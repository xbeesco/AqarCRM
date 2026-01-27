<?php

namespace App\Services;

use App\Models\PropertyContract;
use Carbon\Carbon;

class PropertyContractValidationService
{
    /**
     * Validate start date does not fall within existing contract.
     */
    public function validateStartDate($propertyId, $startDate, $excludeId = null): ?string
    {
        if (! $propertyId || ! $startDate) {
            return null;
        }

        $startDate = Carbon::parse($startDate);

        // Find contracts containing this start date
        $query = PropertyContract::where('property_id', $propertyId)
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $startDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            return sprintf(
                'Start date falls within another contract from %s to %s',
                $overlapping->start_date->format('Y-m-d'),
                $overlapping->end_date->format('Y-m-d')
            );
        }

        return null;
    }

    /**
     * Validate duration does not cause overlap with end date.
     */
    public function validateDuration($propertyId, $startDate, $duration, $excludeId = null): ?string
    {
        if (! $propertyId || ! $startDate || ! $duration) {
            return null;
        }

        $startDate = Carbon::parse($startDate);
        $endDate = $startDate->copy()->addMonths((int) $duration)->subDay();

        // Find contracts that conflict with calculated end date
        $query = PropertyContract::where('property_id', $propertyId)
            ->where(function ($q) use ($startDate, $endDate) {
                // New contract ends within another contract
                $q->where(function ($q1) use ($endDate) {
                    $q1->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $endDate);
                })
                // Or new contract fully contains another contract
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '>=', $startDate)
                            ->where('end_date', '<=', $endDate);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            // Determine overlap type
            if ($endDate >= $overlapping->start_date && $endDate <= $overlapping->end_date) {
                return sprintf(
                    'Duration causes contract to end on %s which conflicts with contract from %s to %s',
                    $endDate->format('Y-m-d'),
                    $overlapping->start_date->format('Y-m-d'),
                    $overlapping->end_date->format('Y-m-d')
                );
            } else {
                return sprintf(
                    'Duration causes contract to fully contain another contract from %s to %s',
                    $overlapping->start_date->format('Y-m-d'),
                    $overlapping->end_date->format('Y-m-d')
                );
            }
        }

        return null;
    }

    /**
     * Full availability validation for Model/Observer use.
     */
    public function validateFullAvailability($propertyId, $startDate, $endDate, $excludeId = null): ?string
    {
        $query = PropertyContract::where('property_id', $propertyId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q1) use ($startDate, $endDate) {
                    // Any type of overlap
                    $q1->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            return sprintf(
                'Property is reserved by contract %s from %s to %s',
                $overlapping->contract_number,
                $overlapping->start_date->format('Y-m-d'),
                $overlapping->end_date->format('Y-m-d')
            );
        }

        return null;
    }
}
