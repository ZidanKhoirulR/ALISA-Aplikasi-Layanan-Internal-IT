<?php
// ============================================
// app/views/components/admin_categories.php
// ============================================

$categories = Category::getAll();
?>

<style>
.border-dashed {
    border-style: dashed !important;
}
</style>

<div class="px-4 py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Master Kategori Layanan</h4>
      <p class="text-muted small mb-0">Kelola kategori layanan, ikon, dan urutan tampil di dashboard user.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4" onclick="openCategoryModal()">
      <i class="bi bi-plus-lg me-2"></i>Tambah Kategori
    </button>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">Icon</th>
              <th>Code</th>
              <th>Nama Kategori</th>
              <th>Urutan</th>
              <th>Status</th>
              <th class="text-end pe-4">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($categories)): ?>
            <tr><td colspan="6" class="text-center py-4">Belum ada kategori.</td></tr>
            <?php endif; ?>
            
            <?php foreach ($categories as $c): ?>
            <tr>
              <td class="ps-4">
                <?php if ($c['custom_icon_path']): ?>
                  <img src="<?= htmlspecialchars($c['custom_icon_path']) ?>" alt="Icon" style="width:32px; height:32px; object-fit:contain; border-radius:4px;">
                <?php else: ?>
                  <div style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border-radius:8px; color:#4f46e5; font-size:16px;">
                    <i class="bi <?= htmlspecialchars($c['default_icon_class'] ?: 'bi-bookmark') ?>"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-muted"><small><?= htmlspecialchars($c['code']) ?></small></td>
              <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
              <td><?= (int)$c['sort_order'] ?></td>
              <td>
                <div class="form-check form-switch m-0 d-flex justify-content-center">
                  <input class="form-check-input" type="checkbox" role="switch"
                         onchange="toggleCategoryStatus(<?= $c['id'] ?>)" 
                         <?= $c['is_active'] ? 'checked' : '' ?>
                         style="cursor:pointer">
                </div>
              </td>
              <td class="text-end pe-4">
                <div class="d-flex justify-content-end gap-1">
                  <button class="btn btn-sm btn-outline-primary" 
                          onclick="manageFields('<?= htmlspecialchars($c['code']) ?>')" title="Kelola Form">
                    <i class="bi bi-ui-radios"></i>
                  </button>
                  <button class="btn btn-sm btn-light border" 
                          onclick="editCategory(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" 
                          onclick="deleteCategory(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['name']), ENT_QUOTES, 'UTF-8') ?>)" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Panduan Penggunaan Card -->
  <div class="card border-0 shadow-sm rounded-4 mt-4 bg-light overflow-hidden">
    <div class="row g-0">
      <div class="col-md-1 bg-primary d-flex align-items-center justify-content-center text-white" style="min-height: 100px;">
        <i class="bi bi-info-circle-fill fs-2"></i>
      </div>
      <div class="col-md-11">
        <div class="card-body">
          <h6 class="fw-bold mb-3 text-primary">Panduan Pengaturan Layanan & Form</h6>
          <div class="row g-4">
            <div class="col-md-6 border-end">
              <p class="fw-bold small mb-2 text-dark"><i class="bi bi-grid-fill me-1"></i> Pengaturan Kategori</p>
              <ul class="small text-muted ps-3 mb-0">
                <li class="mb-1"><b>Kode Unik (Key):</b> Nama identitas sistem (hanya huruf/angka/_). Tidak muncul ke user.</li>
                <li class="mb-1"><b>Nama Kategori:</b> Nama layanan yang akan diklik oleh karyawan di dashboard.</li>
                <li><b>Icon:</b> Simbol visual. Bisa pakai Bootstrap Icon atau upload file gambar sendiri.</li>
              </ul>
            </div>
            <div class="col-md-6">
              <p class="fw-bold small mb-2 text-dark"><i class="bi bi-ui-radios me-1"></i> Pengaturan Form (Kelola Form)</p>
              <ul class="small text-muted ps-3 mb-0">
                <li class="mb-1"><b>Key (Internal):</b> Nama teknis isian. Gunakan bahasa inggris/singkat tanpa spasi.</li>
                <li class="mb-1"><b>Label / Pertanyaan:</b> Kalimat pertanyaan yang akan dijawab oleh user.</li>
                <li><b>Tipe Input:</b> Pilih <b>Teks</b> untuk jawaban bebas, atau <b>Dropdown</b> untuk pilihan ganda.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kategori -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="categoryModalTitle">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="categoryForm" onsubmit="saveCategory(event)">
          <input type="hidden" id="cat_id" name="id" value="0">
          
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Kode Unik (Tanpa Spasi)</label>
            <input type="text" class="form-control bg-light" id="cat_code" name="code" required 
                   placeholder="mis: jaringan_internet">
            <small class="text-danger d-none" id="codeError">Hanya huruf, angka, underscore.</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Nama Kategori</label>
            <input type="text" class="form-control" id="cat_name" name="name" required>
          </div>
          
          <div class="row mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Default Icon Class (Bootstrap)</label>
              <input type="text" class="form-control" id="cat_icon_class" name="default_icon_class" placeholder="bi-globe">
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Urutan</label>
              <input type="number" class="form-control" id="cat_sort" name="sort_order" value="0">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Custom Icon (Opsional, timpa default icon)</label>
            <input type="file" class="form-control" id="cat_icon_file" name="icon_file" accept=".jpg,.jpeg,.png,.svg,.webp">
            <small class="text-muted d-block mt-1">Maks 1MB. Rekomendasi SVG/PNG transparan 64x64px.</small>
            
            <div id="currentIconPreview" class="d-none mt-2 p-2 border rounded bg-light d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <img id="catIconImg" src="" style="width:32px; height:32px; object-fit:contain;">
                <span class="small text-muted">Icon saat ini</span>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="cat_remove_icon" name="remove_icon" value="1">
                <label class="form-check-label small text-danger" for="cat_remove_icon">Hapus Icon</label>
              </div>
            </div>
          </div>

          <div class="alert alert-danger d-none py-2 small" id="catFormError"></div>

          <div class="text-end mt-4">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4" id="btnSaveCategory">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Form Builder -->
<div class="modal fade" id="fieldModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold" id="fieldModalTitle">Kelola Form: </h5>
          <p class="text-muted small mb-0">Atur isian tambahan untuk kategori ini.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <!-- List Fields -->
        <div id="fieldListContainer" class="mb-4">
          <!-- Populated by JS -->
        </div>

        <!-- Add/Edit Field Form -->
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body">
            <h6 class="fw-bold mb-3" id="fieldFormTitle">Tambah Field Baru</h6>
            <form id="fieldForm" onsubmit="saveField(event)">
              <input type="hidden" id="field_id" name="id" value="0">
              <input type="hidden" id="field_category_code" name="category_code" value="">
              <input type="hidden" id="field_is_builtin_ruangan" value="0">

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Key (Tanpa Spasi) *</label>
                  <input type="text" class="form-control" id="field_key" name="field_key" required placeholder="mis: internet_type">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Label / Pertanyaan *</label>
                  <input type="text" class="form-control" id="field_label" name="field_label" required placeholder="mis: Tipe Kendala">
                </div>
              </div>
              <div class="alert alert-info py-2 px-3 small d-none" id="builtinFieldInfo">
                Field bawaan <b>ruangan</b>: key dan tipe input dikunci. Anda tetap bisa ubah label, urutan, wajib isi, dan status aktif/nonaktif.
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-muted">Tipe Input *</label>
                  <select class="form-select" id="field_type" name="field_type" onchange="toggleFieldOptions()" required>
                    <option value="text">Teks Pendek</option>
                    <option value="textarea">Teks Panjang</option>
                    <option value="select">Dropdown (Select)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-muted">Urutan *</label>
                  <input type="number" class="form-control" id="field_sort" name="sort_order" value="0" required>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-end pb-1">
                  <div class="form-check form-switch m-0 pt-2 me-3">
                    <input class="form-check-input" type="checkbox" id="field_active" name="is_active" value="1" checked>
                    <label class="form-check-label small fw-bold text-muted ms-2" for="field_active" style="cursor:pointer">Aktif</label>
                  </div>
                  <div class="form-check form-switch m-0 pt-2">
                    <input class="form-check-input" type="checkbox" id="field_required" name="is_required" value="1" checked>
                    <label class="form-check-label small fw-bold text-muted ms-2" for="field_required" style="cursor:pointer">Wajib Diisi</label>
                  </div>
                </div>
              </div>

              <div class="mb-3 d-none" id="fieldOptionsWrapper">
                <label class="form-label small fw-bold text-muted mb-2">Pilihan Dropdown (Opsi Pilihan)</label>
                <div id="optionRowsContainer" class="mb-2">
                  <!-- Dynamic rows go here -->
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 border-dashed" onclick="addOptionRow()">
                  <i class="bi bi-plus-circle me-1"></i> Tambah Opsi
                </button>
                <small class="text-muted d-block mt-2">Opsi ini akan muncul sebagai pilihan dropdown bagi pengguna.</small>
              </div>

              <div class="text-end mt-3">
                <button type="button" class="btn btn-sm btn-light me-2" onclick="resetFieldForm()">Reset Form</button>
                <button type="submit" class="btn btn-sm btn-primary px-4" id="btnSaveField">Simpan Field</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
let catModalInstance;
let fieldModalInstance;
let currentCategoryCode = '';
let currentFields = [];

function openCategoryModal() {
    if (!catModalInstance) {
        catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
    }
    document.getElementById('categoryForm').reset();
    document.getElementById('cat_id').value = '0';
    document.getElementById('cat_code').readOnly = false;
    document.getElementById('categoryModalTitle').textContent = 'Tambah Kategori';
    document.getElementById('currentIconPreview').classList.add('d-none');
    document.getElementById('catFormError').classList.add('d-none');
    catModalInstance.show();
}

function editCategory(cat) {
    if (!catModalInstance) {
        catModalInstance = new bootstrap.Modal(document.getElementById('categoryModal'));
    }
    document.getElementById('categoryForm').reset();
    document.getElementById('catFormError').classList.add('d-none');
    
    document.getElementById('categoryModalTitle').textContent = 'Edit Kategori';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_code').value = cat.code;
    document.getElementById('cat_code').readOnly = true; // prevent changing code if logic depends on it
    document.getElementById('cat_name').value = cat.name;
    document.getElementById('cat_icon_class').value = cat.default_icon_class || '';
    document.getElementById('cat_sort').value = cat.sort_order;
    
    if (cat.custom_icon_path) {
        document.getElementById('currentIconPreview').classList.remove('d-none');
        document.getElementById('catIconImg').src = cat.custom_icon_path;
        document.getElementById('cat_remove_icon').checked = false;
    } else {
        document.getElementById('currentIconPreview').classList.add('d-none');
    }
    
    catModalInstance.show();
}

function saveCategory(e) {
    e.preventDefault();
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    
    // code validation
    const code = formData.get('code');
    if (!/^[a-zA-Z0-9_]+$/.test(code)) {
        document.getElementById('codeError').classList.remove('d-none');
        return;
    } else {
        document.getElementById('codeError').classList.add('d-none');
    }

    const id = formData.get('id');
    const url = id == '0' ? 'api/admin/categories/create' : 'api/admin/categories/update';
    
    const btn = document.getElementById('btnSaveCategory');
    btn.disabled = true;
    btn.innerHTML = 'Menyimpan...';

    // Append CSRF Token to body
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Csrf-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('pending_toast', JSON.stringify({ msg: 'Kategori berhasil disimpan!', type: 'success' }));
            window.location.reload();
        } else {
            throw new Error(data.error || 'Terjadi kesalahan');
        }
    })
    .catch(err => {
        const errEl = document.getElementById('catFormError');
        errEl.textContent = err.message;
        errEl.classList.remove('d-none');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Simpan';
    });
}

function toggleCategoryStatus(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('api/admin/categories/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Csrf-Token': csrfToken
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            localStorage.setItem('pending_toast', JSON.stringify({ msg: data.error || 'Gagal mengubah status', type: 'danger' }));
            window.location.reload();
        }
    })
    .catch(err => {
        localStorage.setItem('pending_toast', JSON.stringify({ msg: 'Terjadi kesalahan jaringan', type: 'danger' }));
        window.location.reload();
    });
}

function deleteCategory(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus kategori "${name}"?\nSemua field form yang terkait juga akan terhapus.`)) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('api/admin/categories/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Csrf-Token': csrfToken
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('pending_toast', JSON.stringify({ msg: 'Kategori berhasil dihapus!', type: 'success' }));
            window.location.reload();
        } else {
            showToast(data.error || 'Gagal menghapus kategori', 'danger');
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan jaringan', 'danger');
    });
}

// ==========================================
// Field Management
// ==========================================

function manageFields(categoryCode) {
    currentCategoryCode = categoryCode;
    document.getElementById('fieldModalTitle').textContent = 'Kelola Form: ' + categoryCode;
    document.getElementById('field_category_code').value = categoryCode;
    resetFieldForm();
    
    if (!fieldModalInstance) {
        fieldModalInstance = new bootstrap.Modal(document.getElementById('fieldModal'));
    }
    
    loadFields();
    fieldModalInstance.show();
}

function loadFields() {
    const container = document.getElementById('fieldListContainer');
    container.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span> Memuat form...</div>';
    
    fetch('api/admin/categories/fields/' + currentCategoryCode)
    .then(res => res.json())
    .then(data => {
        if (!Array.isArray(data)) throw new Error('Invalid data');
        
        currentFields = data;
        if (data.length === 0) {
            container.innerHTML = '<div class="alert alert-warning small mb-0"><i class="bi bi-info-circle me-1"></i>Belum ada form tambahan. Gunakan form di bawah untuk membuat field baru.</div>';
            return;
        }
        
        let html = '<ul class="list-group list-group-flush rounded-3 shadow-sm">';
        data.forEach((f, idx) => {
            let optionsPreview = '';
            if (f.field_type === 'select' && f.field_options) {
                try {
                    let opts = JSON.parse(f.field_options);
                    optionsPreview = ` <span class="badge bg-light text-secondary border">` + opts.length + ` opsi</span>`;
                } catch(e) {}
            }
            let reqBadge = parseInt(f.is_required) ? '<span class="badge bg-danger ms-1">Wajib</span>' : '';
            
            html += `
            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                <div>
                    <div class="fw-bold mb-1">${f.field_label} ${reqBadge}</div>
                    <div class="small text-muted">
                        Key: <code>${f.field_key}</code> &middot; Tipe: <b>${f.field_type}</b>
                        ${optionsPreview}
                        &middot; Urutan: ${f.sort_order}
                        ${parseInt(f.is_active) ? '<span class="badge bg-success ms-1">Aktif</span>' : '<span class="badge bg-secondary ms-1">Nonaktif</span>'}
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-light border me-1" onclick="editFieldByIndex(${idx})"><i class="bi bi-pencil"></i></button>
                    ${f.field_key !== 'ruangan' ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteField(${f.id})"><i class="bi bi-trash"></i></button>` : ''}
                </div>
            </li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    })
    .catch(err => {
        container.innerHTML = '<div class="alert alert-danger small mb-0">Gagal memuat form field.</div>';
    });
}

function resetFieldForm() {
    document.getElementById('fieldForm').reset();
    document.getElementById('field_id').value = '0';
    document.getElementById('field_category_code').value = currentCategoryCode;
    document.getElementById('field_is_builtin_ruangan').value = '0';
    document.getElementById('fieldFormTitle').textContent = 'Tambah Field Baru';
    document.getElementById('field_key').readOnly = false;
    document.getElementById('field_type').disabled = false;
    document.getElementById('builtinFieldInfo').classList.add('d-none');
    toggleFieldOptions();
}

function toggleFieldOptions() {
    const type = document.getElementById('field_type').value;
    const wrapper = document.getElementById('fieldOptionsWrapper');
    const optionInputs = document.querySelectorAll('.option-input');
    if (type === 'select') {
        wrapper.classList.remove('d-none');
        if (document.querySelectorAll('.option-input').length === 0) {
            addOptionRow();
        }
        document.querySelectorAll('.option-input').forEach(input => {
            input.disabled = false;
            input.required = true;
        });
    } else {
        wrapper.classList.add('d-none');
        optionInputs.forEach(input => {
            input.required = false;
            input.disabled = true;
        });
    }
}

function addOptionRow(val = '') {
    const container = document.getElementById('optionRowsContainer');
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-2 shadow-sm';
    div.innerHTML = `
        <input type="text" class="form-control option-input" placeholder="Teks Pilihan..." value="${val.replace(/"/g, '&quot;')}">
        <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function renderOptionList(opts) {
    const container = document.getElementById('optionRowsContainer');
    container.innerHTML = '';
    if (Array.isArray(opts) && opts.length > 0) {
        opts.forEach(opt => addOptionRow(opt));
    } else {
        addOptionRow();
    }
}

function editFieldByIndex(idx) {
    const f = currentFields[idx];
    if (f) editField(f);
}

function editField(f) {
    document.getElementById('fieldFormTitle').textContent = 'Edit Field';
    document.getElementById('field_is_builtin_ruangan').value = (f.field_key === 'ruangan' ? '1' : '0');
    document.getElementById('field_id').value = f.id;
    document.getElementById('field_key').value = f.field_key;
    document.getElementById('field_label').value = f.field_label;
    document.getElementById('field_type').value = f.field_type;
    document.getElementById('field_sort').value = f.sort_order;
    document.getElementById('field_required').checked = parseInt(f.is_required) === 1;
    document.getElementById('field_active').checked = parseInt(f.is_active) !== 0;
    
    if (f.field_type === 'select' && f.field_options) {
        try {
            let opts = JSON.parse(f.field_options);
            renderOptionList(opts);
        } catch(e) {
            renderOptionList([]);
        }
    } else {
        renderOptionList([]);
    }

    const isBuiltInRuangan = f.field_key === 'ruangan';
    document.getElementById('field_key').readOnly = isBuiltInRuangan;
    document.getElementById('field_type').disabled = isBuiltInRuangan;
    document.getElementById('builtinFieldInfo').classList.toggle('d-none', !isBuiltInRuangan);
    
    toggleFieldOptions();
}

function saveField(e) {
    e.preventDefault();
    const form = document.getElementById('fieldForm');
    const typeSelect = document.getElementById('field_type');
    const isBuiltInRuangan = document.getElementById('field_is_builtin_ruangan').value === '1';

    // Disabled field tidak ikut FormData, jadi pastikan tetap terkirim.
    if (typeSelect.disabled) {
        typeSelect.disabled = false;
    }

    const formData = new FormData(form);
    
    // Parse options from dynamic list to JSON array
    const type = formData.get('field_type');
    if (type === 'select') {
        const optsArray = [];
        document.querySelectorAll('.option-input').forEach(input => {
            const val = input.value.trim();
            if (val) optsArray.push(val);
        });
        
        if (optsArray.length === 0) {
            showToast('Silakan tambahkan minimal satu opsi untuk dropdown.', 'danger');
            return;
        }
        formData.set('field_options', JSON.stringify(optsArray));
    } else {
        formData.set('field_options', '');
    }
    
    // Checkbox boolean handle
    formData.set('is_required', document.getElementById('field_required').checked ? '1' : '0');
    formData.set('is_active', document.getElementById('field_active').checked ? '1' : '0');
    
    if (isBuiltInRuangan) {
        formData.set('field_key', 'ruangan');
    }

    const btn = document.getElementById('btnSaveField');
    btn.disabled = true;
    btn.innerHTML = 'Menyimpan...';

    // Append CSRF Token to body
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('api/admin/categories/fields/save', {
        method: 'POST',
        headers: {
            'X-Csrf-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Field form berhasil disimpan!');
            loadFields();
            resetFieldForm();
        } else {
            showToast(data.error || 'Terjadi kesalahan', 'danger');
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan jaringan', 'danger');
    })
    .finally(() => {
        if (isBuiltInRuangan) {
            typeSelect.disabled = true;
        }
        btn.disabled = false;
        btn.innerHTML = 'Simpan Field';
    });
}

function deleteField(id) {
    if (!confirm('Hapus field ini?')) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('api/admin/categories/fields/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Csrf-Token': csrfToken
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Field berhasil dihapus!');
            loadFields();
        } else {
            showToast(data.error || 'Gagal menghapus', 'danger');
        }
    });
}
</script>
