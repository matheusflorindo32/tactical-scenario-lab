@props(['status'])

@php
// draft | running | completed
$map = [
    'draft' => [
        'label'   => 'Rascunho',
        'variant' => 'neutral',
    ],
    'running' => [
        'label'   => 'Em execução',
        'variant' => 'alert',
    ],
    'completed' => [
        'label'   => 'Concluído',
        'variant' => 'clinical',
    ],
];

$config = $map[$status] ?? ['label' => ucfirst((string) $status), 'variant' => 'neutral'];
@endphp

<x-badge :variant="$config['variant']" dot>{{ $config['label'] }}</x-badge>
