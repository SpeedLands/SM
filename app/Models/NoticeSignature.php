<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeSignature extends Model
{
    /** @use HasFactory<\Database\Factories\NoticeSignatureFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'notice_id',
        'parent_id',
        'student_id',
        'signed_at',
        'authorized',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'authorized' => 'boolean',
    ];

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
