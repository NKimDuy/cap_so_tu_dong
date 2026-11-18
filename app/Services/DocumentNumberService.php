<?php

namespace App\Services;

use App\Enums\BackdateRequestStatus;
use App\Enums\DocumentNumberStatus;
use App\Enums\SystemLogAction;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Mail\BackdateRejectedMail;
use App\Mail\BackdateRequestMail;
use App\Mail\ManagerTrackingMail;
use App\Mail\NumberIssuedMail;
use App\Models\BackdateRequest;
use App\Models\DocumentNumber;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DocumentNumberService
{
    public function maxPending(): int
    {
        return (int) \App\Models\Setting::get('max_pending_numbers', 3);
    }

    public function reminderIntervalDays(): int
    {
        return (int) \App\Models\Setting::get('reminder_interval_days', 7);
    }

    public function countOutstanding(User $user): int
    {
        return DocumentNumber::where('requester_id', $user->id)
            ->where('status', DocumentNumberStatus::DaCap->value)
            ->count();
    }

    /** Giới hạn số chưa trả chỉ áp dụng cho chuyên viên; Quản lý/Admin không giới hạn. */
    public function hasPendingLimit(User $user): bool
    {
        return ! $user->isManagerLike();
    }

    protected function assertUnderLimit(User $user): void
    {
        if (! $this->hasPendingLimit($user)) {
            return;
        }

        if ($this->countOutstanding($user) >= $this->maxPending()) {
            throw new BusinessRuleException(
                "Bạn hiện đang có {$this->maxPending()} số chưa trả. Vui lòng hoàn thành (trả số) ít nhất 1 số trước khi xin số mới."
            );
        }
    }

    /** Tính số thứ tự tiếp theo trong năm, có xử lý va chạm khi nhiều người xin cùng lúc. */
    protected function nextNumber(int $year): int
    {
        $max = DocumentNumber::where('year', $year)->lockForUpdate()->max('number');

        return ($max ?? 0) + 1;
    }

    protected function managers()
    {
        return User::query()->active()->where('role', UserRole::QuanLy->value)->get();
    }

    /**
     * Xin số bình thường: hệ thống cấp số ngay theo thứ tự tuần tự.
     * $registeredBy: Quản lý/Admin xin giúp (null = người xin tự xin).
     */
    public function issueNormal(User $requester, array $data, ?User $registeredBy = null): DocumentNumber
    {
        // Quản lý chọn chính mình ở trang "Xin số giúp" thì coi như tự xin.
        if ($registeredBy && $registeredBy->is($requester)) {
            $registeredBy = null;
        }

        if ($registeredBy) {
            $this->assertCanRegisterFor($registeredBy, $requester);
        }

        $doc = DB::transaction(function () use ($requester, $data, $registeredBy) {
            $this->assertUnderLimit($requester);

            $year = now()->year;
            $number = $this->nextNumber($year);

            $doc = DocumentNumber::create([
                'year' => $year,
                'number' => $number,
                'status' => DocumentNumberStatus::DaCap->value,
                'is_backdate' => false,
                'requester_id' => $requester->id,
                'registered_by' => $registeredBy?->id,
                'content' => $data['content'],
                'signer' => $data['signer'],
                'request_date' => now()->toDateString(),
                'record_date' => now(),
                'expected_return_date' => $data['expected_return_date'],
            ]);

            if ($registeredBy) {
                SystemLog::record(
                    $registeredBy->id,
                    SystemLogAction::XinSoHo,
                    "{$registeredBy->name} xin số {$doc->formatted_number} giúp {$requester->name}.",
                    ['document_number_id' => $doc->id, 'number' => $doc->number, 'year' => $doc->year, 'requester_id' => $requester->id]
                );
            } else {
                SystemLog::record(
                    $requester->id,
                    SystemLogAction::XinSoBinhThuong,
                    "{$requester->name} xin số {$doc->formatted_number}.",
                    ['document_number_id' => $doc->id, 'number' => $doc->number, 'year' => $doc->year]
                );
            }

            return $doc;
        });

        $this->safeSend(fn () => Mail::to($requester->email)->send(new NumberIssuedMail($doc)));

        foreach ($this->managers() as $manager) {
            // Quản lý tự xin số thì đã nhận mail cấp số ở trên, không gửi thêm mail theo dõi cho chính mình.
            if ($manager->is($requester)) {
                continue;
            }
            $this->safeSend(fn () => Mail::to($manager->email)->send(new ManagerTrackingMail($doc)));
        }

        return $doc;
    }

    /**
     * Xin số lùi: tạo yêu cầu chờ quản lý duyệt và cấp từ kho dự trữ.
     */
    public function requestBackdate(User $requester, array $data): BackdateRequest
    {
        $requestedDate = Carbon::parse($data['requested_date']);

        if ($requestedDate->isToday() || $requestedDate->isFuture()) {
            throw new BusinessRuleException('Ngày ghi nhận/ngày ký của số lùi phải là một ngày trong quá khứ.');
        }

        $req = DB::transaction(function () use ($requester, $data) {
            $this->assertUnderLimit($requester);

            $req = BackdateRequest::create([
                'requester_id' => $requester->id,
                'content' => $data['content'],
                'signer' => $data['signer'],
                'requested_date' => $data['requested_date'],
                'expected_return_date' => $data['expected_return_date'],
                'status' => BackdateRequestStatus::DangCho->value,
            ]);

            SystemLog::record(
                $requester->id,
                SystemLogAction::XinSoLui,
                "{$requester->name} xin số lùi ngày {$req->requested_date->format('d/m/Y')}.",
                ['backdate_request_id' => $req->id]
            );

            return $req;
        });

        foreach ($this->managers() as $manager) {
            $this->safeSend(fn () => Mail::to($manager->email)->send(new BackdateRequestMail($req)));
        }

        return $req;
    }

    /**
     * Quản lý chủ động giữ một số lùi dự trữ trong kho (chưa gán cho ai).
     */
    public function reserveBackdateNumber(User $manager, ?string $notes = null): DocumentNumber
    {
        return DB::transaction(function () use ($manager, $notes) {
            $year = now()->year;
            $number = $this->nextNumber($year);

            $doc = DocumentNumber::create([
                'year' => $year,
                'number' => $number,
                'status' => DocumentNumberStatus::SoLuiDuTru->value,
                'is_backdate' => true,
                'held_by' => $manager->id,
                'record_date' => now(),
                'notes' => $notes,
            ]);

            SystemLog::record(
                $manager->id,
                SystemLogAction::GiuSoLuiDuTru,
                "{$manager->name} giữ số lùi dự trữ {$doc->formatted_number}.",
                ['document_number_id' => $doc->id]
            );

            return $doc;
        });
    }

    /**
     * Quản lý/Admin đưa vào kho một số dự trữ đã giữ TRƯỚC khi dùng hệ thống (ghi trong sổ/Google Form cũ).
     * Khác với "Giữ số tiếp theo": quản lý nhập đúng số và năm cụ thể.
     *  - Không được trùng số đã có trong hệ thống.
     *  - Năm hiện tại: số phải NHỎ HƠN số tiếp theo sẽ cấp, để không làm nhảy dãy số đang cấp.
     */
    public function addExistingPoolNumber(User $manager, int $year, int $number, ?string $notes = null): DocumentNumber
    {
        if (! $manager->isManagerLike()) {
            throw new BusinessRuleException('Chỉ Quản lý hoặc Admin mới được nhập số dự trữ.');
        }

        $currentYear = now()->year;
        if ($year < 2000 || $year > $currentYear) {
            throw new BusinessRuleException("Năm không hợp lệ (chỉ nhận từ 2000 đến {$currentYear}).");
        }
        if ($number < 1) {
            throw new BusinessRuleException('Số phải từ 1 trở lên.');
        }

        return DB::transaction(function () use ($manager, $year, $number, $notes, $currentYear) {
            $display = sprintf('%02d/%d', $number, $year);

            if (DocumentNumber::where('year', $year)->where('number', $number)->lockForUpdate()->exists()) {
                throw new BusinessRuleException("Số {$display} đã có trong hệ thống.");
            }

            if ($year === $currentYear) {
                $next = $this->nextNumber($year);
                if ($number >= $next) {
                    throw new BusinessRuleException(
                        "Số {$display} chưa được cấp (số tiếp theo hệ thống sẽ cấp là ".sprintf('%02d', $next)."). "
                        .'Chỉ nhập số dự trữ đã giữ trước đây; muốn giữ số mới hãy dùng nút "Giữ số tiếp theo".'
                    );
                }
            }

            $doc = DocumentNumber::create([
                'year' => $year,
                'number' => $number,
                'status' => DocumentNumberStatus::SoLuiDuTru->value,
                'is_backdate' => true,
                'held_by' => $manager->id,
                'record_date' => now(),
                'notes' => trim('Số dự trữ cũ (nhập vào hệ thống). '.($notes ?? '')),
            ]);

            SystemLog::record(
                $manager->id,
                SystemLogAction::NhapSoDuTruCu,
                "{$manager->name} nhập số dự trữ cũ {$doc->formatted_number} vào kho.",
                ['document_number_id' => $doc->id]
            );

            return $doc;
        });
    }

    /**
     * Quản lý/Admin tự xin SỐ LÙI cho chính mình: cấp thẳng một số trong kho dự trữ, không qua bước chờ duyệt.
     */
    public function issueBackdateForSelf(User $manager, array $data, DocumentNumber $poolNumber): DocumentNumber
    {
        return $this->issueBackdateOnBehalf($manager, $manager, $data, $poolNumber);
    }

    /**
     * Quản lý/Admin xin SỐ LÙI giúp người khác (hoặc cho chính mình): cấp luôn một số trong kho dự trữ
     * (cùng năm với ngày ký), không qua bước chờ duyệt. Vẫn lưu lại một yêu cầu số lùi ở trạng thái
     * "Đã duyệt" để giữ lịch sử.
     */
    public function issueBackdateOnBehalf(User $manager, User $requester, array $data, DocumentNumber $poolNumber): DocumentNumber
    {
        $this->assertCanRegisterFor($manager, $requester);
        $forSelf = $manager->is($requester);

        $requestedDate = Carbon::parse($data['requested_date']);
        if ($requestedDate->isToday() || $requestedDate->isFuture()) {
            throw new BusinessRuleException('Ngày ghi nhận/ngày ký của số lùi phải là một ngày trong quá khứ.');
        }

        // Một giao dịch duy nhất: nếu không cấp được (sai năm, đủ giới hạn, số đã bị lấy...) thì không để lại yêu cầu treo.
        $doc = DB::transaction(function () use ($manager, $requester, $data, $poolNumber, $forSelf) {
            $this->assertUnderLimit($requester);

            $req = BackdateRequest::create([
                'requester_id' => $requester->id,
                'content' => $data['content'],
                'signer' => $data['signer'],
                'requested_date' => $data['requested_date'],
                'expected_return_date' => $data['expected_return_date'],
                'status' => BackdateRequestStatus::DangCho->value,
            ]);

            $doc = $this->assignPoolNumber($manager, $req, $poolNumber);

            if ($forSelf) {
                SystemLog::record(
                    $manager->id,
                    SystemLogAction::XinSoLui,
                    "{$manager->name} tự xin số lùi {$doc->formatted_number} (ngày ký {$req->requested_date->format('d/m/Y')}), cấp thẳng từ kho dự trữ.",
                    ['document_number_id' => $doc->id, 'backdate_request_id' => $req->id]
                );
            } else {
                $doc->update(['registered_by' => $manager->id]);

                SystemLog::record(
                    $manager->id,
                    SystemLogAction::XinSoHo,
                    "{$manager->name} xin số lùi {$doc->formatted_number} giúp {$requester->name}.",
                    ['document_number_id' => $doc->id, 'backdate_request_id' => $req->id, 'requester_id' => $requester->id]
                );
            }

            return $doc->fresh();
        });

        $this->safeSend(fn () => Mail::to($requester->email)->send(new NumberIssuedMail($doc)));

        return $doc;
    }

    /** Chỉ Quản lý/Admin được xin giúp (hoặc cấp thẳng số lùi), và chỉ cho tài khoản đang hoạt động. */
    protected function assertCanRegisterFor(User $actor, User $requester): void
    {
        if (! $actor->isManagerLike()) {
            throw new BusinessRuleException('Chỉ Quản lý hoặc Admin mới được xin số giúp người khác hoặc cấp thẳng số lùi.');
        }

        if (! $requester->is_active) {
            throw new BusinessRuleException('Chỉ xin số giúp được cho tài khoản đang hoạt động.');
        }
    }

    /**
     * Quản lý duyệt một yêu cầu xin số lùi bằng cách gán một số trong kho dự trữ.
     */
    public function approveBackdateRequest(User $manager, BackdateRequest $req, DocumentNumber $poolNumber): DocumentNumber
    {
        $req->loadMissing('requester');

        $doc = DB::transaction(fn () => $this->assignPoolNumber($manager, $req, $poolNumber));

        $this->safeSend(fn () => Mail::to($req->requester->email)->send(new NumberIssuedMail($doc)));

        return $doc;
    }

    /**
     * Gán một số trong kho dự trữ cho yêu cầu số lùi (dùng chung cho duyệt và xin giúp). Phải gọi trong transaction.
     */
    protected function assignPoolNumber(User $manager, BackdateRequest $req, DocumentNumber $poolNumber): DocumentNumber
    {
        $poolNumber->refresh();
        $req->refresh();
        $req->loadMissing('requester');

        if ($poolNumber->status !== DocumentNumberStatus::SoLuiDuTru) {
            throw new BusinessRuleException('Số này không còn ở trong kho số lùi dự trữ.');
        }

        if ($req->status !== BackdateRequestStatus::DangCho) {
            throw new BusinessRuleException('Yêu cầu này đã được xử lý trước đó.');
        }

        // Số đánh lại từ 01 mỗi năm: số lùi phải thuộc đúng năm của ngày ghi nhận/ngày ký.
        $signYear = (int) $req->requested_date->year;
        if ((int) $poolNumber->year !== $signYear) {
            throw new BusinessRuleException(
                "Số {$poolNumber->formatted_number} thuộc năm {$poolNumber->year}, không khớp với ngày ghi nhận/ngày ký "
                .$req->requested_date->format('d/m/Y')." (năm {$signYear}). Chỉ được cấp số dự trữ của năm {$signYear}."
            );
        }

        if ($this->hasPendingLimit($req->requester) && $this->countOutstanding($req->requester) >= $this->maxPending()) {
            throw new BusinessRuleException(
                "Chuyên viên {$req->requester->name} hiện đã có đủ {$this->maxPending()} số chưa trả, chưa thể cấp thêm số."
            );
        }

        $poolNumber->update([
            'requester_id' => $req->requester_id,
            'content' => $req->content,
            'signer' => $req->signer,
            'request_date' => $req->requested_date,
            'record_date' => now(),
            'expected_return_date' => $req->expected_return_date,
            'status' => DocumentNumberStatus::DaCap->value,
            'is_backdate' => true,
            'backdate_request_id' => $req->id,
            'registered_by' => null,
        ]);

        $req->update([
            'status' => BackdateRequestStatus::DaDuyet->value,
            'document_number_id' => $poolNumber->id,
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ]);

        SystemLog::record(
            $manager->id,
            SystemLogAction::DuyetCapSoLui,
            "{$manager->name} duyệt cấp số lùi {$poolNumber->formatted_number} cho {$req->requester->name}.",
            ['document_number_id' => $poolNumber->id, 'backdate_request_id' => $req->id]
        );

        return $poolNumber;
    }

    public function rejectBackdateRequest(User $manager, BackdateRequest $req, ?string $reason = null): BackdateRequest
    {
        $req->loadMissing('requester');

        if ($req->status !== BackdateRequestStatus::DangCho) {
            throw new BusinessRuleException('Yêu cầu này đã được xử lý trước đó.');
        }

        $req->update([
            'status' => BackdateRequestStatus::TuChoi->value,
            'reject_reason' => $reason,
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ]);

        SystemLog::record(
            $manager->id,
            SystemLogAction::TuChoiSoLui,
            "{$manager->name} từ chối yêu cầu xin số lùi của {$req->requester->name}.",
            ['backdate_request_id' => $req->id, 'reason' => $reason]
        );

        $this->safeSend(fn () => Mail::to($req->requester->email)->send(new BackdateRejectedMail($req)));

        return $req;
    }

    /**
     * Xác nhận hoàn thành (chuyên viên đã trả giấy tờ).
     */
    public function complete(User $actor, DocumentNumber $doc): DocumentNumber
    {
        if ($doc->status !== DocumentNumberStatus::DaCap) {
            throw new BusinessRuleException("Chỉ có thể xác nhận hoàn thành cho số đang ở trạng thái 'Đã cấp - Chờ trả'.");
        }

        $doc->update([
            'status' => DocumentNumberStatus::HoanThanh->value,
            'completed_at' => now(),
            'completed_by' => $actor->id,
        ]);

        SystemLog::record(
            $actor->id,
            SystemLogAction::HoanThanhTraSo,
            "{$actor->name} xác nhận hoàn thành số {$doc->formatted_number}.",
            ['document_number_id' => $doc->id]
        );

        return $doc->fresh();
    }

    /**
     * Quản lý/Admin mở lại một số đã "Hoàn thành" (bấm nhầm, hoặc cần hủy để sửa dữ liệu):
     * đưa về "Đã cấp - Chờ trả" và xóa thời điểm/người xác nhận hoàn thành. Nhật ký lưu lại giá trị cũ.
     */
    public function reopen(User $actor, DocumentNumber $doc): DocumentNumber
    {
        if (! $actor->isManagerLike()) {
            throw new BusinessRuleException('Chỉ Quản lý hoặc Admin mới được mở lại số đã hoàn thành.');
        }

        return DB::transaction(function () use ($actor, $doc) {
            $doc->refresh();

            if ($doc->status !== DocumentNumberStatus::HoanThanh) {
                throw new BusinessRuleException("Chỉ mở lại được số đang ở trạng thái 'Hoàn thành'.");
            }

            $old = [
                'completed_at' => $doc->completed_at?->format('Y-m-d H:i:s'),
                'completed_by' => $doc->completed_by,
            ];

            $doc->update([
                'status' => DocumentNumberStatus::DaCap->value,
                'completed_at' => null,
                'completed_by' => null,
                'last_reminder_sent_at' => null,
            ]);

            SystemLog::record(
                $actor->id,
                SystemLogAction::MoLaiSo,
                "{$actor->name} mở lại số {$doc->formatted_number} (bỏ xác nhận hoàn thành, đưa về 'Đã cấp - Chờ trả').",
                ['document_number_id' => $doc->id, 'old' => $old]
            );

            return $doc->fresh();
        });
    }

    /**
     * Quản lý/Admin chỉnh sửa thông tin của một số đã cấp (nội dung, người ký, ngày dự kiến trả).
     * Cho phép với số "Đã cấp - Chờ trả" và "Hoàn thành". Mỗi lần sửa đều ghi nhật ký giá trị cũ/mới.
     *
     * @param  array{content: string, signer: string, expected_return_date: string}  $data
     */
    public function updateInfo(User $actor, DocumentNumber $doc, array $data): DocumentNumber
    {
        if (! $actor->isManagerLike()) {
            throw new BusinessRuleException('Chỉ Quản lý hoặc Admin mới được sửa thông tin số công văn.');
        }

        return DB::transaction(function () use ($actor, $doc, $data) {
            $doc->refresh();

            if (! in_array($doc->status, [DocumentNumberStatus::DaCap, DocumentNumberStatus::HoanThanh], true)) {
                throw new BusinessRuleException("Chỉ sửa được số ở trạng thái 'Đã cấp - Chờ trả' hoặc 'Hoàn thành'.");
            }

            $new = [
                'content' => trim($data['content']),
                'signer' => trim($data['signer']),
                'expected_return_date' => Carbon::parse($data['expected_return_date'])->toDateString(),
            ];

            $old = [
                'content' => $doc->content,
                'signer' => $doc->signer,
                'expected_return_date' => $doc->expected_return_date?->toDateString(),
            ];

            $changes = [];
            foreach ($new as $field => $value) {
                if ($old[$field] !== $value) {
                    $changes[$field] = ['old' => $old[$field], 'new' => $value];
                }
            }

            if ($changes === []) {
                return $doc;
            }

            $update = $new;

            // Đổi hạn trả thì tính lại lịch nhắc trả số theo hạn mới.
            if (isset($changes['expected_return_date'])) {
                $update['last_reminder_sent_at'] = null;
            }

            $doc->update($update);

            SystemLog::record(
                $actor->id,
                SystemLogAction::SuaThongTinSo,
                "{$actor->name} sửa thông tin số {$doc->formatted_number}.",
                ['document_number_id' => $doc->id, 'changes' => $changes]
            );

            return $doc->fresh();
        });
    }

    public function canCancel(User $user, DocumentNumber $doc): bool
    {
        return $doc->status === DocumentNumberStatus::DaCap
            && ($user->id === $doc->requester_id || $user->isManagerLike());
    }

    /**
     * Hủy số công văn:
     * - Nếu đã có số khác được cấp sau số này -> chuyển thành "Số lùi dự trữ".
     * - Nếu chưa có số nào được cấp sau số này -> thu hồi hẳn (số này sẽ được cấp lại cho người xin tiếp theo).
     */
    public function cancel(User $actor, DocumentNumber $doc, ?string $reason = null): void
    {
        if (! $this->canCancel($actor, $doc)) {
            throw new BusinessRuleException("Bạn không có quyền hủy số này, hoặc số không ở trạng thái 'Đã cấp - Chờ trả'.");
        }

        DB::transaction(function () use ($actor, $doc, $reason) {
            $doc->refresh();

            $snapshot = $doc->toArray();
            $linkedRequestId = $doc->backdate_request_id;

            // Số của năm trước luôn được trả về kho dự trữ (không thu hồi), vì dãy số năm cũ không còn cấp tiếp
            // và hệ thống có thể chưa có đủ các số năm cũ để biết đây có phải số cuối hay không.
            $hasLaterNumber = $doc->year < now()->year
                || DocumentNumber::where('year', $doc->year)->where('number', '>', $doc->number)->exists();

            if ($linkedRequestId) {
                BackdateRequest::where('id', $linkedRequestId)->update([
                    'status' => BackdateRequestStatus::DangCho->value,
                    'document_number_id' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            }

            if ($hasLaterNumber) {
                $doc->update([
                    'status' => DocumentNumberStatus::SoLuiDuTru->value,
                    'is_backdate' => true,
                    'requester_id' => null,
                    'registered_by' => null,
                    'content' => null,
                    'signer' => null,
                    'request_date' => null,
                    'expected_return_date' => null,
                    'backdate_request_id' => null,
                    'held_by' => $actor->id,
                    'last_reminder_sent_at' => null,
                    'notes' => 'Số này bị hủy ngày '.now()->format('d/m/Y').' và chuyển thành số lùi dự trữ.'
                        .($reason ? ' Lý do: '.$reason : ''),
                ]);

                SystemLog::record(
                    $actor->id,
                    SystemLogAction::HuySoChuyenDuTru,
                    "{$actor->name} hủy số {$doc->formatted_number}, chuyển thành số lùi dự trữ.",
                    ['document_number_id' => $doc->id, 'snapshot' => $snapshot, 'reason' => $reason]
                );
            } else {
                $formatted = $doc->formatted_number;
                $docId = $doc->id;
                $doc->delete();

                SystemLog::record(
                    $actor->id,
                    SystemLogAction::HuySoThuHoi,
                    "{$actor->name} hủy số {$formatted}, số được thu hồi để cấp lại cho người xin tiếp theo.",
                    ['document_number_id' => $docId, 'snapshot' => $snapshot, 'reason' => $reason]
                );
            }
        });
    }

    /** Gửi mail an toàn: không để lỗi gửi mail làm hỏng luồng nghiệp vụ chính. */
    protected function safeSend(\Closure $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
