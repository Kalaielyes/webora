<?php
$userId = $_SESSION['user']['id'] ?? 0;

// Fetch transactions from SheetDB
//$apiUrl = "https://sheetdb.io/api/v1/2eyctn6m5yzmz/search?id_utilisateur=" . $userId;
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$response = curl_exec($ch);
curl_close($ch);
$allTransactions = json_decode($response, true) ?? [];

$selectedIban = $_GET['iban_filter'] ?? '';
$transactions = $allTransactions;
if ($selectedIban) {
    $transactions = array_filter($allTransactions, function($t) use ($selectedIban) {
        return ($t['source'] ?? '') === $selectedIban || ($t['destination'] ?? '') === $selectedIban;
    });
}
$transactions = array_reverse(array_values($transactions));

// Excel CSV download
if (isset($_GET['action']) && $_GET['action'] === 'excel') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="legalfin_releve_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Libelle', 'Type', 'Source', 'Destination', 'Montant', 'Devise']);
    foreach ($transactions as $t) {
        fputcsv($out, [
            $t['date'] ?? '', $t['libelle'] ?? '', $t['type'] ?? '',
            $t['source'] ?? '', $t['destination'] ?? '',
            $t['montant'] ?? 0, $t['devise'] ?? 'TND'
        ]);
    }
    fclose($out);
    exit;
}

// Build transaction rows JSON for JS pdf generation
$txJson = json_encode($transactions);
$userName = htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$userEmail = htmlspecialchars($user['email'] ?? '');
$genDate = date('d/m/Y H:i');
$ibanDisplay = $selectedIban ?: 'Global — Tous les comptes';
?>

<!-- html2pdf.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
.hist-wrap { animation: fadeUp 0.4s ease; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.hist-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; gap:16px; flex-wrap:wrap; }
.hist-title { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; margin:0; }
.hist-sub { color:var(--muted); font-size:0.82rem; margin-top:4px; }
.hist-actions { display:flex; gap:10px; align-items:center; }
.hbtn { display:inline-flex; align-items:center; gap:7px; padding:10px 18px; border-radius:12px; font-size:0.78rem; font-weight:700; cursor:pointer; text-decoration:none; border:none; transition:all 0.2s; letter-spacing:0.3px; white-space:nowrap; font-family:'Syne',sans-serif; }
.hbtn-primary { background:var(--primary,#2563eb); color:#fff; box-shadow:0 4px 15px rgba(37,99,235,0.3); }
.hbtn-secondary { background:var(--bg3,rgba(255,255,255,0.08)); color:var(--text,#fff); border:1px solid var(--border,rgba(255,255,255,0.1)); }
.hbtn:hover { transform:translateY(-2px); opacity:0.88; }
.hbtn:disabled { opacity:0.6; cursor:wait; transform:none; }
.hist-filter { background:var(--bg2,rgba(255,255,255,0.04)); border:1px solid var(--border,rgba(255,255,255,0.08)); border-radius:16px; padding:16px 20px; margin-bottom:28px; display:flex; align-items:center; gap:14px; }
.hist-filter label { font-size:0.72rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:1px; white-space:nowrap; }
.hist-filter select { flex:1; background:var(--bg,#0f172a); border:1px solid var(--border,rgba(255,255,255,0.1)); color:var(--text,#fff); padding:9px 14px; border-radius:10px; outline:none; font-family:inherit; font-size:0.85rem; font-weight:600; cursor:pointer; }
.hist-table-wrap { overflow-x:auto; }
.hist-table { width:100%; border-collapse:separate; border-spacing:0 6px; }
.hist-table thead th { padding:8px 14px; font-size:0.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:1.5px; font-weight:700; text-align:left; }
.hist-table tbody tr td { padding:16px 14px; background:var(--bg2,rgba(255,255,255,0.03)); border-top:1px solid var(--border,rgba(255,255,255,0.06)); border-bottom:1px solid var(--border,rgba(255,255,255,0.06)); vertical-align:middle; transition:background 0.15s; }
.hist-table tbody tr td:first-child { border-left:1px solid var(--border,rgba(255,255,255,0.06)); border-radius:12px 0 0 12px; }
.hist-table tbody tr td:last-child { border-right:1px solid var(--border,rgba(255,255,255,0.06)); border-radius:0 12px 12px 0; }
.hist-table tbody tr:hover td { background:var(--bg3,rgba(255,255,255,0.06)); }
.tx-label { font-weight:700; font-size:0.88rem; margin-bottom:3px; }
.tx-type { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--primary,#2563eb); }
.tx-iban { font-family:'DM Mono',monospace; font-size:0.72rem; color:var(--muted); }
.tx-iban span { opacity:0.5; }
.tx-amount { font-family:'Syne',sans-serif; font-weight:800; font-size:1rem; text-align:right; }
.tx-date { font-size:0.8rem; color:var(--muted); }
.color-out { color:#f43f5e; }
.color-in  { color:#22c55e; }
.hist-empty { text-align:center; padding:60px 20px; color:var(--muted); border:1px dashed var(--border,rgba(255,255,255,0.1)); border-radius:16px; font-size:0.9rem; }
</style>

<!-- ── HIDDEN PDF TEMPLATE ──────────────────────────────── -->
<div id="pdf-template" style="display:none; font-family:'Helvetica Neue',Arial,sans-serif; color:#1a1a2e; background:#fff; width:794px; margin:0 auto;">

    <!-- LETTERHEAD: Top accent bar -->
    <div style="background:linear-gradient(90deg,#0a1628 0%,#1e3a8a 60%,#1d4ed8 100%); height:8px; width:100%;"></div>

    <!-- HEADER -->
    <div style="padding:32px 48px 24px; display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #e2e8f0;">
        <!-- Left: Logo Block -->
        <div>
            <div style="font-size:26px; font-weight:900; letter-spacing:-1.5px; color:#0a1628; line-height:1;">
                Legal<span style="color:#1d4ed8;">Fin</span><span style="color:#f59e0b; font-size:10px; vertical-align:super; margin-left:2px;">®</span>
            </div>
            <div style="font-size:7.5px; color:#64748b; text-transform:uppercase; letter-spacing:3px; margin-top:5px; font-weight:600;">Banque Numérique Certifiée · Tunisie</div>
            <div style="margin-top:14px; padding:6px 12px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:6px; display:inline-block;">
                <span style="font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#0369a1;">Document Officiel — Confidentiel</span>
            </div>
        </div>
        <!-- Right: Statement Title -->
        <div style="text-align:right;">
            <div style="font-size:18px; font-weight:800; color:#0a1628; text-transform:uppercase; letter-spacing:2px; line-height:1.2;">Relevé de Compte</div>
            <div style="font-size:8px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px; margin-top:4px;">Bank Statement</div>
            <div style="margin-top:12px; text-align:right;">
                <div style="font-size:7.5px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">Date d'émission</div>
                <div style="font-size:11px; font-weight:700; color:#0a1628; margin-top:2px;"><?= $genDate ?></div>
            </div>
            <div style="margin-top:6px; text-align:right;">
                <div style="font-size:7.5px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">Référence Document</div>
                <div style="font-size:9px; font-weight:700; color:#1d4ed8; font-family:monospace; margin-top:2px;">LF-<?= strtoupper(substr(md5($userName.$genDate), 0, 12)) ?></div>
            </div>
        </div>
    </div>

    <!-- GOLD DIVIDER -->
    <div style="height:3px; background:linear-gradient(90deg,#f59e0b,#fcd34d,#f59e0b); margin:0 48px;"></div>

    <!-- CLIENT INFO BAND -->
    <div style="padding:20px 48px; background:#f8fafc; display:flex; justify-content:space-between; gap:20px; border-bottom:1px solid #e2e8f0;">
        <div style="flex:1;">
            <div style="font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#94a3b8; margin-bottom:5px;">Titulaire du Compte</div>
            <div style="font-size:14px; font-weight:800; color:#0a1628; line-height:1.2;"><?= $userName ?></div>
            <div style="font-size:8.5px; color:#64748b; margin-top:3px;"><?= $userEmail ?></div>
        </div>
        <div style="flex:1; border-left:2px solid #e2e8f0; padding-left:20px;">
            <div style="font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#94a3b8; margin-bottom:5px;">Compte / IBAN</div>
            <div style="font-size:9px; font-weight:700; color:#1d4ed8; font-family:monospace; line-height:1.5; word-break:break-all;"><?= $ibanDisplay ?></div>
        </div>
        <div style="flex:1; border-left:2px solid #e2e8f0; padding-left:20px;">
            <div style="font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#94a3b8; margin-bottom:5px;">Période</div>
            <div style="font-size:9px; font-weight:700; color:#0a1628;">Toutes transactions</div>
            <div style="font-size:8px; color:#64748b; margin-top:3px;">Données en temps réel</div>
        </div>
    </div>

    <!-- SECTION TITLE -->
    <div style="padding:18px 48px 10px;">
        <div style="font-size:7.5px; font-weight:800; text-transform:uppercase; letter-spacing:2.5px; color:#1d4ed8; display:flex; align-items:center; gap:8px;">
            <div style="width:20px; height:2px; background:#1d4ed8;"></div>
            Détail des opérations
            <div style="flex:1; height:1px; background:#e2e8f0;"></div>
        </div>
    </div>

    <!-- TRANSACTIONS TABLE -->
    <div style="padding:0 48px 20px;">
        <table style="width:100%; border-collapse:collapse; font-size:8.5px;">
            <thead>
                <tr style="background:#0a1628;">
                    <th style="padding:10px 10px; text-align:left; color:#93c5fd; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:7px; white-space:nowrap;">Date</th>
                    <th style="padding:10px 10px; text-align:left; color:#93c5fd; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:7px;">Opération</th>
                    <th style="padding:10px 10px; text-align:left; color:#93c5fd; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:7px;">Compte source</th>
                    <th style="padding:10px 10px; text-align:left; color:#93c5fd; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:7px;">Compte dest.</th>
                    <th style="padding:10px 10px; text-align:right; color:#93c5fd; font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:7px;">Montant</th>
                </tr>
            </thead>
            <tbody id="pdf-tbody">
            <!-- JS will fill this -->
            </tbody>
        </table>
    </div>

    <!-- SUMMARY BAR -->
    <div id="pdf-summary" style="margin:0 48px 24px; padding:14px 20px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:8px; color:#0369a1; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Total des opérations</div>
        <div id="pdf-tx-count" style="font-size:12px; font-weight:800; color:#0a1628;">— transactions</div>
    </div>

    <!-- SECURITY & SIGNATURE -->
    <div style="margin:0 48px 28px; display:flex; gap:16px;">
        <!-- Biometric Seal -->
        <div style="flex:1; border:1px solid #d1fae5; background:#f0fdf4; border-radius:10px; padding:14px 16px;">
            <div style="font-size:6.5px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#059669; margin-bottom:8px;">Sécurité & Vérification</div>
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px;">
                <div style="width:7px; height:7px; border-radius:50%; background:#22c55e; flex-shrink:0;"></div>
                <div style="font-size:8px; font-weight:700; color:#15803d;">Identité vérifiée — Face ID LegalFin</div>
            </div>
            <div style="font-size:6.5px; color:#6b7280; padding-left:13px;">Protocole : SHA-256 · TLS 1.3 · ISO 27001</div>
            <div style="font-size:6.5px; color:#6b7280; padding-left:13px; margin-top:2px;">Certifié Banque Centrale de Tunisie</div>
        </div>
        <!-- Signature Block -->
        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; text-align:center;">
            <div style="font-size:6.5px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#94a3b8; margin-bottom:20px;">Signature & Cachet Officiel</div>
            <!-- Signature line -->
            <div style="border-top:1.5px solid #0a1628; width:130px; margin:0 auto 6px;"></div>
            <div style="font-size:7.5px; font-weight:700; color:#0a1628;">Direction Générale</div>
            <div style="font-size:6.5px; color:#94a3b8; margin-top:2px;">LegalFin Bank — Tunisie</div>
            <!-- Stamp -->
            <div style="margin-top:10px; border:2px solid #1d4ed8; border-radius:50%; width:50px; height:50px; margin-left:auto; margin-right:auto; display:flex; align-items:center; justify-content:center; transform:rotate(-15deg); opacity:0.6;">
                <div style="text-align:center; font-size:4.5px; font-weight:900; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.5px; line-height:1.4;">LEGAL<br>FIN<br>BANK</div>
            </div>
        </div>
        <!-- Legal Notice -->
        <div style="flex:1; border:1px solid #fef3c7; background:#fffbeb; border-radius:10px; padding:14px 16px;">
            <div style="font-size:6.5px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#d97706; margin-bottom:8px;">Notice Légale</div>
            <div style="font-size:6.5px; color:#78716c; line-height:1.7;">Ce document est généré automatiquement et constitue un relevé non-contractuel. LegalFin Bank décline toute responsabilité en cas d'utilisation frauduleuse. Conservation recommandée: 5 ans.</div>
        </div>
    </div>

    <!-- FOOTER BAR -->
    <div style="background:#0a1628; padding:16px 48px; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:6.5px; color:rgba(255,255,255,0.35); letter-spacing:1px; line-height:1.8;">
            LEGALFIN BANK · Siège Social: LegalFin Tower, Avenue Habib Bourguiba, Tunis 1000<br>
            RC: B123456 · Agrément BCT N°2024-LF-001
        </div>
        <div style="font-size:8px; font-weight:900; color:rgba(255,255,255,0.15); letter-spacing:2px;">LEGALFIN®</div>
        <div style="font-size:6px; color:rgba(255,255,255,0.3); text-align:right; line-height:1.8;">
            Réf: LF-<?= strtoupper(substr(md5($userName.$genDate), 0, 12)) ?><br>
            Généré le <?= $genDate ?>
        </div>
    </div>
    <!-- Bottom accent -->
    <div style="background:linear-gradient(90deg,#f59e0b,#fcd34d,#f59e0b); height:5px;"></div>

</div>

<!-- ── MAIN DASHBOARD CONTENT ──────────────────────────── -->
<div class="hist-wrap">
    <div class="hist-toolbar">
        <div>
            <div class="hist-title">Historique & Relevés</div>
            <div class="hist-sub">Consultez et exportez toutes vos transactions en temps réel.</div>
        </div>
        <div class="hist-actions">
            <a href="?tab=historique&action=excel&iban_filter=<?= urlencode($selectedIban) ?>" class="hbtn hbtn-secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Exporter Excel
            </a>
            <button class="hbtn hbtn-primary" id="pdf-btn" onclick="downloadPDF()">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M10 13h4M10 17h4M14 3v4a1 1 0 001 1h4"/></svg>
                Télécharger PDF
            </button>
        </div>
    </div>

    <div class="hist-filter">
        <label>Compte</label>
        <form method="GET" style="flex:1; display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="tab" value="historique">
            <select name="iban_filter" onchange="this.form.submit()">
                <option value="">Tous mes comptes</option>
                <?php foreach ($comptes as $c): ?>
                    <option value="<?= $c->getIban() ?>" <?= $selectedIban === $c->getIban() ? 'selected' : '' ?>>
                        <?= ucfirst($c->getTypeCompte()) ?> — ···<?= substr($c->getIban(), -6) ?>
                        (<?= number_format($c->getSolde(), 2) ?> <?= $c->getDevise() ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span style="font-size:0.75rem; color:var(--muted); white-space:nowrap;">
                <?= count($transactions) ?> transaction<?= count($transactions) > 1 ? 's' : '' ?>
            </span>
        </form>
    </div>

    <div class="hist-table-wrap">
        <table class="hist-table">
            <thead>
                <tr>
                    <th>Date / Heure</th>
                    <th>Opération</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th style="text-align:right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="hist-empty">Aucune transaction enregistrée pour ce compte.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t):
                        $isOut = $selectedIban && ($t['source'] ?? '') === $selectedIban;
                        $src = $t['source'] ?? '';
                        $dst = $t['destination'] ?? '';
                    ?>
                    <tr>
                        <td class="tx-date"><?= date('d M Y, H:i', strtotime($t['date'] ?? 'now')) ?></td>
                        <td>
                            <div class="tx-label"><?= htmlspecialchars($t['libelle'] ?? 'Virement') ?></div>
                            <div class="tx-type"><?= htmlspecialchars(strtoupper($t['type'] ?? 'INTERNE')) ?></div>
                        </td>
                        <td class="tx-iban"><span>DE : </span><?= $src ? (substr($src, 0, 8) . '···' . substr($src, -4)) : '—' ?></td>
                        <td class="tx-iban"><span>VERS : </span><?= $dst ? (substr($dst, 0, 8) . '···' . substr($dst, -4)) : '—' ?></td>
                        <td class="tx-amount <?= $isOut ? 'color-out' : 'color-in' ?>">
                            <?= $isOut ? '−' : '+' ?><?= number_format($t['montant'] ?? 0, 2) ?> <span style="font-size:0.7rem;"><?= $t['devise'] ?? 'TND' ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- HISTORIQUE PAGINATION -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:18px; flex-wrap:wrap; gap:10px;">
            <div id="hist-page-info" style="font-size:0.75rem; color:var(--muted);"></div>
            <div id="hist-page-btns" style="display:flex; gap:6px;"></div>
        </div>
    </div>
</div>

<script>
(function() {
    var PER_PAGE = 10;
    var currentPage = 1;

    function getRows() {
        var tbody = document.querySelector('.hist-table tbody');
        if (!tbody) return [];
        return Array.from(tbody.querySelectorAll('tr:not(.hist-empty-row)'));
    }

    function renderHistPage(page) {
        currentPage = page;
        var rows = getRows();
        var total = rows.length;
        var totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;
        var start = (currentPage - 1) * PER_PAGE;
        var end   = start + PER_PAGE;

        rows.forEach(function(r, i) {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        // Info
        var info = document.getElementById('hist-page-info');
        if (info) {
            var from = total === 0 ? 0 : start + 1;
            var to   = Math.min(end, total);
            info.textContent = from + '–' + to + ' sur ' + total + ' transaction' + (total > 1 ? 's' : '');
        }

        // Buttons
        var container = document.getElementById('hist-page-btns');
        if (!container) return;
        container.innerHTML = '';

        var base = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 6px;border-radius:8px;font-size:0.75rem;font-weight:700;cursor:pointer;transition:all 0.15s;';
        var inactiveStyle = base + 'background:var(--bg2,rgba(255,255,255,0.04));color:var(--muted);border:1px solid var(--border,rgba(255,255,255,0.08));';
        var activeStyle   = base + 'background:var(--primary,#2563eb);color:#fff;border:none;box-shadow:0 2px 8px rgba(37,99,235,0.3);';

        // Prev
        var prev = document.createElement('button');
        prev.style.cssText = inactiveStyle + (currentPage === 1 ? 'opacity:0.4;' : '');
        prev.innerHTML = '‹';
        prev.disabled = currentPage === 1;
        prev.onclick = function() { renderHistPage(currentPage - 1); };
        container.appendChild(prev);

        // Page numbers
        var pStart = Math.max(1, currentPage - 2);
        var pEnd   = Math.min(totalPages, pStart + 4);
        pStart = Math.max(1, pEnd - 4);

        for (var p = pStart; p <= pEnd; p++) {
            (function(pg) {
                var btn = document.createElement('button');
                btn.style.cssText = pg === currentPage ? activeStyle : inactiveStyle;
                btn.textContent = pg;
                btn.onclick = function() { renderHistPage(pg); };
                container.appendChild(btn);
            })(p);
        }

        // Next
        var next = document.createElement('button');
        next.style.cssText = inactiveStyle + (currentPage === totalPages ? 'opacity:0.4;' : '');
        next.innerHTML = '›';
        next.disabled = currentPage === totalPages;
        next.onclick = function() { renderHistPage(currentPage + 1); };
        container.appendChild(next);
    }

    window.addEventListener('load', function() { renderHistPage(1); });
    renderHistPage(1);
})();
</script>

<script>
var txData = <?= $txJson ?>;
var selectedIban = <?= json_encode($selectedIban) ?>;

function downloadPDF() {
    var btn = document.getElementById('pdf-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Génération...';

    // Populate transaction count in summary bar
    var countEl = document.getElementById('pdf-tx-count');
    if (countEl) countEl.textContent = txData.length + ' transaction' + (txData.length > 1 ? 's' : '');

    // Populate the PDF tbody
    var tbody = document.getElementById('pdf-tbody');
    tbody.innerHTML = '';
    if (txData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;font-style:italic;font-size:9px;">Aucune transaction enregistrée.</td></tr>';
    } else {
        txData.forEach(function(t, i) {
            var isOut = selectedIban && (t.source || '') === selectedIban;
            var color = isOut ? '#dc2626' : '#16a34a';
            var sign  = isOut ? '−' : '+';
            var src   = t.source ? (t.source.substring(0,6) + '···' + t.source.slice(-4)) : '—';
            var dst   = t.destination ? (t.destination.substring(0,6) + '···' + t.destination.slice(-4)) : '—';
            var bg    = i % 2 === 0 ? '#ffffff' : '#f8fafc';
            var borderLeft = isOut ? '3px solid #dc2626' : '3px solid #16a34a';
            var dateStr = t.date ? new Date(t.date).toLocaleString('fr-FR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';
            var amount = parseFloat(t.montant || 0).toFixed(2);
            tbody.innerHTML += `
                <tr style="background:${bg}; border-left:${borderLeft};">
                    <td style="padding:9px 10px; border-bottom:1px solid #f1f5f9; font-size:7.5px; color:#475569; white-space:nowrap;">${dateStr}</td>
                    <td style="padding:9px 10px; border-bottom:1px solid #f1f5f9;">
                        <div style="font-weight:700; font-size:8.5px; color:#0a1628;">${t.libelle || 'Virement'}</div>
                        <div style="font-size:6.5px; color:#1d4ed8; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-top:2px;">${t.type || 'INTERNE'}</div>
                    </td>
                    <td style="padding:9px 10px; border-bottom:1px solid #f1f5f9; font-family:monospace; font-size:7px; color:#64748b;">${src}</td>
                    <td style="padding:9px 10px; border-bottom:1px solid #f1f5f9; font-family:monospace; font-size:7px; color:#64748b;">${dst}</td>
                    <td style="padding:9px 10px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:${color}; font-size:9.5px; white-space:nowrap;">
                        ${sign}${amount} <span style="font-size:7px; font-weight:600;">${t.devise || 'TND'}</span>
                    </td>
                </tr>`;
        });
    }

    // Show template temporarily for html2pdf
    var template = document.getElementById('pdf-template');
    template.style.display = 'block';

    var opt = {
        margin:       0,
        filename:     'LegalFin_Releve_<?= date('Ymd_His') ?>.pdf',
        image:        { type: 'jpeg', quality: 0.99 },
        html2canvas:  { scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff' },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(template).save().then(function() {
        template.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M10 13h4M10 17h4M14 3v4a1 1 0 001 1h4"/></svg> Télécharger PDF';
    });
}
</script>
