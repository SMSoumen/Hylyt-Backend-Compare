<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Validator;

class UserController extends Controller
{	
    public $page_icon = "";
	public $page_title = "";
	public $breadcrumbArr = array();
    public $breadcrumbLinkArr = array();
    
    public $userData="";
    public $userId=0;
	public $role="";
	
	public function __construct()
    {
    	
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function login()
    {  
        $userSession = Session::get('user');

        if (Session::has('user') && count($userSession) > 0 && $userSession[0]['id'] > 0) 
        {
            Redirect::to('dashboard')->send();
        }

        $pageName = 'User Login';        
        
        $data = array();
        $data['pageName'] = $pageName;
        $data['page_description'] = $pageName;

        return view('user.login', $data);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function authenticate()
    {
    	$username = Input::get('username');
    	$password = Input::get('password');  

        $errorStr = "";
       
        if($username == "" || $password == "")
        {
            $errorStr = "Username and password are required";
            return Redirect::route('login')->with('errorStr', $errorStr);
        }
        else
        {
            $authenticated = $this->authenticateUserCredentials($username, $password);

            if(!$authenticated)
            {
                $errorStr = "Invalid Credentials";
                return Redirect::route('login')->with('errorStr', $errorStr);
            }
            else
            {
                return Redirect::route('dashboard');
            }
        }    	
    	
    }

    private function authenticateUserCredentials($username, $password)
    {
        $encPassword = Hash::make($password);
        
        $employeeData = User::where('username','=',$username)
                        ->active()
                        ->get();

        if(count($employeeData)>0 )
        {
            $hashedPassword = $employeeData[0]['password'];
            $userId = $employeeData[0]['user_id'];
            $roleId = $employeeData[0]['role_id'];
            $userName = $employeeData[0]['employee_name'];
            $empNo = $employeeData[0]['employee_no'];

            if (Hash::check($password, $hashedPassword))
            {
                $userArr = array('role' => $roleId, 'id' => $userId, 'name' => $userName, 'empNo' => $empNo);
                Session::push('user', $userArr);
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    /**
     * Display change password view.
     *
     * @return void
     */
    public function changePassword()
    {
    	$userSession = Session::get('user');
        
        if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        }  
        
        $this->page_icon = "<i class='fa fa-user'></i>";
        $this->page_title = "User";
        $this->page_title_link = "dashboard";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];       
       
        $pageName = 'Change Password';
        
        $js = array("/dist/bootbox/bootbox.min.js");
    	$css = array();             
        
        $data = array();
        $data['js'] = $js;
        $data['css'] = $css;
        $data['userdata'] = $this->userData;
        $data['pageName'] = $pageName;
        $data['page_description'] = $pageName;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;

        return view('user.changePassword', $data);
    }
    
    /**
     * Validate current password.
     *
     * @return void
     */    
    public function validateCurrentPassword()
    { 
    	$userSession = Session::get('user');
        
        if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        } 
        
    	$isValid = FALSE;   
    		
        $password = Input::get('currPass');
    	
    	$userSession = Session::get('user');
        $userId = $userSession[0]['id'];
        
        $employeeData = User::where('user_id','=',$userId)
                        ->active()
                        ->get();

        if(count($employeeData)>0 )
        {
            $hashedPassword = $employeeData[0]['password'];

            if (Hash::check($password, $hashedPassword))
            {
                $isValid = TRUE;
            }
            else
            {
                $isValid = FALSE;
            }
        }
		
        echo json_encode(array('valid' => $isValid));	
	}
    
    /**
     * Validate and update password.
     *
     * @return void
     */    
    public function updatePassword(Request $request)
    {
    	$userSession = Session::get('user');
        
        if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        } 
        
    	$userSession = Session::get('user');
        $userId = $userSession[0]['id'];
       
       	$password = $request->new_pass;
        $encPassword = Hash::make($password);
        
        $userData = array();
        $userData['password'] = $encPassword;
        $userData['updated_by'] = $userId;
        
        $user = User::findOrFail($userId);
        $user->update($userData);
        
        return Redirect::route('logout');		
	}

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function logout()
    {
        Session::forget('user');
        return Redirect::route('login');
    }
}
