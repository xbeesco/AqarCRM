<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'owner_id',
        'property_id',
        'commission_rate',
        'duration_months',
        'start_date',
        'end_date',
        'contract_status',
        'notary_number',
        'payment_day',
        'auto_renew',
        'notice_period_days',
        'payment_frequency',
        'payments_count',
        'terms_and_conditions',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'terminated_reason',
        'terminated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'commission_rate' => 'decimal:2',
        'duration_months' => 'integer',
        'payment_day' => 'integer',
        'auto_renew' => 'boolean',
        'notice_period_days' => 'integer',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $year = date('Y');
                // الحصول على آخر رقم عقد في السنة الحالية
                $lastContract = static::where('contract_number', 'like', "PC-{$year}-%")
                    ->orderByRaw('CAST(SUBSTRING(contract_number, -4) AS UNSIGNED) DESC')
                    ->first();
                
                if ($lastContract) {
                    // استخراج الرقم من آخر عقد
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
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
            
            // منع الحفظ إذا كانت المدة لا تتوافق مع التكرار
            $contract->validateDurationFrequency();
        });
        
        static::updating(function ($contract) {
            // Recalculate end_date if start_date or duration_months changed
            if ($contract->isDirty(['start_date', 'duration_months']) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
            
            // منع التحديث إذا كانت المدة لا تتوافق مع التكرار
            if ($contract->isDirty(['duration_months', 'payment_frequency'])) {
                $contract->validateDurationFrequency();
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
        if (isset($this->attributes['payments_count']) && !is_null($this->attributes['payments_count'])) {
            return $this->attributes['payments_count'];
        }

        return \App\Services\PropertyContractService::calculatePaymentsCount(
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
        // التحقق من وجود دفعات مولدة مسبقاً
        if ($this->supplyPayments()->exists()) {
            return false;
        }
        
        // التحقق من أن عدد الدفعات صحيح ورقمي
        if (!is_numeric($this->payments_count) || $this->payments_count <= 0) {
            return false;
        }
        
        // التحقق من أن المدة تقبل القسمة على التكرار
        return $this->isValidDurationForFrequency();
    }
    
    /**
     * Check if duration is valid for the payment frequency.
     */
    public function isValidDurationForFrequency(): bool
    {
        $monthsPerPayment = match($this->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };
        
        // التحقق من أن المدة تقبل القسمة بدون باقي
        return ($this->duration_months % $monthsPerPayment) === 0;
    }
    
    /**
     * Validate duration and frequency compatibility.
     * @throws \Exception if invalid
     */
    protected function validateDurationFrequency(): void
    {
        if (!$this->isValidDurationForFrequency()) {
            $frequencyLabel = match($this->payment_frequency) {
                'monthly' => 'شهري',
                'quarterly' => 'ربع سنوي (3 أشهر)',
                'semi_annually' => 'نصف سنوي (6 أشهر)',
                'annually' => 'سنوي (12 شهر)',
                default => $this->payment_frequency,
            };
            
            throw new \Exception(
                "مدة العقد ({$this->duration_months} شهر) لا تتوافق مع التكرار المحدد ({$frequencyLabel})"
            );
        }
    }
}