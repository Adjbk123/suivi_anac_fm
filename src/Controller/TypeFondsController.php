<?php

namespace App\Controller;

use App\Entity\TypeFonds;
use App\Repository\TypeFondsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/typefonds')]
class TypeFondsController extends AbstractController
{
    #[Route('/', name: 'app_typefonds_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('typefonds/index.html.twig');
    }

    #[Route('/list', name: 'app_typefonds_list', methods: ['GET'])]
    public function list(TypeFondsRepository $typeFondsRepository): JsonResponse
    {
        $typeFonds = $typeFondsRepository->findAll();
        $data = [];

        foreach ($typeFonds as $typeFond) {
            $data[] = [
                'id' => $typeFond->getId(),
                'libelle' => $typeFond->getLibelle(),
                'description' => $typeFond->getDescription(),
                'actions' => ''
            ];
        }

        return $this->json($data);
    }

    #[Route('/new', name: 'app_typefonds_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['libelle'])) {
            return $this->json(['success' => false, 'message' => 'Le libellé est requis'], 400);
        }

        $typeFond = new TypeFonds();
        $typeFond->setLibelle($data['libelle']);
        $typeFond->setDescription($data['description'] ?? '');

        $entityManager->persist($typeFond);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Type de fonds créé avec succès',
            'data' => [
                'id' => $typeFond->getId(),
                'libelle' => $typeFond->getLibelle(),
                'description' => $typeFond->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_typefonds_show', methods: ['GET'])]
    public function show(TypeFonds $typeFond): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $typeFond->getId(),
                'libelle' => $typeFond->getLibelle(),
                'description' => $typeFond->getDescription()
            ]
        ]);
    }

    #[Route('/{id}/edit', name: 'app_typefonds_edit', methods: ['POST'])]
    public function edit(Request $request, TypeFonds $typeFond, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['libelle'])) {
            return $this->json(['success' => false, 'message' => 'Le libellé est requis'], 400);
        }

        $typeFond->setLibelle($data['libelle']);
        $typeFond->setDescription($data['description'] ?? '');

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Type de fonds modifié avec succès',
            'data' => [
                'id' => $typeFond->getId(),
                'libelle' => $typeFond->getLibelle(),
                'description' => $typeFond->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_typefonds_delete', methods: ['DELETE'])]
    public function delete(TypeFonds $typeFond, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($typeFond);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Type de fonds supprimé avec succès'
        ]);
    }
}
