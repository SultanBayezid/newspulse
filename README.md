# NewsPulse

**A modern, open-source news publishing platform built with Laravel 12, Vue 3, Tailwind CSS and MySQL.**

NewsPulse is a complete newsroom in a box: a blazing-fast reading experience, a real editorial
workflow (draft → review → scheduled → published), a media library, comment moderation, polls,
a double opt-in newsletter, reader analytics and a full REST API - bilingual (English / Bangla)
out of the box and dark-mode everywhere.

| | |
|---|---|
| **Backend** | Laravel 12 (PHP 8.2+), Sanctum token auth, MySQL / SQLite / PostgreSQL |
| **Frontend** | Vue 3.5, Vite, Pinia, Vue Router, Tailwind CSS 4, Vue I18n |
| **License** | MIT - free for personal and commercial use |
| **Tests** | 61 automated tests (225 assertions) + production frontend build in CI |

---

## Features

### Reading experience
- Homepage with featured hero, breaking-news ticker, latest feed, trending sidebar and per-category sections
- Long-form article pages with reading-progress bar, estimated reading time, related stories and source attribution
- Full-text search with category, tag, language and sort filters
- Category and tag archives, public author profiles, static pages
- Reading analytics: view counters, daily view charts, trending (last 24h) and most-read rankings
- Dark mode, responsive design and a bilingual interface (English / বাংলা)
- RSS feed (`/feed`) and XML sitemap (`/sitemap.xml`)

### Reader engagement
- Accounts with email verification and password reset (all links point back to the SPA)
- Threaded comments with moderation queue and helpful-votes on comments and articles
- Bookmarks ("read later") with a personal reading list
- Reader polls with one-vote-per-user and live percentages
- Newsletter with double opt-in (subscribe → confirm → unsubscribe) and CSV subscriber export
- In-app notifications for authors and editors

### Editorial workspace (`/admin`)
- Dashboard with totals, 30-day views/articles charts, top articles and recent activity
- Article editor with rich-text toolbar (headings, lists, quotes, links, images, code), HTML source mode, tags, cover images, SEO fields and language selection
- Editorial workflow: authors submit for review, editors approve / reject with notes / schedule; scheduled articles publish automatically via an artisan scheduler
- Media library with uploads, alt text, captions and credits
- Categories (hierarchical, colour-coded) and tag manager
- Comment moderation: approve / reject / delete with filters
- Static page manager with the same rich-text editor
- Poll builder with live results
- Team management: roles (super admin, editor, author, subscriber), ban/unban, last-super-admin protection
- Site settings (name, tagline, social links, comments, registration, reading preferences)
- Activity log of every editorial action

### Platform
- REST API under `/api/v1` with token auth, pagination, validation and rate limiting
- Whitelist HTML sanitizer for all submitted content (XSS protection)
- Role-based access control with policies and a super-admin bypass
- Database queue, scheduled publishing, database notifications
- Docker Compose for one-command production-style deployments
- GitHub Actions CI (PHPUnit matrix + frontend build)

## Quick start (Docker)

```bash
git clone https://github.com/SultanBayezid/newspulse.git
cd newspulse
cp backend/.env.example backend/.env       # adjust MySQL credentials if you like
docker compose up -d --build
```

The stack boots Nginx (frontend + API gateway), PHP-FPM, MySQL, a queue worker and the
scheduler. The site is available at `http://localhost:8080`. Migrations run automatically on
boot; add `SEED_DEMO_DATA=1` to `backend/.env` before starting if you also want the demo
newsroom content.

## Quick start (local development)

Prerequisites: PHP 8.2+, Composer, Node.js 18+ and either SQLite (zero setup) or MySQL.

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # creates demo content
php artisan storage:link
php artisan serve               # http://localhost:8000

# 2. Frontend (second terminal)
cd frontend
npm install
npm run dev                     # http://localhost:5173
```

The Vite dev server proxies `/api/*` to the Laravel backend, so everything works out of the box.

### Demo accounts

The database seeder creates a complete demo newsroom. All accounts use the password `password`:

| Role | Email |
|---|---|
| Super Admin | `admin@newspulse.test` |
| Editor | `editor@newspulse.test` |
| Author | `author@newspulse.test` |
| Reader | `reader@newspulse.test` |

> Demo articles, comments, polls and subscribers are sample content - replace them with your own
> before going live.

## Running the tests

```bash
cd backend
php artisan test        # 61 tests, 225 assertions (SQLite in-memory)
```

Frontend production build:

```bash
cd frontend
npm run build
```

## Project structure

```
newspulse/
├── backend/                  # Laravel 12 REST API
│   ├── app/
│   │   ├── Console/Commands/ # Scheduled article publisher
│   │   ├── Enums/            # UserRole, ArticleStatus, CommentStatus
│   │   ├── Http/             # Controllers, Requests, Resources, Middleware
│   │   ├── Mail/             # Newsletter confirmation
│   │   ├── Models/           # 14 Eloquent models
│   │   ├── Notifications/    # Verification, reset, workflow, comments
│   │   ├── Policies/         # Role-based authorization
│   │   ├── Services/         # Settings, activity logging
│   │   └── Support/          # HTML sanitizer
│   ├── database/             # Migrations, factories, seeders
│   └── routes/               # api.php (v1), web.php (feeds)
├── frontend/                 # Vue 3 SPA (includes its nginx.conf for Docker)
│   └── src/
│       ├── admin/            # Admin shell, editor, charts, pickers
│       ├── api/              # Typed API client
│       ├── components/       # Layout, UI, article, home components
│       ├── i18n/             # English + Bangla translations
│       ├── stores/           # Pinia stores (auth, ui, notifications)
│       └── views/            # Public, auth, account, admin views
├── backend/                  # Laravel 12 REST API (Dockerfile + entrypoint)
├── docker-compose.yml
└── .github/workflows/        # CI
```

## API overview

All endpoints live under `/api/v1`. Authentication uses Laravel Sanctum bearer tokens
(`Authorization: Bearer <token>`).

| Area | Endpoints |
|---|---|
| Auth | `POST /auth/register`, `/auth/login`, `/auth/forgot-password`, `/auth/reset-password`, `/auth/email/verify`, `GET /auth/me`, `PUT /auth/profile` |
| Content | `GET /articles`, `/articles/trending`, `/articles/breaking`, `/articles/{slug}`, `/categories`, `/tags`, `/pages/{slug}`, `/authors/{id}`, `/settings` |
| Engagement | `POST /comments`, `/reactions/toggle`, `/bookmarks/toggle`, `/polls/{id}/vote`, `/newsletter/subscribe`, `/newsletter/confirm/{token}`, `/notifications` |
| Admin | `GET/POST/PUT/DELETE` under `/admin` for articles, categories, tags, media, pages, comments, polls, users, subscribers, settings, activity |

Paginated responses follow the standard Laravel paginator shape (`data`, `links`, `meta`).
Validation errors return HTTP 422 with a `message` and per-field `errors` object.

## Deployment

- **Docker Compose** is the recommended path: `docker compose up -d --build` gives you Nginx,
  PHP-FPM, MySQL, Redis, a queue worker and the scheduler with one command.
- For classic VPS deployments, point Nginx at `frontend/dist` (static) and proxy `/api`,
  `/storage`, `/feed` and `/sitemap.xml` to PHP-FPM. Register the cron entry
  `* * * * * php artisan schedule:run` and run `php artisan queue:work` under a process monitor.
- Set `FRONTEND_URL` and `CORS_ALLOWED_ORIGINS` in production so emails and CORS match your domain.

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for the development workflow and
[`SECURITY.md`](SECURITY.md) for responsible disclosure.

## Roadmap

- [ ] Campaign-style newsletter broadcasts with a visual composer
- [ ] Multi-language article translations linked as editions
- [ ] Image resizing and WebP conversion on upload
- [ ] Full-text search engines (Meilisearch / Algolia) as an optional driver
- [ ] Live blogging mode for major events

## Author

**Sultan Bayezid** - Senior Full Stack Software Engineer, Bangladesh

- Website: [https://lastlineweb.com](https://lastlineweb.com)
- GitHub: [@SultanBayezid](https://github.com/SultanBayezid)
- Email: sultanbayezidai@gmail.com

## License

Released under the MIT License - see [`LICENSE`](LICENSE). You are free to use, modify and
redistribute NewsPulse, including commercially. A credit link back to the project is appreciated
but not required.
