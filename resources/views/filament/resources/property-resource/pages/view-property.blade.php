<x-filament-panels::page>
    
    @push('styles')
    <style>
        @media print {
            /* إخفاء عناصر Filament */
            .fi-sidebar,
            .fi-sidebar-open,
            .fi-topbar,
            .fi-header,
            .fi-breadcrumbs,
            .fi-page-header,
            .fi-page-actions,
            .fi-header-actions,
            .fi-header-wrapper,
            .fi-navigation,
            header,
            aside,
            nav,
            [data-slot="sidebar"],
            [data-slot="topbar"],
            .fi-btn,
            button {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* تنسيق الصفحة */
            @page {
                size: A4;
                margin: 15mm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
            }
            
            .fi-main,
            .fi-main-ctn,
            .fi-page,
            .fi-page-content {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            /* إظهار المحتوى فقط */
            .fi-section-content,
            #widgets-section,
            #table1-container,
            #table2-container,
            #table3-container {
                display: block !important;
                visibility: visible !important;
                background: white !important;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            /* تحسين الجداول للطباعة */
            table {
                page-break-inside: avoid;
                width: 100% !important;
                border-collapse: collapse !important;
            }
            
            tr {
                page-break-inside: avoid;
            }
            
            th, td {
                padding: 6px 8px !important;
                font-size: 10px !important;
                border: 1px solid #ddd !important;
            }
            
            /* الويدجت */
            #widgets-section {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 15px !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }
            
            /* فواصل الصفحات */
            .table-container {
                page-break-inside: avoid;
                margin-bottom: 15px !important;
            }
            
            /* إخفاء الألوان المتدرجة */
            * {
                background-image: none !important;
            }
        }
        
        /* إضافة زر الطباعة للشاشة */
        @media screen {
            .print-screen-btn {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 9999;
                background: #3b82f6;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            }
            
            .print-screen-btn:hover {
                background: #2563eb;
            }
        }
        
        @media print {
            .print-screen-btn {
                display: none !important;
            }
        }
    </style>
    @endpush
    
    {{-- الويدجت - إجمالي التحصيل والتوريد --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;" id="widgets-section">
        {{-- ويدجت التحصيل --}}
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="color: #6b7280; font-size: 14px; font-weight: 600;">إجمالي التحصيل</div>
                    <div style="color: #10b981; font-size: 36px; font-weight: bold; margin-top: 8px;">
                        {{ number_format($collectionTotal) }}
                        <span style="font-size: 16px; color: #6b7280; font-weight: normal;">ر.س</span>
                    </div>
                </div>
                <div style="background: #d1fae5; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 32px; height: 32px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        {{-- ويدجت التوريد --}}
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="color: #6b7280; font-size: 14px; font-weight: 600;">إجمالي التوريد</div>
                    <div style="color: #3b82f6; font-size: 36px; font-weight: bold; margin-top: 8px;">
                        {{ number_format($supplyTotal) }}
                        <span style="font-size: 16px; color: #6b7280; font-weight: normal;">ر.س</span>
                    </div>
                </div>
                <div style="background: #dbeafe; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 32px; height: 32px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    {{-- الجدول الأول - تقرير عقاري عام --}}
    <div style="background: white; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;" id="table1-container">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">تقرير عقاري بصفة عامة</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العقار</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم المالك</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">عدد الوحدات</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">حالة العقار</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">تحصيل الإيجار</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الدفعة القادمة</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">تاريخ التحصيل</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 16px 24px; font-size: 14px; color: #111827;">{{ $generalReport['property_name'] ?? '-' }}</td>
                    <td style="padding: 16px 24px; font-size: 14px; color: #111827;">{{ $generalReport['owner_name'] ?? '-' }}</td>
                    <td style="padding: 16px 24px; text-align: center;">
                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">{{ $generalReport['units_count'] ?? 0 }}</span>
                    </td>
                    <td style="padding: 16px 24px;">
                        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">{{ $generalReport['property_status'] ?? '-' }}</span>
                    </td>
                    <td style="padding: 16px 24px; font-size: 14px; font-weight: 600; color: #10b981;">{{ number_format($generalReport['collected_rent'] ?? 0) }} ر.س</td>
                    <td style="padding: 16px 24px; font-size: 14px; font-weight: 600; color: #3b82f6;">{{ number_format($generalReport['next_collection'] ?? 0) }} ر.س</td>
                    <td style="padding: 16px 24px; font-size: 14px; color: #111827;">
                        {{ $generalReport['next_collection_date'] ? \Carbon\Carbon::parse($generalReport['next_collection_date'])->format('Y-m-d') : '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    {{-- الجدول الثاني - العمليات --}}
    <div style="background: white; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;" id="table2-container">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">تقرير عقاري: بالعمليات العامة والخاصة</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العملية</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">نوع العملية</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">قيمة العملية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operationsReport as $operation)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 16px 24px; font-size: 14px; color: #111827;">{{ $operation['name'] }}</td>
                        <td style="padding: 16px 24px;">
                            <span style="background: {{ $operation['type'] == 'تحصيل' ? '#d1fae5' : '#fee2e2' }}; color: {{ $operation['type'] == 'تحصيل' ? '#065f46' : '#991b1b' }}; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">{{ $operation['type'] }}</span>
                        </td>
                        <td style="padding: 16px 24px; font-size: 14px; font-weight: 600; color: {{ $operation['type'] == 'تحصيل' ? '#10b981' : '#dc2626' }};">{{ number_format($operation['amount']) }} ر.س</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding: 48px; text-align: center; color: #9ca3af;">لا توجد عمليات</td>
                    </tr>
                @endforelse
                
                @if(count($operationsReport) > 0)
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td colspan="2" style="padding: 16px 24px; font-size: 14px; color: #111827;">الإجمالي</td>
                        <td style="padding: 16px 24px; font-size: 14px; color: #1f2937;">{{ number_format($operationsTotal ?? 0) }} ر.س</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    {{-- الجدول الثالث - تفصيلي --}}
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;" id="table3-container">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">تقرير عقاري تفصيلي: قيمة الإيجار حسب الدفعات</h3>
                    <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">النسبة الإدارية 5%</p>
                </div>
            </div>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رقم الوحدة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم المستأجر</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">عدد الدفعات</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">تاريخ الدفعة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">المبلغ</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">النسبة الإدارية</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">صيانة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">صافي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailedReport['data'] ?? [] as $row)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #111827;">{{ $row['unit_number'] }}</td>
                        <td style="padding: 12px 16px; font-size: 13px; color: #374151;">{{ $row['tenant_name'] }}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $row['total_payments'] }}</span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 13px; color: #374151;">
                            {{ $row['payment_date'] ? \Carbon\Carbon::parse($row['payment_date'])->format('Y-m-d') : '-' }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #111827;">{{ number_format($row['amount']) }}</td>
                        <td style="padding: 12px 16px; font-size: 13px; color: #f59e0b;">{{ number_format($row['admin_fee']) }}</td>
                        <td style="padding: 12px 16px; font-size: 13px; color: #dc2626;">{{ number_format($row['maintenance']) }}</td>
                        <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #10b981;">{{ number_format($row['net']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 48px; text-align: center; color: #9ca3af;">لا توجد بيانات</td>
                    </tr>
                @endforelse
                
                @if(count($detailedReport['data'] ?? []) > 0)
                    <tr style="background: #fef3c7;">
                        <td colspan="4" style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #92400e;">العمليات العامة</td>
                        <td style="padding: 12px 16px;">-</td>
                        <td style="padding: 12px 16px;">-</td>
                        <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #dc2626;">{{ number_format($detailedReport['totals']['maintenance']) }}</td>
                        <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #10b981;">{{ number_format($detailedReport['totals']['net']) }}</td>
                    </tr>
                    
                    <tr style="background: #dbeafe;">
                        <td colspan="4" style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #1e40af;">الإجمالي الكلي</td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #1e40af;">{{ number_format($detailedReport['totals']['amount']) }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #f59e0b;">{{ number_format($detailedReport['totals']['admin_fee']) }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #dc2626;">{{ number_format($detailedReport['totals']['maintenance']) }}</td>
                        <td style="padding: 12px 16px; font-size: 14px; font-weight: bold; color: #10b981;">{{ number_format($detailedReport['totals']['net']) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
</x-filament-panels::page>