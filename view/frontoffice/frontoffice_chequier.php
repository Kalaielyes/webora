<?php
require_once '../../config/config.php';
require_once '../../model/demandechequier.php';
require_once '../../controller/demandechequiercontroller.php';

$demandeC = new DemandeChequierController();

// Suppression d'une demande via le contrôleur
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $demandeC->deleteDemande((int)$_GET['id']);
    header('Location: frontoffice_chequier.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_demande'])) {
    $id_compte = $_POST['compte'] ?? '';
    
    // Fallback automatique
    if (empty($id_compte)) {
        try {
            $db = Config::getConnexion();
            $res = $db->query("SELECT id_Compte FROM comptebancaire LIMIT 1")->fetch();
            if ($res) { $id_compte = $res['id_Compte']; }
        } catch (PDOException $e) {}
    }

    $data = [
        'nom_et_prenom' => $_POST['nom_et_prenom'] ?? '',
        'id_compte' => $id_compte,
        'motif' => $_POST['motif'] ?? '',
        'type_chequier' => $_POST['type_chequier'] ?? 'standard',
        'nombre_cheques' => $_POST['nombre_cheques'] ?? '25',
        'montant_max_par_cheque' => $_POST['montant_max_par_cheque'] ?? 0,
        'mode_reception' => $_POST['mode_reception'] ?? 'agence',
        'adresse_agence' => $_POST['adresse_agence'] ?? ($_POST['adresse_livraison'] ?? ''),
        'telephone' => $_POST['telephone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'commentaire' => $_POST['commentaire'] ?? ''
    ];

    $demande = new DemandeChequier($data);
    $id_edit = $_POST['id_edit'] ?? '';
    // On définit le type d'opération pour le message de succès
    $op_type = empty($id_edit) ? 'add' : 'edit';
    
    try {
        if (!empty($id_edit)) {
            $success = $demandeC->updateDemande($demande, (int)$id_edit);
        } else {
            $success = $demandeC->addDemande($demande);
        }
    } catch (Exception $e) {
        die("Erreur lors de l'opération : " . $e->getMessage());
    }
}

// Récupération de toutes les demandes via le contrôleur
$toutes_les_demandes = $demandeC->listDemandes();

// Calcul des statistiques réelles
$countActifs = 0;
$countRequests = 0;
$totalFeuilles = 0;
foreach($toutes_les_demandes as $d) {
    $statut = trim(strtolower($d['statut'] ?? 'en attente'));
    if ($statut === 'acceptée' || $statut === 'acceptee') {
        $countActifs++;
        $totalFeuilles += (int)$d['nombre_cheques'];
    } elseif ($statut === 'en attente' || empty($statut)) {
        $countRequests++;
    }
}

// Récupération des comptes disponibles pour le formulaire
$db = Config::getConnexion();
$comptes_disponibles = [];
try {
    $stmt = $db->query("SELECT id_Compte, iban FROM comptebancaire");
    $comptes_disponibles = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si la table n'existe pas ou erreur, on laisse vide
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>LegalFin — Mes Chéquiers</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="chequier.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-name">Legal<span>Fin</span></div>
    <div class="sb-logo-tag">Espace Client</div>
  </div>
  <div class="sb-user">
    <div class="sb-av">MN</div>
    <div>
      <div class="sb-uname">Mouna Ncib</div>
      <div class="sb-uemail">mouna.ncib@esprit.tn</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-section">Mon compte</div>
    <a class="nav-item" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Tableau de bord
    </a>
    <a class="nav-item active" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
      Mes chéquiers
      <span class="nav-badge"><?= $countActifs + $countRequests ?></span>
    </a>
    <a class="nav-item" href="../backoffice/backoffice_chequier.php">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      backoffice
    </a>
  </nav>
  <div class="sb-footer">
    <div class="badge-kyc"><span class="dot-pulse"></span>KYC Vérifié</div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">Mes chéquiers</div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
          Chéquiers actifs
        </div>
        <div class="stat-card-val" style="color:var(--blue)"><?= $countActifs ?></div>
        <div class="stat-card-sub">En circulation</div>
      </div>
      <div class="stat-card" style="cursor:pointer; transition: transform 0.2s;" onclick="openModal()" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          Demandes en cours
        </div>
        <div class="stat-card-val" style="color:var(--amber)"><?= (int)$countRequests ?></div>
        <div class="stat-card-sub">Cliquer pour voir la liste</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">
          <svg width="13" height="13" fill="none" stroke="var(--muted)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          Feuilles restantes
        </div>
        <div class="stat-card-val"><?= $totalFeuilles ?></div>
        <div class="stat-card-sub">Sur <?= $countActifs ?> chéquiers</div>
      </div>
    </div>

    <!-- DEMANDE FORM -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Faire une demande de chéquier</div>
      </div>
      <div class="demande-card">
        <div class="info-banner">
          <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          Votre demande sera traitée sous 24 à 48h ouvrables. Vous serez notifié par e-mail dès la validation ou le refus de votre demande.
        </div>

        <?php if (isset($success) && $success): ?>
        <div id="phpSuccessMsg" class="info-banner" style="background:var(--green-light); border-color:var(--green); color:var(--green); margin-bottom:1.5rem;">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
          <?= (isset($op_type) && $op_type === 'edit') ? "Votre demande a été modifiée avec succès !" : "Félicitations ! Votre demande a été enregistrée avec succès dans la base de données." ?>
        </div>

        <?php endif; ?>

        <form method="POST" action="" id="demandeForm" onsubmit="return validerDemande(event)" novalidate>
          <input type="hidden" name="id_edit" id="idEdit" value=""/>
          <div class="form-grid">
            <div class="form-section-label">Informations de la demande</div>
            <div class="form-field">
              <label>ID Demande</label>
              <div class="input-id-wrapper">
                <span class="input-id-prefix">
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  DEM-
                </span>
                <input type="text" name="id_demande" id="idDemande" class="input-with-prefix" placeholder="2024-00001" readonly/>
              </div>
              <div class="field-hint">Généré automatiquement</div>
            </div>
            <div class="form-field">
              <label>Compte associé</label>
              <select name="compte" id="compte">
                <option value="">-- Sélectionner un compte --</option>
                <?php foreach ($comptes_disponibles as $cpte): ?>
                  <option value="<?= htmlspecialchars($cpte['id_Compte']) ?>"><?= htmlspecialchars($cpte['iban']) ?></option>
                <?php endforeach; ?>
              </select>
              <div id="errCompte" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-field">
              <label>Nom et Prénom</label>
              <input type="text" name="nom_et_prenom" id="nomPrenom" placeholder="Votre nom et prénom">
              <div id="errNomPrenom" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>

            <div class="form-field">
              <label>Type de chéquier</label>
              <select name="type_chequier">
                <option value="standard">Standard</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="form-field">
              <label>Nombre de chèques</label>
              <select name="nombre_cheques">
                <option value="25">25 chèques</option>
                <option value="50">50 chèques</option>
              </select>
            </div>
            <div class="form-field">
              <label>Montant max par chèque (TND)</label>
              <input type="text" name="montant_max_par_cheque" id="montantMax" placeholder="Ex : 5000"/>
              <div id="errMontant" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-field full">
              <label>Motif de la demande</label>
              <textarea name="motif" id="motif" placeholder="Ex : Premier chéquier, renouvellement..."></textarea>
              <div id="errMotif" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-section-label">Mode de réception</div>
            <div class="form-field full">
              <label>Mode de réception</label>
              <div class="radio-group" id="modeReceptionGroup">
                <label class="radio-option selected" onclick="selectMode(this,'agence')">
                  <input type="radio" name="mode_reception" value="agence" checked/>
                  <span class="radio-dot"></span>
                  Retrait en agence
                </label>
                <label class="radio-option" onclick="selectMode(this,'livraison')">
                  <input type="radio" name="mode_reception" value="livraison"/>
                  <span class="radio-dot"></span>
                  Livraison à domicile
                </label>
              </div>
            </div>
            <div class="form-field full" id="fieldAdresse">
              <label>Adresse de l'agence</label>
              <input type="text" name="adresse_agence" id="adresseInput" placeholder="Ex : Tunis Centre"/>
              <div id="errAdresse" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-section-label">Coordonnées de contact</div>
            <div class="form-field">
              <label>Téléphone</label>
              <input type="text" name="telephone" id="telephone" placeholder="Ex : 71000000"/>
              <div id="errTelephone" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-field">
              <label>Email</label>
              <input type="text" name="email" id="email" placeholder="Entrez votre email">
              <div id="errEmail" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
            <div class="form-field full">
              <label>Commentaire</label>
              <textarea name="commentaire" id="commentaire" placeholder="Informations supplémentaires..."></textarea>
              <div id="errCommentaire" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
            </div>
          </div>
          <div class="form-submit-row">
            <button type="button" class="btn-ghost" onclick="window.history.back()">Annuler</button>
            <button type="submit" class="btn-primary">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
              Soumettre la demande
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MES CHEQUIERS ACTIFS -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Mes chéquiers</div>
        <button class="btn-ghost" onclick="openAllChequiersModal()">Tout voir</button>
      </div>
      <div class="chq-list">
        <?php 
        $affiche_quelque_chose = false;
        $countShow = 0;
        foreach ($toutes_les_demandes as $d): 
            $s = trim(strtolower($d['statut'] ?? 'en attente'));
            if ($s === 'acceptée' || $s === 'acceptee' || $s === 'refusée'):
                if ($countShow >= 3) continue; // Limit to 3 on main page
                $countShow++;
                $affiche_quelque_chose = true;
                $isRefused = $s === 'refusée';
                $creationDate = new DateTime($d['date_demande']);
                $expDate = clone $creationDate;
                $expDate->modify('+2 years');
                $chqNum = "CHQ-" . $creationDate->format('Y') . "-" . str_pad($d['id_demande'], 5, '0', STR_PAD_LEFT);
                $badgeClass = $isRefused ? 'b-refusee' : 'b-actif';
                $dotColor = $isRefused ? 'var(--rose)' : 'var(--green)';
                $statutLabel = $isRefused ? 'Refusée' : 'Actif';
                
                // Styles pour le refus
                $actionStyle = $isRefused ? 'border-color:var(--rose-light); color:var(--rose); cursor:not-allowed; opacity:0.7;' : '';
                $actionCursor = $isRefused ? 'onclick="return false;"' : '';
        ?>
        <div class="chq-card" <?= $isRefused ? 'style="border-color:var(--rose-light);"' : '' ?>>
          <div class="chq-visual" <?= $isRefused ? 'style="background:linear-gradient(135deg, #fecaca 0%, #ef4444 100%);"' : '' ?>>
            <div class="chq-num"><?= $chqNum ?></div>
            <div class="chq-bottom">
              <div>
                <div class="chq-name"><?= htmlspecialchars($d['nom et prenom']) ?></div>
                <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;">Exp. <?= $expDate->format('d/m/Y') ?></div>
              </div>
              <div style="text-align:right">
                <div class="chq-feuilles-val"><?= htmlspecialchars($d['nombre_cheques']) ?></div>
                <div class="chq-feuilles-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="chq-details">
            <div class="chq-details-top">
              <span class="chq-details-title">Chéquier N° <?= $chqNum ?></span>
              <span class="badge <?= $badgeClass ?>"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= $statutLabel ?></span>
            </div>
            <div class="chq-info-grid">
              <div class="ci-item"><div class="ci-label">ID chéquier</div><div class="ci-val" style="font-family:var(--fm);font-size:.75rem"><?= $chqNum ?></div></div>
              <div class="ci-item"><div class="ci-label">Feuilles restantes</div><div class="ci-val"><?= htmlspecialchars($d['nombre_cheques']) ?> / <?= htmlspecialchars($d['nombre_cheques']) ?></div></div>
              <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val"><?= $creationDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val"><?= $expDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Compte lié</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem"><?= htmlspecialchars($d['iban'] ?: 'Non renseigné') ?></div></div>
              <div class="ci-item"><div class="ci-label">Statut</div><div class="ci-val"><span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= $statutLabel ?></span></div></div>
            </div>
            <div class="chq-actions">
              <button class="chq-act-btn ca-primary" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Détails
              </button>
              <button class="chq-act-btn ca-neutral" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Attestation
              </button>
              <button class="chq-act-btn ca-saisir" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequeModal('$chqNum','".htmlspecialchars($d['nom et prenom'])."','".htmlspecialchars($d['iban'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4M14 15h4"/></svg>
                Saisir un chèque
              </button>
              <button class="chq-act-btn ca-danger" style="<?= $isRefused ? $actionStyle : '' ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Faire opposition
              </button>
            </div>
          </div>
        </div>
        <?php 
            endif;
        endforeach; 

        if (!$affiche_quelque_chose): ?>
            <div class="info-banner" style="grid-column: 1 / -1; justify-content: center; padding: 2rem; color: var(--muted);">
                Vous n'avez pas encore de chéquiers actifs ou refusés.
            </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- MES DEMANDES -->
    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Mes demandes récentes</div>
        <button class="btn-ghost">Historique</button>
      </div>
      <div class="dem-list">
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(245,158,11,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande Standard — 25 chèques — Agence <span class="badge b-standard" style="font-size:.62rem;margin-left:4px;">Standard</span></div>
            <div class="dem-meta">Soumise le 09 avr. 2024 · <span class="dem-id">DEM-2024-00041</span> · Retrait agence · Montant max : 5 000 TND</div>
          </div>
          <span class="badge b-attente"><span class="badge-dot" style="background:var(--amber)"></span>En attente</span>
        </div>
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(34,197,94,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande Urgent — 50 chèques — Livraison <span class="badge b-urgent" style="font-size:.62rem;margin-left:4px;">Urgent</span></div>
            <div class="dem-meta">Acceptée le 10 jan. 2024 · <span class="dem-id">DEM-2024-00012</span> · Livraison domicile · Montant max : 10 000 TND</div>
          </div>
          <span class="badge b-acceptee"><span class="badge-dot" style="background:var(--green)"></span>Acceptée</span>
        </div>
        <div class="dem-item">
          <div class="dem-icon" style="background:rgba(220,38,38,.1)">
            <svg width="16" height="16" fill="none" stroke="var(--rose)" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </div>
          <div class="dem-info">
            <div class="dem-title">Demande Standard — 25 chèques — Agence <span class="badge b-standard" style="font-size:.62rem;margin-left:4px;">Standard</span></div>
            <div class="dem-meta">Refusée le 05 juin 2023 · <span class="dem-id">DEM-2023-00031</span> · Retrait agence · Montant max : 2 000 TND</div>
          </div>
          <span class="badge b-refusee"><span class="badge-dot" style="background:var(--rose)"></span>Refusée</span>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->


<!-- ═══════════════════════════════════════════
     MODAL SAISIE CHÈQUE
════════════════════════════════════════════ -->
<div class="modal-overlay" id="chequeModal">
  <div class="modal-box">

    <!-- En-tête -->
    <div class="modal-header">
      <div>
        <div class="modal-title">Saisir un chèque</div>
        <div class="modal-sub">Chéquier : <span id="modalChequierId" style="font-family:var(--fm);color:var(--blue)"></span></div>
      </div>
      <button class="modal-close" onclick="closeChequeModal()" title="Fermer">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Bannière succès -->
    <div class="success-banner" id="successBanner">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      Chèque émis avec succès !
    </div>

    <!-- ─── APERÇU CHÈQUE BANCAIRE ─── -->
    <div class="cheque-preview">
      <div class="cheque-body">

        <div class="cheque-stripe"></div>

        <div class="cheque-header">
          <div class="cheque-bank">
            <div class="cheque-bank-logo">LF</div>
            <div>
              <div class="cheque-bank-name">LegalFin Bank</div>
              <div class="cheque-bank-address">Av. Habib Bourguiba, Tunis 1000</div>
            </div>
          </div>
          <div class="cheque-meta">
            <div class="cheque-meta-row">
              <span class="cheque-meta-label">ID Chèque</span>
              <span class="cheque-meta-val" id="previewId">CHK-—</span>
            </div>
            <div class="cheque-meta-row">
              <span class="cheque-meta-label">N° Chèque</span>
              <span class="cheque-meta-val" id="previewNmr">NMR-—</span>
            </div>
            <div class="cheque-meta-row">
              <span class="cheque-meta-label">Date d'émission</span>
              <span class="cheque-meta-val" id="previewDate">—</span>
            </div>
            <div class="cheque-meta-row">
              <span class="cheque-meta-label">Agence</span>
              <span class="cheque-meta-val" id="previewAgence">—</span>
            </div>
          </div>
        </div>

        <div class="cheque-row">
          <span class="cheque-row-label">Payez contre ce chèque à l'ordre de</span>
          <span class="cheque-row-val" id="previewBenef">________________________________</span>
        </div>

        <div class="cheque-row">
          <span class="cheque-row-label">La somme de</span>
          <span class="cheque-row-val cheque-lettres" id="previewLettres">________________________________</span>
        </div>

        <div class="cheque-bottom-row">
          <div class="cheque-montant-box">
            <div class="cheque-montant-label">Montant (TND)</div>
            <div class="cheque-montant-val" id="previewMontant">0,000</div>
          </div>
          <div class="cheque-id-box">
            <div class="cheque-id-label">Pièce d'identité bénéficiaire</div>
            <div class="cheque-id-val" id="previewCin">—</div>
          </div>
          <div class="cheque-rib-box">
            <div class="cheque-rib-label">RIB bénéficiaire</div>
            <div class="cheque-rib-val" id="previewRib">—</div>
          </div>
          <div class="cheque-signature">
            <div class="cheque-signature-label">Signature du tireur</div>
            <div class="cheque-signature-line"></div>
            <div class="cheque-signature-name" id="signatureName">Mouna Ncib</div>
          </div>
        </div>

        <div class="cheque-micr">
          <span id="previewMicrNmr">⑆ 000000 ⑆</span>
          <span id="previewMicrRib">⑆ 0401 0050 0712 3412 ⑆</span>
          <span>LegalFin — Espace Client</span>
        </div>

      </div>
    </div>

    <!-- ─── FORMULAIRE DE SAISIE ─── -->
    <form class="cheque-form" onsubmit="return false;">
      <div class="cheque-form-grid">

        <!-- Section : Informations du chèque -->
        <div class="form-section-label">Informations du chèque</div>

        <div class="form-field">
          <label>ID Chèque</label>
          <div class="input-id-wrapper">
            <span class="input-id-prefix">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
              CHK-
            </span>
            <input type="text" id="chkId" class="input-with-prefix" readonly/>
          </div>
          <div class="field-hint">Généré automatiquement</div>
        </div>

        <div class="form-field">
          <label>N° Chèque (unique)</label>
          <div class="input-id-wrapper">
            <span class="input-id-prefix">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M4 12h10M4 17h7"/></svg>
              NMR-
            </span>
            <input type="text" id="chkNmr" class="input-with-prefix" readonly/>
          </div>
          <div class="field-hint">Numéro séquentiel unique</div>
        </div>

        <div class="form-field">
          <label>Montant (TND) *</label>
          <input type="text" id="chkMontant" placeholder="Ex : 1500.000" oninput="updatePreview()"/>
          <div id="errChkMontant" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field">
          <label>Date d'émission *</label>
          <input type="text" id="chkDate" placeholder="Ex : 2026-04-12" oninput="updatePreview()"/>
          <div id="errChkDate" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>Montant en lettres *</label>
          <input type="text" id="chkLettres" placeholder="Ex : Mille cinq cents dinars" oninput="updatePreview()"/>
          <div id="errChkLettres" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>Agence *</label>
          <input type="text" id="chkAgence" placeholder="Ex : Agence Tunis Centre" oninput="updatePreview()"/>
          <div id="errChkAgence" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <!-- Section : Bénéficiaire -->
        <div class="form-section-label">Bénéficiaire</div>

        <div class="form-field">
          <label>Nom du bénéficiaire *</label>
          <input type="text" id="chkBenef" placeholder="Ex : Ahmed Ben Ali" oninput="updatePreview()"/>
          <div id="errChkBenef" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field">
          <label>N° Pièce d'identité *</label>
          <input type="text" id="chkCin" placeholder="Ex : 09856321" oninput="updatePreview()"/>
          <div id="errChkCin" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>RIB bénéficiaire *</label>
          <input type="text" id="chkRib" placeholder="Ex : 04010050071234120045" oninput="updatePreview()"/>
          <div id="errChkRib" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

      </div><!-- /cheque-form-grid -->

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeChequeModal()">Annuler</button>
        <button type="button" class="btn-primary" onclick="submitCheque()">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Émettre le chèque
        </button>
      </div>

    </form>

  </div><!-- /modal-box -->
</div><!-- /modal-overlay -->



<!-- MODALE LISTE DES DEMANDES -->
<div id="requestsModal" class="modal-overlay">
  <div class="modal-box" style="width:min(900px, 95vw); max-height:85vh; overflow-y:auto; padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <div class="section-title" style="font-size:1.2rem;">Mes Demandes en Cours</div>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    
    <div class="info-banner" style="margin-bottom:1.2rem;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      Vous pouvez modifier ou annuler vos demandes tant qu'elles ne sont pas encore traitées par l'agence.
    </div>

    <?php if (empty($toutes_les_demandes)): ?>
      <div style="text-align:center; padding:3rem; color:var(--muted);">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:0.3; margin-bottom:1rem;"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
        <p>Aucune demande trouvée dans la base de données.</p>
      </div>
    <?php else: ?>
      <table style="width:100%; border-collapse:collapse; font-size:.85rem;">
        <thead style="background:var(--surface2); text-align:left;">
          <tr>
            <th style="padding:.8rem; border-bottom:1px solid var(--border);">Compte/IBAN</th>
            <th style="padding:.8rem; border-bottom:1px solid var(--border);">Date</th>
            <th style="padding:.8rem; border-bottom:1px solid var(--border);">Type</th>
            <th style="padding:.8rem; border-bottom:1px solid var(--border);">Statut</th>
            <th style="padding:.8rem; border-bottom:1px solid var(--border); text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($toutes_les_demandes as $d): 
             $statut = trim(strtolower($d['statut'] ?? 'en attente'));
             if ($statut === 'en attente' || empty($statut)):
          ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:.8rem;">
                <div style="font-weight:600;"><?= htmlspecialchars($d['nom et prenom']) ?></div>
                <div style="font-family:var(--fm); font-size:.75rem; color:var(--muted);"><?= htmlspecialchars($d['iban'] ?? 'Compte #'.$d['id_compte']) ?></div>
              </td>
              <td style="padding:.8rem;"><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
              <td style="padding:.8rem;">
                <span class="badge <?= $d['type_chequier']==='urgent'?'b-urgent':'b-standard' ?>">
                  <?= ucfirst($d['type_chequier']) ?>
                </span>
              </td>
              <td style="padding:.8rem;">
                <span class="badge b-attente"><span class="badge-dot" style="background:var(--amber)"></span>En attente</span>
              </td>
              <td style="padding:.8rem; text-align:right;">
                <div style="display:flex; gap:.5rem; justify-content:flex-end;">
                  <button class="ca-act-btn ca-primary" onclick='preparerModif(<?= json_encode($d) ?>)' title="Modifier">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="ca-act-btn ca-danger" onclick="if(confirm('Supprimer cette demande ?')) window.location.href='?action=delete&id=<?= $d['id_demande'] ?>'" title="Supprimer">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php 
            endif;
          endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>



<!-- MODAL : TOUS LES CHEQUIERS -->
<div id="allChequiersModal" class="modal-overlay">
  <div class="modal-content" style="max-width:900px; max-height:85vh; overflow-y:auto;">
    <div class="modal-header">
      <div class="modal-title">Tous mes chéquiers</div>
      <button class="modal-close" onclick="closeAllChequiersModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="info-banner" style="margin-bottom:1.5rem;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        Retrouvez ici l'historique complet de vos chéquiers actifs et refusés.
      </div>

      <div class="chq-list" style="display:flex; flex-direction:column; gap:1.5rem;">
        <?php 
        $anyChq = false;
        foreach ($toutes_les_demandes as $d): 
            $s = trim(strtolower($d['statut'] ?? 'en attente'));
            if ($s === 'acceptée' || $s === 'acceptee' || $s === 'refusée'):
                $anyChq = true;
                $isRefused = $s === 'refusée';
                $creationDate = new DateTime($d['date_demande']);
                $expDate = clone $creationDate;
                $expDate->modify('+2 years');
                $chqNum = "CHQ-" . $creationDate->format('Y') . "-" . str_pad($d['id_demande'], 5, '0', STR_PAD_LEFT);
                $badgeClass = $isRefused ? 'b-refusee' : 'b-actif';
                $dotColor = $isRefused ? 'var(--rose)' : 'var(--green)';
                $statutLabel = $isRefused ? 'Refusée' : 'Actif';
                
                $actionStyle = $isRefused ? 'border-color:var(--rose-light); color:var(--rose); cursor:not-allowed; opacity:0.7;' : '';
                $actionCursor = $isRefused ? 'onclick="return false;"' : '';
        ?>
        <div class="chq-card" <?= $isRefused ? 'style="border-color:var(--rose-light);"' : '' ?>>
          <div class="chq-visual" <?= $isRefused ? 'style="background:linear-gradient(135deg, #fecaca 0%, #ef4444 100%);"' : '' ?>>
            <div class="chq-num"><?= $chqNum ?></div>
            <div class="chq-bottom">
              <div>
                <div class="chq-name"><?= htmlspecialchars($d['nom et prenom']) ?></div>
                <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;">Exp. <?= $expDate->format('d/m/Y') ?></div>
              </div>
              <div style="text-align:right">
                <div class="chq-feuilles-val"><?= htmlspecialchars($d['nombre_cheques']) ?></div>
                <div class="chq-feuilles-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="chq-details">
            <div class="chq-details-top">
              <span class="chq-details-title">Chéquier N° <?= $chqNum ?></span>
              <span class="badge <?= $badgeClass ?>"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= $statutLabel ?></span>
            </div>
            <div class="chq-info-grid">
              <div class="ci-item"><div class="ci-label">ID chéquier</div><div class="ci-val" style="font-family:var(--fm);font-size:.75rem"><?= $chqNum ?></div></div>
              <div class="ci-item"><div class="ci-label">Feuilles restantes</div><div class="ci-val"><?= htmlspecialchars($d['nombre_cheques']) ?> / <?= htmlspecialchars($d['nombre_cheques']) ?></div></div>
              <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val"><?= $creationDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val"><?= $expDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Compte lié</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem"><?= htmlspecialchars($d['iban'] ?: 'Non renseigné') ?></div></div>
              <div class="ci-item"><div class="ci-label">Statut</div><div class="ci-val"><span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= $statutLabel ?></span></div></div>
            </div>
            <div class="chq-actions">
              <button class="chq-act-btn ca-primary" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Détails
              </button>
              <button class="chq-act-btn ca-neutral" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Attestation
              </button>
              <button class="chq-act-btn ca-saisir" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequeModal('$chqNum','".htmlspecialchars($d['nom et prenom'])."','".htmlspecialchars($d['iban'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4M14 15h4"/></svg>
                Saisir un chèque
              </button>
              <button class="chq-act-btn ca-danger" style="<?= $isRefused ? $actionStyle : '' ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Faire opposition
              </button>
            </div>
          </div>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
      </div>
    </div>
  </div>
</div>

<script src="chequier.js"></script>
</body>
</html>