#!/usr/bin/env python3
"""Prove alias handling never turns into arbitrary first-kernel selection."""
import importlib.util
import os
from pathlib import Path
import sys
import tempfile
import unittest

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location("m15_build", ROOT / "build/50-build-development-uki.py")
BUILD = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(BUILD)


class InputSelectionTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.work = Path(self.temp.name)
        self.live = self.work / "binary/live"
        self.boot = self.work / "chroot/boot"
        self.live.mkdir(parents=True)
        self.boot.mkdir(parents=True)
        for directory in (self.live, self.boot):
            (directory / "vmlinuz-fixture").write_bytes(b"kernel bytes")
            (directory / "initrd.img-fixture").write_bytes(b"initrd bytes")

    def test_hard_link_aliases_are_same_payload(self):
        os.link(self.live / "vmlinuz-fixture", self.live / "vmlinuz")
        os.link(self.live / "initrd.img-fixture", self.live / "initrd.img")
        self.assertEqual(BUILD.select_live_inputs(self.work)[2], "fixture")

    def test_copied_aliases_are_same_payload(self):
        (self.live / "vmlinuz").write_bytes(b"kernel bytes")
        (self.live / "initrd.img").write_bytes(b"initrd bytes")
        self.assertEqual(BUILD.select_live_inputs(self.work)[2], "fixture")

    def test_different_live_kernel_refused(self):
        (self.live / "vmlinuz-other").write_bytes(b"other kernel")
        with self.assertRaisesRegex(RuntimeError, "one distinct"):
            BUILD.select_live_inputs(self.work)

    def test_different_live_initrd_refused(self):
        (self.live / "initrd.img-other").write_bytes(b"other initrd")
        with self.assertRaisesRegex(RuntimeError, "one distinct"):
            BUILD.select_live_inputs(self.work)

    def test_initrd_must_match_chroot_version(self):
        (self.boot / "initrd.img-fixture").write_bytes(b"mismatched initrd")
        with self.assertRaisesRegex(RuntimeError, "exact initrd"):
            BUILD.select_live_inputs(self.work)

    def test_ambiguous_version_refused_even_with_identical_kernel_bytes(self):
        (self.boot / "vmlinuz-other-version").write_bytes(b"kernel bytes")
        with self.assertRaisesRegex(RuntimeError, "one exact chroot kernel version"):
            BUILD.select_live_inputs(self.work)


if __name__ == "__main__":
    unittest.main()
