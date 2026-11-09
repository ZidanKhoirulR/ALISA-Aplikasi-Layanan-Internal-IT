<?php
// ============================================
// app/views/components/admin_history_zoom.php
// Menampilkan daftar riwayat zoom yang sudah selesai
// Variables: $history_zoom, $f3
// ============================================

$historyZoomList = $f3->get('history_zoom') ?? [];

if (!function_exists('histGetInitials')) {
    function histGetInitials($name) {
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
                <i class="bi bi-camera-video-fill"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-900 admin-title-gradient">Riwayat Zoom</h4>
                <p class="text-muted small mb-0">Monitor seluruh jadwal dan penggunaan meeting Zoom</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
            <!-- Period Filter -->
            <div class="d-flex align-items-center gap-2 px-3 bg-white border rounded-4 shadow-sm me-1" style="height: 40px;">
                <i class="bi bi-funnel-fill text-primary small"></i>
                <span class="text-muted fw-bold me-1" style="font-size: 10px;">PERIODE:</span>
                <input type="date" id="zoomStart" class="border-0 bg-transparent" value="<?= $f3->get('stats_start') ?>" onchange="updateZoomPeriod()" style="outline:none; font-size:11px; width:115px;">
                <span class="text-muted small">&mdash;</span>
                <input type="date" id="zoomEnd" class="border-0 bg-transparent" value="<?= $f3->get('stats_end') ?>" onchange="updateZoomPeriod()" style="outline:none; font-size:11px; width:115px;">
            </div>

            <div class="input-group shadow-sm" style="width: 200px;">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; height: 40px;"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="zoomTableSearch" class="form-control border-start-0" placeholder="Cari..." style="border-radius: 0 12px 12px 0; font-size: 13px; height: 40px;">
            </div>
            <button class="btn btn-primary d-flex align-items-center gap-2 px-3 shadow-sm" style="border-radius: 12px; font-size: 13px; font-weight: 600; height: 40px;" id="btnExportZoom" onclick="exportZoomHistoryToPDF()">
                <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
            </button>
        </div>
    </div>

    <div class="admin-panel-body">
        <!-- Data Card -->
        <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="zoomHistoryTable">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th class="text-secondary fw-bold py-3 ps-4" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">NAMA KEGIATAN</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">RUANGAN</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">WAKTU</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">STATUS</th>
                                <th class="text-secondary fw-bold py-3" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">PEMOHON</th>
                                <th class="text-secondary fw-bold py-3 pe-4 text-center" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php if (empty($historyZoomList)): ?>
                                <tr id="emptyRow">
                                    <td colspan="6" class="py-5 text-center text-muted">
                                        <div class="py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                            <h6 class="fw-bold text-dark">Belum Ada Data Jadwal Zoom</h6>
                                            <p class="small mb-0">Semua pengajuan Zoom akan tampil di tabel ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historyZoomList as $z): 
                                    $statusClass = 'badge-waiting';
                                    $statusText = 'Menunggu';
                                    if ($z['chat_status'] === 'in_progress') { $statusClass = 'badge-inprogress'; $statusText = 'Berjalan'; }
                                    elseif ($z['chat_status'] === 'done') { $statusClass = 'badge-done'; $statusText = 'Selesai'; }
                                ?>
                                <tr class="zoom-row">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($z['nama_kegiatan']) ?></div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?= zoomRequestBadge($z['request_type'] ?? ($z['butuh_link'] ? 'link_tech' : 'own_link')) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 13px;">
                                            <?php if ($z['ruangan']): ?>
                                                <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($z['ruangan']) ?>
                                            <?php else: ?>
                                                <i class="bi bi-camera-video me-1 text-info"></i><span class="text-muted">Virtual / Hanya Link</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="small fw-bold text-dark"><i class="bi bi-calendar-event me-1 text-muted"></i><?= formatZoomRange($z['waktu_mulai'], $z['waktu_selesai']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="chat-status-badge <?= $statusClass ?>" style="font-size: 10px; padding: 4px 10px;"><?= strtoupper($statusText) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm" style="width: 28px; height: 28px; font-size: 11px; background:<?= avatarColor($z['pemohon_id'] ?? 1) ?>">
                                                <?php if (!empty($z['pemohon_avatar_url'])): ?>
                                                    <img src="<?= $z['pemohon_avatar_url'] ?>" alt="<?= htmlspecialchars($z['pemohon_name']) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                                                <?php else: ?>
                                                    <?= histGetInitials($z['pemohon_name']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="small fw-bold text-dark" style="line-height: 1;"><?= htmlspecialchars($z['pemohon_name']) ?></span>
                                                <?php if ($z['teknisi_name']): ?>
                                                    <span class="text-muted" style="font-size: 10px;">IT: <?= htmlspecialchars($z['teknisi_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <button class="btn btn-sm btn-light border shadow-sm rounded-pill px-3 fw-bold text-primary" style="font-size: 12px;"
                                                data-bs-toggle="modal" data-bs-target="#chatHistoryModal"
                                                data-chat-id="<?= (int)($z['chat_id'] ?? 0) ?>"
                                                data-chat-title="<?= htmlspecialchars($z['nama_kegiatan'] ?? '') ?>"
                                                data-pemohon="<?= htmlspecialchars($z['pemohon_name'] ?? '') ?>">
                                            <i class="bi bi-chat-text me-1"></i> Detail
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
let currentPdfDoc = null;

$(document).ready(function() {
    console.log('Zoom History JS Loaded');
    
    $('#zoomTableSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#zoomHistoryTable tbody tr.zoom-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Handle download button in modal
    $('#btnDownloadPdf').on('click', function() {
        try {
            if (currentPdfDoc) {
                currentPdfDoc.save(`Riwayat_Zoom_${new Date().getTime()}.pdf`);
            }
        } catch (err) {
            showToast('Gagal mendownload PDF: ' + err.message, 'danger');
        }
    });
});

function exportZoomHistoryToPDF() {
    const btn = $('#btnExportZoom');
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
                showToast('Library PDF tidak ditemukan. Cek koneksi internet atau refresh halaman.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }
            
            const doc = new jsPDF_Lib('p', 'mm', 'a4');
            
            // Add Header
            doc.setFontSize(18);
            doc.setTextColor(15, 23, 42); // #0f172a
            doc.text('Laporan Riwayat Zoom Meeting', 14, 20);
            
            doc.setFontSize(10);
            doc.setTextColor(100, 116, 139); // #64748b
            doc.text('Aplikasi Layanan Internal IT Selulosa (ALISA)', 14, 26);
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, 14, 31);
            
            // Line separator
            doc.setDrawColor(226, 232, 240); // #e2e8f0
            doc.line(14, 36, 196, 36);

            const tableData = [];
            const visibleRows = $("#zoomHistoryTable tbody tr.zoom-row:visible");
            
            if (visibleRows.length === 0) {
                showToast('Tidak ada data yang tersedia untuk di-export.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }

            visibleRows.each(function() {
                const row = $(this);
                const namaKegiatan = row.find('td:eq(0) .text-dark').text().trim();
                const tipeLink = row.find('td:eq(0) .badge').text().trim();
                const ruangan = row.find('td:eq(1)').text().trim();
                const tanggal = row.find('td:eq(2) .fw-bold').text().trim();
                const jam = row.find('td:eq(2) .text-muted').text().trim();
                const status = row.find('td:eq(3)').text().trim();
                const pemohon = row.find('td:eq(4) .fw-bold').text().trim();
                const teknisi = row.find('td:eq(4) .text-muted').text().trim().replace('IT: ', '');

                tableData.push([
                    `${namaKegiatan}\n(${tipeLink})`,
                    ruangan,
                    `${tanggal}\n${jam}`,
                    status,
                    `${pemohon}${teknisi ? '\nIT: ' + teknisi : ''}`
                ]);
            });

            if (typeof doc.autoTable !== 'function') {
                showToast('Plugin autoTable tidak terdeteksi.', 'danger');
                btn.prop('disabled', false).html(originalContent);
                return;
            }

            doc.autoTable({
                head: [['KEGIATAN', 'RUANGAN', 'WAKTU', 'STATUS', 'PEMOHON']],
                body: tableData,
                startY: 42,
                theme: 'striped',
                headStyles: { 
                    fillColor: [79, 70, 229], // #4f46e5
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
                    0: { cellWidth: 50 },
                    1: { cellWidth: 30, halign: 'center' },
                    2: { cellWidth: 35, halign: 'center' },
                    3: { cellWidth: 25, halign: 'center' },
                    4: { cellWidth: 46 }
                },
                didDrawPage: function (data) {
                    // Footer
                    var str = "Halaman " + doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(148, 163, 184); // #94a3b8
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
function updateZoomPeriod() {
    const start = $('#zoomStart').val();
    const end = $('#zoomEnd').val();
    if (start && end) {
        window.location.href = `?page=history_zoom&start=${start}&end=${end}`;
    }
}
</script>
