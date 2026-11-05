import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'

// Dev: Vite serves the SPA and proxies API/session/storage requests to Laravel,
// so cookies are same-origin and Sanctum SPA auth works without CORS tricks.
// Build: assets go to Laravel's public/spa, and Laravel serves index.html with
// server-rendered SEO tags (see backend SpaController). No Node server in production.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const backend = env.VITE_DEV_BACKEND_URL || 'http://127.0.0.1:8000'

  return {
    plugins: [react(), tailwindcss()],
    base: mode === 'production' ? '/spa/' : '/',
    resolve: {
      alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
    },
    server: {
      port: 5173,
      proxy: {
        '/api': backend,
        '/sanctum': backend,
        '/storage': backend,
        '/robots.txt': backend,
        '/sitemap.xml': backend,
      },
    },
    // Asset URLs are resolved at runtime against window.__APP_BASE__ (set by Laravel), so the
    // same build works at a domain root or in a sub-folder. HTML tags are rewritten server-side.
    experimental: {
      renderBuiltUrl(filename, { hostType }) {
        if (hostType === 'js') return { runtime: `(window.__APP_BASE__ || '') + ${JSON.stringify(`/spa/${filename}`)}` }
        if (hostType === 'css') return { relative: true }
        return undefined
      },
    },
    build: {
      outDir: '../backend/public/spa',
      emptyOutDir: true,
      sourcemap: false,
      chunkSizeWarningLimit: 700,
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (!id.includes('node_modules')) return undefined
            if (id.includes('swiper')) return 'swiper'
            // Anchored to node_modules so packages like @tiptap/react don't land in the core chunk.
            if (/node_modules[\\/](react|react-dom|react-router|react-router-dom|scheduler)[\\/]/.test(id)) return 'react'
            if (/@tanstack|axios/.test(id)) return 'data'
            if (/react-hook-form|zod|@hookform/.test(id)) return 'forms'
            return undefined
          },
        },
      },
    },
  }
})
