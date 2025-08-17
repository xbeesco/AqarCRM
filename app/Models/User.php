<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'identity_file',
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
     * Scope for employees
     */
    public function scopeEmployees($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'employee');
        });
    }

    /**
     * Scope for owners
     */
    public function scopeOwners($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'owner');
        });
    }

    /**
     * Scope for tenants
     */
    public function scopeTenants($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'tenant');
        });
    }

    /**
     * Filament access control
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow super_admin, admin, and employee to access admin panel
        return $this->hasAnyRole(['super_admin', 'admin', 'employee']);
    }
}
