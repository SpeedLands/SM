/**
 * tours.js — Definición de los pasos por ruta y rol para driver.js
 *
 * Cada clave es el nombre de la ruta (data-tour-route del <body>).
 * Los pasos están organizados por rol: admin, teacher, parent.
 * Los selectores usan data-tour="..." que se agregan en los Blade correspondientes.
 *
 * Para rutas compartidas (ej: reports.index vista staff y vista padre), se detecta
 * el atributo data-tour-view-parent para elegir el set de pasos correcto.
 */

const isMobile = () => window.innerWidth < 1024;

/**
 * Retorna los pasos de tour para la ruta y rol actuales.
 * @param {string} route - Nombre de la ruta actual
 * @param {string} role  - Rol del usuario: 'admin', 'teacher', 'parent'
 * @param {string} viewParent - 'true' si está en vista padre (staff con hijos)
 * @returns {Array|null} Pasos del tour o null si no hay tour para esta ruta/rol
 */
export function getTourSteps(route, role, viewParent) {
    const isParentView = viewParent === 'true' || role === 'parent';

    const allTours = {
        // ─────────────── DASHBOARD ───────────────
        'dashboard': {
            staff: [
                {
                    element: '[data-tour="dashboard-heading"]',
                    popover: {
                        title: '📊 Tablero Principal',
                        description: 'Aquí encontrarás el resumen general de la plataforma: reportes recientes, citatorios pendientes y estadísticas del ciclo activo.',
                    },
                },
                {
                    element: '[data-tour="dashboard-stats"]',
                    popover: {
                        title: '📈 Estadísticas del Ciclo',
                        description: 'Estos números se actualizan automáticamente con cada registro nuevo. Refleja el estado actual del ciclo escolar activo.',
                    },
                },
                {
                    element: '[data-tour="dashboard-recent-reports"]',
                    popover: {
                        title: '📋 Reportes Recientes',
                        description: 'Los últimos 5 reportes disciplinarios registrados. Haz clic en "Ver todos" para ir a la sección completa.',
                    },
                },
                {
                    element: '[data-tour="dashboard-citations"]',
                    popover: {
                        title: '📅 Próximos Citatorios',
                        description: 'Citatorios pendientes ordenados por fecha. Asegúrate de atenderlos antes de su vencimiento.',
                    },
                },
                {
                    element: '[data-tour="nav-tutorials"]',
                    popover: {
                        title: '📚 Sección de Tutoriales',
                        description: '¿Tienes dudas sobre alguna función? Visita la sección de Tutoriales para ver guías detalladas de cada módulo.',
                        side: isMobile() ? 'bottom' : 'right',
                        align: 'start',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="dashboard-heading"]',
                    popover: {
                        title: '🏠 Tu Tablero Familiar',
                        description: 'Aquí verás el resumen de la situación académica de tus hijos: reportes, citatorios y avisos recientes.',
                    },
                },
                {
                    element: '[data-tour="dashboard-students"]',
                    popover: {
                        title: '👦 Tus Hijos Vinculados',
                        description: 'Cada tarjeta muestra el resumen de un hijo. Los puntos rojos indican trámites pendientes de tu atención.',
                    },
                },
                {
                    element: '[data-tour="dashboard-parent-citations"]',
                    popover: {
                        title: '📅 Citatorios Próximos',
                        description: 'Si un maestro te cita a reunión, aparecerá aquí. Responde a tiempo para no perder la cita.',
                    },
                },
                {
                    element: '[data-tour="nav-tutorials"]',
                    popover: {
                        title: '📚 Tutoriales de Uso',
                        description: 'Consulta los tutoriales si necesitas ayuda para usar cualquier sección de la plataforma.',
                        side: isMobile() ? 'bottom' : 'right',
                        align: 'start',
                    },
                },
            ],
        },

        // ─────────────── REPORTES ───────────────
        'reports.index': {
            staff: [
                {
                    element: '[data-tour="reports-heading"]',
                    popover: {
                        title: '📋 Reportes Disciplinarios',
                        description: 'Lleva el registro de conducta de los alumnos. Los reportes notifican automáticamente a los padres vía notificación push.',
                    },
                },
                {
                    element: '[data-tour="reports-create-btn"]',
                    popover: {
                        title: '➕ Crear Nuevo Reporte',
                        description: 'Al hacer clic aquí se abre el formulario para registrar una incidencia. Selecciona el alumno, el tipo de falta y describe lo ocurrido.',
                    },
                },
                {
                    element: '[data-tour="reports-filters"]',
                    popover: {
                        title: '🔍 Filtros de Búsqueda',
                        description: 'Filtra por alumno, estado (pendiente/firmado) o gravedad. En móvil, toca el ícono de embudo para ver los filtros.',
                    },
                },
                {
                    element: '[data-tour="reports-table-desktop"]',
                    popover: {
                        title: '📊 Tabla de Reportes',
                        description: 'Cada fila muestra un reporte. Con los botones de acción puedes editar, eliminar o generar un citatorio directamente desde aquí.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="reports-heading"]',
                    popover: {
                        title: '📋 Reportes de tus Hijos',
                        description: 'Aquí verás las incidencias disciplinarias registradas. Los reportes en color ámbar requieren tu firma de enterado.',
                    },
                },
                {
                    element: '[data-tour="reports-filters"]',
                    popover: {
                        title: '🔍 Filtrar Reportes',
                        description: 'Usa el filtro "Solo pendientes de firma" para localizar rápidamente los reportes que necesitas atender.',
                    },
                },
                {
                    element: '[data-tour="reports-sign-btn"]',
                    popover: {
                        title: '✍️ Firmar Reporte',
                        description: 'Al presionar "Firmar Reporte" confirmas que estás enterado de la incidencia. Esto tiene validez institucional.',
                    },
                },
            ],
        },

        // ─────────────── ALUMNOS ───────────────
        'students.index': {
            staff: [
                {
                    element: '[data-tour="students-heading"]',
                    popover: {
                        title: '👥 Gestión de Alumnos',
                        description: 'Aquí puedes inscribir nuevos alumnos, buscar por nombre o grado, editar su información y vincularlos con padres de familia.',
                    },
                },
                {
                    element: '[data-tour="students-create-btn"]',
                    popover: {
                        title: '➕ Inscribir Alumno',
                        description: 'Registra un nuevo alumno con su nombre, grado y grupo. Después podrás vincularle un padre o tutor desde la misma interfaz.',
                    },
                },
                {
                    element: '[data-tour="students-search"]',
                    popover: {
                        title: '🔍 Búsqueda de Alumnos',
                        description: 'Busca por nombre o filtra por grado y grupo. El resultado se actualiza en tiempo real sin recargar la página.',
                    },
                    onHighlightStarted: (element) => {
                        // Forzar apertura de filtros en móvil si el elemento está oculto
                        const panel = document.getElementById('students-filter-panel');
                        if (panel && (window.getComputedStyle(panel).display === 'none' || panel.classList.contains('hidden'))) {
                            panel.classList.remove('hidden');
                        }
                    }
                },
                {
                    element: () => {
                        const mobile = document.querySelector('[data-tour="students-table-mobile"]');
                        if (mobile && window.getComputedStyle(mobile).display !== 'none') return mobile;
                        return document.querySelector('[data-tour="students-table-desktop"]');
                    },
                    popover: {
                        title: '📊 Tabla de Alumnos',
                        description: 'Aquí se listan los estudiantes. Si haces clic en una fila (en vista de escritorio), aparecerá un menú rápido para generar reportes, servicios o citatorios.',
                    },
                },
                {
                    element: () => {
                        const mobile = document.querySelector('[data-tour="students-actions-mobile"]');
                        if (mobile && window.getComputedStyle(mobile).display !== 'none') return mobile;
                        return document.querySelector('[data-tour="students-actions-desktop"]');
                    },
                    popover: {
                        title: '⚙️ Acciones Individuales',
                        description: 'Con estos botones puedes generar selecciones para imprimir credenciales (si eres administrador), ver el historial, editar sus datos o eliminar al alumno.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="students-heading"]',
                    popover: {
                        title: '👦 Ficha de Alumnos',
                        description: 'Aquí puedes ver la información de tus hijos vinculados: grado, grupo y sus datos generales.',
                    },
                },
            ],
        },

        // ─────────────── USUARIOS ───────────────
        'users.index': {
            admin: [
                {
                    element: '[data-tour="users-heading"]',
                    popover: {
                        title: '👤 Gestión de Usuarios',
                        description: 'Administra todas las cuentas de la plataforma: maestros, administrativos y padres de familia.',
                    },
                },
                {
                    element: '[data-tour="users-create-btn"]',
                    popover: {
                        title: '➕ Añadir Usuario',
                        description: 'Crea una nueva cuenta. El correo debe seguir el formato de número de teléfono con @escuela.edu.mx. Define el rol para controlar el acceso.',
                    },
                },
                {
                    element: '[data-tour="users-search"]',
                    popover: {
                        title: '🔍 Buscar y Filtrar',
                        description: 'Busca por nombre o correo electrónico. Filtra por rol (Maestro, Administrativo, Padre) para gestionar grupos de usuarios fácilmente.',
                    },
                },
                {
                    element: '[data-tour="users-table"]',
                    popover: {
                        title: '🔒 Acciones sobre Usuarios',
                        description: 'Con los íconos de cada fila puedes editar un usuario o bloquearlo. Bloquear es preferible a eliminar porque preserva el historial.',
                    },
                },
            ],
        },

        // ─────────────── CICLOS ESCOLARES ───────────────
        'cycles.index': {
            admin: [
                {
                    element: '[data-tour="cycles-heading"]',
                    popover: {
                        title: '🎓 Ciclos Escolares',
                        description: 'Configura los periodos académicos. El ciclo activo es el que se usa en todos los módulos de la plataforma (reportes, citatorios, etc.).',
                    },
                },
                {
                    element: '[data-tour="cycles-create"]',
                    popover: {
                        title: '➕ Crear Ciclo',
                        description: 'Define los años del nuevo ciclo (ej: 2024-2025). Solo puede haber un ciclo activo a la vez.',
                    },
                },
                {
                    element: '[data-tour="cycles-table"]',
                    popover: {
                        title: '⚡ Activar / Desactivar',
                        description: 'El interruptor cambia al ciclo activo. Al activar uno, el anterior se desactiva automáticamente. Los grupos (salones) se gestionan desde aquí también.',
                    },
                },
            ],
        },

        // ─────────────── AVISOS ───────────────
        'notices.index': {
            staff: [
                {
                    element: '[data-tour="notices-heading"]',
                    popover: {
                        title: '📢 Avisos y Comunicados',
                        description: 'Envía comunicados masivos a padres de familia. Los avisos pueden requerir autorización (ej: viajes escolares).',
                    },
                },
                {
                    element: '[data-tour="notices-create-btn"]',
                    popover: {
                        title: '➕ Nuevo Aviso',
                        description: 'Crea un aviso, selecciona los grupos destino y redacta el mensaje. Los padres recibirán una notificación push automáticamente.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="notices-heading"]',
                    popover: {
                        title: '📢 Avisos Escolares',
                        description: 'Aquí verás todos los comunicados enviados por maestros y dirección. Los que requieren tu respuesta estarán marcados.',
                    },
                },
            ],
        },

        // ─────────────── CITATORIOS ───────────────
        'citations.index': {
            staff: [
                {
                    element: '[data-tour="citations-heading"]',
                    popover: {
                        title: '📅 Gestión de Citatorios',
                        description: 'Convoca a padres de familia a reuniones presenciales. El padre recibe la notificación y puede confirmar desde la app.',
                    },
                },
                {
                    element: '[data-tour="citations-create-btn"]',
                    popover: {
                        title: '➕ Nuevo Citatorio',
                        description: 'Define la fecha, hora, lugar y motivo de la reunión. Un citatorio bien redactado facilita la disposición del padre al diálogo.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="citations-heading"]',
                    popover: {
                        title: '📅 Citatorios Recibidos',
                        description: 'Cuando un maestro te cita a reunión aparecerá aquí. Confirma tu asistencia para que el maestro sepa que estás enterado.',
                    },
                },
            ],
        },

        // ─────────────── EXÁMENES ───────────────
        'exams.index': {
            staff: [
                {
                    element: '[data-tour="exams-heading"]',
                    popover: {
                        title: '📝 Programar Exámenes',
                        description: 'Registra las fechas de evaluación para tus grupos. Los padres podrán consultarlas desde la app para organizar a sus hijos.',
                    },
                },
                {
                    element: '[data-tour="exams-create-btn"]',
                    popover: {
                        title: '➕ Nuevo Examen',
                        description: 'Selecciona la materia, grupo y fecha. Al guardar, los padres del grupo serán notificados automáticamente.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="exams-heading"]',
                    popover: {
                        title: '📝 Calendario de Exámenes',
                        description: 'Consulta las fechas de evaluación de tus hijos. Filtra por trimestre para ver las próximas. Planifica el estudio con anticipación.',
                    },
                },
            ],
        },

        // ─────────────── SERVICIO COMUNITARIO ───────────────
        'community-services.index': {
            staff: [
                {
                    element: '[data-tour="community-services-heading"]',
                    popover: {
                        title: '🤝 Servicio Comunitario',
                        description: 'Cuando un alumno acumula 3 reportes, se activa automáticamente la sugerencia de servicio comunitario. Aquí los gestionas.',
                    },
                },
                {
                    element: '[data-tour="community-services-create-btn"]',
                    popover: {
                        title: '➕ Asignar Servicio',
                        description: 'Define la actividad reparatoria y la fecha límite. El padre recibirá notificación y deberá firmar de enterado.',
                    },
                },
            ],
            parent: [
                {
                    element: '[data-tour="community-services-heading"]',
                    popover: {
                        title: '🤝 Servicio Comunitario',
                        description: 'Aparece cuando tu hijo acumula 3 o más reportes. El servicio busca la reflexión y reparación del daño académico.',
                    },
                },
            ],
        },

        // ─────────────── ASISTENCIA ───────────────
        'attendance.index': {
            staff: [
                {
                    element: '[data-tour="attendance-heading"]',
                    popover: {
                        title: '🕐 Control de Asistencia',
                        description: 'Registra la asistencia diaria de tus grupos. Selecciona el grupo, la fecha y marca la asistencia de cada alumno.',
                    },
                },
                {
                    element: '[data-tour="attendance-group-select"]',
                    popover: {
                        title: '👥 Seleccionar Grupo',
                        description: 'Elige el grupo del que quieres registrar asistencia. La lista de alumnos se cargará automáticamente.',
                    },
                },
            ],
        },

        // ─────────────── TIPOS DE REPORTE (INFRACCIONES) ───────────────
        'infractions.index': {
            admin: [
                {
                    element: '[data-tour="infractions-heading"]',
                    popover: {
                        title: '⚙️ Tipos de Reporte',
                        description: 'Define los tipos de faltas que pueden registrar los maestros. Cada tipo tiene una gravedad (Normal o Grave) que determina su impacto.',
                    },
                },
                {
                    element: '[data-tour="infractions-create-btn"]',
                    popover: {
                        title: '➕ Nuevo Tipo',
                        description: 'Agrega una descripción de la falta y su nivel de gravedad. Estos tipos aparecen en el formulario de reportes.',
                    },
                },
            ],
        },
        // ─────────────── REGLAMENTO ───────────────
        'regulations.index': {
            admin: [
                {
                    element: '[data-tour="regulations-heading"]',
                    popover: {
                        title: '📜 Reglamento Institucional',
                        description: 'Aquí puedes actualizar el marco normativo de la escuela. Los cambios se reflejan inmediatamente para todos los usuarios.',
                    },
                },
            ],
        },

        // ─────────────── PROMOVER ALUMNOS ───────────────
        'students.promote': {
            admin: [
                {
                    element: '[data-tour="promote-heading"]',
                    popover: {
                        title: '🚀 Promoción de Alumnos',
                        description: 'Utiliza esta herramienta al finalizar el ciclo escolar para pasar a los alumnos al siguiente grado de forma masiva.',
                    },
                },
            ],
        },

        // ─────────────── IMPORTADOR / EXPORTADOR ───────────────
        'data-importer': {
            admin: [
                {
                    element: '[data-tour="importer-heading"]',
                    popover: {
                        title: '📥 Importación Masiva',
                        description: 'Carga archivos Excel para registrar cientos de alumnos o usuarios en segundos. Usa las plantillas sugeridas.',
                    },
                },
            ],
        },
        'data-exporter': {
            admin: [
                {
                    element: '[data-tour="exporter-heading"]',
                    popover: {
                        title: '📤 Exportación de Datos',
                        description: 'Genera respaldos de tu información en formato CSV para auditorías o reportes externos.',
                    },
                },
            ],
        },
    };

    const tourData = allTours[route];
    if (!tourData) { return null; }

    // Elegir entre staff y parent
    if (isParentView) {
        return tourData.parent || null;
    }

    if (role === 'admin') {
        return tourData.admin || tourData.staff || null;
    }

    return tourData.staff || null;
}

/**
 * Mapeo de ruta → URL para los botones "Guía Interactiva" en la página de tutoriales.
 * Cada tutorial tiene su ruta destino correspondiente.
 */
export const tutorialRouteMap = {
    'tutorial-p-reports': { route: '/reportes', label: 'Ver mis reportes con guía' },
    'tutorial-p-notices': { route: '/avisos', label: 'Ver avisos con guía' },
    'tutorial-p-citations': { route: '/citatorios', label: 'Ver citatorios con guía' },
    'tutorial-p-exams': { route: '/examenes', label: 'Ver exámenes con guía' },
    'tutorial-p-community': { route: '/servicio-comunitario', label: 'Ver servicio comunitario con guía' },
    'tutorial-d-create-report': { route: '/reportes', label: 'Ir a Reportes con guía' },
    'tutorial-d-exams': { route: '/examenes', label: 'Ir a Exámenes con guía' },
    'tutorial-d-citations': { route: '/citatorios', label: 'Ir a Citatorios con guía' },
    'tutorial-d-notices': { route: '/avisos', label: 'Ir a Avisos con guía' },
    'tutorial-d-community': { route: '/servicio-comunitario', label: 'Ir a Servicio Comunitario con guía' },
    'tutorial-a-users': { route: '/users', label: 'Ir a Usuarios con guía' },
    'tutorial-a-cycles': { route: '/cycles', label: 'Ir a Ciclos con guía' },
    'tutorial-a-regulations': { route: '/reglamento', label: 'Ver reglamento con guía' },
    'tutorial-a-inscribe': { route: '/alumnos', label: 'Ir a Alumnos con guía' },
    'tutorial-a-import': { route: '/importar-datos', label: 'Ir a Importar datos con guía' },
    'tutorial-a-export': { route: '/exportar-datos', label: 'Ir a Exportar datos con guía' },
    'tutorial-a-promote': { route: '/alumnos/promover', label: 'Ir a Promover alumnos con guía' },
    'tutorial-a-report-types': { route: '/reportes/tipos', label: 'Ir a Tipos de Reporte con guía' },
};
