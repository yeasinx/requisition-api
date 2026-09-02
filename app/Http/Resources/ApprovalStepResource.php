<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requisition_id' => $this->requisition_id,
            'step_type' => $this->step_type->value, // Extracts the string value from the Enum
            'acted_by' => [
                'id' => $this->actedBy->id,
                'name' => $this->actedBy->name,
                'email' => $this->actedBy->email,
            ],
            'decision' => $this->decision?->value, // Null if pending, otherwise extracts Enum string
            'remarks' => $this->remarks,
            'acted_at' => $this->acted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
