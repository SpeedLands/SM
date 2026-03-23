<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPii extends Model
{
    use HasFactory;

    public $table = 'student_pii';

    protected $primaryKey = 'student_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'address_encrypted',
        'contact_phone_encrypted',
        'allergies_encrypted',
        'medical_conditions_encrypted',
        'emergency_contact_encrypted',
        'mother_name_encrypted',
        'father_name_encrypted',
        'other_contact_encrypted',
        'mother_workplace_encrypted',
        'father_workplace_encrypted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address_encrypted' => 'encrypted',
            'contact_phone_encrypted' => 'encrypted',
            'allergies_encrypted' => 'encrypted',
            'medical_conditions_encrypted' => 'encrypted',
            'emergency_contact_encrypted' => 'encrypted',
            'mother_name_encrypted' => 'encrypted',
            'father_name_encrypted' => 'encrypted',
            'other_contact_encrypted' => 'encrypted',
            'mother_workplace_encrypted' => 'encrypted',
            'father_workplace_encrypted' => 'encrypted',
        ];
    }
}
