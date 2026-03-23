<?php

use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use App\Models\Cycle;
use App\Models\StudentCycleAssociation;
use Livewire\WithFileUploads;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;

    public bool $show = false;
    public ?string $studentId = null;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
    }

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|size:18')]
    public string $curp = '';

    #[Validate('required|in:MATUTINO,VESPERTINO')]
    public string $turn = 'MATUTINO';

    #[Validate('required|exists:class_groups,id')]
    public string $classGroupId = '';

    public string $address = '';
    public string $emergencyContact = '';
    public string $otherContact = '';

    #[Validate('nullable|image|max:2048')]
    public $photo;
    public ?string $photoPreview = null;

    // Parent management
    public string $parentSearch = '';
    public string $parentRelationship = 'PADRE';
    public ?string $selectedParentId = null;
    public $parentSearchResults = [];
    public $currentParents = [];

    #[On('create-student')]
    public function openCreate(): void
    {
        $this->reset(['studentId', 'name', 'curp', 'turn', 'classGroupId', 'address', 'emergencyContact', 'otherContact', 'parentSearch', 'selectedParentId', 'parentSearchResults', 'currentParents', 'photo', 'photoPreview']);
        $this->show = true;
    }

    #[On('edit-student')]
    public function openEdit(string $id): void
    {
        $this->reset(['studentId', 'name', 'curp', 'turn', 'classGroupId', 'address', 'emergencyContact', 'otherContact', 'parentSearch', 'selectedParentId', 'parentSearchResults', 'currentParents', 'photo', 'photoPreview']);
        
        $student = Student::with(['parents', 'currentCycleAssociation'])->findOrFail($id);
        
        $this->studentId = $id;
        $this->name = $student->name;
        $this->curp = $student->curp ?? '';
        $this->turn = $student->turn;
        $this->classGroupId = (string)($student->currentCycleAssociation?->class_group_id ?? '');
        $this->address = $student->pii?->address_encrypted ?? '';
        $this->emergencyContact = $student->pii?->emergency_contact_encrypted ?? '';
        $this->otherContact = $student->pii?->other_contact_encrypted ?? '';
        
        $this->photoPreview = $student->photo_url;
        $this->currentParents = $student->parents;
        $this->show = true;
    }

    public function updatedParentSearch(): void
    {
        if (strlen($this->parentSearch) < 3) {
            $this->parentSearchResults = [];
            return;
        }

        $this->parentSearchResults = User::where('role', 'PARENT')
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->parentSearch . '%')
                      ->orWhere('email', 'like', '%' . $this->parentSearch . '%');
            })
            ->limit(5)
            ->get();
    }

    public function addParent(): void
    {
        if (!$this->studentId || !$this->selectedParentId) return;

        $student = Student::findOrFail($this->studentId);
        
        // Avoid duplicates
        if (!$student->parents()->wherePivot('parent_id', $this->selectedParentId)->exists()) {
            $student->parents()->attach($this->selectedParentId, ['relationship' => $this->parentRelationship]);
            $this->currentParents = $student->load('parents')->parents;
        }

        $this->reset(['parentSearch', 'selectedParentId', 'parentSearchResults']);
    }

    public function removeParent(string $parentId): void
    {
        if (!$this->studentId) return;

        $student = Student::findOrFail($this->studentId);
        $student->parents()->detach($parentId);
        $this->currentParents = $student->load('parents')->parents;
    }

    public function save(): void
    {
        $this->validate();

        \Illuminate\Support\Facades\DB::transaction(function () {
            $group = ClassGroup::findOrFail($this->classGroupId);
            
            $data = [
                'name' => mb_strtoupper($this->name),
                'curp' => $this->curp ? mb_strtoupper($this->curp) : null,
                'turn' => $this->turn,
                'grade' => $group->grade,
                'group_name' => $group->section,
            ];

            if (!$this->studentId && !isset($data['birth_date'])) {
                 $data['birth_date'] = '2010-01-01'; // Default as in StudentImporter
            }

            if ($this->studentId) {
                $student = Student::findOrFail($this->studentId);
                
                if ($this->photo) {
                    if ($student->photo_path) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
                    }
                    $data['photo_path'] = $this->photo->store('students/photos', 'public');
                }

                $student->update($data);
                Flux::toast('Alumno actualizado correctamente.');
            } else {
                if ($this->photo) {
                    $data['photo_path'] = $this->photo->store('students/photos', 'public');
                }
                $student = Student::create($data);
                $this->studentId = $student->id;
                Flux::toast('Alumno inscrito correctamente.');
            }

            // Save PII
            $pii = $student->pii()->firstOrNew(['student_id' => $student->id]);
            $pii->address_encrypted = $this->address;
            $pii->emergency_contact_encrypted = $this->emergencyContact;
            $pii->other_contact_encrypted = $this->otherContact;
            $pii->save();

            // Handle Association for active cycle
            $activeCycle = Cycle::where('is_active', true)->first();
            if ($activeCycle) {
                StudentCycleAssociation::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'cycle_id' => $activeCycle->id,
                    ],
                    [
                        'class_group_id' => $group->id,
                        'status' => 'ACTIVE',
                    ]
                );
            }
        });

        $this->show = false;
        $this->dispatch('student-saved');
    }

    public function render(): mixed
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        return view('livewire.students.student-form', [
            'classGroups' => $activeCycle 
                ? ClassGroup::where('cycle_id', $activeCycle->id)->orderBy('grade')->orderBy('section')->get()
                : collect(),
        ]);
    }
}; ?>

<flux:modal wire:model="show" class="w-full max-w-2xl px-6 py-4">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $studentId ? 'Editar Alumno' : 'Inscribir Nuevo Alumno' }}</flux:heading>
            <flux:subheading>Complete la información del estudiante.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="space-y-4">
                <flux:separator text="Información Básica" />
                
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Photo Section -->
                    <div class="flex flex-col items-center gap-2">
                        <flux:label>Foto del Alumno</flux:label>
                        <div class="relative group">
                            <div class="w-32 h-40 rounded-2xl bg-zinc-100 dark:bg-zinc-800 border-2 border-dashed border-zinc-300 dark:border-zinc-700 flex items-center justify-center overflow-hidden transition-all group-hover:border-indigo-400 dark:group-hover:border-indigo-500">
                                @if ($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif ($photoPreview)
                                    <img src="{{ $photoPreview }}" class="w-full h-full object-cover">
                                @else
                                    <flux:icon icon="pencil" size="lg" class="text-zinc-400 group-hover:text-indigo-500 transition-colors" />
                                @endif
                                
                                <label class="absolute inset-0 cursor-pointer bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center">
                                    <input type="file" wire:model="photo" class="hidden" accept="image/*">
                                </label>
                            </div>
                            @error('photo') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>
                        <flux:text size="xs" class="text-zinc-500 italic">Clic para subir foto</flux:text>
                    </div>

                    <!-- Basic Info Section -->
                    <div class="grow space-y-4">
                        <flux:input 
                            label="Nombre Completo" 
                            wire:model="name" 
                            placeholder="Ej. JUAN PEREZ LOPEZ" 
                            class="uppercase"
                            x-on:input="$el.value = $el.value.toUpperCase(); $wire.name = $el.value"
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input 
                                label="CURP" 
                                wire:model="curp" 
                                placeholder="ABCD010101XXXXX000" 
                                class="uppercase"
                                x-on:input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 18); $wire.curp = $el.value"
                            />

                            <flux:select label="Turno" wire:model="turn">
                                <option value="MATUTINO">Matutino</option>
                                <option value="VESPERTINO">Vespertino</option>
                            </flux:select>
                        </div>

                        <flux:select label="Grupo / Grado" wire:model="classGroupId">
                            <option value="">Seleccione grupo...</option>
                            @foreach($classGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <flux:separator text="Información de Contacto" />
                <flux:textarea label="Dirección" wire:model="address" placeholder="Calle, número, colonia..." rows="2" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input 
                        label="Teléfonos de contacto" 
                        wire:model="emergencyContact" 
                        placeholder="Ej. 12345678, 87654321"
                        x-on:input="emergencyContact = $event.target.value.replace(/\D/g, '')"
                    />
                    <flux:input label="Otro contacto / Parentesco" wire:model="otherContact" placeholder="Ej. Abuela - 1234..." />
                </div>
            </div>

            <div class="space-y-4">
                <flux:separator text="Padres de Familia" />
                
                <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/30">
                    <div class="flex items-start gap-3">
                        <flux:icon icon="information-circle" class="text-blue-600 dark:text-blue-400 shrink-0" />
                        <flux:text size="sm" class="text-blue-900 dark:text-blue-200">
                            Los datos de contacto detallados de los padres se gestionan a través de sus <b>Cuentas de Usuario</b>.
                        </flux:text>
                    </div>
                </div>

                @if($studentId)
                    <div class="space-y-4">
                        <div class="flex gap-2 items-end">
                            <flux:field class="grow">
                                <flux:label>Vincular nuevo Padre/Madre</flux:label>
                                <flux:input wire:model.live.debounce.300ms="parentSearch" icon="user-plus" placeholder="Buscar por nombre o email..." />
                            </flux:field>
                            <flux:select wire:model="parentRelationship" class="w-1/3">
                                <option value="PADRE">Padre</option>
                                <option value="MADRE">Madre</option>
                            </flux:select>
                            <flux:button wire:click="addParent" variant="primary" :disabled="!$selectedParentId">Vincular</flux:button>
                        </div>

                        @if(count($parentSearchResults) > 0)
                            <div class="p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 shadow-inner max-h-40 overflow-y-auto">
                                @foreach($parentSearchResults as $parent)
                                    <button type="button" 
                                        wire:click="$set('selectedParentId', '{{ $parent->id }}')"
                                        @class([
                                            'w-full flex items-center justify-between p-2 rounded text-left transition-colors',
                                            'bg-blue-100 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-800' => $selectedParentId === $parent->id,
                                            'hover:bg-zinc-200 dark:hover:bg-zinc-700' => $selectedParentId !== $parent->id
                                        ])
                                    >
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center text-xs font-bold">{{ $parent->initials() }}</div>
                                            <div>
                                                <div class="text-xs font-bold">{{ $parent->name }}</div>
                                                <div class="text-[10px] text-zinc-500">{{ $parent->email }}</div>
                                            </div>
                                        </div>
                                        @if($selectedParentId === $parent->id)
                                            <flux:icon icon="check" size="sm" class="text-blue-600" />
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-2">
                            <flux:heading size="sm">Padres Vinculados</flux:heading>
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse($currentParents as $parent)
                                    <div class="flex items-center justify-between py-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-xs font-bold text-purple-600">{{ $parent->initials() }}</div>
                                            <div class="whitespace-normal">
                                                <div class="text-sm font-bold uppercase">{{ $parent->name }}</div>
                                                <div class="text-xs text-zinc-500">{{ $parent->pivot?->relationship ?? 'Padre/Madre' }} · {{ $parent->phone ?? 'Sin teléfono' }}</div>
                                            </div>
                                        </div>
                                        <flux:button variant="ghost" size="sm" icon="x-mark" class="text-red-500" wire:click="removeParent('{{ $parent->id }}')" />
                                    </div>
                                @empty
                                    <flux:text class="italic text-xs text-zinc-500">No hay padres vinculados.</flux:text>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800/50 border border-dashed border-zinc-300 dark:border-zinc-700 text-center">
                        <flux:text size="sm" class="italic text-zinc-500">Guarde los datos básicos para poder vincular padres.</flux:text>
                    </div>
                @endif
            </div>

            <div class="flex gap-2 pt-4">
                <flux:spacer />
                <flux:button wire:click="$set('show', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $studentId ? 'Actualizar Registro' : 'Inscribir Alumno' }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
