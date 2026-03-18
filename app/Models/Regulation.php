<?php

namespace App\Models;

use Database\Factories\RegulationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    /** @use HasFactory<RegulationFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'title',
        'content',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}
