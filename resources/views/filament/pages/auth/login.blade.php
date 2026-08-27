<div class="grid min-h-screen w-full lg:grid-cols-2 bg-white dark:bg-zinc-950 antialiased font-sans text-zinc-900 dark:text-zinc-100">

    <!-- LEFT COLUMN: Login Form -->
    <div class="flex flex-col justify-between p-6 sm:p-10 lg:p-14 relative z-10">
        <!-- Top Brand Header -->
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950 shadow-xs">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"></path>
                    <path d="M4 14v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"></path>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                </svg>
            </div>
            <span class="text-sm font-bold tracking-tight text-zinc-900 dark:text-white">{{ config('app.name', 'LED Manager') }}</span>
        </div>

        <!-- Center Form Card -->
        <div class="w-full max-w-sm mx-auto my-auto py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">
                    Đăng nhập tài khoản
                </h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1.5">
                    Nhập thông tin đăng nhập của bạn để tiếp tục
                </p>
            </div>

            <!-- Filament Form -->
            <div class="fi-auth-login-form">
                {{ $this->content }}
            </div>
        </div>

        <!-- Bottom Copyright -->
        <div class="text-xs text-zinc-400 text-center lg:text-left">
            © {{ date('Y') }} {{ config('app.name', 'LED Manager') }}. All rights reserved.
        </div>
    </div>

    <!-- RIGHT COLUMN: Image -->
    <div class="relative hidden lg:block h-full w-full bg-zinc-900 overflow-hidden">
        <img
            src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1920&q=80"
            alt="Authentication Background"
            class="absolute inset-0 h-full w-full object-cover object-center brightness-[0.85] dark:brightness-75"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-transparent to-zinc-950/20"></div>
        <div class="absolute bottom-10 left-10 right-10 z-10 text-white">
            <blockquote class="space-y-2">
                <p class="text-lg font-medium tracking-tight">
                    "Hệ thống quản lý vật tư & màn hình LED chuyên nghiệp, nhanh chóng và tối ưu."
                </p>
                <footer class="text-sm text-zinc-300 font-normal">
                    LED Management System
                </footer>
            </blockquote>
        </div>
    </div>

</div>

