<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - ALISA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      background: #070b14;
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      overflow: hidden;
    }

    /* ─── LEFT BRAND PANEL ────────────────────────── */
    .login-brand-side {
      flex: 1.2;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 64px;
      background: linear-gradient(145deg, #0d1b3e 0%, #0a1628 40%, #050d1a 100%);
    }

    /* Mesh gradient orbs */
    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.6;
      pointer-events: none;
    }
    .orb-1 {
      width: 450px; height: 450px;
      background: radial-gradient(circle, #6366f1 0%, #4338ca 50%, transparent 70%);
      top: -150px; left: -100px;
      animation: orb-drift 18s ease-in-out infinite alternate;
    }
    .orb-2 {
      width: 350px; height: 350px;
      background: radial-gradient(circle, #06b6d4 0%, #0e7490 50%, transparent 70%);
      bottom: -100px; right: -80px;
      animation: orb-drift 22s ease-in-out infinite alternate-reverse;
    }
    .orb-3 {
      width: 200px; height: 200px;
      background: radial-gradient(circle, #a78bfa 0%, transparent 70%);
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      animation: orb-drift 15s ease-in-out infinite alternate;
    }
    @keyframes orb-drift {
      0% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(20px, -30px) scale(1.08); }
      100% { transform: translate(-15px, 15px) scale(0.95); }
    }

    /* Grid overlay */
    .grid-overlay {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
      background-size: 48px 48px;
      pointer-events: none;
    }

    /* Shining top line */
    .brand-shine {
      position: absolute;
      top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, #6366f1, #06b6d4, transparent);
    }

    .brand-content-inner {
      position: relative;
      z-index: 2;
    }

    .brand-logo-wrap {
      display: flex;
      margin-bottom: -70px;
    }
    .brand-logo-icon {
      width: 280px; height: 280px;
      background: none;
      border-radius: 0;
      display: flex; align-items: center; justify-content: flex-start;
      font-size: 44px; color: white;
      box-shadow: none;
      border: none;
    }

    .brand-headline {
      font-size: 42px; font-weight: 800;
      color: white; line-height: 1.15;
      letter-spacing: -1.5px;
      margin-bottom: 20px;
    }
    .brand-headline span {
      background: linear-gradient(90deg, #818cf8, #22d3ee);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .brand-desc {
      font-size: 15px; color: rgba(148,163,184,0.85);
      line-height: 1.7; max-width: 380px;
      font-weight: 400;
    }

    /* ─── RIGHT FORM PANEL ────────────────────────── */
    .login-form-side {
      width: 480px;
      flex-shrink: 0;
      background: #ffffff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 56px 52px;
      position: relative;
      overflow-y: auto;
    }

    .form-header { margin-bottom: 36px; }
    .form-header .welcome { font-size: 13px; font-weight: 700; color: #6366f1; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 8px; }
    .form-header h2 { font-size: 30px; font-weight: 800; color: #0f172a; letter-spacing: -0.8px; line-height: 1.2; }

    /* Divider */
    .form-divider {
      height: 1px;
      background: linear-gradient(90deg, #e2e8f0, #f1f5f9);
      margin: 0 -52px 32px;
    }

    /* Error alert */
    .login-error-alert {
      display: flex; align-items: flex-start; gap: 12px;
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: 12px; padding: 14px 16px;
      margin-bottom: 24px;
      animation: shake .4s ease;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      75% { transform: translateX(4px); }
    }
    .login-error-alert .alert-icon { color: #ef4444; font-size: 18px; margin-top: 1px; flex-shrink: 0; }
    .login-error-alert .alert-text { font-size: 13.5px; color: #991b1b; font-weight: 500; line-height: 1.4; }

    /* Field groups */
    .field-group { margin-bottom: 20px; }
    .field-label {
      display: block;
      font-size: 12.5px; font-weight: 700;
      color: #374151; letter-spacing: 0.03em;
      margin-bottom: 8px;
    }
    .field-wrap {
      position: relative;
    }
    .field-icon {
      position: absolute;
      left: 14px; top: 50%;
      transform: translateY(-50%);
      color: #94a3b8; font-size: 16px;
      pointer-events: none;
      z-index: 2;
    }
    .login-input {
      width: 100%;
      padding: 12px 14px 12px 42px;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      font-size: 14.5px;
      color: #0f172a;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 500;
      transition: all .25s;
      outline: none;
    }
    .login-input:focus {
      background: #ffffff;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
    }
    .login-input:focus ~ .field-icon {
      color: #6366f1;
    }
    .login-input::placeholder { color: #cbd5e1; font-weight: 400; }

    /* Password field with toggle */
    .pass-toggle {
      position: absolute;
      right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: #94a3b8; cursor: pointer;
      font-size: 16px; padding: 4px;
      transition: color .2s;
      z-index: 3;
    }
    .pass-toggle:hover { color: #6366f1; }

    /* CAPTCHA section */
    .captcha-section { margin-bottom: 24px; }
    .captcha-label {
      display: block;
      font-size: 12.5px; font-weight: 700;
      color: #374151; letter-spacing: 0.03em;
      margin-bottom: 8px;
    }
    .btn-generate-captcha {
      width: 100%;
      padding: 12px;
      background: rgba(99,102,241,0.05);
      border: 1.5px dashed #cbd5e1;
      border-radius: 12px;
      color: #64748b;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px; font-weight: 600;
      cursor: pointer;
      transition: all .25s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-generate-captcha:hover {
      background: rgba(99,102,241,0.1);
      border-color: #6366f1;
      color: #6366f1;
    }
    .btn-generate-captcha i { font-size: 16px; }
    .captcha-display {
      display: flex; align-items: center; gap: 10px;
      padding: 10px;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      margin-bottom: 10px;
    }
    .captcha-img-box {
      flex: 1; height: 44px; border-radius: 8px;
      overflow: hidden; background: white;
      border: 1px solid #e2e8f0;
    }
    .captcha-img-box img { width: 100%; height: 100%; object-fit: contain; }
    .captcha-reload-btn {
      width: 36px; height: 36px; border-radius: 9px;
      background: white; border: 1.5px solid #e2e8f0;
      color: #64748b; display: flex; align-items: center;
      justify-content: center; cursor: pointer; font-size: 15px;
      transition: all .25s; flex-shrink: 0; text-decoration: none;
    }
    .captcha-reload-btn:hover {
      background: #6366f1; border-color: #6366f1;
      color: white; transform: rotate(20deg);
    }
    .captcha-hint {
      font-size: 11.5px; color: #94a3b8; font-weight: 500;
      display: flex; align-items: center; gap: 5px;
      margin-bottom: 10px;
    }

    /* Submit button */
    .btn-submit-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
      border: none;
      border-radius: 14px;
      color: white;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 15px; font-weight: 700;
      letter-spacing: 0.02em;
      cursor: pointer;
      transition: all .3s;
      box-shadow: 0 8px 24px rgba(99,102,241,0.35), 0 2px 6px rgba(99,102,241,0.2);
      display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-top: 8px;
    }
    .btn-submit-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(99,102,241,0.45), 0 4px 10px rgba(99,102,241,0.3);
      filter: brightness(1.06);
    }
    .btn-submit-login:active { transform: translateY(0); }
    .btn-submit-login i { font-size: 18px; }

    .form-footer {
      position: absolute; bottom: 24px; left: 0; right: 0;
      text-align: center;
      font-size: 12px; color: #cbd5e1; font-weight: 400;
    }

    /* ─── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 900px) {
      .login-brand-side { display: none; }
      .login-form-side {
        width: 100%;
        padding: 40px 28px;
        background: #0f172a;
      }
      body { background: #0f172a; }
      .form-header .welcome { color: #818cf8; }
      .form-header h2 { color: white; }
      .form-header p  { color: #94a3b8; }
      .field-label, .captcha-label { color: #cbd5e1; }
      .login-input {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: white;
      }
      .login-input:focus { background: rgba(255,255,255,0.08); }
      .login-input::placeholder { color: #475569; }
      .captcha-display { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
      .captcha-reload-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
      .btn-generate-captcha { border-color: rgba(255,255,255,0.15); color: #94a3b8; }
      .btn-generate-captcha:hover { background: rgba(255,255,255,0.05); border-color: #818cf8; color: #818cf8; }
      .form-divider { display: none; }
      .form-footer { color: #64748b; }
    }

    /* Entrance animation */
    .login-form-side { animation: panel-in .6s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes panel-in {
      from { opacity: 0; transform: translateX(30px); }
      to { opacity: 1; transform: translateX(0); }
    }
  </style>
</head>
<body>

  <div class="login-brand-side d-none d-lg-flex">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>
    <div class="brand-shine"></div>

    <div class="brand-content-inner">
      <div class="brand-logo-wrap">
        <div class="brand-logo-icon">
          <?php
          $logoPath = '';
          if (is_dir('public')) {
              $files = scandir('public');
              if ($files !== false) {
                  foreach ($files as $file) {
                      $lowerFile = strtolower($file);
                      if (strpos($lowerFile, 'logo.') === 0) {
                          $logoPath = "public/$file";
                          break;
                      }
                  }
              }
          }
          if ($logoPath): ?>
            <img src="<?= $logoPath ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; object-position: left;" />
          <?php else: ?>
            <i class="bi bi-headset"></i>
          <?php endif; ?>
        </div>
      </div>

      <h2 class="brand-headline">Satu Platform<br>untuk <span>Semua Layanan IT</span></h2>
    </div>
  </div>

  <div class="login-form-side">

    <div class="form-header">
      <div class="welcome">Selamat Datang</div>
      <h2>Login ALISA</h2>
    </div>

    <div class="form-divider"></div>

    <?php if ($f3->get('error')): ?>
      <div class="login-error-alert">
        <i class="bi bi-exclamation-circle-fill alert-icon"></i>
        <div class="alert-text"><?= htmlspecialchars($f3->get('error')) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="login" novalidate autocomplete="off">

      <div class="field-group">
        <label class="field-label" for="usernameInput">Username</label>
        <div class="field-wrap">
          <span class="field-icon"><i class="bi bi-person-fill"></i></span>
          <input type="text" id="usernameInput" name="username" class="login-input"
                 placeholder="Masukkan username Anda" required
                 value="<?= htmlspecialchars($f3->get('POST.username') ?? '') ?>"/>
        </div>
      </div>

      <div class="field-group">
        <label class="field-label" for="passwordInput">Password</label>
        <div class="field-wrap">
          <span class="field-icon"><i class="bi bi-lock-fill"></i></span>
          <input type="password" id="passwordInput" name="password" class="login-input"
                 placeholder="Masukkan password Anda" required/>
          <button type="button" class="pass-toggle" id="togglePass" title="Tampilkan/Sembunyikan">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="captcha-section">
        <label class="captcha-label">Verifikasi Keamanan</label>
        
        <?php if (empty($f3->get('captchaImage'))): ?>
          <div id="captchaInitContainer" style="margin-bottom: 10px;">
            <button type="button" class="btn-generate-captcha" id="btnGenerateCaptcha" onclick="loadCaptcha()">
              <i class="bi bi-shield-lock"></i> Klik untuk Verifikasi Keamanan
            </button>
          </div>
          
          <div id="captchaActualContainer" style="display: none;">
            <div class="captcha-display">
              <div class="captcha-img-box">
                <img src="" id="captchaImageEl" alt="CAPTCHA"/>
              </div>
              <button type="button" class="captcha-reload-btn" title="Muat ulang CAPTCHA" onclick="loadCaptcha()">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div class="captcha-hint" id="captchaHintEl">
              <i class="bi bi-info-circle"></i> Memuat...
            </div>
            <div class="field-wrap">
              <span class="field-icon"><i class="bi bi-shield-check"></i></span>
              <input type="text" name="captcha_answer" id="captchaInput" class="login-input"
                     placeholder="Masukkan kode captcha" autocomplete="off"
                     maxlength="8" style="text-transform:uppercase;"/>
            </div>
          </div>
        <?php else: ?>
          <div id="captchaActualContainer">
            <div class="captcha-display">
              <div class="captcha-img-box">
                <img src="<?= htmlspecialchars($f3->get('captchaImage') ?? '') ?>" id="captchaImageEl" alt="CAPTCHA"/>
              </div>
              <button type="button" class="captcha-reload-btn" title="Muat ulang CAPTCHA" onclick="loadCaptcha()">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div class="captcha-hint" id="captchaHintEl">
              <i class="bi bi-info-circle"></i>
              <?= htmlspecialchars($f3->get('captchaHint') ?? 'Ketik kode pada gambar (tidak case-sensitive)') ?>
            </div>
            <div class="field-wrap">
              <span class="field-icon"><i class="bi bi-shield-check"></i></span>
              <input type="text" name="captcha_answer" id="captchaInput" class="login-input"
                     placeholder="Masukkan kode captcha" required autocomplete="off"
                     maxlength="8" style="text-transform:uppercase;"/>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-submit-login" id="btnLogin">
        <i class="bi bi-box-arrow-in-right"></i>
        Masuk ke Sistem
      </button>
    </form>

    <div class="form-footer">
      @<?= date('Y') ?> BBSPJI Selulosa
    </div>
  </div>

  <script>
    // Password toggle
    document.getElementById('togglePass').addEventListener('click', function () {
      const inp  = document.getElementById('passwordInput');
      const icon = document.getElementById('eyeIcon');
      const isPass = inp.type === 'password';
      inp.type = isPass ? 'text' : 'password';
      icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Button loading state
    document.querySelector('form').addEventListener('submit', function (e) {
      const captchaInput = document.getElementById('captchaInput');
      // If captcha is not required yet, it means user hasn't generated it
      if (document.getElementById('captchaInitContainer') && document.getElementById('captchaInitContainer').style.display !== 'none') {
        e.preventDefault();
        alert('Silakan klik "Verifikasi Keamanan" terlebih dahulu.');
        return;
      }
      const btn = document.getElementById('btnLogin');
      btn.innerHTML = '<span style="width:18px;height:18px;border:2.5px solid rgba(255,255,255,0.4);border-top-color:white;border-radius:50%;display:inline-block;animation:spin .7s linear infinite;"></span> Memproses...';
      btn.style.opacity = '0.85';
      btn.style.cursor = 'not-allowed';
    });

    // Load CAPTCHA
    function loadCaptcha() {
      const btnGen = document.getElementById('btnGenerateCaptcha');
      if (btnGen) {
        btnGen.innerHTML = '<i class="bi bi-hourglass-split"></i> Memuat...';
        btnGen.disabled = true;
      }

      fetch('api/captcha')
        .then(response => response.json())
        .then(data => {
          document.getElementById('captchaImageEl').src = data.image;
          document.getElementById('captchaHintEl').innerHTML = '<i class="bi bi-info-circle"></i> ' + data.hint;
          
          const initContainer = document.getElementById('captchaInitContainer');
          if (initContainer) initContainer.style.display = 'none';
          
          document.getElementById('captchaActualContainer').style.display = 'block';
          document.getElementById('captchaInput').required = true;
          document.getElementById('captchaInput').focus();
        })
        .catch(error => {
          console.error('Error loading CAPTCHA:', error);
          if (btnGen) {
            btnGen.innerHTML = '<i class="bi bi-shield-lock"></i> Gagal memuat. Coba lagi';
            btnGen.disabled = false;
          }
        });
    }
  </script>
  <style>
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</body>
</html>