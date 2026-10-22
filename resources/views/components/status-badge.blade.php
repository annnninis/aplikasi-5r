@props(['status'])

{{-- Badge untuk enum status yang punya getLabel()/getColor() (warna Filament → warna Flux). --}}
@php
    $color = match ($status->getColor()) {
        'success' => 'green',
        'warning' => 'amber',
        'danger' => 'red',
        'info' => 'blue',
        default => 'zinc',
    };
@endphp

<flux:badge size="sm" :color="$color" {{ $attributes->class('shrink-0') }}>{{ $status->getLabel() }}</flux:badge>
