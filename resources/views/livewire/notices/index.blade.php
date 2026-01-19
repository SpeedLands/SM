<?php

use App\Models\Notice;
use App\Models\NoticeSignature;
use App\Models\Student;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';

    // Create Modal
    public bool $showCreateModal = false;
    public string $title = '';
    public string $content = '';
    public string $type = 'GENERAL';
    public string $targetAudience = 'PARENTS';
    public bool $requiresAuthorization = false;
    public string $eventDate = '';
    public string $eventTime = '';

    public function mount(): void
    {
        $this->eventDate = now()->format('Y-m-d');
        $this->eventTime = now()->format('10:00');
    }

    public function saveNotice(): void
    {
        $this->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'type' => 'required',
            'targetAudience' => 'required',
        ], [
            'title.required' => 'El título es obligatorio.',
            'content.required' => 'El contenido es obligatorio.',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();
        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo escolar activo.', 'variant' => 'danger']);
            return;
        }

        Notice::create([
            'cycle_id' => $activeCycle->id,
            'author_id' => auth()->id(),
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'target_audience' => $this->targetAudience,
            'requires_authorization' => $this->requiresAuthorization,
            'event_date' => $this->eventDate ?: null,
            'event_time' => $this->eventTime ?: null,
            'date' => now(),
        ]);

        $this->showCreateModal = false;
        $this->reset(['title', 'content', 'requiresAuthorization']);
        $this->dispatch('notify', ['message' => 'Aviso publicado exitosamente.']);
    }

    public function signNotice(string $noticeId, string $studentId, bool $isAuthorized = true): void
    {
        NoticeSignature::updateOrCreate(
            ['notice_id' => $noticeId, 'student_id' => $studentId, 'parent_id' => auth()->id()],
            ['signed_at' => now(), 'authorized' => $isAuthorized]
        );

        $this->dispatch('notify', ['message' => 'Firma registrada correctamente.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $isStaff = in_array(auth()->user()->role, ['ADMIN', 'TEACHER']);

        if ($isStaff) {
            $notices = Notice::with(['author'])
                ->withCount('signatures')
                ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
                ->orderBy('date', 'desc')
                ->paginate(10);
                
            return [
                'notices' => $notices,
                'isStaff' => true,
            ];
        } else {
            // Parent view
            $studentIds = auth()->user()->students->pluck('id');
            
            $notices = Notice::with(['author', 'signatures' => fn($q) => $q->whereIn('student_id', $studentIds)])
                ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->whereIn('target_audience', ['PARENTS', 'ALL'])
                ->orderBy('date', 'desc')
                ->get();

            return [
                'notices' => $notices,
                'isStaff' => false,
                'myStudents' => auth()->user()->students,
            ];
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" level="1">Avisos y Comunicados</flux:heading>
            <flux:text class="text-zinc-500">Mural digital de avisos escolares.</flux:text>
        </div>
        @if($isStaff)
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">Nuevo Aviso</flux:button>
        @endif
    </div>

    @if($isStaff)
        <!-- Admin/Teacher View: Dashboard with stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar avisos..." icon="magnifying-glass" />
            <flux:select wire:model.live="typeFilter" placeholder="Filtrar por tipo...">
                <option value="">Todos los tipos</option>
                <option value="GENERAL">General</option>
                <option value="URGENT">Urgente</option>
                <option value="EVENT">Evento</option>
            </flux:select>
        </div>

        <div class="space-y-4">
            @forelse($notices as $notice)
                <div wire:key="{{ $notice->id }}" class="p-5 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative overflow-hidden group">
                    @if($notice->type === 'URGENT')
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-red-500"></div>
                    @endif
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <flux:badge size="sm" color="{{ $notice->type === 'URGENT' ? 'red' : ($notice->type === 'EVENT' ? 'blue' : 'neutral') }}" inset="left">
                                    {{ $notice->type === 'GENERAL' ? 'General' : ($notice->type === 'URGENT' ? 'Urgente' : 'Evento') }}
                                </flux:badge>
                                <flux:text size="sm" class="text-zinc-500">{{ $notice->date->format('d M, Y H:i') }}</flux:text>
                                <flux:text size="sm" class="text-zinc-400">· Por {{ $notice->author->name }}</flux:text>
                            </div>
                            <flux:heading level="3" size="md">{{ $notice->title }}</flux:heading>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400 line-clamp-2 text-sm leading-relaxed">{{ $notice->content }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <flux:badge color="neutral" icon="finger-print" variant="outline">{{ $notice->signatures_count }} Firmas</flux:badge>
                            @if($notice->requires_authorization)
                                <flux:badge color="purple" size="sm">Requiere Autorización</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-zinc-500 italic border border-dashed rounded-xl border-zinc-300 dark:border-zinc-700">
                    No hay avisos publicados.
                </div>
            @endforelse
            <div class="mt-4">
                {{ $notices->links() }}
            </div>
        </div>
    @else
        <!-- Parent View: Feed style -->
        <div class="space-y-8 max-w-3xl mx-auto">
            @forelse($notices as $notice)
                @foreach($myStudents as $student)
                    @php 
                        $signature = $notice->signatures->where('student_id', $student->id)->first();
                    @endphp
                    <div wire:key="n-{{ $notice->id }}-s-{{ $student->id }}" class="p-6 rounded-2xl border {{ $signature ? 'border-zinc-200 bg-zinc-50/50' : 'border-blue-200 bg-white' }} dark:border-zinc-700 dark:bg-zinc-900 shadow-lg relative transition-all hover:shadow-xl">
                        @if(!$signature && $notice->type === 'URGENT')
                            <div class="absolute -top-3 -right-3">
                                <flux:badge color="red" size="sm" class="animate-pulse shadow-md">Urgente</flux:badge>
                            </div>
                        @endif

                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-xl shadow-inner">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <flux:text size="xs" class="uppercase tracking-widest font-black text-zinc-400">Aviso para:</flux:text>
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[10px] font-bold">{{ $student->name }}</span>
                                    </div>
                                    <flux:heading level="3" size="lg" class="mt-0.5">{{ $notice->title }}</flux:heading>
                                </div>
                            </div>
                        </div>

                        <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800">
                            {!! nl2br(e($notice->content)) !!}
                        </div>

                        @if($notice->type === 'EVENT' && $notice->event_date)
                            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/30 flex items-center gap-4">
                                <div class="p-3 rounded-lg bg-blue-500 text-white shadow-lg">
                                    <flux:icon icon="calendar-days" />
                                </div>
                                <div>
                                    <flux:text size="sm" class="font-bold text-blue-800 dark:text-blue-200">Detalles del Evento</flux:text>
                                    <flux:text size="lg" class="text-blue-700 dark:text-blue-300 font-medium">
                                        {{ $notice->event_date->format('l, d de F Y') }} {{ $notice->event_time ? 'a las '.$notice->event_time : '' }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif

                        <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            @if(!$signature)
                                @if($notice->requires_authorization)
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/10 rounded-lg border border-purple-100 dark:border-purple-800/30">
                                            <flux:icon icon="information-circle" class="text-purple-600 shrink-0 mt-0.5" />
                                            <flux:text size="sm" class="text-purple-900 dark:text-purple-200 italic">
                                                Este comunicado requiere una respuesta de su parte para autorizar o denegar la participación del alumno en la actividad descrita.
                                            </flux:text>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <flux:button variant="primary" icon="check" class="flex-1 py-3" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', true)">
                                                Autorizar Participación
                                            </flux:button>
                                            <flux:button variant="filled" color="red" icon="x-mark" class="flex-1 py-3" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', false)">
                                                No Autorizar
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <flux:text size="sm" class="text-zinc-500 font-medium">Por favor valide la recepción de este comunicado:</flux:text>
                                        <flux:button variant="primary" icon="finger-print" class="w-full sm:w-auto px-10 shadow-lg shadow-blue-500/30" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}')">
                                            Confirmar de Enterado
                                        </flux:button>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-md">
                                            <flux:icon icon="check" variant="micro" />
                                        </div>
                                        <div>
                                            @if($notice->requires_authorization)
                                                <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200">
                                                    Respuesta: {{ $signature->authorized ? 'AUTORIZADO' : 'NO AUTORIZADO' }}
                                                </flux:text>
                                            @else
                                                <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200">ESTADO: ENTERADO</flux:text>
                                            @endif
                                            <flux:text size="xs" class="text-green-700/60 dark:text-green-400/60">Registrado el {{ $signature->signed_at->format('d/m/Y H:i') }}</flux:text>
                                        </div>
                                    </div>
                                    <flux:icon icon="shield-check" class="text-green-200 dark:text-green-800" size="xl" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="py-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600 mb-4">
                        <flux:icon icon="megaphone" size="xl" />
                    </div>
                    <flux:heading size="md" class="text-zinc-400">Sin avisos pendientes</flux:heading>
                    <flux:text variant="subdued">Por el momento no hay comunicados nuevos para sus hijos.</flux:text>
                </div>
            @endforelse
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" class="md:w-160">
        <form wire:submit="saveNotice" class="space-y-6">
            <header>
                <flux:heading size="md">Nuevo Aviso Escolar</flux:heading>
                <flux:text>Cree un comunicado para la comunidad escolar.</flux:text>
            </header>

            <div class="space-y-4">
                <flux:input wire:model="title" label="Título del Aviso" placeholder="Ej: Suspensión por consejo técnico, Festival de primavera..." />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model.live="type" label="Tipo de Aviso">
                        <option value="GENERAL">General</option>
                        <option value="URGENT">Urgente</option>
                        <option value="EVENT">Evento</option>
                    </flux:select>
                    <flux:select wire:model="targetAudience" label="Dirigido a">
                        <option value="ALL">Todo el plantel</option>
                        <option value="PARENTS">Solo Padres</option>
                    </flux:select>
                </div>

                @if($type === 'EVENT')
                    <div class="grid grid-cols-2 gap-4 animate-in fade-in slide-in-from-top-2 duration-300">
                        <flux:input type="date" wire:model="eventDate" label="Fecha del Evento" />
                        <flux:input type="time" wire:model="eventTime" label="Hora" />
                    </div>
                @endif

                <flux:textarea wire:model="content" label="Mensaje" rows="6" placeholder="Escriba el detalle del comunicado aquí..." />

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <flux:checkbox wire:model="requiresAuthorization" label="Requiere Autorización" description="Active esta opción si necesita que el padre de familia otorgue un permiso explícito (Sí/No) además de darse por enterado." />
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Publicar Aviso</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
