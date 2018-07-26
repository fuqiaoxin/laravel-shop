<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);

        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 调用 alipay 的网页支付
        return app('alipay')->web([
            'out_trade_no'  => $order->no,  // 订单编号，需保证唯一
            'total_amount'  => $order->total_amount, // 订单金额 支持小数点后2位
            'subject'       => '支付 Laravel Shop 的订单：'. $order->no, // 订单标题
        ]);
    }

    // 前端回调页面
    public function alipayReturn()
    {
        // 校验提交的参数是否合法
        try {
            $data = app('alipay')->verify();
            //dd($data);
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);

    }

    // 服务器端回调
    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        \Log::debug('Alipay notify', $data->all());

        // $data->out_trade_no 平台订单流水号 并在数据库中查询
        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order) {
            return 'fail';
        }

        // 如果这笔订单状态已经是已支付
        if ($order->paid_at) {
            // 返回成功数据给Alipay
            return app('alipay')->success();
        }

        if ($data->trade_status && $data->trade_status == 'TRADE_SUCCESS') {
            $order->update([
                'paid_at'           => Carbon::now(),   // 支付时间
                'payment_method'    => 'alipay',        // 支付方式
                'payment_no'        => $data->trade_no, // 支付宝订单号
            ]);
            return app('alipay')->success();
        }

        return 'fail';

    }
}
