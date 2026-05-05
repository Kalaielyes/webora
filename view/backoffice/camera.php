<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accès Backoffice — NexaBank</title>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      min-height:100vh;
      background:#060d1a;
      display:flex; align-items:center; justify-content:center;
      font-family: 'Segoe UI', sans-serif;
    }
    .wrapper {
      display:flex; gap:2rem;
      align-items:stretch;
      max-width:900px; width:100%;
      padding:1rem;
    }
    .card {
      background:#0f1e35;
      border:1px solid #1e3a5f;
      border-radius:16px;
      padding:2rem;
      text-align:center;
      flex:1;
      box-shadow: 0 0 30px rgba(59,130,246,0.1);
    }
    .card h2 { color:#e2e8f0; font-size:1.1rem; margin-bottom:.4rem; }
    .card p  { color:#64748b; font-size:.85rem; margin-bottom:1.2rem; }
    .divider {
      display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      color:#334155; font-size:.85rem; gap:.5rem;
    }
    .divider::before, .divider::after {
      content:''; width:1px; height:60px;
      background:#1e3a5f;
    }
    .video-wrapper {
      position:relative; display:inline-block;
      border-radius:10px; overflow:hidden;
    }
    video { display:block; border-radius:10px; width:100%; }
    canvas.overlay { position:absolute; top:0; left:0; width:100%; height:100%; }
    .frame {
      position:absolute; inset:0;
      border:2px solid #3b82f6; border-radius:10px;
      pointer-events:none;
    }
    .frame.scanning { animation: scan 1.5s ease-in-out infinite; }
    .frame.success  { border-color:#10b981; }
    .frame.fail     { border-color:#ef4444; }
    @keyframes scan {
      0%,100% { box-shadow: inset 0 0 0 rgba(59,130,246,0); }
      50%      { box-shadow: inset 0 0 15px rgba(59,130,246,0.3); }
    }
    .progress-bar {
      height:3px; background:#1e293b; border-radius:2px; margin:.6rem 0;
    }
    .progress-fill {
      height:100%; background:#3b82f6; width:0%;
      transition:width .3s ease; border-radius:2px;
    }
    #status {
      padding:.7rem 1rem; border-radius:8px;
      font-size:.85rem; background:#1e293b; color:#94a3b8;
      min-height:42px; margin:.5rem 0;
    }
    #status.ok   { background:#064e3b; color:#6ee7b7; }
    #status.fail { background:#450a0a; color:#fca5a5; }
    #attempts { color:#475569; font-size:.75rem; margin-top:.3rem; }
    .pin-display {
      display:flex; justify-content:center; gap:.8rem;
      margin:1.2rem 0;
    }
    .pin-dot {
      width:16px; height:16px;
      border-radius:50%;
      background:#1e293b;
      border:2px solid #334155;
      transition:all .2s;
    }
    .pin-dot.filled { background:#3b82f6; border-color:#3b82f6; }
    .pin-dot.error  { background:#ef4444; border-color:#ef4444; }
    .pin-dot.ok     { background:#10b981; border-color:#10b981; }
    .keypad {
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap:.6rem;
      max-width:220px;
      margin:0 auto;
    }
    .key {
      padding:.9rem;
      background:#1e293b;
      color:#e2e8f0;
      border:1px solid #334155;
      border-radius:10px;
      font-size:1.1rem;
      cursor:pointer;
      transition:all .15s;
      user-select:none;
    }
    .key:hover  { background:#2a3f5f; border-color:#3b82f6; }
    .key:active { transform:scale(.95); }
    .key.del    { color:#f87171; }
    .key.empty  { visibility:hidden; }
    #pinStatus {
      margin-top:1rem; padding:.7rem;
      border-radius:8px; font-size:.85rem;
      background:#1e293b; color:#94a3b8;
      min-height:38px;
    }
    #pinStatus.ok   { background:#064e3b; color:#6ee7b7; }
    #pinStatus.fail { background:#450a0a; color:#fca5a5; }
    @media(max-width:640px) {
      .wrapper { flex-direction:column; }
      .divider { flex-direction:row; }
      .divider::before, .divider::after { width:60px; height:1px; }
    }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- RECONNAISSANCE FACIALE -->
  <div class="card">
    <h2>🔐 Reconnaissance Faciale</h2>
    <p>Regardez la caméra pour vous identifier</p>
    <div class="video-wrapper">
      <video id="video" width="320" height="240" autoplay muted playsinline></video>
      <canvas id="overlay" class="overlay" width="320" height="240"></canvas>
      <div class="frame" id="frame"></div>
    </div>
    <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
    <div id="status">⏳ Chargement des modèles...</div>
    <div id="attempts"></div>
  </div>

  <!-- SÉPARATEUR -->
  <div class="divider">OU</div>

  <!-- PIN -->
  <div class="card">
    <h2>🔑 Code PIN</h2>
    <p>Entrez votre code à 4 chiffres</p>
    <div class="pin-display">
      <div class="pin-dot" id="dot0"></div>
      <div class="pin-dot" id="dot1"></div>
      <div class="pin-dot" id="dot2"></div>
      <div class="pin-dot" id="dot3"></div>
    </div>
    <div class="keypad">
      <button class="key" onclick="pressKey('1')">1</button>
      <button class="key" onclick="pressKey('2')">2</button>
      <button class="key" onclick="pressKey('3')">3</button>
      <button class="key" onclick="pressKey('4')">4</button>
      <button class="key" onclick="pressKey('5')">5</button>
      <button class="key" onclick="pressKey('6')">6</button>
      <button class="key" onclick="pressKey('7')">7</button>
      <button class="key" onclick="pressKey('8')">8</button>
      <button class="key" onclick="pressKey('9')">9</button>
      <button class="key empty"></button>
      <button class="key" onclick="pressKey('0')">0</button>
      <button class="key del" onclick="deleteKey()">⌫</button>
    </div>
    <div id="pinStatus">Entrez votre code PIN</div>
  </div>

</div>

<script>
// ==================== CONFIG ====================
const MODEL_URL    = '../../controller/models';
const REDIRECT_URL = 'backoffice_chequier.php';
const MAX_ATTEMPTS = 3;
const THRESHOLD    = 0.5;

// ==================== VARIABLES ====================
let attempts     = 0;
let adminDescriptors = [];
let pinValue     = '';
let pinAttempts  = 0;
const MAX_PIN_ATTEMPTS = 3;

// ==================== ÉLÉMENTS ====================
const video      = document.getElementById('video');
const overlay    = document.getElementById('overlay');
const ctx        = overlay.getContext('2d');
const statusEl   = document.getElementById('status');
const frameEl    = document.getElementById('frame');
const progressEl = document.getElementById('progressFill');
const pinStatusEl= document.getElementById('pinStatus');

// ==================== FONCTIONS COMMUNES ====================
function setStatus(msg, type = '') {
  statusEl.textContent = msg;
  statusEl.className   = type;
}
function setProgress(pct) {
  progressEl.style.width = pct + '%';
}
function redirectToBackoffice() {
  window.location.href = REDIRECT_URL;
}

// ==================== PIN ====================
function pressKey(digit) {
  if (pinAttempts >= MAX_PIN_ATTEMPTS) return;
  if (pinValue.length >= 4) return;
  pinValue += digit;
  updateDots();
  if (pinValue.length === 4) {
    setTimeout(() => checkPin(), 300);
  }
}

function deleteKey() {
  pinValue = pinValue.slice(0, -1);
  updateDots();
  pinStatusEl.textContent = 'Entrez votre code PIN';
  pinStatusEl.className   = '';
}

function updateDots(state = '') {
  for (let i = 0; i < 4; i++) {
    const dot = document.getElementById('dot' + i);
    dot.className = 'pin-dot';
    if (i < pinValue.length) dot.classList.add(state || 'filled');
  }
}

async function checkPin() {
  const res = await fetch('../../controller/FaceAuthController.php?action=verifyPin', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ admin_id: 1, pin: pinValue })
  });

  const result = await res.json();

  if (result.success) {
    updateDots('ok');
    pinStatusEl.textContent = '✅ Code correct ! Redirection...';
    pinStatusEl.className   = 'ok';
    setTimeout(redirectToBackoffice, 1500);
  } else {
    pinAttempts++;
    updateDots('error');
    pinStatusEl.className = 'fail';

    if (pinAttempts >= MAX_PIN_ATTEMPTS) {
      pinStatusEl.textContent = '🚫 Trop de tentatives. Bloqué.';
    } else {
      pinStatusEl.textContent = `❌ Code incorrect. ${MAX_PIN_ATTEMPTS - pinAttempts} essai(s) restant(s).`;
      setTimeout(() => {
        pinValue = '';
        updateDots();
        pinStatusEl.textContent = 'Entrez votre code PIN';
        pinStatusEl.className   = '';
      }, 1200);
    }
  }
}

// ==================== RECONNAISSANCE FACIALE ====================
async function loadAdminDescriptors() {
  const res  = await fetch('../../controller/FaceAuthController.php?action=getDescriptors');
  const data = await res.json();
  return data.map(row => ({
    adminId:    row.admin_id,
    descriptor: new Float32Array(JSON.parse(row.face_descriptor))
  }));
}

async function init() {
  try {
    setStatus('📦 Chargement modèle détection...'); setProgress(20);
    await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);

    setStatus('📦 Chargement modèle landmarks...'); setProgress(50);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);

    setStatus('📦 Chargement modèle reconnaissance...'); setProgress(75);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

    setStatus('📷 Activation caméra...'); setProgress(85);
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { width: 320, height: 240, facingMode: 'user' }
    });
    video.srcObject = stream;

    setStatus('🔄 Chargement profils...'); setProgress(95);
    adminDescriptors = await loadAdminDescriptors();

    if (adminDescriptors.length === 0) {
      setStatus('❌ Aucun profil enregistré.', 'fail');
      return;
    }

    setProgress(100);
    setStatus('✅ Prêt. Regardez la caméra...');
    frameEl.classList.add('scanning');
    video.addEventListener('loadeddata', () => recognitionLoop());

  } catch (err) {
    setStatus('❌ Erreur: ' + err.message, 'fail');
    console.error(err);
  }
}

async function recognitionLoop() {
  if (attempts >= MAX_ATTEMPTS) return;

  document.getElementById('attempts').textContent =
    `Tentative ${attempts + 1} / ${MAX_ATTEMPTS}`;

  await new Promise(r => setTimeout(r, 800));

  try {
    const detection = await faceapi
      .detectSingleFace(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
      .withFaceLandmarks()
      .withFaceDescriptor();

    ctx.clearRect(0, 0, overlay.width, overlay.height);

    if (!detection) {
      setStatus('👤 Aucun visage détecté. Positionnez-vous face à la caméra.');
      await new Promise(r => setTimeout(r, 1500));
      recognitionLoop();
      return;
    }

    const box = detection.detection.box;
    ctx.strokeStyle = '#3b82f6';
    ctx.lineWidth   = 2;
    ctx.strokeRect(box.x, box.y, box.width, box.height);

    let bestMatch    = null;
    let bestDistance = Infinity;

    for (const admin of adminDescriptors) {
      const distance = faceapi.euclideanDistance(detection.descriptor, admin.descriptor);
      if (distance < bestDistance) {
        bestDistance = distance;
        bestMatch    = admin;
      }
    }

    if (bestDistance <= THRESHOLD) {
      frameEl.className = 'frame success';
      ctx.strokeStyle   = '#10b981';
      ctx.lineWidth     = 3;
      ctx.strokeRect(box.x, box.y, box.width, box.height);
      setStatus('✅ Identité confirmée ! Redirection...', 'ok');

      await fetch('../../controller/FaceAuthController.php?action=verifyAndLogin', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ admin_id: bestMatch.adminId })
      });

      setTimeout(redirectToBackoffice, 1500);

    } else {
      attempts++;
      ctx.strokeStyle = '#ef4444';
      ctx.lineWidth   = 2;
      ctx.strokeRect(box.x, box.y, box.width, box.height);

      if (attempts >= MAX_ATTEMPTS) {
        frameEl.className = 'frame fail';
        setStatus('🚫 Visage non reconnu. Utilisez le PIN.', 'fail');
        await fetch('../../controller/FaceAuthController.php?action=logFail', { method: 'POST' });
      } else {
        setStatus(`❌ Non reconnu. ${MAX_ATTEMPTS - attempts} tentative(s) restante(s).`);
        await new Promise(r => setTimeout(r, 1500));
        recognitionLoop();
      }
    }

  } catch (err) {
    setStatus('⚠️ Erreur: ' + err.message);
    await new Promise(r => setTimeout(r, 2000));
    recognitionLoop();
  }
}

// ==================== DÉMARRAGE ====================
window.addEventListener('load', () => {
  init();
});
</script>
</body>
</html>