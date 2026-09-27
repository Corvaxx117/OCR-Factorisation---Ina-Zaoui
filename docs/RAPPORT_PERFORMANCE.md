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

Le Symfony Profiler fournit deux indicateurs serveur complementaires : le
nombre de requetes SQL et leur temps cumule. Les valeurs ci-dessous ont ete
relevees sur les profils HTTP generes le 12 septembre 2026 avec la base locale
de developpement.

Commande reproductible :

```bash
curl -sk -o /dev/null -w '%{http_code} %{time_total} %{size_download}\n' \
  https://127.0.0.1:8001/guests
```

## Mesures Front Office actuelles

| Page | Statut HTTP | Temps HTTP moyen | Taille HTML | Requetes SQL | Temps SQL |
|---|---:|---:|---:|---:|---:|
| `/` | 200 | 32,7 ms | 51 161 octets | 0 | 0,00 ms |
| `/about` | 200 | 28,6 ms | 52 056 octets | 0 | 0,00 ms |
| `/portfolio` | 200 | 68,6 ms | 64 915 octets | 3 | 9,27 ms |
| `/guests` | 200 | 201,0 ms | 75 227 octets | 1 | 40,32 ms |
| `/guest/2` (invite actif) | 200 | 60,0 ms | 60 004 octets | 2 | 11,91 ms |

**Lecture des résultats :** L’accueil et la page À propos ne sollicitent pas la base. Le portfolio et le profil invité
restent sous 70 ms. `/guests` est naturellement la page la plus couteuse : elle affiche la liste
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

## Arbitrage `addSelect` contre `fetch: EAGER`

Deux strategies permettent de supprimer ce N+1. Toutes deux demandent a Doctrine
de rapporter les medias en meme temps que les invites, au lieu d'y retourner
invite par invite. Elles different sur l'endroit ou cette consigne est donnee :
`addSelect` l'ecrit dans la requete concernee, `fetch: 'EAGER'` la fixe une fois
pour toutes sur l'entite `User`.

Elles ont ete mesurees sur la base de developpement (99 invites, 5 053 medias),
trois passes de sept requetes chacune, mediane retenue.

| Strategie | Requetes SQL | Temps SQL | HTTP median |
|---|---:|---:|---:|
| Chargement paresseux (etat initial) | 99 | 261 ms | 541 ms |
| `fetch: 'EAGER'` sur `User::$medias` | 2 | ~40 ms | ~257 ms |
| `LEFT JOIN` + `addSelect` (retenu) | 1 | ~40 ms | ~229 ms |

Contrairement a une idee repandue, `EAGER` sur une association `OneToMany` ne
reproduit pas le N+1 : Doctrine regroupe le chargement des collections en une
seule requete `WHERE user_id IN (...)`. Les deux strategies resolvent donc
reellement le probleme, et l'ecart de temps SQL entre elles reste inferieur a la
dispersion observee entre deux passes.

Le critere decisif est donc ailleurs : c'est la portee de chaque solution.
`addSelect` ne modifie qu'une requete. `EAGER`, declare sur l'entite, s'applique
a toutes les requetes qui chargent un `User`, y compris celles qui n'ont aucun
besoin de ses medias. Autrement dit, il fait payer a toute l'application le prix
d'un probleme qui n'existe que sur une page.

L'effet de bord a ete mesure sur `/portfolio`, qui ne lit jamais `user.medias` :

| `/portfolio` | Sans EAGER | Avec EAGER |
|---|---:|---:|
| Requetes SQL | 3 | 4 |
| Temps SQL | 9,8 ms | 12,3 ms |
| HTTP median | 59 ms | 72 ms |

Soit environ 22 % de temps de reponse supplementaire sur une page etrangere au
probleme, et la meme penalite partout ailleurs ou un `User` est charge.

Dernier argument, non chiffrable : `findActiveGuestsWithMedias()` annonce son
intention dans son nom. Un `fetch: 'EAGER'` pose sur une entite n'explique a
personne pourquoi il est la ; le jour ou quelqu'un le retire en faisant du
menage, le N+1 revient sans que rien ne le signale.

## Complement Lighthouse

Un audit Lighthouse local precedemment releve donne les scores suivants :

| Indicateur | Score |
|---|---:|
| Performance | 95 / 100 |
| Accessibilite | 96 / 100 |
| Bonnes pratiques | 96 / 100 |

Le score SEO n'est volontairement pas retenu. En environnement de developpement,
les pages envoient une consigne `noindex`, qui demande aux moteurs de recherche
de ne pas referencer le site. C'est volontaire : un site de test n'a rien a faire
dans les resultats de Google. Lighthouse detecte cette consigne et abaisse
fortement la note SEO. Ce score mesure donc une precaution de developpement, pas
la qualite du referencement une fois le site en ligne. Il devra etre releve a
nouveau apres mise en production, `noindex` retire.

## Limites et conclusion

`curl` telecharge le document HTML puis s'arrete. Un navigateur, lui, poursuit :
il recupere les images, les feuilles de style et les scripts, puis dessine la
page. Le temps reellement percu par un visiteur est donc superieur aux valeurs
de ce rapport.

Les Core Web Vitals sont les indicateurs publies par Google pour mesurer ce
ressenti : delai avant l'affichage du contenu principal, stabilite visuelle
pendant le chargement, reactivite aux premieres interactions. Ils se mesurent
dans un navigateur, ce que `curl` ne simule pas.

Ce que `curl` mesure reste neanmoins pertinent ici : le temps que met le serveur
a produire la page. C'est exactement la part qui depend du code et des requetes
SQL, donc celle que ce rapport cherche a evaluer. Et c'est une mesure rapide,
reproductible et comparable d'une version a l'autre.

La correction N+1 atteint l'objectif prioritaire : la page Invites ne voit plus
son nombre de requetes SQL croitre lineairement avec le nombre d'invites. Pour
une mesure de production, il faudra refaire les mesures sur l'infrastructure de
production et comparer les Core Web Vitals avec Lighthouse.