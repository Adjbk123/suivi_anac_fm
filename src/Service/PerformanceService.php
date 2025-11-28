<?php

namespace App\Service;

use App\Repository\FormationSessionRepository;
use App\Repository\MissionSessionRepository;
use Doctrine\ORM\EntityManagerInterface;

class PerformanceService
{
    private FormationSessionRepository $formationSessionRepository;
    private MissionSessionRepository $missionSessionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->formationSessionRepository = $formationSessionRepository;
        $this->missionSessionRepository = $missionSessionRepository;
        $this->entityManager = $entityManager;
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
        
        // Récupérer les sessions de formation de l'année
        $formationsPrevues = $this->formationSessionRepository->countByYear($year);
        $formationsRealisees = $this->formationSessionRepository->countExecutedByYear($year);
        
        // Récupérer les budgets
        $budgetPrevu = $this->formationSessionRepository->getTotalBudgetByYear($year);
        $depensesReelles = $this->formationSessionRepository->getTotalRealExpensesByYear($year);
        
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
        $missionsPrevues = $this->missionSessionRepository->countByYear($year);
        $missionsRealisees = $this->missionSessionRepository->countExecutedByYear($year);
        
        // Récupérer les budgets
        $budgetPrevu = $this->missionSessionRepository->getTotalBudgetByYear($year);
        $depensesReelles = $this->missionSessionRepository->getTotalRealExpensesByYear($year);
        
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
     * Calculer les performances des formations avec filtres
     */
    public function getFormationPerformanceWithFilters(array $filters): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        // Récupérer toutes les sessions
        $sessionFilters = [];
        if (!empty($filters['direction'])) {
            $sessionFilters['direction'] = $filters['direction'];
        }
        if (!empty($filters['statut'])) {
            $sessionFilters['statut'] = $filters['statut'];
        }
        
        // Si aucun filtre spécifique, récupérer toutes les sessions
        if (empty($sessionFilters)) {
            $formationSessions = $this->formationSessionRepository->findAll();
        } else {
            $formationSessions = $this->formationSessionRepository->findWithFilters($sessionFilters);
        }
        
        // Filtrer selon les critères
        $formationSessions = array_filter($formationSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDateDebut = $session->getDatePrevueDebut();
            $sessionDateFin = $session->getDatePrevueFin() ?? $sessionDateDebut;
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                $dateDebut->setTime(0, 0, 0);
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                
                // Vérifier si la formation chevauche la période filtrée
                // Une formation est incluse si elle chevauche la période :
                // (datePrevueDebut <= dateFin) ET (datePrevueFin >= dateDebut)
                if ($sessionDateFin < $dateDebut || $sessionDateDebut > $dateFin) {
                    return false;
                }
            } elseif (!empty($filters['date_debut'])) {
                // Seulement date_debut : inclure si la formation commence après ou pendant
                $dateDebut = new \DateTime($filters['date_debut']);
                $dateDebut->setTime(0, 0, 0);
                if ($sessionDateFin < $dateDebut) {
                    return false;
                }
            } elseif (!empty($filters['date_fin'])) {
                // Seulement date_fin : inclure si la formation commence avant ou pendant
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                if ($sessionDateDebut > $dateFin) {
                    return false;
                }
            } else {
                // Si pas de filtres de date, utiliser l'année
                if ($sessionDateDebut->format('Y') != $year) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Calculer les statistiques
        $formationsPrevues = count($formationSessions);
        $formationsRealisees = count(array_filter($formationSessions, function($fs) {
            $statut = $fs->getStatutActivite();
            return $statut && in_array($statut->getCode(), ['prevue_executee', 'non_prevue_executee']);
        }));
        
        $budgetPrevu = 0;
        $depensesReelles = 0;
        foreach ($formationSessions as $session) {
            $budgetPrevu += (float)($session->getBudgetPrevu() ?? 0);
            $depensesReelles += (float)($session->getBudgetReel() ?? 0);
        }
        
        // Calculer les taux
        // Le taux physique = (activités réalisées / total des activités) * 100
        $tauxPhysique = $formationsPrevues > 0 ? round(($formationsRealisees / $formationsPrevues) * 100, 1) : 0;
        // Le taux financier = (dépenses réelles / budget prévu) * 100
        $tauxFinancier = $budgetPrevu > 0 ? round(($depensesReelles / $budgetPrevu) * 100, 1) : 0;
        
        // Log pour débogage
        error_log(sprintf(
            'Performance Formations - Filtres: %s, Total: %d, Réalisées: %d, Taux: %.1f%%, Budget prévu: %.2f, Budget réel: %.2f',
            json_encode($filters),
            $formationsPrevues,
            $formationsRealisees,
            $tauxPhysique,
            $budgetPrevu,
            $depensesReelles
        ));
        
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
     * Calculer les performances des missions avec filtres
     */
    public function getMissionPerformanceWithFilters(array $filters): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        // Récupérer toutes les sessions
        if (empty($filters['direction']) && empty($filters['statut'])) {
            $missionSessions = $this->missionSessionRepository->findAll();
        } else {
            $missionSessions = $this->missionSessionRepository->findAllWithFilters(
                null,
                $filters['direction'] ?? null,
                $filters['statut'] ?? null,
                null
            );
        }
        
        // Filtrer selon les critères
        $missionSessions = array_filter($missionSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDateDebut = $session->getDatePrevueDebut();
            $sessionDateFin = $session->getDatePrevueFin() ?? $sessionDateDebut;
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                $dateDebut->setTime(0, 0, 0);
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                
                // Vérifier si la mission chevauche la période filtrée
                // Une mission est incluse si elle chevauche la période :
                // (datePrevueDebut <= dateFin) ET (datePrevueFin >= dateDebut)
                if ($sessionDateFin < $dateDebut || $sessionDateDebut > $dateFin) {
                    return false;
                }
            } elseif (!empty($filters['date_debut'])) {
                // Seulement date_debut : inclure si la mission commence après ou pendant
                $dateDebut = new \DateTime($filters['date_debut']);
                $dateDebut->setTime(0, 0, 0);
                if ($sessionDateFin < $dateDebut) {
                    return false;
                }
            } elseif (!empty($filters['date_fin'])) {
                // Seulement date_fin : inclure si la mission commence avant ou pendant
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                if ($sessionDateDebut > $dateFin) {
                    return false;
                }
            } else {
                // Si pas de filtres de date, utiliser l'année
                if ($sessionDateDebut->format('Y') != $year) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Calculer les statistiques
        $missionsPrevues = count($missionSessions);
        $missionsRealisees = count(array_filter($missionSessions, function($ms) {
            $statut = $ms->getStatutActivite();
            return $statut && in_array($statut->getCode(), ['prevue_executee', 'non_prevue_executee']);
        }));
        
        $budgetPrevu = 0;
        $depensesReelles = 0;
        foreach ($missionSessions as $session) {
            $budgetPrevu += (float)($session->getBudgetPrevu() ?? 0);
            $depensesReelles += (float)($session->getBudgetReel() ?? 0);
        }
        
        // Calculer les taux
        // Le taux physique = (activités réalisées / total des activités) * 100
        $tauxPhysique = $missionsPrevues > 0 ? round(($missionsRealisees / $missionsPrevues) * 100, 1) : 0;
        // Le taux financier = (dépenses réelles / budget prévu) * 100
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
            // Récupérer les sessions de formation par direction et année
            $filters = ['direction' => $direction->getId()];
            $formationSessions = $this->formationSessionRepository->findWithFilters($filters);
            // Filtrer par année
            $formationSessions = array_filter($formationSessions, function($fs) use ($year) {
                return $fs->getDatePrevueDebut() && $fs->getDatePrevueDebut()->format('Y') == $year;
            });
            
            $missions = array_filter(
                $this->missionSessionRepository->findAllWithFilters(null, (string) $direction->getId(), null, null),
                static fn ($ms) => $ms->getDatePrevueDebut() && $ms->getDatePrevueDebut()->format('Y') == $year
            );
            
            $formationsPrevues = count($formationSessions);
            $formationsRealisees = count(array_filter($formationSessions, fn($fs) => $fs->getStatutActivite() && $fs->getStatutActivite()->getCode() === 'prevue_executee'));
            
            $missionsPrevues = count($missions);
            $missionsRealisees = count(array_filter($missions, static fn ($ms) => $ms->getStatutActivite() && $ms->getStatutActivite()->getCode() === 'prevue_executee'));
            
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
                'formations' => $this->formationSessionRepository->getMonthlyStatsByYear($year),
                'missions' => $this->missionSessionRepository->getMonthlyStatsByYear($year)
            ],
            'performance' => [
                'formations' => $this->getFormationPerformance($year),
                'missions' => $this->getMissionPerformance($year)
            ]
        ];
    }
}
