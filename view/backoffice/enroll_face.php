<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Enrôlement Facial Admin</title>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      min-height:100vh; background:#060d1a;
      display:flex; align-items:center; justify-content:center;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      background:#0f1e35; border:1px solid #1e3a5f;
      border-radius:16px; padding:2.5rem;
      text-align:center; max-width:520px; width:100%;
    }
    h1 { color:#e2e8f0; font-size:1.4rem; margin-bottom:.5rem; }
    p.subtitle { color:#64748b; font-size:.9rem; margin-bottom:1.5rem; }
    video { border-radius:12px; border:2px solid #1e3a5f; display:block; width:100%; }
    #status {
      margin:1.2rem 0; padding:.8rem 1.2rem;
      border-radius:8px; font-size:.95rem;
      background:#1e293b; color:#94a3b8; min-height:46px;
    }
    #status.ok   { background:#064e3b; color:#6ee7b7; }
    #status.fail { background:#450a0a; color:#fca5a5; }

    .pin-section {
      margin:1.2rem 0; padding:1.2rem;
      background:#1e293b; border-radius:10px;
      border:1px solid #334155;
    }
    .pin-section label {
      display:block; color:#94a3b8;
      font-size:.85rem; margin-bottom:.6rem;
    }
    .pin-input-wrapper {
      display:flex; gap:.5rem; justify-content:center;
    }
    .pin-input-wrapper input {
      width:50px; height:50px;
      text-align:center; font-size:1.3rem;
      background:#0f1e35; color:#e2e8f0;
      border:2px solid #334155; border-radius:8px;
      outline:none;
    }
    .pin-input-wrapper input:focus { border-color:#3b82f6; }

    button {
      margin-top:1rem; padding:.75rem 2rem;
      background:#3b82f6; color:#fff;
      border:none; border-radius:8px;
      cursor:pointer; font-size:1rem; width:100%;
    }
    button:disabled { background:#1e3a5f; color:#475569; cursor:not-allowed; }
    button:hover:not(:disabled) { background:#2563eb; }
  </style>
</head>
<body>
<div class="container">
  <h1>📸 Enrôlement Facial Admin</h1>
  <p class="subtitle">Enregistrez votre visage et votre PIN</p>

  <video id="video" autoplay muted playsinline></video>
  <div id="status">⏳ Chargement des modèles...</div>

  <!-- Section PIN -->
  <div class="pin-section">
    <label>🔑 Définissez votre PIN à 4 chiffres</label>
    <div class="pin-input-wrapper">
      <input type="password" id="p1" maxlength="1" inputmode="numeric" pattern="[0-9]">
      <input type="password" id="p2" maxlength="1" inputmode="numeric" pattern="[0-9]">
      <input type="password" id="p3" maxlength="1" inputmode="numeric" pattern="[0-9]">
      <input type="password" id="p4" maxlength="1" inputmode="numeric" pattern="[0-9]">
    </div>
  </div>

  <button id="btnCapture" disabled onclick="captureAll()">📸 Enregistrer visage + PIN</button>
</div>

<script>
const MODEL_URL = '../../models';
const ADMIN_ID  = 1;

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
    setStatus('📦 Chargement modèles...');
    await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;

    setStatus('✅ Prêt. Placez votre visage puis cliquez.');
    btnCapture.disabled = false;

  } catch (err) {
    setStatus('❌ Erreur: ' + err.message, 'fail');
  }
}

async function captureAll() {
  const pin = getPin();

  if (pin.length !== 4 || !(/^\d{4}$/.test(pin))) {
    setStatus('❌ Entrez un PIN de 4 chiffres valide.', 'fail');
    return;
  }

  setStatus('⏳ Analyse du visage...');
  btnCapture.disabled = true;

  const detection = await faceapi
    .detectSingleFace(video)
    .withFaceLandmarks()
    .withFaceDescriptor();

  if (!detection) {
    setStatus('❌ Aucun visage détecté. Réessayez.', 'fail');
    btnCapture.disabled = false;
    return;
  }

  const descriptor = Array.from(detection.descriptor);

  // 1. Sauvegarder le visage
  const faceRes = await fetch('../../controller/FaceAuthController.php?action=saveFace', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ admin_id: ADMIN_ID, descriptor })
  });
  const faceResult = await faceRes.json();

  if (!faceResult.success) {
    setStatus('❌ Erreur sauvegarde visage.', 'fail');
    btnCapture.disabled = false;
    return;
  }

  // 2. Sauvegarder le PIN
  const pinRes = await fetch('../../controller/FaceAuthController.php?action=savePin', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ admin_id: ADMIN_ID, pin })
  });
  const pinResult = await pinRes.json();

  if (pinResult.success) {
    setStatus('✅ Visage + PIN enregistrés avec succès !', 'ok');
  } else {
    setStatus('❌ Erreur sauvegarde PIN.', 'fail');
    btnCapture.disabled = false;
  }
}

window.addEventListener('load', () => init());
</script>
</body>
</html>