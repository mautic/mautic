#!/usr/bin/env python3
"""Run with python3 -m unittest discover -s utils/postgresql -p 'test_*.py'."""

import subprocess
import sys
import tempfile
import unittest
from pathlib import Path


class UnicodeGeneratorTest(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        root = Path(self.directory.name)
        self.script = root / "utils/postgresql/generate-unicode-ci.py"
        self.script.parent.mkdir(parents=True)
        self.script.write_bytes(Path(__file__).with_name("generate-unicode-ci.py").read_bytes())
        self.output = root / "app/bundles/CoreBundle/Resources/unicode/utf8mb4_unicode_ci.json"
        self.output.parent.mkdir(parents=True)
        self.output.write_text("unchanged", encoding="ascii")

    def run_generator(self, *arguments):
        return subprocess.run(
            [sys.executable, str(self.script), *arguments],
            input=b"untrusted source",
            capture_output=True,
            check=False,
        )

    def test_path_arguments_are_rejected(self):
        result = self.run_generator("../../input.txt", "../../output.json")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(b"No path arguments", result.stderr)
        self.assertEqual(self.output.read_text(), "unchanged")

    def test_untrusted_source_cannot_overwrite_the_resource(self):
        result = self.run_generator()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(b"unmodified Unicode UCA 4.0.0", result.stderr)
        self.assertEqual(self.output.read_text(), "unchanged")

    def test_symbolic_link_cannot_redirect_the_output(self):
        target = Path(self.directory.name) / "other.json"
        target.write_text("unchanged", encoding="ascii")
        self.output.unlink()
        self.output.symlink_to(target)
        result = self.run_generator()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(b"symbolic links", result.stderr)
        self.assertEqual(target.read_text(), "unchanged")

    def test_symbolic_link_directory_cannot_redirect_the_output(self):
        target = Path(self.directory.name) / "other"
        self.output.parent.rename(target)
        self.output.parent.symlink_to(target, target_is_directory=True)
        result = self.run_generator()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(b"symbolic links", result.stderr)
        self.assertEqual((target / self.output.name).read_text(), "unchanged")


if __name__ == "__main__":
    unittest.main()
