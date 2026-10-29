<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Http\Resources\OrderListItemResource;
use App\Http\Resources\OrderResource;
use App\Services\Orders\GetOrderService;
use App\Services\Orders\ListOrdersService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, ListOrdersService $service): AnonymousResourceCollection
    {
        $orders = $service->handle($request->toDto())->withQueryString();

        return OrderListItemResource::collection($orders);
    }

    public function show(string $id, GetOrderService $service): OrderResource
    {
        $order = $service->handle($id);

        abort_if($order === null, 404);

        return new OrderResource($order);
    }
}
