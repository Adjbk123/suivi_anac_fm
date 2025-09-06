<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RoleService
{
    public function __construct(
        private Security $security
    ) {}

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->security->isGranted($role);
    }

    /**
     * Vérifie si l'utilisateur est un éditeur
     */
    public function isEditeur(): bool
    {
        return $this->hasRole('ROLE_EDITEUR');
    }

    /**
     * Vérifie si l'utilisateur est un directeur
     */
    public function isDirecteur(): bool
    {
        return $this->hasRole('ROLE_DIRECTEUR');
    }

    /**
     * Vérifie si l'utilisateur est un admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Vérifie si l'utilisateur peut créer des formations
     */
    public function canCreateFormation(): bool
    {
        return $this->isEditeur() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut modifier des formations
     */
    public function canEditFormation(): bool
    {
        return $this->isEditeur() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut valider des formations
     */
    public function canValidateFormation(): bool
    {
        return $this->isEditeur() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut supprimer des formations
     */
    public function canDeleteFormation(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut gérer les utilisateurs
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut gérer les paramètres
     */
    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut voir les statistiques
     */
    public function canViewStatistics(): bool
    {
        return $this->isDirecteur() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut voir les rapports
     */
    public function canViewReports(): bool
    {
        return $this->isDirecteur() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut voir les filtres avancés
     */
    public function canViewAdvancedFilters(): bool
    {
        return $this->isDirecteur() || $this->isAdmin();
    }

    /**
     * Lance une exception si l'utilisateur n'a pas le rôle requis
     */
    public function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            throw new AccessDeniedException('Accès refusé. Rôle requis : ' . $role);
        }
    }

    /**
     * Lance une exception si l'utilisateur ne peut pas créer de formations
     */
    public function requireCanCreateFormation(): void
    {
        if (!$this->canCreateFormation()) {
            throw new AccessDeniedException('Accès refusé. Vous ne pouvez pas créer de formations.');
        }
    }

    /**
     * Lance une exception si l'utilisateur ne peut pas gérer les utilisateurs
     */
    public function requireCanManageUsers(): void
    {
        if (!$this->canManageUsers()) {
            throw new AccessDeniedException('Accès refusé. Vous ne pouvez pas gérer les utilisateurs.');
        }
    }

    /**
     * Lance une exception si l'utilisateur ne peut pas gérer les paramètres
     */
    public function requireCanManageSettings(): void
    {
        if (!$this->canManageSettings()) {
            throw new AccessDeniedException('Accès refusé. Vous ne pouvez pas gérer les paramètres.');
        }
    }

    /**
     * Récupère l'utilisateur connecté
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Récupère le rôle le plus élevé de l'utilisateur
     */
    public function getHighestRole(): string
    {
        if ($this->isAdmin()) {
            return 'ROLE_ADMIN';
        }
        if ($this->isDirecteur()) {
            return 'ROLE_DIRECTEUR';
        }
        if ($this->isEditeur()) {
            return 'ROLE_EDITEUR';
        }
        return 'ROLE_USER';
    }
}
