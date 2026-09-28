<x-mail::message>

@if($isFullyApproved)
# 🎉 Requisition Fully Approved — Ready for Collection!

Hello {{ $requisition->submittedBy->name }},

Great news! Your requisition **{{ $requisition->requisition_number }}** has completed all approval stages and is now **fully approved**.

Your requested items are ready for collection. Please coordinate with the HR department to arrange pickup or delivery.

@elseif($requisition->current_step?->value === 'ACCOUNTS')
# Requisition Approved — Payment Being Processed

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** has been approved by the Business Controller and forwarded to the **Accounts department for payment processing**.

You will receive another update once the payment has been processed and your items are ready.

@elseif($requisition->current_step?->value === 'HR_ADMIN')
# Requisition Update — Almost There!

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** payment has been processed by the Accounts department. The **HR team is now completing the final steps** — your items are nearly ready for collection.

@else
# Requisition Step Approved

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** has been approved by **{{ $approver->name }}** and is progressing through the approval chain.

**Current Status:** In Progress — awaiting next review ({{ $requisition->current_step?->value }})

@endif

@if(!empty($remarks))
**Approver Remarks:**
> {{ $remarks }}

@endif

<x-mail::button :url="config('app.mobile_scheme') . '://requisitions/' . $requisition->id">
    View Requisition
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
