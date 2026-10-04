#!/usr/bin/env python3
"""Tool to compare, preview, update, and rollback agent kit components in derived projects."""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import shutil
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path


def file_hash(path: Path) -> str:
    if not path.is_file():
        return ""
    return hashlib.sha256(path.read_bytes()).hexdigest()


def load_manifest(root: Path) -> dict:
    manifest_path = root / ".ai" / "kit-manifest.json"
    if not manifest_path.is_file():
        raise FileNotFoundError(f"Falta el manifiesto del kit en {manifest_path}")
    return json.loads(manifest_path.read_text(encoding="utf-8"))


def save_manifest(root: Path, manifest: dict) -> None:
    manifest_path = root / ".ai" / "kit-manifest.json"
    manifest["updated_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    file_hashes: dict[str, str] = {}
    for rel_file in manifest.get("components", {}).get("kit_maintained", []):
        p = root / rel_file
        if p.is_file():
            file_hashes[rel_file] = file_hash(p)
    manifest["file_hashes"] = file_hashes

    manifest_path.write_text(json.dumps(manifest, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def check_compatibility(target_root: Path, source_kit: Path) -> list[str]:
    errors: list[str] = []
    target_manifest = load_manifest(target_root)
    source_manifest = load_manifest(source_kit)

    # Check PHP compatibility
    target_php = target_manifest.get("stack_compatibility", {}).get("php", "")
    source_php = source_manifest.get("stack_compatibility", {}).get("php", "")
    if target_php and source_php and target_php != source_php:
        errors.append(f"Incompatibilidad de PHP: el proyecto requiere '{target_php}' y el kit requiere '{source_php}'")

    # Check Laravel compatibility
    target_laravel = target_manifest.get("stack_compatibility", {}).get("laravel", "")
    source_laravel = source_manifest.get("stack_compatibility", {}).get("laravel", "")
    if target_laravel and source_laravel and target_laravel != source_laravel:
        errors.append(f"Incompatibilidad de Laravel: el proyecto requiere '{target_laravel}' y el kit requiere '{source_laravel}'")

    return errors


def analyze_diffs(target_root: Path, source_kit: Path) -> dict[str, list[tuple[str, str]]]:
    source_manifest = load_manifest(source_kit)
    target_manifest = load_manifest(target_root) if (target_root / ".ai" / "kit-manifest.json").is_file() else {}

    kit_maintained = source_manifest.get("components", {}).get("kit_maintained", [])
    target_maintained = target_manifest.get("components", {}).get("kit_maintained", [])
    all_maintained = sorted(set(kit_maintained + target_maintained))

    target_hashes = target_manifest.get("file_hashes", {})

    results: dict[str, list[tuple[str, str]]] = {
        "ok": [],
        "add": [],
        "update": [],
        "conflict": [],
    }

    for rel_file in all_maintained:
        src_path = source_kit / rel_file
        tgt_path = target_root / rel_file

        src_exists = src_path.is_file()
        tgt_exists = tgt_path.is_file()

        if not src_exists and not tgt_exists:
            continue
        elif src_exists and not tgt_exists:
            results["add"].append((rel_file, "Nuevo archivo incorporado por el kit"))
        elif src_exists and tgt_exists:
            src_h = file_hash(src_path)
            tgt_h = file_hash(tgt_path)
            base_h = target_hashes.get(rel_file, "")

            if src_h == tgt_h:
                results["ok"].append((rel_file, "Sin diferencias con el kit"))
            elif base_h and tgt_h == base_h:
                results["update"].append((rel_file, "Actualización disponible desde el kit"))
            else:
                results["conflict"].append((rel_file, "Conflicto: modificado localmente en el proyecto"))

    return results


def backup_target(target_root: Path, files_to_backup: list[str]) -> Path:
    backup_dir = target_root / ".debug" / "agent-kit-backups" / datetime.now(timezone.utc).strftime("backup-%Y%m%d-%H%M%S")
    backup_dir.mkdir(parents=True, exist_ok=True)

    manifest_file = target_root / ".ai" / "kit-manifest.json"
    if manifest_file.is_file():
        shutil.copy2(manifest_file, backup_dir / "kit-manifest.json")

    backed_up: list[str] = []
    for rel_file in files_to_backup:
        tgt_path = target_root / rel_file
        if tgt_path.is_file():
            dest = backup_dir / rel_file
            dest.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(tgt_path, dest)
            backed_up.append(rel_file)

    meta = {
        "backed_up_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "files": backed_up,
    }
    (backup_dir / "backup-meta.json").write_text(json.dumps(meta, indent=2), encoding="utf-8")

    last_link = target_root / ".ai" / ".last-kit-backup.json"
    last_link.write_text(json.dumps({"backup_dir": str(backup_dir.relative_to(target_root))}, indent=2), encoding="utf-8")

    return backup_dir


def perform_rollback(target_root: Path) -> list[str]:
    last_link = target_root / ".ai" / ".last-kit-backup.json"
    if not last_link.is_file():
        return ["No se encontró registro de respaldo previo (.ai/.last-kit-backup.json) para revertir."]

    data = json.loads(last_link.read_text(encoding="utf-8"))
    backup_dir = target_root / data.get("backup_dir", "")
    if not backup_dir.is_dir():
        return [f"El directorio de respaldo {backup_dir} no existe."]

    meta_file = backup_dir / "backup-meta.json"
    if not meta_file.is_file():
        return [f"Meta-información de respaldo ausente en {meta_file}."]

    meta = json.loads(meta_file.read_text(encoding="utf-8"))
    restored: list[str] = []

    # Restore manifest
    backup_manifest = backup_dir / "kit-manifest.json"
    if backup_manifest.is_file():
        shutil.copy2(backup_manifest, target_root / ".ai" / "kit-manifest.json")
        restored.append(".ai/kit-manifest.json")

    for rel_file in meta.get("files", []):
        src_path = backup_dir / rel_file
        tgt_path = target_root / rel_file
        if src_path.is_file():
            tgt_path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src_path, tgt_path)
            restored.append(rel_file)

    return [f"Restaurado exitosamente: {len(restored)} archivos desde {backup_dir.name}"]


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", type=Path, default=Path(__file__).resolve().parent.parent, help="Ruta al proyecto destino")
    parser.add_argument("--source-kit", type=Path, default=Path(__file__).resolve().parent.parent, help="Ruta al kit fuente")
    parser.add_argument("--check-compat", action="store_true", help="Verificar compatibilidad entre el kit y el proyecto")
    parser.add_argument("--preview", action="store_true", help="Previsualizar cambios sin aplicar")
    parser.add_argument("--apply", action="store_true", help="Aplicar actualización")
    parser.add_argument("--strategy", choices=["keep-local", "accept-kit"], default="keep-local", help="Estrategia en caso de archivos conflictivos")
    parser.add_argument("--rollback", action="store_true", help="Revertir la última actualización aplicada")

    args = parser.parse_args()
    target_root = args.root.resolve()
    source_kit = args.source_kit.resolve()

    if args.rollback:
        messages = perform_rollback(target_root)
        print("\n".join(messages))
        return 0

    if args.check_compat:
        errors = check_compatibility(target_root, source_kit)
        if errors:
            print("Compatibilidad del kit: FALLÓ\n" + "\n".join(errors), file=sys.stderr)
            return 1
        print("Compatibilidad del kit: OK")
        return 0

    diffs = analyze_diffs(target_root, source_kit)

    if args.preview or not args.apply:
        print(f"==> Previsualización de actualización del kit agéntico en {target_root.name}")
        print(f"    Origen: {source_kit}")
        print(f"    Sin cambios: {len(diffs['ok'])}")
        print(f"    Nuevos a añadir: {len(diffs['add'])}")
        print(f"    Actualizaciones: {len(diffs['update'])}")
        print(f"    Conflictos locales: {len(diffs['conflict'])}\n")

        for action, files in [("AÑADIR", diffs["add"]), ("ACTUALIZAR", diffs["update"]), ("CONFLICTO", diffs["conflict"])]:
            if files:
                print(f"[{action}]")
                for rel_file, desc in files:
                    print(f"  - {rel_file}: {desc}")
        return 0

    if args.apply:
        # Check compatibility first
        errors = check_compatibility(target_root, source_kit)
        if errors:
            print("No se puede aplicar la actualización debido a incompatibilidad:\n" + "\n".join(errors), file=sys.stderr)
            return 1

        all_affected = [rel for rel, _ in diffs["add"] + diffs["update"] + diffs["conflict"]]
        if not all_affected:
            print("El kit para agentes ya está al día. Nada que aplicar.")
            return 0

        # Backup
        backup_dir = backup_target(target_root, all_affected)
        print(f"==> Respaldo creado en {backup_dir.name}")

        applied_count = 0
        for rel_file, _ in diffs["add"] + diffs["update"]:
            src_path = source_kit / rel_file
            tgt_path = target_root / rel_file
            tgt_path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src_path, tgt_path)
            applied_count += 1

        for rel_file, _ in diffs["conflict"]:
            if args.strategy == "accept-kit":
                src_path = source_kit / rel_file
                tgt_path = target_root / rel_file
                tgt_path.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(src_path, tgt_path)
                applied_count += 1
            else:
                print(f"Conservando personalización local para: {rel_file}")

        # Update manifest
        source_manifest = load_manifest(source_kit)
        save_manifest(target_root, source_manifest)
        print(f"==> Actualización aplicada ({applied_count} archivos actualizados). Manifiesto guardado.")

        # Run check-agent-context
        checker_script = target_root / "scripts" / "check-agent-context.py"
        if checker_script.is_file():
            print("==> Verificando la coherencia del contexto de agentes...")
            result = subprocess.run([sys.executable, str(checker_script), "--root", str(target_root)], check=False)
            if result.returncode != 0:
                print("ADVERTENCIA: check-agent-context reportó inconsistencias tras la actualización.", file=sys.stderr)

        return 0

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
