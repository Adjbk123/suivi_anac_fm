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
        foreach ($formations as $session) {
            // $formations contient maintenant des FormationSession
            $formation = $session->getFormation();
            
            // Calculer les totaux
            $totalParticipants = $session->getUserFormations()->count();
            $participantsPresents = $session->getUserFormations()->filter(function($uf) {
                return $uf->getStatutParticipation() && $uf->getStatutParticipation()->getCode() === 'participe';
            })->count();

            $budgetPrevu = (float)$session->getBudgetPrevu();
            $budgetReel = $session->getBudgetReel() ? (float)$session->getBudgetReel() : 0;

            $sheet->setCellValue('A' . $row, $session->getId());
            $sheet->setCellValue('B' . $row, $formation ? $formation->getTitre() : '');
            $sheet->setCellValue('C' . $row, $session->getDirection() ? $session->getDirection()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '');
            $sheet->setCellValue('E' . $row, $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '');
            $sheet->setCellValue('F' . $row, $session->getDureePrevue());
            $sheet->setCellValue('G' . $row, $session->getDureeReelle() ?: '');
            $sheet->setCellValue('H' . $row, $session->getLieuPrevu());
            $sheet->setCellValue('I' . $row, $session->getLieuReel() ?: '');
            $sheet->setCellValue('J' . $row, number_format($budgetPrevu, 0, ',', ' '));
            $sheet->setCellValue('K' . $row, $budgetReel > 0 ? number_format($budgetReel, 0, ',', ' ') : '');
            $sheet->setCellValue('L' . $row, $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '');
            $sheet->setCellValue('M' . $row, $totalParticipants);
            $sheet->setCellValue('N' . $row, $participantsPresents);
            $sheet->setCellValue('O' . $row, $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y H:i') : '');

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

    public function exportMissionsToExcel(array $missionSessions, array $filters = []): Response
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
        foreach ($missionSessions as $session) {
            $mission = $session->getMission();

            $totalParticipants = $session->getUserMissions()->count();
            $participantsPresents = $session->getUserMissions()->filter(static function ($um) {
                return $um->getStatutParticipation() && $um->getStatutParticipation()->getCode() === 'participe';
            })->count();

            $budgetPrevu = (float) $session->getBudgetPrevu();
            $budgetReel = $session->getBudgetReel() ? (float) $session->getBudgetReel() : 0;

            $sheet->setCellValue('A' . $row, $session->getId());
            $sheet->setCellValue('B' . $row, $mission?->getTitre() ?? '');
            $sheet->setCellValue('C' . $row, $session->getDirection() ? $session->getDirection()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '');
            $sheet->setCellValue('E' . $row, $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '');
            $sheet->setCellValue('F' . $row, $session->getDureePrevue());
            $sheet->setCellValue('G' . $row, $session->getDureeReelle() ?: '');
            $sheet->setCellValue('H' . $row, $session->getLieuPrevu());
            $sheet->setCellValue('I' . $row, $session->getLieuReel() ?: '');
            $sheet->setCellValue('J' . $row, number_format($budgetPrevu, 0, ',', ' '));
            $sheet->setCellValue('K' . $row, $budgetReel > 0 ? number_format($budgetReel, 0, ',', ' ') : '');
            $sheet->setCellValue('L' . $row, $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '');
            $sheet->setCellValue('M' . $row, $totalParticipants);
            $sheet->setCellValue('N' . $row, $participantsPresents);
            $sheet->setCellValue('O' . $row, $mission?->getCreatedAt()?->format('d/m/Y H:i') ?? '');

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

    public function exportMissionBudgetReport(array $missionSessions, array $filters = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Suivi Budget Missions');

        // Titre principal simple
        $sheet->setCellValue('A1', 'BUDGET EXERCICE 2025 - TABLEAU DE SUIVI DES MISSIONS EFFECTUEES');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Période simple
        $periode = '1er janvier au 30 juin 2025';
        if (isset($filters['date_debut']) && isset($filters['date_fin'])) {
            $periode = $filters['date_debut'] . ' au ' . $filters['date_fin'];
        }
        $sheet->setCellValue('A2', 'Période: ' . $periode);
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension('2')->setRowHeight(20);

        // Ligne vide pour l'espacement
        $sheet->getRowDimension('3')->setRowHeight(10);

        // En-têtes simplifiés avec une seule colonne DÉPENSES
        $headers = [
            'A4' => 'N°',
            'B4' => 'BÉNÉFICIAIRES',
            'C4' => 'DIRECTION',
            'D4' => 'INTITULÉ DE LA MISSION',
            'E4' => 'LIEU',
            'F4' => 'PÉRIODE',
            'G4' => 'DÉPENSES',
            'H4' => 'TOTAL',
            'I4' => 'Budget ANAC',
            'J4' => 'Budget Extérieur',
            'K4' => 'Missions prévues et exécutées',
            'L4' => 'Missions non prévues et exécutées'
        ];

        // Appliquer les en-têtes
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes simple
        $headerRange = 'A4:L4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getRowDimension('4')->setRowHeight(30);

        // Données des missions avec une seule colonne DÉPENSES
        $row = 5;
        $totalGeneral = 0;
        $totalBudgetAnac = 0;
        $totalBudgetExterieur = 0;

        foreach ($missionSessions as $index => $session) {
            $mission = $session->getMission();
            // Récupérer les bénéficiaires
            $beneficiaires = [];
            foreach ($session->getUserMissions() as $userMission) {
                $user = $userMission->getUser();
                $beneficiaires[] = $user->getNom() . ' ' . $user->getPrenom();
            }
            $beneficiairesStr = implode(', ', $beneficiaires);

            // Période
            $periodeMission = '';
            if ($session->getDatePrevueDebut() && $session->getDatePrevueFin()) {
                $periodeMission = $session->getDatePrevueDebut()->format('d/m/Y') . ' - ' . $session->getDatePrevueFin()->format('d/m/Y');
            }

            // Statut d'exécution
            $statutCode = $session->getStatutActivite() ? $session->getStatutActivite()->getCode() : '';
            $prevueEtExecutee = in_array($statutCode, ['prevue_executee', 'executee']) ? 'OUI' : 'NON';
            $nonPrevueEtExecutee = in_array($statutCode, ['non_prevue_executee']) ? 'OUI' : 'NON';

            // Calculer les totaux de la mission
            $totalMission = 0;
            $budgetAnac = 0;
            $budgetExterieur = 0;
            $depensesDetails = [];

            foreach ($session->getDepenseMissions() as $depense) {
                $montant = (float) $depense->getMontantReel() ?: (float) $depense->getMontantPrevu();
                $categorieLibelle = $depense->getCategorie()->getLibelle();
                
                $totalMission += $montant;
                $depensesDetails[] = $categorieLibelle . ': ' . number_format($montant, 0, ',', ' ') . ' F CFA';

                // Déterminer le type de budget
                if ($session->getFonds() && strpos(strtolower($session->getFonds()->getLibelle()), 'anac') !== false) {
                    $budgetAnac += $montant;
                } else {
                    $budgetExterieur += $montant;
                }
            }

            $totalGeneral += $totalMission;
            $totalBudgetAnac += $budgetAnac;
            $totalBudgetExterieur += $budgetExterieur;

            // Remplir les données de la mission
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $beneficiairesStr);
            $sheet->setCellValue('C' . $row, $session->getDirection() ? $session->getDirection()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $mission?->getTitre() ?? '');
            $sheet->setCellValue('E' . $row, $session->getLieuPrevu());
            $sheet->setCellValue('F' . $row, $periodeMission);
            
            // Colonne DÉPENSES avec bordures Excel pour chaque dépense
            if (!empty($depensesDetails)) {
                // Créer une cellule avec toutes les dépenses séparées par des retours à la ligne
                $depensesText = implode("\n", $depensesDetails);
                $sheet->setCellValue('G' . $row, $depensesText);
                
                // Appliquer des bordures pour séparer visuellement les dépenses
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        'inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                    ]
                ]);
            } else {
                $sheet->setCellValue('G' . $row, 'Aucune dépense');
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                    ]
                ]);
            }
            
            // Colonnes finales
            $sheet->setCellValue('H' . $row, $totalMission > 0 ? number_format($totalMission, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('I' . $row, $budgetAnac > 0 ? number_format($budgetAnac, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('J' . $row, $budgetExterieur > 0 ? number_format($budgetExterieur, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('K' . $row, $prevueEtExecutee);
            $sheet->setCellValue('L' . $row, $nonPrevueEtExecutee);

            $row++;
        }

        // Ligne de totaux simplifiée
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL GÉNÉRAL');
        $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
        $sheet->setCellValue('G' . $totalRow, ''); // Colonne DÉPENSES vide pour les totaux
        $sheet->setCellValue('H' . $totalRow, number_format($totalGeneral, 0, ',', ' ') . ' F CFA');
        $sheet->setCellValue('I' . $totalRow, number_format($totalBudgetAnac, 0, ',', ' ') . ' F CFA');
        $sheet->setCellValue('J' . $totalRow, number_format($totalBudgetExterieur, 0, ',', ' ') . ' F CFA');

        // Style de la ligne de totaux simple
        $totalRange = 'A' . $totalRow . ':L' . $totalRow;
        $sheet->getStyle($totalRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getRowDimension($totalRow)->setRowHeight(25);

        // Section des statistiques budgétaires avec données réelles
        $statsRow = $totalRow + 2;
        
        // Titre de la section statistiques
        $sheet->setCellValue('A' . $statsRow, 'RÉSUMÉ BUDGÉTAIRE');
        $sheet->mergeCells('A' . $statsRow . ':B' . $statsRow);
        $sheet->getStyle('A' . $statsRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension($statsRow)->setRowHeight(20);
        
        // Calculer les totaux par catégorie de dépense (éviter les doublons)
        $categoriesDepensesStats = [];
        $totalBudgetPrevu = 0;
        
        foreach ($missionSessions as $session) {
            $mission = $session->getMission();
            foreach ($mission->getDepenseMissions() as $depense) {
                $categorieLibelle = $depense->getCategorie()->getLibelle();
                $montantPrevu = (float) $depense->getMontantPrevu();
                $montantReel = (float) $depense->getMontantReel();
                
                // Initialiser la catégorie si elle n'existe pas
                if (!isset($categoriesDepensesStats[$categorieLibelle])) {
                    $categoriesDepensesStats[$categorieLibelle] = [
                        'prevue' => 0,
                        'reelle' => 0
                    ];
                }
                
                // Ajouter les montants (éviter les doublons)
                $categoriesDepensesStats[$categorieLibelle]['prevue'] += $montantPrevu;
                $categoriesDepensesStats[$categorieLibelle]['reelle'] += $montantReel;
                $totalBudgetPrevu += $montantPrevu;
            }
        }
        
        // Trier les catégories par ordre alphabétique pour un affichage cohérent
        ksort($categoriesDepensesStats);
        
        // Afficher chaque catégorie de dépense de manière plus claire
        foreach ($categoriesDepensesStats as $categorie => $montants) {
            $statsRow++;
            $sheet->setCellValue('A' . $statsRow, $categorie . ' (Prévu):');
            $sheet->setCellValue('B' . $statsRow, number_format($montants['prevue'], 0, ',', ' ') . ' F CFA');
            
            $statsRow++;
            $sheet->setCellValue('A' . $statsRow, $categorie . ' (Réalisé):');
            $sheet->setCellValue('B' . $statsRow, number_format($montants['reelle'], 0, ',', ' ') . ' F CFA');
        }
        
        // Ligne de séparation
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, '─────────────────────────');
        $sheet->setCellValue('B' . $statsRow, '─────────────────────────');
        
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, 'TOTAL BUDGÉTISÉ:');
        $sheet->setCellValue('B' . $statsRow, number_format($totalBudgetPrevu, 0, ',', ' ') . ' F CFA');
        
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, 'TOTAL RÉALISÉ:');
        $sheet->setCellValue('B' . $statsRow, number_format($totalGeneral, 0, ',', ' ') . ' F CFA');
        
        $statsRow++;
        $tauxExecution = $totalBudgetPrevu > 0 ? ($totalGeneral / $totalBudgetPrevu) * 100 : 0;
        $sheet->setCellValue('A' . $statsRow, 'TAUX D\'EXÉCUTION FINANCIÈRE:');
        $sheet->setCellValue('B' . $statsRow, number_format($tauxExecution, 2) . '%');

        // Style des statistiques simple
        $statsDataRange = 'A' . ($totalRow + 3) . ':B' . $statsRow;
        $sheet->getStyle($statsDataRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        // Alignement spécial pour les valeurs (colonne B)
        $sheet->getStyle('B' . ($totalRow + 3) . ':B' . $statsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Ajuster la largeur des colonnes de manière optimale (plus large pour éviter la troncature)
        $sheet->getColumnDimension('A')->setWidth(60);  // N° et Labels du résumé (très large pour éviter la troncature)
        $sheet->getColumnDimension('B')->setWidth(30);  // Bénéficiaires et Valeurs du résumé
        $sheet->getColumnDimension('C')->setWidth(20);  // Direction
        $sheet->getColumnDimension('D')->setWidth(50);  // Intitulé
        $sheet->getColumnDimension('E')->setWidth(20);  // Lieu
        $sheet->getColumnDimension('F')->setWidth(25);  // Période
        $sheet->getColumnDimension('G')->setWidth(45);  // DÉPENSES (beaucoup plus large pour contenir toutes les dépenses)
        $sheet->getColumnDimension('H')->setWidth(18);  // Total
        $sheet->getColumnDimension('I')->setWidth(18);  // Budget ANAC
        $sheet->getColumnDimension('J')->setWidth(18);  // Budget Extérieur
        $sheet->getColumnDimension('K')->setWidth(25);  // Prévues et exécutées
        $sheet->getColumnDimension('L')->setWidth(30);  // Non prévues et exécutées

        // Style des données simple
        $dataRange = 'A5:L' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'font' => ['size' => 10]
        ]);

        // Alignement spécial pour certaines colonnes
        $sheet->getStyle('D5:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B5:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G5:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Colonne DÉPENSES alignée à gauche
        
        // Ajuster la hauteur des lignes de données (plus haute pour éviter la troncature)
        for ($i = 5; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(60); // Plus haute pour contenir toutes les dépenses sans troncature
        }

        // Créer la réponse
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport_budget_missions_' . date('Y-m-d_H-i-s') . '.xlsx"');

        ob_start();
        $writer->save('php://output');
        $response->setContent(ob_get_clean());

        return $response;
    }

    /**
     * Export du rapport budgétaire des formations
     */
    public function exportFormationBudgetReport(array $formations, array $filters = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Titre principal
        $sheet->setCellValue('A1', 'EXERCICE 2025 - TABLEAU DE SUIVI DES FORMATIONS EFFECTUÉES');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension('1')->setRowHeight(25);

        // Période
        $periode = '1er janvier au 30 juin 2025';
        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $periode = $filters['date_debut'] . ' au ' . $filters['date_fin'];
        }
        $sheet->setCellValue('A2', 'Période: ' . $periode);
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension('2')->setRowHeight(20);

        // Ligne vide pour l'espacement
        $sheet->getRowDimension('3')->setRowHeight(10);

        // En-têtes simplifiés avec une seule colonne DÉPENSES
        $headers = [
            'A4' => 'N°',
            'B4' => 'BÉNÉFICIAIRES',
            'C4' => 'DIRECTION',
            'D4' => 'INTITULÉ DE LA FORMATION',
            'E4' => 'LIEU',
            'F4' => 'PÉRIODE',
            'G4' => 'DÉPENSES',
            'H4' => 'TOTAL',
            'I4' => 'Budget ANAC',
            'J4' => 'Budget Extérieur',
            'K4' => 'Formations prévues et exécutées',
            'L4' => 'Formations non prévues et exécutées'
        ];

        // Appliquer les en-têtes
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes simple
        $headerRange = 'A4:L4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getRowDimension('4')->setRowHeight(30);

        // Données des formations avec une seule colonne DÉPENSES
        $row = 5;
        $totalGeneral = 0;
        $totalBudgetAnac = 0;
        $totalBudgetExterieur = 0;

        foreach ($formations as $index => $session) {
            // $formations contient maintenant des FormationSession
            $formation = $session->getFormation();
            
            // Récupérer les bénéficiaires
            $beneficiaires = [];
            foreach ($session->getUserFormations() as $userFormation) {
                $user = $userFormation->getUser();
                $beneficiaires[] = $user->getNom() . ' ' . $user->getPrenom();
            }
            $beneficiairesStr = implode(', ', $beneficiaires);

            // Période
            $periodeFormation = '';
            if ($session->getDatePrevueDebut() && $session->getDatePrevueFin()) {
                $periodeFormation = $session->getDatePrevueDebut()->format('d/m/Y') . ' - ' . $session->getDatePrevueFin()->format('d/m/Y');
            }

            // Statut d'exécution
            $statutCode = $session->getStatutActivite() ? $session->getStatutActivite()->getCode() : '';
            $prevueEtExecutee = in_array($statutCode, ['prevue_executee', 'executee']) ? 'OUI' : 'NON';
            $nonPrevueEtExecutee = in_array($statutCode, ['non_prevue_executee']) ? 'OUI' : 'NON';

            // Calculer les totaux de la session
            $totalFormation = 0;
            $budgetAnac = 0;
            $budgetExterieur = 0;
            $depensesDetails = [];

            foreach ($session->getDepenseFormations() as $depense) {
                $montant = (float) $depense->getMontantReel() ?: (float) $depense->getMontantPrevu();
                $categorieLibelle = $depense->getCategorie()->getLibelle();
                
                $totalFormation += $montant;
                $depensesDetails[] = $categorieLibelle . ': ' . number_format($montant, 0, ',', ' ') . ' F CFA';

                // Déterminer le type de budget
                if ($session->getFonds() && strpos(strtolower($session->getFonds()->getLibelle()), 'anac') !== false) {
                    $budgetAnac += $montant;
                } else {
                    $budgetExterieur += $montant;
                }
            }

            $totalGeneral += $totalFormation;
            $totalBudgetAnac += $budgetAnac;
            $totalBudgetExterieur += $budgetExterieur;

            // Remplir les données de la session
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $beneficiairesStr);
            $sheet->setCellValue('C' . $row, $session->getDirection() ? $session->getDirection()->getLibelle() : '');
            $sheet->setCellValue('D' . $row, $formation ? $formation->getTitre() : '');
            $sheet->setCellValue('E' . $row, $session->getLieuPrevu());
            $sheet->setCellValue('F' . $row, $periodeFormation);
            
            // Colonne DÉPENSES avec bordures Excel pour chaque dépense
            if (!empty($depensesDetails)) {
                // Créer une cellule avec toutes les dépenses séparées par des retours à la ligne
                $depensesText = implode("\n", $depensesDetails);
                $sheet->setCellValue('G' . $row, $depensesText);
                
                // Appliquer des bordures pour séparer visuellement les dépenses
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        'inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                    ]
                ]);
            } else {
                $sheet->setCellValue('G' . $row, 'Aucune dépense');
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
                    ]
                ]);
            }
            
            // Colonnes finales
            $sheet->setCellValue('H' . $row, $totalFormation > 0 ? number_format($totalFormation, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('I' . $row, $budgetAnac > 0 ? number_format($budgetAnac, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('J' . $row, $budgetExterieur > 0 ? number_format($budgetExterieur, 0, ',', ' ') . ' F CFA' : '0');
            $sheet->setCellValue('K' . $row, $prevueEtExecutee);
            $sheet->setCellValue('L' . $row, $nonPrevueEtExecutee);

            $row++;
        }

        // Ligne de totaux simplifiée
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL GÉNÉRAL');
        $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
        $sheet->setCellValue('G' . $totalRow, ''); // Colonne DÉPENSES vide pour les totaux
        $sheet->setCellValue('H' . $totalRow, number_format($totalGeneral, 0, ',', ' ') . ' F CFA');
        $sheet->setCellValue('I' . $totalRow, number_format($totalBudgetAnac, 0, ',', ' ') . ' F CFA');
        $sheet->setCellValue('J' . $totalRow, number_format($totalBudgetExterieur, 0, ',', ' ') . ' F CFA');

        // Style de la ligne de totaux simple
        $totalRange = 'A' . $totalRow . ':L' . $totalRow;
        $sheet->getStyle($totalRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getRowDimension($totalRow)->setRowHeight(25);

        // Section des statistiques budgétaires avec données réelles
        $statsRow = $totalRow + 2;
        
        // Titre de la section statistiques
        $sheet->setCellValue('A' . $statsRow, 'RÉSUMÉ BUDGÉTAIRE');
        $sheet->mergeCells('A' . $statsRow . ':B' . $statsRow);
        $sheet->getStyle('A' . $statsRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension($statsRow)->setRowHeight(20);
        
        // Calculer les totaux par catégorie de dépense (éviter les doublons)
        $categoriesDepensesStats = [];
        $totalBudgetPrevu = 0;
        
        foreach ($formations as $session) {
            // $formations contient maintenant des FormationSession
            foreach ($session->getDepenseFormations() as $depense) {
                $categorieLibelle = $depense->getCategorie()->getLibelle();
                $montantPrevu = (float) $depense->getMontantPrevu();
                $montantReel = (float) $depense->getMontantReel();
                
                // Initialiser la catégorie si elle n'existe pas
                if (!isset($categoriesDepensesStats[$categorieLibelle])) {
                    $categoriesDepensesStats[$categorieLibelle] = [
                        'prevue' => 0,
                        'reelle' => 0
                    ];
                }
                
                // Ajouter les montants (éviter les doublons)
                $categoriesDepensesStats[$categorieLibelle]['prevue'] += $montantPrevu;
                $categoriesDepensesStats[$categorieLibelle]['reelle'] += $montantReel;
                $totalBudgetPrevu += $montantPrevu;
            }
        }
        
        // Trier les catégories par ordre alphabétique pour un affichage cohérent
        ksort($categoriesDepensesStats);
        
        // Afficher chaque catégorie de dépense de manière plus claire
        foreach ($categoriesDepensesStats as $categorie => $montants) {
            $statsRow++;
            $sheet->setCellValue('A' . $statsRow, $categorie . ' (Prévu):');
            $sheet->setCellValue('B' . $statsRow, number_format($montants['prevue'], 0, ',', ' ') . ' F CFA');
            
            $statsRow++;
            $sheet->setCellValue('A' . $statsRow, $categorie . ' (Réalisé):');
            $sheet->setCellValue('B' . $statsRow, number_format($montants['reelle'], 0, ',', ' ') . ' F CFA');
        }
        
        // Ligne de séparation
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, '─────────────────────────');
        $sheet->setCellValue('B' . $statsRow, '─────────────────────────');
        
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, 'TOTAL BUDGÉTISÉ:');
        $sheet->setCellValue('B' . $statsRow, number_format($totalBudgetPrevu, 0, ',', ' ') . ' F CFA');
        
        $statsRow++;
        $sheet->setCellValue('A' . $statsRow, 'TOTAL RÉALISÉ:');
        $sheet->setCellValue('B' . $statsRow, number_format($totalGeneral, 0, ',', ' ') . ' F CFA');
        
        $statsRow++;
        $tauxExecution = $totalBudgetPrevu > 0 ? ($totalGeneral / $totalBudgetPrevu) * 100 : 0;
        $sheet->setCellValue('A' . $statsRow, 'TAUX D\'EXÉCUTION FINANCIÈRE:');
        $sheet->setCellValue('B' . $statsRow, number_format($tauxExecution, 2) . '%');

        // Style des statistiques simple
        $statsDataRange = 'A' . ($totalRow + 3) . ':B' . $statsRow;
        $sheet->getStyle($statsDataRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        // Alignement spécial pour les valeurs (colonne B)
        $sheet->getStyle('B' . ($totalRow + 3) . ':B' . $statsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Ajuster la largeur des colonnes de manière optimale (plus large pour éviter la troncature)
        $sheet->getColumnDimension('A')->setWidth(60);  // N° et Labels du résumé (très large pour éviter la troncature)
        $sheet->getColumnDimension('B')->setWidth(30);  // Bénéficiaires et Valeurs du résumé
        $sheet->getColumnDimension('C')->setWidth(20);  // Direction
        $sheet->getColumnDimension('D')->setWidth(50);  // Intitulé
        $sheet->getColumnDimension('E')->setWidth(20);  // Lieu
        $sheet->getColumnDimension('F')->setWidth(25);  // Période
        $sheet->getColumnDimension('G')->setWidth(45);  // DÉPENSES (beaucoup plus large pour contenir toutes les dépenses)
        $sheet->getColumnDimension('H')->setWidth(18);  // Total
        $sheet->getColumnDimension('I')->setWidth(18);  // Budget ANAC
        $sheet->getColumnDimension('J')->setWidth(18);  // Budget Extérieur
        $sheet->getColumnDimension('K')->setWidth(25);  // Prévues et exécutées
        $sheet->getColumnDimension('L')->setWidth(30);  // Non prévues et exécutées

        // Style des données simple
        $dataRange = 'A5:L' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'font' => ['size' => 10]
        ]);

        // Alignement spécial pour certaines colonnes
        $sheet->getStyle('D5:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B5:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G5:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Colonne DÉPENSES alignée à gauche
        
        // Ajuster la hauteur des lignes de données (plus haute pour éviter la troncature)
        for ($i = 5; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(60); // Plus haute pour contenir toutes les dépenses sans troncature
        }

        // Créer la réponse
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport_budget_formations_' . date('Y-m-d') . '.xlsx"');
        
        $tempFile = tempnam(sys_get_temp_dir(), 'formation_budget_report');
        $writer->save($tempFile);
        $response->setContent(file_get_contents($tempFile));
        unlink($tempFile);

        return $response;
    }
}
