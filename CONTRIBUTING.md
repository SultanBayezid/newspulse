# Contributing to NewsPulse

Thank you for considering a contribution - open source makes publishing better for everyone.

## Getting started

1. Fork the repository and clone your fork.
2. Set up the development environment (see the README "Quick start" section).
3. Create a branch for your change: `git checkout -b feature/my-feature`.

## Development workflow

- **Backend code style** follows Laravel conventions. Run the formatter before committing:
  ```bash
  cd backend && ./vendor/bin/pint
  ```
- **Frontend** uses the Vue 3 `<script setup>` style with Tailwind utility classes.
- Keep components small and focused; reuse `src/components/ui` primitives where possible.
- All user-facing strings go through `vue-i18n` - add them to both `en.js` and `bn.js`.

## Tests

Add or update tests for every change in behaviour:

```bash
cd backend
php artisan test
```

Feature tests live in `backend/tests/Feature` and run against an in-memory SQLite database,
so they are fast and need no external services.

## Pull requests

1. Make sure the test suite and the frontend build pass:
   ```bash
   cd frontend && npm run build
   ```
2. Keep pull requests focused - one feature or fix per PR.
3. Describe what changed and why, and link any related issues.
4. Do not bump versions in your PR; releases are handled by the maintainer.

## Reporting bugs

Open an issue with:
- Steps to reproduce
- Expected and actual behaviour
- PHP, Node and browser versions
- Relevant log output (`backend/storage/logs/laravel.log`)

## Feature requests

Feature requests are welcome. Before opening one, check the roadmap in the README and the
existing issues to avoid duplicates. The more concrete the use case, the better.

## Code of conduct

Be respectful and constructive. Harassment and personal attacks are not tolerated.
