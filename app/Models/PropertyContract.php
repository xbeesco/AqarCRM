<?php

namespace App\Models;

use Carbon\Carbon;
use App\Services\PropertyContractService;
use Exception;
use App\Services\PropertyContractValidationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'owner_id',
        'property_id',
        'contract_status',
        'commission_rate',
        'duration_months',
        'start_date',
        'end_date',
        'payment_frequency',
        'payments_count',
        'notes',
        'file',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'commission_rate' => 'decimal:2',
        'duration_months' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $year = date('Y');
                // Get last contract number for current year
                $lastContract = static::where('contract_number', 'like', "PC-{$year}-%")
                    ->orderByRaw('CAST(SUBSTRING(contract_number, -4) AS UNSIGNED) DESC')
                    ->first();

                if ($lastContract) {
                    // Extract number from last contract
                    $lastNumber = intval(substr($lastContract->contract_number, -4));
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $contract->contract_number = sprintf('PC-%s-%04d', $year, $nextNumber);
            }

            // Set owner_id from property if not set
            if (empty($contract->owner_id) && $contract->property_id) {
                $property = Property::find($contract->property_id);
                if ($property) {
                    $contract->owner_id = $property->owner_id;
                }
            }

            // Calculate end_date if not set
            if (empty($contract->end_date) && $contract->start_date && $contract->duration_months) {
                $startDate = Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }

            // Validate duration matches frequency
            $contract->validateDurationFrequency();

            // Validate no overlap with other contracts
            $contract->validateNoOverlap();
        });

        static::updating(function ($contract) {
            // Recalculate end_date if start_date or duration_months changed
            if ($contract->isDirty(['start_date', 'duration_months']) && $contract->start_date && $contract->duration_months) {
                $startDate = Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }

            // Validate duration matches frequency
            if ($contract->isDirty(['duration_months', 'payment_frequency'])) {
                $contract->validateDurationFrequency();
            }

            // Validate no overlap with other contracts
            if ($contract->isDirty(['start_date', 'duration_months', 'property_id'])) {
                $contract->validateNoOverlap();
            }
        });
    }

    /**
     * Get owner attribute from property relationship.
     */
    public function getOwnerAttribute()
    {
        return $this->property?->owner;
    }

    /**
     * Calculate payments count based on duration and frequency.
     */
    public function getPaymentsCountAttribute()
    {
        // Return stored value if exists, otherwise calculate
        if (isset($this->attributes['payments_count']) && ! is_null($this->attributes['payments_count'])) {
            return $this->attributes['payments_count'];
        }

        return PropertyContractService::calculatePaymentsCount(
            $this->duration_months ?? 0,
            $this->payment_frequency ?? 'monthly'
        );
    }

    /**
     * Relationship: Contract belongs to property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relationship: Contract belongs to owner.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relationship: Contract has many supply payments.
     */
    public function supplyPayments()
    {
        return $this->hasMany(SupplyPayment::class);
    }

    /**
     * Check if contract can have payments generated.
     */
    public function canGeneratePayments(): bool
    {
        // Check if payments already exist
        if ($this->supplyPayments()->exists()) {
            return false;
        }

        // Check payments count is valid
        if (! is_numeric($this->payments_count) || $this->payments_count <= 0) {
            return false;
        }

        // Check duration is divisible by frequency
        return $this->isValidDurationForFrequency();
    }

    /**
     * Check if duration is valid for the payment frequency.
     */
    public function isValidDurationForFrequency(): bool
    {
        $monthsPerPayment = match ($this->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };

        // Check duration is divisible without remainder
        return ($this->duration_months % $monthsPerPayment) === 0;
    }

    /**
     * Validate duration and frequency compatibility.
     *
     * @throws Exception if invalid
     */
    protected function validateDurationFrequency(): void
    {
        if (! $this->isValidDurationForFrequency()) {
            $frequencyLabel = match ($this->payment_frequency) {
                'monthly' => 'monthly',
                'quarterly' => 'quarterly (3 months)',
                'semi_annually' => 'semi-annually (6 months)',
                'annually' => 'annually (12 months)',
                default => $this->payment_frequency,
            };

            throw new Exception(
                "Contract duration ({$this->duration_months} months) is not compatible with frequency ({$frequencyLabel})"
            );
        }
    }

    /**
     * Validate no overlap with other contracts.
     *
     * @throws Exception if overlapping
     */
    protected function validateNoOverlap(): void
    {
        if (! $this->property_id || ! $this->start_date || ! $this->end_date) {
            return;
        }

        $validationService = app(PropertyContractValidationService::class);
        $excludeId = $this->exists ? $this->id : null;

        $error = $validationService->validateFullAvailability(
            $this->property_id,
            $this->start_date,
            $this->end_date,
            $excludeId
        );

        if ($error) {
            throw new Exception($error);
        }
    }

    /**
     * Check if contract can be renewed.
     */
    public function canRenew(): bool
    {
        return in_array($this->contract_status, ['active', 'expired'])
            && $this->end_date !== null;
    }

    /**
     * Check if contract can be rescheduled.
     */
    public function canReschedule(): bool
    {
        // Must have existing payments
        if (!$this->supplyPayments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate commission based on amount.
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Scope for contracts expiring within days.
     */
    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('contract_status', 'active')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now());
    }

    /**
     * Scope for contracts by owner.
     */
    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
