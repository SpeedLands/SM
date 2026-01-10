<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    /** @use HasFactory<\Database\Factories\RegulationFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}
