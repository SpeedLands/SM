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
        'target_grades',
        'target_class_groups',
        'requires_authorization',
        'event_date',
        'event_time',
        'date',
    ];

    protected $casts = [
        'requires_authorization' => 'boolean',
        'event_date' => 'date',
        'date' => 'datetime',
        'target_grades' => 'array',
        'target_class_groups' => 'array',
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

    /**
     * Get the query for students who are expected to sign this notice.
     */
    public function getExpectedRecipientsQuery()
    {
        return Student::query()
            ->whereHas('cycleAssociations', function ($query) {
                $query->where('cycle_id', $this->cycle_id);
            })
            ->when($this->target_audience === 'PARENTS', function ($query) {
                $query->when(!empty($this->target_grades), function ($q) {
                    $q->whereIn('grade', $this->target_grades);
                })
                ->when(!empty($this->target_class_groups), function ($q) {
                    $q->whereHas('currentCycleAssociation', function ($sq) {
                        $sq->whereIn('class_group_id', $this->target_class_groups);
                    });
                });
            });
    }

    /**
     * Get the count of expected recipients.
     */
    public function getExpectedRecipientsCount(): int
    {
        // If target_audience is ALL, it targets all students in the cycle
        // If PARENTS, it follows the filters
        return $this->getExpectedRecipientsQuery()->count();
    }

    /**
     * Get signature statistics.
     */
    public function getSignatureStats(): array
    {
        $expected = $this->getExpectedRecipientsCount();
        $signed = $this->signatures_count ?? $this->signatures()->count();
        $pending = max(0, $expected - $signed);
        $percentage = $expected > 0 ? round(($signed / $expected) * 100) : 0;

        return [
            'expected' => $expected,
            'signed' => $signed,
            'pending' => $pending,
            'percentage' => $percentage,
        ];
    }
}
