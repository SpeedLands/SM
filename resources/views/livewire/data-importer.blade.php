<div class="max-w-4xl mx-auto py-12">
    <flux:heading size="xl" level="1" class="mb-6">Importar Datos</flux:heading>

    <div class="space-y-8">
        <!-- Step Indicator -->
        <div class="flex flex-wrap items-center gap-y-4 sm:gap-4 text-sm">
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
                <div class="flex-1 sm:flex-none {{ $step >= 1 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">1. Cargar Archivo</div>
                <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500 shrink-0" />
                <div class="flex-1 sm:flex-none {{ $step >= 2 ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-zinc-500' }}">2. Configuración</div>
            </div>
            
            <flux:icon.chevron-right class="w-4 h-4 text-gray-400 dark:text-zinc-500 hidden sm:block" />
            
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full">
                        <!-- Primary File -->
                        <div class="space-y-4">
                            <flux:heading size="lg">Archivo Principal</flux:heading>
                            <flux:subheading>Alumnos o Maestros</flux:subheading>
                            
                            <label class="cursor-pointer block">
                                <input type="file" wire:model.live="file" accept=".xlsx, .xls" class="hidden" />
                                <div class="flex items-center justify-center gap-2 px-6 py-3 bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white rounded-xl font-semibold hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors">
                                    <flux:icon.plus class="w-5 h-5" />
                                    <span>{{ $file ? 'Cambiar Archivo' : 'Seleccionar Principal' }}</span>
                                </div>
                            </label>
                            
                            @if ($file && !$errors->has('file'))
                                <div class="text-sm text-green-600 dark:text-green-400 flex items-center justify-center gap-1">
                                    <flux:icon.check-circle class="w-4 h-4" />
                                    <span class="truncate max-w-50">{{ $file->getClientOriginalName() }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Parent File (Optional) -->
                        <div class="space-y-4">
                            <flux:heading size="lg">Archivo de Padres</flux:heading>
                            <flux:subheading>(Opcional, si está en otro archivo)</flux:subheading>
                            
                            <label class="cursor-pointer block">
                                <input type="file" wire:model.live="parentFile" accept=".xlsx, .xls" class="hidden" />
                                <div class="flex items-center justify-center gap-2 px-6 py-3 bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-xl font-semibold hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                    <flux:icon.plus class="w-5 h-5" />
                                    <span>{{ $parentFile ? 'Cambiar Archivo' : 'Seleccionar Padres' }}</span>
                                </div>
                            </label>

                            @if ($parentFile && !$errors->has('parentFile'))
                                <div class="text-sm text-green-600 dark:text-green-400 flex items-center justify-center gap-1">
                                    <flux:icon.check-circle class="w-4 h-4" />
                                    <span class="truncate max-w-50">{{ $parentFile->getClientOriginalName() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="w-full max-w-sm mx-auto pt-4">
                        <div wire:loading wire:target="file, parentFile" class="text-sm text-zinc-500 flex items-center justify-center gap-2">
                             <flux:icon.loading class="w-4 h-4 animate-spin" />
                             Subiendo archivo(s)...
                        </div>
                        @error('file') <div class="mt-4 text-red-500 text-sm font-medium">{{ $message }}</div> @enderror
                        @error('parentFile') <div class="mt-4 text-red-500 text-sm font-medium">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 2: Configuration & Mapping -->
        @if ($step === 2)
            <div class="space-y-8 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
                <div class="space-y-6">
                    <flux:heading size="lg">1. Selección de Hojas</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:select wire:model.live="importType" label="Tipo de Datos Principal">
                            <option value="TEACHERS">Maestros / Admins</option>
                            <option value="PARENTS">Padres de Familia</option>
                            <option value="STUDENTS">Alumnos</option>
                        </flux:select>

                        <flux:select wire:model.live="targetSheetIndex" label="Hoja Principal">
                            <option value="">Selecciona una hoja...</option>
                            @foreach($sheets as $sheet)
                                <option value="{{ $sheet['index'] }}">{{ $sheet['name'] }} ({{ $sheet['rows_count'] }} filas)</option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if($importType === 'STUDENTS')
                         <flux:select wire:model.live="parentSheetIndex" label="Hoja de Padres">
                            <option value="">{{ $parentFile ? 'Seleccionar del archivo de padres...' : 'Opcional: Misma hoja o diferente del archivo principal...' }}</option>
                            @php $pSheets = $parentFile ? $parentFileSheets : $sheets; @endphp
                            @foreach($pSheets as $sheet)
                                @php
                                    $isDisabled = !$parentFile && (string)$sheet['index'] === (string)$targetSheetIndex;
                                @endphp
                                <option value="{{ $sheet['index'] }}" @disabled($isDisabled)>
                                    {{ $sheet['name'] }} ({{ $sheet['rows_count'] }} filas){!! $isDisabled ? ' &mdash; <i>(Ya seleccionada como principal)</i>' : '' !!}
                                </option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>

                <div class="space-y-6 pt-6 border-t dark:border-zinc-800">
                    <flux:heading size="lg">2. Mapeo de Columnas</flux:heading>
                    <flux:subheading>Indica qué columna del Excel corresponde a cada campo del sistema.</flux:subheading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                        @php
                            $currentSheet = collect($sheets)->firstWhere('index', (int) $targetSheetIndex);
                            $headers = $currentSheet['header'] ?? [];
                        @endphp

                        @foreach($this->columnMappings as $mapping)
                            <div class="space-y-2">
                                <flux:label>
                                    {{ $mapping['label'] }}
                                    @if($mapping['required']) <span class="text-red-500">*</span> @endif
                                </flux:label>
                                <flux:select wire:model.live="columnMapping.{{ $mapping['field'] }}">
                                    <option value="">Omitir columna</option>
                                    @foreach($headers as $idx => $label)
                                        <option value="{{ $idx }}">Columna {{ $idx + 1 }}: {{ ($label ?: '(Vacío)') }}</option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @endforeach
                    </div>

                    @if($importType === 'STUDENTS' && $parentSheetIndex !== null && $parentSheetIndex !== '')
                        <div class="mt-8 mb-4 pt-6 border-t dark:border-zinc-800">
                            <flux:heading size="md">Mapeo de Columnas (Hoja de Padres)</flux:heading>
                            <flux:subheading>Indica qué columna del Excel de padres corresponde a cada campo.</flux:subheading>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                            @php
                                $pSource = $parentFile ? $parentFileSheets : $sheets;
                                $pSheet = collect($pSource)->firstWhere('index', (int) $parentSheetIndex);
                                $pHeaders = $pSheet['header'] ?? [];
                            @endphp

                            @foreach($this->parentColumnMappings as $mapping)
                                <div class="space-y-2">
                                    <flux:label>
                                        {{ $mapping['label'] }}
                                        @if($mapping['required']) <span class="text-red-500">*</span> @endif
                                    </flux:label>
                                    <flux:select wire:model.live="parentColumnMapping.{{ $mapping['field'] }}">
                                        <option value="">Omitir columna</option>
                                        @foreach($pHeaders as $idx => $label)
                                            <option value="{{ $idx }}">Columna {{ $idx + 1 }}: {{ ($label ?: '(Vacío)') }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pt-6 border-t dark:border-zinc-800">
                    <div class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/50 rounded-xl">
                        <flux:checkbox wire:model.live="isSimulation" label="Modo Simulación" description="Procesa los datos y genera el reporte sin guardar nada en la base de datos." />
                    </div>
                </div>

                <div class="flex justify-between pt-6">
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
                                                <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $mapping['label'] }}</span>
                                                @if($mapping['required'])
                                                    <flux:badge size="sm" class="bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">Requerido</flux:badge>
                                                @else
                                                    <flux:badge size="sm" variant="subtle">Opcional</flux:badge>
                                                @endif
                                            </div>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Columna {{ ($columnMapping[$mapping['field']] ?? $mapping['index']) + 1 }}
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
                                            @php $mappedIdx = $this->columnMapping[$mapping['field']] ?? ''; @endphp
                                            <div class="max-w-xs truncate" title="{{ is_numeric($mappedIdx) ? ($row[$mappedIdx] ?? '') : '' }}">
                                                {{ is_numeric($mappedIdx) ? ($row[$mappedIdx] ?? '-') : '-' }}
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
                            <span wire:loading.remove wire:target="import">
                                {{ $isSimulation ? 'Ejecutar Simulación' : 'Importar Datos' }}
                            </span>
                            <span wire:loading wire:target="import">
                                {{ $isSimulation ? 'Simulando...' : 'Importando...' }}
                            </span>
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="confirm-import" class="min-w-80">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Confirmar {{ $isSimulation ? 'Simulación' : 'Importación' }}</flux:heading>
                            <flux:subheading class="whitespace-normal wrap-break-word">
                                {{ $isSimulation 
                                    ? 'Se procesarán los datos para generar un reporte sin realizar cambios reales.' 
                                    : '¿Estás seguro de que deseas importar estos datos? Esta acción no se puede deshacer fácilmente.' }}
                            </flux:subheading>
                        </div>

                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancelar</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="import" x-on:click="$flux.modal('confirm-import').close()">
                                {{ $isSimulation ? 'Simular' : 'Importar' }}
                            </flux:button>
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
                    <flux:heading size="xl">{{ $isSimulation ? 'Simulación Completada' : '¡Importación Completada!' }}</flux:heading>
                    <flux:subheading>Resultados de: <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $file ? $file->getClientOriginalName() : 'Archivo' }}@if($parentFile) y {{ $parentFile->getClientOriginalName() }}@endif</span></flux:subheading>
                </div>

                @if($isSimulation)
                    <flux:callout variant="warning" icon="information-circle" title="Este es un reporte de simulación">
                        No se han guardado cambios permanentes en la base de datos.
                    </flux:callout>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto">
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

                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <div class="text-xs font-bold text-zinc-500 uppercase mb-2">Padres / Maestros</div>
                        <div class="flex justify-around items-end">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['summary']['parents']['created'] ?? ($stats['created'] ?? ($stats['summary']['teachers']['created'] ?? 0)) }}</div>
                                <div class="text-[10px] text-zinc-500">Nuevos</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['summary']['parents']['updated'] ?? ($stats['updated'] ?? ($stats['summary']['teachers']['updated'] ?? 0)) }}</div>
                                <div class="text-[10px] text-zinc-500">Act.</div>
                            </div>
                        </div>
                    </div>

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

                <div class="mt-8 text-left space-y-4 max-w-3xl mx-auto">
                    @php
                        $hasNotifications = !empty($stats['notifications']['success']) || 
                                           !empty($stats['notifications']['warnings']) || 
                                           !empty($stats['notifications']['errors']);
                    @endphp

                    @if($hasNotifications)
                        <div class="space-y-6">
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
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($stats['notifications']['warnings']))
                                <div x-data="{ open: true }" class="space-y-3">
                                    <flux:heading size="sm" class="text-amber-600 flex items-center justify-between cursor-pointer" x-on:click="open = !open">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.exclamation-triangle class="w-4 h-4" /> Advertencias
                                        </div>
                                        <flux:icon.chevron-down class="w-3 h-3 transition-transform" x-bind:class="open || '-rotate-90'" />
                                    </flux:heading>
                                    <div x-show="open" x-collapse class="max-h-64 overflow-y-auto space-y-2 pr-2">
                                        @foreach($stats['notifications']['warnings'] as $warning)
                                            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 rounded-lg text-sm">
                                                <div class="font-bold text-amber-700 dark:text-amber-400">{{ $warning['message'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800">
                            <flux:text variant="subtle">No se detectaron anomalías.</flux:text>
                        </div>
                    @endif
                </div>

                <div class="pt-8 border-t dark:border-zinc-800">
                    <flux:button wire:click="resetImport" variant="primary">Importar Otro Archivo</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
