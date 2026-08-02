<?php
/**
 * Плашка активного розыгрыша — вставляется в layout
 */
function renderGiveawayBanner(): string {
    try {
        $db = getDB();
        $db->query("SELECT 1 FROM giveaways LIMIT 1");
        $stmt = $db->query("SELECT * FROM giveaways WHERE status IN ('active','drawing') ORDER BY created_at DESC LIMIT 1");
        $gw = $stmt->fetch();
        if (!$gw) return '';

        $prize = number_format((float)$gw['prize_amount'], 0, '', ' ');
        $endDate = $gw['end_at'] ? date('d.m.Y', strtotime($gw['end_at'])) : '—';
        $drawDate = $gw['draw_at'] ? date('d.m.Y в H:i', strtotime($gw['draw_at'])) : $endDate;
        $title = htmlspecialchars($gw['title'], ENT_QUOTES, 'UTF-8');

        $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
        $cnt->execute([$gw['id']]);
        $participants = (int)$cnt->fetch()['cnt'];

        return '<div id="giveaway-banner" style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%)">'
             . '<div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm">'
             . '<div class="flex items-center gap-3">'
             . '<span class="text-2xl">🎁</span>'
             . '<div style="color:#fff"><strong style="color:#ffd700">' . $title . '</strong>'
             . '<span style="color:#e0e0e0" class="ml-2">Призовой фонд: <strong style="color:#ffd700">' . $prize . ' ₽</strong></span></div>'
             . '</div>'
             . '<div class="flex items-center gap-4 text-xs" style="color:#ccc">'
             . '<span>👥 ' . $participants . ' участников</span>'
             . '<span>📅 До ' . $endDate . '</span>'
             . '<span>🎬 Розыгрыш: ' . $drawDate . '</span>'
             . '<a href="/giveaway" style="background:#ffd700;color:#1a1a2e;padding:4px 12px;border-radius:6px;font-weight:700;font-size:12px;text-decoration:none">Подробнее →</a>'
             . '</div>'
             . '</div></div>';
    } catch (Exception $e) {
        return '';
    }
}
