<?php
/**
 * Classe de géolocalisation IP du client
 * Utilise l'API ipapi.co (1000 req/jour gratuit)
 * Détecte le pays, la devise et adapte les règles de crédit par région
 */
class ClientGeolocation
{
    private const IPAPI_ENDPOINT = 'https://ipapi.co/json/';
    private const CACHE_DURATION = 86400; // 24h
    
    // Mapping des devises par pays (code ISO)
    private const COUNTRY_CURRENCIES = [
        'TN' => ['currency' => 'TND', 'name' => 'Dinar Tunisien', 'symbol' => 'د.ت'],
        'FR' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'BE' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'CH' => ['currency' => 'CHF', 'name' => 'Franc Suisse', 'symbol' => 'CHF'],
        'US' => ['currency' => 'USD', 'name' => 'Dollar US', 'symbol' => '$'],
        'CA' => ['currency' => 'CAD', 'name' => 'Dollar Canadien', 'symbol' => 'C$'],
        'GB' => ['currency' => 'GBP', 'name' => 'Livre Sterling', 'symbol' => '£'],
        'DE' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'IT' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'ES' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'DZ' => ['currency' => 'DZD', 'name' => 'Dinar Algérien', 'symbol' => 'د.ج'],
        'MA' => ['currency' => 'MAD', 'name' => 'Dirham Marocain', 'symbol' => 'د.م.'],
        'LY' => ['currency' => 'LYD', 'name' => 'Dinar Libyen', 'symbol' => 'ل.د'],
        'DEFAULT' => ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
    ];
    
    // Règles de crédit par région
    private const REGIONAL_RULES = [
        'TN' => [
            'max_montant' => 1_000_000,
            'min_montant' => 500,
            'taux_defaut' => 7.5,
            'duree_max_mois' => 60,
            'description' => 'Tunisie'
        ],
        'FR' => [
            'max_montant' => 500_000,
            'min_montant' => 1_000,
            'taux_defaut' => 4.5,
            'duree_max_mois' => 84,
            'description' => 'France'
        ],
        'US' => [
            'max_montant' => 300_000,
            'min_montant' => 2_000,
            'taux_defaut' => 5.0,
            'duree_max_mois' => 60,
            'description' => 'États-Unis'
        ],
        'CH' => [
            'max_montant' => 400_000,
            'min_montant' => 1_500,
            'taux_defaut' => 3.5,
            'duree_max_mois' => 84,
            'description' => 'Suisse'
        ],
        'DEFAULT' => [
            'max_montant' => 250_000,
            'min_montant' => 1_000,
            'taux_defaut' => 6.5,
            'duree_max_mois' => 60,
            'description' => 'Autres régions'
        ]
    ];

    /**
     * Récupère la géolocalisation du client depuis son IP
     * @param string|null $ip IP du client (null = $_SERVER['REMOTE_ADDR'])
     * @return array Données de géolocalisation
     */
    public static function getClientLocation(?string $ip = null): array
    {
        $ip = $ip ?? self::getClientIp();
        
        // IPs locales
        if (self::isLocalIp($ip)) {
            return self::getDefaultLocation();
        }

        // Vérifier le cache
        $cached = self::getCachedLocation($ip);
        if ($cached) {
            return $cached;
        }

        // Appeler l'API
        $location = self::fetchFromApi($ip);
        
        if ($location) {
            self::cacheLocation($ip, $location);
        } else {
            $location = self::getDefaultLocation();
        }

        return $location;
    }

    /**
     * Récupère l'adresse IP du client
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return trim($ip);
    }

    /**
     * Vérifie si l'IP est locale
     */
    private static function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', '0.0.0.0']) ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '10.');
    }

    /**
     * Récupère la localisation depuis le cache
     */
    private static function getCachedLocation(string $ip): ?array
    {
        $cacheFile = sys_get_temp_dir() . '/geoloc_' . md5($ip) . '.json';
        
        if (file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if (time() - $mtime < self::CACHE_DURATION) {
                $data = json_decode(file_get_contents($cacheFile), true);
                return $data ?: null;
            } else {
                unlink($cacheFile);
            }
        }
        return null;
    }

    /**
     * Enregistre la localisation en cache
     */
    private static function cacheLocation(string $ip, array $location): void
    {
        $cacheFile = sys_get_temp_dir() . '/geoloc_' . md5($ip) . '.json';
        file_put_contents($cacheFile, json_encode($location));
    }

    /**
     * Appelle l'API ipapi.co
     */
    private static function fetchFromApi(string $ip): ?array
    {
        try {
            $url = self::IPAPI_ENDPOINT;
            if ($ip !== '0.0.0.0') {
                $url = "https://ipapi.co/$ip/json/";
            }

            $context = stream_context_create([
                'http' => ['timeout' => 5]
            ]);

            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log('[ClientGeolocation] Erreur API ipapi.co pour IP: ' . $ip);
                return null;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('[ClientGeolocation] JSON decode error: ' . json_last_error_msg());
                return null;
            }

            return self::formatApiResponse($data);

        } catch (Exception $e) {
            error_log('[ClientGeolocation] Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formate la réponse API
     */
    private static function formatApiResponse(array $apiData): ?array
    {
        if (empty($apiData['country_code'])) {
            return null;
        }

        $countryCode = strtoupper($apiData['country_code']);
        $currencyInfo = self::COUNTRY_CURRENCIES[$countryCode] 
            ?? self::COUNTRY_CURRENCIES['DEFAULT'] 
            ?? ['currency' => 'EUR', 'name' => 'Euro', 'symbol' => '€'];

        return [
            'ip' => $apiData['ip'] ?? '',
            'country_code' => $countryCode,
            'country_name' => $apiData['country_name'] ?? 'Inconnu',
            'city' => $apiData['city'] ?? '',
            'region' => $apiData['region'] ?? '',
            'timezone' => $apiData['timezone'] ?? 'UTC',
            'latitude' => $apiData['latitude'] ?? null,
            'longitude' => $apiData['longitude'] ?? null,
            'currency' => $currencyInfo['currency'],
            'currency_name' => $currencyInfo['name'],
            'currency_symbol' => $currencyInfo['symbol'],
        ];
    }

    /**
     * Localisation par défaut (Tunisie)
     */
    private static function getDefaultLocation(): array
    {
        return [
            'ip' => self::getClientIp(),
            'country_code' => 'TN',
            'country_name' => 'Tunisie',
            'city' => '',
            'region' => '',
            'timezone' => 'Africa/Tunis',
            'latitude' => null,
            'longitude' => null,
            'currency' => 'TND',
            'currency_name' => 'Dinar Tunisien',
            'currency_symbol' => 'د.ت',
        ];
    }

    /**
     * Récupère les règles de crédit pour un pays
     */
    public static function getRegionalRules(string $countryCode): array
    {
        $countryCode = strtoupper($countryCode);
        return self::REGIONAL_RULES[$countryCode] ?? self::REGIONAL_RULES['DEFAULT'];
    }

    /**
     * Vérifie si un montant est valide pour une région
     */
    public static function isMontantValid(float $montant, string $countryCode): bool
    {
        $rules = self::getRegionalRules($countryCode);
        return $montant >= $rules['min_montant'] && $montant <= $rules['max_montant'];
    }

    /**
     * Obtient les taux d'intérêt recommandés basés sur le pays et le profil
     */
    public static function getRecommendedRate(string $countryCode, string $riskProfile = 'medium'): float
    {
        $rules = self::getRegionalRules($countryCode);
        $baseTaux = $rules['taux_defaut'];

        // Ajustement basé sur le profil de risque
        $adjustments = [
            'low' => -1.0,
            'medium' => 0,
            'high' => 2.0,
            'very_high' => 4.0
        ];

        return $baseTaux + ($adjustments[$riskProfile] ?? 0);
    }

    /**
     * Convertit un montant d'une devise à une autre (simple, sans API de change)
     * Pour un vrai projet, utiliser une API comme fixer.io ou exchangerate-api.com
     */
    public static function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        // Taux de change approximatifs (à jour au 2026-05-06)
        $rates = [
            'TND' => 1,
            'EUR' => 0.31,
            'USD' => 0.32,
            'CHF' => 0.29,
            'GBP' => 0.25,
            'CAD' => 0.45,
        ];

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $baseAmount = $amount / ($rates[$fromCurrency] ?? 1);
        return $baseAmount * ($rates[$toCurrency] ?? 1);
    }

    /**
     * Retourne le drapeau emoji pour un code pays
     */
    public static function getCountryFlag(string $countryCode): string
    {
        $flags = [
            'TN' => '🇹🇳',
            'FR' => '🇫🇷',
            'US' => '🇺🇸',
            'GB' => '🇬🇧',
            'DE' => '🇩🇪',
            'IT' => '🇮🇹',
            'ES' => '🇪🇸',
            'CH' => '🇨🇭',
            'CA' => '🇨🇦',
            'BE' => '🇧🇪',
            'DZ' => '🇩🇿',
            'MA' => '🇲🇦',
            'LY' => '🇱🇾',
        ];
        return $flags[strtoupper($countryCode)] ?? '🌍';
    }
}
