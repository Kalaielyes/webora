<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>MonEspace — Mes Actions</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="action.css">
<style>
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .modal-overlay.active {
    display: flex;
  }
  .modal-card {
    width: min(560px, 100%);
    background: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 24px;
    box-shadow: 0 30px 75px rgba(15, 23, 42, 0.35);
    padding: 1.3rem;
  }
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
  }
  .modal-close {
    background: transparent;
    border: none;
    color: rgba(241, 245, 249, 0.9);
    font-size: 1.25rem;
    cursor: pointer;
  }
  .modal-row {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .amount-chip {
    cursor: pointer;
  }
  .amount-chip.selected {
    background: rgba(37, 99, 235, 1);
    color: #fff;
    border-color: rgba(37, 99, 235, 1);
  }
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
  }
  .modal-error {
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: none;
  }
  .modal-success {
    color: #22c55e;
    font-size: 0.95rem;
    margin-top: 0.5rem;
    display: none;
  }
</style>
</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace Client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">AB</div>
    <div>
      <div class="sb-uname">Ahmed Ben Ali</div>
      <div class="sb-uemail">ahmed@email.com</div>
    </div>
  </div>
  <div class="sb-nav">
    <div class="nav-section">Mon espace</div>
    <a class="nav-item" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Tableau de bord</a>
    <a class="nav-item" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>Mon compte</a>
    <a class="nav-item active" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>Mes actions</a>
    <a class="nav-item" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>Virements</a>
    <div class="nav-section">Autres</div>
    <a class="nav-item" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>Historique</a>
    <a class="nav-item" href="#"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/></svg>Paramètres</a>
  </div>
  <div class="sb-footer">
    <div class="badge-kyc"><div class="dot-pulse"></div> KYC Vérifié</div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes Actions</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="content">
    <!-- TABS -->
    <div class="tab-row">
      <div class="tab-pill active" data-tab="projets">Projets disponibles</div>
      <div class="tab-pill" data-tab="invest">Mes investissements</div>
      <div class="tab-pill" data-tab="add">Demande d'un projet</div>
    </div>

    <!-- ===== TAB: PROJETS DISPONIBLES ===== -->
    <div class="tab-panel active" id="panel-projets">
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Projets ouverts à l'investissement</div>
        <button class="btn-ghost">Filtrer</button>
      </div>
      <div class="projects-grid" id="project-list" style="min-height:260px;">
        <div class="project-card featured" style="background:transparent;border:none;box-shadow:none;color:rgba(148,163,184,1);padding:1.3rem;">
          Chargement des projets depuis la base de données...
        </div>
      </div>
    </div>

    <!-- ===== TAB: MES INVESTISSEMENTS ===== -->
    <div class="tab-panel" id="panel-invest">
      <div class="invest-tab-layout">
        <div>
          <div class="section-head" style="margin-bottom:.9rem">
            <div class="section-title">Mes investissements</div>
            <button class="btn-ghost">Voir tout</button>
          </div>
          <div class="invest-list">
            <div class="invest-item">
              <div class="invest-icon" style="background:rgba(245,158,11,.1)"><svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
              <div class="invest-info"><div class="invest-title">Solar Farm Sfax <span class="invest-tag">Énergie</span></div><div class="invest-meta">Investi le 02 avr. 2025</div></div>
              <div class="invest-right"><div class="invest-amt">25 000 TND</div><div class="invest-status st-valide">✓ Validé</div></div>
            </div>
            
          </div>
        </div>
      </div>
    </div>

    <!-- ===== TAB: DEMANDE D'UN PROJET ===== -->
    <div class="tab-panel" id="panel-add">

      <!-- Success banner (hidden by default) -->
      <div class="success-banner" id="success-banner" style="display:none">
        <div class="success-icon">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div class="success-title">Demande envoyée avec succès !</div>
          <div class="success-sub">Votre demande de projet a été transmise à l'administration. Vous serez notifié dès qu'une décision est prise.</div>
          <div class="success-ref" id="success-ref"></div>
        </div>
      </div>

      <div class="demande-layout" style="display:grid;grid-template-columns:1.2fr .8fr;gap:1rem;">
        <!-- LEFT: Form -->
        <div>
          <div class="section-head" style="margin-bottom:.9rem">
            <div class="section-title">Soumettre un nouveau projet</div>
          </div>
          <form id="demande-form" method="post" action="../../controlller/projet.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_demande" />
            <input type="hidden" name="project_id" id="project_id" value="" />
            <input type="hidden" name="status" id="f-status" value="EN_ATTENTE" />
            <div class="invest-form-card">
              <div class="form-row">
                <div class="form-label">Titre du projet <span style="color:var(--red)">*</span></div>
                <input class="form-input" id="f-titre" name="titre" type="text" placeholder="Ex: Startup FinTech Tunis" required />
              </div>
              <div class="form-row">
                <div class="form-label">Secteur <span style="color:var(--red)">*</span></div>
                <select class="form-input" id="f-secteur" name="secteur" style="cursor:pointer" required>
                  <option value="">— Choisir un secteur —</option>
                  <option value="Énergie">Énergie</option>
                  <option value="Tech">Tech</option>
                  <option value="Santé">Santé</option>
                  <option value="Agriculture">Agriculture</option>
                  <option value="Immobilier">Immobilier</option>
                  <option value="Finance">Finance</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>
              <div class="form-row">
                <div class="form-label">Description <span style="color:var(--red)">*</span></div>
                <textarea class="form-input" id="f-desc" name="description" rows="4" placeholder="Décrivez votre projet en détail : objectifs, impact, utilisation des fonds..." required></textarea>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
                <div class="form-row">
                  <div class="form-label">Montant objectif (TND) <span style="color:var(--red)">*</span></div>
                  <input class="form-input" id="f-montant" name="montant" type="number" min="10000" placeholder="Ex: 150 000" required />
                  <div class="form-hint">Min: 10 000 TND</div>
                </div>
                <div class="form-row">
                  <div class="form-label">Date limite souhaitée <span style="color:var(--red)">*</span></div>
                  <input class="form-input" id="f-date" name="date_limite" type="date" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-label">Documents justificatifs (optionnel)</div>
                <input class="form-input" id="f-documents" name="documents" type="file" accept=".pdf,.doc,.docx" style="padding:.45rem .8rem;cursor:pointer" />
                <div class="form-hint">PDF, DOC — max 10 Mo</div>
              </div>

              <div id="form-error" style="display:none;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.7rem 1rem;font-size:.75rem;color:var(--red);margin-bottom:.8rem"></div>

              <div class="form-actions" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-top:1rem">
                <button class="form-submit" id="btn-submit-demande" type="submit">
                  Soumettre la demande
                </button>
                <button class="form-submit" id="btn-update-demande" type="button" style="display:none;background:rgba(37,99,235,1);border-color:rgba(37,99,235,1);">
                  Modifier la demande
                </button>
                <button class="btn-ghost" id="btn-cancel-edit" type="button" style="display:none;">
                  Annuler
                </button>
              </div>
              
            </div>
          </form>
        </div>

        <div style="background:rgba(15,23,42,.92);border:1px solid rgba(148,163,184,.14);border-radius:18px;padding:1rem;">
          <div class="section-head" style="margin-bottom:.8rem;display:flex;justify-content:space-between;align-items:center;">
            <div class="section-title">Historique</div>
            <div style="font-size:.9rem;color:rgba(148,163,184,1);">Total <span id="req-count">0</span></div>
          </div>
          <div id="requests-list" style="display:grid;gap:.8rem;"></div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<div id="invest-modal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <div class="modal-title">Investir dans un projet</div>
        <div style="font-size:.9rem;color:rgba(148,163,184,1);">Complétez le montant et confirmez.</div>
      </div>
      <button type="button" id="modal-close-btn" class="modal-close" aria-label="Fermer">×</button>
    </div>
    <form id="invest-form" autocomplete="off">
      <input type="hidden" id="investment-action" name="action" value="submit_investment" />
      <input type="hidden" id="investment-id" name="investment_id" value="" />
      <input type="hidden" name="status" value="EN_ATTENTE" />
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Projet sélectionné</div>
          <select id="investment-project-select" class="form-input" name="project_id" required>
            <option value="">— Sélectionner un projet —</option>
          </select>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-label">Montant rapide</div>
        <div class="form-amounts" id="quick-amounts">
          <div class="amount-chip" data-value="500">500</div>
          <div class="amount-chip" data-value="1000">1 000</div>
          <div class="amount-chip" data-value="5000">5 000</div>
          <div class="amount-chip" data-value="10000">10 000</div>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Montant personnalisé (TND)</div>
          <input id="investment-amount" class="form-input" type="number" name="montant" min="500" max="100000" placeholder="Ex: 2 500" required />
          <div class="form-hint">Min: 500 TND · Max: 100 000 TND</div>
        </div>
      </div>
      <div class="modal-row">
        <div class="form-row">
          <div class="form-label">Financer depuis</div>
          <select class="form-input" name="financement_source" style="cursor:pointer">
            <option value="courant">Compte Courant — TN59 0401…3412</option>
            <option value="epargne">Compte Épargne — TN59 0401…8821</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="modal-cancel-btn">Annuler</button>
        <button type="submit" class="form-submit">Confirmer l'investissement</button>
      </div>
      <div id="modal-error" class="modal-error"></div>
      <div id="modal-success" class="modal-success"></div>
    </form>
  </div>
</div>

<script src="script.js?v=2"></script>

</body>
</html>

