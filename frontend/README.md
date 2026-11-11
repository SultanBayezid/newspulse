# NewsPulse Frontend

The Vue 3 single-page application for NewsPulse, an open-source news publishing platform.

## Stack

- Vue 3.5 (`<script setup>`)
- Vite 8
- Pinia (state) and Vue Router 4
- Tailwind CSS 4
- Vue I18n (English / Bangla)

## Development

```bash
npm install
npm run dev      # http://localhost:5173 (proxies /api to the Laravel backend)
```

Point the proxy target at your backend URL in `vite.config.js` if it does not run on
`http://localhost:8000`.

## Production build

```bash
npm run build    # outputs to dist/
```

See the repository root README for the full platform documentation, or the Dockerfile in
this directory for the container build.

## License

MIT - (c) 2026 Sultan Bayezid
