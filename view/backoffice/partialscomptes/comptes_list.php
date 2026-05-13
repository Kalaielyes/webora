
  <!-- KPIs -->
  <div class="kpi-row">
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--blue-light)">
        <svg width="18" height="18" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_actifs ?></div><div class="kpi-label">Comptes actifs</div></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--amber-light)">
        <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_attente ?></div><div class="kpi-label">En attente</div><?php if($kpi_attente>0):?><div class="kpi-sub" style="color:var(--amber)">À traiter</div><?php endif;?></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--rose-light)">
        <svg width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <div class="kpi-data"><div class="kpi-val"><?= $kpi_bloques ?></div><div class="kpi-label">Bloqués</div></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:var(--green-light)">
        <svg width="18" height="18" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
      </div>
      <div class="kpi-data">
        <div class="kpi-val kpi-val-solde" title="<?= number_format($kpi_solde,3,'.',' ') ?> TND">
          <?php
            $abs = abs($kpi_solde);
            if ($abs >= 1_000_000_000) echo number_format($kpi_solde/1_000_000_000,3,'.',' ').'G';
            elseif ($abs >= 1_000_000) echo number_format($kpi_solde/1_000_000,3,'.',' ').'M';
            elseif ($abs >= 1_000) echo number_format($kpi_solde/1_000,3,'.',' ').'K';
            else echo number_format($kpi_solde,3,'.',' ');
          ?>
        </div>
        <div class="kpi-label">Solde total (TND)</div>
        <div class="kpi-sub" style="color:var(--muted2);font-family:var(--fm);font-size:.6rem;word-break:break-all;line-height:1.3;"><?= number_format($kpi_solde,3,'.',' ') ?></div>
      </div>
    </div>
  </div>

  <!-- Table + Detail panel -->
  <div class="two-col-layout">

    <!-- TABLE -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="table-toolbar-title">Liste des comptes (<?= count($comptes) ?>)</div>
        <div class="filters">
          <button class="filter-btn active" onclick="setFilter('tous',this)">Tous</button>
          <button class="filter-btn" onclick="setFilter('actif',this)">Actifs</button>
          <button class="filter-btn" onclick="setFilter('en_attente',this)">En attente</button>
          <button class="filter-btn" onclick="setFilter('bloque',this)">Bloqués</button>
          <button class="filter-btn" onclick="setFilter('demande_cloture',this)">Dem. clôture</button>
          <button class="filter-btn" onclick="setFilter('demande_suppression',this)">Dem. supp.</button>
          <button class="filter-btn" onclick="setFilter('demande_activation_courant',this)">Dem. activation</button>
          <button class="filter-btn" onclick="setFilter('cloture',this)">Clôturés</button>
        </div>
      </div>
      <table id="comptes-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>IBAN</th>
            <th>Type</th>
            <th>Solde (TND)</th>
            <th>Statut</th>
            <th>Cartes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($comptes as $row):
          $nb = count(CarteController::findByCompte((int)$row['id_compte']));
          $ini = strtoupper(substr($row['prenom']??'',0,1).substr($row['nom']??'',0,1));
          $isSelected = $selected && $selected->getIdCompte()===(int)$row['id_compte'];
        ?>
        <tr data-statut="<?= htmlspecialchars($row['statut']) ?>" <?= $isSelected?'style="background:var(--blue-light)"':'' ?>>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem">
              <div class="dp-av" style="width:28px;height:28px;font-size:.6rem;flex-shrink:0;border-radius:50%"><?= $ini ?></div>
              <div>
                <div class="td-name"><?= htmlspecialchars($row['prenom'].' '.$row['nom']) ?></div>
                <div style="font-size:.65rem;color:var(--muted)"><?= htmlspecialchars($row['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="td-iban"><?= htmlspecialchars(substr($row['iban'],0,20)).'…' ?></td>
          <td><?= typeLabel($row['type_compte']) ?></td>
          <td style="font-family:var(--fm);font-weight:500"><?= number_format((float)$row['solde'],3,'.',' ') ?></td>
          <td><?= badgeCompte($row['statut']) ?></td>
          <td style="font-size:.78rem;color:var(--muted)"><?= $nb ?> carte<?= $nb!==1?'s':'' ?></td>
          <td>
            <div class="action-group">
              <a href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $row['id_compte'] ?>" class="act-btn" title="Voir">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
              </a>
              <a href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $row['id_compte'] ?>&edit=compte" class="act-btn" title="Modifier">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php if ($row['statut']==='en_attente'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="activer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn success" title="Activer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='bloque'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="debloquer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn" title="Débloquer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='demande_cloture'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline" onsubmit="return confirm('Confirmer la clôture ?')">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="cloturer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Confirmer clôture">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='demande_suppression'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline" onsubmit="return confirm('Confirmer la suppression définitive ?')">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Confirmer suppression">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande de suppression ?')">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="refuser_suppression">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn" title="Refuser suppression">
                   <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='demande_activation_courant'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="activer_conversion_courant">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn success" title="Activer courant">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline" onsubmit="return confirm('Refuser la demande ?')">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="refuser_suppression">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn" title="Refuser">
                   <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='cloture'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline" onsubmit="return confirm('Supprimer définitivement ce compte et ses cartes ?')">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Supprimer le compte">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
              </form>
              <?php elseif ($row['statut']==='actif'): ?>
              <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" style="display:inline">
                <?php Security::csrfInput(); ?>
                <input type="hidden" name="action" value="bloquer">
                <input type="hidden" name="id_compte" value="<?= $row['id_compte'] ?>">
                <button type="submit" class="act-btn danger" title="Bloquer">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($comptes)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">Aucun compte trouvé.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>

      <!-- PAGINATION -->
      <div id="pagination-controls" style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-top:1px solid var(--border); background:var(--surface); border-radius:0 0 12px 12px;">
        <div id="pagination-info" style="font-size:0.75rem; color:var(--muted);"></div>
        <div id="pagination-btns" style="display:flex; gap:6px;"></div>
      </div>
    </div>

    <script>
    (function() {
      var PER_PAGE = 10;
      var currentPage = 1;
      var activeFilter = 'tous';

      function getRows() {
        var tbody = document.querySelector('#comptes-table tbody');
        if (!tbody) return [];
        return Array.from(tbody.querySelectorAll('tr[data-statut]'));
      }

      function getVisibleRows() {
        return getRows().filter(function(r) {
          if (activeFilter === 'tous') return true;
          return (r.dataset.statut || '') === activeFilter;
        });
      }

      function renderPage(page) {
        currentPage = page;
        var all = getRows();
        var visible = getVisibleRows();
        var total = visible.length;
        var totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        var start = (currentPage - 1) * PER_PAGE;
        var end   = start + PER_PAGE;

        // Hide all, show visible range
        all.forEach(function(r) { r.style.display = 'none'; });
        visible.forEach(function(r, i) {
          r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        // Info text
        var info = document.getElementById('pagination-info');
        if (info) {
          var from = total === 0 ? 0 : start + 1;
          var to   = Math.min(end, total);
          info.textContent = from + '–' + to + ' sur ' + total + ' compte' + (total > 1 ? 's' : '');
        }

        // Buttons
        var container = document.getElementById('pagination-btns');
        if (!container) return;
        container.innerHTML = '';

        var btnStyle = 'display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--surface2);color:var(--text);transition:all 0.15s;';
        var activeBtnStyle = 'display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;font-size:0.75rem;font-weight:700;cursor:pointer;border:none;background:var(--blue);color:#fff;box-shadow:0 2px 8px rgba(37,99,235,0.3);';

        // Prev
        var prev = document.createElement('button');
        prev.style.cssText = btnStyle;
        prev.innerHTML = '‹';
        prev.disabled = currentPage === 1;
        prev.style.opacity = currentPage === 1 ? '0.4' : '1';
        prev.onclick = function() { renderPage(currentPage - 1); };
        container.appendChild(prev);

        // Page numbers
        var halfWin = 2;
        var pageStart = Math.max(1, currentPage - halfWin);
        var pageEnd   = Math.min(totalPages, pageStart + 4);
        pageStart = Math.max(1, pageEnd - 4);

        for (var p = pageStart; p <= pageEnd; p++) {
          (function(pg) {
            var btn = document.createElement('button');
            btn.style.cssText = pg === currentPage ? activeBtnStyle : btnStyle;
            btn.textContent = pg;
            btn.onclick = function() { renderPage(pg); };
            container.appendChild(btn);
          })(p);
        }

        // Next
        var next = document.createElement('button');
        next.style.cssText = btnStyle;
        next.innerHTML = '›';
        next.disabled = currentPage === totalPages;
        next.style.opacity = currentPage === totalPages ? '0.4' : '1';
        next.onclick = function() { renderPage(currentPage + 1); };
        container.appendChild(next);
      }

      // Override setFilter to reset page
      var _origSetFilter = window.setFilter;
      window.setFilter = function(val, btn) {
        activeFilter = val;
        currentPage = 1;
        if (_origSetFilter) _origSetFilter(val, btn);
        renderPage(1);
      };

      // Initial render
      window.addEventListener('load', function() { renderPage(1); });
      renderPage(1);
    })();
    </script>

    <!-- DETAIL PANEL -->
    <div class="detail-panel">
    <?php if ($selected):
      $db=$_db??null;
      try {
        $db = Config::getConnexion();
        $uq = $db->prepare("SELECT * FROM utilisateur WHERE id=:id");
        $uq->execute(['id'=>$selected->getIdUtilisateur()]);
        $uRow=$uq->fetch();
      } catch(Exception $e){ $uRow=null; }
      $initDP = $uRow ? strtoupper(substr($uRow['prenom']??'',0,1).substr($uRow['nom']??'',0,1)) : '?';
    ?>

    <div class="dp-header">
      <div class="dp-av"><?= $initDP ?></div>
      <div>
        <div class="dp-name"><?= $uRow ? htmlspecialchars($uRow['prenom'].' '.$uRow['nom']) : 'Inconnu' ?></div>
        <?php if ($uRow): ?>
        <div class="dp-cin"><?= htmlspecialchars($uRow['cin']??'') ?></div>
        <div class="dp-kyc"><?= ($uRow['status_kyc']??'')==='VERIFIE'?'KYC vérifié':'KYC: '.htmlspecialchars($uRow['status_kyc']??'—') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($editCompte): ?>
    <!-- ─── INLINE EDIT FORM ────────────────────────── -->
    <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php">
      <?php Security::csrfInput(); ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
      <div class="edit-form">
        <div class="edit-form-title">Modifier le compte #<?= $selected->getIdCompte() ?></div>
        <div class="form-field">
          <label>IBAN (non modifiable)</label>
          <input type="text" value="<?= htmlspecialchars($selected->getIban()) ?>" readonly>
        </div>
        <div class="form-field">
          <label>Type de compte</label>
          <select name="type_compte">
            <?php foreach (['courant'=>'Courant','epargne'=>'Épargne','devise'=>'Devise','professionnel'=>'Professionnel'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $selected->getTypeCompte()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Solde (TND)</label>
          <input type="text" name="solde" value="<?= htmlspecialchars($formData['solde'] ?? $selected->getSolde()) ?>">
          <?php if (isset($formErrors['solde'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['solde']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Devise</label>
          <select name="devise">
            <?php foreach (['TND','EUR','USD','GBP'] as $d): ?>
            <option value="<?= $d ?>" <?= $selected->getDevise()===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Plafond virement (TND)</label>
          <input type="text" name="plafond_virement" value="<?= htmlspecialchars($formData['plafond_virement'] ?? $selected->getPlafondVirement()) ?>">
          <?php if (isset($formErrors['plafond_virement'])): ?>
            <div style="color:var(--rose);font-size:.7rem;margin-top:.2rem;"><?= htmlspecialchars($formErrors['plafond_virement']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label>Statut</label>
          <select name="statut">
            <?php foreach (['actif'=>'Actif','bloque'=>'Bloqué','en_attente'=>'En attente','demande_cloture'=>'Dem. clôture','demande_suppression'=>'Dem. supp.','demande_activation_courant'=>'Dem. activation','cloture'=>'Clôturé'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $selected->getStatut()===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Date de fermeture</label>
          <input type="text" name="date_fermeture" value="<?= htmlspecialchars($formData['date_fermeture'] ?? $selected->getDateFermeture() ?? '') ?>" placeholder="YYYY-MM-DD">
        </div>
        <?php if ($selected->getTypeCompte() === 'epargne'): ?>
        <div class="form-field" style="background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.2); border-radius:10px; padding:1rem;">
          <label style="color:#2563eb; display:flex; align-items:center; gap:6px;">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Taux d'intérêt annuel (%)
          </label>
          <div style="position:relative; margin-top:.4rem;">
            <input type="number" name="taux_interet" step="0.01" min="0" max="30"
              value="<?= htmlspecialchars($formData['taux_interet'] ?? $selected->getTauxInteret()) ?>"
              style="padding-right:2.5rem; font-weight:700; font-size:1rem;">
            <span style="position:absolute; right:.75rem; top:50%; transform:translateY(-50%); font-size:.7rem; color:var(--muted); font-weight:700;">%</span>
          </div>
          <div style="font-size:.68rem; color:var(--muted); margin-top:.35rem;">
            Dernier versement d'intérêts : <strong><?= $selected->getDerniereInteret() ?? 'Jamais (1ère année)' ?></strong>
          </div>
        </div>
        <?php endif; ?>
        <div class="form-actions-row">
          <a href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>" class="btn-cancel">Annuler</a>
          <button type="submit" class="btn-save">Enregistrer</button>
        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ─── READ MODE ───────────────────────────────── -->
    <div>
      <div class="dp-section">Compte</div>
      <div class="dp-row"><span class="dp-key">ID</span><span class="dp-val">#<?= $selected->getIdCompte() ?></span></div>
      <div class="dp-row"><span class="dp-key">IBAN</span><span class="dp-val-mono" style="font-size:.68rem;word-break:break-all"><?= htmlspecialchars($selected->getIban()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Type</span><span class="dp-val"><?= typeLabel($selected->getTypeCompte()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Solde</span><span class="dp-val" style="font-family:var(--fm);color:var(--blue)"><?= number_format((float)$selected->getSolde(),3,'.',' ') ?> <?= htmlspecialchars($selected->getDevise()) ?></span></div>
      <div class="dp-row"><span class="dp-key">Plafond virement</span><span class="dp-val"><?= number_format((float)$selected->getPlafondVirement(),0,'.',' ') ?> TND</span></div>
      <div class="dp-row"><span class="dp-key">Statut</span><?= badgeCompte($selected->getStatut()) ?></div>
      <div class="dp-row"><span class="dp-key">Ouverture</span><span class="dp-val"><?= htmlspecialchars($selected->getDateOuverture()) ?></span></div>
      <?php if ($selected->getDateFermeture()): ?>
      <div class="dp-row"><span class="dp-key">Fermeture</span><span class="dp-val"><?= htmlspecialchars($selected->getDateFermeture()) ?></span></div>
      <?php endif; ?>
      <?php if ($selected->getTypeCompte() === 'epargne'): ?>
      <div class="dp-row" style="background:rgba(37,99,235,0.06); border-radius:8px; padding:.5rem .75rem; margin-top:.3rem;">
        <span class="dp-key" style="color:#2563eb;">Taux d'intérêt</span>
        <span class="dp-val" style="font-weight:800; color:#2563eb;"><?= $selected->getTauxInteret() ?>%</span>
      </div>
      <div class="dp-row">
        <span class="dp-key">Dernier intérêt</span>
        <span class="dp-val"><?= $selected->getDerniereInteret() ?? '<em style="color:var(--muted)">Pas encore appliqué</em>' ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Cards -->
    <div id="cards-section">
      <div class="dp-section">Cartes (<?= count($cartes) ?>)</div>

      <?php if (empty($cartes)): ?>
      <div style="font-size:.75rem;color:var(--muted)">Aucune carte liée.</div>
      <?php else: ?>
        <?php foreach ($cartes as $carte):
          $dpGrad = ($carte->getStatut()==='bloquee') ? 'rcg-bloque' : 'rcg-'.($carte->getStyle()?:'standard');
          $dpReseau = strtolower($carte->getReseau());
          $dpExp = $carte->getDateExpiration();
          $dpExpDisp = ($dpExp && strlen($dpExp) >= 7) ? substr($dpExp, 5, 2) . '/' . substr($dpExp, 2, 2) : ($dpExp ?: '--/--');
          $dpSceneId = 'dp-scene-'.$carte->getIdCarte();
          $dpModalData = json_encode([
            'num'    => $carte->getNumeroCarte(),
            'holder' => $carte->getTitulaireNom(),
            'exp'    => $dpExpDisp,
            'reseau' => strtolower($carte->getReseau()),
            'statut' => $carte->getStatut(),
            'style'  => $carte->getStyle()?:'standard',
            'plafondPay' => $carte->getPlafondPaiementJour(),
            'plafondRet' => $carte->getPlafondRetraitJour(),
            'cvv'        => $carte->getCvvDisplay(),
          ]);
        ?>
        <div style="margin-bottom:1.2rem; position:relative;">
          
          <!-- Flippable card — same design as frontoffice -->
          <div class="card-scene" id="<?= $dpSceneId ?>" onclick="flipDpCard('<?= $dpSceneId ?>')">
            <div class="card-inner">
              <div class="card-face">
                <div class="real-card-front <?= $dpGrad ?>">
                  <div class="card-holo"></div>
                  <div class="rcf-top">
                    <div class="rcf-chip">
                      <div class="rcf-chip-grid"><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div><div class="rcf-chip-sq"></div></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.4rem;position:relative;z-index:1;">
                      <div style="font-size:.5rem;font-weight:700;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.12em;background:rgba(255,255,255,.08);border-radius:3px;padding:1px 5px;"><?= strtoupper(styleLabel($carte->getStyle()?:'standard')) ?></div>
                      <svg class="rcf-contactless" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10"/><path d="M12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6"/><path d="M12 10c1.1 0 2 .9 2 2s-.9 2-2 2"/></svg>
                    </div>
                  </div>
                  <div class="rcf-number"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
                  <div class="rcf-bottom">
                    <div>
                      <div class="rcf-holder-lbl">Card Holder</div>
                      <div class="rcf-holder-val"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
                    </div>
                    <div style="text-align:center">
                      <div class="rcf-exp-lbl">Expires</div>
                      <div class="rcf-exp-val"><?= htmlspecialchars($dpExpDisp) ?></div>
                    </div>
                    <?php if ($dpReseau==='visa'): ?>
                    <div class="rcf-visa">VISA</div>
                    <?php else: ?>
                    <div class="rcf-mc"><div class="rcf-mc-l"></div><div class="rcf-mc-r"></div></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="card-face card-face-back">
                <div class="real-card-back <?= $dpGrad ?>">
                  <div class="rcb-stripe"></div>
                  <div class="rcb-sig-area">
                    <div class="rcb-sig-lbl">Signature autorisée</div>
                    <div class="rcb-sig-box">
                      <div class="rcb-sig-strip"></div>
                      <div class="rcb-cvv"><?= htmlspecialchars($carte->getCvvDisplay() ?: '•••') ?></div>
                    </div>
                  </div>
                  <div class="rcb-footer">
                    <div>
                      <div class="rcb-bank">LegalFin</div>
                      <div style="font-size:.45rem;color:rgba(255,255,255,.22);margin-top:2px;">Service client: 71 000 000</div>
                    </div>
                    <?php if ($dpReseau==='visa'): ?>
                    <div class="rcf-visa" style="font-size:.8rem;opacity:.4;">VISA</div>
                    <?php else: ?>
                    <div class="rcf-mc"><div class="rcf-mc-l" style="width:18px;height:18px;opacity:.6;"></div><div class="rcf-mc-r" style="width:18px;height:18px;opacity:.6;"></div></div>
                    <?php endif; ?>
                  </div>
                  <div class="rcb-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez le 71 000 000.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-flip-hint" style="margin-top:.3rem;">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
            Cliquer pour retourner · <a href="#" onclick="openCardModal(<?= htmlspecialchars($dpModalData) ?>);return false;" style="color:var(--blue);text-decoration:none;font-size:.58rem;">Voir en grand</a>
          </div>
          <?= badgeCarte($carte->getStatut()) ?>&nbsp;
          <span style="font-size:.62rem;background:var(--bg3);border:1px solid var(--border);border-radius:3px;padding:1px 5px;color:var(--muted);text-transform:uppercase"><?= htmlspecialchars(styleLabel($carte->getStyle())) ?></span>

          <!-- Card actions -->
          <div class="carte-mini-actions" style="margin-top:.5rem;">
            <a href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>&edit_carte=<?= $carte->getIdCarte() ?>" class="cma-btn" title="Modifier">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Modifier
            </a>
            <?php if ($carte->getStatut()==='inactive'): ?>
            <form method="POST" action="<?= APP_URL ?>/controller/CarteController.php" style="flex:1">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="activer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <button type="submit" class="cma-btn success" style="width:100%">Activer</button>
            </form>
            <?php elseif ($carte->getStatut()==='bloquee'): ?>
            <form method="POST" action="<?= APP_URL ?>/controller/CarteController.php" style="flex:1">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="debloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <button type="submit" class="cma-btn success" style="width:100%">Débloquer</button>
            </form>
            <?php else: ?>
            <form method="POST" action="<?= APP_URL ?>/controller/CarteController.php" style="flex:1">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="bloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <button type="submit" class="cma-btn danger" style="width:100%">Bloquer</button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= APP_URL ?>/controller/CarteController.php" style="flex:1" onsubmit="return confirm('Supprimer cette carte ?')">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <button type="submit" class="cma-btn danger" style="width:100%">🗑 Suppr.</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Account actions -->
    <div class="dp-actions">
      <a href="<?= APP_URL ?>/view/backoffice/backoffice_compte.php?tab=comptes&id_compte=<?= $selected->getIdCompte() ?>&edit=compte" class="dp-action-btn da-primary">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Modifier le compte
      </a>
      <?php if ($selected->getStatut()==='bloque'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="debloquer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-success" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Débloquer le compte
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='en_attente'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="activer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-success" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Activer le compte
        </button>
      </form>
      <?php elseif (in_array($selected->getStatut(),['actif','demande_cloture','demande_suppression'])): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="bloquer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          Bloquer le compte
        </button>
      </form>
      <?php endif; ?>
      <?php if ($selected->getStatut()==='demande_cloture'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Accepter la demande de clôture ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="cloturer">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;background:#991B1B;color:#fff;border:none">
          ✓ Accepter clôture (dem. client)
        </button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Refuser la demande de clôture ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="refuser_cloture">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted);border:none">
          ✕ Refuser clôture
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='demande_suppression'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Accepter la demande de suppression (Suppression définitive) ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;background:#991B1B;color:#fff;border:none">
          ✓ Accepter suppression
        </button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Refuser la demande de suppression ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="refuser_suppression">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted);border:none">
          ✕ Refuser suppression
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='demande_activation_courant'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Accepter la conversion en compte courant ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="activer_conversion_courant">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-success" style="width:100%;border:none">
          ✓ Activer comme courant
        </button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Refuser la demande ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="refuser_suppression">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn" style="width:100%;background:rgba(107,114,128,.15);color:var(--muted);border:none">
          ✕ Refuser demande
        </button>
      </form>
      <?php elseif ($selected->getStatut()==='cloture'): ?>
      <form method="POST" action="<?= APP_URL ?>/controller/CompteController.php" onsubmit="return confirm('Supprimer définitivement ce compte et ses cartes ?')">
        <?php Security::csrfInput(); ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
        <button type="submit" class="dp-action-btn da-danger" style="width:100%;border:none">
          🗑 Supprimer le compte (clôturé)
        </button>
      </form>
      <?php endif; ?>
    </div>

    <?php endif; // editCompte ?>

    <?php else: ?>
    <!-- Empty state -->
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;color:var(--muted);text-align:center;padding:2.5rem 0">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" opacity=".3"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
      <div style="font-size:.82rem">Sélectionnez un compte<br>pour voir le détail</div>
    </div>
    <?php endif; // selected ?>
    </div><!-- .detail-panel -->

  </div><!-- .two-col-layout -->

