<?php
require_once __DIR__ . '/../../model/config.php';
require_once '../../controller/demandechequiercontroller.php';
require_once '../../controller/chequiercontroller.php';

$demandeC = new DemandeChequierController();
$chequierC = new ChequierController();

if (!isset($_GET['id'])) {
    header("Location: backoffice_chequier.php");
    exit();
}

$id_demande = (int)$_GET['id'];
$demande = $demandeC->getDemandeById($id_demande);
if (!$demande) {
    die("Demande introuvable");
}

$chequier_id = "CHQ-" . date('Y') . "-" . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$cheque_id = "CHK-" . date('Y') . "-" . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$cheque_num = "NMR-" . date('Y') . "-" . str_pad(1, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisir un chèque — LegalFin Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="chequier.css">
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script> <!-- Placeholder for FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: #000; display: block; overflow-y: auto;">

<div class="saisie-container">
    <div class="saisie-header">
        <div class="saisie-title">
            <h1>Saisir un chèque</h1>
            <div class="saisie-subtitle">Chéquier : <span><?= $chequier_id ?></span></div>
        </div>
        <button class="close-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- VISUAL CHECK -->
    <div class="visual-check">
        <div class="check-banner"></div>
        <div class="check-top">
            <div class="check-bank">
                <div class="bank-logo">LF</div>
                <div class="bank-info">
                    <h2>LegalFin Bank</h2>
                    <p>Av. Habib Bourguiba, Tunis 1000</p>
                </div>
            </div>
            <div class="check-meta">
                <div>ID CHÈQUE <span><?= $cheque_id ?></span></div>
                <div>N° CHÈQUE <span><?= $cheque_num ?></span></div>
                <div>DATE D'ÉMISSION <span class="date-val"><?= date('d/m/Y') ?></span></div>
                <div>AGENCE <span class="agence-val">—</span></div>
            </div>
        </div>

        <div class="check-body">
            <div class="check-line">
                <span class="line-label">Payez contre ce chèque à l'ordre de</span>
                <span class="line-value"></span>
            </div>
            <div class="check-line">
                <span class="line-label">La somme de</span>
                <span class="line-value"></span>
            </div>
        </div>

        <div class="check-bottom-row">
            <div class="check-box">
                <span class="box-label">MONTANT (TND)</span>
                <span class="box-value amount">0,000</span>
            </div>
            <div class="check-box">
                <span class="box-label">PIÈCE D'IDENTITÉ BÉNÉFICIAIRE</span>
                <span class="box-value cin">—</span>
            </div>
            <div class="check-box">
                <span class="box-label">RIB BÉNÉFICIAIRE</span>
                <span class="box-value rib">—</span>
            </div>
            <div class="signature-area">
                <div class="signature-box">
                    <span class="box-label">SIGNATURE DU TIREUR</span>
                    <span class="signature-val"><?= htmlspecialchars($demande['nom et prenom']) ?></span>
                </div>
            </div>
        </div>

        <div class="micr-line">
            <span>⑆ <?= str_replace('NMR-', '', $cheque_num) ?> ⑆</span>
            <span>⑆ 14207207100707000001 ⑆</span>
            <span>LegalFin — Espace Client</span>
        </div>
    </div>

    <!-- FORM SECTION -->
    <div class="form-section-title">Informations du chèque</div>
    
    <form action="process_saisie.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <div class="label-row">
                    <label>ID Chèque</label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-card input-icon"></i>
                    <input type="text" class="premium-input" value="<?= $cheque_id ?>" readonly>
                </div>
                <div class="input-hint">• Généré automatiquement</div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label>N° Chèque (unique)</label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-hashtag input-icon"></i>
                    <input type="text" class="premium-input" value="<?= $cheque_num ?>" readonly>
                </div>
                <div class="input-hint">• Numéro séquentiel unique</div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="payee">Bénéficiaire <span class="required-star">*</span></label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" id="payee" name="beneficiaire" class="premium-input" placeholder="Nom du bénéficiaire" required>
                </div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="amount_digits">Montant (TND) <span class="required-star">*</span></label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-coins input-icon"></i>
                    <input type="number" step="0.001" id="amount_digits" name="montant" class="premium-input" placeholder="Ex : 1500.000" required>
                </div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="cin">CIN Bénéficiaire</label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-badge input-icon"></i>
                    <input type="text" id="cin" name="cin_beneficiaire" class="premium-input" placeholder="Numéro CIN">
                </div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="date">Date d'émission <span class="required-star">*</span></label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-calendar-day input-icon"></i>
                    <input type="date" id="date" name="date_emission" class="premium-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group full">
                <div class="label-row">
                    <label for="amount_words">Montant en lettres <span class="required-star">*</span></label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-spell-check input-icon"></i>
                    <input type="text" id="amount_words" name="montant_lettres" class="premium-input" placeholder="Ex : Mille cinq cents dinars" required>
                </div>
            </div>

            <div class="form-group full">
                <div class="label-row">
                    <label for="agence">Agence <span class="required-star">*</span></label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-building-columns input-icon"></i>
                    <input type="text" id="agence" name="agence" class="premium-input" placeholder="Ex : Agence Tunis Centre" required>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="backoffice_chequier.php" class="btn-secondary">Annuler</a>
            <button type="submit" class="btn-submit">Confirmer l'émission</button>
        </div>
    </form>
</div>

<script src="saisie_chequier.js"></script>
</body>
</html>