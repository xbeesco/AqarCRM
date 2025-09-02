<x-filament-panels::page>
    
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
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">تقرير العقارات</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">اسم العقار</th>
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
                        <td colspan="6" style="padding: 24px; text-align: center; color: #9ca3af; border-bottom: 1px solid #e5e7eb;">
                            لا توجد عقارات
                        </td>
                    </tr>
                @endforelse
                
                @if(count($propertiesReport) > 0)
                    <tr style="background: #f3f4f6;">
                        <td colspan="3" style="padding: 16px; font-size: 14px; font-weight: bold; color: #111827;">
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
    
    
</x-filament-panels::page>