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
        <p>تقرير المالك</p>
        <p style="font-size: 10pt;">التاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d') }} | الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}</p>
    </div>

    {{-- معلومات المالك --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">معلومات المالك</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 12px; color: #6b7280;">اسم المالك</span>
                <p style="font-size: 18px; font-weight: bold; color: #111827; margin: 4px 0 0 0;">{{ $owner->name }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">التليفون</span>
                <p style="font-size: 16px; color: #2563eb; margin: 4px 0 0 0;">{{ $owner->phone }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">التليفون 2</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $owner->secondary_phone ?? 'غير محدد' }}</p>
            </div>
            <div>
                <span style="font-size: 12px; color: #6b7280;">البريد الإلكتروني</span>
                <p style="font-size: 16px; color: #111827; margin: 4px 0 0 0;">{{ $owner->email ?? 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    {{-- ملخص الإحصائيات --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">ملخص الإحصائيات</h3>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px;">
            <div class="stat-card">
                <div class="stat-value" style="color: #2563eb;">{{ $summary['total_properties'] }}</div>
                <div class="stat-label">عدد العقارات</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #0891b2;">{{ $summary['total_units'] }}</div>
                <div class="stat-label">إجمالي الوحدات</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ $summary['occupied_units'] }}</div>
                <div class="stat-label">وحدات مشغولة</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ca8a04;">{{ $summary['vacant_units'] }}</div>
                <div class="stat-label">وحدات شاغرة</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: {{ $summary['occupancy_rate'] >= 80 ? '#16a34a' : ($summary['occupancy_rate'] >= 50 ? '#ca8a04' : '#dc2626') }};">{{ $summary['occupancy_rate'] }}%</div>
                <div class="stat-label">نسبة الإشغال</div>
            </div>
        </div>
    </div>

    {{-- الإحصائيات المالية --}}
    <div class="section" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 16px 0; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">الإحصائيات المالية</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ number_format($summary['monthly_revenue'], 2) }}</div>
                <div class="stat-label">الإيراد الشهري (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ number_format($summary['annual_revenue'], 2) }}</div>
                <div class="stat-label">الإيراد السنوي (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #16a34a;">{{ number_format($summary['total_paid'], 2) }}</div>
                <div class="stat-label">إجمالي المحصل (ر.س)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc2626;">{{ number_format($summary['total_overdue'], 2) }}</div>
                <div class="stat-label">إجمالي المتأخر (ر.س)</div>
            </div>
        </div>
    </div>

    {{-- تقرير العقارات --}}
    @if(count($propertiesReport) > 0)
    <div class="section" style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px;">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">تقرير العقارات</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العقار</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الموقع</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">صنف العقار</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">دفعات التحصيل</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المحصل</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">نسبة الإدارة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رسوم الإدارة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($propertiesReport as $property)
                    <tr>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">{{ $property['property_name'] }}</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $property['location'] }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 12px;">{{ $property['property_category'] }}</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">{{ $property['collection_payments'] }}</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #10b981; border-bottom: 1px solid #e5e7eb;">{{ number_format($property['total_income'], 2) }} ر.س</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <span style="background: #f3f4f6; color: #374151; padding: 4px 8px; border-radius: 6px; font-size: 13px;">{{ $property['admin_percentage'] }}%</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #ef4444; border-bottom: 1px solid #e5e7eb;">{{ number_format($property['admin_fee'], 2) }} ر.س</td>
                    </tr>
                @endforeach
                {{-- الإجمالي --}}
                <tr style="background: #f3f4f6;">
                    <td colspan="4" style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #111827;">الإجمالي</td>
                    <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #10b981;">{{ number_format($propertiesTotal['total_income'], 2) }} ر.س</td>
                    <td style="padding: 12px 16px; font-size: 14px; color: #111827; text-align: center;">-</td>
                    <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #ef4444;">{{ number_format($propertiesTotal['total_admin_fee'], 2) }} ر.س</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- العقود النشطة والمستأجرين --}}
    @if(count($tenantsReport) > 0)
    <div class="section" style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px;">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">العقود النشطة والمستأجرين</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">العقار</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الوحدة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المستأجر</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الهاتف</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الإيجار الشهري</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الأيام المتبقية</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenantsReport as $tenant)
                    <tr>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 6px; font-size: 12px;">{{ $tenant['property_name'] }}</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $tenant['unit_name'] }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $tenant['tenant_name'] }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $tenant['tenant_phone'] }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #ca8a04; border-bottom: 1px solid #e5e7eb;">{{ number_format($tenant['monthly_rent'], 2) }} ر.س</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            @php
                                $days = $tenant['remaining_days'];
                                $color = $days <= 0 ? '#dc2626' : ($days <= 30 ? '#ca8a04' : '#16a34a');
                                $bg = $days <= 0 ? '#fef2f2' : ($days <= 30 ? '#fefce8' : '#f0fdf4');
                            @endphp
                            <span style="background: {{ $bg }}; color: {{ $color }}; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                {{ $days > 0 ? $days . ' يوم' : 'منتهي' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Footer للطباعة فقط --}}
    <div class="print-only print-footer">
        <p>نظام إدارة العقارات © {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</div>
