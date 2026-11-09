<?php
// ============================================
// app/models/DB.php — PDO Singleton
// ============================================

class DB
{
    private static $instance = null;
    private static $schemaCache = [
        'tables' => [],
        'columns' => []
    ];

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/../../config/config.php';
            $d   = $cfg['db'];
            $dsn = "mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}";
            try {
                self::$instance = new PDO($dsn, $d['user'], $d['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
                ]);
            } catch (PDOException $e) {
                // Throw exception instead of die() so it can be caught in AuthController
                throw $e;
            }
        }
        return self::$instance;
    }

    private static $silInstance = null;

    public static function getSilInstance(): PDO
    {
        if (self::$silInstance === null) {
            $cfg = require __DIR__ . '/../../config/config.php';
            $d   = $cfg['db_sil2020'];
            $dsn = "mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}";
            try {
                self::$silInstance = new PDO($dsn, $d['user'], $d['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
                ]);
            } catch (PDOException $e) {
                // Throw exception instead of die() so it can be caught in AuthController
                throw $e;
            }
        }
        return self::$silInstance;
    }

    // Shortcut helpers
    public static function run(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $errCode = $e->getCode();
            $errMsg = $e->getMessage();
            if (($errCode === '42S22' || $errCode === '42S02') && (
                strpos($errMsg, 'reply_to_id') !== false ||
                strpos($errMsg, 'is_pinned') !== false ||
                strpos($errMsg, 'pinned_at') !== false ||
                strpos($errMsg, 'group_id') !== false ||
                strpos($errMsg, 'group_participants') !== false
            )) {
                try {
                    self::runMigration();
                    // Retry the query
                    $stmt = self::getInstance()->prepare($sql);
                    $stmt->execute($params);
                    return $stmt;
                } catch (\Throwable $migrationError) {
                    throw $e;
                }
            }
            throw $e;
        }
    }

    public static function runMigration(): void
    {
        $pdo = self::getInstance();

        // 1. Create group_participants table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS group_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (chat_id, user_id)
            )
        ");

        // 2. Add columns to chats table
        $chatsCols = [
            "type" => "ENUM('ticket','group') DEFAULT 'ticket'",
            "description" => "TEXT NULL",
            "group_id" => "INT NULL",
            "is_pinned" => "TINYINT(1) DEFAULT 0",
            "pinned_at" => "TIMESTAMP NULL DEFAULT NULL",
        ];

        $stmt = $pdo->query("DESCRIBE chats");
        $existingChatsCols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existingChatsCols[] = $row['Field'];
        }

        foreach ($chatsCols as $col => $def) {
            if (!in_array($col, $existingChatsCols)) {
                $pdo->exec("ALTER TABLE chats ADD COLUMN $col $def");
            }
        }

        // 3. Add columns to messages table
        $msgCols = [
            "reply_to_id" => "INT NULL",
            "is_pinned" => "TINYINT(1) DEFAULT 0"
        ];

        $stmt = $pdo->query("DESCRIBE messages");
        $existingMsgCols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existingMsgCols[] = $row['Field'];
        }

        foreach ($msgCols as $col => $def) {
            if (!in_array($col, $existingMsgCols)) {
                $pdo->exec("ALTER TABLE messages ADD COLUMN $col $def");
            }
        }
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        return self::run($sql, $params)->fetch() ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function lastId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    public static function tableExists(string $tableName): bool
    {
        $key = strtolower(trim($tableName));
        if ($key === '') {
            return false;
        }

        if (array_key_exists($key, self::$schemaCache['tables'])) {
            return self::$schemaCache['tables'][$key];
        }

        $row = self::fetch(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$tableName]
        );

        $exists = (int)($row['cnt'] ?? 0) > 0;
        self::$schemaCache['tables'][$key] = $exists;
        return $exists;
    }

    public static function columnExists(string $tableName, string $columnName): bool
    {
        $tableKey = strtolower(trim($tableName));
        $columnKey = strtolower(trim($columnName));
        if ($tableKey === '' || $columnKey === '') {
            return false;
        }

        $cacheKey = $tableKey . '.' . $columnKey;
        if (array_key_exists($cacheKey, self::$schemaCache['columns'])) {
            return self::$schemaCache['columns'][$cacheKey];
        }

        $row = self::fetch(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?",
            [$tableName, $columnName]
        );

        $exists = (int)($row['cnt'] ?? 0) > 0;
        self::$schemaCache['columns'][$cacheKey] = $exists;
        return $exists;
    }
}
