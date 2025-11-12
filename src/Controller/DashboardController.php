<?php

namespace App\Controller;

use App\Repository\FormationSessionRepository;
use App\Repository\MissionSessionRepository;
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
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository,
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
            'total_formations' => $formationSessionRepository->countByYear($currentYear),
            'total_missions' => $missionSessionRepository->countByYear($currentYear),
            'formations_executees' => $formationSessionRepository->countExecutedByYear($currentYear),
            'missions_executees' => $missionSessionRepository->countExecutedByYear($currentYear),
            'formations_prevues' => $formationSessionRepository->countPlannedByYear($currentYear),
            'missions_prevues' => $missionSessionRepository->countPlannedByYear($currentYear),
            'budget_total_formations' => $formationSessionRepository->getTotalBudgetByYear($currentYear),
            'budget_total_missions' => $missionSessionRepository->getTotalBudgetByYear($currentYear),
            'depenses_reelles_formations' => $formationSessionRepository->getTotalRealExpensesByYear($currentYear),
            'depenses_reelles_missions' => $missionSessionRepository->getTotalRealExpensesByYear($currentYear),
        ];
        
        // Ajouter des statistiques spécifiques selon le rôle
        if ($roleService->isAdmin()) {
            $stats['total_users'] = $userRepository->count([]);
            $stats['total_services'] = $serviceRepository->count([]);
        }
        
        // Données pour les graphiques mensuels
        $monthlyData = [
            'formations' => $formationSessionRepository->getMonthlyStatsByYear($currentYear),
            'missions' => $missionSessionRepository->getMonthlyStatsByYear($currentYear),
        ];
        
        // Top 5 des services les plus actifs
        $topServices = $serviceRepository->getTopActiveServicesByYear($currentYear);
        
        // Répartition par statut
        $statusDistribution = [
            'formations' => $formationSessionRepository->getStatusDistributionByYear($currentYear),
            'missions' => $missionSessionRepository->getStatusDistributionByYear($currentYear),
        ];

        // Nouvelles statistiques
        $topExpensiveFormations = $formationSessionRepository->getTopExpensiveFormations($currentYear);
        $bottomExpensiveFormations = $formationSessionRepository->getBottomExpensiveFormations($currentYear);
        $topExpensiveMissions = $missionSessionRepository->getTopExpensiveMissions($currentYear);
        $bottomExpensiveMissions = $missionSessionRepository->getBottomExpensiveMissions($currentYear);
        
        $executionRateFormations = $formationSessionRepository->getExecutionRateByDirection($currentYear);
        $executionRateMissions = $missionSessionRepository->getExecutionRateByDirection($currentYear);
        
        $userParticipationFormations = $formationSessionRepository->getUserParticipationStats($currentYear);
        $userParticipationMissions = $missionSessionRepository->getUserParticipationStats($currentYear);
        
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
