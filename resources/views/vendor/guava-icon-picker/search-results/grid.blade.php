@php
    use function Filament\Support\generate_loading_indicator_html;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-2 max-h-80 overflow-y-auto p-1">
    <template x-for="icon in resultsVisible" :key="icon.id">
        <div role="button"
             tabindex="0"
             class="border rounded-lg shadow-sm hover:cursor-pointer p-2 flex flex-col items-center justify-center text-center transition select-none bg-white dark:bg-white/5 border-gray-200 dark:border-white/10 hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-white/10"
             x-show="! isLoading"
             x-on:click.prevent="updateState(icon)"
             x-bind:class="{
                '!bg-primary-500 !border-primary-500 !text-white': state == icon.id,
                'text-gray-700 dark:text-gray-200': state != icon.id
            }"
        >
            <div class="relative w-full !h-16 flex flex-col items-center justify-center py-1">
                <div class="relative w-10 h-10 flex items-center justify-center grow-1 shrink-0 gap-1 [&>svg]:w-full [&>svg]:h-full"
                     x-bind:class="{
                         'text-white': state == icon.id,
                         'text-gray-700 dark:text-gray-200': state != icon.id
                     }"
                     x-intersect="setElementIcon($el, icon.id)"
                >
                    {{ generate_loading_indicator_html() }}
                </div>
                <small class="w-full text-center grow-0 shrink-0 h-4 truncate text-xs mt-1"
                       x-bind:class="{
                            '!text-white': state == icon.id,
                            'text-gray-500 dark:text-gray-400': state != icon.id
                       }"
                       x-text="icon.label"></small>
            </div>
        </div>
    </template>
    <div x-intersect="addSearchResultsChunk" class="col-span-full"></div>
</div>
