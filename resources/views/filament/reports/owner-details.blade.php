<div class="owner-report-container" style="background: white; padding: 24px; border-radius: 8px;">
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
        .report-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
            .owner-report-container {
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
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي للمالك</h1>
        <p style="color: #6b7280; margin-top: 8px;">{{ $stats['owner_name'] ?? 'غير محدد' }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات المالك الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات المالك</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الاسم:</span>
                <span class="info-value">{{ $stats['owner_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['owner_phone'])
            <div class="info-row">
                <span class="info-label">رقم الهاتف الأول:</span>
                <span class="info-value">{{ $stats['owner_phone'] }}</span>
            </div>
            @endif
            @if($stats['owner_secondary_phone'])
            <div class="info-row">
                <span class="info-label">رقم الهاتف الثاني:</span>
                <span class="info-value">{{ $stats['owner_secondary_phone'] }}</span>
            </div>
            @endif
            @if($stats['owner_email'])
            <div class="info-row">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $stats['owner_email'] }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">تاريخ التسجيل:</span>
                <span class="info-value">{{ $stats['created_at'] ? \Carbon\Carbon::parse($stats['created_at'])->format('Y/m/d') : 'غير محدد' }}</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: {{ $stats['is_active'] ? '#166534' : '#92400e' }}; font-weight: bold;">
                    {{ $stats['is_active'] ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
    </div>

    <!-- إحصائيات العقارات -->
    <div class="report-section">
        <h2 class="report-title">إحصائيات العقارات</h2>
        <div class="report-grid-4">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">عدد العقارات</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['properties_count'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">عقار</div>
            </div>
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">إجمالي الوحدات</div>
                <div class="stat-value" style="color: #166534;">{{ $stats['total_units'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #166534;">وحدة</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">الوحدات المؤجرة</div>
                <div class="stat-value" style="color: #92400e;">{{ $stats['occupied_units'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">وحدة</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">الوحدات الشاغرة</div>
                <div class="stat-value" style="color: #3730a3;">{{ $stats['vacant_units'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">وحدة</div>
            </div>
        </div>
        
        @if($stats['properties_list'] && is_array($stats['properties_list']) && count($stats['properties_list']) > 0)
        <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24; margin-top: 16px;">
            <h3 style="font-weight: 600; margin-bottom: 8px; color: #92400e;">قائمة العقارات:</h3>
            <p>{{ implode(', ', $stats['properties_list']) }}</p>
        </div>
        @endif
    </div>

    <!-- الإحصائيات المالية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية - آخر 12 شهر</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">إجمالي التحصيل</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['total_collection'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">الرسوم الإدارية</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['management_fees'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">المستحق للمالك</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['owner_due'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">ريال</div>
            </div>
        </div>

        <div class="report-grid-3" style="margin-top: 16px;">
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">المحول للمالك</div>
                <div class="stat-value" style="color: #166534;">{{ number_format($stats['paid_to_owner'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #166534;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">الرصيد المعلق</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['pending_balance'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">نسبة التحويل</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['transfer_rate'] ?? 0 }}%</div>
                <div style="font-size: 0.75rem; color: #164e63;">من المستحق</div>
            </div>
        </div>
    </div>

    <!-- معلومات العمليات -->
    <div class="report-section">
        <h2 class="report-title">معلومات العمليات المالية</h2>
        <div class="report-grid-4">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">إجمالي العمليات</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['total_operations'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">عملية</div>
            </div>
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">العمليات المكتملة</div>
                <div class="stat-value" style="color: #166534;">{{ $stats['completed_operations'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #166534;">عملية</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">معدل الإنجاز</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['completion_rate'] ?? 0 }}%</div>
                <div style="font-size: 0.75rem; color: #164e63;">من العمليات</div>
            </div>
            <div class="stat-box" style="background: #f3e8ff;">
                <div class="stat-label" style="color: #6b21a8;">متوسط الدخل الشهري</div>
                <div class="stat-value" style="color: #6b21a8;">{{ number_format($stats['average_monthly_income'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #6b21a8;">ريال</div>
            </div>
        </div>
    </div>

    <!-- آخر دفعة والدفعة القادمة -->
    <div class="report-section">
        <h2 class="report-title">العمليات الحديثة</h2>
        <div class="report-grid">
            <div style="background: #f0f9ff; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #0369a1;">آخر دفعة</h3>
                @if($stats['last_payment'])
                    <p style="color: #0369a1; margin-bottom: 4px;">
                        رقم العملية: {{ $stats['last_payment']['payment_number'] }}
                    </p>
                    <p style="color: #0369a1; margin-bottom: 4px;">
                        المبلغ: {{ number_format($stats['last_payment']['amount'], 0) }} ريال
                    </p>
                    <p style="color: #0369a1;">
                        التاريخ: {{ \Carbon\Carbon::parse($stats['last_payment']['payment_date'])->format('Y/m/d') }}
                    </p>
                @else
                    <p style="color: #6b7280;">لا توجد دفعات سابقة</p>
                @endif
            </div>
            
            <div style="background: #fef3c7; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #92400e;">الدفعة القادمة</h3>
                @if($stats['next_payment'])
                    <p style="color: #92400e; margin-bottom: 4px;">
                        رقم العملية: {{ $stats['next_payment']['payment_number'] }}
                    </p>
                    <p style="color: #92400e; margin-bottom: 4px;">
                        المبلغ: {{ number_format($stats['next_payment']['amount'], 0) }} ريال
                    </p>
                    <p style="color: #92400e;">
                        تاريخ الإنشاء: {{ \Carbon\Carbon::parse($stats['next_payment']['created_date'])->format('Y/m/d') }}
                    </p>
                @else
                    <p style="color: #6b7280;">لا توجد دفعات معلقة</p>
                @endif
            </div>
        </div>
    </div>

    <!-- معلومات إضافية -->
    <div class="report-section">
        <h2 class="report-title">تقييم الأداء</h2>
        <div class="report-grid">
            <div style="background: #f3e8ff; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #6b21a8;">نسبة الإشغال</h3>
                <p style="font-size: 1.25rem; font-weight: bold; color: #6b21a8;">
                    {{ $stats['occupancy_rate'] ?? 0 }}%
                </p>
            </div>
            <div style="background: #ecfdf5; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #065f46;">مستوى الأداء</h3>
                <p style="font-size: 1.25rem; font-weight: bold; color: #065f46;">
                    @switch($stats['performance_level'] ?? 'needs_attention')
                        @case('excellent')
                            ممتاز
                            @break
                        @case('good')
                            جيد
                            @break
                        @default
                            يحتاج تحسين
                    @endswitch
                </p>
            </div>
        </div>
    </div>

</div>