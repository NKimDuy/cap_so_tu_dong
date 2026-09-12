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

    protected function assertUnderLimit(User $user): void
    {
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
     */
    public function issueNormal(User $requester, array $data): DocumentNumber
    {
        $doc = DB::transaction(function () use ($requester, $data) {
            $this->assertUnderLimit($requester);

            $year = now()->year;
            $number = $this->nextNumber($year);

            $doc = DocumentNumber::create([
                'year' => $year,
                'number' => $number,
                'status' => DocumentNumberStatus::DaCap->value,
                'is_backdate' => false,
                'requester_id' => $requester->id,
                'content' => $data['content'],
                'signer' => $data['signer'],
                'request_date' => now()->toDateString(),
                'record_date' => now()->toDateString(),
                'expected_return_date' => $data['expected_return_date'],
            ]);

            SystemLog::record(
                $requester->id,
                SystemLogAction::XinSoBinhThuong,
                "{$requester->name} xin số {$doc->formatted_number}.",
                ['document_number_id' => $doc->id, 'number' => $doc->number, 'year' => $doc->year]
            );

            return $doc;
        });

        $this->safeSend(fn () => Mail::to($requester->email)->send(new NumberIssuedMail($doc)));

        foreach ($this->managers() as $manager) {
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
            throw new BusinessRuleException('Ngày xin số lùi phải là một ngày trong quá khứ.');
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
                'record_date' => now()->toDateString(),
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
     * Quản lý duyệt một yêu cầu xin số lùi bằng cách gán một số trong kho dự trữ.
     */
    public function approveBackdateRequest(User $manager, BackdateRequest $req, DocumentNumber $poolNumber): DocumentNumber
    {
        $req->loadMissing('requester');

        $doc = DB::transaction(function () use ($manager, $req, $poolNumber) {
            $poolNumber->refresh();
            $req->refresh();

            if ($poolNumber->status !== DocumentNumberStatus::SoLuiDuTru) {
                throw new BusinessRuleException('Số này không còn ở trong kho số lùi dự trữ.');
            }

            if ($req->status !== BackdateRequestStatus::DangCho) {
                throw new BusinessRuleException('Yêu cầu này đã được xử lý trước đó.');
            }

            if ($this->countOutstanding($req->requester) >= $this->maxPending()) {
                throw new BusinessRuleException(
                    "Chuyên viên {$req->requester->name} hiện đã có đủ {$this->maxPending()} số chưa trả, chưa thể cấp thêm số."
                );
            }

            $poolNumber->update([
                'requester_id' => $req->requester_id,
                'content' => $req->content,
                'signer' => $req->signer,
                'request_date' => $req->requested_date,
                'record_date' => now()->toDateString(),
                'expected_return_date' => $req->expected_return_date,
                'status' => DocumentNumberStatus::DaCap->value,
                'is_backdate' => true,
                'backdate_request_id' => $req->id,
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
        });

        $this->safeSend(fn () => Mail::to($req->requester->email)->send(new NumberIssuedMail($doc)));

        return $doc;
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

            $hasLaterNumber = DocumentNumber::where('year', $doc->year)
                ->where('number', '>', $doc->number)
                ->exists();

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
