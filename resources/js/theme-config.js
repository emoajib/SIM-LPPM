/**
 * Default theme configuration
 */
const THEME_CONFIG = {
	'theme-base': 'neutral',
	'theme-font': 'sans-serif',
	'theme-primary': 'green',
	'theme-radius': '1',
};

/**
 * Theme Manager Class
 */
class ThemeManager {
	constructor(config) {
		this.config = config;
		this.form = document.getElementById('offcanvasSettings');
		this.resetButton = document.getElementById('reset-changes');
		this.url = new URL(window.location);

		this.initialize();
	}

	/**
	 * Initialize theme manager
	 */
	initialize() {
		// Parse URL parameters first (highest priority)
		this.parseUrlParams();

		// Load saved settings from localStorage (if not overridden by URL)
		this.loadSettings();

		// Setup event listeners
		this.setupEventListeners();
	}

	/**
	 * Parse URL parameters and apply theme settings
	 */
	parseUrlParams() {
		const urlParams = new URLSearchParams(window.location.search);

		urlParams.forEach((value, key) => {
			if (key in this.config) {
				this.setThemeAttribute(key, value);
			}
		});
	}

	/**
	 * Set theme attribute on document element
	 */
	setThemeAttribute(key, value) {
		if (value) {
			document.documentElement.setAttribute(`data-bs-${key}`, value);
			window.localStorage.setItem(`tabler-${key}`, value);
		}
	}

	/**
	 * Remove theme attribute from document element
	 */
	removeThemeAttribute(key) {
		document.documentElement.removeAttribute(`data-bs-${key}`);
		window.localStorage.removeItem(`tabler-${key}`);
		this.url.searchParams.delete(key);
	}

	/**
	 * Load settings from localStorage and update form
	 */
	loadSettings() {
		for (const key in this.config) {
			// Check if already set by URL params
			const currentValue = document.documentElement.getAttribute(`data-bs-${key}`);

			if (!currentValue) {
				// Only load from localStorage if not already set
				const savedValue = window.localStorage.getItem(`tabler-${key}`);
				const value = savedValue ?? this.config[key];

				if (value) {
					this.setThemeAttribute(key, value);
				}
			}

			// Update form controls if form exists
			if (this.form) {
				const finalValue = document.documentElement.getAttribute(`data-bs-${key}`) || this.config[key];
				const radios = this.form.querySelectorAll(`[name="${key}"]`);
				radios.forEach((radio) => {
					radio.checked = radio.value === finalValue;
				});
			}
		}
	}

	/**
	 * Setup event listeners for form and reset button
	 */
	setupEventListeners() {
		// Handle form changes
		if (this.form) {
			this.form.addEventListener('change', (event) => {
				this.handleFormChange(event);
			});
		}

		// Handle reset button
		if (this.resetButton) {
			this.resetButton.addEventListener('click', () => {
				this.resetTheme();
			});
		}
	}

	/**
	 * Handle form change event
	 */
	handleFormChange(event) {
		const { name, value } = event.target;

		if (name in this.config) {
			this.setThemeAttribute(name, value);
			this.url.searchParams.set(name, value);
			window.history.pushState({}, '', this.url);
		}
	}

	/**
	 * Reset theme to defaults
	 */
	resetTheme() {
		for (const key in this.config) {
			this.removeThemeAttribute(key);
		}

		// Reload form controls with defaults
		this.loadSettings();

		// Update URL
		window.history.pushState({}, '', this.url);
	}
}

/**
 * Initialize theme manager when DOM is ready
 */
const initializeTheme = () => {
	new ThemeManager(THEME_CONFIG);
};

document.addEventListener('livewire:navigated', initializeTheme);
