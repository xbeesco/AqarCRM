<x-filament-panels::page>
    {{-- Settings Form --}}
    <form wire:submit.prevent="saveSettings">
        {{ $this->form }}

        <div class="mt-8 flex gap-4" styele="margin-top: 20px;">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
    
    {{-- Cleanup Form (Separate) --}}
    <div class="mt-12">
        {{ $this->cleanupForm }}

        <div class="mt-8 flex gap-4" styele="margin-top: 20px;">
            @foreach($this->getCleanupFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
    
    <x-filament-actions::modals />
</x-filament-panels::page>