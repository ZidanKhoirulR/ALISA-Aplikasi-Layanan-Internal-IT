<?php
// ============================================
// app/controllers/UserController.php
// ============================================

class UserController
{
    // POST /api/user/status
    public function updateStatus(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');

        $status = $f3->get('POST.status') ?? '';
        $allowed = ['available', 'busy', 'offline'];

        if (!in_array($status, $allowed)) {
            http_response_code(422);
            echo json_encode(['error' => 'Status tidak valid.']);
            return;
        }

        User::updateStatus((int)$user['id'], $status);

        // Update session
        $updated = User::findById((int)$user['id']);
        unset($updated['password']);
        $f3->set('SESSION.user', $updated);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $status]);
    }

    // GET /api/admin/sil-users/search
    public function searchSilUsers(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $q = $f3->get('GET.q') ?? '';

        $users = User::searchSil2020Users($q);
        foreach ($users as &$u) {
            $u['avatar_url'] = User::getAvatarUrlFromFilename($u['nama_avatar'] ?? '');
            if ($u['avatar_url'] === '') {
                $u['avatar_url'] = User::getAvatarUrl($u['username'], $u['nama_user']);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['users' => $users]);
    }

    // POST /api/admin/team/add
    public function addTeamMember(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $silId = (int)($f3->get('POST.sil_id') ?? 0);
        if (!$silId) {
            http_response_code(422);
            echo json_encode(['error' => 'ID SIL tidak valid.']);
            return;
        }

        $success = User::addTechnicianFromSil($silId);
        if ($success) {
            Logger::log('TEAM_ADD_SIL', "Added technician from SIL, ID: $silId");
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    // POST /api/admin/team/create
    public function createTeamMember(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $name = trim($f3->get('POST.name') ?? '');
        $username = trim($f3->get('POST.username') ?? '');
        $role = trim($f3->get('POST.role') ?? 'teknisi');

        if (!$name || !$username) {
            http_response_code(422);
            echo json_encode(['error' => 'Nama dan Username wajib diisi.']);
            return;
        }

        $success = User::createManualTechnician($name, $username, $role);
        header('Content-Type: application/json');
        if ($success) {
            Logger::log('TEAM_CREATE_MANUAL', "Created manual $role: $username ($name)");
            echo json_encode(['success' => true]);
        } else {
            http_response_code(422);
            echo json_encode(['error' => 'Username sudah digunakan.']);
        }
    }

    // POST /api/admin/team/update
    public function updateTeamMember(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        $name = trim($f3->get('POST.name') ?? '');
        $username = trim($f3->get('POST.username') ?? '');
        $role = trim($f3->get('POST.role') ?? 'teknisi');

        if (!$id || !$name || !$username) {
            http_response_code(422);
            echo json_encode(['error' => 'Data tidak lengkap.']);
            return;
        }

        $success = User::updateTechnician($id, $name, $username, $role);
        header('Content-Type: application/json');
        if ($success) {
            Logger::log('TEAM_UPDATE', "Updated user ID: $id to $username ($name, $role)");
            echo json_encode(['success' => true]);
        } else {
            http_response_code(422);
            echo json_encode(['error' => 'Username sudah digunakan oleh akun lain.']);
        }
    }

    // POST /api/admin/team/delete
    public function deleteTeamMember(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID tidak valid.']);
            return;
        }

        if ($id === (int)$user['id']) {
            http_response_code(422);
            echo json_encode(['error' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
            return;
        }

        User::deleteTechnician($id);
        Logger::log('TEAM_DELETE', "Deactivated/Deleted user ID: $id");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    // POST /api/user/change-password
    public function changePassword(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        
        $oldPwd = $f3->get('POST.old_password') ?? '';
        $newPwd = $f3->get('POST.new_password') ?? '';
        
        if (!$oldPwd || !$newPwd) {
            http_response_code(422);
            echo json_encode(['error' => 'Password lama dan baru wajib diisi.']);
            return;
        }
        
        // Fetch fresh user data to get password
        $dbUser = User::findById((int)$user['id']);
        
        if (!password_verify($oldPwd, $dbUser['password'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Password lama salah.']);
            return;
        }
        
        if (strlen($newPwd) < 6) {
            http_response_code(422);
            echo json_encode(['error' => 'Password baru minimal 6 karakter.']);
            return;
        }
        
        User::updatePassword((int)$user['id'], $newPwd);
        Logger::log('USER_CHANGE_PASSWORD', "User changed their own password");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
