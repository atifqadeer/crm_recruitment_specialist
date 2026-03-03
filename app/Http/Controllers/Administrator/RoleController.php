<?php


namespace Horsefly\Http\Controllers\Administrator;


use Horsefly\Http\Controllers\Controller;
use Horsefly\User;
use Horsefly\Office;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('auth');
//        $this->middleware('is_admin');

        $this->middleware('permission:role_list|role_create|role_view|role_edit|role_delete|role_assign-role', ['only' => ['index']]);
        $this->middleware('permission:role_create', ['only' => ['create','store']]);
        $this->middleware('permission:Hoffice_role_create', ['only' => ['office_create']]);
        $this->middleware('permission:Hoffice_role_store', ['only' => ['office_store']]);
        $this->middleware('permission:Hoffice_role_update', ['only' => ['update_office']]);
        $this->middleware('permission:role_view', ['only' => ['show']]);
        $this->middleware('permission:role_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:role_delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::with('roles')->where(["is_admin" => 0])->select('id','name')->get();
        $all_roles = Role::where('name', '<>', 'super_admin')->get();

        $roles = Role::orderBy('id','DESC')->paginate(10);
        // print_r($roles);exit();

        // echo '<pre>';print_r($users);echo '</pre>';exit();

        return view('administrator.roles.index',compact('roles', 'all_roles', 'users'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function office_search(Request $request)
    {
    // check if ajax request is coming or not
    if($request->ajax()) {
        // select country name from database
        $data = office::select('id','office_name')->where('office_name','LIKE','%'.$request->country.'%')->get();
        // declare an empty array for output
        $output = '';
        // if searched countries count is larager than zero
        if (count($data)>0) {
            // concatenate output to the array
            $output = '<ul class="list-group" style="display: block; position: relative; z-index: 1">';
            // loop through the result array
            foreach ($data as $row){
                // concatenate output to the array
                $output .= '<li class="list-group-item" style="border-bottom:1px solid rgba(153,153,153,.3); margin:-10px 0 4px 0" id="'.$row->id.'">'.$row->office_name.'</li>';
            }
            // end of output
            $output .= '</ul>';
        }
        else {
            // if there's no matching results according to the input
            $output .= '<li class="list-group-item">'.'No results'.'</li>';
        }
        // return output result array
        return $output;
    }
}
        public function office_create()
        {
            $permission = Permission::get();
            $offices = office::select('id','office_name')->get();
            // echo '<pre>';print_r($offices);echo'</pre>';exit();
            return view('administrator.roles.head_office_roles_create',compact('permission','offices'));
        }
        
    public function office_store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
            'head_office' => 'required',
        ]);
        $prev_permissions=array();
        $prev_permissions = $request->input('permission');
        $prev_permissions[]=44;
        $prev_permissions[]=45;
            $role = Role::create(['name' => $request->input('name')]);
            $head_offices = $request->input('head_office');
            for($i=0;$i<count($head_offices);$i++)
            {
                // $office_per='';
                $all_permissions = Permission::get();
                if(!$all_permissions->contains('name', $head_offices[$i]))
                {
                Permission::create(['name' => $head_offices[$i]]);
                // $office_per = Permission::where('name', $head_offices[$i])->value('id');
                }
                // else
                // {
                //     $office_per = Permission::where('name', $head_offices[$i])->value('id');
                // }
                $prev_permissions[]=$head_offices[$i];
            }
        $role->syncPermissions($prev_permissions);
        return redirect()->route('roles.index')
            ->with('success','Role created successfully');
    }


    public function update_office(Request $request, $id)
    {
        // print_r($request->input('head_office'));exit();
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
            'head_office' => 'required',
        ]);
        $prev_permissions=array();
        $prev_permissions = $request->input('permission');
        $prev_permissions[]=44;
        $prev_permissions[]=45;
        $role = Role::find($id);
        $role->name = $request->input('name');
        // echo $role->name;exit();

        $role->save();
            // $role = Role::create(['name' => $request->input('name')]);
            $head_offices = $request->input('head_office');
            for($i=0;$i<count($head_offices);$i++)
            {
                // $office_per='';
                $all_permissions = Permission::get();
                if(!$all_permissions->contains('name', $head_offices[$i]))
                {
                Permission::create(['name' => $head_offices[$i]]);
                // $office_per = Permission::where('name', $head_offices[$i])->value('id');
                }
                // else
                // {
                //     $office_per = Permission::where('name', $head_offices[$i])->value('id');
                // }
                $prev_permissions[]=$head_offices[$i];
            }
		$role->givePermissionTo($prev_permissions);
        //$role->syncPermissions($prev_permissions);
        return redirect()->route('roles.index')
            ->with('success','Role created successfully');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permission = Permission::get();
        // $offices = office::select('id','office_name')->get();
        // echo '<pre>';print_r($offices);echo'</pre>';exit();
        return view('administrator.roles.create',compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);
           
        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));

        return redirect()->route('roles.index')
            ->with('success','Role created successfully');
    }
   
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
        return view('administrator.roles.show',compact('role','rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::find($id);
        // print_r($role);exit();
        $permission = Permission::get();
        $Hoffice_res = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->pluck('permissions.name')->all();
        $Hoffice_permissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->pluck('permissions.name','permissions.id')->all();
        // print_r($Hoffice_permissions);exit();
        $Hoffice_data = array();

        foreach($Hoffice_permissions as $key => $value)
        {
            if(strpos($value, 'Hoffice_', 0) !== false)
            {
            // echo $value;exit();

                // $Hoffice_data[] = $key;
                $Hoffice_data[$key] = $value;
            }

        }

        // print_r($Hoffice_data);exit();

        $Hoffice_status = false;
        foreach($Hoffice_res as $res)
        {
            if(strpos($res, 'Hoffice_', 0) !== false)
            {
                $Hoffice_status = true;
            }

        }
        // $headOffice_per = array();
        // foreach($Hoffice_permissions as $per)
        // {
        //     echo $per->name;exit();
        // }
        //    print_r($Hoffice_permissions[0]->name);exit();
        // DB::table('users')
        //     ->join('contacts', 'users.id', '=', 'contacts.user_id')
        //     ->join('orders', 'users.id', '=', 'orders.user_id')
        //     ->select('users.id', 'contacts.phone', 'orders.price')
        //     ->get();
        // print_r($test);exit();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
            // $res = preg_grep('/^Hoffice_\s.*/', $Hoffice_res);
            // // $res = str_contains('Hoffice_', $Hoffice_res);
            // echo $res;exit();
            if($Hoffice_status)
            {
                return view('administrator.roles.office_edit',compact('role','permission','rolePermissions','Hoffice_data'));
            }
            else{
                return view('administrator.roles.edit',compact('role','permission','rolePermissions'));

            }
            // print_r($rolePermissions);exit();
            // echo '<pre'; print_r($permission);echo '</pre>';exit();
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
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);

        $role = Role::find($id);
        $role->name = $request->input('name');
        // echo $role->name;exit();

        $role->save();

        $role->syncPermissions($request->input('permission'));

        return redirect()->route('roles.index')
            ->with('success','Role updated successfully');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table("roles")->where('id',$id)->delete();
        return redirect()->route('roles.index')
            ->with('success','Role deleted successfully');
    }
}