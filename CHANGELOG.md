# Changelog

All notable changes to NewsPulse are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-12

### Added - platform
- Initial public release of NewsPulse, an open-source news publishing platform.
- Laravel 12 REST API (`/api/v1`) with Sanctum token authentication.
- Vue 3 single-page application with Tailwind CSS 4, Pinia and Vue Router.
- Bilingual interface: English and Bangla, with a per-article language.
- Dark mode, responsive layout, RSS feed and XML sitemap.
- Docker Compose stack (Nginx, PHP-FPM, MySQL, Redis, queue, scheduler).
- GitHub Actions CI with a PHPUnit matrix and frontend build.
- 61 automated tests covering auth, workflow, moderation, engagement and admin.

### Added - editorial
- Article management with draft / review / scheduled / published / archived workflow.
- Automatic publishing of scheduled articles through the artisan scheduler.
- Rich-text editor with HTML source mode and whitelist sanitization.
- Media library with uploads, alt text, captions and credits.
- Categories (hierarchical, colour-coded), tags and static pages.
- Comment moderation queue with approve / reject / delete actions.
- Reader polls, newsletter double opt-in with CSV export, activity log.
- Role-based access control: super admin, editor, author, subscriber.

### Added - reading
- Homepage with featured hero, breaking ticker, trending sidebar and category sections.
- Search with filters, category/tag archives, author profiles, bookmarks.
- View counters, daily analytics charts and most-read rankings.

[1.0.0]: https://github.com/SultanBayezid/newspulse/releases/tag/v1.0.0
