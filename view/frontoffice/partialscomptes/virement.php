<?php
// ── VIREMENT PARTIAL — with Direct Face Scan + Conditional OTP ─────────────
require_once __DIR__ . '/../../../controller/ObjectifController.php';
require_once __DIR__ . '/../../../controller/CompteController.php';
if (!isset($userId) || !isset($comptes)) { 
    require_once __DIR__ . "/../../../models/Session.php"; Session::start();
    $userId = $_SESSION['user']['id'] ?? 0; 
    $comptes = CompteController::findByUtilisateur($userId);
}
?>

<!-- Face verification using server-side FaceVerificationService -->
<style>
.face-frame-container { position:relative; width:220px; height:220px; margin:0 auto 1.5rem; }
.face-circle-wrap { width:100%; height:100%; border-radius:50%; overflow:hidden; border:4px solid var(--border); position:relative; background:#0f172a; box-shadow:0 0 0 10px rgba(37,99,235,0.05); }
.face-circle-wrap.active { border-color:var(--primary); box-shadow:0 0 30px rgba(37,99,235,0.3); }
.face-circle-wrap.verified { border-color:var(--green); box-shadow:0 0 30px rgba(34,197,94,0.3); }
.scan-line { position:absolute; left:0; width:100%; height:2px; background:linear-gradient(to right, transparent, var(--primary), transparent); top:0; display:none; animation:scanMove 2s linear infinite; z-index:5; box-shadow:0 0 8px var(--primary); }
@keyframes scanMove { 0% { top:0%; } 50% { top:100%; } 100% { top:0%; } }
.loader-tiny { width:24px; height:24px; border:3px solid rgba(255,255,255,0.1); border-top-color:#fff; border-radius:50%; animation:spin 0.8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.scanning-text { animation: pulseText 1.5s infinite; color: var(--primary); font-weight: 700; }
@keyframes pulseText { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>

<div class="virement-container" style="max-width:550px; margin:0 auto; padding:1.5rem; background:var(--bg2); border-radius:24px; border:1px solid var(--border); box-shadow:0 15px 40px rgba(0,0,0,0.1); position:relative; overflow:hidden;">
  <div style="position:absolute; top:-80px; right:-80px; width:200px; height:200px; background:radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%); z-index:0; pointer-events:none;"></div>

  <div style="position:relative; z-index:1;">

    <!-- HEADER -->
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.5rem;">
      <div style="width:40px; height:40px; border-radius:14px; background:#2563eb; color:white; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 12px rgba(37,99,235,0.2);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      </div>
      <div>
        <h2 style="margin:0; font-size:1.2rem; font-family:Syne, sans-serif;">Transfert sécurisé</h2>
        <p id="header-subtitle" style="margin:0; font-size:0.75rem; color:var(--muted2); font-weight:500;">Vérification biométrique requise.</p>
      </div>
    </div>

    <!-- SUCCESS BANNER -->
    <?php if (!empty($_GET['ok'])): ?>
      <div style="background:rgba(34,197,94,0.1); color:#22c55e; padding:12px 16px; border-radius:12px; font-size:0.85rem; margin-bottom:1.2rem; display:flex; align-items:center; gap:10px; border:1px solid rgba(34,197,94,0.2);">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <b>Succès !</b> Transaction traitée.
      </div>
    <?php endif; ?>

    <!-- ══ STEP INDICATOR ══════════════════════════════════ -->
    <div id="step-indicator" style="display:flex; gap:6px; margin-bottom:1.4rem; align-items:center;">
      <div class="step-dot active" id="dot-1" style="flex:1; height:4px; border-radius:99px; background:#2563eb; transition:.3s;"></div>
      <div class="step-dot" id="dot-2" style="flex:1; height:4px; border-radius:99px; background:var(--border); transition:.3s;"></div>
      <div class="step-dot" id="dot-3" style="flex:1; height:4px; border-radius:99px; background:var(--border); transition:.3s;"></div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         STEP 1 — TRANSFER FORM
    ════════════════════════════════════════════════════════ -->
    <div id="step-1">
      <?php $mesObjectifs = ObjectifController::findAllByUtilisateur($userId); ?>
      <form id="virement-form" onsubmit="goToStep2(event)">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="virement">

        <!-- Source -->
        <div style="margin-bottom:1.2rem;">
          <label class="compact-label">DEPUIS LE COMPTE</label>
          <select name="id_compte_source" id="source-select" required onchange="onSourceChange()" class="compact-select">
            <?php foreach ($comptes as $c): if ($c->getStatut() === 'actif' && $c->getTypeCompte() !== 'epargne'): ?>
              <option value="<?= $c->getIdCompte() ?>" data-currency="<?= $c->getDevise() ?>" data-balance="<?= $c->getSolde() ?>">
                <?= ucfirst($c->getTypeCompte()) ?> • ···<?= substr($c->getIban(),-6) ?> (<?= number_format($c->getSolde(), 2) ?> <?= $c->getDevise() ?>)
              </option>
            <?php endif; endforeach; ?>
          </select>
        </div>

        <!-- Dest type toggle -->
        <?php $urlDest = $_GET['dest_type'] ?? 'interne'; ?>
        <div style="display:flex; background:var(--bg3); border-radius:12px; padding:4px; border:1px solid var(--border); margin-bottom:1.2rem;">
          <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="interne" <?= $urlDest==='interne'?'checked':'' ?> onclick="toggleDest('interne')" style="display:none;"><div class="tog-btn <?= $urlDest==='interne'?'active':'' ?>" id="btn-interne">Interne</div></label>
          <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="externe" <?= $urlDest==='externe'?'checked':'' ?> onclick="toggleDest('externe')" style="display:none;"><div class="tog-btn <?= $urlDest==='externe'?'active':'' ?>" id="btn-externe">Externe</div></label>
          <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="objectif" <?= $urlDest==='objectif'?'checked':'' ?> onclick="toggleDest('objectif')" style="display:none;"><div class="tog-btn <?= $urlDest==='objectif'?'active':'' ?>" id="btn-objectif">Objectif</div></label>
        </div>

        <!-- Internal dest -->
        <div id="dest-interne-box" style="margin-bottom:1.2rem;">
          <label class="compact-label">VERS LE COMPTE</label>
          <select name="id_compte_dest" id="dest-select" onchange="updateCalculations()" class="compact-select">
            <?php foreach ($comptes as $c): if ($c->getStatut() === 'actif'): ?>
              <option value="<?= $c->getIdCompte() ?>" data-currency="<?= $c->getDevise() ?>">
                <?= ucfirst($c->getTypeCompte()) ?> • ···<?= substr($c->getIban(),-6) ?>
              </option>
            <?php endif; endforeach; ?>
          </select>
        </div>

        <!-- External dest -->
        <div id="dest-externe-box" style="margin-bottom:1.2rem; display:none; gap:12px;">
          <div style="flex:1;"><label class="compact-label">BÉNÉFICIAIRE</label><input type="text" name="nom_beneficiaire" class="compact-input" placeholder="Nom"></div>
          <div style="flex:1;"><label class="compact-label">IBAN</label><input type="text" name="iban_dest" class="compact-input" placeholder="TN59..."></div>
        </div>

        <!-- Objectif dest -->
        <?php $urlObjId = (int)($_GET['id_objectif'] ?? 0); ?>
        <div id="dest-objectif-box" style="margin-bottom:1.2rem; display:none;">
          <label class="compact-label">VERS MON OBJECTIF</label>
          <select name="id_objectif_dest" id="obj-select" onchange="updateCalculations()" class="compact-select">
            <?php foreach ($mesObjectifs as $obj):
               $rem = $obj->getMontantObjectif() - $obj->getMontantActuel();
               if ($rem > 0): ?>
              <option value="<?= $obj->getIdObjectif() ?>" data-remaining="<?= $rem ?>" <?= ($urlObjId === (int)$obj->getIdObjectif()) ? 'selected' : '' ?>>
                <?= htmlspecialchars($obj->getTitre()) ?> (Reste: <?= number_format($rem,2) ?>)
              </option>
            <?php endif; endforeach; ?>
            <?php if (empty($mesObjectifs)): ?><option disabled>Aucun objectif en cours</option><?php endif; ?>
          </select>
        </div>

        <!-- Amount -->
        <div style="margin-bottom:1.5rem;">
          <label class="compact-label">MONTANT ET DEVISE DE SAISIE</label>
          <div style="display:flex; gap:8px;">
            <div style="position:relative; flex:1;">
              <input type="number" name="montant" id="montant-input" step="0.01" min="0.01" required placeholder="0.00" oninput="validateTransfer()" class="compact-input amount-field">
            </div>
            <select name="montant_devise" id="input-currency" onchange="updateCalculations()" style="width:85px; background:var(--bg3); border:1px solid var(--border); color:var(--text); border-radius:12px; font-weight:700; cursor:pointer; padding:0 8px;">
              <option value="TND">TND</option>
              <option value="EUR">EUR</option>
              <option value="USD">USD</option>
            </select>
          </div>
          <div id="summary-box" style="margin-top:1rem; padding:12px; background:rgba(37,99,235,0.05); border-radius:12px; border:1px dashed rgba(37,99,235,0.2); font-size:0.78rem; display:none;">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
              <span style="color:var(--muted2);">Sera débité de :</span>
              <span id="sum-debit" style="font-weight:700; color:var(--rose);">---</span>
            </div>
            <div id="sum-credit-row" style="display:flex; justify-content:space-between;">
              <span style="color:var(--muted2);">Sera crédité de :</span>
              <span id="sum-credit" style="font-weight:700; color:var(--green);">---</span>
            </div>
          </div>
          <div id="error-box" style="color:#ef4444; font-size:0.75rem; font-weight:600; margin-top:0.6rem; display:none;"></div>
        </div>

        <button type="submit" id="submit-btn" class="btn-primary" style="width:100%; padding:14px; border-radius:14px; font-weight:800; font-size:0.95rem; box-shadow:0 10px 20px rgba(37,99,235,0.2);">
          Suivant — Vérification →
        </button>
      </form>
    </div>

    <!-- ══════════════════════════════════════════════════════
         STEP 2 — FACE SCAN & OTP (CONDITIONAL)
    ════════════════════════════════════════════════════════ -->
    <div id="step-2" style="display:none; text-align:center;" class="step-fade-enter">
      
      <!-- Sub-Step: Face Scan -->
      <div id="face-scan-section">
        <div style="margin-bottom:1.5rem;">
          <div style="font-family:Syne,sans-serif; font-size:1.1rem; font-weight:700; margin-bottom:6px;">Sécurité Biométrique</div>
          <div style="font-size:0.8rem; color:var(--muted2); font-weight:500;">Positionnez votre visage et souriez pour valider.</div>
        </div>

        <div class="face-frame-container">
          <div class="face-circle-wrap" id="face-wrap">
            <video id="virement-video" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover; transform:scaleX(-1);"></video>
            <canvas id="virement-canvas" style="display:none;"></canvas>
            <div class="scan-line" id="virement-scan-line"></div>
            <div id="face-loading" style="position:absolute; inset:0; background:rgba(15,23,42,0.9); display:flex; align-items:center; justify-content:center; z-index:10;">
                <div class="loader-tiny"></div>
            </div>
          </div>
          <div class="scan-overlay"></div>
        </div>

        <div id="virement-face-status" style="font-size:0.85rem; color:var(--sec-color); font-weight:600; margin-bottom:1.5rem; min-height:20px;">Initialisation...</div>
      </div>

      <!-- Sub-Step: OTP (Hidden by default) -->
      <div id="otp-section" style="display:none;">
        <div class="whatsapp-icon" style="background:rgba(37,211,102,0.1); color:#25D366; width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12.011 2.25c-5.38 0-9.761 4.381-9.761 9.76 0 1.721.448 3.334 1.235 4.739l-1.311 4.791 4.901-1.286c1.365.744 2.923 1.171 4.581 1.171 5.379 0 9.76-4.381 9.76-9.76s-4.381-9.775-9.605-9.775zm5.541 13.882c-.225.631-1.321 1.171-1.815 1.231-.496.061-1.126.105-3.303-.78-2.776-1.141-4.546-3.961-4.681-4.141-.135-.18-1.111-1.471-1.111-2.806 0-1.336.721-1.996.976-2.251.255-.255.556-.315.736-.315.18 0 .36 0 .526.015.18.015.421-.061.661.511.24.586.826 2.011.899 2.161.076.15.121.33.015.525-.105.195-.165.315-.33.51-.165.195-.345.33-.495.51-.165.18-.33.375-.135.705.195.33.856 1.411 1.831 2.281 1.261 1.126 2.311 1.471 2.641 1.636.33.165.525.135.72-.09.195-.225.826-.961 1.051-1.291.225-.33.451-.27.765-.15.315.12 1.996.946 2.341 1.111.345.165.571.24.646.375.075.135.075.781-.15 1.412z"/></svg>
        </div>
        <div style="font-family:Syne,sans-serif; font-size:1.1rem; font-weight:700; margin-bottom:6px;">Code WhatsApp</div>
        <div style="font-size:0.8rem; color:var(--muted2); font-weight:500; margin-bottom:1.5rem;">Saisissez le code envoyé sur votre téléphone.</div>
        
        <div style="display:flex; gap:8px; justify-content:center; margin-bottom:1.5rem;">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,0)" onkeydown="otpBack(event,this,0)">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,1)" onkeydown="otpBack(event,this,1)">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,2)" onkeydown="otpBack(event,this,2)">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,3)" onkeydown="otpBack(event,this,3)">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,4)" onkeydown="otpBack(event,this,4)">
            <input class="otp-box" maxlength="1" inputmode="numeric" oninput="otpNext(this,5)" onkeydown="otpBack(event,this,5)">
        </div>

        <div id="virement-otp-error" style="color:#ef4444; font-size:0.75rem; font-weight:600; margin-bottom:1.5rem; min-height:1rem;"></div>
        
        <div style="margin-bottom: 1.5rem;">
            <div id="otp-timer-display" style="font-size: 0.85rem; color: var(--sec-color); font-weight: 700; margin-bottom: 0.5rem;">
                Expire dans : <span id="otp-timer-count">05:00</span>
            </div>
            <button id="resend-otp-btn" onclick="resendVirementOtp()" disabled style="background: none; border: none; color: var(--muted2); font-size: 0.8rem; font-weight: 600; cursor: not-allowed; text-decoration: underline; transition: 0.3s;">
                Renvoyer le code
            </button>
        </div>

        <button id="virement-otp-btn" class="btn-primary" onclick="verifyVirementOtp()" style="padding:12px 25px; border-radius:12px; margin:0 auto 1rem;">Vérifier le code</button>
      </div>

      <div style="display:flex; gap:10px; justify-content:center; margin-top:1rem;">
        <button onclick="goBackToStep1()" style="padding:10px 15px; border-radius:12px; font-size:0.8rem; font-weight:600; background:var(--bg3); border:1px solid var(--border); color:var(--text); cursor:pointer;">
          Retour
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         STEP 3 — FINAL CONFIRMATION
    ════════════════════════════════════════════════════════ -->
    <div id="step-3" style="display:none; text-align:center;" class="step-fade-enter">
      <div style="width:70px; height:70px; border-radius:22px; background:rgba(37,99,235,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; box-shadow:0 10px 20px rgba(37,99,235,0.1);">
        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div style="font-family:Syne,sans-serif; font-size:1.3rem; font-weight:800; margin-bottom:10px;">Confirmation Finale</div>
      <p style="font-size:0.9rem; color:var(--muted2); margin-bottom:2rem; line-height:1.6;">
        Toutes les vérifications de sécurité ont été validées. <br>
        <strong>Souhaitez-vous valider définitivement ce virement ?</strong>
      </p>

      <div style="display:flex; flex-direction:column; gap:16px; margin-top:1rem;">
        <button id="final-confirm-btn" class="btn-primary" onclick="executeVirement()" style="width:100%; padding:22px; border-radius:20px; font-weight:900; font-size:1.1rem; box-shadow:0 15px 35px var(--sec-glow); text-transform:uppercase; letter-spacing:0.05em;">
          OUI — Confirmer le virement
        </button>
        
        <button onclick="goBackToStep1()" style="background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--muted2); padding:14px; border-radius:16px; font-size:0.85rem; cursor:pointer; font-weight:700; transition:0.3s;">
          NON — Annuler l'opération
        </button>
      </div>
    </div>

  </div><!-- /relative -->
</div><!-- /container -->

<style>
/* ── Design Tokens ── */
:root {
  --sec-color: #2563eb;
  --sec-glow: rgba(37, 99, 235, 0.3);
  --success-glow: rgba(34, 197, 94, 0.2);
}

.virement-container {
  max-width: 520px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--bg2);
  border-radius: 28px;
  border: 1px solid var(--border);
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.compact-label {
  display: block;
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--muted2);
  margin-bottom: 0.5rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.compact-select, .compact-input {
  width: 100%;
  background: var(--bg3);
  border: 1px solid var(--border);
  color: var(--text);
  padding: 12px 16px;
  border-radius: 14px;
  font-family: inherit;
  outline: none;
  font-size: 0.92rem;
  transition: all 0.25s;
}

.compact-select:focus, .compact-input:focus {
  border-color: var(--sec-color);
  background: var(--bg2);
  box-shadow: 0 0 0 4px var(--sec-glow);
}

.tog-btn {
  text-align: center;
  padding: 10px;
  border-radius: 12px;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--muted2);
  transition: all 0.3s ease;
  cursor: pointer;
}

.tog-btn:hover { color: var(--text); }
.tog-btn.active {
  background: var(--sec-color);
  color: white;
  box-shadow: 0 4px 12px var(--sec-glow);
}

/* ── Face Scan UI ── */
.face-frame-container {
  position: relative;
  width: 200px;
  height: 200px;
  margin: 0 auto 1.5rem;
}

.face-circle-wrap {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid var(--border);
  position: relative;
  background: #000;
  transition: border-color 0.4s ease, box-shadow 0.4s ease;
}

.face-circle-wrap.active { border-color: var(--sec-color); }
.face-circle-wrap.verified { border-color: var(--green); box-shadow: 0 0 20px var(--success-glow); }

.scan-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(to right, transparent, var(--sec-color), transparent);
  box-shadow: 0 0 15px var(--sec-color);
  z-index: 5;
  display: none;
  animation: scanMove 2.5s ease-in-out infinite;
}

@keyframes scanMove {
  0%, 100% { top: 5%; opacity: 0.2; }
  50% { top: 90%; opacity: 1; }
}

.loader-tiny {
  width: 24px;
  height: 24px;
  border: 3px solid rgba(37,99,235,0.1);
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.scan-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, transparent 40%, rgba(15, 23, 42, 0.4) 100%);
  z-index: 2;
  pointer-events: none;
}

/* ── OTP UI ── */
.otp-box {
  width: 44px;
  height: 54px;
  text-align: center;
  font-size: 1.4rem;
  font-weight: 800;
  background: var(--bg3);
  border: 2px solid var(--border);
  border-radius: 12px;
  color: var(--text);
  outline: none;
  transition: all 0.2s;
}

.otp-box:focus {
  border-color: var(--sec-color);
  box-shadow: 0 5px 15px var(--sec-glow);
}

@keyframes stepIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
// ── Exchange rates ──────────────────────────────────────────
const exchangeRates = {
  'TND':{'EUR':0.33,'USD':0.32,'TND':1},
  'EUR':{'TND':3.00,'USD':1.08,'EUR':1},
  'USD':{'TND':3.12,'EUR':0.92,'USD':1}
};

// ── Step navigation ────────────────────────────────
function setStep(n) {
  [1,2,3].forEach(i => {
    const el = document.getElementById('step-'+i);
    if(el) el.style.display = (i===n) ? 'block' : 'none';
    const dot = document.getElementById('dot-'+i);
    if(dot) dot.style.background = (i<=n) ? '#2563eb' : 'var(--border)';
  });
}

// ── Face Recognition Logic ──────────────────────────────
let modelsLoaded = false;
let cameraReady = false;
let faceInterval = null;
let pendingFormData = null;

async function initCamera() {
    const video = document.getElementById('virement-video');
    const status = document.getElementById('virement-face-status');
    const faceWrap = document.getElementById('face-wrap');
    const loader = document.getElementById('face-loading');
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        status.innerHTML = '<span style="color:var(--rose)">HTTPS requis pour la caméra.</span>';
        return;
    }
    
    status.textContent = 'Initialisation caméra...';
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 400, height: 400 } });
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            cameraReady = true;
            if(loader) loader.style.display = 'none';
            checkAndStartScan();
        };
    } catch (e) {
        console.error("Camera failed:", e);
        status.textContent = 'Accès caméra refusé ou introuvable.';
    }
}

async function checkAndStartScan() {
    if (cameraReady) {
        startFaceScan();
    }
}

async function startFaceScan() {
    if (faceInterval) return;
    const video = document.getElementById('virement-video');
    const canvas = document.getElementById('virement-canvas');
    const status = document.getElementById('virement-face-status');
    const scanLine = document.getElementById('virement-scan-line');
    const faceWrap = document.getElementById('face-wrap');
    
    faceInterval = true; // Flag to prevent multiple loops
    scanLine.style.display = 'block';
    faceWrap.classList.add('active');
    status.textContent = 'Analyse en cours...';

    async function performScan() {
        if (!faceInterval || !cameraReady) return;

        // Capture frame
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = canvas.toDataURL('image/jpeg', 0.8);

        // Calculate amount in TND to decide on OTP
        const amount = parseFloat(document.getElementById('montant-input').value) || 0;
        const inputCur = document.getElementById('input-currency').value;
        const amountInTnd = amount * (exchangeRates[inputCur]['TND'] || 1);

        try {
            status.innerHTML = '<span class="scanning-text">Analyse du visage...</span>';
            const res = await fetch('<?= APP_URL ?>/controller/BiometricOtpController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'verify_face_image',
                    image: imageData,
                    amountTnd: amountInTnd
                })
            });
            
            const text = await res.text();
            let data;
            try { 
                data = JSON.parse(text); 
            } catch(e) {
                console.error("Malformed JSON:", text);
                status.innerHTML = '<span style="color:var(--rose)">Erreur serveur (JSON)</span>';
                setTimeout(performScan, 4000);
                return;
            }

            if (data.success) {
                faceInterval = false;
                status.innerHTML = `<span style="color:var(--green)">✅ ${data.message || 'Identité confirmée !'}</span>`;
                if(video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
                setTimeout(() => handleFaceSuccess(), 1000);
            } else {
                status.innerHTML = `<span style="color:var(--rose)">${data.message || 'Visage non reconnu'}</span>`;
                // Wait 3 seconds before next attempt to avoid API spam
                setTimeout(performScan, 3000);
            }
        } catch (e) {
            console.error("Verification failed:", e);
            status.textContent = 'Erreur technique verification.';
            setTimeout(performScan, 5000);
        }
    }

    performScan();
}

function handleFaceSuccess() {
    const status = document.getElementById('virement-face-status');
    const scanLine = document.getElementById('virement-scan-line');
    const faceWrap = document.getElementById('face-wrap');
    
    status.textContent = '✅ Visage validé !';
    scanLine.style.display = 'none';
    faceWrap.classList.remove('active');
    faceWrap.classList.add('verified');

    // Logic: Skip OTP if < 100 TND
    const amountInput = document.getElementById('montant-input');
    const amount = parseFloat(amountInput.value) || 0;
    const inputCur = document.getElementById('input-currency').value;
    const amountInTnd = amount * (exchangeRates[inputCur]['TND'] || 1);

    const transitionDelay = 600; // Smoother delay

    if (amountInTnd < 100) {
        status.textContent = 'Transfert autorisé.';
        setTimeout(() => setStep(3), transitionDelay);
    } else {
        setTimeout(() => {
            document.getElementById('face-scan-section').style.display = 'none';
            document.getElementById('otp-section').style.display = 'block';
            document.getElementById('header-subtitle').textContent = 'Vérification WhatsApp requise.';
            sendVirementOtp();
        }, transitionDelay);
    }
}

async function sendVirementOtp() {
    await fetch('<?= APP_URL ?>/controller/BiometricOtpController.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action: 'send_otp' })
    });
    document.querySelectorAll('.otp-box')[0].focus();
    startOtpTimer(300); // 5 minutes
}

let otpTimer = null;
function startOtpTimer(seconds) {
    if (otpTimer) clearInterval(otpTimer);
    const display = document.getElementById('otp-timer-count');
    const resendBtn = document.getElementById('resend-otp-btn');
    
    resendBtn.disabled = true;
    resendBtn.style.color = 'var(--muted2)';
    resendBtn.style.cursor = 'not-allowed';

    let timeLeft = seconds;
    otpTimer = setInterval(() => {
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        display.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(otpTimer);
            display.textContent = "00:00";
            resendBtn.disabled = false;
            resendBtn.style.color = 'var(--sec-color)';
            resendBtn.style.cursor = 'pointer';
        }
        timeLeft--;
    }, 1000);
}

async function resendVirementOtp() {
    const resendBtn = document.getElementById('resend-otp-btn');
    if (resendBtn.disabled) return;
    
    resendBtn.textContent = 'Envoi...';
    await sendVirementOtp();
    resendBtn.textContent = 'Renvoyer le code';
}

async function verifyVirementOtp() {
    const boxes = document.querySelectorAll('.otp-box');
    const code = Array.from(boxes).map(b => b.value).join('');
    if (code.length !== 6) return;

    const btn = document.getElementById('virement-otp-btn');
    const err = document.getElementById('virement-otp-error');
    btn.disabled = true;
    btn.textContent = 'Vérification...';

    const res = await fetch('<?= APP_URL ?>/controller/BiometricOtpController.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action: 'verify_otp', code })
    });
    const data = await res.json();

    if (data.success) {
        if (otpTimer) clearInterval(otpTimer);
        setStep(3);
    } else {
        err.textContent = 'Code incorrect. Réessayez.';
        btn.disabled = false;
        btn.textContent = 'Vérifier le code';
        boxes.forEach(b => b.value = '');
        boxes[0].focus();
    }
}

function otpNext(input, idx) {
    input.value = input.value.replace(/[^0-9]/g,'');
    if (input.value && idx < 5) document.querySelectorAll('.otp-box')[idx+1].focus();
    if (idx === 5 && input.value) verifyVirementOtp();
}

function otpBack(e, input, idx) {
    if (e.key === 'Backspace' && !input.value && idx > 0) document.querySelectorAll('.otp-box')[idx-1].focus();
}

function goToStep2(e) {
  e.preventDefault();
  const form = document.getElementById('virement-form');
  pendingFormData = new FormData(form);
  setStep(2);
  initCamera();
}

function goBackToStep1() {
  faceInterval = false; // Stop recursive loop
  if (otpTimer) clearInterval(otpTimer);
  const video = document.getElementById('virement-video');
  if(video && video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
  cameraReady = false;
  setStep(1);
  document.getElementById('face-scan-section').style.display = 'block';
  document.getElementById('otp-section').style.display = 'none';
  const loader = document.getElementById('face-loading');
  if(loader) loader.style.display = 'flex';
  document.getElementById('header-subtitle').textContent = 'Vérification biométrique requise.';
}

// ── Virement form logic ────────────────────────────────────
function onSourceChange() { filterDestAccounts(); updateCalculations(); }

function filterDestAccounts() {
  const src = document.getElementById('source-select').value;
  const dst = document.getElementById('dest-select');
  if (!dst) return;
  let first = -1;
  for (let i=0;i<dst.options.length;i++) {
    dst.options[i].style.display = dst.options[i].value===src ? 'none' : '';
    if (first===-1 && dst.options[i].value!==src) first=i;
  }
  if (dst.options[dst.selectedIndex]?.style.display==='none' && first!==-1) dst.selectedIndex=first;
}

function toggleDest(type) {
  const inter = document.getElementById('dest-interne-box');
  const exter = document.getElementById('dest-externe-box');
  const obj = document.getElementById('dest-objectif-box');
  if(inter) inter.style.display = type==='interne'?'block':'none';
  if(exter) exter.style.display = type==='externe'?'flex':'none';
  if(obj) obj.style.display = type==='objectif'?'block':'none';
  ['interne','externe','objectif'].forEach(t => {
      const btn = document.getElementById('btn-'+t);
      if(btn) btn.classList.toggle('active',t===type);
  });
  updateCalculations();
}

function updateCalculations() {
  const amountInput = document.getElementById('montant-input');
  const amount = parseFloat(amountInput.value)||0;
  const inputCur = document.getElementById('input-currency').value;
  const src = document.getElementById('source-select');
  const dst = document.getElementById('dest-select');
  const srcCur = src.options[src.selectedIndex]?.getAttribute('data-currency');
  const destType = document.querySelector('input[name="dest_type"]:checked').value;
  const box = document.getElementById('summary-box');
  if (amount>0) {
    box.style.display='block';
    const dv = amount*(exchangeRates[inputCur][srcCur]||1);
    document.getElementById('sum-debit').textContent=`- ${dv.toFixed(2)} ${srcCur}`;
    if (destType==='interne') {
      const dstCur = dst.options[dst.selectedIndex]?.getAttribute('data-currency');
      document.getElementById('sum-credit-row').style.display='flex';
      document.getElementById('sum-credit').textContent=`+ ${(amount*(exchangeRates[inputCur][dstCur]||1)).toFixed(2)} ${dstCur}`;
    } else if (destType==='objectif') {
      document.getElementById('sum-credit-row').style.display='flex';
      document.getElementById('sum-credit').textContent=`+ ${amount.toFixed(2)} cible`;
    } else {
      document.getElementById('sum-credit-row').style.display='none';
    }
  } else { box.style.display='none'; }
  validateTransfer();
}

function validateTransfer() {
  const src = document.getElementById('source-select');
  const amtInput = document.getElementById('montant-input');
  const balance = parseFloat(src.options[src.selectedIndex]?.getAttribute('data-balance'))||0;
  const srcCur = src.options[src.selectedIndex]?.getAttribute('data-currency');
  const inputCur = document.getElementById('input-currency').value;
  const amount = parseFloat(amtInput.value)||0;
  const destType = document.querySelector('input[name="dest_type"]:checked').value;
  const debitVal = amount*(exchangeRates[inputCur][srcCur]||1);
  const errorBox = document.getElementById('error-box');
  const btn = document.getElementById('submit-btn');
  let err='';
  if (amount>0 && debitVal>balance) err=`Solde insuffisant (Nécessite ${debitVal.toFixed(2)} ${srcCur})`;
  if (!err && destType==='objectif' && amount>0) {
    const os = document.getElementById('obj-select');
    const rem = parseFloat(os.options[os.selectedIndex]?.getAttribute('data-remaining'))||0;
    if (amount>rem) err=`Dépasse le besoin de l'objectif (Reste: ${rem.toFixed(2)})`;
  }
  if (err) { errorBox.textContent=err; errorBox.style.display='block'; btn.disabled=true; }
  else { errorBox.style.display='none'; btn.disabled=(amount<=0); }
}

function executeVirement() {
  try {
    const btn = document.getElementById('final-confirm-btn');
    if (!pendingFormData) {
        alert("Session expirée.");
        window.location.reload();
        return;
    }
    btn.disabled = true;
    btn.textContent = 'Traitement...';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= APP_URL ?>/controller/CompteController.php';
    for (const [k,v] of pendingFormData.entries()) {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = k; inp.value = v;
      form.appendChild(inp);
    }
    document.body.appendChild(form);
    form.submit();
  } catch (e) {
    alert("Erreur validation.");
    document.getElementById('final-confirm-btn').disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const type = urlParams.get('dest_type') || 'interne';
  toggleDest(type);
  onSourceChange();
});
</script>
