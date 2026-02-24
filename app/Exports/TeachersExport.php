<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeachersExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    public function __construct(
        private readonly bool $generatePasswords = false,
    ) {}

    public function collection()
    {
        return User::query()
            ->whereIn('role', ['TEACHER', 'ADMIN'])
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $passwordColumn = $user->plain_password ?? '';

                if ($this->generatePasswords) {
                    $newPassword = \Illuminate\Support\Str::password(8, letters: true, numbers: true, symbols: false, spaces: false);
                    $user->update([
                        'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
                        'plain_password' => $newPassword,
                    ]);
                    $passwordColumn = $newPassword;
                }

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $passwordColumn,
                    'role' => $user->role,
                ];
            });
    }

    public function headings(): array
    {
        return ['Nombre', 'Correo', 'Contraseña', 'Rol'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
