# MealMates Symfony

![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![Symfony](https://img.shields.io/badge/Symfony-5.4+-brightgreen.svg)
![Docker](https://img.shields.io/badge/Docker-20.10+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)

## Description du Projet

MealMates est une application web construite avec Symfony qui facilite le partage et la réservation de repas entre utilisateurs. Elle permet aux utilisateurs de créer des profils, gérer leurs préférences alimentaires, réserver des repas et communiquer avec d'autres utilisateurs via une fonctionnalité de chat. Cette plateforme vise à améliorer les expériences de repas sociaux en connectant des individus ayant des intérêts culinaires similaires.

### Fonctionnalités Clés
- **Gestion des Utilisateurs** : Les utilisateurs peuvent s'inscrire, créer des profils et gérer leurs préférences alimentaires.
- **Réservation de Repas** : Les utilisateurs peuvent réserver des repas et gérer leurs réservations efficacement.
- **Fonctionnalité de Chat** : Les utilisateurs peuvent communiquer entre eux concernant les arrangements de repas.
- **Notifications par Email** : Les utilisateurs reçoivent des alertes email pour les réservations et autres notifications.

## Stack Technologique

| Technologie | Description |
|-------------|-------------|
| ![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg) | Langage de programmation côté serveur utilisé pour le développement backend. |
| ![Symfony](https://img.shields.io/badge/Symfony-5.4+-brightgreen.svg) | Framework PHP pour construire des applications web. |
| ![Docker](https://img.shields.io/badge/Docker-20.10+-blue.svg) | Plateforme de conteneurisation pour le déploiement. |
| ![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg) | Système de gestion de base de données utilisé pour le stockage des données. |

## Instructions d'Installation

### Prérequis
- PHP 7.4 ou supérieur
- Composer
- Docker (optionnel, pour le déploiement conteneurisé)
- MySQL 8.0 ou supérieur

### Installation Étape par Étape
1. **Cloner le Dépôt**
   ```bash
   git clone https://github.com/nygmasx/mealmates-symfony.git
   cd mealmates-symfony
   ```

2. **Installer les Dépendances**
   ```bash
   composer install
   ```

3. **Configurer les Variables d'Environnement**
    - Copiez le fichier `.env.dev` vers `.env` et configurez votre base de données et autres paramètres.
   ```bash
   cp .env.dev .env
   ```

4. **Exécuter les Migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

5. **Démarrer l'Application**
    - Si vous utilisez Docker, construisez et lancez les conteneurs :
   ```bash
   docker-compose up -d
   ```
    - Si vous n'utilisez pas Docker, démarrez le serveur Symfony :
   ```bash
   php bin/console server:start
   ```

## Utilisation

Pour accéder à l'application, naviguez vers `http://localhost:8000` dans votre navigateur web après avoir démarré le serveur.

### Utilisation de Base
- **Inscrire un Nouvel Utilisateur** : Naviguez vers la page d'inscription et remplissez le formulaire.
- **Créer une Réservation** : Après vous être connecté, vous pouvez parcourir les repas disponibles et créer des réservations.
- **Chatter avec d'Autres Utilisateurs** : Utilisez la fonctionnalité de chat pour communiquer avec d'autres utilisateurs concernant les arrangements de repas.

## Structure du Projet

```
Structure des répertoires :
└── mealmates-symfony/
    ├── bin/                        # Commandes console
    │   └── console
    ├── config/                     # Fichiers de configuration
    │   ├── packages/               # Configurations spécifiques aux packages
    │   ├── routes/                 # Routes de l'application
    │   ├── services.yaml           # Définitions des services
    ├── docker/                     # Configuration Docker
    │   ├── nginx/                  # Configuration Nginx
    │   └── php/                    # Dockerfile PHP
    ├── migrations/                 # Migrations de base de données
    ├── public/                     # Fichiers accessibles publiquement
    │   └── index.php               # Point d'entrée de l'application
    ├── src/                        # Code source de l'application
    │   ├── Command/                # Commandes console
    │   ├── Controller/             # Contrôleurs pour gérer les requêtes
    │   ├── Entity/                 # Entités Doctrine
    │   ├── Repository/             # Repositories pour l'accès aux données
    │   ├── Security/               # Classes liées à la sécurité
    │   ├── Service/                # Services pour la logique métier
    │   └── Kernel.php              # Noyau de l'application
    ├── templates/                  # Templates Twig pour le rendu des vues
    ├── .env.dev                    # Variables d'environnement pour le développement
    ├── composer.json               # Dépendances Composer
    └── Makefile                    # Makefile pour les tâches communes
```

### Explication des Répertoires et Fichiers Principaux
- **bin/** : Contient les commandes console pour gérer l'application.
- **config/** : Contient les fichiers de configuration pour les packages, services et routage.
- **docker/** : Contient les fichiers de configuration Docker pour configurer l'application dans des conteneurs.
- **public/** : Le point d'entrée pour l'application, accessible via le web.
- **src/** : Le répertoire principal du code source contenant les contrôleurs, entités et services.
- **templates/** : Contient les templates Twig utilisés pour le rendu des vues HTML.
