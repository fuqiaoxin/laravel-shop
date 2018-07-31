<?php

namespace App\Admin\Controllers;

use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Http\Requests\Request;
use App\Models\Order;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class OrdersController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('订单列表');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
//    public function edit($id)
//    {
//        return Admin::content(function (Content $content) use ($id) {
//
//            $content->header('header');
//            $content->description('description');
//
//            $content->body($this->form()->edit($id));
//        });
//    }

    /**
     * Create interface.
     *
     * @return Content
     */
//    public function create()
//    {
//        return Admin::content(function (Content $content) {
//
//            $content->header('header');
//            $content->description('description');
//
//            $content->body($this->form());
//        });
//    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Order::class, function (Grid $grid) {
            // 只显示已支付的订单，默认按支付时间倒序排序
            //$grid->model()->whereNotNull('paid_at')->orderBy('paid_at','desc');

            $grid->id('ID')->sortable();
            $grid->no('订单号');
            // 展示关联关系的字段时， 使用 column 方法
            $grid->column('user.name', '买家');
            $grid->total_amount('总金额')->sortable();
            $grid->paid_at('支付时间')->sortable();
            $grid->ship_status('物流')->display(function ($value) {
                return Order::$shipStatusMap[$value];
            });

            $grid->refund_status('退款状态')->display(function ($value) {
                return Order::$refundStatusMap[$value];
            });

            // 禁用创建按钮， 后台不需要创建订单
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                // 禁用删除和编辑按钮
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a class="btn btn-xs btn-primary" href="'.route('admin.orders.show', [$actions->getKey()]).'">查看</a>');
            });

            $grid->tools(function ($tools) {
                // 禁用批量删除按钮
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });


            $grid->created_at();
            $grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Order::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }

    public function show(Order $order)
    {
        return Admin::content(function (Content $content) use ($order) {
            $content->header('查看订单');

            // body 方法可以接受 Laravel 的视图作为参数
            $content->body(view('admin.orders.show', ['order' => $order]));
        });
    }

    public function ship(Order $order, Request $request)
    {
        // 判断当前订单是否已支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未付款');
        }

        // 判断当前订单发货状态是否为未发货
        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已发货');
        }

        // Laravel 5.5 之后 validate 方法可以返回校验过的值
        $data = $this->validate($request, [
            'express_company'       => ['required'],
            'express_no'            => ['required'],
        ], [], [
            'express_company'       => '物流公司',
            'express_no'            => '物流单号',
        ]);

        // 将订单发货状态改为已发货，并存入物流信息
        $order->update([
            'ship_status'   => Order::SHIP_STATUS_DELIVERED,

            // 在 Order 模型的 $casts 属性里指明了 ship_data 是一个数组
            'ship_data' => $data,
        ]);

        // 返回上一页
        return redirect()->back();

    }

    public function handleRefund(Order $order, HandleRefundRequest $request)
    {
        // 判断订单状态是否正确
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 是否同意退款
        if ($request->input('agree')) {
            // 同意退款
            $this->_refundOrder($order);


        } else {
            // 将拒绝退款理由放到订单的 extra 字段中
            $extra                              = $order->extra ? : [];
            $extra['refund_disagree_reason']    = $request->input('reason');

            // 将订单的退款状态改为未退款
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }

        return $order;
    }

    protected function _refundOrder (Order $order)
    {
        // 判断该订单支付方式
        switch ($order->payment_method) {
            case 'wechat':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no'  => $order->no,
                    'total_fee'     => $order->total_amount,
                    'refund_fee'    => $order->total_amount,
                    'out_refund_no' => $refundNo,
                    // 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                    'notify_url'    => route('payment.wechat.refund_notify'),   //
                ]);

                // 将订单状态改为退款中
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;

            case 'alipay':
                // 生成退款单号
                $refundNo = Order::getAvailableRefundNo();

                // 调用支付宝支付实例的 refund 方法
                $ret = app('alipay')->refund([
                    'out_trade_no'  => $order->no,              // 之前的订单流水号
                    'refund_amount' => $order->total_amount,    // 退款金额，单位元
                    'out_request_no'=> $refundNo,               //  退款订单号
                ]);

                // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                if ($ret->sub_code) {
                    // 将退款失败的保存入 extra 字段
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;

                    //将订单退款状态标记为退款失败
                    $order->update([
                        'refund_no'     => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra'         => $extra,
                    ]);
                }else {
                    // 将订单的退款状态标记为退款成功并保存退款订单号
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }


                break;
            default:
                throw new InternalException('未知错误');
                break;
        }
    }

}
