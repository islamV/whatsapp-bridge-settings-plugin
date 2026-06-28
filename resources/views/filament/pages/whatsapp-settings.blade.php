<x-filament-panels::page>
    <livewire:whatsapp-connector />

    {{ $this->form }}

    @if (trim((string) $this->getFormActions()) !== '')
        <div class="flex justify-end mt-4">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
