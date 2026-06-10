<?php
/**
 * Order Form Handler
 * Налаштуйте обробник через PHP режим в редакторі або вручну тут.
 * Функція handleOrderForm() викликається автоматично при POST запиті з заголовком X-CMS-Form: 1
 */

function handleOrderForm(array $data): array {
    // Базова валідація
    if (empty($data['name']) || empty($data['phone'])) {
        return ['success' => false, 'message' => "Заповніть всі поля"];
    }

    $name = htmlspecialchars(trim($data['name']));
    $phone = preg_replace('/[^0-9+]/', '', $data['phone']);

    // ── Відправка на email ────────────────────────────────────────
    // $to = 'your@email.com';
    // $subject = 'Нове замовлення';
    // $body = "Ім'я: {$name}\nТелефон: {$phone}\nЧас: " . date('d.m.Y H:i');
    // mail($to, $subject, $body, 'From: noreply@' . $_SERVER['HTTP_HOST']);

    // ── Відправка в Telegram ──────────────────────────────────────
    // $token = 'YOUR_BOT_TOKEN';
    // $chatId = 'YOUR_CHAT_ID';
    // $text = "📦 *Нове замовлення*\n*Ім'я:* {$name}\n*Телефон:* {$phone}";
    // $url = "https://api.telegram.org/bot{$token}/sendMessage";
    // file_get_contents($url . '?' . http_build_query(['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']));

    // ── Webhook/CRM ───────────────────────────────────────────────
    // $webhookUrl = 'https://your-crm.com/api/leads';
    // $ch = curl_init($webhookUrl);
    // curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['name' => $name, 'phone' => $phone]), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true]);
    // curl_exec($ch); curl_close($ch);

    return ['success' => true, 'message' => 'Дякуємо! Ми зателефонуємо протягом 5 хвилин.'];
}
