<?php

namespace App\Services\Imports;

use App\Models\Student;

/**
 * Responsible for finding students by name using exact, partial, and fuzzy matching.
 */
class StudentMatcher
{
    /**
     * Cached collection of all students for fuzzy matching.
     */
    private static $allStudentsCache = null;

    /**
     * Reset the static cache (useful for testing).
     */
    public static function resetCache(): void
    {
        self::$allStudentsCache = null;
    }

    /**
     * Find a student by name using exact, partial (LIKE), and fuzzy matching.
     *
     * @return array{student: Student|null, method: string, similarity: int}
     */
    public function findInGroup(string $studentName, string $grade, string $section): array
    {
        // 1. Exact match in specific group
        $student = Student::where('name', $studentName)
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'exact', 'similarity' => 100];
        }

        // 2. Exact match globally
        $student = Student::where('name', $studentName)->first();
        if ($student) {
            return ['student' => $student, 'method' => 'exact_global', 'similarity' => 100];
        }

        // 3. Partial match (LIKE) in specific group
        $student = Student::where('name', 'LIKE', "%{$studentName}%")
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'like', 'similarity' => 95];
        }

        // 4. Partial match (LIKE) globally
        $student = Student::where('name', 'LIKE', "%{$studentName}%")->first();
        if ($student) {
            return ['student' => $student, 'method' => 'like_global', 'similarity' => 95];
        }

        // 5. Fuzzy matching globally (85% similarity threshold)
        return $this->fuzzyMatch($studentName);
    }

    /**
     * Attempt to find a student by fuzzy name matching.
     *
     * @return array{student: Student|null, method: string, similarity: int}
     */
    protected function fuzzyMatch(string $studentName): array
    {
        if (self::$allStudentsCache === null) {
            self::$allStudentsCache = Student::select('id', 'name')->get();
        }

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach (self::$allStudentsCache as $candidate) {
            if (abs(strlen($studentName) - strlen($candidate->name)) > 15) {
                continue;
            }

            similar_text(strtoupper($studentName), strtoupper($candidate->name), $similarity);

            if ($similarity > $bestSimilarity && $similarity >= 85) {
                $bestMatch = $candidate;
                $bestSimilarity = $similarity;
            }
        }

        if ($bestMatch) {
            return ['student' => $bestMatch, 'method' => 'fuzzy', 'similarity' => round($bestSimilarity)];
        }

        return ['student' => null, 'method' => 'none', 'similarity' => 0];
    }
}
