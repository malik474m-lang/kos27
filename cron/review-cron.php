<?php
/**
 * Автогенерация отзывов через YandexGPT
 * Запуск: php cron/review-cron.php [количество]
 * Пример: php cron/review-cron.php 3
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/page-cache.php';
register_shutdown_function('pageCacheClear');

$count = (int)($argv[1] ?? $_ENV['REVIEW_COUNT'] ?? 2);

$maleNames = ['Александр','Дмитрий','Максим','Иван','Артём','Андрей','Михаил','Сергей','Николай','Евгений','Алексей','Владимир','Денис','Кирилл','Роман','Олег','Павел','Виктор','Антон','Игорь','Руслан','Тимур','Юрий','Егор'];
$femaleNames = ['Анна','Мария','Елена','Ольга','Наталья','Татьяна','Ирина','Светлана','Екатерина','Юлия','Дарья','Алина','Марина','Оксана','Виктория','Полина'];
$maleFB = ['Срочно нужны были деньги до зарплаты, оформил займ за пару минут. Деньги пришли на карту быстро.','Подавал заявку на займ вечером, одобрили практически сразу. Сервис удобный.','Коллега посоветовал этот сервис для подбора займа. Попробовал — реально удобно сравнивать условия.','Не хватало до зарплаты, оформил микрозайм и через 10 минут деньги были на карте.','Второй раз оформляю через этот сервис. Всё прозрачно, условия понятные.'];
$femaleFB = ['Подала заявку на займ с телефона, деньги пришли на карту за 15 минут. Очень удобно.','Подруга посоветовала этот сервис для сравнения займов. Одобрили заявку быстро.','Нужны были деньги срочно, оформила микрозайм за 10 минут. Условия прозрачные.','Первый раз оформляла займ онлайн, переживала. Но всё оказалось просто и понятно.','Удобно что можно сравнить условия разных МФО и выбрать лучший вариант. Оформила за 5 минут.'];
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
    require_once __DIR__ . '/../includes/ai-compat.php';

    $mood = $rating >= 4 ? 'положительный' : ($rating === 3 ? 'нейтральный' : 'негативный');
    $genderRu = $gender === 'male' ? 'мужчина' : 'женщина';
    $genderVerb = $gender === 'male' ? 'Используй мужской род: оформил, получил, доволен' : 'Используй женский род: оформила, получила, довольна';
    $situation = rnd($situations);
    $style = rnd($styles);

    $systemPrompt = "Ты обычный $genderRu из России, который оформил финансовый продукт. $genderVerb. Пиши $style. Ситуация: $situation. НЕ начинай с названия сервиса. Без markdown.";
    $userPrompt = "Напиши $mood отзыв на \"{$offer['title']}\". Оценка $rating из 5.";
    $aiText = kosmozaimAIComplete($systemPrompt, $userPrompt);

    if ($aiText) {
        $comment = preg_replace('/\*\*(.+?)\*\*/', '$1', $aiText);
        $comment = preg_replace('/\*/', '', $comment);
        $comment = preg_replace('/^```\s*\w*\s*/i', '', $comment);
        $comment = preg_replace('/```/', '', $comment);
        $comment = preg_replace('/^#{1,6}\s+/m', '', $comment);
        $comment = preg_replace('/^["«]|["»]$/', '', trim($comment));
        $comment = trim($comment);
        if (mb_strlen($comment) < 20) $comment = null;
    }
                $comment = preg_replace('/^```\s*\w*\s*/i', '', $comment);
                $comment = preg_replace('/```/', '', $comment);
                $comment = preg_replace('/^#{1,6}\s+/m', '', $comment);
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
