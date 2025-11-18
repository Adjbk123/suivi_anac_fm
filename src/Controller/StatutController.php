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
                'formations_count' => count($statut->getFormationSessions()),
                'isUsed' => $statut->isUsed(),
                'missions_count' => count($statut->getMissionSessions())
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

    // DÉSACTIVÉ : Les statuts d'activité sont configurés une fois pour toutes
    // #[Route('/activite/new', name: 'app_statut_activite_new', methods: ['POST'])]
    // public function newActivite(Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     
    //     $statut = new StatutActivite();
    //     $statut->setCode($data['code']);
    //     $statut->setLibelle($data['libelle']);
    //     $statut->setDescription($data['description'] ?? null);
    //     $statut->setCouleur($data['couleur']);
    //     
    //     $entityManager->persist($statut);
    //     $entityManager->flush();
    //     
    //     return $this->json([
    //         'success' => true,
    //         'message' => 'Statut d\'activité créé avec succès',
    //         'statut' => [
    //             'id' => $statut->getId(),
    //             'code' => $statut->getCode(),
    //             'libelle' => $statut->getLibelle()
    //         ]
    //     ]);
    // }

    // DÉSACTIVÉ : Les statuts de participation sont configurés une fois pour toutes
    // #[Route('/participation/new', name: 'app_statut_participation_new', methods: ['POST'])]
    // public function newParticipation(Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     
    //     $statut = new StatutParticipation();
    //     $statut->setCode($data['code']);
    //     $statut->setLibelle($data['libelle']);
    //     $statut->setDescription($data['description'] ?? null);
    //     $statut->setCouleur($data['couleur']);
    //     
    //     $entityManager->persist($statut);
    //     $entityManager->flush();
    //     
    //     return $this->json([
    //         'success' => true,
    //         'message' => 'Statut de participation créé avec succès',
    //         'statut' => [
    //             'id' => $statut->getId(),
    //             'code' => $statut->getCode(),
    //             'libelle' => $statut->getLibelle()
    //         ]
    //     ]);
    // }

    // DÉSACTIVÉ : Les statuts d'activité ne peuvent pas être supprimés car ils sont configurés une fois pour toutes
    // #[Route('/activite/{id}', name: 'app_statut_activite_delete', methods: ['DELETE'])]
    // public function deleteActivite(StatutActivite $statut, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     // Vérifier si le statut d'activité est utilisé
    //     if ($statut->isUsed()) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Impossible de supprimer ce statut d\'activité car il est utilisé dans des missions ou formations'
    //         ], 400);
    //     }
    //     
    //     $entityManager->remove($statut);
    //     $entityManager->flush();
    //     
    //     return $this->json([
    //         'success' => true,
    //         'message' => 'Statut d\'activité supprimé avec succès'
    //     ]);
    // }
}
