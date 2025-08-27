@php
    $collectionTotal = $collectionTotal ?? 0;
    $supplyTotal = $supplyTotal ?? 0;
@endphp

<div class="space-y-6">
    {{-- Widget حالة التحصيل والتوريد --}}
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">حالة التحصيل</div>
            <div class="text-3xl font-bold text-green-600 mt-2">
                {{ number_format($collectionTotal) }} ر.س
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">حالة التوريد</div>
            <div class="text-3xl font-bold text-blue-600 mt-2">
                {{ number_format($supplyTotal) }} ر.س
            </div>
        </div>
    </div>
    
    {{-- زر الطباعة العام --}}
    <div class="flex justify-end">
        <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            <svg class="w-5 h-5 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            طباعة
        </button>
    </div>
</div>