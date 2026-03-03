@php($index=1)
@foreach($module_notes_history as $key => $value)
    <div class="col-1"></div>
    
    <p>
        <span class="font-weight-semibold">{{ $index++ }}. Created by: </span>{{ $value->name }}
    </p>
    <p>
        <span class="font-weight-semibold">Details: </span>{{ $value->details }}
    </p>
    <hr class="w-25 center">
@endforeach
