<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['requisition_id', 'step_type', 'acted_by_user_id', 'decision', 'remarks', 'acted_at'])]
class ApprovalStep extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalStepFactory> */
    use HasFactory;
}
