# Ina Zaoui — Portfolio Photo

Portfolio photographique moderne pour Ina Zaoui, présentant ses œuvres et celles de photographes invités. Application Symfony 8.1 avec gestion d'albums, médias et système d'authentification sécurisé.

## Fonctionnalités

### Front (Public)
- **Accueil** — Présentation d'Ina Zaoui
- **Portfolio** — Galerie d'images filtrable par album
- **Invités** — Liste des photographes invités avec leurs profils individuels
- **À propos** — Page de présentation personnelle

### Admin (Authentifié)
- **Gestion albums** — Créer, modifier, supprimer les albums
- **Gestion médias** — Upload d'images avec validation (MIME type, taille ≤2MB)
- **Gestion invités** — Ajouter, bloquer/débloquer, supprimer les photographes invités
- **Contrôle d'accès** — Pagination, sécurité CSRF et authentification par formulaire Symfony

## Stack technique

| Technologie | Version |
|------------|---------|
| Symfony | 8.1 stable (LTS actuelle : 7.4) |
| PHP | 8.4 |
| PostgreSQL | 16 (Docker) |
| Bootstrap | 5.3.3 |
| Twig | 3.x |
| Doctrine ORM | 3.x |

## Installation

### Prérequis
- macOS avec Homebrew (ou système équivalent)
- Docker Desktop
- Git

### Setup initial

1. **Cloner le repo**
   ```bash
   git clone https://github.com/Corvaxx117/OCR-Factorisation---Ina-Zaoui.git
   cd OCR-Factorisation---Ina-Zaoui
   ```

2. **Configurer l'environnement**
   ```bash
   cp .env .env.local
   ```
   
   Éditer `.env.local` :
   ```
   DATABASE_URL="postgresql://postgres:postgres@127.0.0.1:5432/ina_zaoui?serverVersion=16&charset=utf8"
   ```

3. **Lancer PostgreSQL en Docker**
   ```bash
   docker run --name postgres-dev -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=ina_zaoui -p 5432:5432 -d postgres:16
   ```

4. **Installer les dépendances**
   ```bash
   symfony composer install
   ```

5. **Créer la BDD et appliquer les migrations**
   ```bash
   symfony console doctrine:database:create
   symfony console doctrine:migrations:migrate --no-interaction
   ```

6. **Importer les données (optionnel)**
   ```bash
   docker exec -i postgres-dev psql -U postgres -d ina_zaoui < backup/album.sql
   docker exec -i postgres-dev psql -U postgres -d ina_zaoui < backup/user.sql
   docker exec -i postgres-dev psql -U postgres -d ina_zaoui < backup/media.sql
   cp backup/public/uploads/* public/uploads/
   ```

7. **Lancer le serveur**
   ```bash
   symfony server:start
   ```

   Accéder à : **https://127.0.0.1:8000**

### Avec ou sans le binaire Symfony

Toutes les commandes de ce README utilisent le binaire `symfony`, qui sélectionne
automatiquement la version de PHP fixée par le fichier `.php-version`. Elles fonctionnent
aussi sans lui, à condition que le PHP du système soit en 8.4.

| Avec le binaire Symfony | Sans le binaire |
|---|---|
| `symfony composer <commande>` | `composer <commande>` |
| `symfony php <fichier>` | `php <fichier>` |
| `symfony console <commande>` | `php bin/console <commande>` |
| `symfony server:start` | `php -S 127.0.0.1:8000 -t public/` |

## Identifiants de test

### Administrateur
- **Email** : `ina@zaoui.com`
- **Mot de passe** : `admin123`
- **Rôle** : `ROLE_ADMIN`
- **Accès** : Gestion complète (albums, médias, invités)

### Invité (Photographe)
- **Email** : `invite+3@example.com` (ou autres `invite+N@example.com`)
- **Mot de passe** : `admin123`
- **Rôle** : `ROLE_USER`
- **Accès** : Voir/gérer seulement ses propres médias

## Architecture

### Single Action Controllers

Chaque contrôleur est une classe dédiée à une seule route et expose une unique méthode
`__invoke()`. Le nom de la classe décrit l'action (`PortfolioAction`, `MediaDeleteAction`)
et les dépendances sont injectées dans la signature de l'action plutôt que dans un
constructeur partagé entre plusieurs routes.

```php
// src/Controller/Front/PortfolioAction.php
#[Route(path: '/portfolio/{id?}', name: 'portfolio')]
public function __invoke(AlbumRepository $albums, MediaRepository $medias, ?int $id = null): Response
```

Ce découpage remplace les contrôleurs fourre-tout de la version initiale. Chaque classe
n'a plus qu'une seule raison de changer, les dépendances déclarées sont exactement celles
que l'action utilise, et une route se lit sans avoir à identifier quelle méthode lui
correspond.

### Répertoires clés
```
src/
├── Controller/
│   ├── Admin/
│   │   ├── Album/     # CRUD albums
│   │   ├── Guest/     # CRUD invités
│   │   ├── Media/     # CRUD médias
│   │   └── Security/  # Login/Logout
│   └── Front/         # Pages publiques
├── Entity/            # Entités Doctrine (User, Album, Media)
├── Form/              # Formulaires Symfony
├── Pagination/         # Résultat paginé réutilisable (20 éléments/page)
├── Repository/        # Requêtes BDD
├── DataFixtures/       # Jeu de données réservé à l'environnement de test
└── Security/          # UserChecker (bloque invités inactifs)

src/Service/
├── FileUploadService.php        # Upload et suppression des fichiers médias
└── GuestRegistrationService.php # Hash du mot de passe et création des invités

templates/
├── base.html.twig     # Layout principal
├── front.html.twig    # Layout site public
├── admin.html.twig    # Layout admin
├── admin/             # Templates admin
├── front/             # Templates publiques
└── _flashes.html.twig # Affichage messages (success/error)
```

## Performance


Les mesures detaillees et la comparaison avant/apres de la page Invites sont
disponibles dans [docs/RAPPORT_PERFORMANCE.md](docs/RAPPORT_PERFORMANCE.md).

## Sécurité

- Hachage des mots de passe géré par Symfony
- Protection CSRF sur tous les POST
- Contrôle d'accès `#[IsGranted('ROLE_*')]`
- Validation des fichiers uploadés (MIME + taille)
- `UserChecker` — bloque les comptes inactifs
- Suppression en cascade des médias orphelins



Les commandes de tests, de couverture et d'analyse statique sont décrites dans le
[guide de contribution](CONTRIBUTING.md).

## Points clés du projet

### Corrections effectuées
- Migration Symfony 5.4 → 8.1 (avec corrections de breaking changes)
- PHP 8.2 → PHP 8.4
- Single Action Controllers (découpage des contrôleurs)
- Injection de dépendances (plus de `getDoctrine()`)
- Attributs PHP 8 (`#[Route]`, `#[ORM\*]`)
- Validation complète des entités
- Tests de sécurité (CSRF, authentification, autorisation)

### N+1 queries résolu
- **Avant** : 102 requêtes / 181ms sur `/guests`
- **Après** : 2 requêtes / 25ms (LEFT JOIN fetch)

### Pagination corrigée
- Les listes Médias et Invités affichent au maximum 20 éléments par page
- Les non-admins ne voient que leurs propres médias
- La pagination est basée sur le vrai nombre de résultats, avec un partial Twig réutilisable

## Intégration continue

GitHub Actions exécute automatiquement la pipeline sur chaque push et Pull
Request vers `develop` ou `main`. Elle prépare PostgreSQL 16, applique les
migrations et fixtures de test, puis lance PHPUnit, PHPStan niveau 8 et PHP CS
Fixer. PHPUnit génère aussi `var/coverage` et `var/coverage.xml`, publiés comme
artefact `coverage-report` dans chaque exécution. Le workflow peut aussi être
exécuté manuellement depuis l'onglet Actions.

## Contribution

Le workflow Git, les conventions de commit, les standards de code et les exigences de
qualité sont décrits dans le [guide de contribution](CONTRIBUTING.md).

## Licence

© 2024 Ina Zaoui. Tous droits réservés.

## Support

Pour toute question ou issue, ouvrir une issue GitHub ou contacter l'équipe de développement.