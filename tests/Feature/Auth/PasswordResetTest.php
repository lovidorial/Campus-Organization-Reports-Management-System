<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status', __('passwords.sent'));
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertSame('Reset your Orgtrack password', $mail->subject);
            $this->assertSame('Reset Password', $mail->actionText);
            $this->assertStringContainsString('60 minutes', implode(' ', array_merge($mail->introLines, $mail->outroLines)));

            return true;
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', __('passwords.reset'));

            return true;
        });
    }

    public function test_login_page_displays_session_status(): void
    {
        $this->withSession(['status' => 'Your password has been reset.'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Your password has been reset.');
    }

    public function test_unknown_email_receives_the_same_generic_success_message(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $knownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $unknownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.test']);

        $this->assertSame(
            $knownResponse->getSession()->get('status'),
            $unknownResponse->getSession()->get('status')
        );
        $this->assertSame(__('passwords.sent'), $unknownResponse->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_archived_officer_gets_generic_success_without_a_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['officer_status' => 'archived']);

        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status', __('passwords.sent'));
        Notification::assertNotSentTo($user, ResetPasswordNotification::class);
    }

    public function test_mail_transport_exception_still_returns_generic_success(): void
    {
        $user = User::factory()->create();

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andThrow(new RuntimeException('Transport failure details must not be shown or logged.'));

        Log::shouldReceive('error')
            ->once()
            ->with('Password reset email could not be sent.', ['exception' => RuntimeException::class]);

        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

        $response
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from('/reset-password/invalid')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->travelTo(now()->addMinutes(61));

        $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');
    }
}
