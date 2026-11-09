<?php
// ============================================
// app/controllers/CategoryController.php
// ============================================

class CategoryController
{
    private function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // GET /api/categories (Active only, for users)
    public function getActive(\Base $f3): void
    {
        Security::checkAuth($f3);
        $this->json(Category::getActive());
    }

    // GET /api/admin/categories (All, for admin)
    public function getAll(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }
        $this->json(Category::getAll());
    }

    // POST /api/admin/categories/create
    public function create(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $code       = trim($f3->get('POST.code') ?? '');
        $name       = trim($f3->get('POST.name') ?? '');
        $iconClass  = trim($f3->get('POST.default_icon_class') ?? 'bi-bookmark');
        $sortOrder  = (int)($f3->get('POST.sort_order') ?? 0);

        if (empty($code) || empty($name)) {
            $this->json(['error' => 'Code and Name are required'], 400);
            return;
        }

        // Handle file upload if present
        $iconPath = $this->handleIconUpload();

        try {
            $id = Category::create($code, $name, $iconClass, $iconPath, $sortOrder);
            $this->json(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/admin/categories/update
    public function update(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id         = (int)($f3->get('POST.id') ?? 0);
        $code       = trim($f3->get('POST.code') ?? '');
        $name       = trim($f3->get('POST.name') ?? '');
        $iconClass  = trim($f3->get('POST.default_icon_class') ?? 'bi-bookmark');
        $sortOrder  = (int)($f3->get('POST.sort_order') ?? 0);
        $removeIcon = (int)($f3->get('POST.remove_icon') ?? 0);

        $existing = Category::findById($id);
        if (!$existing) {
            $this->json(['error' => 'Category not found'], 404);
            return;
        }

        $iconPath = $existing['custom_icon_path'];
        
        if ($removeIcon) {
            $iconPath = null;
        }

        $newUploadedPath = $this->handleIconUpload();
        if ($newUploadedPath) {
            $iconPath = $newUploadedPath;
        }

        try {
            Category::update($id, $code, $name, $iconClass, $iconPath, $sortOrder);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Gagal mengupdate data: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/admin/categories/toggle
    public function toggle(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID required'], 400);
            return;
        }

        Category::toggleActive($id);
        $this->json(['success' => true]);
    }

    // POST /api/admin/categories/delete
    public function delete(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID required'], 400);
            return;
        }

        try {
            // Optional: check if category is used in requests before deleting?
            // For now, simple delete.
            Category::delete($id);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Gagal menghapus kategori: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // FIELD MANAGEMENT
    // ==========================================

    // GET /api/admin/categories/fields/@code
    public function getFields(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $code = $params['code'] ?? '';
        $this->json(CategoryField::getByCategoryCode($code));
    }

    // POST /api/admin/categories/fields/save
    public function saveField(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id           = (int)($f3->get('POST.id') ?? 0);
        $categoryCode = trim($f3->get('POST.category_code') ?? '');
        $fieldKey     = strtolower(trim($f3->get('POST.field_key') ?? ''));
        $fieldLabel   = trim($f3->get('POST.field_label') ?? '');
        $fieldType    = trim($f3->get('POST.field_type') ?? 'text');
        $fieldOptions = trim($f3->get('POST.field_options') ?? '');
        $isRequired   = (int)($f3->get('POST.is_required') ?? 1);
        $isActive     = (int)($f3->get('POST.is_active') ?? 1);
        $sortOrder    = (int)($f3->get('POST.sort_order') ?? 0);

        if (empty($fieldLabel) || empty($categoryCode)) {
            $this->json(['error' => 'Data tidak lengkap'], 400);
            return;
        }

        $allowedTypes = ['text', 'textarea', 'select'];
        if (!in_array($fieldType, $allowedTypes, true)) {
            $this->json(['error' => 'Tipe field tidak valid'], 422);
            return;
        }

        if ($fieldKey !== '' && !preg_match('/^[a-z0-9_]+$/', $fieldKey)) {
            $this->json(['error' => 'Key field hanya boleh huruf kecil, angka, dan underscore'], 422);
            return;
        }

        if ($fieldType === 'select') {
            if ($fieldOptions === '') {
                $this->json(['error' => 'Opsi dropdown wajib diisi'], 422);
                return;
            }

            $decodedOptions = json_decode($fieldOptions, true);
            if (!is_array($decodedOptions) || count($decodedOptions) === 0) {
                $this->json(['error' => 'Format opsi dropdown tidak valid'], 422);
                return;
            }
        } else {
            $fieldOptions = null;
        }

        try {
            if ($id > 0) {
                $existingField = CategoryField::findById($id);
                if (!$existingField) {
                    $this->json(['error' => 'Field tidak ditemukan'], 404);
                    return;
                }

                // Field bawaan ruangan: boleh nonaktif, tapi key/type harus tetap konsisten.
                if (($existingField['field_key'] ?? '') === 'ruangan') {
                    $fieldKey = 'ruangan';
                    $fieldType = $existingField['field_type'] ?? 'text';
                    $fieldOptions = null;
                } elseif ($fieldKey === '') {
                    $this->json(['error' => 'Key field wajib diisi'], 422);
                    return;
                }

                CategoryField::update($id, $fieldKey, $fieldLabel, $fieldType, $fieldOptions, $isRequired, $isActive, $sortOrder);
            } else {
                if ($fieldKey === '') {
                    $this->json(['error' => 'Key field wajib diisi'], 422);
                    return;
                }
                CategoryField::create($categoryCode, $fieldKey, $fieldLabel, $fieldType, $fieldOptions, $isRequired, $isActive, $sortOrder);
            }
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Gagal menyimpan field: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/admin/categories/fields/delete
    public function deleteField(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID required'], 400);
            return;
        }

        try {
            $field = CategoryField::findById($id);
            if ($field && $field['field_key'] === 'ruangan') {
                $this->json(['error' => 'Field Ruangan adalah field bawaan dan tidak dapat dihapus. Silakan nonaktifkan saja jika tidak digunakan.'], 403);
                return;
            }

            CategoryField::delete($id);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Gagal menghapus field'], 500);
        }
    }

    // Helper for handling icon upload
    private function handleIconUpload(): ?string
    {
        if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['icon_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 1048576) { // 1MB limit
                $dirCheck = Storage::ensureWritable(Storage::categoryIconsDir());
                if (!$dirCheck['ok']) {
                    error_log('[category-icon] ' . ($dirCheck['error'] ?? '?') . '; path=' . $dirCheck['path']);
                    return null;
                }
                $uploadDir = $dirCheck['path'];

                $filename = uniqid('cat_') . '.' . $ext;
                $dest = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    @chmod($dest, 0666);
                    return 'public/category-icons/' . $filename;
                }
            }
        }
        return null;
    }
}
