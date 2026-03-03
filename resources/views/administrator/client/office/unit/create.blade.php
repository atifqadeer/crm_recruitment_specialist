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
                        <a href="{{ route('applicants.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Units</span> - Add
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
        				<a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Units</a>
                        <span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">Add</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->

            @if(session()->has('unit_add_error'))
                <div class="alert alert-danger">
                    {{ session()->get('unit_add_error') }}
                </div>
            @endif
        <!-- For Validation Errors  -->
            <!-- ============================================================== -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        <button type="button" class="btn btn-danger"
                                data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#myModal"
                                style="background-color: #007bff;border: none;">Replace With Note</button>
                        <!-- Modal -->
                        <div class="modal fade" tabindex="-1" id="myModal" role="dialog">
                            <div class="modal-dialog">

                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Write A Note For Unit</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Note Title</label>
                                            <input type="text" id="note_title_id" class="form-control" placeholder="NOTE TITLE">
                                        </div>
                                        <div class="form-group">
                                            <label>Write a Note</label>
                                            <textarea  id="duplicate_note_for_applicants_id" cols="30"
                                                       rows="5" class="form-control"
                                                       placeholder="WRITE A NOTE FOR DUPLICATE APPLICANTS HERE..."></textarea>
                                        </div>
                                        <input type="button" id="duplicate_note_id" class="btn btn-primary btn-block" value="Save"
                                               style="background-color: #007bff;">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- Modal End -->
                    </ul>
                </div>
        @endif
        <!-- End Validation Errors  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Add Unit</h5>
                                        <a href="{{ route('units.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(array('route'=>'units.store','method' =>'POST')) }}
                                    <div class="form-group">
                                        {{ Form::label('email_address','Choose Head Office',array('class'=>'col-form-label')) }}
                                        {{ Form::select('select_head_office',['' => 'Select Head Office']+$items,null, array('class'=>'form-control form-control-select2','id'=>'select_head_office_id')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('head_office_text','Unit Name',array('class'=>'col-form-label')) }}
                                        {{ Form::text('unit_name',null,array('id'=>'unit_name_id','class'=>'form-control','placeholder' => 'ENTER UNIT NAME')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('postcode','Postcode') }}
                                        {{ Form::text('unit_postcode',null,array('id'=>'postcode_id','class'=>'form-control','placeholder' => 'ENTER POSTCODE')) }}
                                    </div>
                                  <div class="container px-0">
                                        <div class="card p-4 d-none" id="contact-card-template">
                                            <h6 class="contact-person-label">Contact Person <small>(All fields are required)</small></h6>
                                            <div class="form-group">
                                                {{ Form::label('name','Name') }}
                                                {{ Form::text('unit_contact_name[]',null,array('id'=>'name_id','class'=>'form-control','placeholder' => 'ENTER NAME')) }}
                                            </div>
                                            <div class="form-group">
                                                {{ Form::label('phnumber','Phone Number') }}
                                                {{ Form::text('unit_phone_number[]',null,array('id'=>'phone_number_id','class'=>'form-control','placeholder' =>'ENTER PHONE NUMBER')) }}
                                            </div>
                                            <div class="form-group">
                                                {{ Form::label('mobile','Landline Number') }}
                                                {{ Form::text('unit_landline[]',null,array('id'=>'home_number_id','class'=>'form-control','placeholder' => 'ENTER OFFICE LANDLINE NUMBER')) }}
                                            </div>
                                            <div class="form-group">
                                                {{ Form::label('email','Email Address') }}
                                                {{ Form::email('unit_contact_email[]',null,array('id'=>'email_address_id','class'=>'form-control','placeholder' => 'ENTER EMAIL ADDRESS')) }}
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-danger mb-3 remove-card-button">
                                                    <i class="icon-bin"></i>&nbsp;Remove
                                                </button>
                                            </div>
                                        </div>
                                        <div id="card-container">
                                            <!-- Existing cards will be here -->
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-primary mb-3 float-right" id="add-new-card">
                                                <i class="icon-plus-circle2"></i>&nbsp;Add Person
                                            </button>
                                        </div>
                                    </div>   
                                    <div class="form-group">
                                        {{ Form::label('weburl','Website') }}
                                        {{ Form::text('unit_website',null,array('id'=>'website_id','class'=>'form-control','placeholder' => 'ENTER WEBSITE ADDRESS')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('units_notes', 'Add Notes:') }}
                                        {{ Form::textarea('units_notes',null,
                                        array('class'=>'form-control form-input-styled','rows' => '7','cols' => '20')) }}
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button>
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
                </div>
            </div>

        <!-- /content area -->
<script>
            document.addEventListener('DOMContentLoaded', function() {
            let cardIndex = 1; // Initialize card index
            const maxCards = 5; // Maximum number of cards allowed
        
            function createCard() {
                const cardTemplate = document.getElementById('contact-card-template');
                const newCard = cardTemplate.cloneNode(true);
                newCard.classList.remove('d-none'); // Ensure the card is visible
                newCard.classList.add('card-instance'); // Add class to identify it
        
                // Update card labels with indexing
                const label = newCard.querySelector('h6');
                if (label) {
                    label.innerHTML = `Contact Person ${cardIndex} <small>(All fields are required)</small>`;
                }
        
                // Update form element names and set them as required
                const inputs = newCard.querySelectorAll('input, select');
                inputs.forEach(function(input) {
                    let name = input.getAttribute('name');
                    if (name) {
                        // Add array brackets to the name attribute if not already present
                        if (!name.endsWith('[]')) {
                            input.setAttribute('name', name + '[]');
                        }
                        input.value = ''; // Ensure fields are empty initially
                        input.required = true; // Set all fields as required
                    }
                });
        
                // Add event listener to the remove button
                newCard.querySelector('.remove-card-button').addEventListener('click', function() {
                    newCard.remove();
                    updateCardIndices(); // Update indices after removal
        
                    // Ensure at least one card remains
                    if (document.querySelectorAll('.card-instance').length === 0) {
                        document.getElementById('card-container').appendChild(createCard());
                    }
        
                    // Adjust button visibility
                    updateButtonVisibility();
                });
        
                cardIndex++; // Increment the card index
                return newCard;
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
                cardIndex = cards.length + 1; // Update the card index for new cards
            }
        
            function getFormData() {
                const names = Array.from(document.querySelectorAll('input[name="unit_contact_name[]"]'))
                    .map(input => input.value.trim()).filter(value => value !== "");
                const phones = Array.from(document.querySelectorAll('input[name="unit_phone_number[]"]'))
                    .map(input => input.value.trim()).filter(value => value !== "");
                const landlines = Array.from(document.querySelectorAll('input[name="unit_landline[]"]'))
                    .map(input => input.value.trim()).filter(value => value !== "");
                const emails = Array.from(document.querySelectorAll('input[name="unit_contact_email[]"]'))
                    .map(input => input.value.trim()).filter(value => value !== "");
        
                return {
                    unit_contact_name: names,
                    unit_phone_number: phones,
                    unit_landline: landlines,
                    unit_contact_email: emails
                };
            }
        
            // Update button visibility
            function updateButtonVisibility() {
                const cards = document.querySelectorAll('.card-instance');
                const addNewButton = document.getElementById('add-new-card');
                
                if (cards.length === 1) {
                    cards[0].querySelector('.remove-card-button').style.display = 'none'; // Hide Remove button on the first card
                } else {
                    cards.forEach((card, index) => {
                        const removeButton = card.querySelector('.remove-card-button');
                        if (index === 0) {
                            removeButton.style.display = 'none'; // Hide Remove button on the first card
                        } else {
                            removeButton.style.display = 'inline-block'; // Show Remove button on other cards
                        }
                    });
                }
        
                if (cards.length >= maxCards) {
                    addNewButton.style.display = 'none'; // Hide Add button if max cards reached
                } else {
                    addNewButton.style.display = 'inline-block'; // Show Add button if below max
                }
            }
        
            // Add event listener to the "Add New" button
            document.getElementById('add-new-card').addEventListener('click', function() {
                const container = document.getElementById('card-container');
                const currentCardCount = container.querySelectorAll('.card-instance').length;
        
                if (currentCardCount < maxCards) {
                    container.appendChild(createCard());
                    // Move the "Add New" button to the end of the container
                    this.parentNode.appendChild(this);
        
                    // Update button visibility
                    updateButtonVisibility();
                } else {
                    alert('Maximum number of cards reached.');
                }
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
