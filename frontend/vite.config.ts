import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true,
    // The dev server runs in a container with the source bind-mounted from the
    // Windows host. Native file events do not cross that boundary, so HMR misses
    // edits and the running app serves stale modules. Polling makes the watcher
    // detect saves reliably, at a small CPU cost.
    watch: {
      usePolling: true,
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
  },
})
