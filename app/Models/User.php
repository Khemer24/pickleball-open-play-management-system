<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'manager_approved',
        'organizer_approved',
        'avatar_path',
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
            'manager_approved' => 'boolean',
            'organizer_approved' => 'boolean',
        ];
    }

    public function sessionEntries(): HasMany
    {
        return $this->hasMany(SessionPlayer::class);
    }

    public function managedSessions(): HasMany
    {
        return $this->hasMany(OpenPlaySession::class, 'manager_id');
    }

    public function organizedSessions(): HasMany
    {
        return $this->hasMany(OpenPlaySession::class, 'organizer_id');
    }

    public function isManager(): bool
    {
        return $this->role === 'manager' && $this->manager_approved;
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer' && $this->organizer_approved;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
