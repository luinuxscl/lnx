import importlib.util
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path


spec = importlib.util.spec_from_file_location("check_agent_context", Path(__file__).with_name("check-agent-context.py"))
checker = importlib.util.module_from_spec(spec)
sys.dont_write_bytecode = True
spec.loader.exec_module(checker)


class AgentContextCheckTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.write(".ai/kit-manifest.json", '{\n  "schema_version": "1.0",\n  "kit_version": "1.0.0",\n  "source": "https://github.com/luinuxscl/lnx",\n  "updated_at": "2026-10-04T00:00:00Z",\n  "stack_compatibility": {"php": ">=8.5"},\n  "supported_agents": ["antigravity"],\n  "components": {"kit_maintained": ["README.md"]}\n}\n')
        self.write(".ai/rules/index.md", "| Applies to | Rule file |\n| --- | --- |\n| ** | .ai/rules/general.md |\n")
        self.write(".ai/rules/general.md", "---\npaths:\n  - '**'\n---\n# General\n")
        self.write(".ai/skills/design-screen/SKILL.md", "---\nname: design-screen\ndescription: Design a screen.\n---\nRead `README.md`.\n")
        self.write("README.md", "| Verificación | `python3 scripts/check-agent-context.py` |\n[Rules](.ai/rules/index.md)\n")
        self.write("scripts/check-agent-context.py", "")

    def write(self, name, contents):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(contents, encoding="utf-8")

    def test_valid_references_pass(self):
        self.assertEqual([], checker.check_project(self.root))

    def test_broken_link_and_rule_glob_fail(self):
        self.write("README.md", "| Verificación | `python3 scripts/check-agent-context.py` |\n[Missing](missing.md)\n")
        self.write(".ai/rules/general.md", "---\npaths:\n  - 'app/**'\n---\n")

        errors = checker.check_project(self.root)

        self.assertTrue(any("enlace roto" in error for error in errors))
        self.assertTrue(any("no declara el glob" in error for error in errors))

    def test_invalid_skill_and_command_fail(self):
        self.write(".ai/skills/design-screen/SKILL.md", "No frontmatter\n")
        self.write("README.md", "| Verificación | `composer missing` |\n")

        errors = checker.check_project(self.root)

        self.assertTrue(any("frontmatter inválido" in error for error in errors))
        self.assertTrue(any("comando no verificable" in error for error in errors))

    def test_broken_inline_path_fails(self):
        self.write(".ai/skills/design-screen/SKILL.md", "---\nname: design-screen\ndescription: Design a screen.\n---\nRead `resources/views/missing.blade.php`.\n")

        errors = checker.check_project(self.root)

        self.assertTrue(any("referencia rota" in error for error in errors))

    def test_missing_skill_file_and_invalid_manifest_fail(self):
        (self.root / ".ai/skills/design-screen/SKILL.md").unlink()
        self.write("composer.json", "{")

        errors = checker.check_project(self.root)

        self.assertTrue(any("falta SKILL.md" in error for error in errors))
        self.assertTrue(any("composer.json: JSON inválido" in error for error in errors))

    def test_missing_kit_manifest_fails(self):
        (self.root / ".ai/kit-manifest.json").unlink()

        errors = checker.check_project(self.root)

        self.assertTrue(any("falta el manifiesto del kit" in error for error in errors))

    def test_cli_exits_with_file_and_correction_for_a_broken_fixture(self):
        self.write("README.md", "| Verificación | `python3 scripts/check-agent-context.py` |\n[Missing](missing.md)\n")

        result = subprocess.run(
            [sys.executable, str(Path(__file__).with_name("check-agent-context.py")), "--root", str(self.root)],
            capture_output=True,
            text=True,
            check=False,
        )

        self.assertEqual(1, result.returncode)
        self.assertIn("README.md: enlace roto missing.md; corrige la ruta", result.stderr)


if __name__ == "__main__":
    unittest.main()
