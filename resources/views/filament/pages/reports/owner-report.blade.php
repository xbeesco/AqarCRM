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
        @if($owner_id)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print:shadow-none print:border-none">
                <div class="mb-6 text-center border-b pb-4">
                    <h2 class="text-2xl font-bold text-gray-900">تقرير المالك التفصيلي</h2>
                    <p class="text-gray-600 mt-2">
                        الفترة من {{ $date_from ? \Carbon\Carbon::parse($date_from)->format('Y/m/d') : '' }} 
                        إلى {{ $date_to ? \Carbon\Carbon::parse($date_to)->format('Y/m/d') : '' }}
                    </p>
                </div>

                {{-- Owner Information --}}
                @php
                    $ownerData = $this->getOwnerData();
                @endphp

                @if(!empty($ownerData))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">معلومات المالك</h3>
                            <div class="space-y-2 text-sm">
                                <p><span class="font-medium">الاسم:</span> {{ $ownerData['owner']->name ?? 'غير محدد' }}</p>
                                <p><span class="font-medium">البريد الإلكتروني:</span> {{ $ownerData['owner']->email ?? 'غير محدد' }}</p>
                                <p><span class="font-medium">عدد العقارات:</span> {{ number_format($ownerData['propertiesCount']) }}</p>
                                <p><span class="font-medium">إجمالي الوحدات:</span> {{ number_format($ownerData['totalUnits']) }}</p>
                            </div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900 mb-2">ملخص الأداء</h3>
                            <div class="space-y-2 text-sm">
                                <p><span class="font-medium">الوحدات المؤجرة:</span> {{ number_format($ownerData['occupiedUnits']) }}</p>
                                <p><span class="font-medium">معدل الإشغال:</span> {{ $ownerData['occupancyRate'] }}%</p>
                                <p><span class="font-medium">إجمالي التحصيل:</span> {{ number_format($ownerData['totalCollection'], 2) }} ريال</p>
                                <p><span class="font-medium">صافي الدخل:</span> 
                                    <span class="font-bold {{ $ownerData['netIncome'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($ownerData['netIncome'], 2) }} ريال
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Financial Breakdown --}}
                    <div class="bg-gray-50 p-6 rounded-lg mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">التفصيل المالي</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ number_format($ownerData['totalCollection'], 2) }}</div>
                                <div class="text-sm text-gray-600">إجمالي التحصيل</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ number_format($ownerData['managementFee'], 2) }}</div>
                                <div class="text-sm text-gray-600">النسبة الإدارية ({{ $ownerData['managementPercentage'] }}%)</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600">{{ number_format($ownerData['maintenanceCosts'], 2) }}</div>
                                <div class="text-sm text-gray-600">تكاليف الصيانة</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $ownerData['netIncome'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($ownerData['netIncome'], 2) }}
                                </div>
                                <div class="text-sm text-gray-600">صافي الدخل</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <div class="text-yellow-800">
                    <h3 class="text-lg font-medium mb-2">يرجى اختيار مالك لعرض التقرير</h3>
                    <p class="text-sm">قم بتحديد المالك من القائمة أعلاه لعرض التقرير التفصيلي</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>