<div class="property-report-container" style="background: white; padding: 24px; border-radius: 8px;">
    <!-- Header للطباعة -->
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .report-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .report-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .report-section {
            margin-bottom: 24px;
        }
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #f59e0b;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .stat-box {
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-label {
            font-size: 0.875rem;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 150px;
        }
        .info-value {
            color: #111827;
        }
        @media print {
            .property-report-container {
                width: 100%;
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>

    <!-- عنوان التقرير -->
    <div style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي للعقار</h1>
        <p style="color: #6b7280; margin-top: 8px;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات العقار الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات العقار</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">اسم العقار:</span>
                <span class="info-value">{{ $property->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">المالك:</span>
                <span class="info-value">{{ $property->owner?->name ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الموقع:</span>
                <span class="info-value">{{ $property->location?->name_ar ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">العنوان:</span>
                <span class="info-value">{{ $property->address ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">عدد الطوابق:</span>
                <span class="info-value">{{ $property->floors_count ?? 0 }}</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">سنة البناء:</span>
                <span class="info-value">{{ $property->build_year ?? 'غير محدد' }}</span>
            </div>
        </div>
    </div>

    <!-- إحصائيات الوحدات -->
    <div class="report-section">
        <h2 class="report-title">إحصائيات الوحدات</h2>
        <div class="report-grid-4">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">إجمالي الوحدات</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['total_units'] }}</div>
            </div>
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">وحدات مؤجرة</div>
                <div class="stat-value" style="color: #166534;">{{ $stats['occupied_units'] }}</div>
            </div>
            <div class="stat-box" style="background: #fee2e2;">
                <div class="stat-label" style="color: #991b1b;">وحدات شاغرة</div>
                <div class="stat-value" style="color: #991b1b;">{{ $stats['vacant_units'] }}</div>
            </div>
            <div class="stat-box" style="background: #f3e8ff;">
                <div class="stat-label" style="color: #6b21a8;">نسبة الإشغال</div>
                <div class="stat-value" style="color: #6b21a8;">{{ $stats['occupancy_rate'] }}%</div>
            </div>
        </div>
    </div>

    <!-- الإحصائيات المالية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">الإيراد الشهري</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['monthly_revenue'], 0) }} <span style="font-size: 0.875rem;">ريال</span></div>
            </div>
            <div class="stat-box" style="background: #ccfbf1;">
                <div class="stat-label" style="color: #134e4a;">الإيراد السنوي</div>
                <div class="stat-value" style="color: #134e4a;">{{ number_format($stats['yearly_revenue'], 0) }} <span style="font-size: 0.875rem;">ريال</span></div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">صافي الدخل</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['net_income'], 0) }} <span style="font-size: 0.875rem;">ريال</span></div>
            </div>
        </div>
    </div>

    <!-- المستحقات والصيانة -->
    <div class="report-section">
        <h2 class="report-title">المستحقات والمصروفات</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">المستحقات المعلقة</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['pending_payments'], 0) }} <span style="font-size: 0.875rem;">ريال</span></div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">تكاليف الصيانة السنوية</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['maintenance_costs'], 0) }} <span style="font-size: 0.875rem;">ريال</span></div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">العقود النشطة</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['active_contracts'] }} <span style="font-size: 0.875rem;">عقد</span></div>
            </div>
        </div>
    </div>

    <!-- قائمة الوحدات -->
    @if($property->units->count() > 0)
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-4 text-primary-600 border-b pb-2">تفاصيل الوحدات</h2>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-right">الوحدة</th>
                    <th class="px-3 py-2 text-center">الطابق</th>
                    <th class="px-3 py-2 text-center">الغرف</th>
                    <th class="px-3 py-2 text-center">المساحة</th>
                    <th class="px-3 py-2 text-center">الإيجار</th>
                    <th class="px-3 py-2 text-center">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($property->units as $unit)
                <tr>
                    <td class="px-3 py-2">{{ $unit->name }}</td>
                    <td class="px-3 py-2 text-center">{{ $unit->floor_number ?? '-' }}</td>
                    <td class="px-3 py-2 text-center">{{ $unit->rooms_count ?? '-' }}</td>
                    <td class="px-3 py-2 text-center">{{ $unit->area_sqm ?? '-' }} م²</td>
                    <td class="px-3 py-2 text-center">{{ number_format($unit->rent_price ?? 0, 0) }} ريال</td>
                    <td class="px-3 py-2 text-center">
                        @php
                            $hasActiveContract = $unit->contracts()
                                ->where('contract_status', 'active')
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->exists();
                        @endphp
                        @if($hasActiveContract)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">مؤجرة</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">شاغرة</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- توقيع وختم -->
    <div class="mt-8 pt-4 border-t">
        <div class="grid grid-cols-2 gap-8">
            <div class="text-center">
                <div class="mb-8"></div>
                <div class="border-t pt-2">
                    <p class="text-sm text-gray-600">التوقيع</p>
                </div>
            </div>
            <div class="text-center">
                <div class="mb-8"></div>
                <div class="border-t pt-2">
                    <p class="text-sm text-gray-600">الختم</p>
                </div>
            </div>
        </div>
    </div>
</div>