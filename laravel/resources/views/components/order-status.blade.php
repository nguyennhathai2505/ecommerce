@props(['order', 'size' => 'sm'])

@php
    $status = $order->statusData();
    $sizeClasses = $size === 'md' ? 'px-3 py-1.5 text-sm' : 'px-2.5 py-1 text-xs';
@endphp

<span data-order-status="{{ $order->id }}" data-order-state="{{ $order->status }}" data-order-code="{{ $order->order_code }}" data-status-classes="{{ $status['classes'] }}" {{ $attributes->class("inline-flex items-center rounded-full border font-medium {$status['classes']} {$sizeClasses}") }}>
    <span data-order-label>{{ $status['label'] }}</span>
</span>
