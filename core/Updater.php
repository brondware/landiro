<?php
class Updater {
    private static $cacheFile = '';
    private static $cacheTtl  = 3600; // 1 year cache, refresh manually or hourly

    public static function init(): void {
        self::$cacheFile = DATA_PATH . '/update_cache.json';
    }

    // Returns raw API data (cached), or null on failure
    public static function fetchRemote(bool $force = false): ?array {
        if (!defined('UPDATE_CHECK_URL') || !UPDATE_CHECK_URL) return null;

        if (!$force && self::$cacheFile && file_exists(self::$cacheFile)) {
            $cache = @json_decode(file_get_contents(self::$cacheFile), true);
            if ($cache && isset($cache['checked_at']) && (time() - $cache['checked_at']) < self::$cacheTtl) {
                return $cache['data'] ?? null;
            }
        }

        $json = self::httpGet(UPDATE_CHECK_URL, 6);
        if ($json === null) return null;

        $data = json_decode($json, true);
        if (!is_array($data)) return null;

        if (self::$cacheFile) {
            @file_put_contents(self::$cacheFile, json_encode([
                'checked_at' => time(),
                'data'       => $data,
            ], JSON_UNESCAPED_UNICODE));
        }

        return $data;
    }

    public static function getLatest(): ?array {
        $data = self::fetchRemote();
        return ($data && isset($data['latest'])) ? $data['latest'] : null;
    }

    // Returns true if a newer version exists upstream
    public static function hasUpdate(): bool {
        $latest = self::getLatest();
        if (!$latest || empty($latest['version'])) return false;
        return version_compare($latest['version'], CMS_VERSION, '>');
    }

    // Copy or download archive to data/. Returns local path or false on error.
    public static function download(string $url, string $localPath = '') {
        $tmpFile = DATA_PATH . '/update_download.zip';

        // Fast path: file is on the same server — just copy it
        if ($localPath !== '' && file_exists($localPath)) {
            if (!@copy($localPath, $tmpFile)) return false;
            return $tmpFile;
        }

        // HTTP download fallback
        $content = self::httpGet($url, 120);
        if ($content === null || strlen($content) < 22) return false;

        // Basic ZIP magic bytes check
        if (substr($content, 0, 2) !== 'PK') return false;

        if (@file_put_contents($tmpFile, $content) === false) return false;
        return $tmpFile;
    }

    // Fire-and-forget GET request (used for tracking pings)
    public static function ping(string $url): void {
        @self::httpGet($url, 4);
    }

    // Performs an HTTP GET using cURL (preferred) or file_get_contents fallback
    private static function httpGet(string $url, int $timeout): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => min($timeout, 10),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => 'Landiro-CMS/' . CMS_VERSION,
                CURLOPT_ENCODING       => '',
            ]);
            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $status < 200 || $status >= 300) return null;
            return $body;
        }

        // fallback: file_get_contents
        $ctx = stream_context_create([
            'http' => [
                'timeout'       => $timeout,
                'user_agent'    => 'Landiro-CMS/' . CMS_VERSION,
                'ignore_errors' => true,
            ],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }

    // Rewrite CMS_VERSION in config.php to $newVersion
    private static function updateConfigVersion(string $newVersion): bool {
        $configPath = ROOT_PATH . '/config.php';
        if (!file_exists($configPath)) return false;
        $config  = file_get_contents($configPath);
        $updated = preg_replace(
            "/define\s*\(\s*'CMS_VERSION'\s*,\s*'[^']*'\s*\)/",
            "define('CMS_VERSION', '" . $newVersion . "')",
            $config,
            -1,
            $count
        );
        if (!$count || $updated === null) return false;
        return file_put_contents($configPath, $updated) !== false;
    }

    // Apply downloaded ZIP archive. Returns ['success'=>bool, 'error'=>string, 'copied'=>int]
    public static function apply(string $zipPath, string $newVersion = ''): array {
        if (!extension_loaded('zip')) {
            return ['success' => false, 'error' => 'PHP zip розширення не встановлено'];
        }
        if (!file_exists($zipPath)) {
            return ['success' => false, 'error' => 'Файл оновлення не знайдено'];
        }

        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            return ['success' => false, 'error' => 'Не вдалося відкрити ZIP (код: ' . $res . ')'];
        }

        $tmpDir = DATA_PATH . '/update_tmp_' . time();
        if (!@mkdir($tmpDir, 0755, true)) {
            $zip->close();
            return ['success' => false, 'error' => 'Не вдалося створити тимчасову папку'];
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        // Find actual root (archive may have a top-level subdir)
        $srcRoot  = $tmpDir;
        $entries  = array_diff(scandir($tmpDir) ?: [], ['.', '..']);
        $subdirs  = array_filter($entries, function($e) use ($tmpDir) { return is_dir($tmpDir . '/' . $e); });
        if (count($entries) === 1 && count($subdirs) === 1) {
            $srcRoot = $tmpDir . '/' . reset($subdirs);
        }

        // Dirs/files we never touch during update
        $protected = ['data', 'config.php', 'uploads', '.htaccess', 'install'];

        $copied = self::copyDir($srcRoot, ROOT_PATH, $protected);

        // Cleanup
        self::removeDir($tmpDir);
        @unlink($zipPath);

        // Update CMS_VERSION in config.php
        if ($newVersion !== '') {
            self::updateConfigVersion($newVersion);
        }

        // Invalidate update cache so the sidebar badge disappears immediately
        if (file_exists(DATA_PATH . '/update_cache.json')) {
            @unlink(DATA_PATH . '/update_cache.json');
        }

        return ['success' => true, 'copied' => $copied, 'error' => ''];
    }

    // Recursively copy src→dst, skipping any $skip names at the root level
    private static function copyDir(string $src, string $dst, array $skip = []): int {
        $count = 0;
        foreach (array_diff(scandir($src) ?: [], ['.', '..']) as $item) {
            if (in_array($item, $skip, true)) continue;

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) @mkdir($dstPath, 0755, true);
                $count += self::copyDir($srcPath, $dstPath, []);
            } else {
                if (@copy($srcPath, $dstPath)) $count++;
            }
        }
        return $count;
    }

    private static function removeDir(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? self::removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}

Updater::init();
