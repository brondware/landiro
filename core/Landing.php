<?php
class Landing {
    private string $dataPath;

    public function __construct() {
        $this->dataPath = LANDINGS_DATA_PATH;
    }

    public function getAll(): array {
        $landings = [];
        $files = glob($this->dataPath . '/*.json');
        if (!$files) return [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $landings[] = [
                    'id' => $data['id'],
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                    'published' => $data['published'] ?? false,
                    'updated_at' => $data['updated_at'] ?? '',
                    'sections_count' => count($data['sections'] ?? []),
                ];
            }
        }
        usort($landings, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
        return $landings;
    }

    public function get(string $slug): ?array {
        $file = $this->dataPath . '/' . $slug . '.json';
        if (!file_exists($file)) return null;
        return json_decode(file_get_contents($file), true);
    }

    public function create(string $title, string $slug = ''): array {
        if (!$slug) {
            $slug = $this->slugify($title);
        }
        $slug = $this->uniqueSlug($slug);
        $landing = [
            'id' => $this->uuid(),
            'slug' => $slug,
            'title' => $title,
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'published' => false,
            'password' => '',
            'seo' => [
                'title' => $title,
                'description' => '',
                'og_image' => '',
                'favicon' => '',
            ],
            'global_styles' => [
                'primary_color' => '#FF5A1F',
                'secondary_color' => '#1A1A2E',
                'accent_color' => '#FFD700',
                'text_color' => '#333333',
                'font_family' => 'Inter, sans-serif',
                'custom_css' => '',
            ],
            'scripts' => [
                'ga_id' => '',
                'fb_pixel' => '',
                'gtm_id' => '',
                'head' => '',
                'body_end' => '',
            ],
            'sections' => [],
        ];
        $this->save($landing);
        return $landing;
    }

    public function save(array $landing): bool {
        $landing['updated_at'] = date('c');
        $file = $this->dataPath . '/' . $landing['slug'] . '.json';
        $this->saveHistory($landing['slug'], $file);
        return file_put_contents($file, json_encode($landing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    public function delete(string $slug): bool {
        $file = $this->dataPath . '/' . $slug . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
        $historyDir = $this->dataPath . '/' . $slug . '_history';
        if (is_dir($historyDir)) {
            array_map('unlink', glob($historyDir . '/*.json'));
            rmdir($historyDir);
        }
        return true;
    }

    public function clone(string $slug): ?array {
        $landing = $this->get($slug);
        if (!$landing) return null;
        $newSlug = $this->uniqueSlug($slug . '-copy');
        $landing['id'] = $this->uuid();
        $landing['slug'] = $newSlug;
        $landing['title'] = $landing['title'] . ' (копія)';
        $landing['published'] = false;
        $landing['created_at'] = date('c');
        $this->save($landing);
        return $landing;
    }

    public function addSection(string $slug, array $section): ?array {
        $landing = $this->get($slug);
        if (!$landing) return null;
        $section['id'] = $this->uuid();
        $landing['sections'][] = $section;
        $this->save($landing);
        return $section;
    }

    public function updateSection(string $slug, string $sectionId, array $data): bool {
        $landing = $this->get($slug);
        if (!$landing) return false;
        foreach ($landing['sections'] as &$section) {
            if ($section['id'] === $sectionId) {
                $section = array_merge($section, $data);
                return $this->save($landing);
            }
        }
        return false;
    }

    public function deleteSection(string $slug, string $sectionId): bool {
        $landing = $this->get($slug);
        if (!$landing) return false;
        $landing['sections'] = array_values(array_filter(
            $landing['sections'],
            fn($s) => $s['id'] !== $sectionId
        ));
        return $this->save($landing);
    }

    public function cloneSection(string $slug, string $sectionId): ?array {
        $landing = $this->get($slug);
        if (!$landing) return null;
        $insertAfter = -1;
        $cloned = null;
        foreach ($landing['sections'] as $i => $s) {
            if ($s['id'] === $sectionId) {
                $cloned = $s;
                $insertAfter = $i;
                break;
            }
        }
        if (!$cloned) return null;
        $cloned['id'] = $this->uuid();
        array_splice($landing['sections'], $insertAfter + 1, 0, [$cloned]);
        $this->save($landing);
        return $cloned;
    }

    public function reorderSections(string $slug, array $order): bool {
        $landing = $this->get($slug);
        if (!$landing) return false;
        $indexed = [];
        foreach ($landing['sections'] as $s) {
            $indexed[$s['id']] = $s;
        }
        $reordered = [];
        foreach ($order as $id) {
            if (isset($indexed[$id])) {
                $reordered[] = $indexed[$id];
            }
        }
        $landing['sections'] = $reordered;
        return $this->save($landing);
    }

    public function getHistory(string $slug): array {
        $historyDir = $this->dataPath . '/' . $slug . '_history';
        if (!is_dir($historyDir)) return [];
        $files = glob($historyDir . '/*.json');
        if (!$files) return [];
        rsort($files);
        return array_slice($files, 0, 10);
    }

    private function saveHistory(string $slug, string $currentFile): void {
        if (!file_exists($currentFile)) return;
        $historyDir = $this->dataPath . '/' . $slug . '_history';
        if (!is_dir($historyDir)) mkdir($historyDir, 0755, true);
        $ts = date('YmdHis');
        copy($currentFile, $historyDir . '/' . $ts . '.json');
        // Зберігаємо тільки 10 останніх
        $files = glob($historyDir . '/*.json');
        if ($files && count($files) > 10) {
            sort($files);
            unlink($files[0]);
        }
    }

    private function slugify(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9а-яёіїєa-z]/u', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        if (!$text) $text = 'landing';
        // Транслітерація простих кириличних символів
        $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','і','ї','є'];
        $lat = ['a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','y','','e','yu','ya','i','yi','ye'];
        $text = str_replace($cyr, $lat, $text);
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-') ?: 'landing';
    }

    private function uniqueSlug(string $slug): string {
        $base = $slug;
        $i = 1;
        while (file_exists($this->dataPath . '/' . $slug . '.json')) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function uuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
