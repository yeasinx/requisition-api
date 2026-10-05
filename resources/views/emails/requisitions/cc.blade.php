<x-mail::message>
@if($event === \App\Mail\RequisitionCcMail::APPROVED)
# Requisition Fully Approved
@elseif($event === \App\Mail\RequisitionCcMail::DENIED)
# Requisition Denied
@else
# New Requisition Submitted
@endif

Hello {{ $contact->name }},

@if($event === \App\Mail\RequisitionCcMail::APPROVED)
Requisition **{{ $requisition->requisition_number }}**, on which you were copied, has completed all approval steps and is now **fully approved**.
@elseif($event === \App\Mail\RequisitionCcMail::DENIED)
Requisition **{{ $requisition->requisition_number }}**, on which you were copied, has been **denied**{{ $actor ? ' by ' . $actor->name : '' }}.
@else
**{{ $requisition->submittedBy->name }}** has submitted requisition **{{ $requisition->requisition_number }}** and copied you for your information.
@endif

<x-mail::panel>
**Requisition Number:** {{ $requisition->requisition_number }}<br>
**Submitted By:** {{ $requisition->submittedBy->name }}{{ $requisition->submittedBy->designation ? ' (' . $requisition->submittedBy->designation . ')' : '' }}<br>
**Total Expected Amount:** ৳{{ number_format($requisition->total_expected_price, 2) }}
</x-mail::panel>

@if($event === \App\Mail\RequisitionCcMail::DENIED && $remarks)
**Reason for Denial:**
{{ $remarks }}
@endif

<x-mail::table>
| Item | Qty | Unit Price | Total |
| :--- | :-: | ---------: | ----: |
@foreach($requisition->items as $item)
| {{ $item->item_name }} | {{ $item->quantity }} | ৳{{ number_format($item->unit_price, 2) }} | ৳{{ number_format($item->total_price, 2) }} |
@endforeach
</x-mail::table>

You are receiving this email because you were added in CC. No action is required from you.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
