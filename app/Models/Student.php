<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'curp',
        'birth_date',
        'grade',
        'group_name',
        'turn',
        'siblings_count',
        'birth_order',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_parents', 'student_id', 'parent_id')
            ->withPivot('relationship');
    }

    public function cycleAssociations(): HasMany
    {
        return $this->hasMany(StudentCycleAssociation::class);
    }

    public function currentCycleAssociation(): HasOne
    {
        return $this->hasOne(StudentCycleAssociation::class)
            ->whereHas('cycle', function ($query) {
                $query->where('is_active', true);
            });
    }

    public function pii(): HasOne
    {
        return $this->hasOne(StudentPii::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function communityServices(): HasMany
    {
        return $this->hasMany(CommunityService::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function noticeSignatures(): HasMany
    {
        return $this->hasMany(NoticeSignature::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function photoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->photo_path
                ? \Illuminate\Support\Facades\Storage::url($this->photo_path)
                : null
        );
    }
}
