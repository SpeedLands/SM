<div class="max-w-4xl mx-auto py-12">
    <flux:heading size="xl" level="1" class="mb-6">Importar Datos</flux:heading>

    <div class="space-y-8">
        <!-- Step Indicator -->
        <div class="flex flex-wrap items-center gap-y-4 sm:gap-4 text-sm">
            <!-- Row 1: Steps 1-2 -->
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
                <div class="flex-1 sm:flex-none {{ $step >= 1 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">1. Cargar Archivo</div>
                <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500 shrink-0" />
                <div class="flex-1 sm:flex-none {{ $step >= 2 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">2. Configuración</div>
            </div>
            
            <!-- Separator chevron (hidden on mobile) -->
            <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500 hidden sm:block" />
            
            <!-- Row 2: Steps 3-4 -->
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
                <div class="flex-1 sm:flex-none {{ $step >= 3 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">3. Previsualizar</div>
                <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500 shrink-0" />
                <div class="flex-1 sm:flex-none {{ $step >= 4 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">4. Resultados</div>
            </div>
        </div>

        <!-- Step 1: Upload -->
        @if ($step === 1)
        <div class="space-y-6 bg-white dark:bg-zinc-900 p-8 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 shadow-sm text-center">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-full">
                        <flux:icon.document-arrow-up class="w-12 h-12 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    
                    <div>
                        <flux:heading size="lg">Selecciona el archivo Excel</flux:heading>
                        <flux:subheading>Formatos permitidos: .xlsx, .xls</flux:subheading>
                    </div>

                    <div class="w-full max-w-sm mx-auto">
                        <label class="cursor-pointer">
                            <input type="file" wire:model.live="file" accept=".xlsx, .xls" class="hidden" />
                            <div class="flex items-center justify-center gap-2 px-6 py-3 bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white rounded-xl font-semibold hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors">
                                <flux:icon.plus class="w-5 h-5" />
                                <span>Seleccionar Archivo</span>
                            </div>
                        </label>
                        
                        <div wire:loading wire:target="file" class="mt-4 text-sm text-zinc-500 flex items-center justify-center gap-2">
                             <flux:icon.loading class="w-4 h-4 animate-spin" />
                             Subiendo archivo...
                        </div>

                        <div wire:loading wire:target="analyze" class="mt-4 text-sm text-zinc-500 flex items-center justify-center gap-2">
                             <flux:icon.loading class="w-4 h-4 animate-spin" />
                             Analizando datos...
                        </div>

                        @if ($file && !$errors->has('file'))
                            <div class="mt-4 flex items-center justify-center gap-2 text-green-600 dark:text-green-400 font-medium">
                                <flux:icon.check-circle class="w-5 h-5" />
                                <span>{{ $file->getClientOriginalName() }}</span>
                            </div>
                        @endif

                        @error('file') 
                            <div class="mt-4 text-red-500 text-sm font-medium">
                                {{ $message === 'The file field is required.' ? 'El archivo es obligatorio.' : ($message === 'The file failed to upload.' ? 'Error al subir.' : 'El archivo debe ser .xlsx o .xls') }}
                            </div> 
                        @enderror
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 2: Configuration -->
        @if ($step === 2)
        <div class="space-y-6 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
                <flux:heading size="lg">Configuración de Importación</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:select wire:model.live="importType" label="Tipo de Datos">
                        <option value="TEACHERS">Maestros / Admins</option>
                        <option value="PARENTS">Padres de Familia</option>
                        <option value="STUDENTS">Alumnos</option>
                    </flux:select>

                    <flux:select wire:model.live="targetSheetIndex" label="Hoja de Excel a Importar">
                        <option value="">Selecciona una hoja...</option>
                        @foreach($sheets as $sheet)
                            <option value="{{ $sheet['index'] }}">{{ $sheet['name'] }} ({{ $sheet['rows_count'] }} filas)</option>
                        @endforeach
                    </flux:select>

                    @if($importType === 'STUDENTS')
                         <flux:select wire:model.live="parentSheetIndex" label="Hoja de Padres (Opcional)">
                            <option value="">Saltar / Importar Manualmente</option>
                            @foreach($sheets as $sheet)
                                <option value="{{ $sheet['index'] }}">{{ $sheet['name'] }} ({{ $sheet['rows_count'] }} filas)</option>
                            @endforeach
                        </flux:select>
                        <flux:text class="text-sm text-gray-500 mt-2">
                            Si seleccionas una hoja de padres, el sistema intentará vincularlos automáticamente buscando "Padre de [Nombre Alumno]".
                        </flux:text>
                    @endif
                </div>

                <div class="flex justify-between">
                    <flux:button wire:click="$set('step', 1)" variant="subtle">Atrás</flux:button>
                    <flux:button wire:click="$set('step', 3)" variant="primary" :disabled="$targetSheetIndex === null || $targetSheetIndex === ''">
                        Continuar a Previsualización
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Step 3: Preview -->
        @if ($step === 3)
        <div class="space-y-6 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
                <flux:heading size="lg">Previsualización de Datos</flux:heading>
                <flux:subheading>Revisa los primeros registros. Asegúrate de haber seleccionado la hoja correcta.</flux:subheading>

                <div class="overflow-x-auto border dark:border-zinc-800 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                @foreach($this->columnMappings as $mapping)
                                    <th class="px-6 py-3 text-left">
                                        <div class="flex flex-col gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                    {{ $mapping['label'] }}
                                                </span>
                                                @if($mapping['required'])
                                                    <flux:badge size="sm" class="bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">Requerido</flux:badge>
                                                @else
                                                    <flux:badge size="sm" variant="subtle">Opcional</flux:badge>
                                                @endif
                                            </div>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Columna {{ $mapping['index'] + 1 }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                            @foreach($previewData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                                    @foreach($this->columnMappings as $mapping)
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-zinc-300">
                                            <div class="max-w-xs truncate" title="{{ $row[$mapping['index']] ?? '' }}">
                                                {{ $row[$mapping['index']] ?? '-' }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center">
                    <flux:button wire:click="$set('step', 2)" variant="subtle">Atrás</flux:button>
                    
                    <flux:modal.trigger name="confirm-import">
                        <flux:button variant="primary">
                            <span wire:loading.remove wire:target="import">Importar Datos</span>
                            <span wire:loading wire:target="import">Importando...</span>
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="confirm-import" class="min-w-80">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Confirmar Importación</flux:heading>
                            <flux:subheading class="whitespace-normal wrap-break-word">¿Estás seguro de que deseas importar estos datos al sistema? Esta acción no se puede deshacer fácilmente.</flux:subheading>
                        </div>

                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancelar</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="import" x-on:click="$flux.modal('confirm-import').close()">Importar</flux:button>
                        </div>
                    </div>
                </flux:modal>
                
                 @error('import') 
                    <flux:callout variant="danger" icon="exclamation-circle" title="Error de Importación">
                        {{ $message }}
                    </flux:callout>
                @enderror
            </div>
        @endif

        <!-- Step 4: Results -->
        @if ($step === 4)
        <div class="space-y-6 text-center bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
                <div class="space-y-4">
                    <flux:heading size="xl">¡Importación Completada!</flux:heading>
                    <flux:subheading>Resultados de la hoja: <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['details']['sheet_name'] ?? 'N/A' }}</span></flux:subheading>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
                    <!-- Students Stats -->
                    @if(isset($stats['summary']['students']) && $stats['summary']['students']['total'] > 0)
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                            <div class="text-xs font-bold text-zinc-500 uppercase mb-2">Alumnos</div>
                            <div class="flex justify-around items-end">
                                <div>
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['summary']['students']['created'] }}</div>
                                    <div class="text-[10px] text-zinc-500">Nuevos</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['summary']['students']['updated'] }}</div>
                                    <div class="text-[10px] text-zinc-500">Act.</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Parents Stats -->
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <div class="text-xs font-bold text-zinc-500 uppercase mb-2">Padres</div>
                        <div class="flex justify-around items-end">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['summary']['parents']['created'] ?? ($stats['created'] ?? 0) }}</div>
                                <div class="text-[10px] text-zinc-500">Nuevos</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['summary']['parents']['updated'] ?? ($stats['updated'] ?? 0) }}</div>
                                <div class="text-[10px] text-zinc-500">Act.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Links Stats -->
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <div class="text-xs font-bold text-zinc-500 uppercase mb-2">Vínculos</div>
                        <div class="flex justify-around items-end">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['summary']['links']['successful'] ?? 0 }}</div>
                                <div class="text-[10px] text-zinc-500">Éxito</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['summary']['links']['failed'] ?? 0 }}</div>
                                <div class="text-[10px] text-zinc-500">Error</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Accordion -->
                <div class="mt-8 text-left space-y-4 max-w-3xl mx-auto">
                    @php
                        $hasNotifications = !empty($stats['notifications']['success']) || 
                                           !empty($stats['notifications']['warnings']) || 
                                           !empty($stats['notifications']['errors']);
                    @endphp

                    @if($hasNotifications)
                        <div class="space-y-6">
                            <!-- Errores Críticos -->
                            @if(!empty($stats['notifications']['errors']))
                                <div x-data="{ open: true }" class="space-y-3">
                                    <flux:heading size="sm" class="text-red-600 flex items-center justify-between cursor-pointer" x-on:click="open = !open">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.x-circle class="w-4 h-4" /> Errores Críticos
                                        </div>
                                        <flux:icon.chevron-down class="w-3 h-3 transition-transform" x-bind:class="open || '-rotate-90'" />
                                    </flux:heading>
                                    
                                    <div x-show="open" x-collapse class="space-y-2">
                                        @foreach($stats['notifications']['errors'] as $error)
                                            <div class="p-3 bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/50 rounded-lg text-sm">
                                                <div class="font-bold text-red-700 dark:text-red-400">{{ $error['message'] }}</div>
                                                @if(isset($error['row'])) <div class="text-xs text-red-600 dark:text-red-500 mt-1">Fila: {{ $error['row'] }} | Valor: {{ $error['value'] ?? 'N/A' }}</div> @endif
                                                <div class="text-xs text-red-600 dark:text-red-500 mt-1 italic">Acción: {{ $error['action'] ?? 'Fila omitida' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Advertencias -->
                            @if(!empty($stats['notifications']['warnings']))
                                <div x-data="{ open: true }" class="space-y-3">
                                    <flux:heading size="sm" class="text-amber-600 flex items-center justify-between cursor-pointer" x-on:click="open = !open">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.exclamation-triangle class="w-4 h-4" /> Advertencias y Pendientes
                                        </div>
                                        <flux:icon.chevron-down class="w-3 h-3 transition-transform" x-bind:class="open || '-rotate-90'" />
                                    </flux:heading>

                                    <div x-show="open" x-collapse class="max-h-64 overflow-y-auto space-y-2 pr-2">
                                        @foreach($stats['notifications']['warnings'] as $warning)
                                            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 rounded-lg text-sm">
                                                <div class="font-bold text-amber-700 dark:text-amber-400">{{ $warning['message'] }}</div>
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-[11px] text-amber-600 dark:text-amber-500">
                                                    @if(isset($warning['row'])) <span>Fila: {{ $warning['row'] }}</span> @endif
                                                    @if(isset($warning['student_searched'])) <span>Buscado: "{{ $warning['student_searched'] }}"</span> @endif
                                                    @if(isset($warning['suggestion'])) <span class="italic">Sugerencia: {{ $warning['suggestion'] }}</span> @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Éxitos / Casos Especiales -->
                            @if(!empty($stats['notifications']['success']))
                                <div x-data="{ open: true }" class="space-y-3">
                                    <flux:heading size="sm" class="text-green-600 flex items-center justify-between cursor-pointer" x-on:click="open = !open">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.check-circle class="w-4 h-4" /> Casos Identificados Automáticamente
                                        </div>
                                        <flux:icon.chevron-down class="w-3 h-3 transition-transform" x-bind:class="open || '-rotate-90'" />
                                    </flux:heading>

                                    <div x-show="open" x-collapse class="max-h-96 overflow-y-auto space-y-2 pr-2">
                                        @foreach($stats['notifications']['success'] as $success)
                                            <div class="p-3 bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/50 rounded-lg text-sm">
                                                @if($success['type'] === 'multiple_children')
                                                    <div class="font-bold text-green-700 dark:text-green-400 flex items-center gap-2">
                                                        <flux:icon.users class="w-4 h-4" /> Multi-hijos: {{ $success['parent_name'] }}
                                                    </div>
                                                    <div class="text-[11px] text-green-600 dark:text-green-500 mt-1">
                                                        Hijos: {{ implode(', ', $success['children']) }} (Filas: {{ implode(', ', $success['rows_processed']) }})
                                                    </div>
                                                @elseif($success['type'] === 'fuzzy_match')
                                                    <div class="font-bold text-green-700 dark:text-green-400 flex items-center gap-2">
                                                        <flux:icon.sparkles class="w-4 h-4" /> Ajuste automático ({{ $success['similarity'] }}%)
                                                    </div>
                                                    <div class="text-[11px] text-green-600 dark:text-green-500 mt-1">
                                                        Se buscó "{{ $success['student_searched'] }}" y se vinculó con "{{ $success['student_found'] }}"
                                                    </div>
                                                @elseif($success['type'] === 'staff_parent')
                                                    <div class="font-bold text-green-700 dark:text-green-400 flex items-center gap-2">
                                                        <flux:icon.identification class="w-4 h-4" /> Personal/Admin identificado
                                                    </div>
                                                    <div class="text-[11px] text-green-600 dark:text-green-500 mt-1">
                                                        <strong>{{ $success['user_name'] }}</strong> ({{ $success['user_email'] }}) tiene el rol <strong>{{ $success['user_role'] }}</strong>.
                                                        Sus hijos vinculados en esta sesión: {{ implode(', ', $success['children'] ?? []) }}.
                                                    </div>
                                                @else
                                                    <div class="font-bold text-green-700 dark:text-green-400">{{ $success['message'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800">
                            <flux:text variant="subtle">Importación limpia. No se detectaron anomalías.</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Action items from old structure (backwards compatibility for Teachers) --}}
                @if(!empty($stats['action_items']))
                    <div x-data="{ open: false }" class="mt-8 text-left space-y-4 max-w-2xl mx-auto border dark:border-zinc-800 rounded-xl overflow-hidden">
                        <flux:heading 
                            size="lg" 
                            class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800/50 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                            x-on:click="open = !open"
                        >
                            <div class="flex items-center gap-2">
                                <flux:icon.exclamation-triangle class="text-zinc-500" />
                                Acciones de Seguimiento ({{ count($stats['action_items']) }})
                            </div>
                            <flux:icon.chevron-down class="w-4 h-4 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
                        </flux:heading>
                        
                        <div x-show="open" x-collapse>
                            <div class="p-4 space-y-2 border-t dark:border-zinc-800 max-h-96 overflow-y-auto">
                                @foreach($stats['action_items'] as $item)
                                    <div class="bg-zinc-50 dark:bg-zinc-800/50 border-l-4 border-zinc-400 dark:border-zinc-600 p-3 rounded-r-lg flex justify-between items-start">
                                        <div class="text-sm">
                                            <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</div>
                                            <div class="text-zinc-800 dark:text-zinc-400">{{ $item['message'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="pt-8 border-t dark:border-zinc-800">
                    <flux:button wire:click="resetImport" variant="primary">Importar Otro Archivo</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
