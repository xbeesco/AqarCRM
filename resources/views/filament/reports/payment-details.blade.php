<div class="payment-report-container" style="background: white; padding: 24px; border-radius: 8px;">
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
            .payment-report-container {
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
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي للدفعة</h1>
        <p style="color: #6b7280; margin-top: 8px;">رقم الدفعة: {{ $stats['payment_number'] }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- معلومات الدفعة الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات الدفعة</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">رقم الدفعة:</span>
                <span class="info-value">{{ $stats['payment_number'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الشهر/السنة:</span>
                <span class="info-value">{{ $stats['month_year'] ? \Carbon\Carbon::parse($stats['month_year'])->format('Y/m') : 'غير محدد' }}</span>
            </div>
            @if($stats['receipt_number'])
            <div class="info-row">
                <span class="info-label">رقم الإيصال:</span>
                <span class="info-value">{{ $stats['receipt_number'] }}</span>
            </div>
            @endif
            @if($stats['payment_reference'])
            <div class="info-row">
                <span class="info-label">مرجع الدفع:</span>
                <span class="info-value">{{ $stats['payment_reference'] }}</span>
            </div>
            @endif
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">طريقة الدفع:</span>
                <span class="info-value">{{ $stats['payment_method'] ?? 'غير محدد' }}</span>
            </div>
        </div>
    </div>

    <!-- حالة الدفعة -->
    <div class="report-section">
        <h2 class="report-title">حالة الدفعة</h2>
        @php
            $statusColors = [
                'collected' => ['bg' => '#dcfce7', 'text' => '#166534', 'label' => 'تم التحصيل'],
                'due' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'مستحق'],
                'overdue' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'متأخر'],
                'postponed' => ['bg' => '#f3f4f6', 'text' => '#374151', 'label' => 'مؤجل'],
            ];
            $status = $statusColors[$stats['collection_status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'label' => 'غير محدد'];
        @endphp
        <div style="background: {{ $status['bg'] }}; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الحالة:</span>
                <span class="info-value" style="color: {{ $status['text'] }}; font-weight: bold;">{{ $status['label'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ الاستحقاق:</span>
                <span class="info-value">{{ $stats['due_date_start'] ? \Carbon\Carbon::parse($stats['due_date_start'])->format('Y/m/d') : '-' }}</span>
            </div>
            @if($stats['paid_date'])
            <div class="info-row">
                <span class="info-label">تاريخ الدفع:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($stats['paid_date'])->format('Y/m/d') }}</span>
            </div>
            @endif
            @if($stats['days_late'] > 0)
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">أيام التأخير:</span>
                <span class="info-value" style="color: #dc2626; font-weight: bold;">{{ $stats['days_late'] }} يوم</span>
            </div>
            @else
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">أيام التأخير:</span>
                <span class="info-value">لا يوجد</span>
            </div>
            @endif
        </div>
    </div>

    <!-- الإحصائيات المالية -->
    <div class="report-section">
        <h2 class="report-title">المعلومات المالية</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">المبلغ الأساسي</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['amount'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال</div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">غرامة التأخير</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['late_fee'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">المبلغ الإجمالي</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['total_amount'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">ريال</div>
            </div>
        </div>
    </div>

    <!-- معلومات المستأجر -->
    <div class="report-section">
        <h2 class="report-title">معلومات المستأجر</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">اسم المستأجر:</span>
                <span class="info-value">{{ $stats['tenant_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['tenant_phone'])
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value">{{ $stats['tenant_phone'] }}</span>
            </div>
            @endif
            @if($stats['tenant_email'])
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $stats['tenant_email'] }}</span>
            </div>
            @else
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">غير محدد</span>
            </div>
            @endif
        </div>
    </div>

    <!-- معلومات العقار والوحدة -->
    <div class="report-section">
        <h2 class="report-title">معلومات العقار والوحدة</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">العقار:</span>
                <span class="info-value">{{ $stats['property_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['unit_info'])
            <div class="info-row">
                <span class="info-label">الوحدة:</span>
                <span class="info-value">{{ $stats['unit_info']['name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الطابق:</span>
                <span class="info-value">{{ $stats['unit_info']['floor'] ?? 'الأرضي' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">عدد الغرف:</span>
                <span class="info-value">{{ $stats['unit_info']['rooms'] ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">المساحة:</span>
                <span class="info-value">{{ $stats['unit_info']['area'] ?? 'غير محدد' }} م²</span>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">سعر الإيجار:</span>
                <span class="info-value">{{ number_format($stats['unit_info']['rent'], 0) }} ريال/شهر</span>
            </div>
            @else
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">الوحدة:</span>
                <span class="info-value">غير محدد</span>
            </div>
            @endif
        </div>
    </div>

    <!-- إحصائيات المستأجر -->
    <div class="report-section">
        <h2 class="report-title">إحصائيات المستأجر</h2>
        <div class="report-grid-4">
            <div class="stat-box" style="background: #dbeafe;">
                <div class="stat-label" style="color: #1e40af;">إجمالي المدفوعات</div>
                <div class="stat-value" style="color: #1e40af;">{{ number_format($stats['total_tenant_payments'], 0) }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">ريال</div>
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
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">آخر دفعة</div>
                <div class="stat-value" style="font-size: 1rem; color: #164e63;">{{ $stats['last_payment_date'] ? \Carbon\Carbon::parse($stats['last_payment_date'])->format('Y/m/d') : 'لا يوجد' }}</div>
            </div>
        </div>
    </div>

    <!-- معلومات العقد -->
    @if($stats['contract_number'])
    <div class="report-section">
        <h2 class="report-title">معلومات العقد</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">رقم العقد:</span>
                <span class="info-value">{{ $stats['contract_number'] }}</span>
            </div>
            @if($stats['contract_start'])
            <div class="info-row">
                <span class="info-label">بداية العقد:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($stats['contract_start'])->format('Y/m/d') }}</span>
            </div>
            @endif
            @if($stats['contract_end'])
            <div class="info-row">
                <span class="info-label">نهاية العقد:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($stats['contract_end'])->format('Y/m/d') }}</span>
            </div>
            @endif
            @if($stats['monthly_rent'])
            <div class="info-row">
                <span class="info-label">الإيجار الشهري:</span>
                <span class="info-value">{{ number_format($stats['monthly_rent'], 0) }} ريال</span>
            </div>
            @endif
            @if($stats['security_deposit'])
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">التأمين:</span>
                <span class="info-value">{{ number_format($stats['security_deposit'], 0) }} ريال</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ملاحظات -->
    @if($stats['delay_reason'] || $stats['late_payment_notes'])
    <div class="report-section">
        <h2 class="report-title">ملاحظات</h2>
        <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24;">
            @if($stats['delay_reason'])
            <div style="margin-bottom: 12px;">
                <strong>سبب التأخير:</strong>
                <p style="margin-top: 4px;">{{ $stats['delay_reason'] }}</p>
            </div>
            @endif
            @if($stats['late_payment_notes'])
            <div>
                <strong>ملاحظات إضافية:</strong>
                <p style="margin-top: 4px;">{{ $stats['late_payment_notes'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>