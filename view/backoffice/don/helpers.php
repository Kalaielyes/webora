<?php
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../../models/config.php';
}
require_once __DIR__ . '/../../../models/Session.php';

function backofficeBuildUrl(string $file, array $params = []): string
{
    $query = http_build_query($params);
    $base  = rtrim(APP_URL, '/') . '/view/backoffice/don/' . ltrim($file, '/');
    return htmlspecialchars($base . ($query !== '' ? ('?' . $query) : ''), ENT_QUOTES, 'UTF-8');
}

function cagnotteStatusLabel(string $status): string
{
    $labels = [
        'en_attente' => 'En attente',
        'acceptee'   => 'Acceptée',
        'refusee'    => 'Refusée',
        'suspendue'  => 'Suspendue',
        'cloturee'   => 'Clôturée',
    ];
    return $labels[$status] ?? 'En attente';
}

function cagnotteStatusBadgeClass(string $status): string
{
    if ($status === 'acceptee')  return 'b-active';
    if ($status === 'refusee')   return 'b-danger';
    if ($status === 'suspendue') return 'b-suspend';
    if ($status === 'cloturee')  return 'b-termine';
    return 'b-attente';
}

function cagnotteCategoryLabel(string $category): string
{
    $labels = [
        'sante'      => 'Santé',
        'education'  => 'Éducation',
        'solidarite' => 'Solidarité',
        'autre'      => 'Autre',
    ];
    return $labels[$category] ?? ucfirst($category);
}

function cagnotteCategoryBadgeClass(string $category): string
{
    $classes = [
        'sante'      => 'cat-medical',
        'education'  => 'cat-education',
        'solidarite' => 'cat-humanitaire',
        'autre'      => 'cat-urgence',
    ];
    return $classes[$category] ?? 'cat-urgence';
}

function renderBackofficeSidebar(string $activePage): void
{
    include __DIR__ . '/../partials/sidebar_unified.php';
}
