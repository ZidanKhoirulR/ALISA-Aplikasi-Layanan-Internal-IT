<?php
// ============================================
// app/controllers/CalendarController.php
// ============================================

class CalendarController
{
    private function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // GET /api/calendar/events?start=...&end=...
    public function getEvents(\Base $f3): void
    {
        Security::checkAuth($f3);
        $user = Security::getCurrentUser($f3);
        if (!$user) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $start = $f3->get('GET.start');
        $end   = $f3->get('GET.end');

        if (!$start || !$end) {
            $this->json(['error' => 'Parameter start dan end wajib ada.'], 422);
            return;
        }

        // Jadwal Zoom: semua user login boleh lihat (ketersediaan ruangan rapat bersama)
        $role = strtolower((string)($user['role'] ?? 'user'));
        $userId = (int)$user['id'];
        $taskScopeSql = '';
        $taskScopeParams = [];

        if ($role === 'user') {
            $taskScopeSql = ' AND (
                c.user_id = ?
                OR EXISTS (
                    SELECT 1 FROM group_participants gp
                    WHERE gp.chat_id = c.id AND gp.user_id = ?
                )
            )';
            $taskScopeParams = [$userId, $userId];
        } elseif ($role === 'teknisi') {
            $taskScopeSql = " AND (
                c.type = 'group'
                OR c.status = 'waiting'
                OR c.teknisi_id = ?
            )";
            $taskScopeParams = [$userId];
        }

        $events = [];

        // 1) Zoom schedules — publik untuk semua pegawai (tanpa filter pemilik chat)
        $zoomSchedules = [];
        if (DB::tableExists('zoom_schedules')) {
            $zoomSchedules = DB::fetchAll(" 
                SELECT
                    CONCAT('zoom_', z.id) AS id,
                    CONCAT(COALESCE(NULLIF(z.ruangan, ''), 'Virtual'), ' - ', COALESCE(NULLIF(z.nama_kegiatan, ''), 'Zoom Meeting')) AS title,
                    z.waktu_mulai         AS `start`,
                    z.waktu_selesai       AS `end`,
                    CASE
                        WHEN z.ruangan = 'RAYON'    THEN '#3b82f6'
                        WHEN z.ruangan = 'SELULOSA' THEN '#8b5cf6'
                        WHEN z.ruangan = 'KERTAS'   THEN '#f59e0b'
                        WHEN z.ruangan = 'PINUS'    THEN '#06b6d4'
                        ELSE '#6366f1'
                    END                   AS color,
                    'zoom'                AS type,
                    z.ruangan,
                    z.nama_kegiatan,
                    z.butuh_link,
                    z.request_type,
                    z.waktu_mulai         AS waktu_mulai_orig,
                    z.waktu_selesai       AS waktu_selesai_orig,
                    c.status              AS chat_status
                FROM zoom_schedules z
                LEFT JOIN chats c ON c.id = z.chat_id
                WHERE (z.waktu_mulai < ? AND z.waktu_selesai > ?)
                  AND COALESCE(c.category, '') <> 'Pendampingan IT'
                  AND z.request_type IN ('link_tech', 'own_link')
                ORDER BY z.waktu_mulai ASC
            ", [$end, $start]);
        }

        foreach ($zoomSchedules as $z) {
            $events[] = [
                'id'            => $z['id'],
                'title'         => $z['title'],
                'start'         => $z['start'],
                'end'           => $z['end'],
                'color'         => $z['color'],
                'extendedProps' => [
                    'type'          => 'zoom',
                    'ruangan'       => $z['ruangan'],
                    'nama_kegiatan' => $z['nama_kegiatan'],
                    'butuh_link'    => (bool)($z['butuh_link'] ?? 0),
                    'request_type'  => $z['request_type'] ?? null,
                    'waktu_mulai'   => $z['waktu_mulai_orig'] ?? null,
                    'waktu_selesai' => $z['waktu_selesai_orig'] ?? null,
                    'chat_status'   => $z['chat_status'] ?? 'waiting',
                ]
            ];
        }

        // 2) Active IT tasks (Dihapus sesuai permintaan, kalender hanya untuk zoom)
        
        $this->json($events);
    }
}
