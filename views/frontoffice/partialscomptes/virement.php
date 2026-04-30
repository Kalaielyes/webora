<div class="virement-container" style="max-width:550px; margin:0 auto; padding:1.5rem; background:var(--bg2); border-radius:24px; border:1px solid var(--border); box-shadow:0 15px 40px rgba(0,0,0,0.1); position:relative; overflow:hidden;">
  <!-- Decor -->
  <div style="position:absolute; top:-80px; right:-80px; width:200px; height:200px; background:radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%); z-index:0; pointer-events:none;"></div>
  
  <div style="position:relative; z-index:1;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.5rem;">
      <div style="width:40px; height:40px; border-radius:14px; background:#2563eb; color:white; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 12px rgba(37,99,235,0.2);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      </div>
      <div>
        <h2 style="margin:0; font-size:1.2rem; font-family:Syne, sans-serif;">Transfert</h2>
        <p style="margin:0; font-size:0.75rem; color:var(--text3); font-weight:500;">Mouvements de fonds sécurisés.</p>
      </div>
    </div>

    <?php if (!empty($_GET['ok'])): ?>
      <div style="background:rgba(34,197,94,0.1); color:#22c55e; padding:12px 16px; border-radius:12px; font-size:0.85rem; margin-bottom:1.2rem; display:flex; align-items:center; gap:10px; border:1px solid rgba(34,197,94,0.2);">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <b>Succès !</b> Transaction traitée.
      </div>
    <?php endif; ?>

    <?php 
    $mesObjectifs = ObjectifController::findAllByUtilisateur($userId); 
    ?>
    <form action="<?= APP_URL ?>/controllers/CompteController.php" method="POST" id="virement-form">
      <?php Security::csrfInput(); ?>
      <input type="hidden" name="action" value="virement">
      
      <!-- Source Side -->
      <div style="margin-bottom:1.2rem;">
        <label class="compact-label">DEPUIS LE COMPTE</label>
        <select name="id_compte_source" id="source-select" required onchange="onSourceChange()" class="compact-select">
          <?php foreach ($comptes as $c): if ($c->getStatut() === 'actif' && $c->getTypeCompte() !== 'epargne'): ?>
            <option value="<?= $c->getIdCompte() ?>" data-currency="<?= $c->getDevise() ?>" data-balance="<?= $c->getSolde() ?>" <?= ($selected && $selected->getIdCompte()===$c->getIdCompte()) ? 'selected' : '' ?>>
              <?= ucfirst($c->getTypeCompte()) ?> • ···<?= substr($c->getIban(),-6) ?> (<span class="sensitive-data"><?= number_format($c->getSolde(), 2) ?></span> <?= $c->getDevise() ?>)
            </option>
          <?php endif; endforeach; ?>
        </select>
      </div>

      <!-- Destination Type -->
      <div style="display:flex; background:var(--bg3); border-radius:12px; padding:4px; border:1px solid var(--border); margin-bottom:1.2rem;">
        <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="interne" checked onclick="toggleDest('interne')" style="display:none;"><div class="tog-btn active" id="btn-interne">Interne</div></label>
        <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="externe" onclick="toggleDest('externe')" style="display:none;"><div class="tog-btn" id="btn-externe">Externe</div></label>
        <label style="flex:1; cursor:pointer;"><input type="radio" name="dest_type" value="objectif" onclick="toggleDest('objectif')" style="display:none;"><div class="tog-btn" id="btn-objectif">Objectif</div></label>
      </div>

      <!-- INTERNAL DESTINATION -->
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

      <!-- EXTERNAL DESTINATION -->
      <div id="dest-externe-box" style="margin-bottom:1.2rem; display:none; gap:12px;">
        <div style="flex:1;"><label class="compact-label">BÉNÉFICIAIRE</label><input type="text" name="nom_beneficiaire" class="compact-input" placeholder="Nom"></div>
        <div style="flex:1;"><label class="compact-label">IBAN</label><input type="text" name="iban_dest" class="compact-input" placeholder="TN59..."></div>
      </div>

      <!-- OBJECTIVE DESTINATION -->
      <div id="dest-objectif-box" style="margin-bottom:1.2rem; display:none;">
        <label class="compact-label">VERS MON OBJECTIF</label>
        <select name="id_objectif_dest" id="obj-select" onchange="updateCalculations()" class="compact-select">
          <?php foreach ($mesObjectifs as $obj): 
             $rem = $obj->getMontantObjectif() - $obj->getMontantActuel();
             if ($rem > 0): 
          ?>
            <option value="<?= $obj->getIdObjectif() ?>" data-remaining="<?= $rem ?>">
              <?= htmlspecialchars($obj->getTitre()) ?> (Reste: <span class="sensitive-data"><?= number_format($rem,2) ?></span>)
            </option>
          <?php endif; endforeach; ?>
          <?php if (empty($mesObjectifs)): ?>
            <option disabled>Aucun objectif en cours</option>
          <?php endif; ?>
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
        
        <!-- Summary / Conversion Hint -->
        <div id="summary-box" style="margin-top:1rem; padding:12px; background:rgba(37,99,235,0.05); border-radius:12px; border:1px dashed rgba(37,99,235,0.2); font-size:0.78rem; display:none;">
           <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
              <span style="color:var(--text3);">Sera débité de :</span>
              <span id="sum-debit" class="sensitive-data" style="font-weight:700; color:var(--rose);">---</span>
           </div>
           <div id="sum-credit-row" style="display:flex; justify-content:space-between;">
              <span style="color:var(--text3);">Sera crédité de :</span>
              <span id="sum-credit" class="sensitive-data" style="font-weight:700; color:var(--green);">---</span>
           </div>
        </div>

        <div id="error-box" style="color:#ef4444; font-size:0.75rem; font-weight:600; margin-top:0.6rem; display:none;"></div>
      </div>

      <button type="submit" id="submit-btn" class="btn-primary" style="width:100%; padding:14px; border-radius:14px; font-weight:800; font-size:0.95rem; box-shadow:0 10px 20px rgba(37,99,235,0.2);">
        Confirmer le transfert
      </button>
    </form>
  </div>
</div>

<style>
.compact-label { display:block; font-size:0.65rem; font-weight:800; color:var(--muted2); margin-bottom:0.4rem; letter-spacing:0.05em; }
.compact-select, .compact-input { width:100%; background:var(--bg3); border:1px solid var(--border); color:var(--text); padding:10px 12px; border-radius:12px; font-family:inherit; outline:none; font-size:0.88rem; transition:0.2s; }
.compact-select:focus, .compact-input:focus { border-color:#2563eb; background:var(--bg2); }
.tog-btn { text-align:center; padding:8px; border-radius:10px; font-size:0.8rem; font-weight:700; color:var(--muted2); transition:0.3s; }
.tog-btn.active { background:#2563eb; color:white; }
.amount-field { font-family:var(--fm); font-size:1.2rem !important; font-weight:700 !important; }

/* Remove arrows from number input */
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

.virement-container { backdrop-filter: blur(10px); background: rgba(var(--bg2-rgb), 0.7); }
</style>

<script>
const exchangeRates = {
    'TND': {'EUR': 0.33, 'USD': 0.32, 'TND': 1},
    'EUR': {'TND': 3.00, 'USD': 1.08, 'EUR': 1},
    'USD': {'TND': 3.12, 'EUR': 0.92, 'USD': 1}
};

function onSourceChange() {
    filterDestAccounts();
    updateCalculations();
}

function filterDestAccounts() {
    const src = document.getElementById('source-select').value;
    const dst = document.getElementById('dest-select');
    const options = dst.options;
    
    let firstVisibleIndex = -1;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === src) {
            options[i].style.display = 'none';
        } else {
            options[i].style.display = 'block';
            if (firstVisibleIndex === -1) firstVisibleIndex = i;
        }
    }
    
    // If current selection is hidden, select the first visible
    if (options[dst.selectedIndex].style.display === 'none' && firstVisibleIndex !== -1) {
        dst.selectedIndex = firstVisibleIndex;
    }
}

function toggleDest(type) {
  const isInterne = (type === 'interne');
  const isExterne = (type === 'externe');
  const isObj = (type === 'objectif');
  
  document.getElementById('dest-interne-box').style.display = isInterne ? 'block' : 'none';
  document.getElementById('dest-externe-box').style.display = isExterne ? 'flex' : 'none';
  document.getElementById('dest-objectif-box').style.display = isObj ? 'block' : 'none';
  
  document.getElementById('btn-interne').classList.toggle('active', isInterne);
  document.getElementById('btn-externe').classList.toggle('active', isExterne);
  document.getElementById('btn-objectif').classList.toggle('active', isObj);
  
  updateCalculations();
}

function updateCalculations() {
  const amount = parseFloat(document.getElementById('montant-input').value) || 0;
  const inputCur = document.getElementById('input-currency').value;
  const src = document.getElementById('source-select');
  const dst = document.getElementById('dest-select');
  const srcCur = src.options[src.selectedIndex].getAttribute('data-currency');
  const destType = document.querySelector('input[name="dest_type"]:checked').value;
  
  const summaryBox = document.getElementById('summary-box');
  
  if (amount > 0) {
    summaryBox.style.display = 'block';
    
    // Calculate debit
    const debitRate = exchangeRates[inputCur][srcCur];
    const debitVal = amount * debitRate;
    document.getElementById('sum-debit').textContent = `- ${debitVal.toFixed(2)} ${srcCur}`;
    
    if (destType === 'interne') {
        const dstCur = dst.options[dst.selectedIndex].getAttribute('data-currency');
        const creditRate = exchangeRates[inputCur][dstCur];
        const creditVal = amount * creditRate;
        document.getElementById('sum-credit-row').style.display = 'flex';
        document.getElementById('sum-credit').textContent = `+ ${creditVal.toFixed(2)} ${dstCur}`;
    } else if (destType === 'objectif') {
        // Assume input currency matches Goal currency for simplicity in UI summary
        document.getElementById('sum-credit-row').style.display = 'flex';
        document.getElementById('sum-credit').textContent = `+ ${amount.toFixed(2)} Target`;
    } else {
        document.getElementById('sum-credit-row').style.display = 'none';
    }
  } else {
    summaryBox.style.display = 'none';
  }
  
  validateTransfer();
}

function validateTransfer() {
  const src = document.getElementById('source-select');
  const amountInput = document.getElementById('montant-input');
  const balance = parseFloat(src.options[src.selectedIndex].getAttribute('data-balance'));
  const srcCur = src.options[src.selectedIndex].getAttribute('data-currency');
  const inputCur = document.getElementById('input-currency').value;
  const amount = parseFloat(amountInput.value) || 0;
  const destType = document.querySelector('input[name="dest_type"]:checked').value;
  
  const debitRate = exchangeRates[inputCur][srcCur];
  const debitVal = amount * debitRate;

  const errorBox = document.getElementById('error-box');
  const submitBtn = document.getElementById('submit-btn');
  
  let error = '';
  if (amount > 0 && debitVal > balance) {
    error = `Solde insuffisant (Nécessite ${debitVal.toFixed(2)} ${srcCur})`;
  }

  if (!error && destType === 'objectif' && amount > 0) {
      const objSel = document.getElementById('obj-select');
      if (objSel.selectedIndex >= 0) {
          const remaining = parseFloat(objSel.options[objSel.selectedIndex].getAttribute('data-remaining')) || 0;
          if (amount > remaining) {
              error = `Le montant dépasse le besoin de l'objectif (Reste: ${remaining.toFixed(2)})`;
          }
      } else {
          error = "Aucun objectif sélectionné";
      }
  }

  if (error) {
    errorBox.textContent = error;
    errorBox.style.display = 'block';
    submitBtn.disabled = true;
  } else {
    errorBox.style.display = 'none';
    submitBtn.disabled = (amount <= 0);
  }
}

// Initial Run
setTimeout(onSourceChange, 100);
</script>
