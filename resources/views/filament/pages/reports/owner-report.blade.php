<x-filament-panels::page>
    <div class="space-y-6">
        @if(!empty($reportData))
            <!-- معلومات المالك الأساسية -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">معلومات المالك</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">اسم المالك</p>
                        <p class="text-lg font-medium">{{ $reportData['owner']->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">الفترة</p>
                        <p class="text-lg font-medium">
                            {{ $reportData['dateFrom']->format('Y-m-d') }} - {{ $reportData['dateTo']->format('Y-m-d') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">عدد العقارات</p>
                        <p class="text-lg font-medium">{{ $reportData['propertiesCount'] }}</p>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات المالية -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-green-600 dark:text-green-400">إجمالي التحصيل</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                        {{ number_format($reportData['totalCollection']) }} ر.س
                    </p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <p class="text-sm text-red-600 dark:text-red-400">المستحقات</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">
                        {{ number_format($reportData['outstandingPayments']) }} ر.س
                    </p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">النسبة الإدارية ({{ $reportData['managementPercentage'] }}%)</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                        {{ number_format($reportData['managementFee']) }} ر.س
                    </p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-blue-600 dark:text-blue-400">صافي الدخل</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                        {{ number_format($reportData['netIncome']) }} ر.س
                    </p>
                </div>
            </div>

            <!-- إحصائيات الوحدات -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">إحصائيات الوحدات</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-700 dark:text-gray-300">{{ $reportData['totalUnits'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">إجمالي الوحدات</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-600">{{ $reportData['occupiedUnits'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">وحدات مشغولة</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-red-600">{{ $reportData['vacantUnits'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">وحدات شاغرة</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600">{{ $reportData['occupancyRate'] }}%</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">نسبة الإشغال</p>
                    </div>
                </div>
            </div>

            <!-- تفاصيل العقارات -->
            @if(!empty($reportData['propertiesDetails']) && count($reportData['propertiesDetails']) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">تفاصيل العقارات</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        العقار
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        إجمالي الوحدات
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        مشغولة
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        شاغرة
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        نسبة الإشغال
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        الإيرادات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['propertiesDetails'] as $property)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $property['name'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                            {{ $property['total_units'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-green-600">
                                            {{ $property['occupied_units'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-red-600">
                                            {{ $property['vacant_units'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $property['occupancy_rate'] >= 80 ? 'bg-green-100 text-green-800' : ($property['occupancy_rate'] >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $property['occupancy_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                            {{ number_format($property['revenue']) }} ر.س
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- ملخص الصيانة والتحويلات -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">الصيانة</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">طلبات الصيانة</span>
                            <span class="font-medium">{{ $reportData['maintenanceRequests'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">المكتملة</span>
                            <span class="font-medium">{{ $reportData['completedMaintenance'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">التكلفة الإجمالية</span>
                            <span class="font-medium text-red-600">{{ number_format($reportData['maintenanceCosts']) }} ر.س</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">التحويلات المالية</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">المبالغ المحولة</span>
                            <span class="font-medium text-green-600">{{ number_format($reportData['transferredAmount']) }} ر.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">الرصيد المتبقي</span>
                            <span class="font-medium text-blue-600">{{ number_format($reportData['balance']) }} ر.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">معدل التحصيل</span>
                            <span class="font-medium">{{ $reportData['paymentStats']['collection_rate'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>