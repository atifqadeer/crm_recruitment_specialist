@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
{{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
             <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="#"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Head Offices</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Head Offices</a>
                        <span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">Update</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Edit Office</h5>
                                        <a href="{{ route('offices.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(array('route'=>['offices.update',$office->id],'method'=>'PATCH')) }}
                                    <div class="form-group">
                                        {{ Form::label('head_office_text','Head Office Name',array('class'=>'col-form-label')) }}
                                        {{ Form::text('head_office_name',$office->office_name,array('id'=>'head_office_id','class'=>'form-control')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('postcode','Postcode') }}
                                        {{ Form::text('office_postcode',$office->office_postcode,array('id'=>'postcode_id','class'=>'form-control')) }}
                                    </div>
                                    <div class="form-group">
                                    {{ Form::label('email_address','Choose Type',array('class'=>'col-form-label')) }}
                                        <select name="office_type" id="office_type_id" class="form-control form-control-select2">
                                            <option value="psl" @if($office->office_type === 'psl') selected='selected' @endif>PSL</option>
                                            <option value="non psl" @if($office->office_type === 'non psl') selected='selected' @endif>Non PSL</option>
                                        </select>
                                    </div>
                                    @php
                                        $phoneArray = $office->office_contact_phone;
                                        $landlineArray = $office->office_contact_landline;
                                        $emailArray = $office->office_email;
                                        $nameArray = $office->office_contact_name;
                                    
                                        $emails = array_filter(explode(',', $emailArray));
                                        $phones = array_filter(explode(',', $phoneArray));
                                        $landlines = array_filter(explode(',', $landlineArray));
                                        $names = array_filter(explode(',', $nameArray));

                                        $mergedArray = [];
                                    
                                        $maxLength = max(count($emails), count($phones), count($landlines), count($names));
                                    
                                        for ($i = 0; $i < $maxLength; $i++) {
                                            $email = $emails[$i] ?? '';
                                            $phone = $phones[$i] ?? '';
                                            $landline = $landlines[$i] ?? '';
                                            $name = $names[$i] ?? '';
                                    
                                            if ($email || $phone || $landline || $name) {
                                                $mergedArray[] = [
                                                    'email' => $email,
                                                    'phone' => $phone,
                                                    'landline' => $landline,
                                                    'name' => $name
                                                ];
                                            }
                                        }
                                    @endphp
                                
                                    <div class="container px-0">
                                        <div id="card-container">
                                            @foreach($mergedArray as $index => $value)
                                                <div class="card p-4 card-instance" id="contact-card-{{ $index }}">
                                                    <h6 class="contact-person-label">Contact Person {{ $index + 1 }} <small>(All fields are required)</small></h6>
                                                    <div class="form-group">
                                                        {{ Form::label('name', 'Name') }}
                                                        {{ Form::text('office_contact_name[]', $value['name'], ['id' => 'name_id_' . $index, 'class' => 'form-control', 'placeholder' => 'ENTER NAME', 'required' => 'required']) }}
                                                    </div>
                                                    <div class="form-group">
                                                        {{ Form::label('phnumber', 'Phone Number') }}
                                                        {{ Form::text('office_contact_phone[]', $value['phone'], ['id' => 'phone_number_id_' . $index, 'class' => 'form-control', 'placeholder' => 'ENTER PHONE NUMBER', 'required' => 'required']) }}
                                                    </div>
                                                    <div class="form-group">
                                                        {{ Form::label('mobile', 'Landline Number') }}
                                                        {{ Form::text('office_landline[]', $value['landline'], ['id' => 'home_number_id_' . $index, 'class' => 'form-control', 'placeholder' => 'ENTER OFFICE LANDLINE NUMBER', 'required' => 'required']) }}
                                                    </div>
                                                    <div class="form-group">
                                                        {{ Form::label('email', 'Email Address') }}
                                                        {{ Form::email('office_contact_email[]', $value['email'], ['id' => 'email_address_id_' . $index, 'class' => 'form-control', 'placeholder' => 'ENTER EMAIL ADDRESS', 'required' => 'required']) }}
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-danger mb-3 remove-card-button">
                                                            <i class="icon-bin"></i>&nbsp;Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    
                                        <div>
                                            <button type="button" class="btn btn-primary mb-3 float-right" id="add-new-card">
                                                <i class="icon-plus-circle2"></i>&nbsp;Add Person
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('weburl','Website') }}
                                        {{ Form::text('office_website',$office->office_website,array('id'=>'website_id','class'=>'form-control')) }}
                                    </div>

                                    <div class="text-right">
                                        {{ Form::button('Save <i class="icon-paperplane ml-2"></i>',
                                        ['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
                                    </div>
                                    {{ Form::close() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
        <!-- /content area -->
<script>
        document.addEventListener('DOMContentLoaded', function() {
    let cardIndex = document.querySelectorAll('.card-instance').length + 1; // Initialize card index
    const maxCards = 5; // Maximum number of cards allowed

    function createCard() {
        const cardTemplate = document.querySelector('.card-instance').cloneNode(true);
        cardTemplate.id = `contact-card-${cardIndex}`;
        cardTemplate.classList.remove('d-none');
        cardTemplate.classList.add('card-instance');

        // Update card labels with indexing
        const label = cardTemplate.querySelector('h6');
        if (label) {
            label.innerHTML = `Contact Person ${cardIndex} <small>(All fields are required)</small>`;
        }

        // Update form element names and set them as required
        const inputs = cardTemplate.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            let name = input.getAttribute('name');
            if (name) {
                if (!name.endsWith('[]')) {
                    input.setAttribute('name', name + '[]');
                }
                input.value = '';
                input.required = true; // Set all fields as required
            }
            let id = input.getAttribute('id');
            if (id) {
                input.setAttribute('id', id + '_' + cardIndex);
            }
        });

        // Add event listener to the remove button
        cardTemplate.querySelector('.remove-card-button').addEventListener('click', function() {
            cardTemplate.remove();
            updateCardIndices();
            updateButtonVisibility();
        });

        cardIndex++;
        return cardTemplate;
    }

    // Update card indices
    function updateCardIndices() {
        const cards = document.querySelectorAll('.card-instance');
        cards.forEach((card, index) => {
            const label = card.querySelector('h6');
            if (label) {
                label.innerHTML = `Contact Person ${index + 1} <small>(All fields are required)</small>`;
            }
        });
        cardIndex = cards.length + 1;
    }

    function updateButtonVisibility() {
        const cards = document.querySelectorAll('.card-instance');
        const addNewButton = document.getElementById('add-new-card');

        cards.forEach((card, index) => {
            const removeButton = card.querySelector('.remove-card-button');
            if (cards.length > 1) {
                removeButton.style.display = 'inline-block'; // Show Remove button on all cards if more than one card exists
            } else {
                removeButton.style.display = 'none'; // Hide Remove button if only one card exists
            }
        });

        addNewButton.style.display = cards.length >= maxCards ? 'none' : 'inline-block';
    }

    document.getElementById('add-new-card').addEventListener('click', function() {
        const container = document.getElementById('card-container');
        const currentCardCount = container.querySelectorAll('.card-instance').length;

        if (currentCardCount < maxCards) {
            container.appendChild(createCard());
            this.parentNode.appendChild(this);
            updateButtonVisibility();
        } else {
            alert('Maximum number of cards reached.');
        }
    });

    // Add event listener to existing remove buttons
    document.querySelectorAll('.remove-card-button').forEach(button => {
        button.addEventListener('click', function() {
            const card = button.closest('.card-instance');
            card.remove();
            updateCardIndices();
            updateButtonVisibility();
        });
    });

    // Initialize with at least one card if none exist
    if (document.querySelectorAll('.card-instance').length === 0) {
        document.getElementById('card-container').appendChild(createCard());
    }

    // Initial update of button visibility
    updateButtonVisibility();
});


            </script>
@endsection()
