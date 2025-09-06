<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Entity\User;
use App\Entity\UserMission;
use App\Repository\MissionRepository;
use App\Repository\DirectionRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\UserRepository;
use App\Service\PdfService;
use App\Service\ExcelExportService;
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
    public function index(): Response
    {
        return $this->render('mission/index.html.twig');
    }

    #[Route('/export-excel', name: 'app_mission_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, MissionRepository $missionRepository, ExcelExportService $excelExportService): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = [
            'direction' => $request->query->get('direction'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search')
        ];

        // Récupérer les missions avec les filtres
        $missions = $missionRepository->findWithFilters($filters);

        return $excelExportService->exportMissionsToExcel($missions, $filters);
    }

    #[Route('/create', name: 'app_mission_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function create(): Response
    {
        return $this->render('mission/create.html.twig');
    }

    #[Route('/list', name: 'app_mission_list', methods: ['GET'])]
    public function list(Request $request, MissionRepository $missionRepository): JsonResponse
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $missions = $missionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);
        $data = [];
        
        foreach ($missions as $mission) {
            $data[] = [
                'id' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'direction' => $mission->getDirection() ? $mission->getDirection()->getLibelle() : '-',
                'fonds' => $mission->getFonds() ? $mission->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $mission->getDatePrevueFin() ? $mission->getDatePrevueFin()->format('d/m/Y') : '-',
                'dureePrevue' => $mission->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format($mission->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'statut' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/filters/data', name: 'app_mission_filters_data', methods: ['GET'])]
    public function getFiltersData(
        DirectionRepository $directionRepository,
        StatutActiviteRepository $statutRepository,
        UserRepository $userRepository
    ): JsonResponse
    {
        $directions = $directionRepository->findAll();
        $statuts = $statutRepository->findAll();
        $users = $userRepository->findAll();
        
        $directionsData = [];
        foreach ($directions as $direction) {
            $directionsData[] = [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle()
            ];
        }
        
        $statutsData = [];
        foreach ($statuts as $statut) {
            $statutsData[] = [
                'id' => $statut->getId(),
                'libelle' => $statut->getLibelle(),
                'couleur' => $statut->getCouleur()
            ];
        }
        
        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail()
            ];
        }
        
        return $this->json([
            'directions' => $directionsData,
            'statuts' => $statutsData,
            'users' => $usersData
        ]);
    }



    #[Route('/executed', name: 'app_mission_executed', methods: ['GET'])]
    public function executed(): Response
    {
        return $this->render('mission/executed.html.twig');
    }

    #[Route('/executed/list', name: 'app_mission_executed_list', methods: ['GET'])]
    public function executedList(MissionRepository $missionRepository): JsonResponse
    {
        $missions = $missionRepository->findExecutedMissions();
        $data = [];
        
        foreach ($missions as $mission) {
            // Calculer les dépenses réelles
            $depensesReelles = 0;
            foreach ($mission->getDepenseMissions() as $depense) {
                $depensesReelles += $depense->getMontantReel() ?? 0;
            }
            
            $data[] = [
                'id' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'direction' => $mission->getDirection() ? $mission->getDirection()->getLibelle() : '-',
                'fonds' => $mission->getFonds() ? $mission->getFonds()->getLibelle() : '-',
                'dateReelleDebut' => $mission->getDateReelleDebut() ? $mission->getDateReelleDebut()->format('d/m/Y') : '-',
                'dateReelleFin' => $mission->getDateReelleFin() ? $mission->getDateReelleFin()->format('d/m/Y') : '-',
                'lieuReel' => $mission->getLieuReel() ?: '-',
                'dureeReelle' => $mission->getDureePrevue() ? $mission->getDureePrevue() . ' jours' : '-',
                'budgetPrevu' => number_format($mission->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'depensesReelles' => number_format($depensesReelles, 0, ',', ' ') . ' FCFA',
                'statut' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getCouleur() : 'secondary'
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
    public function plannedList(MissionRepository $missionRepository): JsonResponse
    {
        $missions = $missionRepository->findPlannedMissions();
        $data = [];
        
        foreach ($missions as $mission) {
            // Compter les participants prévus
            $participantsPrevu = count($mission->getUserMissions());
            
            $data[] = [
                'id' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'direction' => $mission->getDirection() ? $mission->getDirection()->getLibelle() : '-',
                'fonds' => $mission->getFonds() ? $mission->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $mission->getDatePrevueFin() ? $mission->getDatePrevueFin()->format('d/m/Y') : '-',
                'lieuPrevu' => $mission->getLieuPrevu(),
                'dureePrevue' => $mission->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format($mission->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'participantsPrevu' => $participantsPrevu . ' participant(s)',
                'statut' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $mission->getStatutActivite() ? $mission->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_mission_new', methods: ['POST'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer les données JSON
        $data = json_decode($request->get('data'), true);
        
        $mission = new Mission();
        $mission->setTitre($data['titre']);
        $mission->setDescription($data['description'] ?? null);
        $mission->setLieuPrevu($data['lieuPrevu']);
        $mission->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        $mission->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        $mission->setDureePrevue($data['dureePrevue']);
        $mission->setBudgetPrevu($data['budgetPrevu']);
        // Récupérer le statut par défaut (prévue non exécutée)
        $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_non_executee']);
        $mission->setStatutActivite($statutActivite);
        $mission->setNotes($data['notes'] ?? null);
        
        // Récupérer la direction
        $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
        $mission->setDirection($direction);
        
        // Récupérer le type de fonds
        $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
        $mission->setFonds($fonds);
        
        $entityManager->persist($mission);
        $entityManager->flush();
        
        // Ajouter les participants
        if (isset($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $userId) {
                $user = $entityManager->getRepository(User::class)->find($userId);
                if ($user) {
                    $userMission = new UserMission();
                    $userMission->setUser($user);
                    $userMission->setMission($mission);
                    // Récupérer le statut de participation par défaut (inscrit)
                    $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
                    $userMission->setStatutParticipation($statutParticipation);
                    $entityManager->persist($userMission);
                }
            }
        }
        
        // Ajouter les dépenses prévues
        $totalBudgetPrevu = 0;
        if (isset($data['depenses']) && is_array($data['depenses'])) {
            foreach ($data['depenses'] as $depense) {
                $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depense['categorieId']);
                if ($categorie) {
                    $depenseMission = new \App\Entity\DepenseMission();
                    $depenseMission->setMission($mission);
                    $depenseMission->setCategorie($categorie);
                    $depenseMission->setMontantPrevu($depense['montant']);
                    $entityManager->persist($depenseMission);
                    $totalBudgetPrevu += (float)$depense['montant'];
                }
            }
        }
        
        // Mettre à jour le budget prévu de la mission avec le total des dépenses
        $mission->setBudgetPrevu($totalBudgetPrevu);
        
        // Ajouter les documents
        $index = 0;
        while ($request->files->has("document_{$index}")) {
            $file = $request->files->get("document_{$index}");
            $nom = $request->get("document_nom_{$index}");
            
            if ($file && $nom) {
                // Récupérer les informations du fichier avant de le déplacer
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                $fileSize = $file->getSize();
                $fileType = $file->getClientMimeType();
                
                // Déplacer le fichier
                $file->move(
                    $this->getParameter('documents_directory'),
                    $newFilename
                );
                
                // Créer l'entité DocumentMission
                $documentMission = new \App\Entity\DocumentMission();
                $documentMission->setMission($mission);
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
            'missionId' => $mission->getId()
        ]);
    }

    #[Route('/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(Mission $mission): Response
    {
        return $this->render('mission/show.html.twig', [
            'mission' => $mission
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mission_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function edit(Mission $mission): Response
    {
        return $this->render('mission/edit.html.twig', [
            'mission' => $mission
        ]);
    }

    #[Route('/{id}/update', name: 'app_mission_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function update(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $mission->setTitre($data['titre']);
        $mission->setDescription($data['description'] ?? null);
        $mission->setLieuPrevu($data['lieuPrevu']);
        $mission->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        $mission->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        $mission->setDureePrevue($data['dureePrevue']);
        $mission->setBudgetPrevu($data['budgetPrevu']);
        
        // Récupérer la direction
        $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
        $mission->setDirection($direction);
        
        // Récupérer le type de fonds
        $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
        $mission->setFonds($fonds);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Mission modifiée avec succès'
        ]);
    }

    #[Route('/{id}', name: 'app_mission_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($mission);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Mission supprimée avec succès'
        ]);
    }

    #[Route('/{id}/add-document', name: 'app_mission_add_document', methods: ['POST'])]
    public function addDocument(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $file = $request->files->get('file');
        $nom = $request->get('nom');
        
        if (!$file || !$nom) {
            return $this->json([
                'success' => false,
                'message' => 'Fichier et nom requis'
            ], 400);
        }
        
        try {
            // Générer un nom de fichier unique
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            
            // Récupérer les informations du fichier avant de le déplacer
            $fileSize = $file->getSize();
            $fileType = $file->getClientMimeType();
            
            // Déplacer le fichier
            $file->move(
                $this->getParameter('documents_directory'),
                $newFilename
            );
            
            // Créer l'entité DocumentMission
            $documentMission = new \App\Entity\DocumentMission();
            $documentMission->setMission($mission);
            $documentMission->setNom($nom);
            $documentMission->setNomFichier($newFilename);
            $documentMission->setType($fileType);
            $documentMission->setTaille($fileSize);
            
            $entityManager->persist($documentMission);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Document ajouté avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du document: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/add-depense', name: 'app_mission_add_depense', methods: ['POST'])]
    public function addDepense(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['categorieId']) || !isset($data['montant'])) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie et montant requis'
            ], 400);
        }
        
        try {
            // Récupérer la catégorie
            $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($data['categorieId']);
            if (!$categorie) {
                return $this->json([
                    'success' => false,
                    'message' => 'Catégorie de dépense introuvable'
                ], 404);
            }
            
            // Créer l'entité DepenseMission
            $depenseMission = new \App\Entity\DepenseMission();
            $depenseMission->setMission($mission);
            $depenseMission->setCategorie($categorie);
            $depenseMission->setMontantPrevu($data['montant']);
            
            $entityManager->persist($depenseMission);
            $entityManager->flush();
            
            // Recalculer le budget prévu de la mission
            $totalPrevu = $this->recalculateBudgetPrevu($mission);
            $mission->setBudgetPrevu($totalPrevu);
            $entityManager->persist($mission);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Dépense ajoutée avec succès',
                'totaux' => [
                    'totalPrevu' => $totalPrevu
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la dépense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcule le budget prévu d'une mission en additionnant tous les montants prévus des dépenses
     */
    private function recalculateBudgetPrevu(Mission $mission): float
    {
        $totalPrevu = 0;
        foreach ($mission->getDepenseMissions() as $depense) {
            $totalPrevu += (float)$depense->getMontantPrevu();
        }
        return $totalPrevu;
    }

    #[Route('/{id}/add-participant', name: 'app_mission_add_participant', methods: ['POST'])]
    public function addParticipant(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['userId'])) {
            return $this->json([
                'success' => false,
                'message' => 'ID utilisateur requis'
            ], 400);
        }
        
        try {
            // Récupérer l'utilisateur
            $user = $entityManager->getRepository(\App\Entity\User::class)->find($data['userId']);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }
            
            // Vérifier que l'utilisateur appartient à la même direction que la mission
            if ($user->getService()->getDirection()->getId() !== $mission->getDirection()->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'L\'utilisateur doit appartenir à la même direction que la mission'
                ], 400);
            }
            
            // Vérifier que l'utilisateur n'est pas déjà participant
            $existingUserMission = $entityManager->getRepository(\App\Entity\UserMission::class)->findOneBy([
                'mission' => $mission,
                'user' => $user
            ]);
            
            if ($existingUserMission) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà participant à cette mission'
                ], 400);
            }
            
            // Récupérer le statut de participation par défaut (inscrit)
            $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
            
            // Créer l'entité UserMission
            $userMission = new \App\Entity\UserMission();
            $userMission->setMission($mission);
            $userMission->setUser($user);
            $userMission->setStatutParticipation($statutParticipation);
            
            $entityManager->persist($userMission);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Participant ajouté avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du participant: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/realisation', name: 'app_mission_realisation', methods: ['GET'])]
    public function realisation(Mission $mission): Response
    {
        return $this->render('mission/realisation.html.twig', [
            'mission' => $mission
        ]);
    }

    #[Route('/{id}/realisation/participants', name: 'app_mission_realisation_participants', methods: ['GET'])]
    public function getParticipantsForRealisation(Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = [
            'prevus' => [],
            'disponibles' => []
        ];
        
        // Participants prévus (déjà inscrits)
        foreach ($mission->getUserMissions() as $userMission) {
            $data['prevus'][] = [
                'id' => $userMission->getId(),
                'userId' => $userMission->getUser()->getId(),
                'nom' => $userMission->getUser()->getNom(),
                'prenom' => $userMission->getUser()->getPrenom(),
                'matricule' => $userMission->getUser()->getMatricule(),
                'statut_participation_id' => $userMission->getStatutParticipation() ? $userMission->getStatutParticipation()->getId() : null,
                'statut_libelle' => $userMission->getStatutParticipation() ? $userMission->getStatutParticipation()->getLibelle() : 'Non défini',
                'statut_couleur' => $userMission->getStatutParticipation() ? $userMission->getStatutParticipation()->getCouleur() : '#6c757d'
            ];
        }
        
        // Utilisateurs de la direction non encore participants
        $direction = $mission->getDirection();
        if ($direction) {
            // Récupérer tous les services de cette direction
            $services = $direction->getServices();
            $users = [];
            foreach ($services as $service) {
                $serviceUsers = $entityManager->getRepository(User::class)->findBy(['service' => $service]);
                $users = array_merge($users, $serviceUsers);
            }
            
            $participantsIds = array_map(function($um) { return $um->getUser()->getId(); }, $mission->getUserMissions()->toArray());
            
            $data['disponibles'] = [];
            foreach ($users as $user) {
                if (!in_array($user->getId(), $participantsIds)) {
                    $data['disponibles'][] = [
                        'id' => $user->getId(),
                        'nom' => $user->getNom(),
                        'prenom' => $user->getPrenom(),
                        'matricule' => $user->getMatricule()
                    ];
                }
            }
        }
        
        return $this->json($data);
    }

    #[Route('/{id}/realisation/depenses', name: 'app_mission_realisation_depenses', methods: ['GET'])]
    public function getRealisationDepenses(Mission $mission): JsonResponse
    {
        $depenses = [];
        $totalPrevu = 0;
        $totalReel = 0;
        
        foreach ($mission->getDepenseMissions() as $depense) {
            $montantPrevu = (float)$depense->getMontantPrevu();
            $montantReel = $depense->getMontantReel() ? (float)$depense->getMontantReel() : 0;
            $ecart = $montantReel - $montantPrevu;
            
            $totalPrevu += $montantPrevu;
            $totalReel += $montantReel;
            
            $depenses[] = [
                'id' => $depense->getId(),
                'categorie' => $depense->getCategorie()->getLibelle(),
                'categorieId' => $depense->getCategorie()->getId(),
                'montantPrevu' => $montantPrevu,
                'montantReel' => $montantReel,
                'ecart' => $ecart
            ];
        }
        
        return $this->json([
            'depenses' => $depenses,
            'totaux' => [
                'totalPrevu' => $totalPrevu,
                'totalReel' => $totalReel,
                'totalEcart' => $totalReel - $totalPrevu
            ]
        ]);
    }

    #[Route('/{id}/realisation/complete', name: 'app_mission_realisation_complete', methods: ['POST'])]
    public function completeRealisation(Request $request, Mission $mission, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Log pour débogage
        error_log('Mission realisation data received: ' . json_encode($data));
        
        try {
            // 1. Mettre à jour les informations de la mission
            if (isset($data['dateReelleDebut'])) {
                $mission->setDateReelleDebut(new \DateTime($data['dateReelleDebut']));
            }
            if (isset($data['dateReelleFin'])) {
                $mission->setDateReelleFin(new \DateTime($data['dateReelleFin']));
            }
            if (isset($data['lieuReel'])) {
                $mission->setLieuReel($data['lieuReel']);
            }
            
            // Calculer la durée réelle
            if (isset($data['dateReelleDebut']) && isset($data['dateReelleFin'])) {
                $dateDebut = new \DateTime($data['dateReelleDebut']);
                $dateFin = new \DateTime($data['dateReelleFin']);
                $dureeReelle = $dateDebut->diff($dateFin)->days + 1; // +1 pour inclure le jour de fin
                $mission->setDureeReelle($dureeReelle);
            }
            
            // 2. Mettre à jour le statut de l'activité
            $statutExecutee = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_executee']);
            $mission->setStatutActivite($statutExecutee);
            
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
                        $userMission->setMission($mission);
                        $userMission->setUser($user);
                        $userMission->setStatutParticipation($statutNonPrevusParticipe);
                        $entityManager->persist($userMission);
                    }
                }
            }
            
            // 5. Ajouter les dépenses réelles
            if (isset($data['depensesReelles'])) {
                foreach ($data['depensesReelles'] as $depenseData) {
                    $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depenseData['categorieId']);
                    if ($categorie) {
                        $depenseMission = $entityManager->getRepository(\App\Entity\DepenseMission::class)->findOneBy([
                            'mission' => $mission,
                            'categorie' => $categorie
                        ]);
                        
                        if ($depenseMission) {
                            $depenseMission->setMontantReel($depenseData['montant']);
                        } else {
                            // Créer une nouvelle dépense si elle n'existe pas
                            $depenseMission = new \App\Entity\DepenseMission();
                            $depenseMission->setMission($mission);
                            $depenseMission->setCategorie($categorie);
                            $depenseMission->setMontantReel($depenseData['montant']);
                            $entityManager->persist($depenseMission);
                        }
                    }
                }
            }
            
            // Calculer le budget réel total
            $budgetReel = 0;
            foreach ($mission->getDepenseMissions() as $depense) {
                if ($depense->getMontantReel()) {
                    $budgetReel += (float)$depense->getMontantReel();
                }
            }
            $mission->setBudgetReel($budgetReel);
            
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Mission marquée comme réalisée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la réalisation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/export/pdf', name: 'app_mission_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, MissionRepository $missionRepository, PdfService $pdfService): Response
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $missions = $missionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);
        
        // Préparer les données pour le PDF avec toutes les colonnes
        $headers = [
            'ID', 'Titre', 'Description', 'Direction', 'Fonds', 'Lieu prévu', 'Lieu réel',
            'Date début prévue', 'Date fin prévue', 'Date début réelle', 'Date fin réelle',
            'Durée prévue', 'Budget prévu', 'Statut', 'Notes'
        ];
        $data = [];
        
        foreach ($missions as $mission) {
            $data[] = [
                $mission->getId(),
                $mission->getTitre(),
                $mission->getDescription() ?: '-',
                $mission->getDirection() ? $mission->getDirection()->getLibelle() : '-',
                $mission->getFonds() ? $mission->getFonds()->getLibelle() : '-',
                $mission->getLieuPrevu() ?: '-',
                $mission->getLieuReel() ?: '-',
                $mission->getDatePrevueDebut() ? $mission->getDatePrevueDebut()->format('d/m/Y') : '-',
                $mission->getDatePrevueFin() ? $mission->getDatePrevueFin()->format('d/m/Y') : '-',
                $mission->getDateReelleDebut() ? $mission->getDateReelleDebut()->format('d/m/Y') : '-',
                $mission->getDateReelleFin() ? $mission->getDateReelleFin()->format('d/m/Y') : '-',
                $mission->getDureePrevue() . ' jours',
                number_format($mission->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                $mission->getStatutActivite() ? $mission->getStatutActivite()->getLibelle() : '-',
                $mission->getNotes() ?: '-'
            ];
        }
        
        // Préparer les filtres appliqués
        $filters = [];
        if ($statutId) {
            $statut = $missionRepository->getEntityManager()->getRepository('App\Entity\StatutActivite')->find($statutId);
            if ($statut) $filters[] = 'Statut: ' . $statut->getLibelle();
        }
        if ($directionId) {
            $direction = $missionRepository->getEntityManager()->getRepository('App\Entity\Direction')->find($directionId);
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
            $user = $missionRepository->getEntityManager()->getRepository('App\Entity\User')->find($participant);
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
                'libelle' => $direction->getLibelle()
            ];
        }
        
        return $this->json($data);
    }




}
