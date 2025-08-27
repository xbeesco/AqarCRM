<x-filament-panels::page>
    
    {{-- اسم المستأجر والعنوان --}}
    <div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; text-align: center;">
        <h1 style="font-size: 28px; font-weight: bold; color: #111827; margin: 0;">
            {{ $tenant->name }}
        </h1>
        <p style="font-size: 16px; color: #6b7280; margin-top: 8px;">
            تقرير المستأجر
        </p>
    </div>
    
    {{-- جدول تقرير المستأجر --}}
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">بيانات المستأجر</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم المستأجر</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رقم التواصل</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العقار المستأجر</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رقم الوحدة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">نوع الوحدة</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">التأمين</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">عدد الدفعات</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">تاريخ بداية العقد</th>
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                            {{ $reportData['tenant_name'] }}
                        </span>
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                        {{ $reportData['phone'] }}
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                        {{ $reportData['property_name'] }}
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                        <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                            {{ $reportData['unit_number'] }}
                        </span>
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                        {{ $reportData['unit_type'] }}
                    </td>
                    <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #10b981; border-bottom: 1px solid #e5e7eb;">
                        {{ number_format($reportData['security_deposit']) }} ر.س
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; text-align: center;">
                        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                            {{ $reportData['payment_count'] }}
                        </span>
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb;">
                        @if($reportData['contract_start'] && $reportData['contract_start'] != '-')
                            {{ \Carbon\Carbon::parse($reportData['contract_start'])->format('Y-m-d') }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="padding: 16px; font-size: 14px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">
                        {{ $reportData['notes'] }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
</x-filament-panels::page>