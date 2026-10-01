#!/usr/bin/env python3
"""Validate maintained agent references without executing Markdown content."""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path


LINK = re.compile(r"(?<!!)\[[^\]]+\]\(([^)]+)\)")
PATH_REFERENCE = re.compile(r"`((?:README\.md|\.ai/|app/|resources/|routes/|scripts/)[^`\s]*)`")
SKILL_NAME = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


def local_target(root: Path, source: Path, reference: str) -> Path | None:
    target = reference.split("#", 1)[0].strip()
    if not target or re.match(r"^[a-z][a-z0-9+.-]*:", target, re.I) or target.startswith("//"):
        return None

    candidate = (source.parent / target).resolve()
    if not candidate.is_relative_to(root.resolve()):
        return None

    return candidate


def check_project(root: Path) -> list[str]:
    errors: list[str] = []
    index = root / ".ai/rules/index.md"
    if not index.is_file():
        return [".ai/rules/index.md: falta el índice; restaura el archivo de reglas."]

    indexed_rules: set[Path] = set()
    for line in index.read_text(encoding="utf-8").splitlines():
        if not line.startswith("|") or ".ai/rules/" not in line:
            continue
        cells = [cell.strip().strip("`") for cell in line.split("|")[1:-1]]
        if len(cells) != 2:
            errors.append(".ai/rules/index.md: fila de índice inválida; usa glob y ruta de regla.")
            continue
        glob, rule_path = cells
        rule = root / rule_path
        indexed_rules.add(rule)
        if not rule.is_file():
            errors.append(f".ai/rules/index.md: {rule_path} no existe; corrige la ruta o crea la regla.")
        elif not re.search(r"(?m)^\s*-\s*['\"]?" + re.escape(glob) + r"['\"]?\s*$", rule.read_text(encoding="utf-8")):
            errors.append(f"{rule_path}: no declara el glob {glob}; sincroniza el índice y el frontmatter.")

    for rule in (root / ".ai/rules").glob("*.md"):
        if rule != index and rule not in indexed_rules:
            errors.append(f"{rule.relative_to(root)}: falta en el índice; añade su glob a .ai/rules/index.md.")

    maintained = [root / "README.md", *root.glob(".ai/guidelines/**/*.md"), *root.glob(".ai/rules/**/*.md"), *root.glob(".ai/skills/**/SKILL.md")]
    for source in maintained:
        if not source.is_file():
            continue
        content = source.read_text(encoding="utf-8")
        for match in LINK.finditer(content):
            target = local_target(root, source, match.group(1))
            if target is not None and not target.exists():
                errors.append(f"{source.relative_to(root)}: enlace roto {match.group(1)}; corrige la ruta o crea el destino.")

        for match in PATH_REFERENCE.finditer(content):
            reference = match.group(1).rstrip(".,;:")
            if "*" in reference or "{" in reference:
                continue
            target = root / reference
            if not target.exists():
                errors.append(f"{source.relative_to(root)}: referencia rota {reference}; corrige la ruta o crea el destino.")

    for skill_directory in (root / ".ai/skills").glob("*"):
        if skill_directory.is_dir() and not (skill_directory / "SKILL.md").is_file():
            errors.append(f"{skill_directory.relative_to(root)}: falta SKILL.md; añade la entrada de la skill.")

    for skill in (root / ".ai/skills").glob("*/SKILL.md"):
        content = skill.read_text(encoding="utf-8")
        frontmatter = re.match(r"\A---\n(.*?)\n---\n", content, re.S)
        if frontmatter is None:
            errors.append(f"{skill.relative_to(root)}: frontmatter inválido; añade name y description entre ---.")
            continue
        name = re.search(r"(?m)^name:\s*(.+)$", frontmatter.group(1))
        description = re.search(r"(?m)^description:\s*(.+)$", frontmatter.group(1))
        if name is None or not SKILL_NAME.fullmatch(name.group(1).strip()) or name.group(1).strip() != skill.parent.name:
            errors.append(f"{skill.relative_to(root)}: name inválido; usa el nombre de la carpeta en kebab-case.")
        if description is None or not description.group(1).strip():
            errors.append(f"{skill.relative_to(root)}: falta description; describe cuándo aplicar la skill.")

    readme = root / "README.md"
    if readme.is_file():
        verification_line = next((line for line in readme.read_text(encoding="utf-8").splitlines() if line.startswith("| Verificación |")), "")
        if not verification_line:
            errors.append("README.md: falta la fila Verificación; documenta los comandos mantenidos.")
        else:
            manifests = {}
            for manifest_name in ("composer.json", "package.json"):
                manifest = root / manifest_name
                try:
                    manifests[manifest_name] = json.loads(manifest.read_text(encoding="utf-8")) if manifest.is_file() else {}
                except json.JSONDecodeError:
                    errors.append(f"{manifest_name}: JSON inválido; corrige la sintaxis para verificar comandos.")
                    manifests[manifest_name] = {}
            composer = manifests["composer.json"]
            package = manifests["package.json"]
            for command in re.findall(r"`([^`]+)`", verification_line):
                parts = command.split()
                valid = (
                    (parts[:1] == ["composer"] and len(parts) >= 2 and parts[1] in composer.get("scripts", {}))
                    or (parts[:2] == ["npm", "run"] and len(parts) >= 3 and parts[2] in package.get("scripts", {}))
                    or (parts[:2] == ["php", "artisan"] and (root / "artisan").is_file())
                    or (parts[:1] == ["python3"] and len(parts) >= 2 and (root / parts[1]).is_file())
                    or (parts[:1] == ["vendor/bin/pint"] and (root / "composer.json").is_file() and "laravel/pint" in composer.get("require-dev", {}))
                )
                if not valid:
                    errors.append(f"README.md: comando no verificable {command}; corrige la fila Verificación o su script.")

    return errors


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", type=Path, default=Path(__file__).resolve().parent.parent)
    args = parser.parse_args()
    errors = check_project(args.root.resolve())
    if errors:
        print("\n".join(errors), file=sys.stderr)
        return 1

    print("Agent context: OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
