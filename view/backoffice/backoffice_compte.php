<?php
/**
 * backoffice_compte.php — ADMIN VIEW
 * • Table of all comptes with filter/search
 * • Detail panel: view, inline-edit compte, inline-edit carte, actions
 * • En attente tab: pending compte + carte requests
 */
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../controller/CompteController.php';
require_once __DIR__ . '/../../controller/CarteController.php';

require_once __DIR__ . '/../../models/Session.php';
Session::requireAdmin("../frontoffice/login.php");

$comptes     = CompteController::findAll();
$kpi_actifs  = CompteController::countByStatut('actif');
$kpi_attente = CompteController::countByStatut('en_attente');
$kpi_bloques = CompteController::countByStatut('bloque');
$kpi_solde   = CompteController::totalSolde();

$selected = null;
$cartes   = [];
if (!empty($_GET['id_compte'])) {
    $selected = CompteController::findById((int)$_GET['id_compte']);
    if ($selected) $cartes = CarteController::findByCompte($selected->getIdCompte());
}

$tab        = $_GET['tab'] ?? 'comptes';
$editCompte = (!empty($_GET['edit']) && $_GET['edit']==='compte' && $selected);
$editCarte  = (!empty($_GET['edit_carte']));
$carteEdit  = null;
if ($editCarte) {
    $carteEdit = CarteController::findById((int)$_GET['edit_carte']);
}

// Pending requests for "En attente" tab
$pendingComptes = array_filter($comptes, fn($r)=>in_array($r['statut'],['en_attente','demande_cloture','demande_suppression','demande_activation_courant']));
$allCartes      = CarteController::findAll();
$pendingCartes  = array_filter($allCartes, fn($c)=>in_array($c->getStatut(),['inactive','demande_cloture','demande_suppression','demande_reactivation']));

// Helpers
function badgeCompte(string $s): string {
    $map=[
        'actif'=>['b-actif','Actif'],
        'bloque'=>['b-bloque','Bloqué'],
        'en_attente'=>['b-attente','En attente'],
        'demande_cloture'=>['b-attente','Dem. clôture'],
        'demande_suppression'=>['b-attente','Dem. supp.'],
        'demande_activation_courant'=>['b-attente','Dem. activation'],
        'cloture'=>['b-cloture','Clôturé']
    ];
    [$cls,$label]=$map[$s]??['b-cloture',ucfirst($s)];
    return "<span class=\"badge {$cls}\"><span class=\"badge-dot\"></span>{$label}</span>";
}
function badgeCarte(string $s): string {
    $map=['active'=>['b-actif','Active'],'inactive'=>['b-cloture','Inactive'],'bloquee'=>['b-bloque','Bloquée'],'expiree'=>['b-cloture','Expirée'],'demande_cloture'=>['b-attente','Dem. supp.'],'demande_blocage'=>['b-attente','Dem. blocage'],'demande_suppression'=>['b-attente','Dem. supp.'],'demande_reactivation'=>['b-attente','Dem. réactiv.']];
    [$cls,$label]=$map[$s]??['b-cloture',ucfirst($s)];
    return "<span class=\"badge {$cls}\"><span class=\"badge-dot\"></span>{$label}</span>";
}
function typeLabel(string $t): string {
    return match($t){
        'courant'=>'<span class="t-courant">Courant</span>',
        'epargne'=>'<span class="t-epargne">Épargne</span>',
        'professionnel'=>'<span class="t-pro">Pro</span>',
        'devise'=>'<span class="t-devise">Devise</span>',
        default=>htmlspecialchars($t),
    };
}
function cmClass(string $style, string $statut=''): string {
    if ($statut==='bloquee') return 'cm-bloque';
    return match($style){'gold'=>'cm-gold','platinum'=>'cm-platinum','titanium'=>'cm-titanium',default=>'cm-standard'};
}
function styleLabel(string $s): string {
    return match($s){'gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium',default=>'Classic'};
}

$adminInitials = strtoupper(substr($_SESSION['user']['prenom']??'A',0,1).substr($_SESSION['user']['nom']??'D',0,1));
$adminNom = htmlspecialchars(($_SESSION['user']['prenom']??'').' '.($_SESSION['user']['nom']??''));
$pendingTotal = count($pendingComptes)+count($pendingCartes);

// -- ERROR & DATA HANDLING FROM PHP POST --
$formErrors = $_SESSION['form_errors'] ?? [];
$formData   = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin Admin — Comptes</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../../assets/css/backoffice/Utilisateur.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/frontoffice/compte.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backoffice/compte.css">
<script>
  (function() {
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
</head>
<body>

<!-- TOP NAVBAR -->
<?php include __DIR__ . '/partials/sidebar_unified.php'; ?>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <?= $tab==='attente' ? 'Demandes en attente' : 'Comptes bancaires' ?>
      <span style="font-size: 0.7rem; color: var(--muted); font-weight: 400; margin-left: 0.5rem; font-family: var(--fb);">/ <?= $tab==='attente' ? 'Validation' : ('Liste' . ($selected?' / #'.$selected->getIdCompte():'')) ?></span>
    </div>
    <div class="topbar-right">
      <?php if ($tab==='comptes'): ?>
      <div class="search-bar" style="margin-right: 0.5rem;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input placeholder="Rechercher IBAN, nom…" id="search-input" oninput="applyFilters()"/>
      </div>
      <?php endif; ?>
      
      
    </div>
  </div>

  <div class="content">
   
  <?php if ($tab==='stats'): ?>
  <?php include __DIR__ . '/partialscomptes/stats.php'; ?>
  <?php elseif ($tab==='attente'): ?>
  <?php include __DIR__ . '/partialscomptes/en_attente.php'; ?>

  <?php elseif ($editCarte && $carteEdit): ?>
  <?php include __DIR__ . '/partialscomptes/edit_carte.php'; ?>
  <?php else: ?>
  <!-- ══ MAIN COMPTES TAB ═══════════════════════════════ -->
  <?php include __DIR__ . '/partialscomptes/comptes_list.php'; ?>
  <?php endif; // tab ?>

  </div><!-- .content -->
</div><!-- .main -->

<!-- ═══ CARD VIEW MODAL ═══════════════════════════════ -->
<div class="card-modal-overlay" id="cardModal" onclick="if(event.target===this)closeCardModal()">
  <div class="card-modal-box">
    <div class="card-modal-header">
      <div>
        <div class="card-modal-title" id="modalTitle">Carte bancaire</div>
        <div class="card-modal-sub" id="modalSub">—</div>
      </div>
      <button class="card-modal-close" onclick="closeCardModal()">✕</button>
    </div>

    <!-- 3D flip card -->
    <div class="modal-card-scene" id="modalCardScene" onclick="this.classList.toggle('flipped')">
      <div class="modal-card-inner">
        <div class="modal-card-face">
          <div class="modal-real-front" id="modalFront">
            <div class="modal-holo"></div>
            <div class="modal-top">
              <div class="modal-chip">
                <div class="modal-chip-grid"><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div><div class="modal-chip-sq"></div></div>
              </div>
              <svg class="modal-contactless" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10"/><path d="M12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6"/><path d="M12 10c1.1 0 2 .9 2 2s-.9 2-2 2"/></svg>
            </div>
            <div class="modal-num" id="modalNum">•••• •••• •••• ••••</div>
            <div class="modal-bot">
              <div>
                <div class="modal-holder-lbl">Card Holder</div>
                <div class="modal-holder-val" id="modalHolder">—</div>
              </div>
              <div>
                <div class="modal-exp-lbl">Expires</div>
                <div class="modal-exp-val" id="modalExp">—</div>
              </div>
              <div id="modalFrontNet"></div>
            </div>
          </div>
        </div>
        <div class="modal-card-face modal-card-face-back">
          <div class="modal-real-back" id="modalBack">
            <div class="modal-stripe"></div>
            <div class="modal-sig-area">
              <div class="modal-sig-lbl">Signature autorisée</div>
              <div class="modal-sig-box">
                <div class="modal-sig-strip"></div>
                <div class="modal-cvv-val" id="modalCvv">•••</div>
              </div>
            </div>
            <div class="modal-back-footer">
              <div class="modal-back-bank">LegalFin</div>
              <div id="modalBackNet"></div>
            </div>
            <div class="modal-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez immédiatement le 71 000 000.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-flip-hint">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
      Cliquez sur la carte pour voir le verso
    </div>

    <div class="modal-info-grid">
      <div class="mi-item"><div class="mi-lbl">Réseau</div><div class="mi-val" id="mi-reseau">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Statut</div><div id="mi-statut">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Plafond paiement/j</div><div class="mi-val" id="mi-ppay">—</div></div>
      <div class="mi-item"><div class="mi-lbl">Plafond retrait/j</div><div class="mi-val" id="mi-pret">—</div></div>
    </div>
  </div>
</div>

<script>
/* ── Sidebar dropdown ── */
function toggleDropdown(id) {
    document.getElementById(id).classList.toggle('open');
}

/* ── Table filters ── */
let currentFilter='tous';
function setFilter(val,btn){
  currentFilter=val;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function applyFilters(){
  const q=(document.getElementById('search-input')?.value||'').toLowerCase();
  document.querySelectorAll('#comptes-table tbody tr').forEach(tr=>{
    const statut=tr.dataset.statut||'';
    const text=tr.textContent.toLowerCase();
    const matchS=currentFilter==='tous'||statut===currentFilter;
    const matchQ=!q||text.includes(q);
    tr.style.display=(matchS&&matchQ)?'':'none';
  });
}

/* ── Card flip (small panel) ── */
function flipDpCard(id){document.getElementById(id).classList.toggle('flipped');}
function flipCard(id){document.getElementById(id).classList.toggle('flipped');}

/* ── Card Modal ── */
const gradMap={
  standard:'linear-gradient(135deg,#1a2a6c,#2563eb,#1e40af)',
  gold:'linear-gradient(135deg,#3d2c00,#8b6914,#c9a227)',
  platinum:'linear-gradient(135deg,#1a1a2e,#374151,#6b7280)',
  titanium:'linear-gradient(135deg,#111111 0%,#2a2a2a 35%,#585858 65%,#8a8a8a 85%,#b0b0b0 100%)',
  bloque:'linear-gradient(135deg,#2d1b1b,#7f1d1d,#991b1b)',
};
const statusLabels={
  active:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F0FDF4;color:#16A34A;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Active</span>',
  demande_blocage:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. blocage</span>',
  inactive:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F1F5F9;color:#64748B;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Inactive</span>',
  bloquee:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FEF2F2;color:#DC2626;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Bloquée</span>',
  expiree:'<span style="display:inline-flex;align-items:center;gap:4px;background:#F1F5F9;color:#64748B;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Expirée</span>',
  demande_cloture:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. supp.</span>',
  demande_suppression:'<span style="display:inline-flex;align-items:center;gap:4px;background:#FFFBEB;color:#D97706;border-radius:99px;padding:2px 8px;font-size:.65rem;font-weight:600">● Dem. supp.</span>',
};
function openCardModal(data){
  if(typeof data==='string') data=JSON.parse(data);
  document.getElementById('modalCardScene').classList.remove('flipped');
  document.getElementById('modalTitle').textContent=data.holder;
  document.getElementById('modalSub').textContent=data.num+' · '+data.style.charAt(0).toUpperCase()+data.style.slice(1);
  document.getElementById('modalNum').textContent=data.num;
  document.getElementById('modalHolder').textContent=data.holder.toUpperCase();
  document.getElementById('modalExp').textContent=data.exp;
  const grad=data.statut==='bloquee'?gradMap.bloque:(gradMap[data.style]||gradMap.standard);
  document.getElementById('modalFront').style.background=grad;
  document.getElementById('modalBack').style.background=grad.replace('135deg','145deg');
  // network
  const visaHtml='<div style="font-family:Syne,sans-serif;font-size:.95rem;font-weight:800;color:rgba(255,255,255,.85);font-style:italic;">VISA</div>';
  const mcHtml='<div style="display:flex;align-items:center;"><div style="width:22px;height:22px;border-radius:50%;background:#eb001b;opacity:.9;"></div><div style="width:22px;height:22px;border-radius:50%;background:#f79e1b;opacity:.9;margin-left:-8px;"></div></div>';
  const visaBackHtml='<div style="font-family:Syne,sans-serif;font-size:.8rem;font-weight:800;color:rgba(255,255,255,.35);font-style:italic;">VISA</div>';
  const mcBackHtml='<div style="display:flex;align-items:center;"><div style="width:18px;height:18px;border-radius:50%;background:#eb001b;opacity:.5;"></div><div style="width:18px;height:18px;border-radius:50%;background:#f79e1b;opacity:.5;margin-left:-7px;"></div></div>';
  document.getElementById('modalFrontNet').innerHTML=data.reseau==='visa'?visaHtml:mcHtml;
  document.getElementById('modalBackNet').innerHTML=data.reseau==='visa'?visaBackHtml:mcBackHtml;
  // info
  document.getElementById('mi-reseau').textContent=data.reseau.toUpperCase();
  document.getElementById('mi-statut').innerHTML=statusLabels[data.statut]||data.statut;
  document.getElementById('mi-ppay').textContent=Number(data.plafondPay).toLocaleString()+' TND';
  document.getElementById('mi-pret').textContent=Number(data.plafondRet).toLocaleString()+' TND';
  if(document.getElementById('modalCvv')) document.getElementById('modalCvv').textContent=data.cvv||'•••';
  document.getElementById('cardModal').classList.add('open');
}
function closeCardModal(){document.getElementById('cardModal').classList.remove('open');}

/* ── Theme & Clock ── */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    var sun = document.getElementById('theme-icon-sun');
    var moon = document.getElementById('theme-icon-moon');
    if (theme === 'light') {
        if (sun) sun.style.display = 'block';
        if (moon) moon.style.display = 'none';
    } else {
        if (sun) sun.style.display = 'none';
        if (moon) moon.style.display = 'block';
    }
}
function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
}
function updateClock() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2, '0');
    var m = String(now.getMinutes()).padStart(2, '0');
    var s = String(now.getSeconds()).padStart(2, '0');
    var el = document.getElementById('real-time-clock');
    if(el) el.textContent = `${h}:${m}:${s}`;
}
function updateGreeting() {
    var hour = new Date().getHours();
    var greeting = "Bonjour";
    if(hour >= 18) greeting = "Bonsoir";
    else if(hour >= 12) greeting = "Bon après-midi";
    var display = document.getElementById('greeting-display');
    if(display) {
        var name = display.querySelector('span').outerHTML;
        display.innerHTML = greeting + ", " + name;
    }
}
setInterval(updateClock, 1000);
updateClock();
updateGreeting();
applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
</script>
</body>
</html>

