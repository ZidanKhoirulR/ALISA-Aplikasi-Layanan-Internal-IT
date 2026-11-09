<?php
// ============================================
// app/views/components/admin_groups.php
// ============================================
?>

<div class="admin-groups-container p-4 h-100 overflow-y-auto">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-800 mb-1">Manajemen Grup Chat</h4>
      <p class="text-muted small mb-0">Buat dan kelola grup percakapan internal tim</p>
    </div>
    <button class="btn btn-primary px-4 rounded-pill fw-bold d-flex align-items-center gap-2" onclick="openCreateGroupModal()">
      <i class="bi bi-plus-lg"></i> Buat Grup Baru
    </button>
  </div>

  <div id="groupsLoading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted">Memuat daftar grup...</p>
  </div>

  <div id="groupsList" class="row g-4" style="display:none">
    <!-- Groups will be loaded here -->
  </div>

  <div id="groupsEmpty" class="text-center py-5" style="display:none">
    <div class="mb-3" style="font-size: 48px; opacity: 0.2;"><i class="bi bi-chat-square-dots"></i></div>
    <h5 class="text-muted">Belum ada grup chat</h5>
    <p class="text-muted small">Mulai buat grup pertama untuk kolaborasi tim</p>
  </div>
</div>

<!-- Modal: Create Group -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-modern border-0">
      <div class="modal-header border-0 pb-0">
        <div class="modal-icon-wrap" style="background: linear-gradient(135deg, #4f46e5, #06b6d4);">
          <i class="bi bi-people-fill"></i>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <h5 class="modal-title-text">Buat Grup Chat Baru</h5>
        <p class="modal-desc text-muted small">Buat grup koordinasi dan pilih anggota tim IT atau user ALISA</p>

        <div class="mb-3 mt-3 d-flex gap-3 align-items-start">
          <div class="group-icon-upload-wrap text-center">
            <div id="groupIconPreview" class="group-icon-preview">
              <i class="bi bi-people-fill"></i>
            </div>
            <label class="btn btn-sm btn-light rounded-pill mt-2 fw-700" style="font-size:10px;cursor:pointer">
              <i class="bi bi-image me-1"></i> Icon Grup
              <input type="file" id="groupIconFile" accept="image/jpeg,image/png,image/webp" class="d-none">
            </label>
            <button type="button" class="btn btn-link btn-sm text-danger p-0 mt-1" id="btnRemoveGroupIcon" style="display:none;font-size:10px" onclick="clearGroupIcon()">Hapus icon</button>
          </div>
          <div class="flex-grow-1">
            <label class="form-label small fw-700 text-muted text-uppercase mb-2">Nama Grup</label>
            <input type="text" id="groupTitle" class="form-control form-control-lg rounded-3 border-light-subtle" placeholder="Contoh: Koordinasi Jaringan / Tim Selulosa" style="font-size: 16px;">
          </div>
        </div>
        
        <div class="mb-4">
          <label class="form-label small fw-700 text-muted text-uppercase mb-2">Keterangan / Catatan Grup</label>
          <textarea id="groupDescription" class="form-control rounded-3 border-light-subtle" rows="2" placeholder="Contoh: Grup koordinasi untuk penanganan jaringan di gedung utama..." style="font-size: 14px;"></textarea>
        </div>

        <div id="selectedChipsContainer" class="mb-3 d-flex flex-wrap gap-2" style="min-height: 32px;">
          <!-- Selected member chips will appear here -->
        </div>

        <div class="mb-2 d-flex justify-content-between align-items-center">
          <label class="form-label small fw-700 text-muted text-uppercase mb-0">Pilih Anggota</label>
        </div>

        <div class="search-box-modern mb-3">
          <i class="bi bi-search"></i>
          <input type="text" id="userSearch" class="form-control form-control-lg" placeholder="Cari nama atau username..." autocomplete="off">
        </div>

        <div class="user-selection-list border rounded-4 overflow-hidden mb-4" style="max-height: 400px; min-height: 200px; overflow-y: auto; background: #fcfdfe;">
          <div id="usersLoading" class="text-center py-5" style="display:none">
            <div class="spinner-border text-primary"></div>
          </div>
          <div id="usersList">
            <!-- Users will be loaded here -->
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-2">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-700" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary px-5 rounded-pill fw-800" onclick="submitCreateGroup()" id="btnSubmitGroup">Buat Grup</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.group-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  background: white;
  position: relative;
  overflow: hidden;
}
.group-icon {
  width: 54px; height: 54px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; color: white;
  box-shadow: 0 4px 12px rgba(79,70,229,0.25);
  overflow: hidden;
  flex-shrink: 0;
}
.group-icon img { width: 100%; height: 100%; object-fit: cover; }
.group-icon-preview {
  width: 72px; height: 72px;
  border-radius: 18px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  overflow: hidden;
  margin: 0 auto;
}
.group-icon-preview img { width: 100%; height: 100%; object-fit: cover; }
.group-icon-upload-wrap { flex-shrink: 0; }
.user-item {
  padding: 12px 20px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; align-items: center; gap: 14px;
}
.user-item:last-child { border-bottom: 0; }
.user-item:hover { background: #f8fafc; transform: translateX(4px); }
.user-item.selected { 
  background: #eff6ff; 
  border-left: 4px solid var(--primary);
  padding-left: 16px;
}
.user-item .form-check-input { 
  width: 20px; height: 20px; cursor: pointer; 
  margin-top: 0;
}

.fw-800 { font-weight: 800; }
.fw-700 { font-weight: 700; }

.user-item-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 50px;
  text-transform: uppercase;
}
.selected-chip {
  padding: 4px 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  animation: fadeInScale 0.2s ease-out;
}
.selected-chip.sil {
  background: #fffbeb;
  border-color: #fef3c7;
  color: #b45309;
}
.selected-chip i {
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.2s;
}
.selected-chip i:hover { opacity: 1; }

@keyframes fadeInScale {
  0% { opacity: 0; transform: scale(0.9); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(100px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideOut {
  from { opacity: 1; transform: translateX(0); }
  to { opacity: 0; transform: translateX(100px); }
}

.custom-checkbox .form-check-input:checked + .form-check-label {
  color: #4f46e5;
}
.fw-600 { font-weight: 600; }
</style>

<script>
// Simple toast notification function
function showToast(message, type = 'success') {
  const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✗';
  const color = type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#ef4444';
  
  const toast = document.createElement('div');
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${color};
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 10000;
    animation: slideIn 0.3s ease;
  `;
  toast.innerHTML = `${icon} ${message}`;
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

let allUsers = []; // Local users
let silUsers = []; // Remote users from search
let selectedUsers = new Set(); // Local IDs
let selectedSilUsers = new Set(); // SIL IDs

let currentEditingGroupId = null;
let groupIconRemove = false;
let pendingGroupIconFile = null;

function setGroupIconPreview(urlOrNull, isDefault = false) {
  const box = document.getElementById('groupIconPreview');
  const btnRemove = document.getElementById('btnRemoveGroupIcon');
  if (urlOrNull && !isDefault) {
    box.innerHTML = `<img src="${urlOrNull}" alt="icon">`;
    btnRemove.style.display = 'inline-block';
  } else {
    box.innerHTML = '<i class="bi bi-people-fill"></i>';
    btnRemove.style.display = 'none';
  }
}

function clearGroupIcon() {
  pendingGroupIconFile = null;
  groupIconRemove = true;
  document.getElementById('groupIconFile').value = '';
  setGroupIconPreview(null, true);
}

document.getElementById('groupIconFile').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran icon maksimal 2MB.');
    e.target.value = '';
    return;
  }
  pendingGroupIconFile = file;
  groupIconRemove = false;
  setGroupIconPreview(URL.createObjectURL(file));
});

function openCreateGroupModal() {
  currentEditingGroupId = null;
  pendingGroupIconFile = null;
  groupIconRemove = false;
  document.querySelector('#createGroupModal .modal-title-text').innerText = 'Buat Grup Chat Baru';
  document.getElementById('btnSubmitGroup').innerText = 'Buat Grup';
  document.getElementById('groupTitle').value = '';
  document.getElementById('groupDescription').value = '';
  document.getElementById('userSearch').value = '';
  document.getElementById('groupIconFile').value = '';
  setGroupIconPreview(null, true);
  selectedUsers.clear();
  selectedSilUsers.clear();
  loadUsers();
  renderSelectedChips();
  const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
  modal.show();
}

async function openEditGroupModal(id) {
  currentEditingGroupId = id;
  document.querySelector('#createGroupModal .modal-title-text').innerText = 'Edit Grup Chat';
  document.getElementById('btnSubmitGroup').innerText = 'Simpan Perubahan';
  
  // Clear first
  document.getElementById('groupTitle').value = '';
  selectedUsers.clear();
  selectedSilUsers.clear();

  // Show loading
  const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
  modal.show();
  
  const loading = document.getElementById('usersLoading');
  const list = document.getElementById('usersList');
  loading.style.display = 'block';
  list.innerHTML = '';

  try {
    // 1. Load group info (Fixing endpoint to match index.php route)
    const gRes = await fetch(`api/chat/${id}`);
    const gInfo = await gRes.json();
    
    if (gInfo.error) throw new Error(gInfo.error);

    document.getElementById('groupTitle').value = gInfo.title || '';
    document.getElementById('groupDescription').value = gInfo.description || '';
    pendingGroupIconFile = null;
    groupIconRemove = false;
    document.getElementById('groupIconFile').value = '';
    setGroupIconPreview(gInfo.icon_path || null, !gInfo.icon_path);
    
    // 2. Load all users if not already loaded
    if (allUsers.length === 0) {
      const uRes = await fetch('api/admin/users');
      allUsers = await uRes.json();
    }
    
    // 3. Set selected users
    if (gInfo.participants) {
      gInfo.participants.forEach(p => {
        // Creator is always in group_participants table, so we show them as selected
        // but toggleUser handles skipping them if we want. 
        // Actually, let's just select everyone who is in the participant list.
        selectedUsers.add(p.id);
      });
    }
    
    renderUsers();
    renderSelectedChips();
  } catch (e) {
    console.error(e);
    showToast('Gagal memuat data grup: ' + e.message, 'danger');
  } finally {
    loading.style.display = 'none';
  }
}

async function loadUsers() {
  const list = document.getElementById('usersList');
  const loading = document.getElementById('usersLoading');
  
  loading.style.display = 'block';
  list.innerHTML = '';

  try {
    const res = await fetch('api/admin/users');
    allUsers = await res.json();
    renderUsers();
  } catch (e) {
    console.error(e);
    list.innerHTML = '<div class="p-3 text-center text-danger small">Gagal memuat daftar user</div>';
  } finally {
    loading.style.display = 'none';
  }
}

async function searchGlobalUsers(q) {
  if (q.length < 2) {
    silUsers = [];
    renderUsers(q);
    return;
  }
  
  try {
    const res = await fetch(`api/admin/sil-users/search?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    silUsers = data.users || [];
    renderUsers(q);
  } catch (e) {
    console.error(e);
  }
}

function renderUsers(filter = '') {
  const list = document.getElementById('usersList');
  list.innerHTML = '';
  
  const f = filter.toLowerCase();

  // Combine local and sil users, avoiding duplicates by sil_id
  const localFiltered = allUsers.filter(u => {
    const nameMatch = (u.name || "").toLowerCase().includes(f);
    const userMatch = (u.username || "").toLowerCase().includes(f);
    return nameMatch || userMatch;
  });

  // Users from SIL who are NOT in local list
  const remoteFiltered = silUsers.filter(su => {
    const nameMatch = (su.nama_user || "").toLowerCase().includes(f);
    const userMatch = (su.username || "").toLowerCase().includes(f);
    const notInLocal = !allUsers.some(au => {
      if (au.sil_id && au.sil_id == su.id_user) return true;
      const lu = (au.username || '').toLowerCase();
      const suU = (su.username || '').toLowerCase();
      return lu && suU && lu === suU;
    });
    return notInLocal && (nameMatch || userMatch);
  });

  if (localFiltered.length === 0 && remoteFiltered.length === 0) {
    list.innerHTML = `
      <div class="text-center py-5 text-muted small">
        <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>
        User tidak ditemukan
      </div>
    `;
    return;
  }

  // Render Local Users
  localFiltered.forEach(u => {
    const isSelected = selectedUsers.has(u.id);
    const item = document.createElement('div');
    item.className = `user-item ${isSelected ? 'selected' : ''}`;
    item.onclick = () => toggleUser(u.id, false);
    
    const badgeClass = u.role === 'admin' ? 'bg-danger-subtle text-danger' : 
                      (u.role === 'teknisi' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-muted');

    item.innerHTML = `
      <div class="form-check me-1">
        <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleUser(${u.id}, false)">
      </div>
      <div class="avatar-sm shadow-sm" style="background: ${u.avatar_url ? 'none' : avatarColor(u.id)}; width: 40px; height: 40px;">
        ${u.avatar_url ? `<img src="${u.avatar_url}" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">` : (u.avatar || '?')}
      </div>
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
          <div class="small fw-800 text-dark" style="line-height:1.2">${u.name || 'Unknown'}</div>
          <span class="user-item-badge ${badgeClass}">${u.role || 'user'}</span>
        </div>
        <div class="text-muted" style="font-size: 11px;">@${u.username || 'no-username'}</div>
      </div>
    `;
    list.appendChild(item);
  });

  // Render Remote Users
  if (remoteFiltered.length > 0) {
    const divider = document.createElement('div');
    divider.className = 'px-4 py-2 bg-light small fw-800 text-muted border-bottom border-top sticky-top';
    divider.style.top = '0';
    divider.innerText = 'HASIL PENCARIAN GLOBAL (BELUM TERDAFTAR)';
    list.appendChild(divider);

    remoteFiltered.forEach(u => {
      const isSelected = selectedSilUsers.has(u.id_user);
      const item = document.createElement('div');
      item.className = `user-item ${isSelected ? 'selected' : ''}`;
      item.onclick = () => toggleUser(u.id_user, true);
      item.innerHTML = `
        <div class="form-check me-1">
          <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleUser(${u.id_user}, true)">
        </div>
        <div class="avatar-sm shadow-sm" style="background: ${u.avatar_url ? 'none' : '#64748b'}; width: 40px; height: 40px;">
          ${u.avatar_url ? `<img src="${u.avatar_url}" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">` : '?'}
        </div>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2">
            <div class="small fw-800 text-dark" style="line-height:1.2">${u.nama_user || 'Unknown'}</div>
            <span class="user-item-badge bg-warning-subtle text-warning">SIL2020</span>
          </div>
          <div class="text-muted" style="font-size: 11px;">@${u.username || 'no-username'}</div>
        </div>
      `;
      list.appendChild(item);
    });
  }
}

function avatarColor(id) {
  if (!id) return '#64748b';
  const colors = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
  return colors[id % colors.length];
}

function findLocalBySilCandidate(su) {
  if (!su) return null;
  return allUsers.find(au => {
    if (au.sil_id && au.sil_id == su.id_user) return true;
    const lu = (au.username || '').toLowerCase();
    const suU = (su.username || '').toLowerCase();
    return lu && suU && lu === suU;
  }) || null;
}

function toggleUser(id, isSil) {
  if (isSil) {
    const su = silUsers.find(x => x.id_user == id);
    const local = findLocalBySilCandidate(su);
    if (local) {
      id = local.id;
      isSil = false;
    }
  }
  if (isSil) {
    if (selectedSilUsers.has(id)) {
      selectedSilUsers.delete(id);
    } else {
      selectedSilUsers.add(id);
    }
  } else {
    if (selectedUsers.has(id)) {
      selectedUsers.delete(id);
    } else {
      selectedUsers.add(id);
    }
  }
  renderUsers(document.getElementById('userSearch').value);
  renderSelectedChips();
}

function renderSelectedChips() {
  const container = document.getElementById('selectedChipsContainer');
  container.innerHTML = '';

  if (selectedUsers.size === 0 && selectedSilUsers.size === 0) {
    container.innerHTML = '<span class="text-muted small italic" style="opacity: 0.6;">Belum ada anggota dipilih...</span>';
    return;
  }

  // Selected Local Users
  selectedUsers.forEach(id => {
    const u = allUsers.find(x => x.id == id);
    if (u) {
      const chip = document.createElement('div');
      chip.className = 'selected-chip';
      chip.innerHTML = `
        <span>${u.name}</span>
        <i class="bi bi-x-circle-fill ms-2" onclick="toggleUser(${id}, false)"></i>
      `;
      container.appendChild(chip);
    }
  });

  // Selected SIL Users
  selectedSilUsers.forEach(id => {
    const su = silUsers.find(x => x.id_user == id);
    if (su) {
      const chip = document.createElement('div');
      chip.className = 'selected-chip sil';
      chip.innerHTML = `
        <span>${su.nama_user}</span>
        <i class="bi bi-x-circle-fill ms-2" onclick="toggleUser(${id}, true)"></i>
      `;
      container.appendChild(chip);
    }
  });
}

let searchTimeout;
document.getElementById('userSearch').oninput = (e) => {
  const q = e.target.value;
  renderUsers(q);
  clearTimeout(searchTimeout);
  if (q.length >= 2) {
    searchTimeout = setTimeout(() => searchGlobalUsers(q), 500);
  }
};

async function submitCreateGroup() {
  const title = document.getElementById('groupTitle').value.trim();
  const description = document.getElementById('groupDescription').value.trim();
  const btn = document.getElementById('btnSubmitGroup');
  
  if (!title) {
    showToast('Masukkan nama grup!', 'danger');
    return;
  }
  if (selectedUsers.size === 0 && selectedSilUsers.size === 0) {
    showToast('Pilih minimal satu anggota!', 'danger');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Membuat...';

  try {
    const fd = new FormData();
    fd.append('title', title);
    fd.append('description', description);
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    Array.from(selectedUsers).forEach(id => fd.append('participants[]', id));
    Array.from(selectedSilUsers).forEach(id => fd.append('sil_participants[]', id));
    if (pendingGroupIconFile) {
      fd.append('group_icon', pendingGroupIconFile);
    }
    if (groupIconRemove) {
      fd.append('remove_icon', '1');
    }

    if (currentEditingGroupId) {
      fd.append('id', currentEditingGroupId);
    }

    const endpoint = currentEditingGroupId ? 'api/admin/chat/group/update' : 'api/admin/chat/group/create';
    
    const res = await fetch(endpoint, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('createGroupModal')).hide();
      showToast(currentEditingGroupId ? 'Grup berhasil diperbarui!' : 'Grup berhasil dibuat!');
      if (data.warning) {
        showToast(data.warning, 'warning');
      }
      loadGroups();
    } else {
      showToast(data.error || 'Gagal menyimpan grup', 'danger');
    }
  } catch (e) {
    console.error(e);
    showToast('Terjadi kesalahan teknis', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = currentEditingGroupId ? 'Simpan Perubahan' : 'Buat Grup';
  }
}

async function deleteGroup(id, title) {
  if (!confirm(`Apakah Anda yakin ingin menghapus grup "${title}"?\nSemua pesan di dalamnya akan terhapus permanen.`)) {
    return;
  }

  try {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    const res = await fetch('api/admin/chat/group/delete', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      showToast('Grup berhasil dihapus!');
      loadGroups();
    } else {
      showToast(data.error || 'Gagal menghapus grup', 'danger');
    }
  } catch (e) {
    console.error(e);
    showToast('Terjadi kesalahan teknis', 'danger');
  }
}

async function loadGroups() {
  const list = document.getElementById('groupsList');
  const loading = document.getElementById('groupsLoading');
  const empty = document.getElementById('groupsEmpty');

  loading.style.display = 'block';
  list.style.display = 'none';
  empty.style.display = 'none';

  try {
    const res = await fetch('api/admin/groups');
    const groups = await res.json();

    if (groups.length === 0) {
      empty.style.display = 'block';
    } else {
      list.innerHTML = '';
      groups.forEach(g => {
        const iconHtml = g.icon_path
          ? `<img src="${g.icon_path}" alt="">`
          : `<i class="bi bi-people-fill"></i>`;
        const pCount = parseInt(g.participant_count, 10) || 0;
        const col = document.createElement('div');
        col.className = 'col-md-6 col-xl-4';
        col.innerHTML = `
          <div class="group-card p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="group-icon">
                ${iconHtml}
              </div>
              <div class="overflow-hidden">
                <h5 class="fw-800 text-truncate mb-0">${g.title}</h5>
                <div class="text-muted text-truncate mb-1" style="font-size: 11px;">${g.description || 'Tidak ada keterangan.'}</div>
                <span class="badge rounded-pill" style="font-size:10px;background:#eef2ff;color:#4338ca;">
                  <i class="bi bi-people-fill me-1"></i>${pCount} peserta
                </span>
              </div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center">
              <div class="text-muted small">
                Dibuat oleh: <strong>${g.creator_name}</strong>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light text-primary rounded-pill px-3" onclick="event.stopPropagation(); openEditGroupModal(${g.id})">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
                <button class="btn btn-sm btn-light text-danger rounded-pill px-2" onclick="event.stopPropagation(); deleteGroup(${g.id}, '${g.title}')">
                  <i class="bi bi-trash"></i>
                </button>
                <div class="text-muted" style="font-size: 10px; align-self: center;">
                  ${new Date(g.created_at).toLocaleDateString('id-ID')}
                </div>
              </div>
            </div>
          </div>
        `;
        list.appendChild(col);
      });
      list.style.display = 'flex';
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.style.display = 'none';
  }
}

// Initial load
loadGroups();
</script>
