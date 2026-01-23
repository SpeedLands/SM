import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: [
                'favicon.ico',
                'favicon.svg',
                'apple-touch-icon.png'
            ],
            manifest: {
                name: 'SM Application',
                short_name: 'SM',
                start_url: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#0ea5a4',
                icons: [
                    { src: '/apple-touch-icon.png', sizes: '192x192', type: 'image/png' },
                    { src: '/apple-touch-icon.png', sizes: '512x512', type: 'image/png' }
                ]
            },
            workbox: {
                runtimeCaching: [
                    {
                        urlPattern: /\/api\//,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                        }
                    },
                    {
                        urlPattern: /\/(css|js)\//,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'static-resources',
                        }
                    }
                ]
            }
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});