@extends('admin.layout')

@section('title', 'Заказы')

@section('content')
    <form class="filters" method="GET" action="{{ route('admin.orders.index') }}">
        <div>
            <label for="status">Статус</label>
            <select name="status" id="status">
                <option value="">Все</option>
                @foreach (\App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $status->value }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="email">Email покупателя</label>
            <input type="text" name="email" id="email" value="{{ request('email') }}" placeholder="name@example.com">
        </div>
        <button type="submit">Фильтровать</button>
    </form>

    @if ($orders->isEmpty())
        <p class="empty">Заказов не найдено.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>№ заказа</th>
                <th>Статус</th>
                <th>Покупатель</th>
                <th>Сумма</th>
                <th>Оформлен</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->number }}</td>
                    <td><span class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span></td>
                    <td>{{ $order->customer_name }}<br><small>{{ $order->customer_email }}</small></td>
                    <td>{{ $order->total }} {{ $order->currency }}</td>
                    <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    <td><a class="back" href="{{ route('admin.orders.show', $order->id) }}">Открыть →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="pagination">
            @if ($orders->onFirstPage())
                <span>&laquo; Назад</span>
            @else
                <a href="{{ $orders->previousPageUrl() }}">&laquo; Назад</a>
            @endif
            <span>Стр. {{ $orders->currentPage() }} из {{ $orders->lastPage() }} ({{ $orders->total() }} всего)</span>
            @if ($orders->hasMorePages())
                <a href="{{ $orders->nextPageUrl() }}">Вперёд &raquo;</a>
            @else
                <span>Вперёд &raquo;</span>
            @endif
        </div>
    @endif
@endsection
