#!/usr/bin/env bash
# Ubah frame PNG di sequence/ menjadi WebP supaya hero tidak berat.
# PNG asli tetap disimpan lokal (di-ignore git) sebagai master.
#
# Pakai: tools/sequence-webp.sh [kualitas]   # default 88
set -euo pipefail
cd "$(dirname "$0")/.."

quality="${1:-88}"

if ! command -v cwebp >/dev/null 2>&1; then
    echo "cwebp tidak ditemukan. Pasang libwebp dulu." >&2
    exit 1
fi

shopt -s nullglob
files=(sequence/*.png)
if [ ${#files[@]} -eq 0 ]; then
    echo "Tidak ada PNG di sequence/."
    exit 0
fi

for file in "${files[@]}"; do
    cwebp -quiet -q "$quality" -m 6 -mt "$file" -o "${file%.png}.webp"
done

echo "Selesai: ${#files[@]} frame jadi WebP (q$quality)."
