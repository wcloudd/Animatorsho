<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderCustomerRequest;
use App\Models\Order;
use App\Services\Admin\AdminOrderListService;
use App\Services\Admin\AdminOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderListService $orderList,
        private readonly AdminOrderService $orders,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();

        return Inertia::render('admin/orders/index', $this->orderList->listForAdmin(
            $status !== '' ? $status : null,
            $search !== '' ? $search : null,
        ));
    }

    public function markPaid(Order $order): RedirectResponse
    {
        try {
            $this->orders->markAsPaid($order);
        } catch (\InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'سفارش به‌عنوان پرداخت‌شده علامت‌گذاری شد.']);

        return redirect()->back();
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->orders->cancel($order);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'سفارش لغو شد.']);

        return redirect()->back();
    }

    public function updateCustomer(UpdateOrderCustomerRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $this->orders->updateCustomer($order, [
            'customer_name' => $validated['customer_name'],
            'customer_mobile' => $validated['customer_mobile'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'اطلاعات تماس سفارش به‌روزرسانی شد.']);

        return redirect()->back();
    }
}
