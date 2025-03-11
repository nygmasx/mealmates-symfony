COMPOSE=docker compose
COMPOSE_MAC=$(COMPOSE) -f docker-compose-sync.yml
EXEC=$(COMPOSE) exec php
CONSOLE=$(EXEC) bin/console
ENVIRONMENT=$(shell uname -s)
SHELL := /bin/bash
MUTAGEN_NAME=symfony-app-sync

# Couleurs pour une meilleure lisibilité
RESET = \033[0m
BLUE = \033[34m
GREEN = \033[32m
YELLOW = \033[33m
RED = \033[31m

# ====================================
# = COMMANDES DOCKER                 =
# ====================================

up:
	@if [ -n "$$(docker ps -q 2>/dev/null)" ]; then \
		echo -e "$(YELLOW)Arrêt des conteneurs en cours...$(RESET)"; \
		docker stop $$(docker ps -q) 2>/dev/null || true; \
	else \
		echo -e "$(GREEN)Aucun conteneur en cours d'exécution à arrêter$(RESET)"; \
	fi
ifeq ($(ENVIRONMENT),Darwin)
	@echo -e "$(BLUE)Environnement Mac détecté$(RESET)"
	@if [ -f docker-compose-sync.yml ]; then \
		$(COMPOSE_MAC) up -d --build --remove-orphans; \
		if [ -f ./scripts/start-macos.sh ]; then \
			bash ./scripts/start-macos.sh; \
		else \
			echo -e "$(YELLOW)Le script start-macos.sh n'existe pas. Création du service Mutagen manuellement...$(RESET)"; \
			mutagen sync create --name=$(MUTAGEN_NAME) ./ docker://symfony-php-1/var/www/symfony; \
		fi \
	else \
		echo -e "$(YELLOW)Fichier docker-compose-sync.yml non trouvé, utilisation de docker-compose standard$(RESET)"; \
		$(COMPOSE) up -d --build --remove-orphans; \
	fi
else
	@echo -e "$(BLUE)Environnement Linux/autre détecté$(RESET)"
	$(COMPOSE) up -d --build --remove-orphans
endif
	@echo -e "$(GREEN)Les conteneurs ont été démarrés avec succès$(RESET)"

stop:
ifeq ($(ENVIRONMENT),Darwin)
	@echo -e "$(YELLOW)Arrêt des conteneurs sur Mac...$(RESET)"
	@if [ -f docker-compose-sync.yml ]; then \
		$(COMPOSE_MAC) stop; \
		if command -v mutagen >/dev/null 2>&1; then \
			echo -e "$(YELLOW)Pause de la synchronisation Mutagen...$(RESET)"; \
			mutagen sync pause $(MUTAGEN_NAME) 2>/dev/null || true; \
		else \
			echo -e "$(YELLOW)Mutagen non installé. Ignoré.$(RESET)"; \
		fi \
	else \
		$(COMPOSE) stop; \
	fi
else
	@echo -e "$(YELLOW)Arrêt des conteneurs...$(RESET)"
	$(COMPOSE) stop
endif
	@echo -e "$(GREEN)Les conteneurs ont été arrêtés avec succès$(RESET)"

rm:
	@echo -e "$(YELLOW)Arrêt des conteneurs...$(RESET)"
	-make stop
ifeq ($(ENVIRONMENT),Darwin)
	@echo -e "$(YELLOW)Suppression des conteneurs sur Mac...$(RESET)"
	@if [ -f docker-compose-sync.yml ]; then \
		$(COMPOSE_MAC) rm -f; \
		if command -v mutagen >/dev/null 2>&1; then \
			echo -e "$(YELLOW)Arrêt de la synchronisation Mutagen...$(RESET)"; \
			mutagen sync terminate $(MUTAGEN_NAME) 2>/dev/null || true; \
		else \
			echo -e "$(YELLOW)Mutagen non installé. Ignoré.$(RESET)"; \
		fi \
	else \
		$(COMPOSE) rm -f; \
	fi
else
	@echo -e "$(YELLOW)Suppression des conteneurs...$(RESET)"
	$(COMPOSE) rm -f
endif
	@echo -e "$(GREEN)Les conteneurs ont été supprimés avec succès$(RESET)"

ssh:
	$(EXEC) bash

phpmyadmin:
	@echo -e "$(BLUE)PhpMyAdmin est disponible à l'adresse: http://localhost:8080$(RESET)"
	@echo -e "$(BLUE)Serveur: database$(RESET)"
	@echo -e "$(BLUE)Utilisateur: symfony$(RESET)"
	@echo -e "$(BLUE)Mot de passe: symfony$(RESET)"

reset-db:
	@echo -e "$(YELLOW)Suppression de la base de données...$(RESET)"
	$(CONSOLE) doctrine:database:drop --force --if-exists
	@echo -e "$(YELLOW)Création de la base de données...$(RESET)"
	$(CONSOLE) doctrine:database:create --if-not-exists
	@echo -e "$(YELLOW)Exécution des migrations...$(RESET)"
	$(CONSOLE) doctrine:migrations:migrate -n
	@echo -e "$(YELLOW)Chargement des fixtures...$(RESET)"
	$(CONSOLE) doctrine:fixtures:load -n
	@echo -e "$(GREEN)Base de données réinitialisée avec succès$(RESET)"

# ====================================
# = COMMANDES SYMFONY                =
# ====================================

# Installation des dépendances
install:
	$(EXEC) composer install

# Mise à jour des dépendances
update:
	$(EXEC) composer update

# Cache clear
cc:
	$(CONSOLE) cache:clear
	@echo -e "$(GREEN)Cache vidé avec succès$(RESET)"

# Assets install
assets:
	$(CONSOLE) assets:install --symlink
	@echo -e "$(GREEN)Assets installés avec succès$(RESET)"

# Création d'entité
entity:
	$(CONSOLE) make:entity

# Création de migration
migration:
	$(CONSOLE) make:migration

# Exécution des migrations
migrate:
	$(CONSOLE) doctrine:migrations:migrate -n
	@echo -e "$(GREEN)Migrations exécutées avec succès$(RESET)"

# Création d'un contrôleur
controller:
	$(CONSOLE) make:controller

# Création d'un form
form:
	$(CONSOLE) make:form

# Création de user
user:
	$(CONSOLE) make:user

# Création d'auth
auth:
	$(CONSOLE) make:auth

# Création d'un voter
voter:
	$(CONSOLE) make:voter

# Création d'un repository
repo:
	$(CONSOLE) make:repository

# Création d'un subscriber
subscriber:
	$(CONSOLE) make:subscriber

# Création de fixtures
fixture:
	$(CONSOLE) make:fixture

# Chargement des fixtures
load-fixtures:
	$(CONSOLE) doctrine:fixtures:load -n
	@echo -e "$(GREEN)Fixtures chargées avec succès$(RESET)"

# Création d'un CRUD
crud:
	$(CONSOLE) make:crud

# Exécution des tests
tests:
	$(EXEC) php bin/phpunit

# ====================================
# = COMMANDES UTILITAIRES           =
# ====================================

# Commande pour linter le code
lint:
	$(EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff

# Commande pour corriger le code selon les règles de coding style
fix:
	$(EXEC) vendor/bin/php-cs-fixer fix

# Analyse statique du code
stan:
	$(EXEC) vendor/bin/phpstan analyse src

# Redémarrage complet
restart: stop up

# Logs
logs:
	$(COMPOSE) logs -f

logs-php:
	$(COMPOSE) logs -f php

logs-nginx:
	$(COMPOSE) logs -f nginx

logs-db:
	$(COMPOSE) logs -f database

# Configuration de Mutagen pour Mac
setup-mac:
	@if [ "$(ENVIRONMENT)" != "Darwin" ]; then \
		echo -e "$(RED)Cette commande n'est disponible que sur Mac.$(RESET)"; \
		exit 1; \
	fi
	@echo -e "$(BLUE)Configuration de Mutagen pour Mac...$(RESET)"
	@if [ ! -f docker-compose-sync.yml ]; then \
		echo -e "$(YELLOW)Création de docker-compose-sync.yml pour Mac...$(RESET)"; \
		cat > docker-compose-sync.yml << 'EOF' \
services:\n\
  php:\n\
    volumes:\n\
      - app-sync:/var/www/symfony:delegated\n\
\n\
  nginx:\n\
    volumes:\n\
      - app-sync:/var/www/symfony:delegated\n\
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro\n\
\n\
volumes:\n\
  app-sync:\n\
    external: true\n\
EOF
	else \
		echo -e "$(YELLOW)Le fichier docker-compose-sync.yml existe déjà.$(RESET)"; \
	fi
	@echo -e "$(GREEN)Configuration de Mutagen terminée. Utilisez 'make up' pour démarrer l'application.$(RESET)"

# Création d'un .env.local
env:
	@if [ ! -f .env.local ]; then \
		echo -e "$(YELLOW)Création du fichier .env.local...$(RESET)"; \
		cat > .env.local << 'EOF' \
DATABASE_URL="mysql://symfony:symfony@database:3306/symfony?serverVersion=8.0"\n\
APP_SECRET="`openssl rand -hex 16`"\n\
EOF
		echo -e "$(GREEN)Fichier .env.local créé avec succès.$(RESET)"; \
	else \
		echo -e "$(YELLOW)Le fichier .env.local existe déjà.$(RESET)"; \
	fi

# Installation des commandes qualité
setup-quality:
	@echo -e "$(BLUE)Installation des outils de qualité de code...$(RESET)"
	$(EXEC) composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer phpunit/phpunit
	@echo -e "$(GREEN)Outils de qualité installés avec succès.$(RESET)"

# Aide
help:
	@echo -e "$(BLUE)Commandes disponibles:$(RESET)"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "$(BLUE)= COMMANDES DOCKER                  =$(RESET)"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "  $(GREEN)make up              $(RESET)- Démarrer les conteneurs"
	@echo -e "  $(GREEN)make stop            $(RESET)- Arrêter les conteneurs"
	@echo -e "  $(GREEN)make rm              $(RESET)- Supprimer les conteneurs"
	@echo -e "  $(GREEN)make ssh             $(RESET)- Se connecter au conteneur PHP"
	@echo -e "  $(GREEN)make restart         $(RESET)- Redémarrer les conteneurs"
	@echo -e "  $(GREEN)make logs            $(RESET)- Afficher les logs de tous les conteneurs"
	@echo -e "  $(GREEN)make logs-php        $(RESET)- Afficher les logs du conteneur PHP"
	@echo -e "  $(GREEN)make logs-nginx      $(RESET)- Afficher les logs du conteneur Nginx"
	@echo -e "  $(GREEN)make logs-db         $(RESET)- Afficher les logs du conteneur MySQL"
	@echo -e "  $(GREEN)make phpmyadmin      $(RESET)- Afficher les informations de connexion à PhpMyAdmin"
	@echo -e "  $(GREEN)make setup-mac       $(RESET)- Configurer Mutagen pour Mac"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "$(BLUE)= COMMANDES SYMFONY                 =$(RESET)"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "  $(GREEN)make install         $(RESET)- Installer les dépendances Composer"
	@echo -e "  $(GREEN)make update          $(RESET)- Mettre à jour les dépendances Composer"
	@echo -e "  $(GREEN)make cc              $(RESET)- Vider le cache"
	@echo -e "  $(GREEN)make assets          $(RESET)- Installer les assets"
	@echo -e "  $(GREEN)make entity          $(RESET)- Créer une entité"
	@echo -e "  $(GREEN)make migration       $(RESET)- Créer une migration"
	@echo -e "  $(GREEN)make migrate         $(RESET)- Exécuter les migrations"
	@echo -e "  $(GREEN)make controller      $(RESET)- Créer un contrôleur"
	@echo -e "  $(GREEN)make form            $(RESET)- Créer un formulaire"
	@echo -e "  $(GREEN)make user            $(RESET)- Créer un User"
	@echo -e "  $(GREEN)make auth            $(RESET)- Configurer l'authentification"
	@echo -e "  $(GREEN)make voter           $(RESET)- Créer un voter"
	@echo -e "  $(GREEN)make repo            $(RESET)- Créer un repository"
	@echo -e "  $(GREEN)make subscriber      $(RESET)- Créer un subscriber"
	@echo -e "  $(GREEN)make fixture         $(RESET)- Créer des fixtures"
	@echo -e "  $(GREEN)make load-fixtures   $(RESET)- Charger les fixtures"
	@echo -e "  $(GREEN)make crud            $(RESET)- Générer un CRUD"
	@echo -e "  $(GREEN)make tests           $(RESET)- Exécuter les tests"
	@echo -e "  $(GREEN)make reset-db        $(RESET)- Réinitialiser la base de données"
	@echo -e "  $(GREEN)make env             $(RESET)- Créer un fichier .env.local"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "$(BLUE)= COMMANDES QUALITÉ                =$(RESET)"
	@echo -e "$(BLUE)=====================================$(RESET)"
	@echo -e "  $(GREEN)make setup-quality   $(RESET)- Installer les outils de qualité de code"
	@echo -e "  $(GREEN)make lint            $(RESET)- Vérifier le code"
	@echo -e "  $(GREEN)make fix             $(RESET)- Corriger le code selon les règles de style"
	@echo -e "  $(GREEN)make stan            $(RESET)- Analyse statique du code"

.PHONY: up stop rm ssh install update cc assets entity migration migrate controller form user auth voter repo subscriber fixture load-fixtures crud tests reset-db lint fix stan restart logs logs-php logs-nginx logs-db phpmyadmin setup-mac env setup-quality help
