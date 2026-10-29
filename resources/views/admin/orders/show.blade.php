@extends('admin.layout')

@section('title', 'Заказ '.$order->number)

@section('content')
    <p><a class="back" href="{{ route('admin.orders.index') }}">&larr; К списку заказов</a></p>

    <div class="card">
        <h2>
            Заказ №{{ $order->number }}
            <span class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span>
        </h2>
        <dl>
            <dt>Покупатель</dt>
            <dd>
                {{ $order->customer_name }} ({{ $order->customer_email }}@if($order->customer_phone), {{ $order->customer_phone }}@endif)
            </dd>
            <dt>Сумма</dt>
            <dd>{{ $order->total }} {{ $order->currency }} (доставка {{ $order->delivery_price }}, скидка {{ $order->discount }})</dd>
            @if ($order->shipment_carrier)
                <dt>Доставка</dt>
                <dd>
                    {{ $order->shipment_carrier }}, трек {{ $order->shipment_tracking_number }}
                    (<a href="{{ $order->shipment_tracking_url }}">ссылка на отслеживание</a>)
                </dd>
            @endif
            @if ($order->paid_at)
                <dt>Оплачен</dt>
                <dd>{{ $order->paid_at->format('d.m.Y H:i') }}</dd>
            @endif
            @if ($order->cancelled_at)
                <dt>Отменён</dt>
                <dd>{{ $order->cancelled_at->format('d.m.Y H:i') }} — {{ $order->cancel_reason }}</dd>
            @endif
            <dt>Оформлен</dt>
            <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
            <dt>Обновлён</dt>
            <dd>{{ $order->updated_at->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>

    <div class="card">
        <h2>Состав заказа</h2>
        <table>
            <thead>
            <tr>
                <th>SKU</th>
                <th>Название</th>
                <th>Кол-во</th>
                <th>Цена</th>
                <th>Сумма</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price }}</td>
                    <td>{{ bcmul($item->price, (string) $item->quantity, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>История статусов</h2>
        <ul class="timeline">
            @foreach ($order->statusHistory as $entry)
                <li>
                    <span class="badge badge-{{ $entry->status->value }}">{{ $entry->status->value }}</span>
                    — {{ $entry->changed_at->format('d.m.Y H:i') }}
                </li>
            @endforeach
        </ul>
    </div>
@endsection
