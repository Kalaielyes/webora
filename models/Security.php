<?php
/**
 * Security Helper — CSRF & Protection
 */
class Security {
    /**
     * Start the session securely if not already started.
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            // ini_set('session.cookie_secure', 1); // Enable if HTTPS is available
            session_start();
        }
    }

    /**
     * Generate or retrieve a CSRF token.
     */
    public static function getCsrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify a CSRF token from a request.
     */
    public static function verifyCsrfToken(?string $token): bool {
        self::startSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Output a hidden CSRF input field.
     */
    public static function csrfInput(): void {
        echo '<input type="hidden" name="csrf_token" value="' . self::getCsrfToken() . '">';
    }

    /**
     * Helper for basic XSS protection on output.
     */
    public static function h(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
