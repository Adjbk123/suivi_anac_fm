<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\User;
use App\Entity\UserFormation;
use App\Repository\FormationRepository;
use App\Service\RoleService;
use App\Service\ExcelExportService;
use App\Service\PerformanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ServiceRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\UserRepository;
use App\Service\PdfService;

#[Route('/formation')]
class FormationController extends AbstractController
{
    #[Route('/', name: 'app_formation_index', methods: ['GET'])]
    public function index(PerformanceService $performanceService): Response
    {
        $currentYear = date('Y');
        $performanceData = $performanceService->getFormationPerformance($currentYear);
        
        return $this->render('formation/index.html.twig', [
            'performanceData' => $performanceData,
            'currentYear' => $currentYear
        ]);
    }

    #[Route('/export-excel', name: 'app_formation_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, FormationRepository $formationRepository, ExcelExportService $excelExportService): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = [
            'service' => $request->query->get('service'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search')
        ];

        // Récupérer les formations avec les filtres
        $formations = $formationRepository->findWithFilters($filters);

        return $excelExportService->exportFormationsToExcel($formations, $filters);
    }

    #[Route('/export-budget-report', name: 'app_formation_export_budget_report', methods: ['GET'])]
    public function exportBudgetReport(Request $request, FormationRepository $formationRepository, ExcelExportService $excelExportService): Response
    {
        $filters = [
            'service' => $request->query->get('service'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search')
        ];
        $formations = $formationRepository->findWithFilters($filters);
        return $excelExportService->exportFormationBudgetReport($formations, $filters);
    }

    #[Route('/create', name: 'app_formation_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function create(): Response
    {
        return $this->render('formation/create.html.twig');
    }

    #[Route('/list', name: 'app_formation_list', methods: ['GET'])]
    public function list(Request $request, FormationRepository $formationRepository): JsonResponse
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $serviceId = $request->query->get('service');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $formations = $formationRepository->findAllWithFilters($statutId, $serviceId, $periode, $participant);
        $data = [];
        
        foreach ($formations as $formation) {
            $data[] = [
                'id' => $formation->getId(),
                'titre' => $formation->getTitre(),
                'service' => $formation->getService() ? $formation->getService()->getLibelle() : '-',
                'fonds' => $formation->getFonds() ? $formation->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $formation->getDatePrevueDebut() ? $formation->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $formation->getDatePrevueFin() ? $formation->getDatePrevueFin()->format('d/m/Y') : '-',
                'dureePrevue' => $formation->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format($formation->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'statut' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/filters/data', name: 'app_formation_filters_data', methods: ['GET'])]
    public function getFiltersData(
        ServiceRepository $serviceRepository,
        StatutActiviteRepository $statutRepository,
        UserRepository $userRepository
    ): JsonResponse
    {
        $services = $serviceRepository->findAll();
        $statuts = $statutRepository->findAll();
        $users = $userRepository->findAll();
        
        $servicesData = [];
        foreach ($services as $service) {
            $servicesData[] = [
                'id' => $service->getId(),
                'libelle' => $service->getLibelle()
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
            'services' => $servicesData,
            'statuts' => $statutsData,
            'users' => $usersData
        ]);
    }

    #[Route('/executed', name: 'app_formation_executed', methods: ['GET'])]
    public function executed(): Response
    {
        return $this->render('formation/executed.html.twig');
    }

    #[Route('/executed/list', name: 'app_formation_executed_list', methods: ['GET'])]
    public function executedList(FormationRepository $formationRepository): JsonResponse
    {
        $formations = $formationRepository->findExecutedFormations();
        $data = [];
        
        foreach ($formations as $formation) {
            // Calculer les dépenses réelles
            $depensesReelles = 0;
            foreach ($formation->getDepenseFormations() as $depense) {
                $depensesReelles += $depense->getMontantReel() ?? 0;
            }
            
            // Compter les participants
            $participants = count($formation->getUserFormations());
            
            $data[] = [
                'id' => $formation->getId(),
                'titre' => $formation->getTitre(),
                'service' => $formation->getService() ? $formation->getService()->getLibelle() : '-',
                'fonds' => $formation->getFonds() ? $formation->getFonds()->getLibelle() : '-',
                'dateReelleDebut' => $formation->getDateReelleDebut() ? $formation->getDateReelleDebut()->format('d/m/Y') : '-',
                'dateReelleFin' => $formation->getDateReelleFin() ? $formation->getDateReelleFin()->format('d/m/Y') : '-',
                'lieuReel' => $formation->getLieuReel() ?: '-',
                'dureeReelle' => $formation->getDureeReelle() ? $formation->getDureeReelle() . ' jours' : '-',
                'budgetPrevu' => number_format($formation->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'depensesReelles' => number_format($depensesReelles, 0, ',', ' ') . ' FCFA',
                'participants' => $participants . ' participant(s)',
                'statut' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/planned', name: 'app_formation_planned', methods: ['GET'])]
    public function planned(): Response
    {
        return $this->render('formation/planned.html.twig');
    }

    #[Route('/planned/list', name: 'app_formation_planned_list', methods: ['GET'])]
    public function plannedList(FormationRepository $formationRepository): JsonResponse
    {
        $formations = $formationRepository->findPlannedFormations();
        $data = [];
        
        foreach ($formations as $formation) {
            // Compter les participants prévus
            $participantsPrevu = count($formation->getUserFormations());
            
            $data[] = [
                'id' => $formation->getId(),
                'titre' => $formation->getTitre(),
                'service' => $formation->getService() ? $formation->getService()->getLibelle() : '-',
                'fonds' => $formation->getFonds() ? $formation->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $formation->getDatePrevueDebut() ? $formation->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $formation->getDatePrevueFin() ? $formation->getDatePrevueFin()->format('d/m/Y') : '-',
                'lieuPrevu' => $formation->getLieuPrevu(),
                'dureePrevue' => $formation->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format($formation->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'participantsPrevu' => $participantsPrevu . ' participant(s)',
                'statut' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $formation->getStatutActivite() ? $formation->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_formation_new', methods: ['POST'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer les données JSON
        $data = json_decode($request->get('data'), true);
        
        $formation = new Formation();
        $formation->setTitre($data['titre']);
        $formation->setDescription($data['description'] ?? null);
        $formation->setLieuPrevu($data['lieuPrevu']);
        $formation->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        $formation->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        $formation->setDureePrevue($data['dureePrevue']);
        $formation->setBudgetPrevu($data['budgetPrevu']);
        // Récupérer le statut par défaut (prévue non exécutée)
        $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_non_executee']);
        $formation->setStatutActivite($statutActivite);
        $formation->setNotes($data['notes'] ?? null);
        
        // Récupérer le service
        $service = $entityManager->getRepository(\App\Entity\Service::class)->find($data['serviceId']);
        $formation->setService($service);
        
        // Récupérer le type de fonds
        $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
        $formation->setFonds($fonds);
        
        $entityManager->persist($formation);
        $entityManager->flush();
        
        // Ajouter les participants
        if (isset($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $userId) {
                $user = $entityManager->getRepository(User::class)->find($userId);
                if ($user) {
                    $userFormation = new UserFormation();
                    $userFormation->setUser($user);
                    $userFormation->setFormation($formation);
                    // Récupérer le statut de participation par défaut (inscrit)
                    $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
                    $userFormation->setStatutParticipation($statutParticipation);
                    $entityManager->persist($userFormation);
                }
            }
        }
        
        // Ajouter les dépenses prévues
        $totalBudgetPrevu = 0;
        if (isset($data['depenses']) && is_array($data['depenses'])) {
            foreach ($data['depenses'] as $depense) {
                $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depense['categorieId']);
                if ($categorie) {
                    $depenseFormation = new \App\Entity\DepenseFormation();
                    $depenseFormation->setFormation($formation);
                    $depenseFormation->setCategorie($categorie);
                    $depenseFormation->setMontantPrevu($depense['montant']);
                    $entityManager->persist($depenseFormation);
                    $totalBudgetPrevu += (float)$depense['montant'];
                }
            }
        }
        
        // Mettre à jour le budget prévu de la formation avec le total des dépenses
        $formation->setBudgetPrevu($totalBudgetPrevu);
        
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
                
                // Créer l'entité DocumentFormation
                $documentFormation = new \App\Entity\DocumentFormation();
                $documentFormation->setFormation($formation);
                $documentFormation->setNom($nom);
                $documentFormation->setNomFichier($newFilename);
                $documentFormation->setType($fileType);
                $documentFormation->setTaille($fileSize);
                
                $entityManager->persist($documentFormation);
            }
            
            $index++;
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Formation créée avec succès',
            'formationId' => $formation->getId()
        ]);
    }

    #[Route('/{id}', name: 'app_formation_show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        return $this->render('formation/show.html.twig', [
            'formation' => $formation
        ]);
    }

    /**
     * Recalcule le budget prévu d'une formation en additionnant tous les montants prévus des dépenses
     */
    private function recalculateBudgetPrevu(Formation $formation): float
    {
        $totalPrevu = 0;
        foreach ($formation->getDepenseFormations() as $depense) {
            $totalPrevu += (float)$depense->getMontantPrevu();
        }
        return $totalPrevu;
    }

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function edit(Formation $formation): Response
    {
        return $this->render('formation/edit.html.twig', [
            'formation' => $formation
        ]);
    }

    #[Route('/{id}/update', name: 'app_formation_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function update(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $formation->setTitre($data['titre']);
        $formation->setDescription($data['description'] ?? null);
        $formation->setLieuPrevu($data['lieuPrevu']);
        $formation->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        $formation->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        $formation->setDureePrevue($data['dureePrevue']);
        $formation->setBudgetPrevu($data['budgetPrevu']);
        
        // Récupérer le service
        $service = $entityManager->getRepository(\App\Entity\Service::class)->find($data['serviceId']);
        $formation->setService($service);
        
        // Récupérer le type de fonds
        $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
        $formation->setFonds($fonds);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Formation modifiée avec succès'
        ]);
    }

    #[Route('/{id}', name: 'app_formation_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($formation);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Formation supprimée avec succès'
        ]);
    }

    #[Route('/{id}/add-document', name: 'app_formation_add_document', methods: ['POST'])]
    public function addDocument(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
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
            
            // Créer l'entité DocumentFormation
            $documentFormation = new \App\Entity\DocumentFormation();
            $documentFormation->setFormation($formation);
            $documentFormation->setNom($nom);
            $documentFormation->setNomFichier($newFilename);
            $documentFormation->setType($fileType);
            $documentFormation->setTaille($fileSize);
            
            $entityManager->persist($documentFormation);
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

    #[Route('/{id}/add-depense', name: 'app_formation_add_depense', methods: ['POST'])]
    public function addDepense(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
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
            
            // Créer l'entité DepenseFormation
            $depenseFormation = new \App\Entity\DepenseFormation();
            $depenseFormation->setFormation($formation);
            $depenseFormation->setCategorie($categorie);
            $depenseFormation->setMontantPrevu($data['montant']);
            
            $entityManager->persist($depenseFormation);
            $entityManager->flush();
            
            // Recalculer le budget prévu de la formation
            $totalPrevu = $this->recalculateBudgetPrevu($formation);
            $formation->setBudgetPrevu($totalPrevu);
            $entityManager->persist($formation);
            $entityManager->flush();
            
            // Recalculer les totaux après l'ajout
            $totalReel = 0;
            foreach ($formation->getDepenseFormations() as $depense) {
                $totalReel += $depense->getMontantReel() ? (float)$depense->getMontantReel() : 0;
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Dépense ajoutée avec succès',
                'totaux' => [
                    'totalPrevu' => $totalPrevu,
                    'totalReel' => $totalReel,
                    'totalEcart' => $totalReel - $totalPrevu
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la dépense: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/add-participant', name: 'app_formation_add_participant', methods: ['POST'])]
    public function addParticipant(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
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
            $user = $entityManager->getRepository(User::class)->find($data['userId']);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }
            
            // Vérifier que l'utilisateur appartient au même service que la formation
            if ($user->getService()->getId() !== $formation->getService()->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'L\'utilisateur doit appartenir au même service que la formation'
                ], 400);
            }
            
            // Vérifier que l'utilisateur n'est pas déjà participant
            $existingUserFormation = $entityManager->getRepository(\App\Entity\UserFormation::class)->findOneBy([
                'formation' => $formation,
                'user' => $user
            ]);
            
            if ($existingUserFormation) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà participant à cette formation'
                ], 400);
            }
            
            // Récupérer le statut de participation par défaut (inscrit)
            $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
            
            // Créer l'entité UserFormation
            $userFormation = new \App\Entity\UserFormation();
            $userFormation->setFormation($formation);
            $userFormation->setUser($user);
            $userFormation->setStatutParticipation($statutParticipation);
            
            $entityManager->persist($userFormation);
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

    #[Route('/{id}/realisation', name: 'app_formation_realisation', methods: ['GET'])]
    public function realisation(Formation $formation): Response
    {
        return $this->render('formation/realisation.html.twig', [
            'formation' => $formation
        ]);
    }

    #[Route('/{id}/realisation/participants', name: 'app_formation_realisation_participants', methods: ['GET'])]
    public function getParticipantsForRealisation(Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = [
            'prevus' => [],
            'disponibles' => []
        ];
        
        // Participants prévus (déjà inscrits)
        foreach ($formation->getUserFormations() as $userFormation) {
            $data['prevus'][] = [
                'id' => $userFormation->getId(),
                'userId' => $userFormation->getUser()->getId(),
                'nom' => $userFormation->getUser()->getNom(),
                'prenom' => $userFormation->getUser()->getPrenom(),
                'matricule' => $userFormation->getUser()->getMatricule(),
                'statut_participation_id' => $userFormation->getStatutParticipation() ? $userFormation->getStatutParticipation()->getId() : null,
                'statut_libelle' => $userFormation->getStatutParticipation() ? $userFormation->getStatutParticipation()->getLibelle() : 'Non défini',
                'statut_couleur' => $userFormation->getStatutParticipation() ? $userFormation->getStatutParticipation()->getCouleur() : '#6c757d'
            ];
        }
        
        // Utilisateurs du service non encore participants
        $service = $formation->getService();
        if ($service) {
            $users = $entityManager->getRepository(User::class)->findBy(['service' => $service]);
            $participantsIds = array_map(function($uf) { return $uf->getUser()->getId(); }, $formation->getUserFormations()->toArray());
            
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

    #[Route('/{id}/realisation/depenses', name: 'app_formation_realisation_depenses', methods: ['GET'])]
    public function getRealisationDepenses(Formation $formation): JsonResponse
    {
        $depenses = [];
        $totalPrevu = 0;
        $totalReel = 0;
        
        foreach ($formation->getDepenseFormations() as $depense) {
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

    #[Route('/{id}/realisation/complete', name: 'app_formation_realisation_complete', methods: ['POST'])]
    public function completeRealisation(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            // 1. Mettre à jour les informations de la formation
            if (isset($data['dateReelleDebut'])) {
                $formation->setDateReelleDebut(new \DateTime($data['dateReelleDebut']));
            }
            if (isset($data['dateReelleFin'])) {
                $formation->setDateReelleFin(new \DateTime($data['dateReelleFin']));
            }
            if (isset($data['lieuReel'])) {
                $formation->setLieuReel($data['lieuReel']);
            }
            
            // Calculer la durée réelle
            if (isset($data['dateReelleDebut']) && isset($data['dateReelleFin'])) {
                $dateDebut = new \DateTime($data['dateReelleDebut']);
                $dateFin = new \DateTime($data['dateReelleFin']);
                $dureeReelle = $dateDebut->diff($dateFin)->days + 1; // +1 pour inclure le jour de fin
                $formation->setDureeReelle($dureeReelle);
            }
            
            // 2. Mettre à jour le statut de l'activité selon la nature
            $natureCode = $data['natureFormation'] ?? 'prevue_executee';
            $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => $natureCode]);
            if (!$statutActivite) {
                // Fallback vers le statut par défaut si le code n'existe pas
                $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_executee']);
            }
            $formation->setStatutActivite($statutActivite);
            
            // 3. Mettre à jour les statuts des participants
            if (isset($data['participants'])) {
                foreach ($data['participants'] as $participantData) {
                    $userFormation = $entityManager->getRepository(\App\Entity\UserFormation::class)->find($participantData['userFormationId']);
                    if ($userFormation) {
                        $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->find($participantData['statut_participation_id']);
                        $userFormation->setStatutParticipation($statutParticipation);
                    }
                }
            }
            
            // 4. Ajouter les nouveaux participants
            if (isset($data['nouveauxParticipants'])) {
                $statutNonPrevusParticipe = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'non_prevus_participe']);
                
                foreach ($data['nouveauxParticipants'] as $userId) {
                    $user = $entityManager->getRepository(User::class)->find($userId);
                    if ($user) {
                        $userFormation = new \App\Entity\UserFormation();
                        $userFormation->setFormation($formation);
                        $userFormation->setUser($user);
                        $userFormation->setStatutParticipation($statutNonPrevusParticipe);
                        $entityManager->persist($userFormation);
                    }
                }
            }
            
            // 5. Ajouter les dépenses réelles
            if (isset($data['depensesReelles'])) {
                foreach ($data['depensesReelles'] as $depenseData) {
                    $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depenseData['categorieId']);
                    if ($categorie) {
                        $depenseFormation = $entityManager->getRepository(\App\Entity\DepenseFormation::class)->findOneBy([
                            'formation' => $formation,
                            'categorie' => $categorie
                        ]);
                        
                        if ($depenseFormation) {
                            $depenseFormation->setMontantReel($depenseData['montant']);
                        } else {
                            // Créer une nouvelle dépense si elle n'existe pas
                            $depenseFormation = new \App\Entity\DepenseFormation();
                            $depenseFormation->setFormation($formation);
                            $depenseFormation->setCategorie($categorie);
                            $depenseFormation->setMontantReel($depenseData['montant']);
                            $entityManager->persist($depenseFormation);
                        }
                    }
                }
            }
            
            // Calculer le budget réel total
            $budgetReel = 0;
            foreach ($formation->getDepenseFormations() as $depense) {
                if ($depense->getMontantReel()) {
                    $budgetReel += (float)$depense->getMontantReel();
                }
            }
            $formation->setBudgetReel($budgetReel);
            
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Formation marquée comme réalisée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la réalisation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/export/pdf', name: 'app_formation_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, FormationRepository $formationRepository, PdfService $pdfService): Response
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $serviceId = $request->query->get('service');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $formations = $formationRepository->findAllWithFilters($statutId, $serviceId, $periode, $participant);
        
        // Préparer les données pour le PDF avec toutes les colonnes
        $headers = [
            'ID', 'Titre', 'Description', 'Service', 'Fonds', 'Durée prévue', 'Durée réelle', 
            'Lieu prévu', 'Lieu réel', 'Budget prévu', 'Budget réel', 'Date début prévue', 
            'Date fin prévue', 'Date début réelle', 'Date fin réelle', 'Statut', 'Notes'
        ];
        $data = [];
        
        foreach ($formations as $formation) {
            $data[] = [
                $formation->getId(),
                $formation->getTitre(),
                $formation->getDescription() ?: '-',
                $formation->getService() ? $formation->getService()->getLibelle() : '-',
                $formation->getFonds() ? $formation->getFonds()->getLibelle() : '-',
                $formation->getDureePrevue() . ' jours',
                $formation->getDureeReelle() ? $formation->getDureeReelle() . ' jours' : '-',
                $formation->getLieuPrevu() ?: '-',
                $formation->getLieuReel() ?: '-',
                number_format((float)$formation->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                $formation->getBudgetReel() ? number_format((float)$formation->getBudgetReel(), 0, ',', ' ') . ' FCFA' : '-',
                $formation->getDatePrevueDebut() ? $formation->getDatePrevueDebut()->format('d/m/Y') : '-',
                $formation->getDatePrevueFin() ? $formation->getDatePrevueFin()->format('d/m/Y') : '-',
                $formation->getDateReelleDebut() ? $formation->getDateReelleDebut()->format('d/m/Y') : '-',
                $formation->getDateReelleFin() ? $formation->getDateReelleFin()->format('d/m/Y') : '-',
                $formation->getStatutActivite() ? $formation->getStatutActivite()->getLibelle() : '-',
                $formation->getNotes() ?: '-'
            ];
        }
        
        // Préparer les filtres appliqués
        $filters = [];
        if ($statutId) {
            $statut = $formationRepository->getEntityManager()->getRepository('App\Entity\StatutActivite')->find($statutId);
            if ($statut) $filters[] = 'Statut: ' . $statut->getLibelle();
        }
        if ($serviceId) {
            $service = $formationRepository->getEntityManager()->getRepository('App\Entity\Service')->find($serviceId);
            if ($service) $filters[] = 'Service: ' . $service->getLibelle();
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
            $user = $formationRepository->getEntityManager()->getRepository('App\Entity\User')->find($participant);
            if ($user) $filters[] = 'Participant: ' . $user->getNom() . ' ' . $user->getPrenom();
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateLandscapeTablePdf(
            'Liste des Formations',
            $headers,
            $data,
            $filters,
            'formations_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'formations_anac_benin.pdf'
        ));
        
        return $response;
    }


}
