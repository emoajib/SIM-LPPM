import "@tabler/core/dist/libs/nouislider/dist/nouislider.min.js";
import TomSelect from "@tabler/core/dist/libs/tom-select/dist/js/tom-select.complete.js";
import * as Tabler from "@tabler/core/js/tabler";
import NProgress from "nprogress";
import "./theme-config";
import countdownTimer from "./countdown-timer";

window.tabler = Tabler;
window.bootstrap = Tabler.bootstrap;
// Make TomSelect available globally
window.TomSelect = TomSelect;

// NProgress Configuration
NProgress.configure({ showSpinner: false });
window.NProgress = NProgress;

/**
 * Configuration items for menu and layout settings
 */
const SETTINGS_CONFIG = {
    "menu-position": {
        localStorage: "tablerMenuPosition",
        default: "top",
    },
    "menu-behavior": {
        localStorage: "tablerMenuBehavior",
        default: "sticky",
    },
    "container-layout": {
        localStorage: "tablerContainerLayout",
        default: "boxed",
    },
};

/**
 * Settings Manager Class
 */
class SettingsManager {
    constructor(config) {
        this.config = config;
        this.settings = this.loadSettings();
    }

    /**
     * Load settings from localStorage or use defaults
     */
    loadSettings() {
        const settings = {};

        for (const [key, params] of Object.entries(this.config)) {
            const storedValue = localStorage.getItem(params.localStorage);
            settings[key] = storedValue ?? params.default;
        }

        return settings;
    }

    /**
     * Parse URL parameters and update settings
     */
    parseUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);

        urlParams.forEach((value, key) => {
            if (this.config[key]) {
                this.updateSetting(key, value);
            }
        });
    }

    /**
     * Update a single setting
     */
    updateSetting(key, value) {
        if (this.config[key]) {
            localStorage.setItem(this.config[key].localStorage, value);
            this.settings[key] = value;
        }
    }

    /**
     * Update form controls to reflect current settings
     */
    syncFormControls(form) {
        if (!form) return;

        for (const [key, value] of Object.entries(this.settings)) {
            const input = form.querySelector(
                `[name="settings-${key}"][value="${value}"]`,
            );

            if (input) {
                input.checked = true;
            }
        }
    }

    /**
     * Save settings from form
     */
    saveFromForm(form) {
        if (!form) return;

        for (const key of Object.keys(this.config)) {
            const checkedInput = form.querySelector(
                `[name="settings-${key}"]:checked`,
            );

            if (checkedInput) {
                this.updateSetting(key, checkedInput.value);
            }
        }

        // Trigger resize event for layout recalculation
        window.dispatchEvent(new Event("resize"));
    }
}

/**
 * Initialize settings functionality
 */
const initializeSettings = () => {
    const settingsManager = new SettingsManager(SETTINGS_CONFIG);
    const settingsForm = document.querySelector("#offcanvasSettings");

    // Parse URL parameters on load
    settingsManager.parseUrlParams();

    // Setup form if it exists
    if (settingsForm) {
        // Sync form controls with current settings
        settingsManager.syncFormControls(settingsForm);

        // Handle form submission
        settingsForm.addEventListener("submit", (event) => {
            event.preventDefault();

            settingsManager.saveFromForm(settingsForm);

            // Hide offcanvas if bootstrap is available
            if (typeof bootstrap !== "undefined" && bootstrap.Offcanvas) {
                const offcanvas =
                    bootstrap.Offcanvas.getInstance(settingsForm) ||
                    new bootstrap.Offcanvas(settingsForm);
                offcanvas.hide();
            }
        });
    }
};

/**
 * TomSelect Configuration
 */
const TOM_SELECT_CONFIG = {
    create: false,
    placeholder: "Pilih opsi...",
    searchField: ["text"],
    valueField: "value",
    labelField: "text",
    copyClassesToDropdown: false,
    dropdownParent: "body",
    controlInput: "<input>",
    hideSelected: true,
    persist: false,
    render: {
        item: (data, escapeFunc) => {
            if (data.customProperties) {
                return (
                    '<div><span class="dropdown-item-indicator">' +
                    data.customProperties +
                    "</span>" +
                    escapeFunc(data.text) +
                    "</div>"
                );
            }
            return "<div>" + escapeFunc(data.text) + "</div>";
        },
        option: (data, escapeFunc) => {
            if (data.customProperties) {
                return (
                    '<div><span class="dropdown-item-indicator">' +
                    data.customProperties +
                    "</span>" +
                    escapeFunc(data.text) +
                    "</div>"
                );
            }
            return "<div>" + escapeFunc(data.text) + "</div>";
        },
        option_create: (data, escape) => {
            return '<div class="create" style="display:none;">Add <strong>' + escape(data.input) + '</strong>...</div>';
        }
    },
};

/**
 * Alpine.js component for Tom Select with Livewire 3 integration
 * Uses wire:ignore to prevent Livewire from morphing the select element
 */
document.addEventListener("alpine:init", () => {
    Alpine.data("tomSelect", (config = {}) => ({
        instance: null,

        init() {
            const select = this.$el;
            const isMultiple = select.hasAttribute("multiple");

            // Initialize Tom Select
            this.instance = new TomSelect(select, {
                ...TOM_SELECT_CONFIG,
                ...config,
                createOnBlur: config.create || false,
                plugins: isMultiple ? ['remove_button'] : [],
                placeholder:
                    select.getAttribute("placeholder") ||
                    TOM_SELECT_CONFIG.placeholder,
                onChange: (value) => {
                    const wireModel = select.getAttribute("wire:model") || select.getAttribute("wire:model.live") || select.getAttribute("wire:model.blur");

                    // Convert value to array for multiple, handle single
                    let finalValue = value;
                    if (isMultiple) {
                        finalValue = Array.isArray(value) ? value : (value ? value.toString().split(',') : []);
                    }

                    // Sync underlying select options
                    if (isMultiple) {
                        Array.from(select.options).forEach(option => {
                            option.selected = finalValue.includes(option.value);
                        });
                    } else {
                        select.value = finalValue;
                    }

                    // Force direct Livewire sync if model exists
                    if (wireModel && this.$wire) {
                        this.$wire.set(wireModel, finalValue);
                    }

                    // Re-dispatch events
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                    select.dispatchEvent(new Event("input", { bubbles: true }));
                },
            });

            // If it's a tagging field (create: true), hide the dropdown arrow
            if (config.create) {
                this.instance.wrapper.classList.add('hide-caret');
                this.instance.control.classList.add('hide-caret');
            }

            // Listen for Livewire updates to sync Tom Select
            const wireModel = select.getAttribute("wire:model") || select.getAttribute("wire:model.live") || select.getAttribute("wire:model.blur");
            if (wireModel) {
                this.$watch(`$wire.${wireModel}`, (value) => {
                    if (this.instance) {
                        const current = this.instance.getValue();
                        // Handle both array (multiple) and string (single) values
                        if (JSON.stringify(current) !== JSON.stringify(value)) {
                            this.instance.setValue(value, true);
                        }
                    }
                });
            } else {
                // Fallback to watching element value for non-livewire usage
                this.$watch("$el.value", (value) => {
                    if (this.instance) {
                        const current = this.instance.getValue();
                        if (current !== value) {
                            this.instance.setValue(value, true);
                        }
                    }
                });
            }
        },

        destroy() {
            if (this.instance) {
                this.instance.destroy();
                this.instance = null;
            }
        },
    }));

    /**
     * Alpine.js component for Tom Select with create functionality
     * Allows users to create new options on the fly
     */
    Alpine.data("tomSelectWithCreate", () => ({
        instance: null,

        init() {
            const select = this.$el;

            this.instance = new TomSelect(select, {
                ...TOM_SELECT_CONFIG,
                create: true,
                createOnBlur: true,
                placeholder:
                    select.getAttribute("placeholder") ||
                    TOM_SELECT_CONFIG.placeholder,
                onChange: (value) => {
                    select.value = value;
                    select.dispatchEvent(
                        new Event("change", { bubbles: true }),
                    );
                    select.dispatchEvent(new Event("input", { bubbles: true }));
                },
            });

            this.$watch("$el.value", (value) => {
                if (this.instance && this.instance.getValue() !== value) {
                    this.instance.setValue(value, true);
                }
            });
        },

        destroy() {
            if (this.instance) {
                this.instance.destroy();
                this.instance = null;
            }
        },
    }));

    /**
     * Alpine.js component for Money/Rupiah Input
     * Real-time masking with cursor position management
     */
    Alpine.data("moneyInput", (index) => ({
        display: "",

        init() {
            this.updateDisplay();
            // Watch for external changes to the Livewire model
            this.$watch(
                () => {
                    try {
                        return this.$wire.get(`form.budget_items.${index}.unit_price`);
                    } catch (e) {
                        return null;
                    }
                },
                (value) => {
                    this.updateDisplay(value);
                },
            );
        },

        updateDisplay(val) {
            try {
                val =
                    val !== undefined
                        ? val
                        : this.$wire.get(`form.budget_items.${index}.unit_price`);
                if (val === "" || val === null || val === undefined) {
                    this.display = "";
                    return;
                }

                // Handle string with decimal points from database (e.g. "1000.00")
                let numericVal;
                if (typeof val === 'string' && val.includes('.')) {
                    numericVal = Math.round(parseFloat(val));
                } else {
                    numericVal = parseInt(val.toString().replace(/[^0-9]/g, ""));
                }

                if (isNaN(numericVal)) {
                    this.display = "";
                    return;
                }
                this.display = new Intl.NumberFormat("id-ID").format(
                    numericVal,
                );
            } catch (e) {
                this.display = "";
            }
        },

        handleFocus() {
            this.$nextTick(() => {
                if (this.$refs.input) {
                    this.$refs.input.select();
                }
            });
        },

        handleInput(e) {
            let input = e.target;
            let rawValue = input.value.replace(/[^0-9]/g, "");

            // Handle empty input
            if (rawValue === "") {
                this.display = "";
                try {
                    this.$wire.set(`form.budget_items.${index}.unit_price`, 0);
                    this.$wire.calculateTotal(index);
                } catch (e) { }
                return;
            }

            // Keep track of cursor position from the END
            // This is more reliable when dots are inserted/removed
            let selectionEnd = input.selectionEnd;
            let lengthBefore = input.value.length;
            let offsetFromEnd = lengthBefore - selectionEnd;

            // Format the raw value
            let numericVal = parseInt(rawValue);
            let formattedValue = new Intl.NumberFormat("id-ID").format(
                numericVal,
            );

            // Update state
            this.display = formattedValue;
            try {
                this.$wire.set(
                    `form.budget_items.${index}.unit_price`,
                    numericVal,
                    false,
                );
                this.$wire.calculateTotal(index);
            } catch (e) { }

            // Restore cursor position
            this.$nextTick(() => {
                let lengthAfter = this.display.length;
                let newPosition = lengthAfter - offsetFromEnd;
                input.setSelectionRange(newPosition, newPosition);
            });
        },
    }));

    /**
     * Alpine.js component for Money/Rupiah Input (Single Field)
     * For use with wire:model directly (not in an array)
     */
    Alpine.data("moneyInputSingle", (wireModelName) => ({
        display: "",

        init() {
            this.updateDisplay();
            // Watch for external changes to the Livewire model
            this.$watch(`$wire.${wireModelName}`, (value) => {
                this.updateDisplay(value);
            });
        },

        updateDisplay(val) {
            val = val || this.$wire.get(wireModelName);
            if (val === "" || val === null || val === undefined) {
                this.display = "";
                return;
            }

            // Handle string with decimal points from database (e.g. "1000.00")
            let numericVal;
            if (typeof val === 'string' && val.includes('.')) {
                numericVal = Math.round(parseFloat(val));
            } else {
                numericVal = parseInt(val.toString().replace(/[^0-9]/g, ""));
            }

            if (isNaN(numericVal)) {
                this.display = "";
                return;
            }
            this.display = new Intl.NumberFormat("id-ID").format(numericVal);
        },

        handleFocus() {
            this.$nextTick(() => {
                this.$refs.input.select();
            });
        },

        handleInput(e) {
            let input = e.target;
            let rawValue = input.value.replace(/[^0-9]/g, "");

            // Handle empty input
            if (rawValue === "") {
                this.display = "";
                this.$wire.set(wireModelName, null);
                return;
            }

            // Keep track of cursor position from the END
            let selectionEnd = input.selectionEnd;
            let lengthBefore = input.value.length;
            let offsetFromEnd = lengthBefore - selectionEnd;

            // Format the raw value
            let numericVal = parseInt(rawValue);
            let formattedValue = new Intl.NumberFormat("id-ID").format(
                numericVal,
            );

            // Update state
            this.display = formattedValue;
            this.$wire.set(wireModelName, numericVal, false);

            // Restore cursor position
            this.$nextTick(() => {
                let lengthAfter = this.display.length;
                let newPosition = lengthAfter - offsetFromEnd;
                input.setSelectionRange(newPosition, newPosition);
            });
        },

        // Helper to manually trigger refresh (useful during Livewire updates)
        refresh() {
            this.updateDisplay();
        }
    }));

    /**
     * Alpine component for form modal functionality
     */
    Alpine.data("modalForm", (id, formId, submitText) => ({
        loading: false,
        submitText: submitText,

        init() {
            this.$watch("loading", (value) => {
                const modal = document.getElementById(id);
                if (modal) {
                    if (value) {
                        modal.classList.add("modal-loading");
                    } else {
                        modal.classList.remove("modal-loading");
                    }
                }
            });
        },

        submitForm() {
            const form = document.getElementById(formId);
            if (form) {
                this.loading = true;
                // Trigger Livewire form submission
                this.$wire.call("handleSubmit").finally(() => {
                    this.loading = false;
                });
            }
        },

        resetForm() {
            const form = document.getElementById(formId);
            if (form) {
                form.reset();
            }
            this.loading = false;
        },
    }));

    /**
     * Alpine component for image preview modal
     */
    Alpine.data("imagePreview", (src) => ({
        currentImage: src,
        currentIndex: 0,
        totalImages: 1,
        zoomLevel: 1,
        isZoomed: false,
        images: [src],

        init() {
            // Initialize images array if passed via data attributes in children
            const slotImages = this.$el.parentElement.querySelectorAll(
                "[data-image-src]",
            );
            if (slotImages.length > 0) {
                this.images = Array.from(slotImages).map(
                    (img) => img.dataset.imageSrc,
                );
                this.totalImages = this.images.length;
            }

            // Set up image load events
            const img = this.$el.querySelector(".preview-image");
            if (img) {
                img.addEventListener("load", () => {
                    img.classList.remove("loading");
                });
            }
        },

        zoomIn() {
            if (this.zoomLevel < 3) {
                this.zoomLevel += 0.25;
                this.isZoomed = this.zoomLevel > 1;
            }
        },

        zoomOut() {
            if (this.zoomLevel > 0.25) {
                this.zoomLevel -= 0.25;
                this.isZoomed = this.zoomLevel > 1;
            }
        },

        resetZoom() {
            this.zoomLevel = 1;
            this.isZoomed = false;
        },

        zoomToggle() {
            if (this.zoomLevel === 1) {
                this.zoomLevel = 2;
                this.isZoomed = true;
            } else {
                this.resetZoom();
            }
        },

        downloadImage() {
            const link = document.createElement("a");
            link.href = this.currentImage;
            link.download = this.currentImage.split("/").pop() || "image";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        nextImage() {
            if (this.currentIndex < this.totalImages - 1) {
                this.currentIndex++;
                this.currentImage = this.images[this.currentIndex];
                this.resetZoom();
            }
        },

        previousImage() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.currentImage = this.images[this.currentIndex];
                this.resetZoom();
            }
        },
    }));

    /**
     * Alpine component for server-synced countdown timer
     */
    Alpine.data("countdownTimer", (config) => countdownTimer(config));
});

// Fallback: Initialize on page navigation (e.g., wire:navigate)
document.addEventListener("livewire:navigated", () => {
    initializeSettings();
});

// Initialize Tom Select specifically for modal content when modals are shown
document.addEventListener("shown.bs.modal", (event) => {
    const modal = event.target;
    const selects = modal.querySelectorAll("select[x-data*='tomSelect']");
    // Alpine will handle initialization automatically
});

// Fallback: Initialize on first page load
document.addEventListener("DOMContentLoaded", () => {
    initializeSettings();
});

// Livewire Global Progress Bar
document.addEventListener("livewire:init", () => {
    Livewire.hook("request", ({ fail, respond, succeed }) => {
        NProgress.start();

        respond(() => {
            NProgress.done();
        });

        fail(() => {
            NProgress.done();
        });
    });
});

/**
 * GLOBAL MODAL MANAGEMENT
 * Optimized for Bootstrap 5 + Livewire 3 (wire:navigate)
 */

// Helper to get/create Bootstrap modal instance
window.getBsModal = (el) => {
    if (!el) return null;
    const bootstrap = window.bootstrap || window.tabler?.bootstrap || window.tabler;
    return (
        bootstrap?.Modal?.getOrCreateInstance(el) || null
    );
};

// Helper to find the closest Livewire component from an element
const findLwComponent = (element) => {
    // 1. Try explicit component-id attribute (for teleported modals)
    if (element.hasAttribute("component-id")) {
        const componentId = element.getAttribute("component-id");
        return window.Livewire?.find(componentId);
    }

    // 2. Try generic DOM traversal for wire:id
    let current = element;
    while (current && current !== document.body) {
        if (current.hasAttribute("wire:id")) {
            const wireId = current.getAttribute("wire:id");
            return window.Livewire?.find(wireId);
        }
        current = current.parentElement;
    }
    return null;
};

// Initialize listeners for modals (callbacks like onShow/onHide)
const setupModalCallbacks = () => {
    document
        .querySelectorAll("[data-livewire-modal]:not([data-modal-bound])")
        .forEach((modalEl) => {
            modalEl.dataset.modalBound = "true";

            const onShow = modalEl.dataset.livewireOnShow;
            const onHide = modalEl.dataset.livewireOnHide;

            if (onShow) {
                modalEl.addEventListener("show.bs.modal", () => {
                    const component = findLwComponent(modalEl);
                    if (component) {
                        if (typeof component[onShow] === "function") {
                            component[onShow]();
                        } else {
                            component.call(onShow);
                        }
                    }
                });
            }

            if (onHide) {
                modalEl.addEventListener("hidden.bs.modal", () => {
                    const component = findLwComponent(modalEl);
                    if (component) {
                        if (typeof component[onHide] === "function") {
                            component[onHide]();
                        } else {
                            component.call(onHide);
                        }
                    }
                });
            }

            // Auto-close logic for alert modals
            if (
                modalEl.classList.contains("modal-alert") &&
                modalEl.dataset.autoClose === "true"
            ) {
                const duration = parseInt(modalEl.dataset.duration || 5000);
                modalEl.addEventListener("shown.bs.modal", () => {
                    const timer = setTimeout(() => {
                        const instance = getBsModal(modalEl);
                        instance?.hide();
                    }, duration);
                    modalEl.addEventListener(
                        "hidden.bs.modal",
                        () => clearTimeout(timer),
                        { once: true },
                    );
                });
            }
        });

    // Handle auto-submit for form modals
    document.querySelectorAll(".modal-form form").forEach((form) => {
        if (form.dataset.autoSubmitBound) return;
        form.dataset.autoSubmitBound = "true";

        form.addEventListener("keydown", (e) => {
            if (
                e.key === "Enter" &&
                !e.shiftKey &&
                e.target.tagName !== "TEXTAREA"
            ) {
                e.preventDefault();
                const submitBtn = form
                    .closest(".modal")
                    ?.querySelector(".btn-primary");
                submitBtn?.click();
            }
        });
    });
};

// Event handlers for global open/close dispatch
const handleOpenModalEvent = (data) => {
    const modalId = data.detail?.modalId || data.modalId || (Array.isArray(data) ? data[0]?.modalId : null);

    if (!modalId) {
        console.warn('open-modal: No modalId provided in event data');
        return;
    }

    // Use a small timeout to ensure DOM updates (especially teleports) have settled
    setTimeout(() => {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = window.getBsModal(modalEl);
            modal?.show();
        } else {
            console.error(`open-modal: Element with ID "${modalId}" not found in DOM after delay`);
        }
    }, 50);
};

const handleCloseModalEvent = (data) => {
    const modalId = data.detail?.modalId || data.modalId || (Array.isArray(data) ? data[0]?.modalId : null);

    if (!modalId) return;

    const modalEl = document.getElementById(modalId);
    if (modalEl) {
        const modal = window.getBsModal(modalEl);
        modal?.hide();
    }
};

// Register Global Listeners
const registerGlobalLivewireListeners = () => {
    window.Livewire.on("open-modal", handleOpenModalEvent);
    window.Livewire.on("close-modal", handleCloseModalEvent);
    window.Livewire.on("toast", (data) => {
        const config = Array.isArray(data) ? data[0] : data;
        window.showToast(config);
    });
    window.Livewire.on("show-toast", (data) => {
        const config = Array.isArray(data) ? data[0] : data;
        if (config?.id) {
            const el = document.getElementById(config.id);
            if (el)
                new (window.bootstrap?.Toast || window.tabler?.Toast)(
                    el,
                ).show();
        }
    });

    // Global listener for file downloads to bypass Livewire intercepted blobs
    const handleDownload = (url) => {
        if (url) {
            console.log("Triggering download:", url);
            window.location.assign(url);
        }
    };

    window.Livewire.on("download-file", (data) => {
        const config = Array.isArray(data) ? data[0] : data;
        handleDownload(config.url || config);
    });

    window.addEventListener("download-file", (event) => {
        handleDownload(event.detail?.url || event.url);
    });
};

window.addEventListener("open-modal", handleOpenModalEvent);
window.addEventListener("close-modal", handleCloseModalEvent);

if (window.Livewire) {
    registerGlobalLivewireListeners();
} else {
    document.addEventListener("livewire:init", registerGlobalLivewireListeners);
}

// Setup on navigation
document.addEventListener("livewire:navigated", setupModalCallbacks);

// Close dropdowns after navigation (needed for @persist header)
document.addEventListener("livewire:navigated", () => {
    // Close all open Bootstrap dropdowns
    document.querySelectorAll(".dropdown-menu.show").forEach((menu) => {
        menu.classList.remove("show");
        const toggle = menu.previousElementSibling;
        if (toggle?.classList.contains("dropdown-toggle")) {
            toggle.classList.remove("show");
            toggle.setAttribute("aria-expanded", "false");
        }
    });
    document.querySelectorAll(".dropdown.show, .nav-item.dropdown.show").forEach((dropdown) => {
        dropdown.classList.remove("show");
    });

    // Also use Bootstrap API if available
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
        const instance = (window.bootstrap?.Dropdown || window.tabler?.Dropdown)?.getInstance(el);
        instance?.hide();
    });

    // Update active navigation links (needed for @persist header)
    const normalizePath = (path) => path.replace(/\/+$/, "") || "/";
    const currentPath = normalizePath(window.location.pathname);

    // Remove all active classes from nav items and links
    document.querySelectorAll(".navbar-nav .nav-item.active, .navbar-nav .nav-link.active, .dropdown-item.active").forEach((el) => {
        el.classList.remove("active");
    });

    // Find the best matching link
    let bestMatch = null;
    let bestMatchLength = -1;

    document.querySelectorAll(".navbar-nav .nav-link[href], .dropdown-item[href]").forEach((link) => {
        const href = link.getAttribute("href");

        // Skip dummy links, hashes, or javascript calls
        if (!href || href === "#" || href.startsWith("javascript:")) return;

        try {
            const linkPath = normalizePath(new URL(href, window.location.origin).pathname);

            // 1. Exact Match
            if (currentPath === linkPath) {
                // Exact match is always the highest priority
                bestMatch = link;
                bestMatchLength = 999;
            }
            // 2. Prefix Match (for nested routes)
            else if (linkPath !== "/" && currentPath.startsWith(linkPath + "/")) {
                if (linkPath.length > bestMatchLength) {
                    bestMatch = link;
                    bestMatchLength = linkPath.length;
                }
            }
        } catch (e) {
            // Ignore invalid URLs
        }
    });

    // Activate the best match
    if (bestMatch) {
        bestMatch.classList.add("active");

        // Also mark parent nav-item as active
        const navItem = bestMatch.closest(".nav-item");
        if (navItem) {
            navItem.classList.add("active");
        }

        // If it's a dropdown item, mark the parent dropdown as active
        const parentDropdown = bestMatch.closest(".nav-item.dropdown");
        if (parentDropdown) {
            parentDropdown.classList.add("active");
            const dropdownToggle = parentDropdown.querySelector(".nav-link.dropdown-toggle");
            if (dropdownToggle) {
                dropdownToggle.classList.add("active");
            }
        }
    }
});

// Cleanup on navigation start
document.addEventListener("livewire:navigate", () => {
    document.querySelectorAll(".modal.show").forEach((el) => {
        const instance = (window.bootstrap?.Modal || window.tabler?.Modal)
            ?.getInstance(el);
        instance?.hide();
    });

    // Force cleanup
    document.body.classList.remove("modal-open");
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";
    document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
});

// Global Helpers
window.LoadingModal = {
    show: (id) => {
        const el = document.getElementById(id);
        if (el) getBsModal(el)?.show();
    },
    hide: (id) => {
        const el = document.getElementById(id);
        if (el) {
            (window.bootstrap?.Modal || window.tabler?.Modal)
                ?.getInstance(el)
                ?.hide();
        }
    },
};

window.ImagePreviewModal = {
    show: (id, src) => {
        const el = document.getElementById(id);
        if (el) {
            if (src) {
                const img = el.querySelector(".preview-image");
                if (img) img.src = src;
            }
            getBsModal(el)?.show();
        }
    },
};

// Initial execution
document.addEventListener("DOMContentLoaded", setupModalCallbacks);
setupModalCallbacks();

/**
 * GLOBAL TOAST MANAGEMENT
 */

const TOAST_VARIANT_ICONS = {
    success: `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-check me-2 text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`,
    danger: `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-x me-2 text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M10 10l4 4m0 -4l-4 4" /></svg>`,
    warning: `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle me-2 text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>`,
    info: `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle me-2 text-info" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>`,
};

window.showToast = ({
    message = "Notification",
    title = null,
    variant = "default",
    position = "top-end",
    autoHide = true,
    delay = 5000,
}) => {
    const positionMap = {
        "top-start": "top-0 start-0",
        "top-center": "top-0 start-50 translate-middle-x",
        "top-end": "top-0 end-0",
        "middle-start": "top-50 start-0 translate-middle-y",
        "middle-center": "top-50 start-50 translate-middle",
        "middle-end": "top-50 end-0 translate-middle-y",
        "bottom-start": "bottom-0 start-0",
        "bottom-center": "bottom-0 start-50 translate-middle-x",
        "bottom-end": "bottom-0 end-0",
    };

    const containerSelector = `.toast-container.position-fixed.${(positionMap[position] || positionMap["top-end"]).split(" ").join(".")}`;
    let container = document.querySelector(containerSelector);

    if (!container) {
        container = document.createElement("div");
        container.className = `toast-container position-fixed ${positionMap[position] || positionMap["top-end"]} p-3`;
        container.style.zIndex = "1090";
        document.body.appendChild(container);
    }

    const toastId = `toast-${Date.now()}`;
    const icon = TOAST_VARIANT_ICONS[variant] || "";
    const displayTitle =
        title || variant.charAt(0).toUpperCase() + variant.slice(1);

    const html = `
        <div class="toast border-${variant}" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true"
            data-bs-autohide="${autoHide}" data-bs-delay="${delay}">
            <div class="toast-header">
                ${icon}
                <strong class="me-auto">${displayTitle}</strong>
                <button type="button" class="ms-2 btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;

    container.insertAdjacentHTML("beforeend", html);
    const el = document.getElementById(toastId);
    const instance = new (window.bootstrap?.Toast || window.tabler?.Toast)(el);
    instance.show();

    el.addEventListener("hidden.bs.toast", () => {
        el.remove();
        if (container.children.length === 0) container.remove();
    });
};

const setupToastListeners = () => {
    // Handle triggers via data attributes
    document.querySelectorAll('[data-bs-toggle="toast"]').forEach((trigger) => {
        if (trigger.dataset.toastBound) return;
        trigger.dataset.toastBound = "true";

        trigger.addEventListener("click", (e) => {
            e.preventDefault();
            const target = trigger.getAttribute("data-bs-target");
            if (target) {
                const el = document.querySelector(target);
                if (el)
                    new (window.bootstrap?.Toast || window.tabler?.Toast)(
                        el,
                    ).show();
            }
        });
    });

    // Handle session flash messages on page load/navigation
    const flashData = window.__toastFlashData || null;
    if (flashData) {
        Object.entries(flashData).forEach(([type, message]) => {
            if (message) {
                window.showToast({
                    message: message,
                    variant: type === "error" ? "danger" : type,
                    position: "top-end",
                });
            }
        });
        // Clear flash data so it doesn't show again on re-navigated
        window.__toastFlashData = null;
    }
};

// Note: Toast listeners are now registered in registerGlobalLivewireListeners above
// to ensure they are attached even if app.js loads after Livewire initializes.

document.addEventListener("livewire:navigated", setupToastListeners);
document.addEventListener("DOMContentLoaded", setupToastListeners);
setupToastListeners();
