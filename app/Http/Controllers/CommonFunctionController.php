<?php 

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

class CommonFunctionController extends Controller 
{

	public function __construct()
    {
        $userSession = Session::get('user');
        
        /*if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        }*/
    }

	function dateCompareValidation()
	{
	 	$startDate = Input::get('start_date');	
	 	$endDate = Input::get('end_date');	
		
		if ($startDate == "")
			$isAvailable = FALSE;
		else if($startDate != "")
		{
			if(strtotime($startDate) > strtotime($endDate))
				$isAvailable = FALSE;
			else
				$isAvailable = TRUE;
		}
		
		echo json_encode(array('valid' => $isAvailable));
	}

	public static function convertStringToUpper($strForConversion)
	{
	 	$convertedString = strtoupper($strForConversion);	 	
	 	return $convertedString;
	}

	public static function convertStringToLower($strForConversion)
	{
	 	$convertedString = strtolower($strForConversion);	 	
	 	return $convertedString;
	}

	public static function convertStringToCap($strForConversion)
	{
	 	//$convertedString = strtolower($strForConversion);	
	 	//$convertedString = ucwords($convertedString);	 	
	 	$convertedString = ucwords($strForConversion);	 	
	 	return $convertedString;
	}

	public static function convertDispToDbDate($dtStr)
	{
		$dt = "";
		
		if($dtStr != NULL && $dtStr != "" && $dtStr != "00-00-0000")
		{
       	 	$dt = date(Config::get('app_config.date_db_format'), strtotime($dtStr));
		}
		 	
	 	return $dt;
	}

	public static function convertDbToDispDate($dtStr)
	{
		$dt = "";
		
		if($dtStr != NULL && $dtStr != "" && $dtStr != "0000-00-00")
		{
       	 	$dt = date(Config::get('app_config.date_disp_format'), strtotime($dtStr));
		}
		 	
	 	return $dt;
	}
	
	public function sendTestNotif()
	{
		$message = "Hello this is a notif. 11";
		$token = "da8x3vnsB2Q:APA91bHgK3CoY_jKrTfGPolu8-xl_9FOMLGAM6p6i5_RP8kaEKBKNfHucxKbgZ_Mx2bTII27XoS2vVb69vIYN1suNnq_Y9u5_VzjbdeUQKdYbqFHvUyMrSAlAK6dPFrvCf7v_WbznHfX";
		$imgUrl = "";
		
		$optionBuiler = new OptionsBuilder();
		$optionBuiler->setTimeToLive(Config::get('app_config.fcm_ttl'));
		$optionBuiler->setContentAvailable(Config::get('app_config.fcm_content_available'));
		$optionBuiler->setPriority(Config::get('app_config.fcm_priority'));

		$notificationBuilder = new PayloadNotificationBuilder('SocioRAC');
		$notificationBuilder->setBody($message)
		                    ->setSound('default');
		                    
		$notification = $notificationBuilder->build();
		//$notification = NULL;

		$notifDetails = array();
        $notifDetails['isContent'] = 0;
        $notifDetails['isAction'] = 0;
        $notifDetails['msg'] = $message;
		$notifDetails['imgUrl'] = $imgUrl;

        $responseData = $notifDetails;

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($responseData);

		$option = $optionBuiler->build();
		$data = $dataBuilder->build();
		$data = NULL;

		$downstreamResponse = FCM::sendTo($token, $option, $notification);
    	
    	print_r('FCM downstreamResponse : ');
    	print_r($downstreamResponse);
    	print_r('</pre>');
    	
    	print_r('FCM optionBuiler : ');
    	print_r($optionBuiler);
    	print_r('</pre>');

		$successCnt = $downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();
		
		if($successCnt > 0)
			$sendStatus = 1;

		//return Array - you must remove all this tokens in your database
		$downstreamResponse->tokensToDelete(); 

		//return Array (key : oldToken, value : new token - you must change the token in your database )
		$downstreamResponse->tokensToModify(); 

		//return Array - you should try to resend the message to the tokens in the array
		$downstreamResponse->tokensToRetry();

		// return Array (key:token, value:errror) - in production you should remove from your database the tokens
	}
}
