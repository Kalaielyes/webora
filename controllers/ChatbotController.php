<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    require_once __DIR__ . '/../models/config.php';
    require_once __DIR__ . '/CompteController.php';
    require_once __DIR__ . '/CarteController.php';

    Config::autoLogin();
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $user   = $_SESSION['user'] ?? [];

    if (!$userId) { ob_end_clean(); echo json_encode(['reply' => 'Session expirée. Veuillez rafraîchir la page.']); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ob_end_clean(); echo json_encode(['reply' => 'Méthode non autorisée.']); exit; }

    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input) || empty($input)) {
        $input = $_POST;
        if (isset($input['history']) && is_string($input['history']))
            $input['history'] = json_decode($input['history'], true) ?? [];
    }

    $message = trim($input['message'] ?? '');
    $lang    = trim($input['lang'] ?? 'fr-FR');
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];
    if ($message === '') { ob_end_clean(); echo json_encode(['reply' => 'Veuillez entrer un message.']); exit; }

    $comptes   = CompteController::findByUtilisateur($userId);
    $allCartes = [];
    foreach ($comptes as $c)
        foreach (CarteController::findByCompte($c->getIdCompte()) as $carte)
            $allCartes[] = ['carte' => $carte, 'compte' => $c];

    // Try Gemini (server-key, no referrer restriction) → fallback to conversational engine
    $reply = callGemini($message, $history, $user, $comptes, $allCartes, $lang);
    if ($reply === null)
        $reply = conversationalReply($message, $history, $user, $comptes, $allCartes, $lang);

    ob_end_clean();
    echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['reply' => '⚠️ Erreur : ' . $e->getMessage()]);
}
exit;


// ════════════════════════════════════════════════════════════════════════════════
// GEMINI ENGINE  (works only if API key has NO referrer restriction)
// ════════════════════════════════════════════════════════════════════════════════
function callGemini(string $msg, array $history, array $user, array $comptes, array $allCartes, string $lang = 'fr-FR'): ?string
{
    $key = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
    if ($key === '' || $key === 'YOUR_KEY_HERE') return null;
    if (!function_exists('curl_init')) return null;

    $ctx  = "Client: " . ($user['prenom'] ?? '') . " " . ($user['nom'] ?? '') . " | KYC: " . ($user['status_kyc'] ?? 'inconnu') . " | Date: " . date('d/m/Y H:i') . "\n";
    $ctx .= "COMPTES:\n";
    foreach ($comptes as $c)
        $ctx .= "  - " . $c->getTypeCompte() . " | Solde: " . number_format((float)$c->getSolde(),2) . " " . $c->getDevise() . " | IBAN: " . $c->getIban() . " | Statut: " . $c->getStatut() . "\n";
    $ctx .= "CARTES:\n";
    foreach ($allCartes as $item) {
        $crt = $item['carte'];
        $ctx .= "  - " . $crt->getTypeCarte() . " (" . $crt->getReseau() . ") ···" . substr($crt->getNumeroCarte(),-4) . " | " . $crt->getStatut() . "\n";
    }

    $langName = 'français';
    if ($lang === 'en-US') $langName = 'anglais (English)';
    if ($lang === 'ar-SA') $langName = 'arabe (Arabic)';

    $system = "Tu es l'assistant IA de la banque LegalFin. Réponds EXCLUSIVEMENT en $langName, de façon professionnelle et bienveillante.\n"
        . "Utilise le Markdown (** gras, • listes). Tu as l'autorisation de répondre à n'importe quelle question, même si elle n'est pas liée à la banque.\n"
        . "DONNÉES CLIENT:\n" . $ctx;

    $contents = [];
    foreach ($history as $h) {
        $role = ($h['role'] === 'user') ? 'user' : 'model';
        $text = trim($h['content'] ?? '');
        if ($text === '') continue;
        $last = count($contents) - 1;
        if ($last >= 0 && $contents[$last]['role'] === $role)
            $contents[$last]['parts'][0]['text'] .= "\n" . $text;
        else
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
    }
    $last = count($contents) - 1;
    if ($last >= 0 && $contents[$last]['role'] === 'user')
        $contents[$last]['parts'][0]['text'] .= "\n" . $msg;
    else
        $contents[] = ['role' => 'user', 'parts' => [['text' => $msg]]];

    $payload = json_encode([
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents'           => $contents,
        'generationConfig'   => ['temperature' => 0.7, 'maxOutputTokens' => 900],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=' . $key);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    @file_put_contents(__DIR__ . '/chatbot_debug.log',
        date('[Y-m-d H:i:s]') . " Gemini HTTP:$httpCode cURL:" . ($curlErr ?: 'ok') . " resp:" . substr($response ?: 'EMPTY', 0, 300) . "\n", FILE_APPEND);

    if ($curlErr || !$response || $httpCode !== 200) return null;
    $res = json_decode($response, true);
    if (isset($res['error'])) { @file_put_contents(__DIR__ . '/chatbot_debug.log', "Gemini error: " . json_encode($res['error']) . "\n", FILE_APPEND); return null; }
    return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
}


// ════════════════════════════════════════════════════════════════════════════════
// CONVERSATIONAL ENGINE  — understands full questions in any language/style
// ════════════════════════════════════════════════════════════════════════════════
function conversationalReply(string $msg, array $history, array $user, array $comptes, array $allCartes, string $lang = 'fr-FR'): string
{
    $norm   = normalizeStr(mb_strtolower($msg, 'UTF-8'));
    $prenom = $user['prenom'] ?? 'Client';

    // ── Detect intent with scored matching ───────────────────────────────────
    $scores = [
        'greeting'   => score($norm, ['bonjour','bonsoir','salut','hello','hi ','hey','coucou','salam','good morning','good evening']),
        'help'       => score($norm, ['aide','help','menu','option','que peux','quoi faire','fonction','commande','liste des']),
        'balance'    => score($norm, ['solde','combien','argent','disponible','balance','montant','avoir','liquidite','reste']),
        'iban'       => score($norm, ['iban','rib','coordonnee','numero de compte','reference bancaire','rib bancaire']),
        'accounts'   => score($norm, ['mes comptes','mon compte','liste compte','voir compte','epargne','courant','professionnel']),
        'cards'      => score($norm, ['carte','card','visa','mastercard','debit','mes cartes','ma carte']),
        'block_card' => score($norm, ['bloquer','opposition','suspendre','desactiver','urgence carte','perdu carte','vole carte']),
        'limits'     => score($norm, ['plafond','limite','maximum retrait','maximum paiement','combien retirer','combien payer']),
        'expiry'     => score($norm, ['expir','validite','date carte','echeance']),
        'kyc'        => score($norm, ['kyc','verification identite','verifier','document','statut kyc','valider identite']),
        'pending'    => score($norm, ['demande','attente','en cours','suivi','statut demande','mes demandes','traitement','pending','request']),
        'credit'     => score($norm, ['credit','pret','emprunt','financement','loan','emprunter','obtenir credit','faire credit',
                                       'necessite','besoin credit','condition credit','dossier credit','comment credit',
                                       'immobilier','auto','personnel','professionnel','taux credit','montant credit','duree credit']),
        'transfer'   => score($norm, ['virement','transfert','envoyer argent','virer','paiement','transaction','effectuer virement']),
        'contact'    => score($norm, ['contact','agence','conseiller','telephone','appeler','joindre','adresse','horaire','service client']),
        'farewell'   => score($norm, ['merci','au revoir','bye','bonne journee','bonne soiree','a bientot','ciao','adieu']),
        'offTopic'   => score($norm, ['meteo','sport','foot','cinema','film','musique','cuisine','recette','voyage','politique',
                                       'jeu video','serie','sante','medecin','restaurant','hotel','tourisme','programmation',
                                       'math','science','animal','blague','actualite','news']),
    ];

    // Get the winning intent
    $intent = array_search(max($scores), $scores);
    $maxScore = max($scores);

    // Require minimum confidence
    if ($maxScore === 0) $intent = 'unknown';

    // ── Intent handlers ───────────────────────────────────────────────────────

    if ($intent === 'greeting') {
        $h = (int)date('H');
        $g = $h < 12 ? 'Bonjour' : ($h < 18 ? 'Bon après-midi' : 'Bonsoir');
        return "$g **$prenom** ! 👋 Je suis votre assistant bancaire LegalFin.\nComment puis-je vous aider aujourd'hui ? Tapez **aide** pour voir toutes les options.";
    }

    if ($intent === 'help') {
        return "Voici tout ce que je peux faire pour vous :\n\n"
            . "💰 **Solde** — *Quel est mon solde ? Combien j'ai ?*\n"
            . "🏦 **Comptes** — *Mes comptes, voir mes comptes*\n"
            . "💳 **Cartes** — *Mes cartes, voir mes cartes*\n"
            . "📋 **IBAN / RIB** — *Mon IBAN, mon RIB*\n"
            . "📊 **Plafonds** — *Mes plafonds, limite retrait*\n"
            . "🔒 **Bloquer carte** — *Bloquer ma carte, opposition*\n"
            . "⏳ **Demandes** — *Mes demandes, statut*\n"
            . "🪪 **KYC** — *Mon KYC, vérification identité*\n"
            . "💸 **Crédit** — *Comment faire un crédit, conditions*\n"
            . "💳 **Virement** — *Faire un virement*\n"
            . "📞 **Contact** — *Contacter la banque*";
    }

    if ($intent === 'balance') {
        $active = array_filter($comptes, fn($c) => $c->getStatut() === 'actif');
        if (empty($active)) return "Aucun compte actif pour le moment. Votre demande est peut-être en cours de traitement.";
        $lines = ['💰 Voici vos soldes disponibles :'];
        foreach ($active as $c)
            $lines[] = "• **" . ucfirst($c->getTypeCompte()) . "** : **" . number_format((float)$c->getSolde(), 3, '.', ' ') . " " . $c->getDevise() . "**";
        return implode("\n", $lines);
    }

    if ($intent === 'iban') {
        $active = array_filter($comptes, fn($c) => $c->getStatut() === 'actif');
        if (empty($active)) return "Aucun compte actif. Votre IBAN sera disponible après activation.";
        $lines = ['📋 Vos IBAN :'];
        foreach ($active as $c)
            $lines[] = "• **" . ucfirst($c->getTypeCompte()) . "** : `" . $c->getIban() . "`";
        return implode("\n", $lines);
    }

    if ($intent === 'accounts') {
        if (empty($comptes)) return "Vous n'avez aucun compte enregistré.";
        $sm = ['actif'=>'✅ Actif','en_attente'=>'⏳ En attente','bloque'=>'🔒 Bloqué','demande_cloture'=>'⏳ Clôture','cloture'=>'❌ Clôturé'];
        $lines = ["🏦 Vous avez **" . count($comptes) . " compte(s)** :"];
        foreach ($comptes as $c)
            $lines[] = "• **" . ucfirst($c->getTypeCompte()) . "** — " . $c->getDevise() . " — " . ($sm[$c->getStatut()] ?? $c->getStatut());
        return implode("\n", $lines);
    }

    if ($intent === 'block_card') {
        return "🔒 Pour **bloquer une carte** immédiatement :\n\n"
            . "1. Allez dans **Mes cartes**\n"
            . "2. Cliquez sur **Bloquer** à côté de la carte concernée\n\n"
            . "📞 Urgence 24h/24 : **71 000 000**\n"
            . "📧 contact@legalfin.tn";
    }

    if ($intent === 'cards') {
        if (empty($allCartes)) return "Vous n'avez aucune carte bancaire liée à vos comptes.";
        $sm = ['active'=>'✅ Active','inactive'=>'⏳ Inactive','bloquee'=>'🔒 Bloquée','expiree'=>'⚠️ Expirée','demande_reactivation'=>'⏳ Réactivation','demande_suppression'=>'⏳ Suppression'];
        $lines = ["💳 Vous avez **" . count($allCartes) . " carte(s)** :"];
        foreach ($allCartes as $item) {
            $c = $item['carte'];
            $lines[] = "• **" . ucfirst($c->getTypeCarte()) . " " . strtoupper($c->getReseau()) . "** ···" . substr($c->getNumeroCarte(),-4) . " — " . ($sm[$c->getStatut()] ?? $c->getStatut());
        }
        return implode("\n", $lines);
    }

    if ($intent === 'limits') {
        $active = array_filter($allCartes, fn($i) => $i['carte']->getStatut() === 'active');
        if (empty($active)) return "Aucune carte active pour afficher les plafonds.";
        $lines = ['📊 Vos plafonds :'];
        foreach ($active as $item) {
            $c = $item['carte'];
            $lines[] = "• **" . ucfirst($c->getTypeCarte()) . "** ···" . substr($c->getNumeroCarte(),-4)
                . "\n  — Paiement/jour : **" . number_format((float)$c->getPlafondPaiementJour(),0,'.',' ') . " TND**"
                . "\n  — Retrait/jour : **" . number_format((float)$c->getPlafondRetraitJour(),0,'.',' ') . " TND**";
        }
        return implode("\n", $lines);
    }

    if ($intent === 'expiry') {
        if (empty($allCartes)) return "Aucune carte trouvée.";
        $lines = ["📅 Dates d'expiration de vos cartes :"];
        foreach ($allCartes as $item) {
            $c = $item['carte']; $exp = $c->getDateExpiration();
            $d = $exp ? (substr($exp,5,2).'/'.substr($exp,2,2)) : '--/--';
            $lines[] = "• ···" . substr($c->getNumeroCarte(),-4) . " — expire **$d**" . ($c->getStatut()==='expiree'?' ⚠️':'');
        }
        return implode("\n", $lines);
    }

    if ($intent === 'kyc') {
        return ($user['status_kyc'] ?? '') === 'VERIFIE'
            ? "✅ Votre identité est **vérifiée (KYC)**.\nVous avez accès à tous les services LegalFin."
            : "⏳ Votre vérification **KYC est en cours**.\nCertaines fonctionnalités sont limitées jusqu'à validation.";
    }

    if ($intent === 'pending') {
        $pC = array_filter($comptes, fn($c) => in_array($c->getStatut(), ['en_attente','demande_cloture','demande_suppression']));
        $pK = array_filter($allCartes, fn($i) => in_array($i['carte']->getStatut(), ['inactive','demande_reactivation','demande_suppression']));
        $t  = count($pC) + count($pK);
        if ($t === 0) return "✅ Vous n'avez aucune demande en attente de traitement.";
        $lines = ["⏳ Vous avez **$t demande(s) en attente** :"];
        if (count($pC)) $lines[] = "• **" . count($pC) . " compte(s)** en attente de traitement";
        if (count($pK)) $lines[] = "• **" . count($pK) . " carte(s)** en attente de traitement";
        $lines[] = "\nConsultez l'onglet **En attente** dans votre espace pour plus de détails.";
        return implode("\n", $lines);
    }

    if ($intent === 'credit') {
        return "💸 **Crédit LegalFin — Comment ça marche ?**\n\n"
            . "**Types de crédits disponibles :**\n"
            . "• 🏠 **Immobilier** — achat ou construction d'un bien\n"
            . "• 🚗 **Auto** — financement d'un véhicule\n"
            . "• 👤 **Personnel** — besoins personnels (voyage, travaux...)\n"
            . "• 💼 **Professionnel** — développement d'activité\n\n"
            . "**Documents nécessaires :**\n"
            . "• 🪪 Carte d'identité nationale (CIN) valide\n"
            . "• 📄 3 dernières fiches de paie (ou bilan si indépendant)\n"
            . "• 🏦 Relevés bancaires des 6 derniers mois\n"
            . "• 📋 Justificatif du projet (devis, compromis, facture...)\n"
            . "• 📝 Formulaire de demande rempli à l'agence\n\n"
            . "**Conditions générales :**\n"
            . "• Être client LegalFin avec un compte actif\n"
            . "• Avoir un revenu régulier justifiable\n"
            . "• Statut KYC vérifié ✅\n\n"
            . "📞 Pour démarrer votre dossier : **71 000 000**\n"
            . "📧 **conseiller@legalfin.tn**\n"
            . "🕐 Nos conseillers répondent lun–ven **8h–17h**";
    }

    if ($intent === 'transfer') {
        return "💳 **Effectuer un virement :**\n\n"
            . "1. Allez dans la section **Mes comptes**\n"
            . "2. Sélectionnez le compte à débiter\n"
            . "3. Cliquez sur **Faire un virement**\n"
            . "4. Renseignez l'IBAN du bénéficiaire et le montant\n\n"
            . "⏱️ Délai : virement SEPA sous **24–48h**\n"
            . "📞 Besoin d'aide : **71 000 000**";
    }

    if ($intent === 'contact') {
        return "📞 **Service client LegalFin**\n\n"
            . "• Téléphone : **71 000 000** (24h/24, 7j/7)\n"
            . "• Email : **contact@legalfin.tn**\n"
            . "• Agences : lundi–vendredi **8h–17h**";
    }

    if ($intent === 'farewell') {
        return "Merci **$prenom** ! 🏦 N'hésitez pas à revenir. Bonne journée !";
    }

    if ($intent === 'offTopic') {
        return "Je suis un assistant bancaire par défaut (le moteur IA complet n'est pas disponible actuellement). 🤔\n\nPosez-moi des questions sur la banque, par exemple : **aide**, **solde**, **virement** !";
    }

    // Unknown — try to be helpful
    return "Je n'ai pas bien compris votre question. 🤔\n\n"
        . "Je suis l'assistant bancaire **LegalFin** et je peux vous aider avec :\n"
        . "💰 Solde · 💳 Cartes · 📋 IBAN · 📊 Plafonds · 💸 Crédit · ⏳ Demandes\n\n"
        . "Tapez **aide** pour voir toutes les options, ou reformulez votre question.";
}


// ── Helpers ───────────────────────────────────────────────────────────────────

function normalizeStr(string $s): string {
    $a = ['à','â','ä','á','ã','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','ñ','ç','œ','æ'];
    $b = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c','oe','ae'];
    return str_replace($a, $b, $s);
}

/** Returns how many keywords match — higher = more confident */
function score(string $norm, array $keywords): int {
    $s = 0;
    foreach ($keywords as $kw)
        if (strpos($norm, $kw) !== false) $s++;
    return $s;
}
