<?php
// ============================================
// app/views/components/chat_panel.php
// Variables: $currentUser, $activeChat, $messages, $activeChatId
// ============================================

$user      = $f3->get('currentUser');
$chat      = $f3->get('activeChat');
$messages  = $f3->get('messages');
$chatId    = $f3->get('activeChatId');
$isGroup   = $chat && (($chat['type'] ?? 'ticket') === 'group');
$pinnedMsg = $f3->get('pinnedMsg');


function techStatusLabel($s) {
    switch($s) {
        case 'available': return '<span class="tech-status available"><i class="bi bi-circle-fill"></i> Tersedia</span>';
        case 'busy':      return '<span class="tech-status busy"><i class="bi bi-circle-fill"></i> Sibuk</span>';
        default:          return '<span class="tech-status offline"><i class="bi bi-circle-fill"></i> Offline</span>';
    }
}

function chatStatusLabel($s, $isResponded = false) {
    switch($s) {
        case 'waiting':
            if ($isResponded) {
                return '<span class="badge bg-warning text-dark px-3 py-2 fs-75"><i class="bi bi-reply-fill"></i> Sudah Direspons</span>';
            }
            return '<span class="badge bg-secondary px-3 py-2 fs-75">⏳ Menunggu</span>';
        case 'in_progress':
            return '<span class="badge bg-primary px-3 py-2 fs-75"><i class="bi bi-arrow-repeat"></i> Permintaan ditangani Tim IT</span>';
        case 'done':
            return '<span class="badge bg-success px-3 py-2 fs-75"><i class="bi bi-check-circle"></i> Selesai</span>';
        default:
            return '';
    }
}
function formatMsgTime($dt) {
    return date('H:i', strtotime($dt));
}

/**
 * Highlight @mention in message text (already HTML-escaped)
 */
function renderMentions(string $html): string {
    return preg_replace('/@([\w.\-]+)/u', '<span class="mention-highlight">@$1</span>', $html);
}

function renderRequestCard($msg) {
    $text = trim($msg);
    // Remove common prefixes
    $text = str_replace(['[SYSTEM_CARD]'], '', $text);
    // Remove leading ℹ (may be multi-byte) using regex
    $text = preg_replace('/^\s*ℹ\s*/u', '', $text);
    $text = trim($text);
    
    $lines = explode("\n", $text);
    $title_line = trim(array_shift($lines)); 
    $header_title = (strpos($text, 'Zoom') !== false) ? 'Permintaan Zoom Meeting' : 'Permintaan Layanan Baru';
    
    $html = '<div class="request-summary-card">';
    $html .= '<div class="request-summary-header"><i class="bi bi-info-circle-fill"></i><span>' . $header_title . '</span></div>';
    $html .= '<div class="request-summary-body">';
    
    $hasLines = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Split on first colon only to preserve values containing colons (e.g. times)
        $colonPos = strpos($line, ':');
        if ($colonPos === false) continue;

        $hasLines = true;
        $labelWithEmoji = trim(substr($line, 0, $colonPos));
        $value = trim(substr($line, $colonPos + 1));
        
        // Strip leading emoji/symbols using grapheme-aware approach
        // preg_replace with \X matches a full grapheme cluster (including multi-codepoint emoji like ⚠️)
        $label = preg_replace('/^\X(?![a-zA-Z0-9])/u', '', $labelWithEmoji);
        $label = trim($label);
        // If stripping removed everything or label is still non-alpha at start, try again
        if (empty($label) || !preg_match('/[a-zA-Z]/u', $label[0] ?? '')) {
            // Fallback: strip all leading non-letter characters
            $label = preg_replace('/^[^\p{L}]+/u', '', $labelWithEmoji);
            $label = trim($label);
        }
        if (empty($label)) $label = $labelWithEmoji;
            
        $isDetail = strpos(strtolower($label), 'detail') !== false;
        $icon = 'bi-dot';
        $l = strtolower($label);
        if (strpos($l, 'tiket') !== false) continue; // Skip rendering Tiket entirely!
        if (strpos($l, 'link zoom') !== false) continue; // Hide Link Zoom field
        if (strpos($l, 'nama') !== false) $icon = 'bi-person-fill';
        elseif (strpos($l, 'ruangan') !== false) $icon = 'bi-building';
        elseif (strpos($l, 'tipe') !== false) $icon = 'bi-cpu';
        elseif (strpos($l, 'titik') !== false) $icon = 'bi-geo-fill';
        elseif (strpos($l, 'perangkat') !== false) $icon = 'bi-laptop';
        elseif (strpos($l, 'merk') !== false) $icon = 'bi-tag-fill';
        elseif (strpos($l, 'asset') !== false) $icon = 'bi-hash';
        elseif (strpos($l, 'model') !== false) $icon = 'bi-printer';
        elseif (strpos($l, 'kendala') !== false) $icon = 'bi-exclamation-triangle';
        elseif (strpos($l, 'software') !== false) $icon = 'bi-app-indicator';
        elseif (strpos($l, 'aplikasi') !== false) $icon = 'bi-window-stack';
        elseif (strpos($l, 'modul') !== false) $icon = 'bi-puzzle-fill';
        elseif (strpos($l, 'detail') !== false) $icon = 'bi-card-text';
        elseif (strpos($l, 'tiket') !== false) $icon = 'bi-ticket-detailed-fill';
        
        $valueHtml = nl2br(htmlspecialchars($value));
        if (strpos($l, 'tiket') !== false) {
            $chatIdRaw = trim(str_replace('#', '', $value));
            $valueHtml = '<a href="?chat=' . $chatIdRaw . '&filter=all" class="btn btn-primary btn-sm rounded-pill px-3 py-1 mt-1 fw-bold" style="font-size: 11px;">';
            $valueHtml .= '<i class="bi bi-box-arrow-up-right me-1"></i> Buka Permintaan';
            $valueHtml .= '</a>';
        }

        $html .= '<div class="request-summary-item">';
        $html .= '<i class="bi ' . $icon . '"></i>';
        $html .= '<div>';
        $html .= '<div class="request-summary-label">' . htmlspecialchars($label) . '</div>';
        $html .= '<div class="request-summary-value ' . ($isDetail ? 'detail' : '') . '">' . $valueHtml . '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Fallback for old format or parsing failure
    if (!$hasLines) {
        return '<div class="msg-system"><i class="bi bi-info-circle me-2"></i>' . nl2br(htmlspecialchars($msg)) . '</div>';
    }

    $html .= '</div></div>';
    return $html;
}
?>

<main class="chat-panel" id="chatPanel">
  <?php if (!$chat): ?>
    <!-- ── Empty State ── -->
    <header class="chat-header d-flex d-lg-none">
      <button class="btn-sidebar-toggle" onclick="openSidebar()"><i class="bi bi-list"></i></button>
      <div class="chat-header-info ms-3">
        <div class="chat-header-name">ALISA</div>
        <div class="chat-header-title">Aplikasi Layanan Internal IT Selulosa</div>
      </div>
    </header>

    <div class="chat-empty-state">
      <?php if ($user['role'] === 'user'): ?>
        <div class="empty-illustration d-flex align-items-center justify-content-center" style="width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg, rgba(79,70,229,0.1), rgba(124,58,237,0.1)); border:1px solid rgba(79,70,229,0.2); margin-bottom: 20px;">
          <i class="bi bi-chat-dots" style="font-size: 50px; color: var(--primary); text-shadow: 0 4px 12px rgba(79,70,229,0.3);"></i>
        </div>
        <h4 class="fw-bold">Halo, ada yang bisa kami bantu?</h4>
        <p class="mb-4">Pilih kategori layanan di bawah untuk mulai membuat permintaan bantuan baru.</p>
        
        <div class="category-grid w-100" style="max-width: 620px;">
          <?php foreach ($f3->get('categories') as $cat): ?>
          <div class="cat-card" data-cat="<?= htmlspecialchars($cat['code']) ?>" onclick="quickRequest('<?= htmlspecialchars($cat['code']) ?>')">
            <?php if ($cat['custom_icon_path']): ?>
              <img src="<?= htmlspecialchars($cat['custom_icon_path']) ?>" alt="icon" style="width:24px; height:24px; object-fit:contain; margin-bottom:12px;">
            <?php else: ?>
              <i class="bi <?= htmlspecialchars($cat['default_icon_class']) ?>"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($cat['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-illustration d-flex align-items-center justify-content-center" style="width:120px; height:120px; border-radius:50%; background:linear-gradient(135deg, rgba(79,70,229,0.1), rgba(124,58,237,0.1)); border:1px solid rgba(79,70,229,0.2);">
          <i class="bi bi-chat-dots" style="font-size: 60px; color: var(--primary); text-shadow: 0 4px 12px rgba(79,70,229,0.3);"></i>
        </div>
        <h4>Belum Ada Percakapan Dipilih</h4>
        <p>Silakan pilih permintaan layanan dari sidebar untuk mulai merespons.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <!-- ── 1. Chat Header ── -->
    <header class="chat-header">
      <button class="btn-sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>

      <?php $isGroup = ($chat['type'] ?? 'ticket') === 'group'; ?>
      <?php if ($isGroup): ?>
        <?php $grpIcon = !empty($chat['icon_path']) ? htmlspecialchars($chat['icon_path']) : ''; ?>
        <div class="chat-header-avatar" style="background: <?= $grpIcon ? 'none' : 'linear-gradient(135deg, #4f46e5, #7c3aed)' ?>">
          <?php if ($grpIcon): ?>
            <img src="<?= $grpIcon ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
          <?php else: ?>
            <i class="bi bi-people-fill text-white"></i>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php $chatUserImg = User::getAvatarUrl($chat['user_username'], $chat['user_name'], (int)($chat['user_sil_id'] ?? 0)); ?>
        <div class="chat-header-avatar" style="background:<?= $chatUserImg ? 'none' : avatarColor((int)$chat['user_id']) ?>">
          <?php if ($chatUserImg): ?>
            <img src="<?= $chatUserImg ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
          <?php else: ?>
            <?= htmlspecialchars($chat['user_avatar'] ?? User::getInitials($chat['user_name'] ?? '')) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="chat-header-info">
        <div class="chat-header-name text-truncate"><?= htmlspecialchars($isGroup ? $chat['title'] : ($chat['user_name'] ?? '')) ?></div>
        <div class="chat-header-title">
          <?php if ($isGroup): ?>
            <a href="javascript:void(0)" class="text-muted small text-decoration-none group-participants-trigger" onclick="openGroupInfo()" style="font-weight: 600;">
              <i class="bi bi-people-fill me-1 text-primary"></i><?= count($chat['participants'] ?? []) ?> Peserta
            </a>
          <?php else: ?>
            <span class="chat-header-category"><?= htmlspecialchars($chat['category'] ?? '') ?></span>
            <?php if (!empty($chat['room'])): ?>
              <span class="ms-1"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($chat['room'] ?? '') ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="chat-header-actions d-flex align-items-center gap-2">
        <?php if ($isGroup): ?>
          <button type="button" class="btn btn-sm bg-primary-subtle text-primary border border-primary-subtle px-3 rounded-pill fw-bold" style="font-size: 11px; transition: all 0.2s;" onclick="openGroupInfo()">
            <i class="bi bi-info-circle-fill me-1"></i> Info Grup
          </button>
        <?php else: ?>
          <div id="chatStatusBadge"><?= chatStatusLabel($chat['status'], !empty($chat['is_responded'])) ?></div>
        <?php endif; ?>

        <?php if (!$isGroup && (in_array($user['role'], ['teknisi','admin']) || (int)$user['id'] === (int)$chat['user_id']) && $chat['status'] !== 'done'): ?>
          <button type="button" class="btn-close-chat" onclick="closeChat(<?= $chatId ?>)" title="Selesaikan layanan">
            <i class="bi bi-check2-circle"></i>
            <span class="btn-close-chat-label">Selesai</span>
          </button>
        <?php endif; ?>
      </div>
    </header>
    
    <!-- ── 2. Message Area (Scrollable) ── -->
    <div class="chat-messages" id="messagesArea">
      <?php if (!empty($pinnedMsg)): ?>
        <div class="pinned-message-bar mb-3">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="small fw-bold text-primary"><i class="bi bi-pin-angle-fill me-1"></i>Pesan Disematkan</div>
              <div class="small text-dark"><?= nl2br(htmlspecialchars($pinnedMsg['message'] ?? '')) ?></div>
            </div>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
              <button class="btn btn-sm btn-light border rounded-pill" onclick="unpinMessage(<?= (int)$pinnedMsg['id'] ?>)">Lepas</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="date-sep"><span><?= date('d F Y', strtotime($chat['created_at'])) ?></span></div>

      <?php foreach ($messages as $msg): ?>
        <?php
          $msgText  = trim($msg['message'] ?? '');
          $isMe     = (int)$msg['sender_id'] === (int)$user['id'];
          // Robust detection for cards
          $isRequestCard = strpos($msgText, 'Permintaan Layanan Baru:') !== false;
          $isZoomCard    = strpos($msgText, 'Permintaan Zoom Meeting') !== false;
          
          $isSystem = $isRequestCard || $isZoomCard 
                      || in_array(mb_substr($msgText, 0, 1), ['✅', '🔧', '⚠️', 'ℹ']) 
                      || strpos($msgText, '[SYSTEM_CARD]') === 0;
          $isFileMsg = strpos($msgText, '📎 [FILE]') === 0;
        ?>
        <?php if ($isSystem): ?>
          <?php if ($isRequestCard || $isZoomCard): ?>
            <div class="position-relative">
              <?= renderRequestCard($msgText) ?>
              <?php if (($user['role'] ?? '') === 'admin'): ?>
                <div class="text-end mt-1 msg-actions msg-actions--standalone">
                  <?php if (!empty($msg['is_pinned'])): ?>
                    <button type="button" class="btn-pin-msg" onclick="unpinMessage(<?= (int)$msg['id'] ?>)" title="Lepas sematan pesan">
                      <i class="bi bi-pin-angle-fill"></i>
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn-pin-msg" onclick="pinMessage(<?= (int)$msg['id'] ?>)" title="Sematkan pesan">
                      <i class="bi bi-pin-angle"></i>
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?php
              $sysCleanText = trim(mb_substr($msg['message'] ?? '', 1));
              // Highlight name after em dash (—) in system messages
              $sysHtml = nl2br(htmlspecialchars($sysCleanText));
              if (strpos($sysCleanText, ' — ') !== false) {
                  $parts = explode(' — ', $sysCleanText, 2);
                  $sysHtml = nl2br(htmlspecialchars($parts[0])) . ' — <strong>' . nl2br(htmlspecialchars($parts[1])) . '</strong>';
              }
            ?>
            <div class="position-relative">
              <div class="msg-system"><i class="bi bi-info-circle me-2"></i><?= $sysHtml ?></div>
              <?php if (($user['role'] ?? '') === 'admin'): ?>
                <div class="text-end mt-1 msg-actions msg-actions--standalone">
                  <?php if (!empty($msg['is_pinned'])): ?>
                    <button type="button" class="btn-pin-msg" onclick="unpinMessage(<?= (int)$msg['id'] ?>)" title="Lepas sematan pesan">
                      <i class="bi bi-pin-angle-fill"></i>
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn-pin-msg" onclick="pinMessage(<?= (int)$msg['id'] ?>)" title="Sematkan pesan">
                      <i class="bi bi-pin-angle"></i>
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="msg-row <?= $isMe ? 'msg-right' : 'msg-left' ?>">
            <?php if (!$isMe): ?>
              <?php $senderImg = User::getAvatarUrl($msg['sender_username'], $msg['sender_name'], (int)($msg['sender_sil_id'] ?? 0)); ?>
              <div class="msg-sender-avatar" style="background:<?= $senderImg ? 'none' : avatarColor((int)$msg['sender_id']) ?>">
                <?php if ($senderImg): ?>
                  <img src="<?= $senderImg ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                <?php else: ?>
                  <?= htmlspecialchars($msg['sender_avatar'] ?? User::getInitials($msg['sender_name'] ?? '')) ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="msg-content">
              <?php if ($isGroup && !$isMe): ?>
                <div class="msg-sender-name"><?= htmlspecialchars($msg['sender_name']) ?> <span class="badge bg-light text-muted fw-normal" style="font-size: 9px;"><?= strtoupper($msg['sender_role']) ?></span></div>
              <?php endif; ?>
              <div class="msg-bubble <?= $isMe ? 'bubble-me' : 'bubble-other' ?>" data-msg-id="<?= $msg['id'] ?>" data-sender-name="<?= htmlspecialchars($msg['sender_name']) ?>">
                <?php if (!empty($msg['reply_to_id']) && !empty($msg['reply_message'])): ?>
                  <div class="reply-preview <?= $isMe ? 'reply-preview-me' : '' ?>" onclick="scrollToMessage(<?= (int)$msg['reply_to_id'] ?>)">
                    <div class="reply-preview-sender"><?= htmlspecialchars($msg['reply_sender_name'] ?? '') ?></div>
                    <div class="reply-preview-text"><?= htmlspecialchars(mb_substr($msg['reply_message'], 0, 80)) ?><?= mb_strlen($msg['reply_message']) > 80 ? '…' : '' ?></div>
                  </div>
                <?php endif; ?>
                <?php
                  $canReply = $isGroup;
                  $isAdminMsg = ($user['role'] ?? '') === 'admin';
                  $replyPreview = htmlspecialchars(addslashes(mb_substr($msgText ?: ($msg['message'] ?? ''), 0, 60)), ENT_QUOTES);
                ?>
                <div class="d-flex justify-content-between align-items-start gap-2 msg-body-row">
                  <div class="flex-grow-1" style="min-width: 0;">
                  <?php if ($isFileMsg): ?>
                    <?php
                      $content = trim(substr($msgText, 12));
                      $parts = explode('|', $content);
                      $name = isset($parts[0]) ? trim($parts[0]) : 'File';
                      $fileUrl = isset($parts[1]) ? trim($parts[1]) : '#';
                      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                      $isImageFile = in_array($ext, ['jpg', 'jpeg', 'png']);
                    ?>
                    <?php if ($isImageFile): ?>
                      <div class="mb-1"><a href="<?= $fileUrl ?>" target="_blank"><img src="<?= $fileUrl ?>" style="max-width: 100%; max-height: 250px; border-radius: 6px; object-fit: contain;"></a></div>
                    <?php else: ?>
                      <?php
                        $iconClass = 'bi-file-earmark-text-fill text-secondary';
                        if ($ext === 'pdf') $iconClass = 'bi-file-earmark-pdf-fill text-danger';
                        elseif (in_array($ext, ['xls', 'xlsx'])) $iconClass = 'bi-file-earmark-excel-fill text-success';
                        elseif (in_array($ext, ['doc', 'docx'])) $iconClass = 'bi-file-earmark-word-fill text-primary';
                      ?>
                      <div class="d-flex align-items-center gap-2 p-2 mb-1 bg-white rounded shadow-sm border" style="min-width: 220px; max-width: 100%;">
                        <div style="font-size: 28px; line-height: 1;"><i class="bi <?= $iconClass ?>"></i></div>
                        <div class="flex-grow-1 overflow-hidden" style="max-width: 180px;">
                          <div class="text-truncate fw-semibold text-dark" style="font-size: 13px;"><?= htmlspecialchars($name) ?></div>
                          <div class="text-muted" style="font-size: 11px;"><?= strtoupper($ext) ?></div>
                        </div>
                        <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-light rounded-circle flex-shrink-0" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f0f2f5; border: none;"><i class="bi bi-download text-dark"></i></a>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <p class="mb-0"><?= renderMentions(nl2br(htmlspecialchars($msg['message'] ?? ''))) ?></p>
                  <?php endif; ?>
                  </div>
                  <?php if ($canReply || $isAdminMsg): ?>
                  <div class="msg-actions">
                    <?php if ($canReply): ?>
                      <button type="button" class="btn-reply-msg" onclick="startReply(<?= $msg['id'] ?>, '<?= htmlspecialchars($msg['sender_name'], ENT_QUOTES) ?>', '<?= $replyPreview ?>')" title="Balas">
                        <i class="bi bi-reply-fill"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($isAdminMsg): ?>
                      <?php if (!empty($msg['is_pinned'])): ?>
                        <button type="button" class="btn-pin-msg" onclick="unpinMessage(<?= (int)$msg['id'] ?>)" title="Lepas sematan pesan">
                          <i class="bi bi-pin-angle-fill"></i>
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn-pin-msg" onclick="pinMessage(<?= (int)$msg['id'] ?>)" title="Sematkan pesan">
                          <i class="bi bi-pin-angle"></i>
                        </button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="msg-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
              </div>
            </div>

            <?php if ($isMe): ?>
              <?php $myMsgImg = User::getAvatarUrl($user['username'], $user['name'], (int)($user['sil_id'] ?? 0)); ?>
              <div class="msg-sender-avatar ms-2" style="background:<?= $myMsgImg ? 'none' : avatarColor((int)$user['id']) ?>">
                <?php if ($myMsgImg): ?>
                  <img src="<?= $myMsgImg ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                <?php else: ?>
                  <?= htmlspecialchars($user['avatar'] ?? User::getInitials($user['name'] ?? '')) ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <div class="typing-indicator" id="typingIndicator" style="display:none"><span></span><span></span><span></span></div>
    </div>

    <!-- ── 4. Chat Input Area (Fixed) ── -->
    <div class="chat-input-area <?= $isGroup ? 'chat-input-area--group' : '' ?>" id="chatInputArea">
      <?php if (!$isGroup && $chat['status'] === 'waiting' && in_array($user['role'], ['teknisi','admin'])): ?>
        <div class="take-ticket-alert" id="takeTicketBar">
          <div class="d-flex align-items-center gap-3">
            <div class="take-ticket-icon"><i class="bi bi-hand-index-thumb-fill"></i></div>
            <div class="take-ticket-text">
              <strong>Permintaan Menunggu</strong>
              <p class="mb-0 text-muted small">Klik tangani untuk mulai membantu.</p>
            </div>
          </div>
          <button class="btn btn-primary rounded-pill px-4" onclick="takeTicket(<?= $chatId ?>)">Tangani</button>
        </div>
      <?php endif; ?>

      <?php if ($isGroup): ?>
        <div class="chat-input-composer">
        <!-- Reply Preview Bar (grup saja) -->
        <div class="reply-bar" id="replyBar" style="display:none;">
          <div class="reply-bar-inner">
            <i class="bi bi-reply-fill text-primary me-2"></i>
            <div class="reply-bar-content">
              <div class="reply-bar-name" id="replyBarName"></div>
              <div class="reply-bar-text" id="replyBarText"></div>
            </div>
          </div>
          <button class="reply-bar-close" onclick="cancelReply()" title="Batal balas"><i class="bi bi-x-lg"></i></button>
        </div>

        <!-- Mention Autocomplete Dropdown -->
        <div class="mention-dropdown" id="mentionDropdown" style="display:none;"></div>

        <div class="chat-input-wrap">
          <button class="btn btn-link p-0 text-muted fs-5" onclick="document.getElementById('fileInput').click()"><i class="bi bi-paperclip"></i></button>
          <input type="file" id="fileInput" style="display:none" onchange="handleFileUpload(this)">
          <textarea id="messageInput" class="chat-input" placeholder="Ketik pesan... (@ untuk mention)" rows="1" onkeydown="handleKeydown(event)" oninput="autoResize(this); handleMentionInput(this)"></textarea>
          <button class="btn-send" id="sendBtn" onclick="sendMessage()"><i class="bi bi-send-fill"></i></button>
        </div>
        <div class="chat-input-hint">Tekan <b>Enter</b> kirim &bull; <b>Shift+Enter</b> baris baru &bull; <b>@</b> mention &bull; <b>Ctrl+V</b> paste screenshot</div>
        </div>
      <?php elseif ($chat['status'] !== 'done'): ?>
        <div class="chat-input-wrap">
          <button class="btn btn-link p-0 text-muted fs-5" onclick="document.getElementById('fileInput').click()"><i class="bi bi-paperclip"></i></button>
          <input type="file" id="fileInput" style="display:none" onchange="handleFileUpload(this)">
          <textarea id="messageInput" class="chat-input" placeholder="Ketik pesan..." rows="1" onkeydown="handleKeydown(event)" oninput="autoResize(this)"></textarea>
          <button class="btn-send" id="sendBtn" onclick="sendMessage()"><i class="bi bi-send-fill"></i></button>
        </div>
        <div class="chat-input-hint">Tekan <b>Enter</b> kirim &bull; <b>Shift+Enter</b> baris baru &bull; <b>Ctrl+V</b> paste screenshot</div>
      <?php else: ?>
        <div class="chat-closed-bar"><i class="bi bi-check-circle-fill me-2"></i>Layanan telah selesai.</div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<style>
/* Zoom Profile Picture Modal Style */
.zoomable-avatar {
  cursor: pointer;
  position: relative;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.zoomable-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 24px rgba(0,0,0,0.18) !important;
}
.zoomable-avatar::after {
  content: '\F648'; /* bi-zoom-in */
  font-family: "bootstrap-icons";
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.35);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  opacity: 0;
  border-radius: 50%;
  transition: opacity 0.2s ease;
}
.zoomable-avatar:hover::after {
  opacity: 1;
}
.btn-close-zoom {
  position: absolute;
  top: -48px;
  right: 12px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 1060;
  transition: all 0.2s ease;
}
.btn-close-zoom:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
  color: #fff;
}
.zoom-img-wrap {
  width: 100%;
  max-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.35);
  background: rgba(0,0,0,0.05);
}
.zoom-img-content {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  border-radius: 20px;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.zoom-img-caption {
  color: rgba(255, 255, 255, 0.95);
  font-size: 16px;
  font-weight: 700;
  margin-top: 16px;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
  letter-spacing: 0.5px;
}

.hover-light:hover {
  background: #f8fafc;
}
.fs-9 {
  font-size: 9px !important;
}
.py-05 {
  padding-top: 0.125rem !important;
  padding-bottom: 0.125rem !important;
}
.group-participants-trigger {
  transition: opacity 0.2s;
}
.group-participants-trigger:hover {
  opacity: 0.8;
}

/* ── Message action buttons (reply + pin) ── */
.msg-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  flex-wrap: nowrap;
}
.msg-actions--standalone {
  justify-content: flex-end;
}
.msg-actions--standalone .btn-pin-msg {
  opacity: 1;
}
.btn-reply-msg,
.btn-pin-msg {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  padding: 0;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s, color 0.2s, background 0.2s;
  flex-shrink: 0;
  line-height: 1;
  background: transparent;
}
.btn-reply-msg {
  color: rgba(255,255,255,0.65);
}
.bubble-other .btn-reply-msg {
  color: rgba(100,116,139,0.75);
}
.btn-pin-msg {
  color: rgba(100,116,139,0.75);
}
.bubble-me .btn-pin-msg {
  color: rgba(255,255,255,0.75);
}
.msg-bubble:hover .msg-actions .btn-reply-msg,
.msg-bubble:hover .msg-actions .btn-pin-msg,
.msg-actions--standalone:hover .btn-pin-msg,
.msg-actions--standalone .btn-pin-msg {
  opacity: 1;
}
.btn-reply-msg:hover {
  color: #4f46e5 !important;
  background: rgba(79, 70, 229, 0.12);
  opacity: 1 !important;
}
.btn-pin-msg:hover {
  color: #4f46e5 !important;
  background: rgba(79, 70, 229, 0.12);
  opacity: 1 !important;
}
.btn-pin-msg .bi-pin-angle-fill {
  color: #ca8a04;
}
.msg-body-row {
  width: 100%;
}

/* ── Reply Preview inside Bubble (high contrast vs parent bubble) ── */
.bubble-me .reply-preview {
  background: rgba(255, 255, 255, 0.18);
  border-left: 3px solid #fde68a;
  border-radius: 8px;
  padding: 6px 10px;
  margin-bottom: 8px;
  cursor: pointer;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
}
.bubble-me .reply-preview:hover {
  background: rgba(255, 255, 255, 0.26);
}
.bubble-other .reply-preview {
  background: #e2e8f0;
  border-left: 3px solid #4f46e5;
  border-radius: 8px;
  padding: 6px 10px;
  margin-bottom: 8px;
  cursor: pointer;
  box-shadow: inset 0 0 0 1px #cbd5e1;
}
.bubble-other .reply-preview:hover {
  background: #cbd5e1;
}
.reply-preview-sender {
  font-size: 10px;
  font-weight: 800;
  margin-bottom: 2px;
}
.bubble-me .reply-preview-sender { color: #fde68a; }
.bubble-other .reply-preview-sender { color: #4338ca; }
.reply-preview-text {
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 220px;
}
.bubble-me .reply-preview-text { color: rgba(255, 255, 255, 0.95); }
.bubble-other .reply-preview-text { color: #334155; }

/* ── Reply Bar (above input) ── */
.reply-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 14px;
  background: #f0f4ff;
  border-top: 1px solid #e0e7ff;
  border-bottom: 1px solid #e0e7ff;
  gap: 8px;
  animation: slideUp 0.15s ease-out;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}
.reply-bar-inner {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 0;
}
.reply-bar-content { min-width: 0; }
.reply-bar-name {
  font-size: 11px;
  font-weight: 800;
  color: #4f46e5;
}
.reply-bar-text {
  font-size: 11px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.reply-bar-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 13px;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  transition: color 0.15s;
  flex-shrink: 0;
}
.reply-bar-close:hover { color: #ef4444; }

.chat-input-composer {
  position: relative;
}
.chat-input-area--group {
  position: relative;
}

/* ── Mention Dropdown ── */
.mention-dropdown {
  position: absolute;
  bottom: calc(100% - 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
  margin: 0 8px 4px;
  animation: slideUp 0.15s ease-out;
}
.mention-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}
.mention-item:last-child { border-bottom: 0; }
.mention-item:hover, .mention-item.active { background: #eff6ff; }
.mention-avatar {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 10px; font-weight: 700;
  flex-shrink: 0; overflow: hidden;
}
.mention-name { font-size: 12px; font-weight: 700; color: #1e293b; }
.mention-username { font-size: 10px; color: #94a3b8; }

/* ── Mention Highlight in messages ── */
.mention-highlight {
  color: #4f46e5;
  font-weight: 700;
  background: #eff6ff;
  border-radius: 4px;
  padding: 0 3px;
}
.bubble-me .mention-highlight {
  color: #c7d2fe;
  background: rgba(255,255,255,0.15);
}

/* ── Highlighted message (scroll target) ── */
.msg-bubble.highlight-flash {
  animation: flashHighlight 1.2s ease-out;
}
@keyframes flashHighlight {
  0%   { box-shadow: 0 0 0 3px rgba(79,70,229,0.5); }
  100% { box-shadow: none; }
}
</style>

<script>
let groupInfoOffcanvas = null;
function openGroupInfo() {
  if (!groupInfoOffcanvas) {
    groupInfoOffcanvas = new bootstrap.Offcanvas(document.getElementById('groupInfoOffcanvas'));
  }
  groupInfoOffcanvas.show();
}

function zoomProfilePicture(imgUrl, caption) {
  const modalImg = document.getElementById('zoomModalImage');
  const modalCap = document.getElementById('zoomModalCaption');
  if (!modalImg || !modalCap) return;
  
  modalImg.src = imgUrl;
  modalCap.textContent = caption;
  
  const zoomModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
  zoomModal.show();
}

// ── Reply ──────────────────────────────────────────────────
let replyToId   = null;
let replyToName = '';

function startReply(msgId, senderName, previewText) {
  replyToId   = msgId;
  replyToName = senderName;
  const bar = document.getElementById('replyBar');
  const nameEl = document.getElementById('replyBarName');
  const textEl = document.getElementById('replyBarText');
  if (!bar || !nameEl || !textEl) return;
  nameEl.textContent = senderName;
  textEl.textContent = previewText;
  bar.style.display = 'flex';
  const input = document.getElementById('messageInput');
  if (input) input.focus();
}

function cancelReply() {
  replyToId   = null;
  replyToName = '';
  const bar = document.getElementById('replyBar');
  if (bar) bar.style.display = 'none';
}

function scrollToMessage(msgId) {
  const bubble = document.querySelector(`.msg-bubble[data-msg-id="${msgId}"]`);
  if (!bubble) return;
  bubble.scrollIntoView({ behavior: 'smooth', block: 'center' });
  bubble.classList.add('highlight-flash');
  setTimeout(() => bubble.classList.remove('highlight-flash'), 1300);
}


// ── Paste Screenshot (Ctrl+V) → reuse app.js handleFileUpload ──
document.addEventListener('paste', function(e) {
  // Hanya aktif jika ada chat yang terbuka
  if (typeof APP === 'undefined' || !APP.activeChatId) return;

  const items = (e.clipboardData || e.originalEvent.clipboardData)?.items;
  if (!items) return;

  for (const item of items) {
    if (item.type.indexOf('image') === -1) continue;
    e.preventDefault();
    const blob = item.getAsFile();
    if (!blob) continue;

    // Buat nama file screenshot dengan timestamp
    const ext = blob.type.split('/')[1] === 'jpeg' ? 'jpg' : (blob.type.split('/')[1] || 'png');
    const fileName = `screenshot-${Date.now()}.${ext}`;
    const file = new File([blob], fileName, { type: blob.type });

    // Gunakan variabel & fungsi dari app.js (sudah include CSRF)
    selectedFileForUpload = file;

    // Setup preview di modal (sama seperti handleFileUpload di app.js)
    const previewContainer = document.getElementById('filePreviewContainer');
    if (previewContainer) {
      previewContainer.innerHTML = '';
      const reader = new FileReader();
      reader.onload = function(ev) {
        previewContainer.innerHTML = `<img src="${ev.target.result}" style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.15);">`;
      };
      reader.readAsDataURL(file);
    }

    // Tampilkan modal preview
    const previewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
    previewModal.show();
    break;
  }
});


</script>

<?php if ($isGroup): ?>
<!-- Offcanvas: Group Info -->
<div class="offcanvas offcanvas-end shadow" tabindex="-1" id="groupInfoOffcanvas" aria-labelledby="groupInfoOffcanvasLabel" style="width: 380px; border-left: none;">
  <div class="offcanvas-header bg-white border-bottom px-4 py-3">
    <h6 class="offcanvas-title fw-bold text-dark" id="groupInfoOffcanvasLabel">Info Grup</h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0" style="background-color: #f0f2f5;">
    
    <!-- Group Overview -->
    <div class="text-center bg-white px-4 py-4 mb-2 shadow-sm">
      <?php $grpIcon = !empty($chat['icon_path']) ? htmlspecialchars($chat['icon_path']) : ''; ?>
      <div class="mx-auto mb-3 <?= $grpIcon ? 'zoomable-avatar' : '' ?>" 
           style="width: 140px; height: 140px; border-radius: 50%; background: <?= $grpIcon ? 'none' : 'linear-gradient(135deg, #4f46e5, #7c3aed)' ?>; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(79,70,229,0.2); overflow: hidden;"
           <?= $grpIcon ? 'onclick="zoomProfilePicture(\'' . $grpIcon . '\', \'' . htmlspecialchars(addslashes($chat['title'] ?? ''), ENT_QUOTES) . '\')"' : '' ?>>
        <?php if ($grpIcon): ?>
          <img src="<?= $grpIcon ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
        <?php else: ?>
          <i class="bi bi-people-fill text-white" style="font-size: 64px;"></i>
        <?php endif; ?>
      </div>
      <h4 class="fw-bold text-dark mb-1" style="font-size: 22px;"><?= htmlspecialchars($chat['title'] ?? '') ?></h4>
      <div class="text-muted" style="font-size: 14px;">Grup • <?= count($chat['participants'] ?? []) ?> Peserta</div>
    </div>
    
    <!-- Description -->
    <?php if (!empty($chat['description'])): ?>
    <div class="bg-white px-4 py-3 mb-2 shadow-sm">
      <div class="text-success fw-bold mb-1" style="font-size: 13px;">Keterangan</div>
      <div class="text-dark" style="font-size: 14px; line-height: 1.5;">
        <?= nl2br(htmlspecialchars($chat['description'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Participants List -->
    <div class="bg-white shadow-sm mb-4">
      <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <span class="text-success fw-bold" style="font-size: 13px;"><?= count($chat['participants'] ?? []) ?> Peserta</span>
      </div>
      <div class="participants-list" style="max-height: 400px; overflow-y: auto;">
        <?php foreach ($chat['participants'] ?? [] as $p): ?>
          <?php 
            $pImg = User::getAvatarUrl($p['username'], $p['name'], (int)($p['sil_id'] ?? 0));
            $isMe = (int)$p['id'] === (int)$user['id'];
            $roleBadge = '';
            if ($p['role'] === 'admin') {
                $roleBadge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-05" style="font-size: 10px;">Admin</span>';
            } elseif ($p['role'] === 'teknisi') {
                $roleBadge = '<span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-05" style="font-size: 10px;">Tim IT</span>';
            }
          ?>
          <div class="d-flex align-items-center justify-content-between px-4 py-3 hover-light border-bottom" style="cursor: pointer; transition: background 0.2s;">
            <div class="d-flex align-items-center gap-3">
              <div class="<?= $pImg ? 'zoomable-avatar' : '' ?>" 
                   style="background:<?= $pImg ? 'none' : avatarColor((int)$p['id']) ?>; width: 44px; height: 44px; font-weight: 700; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; position: relative; flex-shrink: 0; overflow: hidden;"
                   <?= $pImg ? 'onclick="zoomProfilePicture(\'' . $pImg . '\', \'' . htmlspecialchars(addslashes($p['name'] ?? ''), ENT_QUOTES) . '\')"' : '' ?>>
                <?php if ($pImg): ?>
                  <img src="<?= $pImg ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                  <span style="font-size: 16px;"><?= htmlspecialchars($p['avatar'] ?? User::getInitials($p['name'] ?? '')) ?></span>
                <?php endif; ?>
              </div>
              <div class="d-flex flex-column justify-content-center">
                <div class="fw-semibold text-dark text-truncate d-flex align-items-center gap-2" style="font-size: 15px; line-height: 1.2; margin-bottom: 3px;">
                  <?= htmlspecialchars($p['name'] ?? '') ?>
                  <?php if ($isMe): ?>
                    <span class="text-muted fw-normal" style="font-size: 13px;">(Anda)</span>
                  <?php endif; ?>
                </div>
                <div class="text-muted text-truncate" style="font-size: 13px; line-height: 1.2;">
                  @<?= htmlspecialchars($p['username'] ?? '') ?>
                </div>
              </div>
            </div>
            <?php if ($roleBadge): ?>
              <div class="ms-2">
                <?= $roleBadge ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- File Preview Modal (WhatsApp Style) -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; background: #e9edef;">
      <div class="modal-header border-0 py-3" style="background: #008069; color: white;">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-x-lg" data-bs-dismiss="modal" style="cursor: pointer; font-size: 20px; font-weight: bold;"></i>
          <h5 class="modal-title fs-5 fw-semibold mb-0" style="color: white;">Preview</h5>
        </div>
      </div>
      <div class="modal-body p-0 d-flex flex-column align-items-center justify-content-center" style="min-height: 400px; max-height: 80vh; background: #e9edef; overflow: hidden;">
        <!-- Container Preview -->
        <div id="filePreviewContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-3" style="overflow: auto;">
          <!-- Injected by JS -->
        </div>
      </div>
      <div class="modal-footer border-0 p-3" style="background: #f0f2f5; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-success rounded-circle shadow" id="btnConfirmUpload" onclick="confirmFileUpload()" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; background: #00a884; border: none; transition: transform 0.2s;">
          <i class="bi bi-send-fill" style="font-size: 24px; margin-left: 2px;"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Zoom Foto Profil (WhatsApp Style) -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content border-0 bg-transparent shadow-none text-center position-relative">
      <button type="button" class="btn-close-zoom" data-bs-dismiss="modal" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="zoom-img-wrap">
        <img id="zoomModalImage" src="" alt="Profile Preview" class="zoom-img-content">
      </div>
      <div id="zoomModalCaption" class="zoom-img-caption"></div>
    </div>
  </div>
</div>
