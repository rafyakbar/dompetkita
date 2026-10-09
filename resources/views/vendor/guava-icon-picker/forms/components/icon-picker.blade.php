@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\View\ComponentAttributeBag;
    use function Filament\Support\generate_icon_html;
    use function Filament\Support\generate_loading_indicator_html;

    $key = $getKey();
    $statePath = $getStatePath();
    $state = $field->getState();
    $isDropdown = $isDropdown();
    $isDisabled = $isDisabled();
    $shouldCloseOnSelect = $shouldCloseOnSelect();
    $displayName = $getDisplayName();
    $placeholder = $getPlaceholder();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('icon-picker-component', 'guava/filament-icon-picker') }}"
        x-data="iconPickerComponent({
                key: @js($key),
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                displayName: @js($displayName),
                isDropdown: @js($isDropdown),
                shouldCloseOnSelect: @js($shouldCloseOnSelect),
                token: @js($field->getPickerToken()),
                cacheKey: @js($field->getClientCacheKey()),
                indexUrl: @js(route('guava-icon-picker.index')),
                svgUrl: @js(route('guava-icon-picker.svgs')),
            })"
        {{ $getExtraAttributeBag()
            ->class([
                'relative w-full'
            ])
        }}
    >
        <div
            x-bind="dropdownTrigger"
            role="button"
            tabindex="0"
            @class([
                'relative w-full min-h-[4.5rem] py-2 px-3 rounded-lg border flex flex-col items-center justify-center transition shadow-sm select-none',
                'bg-white dark:bg-white/5 border-gray-300 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-500 cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500/20' => ! $isDisabled,
                'bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10 opacity-70 cursor-not-allowed' => $isDisabled,
            ])
        >
            {{-- When no icon is selected (Dinero placeholder style) --}}
            <div x-show="! state" class="relative w-full !h-16 flex flex-col items-center justify-center py-2">
                <div class="text-center grow-0 shrink-0 text-sm text-gray-500 dark:text-gray-400 filament-icon-picker-icon-id">
                    {{ __('No icon selected') }}
                </div>
            </div>

            {{-- When an icon is selected (Dinero item style) --}}
            <div x-show="state" class="relative w-full !h-16 flex flex-col items-center justify-center py-2">
                <div class="relative w-12 h-12 flex items-center justify-center grow-1 shrink-0 gap-1 text-gray-700 dark:text-gray-200 [&>svg]:w-full [&>svg]:h-full"
                     x-data="{ loading: false }"
                >
                    <span x-cloak x-show="loading" class="animate-spin text-primary-500">
                        {!! generate_loading_indicator_html() !!}
                    </span>
                    <span x-show="! loading"
                          class="w-full h-full flex items-center justify-center [&>svg]:w-full [&>svg]:h-full"
                          x-init="$watch('state', (newValue) => {
                              if (newValue) {
                                  loading = true;
                                  setElementIcon($el, newValue, () => loading = false);
                              } else {
                                  $el.innerHTML = '';
                              }
                          })"
                    >
                        @if($state)
                            {!! generate_icon_html($state)?->toHtml() !!}
                        @endif
                    </span>
                </div>
                <small class="w-full text-center grow-0 shrink-0 h-4 truncate text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="displayName || state"></small>

                @if(! $isDisabled)
                    <button
                        type="button"
                        x-on:click.prevent.stop="updateState(null)"
                        class="absolute top-1 right-1 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-white/10 transition z-10"
                        title="Hapus icon"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>

        @if($isDropdown && ! $isDisabled)
            <x-guava-icon-picker::search
                :field="$field"
                :sets="$getAllowedSets()"
                :search-prompt="$getSearchPrompt()"
                :is-dropdown="$isDropdown"
            />
        @endif

        @if(! $isDropdown && ! $isDisabled)
            <x-guava-icon-picker::search
                :field="$field"
                :sets="$getAllowedSets()"
                :search-prompt="$getSearchPrompt()"
                :is-dropdown="$isDropdown"
            />
        @endif
    </div>
</x-dynamic-component>
