<?php
/**
 * Перегенерация обложки статьи через AI
 * POST: { articleId: number, prompt?: string }
 */
require_once __DIR__ . '/../../includes/ai-providers.php';
require_once __DIR__ . '/../../includes/article-image.php';

header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$articleId = (int)($data['articleId'] ?? 0);
$customPrompt = trim((string)($data['prompt'] ?? ''));

if (!$articleId) {
    http_response_code(400);
    echo json_encode(['error' => 'articleId required']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, title, cover_image FROM articles WHERE id = ?");
$stmt->execute([$articleId]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    echo json_encode(['error' => 'Статья не найдена']);
    exit;
}

// Формируем промпт
$prompt = $customPrompt ?: buildArticleImagePrompt($article['title']);

// Генерируем через unified AI providers
$result = aiGenerateImage($prompt);

if ($result['success'] && !empty($result['path'])) {
    // Обновляем статью
    $db->prepare("UPDATE articles SET cover_image = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$result['path'], $articleId]);
    
    echo json_encode([
        'success' => true,
        'path' => $result['path'],
        'provider' => ($result['provider'] ?? '') . (isset($result['model']) ? ' (' . $result['model'] . ')' : ''),
        'prompt' => $prompt,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? 'Не удалось сгенерировать изображение',
    ]);
}
