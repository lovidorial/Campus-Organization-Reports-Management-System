<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->ensureIsNotRateLimited();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): bool
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()->where('email', $this->input('email'))->first();
        if (! $user) {
            $this->failAuthentication('email', 'No account found with this email.');
        }

        if (! Hash::check($this->input('password'), $user->password)) {
            $this->failAuthentication('password', 'Incorrect password.');
        }

        $termEnded = $user->isTermEnded();
        if ($termEnded) {
            activity('auth')
                ->performedOn($user)
                ->withProperties(['user_id' => $user->id])
                ->log('login blocked: term ended');
        }

        if ($termEnded && $user->officer_status !== 'archived' && $user->archived_at === null) {
            throw ValidationException::withMessages([
                'email' => User::TERM_ENDED_MESSAGE,
            ]);
        }

        if (! $user->isAdmin() && $user->archived_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'This account has been archived.',
            ]);
        }

        if (! $user->isAdmin() && $user->officer_status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'This account is inactive. Please contact the OSDW office.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $sent = $user->sendEmailVerificationNotification();
            $this->session()->flash('identity-verification-sent', $sent);
            $this->session()->flash('identity-verification-user-id', $user->id);

            return false;
        }

        RateLimiter::clear($this->throttleKey());
        Cache::forget($this->lockoutStateKey());
        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user, $this->boolean('remember'));

        return true;
    }

    private function failAuthentication(string $field, string $message): never
    {
        $lockoutSeconds = $this->recordFailedAttempt();
        if ($lockoutSeconds !== null) {
            $this->ensureIsNotRateLimited();
        }

        throw ValidationException::withMessages([$field => $message]);
    }

    public function recordFailedAttempt(): ?int
    {
        $key = $this->throttleKey();
        $state = Cache::get($this->lockoutStateKey());
        if (! is_array($state)) {
            RateLimiter::clear($key);
            $state = ['failed_count' => 0, 'lockout_count' => 0];
        }

        RateLimiter::hit($key, 86400);
        $state['failed_count'] = (int) ($state['failed_count'] ?? 0) + 1;
        $state['lockout_count'] = (int) ($state['lockout_count'] ?? 0);

        if ($state['failed_count'] >= 5) {
            $state['lockout_count']++;
            $state['failed_count'] = 0;
            $duration = [60, 300, 900, 3600][min($state['lockout_count'] - 1, 3)];
            $state['locked_until'] = now()->timestamp + $duration;
            Cache::put($this->lockoutStateKey(), $state, now()->addDay());
            RateLimiter::clear($key);

            return $duration;
        }

        Cache::put($this->lockoutStateKey(), $state, now()->addDay());

        return null;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $state = Cache::get($this->lockoutStateKey(), []);
        $seconds = max(0, (int) ($state['locked_until'] ?? 0) - now()->timestamp);
        if ($seconds <= 0) {
            return;
        }

        event(new Lockout($this));
        session()->flash('login_retry_after', $seconds);

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        throw ValidationException::withMessages([
            'email' => "Too many attempts. Try again in {$minutes} minutes {$remainingSeconds} seconds.",
        ]);
    }

    private function lockoutStateKey(): string
    {
        return 'login-lockout:'.hash('sha256', $this->throttleKey());
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
