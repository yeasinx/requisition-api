<?php

namespace App\Http\Requests;

use App\Models\RequisitionAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1', 'max:'.RequisitionAttachment::MAX_PER_REQUISITION],
            'attachments.*' => self::fileRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.required' => 'At least one attachment is required.',
            ...self::fileMessages(),
        ];
    }

    /**
     * Rules for a single attachment file, shared with StoreRequisitionRequest.
     *
     * @return array<int, string>
     */
    public static function fileRules(): array
    {
        return [
            'file',
            'mimes:'.RequisitionAttachment::ALLOWED_MIMES,
            'max:'.RequisitionAttachment::MAX_SIZE_KB,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fileMessages(): array
    {
        return [
            'attachments.max' => 'A requisition can have at most '.RequisitionAttachment::MAX_PER_REQUISITION.' attachments.',
            'attachments.*.mimes' => 'Attachments must be PDF, Word (doc, docx) or Excel (xls, xlsx) files.',
            'attachments.*.max' => 'Each attachment may not be larger than 10 MB.',
        ];
    }
}
