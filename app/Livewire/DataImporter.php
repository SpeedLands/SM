<?php

namespace App\Livewire;

use App\Services\ExcelImportService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataImporter extends Component
{
    use WithFileUploads;

    #[Validate('nullable|file|mimes:xlsx,xls')]
    public $file;

    #[Validate('nullable|file|mimes:xlsx,xls')]
    public $parentFile;

    public function mount(): void
    {
        $this->authorize('admin-only');
    }

    // State
    public $step = 1; // 1: Upload, 2: Configuration, 3: Preview, 4: Result

    public $sheets = [];

    public $parentFileSheets = [];

    // Configuration
    public $importType = 'TEACHERS'; // TEACHERS, PARENTS, STUDENTS

    public $targetSheetIndex = null;

    public $parentSheetIndex = null;

    public $columnMapping = [];

    public $parentColumnMapping = [];

    public $isSimulation = false;

    // Results
    public $stats = [];

    public $previewData = [];

    public function updatedFile()
    {
        $this->validateOnly('file');
        $this->analyze();
    }

    public function updatedParentFile()
    {
        $this->validateOnly('parentFile');
        $this->analyze();
    }

    public function analyze()
    {
        if (! $this->file) {
            return;
        }

        try {
            $service = new ExcelImportService;
            $this->sheets = $service->getSheetsInfo($this->file);

            if ($this->parentFile) {
                $this->parentFileSheets = $service->getSheetsInfo($this->parentFile);
            }

            // Initialize mapping with defaults
            $this->initMapping();

            $this->step = 2;
        } catch (\Exception $e) {
            $this->addError('file', 'Error al analizar el archivo: '.$e->getMessage());
        }
    }

    private function initMapping()
    {
        $mappings = $this->columnMappings;
        $this->columnMapping = [];

        foreach ($mappings as $m) {
            $this->columnMapping[$m['field']] = $m['index'];
        }

        $service = new ExcelImportService;
        $parentMappings = $service->getColumnMappings('PARENTS');
        $this->parentColumnMapping = [];
        foreach ($parentMappings as $m) {
            $this->parentColumnMapping[$m['field']] = $m['index'];
        }
    }

    public function updatePreview()
    {
        if ($this->targetSheetIndex === null || ! is_numeric($this->targetSheetIndex)) {
            return;
        }

        // Find sheet info
        $sheet = collect($this->sheets)->firstWhere('index', (int) $this->targetSheetIndex);
        if ($sheet) {
            $this->previewData = $sheet['preview'] ?? [];
        }
    }

    public function updatedTargetSheetIndex()
    {
        $this->updatePreview();

        if ($this->targetSheetIndex !== null && is_numeric($this->targetSheetIndex)) {
            $sheet = collect($this->sheets)->firstWhere('index', (int) $this->targetSheetIndex);
            if ($sheet && isset($sheet['header'])) {
                $service = app(ExcelImportService::class);
                $this->columnMapping = $service->guessMapping($sheet['header'], $this->importType);
            }
        }
    }

    public function updatedParentSheetIndex()
    {
        if ($this->parentSheetIndex !== null && is_numeric($this->parentSheetIndex)) {
            $parentSource = $this->parentFile ? $this->parentFileSheets : $this->sheets;
            $sheet = collect($parentSource)->firstWhere('index', (int) $this->parentSheetIndex);
            if ($sheet && isset($sheet['header'])) {
                $service = app(ExcelImportService::class);
                $this->parentColumnMapping = $service->guessMapping($sheet['header'], 'PARENTS');
            }
        }
    }

    public function updatedImportType()
    {
        if ($this->targetSheetIndex !== null && is_numeric($this->targetSheetIndex)) {
            $this->updatedTargetSheetIndex();
        }
    }

    public function getColumnMappingsProperty()
    {
        $service = new ExcelImportService;

        return $service->getColumnMappings($this->importType);
    }

    public function getParentColumnMappingsProperty()
    {
        $service = new ExcelImportService;

        return $service->getColumnMappings('PARENTS');
    }

    public function import()
    {
        $this->validate([
            'targetSheetIndex' => 'required|integer',
            'importType' => 'required|string',
            'parentSheetIndex' => 'nullable|integer|different:targetSheetIndex',
        ]);

        // Timeout Prevention: Limit row counts based on type
        $limit = ($this->importType === 'PARENTS') ? 300 : 150;
        $mainSheetInfo = collect($this->sheets)->firstWhere('index', (int) $this->targetSheetIndex);
        if ($mainSheetInfo && $mainSheetInfo['rows_count'] > ($limit + 1)) {
            $this->addError('import', "El archivo principal tiene {$mainSheetInfo['rows_count']} filas. El límite para {$this->importType} es de {$limit} registros por subida para evitar que el servidor se sature.");

            return;
        }

        if ($this->parentSheetIndex !== null && $this->parentSheetIndex !== '') {
            $parentSource = $this->parentFile ? $this->parentFileSheets : $this->sheets;
            $parentSheetInfo = collect($parentSource)->firstWhere('index', (int) $this->parentSheetIndex);
            if ($parentSheetInfo && $parentSheetInfo['rows_count'] > 301) { // 300 data rows + 1 header
                $this->addError('import', "El archivo de padres tiene {$parentSheetInfo['rows_count']} filas. El límite es de 300 registros para evitar que la página marque error de tiempo de espera.");

                return;
            }
        }

        try {
            $service = new ExcelImportService;
            $this->stats = $service->import(
                file: $this->file,
                type: $this->importType,
                sheetIndex: (int) $this->targetSheetIndex,
                parentSheetIndex: (is_numeric($this->parentSheetIndex) && $this->parentSheetIndex !== '') ? (int) $this->parentSheetIndex : null,
                columnMapping: $this->columnMapping,
                parentFile: $this->parentFile,
                dryRun: $this->isSimulation,
                parentColumnMapping: $this->parentColumnMapping
            );

            $this->step = 4;
            $this->dispatch('import-finished');

        } catch (\Exception $e) {
            $this->addError('import', 'La importación falló: '.$e->getMessage());
        }
    }

    public function resetImport()
    {
        $this->reset(['file', 'parentFile', 'step', 'sheets', 'parentFileSheets', 'importType', 'targetSheetIndex', 'parentSheetIndex', 'stats', 'previewData', 'isSimulation']);
    }

    public function render()
    {
        return view('livewire.data-importer');
    }
}
