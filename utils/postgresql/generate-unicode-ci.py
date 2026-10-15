#!/usr/bin/env python3
"""Generate the one-codepoint UCA 4 primary-weight mapping used by MySQL LIKE.

Usage: curl -fsSL https://www.unicode.org/Public/UCA/4.0.0/allkeys-4.0.0.txt |
    python3 utils/postgresql/generate-unicode-ci.py
Only the pinned Unicode source is accepted; the repository resource is overwritten.
Unicode source data is covered by CoreBundle/Resources/unicode/LICENSE.txt.
"""

import collections
import hashlib
import json
import re
import sys
from pathlib import Path

if len(sys.argv) != 1:
    sys.exit("No path arguments are accepted; pipe the Unicode source to stdin.")

output = Path(__file__).resolve().parents[2] / "app/bundles/CoreBundle/Resources/unicode/utf8mb4_unicode_ci.json"
if output.resolve() != output:
    sys.exit("The resource path must not contain symbolic links.")

source = sys.stdin.buffer.read()
if hashlib.sha256(source).hexdigest() != "e97345da79baf2ab6a72304fe84732b5d0c4b4c6adc888679fd17a6a546ec195":
    sys.exit("Expected the unmodified Unicode UCA 4.0.0 allkeys source.")
groups = collections.defaultdict(list)
for line in source.decode().splitlines():
    if ";" not in line:
        continue
    codepoints, elements = line.split(";", 1)
    if len(codepoints.split()) != 1:
        continue
    codepoint = int(codepoints.strip(), 16)
    if not 0 < codepoint <= 0xFFFF:
        continue
    weights = tuple(
        int(weight, 16)
        for weight in re.findall(r"\[[.*]([0-9A-Fa-f]+)\.", elements)
        if int(weight, 16)
    )
    groups[weights].append(codepoint)

mapping = {}
for codepoints in groups.values():
    if len(codepoints) < 2:
        continue
    representative = min(cp for cp in codepoints if chr(cp) not in "%_\\")
    for codepoint in codepoints:
        if codepoint != representative:
            mapping[chr(codepoint)] = chr(representative)

# Early ASCII identity entries keep PostgreSQL translate() scans short.
characters = [chr(cp) for cp in range(1, 128)]
characters += sorted((ch for ch in mapping if ord(ch) > 127), key=ord)
result = {
    "unicode_version": "4.0.0",
    "source": "https://www.unicode.org/Public/UCA/4.0.0/allkeys-4.0.0.txt",
    "sha256": hashlib.sha256(source).hexdigest(),
    "from": "".join(characters),
    "to": "".join(mapping.get(ch, ch) for ch in characters),
}
assert len(result["from"]) == len(result["to"])
assert "\0" not in result["from"] + result["to"]
output.write_text(json.dumps(result, ensure_ascii=True, indent=2) + "\n", encoding="ascii")
