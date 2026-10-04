import importlib.util
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

spec = importlib.util.spec_from_file_location("update_agent_kit", Path(__file__).with_name("update-agent-kit.py"))
updater = importlib.util.module_from_spec(spec)
sys.dont_write_bytecode = True
spec.loader.exec_module(updater)


class UpdateAgentKitTest(unittest.TestCase):
    def setUp(self):
        self.target_dir = tempfile.TemporaryDirectory()
        self.source_dir = tempfile.TemporaryDirectory()
        self.addCleanup(self.target_dir.cleanup)
        self.addCleanup(self.source_dir.cleanup)

        self.target_root = Path(self.target_dir.name)
        self.source_kit = Path(self.source_dir.name)

        manifest = {
            "schema_version": "1.0",
            "kit_version": "1.0.0",
            "source": "https://github.com/luinuxscl/lnx",
            "updated_at": "2026-10-04T00:00:00Z",
            "stack_compatibility": {
                "php": ">=8.5",
                "laravel": "^13.0",
            },
            "supported_agents": ["antigravity"],
            "components": {
                "kit_maintained": [
                    ".ai/rules/general.md",
                ],
                "local_customizations": [],
                "extensions": [],
            },
        }

        self.write(self.source_kit, ".ai/kit-manifest.json", json.dumps(manifest, indent=2))
        self.write(self.source_kit, ".ai/rules/general.md", "# Kit General Rules v1.0\n")
        updater.save_manifest(self.source_kit, manifest)

        self.write(self.target_root, ".ai/kit-manifest.json", json.dumps(manifest, indent=2))
        self.write(self.target_root, ".ai/rules/general.md", "# Kit General Rules v1.0\n")
        updater.save_manifest(self.target_root, manifest)

    def write(self, root: Path, name: str, contents: str):
        path = root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(contents, encoding="utf-8")

    def test_check_compatibility_passes_for_matching_stack(self):
        errors = updater.check_compatibility(self.target_root, self.source_kit)
        self.assertEqual([], errors)

    def test_check_compatibility_fails_for_mismatched_php(self):
        target_manifest = updater.load_manifest(self.target_root)
        target_manifest["stack_compatibility"]["php"] = "^8.1"
        self.write(self.target_root, ".ai/kit-manifest.json", json.dumps(target_manifest, indent=2))

        errors = updater.check_compatibility(self.target_root, self.source_kit)
        self.assertTrue(any("Incompatibilidad de PHP" in error for error in errors))

    def test_analyze_diffs_detects_additions_and_clean_state(self):
        diffs = updater.analyze_diffs(self.target_root, self.source_kit)
        self.assertEqual(1, len(diffs["ok"]))
        self.assertEqual(0, len(diffs["add"]))

        # Add new maintained file in source kit
        source_manifest = updater.load_manifest(self.source_kit)
        source_manifest["components"]["kit_maintained"].append(".ai/skills/new-skill/SKILL.md")
        self.write(self.source_kit, ".ai/kit-manifest.json", json.dumps(source_manifest, indent=2))
        self.write(self.source_kit, ".ai/skills/new-skill/SKILL.md", "---\nname: new-skill\ndescription: New skill\n---\n")

        diffs = updater.analyze_diffs(self.target_root, self.source_kit)
        self.assertEqual(1, len(diffs["add"]))
        self.assertEqual(".ai/skills/new-skill/SKILL.md", diffs["add"][0][0])

    def test_apply_and_rollback_flow(self):
        # Update file in source kit (v1.1)
        source_manifest = updater.load_manifest(self.source_kit)
        source_manifest["kit_version"] = "1.1.0"
        source_manifest["components"]["kit_maintained"].append(".ai/skills/new-skill/SKILL.md")
        self.write(self.source_kit, ".ai/kit-manifest.json", json.dumps(source_manifest, indent=2))
        self.write(self.source_kit, ".ai/skills/new-skill/SKILL.md", "---\nname: new-skill\ndescription: New skill\n---\n")
        self.write(self.source_kit, ".ai/rules/general.md", "# Kit General Rules v1.1\n")

        # Local customization in target project (modified file)
        self.write(self.target_root, ".ai/rules/general.md", "# Local Customization\n")

        # Apply with keep-local strategy
        cmd_apply = [
            sys.executable,
            str(Path(__file__).with_name("update-agent-kit.py")),
            "--root",
            str(self.target_root),
            "--source-kit",
            str(self.source_kit),
            "--apply",
            "--strategy",
            "keep-local",
        ]
        result = subprocess.run(cmd_apply, capture_output=True, text=True, check=False)
        self.assertEqual(0, result.returncode)
        self.assertIn("Actualización aplicada", result.stdout)

        # Verify new file was added and local customization preserved
        self.assertTrue((self.target_root / ".ai/skills/new-skill/SKILL.md").is_file())
        self.assertEqual("# Local Customization\n", (self.target_root / ".ai/rules/general.md").read_text())

        # Test Rollback
        cmd_rollback = [
            sys.executable,
            str(Path(__file__).with_name("update-agent-kit.py")),
            "--root",
            str(self.target_root),
            "--rollback",
        ]
        result_rollback = subprocess.run(cmd_rollback, capture_output=True, text=True, check=False)
        self.assertEqual(0, result_rollback.returncode)
        self.assertIn("Restaurado exitosamente", result_rollback.stdout)

        # Verify new skill file restored/backup state intact
        self.assertEqual("1.0.0", updater.load_manifest(self.target_root)["kit_version"])


if __name__ == "__main__":
    unittest.main()
