# Libreria-web

Online bookstore built on WordPress + WooCommerce, plus a Laravel integration engine that connects the store with an external service. Course project for ITI-621 Web Technologies and Systems III (UTN, San Carlos).

## Business

- **Name:** "LibroLocura"
- **Type:** Bookstore (Northern Zone, Costa Rica)
- **Public store URL:** _not yet_

## Team

| Member | GitHub | Role |
|---|---|---|
| Luis Alejandro Arce Araya | [@Alejandro220106](https://github.com/Alejandro220106) | Project manager |
| Jocelyn Daniela Carballo Castillo | [@Jocelyn300698](https://github.com/Jocelyn300698) | Lead developer |
| Daniel Rivera Miranda | [@Danirimi](https://github.com/Danirimi) | Team leader |
| Jose Andrés Solís Gamboa | [@Croketis](https://github.com/Croketis) | QA |

## Repository structure

```
tienda/themes/    child theme
tienda/plugins/   custom plugin
motor/            Laravel integration engine
docs/             project documents
postman/          Postman collections and example environments
```

## Running the engine

The engine is a Laravel 13 project on PHP 8.3 in `motor/`. Requirements: PHP 8.3 and Composer. From the `motor/` folder run these steps, and no others:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

- `.env.example` lists every variable the engine needs. Secrets are left empty: fill in your own WooCommerce keys, webhook secret and connector token in `.env`. Never commit `.env`.
- The engine is not deployed in Deliverable 2. To receive the store's webhooks on a local machine, expose it with a tunnel (Cloudflare Tunnel or ngrok) and point the two WooCommerce webhooks (order created, order updated) to `POST /api/v1/webhooks/woocommerce`.
- Tests run offline and without real keys:

```bash
php artisan test
```

The architecture and conventions are described in [docs/arquitectura.md](docs/arquitectura.md).

## Integrations

| Piece | Provider | Notes |
|---|---|---|
| Connector (messaging) | Mailtrap Email Sandbox | Sends an order notification to a test inbox. Emails are captured and never delivered, so no real personal data is used. Selected in #69. |
| AI model (address normalization) | _to be defined_ | Provider and model will be declared here. The API key goes only in `.env`. |

## Demo video

_Link pending._

## AI usage statement

The team uses an AI coding assistant (Claude Code) for planning the backlog, drafting documentation, writing and reviewing code. Its configuration lives in `CLAUDE.md` and `.claude/`. Every AI-assisted change is reviewed by a team member through a pull request, verified against the project brief and covered by tests where it touches the engine before merging. Each team member must be able to explain and modify the code they deliver. AI-generated images, if any, will be listed here.
