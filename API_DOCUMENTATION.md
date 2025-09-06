# Documentation des APIs

Ce document décrit toutes les APIs disponibles dans l'application ANAC BENIN.

## Base URL
Toutes les APIs sont accessibles via le préfixe `/api`

## Endpoints disponibles

### 1. Directions
**GET** `/api/directions`

Retourne la liste de toutes les directions.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Direction Générale",
    "description": "Description de la direction"
  }
]
```

### 2. Services
**GET** `/api/services`

Retourne la liste de tous les services avec leurs directions.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Service Informatique",
    "description": "Description du service",
    "direction": {
      "id": 1,
      "libelle": "Direction Générale"
    }
  }
]
```

**GET** `/api/services/{directionId}`

Retourne les services d'une direction spécifique.

### 3. Domaines
**GET** `/api/domaines`

Retourne la liste de tous les domaines.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Sécurité",
    "description": "Description du domaine"
  }
]
```

### 4. Types de Fonds
**GET** `/api/fonds`

Retourne la liste de tous les types de fonds.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Interne",
    "description": "Fonds internes"
  }
]
```

### 5. Postes
**GET** `/api/postes`

Retourne la liste de tous les postes.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Directeur",
    "description": "Description du poste"
  }
]
```

### 6. Utilisateurs
**GET** `/api/users`

Retourne la liste de tous les utilisateurs avec leurs relations.

**Réponse :**
```json
[
  {
    "id": 1,
    "nom": "Doe",
    "prenom": "John",
    "email": "john.doe@example.com",
    "matricule": "EMP001",
    "service": {
      "id": 1,
      "libelle": "Service Informatique"
    },
    "domaine": {
      "id": 1,
      "libelle": "Sécurité"
    },
    "poste": {
      "id": 1,
      "libelle": "Directeur"
    }
  }
]
```

**GET** `/api/users-by-service/{serviceId}`

Retourne les utilisateurs d'un service spécifique.

### 7. Catégories de Dépenses
**GET** `/api/categories-depenses`

Retourne la liste de toutes les catégories de dépenses.

**Réponse :**
```json
[
  {
    "id": 1,
    "libelle": "Transport",
    "description": "Dépenses de transport"
  }
]
```

## Utilisation dans les templates

### Exemple JavaScript
```javascript
// Charger les services
fetch('/api/services')
    .then(response => response.json())
    .then(data => {
        // Traitement des données
        console.log(data);
    })
    .catch(error => console.error('Erreur:', error));

// Charger les utilisateurs d'un service
fetch('/api/users-by-service/1')
    .then(response => response.json())
    .then(data => {
        // Traitement des données
        console.log(data);
    })
    .catch(error => console.error('Erreur:', error));
```

### Exemple avec jQuery (si disponible)
```javascript
$.get('/api/services', function(data) {
    // Traitement des données
    console.log(data);
}).fail(function(error) {
    console.error('Erreur:', error);
});
```

## Codes de statut HTTP

- **200** : Succès
- **400** : Requête invalide
- **404** : Ressource non trouvée
- **500** : Erreur serveur interne

## Notes importantes

1. Toutes les APIs retournent des données au format JSON
2. Les APIs sont accessibles sans authentification (à adapter selon vos besoins)
3. Les données sont paginées automatiquement par Doctrine
4. Les relations sont chargées de manière optimisée avec les méthodes `findAllWithRelations()`

## Évolutions futures

- Ajout de filtres et de tri
- Pagination personnalisée
- Authentification et autorisation
- Cache des réponses
- Documentation Swagger/OpenAPI
