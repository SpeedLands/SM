<?php

use Livewire\Volt\Component;

new class extends Component {
    public $tab = 'parents';
    public $selectedTutorial = null;

    protected $queryString = ['tab', 'selectedTutorial'];

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->selectedTutorial = null;
    }

    public function selectTutorial($name)
    {
        $this->selectedTutorial = $name;
    }

    public function back()
    {
        $this->selectedTutorial = null;
    }

    public function getTitle($name)
    {
        return [
            'tutorial-p-reports' => 'Guía para Ver y Firmar Reportes Escolares',
            'tutorial-p-notices' => 'Cómo Gestionar Avisos y Autorizaciones Digitales',
            'tutorial-p-citations' => 'Atención Efectiva de Citatorios Docentes',
            'tutorial-p-exams' => 'Consulta de Calendario de Evaluaciones',
            'tutorial-p-community' => 'Seguimiento de Servicio Comunitario',
            'tutorial-d-create-report' => 'Manual para el Registro de Reportes Disciplinarios',
            'tutorial-d-exams' => 'Programación de Exámenes y Evaluaciones',
            'tutorial-d-citations' => 'Gestión de Citatorios para Padres',
            'tutorial-d-notices' => 'Publicación de Avisos Masivos',
            'tutorial-d-community' => 'Asignación de Actividades Reparatorias',
            'tutorial-a-users' => 'Panel de Administración de Usuarios',
            'tutorial-a-cycles' => 'Configuración de Ciclos Escolares',
            'tutorial-a-regulations' => 'Edición del Reglamento Institucional',
            'tutorial-a-inscribe' => 'Proceso de Inscripción de Alumnos',
            'tutorial-a-import' => 'Guía de Importación Masiva de Datos',
            'tutorial-a-export' => 'Exportación de Listas y Reportes',
            'tutorial-c-install' => 'Cómo Instalar la Aplicación (PWA)',
            'tutorial-c-notifications' => 'Activar Notificaciones Push',
        ][$name] ?? 'Tutorial';
    }

    public function getCategory($name)
    {
        if (str_starts_with($name, 'tutorial-p')) return 'Padres';
        if (str_starts_with($name, 'tutorial-d')) return 'Docentes';
        if (str_starts_with($name, 'tutorial-a')) return 'Administración';
        return 'Configuración';
    }

    public function getReadTime($name)
    {
        return 5; // Simulado
    }

    public function getSections($name)
    {
        $sections = [
            ['id' => 'intro', 'title' => 'Introducción'],
            ['id' => 'requisitos', 'title' => 'Requisitos'],
            ['id' => 'proceso', 'title' => 'Proceso paso a paso'],
        ];

        // Añadir secciones CRUD si aplica
        if (!str_starts_with($name, 'tutorial-c') && !str_starts_with($name, 'tutorial-p')) {
            $sections[] = ['id' => 'editar', 'title' => 'Cómo Editar'];
            $sections[] = ['id' => 'eliminar', 'title' => 'Cómo Eliminar'];
        }

        $sections[] = ['id' => 'beneficios', 'title' => 'Tips de experto'];

        return $sections;
    }

    public function getContent($name)
    {
        $title = $this->getTitle($name);
        $category = $this->getCategory($name);
        
        $specifics = [
            'tutorial-p-reports' => [
                'desc' => 'Como padre de familia, es vital que estés al tanto del desempeño y comportamiento de tu hijo.',
                'req' => 'Tener una cuenta activa vinculada a uno o más estudiantes.',
                'steps' => '<li>Accede a "Reportes" en el dashboard.</li><li>Selecciona al alumno si tienes más de uno.</li><li>Haz clic en "Firmar de Enterado" en los reportes color ámbar.</li>',
                'tip' => 'La firma digital tiene validez institucional para trámites académicos.'
            ],
            'tutorial-p-notices' => [
                'desc' => 'Gestiona autorizaciones para viajes escolares y eventos especiales de forma digital.',
                'req' => 'Notificaciones push habilitadas en tu dispositivo.',
                'steps' => '<li>Revisa la bandeja de "Avisos".</li><li>Lee los detalles del permiso.</li><li>Selecciona "Autorizar" o "Denegar" según corresponda.</li>',
                'tip' => 'Las autorizaciones tienen fecha límite; asegúrate de responder a tiempo.',
                'edit' => 'Las respuestas a autorizaciones no se pueden editar una vez confirmadas.',
                'delete' => 'Los avisos solo pueden ser eliminados por el personal docente o administrativo.'
            ],
            'tutorial-p-reports' => [
                'desc' => 'Como padre de familia, es vital que estés al tanto del desempeño y comportamiento de tu hijo.',
                'req' => 'Tener una cuenta activa vinculada a uno o más estudiantes.',
                'steps' => '<li>Accede a "Reportes" en el dashboard.</li><li>Selecciona al alumno si tienes más de uno.</li><li>Haz clic en "Firmar de Enterado" en los reportes color ámbar.</li>',
                'tip' => 'La firma digital tiene validez institucional para trámites académicos.',
                'edit' => '', 'delete' => ''
            ],
            'tutorial-p-notices' => [
                'desc' => 'Gestiona autorizaciones para viajes escolares y eventos especiales de forma digital.',
                'req' => 'Notificaciones push habilitadas en tu dispositivo.',
                'steps' => '<li>Revisa la bandeja de "Avisos".</li><li>Lee los detalles del permiso.</li><li>Selecciona "Autorizar" o "Denegar" según corresponda.</li>',
                'tip' => 'Las autorizaciones tienen fecha límite; asegúrate de responder a tiempo.',
                'edit' => '', 'delete' => ''
            ],
            'tutorial-p-citations' => [
                'desc' => 'Confirma tu asistencia a reuniones presenciales con el personal docente.',
                'req' => 'Recibir un citatorio activo en el panel.',
                'steps' => '<li>Ingresa a "Citatorios".</li><li>Verifica el motivo, fecha y hora.</li><li>Haz clic en "Confirmar Enterado".</li>',
                'tip' => 'Acudir a los citatorios mejora el seguimiento educativo de tus hijos.',
                'edit' => '', 'delete' => ''
            ],
            'tutorial-p-exams' => [
                'desc' => 'Consulta las fechas de evaluación para que puedas apoyar a tu hijo en su preparación.',
                'req' => 'Tener alumnos inscritos en el ciclo actual.',
                'steps' => '<li>Ve a "Exámenes" en el menú lateral.</li><li>Filtra por trimestre para ver las fechas próximas.</li><li>Consulta el temario si el docente lo ha adjuntado.</li>',
                'tip' => 'Revisar el calendario evita sorpresas de último momento en las evaluaciones.',
                'edit' => '', 'delete' => ''
            ],
            'tutorial-p-community' => [
                'desc' => 'Seguimiento de las actividades de servicio comunitario asignadas por reportes acumulados.',
                'req' => 'Tener un alumno con 3 o más reportes en el ciclo.',
                'steps' => '<li>Ingresa a "Servicio Comunitario".</li><li>Revisa la actividad asignada y la fecha de cumplimiento.</li><li>Firma de enterado para iniciar el proceso.</li>',
                'tip' => 'El servicio comunitario busca la reparación del daño y la reflexión del alumno.',
                'edit' => '', 'delete' => ''
            ],
            'tutorial-d-create-report' => [
                'desc' => 'Como docente, puedes documentar incidencias o méritos de tus alumnos.',
                'req' => 'Estar asignado como docente de un grupo activo.',
                'steps' => '<li>Haz clic en "Nuevo Reporte".</li><li>Selecciona al alumno y la falta.</li><li>Describe el evento y guarda.</li>',
                'tip' => 'Usa descripciones claras y objetivas para evitar malentendidos.',
                'edit' => '<h2 id="editar">Cómo Editar un Reporte</h2><p>Busca el reporte en tu historial, haz clic en el ícono de lápiz, modifica el texto y guarda. Nota: No puedes cambiar el alumno una vez creado.</p>',
                'delete' => '<h2 id="eliminar">Cómo Eliminar un Reporte</h2><p>Si cometiste un error, puedes eliminar el reporte antes de que el padre lo firme. Busca el botón "Eliminar" en la vista lateral del reporte.</p>'
            ],
            'tutorial-c-install' => [
                'desc' => 'Instala la aplicación en tu celular para un acceso rápido y directo sin usar el navegador.',
                'req' => 'Navegador Chrome (Android) o Safari (iOS).',
                'steps' => '<li>Abre el menú del navegador (tres puntos o flecha).</li><li>Selecciona "Instalar Aplicación" o "Añadir a pantalla de inicio".</li><li>Confirma la instalación.</li>',
                'tip' => 'La App instalada consume menos datos y carga más rápido.',
                'edit' => '',
                'delete' => ''
            ],
            'tutorial-d-exams' => [
                'desc' => 'Programa las fechas de evaluación para que los padres puedan organizar los horarios de sus hijos.',
                'req' => 'Estar en periodo de evaluación vigente.',
                'steps' => '<li>Ve a "Exámenes" -> "Programar".</li><li>Elige materia, grupo y fecha.</li><li>Guarda para notificar automáticamente.</li>',
                'tip' => 'Programar con anticipación reduce la ansiedad en los alumnos.',
                'edit' => '<h2 id="editar">Reprogramar Examen</h2><p>En el calendario de exámenes, selecciona el examen y elige una nueva fecha. Los padres serán notificados del cambio.</p>',
                'delete' => '<h2 id="eliminar">Cancelar Evaluación</h2><p>Haz clic en "Eliminar" sobre el examen programado. Nota: Solo puedes eliminar si no se han registrado calificaciones.</p>'
            ],
            'tutorial-d-citations' => [
                'desc' => 'Genera citatorios para tener reuniones presenciales con los padres de familia.',
                'req' => 'Tener un motivo justificado (académico o disciplinario).',
                'steps' => '<li>Haz clic en "Nuevo Citatorio".</li><li>Define fecha, hora y lugar.</li><li>Escribe el motivo detalladamente.</li>',
                'tip' => 'Un citatorio bien redactado facilita la disposición del padre al diálogo.',
                'edit' => '<h2 id="editar">Editar Fecha de Citatorio</h2><p>Si el padre no puede asistir, puedes editar la fecha desde tu panel de gestión.</p>',
                'delete' => '<h2 id="eliminar">Eliminar Citatorio</h2><p>Puedes eliminar un citatorio si la reunión ya no es necesaria, siempre y cuando el padre no haya confirmado asistencia.</p>'
            ],
            'tutorial-d-notices' => [
                'desc' => 'Envía comunicados a todos los padres de tus grupos de forma rápida.',
                'req' => 'Mensaje autorizado por la dirección si es de carácter general.',
                'steps' => '<li>Crea un "Nuevo Aviso".</li><li>Selecciona los grupos destino.</li><li>Redacta el mensaje y adjunta archivos si es necesario.</li>',
                'tip' => 'Usa avisos para recordar tareas, materiales o eventos del grupo.',
                'edit' => '<h2 id="editar">Editar Aviso</h2><p>Puedes editar el contenido de un aviso en cualquier momento.</p>',
                'delete' => '<h2 id="eliminar">Archivar Aviso</h2><p>En lugar de eliminar, puedes archivar avisos antiguos para mantener limpia la bandeja de los padres.</p>'
            ],
            'tutorial-a-cycles' => [
                'desc' => 'Configura los periodos escolares para organizar los grupos y reportes.',
                'req' => 'Inicio de periodo vacacional o reinscripciones.',
                'steps' => '<li>Crea un "Nuevo Ciclo".</li><li>Define fecha de inicio y fin.</li><li>Actívalo para que sea el ciclo vigente del sistema.</li>',
                'tip' => 'Solo debe haber un ciclo activo a la vez para evitar confusiones en los reportes.',
                'edit' => '<h2 id="editar">Editar Fechas</h2><p>Puedes ajustar las fechas de inicio/fin si hay cambios en el calendario oficial.</p>',
                'delete' => '<h2 id="eliminar">Cerrar Ciclo</h2><p>Al cerrar un ciclo, todos los registros pasan a modo "Historial" y no pueden ser modificados.</p>'
            ],
            'tutorial-a-regulations' => [
                'desc' => 'Mantén actualizado el reglamento institucional que rige las sanciones y méritos.',
                'req' => 'Acuerdo del consejo escolar o directivos.',
                'steps' => '<li>Ingresa a "Configuración" -> "Reglamento".</li><li>Edita los artículos específicos.</li><li>Guarda para que los nuevos reportes usen la versión actualizada.</li>',
                'tip' => 'Un reglamento claro reduce las apelaciones de los padres.',
                'edit' => '<h2 id="editar">Actualizar Artículos</h2><p>Usa el editor de texto para dar formato a los puntos clave del reglamento.</p>',
                'delete' => '<h2 id="eliminar">Eliminar Fracción</h2><p>Puedes eliminar secciones obsoletas del reglamento en tiempo real.</p>'
            ],
            'tutorial-a-inscribe' => [
                'desc' => 'Proceso para registrar nuevos alumnos y vincularlos con sus tutores en el sistema.',
                'req' => 'Documentación física ya validada.',
                'steps' => '<li>Ve a "Alumnos" -> "Nueva Inscripción".</li><li>Completa los datos personales y académicos.</li><li>Busca y vincula al padre mediante su correo electrónico.</li>',
                'tip' => 'Vincular correctamente al padre es crucial para que reciba las notificaciones.',
                'edit' => '<h2 id="editar">Editar Datos de Alumno</h2><p>Cambia el grupo o grado si hubo una promoción o ajuste administrativo.</p>',
                'delete' => '<h2 id="eliminar">Baja de Alumno</h2><p>El sistema permite dar de baja a un alumno, lo cual inhabilita su acceso y detiene las notificaciones a los padres.</p>'
            ],
            'tutorial-a-import' => [
                'desc' => 'Carga masiva de alumnos, padres y grupos mediante archivos de Excel.',
                'req' => 'Archivo en formato .xlsx siguiendo la plantilla oficial.',
                'steps' => '<li>Sube tu archivo en el módulo de "Importación".</li><li>Valida que las columnas coincidan con el sistema.</li><li>Procesa la carga y revisa el log de errores si los hubiera.</li>',
                'tip' => 'Limpia los datos de duplicados en el Excel antes de subirlo para evitar errores de validación.',
                'edit' => '',
                'delete' => ''
            ],
            'tutorial-a-export' => [
                'desc' => 'Genera reportes detallados en Excel de cualquier módulo del sistema.',
                'req' => 'Seleccionar los filtros adecuados para el reporte deseado.',
                'steps' => '<li>Elige el módulo (Ej: Reportes Disciplinarios).</li><li>Aplica filtros de fecha o grado.</li><li>Haz clic en "Exportar a Excel".</li>',
                'tip' => 'Las exportaciones son ideales para juntas de consejo o análisis estadísticos.',
                'edit' => '',
                'delete' => ''
            ],
        ][$name] ?? [
            'desc' => "Esta guía te ayudará a dominar la sección de <strong>{$title}</strong>.",
            'req' => 'Acceso al módulo con permisos de ' . $category . '.',
            'steps' => '<li>Ingresa al módulo desde el menú principal.</li><li>Identifica la acción que deseas realizar.</li><li>Sigue los prompts en pantalla para confirmar los cambios.</li>',
            'tip' => 'Recuerda que todos los cambios se registran en el historial de actividades del sistema.',
            'edit' => '<h2 id="editar">Cómo Editar registros</h2><p>Para editar, busca el registro específico y presiona el botón de edición. El sistema guardará la versión anterior para auditoría.</p>',
            'delete' => '<h2 id="eliminar">Cómo Eliminar registros</h2><p>La eliminación puede estar restringida según tu perfil. Si el botón no aparece, contacta a un administrador.</p>'
        ];

        $crudHtml = ($specifics['edit'] ?? '') . ($specifics['delete'] ?? '');

        return "
            <p id='intro'>{$specifics['desc']}</p>
            
            <h2 id='requisitos'>Requisitos previos</h2>
            <p>{$specifics['req']}</p>

            <h2 id='proceso'>Proceso paso a paso</h2>
            <ol>
                {$specifics['steps']}
            </ol>

            {$crudHtml}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='p-6 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>
                    !
                </div>
                <div>
                    <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Tip de eficiencia</p>
                    <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>{$specifics['tip']}</p>
                </div>
            </div>
        ";
    }
}; ?>

@section('title', 'Guía Escolar: Tutoriales y Ayuda Digital')
@section('description', 'Aprende a gestionar reportes, avisos, citatorios y más en tu plataforma de gestión escolar digital.')

<div class="flex flex-col gap-6">
    @if(!$selectedTutorial)
        <div class="flex flex-col gap-2">
            <flux:heading size="xl" level="1">Centro de Ayuda y Tutoriales</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Guía definitiva para dominar todas las funcionalidades de la plataforma escolar.</flux:text>
        </div>

        <flux:separator />

        {{-- Tabs Navigation --}}
        <div class="flex flex-wrap gap-2 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/50 w-fit">
            <flux:button :variant="$tab === 'parents' ? 'primary' : 'ghost'" wire:click="setTab('parents')" icon="users" size="sm">Padres</flux:button>
            @if(auth()->user()->isViewStaff())
                <flux:button :variant="$tab === 'teachers' ? 'primary' : 'ghost'" wire:click="setTab('teachers')" icon="academic-cap" size="sm">Docentes</flux:button>
                @can('admin-only')
                    <flux:button :variant="$tab === 'admin' ? 'primary' : 'ghost'" wire:click="setTab('admin')" icon="shield-check" size="sm">Admin</flux:button>
                @endcan
            @endif
            <flux:button :variant="$tab === 'config' ? 'primary' : 'ghost'" wire:click="setTab('config')" icon="cog-6-tooth" size="sm">Configuración</flux:button>
        </div>

        {{-- Grid view --}}
        <div class="mt-2 animate-in fade-in slide-in-from-bottom-4 duration-500">
            @if($tab === 'parents')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="document-text" title="Ver y Firmar Reportes" description="Consulta el historial disciplinario y firma de enterado." name="tutorial-p-reports" />
                <x-tutorial-card icon="megaphone" title="Avisos y Autorizaciones" description="Mantente al día con los eventos y firma permisos digitales." name="tutorial-p-notices" />
                <x-tutorial-card icon="calendar-days" title="Atender Citatorios" description="Confirma asistencia a reuniones con el personal docente." name="tutorial-p-citations" />
                <x-tutorial-card icon="book-open" title="Calendario de Exámenes" description="Consulta las fechas de evaluación de todos tus hijos." name="tutorial-p-exams" />
                <x-tutorial-card icon="heart" title="Servicio Comunitario" description="Seguimiento de actividades reparatorias asignadas." name="tutorial-p-community" />
            </div>
            @endif

            @if($tab === 'teachers')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="pencil-square" title="Registrar Reporte" description="Documenta faltas al reglamento o méritos académicos." name="tutorial-d-create-report" />
                <x-tutorial-card icon="clipboard-document-list" title="Programar Evaluaciones" description="Agenda exámenes para que padres y alumnos los visualicen." name="tutorial-d-exams" />
                <x-tutorial-card icon="calendar" title="Generar Citatorios" description="Coordina reuniones presenciales con padres de familia." name="tutorial-d-citations" />
                <x-tutorial-card icon="megaphone" title="Publicar Avisos" description="Comunícate de forma masiva con padres de tus grupos." name="tutorial-d-notices" />
                <x-tutorial-card icon="user-group" title="Asignar Servicio" description="Asigna actividades de servicio comunitario por reportes acumulados." name="tutorial-d-community" />
            </div>
            @endif

            @can('admin-only')
            @if($tab === 'admin')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="users" title="Gestión de Usuarios" description="Administra cuentas, restablece contraseñas y permisos." name="tutorial-a-users" />
                <x-tutorial-card icon="arrow-path" title="Control de Ciclos" description="Configura periodos escolares y activa el ciclo vigente." name="tutorial-a-cycles" />
                <x-tutorial-card icon="document-duplicate" title="Editar Reglamento" description="Actualiza la normativa institucional en tiempo real." name="tutorial-a-regulations" />
                <x-tutorial-card icon="user-plus" title="Inscripción Rápida" description="Registra nuevos alumnos y vincula a sus tutores." name="tutorial-a-inscribe" />
                <x-tutorial-card icon="cloud-arrow-down" title="Importación Masiva" description="Carga masiva de datos mediante archivos Excel/CSV." name="tutorial-a-import" />
                <x-tutorial-card icon="cloud-arrow-up" title="Exportación de Datos" description="Genera respaldos y listas en formatos exportables." name="tutorial-a-export" />
            </div>
            @endif
            @endcan

            @if($tab === 'config')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="device-phone-mobile" title="Instalar Aplicación" description="Cómo instalar la app en Android o iOS (PWA)." name="tutorial-c-install" />
                <x-tutorial-card icon="bell-alert" title="Notificaciones" description="Activa las alertas push para no perderte nada." name="tutorial-c-notifications" />
            </div>
            @endif
        </div>
    @else
        {{-- Article view (Hostinger Style) --}}
        <div class="animate-in fade-in slide-in-from-left-4 duration-500">
            <button wire:click="back" class="flex items-center gap-2 text-zinc-500 hover:text-zinc-900 dark:hover:text-white mb-6 group transition-colors">
                <flux:icon icon="arrow-left" variant="micro" class="group-hover:-translate-x-1 transition-transform" />
                <span class="text-sm font-medium">Volver a tutoriales</span>
            </button>

            <div class="flex flex-col lg:flex-row gap-12 relative">
                {{-- Sidebar TOC (Desktop) --}}
                <aside class="hidden lg:block w-72 shrink-0">
                    <div class="sticky top-6">
                        <flux:heading size="sm" class="uppercase tracking-widest text-zinc-400 mb-4 px-2">En este artículo</flux:heading>
                        <nav class="space-y-1">
                            @foreach($this->getSections($selectedTutorial) as $section)
                                <a href="#{{ $section['id'] }}" class="flex items-center justify-between group px-3 py-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $section['title'] }}</span>
                                    <flux:icon icon="chevron-right" variant="micro" class="text-zinc-300 group-hover:text-indigo-600 transition-colors" />
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Mobile TOC Trigger --}}
                <div class="lg:hidden fixed bottom-6 right-6 z-50">
                    <flux:modal.trigger name="mobile-toc">
                        <flux:button variant="primary" icon="list-bullet" class="shadow-2xl shadow-indigo-500/50 rounded-full h-14 w-40">
                            En este artículo
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="mobile-toc" position="right" class="w-80">
                    <div class="space-y-6">
                        <flux:heading size="lg">En este artículo</flux:heading>
                        <nav class="space-y-2">
                            @foreach($this->getSections($selectedTutorial) as $section)
                                <a href="#{{ $section['id'] }}" x-on:click="$dispatch('close-modal', { name: 'mobile-toc' })" class="flex items-center justify-between p-3 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 transition-colors">
                                    <span class="text-sm font-medium">{{ $section['title'] }}</span>
                                    <flux:icon icon="chevron-right" variant="micro" />
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </flux:modal>

                {{-- Article Content --}}
                <article class="flex-1 max-w-4xl">
                    {{-- Article Header --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        <flux:badge size="sm" color="indigo" inset="left" class="uppercase tracking-tighter">{{ $this->getCategory($selectedTutorial) }}</flux:badge>
                        <flux:badge size="sm" variant="outline" class="uppercase tracking-tighter">Guía Paso a Paso</flux:badge>
                    </div>

                    <flux:heading size="3xl" level="1" class="mb-6">{{ $this->getTitle($selectedTutorial) }}</flux:heading>

                    <div class="flex items-center gap-6 mb-12 py-6 border-y border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-linear-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-[10px] text-white font-black shadow-sm">SM</div>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-tighter">Equipo de Soporte SM</span>
                        </div>
                        <div class="text-xs text-zinc-400 font-medium uppercase tracking-widest">{{ now()->format('d M, Y') }}</div>
                        <div class="text-xs text-zinc-400 font-medium flex items-center gap-1.5 uppercase tracking-widest">
                            <flux:icon icon="clock" variant="micro" class="text-zinc-300" />
                            Lectura de {{ $this->getReadTime($selectedTutorial) }} min
                        </div>
                    </div>

                    {{-- Main Content (Rendered from a separate method/component for cleanliness) --}}
                    <div class="prose prose-indigo dark:prose-invert max-w-none prose-h2:text-2xl prose-h2:font-black prose-h2:mt-12 prose-h2:mb-6 prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-p:leading-relaxed prose-li:text-zinc-600 dark:prose-li:text-zinc-400">
                        {!! $this->getContent($selectedTutorial) !!}
                    </div>
                </article>
            </div>
        </div>
    @endif

    <style>
        .wrap-break-word { word-break: break-all; }
        aside.sticky { height: calc(100vh - 3rem); }
        article h2 { scroll-margin-top: 1.5rem; }
    </style>
</div>