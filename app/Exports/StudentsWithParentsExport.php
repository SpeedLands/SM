<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentsWithParentsExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $groupId,
        private readonly ?string $cycleId = null,
        private readonly bool $generatePasswords = false,
    ) {}

    public function sheets(): array
    {
        return [
            new StudentsExport($this->groupId, $this->cycleId, includeParents: true),
            new ParentsExport($this->groupId, $this->cycleId, $this->generatePasswords, includeWithStudents: true),
        ];
    }
}
