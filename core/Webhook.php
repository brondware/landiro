<?php
class Webhook {
    public static function fire(string $url, array $payload): bool {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ctx  = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\nUser-Agent: Landiro CMS-Webhook/1.0",
            'content'       => $body,
            'timeout'       => 5,
            'ignore_errors' => true,
        ]]);
        return @file_get_contents($url, false, $ctx) !== false;
    }

    public static function sendOrder(string $landingTitle, string $slug, array $order, string $webhookUrl = ''): bool {
        // Landing-specific URL takes priority, fallback to global
        $url = $webhookUrl ?: Settings::get('webhook_url', '');
        if (!$url) return false;

        return self::fire($url, [
            'event'      => 'new_order',
            'landing'    => $slug,
            'landing_name' => $landingTitle,
            'order_id'   => $order['id']   ?? '',
            'created_at' => $order['created_at'] ?? date('c'),
            'fields'     => $order['data'] ?? [],
            'utms'       => $order['utms'] ?? null,
            'ab_variant' => $order['ab_variant'] ?? null,
            'ip'         => $order['ip'] ?? '',
        ]);
    }
}
