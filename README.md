# GLesCrocs – Gestion de file d’attente pour cantine

## Présentation

GLesCrocs est une application web de gestion de file d’attente pour une cantine scolaire.

L’objectif est d’améliorer l’expérience côté client (visibilité sur l’attente, file lisible, notifications) et de simplifier le travail côté service (suivi des commandes, avancement de la file, mise à jour du menu du jour).

Projet réalisé en solo, avec une organisation agile (Trello) et une gestion de version Git (GitHub).

## Fonctionnalités

### Côté client

* Suivi de la file d’attente en temps réel
* Affichage du numéro en cours de service
* Possibilité de suivre son numéro de ticket
* Notification lorsque la commande est prête
* Interface responsive, pensée pour mobile (accès via lien ou QR code)

### Côté administrateur

* Authentification (sessions PHP)
* Gestion du menu du jour (service du midi uniquement)
* Création de commandes (prise en compte et attribution d’un numéro)
* Changement d’état d’une commande : validée, prête, payée
* Avancement de la file d’attente
* Possibilité de correction manuelle en cas de problème (réorganisation, suppression, passage)
* Accès à un espace statistiques (prévu)

### Cycle de vie d’une commande

Le projet distingue clairement les états suivants :

* **VALIDATED** : commande prise en compte et ajoutée à la file d’attente
* **READY** : repas prêt, notification envoyée au client
* **PAID** : commande terminée (repas récupéré)

Ces états permettent un suivi clair de la file d’attente et servent de base au calcul des statistiques.

## Architecture technique

Le projet repose sur une architecture hybride :

* Backend métier : PHP natif + MySQL

  * API REST
  * logique métier (commandes, menu, file)
  * authentification admin (sessions)

* Temps réel : Node.js + Express + Socket.io

  * service dédié aux communications temps réel
  * diffusion des mises à jour de file et notifications

* Frontend : React (Vite)

  * interface client et interface admin
  * appels HTTP vers l’API PHP
  * connexion Socket.io pour les mises à jour en temps réel

Ce choix permet de garder la logique métier en PHP (choix du projet) tout en s’appuyant sur Socket.io (écosystème Node) pour la partie temps réel.

## Fonctionnement du temps réel

Principe :

1. L’administrateur effectue une action (ex. commande servie).
2. Le frontend appelle l’API PHP.
3. Le backend PHP met à jour la base de données.
4. Le backend PHP notifie le service Socket (requête HTTP interne).
5. Le service Socket.io émet un événement vers les clients connectés.
6. Les interfaces se mettent à jour automatiquement.

## Base de données

La base de données est relationnelle (MySQL) et conçue pour un **service unique le midi**.

Entités principales :

* `admins`
* `menus`
* `menu_items`
* `orders`
* `order_items`
* `queue_state`

La file d’attente est gérée via :

* un numéro de ticket attribué à chaque commande
* une table `queue_state` indiquant le numéro actuellement servi pour une date donnée

Les commandes contiennent plusieurs timestamps afin de permettre des analyses ultérieures :

* `validated_at` : commande prise en compte
* `ready_at` : repas prêt
* `paid_at` : commande terminée

Ces champs permettent de calculer des indicateurs tels que le temps d’attente ou la vitesse de préparation.

## API (prévision)

Routes prévues (à ajuster selon l’implémentation finale) :

* POST /api/auth/login
* POST /api/auth/logout
* GET /api/menu/today
* PUT /api/menu/today (admin)
* POST /api/orders (admin)
* GET /api/orders
* PATCH /api/orders/{id}/ready (admin)
* PATCH /api/orders/{id}/served (admin)
* PATCH /api/queue/current (admin)

## Organisation du dépôt

```
GLesCrocs/
  backend-php/      API REST PHP natif
  socket-server/    Service Node.js + Socket.io (temps réel)
  frontend/         Application React (client + admin)
  docs/             Documentation (MCD, API, captures)
  .github/          Workflows CI (GitHub Actions)
  README.md
```

## Installation et lancement (développement local)

Le projet est composé de trois services distincts qui doivent être lancés séparément :

* backend PHP (API REST)
* serveur Socket.io (temps réel)
* frontend React (Vite)

### Pré-requis

* PHP 8+
* MySQL
* Node.js 18+
* npm

### 1. Cloner le projet

```bash
git clone https://github.com/prenom-nom/GLesCrocs.git
cd GLesCrocs
```

### 2. Configuration des variables d’environnement

#### Backend PHP

```bash
cd backend
cp .env.example .env
```

Renseigner dans `.env` les accès à la base de données MySQL ainsi que l’URL du serveur Socket.

#### Serveur Socket

```bash
cd socket-server
cp .env.example .env
```

Renseigner le port et le token d’émission si nécessaire.

### 3. Installation et lancement du frontend (React + Vite)

```bash
cd frontend
npm install
npm run dev
```

Le frontend est accessible par défaut sur :
`http://localhost:5173`

### 4. Installation et lancement du serveur Socket.io

```bash
cd socket-server
npm install
npm run dev
```

Le serveur Socket écoute par défaut sur :
`http://localhost:3001`

### 5. Lancement du backend PHP

```bash
cd backend/public
php -S localhost:8000
```

L’API PHP est accessible sur :
`http://localhost:8000`

### 6. Résumé des services actifs

| Service           | URL                                            |
| ----------------- | ---------------------------------------------- |
| Frontend (React)  | [http://localhost:5173](http://localhost:5173) |
| Backend API (PHP) | [http://localhost:8000](http://localhost:8000) |
| Socket server     | [http://localhost:3001](http://localhost:3001) |

### Remarques

* Les trois services doivent être lancés simultanément en développement.
* Le backend PHP communique avec le serveur Socket via HTTP pour déclencher les événements temps réel.
* Le frontend consomme l’API PHP et se connecte directement au serveur Socket.io.

## CI (GitHub Actions)

Un pipeline d’intégration continue est mis en place :

* déclenché lors d’un push sur la branche principale
* envoie une notification (service à définir : Google Chat, Discord, etc.)

## Statistiques (prévision)

Un module de statistiques est prévu dans le dashboard administrateur.

Indicateurs envisagés :

* Nombre de commandes par jour ou période
* Panier moyen
* Temps moyen de préparation (validated → ready)
* Temps moyen total (validated → paid)
* Heures de pointe (répartition par heure)
* Plats les plus demandés

Ces statistiques seront calculées à partir des données enregistrées lors du service du midi.

## Roadmap (2 semaines)

* Sprint 0 : préparation (Trello, architecture, base de données, structure projet)
* Sprint 1 : socle fonctionnel (API PHP, tickets, file, socket server, page client)
* Sprint 2 : finalisation (dashboard admin, statistiques, corrections de file, CI, documentation, qualité)

## Auteur

Projet réalisé par Armelle dans le cadre d’un projet scolaire.

## Statut

En cours de développement.
