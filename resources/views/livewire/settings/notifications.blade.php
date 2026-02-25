<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notificaciones')" :subheading="__('Administra las notificaciones push de tu cuenta')">
        <div x-data="{
            permission: 'default',
            subscribed: false,
            loading: false,
            platform: 'unknown',
            supported: false,
            isStandalone: false,
            
            init() {
                this.platform = this.detectPlatform();
                this.supported = 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
                this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                
                if (this.supported) {
                    this.permission = Notification.permission;
                    this.checkSubscription();
                }
            },
            
            detectPlatform() {
                const ua = navigator.userAgent;
                const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
                if (isIOS) return 'ios';
                if (/android/i.test(ua)) return 'android';
                if (isSafari) return 'safari';
                return 'desktop';
            },
            
            async checkSubscription() {
                if (!this.supported) return;
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    this.subscribed = !!subscription;
                } catch (e) {
                    console.error('[Notifications] Check subscription error:', e);
                }
            },
            
            async enableNotifications() {
                this.loading = true;
                try {
                    const permission = await Notification.requestPermission();
                    this.permission = permission;
                    
                    if (permission === 'granted') {
                        if (typeof initPushNotifications === 'function') {
                            initPushNotifications();
                        } else if (typeof window.requestPushPermission === 'function') {
                            // Fallback
                        }
                        
                        // Wait a moment for subscription to register
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        await this.checkSubscription();
                    }
                } catch (e) {
                    console.error('[Notifications] Enable error:', e);
                } finally {
                    this.loading = false;
                }
            },
            
            async disableNotifications() {
                this.loading = true;
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    
                    if (subscription) {
                        // Notify server to remove subscription
                        await fetch('{{ route('push.unsubscribe') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ endpoint: subscription.endpoint })
                        });
                        
                        // Unsubscribe from browser
                        await subscription.unsubscribe();
                        this.subscribed = false;
                    }
                } catch (e) {
                    console.error('[Notifications] Disable error:', e);
                } finally {
                    this.loading = false;
                }
            }
        }" class="space-y-6">
        
            {{-- Status Card --}}
            <div class="rounded-lg border p-4" :class="{
                'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20': permission === 'granted' && subscribed,
                'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20': permission === 'default' || (permission === 'granted' && !subscribed),
                'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20': permission === 'denied',
                'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800': !supported,
            }">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <template x-if="permission === 'granted' && subscribed">
                            <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                        </template>
                        <template x-if="permission === 'default' || (permission === 'granted' && !subscribed)">
                            <flux:icon name="bell-alert" class="size-5 text-amber-600 dark:text-amber-400" />
                        </template>
                        <template x-if="permission === 'denied'">
                            <flux:icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                        </template>
                        <template x-if="!supported">
                            <flux:icon name="exclamation-triangle" class="size-5 text-zinc-500" />
                        </template>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-sm" x-show="permission === 'granted' && subscribed">Notificaciones activas</p>
                        <p class="font-semibold text-sm" x-show="permission === 'default'">Notificaciones no configuradas</p>
                        <p class="font-semibold text-sm" x-show="permission === 'granted' && !subscribed">Permiso concedido, pero no suscripto</p>
                        <p class="font-semibold text-sm" x-show="permission === 'denied'">Notificaciones bloqueadas</p>
                        <p class="font-semibold text-sm" x-show="!supported">No soportado</p>

                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1" x-show="permission === 'granted' && subscribed">
                            Recibirás avisos de reportes, citatorios y avisos importantes.
                        </p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1" x-show="permission === 'default'">
                            Activa las notificaciones para recibir avisos importantes al instante.
                        </p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1" x-show="permission === 'granted' && !subscribed">
                            El permiso fue concedido pero la suscripción no se completó. Intenta activar de nuevo.
                        </p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1" x-show="permission === 'denied'">
                            Has bloqueado las notificaciones. Para reactivarlas, debes cambiar el permiso en la configuración de tu navegador.
                        </p>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1" x-show="!supported">
                            Tu navegador no soporta notificaciones push.
                        </p>
                    </div>
                </div>
            </div>

            {{-- iOS specific instructions --}}
            <template x-if="platform === 'ios' && !isStandalone">
                <div class="rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 p-4">
                    <div class="flex items-start gap-3">
                        <flux:icon name="device-phone-mobile" class="size-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                        <div>
                            <p class="font-semibold text-sm text-blue-800 dark:text-blue-300">Paso requerido en iPhone/iPad</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                Para recibir notificaciones en iOS, primero debes instalar la app:
                            </p>
                            <ol class="text-xs text-blue-700 dark:text-blue-400 mt-2 space-y-1 list-decimal list-inside">
                                <li>Toca el botón <strong>Compartir</strong> <span class="inline-block">&#x2B06;&#xFE0F;</span> en Safari</li>
                                <li>Selecciona <strong>"Agregar a pantalla de inicio"</strong></li>
                                <li>Abre la app desde la pantalla de inicio</li>
                                <li>Luego activa las notificaciones desde aquí</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Action Buttons --}}
            <div x-show="supported">
                <template x-if="permission !== 'denied'">
                    <div class="flex gap-3">
                        <div x-show="!subscribed || permission === 'default'">
                            <flux:button 
                                @click="enableNotifications()" 
                                x-bind:disabled="loading"
                                variant="primary"
                                icon="bell"
                            >
                                <span x-show="!loading">Activar Notificaciones</span>
                                <span x-show="loading">Activando...</span>
                            </flux:button>
                        </div>

                        <div x-show="subscribed && permission === 'granted'">
                            <flux:button 
                                @click="disableNotifications()" 
                                x-bind:disabled="loading"
                                variant="danger"
                                icon="bell-slash"
                            >
                                <span x-show="!loading">Desactivar Notificaciones</span>
                                <span x-show="loading">Desactivando...</span>
                            </flux:button>
                        </div>
                    </div>
                </template>

                <template x-if="permission === 'denied'">
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                            <strong>¿Cómo desbloquear?</strong><br>
                            <span x-show="platform === 'ios'">Abre <strong>Configuración > Safari > Notificaciones</strong> y permite las notificaciones para esta app.</span>
                            <span x-show="platform === 'android'">Toca el ícono del candado 🔒 en la barra de direcciones y permite las notificaciones.</span>
                            <span x-show="platform === 'desktop' || platform === 'safari'">Haz clic en el ícono del candado 🔒 en la barra de direcciones y cambia el permiso de notificaciones a "Permitir".</span>
                        </p>
                    </div>
                </template>
            </div>

            {{-- Platform Info --}}
            <div class="text-xs text-zinc-400 dark:text-zinc-500 space-y-1 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                <p>
                    Plataforma detectada: 
                    <span x-text="platform === 'ios' ? 'iOS (Safari)' : platform === 'android' ? 'Android' : platform === 'safari' ? 'Safari (macOS)' : 'Escritorio'"></span>
                    <span x-show="platform === 'ios' || platform === 'safari'"> — Web Push (VAPID)</span>
                    <span x-show="platform !== 'ios' && platform !== 'safari'"> — Firebase Cloud Messaging</span>
                </p>
                <p x-show="isStandalone">
                    ✓ App instalada (modo standalone)
                </p>
            </div>
        </div>
    </x-settings.layout>
</section>
