<?php
/**
 * Writable directories for uploads, icons, and PHP sessions.
 */
class Storage
{
    public const CHAT_FILE_PATTERN = '/^file_\d+_[a-f0-9]+\.(pdf|xls|xlsx|doc|docx|jpg|jpeg|png)$/i';

    public static function projectRoot(): string
    {
        return dirname(__DIR__);
    }

    public static function uploadsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public static function storageUploadsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public static function tmpUploadsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public static function categoryIconsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'category-icons' . DIRECTORY_SEPARATOR;
    }

    public static function groupIconsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'group-icons' . DIRECTORY_SEPARATOR;
    }

    public static function sessionsDir(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'sessions' . DIRECTORY_SEPARATOR;
    }

    /**
     * Urutan folder upload chat (fallback jika public/uploads tidak writable).
     *
     * @return list<array{path:string, url_prefix:string}>
     */
    public static function chatUploadCandidates(): array
    {
        $candidates = [];

        $custom = trim((string)Env::get('UPLOAD_PATH', ''));
        if ($custom !== '') {
            $abs = self::normalizeAbsolutePath($custom);
            $candidates[] = [
                'path'        => $abs,
                'url_prefix'  => rtrim((string)Env::get('UPLOAD_URL_PREFIX', 'files/'), '/') . '/',
            ];
        }

        $candidates[] = ['path' => self::uploadsDir(), 'url_prefix' => 'public/uploads/'];
        $candidates[] = ['path' => self::storageUploadsDir(), 'url_prefix' => 'files/'];
        $candidates[] = ['path' => self::tmpUploadsDir(), 'url_prefix' => 'files/'];

        return $candidates;
    }

    /**
     * @return array{ok:bool, path?:string, url_prefix?:string, error?:string, tried?:string[]}
     */
    public static function resolveChatUploadDir(): array
    {
        $tried = [];
        foreach (self::chatUploadCandidates() as $candidate) {
            $check = self::ensureWritable($candidate['path']);
            $tried[] = $check['path'] . ($check['ok'] ? ' (OK)' : ' (' . ($check['error'] ?? 'fail') . ')');
            if ($check['ok']) {
                return [
                    'ok'          => true,
                    'path'        => $check['path'],
                    'url_prefix'  => $candidate['url_prefix'],
                ];
            }
        }

        return [
            'ok'    => false,
            'error' => 'not_writable',
            'tried' => $tried,
        ];
    }

    /** @return list<string> */
    public static function allChatUploadDirs(): array
    {
        $dirs = [];
        foreach (self::chatUploadCandidates() as $c) {
            $dirs[] = rtrim($c['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        return array_values(array_unique($dirs));
    }

    public static function isValidChatFilename(string $filename): bool
    {
        return (bool)preg_match(self::CHAT_FILE_PATTERN, $filename);
    }

    public static function findChatUploadFile(string $filename): ?string
    {
        if (!self::isValidChatFilename($filename)) {
            return null;
        }
        foreach (self::allChatUploadDirs() as $dir) {
            $path = $dir . $filename;
            if (is_file($path)) {
                return $path;
            }
        }
        // Legacy: file di public/uploads meski sekarang pakai folder lain
        $legacy = self::uploadsDir() . $filename;
        return is_file($legacy) ? $legacy : null;
    }

    /**
     * @return array{ok:bool, path:string, error?:string}
     */
    public static function ensureWritable(string $dir): array
    {
        $dir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                return ['ok' => false, 'path' => $dir, 'error' => 'cannot_create'];
            }
        }

        // Jalankan tes tulis file sungguhan untuk mendeteksi writable secara akurat di Windows (Laragon) & Linux
        $tempFile = $dir . 'tmp_write_test_' . uniqid() . '.txt';
        $isWritable = false;
        if (@file_put_contents($tempFile, 'test') !== false) {
            $isWritable = true;
            @unlink($tempFile);
        }

        if (!$isWritable) {
            @chmod($dir, 0777);
            if (@file_put_contents($tempFile, 'test') !== false) {
                $isWritable = true;
                @unlink($tempFile);
            }
        }

        if (!$isWritable) {
            return ['ok' => false, 'path' => $dir, 'error' => 'not_writable'];
        }

        return ['ok' => true, 'path' => $dir];
    }

    public static function bootstrapWritableDirs(): void
    {
        foreach ([
            self::uploadsDir(),
            self::storageUploadsDir(),
            self::tmpUploadsDir(),
            self::categoryIconsDir(),
            self::groupIconsDir(),
            self::sessionsDir(),
        ] as $dir) {
            self::ensureWritable($dir);
        }
    }

    public static function writableErrorMessage(string $error, ?array $tried = null): string
    {
        if ($error === 'cannot_create') {
            return 'Folder upload tidak bisa dibuat di server. Hubungi admin IT.';
        }
        $msg = 'Folder upload tidak bisa ditulis oleh server.';
        if ($tried) {
            error_log('[storage] upload dirs tried: ' . implode('; ', $tried));
        }
        return $msg . ' Hubungi admin untuk menjalankan scripts/fix-permissions.sh.';
    }

    private static function normalizeAbsolutePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path));
        if ($path === '' || $path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        return rtrim(self::projectRoot() . DIRECTORY_SEPARATOR . $path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
