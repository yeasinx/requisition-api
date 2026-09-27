<x-mail::message>
# Requisition Denied

Hello {{ $requisition->submittedBy->name }},

Your requisition **{{ $requisition->requisition_number }}** has been denied by **{{ $approver->name }}**.

<x-mail::panel>
    **Reason for Denial:**
    {{ $reason }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/requisitions/' . $requisition->id">
    View Requisition
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
