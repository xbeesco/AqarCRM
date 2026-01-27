<x-filament-panels::page>
    <form wire:submit.prevent="reschedule">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            @foreach($this->getActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
