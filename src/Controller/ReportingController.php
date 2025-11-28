<?php

namespace App\Controller;

use App\Repository\FormationSessionRepository;
use App\Repository\MissionSessionRepository;
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
        MissionSessionRepository $missionSessionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $filters = $request->request->all();
        
        // Préparer les données filtrées
        $data = [
            'resume' => $this->getResumeGlobal($filters, $formationSessionRepository, $missionSessionRepository),
            'formations' => $this->getFormationsData($filters, $formationSessionRepository),
            'missions' => $this->getMissionsData($filters, $missionSessionRepository),
            'parDirection' => $this->getDataParDirection($filters, $formationSessionRepository, $missionSessionRepository),
            'parPeriode' => $this->getDataParPeriode($filters, $formationSessionRepository, $missionSessionRepository),
            'statuts' => $this->getStatutsActivites($filters, $formationSessionRepository, $missionSessionRepository),
            'indicateurs' => $this->getIndicateursClés($filters, $formationSessionRepository, $missionSessionRepository),
        ];

        return new JsonResponse($data);
    }

    #[Route('/data/resume', name: 'app_reporting_data_resume', methods: ['POST'])]
    public function getResumeData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getResumeGlobal($filters, $formationSessionRepository, $missionSessionRepository));
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
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getMissionsData($filters, $missionSessionRepository));
    }

    #[Route('/data/directions', name: 'app_reporting_data_directions', methods: ['POST'])]
    public function getDirectionsData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getDataParDirection($filters, $formationSessionRepository, $missionSessionRepository));
    }

    #[Route('/data/periodes', name: 'app_reporting_data_periodes', methods: ['POST'])]
    public function getPeriodesData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getDataParPeriode($filters, $formationSessionRepository, $missionSessionRepository));
    }

    #[Route('/data/statuts', name: 'app_reporting_data_statuts', methods: ['POST'])]
    public function getStatutsData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getStatutsActivites($filters, $formationSessionRepository, $missionSessionRepository));
    }

    #[Route('/data/indicateurs', name: 'app_reporting_data_indicateurs', methods: ['POST'])]
    public function getIndicateursData(
        Request $request,
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository
    ): JsonResponse {
        $filters = $request->request->all();
        return new JsonResponse($this->getIndicateursClés($filters, $formationSessionRepository, $missionSessionRepository));
    }

    #[Route('/api/filters-data', name: 'app_reporting_api_filters', methods: ['GET'])]
    public function getFiltersData(
        FormationSessionRepository $formationSessionRepository,
        MissionSessionRepository $missionSessionRepository,
        ServiceRepository $serviceRepository,
        DirectionRepository $directionRepository,
        StatutActiviteRepository $statutActiviteRepository,
        TypeFondsRepository $typeFondsRepository
    ): JsonResponse {
        // Récupérer les années disponibles depuis les sessions de formation et missions
        try {
            $yearsFormations = $formationSessionRepository->getAvailableYears();
            $yearsMissions = $missionSessionRepository->getAvailableYears();
            
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
        MissionSessionRepository $missionSessionRepository
    ): Response {
        $filters = $request->request->all();
        
        // Récupérer les données
        $data = [
            'resume' => $this->getResumeGlobal($filters, $formationSessionRepository, $missionSessionRepository),
            'formations' => $this->getFormationsData($filters, $formationSessionRepository),
            'missions' => $this->getMissionsData($filters, $missionSessionRepository),
            'parDirection' => $this->getDataParDirection($filters, $formationSessionRepository, $missionSessionRepository),
            'statuts' => $this->getStatutsActivites($filters, $formationSessionRepository, $missionSessionRepository),
            'indicateurs' => $this->getIndicateursClés($filters, $formationSessionRepository, $missionSessionRepository),
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

    private function getResumeGlobal(array $filters, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        // Récupérer toutes les sessions de formation et missions
        $formationSessions = $formationSessionRepository->findAll();
        $missionSessions = $missionSessionRepository->findAll();
        
        // Filtrer les sessions selon les critères
        $formationSessions = array_filter($formationSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDate = $session->getDatePrevueDebut();
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                if ($sessionDate < $dateDebut) {
                    return false;
                }
            }
            if (!empty($filters['date_fin'])) {
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                if ($sessionDate > $dateFin) {
                    return false;
                }
            }
            
            // Si pas de filtres de date, utiliser l'année
            if (empty($filters['date_debut']) && empty($filters['date_fin'])) {
                if ($sessionDate->format('Y') != $year) {
                    return false;
                }
            }
            
            return true;
        });
        
        $missionSessions = array_filter($missionSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDate = $session->getDatePrevueDebut();
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                if ($sessionDate < $dateDebut) {
                    return false;
                }
            }
            if (!empty($filters['date_fin'])) {
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59);
                if ($sessionDate > $dateFin) {
                    return false;
                }
            }
            
            // Si pas de filtres de date, utiliser l'année
            if (empty($filters['date_debut']) && empty($filters['date_fin'])) {
                if ($sessionDate->format('Y') != $year) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Compter par statut
        $statutsData = [];
        foreach ($formationSessions as $session) {
            $statut = $session->getStatutActivite();
            if ($statut) {
                $statutLibelle = $statut->getLibelle();
                if (!isset($statutsData[$statutLibelle])) {
                    $statutsData[$statutLibelle] = [
                        'libelle' => $statutLibelle,
                        'formations' => 0,
                        'missions' => 0,
                        'total' => 0
                    ];
                }
                $statutsData[$statutLibelle]['formations']++;
                $statutsData[$statutLibelle]['total']++;
            }
        }
        
        foreach ($missionSessions as $session) {
            $statut = $session->getStatutActivite();
            if ($statut) {
                $statutLibelle = $statut->getLibelle();
                if (!isset($statutsData[$statutLibelle])) {
                    $statutsData[$statutLibelle] = [
                        'libelle' => $statutLibelle,
                        'formations' => 0,
                        'missions' => 0,
                        'total' => 0
                    ];
                }
                $statutsData[$statutLibelle]['missions']++;
                $statutsData[$statutLibelle]['total']++;
            }
        }
        
        // Calculer le total des activités et le total des activités réalisées
        $totalActivites = 0;
        $totalRealisees = 0;
        foreach ($statutsData as $data) {
            $totalActivites += $data['total'];
            // Les activités réalisées sont celles avec les statuts "Prévue exécutée" et "Non prévue exécutée"
            if (in_array($data['libelle'], ['Prévue exécutée', 'Non prévue exécutée'])) {
                $totalRealisees += $data['total'];
            }
        }
        
        // Calculer les budgets pour la période filtrée
        $budgetPrevu = 0;
        $budgetReel = 0;
        foreach ($formationSessions as $session) {
            $budgetPrevu += (float)$session->getBudgetPrevu();
            $budgetReel += (float)$session->getBudgetReel();
        }
        foreach ($missionSessions as $session) {
            $budgetPrevu += (float)$session->getBudgetPrevu();
            $budgetReel += (float)$session->getBudgetReel();
        }
        
        return [
            'statuts' => array_values($statutsData),
            'total_activites' => $totalActivites,
            'total_realisees' => $totalRealisees,
            'budget_total_prevu' => $budgetPrevu,
            'budget_total_realise' => $budgetReel,
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
        
        // Filtrer par date, année et mois si nécessaire
        $formationSessions = array_filter($formationSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDate = $session->getDatePrevueDebut();
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                if ($sessionDate < $dateDebut) {
                    return false;
                }
            }
            if (!empty($filters['date_fin'])) {
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59); // Inclure toute la journée
                if ($sessionDate > $dateFin) {
                    return false;
                }
            }
            
            // Si pas de filtres de date, utiliser l'année et le mois
            if (empty($filters['date_debut']) && empty($filters['date_fin'])) {
                if ($sessionDate->format('Y') != $year) {
                    return false;
                }
                if (!empty($filters['mois']) && $sessionDate->format('n') != $filters['mois']) {
                    return false;
                }
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

    private function getMissionsData(array $filters, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        // Construire les filtres pour MissionSession
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
        
        // Récupérer les sessions de mission avec les filtres
        $missionSessions = $missionSessionRepository->findWithFilters($sessionFilters);
        
        // Filtrer par date, année et mois si nécessaire
        $missionSessions = array_filter($missionSessions, function($session) use ($year, $filters) {
            if (!$session->getDatePrevueDebut()) {
                return false;
            }
            
            $sessionDate = $session->getDatePrevueDebut();
            
            // Filtrer par date_debut et date_fin si fournis
            if (!empty($filters['date_debut'])) {
                $dateDebut = new \DateTime($filters['date_debut']);
                if ($sessionDate < $dateDebut) {
                    return false;
                }
            }
            if (!empty($filters['date_fin'])) {
                $dateFin = new \DateTime($filters['date_fin']);
                $dateFin->setTime(23, 59, 59); // Inclure toute la journée
                if ($sessionDate > $dateFin) {
                    return false;
                }
            }
            
            // Si pas de filtres de date, utiliser l'année et le mois
            if (empty($filters['date_debut']) && empty($filters['date_fin'])) {
                if ($sessionDate->format('Y') != $year) {
                    return false;
                }
                if (!empty($filters['mois']) && $sessionDate->format('n') != $filters['mois']) {
                    return false;
                }
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
        $missionsArray = [];
        $totalBudget = 0;
        $totalParticipants = 0;
        
        foreach ($missionSessions as $session) {
            $mission = $session->getMission();
            $totalBudget += (float)$session->getBudgetPrevu();
            $totalParticipants += $session->getUserMissions()->count();
            
            $missionsArray[] = [
                'id' => $session->getId(),
                'titre' => $mission ? $mission->getTitre() : '',
                'description' => $mission ? $mission->getDescription() : null,
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
            'liste' => $missionsArray,
            'stats' => [
                'total' => count($missionsArray),
                'budget_total' => $totalBudget,
                'participants_total' => $totalParticipants,
            ]
        ];
    }

    private function getDataParDirection(array $filters, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getExecutionRateByDirection($year),
            'missions' => $missionSessionRepository->getExecutionRateByDirection($year),
        ];
    }

    private function getDataParPeriode(array $filters, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getMonthlyStatsByYear($year),
            'missions' => $missionSessionRepository->getMonthlyStatsByYear($year),
        ];
    }

    private function getStatutsActivites(array $filters, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        return [
            'formations' => $formationSessionRepository->getStatusDistributionByYear($year),
            'missions' => $missionSessionRepository->getStatusDistributionByYear($year),
        ];
    }

    private function getIndicateursClés(array $filters, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository): array
    {
        $year = $filters['annee'] ?? date('Y');
        
        $budgetPrevuFormations = $formationSessionRepository->getTotalBudgetByYear($year);
        $budgetReelFormations = $formationSessionRepository->getTotalRealExpensesByYear($year);
        $budgetPrevuMissions = $missionSessionRepository->getTotalBudgetByYear($year);
        $budgetReelMissions = $missionSessionRepository->getTotalRealExpensesByYear($year);
        
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
