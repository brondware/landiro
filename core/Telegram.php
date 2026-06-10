<?php
class Telegram {
    private string $token;
    private string $chatId;

    public function __construct(string $token, string $chatId) {
        $this->token  = trim($token);
        $this->chatId = trim($chatId);
    }

    public static function fromSettings(): self {
        return new self(
            Settings::get('telegram_token', ''),
            Settings::get('telegram_chat_id', '')
        );
    }

    public function isConfigured(): bool {
        return $this->token !== '' && $this->chatId !== '';
    }

    public function sendOrder(string $landingTitle, string $landingSlug, array $order): bool {
        if (!$this->isConfigured()) return false;

        $lines = [
            '🛒 <b>Нове замовлення!</b>',
            '<b>Лендинг:</b> ' . htmlspecialchars($landingTitle),
            '<b>Дата:</b> ' . date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')),
            '',
        ];

        foreach ($order['data'] ?? [] as $key => $value) {
            if ((string)$value === '' || str_starts_with($key, '_')) continue;
            $lines[] = '<b>' . htmlspecialchars(ucfirst(str_replace(['_', '-'], ' ', $key))) . ':</b> ' . htmlspecialchars($value);
        }

        if (!empty($order['utms'])) {
            $lines[] = '';
            $utmStr = implode(' · ', array_map(
                fn($k, $v) => str_replace('utm_', '', $k) . ': ' . htmlspecialchars($v),
                array_keys($order['utms']),
                $order['utms']
            ));
            $lines[] = '📊 <i>' . $utmStr . '</i>';
        }

        if (!empty($order['ab_variant'])) {
            $lines[] = '🧪 <i>A/B варіант: ' . htmlspecialchars($order['ab_variant']) . '</i>';
        }

        return $this->send(implode("\n", $lines), 'HTML');
    }

    public function testSend(): bool {
        return $this->send('✅ <b>Landiro CMS</b> підключено! Сповіщення про замовлення будуть приходити сюди.', 'HTML');
    }

    public function send(string $text, string $parseMode = ''): bool {
        if (!$this->isConfigured()) return false;

        $payload = ['chat_id' => $this->chatId, 'text' => $text, 'disable_web_page_preview' => true];
        if ($parseMode) $payload['parse_mode'] = $parseMode;

        $body = json_encode($payload);
        $ctx  = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($body),
            'content'       => $body,
            'timeout'       => 5,
            'ignore_errors' => true,
        ]]);

        $result = @file_get_contents(
            "https://api.telegram.org/bot{$this->token}/sendMessage",
            false, $ctx
        );
        if ($result === false) return false;
        $data = json_decode($result, true);
        return (bool)($data['ok'] ?? false);
    }
}
