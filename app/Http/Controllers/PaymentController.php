<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use App\Events\OrderPaid;

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
            $this->afterPaid($order);
            return app('alipay')->success();
        }

        return 'fail';

    }

    public function payByWechat(Order $order, Request $request)
    {
        // 校验权限
        $this->authorize('own', $order);

        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // scan 方法为拉起微信扫码支付 scan 返回 数据对象
        /*
         * {
            'return_code':
            'return_msg':
            'appid':
            'mch_id':
            'nonce_str':
            'sign':
            'result_code':
            'prepay_id':
            'trade_type':
            'code_url':
          }

            // 需要将 code_url 转换成二维码展示
         */
        $wechatOrder =  app('wechat_pay')->scan([
            'out_trade_no'  => $order->no,
            'total_fee'     => $order->total_amount,
            'body'          => '支付 Laravel Shop 的订单:' . $order->no,
        ]);

        // 把要转换的字符串作为 QrCode 的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    // 微信的扫码支付没有前端回调只有服务器端回调
    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data = app('wechat_pay')->verify();

        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)->first();

        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }

        // 订单已支付
        if ($order->paid_at) {
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'   => Carbon::now(),
            'payment_method'    => 'wechat',
            'payment_no'        => $data->transaction_id,
        ]);
        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }


    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }
}
