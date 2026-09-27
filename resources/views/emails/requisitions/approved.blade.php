<x-mail::message>
# Requisition Update: {{ $isFullyApproved ? 'Fully Approved' : 'Step Approved' }}

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** has been approved by **{{ $approver->name }}**.

@if($isFullyApproved)
   **Status:** Your requisition is now fully approved!
@else
    **Status:** In progress. It has now moved to the next approval layer ({{ $requisition->current_step?->value }}).
@endif

@if(!empty($remarks))
   **Approver Remarks:**
   > {{ $remarks }}
@endif

<x-mail::button :url="config('app.url') . '/requisitions/' . $requisition->id">
   View Requisition
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
