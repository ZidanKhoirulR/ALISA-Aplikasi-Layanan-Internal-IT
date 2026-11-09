<?php
// ============================================
// app/views/components/admin_statistics.php
// Variables: $f3, $chat_stats
// ============================================
$stats = $f3->get('chat_stats') ?? [
    'waiting' => 0,
    'in_progress' => 0,
    'done' => 0,
    'categories' => [],
    'performance' => [],
    'avg_by_category' => []
];
$stats['avg_by_category'] = $stats['avg_by_category'] ?? [];
$total = $stats['waiting'] + $stats['in_progress'] + $stats['done'];
$chats = $f3->get('chats') ?? [];
$activeChats = array_filter($chats, function($c) {
    return in_array($c['status'] ?? '', ['waiting', 'in_progress'], true)
        && ($c['type'] ?? 'ticket') === 'ticket';
});
$chatNotifCount = count($activeChats);
$groupNotifCount = Chat::countGroupChatNotifications();
$combinedNotifCount = $chatNotifCount + $groupNotifCount;
?>

<div class="admin-main-dashboard">
    <div class="admin-panel-header">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-sidebar-toggle d-lg-none" onclick="openAdminSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="admin-header-icon">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-900 admin-title-gradient">Statistik Layanan</h4>
                <p class="text-muted small mb-0">Visualisasi data dan analisis performa permintaan IT</p>
            </div>
        </div>
        
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
            <!-- Period Filter -->
            <div class="d-flex align-items-center gap-2 px-3 bg-white border rounded-4 shadow-sm me-1" style="height: 40px;">
                <i class="bi bi-funnel-fill text-primary small"></i>
                <span class="text-muted fw-bold me-1" style="font-size: 10px;">PERIODE:</span>
                <input type="date" id="statsStart" class="border-0 bg-transparent" value="<?= $f3->get('stats_start') ?>" onchange="updatePeriod()" style="outline:none; font-size:11px; width:115px;">
                <span class="text-muted small">&mdash;</span>
                <input type="date" id="statsEnd" class="border-0 bg-transparent" value="<?= $f3->get('stats_end') ?>" onchange="updatePeriod()" style="outline:none; font-size:11px; width:115px;">
            </div>

            <div class="stats-date-badge shadow-sm d-none d-md-flex" style="background: white; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; color: #475569;">
                <i class="bi bi-calendar3 me-2 text-primary"></i>
                <?= date('d M Y') ?>
            </div>
        </div>
    </div>

    <div class="admin-panel-body">
        <!-- Dashboard Content -->
        <div class="row g-4 mb-4">
        <!-- Belum Dikerjakan -->
        <div class="col-md-3">
            <div class="stat-white-card border-warning-subtle" style="cursor: pointer;" onclick="showStatusDetail('waiting')">
                <div class="stat-icon-box bg-warning-subtle text-warning shadow-sm">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-lbl text-muted fw-bold">BELUM DIKERJAKAN</div>
                    <h2 class="stat-val mb-0 fw-900"><?= $stats['waiting'] ?></h2>
                </div>
            </div>
        </div>

        <!-- Sedang Berjalan -->
        <div class="col-md-3">
            <div class="stat-white-card border-primary-subtle" style="cursor: pointer;" onclick="showStatusDetail('in_progress')">
                <div class="stat-icon-box bg-primary-subtle text-primary shadow-sm">
                    <i class="bi bi-gear-fill spin-slow"></i>
                </div>
                <div>
                    <div class="stat-lbl text-muted fw-bold">SEDANG BERJALAN</div>
                    <h2 class="stat-val mb-0 fw-900"><?= $stats['in_progress'] ?></h2>
                </div>
            </div>
        </div>

        <!-- Sudah Selesai -->
        <div class="col-md-3">
            <div class="stat-white-card border-success-subtle" style="cursor: pointer;" onclick="showStatusDetail('done')">
                <div class="stat-icon-box bg-success-subtle text-success shadow-sm">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-lbl text-muted fw-bold">SUDAH SELESAI</div>
                    <h2 class="stat-val mb-0 fw-900"><?= $stats['done'] ?></h2>
                </div>
            </div>
        </div>

        <!-- Notifikasi Percakapan + Grup -->
        <div class="col-md-3">
            <div class="stat-white-card border-danger-subtle" style="cursor: pointer;" onclick="showNotificationDetail()">
                <div class="stat-icon-box bg-danger-subtle text-danger shadow-sm">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <div>
                    <div class="stat-lbl text-muted fw-bold">NOTIF PERCAKAPAN</div>
                    <h2 class="stat-val mb-0 fw-900"><?= $combinedNotifCount ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis -->
    <div class="row g-4 mb-5">
        <!-- Category Analysis -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-primary-subtle p-2 text-primary">
                            <i class="bi bi-tag-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-800 mb-0">Analisis Kategori</h6>
                            <span class="text-muted" style="font-size: 10px; font-weight: 600; letter-spacing: 0.3px;">BELUM DIKERJAKAN</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php 
                        $waitingCategories = array_filter($stats['categories'], function($c) { return $c['cnt'] > 0; });
                        $totalWaiting = array_sum(array_column($waitingCategories, 'cnt'));
                    ?>
                    <?php if (empty($waitingCategories)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success display-4"></i>
                            <p class="text-muted small mt-2">Semua permintaan sudah dikerjakan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($waitingCategories as $cat): 
                            $percent = $totalWaiting > 0 ? ($cat['cnt'] / $totalWaiting * 100) : 0;
                        ?>
                        <div class="category-stat-item mb-3 p-2 rounded-3" 
                             style="cursor: pointer; transition: background 0.2s;" 
                             onclick="showCategoryDetail('<?= addslashes($cat['category']) ?>')">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-dark"><?= htmlspecialchars($cat['category']) ?></span>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-bold"><?= $cat['cnt'] ?></span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 5px; background: #f1f5f9;">
                                <div class="progress-bar bg-warning" style="width: <?= $percent ?>%; border-radius: 5px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Technician Leaderboard -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-success-subtle p-2 text-success">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <h6 class="fw-800 mb-0">Diselesaikan</h6>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($stats['performance'])): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-person-slash text-muted display-4"></i>
                            <p class="text-muted small mt-2">Belum ada penyelesaian permintaan</p>
                        </div>
                    <?php else: ?>
                        <div class="leaderboard-list">
                            <?php foreach ($stats['performance'] as $p): ?>
                            <div class="leader-item d-flex align-items-center gap-3 p-3 rounded-4 border mb-2 bg-light-subtle" style="cursor: pointer;" onclick="showTechnicianDetail(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                <div class="avatar-md flex-shrink-0" style="background:<?= avatarColor($p['id']) ?>; width: 44px; height: 44px; border-radius: 12px; display:flex; align-items:center; justify-content:center; color:white; font-weight:700;">
                                    <?php 
                                        $avatar = User::getAvatarUrl($p['username'], $p['name'], (int)($p['sil_id'] ?? 0));
                                        if ($avatar): 
                                    ?>
                                        <img src="<?= $avatar ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:12px;">
                                    <?php else: ?>
                                        <?= User::getInitials($p['name']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-muted" style="font-size: 11px;"><?= ucfirst($p['role']) ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-800 text-primary h5 mb-0"><?= $p['completed_count'] ?></div>
                                    <div class="text-muted" style="font-size: 10px;">SELESAI</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Chart row -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 py-4 px-4 pb-0">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 bg-indigo-subtle p-2 text-indigo">
                                <i class="bi bi-bar-chart-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-800 mb-0">Semua Permintaan per Kategori</h6>
                                <span class="text-muted" style="font-size: 10px; font-weight: 600;">SELURUH STATUS · <?= date('d M Y', strtotime($f3->get('stats_start'))) ?> – <?= date('d M Y', strtotime($f3->get('stats_end'))) ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size: 10px; font-weight: 700;">
                            <span class="d-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#10b981;display:inline-block;"></span> Selesai</span>
                            <span class="d-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#3b82f6;display:inline-block;"></span> Proses</span>
                            <span class="d-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#f59e0b;display:inline-block;"></span> Menunggu</span>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <?php
                        $catAll = $stats['categories_all'] ?? [];
                        // Only show categories that have at least 1 request
                        $catAllActive = array_filter($catAll, function($c) { return $c['total'] > 0; });
                    ?>
                    <?php if (empty($catAllActive)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:28px;opacity:.3;"></i>
                            <span style="font-size:12px;">Belum ada data pada periode ini</span>
                        </div>
                    <?php else: ?>
                        <div style="position: relative; height: <?= max(160, count($catAllActive) * 44) ?>px;">
                            <canvas id="categoryStackedChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .leader-item {
        transition: all 0.2s ease;
        border: 1.5px solid #cbd5e1 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .leader-item:hover {
        transform: scale(1.02);
        background: white !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: var(--primary) !important;
    }
    .bg-light-subtle { background: #f8fafc; }
    
    .hover-white-card {
        border: 1.5px solid #cbd5e1 !important;
        transition: all 0.2s ease;
    }
    .hover-white-card:hover {
        transform: translateY(-2px);
        background: white !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: var(--primary) !important;
    }
    
    .category-stat-item:hover {
        background: #f1f5f9 !important;
    }

    .stat-white-card {
        background: white;
        border: 1.5px solid #cbd5e1;
        border-radius: 20px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    }
    .stat-white-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .stat-icon-box {
        width: 60px; height: 60px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; flex-shrink: 0;
    }
    .stat-lbl { font-size: 11px; letter-spacing: 0.5px; margin-bottom: 4px; }
    .stat-val { font-size: 32px; color: #0f172a; }

    .stats-date-badge {
        padding: 8px 16px; border-radius: 99px; background: #fff;
        border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #475569;
    }

    .spin-slow { animation: spin 4s linear infinite; }
    .fw-800 { font-weight: 800; }
    .fw-900 { font-weight: 900; }

    /* Circular Chart */
    .stat-circle-wrap { position: relative; width: 120px; height: 120px; }
    .circular-chart { display: block; margin: 0 auto; width: 100%; }
    .circle-bg { fill: none; stroke: #f1f5f9; stroke-width: 3.8; }
    .circle { fill: none; stroke: #10b981; stroke-width: 3.8; stroke-linecap: round; animation: progress 1s ease-out forwards; }
    .circle-percentage {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        font-size: 20px; font-weight: 800; color: #0f172a;
    }
    @keyframes progress { 0% { stroke-dasharray: 0 100; } }

    .legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
    .stat-legend-item { transition: var(--transition); border: 1px solid transparent; }
    .stat-legend-item:hover { background: white !important; border-color: #e2e8f0; transform: translateX(5px); }
    .bg-indigo-subtle { background: #e0e7ff; }
    .text-indigo { color: #4338ca; }
</style>

<script>
function initStatisticsCharts() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js belum termuat, chart statistik tidak dapat ditampilkan.');
        return;
    }

    // ── Category Stacked Bar Chart (all requests per category) ──
    const catCtx = document.getElementById('categoryStackedChart');
    if (catCtx) {
        const catData = <?= json_encode(array_values(array_filter($stats['categories_all'] ?? [], function($c) { return $c['total'] > 0; }))) ?>;
        const labels      = catData.map(c => c.category);
        const doneData    = catData.map(c => c.done);
        const progressData = catData.map(c => c.in_progress);
        const waitingData = catData.map(c => c.waiting);

        new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Selesai',
                        data: doneData,
                        backgroundColor: 'rgba(16,185,129,0.85)',
                        borderRadius: { topLeft: 0, topRight: 6, bottomLeft: 0, bottomRight: 6 },
                        borderSkipped: false,
                        barThickness: 22
                    },
                    {
                        label: 'Proses',
                        data: progressData,
                        backgroundColor: 'rgba(59,130,246,0.85)',
                        borderRadius: 0,
                        borderSkipped: false,
                        barThickness: 22
                    },
                    {
                        label: 'Menunggu',
                        data: waitingData,
                        backgroundColor: 'rgba(245,158,11,0.85)',
                        borderRadius: { topLeft: 6, topRight: 0, bottomLeft: 6, bottomRight: 0 },
                        borderSkipped: false,
                        barThickness: 22
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.dataset.label + ': ' + ctx.parsed.x + ' permintaan';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { stepSize: 1, font: { size: 11 } },
                        border: { display: false }
                    },
                    y: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '700' } },
                        border: { display: false }
                    }
                },
                animation: { duration: 1500, easing: 'easeOutQuart' }
            }
        });
    }

    // ── Time Pie Chart removed ──
}

function ensureChartJsAndInit() {
    if (typeof Chart !== 'undefined') {
        initStatisticsCharts();
        return;
    }

    const existingLoader = document.getElementById('chartjs-fallback-loader');
    if (existingLoader) {
        existingLoader.addEventListener('load', initStatisticsCharts, { once: true });
        return;
    }

    const script = document.createElement('script');
    script.id = 'chartjs-fallback-loader';
    script.src = 'https://unpkg.com/chart.js@4.4.3/dist/chart.umd.min.js';
    script.onload = initStatisticsCharts;
    script.onerror = function() {
        console.error('Gagal memuat fallback Chart.js dari unpkg.');
    };
    document.head.appendChild(script);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureChartJsAndInit);
} else {
    ensureChartJsAndInit();
}

function showCategoryDetail(category) {
    $('#catDetailTitle').text(category);
    $('#catDetailContent').html('<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>');
    showBootstrapModal('categoryDetailModal');
    
    const start = $('#statsStart').val();
    const end = $('#statsEnd').val();
    
    $.get('api/admin/statistics/category-detail', { 
        category: category,
        start: start,
        end: end
    }, function(res) {
        if (!res || res.length === 0) {
            $('#catDetailContent').html('<div class="text-center py-4 text-muted">Belum ada data karyawan untuk kategori ini pada periode terpilih.</div>');
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        res.forEach(item => {
            let initials = typeof getInitials === 'function' ? getInitials(item.name) : (item.name[0] || '?').toUpperCase();
            let avatarHtml = item.avatar_url 
                ? `<img src="${item.avatar_url}" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">`
                : initials;
            let bgStyle = item.avatar_url ? 'none' : avatarColor(item.id);

            html += `
                <div class="list-group-item border-0 px-0 d-flex align-items-center gap-3 mb-2">
                    <div class="avatar-sm flex-shrink-0" style="background:${bgStyle}; width: 38px; height: 38px; border-radius: 10px; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size: 12px; overflow:hidden;">
                        ${avatarHtml}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark small">${item.name}</div>
                        <div class="text-muted" style="font-size: 11px;">${item.username || '-'} &middot; ${item.role.toUpperCase()}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-800 text-primary">${item.cnt}</div>
                        <div class="text-muted" style="font-size: 10px;">PERMINTAAN</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#catDetailContent').html(html);
    }).fail(function(xhr) {
        let msg = 'Gagal memuat data.';
        if (xhr.status === 404) msg = 'API tidak ditemukan (404).';
        if (xhr.status === 500) msg = 'Terjadi kesalahan internal server (500).';
        $('#catDetailContent').html(`<div class="alert alert-danger small py-2"><i class="bi bi-exclamation-triangle me-2"></i>${msg}</div>`);
    });
}

function showBootstrapModal(id) {
    const el = document.getElementById(id);
    if (el) {
        let instance = bootstrap.Modal.getInstance(el);
        if (!instance) {
            instance = new bootstrap.Modal(el);
        }
        instance.show();
    }
}

// Ensure avatarColor is available in JS for the modal
function avatarColor(id) {
    const colors = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
    if (isNaN(id)) {
        // Simple hash for string IDs
        let hash = 0;
        for (let i = 0; i < id.length; i++) hash = id.charCodeAt(i) + ((hash << 5) - hash);
        return colors[Math.abs(hash) % colors.length];
    }
    return colors[parseInt(id) % colors.length];
}

let currentModalData = [];
let modalPageSize = 5;
let modalCurrentPage = 1;
let modalNotifType = 'all';

function escapeHtml(string) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(string).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function isGroupItem(item) {
    if (!item) return false;
    if (item.notif_type === 'group' || item.is_group_chat === true || item.is_group_chat === 1) return true;
    return (item.type || '').toLowerCase() === 'group';
}

function getGroupDisplayName(item) {
    if (!item) return 'Grup Chat';
    const name = (item.group_title || item.title || '').trim();
    return name !== '' ? name : 'Grup Chat';
}

function truncateText(text, maxLen) {
    const s = String(text || '').replace(/\[SYSTEM_CARD\]/g, '').trim();
    if (s.length <= maxLen) return s;
    return s.substring(0, maxLen - 3) + '...';
}

function getNotifOpenUrl(item) {
    if (isGroupItem(item)) {
        return `?page=groups&chat=${item.id}`;
    }
    return `?page=chats&chat=${item.id}`;
}

function renderModalData() {
    const allData = currentModalData || [];
    const countAll = allData.length;
    const countChat = allData.filter(item => !isGroupItem(item)).length;
    const countGroup = allData.filter(item => isGroupItem(item)).length;

    let filteredData = currentModalData || [];
    if (modalNotifType === 'chat') {
        filteredData = filteredData.filter(item => !isGroupItem(item));
    } else if (modalNotifType === 'group') {
        filteredData = filteredData.filter(item => isGroupItem(item));
    }

    const totalItems = filteredData.length;
    const totalPages = totalItems > 0 ? Math.ceil(totalItems / modalPageSize) : 1;

    if (modalCurrentPage < 1) modalCurrentPage = 1;
    if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;

    const startIndex = totalItems > 0 ? (modalCurrentPage - 1) * modalPageSize : 0;
    const endIndex = Math.min(startIndex + modalPageSize, totalItems);
    const pageData = filteredData.slice(startIndex, endIndex);
    const rangeLabel = totalItems > 0
        ? `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalItems} permintaan`
        : `Menampilkan 0 dari ${countAll} permintaan`;

    let html = `
        <div class="d-flex justify-content-between align-items-center mb-2 mt-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small fw-bold" style="font-size: 10px; letter-spacing: 0.3px;">FILTER:</span>
                <button class="btn btn-sm rounded-pill px-3 fw-bold ${modalNotifType === 'all' ? 'btn-primary' : 'btn-light'}" onclick="setNotifTypeFilter('all')" style="font-size: 11px;">Semua (${countAll})</button>
                <button class="btn btn-sm rounded-pill px-3 fw-bold ${modalNotifType === 'chat' ? 'btn-primary' : 'btn-light'}" onclick="setNotifTypeFilter('chat')" style="font-size: 11px;">Percakapan (${countChat})</button>
                <button class="btn btn-sm rounded-pill px-3 fw-bold ${modalNotifType === 'group' ? 'btn-primary' : 'btn-light'}" onclick="setNotifTypeFilter('group')" style="font-size: 11px;">Grup (${countGroup})</button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 mt-1 flex-wrap gap-2 pb-2 border-bottom border-light">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold" style="font-size: 10px; letter-spacing: 0.3px;">TAMPILKAN:</span>
                <select class="form-select form-select-sm border rounded-pill px-3 shadow-sm" id="modalPageSizeSelect" style="width: 80px; font-size: 11px; font-weight: 700; height: 30px; cursor: pointer;">
                    <option value="5" ${modalPageSize === 5 ? 'selected' : ''}>5</option>
                    <option value="10" ${modalPageSize === 10 ? 'selected' : ''}>10</option>
                    <option value="25" ${modalPageSize === 25 ? 'selected' : ''}>25</option>
                    <option value="50" ${modalPageSize === 50 ? 'selected' : ''}>50</option>
                </select>
            </div>
            <div class="text-muted small fw-bold" style="font-size: 11px;">
                ${rangeLabel}
            </div>
        </div>
    `;

    if (totalItems === 0) {
        const emptyMsg = modalNotifType === 'group'
            ? 'Tidak ada percakapan grup yang perlu ditanggapi.'
            : (modalNotifType === 'chat' ? 'Tidak ada tiket layanan aktif.' : 'Tidak ada notifikasi.');
        html += `<div class="text-center py-5 text-muted">${emptyMsg}</div>`;
    }

    html += '<div class="list-group list-group-flush mb-4">';
    pageData.forEach(item => {
        const isGroup = isGroupItem(item);
        const dateSrc = item.last_msg_time || item.created_at;
        let dateFormatted = '';
        if (dateSrc) {
            dateFormatted = new Date(dateSrc).toLocaleDateString('id-ID', { 
                day: '2-digit', 
                month: 'short', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        const groupName = getGroupDisplayName(item);
        let mainTitle = isGroup
            ? groupName
            : escapeHtml(item.title || 'Permintaan');
        let preview = '';
        if (isGroup && item.last_message) {
            preview = truncateText(item.last_message, 80);
        }

        let groupBadge = '';
        if (isGroup) {
            const unread = parseInt(item.unread_count, 10) || 0;
            groupBadge = `
                <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle rounded-pill px-2 py-1" style="font-size: 9px; font-weight: 800; letter-spacing: 0.3px;">
                    <i class="bi bi-people-fill me-1"></i>GRUP: ${escapeHtml(groupName)}
                </span>
                ${unread > 0 ? `<span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 9px; font-weight: 800;">${unread} pesan</span>` : ''}
            `;
        }

        let catBadge = '';
        if (!isGroup && item.category) {
            catBadge = `
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" style="font-size: 9px; font-weight: 800;">
                    ${escapeHtml(item.category)}
                </span>
            `;
        }

        const displayUser = isGroup
            ? (item.last_sender_name || item.user_name || 'Peserta')
            : (item.user_name || '');

        html += `
            <div class="list-group-item border-0 px-0 d-flex align-items-center gap-3 mb-2 p-3 bg-light-subtle rounded-4 transition-all hover-white-card">
                <div class="flex-grow-1">
                    <div class="fw-800 text-dark mb-1 d-flex align-items-center flex-wrap gap-1">
                        <span>${isGroup ? escapeHtml(mainTitle) : mainTitle}</span>
                        ${groupBadge}
                    </div>
                    ${preview ? `<div class="text-muted small mb-1" style="font-size: 11px;"><i class="bi bi-chat-left-text me-1"></i>${escapeHtml(preview)}</div>` : ''}
                    <div class="text-muted d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                        <i class="bi bi-person text-muted"></i> <strong>${escapeHtml(displayUser)}</strong>
                        <span>&middot;</span>
                        <i class="bi bi-calendar3"></i> ${dateFormatted}
                        ${catBadge ? '<span>&middot;</span>' + catBadge : ''}
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <a href="${getNotifOpenUrl(item)}" class="btn btn-primary btn-sm px-3 rounded-pill fw-bold d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 11px; transition: all 0.2s;">
                        BUKA <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        `;
    });
    html += '</div>';

    if (totalPages > 1) {
        html += `
            <div class="d-flex justify-content-center align-items-center gap-2 mt-3 pt-3 border-top border-light flex-wrap">
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold" onclick="changeModalPage(${modalCurrentPage - 1})" ${modalCurrentPage === 1 ? 'disabled' : ''} style="font-size: 11px;">
                    <i class="bi bi-chevron-left me-1"></i> Prev
                </button>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= modalCurrentPage - 1 && i <= modalCurrentPage + 1)) {
                html += `
                    <button class="btn ${i === modalCurrentPage ? 'btn-primary' : 'btn-light'} btn-sm rounded-circle fw-bold" onclick="changeModalPage(${i})" style="width: 32px; height: 32px; font-size: 11px; padding: 0;">
                        ${i}
                    </button>
                `;
            } else if (i === 2 || i === totalPages - 1) {
                html += `<span class="text-muted small px-1">...</span>`;
            }
        }

        html += `
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold" onclick="changeModalPage(${modalCurrentPage + 1})" ${modalCurrentPage === totalPages ? 'disabled' : ''} style="font-size: 11px;">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        `;
    }

    $('#chatListModalContent').html(html);

    $('#modalPageSizeSelect').on('change', function () {
        modalPageSize = parseInt($(this).val());
        modalCurrentPage = 1;
        renderModalData();
    });
}

function changeModalPage(page) {
    modalCurrentPage = page;
    renderModalData();
}

function setNotifTypeFilter(type) {
    modalNotifType = type;
    modalCurrentPage = 1;
    renderModalData();
}

function showNotificationDetail() {
    $('#chatListModalTitle').text('Notifikasi Percakapan & Grup');
    $('#chatListModalIcon').css('background', 'linear-gradient(135deg, #ef4444, #dc2626)');
    $('#chatListModalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    showBootstrapModal('chatListModal');

    $.get('api/chats/notifications', function(res) {
        currentModalData = Array.isArray(res) ? res : [];
        modalNotifType = 'all';
        modalCurrentPage = 1;
        renderModalData();
    }).fail(function(xhr) {
        let msg = 'Gagal memuat data notifikasi.';
        if (xhr.status === 404) msg = 'API tidak ditemukan (404).';
        if (xhr.status === 500) msg = 'Terjadi kesalahan internal server (500).';
        $('#chatListModalContent').html(`<div class="alert alert-danger m-3 py-3 text-center"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>${msg}</div>`);
    });
}

function showStatusDetail(status) {
    const titles = {
        'waiting': 'Belum Dikerjakan',
        'in_progress': 'Sedang Berjalan',
        'done': 'Sudah Selesai'
    };
    const icons = {
        'waiting': 'linear-gradient(135deg, #f59e0b, #d97706)',
        'in_progress': 'linear-gradient(135deg, #3b82f6, #2563eb)',
        'done': 'linear-gradient(135deg, #10b981, #059669)'
    };

    $('#chatListModalTitle').text(titles[status] || 'Daftar Permintaan');
    $('#chatListModalIcon').css('background', icons[status] || '');
    $('#chatListModalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    showBootstrapModal('chatListModal');

    $.get('api/chats', { filter: status }, function(res) {
        currentModalData = res || [];
        modalNotifType = 'all';
        modalCurrentPage = 1;
        renderModalData();
    }).fail(function(xhr) {
        let msg = 'Gagal memuat data.';
        if (xhr.status === 404) msg = 'API tidak ditemukan (404). Pastikan konfigurasi server sudah benar.';
        if (xhr.status === 500) msg = 'Terjadi kesalahan internal server (500).';
        $('#chatListModalContent').html(`<div class="alert alert-danger m-3 py-3 text-center"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>${msg}</div>`);
    });
}

function showTechnicianDetail(techId, techName) {
    const start = $('#statsStart').val();
    const end = $('#statsEnd').val();

    $('#chatListModalTitle').text('Permintaan Ditangani: ' + techName);
    $('#chatListModalIcon').css('background', 'linear-gradient(135deg, #6366f1, #a855f7)');
    $('#chatListModalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    showBootstrapModal('chatListModal');

    $.get('api/admin/statistics/technician-detail', { 
        tech_id: techId,
        start: start,
        end: end
    }, function(res) {
        currentModalData = res || [];
        modalCurrentPage = 1;
        renderModalData();
    }).fail(function(xhr) {
        let msg = 'Gagal memuat data.';
        if (xhr.status === 404) msg = 'API tidak ditemukan (404).';
        if (xhr.status === 500) msg = 'Terjadi kesalahan internal server (500).';
        $('#chatListModalContent').html(`<div class="alert alert-danger m-3 py-3 text-center"><i class="bi bi-exclamation-triangle me-2"></i>${msg}</div>`);
    });
}

function formatDurationJS(seconds) {
    if (seconds <= 0) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    let res = [];
    if (h > 0) res.push(h + ' jam');
    if (m > 0 || res.length === 0) res.push(m + ' mnt');
    return res.join(' ');
}
function updatePeriod() {
    const start = $('#statsStart').val();
    const end = $('#statsEnd').val();
    if (start && end) {
        window.location.href = `?page=statistics&start=${start}&end=${end}`;
    }
}
</script>
</div> <!-- Close admin-main-dashboard -->
