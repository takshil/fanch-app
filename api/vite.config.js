import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Note : la fonctionnalité "fonts" (bunny/google) télécharge des
            // polices distantes pendant `vite build` et fait échouer le build
            // en CI si le réseau n'est pas joignable. On la retire : l'API
            // sert du JSON, la page d'accueil Laravel utilise la pile de
            // polices système en repli (voir resources/css/app.css).
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
