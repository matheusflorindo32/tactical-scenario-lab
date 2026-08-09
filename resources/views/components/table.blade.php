@props([
    'label',
    'empty' => false,
    'emptyTitle' => 'Nenhum registro encontrado',
    'emptyDescription' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-stone-200 bg-white shadow-xs']) }}>
    @if ($empty)
        <x-empty-state
            :title="$emptyTitle"
            :description="$emptyDescription"
            icon="search"
            class="rounded-none border-0 shadow-none"
        />
    @else
        <div class="overflow-x-auto">
            <table aria-label="{{ $label }}" class="min-w-full divide-y divide-stone-200 text-left text-sm text-ink-700">
                {{ $slot }}
            </table>
        </div>
    @endif
</div>
