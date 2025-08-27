<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير العقار - {{ $record->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            background: white;
            color: #333;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .print-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .print-header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .print-header .property-info {
            font-size: 16px;
            color: #6b7280;
        }
        
        .widgets-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .widget-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .widget-card.collection {
            border-color: #10b981;
        }
        
        .widget-card.supply {
            border-color: #3b82f6;
        }
        
        .widget-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .widget-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .widget-value.collection {
            color: #10b981;
        }
        
        .widget-value.supply {
            color: #3b82f6;
        }
        
        .widget-currency {
            font-size: 14px;
            color: #6b7280;
            font-weight: normal;
        }
        
        .table-container {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 40px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .table-subtitle {
            font-size: 12px;
            color: #64748b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            padding: 15px 12px;
            text-align: right;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px 12px;
            font-size: 13px;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
        }
        
        tr:hover {
            background: #fefefe;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-green {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .text-green {
            color: #10b981;
            font-weight: 600;
        }
        
        .text-blue {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .text-orange {
            color: #f59e0b;
            font-weight: 600;
        }
        
        .text-red {
            color: #dc2626;
            font-weight: 600;
        }
        
        .text-bold {
            font-weight: 700;
        }
        
        .row-total {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            font-weight: 700;
            border-top: 2px solid #d1d5db;
        }
        
        .row-operations {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            font-weight: 600;
            color: #92400e;
        }
        
        .row-grand-total {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            font-weight: 700;
            color: #1e40af;
            border-top: 3px solid #3b82f6;
        }
        
        .no-data {
            padding: 60px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            font-size: 16px;
        }
        
        .print-footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            
            body {
                font-size: 12px;
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .print-container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            
            .table-container {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 20px;
            }
            
            .widgets-section {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 20px;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            .widget-card {
                padding: 15px;
                box-shadow: none;
                border: 1px solid #d1d5db;
            }
            
            .print-header {
                margin-bottom: 20px;
            }
            
            .print-header h1 {
                font-size: 20px;
            }
            
            th, td {
                padding: 8px 6px;
            }
            
            .table-title {
                font-size: 14px;
            }
            
            .table-header {
                padding: 12px;
            }
        }
        
        /* Auto-print when page loads */
        @media screen {
            .auto-print {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
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
            
            .auto-print:hover {
                background: #2563eb;
            }
        }
        
        @media print {
            .auto-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <button class="auto-print" onclick="window.print()">طباعة التقرير</button>
    
    <div class="print-container">
        {{-- Header --}}
        <div class="print-header">
            <h1>تقرير العقار التفصيلي</h1>
            <div class="property-info">
                <strong>{{ $record->name }}</strong> - 
                المالك: {{ $record->owner?->name ?? 'غير محدد' }} - 
                تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>

        {{-- Widgets Section --}}
        <div class="widgets-section">
            <div class="widget-card collection">
                <div class="widget-title">إجمالي التحصيل</div>
                <div class="widget-value collection">
                    {{ number_format($collectionTotal) }}
                    <span class="widget-currency">ر.س</span>
                </div>
            </div>
            
            <div class="widget-card supply">
                <div class="widget-title">إجمالي التوريد</div>
                <div class="widget-value supply">
                    {{ number_format($supplyTotal) }}
                    <span class="widget-currency">ر.س</span>
                </div>
            </div>
        </div>

        {{-- General Report Table --}}
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">تقرير عقاري بصفة عامة</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>اسم العقار</th>
                        <th>اسم المالك</th>
                        <th>عدد الوحدات</th>
                        <th>حالة العقار</th>
                        <th>تحصيل الإيجار</th>
                        <th>الدفعة القادمة</th>
                        <th>تاريخ التحصيل</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-bold">{{ $generalReport['property_name'] ?? '-' }}</td>
                        <td>{{ $generalReport['owner_name'] ?? '-' }}</td>
                        <td style="text-align: center;">
                            <span class="badge badge-blue">{{ $generalReport['units_count'] ?? 0 }}</span>
                        </td>
                        <td>
                            <span class="badge badge-yellow">{{ $generalReport['property_status'] ?? '-' }}</span>
                        </td>
                        <td class="text-green">{{ number_format($generalReport['collected_rent'] ?? 0) }} ر.س</td>
                        <td class="text-blue">{{ number_format($generalReport['next_collection'] ?? 0) }} ر.س</td>
                        <td>{{ $generalReport['next_collection_date'] ? \Carbon\Carbon::parse($generalReport['next_collection_date'])->format('Y-m-d') : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Operations Report Table --}}
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">تقرير عقاري: بالعمليات العامة والخاصة</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>اسم العملية</th>
                        <th>نوع العملية</th>
                        <th>قيمة العملية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operationsReport as $operation)
                        <tr>
                            <td>{{ $operation['name'] }}</td>
                            <td>
                                <span class="badge {{ $operation['type'] == 'تحصيل' ? 'badge-green' : 'badge-red' }}">
                                    {{ $operation['type'] }}
                                </span>
                            </td>
                            <td class="{{ $operation['type'] == 'تحصيل' ? 'text-green' : 'text-red' }}">
                                {{ number_format($operation['amount']) }} ر.س
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="no-data">لا توجد عمليات</td>
                        </tr>
                    @endforelse
                    
                    @if(count($operationsReport) > 0)
                        <tr class="row-total">
                            <td colspan="2" class="text-bold">الإجمالي</td>
                            <td class="text-bold">{{ number_format($operationsTotal ?? 0) }} ر.س</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Detailed Report Table --}}
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">تقرير عقاري تفصيلي: قيمة الإيجار حسب الدفعات</div>
                <div class="table-subtitle">النسبة الإدارية 5%</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم الوحدة</th>
                        <th>اسم المستأجر</th>
                        <th>عدد الدفعات</th>
                        <th>تاريخ الدفعة</th>
                        <th>المبلغ</th>
                        <th>النسبة الإدارية</th>
                        <th>صيانة</th>
                        <th>صافي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detailedReport['data'] ?? [] as $row)
                        <tr>
                            <td class="text-bold">{{ $row['unit_number'] }}</td>
                            <td>{{ $row['tenant_name'] }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-blue">{{ $row['total_payments'] }}</span>
                            </td>
                            <td>{{ $row['payment_date'] ? \Carbon\Carbon::parse($row['payment_date'])->format('Y-m-d') : '-' }}</td>
                            <td class="text-bold">{{ number_format($row['amount']) }}</td>
                            <td class="text-orange">{{ number_format($row['admin_fee']) }}</td>
                            <td class="text-red">{{ number_format($row['maintenance']) }}</td>
                            <td class="text-green">{{ number_format($row['net']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="no-data">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                    
                    @if(count($detailedReport['data'] ?? []) > 0)
                        <tr class="row-operations">
                            <td colspan="4" class="text-bold">العمليات العامة</td>
                            <td>-</td>
                            <td>-</td>
                            <td class="text-red text-bold">{{ number_format($detailedReport['totals']['maintenance']) }}</td>
                            <td class="text-green text-bold">{{ number_format($detailedReport['totals']['net']) }}</td>
                        </tr>
                        
                        <tr class="row-grand-total">
                            <td colspan="4" class="text-bold">الإجمالي الكلي</td>
                            <td class="text-bold">{{ number_format($detailedReport['totals']['amount']) }}</td>
                            <td class="text-bold">{{ number_format($detailedReport['totals']['admin_fee']) }}</td>
                            <td class="text-bold">{{ number_format($detailedReport['totals']['maintenance']) }}</td>
                            <td class="text-bold">{{ number_format($detailedReport['totals']['net']) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="print-footer">
            <p>تم إنشاء هذا التقرير بواسطة نظام إدارة العقارات</p>
            <p>تاريخ الإنشاء: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Auto-print functionality
        window.addEventListener('load', function() {
            // Small delay to ensure content is fully loaded
            setTimeout(function() {
                window.print();
            }, 500);
        });

        // Handle print dialog close - return to previous page
        window.addEventListener('afterprint', function() {
            // Optional: close window or redirect back
            // window.close(); // Uncomment if you want to close the print window
        });
    </script>
</body>
</html>