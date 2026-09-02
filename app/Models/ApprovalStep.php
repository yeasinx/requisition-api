<?php

namespace App\Models;

use App\Enums\DecisionStatus;
use App\Enums\RequisitionStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requisition_id', 'step_type', 'acted_by_user_id', 'decision', 'remarks', 'acted_at'])]
class ApprovalStep extends Model
{
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    public function casts(): array
    {
        return [
            'step_type' => RequisitionStep::class,
            'decision' => DecisionStatus::class,
            'acted_at' => 'datetime',
        ];
    }
}
