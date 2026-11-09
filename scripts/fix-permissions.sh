#!/bin/bash
# Perbaiki izin folder upload ALISA di server Linux.
# Usage (dari root project):
#   chmod +x scripts/fix-permissions.sh
#   sudo ./scripts/fix-permissions.sh
# Atau dengan user web server lain:
#   sudo WEB_USER=apache ./scripts/fix-permissions.sh

set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

WEB_USER="${WEB_USER:-www-data}"

echo "ALISA — fix permissions"
echo "Project: $ROOT"
echo "Web user: $WEB_USER"
echo ""

mkdir -p public/uploads public/category-icons public/group-icons storage/uploads tmp/uploads tmp/sessions

if id "$WEB_USER" &>/dev/null; then
  chown -R "$WEB_USER:$WEB_USER" public/uploads public/category-icons public/group-icons storage/uploads tmp/uploads tmp/sessions
else
  echo "WARNING: user '$WEB_USER' tidak ditemukan. Lewati chown — set WEB_USER=apache atau nginx."
fi

chmod -R 777 public/uploads public/category-icons public/group-icons storage/uploads tmp/uploads tmp/sessions

# SELinux (CentOS/RHEL) — abaikan jika tidak dipakai
if command -v chcon &>/dev/null && [ "$(getenforce 2>/dev/null)" = "Enforcing" ]; then
  chcon -R -t httpd_sys_rw_content_t public/uploads public/category-icons public/group-icons storage/uploads tmp/uploads tmp/sessions 2>/dev/null || true
  echo "SELinux context applied (httpd_sys_rw_content_t)."
fi

echo ""
echo "Selesai. Cek dengan: php scripts/check-writable.php"
ls -la public/uploads public/category-icons public/group-icons storage/uploads tmp/uploads tmp/sessions
