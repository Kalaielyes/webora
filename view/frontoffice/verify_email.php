<?php
require_once __DIR__ . '/../../models/Session.php';
Session::start();

$email = Session::get('temp_verify_email');
if (!$email) {
    header('Location: login.php');
    exit;
}

$flash = Session::getFlash();
$errors = Session::get('verify_errors') ?? [];
Session::remove('verify_errors');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l'e-mail - LegalFin Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/frontoffice/Utilisateur.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            margin: 0;
            overflow: hidden;
        }
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .blob1 { width: 500px; height: 500px; top: -150px; left: -150px; background: radial-gradient(circle, rgba(79, 142, 247, 0.1), transparent 70%); }
        .blob2 { width: 400px; height: 400px; bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(45, 212, 191, 0.07), transparent 70%); }
        
        .verify-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeUp 0.6s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--blue), var(--teal));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .logo svg { width: 24px; height: 24px; fill: white; }
        
        h1 { font-family: var(--fh); font-size: 1.8rem; font-weight: 800; margin: 0 0 0.5rem; letter-spacing: -0.02em; }
        p { font-size: 0.9rem; color: var(--muted); line-height: 1.6; margin-bottom: 2rem; }
        .email-display { color: var(--blue); font-weight: 600; }
        
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .otp-input {
            width: 50px;
            height: 60px;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 12px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            font-family: var(--fm);
            outline: none;
            transition: all 0.2s;
        }
        .otp-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(79, 142, 247, 0.1);
            background: var(--surface);
        }
        
        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, var(--blue), #3A7AF0);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-verify:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .resend {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .resend a { color: var(--blue); text-decoration: none; font-weight: 600; }
        
        .flash {
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .flash-success { background: rgba(34, 197, 94, 0.1); color: var(--green); border: 1px solid rgba(34, 197, 94, 0.2); }
        .flash-error { background: rgba(244, 63, 94, 0.1); color: var(--rose); border: 1px solid rgba(244, 63, 94, 0.2); }
    </style>
</head>
<body>
    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    
    <div class="verify-box">
        <div class="logo">
            <svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7l-8 4-8-4v-7l8-4.32z"/></svg>
        </div>
        <h1>Vérifiez votre e-mail</h1>
        <p>Nous avons envoyé un code de vérification à 6 chiffres à <span class="email-display"><?= htmlspecialchars($email) ?></span>. Veuillez le saisir ci-dessous.</p>
        
        <?php if($flash): ?>
            <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        
        <?php if(isset($errors['code'])): ?>
            <div class="flash flash-error"><?= htmlspecialchars($errors['code']) ?></div>
        <?php endif; ?>

        <form action="../../controller/AuthController.php" method="POST" id="otp-form">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="code" id="final-code">
            
            <div class="otp-inputs">
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
            </div>
            
            <button type="submit" class="btn-verify">
                Vérifier le compte
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>
        
        <div class="resend">
            Vous n'avez rien reçu ? <a href="../../controller/AuthController.php?action=resend_otp">Renvoyer le code</a>
        </div>
        
        <div style="text-align:center; margin-top:2rem;">
            <a href="login.php" style="font-size:0.8rem; color:var(--muted); text-decoration:none;">Retour à la connexion</a>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const finalInput = document.getElementById('final-code');
        const form = document.getElementById('otp-form');

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length > 0 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateFinalCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6).split('');
                pasteData.forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                updateFinalCode();
                if (pasteData.length === 6) form.submit();
            });
        });

        function updateFinalCode() {
            let code = "";
            inputs.forEach(input => code += input.value);
            finalInput.value = code;
        }
    </script>
</body>
</html>

