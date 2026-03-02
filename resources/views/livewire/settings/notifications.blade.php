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
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Gestione cómo recibe las alertas y notificaciones push.')">
        <div class="space-y-6">
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
                            <flux:icon name="bell-alert" class="text-zinc-500" />
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
                            <flux:badge color="green" size="sm">Recibiendo</flux:badge>
                        </template>
                    </div>

                    <template x-if="permission === 'denied'">
                        <flux:text size="xs" class="mt-2 text-red-500">
                            Parece que has bloqueado las notificaciones. Debes habilitarlas en la configuración de tu navegador para este sitio.
                        </flux:text>
                    </template>
                </div>

                <div class="space-y-2">
                    <flux:heading size="sm">Dispositivos Vinculados</flux:heading>
                    <flux:text size="xs">Aquí puedes ver si este navegador está registrado para recibir notificaciones.</flux:text>
                    
                    @if($hasToken)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2">
                                <flux:icon name="computer-desktop" variant="micro" class="text-green-500" />
                                <flux:text size="sm">Este navegador (Activo)</flux:text>
                            </div>
                            <flux:button variant="ghost" size="xs" wire:click="clearToken" class="text-red-500">Desvincular</flux:button>
                        </div>
                    @else
                        <div class="p-3 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 text-center">
                            <flux:text size="xs" class="italic text-zinc-500">No hay token de notificación registrado para esta sesión.</flux:text>
                        </div>
                    @endif
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
