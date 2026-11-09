<?php
// ============================================
// app/views/components/admin_sidebar.php
// Variables: $f3, $currentUser
// ============================================

$user = $f3->get('currentUser');
$page = $f3->get('admin_page') ?: 'monitoring';
?>

<aside class="admin-sidebar" id="adminSidebar">
  <!-- Sidebar Header -->
  <div class="admin-sidebar-header">
    <div class="sidebar-brand">
      <div class="sidebar-icon" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      <div>
        <div class="sidebar-title">Admin Panel</div>
        <div class="sidebar-subtitle">Aplikasi Layanan Internal IT Selulosa</div>
      </div>
    </div>

    <!-- User Profile Admin -->
    <div class="sidebar-user border-secondary-subtle" id="userMenuToggle">
      <?php $adminImg = User::getAvatarUrl($user['username'] ?? null, $user['name'] ?? null, (int)($user['sil_id'] ?? 0)); ?>
      <div class="avatar-sm" style="background:<?= $adminImg ? 'none' : avatarColor($user['id']) ?>">
        <?php if ($adminImg): ?>
          <img src="<?= $adminImg ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
        <?php else: ?>
          <?= htmlspecialchars($user['avatar'] ?? User::getInitials($user['name'] ?? '')) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
        <div class="sidebar-user-role"><?= ucfirst($user['role']) ?></div>
      </div>
      <i class="bi bi-chevron-down ms-auto small text-muted"></i>
    </div>

    <!-- Admin User Dropdown -->
    <div class="sidebar-user-menu" id="userDropdown" style="display:none">
      <div class="status-toggle-label">Status Admin</div>
      <div class="status-buttons">
        <button class="status-btn active" disabled>
          <span class="tech-dot dot-available"></span> Online
        </button>
      </div>
      <hr class="sidebar-divider"/>
      <form method="POST" action="logout" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken($f3)) ?>">
        <button type="submit" class="sidebar-menu-item text-danger w-100 text-start border-0 bg-transparent">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </form>
    </div>
  </div>

  <!-- Admin Navigation -->
  <div class="admin-nav-menu mt-3 px-3">
    <div class="nav-section-title">DASHBOARD UTAMA</div>
    
    <a href="?page=statistics" class="admin-nav-link <?= $page === 'statistics' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-fill"></i>
      <span>Statistik Layanan</span>
    </a>

    <a href="?page=monitoring" class="admin-nav-link <?= $page === 'monitoring' ? 'active' : '' ?>">
      <i class="bi bi-activity"></i>
      <span>Monitoring Tim IT</span>
    </a>
    
    <a href="?page=management" class="admin-nav-link <?= $page === 'management' ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i>
      <span>Manajemen Tim</span>
    </a>

    <div class="nav-section-title mt-4">MASTER DATA</div>
    
    <a href="?page=categories" class="admin-nav-link <?= $page === 'categories' ? 'active' : '' ?>">
      <i class="bi bi-tags-fill"></i>
      <span>Kategori Layanan</span>
    </a>

    <div class="nav-section-title mt-4">LAYANAN & PERMINTAAN</div>
    
    <a href="?page=chats" class="admin-nav-link <?= $page === 'chats' ? 'active' : '' ?>">
      <i class="bi bi-chat-dots-fill"></i>
      <span>Percakapan</span>
      <?php 
        $activeCount = count(array_filter($f3->get('chats') ?? [], function($c) {
            return in_array($c['status'], ['waiting','in_progress']);
        }));
        if ($activeCount > 0): 
      ?>
      <span class="badge bg-danger rounded-pill ms-auto"><?= $activeCount ?></span>
      <?php endif; ?>
    </a>
    
    <a href="?page=history" class="admin-nav-link <?= $page === 'history' ? 'active' : '' ?>">
      <i class="bi bi-clock-history"></i>
      <span>Riwayat Layanan</span>
    </a>

    <a href="?page=groups" class="admin-nav-link <?= $page === 'groups' ? 'active' : '' ?>">
      <i class="bi bi-chat-left-text-fill"></i>
      <span>Grup Chat</span>
    </a>

    <a href="?page=history_zoom" class="admin-nav-link <?= $page === 'history_zoom' ? 'active' : '' ?>">
      <i class="bi bi-camera-video"></i>
      <span>Riwayat Zoom</span>
    </a>

    <a href="calendar" class="admin-nav-link <?= ($page === 'calendar' || $f3->get('admin_page') === 'calendar') ? 'active' : '' ?>">
      <i class="bi bi-calendar-event"></i>
      <span>Kalender Zoom</span>
    </a>
  </div>

</aside>
