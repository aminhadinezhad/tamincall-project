@php
    $levels = $getLevels();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{--
        Five stars; the level's name shows beside them, for the star under the pointer or the chosen one.
        In right-to-left the first star is on the right, so the left arrow key raises the rating.
    --}}
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            hover: 0,
            levels: @js($levels),
            get shown() { return this.hover || Number(this.state) || 0 },
            mood(value) { return value >= 4 ? 'is-good' : (value === 3 ? 'is-mid' : (value ? 'is-bad' : '')) },
            set(value) { this.state = Math.min(5, Math.max(1, value)) },
        }"
        class="tc-rating"
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        x-on:mouseleave="hover = 0"
        x-on:keydown.arrow-left.prevent="set((Number(state) || 0) + 1)"
        x-on:keydown.arrow-right.prevent="set((Number(state) || 2) - 1)"
    >
        <div class="tc-rating__stars">
            @foreach ($levels as $value => $name)
                <button
                    type="button"
                    role="radio"
                    class="tc-rating__star"
                    title="{{ $name }}"
                    aria-label="{{ $name }}"
                    x-bind:aria-checked="(Number(state) === {{ $value }}).toString()"
                    x-bind:class="{ 'is-on': shown >= {{ $value }} }"
                    x-on:mouseenter="hover = {{ $value }}"
                    x-on:click="state = {{ $value }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2.6l2.87 5.82 6.43.93-4.65 4.53 1.1 6.4L12 17.26l-5.75 3.02 1.1-6.4L2.7 9.35l6.43-.93z" />
                    </svg>
                </button>
            @endforeach
        </div>

        <span
            class="tc-rating__label"
            x-bind:class="mood(shown)"
            x-text="levels[shown] ?? 'انتخاب کنید'"
        ></span>
    </div>
</x-dynamic-component>
