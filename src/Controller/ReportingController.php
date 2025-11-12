<?php

namespace App\Controller;

use App\Repository\FormationSessionRepository;
use App\Repository\MissionRepository;
use App\Repository\ServiceRepository;
use App\Repository\DirectionRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\TypeFondsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/reporting')]
#[IsGranted('ROLE_DIRECTEUR')]
class ReportingController extends AbstractController
{
    #[Route('/', name: 'app_reporting')]
    public function index(
        ServiceRepository $serviceRepository,
        DirectionRepository $directionRepository,
        StatutActiviteRepository $statutActiviteRepository,
        TypeFondsRepository $typeFondsRepository
    ): Response {
        return $this->render('reporting/index.html.twig', [
            'services' => $serviceRepository->findAll(),
            'directions' => $directionRepository->findAll(),
            'statuts' => $statutActiviteRepository->findAll(),
            'typeFonds' => $typeFondsRepository->findAll(),
            'currentYear' => date('Y'),
        ]);
    }

    #[Route('/data', name: 'app_reporting_data', methods: ['POST'])]
    public function getData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $filters = $request->request->all();
        
        // Préparer les données filtrées
        $data = [
            'resume' => $this->getResumeGlobal($filters, $formationSessionRepository, $missionRepository),
            'formations' => $this->getFormationsData($filters, $formationSessionRepository),
            'missions' => $this->getMissionsData($filters, $missionRepository),
            'parDirection' => $this->getDataParDirection($filters, $formationSessionRepository, $missionRepository),
            'parPeriode' => $this->getDataParPeriode($filters, $formationSessionRepository, $missionRepository),
            'statuts' => $this->getStatutsActivites($filters, $formationSessionRepository, $missionRepository),
            'indicateurs' => $this->getIndicateursClés($filters, $formationSessionRepository, $missionRepository),
        ];

        return new JsonResponse($data);
    }

    #[Route('/data/resume', name: 'app_reporting_data_resume', methods: ['POST'])]
    public function getResumeData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getResumeGlobal($filters, $formationSessionRepository, $missionRepository));
    }

    #[Route('/data/formations', name: 'app_reporting_data_formations', methods: ['POST'])]
    public function getFormationsDataEndpoint(
        Request $request,
        FormationSessionRepository $formationSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getFormationsData($filters, $formationSessionRepository));
    }

    #[Route('/data/missions', name: 'app_reporting_data_missions', methods: ['POST'])]
    public function getMissionsDataEndpoint(
        Request $request,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getMissionsData($filters, $missionRepository));
    }

    #[Route('/data/directions', name: 'app_reporting_data_directions', methods: ['POST'])]
    public function getDirectionsData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getDataParDirection($filters, $formationSessionRepository, $missionRepository));
    }

    #[Route('/data/periodes', name: 'app_reporting_data_periodes', methods: ['POST'])]
    public function getPeriodesData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getDataParPeriode($filters, $formationSessionRepository, $missionRepository));
    }

    #[Route('/data/statuts', name: 'app_reporting_data_statuts', methods: ['POST'])]
    public function getStatutsData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getStatutsActivites($filters, $formationSessionRepository, $missionRepository));
    }

    #[Route('/data/indicateurs', name: 'app_reporting_data_indicateurs', methods: ['POST'])]
    public function getIndicateursData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getIndicateursClés($filters, $formationSessionRepository, $missionRepository));
    }

    #[Route('/api/filters-data', name: 'app_reporting_api_filters', methods: ['GET'])]
    public function getFiltersData(
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository,
        ServiceRepository $serviceRepository,
        DirectionRepository $directionRepository,
        StatutActiviteRepository $statutActiviteRepository,
        TypeFondsRepository $typeFondsRepository
    ): JsonResponse {
        // Récupérer les années disponibles depuis les sessions de formation et missions
        try {
            $yearsFormations = $formationSessionRepository->getAvailableYears();
            $yearsMissions = $missionRepository->getAvailableYears();
            
            // Debug: voir ce qui est retourné
            error_log('Years Formations: ' . print_r($yearsFormations, true));
            error_log('Years Missions: ' . print_r($yearsMissions, true));
            
            // Combiner et dédupliquer les années
            $allYears = array_unique(array_merge($yearsFormations, $yearsMissions));
            $allYears = array_filter($allYears, function($year) {
                return $year !== null && $year > 0;
            });
            rsort($allYears, SORT_NUMERIC); // Tri décroissant numérique
            
            error_log('All Years: ' . print_r($allYears, true));
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des années: ' . $e->getMessage());
            // Fallback: utiliser l'année actuelle
            $allYears = [date('Y')];
        }
        
        // Formater les données pour le JavaScript
        try {
            $directions = array_map(function($direction) {
                return [
                    'id' => $direction->getId(),
                    'libelle' => $direction->getLibelle()
                ];
            }, $directionRepository->findAll());

            $services = array_map(function($service) {
                return [
                    'id' => $service->getId(),
                    'libelle' => $service->getLibelle(),
                    'direction_id' => $service->getDirection() ? $service->getDirection()->getId() : null
                ];
            }, $serviceRepository->findAll());

            $statuts = array_map(function($statut) {
                return [
                    'id' => $statut->getId(),
                    'libelle' => $statut->getLibelle()
                ];
            }, $statutActiviteRepository->findAll());

            $typeFonds = array_map(function($typeFonds) {
                return [
                    'id' => $typeFonds->getId(),
                    'libelle' => $typeFonds->getLibelle()
                ];
            }, $typeFondsRepository->findAll());
        } catch (\Exception $e) {
            error_log('Erreur lors du formatage des données: ' . $e->getMessage());
            $directions = [];
            $services = [];
            $statuts = [];
            $typeFonds = [];
        }

        $response = [
            'directions' => $directions,
            'services' => $services,
            'statuts' => $statuts,
            'typeFonds' => $typeFonds,
            'years' => array_values($allYears), // Réindexer le tableau
        ];
        
        error_log('API Response: ' . print_r($response, true));
        
        return new JsonResponse($response);
    }

    #[Route('/export-pdf', name: 'app_reporting_export_pdf', methods: ['POST'])]
    public function exportPdf(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionRepository $missionRepository
    ): Response {
        $filters = $request->request->all();
        
        // Récupérer les données
        $data = [
            'resume' => $this->getResumeGlobal($filters, $formationSessionRepository, $missionRepository),
            'formations' => $this->getFormationsData($filters, $formationSessionRepository),
            'missions' => $this->getMissionsData($filters, $missionRepository),
            'parDirection' => $this->getDataParDirection($filters, $formationSessionRepository, $missionRepository),
            'statuts' => $this->getStatutsActivites($filters, $formationSessionRepository, $missionRepository),
            'indicateurs' => $this->getIndicateursClés($filters, $formationSessionRepository, $missionRepository),
            'filters' => $filters,
        ];

        // Générer le PDF
        $html = $this->renderView('reporting/pdf.html.twig', $data);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'rapport_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }

    private function getResumeGlobal(array $filters, FormationSessionRepository $formationSessionRepository, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'missions_prevues' => $missionRepository->countPlannedByYear($year),
            'missions_realisees' => $missionRepository->countExecutedByYear($year),
            'formations_prevues' => $formationSessionRepository->countPlannedByYear($year),
            'formations_realisees' => $formationSessionRepository->countExecutedByYear($year),
            'budget_total_prevu' => $formationSessionRepository->getTotalBudgetByYear($year) + $missionRepository->getTotalBudgetByYear($year),
            'budget_total_realise' => $formationSessionRepository->getTotalRealExpensesByYear($year) + $missionRepository->getTotalRealExpensesByYear($year),
        ];
    }

    private function getFormationsData(array $filters, FormationSessionRepository $formationSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        // Construire les filtres pour FormationSession
        $sessionFilters = [];
        
        if (!empty($filters['direction_id'])) {
            $sessionFilters['direction'] = $filters['direction_id'];
        }
        
        if (!empty($filters['statut_id'])) {
            $sessionFilters['statut'] = $filters['statut_id'];
        }
        
        if (!empty($filters['type_fonds_id'])) {
            // Note: Le filtrage par type_fonds doit être géré dans findWithFilters
        }
        
        // Filtrer par année
        if (!empty($filters['mois'])) {
            $month = $filters['mois'];
            // Note: Le filtrage par mois doit être géré dans findWithFilters ou après
        }
        
        // Récupérer les sessions de formation avec les filtres
        $formationSessions = $formationSessionRepository->findWithFilters($sessionFilters);
        
        // Filtrer par année et mois si nécessaire
        $formationSessions = array_filter($formationSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            if ($session->getDatePrevueDebut()->format('Y') != $year) {
                return false;
            }
            if (!empty($filters['mois']) && $session->getDatePrevueDebut()->format('n') != $filters['mois']) {
                return false;
            }
            if (!empty($filters['type_fonds_id']) && $session->getFonds() && $session->getFonds()->getId() != $filters['type_fonds_id']) {
                return false;
            }
            if (!empty($filters['lieu']) && $session->getLieuPrevu() != $filters['lieu']) {
                return false;
            }
            return true;
        });
        
        // Convertir les entités en tableaux pour la sérialisation JSON
        $formationsArray = [];
        $totalBudget = 0;
        $totalParticipants = 0;
        
        foreach ($formationSessions as $session) {
            $formation = $session->getFormation();
            $totalBudget += (float)$session->getBudgetPrevu();
            $totalParticipants += $session->getUserFormations()->count();
            
            $formationsArray[] = [
                'id' => $session->getId(),
                'titre' => $formation ? $formation->getTitre() : '',
                'description' => $formation ? $formation->getDescription() : null,
                'lieuPrevu' => $session->getLieuPrevu(),
                'lieuReel' => $session->getLieuReel(),
                'datePrevueDebut' => $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('Y-m-d') : null,
                'datePrevueFin' => $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('Y-m-d') : null,
                'dateReelleDebut' => $session->getDateReelleDebut() ? $session->getDateReelleDebut()->format('Y-m-d') : null,
                'dateReelleFin' => $session->getDateReelleFin() ? $session->getDateReelleFin()->format('Y-m-d') : null,
                'budgetPrevu' => (float)$session->getBudgetPrevu(),
                'statutActivite' => $session->getStatutActivite() ? [
                    'id' => $session->getStatutActivite()->getId(),
                    'libelle' => $session->getStatutActivite()->getLibelle(),
                    'code' => $session->getStatutActivite()->getCode(),
                    'couleur' => $session->getStatutActivite()->getCouleur(),
                ] : null,
                'direction' => $session->getDirection() ? [
                    'id' => $session->getDirection()->getId(),
                    'libelle' => $session->getDirection()->getLibelle(),
                ] : null,
                'fonds' => $session->getFonds() ? [
                    'id' => $session->getFonds()->getId(),
                    'libelle' => $session->getFonds()->getLibelle(),
                ] : null,
            ];
        }
        
        return [
            'liste' => $formationsArray,
            'stats' => [
                'total' => count($formationsArray),
                'budget_total' => $totalBudget,
                'participants_total' => $totalParticipants,
            ]
        ];
    }

    private function getMissionsData(array $filters, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        $month = $filters['mois'] ?? date('n');
        
        // Construire les critères de filtrage
        $criteria = [];
        
        if (!empty($filters['type_activite']) && $filters['type_activite'] !== 'all') {
            // Filtre par type d'activité (toujours 'mission' ici)
        }
        
        if (!empty($filters['direction_id'])) {
            $criteria['direction.id'] = $filters['direction_id'];
        }
        
        if (!empty($filters['statut_id'])) {
            $criteria['statutActivite.id'] = $filters['statut_id'];
        }
        
        if (!empty($filters['type_fonds_id'])) {
            $criteria['fonds.id'] = $filters['type_fonds_id'];
        }
        
        if (!empty($filters['lieu'])) {
            $criteria['lieuPrevu'] = $filters['lieu'];
        }
        
        // Récupérer les missions avec les filtres
        $missions = $missionRepository->findMissionsWithFilters($year, $month, $criteria);
        
        // Convertir les entités en tableaux pour la sérialisation JSON
        $missionsArray = [];
        $totalBudget = 0;
        
        foreach ($missions as $mission) {
            $totalBudget += $mission->getBudgetPrevu() ?? 0;
            
            $missionsArray[] = [
                'id' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'description' => $mission->getDescription(),
                'lieuPrevu' => $mission->getLieuPrevu(),
                'lieuReel' => $mission->getLieuReel(),
                'datePrevueDebut' => $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('Y-m-d') : null,
                'datePrevueFin' => $mission->getDatePrevueFin() ? $mission->getDatePrevueFin()->format('Y-m-d') : null,
                'dateReelleDebut' => $mission->getDateReelleDebut() ? $mission->getDateReelleDebut()->format('Y-m-d') : null,
                'dateReelleFin' => $mission->getDateReelleFin() ? $mission->getDateReelleFin()->format('Y-m-d') : null,
                'budgetPrevu' => $mission->getBudgetPrevu(),
                'statutActivite' => $mission->getStatutActivite() ? [
                    'id' => $mission->getStatutActivite()->getId(),
                    'libelle' => $mission->getStatutActivite()->getLibelle(),
                    'code' => $mission->getStatutActivite()->getCode(),
                    'couleur' => $mission->getStatutActivite()->getCouleur(),
                ] : null,
                'direction' => $mission->getDirection() ? [
                    'id' => $mission->getDirection()->getId(),
                    'libelle' => $mission->getDirection()->getLibelle(),
                ] : null,
                'fonds' => $mission->getFonds() ? [
                    'id' => $mission->getFonds()->getId(),
                    'libelle' => $mission->getFonds()->getLibelle(),
                ] : null,
            ];
        }
        
        return [
            'liste' => $missionsArray,
            'stats' => [
                'total' => count($missions),
                'budget_total' => $totalBudget,
            ]
        ];
    }

    private function getDataParDirection(array $filters, FormationSessionRepository $formationSessionRepository, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getExecutionRateByDirection($year),
            'missions' => $missionRepository->getExecutionRateByDirection($year),
        ];
    }

    private function getDataParPeriode(array $filters, FormationSessionRepository $formationSessionRepository, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getMonthlyStatsByYear($year),
            'missions' => $missionRepository->getMonthlyStatsByYear($year),
        ];
    }

    private function getStatutsActivites(array $filters, FormationSessionRepository $formationSessionRepository, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getStatusDistributionByYear($year),
            'missions' => $missionRepository->getStatusDistributionByYear($year),
        ];
    }

    private function getIndicateursClés(array $filters, FormationSessionRepository $formationSessionRepository, MissionRepository $missionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        $budgetPrevuFormations = $formationSessionRepository->getTotalBudgetByYear($year);
        $budgetReelFormations = $formationSessionRepository->getTotalRealExpensesByYear($year);
        $budgetPrevuMissions = $missionRepository->getTotalBudgetByYear($year);
        $budgetReelMissions = $missionRepository->getTotalRealExpensesByYear($year);
        
        $budgetPrevuTotal = $budgetPrevuFormations + $budgetPrevuMissions;
        $budgetReelTotal = $budgetReelFormations + $budgetReelMissions;
        
        return [
            'taux_execution_budget' => $budgetPrevuTotal > 0 ? ($budgetReelTotal / $budgetPrevuTotal * 100) : 0,
            'budget_prevu_total' => $budgetPrevuTotal,
            'budget_reel_total' => $budgetReelTotal,
            'ecart_budget' => $budgetReelTotal - $budgetPrevuTotal,
        ];
    }
}
