<div class="p-4 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">رقم الدفعة</p>
            <p class="font-semibold">{{ $payment->payment_number }}</p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">حالة الدفعة</p>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-300">
                مؤجلة
            </span>
        </div>
    </div>
    
    <hr class="border-gray-200 dark:border-gray-700">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">المستأجر</p>
            <p class="font-semibold">{{ $payment->tenant->name }}</p>
            <p class="text-sm text-gray-600">{{ $payment->tenant->phone }}</p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">العقار / الوحدة</p>
            <p class="font-semibold">{{ $payment->property->name }}</p>
            <p class="text-sm text-gray-600">وحدة رقم: {{ $payment->unit->name }}</p>
        </div>
    </div>
    
    <hr class="border-gray-200 dark:border-gray-700">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">المبلغ المستحق</p>
            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($payment->total_amount, 2) }} ريال</p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">مدة التأجيل</p>
            <p class="font-semibold">
                @if($payment->delay_duration)
                    {{ $payment->delay_duration }} يوم
                @else
                    {{ \Carbon\Carbon::parse($payment->due_date_end)->diffInDays(\Carbon\Carbon::now()) }} يوم
                @endif
            </p>
        </div>
    </div>
    
    <hr class="border-gray-200 dark:border-gray-700">
    
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">فترة التحصيل</p>
        <p class="font-semibold">
            من {{ \Carbon\Carbon::parse($payment->due_date_start)->format('Y-m-d') }}
            إلى {{ \Carbon\Carbon::parse($payment->due_date_end)->format('Y-m-d') }}
        </p>
    </div>
    
    @if($payment->delay_reason)
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">سبب التأجيل</p>
        <p class="font-semibold">{{ $payment->delay_reason }}</p>
    </div>
    @endif
    
    @if($payment->late_payment_notes)
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">ملاحظات</p>
        <p class="font-semibold">{{ $payment->late_payment_notes }}</p>
    </div>
    @endif
    
    <hr class="border-gray-200 dark:border-gray-700">
    
    <div class="text-sm text-gray-500">
        <p>تاريخ الإنشاء: {{ $payment->created_at->format('Y-m-d H:i:s') }}</p>
        <p>آخر تحديث: {{ $payment->updated_at->format('Y-m-d H:i:s') }}</p>
    </div>
</div>