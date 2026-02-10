<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Models\Tenant;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TenantService
{
    /**
     * Calculate total amount paid.
     */
    public function calculateTotalPaid(Tenant $tenant): float
    {
        return CollectionPayment::where('tenant_id', $tenant->id)
            ->collectedPayments()
            ->sum('total_amount');
    }

    /**
     * Calculate outstanding balance.
     */
    public function calculateOutstandingBalance(Tenant $tenant): float
    {
        return CollectionPayment::where('tenant_id', $tenant->id)
            ->whereNull('collection_date')
            ->where(function ($query) {
                $query->dueForCollection()
                    ->orWhere(function ($q) {
                        $q->overduePayments();
                    });
            })
            ->sum('total_amount');
    }

    /**
     * Check if tenant is in good standing.
     */
    public function isInGoodStanding(Tenant $tenant): bool
    {
        $overdueCount = CollectionPayment::where('tenant_id', $tenant->id)
            ->overduePayments()
            ->count();

        return $overdueCount === 0;
    }

    /**
     * Get tenant rating.
     */
    public function getTenantRating(Tenant $tenant): array
    {
        $payments = CollectionPayment::where('tenant_id', $tenant->id)->get();

        if ($payments->isEmpty()) {
            return [
                'score' => null,
                'label' => 'جديد',
                'color' => 'gray',
            ];
        }

        $totalPayments = $payments->count();
        $onTimePayments = $payments->filter(function ($payment) {
            if ($payment->collection_date === null) {
                return false;
            }

            return Carbon::parse($payment->collection_date)->lte(Carbon::parse($payment->due_date_end));
        })->count();

        $latePayments = $payments->filter(function ($payment) {
            if ($payment->collection_date === null) {
                return false;
            }

            return Carbon::parse($payment->collection_date)->gt(Carbon::parse($payment->due_date_end));
        })->count();

        $overduePayments = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE)->count();

        // Calculate score out of 100
        $score = 100;
        $score -= ($latePayments / max($totalPayments, 1)) * 30;
        $score -= ($overduePayments / max($totalPayments, 1)) * 50;

        $score = max(0, min(100, $score));

        // Determine rating
        if ($score >= 90) {
            return ['score' => $score, 'label' => 'ممتاز', 'color' => 'success'];
        } elseif ($score >= 75) {
            return ['score' => $score, 'label' => 'جيد', 'color' => 'info'];
        } elseif ($score >= 50) {
            return ['score' => $score, 'label' => 'مقبول', 'color' => 'warning'];
        } else {
            return ['score' => $score, 'label' => 'ضعيف', 'color' => 'danger'];
        }
    }

    /**
     * Get payment history.
     */
    public function getPaymentHistory(Tenant $tenant, ?int $limit = null): Collection
    {
        $query = CollectionPayment::where('tenant_id', $tenant->id)
            ->with(['unit', 'property'])
            ->orderBy('due_date_start', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get overdue payments.
     */
    public function getOverduePayments(Tenant $tenant): Collection
    {
        return CollectionPayment::where('tenant_id', $tenant->id)
            ->overduePayments()
            ->with(['unit', 'property'])
            ->orderBy('due_date_start')
            ->get();
    }

    /**
     * Get upcoming payments.
     */
    public function getUpcomingPayments(Tenant $tenant, int $days = 30): Collection
    {
        return CollectionPayment::where('tenant_id', $tenant->id)
            ->upcomingPayments()
            ->where('due_date_start', '<=', Carbon::now()->addDays($days))
            ->with(['unit', 'property'])
            ->orderBy('due_date_start')
            ->get();
    }

    /**
     * Get current contract.
     */
    public function getCurrentContract(Tenant $tenant): ?UnitContract
    {
        return UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    /**
     * Get all contracts.
     */
    public function getAllContracts(Tenant $tenant): Collection
    {
        return UnitContract::where('tenant_id', $tenant->id)
            ->with(['unit', 'property'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Check if tenant has an active contract.
     */
    public function hasActiveContract(Tenant $tenant): bool
    {
        return $this->getCurrentContract($tenant) !== null;
    }

    /**
     * Get comprehensive financial summary.
     */
    public function getFinancialSummary(Tenant $tenant): array
    {
        $payments = CollectionPayment::where('tenant_id', $tenant->id)->get();

        $collected = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::COLLECTED);
        $overdue = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE);
        $due = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::DUE);
        $upcoming = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::UPCOMING);
        $postponed = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::POSTPONED);

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected' => [
                'count' => $collected->count(),
                'amount' => $collected->sum('total_amount'),
            ],
            'overdue' => [
                'count' => $overdue->count(),
                'amount' => $overdue->sum('total_amount'),
            ],
            'due' => [
                'count' => $due->count(),
                'amount' => $due->sum('total_amount'),
            ],
            'upcoming' => [
                'count' => $upcoming->count(),
                'amount' => $upcoming->sum('total_amount'),
            ],
            'postponed' => [
                'count' => $postponed->count(),
                'amount' => $postponed->sum('total_amount'),
            ],
            'late_fees_total' => $payments->sum('late_fee'),
            'rating' => $this->getTenantRating($tenant),
        ];
    }

    /**
     * Get quick stats.
     */
    public function getQuickStats(Tenant $tenant): array
    {
        return [
            'total_paid' => $this->calculateTotalPaid($tenant),
            'outstanding_balance' => $this->calculateOutstandingBalance($tenant),
            'is_good_standing' => $this->isInGoodStanding($tenant),
            'has_active_contract' => $this->hasActiveContract($tenant),
            'overdue_count' => CollectionPayment::where('tenant_id', $tenant->id)
                ->overduePayments()
                ->count(),
        ];
    }

    /**
     * Search tenants by criteria.
     */
    public function searchTenants(array $criteria): Collection
    {
        $query = Tenant::query();

        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%'.$criteria['name'].'%');
        }

        if (isset($criteria['phone'])) {
            $query->where('phone', 'like', '%'.$criteria['phone'].'%');
        }

        if (isset($criteria['has_overdue']) && $criteria['has_overdue']) {
            $query->whereHas('collectionPayments', function ($q) {
                $q->overduePayments();
            });
        }

        if (isset($criteria['has_active_contract'])) {
            if ($criteria['has_active_contract']) {
                $query->whereHas('unitContracts', function ($q) {
                    $q->active();
                });
            } else {
                $query->whereDoesntHave('unitContracts', function ($q) {
                    $q->active();
                });
            }
        }

        if (isset($criteria['property_id'])) {
            $query->whereHas('unitContracts', function ($q) use ($criteria) {
                $q->where('property_id', $criteria['property_id']);
            });
        }

        return $query->get();
    }
}
