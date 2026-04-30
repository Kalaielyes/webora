
  <!-- ══ EN ATTENTE TAB ════════════════════════════════ -->
  <?php if ($pendingTotal===0): ?>
  <div style="text-align:center;padding:4rem;color:var(--muted)">
    <svg width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;display:block;margin:0 auto 1rem"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
    <div style="font-size:.9rem">Aucune demande en attente — tout est à jour.</div>
  </div>
  <?php else: ?>

  <?php if (!empty($pendingComptes)): ?>
  <!-- Pending comptes -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Comptes en attente (<?= count($pendingComptes) ?>)</div>
    </div>
    <table>
      <thead><tr><th>Client</th><th>IBAN</th><th>Type</th><th>Statut</th><th>Ouverture</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pendingComptes as $row): ?>
      <tr>
        <td>
          <div class="td-name"><?= htmlspecialchars($row['prenom'].' '.$row['nom']) ?></div>
          <div style="font-size:.65rem;color:var(--muted)"><?= htmlspecialchars($row['email']) ?></div>
        </td>
        <td class="td-iban"><?= htmlspecialchars($row['iban']) ?></td>
        <td><?= typeLabel($row['type_compte']) ?></td>
        <td><?= badgeCompte($row['statut']) ?></td>
        <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($row['date_ouverture']) ?></td>
        <td>
          <div class="action-group">
            <?php if ($row['statut']==='en_attente'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Voulez-vous supprimer cette demande ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn danger" title="Supprimer">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($row['statut']==='demande_cloture'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Accepter la clôture ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="cloturer">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter clôture">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de clôture ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="refuser_cloture">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn" title="Refuser clôture" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($row['statut']==='demande_suppression'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Accepter la suppression définitive ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter suppression">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="refuser_suppression">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn" title="Refuser suppression" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>

            <?php if ($row['statut']==='demande_activation_courant'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Accepter la conversion en compte courant ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="activer_conversion_courant">
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn success" title="Accepter activation courant">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande d\'activation ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="refuser_suppression"> <?php /* We use refuser_suppression as it resets status to 'actif' */ ?>
              <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
              <button type="submit" class="act-btn" title="Refuser activation" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($pendingCartes)): ?>
  <!-- Pending cartes -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Cartes en attente (<?= count($pendingCartes) ?>)</div>
    </div>
    <table>
      <thead><tr><th>Titulaire</th><th>Numéro</th><th>Type / Réseau</th><th>Statut</th><th>Emission</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pendingCartes as $carte): ?>
      <tr>
        <td class="td-name"><?= htmlspecialchars($carte->getTitulaireNom()) ?></td>
        <td class="td-iban"><?= htmlspecialchars($carte->getNumeroCarte()) ?></td>
        <td><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> · <?= strtoupper($carte->getReseau()) ?></td>
        <td><?= badgeCarte($carte->getStatut()) ?></td>
        <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($carte->getDateEmission()) ?></td>
        <td>
          <div class="action-group">
            <?php if ($carte->getStatut()==='inactive'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Voulez-vous supprimer cette demande ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn danger" title="Supprimer">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
              </button>
            </form>
            <a href="<?= APP_URL ?>/views/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $carte->getIdCompte() ?>" class="act-btn" title="Voir détail du compte">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </a>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_blocage'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Accepter et bloquer cette carte ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="bloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter blocage">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de blocage ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="refuser_blocage">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser blocage" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_cloture' || $carte->getStatut()==='demande_suppression'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Accepter la suppression de cette carte ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter suppression">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="refuser_cloture">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser suppression" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            <?php if ($carte->getStatut()==='demande_reactivation'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Réactiver cette carte ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn success" title="Accepter réactivation">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="display:inline" onsubmit="return confirm('Refuser la réactivation ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="statut" value="expiree">
              <input type="hidden" name="redirect_id_compte" value="<?= $carte->getIdCompte() ?>">
              <button type="submit" class="act-btn" title="Refuser réactivation" style="background:rgba(107,114,128,.15);color:var(--muted)">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
            <?php endif; ?>
            
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?php endif; // pendingTotal ?>
