<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
require_once __DIR__ . '/../../includes/ai-providers.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$targetOfferId = (int)($data['offerId'] ?? 0);

$maleNames = ['Александр','Дмитрий','Максим','Иван','Артём','Андрей','Михаил','Сергей','Николай','Евгений','Алексей','Владимир','Денис','Кирилл','Роман','Олег','Павел','Виктор','Антон','Игорь'];
$femaleNames = ['Анна','Мария','Елена','Ольга','Наталья','Татьяна','Ирина','Светлана','Екатерина','Юлия','Дарья','Алина','Марина','Оксана','Виктория','Полина'];
$maleFallbacks = ['Срочно нужны были деньги, оформил за пару минут. Перевели быстро, доволен.','Подавал заявку вечером, одобрили сразу. Нормальный сервис.','Брал первый раз, переживал. Но всё нормально.','Коллега посоветовал, попробовал. Удобно.','Не хватало до зарплаты, выручили. Погасил вовремя.'];
$femaleFallbacks = ['Подала заявку с телефона, деньги пришли быстро. Довольна.','Подруга посоветовала, решила попробовать. Одобрили быстро.','Нужны были деньги на лечение, оформила за 10 минут. Спасибо.','Первый раз брала займ, волновалась. Но всё просто.','Удобно что всё онлайн. Одобрили за минуты.'];

$situations = ['срочно понадобились деньги до зарплаты','нужно было оплатить ремонт','неожиданные расходы на лечение','надо было срочно оплатить коммуналку','нашёл через интернет','коллега порекомендовал','увидел рекламу'];
$styles = ['коротко, 2 предложения','эмоционально, 3 предложения','спокойно, 2-3 предложения','с деталями, 3 предложения'];

function weightedRating(): int {
    $w = [1,2,5,25,67]; $r = mt_rand(0,99);
    foreach ($w as $i => $v) { $r -= $v; if ($r < 0) return $i + 1; }
    return 5;
}

$db = getDB();
if ($targetOfferId > 0) {
    $stmt = $db->prepare("SELECT id, title FROM offers WHERE is_active = 1 AND id = ? LIMIT 1");
    $stmt->execute([$targetOfferId]);
    $offer = $stmt->fetch();
    if (!$offer) { echo json_encode(['error' => 'Оффер не найден']); exit; }
} else {
    $offers = $db->query("SELECT id, title FROM offers WHERE is_active = 1")->fetchAll();
    if (!$offers) { echo json_encode(['error' => 'Нет офферов']); exit; }
    $offer = $offers[array_rand($offers)];
}

$gender = mt_rand(0,1) ? 'male' : 'female';
$name = $gender === 'male' ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
$rating = weightedRating();
$comment = null;
$aiProvider = 'fallback';

// Используем unified AI providers
$mood = $rating >= 4 ? 'положительный' : ($rating === 3 ? 'нейтральный' : 'негативный');
$genderRu = $gender === 'male' ? 'мужчина' : 'женщина';
$genderVerb = $gender === 'male' ? 'Используй мужской род: оформил, получил, доволен' : 'Используй женский род: оформила, получила, довольна';
$situation = $situations[array_rand($situations)];
$style = $styles[array_rand($styles)];

$systemPrompt = "Ты обычный $genderRu из России. $genderVerb. Пиши $style. Ситуация: $situation. НЕ начинай с названия сервиса. Без markdown.";
$userPrompt = "Напиши $mood отзыв на \"{$offer['title']}\". Оценка $rating из 5.";

$result = aiGenerateText($userPrompt, $systemPrompt);
if ($result['success'] && !empty($result['text'])) {
    $comment = preg_replace('/\*/', '', $result['text']);
    $comment = preg_replace('/^["«]|["»]$/', '', trim($comment));
    $comment = trim($comment);
    $aiProvider = ($result['provider'] ?? 'unknown') . ' (' . ($result['model'] ?? '') . ')';
}

if (!$comment) {
    $comment = $gender === 'male' ? $maleFallbacks[array_rand($maleFallbacks)] : $femaleFallbacks[array_rand($femaleFallbacks)];
}

$reviewTextColumn = function_exists('dbFirstExistingColumn') ? dbFirstExistingColumn('reviews', ['comment', 'text']) : 'comment';
$db->prepare("INSERT INTO reviews (offer_id, author_name, rating, {$reviewTextColumn}, is_approved) VALUES (?,?,?,?,1)")
   ->execute([$offer['id'], $name, $rating, $comment]);

$db->prepare("UPDATE offers SET rating = (SELECT COALESCE(ROUND(AVG(r.rating),1),0) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1), review_count = (SELECT COUNT(*) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1) WHERE id = ?")
   ->execute([$offer['id'], $offer['id'], $offer['id']]);

echo json_encode([
    'success' => true,
    'review' => ['offer' => $offer['title'], 'name' => $name, 'rating' => $rating, 'comment' => $comment],
    'ai_provider' => $aiProvider,
]);
