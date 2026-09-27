<x-mail::message>
# Requisition Awaiting Your Approval

Hello {{ $approver->name }}

A requisition has been submitted and is currently waiting for your review.

**Requisition Number:** {{ $requisition->requisition_number }}
**Submitted By:** {{ $requisition->submittedBy->name }} ({{ $requisition->submittedBy->email }})
**Current Step:** {{ $requisition->current_step?->value }}
**Total Expected Amount:** ${{ number_format($requisition->total_expected_price, 2) }}

<x-mail::button :url="config('app.url') . '/requisitions/' . $requisition->id">
   Review Requisition
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
