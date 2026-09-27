# Guide de contribution

Merci de votre intérêt pour le projet Ina Zaoui. Ce document décrit **comment contribuer** :
workflow Git, conventions de commit, standards de code et exigences de qualité.

L'installation, la stack technique, l'architecture et les commandes d'exploitation
sont documentées dans le [README](README.md).

## Workflow Git

### 1. Créer une branche

```bash
git checkout develop
git pull origin develop
git checkout -b feature/ma-feature
```

Nommage des branches :

| Préfixe | Usage |
|---|---|
| `feature/` | nouvelle fonctionnalité |
| `fix/` | correction de bug |
| `chore/` | maintenance, dépendances |
| `docs/` | documentation |

### 2. Committer avec Conventional Commits

Format : `type(scope): message`, à l'impératif et en anglais.

```bash
git commit -m "feat(guest): add block/unblock toggle for inactive accounts"
git commit -m "fix(media): resolve N+1 query on index page"
```

Types autorisés : `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `style`, `perf`.

### 3. Ouvrir une Pull Request

```bash
git push origin feature/ma-feature
```

La PR cible `develop` et doit contenir un titre descriptif, un résumé des changements et
le lien vers l'issue concernée le cas échéant. Elle n'est fusionnée qu'une fois la pipeline
GitHub Actions verte et la review validée.

## Qualité

Toute contribution doit satisfaire les quatre exigences suivantes avant d'être soumise.

- La suite **PHPUnit** passe intégralement, sans erreur, avertissement ni *notice* PHP.
- La **couverture de lignes** reste supérieure à 70 %, entités Doctrine exclues
  (98,18 % à ce jour). Les entités sont écartées du calcul : leurs accesseurs
  n'apportent aucune information sur la robustesse du code.
- **PHPStan** est configuré au **niveau 8** dans `phpstan.dist.neon` et ne remonte aucune
  erreur. Ce niveau ne doit pas être abaissé pour faire passer une contribution.
- **PHP CS Fixer** ne signale aucun écart de style.

### Commandes à lancer avant de pousser

Les commandes ci-dessous utilisent le binaire `symfony` et fonctionnent aussi sans lui :
la table d'équivalence figure dans le [README](README.md#avec-ou-sans-le-binaire-symfony).

```bash
# Repartir d'une base de test connue
symfony console --env=test doctrine:fixtures:load --no-interaction

# Suite de tests
symfony php bin/phpunit --testdox

# Couverture (rapport HTML versionné dans TestCoverage/)
symfony php bin/phpunit --coverage-html TestCoverage

# Analyse statique et style
symfony composer quality     # PHPStan niveau 8 + vérification du style
symfony composer cs:fix      # correction automatique du style

# Validation des fichiers de configuration
symfony console lint:twig templates/
symfony console lint:yaml config/
symfony console doctrine:schema:validate --env=test
```

## Standards de code

### PHP

- Indentation de 4 espaces.
- Classes en `PascalCase`, méthodes et variables en `camelCase`, constantes en `UPPER_SNAKE_CASE`.
- Longueur de ligne : 120 caractères visés au maximum. Il s'agit d'un repère de lisibilité,
  pas d'une règle bloquante — une ligne plus longue vaut mieux qu'une coupure artificielle.
- Le formatage n'est pas à la main : `composer cs:fix` applique le style attendu.


## Sécurité

À vérifier avant de soumettre une contribution :

- aucun secret en clair dans le code ou les fixtures (mots de passe, jetons, clés d'API) ;
- toute entrée utilisateur validée côté serveur ;
- toute requête de modification protégée par un jeton CSRF ;
- tout accès à une ressource sensible soumis à un contrôle d'autorisation explicite ;
- mots de passe stockés hachés, jamais en clair ni réversibles ;
- fichiers téléversés contrôlés en type MIME et en taille, et renommés au stockage.

## Documentation

Si le comportement ou l'installation changent lors d'une contribution, mettre à jour la documentation (README) qu'elle rend obsolète.

## Besoin d'aide

Consultez le [README](README.md) ou ouvrez une issue sur le dépôt.
