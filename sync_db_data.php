<?php

use App\Models\Student;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$originalFile = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\ORIGINAL.xlsx';

function normalizeName($text) {
    if (!$text) return '';
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
        ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
        $text
    );
    return preg_replace('/\s+/', ' ', $text);
}

// 1. Load ORIGINAL.xlsx and build map
echo "Cargando ORIGINAL.xlsx para sincronización...\n";
$spreadsheet = IOFactory::load($originalFile);
$sheet = $spreadsheet->getActiveSheet();
$originalMap = [];

foreach ($sheet->getRowIterator(2) as $row) {
    $name = $sheet->getCell('A' . $row->getRowIndex())->getValue();
    if ($name) {
        $originalMap[normalizeName($name)] = true;
    }
}

echo "Se encontraron " . count($originalMap) . " alumnos válidos en ORIGINAL.xlsx\n";

// 2. Scan and Delete invalid students
$students = Student::all();
$deletedCount = 0;

echo "Escaneando base de datos de alumnos...\n";
foreach ($students as $student) {
    $normName = normalizeName($student->name);
    
    if (!isset($originalMap[$normName])) {
        echo "Eliminando alumno: {$student->name} (No en Original)\n";
        $student->delete();
        $deletedCount++;
    }
}

echo "Total de alumnos eliminados: $deletedCount\n";

// 3. Cleanup orphan parents
echo "Limpiando padres huérfanos...\n";
$orphanParents = User::where('role', 'PARENT')
    ->whereDoesntHave('students') 
    ->get();

$parentsDeleted = 0;
foreach ($orphanParents as $parent) {
    echo "Eliminando padre huérfano: {$parent->name} ({$parent->email})\n";
    $parent->delete();
    $parentsDeleted++;
}

echo "Total de padres eliminados: $parentsDeleted\n";
echo "Sincronización finalizada con éxito.\n";
