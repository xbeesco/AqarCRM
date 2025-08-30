<x-filament-panels::page>
    <form wire:submit.prevent="executeOperation">
        {{ $this->form }}

        <div class="mt-6 flex gap-4">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
    <x-filament-actions::modals />
</x-filament-panels::page>