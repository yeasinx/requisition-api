<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'requisition_id' => $this->requisition_id,
            'item_name'      => $this->item_name,
            'description'    => $this->description,
            'quantity'       => $this->quantity,
            'unit_price'     => (float) $this->unit_price,
            'total_price'    => (float) $this->total_price,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
