<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Client;
use Auth;
use DB;
use Redirect;
use Validator;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$clients = Client::where('status','active')->get();
        $clients = Client::all();

        return view('administrator.client.index',compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('administrator.client.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $auth_user = Auth::user()->id;

        Validator::make($request->all(),[
            'client_email' => 'email|unique:clients',
            'client_postcode' => 'unique:clients',
            'client_phone' => 'unique:clients'
        ])->validate();


        $client = new Client();
        $client->user_id = $auth_user;
        $client->client_name = $request->input('client_name');
        $client->client_email = $request->input('client_email');
        $client->client_postcode = $request->input('client_postcode');
        $client->client_phone = $request->input('client_phone');
        $client->client_landline = $request->input('client_landline');
        $client->client_website = $request->input('client_website');
        $client->save();
        $last_inserted_client = $client->id;
        if($last_inserted_client){
            $client_uid = md5($last_inserted_client);
            DB::table('clients')->where('id', $last_inserted_client)->update(['client_uid' => $client_uid]);
            return redirect('clients')->with('client_success_msg', 'Client Added Successfully');
        }
        else{
            return redirect('client.create')->with('client_add_error', 'WHOOPS! Client Could not Added');
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
        $client = Client::find($id);
        return view('administrator.client.show',compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client = Client::find($id);
        return view('administrator.client.edit',compact('client'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $auth_user = Auth::user()->id;
        $client = Client::find($id);
        $client->user_id = $auth_user;
        $client->client_name = $request->get('client_name');
        $client->client_email = $request->get('client_email');
        $client->client_postcode = $request->get('client_postcode');
        $client->client_phone = $request->get('client_phone');
        $client->client_landline = $request->input('client_landline');
        $client->client_website = $request->get('client_website');
        $client->update();


        return redirect('clients')->with('updateClientSuccessMsg', 'Client has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Client::find($id);
        $status = $client->status;
        if($status == 'active'){
            if(DB::table('clients')->where('id',$id)->update(['status' => 'disable'])){
                return redirect('clients')->with('ClientDeleteSuccessMsg', 'Client has been disabled Successfully');
            }
            else{
                return redirect('clients')->with('ClientDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        }
        elseif($status=='disable'){
            if (DB::table('clients')->where('id', $id)->update(['status' => 'active'])) {
                return redirect('clients')->with('ClientDeleteSuccessMsg', 'Client has been enabled Successfully');
            } else {
                return redirect('clients')->with('ClientDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }
        }
    }
}
