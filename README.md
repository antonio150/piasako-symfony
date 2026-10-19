# Piasako

Piasako est une application web de gestion multi-sites construite avec Symfony 7.4 et PHP 8.2 ou supérieur. Elle sépare les données d'administration des données opérationnelles de chaque site.

## Fonctionnement général

L'application utilise deux connexions Doctrine :

- **Base principale** (`DATABASE_URL_MAIN`) : contient notamment les sites et les comptes d'administration.
- **Base dynamique** (`DATABASE_URL_DYNAMIC`) : contient les entités métier d'un site, par exemple les personnes, les équipes, les profils, les tâches et leurs historiques.

Lorsqu'un utilisateur accède à l'espace client, l'application vérifie son identité et ses rôles, retrouve son site dans la base principale, puis utilise les informations de connexion enregistrées pour sélectionner la base de données de ce site.

Les principaux espaces sont :

| Espace | URL | Utilisation |
| --- | --- | --- |
| Connexion administration | `/login` | Connexion à l'espace principal |
| Administration des sites | `/superadmin/site/` | Gestion des sites et de leurs bases de données |
| Administration des comptes | `/superadmin/user/` | Gestion des comptes d'administration |
| Connexion client | `/login-dynamic` | Connexion à l'espace d'un site |
| Espace client | `/espaceclient` | Accès aux fonctions métier du site |
| Gestion des personnes | `/espaceclient/personne/` | Consultation et gestion des personnes |
| Gestion des tâches | `/espaceclient/tache/` | Consultation et création de tâches |

Les routes d'administration nécessitent une authentification. Les fonctionnalités de l'espace client vérifient également les rôles et le site associé à l'utilisateur.

## Prérequis

- PHP **8.2 ou supérieur**, avec les extensions `ctype` et `iconv`.
- Composer.
- Un serveur **MySQL 8** accessible depuis l'application.
- Symfony CLI, facultatif (sinon, le serveur PHP intégré peut être utilisé).
- Docker Compose, facultatif pour démarrer l'application et MySQL dans des conteneurs.

## Installation et configuration

Depuis la racine du projet :

```bash
composer install
```

Créez un fichier `.env.local` à la racine (il est ignoré par Git) et configurez au minimum les variables suivantes. Remplacez les valeurs d'exemple par celles de votre environnement ; n'ajoutez pas de vrais mots de passe ou secrets au dépôt.

```dotenv
APP_ENV=dev
APP_SECRET=remplacer-par-une-valeur-aleatoire
DATABASE_URL_MAIN="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/piasako?serverVersion=8.0&charset=utf8mb4"
DATABASE_URL_DYNAMIC="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/tapos?serverVersion=8.0&charset=utf8mb4"
DB_TYPE=mysql
```

Les bases `piasako` et `tapos` sont des exemples de noms : adaptez les URL aux bases et aux identifiants réellement disponibles. Si le mot de passe contient des caractères réservés dans une URL, encodez-les correctement.

Initialisez ensuite les schémas :

```bash
php bin/console doctrine:database:create --connection=default
php bin/console doctrine:database:create --connection=dynamic
php bin/console doctrine:schema:update --em=default --force
php bin/console doctrine:schema:update --em=dynamic --force
```

Les nouvelles bases de sites doivent également être accessibles au compte MySQL configuré. La commande `app:update-multi-schema` applique le schéma des entités dynamiques aux bases des sites déjà enregistrés dans la base principale.

## Démarrer l'application

### Avec Docker Compose

Depuis la racine du projet, démarrez les conteneurs :

```bash
docker compose up --build
```

L'application est disponible sur <http://localhost:8080>, l'interface Mailpit sur <http://localhost:8025>, et MySQL sur le port `3307` de la machine. Compose crée les bases `piasako` et `tapos` au premier démarrage et configure les deux connexions Symfony vers le service MySQL.

Le mot de passe root MySQL de développement vaut `root` par défaut. Pour le modifier, définissez `MYSQL_ROOT_PASSWORD` avant de démarrer Compose ; le même mot de passe sera utilisé par l'application. Ne réutilisez pas cette configuration de développement en production.

Initialisez les schémas dans le conteneur PHP :

```bash
docker compose exec php php bin/console doctrine:schema:update --em=default --force
docker compose exec php php bin/console doctrine:schema:update --em=dynamic --force
```

Pour arrêter les conteneurs sans supprimer les données :

```bash
docker compose down
```

Les données MySQL restent dans le volume Docker `database_data`. Le script de création de `tapos` est exécuté uniquement lors de l'initialisation d'un volume MySQL vide.

### Sans Docker

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP intégré :

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Ouvrez ensuite <http://127.0.0.1:8000>.

## Commandes courantes

```bash
# Informations sur l'environnement Symfony
php bin/console about

# Lister les routes enregistrées
php bin/console debug:router

# Lister toutes les commandes, y compris les commandes du projet
php bin/console list

# Vérifier les schémas Doctrine
php bin/console doctrine:schema:validate --em=default
php bin/console doctrine:schema:validate --em=dynamic

# Appliquer le schéma dynamique aux bases des sites enregistrés
php bin/console app:update-multi-schema

# Lancer les tests
php bin/phpunit
```

Commandes métier disponibles :

| Commande | Description |
| --- | --- |
| `app:generate-super-admin` | Crée ou met à jour un compte super-administrateur. Vérifiez et modifiez les identifiants définis dans le code de cette commande avant de l'utiliser ; ne conservez pas de mot de passe par défaut en production. |
| `app:update-multi-schema` | Met à jour le schéma de la base dynamique pour chaque site trouvé dans la base principale. |
| `app:create-random-users <database> [count]` | Génère des comptes et personnes de test dans une base donnée (10 entrées par défaut). À réserver au développement ; la commande utilise un mot de passe de test fixe. |

## Tests

Lancer la suite de tests :

```bash
php bin/phpunit
```

Les tests utilisent l'environnement `test` de Symfony. Configurez des bases de test séparées si un test nécessite une connexion à la base de données ; n'utilisez pas les bases de production.

## Arborescence utile

| Répertoire | Contenu |
| --- | --- |
| `src/Controller/` | Contrôleurs HTTP et routes |
| `src/Entity/Main/` | Entités de la base principale |
| `src/Entity/Dynamic/` | Entités métier des bases clientes |
| `src/Service/` | Sélection de base, rôles et services applicatifs |
| `src/Command/` | Commandes Symfony spécifiques au projet |
| `templates/` | Pages Twig |
| `config/` | Configuration Symfony, Doctrine et sécurité |
| `migrations/` | Répertoire configuré pour les migrations Doctrine |
| `public/uploads/` | Fichiers téléversés |
