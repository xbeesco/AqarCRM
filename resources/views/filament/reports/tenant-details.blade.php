<div class="tenant-report-container" style="background: white; padding: 24px; border-radius: 8px;">
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
            .tenant-report-container {
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
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي للمستأجر</h1>
        <p style="color: #6b7280; margin-top: 8px;">{{ $stats['tenant_name'] }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات المستأجر الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات المستأجر</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الاسم الكامل:</span>
                <span class="info-value">{{ $stats['tenant_name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الهاتف الأول:</span>
                <span class="info-value">{{ $stats['tenant_phone'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['tenant_secondary_phone'])
            <div class="info-row">
                <span class="info-label">الهاتف الثاني:</span>
                <span class="info-value">{{ $stats['tenant_secondary_phone'] }}</span>
            </div>
            @endif
            @if($stats['tenant_email'])
            <div class="info-row">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $stats['tenant_email'] }}</span>
            </div>
            @endif
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">تاريخ التسجيل:</span>
                <span class="info-value">{{ $stats['created_at'] ? \Carbon\Carbon::parse($stats['created_at'])->format('Y/m/d') : 'غير محدد' }}</span>
            </div>
        </div>
    </div>

    <!-- حالة الإيجار الحالية -->
    <div class="report-section">
        <h2 class="report-title">حالة الإيجار</h2>
        @if($stats['has_active_contract'])
        @php
            $contract = $stats['current_contract'];
            $daysRemaining = $contract['days_remaining'];
        @endphp
        <div style="background: #dcfce7; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: #166534; font-weight: bold;">مستأجر نشط</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم العقد:</span>
                <span class="info-value">{{ $contract['contract_number'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">بداية العقد:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($contract['start_date'])->format('Y/m/d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">نهاية العقد:</span>
                <span class="info-value">
                    {{ \Carbon\Carbon::parse($contract['end_date'])->format('Y/m/d') }}
                    @if($daysRemaining < 30)
                        <span style="color: #dc2626; font-weight: bold;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @elseif($daysRemaining < 60)
                        <span style="color: #f59e0b;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">الإيجار الشهري:</span>
                <span class="info-value">{{ number_format($contract['monthly_rent'], 0) }} ريال</span>
            </div>
            @if($contract['security_deposit'])
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">مبلغ التأمين:</span>
                <span class="info-value">{{ number_format($contract['security_deposit'], 0) }} ريال</span>
            </div>
            @endif
        </div>
        @else
        <div style="background: #fef3c7; padding: 16px; border-radius: 8px;">
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: #92400e; font-weight: bold;">بدون عقد نشط</span>
            </div>
        </div>
        @endif
    </div>

    <!-- معلومات الوحدة والعقار الحالي -->
    @if($stats['current_unit'] && $stats['current_property'])
    <div class="report-section">
        <h2 class="report-title">معلومات الوحدة والعقار</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">العقار:</span>
                <span class="info-value">{{ $stats['current_property']['name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الوحدة:</span>
                <span class="info-value">{{ $stats['current_unit']['name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الطابق:</span>
                <span class="info-value">{{ $stats['current_unit']['floor_number'] ?? 'الأرضي' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">عدد الغرف:</span>
                <span class="info-value">{{ $stats['current_unit']['rooms_count'] ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">المساحة:</span>
                <span class="info-value">{{ $stats['current_unit']['area_sqm'] ?? 'غير محدد' }} م²</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">سعر الإيجار:</span>
                <span class="info-value">{{ number_format($stats['current_unit']['rent_price'], 0) }} ريال/شهر</span>
            </div>
        </div>
    </div>
    @endif

    <!-- الإحصائيات المالية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">إجمالي المدفوعات</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['total_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">المستحقات المتبقية</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['outstanding_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fee2e2;">
                <div class="stat-label" style="color: #991b1b;">عدد المتأخرات</div>
                <div class="stat-value" style="color: #991b1b;">{{ $stats['overdue_count'] }}</div>
                <div style="font-size: 0.75rem; color: #991b1b;">دفعة</div>
            </div>
        </div>
        <div class="report-grid">
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">إجمالي الغرامات</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['total_late_fees'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">نسبة الالتزام بالدفع</div>
                <div class="stat-value" style="color: #3730a3;">{{ $stats['payment_compliance_rate'] }}%</div>
                <div style="font-size: 0.75rem; color: #3730a3;">من المدفوعات في الوقت</div>
            </div>
        </div>
    </div>

    <!-- معلومات الدفعات -->
    <div class="report-section">
        <h2 class="report-title">معلومات الدفعات</h2>
        <div class="report-grid">
            @if($stats['last_payment'])
            <div style="background: #dcfce7; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #166534;">آخر دفعة</h3>
                <div class="info-row">
                    <span class="info-label">رقم الدفعة:</span>
                    <span class="info-value">{{ $stats['last_payment']['payment_number'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">المبلغ:</span>
                    <span class="info-value">{{ number_format($stats['last_payment']['amount'], 0) }} ريال</span>
                </div>
                <div class="info-row" style="border-bottom: none;">
                    <span class="info-label">تاريخ الدفع:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($stats['last_payment']['paid_date'])->format('Y/m/d') }}</span>
                </div>
            </div>
            @else
            <div style="background: #f3f4f6; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #374151;">آخر دفعة</h3>
                <p style="color: #6b7280;">لا توجد دفعات سابقة</p>
            </div>
            @endif

            @if($stats['next_payment'])
            @php
                $daysUntilDue = $stats['next_payment']['days_until_due'];
                $bgColor = $daysUntilDue < 0 ? '#fee2e2' : ($daysUntilDue < 7 ? '#fef3c7' : '#dbeafe');
                $textColor = $daysUntilDue < 0 ? '#991b1b' : ($daysUntilDue < 7 ? '#92400e' : '#1e40af');
            @endphp
            <div style="background: {{ $bgColor }}; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: {{ $textColor }};">الدفعة القادمة</h3>
                <div class="info-row">
                    <span class="info-label">رقم الدفعة:</span>
                    <span class="info-value">{{ $stats['next_payment']['payment_number'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">المبلغ:</span>
                    <span class="info-value">{{ number_format($stats['next_payment']['amount'], 0) }} ريال</span>
                </div>
                <div class="info-row" style="border-bottom: none;">
                    <span class="info-label">تاريخ الاستحقاق:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($stats['next_payment']['due_date'])->format('Y/m/d') }}
                        @if($daysUntilDue < 0)
                            <span style="color: #dc2626; font-weight: bold;">(متأخر {{ abs($daysUntilDue) }} يوم)</span>
                        @elseif($daysUntilDue < 7)
                            <span style="color: #f59e0b;">(بعد {{ $daysUntilDue }} يوم)</span>
                        @else
                            <span>(بعد {{ $daysUntilDue }} يوم)</span>
                        @endif
                    </span>
                </div>
            </div>
            @else
            <div style="background: #f3f4f6; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #374151;">الدفعة القادمة</h3>
                <p style="color: #6b7280;">لا توجد دفعات قادمة</p>
            </div>
            @endif
        </div>
    </div>

    <!-- تاريخ الإيجار -->
    <div class="report-section">
        <h2 class="report-title">تاريخ الإيجار</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">إجمالي العقود</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['total_contracts'] }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">عقد</div>
            </div>
            <div class="stat-box" style="background: #f3e8ff;">
                <div class="stat-label" style="color: #6b21a8;">العقود المنتهية</div>
                <div class="stat-value" style="color: #6b21a8;">{{ $stats['expired_contracts'] }}</div>
                <div style="font-size: 0.75rem; color: #6b21a8;">عقد</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">متوسط مدة الإيجار</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['avg_contract_months'] }}</div>
                <div style="font-size: 0.75rem; color: #164e63;">شهر</div>
            </div>
        </div>
    </div>

    <!-- تقييم المستأجر -->
    <div class="report-section">
        <h2 class="report-title">تقييم المستأجر</h2>
        <div style="background: {{ $stats['is_good_standing'] ? '#dcfce7' : '#fee2e2' }}; padding: 16px; border-radius: 8px; text-align: center;">
            <h3 style="font-weight: 600; margin-bottom: 12px; color: {{ $stats['is_good_standing'] ? '#166534' : '#991b1b' }};">حالة السداد</h3>
            <p style="font-size: 1.5rem; font-weight: bold; color: {{ $stats['is_good_standing'] ? '#166534' : '#991b1b' }};">
                {{ $stats['is_good_standing'] ? 'ملتزم' : 'غير ملتزم' }}
            </p>
        </div>
    </div>

</div>