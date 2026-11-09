# ETS – Réservation de sessions de tests de langues

Application web : **Symfony 7** (API REST + JWT) · **Next.js 14 / React** · **MongoDB 7** · Docker.

```
.
├── backend/              API Symfony (Doctrine MongoDB ODM, LexikJWT, PHPUnit)
├── frontend/             Next.js (App Router, TypeScript, Axios, Jest)
├── docker-compose.yml
└── .env.example
```

## Avant lancemet docker

Copiez le fichier .env.example en .env pour surcharger les valeurs par défaut de docker-compose.yml

## Lancement (Docker)

```bash
docker compose up --build
```

- Frontend : http://localhost:3000
- API : http://localhost:8001/api
- Au démarrage, le backend génère les clés JWT, crée les index MongoDB et insère des données de démo.

Comptes de démo :

| Rôle  | Email                    | Mot de passe |
|-------|--------------------------|--------------|
| Admin | admin@ets-test.com       | Hello@ETS    |
| User  | s.hamouti@ets-test.com   | Hello@ETS    |

Vous pouvez aussi créer un compte via la page de connexion.

## Tests

```bash
# Backend (PHPUnit, base MongoDB dédiée `ets_emea_test`)
docker compose exec backend vendor/bin/phpunit

# Frontend (Jest + React Testing Library)
cd frontend && npm install && npm test
```

## API

Toutes les routes sauf `register` et `login` exigent `Authorization: Bearer <jwt>`. Les erreurs sont en JSON (`{"error": "...", "violations": {...}}`).

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/api/register` | Inscription, renvoie `{token, user}` |
| POST | `/api/login` | `{email, password}` → `{token}` |
| GET / PUT | `/api/me` | Profil / modification nom + email (renvoie un nouveau token) |
| GET | `/api/sessions?page=&limit=` | Liste paginée (+ champ `booked` pour l'utilisateur) |
| GET | `/api/sessions/{id}` | Détail |
| POST / PUT / DELETE | `/api/sessions[/{id}]` | CRUD, réservé à `ROLE_ADMIN` |
| POST | `/api/sessions/{id}/reservations` | Réserver (409 si complet ou déjà réservé) |
| GET | `/api/reservations` | Mes réservations |
| DELETE | `/api/reservations/{id}` | Annuler (403 si ce n'est pas la mienne) |

## Choix techniques

- **Collections MongoDB** : `users`, `sessions`, `reservations`. Index unique sur `users.email` et sur `reservations(userId, sessionId)`.
- **Sécurité** : mots de passe hashés (`auto`), firewall stateless JWT, validation via `symfony/validator` (DTO + `#[MapRequestPayload]`), CORS restreint, écriture sur les sessions réservée aux admins.
- **Token côté frontend** : stocké dans `localStorage` (simple, mais exposé en cas de XSS). En production, privilégier un cookie `httpOnly` + protection CSRF.
- Le serveur PHP intégré utilisé dans Docker est destiné au développement/démo ; en production, utiliser nginx + php-fpm.

## Auteur

Soufiane HAMOUTI
