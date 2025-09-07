<?php

namespace App\Controller;

use App\Entity\Domaine;
use App\Repository\DomaineRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/domaine')]
class DomaineController extends AbstractController
{
    #[Route('/', name: 'app_domaine_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('domaine/index.html.twig');
    }

    #[Route('/list', name: 'app_domaine_list', methods: ['GET'])]
    public function list(DomaineRepository $domaineRepository): JsonResponse
    {
        $domaines = $domaineRepository->findAll();
        $data = [];
        
        foreach ($domaines as $domaine) {
            $data[] = [
                'id' => $domaine->getId(),
                'libelle' => $domaine->getLibelle(),
                'description' => $domaine->getDescription(),
                'isUsed' => $domaine->isUsed()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_domaine_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $domaine = new Domaine();
        $domaine->setLibelle($data['libelle']);
        $domaine->setDescription($data['description'] ?? null);
        
        $entityManager->persist($domaine);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Domaine créé avec succès',
            'domaine' => [
                'id' => $domaine->getId(),
                'libelle' => $domaine->getLibelle(),
                'description' => $domaine->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_domaine_show', methods: ['GET'])]
    public function show(Domaine $domaine): JsonResponse
    {
        return $this->json([
            'id' => $domaine->getId(),
            'libelle' => $domaine->getLibelle(),
            'description' => $domaine->getDescription()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_domaine_edit', methods: ['PUT'])]
    public function edit(Request $request, Domaine $domaine, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $domaine->setLibelle($data['libelle']);
        $domaine->setDescription($data['description'] ?? null);
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Domaine modifié avec succès',
            'domaine' => [
                'id' => $domaine->getId(),
                'libelle' => $domaine->getLibelle(),
                'description' => $domaine->getDescription()
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_domaine_delete', methods: ['DELETE'])]
    public function delete(Domaine $domaine, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier si le domaine est utilisé
        if ($domaine->isUsed()) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce domaine car il est utilisé par des utilisateurs'
            ], 400);
        }
        
        $entityManager->remove($domaine);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Domaine supprimé avec succès'
        ]);
    }

    #[Route('/export/pdf', name: 'app_domaine_export_pdf', methods: ['GET'])]
    public function exportPdf(DomaineRepository $domaineRepository, PdfService $pdfService): Response
    {
        $domaines = $domaineRepository->findAll();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Libellé', 'Description'];
        $data = [];
        
        foreach ($domaines as $domaine) {
            $data[] = [
                $domaine->getId(),
                $domaine->getLibelle(),
                $domaine->getDescription() ?: '-'
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Domaines',
            $headers,
            $data,
            'domaines_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'domaines_anac_benin.pdf'
        ));
        
        return $response;
    }
}
