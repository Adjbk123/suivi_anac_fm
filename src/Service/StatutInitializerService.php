<?php

namespace App\Service;

use App\Entity\StatutActivite;
use App\Entity\StatutParticipation;
use App\Repository\StatutActiviteRepository;
use App\Repository\StatutParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StatutInitializerService
{
    private const STATUTS_ACTIVITE = [
        [
            'code' => 'prevue_non_executee',
            'libelle' => 'Prévue non exécutée',
            'description' => 'La formation/mission était planifiée, mais jamais réalisée',
            'couleur' => 'warning'
        ],
        [
            'code' => 'prevue_executee',
            'libelle' => 'Prévue exécutée',
            'description' => 'La formation/mission a bien eu lieu comme prévu',
            'couleur' => 'success'
        ],
        [
            'code' => 'non_prevue_executee',
            'libelle' => 'Non prévue exécutée',
            'description' => 'La formation/mission n\'était pas prévue dans le planning initial, mais a quand même été réalisée',
            'couleur' => 'info'
        ],
        [
            'code' => 'annulee',
            'libelle' => 'Annulée',
            'description' => 'La formation/mission était planifiée mais a été officiellement annulée',
            'couleur' => 'danger'
        ]
    ];

    private const STATUTS_PARTICIPATION = [
        [
            'code' => 'inscrit',
            'libelle' => 'Inscrit',
            'description' => 'L\'utilisateur était prévu comme participant',
            'couleur' => 'primary'
        ],
        [
            'code' => 'participe',
            'libelle' => 'Participe',
            'description' => 'L\'utilisateur a effectivement participé',
            'couleur' => 'success'
        ],
        [
            'code' => 'absent',
            'libelle' => 'Absent',
            'description' => 'L\'utilisateur était prévu mais ne s\'est pas présenté',
            'couleur' => 'danger'
        ],
        [
            'code' => 'non_prevus_participe',
            'libelle' => 'Non prévu participe',
            'description' => 'Un utilisateur qui n\'était pas prévu mais a quand même participé',
            'couleur' => 'info'
        ]
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatutActiviteRepository $statutActiviteRepository,
        private StatutParticipationRepository $statutParticipationRepository,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Initialise tous les statuts s'ils n'existent pas déjà
     */
    public function initializeStatuts(): void
    {
        try {
            $this->initializeStatutsActivite();
            $this->initializeStatutsParticipation();
            $this->entityManager->flush();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'initialisation des statuts: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Initialise les statuts d'activité
     */
    private function initializeStatutsActivite(): void
    {
        foreach (self::STATUTS_ACTIVITE as $statutData) {
            $existingStatut = $this->statutActiviteRepository->findOneBy(['code' => $statutData['code']]);
            
            if (!$existingStatut) {
                $statut = new StatutActivite();
                $statut->setCode($statutData['code']);
                $statut->setLibelle($statutData['libelle']);
                $statut->setDescription($statutData['description']);
                $statut->setCouleur($statutData['couleur']);
                
                $this->entityManager->persist($statut);
                
                if ($this->logger) {
                    $this->logger->info(sprintf('Statut d\'activité créé: %s', $statutData['code']));
                }
            } else {
                // Mise à jour des données si nécessaire
                $updated = false;
                if ($existingStatut->getLibelle() !== $statutData['libelle']) {
                    $existingStatut->setLibelle($statutData['libelle']);
                    $updated = true;
                }
                if ($existingStatut->getDescription() !== $statutData['description']) {
                    $existingStatut->setDescription($statutData['description']);
                    $updated = true;
                }
                if ($existingStatut->getCouleur() !== $statutData['couleur']) {
                    $existingStatut->setCouleur($statutData['couleur']);
                    $updated = true;
                }
                
                if ($updated) {
                    $this->entityManager->persist($existingStatut);
                    if ($this->logger) {
                        $this->logger->info(sprintf('Statut d\'activité mis à jour: %s', $statutData['code']));
                    }
                }
            }
        }
    }

    /**
     * Initialise les statuts de participation
     */
    private function initializeStatutsParticipation(): void
    {
        foreach (self::STATUTS_PARTICIPATION as $statutData) {
            $existingStatut = $this->statutParticipationRepository->findOneBy(['code' => $statutData['code']]);
            
            if (!$existingStatut) {
                $statut = new StatutParticipation();
                $statut->setCode($statutData['code']);
                $statut->setLibelle($statutData['libelle']);
                $statut->setDescription($statutData['description']);
                $statut->setCouleur($statutData['couleur']);
                
                $this->entityManager->persist($statut);
                
                if ($this->logger) {
                    $this->logger->info(sprintf('Statut de participation créé: %s', $statutData['code']));
                }
            } else {
                // Mise à jour des données si nécessaire
                $updated = false;
                if ($existingStatut->getLibelle() !== $statutData['libelle']) {
                    $existingStatut->setLibelle($statutData['libelle']);
                    $updated = true;
                }
                if ($existingStatut->getDescription() !== $statutData['description']) {
                    $existingStatut->setDescription($statutData['description']);
                    $updated = true;
                }
                if ($existingStatut->getCouleur() !== $statutData['couleur']) {
                    $existingStatut->setCouleur($statutData['couleur']);
                    $updated = true;
                }
                
                if ($updated) {
                    $this->entityManager->persist($existingStatut);
                    if ($this->logger) {
                        $this->logger->info(sprintf('Statut de participation mis à jour: %s', $statutData['code']));
                    }
                }
            }
        }
    }
}

