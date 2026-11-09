<?php
// ============================================
// app/views/components/admin_history.php
// Menampilkan daftar riwayat permintaan yang sudah selesai
// ============================================

$hChats = $f3->get('history_chats') ?? [];

if (!function_exists('histGenGetInitials')) {
    function histGenGetInitials($name) {
        if (!$name) return '?';
        $p = explode(' ', trim($name));
        return strtoupper(($p[0][0] ?? '') . ($p[1][0] ?? ''));
    }
}
?>

<div class="admin-main-dashboard">
    <!-- Header Section -->
    <div class="admin-panel-header">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-sidebar-toggle d-lg-none" onclick="openAdminSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="admin-header-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-900 admin-title-gradient">Riwayat Layanan</h4>
                <p class="text-muted small mb-0">Daftar seluruh permintaan layanan yang telah selesai</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
            <!-- Period Filter -->
            <div class="d-flex align-items-center gap-2 px-3 bg-white border rounded-4 shadow-sm me-1" style="height: 40px;">
                <i class="bi bi-funnel-fill text-primary small"></i>
                <span class="text-muted fw-bold me-1" style="font-size: 10px;">PERIODE:</span>
                <input type="date" id="histStart" class="border-0 bg-transparent" value="<?= $f3->get('stats_start') ?>" onchange="updateHistoryPeriod()" style="outline:none; font-size:11px; width:115px;">
                <span class="text-muted small">&mdash;</span>
                <input type="date" id="histEnd" class="border-0 bg-transparent" value="<?= $f3->get('stats_end') ?>" onchange="updateHistoryPeriod()" style="outline:none; font-size:11px; width:115px;">
            </div>

            <div class="input-group shadow-sm" style="width: 200px;">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; height: 40px;"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="historyTableSearch" class="form-control border-start-0" placeholder="Cari..." style="border-radius: 0 12px 12px 0; font-size: 13px; height: 40px;">
            </div>
            <button class="btn btn-primary d-flex align-items-center gap-2 px-3 shadow-sm" style="border-radius: 12px; font-size: 13px; font-weight: 600; height: 40px;" id="btnExportHistory" onclick="exportGeneralHistoryToPDF()">
                <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
            </button>
        </div>
    </div>

    <div class="admin-panel-body">
        <!-- Data Card -->
        <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="historyGeneralTable">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th class="text-secondary fw-bold py-3 ps-4" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">WAKTU (MULAI – SELESAI)</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">PEMOHON</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">TIM IT</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">KATEGORI</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">RUANGAN</th>
                                <th class="text-secondary fw-bold py-3 pe-4 text-end" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php if (empty($hChats)): ?>
                                <tr id="emptyRow">
                                    <td colspan="6" class="py-5 text-center text-muted">
                                        <div class="py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                            <h6 class="fw-bold text-dark">Belum Ada Riwayat Selesai</h6>
                                            <p class="small mb-0">Semua layanan yang selesai akan tampil di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($hChats as $hc): ?>
                                <tr class="history-row">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 13px;">
                                            <i class="bi bi-calendar-check me-1 text-success"></i><?= date('d M Y H:i', strtotime($hc['created_at'])) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Selesai: <?= $hc['closed_at'] ? date('d M Y H:i', strtotime($hc['closed_at'])) : '—' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php $pemohonImg = User::getAvatarUrl($hc['pemohon_username'] ?? null, $hc['pemohon_name'] ?? null, (int)($hc['pemohon_sil_id'] ?? 0)); ?>
                                            <div class="avatar-xs flex-shrink-0" style="background:<?= $pemohonImg ? 'none' : avatarColor($hc['pemohon_id'] ?? $hc['user_id'] ?? 0) ?>; width: 32px; height: 32px; font-size: 12px;">
                                                <?php if ($pemohonImg): ?>
                                                    <img src="<?= $pemohonImg ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                                                <?php else: ?>
                                                    <?= User::getInitials($hc['pemohon_name'] ?? '') ?>
                                                <?php endif; ?>
                                            </div>
                                            <span class="small fw-bold text-dark"><?= htmlspecialchars($hc['pemohon_name'] ?? '') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($hc['teknisi_name']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1" style="font-size: 10px; font-weight: 700;">
                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($hc['teknisi_name'] ?? '') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1" style="font-size: 10px; font-weight: 700;">
                                            <?= htmlspecialchars($hc['category'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($hc['room'])): ?>
                                            <span class="small text-dark fw-medium"><i class="bi bi-geo-alt me-1 text-primary"></i><?= htmlspecialchars($hc['room'] ?? '') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-light border shadow-sm rounded-pill px-3 fw-bold text-primary" style="font-size: 12px;"
                                                data-bs-toggle="modal" data-bs-target="#chatHistoryModal"
                                                data-chat-id="<?= (int)($hc['id'] ?? 0) ?>"
                                                data-chat-title="<?= htmlspecialchars($hc['title'] ?? '') ?>"
                                                data-pemohon="<?= htmlspecialchars($hc['pemohon_name'] ?? '') ?>">
                                            <i class="bi bi-eye me-1"></i> Detail
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
</div>

<script>
$(document).ready(function() {
    $('#historyTableSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#historyGeneralTable tbody tr.history-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function exportGeneralHistoryToPDF() {
    const btn = $('#btnExportHistory');
    const originalContent = btn.html();
    
    // UI Loading state
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Memproses...');

    // Small delay to let the UI render the loading state
    setTimeout(() => {
        try {
            // Improved library detection
            let jsPDF_Lib = null;
            if (window.jspdf && window.jspdf.jsPDF) {
                jsPDF_Lib = window.jspdf.jsPDF;
            } else if (window.jsPDF) {
                jsPDF_Lib = window.jsPDF;
            }

            if (!jsPDF_Lib) {
                showToast('Library PDF tidak ditemukan. Cek koneksi internet.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }
            
            const doc = new jsPDF_Lib('p', 'mm', 'a4');
            
            // Add Header
            doc.setFontSize(18);
            doc.setTextColor(15, 23, 42); 
            doc.text('Laporan Riwayat Layanan IT', 14, 20);
            
            doc.setFontSize(10);
            doc.setTextColor(100, 116, 139); 
            doc.text('Aplikasi Layanan Internal IT Selulosa (ALISA)', 14, 26);
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, 14, 31);
            
            // Line separator
            doc.setDrawColor(226, 232, 240); 
            doc.line(14, 36, 196, 36);

            const tableData = [];
            const visibleRows = $("#historyGeneralTable tbody tr.history-row:visible");
            
            if (visibleRows.length === 0) {
                showToast('Tidak ada data yang tersedia untuk di-export.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }

            visibleRows.each(function() {
                const row = $(this);
                const waktu = row.find('td:eq(0) .fw-bold').text().trim();
                const selesai = row.find('td:eq(0) .text-muted').text().trim();
                const pemohon = row.find('td:eq(1) .text-dark').text().trim();
                const teknisi = row.find('td:eq(2)').text().trim();
                const kategori = row.find('td:eq(3)').text().trim();
                const ruangan = row.find('td:eq(4)').text().trim();

                tableData.push([
                    `${waktu}\n${selesai}`,
                    pemohon,
                    teknisi || '—',
                    kategori,
                    ruangan || '—'
                ]);
            });

            if (typeof doc.autoTable !== 'function') {
                showToast('Plugin autoTable tidak terdeteksi.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }

            doc.autoTable({
                head: [['WAKTU (MULAI-SELESAI)', 'PEMOHON', 'TIM IT', 'KATEGORI', 'RUANGAN']],
                body: tableData,
                startY: 42,
                theme: 'striped',
                headStyles: { 
                    fillColor: [16, 185, 129], // #10b981
                    textColor: 255, 
                    fontSize: 10,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                styles: { 
                    fontSize: 9, 
                    cellPadding: 4,
                    overflow: 'linebreak',
                    font: 'helvetica'
                },
                columnStyles: {
                    0: { cellWidth: 40 },
                    1: { cellWidth: 40 },
                    2: { cellWidth: 35, halign: 'center' },
                    3: { cellWidth: 35, halign: 'center' },
                    4: { cellWidth: 32, halign: 'center' }
                },
                didDrawPage: function (data) {
                    // Footer
                    var str = "Halaman " + doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(148, 163, 184); 
                    doc.text(str, 14, doc.internal.pageSize.height - 10);
                }
            });

            // Preview in Modal
            currentPdfDoc = doc;
            const blobUrl = doc.output('bloburl');
            $('#pdfPreviewFrame').attr('src', blobUrl);
            $('#pdfPreviewModal').modal('show');
            
            // Restore button
            btn.prop('disabled', false).html(originalContent);

        } catch (err) {
            console.error('PDF Export Error:', err);
            showToast('Terjadi kesalahan: ' + err.message, 'danger');
            btn.prop('disabled', false).html(originalContent);
        }
    }, 100);
}
function updateHistoryPeriod() {
    const start = $('#histStart').val();
    const end = $('#histEnd').val();
    if (start && end) {
        window.location.href = `?page=history&start=${start}&end=${end}`;
    }
}
</script>
