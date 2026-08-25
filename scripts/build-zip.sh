#!/usr/bin/env bash
# Build a distributable Basalio plugin ZIP from the current Git HEAD.
# This script ignores the working directory and always exports the committed
# tree, then removes everything listed in .distignore. The result is byte-
# identical for a given commit regardless of local untracked or modified files.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(grep -oE 'Version:[[:space:]]+[0-9]+\.[0-9]+\.[0-9]+' "$REPO_ROOT/basalio.php" | awk '{print $2}')"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

# Export a clean working tree from the committed HEAD.
mkdir -p "$TMP_DIR/basalio"
git -C "$REPO_ROOT" archive --format=tar HEAD | tar -x -C "$TMP_DIR/basalio" -f -

# Remove everything listed in .distignore (skip comments and blank lines).
while IFS= read -r line || [[ -n "$line" ]]; do
  line="${line%%#*}"
  line="$(echo -e "${line}" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  [[ -z "$line" ]] && continue
  rm -rf "$TMP_DIR/basalio/$line"
done < "$REPO_ROOT/.distignore"

OUT_DIR="${1:-$REPO_ROOT}"
OUT="$OUT_DIR/basalio-${VERSION}.zip"
mkdir -p "$OUT_DIR"
cd "$TMP_DIR"
zip -rq "$OUT" basalio

echo "Built: $OUT"
echo "Entries:"
unzip -l "$OUT" | tail -3
