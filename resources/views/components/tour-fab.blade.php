{{--
    tour-fab.blade.php — Floating Action Button para lanzar el tour contextual de la página actual.

    Detecta si hay un tour disponible vía el evento 'tour:availability'.
    Se posiciona:
      - Móvil: bottom-20 right-4  (sobre la barra del sistema operativo)
      - Escritorio: bottom-8 right-6

    El estado completed/pending se persiste en localStorage (gestionado por tour-launcher.js).
    El estado se actualiza reactivamente con Alpine.js.
--}}
<div
    x-data="{
        hasTour: false,
        isDone: false,
        showLabel: false,
        init() {
            document.addEventListener('tour:availability', (e) => {
                this.hasTour = e.detail.hasTour;
                this.isDone  = e.detail.isDone;
            });
            document.addEventListener('tour:status-changed', (e) => {
                this.isDone = e.detail.isDone;
            });
        },
        launchTour() {
            if (typeof window.startTour === 'function') {
                window.startTour();
            }
        },
        repeatTour() {
            const route = document.body.dataset.tourRoute || '';
            localStorage.removeItem('sm_tour_done_' + route);
            this.isDone = false;
            this.$nextTick(() => this.launchTour());
        }
    }"
    x-show="hasTour"
    x-cloak
    class="fixed bottom-20 right-4 lg:bottom-8 lg:right-6 z-40 flex flex-col items-end gap-2"
>
    {{-- Etiqueta flotante al hacer hover --}}
    <div
        x-show="showLabel && !isDone"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-2"
        class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs font-semibold px-3 py-1.5 rounded-full shadow-lg whitespace-nowrap pointer-events-none"
        x-cloak
    >
        Iniciar tour de esta sección
    </div>

    {{-- Botón principal --}}
    <template x-if="!isDone">
        <button
            @click="launchTour()"
            @mouseenter="showLabel = true"
            @mouseleave="showLabel = false"
            class="group flex items-center justify-center w-12 h-12 rounded-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white shadow-lg shadow-indigo-500/40 transition-all duration-200 ring-2 ring-indigo-400/30 hover:ring-indigo-400/60"
            title="Iniciar tour guiado de esta sección"
            aria-label="Iniciar tour de esta sección"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform duration-150">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
        </button>
    </template>

    {{-- Botón "Tour completado" con opción de repetir --}}
    <template x-if="isDone">
        <div class="flex items-center gap-2">
            <div
                x-show="showLabel"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-x-2"
                x-transition:enter-end="opacity-100 translate-x-0"
                class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs font-semibold px-3 py-1.5 rounded-full shadow-lg whitespace-nowrap pointer-events-none"
                x-cloak
            >
                Repetir tour
            </div>
            <button
                @click="repeatTour()"
                @mouseenter="showLabel = true"
                @mouseleave="showLabel = false"
                class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-500/15 hover:bg-emerald-500/25 active:scale-95 text-emerald-600 dark:text-emerald-400 shadow-sm border border-emerald-300 dark:border-emerald-700 transition-all duration-200"
                title="Tour completado — clic para repetir"
                aria-label="Tour completado, haz clic para repetirlo"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </div>
    </template>
</div>
