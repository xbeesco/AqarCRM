@php
    $property = $record;
    $totalUnits = $property->units()->count();
    $occupiedUnits = $property->units()->whereHas('activeContract')->count();
    $vacantUnits = $totalUnits - $occupiedUnits;
    $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
@endphp

<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 15mm 10mm;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .print-content {
            background: white !important;
        }

        .print-header {
            text-align: center;
            padding: 15px 0;
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
        }

        .print-header h2 {
            margin: 0;
            font-size: 16pt;
            color: #333;
        }

        .print-header p {
            margin: 5px 0 0 0;
            font-size: 11pt;
            color: #666;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            color: #666;
        }

        .section {
            page-break-inside: avoid;
        }
    }

    .print-only {
        display: none;
    }

    @media print {
        .print-only {
            display: block !important;
        }
    }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 12px;
        color: #6b7280;
    }
</style>

<div class="print-content">
    {{-- Header للطباعة فقط --}}
    <div class="print-only print-header">
        <h2>نظام إدارة العقارات</h2>
        <p>تقرير العقار</p>
        <p style="font-size: 10pt;">التاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d') }} | الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}</p>
    </div>

    {{-- معلومات العقار --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات العقار</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">اسم العقار</span>
                <p style="font-size: 18px; font-weight: bold; color: #111827; margin: 4px 0 0 0;">{{ $property->name }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">المالك</span>
                <p style="font-size: 16px; color: #2563eb; margin: 4px 0 0 0;">{{ $property->owner?->name ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">نوع العقار</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $property->propertyType?->name ?? 'غير محدد' }}</span>
                </p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">حالة العقار</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $property->propertyStatus?->name ?? 'غير محدد' }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- إحصائيات الإشغال --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">إحصائيات الإشغال</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div class="stat-card">
                <div class="stat-value" style="color: #2563eb;">{{ $totalUnits }}</div>
                <div class="stat-label">إجمالي الوحدات</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ $occupiedUnits }}</div>
                <div class="stat-label">وحدات مشغولة</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ca8a04;">{{ $vacantUnits }}</div>
                <div class="stat-label">وحدات شاغرة</div>
            </div>
            <div class="stat-card">
                @php
                    $rateColor = match(true) {
                        $occupancyRate >= 80 => '#16a34a',
                        $occupancyRate >= 50 => '#ca8a04',
                        default => '#dc2626',
                    };
                @endphp
                <div class="stat-value" style="color: {{ $rateColor }};">{{ $occupancyRate }}%</div>
                <div class="stat-label">نسبة الإشغال</div>
            </div>
        </div>
    </div>

    {{-- الإحصائيات المالية --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">الإحصائيات المالية</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ number_format($collectionTotal, 2) }}</div>
                <div class="stat-label">إجمالي التحصيل (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #2563eb;">{{ number_format($supplyTotal, 2) }}</div>
                <div class="stat-label">إجمالي التوريد (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ca8a04;">{{ number_format($generalReport['next_collection'] ?? 0, 2) }}</div>
                <div class="stat-label">الدفعة القادمة (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #374151;">{{ $commissionRate ?? 5 }}%</div>
                <div class="stat-label">نسبة الإدارة</div>
            </div>
        </div>
    </div>

    {{-- معلومات الموقع --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات الموقع</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">الموقع</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->location?->name ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">العنوان</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->address ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">الرمز البريدي</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->postal_code ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">سنة البناء</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->build_year ?? 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    {{-- تفاصيل إضافية --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">تفاصيل إضافية</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد الطوابق</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->floors_count ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد المواقف</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->parking_spots ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد المصاعد</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->elevators ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">ملاحظات</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $property->notes ?? 'لا توجد ملاحظات' }}</p>
            </div>
        </div>
    </div>

    {{-- Footer للطباعة فقط --}}
    <div class="print-only print-footer">
        <p>نظام إدارة العقارات © {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</div>
