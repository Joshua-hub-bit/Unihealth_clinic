<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth.animated')] class extends Component {
    // Login properties
    public string $loginEmail = '';
    public string $loginPassword = '';
    public bool $remember = false;

    // Register properties
    public string $registerName = '';
    public string $registerEmail = '';
    public string $registerPassword = '';
    public string $registerPasswordConfirmation = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $validated = $this->validate([
            'loginEmail' => 'required|string|email',
            'loginPassword' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'loginEmail' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'registerName' => ['required', 'string', 'max:255'],
            'registerEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class . ',email'],
            'registerPassword' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'registerPassword.confirmed' => 'The password confirmation does not match.',
        ], [
            'registerPasswordConfirmation' => 'password confirmation',
        ]);

        $user = User::create([
            'name' => $validated['registerName'],
            'email' => $validated['registerEmail'],
            'password' => Hash::make($validated['registerPassword']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'loginEmail' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->loginEmail).'|'.request()->ip());
    }
}; ?>

<div x-data="{ currentForm: 'login' }" x-on:switch-form.window="currentForm = $event.detail.form">
    <!-- Login Form -->
    <div class="form-slide" 
         :class="currentForm === 'login' ? 'active' : 'left'"
         x-show="currentForm === 'login'">
        <div class="flex flex-col gap-6">
            <x-auth-header title="Log in to your account" description="Enter your email and password below to log in" />

            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />

            <form wire:submit="login" class="flex flex-col gap-6">
                <!-- Email Address -->
                <flux:input wire:model="loginEmail" label="{{ __('Email address') }}" type="email" name="loginEmail" required autofocus autocomplete="email" placeholder="email@example.com" />

                <!-- Password -->
                <div class="relative">
                    <flux:input
                        wire:model="loginPassword"
                        label="{{ __('Password') }}"
                        type="password"
                        name="loginPassword"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />

                    @if (Route::has('password.request'))
                        <x-text-link class="absolute right-0 top-0" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </x-text-link>
                    @endif
                </div>

                <!-- Remember Me -->
                <flux:checkbox wire:model="remember" label="{{ __('Remember me') }}" />

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
                Don't have an account?
                <button type="button" 
                        @click.prevent="$dispatch('switch-form', { form: 'register' })" 
                        class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                    Sign up
                </button>
            </div>
        </div>
    </div>

    <!-- Register Form -->
    <div class="form-slide" 
         :class="currentForm === 'register' ? 'active' : 'right'"
         x-show="currentForm === 'register'">
        <div class="flex flex-col gap-6">
            <x-auth-header title="Create an account" description="Enter your details below to create your account" />

            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />

            <form wire:submit="register" class="flex flex-col gap-6">
                <!-- Name -->
                <div class="grid gap-2">
                    <flux:input wire:model="registerName" id="registerName" label="{{ __('Name') }}" type="text" name="registerName" required autofocus autocomplete="name" placeholder="Full name" />
                </div>

                <!-- Email Address -->
                <div class="grid gap-2">
                    <flux:input wire:model="registerEmail" id="registerEmail" label="{{ __('Email address') }}" type="email" name="registerEmail" required autocomplete="email" placeholder="email@example.com" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <flux:input
                        wire:model="registerPassword"
                        id="registerPassword"
                        label="{{ __('Password') }}"
                        type="password"
                        name="registerPassword"
                        required
                        autocomplete="new-password"
                        placeholder="Password"
                    />
                </div>

                <!-- Confirm Password -->
                <div class="grid gap-2">
                    <flux:input
                        wire:model="registerPasswordConfirmation"
                        id="registerPasswordConfirmation"
                        label="{{ __('Confirm password') }}"
                        type="password"
                        name="registerPasswordConfirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                </div>

                <div class="flex items-center justify-end">
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Create account') }}
                    </flux:button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
                Already have an account?
                <button type="button" 
                        @click.prevent="$dispatch('switch-form', { form: 'login' })" 
                        class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                    Log in
                </button>
            </div>
        </div>
    </div>
</div>
