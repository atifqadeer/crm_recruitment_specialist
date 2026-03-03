@php($index=1)
<?php
//echo '<pre>';
//print_r($audit_data);
//echo '</pre>';
//?>

@if (!empty($audit_data))

    @foreach ($audit_data as $key_1 => $val_1)

        <p class="font-weight-bold pl-3"><span class="font-weight-semibold">{{ $index++ }}. Updated By: </span>{{ $val_1['changes_made_by'] }}</p>

        @foreach ($val_1['changes_made'] as $key_2 => $val_2)
            @if (in_array($key_2, ['applicant_added_date','applicant_added_time','lat','lng','applicant_user_id'])) @continue @endif
            <p class="pl-3"><span class="font-weight-semibold">{{ str_replace('_', ' ', $key_2) }}: </span>{{ $val_2 }}</p>
        @endforeach
        <hr class="w-25 center">

    @endforeach

@else
    <h6 class="font-weight-semibold pl-3">No updated data to show.</h6>
@endif
