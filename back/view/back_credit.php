<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFinAI — Espace Client</title>
  <link rel="stylesheet" href="creditttttttttttttttt.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap"
    rel="stylesheet" />
  <style>
    .btn-backoffice {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: linear-gradient(120deg, var(--rose), #cc2244);
      color: #fff;
      font-family: var(--fh);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.32rem 0.85rem;
      border-radius: 8px;
      text-decoration: none;
      letter-spacing: 0.03em;
      border: 1px solid rgba(255, 77, 109, 0.4);
      transition: opacity 0.2s, transform 0.15s;
      white-space: nowrap;
    }

    .btn-backoffice:hover {
      opacity: 0.85;
      transform: translateY(-1px);
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-logo">🏦 Legal<span>Fin</span>AI</div>
    <div class="sb-user">
      <div class="sb-av">AK</div>
      <div>
        <div class="sb-uname">Nom Prénom</div>
        <div class="sb-badge">
          <span class="sb-dot"></span> Compte vérifié
        </div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-sec">Principal</div>
      <a class="sb-item on" onclick="showPage('dashboard',this)"><span class="sb-ico">📊</span> Tableau de bord</a>
      <a class="sb-item" onclick="showPage('comptes',this)"><span class="sb-ico">🏦</span> Mes Comptes</a>
      <a class="sb-item" onclick="showPage('virements',this)"><span class="sb-ico">💸</span> Virements</a>
      <a class="sb-item" onclick="showPage('credits',this)"><span class="sb-ico">📈</span> Crédits & Épargne</a>
      <a class="sb-item" onclick="showPage('cartes',this)"><span class="sb-ico">💳</span> Mes Cartes</a>
      <div class="sb-sec">Support</div>
      <a class="sb-item" onclick="showPage('reclamations',this)"><span class="sb-ico">📣</span> Réclamations
        <span class="sb-badge2">2</span></a>
      <div class="sb-sec">Compte</div>
      <a class="sb-item" onclick="showPage('profil',this)"><span class="sb-ico">👤</span> Mon Profil</a>
      <a class="sb-item"><span class="sb-ico">⚙️</span> Paramètres</a>
    </nav>
    <div class="sb-footer">
      <div class="sb-ft-btn">🚪 Déconnexion</div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-title" id="page-title">Tableau de bord</div>
      <div class="tb-right">
        <div class="live"><span class="ldot"></span> Données en direct</div>
        <div class="tb-notif" onclick="alert('3 nouvelles notifications')">
          🔔
          <div class="tb-notif-dot"></div>
        </div>
        <!-- ═══ BOUTON BACK-OFFICE ═══ -->
        <a href="/projet_web/front/view/front_credit.php" class="btn-backoffice" title="Accéder au Back Office">
          🛡️ front Office →
        </a>
      </div>
    </header>

    <div class="content">
      <!-- ═══ DASHBOARD ═══ -->
      <div class="page on" id="page-dashboard">
        <div class="bank-card">
          <div class="bc-lbl">Solde disponible</div>
          <div class="bc-bal">
            — —<span style="font-size: 1rem; color: var(--muted)"> TND</span>
          </div>
          <div class="bc-row">
            <div class="bc-item">
              <div class="v" style="color: var(--emerald)">+4 200 TND</div>
              <div class="l">Revenus</div>
            </div>
            <div class="bc-item">
              <div class="v" style="color: var(--rose)">-1 840 TND</div>
              <div class="l">Dépenses</div>
            </div>
            <div class="bc-item">
              <div class="v" style="color: var(--cyan)">+890 TND</div>
              <div class="l">Épargne</div>
            </div>
          </div>
          <div style="
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
              ">
            <div>
              <div class="bc-num">•••• •••• •••• ——</div>
              <div style="
                    font-size: 0.7rem;
                    color: var(--muted);
                    margin-top: 2px;
                  ">
                Nom Prénom · Exp: ——/——
              </div>
            </div>
            <div class="bc-logo">VISA</div>
          </div>
        </div>
        <div class="qa-grid">
          <div class="qa" onclick="openModal('virement')">
            <div class="qa-ico">💸</div>
            <div class="qa-lbl">Virement</div>
            <div class="qa-sub">Envoyer argent</div>
          </div>
          <div class="qa" onclick="showPage('credits',null)">
            <div class="qa-ico">📈</div>
            <div class="qa-lbl">Crédit</div>
            <div class="qa-sub">Simuler & demander</div>
          </div>
          <div class="qa" onclick="showPage('cartes',null)">
            <div class="qa-ico">💳</div>
            <div class="qa-lbl">Carte virtuelle</div>
            <div class="qa-sub">Créer en 1 clic</div>
          </div>
          <div class="qa" onclick="showPage('reclamations',null)">
            <div class="qa-ico">📣</div>
            <div class="qa-lbl">Réclamation</div>
            <div class="qa-sub">Soumettre un ticket</div>
          </div>
        </div>
        <div class="stats">
          <div class="stat sg">
            <div class="qa-ico">💰</div>
            <div class="sv" style="color: var(--gold)">— TND</div>
            <div class="sl">Solde courant</div>
            <div class="st" style="color: var(--muted)">
              En attente de données
            </div>
          </div>
          <div class="stat sc">
            <div class="qa-ico">🏦</div>
            <div class="sv" style="color: var(--cyan)">— TND</div>
            <div class="sl">Compte épargne</div>
            <div class="st" style="color: var(--muted)">
              En attente de données
            </div>
          </div>
          <div class="stat se">
            <div class="qa-ico">📈</div>
            <div class="sv" style="color: var(--emerald)">— TND</div>
            <div class="sl">Crédit en cours</div>
            <div class="st" style="color: var(--muted)">— mois restants</div>
          </div>
          <div class="stat sr">
            <div class="qa-ico">📣</div>
            <div class="sv" style="color: var(--rose)">—</div>
            <div class="sl">Réclamations ouvertes</div>
            <div class="st" style="color: var(--rose)">
              En attente réponse
            </div>
          </div>
        </div>
        <div class="two-col">
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">🧾 Dernières transactions</div>
              <a class="sc-link">Voir tout →</a>
            </div>
            <div style="
                  padding: 2rem;
                  text-align: center;
                  color: var(--muted);
                  font-size: 0.8rem;
                ">
              Aucune transaction à afficher
            </div>
          </div>
          <div>
            <div class="sc" style="margin-bottom: 0.9rem">
              <div class="sc-hd">
                <div class="sc-title">📊 Dépenses du mois</div>
              </div>
              <div class="spend-row">
                <div class="spend-lbl">Alimentation</div>
                <div class="spend-track">
                  <div class="spend-fill" style="
                        width: 65%;
                        background: linear-gradient(
                          90deg,
                          var(--gold),
                          var(--gold2)
                        );
                      "></div>
                </div>
                <div class="spend-val" style="color: var(--gold)">— TND</div>
              </div>
              <div class="spend-row">
                <div class="spend-lbl">Transport</div>
                <div class="spend-track">
                  <div class="spend-fill" style="
                        width: 28%;
                        background: linear-gradient(
                          90deg,
                          var(--cyan),
                          #0099cc
                        );
                      "></div>
                </div>
                <div class="spend-val" style="color: var(--cyan)">— TND</div>
              </div>
              <div class="spend-row">
                <div class="spend-lbl">Santé</div>
                <div class="spend-track">
                  <div class="spend-fill" style="
                        width: 24%;
                        background: linear-gradient(
                          90deg,
                          var(--violet),
                          #7c3aed
                        );
                      "></div>
                </div>
                <div class="spend-val" style="color: var(--violet)">
                  — TND
                </div>
              </div>
              <div class="spend-row">
                <div class="spend-lbl">Loisirs</div>
                <div class="spend-track">
                  <div class="spend-fill" style="
                        width: 18%;
                        background: linear-gradient(
                          90deg,
                          var(--emerald),
                          #00aa77
                        );
                      "></div>
                </div>
                <div class="spend-val" style="color: var(--emerald)">
                  — TND
                </div>
              </div>
              <div class="spend-row">
                <div class="spend-lbl">Autres</div>
                <div class="spend-track">
                  <div class="spend-fill" style="width: 14%; background: var(--muted)"></div>
                </div>
                <div class="spend-val" style="color: var(--muted)">— TND</div>
              </div>
            </div>
            <div class="sc">
              <div class="sc-hd">
                <div class="sc-title">💳 Mes Cartes</div>
                <a class="sc-link">+ Ajouter</a>
              </div>
              <div class="card-item">
                <div class="card-mini">VISA</div>
                <div style="flex: 1">
                  <div class="ci-name">Carte Principale</div>
                  <div class="ci-num">•••• 4821</div>
                </div>
                <span class="ci-st st-on">Active</span>
              </div>
              <div class="card-item">
                <div class="card-mini" style="
                      background: linear-gradient(135deg, #1a0a30, #3a1a6f);
                    ">
                  MC
                </div>
                <div style="flex: 1">
                  <div class="ci-name">Carte Virtuelle</div>
                  <div class="ci-num">•••• 7392</div>
                </div>
                <span class="ci-st st-on">Active</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CARTES ═══ -->
      <div class="page" id="page-cartes">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem">
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">💳 Mes Cartes Bancaires</div>
              <button onclick="openModal('new-card')" style="
                    background: linear-gradient(
                      120deg,
                      var(--gold),
                      var(--gold2)
                    );
                    border: none;
                    border-radius: 8px;
                    padding: 5px 14px;
                    font-family: var(--fh);
                    font-size: 0.72rem;
                    font-weight: 700;
                    color: var(--navy);
                    cursor: pointer;
                  ">
                + Nouvelle carte
              </button>
            </div>
            <div class="card-item">
              <div class="card-mini">VISA</div>
              <div style="flex: 1">
                <div class="ci-name">Carte Principale — Visa Gold</div>
                <div class="ci-num">•••• •••• •••• 4821 · Exp 09/28</div>
                <div style="
                      font-size: 0.68rem;
                      color: var(--muted);
                      margin-top: 1px;
                    ">
                  Plafond : 2 000 TND/jour
                </div>
              </div>
              <span class="ci-st st-on">Active</span>
            </div>
            <div class="card-item">
              <div class="card-mini" style="background: linear-gradient(135deg, #1a0a30, #3a1a6f)">
                MC
              </div>
              <div style="flex: 1">
                <div class="ci-name">Carte Virtuelle — Mastercard</div>
                <div class="ci-num">•••• •••• •••• 7392 · Exp 12/27</div>
                <div style="
                      font-size: 0.68rem;
                      color: var(--muted);
                      margin-top: 1px;
                    ">
                  Plafond : 500 TND/jour
                </div>
              </div>
              <span class="ci-st st-on">Active</span>
            </div>
            <div class="card-item">
              <div class="card-mini" style="background: linear-gradient(135deg, #1a1a1a, #333)">
                INT
              </div>
              <div style="flex: 1">
                <div class="ci-name">Carte Internationale</div>
                <div class="ci-num">•••• •••• •••• 2241 · Exp 06/26</div>
                <div style="
                      font-size: 0.68rem;
                      color: var(--muted);
                      margin-top: 1px;
                    ">
                  Plafond : 1 000 TND/jour
                </div>
              </div>
              <span class="ci-st st-off">Bloquée</span>
            </div>
          </div>
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">📋 Dernières opérations</div>
            </div>
            <div style="
                  padding: 2rem;
                  text-align: center;
                  color: var(--muted);
                  font-size: 0.8rem;
                ">
              Aucune opération récente
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ RÉCLAMATIONS ═══ -->
      <div class="page" id="page-reclamations">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem">
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">📣 Mes Réclamations</div>
              <button onclick="openModal('reclamation')" style="
                    background: linear-gradient(120deg, var(--violet), #7c3aed);
                    border: none;
                    border-radius: 8px;
                    padding: 5px 14px;
                    font-family: var(--fh);
                    font-size: 0.72rem;
                    font-weight: 700;
                    color: #fff;
                    cursor: pointer;
                  ">
                + Nouvelle
              </button>
            </div>
            <div style="
                  padding: 2rem;
                  text-align: center;
                  color: var(--muted);
                  font-size: 0.8rem;
                ">
              Aucune réclamation
            </div>
          </div>
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">💬 Chat support</div>
            </div>
            <div class="chat-wrap">
              <div style="
                    padding: 1.5rem;
                    text-align: center;
                    color: var(--muted);
                    font-size: 0.78rem;
                  ">
                Sélectionnez une réclamation pour voir la conversation
              </div>
            </div>
            <div class="chat-input-row">
              <input class="chat-inp" placeholder="Votre message..." />
              <button class="chat-send" onclick="sendMsg()">↗</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CRÉDITS ═══ -->
      <div class="page" id="page-credits">
        <div class="two-col">
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">📈 Mes Crédits en cours</div>
              <span style="
                    font-size: 0.65rem;
                    color: var(--muted);
                    background: var(--card2);
                    padding: 2px 8px;
                    border-radius: 5px;
                  ">Entité : DemandeCredit</span>
            </div>
            <!-- DemandeCredit active -->
            <div style="
                  background: var(--card2);
                  border-radius: 12px;
                  padding: 1rem;
                  margin-bottom: 0.7rem;
                  border: 1px solid rgba(0, 229, 160, 0.12);
                ">
              <div style="
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    margin-bottom: 0.6rem;
                  ">
                <div>
                  <div style="font-size: 0.85rem; font-weight: 700">
                    Crédit en cours
                  </div>
                  <div style="
                        font-size: 0.7rem;
                        color: var(--muted);
                        margin-top: 2px;
                      ">
                    — TND · — mois · —%/an
                  </div>
                </div>
                <span style="
                      font-size: 0.62rem;
                      background: rgba(0, 229, 160, 0.12);
                      color: var(--emerald);
                      padding: 2px 8px;
                      border-radius: 5px;
                      font-weight: 700;
                    ">Approuvé</span>
              </div>
              <div style="
                    margin-bottom: 0.5rem;
                    height: 5px;
                    background: var(--navy3);
                    border-radius: 99px;
                    overflow: hidden;
                  ">
                <div style="
                      width: 42%;
                      height: 100%;
                      background: linear-gradient(
                        90deg,
                        var(--emerald),
                        #00aa77
                      );
                      border-radius: 99px;
                    "></div>
              </div>
              <div style="
                    font-size: 0.68rem;
                    color: var(--muted);
                    margin-bottom: 0.8rem;
                  ">
                — / — mensualités payées · Reste : — TND
              </div>
              <!-- Garantie liée -->
              <div style="
                    background: var(--navy3);
                    border-radius: 9px;
                    padding: 0.65rem 0.8rem;
                    border-left: 3px solid var(--gold);
                  ">
                <div style="
                      font-size: 0.65rem;
                      color: var(--gold);
                      font-weight: 700;
                      text-transform: uppercase;
                      letter-spacing: 0.07em;
                      margin-bottom: 0.35rem;
                    ">
                  🔒 Entité : Garantie associée
                </div>
                <div style="display: flex; align-items: center; gap: 8px">
                  <div style="font-size: 1rem">🚗</div>
                  <div>
                    <div style="font-size: 0.78rem; font-weight: 600">
                      Bien en garantie
                    </div>
                    <div style="font-size: 0.66rem; color: var(--muted)">
                      Document fourni · Valeur estimée : — TND
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="sc-hd" style="margin-top: 1rem">
              <div class="sc-title">📅 Prochaines échéances</div>
            </div>
            <div class="tx-item">
              <div class="tx-ico" style="background: rgba(255, 77, 109, 0.08)">
                📅
              </div>
              <div style="flex: 1">
                <div class="tx-name">Mensualité Crédit</div>
                <div class="tx-date">——</div>
              </div>
              <div class="tx-amt" style="color: var(--rose)">— TND</div>
            </div>
            <div class="tx-item">
              <div class="tx-ico" style="background: rgba(240, 180, 41, 0.08)">
                📅
              </div>
              <div style="flex: 1">
                <div class="tx-name">Mensualité Crédit</div>
                <div class="tx-date">——</div>
              </div>
              <div class="tx-amt" style="color: var(--muted)">— TND</div>
            </div>
          </div>
          <div class="sc cr-sim">
            <div class="sc-title" style="margin-bottom: 1rem">
              🧮 Simulateur de Crédit
            </div>
            <div class="slider-row">
              <div class="sl-lbl">
                <span>Montant</span><span id="lv">20 000 TND</span>
              </div>
              <input type="range" min="1000" max="100000" step="1000" value="20000" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl">
                <span>Durée</span><span id="dv">36 mois</span>
              </div>
              <input type="range" min="6" max="84" step="6" value="36" oninput="calcCredit()" />
            </div>
            <div class="slider-row">
              <div class="sl-lbl">
                <span>Taux annuel</span><span id="rv">8.5%</span>
              </div>
              <input type="range" min="5" max="20" step="0.5" value="8.5" oninput="calcCredit()" />
            </div>
            <div class="cr-result">
              <div style="font-size: 0.72rem; color: var(--muted)">
                Mensualité estimée
              </div>
              <div class="cr-monthly" id="mp">631 TND</div>
              <div class="cr-detail" id="cd">
                Total : 22 716 TND · Coût : 2 716 TND
              </div>
            </div>
            <!-- Types de garanties acceptées -->
            <div style="margin-top: 0.8rem; margin-bottom: 0.6rem">
              <div style="
                    font-size: 0.7rem;
                    color: var(--muted);
                    margin-bottom: 0.4rem;
                    font-weight: 600;
                  ">
                🔒 Garanties acceptées (Entité : Garantie)
              </div>
              <div style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 0.4rem;
                  ">
                <div style="
                      background: var(--navy3);
                      border-radius: 8px;
                      padding: 0.45rem 0.65rem;
                      font-size: 0.68rem;
                      display: flex;
                      align-items: center;
                      gap: 5px;
                    ">
                  <span>🚗</span> Carte grise
                </div>
                <div style="
                      background: var(--navy3);
                      border-radius: 8px;
                      padding: 0.45rem 0.65rem;
                      font-size: 0.68rem;
                      display: flex;
                      align-items: center;
                      gap: 5px;
                    ">
                  <span>🏠</span> Titre propriété
                </div>
                <div style="
                      background: var(--navy3);
                      border-radius: 8px;
                      padding: 0.45rem 0.65rem;
                      font-size: 0.68rem;
                      display: flex;
                      align-items: center;
                      gap: 5px;
                    ">
                  <span>📜</span> Acte notarié
                </div>
                <div style="
                      background: var(--navy3);
                      border-radius: 8px;
                      padding: 0.45rem 0.65rem;
                      font-size: 0.68rem;
                      display: flex;
                      align-items: center;
                      gap: 5px;
                    ">
                  <span>🤝</span> Garant tiers
                </div>
              </div>
            </div>
            <button class="apply-btn" onclick="openModal('credit')">
              Faire ma demande →
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ PLACEHOLDER PAGES ═══ -->
      <div class="page" id="page-comptes">
        <div style="
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 1rem;
              margin-bottom: 1rem;
            ">
          <!-- TypeCompte -->
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">🗂️ Types de Compte</div>
              <span style="
                    font-size: 0.68rem;
                    color: var(--muted);
                    background: var(--card2);
                    padding: 2px 8px;
                    border-radius: 5px;
                  ">Entité : TypeCompte</span>
            </div>
            <div style="
                  display: grid;
                  grid-template-columns: 1fr 1fr 1fr;
                  gap: 0.6rem;
                  margin-bottom: 0.6rem;
                ">
              <div style="
                    background: linear-gradient(
                      135deg,
                      rgba(240, 180, 41, 0.12),
                      rgba(240, 180, 41, 0.04)
                    );
                    border: 1px solid rgba(240, 180, 41, 0.25);
                    border-radius: 12px;
                    padding: 0.9rem;
                    text-align: center;
                  ">
                <div style="font-size: 1.4rem; margin-bottom: 0.4rem">🏧</div>
                <div style="
                      font-size: 0.78rem;
                      font-weight: 700;
                      color: var(--gold);
                    ">
                  Courant
                </div>
                <div style="
                      font-size: 0.62rem;
                      color: var(--muted);
                      margin-top: 3px;
                    ">
                  Dépenses quotidiennes
                </div>
              </div>
              <div style="
                    background: linear-gradient(
                      135deg,
                      rgba(0, 229, 160, 0.1),
                      rgba(0, 229, 160, 0.03)
                    );
                    border: 1px solid rgba(0, 229, 160, 0.2);
                    border-radius: 12px;
                    padding: 0.9rem;
                    text-align: center;
                  ">
                <div style="font-size: 1.4rem; margin-bottom: 0.4rem">💰</div>
                <div style="
                      font-size: 0.78rem;
                      font-weight: 700;
                      color: var(--emerald);
                    ">
                  Épargne
                </div>
                <div style="
                      font-size: 0.62rem;
                      color: var(--muted);
                      margin-top: 3px;
                    ">
                  Mise de côté
                </div>
              </div>
              <div style="
                    background: linear-gradient(
                      135deg,
                      rgba(139, 92, 246, 0.1),
                      rgba(139, 92, 246, 0.03)
                    );
                    border: 1px solid rgba(139, 92, 246, 0.2);
                    border-radius: 12px;
                    padding: 0.9rem;
                    text-align: center;
                  ">
                <div style="font-size: 1.4rem; margin-bottom: 0.4rem">🏢</div>
                <div style="
                      font-size: 0.78rem;
                      font-weight: 700;
                      color: var(--violet);
                    ">
                  Professionnel
                </div>
                <div style="
                      font-size: 0.62rem;
                      color: var(--muted);
                      margin-top: 3px;
                    ">
                  Entreprise
                </div>
              </div>
            </div>
            <div style="
                  background: var(--card2);
                  border-radius: 10px;
                  padding: 0.7rem 0.9rem;
                  font-size: 0.72rem;
                  color: var(--muted);
                ">
              <span style="color: var(--cyan); font-weight: 600">TypeCompte</span>
              définit la catégorie et les règles associées à chaque compte
              (plafonds, taux d'intérêt, éligibilité).
            </div>
          </div>
          <!-- CompteBancaire résumé -->
          <div class="sc">
            <div class="sc-hd">
              <div class="sc-title">📊 Solde Total</div>
            </div>
            <div style="text-align: center; padding: 0.8rem 0 1rem">
              <div style="
                    font-size: 0.7rem;
                    color: var(--muted);
                    text-transform: uppercase;
                    letter-spacing: 0.1em;
                  ">
                Tous comptes confondus
              </div>
              <div style="
                    font-family: var(--fh);
                    font-size: 2.4rem;
                    font-weight: 700;
                    color: var(--gold);
                    margin-top: 0.3rem;
                  ">
                — TND
              </div>
              <div style="
                    display: flex;
                    justify-content: center;
                    gap: 1.2rem;
                    margin-top: 0.7rem;
                  ">
                <div style="text-align: center">
                  <div style="
                        font-size: 0.82rem;
                        font-weight: 600;
                        color: var(--emerald);
                      ">
                    — TND
                  </div>
                  <div style="font-size: 0.62rem; color: var(--muted)">
                    Entrées ce mois
                  </div>
                </div>
                <div style="width: 1px; background: var(--border)"></div>
                <div style="text-align: center">
                  <div style="
                        font-size: 0.82rem;
                        font-weight: 600;
                        color: var(--rose);
                      ">
                    — TND
                  </div>
                  <div style="font-size: 0.62rem; color: var(--muted)">
                    Sorties ce mois
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- CompteBancaire — liste détaillée -->
        <div class="sc">
          <div class="sc-hd">
            <div class="sc-title">🏦 Mes Comptes Bancaires</div>
            <span style="
                  font-size: 0.68rem;
                  color: var(--muted);
                  background: var(--card2);
                  padding: 2px 8px;
                  border-radius: 5px;
                ">Entité : CompteBancaire</span>
          </div>
          <!-- Compte Courant -->
          <div style="
                background: var(--card2);
                border-radius: 14px;
                padding: 1rem;
                margin-bottom: 0.8rem;
                border: 1px solid rgba(240, 180, 41, 0.12);
              ">
            <div style="
                  display: flex;
                  align-items: center;
                  justify-content: space-between;
                  margin-bottom: 0.7rem;
                ">
              <div style="display: flex; align-items: center; gap: 9px">
                <div style="
                      width: 38px;
                      height: 38px;
                      border-radius: 10px;
                      background: linear-gradient(
                        135deg,
                        rgba(240, 180, 41, 0.2),
                        rgba(240, 180, 41, 0.05)
                      );
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      font-size: 1.1rem;
                    ">
                  🏧
                </div>
                <div>
                  <div style="font-size: 0.85rem; font-weight: 700">
                    Compte Courant
                  </div>
                  <div style="
                        font-size: 0.65rem;
                        color: var(--gold);
                        font-weight: 600;
                        margin-top: 1px;
                      ">
                    TypeCompte : Courant
                  </div>
                </div>
              </div>
              <div style="text-align: right">
                <div style="
                      font-family: var(--fh);
                      font-size: 1.2rem;
                      font-weight: 700;
                      color: var(--gold);
                    ">
                  — TND
                </div>
                <span class="ci-st st-on" style="font-size: 0.6rem">Actif</span>
              </div>
            </div>
            <div style="
                  display: grid;
                  grid-template-columns: repeat(3, 1fr);
                  gap: 0.5rem;
                  font-size: 0.68rem;
                ">
              <div style="color: var(--muted)">
                IBAN
                <div style="
                      color: var(--text);
                      font-size: 0.65rem;
                      margin-top: 1px;
                      font-family: monospace;
                    ">
                  TN59 —— —— ——
                </div>
              </div>
              <div style="color: var(--muted)">
                RIB
                <div style="
                      color: var(--text);
                      font-size: 0.65rem;
                      margin-top: 1px;
                      font-family: monospace;
                    ">
                  ——————
                </div>
              </div>
              <div style="color: var(--muted)">
                Ouvert le
                <div style="color: var(--text); margin-top: 1px">
                  ——/——/——
                </div>
              </div>
            </div>
          </div>
          <!-- Compte Épargne -->
          <div style="
                background: var(--card2);
                border-radius: 14px;
                padding: 1rem;
                margin-bottom: 0.8rem;
                border: 1px solid rgba(0, 229, 160, 0.1);
              ">
            <div style="
                  display: flex;
                  align-items: center;
                  justify-content: space-between;
                  margin-bottom: 0.7rem;
                ">
              <div style="display: flex; align-items: center; gap: 9px">
                <div style="
                      width: 38px;
                      height: 38px;
                      border-radius: 10px;
                      background: linear-gradient(
                        135deg,
                        rgba(0, 229, 160, 0.15),
                        rgba(0, 229, 160, 0.04)
                      );
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      font-size: 1.1rem;
                    ">
                  💰
                </div>
                <div>
                  <div style="font-size: 0.85rem; font-weight: 700">
                    Compte Épargne
                  </div>
                  <div style="
                        font-size: 0.65rem;
                        color: var(--emerald);
                        font-weight: 600;
                        margin-top: 1px;
                      ">
                    TypeCompte : Épargne · 4.5% / an
                  </div>
                </div>
              </div>
              <div style="text-align: right">
                <div style="
                      font-family: var(--fh);
                      font-size: 1.2rem;
                      font-weight: 700;
                      color: var(--emerald);
                    ">
                  — TND
                </div>
                <span class="ci-st st-on" style="font-size: 0.6rem">Actif</span>
              </div>
            </div>
            <div style="
                  display: grid;
                  grid-template-columns: repeat(3, 1fr);
                  gap: 0.5rem;
                  font-size: 0.68rem;
                ">
              <div style="color: var(--muted)">
                IBAN
                <div style="
                      color: var(--text);
                      font-size: 0.65rem;
                      margin-top: 1px;
                      font-family: monospace;
                    ">
                  TN59 —— —— ——
                </div>
              </div>
              <div style="color: var(--muted)">
                Intérêts perçus
                <div style="
                      color: var(--emerald);
                      margin-top: 1px;
                      font-weight: 600;
                    ">
                  — TND
                </div>
              </div>
              <div style="color: var(--muted)">
                Ouvert le
                <div style="color: var(--text); margin-top: 1px">
                  ——/——/——
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="page" id="page-virements">
        <div class="sc">
          <div class="sc-title" style="margin-bottom: 1rem">
            💸 Nouveau Virement
          </div>
          <div class="form-group">
            <label class="form-label">Compte source</label><select class="form-select">
              <option>Compte Courant</option>
              <option>Compte Épargne</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">IBAN bénéficiaire</label><input class="form-input" placeholder="TN59..." />
          </div>
          <div class="form-group">
            <label class="form-label">Montant (TND)</label><input class="form-input" type="number" placeholder="0.00" />
          </div>
          <div class="form-group">
            <label class="form-label">Motif</label><input class="form-input" placeholder="Loyer, Remboursement..." />
          </div>
          <button class="btn-primary">Confirmer le virement</button>
        </div>
      </div>
      <div class="page" id="page-profil">
        <div class="sc">
          <div class="sc-title" style="margin-bottom: 1rem">
            👤 Mon Profil
          </div>
          <div class="tx-item">
            <div style="flex: 1">
              <div class="tx-name">Nom Prénom</div>
              <div class="tx-date">
                <a href="/cdn-cgi/l/email-protection" class="__cf_email__"
                  data-cfemail="583930353d3c7633392a3135183d35393134763b3735">[email&#160;protected]</a>
              </div>
            </div>
            <span class="claim-st cs-closed">KYC Vérifié</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODALS -->
  <div class="modal-ov" id="modal-virement">
    <div class="modal">
      <div class="modal-hd">
        <div class="modal-title">💸 Nouveau Virement</div>
        <button class="modal-close" onclick="closeModal('virement')">
          ✕
        </button>
      </div>
      <div class="form-group">
        <label class="form-label">Bénéficiaire</label><select class="form-select">
          <option>Sonia Ben Ali — •••• 2290</option>
          <option>Loyer Studio — •••• 8821</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Montant (TND)</label><input class="form-input" type="number" placeholder="0.00" />
      </div>
      <div class="form-group">
        <label class="form-label">Motif</label><input class="form-input" placeholder="Motif du virement..." />
      </div>
      <button class="btn-primary">Confirmer</button><button class="btn-secondary" onclick="closeModal('virement')">
        Annuler
      </button>
    </div>
  </div>
  <div class="modal-ov" id="modal-credit">
    <div class="modal">
      <div class="modal-hd">
        <div class="modal-title">📈 Demande de Crédit</div>
        <button class="modal-close" onclick="closeModal('credit')">✕</button>
      </div>
      <div style="
            font-size: 0.65rem;
            color: var(--muted);
            background: var(--card2);
            border-radius: 6px;
            padding: 5px 9px;
            margin-bottom: 0.9rem;
          ">
        Entités : <span style="color: var(--gold)">DemandeCredit</span> +
        <span style="color: var(--cyan)">Garantie</span>
      </div>
      <div class="form-group">
        <label class="form-label">Type de crédit</label><select class="form-select">
          <option>Crédit Auto</option>
          <option>Crédit Immobilier</option>
          <option>Crédit Personnel</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Montant (TND)</label><input class="form-input" type="number" placeholder="20 000" />
      </div>
      <div class="form-group">
        <label class="form-label">Durée</label><select class="form-select">
          <option>24 mois</option>
          <option selected>36 mois</option>
          <option>48 mois</option>
          <option>60 mois</option>
        </select>
      </div>
      <div style="border-top: 1px solid var(--border); margin: 0.8rem 0 0.9rem"></div>
      <div style="
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--cyan);
            margin-bottom: 0.7rem;
          ">
        🔒 Garantie (obligatoire)
      </div>
      <div class="form-group">
        <label class="form-label">Type de garantie</label><select class="form-select" id="sel-garantie"
          onchange="toggleGarantie()">
          <option value="">— Choisir —</option>
          <option value="voiture">🚗 Véhicule (carte grise)</option>
          <option value="appart">🏠 Appartement (titre propriété)</option>
          <option value="terrain">📜 Terrain (acte notarié)</option>
          <option value="garant">🤝 Garant tiers</option>
        </select>
      </div>
      <div id="garantie-detail" style="display: none">
        <div class="form-group">
          <label class="form-label" id="garantie-lbl">Document justificatif</label><input class="form-input"
            id="garantie-inp" placeholder="Référence ou description..." />
        </div>
        <div class="form-group">
          <label class="form-label">Valeur estimée (TND)</label><input class="form-input" type="number"
            placeholder="25 000" />
        </div>
      </div>
      <button class="btn-primary">Soumettre ma demande</button><button class="btn-secondary"
        onclick="closeModal('credit')">
        Annuler
      </button>
    </div>
  </div>
  <div class="modal-ov" id="modal-reclamation">
    <div class="modal">
      <div class="modal-hd">
        <div class="modal-title">📣 Nouvelle Réclamation</div>
        <button class="modal-close" onclick="closeModal('reclamation')">
          ✕
        </button>
      </div>
      <div class="claim-form">
        <div class="fg">
          <label>Type de réclamation</label><select>
            <option>Problème de virement</option>
            <option>Frais incorrects</option>
            <option>Carte bloquée</option>
            <option>Crédit</option>
            <option>Autre</option>
          </select>
        </div>
        <div class="fg">
          <label>Titre</label><input placeholder="Résumé de votre problème..." />
        </div>
        <div class="fg">
          <label>Description détaillée</label><textarea placeholder="Décrivez votre problème en détail..."></textarea>
        </div>
      </div>
      <button class="btn-primary" onclick="closeModal('reclamation')">
        Soumettre la réclamation</button><button class="btn-secondary" onclick="closeModal('reclamation')">
        Annuler
      </button>
    </div>
  </div>
  <div class="modal-ov" id="modal-new-card">
    <div class="modal">
      <div class="modal-hd">
        <div class="modal-title">💳 Nouvelle Carte</div>
        <button class="modal-close" onclick="closeModal('new-card')">
          ✕
        </button>
      </div>
      <div class="form-group">
        <label class="form-label">Type de carte</label><select class="form-select">
          <option>Carte Virtuelle (instantanée)</option>
          <option>Carte Physique (3-5 jours)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Compte lié</label><select class="form-select">
          <option>Compte Courant</option>
          <option>Compte Épargne</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Plafond journalier (TND)</label><input class="form-input" type="number" value="500" />
      </div>
      <button class="btn-primary" onclick="closeModal('new-card')">
        Créer la carte</button><button class="btn-secondary" onclick="closeModal('new-card')">
        Annuler
      </button>
    </div>
  </div>

  <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
  <script>
    function showPage(id, el) {
      document
        .querySelectorAll(".page")
        .forEach((p) => p.classList.remove("on"));
      document.getElementById("page-" + id).classList.add("on");
      document
        .querySelectorAll(".sb-item")
        .forEach((s) => s.classList.remove("on"));
      if (el) el.classList.add("on");
      const titles = {
        dashboard: "Tableau de bord",
        comptes: "Mes Comptes",
        virements: "Virements & Paiements",
        credits: "Crédits & Épargne",
        cartes: "Mes Cartes",
        reclamations: "Réclamations & Messagerie",
        profil: "Mon Profil",
      };
      document.getElementById("page-title").textContent = titles[id] || id;
    }
    function openModal(id) {
      document.getElementById("modal-" + id).classList.add("open");
    }
    function closeModal(id) {
      document.getElementById("modal-" + id).classList.remove("open");
    }
    document.querySelectorAll(".modal-ov").forEach((m) =>
      m.addEventListener("click", (e) => {
        if (e.target === m) m.classList.remove("open");
      })
    );
    function calcCredit() {
      const ranges = document.querySelectorAll("input[type=range]");
      const l = parseInt(ranges[0].value),
        d = parseInt(ranges[1].value),
        r = parseFloat(ranges[2].value);
      document.getElementById("lv").textContent = l.toLocaleString() + " TND";
      document.getElementById("dv").textContent = d + " mois";
      document.getElementById("rv").textContent = r + "%";
      const mr = r / 100 / 12;
      const mp = Math.round(
        (l * (mr * Math.pow(1 + mr, d))) / (Math.pow(1 + mr, d) - 1)
      );
      const total = Math.round(mp * d);
      document.getElementById("mp").textContent =
        mp.toLocaleString() + " TND";
      document.getElementById("cd").textContent =
        "Total : " +
        total.toLocaleString() +
        " TND · Coût : " +
        (total - l).toLocaleString() +
        " TND";
    }
    function toggleGarantie() {
      const v = document.getElementById("sel-garantie").value;
      const d = document.getElementById("garantie-detail");
      const l = document.getElementById("garantie-lbl");
      const i = document.getElementById("garantie-inp");
      if (!v) {
        d.style.display = "none";
        return;
      }
      d.style.display = "block";
      const map = {
        voiture: ["N° carte grise", "Ex: 123456TN"],
        appart: ["N° titre de propriété", "Ex: TP-2021-88821"],
        terrain: ["Réf. acte notarié", "Ex: AN-2019-4421"],
        garant: ["Nom du garant", "Prénom Nom"],
      };
      l.textContent = map[v][0];
      i.placeholder = map[v][1];
    }
    function sendMsg() {
      const inp = document.querySelector(".chat-inp");
      if (!inp.value.trim()) return;
      const div = document.createElement("div");
      div.className = "chat-msg me";
      div.innerHTML = `<div class="chat-av" style="background:linear-gradient(135deg,var(--gold),var(--gold2));color:var(--navy);">AK</div><div class="chat-bubble">${inp.value}</div>`;
      document.querySelector(".chat-wrap").appendChild(div);
      inp.value = "";
      document.querySelector(".chat-wrap").scrollTop = 9999;
    }
  </script>
</body>

</html>