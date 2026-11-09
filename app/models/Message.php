<?php
// ============================================
// app/models/Message.php
// ============================================

class Message
{
    public static function create(int $chatId, int $senderId, string $message, ?int $replyToId = null): int
    {
        DB::run(
            'INSERT INTO messages (chat_id, sender_id, reply_to_id, message) VALUES (?,?,?,?)',
            [$chatId, $senderId, $replyToId, $message]
        );
        // Bump chat updated_at
        DB::run('UPDATE chats SET updated_at = NOW() WHERE id = ?', [$chatId]);
        return (int) DB::lastId();
    }

    public static function getByChatId(int $chatId, int $sinceId = 0): array
    {
        $rows = DB::fetchAll("
            SELECT
                m.*,
                u.name       AS sender_name,
                u.username   AS sender_username,
                u.role       AS sender_role,
                u.avatar     AS sender_avatar,
                u.sil_id     AS sender_sil_id,
                rm.message   AS reply_message,
                rm.sender_id AS reply_sender_id,
                ru.name      AS reply_sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            LEFT JOIN messages rm ON rm.id = m.reply_to_id
            LEFT JOIN users ru ON ru.id = rm.sender_id
            WHERE m.chat_id = ? AND m.id > ?
            ORDER BY m.id ASC
        ", [$chatId, $sinceId]);

        return $rows;
    }

    public static function findById(int $id): ?array
    {
        return DB::fetch("
            SELECT m.*, u.name AS sender_name, u.username AS sender_username
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.id = ?
        ", [$id]) ?: null;
    }

    /** Pesan identik dalam 3 detik terakhir (cegah double-submit). */
    public static function findRecentDuplicate(int $chatId, int $senderId, string $message, ?int $replyToId = null): ?array
    {
        $replyClause = $replyToId === null ? 'AND m.reply_to_id IS NULL' : 'AND m.reply_to_id = ?';
        $params = [$chatId, $senderId, $message];
        if ($replyToId !== null) {
            $params[] = $replyToId;
        }

        return DB::fetch("
            SELECT
                m.*,
                u.name       AS sender_name,
                u.username   AS sender_username,
                u.role       AS sender_role,
                u.avatar     AS sender_avatar,
                u.sil_id     AS sender_sil_id,
                rm.message   AS reply_message,
                rm.sender_id AS reply_sender_id,
                ru.name      AS reply_sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            LEFT JOIN messages rm ON rm.id = m.reply_to_id
            LEFT JOIN users ru ON ru.id = rm.sender_id
            WHERE m.chat_id = ?
              AND m.sender_id = ?
              AND m.message = ?
              {$replyClause}
              AND m.created_at >= DATE_SUB(NOW(), INTERVAL 3 SECOND)
            ORDER BY m.id DESC
            LIMIT 1
        ", $params) ?: null;
    }

    public static function pin(int $id)
    {
        $msg = DB::fetch("SELECT id, chat_id FROM messages WHERE id = ?", [$id]);
        if (!$msg) {
            return;
        }
        DB::run("UPDATE messages SET is_pinned = 0 WHERE chat_id = ?", [(int)$msg['chat_id']]);
        DB::run("UPDATE messages SET is_pinned = 1 WHERE id = ?", [$id]);
    }

    public static function unpin(int $id)
    {
        DB::run("UPDATE messages SET is_pinned = 0 WHERE id = ?", [$id]);
    }

    public static function getPinned(int $chatId): ?array
    {
        return DB::fetch("
            SELECT m.*, u.name as sender_name 
            FROM messages m 
            JOIN users u ON u.id = m.sender_id
            WHERE m.chat_id = ? AND m.is_pinned = 1 
            ORDER BY m.id DESC LIMIT 1
        ", [$chatId]);
    }
}
