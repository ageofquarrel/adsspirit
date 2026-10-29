Заказ №{!! $order->number !!} отменён.

Причина отмены: {!! $order->cancel_reason !!}
@if ($order->paid_at)

Деньги будут возвращены в течение 10 дней.
@endif
