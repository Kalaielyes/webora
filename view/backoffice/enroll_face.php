<?php
/**
 * Face Enrollment for Admins - Unified UI
 */
require_once __DIR__ . '/../../models/Session.php';
require_once __DIR__ . '/../../models/config.php';

Session::requireAdmin('../frontoffice/login.php');

$adminId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrôlement Facial - LegaFin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../view/assets/css/backoffice/Utilisateur.css">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        .enroll-container { max-width: 600px; margin: 0 auto; background: var(--bg2); border-radius: 16px; border: 1px solid var(--border); padding: 2rem; text-align: center; }
        video { width: 100%; border-radius: 12px; border: 2px solid var(--border); background: #000; margin-bottom: 1.5rem; }
        #status { padding: 1rem; border-radius: 8px; font-size: .9rem; margin-bottom: 1.5rem; background: var(--bg3); color: var(--muted); }
        #status.ok { background: rgba(34,197,94,.1); color: #16a34a; }
        #status.fail { background: rgba(244,63,94,.1); color: #e11d48; }
        .pin-section { background: var(--bg3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .pin-inputs { display: flex; gap: .8rem; justify-content: center; margin-top: 1rem; }
        .pin-inputs input { width: 50px; height: 50px; text-align: center; font-size: 1.2rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg2); color: var(--text); font-family: var(--fm); }
        .pin-inputs input:focus { border-color: var(--blue); outline: none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/sidebar_unified.php'; ?>

    <div class="main" id="main-content">
        <div class="topbar">
            <div class="tb-left">
                <div class="page-title">Biométrie & Sécurité</div>
                <div class="breadcrumb">LegaFin / Enrôlement Facial</div>
            </div>
            <div class="tb-right">
                <div style="font-size:.72rem;color:var(--muted);"><?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <div class="content">
            <div class="enroll-container">
                <h2 style="font-family:var(--fh); margin-bottom: 1rem;">📸 Enrôlement Facial</h2>
                <p style="color: var(--muted); margin-bottom: 2rem;">Enregistrez votre visage et configurez votre code PIN pour sécuriser vos opérations de chèques.</p>

                <video id="video" autoplay muted playsinline></video>
                <div id="status">⏳ Chargement des modèles biométriques...</div>

                <div class="pin-section">
                    <label style="font-weight: 600; font-size: .85rem; color: var(--text);">🔑 Définissez votre code PIN à 4 chiffres</label>
                    <div class="pin-inputs">
                        <input type="password" id="p1" maxlength="1" inputmode="numeric">
                        <input type="password" id="p2" maxlength="1" inputmode="numeric">
                        <input type="password" id="p3" maxlength="1" inputmode="numeric">
                        <input type="password" id="p4" maxlength="1" inputmode="numeric">
                    </div>
                </div>

                <button id="btnCapture" class="btn-primary" style="width: 100%; justify-content: center; padding: 1rem;" disabled onclick="captureAll()">
                    📸 Enregistrer Visage & PIN
                </button>
                <p style="font-size: .7rem; color: var(--muted); margin-top: 1rem;">
                    Assurez-vous d'être dans un endroit bien éclairé et de regarder directement la caméra.
                </p>
            </div>
        </div>
    </div>

    <script>
    const MODEL_URL = '../../view/assets/ai-models';
    const ADMIN_ID  = <?= $adminId ?>;

    const video      = document.getElementById('video');
    const statusEl   = document.getElementById('status');
    const btnCapture = document.getElementById('btnCapture');
    const pinInputs  = [
        document.getElementById('p1'),
        document.getElementById('p2'),
        document.getElementById('p3'),
        document.getElementById('p4')
    ];

    // Auto-focus PIN inputs
    pinInputs.forEach((input, i) => {
        input.addEventListener('input', () => {
            if (input.value && i < 3) pinInputs[i + 1].focus();
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && i > 0) pinInputs[i - 1].focus();
        });
    });

    function setStatus(msg, type = '') {
        statusEl.textContent = msg;
        statusEl.className   = type;
    }

    function getPin() {
        return pinInputs.map(i => i.value).join('');
    }

    async function init() {
        try {
            setStatus('📦 Initialisation des modèles...');
            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;

            setStatus('✅ Prêt pour l\'enrôlement.', 'ok');
            btnCapture.disabled = false;

        } catch (err) {
            console.error(err);
            setStatus('❌ Erreur d\'initialisation : ' + err.message, 'fail');
        }
    }

    async function captureAll() {
        const pin = getPin();

        if (pin.length !== 4 || !(/^\d{4}$/.test(pin))) {
            setStatus('❌ Veuillez entrer un PIN valide de 4 chiffres.', 'fail');
            return;
        }

        setStatus('⏳ Analyse de vos traits faciaux...');
        btnCapture.disabled = true;

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            setStatus('❌ Visage non détecté. Assurez-vous d\'être bien visible.', 'fail');
            btnCapture.disabled = false;
            return;
        }

        const descriptor = Array.from(detection.descriptor);

        // 1. Sauvegarder le visage
        try {
            const faceRes = await fetch('../../controller/api/chequier-api.php/biometric/save-face', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ admin_id: ADMIN_ID, descriptor })
            });
            const faceResult = await faceRes.json();

            if (!faceResult.success) {
                setStatus('❌ Erreur lors de l\'enregistrement du visage.', 'fail');
                btnCapture.disabled = false;
                return;
            }

            // 2. Sauvegarder le PIN
            const pinRes = await fetch('../../controller/api/chequier-api.php/biometric/save-pin', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ admin_id: ADMIN_ID, pin })
            });
            const pinResult = await pinRes.json();

            if (pinResult.success) {
                setStatus('✅ Visage et PIN enregistrés avec succès !', 'ok');
                setTimeout(() => {
                    window.location.href = 'chequier_dashboard.php';
                }, 2000);
            } else {
                setStatus('❌ Erreur lors de l\'enregistrement du PIN.', 'fail');
                btnCapture.disabled = false;
            }
        } catch (err) {
            setStatus('❌ Erreur de communication avec le serveur.', 'fail');
            btnCapture.disabled = false;
        }
    }

    window.addEventListener('load', () => init());
    </script>
</body>
</html>
