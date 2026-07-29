import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// `base: './'` keeps every emitted asset URL relative, so the built bundle can be
// dropped into a WordPress theme directory and enqueued from any path.
export default defineConfig({
  base: './',
  plugins: [react()],
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    cssCodeSplit: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom'],
          motion: ['framer-motion'],
        },
      },
    },
  },
});
