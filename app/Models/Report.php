<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'cycle_id',
        'student_id',
        'teacher_id',
        'infraction_id',
        'subject',
        'description',
        'date',
        'status',
        'signed_at',
        'signed_by_parent_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function infraction(): BelongsTo
    {
        return $this->belongsTo(Infraction::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_parent_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public static function countForStudentInCycle(string $studentId, int $cycleId): int
    {
        return self::where('student_id', $studentId)
            ->where('cycle_id', $cycleId)
            ->count();
    }
}
