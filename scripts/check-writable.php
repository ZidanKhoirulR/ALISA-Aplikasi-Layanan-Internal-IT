<?php
/**
 * Cek folder writable — jalankan sekali di server lalu hapus file ini.
 * CLI: php scripts/check-writable.php
 */
require_once dirname(__DIR__) . '/app/helpers.php';
require_once dirname(__DIR__) . '/app/Storage.php';
loadEnv(dirname(__DIR__) . '/.env');

header('Content-Type: text/plain; charset=utf-8');

echo "ALISA — writable directory check\n";
echo "PHP user: " . (function_exists('posix_getpwuid') && function_exists('posix_geteuid')
    ? (posix_getpwuid(posix_geteuid())['name'] ?? '?')
    : (get_current_user() ?: '?')) . "\n\n";

$dirs = [
    'public/uploads'     => Storage::uploadsDir(),
    'storage/uploads'    => Storage::storageUploadsDir(),
    'tmp/uploads'        => Storage::tmpUploadsDir(),
    'category-icons'     => Storage::categoryIconsDir(),
    'group-icons'        => Storage::groupIconsDir(),
    'sessions'           => Storage::sessionsDir(),
];

$allOk = true;
foreach ($dirs as $label => $path) {
    $check = Storage::ensureWritable($path);
    $status = $check['ok'] ? 'OK' : 'FAIL (' . ($check['error'] ?? '?') . ')';
    if (!$check['ok']) {
        $allOk = false;
    }
    echo sprintf("[%s] %s\n    %s\n", $status, $label, $check['path']);
}

echo "\n--- Chat upload (resolved) ---\n";
$resolved = Storage::resolveChatUploadDir();
if ($resolved['ok']) {
    echo "[OK] Akan pakai: " . $resolved['path'] . "\n";
    echo "     URL prefix: " . $resolved['url_prefix'] . "\n";
} else {
    $allOk = false;
    echo "[FAIL] Tidak ada folder upload yang writable.\n";
    foreach ($resolved['tried'] ?? [] as $line) {
        echo "  - $line\n";
    }
}

echo "\n";
if ($allOk) {
    echo "Semua siap. Upload di menu Percakapan seharusnya berjalan.\n";
    echo "Hapus file ini setelah selesai: scripts/check-writable.php\n";
} else {
    echo "Jalankan: sudo ./scripts/fix-permissions.sh\n";
}
