<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCycleAssociation extends Model
{
    use HasFactory, HasUuids;

    public $table = 'student_cycle_association';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'cycle_id',
        'class_group_id',
        'status',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function group()
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
