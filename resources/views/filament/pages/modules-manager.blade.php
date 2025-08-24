<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6">
            @foreach($modules as $module)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $module['name'] }}
                                </h3>
                                <x-filament::badge :color="$this->getStatusColor($module['status'])">
                                    {{ $this->getStatusLabel($module['status']) }}
                                </x-filament::badge>
                            </div>
                            
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ $module['description'] }}
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">الجداول:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        @if(is_array($module['tables']) && count($module['tables']) > 0)
                                            {{ implode(', ', $module['tables']) }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">المميزات:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $module['features'] }}
                                    </p>
                                </div>
                            </div>
                            
                            @if($module['notes'])
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-3">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">ملاحظات:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                        {{ $module['notes'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                        
                        <div class="ml-6 text-center">
                            <div class="relative w-20 h-20">
                                <svg class="w-20 h-20 transform -rotate-90">
                                    <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" 
                                            fill="none" class="text-gray-200 dark:text-gray-700"></circle>
                                    <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" 
                                            fill="none" 
                                            class="text-{{ $this->getCompletionColor($module['completion']) }}-500"
                                            stroke-dasharray="{{ 226.19 * $module['completion'] / 100 }} 226.19"
                                            stroke-linecap="round"></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $module['completion'] }}%
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">الإنجاز</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" />
                <div>
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-400">معلومات النظام</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        تم إزالة حزمة Spatie Permissions بنجاح واستبدالها بنظام صلاحيات مخصص يعتمد على عمود type في جدول المستخدمين.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>