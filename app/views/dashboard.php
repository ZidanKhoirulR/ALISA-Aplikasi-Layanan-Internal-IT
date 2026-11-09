<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($f3->get('PAGE_TITLE')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="public/css/style.css?v=<?= time() ?>" rel="stylesheet"/>
  <meta name="csrf-token" content="<?= Security::generateCsrfToken($f3) ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ── Room Selection Grid (Zoom New Request) ── */
    .room-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    .room-card {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px 4px;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      cursor: pointer;
      transition: all .25s ease;
      font-size: 10px;
      font-weight: 800;
      color: #475569;
      background: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .room-card:hover { border-color: #818cf8; background: #f5f3ff; }
    .room-card.selected {
      border-color: #4f46e5;
      background: #eef2ff;
      color: #4f46e5;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── "Permintaan Baru" Button in Chat Header ── */
    .category-grid.single-mode {
      display: block !important;
    }
    .category-grid.single-mode .cat-card.selected {
      flex-direction: row !important;
      width: 100% !important;
      padding: 16px 24px !important;
      gap: 16px !important;
      justify-content: flex-start !important;
      text-align: left !important;
      background: #f8faff !important;
    }
    .category-grid.single-mode .cat-card.selected i {
      font-size: 32px !important;
    }
    .category-grid.single-mode .cat-card.selected span {
      font-size: 15px !important;
      font-weight: 700 !important;
    }
    .btn-header-new-request {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 16px;
      border-radius: 99px;
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all .25s cubic-bezier(0.4, 0, 0.2, 1);
      flex-shrink: 0;
      box-shadow: 0 4px 14px rgba(16,185,129,0.35);
      white-space: nowrap;
    }
    .btn-header-new-request:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(16,185,129,0.45);
      filter: brightness(1.08);
    }
    .btn-header-new-request:active { transform: scale(.96); }

    /* ── Zoom Modal: form field labels ── */
    .zoom-field-label {
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    /* ── Zoom Toggle Switch ── */
    .zoom-toggle-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 14px;
      padding: 14px 16px;
      cursor: pointer;
      transition: all .2s ease;
      user-select: none;
    }
    .zoom-toggle-wrap:hover { border-color: #818cf8; background: #f5f3ff; }
    .zoom-toggle-wrap.active { border-color: #4f46e5; background: #eef2ff; }
    .zoom-toggle-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; color: white; flex-shrink: 0;
    }
    .zoom-toggle-text strong { display: block; font-size: 14px; font-weight: 700; color: #0f172a; }
    .zoom-toggle-text small  { font-size: 12px; color: #64748b; }
    .form-check-input.zoom-check { width: 46px; height: 24px; cursor: pointer; margin-left: auto; flex-shrink: 0; }

    /* ── Zoom Custom DateTime Picker ── */
    .zoom-datetime-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .zoom-dt-field {
      position: relative;
      display: flex;
      align-items: center;
    }
    .zoom-dt-icon {
      position: absolute;
      left: 12px;
      color: #94a3b8;
      font-size: 15px;
      pointer-events: none;
      z-index: 2;
    }
    .zoom-dt-input {
      width: 100%;
      padding: 10px 12px 10px 36px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-family: var(--font);
      color: #1e293b;
      background: #f8fafc;
      transition: all .2s ease;
      outline: none;
      cursor: pointer;
    }
    .zoom-dt-input:focus {
      border-color: #4f46e5;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(79,70,229,.12);
    }
    /* Customize date/time input colors */
    input[type="date"].zoom-dt-input::-webkit-calendar-picker-indicator {
      opacity: 0.5;
      cursor: pointer;
    }

    /* ── Custom Modern Time Picker ── */
    .time-picker-wrapper {
      position: relative;
      width: 100%;
    }
    .time-picker-input {
      width: 100%;
      padding: 10px 12px 10px 36px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      color: #1e293b;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: left;
    }
    .time-picker-input:hover { border-color: #cbd5e1; background: #f1f5f9; }
    .time-picker-input:focus { border-color: #4f46e5; background: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
    
    /* ── Custom Modern Clock Picker (Centered Overlay) ── */
    .time-picker-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.4);
      backdrop-filter: blur(4px);
      z-index: 10000;
      display: none;
      align-items: flex-start;
      justify-content: center;
      padding-top: 70px;
      animation: fadeIn 0.2s ease;
    }
    .time-picker-overlay.show { display: flex; }
    
    .time-picker-card {
      width: 320px;
      background: white;
      border-radius: 28px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    .clock-header-bg {
      background: #4f46e5;
      padding: 24px;
      color: white;
      text-align: center;
    }
    .clock-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 12px;
      opacity: 0.8;
    }
    .clock-display {
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 0.4px;
      line-height: 1;
      opacity: 0.92;
    }
    
    .clock-body {
      padding: 30px 24px 24px;
      background: #ffffff;
    }
    .digital-time-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .digital-time-select {
      width: 100px;
      height: 56px;
      border-radius: 14px;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      color: #1e293b;
      text-align: center;
      font-size: 24px;
      font-weight: 700;
      outline: none;
      transition: all 0.2s ease;
      cursor: default;
      padding: 0 12px;
    }
    .digital-time-select:focus {
      border-color: #4f46e5;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .digital-time-sep {
      font-size: 28px;
      font-weight: 800;
      color: #4f46e5;
      margin: 0 2px;
    }
    .time-choice-panel {
      margin-top: 16px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .time-choice-col {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 8px;
    }
    .time-choice-title {
      font-size: 11px;
      text-transform: uppercase;
      font-weight: 700;
      color: #64748b;
      text-align: center;
      margin-bottom: 6px;
      letter-spacing: .5px;
    }
    .time-choice-list {
      max-height: 132px;
      overflow-y: auto;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 6px;
      padding-right: 2px;
    }
    .time-choice-list.minute-list {
      grid-template-columns: repeat(3, 1fr);
    }
    .time-choice-btn {
      border: 1px solid #dbe2ea;
      background: #fff;
      color: #334155;
      border-radius: 8px;
      height: 30px;
      font-size: 12px;
      font-weight: 700;
      transition: all .2s ease;
    }
    .time-choice-btn.active {
      background: #4f46e5;
      border-color: #4f46e5;
      color: #fff;
    }
    @media (max-height: 760px) {
      .time-picker-overlay { padding-top: 24px; }
    }
    
    .clock-footer-btns {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding: 16px 24px 24px;
      background: white;
    }
    .btn-clock-action {
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 14px;
      border: none;
      transition: all 0.2s;
    }
    .btn-clock-cancel { background: #f1f5f9; color: #64748b; }
    .btn-clock-save { background: #4f46e5; color: white; }
    .btn-clock-save:hover { background: #4338ca; box-shadow: 0 4px 12px rgba(79,70,229,0.3); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

    /* ── Global Admin Sidebar Styles ── */
    .admin-sidebar {
      width: 280px !important;
    }
    .admin-sidebar-header {
      padding: 40px 24px 20px !important;
    }
    .admin-sidebar .sidebar-brand {
      margin-bottom: 32px !important;
      padding-left: 4px;
    }
    .admin-sidebar .sidebar-user {
      margin-top: 16px !important;
      margin-bottom: 8px !important;
    }
    .admin-nav-menu {
      padding: 0 20px !important;
      margin-top: 24px !important;
    }
    .nav-section-title {
      margin-bottom: 12px !important;
      opacity: 0.5;
    }
    .admin-nav-link {
      margin-bottom: 4px !important;
      padding: 10px 16px !important;
      font-size: 13.5px !important;
    }
    .zoom-input {
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 14px;
      transition: all .25s ease;
      background: #f8fafc;
    }
    .zoom-input:focus {
      background: white; border-color: #4f46e5; box-shadow: 0 8px 24px rgba(79, 70, 229, 0.15);
    }
    .zoom-input {
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      padding: 10px 14px;
      background: #f8fafc;
      transition: all .2s;
    }
    .zoom-input:focus {
      border-color: #4f46e5;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(79,70,229,.12);
    }
    /* Room grid in zoom modal */
    .room-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    .room-card {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px 6px;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      cursor: pointer;
      transition: all .25s ease;
      font-size: 11px;
      font-weight: 800;
      color: #475569;
      background: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      gap: 4px;
    }
    .room-card i { font-size: 14px; color: #94a3b8; transition: color .2s; }
    .room-card:hover { border-color: #818cf8; background: #f5f3ff; }
    .room-card:hover i { color: #818cf8; }
    .room-card.selected {
      border-color: #4f46e5;
      background: #eef2ff;
      color: #4f46e5;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
    .room-card.selected i { color: #4f46e5; }
    /* ── Zoom Request Type Grid ── */
    .zoom-type-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }
    .zoom-type-card {
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 16px;
      padding: 16px 12px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }
    .zoom-type-card i {
      font-size: 24px;
      color: #64748b;
      transition: all 0.2s;
    }
    .zoom-type-card span {
      font-size: 11px;
      font-weight: 700;
      color: #475569;
      line-height: 1.3;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .zoom-type-card:hover {
      border-color: #818cf8;
      background: #f5f3ff;
      transform: translateY(-2px);
    }
    .zoom-type-card.active {
      border-color: #4f46e5;
      background: #eef2ff;
      box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.15);
    }
    .zoom-type-card.active i { color: #4f46e5; }
    .zoom-type-card.active span { color: #4f46e5; }

    /* ── Zoom Extra Fields (ID & Passcode) ── */
    .zoom-extra-fields {
      display: none; /* hidden by default */
      background: #f8fafc;
      border: 1.5px dashed #cbd5e1;
      border-radius: 16px;
      padding: 18px;
      margin-bottom: 20px;
      animation: slideDown 0.3s ease;
    }
    .zoom-extra-fields-title {
      font-size: 11px;
      font-weight: 800;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .zoom-extra-fields-title i { color: #4f46e5; font-size: 14px; }

    /* ── Extra Category Fields ── */
    .extra-field-group {
      display: none;
      animation: slideDown 0.3s ease;
      background: #fdfdfd;
      border: 1px solid #edf2f7;
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 15px;
    }
    .extra-field-label {
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      display: block;
    }
    .extra-field-input {
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 14px;
      background: #fff;
      transition: all 0.2s;
      width: 100%;
    }
    .extra-field-input:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 3px rgba(79,70,229,.1);
      outline: none;
    }
    /* ── Auto Logout Premium Modal ── */
    .auto-logout-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      z-index: 10500;
      display: none;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.4s ease;
    }
    .auto-logout-overlay.show { display: flex; }
    
    .auto-logout-card {
      width: 360px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
      padding: 32px 24px;
      text-align: center;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.5);
      animation: zoomIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    .auto-logout-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #ef4444, #f97316);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      color: white; font-size: 32px;
      box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
    }
    
    .auto-logout-title {
      font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px;
    }
    .auto-logout-desc {
      font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 24px;
    }
    .btn-login-kembali {
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      color: white; border: none; padding: 12px 24px;
      border-radius: 14px; font-weight: 700; font-size: 15px;
      width: 100%; transition: all 0.3s;
      box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
    }
    .btn-login-kembali:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
    }
  </style>
</head>
<?php 
  // FIX DIMULAI DARI SINI BRO!
  $isAdmin = $f3->get('currentUser')['role'] === 'admin';
  $adminPage = $f3->get('admin_page') ?: 'monitoring';
  
  // Flag mengecek mode layar penuh chat (groups & chats)
  $isAdminChatLayout = $isAdmin && ($adminPage === 'chats' || ($adminPage === 'groups' && $f3->get('GET.chat')));
  $isChatPage = $adminPage === 'chats';
  
  // bodyClass cuma dipasang saat BUKAN di layout layar penuh
  $bodyClass = $isAdmin && !$isAdminChatLayout ? ' has-history-panel' : '';
?>
<body class="dashboard-body<?= $bodyClass ?><?= $isAdminChatLayout ? ' admin-chat-full' : '' ?>">

<div class="app-shell">

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <?php if ($f3->get('currentUser')['role'] === 'admin'): ?>
    <?php if (!$isAdminChatLayout): ?>
      <?php include __DIR__ . '/components/admin_sidebar.php'; ?>
    <?php endif; ?>
    
    <div class="admin-content-area<?= $isAdminChatLayout ? ' admin-content-area--chat' : '' ?>">
      <?php 
        $page = $adminPage;
        if ($page === 'monitoring') {
            include __DIR__ . '/components/admin_panel.php';
        } elseif ($page === 'management') {
            include __DIR__ . '/components/admin_management.php';
        } elseif ($page === 'statistics') {
            include __DIR__ . '/components/admin_statistics.php';
        } elseif ($isAdminChatLayout) {
            $isGroupsContext = ($page === 'groups');
            // Re-use standard layout but inside admin content
            // We use a specific class to hide redundant headers in the dark sidebar
            echo '<div class="admin-chats-wrapper">';
            echo '<div class="admin-chats-topbar">';
            echo '<div class="admin-chats-topbar-inner">';
            echo '<a href="index.php?page=' . ($isGroupsContext ? 'groups' : 'monitoring') . '" class="btn-back-admin" title="Kembali"><i class="bi bi-arrow-left"></i></a>';
            echo '<div class="admin-chats-breadcrumb">';
            echo '<span class="breadcrumb-icon"><i class="bi ' . ($isGroupsContext ? 'bi-chat-left-text-fill' : 'bi-chat-dots-fill') . '"></i></span>';
            echo '<div>';
            echo '<div class="breadcrumb-title">' . ($isGroupsContext ? 'Grup Chat' : 'Percakapan') . '</div>';
            echo '<div class="breadcrumb-sub">' . ($isGroupsContext ? 'Percakapan grup koordinasi internal tim IT' : 'Kelola dan monitor permintaan layanan masuk secara real-time') . '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="admin-chats-layout">';
            include __DIR__ . '/components/sidebar.php';
            include __DIR__ . '/components/chat_panel.php';
            echo '</div>';
            echo '</div>';
        } elseif ($page === 'history') {
            include __DIR__ . '/components/admin_history.php';
        } elseif ($page === 'history_zoom') {
            include __DIR__ . '/components/admin_history_zoom.php';
        } elseif ($page === 'groups') {
            include __DIR__ . '/components/admin_groups.php';
        } elseif ($page === 'categories') {
            include __DIR__ . '/components/admin_categories.php';
        }

      ?>
    </div>
  <?php else: ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <?php include __DIR__ . '/components/chat_panel.php'; ?>
  <?php endif; ?>

</div>

<div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap">
          <i class="bi bi-plus-circle-fill"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text" id="modalTitle">Buat Permintaan Baru</h5>
        <p class="modal-desc text-muted small">Jelaskan kendala atau kebutuhan IT Anda</p>

        <div id="requestFormError" class="alert alert-danger d-none py-2 small"></div>

        <div class="mb-3">
          <label class="form-label fw-semibold small d-flex justify-content-between align-items-center">
            <span>Kategori Layanan</span>
            <a href="javascript:void(0)" class="text-primary fw-bold text-decoration-none" id="btnChangeCategory" style="display:none; font-size: 11px;" onclick="showAllCategories()">Ganti</a>
          </label>
          <div class="category-grid" id="categoryGrid">
            <?php foreach ($f3->get('categories') as $cat): ?>
            <div class="cat-card" data-cat="<?= htmlspecialchars($cat['code']) ?>"
                 onclick="selectCategory(this, '<?= htmlspecialchars($cat['code']) ?>')">
              <?php if ($cat['custom_icon_path']): ?>
                <img src="<?= htmlspecialchars($cat['custom_icon_path']) ?>" alt="icon" style="width:24px; height:24px; object-fit:contain; margin-bottom:12px;">
              <?php else: ?>
                <i class="bi <?= htmlspecialchars($cat['default_icon_class']) ?>"></i>
              <?php endif; ?>
              <span><?= htmlspecialchars($cat['name']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3 d-none">
          <label class="form-label fw-semibold small">Nama User <span class="text-danger">*</span></label>
          <input type="text" id="requestUserName" class="form-control" 
                 value="<?= htmlspecialchars($f3->get('currentUser')['name']) ?>" placeholder="Nama lengkap Anda"/>
        </div>

        <div id="extraFieldsWrapper">
          <?php foreach ($f3->get('categoryFields') ?? [] as $code => $fields): ?>
          <div class="extra-field-group" data-for="<?= htmlspecialchars($code) ?>" style="display:none;">
            <?php foreach ($fields as $idx => $f): ?>
            <div class="<?= $idx === count($fields)-1 ? '' : 'mb-2' ?>">
              <label class="extra-field-label text-uppercase">
                <?= htmlspecialchars($f['field_label']) ?> 
                <?php if ($f['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
              </label>
              
              <?php if ($f['field_type'] === 'select'): ?>
                <select class="extra-field-input dynamic-extra" 
                        data-key="<?= htmlspecialchars($f['field_key']) ?>"
                        data-label="<?= htmlspecialchars($f['field_label']) ?>"
                        <?= $f['is_required'] ? 'required' : '' ?>>
                  <option value="">-- Pilih --</option>
                  <?php 
                  $opts = json_decode($f['field_options'] ?? '[]');
                  if (is_array($opts)) {
                      foreach ($opts as $opt) {
                          echo '<option value="'.htmlspecialchars($opt).'">'.htmlspecialchars($opt).'</option>';
                      }
                  }
                  ?>
                </select>
              <?php elseif ($f['field_type'] === 'textarea'): ?>
                <textarea class="extra-field-input dynamic-extra" 
                          data-key="<?= htmlspecialchars($f['field_key']) ?>"
                          data-label="<?= htmlspecialchars($f['field_label']) ?>"
                          rows="2"
                          <?= $f['is_required'] ? 'required' : '' ?>></textarea>
              <?php else: ?>
                <input type="text" class="extra-field-input dynamic-extra" 
                       data-key="<?= htmlspecialchars($f['field_key']) ?>"
                       data-label="<?= htmlspecialchars($f['field_label']) ?>"
                       <?= $f['is_required'] ? 'required' : '' ?> />
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <input type="hidden" id="selectedCategory" value="Umum"/>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary px-5" id="submitRequestBtn" onclick="submitNewRequest()">
          <i class="bi bi-send me-2"></i>Kirim
        </button>
      </div>
    </div>
  </div>
</div>

<?php 
  $activeChat = $f3->get('activeChat');
  $showFab = ($f3->get('currentUser')['role'] === 'user') && (!$activeChat || ($activeChat['type'] ?? 'ticket') !== 'group');
  if ($showFab): 
?>
<button class="fab" onclick="openNewRequestModal()" title="Permintaan Baru">
  <i class="bi bi-headset"></i>
</button>
<?php endif; ?>

<?php 
  $groupCats = $f3->get('groupCategories') ?? [];
  if ($f3->get('currentUser')['role'] === 'user' && !empty($groupCats)):
?>
<div class="modal fade" id="groupRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
          <i class="bi bi-ticket-detailed-fill"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text">Buat Permintaan Grup</h5>
        <p class="modal-desc text-muted small">Pilih kategori layanan yang tersedia untuk grup ini</p>

        <div id="groupRequestFormError" class="alert alert-danger d-none py-2 small mb-3"></div>

        <div class="mb-3">
          <label class="form-label fw-semibold small d-flex justify-content-between align-items-center">
            <span>Kategori Layanan</span>
            <a href="javascript:void(0)" class="text-primary fw-bold text-decoration-none" id="btnChangeGroupCategory" style="display:none; font-size:11px;" onclick="showAllGroupCategories()">Ganti</a>
          </label>
          <div class="category-grid" id="groupCategoryGrid">
            <?php foreach ($groupCats as $cat): ?>
            <div class="cat-card" data-cat="<?= htmlspecialchars($cat['code']) ?>"
                 onclick="selectGroupCategory(this, '<?= htmlspecialchars($cat['code']) ?>')">
              <?php if ($cat['custom_icon_path']): ?>
                <img src="<?= htmlspecialchars($cat['custom_icon_path']) ?>" alt="icon" style="width:24px;height:24px;object-fit:contain;margin-bottom:12px;">
              <?php else: ?>
                <i class="bi <?= htmlspecialchars($cat['default_icon_class']) ?>"></i>
              <?php endif; ?>
              <span><?= htmlspecialchars($cat['name']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3 d-none">
          <label class="form-label fw-semibold small">Nama User <span class="text-danger">*</span></label>
          <input type="text" id="groupReqUserName" class="form-control"
                 value="<?= htmlspecialchars($f3->get('currentUser')['name']) ?>" placeholder="Nama lengkap Anda"/>
        </div>

        <div id="groupExtraFieldsWrapper">
          <?php 
          $groupCatCodes = array_column($groupCats, 'code');
          foreach ($f3->get('categoryFields') ?? [] as $code => $fields):
            if (!in_array($code, $groupCatCodes)) continue;
          ?>
          <div class="extra-field-group" data-for-group="<?= htmlspecialchars($code) ?>" style="display:none;">
            <?php foreach ($fields as $idx => $fieldItem): ?>
            <div class="<?= $idx === count($fields)-1 ? '' : 'mb-2' ?>">
              <label class="extra-field-label text-uppercase">
                <?= htmlspecialchars($fieldItem['field_label']) ?>
                <?php if ($fieldItem['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
              </label>
              <?php if ($fieldItem['field_type'] === 'select'): ?>
                <select class="extra-field-input group-dynamic-extra"
                        data-key="<?= htmlspecialchars($fieldItem['field_key']) ?>"
                        data-label="<?= htmlspecialchars($fieldItem['field_label']) ?>"
                        <?= $fieldItem['is_required'] ? 'required' : '' ?>>
                  <option value="">-- Pilih --</option>
                  <?php 
                  $opts = json_decode($fieldItem['field_options'] ?? '[]');
                  if (is_array($opts)) foreach ($opts as $opt) echo '<option value="'.htmlspecialchars($opt).'">'.htmlspecialchars($opt).'</option>';
                  ?>
                </select>
              <?php elseif ($fieldItem['field_type'] === 'textarea'): ?>
                <textarea class="extra-field-input group-dynamic-extra"
                          data-key="<?= htmlspecialchars($fieldItem['field_key']) ?>"
                          data-label="<?= htmlspecialchars($fieldItem['field_label']) ?>"
                          rows="2"
                          <?= $fieldItem['is_required'] ? 'required' : '' ?>></textarea>
              <?php else: ?>
                <input type="text" class="extra-field-input group-dynamic-extra"
                       data-key="<?= htmlspecialchars($fieldItem['field_key']) ?>"
                       data-label="<?= htmlspecialchars($fieldItem['field_label']) ?>"
                       <?= $fieldItem['is_required'] ? 'required' : '' ?> />
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <input type="hidden" id="selectedGroupCategory" value=""/>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary px-5" id="submitGroupRequestBtn" onclick="submitGroupRequest()">
          <i class="bi bi-send me-2"></i>Kirim
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($f3->get('currentUser')['role'] === 'user'): ?>
<div class="modal fade" id="zoomRequestModal" tabindex="-1" aria-labelledby="zoomModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #2563eb, #4f46e5);">
          <i class="bi bi-camera-video-fill"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text" id="zoomModalTitle">Formulir Permintaan Zoom</h5>
        <p class="modal-desc text-muted small">Lengkapi detail jadwal Zoom Meeting Anda</p>

        <div id="zoomFormError" class="alert alert-danger py-2 small mb-3" style="display: none;"></div>

        <div class="mb-4">
          <label class="zoom-field-label">Tipe Permintaan Zoom</label>
          <div class="zoom-type-grid">
            <div class="zoom-type-card active" onclick="setZoomRequestType(this, 'link_tech')">
              <i class="bi bi-display"></i>
              <span>Link + Ruangan</span>
            </div>
            <div class="zoom-type-card" onclick="setZoomRequestType(this, 'link_only')">
              <i class="bi bi-link-45deg"></i>
              <span>Hanya Link Zoom</span>
            </div>
            <div class="zoom-type-card" onclick="setZoomRequestType(this, 'own_link')">
              <i class="bi bi-person-video3"></i>
              <span>Ruangan</span>
            </div>
          </div>
          <input type="hidden" id="zoomRequestType" value="link_tech">
        </div>

        <div class="mb-3">
          <label class="zoom-field-label" for="zoomNamaKegiatan">Nama Kegiatan <span class="text-danger">*</span></label>
          <input type="text" class="form-control zoom-input" id="zoomNamaKegiatan"
                 placeholder="Cth: Rapat Koordinasi Bulanan"/>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="zoom-field-label">Waktu Mulai <span class="text-danger">*</span></label>
            <div class="zoom-datetime-group">
              <div class="zoom-dt-field">
                <i class="bi bi-calendar3 zoom-dt-icon"></i>
                <input type="date" class="zoom-dt-input" id="zoomTanggalMulai"/>
              </div>
              <div class="zoom-dt-field">
                <i class="bi bi-clock zoom-dt-icon"></i>
                <input type="text" class="zoom-dt-input time-picker-input" id="zoomJamMulai" readonly placeholder="08:00" value="08:00"/>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <label class="zoom-field-label">Waktu Selesai <span class="text-danger">*</span></label>
            <div class="zoom-datetime-group">
              <div class="zoom-dt-field">
                <i class="bi bi-calendar3 zoom-dt-icon"></i>
                <input type="date" class="zoom-dt-input" id="zoomTanggalSelesai"/>
              </div>
              <div class="zoom-dt-field">
                <i class="bi bi-clock zoom-dt-icon"></i>
                <input type="text" class="zoom-dt-input time-picker-input" id="zoomJamSelesai" readonly placeholder="16:00" value="16:00"/>
              </div>
            </div>
          </div>
        </div>

        <div id="zoomExtraFields" class="zoom-extra-fields">
          <div class="zoom-extra-fields-title">
            <i class="bi bi-shield-lock-fill"></i>
            Detail Meeting Anda
          </div>
          <div class="row g-2">
            <div class="col-md-7">
              <label class="zoom-field-label" style="font-size: 10px;">ID Meeting / Link Zoom</label>
              <input type="text" class="form-control zoom-input" id="zoomOwnId" placeholder="Cth: 821 1234 5678">
            </div>
            <div class="col-md-5">
              <label class="zoom-field-label" style="font-size: 10px;">Passcode</label>
              <input type="text" class="form-control zoom-input" id="zoomOwnPass" placeholder="Cth: 123456">
            </div>
          </div>
        </div>

        <div class="mb-3" id="zoomRoomSelectionSection">
          <label class="zoom-field-label">Pilih Ruangan <span class="text-danger">*</span></label>
          <p class="text-muted" style="font-size:11px; margin-top:-2px; margin-bottom:10px;">
            <i class="bi bi-info-circle me-1"></i>Pilih ruangan yang akan disiapkan oleh Tim IT
          </p>
          <div class="room-grid" id="zoomRoomGrid">
            <div class="room-card" onclick="zoomSelectRoom(this, 'RAYON')">RAYON</div>
            <div class="room-card" onclick="zoomSelectRoom(this, 'SELULOSA')">SELULOSA</div>
            <div class="room-card" onclick="zoomSelectRoom(this, 'KERTAS')">KERTAS</div>
            <div class="room-card" onclick="zoomSelectRoom(this, 'PINUS')">PINUS</div>
          </div>
          <input type="hidden" id="zoomSelectedRoom" value=""/>
        </div>

      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary px-5" id="submitZoomBtn" onclick="submitZoomRequest()">
          <i class="bi bi-send me-2"></i>Kirim Permintaan
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>


<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
    <div class="modal-content modal-modern border-0">
      <div class="modal-body p-4 text-center">
        <div class="confirm-icon-wrap mb-3 mx-auto" id="confirmIconWrap">
          <i class="bi bi-question-circle" id="confirmIcon"></i>
        </div>
        <h5 class="fw-800 mb-2" id="confirmTitle">Konfirmasi</h5>
        <p class="text-muted small mb-0" id="confirmMessage">Apakah Anda yakin ingin melanjutkan?</p>
      </div>
      <div class="modal-footer border-0 p-3 pt-0">
        <button type="button" class="btn btn-light flex-grow-1" data-bs-dismiss="modal" style="border-radius:12px; font-weight:600;">Batal</button>
        <button type="button" class="btn btn-primary flex-grow-1" id="confirmBtnYes" style="border-radius:12px; font-weight:700;">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999; pointer-events:none;">
  <div id="appToast" class="toast align-items-center text-white border-0" role="alert" style="pointer-events:auto;">
    <div class="d-flex">
      <div class="toast-body" id="toastBody">Pesan berhasil dikirim</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php if ($f3->get('currentUser')['role'] === 'admin'): ?>
<div class="modal fade" id="addTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header border-0 pb-0">
                <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #4f46e5, #06b6d4);">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <h5 class="modal-title-text">Tambah Tim IT (SIL2020)</h5>
                <p class="modal-desc text-muted small">Cari user dari database SIL2020 untuk dijadikan Tim IT</p>
                <div class="search-box-modern mb-4">
                    <i class="bi bi-search"></i>
                    <input type="text" id="silUserSearch" class="form-control form-control-lg" placeholder="Ketik nama atau username..." autocomplete="off">
                </div>
                <div id="silSearchResults" class="sil-search-results">
                    <div class="text-center py-5 text-muted small">
                        <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                        Ketik nama/username untuk mencari...
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-light px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <form id="formTechCrud" onsubmit="submitTechCrud(event)">
                <div class="modal-header border-0 pb-0">
                    <div class="modal-icon-wrap" id="crudModalIcon" style="background: linear-gradient(135deg, #059669, #10b981);">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <h5 class="modal-title-text" id="crudModalTitle">Buat Akun Teknisi</h5>
                    <p class="modal-desc text-muted small">Isi data di bawah untuk menambahkan atau mengubah akun teknisi lokal.</p>
                    <input type="hidden" id="crudTechId" name="id" value="">
                    <div class="mb-3">
                        <label class="form-label text-uppercase text-muted small fw-bold">Nama Lengkap</label>
                        <input type="text" class="form-control" id="crudName" name="name" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase text-muted small fw-bold">Username</label>
                        <input type="text" class="form-control" id="crudUsername" name="username" required placeholder="Contoh: budi.s">
                        <div class="form-text small">Digunakan untuk login akun baru.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase text-muted small fw-bold">Role Akses</label>
                        <select class="form-select" id="crudRole" name="role">
                            <option value="teknisi">Teknisi (Biasa)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div id="crudError" class="alert alert-danger d-none py-2 fs-7 mt-3 mb-0"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4" id="crudSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="chatHistoryModal" tabindex="-1" aria-labelledby="chatHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <div class="modal-icon-wrap" style="background:linear-gradient(135deg,#059669,#0891b2)">
            <i class="bi bi-chat-square-text-fill"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0" id="chatHistoryModalLabel">Detail Percakapan</h5>
            <div class="text-muted small" id="historyModalSubtitle"></div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3" id="historyMessageContainer">
        <div class="text-center py-5 text-muted" id="historyLoadingSpinner">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Memuat riwayat percakapan…
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-modern">
      <form id="formChangePassword" onsubmit="submitChangePassword(event)">
        <div class="modal-header border-0 pb-0">
          <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pt-2">
          <h5 class="modal-title-text">Ganti Password</h5>
          <p class="modal-desc text-muted small">Pastikan Anda menggunakan password yang kuat dan unik.</p>
          
          <div id="changePasswordError" class="alert alert-danger d-none py-2 fs-7 mb-3"></div>
          <div id="changePasswordSuccess" class="alert alert-success d-none py-2 fs-7 mb-3">
            Password berhasil diubah!
          </div>

          <div class="mb-3">
            <label class="form-label text-uppercase text-muted small fw-bold">Password Lama</label>
            <input type="password" class="form-control" name="old_password" required placeholder="••••••••">
          </div>
          <div class="mb-3">
            <label class="form-label text-uppercase text-muted small fw-bold">Password Baru</label>
            <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="••••••••">
          </div>
          <div class="mb-3">
            <label class="form-label text-uppercase text-muted small fw-bold">Konfirmasi Password Baru</label>
            <input type="password" class="form-control" name="confirm_password" required minlength="6" placeholder="••••••••">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary px-4" id="btnSubmitChangePassword">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
  // Inject PHP data into JS
  <?php
    $activeChatForApp = $f3->get('activeChat');
    $groupParticipantsForApp = [];
    if ($activeChatForApp && ($activeChatForApp['type'] ?? '') === 'group') {
      foreach ($activeChatForApp['participants'] ?? [] as $gp) {
        $groupParticipantsForApp[] = [
          'id'         => (int)($gp['id'] ?? 0),
          'name'       => $gp['name'] ?? '',
          'username'   => $gp['username'] ?? '',
          'avatar_url' => $gp['avatar_url'] ?? User::getAvatarUrl($gp['username'] ?? '', $gp['name'] ?? '', (int)($gp['sil_id'] ?? 0)),
        ];
      }
    }
  ?>
  const APP = {
    currentUser:  <?= json_encode($f3->get('currentUser')) ?>,
    activeChatId: <?= (int)$f3->get('activeChatId') ?>,
    activeChatType: '<?= $f3->get('activeChat')['type'] ?? 'ticket' ?>',
    filter:       '<?= htmlspecialchars($f3->get('filter')) ?>',
    adminPage:    '<?= htmlspecialchars($f3->get('admin_page') ?? '', ENT_QUOTES) ?>',
    groupParticipants: <?= json_encode($groupParticipantsForApp, JSON_UNESCAPED_UNICODE) ?>,
    lastMsgId:    <?= empty($f3->get('messages')) ? 0 : (int)end($f3->get('messages'))['id'] ?>,
    csrfToken:    '<?= Security::generateCsrfToken($f3) ?>',
  };
  <?php if ($f3->get('access_denied_msg')): ?>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof showToast === 'function') {
      showToast(<?= json_encode($f3->get('access_denied_msg'), JSON_UNESCAPED_UNICODE) ?>, 'danger');
    }
  });
  <?php endif; ?>
</script>
 <script src="public/js/app.js?v=<?= time() ?>"></script>

<?php if ($f3->get('currentUser')['role'] === 'user'): ?>
<script src="public/js/zoom_modal.js?v=<?= time() ?>"></script>
<?php endif; ?>

<?php if ($f3->get('currentUser')['role'] === 'admin'): ?>
<script>
// ════════════════════════════════════════════
//  Admin: Chat History Modal — jQuery logic
// ════════════════════════════════════════════
$(function () {

  // ── Open modal: fetch messages via AJAX ──────────────────────
  $('#chatHistoryModal').on('show.bs.modal', function (e) {
    var btn       = $(e.relatedTarget);
    var chatId    = btn.data('chat-id');
    var chatTitle = btn.data('chat-title');
    var pemohon   = btn.data('pemohon');

    // Update modal subtitle
    $('#chatHistoryModalLabel').text('Detail Percakapan');
    $('#historyModalSubtitle').html(
      '<i class="bi bi-person me-1"></i>' + $('<span>').text(pemohon).html() +
      ' &nbsp;·&nbsp; <i class="bi bi-tag me-1"></i>' + $('<span>').text(chatTitle).html()
    );

    // Show loading state
    var $container = $('#historyMessageContainer');
    $container.html(
      '<div class="text-center py-5 text-muted" id="historyLoadingSpinner">' +
        '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
        'Memuat riwayat percakapan…' +
      '</div>'
    );

    // AJAX: hit the existing endpoint
    $.ajax({
      url:      'api/messages/' + chatId,
      method:   'GET',
      success: function (res) {
        var messages = res.messages || [];
        if (messages.length === 0) {
          $container.html(
            '<div class="text-center py-5 text-muted">' +
              '<i class="bi bi-chat-slash fs-1 d-block mb-2"></i>Belum ada pesan.' +
            '</div>'
          );
          return;
        }

        var html = '<div class="history-messages-wrap">';
        messages.forEach(function (msg) {
          var time  = histFormatTime(msg.created_at);
          var name  = escapeHtml(msg.sender_name || '?');
          var text  = escapeHtml(msg.message || '').replace(/\n/g, '<br>');
          var color = histAvatarColor(parseInt(msg.sender_id));
          var isTek = (msg.sender_role === 'teknisi' || msg.sender_role === 'admin');

          // Detect system messages
          var sysStarters = ['✅', '🔧', '⚠️', 'ℹ', '[SYSTEM_CARD]'];
          var starter = sysStarters.find(s => msg.message && msg.message.trim().startsWith(s));
          var isSystem = !!starter;
          
          let isFileMsg = msg.message && msg.message.trim().startsWith('📎 [FILE]');

          if (isSystem && !isFileMsg) {
            var textWithoutEmoji = msg.message.trim().substring(starter.length).trim();
            text = escapeHtml(textWithoutEmoji).replace(/\n/g, '<br>');
            html += '<div class="msg-system"><i class="bi bi-info-circle me-2"></i>' + text + '</div>';
          } else {
            var avatarUrl = msg.sender_avatar_url;
            var initials  = histGetInitials(msg.sender_name);
            var avatar    = escapeHtml(msg.sender_avatar || initials);
            var avColor   = avatarUrl ? 'none' : color;
            
            let msgBodyHtml = text;
            if (isFileMsg) {
              const content = msg.message.trim().substring(9).trim();
              const parts = content.split('|');
              const name = parts[0] ? parts[0].trim() : 'File';
              const fileUrl = parts[1] ? parts[1].trim() : '#';
              const ext = name.split('.').pop().toLowerCase();
              
              if (['jpg', 'jpeg', 'png'].includes(ext)) {
                msgBodyHtml = `<div class="mb-1"><a href="${fileUrl}" target="_blank"><img src="${fileUrl}" style="max-width: 100%; max-height: 250px; border-radius: 6px; object-fit: contain;"></a></div>`;
              } else {
                let iconClass = 'bi-file-earmark-text-fill text-secondary';
                if (ext === 'pdf') iconClass = 'bi-file-earmark-pdf-fill text-danger';
                else if (['xls', 'xlsx'].includes(ext)) iconClass = 'bi-file-earmark-excel-fill text-success';
                else if (['doc', 'docx'].includes(ext)) iconClass = 'bi-file-earmark-word-fill text-primary';
                
                msgBodyHtml = `
                  <div class="d-flex align-items-center gap-2 p-2 mb-1 bg-white rounded shadow-sm border" style="min-width: 220px; max-width: 100%;">
                    <div style="font-size: 28px; line-height: 1;"><i class="bi ${iconClass}"></i></div>
                    <div class="flex-grow-1 overflow-hidden" style="max-width: 180px;">
                      <div class="text-truncate fw-semibold text-dark" style="font-size: 13px;">${escapeHtml(name)}</div>
                      <div class="text-muted" style="font-size: 11px;">${ext.toUpperCase()}</div>
                    </div>
                    <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-light rounded-circle flex-shrink-0" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f0f2f5; border: none;"><i class="bi bi-download text-dark"></i></a>
                  </div>`;
              }
            }
            
            html +=
              '<div class="msg-row msg-left">' +
                '<div class="msg-sender-avatar" style="background:' + avColor + '">' + 
                  (avatarUrl ? '<img src="' + avatarUrl + '" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">' : avatar) + 
                '</div>' +
                '<div class="msg-content">' +
                  '<div class="msg-sender-name">' + name +
                    (isTek ? ' <span class="tek-tag">Tim IT</span>' : '') +
                  '</div>' +
                  '<div class="msg-bubble bubble-other history-bubble">' +
                    (isFileMsg ? msgBodyHtml : '<p>' + msgBodyHtml + '</p>') +
                    '<div class="msg-time">' + time + '</div>' +
                  '</div>' +
                '</div>' +
              '</div>';
          }
        });

        html += '</div>';
        $container.html(html);
      },
      error: function () {
        $container.html(
          '<div class="alert alert-danger m-3">' +
            '<i class="bi bi-exclamation-triangle me-2"></i>' +
            'Gagal memuat riwayat percakapan.' +
          '</div>'
        );
      }
    });
  });

  // ── Utility helpers (scoped, avoid conflicts with app.js) ────
  function histFormatTime(dt) {
    var d = new Date(dt);
    var hh = String(d.getHours()).padStart(2, '0');
    var mm = String(d.getMinutes()).padStart(2, '0');
    return hh + ':' + mm;
  }

  function histGetInitials(name) {
    if (!name) return '?';
    var parts = name.trim().split(' ');
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
  }

  function histAvatarColor(id) {
    var colors = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
    return colors[id % colors.length];
  }

});
</script>
<?php endif; ?>
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; height: 90vh;">
            <div class="modal-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="admin-panel-icon" style="background: linear-gradient(135deg, #ef4444, #b91c1c); width: 38px; height: 38px; font-size: 18px;">
                        <i class="bi bi-file-pdf"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Preview Laporan PDF</h5>
                        <p class="text-muted small mb-0">Tinjau kembali data sebelum diunduh</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm" style="border-radius: 10px; font-weight: 600;" id="btnDownloadPdf">
                        <i class="bi bi-download"></i> Unduh PDF
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe id="pdfPreviewFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chatListModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap" id="chatListModalIcon" style="background: linear-gradient(135deg, #4f46e5, #0891b2);">
          <i class="bi bi-list-task"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text" id="chatListModalTitle">Daftar Permintaan</h5>
        <p class="modal-desc text-muted small" id="chatListModalDesc">Menampilkan permintaan berdasarkan status terpilih</p>
        
        <div id="chatListModalContent" class="mt-3">
          </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="categoryDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #4f46e5, #0891b2);">
          <i class="bi bi-bar-chart-fill"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text" id="catDetailTitle">Analisis Kategori</h5>
        <p class="modal-desc text-muted small" id="catDetailDesc">Daftar karyawan yang mengajukan permintaan ini</p>
        
        <div id="catDetailContent" class="mt-3">
          </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="time-picker-overlay" id="globalTimePicker">
  <div class="time-picker-card">
    <div class="clock-header-bg">
      <div class="clock-title">Pilih Waktu</div>
      <div class="clock-display">Format 24 Jam</div>
    </div>
    <div class="clock-body">
      <div class="digital-time-row">
        <input id="globalHourSelect" class="digital-time-select" aria-label="Pilih jam" readonly />
        <span class="digital-time-sep">:</span>
        <input id="globalMinuteSelect" class="digital-time-select" aria-label="Pilih menit" readonly />
      </div>
      <div class="time-choice-panel">
        <div class="time-choice-col">
          <div class="time-choice-title">Jam</div>
          <div id="hourChoiceList" class="time-choice-list"></div>
        </div>
        <div class="time-choice-col">
          <div class="time-choice-title">Menit</div>
          <div id="minuteChoiceList" class="time-choice-list minute-list"></div>
        </div>
      </div>
    </div>
    <div class="clock-footer-btns">
      <button type="button" class="btn-clock-action btn-clock-cancel" onclick="closeGlobalTimePicker()">Batal</button>
      <button type="button" class="btn-clock-action btn-clock-save" onclick="saveGlobalTimePicker()">OK</button>
    </div>
  </div>
</div>

<div class="auto-logout-overlay" id="autoLogoutModal">
  <div class="auto-logout-card">
    <div class="auto-logout-icon">
      <i class="bi bi-clock-history"></i>
    </div>
    <div class="auto-logout-title">Sesi Berakhir</div>
    <div class="auto-logout-desc">Anda telah keluar otomatis karena tidak ada aktivitas selama 10 menit untuk menjaga keamanan.</div>
    <button class="btn-login-kembali" type="button" onclick="window.location.replace('login?expired=1')">Login Kembali</button>
  </div>
</div>

</body>
</html>