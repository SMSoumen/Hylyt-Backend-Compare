<?php 
namespace App\Libraries;

use Config;
use Twilio;
use App\Api\Msgtemplate;
use App\Api\AppuserOrderProduct;
use App\Api\AppuserOtp;
use App\Api\Msglog;
use Crypt;
use Guzzle;

class MessagingClass 
{
	function sendMessage($templateId, $to, $message, $isUser = 0, $id = 0)
	{
        //$sms = Twilio::message($to, $message);
		$apiKey = Config::get('app_config.msg_service_api_key');
        $sender = Config::get('app_config.msg_service_sender');
        $serviceUrl = Config::get('app_config.msg_service_url');

        $msgUrl = "";
		$msgUrl .= $serviceUrl.'method=sms&api_key='.$apiKey;
		$msgUrl .= '&format=json&custom=1&flash=0&unicode=1';
		$msgUrl .= '&to='.$to;
		$msgUrl .= '&sender='.$sender;
		$msgUrl .= '&message='.$message;
		
		$responseBody = Guzzle::get($msgUrl)->getBody();
		$obj = json_decode($responseBody, true);

		$status = "";
		if(isset($obj["data"]["0"]))
			$status = $obj["data"]["0"]["status"];
		elseif(isset($obj["status"]))
			$status = $obj["status"]." ".$obj["message"];

        $log = new Msglog;
        $log->msgtemplate_id = $templateId;

        if($isUser == 0)
        	$log->seller_id = $id;
        elseif($isUser == 1)
        	$log->appuser_id = $id;

        $log->status = $status;
        $log->msg_content = $message;
        $log->contact = $to;
        $log->save();

        return $status;
	}

	function sendVerificationCodeMessage($appuser)
	{
		$templateId = Config::get("app_config_template.temp_msg_verification_code");
		$template = Msgtemplate::findOrFail($templateId);
		if(isset($template))
		{
			$placeholderVerCode = Config::get("app_config_template.placeholder_verification_code");

			$msgTo = $appuser->contact;
			$isUser = 1;
			$appuserId = $appuser->appuser_id;

			$msgContent = $template->msg_content;

			$encVerCode = $appuser->verification_code;
			$verCode = Crypt::decrypt($encVerCode);

			$msgContent = str_replace($placeholderVerCode, $verCode, $msgContent);

			$status = $this->sendMessage($templateId, $msgTo, $msgContent, $isUser, $appuserId);
        	return $status;
		}
	}

	function sendOtpMessage($appuser)
	{
		$templateId = Config::get("app_config_template.temp_msg_verification_code");
		$template = Msgtemplate::findOrFail($templateId);
		if(isset($template))
		{
			$placeholderVerCode = Config::get("app_config_template.placeholder_verification_code");

			$msgTo = $appuser->contact;
			$isUser = 1;
			$appuserId = $appuser->appuser_id;

			$msgContent = $template->msg_content;

			$encOtp = AppuserOtp::where('appuser_id','=',$appuser->appuser_id)->first()->otp; 
			$otp = Crypt::decrypt($encOtp);

			$msgContent = str_replace($placeholderVerCode, $otp, $msgContent);

			$status = $this->sendMessage($templateId, $msgTo, $msgContent, $isUser, $appuserId);
        	return $status;
		}
	}
}
