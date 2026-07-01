<?php
class Presets {
    private $dir;

    public function __construct() {
        $this->dir = DATA_PATH . '/presets';
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    public function save(string $name, array $section): string {
        $id = 'preset_' . uniqid();
        $entry = [
            'id'         => $id,
            'name'       => $name,
            'type'       => $section['type'] ?? 'custom',
            'template'   => $section['template'] ?? '',
            'created_at' => date('c'),
            'section'    => array_intersect_key($section, array_flip(['html', 'css', 'js', 'php', 'data'])),
        ];
        file_put_contents(
            $this->dir . '/' . $id . '.json',
            json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        return $id;
    }

    public function getAll(): array {
        $list = [];
        foreach (glob($this->dir . '/preset_*.json') ?: [] as $f) {
            $d = json_decode(file_get_contents($f), true);
            if ($d) $list[] = $d;
        }
        usort($list, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });
        return $list;
    }

    public function get(string $id): ?array {
        $f = $this->dir . '/' . basename($id) . '.json';
        return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: null) : null;
    }

    public function delete(string $id): bool {
        $f = $this->dir . '/' . basename($id) . '.json';
        return file_exists($f) && @unlink($f);
    }
}
