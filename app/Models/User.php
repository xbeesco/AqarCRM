<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Enums\UserType;
use App\Services\UserPermissions;
use App\Helpers\AppHelper;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'type',
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
            'joining_date' => 'date',
            'birth_date' => 'date',
            'salary' => 'decimal:2',
            'ownership_documents' => 'array',
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
        if ($this->phone && in_array($this->type, [UserType::OWNER->value, UserType::TENANT->value])) {
            return AppHelper::generateEmailFromPhone($this->phone);
        }
        return $this->email;
    }

    /**
     * Generate password from phone for owners and tenants
     */
    public function generatePasswordFromPhone()
    {
        if ($this->phone && in_array($this->type, [UserType::OWNER->value, UserType::TENANT->value])) {
            return bcrypt($this->phone);
        }
        return $this->password;
    }

    /**
     * Scope for employees
     */
    public function scopeEmployees($query)
    {
        return $query->where('type', UserType::EMPLOYEE->value);
    }

    /**
     * Scope for admins
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('type', [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
        ]);
    }

    /**
     * Scope for owners
     */
    public function scopeOwners($query)
    {
        return $query->where('type', UserType::OWNER->value);
    }

    /**
     * Scope for tenants
     */
    public function scopeTenants($query)
    {
        return $query->where('type', UserType::TENANT->value);
    }
    
    /**
     * Scope by user type
     */
    public function scopeOfType($query, $type)
    {
        if ($type instanceof UserType) {
            $type = $type->value;
        }
        return $query->where('type', $type);
    }

    /**
     * Get user type enum
     */
    public function getUserType(): ?UserType
    {
        return $this->type ? UserType::tryFrom($this->type) : null;
    }
    
    /**
     * Get user type label
     */
    public function getTypeLabel(): string
    {
        $userType = $this->getUserType();
        return $userType ? $userType->label() : 'غير محدد';
    }
    
    /**
     * Get user type color
     */
    public function getTypeColor(): string
    {
        $userType = $this->getUserType();
        return $userType ? $userType->color() : 'gray';
    }
    
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->type === UserType::SUPER_ADMIN->value;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->type === UserType::ADMIN->value;
    }
    
    /**
     * Check if user is employee
     */
    public function isEmployee(): bool
    {
        return $this->type === UserType::EMPLOYEE->value;
    }
    
    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->type === UserType::OWNER->value;
    }
    
    /**
     * Check if user is tenant
     */
    public function isTenant(): bool
    {
        return $this->type === UserType::TENANT->value;
    }
    
    /**
     * Get properties for owners (relationship)
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }
    
    /**
     * Filament access control
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $userType = $this->getUserType();
        return $userType ? $userType->canAccessPanel() : false;
    }
}
