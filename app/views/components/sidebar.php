<?php
// ============================================
// app/views/components/sidebar.php
// Variables available:
//   $currentUser, $chats, $activeChatId, $filter
// ============================================

$user   = $f3->get('currentUser');
$chats  = $f3->get('chats');
$actId  = $f3->get('activeChatId');
$filter = $f3->get('filter') ?: 'all';
$adminPage = $f3->get('admin_page') ?: '';
$pageQuery = trim((string)($f3->get('GET.page') ?? $_GET['page'] ?? ''));
$isAdminChatPage = ($user['role'] === 'admin' && ($adminPage === 'chats' || $pageQuery === 'chats'));
$chatSearch = (string)($f3->get('chat_search') ?? '');
$chatStart = (string)($f3->get('chat_start') ?? '');
$chatEnd = (string)($f3->get('chat_end') ?? '');
$chatMeta = $f3->get('chat_page_meta') ?? null;
$periodQs = ($chatStart !== '' ? '&start_date=' . urlencode($chatStart) : '')
          . ($chatEnd !== '' ? '&end_date=' . urlencode($chatEnd) : '');
$preservePeriod = ($user['role'] === 'user' || $isAdminChatPage);
?>

<aside class="sidebar" id="sidebar">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <div class="sidebar-brand">
      <div class="sidebar-icon">
        <?php
        $logoPath = '';
        if (is_dir('public')) {
            $files = scandir('public');
            if ($files !== false) {
                foreach ($files as $file) {
                    $lowerFile = strtolower($file);
                    if (strpos($lowerFile, 'logo.') === 0) {
                        $logoPath = "public/$file";
                        break;
                    }
                }
            }
        }
        if ($logoPath): ?>
          <img src="<?= $logoPath ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;" />
        <?php else: ?>
          <i class="bi bi-headset"></i>
        <?php endif; ?>
      </div>
    </div>

    <!-- User Profile Mini -->
    <div class="sidebar-user" id="userMenuToggle">
      <?php $userImg = User::getAvatarUrl($user['username'], $user['name'], (int)($user['sil_id'] ?? 0)); ?>
      <div class="avatar-sm"
           style="background:<?= $userImg ? 'none' : avatarColor($user['id']) ?>">
        <?php if ($userImg): ?>
          <img src="<?= $userImg ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
        <?php else: ?>
          <?= htmlspecialchars($user['avatar'] ?? User::getInitials($user['name'] ?? '')) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
        <div class="sidebar-user-role"><?= ucfirst($user['role']) ?></div>
      </div>
      <i class="bi bi-chevron-down ms-auto small"></i>
    </div>

    <!-- User Dropdown -->
    <div class="sidebar-user-menu" id="userDropdown" style="display:none">
      <?php if (in_array($user['role'], ['teknisi','admin'])): ?>
      <div class="status-toggle-label">Status Saya</div>
      <div class="status-buttons">
        <?php foreach(['available'=>'Tersedia','busy'=>'Sibuk','offline'=>'Offline'] as $s=>$label): ?>
        <button class="status-btn <?= $user['status']===$s?'active':'' ?>"
                data-status="<?= $s ?>" onclick="updateMyStatus('<?= $s ?>')">
          <?= techStatusDot($s) ?> <?= $label ?>
        </button>
        <?php endforeach; ?>
      </div>
      <hr class="sidebar-divider"/>
      <?php endif; ?>
      <a href="#" class="sidebar-menu-item text-primary" onclick="openChangePasswordModal(); return false;">
        <i class="bi bi-shield-lock"></i> Ganti Password
      </a>
      <hr class="sidebar-divider"/>
      <form method="POST" action="logout" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken($f3)) ?>">
        <button type="submit" class="sidebar-menu-item text-danger w-100 text-start border-0 bg-transparent">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </form>
    </div>
  </div>

  <!-- New Request Button -->
  <?php 
    $isInGroup = ($f3->get('activeChat') && ($f3->get('activeChat')['type'] ?? 'ticket') === 'group');
  ?>
  <?php if ($user['role'] === 'user'): ?>
  <div class="sidebar-new-btn-wrap mb-2">
    <button class="btn-new-request" onclick="openNewRequestModal()">
      <i class="bi bi-plus-circle-fill"></i>
      <span>Permintaan Baru</span>
    </button>
  </div>
  <div class="sidebar-new-btn-wrap mb-2">
    <a href="calendar" class="btn-new-request" style="background: rgba(255,255,255,0.08); box-shadow: none;">
      <i class="bi bi-calendar3"></i>
      <span>Kalender</span>
    </a>
  </div>
  <?php if ($isInGroup && !empty($f3->get('groupCategories'))): ?>
  <div class="sidebar-new-btn-wrap mb-2">
    <button class="btn-new-request" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);" onclick="openGroupRequestModal()">
      <i class="bi bi-ticket-detailed-fill"></i>
      <span>Buat Permintaan Grup</span>
    </button>
  </div>
  <?php endif; ?>

  <?php elseif ($user['role'] === 'admin'): ?>
  <div class="sidebar-new-btn-wrap d-lg-none">
    <a href="?page=monitoring" class="btn-new-request" style="background: rgba(255,255,255,0.1); box-shadow: none;">
      <i class="bi bi-arrow-left"></i>
      <span>Kembali ke Admin</span>
    </a>
  </div>
  <?php endif; ?>

  <?php if ($isAdminChatPage): ?>
  <div class="px-3 pb-2 pt-3">
    <form method="GET" action="" class="d-flex flex-column gap-2">
      <input type="hidden" name="page" value="chats">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <?php if ($actId): ?><input type="hidden" name="chat" value="<?= (int)$actId ?>"><?php endif; ?>
      <input type="text" class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($chatSearch) ?>" placeholder="Cari percakapan...">
      <div class="d-flex gap-1">
        <input type="date" class="form-control form-control-sm" name="start_date" value="<?= htmlspecialchars($chatStart) ?>">
        <input type="date" class="form-control form-control-sm" name="end_date" value="<?= htmlspecialchars($chatEnd) ?>">
      </div>
      <button type="submit" class="btn btn-sm btn-primary">Terapkan Filter</button>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($user['role'] === 'user'): ?>
  <div style="padding: 8px 20px 12px;">
    <form method="GET" action="" class="d-flex flex-column gap-2" id="userPeriodFilterForm">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <?php if ($actId): ?><input type="hidden" name="chat" value="<?= (int)$actId ?>"><?php endif; ?>
      <div class="sidebar-period-label">
        <i class="bi bi-calendar3 me-1"></i> Filter Periode
      </div>
      <div class="d-flex gap-2">
        <input type="date" class="form-control form-control-sm sidebar-date-input" name="start_date" value="<?= htmlspecialchars($chatStart ?: ($f3->get('SESSION.login_date') ?: date('Y-m-d'))) ?>" placeholder="Dari">
        <input type="date" class="form-control form-control-sm sidebar-date-input" name="end_date" value="<?= htmlspecialchars($chatEnd ?: ($f3->get('SESSION.login_date') ?: date('Y-m-d'))) ?>" placeholder="Sampai">
      </div>
      <div class="d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Terapkan</button>
        <?php if ($chatStart || $chatEnd): ?>
        <a href="?filter=<?= htmlspecialchars($filter) ?><?= $actId ? '&chat='.(int)$actId : '' ?>" class="btn btn-sm btn-outline-secondary" title="Reset filter">
          <i class="bi bi-x-lg"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php if ($chatStart || $chatEnd): ?>
      <div class="small text-white-50" style="font-size:10px;line-height:1.3">
        <i class="bi bi-funnel-fill me-1"></i>
        <?= $chatStart ? htmlspecialchars($chatStart) : '…' ?> — <?= $chatEnd ? htmlspecialchars($chatEnd) : '…' ?>
      </div>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>
  <!-- Filter Tabs -->
  <div class="sidebar-filter">
    <?php 
      $filterTabs = ['all'=>'Semua','active'=>'Aktif','done'=>'Selesai'];
      if (in_array($user['role'], ['admin', 'teknisi']) || true) { // Allow for all if they are participants
          $filterTabs['group'] = 'Grup';
      }
      foreach($filterTabs as $f=>$label): 
    ?>
    <a href="?filter=<?= $f ?><?= $actId ? '&chat='.$actId : '' ?><?= $user['role'] === 'admin' ? '&page=chats' : '' ?><?= $isAdminChatPage && $chatSearch !== '' ? '&q=' . urlencode($chatSearch) : '' ?><?= $preservePeriod ? $periodQs : '' ?>"
       class="filter-tab <?= $filter===$f?'active':'' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Section Title -->
  <div class="sidebar-section-title">
    <span>Daftar Permintaan</span>
    <span class="badge rounded-pill bg-dark-subtle text-muted" style="font-size: 10px;"><?= $isAdminChatPage && is_array($chatMeta) ? (int)($chatMeta['total'] ?? count($chats)) : count($chats) ?></span>
  </div>

  <!-- Chat List -->
  <div class="chat-list" id="chatList">
    <?php if (empty($chats)): ?>
    <div class="empty-list">
      <i class="bi bi-inbox"></i>
      <p>Belum ada permintaan</p>
    </div>
    <?php else: ?>
    <?php foreach ($chats as $chat): ?>
    <?php
      $isActive   = (int)$chat['id'] === (int)$actId;
      $lastMsg    = $chat['last_message'] ?? '—';
      $lastTime   = relativeTime($chat['last_msg_time'] ?? $chat['created_at']);
    ?>
    <?php $isChatPinned = !empty($chat['is_pinned']); ?>
    <div class="chat-item-wrap <?= $isActive ? 'active' : '' ?> <?= $isChatPinned ? 'chat-item-wrap--pinned' : '' ?> <?= $isAdminChatPage ? 'chat-item-wrap--admin-chats' : '' ?>" data-chat-id="<?= $chat['id'] ?>">
      <?php 
        $isGroup = ($chat['type'] ?? 'ticket') === 'group';
        $chatUrl = "?chat={$chat['id']}&filter={$filter}" . ($user['role'] === 'admin' ? '&page=chats' : '');
        if ($isAdminChatPage && $chatSearch !== '') $chatUrl .= '&q=' . urlencode($chatSearch);
        if ($preservePeriod) {
          if ($chatStart !== '') $chatUrl .= '&start_date=' . urlencode($chatStart);
          if ($chatEnd !== '') $chatUrl .= '&end_date=' . urlencode($chatEnd);
        }
      ?>
      <?php if ($isAdminChatPage): ?>
        <?php if ($isChatPinned): ?>
          <button type="button" class="btn-pin-chat btn-pin-chat--active" title="Lepas sematan"
                  onclick="event.preventDefault(); event.stopPropagation(); unpinChat(<?= (int)$chat['id'] ?>);">
            <i class="bi bi-pin-angle-fill"></i>
          </button>
        <?php else: ?>
          <button type="button" class="btn-pin-chat" title="Sematkan percakapan"
                  onclick="event.preventDefault(); event.stopPropagation(); pinChat(<?= (int)$chat['id'] ?>);">
            <i class="bi bi-pin-angle"></i>
          </button>
        <?php endif; ?>
      <?php endif; ?>
      <a href="<?= $chatUrl ?>" class="chat-item <?= $isActive ? 'active' : '' ?> <?= $isGroup ? 'chat-group-item' : '' ?> <?= $isAdminChatPage ? 'chat-item--has-pin' : '' ?>">

        <?php if ($isGroup): ?>
          <?php $groupIcon = !empty($chat['icon_path']) ? htmlspecialchars($chat['icon_path']) : ''; ?>
          <div class="chat-item-avatar group-avatar" style="background: <?= $groupIcon ? 'none' : 'linear-gradient(135deg, #4f46e5, #7c3aed)' ?>">
            <?php if ($groupIcon): ?>
              <img src="<?= $groupIcon ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
            <?php else: ?>
              <i class="bi bi-people-fill text-white"></i>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php $chatUserImg = User::getAvatarUrl($chat['user_username'], $chat['user_name'], (int)($chat['user_sil_id'] ?? 0)); ?>
          <div class="chat-item-avatar" style="background:<?= $chatUserImg ? 'none' : avatarColor($chat['user_id']) ?>">
            <?php if ($chatUserImg): ?>
              <img src="<?= $chatUserImg ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
            <?php else: ?>
              <?= htmlspecialchars($chat['user_avatar'] ?? User::getInitials($chat['user_name'] ?? '')) ?>
            <?php endif; ?>
            <?php if ($chat['teknisi_status']): ?>
            <?= techStatusDot($chat['teknisi_status']) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="chat-item-body">
          <div class="chat-item-top">
            <span class="chat-item-name"><?= htmlspecialchars($isGroup ? $chat['title'] : ($chat['user_name'] ?? '')) ?></span>
            <span class="chat-item-time"><?= $lastTime ?></span>
          </div>
          <div class="chat-item-title"><?= htmlspecialchars($isGroup ? 'Grup Percakapan' : ($chat['title'] ?? '')) ?></div>
          
          <?php if (!$isGroup && !empty($chat['group_id'])): ?>
            <div class="mt-1">
              <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle rounded-pill" style="font-size: 9px; font-weight: 800; letter-spacing: 0.3px;">
                <i class="bi bi-people-fill me-1"></i>PERMINTAAN GRUP: <?= htmlspecialchars($chat['group_title'] ?? 'Grup') ?>
              </span>
            </div>
          <?php endif; ?>

          <?php if (!$isGroup && !empty($chat['zoom_start'])): ?>
            <div class="mt-1 mb-2">
              <span class="zoom-time-badge">
                <i class="bi bi-calendar-check-fill"></i> <?= formatZoomRange($chat['zoom_start'], $chat['zoom_end']) ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="chat-item-bottom">
            <?php if ($isGroup): ?>
              <div class="d-flex flex-column gap-1">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill w-fit" style="font-size: 9px; font-weight: 800; width: fit-content;">GRUP CHAT</span>
                  <?php if (($chat['group_req_count'] ?? 0) > 0): ?>
                    <span class="badge bg-danger rounded-pill" style="font-size: 9px;"><?= $chat['group_req_count'] ?> Permintaan</span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($chat['participant_names'])): ?>
                  <div class="text-muted" style="font-size: 10px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px;">
                    <i class="bi bi-people me-1"></i>
                    <?= htmlspecialchars($chat['participant_names']) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <?= statusBadge($chat['status'], !empty($chat['is_responded'])) ?>
            <?php endif; ?>
          </div>
        </div>
      </a>

    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if ($isAdminChatPage && is_array($chatMeta) && (int)($chatMeta['total_pages'] ?? 1) > 1): ?>
    <?php
      $currentPage = (int)($chatMeta['page'] ?? 1);
      $totalPages = (int)($chatMeta['total_pages'] ?? 1);
      $prevPage = max(1, $currentPage - 1);
      $nextPage = min($totalPages, $currentPage + 1);
      $base = '?page=chats&filter=' . urlencode((string)$filter);
      if ($chatSearch !== '') $base .= '&q=' . urlencode($chatSearch);
      if ($chatStart !== '') $base .= '&start_date=' . urlencode($chatStart);
      if ($chatEnd !== '') $base .= '&end_date=' . urlencode($chatEnd);
      if ($actId) $base .= '&chat=' . (int)$actId;
    ?>
    <div class="px-3 py-2 d-flex align-items-center justify-content-between border-top">
      <a class="btn btn-sm btn-light border <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= $currentPage <= 1 ? '#' : ($base . '&page_num=' . $prevPage) ?>">Prev</a>
      <small class="text-muted">Hal <?= $currentPage ?> / <?= $totalPages ?></small>
      <a class="btn btn-sm btn-light border <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="<?= $currentPage >= $totalPages ? '#' : ($base . '&page_num=' . $nextPage) ?>">Next</a>
    </div>
  <?php endif; ?>
</aside>
