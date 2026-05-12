<?php
require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../controller/ChequierController.php';
require_once __DIR__ . '/../controller/ChequeController.php';

// On peut passer soit l'ID du chéquier (pour prendre le dernier chèque) 
// soit l'ID d'un chèque spécifique.
$id_chequier = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_cheque = isset($_GET['id_cheque']) ? (int)$_GET['id_cheque'] : 0;

$chequierC = new ChequierController();
$chequeC = new ChequeController();

if ($id_cheque > 0) {
    // Si un chèque spécifique est demandé
    $lastCheque = $chequeC->getChequeById($id_cheque);
    if (!$lastCheque) {
        die("Chèque introuvable.");
    }
    $id_chequier = $lastCheque['id_chequier'];
    $chequier = $chequierC->getChequierById($id_chequier);
} else if ($id_chequier > 0) {
    // Si on demande pour un chéquier (on prend le dernier chèque)
    $chequier = $chequierC->getChequierById($id_chequier);
    if (!$chequier) {
        die("Chéquier introuvable.");
    }
    $cheques = $chequeC->listChequesByChequier($id_chequier);
    // On suppose que listChequesByChequier retourne par date décroissante ou que le premier est le plus récent
    $lastCheque = !empty($cheques) ? $cheques[0] : null;
} else {
    die("Paramètres invalides.");
}

if (!$chequier) {
    die("Chéquier introuvable.");
}

if (!$lastCheque) {
    echo "<script>alert('Aucun chèque n\'a encore été saisi pour ce chéquier.'); window.close();</script>";
    exit();
}

$dateEmission = new DateTime($lastCheque['date_emission']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation de Chèque - <?= htmlspecialchars($lastCheque['numero_cheque']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg: #ffffff;
            --surface: #f8fafc;
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm;
            margin: 10mm auto;
            background: var(--bg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 30px;
            margin-bottom: 50px;
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--primary);
        }

        .bank-info {
            text-align: right;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .bank-info strong {
            color: var(--text);
            font-size: 1rem;
            display: block;
            margin-bottom: 4px;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 1.8rem;
            margin-bottom: 50px;
            color: var(--text);
            font-weight: 700;
        }

        .content {
            font-size: 1.05rem;
            text-align: justify;
        }

        .content p {
            margin-bottom: 20px;
        }

        .details-box {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            margin: 40px 0;
            background: var(--surface);
            position: relative;
            overflow: hidden;
        }

        .details-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border);
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .detail-val {
            font-weight: 700;
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        .montant-highlight {
            color: var(--primary-dark);
            font-size: 1.2rem;
        }

        .footer {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 250px;
        }

        .signature-line {
            border-bottom: 1px solid var(--text);
            margin-bottom: 15px;
            height: 80px;
            position: relative;
        }

        .signature-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stamp {
            position: absolute;
            bottom: 150px;
            right: 120px;
            width: 180px;
            height: 180px;
            border: 4px double rgba(99, 102, 241, 0.15);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: rgba(99, 102, 241, 0.15);
            transform: rotate(-20deg);
            pointer-events: none;
            font-size: 0.75rem;
            text-align: center;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: #fff;
            color: var(--text);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .page {
                margin: 0;
                box-shadow: none;
                width: 100%;
                min-height: 100%;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-secondary" onclick="window.close()">Fermer</button>
        <button class="btn" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
            Imprimer l'attestation
        </button>
    </div>

    <div class="page">
        <div class="header">
            <div class="logo">Legal<span>Fin</span></div>
            <div class="bank-info">
                <strong>LegalFin Bank S.A.</strong>
                Siège Social : Avenue Habib Bourguiba, Tunis<br>
                Tél : +216 71 000 000 | Email : contact@legalfin.tn
            </div>
        </div>

        <h1>Attestation d'Émission de Chèque</h1>

        <div class="content">
            <p>La banque <strong>LegalFin Bank</strong> certifie par la présente l'émission du chèque référencé ci-dessous, rattaché au chéquier n° <strong><?= htmlspecialchars($chequier['numero_chequier']) ?></strong> au nom de <strong><?= htmlspecialchars($chequier['nom_client'] ?? 'Client') ?></strong>.</p>
            
            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Numéro du Chèque</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['numero_cheque']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Montant</span>
                    <span class="detail-val montant-highlight"><?= number_format($lastCheque['montant'], 3, '.', ' ') ?> TND</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Montant en lettres</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['lettres']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bénéficiaire</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['beneficiaire']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">N° Pièce d'identité (Bénéf.)</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['cin_beneficiaire'] ?? 'Non spécifié') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">RIB Bénéficiaire</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['rib_beneficiaire']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date d'émission</span>
                    <span class="detail-val"><?= $dateEmission->format('d/m/Y') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Agence émettrice</span>
                    <span class="detail-val"><?= htmlspecialchars($lastCheque['agence']) ?></span>
                </div>
            </div>

            <p>Cette attestation est délivrée à la demande de l'intéressé pour servir et valoir ce que de droit.</p>
            
            <p style="margin-top: 60px; text-align: right; font-weight: 600;">
                Fait à Tunis, le <?= date('d/m/Y') ?>
            </p>
        </div>

        <div class="footer">
            <div class="signature-box">
                <div class="signature-title">Signature du Client</div>
                <div class="signature-line"></div>
                <div class="detail-val"><?= htmlspecialchars($chequier['nom_client'] ?? 'Client') ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Cachet de la Banque</div>
                <div class="signature-line"></div>
                <div class="detail-val">Le Directeur d'Agence</div>
            </div>
        </div>

        <div class="stamp">
            DOCUMENT OFFICIEL<br>CERTIFIÉ PAR WEBORA<br>BANQUE LEGALFIN<br><?= date('Y') ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                // window.print();
            }, 500);
        };
    </script>
</body>
</html>

