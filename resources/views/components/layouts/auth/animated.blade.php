<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .auth-container {
                position: relative;
                background: linear-gradient(135deg, #1a4d2e 0%, #2d5a3d 50%, #1a4d2e 100%);
            }
            
            .auth-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M50 0 L50 100 M0 50 L100 50' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3C/svg%3E");
                background-size: 50px 50px;
                opacity: 0.5;
            }
            
            .welcome-illustration {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.15;
                pointer-events: none;
                z-index: 0;
            }
            
            .form-card {
                position: relative;
                z-index: 1;
                backdrop-filter: blur(20px);
                background: rgba(255, 255, 255, 0.98);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            
            .dark .form-card {
                background: rgba(15, 23, 42, 0.95);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .form-wrapper {
                position: relative;
                min-height: 400px;
            }
            
            .form-slide {
                transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                will-change: transform, opacity;
            }
            
            .form-slide.active {
                transform: translateX(0) scale(1);
                opacity: 1;
                position: relative;
            }
            
            .form-slide.left {
                transform: translateX(-100%) scale(0.9);
                opacity: 0;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                pointer-events: none;
            }
            
            .form-slide.right {
                transform: translateX(100%) scale(0.9);
                opacity: 0;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                pointer-events: none;
            }

            /* Fade-in animation for page load */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .form-card {
                animation: fadeInUp 0.8s ease-out;
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div class="auth-container flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <!-- Background illustration -->
            <div class="welcome-illustration hidden lg:block">
                <svg width="600" height="600" viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Medical Cross -->
                    <rect x="275" y="200" width="50" height="200" fill="currentColor" class="text-white/20"/>
                    <rect x="200" y="275" width="200" height="50" fill="currentColor" class="text-white/20"/>
                    
                    <!-- Stethoscope outline -->
                    <circle cx="300" cy="450" r="40" stroke="currentColor" stroke-width="8" fill="none" class="text-white/20"/>
                    <path d="M300 410 Q250 350, 250 280 Q250 220, 300 180" stroke="currentColor" stroke-width="8" fill="none" class="text-white/20"/>
                    <circle cx="300" cy="180" r="25" fill="currentColor" class="text-white/20"/>
                </svg>
            </div>

            <div class="form-card flex w-full max-w-md flex-col gap-6 rounded-2xl border border-zinc-200 p-8 shadow-2xl dark:border-zinc-800">
                <a href="{{ route('clinic.landing') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-12 w-12 mb-2 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-12 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                
                <div class="form-wrapper" 
                     x-data="{ 
                         currentForm: '{{ request()->routeIs('register') ? 'register' : 'login' }}'
                     }"
                     x-on:switch-form.window="currentForm = $event.detail.form">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
