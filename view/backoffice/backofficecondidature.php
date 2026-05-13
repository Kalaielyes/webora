<?php
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../models/config.php';
require_once __DIR__ . '/../../models/investissement_projet.php';
Session::start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>BankFlow Admin — Gestion des Projets</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="action.css">
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<style>
/* ── Project CRUD Modal ───────────────────────────────────── */
.pm-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(15,23,42,.7);backdrop-filter:blur(5px);
  z-index:9000;align-items:center;justify-content:center;padding:1.5rem;
}
.pm-overlay.active{display:flex;}
.pm-card{
  width:min(580px,100%);background:#fff;
  border-radius:16px;box-shadow:0 30px 80px rgba(15,23,42,.25);
  overflow:hidden;
}
.pm-head{
  background:linear-gradient(135deg,#2563EB,#0D9488);
  padding:1.1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;
}
.pm-head-title{font-family:var(--fh);font-size:1rem;font-weight:700;color:#fff;}
.pm-close{
  background:rgba(255,255,255,.15);border:none;color:#fff;
  width:28px;height:28px;border-radius:7px;cursor:pointer;font-size:1.1rem;
  display:flex;align-items:center;justify-content:center;transition:background .15s;
}
.pm-close:hover{background:rgba(255,255,255,.3);}
.pm-body{padding:1.3rem 1.4rem;display:flex;flex-direction:column;gap:.9rem;}
.pm-row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
.pm-field{display:flex;flex-direction:column;gap:.35rem;}
.pm-label{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}
.pm-input{border:1.5px solid var(--border2);border-radius:8px;padding:.5rem .75rem;font-size:.83rem;font-family:var(--fb);color:var(--text);outline:none;transition:border-color .15s;}
.pm-input:focus{border-color:var(--blue);}
textarea.pm-input{resize:vertical;min-height:80px;}
.pm-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .7rem center;background-size:12px;padding-right:2rem;cursor:pointer;}
.pm-footer{padding:.9rem 1.4rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.6rem;background:var(--bg);}
.pm-btn-cancel{background:transparent;border:1.5px solid var(--border2);border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:500;cursor:pointer;font-family:var(--fb);color:var(--muted);transition:all .15s;}
.pm-btn-cancel:hover{background:var(--border);}
.pm-btn-save{background:var(--blue);border:none;border-radius:8px;padding:.45rem 1.2rem;font-size:.8rem;font-weight:600;cursor:pointer;font-family:var(--fb);color:#fff;transition:background .15s;}
.pm-btn-save:hover{background:#1D4ED8;}
.pm-error{font-size:.75rem;color:var(--rose);padding:.4rem .6rem;background:var(--rose-light);border-radius:6px;display:none;}
.pm-field-err{font-size:.68rem;color:var(--rose);margin-top:.2rem;display:none;}
.pm-input.invalid{border-color:var(--rose);background:var(--rose-light);}
/* Delete confirm modal */
.del-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(15,23,42,.7);backdrop-filter:blur(5px);
  z-index:9100;align-items:center;justify-content:center;padding:1.5rem;
}
.del-overlay.active{display:flex;}
.del-card{
  width:min(380px,100%);background:#fff;border-radius:14px;
  box-shadow:0 20px 60px rgba(15,23,42,.25);padding:1.5rem;
  display:flex;flex-direction:column;gap:1rem;
}
.del-icon{width:44px;height:44px;border-radius:12px;background:var(--rose-light);display:flex;align-items:center;justify-content:center;}
.del-title{font-size:.92rem;font-weight:700;}
.del-sub{font-size:.78rem;color:var(--muted);line-height:1.5;}
.del-actions{display:flex;gap:.6rem;justify-content:flex-end;}
.del-btn-cancel{background:transparent;border:1.5px solid var(--border2);border-radius:8px;padding:.4rem .9rem;font-size:.78rem;font-weight:500;cursor:pointer;font-family:var(--fb);}
.del-btn-confirm{background:var(--rose);border:none;border-radius:8px;padding:.4rem .9rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--fb);color:#fff;}
.act-btn.edit:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
.btn-add-project{
  background:var(--blue);color:#fff;border:none;border-radius:8px;
  padding:.4rem 1rem;font-size:.8rem;font-weight:600;cursor:pointer;
  font-family:var(--fb);display:flex;align-items:center;gap:.4rem;transition:background .15s;
}
.btn-add-project:hover{background:#1D4ED8;}
.ppm-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(15,23,42,.7);backdrop-filter:blur(5px);
  z-index:9200;align-items:center;justify-content:center;padding:1.5rem;
}
.ppm-overlay.active{display:flex;}
.ppm-card{width:min(540px,100%);background:#fff;border-radius:16px;box-shadow:0 25px 70px rgba(15,23,42,.28);overflow:hidden;}
.ppm-head{background:linear-gradient(135deg,#0EA5E9,#2563EB);padding:1rem 1.3rem;display:flex;align-items:center;justify-content:space-between;}
.ppm-title{font-family:var(--fh);font-size:.98rem;font-weight:700;color:#fff;}
.ppm-close{background:rgba(255,255,255,.15);border:none;color:#fff;width:28px;height:28px;border-radius:7px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;}
.ppm-body{padding:1.1rem 1.3rem;display:flex;flex-direction:column;gap:.8rem;}
.ppm-field{display:flex;flex-direction:column;gap:.35rem;}
.ppm-label{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}
.ppm-input{border:1.5px solid var(--border2);border-radius:8px;padding:.5rem .72rem;font-size:.83rem;font-family:var(--fb);color:var(--text);outline:none;}
.ppm-input:focus{border-color:var(--blue);}
.ppm-footer{padding:.9rem 1.3rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.6rem;background:var(--bg);}
.ppm-error{font-size:.75rem;color:var(--rose);padding:.4rem .6rem;background:var(--rose-light);border-radius:6px;display:none;}
.act-btn.progress-btn{width:auto;padding:.35rem .55rem;gap:.35rem;font-size:.72rem;font-weight:600;}
</style>
</head>
<body>
<?php
$pdo = Config::getConnexion();
$projects = [];

try {
    $stmt = $pdo->query("
        SELECT p.id_projet, p.titre, p.description, p.montant_objectif, p.secteur,
               p.date_limite, p.date_creation, p.status, p.taux_rentabilite, p.temps_retour_brut,
               COALESCE(pp.pourcentage, 0) AS progress_value,
               COALESCE(pp.description, '') AS progress_description,
               pp.date_update AS progress_updated_at,
               u.nom AS createur_nom,
               COALESCE((SELECT SUM(i.montant_investi) FROM investissement i WHERE i.id_projet = p.id_projet AND i.status = 'VALIDE'), 0) AS total_investi
        FROM projet p
        LEFT JOIN utilisateur u ON p.id_createur = u.id
        LEFT JOIN (
          SELECT pp1.projet_id, pp1.pourcentage, pp1.description, pp1.date_update
          FROM projet_progress pp1
          INNER JOIN (
            SELECT projet_id, MAX(id) AS max_id
            FROM projet_progress
            GROUP BY projet_id
          ) latest ON latest.projet_id = pp1.projet_id AND latest.max_id = pp1.id
        ) pp ON pp.projet_id = p.id_projet
        ORDER BY p.date_creation DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($projects as &$project) {
        $project['montant_restant'] = max(0, $project['montant_objectif'] - $project['total_investi']);
        $project['progression']    = $project['montant_objectif'] > 0
            ? round(($project['total_investi'] / $project['montant_objectif']) * 100, 1) : 0;
    }
    unset($project);
} catch (Exception $e) {
    $projects = [];
}
?>
<?php include __DIR__ . '/partials/sidebar_unified.php'; ?>
<div class="main">

  <div class="topbar">
    <div class="tb-left">
      <div class="page-title">Gestion des Projets</div>
      <div class="breadcrumb">Admin / Projets</div>
    </div>
    <div class="tb-right">
      <button class="btn-add-project" id="btn-add-project">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Ajout Projet
      </button>
      <div class="search-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input id="input-search-projects" placeholder="Rechercher un projet…"/>
      </div>
    </div>
  </div>
  <div class="content">
    <div class="two-col-layout">
      <div class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-title">Liste des Projets</div>
        </div>
        <table id="projects-table">
          <thead>
            <tr>
              <th>Projet</th><th>Secteur</th><th>Date soumis</th><th>Objectif</th><th>Collecté</th><th>Restant</th><th>Progression</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="projects-tbody">
            <?php if (empty($projects)): ?>
              <tr>
                <td colspan="8" style="text-align:center;color:rgba(148,163,184,1);padding:2rem 0;">Aucun projet trouvé dans la base de données.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($projects as $project): ?>
                <?php
                  $collected  = (float)($project['total_investi']   ?? 0);
                  $remaining  = (float)($project['montant_restant'] ?? $project['montant_objectif']);
                  $trackingProgress = (float)($project['progress_value'] ?? 0);
                  $financialProgress = (float)($project['progression']    ?? 0);
                  $progress   = $trackingProgress; // Show tracking progress in the main column
                  $sectorClass = strtolower(str_replace(['é','è','ê',' '], ['e','e','e',''], $project['secteur']));
                  $dateCreation = $project['date_creation'] ? date('d/m/Y', strtotime($project['date_creation'])) : '—';
                ?>
                <tr class="project-row" 
                    data-project-id="<?= $project['id_projet'] ?>" 
                    data-name="<?= htmlspecialchars(strtolower($project['titre']), ENT_QUOTES) ?>"
                    data-project-name="<?= htmlspecialchars($project['titre'], ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?>"
                    data-creator="<?= htmlspecialchars($project['createur_nom'] ?: 'N/A', ENT_QUOTES) ?>"
                    data-sector="<?= htmlspecialchars($project['secteur'], ENT_QUOTES) ?>"
                    data-date-creation="<?= $dateCreation ?>"
                    data-date-limite="<?= htmlspecialchars($project['date_limite'] ?? '', ENT_QUOTES) ?>"
                    data-objectif="<?= htmlspecialchars((string)$project['montant_objectif'], ENT_QUOTES) ?>"
                    data-taux="<?= htmlspecialchars((string)($project['taux_rentabilite'] ?? 0), ENT_QUOTES) ?>"
                    data-temps="<?= htmlspecialchars((string)($project['temps_retour_brut'] ?? 0), ENT_QUOTES) ?>"
                    data-col="<?= htmlspecialchars((string)$collected, ENT_QUOTES) ?>"
                    data-rest="<?= htmlspecialchars((string)$remaining, ENT_QUOTES) ?>"
                    data-prog="<?= htmlspecialchars((string)$progress, ENT_QUOTES) ?>"
                    data-suivi-pourcentage="<?= htmlspecialchars((string)($project['progress_value'] ?? 0), ENT_QUOTES) ?>"
                    data-suivi-description="<?= htmlspecialchars($project['progress_description'] ?? '', ENT_QUOTES) ?>"
                    data-suivi-date="<?= htmlspecialchars($project['progress_updated_at'] ?? '', ENT_QUOTES) ?>"
                >
                  <td><div class="td-name"><?= htmlspecialchars($project['titre']) ?></div><div class="td-sub">Par: <?= htmlspecialchars($project['createur_nom'] ?: 'N/A') ?></div></td>
                  <td><span class="t-<?= htmlspecialchars($sectorClass) ?>"><?= htmlspecialchars($project['secteur']) ?></span></td>
                  <td><span class="td-mono"><?= $dateCreation ?></span></td>
                  <td><span class="td-mono"><?= number_format((float)$project['montant_objectif'], 0, ',', ' ') ?></span></td>
                  <td>
                    <span class="td-mono" style="font-weight:500;color:var(--green)"><?= number_format($collected, 0, ',', ' ') ?></span>
                    <div style="font-size:0.65rem;color:var(--muted);margin-top:2px;">(<?= $financialProgress ?>% collectés)</div>
                  </td>
                  <td><span class="td-mono" style="color:var(--amber)"><?= number_format($remaining, 0, ',', ' ') ?></span></td>
                  <td><div class="progress-wrap"><div class="progress-bar"><div class="progress-fill" style="width:<?= min(100,$progress) ?>%;background:var(--<?= $progress>=100?'green':($progress>50?'blue':'amber') ?>)"></div></div><span class="progress-pct"><?= $progress ?>%</span></div></td>
                  <td><div class="action-group">
                    <button class="act-btn edit progress-btn" title="Suivi du projet" data-action="progress"
                      data-id="<?= $project['id_projet'] ?>"
                      data-progress="<?= htmlspecialchars((string)($project['progress_value'] ?? 0), ENT_QUOTES) ?>"
                      data-progress-description="<?= htmlspecialchars($project['progress_description'] ?? '', ENT_QUOTES) ?>"
                      data-updated-at="<?= htmlspecialchars($project['progress_updated_at'] ?? '', ENT_QUOTES) ?>">
                      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                      Suivi
                    </button>
                    <button class="act-btn edit" title="Modifier" data-action="edit"
                      data-id="<?= $project['id_projet'] ?>"
                      data-titre="<?= htmlspecialchars($project['titre'], ENT_QUOTES) ?>"
                      data-secteur="<?= htmlspecialchars($project['secteur'], ENT_QUOTES) ?>"
                      data-description="<?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?>"
                      data-montant="<?= htmlspecialchars((string)$project['montant_objectif'], ENT_QUOTES) ?>"
                      data-datelimite="<?= htmlspecialchars($project['date_limite'] ?? '', ENT_QUOTES) ?>"
                      data-datesoumis="<?= htmlspecialchars($project['date_creation'] ?? '', ENT_QUOTES) ?>"
                      data-taux="<?= htmlspecialchars((string)($project['taux_rentabilite'] ?? 0), ENT_QUOTES) ?>"
                      data-temps="<?= htmlspecialchars((string)($project['temps_retour_brut'] ?? 0), ENT_QUOTES) ?>"
                      data-status="<?= htmlspecialchars($project['status'], ENT_QUOTES) ?>">
                      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="act-btn danger" title="Supprimer" data-action="delete" data-id="<?= $project['id_projet'] ?>" data-titre="<?= htmlspecialchars($project['titre'], ENT_QUOTES) ?>"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
                  </div></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="detail-panel">
        <div class="dp-header">
          <div>
            <div class="dp-title" id="dp-title">Sélectionnez un projet</div>
          </div>
        </div>
        <div>
          <div class="dp-section">Détails</div>
          <div id="dp-description" style="font-size:.8rem;color:var(--text2);line-height:1.5;padding:.5rem 0;white-space:pre-wrap;">—</div>
        </div>
        <div>
          <div class="dp-section">Informations projet</div>
          <div class="dp-row"><span class="dp-key">Créateur</span><span class="dp-val" id="dp-creator">N/A</span></div>
          <div class="dp-row"><span class="dp-key">Secteur</span><span class="dp-val" id="dp-secteur">—</span></div>
          <div class="dp-row"><span class="dp-key">Date création</span><span class="dp-val" id="dp-date-creation">—</span></div>
          <div class="dp-row"><span class="dp-key">Date limite</span><span class="dp-val" id="dp-date-limite">—</span></div>
          <div class="dp-row"><span class="dp-key">Objectif</span><span class="dp-val" id="dp-objectif">—</span></div>
          <div class="dp-row"><span class="dp-key">TRI</span><span class="dp-val" id="dp-taux">0%</span></div>
          <div class="dp-row"><span class="dp-key">Retour Brut</span><span class="dp-val" id="dp-temps">0 mois</span></div>
        </div>
        <div>
          <div class="dp-section">Collecte</div>
          <div class="dp-row"><span class="dp-key">Montant collecté</span><span class="dp-val" id="dp-collect">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Restant</span><span class="dp-val" id="dp-restant">0 TND</span></div>
          <div class="dp-row"><span class="dp-key">Progression</span><div class="progress-wrap"><div class="progress-bar"><div id="dp-progress-fill" class="progress-fill" style="width:0%;background:var(--green)"></div></div><span id="dp-progress-pct" class="progress-pct">0%</span></div></div>
        </div>
        <div>
          <div class="dp-section">Suivi</div>
          <div class="dp-row"><span class="dp-key">Avancement déclaré</span><span class="dp-val" id="dp-suivi-pourcentage">0%</span></div>
          <div class="dp-row"><span class="dp-key">Dernière mise à jour</span><span class="dp-val" id="dp-suivi-date">Jamais</span></div>
          <div id="dp-suivi-desc" style="font-size:.78rem;color:var(--text2);line-height:1.5;padding:.45rem 0;white-space:pre-wrap;">Aucun suivi disponible.</div>
        </div>
        <div class="dp-actions">
          <button class="dp-action-btn da-neutral" id="btn-export">Exporter le rapport</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ PROJET MODAL (Add / Edit) ══════════ -->
<div id="pm-overlay" class="pm-overlay" role="dialog" aria-modal="true">
  <div class="pm-card">
    <div class="pm-head">
      <div class="pm-head-title" id="pm-modal-title">Nouveau Projet</div>
      <button class="pm-close" id="pm-close-btn" aria-label="Fermer">×</button>
    </div>
    <div class="pm-body">
      <div id="pm-error" class="pm-error"></div>

      <!-- Date de soumission (shown in edit mode only) -->
      <div id="pm-datesoumis-row" class="pm-field" style="display:none;background:var(--bg3);border-radius:8px;padding:.5rem .75rem;flex-direction:row;align-items:center;gap:.5rem;">
        <svg width="13" height="13" fill="none" stroke="var(--muted)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span class="pm-label" style="margin:0">Date de soumission :</span>
        <span id="pm-datesoumis-val" style="font-size:.82rem;font-family:var(--fm);color:var(--text)"></span>
      </div>

      <div class="pm-row">
        <div class="pm-field" style="grid-column:1/-1">
          <label class="pm-label">Titre du projet <span style="color:var(--rose)">*</span></label>
          <input class="pm-input" id="pm-titre" type="text" placeholder="Ex: Startup FinTech Tunis" maxlength="200" />
          <span class="pm-field-err" id="err-titre">Le titre est obligatoire (3 caractères minimum).</span>
        </div>
      </div>
      <div class="pm-row">
        <div class="pm-field">
          <label class="pm-label">Secteur <span style="color:var(--rose)">*</span></label>
          <select class="pm-input pm-select" id="pm-secteur">
            <option value="">— Choisir —</option>
            <option value="Énergie">Énergie</option>
            <option value="Tech">Tech</option>
            <option value="Immobilier">Immobilier</option>
            <option value="Santé">Santé</option>
            <option value="Agriculture">Agriculture</option>
          </select>
          <span class="pm-field-err" id="err-secteur">Veuillez choisir un secteur.</span>
        </div>
        <div class="pm-field">
          <label class="pm-label">Objectif de collecte (TND) <span style="color:var(--rose)">*</span></label>
          <input class="pm-input" id="pm-montant" type="number" placeholder="Ex: 50000" min="1" step="100" />
          <span class="pm-field-err" id="err-montant">L'objectif doit être > 0.</span>
        </div>
      </div>
      <div class="pm-row">
        <div class="pm-field" style="grid-column:1/-1">
          <label class="pm-label">Date Limite de Collecte <span style="color:var(--rose)">*</span></label>
          <input class="pm-input" id="pm-datelimite" type="date" />
          <span class="pm-field-err" id="err-datelimite">La date limite doit être dans le futur.</span>
        </div>
      </div>
      <div class="pm-row">
        <div class="pm-field">
          <label class="pm-label">TRI (%)</label>
          <input class="pm-input" id="pm-taux" type="number" placeholder="Ex: 15.5" min="0" step="any" />
        </div>
        <div class="pm-field">
          <label class="pm-label">Retour Brut (mois)</label>
          <input class="pm-input" id="pm-temps" type="number" placeholder="Ex: 24.5" min="0" step="any" />
        </div>
      </div>
      <div class="pm-field">
        <label class="pm-label">Description <span style="color:var(--rose)">*</span></label>
        <textarea class="pm-input" id="pm-description" rows="4" placeholder="Décrivez le projet : objectifs, impact, utilisation des fonds..." maxlength="2000"></textarea>
        <span class="pm-field-err" id="err-description">La description est obligatoire (10 caractères minimum).</span>
      </div>
    </div>
    <div class="pm-footer">
      <button class="pm-btn-cancel" id="pm-cancel-btn">Annuler</button>
      <button class="pm-btn-save" id="pm-save-btn">Enregistrer</button>
    </div>
  </div>
</div>

<!-- ══════════ DELETE CONFIRM MODAL ══════════ -->
<div id="del-overlay" class="del-overlay" role="dialog" aria-modal="true">
  <div class="del-card">
    <div class="del-icon">
      <svg width="20" height="20" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
    </div>
    <div>
      <div class="del-title">Supprimer ce projet ?</div>
      <div class="del-sub" id="del-sub">Cette action est irréversible.</div>
    </div>
    <div class="del-actions">
      <button class="del-btn-cancel" id="del-cancel-btn">Annuler</button>
      <button class="del-btn-confirm" id="del-confirm-btn">Supprimer</button>
    </div>
  </div>
</div>

<div id="ppm-overlay" class="ppm-overlay" role="dialog" aria-modal="true">
  <div class="ppm-card">
    <div class="ppm-head">
      <div class="ppm-title">Suivi du projet</div>
      <button class="ppm-close" id="ppm-close-btn" aria-label="Fermer">×</button>
    </div>
    <div class="ppm-body">
      <div id="ppm-error" class="ppm-error"></div>
      <div class="ppm-field">
        <label class="ppm-label">Progression (%)</label>
        <input id="ppm-progress" class="ppm-input" type="number" min="0" max="100" step="0.1">
      </div>
      <div class="ppm-field">
        <label class="ppm-label">Description</label>
        <textarea id="ppm-progress-description" class="ppm-input" rows="4"></textarea>
      </div>
      <div class="ppm-field">
        <label class="ppm-label">Derniere mise a jour</label>
        <input id="ppm-date-update" class="ppm-input" type="text" readonly>
      </div>
    </div>
    <div class="ppm-footer">
      <button class="pm-btn-cancel" id="ppm-cancel-btn">Annuler</button>
      <button class="pm-btn-save" id="ppm-save-btn">Enregistrer</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const controllerUrl = new URL('../../controller/projet.php', window.location.href).href;
  
  const dp = {
    title: document.getElementById('dp-title'),
    description: document.getElementById('dp-description'),
    creator: document.getElementById('dp-creator'),
    secteur: document.getElementById('dp-secteur'),
    dateCreation: document.getElementById('dp-date-creation'),
    dateLimite: document.getElementById('dp-date-limite'),
    objectif: document.getElementById('dp-objectif'),
    taux: document.getElementById('dp-taux'),
    temps: document.getElementById('dp-temps'),
    collect: document.getElementById('dp-collect'),
    restant: document.getElementById('dp-restant'),
    progressFill: document.getElementById('dp-progress-fill'),
    progressPct: document.getElementById('dp-progress-pct'),
    suiviPct: document.getElementById('dp-suivi-pourcentage'),
    suiviDate: document.getElementById('dp-suivi-date'),
    suiviDesc: document.getElementById('dp-suivi-desc'),
  };
  const selectedProjectData = { value: null };

  const selectRow = (row) => {
    document.querySelectorAll('.project-row.selected').forEach(el => el.classList.remove('selected'));
    if (!row) return;
    row.classList.add('selected');
    
    const d = row.dataset;
    selectedProjectData.value = d;
    dp.title.textContent = row.querySelector('.td-name').textContent || 'Projet';
    dp.description.textContent = d.description || 'Aucune description fournie.';
    dp.creator.textContent = d.creator || 'N/A';
    dp.secteur.textContent = d.sector || '—';
    dp.dateCreation.textContent = d.dateCreation || '—';
    dp.dateLimite.textContent = d.dateLimite ? new Date(d.dateLimite).toLocaleDateString('fr-FR') : '—';
    dp.objectif.textContent = d.objectif ? Number(d.objectif).toLocaleString('fr-FR') + ' TND' : '—';
    dp.taux.textContent = (d.taux || 0) + '%';
    dp.temps.textContent = (d.temps || 0) + ' mois';
    dp.collect.textContent = d.col ? Number(d.col).toLocaleString('fr-FR') + ' TND' : '0 TND';
    dp.restant.textContent = d.rest ? Number(d.rest).toLocaleString('fr-FR') + ' TND' : '0 TND';
    
    const prog = d.prog || '0';
    dp.progressFill.style.width = Math.min(100, prog) + '%';
    dp.progressFill.style.background = prog >= 100 ? 'var(--green)' : (prog > 50 ? 'var(--blue)' : 'var(--amber)');
    dp.progressPct.textContent = prog + '%';
    dp.suiviPct.textContent = `${d.suiviPourcentage || 0}%`;
    dp.suiviDate.textContent = d.suiviDate ? new Date(d.suiviDate).toLocaleString('fr-FR') : 'Jamais';
    dp.suiviDesc.textContent = d.suiviDescription || 'Aucun suivi disponible.';
  };

  document.querySelectorAll('.project-row').forEach(row => {
    row.addEventListener('click', () => selectRow(row));
  });

  // ══════════ PROJECT CRUD ══════════
  const pmOverlay   = document.getElementById('pm-overlay');
  const delOverlay  = document.getElementById('del-overlay');
  const pmTitle     = document.getElementById('pm-modal-title');
  const pmError     = document.getElementById('pm-error');
  const pmTitre     = document.getElementById('pm-titre');
  const pmSecteur   = document.getElementById('pm-secteur');
  const pmMontant   = document.getElementById('pm-montant');
  const pmDate      = document.getElementById('pm-datelimite');
  const pmDesc      = document.getElementById('pm-description');
  const pmSaveBtn   = document.getElementById('pm-save-btn');
  const delSub      = document.getElementById('del-sub');
  const delConfirm  = document.getElementById('del-confirm-btn');
  let   crudMode    = 'create';
  let   crudId      = null;
  let   deleteId    = null;
  let   crudStatus  = 'VALIDE';

  const pmDateSoumisRow = document.getElementById('pm-datesoumis-row');
  const pmDateSoumisVal = document.getElementById('pm-datesoumis-val');
  const pmTaux         = document.getElementById('pm-taux');
  const pmTemps        = document.getElementById('pm-temps');
  const errTitre        = document.getElementById('err-titre');
  const errSecteur      = document.getElementById('err-secteur');
  const errMontant      = document.getElementById('err-montant');
  const errDateLimite   = document.getElementById('err-datelimite');
  const errDesc         = document.getElementById('err-description');
  const ppmOverlay = document.getElementById('ppm-overlay');
  const ppmProgress = document.getElementById('ppm-progress');
  const ppmProgressDescription = document.getElementById('ppm-progress-description');
  const ppmDateUpdate = document.getElementById('ppm-date-update');
  const ppmError = document.getElementById('ppm-error');
  const ppmSaveBtn = document.getElementById('ppm-save-btn');
  let ppmProjectId = null;

  function clearErrors() {
    pmError.style.display = 'none';
    [pmTitre, pmSecteur, pmMontant, pmDate, pmDesc].forEach(el => el.classList.remove('invalid'));
    [errTitre, errSecteur, errMontant, errDateLimite, errDesc].forEach(el => el.style.display = 'none');
  }

  function validateForm() {
    let isValid = true;
    clearErrors();
    if (pmTitre.value.trim().length < 3) { pmTitre.classList.add('invalid'); errTitre.style.display = 'block'; isValid = false; }
    if (!pmSecteur.value) { pmSecteur.classList.add('invalid'); errSecteur.style.display = 'block'; isValid = false; }
    if (!pmMontant.value || pmMontant.value <= 0) { pmMontant.classList.add('invalid'); errMontant.style.display = 'block'; isValid = false; }
    if (!pmDate.value) { pmDate.classList.add('invalid'); errDateLimite.style.display = 'block'; isValid = false; }
    if (pmDesc.value.trim().length < 10) { pmDesc.classList.add('invalid'); errDesc.style.display = 'block'; isValid = false; }
    return isValid;
  }

  function openModal(mode, data = {}) {
    crudMode = mode;
    clearErrors();
    if (mode === 'create') {
      pmTitle.textContent = 'Nouveau Projet';
      pmSaveBtn.textContent = 'Créer le projet';
      pmTitre.value = pmSecteur.value = pmMontant.value = pmDate.value = pmDesc.value = pmTaux.value = pmTemps.value = '';
      pmDateSoumisRow.style.display = 'none';
      crudId = null;
      crudStatus = 'VALIDE';
    } else {
      pmTitle.textContent = 'Modifier le projet';
      pmSaveBtn.textContent = 'Enregistrer';
      crudId = data.id;
      crudStatus = data.status || 'VALIDE';
      pmTitre.value = data.titre || '';
      pmSecteur.value = data.secteur || '';
      pmMontant.value = data.montant || '';
      pmDate.value = data.datelimite ? data.datelimite.split(' ')[0] : '';
      pmDesc.value = data.description || '';
      pmTaux.value = data.taux || '';
      pmTemps.value = data.temps || '';
      if (data.datesoumis) {
        pmDateSoumisVal.textContent = new Date(data.datesoumis).toLocaleDateString('fr-FR');
        pmDateSoumisRow.style.display = 'flex';
      } else { pmDateSoumisRow.style.display = 'none'; }
    }
    pmOverlay.classList.add('active');
  }

  function closeModal() { pmOverlay.classList.remove('active'); }

  document.getElementById('btn-add-project').addEventListener('click', () => openModal('create'));
  document.getElementById('pm-close-btn').addEventListener('click', closeModal);
  document.getElementById('pm-cancel-btn').addEventListener('click', closeModal);

  pmSaveBtn.addEventListener('click', async () => {
    if (!validateForm()) return;
    const payload = new FormData();
    payload.append('action', crudMode === 'create' ? 'admin_create_project' : 'admin_update_project');
    if (crudId) payload.append('project_id', crudId);
    payload.append('titre', pmTitre.value.trim());
    payload.append('secteur', pmSecteur.value);
    payload.append('description', pmDesc.value.trim());
    payload.append('montant', pmMontant.value);
    payload.append('date_limite', pmDate.value);
    payload.append('status', crudStatus);
    payload.append('taux_rentabilite', pmTaux.value || 0);
    payload.append('temps_retour_brut', pmTemps.value || 0);

    try {
      const res = await fetch(controllerUrl, { method: 'POST', body: payload });
      const result = await res.json();
      if (result.success) window.location.reload();
      else { pmError.textContent = result.message || 'Erreur.'; pmError.style.display = 'block'; }
    } catch (err) { pmError.textContent = 'Erreur réseau.'; pmError.style.display = 'block'; }
  });

  document.querySelectorAll('.act-btn.edit').forEach(btn => {
    btn.addEventListener('click', e => { 
      e.stopPropagation(); 
      const row = btn.closest('.project-row');
      if (row) selectRow(row);
      openModal('edit', btn.dataset); 
    });
  });

  document.querySelectorAll('.act-btn.danger').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const row = btn.closest('.project-row');
      if (row) selectRow(row);
      deleteId = btn.dataset.id;
      delSub.textContent = `Supprimer « ${btn.dataset.titre} » ?`;
      delOverlay.classList.add('active');
    });
  });

  function openProgressModal(data = {}) {
    ppmProjectId = data.id || null;
    ppmProgress.value = data.progress || 0;
    ppmProgressDescription.value = data.progressDescription || '';
    ppmDateUpdate.value = data.updatedAt || 'Jamais mis a jour';
    ppmError.style.display = 'none';
    ppmOverlay.classList.add('active');
  }

  function closeProgressModal() { ppmOverlay.classList.remove('active'); }

  document.querySelectorAll('[data-action="progress"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const projectId = btn.dataset.id;
      const payload = new FormData();
      payload.append('action', 'admin_get_project_progress');
      payload.append('project_id', projectId);
      try {
        const res = await fetch(controllerUrl, { method: 'POST', body: payload });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Erreur de chargement.');
        openProgressModal({
          id: projectId,
          progress: result.data.pourcentage || 0,
          progressDescription: result.data.description || '',
          updatedAt: result.data.date_update ? new Date(result.data.date_update).toLocaleString('fr-FR') : 'Jamais mis a jour'
        });
      } catch (err) {
        alert(err.message || 'Erreur reseau.');
      }
    });
  });

  document.getElementById('ppm-close-btn').addEventListener('click', closeProgressModal);
  document.getElementById('ppm-cancel-btn').addEventListener('click', closeProgressModal);
  ppmOverlay.addEventListener('click', (e) => { if (e.target === ppmOverlay) closeProgressModal(); });

  ppmSaveBtn.addEventListener('click', async () => {
    if (!ppmProjectId) return;
    const progressValue = parseFloat(ppmProgress.value || '0');
    if (Number.isNaN(progressValue) || progressValue < 0 || progressValue > 100) {
      ppmError.textContent = 'Le pourcentage doit etre entre 0 et 100.';
      ppmError.style.display = 'block';
      return;
    }
    const payload = new FormData();
    payload.append('action', 'admin_update_project_progress');
    payload.append('project_id', ppmProjectId);
    payload.append('pourcentage', progressValue.toString());
    payload.append('description', ppmProgressDescription.value.trim());
    try {
      const res = await fetch(controllerUrl, { method: 'POST', body: payload });
      const result = await res.json();
      if (!result.success) throw new Error(result.message || 'Erreur de mise a jour.');
      closeProgressModal();
      window.location.reload();
    } catch (err) {
      ppmError.textContent = err.message || 'Erreur reseau.';
      ppmError.style.display = 'block';
    }
  });

  document.getElementById('del-cancel-btn').addEventListener('click', () => delOverlay.classList.remove('active'));
  delConfirm.addEventListener('click', async () => {
    if (!deleteId) return;
    const payload = new FormData();
    payload.append('action', 'admin_delete_project');
    payload.append('project_id', deleteId);
    try {
      const res = await fetch(controllerUrl, { method: 'POST', body: payload });
      const result = await res.json();
      if (result.success) window.location.reload();
      else alert(result.message || 'Erreur.');
    } catch (err) { alert('Erreur réseau.'); }
  });

  document.getElementById('input-search-projects').addEventListener('input', e => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.project-row').forEach(row => {
      row.style.display = row.dataset.name.includes(term) ? '' : 'none';
    });
  });

  document.getElementById('btn-export').addEventListener('click', () => {
    const d = selectedProjectData.value;
    if (!d) {
      alert('Sélectionnez d’abord un projet.');
      return;
    }
    if (!window.jspdf || !window.jspdf.jsPDF) {
      alert('Le module PDF n’est pas chargé.');
      return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const pageWidth = doc.internal.pageSize.getWidth();
    let y = 16;

    const addLine = (label, value) => {
      doc.setFont('helvetica', 'bold');
      doc.text(`${label}:`, 14, y);
      doc.setFont('helvetica', 'normal');
      const text = String(value ?? '—');
      const wrapped = doc.splitTextToSize(text, 125);
      doc.text(wrapped, 62, y);
      y += Math.max(7, wrapped.length * 5);
    };

    const addSection = (title) => {
      y += 2;
      doc.setFillColor(248, 250, 252);
      doc.rect(12, y - 4, pageWidth - 24, 8, 'F');
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(30, 64, 175);
      doc.text(title, 14, y + 1.5);
      y += 9;
      doc.setTextColor(15, 23, 42);
      doc.setFontSize(10);
    };

    // Header/logo style
    doc.setFillColor(15, 23, 42);
    doc.rect(0, 0, pageWidth, 30, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    doc.text('LegalFin', 14, 13);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Rapport de Suivi Projet', 14, 20);
    doc.text(`Édité le ${new Date().toLocaleString('fr-FR')}`, pageWidth - 72, 20);

    y = 40;
    doc.setTextColor(15, 23, 42);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.text(d.projectName || 'Projet', 14, y);
    y += 8;
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');

    addSection('Informations Projet');
    addLine('Créateur', d.creator || 'N/A');
    addLine('Secteur', d.sector || '—');
    addLine('Date création', d.dateCreation || '—');
    addLine('Date limite', d.dateLimite ? new Date(d.dateLimite).toLocaleDateString('fr-FR') : '—');
    addLine('Objectif', `${Number(d.objectif || 0).toLocaleString('fr-FR')} TND`);
    addLine('TRI', `${d.taux || 0}%`);
    addLine('Retour brut', `${d.temps || 0} mois`);

    addSection('Collecte');
    addLine('Montant collecté', `${Number(d.col || 0).toLocaleString('fr-FR')} TND`);
    addLine('Montant restant', `${Number(d.rest || 0).toLocaleString('fr-FR')} TND`);
    addLine('Progression financière', `${d.prog || 0}%`);

    addSection('Suivi du Projet');
    addLine('Pourcentage de suivi', `${d.suiviPourcentage || 0}%`);
    addLine('Dernière mise à jour', d.suiviDate ? new Date(d.suiviDate).toLocaleString('fr-FR') : 'Jamais');
    addLine('Description', d.suiviDescription || 'Aucune description');

    addSection('Description détaillée');
    addLine('Projet', d.description || 'Aucune description');

    doc.setFontSize(8.5);
    doc.setTextColor(100, 116, 139);
    doc.text('Document généré automatiquement par LegalFin Administration.', 14, 286);

    const safeTitle = (d.projectName || 'projet').replace(/[^\w\-]+/g, '_');
    doc.save(`Rapport_Suivi_${safeTitle}.pdf`);
  });


  const firstRow = document.querySelector('.project-row');
  if (firstRow) selectRow(firstRow);
});
</script>
</body>
</html>