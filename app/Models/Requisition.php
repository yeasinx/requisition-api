<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'description', 'amount', 'status', 'submitted_by_user_id'])]
class Requisition extends Model
{
    use HasFactory;

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RequisitionStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
