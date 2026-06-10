<?php
class Analytics {
    private string $dir;

    public function __construct() {
        $this->dir = DATA_PATH . '/analytics';
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    public function hit(string $slug): void {
        $data = $this->load($slug);
        $data['views']++;
        $today = date('Y-m-d');
        $data['daily'][$today]['views'] = ($data['daily'][$today]['views'] ?? 0) + 1;
        $this->trimDaily($data);
        $this->save($slug, $data);
    }

    public function order(string $slug): void {
        $data = $this->load($slug);
        $data['orders']++;
        $today = date('Y-m-d');
        $data['daily'][$today]['orders'] = ($data['daily'][$today]['orders'] ?? 0) + 1;
        $this->trimDaily($data);
        $this->save($slug, $data);
    }

    public function getStats(string $slug): array {
        return $this->load($slug);
    }

    public function abHit(string $slug, string $sectionId, string $variant): void {
        $data = $this->load($slug);
        $key = 'ab_' . substr(str_replace('-', '', $sectionId), 0, 8);
        $data[$key][$variant . '_views'] = ($data[$key][$variant . '_views'] ?? 0) + 1;
        $this->save($slug, $data);
    }

    public function abConvert(string $slug, string $sectionId, string $variant): void {
        $data = $this->load($slug);
        $key = 'ab_' . substr(str_replace('-', '', $sectionId), 0, 8);
        $data[$key][$variant . '_orders'] = ($data[$key][$variant . '_orders'] ?? 0) + 1;
        $this->save($slug, $data);
    }

    public function getAbStats(string $slug): array {
        $data = $this->load($slug);
        $result = [];
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'ab_') && is_array($val)) {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    public function getAllStats(): array {
        $result = [];
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $slug = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true) ?? [];
            $result[$slug] = ['views' => $data['views'] ?? 0, 'orders' => $data['orders'] ?? 0];
        }
        return $result;
    }

    private function load(string $slug): array {
        $file = $this->dir . '/' . $slug . '.json';
        if (!file_exists($file)) return ['views' => 0, 'orders' => 0, 'daily' => []];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : ['views' => 0, 'orders' => 0, 'daily' => []];
    }

    private function save(string $slug, array $data): void {
        file_put_contents(
            $this->dir . '/' . $slug . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    private function trimDaily(array &$data): void {
        if (count($data['daily'] ?? []) > 30) {
            ksort($data['daily']);
            $data['daily'] = array_slice($data['daily'], -30, 30, true);
        }
    }
}
