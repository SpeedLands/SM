<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\FcmService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    /**
     * Cache for hasStudents check to avoid repeated DB queries.
     */
    protected ?bool $hasStudentsCache = null;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'external_id',
        'name',
        'email',
        'password',
        'plain_password',
        'role',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'current_team_id',
        'profile_photo_path',
        'last_login_at',
        'phone',
        'occupation',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'plain_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plain_password' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'TEACHER';
    }

    public function isParent(): bool
    {
        return $this->role === 'PARENT' || $this->hasStudents();
    }

    /**
     * Check if the user has students associated (memoized).
     */
    public function hasStudents(): bool
    {
        if ($this->hasStudentsCache !== null) {
            return $this->hasStudentsCache;
        }

        return $this->hasStudentsCache = $this->students()->exists();
    }

    /**
     * Check if the active view is set to Parent mode.
     */
    public function isViewParent(): bool
    {
        // If the user ONLY has the PARENT role, they are always in parent view
        if ($this->role === 'PARENT') {
            return true;
        }

        // If they have dual roles (Staff + Parent), check the session
        if ($this->hasStudents()) {
            return session('active_view') === 'parent';
        }

        return false;
    }

    /**
     * Check if the active view is set to Staff mode.
     */
    public function isViewStaff(): bool
    {
        if ($this->isAdmin() || $this->isTeacher()) {
            // If they have kids, respect the session switcher
            if ($this->hasStudents()) {
                return session('active_view', 'staff') === 'staff';
            }

            return true;
        }

        return false;
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parents', 'parent_id', 'student_id')
            ->withPivot('relationship');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'teacher_id');
    }

    /**
     * Get the count of unsigned notices for the parent.
     */
    public function getUnsignedNoticesCount(?string $studentId = null): int
    {
        if (! $this->isParent()) {
            return 0;
        }

        $activeCycle = Cycle::where('is_active', true)->first();
        if (! $activeCycle) {
            return 0;
        }

        $students = $this->students()
            ->when($studentId, fn ($q) => $q->where('students.id', $studentId))
            ->with(['currentCycleAssociation'])
            ->get();

        if ($students->isEmpty()) {
            return 0;
        }

        $studentIds = $students->pluck('id')->toArray();
        $studentGrades = $students->pluck('grade')->unique()->toArray();
        $studentGroupIds = $students->pluck('currentCycleAssociation.class_group_id')->filter()->unique()->toArray();

        return Notice::where('cycle_id', $activeCycle->id)
            ->whereIn('target_audience', ['PARENTS', 'ALL'])
            ->where(function ($query) use ($studentGrades, $studentGroupIds) {
                $query->where('target_audience', 'ALL')
                    ->orWhere(function ($q) use ($studentGrades, $studentGroupIds) {
                        $q->where('target_audience', 'PARENTS')
                            ->where(function ($sq) use ($studentGrades, $studentGroupIds) {
                                $sq->where(function ($ssq) {
                                    $ssq->whereNull('target_grades')
                                        ->whereNull('target_class_groups');
                                })
                                    ->orWhere(function ($ssq) use ($studentGrades) {
                                        foreach ($studentGrades as $grade) {
                                            $ssq->orWhereJsonContains('notices.target_grades', (string) $grade);
                                        }
                                    })
                                    ->orWhere(function ($ssq) use ($studentGroupIds) {
                                        foreach ($studentGroupIds as $groupId) {
                                            $ssq->orWhereJsonContains('notices.target_class_groups', (string) $groupId);
                                        }
                                    });
                            });
                    });
            })
            ->whereDoesntHave('signatures', function ($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            })
            ->count();
    }

    public function getUnsignedReportsCount(?string $studentId = null): int
    {
        if (! $this->isParent()) {
            return 0;
        }

        $query = Report::where('status', 'PENDING_SIGNATURE');

        if ($studentId) {
            $query->where('student_id', $studentId);
        } else {
            $query->whereIn('student_id', $this->students()->pluck('students.id'));
        }

        return $query->count();
    }

    /**
     * Get the count of unsigned community services for the parent.
     */
    public function getUnsignedCommunityServicesCount(?string $studentId = null): int
    {
        if (! $this->isParent()) {
            return 0;
        }

        $query = CommunityService::where('parent_signature', false);

        if ($studentId) {
            $query->where('student_id', $studentId);
        } else {
            $query->whereIn('student_id', $this->students()->pluck('students.id'));
        }

        return $query->count();
    }

    /**
     * Get the count of unsigned citations for the parent.
     */
    public function getUnsignedCitationsCount(?string $studentId = null): int
    {
        if (! $this->isParent()) {
            return 0;
        }

        $query = Citation::where('parent_signature', false);

        if ($studentId) {
            $query->where('student_id', $studentId);
        } else {
            $query->whereIn('student_id', $this->students()->pluck('students.id'));
        }

        return $query->count();
    }

    /**
     * Consolidated counts for better performance (one students lookup).
     */
    public function getPendingNotificationsSummary(): array
    {
        if (! $this->isParent()) {
            return ['reports' => 0, 'services' => 0, 'notices' => 0, 'citations' => 0, 'total' => 0];
        }

        $students = $this->students()->with(['currentCycleAssociation'])->get();
        if ($students->isEmpty()) {
            return ['reports' => 0, 'services' => 0, 'notices' => 0, 'citations' => 0, 'total' => 0];
        }

        $studentIds = $students->pluck('id')->toArray();

        $reports = Report::whereIn('student_id', $studentIds)->where('status', 'PENDING_SIGNATURE')->count();
        $services = CommunityService::whereIn('student_id', $studentIds)->where('parent_signature', false)->count();
        $citations = Citation::whereIn('student_id', $studentIds)->where('parent_signature', false)->count();

        // Use student data for notices
        $activeCycle = Cycle::where('is_active', true)->first();
        if (! $activeCycle) {
            $notices = 0;
        } else {
            $studentGrades = $students->pluck('grade')->unique()->toArray();
            $studentGroupIds = $students->pluck('currentCycleAssociation.class_group_id')->filter()->unique()->toArray();

            $notices = Notice::where('cycle_id', $activeCycle->id)
                ->whereIn('target_audience', ['PARENTS', 'ALL'])
                ->where(function ($query) use ($studentGrades, $studentGroupIds) {
                    $query->where('target_audience', 'ALL')
                        ->orWhere(function ($q) use ($studentGrades, $studentGroupIds) {
                            $q->where('target_audience', 'PARENTS')
                                ->where(function ($sq) use ($studentGrades, $studentGroupIds) {
                                    $sq->where(function ($ssq) {
                                        $ssq->whereNull('target_grades')
                                            ->whereNull('target_class_groups');
                                    })
                                        ->orWhere(function ($ssq) use ($studentGrades) {
                                            foreach ($studentGrades as $grade) {
                                                $ssq->orWhereJsonContains('notices.target_grades', (string) $grade);
                                            }
                                        })
                                        ->orWhere(function ($ssq) use ($studentGroupIds) {
                                            foreach ($studentGroupIds as $groupId) {
                                                $ssq->orWhereJsonContains('notices.target_class_groups', (string) $groupId);
                                            }
                                        });
                                });
                        });
                })
                ->whereDoesntHave('signatures', function ($q) use ($studentIds) {
                    $q->whereIn('student_id', $studentIds);
                })
                ->count();
        }

        return [
            'reports' => $reports,
            'services' => $services,
            'notices' => $notices,
            'citations' => $citations,
            'total' => $reports + $services + $notices + $citations,
        ];
    }

    /**
     * Get the total pending notifications count (integer).
     */
    public function getPendingNotificationsCount(?string $studentId = null): int
    {
        if ($studentId) {
            return $this->getUnsignedNoticesCount($studentId) +
                   $this->getUnsignedReportsCount($studentId) +
                   $this->getUnsignedCommunityServicesCount($studentId) +
                   $this->getUnsignedCitationsCount($studentId);
        }

        return $this->getPendingNotificationsSummary()['total'];
    }

    /**
     * Get the most relevant route based on pending notifications.
     */
    public function getPendingTargetRoute(?string $studentId = null): string
    {
        if ($studentId) {
            if ($this->getUnsignedNoticesCount($studentId) > 0) {
                return route('notices.index');
            }
            if ($this->getUnsignedCitationsCount($studentId) > 0) {
                return route('citations.index');
            }
            if ($this->getUnsignedCommunityServicesCount($studentId) > 0) {
                return route('community-services.index');
            }

            return route('reports.index');
        }

        $summary = $this->getPendingNotificationsSummary();

        if ($summary['notices'] > 0) {
            return route('notices.index');
        }
        if ($summary['citations'] > 0) {
            return route('citations.index');
        }
        if ($summary['services'] > 0) {
            return route('community-services.index');
        }

        return route('reports.index');
    }

    /**
     * Send a FCM notification to this user.
     */
    public function sendFcmNotification(string $title, string $body, array $data = [], ?string $icon = null, ?string $image = null, ?string $url = null): bool
    {
        if (! $this->fcm_token) {
            return false;
        }

        return app(FcmService::class)->sendNotification(
            $this->fcm_token,
            $title,
            $body,
            $data,
            $icon,
            $image,
            $url
        );
    }
}
