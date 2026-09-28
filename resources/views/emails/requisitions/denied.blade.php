<x-mail::message>
# Requisition Denied

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** has been denied by **{{ $approver->name }}**.

<x-mail::panel>
**Reason for Denial:**
{{ $reason }}
</x-mail::panel>

If you believe this decision was made in error or you have additional information to provide, please reach out to your line manager.

<x-mail::button :url="config('app.mobile_scheme') . '://requisitions/' . $requisition->id">
    View Requisition
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
