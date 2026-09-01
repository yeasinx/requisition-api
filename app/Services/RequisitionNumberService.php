<?php

namespace App\Services;

use App\Models\Requisition;

class RequisitionNumberService
{
   public function generate(): string
   {
       $year = now()->year;
       $prefix = "REQ-{$year}-";

       $count = Requisition::withTrashed()
           ->where('requisition_number', 'like', "{$prefix}%")
           ->count();

       $nextNumber = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

       return $prefix . $nextNumber;
   }
}
