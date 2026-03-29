<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public ?string $fcmToken = '';
    public bool $hasToken = false;

    public function mount(): void
    {
        $this->fcmToken = Auth::user()->fcm_token;
        $this->hasToken = !empty($this->fcmToken);
    }

    public function clearToken(): void
    {
        $user = Auth::user();
        $user->update(['fcm_token' => null]);
        $this->fcmToken = null;
        $this->hasToken = false;
        
        $this->dispatch('notify', [
            'message' => 'Notificaciones desactivadas en este servidor.',
            'variant' => 'success'
        ]);
    }

    public function testNotification(): void
    {
        $this->fcmToken = Auth::user()->fcm_token;
        
        if (!$this->fcmToken) {
            $this->dispatch('fcm-backend-test-result', [
                'success' => false,
                'error' => 'No hay un token registrado para enviar la prueba. Registra el dispositivo primero.'
            ]);
            return;
        }

        $fcmService = app(\App\Services\FcmService::class);
        $result = $fcmService->sendTestNotification($this->fcmToken);

        if ($result['success']) {
            $this->dispatch('fcm-backend-test-result', [
                'success' => true,
                'message' => 'Notificación enviada exitosamente. Debería llegar pronto al dispositivo.'
            ]);
        } else {
            $status = $result['status'] ?? 'UNKNOWN';
            $message = $result['message'] ?? 'Error desconocido';
            
            // Map common errors based on user list
            $explanations = [
                'INVALID_ARGUMENT' => 'Los parámetros de la solicitud no eran válidos (400). Verifica el token, formato del mensaje, o que no tenga datos restringidos.',
                'UNREGISTERED' => 'El token utilizado ya no es válido o la app se desinstaló (404). Borra el vínculo actual y genera un token nuevo.',
                'SENDER_ID_MISMATCH' => 'El ID de remitente autenticado no coincide con el del token de registro (403).',
                'QUOTA_EXCEEDED' => 'Se superó el límite de envío para el destino (429). Demasiados mensajes en poco tiempo.',
                'UNAVAILABLE' => 'El servidor de Firebase está sobrecargado o no disponible (503).',
                'INTERNAL' => 'Se produjo un error interno desconocido en Firebase (500).',
                'THIRD_PARTY_AUTH_ERROR' => 'El certificado APNs en iOS o la clave de autorización de notificaciones push web no son válidos o faltan (401). Verifica Apple Developer y Firebase Console.',
                'UNAUTHENTICATED' => 'Fallo al autenticar contra Firebase, revisar credenciales de service_account (401).',
            ];

            // If there's an errorCode in the details array, we can check it
            $detailErrorCode = null;
            if (!empty($result['details'])) {
                foreach ($result['details'] as $detail) {
                    if (isset($detail['errorCode'])) {
                        $detailErrorCode = $detail['errorCode'];
                        if ($detailErrorCode === 'THIRD_PARTY_AUTH_ERROR') {
                            $status = 'THIRD_PARTY_AUTH_ERROR'; // Override status if this shows up in details
                        }
                    }
                }
            }

            // Check if backend message contains SDK Admin hints
            $sdkAdminHints = [
                'messaging/invalid-apns-credentials' => 'Certificado APNs o clave de autenticación P8 de Apple ha vencido o no es válido.',
                'messaging/registration-token-not-registered' => 'El token ha expirado o no está registrado en este proyecto.',
            ];

            $explanation = $explanations[$status] ?? 'Se produjo un error desconocido o específico del SDK.';
            
            foreach ($sdkAdminHints as $key => $hint) {
                if (str_contains($message, $key)) {
                    $explanation .= " | " . $hint;
                }
            }
            
            $formattedError = "[{$status} - {$result['code']}] {$explanation}\nMensaje original: {$message}";

            if (!empty($result['details'])) {
                $formattedError .= "\nDetalles técnicos: " . json_encode($result['details']);
            }

            $this->dispatch('fcm-backend-test-result', [
                'success' => false,
                'error' => $formattedError
            ]);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Gestione cómo recibe las alertas y notificaciones push.')">
        <div class="space-y-6" x-data="{ 
            generatedToken: '', 
            lastError: '',
            testResultError: '',
            isGenerating: false,
            isTesting: false,
            copy(text) {
                if (!text) return;
                navigator.clipboard.writeText(text);
                $dispatch('notify', { message: 'Copiado al portapapeles', variant: 'success' });
            }
        }"
        @fcm-token-received.window="generatedToken = $event.detail.token; lastError = ''; isGenerating = false;"
        @fcm-error-occurred.window="lastError = $event.detail.error; generatedToken = ''; isGenerating = false;"
        @fcm-backend-test-result.window="isTesting = false; if ($event.detail.success) { testResultError = ''; $dispatch('notify', {message: $event.detail.message, variant: 'success'}); } else { testResultError = $event.detail.error; }"
        >
            <div x-data="{ 
                permission: 'Notification' in window ? Notification.permission : 'denied',
                requestPermission() {
                    if ('Notification' in window) {
                        Notification.requestPermission().then(p => {
                            this.permission = p;
                            if (p === 'granted') {
                                // Trigger the global updateFcmToken function
                                window.requestPushPermission();
                                // Refresh component to check for token
                                $wire.$refresh();
                            }
                        });
                    }
                }
            }" class="space-y-4">
                
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                                <flux:icon name="bell-alert" class="text-zinc-500" />
                            </div>
                            <div>
                                <flux:heading size="sm">Notificaciones de Navegador</flux:heading>
                                <flux:text size="xs">Estado actual: 
                                    <span :class="{
                                        'text-green-600 font-bold': permission === 'granted',
                                        'text-amber-600 font-bold': permission === 'default',
                                        'text-red-600 font-bold': permission === 'denied'
                                    }" x-text="permission === 'granted' ? 'Activado' : (permission === 'default' ? 'Pendiente' : 'Bloqueado')"></span>
                                </flux:text>
                            </div>
                        </div>

                        <template x-if="permission !== 'granted'">
                            <flux:button variant="primary" size="sm" @click="requestPermission()">Activar</flux:button>
                        </template>
                        
                        <template x-if="permission === 'granted'">
                            <div class="flex items-center gap-2">
                                <flux:badge color="green" size="sm">Recibiendo</flux:badge>
                                <flux:button variant="ghost" size="xs" @click="isGenerating = true; window.requestPushPermission()" icon="arrow-path" title="Forzar actualización de token"></flux:button>
                            </div>
                        </template>
                    </div>

                    <template x-if="permission === 'denied'">
                        <flux:text size="xs" class="mt-2 text-red-500 flex items-center gap-2">
                            <flux:icon name="exclamation-circle" variant="micro" />
                            Parece que has bloqueado las notificaciones. Debes habilitarlas en la configuración de tu navegador para este sitio.
                        </flux:text>
                    </template>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Dispositivos Vinculados</flux:heading>
                        @if($hasToken)
                            <flux:badge size="xs" color="green" inset="left">Sincronizado</flux:badge>
                        @endif
                    </div>
                    <flux:text size="xs">Aquí puedes ver si este navegador está registrado para recibir notificaciones.</flux:text>
                    
                    @if($hasToken)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-xs">
                            <div class="flex items-center gap-2">
                                <flux:icon name="computer-desktop" variant="micro" class="text-green-500" />
                                <flux:text size="sm">Navegador Actual (Vínculo Activo)</flux:text>
                            </div>
                            <flux:button variant="ghost" size="xs" wire:click="clearToken" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30">Desvincular</flux:button>
                        </div>
                    @else
                        <div class="p-6 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 text-center bg-zinc-50/50 dark:bg-zinc-800/20">
                            <flux:icon name="no-symbol" class="mx-auto text-zinc-300 mb-2" size="sm" />
                            <flux:text size="xs" class="italic text-zinc-500">No hay token de notificación registrado para esta sesión.</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Herramientas de Diagnóstico (FCM Debugging) --}}
                <div x-show="permission === 'granted'" class="pt-4 border-t border-zinc-100 dark:border-zinc-700 space-y-4 animate-in fade-in duration-500">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider text-[10px] font-black">Herramientas de Diagnóstico</flux:heading>
                        <div class="flex gap-2">
                            <flux:button variant="ghost" size="xs" icon="paper-airplane" wire:click="testNotification" @click="isTesting = true; testResultError = ''" x-bind:disabled="isTesting">
                                <span x-show="!isTesting">Probar Notificación</span>
                                <span x-show="isTesting">Enviando...</span>
                            </flux:button>
                            <flux:button variant="ghost" size="xs" icon="sparkles" @click="isGenerating = true; window.requestPushPermission()" x-bind:disabled="isGenerating">
                                <span x-show="!isGenerating">Generar Nuevo Token</span>
                                <span x-show="isGenerating">Generando...</span>
                            </flux:button>
                        </div>
                    </div>

                    {{-- Token Display --}}
                    <div x-show="generatedToken" class="space-y-2">
                        <flux:label size="sm">Token FCM Generado</flux:label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-zinc-100 dark:bg-zinc-950 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 font-mono text-[10px] break-all leading-tight text-zinc-600 dark:text-zinc-400 max-h-24 overflow-y-auto" x-text="generatedToken"></div>
                            <flux:button variant="outline" size="sm" icon="clipboard" @click="copy(generatedToken)" title="Copiar Token"></flux:button>
                        </div>
                        <flux:text size="xs" class="text-indigo-600 dark:text-indigo-400 font-medium">Este token identifica a este navegador ante Firebase.</flux:text>
                    </div>

                    {{-- Error Display from Frontend Generation --}}
                    <div x-show="lastError" class="p-4 rounded-xl border border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/20">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 text-red-700 dark:text-red-400">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 shrink-0" />
                                <div>
                                    <flux:heading level="3" size="sm" class="font-bold text-red-900 dark:text-red-200">Error en Navegador (Front-end)</flux:heading>
                                    <flux:text size="xs" class="text-red-700 dark:text-red-400 mt-1 break-words" x-text="lastError"></flux:text>
                                </div>
                            </div>
                            <flux:button variant="ghost" size="xs" icon="clipboard" @click="copy(lastError)" class="text-red-600 hover:bg-red-100 dark:hover:bg-red-950/50" title="Copiar Error"></flux:button>
                        </div>
                    </div>

                    {{-- Error Display from Backend Test --}}
                    <div x-show="testResultError" class="p-4 rounded-xl border border-orange-200 bg-orange-50 dark:border-orange-900/40 dark:bg-orange-900/20">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 text-orange-800 dark:text-orange-300">
                                <flux:icon name="exclamation-circle" class="mt-0.5 shrink-0" />
                                <div>
                                    <flux:heading level="3" size="sm" class="font-bold text-orange-900 dark:text-orange-200">Error de Envío de Firebase (Back-end)</flux:heading>
                                    <pre class="mt-2 whitespace-pre-wrap font-mono text-[10px] leading-tight text-orange-800 dark:text-orange-300 break-words max-h-40 overflow-y-auto" x-text="testResultError"></pre>
                                </div>
                            </div>
                            <flux:button variant="ghost" size="xs" icon="clipboard" @click="copy(testResultError)" class="text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-950/50" title="Copiar Error"></flux:button>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-900/10 flex items-start gap-4">
                    <flux:icon name="information-circle" class="text-blue-600 dark:text-blue-400 mt-1" />
                    <div class="space-y-1">
                        <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 font-bold">Importante</flux:heading>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-400 leading-relaxed">
                            Las notificaciones push dependen de su navegador y sistema operativo. Asegúrese de que las notificaciones de este sitio no estén desactivadas en la configuración de su equipo.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
