<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Orders\MapListOrdersFiltersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Http\Resources\OrderListItemResource;
use App\Http\Resources\OrderResource;
use App\Services\Orders\GetOrderService;
use App\Services\Orders\ListOrdersService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class OrderController extends Controller
{
    public function index(
        ListOrdersRequest $request,
        ListOrdersService $service,
        MapListOrdersFiltersAction $mapFilters
    ): AnonymousResourceCollection {
        $orders = $service->handle($mapFilters->execute($request->validated()))
            ->withQueryString();

        return OrderListItemResource::collection($orders);
    }

    public function show(string $id, GetOrderService $service): OrderResource
    {
        $order = $service->handle($id);

        abort_if($order === null, ResponseAlias::HTTP_NOT_FOUND);

        return new OrderResource($order);
    }
}
