<?php

namespace App\Models;

use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
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

    public function studentCycleAssociations()
    {
        return $this->hasMany(StudentCycleAssociation::class, 'class_group_id');
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            StudentCycleAssociation::class,
            'class_group_id', // Foreign key on student_cycle_association table...
            'id',             // Foreign key on students table...
            'id',             // Local key on class_groups table...
            'student_id'      // Local key on student_cycle_association table...
        );
    }
}
