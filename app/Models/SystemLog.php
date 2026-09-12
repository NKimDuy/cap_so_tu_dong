<?php

namespace App\Models;

use App\Enums\SystemLogAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'data',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        $case = SystemLogAction::tryFrom($this->action);

        return $case?->label() ?? $this->action;
    }

    public static function record(?int $userId, SystemLogAction $action, ?string $description = null, ?array $data = null): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action->value,
            'description' => $description,
            'data' => $data,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
