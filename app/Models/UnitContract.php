<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UnitContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'tenant_id',
        'unit_id',
        'property_id',
        'monthly_rent',
        'security_deposit',
        'duration_months',
        'start_date',
        'end_date',
        'contract_status',
        'payment_frequency',
        'payment_method',
        'grace_period_days',
        'late_fee_rate',
        'utilities_included',
        'furnished',
        'evacuation_notice_days',
        'terms_and_conditions',
        'special_conditions',
        'notes',
        'file',
        'created_by',
        'approved_by',
        'approved_at',
        'terminated_reason',
        'terminated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'duration_months' => 'integer',
        'grace_period_days' => 'integer',
        'late_fee_rate' => 'decimal:2',
        'utilities_included' => 'boolean',
        'furnished' => 'boolean',
        'evacuation_notice_days' => 'integer',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            // Generate contract number if not set
            if (empty($contract->contract_number)) {
                // استخدام timestamp بالميلي ثانية لضمان عدم التكرار
                $contract->contract_number = 'UC-' . round(microtime(true) * 1000);
            }
            
            // Calculate end_date if not set
            if (empty($contract->end_date) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
            
            // Set default values if not provided
            if (is_null($contract->security_deposit)) {
                $contract->security_deposit = $contract->monthly_rent ?? 0;
            }
            
            if (is_null($contract->grace_period_days)) {
                $contract->grace_period_days = 5;
            }
            
            if (is_null($contract->late_fee_rate)) {
                $contract->late_fee_rate = 5.00;
            }
            
            if (is_null($contract->evacuation_notice_days)) {
                $contract->evacuation_notice_days = 30;
            }
            
            if (is_null($contract->contract_status)) {
                $contract->contract_status = 'active';
            }
        });
        
        static::updating(function ($contract) {
            // Recalculate end_date if start_date or duration_months changed
            if ($contract->isDirty(['start_date', 'duration_months']) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
        });
    }

    /**
     * Get total contract value.
     */
    public function getTotalContractValue(): float
    {
        return $this->monthly_rent * $this->duration_months;
    }

    /**
     * Relationship: Contract belongs to tenant (user).
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Relationship: Contract belongs to unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: Contract belongs to property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    /**
     * Relationship: Contract has many payments.
     */
    public function payments()
    {
        return $this->hasMany(CollectionPayment::class, 'unit_contract_id');
    }
    
    /**
     * Scope: Active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('contract_status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }
    
    /**
     * Scope: Draft contracts.
     */
    public function scopeDraft($query)
    {
        return $query->where('contract_status', 'draft');
    }
    
    /**
     * Scope: Expired contracts.
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('contract_status', 'expired')
              ->orWhere(function($q2) {
                  $q2->where('contract_status', 'active')
                     ->where('end_date', '<', now());
              });
        });
    }
    
    /**
     * Scope: Terminated contracts.
     */
    public function scopeTerminated($query)
    {
        return $query->where('contract_status', 'terminated');
    }
    
    /**
     * Scope: Renewed contracts.
     */
    public function scopeRenewed($query)
    {
        return $query->where('contract_status', 'renewed');
    }
    
    /**
     * Scope: Contracts expiring soon (within N days).
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('contract_status', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
    
    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->contract_status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }
    
    /**
     * Check if contract has expired.
     */
    public function hasExpired(): bool
    {
        return $this->contract_status === 'expired' 
            || ($this->contract_status === 'active' && $this->end_date < now());
    }
    
    /**
     * Check if contract is draft.
     */
    public function isDraft(): bool
    {
        return $this->contract_status === 'draft';
    }
    
    /**
     * Check if contract was terminated.
     */
    public function isTerminated(): bool
    {
        return $this->contract_status === 'terminated';
    }
    
    /**
     * Check if contract was renewed.
     */
    public function isRenewed(): bool
    {
        return $this->contract_status === 'renewed';
    }
    
    /**
     * Get remaining days.
     */
    public function getRemainingDays(): int
    {
        if (!$this->isActive()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->end_date, false));
    }
    
    /**
     * Get status badge color for UI.
     */
    public function getStatusColor(): string
    {
        return match($this->contract_status) {
            'draft' => 'gray',
            'active' => $this->end_date < now()->addDays(30) ? 'warning' : 'success',
            'expired' => 'danger',
            'terminated' => 'danger',
            'renewed' => 'info',
            default => 'secondary'
        };
    }
    
    /**
     * Get status label in Arabic.
     */
    public function getStatusLabel(): string
    {
        return match($this->contract_status) {
            'draft' => 'مسودة',
            'active' => 'نشط',
            'expired' => 'منتهي',
            'terminated' => 'ملغي',
            'renewed' => 'مُجدد',
            default => $this->contract_status
        };
    }
    
    /**
     * Get payments count attribute dynamically.
     */
    public function getPaymentsCountAttribute()
    {
        return \App\Services\PropertyContractService::calculatePaymentsCount(
            $this->duration_months ?? 0,
            $this->payment_frequency ?? 'monthly'
        );
    }
    
    /**
     * Check if contract can have payments generated.
     */
    public function canGeneratePayments(): bool
    {
        // التحقق من وجود دفعات مولدة مسبقاً
        if ($this->payments()->exists()) {
            return false;
        }
        
        // التحقق من أن عدد الدفعات صحيح ورقمي
        $paymentsCount = $this->payments_count;
        if (!is_numeric($paymentsCount) || $paymentsCount <= 0) {
            return false;
        }
        
        // التحقق من البيانات المطلوبة
        return $this->tenant_id && 
               $this->unit_id && 
               $this->monthly_rent > 0 &&
               $this->start_date &&
               $this->end_date;
    }
    
    /**
     * Get paid payments.
     */
    public function getPaidPayments()
    {
        return $this->payments()
            ->paid()  // استخدام الـ scope الجديد
            ->orderBy('due_date_start')
            ->get();
    }
    
    /**
     * Get unpaid payments.
     */
    public function getUnpaidPayments()
    {
        return $this->payments()
            ->unpaid()  // استخدام الـ scope الجديد
            ->orderBy('due_date_start')
            ->get();
    }
    
    /**
     * Get last paid date.
     */
    public function getLastPaidDate(): ?Carbon
    {
        $lastPaidPayment = $this->payments()
            ->paid()  // استخدام الـ scope الجديد
            ->orderBy('due_date_end', 'desc')
            ->first();
            
        return $lastPaidPayment ? Carbon::parse($lastPaidPayment->due_date_end) : null;
    }
    
    /**
     * Check if contract can be rescheduled.
     */
    public function canReschedule(): bool
    {
        // يمكن إعادة الجدولة إذا:
        // 1. العقد نشط أو مسودة
        if (!in_array($this->contract_status, ['active', 'draft'])) {
            return false;
        }
        
        // 2. يوجد دفعات (سواء مدفوعة أو غير مدفوعة)
        if (!$this->payments()->exists()) {
            return false;
        }
        
        // 3. ليست كل الدفعات مدفوعة (أو يمكن إضافة دفعات جديدة حتى لو كلها مدفوعة)
        // نسمح بإعادة الجدولة حتى لو كل الدفعات مدفوعة لإضافة فترة جديدة
        
        return true;
    }
    
    /**
     * Get count of paid months.
     */
    public function getPaidMonthsCount(): int
    {
        $paidPayments = $this->getPaidPayments();
        
        if ($paidPayments->isEmpty()) {
            return 0;
        }
        
        $totalDays = 0;
        foreach ($paidPayments as $payment) {
            $start = Carbon::parse($payment->due_date_start);
            $end = Carbon::parse($payment->due_date_end);
            $totalDays += $start->diffInDays($end) + 1;
        }
        
        // تحويل الأيام إلى أشهر (تقريبياً 30 يوم للشهر)
        return intval($totalDays / 30);
    }
    
    /**
     * Get remaining months that can be modified.
     */
    public function getRemainingMonths(): int
    {
        $totalMonths = $this->duration_months ?? 0;
        $paidMonths = $this->getPaidMonthsCount();
        
        return max(0, $totalMonths - $paidMonths);
    }
    
    /**
     * Get count of paid payments.
     */
    public function getPaidPaymentsCount(): int
    {
        return $this->payments()
            ->paid()  // استخدام الـ scope الجديد
            ->count();
    }
    
    /**
     * Get count of unpaid payments.
     */
    public function getUnpaidPaymentsCount(): int
    {
        return $this->payments()
            ->unpaid()  // استخدام الـ scope الجديد
            ->count();
    }
}