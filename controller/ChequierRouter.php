<?php
/**
 * ChequierRouter - Routing Integration for Cheque Module
 * Handles requests for cheque management, cheque books, and biometric verification
 */

class ChequierRouter {
    
    private static array $routes = [];

    public static function init() {
        // Chequier Management Routes
        self::$routes = [
            // ── Chequier CRUD ──────────────────────────────────────────
            'chequier/list'         => ['controller' => 'ChequierController', 'method' => 'listChequiers'],
            'chequier/view'         => ['controller' => 'ChequierController', 'method' => 'getChequierById'],
            'chequier/add'          => ['controller' => 'ChequierController', 'method' => 'addChequier'],
            'chequier/update'       => ['controller' => 'ChequierController', 'method' => 'updateChequier'],
            'chequier/delete'       => ['controller' => 'ChequierController', 'method' => 'deleteChequier'],
            'chequier/by-account'   => ['controller' => 'ChequierController', 'method' => 'getChequiersByAccount'],
            'chequier/expiring'     => ['controller' => 'ChequierController', 'method' => 'getChequiersExpirantMoinsDe15Jours'],

            // ── Cheque CRUD ────────────────────────────────────────────
            'cheque/list'           => ['controller' => 'ChequeController', 'method' => 'listChequesByChequier'],
            'cheque/view'           => ['controller' => 'ChequeController', 'method' => 'getChequeById'],
            'cheque/add'            => ['controller' => 'ChequeController', 'method' => 'addCheque'],
            'cheque/update'         => ['controller' => 'ChequeController', 'method' => 'updateCheque'],
            'cheque/delete'         => ['controller' => 'ChequeController', 'method' => 'deleteCheque'],
            'cheque/verify'         => ['controller' => 'ChequeController', 'method' => 'verifyCheque'],
            'cheque/search'         => ['controller' => 'ChequeController', 'method' => 'searchCheques'],

            // ── Cheque Book Request (Demand) ───────────────────────────
            'demande-chequier/list' => ['controller' => 'DemandeChequierController', 'method' => 'listDemandes'],
            'demande-chequier/view' => ['controller' => 'DemandeChequierController', 'method' => 'getDemandeById'],
            'demande-chequier/add'  => ['controller' => 'DemandeChequierController', 'method' => 'addDemande'],
            'demande-chequier/update' => ['controller' => 'DemandeChequierController', 'method' => 'updateDemande'],
            'demande-chequier/delete' => ['controller' => 'DemandeChequierController', 'method' => 'deleteDemande'],
            'demande-chequier/status' => ['controller' => 'DemandeChequierController', 'method' => 'updateStatus'],

            // ── Biometric Face Authentication ──────────────────────────
            'biometric/descriptors' => ['controller' => 'BiometricFaceAuthController', 'method' => 'getDescriptors'],
            'biometric/save-face'   => ['controller' => 'BiometricFaceAuthController', 'method' => 'saveFace'],
            'biometric/verify'      => ['controller' => 'BiometricFaceAuthController', 'method' => 'verifyAndLogin'],
            'biometric/log-fail'    => ['controller' => 'BiometricFaceAuthController', 'method' => 'logFailedAttempt'],
            'biometric/save-pin'    => ['controller' => 'BiometricFaceAuthController', 'method' => 'savePin'],
            'biometric/verify-pin'  => ['controller' => 'BiometricFaceAuthController', 'method' => 'verifyPin'],
            'biometric/verify-cheque' => ['controller' => 'BiometricFaceAuthController', 'method' => 'verifyCheque'],
            'biometric/delete-face' => ['controller' => 'BiometricFaceAuthController', 'method' => 'deleteFaceDescriptor'],
            'biometric/has-pin'     => ['controller' => 'BiometricFaceAuthController', 'method' => 'hasPinSet'],
        ];
    }

    public static function route(string $path): bool {
        self::init();
        
        if (!isset(self::$routes[$path])) {
            return false;
        }

        $route = self::$routes[$path];
        $controller = $route['controller'];
        $method = $route['method'];

        // Require controller
        require_once __DIR__ . '/' . $controller . '.php';

        // Instantiate and call
        if (class_exists($controller)) {
            $instance = new $controller();
            if (method_exists($instance, $method)) {
                $instance->$method();
                return true;
            }
        }

        return false;
    }

    public static function getRoutes(): array {
        self::init();
        return self::$routes;
    }
}
