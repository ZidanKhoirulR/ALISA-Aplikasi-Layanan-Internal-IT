<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kalender Ketersediaan — ALISA</title>
  <meta name="description" content="Dashboard kalender interaktif untuk melihat jadwal Zoom Meeting dan beban kerja Teknisi IT Selulosa."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <!-- FullCalendar via CDN -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet"/>
  <link href="public/css/style.css" rel="stylesheet"/>
  <style>
    /* ── Calendar Page Layout ── */
    .cal-shell {
      display: flex;
      height: 100vh;
      overflow: hidden;
      background: #0f172a;
    }

    /* ── Calendar Content Area ── */
    .cal-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #f8fafc;
      overflow: hidden;
    }

    /* ── Legend Chips ── */
    .cal-legend {
      display: flex; align-items: center; gap: 12px; margin-left: auto;
    }
    .legend-chip {
      display: flex; align-items: center; gap: 6px;
      font-size: 11px; font-weight: 700; color: #475569;
      background: white; border: 1px solid #e2e8f0;
      padding: 5px 14px; border-radius: 99px;
      text-transform: uppercase; letter-spacing: 0.5px;
    }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .dot-zoom { background: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.4); }
    .dot-it   { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }

    /* ── FullCalendar Custom Overrides ── */
    #mainCalendar {
      background: white;
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.03);
      border: 1px solid #e2e8f0;
      min-height: 500px;
    }

    /* Toolbar */
    .fc .fc-toolbar-title { font-size: 18px !important; font-weight: 800 !important; color: #0f172a !important; letter-spacing: -0.5px; }
    .fc .fc-button-primary {
      background: #ffffff !important;
      border-color: #e2e8f0 !important;
      color: #475569 !important;
      font-weight: 700 !important;
      font-size: 12px !important;
      border-radius: 10px !important;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      padding: 8px 16px !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
    }
    .fc .fc-button-primary:hover { background: #f8fafc !important; color: var(--primary) !important; border-color: #cbd5e1 !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
      background: var(--primary) !important;
      border-color: var(--primary) !important;
      color: white !important;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2) !important;
    }
    .fc .fc-today-button { background: #f1f5f9 !important; border-color: #e2e8f0 !important; color: #475569 !important; }

    /* Grid */
    .fc .fc-col-header-cell-cushion { font-weight: 800 !important; color: #94a3b8 !important; font-size: 11px !important; text-transform: uppercase; letter-spacing: 1px; }
    .fc .fc-daygrid-day-number { font-size: 13px !important; font-weight: 700 !important; color: #64748b !important; padding: 10px !important; }
    .fc .fc-day-today { background: rgba(79, 70, 229, 0.03) !important; }
    .fc .fc-day-today .fc-daygrid-day-number { color: var(--primary) !important; font-weight: 800 !important; }
    .fc th { border-color: #f1f5f9 !important; }
    .fc td { border-color: #f1f5f9 !important; }
    .fc .fc-scrollgrid { border-color: #e2e8f0 !important; border-radius: 16px; overflow: hidden; }

    /* Events */
    .fc-event {
      border: none !important;
      border-radius: 8px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      padding: 5px 10px !important;
      margin: 2px 4px !important;
      cursor: pointer !important;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      background: #ffffff !important;
      color: #1e293b !important;
      border-left: 4px solid var(--fc-event-bg-color, #cbd5e1) !important;
    }
    .fc-event:hover { 
      transform: translateY(-1px) scale(1.01); 
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
      background: #f8fafc !important;
    }

    /* Multi-day block styling (Premium Pill) */
    .fc-daygrid-block-event {
      padding: 5px 12px !important;
      border-radius: 99px !important; /* Pill shape for long bars */
      margin: 3px 6px !important;
      background-image: linear-gradient(135deg, rgba(255,255,255,0.25), rgba(255,255,255,0)) !important;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;
    }
    .fc-daygrid-block-event .fc-event-main {
      padding: 0 !important;
      color: white !important;
    }
    .fc-daygrid-event-h-harness { margin-bottom: 2px; }

    /* Single day / Dot styling */
    .fc-daygrid-dot-event {
      padding: 4px 10px !important;
      background: rgba(0,0,0,0.02) !important;
      border: 1px solid rgba(0,0,0,0.05) !important;
    }
    .fc-daygrid-dot-event:hover { background: rgba(0,0,0,0.05) !important; }

    /* ── Event Detail Modal ── */
    .event-detail-row {
      display: flex; align-items: flex-start; gap: 14px;
      padding: 12px 0; border-bottom: 1px solid #f1f5f9;
    }
    .event-detail-row:last-child { border-bottom: none; }
    .event-detail-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
      box-shadow: 0 4px 8px rgba(0,0,0,0.03);
    }
    .event-detail-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
    .event-detail-value { font-size: 14px; font-weight: 700; color: #0f172a; }

    /* Mobile responsive */
    /* Force Icons to BI */
    .fc .fc-icon { font-family: "bootstrap-icons" !important; }
    .fc .fc-icon-chevron-left:before { content: "\f282" !important; }
    .fc .fc-icon-chevron-right:before { content: "\f285" !important; }

    /* ── FullCalendar Popover (the "+more" list) ── */
    .fc-popover {
      z-index: 1040 !important;
      background: #ffffff !important;
      border-radius: 16px !important;
      box-shadow: 0 10px 40px rgba(15, 23, 42, 0.2) !important;
      border: 1px solid #e2e8f0 !important;
      overflow: hidden !important;
      min-width: 250px !important;
    }
    .fc-popover-header {
      background: #f8fafc !important;
      padding: 12px 16px !important;
      border-bottom: 1px solid #f1f5f9 !important;
      font-weight: 800 !important;
      color: #1e293b !important;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .fc-popover-title {
      font-size: 14px !important;
      font-weight: 800 !important;
    }
    .fc-popover-close {
      color: #94a3b8 !important;
      opacity: 0.7;
      cursor: pointer;
    }
    .fc-popover-body {
      padding: 14px !important;
      background: #ffffff !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 6px !important;
    }
    /* Style for the "+more" link in day cell */
    .fc-daygrid-more-link {
      font-weight: 800 !important;
      color: var(--primary) !important;
      font-size: 11px !important;
      background: rgba(79, 70, 229, 0.08) !important;
      padding: 4px 8px !important;
      border-radius: 8px !important;
      text-decoration: none !important;
      display: inline-block !important;
      margin: 2px 4px !important;
      transition: all 0.2s ease;
    }
    .fc-daygrid-more-link:hover {
      background: var(--primary) !important;
      color: white !important;
    }

    @media (max-width: 768px) {
      .cal-legend  { display: none; }
      #mainCalendar { padding: 10px; border-radius: 12px; min-height: 400px; }
      .fc .fc-toolbar { flex-direction: column; gap: 10px; }
      .fc .fc-toolbar-title { font-size: 16px !important; order: 1; }
      .fc .fc-toolbar-chunk:nth-child(1) { order: 2; }
      .fc .fc-toolbar-chunk:nth-child(3) { order: 3; }
    }
  </style>
</head>
<?php 
  $user = $f3->get('currentUser'); 
  $isAdmin = $user['role'] === 'admin';
?>
<body class="dashboard-body">

<div class="app-shell">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <?php if ($isAdmin): ?>
    <!-- ADMIN SIDEBAR -->
    <?php include __DIR__ . '/components/admin_sidebar.php'; ?>
    
    <!-- ADMIN MAIN CONTENT -->
    <div class="admin-content-area flex-grow-1 h-100 overflow-hidden d-flex flex-column">
      <div class="admin-main-dashboard">
        <!-- Header Section -->
        <div class="admin-panel-header">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-sidebar-toggle d-lg-none" onclick="openAdminSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-panel-icon" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Kalender Jadwal</h4>
                    <p class="text-muted small mb-0">Visualisasi jadwal Zoom Meeting & beban kerja tim</p>
                </div>
            </div>
            
            <div class="cal-legend d-none d-xl-flex gap-1">
                <div class="legend-chip"><span class="legend-dot" style="background:#3b82f6;"></span>RAYON</div>
                <div class="legend-chip"><span class="legend-dot" style="background:#8b5cf6;"></span>SELULOSA</div>
                <div class="legend-chip"><span class="legend-dot" style="background:#f59e0b;"></span>KERTAS</div>
                <div class="legend-chip"><span class="legend-dot" style="background:#06b6d4;"></span>PINUS</div>
                <div class="legend-chip border-0 ms-2">|</div>
                <div class="legend-chip"><span class="legend-dot dot-it"></span>PIC Teknis</div>
            </div>
        </div>

        <div class="admin-panel-body d-flex flex-column">
            <div id="mainCalendar" class="flex-grow-1"></div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- SIDEBAR (User/Teknisi) -->
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <!-- CHAT PANEL REPLACEMENT: CALENDAR -->
    <div class="cal-content">
      <!-- Top Bar -->
      <div class="chat-header border-bottom">
        <button class="btn-sidebar-toggle d-lg-none me-3" onclick="openSidebar()">
          <i class="bi bi-list"></i>
        </button>
        <div class="chat-header-avatar" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
          <i class="bi bi-calendar3"></i>
        </div>
        <div class="chat-header-info">
          <div class="chat-header-name">Kalender Ketersediaan</div>
          <div class="chat-header-title">Monitoring jadwal Zoom & Tim IT</div>
        </div>

        <!-- Legend -->
        <div class="cal-legend ms-auto d-none d-md-flex flex-wrap justify-content-end gap-1">
          <div class="legend-chip"><span class="legend-dot" style="background:#3b82f6;"></span>RAYON</div>
          <div class="legend-chip"><span class="legend-dot" style="background:#8b5cf6;"></span>SELULOSA</div>
          <div class="legend-chip"><span class="legend-dot" style="background:#f59e0b;"></span>KERTAS</div>
          <div class="legend-chip"><span class="legend-dot" style="background:#06b6d4;"></span>PINUS</div>
          <div class="legend-chip border-0 ms-2">|</div>
          <div class="legend-chip"><span class="legend-dot dot-it"></span>PIC Teknis</div>
          <a href="./" class="btn btn-sm btn-light border ms-2 rounded-pill fw-bold" style="font-size: 11px;">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
        </div>
      </div>

      <!-- Calendar Body -->
      <div class="p-3 p-md-4 flex-grow-1 overflow-auto d-flex flex-column">
        <div id="mainCalendar" class="flex-grow-1"></div>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- ── Modal: Detail Event ── -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-modern">
      <div class="modal-header border-0 pb-2" id="eventModalHeader">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <div class="modal-icon-wrap" id="eventModalIcon" style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
            <i id="eventModalIconEl" class="bi bi-camera-video-fill"></i>
          </div>
          <div>
            <h5 class="modal-title-text mt-0" id="eventModalTitle" style="font-size: 18px;">Detail Event</h5>
            <div class="text-muted small" id="eventModalSubtitle"></div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2 px-4" id="eventModalBody">
        <!-- Injected by JS -->
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-light px-4 border" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999; pointer-events:none;">
  <div id="appToast" class="toast align-items-center text-white border-0" role="alert" style="pointer-events:auto;">
    <div class="d-flex">
      <div class="toast-body" id="toastBody">Berhasil</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
// ── Inject PHP context ──────────────────────────────────────
const APP = {
  currentUser:  <?= json_encode($f3->get('currentUser')) ?>,
  activeChatId: 0,
  filter:       'all',
  lastMsgId:    0,
};

// ── Utility ──────────────────────────────────────────────────
function escapeHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str || ''));
  return div.innerHTML;
}
function formatDateJS(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
}
function formatTimeJS(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', hour12:false }).replace(':', '.');
}

// ── Sidebar (mobile) ─────────────────────────────────────────
function openSidebar()      { $('#sidebar').addClass('open');        $('#sidebarOverlay').addClass('active'); }
function openAdminSidebar() { $('#adminSidebar').addClass('open');   $('#sidebarOverlay').addClass('active'); }
function closeSidebar()     { 
  $('#sidebar, #adminSidebar').removeClass('open'); 
  $('#sidebarOverlay').removeClass('active'); 
}

$('#userMenuToggle').on('click', function(e) {
  e.stopPropagation();
  const dd = $('#userDropdown');
  dd.is(':visible') ? dd.slideUp(150) : dd.slideDown(150);
});
$(document).on('click', function() { $('#userDropdown').slideUp(150); });

// ── Event Detail Modal ────────────────────────────────────────
let eventDetailModal = null;

function showEventModal(info) {
  const ev   = info.event;
  const ext  = ev.extendedProps;
  const type = ext.type; // 'zoom' | 'task'

  // Icon & colour
  const isZoom = type === 'zoom';
  document.getElementById('eventModalIcon').style.background = isZoom
    ? 'linear-gradient(135deg,#3b82f6,#6366f1)'
    : 'linear-gradient(135deg,#10b981,#059669)';
  document.getElementById('eventModalIconEl').className = isZoom
    ? 'bi bi-camera-video-fill' : 'bi bi-person-gear';
  document.getElementById('eventModalTitle').textContent   = ev.title.replace('✅ ', '').replace('⏳ ', '');
  document.getElementById('eventModalSubtitle').textContent = isZoom ? 'Jadwal Zoom Meeting' : 'PIC Teknis Aktif';

  // Build body HTML
  let html = '';

  if (isZoom) {
    html += detailRow('bi-building', '#ede9fe', '#5b21b6',  'Ruangan',       ext.ruangan  || 'Virtual / Tanpa Ruangan');
    
    // Grid for Start: Date & Time separate columns
    html += `
    <div class="event-detail-row p-0 overflow-hidden">
      <div class="row g-0 w-100">
        <div class="col-6 border-end p-3 py-2">
          <div class="event-detail-label"><i class="bi bi-calendar-event me-1"></i> Tanggal Mulai</div>
          <div class="event-detail-value">${formatDateJS(ext.waktu_mulai)}</div>
        </div>
        <div class="col-6 p-3 py-2">
          <div class="event-detail-label"><i class="bi bi-clock me-1"></i> Jam Mulai</div>
          <div class="event-detail-value">${formatTimeJS(ext.waktu_mulai)}</div>
        </div>
      </div>
    </div>`;

    // Grid for End: Date & Time separate columns
    html += `
    <div class="event-detail-row p-0 overflow-hidden">
      <div class="row g-0 w-100">
        <div class="col-6 border-end p-3 py-2">
          <div class="event-detail-label"><i class="bi bi-calendar-x me-1"></i> Tanggal Selesai</div>
          <div class="event-detail-value">${formatDateJS(ext.waktu_selesai)}</div>
        </div>
        <div class="col-6 p-3 py-2">
          <div class="event-detail-label"><i class="bi bi-clock me-1"></i> Jam Selesai</div>
          <div class="event-detail-value">${formatTimeJS(ext.waktu_selesai)}</div>
        </div>
      </div>
    </div>`;

    html += detailRow('bi-journals', '#f0fdf4', '#15803d', 'Nama Kegiatan', escapeHtml(ext.nama_kegiatan || '—'));
    
    // Status penanganan chat
    let statusBadge = '<span class="badge bg-secondary">Menunggu</span>';
    if (ext.chat_status === 'done') {
      statusBadge = '<span class="badge bg-success">✅ Selesai Ditangani</span>';
    } else if (ext.chat_status === 'in_progress') {
      statusBadge = '<span class="badge bg-primary">⏳ Permintaan ditangani Tim IT</span>';
    }
    html += detailRow('bi-shield-check', '#fef3c7', '#d97706', 'Status', statusBadge);

    // Tipe Permintaan Logic
    let reqTypeLabel = '';
    const reqType = ext.request_type || (ext.butuh_link ? 'link_tech' : 'own_link');
    if (reqType === 'link_tech') {
        reqTypeLabel = 'Link Zoom + Ruangan';
    } else if (reqType === 'link_only') {
        reqTypeLabel = 'Hanya Link Zoom (Virtual)';
    } else {
        reqTypeLabel = 'Ruangan Saja';
    }
    html += detailRow('bi-tags-fill', '#e0e7ff', '#4f46e5', 'Tipe Permintaan', reqTypeLabel);

  } else {
    html += detailRow('bi-person-badge', '#eef2ff', '#4338ca', 'Teknisi',        escapeHtml(ext.teknisi_name || '—'));
    html += detailRow('bi-tag',          '#f0fdf4', '#15803d', 'Kategori Layanan', escapeHtml(ext.category || '—'));
    html += detailRow('bi-person',       '#fff7ed', '#c2410c', 'User',            escapeHtml(ext.user_name || '—'));

    // Tampilkan info Zoom jika ini adalah tugas terkait Zoom
    if (ext.zoom_start) {
      const zoomTimeRange = formatDatetime(ext.zoom_start) + ' s/d ' + 
        (ext.zoom_end ? new Date(ext.zoom_end).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', hour12:false}) : '—');
      html += detailRow('bi-camera-video', '#e0f2fe', '#0284c7', 'Jadwal Zoom', zoomTimeRange);
      html += detailRow('bi-building',     '#ede9fe', '#5b21b6', 'Ruangan Zoom', escapeHtml(ext.zoom_room || '—'));
    }

    html += detailRow('bi-clock',        '#ede9fe', '#5b21b6', 'Permintaan Dibuat',   formatDatetime(ev.start));
  }

  document.getElementById('eventModalBody').innerHTML = html;

  // Hide any open FullCalendar popovers to prevent overlap
  $('.fc-popover').hide();

  // Show modal
  eventDetailModal = eventDetailModal || new bootstrap.Modal(document.getElementById('eventDetailModal'));
  eventDetailModal.show();
}

function detailRow(icon, bgColor, iconColor, label, value) {
  return `
    <div class="event-detail-row">
      <div class="event-detail-icon" style="background:${bgColor}; color:${iconColor};">
        <i class="bi ${icon}"></i>
      </div>
      <div>
        <div class="event-detail-label">${escapeHtml(label)}</div>
        <div class="event-detail-value">${value}</div>
      </div>
    </div>`;
}

// ── FullCalendar Initialization ───────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  const calEl = document.getElementById('mainCalendar');

  const calendar = new FullCalendar.Calendar(calEl, {
    locale:          'id',
    initialView:     'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay'
    },
    buttonText: {
      today: 'Hari ini',
      month: 'Bulan',
      week:  'Minggu',
      day:   'Hari',
    },
    height:          '100%',
    aspectRatio:     1.8,
    fixedWeekCount:  false,
    dayMaxEvents:    true, // Collapse extra events into "+more"
    eventClick:      showEventModal,
    
    // ── Custom Event Rendering ────────────────────────────────
    eventContent: function(arg) {
      const ext = arg.event.extendedProps;
      const isDone = ext.chat_status === 'done';
      const isZoom = ext.type === 'zoom';
      const color = arg.event.backgroundColor || '#6366f1';
      
      // Select icon
      let iconHtml = '';
      if (isZoom) {
        iconHtml = isDone 
          ? `<i class="bi bi-check-circle-fill" style="color:#10b981;"></i>` 
          : `<i class="bi bi-camera-video-fill" style="color:${color};"></i>`;
      } else {
        iconHtml = `<i class="bi bi-person-gear" style="color:${color};"></i>`;
      }
      
      // Time string
      let timeHtml = arg.timeText ? `<span class="fw-800 me-1" style="color: ${color}; font-size: 10px;">${arg.timeText}</span>` : '';
      
      return {
        html: `
          <div class="fc-event-main-content d-flex align-items-center text-truncate" style="gap: 8px;">
            <span class="flex-shrink-0 d-flex align-items-center">${iconHtml}</span>
            ${timeHtml}
            <span class="text-truncate" style="font-weight: 700; color: #334155;">${arg.event.title}</span>
          </div>
        `
      };
    },

    eventDidMount:   function(info) {
      // Tambahkan tooltip Bootstrap
      info.el.setAttribute('data-bs-toggle', 'tooltip');
      info.el.setAttribute('data-bs-title',  info.event.title);
      new bootstrap.Tooltip(info.el, { 
        trigger: 'hover', 
        placement: 'top',
        boundary: 'window',
        fallbackPlacements: ['bottom', 'right']
      });
    },

    // ── Sumber Event (Events Source) ─────────────────────────
    events: function(fetchInfo, successCallback, failureCallback) {
      fetch(`api/calendar/events?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}&v=${new Date().getTime()}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            console.error(data.error);
            failureCallback(data.error);
          } else {
            successCallback(data);
          }
        })
        .catch(err => {
          console.error(err);
          failureCallback(err);
        });
    }
  });

  calendar.render();
});
</script>
</body>
</html>
