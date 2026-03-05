<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credencial - {{ $student->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; margin: 0; padding: 0; }
            .contenedor { margin-top: 0 !important; }
        }
        .page-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .contenedor {
            display: flex;
            flex-direction: row;
            gap: 20px;
            justify-content: center;
        }
        .credencial {
            width: 6.68cm;
            height: 9.85cm;
            border-radius: 12px;
            padding: 10px;
            text-align: center;
            position: relative;
            background-image: url("{{ asset('images/credentials/fondo.jpg') }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 100% 100%;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden;
            border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .foto {
            width: 2.8cm;
            height: 3.5cm;
            object-fit: cover;
            border: 2px solid #000;
            margin: 15px auto 5px auto;
            background-color: #eee;
            display: block;
        }
        .foto-placeholder {
            width: 2.8cm;
            height: 3.5cm;
            border: 2px solid #000;
            margin: 15px auto 5px auto;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }
        .qr {
            width: 2.4cm;
            height: 2.4cm;
            margin: 5px auto;
            display: block;
        }
        .logo {
            width: 1.5cm;
            height: 1.5cm;
            position: absolute;
            bottom: 5px;
            left: 5px;
        }
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
    </style>
</head>
<body class="antialiased font-sans">
    <div class="page-wrapper">
        <div class="flex gap-4 mb-8 no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition-colors flex items-center gap-2 font-medium cursor-pointer">
                <span>🖨️</span> Imprimir Credencial
            </button>
            <button onclick="window.close()" class="bg-zinc-600 hover:bg-zinc-700 text-white px-6 py-2 rounded-lg shadow-md transition-colors flex items-center gap-2 font-medium cursor-pointer">
                <span>✕</span> Cerrar
            </button>
        </div>

        <div class="contenedor">
            {{-- Frente --}}
            <div class="credencial">
                <table class="tabla-escuela text-center mt-2.5">
                    <tr><td><strong>ESCUELA SECUNDARIA GENERAL No. 5</strong></td></tr>
                    <tr><td>DR ROGELIO MONTEMAYOR SEGUY</td></tr>
                    <tr><td>Tel: (+52) 878-112-0282</td></tr>
                </table>

                @if($student->photo_url)
                    <img src="{{ $student->photo_url }}" class="foto" alt="Foto del alumno">
                @else
                    <div class="foto-placeholder">Sin Foto</div>
                @endif

                <table class="tabla-escuela text-center">
                    <tr><td>Ciclo Escolar: {{ $cycleName }}</td></tr>
                </table>
                <div class="nombre uppercase">{{ $student->name }}</div>
                <div class="curp">{{ $student->curp }}</div>
                <div class="font-bold underline tracking-tight">{{ $student->grade }} - {{ $student->group_name }}</div>
                <div class="text-[10px] uppercase font-medium">{{ $student->turn }}</div>

                <img src="{{ $qrUrl }}" class="qr" alt="Código QR">
                <img src="{{ asset('images/credentials/escudog5.png') }}" class="logo" alt="Logo Escuela">
            </div>

            {{-- Reverso --}}
            <div class="credencial reverso">
                <div class="text-center w-full">
                    <p class="m-0 font-bold">ESCUELA SECUNDARIA GENERAL No. 5</p>
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
</body>
</html>
