<?php
// ============================================
// app/controllers/DashboardController.php
// ============================================

class DashboardController
{
    public function beforeroute(\Base $f3): void
    {
        Security::checkAuth($f3);
        $session = $f3->get('SESSION.user');

        $user = User::findById((int)$session['id']);
        if (!$user) {
            $f3->clear('SESSION');
            $f3->reroute('/login');
            exit;
        }
        $user['avatar_url'] = User::getAvatarUrl($user['username'], $user['name'], (int)($user['sil_id'] ?? 0));
        $f3->set('currentUser', $user);

        if (!$f3->get('SESSION.login_date')) {
            $f3->set('SESSION.login_date', date('Y-m-d'));
        }
    }

    public function index(\Base $f3): void
    {
        $user = $f3->get('currentUser');

        // ── Admin: fetch completed chat history & team monitoring ────────────────────────
        $historyChatList = [];
        $historyZoomList = [];
        $techList = [];
        $adminPage = $f3->get('GET.page') ?? 'statistics';

        $filter = $f3->get('GET.filter') ?? ($adminPage === 'groups' ? 'group' : 'active');
        $chatSearch = trim((string)($f3->get('GET.q') ?? ''));
        $chatStart = trim((string)($f3->get('GET.start_date') ?? ''));
        $chatEnd = trim((string)($f3->get('GET.end_date') ?? ''));
        $chatPage = max(1, (int)($f3->get('GET.page_num') ?? 1));
        $chatLimit = 20;
        $chatMeta = null;
        if ($user['role'] === 'admin' && $adminPage === 'chats') {
            if ($chatStart === '') $chatStart = date('Y-m-01');
            if ($chatEnd === '') $chatEnd = date('Y-m-t');
            $paged = Chat::listForUserPaged((int)$user['id'], $user['role'], $filter, $chatSearch, $chatStart, $chatEnd, $chatPage, $chatLimit);
            $chats = $paged['items'];
            $chatMeta = $paged['meta'];
        } else {
            $chats  = Chat::listForUser((int)$user['id'], $user['role'], $filter, $chatStart ?: null, $chatEnd ?: null);
        }

        // Active chat from query string — block IDOR (manual ?chat=ID)
        $requestedChatId = (int)($f3->get('GET.chat') ?? 0);
        $activeChatId = 0;
        if ($requestedChatId > 0) {
            if (!Chat::canUserAccessChat($requestedChatId, (int)$user['id'], (string)$user['role'])) {
                $params = $_GET;
                unset($params['chat']);
                $query = http_build_query($params);
                $f3->set('SESSION.access_denied', 'Anda tidak memiliki akses ke percakapan ini.');
                $f3->reroute('/' . ($query !== '' ? '?' . $query : ''));
                exit;
            }
            $activeChatId = $requestedChatId;
        }
        $activeChat   = $activeChatId ? Chat::findById($activeChatId) : null;

        if ($activeChat && ($activeChat['type'] ?? 'ticket') === 'group') {
            $activeChat['participants'] = Chat::getParticipants((int)$activeChat['id']);
            foreach ($activeChat['participants'] as &$gp) {
                $gp['avatar_url'] = User::getAvatarUrl(
                    $gp['username'] ?? '',
                    $gp['name'] ?? '',
                    (int)($gp['sil_id'] ?? 0)
                );
            }
            unset($gp);
        } else {
            $f3->set('groupCategories', []);
        }

        $messages = $activeChatId ? Message::getByChatId($activeChatId) : [];
        $pinnedMsg = $activeChat ? Message::getPinned($activeChatId) : null;

        if ($user['role'] === 'admin') {
            // Periode Filter
            $start = $f3->get('GET.start') ?: date('Y-m-01'); // 1st of current month
            $end   = $f3->get('GET.end')   ?: date('Y-m-t');
            $historyChatList = Chat::getAdminHistory($start, $end);
            $historyZoomList = Chat::getAdminZoomHistory($start, $end);

            foreach ($historyZoomList as &$z) {
                $z['pemohon_avatar_url'] = User::getAvatarUrl($z['pemohon_username'], $z['pemohon_name'], (int)($z['pemohon_sil_id'] ?? 0));
            }

            $techList = User::getTechniciansWithActiveTasks();
            
            $f3->set('chat_stats', Chat::getStats($start, $end));
            $f3->set('stats_start', $start);
            $f3->set('stats_end', $end);
        }

        $f3->set('history_zoom',  $historyZoomList ?? []);

        // Fetch active categories for UI
        $categories = Category::getActive();
        $categoryFields = CategoryField::getAllGrouped();

        $f3->set('categories',    $categories);
        $f3->set('categoryFields', $categoryFields);
        $f3->set('currentUser',   $user);
        $f3->set('chats',         $chats);
        $f3->set('activeChat',    $activeChat);
        $f3->set('messages',      $messages);
        $f3->set('pinnedMsg',     $pinnedMsg ?? null);
        $f3->set('activeChatId',  $activeChatId);
        $f3->set('filter',        $filter);
        $f3->set('chat_search',   $chatSearch);
        $f3->set('chat_start',    $chatStart);
        $f3->set('chat_end',      $chatEnd);
        $f3->set('chat_page_meta',$chatMeta);
        $f3->set('admin_page',    $adminPage);
        $f3->set('history_chats', $historyChatList);
        $f3->set('tech_list',     $techList);
        $f3->set('PAGE_TITLE',    'Dashboard — ALISA');
        if ($f3->exists('SESSION.access_denied')) {
            $f3->set('access_denied_msg', $f3->get('SESSION.access_denied'));
            $f3->clear('SESSION.access_denied');
        }

        include __DIR__ . '/../views/dashboard.php';
    }

    // GET /calendar
    public function calendar(\Base $f3): void
    {
        // Handle reroute if dashboard query params are present
        if ($f3->get('GET.page') || $f3->get('GET.filter') || $f3->get('GET.chat')) {
            $f3->reroute('/?' . $f3->get('QUERY'));
            return;
        }

        $user = $f3->get('currentUser');
        $filter = 'all';
        $chatStart = trim((string)($f3->get('GET.start_date') ?? ''));
        $chatEnd = trim((string)($f3->get('GET.end_date') ?? ''));
        $chats  = Chat::listForUser((int)$user['id'], $user['role'], $filter, $chatStart ?: null, $chatEnd ?: null);
        $activeChatId = 0;

        $categories = Category::getActive();

        $f3->set('currentUser',  $user);
        $f3->set('chats',        $chats);
        $f3->set('categories',   $categories);
        $f3->set('categoryFields', CategoryField::getAllGrouped());
        $f3->set('activeChat',   null);
        $f3->set('messages',     []);
        $f3->set('activeChatId', $activeChatId);
        $f3->set('filter',       $filter);
        $f3->set('admin_page',   'calendar');
        $f3->set('PAGE_TITLE',   'Kalender — ALISA');

        include __DIR__ . '/../views/calendar.php';
    }
}
