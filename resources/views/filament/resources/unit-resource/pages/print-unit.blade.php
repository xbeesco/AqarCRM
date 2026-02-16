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
        <p>تقرير الوحدة</p>
        <p style="font-size: 10pt;">التاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d') }} | الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}</p>
    </div>

    {{-- معلومات الوحدة --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات الوحدة</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">اسم الوحدة</span>
                <p style="font-size: 18px; font-weight: bold; color: #111827; margin: 4px 0 0 0;">{{ $unit->name }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">العقار</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $unit->property?->name ?? 'غير محدد' }}</span>
                </p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">نوع الوحدة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $unit->unitType?->name ?? 'غير محدد' }}</span>
                </p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">تصنيف الوحدة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->unitCategory?->name ?? 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    {{-- حالة الإشغال --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">حالة الإشغال</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div class="stat-card">
                @php
                    $statusColor = $unit->occupancy_status->color();
                    $colorMap = [
                        'success' => '#16a34a',
                        'warning' => '#ca8a04',
                        'danger' => '#dc2626',
                        'gray' => '#6b7280',
                        'primary' => '#2563eb',
                    ];
                @endphp
                <div class="stat-value" style="color: {{ $colorMap[$statusColor] ?? '#6b7280' }};">{{ $unit->occupancy_status->label() }}</div>
                <div class="stat-label">حالة الوحدة</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #111827; font-size: 18px;">{{ $unit->activeContract?->tenant?->name ?? 'لا يوجد' }}</div>
                <div class="stat-label">المستأجر الحالي</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ $unit->activeContract ? number_format($unit->activeContract->monthly_rent, 2) : '-' }}</div>
                <div class="stat-label">الإيجار الشهري الحالي (ر.س)</div>
            </div>
        </div>
    </div>

    {{-- مواصفات الوحدة --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">مواصفات الوحدة</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">سعر الإيجار</span>
                <p style="font-size: 16px; font-weight: bold; color: #ca8a04; margin: 4px 0 0 0;">{{ $unit->rent_price ? number_format($unit->rent_price, 2) . ' ر.س' : '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">المساحة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->area_sqm ? $unit->area_sqm . ' م²' : '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">رقم الطابق</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->floor_number ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد الغرف</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->rooms_count ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد دورات المياه</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->bathrooms_count ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">عدد الشرفات</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->balconies_count ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">غرفة غسيل</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: {{ $unit->has_laundry_room ? '#d1fae5' : '#f3f4f6' }}; color: {{ $unit->has_laundry_room ? '#065f46' : '#374151' }}; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $unit->has_laundry_room ? 'نعم' : 'لا' }}</span>
                </p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">غرفة خادمة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: {{ $unit->has_maid_room ? '#d1fae5' : '#f3f4f6' }}; color: {{ $unit->has_maid_room ? '#065f46' : '#374151' }}; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $unit->has_maid_room ? 'نعم' : 'لا' }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- معلومات الخدمات --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات الخدمات</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">رقم حساب الكهرباء</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->electricity_account_number ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">رقم عداد المياه</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->water_meter_number ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">مصروف المياه</span>
                <p style="font-size: 16px; color: #0369a1; margin: 4px 0 0 0;">{{ $unit->water_expenses ? number_format($unit->water_expenses, 2) . ' ر.س' : '-' }}</p>
            </div>
        </div>
    </div>

    {{-- معلومات العقار --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات العقار</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">المالك</span>
                <p style="font-size: 16px; font-weight: bold; color: #111827; margin: 4px 0 0 0;">{{ $unit->property?->owner?->name ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">الموقع</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->property?->location?->name ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">نوع العقار</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->property?->propertyType?->name ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">العنوان</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $unit->property?->address ?? 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    {{-- ملاحظات --}}
    @if($unit->notes)
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">ملاحظات</h3>
        <p style="font-size: 14px; color: #6b7280; margin: 0;">{{ $unit->notes }}</p>
    </div>
    @endif

    {{-- Footer للطباعة فقط --}}
    <div class="print-only print-footer">
        <p>نظام إدارة العقارات © {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</div>
