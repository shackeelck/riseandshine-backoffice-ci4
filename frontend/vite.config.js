import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: '../public/',
    emptyOutDir: false,
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    proxy: {
      '/api': 'http://localhost:8080',
      '/assets': 'http://localhost:8080'  
    }
  }
})
