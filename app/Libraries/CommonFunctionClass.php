<?php
namespace App\Libraries;

use Config;
use Image;
use Carbon\Carbon;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppKeyMapping;
use Illuminate\Support\Facades\Input;
use App\Models\Api\SessionType;

use App\Models\Org\Organization;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationAdministrationSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Libraries\MailClass;

class CommonFunctionClass
{
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
	
	public static function brToNl($string)
	{
		return preg_replace('#<br\s*?/?>#i', "\n", $string); 
		/*return preg_replace('#<br\s*?/?>#i', "", $string);*/
	}
	
	public static function generateLoginToken()
	{		
		$minVal = 1000;
        $maxVal = 9999;

        $randonNum = rand($minVal, $maxVal);
		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);	

        $loginToken = $utcToday."_".$randonNum;

        return $loginToken;
	}
	
	public static function setLastSyncTs($userId, $loginToken)
	{
        $userSession = CommonFunctionClass::getUserSession($userId, $loginToken);
        if(isset($userSession))
        {
        	$dtCurr = date(Config::get('app_config.datetime_db_format'));

        	$userSession->last_sync_ts = $dtCurr;
	        $userSession->save();

	        $user = Appuser::byId($userId)->first();
	        $user->last_sync_ts = $dtCurr;
	        $user->save();
        }
	}
	
	public static function getCurrentTimestamp()
	{		
        $dtCurr = date(Config::get('app_config.datetime_db_format'));
        return $dtCurr;
	}
	
	public static function setEnterpAdminUserSession($userId)
	{
		$loginToken = CommonFunctionClass::generateLoginToken();

    	$userSession = New OrganizationAdministrationSession;
        $userSession->org_admin_id = $userId;
        $userSession->login_token = $loginToken;
        $userSession->save();
        
		return $loginToken;
	}
	
	public static function getEnterpAdminUserSession($userId, $loginToken)
	{
		$userSession = NULL;
		if(isset($userId) && $userId > 0 && isset($loginToken) && $loginToken != "")
		{
			$userSession = OrganizationAdministrationSession::ofUser($userId)->havingToken($loginToken)->first();
		}
		return $userSession;
	}
	
	public static function setUserSession($userId)
	{
    	$sessTypeId = Input::get('sessType');

    	$ipAddress = Input::get('ipAddress');
    	$deviceUniqueId = Input::get('deviceUniqueId');
    	$clientDetailsStr = Input::get('clientDetailsStr');
    	$deviceModelName = Input::get('deviceModelName');
    	$appKey = Input::get('appKey');
    	
    	$sessModelObj = New SessionType;
		$androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
		$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
    	
    	$sessTypeObj = $sessModelObj->byId($sessTypeId)->first();		
		
		if(isset($sessTypeObj))
			$sessionTypeId = $sessTypeId;
		else
			$sessionTypeId = $androidTypeId;
	                    			
		$sessionSaved = FALSE;
		do
		{
			$loginToken = CommonFunctionClass::generateLoginToken();
	        $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
	        
	        if(!isset($userSession))            
	        {
	        	$userSession = New AppuserSession;
		        $userSession->appuser_id = $userId;
		        $userSession->login_token = $loginToken;
		        $userSession->session_type_id = $sessionTypeId;

		        if(isset($ipAddress) && trim($ipAddress) != "")
		        {
		        	$ipAddress = trim($ipAddress);
		        	$userSession->ip_address = $ipAddress;
		        }

		        if(isset($deviceUniqueId) && trim($deviceUniqueId) != "")
		        {
		        	$deviceUniqueId = trim($deviceUniqueId);
		        	$userSession->device_unique_id = $deviceUniqueId;
		        }

		        if(isset($deviceModelName) && trim($deviceModelName) != "")
		        {
		        	$deviceModelName = trim($deviceModelName);
		        	$userSession->device_model_name = $deviceModelName;
		        }

		        if(isset($clientDetailsStr) && trim($clientDetailsStr) != "")
		        {
		        	$clientDetailsStr = trim($clientDetailsStr);
		        	$userSession->client_details = $clientDetailsStr;
		        }

		        $mappedAppKeyId = 0;
		        if(isset($appKey) && trim($appKey) != "")
		        {
		        	$appKey = trim($appKey);

		        	$mappedAppKey = AppKeyMapping::byAppKey($appKey)->active()->first();
		        	if(isset($mappedAppKey))
		        	{
		        		$mappedAppKeyId = $mappedAppKey->app_key_mapping_id;
		        		$userSession->mapped_app_key_id = $mappedAppKeyId;
		        	}
		        }

		        $userSession->save();
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);

		        $sendSessionCreationMail = true;//false;
		        if($sendSessionCreationMail)
				{
					MailClass::sendNewAppuserSessionEstablishedMail($userId, $userSession);
				}
		        
		        $sessionSaved = TRUE;
			}
		}while(!$sessionSaved);
		
		if($sessionTypeId == $webTypeId)
		{
			CommonFunctionClass::retainPermittedWebSessions($userId);
		}
        
		return $loginToken;
	}

	public static function isSessionWebRequest($userSession)
	{
		$isWebRequest = FALSE;

		if(isset($userSession))
		{
	    	$sessModelObj = New SessionType;
			$androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
			$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;

	        $sessionTypeId = $userSession->session_type_id;

			if($sessionTypeId == $webTypeId)
			{
				$isWebRequest = TRUE;
			}
		}

		return $isWebRequest;
	}
	
	public static function setUserSessionOld($userId)
	{
    	$sessTypeId = Input::get('sessType');
    	
    	$sessModelObj = New SessionType;
		$androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
		$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
    	
    	$sessTypeObj = $sessModelObj->byId($sessTypeId)->first();		
		
		if(isset($sessTypeObj))
			$sessionTypeId = $sessTypeId;
		else
			$sessionTypeId = $androidTypeId;
	                    			
		$sessionSaved = FALSE;
		do
		{
			$loginToken = CommonFunctionClass::generateLoginToken();
	        $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
	        
	        if(!isset($userSession))            
	        {
	        	$userSession = New AppuserSession;
		        $userSession->appuser_id = $userId;
		        $userSession->login_token = $loginToken;
		        $userSession->session_type_id = $sessionTypeId;
		        $userSession->save();
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
		        
		        $sessionSaved = TRUE;
			}
		}while(!$sessionSaved);
		
		if($sessionTypeId == $webTypeId)
		{
			CommonFunctionClass::retainPermittedWebSessions($userId);
		}
        
		return $loginToken;
	}
	
	public static function getOtherUserLoginTokens($userId, $loginToken)
	{
		// return CommonFunctionClass::getUserLoginTokens($userId, $loginToken);
		return CommonFunctionClass::getMappedUserLoginTokenDetails($userId, $loginToken);
	}
	
	public static function getAllUserLoginTokens($userId)
	{
		// return CommonFunctionClass::getUserLoginTokens($userId, NULL);
		return CommonFunctionClass::getMappedUserLoginTokenDetails($userId, NULL);
	}
	
	private static function getUserLoginTokens($userId, $loginToken)
	{
		$userTokenArr = array();
		if(isset($userId) && $userId > 0)
		{
			$userSessions = AppuserSession::ofUser($userId);
			
			if(isset($loginToken) && $loginToken != "")
			{
				$userSessions->exceptToken($loginToken);
			}
			// $userSessions = $userSessions->exceptSessionTypeWeb();
			$userSessions = $userSessions->forBaseApp();
			$userSessions = $userSessions->withValidRegistrationToken();
			$userSessions = $userSessions->get();
			
			if(isset($userSessions) && count($userSessions) > 0)
			{
				foreach($userSessions as $userSession)
				{
					$token = $userSession->reg_token;
					
					if(isset($token) && $token != "")
						array_push($userTokenArr, $token);
				}
			}
		}
		return $userTokenArr;
	}
	
	private static function getMappedUserLoginTokenDetails($userId, $loginToken)
	{
		$compiledUserTokenDetailsArr = array();
		$userTokenArr = array();
		$isMappedAppArr = array();
		$mappedAppKeyDetailsArr = array();
		if(isset($userId) && $userId > 0)
		{
            $user = Appuser::byId($userId)->verified()->accountNotDisabled()->first();

	    	$sessModelObj = New SessionType;
			$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;

            if(isset($user))
            {
            	$userSessions = AppuserSession::ofUser($userId);
				
				if(isset($loginToken) && $loginToken != "")
				{
					$userSessions->exceptToken($loginToken);
				}
				// $userSessions = $userSessions->exceptSessionTypeWeb();
				$userSessions = $userSessions->withValidRegistrationToken();
				$userSessions = $userSessions->get();
				
				if(isset($userSessions) && count($userSessions) > 0)
				{
					foreach($userSessions as $userSession)
					{
						$token = $userSession->reg_token;
						
						if(isset($token) && $token != "")
						{
							array_push($userTokenArr, $token);

							$isMappedApp = FALSE;
							$isWebSession = FALSE;
							$mappedAppKeyDetails = NULL;
							if($userSession->mapped_app_key_id > 0 && isset($userSession->appKeyMapping)) 
							{
								$mappedAppKeyDetails = $userSession->appKeyMapping;
								$isMappedApp = TRUE;
							}

							if($userSession->session_type_id == $webTypeId)
							{
								$isWebSession = TRUE;
							}

							array_push($isMappedAppArr, $isMappedApp);
							array_push($mappedAppKeyDetailsArr, $mappedAppKeyDetails);

							$compiledUserTokenDetails = array();
							$compiledUserTokenDetails['userSession'] = $userSession;
							$compiledUserTokenDetails['token'] = $token;
							$compiledUserTokenDetails['isWebSession'] = $isWebSession;
							$compiledUserTokenDetails['isMappedApp'] = $isMappedApp;
							$compiledUserTokenDetails['mappedAppKeyDetails'] = $mappedAppKeyDetails;

							array_push($compiledUserTokenDetailsArr, $compiledUserTokenDetails);
						}
					}
				}
            }
		}

		$tokenDetailsArr = array();
		$tokenDetailsArr['tokenArr'] = $userTokenArr;
		$tokenDetailsArr['isMappedAppArr'] = $isMappedAppArr;
		$tokenDetailsArr['mappedAppKeyDetailsArr'] = $mappedAppKeyDetailsArr;

		return $compiledUserTokenDetailsArr;//$tokenDetailsArr;
	}
	
	public static function getOnlyWebUserLoginTokens($userId, $loginToken)
	{
		$compiledUserTokenDetailsArr = array();
		$userTokenArr = array();
		$isMappedAppArr = array();
		$mappedAppKeyDetailsArr = array();
		if(isset($userId) && $userId > 0)
		{
            $user = Appuser::byId($userId)->verified()->accountNotDisabled()->first();

            if(isset($user))
            {
				$userSessions = AppuserSession::ofUser($userId);
				
				if(isset($loginToken) && $loginToken != "")
				{
					$userSessions->exceptToken($loginToken);
				}
				$userSessions = $userSessions->ofSessionTypeWeb();
				$userSessions = $userSessions->withValidRegistrationToken();
				$userSessions = $userSessions->get();
				
				if(isset($userSessions) && count($userSessions) > 0)
				{
					foreach($userSessions as $userSession)
					{
						$token = $userSession->reg_token;
						
						if(isset($token) && $token != "")
						{
							array_push($userTokenArr, $token);

							$isMappedApp = FALSE;
							$mappedAppKeyDetails = NULL;
							if($userSession->mapped_app_key_id > 0 && isset($userSession->appKeyMapping)) 
							{
								$mappedAppKeyDetails = $userSession->appKeyMapping;
								$isMappedApp = TRUE;
							}

							array_push($isMappedAppArr, $isMappedApp);
							array_push($mappedAppKeyDetailsArr, $mappedAppKeyDetails);

							$compiledUserTokenDetails = array();
							$compiledUserTokenDetails['token'] = $token;
							$compiledUserTokenDetails['isMappedApp'] = $isMappedApp;
							$compiledUserTokenDetails['mappedAppKeyDetails'] = $mappedAppKeyDetails;

							array_push($compiledUserTokenDetailsArr, $compiledUserTokenDetails);
						}
					}
				}
			}
		}

		$tokenDetailsArr = array();
		$tokenDetailsArr['tokenArr'] = $userTokenArr;
		$tokenDetailsArr['isMappedAppArr'] = $isMappedAppArr;
		$tokenDetailsArr['mappedAppKeyDetailsArr'] = $mappedAppKeyDetailsArr;

		return $compiledUserTokenDetailsArr;//$tokenDetailsArr;
	}
	
	// public static function getOnlyWebUserLoginTokens($userId, $loginToken)
	// {
	// 	$userTokenArr = array();
	// 	if(isset($userId) && $userId > 0)
	// 	{
	// 		$userSessions = AppuserSession::ofUser($userId);
			
	// 		if(isset($loginToken) && $loginToken != "")
	// 		{
	// 			$userSessions->exceptToken($loginToken);
	// 		}
	// 		$userSessions = $userSessions->ofSessionTypeWeb();
	// 		$userSessions = $userSessions->withValidRegistrationToken();
	// 		$userSessions = $userSessions->get();
			
	// 		if(isset($userSessions) && count($userSessions) > 0)
	// 		{
	// 			foreach($userSessions as $userSession)
	// 			{
	// 				$token = $userSession->reg_token;
					
	// 				if(isset($token) && $token != "")
	// 					array_push($userTokenArr, $token);
	// 			}
	// 		}
	// 	}
	// 	return $userTokenArr;
	// }
	
	private static function retainPermittedWebSessions($userId)
	{
		if(isset($userId) && $userId > 0)
		{
			$userSessions = AppuserSession::ofUser($userId);			
			$userSessions = $userSessions->ofSessionTypeWeb()->onlyPermittedSessions()->get();
			
			if(isset($userSessions) && count($userSessions) > 0)
			{
				foreach($userSessions as $userSession)
				{
					$userSession->delete();
				}
			}
		}
	}
	
	public static function getFirstUserSessionToken($userId)
	{
		$regToken = "";
		$userSession = CommonFunctionClass::getFirstUserSession($userId);
		if(isset($userSession))
		{
			$regToken = $userSession->reg_token;
		}		
		return $regToken;
	}
	
	public static function getFirstUserSession($userId)
	{
		$userSession = NULL;
		if(isset($userId) && $userId > 0)
		{
			$userSession = AppuserSession::ofUser($userId)->first();
			
			if(!isset($userSession))
				$userSession = NULL;
		}
		return $userSession;
	}
	
	public static function getUserSession($userId, $loginToken)
	{
		$userSession = NULL;
		if(isset($userId) && $userId > 0 && isset($loginToken) && $loginToken != "")
		{
			$userSession = AppuserSession::ofUser($userId)->havingToken($loginToken)->first();
			
			if(!isset($userSession))
				$userSession = NULL;
		}
		return $userSession;
	}
	
	public static function removeOtherUserSessionWithMessageToken($userId, $msgToken, $appKeyMappingId = 0)
	{
		if(isset($userId) && $userId > 0 && isset($msgToken) && $msgToken != "")
		{
			 AppuserSession::havingMessagingToken($msgToken)->byMappedAppKeyId($appKeyMappingId)->delete();
		}
	}
	
	public static function getUserSessionCount($userId)
	{
		$sessionCnt = 0;
		if(isset($userId) && $userId > 0)
		{
			$userSessions = AppuserSession::ofUser($userId)->exceptSessionTypeWeb();
			$userSessions = $userSessions->get();
			
			if(isset($userSessions))
				$sessionCnt = count($userSessions);
		}
		return $sessionCnt;
	}
	
	public static function getAllowedUserSessionCount($userId)
	{	
		$allowedDeviceCount	= 0;
    	$userConstant = AppuserConstant::ofUser($userId)->first();
    	if(isset($userConstant))
    		$allowedDeviceCount = $userConstant->allowed_device_count;
    	
    	return $allowedDeviceCount;
	}
	
	public static function canEstablishDeviceSession($userId, $disableAutoTrashSession = 0)
	{
		$canBeEstablished = TRUE;
		    	
    	$allowedDeviceCount = CommonFunctionClass::getAllowedUserSessionCount($userId);
    	$sessionCnt = CommonFunctionClass::getUserSessionCount($userId);
    	
    	if($sessionCnt >= $allowedDeviceCount)
    	{
    		if(isset($disableAutoTrashSession) && $disableAutoTrashSession == 1)
    		{
				$canBeEstablished = FALSE;
    		}
			else
			{
    			CommonFunctionClass::trashOlderSession($userId, $allowedDeviceCount, $sessionCnt);
	    		$sessionCnt = CommonFunctionClass::getUserSessionCount($userId);

	    		if($sessionCnt >= $allowedDeviceCount)
		    	{
					$canBeEstablished = FALSE;
				}
			}	
		}
		
		return $canBeEstablished;
	}
	
	public static function trashOlderSession($userId, $allowedDeviceCount, $sessionCnt)
	{
		$isTrashSuccess = FALSE;
		
    	$sessTypeId = Input::get('sessType');
    	
    	$sessModelObj = New SessionType;
		$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
		$androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
    	
    	$sessTypeObj = $sessModelObj->byId($sessTypeId)->first();		
		
		if(isset($sessTypeObj))
			$sessionTypeId = $sessTypeId;
		else
			$sessionTypeId = $androidTypeId;
			
		if($sessionTypeId > 0) //!= $webTypeId)
		{
			$userSession = AppuserSession::ofUser($userId)->ofSessionType($sessionTypeId)->orderBy('last_sync_ts', 'ASC')->first();
			if(isset($userSession))
			{
				$userSession->delete();
				$isTrashSuccess = TRUE;
			}
		}
		
		return $isTrashSuccess;		
	}
	
	public static function getUserSessionAppKeyMapping()
	{
    	$userId = Input::get('userId');
    	$loginToken = Input::get('loginToken');
    	$appKey = Input::get('appKey');
	                    			
        $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
        
        $mappedAppKeyId = 0;
        if(isset($userSession))            
        {
	        $mappedAppKeyId = $userSession->mapped_app_key_id;
		}
		elseif(isset($appKey) && trim($appKey) != "")
    	{
        	$appKey = trim($appKey);

        	$mappedAppKey = AppKeyMapping::byAppKey($appKey)->active()->first();
        	if(isset($mappedAppKey))
        	{
        		$mappedAppKeyId = $mappedAppKey->app_key_mapping_id;
        	}
		}

		$fetchedMappedAppKey = NULL;
		if($mappedAppKeyId > 0)
		{
        	$fetchedMappedAppKey = AppKeyMapping::byId($mappedAppKeyId)->active()->first();
		}
        
		return $fetchedMappedAppKey;
	}

	public static function validateEmailAddress($emailaddress)
	{
		$isValidEmail = FALSE;
		$pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

		if (preg_match($pattern, $emailaddress) === 1) {
    		// emailaddress is valid
			$isValidEmail = TRUE;
		}
		return $isValidEmail;
	}
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateVerificationCode()
    {
        $minVal = 1000;
        $maxVal = 9999;

        $randonNum = rand($minVal, $maxVal);

        return $randonNum;
    }
    
    public static function getCreateTimestamp()
    {
		$utcTz =  'UTC';
		$createDateObj = Carbon::now($utcTz);
		$createTimeStamp = $createDateObj->timestamp;		
		$createTimeStamp = $createTimeStamp * 1000;
		return $createTimeStamp;
	}
    
    public static function getLastSyncTimestampForMicrosoftCalendarSyncing()
    {
		$lastSyncDateTs = CommonFunctionClass::getCreateTimestamp();
		$lastSyncDateStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($lastSyncDateTs, 0);
		$lastSyncDateStr = substr_replace( $lastSyncDateStr, ":", 22, 0 );
		$lastSyncDateStr = substr($lastSyncDateStr, 0, -6);
		$lastSyncDateStr = $lastSyncDateStr . '.0000000';
		return $lastSyncDateStr;
	}
    
    public static function getDispDateTimeFromUTCTimeStamp($utcTs, $offsetInMinutes = NULL)
    {
    	$utcTs = intval($utcTs/1000);
		$locCreateDt = Carbon::createFromTimeStampUTC($utcTs);
		if($offsetInMinutes != 0)
		{
			$offsetInMinutes = $offsetInMinutes*-1;
			
			$offsetIsNegative = 0;
			if($offsetInMinutes < 0)
			{
				$offsetIsNegative = 1;
				$offsetInMinutes = $offsetInMinutes*-1;
			}
			
			$offsetHours = 	$offsetInMinutes%60;						
			$offsetMinutes = $offsetInMinutes - ($offsetHours*60);
			
			if($offsetIsNegative == 1)
			{
				if($offsetHours > 0)	
					$locCreateDt = $locCreateDt->subHours($offsetHours);
				if($offsetMinutes > 0)
					$locCreateDt = $locCreateDt->subMinutes($offsetMinutes);		
			}
			else
			{
				if($offsetHours > 0)	
					$locCreateDt = $locCreateDt->addHours($offsetHours);
				if($offsetMinutes > 0)
					$locCreateDt = $locCreateDt->addMinutes($offsetMinutes);				
			}							
		}		
		$locCreateDateStr = $locCreateDt->toFormattedDateString();
		$locCreateTimeStr = $locCreateDt->toTimeString();
		$locCreateTimeStr = substr($locCreateTimeStr, 0, -3);
		
        $contentDispDateTime = $locCreateDateStr.' '.$locCreateTimeStr;					
		return $contentDispDateTime;
	}
	
	public static function getSharedByAppendedString($content, $contentTs, $sharedByName, $sharedByEmail)
	{
		$separatorStr = Config::get('app_config.conversation_part_separator');
		// $appendedStr = ucwords($sharedByName).' ('.strtolower($sharedByEmail).') --><br><br>'.$content;
		$appendedStr = "";
		$appendedStr .= '<br>'.$separatorStr.'<br>';
		$appendedStr .= '-->'.ucwords($sharedByName).' ('.strtolower($sharedByEmail).')'.' ['.$contentTs.']';
		$appendedStr .= '<br>'.$content;
		return $appendedStr;
	}
	
	public static function getSentByAppendedString($content, $contentTs, $sentByName, $sentByEmail, $sentToName, $sentToEmail)
	{
		$separatorStr = Config::get('app_config.conversation_part_separator');
		$appendedStr = "";
		/*$appendedStr .= 'From: '.ucwords($sentByName).' ('.strtolower($sentByEmail).')';
		$appendedStr .= '<br>'.'To: '.ucwords($sentToName).' ('.strtolower($sentToEmail).')';
		$appendedStr .= '<br><br>'.$content;*/
		$appendedStr .= '<br>'.$separatorStr.'<br>';
		$appendedStr .= '-->'.ucwords($sentToName).' ('.strtolower($sentToEmail).')'.' ['.$contentTs.']';
		// $appendedStr .= '<br>'.''.ucwords($sentToName).' ('.strtolower($sentToEmail).')';
		$appendedStr .= '<br>'.$content;
		return $appendedStr;
	}
	
	public static function getSentToAppendedString($content, $contentTs, $sentByName, $sentByEmail, $sentToName, $sentToEmail)
	{
		$separatorStr = Config::get('app_config.conversation_part_separator');
		$appendedStr = "";
		/*$appendedStr .= 'From: '.ucwords($sentByName).' ('.strtolower($sentByEmail).')';
		$appendedStr .= '<br>'.'To: '.ucwords($sentToName).' ('.strtolower($sentToEmail).')';
		$appendedStr .= '<br><br>'.$content;*/
		$appendedStr .= '<br>'.$separatorStr.'<br>';
		$appendedStr .= '-->'.ucwords($sentByName).' ('.strtolower($sentByEmail).')'.' ['.$contentTs.']';
		// $appendedStr .= '<br>'.''.ucwords($sentToName).' ('.strtolower($sentToEmail).')';
		$appendedStr .= '<br>'.$content;
		return $appendedStr;
	}
	
	public static function getContentPartReplyAppendedStringForChangeLog($changeLogContentText, $operTs, $operByName, $operByEmail)
	{		
        $isDeletedPostfixText = Config::get('app_config.conversation_part_is_deleted_indicator'); 

        $appendedStr = "";
		// $appendedStr = '<br>'.$isDeletedPostfixText.'<br>';
		// $appendedStr .= '-->'.ucwords($operByName).' ('.strtolower($operByEmail).')'.' ['.$operTs.']';
		// $appendedStr .= '<br>'.$isDeletedPostfixText;
		// $appendedStr .= $changeLogContentText;

		$isDeletedCompiledStr = $isDeletedPostfixText.'<br>';
		$isDeletedCompiledStr .= '-->'.ucwords($operByName).' ('.strtolower($operByEmail).')'.' ['.$operTs.']';
		$isDeletedCompiledStr .= '<br>'.$changeLogContentText;

		$contentText = $isDeletedCompiledStr;

		// $appendedStr .= '<br>';//.$separatorStr.'<br>';
		// $appendedStr .= '-->';
		$appendedStr .= '<br>'.$contentText;
		
		return $appendedStr;
	}
	
	public static function getContentPartReplyAppendedString($contentConversationDetail, $contentText, $contentTs, $sharedByName, $sharedByEmail)
	{
		$repliedToStr = "";
		if(isset($contentConversationDetail))
		{
			$conversationStr = $contentConversationDetail['content'];
			$senderStr = isset($contentConversationDetail['senderBase']) ? $contentConversationDetail['senderBase'] : $contentConversationDetail['sender'];
			$sentAtStr = $contentConversationDetail['sentAt'];

			$replySeparatorStr = Config::get('app_config.conversation_reply_separator');
			if (strpos($conversationStr, $replySeparatorStr) !== false) 
			{
				$conversationRepliesArr = explode($replySeparatorStr, $conversationStr);
				if(count($conversationRepliesArr) > 0)
				{
					$conversationStr = $conversationRepliesArr[0];
				}
			}

	        $isEditedPostfixText = Config::get('app_config.conversation_part_is_edited_indicator'); 
			if (strpos($conversationStr, $isEditedPostfixText) !== false) 
			{
				$conversationEditsArr = explode($isEditedPostfixText, $conversationStr);
				if(count($conversationEditsArr) > 0)
				{
					$conversationStr = $conversationEditsArr[0];
				}
			}

			$dispContentTextLength = 50;
			$strippedContentText = $conversationStr;
			$strippedContentTextLength = strlen($strippedContentText);
			if($strippedContentTextLength > $dispContentTextLength)
			{
				$strippedContentText = substr($strippedContentText, 0, $dispContentTextLength);
				$strippedContentText .= "..";
			}
			else
			{
				$strippedContentText = substr($strippedContentText, 0, $strippedContentTextLength);						
			}
			$repliedToStr .= '<br>'.$replySeparatorStr.'<br>';
			$repliedToStr .= '-->'.$senderStr;//.' ['.$contentTs.']';
			$repliedToStr .= '<br>'.$strippedContentText;
		}

		$baseReplyStr = "";
		$baseReplyStr .= '<br>';//.$separatorStr.'<br>';
		$baseReplyStr .= '-->'.ucwords($sharedByName).' ('.strtolower($sharedByEmail).')'.' ['.$contentTs.']';
		$baseReplyStr .= '<br>'.$contentText;

		$appendedStr = $baseReplyStr.$repliedToStr;

		return $appendedStr;
	}
	
	public static function getContentPartDeleteAppendedString($contentConversationDetail, $operTs, $operByName, $operByEmail)
	{
		$appendedStr = "";
		if(isset($contentConversationDetail))
		{
	        $isDeletedPostfixText = Config::get('app_config.conversation_part_is_deleted_indicator'); 
	        $deletedContentText = Config::get('app_config.conversation_part_is_deleted_content_text'); 
			$separatorStr = Config::get('app_config.conversation_part_separator');

			$conversationStr = $contentConversationDetail['content'];
			$senderStr = isset($contentConversationDetail['senderBase']) ? $contentConversationDetail['senderBase'] : $contentConversationDetail['sender'];
			$sentAtStr = $contentConversationDetail['sentAt'];

			$isDeletedCompiledStr = $isDeletedPostfixText.'<br>';
			$isDeletedCompiledStr .= '-->'.ucwords($operByName).' ('.strtolower($operByEmail).')'.' ['.$operTs.']';
			$isDeletedCompiledStr .= '<br>'.$deletedContentText;

			$contentText = $isDeletedCompiledStr;

			$appendedStr .= '<br>';//.$separatorStr.'<br>';
			$appendedStr .= '-->'.$senderStr.' ['.$sentAtStr.']';
			$appendedStr .= '<br>'.$contentText;
		}
		return $appendedStr;
	}
	
	public static function getContentPartEditAppendedString($contentConversationDetail, $editedText, $operTs, $operByName, $operByEmail)
	{
		$appendedStr = "";
		if(isset($contentConversationDetail))
		{
	        $isEditedPostfixText = Config::get('app_config.conversation_part_is_edited_indicator'); 
	        $editedContentText = Config::get('app_config.conversation_part_is_edited_content_text');
			$separatorStr = Config::get('app_config.conversation_part_separator');

			$conversationStr = $contentConversationDetail['content'];
			$senderStr = isset($contentConversationDetail['senderBase']) ? $contentConversationDetail['senderBase'] : $contentConversationDetail['sender'];
			$sentAtStr = $contentConversationDetail['sentAt'];

			if (strpos($conversationStr, $isEditedPostfixText) === false) 
			{
				$conversationEditsArr = explode($isEditedPostfixText, $conversationStr);
				if(count($conversationEditsArr) > 0)
				{
					$conversationStr = $conversationEditsArr[0];
				}
			}

			$replySeparatorStr = Config::get('app_config.conversation_reply_separator');
			$replyStr = "";
			if (strpos($conversationStr, $replySeparatorStr) !== false) 
			{
				$conversationRepliesArr = explode($replySeparatorStr, $conversationStr);
				if(count($conversationRepliesArr) > 0)
				{
					$conversationStr = $conversationRepliesArr[0];
					if(count($conversationRepliesArr) > 1)
					{
						$replyStr .= '<br>'.$replySeparatorStr;
						$replyStr .= $conversationRepliesArr[1];
					}
				}
			}

			$isEditedCompiledStr = '<br>'.$isEditedPostfixText.'<br>';
			$isEditedCompiledStr .= '-->'.ucwords($operByName).' ('.strtolower($operByEmail).')'.' ['.$operTs.']';
			$isEditedCompiledStr .= '<br>'.$editedContentText;

			$contentText = $editedText.$isEditedCompiledStr.$replyStr;

			$appendedStr .= '<br>';//.$separatorStr.'<br>';
			$appendedStr .= '-->'.$senderStr.' ['.$sentAtStr.']';
			$appendedStr .= '<br>'.$contentText;
		}
		return $appendedStr;
	}
	
	public static function convertKbToMb($sizeInKb)
	{
		$sizeInMb = 0;
		if(isset($sizeInKb) && $sizeInKb > 0)
		{
			$sizeInMb = $sizeInKb/1024;
			$sizeInMb = floor($sizeInMb);
		}
		return $sizeInMb;
	}
	
	public static function getStrippedContentText($contentText) 
	{
		$strippedContentText = $contentText;
		$strippedContentText = strip_tags($strippedContentText);
		$strippedContentText = str_replace(array("\n","\r","&nbsp;"), ' ', $strippedContentText);
		$strippedContentText = preg_replace('!\s+!', ' ', $strippedContentText);
		
		return $strippedContentText;
	}
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateAppuserPassword()
    {
        $randomStr = Str::random(10);
        return $randomStr;
    }
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateRandomAlphaNumericString($strLength)
    {
        $randomStr = Str::random($strLength);
        return $randomStr;
    }
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateRandomNumericString($strLength)
    {
        $minVal = pow(10, ($strLength - 1));
        $maxVal = pow(10, $strLength) - 1;

        $randonNum = rand($minVal, $maxVal);

        return $randonNum;
    }
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generatePremiumCouponCodeString($couponPrefix)
    {
    	$part1 = $couponPrefix;
    	$part2 = CommonFunctionClass::generateRandomAlphaNumericString(8);
    	$part3 = CommonFunctionClass::generateRandomAlphaNumericString(4);
    	$part4 = CommonFunctionClass::generateRandomAlphaNumericString(4);
    	$part5 = CommonFunctionClass::generateRandomAlphaNumericString(4);
    	$part6 = CommonFunctionClass::generateRandomAlphaNumericString(8);

    	$partSeparator = '-';

    	$couponCodeStr  = '';
    	$couponCodeStr .= $part1 . $partSeparator;
    	$couponCodeStr .= $part2 . $partSeparator;
    	$couponCodeStr .= $part3 . $partSeparator;
    	$couponCodeStr .= $part4 . $partSeparator;
    	$couponCodeStr .= $part5 . $partSeparator;
    	$couponCodeStr .= $part6;

        return $couponCodeStr;
    }
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateEnterpriseCouponCodeString($couponPrefix)
    {
    	$part1 = $couponPrefix;
    	$part2 = CommonFunctionClass::generateRandomAlphaNumericString(8);
    	$part3 = CommonFunctionClass::generateRandomAlphaNumericString(4);
    	$part4 = CommonFunctionClass::generateRandomAlphaNumericString(8);
    	$part5 = CommonFunctionClass::generateRandomAlphaNumericString(4);
    	$part6 = CommonFunctionClass::generateRandomAlphaNumericString(8);

    	$partSeparator = '-';

    	$couponCodeStr  = '';
    	$couponCodeStr .= $part1 . $partSeparator;
    	$couponCodeStr .= $part2 . $partSeparator;
    	$couponCodeStr .= $part3 . $partSeparator;
    	$couponCodeStr .= $part4 . $partSeparator;
    	$couponCodeStr .= $part5 . $partSeparator;
    	$couponCodeStr .= $part6;

        return $couponCodeStr;
    }
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateOpenGroupRegistrationCodeString($existingGroupRegCodeArr)
    {
    	$uniqueCodeGenerated = FALSE;
    	$genCode = '';
    	
    	do
    	{
    		$tempGenCode = CommonFunctionClass::generateRandomNumericString(12);

    		if(!in_array($genCode, $existingGroupRegCodeArr))
    		{
    			$uniqueCodeGenerated = TRUE;
    			$genCode = $tempGenCode;
    		}
    	}
    	while(!$uniqueCodeGenerated);

        return $genCode;
    }
	
	public static function getFileSizeInKBFromSizeInBytes($sizeInBytes)
	{
		$sizeInKb = 0;
		if(isset($sizeInBytes) && $sizeInBytes > 0)
		{
			$sizeInKb = $sizeInBytes/1000;
			$sizeInKb = floor($sizeInKb);
		}
		return $sizeInKb;
	}
	
	public static function getFileSizeStrFromSizeInBytes($sizeInBytes)
	{
		$sizeStr = "";
		if(isset($sizeInBytes) && $sizeInBytes > 0)
		{
			$sizeStr = CommonFunctionClass::getHumanFileSizeStr($sizeInBytes, 2);
		}
		return $sizeStr;
	}

	public static function getHumanFileSizeStr($size, $precision = 2) {
	    $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $step = 1024;
	    $i = 0;
	    while (($size / $step) > 0.9) {
	        $size = $size / $step;
	        $i++;
	    }
	    return round($size, $precision).$units[$i];
	}
    
    public static function getDBDateTimeStrFromUTCTimeStamp($utcTs, $offsetInMinutes = NULL)
    {
    	$utcTs = intval($utcTs/1000);
		$locCreateDt = Carbon::createFromTimeStampUTC($utcTs);
		// Log::info('offsetInMinutes : '.$offsetInMinutes.' : locCreateDt : '.$locCreateDt);

		if(isset($offsetInMinutes) && $offsetInMinutes != 0)
		{
			$offsetInMinutes = $offsetInMinutes*-1;
			
			$offsetIsNegative = 0;
			if($offsetInMinutes < 0)
			{
				$offsetIsNegative = 1;
				$offsetInMinutes = $offsetInMinutes*-1;
			}
			
			$offsetHours = 	floor($offsetInMinutes / 60);						
			$offsetMinutes = $offsetInMinutes - ($offsetHours*60);

			// Log::info('offsetHours : '.$offsetHours.' : offsetMinutes : '.$offsetMinutes.' : offsetIsNegative : '.$offsetIsNegative);
			
			// if($offsetIsNegative == 1)
			// {
			// 	if($offsetHours > 0)	
			// 		$locCreateDt = $locCreateDt->subHours($offsetHours);
			// 	if($offsetMinutes > 0)
			// 		$locCreateDt = $locCreateDt->subMinutes($offsetMinutes);		
			// }
			// else
			// {
			// 	if($offsetHours > 0)	
			// 		$locCreateDt = $locCreateDt->addHours($offsetHours);
			// 	if($offsetMinutes > 0)
			// 		$locCreateDt = $locCreateDt->addMinutes($offsetMinutes);				
			// }							
		}

        $contentDBDateStr = $locCreateDt->format('Y-m-d');
        $contentDBTimeStr = $locCreateDt->format('H:i:s');
        $contentDBTimeZoneStr = $locCreateDt->format('T');
        $contentDBTimeZoneStr = substr($contentDBTimeZoneStr, 3);

		// Log::info('contentDBDateStr : '.$contentDBDateStr.' : contentDBTimeStr : '.$contentDBTimeStr.' : contentDBTimeZoneStr : '.$contentDBTimeZoneStr);
		
        $contentDBDateTime = $contentDBDateStr.'T'.$contentDBTimeStr.$contentDBTimeZoneStr;					
		return $contentDBDateTime;
	}
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function generateRetailCloudCalendarEventIdString()
    {
    	$part1 = 'HLT';
    	$part2 = CommonFunctionClass::getCreateTimestamp();
    	$part3 = CommonFunctionClass::generateRandomAlphaNumericString(4);

    	$partSeparator = '';

    	$calendarEventIdStr  = '';
    	$calendarEventIdStr .= $part1 . $partSeparator;
    	$calendarEventIdStr .= $part2 . $partSeparator;
    	$calendarEventIdStr .= $part3;

    	$calendarEventIdStr = strtolower($calendarEventIdStr);

        return $calendarEventIdStr;
    }
}
