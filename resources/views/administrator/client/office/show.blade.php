@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">
             <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Head Offices</span> - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Head Offices</a>
                        <span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">Details</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Inner container -->
            <div class="d-flex align-items-start flex-column flex-md-row">
                <!-- Left content -->
                <div class="w-100 order-2 order-md-1">

                    <!-- Details grid -->
                    <div class="row">
                        <div class="col-lg-3"></div>
                        <div class="col-lg-6">
                            <div class="card border-left-3 border-left-danger rounded-left-0">
                                <div class="card-body">
                                    <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                        <div>
                                            <h6 class="font-weight-semibold">Office Name: {{ ucwords(strtolower($office->office_name)) }}</h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li class="font-weight-semibold"><strong>Contact Persons:</strong> <br>
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
                                            
                                                    @foreach($mergedArray as $index => $value)
                                                        <div class="font-weight-semibold mt-2 ml-3">
                                                            <span class="badge badge-mark border-success mr-2" style="width:5px;height:5px"></span><strong><em><u>Person - {{ $index + 1 }}</u></em></strong><br>
                                                            Name: {{ $value['name'] }}<br>
                                                            Phone: {{ $value['phone'] }}<br>
                                                            Landline: {{ $value['landline'] }}<br>
                                                            Email: <a href="mailto:{{ $value['email'] }}">{{ $value['email'] }}</a>
                                                        </div><br>
                                                    @endforeach
                                                </li>
                                                <li><strong>Website:</strong> <span class="font-weight-semibold"><a href="#">{{ $office->office_website }}</a></span></li>
                                            </ul>
                                        </div>

                                        <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                            <h6 class="font-weight-semibold">Office ID#: {{ $office->id }}</h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li class="font-weight-semibold"><strong>Type:</strong> {{ strtoupper($office->office_type) }}</li>
                                                <li><strong>Postcode:</strong> <span class="font-weight-semibold">{{$office->office_postcode}}</span></li>
                                                <li class="dropdown">
                                                    <strong>Status:</strong> &nbsp;
                                                    <span class="badge badge-success">Active</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-sm-flex justify-content-end align-items-sm-center">
                                    <a href="{{ route('offices.index') }}" class="btn bg-slate-800 legitRipple">Close</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Details grid -->

                </div>
                <!-- /left content -->

            </div>
            <!-- /inner container -->
        </div>
        <!-- /content area -->

@endsection()
