  <!-- ══ MAIN ACCOUNT VIEW ══════════════════════════════ -->

  <?php if (count($comptes)>1): ?>
  <div class="accounts-switcher">
    <?php foreach ($comptes as $c): ?>
    <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?id_compte=<?= $c->getIdCompte() ?>"
       class="acct-tab <?= ($selected&&$selected->getIdCompte()===$c->getIdCompte())?'active':'' ?>">
      <?= htmlspecialchars(ucfirst($c->getTypeCompte())) ?> ···<span class="sensitive-data"><?= substr($c->getIban(),-4) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Balance card -->
  <div class="balance-card">
    <div class="bc-top">
      <div>
        <div class="bc-label">Solde disponible</div>
        <div class="bc-amount sensitive-data"><?= number_format((float)$selected->getSolde(),3,'.',' ') ?><span><?= htmlspecialchars($selected->getDevise()) ?></span></div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.45rem">
        <div class="bc-type-badge"><?= htmlspecialchars(ucfirst($selected->getTypeCompte())) ?></div>
        <?= badgeCompte($selected->getStatut()) ?>
      </div>
    </div>
    <div class="bc-iban-row">
      <div class="copy-wrapper">
        <div>
          <div class="bc-iban-label">IBAN</div>
          <div class="bc-iban sensitive-data" id="iban-to-copy"><?= htmlspecialchars($selected->getIban()) ?></div>
        </div>
        <button class="copy-btn" onclick="copyToClipboard('<?= $selected->getIban() ?>', this)" title="Copier l'IBAN" style="margin-top: 10px;">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
        </button>
        <div class="copy-tooltip">Copié !</div>
      </div>
      <div style="text-align:right">
        <div class="bc-iban-label">Date d'ouverture</div>
        <div style="font-size:.82rem;color:var(--muted2)"><?= htmlspecialchars($selected->getDateOuverture()) ?></div>
      </div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.6rem;margin-top:.85rem;padding-top:.8rem;border-top:1px solid var(--border)">
      <div style="font-size:.72rem;color:var(--muted2)">
        Plafond virement: <strong style="color:var(--text)"><?= number_format((float)$selected->getPlafondVirement(),0,'.',' ') ?> TND</strong>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if ($selected->getStatut()==='actif'): ?>
        <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:contents">
          <?php Security::csrfInput(); ?>
          <input type="hidden" name="action" value="demande_suppression">
          <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
          <button type="submit" class="acct-action-btn aa-neutral" onclick="return confirm('Demander la suppression de ce compte ?\nUn agent bancaire traitera votre demande.')">Dem. suppression</button>
        </form>
        <?php elseif ($selected->getStatut()==='bloque'): ?>
        <div class="notice-msg notice-amber">🔒 Ce compte est bloqué. Contactez votre conseiller.</div>
        <?php elseif ($selected->getStatut()==='demande_cloture'): ?>
        <div class="notice-msg notice-amber">⏳ Demande de clôture en attente de validation.</div>
        <?php elseif ($selected->getStatut()==='demande_suppression'): ?>
        <div class="notice-msg notice-amber">⏳ Demande de suppression en attente de validation.</div>
        <?php elseif ($selected->getStatut()==='demande_activation_courant'): ?>
        <div class="notice-msg notice-amber">⏳ Demande d'activation en compte courant en cours...</div>
        <?php elseif ($selected->getStatut()==='en_attente'): ?>
        <div class="notice-msg notice-amber">⏳ Compte en attente d'activation.</div>
        <?php endif; ?>

        <?php if ($selected->getTypeCompte() === 'epargne' && $selected->getStatut() === 'actif'): ?>
        <form method="POST" action="<?= APP_URL ?>/controllers/CompteController.php" style="display:contents">
          <?php Security::csrfInput(); ?>
          <input type="hidden" name="action" value="demande_activation_courant">
          <input type="hidden" name="id_compte" value="<?= $selected->getIdCompte() ?>">
          <button type="submit" class="acct-action-btn aa-neutral" style="background:var(--blue-light); color:var(--blue); border-color:var(--blue-light);" onclick="return confirm('Demander l\'activation de ce compte en compte courant ?')">
            🚀 Activer comme courant
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($selected->getTypeCompte() === 'epargne'): 
      $interestRate = $selected->getTauxInteret() / 100;  // Use real rate from DB
      $rateDisplay  = number_format($selected->getTauxInteret(), 2) . '% / AN';
      $estInterest  = $selected->getSolde() * $interestRate;
      $y1 = $selected->getSolde() * pow(1 + $interestRate, 1);
      $y5 = $selected->getSolde() * pow(1 + $interestRate, 5);
      $gain5 = $y5 - $selected->getSolde();
  ?>
  <style>
    .saving-card { margin-top: 1.5rem; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--r); padding: 1.8rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
  </style>

  <div style="margin-top: 1.5rem; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--r); padding: 1.8rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position:relative; overflow:hidden;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; position:relative; z-index:2;">
      <div>
        <div style="font-size: 0.65rem; color: var(--muted2); font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.6rem;">Gain Annuel Estimé</div>
        <div style="font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--green); line-height: 1; letter-spacing: -0.02em;">
          +<span class="sensitive-data"><?= number_format($estInterest, 2, '.', ' ') ?></span> <span style="font-size: 0.9rem; color: var(--muted2); font-weight: 500;"><?= htmlspecialchars($selected->getDevise()) ?></span>
        </div>
        <div style="margin-top: 0.8rem; display: flex; gap: 0.5rem;">
          <span style="background: var(--green-light); color: var(--green); padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 700;"><?= $rateDisplay ?></span>
          <span style="background: var(--blue-light); color: var(--blue); padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 700;">+<?= number_format($gain5,0) ?> <?= $selected->getDevise() ?> / 5ans</span>
        </div>
      </div>
      <div style="width: 44px; height: 44px; background: var(--bg3); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--green);">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      </div>
    </div>

    <div style="height: 220px; width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 16px; padding: 1rem; position:relative; z-index:2;">
      <canvas id="savingProjectionChartMega"></canvas>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const g_ctx = document.getElementById('savingProjectionChartMega').getContext('2d');
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      const g_gridColor = isLight ? 'rgba(0,0,0,0.04)' : 'rgba(255,255,255,0.04)';
      
      const g_gradient = g_ctx.createLinearGradient(0, 0, 0, 220);
      g_gradient.addColorStop(0, 'rgba(34, 197, 94, 0.22)');
      g_gradient.addColorStop(1, 'rgba(34, 197, 94, 0)');

      new Chart(g_ctx, {
        type: 'line',
        data: {
          labels: ['Actuel', 'An 1', 'An 2', 'An 3', 'An 4', 'An 5'],
          datasets: [{
            data: [
              <?= $selected->getSolde() ?>,
              <?= $y1 ?>,
              <?= $selected->getSolde() * pow(1 + $interestRate, 2) ?>,
              <?= $selected->getSolde() * pow(1 + $interestRate, 3) ?>,
              <?= $selected->getSolde() * pow(1 + $interestRate, 4) ?>,
              <?= $y5 ?>
            ],
            borderColor: '#22C55E',
            backgroundColor: g_gradient,
            borderWidth: 3,
            fill: true,
            tension: 0.44,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#22C55E',
            pointBorderWidth: 3,
            pointRadius: 1,
            pointHoverRadius: 6,
            pointHoverBorderWidth: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 12,
                titleFont: { size: 13, family: 'Syne', weight: 'bold' },
                bodyFont: { size: 12, family: 'DM Sans' },
                displayColors: false,
                callbacks: { label: function(c) { return ' ' + Number(c.raw).toLocaleString() + ' <?= $selected->getDevise() ?>'; } }
            }
          },
          scales: {
            y: { 
              grid: { color: g_gridColor, drawBorder: false },
              ticks: { color: '#888', font: { size: 10, family: 'DM Sans' }, padding: 8,
              callback: function(v) { return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v); }}
            },
            x: { grid: { display: false }, ticks: { color: '#888', font: { size: 10, family: 'DM Sans', weight: 'bold' }, padding: 8 }}
          }
        }
      });
    });
  </script>
  <?php endif; ?>

  <?php if ($selected->getTypeCompte() !== 'epargne'): ?>
  <!-- Cards section -->
  <div>
    <div class="section-head" style="margin-bottom:.9rem">
      <div class="section-title">Mes cartes (<?= count($cartes) ?>)</div>
      <?php if ($selected->getStatut()==='actif'): ?>
        <?php if ($isKycVerifie): ?>
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-ghost">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Nouvelle carte
        </a>
        <?php else: ?>
        <button class="btn-ghost" style="opacity:0.5;cursor:not-allowed;" onclick="alert('Vérification KYC requise pour demander une carte.')" title="Vérification KYC requise">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Nouvelle carte 🔒
        </button>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (empty($cartes)): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:2rem 1.5rem;text-align:center;color:var(--muted)">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:.6rem;display:block;margin-inline:auto"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      <div style="font-size:.82rem;margin-bottom:.7rem">Aucune carte liée à ce compte.</div>
      <?php if ($selected->getStatut()==='actif'): ?>
        <?php if ($isKycVerifie): ?>
        <a href="<?= APP_URL ?>/views/frontoffice/frontoffice_compte.php?form=carte&id_compte=<?= $selected->getIdCompte() ?>" class="btn-primary">Demander une carte</a>
        <?php else: ?>
        <div class="notice-msg notice-amber" style="display:inline-block;margin-top:1rem;">🔒 Vérification KYC requise pour demander une carte.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="cartes-grid">
      <?php foreach ($cartes as $i=>$carte):
        $cardId = 'card-'.$carte->getIdCarte();
        $isBloque = $carte->getStatut()==='bloquee';
        $gradClass = $isBloque ? 'rcg-bloque' : 'rcg-'.($carte->getStyle()?:'standard');
        $reseau = strtolower($carte->getReseau());
        $expRaw = $carte->getDateExpiration(); // YYYY-MM or YYYY-MM-DD
        $expDisplay = '';
        if ($expRaw && strlen($expRaw) >= 7) {
            $expDisplay = substr($expRaw, 5, 2) . '/' . substr($expRaw, 2, 2);
        } else {
            $expDisplay = $expRaw ?: '--/--';
        }
      ?>
      <div class="carte-card">

        <!-- 3D FLIP CARD -->
        <div class="card-scene" id="<?= $cardId ?>" onclick="flipCard('<?= $cardId ?>')">
          <div class="card-inner">

            <!-- FRONT -->
            <div class="card-face">
              <div class="real-card-front <?= $gradClass ?>">
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
                <div class="rcf-number sensitive-data"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
                <div class="rcf-bottom">
                  <div>
                    <div class="rcf-holder-lbl">Card Holder</div>
                    <div class="rcf-holder-val"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
                  </div>
                  <div style="text-align:center">
                    <div class="rcf-exp-lbl">Expires</div>
                    <div class="rcf-exp-val"><?= htmlspecialchars($expDisplay) ?></div>
                  </div>
                  <?php if ($reseau==='visa'): ?>
                  <div class="rcf-visa">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l"></div><div class="rcf-mc-r"></div></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- BACK -->
            <div class="card-face card-face-back">
              <div class="real-card-back <?= $gradClass ?>">
                <div class="rcb-stripe"></div>
                <div class="rcb-sig-area">
                  <div class="rcb-sig-lbl">Signature autorisée</div>
                  <div class="rcb-sig-box">
                    <div class="rcb-sig-strip"></div>
                    <div class="rcb-cvv sensitive-data"><?= htmlspecialchars($carte->getCvvDisplay() ?: '•••') ?></div>
                  </div>
                </div>
                <div class="rcb-footer">
                  <div>
                    <div class="rcb-bank">LegalFin</div>
                    <div style="font-size:.45rem;color:rgba(255,255,255,.22);margin-top:2px;">Service client: 71 000 000</div>
                  </div>
                  <?php if ($reseau==='visa'): ?>
                  <div class="rcf-visa" style="font-size:.8rem;opacity:.4;">VISA</div>
                  <?php else: ?>
                  <div class="rcf-mc"><div class="rcf-mc-l" style="width:18px;height:18px;opacity:.6;"></div><div class="rcf-mc-r" style="width:18px;height:18px;opacity:.6;"></div></div>
                  <?php endif; ?>
                </div>
                <div class="rcb-fine">Cette carte est la propriété de LegalFin. En cas de perte ou vol, appelez le 71 000 000.</div>
              </div>
            </div>

          </div><!-- .card-inner -->
        </div><!-- .card-scene -->

        <div class="card-flip-hint">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
          Cliquez pour retourner
        </div>

        <!-- DETAILS -->
        <div class="card-info-section">
          <!-- Header row: type + status -->
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.8rem;flex-wrap:wrap;">
            <span style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars(ucfirst($carte->getTypeCarte())) ?> <?= strtoupper($carte->getReseau()) ?></span>
            <?= badgeCarte($carte->getStatut()) ?>
            <span style="margin-left:auto;font-size:.62rem;background:var(--surface2);border:1px solid var(--border);border-radius:4px;padding:2px 7px;color:var(--muted2);text-transform:uppercase;letter-spacing:.06em;"><?= strtoupper(styleLabel($carte->getStyle()?:'standard')) ?></span>
          </div>

          <!-- Full info grid -->
          <div class="carte-info-grid" style="grid-template-columns:1fr 1fr;gap:.65rem;">
            <div class="carte-info-item">
              <div class="ci-label">Titulaire</div>
              <div class="ci-val" style="font-size:.78rem;"><?= htmlspecialchars($carte->getTitulaireNom()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Numéro</div>
              <div class="ci-val sensitive-data" style="font-family:var(--fm);font-size:.75rem;letter-spacing:.06em;"><?= htmlspecialchars($carte->getNumeroCarte()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Plafond paiement/j</div>
              <div class="ci-val"><?= number_format((float)$carte->getPlafondPaiementJour(),0,'.',' ') ?> TND</div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Plafond retrait/j</div>
              <div class="ci-val"><?= number_format((float)$carte->getPlafondRetraitJour(),0,'.',' ') ?> TND</div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Réseau</div>
              <div class="ci-val"><?= strtoupper($carte->getReseau()) ?></div>
            </div>
            <div class="carte-info-item">
              <div class="ci-label">Expiration</div>
              <div class="ci-val"><?= htmlspecialchars($expDisplay) ?></div>
            </div>
            <?php if ($carte->getDateEmission()): ?>
            <div class="carte-info-item">
              <div class="ci-label">Date d'émission</div>
              <div class="ci-val"><?= htmlspecialchars($carte->getDateEmission()) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($carte->getMotifBlocage()): ?>
            <div class="carte-info-item" style="grid-column:1/-1;">
              <div class="ci-label" style="color:var(--rose);">Motif blocage</div>
              <div class="ci-val" style="color:var(--rose);font-size:.77rem;"><?= htmlspecialchars($carte->getMotifBlocage()) ?></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Status notices -->
          <?php if ($carte->getStatut()==='demande_cloture' || $carte->getStatut()==='demande_suppression'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ Demande de suppression en cours de traitement.</div>
          <?php elseif ($carte->getStatut()==='inactive'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ En attente d'activation par un agent bancaire.</div>
          <?php elseif ($carte->getStatut()==='expiree'): ?>
          <div class="notice-msg notice-red" style="margin-top:.7rem;">✕ Cette carte est expirée.</div>
          <div class="carte-actions-row" style="margin-top:.8rem;">
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="width:100%;">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="demander_reactivation">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="btn-primary" style="width:100%; font-size:.75rem; padding:.5rem;">Demander Réactivation</button>
            </form>
          </div>
          <?php elseif ($carte->getStatut()==='demande_reactivation'): ?>
          <div class="notice-msg notice-amber" style="margin-top:.7rem;">⏳ Demande de réactivation en cours.</div>
          <?php else: ?>
          <!-- Action buttons -->
          <div class="carte-actions-row" style="margin-top:.8rem;">
            <?php if ($carte->getStatut()==='bloquee'): ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="debloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn success" style="width:100%;" onclick="return confirm('Débloquer cette carte ?')">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Débloquer
              </button>
            </form>
            <?php else: ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="bloquer">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn danger" style="width:100%;" onclick="return confirm('Bloquer cette carte ?')">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Bloquer
              </button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= APP_URL ?>/controllers/CarteController.php" style="flex:1;">
              <?php Security::csrfInput(); ?>
              <input type="hidden" name="action" value="demande_suppression">
              <input type="hidden" name="id_carte" value="<?= $carte->getIdCarte() ?>">
              <input type="hidden" name="redirect_id_compte" value="<?= $selected->getIdCompte() ?>">
              <input type="hidden" name="origin" value="front">
              <button type="submit" class="carte-action-btn danger" style="width:100%;" onclick="return confirm('Demander la suppression de cette carte ?')">
                Supprimer
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>

      </div><!-- .carte-card -->
      <?php endforeach; ?>
    </div>
    <?php endif; // cartes ?>
  </div>
  <?php endif; // not epargne ?>

  </div>
