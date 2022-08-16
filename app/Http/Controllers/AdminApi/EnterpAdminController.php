<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\OrganizationAdministration;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use DB;
use View;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\FileUploadClass;

class EnterpAdminController extends Controller
{
    public $userId = 0;
    public $roleId = 0;
    public $userDetails = NULL;

    public $modulePermissions="";
    public $module="";
    	
	public function __construct()
    {
        $encUserToken = Input::get('usrtoken'); 

        if(isset($encUserToken) && $encUserToken != "")
        {
            $userId = Crypt::decrypt($encUserToken);

            $user = User::active()->byId($userId)->first();

            if(isset($user))
            {
                $this->userDetails = $user;
                $this->userId = $userId;
                $this->roleId = $user->role_id;
            }
        }
    }
    
    /**
     * Authenticate app user for login.
     *
     * @return json array
     */
    public function authenticate()
    {
        $msg = "";
        $status = 0;

    	$username = Input::get('username');
    	$password = Input::get('password');

        /*$username = 'admin';
        $password = 'itsadm9';*/

        $response = array();

        if($username != "" && $password != "")
        {
	        $employeeData = User::where('username','=',$username)->active()->first();
	                        
	        if(isset($employeeData))
        	{
        		$hashedPassword = $employeeData->password;
	            $userId = $employeeData->user_id;
	            $roleId = $employeeData->role_id;
	            $userName = $employeeData->employee_name;
	            
        		if(Hash::check($password, $hashedPassword))
	            {	                
                    $status = 1;

                    $encUserToken = Crypt::encrypt($userId);
                    $response['userToken'] = $encUserToken;
                    $response['userFullname'] = $userName;
                    $response['roleId'] = $roleId;
	            }
	            else
	            {
		            $status = -1;
		            $msg = "Invalid Credentials";
	            }
			}
            else
            {
	            $status = -1;
	            $msg = "Invalid Credentials";
			}
        }
    	else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";
        
        return Response::json($response);
    }    
}