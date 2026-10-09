@php
    use function Filament\Support\generate_loading_indicator_html;
@endphp

@props([
    'field',
    'sets',
    'searchPrompt',
    'isDropdown' => false,
])

<x-filament::section
    compact
    @class([
        'absolute w-full top-full left-0 mt-2 z-50 shadow-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl' => $isDropdown,
    ])
    x-bind="dropdownMenu"
    x-cloak
>
    <div class="flex flex-col gap-3 @container">
        {{-- In Dinero, there is no set select dropdown --}}
        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
            <x-filament::input
                x-bind="searchInput"
                :placeholder="$searchPrompt ?? 'Search icons...'"
                autofocus
            />
        </x-filament::input.wrapper>

        <x-guava-icon-picker::searching :field="$field" />
        <x-guava-icon-picker::no-results-found :field="$field"/>

        {{ $field->getSearchResultsViewComponent() }}
    </div>
</x-filament::section>
