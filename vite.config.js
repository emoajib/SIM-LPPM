import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import { defineConfig } from "vite";

export default defineConfig({
	plugins: [
		laravel({
			input: ["resources/css/app.css", "resources/js/app.js", "resources/js/theme-config.js", "resources/js/modern.jsx", "resources/css/modern.css"],
			refresh: true,
		}),
		react(),
		tailwindcss(),
	],
	server: {
		cors: true,
	},
});
