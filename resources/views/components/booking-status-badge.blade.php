@props(['booking'])

{{-- Booking disetujui yang jam selesainya sudah lewat ditampilkan sebagai "Selesai Dipakai" (dihitung, tidak disimpan). --}}
@if ($booking->isFinished())
    <flux:badge size="sm" color="zinc" {{ $attributes->class('shrink-0') }}>Selesai Dipakai</flux:badge>
@else
    <x-status-badge :status="$booking->approval_status" {{ $attributes }} />
@endif
