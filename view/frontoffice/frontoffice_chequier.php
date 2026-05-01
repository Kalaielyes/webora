<?php
require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../model/demandechequier.php';
require_once __DIR__ . '/../../controller/demandechequiercontroller.php';
require_once __DIR__ . '/../../controller/chequiercontroller.php';
require_once __DIR__ . '/../../model/chequier.php';
require_once __DIR__ . '/../../controller/chequecontroller.php';
require_once __DIR__ . '/../../model/cheque.php';

$demandeC = new DemandeChequierController();
$chequierC = new ChequierController();
$chequeC = new ChequeController();
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $demandeC->deleteDemande((int)$_GET['id']);
    header('Location: frontoffice_chequier.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_et_prenom'])) {
    $id_compte = $_POST['compte'] ?? '';
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
        $error_msg = "Erreur lors de l'opération : " . $e->getMessage();
        $success = false;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'emettre_cheque') {
    $id_chequier = (int)($_POST['id_chequier'] ?? 0);
    $validated = false;
    $validation_errors = [];
    
    if ($id_chequier <= 0) {
        $error_msg = "Erreur : Chéquier non identifié. Veuillez réessayer.";
    } else {
        $validation_errors = [];
        $montant_str = trim($_POST['montant'] ?? '');
        $montant = floatval($montant_str);
        if (empty($montant_str) || !is_numeric($montant_str) || $montant <= 0 || $montant > 8000) {
            $validation_errors[] = "Le montant est obligatoire, doit être positif et max 8000.";
        }
        $date_emission = trim($_POST['date_emission'] ?? '');
        if (empty($date_emission) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_emission)) {
            $validation_errors[] = "La date d'émission est obligatoire et invalide.";
        }
        $lettres = trim($_POST['lettres'] ?? '');
        if (empty($lettres) || strlen($lettres) > 40 || !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $lettres)) {
            $validation_errors[] = "Les lettres sont obligatoires, max 40 caractères et lettres uniquement.";
        }
        $agence = trim($_POST['agence'] ?? '');
        if (empty($agence) || strlen($agence) > 20) {
            $validation_errors[] = "L'agence est obligatoire, max 20 caractères.";
        }
        $beneficiaire = trim($_POST['beneficiaire'] ?? '');
        if (empty($beneficiaire) || strlen($beneficiaire) > 10 || !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $beneficiaire)) {
            $validation_errors[] = "Le nom du bénéficiaire est obligatoire, max 10 caractères et lettres uniquement.";
        }
        $cin = trim($_POST['cin_beneficiaire'] ?? '');
        if (!preg_match('/^\d{8}$/', $cin)) {
            $validation_errors[] = "Le CIN doit contenir exactement 8 chiffres.";
        }
        $rib = trim($_POST['rib_beneficiaire'] ?? '');
        if (!preg_match('/^\d{20}$/', $rib)) {
            $validation_errors[] = "Le RIB doit contenir exactement 20 chiffres.";
        }
        $numero_cheque = trim($_POST['numero_cheque'] ?? '');
        if (empty($numero_cheque)) {
            $validation_errors[] = "Le numéro de chèque est obligatoire.";
        }
        if (empty($validation_errors)) {
            $validated = true;
            
            $data = [
                'id_chequier'      => $id_chequier,
                'numero_cheque'    => $numero_cheque,
                'montant'          => $montant,
                'date_emission'    => $date_emission,
                'beneficiaire'     => $beneficiaire,
                'rib_beneficiaire' => $rib,
                'cin_beneficiaire' => $cin,
                'lettres'          => $lettres,
                'agence'           => $agence
            ];

            $cheque = new Cheque($data);
            try {
                $res = $chequeC->addCheque($cheque);
                if ($res) {
                    $success = true;
                    $op_type = 'cheque_emited';
                    // Rediriger avec un paramètre de succès
                    header('Location: frontoffice_chequier.php?view=mes_chequiers&cheque_success=1');
                    exit();
                }
            } catch (Exception $e) {
                $error_msg = "Erreur base de données : " . $e->getMessage();
            }
        } else {
            $error_msg = "Erreur de validation : " . implode(" ", $validation_errors);
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'get_history' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $cheques = $chequeC->listChequesByChequier($id);
    echo json_encode($cheques);
    exit();
}
if (isset($_GET['action']) && $_GET['action'] === 'delete_cheque' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    try {
        $res = $chequeC->deleteCheque($id);
        echo json_encode(['success' => $res]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_cheque') {
    $id_cheque = (int)($_POST['id_cheque'] ?? 0);
    $data = [
        'montant'          => (float)($_POST['montant'] ?? 0),
        'date_emission'    => $_POST['date_emission'] ?? date('Y-m-d'),
        'beneficiaire'     => $_POST['beneficiaire'] ?? '',
        'rib_beneficiaire' => $_POST['rib_beneficiaire'] ?? '',
        'cin_beneficiaire' => $_POST['cin_beneficiaire'] ?? '',
        'lettres'          => $_POST['lettres'] ?? '',
        'agence'           => $_POST['agence'] ?? ''
    ];

    $cheque = new Cheque($data);
    try {
        $res = $chequeC->updateCheque($cheque, $id_cheque);
        if ($res) {
            $success = true;
            $op_type = 'edit_cheque'; // Specific type for check edit
            // Optionnel : ne pas rediriger si on veut rester sur la vue actuelle
            header('Location: frontoffice_chequier.php?view=mes_chequiers&status=success_edit');
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "Erreur lors de la modification : " . $e->getMessage();
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'generate_pdf' && isset($_GET['cheque_id'])) {
    require_once __DIR__ . '/../../controller/chequecontroller.php';

    $chequeController = new ChequeController();
    try {
        $fileName = $chequeController->generateChequePDF($_GET['cheque_id']);
        $filePath = __DIR__ . '/../../exports/' . $fileName;

        if (file_exists($filePath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
            exit;
        } else {
            echo "Erreur : le fichier PDF n'a pas pu être généré.";
        }
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
}

$toutes_les_demandes = $demandeC->listDemandes();
$tous_les_chequiers = $chequierC->listChequiers();
$countActifs = 0;
$totalFeuilles = 0;
foreach($tous_les_chequiers as $c) {
    if (trim(strtolower($c['statut'])) === 'actif') {
        $countActifs++;
        $totalFeuilles += (int)$c['nombre_feuilles'];
    }
}

$countRequests = 0;
foreach($toutes_les_demandes as $d) {
    $statut = trim(strtolower($d['statut'] ?? 'en attente'));
    if ($statut === 'en attente' || empty($statut)) {
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

<?php 
$current_view = $_GET['view'] ?? 'dashboard'; 
?>
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
    
    <a class="nav-item <?= $current_view === 'dashboard' ? 'active' : '' ?>" href="frontoffice_chequier.php?view=dashboard">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Tableau de bord
    </a>

    <a class="nav-item <?= $current_view === 'mes_chequiers' ? 'active' : '' ?>" href="frontoffice_chequier.php?view=mes_chequiers">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
      Mes chéquiers
      <span class="nav-badge" style="background:rgba(220,38,38,0.25); color:#FCA5A5; min-width:18px; height:18px; display:flex; align-items:center; justify-content:center; border-radius:50%; padding:0; margin-left:auto; font-size:0.65rem;"><?= $countActifs ?></span>
    </a>

    <a class="nav-item" href="../backoffice/camera.php">
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
    <div class="topbar-title"><?= $current_view === 'mes_chequiers' ? 'Mes chéquiers' : 'Demande d\'un chequier' ?></div>
    <div class="topbar-right">
      <div class="notif">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <div class="content">
    <?php if (isset($success) && $success && ($op_type === 'add' || $op_type === 'edit')): ?>
      <div id="phpSuccessMsg" class="success-banner" style="margin-bottom: 1.5rem; align-items: center;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0; color:var(--green);"><path d="M20 6L9 17l-5-5"/></svg>
        <div style="flex:1; font-size:0.8rem; color:var(--text); padding: 0 4px;">
            <?php 
              if ($op_type === 'add') echo "Votre demande de chéquier a été enregistrée avec succès.";
              elseif ($op_type === 'edit') echo "Votre demande a été mise à jour avec succès.";
            ?>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--muted2); cursor:pointer; font-size:1.2rem; line-height:1; display:flex;">&times;</button>
      </div>
    <?php elseif (isset($_GET['cheque_success']) && $_GET['cheque_success'] == 1): ?>
      <div id="chequeSuccessMsg" class="success-banner" style="margin-bottom: 1.5rem; align-items: center;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0; color:var(--green);"><path d="M20 6L9 17l-5-5"/></svg>
        <div style="flex:1; font-size:0.8rem; color:var(--text); padding: 0 4px;">
          Le chèque a été émis avec succès.
        </div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--muted2); cursor:pointer; font-size:1.2rem; line-height:1; display:flex;">&times;</button>
      </div>
    <?php elseif (isset($error_msg)): ?>
      <div class="danger-banner" style="margin-bottom: 1.5rem; align-items: center;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0; color:var(--rose);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <div style="flex:1; font-size:0.8rem; color:var(--text); padding: 0 4px;"><?= htmlspecialchars($error_msg) ?></div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--muted2); cursor:pointer; font-size:1.2rem; line-height:1; display:flex;">&times;</button>
      </div>
    <?php endif; ?>

    <!-- STATS (Persistent) -->
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

    <?php if ($current_view === 'dashboard'): ?>
    <!-- VIEW: DASHBOARD (Faire une demande) -->

    <div>
      <div class="section-head" style="margin-bottom:.9rem">
        <div class="section-title">Faire une demande de chéquier</div>
      </div>
        <div class="info-banner">
          <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          Votre demande sera traitée sous 24 à 48h ouvrables. Vous serez notifié par e-mail dès la validation ou le refus de votre demande.
        </div>


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
    <?php else: ?>
    
    <!-- VIEW: MES CHEQUIERS (Cards + Demandes) -->
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
        foreach ($tous_les_chequiers as $chq): 
            $s = trim(strtolower($chq['statut']));
            if ($s === 'actif' || $s === 'bloque' || $s === 'expire'):
                $countShow++;
                $affiche_quelque_chose = true;
                $affiche_quelque_chose = true;
                $isRefused = ($s !== 'actif');
                $creationDate = new DateTime($chq['date_creation']);
                $expDate = new DateTime($chq['date_expiration']);
                $chqNum = $chq['numero_chequier'];
                $badgeClass = $s === 'actif' ? 'b-actif' : 'b-refusee';
                $dotColor = $s === 'actif' ? 'var(--green)' : 'var(--rose)';
                $statutLabel = ucfirst($s);
                
                // Styles pour le refus
                $actionStyle = $isRefused ? 'border-color:var(--rose-light); color:var(--rose); cursor:not-allowed; opacity:0.7;' : '';
                $actionCursor = $isRefused ? 'onclick="return false;"' : 'onclick="generateAttestation('.$chq['id_chequier'].')"';
        ?>
        <div class="chq-card" <?= $isRefused ? 'style="border-color:var(--rose-light);"' : '' ?>>
          <div class="chq-visual" <?= $isRefused ? 'style="background:linear-gradient(135deg, #fecaca 0%, #ef4444 100%);"' : '' ?>>
            <div class="chq-num"><?= htmlspecialchars($chqNum) ?></div>
            <div class="chq-bottom">
              <div>
                <div class="chq-name"><?= htmlspecialchars($chq['nom et prenom'] ?? 'Client') ?></div>
                <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;">Exp. <?= $expDate->format('d/m/Y') ?></div>
              </div>
              <div style="text-align:right">
                <div class="chq-feuilles-val"><?= htmlspecialchars($chq['nombre_feuilles']) ?></div>
                <div class="chq-feuilles-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="chq-details">
            <div class="chq-details-top">
              <span class="chq-details-title">Chéquier N° <?= htmlspecialchars($chqNum) ?></span>
              <span class="badge <?= $badgeClass ?>"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= htmlspecialchars($statutLabel) ?></span>
            </div>
            <div class="chq-info-grid">
              <div class="ci-item"><div class="ci-label">ID chéquier</div><div class="ci-val" style="font-family:var(--fm);font-size:.75rem"><?= htmlspecialchars($chqNum) ?></div></div>
              <div class="ci-item"><div class="ci-label">Feuilles restantes</div><div class="ci-val"><?= htmlspecialchars($chq['nombre_feuilles']) ?> / <?= htmlspecialchars($chq['nombre_feuilles']) ?></div></div>
              <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val"><?= $creationDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val"><?= $expDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Compte lié</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem"><?= htmlspecialchars($chq['iban'] ?? 'Non renseigné') ?></div></div>
              <div class="ci-item"><div class="ci-label">Statut</div><div class="ci-val"><span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= htmlspecialchars($statutLabel) ?></span></div></div>
            </div>
            <div class="chq-actions">
              <button class="chq-act-btn ca-primary" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequierModal('".htmlspecialchars($chqNum)."','".htmlspecialchars(addslashes($chq['nom et prenom'] ?? 'Client'))."','".htmlspecialchars(addslashes($chq['iban'] ?? ''))."','".htmlspecialchars($statutLabel)."','".htmlspecialchars($chq['nombre_feuilles'])."','".htmlspecialchars($chq['date_creation'])."','".htmlspecialchars($chq['date_expiration'])."','".htmlspecialchars($chq['id_chequier'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Détails
              </button>
              <button class="chq-act-btn ca-neutral" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Attestation
              </button>
              <button class="chq-act-btn ca-saisir" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequeModal('".htmlspecialchars($chqNum)."','".htmlspecialchars(addslashes($chq['nom et prenom'] ?? ''))."','".htmlspecialchars(addslashes($chq['iban'] ?? ''))."','".htmlspecialchars($chq['id_chequier'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4M14 15h4"/></svg>
                Saisir un chèque
              </button>
              <button class="chq-act-btn ca-neutral" onclick="openHistoriqueModal('<?= htmlspecialchars($chqNum) ?>','<?= htmlspecialchars($chq['id_chequier'] ?? '') ?>')" style="background:rgba(99,102,241,0.1); color:var(--blue); border-color:rgba(99,102,241,0.2);">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historique
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
    <div class="success-banner" id="successBanner" style="display:none;">
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
    <form class="cheque-form" id="chequeEmissionForm" method="POST" onsubmit="return submitCheque(event);">
      <input type="hidden" name="action_type" value="emettre_cheque">
      <input type="hidden" name="id_chequier" id="hiddenChequierId">
      <div class="cheque-form-grid">

        <!-- Section : jointure Chéquier -->
        <div class="form-section-label">Lien avec le chéquier</div>
        
        <div class="form-field full-col">
          <label>N° Chéquier cible (Jointure)</label>
          <div class="input-id-wrapper">
            <span class="input-id-prefix" style="background:var(--surface3); color:var(--blue);">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M4 12h10M4 17h7"/></svg>
              REF-
            </span>
            <input type="text" id="chkRefChequier" class="input-with-prefix" readonly style="background:var(--surface2); cursor:default;"/>
          </div>
          <div class="field-hint">Ce chèque sera rattaché mathématiquement à ce chéquier.</div>
        </div>

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
            <input type="text" id="chkNmr" name="numero_cheque" class="input-with-prefix" readonly/>
          </div>
          <div class="field-hint">Numéro séquentiel unique</div>
        </div>

        <div class="form-field">
          <label>Montant (TND) *</label>
          <input type="text" id="chkMontant" name="montant" placeholder="Ex : 1500.000" oninput="updatePreview()"/>
          <div id="errChkMontant" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field">
          <label>Date d'émission *</label>
          <input type="date" id="chkDate" name="date_emission" oninput="updatePreview()"/>
          <div id="errChkDate" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>Montant en lettres *</label>
          <input type="text" id="chkLettres" name="lettres" placeholder="Ex : Mille cinq cents dinars" oninput="updatePreview()"/>
          <div id="errChkLettres" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>Agence *</label>
          <input type="text" id="chkAgence" name="agence" placeholder="Ex : Agence Tunis Centre" oninput="updatePreview()"/>
          <div id="errChkAgence" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <!-- Section : Bénéficiaire -->
        <div class="form-section-label">Bénéficiaire</div>

        <div class="form-field">
          <label>Nom du bénéficiaire *</label>
          <input type="text" id="chkBenef" name="beneficiaire" placeholder="Ex : Ahmed Ben Ali" oninput="updatePreview()"/>
          <div id="errChkBenef" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field">
          <label>N° Pièce d'identité *</label>
          <input type="text" id="chkCin" name="cin_beneficiaire" placeholder="Ex : 09856321" oninput="updatePreview()"/>
          <div id="errChkCin" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

        <div class="form-field full-col">
          <label>RIB bénéficiaire *</label>
          <input type="text" id="chkRib" name="rib_beneficiaire" placeholder="Ex : 04010050071234120045" oninput="updatePreview()"/>
          <div id="errChkRib" class="error-msg" style="color:var(--rose);font-size:0.75rem;margin-top:4px;display:none;"></div>
        </div>

      </div><!-- /cheque-form-grid -->

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeChequeModal()">Annuler</button>
        <button type="submit" class="btn-primary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Émettre le chèque
        </button>
      </div>
    </form>
  </div><!-- /modal-box -->
</div><!-- /modal-overlay -->
<?php endif; ?>



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
             $statut_raw = $d['statut'] ?? '';
             $statut = trim(strtolower($statut_raw));
             // Afficher toutes les demandes en attente (filtre robuste)
             if ($statut === 'en attente' || $statut === '' || strpos($statut, 'attente') !== false || $statut_raw === null):
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
        foreach ($tous_les_chequiers as $chq): 
            $s = trim(strtolower($chq['statut']));
            if ($s === 'actif' || $s === 'bloque' || $s === 'expire'):
                $anyChq = true;
                $isRefused = ($s !== 'actif');
                $creationDate = new DateTime($chq['date_creation']);
                $expDate = new DateTime($chq['date_expiration']);
                $chqNum = $chq['numero_chequier'];
                $badgeClass = $s === 'actif' ? 'b-actif' : 'b-refusee';
                $dotColor = $s === 'actif' ? 'var(--green)' : 'var(--rose)';
                $statutLabel = ucfirst($s);
                
                $actionStyle = $isRefused ? 'border-color:var(--rose-light); color:var(--rose); cursor:not-allowed; opacity:0.7;' : '';
                $actionCursor = $isRefused ? 'onclick="return false;"' : 'onclick="generateAttestation('.$chq['id_chequier'].')"';
        ?>
        <div class="chq-card" <?= $isRefused ? 'style="border-color:var(--rose-light);"' : '' ?>>
          <div class="chq-visual" <?= $isRefused ? 'style="background:linear-gradient(135deg, #fecaca 0%, #ef4444 100%);"' : '' ?>>
            <div class="chq-num"><?= htmlspecialchars($chqNum) ?></div>
            <div class="chq-bottom">
              <div>
                <div class="chq-name"><?= htmlspecialchars($chq['nom et prenom'] ?? 'Client') ?></div>
                <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;">Exp. <?= $expDate->format('d/m/Y') ?></div>
              </div>
              <div style="text-align:right">
                <div class="chq-feuilles-val"><?= htmlspecialchars($chq['nombre_feuilles']) ?></div>
                <div class="chq-feuilles-label">feuilles</div>
              </div>
            </div>
          </div>
          <div class="chq-details">
            <div class="chq-details-top">
              <span class="chq-details-title">Chéquier N° <?= htmlspecialchars($chqNum) ?></span>
              <span class="badge <?= $badgeClass ?>"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= htmlspecialchars($statutLabel) ?></span>
            </div>
            <div class="chq-info-grid">
              <div class="ci-item"><div class="ci-label">ID chéquier</div><div class="ci-val" style="font-family:var(--fm);font-size:.75rem"><?= htmlspecialchars($chqNum) ?></div></div>
              <div class="ci-item"><div class="ci-label">Feuilles restantes</div><div class="ci-val"><?= htmlspecialchars($chq['nombre_feuilles']) ?> / <?= htmlspecialchars($chq['nombre_feuilles']) ?></div></div>
              <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val"><?= $creationDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val"><?= $expDate->format('d M. Y') ?></div></div>
              <div class="ci-item"><div class="ci-label">Compte lié</div><div class="ci-val" style="font-family:var(--fm);font-size:.72rem"><?= htmlspecialchars($chq['iban'] ?? 'Non renseigné') ?></div></div>
              <div class="ci-item"><div class="ci-label">Statut</div><div class="ci-val"><span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><span class="badge-dot" style="background:<?= $dotColor ?>"></span><?= htmlspecialchars($statutLabel) ?></span></div></div>
            </div>
            <div class="chq-actions">
              <?php if (!$isRefused): ?>
              <button class="chq-act-btn" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;font-weight:600;letter-spacing:.02em;" onclick="openChequierModal('<?= htmlspecialchars($chqNum) ?>','<?= htmlspecialchars(addslashes($chq['nom et prenom'] ?? '')) ?>','<?= htmlspecialchars(addslashes($chq['iban'] ?? '')) ?>','<?= $statutLabel ?>','<?= $chq['nombre_feuilles'] ?>','<?= $chq['date_creation'] ?>','<?= $chq['date_expiration'] ?>','<?= $chq['id_chequier'] ?>')">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/><path d="M14 3v4a1 1 0 001 1h4"/></svg>
                Saisir chéquier
              </button>
              <?php endif; ?>
              <button class="chq-act-btn ca-primary" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequierModal('".htmlspecialchars($chqNum)."','".htmlspecialchars(addslashes($chq['nom et prenom'] ?? 'Client'))."','".htmlspecialchars(addslashes($chq['iban'] ?? ''))."','".htmlspecialchars($statutLabel)."','".htmlspecialchars($chq['nombre_feuilles'])."','".htmlspecialchars($chq['date_creation'])."','".htmlspecialchars($chq['date_expiration'])."','".htmlspecialchars($chq['id_chequier'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Détails
              </button>
              <button class="chq-act-btn ca-neutral" style="<?= $actionStyle ?>" <?= $actionCursor ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Attestation
              </button>
              <button class="chq-act-btn ca-saisir" style="<?= $actionStyle ?>" <?= $isRefused ? 'onclick="return false;"' : "onclick=\"openChequeModal('".htmlspecialchars($chqNum)."','".htmlspecialchars(addslashes($chq['nom et prenom'] ?? ''))."','".htmlspecialchars(addslashes($chq['iban'] ?? ''))."','".htmlspecialchars($chq['id_chequier'])."')\"" ?>>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4M14 15h4"/></svg>
                Saisir un chèque
              </button>
              <button class="chq-act-btn ca-neutral" onclick="openHistoriqueModal('<?= htmlspecialchars($chqNum) ?>','<?= htmlspecialchars($chq['id_chequier'] ?? '') ?>')" style="background:rgba(99,102,241,0.1); color:var(--blue); border-color:rgba(99,102,241,0.2);">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historique
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

<!-- ═══════════════════════════════
     MODAL SAISIR CHÉQUIER
════════════════════════════════ -->
<div class="modal-overlay" id="chequierModal">
  <div class="modal-box" style="max-width:520px;">
    <div class="modal-header">
      <div>
        <div class="modal-title">Saisir chéquier</div>
        <div class="modal-sub">Chéquier : <span id="chqModalId" style="font-family:var(--fm);color:var(--blue)"></span></div>
      </div>
      <button class="modal-close" onclick="closeChequierModal()" title="Fermer">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Visual chequier card -->
    <div class="chq-visual" id="chqModalVisual" style="border-radius:14px;margin-bottom:1.2rem;min-height:100px;">
      <div class="chq-num" id="chqModalNum"></div>
      <div class="chq-bottom">
        <div>
          <div class="chq-name" id="chqModalName"></div>
          <div style="font-size:.5rem;color:rgba(255,255,255,.35);margin-top:2px;" id="chqModalExpLabel">Exp. —</div>
        </div>
        <div style="text-align:right">
          <div class="chq-feuilles-val" id="chqModalFeuilles"></div>
          <div class="chq-feuilles-label">feuilles</div>
        </div>
      </div>
    </div>

    <!-- Details grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:1.4rem;">
      <div class="ci-item"><div class="ci-label">N° Chéquier</div><div class="ci-val" id="chqModalNumVal" style="font-family:var(--fm);font-size:.72rem;"></div></div>
      <div class="ci-item"><div class="ci-label">Statut</div><div id="chqModalStatutBadge"></div></div>
      <div class="ci-item"><div class="ci-label">Feuilles disponibles</div><div class="ci-val" id="chqModalFeuillesVal"></div></div>
      <div class="ci-item"><div class="ci-label">Compte IBAN</div><div class="ci-val" id="chqModalIban" style="font-family:var(--fm);font-size:.7rem;"></div></div>
      <div class="ci-item"><div class="ci-label">Date création</div><div class="ci-val" id="chqModalDateCreation"></div></div>
      <div class="ci-item"><div class="ci-label">Date expiration</div><div class="ci-val" id="chqModalDateExp"></div></div>
    </div>

    <!-- Action rapide -->
    <div style="background:var(--surface2);border-radius:10px;padding:1rem;border:1px solid var(--border);">
      <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.8rem;">Action rapide</div>
      <div style="display:flex;flex-direction:column;gap:.7rem;">
        <div class="form-field-mini" style="margin:0;">
          <label style="font-size:.75rem;color:var(--muted2);">Bénéficiaire du chèque</label>
          <input type="text" id="chqModalBenef" placeholder="Nom du bénéficiaire" style="height:36px;font-size:.82rem;">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;">
          <div class="form-field-mini" style="margin:0;">
            <label style="font-size:.75rem;color:var(--muted2);">Montant (TND)</label>
            <input type="text" id="chqModalMontant" placeholder="Ex : 1500.000" style="height:36px;font-size:.82rem;">
          </div>
          <div class="form-field-mini" style="margin:0;">
            <label style="font-size:.75rem;color:var(--muted2);">Date d'émission</label>
            <input type="date" id="chqModalDate" style="height:36px;font-size:.82rem;">
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer" style="margin-top:1.2rem;">
      <button type="button" class="btn-ghost" onclick="closeChequierModal()">Fermer</button>
      <button type="button" class="btn-primary" onclick="lancerSaisirCheque()" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        Saisir un chèque
      </button>
    </div>
  </div>
</div>

<!-- MODAL : HISTORIQUE DES CHEQUES -->
<div class="modal-overlay" id="historiqueChequeModal">
  <div class="modal-box" style="max-width:800px; max-height:80vh; overflow-y:auto;">
    <div class="modal-header">
      <div>
        <div class="modal-title">Historique des chèques</div>
        <div class="modal-sub">Chéquier : <span id="histChqNum" style="font-family:var(--fm);color:var(--blue)"></span></div>
      </div>
      <button class="modal-close" onclick="closeHistoriqueModal()" title="Fermer">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    
    <div class="modal-body" style="padding-top:1rem;">
      <div id="historiqueList">
        <div id="historiqueContent">
          <div class="info-banner">Chargement de l'historique...</div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-ghost" onclick="closeHistoriqueModal()">Fermer</button>
    </div>
  </div>
</div>

<script src="chequier.js?v=<?= time() ?>"></script>
<script src="cheque.js?v=<?= time() ?>"></script>
<script src="demandechequier.js?v=<?= time() ?>"></script>
<script src="saisiecheque.js?v=<?= time() ?>"></script>
<script>
/* ── Modales de base ── */
function openModal() {
  var m = document.getElementById('requestsModal');
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal() {
  var m = document.getElementById('requestsModal');
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function openAllChequiersModal() {
  var m = document.getElementById('allChequiersModal');
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeAllChequiersModal() {
  var m = document.getElementById('allChequiersModal');
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function closeHistoriqueModal() {
  var m = document.getElementById('historiqueChequeModal');
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
/* Fermer en cliquant sur l'overlay */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) {
      if (e.target === o) { o.classList.remove('open'); document.body.style.overflow = ''; }
    });
  });
});

/* ── preparerModif : pré-remplit le formulaire de demande ── */
function preparerModif(d) {
  closeModal();
  var form = document.getElementById('demandeForm');
  if (!form) {
    window.location.href = 'frontoffice_chequier.php?view=dashboard&edit_id=' + d.id_demande;
    return;
  }
  document.getElementById('idEdit').value        = d.id_demande || '';
  document.getElementById('nomPrenom').value     = d['nom et prenom'] || '';
  document.getElementById('motif').value         = d.motif || '';
  document.getElementById('montantMax').value    = d.montant_max_par_cheque || '';
  document.getElementById('telephone').value     = d.telephone || '';
  document.getElementById('email').value         = d.email || '';
  document.getElementById('commentaire').value   = d.commentaire || '';
  document.getElementById('adresseInput').value  = d.adresse_agence || '';
  var cpt = document.getElementById('compte');
  if (cpt && d.id_compte) cpt.value = d.id_compte;
  var tc = document.querySelector('select[name="type_chequier"]');
  if (tc && d.type_chequier) tc.value = d.type_chequier;
  var nb = document.querySelector('select[name="nombre_cheques"]');
  if (nb && d.nombre_cheques) nb.value = d.nombre_cheques;
  document.querySelectorAll('input[name="mode_reception"]').forEach(function(r) {
    var lbl = r.closest('label');
    if (r.value === d.mode_reception) { r.checked = true; if (lbl) lbl.classList.add('selected'); }
    else { r.checked = false; if (lbl) lbl.classList.remove('selected'); }
  });
  var btn = document.querySelector('#demandeForm .btn-primary');
  if (btn) btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Modifier la demande';
  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openAttestation(chqNum, owner, iban, statut, feuilles, creation, expiration) {

  const win = window.open('', '_blank');

  if (!win) {
    alert("Veuillez autoriser les popups pour générer l'attestation.");
    return;
  }

  // Sécuriser les valeurs (éviter undefined)
  owner = owner || "Non renseigné";
  chqNum = chqNum || "Non renseigné";
  iban = iban || "Non renseigné";
  statut = statut || "Non renseigné";
  feuilles = feuilles || "0";
  creation = creation || "Non renseignée";
  expiration = expiration || "Non renseignée";

  const html = `
  <!DOCTYPE html>
  <html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>Attestation de Chéquier</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        background: #f7f8fb;
        padding: 40px;
      }
      .card {
        max-width: 700px;
        margin: auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
      }
      h1 {
        text-align: center;
        margin-bottom: 20px;
      }
      .field {
        margin-bottom: 10px;
      }
      .label {
        font-weight: bold;
      }
      .btn {
        margin-top: 20px;
        padding: 10px 15px;
        background: #4f46e5;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 5px;
      }
    </style>
  </head>
  <body>

    <div class="card">
      <h1>Attestation de Chéquier</h1>

      <div class="field"><span class="label">Titulaire :</span> ${owner}</div>
      <div class="field"><span class="label">Numéro :</span> ${chqNum}</div>
      <div class="field"><span class="label">IBAN :</span> ${iban}</div>
      <div class="field"><span class="label">Création :</span> ${creation}</div>
      <div class="field"><span class="label">Expiration :</span> ${expiration}</div>
      <div class="field"><span class="label">Statut :</span> ${statut}</div>
      <div class="field"><span class="label">Feuilles :</span> ${feuilles}</div>

      <p style="margin-top:20px;">
        Cette attestation est générée automatiquement.
      </p>

      <button class="btn" onclick="window.print()">Imprimer</button>
    </div>

  </body>
  </html>
  `;

  win.document.open();
  win.document.write(html);
  win.document.close();
}

function openHistoriqueModal(num, id) {
  console.log("Opening history v4 for:", num, id);
  const modal = document.getElementById('historiqueChequeModal');
  const chqNumSpan = document.getElementById('histChqNum');
  const content = document.getElementById('historiqueContent');

  if (chqNumSpan) chqNumSpan.textContent = num + " [ACTIF - v4]";
  if (content) content.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--muted);">Chargement v4...</div>';

  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  fetch(`frontoffice_chequier.php?action=get_history&id=${id}&cb=${Date.now()}`, { cache: "no-store" })
    .then(response => response.json())
    .then(data => {
      window.currentChequesHistory = data;
      if (!data || data.length === 0) {
        content.innerHTML = '<div class="info-banner" style="background:var(--surface2); color:var(--muted);">Aucun chèque émis pour ce chéquier.</div>';
      } else {
        let html = `
          <div class="hist-list">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead style="background:var(--surface2); text-align:left;">
                <tr>
                  <th style="padding:10px; border-bottom:2px solid var(--blue);">N° Chèque</th>
                  <th style="padding:10px; border-bottom:2px solid var(--blue);">Bénéficiaire</th>
                  <th style="padding:10px; border-bottom:2px solid var(--blue);">Montant</th>
                  <th style="padding:10px; border-bottom:2px solid var(--blue);">Date</th>
                  <th style="padding:10px; border-bottom:2px solid var(--blue); text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
        `;
        data.forEach(chq => {
          const dateStr = new Date(chq.date_emission).toLocaleDateString('fr-FR');
          html += `
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:12px; font-family:var(--fm); color:var(--blue);">${chq.numero_cheque}</td>
              <td style="padding:12px;">
                <div style="font-weight:600;">${chq.beneficiaire}</div>
                <div style="font-size:0.7rem; color:var(--muted);">${chq.rib_beneficiaire}</div>
              </td>
              <td style="padding:12px; font-weight:600;">${parseFloat(chq.montant).toLocaleString('fr-TN', {minimumFractionDigits:3})} TND</td>
              <td style="padding:12px; color:var(--muted);">${dateStr}</td>
              <td style="padding:12px; text-align:right;">
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                  <button type="button" onclick="generateChequeAttestation(${chq.id_cheque})" style="background:#10b981; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:10px; font-weight:bold; letter-spacing:0.5px;">ATTESTATION</button>
                  <button type="button" onclick="editCheque(${chq.id_cheque})" style="background:#4f46e5; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:10px; font-weight:bold; letter-spacing:0.5px;">MODIFIER</button>
                  <button type="button" onclick="deleteCheque(${chq.id_cheque}, ${id})" style="background:#dc2626; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:10px; font-weight:bold; letter-spacing:0.5px;">SUPPRIMER</button>
                </div>
              </td>
            </tr>
          `;
        });
        html += '</tbody></table></div>';
        content.innerHTML = html;
      }
    })
    .catch(err => {
      content.innerHTML = '<div class="info-banner" style="background:var(--rose-light); color:var(--rose);">Erreur lors du chargement.</div>';
    });
}
</script>
</body>
</html>