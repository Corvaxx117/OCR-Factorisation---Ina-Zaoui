# Rapport de performance Front Office

## Objectif

Mesurer les pages publiques et documenter la correction de la lenteur observee
sur `/guests`.

## Environnement et methode

- Application Symfony 8.1 / PHP-FPM 8.4.20 en environnement local `dev`.
- PostgreSQL 16 dans le conteneur Docker `postgres-dev`.
- Base de developpement chargee avec environ 100 invites et 5 050 medias.
- Cinq requetes HTTPS locales par page avec `curl` ; les resultats ci-dessous
  correspondent a la moyenne des cinq passages, une fois le serveur echauffe.

Deux indicateurs sont releves :

1. Le temps total HTTP (`time_total`) : temps entre le depart de la requete et
   la reception complete de la reponse HTML.
2. La taille HTML transferee (`size_download`) : poids de la reponse initiale,
   hors images, feuilles de style et scripts charges ensuite par le navigateur.

Commande reproductible :

```bash
curl -sk -o /dev/null -w '%{http_code} %{time_total} %{size_download}\n' \
  https://127.0.0.1:8001/guests
```

## Mesures Front Office actuelles

| Page | Statut HTTP | Temps HTTP moyen | Taille HTML |
|---|---:|---:|---:|
| `/` | 200 | 32,7 ms | 51 161 octets |
| `/about` | 200 | 28,6 ms | 52 056 octets |
| `/portfolio` | 200 | 68,6 ms | 64 915 octets |
| `/guests` | 200 | 201,0 ms | 75 227 octets |
| `/guest/2` (invite actif) | 200 | 60,0 ms | 60 004 octets |

`/guests` est naturellement la page la plus couteuse : elle affiche la liste
des invites et le nombre de leurs medias. Son temps HTTP reste sous 250 ms dans
cet environnement local charge.

## Correction de la lenteur `/guests`

Le Symfony Profiler avait identifie un probleme N+1. La page chargeait les
invites, puis declenchait une requete supplementaire pour les medias de chacun
d'eux au moment de calculer `guest.medias|length` dans Twig.

| Version | Requetes SQL | Temps SQL |
|---|---:|---:|
| Avant correction | 102 | 181 ms |
| Apres correction | 2 | 25 ms |
| Gain | -98 % | -86 % |

La correction est centralisee dans `UserRepository::findActiveGuestsWithMedias()`
avec un `LEFT JOIN` et `addSelect('m')`. Doctrine recupere alors les invites et
leurs medias dans une seule requete de lecture, ce qui evite les acces SQL
repetes pendant le rendu Twig.

## Complement Lighthouse

Un audit Lighthouse local precedemment releve donne les scores suivants :

| Indicateur | Score |
|---|---:|
| Performance | 95 / 100 |
| Accessibilite | 96 / 100 |
| Bonnes pratiques | 96 / 100 |

Le score SEO de developpement n'est pas retenu comme reference, car le serveur
local applique `noindex`.

## Limites et conclusion

Les temps `curl` ne remplacent pas une mesure de rendu navigateur : ils ne
comprennent ni le telechargement des images, ni le JavaScript, ni les Core Web
Vitals. Ils fournissent en revanche une mesure rapide, comparable et
reproductible du temps de reponse serveur.

La correction N+1 atteint l'objectif prioritaire : la page Invites ne voit plus
son nombre de requetes SQL croitre lineairement avec le nombre d'invites. Pour
une mesure de production, il faudra refaire les mesures sur l'infrastructure de
production et comparer les Core Web Vitals avec Lighthouse.