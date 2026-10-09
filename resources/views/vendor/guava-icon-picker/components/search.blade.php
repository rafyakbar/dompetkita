@props([
    'field',
    'sets',
    'searchPrompt',
    'isDropdown' => false,
])

<div
    class="dinero-icon-dropdown"
    x-bind="dropdownMenu"
    x-cloak
>
    <div style="display: flex; flex-direction: column; width: 100%;">
        {{-- Search input identical to Dinero's search box --}}
        <input
            type="text"
            class="dinero-icon-search-input"
            x-bind="searchInput"
            placeholder="Start typing to search..."
            autofocus
        />

        <x-guava-icon-picker::searching :field="$field" />
        <x-guava-icon-picker::no-results-found :field="$field"/>

        {{ $field->getSearchResultsViewComponent() }}
    </div>
</div>
