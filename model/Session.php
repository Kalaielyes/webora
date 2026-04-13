<?php
// =============================================================
//  model/Session.php — NexaBank
//  Gestion centralisée de la session PHP
// =============================================================

class Session {

    // Démarre la session si pas encore démarrée
    public static function start() : void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value) : void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key) : mixed {
        return $_SESSION[$key] ?? null;
    }

    public static function has(string $key) : bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key) : void {
        unset($_SESSION[$key]);
    }

    public static function destroy() : void {
        session_unset();
        session_destroy();
    }

    // ─────────────────────────────────────────────
    //  Vérifications de rôle
    // ─────────────────────────────────────────────

    public static function isLoggedIn() : bool {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() : bool {
        return isset($_SESSION['role']) &&
               in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN']);
    }

    // ─────────────────────────────────────────────
    //  Guards — redirigent si non autorisé
    // ─────────────────────────────────────────────

    public static function requireLogin(string $redirect = '../view/FrontOffice/login.php') : void {
        self::start();
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    public static function requireAdmin(string $redirect = '../view/FrontOffice/login.php') : void {
        self::start();
        if (!self::isAdmin()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    // ─────────────────────────────────────────────
    //  Flash messages (message one-shot)
    // ─────────────────────────────────────────────

    public static function setFlash(string $type, string $message) : void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    // Retourne le flash et le supprime immédiatement
    public static function getFlash() : ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
