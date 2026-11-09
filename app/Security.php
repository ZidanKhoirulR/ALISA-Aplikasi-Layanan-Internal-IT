<?php
// ============================================
// app/Security.php - Security Helpers
// ============================================

class Security
{
    /**
     * Generate visual CAPTCHA (SVG data URI) for login
     */
    public static function generateLoginCaptcha(\Base $f3): array
    {
        $code = self::randomCaptchaCode(5);
        $f3->set('SESSION.login_captcha_hash', hash('sha256', strtoupper($code)));
        $f3->set('SESSION.login_captcha_expires', time() + 300); // 5 minutes
        $f3->set('SESSION.login_captcha_tries', 0);
        $f3->clear('SESSION.login_captcha_last_error');

        return [
            'image' => self::buildCaptchaSvgDataUri($code),
            'hint'  => 'Ketik kode pada gambar (tidak case-sensitive).'
        ];
    }

    public static function validateLoginCaptcha(\Base $f3, ?string $answer): bool
    {
        return true;
    }

    public static function getLoginCaptchaError(\Base $f3): string
    {
        return (string)($f3->get('SESSION.login_captcha_last_error') ?? '');
    }

    private static function randomCaptchaCode($length = 5): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid ambiguous chars like O/0 and I/1
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    private static function buildCaptchaSvgDataUri($code): string
    {
        $w = 220;
        $h = 64;
        $fontSize = 30;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
        $svg .= '<rect width="100%" height="100%" rx="10" fill="#f8fafc" />';

        for ($i = 0; $i < 7; $i++) {
            $x1 = random_int(0, $w);
            $y1 = random_int(0, $h);
            $x2 = random_int(0, $w);
            $y2 = random_int(0, $h);
            $stroke = random_int(180, 220);
            $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgb(' . $stroke . ',' . $stroke . ',' . $stroke . ')" stroke-width="1"/>';
        }

        for ($i = 0; $i < 20; $i++) {
            $cx = random_int(0, $w);
            $cy = random_int(0, $h);
            $r = random_int(1, 2);
            $dot = random_int(150, 210);
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="rgb(' . $dot . ',' . $dot . ',' . $dot . ')"/>';
        }

        $letters = str_split($code);
        $x = 28;
        for ($i = 0; $i < count($letters); $i++) {
            $char = htmlspecialchars($letters[$i], ENT_QUOTES, 'UTF-8');
            $y = random_int(42, 50);
            $rotate = random_int(-18, 18);
            $color = random_int(40, 95);
            $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . $fontSize . '" font-weight="700" fill="rgb(' . $color . ',' . $color . ',' . ($color + 30) . ')" transform="rotate(' . $rotate . ' ' . $x . ' ' . $y . ')">' . $char . '</text>';
            $x += 36;
        }

        $svg .= '</svg>';
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    /**
     * Set common security headers
     */
    public static function setHeaders(\Base $f3): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        // Enable XSS protection in older browsers
        header('X-XSS-Protection: 1; mode=block');
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Basic Content Security Policy (can be refined)
        // Allowing Google Fonts and local assets
        $csp = "default-src 'self' blob:; "
             . "script-src 'self' 'unsafe-inline' blob: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; "
             . "style-src 'self' 'unsafe-inline' blob: https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
             . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
             . "img-src 'self' data:; "
             . "frame-src 'self' blob:; "
             . "connect-src 'self';";
        header("Content-Security-Policy: $csp");
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(\Base $f3): string
    {
        if (!$f3->exists('SESSION.csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $f3->set('SESSION.csrf_token', $token);
        }
        return $f3->get('SESSION.csrf_token');
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(\Base $f3, ?string $token): bool
    {
        if (!$token || !$f3->exists('SESSION.csrf_token')) {
            return false;
        }
        return hash_equals($f3->get('SESSION.csrf_token'), $token);
    }

    /**
     * Reload user from DB (role/status cannot be trusted from stale session).
     */
    public static function getCurrentUser(\Base $f3): ?array
    {
        $session = $f3->get('SESSION.user');
        if (!$session || empty($session['id'])) {
            return null;
        }

        $user = User::findById((int)$session['id']);
        if (!$user) {
            return null;
        }

        unset($user['password']);
        $f3->set('SESSION.user', $user);
        return $user;
    }

    public static function canAccessChat(\Base $f3, int $chatId): bool
    {
        $user = self::getCurrentUser($f3);
        if (!$user) {
            return false;
        }
        return Chat::canUserAccessChat(
            $chatId,
            (int)$user['id'],
            (string)($user['role'] ?? 'user')
        );
    }

    /**
     * Centralized Auth Check
     */
    public static function checkAuth(\Base $f3): void
    {
        if (!$f3->get('SESSION.user')) {
            if ($f3->get('AJAX')) {
                http_response_code(401);
                die(json_encode(['error' => 'Unauthorized']));
            } else {
                $f3->reroute('/login');
                exit;
            }
        }

        if (!self::getCurrentUser($f3)) {
            $f3->clear('SESSION');
            if ($f3->get('AJAX')) {
                http_response_code(401);
                die(json_encode(['error' => 'Unauthorized']));
            }
            $f3->reroute('/login');
            exit;
        }
        
        // Session timeout check (10 minutes = 600 seconds)
        $lastActivity = $f3->get('SESSION.last_activity');
        $timeout = 600; 
        
        if ($lastActivity && (time() - $lastActivity > $timeout)) {
            $f3->clear('SESSION');
            if ($f3->get('AJAX')) {
                http_response_code(401);
                die(json_encode(['error' => 'Session expired']));
            } else {
                $f3->reroute('/login?expired=1');
                exit;
            }
        }
        
        $f3->set('SESSION.last_activity', time());
    }

    /**
     * Rate limiting for login (IP-based)
     */
    public static function throttleLogin(\Base $f3): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $record = DB::fetch("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?", [$ip]);
        
        if ($record) {
            $attempts = (int)$record['attempts'];
            $lastAttempt = strtotime($record['last_attempt']);
            
            // If more than 5 attempts, wait 15 minutes
            if ($attempts >= 5 && (time() - $lastAttempt < 900)) {
                return false;
            }
            
            // Reset if 15 minutes passed since last attempt
            if (time() - $lastAttempt >= 900) {
                self::resetLoginAttempts($f3);
            }
        }
        
        return true;
    }

    public static function incrementLoginAttempts(\Base $f3): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $record = DB::fetch("SELECT id FROM login_attempts WHERE ip_address = ?", [$ip]);
        
        if ($record) {
            DB::run("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ?", [$ip]);
        } else {
            DB::run("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)", [$ip]);
        }
    }

    public static function resetLoginAttempts(\Base $f3): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        DB::run("DELETE FROM login_attempts WHERE ip_address = ?", [$ip]);
    }
}
