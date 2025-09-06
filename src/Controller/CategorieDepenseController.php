<?php

namespace App\Controller;

use App\Entity\CategorieDepense;
use App\Repository\CategorieDepenseRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categorie-depense')]
class CategorieDepenseController extends AbstractController
{
    #[Route('/', name: 'app_categoriedepense_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('categorie_depense/index.html.twig');
    }

    #[Route('/list', name: 'app_categoriedepense_list', methods: ['GET'])]
    public function list(CategorieDepenseRepository $categorieDepenseRepository): JsonResponse
    {
        $categorieDepenses = $categorieDepenseRepository->findAll();
        $data = [];
        
        foreach ($categorieDepenses as $categorieDepense) {
            $data[] = [
                'id' => $categorieDepense->getId(),
                'libelle' => $categorieDepense->getLibelle(),
                'description' => $categorieDepense->getDescription()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_categoriedepense_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $categorieDepense = new CategorieDepense();
        $categorieDepense->setLibelle($data['libelle']);
        $categorieDepense->setDescription($data['description'] ?? null);
        
        $entityManager->persist($categorieDepense);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Catégorie de dépense créée avec succès',
            'categorieDepense' => [
                'id' => $categorieDepense->getId(),
                'libelle' => $categorieDepense->getLibelle(),
                'description' => $categorieDepense->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_categoriedepense_show', methods: ['GET'])]
    public function show(CategorieDepense $categorieDepense): JsonResponse
    {
        return $this->json([
            'id' => $categorieDepense->getId(),
            'libelle' => $categorieDepense->getLibelle(),
            'description' => $categorieDepense->getDescription()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categoriedepense_edit', methods: ['PUT'])]
    public function edit(Request $request, CategorieDepense $categorieDepense, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $categorieDepense->setLibelle($data['libelle']);
        $categorieDepense->setDescription($data['description'] ?? null);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Catégorie de dépense modifiée avec succès',
            'categorieDepense' => [
                'id' => $categorieDepense->getId(),
                'libelle' => $categorieDepense->getLibelle(),
                'description' => $categorieDepense->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_categoriedepense_delete', methods: ['DELETE'])]
    public function delete(CategorieDepense $categorieDepense, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($categorieDepense);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Catégorie de dépense supprimée avec succès'
        ]);
    }

    #[Route('/export/pdf', name: 'app_categoriedepense_export_pdf', methods: ['GET'])]
    public function exportPdf(CategorieDepenseRepository $categorieDepenseRepository, PdfService $pdfService): Response
    {
        $categorieDepenses = $categorieDepenseRepository->findAll();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Libellé', 'Description'];
        $data = [];
        
        foreach ($categorieDepenses as $categorieDepense) {
            $data[] = [
                $categorieDepense->getId(),
                $categorieDepense->getLibelle(),
                $categorieDepense->getDescription() ?: '-'
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Catégories de Dépenses',
            $headers,
            $data,
            'categories_depenses_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'categories_depenses_anac_benin.pdf'
        ));
        
        return $response;
    }
}
