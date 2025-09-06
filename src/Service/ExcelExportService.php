<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class ExcelExportService
{
    public function exportFormationsToExcel(array $formations, array $filters = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Formations');

        // En-têtes
        $headers = [
            'A1' => 'ID',
            'B1' => 'Titre',
            'C1' => 'Service',
            'D1' => 'Date de début',
            'E1' => 'Date de fin',
            'F1' => 'Durée prévue (jours)',
            'G1' => 'Durée réelle (jours)',
            'H1' => 'Lieu prévu',
            'I1' => 'Lieu réel',
            'J1' => 'Budget prévu (FCFA)',
            'K1' => 'Budget réel (FCFA)',
            'L1' => 'Statut',
            'M1' => 'Nombre de participants',
            'N1' => 'Participants présents',
            'O1' => 'Date de création'
        ];

        // Appliquer les en-têtes
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes
        $headerRange = 'A1:O1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Données
        $row = 2;
        foreach ($formations as $formation) {
            // Calculer les totaux
            $totalParticipants = $formation->getUserFormations()->count();
            $participantsPresents = $formation->getUserFormations()->filter(function($uf) {
                return $uf->getStatutParticipation() && $uf->getStatutParticipation()->getCode() === 'participe';
            })->count();

            $budgetPrevu = 0;
            $budgetReel = 0;
            foreach ($formation->getDepenseFormations() as $depense) {
                $budgetPrevu += $depense->getMontantPrevu();
                if ($depense->getMontantReel()) {
                    $budgetReel += $depense->getMontantReel();
                }
            }

            $sheet->setCellValue('A' . $row, $formation->getId());
            $sheet->setCellValue('B' . $row, $formation->getTitre());
            $sheet->setCellValue('C' . $row, $formation->getService() ? $formation->getService()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $formation->getDatePrevueDebut() ? $formation->getDatePrevueDebut()->format('d/m/Y') : '');
            $sheet->setCellValue('E' . $row, $formation->getDatePrevueFin() ? $formation->getDatePrevueFin()->format('d/m/Y') : '');
            $sheet->setCellValue('F' . $row, $formation->getDureePrevue());
            $sheet->setCellValue('G' . $row, $formation->getDureeReelle() ?: '');
            $sheet->setCellValue('H' . $row, $formation->getLieuPrevu());
            $sheet->setCellValue('I' . $row, $formation->getLieuReel() ?: '');
            $sheet->setCellValue('J' . $row, number_format($budgetPrevu, 0, ',', ' '));
            $sheet->setCellValue('K' . $row, $budgetReel > 0 ? number_format($budgetReel, 0, ',', ' ') : '');
            $sheet->setCellValue('L' . $row, $formation->getStatutActivite() ? $formation->getStatutActivite()->getLibelle() : '');
            $sheet->setCellValue('M' . $row, $totalParticipants);
            $sheet->setCellValue('N' . $row, $participantsPresents);
            $sheet->setCellValue('O' . $row, $formation->getDatePrevueDebut() ? $formation->getDatePrevueDebut()->format('d/m/Y H:i') : '');

            $row++;
        }

        // Ajuster la largeur des colonnes
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Ajouter des bordures aux données
        $dataRange = 'A1:O' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Créer la réponse
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="formations_' . date('Y-m-d_H-i-s') . '.xlsx"');

        ob_start();
        $writer->save('php://output');
        $response->setContent(ob_get_clean());

        return $response;
    }

    public function exportMissionsToExcel(array $missions, array $filters = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Missions');

        // En-têtes
        $headers = [
            'A1' => 'ID',
            'B1' => 'Titre',
            'C1' => 'Direction',
            'D1' => 'Date de début',
            'E1' => 'Date de fin',
            'F1' => 'Durée prévue (jours)',
            'G1' => 'Durée réelle (jours)',
            'H1' => 'Lieu prévu',
            'I1' => 'Lieu réel',
            'J1' => 'Budget prévu (FCFA)',
            'K1' => 'Budget réel (FCFA)',
            'L1' => 'Statut',
            'M1' => 'Nombre de participants',
            'N1' => 'Participants présents',
            'O1' => 'Date de création'
        ];

        // Appliquer les en-têtes
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes
        $headerRange = 'A1:O1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Données
        $row = 2;
        foreach ($missions as $mission) {
            // Calculer les totaux
            $totalParticipants = $mission->getUserMissions()->count();
            $participantsPresents = $mission->getUserMissions()->filter(function($um) {
                return $um->getStatutParticipation() && $um->getStatutParticipation()->getCode() === 'participe';
            })->count();

            $budgetPrevu = 0;
            $budgetReel = 0;
            foreach ($mission->getDepenseMissions() as $depense) {
                $budgetPrevu += $depense->getMontantPrevu();
                if ($depense->getMontantReel()) {
                    $budgetReel += $depense->getMontantReel();
                }
            }

            $sheet->setCellValue('A' . $row, $mission->getId());
            $sheet->setCellValue('B' . $row, $mission->getTitre());
            $sheet->setCellValue('C' . $row, $mission->getDirection() ? $mission->getDirection()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('d/m/Y') : '');
            $sheet->setCellValue('E' . $row, $mission->getDatePrevueFin() ? $mission->getDatePrevueFin()->format('d/m/Y') : '');
            $sheet->setCellValue('F' . $row, $mission->getDureePrevue());
            $sheet->setCellValue('G' . $row, $mission->getDureeReelle() ?: '');
            $sheet->setCellValue('H' . $row, $mission->getLieuPrevu());
            $sheet->setCellValue('I' . $row, $mission->getLieuReel() ?: '');
            $sheet->setCellValue('J' . $row, number_format($budgetPrevu, 0, ',', ' '));
            $sheet->setCellValue('K' . $row, $budgetReel > 0 ? number_format($budgetReel, 0, ',', ' ') : '');
            $sheet->setCellValue('L' . $row, $mission->getStatutActivite() ? $mission->getStatutActivite()->getLibelle() : '');
            $sheet->setCellValue('M' . $row, $totalParticipants);
            $sheet->setCellValue('N' . $row, $participantsPresents);
            $sheet->setCellValue('O' . $row, $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('d/m/Y H:i') : '');

            $row++;
        }

        // Ajuster la largeur des colonnes
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Ajouter des bordures aux données
        $dataRange = 'A1:O' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Créer la réponse
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="missions_' . date('Y-m-d_H-i-s') . '.xlsx"');

        ob_start();
        $writer->save('php://output');
        $response->setContent(ob_get_clean());

        return $response;
    }
}
