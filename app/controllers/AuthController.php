<?php
// ============================================
// app/controllers/AuthController.php
// ============================================

class AuthController
{
    public function login(\Base $f3): void
    {
        if ($f3->get('SESSION.user')) {
            $f3->reroute('/');
        }

        $error = null;
        $captchaData = null;

        if ($f3->get('VERB') === 'POST') {
            $captchaAnswer = $f3->get('POST.captcha_answer') ?? '';
            if (!Security::validateLoginCaptcha($f3, $captchaAnswer)) {
                $captchaError = Security::getLoginCaptchaError($f3);
                if ($captchaError === 'expired') {
                    $error = 'CAPTCHA sudah kedaluwarsa. Silakan muat ulang CAPTCHA.';
                } else if ($captchaError === 'max_attempts') {
                    $error = 'Terlalu banyak percobaan CAPTCHA. Silakan muat ulang CAPTCHA.';
                } else {
                    $error = 'Kode CAPTCHA tidak valid. Silakan coba lagi.';
                }
            } else if (!Security::throttleLogin($f3)) {
                $error = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
            } else {
                $username = trim($f3->get('POST.username') ?? '');
                $password = trim($f3->get('POST.password') ?? '');
                
                $loginSuccess = false;
                $user = null;

                // 1. Try sil2020 login
                try {
                    $silDb = DB::getSilInstance();
                    $stmt = $silDb->prepare('SELECT * FROM tb_arsipuser WHERE login = ?');
                    $stmt->execute([$username]);
                    $silUser = $stmt->fetch();

                    if ($silUser && $silUser['password'] === md5($password)) {
                        User::syncFromSil($silUser);
                        $user = User::findByUsername($username); // username in local DB = login from sil2020
                        $loginSuccess = true;
                    }
                } catch (Exception $e) {
                    // Ignore sil DB errors if any, fallback to local
                }

                // 2. Fallback to local users if sil2020 failed
                if (!$loginSuccess) {
                    // We check by username first, if not found, check by email (for local users like admin@alisa.local)
                    $user = User::findByUsername($username);
                    if (!$user && filter_var($username, FILTER_VALIDATE_EMAIL)) {
                        $user = User::findByEmail($username);
                    }

                    if ($user) {
                        // Check if it's already a modern hash
                        if (password_verify($password, $user['password'])) {
                            $loginSuccess = true;
                        } 
                        // Fallback: Check legacy MD5 then migrate to modern hash
                        else if ($user['password'] === md5($password)) {
                            $loginSuccess = true;
                            // Graceful migration to password_hash
                            User::updatePassword((int)$user['id'], $password);
                        }
                    }
                }

                if ($loginSuccess && $user) {
                    // Reset login attempts on success
                    Security::resetLoginAttempts($f3);

                    // Update online status
                    User::updateStatus((int)$user['id'],
                        in_array($user['role'], ['teknisi','admin']) ? 'available' : 'available'
                    );
                    
                    // Security: Regenerate session ID to prevent Session Fixation
                    session_regenerate_id(true);
                    
                    unset($user['password']);
                    $f3->set('SESSION.user', $user);
                    $f3->set('SESSION.last_activity', time());
                    $f3->set('SESSION.login_date', date('Y-m-d'));
                    
                    // Initialize CSRF token
                    Security::generateCsrfToken($f3);

                    $f3->reroute('/');
                    exit;
                } else {
                    Security::incrementLoginAttempts($f3);
                    $error = 'Username/Email atau password salah.';
                }
            }
            // Rotate CAPTCHA after every POST attempt to prevent replay.
            $captchaData = Security::generateLoginCaptcha($f3);
        } else {
            // Do not generate captcha on initial GET request
            $captchaData = ['image' => '', 'hint' => ''];
        }

        $f3->set('error', $error);
        $f3->set('captchaImage', $captchaData['image']);
        $f3->set('captchaHint', $captchaData['hint']);
        $f3->set('PAGE_TITLE', 'Login — ALISA');
        include __DIR__ . '/../views/login.php';
    }

    public function logout(\Base $f3): void
    {
        $user = $f3->get('SESSION.user');
        if ($user) {
            User::updateStatus((int)$user['id'], 'offline');
        }
        $f3->clear('SESSION');
        $f3->reroute('/login');
    }

    public function getCaptcha(\Base $f3): void
    {
        $captchaData = Security::generateLoginCaptcha($f3);
        header('Content-Type: application/json');
        echo json_encode([
            'image' => $captchaData['image'],
            'hint'  => $captchaData['hint']
        ]);
        exit;
    }
}
