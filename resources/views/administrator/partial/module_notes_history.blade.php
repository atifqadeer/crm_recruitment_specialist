@php($index=1)
@foreach($module_notes_history as $key => $value)
    <div class="p-2">
        <p>
            <span class="font-weight-semibold">{{ $index++ }}. Created by: </span>{{ $value->username }}
            <span class="font-weight-semibold"> - Created at: </span>{{ date('d M,Y h:i:s A',strtotime($value->created_at)) }}
			<span class="badge {{ $value->status == 'active' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($value->status) }}</span>
        </p>
        <p class="pl-3">
            <span class="font-weight-semibold">Note Details: </span><br>{!! $value->note !!}
        </p>
        <hr class="w-50 center">
    </div>
@endforeach
