<?php

namespace Horsefly\Http\Controllers\Auth;

use Carbon\Carbon;
use Horsefly\Http\Controllers\Controller;
use Horsefly\User;
use Horsefly\LoginDetail;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('guest')->except('logout');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function authenticated()
    {
		date_default_timezone_set('Europe/London');
		
		$date = Carbon::now()->format('Y-m-d');
        $time = Carbon::now()->format('H:i:m');
       
        $login = new LoginDetail;
        $login->login_time= $time;
        $login->login_date= $date;
		
        $auth_user = Auth::user();
		
        if ($auth_user->hasRole('super_admin') || $auth_user->hasPermissionTo('dashboard_statistics')) {
            $today = Carbon::today();
			
            /*** Session keys for dashboard links */
            $session_array = ['daily_date' => $today, 'weekly_date' => $today, 'monthly_date' => $today, 'aggregate' => ['start_date' => '', 'end_date' => $today]];
            session()->put([
                'applicants_stats' => $session_array,
                'sales_stats' => $session_array,
                'offices_stats' => $session_array,
                'units_stats' => $session_array,
                'quality_stats' => $session_array,
                'resource_stats' => $session_array,
                'crm_stats' => $session_array,
            ]);
			$login->user_id= $auth_user->id;
            $login->save();
			
            return redirect('/home');
        } elseif ($auth_user->hasAnyPermission([
			'applicant_list', 'applicant_import', 
			'applicant_create', 'applicant_edit', 
			'applicant_view', 'applicant_history', 
			'applicant_note-create', 'applicant_note-history'
		]))
		{
			$login->user_id= $auth_user->id;
			$login->save();
			return redirect('/applicants');
		}
        elseif ($auth_user->hasAnyPermission([
			'office_list', 'office_import', 
			'office_create', 'office_edit', 
			'office_view', 'office_note-history', 
			'office_note-create']))
            {
			
            $login->user_id= $auth_user->id;
            $login->save();
			
            return redirect('/offices');
        }
        elseif ($auth_user->hasAnyPermission([
			'unit_list','unit_import','unit_create',
			'unit_edit','unit_view','unit_note-create',
			'unit_note-history'
		]))
		{
            $login->user_id= $auth_user->id;
            $login->save();
			
            return redirect('/units');
        } 
        elseif ($auth_user->hasAnyPermission([
			'sale_list', 'sale_import', 'sale_create', 
			'sale_edit', 'sale_view', 'sale_open', 
			'sale_close', 'sale_manager-detail', 
			'sale_history', 'sale_notes', 
			'sale_note-create', 'sale_note-history'
		]))
		{
            $login->user_id= $auth_user->id;
            $login->save();
			
            return redirect('/sales');
        }
        else
		{
            $login->user_id= $auth_user->id;
            $login->save();
			
            return redirect('post-code-finder');
        }
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $user = User::where('email',$request->email)->first();

        Log::info('Requested IP Address: ' . $request->ip());

        if( $user && !$user->is_active){
            return redirect()->back()->with('error','The user has been de-activated');
        }

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }
        

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}