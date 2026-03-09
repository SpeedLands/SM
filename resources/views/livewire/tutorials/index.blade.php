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
            'tutorial-d-create-report' => 'Gestión de Reportes Disciplinarios',
            'tutorial-d-exams' => 'Programación de Exámenes y Evaluaciones',
            'tutorial-d-citations' => 'Gestión de Citatorios para Padres',
            'tutorial-d-notices' => 'Publicación de Avisos Masivos',
            'tutorial-d-community' => 'Asignación de Actividades Reparatorias',
            'tutorial-a-users' => 'Panel de Administración de Usuarios',
            'tutorial-a-cycles' => 'Configuración de Ciclos Escolares',
            'tutorial-a-regulations' => 'Edición del Reglamento Institucional',
            'tutorial-a-inscribe' => 'Gestión del Apartado de Alumnos',
            'tutorial-a-import' => 'Guía de Importación Masiva de Datos',
            'tutorial-a-export' => 'Exportación de Listas y Reportes',
            'tutorial-a-promote' => 'Guía para Promover Alumnos de Ciclo',
            'tutorial-a-report-types' => 'Gestión de Tipos de Reportes',
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
        // Custom sections for user management tutorial
        if ($name === 'tutorial-a-users') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'añadir', 'title' => '1. Añadir nuevo usuario'],
                ['id' => 'buscar', 'title' => '2. Buscar usuarios'],
                ['id' => 'editar', 'title' => '3. Editar usuarios'],
                ['id' => 'bloquear', 'title' => '4. Bloquear usuarios'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for regulation tutorial
        if ($name === 'tutorial-a-regulations') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'acceder', 'title' => '1. Acceder al reglamento'],
                ['id' => 'editar', 'title' => '2. Editar el reglamento'],
                ['id' => 'herramientas', 'title' => '3. Campos y herramientas'],
                ['id' => 'guardar', 'title' => '4. Guardar cambios'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for cycles tutorial
        if ($name === 'tutorial-a-cycles') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'registrar', 'title' => '1. Registrar nuevo ciclo'],
                ['id' => 'activar', 'title' => '2. Activar/Desactivar ciclo'],
                ['id' => 'editar', 'title' => '3. Editar ciclo escolar'],
                ['id' => 'salones', 'title' => '4. Gestionar grupos'],
                ['id' => 'borrar', 'title' => '5. Borrar ciclo escolar'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for promote students tutorial
        if ($name === 'tutorial-a-promote') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'acceder', 'title' => '1. Acceder al apartado'],
                ['id' => 'configurar', 'title' => '2. Configurar origen y destino'],
                ['id' => 'promover', 'title' => '3. Promover alumnos'],
                ['id' => 'verificar', 'title' => '4. Verificar el cambio'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for import data tutorial
        if ($name === 'tutorial-a-import') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'acceder', 'title' => '1. Acceder al apartado'],
                ['id' => 'cargar', 'title' => '2. Cargar archivo'],
                ['id' => 'configurar', 'title' => '3. Configuración'],
                ['id' => 'previsualizar', 'title' => '4. Previsualizar datos'],
                ['id' => 'resultados', 'title' => '5. Resultados'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for community service tutorial
        if ($name === 'tutorial-d-community') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'asignar', 'title' => '1. Asignar servicio'],
                ['id' => 'buscar', 'title' => '2. Buscar servicios'],
                ['id' => 'firmar', 'title' => '3. Firmar servicios'],
                ['id' => 'borrar', 'title' => '4. Borrar servicios'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for notices tutorial
        if ($name === 'tutorial-d-notices') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'agregar', 'title' => '1. Agregar aviso'],
                ['id' => 'buscar', 'title' => '2. Buscar avisos'],
                ['id' => 'editar', 'title' => '3. Editar avisos'],
                ['id' => 'borrar', 'title' => '4. Borrar avisos'],
                ['id' => 'firmas', 'title' => '5. Visualizar firmas'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for citations tutorial
        if ($name === 'tutorial-d-citations') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'agregar', 'title' => '1. Agregar citatorio'],
                ['id' => 'buscar', 'title' => '2. Buscar citatorios'],
                ['id' => 'editar', 'title' => '3. Editar citatorios'],
                ['id' => 'borrar', 'title' => '4. Borrar citatorios'],
                ['id' => 'firmar', 'title' => '5. Firmar citatorios'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for exams tutorial
        if ($name === 'tutorial-d-exams') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'programar', 'title' => '1. Programar examen'],
                ['id' => 'buscar', 'title' => '2. Buscar exámenes'],
                ['id' => 'borrar', 'title' => '3. Borrar examen'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for report types tutorial
        if ($name === 'tutorial-a-report-types') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'acceder', 'title' => '1. Acceder al apartado'],
                ['id' => 'agregar', 'title' => '2. Añadir tipo de reporte'],
                ['id' => 'editar', 'title' => '3. Modificar tipo de reporte'],
                ['id' => 'borrar', 'title' => '4. Eliminar tipo de reporte'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for students tutorial
        if ($name === 'tutorial-a-inscribe') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'inscribir', 'title' => '1. Inscribir alumno'],
                ['id' => 'vincular', 'title' => '2. Vincular padres'],
                ['id' => 'buscar', 'title' => '3. Buscar alumnos'],
                ['id' => 'editar', 'title' => '4. Editar alumno'],
                ['id' => 'borrar', 'title' => '5. Borrar alumno'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

        // Custom sections for reports tutorial
        if ($name === 'tutorial-d-create-report') {
            return [
                ['id' => 'intro', 'title' => 'Introducción'],
                ['id' => 'requisitos', 'title' => 'Requisitos previos'],
                ['id' => 'crear', 'title' => '1. Crear reporte'],
                ['id' => 'buscar', 'title' => '2. Buscar reportes'],
                ['id' => 'borrar', 'title' => '3. Borrar reporte'],
                ['id' => 'beneficios', 'title' => 'Tips de experto'],
            ];
        }

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
                'custom' => true,
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
                'custom' => true,
            ],
            'tutorial-a-regulations' => [
                'custom' => true,
            ],
            'tutorial-a-inscribe' => [
                'custom' => true,
            ],
            'tutorial-a-import' => [
                'custom' => true,
            ],
            'tutorial-a-export' => [
                'desc' => 'Genera reportes detallados en Excel de cualquier módulo del sistema.',
                'req' => 'Seleccionar los filtros adecuados para el reporte deseado.',
                'steps' => '<li>Elige el módulo (Ej: Reportes Disciplinarios).</li><li>Aplica filtros de fecha o grado.</li><li>Haz clic en "Exportar a Excel".</li>',
                'tip' => 'Las exportaciones son ideales para juntas de consejo o análisis estadísticos.',
                'edit' => '',
                'delete' => ''
            ],
            'tutorial-a-users' => [
                'custom' => true,
            ],
            'tutorial-a-promote' => [
                'custom' => true,
            ],
            'tutorial-d-community' => [
                'custom' => true,
            ],
            'tutorial-d-notices' => [
                'custom' => true,
            ],
            'tutorial-d-citations' => [
                'custom' => true,
            ],
            'tutorial-d-exams' => [
                'custom' => true,
            ],
            'tutorial-a-report-types' => [
                'custom' => true,
            ],
            'tutorial-c-notifications' => [
                'desc' => 'Activa las notificaciones push para recibir alertas al instante sobre reportes, citatorios y avisos.',
                'req' => 'Un navegador moderno (Chrome, Safari, Edge) con soporte para Service Workers.',
                'steps' => '<li>Ve a tu perfil (abajo a la izquierda).</li><li>Selecciona "Configuración" en el menú desplegable.</li><li>Haz clic en la pestaña "Notificaciones".</li><li>Presiona el botón "Activar" y concede los permisos en tu navegador.</li>',
                'tip' => 'Si activas las notificaciones pero no las recibes, revisa que tu sistema operativo (Windows/Android/iOS) no esté en modo "No molestar".',
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

        // Custom full-page content
        if (!empty($specifics['custom'])) {
            if ($name === 'tutorial-a-users') return $this->getUserManagementContent();
            if ($name === 'tutorial-a-regulations') return $this->getRegulationContent();
            if ($name === 'tutorial-a-cycles') return $this->getCyclesContent();
            if ($name === 'tutorial-a-promote') return $this->getPromoteContent();
            if ($name === 'tutorial-a-import') return $this->getImportContent();
            if ($name === 'tutorial-d-community') return $this->getCommunityServiceContent();
            if ($name === 'tutorial-d-notices') return $this->getNoticesContent();
            if ($name === 'tutorial-d-citations') return $this->getCitationsContent();
            if ($name === 'tutorial-d-exams') return $this->getExamsContent();
            if ($name === 'tutorial-a-report-types') return $this->getReportTypesContent();
            if ($name === 'tutorial-a-inscribe') return $this->getStudentsContent();
            if ($name === 'tutorial-d-create-report') return $this->getReportsContent();
        }

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

    public function getUserManagementContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Gestión de Usuarios</strong> permite administrar las cuentas de acceso a la plataforma escolar. Desde aquí podrás crear cuentas para maestros, administrativos y padres de familia, así como buscar, editar y bloquear usuarios según sea necesario.</p>

            {$img('gestionUsuarios/menu.png', 'Vista general del apartado de Gestión de Usuarios en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener los datos del usuario a registrar: nombre completo, correo electrónico y rol.</li>
            </ul>

            <h2 id='añadir'>1. Añadir nuevo usuario</h2>
            <p>Para registrar una nueva cuenta de usuario en la plataforma, siga los siguientes pasos:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Gestión de Usuarios\"</strong> que se encuentra en el menú lateral izquierdo. Esto le mostrará la lista de usuarios registrados.')}

            {$img('gestionUsuarios/filaUsuario.png', 'Tabla de usuarios registrados en la plataforma')}

            {$step(2, 'Presione el botón <strong>\"Añadir Nuevo Usuario\"</strong> ubicado en la parte superior. Se abrirá un formulario con los campos necesarios.')}

            {$img('gestionUsuarios/botonA%C3%B1adirNuevoUsuario.png', 'Botón para añadir un nuevo usuario en la parte superior de la tabla')}

            {$img('gestionUsuarios/formularioNuevoUsuario.png', 'Formulario vacío para registrar un nuevo usuario')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Nombre</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo obligatorio. Coloque el nombre completo del usuario.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Correo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo obligatorio. La cuenta debe seguir el formato de número de teléfono con terminación <code>@escuela.edu.mx</code>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Rol</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Seleccione el tipo de usuario: <strong>Maestro</strong>, <strong>Administrativo</strong> o <strong>Padre/Tutor</strong>. Dependiendo del rol, el usuario podrá acceder a diferentes apartados.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Teléfono</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Exclusivo del rol <strong>Padre/Tutor</strong>. Registre el número de teléfono del padre o tutor.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Contraseña</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo obligatorio. Debe tener una longitud mínima de <strong>8 caracteres</strong>.</span>
                </div>
            </div>

            {$img('gestionUsuarios/formularioLLenadoA%C3%B1adirNuevoUsuario.png', 'Formulario completado con los datos del nuevo usuario')}

            {$step(4, 'Presione el botón <strong>\"Guardar\"</strong> para crear la cuenta. El nuevo usuario aparecerá en la tabla.')}

            {$img('gestionUsuarios/resultadoNuevoUsuarioAgregado.png', 'Nuevo usuario creado y visible en la tabla de usuarios')}

            <h2 id='buscar'>2. Buscar usuarios</h2>
            <p>La plataforma ofrece herramientas de filtrado para localizar usuarios de forma rápida.</p>

            {$step(1, 'En el apartado de Gestión de Usuarios, ubique la sección de filtros en la parte superior de la tabla.')}

            {$img('gestionUsuarios/camposFiltroBuscarUsuario.png', 'Campos de filtro para buscar usuarios por nombre, correo o rol')}

            {$step(2, 'Utilice los campos de búsqueda disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Búsqueda</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite buscar usuarios por <strong>nombre</strong> o <strong>correo electrónico</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtrar por Rol</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre por tipo de usuario: <strong>Todos los roles</strong>, <strong>Administrativo</strong>, <strong>Maestro</strong> o <strong>Padre/Tutor</strong>.</span>
                </div>
            </div>

            {$img('gestionUsuarios/resultadoBuscarPorNombre.png', 'Resultado al buscar un usuario por nombre')}
            {$img('gestionUsuarios/resultadoBuscarPorRol.png', 'Resultado al filtrar usuarios por rol')}

            <h2 id='editar'>3. Editar usuarios</h2>
            <p>Para modificar la información de un usuario existente:</p>

            {$step(1, 'En la tabla de usuarios, localice el registro que desea modificar.')}
            {$img('gestionUsuarios/usuarioHaEditar.png', 'Fila del usuario seleccionado para editar')}
            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> (editar). Se abrirá un formulario con los datos actuales del usuario.')}

            {$img('gestionUsuarios/formularioUsuarioEditado.png', 'Formulario de edición con los datos actuales del usuario seleccionado')}

            {$step(3, 'Modifique los campos necesarios:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Nombre</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Puede actualizar el nombre completo del usuario.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Correo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Actualice el correo electrónico del usuario manteniendo el formato <code>@escuela.edu.mx</code>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Rol</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Cambie el rol del usuario entre Maestro, Administrativo o Padre/Tutor.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Contraseña</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo opcional — dejarlo vacío mantendrá la contraseña actual. Si desea cambiarla, debe tener mínimo <strong>8 caracteres</strong>.</span>
                </div>
            </div>

            {$img('gestionUsuarios/formularioLLenadoEditarUsuario.png', 'Formulario con los datos modificados del usuario')}

            {$step(4, 'Presione el botón <strong>\"Actualizar\"</strong> para guardar los cambios.')}

            {$img('gestionUsuarios/resultadoUsuarioEditado.png', 'Usuario actualizado correctamente en la tabla')}

            <h2 id='bloquear'>4. Bloquear usuarios</h2>
            <p>Si necesita impedir que un usuario acceda a la plataforma sin eliminar su cuenta:</p>

            {$step(1, 'En la tabla de usuarios, localice el registro del usuario a bloquear.')}
            {$step(2, 'Presione el ícono de <strong>\"Candado\"</strong>. Se mostrará un cuadro de confirmación.')}

            {$img('gestionUsuarios/modalBloquearUsuario.png', 'Cuadro de confirmación para bloquear al usuario seleccionado')}

            {$step(3, 'Confirme la acción. El usuario bloqueado no podrá iniciar sesión en la plataforma.')}

            {$img('gestionUsuarios/candadoAbierto.png', 'El ícono cambia a un candado abierto indicando que el usuario está bloqueado')}

            {$important('Al bloquear un usuario, el ícono cambiará a un <strong>candado abierto</strong>, indicando que la cuenta está bloqueada. El usuario verá un mensaje informándole que su acceso ha sido restringido. Para desbloquear, simplemente vuelva a presionar el ícono del candado.')}

            {$img('gestionUsuarios/mensajeBloqueadoUsuario.png', 'Mensaje que visualiza el usuario cuando su cuenta ha sido bloqueada')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Prefiera bloquear antes que eliminar</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Si un usuario ya no requiere acceso, bloquéelo en lugar de eliminarlo. Esto preserva el historial de actividades vinculado a esa cuenta.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Formato de correo consistente</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Asegúrese de que todos los correos sigan el formato del número de teléfono con <code>@escuela.edu.mx</code> para mantener consistencia en el sistema.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use filtros para gestión masiva</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Filtre por rol para revisar rápidamente todas las cuentas de un tipo específico, ideal para inicio de ciclo escolar.</p>
                    </div>
                </div>
            </div>
        ";
    }
    public function getRegulationContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Reglamento</strong> permite visualizar y editar el reglamento escolar institucional. Este documento es visible para todos los usuarios de la plataforma y establece las normas de convivencia, sanciones y lineamientos generales de la institución.</p>

            {$img('reglamento/menu.png', 'Vista general del apartado de Reglamento en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener definido el contenido y título del reglamento institucional aprobado por el consejo escolar.</li>
            </ul>

            <h2 id='acceder'>1. Acceder al reglamento</h2>
            <p>Para visualizar el reglamento escolar actual:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Reglamento\"</strong> que se encuentra en el menú lateral izquierdo. Con esta acción, podrá visualizar el reglamento escolar vigente.')}

            {$img('reglamento/menu.png', 'Opción de Reglamento ubicada en el menú lateral izquierdo')}

            {$important('El reglamento es visible para <strong>todos los usuarios</strong> de la plataforma (administradores, maestros y padres de familia). Cualquier cambio se reflejará de inmediato para todos.')}

            <h2 id='editar'>2. Editar el reglamento</h2>
            <p>Para modificar el contenido del reglamento institucional:</p>

            {$step(1, 'Una vez en el apartado de Reglamento, localice y presione el botón <strong>\"Editar Reglamento\"</strong>. Esto iniciará el proceso de modificación.')}

            {$img('reglamento/botonEditarReglamento.png', 'Botón Editar Reglamento para iniciar la modificación')}

            {$step(2, 'Se abrirá una <strong>caja de herramientas</strong> que le permitirá editar el contenido del reglamento, incluyendo campos de texto y opciones de formato.')}

            {$img('reglamento/cajaHerramientasEditar.png', 'Caja de herramientas con opciones de edición del reglamento')}

            <h2 id='herramientas'>3. Campos y herramientas</h2>
            <p>El editor de reglamento cuenta con los siguientes campos y herramientas de formato:</p>

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Título</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Aquí colocará el título que represente al reglamento escolar.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Contenido</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>La descripción y detalles del reglamento que será visible por todos los usuarios de la plataforma.</span>
                </div>
            </div>

            <p>Además, la barra de herramientas ofrece las siguientes opciones de formato:</p>

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Negrita</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Resalta texto importante con <strong>formato en negrita</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Cursiva</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Aplica <em>formato cursiva</em> para énfasis o referencias.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Subrayado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Subraya texto para destacar puntos relevantes.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Tachado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Subraya por el medio del texto para indicar texto eliminado o corregido.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Títulos</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Cambie el tamaño del texto para crear secciones y subtítulos dentro del reglamento.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Alineación</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Alinee el texto a la izquierda, centro o derecha según la necesidad del documento.</span>
                </div>
            </div>

            <h2 id='guardar'>4. Guardar cambios</h2>
            <p>Una vez que haya realizado las modificaciones necesarias al reglamento:</p>

            {$step(1, 'Verifique que el <strong>título</strong> y el <strong>contenido</strong> del reglamento estén correctos.')}

            {$step(2, 'Presione el botón <strong>\"Guardar Cambios\"</strong> para aplicar las modificaciones.')}

            {$img('reglamento/guardarCambios.png', 'Botón Guardar Cambios para aplicar las modificaciones al reglamento')}

            {$step(3, 'Los cambios se verán reflejados inmediatamente en el apartado de Reglamento para <strong>todos los usuarios</strong>.')}

            {$img('reglamento/cambiosReflejados.png', 'Reglamento actualizado visible para todos los usuarios')}

            {$important('Una vez guardados los cambios, el reglamento actualizado será visible de inmediato para todos los usuarios de la plataforma. Asegúrese de que el contenido sea el correcto antes de guardar.')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Estructura clara con títulos</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Use las herramientas de <strong>tamaño de título</strong> para organizar el reglamento en secciones claras. Esto facilita la lectura tanto para maestros como para padres de familia.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Resalte las sanciones importantes</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Utilice <strong>negrita</strong> y <strong>subrayado</strong> para destacar las faltas graves y sus consecuencias. Un reglamento claro reduce las apelaciones de los padres.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Actualice al inicio de cada ciclo</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Revise y actualice el reglamento al inicio de cada ciclo escolar para reflejar los acuerdos más recientes del consejo escolar.</p>
                    </div>
                </div>
            </div>
        ";
    }
    public function getCyclesContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Ciclos Escolares</strong> permite administrar los periodos académicos de la institución. Desde aquí podrá crear nuevos ciclos, activarlos, editarlos, gestionar los grupos (salones) de cada ciclo y eliminar ciclos que ya no sean necesarios.</p>

            {$img('ciclosEscolares/menu.png', 'Vista general del apartado de Ciclos Escolares en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener definidos los años que comprenderá el nuevo ciclo escolar.</li>
            </ul>

            <h2 id='registrar'>1. Registrar nuevo ciclo escolar</h2>
            <p>Para crear un nuevo ciclo escolar en la plataforma:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Ciclos Escolares\"</strong> que se encuentra en el menú lateral izquierdo. Con esta acción, podrá visualizar el apartado de Ciclos Escolares.')}

            {$img('ciclosEscolares/tablalistaCiclos.png', 'Apartado de Ciclos Escolares con la lista de ciclos registrados')}

            {$step(2, 'Diríjase al recuadro que dice <strong>\"Registrar Nuevo Ciclo\"</strong>. Aquí podrá añadir un nuevo ciclo escolar, asignándole los años que comprenderá dicho ciclo.')}

            {$img('ciclosEscolares/modalRegistrarCiclo.png', 'Formulario para registrar un nuevo ciclo escolar')}

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Nombre del Ciclo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Debe respetar el formato que se muestra en el recuadro (ejemplo: <code>2025-2026</code>).</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Ciclo Activo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Opción para activar el ciclo escolar y usarlo apenas sea creado.</span>
                </div>
            </div>

            {$step(3, 'Presione el botón <strong>\"Guardar\"</strong> para registrar el ciclo. El nuevo ciclo aparecerá en la Lista de Ciclos.')}

            {$important('El formato del nombre del ciclo debe seguir el patrón de años (ejemplo: <strong>2025-2026</strong>). Esto es importante para mantener la consistencia en todo el sistema.')}

            <h2 id='activar'>2. Activar/Desactivar ciclo escolar</h2>
            <p>Para cambiar el ciclo activo de la plataforma:</p>

            {$step(1, 'Primero deberá haber creado ciclos escolares. Para visualizarlos, estos se encontrarán en el recuadro con el título de <strong>\"Lista de Ciclos\"</strong>. Una vez visualizados, seleccione el ciclo escolar presionando el ícono de <strong>\"Lápiz\"</strong>.')}

            {$img('ciclosEscolares/tablalistaCiclos2.png', 'Lista de Ciclos con el ícono de edición para seleccionar un ciclo')}

            {$step(2, 'Una vez seleccionado, el recuadro de registro cambiará a <strong>\"Editar Ciclo\"</strong> donde verá el nombre del ciclo elegido. Para activarlo, presione el switch del campo <strong>\"Ciclo Activo\"</strong> y presione el botón <strong>\"Actualizar\"</strong>.')}

            {$img('ciclosEscolares/cuadroEditarActivarCiclo.png', 'Cuadro de Editar Ciclo con el switch de Ciclo Activo')}

            {$img('ciclosEscolares/cicloAFaltaDeSerActivado.png', 'Ciclo escolar a punto de ser activado con el switch encendido')}

            {$step(3, 'Una vez activado, visualizará tanto en el <strong>tablero general</strong> como en el apartado de Ciclos Escolares que el ciclo activo es el previamente seleccionado.')}

            {$img('ciclosEscolares/cicloActivado.png', 'Ciclo escolar activado correctamente en la lista')}
            {$img('ciclosEscolares/visualizacionTableroCicloActivado.png', 'Tablero general mostrando el ciclo activo actualizado')}

            {$important('Al activar un ciclo, podrá iniciar un nuevo periodo con nuevos reportes, servicios comunitarios, avisos, entre otros. Solo debe haber <strong>un ciclo activo</strong> a la vez.')}

            <h2 id='editar'>3. Editar ciclo escolar</h2>
            <p>Para modificar el nombre o estado de un ciclo existente:</p>

            {$step(1, 'En la <strong>\"Lista de Ciclos\"</strong>, seleccione el ciclo escolar que desea editar presionando el ícono de <strong>\"Lápiz\"</strong>.')}

            {$img('ciclosEscolares/tablalistaCiclos3.png', 'Lista de Ciclos con el ícono de lápiz para editar')}

            {$step(2, 'El recuadro cambiará a <strong>\"Editar Ciclo\"</strong> mostrando el nombre del ciclo elegido. Podrá cambiar el nombre del ciclo siguiendo el formato de años, así como activar o desactivar el ciclo.')}

            {$img('ciclosEscolares/cicloCampoNombreAEditar.png', 'Campo de nombre del ciclo listo para ser editado')}
            {$img('ciclosEscolares/cicloCampoNombreAPuntoDeEditar.png', 'Campo de nombre del ciclo con el nuevo valor ingresado')}

            {$step(3, 'Una vez modificados los campos, presione el botón <strong>\"Actualizar\"</strong> para guardar los cambios.')}

            {$img('ciclosEscolares/cicloCambiadoEditatoNombre.png', 'Ciclo actualizado correctamente en la lista de ciclos')}
            {$img('ciclosEscolares/cicloCambiadoNombreTablero.png', 'Tablero general reflejando el cambio del nombre del ciclo')}

            <h2 id='salones'>4. Gestionar grupos</h2>
            <p>Cada ciclo escolar puede tener grupos (salones) asignados. Para administrarlos:</p>

            {$step(1, 'En la <strong>\"Lista de Ciclos\"</strong>, seleccione el ciclo escolar presionando el ícono de <strong>\"Personas\"</strong>. Esto abrirá un recuadro para gestionar los grupos.')}

            {$img('ciclosEscolares/tablalistaCiclos.png', 'Lista de Ciclos con el ícono de personas para gestionar grupos')}

            <h3>Añadir un grupo</h3>

            {$step(2, 'En el recuadro, complete los campos para crear un nuevo grupo:')}

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Seleccione el grado escolar del grupo.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Sección</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Indique la sección o letra del grupo (A, B, C, etc.).</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Tutor</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Asigne un maestro como tutor responsable del grupo.</span>
                </div>
            </div>

            {$img('ciclosEscolares/modalA%C3%B1adirSalon.png', 'Recuadro para añadir un nuevo grupo al ciclo escolar')}
            {$img('ciclosEscolares/modalA%C3%B1adirSalonCamposLLenados.png', 'Campos del grupo completados listos para guardar')}

            {$step(3, 'Presione el botón <strong>\"Guardar\"</strong>. El nuevo grupo se visualizará en el mismo recuadro.')}

            {$img('ciclosEscolares/salonA%C3%B1adido.png', 'Grupo añadido correctamente al ciclo escolar')}

            <h3>Modificar un grupo</h3>

            {$step(4, 'Para modificar un grupo, presione el ícono de <strong>\"Lápiz\"</strong> del grupo correspondiente. Esto llenará automáticamente los campos con los datos actuales del grupo.')}

            {$img('ciclosEscolares/modificarSalonMismaInformacion.png', 'Campos del grupo cargados con la información actual')}

            {$step(5, 'Modifique los datos necesarios y presione el botón <strong>\"Actualizar\"</strong>. Para cancelar, presione el botón <strong>\"X\"</strong>.')}

            {$img('ciclosEscolares/modificarSalonInformacionCambiada.png', 'Campos del grupo con los nuevos datos ingresados')}
            {$img('ciclosEscolares/resultadoSalonModificado.png', 'Grupo modificado correctamente')}

            <h3>Borrar un grupo</h3>

            {$step(6, 'Para borrar un grupo, presione el ícono de <strong>\"Bote de Basura\"</strong> del grupo correspondiente.')}

            {$img('ciclosEscolares/borrarGrupo.png', 'Ícono de bote de basura para eliminar un grupo')}

            {$important('Para poder borrar un grupo con éxito, deberá <strong>desvincular los alumnos</strong> que estén registrados en dicho grupo. De lo contrario, el ícono de borrar estará bloqueado.')}

            <h2 id='borrar'>5. Borrar ciclo escolar</h2>
            <p>Para eliminar un ciclo escolar que ya no sea necesario:</p>

            {$step(1, 'En el apartado de Ciclos Escolares, diríjase a la tabla <strong>\"Lista de Ciclos\"</strong>.')}

            {$img('ciclosEscolares/tablalistaCiclos4.png', 'Lista de Ciclos con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro a eliminar. Se abrirá un cuadro preguntando si está seguro de la decisión.')}

            {$img('ciclosEscolares/modaBorrarCiclo.png', 'Cuadro de confirmación para eliminar el ciclo escolar')}

            {$step(3, 'Confirme la acción para eliminar el ciclo escolar.')}

            {$img('ciclosEscolares/cicloBorrado.png', 'Ciclo escolar eliminado correctamente de la lista')}

            {$important('En algunos casos el ciclo escolar tendrá <strong>bloqueada</strong> la opción de eliminar debido a que tiene vinculados grupos, secciones, entre otros. Deberá eliminar previamente esta información para poder proceder con la eliminación del ciclo.')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Un solo ciclo activo</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Mantenga siempre <strong>un solo ciclo activo</strong> a la vez. Esto evita confusiones en los reportes, avisos y demás módulos que dependen del ciclo vigente.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Configure los grupos antes de inscribir</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Antes de inscribir alumnos, asegúrese de tener todos los <strong>grupos y secciones</strong> creados. Esto facilitará el proceso de asignación durante las inscripciones.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Asigne tutores a cada grupo</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Aunque el tutor es opcional, asignarlo permite que el maestro tenga acceso directo a los reportes y citatorios de su grupo.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>4</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>No elimine ciclos con historial</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Los ciclos anteriores contienen el historial de reportes y actividades. Prefiera <strong>desactivarlos</strong> en lugar de eliminarlos para conservar la información.</p>
                    </div>
                </div>
            </div>
        ";
    }
    public function getPromoteContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Promover Alumnos</strong> permite cambiar de grupo o ciclo escolar a los alumnos de forma masiva o individual. Esta herramienta es esencial al finalizar un ciclo escolar para trasladar a los estudiantes a su nuevo grado y sección.</p>

            {$img('promoverAlumnos/menu.png', 'Vista general del apartado de Promover Alumnos en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener <strong>ciclos escolares</strong> y <strong>grupos</strong> previamente creados en el apartado de Ciclos Escolares.</li>
                <li>Los alumnos deben estar inscritos y vinculados a un grupo del ciclo actual.</li>
            </ul>

            <h2 id='acceder'>1. Acceder al apartado</h2>
            <p>Para iniciar el proceso de promoción de alumnos:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Promover Alumnos\"</strong> que se encuentra en el menú lateral izquierdo. Con esta acción, visualizará el apartado de Promover Alumnos.')}

            <h2 id='configurar'>2. Configurar origen y destino</h2>
            <p>El apartado cuenta con dos recuadros principales que deberá configurar:</p>

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Origen</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Seleccione el <strong>ciclo actual</strong> y el <strong>grupo o salón</strong> a promover. Aquí se mostrarán los alumnos vinculados a dicho grupo. Tiene la opción de seleccionar alumnos <strong>individualmente</strong> o de forma <strong>general</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Destino</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Seleccione el <strong>ciclo al que desea promover</strong> y el <strong>grupo o salón destinado</strong> donde se trasladarán los alumnos.</span>
                </div>
            </div>

            {$img('promoverAlumnos/recuadroSinSeleccionarAlumnos.png', 'Recuadros de Origen y Destino con los campos a configurar')}

            {$important('Los ciclos escolares y los grupos deberán haber sido creados <strong>previamente</strong> en el apartado de Ciclos Escolares. Si no visualiza opciones, verifique que existan ciclos y grupos registrados.')}

            {$step(1, 'En el recuadro <strong>Origen</strong>, seleccione el ciclo escolar actual y el grupo del que provienen los alumnos.')}

            {$step(2, 'En el recuadro <strong>Destino</strong>, seleccione el ciclo escolar destino y el grupo al que serán promovidos.')}

            {$step(3, 'Seleccione los alumnos que desea promover. Puede seleccionarlos <strong>individualmente</strong> marcando cada casilla, o usar la opción de <strong>selección general</strong> para elegir a todos.')}

            {$img('promoverAlumnos/recuadroAlumnoSeleccionado.png', 'Alumnos seleccionados listos para ser promovidos')}

            <h2 id='promover'>3. Promover alumnos</h2>
            <p>Una vez configurados los campos y seleccionados los alumnos:</p>

            {$step(1, 'Presione el botón <strong>\"Promover Alumnos Seleccionados\"</strong>. Esto cambiará de grupo a los alumnos seleccionados, trasladándolos al ciclo y grupo destino.')}

            {$important('Esta acción moverá a los alumnos del grupo de origen al grupo de destino. Asegúrese de que los datos seleccionados sean correctos antes de confirmar.')}

            <h2 id='verificar'>4. Verificar el cambio</h2>
            <p>Para confirmar que la promoción se realizó correctamente:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Alumnos\"</strong> en el menú lateral.')}

            {$step(2, 'Seleccione la casilla <strong>\"Solo mostrar inscritos en ciclo actual\"</strong> para visualizar los alumnos que ahora pertenecen al nuevo ciclo y grupo.')}

            {$img('promoverAlumnos/tabla.png', 'Apartado de Promover Alumnos con los recuadros de Origen y Destino')}

            {$img('promoverAlumnos/resultado.png', 'Apartado de Alumnos mostrando los alumnos promovidos en el nuevo ciclo')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Cree primero los ciclos y grupos</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Antes de promover, asegúrese de que el <strong>ciclo destino</strong> y sus <strong>grupos</strong> ya estén configurados en el apartado de Ciclos Escolares.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Promueva por grupos completos</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Use la <strong>selección general</strong> para agilizar el proceso cuando todo el grupo avanza al mismo grado y sección.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Verifique siempre el resultado</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Después de promover, vaya al apartado de <strong>Alumnos</strong> y use el filtro de ciclo actual para confirmar que los alumnos aparecen en el grupo correcto.</p>
                    </div>
                </div>
            </div>
        ";
    }
    public function getImportContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Importar Datos</strong> permite realizar cargas masivas de información a la plataforma mediante archivos de Excel. Puede importar datos de <strong>maestros/administrativos</strong>, <strong>padres de familia</strong> y <strong>alumnos</strong>, agilizando significativamente el proceso de registro al inicio de cada ciclo escolar.</p>

            {$img('importarDatos/menu.png', 'Vista general del apartado de Importar Datos en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener un archivo Excel con extensión <strong>.xlsx</strong> o <strong>.xls</strong> con los datos a importar.</li>
                <li>Verificar que los datos del archivo no contengan duplicados.</li>
            </ul>

            <h2 id='acceder'>1. Acceder al apartado</h2>
            <p>Para iniciar el proceso de importación:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Importar Datos\"</strong> que se encuentra en el menú lateral izquierdo. Con esta acción, visualizará el apartado de Importar Datos.')}

            <h2 id='cargar'>2. Cargar archivo</h2>
            <p>El primer paso del proceso de importación es seleccionar el archivo Excel:</p>

            {$step(1, 'En la sección <strong>\"Cargar archivo\"</strong>, seleccione el archivo Excel con extensión <strong>.xlsx</strong> o <strong>.xls</strong> que desea importar.')}

            {$img('importarDatos/seleecionarArchivo.png', 'Sección para cargar el archivo Excel a importar')}

            {$step(2, 'Una vez cargado, se mostrará un indicador de carga mientras el sistema procesa el archivo.')}

            {$important('Asegúrese de que el archivo Excel tenga el formato correcto y que las columnas coincidan con los datos esperados por el sistema.')}

            <h2 id='configurar'>3. Configuración</h2>
            <p>Una vez cargado el archivo, deberá configurar qué información desea importar:</p>

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Tipo de Datos</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indique qué tipo de datos desea importar. Hay <strong>3 tipos</strong>: <strong>Maestros / Admins</strong>, <strong>Padres de Familia</strong> y <strong>Alumnos</strong>. Estos corresponden a los roles del apartado de Gestión de Usuarios y Alumnos.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Hoja de Excel</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Seleccione qué <strong>hoja del archivo Excel</strong> contiene los datos a importar.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Hoja de Padres</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>, exclusivo del tipo de dato <strong>\"Alumnos\"</strong>. Indique qué hoja del archivo Excel contiene los datos de los padres para vincularlos automáticamente con sus respectivos hijos.</span>
                </div>
            </div>

            {$img('importarDatos/importarHojas.png', 'Campos de configuración para seleccionar tipo de datos y hojas del Excel')}

            <h2 id='previsualizar'>4. Previsualizar datos</h2>
            <p>Antes de confirmar la importación, el sistema le permite revisar los datos:</p>

            {$step(1, 'En la sección <strong>\"Previsualizar\"</strong>, se mostrará una tabla con los datos contenidos en la hoja de Excel seleccionada. Verifique que la información sea correcta.')}

            {$img('importarDatos/previsualizacion.png', 'Previsualización de los datos del archivo Excel antes de importar')}

            {$step(2, 'Si los datos son correctos, presione el botón <strong>\"Importar Datos\"</strong>. Esto abrirá una ventana de confirmación.')}

            {$step(3, 'Confirme la acción para iniciar el proceso de importación.')}

            {$important('Revise cuidadosamente la previsualización antes de confirmar. Una vez importados, los datos se registrarán en el sistema.')}

            <h2 id='resultados'>5. Resultados</h2>
            <p>Una vez completada la importación, el sistema mostrará un resumen detallado con los siguientes datos:</p>

            {$img('importarDatos/importacionExitosa.png', 'Pantalla de resultados después de la importación exitosa')}

            <div class='not-prose my-4 ml-4 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Alumnos</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Número de alumnos nuevos importados al sistema.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Padres</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Número de padres nuevos importados al sistema.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Vínculos</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Número de vínculos concretados entre alumnos y padres, así como el número de errores en la vinculación.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Casos Identificados</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Casos de vinculación donde algún alumno tiene vínculo familiar o tutorado con un <strong>docente de la institución</strong>, identificados automáticamente.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Acciones de Seguimiento</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Casos pendientes de concretarse, como falta de vinculación con un padre, madre u otro tutor.</span>
                </div>
            </div>

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Limpie los datos antes de importar</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Elimine <strong>duplicados</strong> y verifique el formato de los datos en el archivo Excel antes de subirlo. Esto evitará errores de validación durante la importación.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use la hoja de padres para alumnos</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Al importar <strong>Alumnos</strong>, aproveche el campo de <strong>Hoja de Padres</strong> para vincular automáticamente a los alumnos con sus tutores en un solo paso.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Revise las acciones de seguimiento</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Después de cada importación, revise los <strong>casos pendientes</strong> en la sección de resultados para completar manualmente las vinculaciones que no se concretaron.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>4</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Siempre previsualice antes de importar</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>La previsualización le permite detectar errores en los datos antes de que se registren. Tómese el tiempo de verificar que la información sea correcta.</p>
                    </div>
                </div>
            </div>
        ";
    }
    public function getCommunityServiceContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Servicio Comunitario</strong> permite asignar, dar seguimiento y gestionar las actividades reparatorias asignadas a los alumnos. Estas actividades se generan como consecuencia de reportes acumulados y buscan la reflexión y reparación del daño por parte del estudiante.</p>

            {$img('servicioComunitario/menu.png', 'Vista general del apartado de Servicio Comunitario en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Docente</strong> o <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener alumnos inscritos en el ciclo escolar actual.</li>
                <li>El alumno debe contar con reportes acumulados que ameriten la asignación de servicio comunitario.</li>
            </ul>

            <h2 id='asignar'>1. Asignar servicio comunitario</h2>
            <p>Para asignar una actividad de servicio comunitario a un alumno:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Servicio Comunitario\"</strong> que se encuentra en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Asignar Servicio\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('servicioComunitario/agregarServicio.png', 'Botón Asignar Servicio para iniciar el proceso')}
            {$img('servicioComunitario/formularioAgregarServicio.png', 'Formulario vacío para asignar un servicio comunitario')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Buscar Alumno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque al menos <strong>3 caracteres</strong> del nombre del alumno para buscarlo. Una vez encontrado, selecciónelo haciendo clic en su nombre.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Actividad</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Detalle el tipo de actividad que deberá cumplir el alumno.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Instrucciones Adicionales</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Agregue instrucciones adicionales que el alumno deberá seguir para cumplir el servicio.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Fecha de Cumplimiento</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la fecha en la que se deberá cumplir el servicio. Por defecto muestra la fecha actual.</span>
                </div>
            </div>

            {$important('No se pueden asignar servicios comunitarios los <strong>domingos</strong>. Asegúrese de seleccionar una fecha válida.')}

            {$img('servicioComunitario/formularioLlenadoSubirServicio.png', 'Formulario completado con los datos del servicio comunitario')}

            {$step(4, 'Presione el botón <strong>\"Guardar\"</strong> para registrar el servicio. El nuevo servicio se verá reflejado en la tabla.')}

            {$img('servicioComunitario/nuevoServicioEnLaTabla.png', 'Nuevo servicio comunitario registrado en la tabla')}

            <h2 id='buscar'>2. Buscar servicios comunitarios</h2>
            <p>La plataforma ofrece herramientas de búsqueda y filtrado para localizar servicios rápidamente.</p>

            {$step(1, 'En el apartado de Servicio Comunitario, ubique los campos de búsqueda en la parte superior de la tabla.')}

            {$img('servicioComunitario/camposBusquedaServicios.png', 'Campos de búsqueda y filtrado de servicios comunitarios')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Búsqueda</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite buscar servicios por <strong>nombre del alumno</strong> o <strong>tipo de actividad</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtrar por Estado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre los servicios por su estado actual: <strong>Pendientes</strong>, <strong>Completados</strong> o <strong>No asistió</strong>.</span>
                </div>
            </div>

            {$img('servicioComunitario/campoBusqueda.png', 'Resultado al buscar un servicio por nombre de alumno')}
            {$img('servicioComunitario/campoEstados.png', 'Resultado al filtrar servicios por estado')}

            <h2 id='firmar'>3. Firmar servicios comunitarios</h2>
            <p>Una vez que el alumno ha cumplido (o no) con la actividad asignada, deberá registrar el resultado:</p>

            {$step(1, 'En la tabla de servicios comunitarios, ubique el registro correspondiente.')}

            {$img('servicioComunitario/tablaServiciosPorFirmar.png', 'Tabla de servicios con los botones de acción para firmar')}

            {$step(2, 'En la columna de <strong>\"Acciones\"</strong>, visualizará dos botones:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider pt-0.5'>✓ Palomita</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica que el servicio fue <strong>cumplido</strong>. El estado cambiará a <strong>Completado</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider pt-0.5'>✗ Equis</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica que el servicio <strong>no fue cumplido</strong>. El estado cambiará a <strong>No asistió</strong>.</span>
                </div>
            </div>

            {$img('servicioComunitario/servicioFirmado.png', 'Servicio comunitario firmado con el estado actualizado')}

            <h2 id='borrar'>4. Borrar servicios comunitarios</h2>
            <p>Para eliminar un servicio comunitario registrado:</p>

            {$step(1, 'En la tabla de servicios comunitarios, localice el registro a eliminar.')}

            {$img('servicioComunitario/tablaServicioPorBorrar.png', 'Tabla de servicios con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('servicioComunitario/modalEliminacion.png', 'Cuadro de confirmación para eliminar el servicio comunitario')}

            {$step(3, 'Presione el botón <strong>\"Eliminar\"</strong> para confirmar. El servicio será eliminado de la tabla.')}

            {$img('servicioComunitario/servicioEliminado.png', 'Servicio comunitario eliminado correctamente de la tabla')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Asigne actividades claras y específicas</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Describa la actividad con detalle para que el alumno y el padre de familia entiendan exactamente qué se espera. Use las instrucciones adicionales para dar contexto.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Firme los servicios a tiempo</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Registre el cumplimiento o incumplimiento del servicio en la fecha establecida. Esto mantiene actualizado el historial del alumno y notifica a los padres.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use los filtros para dar seguimiento</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Filtre por estado <strong>\"Pendientes\"</strong> para identificar rápidamente los servicios que aún no han sido firmados y requieren atención.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getNoticesContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Avisos</strong> permite crear, buscar, editar, eliminar y dar seguimiento a comunicados dirigidos a padres de familia y docentes. Los avisos pueden ser de distintos tipos y permiten verificar quién los ha leído mediante un sistema de firmas.</p>

            {$img('avisos/menu.png', 'Vista general del apartado de Avisos en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Docente</strong> o <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener un ciclo escolar activo con alumnos y padres registrados.</li>
                <li>El mensaje debe estar autorizado por la dirección si es de carácter general.</li>
            </ul>

            <h2 id='agregar'>1. Agregar aviso</h2>
            <p>Para crear y publicar un nuevo aviso:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Avisos\"</strong> que se encuentra en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Nuevo Aviso\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('avisos/botonNuevoAviso.png', 'Botón Nuevo Aviso para iniciar la creación')}
            {$img('avisos/modalAgregarAviso.png', 'Formulario vacío para crear un nuevo aviso')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Título del Aviso</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque un título representativo o general para el mensaje.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Tipo de Aviso</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite elegir el tipo de aviso. Existen tres tipos: <strong>General</strong>, <strong>Urgente</strong> y <strong>Evento</strong>.</span>
                </div>
            </div>

            {$important('Al seleccionar el tipo <strong>Evento</strong>, se muestran 2 campos adicionales: <strong>Fecha de Evento</strong> (para agendar el día) y <strong>Hora</strong> (para agendar la hora del evento).')}

            {$img('avisos/camposEspecialesAvisoEvento.png', 'Campos adicionales al seleccionar tipo Evento')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Dirigido a</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Define a qué público irá dirigido. <strong>Todo el plantel</strong> (docentes y padres) o <strong>Solo padres</strong> (exclusivo para padres de familia).</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtros de Audiencia</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite especificar la audiencia: por <strong>grado</strong> (padres con hijos en ese grado) o por <strong>salón</strong> (grupo y grado específico). Si no se selecciona ninguno, el mensaje será general.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Mensaje</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Detalle el comunicado o cuerpo del mensaje que recibirán los destinatarios.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Requiere Autorización</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Activa una opción que otorga al padre de familia un permiso explícito para la realización de una actividad.</span>
                </div>
            </div>

            {$img('avisos/formularioAgregarAvisoLLeno.png', 'Formulario completado con los datos del aviso')}

            {$step(4, 'Presione el botón <strong>\"Publicar Aviso\"</strong>. El aviso se visualizará en el tablero.')}

            {$img('avisos/nuevoAvisoPublicado.png', 'Nuevo aviso publicado en el tablero de avisos')}

            <h2 id='buscar'>2. Buscar avisos</h2>
            <p>La plataforma ofrece herramientas de búsqueda y filtrado para localizar avisos rápidamente.</p>

            {$step(1, 'En el apartado de Avisos, ubique los campos de búsqueda en la parte superior del tablero.')}

            {$img('avisos/camposBuscarAvisos.png', 'Campos de búsqueda y filtrado de avisos')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Búsqueda</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite buscar avisos por <strong>título del aviso</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Tipo de Aviso</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre los avisos por tipo: <strong>General</strong>, <strong>Urgente</strong> o <strong>Evento</strong>.</span>
                </div>
            </div>

            {$img('avisos/campoBusquedaAviso.png', 'Resultado al buscar un aviso por título')}
            {$img('avisos/campoBusquedaTiposDeAviso.png', 'Resultado al filtrar avisos por tipo de aviso')}

            <h2 id='editar'>3. Editar avisos</h2>
            <p>Para modificar un aviso existente:</p>

            {$step(1, 'En el tablero de avisos, localice el aviso a editar.')}

            {$img('avisos/avisoParaEditar.png', 'Aviso en el tablero con el ícono de lápiz para editar')}

            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> del registro. Esto abrirá un recuadro con los datos actuales del aviso.')}

            {$img('avisos/modalEdicionAviso.png', 'Modal de edición con los datos actuales del aviso')}

            {$step(3, 'Modifique los campos que necesite. El formulario cuenta con los mismos campos que al crear un aviso:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Campos editables</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Título, Tipo de Aviso, Dirigido a, Filtros de Audiencia, Mensaje y Requiere Autorización.</span>
                </div>
            </div>

            {$important('Si cambia el tipo de aviso a <strong>Evento</strong>, se mostrarán los campos adicionales de Fecha y Hora del evento.')}

            {$img('avisos/camposEspecialesAvisoEventoEdicion.png', 'Campos de evento mostrados durante la edición')}
            {$img('avisos/modalEdicionNuevaInformacion.png', 'Modal de edición con la nueva información ingresada')}

            {$step(4, 'Presione el botón <strong>\"Actualizar Aviso\"</strong>. Los cambios se verán reflejados en el tablero.')}

            {$img('avisos/avisoEditado.png', 'Aviso actualizado correctamente en el tablero')}

            <h2 id='borrar'>4. Borrar avisos</h2>
            <p>Para eliminar un aviso publicado:</p>

            {$step(1, 'En el tablero de avisos, localice el aviso a eliminar.')}

            {$img('avisos/avisoParaBorrar.png', 'Aviso en el tablero con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('avisos/modalBorrarAviso.png', 'Cuadro de confirmación para eliminar el aviso')}

            {$step(3, 'Presione el botón <strong>\"Eliminar\"</strong> para confirmar. El aviso será eliminado del tablero y dejará de ser visible para los usuarios.')}

            {$img('avisos/tableroVacioAvisoBorrado.png', 'Tablero de avisos tras eliminar el registro')}

            <h2 id='firmas'>5. Visualizar firmas de padres</h2>
            <p>El sistema permite verificar qué padres han leído y firmado cada aviso:</p>

            {$step(1, 'En el tablero de avisos, localice el aviso del cual desea revisar las firmas.')}

            {$img('avisos/avisoPorRevisarFirmas.png', 'Aviso en el tablero con el ícono de huella dactilar')}

            {$step(2, 'Presione el ícono de <strong>\"Huella Dactilar\"</strong> del registro. Se abrirá un cuadro con los detalles de las firmas.')}

            {$img('avisos/modalVisualizacionFirmas.png', 'Modal con la información de firmas del aviso')}

            {$step(3, 'El cuadro muestra tres indicadores:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30'>
                    <span class='shrink-0 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider pt-0.5'>Cuadro Verde</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el número de personas que <strong>han firmado</strong> el aviso.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider pt-0.5'>Cuadro Gris</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el número de personas que <strong>recibieron</strong> el aviso y se espera que firmen.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30'>
                    <span class='shrink-0 text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider pt-0.5'>Cuadro Azul</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el <strong>progreso en porcentaje</strong> del número de firmas.</span>
                </div>
            </div>

            {$step(4, 'Debajo de los indicadores encontrará dos secciones:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Firmados</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Muestra el número y nombre de los padres que <strong>ya han firmado</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Pendientes</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Muestra el número y nombre de los padres que están <strong>pendientes de firmar</strong>.</span>
                </div>
            </div>

            {$img('avisos/modalVisualizacionPendientesFirma.png', 'Detalle de padres firmados y pendientes de firma')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use los filtros de audiencia estratégicamente</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Segmente sus avisos por grado o salón para que el mensaje llegue únicamente a los padres relevantes. Esto evita saturar a toda la comunidad con información que no les compete.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Revise las firmas periódicamente</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Use el ícono de huella dactilar para verificar quién ha leído el aviso. Si hay padres pendientes, considere reenviar la información por otro medio.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Active \"Requiere Autorización\" para eventos importantes</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Para salidas, actividades extracurriculares o eventos que requieran consentimiento, active esta opción para obtener un permiso explícito de cada padre.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getCitationsContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Citatorios</strong> permite agendar, gestionar y dar seguimiento a reuniones presenciales con los padres de familia. Los citatorios se generan cuando es necesario tratar un tema académico o disciplinario directamente con el tutor del alumno.</p>

            {$img('citatorios/menu.png', 'Vista general del apartado de Citatorios en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Docente</strong> o <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener alumnos inscritos en el ciclo escolar actual.</li>
                <li>Contar con un motivo justificado (académico o disciplinario) para la cita.</li>
            </ul>

            <h2 id='agregar'>1. Agregar citatorio</h2>
            <p>Para crear y agendar un nuevo citatorio:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Citatorios\"</strong> que se encuentra en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Nuevo Citatorio\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('citatorios/agregarCitatorio.png', 'Botón Nuevo Citatorio para iniciar el proceso')}
            {$img('citatorios/modalAgregarCitatorio.png', 'Formulario vacío para crear un nuevo citatorio')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Buscar Alumno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque al menos <strong>3 caracteres</strong> del nombre del alumno para buscarlo. Selecciónelo haciendo clic en su nombre.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Fecha de la Cita</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la fecha en la que se agendará la cita con el padre de familia.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Hora</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la hora en la que se agendará la cita.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Motivo de la Cita</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Describa el motivo por el cual se otorga el citatorio.</span>
                </div>
            </div>

            {$img('citatorios/modalAgregarCitatorioLLeno.png', 'Formulario completado con los datos del citatorio')}

            {$step(4, 'Presione el botón <strong>\"Generar Citatorio\"</strong>. El citatorio se verá reflejado en la tabla.')}

            {$img('citatorios/citatorioCreado.png', 'Nuevo citatorio registrado en la tabla de citatorios')}

            <h2 id='buscar'>2. Buscar citatorios</h2>
            <p>La plataforma ofrece herramientas de búsqueda y filtrado para localizar citatorios rápidamente.</p>

            {$step(1, 'En el apartado de Citatorios, ubique los campos de búsqueda en la parte superior de la tabla.')}

            {$img('citatorios/camposDeFiltro.png', 'Campos de búsqueda y filtrado de citatorios')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Búsqueda</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite buscar citatorios por <strong>nombre del alumno</strong> o <strong>motivo</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtrar por Estado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre los citatorios por estado: <strong>Todos los estados</strong>, <strong>Pendientes</strong>, <strong>Asistió</strong> o <strong>No asistió</strong>.</span>
                </div>
            </div>

            {$img('citatorios/campoBusquedaAviso.png', 'Resultado al buscar un citatorio por nombre o motivo')}
            {$img('citatorios/campoTipoDeAviso.png', 'Resultado al filtrar citatorios por estado')}

            <h2 id='editar'>3. Editar citatorios</h2>
            <p>Para modificar un citatorio existente:</p>

            {$step(1, 'En la tabla de citatorios, localice el registro a editar.')}

            {$img('citatorios/tableroCitatoriosHaEditar.png', 'Tabla de citatorios con el ícono de lápiz para editar')}

            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> del registro. Esto abrirá un recuadro con los datos actuales del citatorio.')}

            {$img('citatorios/modalEdicionCitatorio.png', 'Modal de edición con los datos actuales del citatorio')}

            {$step(3, 'Modifique los campos que necesite:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Campos editables</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Buscar Alumno, Fecha de la Cita, Hora y Motivo de la Cita.</span>
                </div>
            </div>

            {$img('citatorios/modalEdicionCitatorioLLeno.png', 'Modal de edición con la nueva información ingresada')}

            {$step(4, 'Presione el botón <strong>\"Actualizar Citatorio\"</strong>. Los cambios se verán reflejados en la tabla.')}

            {$img('citatorios/citatorioEditado.png', 'Citatorio actualizado correctamente en la tabla')}

            <h2 id='borrar'>4. Borrar citatorios</h2>
            <p>Para eliminar un citatorio registrado:</p>

            {$step(1, 'En la tabla de citatorios, localice el registro a eliminar.')}

            {$img('citatorios/tableroCitatoriosBorrar.png', 'Tabla de citatorios con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('citatorios/modalBorrarCitatorio.png', 'Cuadro de confirmación para eliminar el citatorio')}

            {$step(3, 'Presione el botón <strong>\"Eliminar\"</strong> para confirmar. El citatorio será eliminado de la tabla.')}

            {$img('citatorios/citatorioBorrado.png', 'Citatorio eliminado correctamente de la tabla')}

            <h2 id='firmar'>5. Firmar citatorios</h2>
            <p>Una vez que la cita se ha llevado a cabo (o no), deberá registrar el resultado de la asistencia:</p>

            {$step(1, 'En la tabla de citatorios, ubique el registro correspondiente.')}

            {$img('citatorios/tableroCitatoriosAFirmar.png', 'Tabla de citatorios con los botones de acción para firmar')}

            {$step(2, 'En la columna de <strong>\"Acciones\"</strong>, visualizará dos botones:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider pt-0.5'>✓ Palomita</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica que el padre <strong>asistió</strong> a la cita. El estado cambiará a <strong>Asistió</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider pt-0.5'>✗ Equis</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica que el padre <strong>no asistió</strong>. El estado cambiará a <strong>No asistió</strong>.</span>
                </div>
            </div>

            {$img('citatorios/citatorioFirmado.png', 'Citatorio firmado con el estado de asistencia actualizado')}

            {$important('Una vez firmado un citatorio, su estado cambiará y se reflejará en los filtros de búsqueda. Esto permite llevar un registro claro de la asistencia de los padres.')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Redacte motivos claros y detallados</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Un motivo bien descrito facilita la disposición del padre al diálogo y le permite prepararse para la reunión. Sea específico sobre el tema a tratar.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Firme los citatorios inmediatamente después de la cita</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Registre la asistencia o inasistencia del padre el mismo día de la cita. Esto mantiene el historial actualizado y permite tomar acciones oportunas.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use los filtros de estado para dar seguimiento</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Filtre por <strong>\"Pendientes\"</strong> para identificar citas próximas y por <strong>\"No asistió\"</strong> para reagendar reuniones con padres que no se presentaron.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getExamsContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Exámenes</strong> permite programar, buscar y eliminar evaluaciones para que padres y alumnos puedan visualizar las fechas de sus exámenes. Los exámenes programados también se reflejan en el calendario general de la plataforma.</p>

            {$img('examenes/menu.png', 'Vista general del apartado de Exámenes en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Docente</strong> o <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener un ciclo escolar activo con grados y grupos configurados.</li>
                <li>Conocer la materia, grado, grupo y trimestre del examen a programar.</li>
            </ul>

            <h2 id='programar'>1. Programar examen</h2>
            <p>Para agendar un nuevo examen:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Exámenes\"</strong> que se encuentra en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Programar Examen\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('examenes/botonAgregarExamen.png', 'Botón Programar Examen para iniciar el proceso')}
            {$img('examenes/modalAgregarExamen.png', 'Formulario vacío para programar un nuevo examen')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Nombre de la Materia</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque el nombre de la materia en la que se asignará el examen.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el grado al que se le aplicará el examen.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grupo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el grupo o sección al que se le aplicará el examen.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Trimestre</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica el trimestre al que corresponde el examen.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Fecha del Examen</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la fecha en la que se aplicará el examen. Se asigna por defecto la fecha actual.</span>
                </div>
            </div>

            {$img('examenes/modalAgregarExamenLLeno.png', 'Formulario completado con los datos del examen')}

            {$step(4, 'Presione el botón <strong>\"Guardar Examen\"</strong>. El examen se verá reflejado en el tablero.')}

            {$img('examenes/examenAgregado.png', 'Nuevo examen programado en el tablero de exámenes')}

            <h2 id='buscar'>2. Buscar exámenes</h2>
            <p>La plataforma ofrece filtros para localizar exámenes programados rápidamente.</p>

            {$step(1, 'En el apartado de Exámenes, ubique los campos de filtrado en la parte superior del tablero.')}

            {$img('examenes/camposFiltrarExamenes.png', 'Campos de filtrado de exámenes')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Trimestre</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre exámenes por trimestre: <strong>Todos los trimestres</strong>, <strong>1° Trimestre</strong>, <strong>2° Trimestre</strong> o <strong>3° Trimestre</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre exámenes por grado. Los grados se generan a partir del ciclo escolar activo.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grupo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre exámenes por grupo o sección. Los grupos se generan a partir del ciclo escolar activo.</span>
                </div>
            </div>

            {$img('examenes/campoFiltradoTrimestre.png', 'Resultado al filtrar exámenes por trimestre')}
            {$img('examenes/campoFiltradoGrado.png', 'Resultado al filtrar exámenes por grado')}
            {$img('examenes/campoFiltradoSeccion.png', 'Resultado al filtrar exámenes por grupo o sección')}

            <h2 id='borrar'>3. Borrar examen</h2>
            <p>Para eliminar un examen programado:</p>

            {$step(1, 'En el tablero de exámenes, localice el examen a eliminar.')}

            {$img('examenes/tableroBorrarExamen.png', 'Tablero de exámenes con el registro a eliminar')}

            {$step(2, 'Pase el cursor por encima del examen y presione el ícono de <strong>\"Basura\"</strong>. Se abrirá un cuadro de confirmación.')}

            {$img('examenes/examenIconoBasura.png', 'Ícono de basura visible al pasar el cursor sobre el examen')}
            {$img('examenes/modalEliminarExamen.png', 'Cuadro de confirmación para eliminar el examen')}

            {$step(3, 'Presione el botón <strong>\"Eliminar\"</strong> para confirmar. El examen será eliminado del tablero.')}

            {$img('examenes/tableroExamenesBorrados.png', 'Tablero de exámenes tras eliminar el registro')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Programe los exámenes con anticipación</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Agende las evaluaciones con suficiente tiempo de antelación para que padres y alumnos puedan prepararse. Los exámenes se reflejan en el calendario general.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use los filtros para organizar por trimestre</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Filtre por trimestre para tener una vista clara de las evaluaciones pendientes en cada periodo. Combine con filtros de grado y grupo para mayor precisión.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Sea específico con el nombre de la materia</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Use nombres claros y consistentes para las materias (ej. \"Matemáticas\", \"Español\") para que padres y alumnos identifiquen fácilmente cada evaluación.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getReportTypesContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Tipos de Reportes</strong> permite al administrador gestionar el catálogo de infracciones disponibles al momento de generar un reporte disciplinario. Cada tipo de reporte cuenta con una descripción y un nivel de gravedad que los docentes utilizarán al registrar faltas.</p>

            {$img('tiposReportes/visualizacionApartado.png', 'Vista general del apartado de Tipos de Reportes')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener un ciclo escolar activo.</li>
                <li>Acceder primero al apartado de <strong>Reportes</strong> en el menú lateral.</li>
            </ul>

            <h2 id='acceder'>1. Acceder al apartado</h2>
            <p>Para llegar al apartado de tipos de reportes:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Reportes\"</strong> en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Gestionar Tipos\"</strong>. Esto lo llevará al apartado de tipos de reportes.')}

            {$img('tiposReportes/botonAccederApartado.png', 'Botón Gestionar Tipos en el apartado de Reportes')}

            <h2 id='agregar'>2. Añadir nuevo tipo de reporte</h2>
            <p>Para crear un nuevo tipo de infracción:</p>

            {$step(1, 'En el apartado de tipos de reportes, presione el botón <strong>\"Nuevo tipo\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('tiposReportes/botonAgregarTipoReporte.png', 'Botón Nuevo tipo para crear una infracción')}
            {$img('tiposReportes/modalAgregarTipoReporte.png', 'Formulario vacío para crear un nuevo tipo de reporte')}

            {$step(2, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Descripción</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque el título o detalles del nuevo tipo de reporte.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Gravedad</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Define qué tan grave es la falta. Cuenta con dos valores: <strong>Normal</strong> y <strong>Grave</strong>.</span>
                </div>
            </div>

            {$img('tiposReportes/modalAgregarTipoReporteLLeno.png', 'Formulario completado con los datos del nuevo tipo de reporte')}

            {$step(3, 'Presione el botón <strong>\"Guardar\"</strong>. El nuevo tipo se verá reflejado en la tabla.')}

            {$img('tiposReportes/tipoReporteAgregado.png', 'Nuevo tipo de reporte registrado en la tabla')}

            <h2 id='editar'>3. Modificar tipo de reporte</h2>
            <p>Para editar un tipo de reporte existente:</p>

            {$step(1, 'En la tabla de tipos de reportes, localice el registro a modificar.')}

            {$img('tiposReportes/tipoReporteAModificar.png', 'Tabla de tipos de reportes con el ícono de lápiz para editar')}

            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> del registro. Esto abrirá un recuadro con los datos actuales.')}

            {$img('tiposReportes/modalEditarReporte.png', 'Modal de edición con los datos actuales del tipo de reporte')}

            {$step(3, 'Modifique los campos que necesite (Descripción y/o Gravedad).')}

            {$img('tiposReportes/modalEditarNuevaInformacion.png', 'Modal de edición con la nueva información ingresada')}

            {$step(4, 'Presione el botón <strong>\"Guardar\"</strong>. Los cambios se verán reflejados en la tabla.')}

            {$img('tiposReportes/tipoReporteEditado.png', 'Tipo de reporte actualizado correctamente en la tabla')}

            {$important('Al modificar la descripción de un tipo de reporte, los reportes existentes que tenían la antigua descripción cambiarán automáticamente al nuevo formato.')}

            <h2 id='borrar'>4. Eliminar tipo de reporte</h2>
            <p>Para eliminar un tipo de reporte del catálogo:</p>

            {$step(1, 'En la tabla de tipos de reportes, localice el registro a eliminar.')}

            {$img('tiposReportes/tipoReporteAEliminar.png', 'Tabla de tipos de reportes con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('tiposReportes/modalEliminar.png', 'Cuadro de confirmación para eliminar el tipo de reporte')}

            {$important('En algunos casos el tipo de reporte tendrá <strong>bloqueada</strong> la opción de eliminar debido a que está vinculado a reportes existentes. Deberá eliminar previamente esos reportes para poder eliminar el tipo.')}

            {$step(3, 'Presione el botón <strong>\"Eliminar\"</strong> para confirmar. El tipo será eliminado de la tabla.')}

            {$img('tiposReportes/tipoReporteEliminado.png', 'Tipo de reporte eliminado correctamente de la tabla')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use descripciones claras y estandarizadas</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Defina las infracciones con títulos específicos para que los docentes las identifiquen fácilmente al momento de generar un reporte. Evite descripciones ambiguas.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Clasifique correctamente la gravedad</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Asigne <strong>\"Normal\"</strong> a faltas menores y <strong>\"Grave\"</strong> a conductas que requieran atención inmediata. Esto ayuda a los docentes a filtrar reportes y a los padres a entender la severidad.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Configure los tipos antes de iniciar el ciclo</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Defina todos los tipos de reporte al inicio del ciclo escolar. Así los docentes tendrán disponible el catálogo completo desde el primer día.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getStudentsContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Alumnos</strong> permite inscribir, buscar, editar, eliminar y vincular padres de familia a los alumnos registrados en el sistema. Es el módulo central para la gestión del alumnado de la institución.</p>

            {$img('alumnos/menu.png', 'Vista general del apartado de Alumnos en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener un ciclo escolar activo con <strong>grados y grupos</strong> creados previamente en el apartado de Ciclos Escolares.</li>
                <li>Para vincular padres, estos deberán estar registrados previamente en el apartado de Gestión de Usuarios.</li>
            </ul>

            <h2 id='inscribir'>1. Inscribir nuevo alumno</h2>
            <p>Para registrar un nuevo alumno en el sistema:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Alumnos\"</strong> en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Inscribir Alumno\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('alumnos/botonAgregarAlumno.png', 'Botón Inscribir Alumno para iniciar el registro')}
            {$img('alumnos/modalAgregarAlumnoVacio.png', 'Formulario vacío para inscribir un nuevo alumno')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Nombre</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque el nombre completo del alumno.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Turno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Por defecto <strong>Matutino</strong>. Puede cambiarse a Vespertino y viceversa.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grupo / Grado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Seleccione el grupo y grado del alumno. Estos deben haber sido creados previamente en Ciclos Escolares.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Dirección</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Dirección domiciliaria del alumno.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Teléfonos de Contacto</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Teléfono de la casa del alumno.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Otro Contacto / Parentesco</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Números de familiares cercanos. Para más de uno, sepárelos con el símbolo <strong>\"/\"</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Padres de Familia</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Al principio no se visualizarán. Primero deberá crear al alumno y después vincularlo con sus padres (ver sección siguiente).</span>
                </div>
            </div>

            {$img('alumnos/modalAgregarAlumnoLLeno.png', 'Formulario completado con los datos del alumno')}

            {$step(4, 'Presione el botón <strong>\"Inscribir Alumno\"</strong> para guardar. El alumno se verá reflejado en la tabla.')}

            <h2 id='vincular'>2. Vincular padres de familia</h2>
            <p>Una vez inscrito el alumno, podrá vincular a sus padres o tutores:</p>

            {$step(1, 'El alumno ya debe estar inscrito y ser visible en la tabla de alumnos.')}

            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> del alumno a vincular. Esto abrirá el recuadro de edición donde el apartado de Padres de Familia estará desbloqueado.')}

            {$img('alumnos/alumnoAVincular.png', 'Alumno en la tabla listo para vincular padres')}
            {$img('alumnos/modalVinculacionPadresVacio.png', 'Sección de Padres de Familia desbloqueada en el modal de edición')}

            {$step(3, 'Coloque el nombre del padre de familia, elija el rol <strong>(Padre o Madre)</strong> y presione el botón <strong>\"Vincular\"</strong>.')}

            {$important('Los nombres de los padres siguen el formato <strong>\"Padre de (nombre del alumno)\"</strong> y <strong>\"Madre de (nombre del alumno)\"</strong>, según el registro en Gestión de Usuarios.')}

            {$img('alumnos/modalVinculacionPadresHecho.png', 'Padres de familia vinculados correctamente al alumno')}

            {$step(4, 'Presione el botón <strong>\"Actualizar\"</strong> para finalizar la vinculación.')}

            <h2 id='buscar'>3. Buscar alumnos</h2>
            <p>La plataforma ofrece herramientas de búsqueda y filtrado para localizar alumnos rápidamente.</p>

            {$step(1, 'En el apartado de Alumnos, ubique el recuadro <strong>\"Filtros\"</strong> en la parte superior.')}

            {$img('alumnos/camposBuscarAlumnos.png', 'Campos de búsqueda y filtrado de alumnos')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Buscar Alumno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite buscar alumnos por <strong>nombre</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre alumnos por su <strong>grado</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Grupo</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Filtre alumnos por su <strong>grupo o sección</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Solo mostrar inscritos en ciclo actual</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Switch que muestra únicamente alumnos inscritos en el ciclo activo. De lo contrario, muestra todos los alumnos registrados.</span>
                </div>
            </div>

            {$img('alumnos/campoBuscarAlumnoNombre.png', 'Resultado al buscar un alumno por nombre')}
            {$img('alumnos/camposBuscarGradoSeccion.png', 'Resultado al filtrar alumnos por grado y grupo')}
            {$img('alumnos/switchMostrarAlumnosCicloActivo.png', 'Switch para mostrar solo alumnos del ciclo actual')}

            <h2 id='editar'>4. Editar alumno</h2>
            <p>Para modificar los datos de un alumno existente:</p>

            {$step(1, 'En la tabla de alumnos, localice el registro a editar.')}

            {$img('alumnos/alumnoAEditar.png', 'Alumno en la tabla con el ícono de lápiz para editar')}

            {$step(2, 'Presione el ícono de <strong>\"Lápiz\"</strong> del registro. Esto abrirá el recuadro de edición con la información actual.')}

            {$img('alumnos/modalEdicionAlumno.png', 'Modal de edición con los datos actuales del alumno')}

            {$step(3, 'Modifique los campos que necesite. Cuenta con los mismos campos que al inscribir: Nombre, Turno, Grupo/Grado, Dirección, Teléfonos, Contactos y Padres de Familia.')}

            {$img('alumnos/modalEdicionAlumnoNuevaInformacion.png', 'Modal de edición con la nueva información ingresada')}

            {$step(4, 'Presione el botón <strong>\"Actualizar Registro\"</strong>. Los cambios se verán reflejados en la tabla.')}

            {$img('alumnos/alumnoEditado.png', 'Alumno actualizado correctamente en la tabla')}

            <h2 id='borrar'>5. Borrar alumno</h2>
            <p>Para eliminar un alumno del sistema:</p>

            {$step(1, 'En la tabla de alumnos, localice el registro a eliminar.')}

            {$img('alumnos/alumnoAEliminar.png', 'Alumno en la tabla con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('alumnos/modalEliminar.png', 'Cuadro de confirmación para eliminar el alumno')}

            {$important('En algunos casos el alumno tendrá <strong>bloqueada</strong> la opción de eliminar debido a que tiene vinculados reportes, servicios, citatorios, entre otros. Deberá eliminar previamente esa información para poder eliminarlo.')}

            {$step(3, 'Presione el botón <strong>\"Eliminar Alumno\"</strong> para confirmar. El alumno será eliminado de la tabla.')}

            {$img('alumnos/alumnoEliminado.png', 'Alumno eliminado correctamente de la tabla')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Vincule padres inmediatamente después de inscribir</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>La vinculación correcta es crucial para que los padres reciban notificaciones de reportes, citatorios y avisos. No deje esta tarea pendiente.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Use el switch de ciclo activo para mantener orden</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Active <strong>\"Solo mostrar inscritos en ciclo actual\"</strong> para trabajar únicamente con alumnos vigentes y evitar confusiones con registros de ciclos anteriores.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Cree los ciclos y grupos antes de inscribir</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Los grados y grupos deben existir en el apartado de Ciclos Escolares antes de poder asignarlos a los alumnos. Planifique la estructura escolar primero.</p>
                    </div>
                </div>
            </div>
        ";
    }

    public function getReportsContent(): string
    {
        $important = fn(string $text) => "
            <div class='not-prose my-6 flex gap-4 p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40'>
                <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg'>!</div>
                <div>
                    <p class='text-sm font-bold text-amber-900 dark:text-amber-100 mb-1'>Importante</p>
                    <p class='text-sm text-amber-800/80 dark:text-amber-300/80'>{$text}</p>
                </div>
            </div>";

        $step = fn(int $num, string $text) => "
            <div class='not-prose my-4 flex gap-4 items-start'>
                <div class='shrink-0 h-8 w-8 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-sm'>{$num}</div>
                <div class='text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed pt-1'>{$text}</div>
            </div>";

        $img = fn(string $src, string $alt) => "
            <div class='not-prose my-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm bg-zinc-50 dark:bg-zinc-800/30'>
                <img src='/images/tutorials/{$src}' alt='{$alt}' class='w-full h-auto' loading='lazy' />
                <div class='px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900'>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400 italic m-0'>{$alt}</p>
                </div>
            </div>";

        return "
            <p id='intro'>El apartado de <strong>Reportes</strong> permite crear, buscar y eliminar reportes disciplinarios asignados a los alumnos. Los reportes documentan las infracciones cometidas y notifican a los padres de familia, quienes deberán firmarlos como acuse de recibo.</p>

            {$img('reportes/menu.png', 'Vista general del apartado de Reportes en el menú lateral')}

            <h2 id='requisitos'>Requisitos previos</h2>
            <ul>
                <li>Contar con permisos de <strong>Docente</strong> o <strong>Administrador</strong> en la plataforma.</li>
                <li>Tener alumnos inscritos en el ciclo escolar actual.</li>
                <li>Contar con <strong>tipos de reportes</strong> configurados previamente (ver tutorial de Tipos de Reportes).</li>
            </ul>

            <h2 id='crear'>1. Crear reporte</h2>
            <p>Para registrar un nuevo reporte disciplinario:</p>

            {$step(1, 'Diríjase al apartado de <strong>\"Reportes\"</strong> en el menú lateral izquierdo.')}

            {$step(2, 'Presione el botón <strong>\"Nuevo Reporte\"</strong>. Esto abrirá un recuadro con campos a llenar.')}

            {$img('reportes/botonCrearReporte.png', 'Botón Nuevo Reporte para iniciar el registro')}
            {$img('reportes/modalCrearReporteVacio.png', 'Formulario vacío para crear un nuevo reporte')}

            {$step(3, 'Complete los campos del formulario:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Buscar Alumno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Coloque al menos <strong>3 caracteres</strong> del nombre del alumno para buscarlo. Selecciónelo haciendo clic en su nombre.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Fecha</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la fecha en la que se aplicó el reporte. Por defecto muestra la fecha actual.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Hora</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Indica la hora en la que se aplicó el reporte. Por defecto muestra la hora actual.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Infracción</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Seleccione el tipo de falta. Los tipos son gestionados en el apartado de <strong>Gestionar Tipos</strong>.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Asunto / Materia</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>opcional</strong>. Detalle el asunto o materia donde se cometió la falta.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Descripción de los Hechos</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Campo <strong>obligatorio</strong>. Describa con detalle el suceso de la infracción.</span>
                </div>
            </div>

            {$important('Al buscar un alumno, asegúrese de <strong>seleccionarlo haciendo clic en su nombre</strong>. De lo contrario, el registro presentará un error.')}

            {$img('reportes/modalCrearReporteLLeno.png', 'Formulario completado con los datos del reporte')}

            {$step(4, 'Presione el botón <strong>\"Guardar Reporte\"</strong>. El reporte se verá reflejado en la tabla.')}

            {$img('reportes/reporteCreado.png', 'Nuevo reporte registrado en la tabla de reportes')}

            <h2 id='buscar'>2. Buscar reportes</h2>
            <p>La plataforma ofrece herramientas de búsqueda y filtrado para localizar reportes rápidamente.</p>

            {$step(1, 'En el apartado de Reportes, ubique el recuadro de filtros en la parte superior.')}

            {$img('reportes/camposFiltrarReportes.png', 'Campos de búsqueda y filtrado de reportes')}

            {$step(2, 'Utilice los filtros disponibles:')}

            <div class='not-prose my-4 ml-12 space-y-3'>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Buscar por Alumno</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Permite visualizar los reportes asignados a un alumno específico.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtrar por Estado</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Muestre reportes por estado: <strong>Todos los Estados</strong>, <strong>Pendientes de Firma</strong> o <strong>Firmado</strong>. Cuando un padre firma un reporte, su estado pasa a Firmado.</span>
                </div>
                <div class='flex gap-3 items-start p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800'>
                    <span class='shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider pt-0.5'>Filtrar por Gravedad</span>
                    <span class='text-sm text-zinc-600 dark:text-zinc-400'>Muestre reportes por gravedad: <strong>Todas las gravedades</strong>, <strong>Normal</strong> o <strong>Grave</strong>. Los niveles se configuran en Gestionar Tipos.</span>
                </div>
            </div>

            {$img('reportes/campoBusquedaPorNombre.png', 'Resultado al buscar reportes por nombre de alumno')}
            {$img('reportes/campoBusquedaEstado.png', 'Resultado al filtrar reportes por estado')}
            {$img('reportes/campoBusquedaGravedad.png', 'Resultado al filtrar reportes por gravedad')}

            <h2 id='borrar'>3. Borrar reporte</h2>
            <p>Para eliminar un reporte disciplinario:</p>

            {$step(1, 'En la tabla de reportes, localice el registro a eliminar.')}

            {$img('reportes/registroABorrar.png', 'Reporte en la tabla con el ícono de basura para eliminar')}

            {$step(2, 'Presione el ícono de <strong>\"Basura\"</strong> del registro. Se abrirá un cuadro de confirmación.')}

            {$img('reportes/modalEliminar.png', 'Cuadro de confirmación para eliminar el reporte')}

            {$step(3, 'Presione el botón <strong>\"Eliminar Reporte\"</strong> para confirmar. El reporte será eliminado de la tabla.')}

            {$img('reportes/registroEliminado.png', 'Reporte eliminado correctamente de la tabla')}

            <h2 id='beneficios'>Tips de experto</h2>
            <div class='not-prose my-4 space-y-4'>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>1</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Sea objetivo y detallado en la descripción</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Describa el evento de forma clara, objetiva y con suficiente contexto. Esto facilita la comunicación con los padres y evita malentendidos.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>2</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Revise los reportes pendientes de firma</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>Use el filtro <strong>\"Pendientes de Firma\"</strong> para identificar reportes que los padres aún no han revisado. Esto le permite dar seguimiento oportuno.</p>
                    </div>
                </div>
                <div class='p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/30 flex gap-4'>
                    <div class='shrink-0 h-10 w-10 flex items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-xl'>3</div>
                    <div>
                        <p class='text-sm font-bold text-indigo-900 dark:text-indigo-100 mb-1'>Seleccione la infracción correcta</p>
                        <p class='text-sm text-indigo-800/80 dark:text-indigo-300/80'>El tipo de infracción determina la gravedad del reporte. Asegúrese de seleccionar la falta que corresponda al evento para mantener la consistencia en los registros.</p>
                    </div>
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
                <x-tutorial-card icon="document-text" title="Ver y Firmar Reportes" description="Consulta el historial disciplinario y firma de enterado." name="tutorial-p-reports" tourRoute="reports.index" />
                <x-tutorial-card icon="megaphone" title="Avisos y Autorizaciones" description="Mantente al día con los eventos y firma permisos digitales." name="tutorial-p-notices" tourRoute="notices.index" />
                <x-tutorial-card icon="calendar-days" title="Atender Citatorios" description="Confirma asistencia a reuniones con el personal docente." name="tutorial-p-citations" tourRoute="citations.index" />
                <x-tutorial-card icon="book-open" title="Calendario de Exámenes" description="Consulta las fechas de evaluación de todos tus hijos." name="tutorial-p-exams" tourRoute="exams.index" />
                <x-tutorial-card icon="heart" title="Servicio Comunitario" description="Seguimiento de actividades reparatorias asignadas." name="tutorial-p-community" tourRoute="community-services.index" />
            </div>
            @endif

            @if($tab === 'teachers')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="pencil-square" title="Gestionar Reportes" description="Crea, busca y elimina reportes disciplinarios de alumnos." name="tutorial-d-create-report" tourRoute="reports.index" />
                <x-tutorial-card icon="clipboard-document-list" title="Programar Evaluaciones" description="Agenda exámenes para que padres y alumnos los visualicen." name="tutorial-d-exams" tourRoute="exams.index" />
                <x-tutorial-card icon="calendar" title="Generar Citatorios" description="Coordina reuniones presenciales con padres de familia." name="tutorial-d-citations" tourRoute="citations.index" />
                <x-tutorial-card icon="megaphone" title="Publicar Avisos" description="Comunícate de forma masiva con padres de tus grupos." name="tutorial-d-notices" tourRoute="notices.index" />
                <x-tutorial-card icon="user-group" title="Asignar Servicio" description="Asigna actividades de servicio comunitario por reportes acumulados." name="tutorial-d-community" tourRoute="community-services.index" />
            </div>
            @endif

            @can('admin-only')
            @if($tab === 'admin')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <x-tutorial-card icon="users" title="Gestión de Usuarios" description="Administra cuentas, restablece contraseñas y permisos." name="tutorial-a-users" tourRoute="users.index" />
                <x-tutorial-card icon="arrow-path" title="Control de Ciclos" description="Configura periodos escolares y activa el ciclo vigente." name="tutorial-a-cycles" tourRoute="cycles.index" />
                <x-tutorial-card icon="document-duplicate" title="Editar Reglamento" description="Actualiza la normativa institucional en tiempo real." name="tutorial-a-regulations" tourRoute="regulations.index" />
                <x-tutorial-card icon="user-plus" title="Gestión de Alumnos" description="Inscribe, busca, edita y vincula padres a tus alumnos." name="tutorial-a-inscribe" tourRoute="students.index" />
                <x-tutorial-card icon="cloud-arrow-down" title="Importación Masiva" description="Carga masiva de datos mediante archivos Excel/CSV." name="tutorial-a-import" tourRoute="data-importer" />
                <x-tutorial-card icon="cloud-arrow-up" title="Exportación de Datos" description="Genera respaldos y listas en formatos exportables." name="tutorial-a-export" tourRoute="data-exporter" />
                <x-tutorial-card icon="arrow-up-circle" title="Promover Alumnos" description="Cambia de grupo o ciclo a los alumnos de forma masiva." name="tutorial-a-promote" tourRoute="students.promote" />
                <x-tutorial-card icon="tag" title="Tipos de Reportes" description="Gestiona los tipos de infracciones y su nivel de gravedad." name="tutorial-a-report-types" tourRoute="infractions.index" />
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
        <div class="animate-in fade-in slide-in-from-left-4 duration-500"
            x-data="{
                activeSection: '',
                init() {
                    const ids = @js(collect($this->getSections($selectedTutorial))->pluck('id')->toArray());
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.activeSection = entry.target.id;
                            }
                        });
                    }, { rootMargin: '-10% 0px -70% 0px' });
                    this.$nextTick(() => {
                        ids.forEach(id => {
                            const el = document.getElementById(id);
                            if (el) observer.observe(el);
                        });
                    });
                    const lastId = ids[ids.length - 1];
                    const self = this;
                    window.addEventListener('scroll', () => {
                        if ((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 50)) {
                            self.activeSection = lastId;
                        }
                    });
                }
            }">
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
                                <a href="#{{ $section['id'] }}"
                                    class="flex items-center justify-between group px-3 py-2 rounded-lg transition-all duration-200"
                                    :class="activeSection === '{{ $section['id'] }}'
                                        ? 'bg-indigo-50 dark:bg-indigo-900/30 border-l-2 border-indigo-500'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 border-l-2 border-transparent'">
                                    <span class="text-sm transition-colors duration-200"
                                        :class="activeSection === '{{ $section['id'] }}'
                                            ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                                            : 'text-zinc-600 dark:text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400'">{{ $section['title'] }}</span>
                                    <flux:icon icon="chevron-right" variant="micro"
                                        class="transition-colors duration-200"
                                        ::class="activeSection === '{{ $section['id'] }}' ? 'text-indigo-500' : 'text-zinc-300 group-hover:text-indigo-600'" />
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
                                <a href="#{{ $section['id'] }}" x-on:click="$dispatch('close-modal', { name: 'mobile-toc' })"
                                    class="flex items-center justify-between p-3 rounded-xl border transition-colors"
                                    :class="activeSection === '{{ $section['id'] }}'
                                        ? 'border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/30'
                                        : 'border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50'">
                                    <span class="text-sm font-medium"
                                        :class="activeSection === '{{ $section['id'] }}' ? 'text-indigo-600 dark:text-indigo-400' : ''">{{ $section['title'] }}</span>
                                    <flux:icon icon="chevron-right" variant="micro"
                                        ::class="activeSection === '{{ $section['id'] }}' ? 'text-indigo-500' : ''" />
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
                    <div class="prose prose-indigo dark:prose-invert max-w-none prose-h2:text-2xl prose-h2:font-black prose-h2:mt-24 prose-h2:mb-10 prose-h2:pt-12 prose-h2:border-t prose-h2:border-zinc-100 dark:prose-h2:border-zinc-800 prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-p:leading-relaxed prose-li:text-zinc-600 dark:prose-li:text-zinc-400">
                        {!! $this->getContent($selectedTutorial) !!}
                    </div>
                </article>
            </div>
        </div>
    @endif

    <style>
        .wrap-break-word { word-break: break-all; }
        aside.sticky { height: calc(100vh - 3rem); }
        article h2 { scroll-margin-top: 5rem; }
        article h2:first-of-type { border-top: none !important; padding-top: 0 !important; margin-top: 2rem !important; }
    </style>
</div>