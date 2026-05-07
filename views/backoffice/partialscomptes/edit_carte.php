  <!-- ══ EDIT CARTE PAGE ════════════════════════════════ -->
  <div class="section-head" style="margin-bottom:.5rem">
    <div class="page-title">Modifier la carte</div>
    <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carteEdit->getIdCompte() ?>" class="btn-ghost">← Retour</a>
  </div>
  <div style="display:flex; justify-content:center; align-items:flex-start; padding-top:1rem;">
    <div class="edit-card-panel" style="background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:2rem; width:100%; max-width:650px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
      <div style="display:flex; align-items:center; gap:1rem; margin-bottom:2rem; padding-bottom:1rem; border-bottom:1px solid var(--border2)">
        <div style="width:48px; height:48px; background:var(--blue-light); color:var(--blue); border-radius:12px; display:flex; align-items:center; justify-content:center;">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        </div>
        <div>
          <h2 style="margin:0; font-size:1.25rem; font-weight:700; font-family:var(--fs);">Paramètres de la carte</h2>
          <p style="margin:0; font-size:.82rem; color:var(--muted)">Modification des plafonds, du statut et du style visuel.</p>
        </div>
      </div>

      <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id_carte" value="<?= $carteEdit->getIdCarte() ?>">
        <input type="hidden" name="redirect_id_compte" value="<?= $carteEdit->getIdCompte() ?>">
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem">
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Numéro de carte</label>
            <input type="text" value="<?= htmlspecialchars($carteEdit->getNumeroCarte()) ?>" readonly style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:.75rem; width:100%; color:var(--muted); font-family:var(--fm); cursor:not-allowed;">
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Titulaire</label>
            <input type="text" name="titulaire_nom" value="<?= htmlspecialchars($formData['titulaire_nom'] ?? $carteEdit->getTitulaireNom()) ?>" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-weight:500;">
            <?php if (isset($formErrors['titulaire_nom'])): ?>
              <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['titulaire_nom']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Type de carte</label>
            <select name="type_carte" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-weight:500;">
              <?php foreach (['debit'=>'Débit','credit'=>'Crédit','prepayee'=>'Prépayée'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $carteEdit->getTypeCarte()===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Réseau</label>
            <select name="reseau" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-weight:500;">
              <?php foreach (['visa'=>'Visa','mastercard'=>'Mastercard','amex'=>'Amex'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $carteEdit->getReseau()===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Statut de sécurité</label>
            <select name="statut" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-weight:600; <?= $carteEdit->getStatut()==='bloquee'?'color:var(--rose)':'' ?>">
              <?php foreach (['active'=>'Active','inactive'=>'Inactive','bloquee'=>'Bloquée','expiree'=>'Expirée'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $carteEdit->getStatut()===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Style visuel</label>
            <select id="style_select" name="style" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-weight:600; transition:color 0.2s;">
              <?php foreach (['standard'=>'Standard','gold'=>'Gold','platinum'=>'Platinum','titanium'=>'Titanium'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $carteEdit->getStyle()===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Date d'expiration</label>
            <input type="month" name="date_expiration" value="<?= htmlspecialchars(substr($formData['date_expiration'] ?? $carteEdit->getDateExpiration(), 0, 7)) ?>" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%; font-family:var(--fm);">
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Motif blocage</label>
            <input type="text" name="motif_blocage" value="<?= htmlspecialchars($carteEdit->getMotifBlocage()??'') ?>" placeholder="Aucun" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; width:100%;">
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Plafond Paiement / Jour</label>
            <div style="position:relative">
              <input type="number" id="plafond_paiement_input" name="plafond_paiement_jour" value="<?= (float)($formData['plafond_paiement_jour'] ?? $carteEdit->getPlafondPaiementJour()) ?>" oninput="updateAdminCardStyle(this.value)" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; padding-right:3rem; width:100%; font-weight:600;">
              <span style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); font-size:.65rem; color:var(--muted2); font-weight:600;">TND</span>
            </div>
            <?php if (isset($formErrors['plafond_paiement_jour'])): ?>
              <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_paiement_jour']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-field">
            <label style="font-weight:600; font-size:.78rem; color:var(--muted2); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:.5rem;">Plafond Retrait / Jour</label>
            <div style="position:relative">
              <input type="number" name="plafond_retrait_jour" value="<?= (float)($formData['plafond_retrait_jour'] ?? $carteEdit->getPlafondRetraitJour()) ?>" style="background:var(--surface); border:1px solid var(--border2); border-radius:8px; padding:.75rem; padding-right:3rem; width:100%; font-weight:600;">
              <span style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); font-size:.65rem; color:var(--muted2); font-weight:600;">TND</span>
            </div>
            <?php if (isset($formErrors['plafond_retrait_jour'])): ?>
              <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_retrait_jour']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <script>
        function updateAdminCardStyle(val) {
          const v = parseFloat(val) || 0;
          let style = 'standard';
          if(v >= 1500) style = 'titanium';
          else if(v >= 1000) style = 'platinum';
          else if(v >= 500) style = 'gold';
          
          const sel = document.getElementById('style_select');
          if(sel) {
            sel.value = style;
            const colors = { standard: '#2563EB', gold: '#D97706', platinum: '#4B5563', titanium: '#111827' };
            sel.style.color = colors[style] || 'inherit';
          }
        }
        document.addEventListener('DOMContentLoaded', ()=>updateAdminCardStyle(document.getElementById('plafond_paiement_input').value));
        </script>

        <div style="margin-top:2.5rem; padding-top:1.5rem; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem;">
          <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carteEdit->getIdCompte() ?>" class="btn-cancel" style="padding:.75rem 1.5rem; border-radius:10px; font-weight:600; text-decoration:none; color:var(--muted2); background:var(--surface2); display:flex; align-items:center; gap:.5rem;">
            Annuler
          </a>
          <button type="submit" class="btn-save" style="padding:.75rem 2rem; border-radius:10px; font-weight:700; border:none; color:white; background:var(--blue); cursor:pointer; box-shadow: 0 4px 12px rgba(37,99,235,0.25); display:flex; align-items:center; gap:.5rem;">
            Sauvegarder les modifications
          </button>
        </div>
      </form>
    </div>
  </div>

