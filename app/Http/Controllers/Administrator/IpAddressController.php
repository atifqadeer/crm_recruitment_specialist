<?php

namespace Horsefly\Http\Controllers\Administrator;

use Carbon\Carbon;
use Horsefly\Applicant;
use Horsefly\IpAddress;
use Horsefly\Observers\IpAddressObserver;
use Horsefly\User;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IpAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
//        $this->middleware('is_admin');
        $this->middleware('permission:ip-address_list|ip-address_create|ip-address_edit|ip-address_enable-disable|ip-address_delete', ['only' => ['index','ipAddresses']]);
        $this->middleware('permission:ip-address_create', ['only' => ['create','store']]);
        $this->middleware('permission:ip-address_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:ip-address_enable-disable', ['only' => ['ipAddressStatus']]);
        $this->middleware('permission:ip-address_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('administrator.ip_address.index');
    }

    public function create()
    {
        return view('administrator.ip_address.create');
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|unique:ip_addresses,ip_address'
        ])->validate();

        $ip_address = new IpAddress();
        $ip_address->user_id = Auth::id();
        $ip_address->ip_address = $request->input('ip_address');
        $ip_address->ip_address_added_date = date("jS F Y");
        $ip_address->ip_address_added_time = date("h:i A");
        $ip_address->save();

        $last_inserted_ip_address = $ip_address->id;
        if ($last_inserted_ip_address) {
            return redirect('/ip-addresses')->with('ip_address_success_msg', 'IP Address Added Successfully');
        } else {
            return redirect('ip-addresses.create')->with('ip_address_add_error', 'WHOOPS! IP Address Could not Added');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $ip_address
     * @return \Illuminate\Http\Response
     */
    public function edit($ip_address)
    {
        $ip_address = IpAddress::find($ip_address);

        return view('administrator.ip_address.edit',compact('ip_address'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $ip_address
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $ip_address)
    {
        date_default_timezone_set('Europe/London');
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|unique:ip_addresses,ip_address'
        ])->validate();

        $ip_address = IpAddress::find($ip_address);
        $ip_address->ip_address = $request->input('ip_address');
        $ip_address->update();

        return redirect('/ip-addresses')->with('updateIpAddressSuccessMsg', 'IP Address updated successfully!');
    }

    public function destroy($ip_address)
    {
        $ip_address = IpAddress::find($ip_address);
        $ip_address->delete();

        return redirect('/ip-addresses')->with('deleteIpAddressSuccessMsg', 'IP Address deleted successfully!');
    }

    public function ipAddresses()
    {
        $auth_user = Auth::user();
        $ip_addresses = IpAddress::with('audits')->orderBy('id', 'DESC')->get();

        $raw_columns = ['user_name', 'message', 'status'];
        $datatable = datatables($ip_addresses)
            ->addColumn('user_name', function ($ip_address) {
                $audits = $ip_address->audits;
                $user= '';
                $no_of_audits = count($audits);
                if($no_of_audits > 0)
                {
                $user = User::find($audits[$no_of_audits - 1]->user_id);
                }
                else
                {
                $user = User::find($ip_address->user_id);
                }
                
                return $user->name;
            })
            ->addColumn('message', function ($ip_address) {
                $audits = $ip_address->audits;
                $no_of_audits = count($audits);

                if($no_of_audits > 0)
                {

                    return $audits[$no_of_audits - 1]->message;
                }
                else
                {
                    return 'N/A';
                }
                
            })
            ->addColumn('status', function ($ip_address) {
                $content = ($ip_address->status == 'active') ?
                    '<h5><span class="badge badge-success" style="width: 100%;">Enabled</span></h5>'
                    : '<h5><span class="badge badge-danger" style="width: 100%;">Disabled</span></h5>';
                return $content;
            });
        if ($auth_user->hasAnyPermission(['ip-address_edit','ip-address_enable-disable','ip-address_delete'])) {
            $raw_columns = ['user_name', 'message', 'status', 'action'];
            $datatable = $datatable->addColumn('action', function ($ip_address) use ($auth_user) {
                $content = "";
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class=list-icons-item" data-toggle="dropdown"><i class="icon-menu9"></i></a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('ip-address_edit')) {
                    $content .= '<a href="' . route('ip-addresses.edit', $ip_address->id) . '" class="dropdown-item"> <i></i>Edit</a>';
                }
                if ($auth_user->hasPermissionTo('ip-address_enable-disable')) {
                    $content .= ($ip_address->status == 'active') ?
                        '<a href="' . route('ipAddressStatus', $ip_address->id) . '" class="dropdown-item"><i></i>Disable </a>'
                        : '<a href="' . route('ipAddressStatus', $ip_address->id) . '" class="dropdown-item"><i></i>Enable </a>';
                }
                if ($auth_user->hasPermissionTo('ip-address_delete')) {
                    $content .= '<a href="' . route('ip-delete.destroy', $ip_address->id) . '" class="dropdown-item"> <i></i>Delete</a>';
                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            });
        }
        $datatable = $datatable->rawColumns($raw_columns)->make(true);
        return $datatable;
    }

    public function ipAddressStatus($ip_address)
    {
        $ip_address_obj = IpAddress::find($ip_address);
        $ip_address_observer = new IpAddressObserver();
        if ($ip_address_obj->status == 'active') {
            if (DB::table('ip_addresses')->where('id', $ip_address)->update(['status' => 'disable', 'updated_at' => Carbon::now()])) {
                $ip_address_observer->updated($ip_address_obj, "Status: Disabled for {$ip_address_obj->ip_address} successfully", ["is active" => "Disabled"]);
                return redirect('/ip-addresses')->with('IpAddressDisableSuccessMsg', 'IP Address Disabled Successfully');
            } else {
                return redirect('/ip-addresses')->with('IpAddressDisableErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        } elseif ($ip_address_obj->status == 'disable') {
            if (DB::table('ip_addresses')->where('id', $ip_address)->update(['status' => 'active', 'updated_at' => Carbon::now()])) {
                $ip_address_observer->updated($ip_address_obj, "Status: Enabled for {$ip_address_obj->ip_address} successfully", ["is active" => "Enabled"]);
                return redirect('/ip-addresses')->with('IpAddressEnableSuccessMsg', 'IP Address Enabled Successfully');
            } else {
                return redirect('/ip-addresses')->with('IpAddressEnableErrMsg', 'WHOOPS! Something Went Wrong!!');
            }
        }
    }
}
