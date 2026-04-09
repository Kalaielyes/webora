<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>LegalFinAI — Back Office Admin</title>
  <link rel="stylesheet" href="creditttt.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap"
    rel="stylesheet" />
</head>

<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-top">
      <div class="sb-brand">
        🏦 Legal<span class="hl">Fin</span>AI
        <span class="sb-env">ADMIN</span>
      </div>
    </div>
    <div class="sb-admin">
      <div class="sb-av">SA</div>
      <div>
        <div class="sb-aname">Administrateur</div>
        <div class="sb-arole">Administrateur Système</div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-sec">Vue d'ensemble</div>
      <a class="sb-item on" onclick="showPage('dashboard',this)"><span class="sb-ico">📊</span> Dashboard</a>
      <a class="sb-item" onclick="showPage('analytics',this)"><span class="sb-ico">📈</span> Analytiques</a>
      <div class="sb-sec">Gestion</div>
      <a class="sb-item" onclick="showPage('users',this)"><span class="sb-ico">👥</span> Utilisateurs
        <span class="sb-badge ba">12 KYC</span></a>
      <a class="sb-item" onclick="showPage('comptes',this)"><span class="sb-ico">🏦</span> Comptes</a>
      <a class="sb-item" onclick="showPage('transactions',this)"><span class="sb-ico">💸</span> Transactions</a>
      <a class="sb-item" onclick="showPage('credits',this)"><span class="sb-ico">📈</span> Crédits
        <span class="sb-badge ba">8 dossiers</span></a>
      <a class="sb-item" onclick="showPage('cartes',this)"><span class="sb-ico">💳</span> Cartes</a>
      <div class="sb-sec">Support</div>
      <a class="sb-item" onclick="showPage('reclamations',this)"><span class="sb-ico">📣</span> Réclamations
        <span class="sb-badge br">5</span></a>
      <div class="sb-sec">Système</div>
      <a class="sb-item" onclick="showPage('audit',this)"><span class="sb-ico">🔍</span> Audit Trail</a>
      <a class="sb-item"><span class="sb-ico">⚙️</span> Configuration</a>
    </nav>
    <div class="sb-status">
      <div class="st-row">
        <span class="std" style="background: var(--emerald)"></span>API Core —
        Opérationnel
      </div>
      <div class="st-row">
        <span class="std" style="background: var(--emerald)"></span>Base de
        données — OK
      </div>
      <div class="st-row">
        <span class="std" style="background: var(--amber)"></span>Email
        Service — Latence
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-left">
        <div class="pt" id="page-title">Dashboard Administration</div>
        <div class="bc" id="page-bc">Admin › Vue d'ensemble</div>
      </div>
      <div class="tb-right">
        <div class="live"><span class="ldot"></span> Live</div>
        <button class="tb-btn">📥 Exporter</button>
        <button class="tb-btn al-btn" onclick="alert('5 alertes réclamations actives')">
          🔔 <span class="al-cnt">5</span>
        </button>
        <button class="tb-btn tb-btn-p" onclick="openModal()">
          + Nouvel admin
        </button>
        <!-- ═══ BOUTON BACK-OFFICE ═══ -->
        <a href="/projet_web/back/view/back_credit.php" class="btn-frontoffice" title="Accéder à l'Espace Client">
          👤 Espace Client →
        </a>
      </div>
    </header>

    <div class="content">
      <!-- DASHBOARD -->
      <div class="page on" id="page-dashboard">
        <div class="kpi-grid">
          <div class="kpi kb">
            <div class="kpi-top">
              <span class="kpi-ico">👥</span><span class="kpi-ch ku">↑ +8.3%</span>
            </div>
            <div class="kpi-val" style="color: var(--blue)">—</div>
            <div class="kpi-lbl">Comptes actifs</div>
            <div class="kpi-sub">Aucune donnée</div>
          </div>
          <div class="kpi ke">
            <div class="kpi-top">
              <span class="kpi-ico">💰</span><span class="kpi-ch ku">↑ +12.1%</span>
            </div>
            <div class="kpi-val" style="color: var(--emerald)">— TND</div>
            <div class="kpi-lbl">Volume transactions</div>
            <div class="kpi-sub">Aucune donnée</div>
          </div>
          <div class="kpi ka">
            <div class="kpi-top">
              <span class="kpi-ico">📣</span><span class="kpi-ch kd">↑ +3</span>
            </div>
            <div class="kpi-val" style="color: var(--amber)">—</div>
            <div class="kpi-lbl">Réclamations ouvertes</div>
            <div class="kpi-sub">Aucune donnée</div>
          </div>
          <div class="kpi kr">
            <div class="kpi-top">
              <span class="kpi-ico">💳</span><span class="kpi-ch ku">↑ +5.7%</span>
            </div>
            <div class="kpi-val" style="color: var(--rose)">—</div>
            <div class="kpi-lbl">Demandes de crédit</div>
            <div class="kpi-sub">Aucune donnée</div>
          </div>
        </div>
        <div class="main-grid">
          <div>
            <div class="tabs">
              <div class="tab on">👥 Utilisateurs</div>
              <div class="tab">💸 Transactions</div>
              <div class="tab">📣 Réclamations</div>
            </div>
            <div class="dt-wrap">
              <div class="dt-hd">
                <div class="dt-title">Utilisateurs récents</div>
                <div class="dt-acts">
                  <div class="dt-search">🔍 Rechercher...</div>
                  <button class="tb-btn">⬇ CSV</button>
                </div>
              </div>
              <table>
                <thead>
                  <tr>
                    <th><input type="checkbox" /></th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>KYC</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="7" style="
                          text-align: center;
                          padding: 2rem;
                          color: var(--muted2);
                          font-size: 0.8rem;
                        ">
                      Aucun utilisateur à afficher
                    </td>
                  </tr>
                </tbody>
              </table>
              <div style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0.6rem 0.9rem;
                    border-top: 1px solid var(--border);
                    font-size: 0.7rem;
                    color: var(--muted);
                  ">
                Affichage 0 résultats
                <div style="display: flex; gap: 3px">
                  <button class="tda">‹</button><button class="tda" style="background: var(--blue); color: #fff">
                    1</button><button class="tda">2</button><button class="tda">›</button>
                </div>
              </div>
            </div>
          </div>
          <div>
            <div class="panel">
              <div class="panel-hd">
                <div class="panel-title">📣 Réclamations urgentes</div>
                <span class="badge b-off">5</span>
              </div>
              <div class="panel-body" style="padding: 0.4rem 0.8rem">
                <div style="
                      padding: 1.5rem;
                      text-align: center;
                      color: var(--muted2);
                      font-size: 0.78rem;
                    ">
                  Aucune alerte urgente
                </div>
              </div>
            </div>
            <div class="panel">
              <div class="panel-hd">
                <div class="panel-title">⚡ Performance modules</div>
              </div>
              <div class="panel-body">
                <div class="prog-row">
                  <div class="prog-top">
                    <span>👥 Utilisateurs</span><span style="color: var(--blue)">92%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 92%;
                          background: linear-gradient(
                            90deg,
                            var(--blue),
                            var(--indigo)
                          );
                        "></div>
                  </div>
                </div>
                <div class="prog-row">
                  <div class="prog-top">
                    <span>🏦 Comptes</span><span style="color: var(--cyan)">88%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 88%;
                          background: linear-gradient(
                            90deg,
                            var(--cyan),
                            #0284c7
                          );
                        "></div>
                  </div>
                </div>
                <div class="prog-row">
                  <div class="prog-top">
                    <span>💸 Virements</span><span style="color: var(--emerald)">78%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 78%;
                          background: linear-gradient(
                            90deg,
                            var(--emerald),
                            #059669
                          );
                        "></div>
                  </div>
                </div>
                <div class="prog-row">
                  <div class="prog-top">
                    <span>📈 Crédits</span><span style="color: var(--amber)">65%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 65%;
                          background: linear-gradient(
                            90deg,
                            var(--amber),
                            #d97706
                          );
                        "></div>
                  </div>
                </div>
                <div class="prog-row">
                  <div class="prog-top">
                    <span>📣 Réclamations</span><span style="color: var(--violet)">54%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 54%;
                          background: linear-gradient(
                            90deg,
                            var(--violet),
                            #7c3aed
                          );
                        "></div>
                  </div>
                </div>
                <div class="prog-row">
                  <div class="prog-top">
                    <span>💳 Cartes</span><span style="color: var(--rose)">43%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="
                          width: 43%;
                          background: linear-gradient(
                            90deg,
                            var(--rose),
                            #a32d2d
                          );
                        "></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="panel">
              <div class="panel-hd">
                <div class="panel-title">📊 Stats système</div>
              </div>
              <div class="panel-body" style="padding: 0.3rem 0.8rem">
                <div class="sri">
                  <div class="sri-l">Uptime</div>
                  <div class="sri-v" style="color: var(--emerald)">99.2%</div>
                </div>
                <div class="sri">
                  <div class="sri-l">Temps réponse API</div>
                  <div class="sri-v" style="color: var(--blue)">142ms</div>
                </div>
                <div class="sri">
                  <div class="sri-l">Cartes actives</div>
                  <div class="sri-v">—</div>
                </div>
                <div class="sri">
                  <div class="sri-l">Réclamations/mois</div>
                  <div class="sri-v">—</div>
                </div>
                <div class="sri">
                  <div class="sri-l">CO₂ économisé</div>
                  <div class="sri-v" style="color: var(--emerald)">
                    2.4 kg
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RÉCLAMATIONS ADMIN -->
      <div class="page" id="page-reclamations">
        <div class="claim-grid">
          <div class="claim-kpi">
            <div class="claim-kpi-val" style="color: var(--rose)">—</div>
            <div class="claim-kpi-lbl">Ouvertes</div>
          </div>
          <div class="claim-kpi">
            <div class="claim-kpi-val" style="color: var(--amber)">—</div>
            <div class="claim-kpi-lbl">En cours</div>
          </div>
          <div class="claim-kpi">
            <div class="claim-kpi-val" style="color: var(--emerald)">—</div>
            <div class="claim-kpi-lbl">Résolues ce mois</div>
          </div>
        </div>
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">📣 Toutes les Réclamations</div>
            <div class="dt-acts">
              <div class="dt-search">🔍 Rechercher...</div>
              <select style="
                    background: var(--card2);
                    border: 1px solid var(--border);
                    border-radius: 7px;
                    padding: 0.3rem 0.7rem;
                    font-size: 0.72rem;
                    color: var(--text);
                    outline: none;
                  ">
                <option>Tous les statuts</option>
                <option>Ouvert</option>
                <option>En cours</option>
                <option>Résolu</option>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Client</th>
                <th>Type</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Soumis le</th>
                <th>Conseiller</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="8" style="
                      text-align: center;
                      padding: 2rem;
                      color: var(--muted2);
                      font-size: 0.8rem;
                    ">
                  Aucune réclamation à afficher
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CARTES ADMIN -->
      <div class="page" id="page-cartes">
        <div class="carte-stats">
          <div class="cs-card">
            <div class="cs-val" style="color: var(--blue)">—</div>
            <div class="cs-lbl">Cartes actives</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--emerald)">—</div>
            <div class="cs-lbl">Cartes virtuelles</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--amber)">—</div>
            <div class="cs-lbl">Cartes bloquées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--rose)">—</div>
            <div class="cs-lbl">Suspicion fraude</div>
          </div>
        </div>
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">💳 Gestion des Cartes</div>
            <div class="dt-acts">
              <div class="dt-search">🔍 Rechercher...</div>
              <button class="tb-btn">⬇ CSV</button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>N° Carte</th>
                <th>Titulaire</th>
                <th>Type</th>
                <th>Plafond/jour</th>
                <th>Opérations</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="7" style="
                      text-align: center;
                      padding: 2rem;
                      color: var(--muted2);
                      font-size: 0.8rem;
                    ">
                  Aucune carte à afficher
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- AUDIT -->
      <div class="page" id="page-audit">
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">🔍 Audit Trail — Actions Immutables</div>
            <button class="tb-btn">📥 Export PDF</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>Horodatage</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Entité</th>
                <th>IP</th>
                <th>Résultat</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="6" style="
                      text-align: center;
                      padding: 2rem;
                      color: var(--muted2);
                      font-size: 0.8rem;
                    ">
                  Aucune entrée d'audit
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PLACEHOLDER PAGES -->
      <div class="page" id="page-analytics">
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">📈 Analytiques</div>
          </div>
          <div style="padding: 2rem; text-align: center; color: var(--muted2)">
            Graphiques analytiques — à intégrer avec Chart.js
          </div>
        </div>
      </div>
      <div class="page" id="page-users">
        <div style="font-size: 0.8rem; color: var(--muted2)">
          Voir tableau dashboard
        </div>
      </div>
      <div class="page" id="page-comptes">
        <!-- Stats TypeCompte -->
        <div class="carte-stats" style="margin-bottom: 1rem">
          <div class="cs-card">
            <div class="cs-val" style="color: var(--blue)">—</div>
            <div class="cs-lbl">Comptes Courants</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--emerald)">—</div>
            <div class="cs-lbl">Comptes Épargne</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--violet)">—</div>
            <div class="cs-lbl">Comptes Pro</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--amber)">—</div>
            <div class="cs-lbl">Total actifs</div>
          </div>
        </div>
        <div class="dt-wrap">
          <div class="dt-hd">
            <div style="display: flex; align-items: center; gap: 0.7rem">
              <div class="dt-title">🏦 Gestion des Comptes Bancaires</div>
              <span style="
                    font-size: 0.6rem;
                    background: rgba(59, 130, 246, 0.1);
                    color: var(--blue);
                    padding: 2px 8px;
                    border-radius: 5px;
                    font-weight: 600;
                  ">Entité : CompteBancaire / TypeCompte</span>
            </div>
            <div class="dt-acts">
              <div class="dt-search">🔍 Rechercher...</div>
              <button class="tb-btn">⬇ CSV</button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>N° Compte</th>
                <th>Titulaire</th>
                <th>TypeCompte</th>
                <th>IBAN</th>
                <th>Solde</th>
                <th>Ouverture</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="8" style="
                      text-align: center;
                      padding: 2rem;
                      color: var(--muted2);
                      font-size: 0.8rem;
                    ">
                  Aucun compte à afficher
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="page" id="page-transactions">
        <div class="dt-wrap">
          <div class="dt-hd">
            <div class="dt-title">💸 Toutes les Transactions</div>
          </div>
          <div style="padding: 2rem; text-align: center; color: var(--muted2)">
            Journal complet des transactions
          </div>
        </div>
      </div>
      <div class="page" id="page-credits">
        <!-- KPIs Crédits -->
        <div class="carte-stats" style="margin-bottom: 1rem">
          <div class="cs-card">
            <div class="cs-val" style="color: var(--amber)">—</div>
            <div class="cs-lbl">En attente</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--emerald)">—</div>
            <div class="cs-lbl">Approuvées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--rose)">—</div>
            <div class="cs-lbl">Refusées</div>
          </div>
          <div class="cs-card">
            <div class="cs-val" style="color: var(--blue)">—</div>
            <div class="cs-lbl">Encours total</div>
          </div>
        </div>
        <div class="dt-wrap">
          <div class="dt-hd">
            <div style="display: flex; align-items: center; gap: 0.7rem">
              <div class="dt-title">📈 Dossiers Crédit & Garanties</div>
              <span style="
                    font-size: 0.6rem;
                    background: rgba(59, 130, 246, 0.1);
                    color: var(--blue);
                    padding: 2px 8px;
                    border-radius: 5px;
                    font-weight: 600;
                  ">Entité : DemandeCredit / Garantie</span>
            </div>
            <div class="dt-acts">
              <div class="dt-search">🔍 Rechercher...</div>
              <select style="
                    background: var(--card2);
                    border: 1px solid var(--border);
                    border-radius: 7px;
                    padding: 0.3rem 0.7rem;
                    font-size: 0.72rem;
                    color: var(--text);
                    outline: none;
                  ">
                <option>Tous statuts</option>
                <option>En attente</option>
                <option>Approuvé</option>
                <option>Refusé</option>
              </select>
              <button class="tb-btn">⬇ CSV</button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Client</th>
                <th>Type crédit</th>
                <th>Montant</th>
                <th>Durée</th>
                <th>Garantie</th>
                <th>Document</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="9" style="
                      text-align: center;
                      padding: 2rem;
                      color: var(--muted2);
                      font-size: 0.8rem;
                    ">
                  Aucun dossier crédit à afficher
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL -->
  <div class="modal-ov" id="modal-add">
    <div class="modal">
      <div class="modal-hd">
        <div class="modal-title">+ Nouvel Administrateur</div>
        <button class="modal-close" onclick="closeModal()">✕</button>
      </div>
      <div class="fr">
        <div class="fg">
          <label class="fl">Prénom</label><input class="fi" placeholder="Prénom" />
        </div>
        <div class="fg">
          <label class="fl">Nom</label><input class="fi" placeholder="Nom" />
        </div>
      </div>
      <div class="fg">
        <label class="fl">Email professionnel</label><input class="fi" placeholder="admin@legalfinai.com" />
      </div>
      <div class="fg">
        <label class="fl">Rôle</label><select class="fs">
          <option>Super Administrateur</option>
          <option>Administrateur</option>
          <option>Conseiller</option>
        </select>
      </div>
      <div class="fg">
        <label class="fl">Modules accessibles</label><select class="fs">
          <option>Tous les modules</option>
          <option>Réclamations uniquement</option>
          <option>Crédits & Cartes</option>
        </select>
      </div>
      <button class="btn-p">Créer l'administrateur</button>
      <button class="btn-s" onclick="closeModal()">Annuler</button>
    </div>
  </div>

  <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
  <script>
    function showPage(id, el) {
      document
        .querySelectorAll(".page")
        .forEach((p) => p.classList.remove("on"));
      const pg = document.getElementById("page-" + id);
      if (pg) pg.classList.add("on");
      document
        .querySelectorAll(".sb-item")
        .forEach((s) => s.classList.remove("on"));
      if (el) el.classList.add("on");
      const titles = {
        dashboard: "Dashboard Administration",
        analytics: "Analytiques",
        users: "Gestion Utilisateurs",
        comptes: "Gestion des Comptes",
        transactions: "Toutes les Transactions",
        credits: "Dossiers Crédit",
        cartes: "Gestion des Cartes",
        reclamations: "Réclamations & Support",
        audit: "Audit Trail",
      };
      const bcs = {
        dashboard: "Admin › Vue d'ensemble",
        analytics: "Admin › Analytiques",
        users: "Admin › Utilisateurs",
        comptes: "Admin › Comptes",
        transactions: "Admin › Transactions",
        credits: "Admin › Crédits",
        cartes: "Admin › Cartes",
        reclamations: "Admin › Réclamations",
        audit: "Admin › Audit",
      };
      document.querySelector(".pt").textContent = titles[id] || id;
      document.querySelector(".bc").textContent = bcs[id] || "";
    }
    function openModal() {
      document.getElementById("modal-add").classList.add("open");
    }
    function closeModal() {
      document.getElementById("modal-add").classList.remove("open");
    }
    document.querySelectorAll(".modal-ov").forEach((m) =>
      m.addEventListener("click", (e) => {
        if (e.target === m) m.classList.remove("open");
      })
    );
  </script>
</body>

</html>