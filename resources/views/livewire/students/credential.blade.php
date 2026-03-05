<?php

use App\Models\Student;
use App\Models\Cycle;
use Livewire\Volt\Component;


new class extends Component {
    public Student $student;

    public function mount(Student $student)
    {
        $this->student = $student->load(['currentCycleAssociation.cycle']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        
        return [
            'cycleName' => $activeCycle ? $activeCycle->name : '2025 - 2026',
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($this->student->curp) . '&format=png',
        ];
    }
}; ?>

<div id="credential-root" class="m-0 p-0 flex flex-col justify-center items-center min-h-screen bg-zinc-50 dark:bg-zinc-900 border-none">
    <style>
        @media print {
            .no-print { display: none !important; }
            #credential-root { background: white !important; padding: 0 !important; }
        }
        .contenedor {
            display: flex;
            flex-direction: row;
            gap: 20px;
            justify-content: center;
            padding: 20px;
        }
        .credencial {
            width: 6.68cm;
            height: 9.85cm;
            border-radius: 12px;
            padding: 10px;
            text-align: center;
            position: relative;
            background-image: url("{{ asset('images/credentials/fondo.jpg') }}") !important;
            background-repeat: no-repeat !important;
            background-position: center center !important;
            background-size: 100% 100% !important;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden;
            border: 1px solid #000;
        }

        /* Foto */
        .foto {
            width: 2.8cm;
            height: 3.5cm;
            object-fit: cover;
            border: 2px solid #000;
            margin: 15px auto 5px auto;
            background-color: #eee;
        }

        /* QR */
        .qr {
            width: 2.4cm;
            height: 2.4cm;
            margin: 5px auto;
            display: block;
        }

        /* Logo inferior */
        .logo {
            width: 1.5cm;
            height: 1.5cm;
            position: absolute;
            bottom: 5px;
            left: 5px;
        }

        /* Textos */
        .nombre {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            line-height: 1.2;
            margin-top: 2px;
        }
        .curp {
            font-size: 11px;
            color: #333;
            font-family: monospace;
        }
        .tabla-escuela {
            width: 100%;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .tabla-escuela td {
            padding: 0;
            line-height: 1.2;
        }

        /* Reverso */
        .reverso {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 20px 10px;
        }
        .reverso-content {
            margin-top: 2cm;
        }
        .linea-firma {
            margin-top: 20px;
            border-top: 1px solid #000;
            width: 80%;
            text-align: center;
            font-size: 10px;
            padding-top: 5px;
        }
        .no-print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }
    </style>

    <div class="no-print-btn no-print flex gap-2">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm transition-colors cursor-pointer font-medium">Imprimir Credencial</button>
        <button onclick="window.close()" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-lg shadow-sm transition-colors cursor-pointer font-medium">Cerrar</button>
    </div>

    <div class="contenedor">
        <!-- Frente -->
        <div class="credencial">
            <table class="tabla-escuela text-center" style="margin-top: 10px;">
                <tr><td><strong>ESCUELA SECUNDARIA GENERAL No. 5</strong></td></tr>
                <tr><td>DR ROGELIO MONTEMAYOR SEGUY</td></tr>
                <tr><td>Tel: (+52) 878-112-0282</td></tr>
            </table>

            <div>
                @if($student->photo_url)
                    <img src="{{ $student->photo_url }}" class="foto">
                @else
                    <div class="foto flex items-center justify-center text-zinc-500" style="font-size: 10px;">Sin Foto</div>
                @endif
                
                <table class="tabla-escuela text-center">
                    <tr><td>Ciclo Escolar: {{ $cycleName }}</td></tr>
                </table>
                <div class="nombre uppercase">{{ $student->name }}</div>
                <div class="curp">{{ $student->curp }}</div>
                <div class="font-bold underline tracking-tight">{{ $student->grade }} - {{ $student->group_name }}</div>
                <div class="text-[10px] uppercase font-medium">{{ $student->turn }}</div>
            </div>

            <img src="{{ $qrUrl }}" class="qr">
            <img src="{{ asset('images/credentials/escudog5.png') }}" class="logo" alt="Logo Escuela">
        </div>

        <!-- Reverso -->
        <div class="credencial reverso">
            <div class="text-center w-full">
                <p class="m-0"><strong>ESCUELA SECUNDARIA GENERAL No. 5</strong></p>
                <p class="m-0 text-[10px]">DR ROGELIO MONTEMAYOR SEGUY</p>
            </div>

            <div class="reverso-content text-center">
                <p class="mb-1">Ciclo Escolar: {{ $cycleName }}</p>
                <p class="px-2 text-[10px]"><em>Esta credencial es personal e intransferible. Identificación oficial de la institución.</em></p>
            </div>
            
            <div class="linea-firma">
                PROF. NESTOR DAMIAN RIQUEJO JORDAN<br>
                <small class="text-[9px]">Director</small>
            </div>
        </div>
    </div>
</div>

