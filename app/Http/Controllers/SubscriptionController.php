<?php
namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use DB;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;

class SubscriptionController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    /**
     * Unsubscribe Verification Pending Mail.
     *
     * @return json array
     */
    public function unsubscribeVerificationPending()
    {      
        $msg = "";
        $status = 0;

        $encUserId = Input::get('u');

        //$encUserId = "eyJpdiI6IjZ5Tk95dVRvcVpqbU82SDdqNWpXZXc9PSIsInZhbHVlIjoiNmFNRjFieVNhQm9KVU9xTVBJd3Aydz09IiwibWFjIjoiN2E2MTYzNGFhMTYxMTI3NmU0NzRkMDVkZDIzNTk5NWE4NjhiODQwZTdiNGFkODE3MDJkYmRlNDc2NThlNTg1OCJ9";

        $response = array();

        if($encUserId != "")
        {
            $decParts = Crypt::decrypt($encUserId);
        	$parts = explode("_",$decParts);
        	if(count($parts) > 1)
        	{
        		$userId = $parts[1];
	            $user = Appuser::findOrFail($userId);
	            
	            if(isset($user))
	            {              
	                $status = 1;
	                $subFlag = 1;
	                
	                $user->ver_pend_mail_unsub = $subFlag;
	                $user->save();
	                
	                $msg = Config::get('app_config_notif.inf_user_unsub_success');
	            }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
			}
	        else
	        {
	            $status = -1;
	            $msg = Config::get('app_config_notif.err_invalid_data');
	        }     
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }            

        $response['status'] = $status;
        $response['msg'] = "$msg";


        //return Response::json($response);
        print_r("<h1>");
        print_r($msg);        
        print_r("</h1>");
    }
    
    /**
     * Register app user.
     *
     * @return json array
     */
    public function unsubscribeInactivity()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('u');

        //$encUserId = "eyJpdiI6IjZ5Tk95dVRvcVpqbU82SDdqNWpXZXc9PSIsInZhbHVlIjoiNmFNRjFieVNhQm9KVU9xTVBJd3Aydz09IiwibWFjIjoiN2E2MTYzNGFhMTYxMTI3NmU0NzRkMDVkZDIzNTk5NWE4NjhiODQwZTdiNGFkODE3MDJkYmRlNDc2NThlNTg1OCJ9";

        $response = array();

        if($encUserId != "")
        {
            $decParts = Crypt::decrypt($encUserId);
        	$parts = explode("_",$decParts);
        	if(count($parts) > 1)
        	{
        		$userId = $parts[1];
	            $user = Appuser::findOrFail($userId);
	            
	            if(isset($user))
	            {              
	                $status = 1;
	                $subFlag = 1;
	                
	                $user->inact_rem_mail_unsub = $subFlag;
	                $user->save();
	                
	                $msg = Config::get('app_config_notif.inf_user_unsub_success');
	            }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
			}
	        else
	        {
	            $status = -1;
	            $msg = Config::get('app_config_notif.err_invalid_data');
	        }     
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }            

        $response['status'] = $status;
        $response['msg'] = "$msg";

        //return Response::json($response);
        print_r("<h1>");
        print_r($msg);        
        print_r("</h1>");
    }
}
