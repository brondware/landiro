<?php
class FileManager {
    public function upload(array $file, string $slug): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Помилка завантаження файлу'];
        }
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'error' => 'Файл занадто великий (максимум 5MB)'];
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'error' => 'Дозволені тільки зображення (JPG, PNG, GIF, WebP, SVG)'];
        }
        $ext = $this->getExtension($mime);
        $uploadDir = UPLOADS_PATH . '/' . preg_replace('/[^a-z0-9-]/', '', $slug);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = uniqid('img_') . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;

        // Конвертація в WebP якщо є підтримка GD
        if (in_array($mime, ['image/jpeg', 'image/png']) && function_exists('imagewebp')) {
            $webpDest = $uploadDir . '/' . uniqid('img_') . '.webp';
            if ($this->convertToWebP($file['tmp_name'], $mime, $webpDest)) {
                $filename = basename($webpDest);
                $dest = $webpDest;
            } else {
                move_uploaded_file($file['tmp_name'], $dest);
            }
        } else {
            move_uploaded_file($file['tmp_name'], $dest);
        }

        $relPath = 'data/uploads/' . preg_replace('/[^a-z0-9-]/', '', $slug) . '/' . $filename;
        return ['success' => true, 'path' => BASE_URL . '/' . $relPath, 'rel' => $relPath, 'filename' => $filename];
    }

    private function convertToWebP(string $src, string $mime, string $dest): bool {
        try {
            if ($mime === 'image/jpeg')     $img = imagecreatefromjpeg($src);
            elseif ($mime === 'image/png')  $img = imagecreatefrompng($src);
            else                            $img = null;
            if (!$img) return false;
            $result = imagewebp($img, $dest, 85);
            imagedestroy($img);
            return $result;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getExtension(string $mime): string {
        if ($mime === 'image/jpeg')     return 'jpg';
        if ($mime === 'image/png')      return 'png';
        if ($mime === 'image/gif')      return 'gif';
        if ($mime === 'image/webp')     return 'webp';
        if ($mime === 'image/svg+xml')  return 'svg';
        return 'jpg';
    }
}
