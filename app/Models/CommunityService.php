<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityService extends Model
{
    /** @use HasFactory<\Database\Factories\CommunityServiceFactory> */
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'cycle_id',
        'student_id',
        'assigned_by_id',
        'activity',
        'description',
        'scheduled_date',
        'status',
        'parent_signature',
        'parent_signed_at',
        'authority_signature_id',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'parent_signed_at' => 'datetime',
        'completed_at' => 'datetime',
        'parent_signature' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function authoritySignature()
    {
        return $this->belongsTo(User::class, 'authority_signature_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
