<?php

use App\Models\Regulation;
use Livewire\Volt\Component;

new class extends Component {
    public string $title = '';
    public string $content = '';
    public bool $isEditing = false;
    public ?Regulation $regulation = null;

    public function mount(): void
    {
        $this->regulation = Regulation::find(1);
        
        if ($this->regulation) {
            $this->title = $this->regulation->title;
            $this->content = $this->regulation->content;
        } else {
            $this->title = 'Reglamento Escolar';
            $this->content = '<p>El reglamento aún no ha sido redactado.</p>';
        }
    }

    public function toggleEdit(): void
    {
        if (!auth()->user()->isAdmin() || !auth()->user()->isViewStaff()) {
            return;
        }
        $this->isEditing = !$this->isEditing;
    }

    public function save(): void
    {
        if (!auth()->user()->isAdmin() || !auth()->user()->isViewStaff()) {
            return;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($this->regulation) {
            $this->regulation->update([
                'title' => $this->title,
                'content' => $this->content,
                'last_updated' => now(),
            ]);
        } else {
            $this->regulation = Regulation::create([
                'id' => 1,
                'title' => $this->title,
                'content' => $this->content,
                'last_updated' => now(),
            ]);
        }

        $this->isEditing = false;
        $this->dispatch('regulation-saved');
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <link rel="stylesheet" href="{{ asset('/css/quill.snow.css') }}">
    
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Reglamento Escolar</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Consulta las normas y estatutos de convivencia de la institución.</flux:text>
        </div>
        
        @if(auth()->user()->isAdmin() && auth()->user()->isViewStaff())
            <flux:button variant="primary" icon="{{ $isEditing ? 'x-mark' : 'pencil' }}" wire:click="toggleEdit">
                {{ $isEditing ? 'Cancelar Edición' : 'Editar Reglamento' }}
            </flux:button>
        @endif
    </div>

    <div class="p-8 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
        @if($isEditing)
            <form wire:submit="save" class="space-y-6">
                <flux:input label="Título del Reglamento" wire:model="title" />

                <div class="space-y-4">
                    <flux:label>Contenido del Reglamento</flux:label>
                    
                    <div 
                        class="quill-editor-container bg-white dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden"
                        x-data="{
                            content: @entangle('content'),
                            quill: null,
                            init() {
                                this.quill = new Quill($refs.editor, {
                                    theme: 'snow',
                                    modules: {
                                        toolbar: [
                                            ['bold', 'italic', 'underline', 'strike'],
                                            [{ 'header': 1 }, { 'header': 2 }],
                                            ['blockquote'],
                                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                            [{ 'align': [] }],
                                            ['clean']
                                        ]
                                    }
                                });

                                // Set initial content
                                this.quill.root.innerHTML = this.content;

                                // Sync content to Livewire
                                this.quill.on('text-change', () => {
                                    this.content = this.quill.root.innerHTML;
                                });

                                // Sync content from Livewire (if changed externally)
                                $watch('content', value => {
                                    if (value !== this.quill.root.innerHTML) {
                                        this.quill.root.innerHTML = value;
                                    }
                                });
                            }
                        }"
                        wire:ignore
                    >
                        <div x-ref="editor" class="min-h-125 text-lg"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="toggleEdit">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar Cambios</flux:button>
                </div>
            </form>
        @else
            <div class="prose-custom max-w-none dark:text-zinc-300">
                <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-4 text-center uppercase tracking-wide">
                    {{ $title }}
                </h2>
                
                <div class="regulation-html-content ql-editor p-0!">
                    {!! $content !!}
                </div>

                <div class="mt-12 pt-6 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500 italic">
                    <div>Última actualización: {{ $regulation?->last_updated?->format('d/m/Y H:i') ?? 'N/A' }}</div>
                    <div>Institución Educativa Técnica</div>
                </div>
            </div>
        @endif
    </div>

    <style>
        /* Modern Typography for Regulations Viewer */
        .regulation-html-content {
            @apply text-zinc-700 dark:text-zinc-300 leading-relaxed;
        }
        .regulation-html-content h1 { @apply text-4xl font-extrabold mt-12 mb-6 text-zinc-900 dark:text-white text-center !important; }
        .regulation-html-content h2 { @apply text-2xl font-bold mt-10 mb-4 text-zinc-900 dark:text-white text-center !important; }
        .regulation-html-content h3 { @apply text-xl font-semibold mt-8 mb-4 text-zinc-800 dark:text-zinc-200 border-b border-zinc-100 dark:border-zinc-800 pb-2 !important; }
        .regulation-html-content p { @apply my-4 text-justify !important; }
        .regulation-html-content ul { @apply list-disc list-outside my-6 space-y-3 ml-6 !important; }
        .regulation-html-content ol { @apply list-decimal list-outside my-6 space-y-3 ml-6 !important; }
        .regulation-html-content li { @apply pl-2 !important; }
        .regulation-html-content strong { @apply font-bold text-zinc-900 dark:text-zinc-100 !important; }
        
        /* Quill Dark Mode Support */
        .dark .ql-toolbar.ql-snow {
            @apply bg-zinc-900 border-zinc-700;
        }
        .dark .ql-container.ql-snow {
            @apply border-zinc-700 bg-zinc-900/50;
        }
        .dark .ql-editor {
            @apply text-zinc-200;
        }
        .dark .ql-snow .ql-stroke {
            stroke: #d4d4d8;
        }
        .dark .ql-snow .ql-fill {
            fill: #d4d4d8;
        }
        .dark .ql-snow .ql-picker {
            color: #d4d4d8;
        }
        
        /* Active Buttons in Dark Mode: White bg, Black icon */
        .dark .ql-snow.ql-toolbar button.ql-active,
        .dark .ql-snow.ql-toolbar .ql-picker-label.ql-active {
            @apply bg-white !important;
        }
        .dark .ql-snow.ql-toolbar button.ql-active .ql-stroke {
            stroke: #000 !important;
        }
        .dark .ql-snow.ql-toolbar button.ql-active .ql-fill {
            fill: #000 !important;
        }

        /* Fix List Alignment in Editor */
        .ql-editor ol, .ql-editor ul {
            padding-left: 1.5em !important;
        }
        
        /* Fix for justified and centered text */
        .ql-align-justify { text-align: justify !important; }
        .ql-align-center { text-align: center !important; }
        .ql-align-right { text-align: right !important; }
    </style>

    <script src="{{ asset('js/quill.js') }}"></script>
</div>
