Заказ №{!! $order->number !!} оплачен.

Состав заказа:
@foreach ($order->items as $item)
- {!! $item->name !!} ({!! $item->sku !!}) x{!! $item->quantity !!} — {!! $item->price !!} {!! $order->currency !!}
@endforeach

Итого к оплате: {!! $order->total !!} {!! $order->currency !!}
