import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    // APP_URL 에서 호스트 추출 (예: http://192.168.0.187:7700 → 192.168.0.187)
    let hmrHost = 'localhost';
    try {
        const appUrl = env.APP_URL || '';
        if (appUrl) {
            hmrHost = new URL(appUrl).hostname;
        }
    } catch (_) {}

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.js'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
        server: {
            host: '0.0.0.0',   // 모든 네트워크 인터페이스에서 수신
            hmr: {
                host: hmrHost, // HMR 웹소켓도 실제 IP로 연결
            },
        },
    };
});
