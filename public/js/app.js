/**
 * ALISA — app.js
 * jQuery + AJAX polling for real-time chat experience
 */

// â”€â”€ Constants â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const POLL_INTERVAL  = 3000;   // ms between message polls
const CHAT_POLL      = 5000;   // ms between chat list refresh

let pollTimer        = null;
let chatListTimer    = null;
let isLoading        = false;
let selectedFileForUpload = null;
let pendingOptimisticMsgId = null;
let lastSendGuard          = { text: '', at: 0 };

// ── Bootstrap Modal instance ──────────────────────────────────────
let newRequestModal  = null;
let groupRequestModal = null;
let toastInstance    = null;

// ── Bootstrap Toast ──────────────────────────────────────
function showToast(msg, type = 'success') {
  const el = document.getElementById('appToast');
  if (!el) return;
  el.className = `toast align-items-center text-white border-0 bg-${type === 'success' ? 'success' : 'danger'}`;
  const icon = type === 'success' ? '<i class="bi bi-check-circle-fill me-2 fs-5"></i>' : '<i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>';
  const toastBody = document.getElementById('toastBody');
  if (toastBody) {
    toastBody.innerHTML = `<div class="d-flex align-items-center">${icon}<span>${msg}</span></div>`;
  }
  toastInstance = toastInstance || new bootstrap.Toast(el, { delay: 3000 });
  toastInstance.show();
}

// ── On DOM ready ───────────────────────────────────────────────────────────
$(function () {
  // Show pending toast if exists
  const pendingToast = localStorage.getItem('pending_toast');
  if (pendingToast) {
    try {
      const data = JSON.parse(pendingToast);
      showToast(data.msg, data.type || 'success');
    } catch (e) {
      showToast(pendingToast);
    }
    localStorage.removeItem('pending_toast');
  }

  // Security: Global AJAX Setup for CSRF
  $.ajaxSetup({
    headers: {
      'X-Csrf-Token': APP.csrfToken
    }
  });

  const reqModalEl = document.getElementById('newRequestModal');
  if (reqModalEl) {
    newRequestModal = new bootstrap.Modal(reqModalEl);
  }

  const grpModalEl = document.getElementById('groupRequestModal');
  if (grpModalEl) {
    groupRequestModal = new bootstrap.Modal(grpModalEl);
  }

  // Scroll messages to bottom on load
  scrollToBottom();

  initGroupMentions();

  // User dropdown toggle
  $('#userMenuToggle').on('click', function (e) {
    e.stopPropagation();
    const dd = $('#userDropdown');
    dd.is(':visible') ? dd.slideUp(150) : dd.slideDown(150);
  });
  $(document).on('click', function () { $('#userDropdown').slideUp(150); });

  // Mobile sidebar toggle
  $('#sidebarToggle').on('click', openSidebar);

  // Start polling if a chat is open
  if (APP.activeChatId) {
    startMessagePolling();
    startChatListPolling();
  } else {
    startChatListPolling();
  }

  // Auto-resize textarea on input
  const inp = document.getElementById('messageInput');
  if (inp) {
    inp.addEventListener('focus', () => {
      inp.style.height = 'auto';
    });
  }
});

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  SIDEBAR
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openSidebar() {
  $('#sidebar').addClass('open');
  $('#sidebarOverlay').addClass('active');
}
function openAdminSidebar() {
  $('.admin-sidebar').addClass('open');
  $('#sidebarOverlay').addClass('active');
}
function closeSidebar() {
  $('#sidebar').removeClass('open');
  $('.admin-sidebar').removeClass('open');
  $('#sidebarOverlay').removeClass('active');
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  NEW REQUEST MODAL
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openNewRequestModal() {
  if (!newRequestModal) {
    return;
  }
  // Reset form
  $('#requestUserName').val(APP.currentUser.name);
  $('#selectedCategory').val('Umum');
  $('#roomSelectionWrapper').removeClass('d-none'); // Always show for modern categories
  $('#requestFormError').addClass('d-none').text('');
  
  // Reset category grid view
  $('#categoryGrid').removeClass('single-mode');
  $('.cat-card').removeClass('selected d-none');
  $('#btnChangeCategory').hide();

  // Reset extra fields
  $('.extra-field-group').hide();
  $('.extra-field-input').val('');

  newRequestModal.show();
}

function showAllCategories() {
  $('#categoryGrid').removeClass('single-mode');
  $('.cat-card').removeClass('d-none').css('pointer-events', 'auto');
  $('#btnChangeCategory').fadeOut(200);
}


function selectCategory(el, cat) {
  $('.cat-card').removeClass('selected');
  $(el).addClass('selected');
  $('#selectedCategory').val(cat);

  // Khusus Zoom Meeting: langsung buka modal Zoom khusus
  if (cat === 'zoom_meeting' || cat.toLowerCase().indexOf('zoom') !== -1) {
    if (typeof openZoomRequestModal === 'function') {
      if (newRequestModal) newRequestModal.hide();
      setTimeout(() => openZoomRequestModal(), 300);
      return;
    }
  }
  
  // Untuk kategori selain Zoom, pastikan room selection muncul
  $('#roomSelectionWrapper').removeClass('d-none');

  // Toggle extra fields based on category
  $('.extra-field-group').hide();
  $(`.extra-field-group[data-for="${cat}"]`).fadeIn(300);
}


function selectRoom(el, room) {
  $('.room-card').removeClass('selected');
  $(el).addClass('selected');
  $('#selectedRoom').val(room);
}

function quickRequest(cat) {
  /**
   * Jika kategori adalah "Zoom Meeting", buka modal Zoom khusus.
   * Modal Zoom hanya tersedia untuk role 'user' (lihat dashboard.php).
   * Untuk kategori lain, buka modal New Request standar.
   */
  if (cat === 'zoom_meeting' || cat.toLowerCase().indexOf('zoom') !== -1) {
    // Cek apakah zoom modal tersedia (user role)
    if (typeof openZoomRequestModal === 'function') {
      openZoomRequestModal();
      return;
    }
  }

  // Kategori selain Zoom → modal standar
  openNewRequestModal();
  
  setTimeout(() => {
    const el = $(`.modal .cat-card[data-cat="${cat}"]`);
    if (el.length) {
      selectCategory(el[0], cat);
      // Mode single category: sembunyikan yang lain agar visual lebih bersih
      $('#categoryGrid').addClass('single-mode');
      $('.cat-card').not('.selected').addClass('d-none');
      $('.cat-card.selected').css('pointer-events', 'none');
      $('#btnChangeCategory').fadeIn(200);
    }
  }, 50);
}

// ─────────────────────────────────────────────────────────────────
//  GROUP REQUEST MODAL
// ─────────────────────────────────────────────────────────────────
function openGroupRequestModal() {
  if (!groupRequestModal) return;

  // Reset form
  $('#groupReqUserName').val(APP.currentUser.name);
  $('#selectedGroupCategory').val('');
  $('#groupRequestFormError').addClass('d-none').text('');

  // Reset category grid
  $('#groupCategoryGrid').removeClass('single-mode');
  $('#groupCategoryGrid .cat-card').removeClass('selected d-none').css('pointer-events', 'auto');
  $('#btnChangeGroupCategory').hide();

  // Reset extra fields
  $('.extra-field-group[data-for-group]').hide();
  $('.group-dynamic-extra').val('');

  // Auto-select if only one category
  const cards = $('#groupCategoryGrid .cat-card');
  if (cards.length === 1) {
    const onlyCard = cards[0];
    const onlyCat  = $(onlyCard).data('cat');
    selectGroupCategory(onlyCard, onlyCat);
    $('#groupCategoryGrid').addClass('single-mode');
    $(onlyCard).css('pointer-events', 'none');
  }

  groupRequestModal.show();
}

function showAllGroupCategories() {
  $('#groupCategoryGrid').removeClass('single-mode');
  $('#groupCategoryGrid .cat-card').removeClass('d-none').css('pointer-events', 'auto');
  $('#btnChangeGroupCategory').fadeOut(200);
}

function selectGroupCategory(el, cat) {
  $('#groupCategoryGrid .cat-card').removeClass('selected');
  $(el).addClass('selected');
  $('#selectedGroupCategory').val(cat);

  // Zoom redirect
  if ((cat === 'zoom_meeting' || cat.toLowerCase().indexOf('zoom') !== -1) && typeof openZoomRequestModal === 'function') {
    groupRequestModal.hide();
    setTimeout(() => openZoomRequestModal(), 300);
    return;
  }

  // Toggle extra fields
  $('.extra-field-group[data-for-group]').hide();
  $(`.extra-field-group[data-for-group="${cat}"]`).fadeIn(300);
}

function submitGroupRequest() {
  const reqName   = $('#groupReqUserName').val().trim();
  const cat       = $('#selectedGroupCategory').val();

  const showErr = (msg) => {
    $('#groupRequestFormError').removeClass('d-none').text(msg);
  };

  if (!cat) { showErr('Pilih kategori layanan terlebih dahulu.'); return; }
  if (!reqName) { showErr('Nama User wajib diisi.'); return; }

  // Collect extra fields
  let extraData = {};
  let extraValid = true;
  $(`.extra-field-group[data-for-group="${cat}"] .group-dynamic-extra`).each(function () {
    const el  = $(this);
    const key = el.data('key');
    const val = el.val() ? el.val().trim() : '';
    if (el.prop('required') && !val) {
      showErr(`Silakan isi: ${el.data('label')}`);
      el.focus();
      extraValid = false;
      return false;
    }
    if (val) extraData[key] = val;
  });
  if (!extraValid) return;

  let shortTitle = 'Permintaan Baru';
  if (Object.keys(extraData).length > 0) {
    const firstVal = String(Object.values(extraData)[0]);
    shortTitle = firstVal.length > 50 ? firstVal.substring(0, 47) + '...' : firstVal;
  }
  const title = `[${cat}] ${shortTitle}`;

  const btn = $('#submitGroupRequestBtn');
  btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim…');

  $.ajax({
    url:    'api/chat/create',
    method: 'POST',
    data: {
      title:         title,
      category:      cat,
      category_code: cat,
      name:          reqName,
      room:          '',
      detail:        '',
      extra:         JSON.stringify(extraData),
      group_id:      APP.activeChatId   // selalu terhubung ke grup aktif
    },
    success: function (res) {
      if (res.success) {
        groupRequestModal.hide();
        showToast('Permintaan berhasil dibuat!');
        // Redirect ke tiket baru, bukan ke grup
        window.location.href = `?chat=${res.chat_id}&filter=all`;
      } else {
        showErr(res.error || 'Terjadi kesalahan.');
      }
    },
    error: function (xhr) {
      showErr(xhr.responseJSON?.error || 'Gagal membuat permintaan.');
    },
    complete: function () {
      btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim');
    }
  });
}

function submitNewRequest() {
  const reqName   = $('#requestUserName').val().trim();
  let cat         = $('#selectedCategory').val() || 'Umum';

  // Alihkan jika Zoom
  if ((cat === 'zoom_meeting' || cat.toLowerCase().indexOf('zoom') !== -1) && typeof openZoomRequestModal === 'function') {
    newRequestModal.hide();
    openZoomRequestModal();
    return;
  }

  // Validasi
  if (!reqName) {
    $('#requestFormError').removeClass('d-none').text('Nama User wajib diisi.');
    $('#requestUserName').focus();
    return;
  }

  // Collect Dynamic Extra Data
  let extraData = {};
  let extraValid = true;
  $(`.extra-field-group[data-for="${cat}"] .dynamic-extra`).each(function() {
    const el = $(this);
    const key = el.data('key');
    const val = el.val() ? el.val().trim() : '';
    const req = el.prop('required');
    
    if (req && !val) {
      $('#requestFormError').removeClass('d-none').text(`Silakan isi: ${el.data('label')}`);
      el.focus();
      extraValid = false;
      return false; // break loop
    }
    
    if (val) {
      extraData[key] = val;
    }
  });
  
  if (!extraValid) return;

  // Generate short title for sidebar
  let shortTitle = 'Permintaan Baru';
  if (Object.keys(extraData).length > 0) {
    const firstVal = String(Object.values(extraData)[0]);
    shortTitle = firstVal.length > 50 ? firstVal.substring(0, 47) + '...' : firstVal;
  }
  const title = `[${cat}] ${shortTitle}`;

  const btn = $('#submitRequestBtn');
  btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim…');

  $.ajax({
    url:    'api/chat/create',
    method: 'POST',
    data:   { 
      title:        title, 
      category:     cat, // backward compatibility
      category_code: cat,
      name:         reqName,
      room:         '',
      detail:       '',
      extra:        JSON.stringify(extraData),
      group_id:     null
    },
    success: function (res) {
      if (res.success) {
        newRequestModal.hide();
        showToast('Permintaan berhasil dibuat!');
        window.location.href = `?chat=${res.chat_id}&filter=all`;
      } else {
        showToast(res.error || 'Terjadi kesalahan.', 'danger');
      }
    },
    error: function (xhr) {
      const err = xhr.responseJSON?.error || 'Gagal membuat permintaan.';
      $('#requestFormError').removeClass('d-none').text(err);
    },
    complete: function () {
      btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim');
    }
  });
}

// ── Send button state ───────────────────────────────────────
function setSendLoading(loading) {
  const sendBtn = document.getElementById('sendBtn');
  if (!sendBtn) return;
  sendBtn.disabled = !!loading;
  sendBtn.innerHTML = loading
    ? '<span class="spinner-border spinner-border-sm"></span>'
    : '<i class="bi bi-send-fill"></i>';
}

function resetSendState() {
  isLoading = false;
  setSendLoading(false);
}

function normalizeMsgText(text) {
  return (text || '').trim();
}

function removePendingOutgoingByText(area, text) {
  if (!area) return;
  const norm = normalizeMsgText(text);
  if (!norm) return;
  area.querySelectorAll('.msg-bubble.bubble-me[data-pending="1"]').forEach(bubble => {
    if (normalizeMsgText(bubble.getAttribute('data-outgoing-text')) !== norm) return;
    const row = bubble.closest('.msg-row');
    if (row) row.remove();
  });
}

function dedupeMessageBubbles(area, msgId) {
  if (!area || !msgId) return;
  const bubbles = area.querySelectorAll(`.msg-bubble[data-msg-id="${msgId}"]`);
  for (let i = 1; i < bubbles.length; i++) {
    const row = bubbles[i].closest('.msg-row');
    if (row) row.remove();
  }
}

function confirmOptimisticMessage(serverMsg) {
  if (!serverMsg || !serverMsg.id) return;
  const realId = String(serverMsg.id);
  const area = document.getElementById('messagesArea');
  if (!area) return;

  const existingReal = area.querySelector(`.msg-bubble[data-msg-id="${realId}"]`);
  if (existingReal) {
    removePendingOutgoingByText(area, serverMsg.message);
    if (pendingOptimisticMsgId) {
      const optBubble = area.querySelector(`.msg-bubble[data-msg-id="${pendingOptimisticMsgId}"]`);
      if (optBubble) {
        const row = optBubble.closest('.msg-row');
        if (row) row.remove();
      }
    }
    pendingOptimisticMsgId = null;
    dedupeMessageBubbles(area, realId);
    return;
  }

  let bubble = null;
  if (pendingOptimisticMsgId) {
    bubble = area.querySelector(`.msg-bubble[data-msg-id="${pendingOptimisticMsgId}"]`);
  }
  if (!bubble) {
    const pending = area.querySelectorAll('.msg-bubble.bubble-me[data-pending="1"]');
    bubble = pending[pending.length - 1] || null;
  }

  if (bubble) {
    if (area.querySelector(`.msg-bubble[data-msg-id="${realId}"]`)) {
      const row = bubble.closest('.msg-row');
      if (row) row.remove();
    } else {
      bubble.setAttribute('data-msg-id', realId);
      bubble.removeAttribute('data-pending');
      bubble.removeAttribute('data-outgoing-text');
      if (serverMsg.sender_name) {
        bubble.setAttribute('data-sender-name', serverMsg.sender_name);
      }
      const row = bubble.closest('.msg-row');
      if (row && APP.activeChatType === 'group') {
        const preview = (serverMsg.message || '').substring(0, 60).replace(/'/g, "\\'");
        const actions = row.querySelector('.msg-actions');
        if (actions && !actions.querySelector('.btn-reply-msg')) {
          actions.insertAdjacentHTML('afterbegin',
            `<button type="button" class="btn-reply-msg" onclick="startReply(${realId}, '${escapeHtml(APP.currentUser.name).replace(/'/g, "\\'")}', '${escapeHtml(preview)}')" title="Balas"><i class="bi bi-reply-fill"></i></button>`
          );
        }
      }
    }
  } else {
    removePendingOutgoingByText(area, serverMsg.message);
    if (!area.querySelector(`.msg-bubble[data-msg-id="${realId}"]`)) {
      appendMessage(serverMsg);
    }
  }

  pendingOptimisticMsgId = null;
  dedupeMessageBubbles(area, realId);
}

function removeOptimisticBubble() {
  if (!pendingOptimisticMsgId) return;
  const bubble = document.querySelector(`.msg-bubble[data-msg-id="${pendingOptimisticMsgId}"]`);
  if (bubble) {
    const row = bubble.closest('.msg-row');
    if (row) row.remove();
  }
  pendingOptimisticMsgId = null;
}

// ── SEND MESSAGE ────────────────────────────────────────────
function sendMessage() {
  if (!APP.activeChatId) return;
  if (mentionActive) {
    hideMentionDropdown();
  }
  const input   = $('#messageInput');
  const message = input.val().trim();
  if (!message || isLoading) return;

  const now = Date.now();
  if (lastSendGuard.text === message && now - lastSendGuard.at < 1500) return;
  lastSendGuard = { text: message, at: now };

  const replyId   = (typeof replyToId !== 'undefined' && replyToId) ? replyToId : null;
  const replyName = (typeof replyToName !== 'undefined') ? replyToName : '';
  const replyTextEl = document.getElementById('replyBarText');
  const replyText = replyId && replyTextEl ? replyTextEl.textContent : '';

  isLoading = true;
  setSendLoading(true);

  pendingOptimisticMsgId = 'opt-' + Date.now();

  try {
    appendMessage({
      clientMsgId:       pendingOptimisticMsgId,
      sender_id:         APP.currentUser.id,
      sender_name:       APP.currentUser.name,
      sender_role:       APP.currentUser.role,
      sender_avatar:     APP.currentUser.avatar,
      sender_avatar_url: APP.currentUser.avatar_url,
      message:           message,
      created_at:        new Date().toISOString(),
      reply_to_id:       replyId,
      reply_sender_name: replyName,
      reply_message:     replyText,
    });
    input.val('').css('height', 'auto');
    if (typeof cancelReply === 'function') cancelReply();
    scrollToBottom();
  } catch (err) {
    console.error('sendMessage UI error:', err);
    removeOptimisticBubble();
    resetSendState();
    showToast('Gagal menampilkan pesan.', 'danger');
    return;
  }

  $.ajax({
    url:      'api/message/send',
    method:   'POST',
    dataType: 'json',
    timeout:  20000,
    data:     {
      chat_id:     APP.activeChatId,
      message:     message,
      reply_to_id: replyId || '',
      csrf_token:  APP.csrfToken,
    },
    success: function (res) {
      if (res && res.success && res.message) {
        const newId = parseInt(res.message.id, 10);
        if (!isNaN(newId)) {
          APP.lastMsgId = Math.max(APP.lastMsgId || 0, newId);
          confirmOptimisticMessage(res.message);
        }
      } else if (res && res.error) {
        removeOptimisticBubble();
        showToast(res.error, 'danger');
      }
    },
    error: function (xhr) {
      removeOptimisticBubble();
      const err = xhr.responseJSON && xhr.responseJSON.error;
      showToast(err || 'Gagal mengirim pesan.', 'danger');
    },
    complete: function () {
      resetSendState();
      try {
        pollChatList();
      } catch (e) { /* ignore sidebar refresh errors */ }
      const inp = document.getElementById('messageInput');
      if (inp) inp.focus();
    }
  });
}

function handleFileUpload(input) {
  const file = input.files[0];
  if (!file) return;

  const allowed = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
  const ext = file.name.split('.').pop().toLowerCase();
  
  const isGroup = (typeof APP !== 'undefined' && APP.activeChatType === 'group');

  if (!allowed.includes(ext)) {
    if (isGroup) {
      confirmModern('Pada grup chat, file yang bisa di upload yaitu gambar, pdf, doc, excel. Tidak dapat mengirim di luar dari format yang sudah ditentukan.', 'Format Tidak Diizinkan', 'danger', true);
    } else {
      confirmModern('Format file tidak diizinkan. Hanya PDF, Excel, Word, dan Gambar (JPG/JPEG/PNG).', 'Format Tidak Didukung', 'danger', true);
    }
    input.value = '';
    return;
  }

  // Max 10MB
  if (file.size > 10 * 1024 * 1024) {
    confirmModern('Ukuran file terlalu besar. Maksimal 10MB.', 'File Terlalu Besar', 'danger', true);
    input.value = '';
    return;
  }

  selectedFileForUpload = file;
  
  // Setup Preview Modal
  const previewContainer = document.getElementById('filePreviewContainer');
  previewContainer.innerHTML = '';
  
  const isImage = ['jpg', 'jpeg', 'png'].includes(ext);
  
  if (isImage) {
    const reader = new FileReader();
    reader.onload = function(e) {
      previewContainer.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">`;
    }
    reader.readAsDataURL(file);
  } else {
    // Document Preview Icon
    let iconClass = 'bi-file-earmark-text-fill text-secondary';
    if (ext === 'pdf') iconClass = 'bi-file-earmark-pdf-fill text-danger';
    else if (['xls', 'xlsx'].includes(ext)) iconClass = 'bi-file-earmark-excel-fill text-success';
    else if (['doc', 'docx'].includes(ext)) iconClass = 'bi-file-earmark-word-fill text-primary';
    
    previewContainer.innerHTML = `
      <div class="d-flex flex-column align-items-center text-center p-4 bg-white rounded shadow-sm" style="width: 260px; max-width: 100%;">
        <i class="bi ${iconClass} mb-3" style="font-size: 64px;"></i>
        <div class="fw-bold text-dark text-truncate w-100" style="font-size: 15px;">${file.name}</div>
        <div class="text-muted mt-1" style="font-size: 13px;">${(file.size / 1024).toFixed(1)} KB • ${ext.toUpperCase()}</div>
      </div>
    `;
  }
  
  const previewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
  previewModal.show();
  
  // Clear input so selecting the same file again triggers change event
  input.value = '';
}

function confirmFileUpload() {
  if (!selectedFileForUpload) return;
  if (!APP.activeChatId) {
    confirmModern('Chat belum aktif. Silakan pilih percakapan dulu lalu upload ulang.', 'Upload Gagal', 'danger', true);
    selectedFileForUpload = null;
    return;
  }
  
  const file = selectedFileForUpload;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('chat_id', APP.activeChatId);
  formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

  const btn = $('#btnConfirmUpload');
  const originalHtml = btn.html();
  btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm text-white" style="width: 20px; height: 20px;"></span>');

  $.ajax({
    url: 'api/chat/upload',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res) {
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('filePreviewModal')).hide();
        if (res.message) {
          appendMessage(res.message);
          APP.lastMsgId = Math.max(APP.lastMsgId || 0, parseInt(res.message.id));
          scrollToBottom();
        } else {
          pollMessages();
        }
        pollChatList();
      } else {
        confirmModern(res.error || 'Gagal mengupload file.', 'Upload Gagal', 'danger', true);
      }
    },
    error: function(xhr) {
      let err = xhr.responseJSON?.error;
      if (!err && xhr.responseText) {
        try {
          const parsed = JSON.parse(xhr.responseText);
          err = parsed?.error;
        } catch (_) {
          err = xhr.responseText.substring(0, 180);
        }
      }
      err = err || `Terjadi kesalahan saat upload (HTTP ${xhr.status || 0}).`;
      confirmModern(err, 'Kesalahan Sistem', 'danger', true);
    },
    complete: function() {
      btn.prop('disabled', false).html(originalHtml);
      selectedFileForUpload = null;
    }
  });
}

// ── Mention autocomplete (group chat) ───────────────────────
let groupParticipants = [];
let mentionQuery = '';
let mentionStart = -1;
let mentionActive = false;
let mentionIndex = 0;
let mentionFilteredUsers = [];

function initGroupMentions() {
  if (typeof APP === 'undefined' || APP.activeChatType !== 'group') return;

  if (Array.isArray(APP.groupParticipants) && APP.groupParticipants.length > 0) {
    groupParticipants = APP.groupParticipants.filter(p => p && p.username);
  }

  if (APP.activeChatId && groupParticipants.length === 0) {
    fetch('api/chat/' + APP.activeChatId + '/participants')
      .then(r => {
        if (!r.ok) throw new Error('participants fetch failed');
        return r.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          groupParticipants = data.filter(p => p && p.username);
        }
      })
      .catch(() => {});
  }

  document.addEventListener('click', (e) => {
    if (!mentionActive) return;
    const dd = document.getElementById('mentionDropdown');
    const input = document.getElementById('messageInput');
    if (dd && !dd.contains(e.target) && input && e.target !== input) {
      hideMentionDropdown();
    }
  });
}

function handleMentionInput(textarea) {
  if (typeof APP === 'undefined' || APP.activeChatType !== 'group') return;
  if (!textarea) return;

  const val = textarea.value;
  const cursor = textarea.selectionStart;
  const textBefore = val.substring(0, cursor);
  const atIdx = textBefore.lastIndexOf('@');

  if (atIdx === -1) {
    hideMentionDropdown();
    return;
  }

  const fragment = textBefore.substring(atIdx + 1);
  if (/\s/.test(fragment)) {
    hideMentionDropdown();
    return;
  }

  mentionStart = atIdx;
  mentionQuery = fragment.toLowerCase();
  mentionActive = true;
  mentionIndex = 0;

  const q = mentionQuery;
  mentionFilteredUsers = groupParticipants.filter(p => {
    const name = (p.name || '').toLowerCase();
    const user = (p.username || '').toLowerCase();
    return name.includes(q) || user.includes(q);
  });

  if (mentionFilteredUsers.length === 0) {
    hideMentionDropdown();
    return;
  }
  renderMentionDropdown(mentionFilteredUsers);
}

function renderMentionDropdown(users) {
  const dd = document.getElementById('mentionDropdown');
  if (!dd) return;

  dd.innerHTML = '';
  users.forEach((u, i) => {
    const item = document.createElement('div');
    item.className = 'mention-item' + (i === mentionIndex ? ' active' : '');
    item.dataset.username = u.username;
    item.dataset.name = u.name || '';

    const bg = u.avatar_url ? 'none' : avatarColor(parseInt(u.id, 10) || 0);
    item.innerHTML = `
      <div class="mention-avatar" style="background:${bg}; position:relative;">
        ${u.avatar_url
          ? `<img src="${u.avatar_url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`
          : escapeHtml(getInitials(u.name || u.username))}
      </div>
      <div>
        <div class="mention-name">${escapeHtml(u.name || u.username)}</div>
        <div class="mention-username">@${escapeHtml(u.username)}</div>
      </div>`;
    item.addEventListener('mousedown', (e) => {
      e.preventDefault();
      insertMention(u.username);
    });
    dd.appendChild(item);
  });
  dd.style.display = 'block';
}

function hideMentionDropdown() {
  mentionActive = false;
  mentionFilteredUsers = [];
  const dd = document.getElementById('mentionDropdown');
  if (dd) dd.style.display = 'none';
}

function insertMention(username) {
  const textarea = document.getElementById('messageInput');
  if (!textarea || !username) return;

  const val = textarea.value;
  const cursor = textarea.selectionStart;
  const before = val.substring(0, mentionStart);
  const after = val.substring(cursor);
  textarea.value = before + '@' + username + ' ' + after;
  const newPos = before.length + username.length + 2;
  textarea.setSelectionRange(newPos, newPos);
  hideMentionDropdown();
  textarea.focus();
  autoResize(textarea);
}

function handleKeydown(e) {
  if (mentionActive && APP.activeChatType === 'group') {
    const dd = document.getElementById('mentionDropdown');
    const items = dd ? dd.querySelectorAll('.mention-item') : [];

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      mentionIndex = Math.min(mentionIndex + 1, items.length - 1);
      items.forEach((el, i) => el.classList.toggle('active', i === mentionIndex));
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      mentionIndex = Math.max(mentionIndex - 1, 0);
      items.forEach((el, i) => el.classList.toggle('active', i === mentionIndex));
      return;
    }
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      const active = items[mentionIndex];
      if (active && active.dataset.username) {
        insertMention(active.dataset.username);
      }
      return;
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      hideMentionDropdown();
      return;
    }
    if (e.key === 'Tab' && items.length > 0) {
      e.preventDefault();
      const active = items[mentionIndex];
      if (active && active.dataset.username) {
        insertMention(active.dataset.username);
      }
      return;
    }
  }

  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function autoResize(el) {
  if (!el) return;
  el.style.height = 'auto';
  const newHeight = el.scrollHeight;
  el.style.height = (newHeight > 120 ? 120 : newHeight) + 'px';
  // If it exceeds max-height, show scrollbar
  el.style.overflowY = newHeight > 120 ? 'auto' : 'hidden';
}

function scrollToBottom(forceInstant = false) {
  const area = document.getElementById('messagesArea');
  if (area) {
    area.scrollTo({
      top: area.scrollHeight,
      behavior: forceInstant ? 'auto' : 'smooth'
    });
  }
}

// ─── Polling ───
function startMessagePolling() {
  clearInterval(pollTimer);
  if (!APP.activeChatId) return;
  pollTimer = setInterval(pollMessages, POLL_INTERVAL);
}

function pollMessages() {
  if (!APP.activeChatId) return;
  if (isLoading || pendingOptimisticMsgId) return;
  $.ajax({
    url: `api/messages/${APP.activeChatId}`,
    cache: false,
    data: { since: APP.lastMsgId, _ts: Date.now() },
    success: function (res) {
      if (res.messages && res.messages.length > 0) {
        res.messages.forEach(msg => {
          appendMessage(msg);
          APP.lastMsgId = Math.max(APP.lastMsgId, parseInt(msg.id));
        });
        scrollToBottom();
      }
      if (res.chat) updateChatHeader(res.chat);
    }
  });
}

function startChatListPolling() {
  clearInterval(chatListTimer);
  chatListTimer = setInterval(pollChatList, CHAT_POLL);
}

function pollChatList() {
  $.ajax({
    url: 'api/chats',
    cache: false,
    data: { filter: APP.filter, _ts: Date.now() },
    success: function (chats) { updateSidebarList(chats); }
  });
}

function updateSidebarList(chats) {
  // Update time labels, status badges, and ordering without full DOM replace
  const chatListEl = document.getElementById('chatList');
  if (!chatListEl || !Array.isArray(chats)) return;

  const relativeTimeText = function(dt) {
    if (!dt) return 'baru saja';
    const now = new Date();
    const t = new Date(dt);
    if (isNaN(t.getTime())) return 'baru saja';
    const diffSec = Math.floor((now - t) / 1000);
    if (diffSec < 60) return 'baru saja';
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin} menit lalu`;
    const diffHour = Math.floor(diffMin / 60);
    if (diffHour < 24) return `${diffHour} jam lalu`;
    const diffDay = Math.floor(diffHour / 24);
    if (diffDay < 7) return `${diffDay} hari lalu`;
    return t.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
  };

  chats.forEach(chat => {
    const item = $(`.chat-item-wrap[data-chat-id="${chat.id}"]`);
    if (item.length) {
      const isResponded = parseInt(chat.is_responded || 0, 10) === 1;
      const waitingBadge = isResponded
        ? '<span class="chat-status-badge badge-responded"><i class="bi bi-reply-fill"></i> Sudah Direspons</span>'
        : '<span class="chat-status-badge badge-waiting"><i class="bi bi-clock"></i> Waiting</span>';
      const badgeMap = {
        waiting:     waitingBadge,
        in_progress: '<span class="chat-status-badge badge-inprogress"><i class="bi bi-arrow-repeat"></i> Permintaan ditangani Tim IT</span>',
        done:        '<span class="chat-status-badge badge-done"><i class="bi bi-check-circle"></i> Done</span>',
      };
      item.find('.chat-item-bottom').html(badgeMap[chat.status] || '');
      item.find('.chat-item-time').text(relativeTimeText(chat.last_msg_time || chat.created_at));

      // Update Zoom Time if it exists
      if (chat.zoom_start && chat.zoom_end) {
        const s = new Date(chat.zoom_start);
        const e = new Date(chat.zoom_end);
        const optDay = { day: '2-digit', month: 'short' };
        const optTime = { hour: '2-digit', minute: '2-digit', hour12: false };
        const startDay = s.toLocaleDateString('id-ID', optDay);
        const endDay = e.toLocaleDateString('id-ID', optDay);
        const startTime = s.toLocaleTimeString('id-ID', optTime);
        const endTime = e.toLocaleTimeString('id-ID', optTime);
        let rangeStr = (startDay === endDay) 
          ? `${startDay}, ${startTime} - ${endTime}`
          : `${startDay} ${startTime} - ${endDay} ${endTime}`;
        let bodyEl = item.find('.chat-item-body');
        let badgeWrap = bodyEl.find('.zoom-badge-wrap');
        if (!badgeWrap.length) {
          item.find('.chat-item-title').after(`<div class="mt-1 mb-2 zoom-badge-wrap"><span class="zoom-time-badge"><i class="bi bi-calendar-check-fill"></i> ${rangeStr}</span></div>`);
        } else {
          badgeWrap.find('.zoom-time-badge').html(`<i class="bi bi-calendar-check-fill"></i> ${rangeStr}`);
        }
      }
    }
  });

  // Prevent native browser tooltip on sidebar items (caused by title attributes)
  $('#chatList .chat-item, #chatList .chat-item *').removeAttr('title');

  // Reorder list to follow backend sort (latest activity first)
  chats.forEach(chat => {
    const node = chatListEl.querySelector(`.chat-item-wrap[data-chat-id="${chat.id}"]`);
    if (node) chatListEl.appendChild(node);
  });
}

function appendMessage(msg) {
  const area = document.getElementById('messagesArea');
  if (!area) return;
  const msgId = msg.id ? String(msg.id) : (msg.clientMsgId ? String(msg.clientMsgId) : '');
  if (!msgId) return;

  const isMeEarly = parseInt(msg.sender_id) === parseInt(APP.currentUser.id);
  if (msg.id && isMeEarly) {
    removePendingOutgoingByText(area, msg.message);
  }
  if (area.querySelector(`.msg-bubble[data-msg-id="${msgId}"]`)) {
    if (msg.id) dedupeMessageBubbles(area, msgId);
    return;
  }
  const isPending = !!msg.clientMsgId && !msg.id;
  const outgoingTextAttr = isPending
    ? ` data-outgoing-text="${escapeHtml(normalizeMsgText(msg.message))}"`
    : '';

  const isMe = parseInt(msg.sender_id) === parseInt(APP.currentUser.id);
  const time = formatTime(msg.created_at);
  const avatarUrl = msg.sender_avatar_url;
  const color = avatarUrl ? 'none' : avatarColor(parseInt(msg.sender_id));
  const isTek = ['teknisi', 'admin'].includes(msg.sender_role);

  // Build reply preview HTML if this message is a reply
  let replyHtml = '';
  if (msg.reply_to_id && msg.reply_message) {
    const previewText = msg.reply_message.length > 80
      ? msg.reply_message.substring(0, 80) + '…'
      : msg.reply_message;
    const replyClass = isMe ? 'reply-preview' : 'reply-preview';
    replyHtml = `<div class="${replyClass}" onclick="scrollToMessage(${msg.reply_to_id})">
      <div class="reply-preview-sender">${escapeHtml(msg.reply_sender_name || '')}</div>
      <div class="reply-preview-text">${escapeHtml(previewText)}</div>
    </div>`;
  }

  // Render @mentions as highlighted spans
  function renderMentionsJS(text) {
    if (typeof APP === 'undefined' || APP.activeChatType !== 'group') return text;
    return text.replace(/@([\w.\-]+)/g, '<span class="mention-highlight">@$1</span>');
  }

  let html = '';
  const msgText = msg.message ? msg.message.trim() : '';
  const isRequestCard = msgText.includes('Permintaan Layanan Baru:');
  const isZoomCard = msgText.includes('Permintaan Zoom Meeting');
  const sysStarters = ['✅', '🔧', '⚠️', 'ℹ', '[SYSTEM_CARD]'];
  const starter = sysStarters.find(s => msgText.startsWith(s));
  const isSystem = isRequestCard || isZoomCard || !!starter;
  
  let msgBodyHtml = '';
  let isFileMsg = msgText.startsWith('📎 [FILE]');
  let isImageFile = false;
  let fileUrl = '';
  
  if (isFileMsg) {
    const content = msgText.substring(9).trim();
    const parts = content.split('|');
    const name = parts[0] ? parts[0].trim() : 'File';
    fileUrl = parts[1] ? parts[1].trim() : '#';
    const ext = name.split('.').pop().toLowerCase();
    
    isImageFile = ['jpg', 'jpeg', 'png'].includes(ext);
    
    if (isImageFile) {
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
  } else if (!isSystem) {
    msgBodyHtml = renderMentionsJS(escapeHtml(msg.message).replace(/\n/g, '<br>'));
  }

  const bubbleContentHtml = isFileMsg
    ? msgBodyHtml
    : `<p class="mb-0">${msgBodyHtml}</p>`;

  if (isSystem) {
    if (isRequestCard || isZoomCard) {
      html = renderRequestCardJS(msgText);
    } else {
      const cleanText = msgText.substring(starter ? starter.length : 0).trim();
      let sysHtml = escapeHtml(cleanText).replace(/\n/g, '<br>');
      if (cleanText.includes(' — ')) {
        const dashIdx = cleanText.indexOf(' — ');
        sysHtml = escapeHtml(cleanText.substring(0, dashIdx)).replace(/\n/g, '<br>')
                + ' — <strong>' + escapeHtml(cleanText.substring(dashIdx + 3)).replace(/\n/g, '<br>') + '</strong>';
      }
      html = `<div class="msg-system"><i class="bi bi-info-circle me-2"></i>${sysHtml}</div>`;
    }
  } else if (isMe) {
    const canReply = typeof APP !== 'undefined' && APP.activeChatType === 'group';
    const replyPreview = (msg.message || '').substring(0, 60).replace(/'/g, "\\'");
    const replyBtn = canReply && msg.id
      ? `<button type="button" class="btn-reply-msg" onclick="startReply(${msgId}, '${escapeHtml(APP.currentUser.name).replace(/'/g, "\\'")}', '${escapeHtml(replyPreview)}')" title="Balas"><i class="bi bi-reply-fill"></i></button>`
      : '';
    const actionsHtml = replyBtn ? `<div class="msg-actions">${replyBtn}</div>` : '';
    html = `
      <div class="msg-row msg-right">
        <div class="msg-content">
          <div class="msg-bubble bubble-me" data-msg-id="${msgId}" data-sender-name="${escapeHtml(APP.currentUser.name)}"${isPending ? ' data-pending="1"' : ''}${outgoingTextAttr}>
            ${replyHtml}
            <div class="d-flex justify-content-between align-items-start gap-2 msg-body-row">
              <div class="flex-grow-1" style="min-width:0;">${bubbleContentHtml}</div>
              ${actionsHtml}
            </div>
            <div class="msg-time">${time}</div>
          </div>
        </div>
        <div class="msg-sender-avatar ms-2" style="background:${APP.currentUser.avatar_url ? 'none' : avatarColor(parseInt(APP.currentUser.id))}">
          ${APP.currentUser.avatar_url ? `<img src="${APP.currentUser.avatar_url}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">` : escapeHtml(APP.currentUser.avatar || getInitials(APP.currentUser.name))}
        </div>
      </div>`;
  } else {
    const canReply = typeof APP !== 'undefined' && APP.activeChatType === 'group';
    const replyPreview = (msg.message || '').substring(0, 60).replace(/'/g, "\\'");
    const replyBtn = canReply && msgId
      ? `<button type="button" class="btn-reply-msg" onclick="startReply(${msgId}, '${escapeHtml(msg.sender_name || '').replace(/'/g, "\\'")}', '${escapeHtml(replyPreview)}')" title="Balas"><i class="bi bi-reply-fill"></i></button>`
      : '';
    const actionsHtml = replyBtn ? `<div class="msg-actions">${replyBtn}</div>` : '';
    html = `
      <div class="msg-row msg-left">
        <div class="msg-sender-avatar" style="background:${color}">
          ${avatarUrl ? `<img src="${avatarUrl}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">` : escapeHtml(msg.sender_avatar || getInitials(msg.sender_name))}
        </div>
        <div class="msg-content">
          <div class="msg-sender-name">${escapeHtml(msg.sender_name)} ${isTek ? '<span class="tek-tag">Tim IT</span>' : ''}</div>
          <div class="msg-bubble bubble-other"${msgId ? ` data-msg-id="${msgId}" data-sender-name="${escapeHtml(msg.sender_name || '')}"` : ''}>
            ${replyHtml}
            <div class="d-flex justify-content-between align-items-start gap-2 msg-body-row">
              <div class="flex-grow-1" style="min-width:0;">${bubbleContentHtml}</div>
              ${actionsHtml}
            </div>
            <div class="msg-time">${time}</div>
          </div>
        </div>
      </div>`;
  }
  const typingEl = document.getElementById('typingIndicator');
  if (typingEl) typingEl.insertAdjacentHTML('beforebegin', html);
  else area.insertAdjacentHTML('beforeend', html);
  if (msg.id) dedupeMessageBubbles(area, msgId);
}

/**
 * Strip leading emoji/non-alpha characters from a label string.
 * Uses Array.from() to correctly handle multi-codepoint emoji (e.g. ⚠️, 🏷).
 */
function stripLeadingEmoji(str) {
  const chars = Array.from(str); // split by Unicode codepoints
  if (chars.length === 0) return str;
  // Remove leading characters that are not letters or digits
  let i = 0;
  while (i < chars.length && !/[a-zA-Z0-9]/.test(chars[i])) {
    i++;
  }
  return chars.slice(i).join('').trim();
}

/**
 * JS version of PHP renderRequestCard for consistent UI during polling
 */
function renderRequestCardJS(text) {
  // Normalize line endings and clean prefix markers
  let cleanText = text.replace('[SYSTEM_CARD]', '').replace(/^ℹ\s*/u, '').trim();
  let lines = cleanText.split(/\r?\n/);
  let firstLine = lines.shift() || '';
  let headerTitle = text.includes('Zoom') ? 'Permintaan Zoom Meeting' : 'Permintaan Layanan Baru';
  
  let html = `<div class="request-summary-card">
    <div class="request-summary-header"><i class="bi bi-info-circle-fill"></i><span>${headerTitle}</span></div>
    <div class="request-summary-body">`;
  
  let hasItems = false;
  lines.forEach(line => {
    line = line.trim();
    if (!line) return;

    // Split on first colon only to preserve values that contain colons (e.g. times)
    const colonIdx = line.indexOf(':');
    if (colonIdx === -1) return;

    hasItems = true;
    let labelWithEmoji = line.substring(0, colonIdx).trim();
    let value = line.substring(colonIdx + 1).trim();

    // Strip leading emoji/symbols using codepoint-aware method
    let label = stripLeadingEmoji(labelWithEmoji);
    if (!label) label = labelWithEmoji; // fallback if stripping removed everything

    let l = label.toLowerCase();
    if (l.includes('tiket')) return; // Skip rendering Tiket entirely!
    if (l.includes('link zoom')) return; // Hide Link Zoom field
    let icon = 'bi-dot';
    if (l.includes('nama')) icon = 'bi-person-fill';
    else if (l.includes('ruangan')) icon = 'bi-building';
    else if (l.includes('tipe')) icon = 'bi-cpu';
    else if (l.includes('titik')) icon = 'bi-geo-fill';
    else if (l.includes('perangkat')) icon = 'bi-laptop';
    else if (l.includes('merk')) icon = 'bi-tag-fill';
    else if (l.includes('asset')) icon = 'bi-hash';
    else if (l.includes('model')) icon = 'bi-printer';
    else if (l.includes('kendala')) icon = 'bi-exclamation-triangle';
    else if (l.includes('software')) icon = 'bi-app-indicator';
    else if (l.includes('aplikasi')) icon = 'bi-window-stack';
    else if (l.includes('modul')) icon = 'bi-puzzle-fill';
    else if (l.includes('detail')) icon = 'bi-card-text';
    else if (l.includes('tiket')) icon = 'bi-ticket-detailed-fill';

    let valueHtml = escapeHtml(value).replace(/\n/g, '<br>');
    if (l.includes('tiket')) {
      let idRaw = value.replace('#', '').trim();
      valueHtml = `<a href="?chat=${idRaw}&filter=all" class="btn btn-primary btn-sm rounded-pill px-3 py-1 mt-1 fw-bold" style="font-size: 11px;">
        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Permintaan
      </a>`;
    }

    html += `<div class="request-summary-item">
      <i class="bi ${icon}"></i>
      <div>
        <div class="request-summary-label">${escapeHtml(label)}</div>
        <div class="request-summary-value">${valueHtml}</div>
      </div>
    </div>`;
  });

  if (!hasItems) {
    return `<div class="msg-system"><i class="bi bi-info-circle me-2"></i>${escapeHtml(text).replace(/\n/g, '<br>')}</div>`;
  }

  html += `</div></div>`;
  return html;
}

function takeTicket(chatId) {
  $.ajax({
    url: `api/chat/take/${chatId}`,
    method: 'POST',
    success: function(res) {
      if (res.success) {
        showToast('Permintaan berhasil ditangani.');
        $('#takeTicketBar').remove();
        updateChatHeader({ status: 'in_progress' });
        pollMessages();
      }
    }
  });
}

function updateChatHeader(chat) {
  const isResponded = parseInt(chat.is_responded || 0, 10) === 1;
  const waitingBadge = isResponded
    ? `<span class="badge bg-warning text-dark px-3 py-2 fs-75"><i class="bi bi-reply-fill"></i> Sudah Direspons</span>`
    : `<span class="badge bg-secondary px-3 py-2 fs-75">⏳ Menunggu</span>`;
  const statusMap = {
    waiting:     waitingBadge,
    in_progress: `<span class="badge bg-primary px-3 py-2 fs-75"><i class="bi bi-arrow-repeat"></i> Permintaan ditangani Tim IT</span>`,
    done:        `<span class="badge bg-success px-3 py-2 fs-75"><i class="bi bi-check-circle"></i> Selesai</span>`,
  };
  $('#chatStatusBadge').html(statusMap[chat.status] || '');
  if (chat.status && chat.status !== 'waiting') {
    $('#takeTicketBar').remove();
  }

  // If newly done, show closed bar
  if (chat.type !== 'group' && chat.status === 'done') {
    clearInterval(pollTimer);
    const inputArea = document.getElementById('chatInputArea');
    if (inputArea) {
      inputArea.outerHTML = `<div class="chat-closed-bar"><i class="bi bi-check-circle-fill me-2 text-success"></i>Layanan ini telah selesai.</div>`;
    }
    $('.btn-close-chat').hide();
  }
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  CLOSE CHAT
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function closeChat(chatId) {
  confirmModern('Tutup permintaan ini? Chat akan ditandai sebagai Selesai.', 'Permintaan Diselesaikan').then(confirmed => {
    if (!confirmed) return;

    $.ajax({
      url:    `api/chat/close/${chatId}`,
      method: 'POST',
      success: function (res) {
        if (res.success) {
          showToast('Permintaan telah diselesaikan.');
          pollMessages(); // fetch the closing system message
        }
      },
      error: function () {
        showToast('Gagal menutup layanan.', 'danger');
      }
    });
  });
}

function closeGroupRequest(chatId, title = 'Permintaan') {
  confirmModern(`Selesaikan permintaan "${title}"?`, 'Permintaan Diselesaikan').then(confirmed => {
    if (!confirmed) return;

    $.ajax({
      url:    `api/chat/close/${chatId}`,
      method: 'POST',
      success: function (res) {
        if (res.success) {
          showToast('Permintaan telah diselesaikan.');
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        }
      },
      error: function () {
        showToast('Gagal menyelesaikan permintaan.', 'danger');
      }
    });
  });
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  UPDATE TECHNICIAN STATUS
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function updateMyStatus(status) {
  $.ajax({
    url:    'api/user/status',
    method: 'POST',
    data:   { status },
    success: function (res) {
      if (res.success) {
        // Update active button UI
        $('.status-btn').removeClass('active');
        $(`.status-btn[data-status="${status}"]`).addClass('active');
        showToast(`Status diubah: ${status}`);
      }
    },
    error: function () {
      showToast('Gagal mengubah status.', 'danger');
    }
  });
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  CHANGE PASSWORD
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let changePasswordModal = null;

function openChangePasswordModal() {
  if (!changePasswordModal) {
    changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
  }
  $('#formChangePassword')[0].reset();
  $('#changePasswordError, #changePasswordSuccess').addClass('d-none');
  changePasswordModal.show();
}

function submitChangePassword(e) {
  e.preventDefault();
  const form = $('#formChangePassword');
  const oldPwd = form.find('input[name="old_password"]').val();
  const newPwd = form.find('input[name="new_password"]').val();
  const confirmPwd = form.find('input[name="confirm_password"]').val();
  const btn = $('#btnSubmitChangePassword');
  const err = $('#changePasswordError');
  const success = $('#changePasswordSuccess');

  err.addClass('d-none');
  success.addClass('d-none');

  if (newPwd !== confirmPwd) {
    err.removeClass('d-none').text('Konfirmasi password tidak cocok.');
    return;
  }

  if (newPwd.length < 6) {
    err.removeClass('d-none').text('Password baru minimal 6 karakter.');
    return;
  }

  btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

  $.ajax({
    url: 'api/user/change-password',
    method: 'POST',
    data: { old_password: oldPwd, new_password: newPwd },
    success: function(res) {
      if (res.success) {
        success.removeClass('d-none');
        setTimeout(() => {
          changePasswordModal.hide();
          showToast('Password berhasil diubah.');
        }, 1500);
      }
    },
    error: function(xhr) {
      const msg = xhr.responseJSON?.error || 'Gagal mengubah password.';
      err.removeClass('d-none').text(msg);
    },
    complete: function() {
      btn.prop('disabled', false).text('Simpan Perubahan');
    }
  });
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  UTILITIES
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function formatTime(dt) {
  const d = new Date(dt);
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}

function getInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(' ');
  return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}

function avatarColor(id) {
  const colors = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
  return colors[id % colors.length];
}

function getIconForLabel(label, emoji) {
  label = label.toLowerCase();
  if (label.includes('nama')) return 'bi-person-fill';
  if (label.includes('ruangan')) return 'bi-building';
  if (label.includes('tipe')) return 'bi-cpu';
  if (label.includes('titik')) return 'bi-geo-fill';
  if (label.includes('perangkat')) return 'bi-laptop';
  if (label.includes('merk')) return 'bi-tag-fill';
  if (label.includes('asset')) return 'bi-hash';
  if (label.includes('model')) return 'bi-printer';
  if (label.includes('kendala')) return 'bi-exclamation-triangle';
  if (label.includes('software')) return 'bi-app-indicator';
  if (label.includes('aplikasi')) return 'bi-window-stack';
  if (label.includes('modul')) return 'bi-puzzle-fill';
  if (label.includes('detail')) return 'bi-card-text';
  return 'bi-dot';
}

/**
 * Modern Confirmation Dialog
 * type: 'primary' | 'danger'
 * hideCancel: boolean (if true, acts like an alert)
 */
function confirmModern(message, title = 'Konfirmasi', type = 'primary', hideCancel = false) {
  return new Promise((resolve) => {
    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) {
      // Last resort fallback
      console.warn('Modern modal missing, using native confirm');
      resolve(confirm(message));
      return;
    }
    
    const modal = new bootstrap.Modal(modalEl);
    let confirmed = false;

    $('#confirmTitle').text(title);
    $('#confirmMessage').text(message);
    
    const iconWrap = $('#confirmIconWrap');
    const icon     = $('#confirmIcon');
    const btnYes   = $('#confirmBtnYes');
    const btnNo    = $(modalEl).find('.btn-light');
    
    if (hideCancel) btnNo.hide();
    else btnNo.show();
    
    iconWrap.removeClass('danger');
    btnYes.removeClass('btn-danger').addClass('btn-primary');
    icon.attr('class', 'bi bi-question-circle');
    
    if (type === 'danger') {
      iconWrap.addClass('danger');
      btnYes.removeClass('btn-primary').addClass('btn-danger');
      icon.attr('class', 'bi bi-exclamation-triangle');
    }
    
    btnYes.off('click').on('click', function() {
      confirmed = true;
      modal.hide();
    });
    
    modalEl.addEventListener('hidden.bs.modal', function() {
      resolve(confirmed);
    }, { once: true });
    
    modal.show();
  });
}

/**
 * Modern Alert Dialog (Single Button)
 */
function alertModern(message, title = 'Informasi', type = 'primary') {
  return confirmModern(message, title, type, true);
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  ADMIN PANEL
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// SIL2020 User Search — auto-load all on modal open, filter client-side
let silAllUsers = [];

$('#addTeamModal').on('show.bs.modal', function () {
  silAllUsers = [];
  $('#silUserSearch').val('');
  $('#silSearchResults').html(
    '<div class="text-center py-4">' +
    '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
    '<div class="text-muted small mt-2">Memuat data dari SIL2020...</div>' +
    '</div>'
  );

  $.ajax({
    url: 'api/admin/sil-users/search',
    data: { q: '' },
    success: function(res) {
      silAllUsers = res.users || [];
      renderSilUserList(silAllUsers);
    },
    error: function() {
      $('#silSearchResults').html(
        '<div class="alert alert-danger m-2 py-2 small">' +
        '<i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat data SIL2020.' +
        '</div>'
      );
    }
  });
});

// Instant client-side filter as user types
$(document).on('input', '#silUserSearch', function() {
  const q = $(this).val().trim().toLowerCase();
  if (!silAllUsers.length) return;

  const filtered = q.length === 0
    ? silAllUsers
    : silAllUsers.filter(u =>
        (u.nama_user || '').toLowerCase().includes(q) ||
        (u.username || '').toLowerCase().includes(q)
      );

  renderSilUserList(filtered);
});

function renderSilUserList(users) {
  if (!users || users.length === 0) {
    $('#silSearchResults').html('<div class="text-center py-4 text-muted small">Tidak ada user ditemukan.</div>');
    return;
  }

  let html = '<div class="sil-user-count"><i class="bi bi-people-fill me-1"></i>' + users.length + ' akun tersedia</div>';
  users.forEach(u => {
    const name = u.nama_user || u.username || '?';
    const parts = name.trim().split(' ');
    const initials = (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
    const bg = avatarColor(u.id_user);
    html += '<div class="sil-user-item">' +
      '<div class="sil-user-avatar" style="background:' + bg + '">' +
        escapeHtml(initials) +
        (u.avatar_url ? '<img src="' + u.avatar_url + '" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit; position:absolute; inset:0;" onerror="this.remove()">' : '') +
      '</div>' +
      '<div class="sil-user-info">' +
        '<div class="sil-user-name">' + escapeHtml(name) + '</div>' +
        '<div class="sil-user-meta">@' + escapeHtml(u.username || '-') + '</div>' +
      '</div>' +
      '<button class="btn btn-primary btn-sm btn-add-team" onclick="addTeamMember(' + u.id_user + ', this)">' +
        '<i class="bi bi-plus-lg"></i>' +
      '</button>' +
    '</div>';
  });
  $('#silSearchResults').html(html);
}
// Add Team Member from SIL2020
function addTeamMember(silId, btn) {
  const $btn = $(btn);
  const oldText = $btn.text();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

  $.ajax({
    url: 'api/admin/team/add',
    method: 'POST',
    data: { sil_id: silId },
    success: function(res) {
      if (res.success) {
        $btn.removeClass('btn-primary').addClass('btn-success').text('Ditambahkan');
        showToast('Berhasil menambahkan anggota tim baru.');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        $btn.prop('disabled', false).text(oldText);
        showToast('Gagal menambahkan anggota tim.', 'danger');
      }
    },
    error: function() {
      $btn.prop('disabled', false).text(oldText);
      showToast('Terjadi kesalahan sistem.', 'danger');
    }
  });
}

// Reset CRUD Modal on hide
$('#createManualModal').on('hidden.bs.modal', function () {
  $('#formTechCrud')[0].reset();
  $('#crudTechId').val('');
  $('#crudError').addClass('d-none');
  $('#crudModalTitle').text('Buat Akun Teknisi Manual');
  $('#crudModalIcon').css('background', 'linear-gradient(135deg, #059669, #10b981)');
});

// Edit Button Click
$(document).on('click', '.btn-edit-tech', function() {
  const $btn = $(this);
  $('#crudTechId').val($btn.data('id'));
  $('#crudName').val($btn.data('name'));
  $('#crudUsername').val($btn.data('username'));
  $('#crudRole').val($btn.data('role'));
  
  $('#crudModalTitle').text('Edit Akun Teknisi');
  $('#crudModalIcon').css('background', 'linear-gradient(135deg, #4f46e5, #0ea5e9)');
  
  new bootstrap.Modal(document.getElementById('createManualModal')).show();
});

// Submit Create/Edit Form
function submitTechCrud(e) {
  e.preventDefault();
  const form = $('#formTechCrud');
  const id = $('#crudTechId').val();
  const url = id ? 'api/admin/team/update' : 'api/admin/team/create';
  const $btn = $('#crudSubmitBtn');
  
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
  $('#crudError').addClass('d-none');

  $.ajax({
    url: url,
    method: 'POST',
    data: form.serialize(),
    success: function(res) {
      if (res.success) {
        showToast(id ? 'Data berhasil diubah.' : 'Akun berhasil dibuat.');
        setTimeout(() => window.location.reload(), 1000);
      }
    },
    error: function(xhr) {
      const msg = xhr.responseJSON?.error || 'Gagal menyimpan data.';
      $('#crudError').removeClass('d-none').text(msg);
      $btn.prop('disabled', false).text('Simpan');
    }
  });
}

// Delete Team Member
$(document).on('click', '.btn-delete-tech', function() {
  const $btn = $(this);
  const id = $btn.data('id');
  const name = $btn.data('name');

  confirmModern(`Hapus ${name} dari daftar Tim IT?\n\nPeran mereka akan diubah menjadi user biasa.`, 'Hapus Anggota Tim?', 'danger').then(confirmed => {
    if (!confirmed) return;

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
      url: 'api/admin/team/delete',
      method: 'POST',
      data: { id: id },
      success: function(res) {
        if (res.success) {
          showToast('Berhasil menghapus anggota tim.');
          setTimeout(() => window.location.reload(), 1000);
        }
      },
      error: function(xhr) {
        const msg = xhr.responseJSON?.error || 'Gagal menghapus anggota tim.';
        showToast(msg, 'danger');
        $btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
      }
    });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
//  AUTO LOGOUT TRACKER (10 MINUTES)
//  Keep session alive while the tab is active and app polling keeps running.
// ─────────────────────────────────────────────────────────────────────────────
$(document).ready(function() {
  const INACTIVITY_LIMIT_MS = 10 * 60 * 1000; // 10 minutes
  const logoutUrl = 'logout';
  let inactivityTimer = null;
  let lastAppActivity = Date.now();
  let monitorTimer = null;

  function forceLogout() {
    if ($('#autoLogoutModal').hasClass('show')) return;
    // Call the logout route silently to destroy session on backend
    $.post(logoutUrl, function() {
      // Show the beautiful modal defined in dashboard.php
      $('#autoLogoutModal').addClass('show');
    }).fail(function() {
      // Even if AJAX fails, we force UI logout
      $('#autoLogoutModal').addClass('show');
    });
  }

  function markActivity() {
    lastAppActivity = Date.now();
    clearTimeout(inactivityTimer);
    if (!$('#autoLogoutModal').hasClass('show')) {
      inactivityTimer = setTimeout(forceLogout, INACTIVITY_LIMIT_MS);
    }
  }

  // User interactions count as activity.
  $(document).on('mousemove keydown scroll click touchstart', function() {
    markActivity();
  });

  // Any successful AJAX call (including polling) keeps session alive.
  $(document).ajaxSuccess(function() {
    markActivity();
  });

  // Periodic monitor: if app stays silent (no user + no ajax), then logout.
  monitorTimer = setInterval(function() {
    if (Date.now() - lastAppActivity > INACTIVITY_LIMIT_MS) {
      clearInterval(monitorTimer);
      forceLogout();
    }
  }, 30000);

  // Start tracker initially.
  markActivity();
});

function pinMessage(messageId) {
  $.post('api/chat/message/pin', { id: messageId }, function(res) {
    if (res && res.success) {
      showToast('Pesan berhasil disematkan.');
      setTimeout(() => window.location.reload(), 300);
      return;
    }
    showToast('Gagal menyematkan pesan.', 'danger');
  }).fail(function() {
    showToast('Gagal menyematkan pesan.', 'danger');
  });
}

function unpinMessage(messageId) {
  $.post('api/chat/message/unpin', { id: messageId }, function(res) {
    if (res && res.success) {
      showToast('Sematan pesan dilepas.');
      setTimeout(() => window.location.reload(), 300);
      return;
    }
    showToast('Gagal melepas sematan.', 'danger');
  }).fail(function() {
    showToast('Gagal melepas sematan.', 'danger');
  });
}

function pinChat(chatId) {
  $.post('api/chat/pin', { id: chatId }, function(res) {
    if (res && res.success) {
      showToast('Percakapan berhasil disematkan.');
      setTimeout(() => window.location.reload(), 300);
      return;
    }
    showToast((res && res.error) || 'Gagal menyematkan percakapan.', 'danger');
  }).fail(function(xhr) {
    const err = xhr.responseJSON && xhr.responseJSON.error;
    showToast(err || 'Gagal menyematkan percakapan.', 'danger');
  });
}

function unpinChat(chatId) {
  $.post('api/chat/unpin', { id: chatId }, function(res) {
    if (res && res.success) {
      showToast('Sematan percakapan dilepas.');
      setTimeout(() => window.location.reload(), 300);
      return;
    }
    showToast((res && res.error) || 'Gagal melepas sematan.', 'danger');
  }).fail(function(xhr) {
    const err = xhr.responseJSON && xhr.responseJSON.error;
    showToast(err || 'Gagal melepas sematan.', 'danger');
  });
}
