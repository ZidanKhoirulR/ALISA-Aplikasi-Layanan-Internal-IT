<?php
// ============================================
// app/views/components/admin_panel.php
// Variables: $tech_list
// ============================================
$tech_list = $f3->get('tech_list') ?? [];
?>

<div class="admin-main-dashboard">
    <div class="admin-panel-header">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-sidebar-toggle d-lg-none" onclick="openAdminSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="admin-header-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-900 admin-title-gradient">Dashboard Admin</h4>
                <p class="text-muted small mb-0">Monitoring & Manajemen Tim IT</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
            <!-- Period Filter -->
            <div class="d-flex align-items-center gap-2 px-3 bg-white border rounded-4 shadow-sm me-1" style="height: 40px;">
                <i class="bi bi-funnel-fill text-primary small"></i>
                <span class="text-muted fw-bold me-1" style="font-size: 10px;">PERIODE:</span>
                <input type="date" id="monStart" class="border-0 bg-transparent" value="<?= $f3->get('stats_start') ?>" onchange="updateMonPeriod()" style="outline:none; font-size:11px; width:115px;">
                <span class="text-muted small">&mdash;</span>
                <input type="date" id="monEnd" class="border-0 bg-transparent" value="<?= $f3->get('stats_end') ?>" onchange="updateMonPeriod()" style="outline:none; font-size:11px; width:115px;">
            </div>

            <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" style="height: 40px; font-size: 13px; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#createManualModal" title="Buat Akun Manual">
                <i class="bi bi-pencil-square me-2"></i>Manual
            </button>
            <button class="btn btn-primary rounded-pill px-3 shadow-sm" style="height: 40px; font-size: 13px; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                <i class="bi bi-person-plus-fill me-2"></i>Impor SIL2020
            </button>
        </div>
    </div>

    <div class="admin-panel-body">
        <!-- Section: Quick Stats -->
        <?php $quickStats = $f3->get('chat_stats') ?? ['waiting' => 0, 'in_progress' => 0, 'done' => 0]; ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 d-flex align-items-center gap-3" style="border: 1.5px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.06);">
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 20px;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">BELUM DIKERJAKAN</div>
                        <h4 class="mb-0 fw-800"><?= $quickStats['waiting'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 d-flex align-items-center gap-3" style="border: 1.5px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.06);">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 20px;">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">SEDANG BERJALAN</div>
                        <h4 class="mb-0 fw-800"><?= $quickStats['in_progress'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 20px;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">SUDAH SELESAI</div>
                        <h4 class="mb-0 fw-800"><?= $quickStats['done'] ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Monitoring -->
        <section class="admin-section mb-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="section-title mb-0">
                    <i class="bi bi-activity me-2 text-primary"></i>Status Tim IT Saat Ini
                </h5>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fs-6">
                    Total: <?= count($tech_list) ?> Personel
                </span>
            </div>

            <div class="row g-4">
                <?php foreach ($tech_list as $tech): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="tech-monitor-card h-100">
                        <div class="tech-info">
                            <div class="tech-avatar-wrap">
                                <?php $techImg = User::getAvatarUrl($tech['username'], $tech['name'], (int)($tech['sil_id'] ?? 0)); ?>
                                <div class="avatar-sm" style="background:<?= $techImg ? 'none' : avatarColor($tech['id']) ?>; width: 48px; height: 48px; font-size: 18px;">
                                    <?php if ($techImg): ?>
                                        <img src="<?= $techImg ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                                    <?php else: ?>
                                        <?= htmlspecialchars($tech['avatar'] ?? User::getInitials($tech['name'])) ?>
                                    <?php endif; ?>
                                </div>
                                <?= techStatusDot($tech['status']) ?>
                            </div>
                            <div class="tech-details">
                                <div class="tech-name fs-6"><?= htmlspecialchars($tech['name'] ?? '') ?></div>
                                <div class="tech-role-status mt-1">
                                    <span class="role-tag"><?= ucfirst($tech['role']) ?></span>
                                    <span class="status-text <?= $tech['status'] ?> ms-2"><?= ucfirst($tech['status']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tech-active-task mt-3">
                            <?php if ($tech['active_chat']): ?>
                                <div class="task-info">
                                    <div class="task-label">Sedang Menangani:</div>
                                    <div class="task-title" title="<?= htmlspecialchars($tech['active_chat']['title'] ?? '') ?>">
                                        <i class="bi bi-chat-dots me-1 text-primary"></i>
                                        <?= htmlspecialchars($tech['active_chat']['title'] ?? '') ?>
                                    </div>
                                    <div class="task-meta-row">
                                        <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.65rem;"><?= htmlspecialchars($tech['active_chat']['category'] ?? '') ?></span>
                                        <?php if (!empty($tech['active_chat']['room'])): ?>
                                            <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">
                                                <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($tech['active_chat']['room'] ?? '') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-user text-muted d-flex align-items-center gap-2">
                                        <?php $userImg = User::getAvatarUrl($tech['active_chat']['user_username'] ?? null, $tech['active_chat']['user_name'] ?? null, (int)($tech['active_chat']['user_sil_id'] ?? 0)); ?>
                                        <div class="avatar-xs flex-shrink-0" style="background:<?= $userImg ? 'none' : avatarColor(99) ?>; width: 20px; height: 20px; font-size: 9px;">
                                            <?php if ($userImg): ?>
                                                <img src="<?= $userImg ?>" alt="User" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                                            <?php else: ?>
                                                <?= User::getInitials($tech['active_chat']['user_name'] ?? '') ?>
                                            <?php endif; ?>
                                        </div>
                                        <span>Pemohon: <?= htmlspecialchars($tech['active_chat']['user_name'] ?? '') ?></span>
                                    </div>
                                </div>
                                <a href="?page=chats&chat=<?= $tech['active_chat']['id'] ?>" class="btn-view-task ms-auto">
                                    <i class="bi bi-box-arrow-in-up-right"></i>
                                </a>
                            <?php else: ?>
                                <div class="task-empty w-100 text-center py-2">
                                    <i class="bi bi-cup-hot me-1 text-success"></i> Tidak ada permintaan aktif (Standby)
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>


<script>
function updateMonPeriod() {
    const start = $('#monStart').val();
    const end = $('#monEnd').val();
    if (start && end) {
        window.location.href = `?page=monitoring&start=${start}&end=${end}`;
    }
}
</script>
