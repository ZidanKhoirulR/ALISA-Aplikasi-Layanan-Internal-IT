<?php
// ============================================
// app/views/components/admin_management.php
// Variables: $tech_list
// ============================================
$tech_list = $f3->get('tech_list') ?? [];
?>

<div class="admin-main-dashboard p-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-sidebar-toggle d-lg-none" onclick="openAdminSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h4 class="fw-bold text-dark mb-1">Manajemen Tim IT</h4>
                <p class="text-muted small mb-0">Kelola akun teknisi untuk melayani permintaan pengguna.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createManualModal">
                <i class="bi bi-pencil-square me-2"></i>Buat Akun Manual
            </button>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                <i class="bi bi-cloud-download me-2"></i>Impor dari SIL2020
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Nama Lengkap</th>
                            <th class="py-3">Username</th>
                            <th class="py-3">Role</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tech_list)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada tim IT yang terdaftar.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tech_list as $tech): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php $techImg = User::getAvatarUrl($tech['username'], $tech['name'], (int)($tech['sil_id'] ?? 0)); ?>
                                        <div class="avatar-sm flex-shrink-0" style="background:<?= $techImg ? 'none' : avatarColor($tech['id']) ?>">
                                            <?php if ($techImg): ?>
                                                <img src="<?= $techImg ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                                            <?php else: ?>
                                                <?= htmlspecialchars($tech['avatar'] ?? User::getInitials($tech['name'] ?? '')) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($tech['name'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td class="py-3 text-muted">@<?= htmlspecialchars($tech['username'] ?? '') ?></td>
                                <td class="py-3">
                                    <span class="badge <?= $tech['role'] === 'admin' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' ?> rounded-pill px-3 py-1">
                                        <?= ucfirst($tech['role']) ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="status-text <?= $tech['status'] ?>"><i class="bi bi-circle-fill small me-1"></i><?= ucfirst($tech['status']) ?></span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <button class="btn btn-sm btn-light text-primary rounded-3 me-2 btn-edit-tech" 
                                            data-id="<?= $tech['id'] ?>" 
                                            data-name="<?= htmlspecialchars($tech['name'] ?? '') ?>" 
                                            data-username="<?= htmlspecialchars($tech['username'] ?? '') ?>" 
                                            data-role="<?= $tech['role'] ?? '' ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger rounded-3 btn-delete-tech" 
                                            data-id="<?= $tech['id'] ?>" 
                                            data-name="<?= htmlspecialchars($tech['name'] ?? '') ?>"
                                            <?= $tech['id'] == $f3->get('currentUser')['id'] ? 'disabled title="Tidak bisa menghapus akun sendiri"' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

