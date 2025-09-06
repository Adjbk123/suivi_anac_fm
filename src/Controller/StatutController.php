<?php

namespace App\Controller;

use App\Entity\StatutActivite;
use App\Entity\StatutParticipation;
use App\Repository\StatutActiviteRepository;
use App\Repository\StatutParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/statut')]
class StatutController extends AbstractController
{
    #[Route('/', name: 'app_statut_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('statut/index.html.twig');
    }

    #[Route('/activite', name: 'app_statut_activite_index', methods: ['GET'])]
    public function indexActivite(): Response
    {
        return $this->render('statut/activite.html.twig');
    }

    #[Route('/participation', name: 'app_statut_participation_index', methods: ['GET'])]
    public function indexParticipation(): Response
    {
        return $this->render('statut/participation.html.twig');
    }

    #[Route('/activite/list', name: 'app_statut_activite_list', methods: ['GET'])]
    public function listActivite(StatutActiviteRepository $statutActiviteRepository): JsonResponse
    {
        $statuts = $statutActiviteRepository->findAll();
        $data = [];
        
        foreach ($statuts as $statut) {
            $data[] = [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle(),
                'description' => $statut->getDescription(),
                'couleur' => $statut->getCouleur(),
                'formations_count' => count($statut->getFormations()),
                'missions_count' => count($statut->getMissions())
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/participation/list', name: 'app_statut_participation_list', methods: ['GET'])]
    public function listParticipation(StatutParticipationRepository $statutParticipationRepository): JsonResponse
    {
        $statuts = $statutParticipationRepository->findAll();
        $data = [];
        
        foreach ($statuts as $statut) {
            $data[] = [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle(),
                'description' => $statut->getDescription(),
                'couleur' => $statut->getCouleur(),
                'user_formations_count' => count($statut->getUserFormations()),
                'user_missions_count' => count($statut->getUserMissions())
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/activite/new', name: 'app_statut_activite_new', methods: ['POST'])]
    public function newActivite(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $statut = new StatutActivite();
        $statut->setCode($data['code']);
        $statut->setLibelle($data['libelle']);
        $statut->setDescription($data['description'] ?? null);
        $statut->setCouleur($data['couleur']);
        
        $entityManager->persist($statut);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Statut d\'activité créé avec succès',
            'statut' => [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle()
            ]
        ]);
    }

    #[Route('/participation/new', name: 'app_statut_participation_new', methods: ['POST'])]
    public function newParticipation(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $statut = new StatutParticipation();
        $statut->setCode($data['code']);
        $statut->setLibelle($data['libelle']);
        $statut->setDescription($data['description'] ?? null);
        $statut->setCouleur($data['couleur']);
        
        $entityManager->persist($statut);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Statut de participation créé avec succès',
            'statut' => [
                'id' => $statut->getId(),
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle()
            ]
        ]);
    }
}
