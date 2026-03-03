@php($index=1)
@forelse($module_notes_history as $key => $value)
    <div class="col-1"></div>
    <p>
        <span class="font-weight-semibold">{{ $index++ }}. Created By: </span>{{ $value->created_by }} |
        <span class="font-weight-semibold">Date & Time: </span>{{ $value->created_at }} |
    </p>
    <p>
        <span class="font-weight-semibold">Details: </span>{{ $value->details }}
    </p>
    <hr class="w-25 center">
@empty
    <div class="col-1"></div>
    <p>
        <span class="font-weight-semibold">No Notes History found. </span>
    </p>
@endforelse