<div class="max-w-4xl mx-auto py-12">
    <flux:heading size="xl" level="1" class="mb-6">Importar Datos</flux:heading>

    <div class="space-y-8">
        <!-- Step Indicator -->
        <div class="flex items-center space-x-4 text-sm">
            <div class="{{ $step >= 1 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">1. Cargar Archivo</div>
            <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500" />
            <div class="{{ $step >= 2 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">2. Configuración</div>
            <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500" />
            <div class="{{ $step >= 3 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">3. Previsualizar</div>
            <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500" />
            <div class="{{ $step >= 4 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">4. Resultados</div>
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
                            <flux:subheading>¿Estás seguro de que deseas importar estos datos al sistema? Esta acción no se puede deshacer fácilmente.</flux:subheading>
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
                <div class="flex justify-center">
                    <flux:icon.check-circle class="w-16 h-16 text-green-500" />
                </div>
                <flux:heading size="xl">¡Importación Completada!</flux:heading>
                
                <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto">
                    <div class="bg-green-50 dark:bg-green-950 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['created'] ?? 0 }}</div>
                        <div class="text-xs text-green-600 dark:text-green-500 uppercase">Creados</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $stats['updated'] ?? 0 }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-500 uppercase">Actualizados</div>
                    </div>
                    @if(($stats['errors'] ?? 0) > 0)
                    <div class="bg-red-50 dark:bg-red-950 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $stats['errors'] ?? 0 }}</div>
                        <div class="text-xs text-red-600 dark:text-red-500 uppercase">Errores</div>
                    </div>
                    @else
                     <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-gray-700 dark:text-zinc-400">0</div>
                        <div class="text-xs text-gray-600 dark:text-zinc-500 uppercase">Errores</div>
                    </div>
                    @endif
                </div>

                @if(!empty($stats['action_items']))
                    <div class="mt-8 text-left space-y-4 max-w-2xl mx-auto">
                        <flux:heading size="lg" class="flex items-center gap-2">
                            <flux:icon.exclamation-triangle class="text-zinc-500" />
                            Acciones Requeridas ({{ count($stats['action_items']) }})
                        </flux:heading>
                        
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                            @foreach($stats['action_items'] as $item)
                                <div class="bg-zinc-50 dark:bg-zinc-800/50 border-l-4 border-zinc-400 dark:border-zinc-600 p-4 rounded-r-lg flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</div>
                                        <div class="text-sm text-zinc-800 dark:text-zinc-400">{{ $item['message'] }}</div>
                                    </div>
                                    @if($item['type'] === 'INCOMPLETE_STUDENT')
                                        <flux:button variant="outline" size="sm" href="{{ route('students.index', ['search' => $item['title']]) }}" target="_blank" class="ml-4 shrink-0">Ver Alumno</flux:button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        
                        <flux:text variant="subtle" class="italic">
                            Nota: Los padres que no se vincularon automáticamente pueden ser asignados manualmente desde el catálogo de alumnos.
                        </flux:text>
                    </div>
                @endif

                <div class="pt-8 border-t dark:border-zinc-800">
                    <flux:button wire:click="resetImport" variant="primary">Importar Otro Archivo</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
