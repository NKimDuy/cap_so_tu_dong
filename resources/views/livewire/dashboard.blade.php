<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Tổng quan</h1>
        <p class="text-sm text-slate-500">Xin chào {{ auth()->user()->name }}, hôm nay là {{ now()->translatedFormat('d/m/Y') }}.</p>
    </div>

    @if(auth()->user()->isChuyenVien())
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card>
                <div class="text-sm text-slate-500">Số chưa trả</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $outstanding }} / {{ $maxPending }}</div>
                @if($outstanding >= $maxPending)
                    <div class="text-xs text-red-600 mt-1">Đã đạt giới hạn, hãy trả bớt số trước khi xin thêm.</div>
                @endif
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Tổng số đã xin năm {{ now()->year }}</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $totalThisYear }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Yêu cầu số lùi đang chờ duyệt</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $pendingBackdate }}</div>
            </x-card>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('so.xin-so') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700">+ Xin số công văn</a>
            <a href="{{ route('so.cua-toi') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">Xem số của tôi</a>
        </div>

        <x-card title="5 số gần đây nhất">
            @if($recent->isEmpty())
                <p class="text-sm text-slate-500">Bạn chưa xin số công văn nào.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-4">Số</th>
                                <th class="py-2 pr-4">Năm</th>
                                <th class="py-2 pr-4">Ngày ghi nhận/Ngày ký</th>
                                <th class="py-2 pr-4">Nội dung</th>
                                <th class="py-2 pr-4">Trạng thái</th>
                                <th class="py-2 pr-4">Hạn trả</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $doc)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4 font-semibold">{{ $doc->number_display }}</td>
                                    <td class="py-2 pr-4 text-slate-500">{{ $doc->year }}</td>
                                    <td class="py-2 pr-4"><x-date-time :value="$doc->signed_at" :time="$doc->signed_has_time" /></td>
                                    <td class="py-2 pr-4">{{ $doc->content }}</td>
                                    <td class="py-2 pr-4"><x-status-badge :status="$doc->status" /></td>
                                    <td class="py-2 pr-4">{{ $doc->expected_return_date?->format('d/m/Y') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    @endif

    @if(auth()->user()->isManagerLike())
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <x-card>
                <div class="text-sm text-slate-500">Yêu cầu số lùi chờ duyệt</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $pendingApprovals }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Số lùi dự trữ còn trong kho</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $reserveCount }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Số đã cấp trong tháng {{ now()->month }} (theo ngày ký)</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $issuedThisMonth }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Hồ sơ quá hạn chưa trả</div>
                <div class="text-2xl font-semibold text-red-600 mt-1">{{ $overdue->count() }}</div>
            </x-card>
        </div>

        <x-card title="Hồ sơ quá hạn chưa trả (mới nhất)">
            @if($overdue->isEmpty())
                <p class="text-sm text-slate-500">Không có hồ sơ nào quá hạn. 🎉</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-4">Số</th>
                                <th class="py-2 pr-4">Năm</th>
                                <th class="py-2 pr-4">Người xin</th>
                                <th class="py-2 pr-4">Hạn trả</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdue as $doc)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4 font-semibold">{{ $doc->number_display }}</td>
                                    <td class="py-2 pr-4 text-slate-500">{{ $doc->year }}</td>
                                    <td class="py-2 pr-4">{{ $doc->requester?->name }}</td>
                                    <td class="py-2 pr-4 text-red-600">{{ $doc->expected_return_date?->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    @endif

    @if(auth()->user()->isAdmin())
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card>
                <div class="text-sm text-slate-500">Tổng số người dùng</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $totalUsers }} <span class="text-sm font-normal text-slate-500">({{ $activeUsers }} đang hoạt động)</span></div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Tổng số công văn năm {{ now()->year }}</div>
                <div class="text-2xl font-semibold text-slate-800 mt-1">{{ $totalThisYearAll }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-slate-500">Quản trị</div>
                <a href="{{ route('admin.nguoi-dung') }}" class="text-brand-600 text-sm hover:underline block mt-1">Quản lý tài khoản →</a>
                <a href="{{ route('admin.cau-hinh') }}" class="text-brand-600 text-sm hover:underline block mt-1">Cấu hình hệ thống →</a>
            </x-card>
        </div>
    @endif
</div>
