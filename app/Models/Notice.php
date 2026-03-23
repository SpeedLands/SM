<?php

namespace App\Models;

use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    public ?array $cached_stats = null;

    protected $fillable = [
        'cycle_id',
        'author_id',
        'title',
        'content',
        'type',
        'target_audience',
        'target_grades',
        'target_class_groups',
        'target_student_id',
        'requires_authorization',
        'event_date',
        'end_date',
        'event_time',
        'date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_authorization' => 'boolean',
            'event_date' => 'date',
            'end_date' => 'date',
            'date' => 'datetime',
            'target_grades' => 'array',
            'target_class_groups' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(NoticeSignature::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function targetStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'target_student_id');
    }

    /**
     * Get the query for students who are expected to sign this notice.
     */
    public function getExpectedRecipientsQuery()
    {
        return Student::query()
            ->when($this->target_student_id, function ($query) {
                $query->where('id', $this->target_student_id);
            }, function ($query) {
                $query->whereHas('currentCycleAssociation', function ($query) {
                    $query->where('cycle_id', $this->cycle_id);
                })
                    ->when($this->target_audience === 'PARENTS', function ($query) {
                        $query->when(! empty($this->target_grades), function ($q) {
                            $q->whereIn('grade', $this->target_grades);
                        })
                            ->when(! empty($this->target_class_groups), function ($q) {
                                $q->whereHas('currentCycleAssociation', function ($sq) {
                                    $sq->whereIn('class_group_id', $this->target_class_groups);
                                });
                            });
                    });
            });
    }

    /**
     * Get the count of expected recipients.
     */
    public function getExpectedRecipientsCount(): int
    {
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

    /**
     * Check if a specific student is a target recipient of this notice.
     */
    public function isTargeting(Student $student): bool
    {
        // If specific student is targeted
        if ($this->target_student_id) {
            return $student->id === $this->target_student_id;
        }

        // Must be in the cycle of the notice
        $association = $student->cycleAssociations->firstWhere('cycle_id', $this->cycle_id);

        if (! $association) {
            return false;
        }
        // ...

        // If target audience is ALL, then yes.
        if ($this->target_audience === 'ALL') {
            return true;
        }

        // If PARENTS, but no specific targeting filters, then all parents (all students).
        if (empty($this->target_grades) && empty($this->target_class_groups)) {
            return true;
        }

        // Check Grade targeting
        if (! empty($this->target_grades)) {
            if (in_array($student->grade, $this->target_grades)) {
                return true;
            }
        }

        // Check Class Group targeting
        if (! empty($this->target_class_groups)) {
            if (in_array($association->class_group_id, $this->target_class_groups)) {
                return true;
            }
        }

        return false;
    }
}
