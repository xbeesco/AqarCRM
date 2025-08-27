<div class="unit-report-container" style="background: white; padding: 24px; border-radius: 8px;">
    <!-- Header للطباعة -->
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            .unit-report-container {
                width: 100%;
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- عنوان التقرير -->
    <div style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي للوحدة</h1>
        <p style="color: #6b7280; margin-top: 8px;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات الوحدة الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات الوحدة</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">اسم الوحدة:</span>
                <span class="info-value">{{ $unit->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">العقار:</span>
                <span class="info-value">{{ $stats['property_name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الطابق:</span>
                <span class="info-value">{{ $stats['floor_number'] ?? 'الأرضي' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">عدد الغرف:</span>
                <span class="info-value">{{ $stats['rooms_count'] ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">المساحة:</span>
                <span class="info-value">{{ $stats['area_sqm'] ?? 'غير محدد' }} م²</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">سعر الإيجار:</span>
                <span class="info-value">{{ number_format($stats['rent_price'], 0) }} ريال/شهر</span>
            </div>
        </div>
    </div>

    <!-- حالة الإشغال -->
    <div class="report-section">
        <h2 class="report-title">حالة الإشغال</h2>
        @if($stats['is_occupied'])
        <div style="background: #dcfce7; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: #166534; font-weight: bold;">مؤجرة</span>
            </div>
            <div class="info-row">
                <span class="info-label">المستأجر:</span>
                <span class="info-value">{{ $stats['current_tenant'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">بداية العقد:</span>
                <span class="info-value">{{ Carbon\Carbon::parse($stats['contract_start'])->format('Y/m/d') }}</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">نهاية العقد:</span>
                <span class="info-value">
                    {{ Carbon\Carbon::parse($stats['contract_end'])->format('Y/m/d') }}
                    @php
                        $daysRemaining = now()->diffInDays($stats['contract_end'], false);
                    @endphp
                    @if($daysRemaining < 30)
                        <span style="color: #dc2626; font-weight: bold;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @endif
                </span>
            </div>
        </div>
        @else
        <div style="background: #fee2e2; padding: 16px; border-radius: 8px;">
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: #991b1b; font-weight: bold;">شاغرة</span>
            </div>
        </div>
        @endif
    </div>

    <!-- الإحصائيات المالية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">إجمالي الإيرادات</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['total_revenue'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">المستحقات</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['pending_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">تكاليف الصيانة</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['maintenance_costs'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال</div>
            </div>
        </div>
        <div class="report-grid">
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">صافي الدخل</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['net_income'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">ريال</div>
            </div>
            <div class="stat-box" style="background: #f3e8ff;">
                <div class="stat-label" style="color: #6b21a8;">الإيراد السنوي المتوقع</div>
                <div class="stat-value" style="color: #6b21a8;">{{ number_format($stats['rent_price'] * 12, 0) }}</div>
                <div style="font-size: 0.75rem; color: #6b21a8;">ريال</div>
            </div>
        </div>
    </div>

    <!-- تاريخ الإيجار -->
    <div class="report-section">
        <h2 class="report-title">تاريخ الإيجار</h2>
        <div class="report-grid">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">العقود السابقة</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['previous_contracts'] }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">عقد</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">متوسط مدة الإيجار</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['avg_contract_months'] }}</div>
                <div style="font-size: 0.75rem; color: #164e63;">شهر</div>
            </div>
        </div>
    </div>

</div>