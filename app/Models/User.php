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
        'email_verified_at',
        'password',
        'phone',
        'secondary_phone',
        'identity_file',
        'type',
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
     * Unified scope for filtering by user type(s)
     * Supports: strings, UserType enums, arrays, and special keywords
     * 
     * Examples:
     * - User::byType('owner')
     * - User::byType(UserType::TENANT)
     * - User::byType(['owner', 'tenant'])
     * - User::byType('staff') // returns all employees
     * - User::byType('admin') // returns admin + super_admin
     * - User::byType('clients') // returns owner + tenant
     */
    public function scopeByType($query, $types)
    {
        // Convert single value to array for uniform processing
        if (!is_array($types)) {
            // Handle UserType enum
            if ($types instanceof UserType) {
                $types = [$types->value];
            } 
            // Handle string keywords
            else {
                $types = match(strtolower($types)) {
                    // Groups
                    'admin', 'admins' => [
                        UserType::SUPER_ADMIN->value,
                        UserType::ADMIN->value
                    ],
                    'staff', 'employees' => [
                        UserType::EMPLOYEE->value,
                        UserType::ADMIN->value,
                        UserType::SUPER_ADMIN->value
                    ],
                    'clients', 'client' => [
                        UserType::OWNER->value,
                        UserType::TENANT->value
                    ],
                    // Single types
                    'owner', 'owners' => [UserType::OWNER->value],
                    'tenant', 'tenants' => [UserType::TENANT->value],
                    'employee' => [UserType::EMPLOYEE->value],
                    'super_admin' => [UserType::SUPER_ADMIN->value],
                    // Default: use as-is
                    default => [$types]
                };
            }
        } else {
            // Process array - convert any UserType enums to strings
            $types = array_map(function($type) {
                return $type instanceof UserType ? $type->value : $type;
            }, $types);
        }
        
        // Apply query filter
        if (count($types) === 1) {
            return $query->where('type', $types[0]);
        }
        
        return $query->whereIn('type', $types);
    }

    /**
     * Scope for employees (legacy - uses byType internally)
     */
    public function scopeEmployees($query)
    {
        return $query->byType('staff');
    }

    /**
     * Scope for admins (legacy - uses byType internally)
     */
    public function scopeAdmins($query)
    {
        return $query->byType('admin');
    }

    /**
     * Scope for owners (legacy - uses byType internally)
     */
    public function scopeOwners($query)
    {
        return $query->byType('owner');
    }

    /**
     * Scope for tenants (legacy - uses byType internally)
     */
    public function scopeTenants($query)
    {
        return $query->byType('tenant');
    }
    
    /**
     * Scope by user type (legacy - uses byType internally)
     */
    public function scopeOfType($query, $type)
    {
        return $query->byType($type);
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
     * Get collection payments for tenant
     */
    public function collectionPayments()
    {
        return $this->hasMany(CollectionPayment::class, 'tenant_id');
    }
    
    /**
     * Get unit contracts for tenant
     */
    public function unitContracts()
    {
        return $this->hasMany(UnitContract::class, 'tenant_id');
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