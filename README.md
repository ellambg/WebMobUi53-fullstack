# Application de sondage — WebMobUI HEIG-VD

Ce projet est réalisé dans le cadre du cours _Web & Mobile UI_ enseigné à la
[Haute Ecole d'Ingénierie et de Gestion du Canton de Vaud (HEIG-VD)](https://heig-vd.ch), Suisse.

L'objectif est de créer une application fullstack de sondage mêlant backend Laravel et frontend Vue.js.

## Stack technique

- Backend : Laravel 12
- Frontend : Vue.js 3 (Composition API)
- Base de données : SQLite
- Build : Vite
- Graphiques : Chart.js

## Prérequis

- PHP 8.4+
- Composer
- Node.js 22+
- npm

## Installation

```bash
git clone git@github.com:ellambg/WebMobUi53-fullstack.git
cd WebMobUi53-fullstack
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

## Lancer l'application

Dans deux terminaux séparés :

```bash
# Terminal 1 — Backend
php artisan serve

# Terminal 2 — Frontend
npm run dev
```

L'application est accessible sur **http://127.0.0.1:8000**

## Utilisation

1. Créer un compte via **/auth/register**
2. Se connecter via **/auth/login**
3. Accéder au dashboard via **/polls/dashboard**
4. Créer un sondage et copier le lien de partage
5. Partager le lien — toute personne connectée peut voter

## Fonctionnalités

- Dashboard des sondages (créer, modifier, supprimer)
- Lien de partage par token unique
- Page de vote accessible via token
- Résultats en temps réel (polling toutes les 5 secondes)
- Graphique en barres des résultats
- Sondages en brouillon ou lancés
- Choix simple ou multiple
- Résultats publics ou privés
- Durée configurable en secondes

## Architecture frontend

- `AppPollDashboard.vue` — application Vue du dashboard
- `AppPollVote.vue` — application Vue de la page de vote
- `components/PollTable.vue` — tableau des sondages
- `components/PollForm.vue` — formulaire création/édition
- `components/PollChart.vue` — graphique des résultats
- `stores/usePollStore.js` — store réactif des sondages
- `composables/useFetchApi.js` — composable pour les appels API
- `composables/usePolling.js` — composable pour le polling

## API endpoints

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | /api/v1/polls | Liste des sondages de l'utilisateur |
| POST | /api/v1/polls | Créer un sondage |
| PUT | /api/v1/polls/{id} | Modifier un sondage |
| DELETE | /api/v1/polls/{id} | Supprimer un sondage |
| GET | /api/v1/polls/{token} | Afficher un sondage |
| POST | /api/v1/polls/{token}/vote | Voter |
| GET | /api/v1/polls/{token}/results | Résultats |
