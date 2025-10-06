# Changelog du squelette Applicatif de la DR Bretagne (Angular)

Pour plus de clarté, ce document ne doit servir qu'à lister les BREAKING CHANGES du squelette, implicant une modification du code des applications.

# 5.8.2

Pour désactiver l'encryption des requêtes en DEV, il faut désormais créer la variable dnc (même vide) dans le stockage de session (au lieu du stockage local).

# 5.7.0

Désormais, les utilisateurs sont prévenus lorsque le front-end a été mis à jour (ng build) et sont invités à recharger la page Web.
Pour intégrer le message d'invitation à monter de version côté fron-end, il faut importer 1 librairie :

```
npm install sweetalert2 --save
```

Aussi, si vous ne modifiez que le back-end de votre application, ne refaites pas un build sous Angular : contentez-vous de modifier seulement le dossier /API pour éviter que les utilisateurs aient cette invite inutile.

La variable $\_SESSION n'est plus utilisée pour stocker les informations de l'utilisateur (remplacé par l'access_token dans le header Authorization Bearer).
Il est donc possible de pousser une modification en PROD sans géner les utilisateurs (sauf si le front doit être rechargé).
Pensez à vérifier que vous n'utilisez pas $\_SESSION pour conserver des données propres aux utilisateurs. Privilégiez plutôt une table de la base de données. Utilisez dans ce cas $\_USER['access_token'] comme PRIMARY KEY de votre table si vous souhaitez que les données ne perdurent pas en cas de logout, et intégrez un champ date pour pouvoir la purger. Si vous souhaitez que les données perdurent en cas de logout, utilisez le nni comme PRIMARY KEY.

# 5.6.0

Si vous utilisez les fichiers d'exemple pour db_export et db_import, sachez que le fichier db_export commande directement l'import à la DEV.
Dans ce cas, vous pouvez donc retirer la ligne db_import de la crontab de DEV de votre application.

# 5.5.0

Pensez à paramétrer l'anonymisation des exports vers la DEV depuis l'administration de la PROD.

# 5.4.0

Pour intégrer les graphiques de santé du serveur, il faut importer 2 librairies :

```
npm install ngx-echarts echarts --save
```

ATTENTION : faites un ng build sans tarder juste après l'ajout des 2 librairies. Si vous obtenez des erreurs de compilation, tentez de chercher quelle version de ngx-echarts s'adapte à votre version d'Angular. Puis faites de même pour echarts. Supprimez les 2 bibliothèques et installez les versions adaptées.

Il faut aussi ajouter l'extension zip de php dans place-cloud.conf:

```
extensions="mysqli gd zip"
```

Pensez à ajouter les lignes suivantes à vos crontab de DEV et PROD :

```
* 07-18 * * 1-5 cd /var/www/html/API/core && php ./core_metrics.php > /dev/fifo-stdout 2> /dev/fifo-stderr
```

Pensez à ajouter les lignes crontab à votre section "assets" dans angular.json.
L'idéal est de placer toutes les lignes acceptées par Place, même si les fichiers n'existent pas (cela n'empêchera pas le build) :

```
  "src/favicon.ico",
  "src/assets",
  "src/API",
  "src/place-cloud.conf",
  "src/composer.json",
  "src/development.env",
  "src/production.env",
  "src/environment.env",
  "src/crontab.production",
  "src/crontab.development",
  "src/crontab"
```

Si vous avez un routing admin personnalisé, pensez à ajouter le routing et l'entrée de links: Link[] qui se trouvent dans core-admin-component.

## Nouveau : recopie automatisée des tables de DEV vers PROD

Pour mettre en place la recopie de tables de BDD de PROD vers DEV, il faut ajouter une ligne dans la crontab de PROD (prendre exemple dans /API/core/classes/coredbsyncrhonize/examples/) et paramétrer dans la partie administration de la PROD les tables à inclure dans l'export.

A venir : un système d'anonimisation des données en DEV.

# 5.3.0

Il est possible de forcer le choix d'un délégataire SSO en modifiant environment.ts comme suit :

```
export const environment = {
    auth_method: 'SSO_delegate',
    SSO_delegate: 'https://xxxxxxxxxxxxxxx.place-cloud-enedis.fr/API/core/SSO_callback/?callback=',
    ...
}
```

# 5.2.0

Vous pouvez désormais ouvrir une partie de vos APIs à d'autres applications.
Il suffit de déclarer ces APIs dans my-config.inc.php :

```
$_externalApis = ['getTopContributors', 'getPublicTasks', 'isActiveMember'];
```

# 5.1.0

Vous pouvez désormais utiliser le paramètre hidden=true quand vous déclarez des paramètres avec CoreParameter.
Ces paramètres utilisent un champ de type "password" côté front pour pouvoir être écrsés sans afficher leur valeur.
A utiliser pour les pot depasse, les secrets...

Il n'est plus nécessaire de passer par CoreParameters pour initialiser les appels à APIGILE.
Faites simplement par exemple : $apiContacts = new ApiContacts();

## Attention : assurez-vous d'avoir le paramêtre endpoint_api dans vos CoreParameters (/admin/parameters)

## Attention : assurez-vous que xxx_DEVELOPER possède le droit 'PARAMETRES' dans my-config.php

## Attention : assurez-vous que /parameters est présent dans la route de _votre_ admin-routing.module.ts (le cas échéant)

# 5.0.0

## Le squelette ne fonctionne plus sous Azur : merci de retirer _votre_ code spécifique Azur

Pour retirer le code spécifique Azur, vous pouvez plus globalement rechercher :

- isHostedOn
- /appli/projects/
- /home/
- .appheb
  (la liste n'est pas exhaustive)

Par exemple :

```
$rootDir = '/home/' . $_mySqlLogin . '/www/pieces_jointes/';
```

deviendrait :

```
$rootDir = '/var/www/html/files/attachements/;
```

# 4.35.0

## $\_coreMysqli est abandonné

Faites une recherche globale dans votre code, et remplacez $\_coreMysqli par CoreMysqli::get()
Par exemple :

```
$stmt = $_coreMysqli->prepare($query);
```

devient :

```
$stmt = CoreMysqli::get()->prepare($query);
```

Pensez à ajouter la ligne suivante en haut de page

```
use EnedisLabBZH\Core\CoreMysqli;
```
