<?php

namespace Horsefly\Http\Controllers\Administrator;

//use Horsefly\Observers\UnitObserver;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Unit;
use Horsefly\Office;
use Horsefly\ModuleNote;
use Maatwebsite\Excel\Facades\Excel;
use Horsefly\Exports\UnitsEmailExport;
//use Auth;
use DB;
use Illuminate\Support\Facades\Auth;
use Redirect;
use Validator;
use Session;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:unit_list|unit_import|unit_create|unit_edit|unit_view|unit_note-create|unit_note-history', ['only' => ['index','getUnits']]);
        $this->middleware('permission:unit_import', ['only' => ['getUploadUnitCsv']]);
        $this->middleware('permission:unit_create', ['only' => ['create','store']]);
        $this->middleware('permission:unit_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:unit_view', ['only' => ['show']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $units = Office::join('units', 'offices.id', '=', 'units.head_office')
        //     ->select('units.*', 'offices.office_name')->get();
        $head_offices = Office::where("status","active")->get();
        $items = array();
        foreach($head_offices as $office){
            $items[$office->id] = $office->office_name;
        }
        return view('administrator.client.office.unit.index',compact('head_offices'));
    }
	
	public function export_email(Request $request){

        return Excel::download(new UnitsEmailExport(), 'Units_Emails.csv');

    }

    public function getUnits(Request $request)
    {
        $auth_user = Auth::user();
        $result = Office::join('units', 'offices.id', '=', 'units.head_office')
            ->select('units.*', 'offices.office_name')->orderBy('units.id', 'DESC');

        $aColumns = ['units_added_date','units_added_time','office_name','unit_name',
        'unit_postcode','contact_phone_number','contact_landline','units_notes','status'];

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
                $query->orWhere('unit_name', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('unit_postcode', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('contact_phone_number', 'LIKE', "%{$sKeywords}%");
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
        $unitData = $result->get();

        $iTotal = $iFilteredTotal;
        $output = array(
             "sEcho" => intval($request->get('sEcho')),
             "iTotalRecords" => $iTotal,
             "iTotalDisplayRecords" => $iFilteredTotal,
             "aaData" => array()
        );
        $i = 0;
        
        foreach ($unitData as $uRow) {
            $phoneArray = $uRow->contact_phone_number;
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
           
            $landlineArray = $uRow->contact_landline;
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
            $unit_new_note = ModuleNote::where(['module_noteable_id' =>$uRow->id, 'module_noteable_type' =>'Horsefly\Unit'])
            ->select('module_notes.details')
            ->orderBy('module_notes.id', 'DESC')
            ->first();

            if ($unit_new_note) {
                $unit_notes_final = $unit_new_note->details;
            } else {
                $unit_notes_final = $uRow->units_notes;
            }
			                $updated_date = date('d-m-Y', strtotime($uRow->updated_at));
            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$uRow->id}\">
                             <span></span>
                          </label>";

            if ($auth_user->hasPermissionTo('unit_note-history')) {
                $notes = '';
                $notes .= '<a href="#" class="notes_history" data-unit="' . $uRow->id . '"
                                 data-controls-modal="#notes_history' . $uRow->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#notes_history' . $uRow->id . '">History</a>';

                $notes .= '<div id="notes_history' . $uRow->id . '" class="modal fade" tabindex="-1">';
                $notes .= '<div class="modal-dialog modal-lg">';
                $notes .= '<div class="modal-content">';
                $notes .= '<div class="modal-header">';
                $notes .= '<h6 class="modal-title">Unit Notes History - <span class="font-weight-semibold">' . $uRow->unit_name . '</span></h6>';
                $notes .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $notes .= '</div>';
                $notes .= '<div class="modal-body" id="unit_notes_history' . $uRow->id . '" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $notes .= '</div>';
                $notes .= '<div class="modal-footer">';
                $notes .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $notes .= '</div>';
                $notes .= '</div>';
                $notes .= '</div>';
                $notes .= '</div>';
            }

            if($uRow->status == 'active'){
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
            if ($auth_user->hasPermissionTo('unit_edit')) {
                $action .=     "<a href=\"/units/{$uRow->id}/edit\" class=\"dropdown-item\"> Edit</a>";
            }
            if ($auth_user->hasPermissionTo('unit_view')) {
                $action .=     "<a href=\"/units/{$uRow->id}\" class=\"dropdown-item\"> View </a>";
            }
            if ($auth_user->hasPermissionTo('unit_note-create')) {
                $action .=     "<a href=\"#\" class=\"dropdown-item\"
                                               data-controls-modal=\"#add_unit_note{$uRow->id}\"
                                               data-backdrop=\"static\"
                                               data-keyboard=\"false\" data-toggle=\"modal\"
                                               data-target=\"#add_unit_note{$uRow->id}\">
                                               Add Note
                                </a >";
            }
                $action .=
                           "</div>
                        </div>
                      </div>";

            if ($auth_user->hasPermissionTo('unit_note-create')) {
                $action .=
                     "<div id=\"add_unit_note{$uRow->id}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-lg\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Add Unit Note</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                                    </div>
                                    <form action=\"{$url}\" method=\"POST\" class=\"form-horizontal\" id=\"note_form{$uRow->id}\">
                                        <input type=\"hidden\" name=\"_token\" value=\"{$csrf}\">
                                        <input type=\"hidden\" name=\"module\" value=\"Unit\">
                                        <div class=\"modal-body\">
                                            <div id=\"note_alert{$uRow->id}\"></div>
                                            <div class=\"form-group row\">
                                                <label class=\"col-form-label col-sm-3\">Details</label>
                                                <div class=\"col-sm-9\">
                                                    <input type=\"hidden\" name=\"module_key\" value=\"{$uRow->id}\">
                                                    <textarea name=\"details\" id=\"note_details{$uRow->id}\" class=\"form-control\" cols=\"30\" rows=\"4\"
                                                              placeholder=\"TYPE HERE ..\" required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class=\"modal-footer\">
                                            <button type=\"button\" class=\"btn btn-link legitRipple\" data-dismiss=\"modal\">
                                                Close
                                            </button>
                                            <button type=\"submit\" data-note_key=\"{$uRow->id}\" class=\"btn bg-teal legitRipple note_form_submit\">Save</button>
                                        </div>
                                    </form>
                            </div>
                        </div>
                      </div>";
            }

            $output['aaData'][$i] = array(
                "DT_RowId" => "row_{$uRow->id}",
            //    @$checkbox,
                @$uRow->units_added_date,
                @$updated_date,
                @$uRow->office_name,
                @$uRow->unit_name,
                @$uRow->unit_postcode,
				@$phoneNumbersString,
                @$landlineString,
                @$unit_notes_final
            );
            if ($auth_user->hasPermissionTo('unit_note-history')) { array_push($output['aaData'][$i], @$notes); }
            array_push($output['aaData'][$i], @$status);
            if ($auth_user->hasAnyPermission(['unit_edit','unit_view','unit_note-create'])) {
                array_push($output['aaData'][$i], @$action);
            }
            $i++;
        }

         echo json_encode($output);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $head_offices = Office::where("status","active")->get();
        $items = array();
        foreach($head_offices as $office){
            $items[$office->id] = $office->office_name;
        }
       
        return view('administrator.client.office.unit.create',compact('head_offices','items'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $auth_user = Auth::user()->id;

      
        // Get input arrays
        $phoneNumbers = $request->input('unit_phone_number');
        $landlineArray = $request->input('unit_landline');
        $contactNameArray = $request->input('unit_contact_name');
        $emailsArray = $request->input('unit_contact_email');

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
            'unit_phone_number' => $phoneNumbers,
            'unit_landline' => $landlineArray,
            'unit_contact_name' => $contactNameArray,
            'unit_contact_email' => $emailsArray,
        ]);

        $validator = Validator::make($request->all(), [
            'unit_contact_name.*' => 'required|string',
            'unit_phone_number.*' => 'required|string',
            'unit_landline.*' => 'required|string',
            'unit_contact_email.*' => 'required|email',
            'unit_postcode' => "unique:units",
        ]);
        
        // Handle validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

         // Retrieve the array of phone numbers from the request
         $phoneNumbers = $request->input('unit_phone_number');
         $landlineArray = $request->input('unit_landline');
         $contactNameArray = $request->input('unit_contact_name');
         $emailsArray = $request->input('unit_contact_email');
 
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

        $unit = new Unit();
        $unit->user_id = $auth_user;
        $unit->unit_name = $request->input('unit_name');
        $unit->unit_postcode = $request->input('unit_postcode');
        $unit->head_office = $request->input('select_head_office');
        $unit->contact_name = $contactNameString;
        $unit->contact_phone_number = $phoneNumbersString;
        $unit->contact_landline = $landlineString;
        $unit->contact_email = $emailString;
        $unit->website = $request->input('unit_website');
        $unit->units_notes = $request->input('units_notes');
        $unit->units_added_date = date("jS F Y");
        $unit->units_added_time = date("h:i A");
        $unit->save();
        $last_inserted_unit = $unit->id;
        if($last_inserted_unit){
            $unit_uid = md5($last_inserted_unit);
            DB::table('units')->where('id', $last_inserted_unit)->update(['unit_uid' => $unit_uid]);
            return redirect('units')->with('unit_success_msg', 'Unit has been Added Successfully');
        }
        else{
            return redirect('units.create')->with('unit_add_error', 'WHOOPS! Unit Could not Added');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $unit = Unit::find($id);
        $head_office_id = $unit->head_office;
        $head_office_name = Office::select("office_name")->where("id",$head_office_id)->first();
//        echo '<pre>';print_r($head_office_name);exit;
        return view('administrator.client.office.unit.show',compact('unit','head_office_name'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $unit = Unit::find($id);
        //$head_office = Office::where("status","active")->get();
        $head_office = Office::all();
        $units = Office::join('units', 'offices.id', '=', 'units.head_office')
            ->select('units.*', 'offices.office_name')->get();
        return view('administrator.client.office.unit.edit',compact('unit','head_office'));
    }

     public function update(Request $request, $id)
    {
        // Get input arrays
        $phoneNumbers = $request->input('contact_phone');
        $landlineArray = $request->input('unit_landline');
        $contactNameArray = $request->input('contact_name');
        $emailsArray = $request->input('contact_email');

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
            'unit_phone_number' => $phoneNumbers,
            'unit_landline' => $landlineArray,
            'unit_contact_name' => $contactNameArray,
            'unit_contact_email' => $emailsArray,
        ]);

        $validator = Validator::make($request->all(), [
            'unit_contact_name.*' => 'required|string',
            'unit_phone_number.*' => 'required|string',
            'unit_landline.*' => 'required|string',
            'unit_contact_email.*' => 'required|email',
           // 'unit_postcode' => 'required|unique:units,unit_postcode,'.$id,
        ]);
        
        // Handle validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

         // Retrieve the array of phone numbers from the request
         $phoneNumbers = $request->input('contact_phone');
         $landlineArray = $request->input('unit_landline');
         $contactNameArray = $request->input('contact_name');
         $emailsArray = $request->input('contact_email');
 
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
        $unit = Unit::find($id);
        $unit->user_id = $auth_user;
        $unit->unit_name = $request->get('unit_name');
        $unit->unit_postcode = $request->get('unit_postcode');
        $unit->head_office = $request->get('select_head_office');
        $unit->contact_name = $contactNameString;
        $unit->contact_phone_number = $phoneNumbersString;
        $unit->contact_landline = $landlineString;
        $unit->contact_email = $emailString;
        $unit->website = $request->get('contact_website');
        $unit->update();

        return redirect('units')->with('updateUnitSuccessMsg', 'Unit has been updated');
    }

    public function destroy($id)
    {
        $unit = Unit::find($id);
        $status = $unit->status;
        if($status == 'active'){
            if(DB::table('units')->where('id',$id)->update(['status' => 'disable'])){
                return redirect('units')->with('unitDeleteSuccessMsg', 'Unit has been disabled Successfully');
            }
            else{
                return redirect('units')->with('unitDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }
        }
        elseif($status == 'disable'){
            if(DB::table('units')->where('id',$id)->update(['status' => 'active'])){
                return redirect('units')->with('unitDeleteSuccessMsg', 'Unit has been enabled Successfully');
            }
            else{
                return redirect('units')->with('unitDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        }
    }
	
    public function getAllOfficeUnits($office_id){
        $units_list = Unit::where(["head_office"=> $office_id, "status" => "active"])->get();
        $office_name_obj = Office::find($office_id);
        $office_name = $office_name_obj->office_name;
        return view('administrator.sale.open.office_unit_list',compact('units_list','office_name'));
    }
	
    public function getUploadUnitCsv(Request $request)
    {
        if ($request->file('unit_csv') != null ){

            $file = $request->file('unit_csv');

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
					//                    $audit_data = [];
					//                    $index = 1;

										// disable Unit model events
					//                    $dispatcher = Unit::getEventDispatcher();
					//                    Unit::unsetEventDispatcher();

                    $office = Office::find($request->input('office'));

                    foreach($importData_arr as $importData){

                        $postcode = $importData[1];
                    //    $data_arr = $this->geocode($postcode);
                        $latitude = 00.000000;
                        $longitude = 00.000000;
                    //    if ($data_arr) {
                     //       $latitude = $data_arr[0];
                     //       $longitude = $data_arr[1];
                     //   }
                        $auth_user = Auth::user()->id;
                        $unit = new Unit();
                        $unit->user_id = $auth_user;
						//                        $unit->head_office = $request->input('office');
                        $unit->head_office = $office->id;
                        $unit->unit_name = $importData[0];
                        $unit->unit_postcode = $postcode;
                        $unit->contact_name = $importData[2];
                        $unit->contact_phone_number = $importData[3];
                        $unit->contact_landline = $importData[4];
                        $unit->contact_email = $importData[5];
                        $unit->website = $importData[6];
                        $unit->units_notes = $importData[7];
                        $unit->units_added_date = date("jS F Y");
                        $unit->units_added_time = date("h:i A");
                        $unit->lat = $latitude;
                        $unit->lng = $longitude;
                        $unit->save();

                        // csv data for each office
						//                        $csv_data  =  'Office Name: ' . $office->office_name . ' | ';
						//                        $csv_data .=  'Unit Name: ' . $unit->unit_name . ' | ';
						//                        $csv_data .=  'Postcode: ' . $unit->unit_postcode . ' | ';
						//                        $csv_data .=  'Contact Name: ' . $unit->contact_name . ' | ';
						//                        $csv_data .=  'Email: ' . $unit->contact_email . ' | ';
						//                        $csv_data .=  'Phone: ' . $unit->contact_phone_number . ' | ';
						//                        $csv_data .=  'Added Date: ' . $unit->units_added_date . ' | ';
						//                        $csv_data .=  'Added time: ' . $unit->units_added_time;
						//                        $audit_data[$index++] = $csv_data;
                    }
                    // enable Unit model events
					//                    Unit::setEventDispatcher($dispatcher);

                    /*** activity log
                    $unit_observer = new UnitObserver();
                    $unit_observer->csvAudit($audit_data);
                     */

                    Session::flash('message','Import Successful.');
                }else{
                    Session::flash('message','File too large. File must be less than 2MB.');
                }

            }else{
                Session::flash('message','Invalid File Extension.');
            }
        }
        return redirect('units')->with('applicant_success_msg', 'Applicant Added Successfully');
    }
	
	public function getUploadEmailCsv(Request $request)
{
    $request->validate([
        'email_csv' => 'required|file|mimes:csv,txt|max:2048'
    ]);

    $file = $request->file('email_csv');
    $emails = [];

    if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
        // Read the headers
        $header = fgetcsv($handle, 1000, ',');

        // Convert headers to lowercase and trim whitespace
        $header = array_map('strtolower', $header);

        // Get the indices of the columns 'primary email', 'secondary email', and 'tertiary email'
        $email1Index = array_search('primary email', $header);
        $email2Index = array_search('secondary email', $header);
        $email3Index = array_search('tertiary email', $header);

        // Check if all columns exist
        if ($email1Index === false && $email2Index === false && $email3Index === false) {
            return response()->json(['error' => 'Columns "primary email", "secondary email", and "tertiary email" not found in the CSV file.'], 422);
        }

        // Read each line of the CSV file
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            // Initialize an array to store valid email addresses for each row
            $validEmails = [];

            // Check and collect valid emails from each column
            if ($email1Index !== false && isset($data[$email1Index])) {
                $email1 = preg_replace('/\s+/', '', $data[$email1Index]);
                if (!empty($email1)) {
                    $validEmails[] = $email1;
                }
            }
            if ($email2Index !== false && isset($data[$email2Index])) {
                $email2 = preg_replace('/\s+/', '', $data[$email2Index]);
                if (!empty($email2)) {
                    $validEmails[] = $email2;
                }
            }
            if ($email3Index !== false && isset($data[$email3Index])) {
                $email3 = preg_replace('/\s+/', '', $data[$email3Index]);
                if (!empty($email3)) {
                    $validEmails[] = $email3;
                }
            }

            // Merge $validEmails array into $emails array
            $emails = array_merge($emails, $validEmails);
        }

        fclose($handle);
    }

    // Remove duplicates and ensure the data is UTF-8 encoded
    $emails = array_unique($emails);
    $emails = array_map('utf8_encode', $emails);

    // Implode the unique email addresses with comma and space
    $emailsString = implode(',', $emails);

    return response()->json(['success' => true, 'emailsString' => $emailsString]);
}

	
	//    AJAX FUNCTIONS
    public function getAjaxUnitListing(Request $request){
        $office_id = $request->Input('office_id');
        $all_units = Unit::select("id","unit_name")->where(["head_office"=> $office_id, "status" => "active"])->get();
        $units_array = array();
        foreach($all_units as $key => $unit){
            $units_array[$key]['id'] = $unit->id;
            $units_array[$key]['unit_name'] = $unit->unit_name;
        }
		//        foreach($all_units as $unit){
		//
		//            $units_array[] = $unit->id;
		//            $units_array[] = $unit->unit_name;
		//
		//        }
        return response()->json($units_array);
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
