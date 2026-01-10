<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
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

    protected $casts = [
        'date' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function infraction()
    {
        return $this->belongsTo(Infraction::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'signed_by_parent_id');
    }

    public function cycle()
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
