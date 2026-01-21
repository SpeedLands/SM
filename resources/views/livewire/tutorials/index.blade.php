<?php

use Livewire\Volt\Component;

new class extends Component {
    public $tab = 'parents';

    public function setTab($tab)
    {
        $this->tab = $tab;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <flux:heading size="xl">Tutoriales</flux:heading>
        <p class="text-zinc-500 dark:text-zinc-400">Guías paso a paso para aprovechar al máximo la plataforma.</p>
    </div>

    <flux:separator />

    {{-- Tabs Navigation --}}
    <div class="flex gap-2 overflow-x-auto border-b border-zinc-200 pb-2 dark:border-zinc-700">
        <flux:button :variant="$tab === 'parents' ? 'primary' : 'ghost'" wire:click="setTab('parents')" icon="users">Padres de Familia</flux:button>
        <flux:button :variant="$tab === 'teachers' ? 'primary' : 'ghost'" wire:click="setTab('teachers')" icon="academic-cap">Docentes</flux:button>
        @can('admin-only')
            <flux:button :variant="$tab === 'admin' ? 'primary' : 'ghost'" wire:click="setTab('admin')" icon="shield-check">Administración</flux:button>
        @endcan
    </div>

    {{-- Parents Content --}}
    @if($tab === 'parents')
    <div class="space-y-6">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.document-text class="text-zinc-500" />
                    </div>
                    <flux:heading size="lg">Ver Reportes</flux:heading>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Aprende a consultar los reportes disciplinarios y académicos de tus hijos.</p>
                <flux:modal.trigger name="tutorial-reports">
                    <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                </flux:modal.trigger>
            </div>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.megaphone class="text-zinc-500" />
                    </div>
                    <flux:heading size="lg">Consultar Avisos</flux:heading>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Mantente informado sobre los anuncios importantes de la institución.</p>
                <flux:modal.trigger name="tutorial-notices">
                    <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                </flux:modal.trigger>
            </div>

             <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.calendar-days class="text-zinc-500" />
                    </div>
                    <flux:heading size="lg">Citatorios</flux:heading>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Revisa y confirma tu asistencia a citatorios escolares.</p>
                <flux:modal.trigger name="tutorial-citations">
                    <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    @endif

    {{-- Teachers Content --}}
    @if($tab === 'teachers')
    <div class="space-y-6">
         <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.pencil-square class="text-zinc-500" />
                    </div>
                    <flux:heading size="lg">Crear Reportes</flux:heading>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Pasos para registrar un nuevo reporte disciplinario o académico.</p>
                <flux:modal.trigger name="tutorial-create-report">
                    <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                </flux:modal.trigger>
            </div>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.clipboard-document-list class="text-zinc-500" />
                    </div>
                    <flux:heading size="lg">Programar Exámenes</flux:heading>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Agenda exámenes parciales y finales para tus grupos.</p>
                 <flux:modal.trigger name="tutorial-create-exam">
                    <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    @endif

    {{-- Admin Content --}}
    @can('admin-only')
        @if($tab === 'admin')
        <div class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.cog-6-tooth class="text-zinc-500" />
                        </div>
                        <flux:heading size="lg">Gestión de Usuarios</flux:heading>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Administra cuentas de maestros, padres y alumnos.</p>
                    <flux:modal.trigger name="tutorial-manage-users">
                        <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                    </flux:modal.trigger>
                </div>
                <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.academic-cap class="text-zinc-500" />
                        </div>
                        <flux:heading size="lg">Ciclos Escolares</flux:heading>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Configura y cambia entre ciclos escolares activos.</p>
                    <flux:modal.trigger name="tutorial-cycles">
                        <flux:button variant="primary" class="w-full">Ver Guía</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
        @endif
    @endcan

    {{-- Modals for Tutorials Content --}}
    
    {{-- Parents --}}
    <flux:modal name="tutorial-reports" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Cómo ver Reportes</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Inicia sesión en la plataforma con tus credenciales.</p>
                <p>2. En el menú lateral izquierdo, haz clic en <strong>"Reportes"</strong>.</p>
                <p>3. Verás una lista con todos los reportes de tus hijos. Puedes filtrar por fecha o tipo de reporte.</p>
                <p>4. Haz clic en el botón <strong>"Ver detalle"</strong> de cualquier reporte para ver la información completa.</p>
                <div class="text-sm rounded-lg bg-zinc-100 p-3 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Si tienes activadas las notificaciones, recibirás un correo cada vez que se genere un nuevo reporte.</div>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="tutorial-notices" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Consultar Avisos</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Dirígete a la sección <strong>"Avisos"</strong> en el menú lateral.</p>
                <p>2. Aquí encontrarás comunicados generales y específicos para tu grupo.</p>
                <p>3. Algunos avisos pueden requerir tu confirmación de lectura. Busca el botón <strong>"Marcar como leído"</strong> o <strong>"Firmar"</strong> si es necesario.</p>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="tutorial-citations" class="md:w-2/3 lg:w-1/2">
         <div class="space-y-6">
            <flux:heading size="lg">Cómo atender Citatorios</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Ve al menú <strong>"Citatorios"</strong>.</p>
                <p>2. Si tienes un citatorio pendiente, verás la fecha, hora y el motivo.</p>
                <p>3. Es importante asistir puntualmente. Si no puedes asistir, por favor comunícate con la escuela para reagendar.</p>
            </div>
        </div>
    </flux:modal>

    {{-- Teachers --}}
    <flux:modal name="tutorial-create-report" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Crear un Nuevo Reporte</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Ingresa a la sección <strong>"Reportes"</strong>.</p>
                <p>2. Haz clic en el botón <strong>"Nuevo Reporte"</strong> en la parte superior derecha.</p>
                <p>3. Selecciona al alumno, el tipo de reporte (Conducta, Académico, etc.) y la fecha.</p>
                <p>4. Describe detalladamente la situación en el campo de descripción.</p>
                <p>5. Haz clic en <strong>"Guardar"</strong>. El reporte se enviará automáticamente a los padres.</p>
            </div>
        </div>
    </flux:modal>

     <flux:modal name="tutorial-create-exam" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Programar Exámenes</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Ve a la sección <strong>"Exámenes"</strong>.</p>
                <p>2. Selecciona <strong>"Programar Examen"</strong>.</p>
                <p>3. Elige el grupo, la materia y la fecha del examen.</p>
                <p>4. Puedes añadir temas específicos que vendrán en el examen.</p>
                <p>5. Al guardar, los alumnos y padres verán el examen en su calendario.</p>
            </div>
        </div>
    </flux:modal>

    {{-- Admin --}}
    @can('admin-only')
    <flux:modal name="tutorial-manage-users" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Gestión de Usuarios</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Accede a <strong>"Gestión de Usuarios"</strong> desde el menú.</p>
                <p>2. Utiliza el buscador para encontrar un usuario específico.</p>
                <p>3. Puedes editar sus datos, restablecer su contraseña o desactivar la cuenta.</p>
                <p>4. Para crear un nuevo usuario, usa el botón <strong>"Nuevo Usuario"</strong> y completa el formulario.</p>
            </div>
        </div>
    </flux:modal>
     <flux:modal name="tutorial-cycles" class="md:w-2/3 lg:w-1/2">
        <div class="space-y-6">
            <flux:heading size="lg">Configuración de Ciclos</flux:heading>
            <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
                <p>1. Ve a <strong>"Ciclos Escolares"</strong>.</p>
                <p>2. Aquí puedes crear nuevos ciclos escolares (ej. "2024-2025").</p>
                <p>3. Asegúrate de marcar el ciclo correspondiente como <strong>"Activo"</strong> para que sea el predeterminado en toda la plataforma.</p>
            </div>
        </div>
    </flux:modal>
    @endcan

</div>
