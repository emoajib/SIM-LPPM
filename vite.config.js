import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
	plugins: [
		laravel({
			input: ["resources/css/app.css", "resources/js/app.js", "resources/js/theme-config.js", "resources/js/modern.jsx"],
			refresh: true,
		}),
		react(),
	],
	server: {
		cors: true,
	},
});
