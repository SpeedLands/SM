<?php

namespace App\Services\Imports;

/**
 * Shared utility methods for Excel import services.
 */
trait ImportUtils
{
    /**
     * Remove accent characters from a string for fuzzy comparisons.
     */
    protected function stripAccents(string $text): string
    {
        $search = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ü', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N', 'u', 'U'];

        return str_replace($search, $replace, $text);
    }

    /**
     * Sanitize email address by removing accents and converting special characters.
     */
    protected function sanitizeEmail($email): string
    {
        if (! $email) {
            return '';
        }

        $email = trim((string) $email);

        return strtolower($this->stripAccents($email));
    }

    /**
     * Sanitize phone numbers. Returns null if the value clearly isn't a phone number.
     */
    protected function sanitizePhone($phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = trim((string) $phone);

        // If it contains a word (e.g. "PADRE", "NO TIENE", "N/A"), reject it.
        if (preg_match('/[a-zA-Z]{3,}/', $phone)) {
            return null;
        }

        // Keep only numbers, plus, minus, parenthesis and spaces
        $clean = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone);

        return trim($clean) === '' ? null : trim($clean);
    }

    /**
     * Normalize a turn value to MATUTINO or VESPERTINO, tolerating typos and accents.
     */
    protected function normalizeTurn(mixed $turn): string
    {
        $clean = strtoupper(trim($this->stripAccents((string) ($turn ?? ''))));

        $matutino = ['MATUTINO', 'MATUTIN', 'MATUINO', 'MAÑANA', 'MANANA', 'MAT', 'M', 'MORNING'];
        $vespertino = ['VESPERTINO', 'VESPERTIN', 'VESPERTNO', 'TARDE', 'VESPER', 'VES', 'V', 'AFTERNOON'];

        if (in_array($clean, $matutino, true)) {
            return 'MATUTINO';
        }

        if (in_array($clean, $vespertino, true)) {
            return 'VESPERTINO';
        }

        if (str_starts_with($clean, 'M')) {
            return 'MATUTINO';
        }

        if (str_starts_with($clean, 'V') || str_starts_with($clean, 'T')) {
            return 'VESPERTINO';
        }

        return 'MATUTINO';
    }

    /**
     * Parse grade and section from sheet name (e.g. "3A", "3A MATUTINO", "TERCERO A").
     */
    public function extractGradeAndSection(string $sheetName): array
    {
        $grade = '1º';
        $section = 'A';

        $normalized = strtoupper($this->stripAccents($sheetName));

        $search = ['PRIMERO', 'SEGUNDO', 'TERCERO', 'CUARTO', 'QUINTO', 'SEXTO'];
        $replace = ['1', '2', '3', '4', '5', '6'];
        $normalized = str_replace($search, $replace, $normalized);

        if (preg_match('/(\d+)/', $normalized, $numMatches, PREG_OFFSET_CAPTURE)) {
            $gradeNum = $numMatches[0][0];
            $grade = $gradeNum.'º';
            $offset = $numMatches[0][1] + strlen($gradeNum);
            $after = substr($normalized, $offset);

            if (preg_match('/(?:[^A-Z]|\b[A-Z]{2,}\b)*\b([A-Z])\b/i', $after, $letterMatches)) {
                $section = strtoupper($letterMatches[1]);
            }
        }

        return [
            'grade' => $grade,
            'section' => $section,
        ];
    }

    /**
     * Extract relationship type and student name from a parent label.
     * e.g. "Padre de JUAN PEREZ" => ['relationship' => 'PADRE', 'student_name' => 'JUAN PEREZ']
     */
    protected function extractRelationAndName($parentName): array
    {
        $normalized = $this->stripAccents((string) $parentName);

        if (preg_match('/^(Padre|Papa|Papi|Mama|Madre|Tutor|Tutora|Abuelo|Abuela|Tio|Tia|Tutor Legal)\s+de\s+(.+)$/i', $normalized, $matches)) {
            $alias = strtoupper($matches[1]);
            $relationship = match ($alias) {
                'PAPA', 'PAPI' => 'PADRE',
                'MAMA' => 'MADRE',
                'TIO' => 'TUTOR',
                'TIA' => 'TUTORA',
                default => $alias,
            };

            return [
                'relationship' => $relationship,
                'student_name' => trim($matches[2]),
            ];
        }

        return [
            'relationship' => 'TUTOR',
            'student_name' => null,
        ];
    }
}
