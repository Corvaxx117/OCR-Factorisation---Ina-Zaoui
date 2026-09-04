# Strategie de tests

## Etat actuel

La suite repose sur PHPUnit 11, PostgreSQL 16, PCOV pour la couverture et
`dama/doctrine-test-bundle` pour l'isolation transactionnelle. Elle contient 57
tests et 185 assertions, avec 88,01 % de lignes couvertes, au-dessus de
l'objectif de 70 %.

## Organisation

```text
tests/
├── Unit/          Entites, validation de fichier et UserChecker
├── Functional/    Repositories, securite, controllers Admin et Front Office
└── Support/       Acces types a l'EntityManager et au password hasher
```

- Les tests unitaires etendent `TestCase` : ils n'utilisent ni kernel Symfony ni
  base de donnees.
- Les tests fonctionnels utilisent `KernelTestCase` ou `WebTestCase` : ils
  valident Doctrine, les formulaires, les routes, la securite et les templates.
- Les fixtures de `AppFixtures` fournissent un administrateur, un invite actif,
  un invite bloque, deux albums et deux medias pour l'environnement `test`.
- Les factories privees presentes dans les tests servent uniquement aux donnees
  propres a un scenario, par exemple un media a supprimer ou une seconde page.

## Couverture fonctionnelle

- Authentification : login valide/invalide, compte bloque, logout et acces
  protege.
- Administration : CRUD Album, Invite et Media, droits admin/proprietaire,
  CSRF, validation et pagination a 20 elements par page.
- Front Office : accueil, a propos, portfolio par defaut et par album, liste des
  invites actifs et profils inaccessibles pour les comptes bloques ou admin.
- Performance : le repository des invites charge les medias par `LEFT JOIN` pour
  eviter le N+1.

## Commandes de maintenance

```bash
# Repartir d'une base de test connue
symfony console --env=test doctrine:fixtures:load --no-interaction

# Executer la suite
symfony php bin/phpunit --testdox

# Generer la couverture
symfony php bin/phpunit --coverage-html var/coverage
open var/coverage/index.html

# Verifier la qualite du code
symfony composer phpstan
symfony composer cs:check
symfony composer quality
```

## Execution continue

GitHub Actions execute migrations, fixtures, PHPUnit, PHPStan et PHP CS Fixer
sur les branches `develop` et `main`, ainsi que sur leurs Pull Requests.
