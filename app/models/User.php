<?php
// ============================================
// app/models/User.php
// ============================================

class User
{
    private static $avatarDirCache = null;
    private static $avatarFilesCache = null;
    private static $silAvatarFilenameCache = [];
    private static $avatarUrlCache = [];

    public static function findByEmail(string $email): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function findByUsername(string $username): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE LOWER(username) = LOWER(?)', [$username]);
    }

    public static function syncFromSil(array $silUser): void
    {
        $login = trim($silUser['login'] ?? $silUser['username'] ?? '');
        if (empty($login)) return;

        $silId = (int)($silUser['id_user'] ?? 0);
        $existing = DB::fetch(
            'SELECT * FROM users WHERE sil_id = ? OR LOWER(username) = LOWER(?)',
            [$silId, $login]
        );
        if (!$existing) {
            $name = $silUser['nama_user'] ?? $login;
            $email = !empty($silUser['email']) ? trim($silUser['email']) : ($login . '@sil.local');
            if (self::findByEmail($email)) {
                $email = strtolower($login) . '.' . $silId . '@sil.local';
            }
            $role = 'user';
            $avatar = self::getInitials($name);
            DB::run(
                'INSERT INTO users (name, username, email, password, role, avatar, sil_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$name, $login, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $role, $avatar, $silId]
            );
        } else {
            DB::run('UPDATE users SET username = ?, sil_id = ? WHERE id = ?', [
                $login,
                $silId,
                $existing['id']
            ]);
        }
    }

    public static function findById(int $id): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function getAvailableTechnician(): ?array
    {
        return DB::fetch(
            "SELECT * FROM users WHERE role IN ('teknisi','admin') AND status = 'available' ORDER BY id LIMIT 1"
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::run('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        DB::run('UPDATE users SET password = ? WHERE id = ?', [$hash, $id]);
    }

    public static function getTechniciansWithActiveTasks(): array
    {
        // Fetch all technicians and admins
        $techs = DB::fetchAll("SELECT id, name, username, role, status, avatar, sil_id FROM users WHERE role IN ('teknisi','admin')");
        $today = date('Y-m-d');
        
        foreach ($techs as &$t) {
            // Fetch their currently active chat (in_progress)
            // Join with zoom_schedules to check if it's a future meeting
            $activeChat = DB::fetch("
                SELECT c.id, c.title, c.category, c.room, u.name as user_name, u.username as user_username, u.avatar as user_avatar, u.sil_id as user_sil_id,
                       zs.waktu_mulai as zoom_start
                FROM chats c
                JOIN users u ON u.id = c.user_id
                LEFT JOIN zoom_schedules zs ON zs.chat_id = c.id
                WHERE c.teknisi_id = ? AND c.status = 'in_progress'
                ORDER BY c.updated_at DESC
                LIMIT 1
            ", [$t['id']]);

            if ($activeChat) {
                $isActuallyBusy = true;
                
                // If it's a Zoom request, check the date
                if (!empty($activeChat['zoom_start'])) {
                    $zoomDate = date('Y-m-d', strtotime($activeChat['zoom_start']));
                    if ($zoomDate > $today) {
                        // Future meeting, technician is still available for other tasks today
                        $isActuallyBusy = false;
                    }
                }

                if ($isActuallyBusy) {
                    $t['active_chat'] = $activeChat;
                    $t['status'] = 'busy';
                } else {
                    // Assigned to future task, but available today
                    $t['active_chat'] = null; 
                    $t['status'] = 'available';
                }
            } else {
                $t['active_chat'] = null;
                $t['status'] = 'available';
            }
        }
        
        return $techs;
    }

    public static function searchSil2020Users(string $query): array
    {
        $silDb = DB::getSilInstance();

        if ($query === '') {
            // Return all users when no query
            $stmt = $silDb->prepare("
                SELECT id_user, login AS username, nama_user, nama_avatar, '' AS email
                FROM tb_arsipuser 
                WHERE status = 1
                ORDER BY nama_user ASC
                LIMIT 200
            ");
            $stmt->execute();
        } else {
            $stmt = $silDb->prepare("
                SELECT id_user, login AS username, nama_user, nama_avatar, '' AS email
                FROM tb_arsipuser 
                WHERE status = 1
                  AND (login LIKE ? OR nama_user LIKE ?)
                ORDER BY nama_user ASC
                LIMIT 50
            ");
            $stmt->execute(["%$query%", "%$query%"]);
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as $i => &$row) {
            // "n1" requested by user: sequence starts from 1 (offset +1)
            $row['n1'] = $i + 1;
        }
        return $rows;
    }

    public static function addTechnicianFromSil(int $silId): bool
    {
        $silDb = DB::getSilInstance();
        $stmt = $silDb->prepare("SELECT * FROM tb_arsipuser WHERE id_user = ?");
        $stmt->execute([$silId]);
        $silUser = $stmt->fetch();

        if (!$silUser) return false;

        // tb_arsipuser uses 'login' as the username field
        $login = $silUser['login'] ?? '';

        $existing = self::findByUsername($login);
        if ($existing) {
            // If exists, just promote to teknisi if not already admin
            if ($existing['role'] === 'user') {
                DB::run("UPDATE users SET role = 'teknisi', sil_id = ? WHERE id = ?", [$silId, $existing['id']]);
            }
            return true;
        }

        $name = $silUser['nama_user'] ?? $login;
        $email = $login . '@sil.local';
        $avatar = self::getInitials($name);

        DB::run(
            'INSERT INTO users (name, username, email, password, role, avatar, sil_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$name, $login, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), 'teknisi', $avatar, $silId]
        );

        return true;
    }

    public static function createManualTechnician(string $name, string $username, string $role): bool
    {
        $existing = self::findByUsername($username);
        if ($existing) return false;

        $email = $username . '@silat.local';
        $avatar = self::getInitials($name);
        
        // Ensure role is either teknisi or admin
        $role = in_array($role, ['teknisi', 'admin']) ? $role : 'teknisi';

        DB::run(
            'INSERT INTO users (name, username, email, password, role, avatar) VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $username, $email, password_hash('sil2020', PASSWORD_DEFAULT), $role, $avatar]
        );
        return true;
    }

    public static function updateTechnician(int $id, string $name, string $username, string $role): bool
    {
        $existing = DB::fetch('SELECT id FROM users WHERE username = ? AND id != ?', [$username, $id]);
        if ($existing) return false;

        $avatar = self::getInitials($name);
        $role = in_array($role, ['teknisi', 'admin']) ? $role : 'teknisi';

        DB::run(
            'UPDATE users SET name = ?, username = ?, role = ?, avatar = ? WHERE id = ?',
            [$name, $username, $role, $avatar, $id]
        );
        return true;
    }

    public static function deleteTechnician(int $id): void
    {
        // Instead of hard delete, maybe just downgrade role to 'user' so chat history doesn't break?
        DB::run("UPDATE users SET role = 'user' WHERE id = ?", [$id]);
    }

    public static function getInitials(?string $name): string
    {
        if (empty($name)) return '?';
        $parts = explode(' ', trim($name));
        $init  = strtoupper(substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $init .= strtoupper(substr($parts[1], 0, 1));
        }
        return $init;
    }

    /**
     * Check if user has an avatar file in public/avatar/
     * Returns the relative path to the image, or an empty string if not found.
     */
    public static function getAvatarUrlFromFilename(?string $filename): string
    {
        return self::resolveAvatarFilename($filename);
    }

    public static function getAvatarUrl(?string $username, ?string $name, ?int $silId = null): string
    {
        if (!$username && !$name && !$silId) return '';

        $u = $username ? strtolower(trim($username, " \t\n\r\0\x0B@")) : '';
        $n = $name ? strtolower(trim($name)) : '';
        $sid = $silId ? (int)$silId : 0;
        $cacheKey = $sid . '|' . $u . '|' . $n;
        if (array_key_exists($cacheKey, self::$avatarUrlCache)) {
            return self::$avatarUrlCache[$cacheKey];
        }

        $silAvatar = self::getSilAvatarFilename($u, $n, $sid);
        if ($silAvatar !== '') {
            $resolved = self::resolveAvatarFilename($silAvatar);
            if ($resolved !== '') {
                self::$avatarUrlCache[$cacheKey] = $resolved;
                return $resolved;
            }
        }

        $avatarDir = self::getAvatarDir();

        // Basic candidates
        $candidates = [$u];
        $uNoNums = preg_replace('/[0-9]+/', '', $u);
        if (strlen($uNoNums) >= 3) $candidates[] = $uNoNums;

        // Name part combinations
        $parts = $n ? array_filter(explode(' ', $n)) : [];
        if (!empty($parts)) {
            // Full name
            $candidates[] = $n;
            // All individual words (e.g. "rina", "arinal", "haq")
            foreach ($parts as $p) {
                if (strlen($p) >= 3) $candidates[] = $p;
            }
            // Joined combinations (e.g. "rinaarinal", "arinalhaq", "rinaarinalhaq")
            $nParts = count($parts);
            for ($i = 0; $i < $nParts; $i++) {
                $temp = $parts[$i];
                $tempSpace = $parts[$i];
                for ($j = $i + 1; $j < $nParts; $j++) {
                    $temp .= $parts[$j];
                    $tempSpace .= ' ' . $parts[$j];
                    if (strlen($temp) >= 3) $candidates[] = $temp;
                    if (strlen($tempSpace) >= 3) $candidates[] = $tempSpace;
                }
            }
            // Underscore combinations
            $candidates[] = str_replace(' ', '_', $n);
        }

        $candidates = array_unique(array_filter($candidates));
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        // Pass 1: Direct exact match (Fast)
        foreach ($candidates as $c) {
            foreach ($extensions as $ext) {
                foreach ([$c, $c . '2'] as $base) {
                    $file = $base . '.' . $ext;
                    if (file_exists($avatarDir . $file)) {
                        return self::$avatarUrlCache[$cacheKey] = 'public/avatar/' . rawurlencode($file);
                    }
                }
            }
        }

        // Pass 2: Directory scan for case-insensitive and partial matches
        $files = self::getAvatarFiles();
        if (!empty($files)) {

            // 2a. Case-insensitive exact match
            foreach ($candidates as $c) {
                foreach ($extensions as $ext) {
                    $target1 = strtolower($c . '.' . $ext);
                    $target2 = strtolower($c . '2.' . $ext);
                    foreach ($files as $f) {
                        $fLower = strtolower($f);
                        if ($fLower === $target1 || $fLower === $target2) {
                            return self::$avatarUrlCache[$cacheKey] = 'public/avatar/' . rawurlencode($f);
                        }
                    }
                }
            }

            // 2b. Strict Permissive match (must start with candidate or match word boundary)
            foreach ($candidates as $c) {
                if (strlen($c) < 4) continue; // Too short for permissive match
                foreach ($files as $f) {
                    $fLower = strtolower($f);
                    $fNameOnly = strtolower(pathinfo($f, PATHINFO_FILENAME));
                    // Match if filename starts with candidate, or has it after a space/underscore
                    if (strpos($fNameOnly, $c) === 0 || strpos($fNameOnly, '_' . $c) !== false || strpos($fNameOnly, ' ' . $c) !== false) {
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if (in_array($ext, $extensions)) {
                            return self::$avatarUrlCache[$cacheKey] = 'public/avatar/' . rawurlencode($f);
                        }
                    }
                }
            }
        }

        return self::$avatarUrlCache[$cacheKey] = '';
    }

    private static function getAvatarDir(): string
    {
        if (self::$avatarDirCache !== null) {
            return self::$avatarDirCache;
        }

        $root = realpath(__DIR__ . '/../../');
        if (!$root) {
            $root = realpath('.');
        }

        self::$avatarDirCache = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR;
        return self::$avatarDirCache;
    }

    private static function getAvatarFiles(): array
    {
        if (self::$avatarFilesCache !== null) {
            return self::$avatarFilesCache;
        }

        $avatarDir = self::getAvatarDir();
        if (!is_dir($avatarDir)) {
            self::$avatarFilesCache = [];
            return self::$avatarFilesCache;
        }

        self::$avatarFilesCache = array_values(array_diff(scandir($avatarDir), ['.', '..']));
        return self::$avatarFilesCache;
    }

    private static function resolveAvatarFilename(?string $filename): string
    {
        $filename = trim((string)$filename);
        if ($filename === '') {
            return '';
        }

        $safeFilename = basename($filename);
        $avatarDir = self::getAvatarDir();

        if (file_exists($avatarDir . $safeFilename)) {
            return 'public/avatar/' . rawurlencode($safeFilename);
        }

        $safeLower = strtolower($safeFilename);
        foreach (self::getAvatarFiles() as $file) {
            if (strtolower($file) === $safeLower) {
                return 'public/avatar/' . rawurlencode($file);
            }
        }

        return '';
    }

    private static function getSilAvatarFilename(?string $username, ?string $name, ?int $silId = null): string
    {
        $username = strtolower(trim((string)$username));
        $name = strtolower(trim((string)$name));
        $sid = $silId ? (int)$silId : 0;
        $cacheKey = $sid . '|' . $username . '|' . $name;

        if (array_key_exists($cacheKey, self::$silAvatarFilenameCache)) {
            return self::$silAvatarFilenameCache[$cacheKey];
        }

        try {
            $silDb = DB::getSilInstance();

            if ($sid > 0) {
                $row = $silDb->prepare("
                    SELECT nama_avatar
                    FROM tb_arsipuser
                    WHERE id_user = ?
                    LIMIT 1
                ");
                $row->execute([$sid]);
                $result = $row->fetch();
                if (!empty($result['nama_avatar'])) {
                    return self::$silAvatarFilenameCache[$cacheKey] = trim($result['nama_avatar']);
                }
            }

            if ($username !== '') {
                $row = $silDb->prepare("
                    SELECT nama_avatar
                    FROM tb_arsipuser
                    WHERE LOWER(TRIM(login)) = ?
                    LIMIT 1
                ");
                $row->execute([$username]);
                $result = $row->fetch();
                if (!empty($result['nama_avatar'])) {
                    return self::$silAvatarFilenameCache[$cacheKey] = trim($result['nama_avatar']);
                }
            }

            if ($name !== '') {
                $row = $silDb->prepare("
                    SELECT nama_avatar
                    FROM tb_arsipuser
                    WHERE LOWER(TRIM(nama_user)) = ?
                    LIMIT 1
                ");
                $row->execute([$name]);
                $result = $row->fetch();
                if (!empty($result['nama_avatar'])) {
                    return self::$silAvatarFilenameCache[$cacheKey] = trim($result['nama_avatar']);
                }
            }
        } catch (\Throwable $e) {
            // Keep avatar resolution non-fatal if SIL2020 is temporarily unavailable.
        }

        return self::$silAvatarFilenameCache[$cacheKey] = '';
    }

    public static function ensureUserExistsBySilId(int $silId): int
    {
        $existing = DB::fetch('SELECT id FROM users WHERE sil_id = ?', [$silId]);
        if ($existing) return (int)$existing['id'];

        $silDb = DB::getSilInstance();
        $stmt = $silDb->prepare("SELECT id_user, login AS username, nama_user FROM tb_arsipuser WHERE id_user = ?");
        $stmt->execute([$silId]);
        $silUser = $stmt->fetch();

        if (!$silUser) return 0;

        self::syncFromSil([
            'id_user' => $silUser['id_user'],
            'login' => $silUser['username'],
            'nama_user' => $silUser['nama_user'],
        ]);

        $new = DB::fetch('SELECT id FROM users WHERE sil_id = ?', [$silId]);
        if ($new) {
            return (int)$new['id'];
        }

        $login = trim($silUser['username'] ?? '');
        if ($login !== '') {
            $byUser = DB::fetch('SELECT id FROM users WHERE LOWER(username) = LOWER(?)', [$login]);
            if ($byUser) {
                return (int)$byUser['id'];
            }
        }

        return 0;
    }
}
