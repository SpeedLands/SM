<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Credenciales</title>
    <style>
        @page {
            margin: 0.5cm;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'Helvetica', sans-serif;
        }
        
        .scaling-wrapper {
            transform: scale({{ $scale ?? 1.0 }});
            transform-origin: top left;
        }

        .page-break {
            page-break-after: always;
        }

        .page-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 1cm;
        }
        .page-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }
        .credencial {
            width: 6.68cm;
            height: 9.85cm;
            border-radius: 12px;
            text-align: center;
            position: relative;
            background: white;
            overflow: hidden;
            border: 0.5pt solid #000;
            display: inline-block;
        }
        .fondo {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .content {
            position: relative;
            z-index: 10;
            margin-top: 10px;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }
        .content-reverso {
            position: relative;
            z-index: 10;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }
        .tabla-escuela {
            width: 100%;
            font-size: 11px;
            margin-bottom: 4px;
            border-collapse: collapse;
        }
        .tabla-escuela td {
            padding: 0;
            line-height: 1.3;
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        /* Foto */
        .foto {
            width: 2cm;
            height: 2.5cm;
            border: 2px solid #000;
            margin: 15px auto 4px auto;
            display: block;
        }
        .foto-placeholder {
            /* padding-top: 1cm; */
            font-size: 8px;
            color: #666;
            background: #eee;
            width: 2cm;
            height: 2.5cm;
            margin: 15px auto 4px auto;
            border: 2px solid #000;
        }
        /* QR */
        .qr {
            width: 1.8cm;
            height: 1.8cm;
            margin-top: 35px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        /* Logo inferior */
        .logo {
            width: 1.5cm;
            height: 1.5cm;
            position: absolute;
            bottom: 15px;
            left: 5px;
        }
        /* Textos */
        .nombre {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-top: 5px;
            overflow: hidden;
        }
        .curp {
            font-size: 12px;
            color: #000;
            margin-top: 2px;
            font-family: 'Courier', monospace;
        }
        .detalles {
            font-size: 11px;
            color: #000;
            margin-top: 2px;
        }
        .ciclo {
            font-size: 11px;
            color: #000;
        }
        /* Reverso */
        .reverso-inner {
            padding-top: 35px;
            text-align: center;
        }
        .reverso-text {
            margin-top: 10px;
            margin-bottom: 0px;
            font-size: 13px;
        }
        .reverso-text-2 {
            font-size: 13px;
            margin-top: 0px;
            margin-bottom: 0px;
        }
        .linea-firma {
            position: absolute;
            bottom: 40px;
            left: 15%;
            right: 15%;
            width: 70%;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="scaling-wrapper">
        @foreach($studentsData as $index => $data)
            <table class="page-table">
                <tr>
                    <td>
                        <!-- Frente -->
                        <div class="credencial">
                            @if($fondoBase64)
                                <img src="{{ $fondoBase64 }}" class="fondo">
                            @endif
                            
                            <div class="content">
                                <br>
                                <table class="tabla-escuela">
                                    <tr><td><span class="bold">ESCUELA SECUNDARIA GENERAL No. 5</span></td></tr>
                                    <tr><td>DR ROGELIO MONTEMAYOR SEGUY</td></tr>
                                    <tr><td>Tel: (+52) 878-112-0282</td></tr>
                                </table>

                                <div>
                                    @if($data['photoBase64'])
                                        <img src="{{ $data['photoBase64'] }}" class="foto">
                                    @else
                                        <div class="foto-placeholder">SIN FOTO</div>
                                    @endif

                                    <div class="ciclo">Ciclo Escolar: {{ $cycleName }}</div>
                                    <div class="nombre">{{ $data['student']->name }}</div>
                                    <div class="curp">{{ $data['student']->curp }}</div>
                                    <div class="detalles">{{ $data['student']->grade }} - {{ $data['student']->group_name }}</div>
                                </div>

                                <img src="{{ $data['qrCodeBase64'] }}" class="qr">
                                
                                @if($escudoBase64)
                                    <img src="{{ $escudoBase64 }}" class="logo">
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <!-- Reverso -->
                        <div class="credencial">
                            @if($fondoBase64)
                                <img src="{{ $fondoBase64 }}" class="fondo">
                            @endif

                            <div class="content-reverso">
                                <div class="reverso-inner">
                                    <p class="reverso-text"><span class="bold">ESCUELA SECUNDARIA GENERAL</span></p>
                                    <p class="reverso-text-2"><span class="bold">No. 5</span></p>
                                    <p class="reverso-text">DR ROGELIO MONTEMAYOR SEGUY</p>
                                    <p class="reverso-text">Ciclo Escolar: {{ $cycleName }}</p>
                                    <p class="reverso-text"><em>Esta credencial es personal e</em></p>
                                    <p class="reverso-text-2"><em>intransferible.</em></p>
                                    
                                    <div class="linea-firma">
                                        PROF. NESTOR DAMIAN RIQUEJO JORDAN<br>
                                        <span style="font-size: 10px;">Director</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- 
                Page break every 2 students to maximize paper usage.
                Standard Letter size (21.59cm x 27.94cm) fits 2 sets of credentials (9.85cm each).
            --}}
            @if(($loop->iteration % 2 == 0) && !$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    </div>
</body>
</html>
