<?php

namespace App\Models;

use Database\Factories\ExamScheduleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    /** @use HasFactory<ExamScheduleFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'cycle_id',
        'grade',
        'group_name',
        'period',
        'subject',
        'exam_date',
        'day_of_week',
        'created_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
