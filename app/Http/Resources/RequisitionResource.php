<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'requisition_number'   => $this->requisition_number,
            'submitted_by'         => [
                'id'          => $this->submitter->id,
                'name'        => $this->submitter->name,
                'email'       => $this->submitter->email,
                'employee_id' => $this->submitter->employee_id,
                'designation' => $this->submitter->designation,
            ],
            'current_step'         => $this->current_step?->value,
            'status'               => $this->status->value,
            'total_expected_price' => (float) $this->total_expected_price,

            // Conditional loading to prevent N+1 query issues
            'items'                => RequisitionItemResource::collection($this->whenLoaded('items')),
            'approvals'            => ApprovalStepResource::collection($this->whenLoaded('approvals')),

            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
