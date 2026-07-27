<?php
/**
 * Автогенерация отзывов через YandexGPT
 * Запуск: php cron/review-cron.php [количество]
 * Пример: php cron/review-cron.php 3
 */
require_once __DIR__ . '/../config.php';

$count = (int)($argv[1] ?? $_ENV['REVIEW_COUNT'] ?? 2);

$maleNames = ['Александр','Дмитрий','Максим','Иван','Артём','Андрей','Михаил','Сергей','Николай','Евгений','Алексей','Владимир','Денис','Кирилл','Роман','Олег','Павел','Виктор','Антон','Игорь','Руслан','Тимур','Юрий','Егор'];
$femaleNames = ['Анна','Мария','Елена','Ольга','Наталья','Татьяна','Ирина','Светлана','Екатерина','Юлия','Дарья','Алина','Марина','Оксана','Виктория','Полина'];
$maleFB = ['Срочно нужны были деньги, оформил за пару минут. Доволен.','Подавал заявку вечером, одобрили сразу.','Коллега посоветовал, попробовал. Удобно.','Не хватало до зарплаты, выручили.','Второй раз обращаюсь, всё нормально.'];
$femaleFB = ['Подала заявку с телефона, деньги пришли быстро. Довольна.','Подруга посоветовала, одобрили быстро.','Нужны были деньги, оформила за 10 минут.','Первый раз брала займ, всё просто оказалось.','Удобно что всё онлайн.'];
$situations = ['срочно понадобились деньги до зарплаты','нужно было оплатить ремонт','неожиданные расходы','надо было оплатить коммуналку','нашёл через интернет','коллега порекомендовал','увидел рекламу','подруга посоветовала','нужно было купить лекарства','не дотягивал до аванса'];
$styles = ['коротко, 2 предложения','эмоционально, 3 предложения','спокойно, 2-3 предложения','с деталями, 3 предложения','как другу, 2-3 предложения'];

function weightedRating(): int {
    $w = [1,2,5,25,67]; $r = mt_rand(0,99);
    foreach ($w as $i => $v) { $r -= $v; if ($r < 0) return $i + 1; }
    return 5;
}

function rnd(array $arr) { return $arr[array_rand($arr)]; }

$db = getDB();
$offers = $db->query("SELECT id, title FROM offers WHERE is_active = 1")->fetchAll();
if (!$offers) { echo "[ERROR] Нет активных офферов\n"; exit(1); }

echo "[START] Генерация $count отзывов\n";

for ($i = 0; $i < $count; $i++) {
    $offer = rnd($offers);
    $gender = mt_rand(0,1) ? 'male' : 'female';
    $name = $gender === 'male' ? rnd($maleNames) : rnd($femaleNames);
    $rating = weightedRating();
    $comment = null;

    if (YANDEX_GPT_API_KEY && YANDEX_FOLDER_ID) {
        $mood = $rating >= 4 ? 'положительный' : ($rating === 3 ? 'нейтральный' : 'негативный');
        $genderRu = $gender === 'male' ? 'мужчина' : 'женщина';
        $genderVerb = $gender === 'male' ? 'Используй мужской род: оформил, получил, доволен' : 'Используй женский род: оформила, получила, довольна';
        $situation = rnd($situations);
        $style = rnd($styles);

        $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
                'content' => json_encode([
                    'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt-lite/latest',
                    'completionOptions' => ['stream' => false, 'temperature' => 0.9, 'maxTokens' => 150],
                    'messages' => [
                        ['role' => 'system', 'text' => "Ты обычный $genderRu из России. $genderVerb. Пиши $style. Ситуация: $situation. НЕ начинай с названия сервиса. Без markdown."],
                        ['role' => 'user', 'text' => "Напиши $mood отзыв на \"{$offer['title']}\". Оценка $rating из 5."],
                    ],
                ]),
                'timeout' => 15,
            ],
        ]));

        if ($response) {
            $data = json_decode($response, true);
            $text = $data['result']['alternatives'][0]['message']['text'] ?? null;
            if ($text) {
                $comment = preg_replace('/\*/', '', $text);
                $comment = preg_replace('/^["«]|["»]$/', '', trim($comment));
                $comment = trim($comment);
                if (mb_strlen($comment) < 20) $comment = null;
            }
        }
    }

    if (!$comment) {
        $comment = $gender === 'male' ? rnd($maleFB) : rnd($femaleFB);
    }

    $db->prepare("INSERT INTO reviews (offer_id, author_name, rating, comment, is_approved) VALUES (?,?,?,?,1)")
       ->execute([$offer['id'], $name, $rating, $comment]);

    $db->prepare("UPDATE offers SET rating = (SELECT COALESCE(ROUND(AVG(r.rating),1),0) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1), review_count = (SELECT COUNT(*) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1) WHERE id = ?")
       ->execute([$offer['id'], $offer['id'], $offer['id']]);

    $g = $gender === 'male' ? 'М' : 'Ж';
    echo "[OK] $name ($g) -> {$offer['title']} ($rating/5)\n";
}

echo "[DONE] Отзывы созданы\n";
