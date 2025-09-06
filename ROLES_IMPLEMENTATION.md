# Système de Rôles - ANAC BENIN

## Vue d'ensemble

Ce document décrit l'implémentation du système de rôles dans l'application de suivi ANAC BENIN. Le système est basé sur une hiérarchie de rôles qui contrôle l'accès aux différentes fonctionnalités de l'application.

## Hiérarchie des Rôles

```
ROLE_USER (tous les utilisateurs connectés)
    ↓
ROLE_EDITEUR (peut créer/modifier/valider)
    ↓
ROLE_DIRECTEUR (peut voir stats/filtres + valider sa direction)
    ↓
ROLE_ADMIN (peut TOUT faire)
```

## Détail des Rôles

### ROLE_USER (Niveau 0 - Utilisateur de base)
- **Accès automatique** : Attribué à tous les utilisateurs connectés
- **Permissions** :
  - ✅ Lecture : Toutes les formations, missions, utilisateurs
  - ✅ Navigation : Tableau de bord, formations, missions
  - ❌ Création : Aucune
  - ❌ Modification : Aucune
  - ❌ Suppression : Aucune

### ROLE_EDITEUR (Niveau 1 - Utilisateur de base)
- **Permissions** :
  - ✅ **Lecture** : Toutes les formations, missions, utilisateurs
  - ✅ **Création** : Formations et missions pour **TOUS** les services et domaines
  - ✅ **Modification** : Ses propres formations/missions + toutes les autres
  - ✅ **Validation** : Peut valider **TOUTES** les formations et missions
  - ❌ **Suppression** : Aucune
  - ❌ **Gestion des utilisateurs** : Aucune
  - ❌ **Gestion des paramètres** : Aucune

### ROLE_DIRECTEUR (Niveau 2 - Responsable de service/direction)
- **Permissions** :
  - ✅ **Lecture** : Toutes les formations, missions, utilisateurs
  - ✅ **Statistiques** : Accès complet aux statistiques et rapports
  - ✅ **Filtres avancés** : Tous les filtres de recherche et d'analyse
  - ✅ **Validation** : Formations et missions de sa direction
  - ❌ **Création/Modification** : Aucune
  - ❌ **Suppression** : Aucune
  - ❌ **Gestion des utilisateurs** : Aucune

### ROLE_ADMIN (Niveau 3 - Administrateur système)
- **Permissions** :
  - ✅ **TOUT** : Lecture, création, modification, suppression
  - ✅ **Gestion des utilisateurs** : Création, modification, suppression, attribution des rôles
  - ✅ **Gestion des paramètres** : Directions, services, domaines, postes, statuts
  - ✅ **Rapports** : Tous les rapports et statistiques
  - ✅ **Filtres et statistiques** : Accès complet à tous les filtres et analyses

## Implémentation Technique

### 1. Configuration de Sécurité (`config/packages/security.yaml`)

```yaml
# Hiérarchie des rôles
role_hierarchy:
    ROLE_EDITEUR: ROLE_USER
    ROLE_DIRECTEUR: ROLE_EDITEUR
    ROLE_ADMIN: ROLE_DIRECTEUR

# Contrôles d'accès par route
access_control:
    # Routes nécessitant ROLE_EDITEUR
    - { path: /formation/create, roles: ROLE_EDITEUR }
    - { path: /formation/new, roles: ROLE_EDITEUR }
    
    # Routes nécessitant ROLE_DIRECTEUR
    - { path: /reporting, roles: ROLE_DIRECTEUR }
    
    # Routes nécessitant ROLE_ADMIN
    - { path: /admin, roles: ROLE_ADMIN }
    - { path: /user/new, roles: ROLE_ADMIN }
```

### 2. Service de Gestion des Rôles (`src/Service/RoleService.php`)

Le service centralise toute la logique de vérification des permissions :

```php
class RoleService
{
    public function isEditeur(): bool
    public function isDirecteur(): bool
    public function isAdmin(): bool
    public function canCreateFormation(): bool
    public function canManageUsers(): bool
    public function canViewStatistics(): bool
    // ... autres méthodes
}
```

### 3. Extension Twig (`src/Twig/RoleExtension.php`)

Permet d'utiliser les vérifications de rôles dans les templates :

```twig
{% if can_create_formation() %}
    <button class="btn btn-primary">Créer une formation</button>
{% endif %}

{% if can_manage_users() %}
    <a href="{{ path('app_user_index') }}">Gérer les utilisateurs</a>
{% endif %}
```

### 4. Contrôleurs Sécurisés

Utilisation des attributs `IsGranted` :

```php
#[Route('/create', name: 'app_formation_create')]
#[IsGranted('ROLE_EDITEUR')]
public function create(): Response
{
    // Seuls les éditeurs et plus peuvent accéder
}

#[Route('/{id}', name: 'app_formation_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
public function delete(Formation $formation): JsonResponse
{
    // Seuls les admins peuvent supprimer
}
```

## Utilisation

### 1. Attribution des Rôles via Commande

```bash
# Lister les rôles disponibles
php bin/console app:assign-roles --list-roles

# Attribuer un rôle à un utilisateur spécifique
php bin/console app:assign-roles --email=user@example.com --role=ROLE_EDITEUR

# Attribuer un rôle à tous les utilisateurs
php bin/console app:assign-roles --role=ROLE_EDITEUR --all-users
```

### 2. Attribution des Rôles via Interface Web

1. Se connecter en tant qu'admin
2. Aller dans "Utilisateurs"
3. Cliquer sur "Modifier les rôles" pour un utilisateur
4. Sélectionner les rôles souhaités
5. Sauvegarder

### 3. Vérification des Rôles dans le Code

```php
// Dans un contrôleur
use App\Service\RoleService;

public function someAction(RoleService $roleService): Response
{
    if ($roleService->canCreateFormation()) {
        // Logique pour créer une formation
    }
    
    if ($roleService->isAdmin()) {
        // Logique réservée aux admins
    }
}
```

### 4. Vérification des Rôles dans les Templates

```twig
{# Afficher/masquer des éléments selon les rôles #}
{% if can_create_formation() %}
    <div class="creation-panel">
        <h3>Créer une nouvelle formation</h3>
        <!-- Formulaire de création -->
    </div>
{% endif %}

{% if can_view_statistics() %}
    <div class="statistics-panel">
        <h3>Statistiques avancées</h3>
        <!-- Contenu des statistiques -->
    </div>
{% endif %}
```

## Sécurité

### 1. Vérifications Multiples

- **Niveau Route** : Attributs `IsGranted` sur les contrôleurs
- **Niveau Service** : Vérifications dans la logique métier
- **Niveau Template** : Masquage des éléments sensibles

### 2. Hiérarchie Automatique

Un utilisateur avec `ROLE_ADMIN` a automatiquement accès à tous les rôles inférieurs grâce à la hiérarchie définie dans `security.yaml`.

### 3. Validation des Données

Toutes les entrées utilisateur sont validées et les permissions sont vérifiées à chaque niveau.

## Maintenance

### 1. Ajout d'un Nouveau Rôle

1. Ajouter le rôle dans `security.yaml`
2. Mettre à jour `RoleService`
3. Mettre à jour `RoleExtension`
4. Mettre à jour les contrôleurs
5. Mettre à jour les templates

### 2. Modification des Permissions

1. Modifier la logique dans `RoleService`
2. Mettre à jour les contrôleurs si nécessaire
3. Tester les changements

### 3. Débogage

```bash
# Vérifier les rôles d'un utilisateur
php bin/console app:assign-roles --email=user@example.com --list-roles

# Vérifier la configuration de sécurité
php bin/console debug:security
```

## Tests

### 1. Test des Permissions

- Tester chaque rôle avec différents utilisateurs
- Vérifier que les restrictions d'accès fonctionnent
- Tester la hiérarchie des rôles

### 2. Test de Sécurité

- Vérifier que les utilisateurs ne peuvent pas accéder aux routes interdites
- Tester les tentatives d'accès non autorisées
- Vérifier la validation des données

## Support

Pour toute question ou problème avec le système de rôles :

1. Vérifier la configuration dans `security.yaml`
2. Consulter les logs d'erreur
3. Utiliser la commande de débogage
4. Contacter l'équipe de développement

---

**Note** : Ce système de rôles est conçu pour être flexible et extensible. Toute modification doit être testée rigoureusement avant d'être déployée en production.
