<?php
/**
 * ChequeController - webora Integration
 * Manages individual cheques
 */

require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Cheque.php';

class ChequeController {

    // ── Add new cheque ─────────────────────────────────────────────────────
    public function addCheque(Cheque $cheque) {
        $db = Config::getConnexion();
        $db->beginTransaction();
        try {
            $sql = "INSERT INTO cheque 
                    (id_chequier, numero_cheque, montant, date_emission, beneficiaire, 
                     rib_beneficiaire, cin_beneficiaire, lettres, agence) 
                    VALUES 
                    (:id_chequier, :num_cheque, :montant, :date_e, :benef, 
                     :rib, :cin, :lettres, :agence)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_chequier'   => $cheque->getIdChequier(),
                ':num_cheque'    => $cheque->getNumeroCheque(),
                ':montant'       => $cheque->getMontant(),
                ':date_e'        => $cheque->getDateEmission(),
                ':benef'         => $cheque->getBeneficiaire(),
                ':rib'           => $cheque->getRibBeneficiaire(),
                ':cin'           => $cheque->getCinBeneficiaire(),
                ':lettres'       => $cheque->getLettres(),
                ':agence'        => $cheque->getAgence()
            ]);

            // Decrement chequier sheet count
            $sqlUpdate = "UPDATE chequier SET nombre_feuilles = nombre_feuilles - 1 
                          WHERE id_chequier = :id AND nombre_feuilles > 0";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->execute([':id' => $cheque->getIdChequier()]);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('[LegaFin] ChequeController::addCheque - ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Get cheque by ID ────────────────────────────────────────────────────
    public function getChequeById(int $id_cheque) {
        $db = Config::getConnexion();
        try {
            $stmt = $db->prepare("SELECT * FROM cheque WHERE id_cheque = :id");
            $stmt->execute([':id' => $id_cheque]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequeController::getChequeById - ' . $e->getMessage());
            return null;
        }
    }

    // ── List cheques by chequier ────────────────────────────────────────────
    public function listChequesByChequier(int $id_chequier) {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM cheque 
                WHERE id_chequier = :id 
                ORDER BY date_emission DESC, id_cheque DESC";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id_chequier]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequeController::listChequesByChequier - ' . $e->getMessage());
            return [];
        }
    }

    // ── Update cheque ──────────────────────────────────────────────────────
    public function updateCheque(Cheque $cheque, int $id_cheque) {
        $db = Config::getConnexion();
        $sql = "UPDATE cheque 
                SET montant = :montant,
                    date_emission = :date_e,
                    beneficiaire = :benef,
                    rib_beneficiaire = :rib,
                    cin_beneficiaire = :cin,
                    lettres = :lettres,
                    agence = :agence
                WHERE id_cheque = :id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':montant' => $cheque->getMontant(),
                ':date_e'  => $cheque->getDateEmission(),
                ':benef'   => $cheque->getBeneficiaire(),
                ':rib'     => $cheque->getRibBeneficiaire(),
                ':cin'     => $cheque->getCinBeneficiaire(),
                ':lettres' => $cheque->getLettres(),
                ':agence'  => $cheque->getAgence(),
                ':id'      => $id_cheque
            ]);
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequeController::updateCheque - ' . $e->getMessage());
            return false;
        }
    }

    // ── Delete cheque ──────────────────────────────────────────────────────
    public function deleteCheque(int $id_cheque) {
        $db = Config::getConnexion();
        $db->beginTransaction();
        try {
            $stmtGet = $db->prepare("SELECT id_chequier FROM cheque WHERE id_cheque = :id");
            $stmtGet->execute([':id' => $id_cheque]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $stmtDel = $db->prepare("DELETE FROM cheque WHERE id_cheque = :id");
                $stmtDel->execute([':id' => $id_cheque]);

                // Increment chequier sheet count
                $stmtUp = $db->prepare("UPDATE chequier SET nombre_feuilles = nombre_feuilles + 1 WHERE id_chequier = :id_chequier");
                $stmtUp->execute([':id_chequier' => $row['id_chequier']]);
                
                $db->commit();
                return true;
            }
            $db->rollBack();
            return false;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('[LegaFin] ChequeController::deleteCheque - ' . $e->getMessage());
            return false;
        }
    }

    // ── Verify cheque (for validation) ─────────────────────────────────────
    public function verifyCheque(int $id_cheque): bool {
        $cheque = $this->getChequeById($id_cheque);
        return $cheque !== null && !empty($cheque['numero_cheque']);
    }

    // ── Search cheques ────────────────────────────────────────────────────
    public function searchCheques(string $searchTerm): array {
        $db = Config::getConnexion();
        $sql = "SELECT c.*, ch.numero_chequier 
                FROM cheque c
                LEFT JOIN chequier ch ON c.id_chequier = ch.id_chequier
                WHERE c.numero_cheque LIKE :term 
                   OR c.beneficiaire LIKE :term 
                   OR c.cin_beneficiaire LIKE :term
                ORDER BY c.date_emission DESC";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':term' => '%' . $searchTerm . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequeController::searchCheques - ' . $e->getMessage());
            return [];
        }
    }

    // ── Get cheques statistics ─────────────────────────────────────────────
    public function getChequeStats(): array {
        $db = Config::getConnexion();
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM cheque");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $stmt = $db->query("SELECT SUM(montant) as montant_total FROM cheque");
            $montantTotal = $stmt->fetch(PDO::FETCH_ASSOC)['montant_total'] ?? 0;

            return [
                'total_cheques' => $total,
                'montant_total' => $montantTotal
            ];
        } catch (PDOException $e) {
            error_log('[LegaFin] ChequeController::getChequeStats - ' . $e->getMessage());
            return ['total_cheques' => 0, 'montant_total' => 0];
        }
    }
}
