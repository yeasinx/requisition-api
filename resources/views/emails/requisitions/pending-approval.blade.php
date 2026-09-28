<x-mail::message>

@if($requisition->current_step?->value === 'ACCOUNTS')
# Action Required: Process Payment Release

Hello {{ $approver->name }},

Requisition **{{ $requisition->requisition_number }}** has been reviewed and **approved by the Business Controller**. It has now been forwarded to your department for **payment processing**.

**Submitted By:** {{ $requisition->submittedBy->name }} ({{ $requisition->submittedBy->email }})
**Requisition Number:** {{ $requisition->requisition_number }}
**Total Amount:** ${{ number_format($requisition->total_expected_price, 2) }}

Please review the requisition details and process the payment release at your earliest convenience.

<x-mail::button :url="config('app.mobile_scheme') . '://requisitions/' . $requisition->id">
    Review & Process Payment
</x-mail::button>

@elseif($requisition->current_step?->value === 'HR_ADMIN')
# Action Required: Collect Payment / Take Further Action

Hello {{ $approver->name }},

The **Accounts department has processed the payment** for requisition **{{ $requisition->requisition_number }}**. This requisition is now in your hands for **final verification and collection**.

**Submitted By:** {{ $requisition->submittedBy->name }} ({{ $requisition->submittedBy->email }})
**Requisition Number:** {{ $requisition->requisition_number }}
**Total Amount:** ${{ number_format($requisition->total_expected_price, 2) }}

Please review and arrange for collection or take any further necessary action to complete this requisition.

<x-mail::button :url="config('app.mobile_scheme') . '://requisitions/' . $requisition->id">
    Review & Complete
</x-mail::button>

@else
# Requisition Awaiting Your Approval

Hello {{ $approver->name }},

A requisition has been submitted and is currently waiting for your review.

**Requisition Number:** {{ $requisition->requisition_number }}
**Submitted By:** {{ $requisition->submittedBy->name }} ({{ $requisition->submittedBy->email }})
**Current Step:** {{ $requisition->current_step?->value }}
**Total Expected Amount:** ${{ number_format($requisition->total_expected_price, 2) }}

<x-mail::button :url="config('app.mobile_scheme') . '://requisitions/' . $requisition->id">
    Review Requisition
</x-mail::button>

@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
