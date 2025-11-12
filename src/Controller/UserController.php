<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRoleFormType;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\DomaineRepository;
use App\Repository\PosteRepository;
use App\Repository\DirectionRepository;
use App\Service\PdfService;
use App\Service\RoleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig');
    }

    #[Route('/list', name: 'app_user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAllWithRelations();
        
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'matricule' => $user->getMatricule() ?: '-',
                'role' => $user->getPrimaryRole(),
                'direction' => $user->getDirection() ? $user->getDirection()->getLibelle() : '-',
                'direction_id' => $user->getDirection() ? $user->getDirection()->getId() : null,
                'service' => $user->getService() ? $user->getService()->getLibelle() : '-',
                'service_id' => $user->getService() ? $user->getService()->getId() : null,
                'domaine' => $user->getDomaine() ? $user->getDomaine()->getLibelle() : '-',
                'domaine_id' => $user->getDomaine() ? $user->getDomaine()->getId() : null,
                'poste' => $user->getPoste() ? $user->getPoste()->getLibelle() : '-',
                'poste_id' => $user->getPoste() ? $user->getPoste()->getId() : null
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_user_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository, DomaineRepository $domaineRepository, PosteRepository $posteRepository, DirectionRepository $directionRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }
        
        // La direction est optionnelle mais si elle n'est pas fournie, le service est obligatoire
        if (!isset($data['direction_id']) && !isset($data['service_id'])) {
            return $this->json(['success' => false, 'message' => 'Vous devez sélectionner une direction ou un service'], 400);
        }
        
        $direction = null;
        if (isset($data['direction_id']) && $data['direction_id']) {
            $direction = $directionRepository->find($data['direction_id']);
            if (!$direction) {
                return $this->json(['success' => false, 'message' => 'Direction introuvable'], 404);
            }
        }
        
        $service = null;
        if (isset($data['service_id']) && $data['service_id']) {
            $service = $serviceRepository->find($data['service_id']);
            if (!$service) {
                return $this->json(['success' => false, 'message' => 'Service introuvable'], 404);
            }
        }
        
        $domaine = null;
        if (isset($data['domaine_id']) && $data['domaine_id']) {
            $domaine = $domaineRepository->find($data['domaine_id']);
            if (!$domaine) {
                return $this->json(['success' => false, 'message' => 'Domaine introuvable'], 404);
            }
        }
        
        $poste = null;
        if (isset($data['poste_id']) && $data['poste_id']) {
            $poste = $posteRepository->find($data['poste_id']);
            if (!$poste) {
                return $this->json(['success' => false, 'message' => 'Poste introuvable'], 404);
            }
        }
        
        $user = new User();
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);
        $user->setMatricule($data['matricule'] ?? null);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setDirection($direction);
        $user->setService($service);
        $user->setDomaine($domaine);
        $user->setPoste($poste);
        
        // Gérer les rôles
        $roles = $data['roles'] ?? 'ROLE_USER';
        $user->setRoles([$roles]);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Utilisateur créé avec succès']);
    }

    #[Route('/show/{id}', name: 'app_user_show_page', methods: ['GET'])]
    public function showPage(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'matricule' => $user->getMatricule(),
            'direction_id' => $user->getDirection() ? $user->getDirection()->getId() : null,
            'service_id' => $user->getService() ? $user->getService()->getId() : null,
            'domaine_id' => $user->getDomaine() ? $user->getDomaine()->getId() : null,
            'poste_id' => $user->getPoste() ? $user->getPoste()->getId() : null,
            'roles' => $user->getPrimaryRole() // Retourner le rôle principal
        ]);
    }

    #[Route('/{id}', name: 'app_user_edit', methods: ['PUT'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository, DomaineRepository $domaineRepository, PosteRepository $posteRepository, DirectionRepository $directionRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }
        
        // La direction est optionnelle mais si elle n'est pas fournie, le service est obligatoire
        if (!isset($data['direction_id']) && !isset($data['service_id'])) {
            return $this->json(['success' => false, 'message' => 'Vous devez sélectionner une direction ou un service'], 400);
        }
        
        $direction = null;
        if (isset($data['direction_id']) && $data['direction_id']) {
            $direction = $directionRepository->find($data['direction_id']);
            if (!$direction) {
                return $this->json(['success' => false, 'message' => 'Direction introuvable'], 404);
            }
        }
        
        $service = null;
        if (isset($data['service_id']) && $data['service_id']) {
            $service = $serviceRepository->find($data['service_id']);
            if (!$service) {
                return $this->json(['success' => false, 'message' => 'Service introuvable'], 404);
            }
        }
        
        $domaine = null;
        if (isset($data['domaine_id']) && $data['domaine_id']) {
            $domaine = $domaineRepository->find($data['domaine_id']);
            if (!$domaine) {
                return $this->json(['success' => false, 'message' => 'Domaine introuvable'], 404);
            }
        }
        
        $poste = null;
        if (isset($data['poste_id']) && $data['poste_id']) {
            $poste = $posteRepository->find($data['poste_id']);
            if (!$poste) {
                return $this->json(['success' => false, 'message' => 'Poste introuvable'], 404);
            }
        }
        
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);
        $user->setMatricule($data['matricule'] ?? null);
        
        // Mettre à jour le mot de passe seulement s'il est fourni
        if (isset($data['password']) && $data['password']) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }
        
        $user->setDirection($direction);
        $user->setService($service);
        $user->setDomaine($domaine);
        $user->setPoste($poste);
        
        // Mettre à jour les rôles si fournis
        if (isset($data['roles']) && $data['roles']) {
            $user->setRoles([$data['roles']]);
        }
        
        // Mettre à jour la date de modification
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Utilisateur modifié avec succès']);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($user);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    }

    #[Route('/directions/list', name: 'app_user_directions_list', methods: ['GET'])]
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

    #[Route('/services/list', name: 'app_user_services_list', methods: ['GET'])]
    public function getServices(ServiceRepository $serviceRepository, Request $request): JsonResponse
    {
        $directionId = $request->query->get('direction_id');
        
        if ($directionId) {
            // Filtrer les services par direction
            $services = $serviceRepository->findBy(['direction' => $directionId]);
        } else {
            $services = $serviceRepository->findAll();
        }
        
        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'libelle' => $service->getLibelle(),
                'direction_id' => $service->getDirection() ? $service->getDirection()->getId() : null,
                'direction' => $service->getDirection() ? $service->getDirection()->getLibelle() : '-'
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/domaines/list', name: 'app_user_domaines_list', methods: ['GET'])]
    public function getDomaines(DomaineRepository $domaineRepository): JsonResponse
    {
        $domaines = $domaineRepository->findAll();
        
        $data = [];
        foreach ($domaines as $domaine) {
            $data[] = [
                'id' => $domaine->getId(),
                'libelle' => $domaine->getLibelle()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/postes/list', name: 'app_user_postes_list', methods: ['GET'])]
    public function getPostes(PosteRepository $posteRepository): JsonResponse
    {
        $postes = $posteRepository->findAll();
        
        $data = [];
        foreach ($postes as $poste) {
            $data[] = [
                'id' => $poste->getId(),
                'libelle' => $poste->getLibelle()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/export/pdf', name: 'app_user_export_pdf', methods: ['GET'])]
    public function exportPdf(UserRepository $userRepository, PdfService $pdfService): Response
    {
        $users = $userRepository->findAllWithRelations();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Nom', 'Prénom', 'Email', 'Matricule', 'Service', 'Domaine', 'Poste'];
        $data = [];
        
        foreach ($users as $user) {
            $data[] = [
                $user->getId(),
                $user->getNom(),
                $user->getPrenom(),
                $user->getEmail(),
                $user->getMatricule() ?: '-',
                $user->getService() ? $user->getService()->getLibelle() : '-',
                $user->getDomaine() ? $user->getDomaine()->getLibelle() : '-',
                $user->getPoste() ? $user->getPoste()->getLibelle() : '-'
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Utilisateurs',
            $headers,
            $data,
            'utilisateurs_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'utilisateurs_anac_benin.pdf'
        ));
        
        return $response;
    }

    #[Route('/{id}/roles', name: 'app_user_edit_roles', methods: ['GET', 'POST'])]
    public function editRoles(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserRoleFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date de modification
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Les rôles de l\'utilisateur ont été mis à jour avec succès !');
            return $this->redirectToRoute('app_user_show_page', ['id' => $user->getId()]);
        }

        return $this->render('user/edit_roles.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/roles', name: 'app_user_update_roles', methods: ['PUT'])]
    public function updateRoles(Request $request, User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['roles'])) {
            return $this->json(['success' => false, 'message' => 'Rôles manquants'], 400);
        }
        
        // Mettre à jour les rôles
        $user->setRoles($data['roles']);
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true, 
            'message' => 'Rôles mis à jour avec succès',
            'roles' => $user->getRoles()
        ]);
    }
}
