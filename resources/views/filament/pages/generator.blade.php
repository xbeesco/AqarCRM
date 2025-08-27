<x-filament-panels::page>
    <form wire:submit.prevent="generate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="generate" />
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Generating...</span>
            </x-filament::button>
        </div>
    </form>

    @if(config('app.debug'))
        <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Debug Info</h3>
            <pre class="text-xs text-gray-600 dark:text-gray-400">{{ json_encode($this->data, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</x-filament-panels::page>