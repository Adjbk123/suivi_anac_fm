<?php

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Repository\MissionRepository;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\StatutParticipationRepository;
use App\Service\RoleService;
use App\Service\PerformanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        FormationRepository $formationRepository,
        MissionRepository $missionRepository,
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        StatutActiviteRepository $statutActiviteRepository,
        StatutParticipationRepository $statutParticipationRepository,
        EntityManagerInterface $entityManager,
        RoleService $roleService,
        PerformanceService $performanceService
    ): Response {
        $currentYear = date('Y');
        $user = $this->getUser();
        
        // Statistiques générales pour l'année en cours
        $stats = [
            'total_formations' => $formationRepository->countByYear($currentYear),
            'total_missions' => $missionRepository->countByYear($currentYear),
            'formations_executees' => $formationRepository->countExecutedByYear($currentYear),
            'missions_executees' => $missionRepository->countExecutedByYear($currentYear),
            'formations_prevues' => $formationRepository->countPlannedByYear($currentYear),
            'missions_prevues' => $missionRepository->countPlannedByYear($currentYear),
            'budget_total_formations' => $formationRepository->getTotalBudgetByYear($currentYear),
            'budget_total_missions' => $missionRepository->getTotalBudgetByYear($currentYear),
            'depenses_reelles_formations' => $formationRepository->getTotalRealExpensesByYear($currentYear),
            'depenses_reelles_missions' => $missionRepository->getTotalRealExpensesByYear($currentYear),
        ];
        
        // Ajouter des statistiques spécifiques selon le rôle
        if ($roleService->isAdmin()) {
            $stats['total_users'] = $userRepository->count([]);
            $stats['total_services'] = $serviceRepository->count([]);
        }
        
        // Données pour les graphiques mensuels
        $monthlyData = [
            'formations' => $formationRepository->getMonthlyStatsByYear($currentYear),
            'missions' => $missionRepository->getMonthlyStatsByYear($currentYear),
        ];
        
        // Top 5 des services les plus actifs
        $topServices = $serviceRepository->getTopActiveServicesByYear($currentYear);
        
        // Répartition par statut
        $statusDistribution = [
            'formations' => $formationRepository->getStatusDistributionByYear($currentYear),
            'missions' => $missionRepository->getStatusDistributionByYear($currentYear),
        ];

        // Nouvelles statistiques
        $topExpensiveFormations = $formationRepository->getTopExpensiveFormations($currentYear);
        $bottomExpensiveFormations = $formationRepository->getBottomExpensiveFormations($currentYear);
        $topExpensiveMissions = $missionRepository->getTopExpensiveMissions($currentYear);
        $bottomExpensiveMissions = $missionRepository->getBottomExpensiveMissions($currentYear);
        
        $executionRateFormations = $formationRepository->getExecutionRateByDirection($currentYear);
        $executionRateMissions = $missionRepository->getExecutionRateByDirection($currentYear);
        
        $userParticipationFormations = $formationRepository->getUserParticipationStats($currentYear);
        $userParticipationMissions = $missionRepository->getUserParticipationStats($currentYear);
        
        // Récupérer les données de performance
        $performanceData = $performanceService->getGlobalPerformance($currentYear);
        
        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'monthlyData' => $monthlyData,
            'topServices' => $topServices,
            'statusDistribution' => $statusDistribution,
            'currentYear' => $currentYear,
            'topExpensiveFormations' => $topExpensiveFormations,
            'bottomExpensiveFormations' => $bottomExpensiveFormations,
            'topExpensiveMissions' => $topExpensiveMissions,
            'bottomExpensiveMissions' => $bottomExpensiveMissions,
            'executionRateFormations' => $executionRateFormations,
            'executionRateMissions' => $executionRateMissions,
            'userParticipationFormations' => $userParticipationFormations,
            'userParticipationMissions' => $userParticipationMissions,
            'userRole' => $user->getRoles()[0] ?? 'ROLE_USER',
            'isAdmin' => $roleService->isAdmin(),
            'isDirecteur' => $roleService->isDirecteur(),
            'isEditeur' => $roleService->isEditeur(),
            'performanceData' => $performanceData,
        ]);
    }
}
