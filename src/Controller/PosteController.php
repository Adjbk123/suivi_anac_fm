<?php

namespace App\Controller;

use App\Entity\Poste;
use App\Repository\PosteRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/poste')]
class PosteController extends AbstractController
{
    #[Route('/', name: 'app_poste_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('poste/index.html.twig');
    }

    #[Route('/list', name: 'app_poste_list', methods: ['GET'])]
    public function list(PosteRepository $posteRepository): JsonResponse
    {
        $postes = $posteRepository->findAll();
        $data = [];
        
        foreach ($postes as $poste) {
            $data[] = [
                'id' => $poste->getId(),
                'libelle' => $poste->getLibelle(),
                'description' => $poste->getDescription(),
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_poste_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['libelle'])) {
            return $this->json([
                'success' => false,
                'message' => 'Le libellé est obligatoire'
            ]);
        }
        
        $poste = new Poste();
        $poste->setLibelle($data['libelle']);
        $poste->setDescription($data['description'] ?? null);
        
        $entityManager->persist($poste);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Poste créé avec succès !',
            'data' => [
                'id' => $poste->getId(),
                'libelle' => $poste->getLibelle(),
                'description' => $poste->getDescription(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_poste_show', methods: ['GET'])]
    public function show(Poste $poste): JsonResponse
    {
        return $this->json([
            'id' => $poste->getId(),
            'libelle' => $poste->getLibelle(),
            'description' => $poste->getDescription(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_poste_edit', methods: ['POST'])]
    public function edit(Request $request, Poste $poste, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['libelle'])) {
            return $this->json([
                'success' => false,
                'message' => 'Le libellé est obligatoire'
            ]);
        }
        
        $poste->setLibelle($data['libelle']);
        $poste->setDescription($data['description'] ?? null);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Poste modifié avec succès !',
            'data' => [
                'id' => $poste->getId(),
                'libelle' => $poste->getLibelle(),
                'description' => $poste->getDescription(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_poste_delete', methods: ['DELETE'])]
    public function delete(Poste $poste, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($poste);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Poste supprimé avec succès !'
        ]);
    }

    #[Route('/export/pdf', name: 'app_poste_export_pdf', methods: ['GET'])]
    public function exportPdf(PosteRepository $posteRepository, PdfService $pdfService): Response
    {
        $postes = $posteRepository->findAll();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Libellé', 'Description'];
        $data = [];
        
        foreach ($postes as $poste) {
            $data[] = [
                $poste->getId(),
                $poste->getLibelle(),
                $poste->getDescription() ?: '-'
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Postes',
            $headers,
            $data,
            'postes_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'postes_anac_benin.pdf'
        ));
        
        return $response;
    }
}
