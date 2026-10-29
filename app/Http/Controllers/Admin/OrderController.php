<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\MapListOrdersFiltersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Services\Orders\GetOrderService;
use App\Services\Orders\ListOrdersService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class OrderController extends Controller
{
    public function index(
        ListOrdersRequest $request,
        ListOrdersService $service,
        MapListOrdersFiltersAction $mapFilters
    ): View {
        $orders = $service->handle($mapFilters->execute($request->validated()))
            ->withQueryString();

        return view('admin.orders.index', ['orders' => $orders]);
    }

    public function show(string $id, GetOrderService $service): View
    {
        $order = $service->handle($id);

        abort_if($order === null, ResponseAlias::HTTP_NOT_FOUND);

        return view('admin.orders.show', ['order' => $order]);
    }
}
