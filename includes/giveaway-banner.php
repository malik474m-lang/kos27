<?php
/**
 * Плашка активного розыгрыша — вставляется в layout
 */
function renderGiveawayBanner(): string {
    try {
        $db = getDB();
        $db->query("SELECT 1 FROM giveaways LIMIT 1");
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT * FROM giveaways WHERE status IN ('active','drawing') AND start_at <= ? AND end_at >= ? LIMIT 1");
        $stmt->execute([$now, $now]);
        $gw = $stmt->fetch();
        if (!$gw) return '';

        $prize = number_format((float)$gw['prize_amount'], 0, '', ' ');
        $endDate = date('d.m.Y', strtotime($gw['end_at']));
        $drawDate = $gw['draw_at'] ? date('d.m.Y в H:i', strtotime($gw['draw_at'])) : $endDate;
        $title = htmlspecialchars($gw['title'], ENT_QUOTES, 'UTF-8');

        $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
        $cnt->execute([$gw['id']]);
        $participants = (int)$cnt->fetch()['cnt'];

        return '<div id="giveaway-banner" class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 text-white">'
             . '<div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm">'
             . '<div class="flex items-center gap-3">'
             . '<span class="text-2xl">🎁</span>'
             . '<div><strong>' . $title . '</strong>'
             . '<span class="opacity-90 ml-2">Призовой фонд: <strong>' . $prize . ' ₽</strong></span></div>'
             . '</div>'
             . '<div class="flex items-center gap-4 text-xs opacity-90">'
             . '<span>👥 ' . $participants . ' участников</span>'
             . '<span>📅 До ' . $endDate . '</span>'
             . '<span>🎬 Розыгрыш: ' . $drawDate . '</span>'
             . '</div>'
             . '</div></div>';
    } catch (Exception $e) {
        return '';
    }
}
