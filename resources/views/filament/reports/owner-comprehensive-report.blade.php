<div class="owner-report-container" style="background: white; padding: 24px; border-radius: 8px;">
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
            .owner-report-container {
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
        <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">تقرير تفصيلي شامل للمالك</h1>
        <p style="color: #6b7280; margin-top: 8px; font-size: 1.25rem;">{{ $stats['owner_name'] ?? 'غير محدد' }}</p>
        <p style="color: #6b7280;">تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
        @if(isset($dateRange))
        <p style="color: #6b7280;">الفترة: {{ $dateRange['from'] ?? 'البداية' }} - {{ $dateRange['to'] ?? 'النهاية' }}</p>
        @endif
    </div>

    <!-- معلومات المالك الأساسية -->
    <div class="report-section">
        <h2 class="report-title">معلومات المالك الأساسية</h2>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
            <div class="info-row">
                <span class="info-label">الاسم الكامل:</span>
                <span class="info-value">{{ $stats['owner_name'] ?? 'غير محدد' }}</span>
            </div>
            @if($stats['owner_phone'] ?? false)
            <div class="info-row">
                <span class="info-label">رقم الهاتف الأول:</span>
                <span class="info-value" style="direction: ltr; text-align: right;">{{ $stats['owner_phone'] }}</span>
            </div>
            @endif
            @if($stats['owner_secondary_phone'] ?? false)
            <div class="info-row">
                <span class="info-label">رقم الهاتف الثاني:</span>
                <span class="info-value" style="direction: ltr; text-align: right;">{{ $stats['owner_secondary_phone'] }}</span>
            </div>
            @endif
            @if($stats['owner_email'] ?? false)
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
                <span class="info-value" style="color: {{ ($stats['is_active'] ?? false) ? '#166534' : '#92400e' }}; font-weight: bold;">
                    {{ ($stats['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
    </div>

    <!-- إحصائيات العقارات والوحدات -->
    <div class="report-section">
        <h2 class="report-title">إحصائيات العقارات والوحدات</h2>
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
        
        <!-- نسبة الإشغال -->
        <div style="background: #f3e8ff; padding: 16px; border-radius: 8px; margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-weight: 600; color: #6b21a8;">نسبة الإشغال العامة</h3>
                <span style="font-size: 2rem; font-weight: bold; color: #6b21a8;">{{ $stats['occupancy_rate'] ?? 0 }}%</span>
            </div>
            <div style="background: #e9d5ff; height: 20px; border-radius: 10px; margin-top: 8px; overflow: hidden;">
                <div style="background: #6b21a8; height: 100%; width: {{ $stats['occupancy_rate'] ?? 0 }}%;"></div>
            </div>
        </div>
        
        @if(($stats['properties_list'] ?? false) && is_array($stats['properties_list']) && count($stats['properties_list']) > 0)
        <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24; margin-top: 16px;">
            <h3 style="font-weight: 600; margin-bottom: 8px; color: #92400e;">قائمة العقارات المملوكة:</h3>
            <p style="line-height: 1.8;">{{ implode(' • ', $stats['properties_list']) }}</p>
        </div>
        @endif
    </div>

    <!-- الإحصائيات المالية التفصيلية -->
    <div class="report-section">
        <h2 class="report-title">الإحصائيات المالية - آخر 12 شهر</h2>
        
        <!-- المبالغ الرئيسية -->
        <div class="report-grid-3">
            <div class="stat-box" style="background: #d1fae5;">
                <div class="stat-label" style="color: #065f46;">إجمالي التحصيل</div>
                <div class="stat-value" style="color: #065f46;">{{ number_format($stats['total_collection'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #065f46;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #fed7aa;">
                <div class="stat-label" style="color: #9a3412;">الرسوم الإدارية</div>
                <div class="stat-value" style="color: #9a3412;">{{ number_format($stats['management_fees'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #9a3412;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #e0e7ff;">
                <div class="stat-label" style="color: #3730a3;">المستحق للمالك</div>
                <div class="stat-value" style="color: #3730a3;">{{ number_format($stats['owner_due'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #3730a3;">ريال سعودي</div>
            </div>
        </div>

        <!-- حالة المدفوعات -->
        <div class="report-grid-3" style="margin-top: 16px;">
            <div class="stat-box" style="background: #dcfce7;">
                <div class="stat-label" style="color: #166534;">المحول للمالك</div>
                <div class="stat-value" style="color: #166534;">{{ number_format($stats['paid_to_owner'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #166534;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">الرصيد المعلق</div>
                <div class="stat-value" style="color: #92400e;">{{ number_format($stats['pending_balance'] ?? 0, 0) }}</div>
                <div style="font-size: 0.75rem; color: #92400e;">ريال سعودي</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">نسبة التحويل</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['transfer_rate'] ?? 0 }}%</div>
                <div style="font-size: 0.75rem; color: #164e63;">من المستحق</div>
            </div>
        </div>

        <!-- متوسط الدخل الشهري -->
        <div style="background: #f3e8ff; padding: 16px; border-radius: 8px; margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-weight: 600; color: #6b21a8;">متوسط الدخل الشهري</h3>
                <span style="font-size: 1.5rem; font-weight: bold; color: #6b21a8;">
                    {{ number_format($stats['average_monthly_income'] ?? 0, 0) }} ريال
                </span>
            </div>
        </div>
    </div>

    <!-- معلومات العمليات المالية -->
    <div class="report-section">
        <h2 class="report-title">تفاصيل العمليات المالية</h2>
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
            <div class="stat-box" style="background: #fef3c7;">
                <div class="stat-label" style="color: #92400e;">العمليات المعلقة</div>
                <div class="stat-value" style="color: #92400e;">
                    {{ ($stats['total_operations'] ?? 0) - ($stats['completed_operations'] ?? 0) }}
                </div>
                <div style="font-size: 0.75rem; color: #92400e;">عملية</div>
            </div>
            <div class="stat-box" style="background: #cffafe;">
                <div class="stat-label" style="color: #164e63;">معدل الإنجاز</div>
                <div class="stat-value" style="color: #164e63;">{{ $stats['completion_rate'] ?? 0 }}%</div>
                <div style="font-size: 0.75rem; color: #164e63;">من العمليات</div>
            </div>
        </div>
    </div>

    <!-- آخر العمليات -->
    <div class="report-section">
        <h2 class="report-title">آخر العمليات المالية</h2>
        <div class="report-grid">
            <!-- آخر دفعة محولة -->
            <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; border: 1px solid #0284c7;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #0369a1;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-left: 8px;"></span>
                    آخر دفعة محولة
                </h3>
                @if($stats['last_payment'] ?? false)
                    <div class="info-row" style="padding: 4px 0;">
                        <span class="info-label">رقم العملية:</span>
                        <span class="info-value" style="font-weight: 600;">{{ $stats['last_payment']['payment_number'] }}</span>
                    </div>
                    <div class="info-row" style="padding: 4px 0;">
                        <span class="info-label">المبلغ المحول:</span>
                        <span class="info-value" style="color: #166534; font-weight: bold;">
                            {{ number_format($stats['last_payment']['amount'], 0) }} ريال
                        </span>
                    </div>
                    <div class="info-row" style="padding: 4px 0; border-bottom: none;">
                        <span class="info-label">تاريخ التحويل:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($stats['last_payment']['payment_date'])->format('Y/m/d') }}</span>
                    </div>
                @else
                    <p style="color: #6b7280; text-align: center; padding: 16px 0;">لا توجد دفعات محولة سابقاً</p>
                @endif
            </div>
            
            <!-- الدفعة القادمة المعلقة -->
            <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border: 1px solid #fbbf24;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #92400e;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #f59e0b; border-radius: 50%; margin-left: 8px;"></span>
                    الدفعة القادمة المعلقة
                </h3>
                @if($stats['next_payment'] ?? false)
                    <div class="info-row" style="padding: 4px 0;">
                        <span class="info-label">رقم العملية:</span>
                        <span class="info-value" style="font-weight: 600;">{{ $stats['next_payment']['payment_number'] }}</span>
                    </div>
                    <div class="info-row" style="padding: 4px 0;">
                        <span class="info-label">المبلغ المستحق:</span>
                        <span class="info-value" style="color: #92400e; font-weight: bold;">
                            {{ number_format($stats['next_payment']['amount'], 0) }} ريال
                        </span>
                    </div>
                    <div class="info-row" style="padding: 4px 0; border-bottom: none;">
                        <span class="info-label">تاريخ الإنشاء:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($stats['next_payment']['created_date'])->format('Y/m/d') }}</span>
                    </div>
                @else
                    <p style="color: #6b7280; text-align: center; padding: 16px 0;">لا توجد دفعات معلقة حالياً</p>
                @endif
            </div>
        </div>
    </div>

    <!-- آخر 5 عمليات تحويل (إن وجدت) -->
    @if(isset($recentPayments) && count($recentPayments) > 0)
    <div class="report-section">
        <h2 class="report-title">آخر 5 عمليات تحويل</h2>
        <table class="payment-table">
            <thead>
                <tr>
                    <th>رقم العملية</th>
                    <th>تاريخ التحويل</th>
                    <th>المبلغ الإجمالي</th>
                    <th>الخصومات</th>
                    <th>صافي المبلغ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPayments as $payment)
                <tr>
                    <td>{{ $payment->payment_number }}</td>
                    <td>{{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('Y/m/d') : '-' }}</td>
                    <td>{{ number_format($payment->gross_amount, 0) }} ريال</td>
                    <td>{{ number_format($payment->gross_amount - $payment->net_amount, 0) }} ريال</td>
                    <td style="color: #166534; font-weight: bold;">{{ number_format($payment->net_amount, 0) }} ريال</td>
                    <td>
                        @switch($payment->supply_status)
                            @case('collected')
                                <span style="color: #166534;">✓ محول</span>
                                @break
                            @case('pending')
                                <span style="color: #92400e;">⏳ معلق</span>
                                @break
                            @case('worth_collecting')
                                <span style="color: #0369a1;">💰 جاهز للتحصيل</span>
                                @break
                        @endswitch
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- تقييم الأداء العام -->
    <div class="report-section">
        <h2 class="report-title">تقييم الأداء العام</h2>
        <div class="report-grid-3">
            <div style="background: #ecfdf5; padding: 16px; border-radius: 8px; text-align: center;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #065f46;">مستوى الأداء</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #065f46;">
                    @switch($stats['performance_level'] ?? 'needs_attention')
                        @case('excellent')
                            ⭐⭐⭐ ممتاز
                            @break
                        @case('good')
                            ⭐⭐ جيد
                            @break
                        @default
                            ⭐ يحتاج تحسين
                    @endswitch
                </p>
            </div>
            
            <div style="background: #f3e8ff; padding: 16px; border-radius: 8px; text-align: center;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #6b21a8;">نسبة الإشغال</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #6b21a8;">
                    {{ $stats['occupancy_rate'] ?? 0 }}%
                </p>
            </div>
            
            <div style="background: #e0e7ff; padding: 16px; border-radius: 8px; text-align: center;">
                <h3 style="font-weight: 600; margin-bottom: 12px; color: #3730a3;">نسبة التحويل</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: #3730a3;">
                    {{ $stats['transfer_rate'] ?? 0 }}%
                </p>
            </div>
        </div>
    </div>

    <!-- توقيع التقرير -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
        <div class="report-grid">
            <div style="text-align: center;">
                <p style="color: #6b7280; margin-bottom: 40px;">توقيع الإدارة</p>
                <div style="border-bottom: 1px solid #9ca3af; width: 200px; margin: 0 auto;"></div>
            </div>
            <div style="text-align: center;">
                <p style="color: #6b7280; margin-bottom: 40px;">توقيع المالك</p>
                <div style="border-bottom: 1px solid #9ca3af; width: 200px; margin: 0 auto;"></div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #9ca3af; font-size: 0.875rem;">
            <p>تم إنشاء هذا التقرير آلياً بواسطة نظام إدارة العقارات</p>
            <p>{{ config('app.name', 'نظام إدارة العقارات') }} © {{ date('Y') }}</p>
        </div>
    </div>
</div>