@props([
    'label'    => null,
    'name'     => null,
    'hint'     => null,
    'error'    => null,
    'required' => false,
    'options'  => [], // ['value' => 'Label']
    'selected' => null,
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

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' =>
                'block w-full appearance-none rounded-md border-0 bg-white bg-[url("data:image/svg+xml;utf8,'
                . '%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20viewBox%3D%220%200%2012%2012%22%3E%3Cpath%20fill%3D%22%2355606f%22%20d%3D%22M6%208.5%201.5%204h9z%22%2F%3E%3C%2Fsvg%3E")] '
                . 'bg-[length:12px_12px] bg-[right_1rem_center] bg-no-repeat px-3.5 pr-10 py-2.5 text-sm text-ink-900 '
                . 'ring-1 ring-inset ' . $ring
                . ' focus:outline-none focus:ring-2',
        ]) }}
    >
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @if((string)$selected === (string)$value) selected @endif>{{ $label }}</option>
        @endforeach
    </select>

    @if ($hint && ! $hasErr)
        <p class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @if ($hasErr)
        <p class="mt-1.5 text-xs font-medium text-emergency-600">{{ $error }}</p>
    @endif
</label>
