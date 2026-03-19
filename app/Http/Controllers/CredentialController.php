<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Student;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CredentialController extends Controller
{
    public function show(Student $student): Response
    {
        return $this->generatePdf([$student]);
    }

    public function bulk(Request $request): Response
    {
        $ids = $request->input('ids', []);
        $students = Student::whereIn('id', $ids)->get();

        if ($students->isEmpty()) {
            abort(404, 'No se seleccionaron alumnos.');
        }

        return $this->generatePdf($students, $request->input('scale', 1.0));
    }

    protected function generatePdf($students, $scale = 1.0): Response
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $cycleName = $activeCycle ? $activeCycle->name : '2025 - 2026';

        // Static global assets encoded once
        $fondoPath = public_path('images/credentials/fondo.jpg');
        $escudoPath = public_path('images/credentials/escudog5.png');

        $fondoBase64 = file_exists($fondoPath)
            ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($fondoPath))
            : null;

        $escudoBase64 = file_exists($escudoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($escudoPath))
            : null;

        $studentsData = [];

        // Local QR settings
        $renderer = new ImageRenderer(
            new RendererStyle(200, 0),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        foreach ($students as $student) {
            $photoBase64 = null;
            if ($student->photo_path && file_exists(storage_path('app/public/'.$student->photo_path))) {
                $photoPath = storage_path('app/public/'.$student->photo_path);
                $photoBase64 = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($photoPath));
            }

            $qrCodeSvg = $writer->writeString($student->curp);
            $qrCodeBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);

            $studentsData[] = [
                'student' => $student,
                'photoBase64' => $photoBase64,
                'qrCodeBase64' => $qrCodeBase64,
            ];
        }

        $pdf = Pdf::loadView('students.credential-pdf', [
            'studentsData' => $studentsData,
            'cycleName' => $cycleName,
            'fondoBase64' => $fondoBase64,
            'escudoBase64' => $escudoBase64,
            'scale' => $scale,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('credenciales.pdf');
    }
}
