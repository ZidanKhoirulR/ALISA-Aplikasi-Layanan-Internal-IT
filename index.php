<?php
/**
 * ALISA — Entry Point
 * Fat-Free Framework (F3) Bootstrap & Router
 */
date_default_timezone_set('Asia/Jakarta');

// ── Autoload F3 (choose one approach) ──────────────────────
$f3CorePath = __DIR__ . '/vendor/fatfree-core/base.php';
$composerPath = __DIR__ . '/vendor/autoload.php';

if (file_exists($composerPath)) {
    require $composerPath;
} elseif (file_exists($f3CorePath)) {
    require $f3CorePath;
} else {
    die(
        '<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto">'
        . '<h2>⚠️ Fat-Free Framework tidak ditemukan</h2>'
        . '<p>Jalankan salah satu perintah berikut di terminal:</p>'
        . '<pre style="background:#f1f5f9;padding:16px;border-radius:8px">'
        . "# Cara 1: Composer\ncomposer require bcosca/fatfree-core\n\n"
        . "# Cara 2: Manual\n# Download dari https://fatfreeframework.com\n"
        . '# Letakkan base.php di vendor/fatfree-core/base.php'
        . '</pre></div>'
    );
}

// ── Boot F3 ────────────────────────────────────────────────
$f3 = Base::instance();

// ── Helpers & Security ──────────────────────────────────────
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/Storage.php';
require_once __DIR__ . '/app/Security.php';

// Load Environment Variables
loadEnv(__DIR__ . '/.env');

// Pastikan folder upload & session ada (permission penuh: scripts/fix-permissions.sh)
Storage::bootstrapWritableDirs();

// ── Session ──────────────────────────────────────────────────
$sessionCheck = Storage::ensureWritable(Storage::sessionsDir());
$sessionPath = rtrim($sessionCheck['path'], DIRECTORY_SEPARATOR);
ini_set('session.save_path', $sessionPath);

// Session Cookie Security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');

session_start();

$f3->set('JAR.httponly', true);
$f3->set('JAR.samesite', 'Lax');

// ── Global Security ────────────────────────────────────────
Security::setHeaders($f3);

if ($f3->get('VERB') === 'POST' && !in_array($f3->get('PATH'), ['/login'])) {
    $token = $f3->get('POST.csrf_token')
        ?? $f3->get('HEADERS.X-Csrf-Token')
        ?? $f3->get('HEADERS.X-CSRF-TOKEN')
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!Security::validateCsrfToken($f3, $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

// ── App Settings ───────────────────────────────────────────
$f3->set('AUTOLOAD', 'app/models/;app/controllers/');
$f3->set('UI',       'app/views/');
$f3->set('DEBUG',    0); // Set to 0 for security in production
$f3->set('CACHE',    false);
$f3->set('TZ',       'Asia/Jakarta');


// ── Auth Middleware Helper ─────────────────────────────────
$requireLogin = function (\Base $f3) {
    Security::checkAuth($f3);
};

// ── Routes ─────────────────────────────────────────────────

// Auth
$f3->route('GET|POST /login',  'AuthController->login');
$f3->route('POST     /logout', 'AuthController->logout');
$f3->route('GET      /api/captcha', 'AuthController->getCaptcha');

// Dashboard (main app)
$f3->route('GET /',          'DashboardController->index');
$f3->route('GET /index.php', function($f3) {
    $f3->reroute('/?' . $f3->get('QUERY'));
});
$f3->route('GET /dashboard', function($f3) {
    $f3->reroute('/');
});

// ── AJAX / API Routes ──────────────────────────────────────
$f3->route('POST /api/chat/create',      function ($f3) {
    (new ChatController())->create($f3);
});
$f3->route('GET /api/chats/notifications', function ($f3) {
    (new ChatController())->notifications($f3);
});
$f3->route('GET /api/chats',             function ($f3) {
    (new ChatController())->list($f3);
});
$f3->route('GET /api/chat/@id',          function ($f3, $params) {
    (new ChatController())->info($f3, $params);
});
$f3->route('POST /api/message/send',     function ($f3) {
    (new ChatController())->sendMessage($f3);
});
$f3->route('GET /api/messages/@chat_id', function ($f3, $params) {
    (new ChatController())->getMessages($f3, $params);
});
$f3->route('POST /api/chat/close/@id',   function ($f3, $params) {
    (new ChatController())->close($f3, $params);
});
$f3->route('POST /api/chat/take/@id',    function ($f3, $params) {
    (new ChatController())->take($f3, $params);
});
$f3->route('POST /api/user/status',      function ($f3) {
    (new UserController())->updateStatus($f3);
});
$f3->route('POST /api/user/change-password', function ($f3) {
    (new UserController())->changePassword($f3);
});

// Master Kategori Layanan
$f3->route('GET /api/categories', function ($f3) {
    (new CategoryController())->getActive($f3);
});
$f3->route('GET /api/admin/categories', function ($f3) {
    (new CategoryController())->getAll($f3);
});
$f3->route('POST /api/admin/categories/create', function ($f3) {
    (new CategoryController())->create($f3);
});
$f3->route('POST /api/admin/categories/update', function ($f3) {
    (new CategoryController())->update($f3);
});
$f3->route('POST /api/admin/categories/toggle', function ($f3) {
    (new CategoryController())->toggle($f3);
});
$f3->route('POST /api/admin/categories/delete', function ($f3) {
    (new CategoryController())->delete($f3);
});
$f3->route('GET /api/admin/categories/fields/@code', function ($f3, $params) {
    (new CategoryController())->getFields($f3, $params);
});
$f3->route('POST /api/admin/categories/fields/save', function ($f3) {
    (new CategoryController())->saveField($f3);
});
$f3->route('POST /api/admin/categories/fields/delete', function ($f3) {
    (new CategoryController())->deleteField($f3);
});

// Admin Team Management
$f3->route('GET  /api/admin/sil-users/search', function ($f3) {
    (new UserController())->searchSilUsers($f3);
});
$f3->route('POST /api/admin/team/add',         function ($f3) {
    (new UserController())->addTeamMember($f3);
});
$f3->route('POST /api/admin/team/create',      function ($f3) {
    (new UserController())->createTeamMember($f3);
});
$f3->route('POST /api/admin/team/update',      function ($f3) {
    (new UserController())->updateTeamMember($f3);
});
$f3->route('POST /api/admin/team/delete',      function ($f3) {
    (new UserController())->deleteTeamMember($f3);
});
$f3->route('GET /api/admin/statistics/category-detail', function ($f3) {
    (new ChatController())->getCategoryStats($f3);
});
$f3->route('GET /api/admin/statistics/technician-detail', function ($f3) {
    (new ChatController())->getTechnicianStats($f3);
});
$f3->route('GET /api/admin/statistics/completion-time-detail', function ($f3) {
    (new ChatController())->getCompletionTimeDetail($f3);
});

// Admin Group Chat Management
$f3->route('POST /api/admin/chat/group/create', function ($f3) {
    (new ChatController())->createGroup($f3);
});
$f3->route('POST /api/admin/chat/group/update', function ($f3) {
    (new ChatController())->updateGroup($f3);
});
$f3->route('POST /api/admin/chat/group/delete', function ($f3) {
    (new ChatController())->deleteGroup($f3);
});
$f3->route('POST /api/chat/message/pin', function ($f3) {
    (new ChatController())->pinMessage($f3);
});
$f3->route('POST /api/chat/message/unpin', function ($f3) {
    (new ChatController())->unpinMessage($f3);
});
$f3->route('POST /api/chat/pin', function ($f3) {
    (new ChatController())->pinChat($f3);
});
$f3->route('POST /api/chat/unpin', function ($f3) {
    (new ChatController())->unpinChat($f3);
});
$f3->route('GET /api/admin/users', function ($f3) {
    (new ChatController())->getAllUsers($f3);
});
$f3->route('POST /api/chat/upload', function ($f3) {
    (new ChatController())->uploadFile($f3);
});
$f3->route('GET /files/@file', function ($f3, $params) {
    (new ChatController())->serveChatFile($f3, $params);
});
$f3->route('GET /api/admin/groups', function ($f3) {
    (new ChatController())->listGroups($f3);
});
$f3->route('GET /api/chat/@id/participants', function ($f3, $params) {
    (new ChatController())->getParticipants($f3, $params);
});

// Kalender Ketersediaan
$f3->route('GET /calendar', 'DashboardController->calendar');


$f3->route('GET /api/calendar/events', function ($f3) use ($requireLogin) {
    $requireLogin($f3);
    (new CalendarController())->getEvents($f3);
});

// Zoom Schedule API
$f3->route('POST /api/zoom/schedule', function ($f3) use ($requireLogin) {
    $requireLogin($f3);
    (new ChatController())->saveZoomSchedule($f3);
});

// ── Run ────────────────────────────────────────────────────
$f3->run();
