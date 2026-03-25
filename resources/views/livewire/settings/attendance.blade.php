<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $matinoEntryTime = '07:30';
    public string $vespertinoEntryTime = '13:30';
    public int $graceMinutes = 10;
    
    public bool $autoAbsenceEnabled = false;
    public string $matutinoAutoAbsenceTime = '14:00';
    public string $vespertinoAutoAbsenceTime = '20:00';

    public function mount(): void
    {
        $this->authorize('admin-only');
        $this->matinoEntryTime = Setting::get('attendance.matutino_entry_time', '07:30');
        $this->vespertinoEntryTime = Setting::get('attendance.vespertino_entry_time', '13:30');
        $this->graceMinutes = (int) Setting::get('attendance.grace_minutes', 10);
        
        $this->autoAbsenceEnabled = (bool) Setting::get('attendance.auto_absence_enabled', false);
        $this->matutinoAutoAbsenceTime = Setting::get('attendance.matutino_auto_absence_time', '14:00');
        $this->vespertinoAutoAbsenceTime = Setting::get('attendance.vespertino_auto_absence_time', '20:00');
    }

    public function save(): void
    {
        $this->validate([
            'matinoEntryTime' => ['required', 'date_format:H:i'],
            'vespertinoEntryTime' => ['required', 'date_format:H:i'],
            'graceMinutes' => ['required', 'integer', 'min:0', 'max:60'],
            'autoAbsenceEnabled' => ['required', 'boolean'],
            'matutinoAutoAbsenceTime' => ['required', 'date_format:H:i'],
            'vespertinoAutoAbsenceTime' => ['required', 'date_format:H:i'],
        ], [
            'matinoEntryTime.required' => 'La hora de entrada matutina es obligatoria.',
            'vespertinoEntryTime.required' => 'La hora de entrada vespertina es obligatoria.',
            'graceMinutes.required' => 'Los minutos de gracia son obligatorios.',
            'graceMinutes.integer' => 'Los minutos de gracia deben ser un número entero.',
            'graceMinutes.min' => 'Los minutos de gracia no pueden ser negativos.',
            'graceMinutes.max' => 'Los minutos de gracia no pueden exceder los 60 minutos.',
            'matutinoAutoAbsenceTime.required' => 'La hora límite matutina es obligatoria.',
            'vespertinoAutoAbsenceTime.required' => 'La hora límite vespertina es obligatoria.',
        ]);

        Setting::set('attendance.matutino_entry_time', $this->matinoEntryTime);
        Setting::set('attendance.vespertino_entry_time', $this->vespertinoEntryTime);
        Setting::set('attendance.grace_minutes', $this->graceMinutes);
        
        Setting::set('attendance.auto_absence_enabled', $this->autoAbsenceEnabled);
        Setting::set('attendance.matutino_auto_absence_time', $this->matutinoAutoAbsenceTime);
        Setting::set('attendance.vespertino_auto_absence_time', $this->vespertinoAutoAbsenceTime);

        $this->dispatch('notify', [
            'message' => 'Configuración guardada correctamente.',
            'variant' => 'success',
        ]);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        heading="Asistencia"
        subheading="Configura los horarios de entrada y el tiempo de tolerancia para cada turno."
    >
        <form wire:submit="save" class="space-y-6">
            {{-- Turno Matutino --}}
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/50 rounded-lg flex items-center justify-center">
                        <flux:icon.sun class="w-4 h-4 text-amber-500" />
                    </div>
                    <flux:heading size="sm">Turno Matutino</flux:heading>
                </div>

                <flux:field>
                    <flux:label>Hora de entrada</flux:label>
                    <flux:input type="time" wire:model="matinoEntryTime" />
                    <flux:error name="matinoEntryTime" />
                    <flux:description>Alumnos que lleguen después de esta hora + tolerancia serán marcados como <strong>Retardo</strong>.</flux:description>
                </flux:field>
            </div>

            {{-- Turno Vespertino --}}
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                        <flux:icon.moon class="w-4 h-4 text-indigo-500" />
                    </div>
                    <flux:heading size="sm">Turno Vespertino</flux:heading>
                </div>

                <flux:field>
                    <flux:label>Hora de entrada</flux:label>
                    <flux:input type="time" wire:model="vespertinoEntryTime" />
                    <flux:error name="vespertinoEntryTime" />
                    <flux:description>Alumnos que lleguen después de esta hora + tolerancia serán marcados como <strong>Retardo</strong>.</flux:description>
                </flux:field>
            </div>

            {{-- Tolerancia --}}
            <flux:field>
                <flux:label>Minutos de tolerancia</flux:label>
                <flux:input type="number" wire:model="graceMinutes" min="0" max="60" />
                <flux:error name="graceMinutes" />
                <flux:description>Minutos de gracia contados a partir de la hora de entrada de cada turno.</flux:description>
            </flux:field>

            {{-- Preview --}}
            @php
                $matinoThreshold = \Illuminate\Support\Carbon::createFromFormat('H:i', $matinoEntryTime)->addMinutes($graceMinutes)->format('H:i');
                $vespertinoThreshold = \Illuminate\Support\Carbon::createFromFormat('H:i', $vespertinoEntryTime)->addMinutes($graceMinutes)->format('H:i');
            @endphp
            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-sm space-y-1">
                <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">Vista previa del umbral</flux:text>
                <flux:text variant="subtle">
                    Matutino: Retardo después de las <strong>{{ $matinoThreshold }}</strong>
                </flux:text>
                <flux:text variant="subtle">
                    Vespertino: Retardo después de las <strong>{{ $vespertinoThreshold }}</strong>
                </flux:text>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="md">Faltas Automáticas (Inasistencias)</flux:heading>
                        <flux:text class="text-zinc-500 text-sm">El sistema marcará automáticamente como inasistencia a los alumnos que no tengan registro en el día tras pasar la hora límite.</flux:text>
                    </div>
                    <flux:switch wire:model.live="autoAbsenceEnabled" />
                </div>

                @if($autoAbsenceEnabled)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-in fade-in duration-300 relative">
                        <div class="p-4 rounded-xl relative border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <flux:field>
                                <flux:label>Hora límite Matutino</flux:label>
                                <flux:input type="time" wire:model="matutinoAutoAbsenceTime" />
                                <flux:error name="matutinoAutoAbsenceTime" />
                                <flux:description>Los alumnos de la mañana se procesarán al pasar esta hora.</flux:description>
                            </flux:field>
                        </div>
                        <div class="p-4 rounded-xl relative border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <flux:field>
                                <flux:label>Hora límite Vespertino</flux:label>
                                <flux:input type="time" wire:model="vespertinoAutoAbsenceTime" />
                                <flux:error name="vespertinoAutoAbsenceTime" />
                                <flux:description>Los alumnos de la tarde se procesarán al pasar esta hora.</flux:description>
                            </flux:field>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Guardar cambios</span>
                    <span wire:loading>Guardando...</span>
                </flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
