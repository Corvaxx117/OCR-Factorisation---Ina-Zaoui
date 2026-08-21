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
- **Contrôle d'accès** — Pagination, sécurité CSRF, authentification par token

## 🛠 Stack Technique

| Technologie | Version |
|------------|---------|
| Symfony | 8.1 LTS |
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
   git clone https://github.com/YOUR_USERNAME/factorisation.git
   cd factorisation
   ```

2. **Configurer l'environnement**
   ```bash
   cp .env .env.local
   ```
   
   Éditer `.env.local` :
   ```
   DATABASE_URL="postgresql://postgres:password@localhost:5432/ina_zaoui"
   ```

3. **Lancer PostgreSQL en Docker**
   ```bash
   docker run --name postgres-dev -e POSTGRES_PASSWORD=password -e POSTGRES_DB=ina_zaoui -p 5432:5432 -d postgres:16
   ```

4. **Installer les dépendances**
   ```bash
   composer install
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
   docker exec -i postgres-dev psql -U postgres -d ina_zaoui < backup/user.sql (adapté)
   docker exec -i postgres-dev psql -U postgres -d ina_zaoui < backup/media.sql
   cp backup/public/uploads/* public/uploads/
   ```

7. **Lancer le serveur**
   ```bash
   symfony server:start
   ```

   Accéder à : **https://127.0.0.1:8001**

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

## 📐 Architecture

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
├── Repository/        # Requêtes BDD
└── Security/          # UserChecker (bloque invités inactifs)

templates/
├── base.html.twig     # Layout principal
├── front.html.twig    # Layout site public
├── admin.html.twig    # Layout admin
├── admin/             # Templates admin
├── front/             # Templates publiques
└── _flashes.html.twig # Affichage messages (success/error)
```

### Entités
- **User** — Représente admin et invités
  - Champs : `id`, `email`, `password`, `name`, `admin`, `active`, `roles`, `medias`
  - Sécurité : hash bcrypt, `active` bloque la connexion
  
- **Album** — Groupement de médias
  - Relation : `OneToMany → Media`
  - Validation : nom requis, max 255 caractères
  
- **Media** — Images uploadées
  - Champs : `path`, `title`, `user_id`, `album_id`
  - Validation : MIME JPEG/PNG/GIF/WebP, taille ≤ 2MB
  - Suppression : cascade (supprime fichier physique aussi)

## Performance

| Page | Requêtes BD | Temps SQL | Optimisation |
|------|-----------|----------|-------------|
| `/guests` | 2 | 25ms | `LEFT JOIN` (avant : 102 req / 181ms) |
| `/admin/media` | Pagérisé | Dépend filtre | Filtrage utilisateur inclus |
| `/portfolio` | 1-2 | Rapide | Album optionnel |

**Audit Lighthouse** (dev)
- Performance : 95
- Accessibilité : 96
- Bonnes pratiques : 96
- SEO : 54 (noindex en dev, sera 90+ en prod)

## Sécurité

✅ **Implémentée**
- ✅ Hash bcrypt de mots de passe
- ✅ Protection CSRF sur tous les POST
- ✅ Contrôle d'accès `#[IsGranted('ROLE_*')]`
- ✅ Validation fichiers uploadés (MIME + taille)
- ✅ `UserChecker` — bloque les comptes inactifs
- ✅ Suppression en cascade des médias orphelins

## 🚀 Commandes utiles

```bash
# Développement
symfony server:start                          # Lancer le serveur dev
symfony console cache:clear                   # Vider le cache
symfony console doctrine:migrations:status    # Vérifier migrations

# BDD
symfony console doctrine:database:create      # Créer la BDD
symfony console doctrine:database:drop --force # Supprimer la BDD
symfony console doctrine:migrations:migrate   # Appliquer les migrations

# Tests
symfony console lint:twig templates/         # Valider Twig
symfony console lint:yaml config/            # Valider YAML
symfony console doctrine:schema:validate     # Valider le schéma

# Git
git checkout develop
git pull origin develop
git checkout -b feature/my-feature
git add .
git commit -m "feat: description"
git push origin feature/my-feature
```

## Git Workflow

- **Branche `main`** — Code de production stable
- **Branche `develop`** — Intégration continue des features
- **Branches `feature/*`** — Nouvelles fonctionnalités
- **Branches `fix/*`** — Corrections de bugs
- **Convention commits** : `feat:`, `fix:`, `docs:`, `chore:`, `refactor:`

Exemple :
```bash
git checkout -b feature/guest-management
# ... travail ...
git commit -m "feat: add guest CRUD with active/inactive toggle"
git push origin feature/guest-management
# → Créer une Pull Request sur GitHub
```

## Points clés du projet

### Corrections effectuées
- ✅ Migration Symfony 5.4 → 8.1 (avec corrections de breaking changes)
- ✅ PHP 8.2 → PHP 8.4 (PHP 8.4 native)
- ✅ Single Action Controllers (découpage des controllers)
- ✅ Injection de dépendances (plus de `getDoctrine()`)
- ✅ Attributs PHP 8 (`#[Route]`, `#[ORM\*]`)
- ✅ Validation complète des entités
- ✅ Tests de sécurité (CSRF, authentification, autorisation)

### N+1 queries résolu
- **Avant** : 102 requêtes / 181ms sur `/guests`
- **Après** : 2 requêtes / 25ms (LEFT JOIN fetch)

### Pagination corrigée
- Les non-admins ne voient que leurs propres médias
- Pagination basée sur le vrai nombre de résultats (pas le total global)

## Contribution

1. Fork le projet
2. Créer une branche `feature/ma-feature`
3. Committer avec messages explicites
4. Pousser et créer une PR

## Licence

© 2024 Ina Zaoui. Tous droits réservés.

## Support

Pour toute question ou issue, ouvrir une issue GitHub ou contacter l'équipe de développement.