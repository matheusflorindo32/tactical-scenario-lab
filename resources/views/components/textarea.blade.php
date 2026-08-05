@props([
    'label'    => null,
    'name'     => null,
    'hint'     => null,
    'error'    => null,
    'required' => false,
    'rows'     => 4,
    'value'    => null,
])

@php
$id     = $attributes->get('id') ?? $name;
$hasErr = filled($error);
$ring   = $hasErr
    ? 'ring-emergency-500 focus:ring-emergency-500'
    : 'ring-stone-300 focus:ring-navy-500';
@endphp

<label @if($id) for="{{ $id }}" @endif class="block">
    @if ($label)
        <span class="mb-1.5 block text-sm font-medium text-ink-900">
            {{ $label }}
            @if ($required)<span class="text-emergency-500" aria-hidden="true">*</span>@endif
        </span>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' =>
                'block w-full rounded-md border-0 bg-white px-3.5 py-2.5 text-sm text-ink-900 '
                . 'placeholder:text-ink-300 ring-1 ring-inset ' . $ring
                . ' focus:outline-none focus:ring-2 resize-y',
        ]) }}
    >{{ $value }}</textarea>

    @if ($hint && ! $hasErr)
        <p class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @if ($hasErr)
        <p class="mt-1.5 text-xs font-medium text-emergency-600">{{ $error }}</p>
    @endif
</label>
