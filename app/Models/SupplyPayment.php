<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SupplyPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'property_contract_id',
        'owner_id',
        'gross_amount',
        'commission_amount',
        'commission_rate',
        'maintenance_deduction',
        'other_deductions',
        'net_amount',
        'supply_status',
        'due_date',
        'paid_date',
        'collected_by',
        'delay_duration',
        'delay_reason',
        'approval_status',
        'approved_by',
        'approved_at',
        'bank_transfer_reference',
        'invoice_details',
        'deduction_details',
        'month_year',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'maintenance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'approved_at' => 'datetime',
        'invoice_details' => 'array',
        'deduction_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // Calculate net amount
            $payment->net_amount = $payment->calculateNetAmount();
        });

        static::updating(function ($payment) {
            // Recalculate net amount
            $payment->net_amount = $payment->calculateNetAmount();
        });
    }

    // Relationships
    public function propertyContract(): BelongsTo
    {
        return $this->belongsTo(PropertyContract::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // Scopes
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopePending($query)
    {
        return $query->where('supply_status', 'pending');
    }

    public function scopeWorthCollecting($query)
    {
        return $query->where('supply_status', 'worth_collecting');
    }

    public function scopeCollected($query)
    {
        return $query->where('supply_status', 'collected');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeByMonth($query, $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }

    // Methods
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'SUPPLY-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function calculateNetAmount(): float
    {
        return $this->gross_amount - $this->commission_amount - $this->maintenance_deduction - $this->other_deductions;
    }

    public function calculateCommission(): float
    {
        return round(($this->gross_amount * $this->commission_rate) / 100, 2);
    }

    public function approve($approverId): bool
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'supply_status' => 'worth_collecting',
        ]);

        return true;
    }

    public function reject($approverId, $reason = null): bool
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes' => $reason ? "Rejected: {$reason}" : 'Rejected',
        ]);

        return true;
    }

    public function processPayment($bankTransferReference = null): bool
    {
        $this->update([
            'supply_status' => 'collected',
            'paid_date' => now()->toDateString(),
            'bank_transfer_reference' => $bankTransferReference,
        ]);

        // Create transaction record
        $this->createTransaction();

        return true;
    }

    public function requiresApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function getDeductionBreakdown(): array
    {
        return [
            'commission' => [
                'amount' => $this->commission_amount,
                'rate' => $this->commission_rate . '%',
                'description' => 'Management commission',
            ],
            'maintenance' => [
                'amount' => $this->maintenance_deduction,
                'description' => 'Maintenance and repairs',
            ],
            'other' => [
                'amount' => $this->other_deductions,
                'description' => 'Other deductions',
                'details' => $this->deduction_details,
            ]
        ];
    }

    private function createTransaction(): void
    {
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'supply_payment',
            'transactionable_type' => SupplyPayment::class,
            'transactionable_id' => $this->id,
            'property_id' => $this->propertyContract->property_id ?? null,
            'debit_amount' => 0.00,
            'credit_amount' => $this->net_amount,
            'description' => "Owner payment for property contract - {$this->month_year}",
            'transaction_date' => $this->paid_date,
            'reference_number' => $this->payment_number,
            'meta_data' => [
                'owner_name' => $this->owner->name,
                'gross_amount' => $this->gross_amount,
                'deductions' => $this->getDeductionBreakdown(),
                'bank_reference' => $this->bank_transfer_reference,
            ]
        ]);
    }
}