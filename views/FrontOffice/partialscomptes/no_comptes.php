  <!-- ══ NO ACCOUNTS ═══════════════════════════════════ -->
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted)">
    <svg width="52" height="52" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:1rem;opacity:.3;display:block;margin-inline:auto"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
    <div style="font-size:.9rem;margin-bottom:.5rem">Aucun compte bancaire</div>
    <div style="font-size:.78rem;margin-bottom:1.3rem">Soumettez une demande pour ouvrir votre premier compte.</div>
    <?php if ($isKycVerifie): ?>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=compte" class="btn-primary">Demander un compte</a>
    <?php else: ?>
    <div class="notice-msg notice-amber" style="display:inline-block;margin-top:1rem;">🔒 Votre identité doit être vérifiée (KYC) pour ouvrir un compte.</div>
    <?php endif; ?>
  </div>

