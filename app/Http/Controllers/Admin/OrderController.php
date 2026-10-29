<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Services\Orders\GetOrderService;
use App\Services\Orders\ListOrdersService;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, ListOrdersService $service): View
    {
        $orders = $service->handle($request->toDto())->withQueryString();

        return view('admin.orders.index', ['orders' => $orders]);
    }

    public function show(string $id, GetOrderService $service): View
    {
        $order = $service->handle($id);

        abort_if($order === null, 404);

        return view('admin.orders.show', ['order' => $order]);
    }
}
