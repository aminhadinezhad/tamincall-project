<?php

namespace App\Models;

use App\Enums\NoPurchaseReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['call_id', 'user_id', 'answered', 'purchased', 'no_purchase_reason', 'agent_satisfaction', 'overall_satisfaction', 'notes'])]
class FollowUp extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'answered' => 'boolean',
            'purchased' => 'boolean',
            'no_purchase_reason' => NoPurchaseReason::class,
            'agent_satisfaction' => 'integer',
            'overall_satisfaction' => 'integer',
        ];
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
