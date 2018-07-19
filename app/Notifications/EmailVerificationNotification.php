<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;
use Cache;

/**
 * 发邮件验证的消息类
 * 在类的申明里我们加上了 implements ShouldQueue，ShouldQueue 这个接口本身没有定义任何方法，对于继承了 ShouldQueue 的邮件类 Laravel 会用将发邮件的操作放进队列里来实现异步发送；
 * Class EmailVerificationNotification
 * @package App\Notifications
 */
class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     * 发送邮件调用此方法构建邮件内容，参数就是 App\Models\User
     *  greeting() 方法可以设置邮件的欢迎词；
        subject() 方法用来设定邮件的标题；
        line() 方法会在邮件内容里添加一行文字；
        action() 方法会在邮件内容里添加一个链接按钮。
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // 使用 Laravel 内置的 Str 类生成随机字符串的函数，参数就是要生成的字符串长度
        $token = Str::random(16);
        // 往缓存中写入这个随机字符串，有效时间为30分钟
        Cache::set('email_verification_'.$notifiable->email, $token, 30);
        $url = route('email_verification.verify',['email' => $notifiable->email, 'token' => $token]);

        return (new MailMessage)
                    ->greeting($notifiable->name.'您好:')
                    ->subject('注册成功，请验证您的邮箱')
                    ->line('请点击下方链接验证您的邮箱.')
                    ->action('验证', $url)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
