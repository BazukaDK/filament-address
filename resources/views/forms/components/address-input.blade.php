<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            query: '',
            suggestions: [],
            showSuggestions: false,
            loading: false,
            debounceTimer: null,
            isSelected: false,
            isManual: false,
            apiError: false,

            init() {
                // state is a string when hydrated from an existing address, an object after a new selection
                this.isSelected = !! this.state;
                this.query = this.displayText(this.state);

                this.$watch('state', (value) => {
                    if (this.isManual) { return; }
                    this.query = this.displayText(value);
                });
            },

            displayText(value) {
                if (! value) { return ''; }
                const text = typeof value === 'string' ? value : (value.titel ?? '');
                return this.cleanText(text);
            },

            cleanText(text) {
                return text.split(',').map(s => s.trim()).filter(s => s).join(', ');
            },

            onInput() {
                if (this.isManual) {
                    this.state = { manual: true, tekst: this.query };
                    this.isSelected = !! this.query;
                    return;
                }

                this.isSelected = false;
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.search(), 300);
            },

            async search() {
                if (this.query.length < 2) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }

                this.loading = true;

                try {
                    const url = 'https://adressevaelger.dk/adresser/soeg?tekst=' + encodeURIComponent(this.query) + '&maksimum={{ $getSuggestionCount() }}&token={{ config("filament-address.api_token") }}';
                    const response = await fetch(url);
                    const data = await response.json();
                    this.suggestions = data.status === 'ok' ? (data.fund ?? []) : [];
                    this.showSuggestions = this.suggestions.length > 0;
                    this.apiError = false;
                } catch {
                    this.apiError = true;
                    this.suggestions = [];
                    this.showSuggestions = false;
                } finally {
                    this.loading = false;
                }
            },

            enterManualMode() {
                this.isManual = true;
                this.isSelected = !! this.query;
                this.state = { manual: true, tekst: this.query };
                this.suggestions = [];
                this.showSuggestions = false;
                this.$nextTick(() => this.$el.querySelector('input').focus());
            },

            select(suggestion) {
                if (suggestion.type !== 'adresse') {
                    this.query = suggestion.titel.trim();
                    this.isSelected = false;
                    this.search();
                    return;
                }

                this.state = suggestion;
                this.query = this.cleanText(suggestion.titel);
                this.isSelected = true;
                this.suggestions = [];
                this.showSuggestions = false;
            },
        }"
        x-on:click.outside="showSuggestions = false"
        class="relative"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled()"
            :valid="! $errors->has($getStatePath())"
            suffix-icon-color="gray"
            :suffix-icon="'heroicon-m-magnifying-glass'"
        >
            <x-filament::input
                type="text"
                :disabled="$isDisabled()"
                :placeholder="$getPlaceholder()"
                x-model="query"
                x-on:input="onInput()"
                x-on:focus="query.length >= 2 && suggestions.length > 0 && (showSuggestions = true)"
                x-on:keydown.escape="showSuggestions = false"
                x-on:keydown.arrow-down.prevent="$focus.within($el.closest('.relative').querySelector('[role=listbox]')).first()"
                role="combobox"
                x-bind:aria-expanded="showSuggestions"
                aria-autocomplete="list"
                autocomplete="off"
            />
        </x-filament::input.wrapper>

        <p
            x-show="query && ! isSelected && ! isManual && ! apiError"
            x-cloak
            class="mt-1 text-xs text-warning-600 dark:text-warning-400"
        >
            {{ __('filament-address::address.select_from_suggestions') }}
        </p>

        <div
            x-show="apiError && ! isManual"
            x-cloak
            class="mt-1 flex items-center justify-between gap-2 text-xs text-danger-600 dark:text-danger-400"
        >
            <span>{{ __('filament-address::address.lookup_unavailable') }}</span>
            <button
                type="button"
                x-on:click="enterManualMode()"
                class="font-medium underline hover:no-underline focus:outline-none"
            >
                {{ __('filament-address::address.enter_manually') }}
            </button>
        </div>

        <p
            x-show="isManual"
            x-cloak
            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
        >
            {{ __('filament-address::address.manually_entered') }}
        </p>

        <div
            x-show="showSuggestions"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
            class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-900"
        >
            <ul role="listbox" class="divide-y divide-gray-100 dark:divide-white/5">
                <template x-for="(suggestion, index) in suggestions" :key="index">
                    <li role="option">
                        <button
                            type="button"
                            class="w-full px-3 py-2.5 text-left text-sm text-gray-700 hover:bg-primary-50 focus:bg-primary-50 focus:text-primary-600 focus:outline-none dark:text-gray-200 dark:hover:bg-primary-600/10 dark:focus:bg-primary-600/10 dark:focus:text-primary-400"
                            x-text="cleanText(suggestion.titel)"
                            x-on:click="select(suggestion)"
                            x-on:keydown.enter.prevent="select(suggestion)"
                            x-on:keydown.arrow-down.prevent="$focus.next()"
                            x-on:keydown.arrow-up.prevent="$focus.previous()"
                        ></button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</x-dynamic-component>
