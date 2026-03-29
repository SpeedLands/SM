<?php

use App\Models\Cycle;
use App\Models\ClassGroup;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public ?string $cycleId = null;
    public ?string $groupId = null;
    public ?string $parentCycleId = null;
    public ?string $parentGroupId = null;
    public bool $generatePasswords = false;
    public bool $generateTeacherPasswords = false;
    public bool $includeParents = false;
    public bool $generateStudentParentPasswords = false;
    public ?string $attendanceCycleId = null;
    public ?string $attendanceGroupId = null;
    public int $attendanceMonth;
    public int $attendanceYear;

    public string $reportMode = 'group';
    public ?string $reportGroupId = null;
    public ?string $reportStudentId = null;
    public string $reportStudentSearch = '';
    public bool $reportAllCycles = false;

    public function mount(): void
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $this->cycleId = $activeCycle->id;
            $this->parentCycleId = $activeCycle->id;
            $this->attendanceCycleId = $activeCycle->id;
        }

        $this->attendanceMonth = (int) now()->format('m');
        $this->attendanceYear = (int) now()->format('Y');

        if ($this->attendanceCycleId) {
            $this->attendanceGroupId = $this->attendanceGroups()->first()?->id;
        }
    }

    #[Computed]
    public function attendanceGroups()
    {
        if (! $this->attendanceCycleId) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->attendanceCycleId)
            ->orderBy('grade')
            ->orderBy('section')
            ->get();
    }

    public function updatedAttendanceCycleId(): void
    {
        $this->attendanceGroupId = null;
    }

    #[Computed]
    public function cycles()
    {
        return Cycle::orderBy('name', 'desc')->get();
    }

    #[Computed]
    public function groups()
    {
        if (! $this->cycleId) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->cycleId)
            ->orderBy('grade')
            ->orderBy('section')
            ->get();
    }

    #[Computed]
    public function parentGroups()
    {
        if (! $this->parentCycleId) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->parentCycleId)
            ->orderBy('grade')
            ->orderBy('section')
            ->get();
    }

    public function updatedCycleId(): void
    {
        $this->groupId = null;
        $this->includeParents = false;
        $this->generateStudentParentPasswords = false;
    }

    public function updatedGroupId(): void
    {
        $this->includeParents = false;
        $this->generateStudentParentPasswords = false;
    }
    
    public function updatedParentCycleId(): void
    {
        $this->parentGroupId = null;
    }

    #[Computed]
    public function reportStudents()
    {
        if (strlen($this->reportStudentSearch) < 3) {
            return collect();
        }

        $activeCycle = Cycle::where('is_active', true)->first();

        // Join with cycle associations to get cycle clarity
        return \App\Models\Student::with(['cycleAssociations.cycle'])
            ->where('name', 'like', "%{$this->reportStudentSearch}%")
            ->limit(10)
            ->get()
            ->map(function ($student) use ($activeCycle) {
                $isActive = $activeCycle && $student->cycleAssociations->contains('cycle_id', $activeCycle->id);
                $lastCycle = $student->cycleAssociations->sortByDesc('cycle.start_date')->first()?->cycle?->name;
                
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'group' => $student->group_name,
                    'is_active' => $isActive,
                    'cycle_name' => $isActive ? 'Ciclo Activo' : ($lastCycle ?? 'Sin ciclo'),
                ];
            });
    }

    public function selectReportStudent(string $id, string $name): void
    {
        $this->reportStudentId = $id;
        $this->reportStudentSearch = $name;
    }

    public function resetReportStudent(): void
    {
        $this->reportStudentId = null;
        $this->reportStudentSearch = '';
    }

    public function updatedReportStudentSearch(): void
    {
        $this->reportStudentId = null;
    }
}; ?>

<div class="py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Exportar Datos a Excel</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Descargue la información del sistema en formatos compatibles con el importador.</flux:text>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Teachers & Admins -->
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                        <flux:icon name="identification" />
                    </div>
                    <div>
                        <flux:heading size="lg">Maestros y Administradores</flux:heading>
                        <flux:text size="sm">Exporta la lista completa de personal.</flux:text>
                    </div>
                </div>

                <div class="flex flex-col gap-3 mb-6">
                    <flux:checkbox 
                        wire:model.live="generateTeacherPasswords" 
                        label="Generar nuevas contraseñas" 
                        description="Asignará una contraseña aleatoria a cada usuario exportado." 
                    />
                </div>

                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button
                        as="a"
                        :href="route('export.teachers', ['generate_passwords' => $generateTeacherPasswords])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full"
                    >
                        Descargar Maestros
                    </flux:button>
                </div>
            </div>

            <!-- Parents -->
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                            <flux:icon name="users" />
                        </div>
                        <div>
                            <flux:heading size="lg">Padres y Tutores</flux:heading>
                            <flux:text size="sm">Exporta padres con sus hijos vinculados.</flux:text>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col gap-3 mb-6">
                    <div class="flex flex-row gap-2 w-full">
                        <flux:select wire:model.live="parentCycleId" placeholder="Ciclo Escolar" class="w-1/2">
                            @foreach ($this->cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="parentGroupId" class="w-1/2">
                            <option value="">Todos los grupos</option>
                            @foreach ($this->parentGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:checkbox 
                        wire:model.live="generatePasswords" 
                        label="Generar nuevas contraseñas" 
                        description="Asignará una contraseña aleatoria a cada usuario exportado." 
                    />
                </div>
                
                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button
                        as="a"
                        :href="route('export.parents', ['cycle_id' => $parentCycleId, 'group_id' => $parentGroupId, 'generate_passwords' => $generatePasswords])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full"
                    >
                        Descargar Padres
                    </flux:button>
                </div>
            </div>

            <!-- Students -->
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                        <flux:icon name="academic-cap" />
                    </div>
                    <div>
                        <flux:heading size="lg">Alumnos</flux:heading>
                        <flux:text size="sm">Exporta alumnos por ciclo y grupo.</flux:text>
                    </div>
                </div>

                <div class="flex flex-col gap-3 mb-6">
                    <div class="flex flex-row gap-2 w-full">
                        <flux:select wire:model.live="cycleId" placeholder="Ciclo Escolar" class="w-1/2">
                            @foreach ($this->cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="groupId" class="w-1/2">
                            <option value="">Todos los grupos</option>
                            @foreach ($this->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    @if ($groupId)
                        <flux:switch 
                            wire:model.live="includeParents" 
                            label="Incluir padres de familia" 
                            description="Agrega una hoja adicional con los padres vinculados al grupo." 
                        />
                        @if ($includeParents)
                            <flux:checkbox 
                                wire:model.live="generateStudentParentPasswords" 
                                label="Generar nuevas contraseñas para padres" 
                                description="Asignará una contraseña aleatoria a cada padre exportado." 
                            />
                        @endif
                    @endif
                    <flux:text size="sm" class="flex items-center gap-2">
                            <flux:icon name="information-circle" variant="micro" />
                            Los datos sensibles (PII) se exportarán desencriptados.
                    </flux:text>
                </div>

                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button
                        as="a"
                        :href="route('export.students', ['cycle_id' => $cycleId, 'group_id' => $groupId, 'include_parents' => $includeParents, 'generate_passwords' => $generateStudentParentPasswords])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full"
                    >
                        Descargar Alumnos
                    </flux:button>
                </div>
            </div>

            <!-- Attendance -->
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                        <flux:icon name="clipboard-document-check" />
                    </div>
                    <div>
                        <flux:heading size="lg">Asistencias</flux:heading>
                        <flux:text size="sm">Exporta el registro mensual por grupo.</flux:text>
                    </div>
                </div>

                <div class="flex flex-col gap-3 mb-6">
                    <div class="flex flex-row gap-2 w-full">
                        <flux:select wire:model.live="attendanceCycleId" placeholder="Ciclo Escolar" class="w-1/2">
                            @foreach ($this->cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="attendanceGroupId" placeholder="Seleccione grupo" class="w-1/2">
                            @foreach ($this->attendanceGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex flex-row gap-2 w-full">
                        <flux:select wire:model.live="attendanceMonth" class="w-1/2">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}">{{ ucfirst(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="attendanceYear" class="w-1/2">
                            @foreach (range(now()->year - 2, now()->year + 1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    @if($attendanceGroupId)
                        <flux:button
                            as="a"
                            :href="route('export.attendance', ['group_id' => $attendanceGroupId, 'month' => $attendanceMonth, 'year' => $attendanceYear])"
                            variant="primary"
                            icon="document-arrow-down"
                            class="w-full"
                        >
                            Descargar Asistencias
                        </flux:button>
                    @else
                        <flux:button variant="primary" disabled icon="document-arrow-down" class="w-full">
                            Seleccione un grupo
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-6">
            <!-- Reports -->
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                        <flux:icon name="document-text" />
                    </div>
                    <div>
                        <flux:heading size="lg">Reportes Disciplinarios</flux:heading>
                        <flux:text size="sm">Exporta el historial de incidencias por grupo o alumno.</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg w-full">
                            <button 
                                wire:click="$set('reportMode', 'group')" 
                                class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all {{ $reportMode === 'group' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                Por Grupo
                            </button>
                            <button 
                                wire:click="$set('reportMode', 'student')" 
                                class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all {{ $reportMode === 'student' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                Por Alumno
                            </button>
                        </div>

                        @if($reportMode === 'group')
                            <div class="space-y-3">
                                <flux:select wire:model.live="reportGroupId" placeholder="Seleccione grupo (Ciclo Activo)">
                                    <option value="">Seleccione un grupo...</option>
                                    @foreach ($this->groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:text size="xs" class="text-zinc-400 italic flex items-center gap-1">
                                    <flux:icon name="information-circle" variant="micro" />
                                    La exportación grupal siempre se filtra por el ciclo escolar activo.
                                </flux:text>
                            </div>
                        @else
                            <div class="space-y-4 font-normal">
                                <div class="relative">
                                    @if(!$reportStudentId)
                                        <flux:input 
                                            wire:model.live.debounce.300ms="reportStudentSearch" 
                                            label="Buscar Alumno" 
                                            placeholder="Escriba nombre del alumno..." 
                                            icon="magnifying-glass"
                                        />
                                        
                                        @if(count($this->reportStudents) > 0)
                                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden max-h-60 overflow-y-auto">
                                                @foreach($this->reportStudents as $student)
                                                    <button 
                                                        type="button" 
                                                        wire:click="selectReportStudent('{{ $student['id'] }}', '{{ $student['name'] }}')" 
                                                        class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex justify-between items-start gap-4 transition-colors border-b border-zinc-50 dark:border-zinc-800 last:border-0"
                                                    >
                                                        <div class="flex flex-col flex-1 min-w-0">
                                                            <span class="font-bold text-sm text-zinc-700 dark:text-zinc-200 whitespace-normal wrap-anywhere uppercase leading-tight">{{ $student['name'] }}</span>
                                                            <span class="text-xs text-zinc-500 mt-1">{{ $student['grade'] }}{{ $student['group'] }}</span>
                                                        </div>
                                                        <div class="shrink-0 pt-0.5">
                                                            <flux:badge size="xs" :color="$student['is_active'] ? 'green' : 'zinc'" class="shrink-0 min-w-20 justify-center">
                                                                {{ $student['cycle_name'] }}
                                                            </flux:badge>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <div class="p-4 rounded-xl border border-green-200 bg-green-50/30 dark:border-green-900/30 dark:bg-green-900/10 flex items-center justify-between group">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                                                    <flux:icon name="user" />
                                                </div>
                                                <div>
                                                    <div class="text-xs uppercase tracking-widest font-black text-green-600/70 dark:text-green-400/70">Alumno seleccionado</div>
                                                    <div class="font-bold text-zinc-900 dark:text-white">{{ $reportStudentSearch }}</div>
                                                </div>
                                            </div>
                                            <flux:button 
                                                variant="ghost" 
                                                size="xs" 
                                                icon="x-mark" 
                                                wire:click="resetReportStudent"
                                            >
                                                Cambiar
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>

                                <flux:switch 
                                    wire:model.live="reportAllCycles" 
                                    label="Incluir todos los ciclos" 
                                    description="Si se desactiva, solo se exportarán reportes del ciclo activo." 
                                />
                            </div>
                        @endif
                    </div>

                    <div class="bg-zinc-50 dark:bg-zinc-800/30 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 flex flex-col justify-center items-center text-center">
                        <div class="mb-4 p-3 bg-white dark:bg-zinc-900 rounded-full border border-zinc-100 dark:border-zinc-800 shadow-sm">
                            <flux:icon name="arrow-down-tray" size="xl" class="text-zinc-600 dark:text-zinc-400" />
                        </div>
                        
                        @php
                            $canDownload = ($reportMode === 'group' && $reportGroupId) || ($reportMode === 'student' && $reportStudentId);
                            $downloadUrl = $canDownload 
                                ? route('export.reports', [
                                    'group_id' => $reportMode === 'group' ? $reportGroupId : null,
                                    'student_id' => $reportMode === 'student' ? $reportStudentId : null,
                                    'all_cycles' => $reportMode === 'student' ? $reportAllCycles : '0'
                                  ])
                                : '#';
                        @endphp

                        <flux:button
                            as="a"
                            :href="$downloadUrl"
                            variant="primary"
                            icon="document-arrow-down"
                            class="w-full sm:w-64"
                            :disabled="!$canDownload"
                        >
                            Descargar Reportes
                        </flux:button>
                        
                        @if(!$canDownload)
                            <flux:text size="xs" class="mt-3 text-zinc-400">
                                {{ $reportMode === 'group' ? 'Por favor seleccione un grupo.' : 'Por favor busque y seleccione un alumno.' }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="mb-2">¿Cómo funcionan estas exportaciones?</flux:heading>
            <flux:text size="xs" class="leading-relaxed">
                Los archivos generados mantienen exactamente el mismo orden de columnas que el sistema de importación. Esto significa que puede descargar los datos, editarlos en Excel (agregar alumnos, cambiar correos, etc.) y volver a subirlos en la sección "Importar Datos" para aplicar los cambios de forma masiva.
            </flux:text>
        </div>
    </div>
</div>