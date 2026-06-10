<?php
class Auth {
    public static function check(): bool {
        return isset($_SESSION['cms_admin']) && $_SESSION['cms_admin'] === true;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    public static function login(string $login, string $password): bool {
        if ($login === ADMIN_LOGIN && password_verify($password, ADMIN_PASSWORD)) {
            $_SESSION['cms_admin'] = true;
            $_SESSION['cms_csrf'] = bin2hex(random_bytes(32));
            return true;
        }
        return false;
    }

    public static function logout(): void {
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    public static function csrf(): string {
        if (empty($_SESSION['cms_csrf'])) {
            $_SESSION['cms_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['cms_csrf'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['cms_csrf']) && hash_equals($_SESSION['cms_csrf'], $token);
    }
}
