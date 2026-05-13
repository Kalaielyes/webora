  <div class="section-head" style="margin-bottom:.3rem">
    <div class="section-title">Demander une nouvelle carte</div>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php<?= $selected?'?id_compte='.$selected->getIdCompte():'' ?>" class="btn-ghost">✕ Annuler</a>
  </div>
  <div class="demand-card">
    <div class="info-banner">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
      Votre demande sera traitée par un agent bancaire sous 48h ouvrées.
    </div>
    <form id="form_carte" method="POST" action="<?= APP_URL ?>/controllers/CarteController.php">
      <?php Security::csrfInput(); ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="cvv" value="<?= rand(100,999) ?>">
      <?php if ($selected): ?><input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>"><?php endif; ?>
      <div class="form-grid">
        <div class="form-field">
          <label>Compte lié *</label>
          <select id="id_compte" name="id_compte">
            <option value="">— Sélectionner un compte —</option>
            <?php foreach ($comptes as $c):
              $isActif = $c->getStatut() === 'actif';
              $isEpargne = $c->getTypeCompte() === 'epargne';
            ?>
            <option value="<?= $c->getIdCompte() ?>"
              <?= ($selected && $selected->getIdCompte()===$c->getIdCompte()) ? 'selected' : '' ?>
              <?= (!$isActif || $isEpargne) ? 'disabled style="color:#6B7280"' : '' ?>>
              <?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — IBAN ···<?= substr($c->getIban(),-6) ?>
              <?= !$isActif ? ' ('.$c->getStatut().')' : ($isEpargne ? ' (Indisponible pour carte)' : '') ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:.68rem;color:var(--muted2);margin-top:.4rem">
            ℹ️ Les comptes épargne ne sont pas éligibles aux cartes bancaires.
          </div>
          <?php if (empty($comptes)): ?>
          <div style="font-size:.7rem;color:var(--rose);margin-top:.3rem">⚠ Aucun compte disponible. <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" style="color:var(--blue)">Ouvrir un compte d'abord.</a></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Type de carte</label>
          <select name="type_carte">
            <option value="debit">Débit</option>
            <option value="credit">Crédit</option>
            <option value="prepayee">Prépayée</option>
          </select>
        </div>
        <div class="form-field">
          <label>Réseau</label>
          <select name="reseau">
            <option value="visa">Visa</option>
            <option value="mastercard">Mastercard</option>
          </select>
        </div>
        <div class="form-field">
          <label>Nom du titulaire</label>
          <input type="text" id="titulaire_nom" name="titulaire_nom" value="<?= htmlspecialchars($formData['titulaire_nom'] ?? strtoupper(($user['prenom']??'').' '.($user['nom']??''))) ?>">
          <?php if (isset($formErrors['titulaire_nom'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['titulaire_nom']) ?></div>
          <?php else: ?>
          <div id="err_titulaire_nom" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Plafond paiement / jour (TND)</label>
          <input type="text" id="plafond_input" name="plafond_paiement_jour" value="<?= htmlspecialchars($formData['plafond_paiement_jour'] ?? '') ?>" placeholder="100" oninput="updateStyleHint(this.value)">
          <?php if (isset($formErrors['plafond_paiement_jour'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_paiement_jour']) ?></div>
          <?php else: ?>
          <div id="err_plafond_paiement" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
          <div id="style-hint" style="font-size:.68rem;margin-top:.3rem;color:var(--muted2)">
            → Carte <strong id="style-hint-name">Gold</strong>
            <span style="font-size:.6rem;color:var(--muted)">(< 500 = Classic · ≥ 500 = Gold · ≥ 1000 = Platinum · ≥ 1500 = Titanium)</span>
          </div>
        </div>
        <div class="form-field">
          <label>Plafond retrait / jour (TND)</label>
          <input type="text" id="plafond_retrait" name="plafond_retrait_jour" value="<?= htmlspecialchars($formData['plafond_retrait_jour'] ?? '') ?>" placeholder="100">
          <?php if (isset($formErrors['plafond_retrait_jour'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_retrait_jour']) ?></div>
          <?php else: ?>
          <div id="err_plafond_retrait" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Date d'expiration</label>
          <input type="text" id="date_expiration" name="date_expiration" value="<?= date('Y-m',strtotime('+4 years')) ?>" readonly style="background-color: var(--surface2); color: var(--muted); cursor: not-allowed;">
        </div>
      </div>
      <div class="form-actions">
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php<?= $selected?'?id_compte='.$selected->getIdCompte():'' ?>" class="btn-ghost">Annuler</a>
        <button type="submit" class="btn-primary">Envoyer la demande</button>
      </div>
    </form>
  </div>
