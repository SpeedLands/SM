<?php

use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $gradeFilter = 'Todos';
    public string $groupFilter = 'Todos';
    public bool $onlyActiveCycle = true;
    public array $selectedStudents = [];
    public float $scale = 1.0;
    public bool $bulkMode = false;

    // Deletion State
    public string $idToDelete = '';
    public string $nameToDelete = '';
    public bool $showDeleteModal = false;

    #[On('student-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingGradeFilter(): void { $this->resetPage(); }
    public function updatingGroupFilter(): void { $this->resetPage(); }
    public function updatingOnlyActiveCycle(): void { $this->resetPage(); }

    public function exitBulkMode(): void
    {
        $this->bulkMode = false;
        $this->selectedStudents = [];
    }

    public function toggleBulkModeForStudent(string $studentId): void
    {
        if ($this->bulkMode && count($this->selectedStudents) === 1 && $this->selectedStudents[0] === $studentId) {
            $this->exitBulkMode();
        } else {
            $this->bulkMode = true;
            $this->selectedStudents = [$studentId];
        }
    }

    public function selectAll(): void
    {
        $allIds = Student::pluck('id')->toArray();
        if (count($this->selectedStudents) === count($allIds)) {
            $this->selectedStudents = [];
        } else {
            $this->selectedStudents = $allIds;
        }
    }

    public function confirmDelete(string $id): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        $student = Student::findOrFail($id);
        $this->idToDelete = $id;
        $this->nameToDelete = $student->name;
        $this->showDeleteModal = true;
    }

    public function deleteStudent(): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        if (!$this->idToDelete) return;

        $student = Student::withCount(['reports', 'communityServices', 'citations', 'noticeSignatures'])->findOrFail($this->idToDelete);
        
        if ($student->reports_count > 0 || $student->community_services_count > 0 || $student->citations_count > 0 || $student->notice_signatures_count > 0) {
            $this->dispatch('notify', [
                'variant' => 'danger', 
                'message' => 'No se puede eliminar un alumno que tiene historial.'
            ]);
            $this->showDeleteModal = false;
            return;
        }

        try {
            $student->delete();
            $this->showDeleteModal = false;
            $this->dispatch('notify', ['variant' => 'success', 'message' => 'Alumno eliminado correctamente.']);
            $this->refresh();
        } catch (\Exception $e) {
            $this->dispatch('notify', ['variant' => 'danger', 'message' => 'Error al eliminar el alumno.']);
        }
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $classGroups = $activeCycle ? ClassGroup::where('cycle_id', $activeCycle->id)->get() : collect();
        
        $query = Student::query()
            ->select('students.*')
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->join('student_parents', 'students.id', '=', 'student_parents.student_id')
                  ->where('student_parents.parent_id', auth()->id());
            })
            ->when($this->onlyActiveCycle && $activeCycle, function ($q) use ($activeCycle) {
                $q->whereHas('currentCycleAssociation', function ($sq) use ($activeCycle) {
                    $sq->where('cycle_id', $activeCycle->id);
                });
            });

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->gradeFilter !== 'Todos') {
            $query->where('grade', $this->gradeFilter);
        }

        if ($this->groupFilter !== 'Todos') {
            $query->where('group_name', $this->groupFilter);
        }

        return [
            'students' => $query->withCount(['reports', 'communityServices', 'citations', 'noticeSignatures'])->latest('name')->paginate(10),
            'classGroups' => $classGroups,
            'activeCycle' => $activeCycle,
            'availableSections' => $classGroups->pluck('section')->unique()->sort()->values()->toArray(),
        ];
    }
}; ?>

<div x-data="studentPopover()" x-init="init()" class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Gestión de Alumnos</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administre el padrón de estudiantes, sus datos de contacto y su situación académica.</flux:text>
        </div>
        @if(auth()->user()->isViewStaff())
            <flux:button variant="primary" icon="plus" x-on:click="$dispatch('create-student')">Inscribir Alumno</flux:button>
        @endif
    </div>

    @if($activeCycle && count($classGroups) === 0)
        <flux:callout variant="warning" heading="Faltan Grupos Académicos">
            No hay grupos (grados/secciones) configurados para el ciclo activo ({{ $activeCycle->name }}). Debe registrar grupos antes de poder inscribir alumnos.
            <flux:link href="{{ route('cycles.index') }}" icon="arrow-right-start-on-rectangle" class="ml-2 font-bold">Ir a Configuración de Ciclos</flux:link>
        </flux:callout>
    @endif

    @if(!$activeCycle)
        <flux:callout variant="danger" heading="Ciclo Activo No Encontrado">
            No hay un ciclo escolar marcado como activo. Por favor, configure uno para poder gestionar alumnos.
            <flux:link href="{{ route('cycles.index') }}" class="ml-2">Configurar Ciclo</flux:link>
        </flux:callout>
    @endif

    <x-filter-bar>
        <x-slot:pills>
            @if($search) <flux:badge variant="solid" color="zinc" class="shrink-0">"{{ $search }}"</flux:badge> @endif
            @if($gradeFilter !== 'Todos') <flux:badge variant="solid" color="zinc" class="shrink-0">{{ $gradeFilter }}</flux:badge> @endif
            @if($groupFilter !== 'Todos') <flux:badge variant="solid" color="zinc" class="shrink-0">Sección {{ $groupFilter }}</flux:badge> @endif
            @if($onlyActiveCycle) <flux:badge variant="solid" color="zinc" class="shrink-0">Ciclo Activo</flux:badge> @endif
        </x-slot:pills>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <flux:heading size="lg" level="2">Filtros</flux:heading>
            <flux:switch wire:model.live="onlyActiveCycle" label="Solo mostrar inscritos en ciclo actual" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <flux:field>
                <flux:label>Buscar Alumno</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nombre..." />
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="gradeFilter">
                    <option value="Todos">Todos los grados</option>
                    <option value="1º">1º Secundaria</option>
                    <option value="2º">2º Secundaria</option>
                    <option value="3º">3º Secundaria</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="groupFilter">
                    <option value="Todos">Todos los grupos</option>
                    @foreach($availableSections as $section)
                        <option value="{{ $section }}">Sección {{ $section }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </x-filter-bar>

    <!-- Mobile Cards (Staff View) -->
    <div class="space-y-4 sm:hidden pb-10">
        @forelse($students as $student)
            <div wire:key="std-mob-{{ $student->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0 overflow-hidden border border-zinc-200 dark:border-zinc-700">
                            @if($student->photo_url)
                                <img src="{{ $student->photo_url }}" class="w-full h-full object-cover">
                            @else
                                <flux:icon icon="user" class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                            @endif
                        </div>
                        <div class="flex flex-col">
                            <flux:text size="sm" class="font-bold uppercase">{{ $student->name }}</flux:text>
                            <flux:text size="xs" class="text-zinc-500 font-mono">{{ $student->curp }}</flux:text>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="flex gap-1 mb-1">
                            <flux:badge size="xs" color="blue">{{ $student->grade }}</flux:badge>
                            <flux:badge size="xs" color="neutral">{{ $student->group_name }}</flux:badge>
                        </div>
                        <flux:badge size="xs" variant="outline" color="{{ $student->turn === 'MATUTINO' ? 'sky' : 'orange' }}">
                            {{ $student->turn }}
                        </flux:badge>
                    </div>

                    @if($bulkMode)
                        <div class="ml-4 pt-1">
                            <flux:checkbox wire:model.live="selectedStudents" value="{{ $student->id }}" :disabled="!$student->curp" />
                        </div>
                    @endif
                </div>
                

                <div class="flex justify-end gap-1 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                    @if(auth()->user()->isAdmin())
                                        <flux:button variant="ghost" size="xs" icon="identification" 
                                            :title="$student->curp ? 'Selección para credencial' : 'Falta CURP para generar credencial'" 
                                            :disabled="!$student->curp"
                                            wire:click.stop="toggleBulkModeForStudent('{{ $student->id }}')" 
                                        />
                                    @endif
                    <flux:button variant="ghost" size="xs" icon="eye" x-on:click="$dispatch('view-history', { id: '{{ $student->id }}' })" title="Ver historial" />
                    @if(auth()->user()->isViewStaff())
                        {{-- Mobile quick actions --}}
                        <flux:dropdown>
                            <flux:button variant="ghost" size="xs" icon="plus-circle" title="Crear reporte, servicio o citatorio" />
                            <flux:menu>
                                <flux:menu.item icon="document-text" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToReport()">Generar Reporte</flux:menu.item>
                                <flux:menu.item icon="briefcase" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToService()">Servicio Comunitario</flux:menu.item>
                                <flux:menu.item icon="calendar-days" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToCitation()">Citatorio</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="clock" x-on:click="$dispatch('view-history', { id: '{{ $student->id }}' })">Ver Historial</flux:menu.item>
                                <flux:menu.item icon="pencil" x-on:click="$dispatch('edit-student', { id: '{{ $student->id }}' })">Editar Datos</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>

                        @if($student->reports_count === 0 && $student->community_services_count === 0 && $student->citations_count === 0 && $student->notice_signatures_count === 0)
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $student->id }}')" />
                        @else
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-zinc-300 dark:text-zinc-600" disabled />
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <x-empty-state icon="user-group" heading="Sin resultados" description="No se encontraron alumnos coincidentes" class="bg-zinc-50 dark:bg-zinc-800/50 border border-dashed border-zinc-300" />
        @endforelse
        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Students Table (Desktop View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        @if($bulkMode)
                        <th class="py-3 px-2 w-10">
                            {{-- We only count students WITH CURP for the select all comparison, as others can't be selected --}}
                            <flux:checkbox wire:click="selectAll" :checked="count($selectedStudents) > 0 && count($selectedStudents) === \App\Models\Student::whereNotNull('curp')->count()" />
                        </th>
                        @endif
                        <th class="py-3 px-2 font-semibold">Alumno</th>
                        <th class="py-3 px-2 font-semibold text-center">Grado / Grupo</th>
                        <th class="py-3 px-2 font-semibold text-center">Turno</th>
                        <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr wire:key="std-desk-{{ $student->id }}" 
                        @if(auth()->user()->isViewStaff())
                            class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors cursor-pointer {{ in_array($student->id, $selectedStudents) ? 'bg-indigo-50 dark:bg-indigo-900/10' : '' }}"
                        @else
                            class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors"
                        @endif
                        >
                            @if($bulkMode)
                            <td class="py-4 px-2" x-on:click.stop>
                                <flux:checkbox wire:model.live="selectedStudents" value="{{ $student->id }}" :disabled="!$student->curp" />
                            </td>
                            @endif
                            <td class="py-4 px-2"
                                @if(auth()->user()->isViewStaff())
                                    x-on:click="select($event)" data-id="{{ $student->id }}" data-name="{{ $student->name }}"
                                @endif
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center overflow-hidden">
                                        @if($student->photo_url)
                                            <img src="{{ $student->photo_url }}" class="w-full h-full object-cover">
                                        @else
                                            <flux:icon icon="user" class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-white uppercase">{{ $student->name }}</div>
                                        @if($student->curp)
                                            <div class="text-[10px] font-mono text-zinc-400 uppercase tracking-tighter">{{ $student->curp }}</div>
                                        @endif
                                        <div class="text-xs text-zinc-500">Inscrito en {{ $activeCycle?->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <flux:badge size="sm" color="blue">{{ $student->grade }}</flux:badge>
                                    <flux:badge size="sm" color="neutral">{{ $student->group_name }}</flux:badge>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                <flux:badge size="sm" variant="outline" color="{{ $student->turn === 'MATUTINO' ? 'sky' : 'orange' }}">
                                    {{ $student->turn }}
                                </flux:badge>
                            </td>
                            <td class="py-4 px-2 text-right">
                                <div class="flex justify-end gap-1">
                                    @if(auth()->user()->isAdmin())
                                        <flux:button variant="ghost" size="sm" icon="identification" 
                                            :title="$student->curp ? 'Selección para credencial' : 'Falta CURP para generar credencial'" 
                                            :disabled="!$student->curp"
                                            wire:click.stop="toggleBulkModeForStudent('{{ $student->id }}')" 
                                        />
                                    @endif
                                    <flux:button x-on:click.stop variant="ghost" size="sm" icon="eye" x-on:click="$dispatch('view-history', { id: '{{ $student->id }}' })" title="Ver historial" />
                                    @if(auth()->user()->isViewStaff())
                                        <flux:button x-on:click.stop variant="ghost" size="sm" icon="pencil" x-on:click="$dispatch('edit-student', { id: '{{ $student->id }}' })" title="Editar alumno" />
                                        @if($student->reports_count === 0 && $student->community_services_count === 0 && $student->citations_count === 0 && $student->notice_signatures_count === 0)
                                            <flux:button x-on:click.stop variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $student->id }}')" title="Eliminar alumno" />
                                        @else
                                            <flux:button x-on:click.stop variant="ghost" size="sm" icon="trash" class="text-zinc-300 dark:text-zinc-600" title="No se puede eliminar por historial asociado" disabled />
                                        @endif
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-zinc-500 italic">No se encontraron alumnos coincidentes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            </div>
        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-sm text-zinc-500">
            <div>{{ $students->links() }}</div>
        </div>
    </div>

    @if(auth()->user()->isViewStaff())
        <livewire:students.student-form />
    @endif

    <livewire:students.history-modal />

    <!-- Deletion Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="min-w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmar Eliminación</flux:heading>
                <flux:subheading class="whitespace-normal wrap-break-word">
                    ¿Estás seguro de eliminar al alumno <span class="font-bold text-zinc-900 dark:text-white uppercase wrap-break-word">{{ $nameToDelete }}</span>?
                    Esta acción es irreversible.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="deleteStudent">Eliminar Alumno</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Shared Popover (floating for Desktop) -->
    <div x-show="show" x-cloak class="z-50" x-bind:style="popoverStyle()" @click.away="hide()">
        <div :class="popoverClass" class="bg-white dark:bg-zinc-900 rounded shadow-lg p-2 border border-zinc-200 dark:border-zinc-700" x-ref="popover">
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToReport()">Reporte</button>
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToService()">Servicio Comunitario</button>
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToCitation()">Citatorio</button>
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        <!-- Bulk Actions Bar -->
        <div 
            x-show="$wire.selectedStudents.length > 0"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-10"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-4"
        >
            <div class="bg-zinc-900 text-white p-4 rounded-2xl shadow-2xl flex flex-col md:flex-row items-center justify-between gap-4 md:gap-6 border border-zinc-800">
                <div class="flex items-center gap-4 border-b md:border-b-0 md:border-r border-zinc-800 pb-3 md:pb-0 md:pr-6 w-full md:w-auto">
                    <flux:badge color="blue" size="sm" variant="solid" class="rounded-full h-8 w-8 flex items-center justify-center p-0 shrink-0">
                        <span x-text="$wire.selectedStudents.length"></span>
                    </flux:badge>
                    <div class="flex flex-col">
                        <span class="text-xs text-zinc-400 font-bold uppercase shrink-0">Seleccionados</span>
                        <span class="text-sm">Imprimir</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 grow w-full">
                    <div class="flex flex-col grow min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-zinc-500 uppercase font-black">Escala</span>
                                <button type="button" x-on:click="$flux.modal('scale-help-modal').show()" class="text-zinc-500 hover:text-white transition-colors">
                                    <flux:icon name="information-circle" variant="micro" class="size-3" />
                                </button>
                            </div>
                            <span class="text-xs font-mono text-zinc-400" x-text="Math.round($wire.scale * 100) + '% (' + ($wire.scale * 9.8).toFixed(1) + 'x' + ($wire.scale * 6.7).toFixed(1) + ' cm)'"></span>
                        </div>
                        <input type="range" wire:model.live="scale" min="0.5" max="2.0" step="0.1" class="w-full h-1 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-blue-500">
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <flux:button 
                            variant="primary" 
                            size="sm"
                            icon="printer" 
                            type="submit"
                            form="bulk-print-form"
                        >
                            <span class="hidden sm:inline">Generar PDF</span>
                            <span class="sm:hidden">PDF</span>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            size="sm" 
                            icon="x-mark" 
                            wire:click="exitBulkMode"
                            title="Cancelar selección"
                        />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->user()->isAdmin())
        <!-- Hidden form for bulk printing (native submission to bypass popup blockers) -->
        <form id="bulk-print-form" method="POST" action="{{ route('students.credential.bulk') }}" target="_blank" class="hidden">
            @csrf
            @foreach($selectedStudents as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}">
            @endforeach
            <input type="hidden" name="scale" value="{{ $scale }}">
        </form>

        <!-- Scale Help Modal -->
        <flux:modal name="scale-help-modal" class="w-full max-w-md">
            <div class="space-y-6">
                <header>
                    <flux:heading size="lg">Guía de Medidas (Centímetros)</flux:heading>
                    <flux:text>Referencia de tamaño final según la escala de impresión seleccionada.</flux:text>
                </header>

                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="px-4 py-2">Escala</th>
                                <th class="px-4 py-2">Alto (cm)</th>
                                <th class="px-4 py-2">Ancho (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @php
                                $measurements = [
                                    ['s' => '200%', 'h' => '19.6', 'w' => '13.4'],
                                    ['s' => '190%', 'h' => '18.62', 'w' => '12.73'],
                                    ['s' => '180%', 'h' => '17.64', 'w' => '12.06'],
                                    ['s' => '170%', 'h' => '16.66', 'w' => '11.39'],
                                    ['s' => '160%', 'h' => '15.68', 'w' => '10.72'],
                                    ['s' => '150%', 'h' => '14.7', 'w' => '10.05'],
                                    ['s' => '140%', 'h' => '13.72', 'w' => '9.38'],
                                    ['s' => '130%', 'h' => '12.74', 'w' => '8.71'],
                                    ['s' => '120%', 'h' => '11.76', 'w' => '8.04'],
                                    ['s' => '110%', 'h' => '10.78', 'w' => '7.37'],
                                    ['s' => '100%', 'h' => '9.8', 'w' => '6.7'],
                                    ['s' => '90%', 'h' => '8.82', 'w' => '6.03'],
                                    ['s' => '80%', 'h' => '7.84', 'w' => '5.36'],
                                    ['s' => '70%', 'h' => '6.86', 'w' => '4.69'],
                                    ['s' => '60%', 'h' => '5.88', 'w' => '4.02'],
                                    ['s' => '50%', 'h' => '4.9', 'w' => '3.35'],
                                ];
                            @endphp
                            @foreach($measurements as $m)
                                <tr @class(['bg-blue-50/50 dark:bg-blue-900/10 font-bold' => $m['s'] === '100%'])>
                                    <td class="px-4 py-1.5">{{ $m['s'] }}</td>
                                    <td class="px-4 py-1.5">{{ $m['h'] }} cm</td>
                                    <td class="px-4 py-1.5">{{ $m['w'] }} cm</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cerrar</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

<script>
function studentPopover(){
    return {
        show: false,
        x: 0,
        y: 0,
        width: 180,
        studentId: null,
        studentName: null,
        popoverClass: '',
        init(){
            // close popover when scrolling to avoid misplacement
            window.addEventListener('scroll', () => { if(this.show) this.hide(); }, true);
            window.addEventListener('resize', () => { if(this.show) this.hide(); });
        },
        popoverStyle(){
            return `position:fixed; left:${this.x}px; top:${this.y}px; width:${this.width}px; z-index:9999;`;
        },
        select(ev){
            // prevent the click from bubbling to the document and triggering @click.away
            ev.stopPropagation();
            const tr = ev.currentTarget;
            const rect = tr.getBoundingClientRect();
            this.studentId = tr.dataset.id;
            this.studentName = tr.dataset.name;

            const padding = 8;
            const estimatedHeight = 120; // approximate popover height

            // Responsive width: full width on small screens with small margins
            if (window.innerWidth <= 640) {
                this.width = Math.max(200, Math.min(window.innerWidth - 32, 360));
                this.popoverClass = '';
                // horizontal center
                this.x = Math.round((window.innerWidth - this.width) / 2);
            } else {
                this.width = 180;
                // center horizontally above the row
                let left = rect.left + (rect.width / 2) - (this.width / 2);
                if (left < padding) left = padding;
                if (left + this.width > window.innerWidth - padding) left = window.innerWidth - this.width - padding;
                this.x = Math.round(left);
            }

            // position above the row if there's space, otherwise below
            let top = rect.top - estimatedHeight - padding;
            if (top < 8) {
                top = rect.bottom + padding; // place below
            }

            // ensure popover not off-screen vertically
            if (top + estimatedHeight > window.innerHeight - padding) {
                top = Math.max(padding, window.innerHeight - estimatedHeight - padding);
            }

            this.y = Math.round(top + window.scrollY - window.scrollY); // keep fixed viewport coords
            this.show = true;
        },
        hide(){ this.show = false; this.studentId = null; this.studentName = null; },
        goToReport(){
            if(!this.studentId) return;
            const url = "{{ route('reports.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        },
        goToService(){
            if(!this.studentId) return;
            const url = "{{ route('community-services.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        },
        goToCitation(){
            if(!this.studentId) return;
            const url = "{{ route('citations.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        }
    }
}
</script>