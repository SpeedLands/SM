<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('SM: School Management System') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        <style>
            body { font-family: 'Instrument Sans', sans-serif; }
            .glass { 
                background: rgba(255, 255, 255, 0.7); 
                backdrop-filter: blur(12px); 
                border: 1px solid rgba(255, 255, 255, 0.2); 
            }
            .dark .glass { 
                background: rgba(23, 23, 23, 0.7); 
                border: 1px solid rgba(255, 255, 255, 0.05); 
            }
            .hero-pattern {
                background-color: transparent;
                background-image: radial-gradient(circle at 2px 2px, rgba(59, 130, 246, 0.05) 1px, transparent 0);
                background-size: 40px 40px;
            }
        </style>
    </head>
    <body class="antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 min-h-screen selection:bg-blue-500/30 hero-pattern">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 glass border-b border-zinc-200 dark:border-white/5 px-6 py-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="size-10 bg-blue-600 rounded-xl flex items-center justify-center font-bold text-xl shadow-lg shadow-blue-500/20 text-white leading-none">SM</div>
                    <span class="text-xl font-bold tracking-tight hidden sm:block">{{ __('School Management System') }}</span>
                </div>
                <div class="flex items-center gap-6">
                    <a href="#features" class="text-sm font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">{{ __('Features') }}</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-sm font-semibold rounded-lg transition-all shadow-md shadow-blue-500/20 text-white">
                                {{ __('Access the Platform') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                {{ __('Log in') }}
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-6 pt-24 pb-32 flex flex-col items-center text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-600 dark:text-blue-400 text-xs font-bold mb-8 uppercase tracking-widest">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500 shadow-sm shadow-blue-500/50"></span>
                </span>
                {{ __('Efficient Communication') }}
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-600 dark:from-white dark:via-zinc-200 dark:to-zinc-500 bg-clip-text text-transparent">
                {{ __('SM: The Future of School Management') }}
            </h1>
            <p class="text-lg md:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mb-12 leading-relaxed">
                {{ __('Manage your institution with ease and precision.') }} {{ __('Real-time tracking of students, grades, and discipline.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-5">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-lg font-bold rounded-2xl transition-all flex items-center gap-3 group shadow-xl shadow-blue-600/25 text-white">
                        <span>{{ __('Access the Platform') }}</span>
                        <svg class="size-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 text-lg font-bold rounded-2xl transition-all shadow-xl shadow-zinc-200/50 dark:shadow-none">
                        {{ __('Login to your account') }}
                    </a>
                @endauth
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-200 dark:border-white/5 relative">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] group hover:border-blue-500/40 transition-all shadow-sm">
                    <div class="size-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-8 border border-blue-500/20 group-hover:scale-110 transition-transform">
                        <svg class="size-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2zM9 9h10a2 2 0 012 2v8a2 2 0 01-2 2H9" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 tracking-tight">{{ __('Centralized Management') }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-base leading-relaxed">{{ __('Real-time tracking of students, grades, and discipline.') }}</p>
                </div>
                <!-- Feature 2 -->
                <div class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] group hover:border-emerald-500/40 transition-all shadow-sm">
                    <div class="size-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-8 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                        <svg class="size-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 tracking-tight">{{ __('Parent Portal') }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-base leading-relaxed">{{ __('Keep parents informed with instant notices and citations.') }}</p>
                </div>
                <!-- Feature 3 -->
                <div class="p-8 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] group hover:border-violet-500/40 transition-all shadow-sm">
                    <div class="size-14 bg-violet-500/10 rounded-2xl flex items-center justify-center mb-8 border border-violet-500/20 group-hover:scale-110 transition-transform">
                        <svg class="size-7 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 tracking-tight">{{ __('Admin Dashboard') }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-base leading-relaxed">{{ __('Powerful tools for administrators to oversee all operations.') }}</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto px-6 py-16 border-t border-zinc-200 dark:border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">
                © {{ date('Y') }} SM: Sistema de Gestión Escolar. {{ __('Academic Success') }}.
            </div>
            <div class="flex gap-10 text-zinc-500 dark:text-zinc-400 text-xs font-bold uppercase tracking-widest">
                <a href="#" class="hover:text-zinc-900 dark:hover:text-white transition-colors">{{ __('About SM') }}</a>
                <a href="#" class="hover:text-zinc-900 dark:hover:text-white transition-colors">{{ __('Contact Support') }}</a>
            </div>
        </footer>
    </body>
</html>
