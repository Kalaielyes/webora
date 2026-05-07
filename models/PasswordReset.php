<?php
// =============================================================
//  model/PasswordReset.php — NexaBank
//  Gestion des tokens de réinitialisation de mot de passe
// =============================================================

require_once __DIR__ . '/config.php';

class PasswordReset {

    private PDO $db;
    private const EXPIRY_MINUTES = 30;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    // ─────────────────────────────────────────────
    //  Créer un token pour un utilisateur
    //  Retourne le token brut (à envoyer par WhatsApp)
    // ─────────────────────────────────────────────
    public function createToken(int $userId) : string {
        // Invalider les anciens tokens non utilisés
        $this->invalidateOldTokens($userId);

        // 32 bytes aléatoires → 64 chars hex (token brut envoyé par lien)
        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken); // seul le hash est en DB
        $expiresAt   = date('Y-m-d H:i:s', time() + (self::EXPIRY_MINUTES * 60));

        $s = $this->db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at)
             VALUES (:uid, :token, :exp)"
        );
        $s->execute([':uid' => $userId, ':token' => $hashedToken, ':exp' => $expiresAt]);

        return $rawToken; // jamais stocker le brut en DB
    }

    // ─────────────────────────────────────────────
    //  Valider un token reçu depuis l'URL
    //  Retourne user_id si valide, null sinon
    // ─────────────────────────────────────────────
    public function validateToken(string $rawToken) : ?int {
        $hashedToken = hash('sha256', $rawToken);

        $s = $this->db->prepare(
            "SELECT user_id, expires_at, used
             FROM   password_reset_tokens
             WHERE  token = :token LIMIT 1"
        );
        $s->execute([':token' => $hashedToken]);
        $row = $s->fetch();

        if (!$row)                                  return null; // inexistant
        if ($row['used'])                           return null; // déjà utilisé
        if (strtotime($row['expires_at']) < time()) return null; // expiré

        return (int) $row['user_id'];
    }

    // ─────────────────────────────────────────────
    //  Marquer le token comme consommé après reset
    // ─────────────────────────────────────────────
    public function markAsUsed(string $rawToken) : void {
        $s = $this->db->prepare(
            "UPDATE password_reset_tokens SET used = 1 WHERE token = :token"
        );
        $s->execute([':token' => hash('sha256', $rawToken)]);
    }

    // ─────────────────────────────────────────────
    //  Vérifier validité sans consommer (pour l'UI)
    // ─────────────────────────────────────────────
    public function isValid(string $rawToken) : bool {
        return $this->validateToken($rawToken) !== null;
    }

    // ─────────────────────────────────────────────
    //  Temps restant avant expiration (en minutes)
    // ─────────────────────────────────────────────
    public function minutesRemaining(string $rawToken) : int {
        $s = $this->db->prepare(
            "SELECT expires_at FROM password_reset_tokens
             WHERE token = :token AND used = 0 LIMIT 1"
        );
        $s->execute([':token' => hash('sha256', $rawToken)]);
        $row = $s->fetch();
        if (!$row) return 0;
        return max(0, (int) ceil((strtotime($row['expires_at']) - time()) / 60));
    }

    // Invalider tous les tokens actifs d'un utilisateur
    private function invalidateOldTokens(int $userId) : void {
        $s = $this->db->prepare(
            "UPDATE password_reset_tokens SET used = 1 WHERE user_id = :uid AND used = 0"
        );
        $s->execute([':uid' => $userId]);
    }

    // Nettoyage des tokens périmés (optionnel, cron quotidien)
    public function purgeExpired() : void {
        $this->db->exec("DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used = 1");
    }
}
