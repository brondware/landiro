<?php
class LandingTemplates {

    public static function getAll(): array {
        return [
            ['id' => 'blank',   'name' => 'Порожній',  'desc' => 'Без секцій — починайте з нуля', 'icon' => '📄', 'sections_preview' => []],
            ['id' => 'product', 'name' => 'Товарка',   'desc' => 'Hero · Переваги · Ціна · Форма · Підвал', 'icon' => '🛍️', 'sections_preview' => ['hero','benefits','pricing','order-form','footer']],
            ['id' => 'service', 'name' => 'Послуга',   'desc' => 'Hero · Як це працює · Відгуки · Форма · Підвал', 'icon' => '⚡', 'sections_preview' => ['hero','how-it-works','testimonials','order-form','footer']],
            ['id' => 'info',    'name' => 'Інфопродукт','desc' => 'Hero · Блок тексту · Переваги · Ціна · Форма', 'icon' => '📚', 'sections_preview' => ['hero','text-block','benefits','pricing','order-form']],
        ];
    }

    public static function buildSections(string $tplId): array {
        $tm  = new Template();
        if ($tplId === 'product') {
            $map = [
                ['hero',       'hero-01'],
                ['benefits',   'benefits-01'],
                ['pricing',    'pricing-01'],
                ['order-form', 'order-form-01'],
                ['footer',     'footer-01'],
            ];
        } elseif ($tplId === 'service') {
            $map = [
                ['hero',         'hero-01'],
                ['how-it-works', null],
                ['testimonials', 'testimonials-01'],
                ['order-form',   'order-form-01'],
                ['footer',       'footer-01'],
            ];
        } elseif ($tplId === 'info') {
            $map = [
                ['hero',       'hero-01'],
                ['text-block', null],
                ['benefits',   'benefits-01'],
                ['pricing',    'pricing-01'],
                ['order-form', 'order-form-01'],
            ];
        } else {
            $map = [];
        }

        $sections = [];
        foreach ($map as [$type, $tId]) {
            $tmpl = $tId ? ($tm->get($type, $tId) ?? []) : [];
            $vars = [];
            foreach ($tmpl['vars'] ?? [] as $k => $def) {
                $vars[$k] = $def['default'] ?? '';
            }
            $sections[] = [
                'id'       => self::uuid(),
                'type'     => $type,
                'template' => $tId ?? '',
                'visible'  => true,
                'html'     => $tmpl['html'] ?? "<div style=\"padding:40px 20px;text-align:center;color:#888\"><p>{$type}</p></div>",
                'css'      => $tmpl['css'] ?? '',
                'js'       => $tmpl['js']  ?? '',
                'php'      => $tmpl['php'] ?? '',
                'data'     => ['vars' => $vars],
            ];
        }
        return $sections;
    }

    private static function uuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
    }
}
