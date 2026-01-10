<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    /** @use HasFactory<\Database\Factories\ClassGroupFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'cycle_id',
        'grade',
        'section',
        'tutor_teacher_id',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_teacher_id');
    }
}
