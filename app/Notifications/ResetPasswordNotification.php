<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Reset your OrgTrack password')
            ->greeting('Password reset request')
            ->line('We received a request to reset the password for your OrgTrack account.')
            ->action('Reset Password', $this->resetUrl($notifiable))
            ->line('This password reset link expires in 60 minutes.')
            ->line('If you did not request a password reset, you can ignore this email.');
    }
}