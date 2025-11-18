<?php

namespace App\EventSubscriber;

use App\Service\StatutInitializerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

class StatutInitializerSubscriber implements EventSubscriberInterface
{
    private static bool $initialized = false;

    public function __construct(
        private StatutInitializerService $statutInitializerService,
        private ?LoggerInterface $logger = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne s'exécuter qu'une seule fois par cycle de requête
        if (self::$initialized) {
            return;
        }

        // Ignorer les sous-requêtes (comme les fragments)
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            // Vérifier et créer les statuts manquants
            $this->statutInitializerService->initializeStatuts();
            self::$initialized = true;
        } catch (\Doctrine\DBAL\Exception $e) {
            // Si la base de données n'est pas encore disponible ou les tables n'existent pas, on ignore l'erreur
            // L'initialisation sera tentée à la prochaine requête
            if ($this->logger) {
                $this->logger->debug('Base de données non disponible ou tables manquantes, initialisation des statuts reportée: ' . $e->getMessage());
            }
            self::$initialized = false; // Réessayer à la prochaine requête
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer la requête
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'initialisation automatique des statuts: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
            }
            // Marquer comme initialisé pour ne pas bloquer toutes les requêtes
            self::$initialized = true;
        }
    }
}

