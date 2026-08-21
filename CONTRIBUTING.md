# Guide de Contribution

Merci de vouloir contribuer au projet Ina Zaoui ! Ce document explique comment procéder.

## Prérequis

- Symfony 8.1+ LTS
- PHP 8.4+
- PostgreSQL 16
- Docker Desktop
- Git

## Workflow Git

### 1. Créer une branche

```bash
git checkout develop
git pull origin develop
git checkout -b feature/ma-feature
```

Nommage des branches :
- `feature/description` — nouvelle fonctionnalité
- `fix/description` — correction de bug
- `chore/description` — maintenance, dépendances
- `docs/description` — documentation

### 2. Committer avec Conventional Commits

Format : `type(scope): message`

```bash
git commit -m "feat(guest): add block/unblock toggle for inactive accounts"
git commit -m "fix(media): resolve N+1 query on index page"
git commit -m "docs(readme): update installation instructions"
git commit -m "refactor(auth): rename UserChecker for clarity"
```

**Types autorisés** :
- `feat` — nouvelle fonctionnalité
- `fix` — correction de bug
- `docs` — documentation
- `refactor` — restructuration de code (pas de logique changée)
- `test` — ajout de tests
- `chore` — maintenance, dépendances, config
- `style` — formatage, indentation (pas de logique changée)
- `perf` — amélioration de performance

### 3. Pousser et créer une Pull Request

```bash
git push origin feature/ma-feature
```

Puis créer une PR sur GitHub :
- Titre descriptif (reprendre le commit message)
- Description détaillée des changements
- Lier les issues si applicable (#123)
- Demander une review

## Architecture

### Ajouter une nouvelle fonctionnalité

**Exemple : ajouter une page "Mentions légales"**

1. **Créer le controller**
   ```php
   // src/Controller/Front/LegalAction.php
   #[Route(path: '/legal', name: 'legal')]
   public function __invoke(): Response {
       return $this->render('front/legal.html.twig');
   }
   ```

2. **Créer le template**
   ```twig
   {# templates/front/legal.html.twig #}
   {% extends 'front.html.twig' %}
   
   {% block front %}
       <h1>Mentions Légales</h1>
       <!-- contenu -->
   {% endblock %}
   ```

3. **Ajouter le lien de navigation**
   ```twig
   {# templates/front.html.twig #}
   <li class="nav-item">
       <a class="nav-link" href="{{ path('legal') }}">Mentions</a>
   </li>
   ```

4. **Committer**
   ```bash
   git add .
   git commit -m "feat(front): add legal page"
   git push origin feature/legal-page
   ```

### Ajouter une entité

**Exemple : ajouter un champ "website" sur User**

1. **Modifier l'entité**
   ```php
   // src/Entity/User.php
   #[ORM\Column(length: 255, nullable: true)]
   #[Assert\Url(message: 'Veuillez entrer une URL valide.')]
   private ?string $website = null;
   
   public function getWebsite(): ?string {
       return $this->website;
   }
   
   public function setWebsite(?string $website): void {
       $this->website = $website;
   }
   ```

2. **Générer la migration**
   ```bash
   symfony console make:migration
   symfony console doctrine:migrations:migrate
   ```

3. **Mettre à jour le formulaire**
   ```php
   // src/Form/GuestType.php
   ->add('website', UrlType::class, ['required' => false])
   ```

4. **Committer**
   ```bash
   git add src/Entity/User.php src/Form/GuestType.php migrations/
   git commit -m "feat(user): add website field"
   ```

## Tests & Validation

Avant de pousser :

```bash
# Valider Twig
symfony console lint:twig templates/

# Valider YAML
symfony console lint:yaml config/

# Valider le schéma BDD
symfony console doctrine:schema:validate

# Vider le cache
symfony console cache:clear

# Lancer les tests (quand implémentés)
symfony console --env=test
```

## Standards de code

### Conventions PHP

- Indentation : 4 espaces
- Noms de classes : PascalCase (`UserChecker`, `MediaUploadAction`)
- Noms de méthodes : camelCase (`getUserIdentifier()`)
- Noms de constantes : UPPER_CASE (`MAX_UPLOAD_SIZE`)
- Ligne max : 120 caractères (soft limit)

```php
// ✅ BON
public function checkPreAuth(UserInterface $user): void
{
    if (!$user instanceof User) {
        return;
    }
    
    if (!$user->isActive()) {
        throw new CustomUserMessageAccountStatusException(
            'Votre compte a été désactivé.'
        );
    }
}
```

### Conventions Twig

- Indentation : 4 espaces
- Commentaires : `{# commentaire #}`
- Variables : camelCase (`{{ user.firstName }}`)
- Filtres : underscore (`{{ text|upper }}`)

```twig
{# ✅ BON #}
{% for guest in guests %}
    <article class="guest-card">
        <h3>{{ guest.name }}</h3>
        <p>{{ guest.description|truncate(100) }}</p>
    </article>
{% endfor %}
```

## Sécurité

Avant de soumettre une PR :

- ✅ Pas de secrets en clair (tokens, mdp)
- ✅ Valider les inputs utilisateur
- ✅ Protéger les POST avec CSRF
- ✅ Vérifier `#[IsGranted(...)]` sur actions sensibles
- ✅ Hacher les mots de passe avec `UserPasswordHasherInterface`
- ✅ Désinfecter les fichiers uploadés

## Performance

- Optimiser les requêtes : utiliser `LEFT JOIN` au lieu de lazy loading
- Éviter le N+1 : pré-charger les relations avec `->addSelect('...')`
- Paginer les grandes listes (max 25-50 items par page)
- Compresser les images (max 2MB)

## Documentation

Toute nouvelle fonctionnalité doit inclure :

- Docblocks PHP (classes et méthodes)
- Commentaires sur code complexe
- Mise à jour du README si nécessaire
- Exemple d'utilisation si applicable

```php
/**
 * Vérifie l'état du compte utilisateur avant authentification.
 * Bloque la connexion si le compte est désactivé (active = false).
 */
class UserChecker implements UserCheckerInterface {
    // ...
}
```

## Release Notes

Quand tu pousses sur `develop`, le code sera listé dans les prochaines notes de version sous la forme :

```markdown
### Features
- feat(guest): add block/unblock toggle

### Bug Fixes
- fix(media): resolve N+1 query

### Documentation
- docs(readme): update installation
```

## Besoin d'aide ?

- Consulte le [README.md](README.md)
- Ouvre une issue si tu as une question
- Demande une review sur ta PR

Merci pour ta contribution ! 🙏
