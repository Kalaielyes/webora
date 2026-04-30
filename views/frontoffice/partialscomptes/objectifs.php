<?php
/**
 * objectifs.php — INTEGRATED PROJECT STYLE
 * Adaptable to Light/Dark mode, no emojis.
 */
$mesObjectifs = ObjectifController::findAllByUtilisateur($userId);
$formError = $_SESSION['form_error'] ?? null;
unset($_SESSION['form_error']);
?>

<div class="prj-container">
    
    <!-- HEADER -->
    <div class="prj-header">
        <div class="header-main">
            <h1 class="header-title">Objectifs Financiers</h1>
            <p class="header-desc">Planifiez et suivez la progression de vos projets.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button id="btn-enable-notif" style="display:none; background: var(--amber-light); color: var(--amber); border: 1px solid var(--amber); border-radius: 12px; padding: 10px; font-size: 0.75rem; cursor: pointer; font-weight: 700;">
                Activer Notifications PC
            </button>
            <button onclick="document.getElementById('modal-obj').style.display='flex'" class="btn-primary" style="padding: 10px 20px; border-radius: 12px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Nouvel Objectif
            </button>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if ($formError): ?>
        <div class="prj-alert danger">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= htmlspecialchars($formError) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['ok'])): ?>
        <div class="prj-alert success">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            Opération effectuée avec succès.
        </div>
    <?php endif; ?>

    <!-- GRID -->
    <?php if (empty($mesObjectifs)): ?>
        <div class="prj-empty">
            <div class="empty-icon-box">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <h2>Prêt à relever un défi ?</h2>
            <p>Commencez par définir un montant et une échéance pour vos rêves.</p>
        </div>
    <?php else: ?>
        <div class="prj-grid">
            <?php foreach ($mesObjectifs as $obj): 
                $analysis = ObjectifController::analyzeObjectif($obj);
                $isAtteint = ($obj->getStatut() === 'atteint');
                $isRetard = ($analysis['status_hint'] === 'En retard');
                $accent = $isAtteint ? 'var(--green)' : ($isRetard ? 'var(--rose)' : 'var(--blue)');
            ?>
            <div class="prj-card" style="border-top: 4px solid <?= $accent ?>;">
                <div class="card-head">
                    <span class="status-pill" style="background: <?= $accent ?>15; border: 1px solid <?= $accent ?>25; color: <?= $accent ?>;">
                        <?= $analysis['status_hint'] ?>
                    </span>
                    <form action="<?= APP_URL ?>/controllers/ObjectifController.php" method="POST" onsubmit="return confirm('Supprimer cet objectif ?')">
                        <?php Security::csrfInput(); ?>
                        <input type="hidden" name="action" value="delete_objectif">
                        <input type="hidden" name="id_objectif" value="<?= $obj->getIdObjectif() ?>">
                        <button type="submit" class="card-delete-icon">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>

                <h3 class="card-title"><?= htmlspecialchars($obj->getTitre()) ?></h3>
                
                <div class="card-numbers">
                    <div class="num-main sensitive-data"><?= number_format($obj->getMontantActuel(), 2) ?></div>
                    <div class="num-target">sur <span class="sensitive-data"><?= number_format($obj->getMontantObjectif(), 2) ?></span> <?= $obj->getDevise() ?></div>
                </div>

                <div class="prj-progress-wrap">
                    <div class="progress-labels">
                        <span>Progression : <span class="sensitive-data"><?= $analysis['progress_pct'] ?></span>%</span>
                        <span>Objectif temps : <?= $analysis['ideal_pct'] ?>%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?= $analysis['progress_pct'] ?>%; background: <?= $accent ?>;"></div>
                        <div class="progress-marker" style="left: <?= $analysis['ideal_pct'] ?>%;"></div>
                    </div>
                </div>

                <div class="card-foot">
                    <div class="foot-info">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Échéance : <?= date('d/m/y', strtotime($obj->getDateFin())) ?>
                    </div>
                    <?php if (!$isAtteint): ?>
                        <button class="btn-ghost" style="padding: 6px 12px; font-size: 0.7rem; font-weight: 700; color: var(--blue); border-color: var(--blue-border);" onclick="openAlimentModal(<?= $obj->getIdObjectif() ?>, '<?= addslashes($obj->getTitre()) ?>', '<?= $obj->getDevise() ?>')">
                            VERSER
                        </button>
                    <?php else: ?>
                        <div class="attained-label">COMPLÉTÉ</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- CREATE MODAL -->
<div id="modal-obj" class="prj-modal">
    <div class="modal-content">
        <h2 style="font-family: var(--fh); margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            Nouvelle Mission
            <button onclick="document.getElementById('modal-obj').style.display='none'" style="background:none; border:none; cursor:pointer; color:var(--muted);"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </h2>
        <form action="<?= APP_URL ?>/controllers/ObjectifController.php" method="POST" class="prj-form">
            <?php Security::csrfInput(); ?>
            <input type="hidden" name="action" value="add_objectif">
            <div class="form-group">
                <label>Nom du projet</label>
                <input type="text" name="titre" placeholder="Ex: Maison, Voyage..." class="prj-input">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label>Cible</label>
                    <input type="number" name="montant_objectif" step="0.01" class="prj-input">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Devise</label>
                    <select name="devise" class="prj-select">
                        <option value="TND">TND</option>
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date début</label>
                    <input type="date" name="date_debut" value="<?= date('Y-m-d') ?>" class="prj-input">
                </div>
                <div class="form-group">
                    <label>Échéance</label>
                    <input type="date" name="date_fin" class="prj-input">
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:12px; margin-top:10px;">Lancer le projet</button>
        </form>
    </div>
</div>

<!-- FUND MODAL -->
<div id="modal-fund" class="prj-modal">
    <div class="modal-content">
        <h2 id="alim-title" style="font-family: var(--fh); margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            Verser des fonds
            <button onclick="document.getElementById('modal-fund').style.display='none'" style="background:none; border:none; cursor:pointer; color:var(--muted);"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </h2>
        <form action="<?= APP_URL ?>/controllers/ObjectifController.php" method="POST" class="prj-form">
            <?php Security::csrfInput(); ?>
            <input type="hidden" name="action" value="alimenter">
            <input type="hidden" name="id_objectif" id="alim-id">
            <div class="form-group">
                <label>Compte source (Hors épargne)</label>
                <select name="id_compte_source" class="prj-select">
                    <?php foreach ($comptes as $c): if ($c->getStatut()==='actif' && $c->getTypeCompte() !== 'epargne'): ?>
                        <option value="<?= $c->getIdCompte() ?>"><?= ucfirst($c->getTypeCompte()) ?> (<span class="sensitive-data"><?= number_format($c->getSolde(),2) ?></span> <?= $c->getDevise() ?>)</option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Montant en <span id="alim-devise">TND</span></label>
                <input type="number" name="montant" step="0.01" class="prj-input">
                <p style="font-size:0.65rem; color:var(--muted); margin-top:5px;">La conversion se fait automatiquement au taux du jour.</p>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:12px; margin-top:10px;">Confirmer le versement</button>
        </form>
    </div>
</div>

<script>
function openAlimentModal(id, title, devise) {
    document.getElementById('alim-id').value = id;
    document.getElementById('alim-title').firstChild.textContent = "Alimenter : " + title;
    document.getElementById('alim-devise').textContent = devise;
    document.getElementById('modal-fund').style.display = 'flex';
}
window.onclick = function(e){ if(e.target.className==='prj-modal') e.target.style.display='none'; }

// DESKTOP NOTIFICATIONS (PC)
document.addEventListener('DOMContentLoaded', () => {
    const btnNotif = document.getElementById('btn-enable-notif');
    console.log("Notification status:", Notification.permission);

    // Function to show the notification
    const showDesktopNotif = (title, body) => {
        if (Notification.permission === 'granted') {
            const n = new Notification(title, {
                body: body,
                vibrate: [200, 100, 200]
            });
            n.onclick = () => { window.focus(); n.close(); };
        }
    };

    // Update button visibility
    if (Notification.permission === 'default') {
        btnNotif.style.display = 'block';
    }

    btnNotif.onclick = () => {
        Notification.requestPermission().then(p => {
            if (p !== 'default') btnNotif.style.display = 'none';
        });
    };
});
</script>

<style>
.prj-container { display: flex; flex-direction: column; gap: 1.5rem; }
.prj-header { display: flex; justify-content: space-between; align-items: flex-start; }
.header-title { font-family: var(--fh); font-size: 1.25rem; font-weight: 800; color: var(--text); }
.header-desc { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }

.prj-alert { padding: 12px 1rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.prj-alert.danger { background: var(--rose-light); color: var(--rose); border: 1px solid rgba(244, 63, 94, 0.2); }
.prj-alert.success { background: var(--green-light); color: var(--green); border: 1px solid rgba(34, 197, 94, 0.2); }

.prj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }

.prj-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--r); padding: 1.4rem; display: flex; flex-direction: column; gap: 1.2rem; transition: transform 0.2s; }
.prj-card:hover { transform: translateY(-3px); border-color: var(--border2); }

.card-head { display: flex; justify-content: space-between; align-items: center; }
.card-delete-icon { background: none; border: none; cursor: pointer; color: var(--muted2); transition: 0.2s; display: flex; align-items: center; justify-content: center; padding: 4px; border-radius: 6px; }
.card-delete-icon:hover { background: var(--rose-light); color: var(--rose); }

.card-title { font-family: var(--fh); font-size: 1rem; font-weight: 700; color: var(--text); margin: 0; }

.card-numbers { display: flex; flex-direction: column; gap: 2px; }
.num-main { font-family: var(--fm); font-size: 1.6rem; font-weight: 700; color: var(--text); }
.num-target { font-size: 0.72rem; color: var(--muted); font-weight: 600; }

.prj-progress-wrap { display: flex; flex-direction: column; gap: 8px; }
.progress-labels { display: flex; justify-content: space-between; font-size: 0.65rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }
.progress-track { height: 8px; background: var(--bg3); border-radius: 4px; position: relative; overflow: hidden; border: 1px solid var(--border); }
.progress-fill { height: 100%; border-radius: 4px; transition: width 0.8s ease-out; }
.progress-marker { position: absolute; height: 100%; width: 2px; background: var(--text); top: 0; opacity: 0.5; z-index: 2; }

.card-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 0.4rem; }
.foot-info { font-size: 0.7rem; color: var(--muted); font-weight: 600; display: flex; align-items: center; gap: 6px; }

.attained-label { font-size: 0.65rem; font-weight: 800; color: var(--green); background: var(--green-light); padding: 4px 10px; border-radius: 6px; }

/* MODAL */
.prj-modal { position: fixed; inset: 0; background: var(--glass); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
.modal-content { background: var(--bg2); border: 1px solid var(--border); border-radius: 18px; width: 100%; max-width: 440px; padding: 2rem; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }

.prj-form { display: flex; flex-direction: column; gap: 1.2rem; }
.form-row { display: flex; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 0.7rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.02em; }

.prj-input, .prj-select { background: var(--bg3); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; outline: none; font-family: inherit; }
.prj-input:focus, .prj-select:focus { border-color: var(--blue); }

.prj-empty { text-align: center; padding: 4rem 2rem; background: var(--bg2); border: 1px dashed var(--border); border-radius: 24px; color: var(--muted); }
.empty-icon-box { margin-bottom: 1.5rem; color: var(--muted2); opacity: 0.3; }
.prj-empty h2 { color: var(--text); font-family: var(--fh); font-size: 1.1rem; margin-bottom: 0.5rem; }
</style>
