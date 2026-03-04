<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'student_id',
        'date',
        'entry_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'entry_time' => 'datetime:H:i',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
