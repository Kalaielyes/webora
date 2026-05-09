  <!-- ══ EN ATTENTE TAB ════════════════════════════════ -->
  <div class="section-head">
    <div class="section-title">Demandes en attente de validation</div>
    <a href="<?= APP_URL ?>/view/frontoffice/frontoffice_compte.php" class="btn-ghost">← Retour</a>
  </div>
  <?php if ($pendingCount===0): ?>
  <div style="text-align:center;padding:3rem;color:var(--muted)">
    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:.8rem;display:block;margin-inline:auto"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
    <div style="font-size:.88rem">Aucune demande en attente.</div>
  </div>
  <?php else: ?>
  <?php if (!empty($pendingComptes)): ?>
  <div class="attente-section">
    <div class="attente-header">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      <span class="attente-title">Comptes (<?= count($pendingComptes) ?>)</span>
    </div>
    <?php foreach ($pendingComptes as $c): ?>
    <div class="attente-item">
      <div class="attente-icon"><svg width="15" height="15" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg></div>
      <div class="attente-info">
        <div class="attente-label"><?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> — <?= htmlspecialchars($c->getDevise()) ?></div>
        <div class="attente-meta">IBAN: <?= htmlspecialchars(substr($c->getIban(),0,16)).'...' ?></div>
      </div>
      <?= badgeCompte($c->getStatut()) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($pendingCartes)): ?>
  <div class="attente-section" style="border-color:rgba(79,142,247,.2)">
    <div class="attente-header">
      <svg width="16" height="16" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      <span class="attente-title" style="color:var(--blue)">Cartes (<?= count($pendingCartes) ?>)</span>
    </div>
    <?php foreach ($pendingCartes as $item): $carte=$item['carte']; $cpt=$item['compte']; ?>
    <div class="attente-item">
      <div class="attente-icon" style="background:var(--blue-light);border-color:var(--border2)">
        <svg width="15" height="15" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      </div>
      <div class="attente-info">
        <div class="attente-label"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?> — <?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
        <div class="attente-meta">Compte: <?= htmlspecialchars(ucfirst($cpt->getTypeCompte())) ?></div>
      </div>
      <?= badgeCarte($carte->getStatut()) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

