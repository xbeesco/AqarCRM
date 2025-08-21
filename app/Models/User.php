<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;
    
    /**
     * The guard for this model.
     *
     * @var string
     */
    protected $guard_name = 'web';

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
        'secondary_phone',
        'identity_file',
        'user_type',
        // Employee fields
        'employee_id',
        'department',
        'joining_date',
        'salary',
        'position',
        'supervisor_id',
        'emergency_contact',
        'emergency_phone',
        'address',
        'birth_date',
        // Owner fields
        'commercial_register',
        'tax_number',
        'bank_name',
        'bank_account_number',
        'iban',
        'nationality',
        'ownership_documents',
        'legal_representative',
        'company_name',
        'business_type',
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
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Set the phone attribute (remove non-numeric characters)
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Set the secondary_phone attribute (remove non-numeric characters)
     */
    public function setSecondaryPhoneAttribute($value)
    {
        $this->attributes['secondary_phone'] = preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Generate email from phone for owners and tenants
     */
    public function generateEmailFromPhone()
    {
        if ($this->phone && in_array($this->user_type, ['owner', 'tenant'])) {
            return $this->phone . '@towntop.sa';
        }
        return $this->email;
    }

    /**
     * Generate password from phone for owners and tenants
     */
    public function generatePasswordFromPhone()
    {
        if ($this->phone && in_array($this->user_type, ['owner', 'tenant'])) {
            return bcrypt($this->phone);
        }
        return $this->password;
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
