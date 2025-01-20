# datasolution

## Git flow

Le projet utilise git flow pour les dev.

 - `main` est la branche de production
 - `develop` est la branche de développement
 - `feature/` est la racine des branches pour les nouvelle fonctionnalités
 - `bugfix/` est la racine des branches pour le corrections de bug
 - `release/` est la racine des branche pour les versions
 - `hotfix/` est la racine des branches pour les correctifs à chaud
 - `support/` est la racine des branches pour les actions de support

Pour plus d'informations, voir <http://dominhhai.github.io/git-flow-cheatsheet/index.html>

## Configuration

Il faut copier le fichier `.env` en `.env.local` et renseigner correctement les 
variables d'environnement en fonction de la machine ou l'environnement désiré.

Le fichier `.env.local` ne doit pas être versionné.

Actuellement, pour avoir une base de données en dev, il faut récupérer une 
copie de celle qui existe sur le serveur de développement.

## Développement

Il est bon d'avoir l'outil Symfony en ligne de commande : <https://symfony.com/download>

Ensuite, un simple `symfony serve` dans le dossier du projet le sert 
sur <http://localhost:8000>

Pensez à avoir un `.env.local` avec `APP_ENV=dev`.

Pensez à faire `composer install`

