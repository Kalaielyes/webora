<?php






require_once __DIR__ . '/config.php';

class Utilisateur {

    private PDO $db;

    private int    $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $mdp;
    private string $numTel;
    private string $date_naissance;
    private string $adresse;
    private string $cin;
    private string $status_kyc   = 'EN_ATTENTE';
    private string $status_aml   = 'EN_ATTENTE';
    private string $status       = 'ACTIF';
    private string $role         = 'CLIENT';
    private int    $niveau_acces = 1;
    private string $id_file_path = '';
    private bool   $association  = false;

    // Initialize database connection
    public function __construct() {
        $this->db = config::getConnexion();
    }

    
    public function getId()            : int    { return $this->id; }
    public function getNom()           : string { return $this->nom; }
    public function getPrenom()        : string { return $this->prenom; }
    public function getEmail()         : string { return $this->email; }
    public function getNumTel()        : string { return $this->numTel; }
    public function getDateNaissance() : string { return $this->date_naissance; }
    public function getAdresse()       : string { return $this->adresse; }
    public function getCin()           : string { return $this->cin; }
    public function getStatusKyc()     : string { return $this->status_kyc; }
    public function getStatusAml()     : string { return $this->status_aml; }
    public function getStatus()        : string { return $this->status; }
    public function getRole()          : string { return $this->role; }
    public function getNiveauAcces()   : int    { return $this->niveau_acces; }
    public function getIdFilePath()   : string { return $this->id_file_path; }
    public function getAssociation()  : bool   { return $this->association; }

    
    
    
    public function setNom(string $v) : void {
        $v = trim($v);
        if (empty($v)) {
            throw new InvalidArgumentException("Le nom est requis.");
        }
        if (strlen($v) < 2) {
            throw new InvalidArgumentException("Le nom doit contenir au moins 2 caractères.");
        }
        if (strlen($v) > 50) {
            throw new InvalidArgumentException("Le nom ne peut pas dépasser 50 caractères.");
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $v)) {
            throw new InvalidArgumentException("Le nom ne peut contenir que des lettres, espaces et tirets.");
        }
        $this->nom = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
    
    
    public function setPrenom(string $v) : void {
        $v = trim($v);
        if (empty($v)) {
            throw new InvalidArgumentException("Le prénom est requis.");
        }
        if (strlen($v) < 2) {
            throw new InvalidArgumentException("Le prénom doit contenir au moins 2 caractères.");
        }
        if (strlen($v) > 50) {
            throw new InvalidArgumentException("Le prénom ne peut pas dépasser 50 caractères.");
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $v)) {
            throw new InvalidArgumentException("Le prénom ne peut contenir que des lettres, espaces et tirets.");
        }
        $this->prenom = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
    
    
    public function setEmail(string $v) : void {
        $v = strtolower(trim($v));
        if (empty($v)) {
            throw new InvalidArgumentException("L'email est requis.");
        }
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Format d'email invalide (exemple: nom@domaine.com).");
        }
        if (strlen($v) > 100) {
            throw new InvalidArgumentException("L'email ne peut pas dépasser 100 caractères.");
        }
        
        $domain = substr(strrchr($v, "@"), 1);
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            
            
        }
        $this->email = $v;
    }
    
    
    public function setMdp(string $v) : void {
        if (empty($v)) {
            throw new InvalidArgumentException("Le mot de passe est requis.");
        }
        if (strlen($v) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères.");
        }
        if (strlen($v) > 255) {
            throw new InvalidArgumentException("Le mot de passe est trop long.");
        }
        
        $force = 0;
        if (preg_match('/[A-Z]/', $v)) $force++;
        if (preg_match('/[a-z]/', $v)) $force++;
        if (preg_match('/[0-9]/', $v)) $force++;
        if (preg_match('/[^A-Za-z0-9]/', $v)) $force++;
        if ($force < 3) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 3 types parmi : majuscules, minuscules, chiffres, caractères spéciaux.");
        }
        $this->mdp = password_hash($v, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    
    public function setNumTel(string $v) : void {
        $v = trim($v);
        if (empty($v)) {
            throw new InvalidArgumentException("Le numéro de téléphone est requis.");
        }
        
        $clean = preg_replace('/[^0-9+]/', '', $v);
        
        if (!preg_match('/^(?:\+216|00216)?[0-9]{8}$/', $clean) && 
            !preg_match('/^[0-9]{8}$/', $clean)) {
            throw new InvalidArgumentException("Numéro de téléphone invalide. Format attendu: +216XXXXXXXX ou 2XXXXXXXX");
        }
        $this->numTel = $clean;
    }
    
    
    public function setDateNaissance(string $v) : void {
        if (empty($v)) {
            throw new InvalidArgumentException("La date de naissance est requise.");
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            throw new InvalidArgumentException("Format de date invalide (YYYY-MM-DD).");
        }
        $date = date_create($v);
        if (!$date) {
            throw new InvalidArgumentException("Date de naissance invalide.");
        }
        $today = new DateTime();
        $age = $today->diff($date)->y;
        if ($age < 18) {
            throw new InvalidArgumentException("Vous devez avoir au moins 18 ans pour vous inscrire.");
        }
        if ($age > 120) {
            throw new InvalidArgumentException("Date de naissance invalide.");
        }
        $this->date_naissance = $v;
    }
    
    
    public function setAdresse(string $v) : void {
        $v = trim($v);
        if (empty($v)) {
            throw new InvalidArgumentException("L'adresse est requise.");
        }
        if (strlen($v) < 5) {
            throw new InvalidArgumentException("L'adresse doit contenir au moins 5 caractères.");
        }
        if (strlen($v) > 255) {
            throw new InvalidArgumentException("L'adresse ne peut pas dépasser 255 caractères.");
        }
        $this->adresse = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
    
    
    public function setCin(string $v) : void {
        $v = trim($v);
        if (empty($v)) {
            throw new InvalidArgumentException("Le CIN est requis.");
        }
        if (!preg_match('/^\d{8}$/', $v)) {
            throw new InvalidArgumentException("Le CIN doit contenir exactement 8 chiffres.");
        }
        
        if ($v === '00000000') {
            throw new InvalidArgumentException("CIN invalide.");
        }
        $this->cin = $v;
    }
    
    
    public function setRole(string $v) : void {
        $allowed = ['CLIENT','ADMIN','SUPER_ADMIN'];
        if (!in_array($v, $allowed)) {
            throw new InvalidArgumentException("Rôle invalide.");
        }
        $this->role         = $v;
        $this->niveau_acces = array_search($v, $allowed) + 1;
    }
    
    
    public function setStatusKyc(string $v) : void {
        if (!in_array($v, ['EN_ATTENTE','VERIFIE','REJETE'])) {
            throw new InvalidArgumentException("Statut KYC invalide.");
        }
        $this->status_kyc = $v;
    }
    
    
    public function setStatusAml(string $v) : void {
        if (!in_array($v, ['EN_ATTENTE','CONFORME','ALERTE'])) {
            throw new InvalidArgumentException("Statut AML invalide.");
        }
        $this->status_aml = $v;
    }
    
    
    public function setStatus(string $v) : void {
        if (!in_array($v, ['ACTIF','INACTIF','SUSPENDU'])) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        $this->status = $v;
    }
    
    
    public function setIdFilePath(string $v) : void {
        $v = trim($v);
        
        if (!empty($v) && !preg_match('/^[a-zA-Z0-9_\-\.\/ \(\)\[\]]+$/', $v)) {
            throw new InvalidArgumentException("Chemin de fichier invalide (caractères spéciaux non autorisés).");
        }
        $this->id_file_path = $v;
    }
    
    
    public function setAssociation(bool $v) : void {
        $this->association = $v;
    }

    
    public function verifyPassword(string $plain, string $hash) : bool {
        return password_verify($plain, $hash);
    }

    
    public function validateForCreate() : void {
        $errors = [];
        
        
        if (empty($this->nom)) $errors[] = "Le nom est requis.";
        if (empty($this->prenom)) $errors[] = "Le prénom est requis.";
        if (empty($this->email)) $errors[] = "L'email est requis.";
        if (empty($this->mdp)) $errors[] = "Le mot de passe est requis.";
        if (empty($this->numTel)) $errors[] = "Le téléphone est requis.";
        if (empty($this->date_naissance)) $errors[] = "La date de naissance est requise.";
        if (empty($this->adresse)) $errors[] = "L'adresse est requise.";
        if (empty($this->cin)) $errors[] = "Le CIN est requis.";
        
        
        if ($this->emailExiste($this->email)) {
            $errors[] = "Cet email est déjà utilisé.";
        }
        if ($this->cinExiste($this->cin)) {
            $errors[] = "Ce CIN est déjà enregistré.";
        }
        
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(" ", $errors));
        }
    }

    
    public function findByEmail(string $email) : ?array {
        $s = $this->db->prepare("SELECT * FROM utilisateur WHERE email=:e LIMIT 1");
        $s->execute([':e' => strtolower(trim($email))]);
        return $s->fetch() ?: null;
    }

    public function findById(int $id) : ?array {
        $s = $this->db->prepare("SELECT * FROM utilisateur WHERE id=:id LIMIT 1");
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    public function findAll(string $filtre = 'tous') : array {
        $where = match($filtre) {
            'clients'     => "WHERE role='CLIENT'",
            'admins'      => "WHERE role='ADMIN'",
            'association' => "WHERE association=1 AND role != 'SUPER_ADMIN'",
            'bloques'     => "WHERE status='SUSPENDU' AND role != 'SUPER_ADMIN'",
            'kyc_attente' => "WHERE status_kyc='EN_ATTENTE' AND role != 'SUPER_ADMIN'",
            default       => "WHERE role != 'SUPER_ADMIN'"
        };
        return $this->db->query("SELECT * FROM utilisateur $where ORDER BY date_inscription DESC")->fetchAll();
    }

    public function getStats(string $periode = 'tout') : array {
        $where = "";
        if ($periode === 'mois') {
            $where = " WHERE MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())";
        } elseif ($periode === 'annee') {
            $where = " WHERE YEAR(date_inscription)=YEAR(NOW())";
        }
        
        $wAnd = $where ? str_replace("WHERE", "AND", $where) : "";
        
        $q = fn($sql) => (int)$this->db->query($sql)->fetchColumn();
        return [
            'total'       => $q("SELECT COUNT(*) FROM utilisateur $where"),
            'actifs'      => $q("SELECT COUNT(*) FROM utilisateur WHERE role='CLIENT' AND status='ACTIF' $wAnd"),
            'kyc'         => $q("SELECT COUNT(*) FROM utilisateur WHERE status_kyc='EN_ATTENTE' $wAnd"),
            'bloques'     => $q("SELECT COUNT(*) FROM utilisateur WHERE status='SUSPENDU' $wAnd"),
            'admins'      => $q("SELECT COUNT(*) FROM utilisateur WHERE role IN ('ADMIN','SUPER_ADMIN') $wAnd"),
            'association' => $q("SELECT COUNT(*) FROM utilisateur WHERE association=1 $wAnd"),
            'mois'        => $q("SELECT COUNT(*) FROM utilisateur WHERE MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())"),
        ];
    }

    
    public function create() : int {
        $this->validateForCreate();
        
        $s = $this->db->prepare(
            "INSERT INTO utilisateur (nom,prenom,email,mdp,numTel,date_naissance,adresse,cin,
             status_kyc,status_aml,status,role,niveau_acces,id_file_path,association,date_creation,date_inscription)
             VALUES (:nom,:prenom,:email,:mdp,:numTel,:dn,:adresse,:cin,
             :skyc,:saml,:status,:role,:na,:file_path,:assoc,CURDATE(),NOW())"
        );
        $s->execute([
            ':nom'=>$this->nom, ':prenom'=>$this->prenom, ':email'=>$this->email,
            ':mdp'=>$this->mdp, ':numTel'=>$this->numTel, ':dn'=>$this->date_naissance,
            ':adresse'=>$this->adresse, ':cin'=>$this->cin,
            ':skyc'=>$this->status_kyc, ':saml'=>$this->status_aml,
            ':status'=>$this->status, ':role'=>$this->role, ':na'=>$this->niveau_acces,
            ':file_path'=>$this->id_file_path, ':assoc'=>(int)$this->association,
        ]);
        $this->id = (int) $this->db->lastInsertId();
        return $this->id;
    }

    
    public function updateProfil(int $id, array $data) : bool {
        
        $this->setNom($data['nom'] ?? '');
        $this->setPrenom($data['prenom'] ?? '');
        $this->setNumTel($data['numTel'] ?? '');
        $this->setAdresse($data['adresse'] ?? '');
        
        $s = $this->db->prepare(
            "UPDATE utilisateur SET nom=:nom,prenom=:prenom,numTel=:tel,adresse=:adresse WHERE id=:id"
        );
        return $s->execute([
            ':nom'    => $this->nom,
            ':prenom' => $this->prenom,
            ':tel'    => $this->numTel,
            ':adresse'=> $this->adresse,
            ':id'     => $id,
        ]);
    }

    
    public function updateStatuts(int $id, string $status, string $kyc, string $aml, string $role) : bool {
        
        $this->setStatus($status);
        $this->setStatusKyc($kyc);
        $this->setStatusAml($aml);
        $this->setRole($role);
        
        $s  = $this->db->prepare(
            "UPDATE utilisateur SET status=:s,status_kyc=:kyc,status_aml=:aml,role=:role,niveau_acces=:na WHERE id=:id"
        );
        return $s->execute([
            ':s'=>$this->status,
            ':kyc'=>$this->status_kyc,
            ':aml'=>$this->status_aml,
            ':role'=>$this->role,
            ':na'=>$this->niveau_acces,
            ':id'=>$id
        ]);
    }

    
    public function updatePassword(int $id, string $ancien, string $nouveau) : bool {
        
        if (empty($nouveau)) {
            throw new InvalidArgumentException("Le nouveau mot de passe est requis.");
        }
        if (strlen($nouveau) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères.");
        }
        if ($nouveau === $ancien) {
            throw new InvalidArgumentException("Le nouveau mot de passe doit être différent de l'ancien.");
        }
        
        $row = $this->findById($id);
        if (!$row) {
            throw new RuntimeException("Utilisateur non trouvé.");
        }
        if (!$this->verifyPassword($ancien, $row['mdp'])) {
            throw new RuntimeException("L'ancien mot de passe est incorrect.");
        }
        
        $s = $this->db->prepare("UPDATE utilisateur SET mdp=:h WHERE id=:id");
        return $s->execute([
            ':h'=>password_hash($nouveau, PASSWORD_BCRYPT, ['cost'=>12]),
            ':id'=>$id
        ]);
    }

    
    public function resetPassword(int $id, string $newMdp) : bool {
        if (empty($newMdp)) {
            throw new InvalidArgumentException("Le nouveau mot de passe est requis.");
        }
        if (strlen($newMdp) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères.");
        }
        
        $s = $this->db->prepare("UPDATE utilisateur SET mdp=:h WHERE id=:id");
        return $s->execute([
            ':h'=>password_hash($newMdp, PASSWORD_BCRYPT, ['cost'=>12]),
            ':id'=>$id
        ]);
    }

    
    public function updateDerniereConnexion(int $id) : bool {
        $s = $this->db->prepare("UPDATE utilisateur SET derniere_connexion=NOW() WHERE id=:id");
        return $s->execute([':id'=>$id]);
    }

    
    public function updateFilePath(int $id, string $filePath) : bool {
        $this->setIdFilePath($filePath);
        $s = $this->db->prepare("UPDATE utilisateur SET id_file_path=:fp WHERE id=:id");
        return $s->execute([':fp'=>$this->id_file_path, ':id'=>$id]);
    }

    
    public function updateAssociation(int $id, bool $assoc) : bool {
        $this->setAssociation($assoc);
        $s = $this->db->prepare("UPDATE utilisateur SET association=:a WHERE id=:id");
        return $s->execute([':a'=>(int)$this->association, ':id'=>$id]);
    }

    public function updateAmlScore(int $id, int $score, array $reasons) : bool {
        $s = $this->db->prepare("UPDATE utilisateur SET aml_score=:score, aml_reasons=:reasons WHERE id=:id");
        return $s->execute([
            ':score' => $score,
            ':reasons' => json_encode($reasons),
            ':id' => $id
        ]);
    }

    public function updateOcrResult(int $id, array $ocrData) : bool {
        $s = $this->db->prepare("UPDATE utilisateur SET ocr_result=:res WHERE id=:id");
        return $s->execute([
            ':res' => json_encode($ocrData),
            ':id' => $id
        ]);
    }

    
    public function delete(int $id) : bool {
        
        if (!$this->findById($id)) {
            throw new RuntimeException("Utilisateur non trouvé.");
        }
        $s = $this->db->prepare("DELETE FROM utilisateur WHERE id=:id");
        return $s->execute([':id'=>$id]);
    }

    
    public function emailExiste(string $email, int $excludeId = 0) : bool {
        $s = $this->db->prepare("SELECT COUNT(*) FROM utilisateur WHERE email=:e AND id!=:id");
        $s->execute([':e'=>strtolower(trim($email)), ':id'=>$excludeId]);
        return (int)$s->fetchColumn() > 0;
    }

    public function cinExiste(string $cin, int $excludeId = 0) : bool {
        $s = $this->db->prepare("SELECT COUNT(*) FROM utilisateur WHERE cin=:c AND id!=:id");
        $s->execute([':c'=>trim($cin), ':id'=>$excludeId]);
        return (int)$s->fetchColumn() > 0;
    }
}


