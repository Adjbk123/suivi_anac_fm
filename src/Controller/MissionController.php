<?php

namespace App\Controller;

use App\Entity\DepenseMission;
use App\Entity\DepenseMissionParticipant;
use App\Entity\DocumentMission;
use App\Entity\Mission;
use App\Entity\MissionSession;
use App\Entity\User;
use App\Entity\UserMission;
use App\Repository\DirectionRepository;
use App\Repository\MissionRepository;
use App\Repository\MissionSessionRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\UserRepository;
use App\Service\ExcelExportService;
use App\Service\PdfService;
use App\Service\PerformanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mission')]
class MissionController extends AbstractController
{
    #[Route('/', name: 'app_mission_index', methods: ['GET'])]
    public function index(PerformanceService $performanceService): Response
    {
        $currentYear = date('Y');
        $performanceData = $performanceService->getMissionPerformance($currentYear);

        return $this->render('mission/index.html.twig', [
            'performanceData' => $performanceData,
            'currentYear' => $currentYear,
        ]);
    }

    #[Route('/export-excel', name: 'app_mission_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, MissionSessionRepository $missionSessionRepository, ExcelExportService $excelExportService): Response
    {
        $filters = [
            'direction' => $request->query->get('direction'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search'),
        ];

        $missionSessions = $missionSessionRepository->findWithFilters($filters);

        return $excelExportService->exportMissionsToExcel($missionSessions, $filters);
    }

    #[Route('/export-budget-report', name: 'app_mission_export_budget_report', methods: ['GET'])]
    public function exportBudgetReport(Request $request, MissionSessionRepository $missionSessionRepository, ExcelExportService $excelExportService): Response
    {
        $filters = [
            'direction' => $request->query->get('direction'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search'),
        ];

        $missionSessions = $missionSessionRepository->findWithFilters($filters);

        return $excelExportService->exportMissionBudgetReport($missionSessions, $filters);
    }

    #[Route('/create', name: 'app_mission_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function create(): Response
    {
        return $this->render('mission/create.html.twig');
    }

    #[Route('/modele/create', name: 'app_mission_modele_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function createModele(): Response
    {
        return $this->render('mission/modele_create.html.twig');
    }

    #[Route('/modele', name: 'app_mission_modele_store', methods: ['POST'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function storeModele(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty(trim((string) ($data['titre'] ?? '')))) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre de la mission est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $mission = new Mission();
        $mission->setTitre(trim($data['titre']));
        $mission->setDescription($data['description'] ?? null);
        $mission->setCreatedAt(new \DateTime());

        $entityManager->persist($mission);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de mission créé avec succès.',
            'missionId' => $mission->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/modele/{id}/edit', name: 'app_mission_modele_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function editModele(Mission $mission): Response
    {
        return $this->render('mission/modele_edit.html.twig', [
            'mission' => $mission,
        ]);
    }

    #[Route('/modele/{id}', name: 'app_mission_modele_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function updateModele(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            return $this->json([
                'success' => false,
                'message' => 'Le titre de la mission est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $mission->setTitre($titre);
        $mission->setDescription($data['description'] ?? null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de mission mis à jour avec succès.',
        ]);
    }

    #[Route('/modele/{id}', name: 'app_mission_modele_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function deleteModele(Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$mission->getSessions()->isEmpty()) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce modèle : des sessions y sont associées.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($mission);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de mission supprimé avec succès.',
        ]);
    }

    #[Route('/modeles', name: 'app_mission_modele_index', methods: ['GET'])]
    public function modelesIndex(MissionRepository $missionRepository): Response
    {
        $modeles = $missionRepository->findBy([], ['titre' => 'ASC']);

        return $this->render('mission/modele_index.html.twig', [
            'modeles' => $modeles,
        ]);
    }

    #[Route('/list', name: 'app_mission_list', methods: ['GET'])]
    public function list(Request $request, MissionSessionRepository $missionSessionRepository): JsonResponse
    {
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');

        $missionSessions = $missionSessionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);
        $data = [];

        foreach ($missionSessions as $session) {
            $mission = $session->getMission();
            $data[] = [
                'id' => $session->getId(),
                'missionId' => $mission?->getId(),
                'titre' => $mission?->getTitre() ?? '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                'lieuPrevu' => $session->getLieuPrevu() ?? '-',
                'dureePrevue' => $session->getDureePrevue() ? $session->getDureePrevue() . ' jours' : '-',
                'budgetPrevu' => number_format((float) $session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary',
                'statut_code' => $session->getStatutActivite() ? $session->getStatutActivite()->getCode() : null,
            ];
        }

        return $this->json($data);
    }

    #[Route('/filters/data', name: 'app_mission_filters_data', methods: ['GET'])]
    public function getFiltersData(
        DirectionRepository $directionRepository,
        StatutActiviteRepository $statutRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $directions = array_map(static fn ($direction) => [
            'id' => $direction->getId(),
            'libelle' => $direction->getLibelle(),
        ], $directionRepository->findAll());

        $statuts = array_map(static fn ($statut) => [
            'id' => $statut->getId(),
            'libelle' => $statut->getLibelle(),
            'couleur' => $statut->getCouleur(),
        ], $statutRepository->findAll());

        $users = array_map(static fn ($user) => [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
        ], $userRepository->findAll());

        return $this->json([
            'directions' => $directions,
            'statuts' => $statuts,
            'users' => $users,
        ]);
    }

    #[Route('/executed', name: 'app_mission_executed', methods: ['GET'])]
    public function executed(): Response
    {
        return $this->render('mission/executed.html.twig');
    }

    #[Route('/executed/list', name: 'app_mission_executed_list', methods: ['GET'])]
    public function executedList(MissionSessionRepository $missionSessionRepository): JsonResponse
    {
        $sessions = $missionSessionRepository->findExecutedSessions();
        $data = [];

        foreach ($sessions as $session) {
            $mission = $session->getMission();
            $depensesReelles = 0;
            foreach ($session->getDepenseMissions() as $depense) {
                $depensesReelles += (float) ($depense->getMontantReel() ?? 0);
            }

            $data[] = [
                'id' => $session->getId(),
                'missionId' => $mission?->getId(),
                'titre' => $mission?->getTitre() ?? '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'dateReelleDebut' => $session->getDateReelleDebut() ? $session->getDateReelleDebut()->format('d/m/Y') : '-',
                'dateReelleFin' => $session->getDateReelleFin() ? $session->getDateReelleFin()->format('d/m/Y') : '-',
                'lieuReel' => $session->getLieuReel() ?? '-',
                'dureeReelle' => $session->getDureeReelle() ? $session->getDureeReelle() . ' jours' : '-',
                'budgetPrevu' => number_format((float) $session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'depensesReelles' => number_format($depensesReelles, 0, ',', ' ') . ' FCFA',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary',
                'statut_code' => $session->getStatutActivite() ? $session->getStatutActivite()->getCode() : null,
            ];
        }

        return $this->json($data);
    }

    #[Route('/planned', name: 'app_mission_planned', methods: ['GET'])]
    public function planned(): Response
    {
        return $this->render('mission/planned.html.twig');
    }

    #[Route('/planned/list', name: 'app_mission_planned_list', methods: ['GET'])]
    public function plannedList(MissionSessionRepository $missionSessionRepository): JsonResponse
    {
        $sessions = $missionSessionRepository->findPlannedSessions();
        $data = [];

        foreach ($sessions as $session) {
            $mission = $session->getMission();
            $data[] = [
                'id' => $session->getId(),
                'missionId' => $mission?->getId(),
                'titre' => $mission?->getTitre() ?? '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                'lieuPrevu' => $session->getLieuPrevu() ?? '-',
                'dureePrevue' => $session->getDureePrevue() ? $session->getDureePrevue() . ' jours' : '-',
                'budgetPrevu' => number_format((float) $session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'participantsPrevu' => count($session->getUserMissions()) . ' participant(s)',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary',
                'statut_code' => $session->getStatutActivite() ? $session->getStatutActivite()->getCode() : null,
            ];
        }

        return $this->json($data);
    }

    #[Route('/new', name: 'app_mission_new', methods: ['POST'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager, MissionRepository $missionRepository): JsonResponse
    {
        try {
            $data = json_decode($request->get('data'), true);
            if (!$data) {
                return $this->json(['success' => false, 'message' => 'Données JSON invalides'], 400);
            }

            $requiredFields = ['titre', 'directionId', 'fondsId', 'lieuPrevu', 'datePrevueDebut', 'datePrevueFin', 'dureePrevue'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    return $this->json(['success' => false, 'message' => "Le champ {$field} est requis"], 400);
                }
            }

            $mission = null;
            if (!empty($data['missionId'])) {
                $mission = $missionRepository->find((int) $data['missionId']);
            }

            if (!$mission) {
                $mission = new Mission();
                $mission->setTitre($data['titre']);
                $mission->setDescription($data['description'] ?? null);
                $entityManager->persist($mission);
            } else {
                $mission->setTitre($data['titre']);
                $mission->setDescription($data['description'] ?? $mission->getDescription());
            }

            $missionSession = new MissionSession();
            $missionSession->setMission($mission);
            $missionSession->setLieuPrevu($data['lieuPrevu']);
            $missionSession->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
            $missionSession->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
            $missionSession->setDureePrevue((int) $data['dureePrevue']);
            $missionSession->setBudgetPrevu(isset($data['budgetPrevu']) ? (string) (float) $data['budgetPrevu'] : '0');
            $missionSession->setNotes($data['notes'] ?? null);

            $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_non_executee']);
            if (!$statutActivite) {
                return $this->json(['success' => false, 'message' => "Statut d'activité par défaut introuvable"], 500);
            }
            $missionSession->setStatutActivite($statutActivite);

            $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
            if (!$direction) {
                return $this->json(['success' => false, 'message' => 'Direction introuvable'], 404);
            }
            $missionSession->setDirection($direction);

            $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
            if (!$fonds) {
                return $this->json(['success' => false, 'message' => 'Type de fonds introuvable'], 404);
            }
            $missionSession->setFonds($fonds);

            $entityManager->persist($missionSession);
            $entityManager->flush();

            $userMissionsByUserId = [];
            if (isset($data['participants']) && is_array($data['participants'])) {
                $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
                foreach ($data['participants'] as $userId) {
                    $user = $entityManager->getRepository(User::class)->find($userId);
                    if ($user) {
                        $userMission = new UserMission();
                        $userMission->setUser($user);
                        $userMission->setMissionSession($missionSession);
                        $userMission->setStatutParticipation($statutParticipation);
                        $entityManager->persist($userMission);
                        $userMissionsByUserId[$user->getId()] = $userMission;
                    }
                }
            }

            $totalBudgetPrevu = 0;
            $depenseEntitiesByCategory = [];
            $participantDepensesPayload = isset($data['participantDepenses']) && is_array($data['participantDepenses'])
                ? $data['participantDepenses']
                : [];

            if (!empty($participantDepensesPayload)) {
                $allocationsByCategory = [];

                foreach ($participantDepensesPayload as $row) {
                    $participantId = (int) ($row['participantId'] ?? 0);
                    $categorieId = (int) ($row['categorieId'] ?? 0);
                    $montant = isset($row['montant']) ? (float) $row['montant'] : 0;

                    if ($participantId <= 0 || $categorieId <= 0 || $montant <= 0) {
                        continue;
                    }

                    $allocationsByCategory[$categorieId][] = [
                        'participantId' => $participantId,
                        'montant' => $montant,
                    ];
                }

                foreach ($allocationsByCategory as $categorieId => $allocations) {
                    $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($categorieId);
                    if (!$categorie) {
                        continue;
                    }

                    $montantTotal = array_reduce($allocations, static function (float $carry, array $item): float {
                        return $carry + ($item['montant'] ?? 0);
                    }, 0.0);

                    if ($montantTotal <= 0) {
                        continue;
                    }

                    $depenseMission = new DepenseMission();
                    $depenseMission->setMissionSession($missionSession);
                    $depenseMission->setCategorie($categorie);
                    $depenseMission->setMontantPrevu(number_format($montantTotal, 2, '.', ''));
                    $entityManager->persist($depenseMission);

                    $depenseEntitiesByCategory[$categorieId] = $depenseMission;
                    $totalBudgetPrevu += $montantTotal;
                }

                foreach ($allocationsByCategory as $categorieId => $allocations) {
                    $depenseMission = $depenseEntitiesByCategory[$categorieId] ?? null;
                    if (!$depenseMission) {
                        continue;
                    }

                    foreach ($allocations as $allocation) {
                        $participantId = $allocation['participantId'] ?? 0;
                        $userMission = $userMissionsByUserId[$participantId] ?? null;
                        if (!$userMission) {
                            continue;
                        }

                        $allocationEntity = new DepenseMissionParticipant();
                        $allocationEntity->setDepenseMission($depenseMission);
                        $allocationEntity->setUserMission($userMission);
                        $allocationEntity->setMontantPrevu(number_format($allocation['montant'], 2, '.', ''));
                        $entityManager->persist($allocationEntity);
                    }
                }
            } elseif (isset($data['depenses']) && is_array($data['depenses'])) {
                foreach ($data['depenses'] as $depense) {
                    $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depense['categorieId'] ?? null);
                    if ($categorie && isset($depense['montant'])) {
                        $depenseMission = new DepenseMission();
                        $depenseMission->setMissionSession($missionSession);
                        $depenseMission->setCategorie($categorie);
                        $depenseMission->setMontantPrevu(number_format((float) $depense['montant'], 2, '.', ''));
                        $entityManager->persist($depenseMission);
                        $totalBudgetPrevu += (float) $depense['montant'];
                    }
                }
            }

            if ($totalBudgetPrevu > 0) {
                $missionSession->setBudgetPrevu((string) $totalBudgetPrevu);
            }

            $index = 0;
            while ($request->files->has("document_{$index}")) {
                $file = $request->files->get("document_{$index}");
                $nom = $request->get("document_nom_{$index}");

                if ($file && $nom) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                    $fileSize = $file->getSize();
                    $fileType = $file->getClientMimeType();

                    $file->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );

                    $documentMission = new DocumentMission();
                    $documentMission->setMissionSession($missionSession);
                    $documentMission->setNom($nom);
                    $documentMission->setNomFichier($newFilename);
                    $documentMission->setType($fileType);
                    $documentMission->setTaille($fileSize);

                    $entityManager->persist($documentMission);
                }

                $index++;
            }

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Mission créée avec succès',
                'missionId' => $mission->getId(),
                'missionSessionId' => $missionSession->getId(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la mission: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/session/{id}', name: 'app_mission_session_show', methods: ['GET'])]
    public function showSession(MissionSession $missionSession): Response
    {
        [$participantStats, $depenseAllocations, $allocationAlerts] = $this->buildMissionAllocationViewData($missionSession);
        [$participantOptionsData, $allocationIndexData] = $this->buildMissionParticipantSelectData($missionSession);

        return $this->render('mission/show.html.twig', [
            'missionSession' => $missionSession,
            'mission' => $missionSession->getMission(),
            'participantExpenseStats' => $participantStats,
            'depenseAllocationsView' => $depenseAllocations,
            'allocationAlerts' => $allocationAlerts,
            'participantOptionsData' => $participantOptionsData,
            'allocationIndexData' => $allocationIndexData,
        ]);
    }

    #[Route('/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(MissionSessionRepository $missionSessionRepository, MissionRepository $missionRepository, int $id): Response
    {
        $missionSession = $missionSessionRepository->find($id);
        if ($missionSession) {
            [$participantStats, $depenseAllocations, $allocationAlerts] = $this->buildMissionAllocationViewData($missionSession);
            [$participantOptionsData, $allocationIndexData] = $this->buildMissionParticipantSelectData($missionSession);

            return $this->render('mission/show.html.twig', [
                'missionSession' => $missionSession,
                'mission' => $missionSession->getMission(),
                'participantExpenseStats' => $participantStats,
                'depenseAllocationsView' => $depenseAllocations,
                'allocationAlerts' => $allocationAlerts,
                'participantOptionsData' => $participantOptionsData,
                'allocationIndexData' => $allocationIndexData,
            ]);
        }

        $mission = $missionRepository->find($id);
        if (!$mission) {
            throw $this->createNotFoundException('Mission ou session introuvable');
        }

        $sessions = $missionSessionRepository->findMissionSessions($mission->getId());

        return $this->render('mission/template_show.html.twig', [
            'mission' => $mission,
            'sessions' => $sessions,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mission_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function edit(MissionSessionRepository $missionSessionRepository, int $id): Response
    {
        $missionSession = $missionSessionRepository->find($id);
        if (!$missionSession) {
            throw $this->createNotFoundException('Session de mission introuvable');
        }

        return $this->render('mission/edit.html.twig', [
            'missionSession' => $missionSession,
            'mission' => $missionSession->getMission(),
        ]);
    }

    #[Route('/{id}/update', name: 'app_mission_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function update(Request $request, MissionSessionRepository $missionSessionRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $missionSession = $missionSessionRepository->find($id);
        if (!$missionSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de mission introuvable',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $mission = $missionSession->getMission();
        if (!empty($data['missionModeleId']) && (int) $data['missionModeleId'] !== $mission->getId()) {
            $nouvelleMission = $entityManager->getRepository(Mission::class)->find((int) $data['missionModeleId']);
            if (!$nouvelleMission) {
                return $this->json([
                    'success' => false,
                    'message' => 'Modèle de mission introuvable',
                ], 404);
            }
            $missionSession->setMission($nouvelleMission);
            $mission = $nouvelleMission;
        }

        if (isset($data['description'])) {
            $mission->setDescription($data['description']);
        }

        if (isset($data['lieuPrevu'])) {
            $missionSession->setLieuPrevu($data['lieuPrevu']);
        }
        if (isset($data['datePrevueDebut'])) {
            $missionSession->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        }
        if (isset($data['datePrevueFin'])) {
            $missionSession->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        }
        if (isset($data['dureePrevue'])) {
            $missionSession->setDureePrevue((int) $data['dureePrevue']);
        }
        if (isset($data['budgetPrevu'])) {
            $missionSession->setBudgetPrevu((string) (float) $data['budgetPrevu']);
        }
        if (isset($data['notes'])) {
            $missionSession->setNotes($data['notes']);
        }
        if (isset($data['directionId'])) {
            $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
            $missionSession->setDirection($direction);
        }
        if (isset($data['fondsId'])) {
            $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
            $missionSession->setFonds($fonds);
        }
        if (!empty($data['statutActiviteId'])) {
            $statut = $entityManager->getRepository(\App\Entity\StatutActivite::class)->find($data['statutActiviteId']);
            if ($statut) {
                $missionSession->setStatutActivite($statut);
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Session de mission mise à jour avec succès',
        ]);
    }

    #[Route('/{id}', name: 'app_mission_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(MissionSessionRepository $missionSessionRepository, MissionRepository $missionRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $missionSession = $missionSessionRepository->find($id);
        if ($missionSession) {
            $mission = $missionSession->getMission();
            $entityManager->remove($missionSession);
            $entityManager->flush();

            if ($mission && $mission->getSessions()->count() === 0) {
                $entityManager->remove($mission);
                $entityManager->flush();
            }

            return $this->json([
                'success' => true,
                'message' => 'Session de mission supprimée avec succès',
            ]);
        }

        $mission = $missionRepository->find($id);
        if ($mission) {
            foreach ($mission->getSessions() as $session) {
                $entityManager->remove($session);
            }
            $entityManager->remove($mission);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Mission supprimée avec succès',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Mission ou session introuvable',
        ], 404);
    }

    private function recalculateBudgetPrevu(MissionSession $missionSession): float
    {
        $totalPrevu = 0;
        foreach ($missionSession->getDepenseMissions() as $depense) {
            $totalPrevu += (float) $depense->getMontantPrevu();
        }

        return $totalPrevu;
    }

    #[Route('/{id}/add-document', name: 'app_mission_add_document', methods: ['POST'])]
    public function addDocument(Request $request, MissionSession $missionSession, EntityManagerInterface $entityManager): JsonResponse
    {
        $file = $request->files->get('file');
        $nom = $request->get('nom');

        if (!$file || !$nom) {
            return $this->json([
                'success' => false,
                'message' => 'Fichier et nom requis',
            ], 400);
        }

        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
            $fileSize = $file->getSize();
            $fileType = $file->getClientMimeType();

            $file->move(
                $this->getParameter('documents_directory'),
                $newFilename
            );

            $documentMission = new DocumentMission();
            $documentMission->setMissionSession($missionSession);
            $documentMission->setNom($nom);
            $documentMission->setNomFichier($newFilename);
            $documentMission->setType($fileType);
            $documentMission->setTaille($fileSize);

            $entityManager->persist($documentMission);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Document ajouté avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => "Erreur lors de l'ajout du document: " . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}/add-depense', name: 'app_mission_add_depense', methods: ['POST'])]
    public function addDepense(Request $request, MissionSession $missionSession, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['categorieId']) || !isset($data['montant']) || !isset($data['participantId'])) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie, participant et montant requis',
            ], 400);
        }

        try {
            $montant = (float) $data['montant'];
            if ($montant <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le montant doit être supérieur à zéro',
                ], 400);
            }

            $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($data['categorieId']);
            if (!$categorie) {
                return $this->json([
                    'success' => false,
                    'message' => 'Catégorie de dépense introuvable',
                ], 404);
            }

            $userMission = $entityManager->getRepository(UserMission::class)->find($data['participantId']);
            if (!$userMission || $userMission->getMissionSession()->getId() !== $missionSession->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Participant invalide pour cette mission',
                ], 400);
            }

            $depenseMission = $entityManager->getRepository(DepenseMission::class)->findOneBy([
                'missionSession' => $missionSession,
                'categorie' => $categorie,
            ]);

            if (!$depenseMission) {
                $depenseMission = new DepenseMission();
                $depenseMission->setMissionSession($missionSession);
                $depenseMission->setCategorie($categorie);
                $depenseMission->setMontantPrevu(number_format(0, 2, '.', ''));
                $entityManager->persist($depenseMission);
            }

            $existingAllocation = $entityManager->getRepository(DepenseMissionParticipant::class)->findOneBy([
                'depenseMission' => $depenseMission,
                'userMission' => $userMission,
            ]);

            if ($existingAllocation) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ce participant possède déjà une dépense pour cette catégorie.',
                ], 400);
            }

            $currentMontant = (float) ($depenseMission->getMontantPrevu() ?? 0);
            $depenseMission->setMontantPrevu(number_format($currentMontant + $montant, 2, '.', ''));

            $allocation = new DepenseMissionParticipant();
            $allocation->setDepenseMission($depenseMission);
            $allocation->setUserMission($userMission);
            $allocation->setMontantPrevu(number_format($montant, 2, '.', ''));
            $entityManager->persist($allocation);

            $entityManager->flush();

            $totalPrevu = $this->recalculateBudgetPrevu($missionSession);
            $missionSession->setBudgetPrevu((string) $totalPrevu);
            $entityManager->persist($missionSession);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Dépense ajoutée avec succès',
                'totaux' => [
                    'totalPrevu' => $totalPrevu,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => "Erreur lors de l'ajout de la dépense: " . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}/add-participant', name: 'app_mission_add_participant', methods: ['POST'])]
    public function addParticipant(Request $request, MissionSession $missionSession, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? ($data['userId'] ?? null);

        if (!$userIds) {
            return $this->json([
                'success' => false,
                'message' => 'ID utilisateur requis',
            ], 400);
        }

        $direction = $missionSession->getDirection();
        if (!$direction) {
            return $this->json([
                'success' => false,
                'message' => 'Aucune direction associée à la mission',
            ], 400);
        }

        $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
        $added = 0;

        foreach ((array) $userIds as $userId) {
            $user = $entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => "Utilisateur introuvable (ID: {$userId})",
                ], 404);
            }

            $service = $user->getService();
            $serviceDirection = $service ? $service->getDirection() : null;
            if (!$serviceDirection || $serviceDirection->getId() !== $direction->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => "L'utilisateur {$user->getNom()} {$user->getPrenom()} n'appartient pas à la direction de la mission",
                ], 400);
            }

            $existing = $entityManager->getRepository(UserMission::class)->findOneBy([
                'missionSession' => $missionSession,
                'user' => $user,
            ]);

            if ($existing) {
                continue;
            }

            $userMission = new UserMission();
            $userMission->setMissionSession($missionSession);
            $userMission->setUser($user);
            $userMission->setStatutParticipation($statutParticipation);

            $entityManager->persist($userMission);
            $added++;
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => $added > 1 ? "{$added} participants ajoutés avec succès" : 'Participant ajouté avec succès',
        ]);
    }

    #[Route('/{id}/realisation', name: 'app_mission_realisation', methods: ['GET'])]
    public function realisation(MissionSession $missionSession): Response
    {
        return $this->render('mission/realisation.html.twig', [
            'missionSession' => $missionSession,
            'mission' => $missionSession->getMission(),
        ]);
    }

    #[Route('/{id}/realisation/participants', name: 'app_mission_realisation_participants', methods: ['GET'])]
    public function getParticipantsForRealisation(MissionSession $missionSession, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = [
            'prevus' => [],
            'disponibles' => [],
        ];

        foreach ($missionSession->getUserMissions() as $userMission) {
            $user = $userMission->getUser();
            $data['prevus'][] = [
                'id' => $userMission->getId(),
                'userId' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'matricule' => $user->getMatricule(),
                'statut_participation_id' => $userMission->getStatutParticipation()?->getId(),
                'statut_libelle' => $userMission->getStatutParticipation()?->getLibelle() ?? 'Non défini',
                'statut_couleur' => $userMission->getStatutParticipation()?->getCouleur() ?? '#6c757d',
            ];
        }

        $direction = $missionSession->getDirection();
        if ($direction) {
            $users = [];
            foreach ($direction->getServices() as $service) {
                $serviceUsers = $entityManager->getRepository(User::class)->findBy(['service' => $service]);
                $users = array_merge($users, $serviceUsers);
            }

            $participantsIds = array_map(static fn (UserMission $um) => $um->getUser()->getId(), $missionSession->getUserMissions()->toArray());

            foreach ($users as $user) {
                if (!in_array($user->getId(), $participantsIds, true)) {
                    $data['disponibles'][] = [
                        'id' => $user->getId(),
                        'nom' => $user->getNom(),
                        'prenom' => $user->getPrenom(),
                        'matricule' => $user->getMatricule(),
                    ];
                }
            }
        }

        return $this->json($data);
    }

    #[Route('/{id}/realisation/depenses', name: 'app_mission_realisation_depenses', methods: ['GET'])]
    public function getRealisationDepenses(MissionSession $missionSession): JsonResponse
    {
        $depenses = [];
        $totalPrevu = 0;
        $totalReel = 0;

        foreach ($missionSession->getDepenseMissions() as $depense) {
            $montantPrevu = (float) $depense->getMontantPrevu();
            $montantReel = $depense->getMontantReel() ? (float) $depense->getMontantReel() : 0;
            $ecart = $montantReel - $montantPrevu;

            $totalPrevu += $montantPrevu;
            $totalReel += $montantReel;

            $allocations = [];
            $totalAllocationReelle = 0;

            foreach ($depense->getParticipantAllocations() as $allocation) {
                $userMission = $allocation->getUserMission();
                $user = $userMission?->getUser();
                $montantAllocation = $allocation->getMontantReel() ? (float) $allocation->getMontantReel() : 0;
                $totalAllocationReelle += $montantAllocation;

                $allocations[] = [
                    'id' => $allocation->getId(),
                    'userMissionId' => $userMission?->getId(),
                    'userId' => $user?->getId(),
                    'nom' => $user?->getNom(),
                    'prenom' => $user?->getPrenom(),
                    'montantPrevu' => $allocation->getMontantPrevu() ? (float) $allocation->getMontantPrevu() : null,
                    'montantReel' => $montantAllocation,
                ];
            }

            $depenses[] = [
                'id' => $depense->getId(),
                'categorie' => $depense->getCategorie()->getLibelle(),
                'categorieId' => $depense->getCategorie()->getId(),
                'montantPrevu' => $montantPrevu,
                'montantReel' => $montantReel,
                'ecart' => $ecart,
                'allocations' => $allocations,
                'allocationReelleTotale' => $totalAllocationReelle,
            ];
        }

        return $this->json([
            'depenses' => $depenses,
            'totaux' => [
                'totalPrevu' => $totalPrevu,
                'totalReel' => $totalReel,
                'totalEcart' => $totalReel - $totalPrevu,
            ],
        ]);
    }

    #[Route('/{id}/realisation/complete', name: 'app_mission_realisation_complete', methods: ['POST'])]
    public function completeRealisation(Request $request, MissionSession $missionSession, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Log pour débogage
        error_log('Mission realisation data received: ' . json_encode($data));

        try {
            $sessionUserMissionsById = [];
            $sessionUserMissionsByUserId = [];

            foreach ($missionSession->getUserMissions() as $existingUserMission) {
                $sessionUserMissionsById[$existingUserMission->getId()] = $existingUserMission;
                $user = $existingUserMission->getUser();
                if ($user) {
                    $sessionUserMissionsByUserId[$user->getId()] = $existingUserMission;
                }
            }

            // 1. Mettre à jour les informations de la mission
            if (isset($data['dateReelleDebut'])) {
                $missionSession->setDateReelleDebut(new \DateTime($data['dateReelleDebut']));
            }
            if (isset($data['dateReelleFin'])) {
                $missionSession->setDateReelleFin(new \DateTime($data['dateReelleFin']));
            }
            if (isset($data['lieuReel'])) {
                $missionSession->setLieuReel($data['lieuReel']);
            }

            // Calculer la durée réelle
            if (isset($data['dateReelleDebut']) && isset($data['dateReelleFin'])) {
                $dateDebut = new \DateTime($data['dateReelleDebut']);
                $dateFin = new \DateTime($data['dateReelleFin']);
                $dureeReelle = $dateDebut->diff($dateFin)->days + 1; // +1 pour inclure le jour de fin
                $missionSession->setDureeReelle($dureeReelle);
            }

            // 2. Mettre à jour le statut de l'activité selon la nature
            $natureCode = $data['natureMission'] ?? 'prevue_executee';
            $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => $natureCode]);
            if (!$statutActivite) {
                // Fallback vers le statut par défaut si le code n'existe pas
                $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_executee']);
            }
            $missionSession->setStatutActivite($statutActivite);

            // 3. Mettre à jour les statuts des participants
            if (isset($data['participants'])) {
                foreach ($data['participants'] as $participantData) {
                    $userMission = $entityManager->getRepository(\App\Entity\UserMission::class)->find($participantData['userMissionId']);
                    if ($userMission) {
                        $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->find($participantData['statut_participation_id']);
                        $userMission->setStatutParticipation($statutParticipation);
                    }
                }
            }

            // 4. Ajouter les nouveaux participants
            if (isset($data['nouveauxParticipants'])) {
                $statutNonPrevusParticipe = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'non_prevus_participe']);

                        foreach ($data['nouveauxParticipants'] as $userId) {
                    $user = $entityManager->getRepository(User::class)->find($userId);
                    if ($user) {
                        $userMission = new \App\Entity\UserMission();
                        $userMission->setMissionSession($missionSession);
                        $userMission->setUser($user);
                        $userMission->setStatutParticipation($statutNonPrevusParticipe);
                        $entityManager->persist($userMission);

                         $sessionUserMissionsByUserId[$user->getId()] = $userMission;
                                if ($userMission->getId()) {
                                    $sessionUserMissionsById[$userMission->getId()] = $userMission;
                                }
                    }
                }
            }

            // 5. Ajouter les dépenses réelles
            if (isset($data['depensesReelles'])) {
                foreach ($data['depensesReelles'] as $depenseData) {
                    $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depenseData['categorieId']);
                    if ($categorie) {
                        $depenseMission = $entityManager->getRepository(\App\Entity\DepenseMission::class)->findOneBy([
                            'missionSession' => $missionSession,
                            'categorie' => $categorie
                        ]);

                        if ($depenseMission) {
                            $depenseMission->setMontantReel($depenseData['montant']);
                        } else {
                            // Créer une nouvelle dépense si elle n'existe pas
                            $depenseMission = new \App\Entity\DepenseMission();
                            $depenseMission->setMissionSession($missionSession);
                            $depenseMission->setCategorie($categorie);
                            $depenseMission->setMontantReel($depenseData['montant']);
                            $entityManager->persist($depenseMission);
                        }

                        $existingAllocations = [];
                        foreach ($depenseMission->getParticipantAllocations() as $allocationEntity) {
                            $userMission = $allocationEntity->getUserMission();
                            if ($userMission && $userMission->getId()) {
                                $existingAllocations[$userMission->getId()] = $allocationEntity;
                            }
                        }

                        $allocationPayloads = $depenseData['allocations'] ?? [];
                        $keptAllocations = [];
                        $allocationTotal = 0.0;

                        foreach ($allocationPayloads as $allocationPayload) {
                            $allocationAmount = $allocationPayload['montant'] ?? $allocationPayload['montantReel'] ?? null;
                            if ($allocationAmount === null) {
                                continue;
                            }

                            $userMission = null;
                            if (!empty($allocationPayload['userMissionId'])) {
                                $userMissionId = (int) $allocationPayload['userMissionId'];
                                $userMission = $sessionUserMissionsById[$userMissionId] ?? $entityManager->getRepository(\App\Entity\UserMission::class)->find($userMissionId);
                                if ($userMission) {
                                    $sessionUserMissionsById[$userMission->getId()] = $userMission;
                                    $user = $userMission->getUser();
                                    if ($user) {
                                        $sessionUserMissionsByUserId[$user->getId()] = $userMission;
                                    }
                                }
                            } elseif (!empty($allocationPayload['userId'])) {
                                $userId = (int) $allocationPayload['userId'];
                                $userMission = $sessionUserMissionsByUserId[$userId] ?? null;
                            }

                            if (!$userMission) {
                                throw new \InvalidArgumentException('Participant invalide détecté dans la répartition des dépenses.');
                            }

                            $allocationEntity = null;
                            $userMissionId = $userMission->getId();

                            if ($userMissionId && isset($existingAllocations[$userMissionId])) {
                                $allocationEntity = $existingAllocations[$userMissionId];
                            } else {
                                $allocationEntity = new DepenseMissionParticipant();
                                $allocationEntity->setDepenseMission($depenseMission);
                                $allocationEntity->setUserMission($userMission);
                                $entityManager->persist($allocationEntity);
                            }

                            $formattedAmount = number_format((float) $allocationAmount, 2, '.', '');
                            $allocationEntity->setMontantReel($formattedAmount);
                            $allocationTotal += (float) $formattedAmount;

                            if (array_key_exists('montantPrevu', $allocationPayload)) {
                                $formattedPlanned = $allocationPayload['montantPrevu'] === null
                                    ? null
                                    : number_format((float) $allocationPayload['montantPrevu'], 2, '.', '');
                                $allocationEntity->setMontantPrevu($formattedPlanned);
                            }

                            $keptAllocations[] = $allocationEntity;
                        }

                        foreach ($depenseMission->getParticipantAllocations() as $allocationEntity) {
                            if (!in_array($allocationEntity, $keptAllocations, true)) {
                                $entityManager->remove($allocationEntity);
                            }
                        }

                        if ($depenseMission->getMontantReel() !== null) {
                            $expected = (float) $depenseMission->getMontantReel();
                            if ($allocationTotal === 0.0) {
                                throw new \InvalidArgumentException(sprintf(
                                    "La dépense \"%s\" possède un montant réel de %s FCFA mais aucune répartition n'a été fournie.",
                                    $depenseMission->getCategorie()->getLibelle(),
                                    number_format($expected, 0, ',', ' ')
                                ));
                            }

                            if (abs($allocationTotal - $expected) > 0.5) {
                                throw new \InvalidArgumentException(sprintf(
                                    "Les montants répartis (%s FCFA) pour la dépense \"%s\" ne correspondent pas au montant réel (%s FCFA).",
                                    number_format($allocationTotal, 0, ',', ' '),
                                    $depenseMission->getCategorie()->getLibelle(),
                                    number_format($expected, 0, ',', ' ')
                                ));
                            }
                        }
                    }
                }
            }

            // Calculer le budget réel total
            $budgetReel = 0;
            foreach ($missionSession->getDepenseMissions() as $depense) {
                if ($depense->getMontantReel()) {
                    $budgetReel += (float) $depense->getMontantReel();
                }
            }
            $missionSession->setBudgetReel($budgetReel);

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Mission marquée comme réalisée avec succès',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la réalisation: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/export/pdf', name: 'app_mission_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, MissionSessionRepository $missionSessionRepository, PdfService $pdfService): Response
    {
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');

        $missionSessions = $missionSessionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);

        // Préparer les données pour le PDF avec toutes les colonnes
        $headers = [
            'ID', 'Titre', 'Description', 'Direction', 'Fonds', 'Lieu prévu', 'Lieu réel',
            'Date début prévue', 'Date fin prévue', 'Date début réelle', 'Date fin réelle',
            'Durée prévue', 'Budget prévu', 'Statut', 'Notes'
        ];
        $data = [];

        foreach ($missionSessions as $session) {
            $mission = $session->getMission();
            $data[] = [
                $session->getId(),
                $mission->getTitre(),
                $mission->getDescription() ?: '-',
                $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                $session->getLieuPrevu() ?: '-',
                $session->getLieuReel() ?: '-',
                $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                $session->getDateReelleDebut() ? $session->getDateReelleDebut()->format('d/m/Y') : '-',
                $session->getDateReelleFin() ? $session->getDateReelleFin()->format('d/m/Y') : '-',
                $session->getDureePrevue() . ' jours',
                number_format((float) $session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                $session->getNotes() ?: '-',
            ];
        }

        // Préparer les filtres appliqués
        $filters = [];
        if ($statutId) {
            $statut = $missionSessionRepository->getEntityManager()->getRepository('App\Entity\StatutActivite')->find($statutId);
            if ($statut) $filters[] = 'Statut: ' . $statut->getLibelle();
        }
        if ($directionId) {
            $direction = $missionSessionRepository->getEntityManager()->getRepository('App\Entity\Direction')->find($directionId);
            if ($direction) $filters[] = 'Direction: ' . $direction->getLibelle();
        }
        if ($periode) {
            $periodeTexts = [
                'mois' => 'Ce mois',
                'trimestre' => 'Ce trimestre',
                'annee' => 'Cette année'
            ];
            $filters[] = 'Période: ' . ($periodeTexts[$periode] ?? $periode);
        }
        if ($participant) {
            $user = $missionSessionRepository->getEntityManager()->getRepository('App\Entity\User')->find($participant);
            if ($user) $filters[] = 'Participant: ' . $user->getNom() . ' ' . $user->getPrenom();
        }

        // Générer le PDF
        $pdfContent = $pdfService->generateLandscapeTablePdf(
            'Liste des Missions',
            $headers,
            $data,
            $filters,
            'missions_anac_benin.pdf'
        );

        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'missions_anac_benin.pdf'
        ));

        return $response;
    }

    #[Route('/api/directions', name: 'app_mission_api_directions', methods: ['GET'])]
    public function getDirections(DirectionRepository $directionRepository): JsonResponse
    {
        $directions = $directionRepository->findAll();
        $data = [];

        foreach ($directions as $direction) {
            $data[] = [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle(),
            ];
        }

        return $this->json($data);
    }

    /**
     * Prépare les données d'affichage pour les répartitions de dépenses par participant.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: array<int, string>}
     */
    private function buildMissionAllocationViewData(MissionSession $missionSession): array
    {
        $participantStats = [];
        foreach ($missionSession->getUserMissions() as $userMission) {
            $user = $userMission->getUser();
            $participantStats[$userMission->getId()] = [
                'userMissionId' => $userMission->getId(),
                'nom' => $user?->getNom(),
                'prenom' => $user?->getPrenom(),
                'matricule' => $user?->getMatricule(),
                'email' => $user?->getEmail(),
                'statut' => $userMission->getStatutParticipation()?->getLibelle(),
                'statutCouleur' => $userMission->getStatutParticipation()?->getCouleur() ?: 'secondary',
                'totalPrevu' => 0.0,
                'totalReel' => 0.0,
                'details' => [],
            ];
        }

        $depenseAllocations = [];
        $alerts = [];

        foreach ($missionSession->getDepenseMissions() as $depense) {
            $allocations = [];
            $totalReparti = 0.0;
            $totalRepartiPrevu = 0.0;

            foreach ($depense->getParticipantAllocations() as $allocation) {
                $userMission = $allocation->getUserMission();
                if (!$userMission) {
                    continue;
                }

                $user = $userMission->getUser();
                $montantPrevu = $allocation->getMontantPrevu() !== null ? (float) $allocation->getMontantPrevu() : null;
                $montantReel = $allocation->getMontantReel() !== null ? (float) $allocation->getMontantReel() : null;

                $allocData = [
                    'allocationId' => $allocation->getId(),
                    'userMissionId' => $userMission->getId(),
                    'nom' => $user?->getNom(),
                    'prenom' => $user?->getPrenom(),
                    'matricule' => $user?->getMatricule(),
                    'montantPrevu' => $montantPrevu,
                    'montantReel' => $montantReel,
                ];
                $allocations[] = $allocData;

                if ($montantReel !== null) {
                    $totalReparti += $montantReel;
                }
                if ($montantPrevu !== null) {
                    $totalRepartiPrevu += $montantPrevu;
                }

                $statKey = $userMission->getId();
                if ($statKey && isset($participantStats[$statKey])) {
                    if ($montantPrevu !== null) {
                        $participantStats[$statKey]['totalPrevu'] += $montantPrevu;
                    }
                    if ($montantReel !== null) {
                        $participantStats[$statKey]['totalReel'] += $montantReel;
                    }
                    $participantStats[$statKey]['details'][] = [
                        'allocationId' => $allocation->getId(),
                        'categorie' => $depense->getCategorie()->getLibelle(),
                        'montantPrevu' => $montantPrevu,
                        'montantReel' => $montantReel,
                    ];
                }
            }

            $montantReel = $depense->getMontantReel() ? (float) $depense->getMontantReel() : 0.0;
            $isBalanced = $montantReel === 0.0 || abs($montantReel - $totalReparti) < 0.5;

            if ($montantReel > 0 && !$isBalanced) {
                $alerts[] = sprintf(
                    'La dépense « %s » présente %s FCFA répartis pour %s FCFA de montant réel.',
                    $depense->getCategorie()->getLibelle(),
                    number_format($totalReparti, 0, ',', ' '),
                    number_format($montantReel, 0, ',', ' ')
                );
            }

            $depenseAllocations[$depense->getId()] = [
                'items' => $allocations,
                'totalReparti' => $totalReparti,
                'totalRepartiPrevu' => $totalRepartiPrevu,
                'montantReel' => $montantReel,
                'isBalanced' => $isBalanced,
            ];
        }

        foreach ($participantStats as &$stat) {
            $stat['totalPrevu'] = round($stat['totalPrevu'], 2);
            $stat['totalReel'] = round($stat['totalReel'], 2);
            $stat['ecart'] = round($stat['totalReel'] - $stat['totalPrevu'], 2);
        }
        unset($stat);

        usort($participantStats, static function (array $a, array $b): int {
            return strcmp(trim(($a['nom'] ?? '') . ' ' . ($a['prenom'] ?? '')), trim(($b['nom'] ?? '') . ' ' . ($b['prenom'] ?? '')));
        });

        return [$participantStats, $depenseAllocations, $alerts];
    }

    /**
     * Prépare les données nécessaires aux sélecteurs (participants et index d'allocations existantes).
     *
     * @return array{0: array<int, array{id:int,label:string}>, 1: array<int, int[]>}
     */
    private function buildMissionParticipantSelectData(MissionSession $missionSession): array
    {
        $participantOptionsData = [];
        foreach ($missionSession->getUserMissions() as $userMission) {
            $user = $userMission->getUser();
            $labelParts = [];
            if ($user?->getNom()) {
                $labelParts[] = $user->getNom();
            }
            if ($user?->getPrenom()) {
                $labelParts[] = $user->getPrenom();
            }
            $label = trim(implode(' ', $labelParts));
            if ($user?->getEmail()) {
                $label .= sprintf(' (%s)', $user->getEmail());
            }
            $participantOptionsData[] = [
                'id' => $userMission->getId(),
                'label' => $label !== '' ? $label : sprintf('Participant #%d', $userMission->getId()),
            ];
        }

        $allocationIndexData = [];
        foreach ($missionSession->getDepenseMissions() as $depense) {
            $categorie = $depense->getCategorie();
            if (!$categorie) {
                continue;
            }
            foreach ($depense->getParticipantAllocations() as $allocation) {
                $userMission = $allocation->getUserMission();
                if (!$userMission) {
                    continue;
                }
                $categorieId = $categorie->getId();
                if (!$categorieId) {
                    continue;
                }
                $allocationIndexData[$categorieId] ??= [];
                if (!in_array($userMission->getId(), $allocationIndexData[$categorieId], true)) {
                    $allocationIndexData[$categorieId][] = $userMission->getId();
                }
            }
        }

        return [$participantOptionsData, $allocationIndexData];
    }

    #[Route('/allocation/{id}', name: 'app_mission_allocation_show', methods: ['GET'])]
    public function getMissionAllocation(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $allocation = $entityManager->getRepository(DepenseMissionParticipant::class)->find($id);
        if (!$allocation) {
            return $this->json([
                'success' => false,
                'message' => 'Allocation introuvable',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'montantPrevu' => $allocation->getMontantPrevu(),
        ]);
    }

    #[Route('/allocation/{id}', name: 'app_mission_allocation_update', methods: ['PUT'])]
    public function updateMissionAllocation(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $allocation = $entityManager->getRepository(DepenseMissionParticipant::class)->find($id);
        if (!$allocation) {
            return $this->json([
                'success' => false,
                'message' => 'Allocation introuvable',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);
        $montant = isset($data['montant']) ? (float) $data['montant'] : null;
        if ($montant === null || $montant <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Montant invalide',
            ], 400);
        }

        $diff = $montant - (float) ($allocation->getMontantPrevu() ?? 0);
        $depenseMission = $allocation->getDepenseMission();
        $depenseMission->setMontantPrevu(number_format((float) $depenseMission->getMontantPrevu() + $diff, 2, '.', ''));
        $allocation->setMontantPrevu(number_format($montant, 2, '.', ''));

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Montant mis à jour avec succès',
        ]);
    }

    #[Route('/allocation/{id}', name: 'app_mission_allocation_delete', methods: ['DELETE'])]
    public function deleteMissionAllocation(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $allocation = $entityManager->getRepository(DepenseMissionParticipant::class)->find($id);
        if (!$allocation) {
            return $this->json([
                'success' => false,
                'message' => 'Allocation introuvable',
            ], 404);
        }

        $depenseMission = $allocation->getDepenseMission();
        $depenseMission->setMontantPrevu(number_format((float) $depenseMission->getMontantPrevu() - (float) $allocation->getMontantPrevu(), 2, '.', ''));
        $entityManager->remove($allocation);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ligne supprimée avec succès',
        ]);
    }
}
