/* ================================================================
   verify_cheque_modal.js
   Intercepte openChequeModal() pour exiger Face ID OU PIN
   avant d'ouvrir le formulaire de saisie du chèque.
   
   📁 Placer dans : view/frontoffice/
   📌 Inclure APRÈS saisiecheque.js dans frontoffice_chequier.php :
      <script src="verify_cheque_modal.js?v=..."></script>
================================================================ */

(function () {

  // ── 1. Sauvegarder l'ancienne fonction openChequeModal ──────────
  const _originalOpenChequeModal = window.openChequeModal;

  // ── 2. Remplacer openChequeModal par notre version sécurisée ────
  window.openChequeModal = function (num, nom, iban, id_chequier, edit_id) {
    // Mémoriser les paramètres pour les passer après vérification
    window._pendingChequeArgs = { num, nom, iban, id_chequier, edit_id };
    openVerifyModal();
  };

  // ── 3. Injecter le modal de vérification dans le DOM ────────────
  const modalHTML = `
  <div id="verifySecurityModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(6,13,26,0.92); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
  ">
    <div style="
      background:#0f1e35; border:1px solid #1e3a5f;
      border-radius:20px; padding:2rem;
      width:100%; max-width:820px; margin:1rem;
      box-shadow:0 0 60px rgba(59,130,246,0.15);
      font-family:'Segoe UI',sans-serif;
    ">
      <!-- Header -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
          <div style="color:#e2e8f0; font-weight:700; font-size:1.1rem;">🔒 Vérification requise</div>
          <div style="color:#64748b; font-size:.8rem; margin-top:.2rem;">Identifiez-vous pour saisir un chèque</div>
        </div>
        <button onclick="closeVerifyModal()" style="
          background:none; border:none; color:#475569;
          cursor:pointer; font-size:1.4rem; line-height:1;
          padding:.3rem;
        ">✕</button>
      </div>

      <!-- Corps : Face ID | divider | PIN -->
      <div style="display:flex; gap:1.5rem; align-items:stretch; flex-wrap:wrap;">

        <!-- ─── FACE ID ─── -->
        <div style="flex:1; min-width:280px; background:#0a1628; border:1px solid #1e3a5f; border-radius:14px; padding:1.5rem; text-align:center;">
          <div style="color:#e2e8f0; font-weight:600; font-size:1rem; margin-bottom:.3rem;">🔐 Reconnaissance Faciale</div>
          <div style="color:#64748b; font-size:.78rem; margin-bottom:1rem;">Regardez la caméra pour vous identifier</div>
          
          <div style="position:relative; display:inline-block; border-radius:10px; overflow:hidden;">
            <video id="vcm_video" width="280" height="210" autoplay muted playsinline style="display:block; border-radius:10px;"></video>
            <canvas id="vcm_overlay" width="280" height="210" style="position:absolute;top:0;left:0;width:100%;height:100%;"></canvas>
            <div id="vcm_frame" style="
              position:absolute; inset:0; border:2px solid #3b82f6;
              border-radius:10px; pointer-events:none;
              animation: vcm_scan 1.5s ease-in-out infinite;
            "></div>
          </div>
          
          <div style="height:3px; background:#1e293b; border-radius:2px; margin:.6rem 0;">
            <div id="vcm_progress" style="height:100%; background:#3b82f6; width:0%; transition:width .3s; border-radius:2px;"></div>
          </div>
          <div id="vcm_status" style="
            padding:.6rem .8rem; border-radius:8px; font-size:.8rem;
            background:#1e293b; color:#94a3b8; min-height:38px; margin:.4rem 0;
          ">⏳ Initialisation...</div>
          <div id="vcm_attempts" style="color:#475569; font-size:.72rem; margin-top:.2rem;"></div>
        </div>

        <!-- ─── DIVIDER ─── -->
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; color:#334155; font-size:.8rem; gap:.5rem; min-width:30px;">
          <div style="width:1px; height:60px; background:#1e3a5f;"></div>
          OU
          <div style="width:1px; height:60px; background:#1e3a5f;"></div>
        </div>

        <!-- ─── PIN ─── -->
        <div style="flex:1; min-width:240px; background:#0a1628; border:1px solid #1e3a5f; border-radius:14px; padding:1.5rem; text-align:center;">
          <div style="color:#e2e8f0; font-weight:600; font-size:1rem; margin-bottom:.3rem;">🔑 Code PIN</div>
          <div style="color:#64748b; font-size:.78rem; margin-bottom:1.2rem;">Entrez votre code à 4 chiffres</div>
          
          <!-- Dots -->
          <div style="display:flex; justify-content:center; gap:.8rem; margin:1rem 0;" id="vcm_dots_row">
            <div class="vcm_dot" id="vcmd0"></div>
            <div class="vcm_dot" id="vcmd1"></div>
            <div class="vcm_dot" id="vcmd2"></div>
            <div class="vcm_dot" id="vcmd3"></div>
          </div>
          
          <!-- Keypad -->
          <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; max-width:200px; margin:0 auto;">
            ${[1,2,3,4,5,6,7,8,9].map(n => `
              <button onclick="vcm_pressKey('${n}')" style="
                padding:.8rem; background:#1e293b; color:#e2e8f0;
                border:1px solid #334155; border-radius:9px;
                font-size:1rem; cursor:pointer; transition:all .1s;
              " onmouseover="this.style.background='#2a3f5f'" onmouseout="this.style.background='#1e293b'">${n}</button>
            `).join('')}
            <button style="visibility:hidden"></button>
            <button onclick="vcm_pressKey('0')" style="
              padding:.8rem; background:#1e293b; color:#e2e8f0;
              border:1px solid #334155; border-radius:9px;
              font-size:1rem; cursor:pointer; transition:all .1s;
            " onmouseover="this.style.background='#2a3f5f'" onmouseout="this.style.background='#1e293b'">0</button>
            <button onclick="vcm_deleteKey()" style="
              padding:.8rem; background:#1e293b; color:#f87171;
              border:1px solid #334155; border-radius:9px;
              font-size:1rem; cursor:pointer; transition:all .1s;
            " onmouseover="this.style.background='#2a3f5f'" onmouseout="this.style.background='#1e293b'">⌫</button>
          </div>
          
          <div id="vcm_pin_status" style="
            margin-top:1rem; padding:.6rem; border-radius:8px; font-size:.8rem;
            background:#1e293b; color:#94a3b8; min-height:36px;
          ">Entrez votre code PIN</div>
        </div>

      </div>
    </div>
  </div>

  <style>
    .vcm_dot {
      width:14px; height:14px; border-radius:50%;
      background:#1e293b; border:2px solid #334155; transition:all .2s;
    }
    .vcm_dot.filled { background:#3b82f6; border-color:#3b82f6; }
    .vcm_dot.error  { background:#ef4444; border-color:#ef4444; }
    .vcm_dot.ok     { background:#10b981; border-color:#10b981; }
    @keyframes vcm_scan {
      0%,100% { box-shadow: inset 0 0 0 rgba(59,130,246,0); }
      50%      { box-shadow: inset 0 0 15px rgba(59,130,246,0.3); }
    }
    @keyframes vcm_success_frame {
      0%,100% { border-color:#10b981; }
    }
    @media(max-width:640px) {
      #verifySecurityModal > div > div:last-child { flex-direction:column; }
    }
  </style>
  `;

  document.addEventListener('DOMContentLoaded', function () {
    document.body.insertAdjacentHTML('beforeend', modalHTML);
  });

  // ── 4. Variables d'état ──────────────────────────────────────────
  const MODEL_URL       = '../../assets/ai-models';
  const THRESHOLD       = 0.5;
  const MAX_FACE        = 3;
  const MAX_PIN_TRIES   = 3;

  let vcm_stream        = null;
  let vcm_descriptors   = [];
  let vcm_faceAttempts  = 0;
  let vcm_pinValue      = '';
  let vcm_pinAttempts   = 0;
  let vcm_done          = false;
  let vcm_loopRunning   = false;

  // ── 5. Ouvrir / Fermer le modal ──────────────────────────────────
  window.openVerifyModal = function () {
    const m = document.getElementById('verifySecurityModal');
    if (!m) return;
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    vcm_reset();
    vcm_init();
  };

  window.closeVerifyModal = function () {
    const m = document.getElementById('verifySecurityModal');
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    vcm_stopCamera();
  };

  // ── 6. Reset état ────────────────────────────────────────────────
  function vcm_reset() {
    vcm_faceAttempts = 0;
    vcm_pinValue     = '';
    vcm_pinAttempts  = 0;
    vcm_done         = false;
    vcm_loopRunning  = false;
    vcm_setStatus('⏳ Initialisation...');
    vcm_setProgress(0);
    vcm_updateDots();
    document.getElementById('vcm_pin_status').textContent = 'Entrez votre code PIN';
    document.getElementById('vcm_pin_status').style.background = '#1e293b';
    document.getElementById('vcm_pin_status').style.color = '#94a3b8';
    document.getElementById('vcm_attempts').textContent = '';
    const frame = document.getElementById('vcm_frame');
    if (frame) { frame.style.borderColor = '#3b82f6'; frame.style.animation = 'vcm_scan 1.5s ease-in-out infinite'; }
  }

  // ── 7. Helpers UI ────────────────────────────────────────────────
  function vcm_setStatus(msg, ok) {
    const el = document.getElementById('vcm_status');
    if (!el) return;
    el.textContent = msg;
    if (ok === true)  { el.style.background = '#064e3b'; el.style.color = '#6ee7b7'; }
    else if (ok === false) { el.style.background = '#450a0a'; el.style.color = '#fca5a5'; }
    else { el.style.background = '#1e293b'; el.style.color = '#94a3b8'; }
  }
  function vcm_setProgress(pct) {
    const el = document.getElementById('vcm_progress');
    if (el) el.style.width = pct + '%';
  }
  function vcm_updateDots(state) {
    for (let i = 0; i < 4; i++) {
      const d = document.getElementById('vcmd' + i);
      if (!d) continue;
      d.className = 'vcm_dot';
      if (i < vcm_pinValue.length) d.classList.add(state || 'filled');
    }
  }
  function vcm_setPinStatus(msg, ok) {
    const el = document.getElementById('vcm_pin_status');
    if (!el) return;
    el.textContent = msg;
    if (ok === true)  { el.style.background = '#064e3b'; el.style.color = '#6ee7b7'; }
    else if (ok === false) { el.style.background = '#450a0a'; el.style.color = '#fca5a5'; }
    else { el.style.background = '#1e293b'; el.style.color = '#94a3b8'; }
  }

  // ── 8. Succès → ouvrir le vrai modal chèque ─────────────────────
  async function vcm_success(adminId) {
    if (vcm_done) return;
    vcm_done = true;

    // Marquer session PHP
    await fetch('../../controller/FaceAuthController.php?action=verifyCheque', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ admin_id: adminId || 1 })
    });

    setTimeout(function () {
      closeVerifyModal();
      // Appeler l'original avec les paramètres mémorisés
      const a = window._pendingChequeArgs;
      if (a && _originalOpenChequeModal) {
        _originalOpenChequeModal(a.num, a.nom, a.iban, a.id_chequier, a.edit_id);
      }
    }, 1200);
  }

  // ── 9. Arrêter la caméra ─────────────────────────────────────────
  function vcm_stopCamera() {
    if (vcm_stream) {
      vcm_stream.getTracks().forEach(t => t.stop());
      vcm_stream = null;
    }
    vcm_loopRunning = false;
  }

  // ── 10. Initialisation Face ID ───────────────────────────────────
  async function vcm_init() {
    try {
      vcm_setStatus('📷 Activation caméra...'); vcm_setProgress(50);
      vcm_stream = await navigator.mediaDevices.getUserMedia({
        video: { width: 280, height: 210, facingMode: 'user' }
      });
      const video = document.getElementById('vcm_video');
      video.srcObject = vcm_stream;

      vcm_setProgress(100);
      vcm_setStatus('✅ Prêt. Regardez la caméra...');
      video.addEventListener('loadeddata', () => {
          vcm_loopRunning = true;
          _vcm_recognize();
      }, { once: true });

    } catch (err) {
      vcm_setStatus('❌ ' + err.message, false);
    }
  }

  // ── 11. Boucle reconnaissance (Server-Side) ────────────────────────
  async function _vcm_recognize() {
    if (vcm_done || !vcm_loopRunning || vcm_faceAttempts >= MAX_FACE) return;

    document.getElementById('vcm_attempts').textContent =
      `Analyse en cours... (Tentative ${vcm_faceAttempts + 1})`;

    const video   = document.getElementById('vcm_video');
    const canvas  = document.getElementById('vcm_overlay');
    const ctx     = canvas.getContext('2d');
    
    // Capture frame
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg', 0.8);

    try {
      vcm_setStatus('📡 Analyse du visage...');
      
      const res = await fetch('../../controller/BiometricOtpController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'verify_face_image',
            image: imageData,
            amountTnd: 0 // Cheque verification
        })
      });
      
      const data = await res.json();

      if (data.success) {
        const frame = document.getElementById('vcm_frame');
        if (frame) { frame.style.borderColor = '#10b981'; frame.style.animation = 'none'; }
        vcm_setStatus('✅ ' + (data.message || 'Visage reconnu !'), true);
        await vcm_success(1);
      } else {
        vcm_faceAttempts++;
        if (vcm_faceAttempts >= MAX_FACE) {
          const frame = document.getElementById('vcm_frame');
          if (frame) { frame.style.borderColor = '#ef4444'; frame.style.animation = 'none'; }
          vcm_setStatus('🚫 ' + (data.message || 'Non reconnu.'), false);
        } else {
          vcm_setStatus(`❌ ${data.message || 'Non reconnu'}. Nouvelle tentative...`);
          setTimeout(_vcm_recognize, 2000);
        }
      }
    } catch (err) {
      vcm_setStatus('⚠️ Erreur: ' + err.message);
      setTimeout(_vcm_recognize, 3000);
    }
  }

  // ── 12. PIN ──────────────────────────────────────────────────────
  window.vcm_pressKey = function (digit) {
    if (vcm_done || vcm_pinAttempts >= MAX_PIN_TRIES) return;
    if (vcm_pinValue.length >= 4) return;
    vcm_pinValue += digit;
    vcm_updateDots();
    if (vcm_pinValue.length === 4) setTimeout(vcm_checkPin, 300);
  };

  window.vcm_deleteKey = function () {
    vcm_pinValue = vcm_pinValue.slice(0, -1);
    vcm_updateDots();
    vcm_setPinStatus('Entrez votre code PIN');
  };

  async function vcm_checkPin() {
    const res    = await fetch('../../controller/FaceAuthController.php?action=verifyPin', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ admin_id: 1, pin: vcm_pinValue })
    });
    const result = await res.json();

    if (result.success) {
      vcm_updateDots('ok');
      vcm_setPinStatus('✅ Code correct ! Ouverture...', true);
      await vcm_success(1);
    } else {
      vcm_pinAttempts++;
      vcm_updateDots('error');
      vcm_setPinStatus(
        vcm_pinAttempts >= MAX_PIN_TRIES
          ? '🚫 Trop de tentatives. Bloqué.'
          : `❌ Code incorrect. ${MAX_PIN_TRIES - vcm_pinAttempts} essai(s) restant(s).`,
        false
      );
      if (vcm_pinAttempts < MAX_PIN_TRIES) {
        setTimeout(function () {
          vcm_pinValue = '';
          vcm_updateDots();
          vcm_setPinStatus('Entrez votre code PIN');
        }, 1200);
      }
    }
  }

})();
