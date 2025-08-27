<?php

namespace App\Services;

use App\Models\UnitContract;
use Carbon\Carbon;

class ContractValidationService
{
    /**
     * التحقق من تاريخ البداية فقط
     * يُظهر خطأ فقط إذا كان التاريخ نفسه يقع داخل عقد آخر
     */
    public function validateStartDate($unitId, $startDate, $excludeId = null): ?string
    {
        if (!$unitId || !$startDate) {
            return null;
        }

        $startDate = Carbon::parse($startDate);
        
        // البحث عن عقود تحتوي على تاريخ البداية هذا
        $query = UnitContract::where('unit_id', $unitId)
            ->whereIn('contract_status', ['active', 'renewed', 'draft'])
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $startDate);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $overlapping = $query->first();
        
        if ($overlapping) {
            return sprintf(
                'تاريخ البداية يقع داخل عقد آخر من %s إلى %s',
                $overlapping->start_date->format('Y-m-d'),
                $overlapping->end_date->format('Y-m-d')
            );
        }
        
        return null;
    }
    
    /**
     * التحقق من المدة فقط
     * يُظهر خطأ إذا كانت المدة ستؤدي لتداخل مع نهاية العقد
     */
    public function validateDuration($unitId, $startDate, $duration, $excludeId = null): ?string
    {
        if (!$unitId || !$startDate || !$duration) {
            return null;
        }
        
        $startDate = Carbon::parse($startDate);
        $endDate = $startDate->copy()->addMonths((int)$duration)->subDay();
        
        // البحث عن عقود يتعارض معها تاريخ النهاية المحسوب
        $query = UnitContract::where('unit_id', $unitId)
            ->whereIn('contract_status', ['active', 'renewed', 'draft'])
            ->where(function($q) use ($startDate, $endDate) {
                // العقد الجديد ينتهي داخل عقد آخر
                $q->where(function($q1) use ($endDate) {
                    $q1->where('start_date', '<=', $endDate)
                       ->where('end_date', '>=', $endDate);
                })
                // أو العقد الجديد يحتوي على عقد آخر بالكامل
                ->orWhere(function($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '>=', $startDate)
                       ->where('end_date', '<=', $endDate);
                });
            });
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $overlapping = $query->first();
        
        if ($overlapping) {
            // تحديد نوع التداخل
            if ($endDate >= $overlapping->start_date && $endDate <= $overlapping->end_date) {
                return sprintf(
                    'المدة ستجعل العقد ينتهي في %s وهو يتعارض مع عقد آخر من %s إلى %s',
                    $endDate->format('Y-m-d'),
                    $overlapping->start_date->format('Y-m-d'),
                    $overlapping->end_date->format('Y-m-d')
                );
            } else {
                return sprintf(
                    'المدة ستجعل العقد يغطي عقداً آخر بالكامل من %s إلى %s',
                    $overlapping->start_date->format('Y-m-d'),
                    $overlapping->end_date->format('Y-m-d')
                );
            }
        }
        
        return null;
    }
    
    /**
     * التحقق الكامل للتوافق (للاستخدام في Model/Observer)
     */
    public function validateFullAvailability($unitId, $startDate, $endDate, $excludeId = null): ?string
    {
        $query = UnitContract::where('unit_id', $unitId)
            ->whereIn('contract_status', ['active', 'renewed', 'draft'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q1) use ($startDate, $endDate) {
                    // أي نوع من التداخل
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
                'الوحدة محجوزة بالعقد رقم %s من %s إلى %s',
                $overlapping->contract_number,
                $overlapping->start_date->format('Y-m-d'),
                $overlapping->end_date->format('Y-m-d')
            );
        }
        
        return null;
    }
}