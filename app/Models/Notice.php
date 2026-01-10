<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    /** @use HasFactory<\Database\Factories\NoticeFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;
    // Composite PKs are tricky in standard Eloquent save(), but manageable.
    // We define generic ID for now, but be aware.
    // Or we rely on ID being unique enough? Yes, ID is UUID.
    // Partitioning key is cycle_id.

    protected $fillable = [
        'cycle_id',
        'author_id',
        'title',
        'content',
        'type',
        'target_audience',
        'requires_authorization',
        'event_date',
        'event_time',
        'date',
    ];

    protected $casts = [
        'requires_authorization' => 'boolean',
        'event_date' => 'date',
        'date' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signatures()
    {
        return $this->hasMany(NoticeSignature::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }
}
