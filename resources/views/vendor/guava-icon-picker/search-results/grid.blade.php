@php
    use function Filament\Support\generate_loading_indicator_html;
@endphp

<div class="dinero-icon-grid">
    <template x-for="icon in resultsVisible" :key="icon.id">
        <div
            role="button"
            tabindex="0"
            class="dinero-icon-card"
            x-show="! isLoading"
            x-on:click.prevent="updateState(icon)"
            x-bind:class="{ 'is-selected': state == icon.id }"
        >
            <div
                class="dinero-icon-card-icon"
                x-intersect="setElementIcon($el, icon.id)"
            >
                {{ generate_loading_indicator_html() }}
            </div>
            <small
                class="dinero-icon-card-label"
                x-text="icon.id"
            ></small>
        </div>
    </template>
    <div x-intersect="addSearchResultsChunk" style="grid-column: 1 / -1;"></div>
</div>
