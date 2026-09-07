#!/usr/bin/env bash
# Cut a new release. Usage: bin/release.sh 1.0.2
set -euo pipefail

VERSION="${1:?usage: bin/release.sh <version>}"
SLUG="numbered-accordion-elementor"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ -n "$(git status --porcelain)" ]]; then
	echo "working tree is dirty; commit or stash first" >&2
	exit 1
fi

# Bump version in the three places it appears.
sed -i '' -E "s/^( \* Version: +).*/\1${VERSION}/" "${SLUG}.php"
sed -i '' -E "s/(define\( 'NACC_VERSION', ')[^']+('\ \);)/\1${VERSION}\2/" "${SLUG}.php"
sed -i '' -E "s/^(Stable tag: ).*/\1${VERSION}/" readme.txt

# Build the distributable zip from a clean export.
BUILD="$(mktemp -d)"
mkdir -p "${BUILD}/${SLUG}"
rsync -a --exclude '.git' --exclude '.gitignore' --exclude 'bin' --exclude 'docs' \
      --exclude 'README.md' --exclude '*.zip' --exclude '.DS_Store' \
      ./ "${BUILD}/${SLUG}/"
( cd "$BUILD" && zip -rq "${SLUG}.zip" "$SLUG" )
mv "${BUILD}/${SLUG}.zip" "./${SLUG}.zip"
rm -rf "$BUILD"

git add -A
git commit -m "Release ${VERSION}"
git tag "v${VERSION}"
git push origin main --tags

gh release create "v${VERSION}" "./${SLUG}.zip" \
	--title "v${VERSION}" \
	--notes "See readme.txt changelog."

echo "released v${VERSION}"
