<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentParent extends Model
{
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = ['student_id', 'parent_id'];

    protected $fillable = [
        'student_id',
        'parent_id',
        'relationship',
    ];
}
