<div class="owner-payment-report-container" style="background: white; padding: 24px; border-radius: 8px;">
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
            .owner-payment-report-container {
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
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي لدفعة المالك</h1>
        <p style="color: #6b7280; margin-top: 8px;">رقم العملية: {{ $stats['payment_number'] ?: 'غير محدد' }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات العملية الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات العملية</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">رقم العملية:</span>
                <span class="info-value">{{ $stats['payment_number'] ?: 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ التحويل:</span>
                <span class="info-value">{{ $stats['payment_date'] ? \Carbon\Carbon::parse($stats['payment_date'])->format('Y/m/d') : 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">طريقة التحويل:</span>
                <span class="info-value">
                    @switch($stats['payment_method'])
                        @case('bank_transfer')
                            تحويل بنكي
                            @break
                        @case('cash')
                            نقداً
                            @break
                        @case('check')
                            شيك
                            @break
                        @default
                            {{ $stats['payment_method'] ?: 'غير محدد' }}
                    @endswitch
                </span>
            </div>
            @if($stats['bank_reference'])
            <div class="info-row">
                <span class="info-label">مرجع البنك:</span>
                <span class="info-value">{{ $stats['bank_reference'] }}</span>
            </div>
            @endif
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: {{ $stats['payment_status'] === 'collected' ? '#166534' : '#92400e' }}; font-weight: bold;">
                    @switch($stats['payment_status'])
                        @case('collected')
                            محول
                            @break
                        @case('pending')
                            معلق
                            @break
                        @case('worth_collecting')
                            جاهز للتحصيل
                            @break
                        @default
                            {{ $stats['payment_status'] ?: 'غير محدد' }}
                    @endswitch
                </span>
            </div>
        </div>
    </div>

    <!-- المعلومات المالية -->
    <div class="report-section">
        <h2 class="report-title">المعلومات المالية</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">المبلغ الإجمالي</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['amount'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">الخصومات</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['deductions'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">صافي المبلغ</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['net_amount'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">ريال</div>
            </div>
        </div>
    </div>

    <!-- معلومات المالك -->
    <div class="report-section">
        <h2 class="report-title">معلومات المالك</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">اسم المالك:</span>
                <span class="info-value">{{ $stats['owner_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['owner_phone'])
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value">{{ $stats['owner_phone'] }}</span>
            </div>
            @endif
            @if($stats['owner_email'])
            <div class="info-row">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $stats['owner_email'] }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">عدد العقارات:</span>
                <span class="info-value">{{ $stats['properties_count'] }} عقار</span>
            </div>
            <div class="info-row">
                <span class="info-label">إجمالي الوحدات:</span>
                <span class="info-value">{{ $stats['total_units'] }} وحدة</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">العقارات:</span>
                <span class="info-value">{{ $stats['properties_names'] ?: 'غير محدد' }}</span>
            </div>
        </div>
    </div>

    <!-- إحصائيات المالك -->
    <div class="report-section">
        <h2 class="report-title">إحصائيات المالك</h2>
        <div class="report-grid-4">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">إجمالي المدفوعات</div>
                <div class="stat-value" style="color: #1e40af;">{{ number_format($stats['total_owner_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">المدفوعات المعلقة</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['pending_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال</div>
            </div>
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">العمليات المكتملة</div>
                <div class="stat-value" style="color: #166534;">{{ $stats['completed_operations'] }}</div>
                <div style="font-size: 0.75rem; color: #166534;">عملية</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">معدل الإنجاز</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['completion_rate'] }}%</div>
                <div style="font-size: 0.75rem; color: #164e63;">من العمليات</div>
            </div>
        </div>
    </div>

    <!-- معلومات إضافية -->
    <div class="report-section">
        <h2 class="report-title">معلومات إضافية</h2>
        <div class="report-grid">
            <div style="background: #f3e8ff; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #6b21a8;">متوسط الدفعة</h3>
                <p style="font-size: 1.25rem; font-weight: bold; color: #6b21a8;">
                    {{ number_format($stats['average_payment'], 0) }} ريال
                </p>
            </div>
            <div style="background: #f0f9ff; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #0369a1;">آخر دفعة</h3>
                <p style="color: #0369a1;">
                    {{ $stats['last_payment_date'] ? \Carbon\Carbon::parse($stats['last_payment_date'])->format('Y/m/d') : 'لا توجد' }}
                </p>
            </div>
        </div>
    </div>

    <!-- ملاحظات -->
    @if($stats['notes'])
    <div class="report-section">
        <h2 class="report-title">ملاحظات</h2>
        <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24;">
            <p>{{ $stats['notes'] }}</p>
        </div>
    </div>
    @endif

</div>