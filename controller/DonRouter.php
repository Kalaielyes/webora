<?php

/**
 * DonRouter — Routing for Donations / Cagnottes module.
 * Follows the same pattern as ChequierRouter.
 */
class DonRouter
{
    private static array $routes = [];

    public static function init(): void
    {
        self::$routes = [
            // ── Cagnotte CRUD ──────────────────────────────────────────
            'don/cagnotte/list'     => ['controller' => 'CagnotteController', 'method' => 'getAllCagnottes'],
            'don/cagnotte/view'     => ['controller' => 'CagnotteController', 'method' => 'getCagnotteById'],
            'don/cagnotte/add'      => ['controller' => 'CagnotteController', 'method' => 'ajouterCagnotte'],
            'don/cagnotte/update'   => ['controller' => 'CagnotteController', 'method' => 'modifierCagnotte'],
            'don/cagnotte/delete'   => ['controller' => 'CagnotteController', 'method' => 'supprimerCagnotte'],
            'don/cagnotte/status'   => ['controller' => 'CagnotteController', 'method' => 'updateStatusAdmin'],
            'don/cagnotte/mine'     => ['controller' => 'CagnotteController', 'method' => 'getUserCagnottes'],
            'don/cagnotte/stats'    => ['controller' => 'CagnotteController', 'method' => 'getAdminOverviewStats'],

            // ── Don CRUD ───────────────────────────────────────────────
            'don/don/list'          => ['controller' => 'DonController', 'method' => 'getAllDons'],
            'don/don/add'           => ['controller' => 'DonController', 'method' => 'ajouterDon'],
            'don/don/delete'        => ['controller' => 'DonController', 'method' => 'supprimerDon'],
            'don/don/confirm'       => ['controller' => 'DonController', 'method' => 'confirmerDon'],
            'don/don/refuse'        => ['controller' => 'DonController', 'method' => 'refuserDon'],
            'don/don/stats'         => ['controller' => 'DonController', 'method' => 'getGlobalStats'],

            // ── Achievements ───────────────────────────────────────────
            'don/achievement/list'  => ['controller' => 'AchievementController', 'method' => 'getAllWithUnlockCount'],
            'don/achievement/add'   => ['controller' => 'AchievementController', 'method' => 'create'],
            'don/achievement/update'=> ['controller' => 'AchievementController', 'method' => 'update'],
            'don/achievement/delete'=> ['controller' => 'AchievementController', 'method' => 'delete'],
            'don/achievement/toggle'=> ['controller' => 'AchievementController', 'method' => 'toggle'],
            'don/achievement/user'  => ['controller' => 'AchievementController', 'method' => 'getUserAchievementsData'],
        ];
    }

    public static function route(string $path): bool
    {
        self::init();

        if (!isset(self::$routes[$path])) {
            return false;
        }

        $route      = self::$routes[$path];
        $controller = $route['controller'];
        $method     = $route['method'];

        require_once __DIR__ . '/' . $controller . '.php';

        if (class_exists($controller)) {
            $instance = new $controller();
            if (method_exists($instance, $method)) {
                $instance->$method();
                return true;
            }
        }

        return false;
    }

    public static function getRoutes(): array
    {
        self::init();
        return self::$routes;
    }
}
