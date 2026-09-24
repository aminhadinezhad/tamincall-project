{{-- In the top bar on every page: one click from anywhere to a new call. On a phone only the + shows. --}}
<x-filament::button
    tag="a"
    :href="\App\Filament\Resources\Calls\CallResource::getUrl('create')"
    icon="heroicon-m-plus"
    size="sm"
    class="tc-quick-call"
    aria-label="ثبت تماس جدید"
>
    <span class="tc-quick-call__label">ثبت تماس</span>
</x-filament::button>
