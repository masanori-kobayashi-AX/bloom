<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // パスワード再設定メールを日本語化（通知チャネルは維持）
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('【Bloom】パスワード再設定のご案内')
                ->greeting('Bloom パスワード再設定')
                ->line('パスワード再設定のリクエストを受け付けました。下のボタンから新しいパスワードを設定してください。')
                ->action('パスワードを再設定する', $url)
                ->line('このリンクの有効期限は60分です。')
                ->line('心当たりがない場合は、このメールを破棄してください。');
        });
    }
}
