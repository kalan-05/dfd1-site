// vite.config.js
// [CHANGE] Добавлен proxy, чтобы все запросы к /php/* шли на PHP-сервер (localhost:5174)

import path from 'path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: {
			'@': path.resolve(__dirname, 'src'),
		},
	},
	server: {
		proxy: {
			// [CHANGE] Проксируем все PHP-роуты на встроенный PHP-сервер
			'/php': {
				target: 'http://localhost:5174',
				changeOrigin: true,
				// [NO-CHANGE] Путь оставляем как есть: /php/send-review.php
				rewrite: p => p,
			},
		},
	},
})
