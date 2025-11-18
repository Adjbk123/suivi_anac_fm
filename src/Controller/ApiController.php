<?php

namespace App\Controller;

use App\Repository\DirectionRepository;
use App\Repository\ServiceRepository;
use App\Repository\DomaineRepository;
use App\Repository\TypeFondsRepository;
use App\Repository\PosteRepository;
use App\Repository\UserRepository;
use App\Repository\CategorieDepenseRepository;
use App\Repository\StatutActiviteRepository;
use App\Repository\StatutParticipationRepository;
use App\Repository\FormationRepository;
use App\Repository\MissionRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserMissionRepository;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/directions', name: 'api_directions', methods: ['GET'])]
    public function getDirections(DirectionRepository $directionRepository): JsonResponse
    {
        $directions = $directionRepository->findAll();
        $data = [];
        
        foreach ($directions as $direction) {
            $data[] = [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle(),
                'description' => $direction->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/services', name: 'api_services', methods: ['GET'])]
    public function getServices(ServiceRepository $serviceRepository): JsonResponse
    {
        $services = $serviceRepository->findAllWithDirection();
        $data = [];
        
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'libelle' => $service->getLibelle(),
                'description' => $service->getDescription(),
                'direction' => $service->getDirection() ? [
                    'id' => $service->getDirection()->getId(),
                    'libelle' => $service->getDirection()->getLibelle()
                ] : null
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/services/{directionId}', name: 'api_services_by_direction', methods: ['GET'])]
    public function getServicesByDirection(int $directionId, ServiceRepository $serviceRepository): JsonResponse
    {
        $services = $serviceRepository->findBy(['direction' => $directionId]);
        $data = [];
        
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'libelle' => $service->getLibelle(),
                'description' => $service->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/domaines', name: 'api_domaines', methods: ['GET'])]
    public function getDomaines(DomaineRepository $domaineRepository): JsonResponse
    {
        $domaines = $domaineRepository->findAll();
        $data = [];
        
        foreach ($domaines as $domaine) {
            $data[] = [
                'id' => $domaine->getId(),
                'libelle' => $domaine->getLibelle(),
                'description' => $domaine->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/fonds', name: 'api_fonds', methods: ['GET'])]
    public function getFonds(TypeFondsRepository $typeFondsRepository): JsonResponse
    {
        $fonds = $typeFondsRepository->findAll();
        $data = [];
        
        foreach ($fonds as $fond) {
            $data[] = [
                'id' => $fond->getId(),
                'libelle' => $fond->getLibelle(),
                'description' => $fond->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/postes', name: 'api_posts', methods: ['GET'])]
    public function getPostes(PosteRepository $posteRepository): JsonResponse
    {
        $postes = $posteRepository->findAll();
        $data = [];
        
        foreach ($postes as $poste) {
            $data[] = [
                'id' => $poste->getId(),
                'libelle' => $poste->getLibelle(),
                'description' => $poste->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/users-by-service/{serviceId}', name: 'api_users_by_service', methods: ['GET'])]
    public function getUsersByService(int $serviceId, UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findBy(['service' => $serviceId]);
        $data = [];
        
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'matricule' => $user->getMatricule()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAllWithRelations();
        $data = [];
        
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'matricule' => $user->getMatricule(),
                'service' => $user->getService() ? [
                    'id' => $user->getService()->getId(),
                    'libelle' => $user->getService()->getLibelle()
                ] : null,
                'domaine' => $user->getDomaine() ? [
                    'id' => $user->getDomaine()->getId(),
                    'libelle' => $user->getDomaine()->getLibelle()
                ] : null,
                'poste' => $user->getPoste() ? [
                    'id' => $user->getPoste()->getId(),
                    'libelle' => $user->getPoste()->getLibelle()
                ] : null
            ];
        }
        
        return $this->json($data);
    }



    #[Route('/categories-depenses', name: 'api_categories_depenses', methods: ['GET'])]
    public function getCategoriesDepenses(CategorieDepenseRepository $categorieDepenseRepository): JsonResponse
    {
        $categories = $categorieDepenseRepository->findAll();
        $data = [];
        
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'libelle' => $categorie->getLibelle(),
                'description' => $categorie->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/formations', name: 'api_formations', methods: ['GET'])]
    public function getFormations(FormationRepository $formationRepository): JsonResponse
    {
        $templates = $formationRepository->findBy([], ['titre' => 'ASC']);
        $data = [];

        foreach ($templates as $template) {
            $data[] = [
                'id' => $template->getId(),
                'titre' => $template->getTitre(),
                'description' => $template->getDescription(),
                'createdAt' => $template->getCreatedAt() ? $template->getCreatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        return $this->json($data);
    }

    #[Route('/missions', name: 'api_missions', methods: ['GET'])]
    public function getMissions(MissionRepository $missionRepository): JsonResponse
    {
        $missions = $missionRepository->findBy([], ['titre' => 'ASC']);
        $data = [];

        foreach ($missions as $mission) {
            $data[] = [
                'id' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'description' => $mission->getDescription(),
                'createdAt' => $mission->getCreatedAt() ? $mission->getCreatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        return $this->json($data);
    }

    #[Route('/statuts-activite', name: 'api_statuts_activite', methods: ['GET'])]
    public function getStatutsActivite(StatutActiviteRepository $statutActiviteRepository): JsonResponse
    {
        $statuts = $statutActiviteRepository->findAll();
        $data = [];
        
        foreach ($statuts as $statut) {
            $data[] = [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle(),
                'description' => $statut->getDescription(),
                'couleur' => $statut->getCouleur()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/statuts-participation', name: 'api_statuts_participation', methods: ['GET'])]
    public function getStatutsParticipation(StatutParticipationRepository $statutParticipationRepository): JsonResponse
    {
        $statuts = $statutParticipationRepository->findAll();
        $data = [];
        
        foreach ($statuts as $statut) {
            $data[] = [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle(),
                'description' => $statut->getDescription(),
                'couleur' => $statut->getCouleur()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/user/{userId}/formations', name: 'api_user_formations', methods: ['GET'])]
    public function getUserFormations(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        $data = [];
        
        // Récupérer toutes les formations de l'utilisateur
        foreach ($user->getUserFormations() as $userFormation) {
            $formationSession = $userFormation->getFormationSession();
            $formation = $userFormation->getFormation();
            
            if (!$formationSession || !$formation) {
                continue;
            }
            
            // Calculer les dépenses réelles
            $depensesReelles = 0;
            foreach ($formationSession->getDepenseFormations() as $depense) {
                $depensesReelles += (float)($depense->getMontantReel() ?? 0);
            }
            
            $data[] = [
                'id' => $formationSession->getId(),
                'formationId' => $formation->getId(),
                'titre' => $formation->getTitre(),
                'description' => $formation->getDescription(),
                'lieuPrevu' => $formationSession->getLieuPrevu(),
                'lieuReel' => $formationSession->getLieuReel(),
                'datePrevueDebut' => $formationSession->getDatePrevueDebut() ? $formationSession->getDatePrevueDebut()->format('d/m/Y') : null,
                'datePrevueFin' => $formationSession->getDatePrevueFin() ? $formationSession->getDatePrevueFin()->format('d/m/Y') : null,
                'dateReelleDebut' => $formationSession->getDateReelleDebut() ? $formationSession->getDateReelleDebut()->format('d/m/Y') : null,
                'dateReelleFin' => $formationSession->getDateReelleFin() ? $formationSession->getDateReelleFin()->format('d/m/Y') : null,
                'dureePrevue' => $formationSession->getDureePrevue(),
                'dureeReelle' => $formationSession->getDureeReelle(),
                'budgetPrevu' => $formationSession->getBudgetPrevu(),
                'depensesReelles' => $depensesReelles,
                'direction' => $formationSession->getDirection() ? [
                    'id' => $formationSession->getDirection()->getId(),
                    'libelle' => $formationSession->getDirection()->getLibelle()
                ] : null,
                'fonds' => $formationSession->getFonds() ? [
                    'id' => $formationSession->getFonds()->getId(),
                    'libelle' => $formationSession->getFonds()->getLibelle()
                ] : null,
                'statutActivite' => $formationSession->getStatutActivite() ? [
                    'id' => $formationSession->getStatutActivite()->getId(),
                    'code' => $formationSession->getStatutActivite()->getCode(),
                    'libelle' => $formationSession->getStatutActivite()->getLibelle(),
                    'couleur' => $formationSession->getStatutActivite()->getCouleur()
                ] : null,
                'statutParticipation' => $userFormation->getStatutParticipation() ? [
                    'id' => $userFormation->getStatutParticipation()->getId(),
                    'code' => $userFormation->getStatutParticipation()->getCode(),
                    'libelle' => $userFormation->getStatutParticipation()->getLibelle(),
                    'couleur' => $userFormation->getStatutParticipation()->getCouleur()
                ] : null,
                'notes' => $formationSession->getNotes()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/user/{userId}/missions', name: 'api_user_missions', methods: ['GET'])]
    public function getUserMissions(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        $data = [];
        
        // Récupérer toutes les missions de l'utilisateur
        foreach ($user->getUserMissions() as $userMission) {
            $missionSession = $userMission->getMissionSession();
            $mission = $userMission->getMission();
            
            if (!$missionSession || !$mission) {
                continue;
            }
            
            // Calculer les dépenses réelles
            $depensesReelles = 0;
            foreach ($missionSession->getDepenseMissions() as $depense) {
                $depensesReelles += (float)($depense->getMontantReel() ?? 0);
            }
            
            $data[] = [
                'id' => $missionSession->getId(),
                'missionId' => $mission->getId(),
                'titre' => $mission->getTitre(),
                'description' => $mission->getDescription(),
                'lieuPrevu' => $missionSession->getLieuPrevu(),
                'lieuReel' => $missionSession->getLieuReel(),
                'datePrevueDebut' => $missionSession->getDatePrevueDebut() ? $missionSession->getDatePrevueDebut()->format('d/m/Y') : null,
                'datePrevueFin' => $missionSession->getDatePrevueFin() ? $missionSession->getDatePrevueFin()->format('d/m/Y') : null,
                'dateReelleDebut' => $missionSession->getDateReelleDebut() ? $missionSession->getDateReelleDebut()->format('d/m/Y') : null,
                'dateReelleFin' => $missionSession->getDateReelleFin() ? $missionSession->getDateReelleFin()->format('d/m/Y') : null,
                'dureePrevue' => $missionSession->getDureePrevue(),
                'dureeReelle' => $missionSession->getDureeReelle(),
                'budgetPrevu' => $missionSession->getBudgetPrevu(),
                'depensesReelles' => $depensesReelles,
                'direction' => $missionSession->getDirection() ? [
                    'id' => $missionSession->getDirection()->getId(),
                    'libelle' => $missionSession->getDirection()->getLibelle()
                ] : null,
                'fonds' => $missionSession->getFonds() ? [
                    'id' => $missionSession->getFonds()->getId(),
                    'libelle' => $missionSession->getFonds()->getLibelle()
                ] : null,
                'statutActivite' => $missionSession->getStatutActivite() ? [
                    'id' => $missionSession->getStatutActivite()->getId(),
                    'code' => $missionSession->getStatutActivite()->getCode(),
                    'libelle' => $missionSession->getStatutActivite()->getLibelle(),
                    'couleur' => $missionSession->getStatutActivite()->getCouleur()
                ] : null,
                'statutParticipation' => $userMission->getStatutParticipation() ? [
                    'id' => $userMission->getStatutParticipation()->getId(),
                    'code' => $userMission->getStatutParticipation()->getCode(),
                    'libelle' => $userMission->getStatutParticipation()->getLibelle(),
                    'couleur' => $userMission->getStatutParticipation()->getCouleur()
                ] : null,
                'notes' => $missionSession->getNotes()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/users-by-direction/{directionId}', name: 'api_users_by_direction', methods: ['GET'])]
    public function getUsersByDirection(
        int $directionId,
        Request $request,
        UserRepository $userRepository,
        UserMissionRepository $userMissionRepository,
        \App\Repository\UserFormationRepository $userFormationRepository
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->query->get('pageSize', 50)));
        $missionSessionId = $request->query->getInt('excludeMissionParticipants', 0);
        if ($missionSessionId === 0) {
            $missionSessionId = $request->query->getInt('excludeMissionSessionParticipants', 0);
        }
        $formationSessionId = $request->query->getInt('excludeFormationSessionParticipants', 0);

        $excludedIds = [];

        if ($missionSessionId > 0) {
            $excludedIds = array_merge($excludedIds, $userMissionRepository->findUserIdsByMissionSession($missionSessionId));
        }

        if ($formationSessionId > 0) {
            $excludedIds = array_merge($excludedIds, $userFormationRepository->findUserIdsByFormationSession($formationSessionId));
        }

        $users = $userRepository->findByDirectionPaginated($directionId, $page, $pageSize, $excludedIds);

        $data = [];
        foreach ($users as $user) {
            $service = $user->getService();
            $direction = $user->getDirection() ?: ($service ? $service->getDirection() : null);

            $data[] = [
                'id' => $user->getId(),
                'matricule' => $user->getMatricule(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'service' => $service ? $service->getLibelle() : '-',
                'direction' => $direction ? $direction->getLibelle() : '-'
            ];
        }

        return $this->json([
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => count($data),
            'users' => $data
        ]);
    }
}
