@php
    use App\Models\CollectionPayment;
    use App\Models\UnitContract;

    $activeContract = UnitContract::where('tenant_id', $tenant->id)
        ->where('contract_status', 'active')
        ->with(['unit.property'])
        ->first();

    $totalPaid = CollectionPayment::where('tenant_id', $tenant->id)
        ->collectedPayments()
        ->sum('total_amount');

    $pendingPayments = CollectionPayment::where('tenant_id', $tenant->id)
        ->dueForCollection()
        ->sum('total_amount');

    $overduePayments = CollectionPayment::where('tenant_id', $tenant->id)
        ->overduePayments()
        ->sum('total_amount');

    $paymentCount = CollectionPayment::where('tenant_id', $tenant->id)->count();

    $remainingDays = $activeContract ? now()->diffInDays($activeContract->end_date, false) : 0;
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
        <p>تقرير المستأجر</p>
        <p style="font-size: 10pt;">التاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d') }} | الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}</p>
    </div>

    {{-- معلومات المستأجر --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات المستأجر</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">اسم المستأجر</span>
                <p style="font-size: 18px; font-weight: bold; color: #111827; margin: 4px 0 0 0;">{{ $tenant->name }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">رقم الهاتف</span>
                <p style="font-size: 16px; color: #2563eb; margin: 4px 0 0 0;">{{ $tenant->phone }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">البريد الإلكتروني</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $tenant->email ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">رقم الهوية</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $tenant->national_id ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">المهنة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $tenant->occupation ?? 'غير محددة' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">جهة العمل</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $tenant->employer ?? 'غير محددة' }}</p>
            </div>
        </div>
    </div>

    {{-- معلومات العقد الحالي --}}
    @if($activeContract)
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات العقد الحالي</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">العقار الحالي</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 6px; font-size: 13px;">{{ $activeContract->unit->property->name }}</span>
                </p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">الوحدة</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $activeContract->unit->name }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">الإيجار الشهري</span>
                <p style="font-size: 16px; font-weight: bold; color: #ca8a04; margin: 4px 0 0 0;">{{ number_format($activeContract->monthly_rent, 2) }} ر.س</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">بداية العقد</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $activeContract->start_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">نهاية العقد</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $activeContract->end_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">الأيام المتبقية</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">
                    @php
                        $daysColor = $remainingDays <= 0 ? '#dc2626' : ($remainingDays <= 30 ? '#ca8a04' : '#16a34a');
                        $daysBg = $remainingDays <= 0 ? '#fef2f2' : ($remainingDays <= 30 ? '#fefce8' : '#f0fdf4');
                    @endphp
                    <span style="background: {{ $daysBg }}; color: {{ $daysColor }}; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        {{ $remainingDays > 0 ? $remainingDays . ' يوم' : 'منتهي' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- الإحصائيات المالية --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">الإحصائيات المالية</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">إجمالي المدفوع (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ca8a04;">{{ number_format($pendingPayments, 2) }}</div>
                <div class="stat-label">مدفوعات مستحقة (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc2626;">{{ number_format($overduePayments, 2) }}</div>
                <div class="stat-label">مدفوعات متأخرة (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #2563eb;">{{ $paymentCount }}</div>
                <div class="stat-label">عدد الدفعات</div>
            </div>
        </div>
    </div>

    {{-- Footer للطباعة فقط --}}
    <div class="print-only print-footer">
        <p>نظام إدارة العقارات © {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</div>
