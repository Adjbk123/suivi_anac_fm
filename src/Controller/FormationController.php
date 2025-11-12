<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\FormationSession;
use App\Entity\User;
use App\Entity\UserFormation;
use App\Repository\FormationRepository;
use App\Repository\FormationSessionRepository;
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
use App\Repository\DirectionRepository;
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
    public function exportExcel(Request $request, FormationSessionRepository $formationSessionRepository, ExcelExportService $excelExportService): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = [
            'direction' => $request->query->get('direction'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search')
        ];

        // Récupérer les sessions de formation avec les filtres
        $formationSessions = $formationSessionRepository->findWithFilters($filters);

        return $excelExportService->exportFormationsToExcel($formationSessions, $filters);
    }

    #[Route('/export-budget-report', name: 'app_formation_export_budget_report', methods: ['GET'])]
    public function exportBudgetReport(Request $request, FormationSessionRepository $formationSessionRepository, ExcelExportService $excelExportService): Response
    {
        $filters = [
            'direction' => $request->query->get('direction'),
            'statut' => $request->query->get('statut'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'search' => $request->query->get('search')
        ];
        $formationSessions = $formationSessionRepository->findWithFilters($filters);
        return $excelExportService->exportFormationBudgetReport($formationSessions, $filters);
    }

    #[Route('/create', name: 'app_formation_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function create(): Response
    {
        return $this->render('formation/create.html.twig');
    }

    #[Route('/modele/create', name: 'app_formation_modele_create', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function createModele(): Response
    {
        return $this->render('formation/modele_create.html.twig');
    }

    #[Route('/modele', name: 'app_formation_modele_store', methods: ['POST'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function storeModele(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty(trim((string) ($data['titre'] ?? '')))) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre de la formation est requis.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $formation = new Formation();
        $formation->setTitre(trim($data['titre']));
        $formation->setDescription($data['description'] ?? null);
        $formation->setCreatedAt(new \DateTime());

        $entityManager->persist($formation);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de formation créé avec succès.',
            'formationId' => $formation->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/modele/{id}/edit', name: 'app_formation_modele_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function editModele(Formation $formation): Response
    {
        return $this->render('formation/modele_edit.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/modele/{id}', name: 'app_formation_modele_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function updateModele(Request $request, Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            return $this->json([
                'success' => false,
                'message' => 'Le titre de la formation est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $formation->setTitre($titre);
        $formation->setDescription($data['description'] ?? null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de formation mis à jour avec succès.',
        ]);
    }

    #[Route('/modele/{id}', name: 'app_formation_modele_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function deleteModele(Formation $formation, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$formation->getSessions()->isEmpty()) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce modèle : des sessions y sont associées.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($formation);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Modèle de formation supprimé avec succès.',
        ]);
    }

    #[Route('/modeles', name: 'app_formation_modele_index', methods: ['GET'])]
    public function modelesIndex(FormationRepository $formationRepository): Response
    {
        $modeles = $formationRepository->findBy([], ['titre' => 'ASC']);

        return $this->render('formation/modele_index.html.twig', [
            'modeles' => $modeles
        ]);
    }

    #[Route('/list', name: 'app_formation_list', methods: ['GET'])]
    public function list(Request $request, FormationSessionRepository $formationSessionRepository): JsonResponse
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $formationSessions = $formationSessionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);
        $data = [];
        
        foreach ($formationSessions as $session) {
            $formation = $session->getFormation();
            $data[] = [
                'id' => $session->getId(),
                'titre' => $formation ? $formation->getTitre() : '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                'dureePrevue' => $session->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format((float)$session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/filters/data', name: 'app_formation_filters_data', methods: ['GET'])]
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

    #[Route('/executed', name: 'app_formation_executed', methods: ['GET'])]
    public function executed(): Response
    {
        return $this->render('formation/executed.html.twig');
    }

    #[Route('/executed/list', name: 'app_formation_executed_list', methods: ['GET'])]
    public function executedList(FormationSessionRepository $formationSessionRepository): JsonResponse
    {
        $formationSessions = $formationSessionRepository->findExecutedSessions();
        $data = [];
        
        foreach ($formationSessions as $session) {
            $formation = $session->getFormation();
            // Calculer les dépenses réelles
            $depensesReelles = 0;
            foreach ($session->getDepenseFormations() as $depense) {
                $depensesReelles += (float)($depense->getMontantReel() ?? 0);
            }
            
            // Compter les participants
            $participants = count($session->getUserFormations());
            
            $data[] = [
                'id' => $session->getId(),
                'titre' => $formation ? $formation->getTitre() : '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'dateReelleDebut' => $session->getDateReelleDebut() ? $session->getDateReelleDebut()->format('d/m/Y') : '-',
                'dateReelleFin' => $session->getDateReelleFin() ? $session->getDateReelleFin()->format('d/m/Y') : '-',
                'lieuReel' => $session->getLieuReel() ?: '-',
                'dureeReelle' => $session->getDureeReelle() ? $session->getDureeReelle() . ' jours' : '-',
                'budgetPrevu' => number_format((float)$session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'depensesReelles' => number_format($depensesReelles, 0, ',', ' ') . ' FCFA',
                'participants' => $participants . ' participant(s)',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary'
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
    public function plannedList(FormationSessionRepository $formationSessionRepository): JsonResponse
    {
        $formationSessions = $formationSessionRepository->findPlannedSessions();
        $data = [];
        
        foreach ($formationSessions as $session) {
            $formation = $session->getFormation();
            // Compter les participants prévus
            $participantsPrevu = count($session->getUserFormations());
            
            $data[] = [
                'id' => $session->getId(),
                'titre' => $formation ? $formation->getTitre() : '-',
                'direction' => $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                'fonds' => $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                'datePrevueDebut' => $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                'datePrevueFin' => $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                'lieuPrevu' => $session->getLieuPrevu(),
                'dureePrevue' => $session->getDureePrevue() . ' jours',
                'budgetPrevu' => number_format((float)$session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                'participantsPrevu' => $participantsPrevu . ' participant(s)',
                'statut' => $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                'statut_couleur' => $session->getStatutActivite() ? $session->getStatutActivite()->getCouleur() : 'secondary'
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
        
        // 1. Créer ou récupérer la Formation (modèle)
        $formation = null;
        if (isset($data['formationId']) && $data['formationId']) {
            // Utiliser une formation existante
            $formation = $entityManager->getRepository(Formation::class)->find($data['formationId']);
        }
        
        if (!$formation) {
            // Créer une nouvelle formation (modèle)
            $formation = new Formation();
            $formation->setTitre($data['titre']);
            $formation->setDescription($data['description'] ?? null);
            $entityManager->persist($formation);
        }
        
        // 2. Créer la FormationSession (session)
        $formationSession = new FormationSession();
        $formationSession->setFormation($formation);
        $formationSession->setLieuPrevu($data['lieuPrevu']);
        $formationSession->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        $formationSession->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        $formationSession->setDureePrevue($data['dureePrevue']);
        $formationSession->setBudgetPrevu($data['budgetPrevu'] ?? '0');
        
        // Récupérer le statut par défaut (prévue non exécutée)
        $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_non_executee']);
        $formationSession->setStatutActivite($statutActivite);
        $formationSession->setNotes($data['notes'] ?? null);
        
        // Récupérer la direction
        $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
        $formationSession->setDirection($direction);
        
        // Récupérer le type de fonds
        $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
        $formationSession->setFonds($fonds);
        
        $entityManager->persist($formationSession);
        $entityManager->flush();
        
        // 3. Ajouter les participants
        if (isset($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $userId) {
                $user = $entityManager->getRepository(User::class)->find($userId);
                if ($user) {
                    $userFormation = new UserFormation();
                    $userFormation->setUser($user);
                    $userFormation->setFormationSession($formationSession);
                    // Récupérer le statut de participation par défaut (inscrit)
                    $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
                    $userFormation->setStatutParticipation($statutParticipation);
                    $entityManager->persist($userFormation);
                }
            }
        }
        
        // 4. Ajouter les dépenses prévues
        $totalBudgetPrevu = 0;
        if (isset($data['depenses']) && is_array($data['depenses'])) {
            foreach ($data['depenses'] as $depense) {
                $categorie = $entityManager->getRepository(\App\Entity\CategorieDepense::class)->find($depense['categorieId']);
                if ($categorie) {
                    $depenseFormation = new \App\Entity\DepenseFormation();
                    $depenseFormation->setFormationSession($formationSession);
                    $depenseFormation->setCategorie($categorie);
                    $depenseFormation->setMontantPrevu($depense['montant']);
                    $entityManager->persist($depenseFormation);
                    $totalBudgetPrevu += (float)$depense['montant'];
                }
            }
        }
        
        // Mettre à jour le budget prévu de la session avec le total des dépenses
        if ($totalBudgetPrevu > 0) {
            $formationSession->setBudgetPrevu((string)$totalBudgetPrevu);
        }
        
        // 5. Ajouter les documents
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
                $documentFormation->setFormationSession($formationSession);
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
            'formationId' => $formation->getId(),
            'formationSessionId' => $formationSession->getId()
        ]);
    }

    #[Route('/session/{id}', name: 'app_formation_session_show', methods: ['GET'])]
    public function showSession(FormationSession $formationSession): Response
    {
        return $this->render('formation/show.html.twig', [
            'formationSession' => $formationSession,
            'formation' => $formationSession->getFormation()
        ]);
    }
    
    #[Route('/{id}', name: 'app_formation_show', methods: ['GET'])]
    public function show(FormationSessionRepository $formationSessionRepository, FormationRepository $formationRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        // Chercher d'abord une FormationSession avec cet ID
        $formationSession = $formationSessionRepository->find($id);
        if ($formationSession) {
            return $this->render('formation/show.html.twig', [
                'formationSession' => $formationSession,
                'formation' => $formationSession->getFormation()
            ]);
        }
        
        // Sinon, chercher une Formation (modèle) et afficher ses sessions
        $formation = $formationRepository->find($id);
        if ($formation) {
            $sessions = $formationSessionRepository->findByFormation($id);
            return $this->render('formation/modele_show.html.twig', [
                'formation' => $formation,
                'sessions' => $sessions
            ]);
        }
        
        throw $this->createNotFoundException('Formation ou session introuvable');
    }

    /**
     * Recalcule le budget prévu d'une session de formation en additionnant tous les montants prévus des dépenses
     */
    private function recalculateBudgetPrevu(FormationSession $formationSession): float
    {
        $totalPrevu = 0;
        foreach ($formationSession->getDepenseFormations() as $depense) {
            $totalPrevu += (float)$depense->getMontantPrevu();
        }
        return $totalPrevu;
    }

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function edit(FormationSessionRepository $formationSessionRepository, int $id): Response
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            throw $this->createNotFoundException('Session de formation introuvable');
        }
        
        return $this->render('formation/edit.html.twig', [
            'formationSession' => $formationSession,
            'formation' => $formationSession->getFormation()
        ]);
    }

    #[Route('/{id}/update', name: 'app_formation_update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITEUR')]
    public function update(Request $request, FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Mettre à jour la Formation (modèle) si nécessaire
        $formation = $formationSession->getFormation();
        if (isset($data['titre'])) {
            $formation->setTitre($data['titre']);
        }
        if (isset($data['description'])) {
            $formation->setDescription($data['description']);
        }
        
        // Mettre à jour la FormationSession
        if (isset($data['lieuPrevu'])) {
            $formationSession->setLieuPrevu($data['lieuPrevu']);
        }
        if (isset($data['datePrevueDebut'])) {
            $formationSession->setDatePrevueDebut(new \DateTime($data['datePrevueDebut']));
        }
        if (isset($data['datePrevueFin'])) {
            $formationSession->setDatePrevueFin(new \DateTime($data['datePrevueFin']));
        }
        if (isset($data['dureePrevue'])) {
            $formationSession->setDureePrevue($data['dureePrevue']);
        }
        if (isset($data['budgetPrevu'])) {
            $formationSession->setBudgetPrevu($data['budgetPrevu']);
        }
        
        // Récupérer la direction
        if (isset($data['directionId'])) {
            $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($data['directionId']);
            if ($direction) {
                $formationSession->setDirection($direction);
            }
        }
        
        // Récupérer le type de fonds
        if (isset($data['fondsId'])) {
            $fonds = $entityManager->getRepository(\App\Entity\TypeFonds::class)->find($data['fondsId']);
            if ($fonds) {
                $formationSession->setFonds($fonds);
            }
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Formation modifiée avec succès'
        ]);
    }

    #[Route('/{id}', name: 'app_formation_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Chercher d'abord une FormationSession
        $formationSession = $formationSessionRepository->find($id);
        if ($formationSession) {
            $formation = $formationSession->getFormation();
            $entityManager->remove($formationSession);
            $entityManager->flush();
            
            // Vérifier si la Formation a d'autres sessions
            $otherSessions = $formationSessionRepository->findByFormation($formation->getId());
            if (empty($otherSessions)) {
                // Supprimer la Formation si elle n'a plus de sessions
                $entityManager->remove($formation);
                $entityManager->flush();
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Session de formation supprimée avec succès'
            ]);
        }
        
        // Sinon, chercher une Formation (modèle)
        $formation = $entityManager->getRepository(Formation::class)->find($id);
        if ($formation) {
            $entityManager->remove($formation);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Formation supprimée avec succès'
            ]);
        }
        
        return $this->json([
            'success' => false,
            'message' => 'Formation ou session introuvable'
        ], 404);
    }

    #[Route('/{id}/add-document', name: 'app_formation_add_document', methods: ['POST'])]
    public function addDocument(Request $request, FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
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
            $documentFormation->setFormationSession($formationSession);
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
    public function addDepense(Request $request, FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
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
            $depenseFormation->setFormationSession($formationSession);
            $depenseFormation->setCategorie($categorie);
            $depenseFormation->setMontantPrevu($data['montant']);
            
            $entityManager->persist($depenseFormation);
            $entityManager->flush();
            
            // Recalculer le budget prévu de la session
            $totalPrevu = $this->recalculateBudgetPrevu($formationSession);
            $formationSession->setBudgetPrevu((string)$totalPrevu);
            $entityManager->persist($formationSession);
            $entityManager->flush();
            
            // Recalculer les totaux après l'ajout
            $totalReel = 0;
            foreach ($formationSession->getDepenseFormations() as $depense) {
                $totalReel += (float)($depense->getMontantReel() ?? 0);
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
    public function addParticipant(Request $request, FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['userIds']) || !is_array($data['userIds']) || empty($data['userIds'])) {
            return $this->json([
                'success' => false,
                'message' => 'IDs utilisateurs requis (tableau)'
            ], 400);
        }
        
        try {
            $added = 0;
            $errors = [];
            
            foreach ($data['userIds'] as $userId) {
                // Récupérer l'utilisateur
                $user = $entityManager->getRepository(User::class)->find($userId);
                if (!$user) {
                    $errors[] = "Utilisateur ID $userId introuvable";
                    continue;
                }
                
                // Vérifier que l'utilisateur appartient à la même direction que la session
                if (!$user->getDirection() || $user->getDirection()->getId() !== $formationSession->getDirection()->getId()) {
                    $errors[] = "L'utilisateur {$user->getNom()} {$user->getPrenom()} doit appartenir à la même direction que la formation";
                    continue;
                }
                
                // Vérifier que l'utilisateur n'est pas déjà participant
                $existingUserFormation = $entityManager->getRepository(\App\Entity\UserFormation::class)->findOneBy([
                    'formationSession' => $formationSession,
                    'user' => $user
                ]);
                
                if ($existingUserFormation) {
                    $errors[] = "L'utilisateur {$user->getNom()} {$user->getPrenom()} est déjà participant à cette formation";
                    continue;
                }
                
                // Récupérer le statut de participation par défaut (inscrit)
                $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->findOneBy(['code' => 'inscrit']);
                
                // Créer l'entité UserFormation
                $userFormation = new \App\Entity\UserFormation();
                $userFormation->setFormationSession($formationSession);
                $userFormation->setUser($user);
                $userFormation->setStatutParticipation($statutParticipation);
                
                $entityManager->persist($userFormation);
                $added++;
            }
            
            $entityManager->flush();
            
            $message = "$added participant(s) ajouté(s) avec succès";
            if (!empty($errors)) {
                $message .= ". Erreurs: " . implode(', ', $errors);
            }
            
            return $this->json([
                'success' => $added > 0,
                'message' => $message,
                'added' => $added,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des participants: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/realisation', name: 'app_formation_realisation', methods: ['GET'])]
    public function realisation(FormationSessionRepository $formationSessionRepository, int $id): Response
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            throw $this->createNotFoundException('Session de formation introuvable');
        }
        
        return $this->render('formation/realisation.html.twig', [
            'formationSession' => $formationSession,
            'formation' => $formationSession->getFormation()
        ]);
    }

    #[Route('/{id}/realisation/participants', name: 'app_formation_realisation_participants', methods: ['GET'])]
    public function getParticipantsForRealisation(FormationSessionRepository $formationSessionRepository, UserRepository $userRepository, int $id): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
        $data = [
            'prevus' => [],
            'disponibles' => []
        ];
        
        // Participants prévus (déjà inscrits)
        foreach ($formationSession->getUserFormations() as $userFormation) {
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
        
        // Utilisateurs de la direction non encore participants
        $direction = $formationSession->getDirection();
        if ($direction) {
            $users = $userRepository->findByDirection($direction->getId());
            $participantsIds = array_map(function($uf) { return $uf->getUser()->getId(); }, $formationSession->getUserFormations()->toArray());
            
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
    public function getRealisationDepenses(FormationSessionRepository $formationSessionRepository, int $id): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
        $depenses = [];
        $totalPrevu = 0;
        $totalReel = 0;
        
        foreach ($formationSession->getDepenseFormations() as $depense) {
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
    public function completeRealisation(Request $request, FormationSessionRepository $formationSessionRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $formationSession = $formationSessionRepository->find($id);
        if (!$formationSession) {
            return $this->json([
                'success' => false,
                'message' => 'Session de formation introuvable'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        try {
            // 1. Mettre à jour les informations de la session
            if (isset($data['dateReelleDebut'])) {
                $formationSession->setDateReelleDebut(new \DateTime($data['dateReelleDebut']));
            }
            if (isset($data['dateReelleFin'])) {
                $formationSession->setDateReelleFin(new \DateTime($data['dateReelleFin']));
            }
            if (isset($data['lieuReel'])) {
                $formationSession->setLieuReel($data['lieuReel']);
            }
            
            // Calculer la durée réelle
            if (isset($data['dateReelleDebut']) && isset($data['dateReelleFin'])) {
                $dateDebut = new \DateTime($data['dateReelleDebut']);
                $dateFin = new \DateTime($data['dateReelleFin']);
                $dureeReelle = $dateDebut->diff($dateFin)->days + 1; // +1 pour inclure le jour de fin
                $formationSession->setDureeReelle($dureeReelle);
            }
            
            // 2. Mettre à jour le statut de l'activité selon la nature
            $natureCode = $data['natureFormation'] ?? 'prevue_executee';
            $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => $natureCode]);
            if (!$statutActivite) {
                // Fallback vers le statut par défaut si le code n'existe pas
                $statutActivite = $entityManager->getRepository(\App\Entity\StatutActivite::class)->findOneBy(['code' => 'prevue_executee']);
            }
            $formationSession->setStatutActivite($statutActivite);
            
            // 3. Mettre à jour les statuts des participants
            if (isset($data['participants'])) {
                foreach ($data['participants'] as $participantData) {
                    $userFormation = $entityManager->getRepository(\App\Entity\UserFormation::class)->find($participantData['userFormationId']);
                    if ($userFormation && $userFormation->getFormationSession() === $formationSession) {
                        $statutParticipation = $entityManager->getRepository(\App\Entity\StatutParticipation::class)->find($participantData['statut_participation_id']);
                        if ($statutParticipation) {
                            $userFormation->setStatutParticipation($statutParticipation);
                        }
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
                        $userFormation->setFormationSession($formationSession);
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
                            'formationSession' => $formationSession,
                            'categorie' => $categorie
                        ]);
                        
                        if ($depenseFormation) {
                            $depenseFormation->setMontantReel($depenseData['montant']);
                        } else {
                            // Créer une nouvelle dépense si elle n'existe pas
                            $depenseFormation = new \App\Entity\DepenseFormation();
                            $depenseFormation->setFormationSession($formationSession);
                            $depenseFormation->setCategorie($categorie);
                            $depenseFormation->setMontantReel($depenseData['montant']);
                            $entityManager->persist($depenseFormation);
                        }
                    }
                }
            }
            
            // Calculer le budget réel total
            $budgetReel = 0;
            foreach ($formationSession->getDepenseFormations() as $depense) {
                if ($depense->getMontantReel()) {
                    $budgetReel += (float)$depense->getMontantReel();
                }
            }
            $formationSession->setBudgetReel((string)$budgetReel);
            
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
    public function exportPdf(Request $request, FormationSessionRepository $formationSessionRepository, EntityManagerInterface $entityManager, PdfService $pdfService): Response
    {
        // Récupérer les paramètres de filtrage
        $statutId = $request->query->get('statut');
        $directionId = $request->query->get('direction');
        $periode = $request->query->get('periode');
        $participant = $request->query->get('participant');
        
        $formationSessions = $formationSessionRepository->findAllWithFilters($statutId, $directionId, $periode, $participant);
        
        // Préparer les données pour le PDF avec toutes les colonnes
        $headers = [
            'ID', 'Titre', 'Description', 'Direction', 'Fonds', 'Durée prévue', 'Durée réelle', 
            'Lieu prévu', 'Lieu réel', 'Budget prévu', 'Budget réel', 'Date début prévue', 
            'Date fin prévue', 'Date début réelle', 'Date fin réelle', 'Statut', 'Notes'
        ];
        $data = [];
        
        foreach ($formationSessions as $session) {
            $formation = $session->getFormation();
            $data[] = [
                $session->getId(),
                $formation ? $formation->getTitre() : '-',
                $formation ? ($formation->getDescription() ?: '-') : '-',
                $session->getDirection() ? $session->getDirection()->getLibelle() : '-',
                $session->getFonds() ? $session->getFonds()->getLibelle() : '-',
                $session->getDureePrevue() . ' jours',
                $session->getDureeReelle() ? $session->getDureeReelle() . ' jours' : '-',
                $session->getLieuPrevu() ?: '-',
                $session->getLieuReel() ?: '-',
                number_format((float)$session->getBudgetPrevu(), 0, ',', ' ') . ' FCFA',
                $session->getBudgetReel() ? number_format((float)$session->getBudgetReel(), 0, ',', ' ') . ' FCFA' : '-',
                $session->getDatePrevueDebut() ? $session->getDatePrevueDebut()->format('d/m/Y') : '-',
                $session->getDatePrevueFin() ? $session->getDatePrevueFin()->format('d/m/Y') : '-',
                $session->getDateReelleDebut() ? $session->getDateReelleDebut()->format('d/m/Y') : '-',
                $session->getDateReelleFin() ? $session->getDateReelleFin()->format('d/m/Y') : '-',
                $session->getStatutActivite() ? $session->getStatutActivite()->getLibelle() : '-',
                $session->getNotes() ?: '-'
            ];
        }
        
        // Préparer les filtres appliqués
        $filters = [];
        if ($statutId) {
            $statut = $entityManager->getRepository(\App\Entity\StatutActivite::class)->find($statutId);
            if ($statut) $filters[] = 'Statut: ' . $statut->getLibelle();
        }
        if ($directionId) {
            $direction = $entityManager->getRepository(\App\Entity\Direction::class)->find($directionId);
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
            $user = $entityManager->getRepository(\App\Entity\User::class)->find($participant);
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
