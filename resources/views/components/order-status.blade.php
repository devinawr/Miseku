@props(['order'])
<span {{ $attributes->merge(['class' => 'ms-chip ' . $order->status_tone]) }}>{{ $order->status_label }}</span>
