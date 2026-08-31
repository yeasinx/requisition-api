<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['requisition_number', 'submitted_by_user_id', 'current_step', 'status', 'total_expected_price'])]
class Requisition extends Model
{
    use HasFactory, SoftDeletes;

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function casts(): array
    {
        return [
            'current_step' => RequisitionStep::class,
            'status' => RequisitionStatus::class,
            'total_expected_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
