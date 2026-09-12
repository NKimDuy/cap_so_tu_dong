<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#2563eb',
                            600: '#1d4ed8', 700: '#1e40af',
                        },
                    },
                },
            },
        }
    </script>
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside class="hidden md:flex md:flex-col w-64 bg-slate-900 text-slate-200 shrink-0">
            <div class="px-5 py-5 border-b border-slate-800">
                <div class="text-sm font-semibold leading-tight text-white">Cấp Số Tự Động</div>
                <div class="text-xs text-slate-400">TT. Đào tạo từ xa</div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Tổng quan</x-nav-link>

                @if(auth()->user()?->isChuyenVien())
                    <div class="pt-3 pb-1 px-3 text-xs font-semibold text-slate-500 uppercase">Chuyên viên</div>
                    <x-nav-link :href="route('so.xin-so')" :active="request()->routeIs('so.xin-so')">Xin số công văn</x-nav-link>
                    <x-nav-link :href="route('so.cua-toi')" :active="request()->routeIs('so.cua-toi')">Số của tôi</x-nav-link>
                @endif

                @if(auth()->user()?->isManagerLike())
                    <div class="pt-3 pb-1 px-3 text-xs font-semibold text-slate-500 uppercase">Quản lý</div>
                    <x-nav-link :href="route('so.duyet-so-lui')" :active="request()->routeIs('so.duyet-so-lui')">Duyệt số lùi</x-nav-link>
                    <x-nav-link :href="route('so.kho-du-tru')" :active="request()->routeIs('so.kho-du-tru')">Kho số lùi dự trữ</x-nav-link>
                    <x-nav-link :href="route('so.tat-ca')" :active="request()->routeIs('so.tat-ca')">Tất cả số công văn</x-nav-link>
                    <x-nav-link :href="route('bao-cao')" :active="request()->routeIs('bao-cao')">Báo cáo thống kê</x-nav-link>
                @endif

                @if(auth()->user()?->isAdmin())
                    <div class="pt-3 pb-1 px-3 text-xs font-semibold text-slate-500 uppercase">Admin</div>
                    <x-nav-link :href="route('admin.nguoi-dung')" :active="request()->routeIs('admin.nguoi-dung')">Tài khoản người dùng</x-nav-link>
                    <x-nav-link :href="route('admin.cau-hinh')" :active="request()->routeIs('admin.cau-hinh')">Cấu hình hệ thống</x-nav-link>
                    <x-nav-link :href="route('admin.nhat-ky')" :active="request()->routeIs('admin.nhat-ky')">Nhật ký hệ thống</x-nav-link>
                @endif
            </nav>
            <div class="px-3 py-4 border-t border-slate-800 text-xs text-slate-400">
                Đăng nhập: <span class="text-slate-200">{{ auth()->user()?->name }}</span><br>
                Vai trò: <span class="text-slate-200">{{ auth()->user()?->role?->label() }}</span>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between">
                <div class="md:hidden font-semibold text-slate-800">Cấp Số Tự Động</div>
                <div class="flex-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-600 hover:text-red-600">Đăng xuất</button>
                </form>
            </header>

            <main class="flex-1 p-4 md:p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
