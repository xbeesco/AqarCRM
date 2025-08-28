<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 20mm 15mm;
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
            padding: 20px 0;
            border-bottom: 2px solid #333;
            margin-bottom: 30px;
        }
        
        .print-header h2 {
            margin: 0;
            font-size: 18pt;
            color: #333;
        }
        
        .print-header p {
            margin: 5px 0 0 0;
            font-size: 12pt;
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
            font-size: 10pt;
            color: #666;
        }
    }
    
    /* إخفاء العناصر في العرض العادي */
    .print-only {
        display: none;
    }
    
    @media print {
        .print-only {
            display: block !important;
        }
    }
</style>

<div class="print-content">
    {{-- Header للطباعة فقط --}}
    <div class="print-only print-header">
        <h2>نظام إدارة العقارات</h2>
        <p>تقرير المالك</p>
        <p style="font-size: 10pt;">التاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d') }} | الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}</p>
    </div>
    
    {{-- اسم المالك والعنوان --}}
    <div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; text-align: center;">
        <h1 style="font-size: 28px; font-weight: bold; color: #111827; margin: 0;">
            {{ $owner->name }}
        </h1>
        <p style="font-size: 16px; color: #6b7280; margin-top: 8px;">
            تقرير المالك - التليفون: {{ $owner->phone }}
            @if($owner->secondary_phone)
                / {{ $owner->secondary_phone }}
            @endif
        </p>
    </div>
    
    {{-- الجدول الأول - تقرير العقارات --}}
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px;">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">تقرير العقارات</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العقار</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المكان</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">سكني</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">تجاري</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">صنف العقار</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">دفعات التحصيل</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المحصل</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">نسبة الإدارة %</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رسوم الإدارة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($propertiesReport as $property)
                    <tr>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                {{ $property['property_name'] }}
                            </span>
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            {{ $property['location'] }}
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            @if($property['is_residential'] == 'نعم')
                                <span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 6px; font-size: 12px;">✓</span>
                            @else
                                <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            @if($property['is_commercial'] == 'نعم')
                                <span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 6px; font-size: 12px;">✓</span>
                            @else
                                <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            {{ $property['property_category'] }}
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                {{ $property['collection_payments'] }}
                            </span>
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #10b981; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($property['total_income'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 13px;">
                                {{ $property['admin_percentage'] }}%
                            </span>
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #ef4444; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($property['admin_fee'], 2) }} ر.س
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 24px; text-align: center; color: #9ca3af; border-bottom: 1px solid #e5e7eb;">
                            لا توجد عقارات
                        </td>
                    </tr>
                @endforelse
                
                @if(count($propertiesReport) > 0)
                    <tr style="background: #f3f4f6;">
                        <td colspan="6" style="padding: 16px; font-size: 14px; font-weight: bold; color: #111827;">
                            الإجمالي
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: bold; color: #10b981;">
                            {{ number_format($propertiesTotal['total_income'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; text-align: center;">
                            -
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: bold; color: #ef4444;">
                            {{ number_format($propertiesTotal['total_admin_fee'], 2) }} ر.س
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    {{-- الجدول الثاني - تقرير مالك العقار التفصيلي --}}
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">تقرير مالك العقار التفصيلي</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th rowspan="2" style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">اسم العقار</th>
                    <th colspan="2" style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">عدد الوحدات</th>
                    <th colspan="2" style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">تاريخ الدفعة</th>
                    <th rowspan="2" style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">مبلغ الدفعة</th>
                    <th rowspan="2" style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">رسوم الإدارة</th>
                    <th colspan="2" style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">الصيانة والالتزامات</th>
                    <th rowspan="2" style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">صافي المبلغ للمالك</th>
                </tr>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">العدد</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">النوع</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">من</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">إلى</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">خاصة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">حكومية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ownerDetailedReport as $report)
                    <tr>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                            <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                {{ $report['property_name'] }}
                            </span>
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            {{ $report['units_count'] }}
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            {{ $report['unit_type'] }}
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            @if($report['payment_date_from'] && $report['payment_date_from'] != '-')
                                {{ \Carbon\Carbon::parse($report['payment_date_from'])->format('Y-m-d') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            @if($report['payment_date_to'] && $report['payment_date_to'] != '-')
                                {{ \Carbon\Carbon::parse($report['payment_date_to'])->format('Y-m-d') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #10b981; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($report['payment_amount'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #ef4444; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($report['admin_fee'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #f59e0b; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($report['maintenance_special'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; color: #8b5cf6; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($report['government_obligations'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #059669; border-bottom: 1px solid #e5e7eb;">
                            {{ number_format($report['net_income'], 2) }} ر.س
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="padding: 24px; text-align: center; color: #9ca3af; border-bottom: 1px solid #e5e7eb;">
                            لا توجد بيانات
                        </td>
                    </tr>
                @endforelse
                
                @if(count($ownerDetailedReport) > 0)
                    {{-- مجموع الصيانة الخاصة --}}
                    <tr style="background: #fef3c7;">
                        <td colspan="7" style="padding: 16px; font-size: 14px; font-weight: 600; color: #92400e;">
                            مجموع الصيانة الخاصة
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: bold; color: #f59e0b;">
                            {{ number_format($detailedTotals['maintenance_special'], 2) }} ر.س
                        </td>
                        <td colspan="2" style="padding: 16px;"></td>
                    </tr>
                    
                    {{-- مجموع الالتزامات الحكومية --}}
                    <tr style="background: #fef3c7;">
                        <td colspan="8" style="padding: 16px; font-size: 14px; font-weight: 600; color: #92400e;">
                            مجموع الالتزامات الحكومية
                        </td>
                        <td style="padding: 16px; font-size: 14px; font-weight: bold; color: #8b5cf6;">
                            {{ number_format($detailedTotals['government_obligations'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px;"></td>
                    </tr>
                    
                    {{-- الصيانة العامة --}}
                    <tr style="background: #dbeafe;">
                        <td colspan="7" style="padding: 16px; font-size: 14px; font-weight: 600; color: #1e40af;">
                            الصيانة العامة
                        </td>
                        <td colspan="2" style="padding: 16px; font-size: 14px; font-weight: bold; color: #1e40af;">
                            {{ number_format($detailedTotals['general_maintenance'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px;"></td>
                    </tr>
                    
                    {{-- الالتزامات العامة --}}
                    <tr style="background: #dbeafe;">
                        <td colspan="7" style="padding: 16px; font-size: 14px; font-weight: 600; color: #1e40af;">
                            الالتزامات العامة
                        </td>
                        <td colspan="2" style="padding: 16px; font-size: 14px; font-weight: bold; color: #1e40af;">
                            {{ number_format($detailedTotals['general_obligations'], 2) }} ر.س
                        </td>
                        <td style="padding: 16px;"></td>
                    </tr>
                    
                    {{-- الإجمالي الكلي --}}
                    <tr style="background: #374151;">
                        <td colspan="9" style="padding: 16px; font-size: 16px; font-weight: bold; color: white;">
                            الإجمالي الكلي
                        </td>
                        <td style="padding: 16px; font-size: 16px; font-weight: bold; color: #10b981; background: #374151;">
                            {{ number_format($detailedTotals['grand_total'], 2) }} ر.س
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    {{-- Footer للطباعة فقط --}}
    <div class="print-only print-footer">
        <p>نظام إدارة العقارات © {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</div>