<?php
namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContact;
use App\Models\Api\AppuserBroadcast;
use App\Models\Api\AppuserBroadcastMember;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentAttachment;
use Illuminate\Http\Request;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use Carbon\Carbon;
use DB;
use App\Libraries\MailClass;
use App\Libraries\FileUploadClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentManagementClass;
use App\Libraries\ContentDependencyManagementClass;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class AppuserContactController extends Controller
{	
    use CloudMessagingTrait;
    
	public function __construct()
    {
    	
    }
    
    /**
     * Register app user.
     *
     * @return json array
     */
    public function syncContacts()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $emailArr = Input::get('emailArr');
        $contactArr = Input::get('contactArr');
        $nameArr = Input::get('nameArr');
        $loginToken = Input::get('loginToken');
        
        $emailArr = jsonDecodeArrStringIfRequired($emailArr);
        $nameArr = jsonDecodeArrStringIfRequired($nameArr);
		$contactArr = jsonDecodeArrStringIfRequired($contactArr);

        $response = array();

        if($encUserId != "")
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
            
            if(isset($user))
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
                
                $userRegEmail = $user->email;
                
                $respContArr = array();
                for($i=0; $i<count($emailArr); $i++)
                {
					$email = isset($emailArr[$i]) ? $emailArr[$i] : "";
					$name = $nameArr[$i];
					$contactNo = isset($contactArr[$i]) ? $contactArr[$i] : "";

					$orgContactNo = $contactNo;

					$email = sanitizeEmailString($email);
					$contactNo = sanitizeContactNoString($contactNo);
						
					if($name == NULL)
						$name = "";

					// $response['name'] = $name;
					// $response['email'] = $email;
					// $response['userRegEmail'] = $userRegEmail;
					// $response['contactNo'] = $contactNo;
					
					if($email != "" && $userRegEmail != $email && CommonFunctionClass::validateEmailAddress($email))
					{
						$userContact = AppuserContact::ofUser($userId)->ofEmail($email)->first();            
					
						$isEmailRegistered = 0;
						$registeredAppuserId = 0;
	            		$regUser = Appuser::ofEmail($email)->active()->first();
	            		if(isset($regUser))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $regUser->appuser_id;
						}
						else if(isset($contactNo) && $contactNo != '')
						{
		            		$regContactUser = Appuser::ofMappedContact($contactNo)->active()->first();
		            		if(isset($regContactUser))
		            		{
								$isEmailRegistered = 1;
								$registeredAppuserId = $regContactUser->appuser_id;
							}
						}
						
						if(!isset($userContact))
						{
							//Insert
							$userContact = New AppuserContact;
							$userContact->appuser_id = $userId;
							$userContact->email = $email;
						}
						// $response['userContact'] = $userContact;
						// $response['isEmailRegistered'] = $isEmailRegistered;
						// $response['registeredAppuserId'] = $registeredAppuserId;
						
						$userContact->is_srac_regd = $isEmailRegistered;
						$userContact->regd_appuser_id = $registeredAppuserId;
						$userContact->name = $name;
						$userContact->contact_no = $contactNo;
						$userContact->org_contact_no = $orgContactNo;
						$userContact->save();
					}
					else if($contactNo != "")// && CommonFunctionClass::validateEmailAddress($contactNo))
					{
						$userContact = AppuserContact::ofUser($userId)->ofContactNo($contactNo)->first();   

						$isEmailRegistered = 0;
						$registeredAppuserId = 0;

	            		$regContactUser = Appuser::ofMappedContact($contactNo)->active()->first();
	            		if(isset($regContactUser))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $regContactUser->appuser_id;
						}
						
						if(!isset($userContact))
						{
							//Insert
							$userContact = New AppuserContact;
							$userContact->appuser_id = $userId;
							$userContact->email = "";
						}
						// $response['userContact'] = $userContact;
						// $response['isEmailRegistered'] = $isEmailRegistered;
						// $response['registeredAppuserId'] = $registeredAppuserId;
						
						$userContact->is_srac_regd = $isEmailRegistered;
						$userContact->regd_appuser_id = $registeredAppuserId;
						$userContact->name = $name;
						$userContact->contact_no = $contactNo;
						$userContact->org_contact_no = $orgContactNo;
						$userContact->save();
					}		
				}   
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
    
    /**
     * get app user contact list.
     *
     * @return json array
     */
    public function getAppuserContactList()
    {
        set_time_limit(0);

    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $searchStr = Input::get('searchStr');
        $onlySracUsers = Input::get('onlySracUsers');
        $showBlockedUsers = Input::get('showBlockedUsers');
        $loginToken = Input::get('loginToken');
        $performRefresh = Input::get('performRefresh');
        $forShare = Input::get('forShare');
        
        if(!isset($forShare))
        {
			$forShare = 0;
		}

        $response = array();

        if($encUserId != "")
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
            
            if(isset($user))
            {  
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				            
				if(!isset($performRefresh) || $performRefresh * 1 != 1)
					$performRefresh = 0;
					
                $status = 1;
                $userRegEmail = $user->email;
                
            	$userCont = AppuserContact::ofUser($userId)->where('email','<>',$userRegEmail);
                
                $isBlockedValArr = array();
                array_push($isBlockedValArr, 0);
                
                if($showBlockedUsers == 1)
                {
                	array_push($isBlockedValArr, 1);				
				}
				
                $userCont = $userCont->whereIn('is_blocked', $isBlockedValArr);
                	
                if(isset($searchStr) && $searchStr != "")
                {		
                	$userCont = $userCont->where(function($query) use ($searchStr)
							            {
							                $query->where('name','like',"%$searchStr%")
							                      ->orWhere('email','like',"%$searchStr%");
							            });			
				}
				
				$userCont = $userCont->orderBy('name', 'asc');
				if($performRefresh == 0 && $onlySracUsers == 1)
				{
					$userCont = $userCont->where('is_srac_regd', '=', '1');
				}
                
                $userContArr = $userCont->get();

				// Log::info('userContArr Length : '.count($userContArr));
                
                $i = 0;
                $sracContInd = 0;
                $nonSracContInd = 0;
                $appuserSelectList = array();
                $respSracContactArr = array();
                $respNonSracContactArr = array();
                $respSracAppuserIdArr = array();
                foreach ($userContArr as $contIndex => $usrCont) 
                {
					$contId = $usrCont->appuser_contact_id;
					$name = $usrCont->name;
					$email = $usrCont->email;
					$contactNo = $usrCont->contact_no;
					$isRegd = $usrCont->is_srac_regd;
					$isBlocked = $usrCont->is_blocked;
					$registeredAppuserId = $usrCont->regd_appuser_id;

					$name = isset($name) ? trim($name) : NULL;
					$email = isset($email) ? trim($email) : NULL;
					$contactNo = isset($contactNo) ? trim($contactNo) : NULL;

					
					if($performRefresh == 1)
					{
						$isEmailRegistered = 0;
						
						// $userRegistered = NULL;
						// if(isset($email) && $email != '')
						// {
						// 	$userRegistered = Appuser::ofEmail($email)->active()->first();
						// }

	            		$userRegistered = Appuser::forRegisteredAppUserByEmailOrContactNo($email, $contactNo)->active()->first();

	            		if(isset($userRegistered))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $userRegistered->appuser_id;
						}
						// else if(isset($contactNo) && $contactNo != '')
						// {
						// 	$regContactUser = Appuser::ofMappedContact($contactNo)->active()->first();
						// 	if(isset($regContactUser))
						// 	{
						// 		$isEmailRegistered = 1;
						// 		$registeredAppuserId = $regContactUser->appuser_id;
						// 	}
						// }		            		
						
						//if($isRegd != $isEmailRegistered)
						{
							$usrCont->is_srac_regd = $isEmailRegistered;
							$usrCont->regd_appuser_id = $registeredAppuserId;
							$usrCont->save();
						}	
					}
					else
					{
						$isEmailRegistered = $isRegd;
					}	

					// Log::info('contIndex : '.$contIndex.' : name : '.$name.' : email : '.$email.' : contactNo : '.$contactNo.' : isEmailRegistered : '.$isEmailRegistered.' : registeredAppuserId : '.$registeredAppuserId);
					
					if($onlySracUsers == 0 || ($onlySracUsers == 1 && $isEmailRegistered == 1))
					{					
						$contRegAppuserId = $usrCont->regd_appuser_id;

                		$contId = sracEncryptNumberData($contId, $userSession);

						$contDetails = array();
						$contDetails["id"] = $contId;
						$contDetails["email"] = $email;
						$contDetails["name"] = $name;
						$contDetails["isRegd"] = $isEmailRegistered;
						$contDetails["isBlocked"] = $isBlocked;	
						$contDetails["regdId"] = sracEncryptNumberData($contRegAppuserId, $userSession);
						
						if($isEmailRegistered == 1)
						{
							// if(!in_array($contRegAppuserId, $respSracAppuserIdArr))
							{
								if($email == "")
								{
									$appuserDetails = Appuser::byId($contRegAppuserId)->verified()->first();
									if(isset($appuserDetails))
									{
										$email = $appuserDetails->email;
										$email = trim($email);
										$contDetails["email"] = $email;
									}
								}

								if($name != "" && $email != "")
								{
									array_push($respSracAppuserIdArr, $contRegAppuserId);

									$respSracContactArr[$sracContInd++] = $contDetails;	
									$appuserSelectList[$i]["id"] = $contId;
									$appuserSelectList[$i]["text"] = $name." [".$email."]";

									$i++;
								}
							}
						}
						else
							$respNonSracContactArr[$nonSracContInd++] = $contDetails;
					}								
				}      
				$response["sracContRes"] = $respSracContactArr;          
				$response["nonSracContRes"] = $respNonSracContactArr;   
				
				if($forShare == 1)
				{        
					$response["results"] = $appuserSelectList;  
				}          
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
    
    /**
     * share app user content.
     *
     * @return json array
     */
    public function shareContent()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $contentArr = Input::get('contentArr');
        $contactIdArr = Input::get('contactIdArr');
        $broadcastIdArr = Input::get('broadcastIdArr');
        $groupIdArr = Input::get('groupIdArr');
        $emailArr = Input::get('emailArr');
        $isFolder = Input::get('isFolder');
        $inpIsLocked = Input::get('isLocked');
        $loginToken = Input::get('loginToken');
        $inpIsShareEnabled = Input::get('isShareEnabled');
        $encOrgId = "";

     	$oneLineContentText = Input::get('oneLineContentText');
     	
     	$oneLineContentText = strip_tags($oneLineContentText);
        $oneLineContentText = substr($oneLineContentText, 0, 100);
        $oneLineContentText = preg_replace("/&#?[a-z0-9]+;/i"," ",$oneLineContentText);
     	
     	
        $isOneLineQuickShare = Input::get('isOneLineQuickShare');
        if(!isset($isOneLineQuickShare) || $isOneLineQuickShare != 1)
        {
            $isOneLineQuickShare = 0;
            $oneLineContentText = "";
        }

        $performContentRemove = Input::get('performContentRemove');
        if(!isset($performContentRemove) || $performContentRemove != 1)
        {
            $performContentRemove = 0;
        }
        
        if(!isset($inpIsLocked)) {
			$inpIsLocked = Config::get('app_config.default_content_lock_status');
		}
        
        if(!isset($inpIsShareEnabled)) {
			$inpIsShareEnabled = Config::get('app_config.default_content_share_status');
		}

		// Log::info('1 : contentArr : ');
		// Log::info($contentArr);
		
		$contentIdArr = array();
		if(!isset($contentArr))
		{
			// Log::info('!isset($contentArr)');
			$contentArr = array();
			
			$contentIdArr = Input::get('idArr');
       		$contentIdArr = json_decode($contentIdArr);
	   	 	if(!isset($contentIdArr))
	   	 	{
				$contentIdArr = array();
			}
		}
		else
		{
			// Log::info('isset($contentArr)');
			if(!is_array($contentArr))
			{
        		$contentArr = json_decode($contentArr);
        		if(!is_array($contentArr))
        		{
					$contentArr = array();
        		}
			}
		}

		// Log::info('2 : contentArr : ');
		// Log::info($contentArr);

		// Log::info('contentIdArr : ');
		// Log::info($contentIdArr);

		// Log::info('1 : contactIdArr : ');
		// Log::info($contactIdArr);

		if(!isset($contactIdArr))
		{
			$contactIdArr = array();
		}
		else
		{
			if(!is_array($contactIdArr))
			{
        		$contactIdArr = json_decode($contactIdArr);
        		if(!is_array($contactIdArr))
        		{
					$contactIdArr = array();
        		}
			}
		}

		// Log::info('2 : contactIdArr : ');
		// Log::info($contactIdArr);

		// Log::info('1 : broadcastIdArr : ');
		// Log::info($broadcastIdArr);

		if(!isset($broadcastIdArr))
		{
			$broadcastIdArr = array();
		}
		else
		{
			if(!is_array($broadcastIdArr))
			{
        		$broadcastIdArr = json_decode($broadcastIdArr);
        		if(!is_array($broadcastIdArr))
        		{
					$broadcastIdArr = array();
        		}
			}
		}

		// Log::info('2 : broadcastIdArr : ');
		// Log::info($broadcastIdArr);

		if(!isset($groupIdArr))
		{
			$groupIdArr = array();
		}
		else
		{
			if(!is_array($groupIdArr))
			{
        		$groupIdArr = json_decode($groupIdArr);
        		if(!is_array($groupIdArr))
        		{
					$groupIdArr = array();
        		}
			}
		}

		// Log::info('groupIdArr : ');
		// Log::info($groupIdArr);

		if(!isset($emailArr))
		{
			$emailArr = array();
		}
		else
		{
			if(!is_array($emailArr))
			{
        		$emailArr = json_decode($emailArr);
        		if(!is_array($emailArr))
        		{
					$emailArr = array();
        		}
			}
		}

		// Log::info('emailArr : ');
		// Log::info($emailArr);

        $response = array();
        $responeLogsArr = array();
        if($encUserId != "" && (($isOneLineQuickShare == 0 && (count($contentArr) + count($contentIdArr) > 0)) || ($isOneLineQuickShare == 1 && $oneLineContentText != "")) && ((count($contactIdArr)+count($broadcastIdArr)+count($groupIdArr)+count($emailArr)) > 0))
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
        		
        		$contentIdArr = sracDecryptNumberArrayData($contentIdArr, $userSession);
                $contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
                $broadcastIdArr = sracDecryptNumberArrayData($broadcastIdArr, $userSession);
                $groupIdArr = sracDecryptNumberArrayData($groupIdArr, $userSession);
				             
                $status = 1;
                $msg = 'Content sent successfully';

                $isFolderBool = FALSE;
                if($isFolder == 1)
                {
                	$isFolderBool = TRUE;
                }
				
	        	$userConstantObj = AppuserConstant::ofUser($userId)->first();	
				$hasRightToShare = FALSE;
				if(isset($userConstantObj) && isset($userConstantObj->is_srac_share_enabled) && $userConstantObj->is_srac_share_enabled == 1) {
					$hasRightToShare = TRUE;
				}
                
                $sharedByUserEmail = $user->email;             
                $sharedByUserName = $user->fullname;
                
                $j=0;
                $shareUserIdArr = array();
                if($hasRightToShare) {
                	array_push($responeLogsArr, "Has right to share");
					for($i=0; $i<count($contactIdArr); $i++)
	                {
	                	$contactId = $contactIdArr[$i];
                		array_push($responeLogsArr, "contactId[$i] : $contactId");
	                	
	                	if($contactId > 0)
	                	{
							$shareUser = AppuserContact::findOrFail($contactId);
	                	
		                	if(isset($shareUser))
		                	{
        						array_push($responeLogsArr, $shareUser);

		                		$userEmail = $shareUser->email;
		                		$userContactNo = $shareUser->contact_no;

            					$shareAppUser = Appuser::forRegisteredAppUserByEmailOrContactNo($userEmail, $userContactNo)->active()->first();

		                		if(isset($shareAppUser))
		                		{
									$shareUserIdArr[$j] = $shareAppUser->appuser_id;
									$j++;								
								}
							}
						}                	
	                }
	                for($i=0; $i<count($broadcastIdArr); $i++)
	                {
	                	$broadcastId = $broadcastIdArr[$i];
	                	
	                	if($broadcastId > 0)
	                	{
							$broadcastMembers = AppuserBroadcastMember::ofBroadcast($broadcastId)->get();
	                	
		                	if(isset($broadcastMembers) && count($broadcastMembers) > 0)
		                	{
		                		foreach($broadcastMembers as $member)
		                		{
									$shareUserIdArr[$j] = $member->member_appuser_id;
									$j++;
								}
							}
						}                	
	                }
	                for($i=0; $i<count($emailArr); $i++)
	                {
	                	$email = $emailArr[$i];
	                	
	                	if($email != "")
	                	{
	                		$emailUser = Appuser::active()->ofEmail($email)->first();
	                		
	                		if(isset($emailUser))
	                		{
								$shareUserIdArr[$j] = $emailUser->appuser_id;
								$j++;							
							}
						}                	
	                }
	                
	                $shareUserIdArr = array_unique($shareUserIdArr);
				}
        		array_push($responeLogsArr, $shareUserIdArr);
                
                $finalShareUserIdrr = array();
                for($i=0; $i<count($shareUserIdArr); $i++)
                {
					$shareUserId = $shareUserIdArr[$i];
					
					$isSharedUserBlocked = AppuserContact::where('appuser_id', '=', $userId)
											->where('regd_appuser_id', '=', $shareUserId)
											->where('is_blocked', '=', '1')
											->first();
											
					$isUserBlocked = AppuserContact::where('appuser_id', '=', $shareUserId)
											->where('regd_appuser_id', '=', $userId)
											->where('is_blocked', '=', '1')
											->first();
											
					if((!isset($isSharedUserBlocked) && !isset($isUserBlocked)))
					{
						array_push($finalShareUserIdrr, $shareUserId);
					}
				}
        		// $response['finalShareUserIdrr'] = $finalShareUserIdrr;
                
                $j = 0;
                $finalShareGroupIdArr = array();
                $memberIdArr = array();
                $groups = array();
                for($i=0; $i<count($groupIdArr); $i++)
                {
                	$grpId = $groupIdArr[$i];
                	
                	if($grpId > 0)
                	{
						$group = Group::findOrFail($grpId);
	                	if(isset($group))
	                	{
							$isUserGroupMember = GroupMember::isUserGroupMember($grpId, $userId)->first();
	                	
		                	if(isset($isUserGroupMember))
		                	{
								$finalShareGroupIdArr[$j] = $isUserGroupMember->member_appuser_id;
								$memberIdArr[$j] = $isUserGroupMember->member_id;
								$groups[$j] = $group;
								$j++;
							} 
						}
					}		           	
                }

				// Log::info('finalShareUserIdrr : ');
				// Log::info($finalShareUserIdrr);
                
                if(count($finalShareUserIdrr) + count($groups) > 0)
                {
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgKey($user, $encOrgId);
						
					$appUsers = Appuser::whereIn('appuser_id', $finalShareUserIdrr)->get();

					$totalContentSent = 0;

					if($isOneLineQuickShare == 1 && $oneLineContentText != "")
					{
        				$oneLineContentText = urldecode($oneLineContentText);

        				$consIsFolder = TRUE;
        				$consFolderOrGroupId = NULL;
                        $contentAddResponse = $depMgmtObj->quickCreateAppuserArchiveContent($isFolderBool, $consFolderOrGroupId, $oneLineContentText, NULL, NULL, NULL, FALSE);
                        $addedContentId = $contentAddResponse['syncId'];

                        if($addedContentId > 0)
                        {
                        	array_push($contentIdArr, $addedContentId);
                        	$performContentRemove = 1;
                        }
					}
					
					if(count($contentIdArr) > 0)
					{
						$app = app();
		                
		                for($i=0; $i<count($contentIdArr); $i++)
		                {
							$contentId = $contentIdArr[$i];
							
							$content = $depMgmtObj->getContentObject($contentId, $isFolderBool);
							if(isset($content) && $content->is_share_enabled == 1 && (($isFolderBool && $content->is_removed == 0) || !$isFolderBool))
							{
								$hasAttachment = 0;
								$attachmentArr = array();
								
								$contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolderBool);
								if(count($contentAttachments) > 0)
								{
									$hasAttachment = 1;
									foreach($contentAttachments as $attachment)
									{
										array_push($attachmentArr, sracEncryptNumberData($attachment->content_attachment_id, $userSession));
									}
								}
								
				                $contentObj = $app->make('stdClass');
				                $contentObj->id = sracEncryptNumberData($contentId, $userSession);
				                $contentObj->hasAtt = $hasAttachment;
				                $contentObj->attArr = $attachmentArr;
				                
				                array_push($contentArr, $contentObj);
							}	
						}	
					} 
					// Log::info('contentArr : ');
					// Log::info($contentArr);

					$compGeneratedContentArr = array();
					
					for($i=0; $i<count($contentArr); $i++)
	                {
	                	$contObj = $contentArr[$i];
	                	// Log::info('contObj : ');
	                	// Log::info($contObj);
	                	if(isset($contObj->id))
	                	{
	                		$contId = sracDecryptNumberData($contObj->id, $userSession);
		                	$hasAttachment = $contObj->hasAtt;
	                		array_push($responeLogsArr, "contId[$i] : $contId");
		                	
							// Log::info('contId : ');
							// Log::info($contId);

							// Log::info('before send : hasAttachment : ');
							// Log::info($hasAttachment);

		                	$userContent = NULL;
		                	if($isFolder == 1)
		                    	$userContent = AppuserContent::byId($contId)->first();
		                    else
		                    	$userContent = GroupContent::byGroupContentId($contId)->first();
		                    
		                    if(isset($userContent))
			                {
			                	$totalContentSent++;		                	

								// Log::info('totalContentSent : ');
								// Log::info($totalContentSent);

			                	//For Contacts & broadcasts
								$contentText = "";
								if(isset($userContent->content) && $userContent->content != "")
								{
									try
									{
										$contentText = Crypt::decrypt($userContent->content);
									} 
									catch (DecryptException $e) 
									{
										//
									}
								}

								$contentTitle = $userContent->content_title;
			                	
			                	if($contentText != "")
			                	{
									$contentIsLocked = $inpIsLocked;
									if($userContent->is_locked == 1) {
										$contentIsLocked = 1;
									}

									$contentIsShareEnabled = 0;
									if($userContent->is_share_enabled == 1) {
										$contentIsShareEnabled = 1;
									}

									$contentAttachmentsArr = array();
			                		if($hasAttachment == 1)
			                		{
										$attachmentArr = sracDecryptNumberArrayData($contObj->attArr, $userSession);
				                		if(count($attachmentArr) > 0)
				                		{	
				                			if($isFolder == 1)                					
			                    				$contentAttachmentsArr = AppuserContentAttachment::whereIn('content_attachment_id',$attachmentArr)->get();
			                    			else
			                    				$contentAttachmentsArr = GroupContentAttachment::whereIn('content_attachment_id',$attachmentArr)->get();
										}

										// Log::info('before send : contentAttachmentsArr : ');
										// Log::info($contentAttachmentsArr);
									}
									
	        						$response['contentText'] = $contentText;
	        						if($contentTitle === '' || $contentTitle ===null){
	        						    $response['content_title'] = 'No Title';
	        						}else{
	        						    $response['content_title'] = $contentTitle;
	        						}
                                     

									// $appendedContentText = CommonFunctionClass::getSharedByAppendedString($contentText, $sharedByUserName, $sharedByUserEmail);
			                		
			                		if(count($appUsers) > 0)
			                		{
			        					$addedFolderContentResponseArr = $this->addUserContent($appUsers, $userContent, $contentIsLocked, $inpIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $userId, $userSession);

			        					if(count($addedFolderContentResponseArr) > 0)
			        					{
			        						$compGeneratedContentArr = array_merge($compGeneratedContentArr, $addedFolderContentResponseArr);
			        					}
									}
								
									//For Groups
									if(count($groups) > 0)
			                		{
				        				$addedGroupContentResponseArr = $this->addGroupContent($groups, $memberIdArr, $finalShareGroupIdArr, $userContent, $contentIsLocked, $inpIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $userSession);

			        					if(count($addedGroupContentResponseArr) > 0)
			        					{
			        						$compGeneratedContentArr = array_merge($compGeneratedContentArr, $addedGroupContentResponseArr);
			        					}
			                		}

			                		if($performContentRemove == 1)
			                		{
			                			$depMgmtObj->deleteContent($contId, $isFolderBool);
			                		}
								}
			                }     
	                	}
		                	           		
	                }

	                if($totalContentSent > 0)
	                {
                		$status = 1;
	                	$msg = "Content sent";

        				$response['compContentArr'] = json_encode($compGeneratedContentArr);
	                }
	                else
	                {
                		$status = -1;
	                	$msg = "Content could not be sent";
	                }
				}  

        		// $response['responeLogsArr'] = $responeLogsArr;                      
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
    
    public function addUserContent($appUsers, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $sharedByUserId, $userSession)
    {
    	$addedFolderContentResponseArr = array();
		foreach($appUsers as $appUser)
		{
			$userId = $appUser->appuser_id;
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgKey($appUser, "");
            $addedFolderContentId = $depMgmtObj->createSentFolderContent($mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserEmail, $sharedByUserId);

            if($addedFolderContentId > 0)
            {
            	$addedFolderContentResponseObj = array();
            	$addedFolderContentResponseObj['contentId'] = sracEncryptNumberData($addedFolderContentId, $userSession);
            	$addedFolderContentResponseObj['isFolder'] = 1;
            	$addedFolderContentResponseObj['userId'] = sracEncryptNumberData($userId, $userSession);
				$addedFolderContentResponseObj['orgId'] = "";
            	
            	array_push($addedFolderContentResponseArr, $addedFolderContentResponseObj); 
            }
		}
		return $addedFolderContentResponseArr;
	}
    
    public function addGroupContent($groups, $memberIdArr, $appuserIdArr, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $userSession)
    {
    	$addedGroupContentResponseArr = array();
    	$isAdd = 1;
    	
		$user = Appuser::ofEmail($sharedByUserEmail)->first();
		$depMgmtObj = New ContentDependencyManagementClass;
		$depMgmtObj->withOrgKey($user, "");
		foreach($groups as $i => $group)
		{
			$groupId = $group->group_id;
			$memberId = $memberIdArr[$i];
			$appuserId = $appuserIdArr[$i];

            $addedGroupContentId = $depMgmtObj->createSentGroupContent($groupId, $memberId, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentTitle, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail);

            if($addedGroupContentId > 0)
            {
            	$addedGroupContentResponseObj = array();
            	$addedGroupContentResponseObj['contentId'] = sracEncryptNumberData($addedGroupContentId, $userSession);
            	$addedGroupContentResponseObj['isFolder'] = 0;
            	$addedGroupContentResponseObj['groupId'] = sracEncryptNumberData($groupId, $userSession);
            	$addedGroupContentResponseObj['userId'] = sracEncryptNumberData($appuserId, $userSession);
				$addedGroupContentResponseObj['orgId'] = "";

            	array_push($addedGroupContentResponseArr, $addedGroupContentResponseObj); 
            }
		}
		return $addedGroupContentResponseArr;
	}
    
    /**
     * block app user email.
     *
     * @return json array
     */
    public function blockEmail()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $emailArr = Input::get('emailArr');
        $loginToken = Input::get('loginToken');
        
        $emailArr = json_decode($emailArr);

        $response = array();
        if($encUserId != "" || count($emailArr) > 0)
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
            
            if(isset($user))
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
                
                for($i=0; $i<count($emailArr); $i++)
                {
                	$selContactEmail = $emailArr[$i];
            		$selContact = AppuserContact::ofEmail($selContactEmail)->ofUser($userId)->first();
            		
            		if(!isset($selContact))
            		{
						$selContact = New AppuserContact;
						$selContact->name = "";
						$selContact->email = $selContactEmail;
					}
            		   
                	$isEmailRegistered = 0;
                	$registeredAppuserId = 0;
                	$userIsBlocked = 0;

                	$selContactContactNo = $selContact->contact_no;
                	
            		$userRegistered = Appuser::ofEmail($selContactEmail)->active()->first();
            		if(isset($userRegistered))
            		{
						$isEmailRegistered = 1;
						$registeredAppuserId = $userRegistered->appuser_id;
						$selContact->is_blocked = 1;
					}
					else if(isset($selContactContactNo) && $selContactContactNo != '')
					{
						$regContactUser = Appuser::ofMappedContact($selContactContactNo)->active()->first();
	            		if(isset($regContactUser))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $regContactUser->appuser_id;
							$selContact->is_blocked = 1;
						}
					}		
					
					$selContact->is_srac_regd = $isEmailRegistered;
					$selContact->regd_appuser_id = $registeredAppuserId;
					$selContact->save();                              		
                }                        
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
    
    /**
     * block app user contact.
     *
     * @return json array
     */
    public function blockContact()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $contactIdArr = Input::get('contactIdArr');
        $loginToken = Input::get('loginToken');
        
        $contactIdArr = json_decode($contactIdArr);

        $response = array();
        if($encUserId != "" || count($contactIdArr) > 0)
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
				             
                $status = 1;
                
                for($i=0; $i<count($contactIdArr); $i++)
                {
                	$contactId = $contactIdArr[$i];
            		$selContact = AppuserContact::findOrFail($contactId);
            		$selContactEmail = $selContact->email;
            		$selContactContactNo = $selContact->contact_no;
            		   
                	$isEmailRegistered = 0;
                	$registeredAppuserId = 0;
                	$userIsBlocked = 0;
                	
            		$userRegistered = Appuser::ofEmail($selContactEmail)->active()->first();
            		if(isset($userRegistered))
            		{
						$isEmailRegistered = 1;
						$registeredAppuserId = $userRegistered->appuser_id;
						$selContact->is_blocked = 1;
					}
					else if(isset($selContactContactNo) && $selContactContactNo != '')
					{
						$regContactUser = Appuser::ofMappedContact($selContactContactNo)->active()->first();
	            		if(isset($regContactUser))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $regContactUser->appuser_id;
							$selContact->is_blocked = 1;
						}
					}		
					
					$selContact->is_srac_regd = $isEmailRegistered;
					$selContact->regd_appuser_id = $registeredAppuserId;
					$selContact->save();                              		
                }                        
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
    
    /**
     * block app user contact.
     *
     * @return json array
     */
    public function unblockContact()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $contactIdArr = Input::get('contactIdArr');
        $loginToken = Input::get('loginToken');
        
        $contactIdArr = json_decode($contactIdArr);

        $response = array();
        if($encUserId != "" || count($contactIdArr) > 0)
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
				             
                $status = 1;
                
                for($i=0; $i<count($contactIdArr); $i++)
                {
                	$contactId = $contactIdArr[$i];
            		$selContact = AppuserContact::findOrFail($contactId);
            		$selContactEmail = $selContact->email;
            		$selContactContactNo = $selContact->contact_no;
            		   
                	$isEmailRegistered = 0;
                	$registeredAppuserId = 0;
                	
            		$userRegistered = Appuser::ofEmail($selContactEmail)->active()->first();
            		if(isset($userRegistered))
            		{
						$isEmailRegistered = 1;
						$registeredAppuserId = $userRegistered->appuser_id;
					}
					else if(isset($selContactContactNo) && $selContactContactNo != '')
					{
						$regContactUser = Appuser::ofMappedContact($selContactContactNo)->active()->first();
	            		if(isset($regContactUser))
	            		{
							$isEmailRegistered = 1;
							$registeredAppuserId = $regContactUser->appuser_id;
						}
					}			
					
					$selContact->is_srac_regd = $isEmailRegistered;
					$selContact->regd_appuser_id = $registeredAppuserId;
					$selContact->is_blocked = 0;
					$selContact->save();                              		
                }                        
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
    
    /**
     * invite app user contact.
     *
     * @return json array
     */
    public function inviteContact()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $contactIdArr = Input::get('contactIdArr');
        $loginToken = Input::get('loginToken');
        
        $contactIdArr = json_decode($contactIdArr);

        $response = array();
        if($encUserId != "" || count($contactIdArr) > 0)
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
				             
                $status = 1;
                
                for($i=0; $i<count($contactIdArr); $i++)
                {
                	$contactId = $contactIdArr[$i];
            		$selContact = AppuserContact::findOrFail($contactId);
            		$selContactEmail = $selContact->email;
            		$selContactContactNo = $selContact->contact_no;
            		   
                	$isEmailRegistered = 0;
                	$registeredAppuserId = 0;

                	
            		$userRegistered = Appuser::forRegisteredAppUserByEmailOrContactNo($selContactEmail, $selContactContactNo)->active()->first();

            		if(isset($userRegistered))
            		{
						$isEmailRegistered = 1;
						$registeredAppuserId = $userRegistered->appuser_id;
					}
					// else if(isset($selContactContactNo) && $selContactContactNo != '')
					// {
					// 	$regContactUser = Appuser::ofMappedContact($selContactContactNo)->active()->first();
					// 	if(isset($regContactUser))
					// 	{
					// 		$isEmailRegistered = 1;
					// 		$registeredAppuserId = $regContactUser->appuser_id;
					// 	}
					// }
					
					$selContact->is_srac_regd = $isEmailRegistered;
					$selContact->regd_appuser_id = $registeredAppuserId;
					$selContact->save();      
					
					if($isEmailRegistered == 0)
					{
						//Send Invitation Mail
						MailClass::sendInviteContactMail($userId, $selContactEmail);
					}                        		
                }
                        
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
    
    /**
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getAppuserBroadcastList()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $searchStr = Input::get('searchStr');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "")
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
            
            if(isset($user))
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
                
            	$userBroadcast = AppuserBroadcast::ofUser($userId);
            	
            	if(isset($searchStr) && $searchStr != "")
                {		
                	$userBroadcast = $userBroadcast->where(function($query) use ($searchStr)
							            {
							                $query->where('name','like',"%$searchStr%");
							            });			
				}
				
				$userBroadcastArr = $userBroadcast->orderBy('name', 'asc')->get();
                
                $i = 0;
                $bctInd = 0;
                $respBroadcastArr = array();
                
                foreach ($userBroadcastArr as $userBroadcast) 
                {
					$bctId = $userBroadcast->appuser_broadcast_id;
					$name = $userBroadcast->name;
										
					$bctDetails = array();
					$bctDetails["id"] = sracEncryptNumberData($bctId, $userSession);
					$bctDetails["name"] = $name;
            		
            		/*$broadcastMembers = AppuserBroadcastMember::ofBroadcast($bctId)->get();
            		$memberCnt = count($broadcastMembers);*/
            		
            		/*$memberArr = array();
            		$memberInd = 0;
            		foreach ($broadcastMembers as $member) 
                	{
                		$memDetails = array();
                		$memDetails["id"] = $member;
						$memDetails["name"] = $name;
                		
                		$memberArr[$memberInd++] = $memDetails;
                	}*/
					
					//$bctDetails["memberCnt"] = $memberCnt;
					//$bctDetails["memberArr"] = $memberArr;
						
					$respBroadcastArr[$bctInd++] = $bctDetails;			
				}      
				$response["bcastRes"] = $respBroadcastArr;          
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
    
    /**
     * save app user broadcast details.
     *
     * @return json array
     */
    public function saveBroadcastDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        
        $bctId = Input::get('bctId');
        $bctName = Input::get('bctName');
        $memberContIdArr = Input::get('memberArr');
        $loginToken = Input::get('loginToken');
        
        $memberContIdArr = json_decode($memberContIdArr);

        $response = array();

        if($encUserId != "" || count($memberContIdArr) > 0)
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
            
            if(isset($user))
            {  
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bctId = sracDecryptNumberData($bctId, $userSession);
				$memberContIdArr = sracDecryptNumberArrayData($memberContIdArr, $userSession);
				            
                $status = 1;
                $existingMemberContIdArr = array();
                
                if($bctId > 0)
                {
					//Update Bct
            		$userBroadcast = AppuserBroadcast::findOrFail($bctId);
            		$userBroadcastMembers = AppuserBroadcastMember::ofBroadcast($bctId)->get();

					foreach($userBroadcastMembers as $bm)
					{
						$contId = $bm->appuser_contact_id;
						array_push($existingMemberContIdArr, $contId);
					}
				}  
				else
				{
					//Insert Bct
            		$userBroadcast = new AppuserBroadcast;
					$userBroadcast->appuser_id = $userId;
				}         
				$userBroadcast->name = $bctName;   
				$userBroadcast->save();
				
				$serverBroadcastId = $userBroadcast->appuser_broadcast_id;
				
				foreach ($memberContIdArr as $memberContId) 
                {
                	if(!in_array($memberContId, $existingMemberContIdArr))	
                	{
                		$memberAppuser = AppuserContact::ofUserContact($memberContId)->first();
                		
                		if(isset($memberAppuser))
                		{
		            		$selContactEmail = $memberAppuser->email;
		            		$selContactContactNo = $memberAppuser->contact_no;
		            		$memberAppuserId = $memberAppuser->regd_appuser_id;

	            			$userRegistered = Appuser::forRegisteredAppUserByEmailOrContactNo($selContactEmail, $selContactContactNo)->active()->first();
		            		if(isset($userRegistered))
		            		{
								$memberAppuserId = $userRegistered->appuser_id;
								$memberAppuser->regd_appuser_id = $memberAppuserId;
								$memberAppuser->save();
		            		}
                			
                			if($memberAppuserId > 0)
                			{
								//Insert Member
								$bctMember = New AppuserBroadcastMember;
								$bctMember->appuser_broadcast_id = $serverBroadcastId;
								$bctMember->appuser_contact_id = $memberContId;
								$bctMember->member_appuser_id = $memberAppuserId;
								$bctMember->save();
							}							
						}
					}
                }
				
				foreach ($existingMemberContIdArr as $memberContId) 
                {
                	if(!in_array($memberContId, $memberContIdArr))	
                	{
						//Delete Member
            			$bctMember = AppuserBroadcastMember::ofContactId($memberContId)->first();
            			$bctMember->delete();
					}
                }
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
    
    /**
     * save app user broadcast details.
     *
     * @return json array
     */
    public function loadAddEditBroadcastDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $bctId = Input::get('bctId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "")
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bctId = sracDecryptNumberData($bctId, $userSession);
				             
                $status = 1;
                
        		$userBroadcasts = AppuserBroadcast::ofUser($userId);
        		
        		if($bctId > 0)
        		{
        			$userBroadcasts = $userBroadcasts->where('appuser_broadcast_id','<>',$bctId);
				}
        			
        		$userBroadcastsArr = $userBroadcasts->get();
				
				$bctNameArr = array();
				$i = 0;
				foreach($userBroadcastsArr as $broadcast)
				{
					$bctNameArr[$i] = $broadcast->name;
					$i++;	
				}
       			$response['existingBctName'] = $bctNameArr;
       			
				$bctExists = 0;       			
        		if($bctId > 0)
        		{					
            		$editBroadcast = AppuserBroadcast::findOrFail($bctId);
            		$editBroadcastMembers = AppuserBroadcastMember::ofBroadcast($bctId)
		    								->join('appuser_contacts', 'appuser_contacts.regd_appuser_id', '=', 'appuser_broadcast_members.member_appuser_id')
		    								->where('appuser_id','=',$userId)
		    								->orderBy('name', 'ASC')
            								->get();
            		
            		if(isset($editBroadcast))
            		{
						$bctExists = 1;
						$bctMemberCnt = count($editBroadcastMembers);
						
						$bctDetails["name"] = $editBroadcast->name;
						$bctDetails["memberCnt"] = $bctMemberCnt;
						
						if($bctMemberCnt > 0)
            			{
            				$memberArr = array();
            				foreach($editBroadcastMembers as $bctMember)
            				{
            					$memberDetails = array();
            					$memberDetails["id"] = sracEncryptNumberData($bctMember->appuser_contact_id, $userSession);
            					$memberDetails["name"] = $bctMember->name;
            					$memberDetails["email"] = $bctMember->email;
            					
								array_push($memberArr, $memberDetails);
							}
            				$bctDetails["members"] = $memberArr;
            			}
       					$response['bctDetails'] = $bctDetails;
					}
				}
       			$response['bctExists'] = $bctExists;
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
    
    /**
     * rename group.
     *
     * @return json array
     */
    public function performBroadcastRename()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $bcastId = Input::get('bctId');
        $bcastName = Input::get('bctName');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $bcastId > 0)
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bcastId = sracDecryptNumberData($bcastId, $userSession);
				  
                if($bcastId > 0)
                {
            		$broadcast = AppuserBroadcast::findOrFail($bcastId);
            		if(isset($broadcast))
            		{            			
        				$status = 1;
            								
						$broadcast->name = $bcastName;   
						$broadcast->save();	               			
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
    
    /**
     * remove group user.
     *
     * @return json array
     */
    public function removeBroadcastUser()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $bcastId = Input::get('bctId');
        $contactId = Input::get('contactId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $bcastId != "")
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bcastId = sracDecryptNumberData($bcastId, $userSession);
				$contactId = sracDecryptNumberData($contactId, $userSession);
				  
                if($bcastId > 0 && $contactId != $userId)
                {    
            		$broadcast = AppuserBroadcast::byId($bcastId)->first();
            		
            		if(isset($broadcast))
            		{            			
        				$status = 1;
            			$broadcastUser = AppuserBroadcastMember::ofBroadcast($bcastId)->ofContactId($contactId)->first();
        				
        				if(isset($broadcastUser))
        				{
							$broadcastUser->delete();
						}		           			
            				               			
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
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
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
    
    /**
     * add member to Broadcast.
     *
     * @return json array
     */
    public function addBroadcastMember()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $bcastId = Input::get('bctId');
        $memberContactIdArr = Input::get('memberArr');
        $loginToken = Input::get('loginToken');
        
        if(!isset($memberContactIdArr))
        {
        	$memberContactIdArr = array();
        }
        else
        {
        	if(!is_array($memberContactIdArr))
        	{
        		$memberContactIdArr = json_decode($memberContactIdArr);
        	}
        }
        

        $response = array();
        if($encUserId != "" && count($memberContactIdArr) > 0 && $bcastId != "")
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
            
            if(isset($user))
            {  
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bcastId = sracDecryptNumberData($bcastId, $userSession);
				$memberContactIdArr = sracDecryptNumberArrayData($memberContactIdArr, $userSession);
				      		  
        		$broadcast = AppuserBroadcast::findOrFail($bcastId);
        		
        		if(isset($broadcast))
        		{       			
    				$status = 1;
    				foreach ($memberContactIdArr as $memberContactId) 
	                {
	                	$broadcastUser = AppuserBroadcastMember::ofBroadcast($bcastId)->ofContactId($memberContactId)->first();
    				
	    				if(!isset($broadcastUser))
	    				{
	                		$memberAppuser = AppuserContact::findOrFail($memberContactId);
	                		
	                		if(isset($memberAppuser) && $memberAppuser->regd_appuser_id > 0)
	                		{
	                			$memberAppuserId = $memberAppuser->regd_appuser_id;
	                			
								//Insert Member
								$bctMember = New AppuserBroadcastMember;
								$bctMember->appuser_broadcast_id = $bcastId;
								$bctMember->appuser_contact_id = $memberContactId;
								$bctMember->member_appuser_id = $memberAppuserId;
								$bctMember->save();								
							}
						}
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
    
    /**
     * remove group user.
     *
     * @return json array
     */
    public function deleteBroadcast()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $bcastId = Input::get('bctId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $bcastId != "")
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
            
            if(isset($user))
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
				$bcastId = sracDecryptNumberData($bcastId, $userSession);
				
            	$broadcast = AppuserBroadcast::findOrFail($bcastId);
        		
        		if(isset($broadcast))
        		{  
        			$status = 1;
	                AppuserBroadcastMember::ofBroadcast($bcastId)->delete();        			
        			$broadcast->delete();
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