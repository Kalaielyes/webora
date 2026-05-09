  <!-- ══ FORM: NEW COMPTE ════════════════════════════════ -->
  <div class="section-head" style="margin-bottom:.3rem">
    <div class="section-title">Demander un nouveau compte</div>
    <a href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php" class="btn-ghost">✕ Annuler</a>
  </div>
  <div class="demand-card">
    <div class="info-banner">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
      Votre demande sera examinée par un agent bancaire. Vous serez notifié une fois validée.
    </div>
    <form id="form_compte" method="POST" action="<?= APP_URL ?>/controller/CompteController.php">
      <?php Security::csrfInput(); ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id_utilisateur" value="<?= $userId ?>">
      <div class="form-grid">
        <div class="form-field">
          <label>Type de compte</label>
          <select id="type_compte" name="type_compte">
            <option value="courant">Compte courant</option>
            <option value="epargne">Compte épargne</option>
            <option value="devise">Compte devise</option>
            <option value="professionnel">Compte professionnel</option>
          </select>
        </div>
        <div class="form-field">
          <label>Devise</label>
          <select name="devise">
            <option value="TND">TND — Dinar tunisien</option>
            <option value="EUR">EUR — Euro</option>
            <option value="USD">USD — Dollar américain</option>
          </select>
        </div>
        <div class="form-field">
          <label>Plafond de virement souhaité (TND)</label>
          <input type="text" id="plafond_virement" name="plafond_virement" value="<?= htmlspecialchars($formData['plafond_virement'] ?? '') ?>" placeholder="1000">
          <?php if (isset($formErrors['plafond_virement'])): ?>
          <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_virement']) ?></div>
          <?php else: ?>
          <div id="err_plafond_virement" style="color:var(--rose);font-size:.7rem;margin-top:.2rem;display:none;"></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <a href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php" class="btn-ghost">Annuler</a>
        <button type="submit" class="btn-primary">Envoyer la demande</button>
      </div>
    </form>
  </div>


