/**
 * ALISA — zoom_modal.js
 * Logika JS untuk Modal Formulir Permintaan Zoom Meeting.
 * File ini hanya di-load untuk role 'user'.
 */

// ── Instance Bootstrap Modal ──────────────────────────────────
let zoomRequestModal = null;

// ── Inisialisasi setelah DOM siap ────────────────────────────
$(function () {
  const zoomEl = document.getElementById('zoomRequestModal');
  if (zoomEl) {
    zoomRequestModal = new bootstrap.Modal(zoomEl);

    // Reset form tiap kali modal ditutup
    zoomEl.addEventListener('hidden.bs.modal', function () {
      resetZoomForm();
    });
  }

  // Set default dates to today
  const today = new Date().toISOString().split('T')[0];
  if ($('#zoomTanggalMulai').length) {
    $('#zoomTanggalMulai').val(today);
    $('#zoomTanggalSelesai').val(today);
  }

});

// ── Buka Zoom Modal ───────────────────────────────────────────
function openZoomRequestModal() {
  resetZoomForm();
  // Set default dates to today when opening
  const today = new Date().toISOString().split('T')[0];
  $('#zoomTanggalMulai').val(today);
  $('#zoomTanggalSelesai').val(today);
  if (zoomRequestModal) zoomRequestModal.show();
}

// ── Reset semua field di Zoom Modal ──────────────────────────
function resetZoomForm() {
  // Reset Request Type to default (Link + Ruangan)
  $('#zoomRequestType').val('link_tech');
  $('.zoom-type-card').removeClass('active');
  $('.zoom-type-card:first-child').addClass('active');

  // Reset Extra Fields
  $('#zoomExtraFields').hide();
  $('#zoomOwnId').val('');
  $('#zoomOwnPass').val('');

  // Reset Standard Fields
  const today = new Date().toISOString().split('T')[0];
  $('#zoomTanggalMulai').val(today);
  $('#zoomJamMulai').val('08:00');
  $('#zoomTanggalSelesai').val(today);
  $('#zoomJamSelesai').val('16:00');
  $('#zoomNamaKegiatan').val('');
  
  // Reset Room Selection
  $('#zoomSelectedRoom').val('');
  $('#zoomRoomSelectionSection').show();
  $('#zoomRoomGrid .room-card').removeClass('selected');
  
  $('#zoomFormError').hide().text('');
  $('#submitZoomBtn').prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim Permintaan');
}

// ── Ganti Tipe Permintaan Zoom ────────────────────────────────
function setZoomRequestType(el, type) {
  $('.zoom-type-card').removeClass('active');
  $(el).addClass('active');
  $('#zoomRequestType').val(type);

  // Logic visibility based on type
  if (type === 'link_only') {
    $('#zoomRoomSelectionSection').hide();
    $('#zoomExtraFields').hide();
    $('#zoomSelectedRoom').val('');
    $('#zoomRoomGrid .room-card').removeClass('selected');
  } else if (type === 'own_link') {
    $('#zoomRoomSelectionSection').show();
    $('#zoomExtraFields').show();
  } else {
    // link_tech (default)
    $('#zoomRoomSelectionSection').show();
    $('#zoomExtraFields').hide();
  }
  
  $('#zoomFormError').hide().text('');
}

// ── Pemilihan Ruangan (input "teknis") ────────────────────────
function zoomSelectRoom(el, room) {
  $('#zoomRoomGrid .room-card').removeClass('selected');
  $(el).addClass('selected');
  $('#zoomSelectedRoom').val(room);
  $('#zoomFormError').hide().text('');
}

// ── Gabungkan tanggal + jam menjadi datetime string ───────────
function buildDatetime(dateId, timeId) {
  const d = $(dateId).val().trim();
  const t = $(timeId).val().trim();
  if (!d || !t) return '';
  return d + 'T' + t;
}

// ── Submit Formulir Zoom ──────────────────────────────────────
function submitZoomRequest() {
  // 1. Ambil semua nilai field
  const requestType  = $('#zoomRequestType').val();
  const waktuMulai   = buildDatetime('#zoomTanggalMulai', '#zoomJamMulai');
  const waktuSelesai = buildDatetime('#zoomTanggalSelesai', '#zoomJamSelesai');
  const namaKegiatan = $('#zoomNamaKegiatan').val().trim();
  const teknis       = $('#zoomSelectedRoom').val();
  
  // Extra fields
  const ownId   = $('#zoomOwnId').val().trim();
  const ownPass = $('#zoomOwnPass').val().trim();

  // 2. Validasi
  const showErr = function(msg) {
    $('#zoomFormError').show().text(msg);
    return false;
  };

  if (!$('#zoomTanggalMulai').val()) return showErr('Tanggal Mulai wajib diisi.');
  if (!$('#zoomJamMulai').val())      return showErr('Jam Mulai wajib diisi.');
  if (!$('#zoomTanggalSelesai').val()) return showErr('Tanggal Selesai wajib diisi.');
  if (!$('#zoomJamSelesai').val())    return showErr('Jam Selesai wajib diisi.');
  if (waktuSelesai <= waktuMulai)     return showErr('Waktu Selesai harus setelah Waktu Mulai.');
  if (!namaKegiatan)                  return showErr('Nama Kegiatan wajib diisi.');
  
  // Validasi berdasarkan Tipe
  if (requestType === 'own_link') {
    if (!ownId)   return showErr('ID Meeting / Link Zoom wajib diisi untuk skenario ini.');
    if (!ownPass) return showErr('Passcode wajib diisi untuk skenario ini.');
  }

  if (requestType !== 'link_only') {
    if (!teknis) {
      return showErr('Silakan pilih Ruangan terlebih dahulu.');
    }
  }

  // 3. Disable tombol kirim, tampilkan spinner
  const $btn = $('#submitZoomBtn');
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim…');
  $('#zoomFormError').hide().text('');

  const roomLabel = teknis ? ' (' + teknis + ')' : ' (Tanpa Ruangan)';
  const title     = '[Zoom] ' + namaKegiatan + roomLabel;

  $.ajax({
    url:    'api/chat/create',
    method: 'POST',
    data:   { 
      title: title, 
      category: 'Zoom Meeting',
      room: teknis,
      name: $('#zoomRequestUserName').val(), // Fallback if name field exists
      group_id: (APP.activeChatType === 'group') ? APP.activeChatId : null
    },
    success: function (res) {
      if (!res.success) {
        showErr(res.error || 'Gagal membuat permintaan.');
        $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim Permintaan');
        return;
      }

      const chatId = res.chat_id;

      $.ajax({
        url:    'api/zoom/schedule',
        method: 'POST',
        data: {
          chat_id:       chatId,
          request_type:  requestType,
          waktu_mulai:   waktuMulai,
          waktu_selesai: waktuSelesai,
          nama_kegiatan: namaKegiatan,
          teknis:        teknis,
          id_meeting:    ownId,
          passcode:      ownPass,
          butuh_link:    (requestType !== 'own_link' ? 1 : 0) // butuh_link legacy field
        },
        success: function (schedRes) {
          if (zoomRequestModal) zoomRequestModal.hide();
          showToast('Permintaan Zoom berhasil dikirim! 🎉');
          window.location.href = '?chat=' + chatId + '&filter=all';
        },
        error: function (xhr) {
          const err = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyimpan detail Zoom.';
          showErr(err);
          $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim Permintaan');
        }
      });
    },
    error: function (xhr) {
      const err = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal membuat permintaan.';
      showErr(err);
      $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Kirim Permintaan');
    }
  });
}

// Global Digital Time Picker Logic
let activeTimeInputId = null;
let selectOptionsReady = false;
let selectedHour = "08";
let selectedMinute = "00";

function sanitizeTimePart(rawVal, maxVal, fallbackVal) {
  const num = parseInt(rawVal, 10);
  if (Number.isNaN(num)) return fallbackVal;
  if (num < 0) return "00";
  if (num > maxVal) return maxVal.toString().padStart(2, "0");
  return num.toString().padStart(2, "0");
}

function openGlobalTimePicker() {
  initializeTimeSelectOptions();
  $("#globalTimePicker").addClass("show");
}

function closeGlobalTimePicker() {
  $("#globalTimePicker").removeClass("show");
}

function saveGlobalTimePicker() {
  const h = sanitizeTimePart(selectedHour, 23, "08");
  const m = sanitizeTimePart(selectedMinute, 59, "00");
  setActiveTime(h, m);
  if (activeTimeInputId) {
    $("#" + activeTimeInputId).val(`${h}:${m}`);
  }
  closeGlobalTimePicker();
}

function initializeTimeSelectOptions() {
  if (selectOptionsReady) return;
  const $hourList = $("#hourChoiceList");
  const $minuteList = $("#minuteChoiceList");
  if (!$hourList.length || !$minuteList.length) return;
  for (let i = 0; i < 24; i++) {
    const val = i.toString().padStart(2, "0");
    $hourList.append(`<button type="button" class="time-choice-btn" data-hour="${val}">${val}</button>`);
  }
  for (let i = 0; i < 60; i++) {
    const val = i.toString().padStart(2, "0");
    $minuteList.append(`<button type="button" class="time-choice-btn" data-minute="${val}">${val}</button>`);
  }
  selectOptionsReady = true;
}

function setActiveTime(h, m) {
  selectedHour = sanitizeTimePart(h, 23, "08");
  selectedMinute = sanitizeTimePart(m, 59, "00");
  $("#globalHourSelect").val(selectedHour);
  $("#globalMinuteSelect").val(selectedMinute);
  $("#hourChoiceList .time-choice-btn").removeClass("active");
  $("#minuteChoiceList .time-choice-btn").removeClass("active");
  $(`#hourChoiceList .time-choice-btn[data-hour="${selectedHour}"]`).addClass("active");
  $(`#minuteChoiceList .time-choice-btn[data-minute="${selectedMinute}"]`).addClass("active");
}

$(document).on("click", "#globalTimePicker", function(e) {
  if (e.target === this) closeGlobalTimePicker();
});

$(document).on("click", ".time-picker-input", function() {
  activeTimeInputId = $(this).attr("id");
  initializeTimeSelectOptions();
  const currentVal = $(this).val() || "08:00";
  const parts = currentVal.split(":");
  const h = sanitizeTimePart(parts[0], 23, "08");
  const m = sanitizeTimePart(parts[1], 59, "00");
  setActiveTime(h, m);
  openGlobalTimePicker();
});

$(document).on("click", "#hourChoiceList .time-choice-btn", function() {
  setActiveTime($(this).data("hour"), selectedMinute);
});

$(document).on("click", "#minuteChoiceList .time-choice-btn", function() {
  setActiveTime(selectedHour, $(this).data("minute"));
});
