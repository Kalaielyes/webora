<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/cheque.php';

class ChequeController {

    public function addCheque(Cheque $cheque) {
        $db = Config::getConnexion();
        $db->beginTransaction();
        try {
            $sql = "INSERT INTO cheque (id_chequier, numero_cheque, montant, date_emission, beneficiaire, rib_beneficiaire, cin_beneficiaire, lettres, agence) 
                    VALUES (:id_chequier, :num_cheque, :montant, :date_e, :benef, :rib, :cin, :lettres, :agence)";
            
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

            $sqlUpdate = "UPDATE chequier SET nombre_feuilles = nombre_feuilles - 1 
                          WHERE id_chequier = :id AND nombre_feuilles > 0";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->execute([':id' => $cheque->getIdChequier()]);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function listChequesByChequier(int $id_chequier) {
        $db = Config::getConnexion();
        $sql = "SELECT * FROM cheque WHERE id_chequier = :id ORDER BY date_emission DESC, id_cheque DESC";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id_chequier]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getChequeById(int $id_cheque) {
        $db = Config::getConnexion();
        try {
            $stmt = $db->prepare("SELECT * FROM cheque WHERE id_cheque = :id");
            $stmt->execute([':id' => $id_cheque]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

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
            $stmt->execute([
                ':montant' => $cheque->getMontant(),
                ':date_e'  => $cheque->getDateEmission(),
                ':benef'   => $cheque->getBeneficiaire(),
                ':rib'     => $cheque->getRibBeneficiaire(),
                ':cin'     => $cheque->getCinBeneficiaire(),
                ':lettres' => $cheque->getLettres(),
                ':agence'  => $cheque->getAgence(),
                ':id'      => $id_cheque
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

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

                $stmtUp = $db->prepare("UPDATE chequier SET nombre_feuilles = nombre_feuilles + 1 WHERE id_chequier = :id_chequier");
                $stmtUp->execute([':id_chequier' => $row['id_chequier']]);
                
                $db->commit();
                return true;
            }
            $db->rollBack();
            return false;
        } catch (PDOException $e) {
            $db->rollBack();
        return false;
        }
    }

    /*
    public function generateChequePDF($chequeId) {
        require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

        $cheque = Cheque::getChequeById($chequeId);
        if (!$cheque) {
            throw new Exception("Chèque introuvable.");
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        $pdf->Cell(0, 10, 'Attestation de Chèque', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Titulaire : ' . $cheque->getBeneficiaire(), 0, 1);
        $pdf->Cell(0, 10, 'Numéro de chèque : ' . $cheque->getNumeroCheque(), 0, 1);
        $pdf->Cell(0, 10, 'Montant : ' . $cheque->getMontant() . ' TND', 0, 1);
        $pdf->Cell(0, 10, 'Date d\'émission : ' . $cheque->getDateEmission(), 0, 1);
        $pdf->Cell(0, 10, 'RIB Bénéficiaire : ' . $cheque->getRibBeneficiaire(), 0, 1);
        $pdf->Cell(0, 10, 'CIN Bénéficiaire : ' . $cheque->getCinBeneficiaire(), 0, 1);

        $fileName = 'cheque_attestation_' . $chequeId . '.pdf';
        $filePath = __DIR__ . '/../exports/' . $fileName;
        $pdf->Output('F', $filePath);

        return $fileName;
    }
    */
}
?>
