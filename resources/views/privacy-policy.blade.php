<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Política de Privacidad — SM</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        <style>
            body { font-family: 'Instrument Sans', sans-serif; }
            .glass {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            .dark .glass {
                background: rgba(23, 23, 23, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
        </style>
    </head>
    <body class="antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 min-h-screen selection:bg-blue-500/30">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 glass border-b border-zinc-200 dark:border-white/5 px-6 py-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="size-10 bg-blue-600 rounded-xl flex items-center justify-center font-bold text-xl shadow-lg shadow-blue-500/20 text-white leading-none">SM</div>
                    <span class="text-xl font-bold tracking-tight hidden sm:block">Sistema de Gestión Escolar</span>
                </a>
                <div class="flex items-center gap-6">
                    <a href="{{ url('/') }}" class="text-sm font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">← Volver al inicio</a>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="max-w-4xl mx-auto px-6 py-16 md:py-24">
            <!-- Header -->
            <div class="mb-16 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-600 dark:text-blue-400 text-xs font-bold mb-6 uppercase tracking-widest">
                    Documento Legal
                </div>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-600 dark:from-white dark:via-zinc-200 dark:to-zinc-500 bg-clip-text text-transparent">
                    Política de Privacidad
                </h1>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">
                    Última actualización: {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
                </p>
            </div>

            <!-- Policy Content -->
            <div class="space-y-12">

                {{-- 1. Introducción --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-600 dark:text-blue-400 font-bold text-sm">1</div>
                        <h2 class="text-xl font-bold tracking-tight">Introducción</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p><strong>SM: Sistema de Gestión Escolar</strong> (en adelante, "la Aplicación") es una plataforma educativa diseñada para facilitar la comunicación entre la institución educativa, docentes y padres de familia o tutores respecto al desempeño académico y disciplinario de los alumnos.</p>
                        <p>La presente Política de Privacidad describe cómo recopilamos, usamos, almacenamos y protegemos la información personal de los usuarios, en cumplimiento con la <strong>Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)</strong> de México y las políticas de datos de usuario de Google Play.</p>
                        <p>Al utilizar la Aplicación, usted acepta las prácticas descritas en este documento.</p>
                    </div>
                </section>

                {{-- 2. Responsable del tratamiento --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-600 dark:text-blue-400 font-bold text-sm">2</div>
                        <h2 class="text-xl font-bold tracking-tight">Responsable del Tratamiento de Datos</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>La institución educativa que opera la Aplicación es la responsable del tratamiento de los datos personales. Para ejercer sus derechos ARCO (Acceso, Rectificación, Cancelación y Oposición), puede comunicarse a través de los canales de contacto proporcionados por la institución.</p>
                    </div>
                </section>

                {{-- 3. Datos que recopilamos --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm">3</div>
                        <h2 class="text-xl font-bold tracking-tight">Datos que Recopilamos</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-4">
                        <p>La Aplicación recopila los siguientes tipos de información:</p>

                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-100 dark:border-zinc-700/50">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">a) Datos de los padres de familia / tutores:</h3>
                            <ul class="list-disc list-inside space-y-1 text-zinc-500 dark:text-zinc-400">
                                <li>Nombre completo</li>
                                <li>Correo electrónico</li>
                                <li>Número de teléfono</li>
                                <li>Ocupación</li>
                                <li>Fotografía de perfil (opcional)</li>
                            </ul>
                        </div>

                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-100 dark:border-zinc-700/50">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">b) Datos de los alumnos:</h3>
                            <ul class="list-disc list-inside space-y-1 text-zinc-500 dark:text-zinc-400">
                                <li>Nombre completo</li>
                                <li>CURP (Clave Única de Registro de Población)</li>
                                <li>Fecha de nacimiento</li>
                                <li>Grado y grupo escolar</li>
                                <li>Turno escolar</li>
                                <li>Fotografía escolar</li>
                            </ul>
                        </div>

                        <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800/30">
                            <h3 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">c) Datos sensibles (almacenados con cifrado):</h3>
                            <ul class="list-disc list-inside space-y-1 text-amber-800/80 dark:text-amber-300/80">
                                <li>Dirección del domicilio</li>
                                <li>Teléfono de contacto del alumno</li>
                                <li>Alergias y condiciones médicas</li>
                                <li>Contacto de emergencia</li>
                                <li>Nombres y lugares de trabajo de los padres</li>
                            </ul>
                        </div>

                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-100 dark:border-zinc-700/50">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">d) Datos técnicos del dispositivo:</h3>
                            <ul class="list-disc list-inside space-y-1 text-zinc-500 dark:text-zinc-400">
                                <li>Tokens de notificaciones push (FCM / WebPush)</li>
                                <li>Tipo de navegador y dispositivo (user agent)</li>
                                <li>Claves de cifrado para notificaciones push</li>
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- 4. Finalidad del tratamiento --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-violet-500/10 rounded-xl flex items-center justify-center border border-violet-500/20 text-violet-600 dark:text-violet-400 font-bold text-sm">4</div>
                        <h2 class="text-xl font-bold tracking-tight">Finalidad del Tratamiento</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Los datos personales recopilados se utilizan exclusivamente para las siguientes finalidades:</p>
                        <ul class="list-disc list-inside space-y-2">
                            <li><strong>Gestión escolar:</strong> Registro de alumnos, control de asistencia, administración de grados y grupos.</li>
                            <li><strong>Comunicación:</strong> Envío de avisos generales, citatorios, reportes disciplinarios y notificaciones sobre exámenes a los padres de familia.</li>
                            <li><strong>Reportes académicos y disciplinarios:</strong> Registro y seguimiento de reportes de conducta, servicios comunitarios asignados y citatorios.</li>
                            <li><strong>Notificaciones push:</strong> Alertas en tiempo real sobre eventos relevantes para el alumno a través de Firebase Cloud Messaging (FCM) y Web Push.</li>
                            <li><strong>Identificación:</strong> Uso de la CURP como identificador único para el registro de asistencia mediante escaneo de credenciales.</li>
                            <li><strong>Seguridad:</strong> Autenticación de usuarios, control de acceso basado en roles (administrador, docente, padre de familia).</li>
                        </ul>
                    </div>
                </section>

                {{-- 5. Seguridad de los datos --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm">5</div>
                        <h2 class="text-xl font-bold tracking-tight">Seguridad de los Datos</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Implementamos las siguientes medidas de seguridad para proteger su información:</p>
                        <ul class="list-disc list-inside space-y-2">
                            <li><strong>Cifrado de datos sensibles:</strong> La información médica, direcciones y datos de contacto de emergencia se almacenan cifrados en la base de datos.</li>
                            <li><strong>Contraseñas protegidas:</strong> Las contraseñas de los usuarios se almacenan utilizando algoritmos de hash seguros; nunca se guardan en texto plano.</li>
                            <li><strong>Autenticación segura:</strong> La Aplicación soporta autenticación de dos factores (2FA) para mayor protección de las cuentas.</li>
                            <li><strong>Comunicación cifrada:</strong> Toda la comunicación entre el dispositivo y el servidor se realiza a través de HTTPS/TLS.</li>
                            <li><strong>Control de acceso:</strong> El acceso a la información está restringido por roles (administrador, docente, padre de familia), limitando la visualización de datos según el perfil del usuario.</li>
                        </ul>
                    </div>
                </section>

                {{-- 6. Compartición de datos con terceros --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-600 dark:text-blue-400 font-bold text-sm">6</div>
                        <h2 class="text-xl font-bold tracking-tight">Compartición de Datos con Terceros</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p><strong>No vendemos, comercializamos ni transferimos</strong> su información personal a terceros con fines comerciales o publicitarios.</p>
                        <p>Los datos pueden compartirse únicamente en los siguientes casos:</p>
                        <ul class="list-disc list-inside space-y-2">
                            <li><strong>Firebase Cloud Messaging (Google):</strong> Los tokens de dispositivo se utilizan exclusivamente para el envío de notificaciones push. Google procesa estos datos conforme a su propia <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 underline hover:no-underline">política de privacidad</a>.</li>
                            <li><strong>Requerimiento legal:</strong> Cuando sea exigido por alguna autoridad competente conforme a la legislación aplicable.</li>
                        </ul>
                    </div>
                </section>

                {{-- 7. Retención de datos --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-violet-500/10 rounded-xl flex items-center justify-center border border-violet-500/20 text-violet-600 dark:text-violet-400 font-bold text-sm">7</div>
                        <h2 class="text-xl font-bold tracking-tight">Retención de Datos</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Los datos personales se conservan durante el tiempo que el alumno permanezca inscrito en la institución educativa y durante el periodo que la normativa escolar vigente lo requiera.</p>
                        <p>Los tokens de notificaciones push se eliminan automáticamente cuando el dispositivo ya no es válido o cuando el usuario revoca el permiso de notificaciones.</p>
                        <p>Las cuentas de usuario inactivas podrán ser desactivadas por el administrador de la institución al término del ciclo escolar.</p>
                    </div>
                </section>

                {{-- 8. Derechos ARCO --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm">8</div>
                        <h2 class="text-xl font-bold tracking-tight">Derechos del Usuario (ARCO)</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Conforme a la LFPDPPP, usted tiene derecho a:</p>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-700/50">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs uppercase tracking-wide mb-1">Acceso</p>
                                <p class="text-xs">Conocer qué datos personales tenemos sobre usted y su uso.</p>
                            </div>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-700/50">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs uppercase tracking-wide mb-1">Rectificación</p>
                                <p class="text-xs">Solicitar la corrección de datos inexactos o incompletos.</p>
                            </div>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-700/50">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs uppercase tracking-wide mb-1">Cancelación</p>
                                <p class="text-xs">Solicitar la eliminación de sus datos cuando ya no sean necesarios.</p>
                            </div>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-700/50">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs uppercase tracking-wide mb-1">Oposición</p>
                                <p class="text-xs">Oponerse al tratamiento de sus datos para fines específicos.</p>
                            </div>
                        </div>
                        <p>Para ejercer estos derechos, contacte directamente a la administración de la institución educativa.</p>
                    </div>
                </section>

                {{-- 9. Menores de edad --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-amber-500/10 rounded-xl flex items-center justify-center border border-amber-500/20 text-amber-600 dark:text-amber-400 font-bold text-sm">9</div>
                        <h2 class="text-xl font-bold tracking-tight">Datos de Menores de Edad</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>La Aplicación gestiona información de alumnos que pueden ser menores de edad. Los datos de los alumnos son proporcionados y autorizados por la institución educativa y los padres de familia o tutores legales al momento de la inscripción.</p>
                        <p>Los menores de edad <strong>no acceden directamente</strong> a la Aplicación; el acceso está reservado exclusivamente para personal administrativo, docentes y padres de familia o tutores.</p>
                    </div>
                </section>

                {{-- 10. Notificaciones push --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-600 dark:text-blue-400 font-bold text-sm">10</div>
                        <h2 class="text-xl font-bold tracking-tight">Notificaciones Push</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>La Aplicación utiliza notificaciones push para informar a los padres de familia sobre:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Avisos generales de la institución</li>
                            <li>Citatorios escolares</li>
                            <li>Reportes disciplinarios</li>
                            <li>Exámenes programados</li>
                            <li>Servicios comunitarios asignados</li>
                        </ul>
                        <p>Para este servicio utilizamos <strong>Firebase Cloud Messaging (FCM)</strong> de Google y <strong>Web Push</strong> (protocolo estándar con claves VAPID). El usuario puede desactivar las notificaciones en cualquier momento desde la configuración de su dispositivo o navegador.</p>
                    </div>
                </section>

                {{-- 11. Cambios a la política --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-violet-500/10 rounded-xl flex items-center justify-center border border-violet-500/20 text-violet-600 dark:text-violet-400 font-bold text-sm">11</div>
                        <h2 class="text-xl font-bold tracking-tight">Cambios a esta Política</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Nos reservamos el derecho de actualizar esta Política de Privacidad en cualquier momento. Cualquier modificación será publicada en esta misma página con la fecha de actualización correspondiente.</p>
                        <p>Le recomendamos revisar periódicamente esta página para mantenerse informado sobre cómo protegemos su información.</p>
                    </div>
                </section>

                {{-- 12. Contacto --}}
                <section class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm">12</div>
                        <h2 class="text-xl font-bold tracking-tight">Contacto</h2>
                    </div>
                    <div class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed space-y-3">
                        <p>Si tiene preguntas, comentarios o solicitudes relacionadas con esta Política de Privacidad o el tratamiento de sus datos personales, puede comunicarse con la administración de la institución educativa a través de los canales de contacto oficiales proporcionados al momento de su registro.</p>
                    </div>
                </section>

            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto px-6 py-16 border-t border-zinc-200 dark:border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">
                © {{ date('Y') }} SM: Sistema de Gestión Escolar.
            </div>
            <div class="flex gap-10 text-zinc-500 dark:text-zinc-400 text-xs font-bold uppercase tracking-widest">
                <a href="{{ url('/') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Inicio</a>
                <a href="{{ route('privacy-policy') }}" class="text-zinc-900 dark:text-white">Política de Privacidad</a>
            </div>
        </footer>
    </body>
</html>
