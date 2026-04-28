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

            init() {
                this.query = this.state ?? '';

                this.$watch('state', (value) => {
                    if (value !== this.query) {
                        this.query = value ?? '';
                    }
                });
            },

            onInput() {
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
                    const url = 'https://api.dataforsyningen.dk/autocomplete?fuzzy&type=adresse&per_side={{ $getSuggestionCount() }}&q=' + encodeURIComponent(this.query);
                    const response = await fetch(url);
                    this.suggestions = await response.json();
                    this.showSuggestions = this.suggestions.length > 0;
                } catch {
                    this.suggestions = [];
                    this.showSuggestions = false;
                } finally {
                    this.loading = false;
                }
            },

            select(suggestion) {
                this.state = suggestion.tekst;
                this.query = suggestion.tekst;
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
            />
        </x-filament::input.wrapper>

        <div
            x-show="showSuggestions"
            x-cloak
            class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
        >
            <ul role="listbox">
                <template x-for="(suggestion, index) in suggestions" :key="index">
                    <li role="option">
                        <button
                            type="button"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:text-gray-200 dark:hover:bg-white/5 dark:focus:bg-white/5"
                            x-text="suggestion.tekst"
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
