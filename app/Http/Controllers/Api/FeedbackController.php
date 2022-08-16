<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserFeedback;
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
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;

class FeedbackController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    /**
     * Add Feedback.
     *
     * @return json array
     */
    public function saveFeedbackDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $feedback = Input::get('feedback');
        $loginToken = Input::get('loginToken');

        /*$encUserId = "eyJpdiI6InVVMHF5dEc2dTBYQm1CajZhY05FT1E9PSIsInZhbHVlIjoib0xuT0xXcitYc2FyVjE4NjUzNTc4UT09IiwibWFjIjoiOWE3MjEwMjBlNjhkZDE0ZWNhNjBiODAzN2I4MDcyYjAzZjU4M2NjZjI5Njk3YjZlMzBmMzU4OTg2Mzk4ODgyZiJ9";
        $feedback = "gifhhfgf edited";*/

        $response = array();

        if($encUserId != "" && $feedback != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
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
				             
                $status = 1;
                $msg = "";

                $userFeedback = New AppuserFeedback;
                $userFeedback->appuser_id = $userId;
                $userFeedback->feedback_text = $feedback;
                $userFeedback->save();
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);

                //Thank you mail
                MailClass::sendFeedbackAcknowledgementMail($userId, $feedback);
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
