<?php

namespace App\Controller;

use App\Repository\FormationSessionRepository;
use App\Repository\MissionSessionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/api/search/suggestions', name: 'app_search_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all'); // all, formations, missions, users
        
        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }
        
        $suggestions = [];
        
        // Recherche dans les sessions de formation
        if (in_array($type, ['all', 'formations'])) {
            $formationSessions = $formationSessionRepository->searchSuggestions($query);
            foreach ($formationSessions as $session) {
                $suggestions[] = [
                    'type' => 'formation',
                    'id' => $session['id'],
                    'title' => $session['titre'],
                    'description' => $session['description'] ?: '',
                    'direction' => $session['direction_name'] ?: '',
                    'status' => $session['status_name'] ?: '',
                    'date' => $session['datePrevueDebut'] ? (is_string($session['datePrevueDebut']) ? $session['datePrevueDebut'] : $session['datePrevueDebut']->format('d/m/Y')) : '',
                    'icon' => 'fa-graduation-cap',
                    'color' => 'primary',
                    'url' => $this->generateUrl('app_formation_show', ['id' => $session['id']])
                ];
            }
        }
        
        // Recherche dans les missions
        if (in_array($type, ['all', 'missions'])) {
            $missions = $missionSessionRepository->searchSuggestions($query);
            foreach ($missions as $mission) {
                $suggestions[] = [
                    'type' => 'mission',
                    'id' => $mission['id'],
                    'title' => $mission['titre'],
                    'description' => $mission['description'] ?: '',
                    'direction' => $mission['direction_name'] ?: '',
                    'status' => $mission['status_name'] ?: '',
                    'date' => $mission['datePrevueDebut'] ? (is_string($mission['datePrevueDebut']) ? $mission['datePrevueDebut'] : $mission['datePrevueDebut']->format('d/m/Y')) : '',
                    'icon' => 'fa-plane',
                    'color' => 'success',
                    'url' => $this->generateUrl('app_mission_show', ['id' => $mission['id']])
                ];
            }
        }
        
        // Recherche dans les utilisateurs
        if (in_array($type, ['all', 'users'])) {
            $users = $userRepository->searchSuggestions($query);
            foreach ($users as $user) {
                $suggestions[] = [
                    'type' => 'user',
                    'id' => $user['id'],
                    'title' => $user['nom'] . ' ' . $user['prenom'],
                    'description' => $user['email'] ?: '',
                    'service' => $user['service_name'] ?: '',
                    'direction' => $user['direction_name'] ?: '',
                    'icon' => 'fa-user',
                    'color' => 'info',
                    'url' => $this->generateUrl('app_user_show_page', ['id' => $user['id']])
                ];
            }
        }
        
        // Limiter à 10 suggestions maximum
        $suggestions = array_slice($suggestions, 0, 10);
        
        return new JsonResponse(['suggestions' => $suggestions]);
    }
    
    #[Route('/api/search/global', name: 'app_search_global', methods: ['GET'])]
    public function globalSearch(Request $request, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }
        
        $results = [
            'formations' => $formationSessionRepository->searchGlobal($query),
            'missions' => $missionSessionRepository->searchGlobal($query),
            'users' => $userRepository->searchGlobal($query)
        ];
        
        return new JsonResponse(['results' => $results]);
    }

    #[Route('/search', name: 'app_search_results', methods: ['GET'])]
    public function searchResults(Request $request, FormationSessionRepository $formationSessionRepository, MissionSessionRepository $missionSessionRepository, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->redirectToRoute('app_dashboard');
        }
        
        $results = [
            'formations' => [],
            'missions' => [],
            'users' => []
        ];
        
        if (strlen($query) >= 2) {
            $results['formations'] = $formationSessionRepository->searchGlobal($query);
            $results['missions'] = $missionSessionRepository->searchGlobal($query);
            $results['users'] = $userRepository->searchGlobal($query);
        }
        
        return $this->render('search/results.html.twig', [
            'query' => $query,
            'results' => $results,
            'totalResults' => count($results['formations']) + count($results['missions']) + count($results['users'])
        ]);
    }
}
