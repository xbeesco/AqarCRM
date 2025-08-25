<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->property_id)
            @php
                $data = $this->getPropertyData();
            @endphp
            
            @if(!empty($data))
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- معلومات العقار -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">معلومات العقار</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">اسم العقار</dt>
                                <dd class="font-medium">{{ $data['property']->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">المالك</dt>
                                <dd class="font-medium">{{ $data['property']->owner?->name ?? 'غير محدد' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">الموقع</dt>
                                <dd class="font-medium">{{ $data['property']->location?->name_ar ?? 'غير محدد' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">إجمالي الوحدات</dt>
                                <dd class="font-medium">{{ $data['totalUnits'] }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- إحصائيات الإشغال -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">إحصائيات الإشغال</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">الوحدات المؤجرة</dt>
                                <dd class="font-medium text-green-600">{{ $data['occupiedUnits'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">الوحدات الشاغرة</dt>
                                <dd class="font-medium text-red-600">{{ $data['vacantUnits'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">معدل الإشغال</dt>
                                <dd class="font-medium">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $data['occupancyRate'] >= 80 ? 'bg-green-100 text-green-800' : ($data['occupancyRate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $data['occupancyRate'] }}%
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">العقود النشطة</dt>
                                <dd class="font-medium">{{ $data['activeContracts'] }}</dd>
                            </div>
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

                <!-- معلومات إضافية -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">معلومات الإيجار</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">متوسط سعر الإيجار</dt>
                                <dd class="font-medium">{{ number_format($data['averageRent'], 2) }} ريال</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">الإيجار الشهري المحتمل</dt>
                                <dd class="font-medium">{{ number_format($data['monthlyRentPotential'], 2) }} ريال</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">الإيجار الشهري الفعلي</dt>
                                <dd class="font-medium">{{ number_format($data['actualMonthlyRent'], 2) }} ريال</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">فترة التقرير</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">من تاريخ</dt>
                                <dd class="font-medium">{{ $data['dateFrom']->format('Y-m-d') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">إلى تاريخ</dt>
                                <dd class="font-medium">{{ $data['dateTo']->format('Y-m-d') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">نوع التقرير</dt>
                                <dd class="font-medium">
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
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">لم يتم اختيار عقار</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">يرجى اختيار عقار من القائمة أعلاه لعرض التقرير.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>