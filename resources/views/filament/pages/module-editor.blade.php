<x-filament-panels::page>
    <div class="space-y-6">
        @if($module)
            <div class="flex items-center justify-end">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Module:</span>
                    <x-filament::badge color="primary">
                        {{ $module }}
                    </x-filament::badge>
                </div>
            </div>
        @endif

        <form wire:submit="save">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page>