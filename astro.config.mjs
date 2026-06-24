// @ts-check
import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
  site: 'https://gentlebeam.it',
  output: 'static',
  build: {
    format: 'directory',
  },
  // La home reindirizza a /test, l'URL che il centro comunica alle clienti.
  redirects: {
    '/': '/test',
  },
});
