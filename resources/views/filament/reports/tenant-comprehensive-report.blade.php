<div class="tenant-report-container" style="background: white; padding: 24px; border-radius: 8px;">
    <!-- Styles للطباعة والعرض -->
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
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .payment-table th {
            background: #f9fafb;
            padding: 8px;
            text-align: right;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .payment-table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
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
            .report-grid, .report-grid-3, .report-grid-4 {
                page-break-inside: avoid;
            }
        }
    </style>

    <!-- عنوان التقرير -->
    <div style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي شامل للمستأجر</h1>
        <p style="color: #6b7280; margin-top: 8px; font-size: 1.25rem;">{{ $stats['tenant_name'] ?? 'غير محدد' }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
        @if(isset($dateRange))
        <p style="color: #6b7280;">الفترة: {{ $dateRange['from'] ?? 'البداية' }} - {{ $dateRange['to'] ?? 'النهاية' }}</p>
        @endif
    </div>

    <!-- معلومات المستأجر الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات المستأجر الأساسية</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الاسم الكامل:</span>
                <span class="info-value">{{ $stats['tenant_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['tenant_phone'] ?? false)
            <div class="info-row">
                <span class="info-label">رقم الهاتف الأول:</span>
                <span class="info-value" style="direction: ltr; text-align: right;">{{ $stats['tenant_phone'] }}</span>
            </div>
            @endif
            @if($stats['tenant_secondary_phone'] ?? false)
            <div class="info-row">
                <span class="info-label">رقم الهاتف الثاني:</span>
                <span class="info-value" style="direction: ltr; text-align: right;">{{ $stats['tenant_secondary_phone'] }}</span>
            </div>
            @endif
            @if($stats['tenant_email'] ?? false)
            <div class="info-row">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $stats['tenant_email'] }}</span>
            </div>
            @endif
            @if($stats['identity_file'] ?? false)
            <div class="info-row">
                <span class="info-label">ملف الهوية:</span>
                <span class="info-value" style="color: #166534;">✓ مرفق</span>
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
        <h2 class="report-title">حالة الإيجار والعقد</h2>
        @if($stats['has_active_contract'])
        @php
            $contract = $stats['current_contract'];
            $daysRemaining = $contract['days_remaining'];
        @endphp
        <div style="background: #dcfce7; padding: 16px; border-radius: 8px; border: 1px solid #22c55e;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-weight: 600; color: #166534;">مستأجر نشط</h3>
                <span style="background: #166534; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">
                    العقد ساري
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم العقد:</span>
                <span class="info-value" style="font-weight: bold;">{{ $contract['contract_number'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">بداية العقد:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($contract['start_date'])->format('Y/m/d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">نهاية العقد:</span>
                <span class="info-value">
                    {{ \Carbon\Carbon::parse($contract['end_date'])->format('Y/m/d') }}
                    @if($daysRemaining < 0)
                        <span style="color: #dc2626; font-weight: bold;">(انتهى منذ {{ abs($daysRemaining) }} يوم)</span>
                    @elseif($daysRemaining < 30)
                        <span style="color: #dc2626; font-weight: bold;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @elseif($daysRemaining < 60)
                        <span style="color: #f59e0b;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @else
                        <span style="color: #166534;">(متبقي {{ $daysRemaining }} يوم)</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">الإيجار الشهري:</span>
                <span class="info-value" style="color: #166534; font-weight: bold;">{{ number_format($contract['monthly_rent'], 0) }} ريال</span>
            </div>
            @if($contract['security_deposit'])
            <div class="info-row">
                <span class="info-label">مبلغ التأمين:</span>
                <span class="info-value">{{ number_format($contract['security_deposit'], 0) }} ريال</span>
            </div>
            @endif
            @if($contract['payment_method'])
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">طريقة الدفع:</span>
                <span class="info-value">{{ $contract['payment_method'] }}</span>
            </div>
            @endif
        </div>
        @else
        <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-weight: 600; color: #92400e;">بدون عقد نشط</h3>
                <span style="background: #92400e; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">
                    غير نشط
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- معلومات الوحدة والعقار الحالي -->
    @if($stats['current_unit'] && $stats['current_property'])
    <div class="report-section">
        <h2 class="report-title">معلومات الوحدة والعقار</h2>
        <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; border: 1px solid #0284c7;">
            <div class="report-grid">
                <!-- معلومات العقار -->
                <div>
                    <h3 style="font-weight: 600; margin-bottom: 8px; color: #0369a1;">معلومات العقار</h3>
                    <div class="info-row">
                        <span class="info-label">اسم العقار:</span>
                        <span class="info-value">{{ $stats['current_property']['name'] }}</span>
                    </div>
                    @if($stats['current_property']['address'])
                    <div class="info-row">
                        <span class="info-label">العنوان:</span>
                        <span class="info-value">{{ $stats['current_property']['address'] }}</span>
                    </div>
                    @endif
                    @if($stats['current_property']['type'])
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">نوع العقار:</span>
                        <span class="info-value">{{ $stats['current_property']['type'] }}</span>
                    </div>
                    @endif
                </div>
                
                <!-- معلومات الوحدة -->
                <div>
                    <h3 style="font-weight: 600; margin-bottom: 8px; color: #0369a1;">معلومات الوحدة</h3>
                    <div class="info-row">
                        <span class="info-label">رقم الوحدة:</span>
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
                    @if($stats['current_unit']['area_sqm'])
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">المساحة:</span>
                        <span class="info-value">{{ $stats['current_unit']['area_sqm'] }} م²</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الإحصائيات المالية التفصيلية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية الشاملة</h2>
        
        <!-- المبالغ الرئيسية -->
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5; border: 1px solid #22c55e;">
                <div class="stat-label" style="color: #065f46;">إجمالي المدفوعات</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['total_payments'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #fef3c7; border: 1px solid #fbbf24;">
                <div class="stat-label" style="color: #92400e;">المستحقات المتبقية</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['outstanding_payments'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #fee2e2; border: 1px solid #ef4444;">
                <div class="stat-label" style="color: #991b1b;">عدد المتأخرات</div>
                <div class="stat-value" style="color: #991b1b;">{{ $stats['overdue_count'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #991b1b;">دفعة</div>
            </div>
        </div>
        
        <!-- معلومات إضافية -->
        <div class="report-grid">
            <div class="stat-box" style="background: #fed7aa; border: 1px solid #f97316;">
                <div class="stat-label" style="color: #9a3412;">إجمالي الغرامات</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['total_late_fees'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff; border: 1px solid #6366f1;">
                <div class="stat-label" style="color: #3730a3;">نسبة الالتزام بالدفع</div>
                <div class="stat-value" style="color: #3730a3;">{{ $stats['payment_compliance_rate'] ?? 0 }}%</div>
                <div style="font-size: 0.75rem; color: #3730a3;">من المدفوعات في الوقت</div>
            </div>
        </div>
    </div>

    <!-- معلومات الدفعات الحالية -->
    <div class="report-section">
        <h2 class="report-title">معلومات الدفعات</h2>
        <div class="report-grid">
            <!-- آخر دفعة -->
            @if($stats['last_payment'])
            <div style="background: #dcfce7; padding: 16px; border-radius: 8px; border: 1px solid #22c55e;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #166534;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-left: 8px;"></span>
                    آخر دفعة مسجلة
                </h3>
                <div class="info-row" style="padding: 4px 0;">
                    <span class="info-label">رقم الدفعة:</span>
                    <span class="info-value" style="font-weight: 600;">{{ $stats['last_payment']['payment_number'] }}</span>
                </div>
                <div class="info-row" style="padding: 4px 0;">
                    <span class="info-label">المبلغ:</span>
                    <span class="info-value" style="color: #166534; font-weight: bold;">{{ number_format($stats['last_payment']['amount'], 0) }} ريال</span>
                </div>
                <div class="info-row" style="padding: 4px 0; border-bottom: none;">
                    <span class="info-label">تاريخ الدفع:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($stats['last_payment']['paid_date'])->format('Y/m/d') }}</span>
                </div>
            </div>
            @else
            <div style="background: #f3f4f6; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #374151;">آخر دفعة مسجلة</h3>
                <p style="color: #6b7280; text-align: center; padding: 16px 0;">لا توجد دفعات سابقة مسجلة</p>
            </div>
            @endif

            <!-- الدفعة القادمة -->
            @if($stats['next_payment'])
            @php
                $daysUntilDue = $stats['next_payment']['days_until_due'];
                $bgColor = $daysUntilDue < 0 ? '#fee2e2' : ($daysUntilDue < 7 ? '#fef3c7' : '#dbeafe');
                $borderColor = $daysUntilDue < 0 ? '#ef4444' : ($daysUntilDue < 7 ? '#fbbf24' : '#3b82f6');
                $textColor = $daysUntilDue < 0 ? '#991b1b' : ($daysUntilDue < 7 ? '#92400e' : '#1e40af');
                $statusColor = $daysUntilDue < 0 ? '#ef4444' : ($daysUntilDue < 7 ? '#f59e0b' : '#3b82f6');
            @endphp
            <div style="background: {{ $bgColor }}; padding: 16px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: {{ $textColor }};">
                    <span style="display: inline-block; width: 8px; height: 8px; background: {{ $statusColor }}; border-radius: 50%; margin-left: 8px;"></span>
                    الدفعة القادمة المستحقة
                </h3>
                <div class="info-row" style="padding: 4px 0;">
                    <span class="info-label">رقم الدفعة:</span>
                    <span class="info-value" style="font-weight: 600;">{{ $stats['next_payment']['payment_number'] }}</span>
                </div>
                <div class="info-row" style="padding: 4px 0;">
                    <span class="info-label">المبلغ:</span>
                    <span class="info-value" style="color: {{ $textColor }}; font-weight: bold;">{{ number_format($stats['next_payment']['amount'], 0) }} ريال</span>
                </div>
                <div class="info-row" style="padding: 4px 0; border-bottom: none;">
                    <span class="info-label">تاريخ الاستحقاق:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($stats['next_payment']['due_date'])->format('Y/m/d') }}
                        @if($daysUntilDue < 0)
                            <span style="color: #dc2626; font-weight: bold;">(متأخر {{ abs($daysUntilDue) }} يوم)</span>
                        @elseif($daysUntilDue === 0)
                            <span style="color: #f59e0b; font-weight: bold;">(مستحق اليوم)</span>
                        @elseif($daysUntilDue < 7)
                            <span style="color: #f59e0b;">(بعد {{ $daysUntilDue }} يوم)</span>
                        @else
                            <span style="color: #1e40af;">(بعد {{ $daysUntilDue }} يوم)</span>
                        @endif
                    </span>
                </div>
            </div>
            @else
            <div style="background: #f3f4f6; padding: 16px; border-radius: 8px;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #374151;">الدفعة القادمة المستحقة</h3>
                <p style="color: #6b7280; text-align: center; padding: 16px 0;">لا توجد دفعات مستحقة حالياً</p>
            </div>
            @endif
        </div>
    </div>

    <!-- آخر 5 دفعات (إن وجدت) -->
    @if(isset($recentPayments) && count($recentPayments) > 0)
    <div class="report-section">
        <h2 class="report-title">آخر 5 دفعات</h2>
        <table class="payment-table">
            <thead>
                <tr>
                    <th>رقم الدفعة</th>
                    <th>الشهر/السنة</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>المبلغ</th>
                    <th>الغرامة</th>
                    <th>الإجمالي</th>
                    <th>تاريخ الدفع</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPayments as $payment)
                <tr>
                    <td style="font-weight: 600;">{{ $payment->payment_number }}</td>
                    <td>{{ $payment->month_year ? \Carbon\Carbon::parse($payment->month_year)->format('Y/m') : '-' }}</td>
                    <td>{{ $payment->due_date_start ? \Carbon\Carbon::parse($payment->due_date_start)->format('Y/m/d') : '-' }}</td>
                    <td>{{ number_format($payment->amount, 0) }} ريال</td>
                    <td style="color: #f97316;">{{ number_format($payment->late_fee, 0) }} ريال</td>
                    <td style="font-weight: bold;">{{ number_format($payment->total_amount, 0) }} ريال</td>
                    <td>{{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('Y/m/d') : '-' }}</td>
                    <td>
                        @switch($payment->collection_status)
                            @case('collected')
                                <span style="color: #166534; font-weight: 600;">✓ محصل</span>
                                @break
                            @case('due')
                                <span style="color: #92400e; font-weight: 600;">⏳ مستحق</span>
                                @break
                            @case('overdue')
                                <span style="color: #991b1b; font-weight: 600;">⚠ متأخر</span>
                                @break
                            @case('postponed')
                                <span style="color: #6b7280; font-weight: 600;">⏸ مؤجل</span>
                                @break
                            @default
                                <span style="color: #6b7280;">{{ $payment->collection_status }}</span>
                        @endswitch
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- تاريخ الإيجار والعقود -->
    <div class="report-section">
        <h2 class="report-title">تاريخ الإيجار والعقود</h2>
        <div class="report-grid-3">
            <div class="stat-box" style="background: #dbeafe; border: 1px solid #3b82f6;">
                <div class="stat-label" style="color: #1e40af;">إجمالي العقود</div>
                <div class="stat-value" style="color: #1e40af;">{{ $stats['total_contracts'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #1e40af;">عقد</div>
            </div>
            <div class="stat-box" style="background: #f3e8ff; border: 1px solid #8b5cf6;">
                <div class="stat-label" style="color: #6b21a8;">العقود المنتهية</div>
                <div class="stat-value" style="color: #6b21a8;">{{ $stats['expired_contracts'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #6b21a8;">عقد</div>
            </div>
            <div class="stat-box" style="background: #cffafe; border: 1px solid #06b6d4;">
                <div class="stat-label" style="color: #164e63;">متوسط مدة الإيجار</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['avg_contract_months'] ?? 0 }}</div>
                <div style="font-size: 0.75rem; color: #164e63;">شهر</div>
            </div>
        </div>
    </div>

    <!-- معلومات إضافية -->
    @if($stats['has_active_contract'])
    <div class="report-section">
        <h2 class="report-title">معلومات إضافية للعقد الحالي</h2>
        <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24;">
            <div class="report-grid">
                <div>
                    <div class="info-row">
                        <span class="info-label">المدة الإجمالية للعقد:</span>
                        <span class="info-value">
                            {{ \Carbon\Carbon::parse($stats['current_contract']['start_date'])->diffInMonths(\Carbon\Carbon::parse($stats['current_contract']['end_date'])) }} شهر
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المدة المنقضية:</span>
                        <span class="info-value">
                            {{ \Carbon\Carbon::parse($stats['current_contract']['start_date'])->diffInMonths(now()) }} شهر
                        </span>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <span class="info-label">إجمالي قيمة العقد:</span>
                        <span class="info-value" style="color: #92400e; font-weight: bold;">
                            {{ number_format($stats['current_contract']['monthly_rent'] * \Carbon\Carbon::parse($stats['current_contract']['start_date'])->diffInMonths(\Carbon\Carbon::parse($stats['current_contract']['end_date'])), 0) }} ريال
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المدفوع من العقد:</span>
                        <span class="info-value" style="color: #166534; font-weight: bold;">
                            {{ number_format($stats['total_payments'], 0) }} ريال
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ختام التقرير -->
    <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; color: #9ca3af; font-size: 0.875rem;">
        <p>تم إنشاء هذا التقرير آلياً بواسطة نظام إدارة العقارات</p>
        <p>{{ config('app.name', 'نظام إدارة العقارات') }} © {{ date('Y') }}</p>
    </div>
</div>