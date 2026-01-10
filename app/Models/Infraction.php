<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Infraction extends Model
{
    /** @use HasFactory<\Database\Factories\InfractionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'description',
        'severity',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
