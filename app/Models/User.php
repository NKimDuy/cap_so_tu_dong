<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isChuyenVien(): bool
    {
        return $this->role === UserRole::ChuyenVien;
    }

    public function isQuanLy(): bool
    {
        return $this->role === UserRole::QuanLy;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Quản lý hoặc admin đều có quyền xem/duyệt như quản lý */
    public function isManagerLike(): bool
    {
        return in_array($this->role, [UserRole::QuanLy, UserRole::Admin], true);
    }

    public function documentNumbers(): HasMany
    {
        return $this->hasMany(DocumentNumber::class, 'requester_id');
    }

    public function backdateRequests(): HasMany
    {
        return $this->hasMany(BackdateRequest::class, 'requester_id');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    /** Số công văn đang "Đã cấp - Chờ trả" của chuyên viên này */
    public function outstandingNumbersCount(): int
    {
        return $this->documentNumbers()->where('status', 'da_cap')->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, UserRole|string $role)
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $query->where('role', $value);
    }
}
