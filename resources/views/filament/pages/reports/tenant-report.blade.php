<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Filters --}}
        <x-filament-panels::form 
            wire:submit="$refresh"
        >
            {{ $this->form }}
            
            <x-filament::actions>
                <x-filament::button 
                    wire:click="$refresh"
                    icon="heroicon-o-arrow-path"
                    color="primary"
                    size="sm"
                >
                    تحديث التقرير
                </x-filament::button>
            </x-filament::actions>
        </x-filament-panels::form>

        {{-- Report Content --}}
        @if($tenant_id)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print:shadow-none print:border-none">
                <div class="mb-6 text-center border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-900">تقرير المستأجر التفصيلي</h2>
                    <p class="text-gray-600 mt-2">
                        الفترة من {{ $date_from ? \Carbon\Carbon::parse($date_from)->format('Y/m/d') : '' }} 
                        إلى {{ $date_to ? \Carbon\Carbon::parse($date_to)->format('Y/m/d') : '' }}
                    </p>
                </div>

                {{-- Tenant Information --}}
                @php
                    $tenantData = $this->getTenantData();
                @endphp

                @if(!empty($tenantData))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">معلومات المستأجر</h3>
                            <div class="space-y-2 text-sm">
                                <p><span class="font-medium">الاسم:</span> {{ $tenantData['tenant']->name ?? 'غير محدد' }}</p>
                                <p><span class="font-medium">البريد الإلكتروني:</span> {{ $tenantData['tenant']->email ?? 'غير محدد' }}</p>
                                <p><span class="font-medium">الهاتف:</span> {{ $tenantData['tenant']->phone ?? 'غير محدد' }}</p>
                                <p><span class="font-medium">الهوية الوطنية:</span> {{ $tenantData['tenant']->national_id ?? 'غير محدد' }}</p>
                            </div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900 mb-2">معلومات الوحدة الحالية</h3>
                            <div class="space-y-2 text-sm">
                                @if($tenantData['currentUnit'])
                                    <p><span class="font-medium">الوحدة:</span> {{ $tenantData['currentUnit']->name }}</p>
                                    <p><span class="font-medium">العقار:</span> {{ $tenantData['currentContract']->property->name ?? 'غير محدد' }}</p>
                                    <p><span class="font-medium">الإيجار الشهري:</span> {{ number_format($tenantData['financialStats']['monthly_rent'], 2) }} ريال</p>
                                    <p><span class="font-medium">التأمين:</span> {{ number_format($tenantData['financialStats']['security_deposit'], 2) }} ريال</p>
                                @else
                                    <p class="text-orange-600 font-medium">لا يوجد وحدة حالية</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Payment Statistics --}}
                    <div class="bg-gray-50 p-6 rounded-lg mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">إحصائيات المدفوعات</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ number_format($tenantData['totalPaidAmount'], 2) }}</div>
                                <div class="text-sm text-gray-600">إجمالي المدفوع</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600">{{ number_format($tenantData['outstandingBalance'], 2) }}</div>
                                <div class="text-sm text-gray-600">المستحقات المتبقية</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $tenantData['paymentComplianceRate'] }}%</div>
                                <div class="text-sm text-gray-600">معدل الالتزام</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ $tenantData['averageDelayDays'] }}</div>
                                <div class="text-sm text-gray-600">متوسط التأخير (يوم)</div>
                            </div>
                        </div>
                    </div>

                    {{-- Contract Information --}}
                    @if($tenantData['currentContract'])
                        <div class="bg-purple-50 p-6 rounded-lg mb-6">
                            <h3 class="text-lg font-semibold text-purple-900 mb-4">تفاصيل العقد الحالي</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">رقم العقد</p>
                                    <p class="font-semibold">{{ $tenantData['currentContract']->contract_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">تاريخ البداية</p>
                                    <p class="font-semibold">{{ $tenantData['currentContract']->start_date?->format('Y/m/d') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">تاريخ الانتهاء</p>
                                    <p class="font-semibold">{{ $tenantData['currentContract']->end_date?->format('Y/m/d') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">مدة العقد</p>
                                    <p class="font-semibold">{{ $tenantData['currentContract']->duration_months }} شهر</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">الأشهر المتبقية</p>
                                    <p class="font-semibold {{ $tenantData['financialStats']['remaining_contract_months'] <= 3 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $tenantData['financialStats']['remaining_contract_months'] }} شهر
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">حالة العقد</p>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($tenantData['currentContract']->contract_status === 'active') bg-green-100 text-green-800
                                        @elseif($tenantData['currentContract']->contract_status === 'expired') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ucfirst($tenantData['currentContract']->contract_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Payment Details --}}
                    @if($report_type === 'payment_history' && $tenantData['paymentHistory']->count() > 0)
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                            <div class="bg-gray-50 px-4 py-3 border-b">
                                <h3 class="text-lg font-semibold text-gray-900">سجل المدفوعات</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-right">رقم الدفعة</th>
                                            <th class="px-4 py-2 text-right">المبلغ</th>
                                            <th class="px-4 py-2 text-right">تاريخ الاستحقاق</th>
                                            <th class="px-4 py-2 text-right">تاريخ الدفع</th>
                                            <th class="px-4 py-2 text-right">الحالة</th>
                                            <th class="px-4 py-2 text-right">الوحدة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tenantData['paymentHistory'] as $payment)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $payment->payment_number }}</td>
                                                <td class="px-4 py-2 font-semibold">{{ number_format($payment->total_amount, 2) }} ريال</td>
                                                <td class="px-4 py-2">{{ $payment->due_date_start?->format('Y/m/d') }}</td>
                                                <td class="px-4 py-2">{{ $payment->paid_date?->format('Y/m/d') ?? 'لم يدفع' }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        @if($payment->collection_status === 'collected') bg-green-100 text-green-800
                                                        @elseif($payment->collection_status === 'overdue') bg-red-100 text-red-800
                                                        @else bg-yellow-100 text-yellow-800 @endif">
                                                        {{ $payment->collection_status }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">{{ $payment->unit?->name ?? 'غير محدد' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Contract History --}}
                    @if($report_type === 'contracts' && $tenantData['contractHistory']->count() > 0)
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                            <div class="bg-gray-50 px-4 py-3 border-b">
                                <h3 class="text-lg font-semibold text-gray-900">سجل العقود</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-right">رقم العقد</th>
                                            <th class="px-4 py-2 text-right">الوحدة</th>
                                            <th class="px-4 py-2 text-right">العقار</th>
                                            <th class="px-4 py-2 text-right">الإيجار الشهري</th>
                                            <th class="px-4 py-2 text-right">تاريخ البداية</th>
                                            <th class="px-4 py-2 text-right">تاريخ الانتهاء</th>
                                            <th class="px-4 py-2 text-right">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tenantData['contractHistory'] as $contract)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="px-4 py-2 font-semibold">{{ $contract->contract_number }}</td>
                                                <td class="px-4 py-2">{{ $contract->unit?->name ?? 'غير محدد' }}</td>
                                                <td class="px-4 py-2">{{ $contract->property?->name ?? 'غير محدد' }}</td>
                                                <td class="px-4 py-2">{{ number_format($contract->monthly_rent, 2) }} ريال</td>
                                                <td class="px-4 py-2">{{ $contract->start_date?->format('Y/m/d') }}</td>
                                                <td class="px-4 py-2">{{ $contract->end_date?->format('Y/m/d') }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        @if($contract->contract_status === 'active') bg-green-100 text-green-800
                                                        @elseif($contract->contract_status === 'expired') bg-red-100 text-red-800
                                                        @else bg-yellow-100 text-yellow-800 @endif">
                                                        {{ ucfirst($contract->contract_status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Summary Statistics --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-indigo-900 mb-2">إحصائيات الدفع</h4>
                            <div class="space-y-1 text-sm">
                                <p><span class="text-gray-600">إجمالي المدفوعات:</span> {{ $tenantData['paymentStats']['total_payments'] }}</p>
                                <p><span class="text-gray-600">المدفوعات المكتملة:</span> {{ $tenantData['paymentStats']['paid_payments'] }}</p>
                                <p><span class="text-gray-600">المدفوعات المعلقة:</span> {{ $tenantData['paymentStats']['pending_payments'] }}</p>
                                <p><span class="text-gray-600">المدفوعات المتأخرة:</span> {{ $tenantData['paymentStats']['overdue_payments'] }}</p>
                            </div>
                        </div>

                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-900 mb-2">معلومات مالية</h4>
                            <div class="space-y-1 text-sm">
                                <p><span class="text-gray-600">إجمالي الرسوم المتأخرة:</span> {{ number_format($tenantData['totalLateFees'], 2) }} ريال</p>
                                <p><span class="text-gray-600">قيمة العقد الإجمالية:</span> {{ number_format($tenantData['financialStats']['total_contract_value'], 2) }} ريال</p>
                                <p><span class="text-gray-600">العقود النشطة:</span> {{ $tenantData['activeContractsCount'] }}</p>
                            </div>
                        </div>

                        <div class="bg-teal-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-teal-900 mb-2">طلبات الصيانة</h4>
                            <div class="space-y-1 text-sm">
                                <p><span class="text-gray-600">عدد الطلبات:</span> {{ $tenantData['maintenanceRequests']->count() }}</p>
                                @if($tenantData['lastExpiredContract'])
                                    <p><span class="text-gray-600">آخر عقد منتهي:</span> {{ $tenantData['lastExpiredContract']->end_date?->format('Y/m/d') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <div class="text-yellow-800">
                    <h3 class="text-lg font-medium mb-2">يرجى اختيار مستأجر لعرض التقرير</h3>
                    <p class="text-sm">قم بتحديد المستأجر من القائمة أعلاه لعرض التقرير التفصيلي</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>