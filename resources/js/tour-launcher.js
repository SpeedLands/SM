/**
 * tour-launcher.js — Sistema central de tours interactivos con driver.js
 *
 * Flujo:
 * 1. Lee data-tour-route, data-tour-role y data-tour-view-parent del <body>.
 * 2. Carga los pasos correctos desde tours.js.
 * 3. Registra window.startTour() para que el FAB y los botones de /tutoriales puedan lanzarlo.
 * 4. Detecta ?start_tour=1 en la URL para auto-lanzar el tour al cargar.
 * 5. Guarda el estado de completado en localStorage por ruta.
 * 6. Emite el evento custom 'tour:status-changed' para que el FAB Alpine actualice su estado.
 */

import { driver } from 'driver.js';
import { getTourSteps } from './tours.js';

const STORAGE_KEY_PREFIX = 'sm_tour_done_';
const isMobile = () => window.innerWidth < 1024;

/**
 * Inicializa el sistema de tours. Llamar al montar el DOM.
 */
export function initTourSystem() {
    const body = document.body;
    const route = body.dataset.tourRoute || '';
    const role = body.dataset.tourRole || '';
    const viewParent = body.dataset.tourViewParent || 'false';

    if (!route || !role) {
        console.warn('Tour System: Missing route or role', { route, role });
        return;
    }

    const steps = getTourSteps(route, role, viewParent);
    const hasTour = steps && steps.length > 0;

    console.log('Tour System: Initialized', { route, role, hasTour });

    // Notificar al FAB si hay tour disponible
    document.dispatchEvent(new CustomEvent('tour:availability', {
        detail: {
            hasTour,
            isDone: hasTour && localStorage.getItem(STORAGE_KEY_PREFIX + route) === '1',
        },
    }));

    if (!hasTour) {
        console.log('Tour System: No tour available for this route/role');
        return;
    }

    // Exponer la función globalmente para el FAB y botones de /tutoriales
    window.startTour = (forcedSteps = null) => {
        // Create a shallow copy of steps to avoid mutating the original definition
        // We use JSON parse/stringify for a deep-ish clone of simple objects
        const tourSteps = JSON.parse(JSON.stringify(forcedSteps || steps));

        if (!tourSteps || tourSteps.length === 0) { return; }

        // En móvil, abrir el sidebar antes del primer paso si el primer elemento está en él
        const firstEl = document.querySelector(tourSteps[0]?.element);
        if (isMobile() && firstEl === null) {
            const sidebarToggle = document.querySelector('[data-flux-sidebar-toggle]');
            if (sidebarToggle) {
                sidebarToggle.click();
                // Esperar a que el sidebar abra
                setTimeout(() => launchDriver(tourSteps, route), 400);
                return;
            }
        }

        launchDriver(tourSteps, route);
    };

    // Auto-lanzar si viene ?start_tour=1 en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('start_tour') === '1') {
        const tryLaunch = () => {
            console.log('Tour System: Attempting auto-launch');
            // Un pequeño delay para que Livewire hidrate los componentes y data-tour esté disponible
            setTimeout(() => {
                if (window.startTour) {
                    window.startTour();
                } else {
                    console.warn('Tour System: window.startTour not ready');
                }
            }, 800);
        };

        if (document.readyState === 'complete') {
            tryLaunch();
        } else {
            window.addEventListener('load', tryLaunch, { once: true });
        }
    }

    // Re-inicializar en navegación de Livewire (wire:navigate)
    document.addEventListener('livewire:navigated', () => {
        setTimeout(() => initTourSystem(), 300);
    });
}

/**
 * Lanza el driver.js con los pasos dados.
 * @param {Array} steps
 * @param {string} route
 */
function launchDriver(steps, route) {
    console.log('Tour System: launchDriver', { route, steps_count: steps.length });

    // Filtrar pasos donde el elemento no existe en el DOM (o no es visible por diseño responsivo)
    const validSteps = steps.filter((step, index) => {
        if (!step.element) { return true; } // paso sin elemento (sólo popover)

        const isFunction = typeof step.element === 'function';
        const isString = typeof step.element === 'string';

        if (isFunction) { return true; }

        if (!isString) { return document.body.contains(step.element); }

        const elements = Array.from(document.querySelectorAll(step.element));
        if (elements.length === 0) {
            // Permitir que el paso de búsqueda pase el filtro aunque no esté en el DOM (se abrirá dinámicamente)
            if (step.element.includes('students-search')) return true;

            console.warn(`Tour System: Element not found for step ${index}: ${step.element}`);
            return false;
        }

        // Buscar el primer elemento que sea visible
        const visibleEl = elements.find(el => {
            return (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0) &&
                window.getComputedStyle(el).display !== 'none';
        });

        if (visibleEl) {
            step.element = visibleEl; // driver.js acepta elementos DOM
            return true;
        }

        // Si hay elementos pero ninguno es visible, permitirlo solo si es el de búsqueda
        if (step.element.includes('students-search')) return true;

        console.warn(`Tour System: No visible instance found for step ${index}: ${step.element}`);
        return false;
    });

    console.log('Tour System: validSteps', { count: validSteps.length });

    if (validSteps.length === 0) {
        console.error('Tour System: No valid steps found for this tour. Abandoning launch.');
        return;
    }

    const driverInstance = driver({
        showProgress: true,
        showButtons: ['next', 'previous', 'close'],
        nextBtnText: 'Siguiente →',
        prevBtnText: '← Anterior',
        doneBtnText: '✓ Entendido',
        progressText: 'Paso {{current}} de {{total}}',
        popoverClass: isMobile() ? 'driver-popover-custom driver-popover-mobile' : 'driver-popover-custom',
        overlayOpacity: 0.65,
        animate: true,
        allowClose: true,
        steps: validSteps,
        onDestroyStarted: () => {
            driverInstance.destroy();
        },
        onDestroyed: () => {
            // Marcar como completado si llegó al último paso
            const current = driverInstance.getActiveIndex();
            if (current === null || current >= validSteps.length - 1) {
                markTourDone(route);
            }
        },
    });

    driverInstance.drive();
}

/**
 * Marca un tour como completado en localStorage y dispara el evento para el FAB.
 * @param {string} route
 */
function markTourDone(route) {
    localStorage.setItem(STORAGE_KEY_PREFIX + route, '1');
    document.dispatchEvent(new CustomEvent('tour:status-changed', {
        detail: { isDone: true, route },
    }));
}

/**
 * Resetea el estado completado de un tour (para el botón "Repetir tour").
 * @param {string} route
 */
export function resetTourDone(route) {
    localStorage.removeItem(STORAGE_KEY_PREFIX + route);
    document.dispatchEvent(new CustomEvent('tour:status-changed', {
        detail: { isDone: false, route },
    }));
}

/**
 * Verifica si el tour de una ruta ya fue completado.
 * @param {string} route
 * @returns {boolean}
 */
export function isTourDone(route) {
    return localStorage.getItem(STORAGE_KEY_PREFIX + route) === '1';
}
