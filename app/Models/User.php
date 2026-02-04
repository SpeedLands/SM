<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Cache for hasStudents check to avoid repeated DB queries.
     */
    protected ?bool $hasStudentsCache = null;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! $user->id) {
                $user->id = (string) Str::uuid();
            }
        });
    }

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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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

    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parents', 'parent_id', 'student_id')
            ->withPivot('relationship');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
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

        $students = $this->students()
            ->when($studentId, fn ($q) => $q->where('students.id', $studentId))
            ->with(['currentCycleAssociation'])->get();

        $activeCycle = Cycle::where('is_active', true)->first();

        if (! $activeCycle) {
            return 0;
        }

        return Notice::where('cycle_id', $activeCycle->id)
            ->whereIn('target_audience', ['PARENTS', 'ALL'])
            ->get()
            ->filter(function ($notice) use ($students, $studentId) {
                // If a specific studentId is requested, we only care about that student
                $targetStudents = $studentId
                    ? $students->filter(fn ($s) => (string) $s->id === (string) $studentId)
                    : $students;

                foreach ($targetStudents as $student) {
                    if ($notice->isTargeting($student)) {
                        // Check if signed for this student
                        $signed = $notice->signatures()->where('student_id', (string) $student->id)->exists();
                        if (! $signed) {
                            return true;
                        }
                    }
                }

                return false;
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
            // Verify ownership first
            $ownsStudent = $this->students()->where('students.id', $studentId)->exists();
            if (! $ownsStudent) {
                return 0;
            }
            $query->where('student_id', $studentId);
        } else {
            $query->whereHas('student.parents', fn ($q) => $q->where('users.id', $this->id));
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
            // Verify ownership first
            $ownsStudent = $this->students()->where('students.id', $studentId)->exists();
            if (! $ownsStudent) {
                return 0;
            }
            $query->where('student_id', $studentId);
        } else {
            $query->whereHas('student.parents', fn ($q) => $q->where('users.id', $this->id));
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
            // Verify ownership first
            $ownsStudent = $this->students()->where('students.id', $studentId)->exists();
            if (! $ownsStudent) {
                return 0;
            }
            $query->where('student_id', $studentId);
        } else {
            $query->whereHas('student.parents', fn ($q) => $q->where('users.id', $this->id));
        }

        return $query->count();
    }

    /**
     * Get the total pending notifications count.
     */
    public function getPendingNotificationsCount(?string $studentId = null): int
    {
        return $this->getUnsignedNoticesCount($studentId) +
               $this->getUnsignedReportsCount($studentId) +
               $this->getUnsignedCommunityServicesCount($studentId) +
               $this->getUnsignedCitationsCount($studentId);
    }
}
