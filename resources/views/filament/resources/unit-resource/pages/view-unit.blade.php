<x-filament-panels::page>
    
    {{-- عنوان الوحدة --}}
    <div id="unit-header" style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
        <h1 style="font-size: 24px; font-weight: bold; color: #111827; margin: 0;">
            شقة رقم {{ $unit->id }} 
            @if($unit->property)
                في عمارة {{ $unit->property->name }}
            @endif
        </h1>
    </div>
    
    {{-- جدول تقرير الوحدة --}}
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; overflow: hidden;" id="unit-table-container">
        <div style="background: #f9fafb; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">تقرير الوحدة العقارية</h3>
        </div>
        <table style="width: 100%;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; width: 20%;">نوع الوحدة</th>
                    <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; width: 20%;">رقم الوحدة</th>
                    <th colspan="2" style="padding: 12px 24px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb; width: 60%;">مواصفات الوحدة</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rowsCount = max(1, count($specifications));
                @endphp
                
                @for($i = 0; $i < $rowsCount; $i++)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        @if($i == 0)
                            <td rowspan="{{ $rowsCount }}" style="padding: 16px 24px; font-size: 14px; color: #111827; vertical-align: middle; border-left: 1px solid #e5e7eb;">
                                <span style="background: #dbeafe; color: #1e40af; padding: 6px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block;">
                                    {{ $unit->unitType?->name_ar ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td rowspan="{{ $rowsCount }}" style="padding: 16px 24px; font-size: 14px; color: #111827; vertical-align: middle; border-left: 1px solid #e5e7eb;">
                                <span style="background: #d1fae5; color: #065f46; padding: 6px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block;">
                                    {{ $unit->id }}
                                </span>
                            </td>
                        @endif
                        
                        @if(isset($specifications[$i]))
                            <td style="padding: 12px 16px; font-size: 13px; font-weight: 600; color: #6b7280; background: #f9fafb; border-left: 1px solid #e5e7eb; width: 30%;">
                                {{ $specifications[$i]['label'] }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 14px; color: #111827; font-weight: 500;">
                                {{ $specifications[$i]['value'] }}
                            </td>
                        @else
                            <td style="padding: 12px 16px; border-left: 1px solid #e5e7eb;">&nbsp;</td>
                            <td style="padding: 12px 16px;">&nbsp;</td>
                        @endif
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    
</x-filament-panels::page>