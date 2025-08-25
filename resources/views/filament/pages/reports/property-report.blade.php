<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->property_id)
            @php
                $data = $this->getPropertyData();
            @endphp
            
            @if(!empty($data))
                <!-- معلومات العقار الأساسية -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">معلومات العقار</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">اسم العقار</p>
                            <p class="text-lg font-medium">{{ $data['property']->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">المالك</p>
                            <p class="text-lg font-medium">{{ $data['property']->owner?->name ?? 'غير محدد' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">الموقع</p>
                            <p class="text-lg font-medium">{{ $data['property']->location?->name_ar ?? 'غير محدد' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">إجمالي الوحدات</p>
                            <p class="text-lg font-medium">{{ $data['totalUnits'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات الإشغال -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">إحصائيات الإشغال</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">الوحدات المؤجرة</p>
                            <p class="text-2xl font-bold text-green-600">{{ $data['occupiedUnits'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">الوحدات الشاغرة</p>
                            <p class="text-2xl font-bold text-red-600">{{ $data['vacantUnits'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">معدل الإشغال</p>
                            <p class="text-2xl font-bold">{{ $data['occupancyRate'] }}%</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">العقود النشطة</p>
                            <p class="text-2xl font-bold">{{ $data['activeContracts'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- الإحصائيات المالية -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">الإحصائيات المالية</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">إجمالي الإيرادات</p>
                            <p class="text-xl font-bold text-green-600">{{ number_format($data['totalRevenue'], 2) }} ريال</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">المستحقات</p>
                            <p class="text-xl font-bold text-yellow-600">{{ number_format($data['outstandingPayments'], 2) }} ريال</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">تكاليف الصيانة</p>
                            <p class="text-xl font-bold text-red-600">{{ number_format($data['maintenanceCosts'], 2) }} ريال</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">صافي الدخل</p>
                            <p class="text-xl font-bold {{ $data['netIncome'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                {{ number_format($data['netIncome'], 2) }} ريال
                            </p>
                        </div>
                    </div>
                </div>

                <!-- معلومات الإيجار -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">معلومات الإيجار</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">متوسط سعر الإيجار</p>
                            <p class="text-xl font-bold">{{ number_format($data['averageRent'], 2) }} ريال</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">الإيجار الشهري المحتمل</p>
                            <p class="text-xl font-bold">{{ number_format($data['monthlyRentPotential'], 2) }} ريال</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">الإيجار الشهري الفعلي</p>
                            <p class="text-xl font-bold">{{ number_format($data['actualMonthlyRent'], 2) }} ريال</p>
                        </div>
                    </div>
                </div>

                <!-- فترة التقرير -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">فترة التقرير</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">من تاريخ</p>
                            <p class="text-lg font-medium">{{ $data['dateFrom']->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">إلى تاريخ</p>
                            <p class="text-lg font-medium">{{ $data['dateTo']->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">نوع التقرير</p>
                            <p class="text-lg font-medium">
                                @switch($this->report_type)
                                    @case('summary')
                                        مختصر
                                        @break
                                    @case('detailed')
                                        تفصيلي
                                        @break
                                    @case('occupancy')
                                        الإشغال
                                        @break
                                    @case('financial')
                                        مالي
                                        @break
                                    @default
                                        {{ $this->report_type }}
                                @endswitch
                            </p>
                        </div>
                    </div>
                </div>

                <!-- جدول الوحدات إذا كان التقرير تفصيلي -->
                @if($this->report_type == 'detailed' && $data['property']->units->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold">تفاصيل الوحدات</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الوحدة</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">النوع</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الحالة</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">المستأجر</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإيجار</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($data['property']->units as $unit)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $unit->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                                {{ $unit->unitCategory?->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @php
                                                    $activeContract = $unit->contracts()->where('contract_status', 'active')->first();
                                                @endphp
                                                @if($activeContract)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        مؤجرة
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        شاغرة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                                @if($activeContract && $activeContract->tenant)
                                                    {{ $activeContract->tenant->name }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                                {{ number_format($unit->rent_price) }} ريال
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">لم يتم اختيار عقار</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">يرجى اختيار عقار من القائمة أعلاه لعرض التقرير.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>