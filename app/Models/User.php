<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'role',
        'phone',
        'bio',
        'avatar',
        'logo',
        'signature',
        'password',
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
            // is_organizer removed; organizer determined by role
        ];
    }

    // Role checking methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isParticipant()
    {
        return $this->role === 'participant';
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    // Organizer checking methods (Admin = Event Organizer)
    public function isOrganizer()
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function canCreateEvents()
    {
        return $this->isOrganizer();
    }

    // Relationships
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Override password reset notification to use custom notification
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Accessor methods for file URLs
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature ? asset('storage/' . $this->signature) : null;
    }
}
