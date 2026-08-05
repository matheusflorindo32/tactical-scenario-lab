@props(['items' => []])

{{--
    items = [
        [
            'title'    => 'Cenário criado',
            'subtitle' => 'Rascunho pronto para execução',
            'time'     => '10:24',
            'status'   => 'done', // done | current | pending
        ],
        ...
    ]
--}}

<ol class="relative border-l-2 border-stone-200 pl-6" {{ $attributes }}>
    @foreach ($items as $item)
        @php
            $status = $item['status'] ?? 'pending';
            $ring = [
                'done'    => 'bg-clinical-500 ring-clinical-100',
                'current' => 'bg-navy-900 ring-navy-100',
                'pending' => 'bg-stone-100 ring-stone-100 text-ink-300',
            ][$status];
            $titleCls = [
                'done'    => 'text-ink-900',
                'current' => 'text-navy-900 font-semibold',
                'pending' => 'text-ink-500',
            ][$status];
        @endphp
        <li class="mb-6 last:mb-0">
            <span class="absolute -left-[9px] flex h-4 w-4 items-center justify-center rounded-full ring-4 {{ $ring }}">
                @if ($status === 'done')
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4" class="h-2.5 w-2.5"><path d="M5 13l4 4L19 7"/></svg>
                @endif
            </span>
            <div class="flex items-baseline justify-between gap-3">
                <div>
                    <p class="text-sm {{ $titleCls }}">{{ $item['title'] }}</p>
                    @if (! empty($item['subtitle']))
                        <p class="mt-0.5 text-xs text-ink-500">{{ $item['subtitle'] }}</p>
                    @endif
                </div>
                @if (! empty($item['time']))
                    <span class="shrink-0 font-mono text-xs text-ink-500">{{ $item['time'] }}</span>
                @endif
            </div>
        </li>
    @endforeach
</ol>
