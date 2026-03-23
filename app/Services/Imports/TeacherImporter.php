<?php

namespace App\Services\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles the import of teacher/admin users from Excel data.
 */
class TeacherImporter
{
    use ImportUtils;

    /**
     * Import teacher rows into the database.
     *
     * @return array{created: int, updated: int, total: int, errors: int}
     */
    public function import(Collection $rows, array $columnMapping): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'total' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                $emailIdx = $columnMapping['email'] ?? 1;
                $nameIdx = $columnMapping['name'] ?? 0;
                $passwordIdx = $columnMapping['password'] ?? 2;
                $roleIdx = $columnMapping['role'] ?? 3;

                $email = $this->sanitizeEmail($row[$emailIdx] ?? '');

                if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail'])) {
                    continue;
                }

                $name = $row[$nameIdx] ?? null;
                $password = $row[$passwordIdx] ?? null;
                $roleLabel = $row[$roleIdx] ?? '';

                if (! $name || strtolower(trim($name)) === 'nombre') {
                    continue;
                }

                $role = match (strtoupper(trim($roleLabel))) {
                    'ADMINISTRADOR', 'ADMISTRADOR', 'DIRECTOR' => 'ADMIN',
                    default => 'TEACHER',
                };

                $user = User::where('email', $email)->first();

                if ($user) {
                    $updateData = [
                        'name' => $name,
                        'role' => $role,
                    ];

                    if ($password) {
                        $updateData['password'] = Hash::make((string) $password);
                        $updateData['plain_password'] = (string) $password;
                    }

                    $user->update($updateData);
                    $stats['updated']++;
                } else {
                    $plainPassword = $password ?: Str::random(10);
                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($plainPassword),
                        'plain_password' => $plainPassword,
                        'role' => $role,
                        'status' => 'ACTIVE',
                    ]);
                    $stats['created']++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }
}
