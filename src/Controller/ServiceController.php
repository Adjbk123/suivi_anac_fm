<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Direction;
use App\Repository\ServiceRepository;
use App\Repository\DirectionRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/service')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('service/index.html.twig');
    }

    #[Route('/list', name: 'app_service_list', methods: ['GET'])]
    public function list(ServiceRepository $serviceRepository): JsonResponse
    {
        $services = $serviceRepository->findAllWithDirection();
        
        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'libelle' => $service->getLibelle(),
                'description' => $service->getDescription(),
                'direction' => $service->getDirection() ? $service->getDirection()->getLibelle() : '-',
                'direction_id' => $service->getDirection() ? $service->getDirection()->getId() : null,
                'isUsed' => $service->isUsed()
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/new', name: 'app_service_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, DirectionRepository $directionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['libelle']) || !isset($data['direction_id'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }
        
        $direction = $directionRepository->find($data['direction_id']);
        if (!$direction) {
            return $this->json(['success' => false, 'message' => 'Direction introuvable'], 404);
        }
        
        $service = new Service();
        $service->setLibelle($data['libelle']);
        $service->setDescription($data['description'] ?? '');
        $service->setDirection($direction);
        
        $entityManager->persist($service);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Service créé avec succès']);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    public function show(Service $service): JsonResponse
    {
        return $this->json([
            'id' => $service->getId(),
            'libelle' => $service->getLibelle(),
            'description' => $service->getDescription(),
            'direction_id' => $service->getDirection() ? $service->getDirection()->getId() : null
        ]);
    }

    #[Route('/{id}', name: 'app_service_edit', methods: ['PUT'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager, DirectionRepository $directionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['libelle']) || !isset($data['direction_id'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }
        
        $direction = $directionRepository->find($data['direction_id']);
        if (!$direction) {
            return $this->json(['success' => false, 'message' => 'Direction introuvable'], 404);
        }
        
        $service->setLibelle($data['libelle']);
        $service->setDescription($data['description'] ?? '');
        $service->setDirection($direction);
        
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Service modifié avec succès']);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['DELETE'])]
    public function delete(Service $service, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier si le service est utilisé
        if ($service->isUsed()) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce service car il est utilisé dans des missions ou formations'
            ], 400);
        }
        
        $entityManager->remove($service);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Service supprimé avec succès']);
    }

    #[Route('/directions/list', name: 'app_service_directions_list', methods: ['GET'])]
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

    #[Route('/export/pdf', name: 'app_service_export_pdf', methods: ['GET'])]
    public function exportPdf(ServiceRepository $serviceRepository, PdfService $pdfService): Response
    {
        $services = $serviceRepository->findAllWithDirection();
        
        // Préparer les données pour le PDF
        $headers = ['ID', 'Libellé', 'Description', 'Direction'];
        $data = [];
        
        foreach ($services as $service) {
            $data[] = [
                $service->getId(),
                $service->getLibelle(),
                $service->getDescription() ?: '-',
                $service->getDirection() ? $service->getDirection()->getLibelle() : '-'
            ];
        }
        
        // Générer le PDF
        $pdfContent = $pdfService->generateTablePdf(
            'Liste des Services',
            $headers,
            $data,
            'services_anac_benin.pdf'
        );
        
        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'services_anac_benin.pdf'
        ));
        
        return $response;
    }
}
