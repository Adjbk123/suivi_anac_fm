<?php

namespace App\Twig;

use App\Service\RoleService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleExtension extends AbstractExtension
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_role', [$this->roleService, 'hasRole']),
            new TwigFunction('is_editeur', [$this->roleService, 'isEditeur']),
            new TwigFunction('is_directeur', [$this->roleService, 'isDirecteur']),
            new TwigFunction('is_admin', [$this->roleService, 'isAdmin']),
            new TwigFunction('can_create_formation', [$this->roleService, 'canCreateFormation']),
            new TwigFunction('can_edit_formation', [$this->roleService, 'canEditFormation']),
            new TwigFunction('can_validate_formation', [$this->roleService, 'canValidateFormation']),
            new TwigFunction('can_delete_formation', [$this->roleService, 'canDeleteFormation']),
            new TwigFunction('can_manage_users', [$this->roleService, 'canManageUsers']),
            new TwigFunction('can_manage_settings', [$this->roleService, 'canManageSettings']),
            new TwigFunction('can_view_statistics', [$this->roleService, 'canViewStatistics']),
            new TwigFunction('can_view_reports', [$this->roleService, 'canViewReports']),
            new TwigFunction('can_view_advanced_filters', [$this->roleService, 'canViewAdvancedFilters']),
            new TwigFunction('get_highest_role', [$this->roleService, 'getHighestRole']),
        ];
    }
}
