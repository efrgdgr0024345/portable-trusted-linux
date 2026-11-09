#!/usr/bin/env python3
"""Read-only verification of the generated ISO's filesystem, El Torito and ESP binding."""
import argparse
import hashlib
import importlib.util
import json
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile

sys.dont_write_bytecode = True
SOURCE = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("ptl_uki_verify", SOURCE / "verifier/verify-development-uki.py")
v = importlib.util.module_from_spec(spec)
spec.loader.exec_module(v)
SYSTEM_SBIN_PATH = "/usr/sbin:/sbin"


def partition_tool():
    # Service and ordinary non-root shells may omit administrative tool paths.
    tool = shutil.which("sfdisk") or shutil.which("sfdisk", path=SYSTEM_SBIN_PATH)
    if tool is None:
        raise ValueError("Required partition inspection tool sfdisk is unavailable")
    return tool


def extent_digest(iso, offset, length):
    h = hashlib.sha256()
    with iso.open("rb") as stream:
        stream.seek(offset)
        remaining = length
        while remaining:
            block = stream.read(min(1024 * 1024, remaining))
            if not block:
                raise ValueError("Truncated ISO boot extent")
            h.update(block)
            remaining -= len(block)
    return h.hexdigest()


def check(iso, bundle):
    provenance = json.loads((bundle / "provenance.json").read_text())
    manifest = json.loads((bundle / "inputs.json").read_text())
    report = {"status": "VERIFIED", "iso_sha256": v.digest(iso), "iso_size": iso.stat().st_size,
              "source_commit": provenance["source_commit"], "firmware_enforcement": "NOT VERIFIED",
              "scope": "Read-back of signed UKI and expected live inputs from actual ISO/boot extents"}
    with tempfile.TemporaryDirectory(prefix="ptl-m15-iso-") as name:
        temp = Path(name)
        def extract(source, destination):
            v.command(["xorriso", "-osirrox", "on", "-indev", iso, "-extract", source, destination])
        extract("/EFI/Linux/blackcat-development.efi", temp / "filesystem.efi")
        extract("/boot/grub/efi.img", temp / "esp.img")
        extract("/live", temp / "live")
        for pattern, section in (("vmlinuz*", ".linux"), ("initrd*", ".initrd")):
            hashes = {v.digest(x) for x in (temp / "live").glob(pattern) if x.is_file()}
            if hashes != {manifest["inputs"][section]["sha256"]}:
                raise ValueError("ISO live input mismatch or ambiguity: " + section)
        if v.digest(temp / "filesystem.efi") != provenance["signed_uki_sha256"]:
            raise ValueError("ISO filesystem UKI differs from signed artifact")
        if v.digest(temp / "esp.img") != provenance["esp_sha256"]:
            raise ValueError("ISO ESP differs from built development ESP")
        v.command(["mcopy", "-i", temp / "esp.img", "::EFI/BOOT/BOOTX64.EFI", temp / "fallback.efi"])
        if v.digest(temp / "fallback.efi") != provenance["signed_uki_sha256"]:
            raise ValueError("UEFI fallback does not match verified UKI")
        report["signature"] = v.verify(temp / "fallback.efi", bundle / "development-cert.pem",
                                       manifest, provenance["certificate_der_sha256"])
        catalog = v.command(["xorriso", "-indev", iso, "-report_el_torito", "plain"]).decode()
        (bundle / "el-torito.log").write_text(catalog)
        lbas = []
        for line in catalog.splitlines():
            match = re.search(r"El Torito boot img\s*:\s+\d+\s+UEFI\s+.*\s+(\d+)\s*$", line)
            if match:
                lbas.append(int(match.group(1)))
        if len(lbas) != 1:
            raise ValueError("Require exactly one UEFI El Torito boot image")
        size = (temp / "esp.img").stat().st_size
        if extent_digest(iso, lbas[0] * 2048, size) != provenance["esp_sha256"]:
            raise ValueError("Actual El Torito UEFI boot extent differs from verified ESP")
        report["el_torito_uefi_lba"] = lbas[0]
        table = json.loads(v.command([partition_tool(), "--json", iso]))
        (bundle / "partition-table.json").write_text(json.dumps(table, indent=2) + "\n")
        pt = table["partitiontable"]
        sector_size = pt.get("sectorsize", 512)
        partitions = [p for p in pt.get("partitions", []) if str(p.get("type", "")).lower() in
                      ("ef", "0xef", "c12a7328-f81f-11d2-ba4b-00a0c93ec93b")]
        if len(partitions) != 1:
            raise ValueError("Require one hybrid-media EFI partition")
        partition = partitions[0]
        if partition["size"] * sector_size < size:
            raise ValueError("Hybrid EFI partition is shorter than ESP")
        if extent_digest(iso, partition["start"] * sector_size, size) != provenance["esp_sha256"]:
            raise ValueError("Hybrid media EFI partition differs from verified ESP")
        report["hybrid_efi_start_sector"] = partition["start"]
        report["hybrid_efi_sector_size"] = sector_size
    (bundle / "iso-verification.json").write_text(json.dumps(report, indent=2) + "\n")
    print(json.dumps(report, indent=2))


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--iso", type=Path, required=True)
    p.add_argument("--bundle", type=Path, required=True)
    args = p.parse_args()
    try:
        check(args.iso.resolve(), args.bundle.resolve())
    except (ValueError, OSError, KeyError, StopIteration) as error:
        print("M15_ISO_FAILED: " + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
