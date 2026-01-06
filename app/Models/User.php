<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
        ];
    }

    /**
     * Get the farms that the user belongs to.
     */
    public function farms()
    {
        return $this->belongsToMany(Farm::class)
            ->withPivot([
                'role',
                'membership_status',
                'employment_category',
                'pay_type',
                'pay_rate',
                'start_date',
                'end_date',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Get all job role assignments for this user.
     */
    public function jobRoleAssignments()
    {
        return $this->hasMany(UserJobRoleAssignment::class);
    }

    /**
     * Get active job role assignments for this user.
     */
    public function activeJobRoleAssignments()
    {
        return $this->hasMany(UserJobRoleAssignment::class)->whereNull('ended_at');
    }

    /**
     * Get job role assignments for a specific farm.
     */
    public function jobRoleAssignmentsForFarm($farmId)
    {
        return $this->jobRoleAssignments()
            ->where('farm_id', $farmId)
            ->whereNull('ended_at');
    }

    /**
     * Get the URL for the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
            return $this->profile_photo_path;
        }

        // Otherwise, generate storage URL
        return asset('storage/' . $this->profile_photo_path);
    }
}
