@php($index=1)

@if (!empty($audit_data))

    @foreach ($audit_data as $key_1 => $val_1)

        <p class="font-weight-bold pl-3"><span class="font-weight-semibold">{{ $index++ }}. Updated By: </span>{{ $val_1['changes_made_by'] }}</p>

        @foreach ($val_1['changes_made'] as $key_2 => $val_2)
            <p class="pl-3"><span class="font-weight-semibold">{{ ucwords(str_replace('_', ' ', $key_2)) }}: </span>{{ $val_2 }}</p>
        @endforeach
        <hr class="w-25 center">

    @endforeach

@else
    <h6 class="font-weight-semibold pl-3">No updated data to show.</h6>
    <hr class="w-25 center">
@endif
@if($original_sale)
    <h6 class="font-weight-semibold pl-3">Original Sale</h6>
    <div class="row">
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Created By: </span>{{ $original_sale->user->name }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Created At: </span>{{ $original_sale->data['sale_added_date'] }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Job Type: </span>{{ $original_sale->data['job_type'] }}</p>
        </div>

        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Office: </span>{{ @\Horsefly\Office::find($original_sale->data['head_office'])->office_name }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Unit: </span>{{ @\Horsefly\Unit::find($original_sale->data['head_office_unit'])->unit_name }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Postcode: </span>{{ $original_sale->data['postcode'] }}</p>
        </div>

        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Category: </span>{{ $original_sale->data['job_category'] }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Title: </span>{{ $original_sale->data['job_title'] }}</p>
        </div>
        <div class="col-md-4">
            <p class="pl-3"><span class="font-weight-semibold">Experience: </span>{{ $original_sale->data['experience'] }}</p>
        </div>

        <div class="col-md-8">
            <p class="pl-3"><span class="font-weight-semibold">Timing: </span>{{ $original_sale->data['timing'] }}</p>
        </div>
        @if(isset($original_sale->data['send_cv_limit']))
            <div class="col-md-4">
                <p class="pl-3"><span class="font-weight-semibold">Send CV Limit: </span>{{ $original_sale->data['send_cv_limit'] }}</p>
            </div>
        @endif

        <div class="col-md-12">
            <p class="pl-3"><span class="font-weight-semibold">Salary: </span>{{ $original_sale->data['salary'] }}</p>
        </div>
        <div class="col-md-12">
            <p class="pl-3"><span class="font-weight-semibold">Qualification: </span>{{ $original_sale->data['qualification'] }}</p>
        </div>
        <div class="col-md-12">
            <p class="pl-3"><span class="font-weight-semibold">Benefits: </span>{{ $original_sale->data['benefits'] }}</p>
        </div>

    </div>
@endif