import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  base: '/cos30043/s106200220/Project/',
  plugins: [vue()],
  server: {
    proxy: {
      '/resources': {
        target: 'http://localhost/Project', // Address of your PHP server
        changeOrigin: true,
        secure: false
      }
    }
  }
})
