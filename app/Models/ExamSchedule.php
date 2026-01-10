<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\ExamScheduleFactory> */
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
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }
}
