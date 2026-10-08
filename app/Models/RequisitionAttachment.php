<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['requisition_id', 'uploaded_by_user_id', 'original_name', 'path', 'mime_type', 'size'])]
class RequisitionAttachment extends Model
{
    use HasFactory;

    /** Disk holding attachment files; private, served only through the authorized download route. */
    public const DISK = 'local';

    /** Accepted extensions: PDF, Word, Excel. */
    public const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx';

    /** Max size per file in kilobytes (10 MB). */
    public const MAX_SIZE_KB = 10240;

    /** Max attachments per requisition. */
    public const MAX_PER_REQUISITION = 5;

    protected static function booted(): void
    {
        // Remove the stored file together with its record
        static::deleted(function (RequisitionAttachment $attachment) {
            Storage::disk(self::DISK)->delete($attachment->path);
        });
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function uploadedBy(): BelongsTo
    {
        // Deleted uploaders still own their attachments.
        return $this->belongsTo(User::class, 'uploaded_by_user_id')->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
