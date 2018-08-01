<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $remark = $request->input('remark');
        $items = $request->input('items');
        $coupon = null;

        // 如果用户提交了优惠码
        if ($code = $request->input('coupon_code')) {
            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon) {
                throw new CouponCodeUnavailableException('优惠券不存在');
            }
        }

        return $orderService->store($user, $address, $remark, $items, $coupon);
    }

    public function index(Request $request)
    {
        // 使用 with 方法预加载， 避免 N + 1问题
        $orders = Order::query()
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();
        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order, Request $request)
    {   $this->authorize('own', $order);
        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function received(Order $order, Request $request)
    {
        // 校验权限
        $this->authorize('own', $order);

        // 判断订单的发货状态是否已发货
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('发货状态不正确');
        }

        // 更新发货状态未已收到
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        // 返回原页面
        return redirect()->back();
    }

    public function review(Order $order)
    {
        // 校验权限
        $this->authorize('own', $order);

        // 判断是否已支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付');
        }

         // 使用 load 方法加载关联数据, 避免 N+1 性能问题
        return view('orders.review', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function saveReview(Order $order, SendReviewRequest $request)
    {
        // 校验权限
        $this->authorize('own', $order);

        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不能评价');
        }

        // 判断是否已评价
        if ($order->reviewed) {
            throw new InvalidRequestException('该订单已评价');
        }

        $reviews = $request->input('reviews');


        // 开启事务
        \DB::transaction(function () use ($reviews, $order) {
            // 遍历用户提交的数据
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);
                // 保存评分和评价
                $orderItem->update([
                    'rating'        => $review['rating'],
                    'review'        => $review['review'],
                    'reviewed_at'   => Carbon::now(),
                ]);
            }

            // 将订单标记为已评价
            $order->update(['reviewed' => true]);
        });

        return redirect()->back();
    }

    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        // 校验订单是否属于当前用户
        $this->authorize('own', $order);

        // 判断订单是否已付款
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付');
        }

        // 将用户输入的退款理由放到订单的 extra 字段中
        $extra                  = $order->extra ? : [];
        $extra['refund_reason'] = $request->input('reason');

        // 将订单退款状态改为已申请退款
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }
}
