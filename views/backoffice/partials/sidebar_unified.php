<?php
// Admin sidebar logic
require_once __DIR__ . "/../../../models/Session.php";
require_once __DIR__ . "/../../../models/Utilisateur.php";

$m_sidebar = new Utilisateur();
$stats_sidebar = $m_sidebar->getStats("tout");
$kyc_count = $stats_sidebar["kyc"] ?? 0;

$currentUserId_sidebar = (int) Session::get("user_id");
$currentUserRole_sidebar = Session::get("role");
$adminInitials_sidebar = strtoupper(mb_substr(Session::get("user_nom")??"",0,1).mb_substr(Session::get("user_prenom")??"",0,1));

$ALL_MODULES_SIDEBAR = [
    "comptes"          => ["label"=>"Comptes",           "icon"=>"<path d=\"M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z\"/><polyline points=\"9 22 9 12 15 12 15 22\"/>"],
    "actions"          => ["label"=>"Actions",           "icon"=>"<polyline points=\"22 12 18 12 15 21 9 3 6 12 2 12\"/>"],
    "credit"           => ["label"=>"Crédit",            "icon"=>"<rect x=\"1\" y=\"4\" width=\"22\" height=\"16\" rx=\"2\" ry=\"2\"/><line x1=\"1\" y1=\"10\" x2=\"23\" y2=\"10\"/>"],
    "demande_chequier" => ["label"=>"Demande Chéquier",  "icon"=>"<path d=\"M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z\"/><polyline points=\"14 2 14 8 20 8\"/><line x1=\"16\" y1=\"13\" x2=\"8\" y2=\"13\"/><line x1=\"16\" y1=\"17\" x2=\"8\" y2=\"17\"/><polyline points=\"10 9 9 9 8 9\"/>"],
    "dons_collectes"   => ["label"=>"Dons Collectés",   "icon"=>"<path d=\"M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z\"/>"],
    "utilisateurs"     => ["label"=>"Utilisateurs",     "icon"=>"<path d=\"M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M23 21v-2a4 4 0 00-3-3.87\"/><path d=\"M16 3.13a4 4 0 010 7.75\"/>"],
    "statistiques"     => ["label"=>"Statistiques",     "icon"=>"<line x1=\"18\" y1=\"20\" x2=\"18\" y2=\"10\"/><line x1=\"12\" y1=\"20\" x2=\"12\" y2=\"4\"/><line x1=\"6\" y1=\"20\" x2=\"6\" y2=\"14\"/>"],
    "audit"            => ["label"=>"Journal d\'Audit", "icon"=>"<path d=\"M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z\"/><polyline points=\"14 2 14 8 20 8\"/><line x1=\"16\" y1=\"13\" x2=\"8\" y2=\"13\"/><line x1=\"16\" y1=\"17\" x2=\"8\" y2=\"17\"/><polyline points=\"10 9 9 9 8 9\"/>"],
];

$permFile_sidebar = __DIR__ . "/../../../models/admin_permissions.json";
$perms_sidebar = file_exists($permFile_sidebar) ? json_decode(file_get_contents($permFile_sidebar), true) : [];

$myModules_sidebar = ($currentUserRole_sidebar === "SUPER_ADMIN") ? array_keys($ALL_MODULES_SIDEBAR) : ($perms_sidebar[$currentUserId_sidebar] ?? []);

$currentFile_sidebar = basename($_SERVER["PHP_SELF"]);
$page_sidebar = $_GET["page"] ?? "dashboard";
$tab_sidebar  = $_GET["tab"] ?? "comptes";

// Fix pending logic for comptes dropdown
if(!isset($pendingTotal)){
    $pendingTotal = 0;
    if($currentFile_sidebar === "backoffice_compte.php") {
        global $pendingComptes, $pendingCartes;
        $pendingTotal = (is_array($pendingComptes) ? count($pendingComptes) : 0) + (is_array($pendingCartes) ? count($pendingCartes) : 0);
    }
}
?>
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-env">ADMIN</div>
  </div>
  <div class="sb-admin">
    <div class="sb-av"><?= $adminInitials_sidebar ?></div>
    <div>
      <div class="sb-aname"><?= htmlspecialchars(Session::get("user_nom")." ".Session::get("user_prenom")) ?></div>
      <div class="sb-arole"><?= Session::get("role") ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Principal</div>
    <a href="backoffice_utilisateur.php?page=utilisateurs&filtre=tous" class="nav-item <?= ($currentFile_sidebar==="backoffice_utilisateur.php" && $page_sidebar==="utilisateurs")?"active":"" ?> <?= !in_array("utilisateurs",$myModules_sidebar)?"disabled":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES_SIDEBAR["utilisateurs"]["icon"] ?></svg>
      Utilisateurs
      <?php if($kyc_count>0): ?><span class="nav-badge"><?= $kyc_count ?></span><?php endif; ?>
    </a>
    <a href="backoffice_utilisateur.php?page=statistiques" class="nav-item <?= ($currentFile_sidebar==="backoffice_utilisateur.php" && $page_sidebar==="statistiques")?"active":"" ?> <?= !in_array("statistiques",$myModules_sidebar)?"disabled":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES_SIDEBAR["statistiques"]["icon"] ?></svg>
      Statistiques
    </a>
    
    <!-- Comptes Dropdown -->
    <div class="nav-dropdown <?= ($currentFile_sidebar === "backoffice_compte.php" || $page_sidebar === "comptes") ? "open" : "" ?>" id="dropdown-compte-admin">
      <div class="nav-item nav-dropdown-toggle <?= !in_array("comptes",$myModules_sidebar)?"disabled":"" ?>" 
           onclick="toggleDropdown('dropdown-compte-admin')"
           style="cursor:pointer; user-select:none;">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES_SIDEBAR["comptes"]["icon"] ?></svg>
        <span style="flex:1">Comptes & Cartes</span>
        <svg class="nav-chevron" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="nav-dropdown-menu">
        <a class="nav-sub-item <?= ($currentFile_sidebar === "backoffice_compte.php" && $tab_sidebar==="comptes")?"active":"" ?>" href="backoffice_compte.php?tab=comptes">
          <span class="nav-sub-dot"></span>Liste des comptes
        </a>
        <a class="nav-sub-item <?= ($currentFile_sidebar === "backoffice_compte.php" && $tab_sidebar==="attente")?"active":"" ?>" href="backoffice_compte.php?tab=attente">
          <span class="nav-sub-dot"></span>En attente
          <?php if ($pendingTotal > 0): ?>
            <span style="margin-left:auto;background:rgba(245,158,11,.2);color:var(--amber);border-radius:99px;padding:1px 7px;font-size:.62rem;font-weight:600"><?= $pendingTotal ?></span>
          <?php endif; ?>
        </a>
        <a class="nav-sub-item <?= ($currentFile_sidebar === "backoffice_compte.php" && $tab_sidebar==="stats")?"active":"" ?>" href="backoffice_compte.php?tab=stats">
          <span class="nav-sub-dot"></span>Statistiques
        </a>
      </div>
    </div>

    <?php foreach(["actions","credit","demande_chequier","dons_collectes"] as $mk): ?>
    <a href="#" class="nav-item <?= !in_array($mk,$myModules_sidebar)?"disabled":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES_SIDEBAR[$mk]["icon"] ?></svg>
      <?= $ALL_MODULES_SIDEBAR[$mk]["label"] ?>
    </a>
    <?php endforeach; ?>
    

    <div class="nav-section">Paramètres</div>
    <a href="backoffice_utilisateur.php?page=profil" class="nav-item <?= ($currentFile_sidebar==="backoffice_utilisateur.php" && $page_sidebar==="profil")?"active":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil Admin
    </a>
    <?php if($currentUserRole_sidebar==="SUPER_ADMIN"): ?>
    <a href="backoffice_utilisateur.php?page=permissions" class="nav-item <?= ($currentFile_sidebar==="backoffice_utilisateur.php" && $page_sidebar==="permissions")?"active":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Permissions
    </a>
    <?php endif; ?>
    
    <?php if(in_array("audit", $myModules_sidebar)): ?>
    <a href="backoffice_utilisateur.php?page=audit" class="nav-item <?= ($currentFile_sidebar==="backoffice_utilisateur.php" && $page_sidebar==="audit")?"active":"" ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $ALL_MODULES_SIDEBAR["audit"]["icon"] ?></svg>
      Journal d'Audit
    </a>
    <?php endif; ?>
  </nav>

    <script>
    if(typeof toggleDropdown !== 'function') {
      function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('open');
      }
    }
    </script>

  <div class="sb-footer">
    <div class="sb-status"><div class="status-dot"></div>Système opérationnel</div>
    <a href="../../controllers/AuthController.php?action=logout" style="display:flex;align-items:center;gap:.5rem;font-size:.72rem;color:#475569;text-decoration:none;margin-top:.6rem;">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Déconnexion
    </a>
  </div>
</div>