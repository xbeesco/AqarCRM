@php
    use App\Helpers\DateHelper;
    use Carbon\Carbon;
    
    $isTestMode = DateHelper::isTestMode();
    $testDate = DateHelper::getTestDate();
@endphp

@if($isTestMode && $testDate)
    <div class="flex items-center gap-2 px-3 py-1.5 bg-warning-500/10 dark:bg-warning-500/20 text-warning-700 dark:text-warning-400 rounded-lg border border-warning-500/20 dark:border-warning-500/30">
        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1 text-sm font-medium">
            <span>وضع المحاكاة:</span>
            <span class="font-bold" dir="ltr">{{ Carbon::parse($testDate)->format('Y-m-d') }}</span>
        </div>
    </div>
@endif