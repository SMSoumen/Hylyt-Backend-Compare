<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use Illuminate\Http\Request;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;

class MessagingController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    /**
     * Register app user.
     *
     * @return json array
     */
     public function registerAppuserToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $token = Input::get('registrationToken');
        $loginToken = Input::get('loginToken');
        $appKey = Input::get('appKey');

        /*$encUserId = "eyJpdiI6IkJUMEt4UG5QZExqZlJxTUtPaUVOeUE9PSIsInZhbHVlIjoiZDdyMWF5ZGJKZEZ0d0xIUzhIT2g3UT09IiwibWFjIjoiZDM3YzU4ZmFkNjZmYjgwZWI4ZmU3NDgwYWE0ZTkxMDNlY2Y2YmQ1ZTFhYTQ5NDFjNjI2N2YzNjcwNTg4OGQyNiJ9";
        $token = "0edfc8b2-da5d-4e35-8380-da8d88a46b07";
        $loginToken = "2018-02-27 17:29:06_2946";*/

        $response = array();

        if($encUserId != "" && $token != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $depMgmtObj->setAppKeyStr($appKey);

                $appKeyMappingId = $depMgmtObj->getAppKeyMappingId();
				
				CommonFunctionClass::removeOtherUserSessionWithMessageToken($userId, $token, $appKeyMappingId);
				  
                $status = 1;
                $msg = "";
                
                $userSession->reg_token = $token;
                $userSession->save();
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

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
}