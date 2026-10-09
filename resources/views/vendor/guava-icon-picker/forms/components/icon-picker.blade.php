@php
    use Filament\Support\Facades\FilamentAsset;
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
    <style>
        .dinero-icon-picker-root {
            position: relative;
            width: 100%;
        }
        .dinero-icon-trigger {
            position: relative;
            width: 100%;
            height: 4rem; /* 64px like !h-16 */
            min-height: 4rem;
            border-radius: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 2.5rem 0.5rem 1rem;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s ease-in-out;
            background-color: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #9ca3af;
        }
        :not(.dark) .dinero-icon-trigger {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }
        .dinero-icon-trigger:hover {
            border-color: rgba(255, 255, 255, 0.3);
        }
        :not(.dark) .dinero-icon-trigger:hover {
            border-color: #9ca3af;
        }
        .dinero-icon-trigger.is-active,
        .dinero-icon-trigger:focus-within {
            border: 2px solid #38bdf8 !important;
            box-shadow: 0 0 0 1px #38bdf8;
            outline: none;
        }
        .dinero-icon-trigger.is-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .dinero-icon-placeholder {
            font-size: 0.9375rem;
            color: #9ca3af;
            text-align: center;
            font-weight: 400;
        }
        :not(.dark) .dinero-icon-placeholder {
            color: #6b7280;
        }
        .dinero-icon-selected-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .dinero-icon-selected-preview {
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f3f4f6;
        }
        :not(.dark) .dinero-icon-selected-preview {
            color: #111827;
        }
        .dinero-icon-selected-preview svg {
            width: 100% !important;
            height: 100% !important;
        }
        .dinero-icon-selected-label {
            font-size: 0.75rem;
            color: #9ca3af;
            text-align: center;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 0.125rem;
        }
        :not(.dark) .dinero-icon-selected-label {
            color: #6b7280;
        }
        .dinero-icon-chevron {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.25rem;
            height: 1.25rem;
            color: #9ca3af;
            pointer-events: none;
        }
        .dinero-icon-clear-btn {
            position: absolute;
            right: 2.375rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.25rem;
            border-radius: 0.375rem;
            color: #9ca3af;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .dinero-icon-clear-btn:hover {
            color: #f3f4f6;
            background-color: rgba(255, 255, 255, 0.1);
        }
        :not(.dark) .dinero-icon-clear-btn:hover {
            color: #111827;
            background-color: #f3f4f6;
        }

        /* Dropdown styles */
        .dinero-icon-dropdown {
            position: absolute;
            top: calc(100% + 0.375rem);
            left: 0;
            width: 100%;
            z-index: 50;
            border-radius: 0.75rem;
            padding: 0.75rem;
            background-color: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.6);
        }
        :not(.dark) .dinero-icon-dropdown {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.12);
        }
        .dinero-icon-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background-color: #18181b;
            color: #f3f4f6;
            outline: none;
            margin-bottom: 0.625rem;
            transition: border-color 0.15s;
        }
        :not(.dark) .dinero-icon-search-input {
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #111827;
        }
        .dinero-icon-search-input::placeholder {
            color: #6b7280;
        }
        .dinero-icon-search-input:focus {
            border-color: #38bdf8;
        }

        /* Grid & Cards styles */
        .dinero-icon-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.5rem;
            max-height: 22rem;
            overflow-y: auto;
            padding: 2px;
        }
        @media (min-width: 640px) {
            .dinero-icon-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .dinero-icon-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1536px) {
            .dinero-icon-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
        .dinero-icon-card {
            position: relative;
            height: 4.8rem;
            min-height: 4.8rem;
            padding: 0.5rem 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background-color: rgba(255, 255, 255, 0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s ease-in-out;
            color: #f3f4f6;
        }
        :not(.dark) .dinero-icon-card {
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            color: #111827;
        }
        .dinero-icon-card:hover {
            border-color: #38bdf8;
            background-color: rgba(56, 189, 248, 0.08);
        }
        :not(.dark) .dinero-icon-card:hover {
            border-color: #0284c7;
            background-color: #f0f9ff;
        }
        .dinero-icon-card.is-selected {
            background-color: #0284c7 !important;
            border-color: #38bdf8 !important;
            color: #ffffff !important;
        }
        .dinero-icon-card-icon {
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: inherit;
        }
        .dinero-icon-card-icon svg {
            width: 100% !important;
            height: 100% !important;
        }
        .dinero-icon-card-label {
            font-size: 0.6875rem;
            line-height: 0.875rem;
            width: 100%;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 0.25rem;
            color: inherit;
        }
        .dinero-icon-card:not(.is-selected) .dinero-icon-card-label {
            color: #e5e7eb;
        }
        :not(.dark) .dinero-icon-card:not(.is-selected) .dinero-icon-card-label {
            color: #374151;
        }
    </style>

    <div
        class="dinero-icon-picker-root"
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
    >
        {{-- Trigger Input Box --}}
        <div
            x-bind="dropdownTrigger"
            role="button"
            tabindex="0"
            class="dinero-icon-trigger @if($isDisabled) is-disabled @endif"
            x-bind:class="{ 'is-active': dropdownOpen }"
        >
            {{-- When no icon is selected --}}
            <div x-show="! state" class="dinero-icon-placeholder">
                {{ __('No icon selected') }}
            </div>

            {{-- When an icon is selected --}}
            <div x-show="state" class="dinero-icon-selected-wrap">
                <div class="dinero-icon-selected-preview" x-data="{ loading: false }">
                    <span x-cloak x-show="loading" class="animate-spin text-primary-500">
                        {!! generate_loading_indicator_html() !!}
                    </span>
                    <span x-show="! loading"
                          class="w-full h-full flex items-center justify-center"
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
                <small class="dinero-icon-selected-label" x-text="state"></small>
            </div>

            {{-- Clear button --}}
            @if(! $isDisabled)
                <button
                    type="button"
                    x-show="state"
                    x-on:click.prevent.stop="updateState(null)"
                    class="dinero-icon-clear-btn"
                    title="Hapus icon"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                </button>
            @endif

            {{-- Chevron down icon --}}
            <svg class="dinero-icon-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </div>

        {{-- Dropdown Search & Grid --}}
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
