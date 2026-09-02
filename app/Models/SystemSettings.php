<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['first_approver_user_id', 'second_approver_user_id', 'business_controller_user_id', 'accounts_approver_user_id', 'hr_admin_approver_user_id', 'updated_by_user_id'])]
class SystemSettings extends Model
{
   public function firstApprover(): BelongsTo
   {
       return $this->belongsTo(User::class, 'first_approver_user_id');
   }
   public function secondApprover(): BelongsTo
   {
       return $this->belongsTo(User::class, 'second_approver_user_id');
   }
   public function businessController(): BelongsTo
   {
       return $this->belongsTo(User::class, 'business_controller_user_id');
   }
   public function accountsApprover(): BelongsTo
   {
       return $this->belongsTo(User::class, 'accounts_approver_user_id');
   }
   public function hrAdminApprover(): BelongsTo
   {
       return $this->belongsTo(User::class, 'hr_admin_approver_user_id');
   }
   public function updatedBy(): BelongsTo
   {
       return $this->belongsTo(User::class, 'updated_by_user_id');
   }

   public static function get(): self
   {
       return self::firstOrFail();
   }
}
