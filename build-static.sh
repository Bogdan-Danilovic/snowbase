#!/usr/bin/env bash
# Exports the PHP site to static HTML in ./public (deployed by Vercel).
# Requires: php with pdo_mysql, a MySQL/MariaDB loaded from migracija.sql,
# DB env vars (MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD).
# Usage: ./build-static.sh   (then commit ./public)
set -euo pipefail
cd "$(dirname "$0")"

PORT="${EXPORT_PORT:-8099}"
BASE="http://127.0.0.1:${PORT}"
OUT="public"

php -S "127.0.0.1:${PORT}" >/dev/null 2>&1 &
PHP_PID=$!
trap 'kill $PHP_PID 2>/dev/null || true' EXIT
sleep 1

rm -rf "$OUT"
mkdir -p "$OUT"

# Rewrites PHP links to static file names.
rewrite() {
  sed -E \
    -e "s#'destinacija\.php\?id=' \+ ([A-Za-z_.]+)#'destinacija-' + \1 + '.html'#g" \
    -e 's#destinacija\.php\?id=([0-9]+)#destinacija-\1.html#g' \
    -e 's#index\.php#index.html#g'
}

fetch() { # $1 = url path, $2 = output file
  local code
  code=$(curl -s -o "$OUT/$2.tmp" -w '%{http_code}' "$BASE/$1")
  [ "$code" = "200" ] || { echo "FAIL $1 -> $code"; exit 1; }
  rewrite < "$OUT/$2.tmp" > "$OUT/$2"
  rm "$OUT/$2.tmp"
  echo "ok  $1 -> $OUT/$2"
}

fetch "index.php" "index.html"

IDS=$(php -r 'require "db.php"; echo implode(" ", $pdo->query("SELECT id FROM destinacije ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));')
for id in $IDS; do
  fetch "destinacija.php?id=${id}" "destinacija-${id}.html"
done

cp -r Slike "$OUT/"
cp "style 3.0.css" "$OUT/"

# Sanity check: no PHP links left.
if grep -lE "\.php[?'\"#]" "$OUT"/*.html; then
  echo "WARNING: .php references remain in files above"
fi
echo "Done."
