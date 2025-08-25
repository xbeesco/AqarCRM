<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->unit_id)
            @php
                $data = $this->getUnitData();
            @endphp
            
            @if(!empty($data))
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- معلومات الوحدة -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">معلومات الوحدة</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">اسم الوحدة</dt>
                                <dd class="font-medium">{{ $data['unit']->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">العقار</dt>
                                <dd class="font-medium">{{ $data['unit']->property?->name ?? 'غير محدد' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">الفئة</dt>
                                <dd class="font-medium">{{ $data['unit']->unitCategory?->name_ar ?? 'غير محدد' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">سعر الإيجار</dt>
                                <dd class="font-medium">{{ number_format($data['unit']->rent_price, 2) }} ريال</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- حالة الوحدة -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">حالة الوحدة</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">الحالة</dt>
                                <dd class="font-medium">
                                    @if($data['isOccupied'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            مؤجرة
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            شاغرة
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            @if($data['currentTenant'])
                                <div>
                                    <dt class="text-sm text-gray-500">المستأجر الحالي</dt>
                                    <dd class="font-medium">{{ $data['currentTenant']->name }}</dd>
                                </div>
                            @endif
                            @if($data['currentContract'])
                                <div>
                                    <dt class="text-sm text-gray-500">رقم العقد</dt>
                                    <dd class="font-medium">{{ $data['currentContract']->contract_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">تاريخ انتهاء العقد</dt>
                                    <dd class="font-medium">{{ $data['currentContract']->end_date }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- الإحصائيات المالية -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">الإحصائيات المالية</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">إجمالي الإيرادات</dt>
                                <dd class="font-medium text-green-600">{{ number_format($data['totalRevenue'], 2) }} ريال</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">المستحقات</dt>
                                <dd class="font-medium text-yellow-600">{{ number_format($data['outstandingPayments'], 2) }} ريال</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">تكاليف الصيانة</dt>
                                <dd class="font-medium text-red-600">{{ number_format($data['maintenanceCosts'], 2) }} ريال</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">صافي الدخل</dt>
                                <dd class="font-medium {{ $data['netIncome'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($data['netIncome'], 2) }} ريال
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- معدلات الإشغال -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
                    <h3 class="text-lg font-semibold mb-4">معدلات الإشغال للفترة</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">معدل الإشغال</dt>
                            <dd class="font-medium text-2xl">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-medium 
                                    {{ $data['occupancyRate'] >= 80 ? 'bg-green-100 text-green-800' : ($data['occupancyRate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $data['occupancyRate'] }}%
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">الأيام المؤجرة</dt>
                            <dd class="font-medium text-2xl text-green-600">{{ $data['occupiedDays'] }} يوم</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">إجمالي أيام الفترة</dt>
                            <dd class="font-medium text-2xl">{{ $data['totalDays'] }} يوم</dd>
                        </div>
                    </div>
                </div>

                <!-- سجل الإيجارات -->
                @if($data['rentalHistory'] && $data['rentalHistory']->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
                        <h3 class="text-lg font-semibold mb-4">سجل الإيجارات الأخيرة</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستأجر</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">من تاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إلى تاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">القيمة الشهرية</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800">
                                    @foreach($data['rentalHistory'] as $contract)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $contract->tenant?->name ?? 'غير محدد' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $contract->start_date }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $contract->end_date }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($contract->monthly_amount, 2) }} ريال</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    {{ $contract->contract_status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $contract->contract_status == 'active' ? 'نشط' : 'منتهي' }}
                                                </span>
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
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">لم يتم اختيار وحدة</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">يرجى اختيار عقار ثم وحدة من القوائم أعلاه لعرض التقرير.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>