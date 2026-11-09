<?php
// ============================================
// app/controllers/ChatController.php
// ============================================

class ChatController
{
    private function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // POST /api/chat/create
    public function create(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user  = $f3->get('SESSION.user');
        $title = trim($f3->get('POST.title') ?? '');
        $catCode = trim($f3->get('POST.category_code') ?? '');
        $catName = trim($f3->get('POST.category') ?? 'Umum');

        if ($catCode) {
            $catRecord = Category::findByCode($catCode);
            if ($catRecord) {
                $catName = $catRecord['name'];
            }
        }
        
        $cat = $catName;

        // New fields for structured form summary
        $reqName   = trim($f3->get('POST.name') ?? '');
        $reqRoom   = trim($f3->get('POST.room') ?? '');
        $reqDetail = trim($f3->get('POST.detail') ?? '');
        $groupId   = $f3->get('POST.group_id') ? (int)$f3->get('POST.group_id') : null;

        if ($groupId) {
            $groupChat = Chat::findById($groupId);
            if (!$groupChat || ($groupChat['type'] ?? '') !== 'group') {
                $this->json(['error' => 'Grup tidak valid.'], 422);
                return;
            }
            if (!$this->canAccessChat($groupId, $user)) {
                $this->json(['error' => 'Anda bukan anggota grup ini.'], 403);
                return;
            }
        }

        if (empty($title)) {
            $this->json(['error' => 'Judul permintaan tidak boleh kosong.'], 422);
            return;
        }

        $extra = json_decode($f3->get('POST.extra') ?? '{}', true);
        if (!is_array($extra)) {
            $extra = [];
        }

        $fieldLabelMap = [];
        if (!empty($catCode)) {
            foreach (CategoryField::getByCategoryCode($catCode) as $fieldItem) {
                $k = strtolower(trim((string)($fieldItem['field_key'] ?? '')));
                if ($k !== '') {
                    $fieldLabelMap[$k] = (string)($fieldItem['field_label'] ?? $k);
                }
            }
        }

        // Extract Ruangan from extra if provided, to map to DB's room column
        if (empty($reqRoom) && !empty($extra)) {
            foreach ($extra as $key => $val) {
                if (strtolower((string)$key) === 'ruangan') {
                    $reqRoom = $val;
                    unset($extra[$key]);
                    break;
                }
            }
        }

        $chatId = Chat::create((int)$user['id'], $title, $cat, $reqRoom, $groupId);

        // Send auto-summary if structured fields are provided (skip for Zoom Meeting as it has its own specialized card)
        if ($catCode !== 'zoom_meeting' && strtolower($cat) !== 'zoom meeting' && ($reqName || $reqRoom || $reqDetail || !empty($extra))) {
            $displayName = $reqName ?: $user['name'];
            $displayName = preg_replace('/\s*\(@[^)]+\)/', '', $displayName);
            
            $summary = "ℹ Permintaan Layanan Baru:\n"
                     . "👤 Nama: " . $displayName;
            
            if ($reqRoom) {
                $summary .= "\n🏢 Ruangan: " . $reqRoom;
            }
            
            // Add Category-specific dynamic fields to summary
            if (!empty($extra) && is_array($extra)) {
                foreach ($extra as $key => $val) {
                    if (!empty($val)) {
                        // Attempt to deduce icon based on key/label for styling (similar to frontend rendering)
                        $icon = '🔹';
                        $kl = strtolower((string)$key);
                        if (strpos($kl, 'tipe') !== false) $icon = '🌐';
                        elseif (strpos($kl, 'titik') !== false || strpos($kl, 'lokasi') !== false || strpos($kl, 'ruang') !== false) $icon = '📍';
                        elseif (strpos($kl, 'perangkat') !== false) $icon = '💻';
                        elseif (strpos($kl, 'merk') !== false || strpos($kl, 'brand') !== false) $icon = '🏷️';
                        elseif (strpos($kl, 'asset') !== false) $icon = '🔢';
                        elseif (strpos($kl, 'printer') !== false) $icon = '🖨';
                        elseif (strpos($kl, 'kendala') !== false || strpos($kl, 'issue') !== false || strpos($kl, 'butuh') !== false) $icon = '⚠️';
                        elseif (strpos($kl, 'software') !== false || strpos($kl, 'aplikasi') !== false) $icon = '💿';
                        elseif (strpos($kl, 'modul') !== false || strpos($kl, 'fitur') !== false) $icon = '🧩';
                        
                        // Capitalize or format key nicely (e.g. from internet_type or "Tipe Kendala")
                        $displayKey = $fieldLabelMap[$kl] ?? ucwords(str_replace('_', ' ', (string)$key));
                        $summary .= "\n{$icon} {$displayKey}: " . $val;
                    }
                }
            }

            if ($reqDetail) {
                $summary .= "\n📝 Detail: " . $reqDetail;
            }
            
            Message::create($chatId, (int)$user['id'], $summary);
        }

        $chat   = Chat::findById($chatId);
        if ($chat) {
            $chat['user_avatar_url'] = User::getAvatarUrl($chat['user_username'], $chat['user_name'], (int)($chat['user_sil_id'] ?? 0));
            $chat['teknisi_avatar_url'] = User::getAvatarUrl($chat['teknisi_username'], $chat['teknisi_name'], (int)($chat['teknisi_sil_id'] ?? 0));
            
            // If linked to a group, post a message in the group chat
            if ($groupId) {
                $displayName = $reqName ?: $user['name'];
                $displayName = preg_replace('/\s*\(@[^)]+\)/', '', $displayName);
                
                $groupMsg = "[SYSTEM_CARD] Permintaan Layanan Baru:\n"
                          . "👤 Nama: " . $displayName;
                
                if ($reqRoom) {
                    $groupMsg .= "\n🏢 Ruangan: " . $reqRoom;
                }

                if (!empty($extra) && is_array($extra)) {
                    foreach ($extra as $key => $val) {
                        if (!empty($val)) {
                            $icon = '🔹';
                            $kl = strtolower((string)$key);
                            if (strpos($kl, 'tipe') !== false) $icon = '🌐';
                            elseif (strpos($kl, 'titik') !== false || strpos($kl, 'lokasi') !== false || strpos($kl, 'ruang') !== false) $icon = '📍';
                            elseif (strpos($kl, 'perangkat') !== false) $icon = '💻';
                            elseif (strpos($kl, 'merk') !== false || strpos($kl, 'brand') !== false) $icon = '🏷️';
                            elseif (strpos($kl, 'asset') !== false) $icon = '🔢';
                            elseif (strpos($kl, 'printer') !== false) $icon = '🖨';
                            elseif (strpos($kl, 'kendala') !== false || strpos($kl, 'issue') !== false || strpos($kl, 'butuh') !== false) $icon = '⚠️';
                            elseif (strpos($kl, 'software') !== false || strpos($kl, 'aplikasi') !== false) $icon = '💿';
                            elseif (strpos($kl, 'modul') !== false || strpos($kl, 'fitur') !== false) $icon = '🧩';
                            
                            $displayKey = $fieldLabelMap[$kl] ?? ucwords(str_replace('_', ' ', (string)$key));
                            $groupMsg .= "\n{$icon} {$displayKey}: " . $val;
                        }
                    }
                }

                $groupMsg .= "\n📝 Detail: " . $title;
                Message::create($groupId, (int)$user['id'], $groupMsg);
            }
        }
        $this->json(['success' => true, 'chat_id' => $chatId, 'chat' => $chat]);
    }

    // GET /api/chats?filter=all
    public function list(\Base $f3): void
    {
        Security::checkAuth($f3);
        try {
            $user   = $f3->get('SESSION.user');
            $filter = $f3->get('GET.filter') ?? 'all';
            $isPaged = (int)($f3->get('GET.paged') ?? 0) === 1;
            if ($isPaged) {
                $q = trim((string)($f3->get('GET.q') ?? ''));
                $startDate = trim((string)($f3->get('GET.start_date') ?? ''));
                $endDate = trim((string)($f3->get('GET.end_date') ?? ''));
                $page = (int)($f3->get('GET.page') ?? 1);
                $limit = (int)($f3->get('GET.limit') ?? 20);
                $paged = Chat::listForUserPaged((int)$user['id'], (string)$user['role'], (string)$filter, $q, $startDate ?: null, $endDate ?: null, $page, $limit);
                $chats = $paged['items'];
            } else {
                $chats  = Chat::listForUser((int)$user['id'], $user['role'], $filter);
            }
            foreach ($chats as &$c) {
                $c['user_avatar_url'] = User::getAvatarUrl($c['user_username'], $c['user_name'], (int)($c['user_sil_id'] ?? 0));
                if (isset($c['teknisi_username'])) {
                    $c['teknisi_avatar_url'] = User::getAvatarUrl($c['teknisi_username'], $c['teknisi_name'], (int)($c['teknisi_sil_id'] ?? 0));
                }
            }
            if ($isPaged) {
                $this->json([
                    'items' => $chats,
                    'meta' => $paged['meta']
                ]);
                return;
            }
            $this->json($chats);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    // GET /api/chats/notifications — tiket aktif + percakapan grup (admin)
    public function notifications(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if (($user['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        try {
            $items = Chat::listAllNotifications();
            foreach ($items as &$c) {
                $c['user_avatar_url'] = User::getAvatarUrl(
                    $c['user_username'] ?? '',
                    $c['user_name'] ?? '',
                    (int)($c['user_sil_id'] ?? 0)
                );
                if (isset($c['teknisi_username'])) {
                    $c['teknisi_avatar_url'] = User::getAvatarUrl(
                        $c['teknisi_username'],
                        $c['teknisi_name'] ?? '',
                        (int)($c['teknisi_sil_id'] ?? 0)
                    );
                }
                if (($c['notif_type'] ?? '') === 'group') {
                    $c['is_group_chat'] = true;
                }
            }
            unset($c);
            $this->json($items);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/chat/@id
    public function info(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        $chatId = (int)$params['id'];
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        $chat = Chat::findById($chatId);
        if (!$chat) {
            $this->json(['error' => 'Chat not found'], 404);
            return;
        }
        $chat['user_avatar_url'] = User::getAvatarUrl($chat['user_username'], $chat['user_name'], (int)($chat['user_sil_id'] ?? 0));
        $chat['teknisi_avatar_url'] = User::getAvatarUrl($chat['teknisi_username'], $chat['teknisi_name'], (int)($chat['teknisi_sil_id'] ?? 0));
        
        if ($chat['type'] === 'group') {
            $chat['participants'] = Chat::getParticipants((int)$chat['id']);
            foreach ($chat['participants'] as &$p) {
                $p['avatar_url'] = User::getAvatarUrl($p['username'], $p['name'], (int)($p['sil_id'] ?? 0));
            }
        }

        $this->json($chat);
    }
    public function sendMessage(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user    = $f3->get('SESSION.user');
        $chatId  = (int)($f3->get('POST.chat_id') ?? 0);
        $message = trim($f3->get('POST.message') ?? '');
        $replyToId = $f3->get('POST.reply_to_id') ? (int)$f3->get('POST.reply_to_id') : null;

        if (!$chatId || empty($message)) {
            $this->json(['error' => 'Data tidak lengkap.'], 422);
            return;
        }
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }

        $chat = Chat::findById($chatId);
        if (!$chat) {
            $this->json(['error' => 'Chat tidak ditemukan.'], 404);
            return;
        }
        // Ticket yang selesai tidak bisa kirim; grup tetap bisa
        if (($chat['type'] ?? 'ticket') !== 'group' && $chat['status'] === 'done') {
            $this->json(['error' => 'Chat sudah ditutup.'], 403);
            return;
        }

        if ($replyToId) {
            $replyMsg = Message::findById($replyToId);
            if (!$replyMsg || (int)$replyMsg['chat_id'] !== $chatId) {
                $replyToId = null;
            }
        }

        // Hindari duplikat jika request terkirim dua kali (double-click / race)
        $recentDup = Message::findRecentDuplicate($chatId, (int)$user['id'], $message, $replyToId);
        if ($recentDup) {
            $msg = $recentDup;
            $msg['sender_avatar_url'] = User::getAvatarUrl(
                $msg['sender_username'],
                $msg['sender_name'],
                (int)($msg['sender_sil_id'] ?? 0)
            );
            $this->json(['success' => true, 'message' => $msg]);
            return;
        }

        $msgId = Message::create($chatId, (int)$user['id'], $message, $replyToId);
        $msg   = DB::fetch("
            SELECT m.*,
                   u.name AS sender_name, u.username AS sender_username, u.role AS sender_role,
                   u.avatar AS sender_avatar, u.sil_id AS sender_sil_id,
                   rm.message AS reply_message, rm.sender_id AS reply_sender_id,
                   ru.name AS reply_sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            LEFT JOIN messages rm ON rm.id = m.reply_to_id
            LEFT JOIN users ru ON ru.id = rm.sender_id
            WHERE m.id = ?", [$msgId]);

        if ($msg) {
            $msg['sender_avatar_url'] = User::getAvatarUrl($msg['sender_username'], $msg['sender_name'], (int)($msg['sender_sil_id'] ?? 0));
        }
        $this->json(['success' => true, 'message' => $msg]);
    }

    // GET /api/messages/@chat_id?since=0
    public function getMessages(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        $chatId  = (int)$params['chat_id'];
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        $sinceId = (int)($f3->get('GET.since') ?? 0);
        $msgs    = Message::getByChatId($chatId, $sinceId);
        foreach ($msgs as &$m) {
            $m['sender_avatar_url'] = User::getAvatarUrl($m['sender_username'], $m['sender_name'], (int)($m['sender_sil_id'] ?? 0));
        }

        // Also return updated chat info (status may have changed)
        $chat = Chat::findById($chatId);
        if ($chat) {
            $chat['user_avatar_url'] = User::getAvatarUrl($chat['user_username'], $chat['user_name'], (int)($chat['user_sil_id'] ?? 0));
            $chat['teknisi_avatar_url'] = User::getAvatarUrl($chat['teknisi_username'], $chat['teknisi_name'], (int)($chat['teknisi_sil_id'] ?? 0));
        }
        $this->json(['messages' => $msgs, 'chat' => $chat]);
    }

    // POST /api/chat/close/@id
    public function close(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user   = $f3->get('SESSION.user');
        $chatId = (int)$params['id'];
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        $chat   = Chat::findById($chatId);

        if (!$chat) {
            $this->json(['error' => 'Chat tidak ditemukan.'], 404);
            return;
        }

        // Only technician, admin, or the chat owner can close
        $allowed = in_array($user['role'], ['teknisi','admin'])
            || (int)$user['id'] === (int)$chat['user_id'];

        if (!$allowed) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }

        // Add closing system message with duration
        $duration = time() - strtotime($chat['created_at']);
        $formattedDuration = formatDuration($duration);
        
        $msg = '✅ Layanan ini telah diselesaikan (' . date('H:i') . '). '
             . 'Total durasi penanganan: ' . $formattedDuration . '. '
             . 'Terima kasih telah menggunakan ALISA.';

        Message::create($chatId, (int)$user['id'], $msg);
        Chat::close($chatId);
        $this->json(['success' => true]);
    }

    // POST /api/chat/take/@id
    public function take(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user   = $f3->get('SESSION.user');
        $chatId = (int)$params['id'];
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        $chat   = Chat::findById($chatId);

        if (!$chat) {
            $this->json(['error' => 'Chat tidak ditemukan.'], 404);
            return;
        }

        if (!in_array($user['role'], ['teknisi','admin'])) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }

        if ($chat['status'] !== 'waiting') {
            $this->json(['error' => 'Permintaan sudah ditangani oleh teknisi lain atau sudah selesai.'], 400);
            return;
        }

        // Assign to this technician
        Chat::markInProgress($chatId, (int)$user['id']);
        User::updateStatus((int)$user['id'], 'busy');

        // Post a system message announcing who took the ticket
        $handlerName = trim($user['name'] ?? '') ?: ($user['username'] ?? '');
        $handlerName = preg_replace('/\s*\(@[^)]+\)/', '', $handlerName);
        Message::create($chatId, (int)$user['id'], '🔧 Permintaan ditangani Tim IT (' . date('H:i') . ') — ' . $handlerName);

        $this->json(['success' => true]);
    }

    // POST /api/zoom/schedule
    // Menyimpan detail Zoom Meeting ke tabel zoom_schedules
    public function saveZoomSchedule(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');

        // Hanya user biasa yang bisa mengajukan permintaan Zoom
        if ($user['role'] !== 'user') {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }

        $chatId       = (int)($f3->get('POST.chat_id')       ?? 0);
        $butuhLink    = (int)($f3->get('POST.butuh_link')     ?? 0);
        $requestType  = trim($f3->get('POST.request_type')    ?? 'link_tech');
        $idMeeting    = trim($f3->get('POST.id_meeting')      ?? '');
        $passcode     = trim($f3->get('POST.passcode')        ?? '');

        // Normalize datetime: 'YYYY-MM-DDTHH:mm' → 'YYYY-MM-DD HH:mm:ss'
        $waktuMulaiRaw   = trim($f3->get('POST.waktu_mulai')   ?? '');
        $waktuSelesaiRaw = trim($f3->get('POST.waktu_selesai') ?? '');
        $waktuMulai   = $waktuMulaiRaw   ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $waktuMulaiRaw)))   : '';
        $waktuSelesai = $waktuSelesaiRaw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $waktuSelesaiRaw))) : '';
        $namaKegiatan = trim($f3->get('POST.nama_kegiatan')   ?? '');
        $teknis       = trim($f3->get('POST.teknis')           ?? ''); // nilai dari input "teknis" (ruangan)
        $teknis       = $teknis ?: null;

        // Validasi input
        $allowedRooms = ['RAYON', 'SELULOSA', 'KERTAS', 'PINUS'];
        if (!$chatId)                       { $this->json(['error' => 'chat_id tidak valid.'],        422); return; }
        if (!$waktuMulai || !$waktuSelesai) { $this->json(['error' => 'Waktu tidak lengkap.'],        422); return; }
        if (empty($namaKegiatan))           { $this->json(['error' => 'Nama kegiatan wajib diisi.'],  422); return; }
        
        if ($requestType === 'own_link') {
            if (empty($idMeeting)) { $this->json(['error' => 'ID Meeting wajib diisi.'], 422); return; }
            if (empty($passcode))  { $this->json(['error' => 'Passcode wajib diisi.'],   422); return; }
        }

        if ($requestType !== 'link_only') {
            if (empty($teknis) || !in_array($teknis, $allowedRooms)) { 
                $this->json(['error' => 'Ruangan wajib dipilih untuk persiapan teknis.'], 422); 
                return; 
            }
        }

        // Pastikan chat milik user ini
        $chat = Chat::findById($chatId);
        if (!$chat || (int)$chat['user_id'] !== (int)$user['id']) {
            $this->json(['error' => 'Chat tidak ditemukan atau bukan milik Anda.'], 403);
            return;
        }

        // Simpan ke tabel zoom_schedules
        DB::run("
            INSERT INTO zoom_schedules
                (chat_id, butuh_link, request_type, id_meeting, passcode, waktu_mulai, waktu_selesai, nama_kegiatan, ruangan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [$chatId, $butuhLink, $requestType, $idMeeting, $passcode, $waktuMulai, $waktuSelesai, $namaKegiatan, $teknis]);

        // Kirim pesan ringkasan ke chat sebagai konfirmasi
        $typeLabels = [
            'link_only' => 'Hanya Link Zoom (Tanpa Persiapan Ruangan)',
            'link_tech' => 'Link Zoom + Persiapan Ruangan',
            'own_link'  => 'Ruangan'
        ];
        $label = $typeLabels[$requestType] ?? 'Zoom Meeting';

        $summary  = "ℹ Permintaan Zoom Meeting diterima:\n"
                  . "📝 Tipe: {$label}\n"
                  . "📅 Kegiatan: {$namaKegiatan}\n";
        
        if ($requestType !== 'link_only') {
            $summary .= "🏢 Ruangan: {$teknis}\n";
        }
        
        $summary .= "⏰ Mulai: " . date('d M Y H:i', strtotime($waktuMulai)) . "\n"
                  . "⏰ Selesai: " . date('d M Y H:i', strtotime($waktuSelesai)) . "\n";
        
        if ($requestType === 'own_link') {
            $summary .= "🔗 ID Meeting: {$idMeeting}\n"
                      . "🔑 Passcode: {$passcode}\n";
        } else {
            $summary .= "🔗 Link Zoom dari IT: Ya (Akan disiapkan)\n";
        }

        Message::create($chatId, (int)$user['id'], $summary);

        $this->json(['success' => true, 'chat_id' => $chatId]);
    }

    // GET /api/admin/statistics/category-detail?category=...
    public function getCategoryStats(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $category = $f3->get('GET.category');
        $start    = $f3->get('GET.start');
        $end      = $f3->get('GET.end');

        if (!$category) {
            $this->json(['error' => 'Category required'], 400);
            return;
        }

        $data = Chat::getCategoryUserBreakdown($category, $start, $end);
        foreach ($data as &$d) {
            $d['avatar_url'] = User::getAvatarUrl($d['username'], $d['name'], (int)($d['sil_id'] ?? 0));
        }

        $this->json($data);
    }

    // GET /api/admin/statistics/technician-detail?tech_id=...
    public function getTechnicianStats(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $techId = $f3->get('GET.tech_id');
        $start  = $f3->get('GET.start');
        $end    = $f3->get('GET.end');

        if (!$techId) {
            $this->json(['error' => 'Technician ID required'], 400);
            return;
        }

        $data = Chat::getTechnicianPerformanceBreakdown($techId, $start, $end);
        $this->json($data);
    }
    // GET /api/admin/statistics/completion-time-detail
    public function getCompletionTimeDetail(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $start = $f3->get('GET.start');
        $end   = $f3->get('GET.end');

        $data = Chat::getCompletionTimeBreakdown($start, $end);
        $this->json($data);
    }

    // POST /api/admin/chat/group/create
    public function createGroup(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $title = trim($f3->get('POST.title') ?? '');
        $description = trim($f3->get('POST.description') ?? '');
        
        $participantIds = $f3->get('POST.participants') ?? [];
        if (is_string($participantIds)) $participantIds = json_decode($participantIds, true) ?? [];
        
        $silParticipants = $f3->get('POST.sil_participants') ?? [];
        if (is_string($silParticipants)) $silParticipants = json_decode($silParticipants, true) ?? [];

        if (empty($title)) {
            $this->json(['error' => 'Nama grup tidak boleh kosong.'], 422);
            return;
        }

        // Panggil helper handleGroupIconUpload yang sudah ada di bawah controller
        try {
            $iconPath = $this->handleGroupIconUpload();
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 400);
            return;
        }

        try {
            $chatId = Chat::createGroup((int)$user['id'], $title, $description, 'Grup', $participantIds, $silParticipants, $iconPath);
            $this->json(['success' => true, 'chat_id' => $chatId]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Server Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    // POST /api/admin/chat/group/update
    public function updateGroup(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        $title = trim($f3->get('POST.title') ?? '');
        $description = trim($f3->get('POST.description') ?? '');
        
        $participantIds = $f3->get('POST.participants') ?? [];
        if (is_string($participantIds)) $participantIds = json_decode($participantIds, true) ?? [];
        
        $silParticipants = $f3->get('POST.sil_participants') ?? [];
        if (is_string($silParticipants)) $silParticipants = json_decode($silParticipants, true) ?? [];

        if (!$id || empty($title)) {
            $this->json(['error' => 'Data tidak valid.'], 422);
            return;
        }

        // Logika Data Flow untuk upload / hapus Icon
        $iconPath = null;
        if ($f3->get('POST.remove_icon') === '1') {
            $iconPath = ''; // Pakai string kosong sebagai flag untuk menghapus data di DB
        } else {
            try {
                $iconPath = $this->handleGroupIconUpload();
            } catch (\RuntimeException $e) {
                $this->json(['error' => $e->getMessage()], 400);
                return;
            }
        }

        Chat::updateGroup($id, $title, $description, 'Grup', $participantIds, $silParticipants, $iconPath);
        $this->json(['success' => true]);
    }

    // POST /api/admin/chat/group/delete
    public function deleteGroup(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID tidak valid.'], 422);
            return;
        }

        Chat::deleteGroup($id);
        $this->json(['success' => true]);
    }

    // POST /api/chat/message/pin
    public function pinMessage(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if (($user['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Hanya admin yang dapat menyematkan pesan.'], 403);
            return;
        }
        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID tidak valid.'], 422);
            return;
        }
        $msg = Message::findById($id);
        if (!$msg) {
            $this->json(['error' => 'Pesan tidak ditemukan.'], 404);
            return;
        }
        if (!$this->canAccessChat((int)$msg['chat_id'], $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        Message::pin($id);
        $this->json(['success' => true]);
    }

    // POST /api/chat/message/unpin
    public function unpinMessage(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if (($user['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Hanya admin yang dapat melepas sematan.'], 403);
            return;
        }
        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID tidak valid.'], 422);
            return;
        }
        $msg = Message::findById($id);
        if (!$msg) {
            $this->json(['error' => 'Pesan tidak ditemukan.'], 404);
            return;
        }
        if (!$this->canAccessChat((int)$msg['chat_id'], $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        Message::unpin($id);
        $this->json(['success' => true]);
    }

    // POST /api/chat/pin
    public function pinChat(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if (($user['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Hanya admin yang dapat menyematkan percakapan.'], 403);
            return;
        }
        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID tidak valid.'], 422);
            return;
        }
        if (!Chat::findById($id)) {
            $this->json(['error' => 'Percakapan tidak ditemukan.'], 404);
            return;
        }
        if (!$this->canAccessChat($id, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        Chat::pin($id);
        $this->json(['success' => true]);
    }

    // POST /api/chat/unpin
    public function unpinChat(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if (($user['role'] ?? '') !== 'admin') {
            $this->json(['error' => 'Hanya admin yang dapat melepas sematan.'], 403);
            return;
        }
        $id = (int)($f3->get('POST.id') ?? 0);
        if (!$id) {
            $this->json(['error' => 'ID tidak valid.'], 422);
            return;
        }
        if (!Chat::findById($id)) {
            $this->json(['error' => 'Percakapan tidak ditemukan.'], 404);
            return;
        }
        if (!$this->canAccessChat($id, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        Chat::unpin($id);
        $this->json(['success' => true]);
    }

    // GET /api/admin/users
    public function getAllUsers(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $users = DB::fetchAll("SELECT id, name, username, role, avatar, sil_id FROM users ORDER BY name ASC");
        foreach ($users as &$u) {
            $u['avatar_url'] = User::getAvatarUrl($u['username'], $u['name'], (int)($u['sil_id'] ?? 0));
        }

        $this->json($users);
    }

    // GET /api/admin/groups
    public function listGroups(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        if ($user['role'] !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $groups = Chat::listGroups();
        $this->json($groups);
    }

    // GET /api/chat/@id/participants
    public function getParticipants(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        $chatId = (int)$params['id'];
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }
        $participants = Chat::getParticipants($chatId);
        foreach ($participants as &$p) {
            $p['avatar_url'] = User::getAvatarUrl($p['username'], $p['name'], (int)($p['sil_id'] ?? 0));
        }
        $this->json($participants);
    }

    // POST /api/chat/upload
    public function uploadFile(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = $f3->get('SESSION.user');
        $chatId = (int)($f3->get('POST.chat_id') ?? 0);
        $logPrefix = '[chat-upload] ';

        if (!$chatId || !isset($_FILES['file'])) {
            error_log($logPrefix . 'invalid payload; chat_id=' . $chatId . '; has_file=' . (isset($_FILES['file']) ? '1' : '0'));
            $this->json(['error' => 'Data tidak lengkap.'], 422);
            return;
        }
        if (!$this->canAccessChat($chatId, $user)) {
            $this->json(['error' => 'Tidak diizinkan.'], 403);
            return;
        }

        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL    => 'File terunggah sebagian. Coba ulangi.',
                UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload tidak tersedia di server.',
                UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file ke storage.',
                UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.'
            ];
            $msg = $uploadErrors[$file['error']] ?? 'Terjadi kesalahan saat upload file.';
            error_log($logPrefix . 'php upload error=' . (int)$file['error'] . '; name=' . ($file['name'] ?? 'unknown'));
            $this->json(['error' => $msg], 400);
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            error_log($logPrefix . 'blocked extension; ext=' . $ext . '; name=' . $file['name']);
            $this->json(['error' => 'Format file tidak diizinkan. Hanya PDF, Excel, Word, dan Gambar.'], 400);
            return;
        }

        $dirResolved = Storage::resolveChatUploadDir();
        if (!$dirResolved['ok']) {
            error_log($logPrefix . 'no writable upload dir; tried=' . implode('; ', $dirResolved['tried'] ?? []));
            $this->json([
                'error' => Storage::writableErrorMessage($dirResolved['error'] ?? 'not_writable', $dirResolved['tried'] ?? null),
            ], 500);
            return;
        }
        $uploadDirAbs = $dirResolved['path'];
        $urlPrefix = $dirResolved['url_prefix'];

        $filename = 'file_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDirAbs . $filename;

        if (@move_uploaded_file($file['tmp_name'], $target)) {
            $fileUrl = $urlPrefix . $filename;
            // Specialized format for frontend parsing: 📎 [FILE] filename | url
            $msgText = "📎 [FILE] " . $file['name'] . " | " . $fileUrl;
            
            $msgId = Message::create($chatId, (int)$user['id'], $msgText);
            
            $msg = DB::fetch("
                SELECT m.*, u.name AS sender_name, u.username AS sender_username, u.role AS sender_role, u.avatar AS sender_avatar, u.sil_id AS sender_sil_id
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE m.id = ?", [$msgId]);

            if ($msg) {
                 $msg['sender_avatar_url'] = User::getAvatarUrl($msg['sender_username'], $msg['sender_name'], (int)($msg['sender_sil_id'] ?? 0));
            }
            
            $this->json(['success' => true, 'message' => $msg]);
        } else {
            error_log($logPrefix . 'move_uploaded_file failed; tmp=' . ($file['tmp_name'] ?? 'none') . '; target=' . $target);
            $this->json(['error' => 'Gagal mengupload file ke server. Pastikan folder upload memiliki izin tulis (writable).'], 500);
        }
    }

    // GET /files/@file — serve lampiran chat (tmp/storage fallback)
    public function serveChatFile(\Base $f3, array $params): void
    {
        Security::checkAuth($f3);

        $filename = basename((string)($params['file'] ?? ''));
        $path = Storage::findChatUploadFile($filename);
        if (!$path) {
            http_response_code(404);
            echo 'File tidak ditemukan.';
            return;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $types = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $mime = $types[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    private function canAccessChat(int $chatId, array $user): bool
    {
        return Chat::canUserAccessChat(
            $chatId,
            (int)($user['id'] ?? 0),
            strtolower((string)($user['role'] ?? 'user'))
        );
    }

    /** @return list<mixed> */
    private function postArray(\Base $f3, string $key): array
    {
        $val = $f3->get('POST.' . $key);
        if (is_array($val)) {
            return $val;
        }
        if ($val === null || $val === '') {
            return [];
        }
        return [$val];
    }

    private function handleGroupIconUpload(): ?string
    {
        if (!isset($_FILES['group_icon']) || $_FILES['group_icon']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        $file = $_FILES['group_icon'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL    => 'File terunggah sebagian. Coba ulangi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload tidak tersedia di server.',
                UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file ke storage.',
                UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.'
            ];
            $msg = $uploadErrors[$file['error']] ?? 'Terjadi kesalahan saat upload file icon.';
            throw new \RuntimeException($msg);
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Format file icon tidak diizinkan. Hanya JPG, JPEG, PNG, dan WEBP.');
        }
        
        if ($file['size'] > 2097152) { // 2MB
            throw new \RuntimeException('Ukuran file icon terlalu besar. Maksimal 2MB.');
        }
        
        $dirCheck = Storage::ensureWritable(Storage::groupIconsDir());
        if (!$dirCheck['ok']) {
            throw new \RuntimeException('Folder penyimpanan icon grup tidak dapat ditulisi (not writable). Hubungi admin untuk menjalankan scripts/fix-permissions.sh.');
        }
        
        $filename = 'grp_' . uniqid() . '.' . $ext;
        $dest = $dirCheck['path'] . $filename;
        
        if (@move_uploaded_file($file['tmp_name'], $dest)) {
            @chmod($dest, 0666);
            error_log('[group-icon] Successfully uploaded: ' . $filename);
            return 'public/group-icons/' . $filename;
        }
        
        throw new \RuntimeException('Gagal menyimpan file icon ke folder tujuan. Periksa izin akses folder server.');
    }
}