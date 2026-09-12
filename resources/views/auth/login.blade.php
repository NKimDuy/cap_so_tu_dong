<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-lg font-semibold text-slate-800">Hệ thống Cấp Số Tự Động</div>
            <div class="text-sm text-slate-500">Trung tâm Đào tạo từ xa</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    Ghi nhớ đăng nhập
                </label>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition">
                    Đăng nhập
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-4">
            Tài khoản do Admin hệ thống cấp. Liên hệ Admin nếu bạn chưa có tài khoản.
        </p>
    </div>
</body>
</html>
