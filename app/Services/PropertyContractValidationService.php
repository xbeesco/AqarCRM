<?php

namespace App\Services;

use App\Models\PropertyContract;
use Carbon\Carbon;

class PropertyContractValidationService
{
    /**
     * التحقق من تاريخ البداية فقط
     * يُظهر خطأ فقط إذا كان التاريخ نفسه يقع داخل عقد آخر
     */
    public function validateStartDate($propertyId, $startDate, $excludeId = null): ?string
    {
        if (!$propertyId || !$startDate) {
            return null;
        }

        $startDate = Carbon::parse($startDate);
        
        // البحث عن عقود تحتوي على تاريخ البداية هذا
        $query = PropertyContract::where('property_id', $propertyId)
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
    public function validateDuration($propertyId, $startDate, $duration, $excludeId = null): ?string
    {
        if (!$propertyId || !$startDate || !$duration) {
            return null;
        }
        
        $startDate = Carbon::parse($startDate);
        $endDate = $startDate->copy()->addMonths((int)$duration)->subDay();
        
        // البحث عن عقود يتعارض معها تاريخ النهاية المحسوب
        $query = PropertyContract::where('property_id', $propertyId)
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
    public function validateFullAvailability($propertyId, $startDate, $endDate, $excludeId = null): ?string
    {
        $query = PropertyContract::where('property_id', $propertyId)
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
                'العقار محجوز بالعقد رقم %s من %s إلى %s',
                $overlapping->contract_number,
                $overlapping->start_date->format('Y-m-d'),
                $overlapping->end_date->format('Y-m-d')
            );
        }
        
        return null;
    }
}