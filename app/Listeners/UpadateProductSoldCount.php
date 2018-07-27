<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\OrderItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

// implements ShoudQueue 代表此监听器是异步执行的
class UpadateProductSoldCount implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        // 从事件对象中取出对应的订单
        $order = $event->getOrder();

        // 循环遍历订单的商品
        foreach ($order->items as $item) {
            $product = $item->product;

            // 计算对应商品的销量
            $soldCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');    // 关联的订单状态是已支付
                })
                ->sum('amount');
            // 更新商品销量
            $product->update([
                'sold_count'    => $soldCount,
            ]);
        }
    }
}
