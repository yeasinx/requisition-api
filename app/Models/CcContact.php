<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A person (not necessarily a system user) who can be CC'd on requisition emails.
 */
#[Fillable(['name', 'designation', 'email'])]
class CcContact extends Model
{
    use SoftDeletes;

    public function requisitions(): BelongsToMany
    {
        return $this->belongsToMany(Requisition::class);
    }
}
