<?php
class Template {
    private $templatesPath;

    public static $SECTION_TYPES = [
        'hero'         => ['label' => 'Hero / Банер',       'icon' => 'layout', 'color' => '#6366f1'],
        'benefits'     => ['label' => 'Переваги',           'icon' => 'star',   'color' => '#f59e0b'],
        'product'      => ['label' => 'Продукт',            'icon' => 'package','color' => '#10b981'],
        'how-it-works' => ['label' => 'Як це працює',       'icon' => 'list',   'color' => '#3b82f6'],
        'testimonials' => ['label' => 'Відгуки',            'icon' => 'message-circle', 'color' => '#8b5cf6'],
        'pricing'      => ['label' => 'Ціна / Оффер',       'icon' => 'tag',    'color' => '#ef4444'],
        'countdown'    => ['label' => 'Таймер',             'icon' => 'clock',  'color' => '#f97316'],
        'faq'          => ['label' => 'FAQ',                'icon' => 'help-circle', 'color' => '#06b6d4'],
        'gallery'      => ['label' => 'Галерея',            'icon' => 'image',  'color' => '#84cc16'],
        'video'        => ['label' => 'Відео',              'icon' => 'play-circle', 'color' => '#ec4899'],
        'order-form'   => ['label' => 'Форма замовлення',   'icon' => 'shopping-cart', 'color' => '#14b8a6'],
        'trust'        => ['label' => 'Довіра',             'icon' => 'shield', 'color' => '#64748b'],
        'cta'          => ['label' => 'CTA кнопка',         'icon' => 'zap',    'color' => '#dc2626'],
        'footer'       => ['label' => 'Підвал',             'icon' => 'align-bottom', 'color' => '#475569'],
        'before-after' => ['label' => 'До / Після',          'icon' => 'sliders', 'color' => '#0ea5e9'],
        'text-block'   => ['label' => 'Текстовий блок',     'icon' => 'type',   'color' => '#78716c'],
        'custom'       => ['label' => 'Кастомний',          'icon' => 'code',   'color' => '#6b7280'],
    ];

    public function __construct() {
        $this->templatesPath = TEMPLATES_PATH;
    }

    public function getAll(?string $type = null): array {
        $result = [];
        $dirs = $type
            ? glob($this->templatesPath . '/' . $type . '/*', GLOB_ONLYDIR)
            : glob($this->templatesPath . '/*/*', GLOB_ONLYDIR);

        if (!$dirs) return [];

        foreach ($dirs as $dir) {
            $meta = $this->getMeta($dir);
            if ($meta) {
                $meta['path'] = $dir;
                $meta['type'] = basename(dirname($dir));
                $meta['id_dir'] = basename($dir);
                $result[] = $meta;
            }
        }
        return $result;
    }

    public function get(string $type, string $id): ?array {
        $dir = $this->templatesPath . '/' . $type . '/' . $id;
        if (!is_dir($dir)) return null;
        $meta = $this->getMeta($dir);
        if (!$meta) return null;
        $meta['html'] = $this->readFile($dir . '/template.html');
        $meta['css']  = $this->readFile($dir . '/style.css');
        $meta['js']   = $this->readFile($dir . '/script.js');
        $meta['php']  = $this->readFile($dir . '/handler.php');
        $meta['type'] = $type;
        $meta['id_dir'] = $id;
        return $meta;
    }

    public function installFromZip(string $zipPath): array {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Не вдалося відкрити архів'];
        }
        // Перевіряємо наявність meta.json
        $metaContent = $zip->getFromName('meta.json');
        if (!$metaContent) {
            return ['success' => false, 'error' => 'Архів не містить meta.json'];
        }
        $meta = json_decode($metaContent, true);
        if (!$meta || empty($meta['type']) || empty($meta['id'])) {
            return ['success' => false, 'error' => 'Невалідний meta.json (потрібні поля type і id)'];
        }
        $type = preg_replace('/[^a-z0-9-]/', '', $meta['type']);
        $id = preg_replace('/[^a-z0-9-]/', '', $meta['id']);
        $targetDir = $this->templatesPath . '/' . $type . '/' . $id;
        if (!is_dir(dirname($targetDir))) {
            mkdir(dirname($targetDir), 0755, true);
        }
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $zip->extractTo($targetDir);
        $zip->close();
        return ['success' => true, 'type' => $type, 'id' => $id];
    }

    private function getMeta(string $dir): ?array {
        $metaFile = $dir . '/meta.json';
        if (!file_exists($metaFile)) {
            // Автогенерація базової мети
            return [
                'id' => basename($dir),
                'name' => ucfirst(str_replace(['-', '_'], ' ', basename($dir))),
                'description' => '',
                'tags' => [],
                'vars' => [],
                'has_php' => file_exists($dir . '/handler.php'),
                'has_js' => file_exists($dir . '/script.js'),
            ];
        }
        $meta = json_decode(file_get_contents($metaFile), true);
        if (!$meta) return null;
        $meta['has_php'] = file_exists($dir . '/handler.php');
        $meta['has_js'] = file_exists($dir . '/script.js');
        $meta['has_preview'] = file_exists($dir . '/preview.jpg') || file_exists($dir . '/preview.png');
        return $meta;
    }

    private function readFile(string $path): string {
        return file_exists($path) ? file_get_contents($path) : '';
    }
}
