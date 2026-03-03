<?php

namespace Horsefly\Http\Controllers\Administrator;

//use Horsefly\Observers\OfficeObserver;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Horsefly\Exports\HeadOfficesExportIncorrectPostcode;
use Horsefly\Office;
use Horsefly\ModuleNote;
use Illuminate\Validation\Rule;

use DB;
use Illuminate\Support\Facades\Auth;
use Redirect;
use Validator;
use Session;

class OfficeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:office_list|office_import|office_create|office_edit|office_view|office_note-history|office_note-create', ['only' => ['index','getOffices']]);
        $this->middleware('permission:office_create', ['only' => ['create','store']]);
        $this->middleware('permission:office_import', ['only' => ['getUploadOfficeCsv']]);
        $this->middleware('permission:office_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:office_view', ['only' => ['show']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$offices = Office::where("status","active")->get();
        //$offices = Office::all();
        return view('administrator.client.office.index');
    }

    public function getOffices(Request $request)
    {
        $auth_user = Auth::user();
        $result = Office::orderBy('id', 'DESC');

        $aColumns = ['office_added_date','office_added_time','office_name','office_postcode','office_type',
        'office_contact_phone','office_contact_landline','office_notes','status'];

        $iStart = $request->get('iDisplayStart');
        $iPageSize = $request->get('iDisplayLength');

        $order = 'id';
        $sort = ' DESC';

        if ($request->get('iSortCol_0')) { //iSortingCols
      
            $sOrder = "ORDER BY  ";

            for ($i = 0; $i < intval($request->get('iSortingCols')); $i++) {
                if ($request->get('bSortable_' . intval($request->get('iSortCol_' . $i))) == "true") {
                    $sOrder .= $aColumns[intval($request->get('iSortCol_' . $i))] . " " . $request->get('sSortDir_' . $i) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                 $sOrder = " id ASC";
            }

            $OrderArray = explode(' ', $sOrder);
            $order = trim($OrderArray[3]);
            $sort = trim($OrderArray[4]);

        }

        $sKeywords = $request->get('sSearch');
        if ($sKeywords != "") {

            $result->Where(function($query) use ($sKeywords) {
                $query->orWhere('office_name', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('office_postcode', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('office_type', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('office_contact_phone', 'LIKE', "%{$sKeywords}%");
            });
        }

        for ($i = 0; $i < count($aColumns); $i++) {
            $request->get('sSearch_' . $i);
            if ($request->get('bSearchable_' . $i) == "true" && $request->get('sSearch_' . $i) != '') {
                 $result->orWhere($aColumns[$i], 'LIKE', "%" . $request->orWhere('sSearch_' . $i) . "%");
            }
        }

        $iFilteredTotal = $result->count();

        if ($iStart != null && $iPageSize != '-1') {
            $result->skip($iStart)->take($iPageSize);
        }

        $result->orderBy($order, trim($sort));
        $result->limit($request->get('iDisplayLength'));
        $officeData = $result->get();

        $iTotal = $iFilteredTotal;
        $output = array(
             "sEcho" => intval($request->get('sEcho')),
             "iTotalRecords" => $iTotal,
             "iTotalDisplayRecords" => $iFilteredTotal,
             "aaData" => array()
        );
        $i = 0;
        
        foreach ($officeData as $oRow) {
 			$phoneArray = $oRow->office_contact_phone;
            // Convert to array if it's a comma-separated string
            if (!is_array($phoneArray)) {
                $phoneArray = explode(',', $phoneArray);
            }

            // Get only the first element of the array
            if (!empty($phoneArray)) {
                $phoneNumbersString = $phoneArray[0];
            } else {
                $phoneNumbersString = ''; // Handle the case where $phoneArray is empty
            }
           
            $landlineArray = $oRow->office_contact_landline;
            // Convert to array if it's a comma-separated string
            if (!is_array($landlineArray)) {
                $landlineArray = explode(',', $landlineArray);
            }

            // Get only the first element of the array
            if (!empty($landlineArray)) {
                $landlineString = $landlineArray[0];
            } else {
                $landlineString = ''; // Handle the case where $phoneArray is empty
            }
    
            $office_new_note = ModuleNote::where(['module_noteable_id' =>$oRow->id, 'module_noteable_type' =>'Horsefly\Office'])
            ->select('module_notes.details')
            ->orderBy('module_notes.id', 'DESC')
            ->first();

            if ($office_new_note) {
                $office_notes_final = $office_new_note->details;

            } else {
                $office_notes_final = $oRow->office_notes;
            }

            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$oRow->id}\">
                             <span></span>
                          </label>";
            if ($auth_user->hasPermissionTo('office_note-history')) {
                $notes = '';
                $notes .= '<a href="#" class="notes_history" data-office="' . $oRow->id . '"
                                 data-controls-modal="#notes_history' . $oRow->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#notes_history' . $oRow->id . '">History</a>';

                $notes .= '<div id="notes_history' . $oRow->id . '" class="modal fade" tabindex="-1">';
                $notes .= '<div class="modal-dialog modal-lg">';
                $notes .= '<div class="modal-content">';
                $notes .= '<div class="modal-header">';
                $notes .= '<h6 class="modal-title">Office Notes History - <span class="font-weight-semibold">' . $oRow->office_name . '</span></h6>';
                $notes .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $notes .= '</div>';
                $notes .= '<div class="modal-body" id="office_notes_history' . $oRow->id . '" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $notes .= '</div>';
                $notes .= '<div class="modal-footer">';
                $notes .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $notes .= '</div>';
                $notes .= '</div>';
                $notes .= '</div>';
                $notes .= '</div>';
            }

            $office_type = strtoupper($oRow->office_type);
            // $job_category = strtoupper($oRow->job_category);

            if($oRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Disable</span></h5>';
            }

            $url = route('module_note.store');
            $csrf = csrf_token();

            $action = "<div class=\"list-icons\">
                        <div class=\"dropdown\">
                            <a href=\"#\" class=\"list-icons-item\" data-toggle=\"dropdown\">
                                <i class=\"icon-menu9\"></i>
                            </a>
                            <div class=\"dropdown-menu dropdown-menu-right\">";
            if ($auth_user->hasPermissionTo('office_edit')) {
                $action .=     "<a href=\"/offices/{$oRow->id}/edit\" class=\"dropdown-item\"> Edit</a>";
            }
            if ($auth_user->hasPermissionTo('office_view')) {
                $action .=     "<a href=\"/offices/{$oRow->id}\" class=\"dropdown-item\"> View </a>";
            }
            if ($auth_user->hasPermissionTo('office_note-create')) {
                $action .=
                    "<a href=\"#\" class=\"dropdown-item\"
                                               data-controls-modal=\"#add_office_note{$oRow->id}\"
                                               data-backdrop=\"static\"
                                               data-keyboard=\"false\" data-toggle=\"modal\"
                                               data-target=\"#add_office_note{$oRow->id}\">
                                               Add Note
                                </a >
                            </div>
                        </div>
                      </div>
                      <div id=\"add_office_note{$oRow->id}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-lg\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Add Head Office Note</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                                    </div>
                                    <form action=\"{$url}\" method=\"POST\" class=\"form-horizontal\" id=\"note_form{$oRow->id}\">
                                        <input type=\"hidden\" name=\"_token\" value=\"{$csrf}\">
                                        <input type=\"hidden\" name=\"module\" value=\"Office\">
                                        <div class=\"modal-body\">
                                            <div id=\"note_alert{$oRow->id}\"></div>
                                            <div class=\"form-group row\">
                                                <label class=\"col-form-label col-sm-3\">Details</label>
                                                <div class=\"col-sm-9\">
                                                    <input type=\"hidden\" name=\"module_key\" value=\"{$oRow->id}\">
                                                    <textarea name=\"details\" id=\"note_details{$oRow->id}\" class=\"form-control\" cols=\"30\" rows=\"4\"
                                                              placeholder=\"TYPE HERE ..\" required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class=\"modal-footer\">
                                            <button type=\"button\" class=\"btn btn-link legitRipple\" data-dismiss=\"modal\">
                                                Close
                                            </button>
                                            <button type=\"submit\" data-note_key=\"{$oRow->id}\" class=\"btn bg-teal legitRipple note_form_submit\">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                      </div>";
            }
            $output['aaData'][$i] = array(
                "DT_RowId" => "row_{$oRow->id}",
            //    @$checkbox,
                @$oRow->office_added_date,
                @$oRow->office_added_time,
                @$oRow->office_name,
                @$oRow->office_postcode,
                @$office_type,
                @$phoneNumbersString,
                @$landlineString,
                @$office_notes_final
            );
            if ($auth_user->hasPermissionTo('office_note-history')) { array_push($output['aaData'][$i], @$notes); }
            array_push($output['aaData'][$i], @$status);
            if ($auth_user->hasAnyPermission(['office_edit','office_view','office_note-create'])) {
                array_push($output['aaData'][$i], @$action);
            }
            $i++;
        }
         echo json_encode($output);
    }

    public function create()
    {
        return view('administrator.client.office.create');
    }
	
	public function exportHeadOfficesWithNoLatLng(Request $request)
    {
        return Excel::download(new HeadOfficesExportIncorrectPostcode(), 'headoffice_incorrect_postcodes.csv');
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $auth_user = Auth::user()->id;

       // Get input arrays
        $phoneNumbers = $request->input('office_contact_phone');
        $landlineArray = $request->input('office_landline');
        $contactNameArray = $request->input('office_contact_name');
        $emailsArray = $request->input('office_contact_email');

        // Filter out null or empty values
        $phoneNumbers = array_filter($phoneNumbers, fn($value) => !is_null($value) && $value !== '');
        $landlineArray = array_filter($landlineArray, fn($value) => !is_null($value) && $value !== '');
        $contactNameArray = array_filter($contactNameArray, fn($value) => !is_null($value) && $value !== '');
        $emailsArray = array_filter($emailsArray, fn($value) => !is_null($value) && $value !== '');

        // Reindex arrays to avoid gaps
        $phoneNumbers = array_values($phoneNumbers);
        $landlineArray = array_values($landlineArray);
        $contactNameArray = array_values($contactNameArray);
        $emailsArray = array_values($emailsArray);

        // Prepare cleaned input arrays
        $request->merge([
            'office_contact_phone' => $phoneNumbers,
            'office_landline' => $landlineArray,
            'office_contact_name' => $contactNameArray,
            'office_contact_email' => $emailsArray,
        ]);

        $validator = Validator::make($request->all(), [
            'office_contact_name.*' => 'required|string',
            'office_contact_phone.*' => 'required|string',
            'office_landline.*' => 'required|string',
            'office_contact_email.*' => 'required|email',
            'office_postcode' => 'required|unique:offices',
        ]);
        
        // Handle validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $postcode = $request->input('office_postcode');
        $data_arr = $this->geocode($postcode);
        $latitude = 00.000000;
        $longitude = 00.000000;
        if ($data_arr) {
            $latitude = $data_arr[0];
            $longitude = $data_arr[1];
        }
		
		// Retrieve the array of phone numbers from the request
        $phoneNumbers = $request->input('office_contact_phone');
        $landlineArray = $request->input('office_landline');
        $contactNameArray = $request->input('office_contact_name');
        $emailsArray = $request->input('office_contact_email');

         // Convert the array to a comma-separated string
         if (is_array($phoneNumbers) && count($phoneNumbers) > 1) {
            $phoneNumbersString = implode(',', $phoneNumbers);
        } else {
            $phoneNumbersString = is_array($phoneNumbers) ? $phoneNumbers[0] : $phoneNumbers;
        }

        if (is_array($landlineArray) && count($landlineArray) > 1) {
            $landlineString = implode(',', $landlineArray);
        } else {
            $landlineString = is_array($landlineArray) ? $landlineArray[0] : $landlineArray;
        }
        
        if (is_array($contactNameArray) && count($contactNameArray) > 1) {
            $contactNameString = implode(',', $contactNameArray);
        } else {
            $contactNameString = is_array($contactNameArray) ? $contactNameArray[0] : $contactNameArray;
        }
       
        if (is_array($emailsArray) && count($emailsArray) > 1) {
            $emailString = implode(',', $emailsArray);
        } else {
            $emailString = is_array($emailsArray) ? $emailsArray[0] : $emailsArray;
        }

        $office = new Office();
        $office->user_id = $auth_user;
        $office->office_name = $request->input('head_office_name');
        $office->office_postcode = $postcode;
        $office->office_type = $request->input('office_type');
		$office->office_contact_name = $contactNameString;
        $office->office_contact_phone = $phoneNumbersString;
        $office->office_contact_landline = $landlineString;
        $office->office_email = $emailString;
        $office->office_website = $request->input('office_contact_website');
        $office->office_notes = $request->input('office_notes');
        $office->office_added_date = date("jS F Y");
        $office->office_added_time = date("h:i A");
        $office->lat = $latitude;
        $office->lng = $longitude;
        $office->save();
        $last_inserted_office = $office->id;
        if($last_inserted_office){
            $office_uid = md5($last_inserted_office);
            DB::table('offices')->where('id', $last_inserted_office)->update(['office_uid' => $office_uid]);
            return redirect('offices')->with('head_office_success_msg', 'Head Office Added Successfully');
        }
        else{
            return redirect('offices.create')->with('office_add_error', 'WHOOPS! Head Office Could not Added');
        }
    }

    public function show($id)
    {
        $office = Office::find($id);
        return view('administrator.client.office.show',compact('office'));
    }

    public function edit($id)
    {
        $office = Office::find($id);
        //$office_types = Office::select("office_type")->where("status","active")->get();
        $office_types = Office::select("office_type")->get();
        return view('administrator.client.office.edit',compact('office','office_types'));
    }

    public function update(Request $request, $id)
    {
		// Get input arrays
        $phoneNumbers = $request->input('office_contact_phone');
        $landlineArray = $request->input('office_landline');
        $contactNameArray = $request->input('office_contact_name');
        $emailsArray = $request->input('office_contact_email');

        // Filter out null or empty values
        $phoneNumbers = array_filter($phoneNumbers, fn($value) => !is_null($value) && $value !== '');
        $landlineArray = array_filter($landlineArray, fn($value) => !is_null($value) && $value !== '');
        $contactNameArray = array_filter($contactNameArray, fn($value) => !is_null($value) && $value !== '');
        $emailsArray = array_filter($emailsArray, fn($value) => !is_null($value) && $value !== '');

        // Reindex arrays to avoid gaps
        $phoneNumbers = array_values($phoneNumbers);
        $landlineArray = array_values($landlineArray);
        $contactNameArray = array_values($contactNameArray);
        $emailsArray = array_values($emailsArray);

        // Prepare cleaned input arrays
        $request->merge([
            'office_contact_phone' => $phoneNumbers,
            'office_landline' => $landlineArray,
            'office_contact_name' => $contactNameArray,
            'office_contact_email' => $emailsArray,
        ]);

        $validator = Validator::make($request->all(), [
            'office_contact_name.*' => 'required|string',
            'office_contact_phone.*' => 'required|string',
            'office_landline.*' => 'required|string',
            'office_contact_email.*' => 'required|email',
			'office_postcode' => [
				'required',
				Rule::unique('offices')->ignore($id, 'id'), // Assuming 'id' is the primary key column
			],
        ]);
        
        // Handle validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
		
        $latitude = 00.000000;
        $longitude = 00.000000;
		
		// Retrieve the array of phone numbers from the request
		$phoneNumbers = $request->input('office_contact_phone');
		$landlineArray = $request->input('office_landline');
		$contactNameArray = $request->input('office_contact_name');
		$emailsArray = $request->input('office_contact_email');

		// Convert the array to a comma-separated string
		if (is_array($phoneNumbers) && count($phoneNumbers) > 1) {
			$phoneNumbersString = implode(',', $phoneNumbers);
		} else {
			$phoneNumbersString = is_array($phoneNumbers) ? $phoneNumbers[0] : $phoneNumbers;
		}

		if (is_array($landlineArray) && count($landlineArray) > 1) {
			$landlineString = implode(',', $landlineArray);
		} else {
			$landlineString = is_array($landlineArray) ? $landlineArray[0] : $landlineArray;
		}

		if (is_array($contactNameArray) && count($contactNameArray) > 1) {
			$contactNameString = implode(',', $contactNameArray);
		} else {
			$contactNameString = is_array($contactNameArray) ? $contactNameArray[0] : $contactNameArray;
		}

		if (is_array($emailsArray) && count($emailsArray) > 1) {
			$emailString = implode(',', $emailsArray);
		} else {
			$emailString = is_array($emailsArray) ? $emailsArray[0] : $emailsArray;
		}
		
        $auth_user = Auth::user()->id;
        $office = Office::find($id);
		 if ($request->get('office_postcode') != null && $office->office_postcode != $request->get('office_postcode')) {
            $postcode = $request->input('office_postcode');
            $data_arr = $this->geocode($postcode);
            if ($data_arr) {
                $latitude = $data_arr[0];
                $longitude = $data_arr[1];
            }
        }
        $office->office_name = $request->get('head_office_name');
        $office->office_type = $request->get('office_type');
        $office->office_postcode = $request->get('office_postcode');
        $office->office_contact_name = $contactNameString;
        $office->office_contact_phone = $phoneNumbersString;
        $office->office_contact_landline = $landlineString;
        $office->office_email = $emailString;
        $office->office_website = $request->get('office_website');
        $office->lat = $latitude;
        $office->lng = $longitude;
        $office->update();

        return redirect('offices')->with('updateOfficeSuccessMsg', 'Head Office has been updated');
    }

    public function destroy($id)
    {
        $office = Office::find($id);
        $status = $office->status;
        if($status == 'active'){
            if(DB::table('offices')->where('id',$id)->update(['status' => 'disable'])){
                return redirect('offices')->with('OfficeDeleteSuccessMsg', 'Head Office has been disabled Successfully');
            }
            else{
                return redirect('offices')->with('OfficeDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        }
        elseif($status == 'disable'){
            if(DB::table('offices')->where('id',$id)->update(['status' => 'active'])){
                return redirect('offices')->with('OfficeDeleteSuccessMsg', 'Head Office has been enabled Successfully');
            }
            else{
                return redirect('offices')->with('OfficeDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        }
    }

    public function getUploadOfficeCsv(Request $request){
        date_default_timezone_set('Europe/London');
        if ($request->file('office_csv') != null ){

            $file = $request->file('office_csv');

            // File Details
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 2MB in Bytes
            $maxFileSize = 2097152;

            // Check file extension
            if(in_array(strtolower($extension),$valid_extension)){

                // Check file size
                if($fileSize <= $maxFileSize){

                    // File upload location
                    $location = 'uploads';

                    // Upload file
                    $file->move($location,$filename);

                    // Import CSV to Database
                    $filepath = public_path($location."/".$filename);

                    // Reading file
                    $file = fopen($filepath,"r");

                    $importData_arr = array();
                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata );

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if($i == 0){
                            $i++;
                            continue;
                        }
                        for ($c=0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata [$c];
                        }
                        $i++;
                    }
                    fclose($file);
//                    echo '<pre>';print_r($importData_arr);exit;
//                    $audit_data = [];
//                    $index = 1;

                    // disable Office model events
//                    $dispatcher = Office::getEventDispatcher();
//                    Office::unsetEventDispatcher();

                    foreach($importData_arr as $importData){

                        $postcode = $importData[1];
                        $data_arr = $this->geocode($postcode);
                        $latitude = 00.000000;
                        $longitude = 00.000000;
                        if ($data_arr) {
                            $latitude = $data_arr[0];
                            $longitude = $data_arr[1];
                        }
                        $office = new Office();
                        $office->user_id = Auth::user()->id;
                        $office->office_name = $importData[0];
                        $office->office_postcode = $postcode;
                        $office->office_type = $importData[2];
                        $office->office_contact_name = $importData[3];
                        $office->office_contact_phone = $importData[4];
                        $office->office_contact_landline = $importData[5];
                        $office->office_email = $importData[6];
                        $office->office_website = $importData[7];
                        $office->office_notes = $importData[8];
                        $office->office_added_date = date("jS F Y");
                        $office->office_added_time = date("h:i A");
                        $office->lat = $latitude;
                        $office->lng = $longitude;
                        $office->save();

                        // csv data for each office
//                        $csv_data =   'Name: ' . $office->office_name . ' | ';
//                        $csv_data .=  'Postcode: ' . $office->office_postcode . ' | ';
//                        $csv_data .=  'Type: ' . $office->office_type . ' | ';
//                        $csv_data .=  'Contact Name: ' . $office->office_contact_name . ' | ';
//                        $csv_data .=  'Email: ' . $office->office_email . ' | ';
//                        $csv_data .=  'Added Date: ' . $office->office_added_date . ' | ';
//                        $csv_data .=  'Added time: ' . $office->office_added_time;
//                        $audit_data[$index++] = $csv_data;
                    }
                    // enable Office model events
//                    Office::setEventDispatcher($dispatcher);

//                    $office_observer = new OfficeObserver();
//                    $office_observer->csvAudit($audit_data);
                    Session::flash('message','Import Successful.');
                }else{
                    Session::flash('message','File too large. File must be less than 2MB.');
                }

            }else{
                Session::flash('message','Invalid File Extension.');
            }
        }
        return redirect('offices')->with('applicant_success_msg', 'Applicant Added Successfully');
    }
//WITHIN CONTROLLER FUNCTION DEFINITION
    function geocode($address)
    {

        $address = urlencode($address);

//        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=AIzaSyBPx06p1VPBhS_qz-dw7t0rYkoMbKeoNBM";
        $postcode_api = config('app.postcode_api');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$postcode_api}";

        $resp_json = file_get_contents($url);

        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if ($resp['status'] == 'OK') {

            // get the important data
            $lati = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
            $longi = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";


            // verify if data is complete
            if ($lati && $longi) {

                // put the data in the array
                $data_arr = array();

                array_push(
                    $data_arr,
                    $lati,
                    $longi
                );

                return $data_arr;

            } else {
                return false;
            }

        } else {
            echo "<strong>ERROR: {$resp['status']}</strong>";
            return false;
        }
    }
}
