<?php
class Mailer {
    public static function sendOrder(string $landingTitle, array $order): bool {
        $to = Settings::get('email_to', '');
        if (!$to || !Settings::get('email_enabled')) return false;

        $subject = 'Нове замовлення: ' . $landingTitle;
        $date    = date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now'));

        $body  = "Нова заявка з лендингу!\n";
        $body .= "Лендинг: {$landingTitle}\n";
        $body .= "Дата: {$date}\n";
        $body .= str_repeat('-', 40) . "\n";

        foreach ($order['data'] ?? [] as $key => $val) {
            if ((string)$val === '' || str_starts_with($key, '_')) continue;
            $body .= ucfirst(str_replace(['_', '-'], ' ', $key)) . ': ' . $val . "\n";
        }

        if (!empty($order['utms'])) {
            $body .= str_repeat('-', 40) . "\n";
            foreach ($order['utms'] as $k => $v) {
                $body .= str_replace('utm_', '', $k) . ': ' . $v . "\n";
            }
        }

        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $from    = 'noreply@' . $host;
        $headers = implode("\r\n", [
            'From: Landiro CMS <' . $from . '>',
            'Reply-To: ' . $from,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
            'X-Mailer: Landiro CMS',
        ]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $encodedSubject, $body, $headers);
    }
}
