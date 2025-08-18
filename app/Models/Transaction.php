<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'type',
        'transactionable_type',
        'transactionable_id',
        'property_id',
        'debit_amount',
        'credit_amount',
        'balance',
        'description',
        'transaction_date',
        'reference_number',
        'meta_data',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'date',
        'meta_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
            
            // Calculate balance (simplified - in real system would be more complex)
            $transaction->balance = $transaction->debit_amount - $transaction->credit_amount;
        });
    }

    // Relationships
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    // Methods
    public static function generateTransactionNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'TXN-' . $year . '-' . str_pad($count, 8, '0', STR_PAD_LEFT);
    }

    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }

    public function getNetAmount(): float
    {
        return $this->debit_amount - $this->credit_amount;
    }

    public static function calculatePropertyBalance($propertyId, $asOfDate = null): float
    {
        $query = self::where('property_id', $propertyId);
        
        if ($asOfDate) {
            $query->where('transaction_date', '<=', $asOfDate);
        }
        
        $totals = $query->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
                       ->first();
        
        return ($totals->total_debits ?? 0) - ($totals->total_credits ?? 0);
    }

    public static function getPropertyTransactionSummary($propertyId, $startDate = null, $endDate = null): array
    {
        $query = self::where('property_id', $propertyId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }
        
        $summary = $query->selectRaw('
            type,
            COUNT(*) as count,
            SUM(debit_amount) as total_debits,
            SUM(credit_amount) as total_credits,
            SUM(debit_amount - credit_amount) as net_amount
        ')->groupBy('type')->get();
        
        return $summary->keyBy('type')->toArray();
    }
}