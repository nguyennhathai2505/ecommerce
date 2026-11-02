@props([
    'order',
    'editable' => false,
    'size' => 'sm',
])

@php
    $statuses = \App\Models\Order::STATUSES;
    $allowedStatuses = $order->allowedNextStatuses();

    $currentStatus = $statuses[$order->status] ?? [
        'label' => $order->status_label,
        'classes' => 'border-gray-200 bg-gray-50 text-gray-700',
    ];

@endphp

@if($editable)
    <div
        x-data="{ open: false }"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        @order-status-synced="open = false"
        class="relative inline-block"
    >
        <form x-ref="statusForm" data-order-form data-order-id="{{ $order->id }}" action="{{ route('admin.orders.update-status', $order->id) }}" method="POST">
            @csrf
            <input type="hidden" name="current_status" value="{{ $order->status }}">
            <input x-ref="statusInput" type="hidden" name="status" value="{{ $order->status }}">
            <button
                type="button"
                data-order-editor
                data-order-status="{{ $order->id }}"
                data-order-state="{{ $order->status }}"
                data-order-code="{{ $order->order_code }}"
                data-status-classes="{{ $currentStatus['classes'] }}"
                @disabled($allowedStatuses === [])
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="listbox"
                aria-controls="order-status-options-{{ $order->id }}"
                class="inline-flex min-w-40 items-center justify-between gap-3 rounded-lg border px-3 py-1.5 text-sm font-medium outline-none transition focus:ring-2 focus:ring-gray-900 focus:ring-offset-1 {{ $currentStatus['classes'] }}"
            >
                <span data-order-label>{{ $currentStatus['label'] }}</span>
                <svg data-order-chevron @class(['h-4 w-4 transition', 'hidden' => $allowedStatuses === []]) :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.origin.top
                id="order-status-options-{{ $order->id }}"
                role="listbox"
                class="absolute right-0 z-20 mt-2 w-48 space-y-1 rounded-xl border border-gray-200 bg-white p-2 shadow-lg"
            >
                @foreach($statuses as $value => $status)
                    <button
                        type="button"
                        role="option"
                        data-order-option="{{ $value }}"
                        aria-selected="false"
                        @click="$refs.statusInput.value = '{{ $value }}'; open = false; $refs.statusForm.requestSubmit()"
                        @class(['flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm font-medium transition hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gray-900 ' . $status['classes'], 'hidden' => !in_array($value, $allowedStatuses, true)])
                    >
                        <span>{{ $status['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </form>
    </div>
@else
    <x-order-status :order="$order" :size="$size" {{ $attributes }} />
@endif
