@php
    $collectionTotal = $collectionTotal ?? 0;
    $supplyTotal = $supplyTotal ?? 0;
    $generalReport = $generalReport ?? [];
    $operationsReport = $operationsReport ?? [];
    $detailedReport = $detailedReport ?? [];
@endphp

<div class="space-y-6">
    {{-- Widget حالة التحصيل والتوريد --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">حالة التحصيل</div>
                    <div class="text-3xl font-bold text-green-600 mt-2">
                        {{ number_format($collectionTotal) }} <span class="text-lg">ر.س</span>
                    </div>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">حالة التوريد</div>
                    <div class="text-3xl font-bold text-blue-600 mt-2">
                        {{ number_format($supplyTotal) }} <span class="text-lg">ر.س</span>
                    </div>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    {{-- زر الطباعة العام --}}
    <div class="flex justify-end mb-4">
        <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            <svg class="w-5 h-5 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            طباعة عامة
        </button>
    </div>
    
    {{-- جدول التقرير العقاري بصفة عامة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800">تقرير عقاري بصفة عامة</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">اسم العقار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">اسم المالك</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">عدد الوحدات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">حالة العقار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">تحصيل الإيجار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">الدفعة القادمة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">تاريخ التحصيل</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $generalReport['property_name'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $generalReport['owner_name'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-center">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-lg">{{ $generalReport['units_count'] ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-lg">{{ $generalReport['property_status'] ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-green-600">{{ number_format($generalReport['collected_rent'] ?? 0) }} ر.س</td>
                        <td class="px-6 py-4 text-sm font-medium text-blue-600">{{ number_format($generalReport['next_collection'] ?? 0) }} ر.س</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $generalReport['next_collection_date'] ? \Carbon\Carbon::parse($generalReport['next_collection_date'])->format('Y-m-d') : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t flex justify-center">
            <button onclick="printTable('general-report')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                طباعة الجدول
            </button>
        </div>
    </div>
    
    {{-- جدول التقرير العقاري بالعمليات العامة والخاصة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800">تقرير عقاري: بالعمليات العامة والخاصة مع الالتزامات</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">اسم العملية</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">نوع العملية</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">قيمة العملية</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($operationsReport as $operation)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $operation['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-lg">{{ $operation['type'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-red-600">{{ number_format($operation['amount']) }} ر.س</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                لا توجد عمليات
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t flex justify-center">
            <button onclick="printTable('operations-report')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                طباعة الجدول
            </button>
        </div>
    </div>
    
    {{-- جدول التقرير العقاري بصفة خاصة --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800">تقرير عقاري بصفة خاصة: قيمة الإيجار حسب عدد الدفعات</h3>
            <p class="text-sm text-gray-600 mt-1">النسبة حسب ما هو محدد تجدول إلكترونياً مع ذكر كم بالمئة %</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">رقم الوحدة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">اسم المستأجر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">مجموع الدفعات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">تاريخ الدفعة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">مبلغ الدفعة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">النسبة الإدارية 5%</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">صيانة + التزام</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">صافي الصرف</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($detailedReport['data'] ?? [] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['unit_number'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row['tenant_name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-center">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-lg">{{ $row['total_payments'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row['payment_date'] ? \Carbon\Carbon::parse($row['payment_date'])->format('Y-m-d') : '-' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ number_format($row['amount']) }} ر.س</td>
                            <td class="px-6 py-4 text-sm text-orange-600">{{ number_format($row['admin_fee']) }} ر.س</td>
                            <td class="px-6 py-4 text-sm text-red-600">{{ number_format($row['maintenance']) }} ر.س</td>
                            <td class="px-6 py-4 text-sm font-medium text-green-600">{{ number_format($row['net']) }} ر.س</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                لا توجد بيانات
                            </td>
                        </tr>
                    @endforelse
                    
                    @if(count($detailedReport['data'] ?? []) > 0)
                        {{-- صف العمليات العامة --}}
                        <tr class="bg-yellow-50 font-semibold">
                            <td colspan="4" class="px-6 py-3 text-sm text-gray-800">العمليات العامة (صيانة خاصة + التزام حكومي)</td>
                            <td class="px-6 py-3 text-sm text-gray-600">-</td>
                            <td class="px-6 py-3 text-sm text-gray-600">-</td>
                            <td class="px-6 py-3 text-sm text-red-600">{{ number_format($detailedReport['totals']['maintenance']) }} ر.س</td>
                            <td class="px-6 py-3 text-sm text-green-600">{{ number_format($detailedReport['totals']['net']) }} ر.س</td>
                        </tr>
                        
                        {{-- صف الإجمالي --}}
                        <tr class="bg-blue-50 font-bold">
                            <td colspan="4" class="px-6 py-3 text-sm text-blue-900">الإجمالي الكلي</td>
                            <td class="px-6 py-3 text-sm text-blue-900">{{ number_format($detailedReport['totals']['amount']) }} ر.س</td>
                            <td class="px-6 py-3 text-sm text-orange-700">{{ number_format($detailedReport['totals']['admin_fee']) }} ر.س</td>
                            <td class="px-6 py-3 text-sm text-red-700">{{ number_format($detailedReport['totals']['maintenance']) }} ر.س</td>
                            <td class="px-6 py-3 text-sm text-green-700">{{ number_format($detailedReport['totals']['net']) }} ر.س</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t flex justify-center">
            <button onclick="printTable('detailed-report')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                طباعة الجدول
            </button>
        </div>
    </div>
</div>

<script>
    function printTable(tableId) {
        window.print();
    }
</script>