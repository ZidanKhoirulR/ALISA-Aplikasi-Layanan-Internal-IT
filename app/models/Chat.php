<?php
// ============================================
// app/models/Chat.php
// ============================================

class Chat
{
    private static function hasZoomSchedulesTable(): bool
    {
        return DB::tableExists('zoom_schedules');
    }

    // Fetch list of chats for sidebar
    public static function listForUser($userId, $role, $filter = 'all', $startDate = null, $endDate = null)
    {
        $params = [];
        $whereRole = self::buildRoleWhere($userId, $role, $params);

        $whereStatus = '';
        if ($filter === 'active') {
            $whereStatus = "AND c.status IN ('waiting','in_progress') AND c.type = 'ticket'";
        } elseif ($filter === 'done') {
            $whereStatus = "AND c.status = 'done' AND c.type = 'ticket'";
        } elseif ($filter === 'waiting') {
            $whereStatus = "AND c.status != 'done' AND c.teknisi_id IS NULL AND c.type = 'ticket'";
        } elseif ($filter === 'in_progress') {
            $whereStatus = "AND c.status != 'done' AND c.teknisi_id IS NOT NULL AND c.type = 'ticket'";
        } elseif ($filter === 'group') {
            $whereStatus = "AND c.type = 'group'";
        } else {
            // 'all' filter: show all service requests (ticket only), sorted by latest chat time
            $whereStatus = "AND c.type = 'ticket'";
        }

        $whereExtra = '';
        if (!empty($startDate)) {
            $params['start_date'] = $startDate . ' 00:00:00';
            $whereExtra .= " AND c.created_at >= :start_date";
        }
        if (!empty($endDate)) {
            $params['end_date'] = $endDate . ' 23:59:59';
            $whereExtra .= " AND c.created_at <= :end_date";
        }

        $zoomJoin = '';
        $zoomSelect = 'NULL AS zoom_start, NULL AS zoom_end';
        if (self::hasZoomSchedulesTable()) {
            $zoomJoin = 'LEFT JOIN zoom_schedules zs ON zs.chat_id = c.id';
            $zoomSelect = 'zs.waktu_mulai AS zoom_start, zs.waktu_selesai AS zoom_end';
        }

        $sql = "
            SELECT
                c.*,
                gc.title AS group_title,
                u.name  AS user_name,
                u.username AS user_username,
                u.avatar AS user_avatar,
                u.sil_id AS user_sil_id,
                t.name  AS teknisi_name,
                t.username AS teknisi_username,
                t.status AS teknisi_status,
                t.sil_id AS teknisi_sil_id,
                EXISTS (
                    SELECT 1
                    FROM messages rm
                    JOIN users ru ON ru.id = rm.sender_id
                    WHERE rm.chat_id = c.id
                      AND ru.role IN ('teknisi','admin')
                ) AS is_responded,
                (SELECT message FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_msg_time,
                (SELECT COUNT(*) FROM chats WHERE group_id = c.id AND status IN ('waiting','in_progress')) AS group_req_count,
                (SELECT GROUP_CONCAT(pu.name SEPARATOR ', ') FROM group_participants gp JOIN users pu ON pu.id = gp.user_id WHERE gp.chat_id = c.id) AS participant_names,
                {$zoomSelect}
            FROM chats c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            {$zoomJoin}
            LEFT JOIN chats gc ON gc.id = c.group_id
            WHERE {$whereRole} {$whereStatus} {$whereExtra}
            ORDER BY
                c.is_pinned DESC,
                c.pinned_at DESC,
                CASE WHEN c.status = 'done' THEN 1 ELSE 0 END ASC,
                COALESCE(last_msg_time, c.created_at) DESC
        ";

        return DB::fetchAll($sql, $params);
    }
    /** Tiket layanan aktif (bukan grup chat). */
    public static function listTicketNotifications(): array
    {
        $zoomJoin = '';
        $zoomSelect = 'NULL AS zoom_start, NULL AS zoom_end';
        if (self::hasZoomSchedulesTable()) {
            $zoomJoin = 'LEFT JOIN zoom_schedules zs ON zs.chat_id = c.id';
            $zoomSelect = 'zs.waktu_mulai AS zoom_start, zs.waktu_selesai AS zoom_end';
        }

        return DB::fetchAll("
            SELECT
                c.*,
                u.name AS user_name,
                u.username AS user_username,
                u.avatar AS user_avatar,
                u.sil_id AS user_sil_id,
                t.name AS teknisi_name,
                t.username AS teknisi_username,
                t.sil_id AS teknisi_sil_id,
                EXISTS (
                    SELECT 1
                    FROM messages rm
                    JOIN users ru ON ru.id = rm.sender_id
                    WHERE rm.chat_id = c.id
                      AND ru.role IN ('teknisi','admin')
                ) AS is_responded,
                (SELECT message FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_msg_time,
                {$zoomSelect}
            FROM chats c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            {$zoomJoin}
            WHERE c.type = 'ticket'
              AND c.status IN ('waiting', 'in_progress')
            ORDER BY COALESCE(last_msg_time, c.updated_at, c.created_at) DESC
        ");
    }

    /**
     * Grup chat dengan pesan belum dibalas admin/teknisi (notif = percakapan di grup).
     */
    public static function listGroupChatNotifications(): array
    {
        return DB::fetchAll("
            SELECT
                c.*,
                c.title AS group_title,
                u.name AS user_name,
                u.username AS user_username,
                u.avatar AS user_avatar,
                u.sil_id AS user_sil_id,
                (SELECT message FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_msg_time,
                (
                    SELECT COUNT(*)
                    FROM messages m
                    WHERE m.chat_id = c.id
                      AND m.id > COALESCE((
                          SELECT MAX(m2.id)
                          FROM messages m2
                          JOIN users u2 ON u2.id = m2.sender_id
                          WHERE m2.chat_id = c.id
                            AND u2.role IN ('admin', 'teknisi')
                      ), 0)
                ) AS unread_count,
                (
                    SELECT pu.name
                    FROM messages lm
                    JOIN users pu ON pu.id = lm.sender_id
                    WHERE lm.chat_id = c.id
                    ORDER BY lm.id DESC
                    LIMIT 1
                ) AS last_sender_name,
                (
                    SELECT GROUP_CONCAT(pu.name SEPARATOR ', ')
                    FROM group_participants gp
                    JOIN users pu ON pu.id = gp.user_id
                    WHERE gp.chat_id = c.id
                ) AS participant_names
            FROM chats c
            JOIN users u ON u.id = c.user_id
            WHERE c.type = 'group'
              AND EXISTS (SELECT 1 FROM messages WHERE chat_id = c.id)
              AND (
                  SELECT COUNT(*)
                  FROM messages m
                  WHERE m.chat_id = c.id
                    AND m.id > COALESCE((
                        SELECT MAX(m2.id)
                        FROM messages m2
                        JOIN users u2 ON u2.id = m2.sender_id
                        WHERE m2.chat_id = c.id
                          AND u2.role IN ('admin', 'teknisi')
                    ), 0)
              ) > 0
            ORDER BY COALESCE(last_msg_time, c.updated_at, c.created_at) DESC
        ");
    }

    public static function countGroupChatNotifications(): int
    {
        $row = DB::fetch("
            SELECT COUNT(*) AS cnt
            FROM chats c
            WHERE c.type = 'group'
              AND EXISTS (SELECT 1 FROM messages WHERE chat_id = c.id)
              AND (
                  SELECT COUNT(*)
                  FROM messages m
                  WHERE m.chat_id = c.id
                    AND m.id > COALESCE((
                        SELECT MAX(m2.id)
                        FROM messages m2
                        JOIN users u2 ON u2.id = m2.sender_id
                        WHERE m2.chat_id = c.id
                          AND u2.role IN ('admin', 'teknisi')
                    ), 0)
              ) > 0
        ");
        return (int)($row['cnt'] ?? 0);
    }

    /** Gabungan tiket + grup untuk modal notifikasi admin. */
    public static function listAllNotifications(): array
    {
        $tickets = self::listTicketNotifications();
        $groups  = self::listGroupChatNotifications();

        foreach ($tickets as &$t) {
            $t['notif_type'] = 'ticket';
        }
        unset($t);

        foreach ($groups as &$g) {
            $g['notif_type'] = 'group';
            $g['type'] = 'group';
        }
        unset($g);

        $items = array_merge($tickets, $groups);
        usort($items, static function ($a, $b) {
            $ta = strtotime($a['last_msg_time'] ?? $a['updated_at'] ?? $a['created_at'] ?? 0);
            $tb = strtotime($b['last_msg_time'] ?? $b['updated_at'] ?? $b['created_at'] ?? 0);
            return $tb <=> $ta;
        });

        return $items;
    }

    public static function listForUserPaged(int $userId, string $role, string $filter = 'all', string $q = '', ?string $startDate = null, ?string $endDate = null, int $page = 1, int $limit = 20): array
    {
        $params = [];
        $whereRole = self::buildRoleWhere($userId, $role, $params);
        $whereStatus = self::buildStatusWhere($filter);

        $limit = max(1, min(100, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $whereExtra = [];
        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $params['q1'] = $qLike;
            $params['q2'] = $qLike;
            $params['q3'] = $qLike;
            $params['q4'] = $qLike;
            $whereExtra[] = "(c.title LIKE :q1 OR c.category LIKE :q2 OR u.name LIKE :q3 OR u.username LIKE :q4)";
        }
        if (!empty($startDate)) {
            $params['start_date'] = $startDate . ' 00:00:00';
            $whereExtra[] = "c.created_at >= :start_date";
        }
        if (!empty($endDate)) {
            $params['end_date'] = $endDate . ' 23:59:59';
            $whereExtra[] = "c.created_at <= :end_date";
        }

        $extraSql = '';
        if (!empty($whereExtra)) {
            $extraSql = ' AND ' . implode(' AND ', $whereExtra);
        }

        $countSql = "
            SELECT COUNT(*) AS total
            FROM chats c
            JOIN users u ON u.id = c.user_id
            WHERE {$whereRole} {$whereStatus} {$extraSql}
        ";
        $countRow = DB::fetch($countSql, $params);
        $total = (int)($countRow['total'] ?? 0);

        $itemsSql = "
            SELECT
                c.*,
                gc.title AS group_title,
                u.name  AS user_name,
                u.username AS user_username,
                u.avatar AS user_avatar,
                u.sil_id AS user_sil_id,
                t.name  AS teknisi_name,
                t.username AS teknisi_username,
                t.status AS teknisi_status,
                t.sil_id AS teknisi_sil_id,
                EXISTS (
                    SELECT 1
                    FROM messages rm
                    JOIN users ru ON ru.id = rm.sender_id
                    WHERE rm.chat_id = c.id
                      AND ru.role IN ('teknisi','admin')
                ) AS is_responded,
                (SELECT message FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_msg_time,
                (SELECT COUNT(*) FROM chats WHERE group_id = c.id AND status IN ('waiting','in_progress')) AS group_req_count,
                (SELECT GROUP_CONCAT(pu.name SEPARATOR ', ') FROM group_participants gp JOIN users pu ON pu.id = gp.user_id WHERE gp.chat_id = c.id) AS participant_names
            FROM chats c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            LEFT JOIN chats gc ON gc.id = c.group_id
            WHERE {$whereRole} {$whereStatus} {$extraSql}
            ORDER BY
                c.is_pinned DESC,
                c.pinned_at DESC,
                CASE WHEN c.status = 'done' THEN 1 ELSE 0 END ASC,
                COALESCE(last_msg_time, c.created_at) DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $items = DB::fetchAll($itemsSql, $params);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
            ],
        ];
    }

    public static function isGroupParticipant(int $chatId, int $userId): bool
    {
        if ($userId <= 0 || $chatId <= 0) {
            return false;
        }
        $row = DB::fetch(
            'SELECT 1 FROM group_participants WHERE chat_id = ? AND user_id = ? LIMIT 1',
            [$chatId, $userId]
        );
        return !empty($row);
    }

    /**
     * Authorization: who may read/send in a chat.
     * - user: own tickets only; group chats where listed as participant
     * - teknisi: ticket queue (waiting) or assigned tickets; all group chats (IT)
     * - admin: full access
     */
    public static function canUserAccessChat(int $chatId, int $userId, string $role): bool
    {
        if ($chatId <= 0 || $userId <= 0) {
            return false;
        }

        $chat = self::findById($chatId);
        if (!$chat) {
            return false;
        }

        $role = strtolower(trim($role));
        $type = (string)($chat['type'] ?? 'ticket');

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'teknisi') {
            if ($type === 'group') {
                return true;
            }
            $status = (string)($chat['status'] ?? 'waiting');
            if ($status === 'waiting') {
                return true;
            }
            return (int)($chat['teknisi_id'] ?? 0) === $userId;
        }

        if ($role === 'user') {
            if ($type === 'group') {
                return self::isGroupParticipant($chatId, $userId);
            }
            return (int)($chat['user_id'] ?? 0) === $userId;
        }

        return false;
    }

    // Single chat with full details
    public static function findById($chatId)
    {
        return DB::fetch("
            SELECT
                c.*,
                gc.title AS group_title,
                u.name   AS user_name,
                u.username AS user_username,
                u.avatar AS user_avatar,
                u.sil_id AS user_sil_id,
                u.email  AS user_email,
                t.name   AS teknisi_name,
                t.username AS teknisi_username,
                t.status AS teknisi_status,
                t.avatar AS teknisi_avatar,
                t.sil_id AS teknisi_sil_id,
                TIMESTAMPDIFF(SECOND, c.created_at, c.updated_at) as duration_seconds,
                EXISTS (
                    SELECT 1
                    FROM messages rm
                    JOIN users ru ON ru.id = rm.sender_id
                    WHERE rm.chat_id = c.id
                      AND ru.role IN ('teknisi','admin')
                ) AS is_responded
            FROM chats c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            LEFT JOIN chats gc ON gc.id = c.group_id
            WHERE c.id = ?
        ", [$chatId]);
    }

    // Create a new chat
    public static function create($userId, $title, $category = 'Umum', $room = null, $groupId = null)
    {
        DB::run(
            'INSERT INTO chats (user_id, teknisi_id, title, category, room, status, group_id) VALUES (?,?,?,?,?,?,?)',
            [
                $userId,
                null,
                $title,
                $category,
                $room,
                'waiting',
                $groupId,
            ]
        );
        $chatId = (int) DB::lastId();

        // The first message in the chat is the employee request description
        Message::create($chatId, $userId, $title);

        return $chatId;
    }

    // Mark in_progress when technician opens chat
    public static function markInProgress($chatId, $tekId)
    {
        DB::run(
            "UPDATE chats SET status='in_progress', teknisi_id=? WHERE id=? AND status='waiting'",
            [$tekId, $chatId]
        );
    }

    // Close chat
    public static function close($chatId)
    {
        $chat = self::findById($chatId);
        DB::run("UPDATE chats SET status='done' WHERE id=?", [$chatId]);

        // Free technician if no other active chats assigned
        if ($chat && $chat['teknisi_id']) {
            $active = DB::fetch(
                "SELECT COUNT(*) AS cnt FROM chats WHERE teknisi_id=? AND status='in_progress'",
                [$chat['teknisi_id']]
            );
            if ((int)($active['cnt'] ?? 1) === 0) {
                User::updateStatus((int)$chat['teknisi_id'], 'available');
            }
        }
    }

    // Get counts for admin dashboard
    public static function getStats($start = null, $end = null)
    {
        $where = ' WHERE 1=1 ';
        $params = [];
        if ($start && $end) {
            $where = ' WHERE c.created_at BETWEEN ? AND ? ';
            $params = [$start . ' 00:00:00', $end . ' 23:59:59'];
        }

        $statusStats = DB::fetch("
            SELECT
                SUM(CASE WHEN c.status != 'done' AND c.teknisi_id IS NULL THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN c.status != 'done' AND c.teknisi_id IS NOT NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN c.status = 'done' THEN 1 ELSE 0 END) as done
            FROM chats c
            {$where} AND (c.type IS NULL OR c.type != 'group')
        ", $params);

        $result = [
            'waiting'     => (int)($statusStats['waiting'] ?? 0),
            'in_progress' => (int)($statusStats['in_progress'] ?? 0),
            'done'        => (int)($statusStats['done'] ?? 0),
            'categories'  => [],
            'performance' => []
        ];

        $masterCategories = [
            'Jaringan Internet',
            'Laptop/Komputer',
            'Printer',
            'Zoom Meeting',
            'Software',
            'Sistem Informasi',
            'Lainnya'
        ];

        // Only count requests that haven't been worked on yet (waiting = no teknisi assigned, not done)
        $waitingWhere = ' WHERE (c.status != \'done\' AND c.teknisi_id IS NULL) AND (c.type IS NULL OR c.type != \'group\') ';
        $waitingParams = [];
        if ($start && $end) {
            $waitingWhere .= ' AND c.created_at BETWEEN ? AND ? ';
            $waitingParams = [$start . ' 00:00:00', $end . ' 23:59:59'];
        }

        $dbCategories = DB::fetchAll("
            SELECT c.category, COUNT(*) as cnt
            FROM chats c
            {$waitingWhere}
            GROUP BY c.category
        ", $waitingParams);

        $catMap = [];
        foreach ($dbCategories as $dc) {
            $catMap[$dc['category']] = (int)$dc['cnt'];
        }

        foreach ($masterCategories as $mc) {
            $result['categories'][] = [
                'category' => $mc,
                'cnt' => $catMap[$mc] ?? 0
            ];
            unset($catMap[$mc]);
        }

        foreach ($catMap as $cat => $cnt) {
            $result['categories'][] = [
                'category' => $cat,
                'cnt' => $cnt
            ];
        }

        usort($result['categories'], function ($a, $b) {
            return $b['cnt'] <=> $a['cnt'];
        });

        $perfWhere = "
            WHERE c.status = 'done' AND (c.type IS NULL OR c.type != 'group')
        ";
        $perfParams = [];
        if ($start && $end) {
            $perfWhere .= ' AND c.created_at BETWEEN ? AND ?';
            $perfParams = [$start . ' 00:00:00', $end . ' 23:59:59'];
        }

        $result['performance'] = DB::fetchAll("
            SELECT u.id, u.name, u.username, u.role, u.sil_id, COUNT(*) as completed_count
            FROM chats c
            JOIN users u ON u.id = c.teknisi_id
            {$perfWhere}
            GROUP BY u.id, u.name, u.username, u.role, u.sil_id
            ORDER BY completed_count DESC
        ", $perfParams);

        $avgTimeRes = DB::fetch("
            SELECT AVG(TIMESTAMPDIFF(SECOND, c.created_at, c.updated_at)) as avg_seconds
            FROM chats c
            {$perfWhere}
        ", $perfParams);
        $result['avg_seconds'] = (float)($avgTimeRes['avg_seconds'] ?? 0);

        $dbAvg = DB::fetchAll("
            SELECT c.category, AVG(TIMESTAMPDIFF(SECOND, c.created_at, c.updated_at)) as avg_seconds
            FROM chats c
            {$perfWhere}
            GROUP BY c.category
        ", $perfParams);

        $avgMap = [];
        foreach ($dbAvg as $da) {
            $avgMap[$da['category']] = (float)$da['avg_seconds'];
        }

        $result['avg_by_category'] = [];
        foreach ($masterCategories as $mc) {
            $result['avg_by_category'][] = [
                'category' => $mc,
                'avg_seconds' => $avgMap[$mc] ?? 0
            ];
            unset($avgMap[$mc]);
        }
        foreach ($avgMap as $cat => $avg) {
            $result['avg_by_category'][] = [
                'category' => $cat,
                'avg_seconds' => $avg
            ];
        }

        // Per-category breakdown by status (for the "Semua Permintaan per Kategori" chart)
        $allCatRows = DB::fetchAll("
            SELECT c.category,
                SUM(CASE WHEN c.status != 'done' AND c.teknisi_id IS NULL THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN c.status != 'done' AND c.teknisi_id IS NOT NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN c.status = 'done' THEN 1 ELSE 0 END) as done,
                COUNT(*) as total
            FROM chats c
            {$where} AND (c.type IS NULL OR c.type != 'group')
            GROUP BY c.category
        ", $params);

        $allCatMap = [];
        foreach ($allCatRows as $row) {
            $allCatMap[$row['category']] = $row;
        }

        $result['categories_all'] = [];
        foreach ($masterCategories as $mc) {
            $result['categories_all'][] = [
                'category'    => $mc,
                'waiting'     => (int)($allCatMap[$mc]['waiting']     ?? 0),
                'in_progress' => (int)($allCatMap[$mc]['in_progress'] ?? 0),
                'done'        => (int)($allCatMap[$mc]['done']        ?? 0),
                'total'       => (int)($allCatMap[$mc]['total']       ?? 0),
            ];
            unset($allCatMap[$mc]);
        }
        foreach ($allCatMap as $cat => $row) {
            $result['categories_all'][] = [
                'category'    => $cat,
                'waiting'     => (int)$row['waiting'],
                'in_progress' => (int)$row['in_progress'],
                'done'        => (int)$row['done'],
                'total'       => (int)$row['total'],
            ];
        }

        return $result;
    }

    // Get breakdown of who requested a specific category
    public static function getCategoryUserBreakdown($category, $start = null, $end = null)
    {
        $where = ' WHERE c.category = ? AND (c.type IS NULL OR c.type != \'group\') ';
        $params = [$category];
        if ($start && $end) {
            $where .= ' AND c.created_at BETWEEN ? AND ? ';
            $params[] = $start . ' 00:00:00';
            $params[] = $end . ' 23:59:59';
        }

        return DB::fetchAll("
            SELECT u.id, u.name, u.username, u.role, u.sil_id, COUNT(*) as cnt
            FROM chats c
            JOIN users u ON u.id = c.user_id
            {$where}
            GROUP BY u.id, u.name, u.username, u.role, u.sil_id
            ORDER BY cnt DESC
        ", $params);
    }

    // Get list of chats handled by a specific technician
    public static function getTechnicianPerformanceBreakdown($techId, $start = null, $end = null)
    {
        $where = ' WHERE c.teknisi_id = ? AND c.status = \'done\' AND (c.type IS NULL OR c.type != \'group\') ';
        $params = [$techId];
        if ($start && $end) {
            $where .= ' AND c.created_at BETWEEN ? AND ? ';
            $params[] = $start . ' 00:00:00';
            $params[] = $end . ' 23:59:59';
        }

        return DB::fetchAll("
            SELECT c.*, u.name as user_name, u.username as user_username, u.sil_id as user_sil_id, gc.title AS group_title
            FROM chats c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN chats gc ON gc.id = c.group_id
            {$where}
            ORDER BY c.created_at DESC
        ", $params);
    }

    // Get completion time breakdown by category
    public static function getCompletionTimeBreakdown($start = null, $end = null)
    {
        $where = " WHERE c.status = 'done' ";
        $params = [];
        if ($start && $end) {
            $where .= ' AND c.created_at BETWEEN ? AND ? ';
            $params = [$start . ' 00:00:00', $end . ' 23:59:59'];
        }

        return DB::fetchAll("
            SELECT
                c.id,
                c.title,
                c.category,
                c.created_at,
                c.updated_at as closed_at,
                TIMESTAMPDIFF(SECOND, c.created_at, c.updated_at) as duration_seconds,
                u.name as user_name,
                t.name as teknisi_name
            FROM chats c
            JOIN users u ON u.id = c.user_id
            JOIN users t ON t.id = c.teknisi_id
            {$where}
            ORDER BY c.category ASC, c.updated_at DESC
        ", $params);
    }

    public static function getAdminHistory($start, $end): array
    {
        $rows = DB::fetchAll("
            SELECT
                c.id,
                c.user_id,
                c.title,
                COALESCE(c.category, 'Umum') AS category,
                NULLIF(c.room, '')           AS room,
                c.created_at,
                c.updated_at                 AS closed_at,
                u.id                         AS pemohon_id,
                COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), 'Tanpa Nama') AS pemohon_name,
                u.username                   AS pemohon_username,
                u.sil_id                     AS pemohon_sil_id,
                COALESCE(NULLIF(t.name, ''), NULLIF(t.username, '')) AS teknisi_name
            FROM chats c
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            WHERE c.status = 'done'
              AND c.created_at BETWEEN ? AND ?
            ORDER BY c.updated_at DESC
        ", [$start . ' 00:00:00', $end . ' 23:59:59']);

        foreach ($rows as &$row) {
            $row['category'] = trim((string)($row['category'] ?? '')) !== '' ? $row['category'] : 'Umum';
            $row['room'] = trim((string)($row['room'] ?? '')) !== '' ? $row['room'] : null;
        }

        return $rows;
    }

    public static function getAdminZoomHistory($start, $end): array
    {
        if (!self::hasZoomSchedulesTable()) {
            return [];
        }

        $rows = DB::fetchAll("
            SELECT
                z.id,
                z.chat_id,
                z.butuh_link,
                COALESCE(NULLIF(z.request_type, ''), CASE WHEN z.butuh_link = 1 THEN 'link_tech' ELSE 'own_link' END) AS request_type,
                z.id_meeting,
                z.passcode,
                z.waktu_mulai,
                z.waktu_selesai,
                z.nama_kegiatan,
                z.ruangan,
                c.status      AS chat_status,
                c.updated_at  AS closed_at,
                u.id          AS pemohon_id,
                COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), 'Tanpa Nama') AS pemohon_name,
                u.username    AS pemohon_username,
                u.sil_id      AS pemohon_sil_id,
                COALESCE(NULLIF(t.name, ''), NULLIF(t.username, '')) AS teknisi_name
            FROM zoom_schedules z
            LEFT JOIN chats c ON c.id = z.chat_id
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN users t ON t.id = c.teknisi_id
            WHERE z.waktu_mulai BETWEEN ? AND ?
            ORDER BY z.waktu_mulai DESC
        ", [$start . ' 00:00:00', $end . ' 23:59:59']);

        foreach ($rows as &$row) {
            $row['chat_status'] = $row['chat_status'] ?: 'waiting';
        }

        return $rows;
    }

    public static function getUserCategoryRestrictions($userId): array
    {
        $rows = DB::fetchAll("
            SELECT c.category 
            FROM chats c
            JOIN group_participants gp ON gp.chat_id = c.id
            WHERE c.type = 'group' AND gp.user_id = ? AND c.category IS NOT NULL AND c.category != 'Grup'
        ", [$userId]);
        
        $categories = [];
        foreach ($rows as $row) {
            $parts = explode(',', $row['category']);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p) $categories[] = $p;
            }
        }
        return array_unique($categories);
    }

    private static $lastFailedSilSyncIds = [];

    public static function getLastFailedSilSyncIds(): array
    {
        return self::$lastFailedSilSyncIds;
    }

    public static function createGroup($creatorId, $title, $description = '', $category = 'Grup', $participantIds = [], $silIds = [], $iconPath = null)
    {
        DB::run(
            "INSERT INTO chats (user_id, title, description, category, status, type, icon_path) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$creatorId, $title, $description, $category ?: 'Grup', 'waiting', 'group', $iconPath]
        );
        $chatId = (int) DB::lastId();

        // Add creator as participant
        self::addParticipant($chatId, $creatorId);

        // Add regular participants (local IDs)
        if (is_array($participantIds)) {
            foreach ($participantIds as $pId) {
                if ((int)$pId !== (int)$creatorId) {
                    self::addParticipant($chatId, $pId);
                }
            }
        }

        // Add participants by SIL ID (sync if needed)
        if (is_array($silIds)) {
            foreach ($silIds as $silId) {
                $localId = User::ensureUserExistsBySilId((int)$silId);
                if ($localId && (int)$localId !== (int)$creatorId) {
                    self::addParticipant($chatId, $localId);
                }
            }
        }

        // System message
        Message::create($chatId, $creatorId, "📢 Grup \"$title\" telah dibuat.");

        return $chatId;
    }

    public static function updateGroup($chatId, $title, $description = '', $category = 'Grup', $participantIds = [], $silIds = [], $iconPath = null)
    {
        // 1. Data Normalization: Cek status $iconPath
        if ($iconPath === '') {
            // Flag dari controller kalau user klik "Hapus Icon"
            DB::run("UPDATE chats SET title = ?, description = ?, category = ?, icon_path = NULL WHERE id = ?", [$title, $description, $category ?: 'Grup', $chatId]);
        } elseif ($iconPath !== null) {
            // Upload foto baru (Timpa)
            DB::run("UPDATE chats SET title = ?, description = ?, category = ?, icon_path = ? WHERE id = ?", [$title, $description, $category ?: 'Grup', $iconPath, $chatId]);
        } else {
            // Tidak ada file yang diupload & tidak minta dihapus (Biarkan icon lama)
            DB::run("UPDATE chats SET title = ?, description = ?, category = ? WHERE id = ?", [$title, $description, $category ?: 'Grup', $chatId]);
        }

        // 2. Manage participants
        DB::run("DELETE FROM group_participants WHERE chat_id = ?", [$chatId]);

        // Add creator back (always a participant)
        $chat = self::findById($chatId);
        self::addParticipant($chatId, $chat['user_id']);

        // Add regular participants
        if (is_array($participantIds)) {
            foreach ($participantIds as $pId) {
                self::addParticipant($chatId, $pId);
            }
        }

        // Add SIL participants
        if (is_array($silIds)) {
            foreach ($silIds as $silId) {
                $localId = User::ensureUserExistsBySilId((int)$silId);
                if ($localId) {
                    self::addParticipant($chatId, $localId);
                }
            }
        }
    }

    private static function deleteGroupIconFile(?string $path): void
    {
        if (!$path || strpos($path, 'public/group-icons/') !== 0) {
            return;
        }
        $file = Storage::projectRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function removeParticipant($chatId, $userId)
    {
        DB::run("DELETE FROM group_participants WHERE chat_id = ? AND user_id = ?", [$chatId, $userId]);
    }

    public static function addParticipant($chatId, $userId)
    {
        DB::run(
            "INSERT IGNORE INTO group_participants (chat_id, user_id) VALUES (?, ?)",
            [$chatId, $userId]
        );
    }

    public static function getParticipants($chatId)
    {
        return DB::fetchAll("
            SELECT u.id, u.name, u.username, u.avatar, u.role, u.sil_id
            FROM group_participants gp
            JOIN users u ON u.id = gp.user_id
            WHERE gp.chat_id = ?
        ", [$chatId]);
    }

    public static function listGroups()
    {
        return DB::fetchAll("
            SELECT c.*, u.name as creator_name,
                   (SELECT COUNT(*) FROM group_participants WHERE chat_id = c.id) as participant_count
            FROM chats c
            JOIN users u ON u.id = c.user_id
            WHERE c.type = 'group'
            ORDER BY c.created_at DESC
        ");
    }

    public static function deleteGroup($chatId)
    {
        // 1. Delete messages
        DB::run("DELETE FROM messages WHERE chat_id = ?", [$chatId]);
        // 2. Delete participants
        DB::run("DELETE FROM group_participants WHERE chat_id = ?", [$chatId]);
        // 3. Delete chat record
        DB::run("DELETE FROM chats WHERE id = ? AND type = 'group'", [$chatId]);
    }

    private static function buildRoleWhere(int $userId, string $role, array &$params): string
    {
        $role = strtolower(trim($role));

        if ($role === 'user') {
            $params['uid1'] = $userId;
            $params['uid2'] = $userId;
            return "(
                (c.type = 'ticket' AND c.user_id = :uid1)
                OR (c.type = 'group' AND EXISTS (
                    SELECT 1 FROM group_participants gp
                    WHERE gp.chat_id = c.id AND gp.user_id = :uid2
                ))
            )";
        }

        if ($role === 'teknisi') {
            $params['uid1'] = $userId;
            return "(
                c.type = 'group'
                OR (c.type = 'ticket' AND c.status = 'waiting')
                OR (c.type = 'ticket' AND c.teknisi_id = :uid1)
            )";
        }

        return '1=1';
    }

    private static function buildStatusWhere(string $filter): string
    {
        if ($filter === 'active') {
            return "AND c.status IN ('waiting','in_progress') AND c.type = 'ticket'";
        }
        if ($filter === 'done') {
            return "AND c.status = 'done' AND c.type = 'ticket'";
        }
        if ($filter === 'waiting') {
            return "AND c.status != 'done' AND c.teknisi_id IS NULL AND c.type = 'ticket'";
        }
        if ($filter === 'in_progress') {
            return "AND c.status != 'done' AND c.teknisi_id IS NOT NULL AND c.type = 'ticket'";
        }
        if ($filter === 'group') {
            return "AND c.type = 'group'";
        }
        return "AND c.type = 'ticket'";
    }

    public static function pin(int $id): void
    {
        DB::run(
            'UPDATE chats SET is_pinned = 1, pinned_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public static function unpin(int $id): void
    {
        DB::run(
            'UPDATE chats SET is_pinned = 0, pinned_at = NULL WHERE id = ?',
            [$id]
        );
    }
}