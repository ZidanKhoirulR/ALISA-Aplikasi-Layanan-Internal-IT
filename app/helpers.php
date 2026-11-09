<?php
/**
 * app/helpers.php
 * Common helper functions for views and system
 */

/**
 * Simple .env loader
 */
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                // putenv is disabled on the server
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

class Env {
    public static function get($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) return $default;
        switch (strtolower($value)) {
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
        }
        return $value;
    }
}

class Logger {
    public static function log($action, $details = null) {
        $f3 = \Base::instance();
        $user = $f3->get('SESSION.user');
        $userId = $user ? (int)$user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        if (is_array($details)) {
            $details = json_encode($details);
        }
        
        DB::run(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$userId, $action, $details, $ip]
        );
    }
}

if (!function_exists('avatarColor')) {
    function avatarColor($id) {
        if (empty($id)) return '#64748b'; // Fallback gray
        $num = is_numeric($id) ? (int)$id : abs(crc32((string)$id));
        $colors = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
        return $colors[$num % count($colors)];
    }
}

if (!function_exists('techStatusDot')) {
    function techStatusDot($s) {
        switch($s) {
            case 'available': return '<span class="tech-dot dot-available" title="Available"></span>';
            case 'busy':      return '<span class="tech-dot dot-busy" title="Busy"></span>';
            default:          return '<span class="tech-dot dot-offline" title="Offline"></span>';
        }
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge($s, $isResponded = false) {
        switch($s) {
            case 'waiting':
                if ($isResponded) {
                    return '<span class="chat-status-badge badge-responded"><i class="bi bi-reply-fill"></i> Sudah Direspons</span>';
                }
                return '<span class="chat-status-badge badge-waiting"><i class="bi bi-clock"></i> Waiting</span>';
            case 'in_progress': return '<span class="chat-status-badge badge-inprogress"><i class="bi bi-arrow-repeat"></i> Permintaan ditangani Tim IT</span>';
            case 'done':        return '<span class="chat-status-badge badge-done"><i class="bi bi-check-circle"></i> Done</span>';
            default:          return '';
        }
    }
}

if (!function_exists('relativeTime')) {
    function relativeTime($dt) {
        if (!$dt) return '';
        $diff = time() - strtotime($dt);
        if ($diff < 60)    return 'Baru saja';
        if ($diff < 3600)  return floor($diff/60) . ' mnt lalu';
        if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
        return date('d M', strtotime($dt));
    }
}

if (!function_exists('formatZoomRange')) {
    function formatZoomRange($start, $end) {
        if (!$start || !$end) return '';
        $s = strtotime($start);
        $e = strtotime($end);

        $startDate = date('Y-m-d', $s);
        $endDate = date('Y-m-d', $e);

        $startDay = date('d M Y', $s);
        $endDay = date('d M Y', $e);
        $startTime = date('H.i', $s);
        $endTime = date('H.i', $e);

        if ($startDate === $endDate) {
            return $startDay . ', ' . $startTime . '-' . $endTime . ' WIB';
        }

        return $startDay . ' ' . $startTime . ' WIB - ' . $endDay . ' ' . $endTime . ' WIB';
    }
}

if (!function_exists('zoomRequestBadge')) {
    function zoomRequestBadge($type) {
        switch($type) {
            case 'link_tech':
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 9px; font-weight: 800;">LINK + TEKNIS DARI IT</span>';
            case 'link_only':
                return '<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 9px; font-weight: 800;">LINK DARI IT (HANYA LINK)</span>';
            case 'own_link':
                return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 9px; font-weight: 800;">RUANGAN</span>';
            default:
                // Legacy support
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 9px; font-weight: 800;">LINK + TEKNIS DARI IT</span>';
        }
    }
}


if (!function_exists('formatDuration')) {
    function formatDuration($seconds) {
        $seconds = (int)$seconds; // Prevent float-to-int precision error (PHP 8.1+)
        if ($seconds <= 0) return '0 mnt';
        
        $hours   = (int)floor($seconds / 3600);
        $minutes = (int)floor(($seconds % 3600) / 60);
        
        $res = [];
        if ($hours > 0) $res[] = $hours . ' jam';
        if ($minutes > 0 || empty($res)) $res[] = $minutes . ' mnt';
        
        return implode(' ', $res);
    }
}

