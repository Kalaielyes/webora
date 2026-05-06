<?php
class Session {
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
    public static function isLoggedIn() : bool {
        return isset($_SESSION['user_id']);
    }
    public static function isAdmin() : bool {
        return isset($_SESSION['role']) &&
               in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN']);
    }
<<<<<<< HEAD
    public static function requireLogin(string $redirect = '../views/frontoffice/login.php') : void {
=======
    public static function requireLogin(string $redirect = '../views/FrontOffice/login.php') : void {
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
        self::start();
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect);
            exit;
        }
    }
<<<<<<< HEAD
    public static function requireAdmin(string $redirect = '../views/frontoffice/login.php') : void {
=======
    public static function requireAdmin(string $redirect = '../views/FrontOffice/login.php') : void {
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
        self::start();
        if (!self::isAdmin()) {
            header('Location: ' . $redirect);
            exit;
        }
    }
    public static function setFlash(string $type, string $message) : void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
    public static function getFlash() : ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
