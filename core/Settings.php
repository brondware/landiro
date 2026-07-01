<?php
class Settings {
    private static $file = '';
    private static $data  = [];
    private static $loaded = false;

    public static function init(): void {
        self::$file = DATA_PATH . '/settings.json';
        if (!self::$loaded) {
            self::$data = file_exists(self::$file)
                ? (json_decode(file_get_contents(self::$file), true) ?? [])
                : [];
            self::$loaded = true;
        }
    }

    public static function get(string $key, $default = null) {
        self::init();
        return self::$data[$key] ?? $default;
    }

    public static function set(string $key, $value): void {
        self::init();
        self::$data[$key] = $value;
    }

    public static function setMany(array $pairs): void {
        foreach ($pairs as $k => $v) self::set($k, $v);
    }

    public static function save(): bool {
        self::init();
        return file_put_contents(self::$file, json_encode(self::$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    public static function all(): array {
        self::init();
        return self::$data;
    }
}
