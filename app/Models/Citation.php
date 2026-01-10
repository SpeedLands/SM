<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Citation extends Model
{
    /** @use HasFactory<\Database\Factories\CitationFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'cycle_id',
        'student_id',
        'teacher_id',
        'reason',
        'citation_date',
        'status',
        'parent_signature',
        'created_at',
    ];

    protected $casts = [
        'citation_date' => 'datetime',
        'parent_signature' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }
}
