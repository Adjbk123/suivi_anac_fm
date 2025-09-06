<?php

namespace App\Controller;

use App\Entity\Direction;
use App\Repository\DirectionRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/direction')]
class DirectionController extends AbstractController
{
    #[Route('/', name: 'app_direction_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('direction/index.html.twig');
    }

    #[Route('/list', name: 'app_direction_list', methods: ['GET'])]
    public function list(DirectionRepository $directionRepository): JsonResponse
    {
        $directions = $directionRepository->findAll();
        $data = [];
        
        foreach ($directions as $direction) {
            $data[] = [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle(),
                'description' => $direction->getDescription(),
                'services_count' => $direction->getServices()->count()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_direction_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $direction = new Direction();
        $direction->setLibelle($data['libelle']);
        $direction->setDescription($data['description'] ?? null);
        
        $entityManager->persist($direction);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Direction créée avec succès',
            'direction' => [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle(),
                'description' => $direction->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_direction_show', methods: ['GET'])]
    public function show(Direction $direction): JsonResponse
    {
        return $this->json([
            'id' => $direction->getId(),
            'libelle' => $direction->getLibelle(),
            'description' => $direction->getDescription(),
            'services_count' => $direction->getServices()->count()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_direction_edit', methods: ['PUT'])]
    public function edit(Request $request, Direction $direction, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $direction->setLibelle($data['libelle']);
        $direction->setDescription($data['description'] ?? null);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Direction modifiée avec succès',
            'direction' => [
                'id' => $direction->getId(),
                'libelle' => $direction->getLibelle(),
                'description' => $direction->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_direction_delete', methods: ['DELETE'])]
    public function delete(Direction $direction, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($direction->getServices()->count() > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette direction car elle contient des services'
            ], 400);
        }
        
        $entityManager->remove($direction);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Direction supprimée avec succès'
        ]);
    }

    #[Route('/export/pdf', name: 'app_direction_export_pdf', methods: ['GET'])]
    public function exportPdf(DirectionRepository $directionRepository, PdfService $pdfService): Response
    {
        $directions = $directionRepository->findAll();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Libellé', 'Description', 'Nombre de Services'];
        $data = [];
        
        foreach ($directions as $direction) {
            $data[] = [
                $direction->getId(),
                $direction->getLibelle(),
                $direction->getDescription() ?: '-',
                $direction->getServices()->count()
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Directions',
            $headers,
            $data,
            'directions_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'directions_anac_benin.pdf'
        ));
        
        return $response;
    }
}
