<?php

namespace App\Service;

use App\Repository\FormationRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;

class PerformanceService
{
    private FormationRepository $formationRepository;
    private MissionRepository $missionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        FormationRepository $formationRepository,
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->formationRepository = $formationRepository;
        $this->missionRepository = $missionRepository;
        $entityManager = $entityManager;
    }

    /**
     * Calculer les performances globales pour une année donnée
     */
    public function getGlobalPerformance(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        $formationsPerformance = $this->getFormationPerformance($year);
        $missionsPerformance = $this->getMissionPerformance($year);
        
        return [
            'year' => $year,
            'formations' => $formationsPerformance,
            'missions' => $missionsPerformance,
            'global' => $this->calculateGlobalPerformance($formationsPerformance, $missionsPerformance)
        ];
    }

    /**
     * Calculer les performances des formations
     */
    public function getFormationPerformance(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        // Récupérer les formations de l'année
        $formationsPrevues = $this->formationRepository->countByYear($year);
        $formationsRealisees = $this->formationRepository->countExecutedByYear($year);
        
        // Récupérer les budgets
        $budgetPrevu = $this->formationRepository->getTotalBudgetByYear($year);
        $depensesReelles = $this->formationRepository->getTotalRealExpensesByYear($year);
        
        // Calculer les taux
        $tauxPhysique = $formationsPrevues > 0 ? round(($formationsRealisees / $formationsPrevues) * 100, 1) : 0;
        $tauxFinancier = $budgetPrevu > 0 ? round(($depensesReelles / $budgetPrevu) * 100, 1) : 0;
        
        return [
            'taux_physique' => $tauxPhysique,
            'taux_financier' => $tauxFinancier,
            'formations_prevues' => $formationsPrevues,
            'formations_realisees' => $formationsRealisees,
            'formations_en_cours' => $formationsPrevues - $formationsRealisees,
            'budget_prevu' => $budgetPrevu,
            'depenses_reelles' => $depensesReelles,
            'ecart_budgetaire' => $depensesReelles - $budgetPrevu,
            'couleur_physique' => $this->getPerformanceColor($tauxPhysique),
            'couleur_financier' => $this->getBudgetColor($tauxFinancier),
            'icon_physique' => $this->getPerformanceIcon($tauxPhysique),
            'icon_financier' => $this->getBudgetIcon($tauxFinancier)
        ];
    }

    /**
     * Calculer les performances des missions
     */
    public function getMissionPerformance(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        // Récupérer les missions de l'année
        $missionsPrevues = $this->missionRepository->countByYear($year);
        $missionsRealisees = $this->missionRepository->countExecutedByYear($year);
        
        // Récupérer les budgets
        $budgetPrevu = $this->missionRepository->getTotalBudgetByYear($year);
        $depensesReelles = $this->missionRepository->getTotalRealExpensesByYear($year);
        
        // Calculer les taux
        $tauxPhysique = $missionsPrevues > 0 ? round(($missionsRealisees / $missionsPrevues) * 100, 1) : 0;
        $tauxFinancier = $budgetPrevu > 0 ? round(($depensesReelles / $budgetPrevu) * 100, 1) : 0;
        
        return [
            'taux_physique' => $tauxPhysique,
            'taux_financier' => $tauxFinancier,
            'missions_prevues' => $missionsPrevues,
            'missions_realisees' => $missionsRealisees,
            'missions_en_cours' => $missionsPrevues - $missionsRealisees,
            'budget_prevu' => $budgetPrevu,
            'depenses_reelles' => $depensesReelles,
            'ecart_budgetaire' => $depensesReelles - $budgetPrevu,
            'couleur_physique' => $this->getPerformanceColor($tauxPhysique),
            'couleur_financier' => $this->getBudgetColor($tauxFinancier),
            'icon_physique' => $this->getPerformanceIcon($tauxPhysique),
            'icon_financier' => $this->getBudgetIcon($tauxFinancier)
        ];
    }

    /**
     * Calculer les performances par direction
     */
    public function getPerformanceByDirection(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        // Récupérer les directions
        $directions = $this->entityManager->getRepository(\App\Entity\Direction::class)->findAll();
        $performanceByDirection = [];
        
        foreach ($directions as $direction) {
            $formations = $this->formationRepository->findByDirectionAndYear($direction, $year);
            $missions = $this->missionRepository->findByDirectionAndYear($direction, $year);
            
            $formationsPrevues = count($formations);
            $formationsRealisees = count(array_filter($formations, fn($f) => $f->getStatutActivite()->getCode() === 'prevue_executee'));
            
            $missionsPrevues = count($missions);
            $missionsRealisees = count(array_filter($missions, fn($m) => $m->getStatutActivite()->getCode() === 'prevue_executee'));
            
            $performanceByDirection[] = [
                'direction' => $direction->getLibelle(),
                'formations' => [
                    'prevues' => $formationsPrevues,
                    'realisees' => $formationsRealisees,
                    'taux' => $formationsPrevues > 0 ? round(($formationsRealisees / $formationsPrevues) * 100, 1) : 0
                ],
                'missions' => [
                    'prevues' => $missionsPrevues,
                    'realisees' => $missionsRealisees,
                    'taux' => $missionsPrevues > 0 ? round(($missionsRealisees / $missionsPrevues) * 100, 1) : 0
                ]
            ];
        }
        
        return $performanceByDirection;
    }

    /**
     * Calculer les performances globales combinées
     */
    private function calculateGlobalPerformance(array $formations, array $missions): array
    {
        $totalPrevues = $formations['formations_prevues'] + $missions['missions_prevues'];
        $totalRealisees = $formations['formations_realisees'] + $missions['missions_realisees'];
        $totalBudgetPrevu = $formations['budget_prevu'] + $missions['budget_prevu'];
        $totalDepensesReelles = $formations['depenses_reelles'] + $missions['depenses_reelles'];
        
        $tauxPhysiqueGlobal = $totalPrevues > 0 ? round(($totalRealisees / $totalPrevues) * 100, 1) : 0;
        $tauxFinancierGlobal = $totalBudgetPrevu > 0 ? round(($totalDepensesReelles / $totalBudgetPrevu) * 100, 1) : 0;
        
        return [
            'taux_physique' => $tauxPhysiqueGlobal,
            'taux_financier' => $tauxFinancierGlobal,
            'total_prevues' => $totalPrevues,
            'total_realisees' => $totalRealisees,
            'total_budget_prevu' => $totalBudgetPrevu,
            'total_depenses_reelles' => $totalDepensesReelles,
            'couleur_physique' => $this->getPerformanceColor($tauxPhysiqueGlobal),
            'couleur_financier' => $this->getBudgetColor($tauxFinancierGlobal),
            'icon_physique' => $this->getPerformanceIcon($tauxPhysiqueGlobal),
            'icon_financier' => $this->getBudgetIcon($tauxFinancierGlobal)
        ];
    }

    /**
     * Déterminer la couleur selon le taux de performance physique
     */
    private function getPerformanceColor(float $taux): string
    {
        if ($taux >= 90) return 'success';
        if ($taux >= 70) return 'warning';
        return 'danger';
    }

    /**
     * Déterminer la couleur selon le taux financier
     */
    private function getBudgetColor(float $taux): string
    {
        if ($taux <= 100) return 'success';
        if ($taux <= 120) return 'warning';
        return 'danger';
    }

    /**
     * Déterminer l'icône selon le taux de performance physique
     */
    private function getPerformanceIcon(float $taux): string
    {
        if ($taux >= 90) return 'fa-check-circle';
        if ($taux >= 70) return 'fa-exclamation-triangle';
        return 'fa-times-circle';
    }

    /**
     * Déterminer l'icône selon le taux financier
     */
    private function getBudgetIcon(float $taux): string
    {
        if ($taux <= 100) return 'fa-check-circle';
        if ($taux <= 120) return 'fa-exclamation-triangle';
        return 'fa-times-circle';
    }

    /**
     * Formater un montant en FCFA
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public function getChartData(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        return [
            'monthly' => [
                'formations' => $this->formationRepository->getMonthlyStatsByYear($year),
                'missions' => $this->missionRepository->getMonthlyStatsByYear($year)
            ],
            'performance' => [
                'formations' => $this->getFormationPerformance($year),
                'missions' => $this->getMissionPerformance($year)
            ]
        ];
    }
}
