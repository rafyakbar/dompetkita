<x-filament-panels::page>
    <div class="flex flex-col gap-6" style="display: flex; flex-direction: column; gap: 1.5rem;">
        @foreach ($this->getRegisteredMyProfileComponents() as $component)
            @unless(is_null($component))
                @livewire($component)
            @endunless
        @endforeach
    </div>
</x-filament-panels::page>
