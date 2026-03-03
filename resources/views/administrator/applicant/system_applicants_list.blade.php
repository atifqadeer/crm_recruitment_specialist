@extends('layouts.app')

@section('content')
    <div class="dashboard-wrapper">
        <div class="container-fluid  dashboard-content">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="page-header">
                        <h2 class="pageheader-title">System Applicants</h2>
                        <div class="page-breadcrumb">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">System Applicants</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Listing</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">All System Applicants </h5>
                            <p>System Applicants With Their Credentials</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example3" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Job Title</th>
                                        <th>Postcode</th>
                                        <th>Email Address</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>Tiger Nixon</td>
                                        <td>System Architect</td>
                                        <td>Edinburgh</td>
                                        <td>2011/04/25</td>
                                        <td>$320,800</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Garrett Winters</td>
                                        <td>Accountant</td>
                                        <td>Tokyo</td>
                                        <td>2011/07/25</td>
                                        <td>$170,750</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Ashton Cox</td>
                                        <td>Junior Technical Author</td>
                                        <td>San Francisco</td>
                                        <td>66</td>
                                        <td>2009/01/12</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>Senior Javascript Developer</td>
                                        <td>Edinburgh</td>
                                        <td>2012/03/29</td>
                                        <td>$433,060</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Airi Satou</td>
                                        <td>Accountant</td>
                                        <td>Tokyo</td>
                                        <td>2008/11/28</td>
                                        <td>$162,700</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Brielle Williamson</td>
                                        <td>Integration Specialist</td>
                                        <td>New York</td>
                                        <td>61</td>
                                        <td>2012/12/02</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Herrod Chandler</td>
                                        <td>Sales Assistant</td>
                                        <td>San Francisco</td>
                                        <td>59</td>
                                        <td>$137,500</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Rhona Davidson</td>
                                        <td>Integration Specialist</td>
                                        <td>Tokyo</td>
                                        <td>55</td>
                                        <td>$327,900</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Colleen Hurst</td>
                                        <td>Javascript Developer</td>
                                        <td>San Francisco</td>
                                        <td>39</td>
                                        <td>$205,500</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Sonya Frost</td>
                                        <td>Software Engineer</td>
                                        <td>Edinburgh</td>
                                        <td>23</td>
                                        <td>$103,600</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Jena Gaines</td>
                                        <td>Office Manager</td>
                                        <td>London</td>
                                        <td>30</td>
                                        <td>$90,560</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Quinn Flynn</td>
                                        <td>Support Lead</td>
                                        <td>Edinburgh</td>
                                        <td>22</td>
                                        <td>$342,000</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Charde Marshall</td>
                                        <td>Regional Director</td>
                                        <td>San Francisco</td>
                                        <td>2008/10/16</td>
                                        <td>$470,600</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Haley Kennedy</td>
                                        <td>Senior Marketing Designer</td>
                                        <td>London</td>
                                        <td>43</td>
                                        <td>$313,500</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Tatyana Fitzpatrick</td>
                                        <td>Regional Director</td>
                                        <td>London</td>
                                        <td>2010/03/17</td>
                                        <td>$385,750</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Michael Silva</td>
                                        <td>Marketing Designer</td>
                                        <td>London</td>
                                        <td>66</td>
                                        <td>$198,500</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Paul Byrd</td>
                                        <td>Chief Financial Officer (CFO)</td>
                                        <td>New York</td>
                                        <td>64</td>
                                        <td>2010/06/09</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Gloria Little</td>
                                        <td>Systems Administrator</td>
                                        <td>New York</td>
                                        <td>59</td>
                                        <td>2009/04/10</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Bradley Greer</td>
                                        <td>Software Engineer</td>
                                        <td>London</td>
                                        <td>41</td>
                                        <td>2012/10/13</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Dai Rios</td>
                                        <td>Personnel Lead</td>
                                        <td>Edinburgh</td>
                                        <td>35</td>
                                        <td>2012/09/26</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Jenette Caldwell</td>
                                        <td>Development Lead</td>
                                        <td>New York</td>
                                        <td>30</td>
                                        <td>2011/09/03</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Yuri Berry</td>
                                        <td>Chief Marketing Officer (CMO)</td>
                                        <td>New York</td>
                                        <td>40</td>
                                        <td>2009/06/25</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Caesar Vance</td>
                                        <td>Pre-Sales Support</td>
                                        <td>New York</td>
                                        <td>21</td>
                                        <td>2011/12/12</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Doris Wilder</td>
                                        <td>Sales Assistant</td>
                                        <td>Sidney</td>
                                        <td>23</td>
                                        <td>2010/09/20</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Angelica Ramos</td>
                                        <td>Chief Executive Officer (CEO)</td>
                                        <td>London</td>
                                        <td>47</td>
                                        <td>2009/10/09</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Gavin Joyce</td>
                                        <td>Developer</td>
                                        <td>Edinburgh</td>
                                        <td>42</td>
                                        <td>2010/12/22</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Jennifer Chang</td>
                                        <td>Regional Director</td>
                                        <td>Singapore</td>
                                        <td>28</td>
                                        <td>2010/11/14</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Brenden Wagner</td>
                                        <td>Software Engineer</td>
                                        <td>San Francisco</td>
                                        <td>28</td>
                                        <td>2011/06/07</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Fiona Green</td>
                                        <td>Chief Operating Officer (COO)</td>
                                        <td>San Francisco</td>
                                        <td>48</td>
                                        <td>2010/03/11</td>
                                        <td>$850,000</td>
                                        <td><a href="#" class="btn btn-primary"><i class="fa fa-eye"></i></a> |
                                            <a href="#" class="btn btn-danger"><i class="fa fa-trash-alt"></i></a>
                                            | <a href="{{ route('applicants.edit','1') }}" class="btn btn-success"><i class="fa fa-pencil-alt"></i></a></td>
                                    </tr>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <th>Name</th>
                                        <th>Job Title</th>
                                        <th>Postcode</th>
                                        <th>Email Address</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->
                <!-- end data table multiselects  -->
                <!-- ============================================================== -->
            </div>
        </div>
        
    </div>
@endsection()