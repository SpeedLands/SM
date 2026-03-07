<table>
    <thead>
        <tr>
            <th colspan="{{ 2 + count($daysInMonth) }}" style="text-align: center; font-weight: bold;">
                ESCUELA SECUNDARIA GENERAL NO. 5 DR. ROGELIO MONTEMAYOR SEGUY.
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + count($daysInMonth) }}" style="text-align: center; font-weight: bold;">
                CLAVE: 05DES0074C
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + count($daysInMonth) }}" style="text-align: center; font-weight: bold;">
                PIEDRAS NEGRAS, COAHUILA.
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + count($daysInMonth) }}" style="text-align: center; font-weight: bold;">
                {{ $group->grade }}° SECCION: {{ $group->section }} &nbsp;&nbsp;&nbsp; CICLO ESCOLAR {{ $cycleName }}
            </th>
        </tr>
        <tr>
            <th rowspan="2" style="font-weight: bold; border: 1px solid #000000; text-align: center; vertical-align: middle;">NO.</th>
            <th rowspan="2" style="font-weight: bold; border: 1px solid #000000; text-align: center; vertical-align: middle;">Nombre</th>
            <th colspan="{{ count($daysInMonth) }}" style="font-weight: bold; border: 1px solid #000000; text-align: center;">
                {{ strtoupper($monthName) }}
            </th>
        </tr>
        <tr>
            @foreach($daysInMonth as $day)
                <th style="font-weight: bold; border: 1px solid #000000; text-align: center;">{{ $day->format('d') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            <tr>
                <td style="border: 1px solid #000000; text-align: right;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $student->name }}</td>
                @foreach($daysInMonth as $day)
                    @php
                        $dateString = $day->format('Y-m-d');
                        $isNonWorking = !in_array($dateString, $workingDays);
                        $bgColor = $isNonWorking ? 'background-color: #E2E8F0;' : '';
                        $attendance = $attendances[$student->id][$dateString] ?? null;
                        $symbol = $attendance ? ($statusSymbols[$attendance->status] ?? $attendance->status) : '';
                    @endphp
                    <td style="border: 1px solid #000000; text-align: center; {{ $bgColor }}">
                        {{ $symbol }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
