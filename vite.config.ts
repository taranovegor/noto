import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig(({ mode }) => ({
  plugins: [
    react(),
    symfonyPlugin(),
  ],
  base: '/build/',
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    sourcemap: mode !== 'production',
    rollupOptions: {
      input: {
        app: './assets/app.tsx',
      },
    },
  },
  css: {
    modules: {
      generateScopedName: '[name]_[local]_[hash:base64:4]',
    },
  },
  server: {
    origin: 'http://localhost:5173',
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: true,
  },
}));

