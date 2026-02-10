<?php

namespace App\Livewire;

use App\Services\ExcelImportService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataImporter extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls')]
    public $file;

    public function mount(): void
    {
        $this->authorize('admin-only');
    }

    // State
    public $step = 1; // 1: Upload, 2: Configuration, 3: Preview, 4: Result

    public $sheets = [];

    // Configuration
    public $importType = 'TEACHERS'; // TEACHERS, PARENTS, STUDENTS

    public $targetSheetIndex = null;

    public $parentSheetIndex = null;

    // Results
    public $stats = [];

    public $previewData = [];

    public function updatedFile()
    {
        $this->validate();
        $this->analyze();
    }

    public function analyze()
    {
        try {
            $service = new ExcelImportService;
            $this->sheets = $service->getSheetsInfo($this->file);
            $this->step = 2;
        } catch (\Exception $e) {
            $this->addError('file', 'Error al analizar el archivo: '.$e->getMessage());
        }
    }

    public function updatePreview()
    {
        if ($this->targetSheetIndex === null) {
            return;
        }

        // Find sheet info
        $sheet = collect($this->sheets)->firstWhere('index', (int) $this->targetSheetIndex);
        $this->previewData = $sheet['preview'] ?? [];
    }

    public function updatedTargetSheetIndex()
    {
        $this->updatePreview();
    }

    public function getColumnMappingsProperty()
    {
        $service = new ExcelImportService;

        return $service->getColumnMappings($this->importType);
    }

    public function import()
    {
        $this->validate([
            'targetSheetIndex' => 'required|integer',
            'importType' => 'required|string',
            'parentSheetIndex' => 'nullable|integer|different:targetSheetIndex',
        ]);

        try {
            $service = new ExcelImportService;
            $this->stats = $service->import(
                $this->file,
                $this->importType,
                (int) $this->targetSheetIndex,
                $this->parentSheetIndex !== '' ? (int) $this->parentSheetIndex : null
            );

            $this->step = 4;
            $this->dispatch('import-finished');

        } catch (\Exception $e) {
            $this->addError('import', 'La importación falló: '.$e->getMessage());
        }
    }

    public function resetImport()
    {
        $this->reset(['file', 'step', 'sheets', 'importType', 'targetSheetIndex', 'parentSheetIndex', 'stats', 'previewData']);
    }

    public function render()
    {
        return view('livewire.data-importer');
    }
}
