<?php
namespace App\Http\Traits;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentAttachment;
use App\Libraries\CommonFunctionClass;
use Illuminate\Http\Request;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use URL;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use OneSignal;
use \Exception as Exception;
use Illuminate\Support\Facades\Log;

trait CloudMessagingTrait 
{
    public function sendEntryAddMessageToDevice($userId, $contentId, $sharedByUserEmail)
    {
    	$isSilent = FALSE;
        $sendStatus = $this->sendRelativeEntryAddMessageToDevice($isSilent, $userId, $contentId, $sharedByUserEmail);
    	return $sendStatus;					
	}
	
    public function sendEntryAddSilentMessageToDevice($userId, $contentId, $sharedByUserEmail)
    {
    	$isSilent = TRUE;
        $sendStatus = $this->sendRelativeEntryAddMessageToDevice($isSilent, $userId, $contentId, $sharedByUserEmail);
    	return $sendStatus;					
	}

    public function sendRelativeEntryAddMessageToDevice($isSilent, $userId, $contentId, $sharedByUserEmail)
    {
    	// Log::info('sendRelativeEntryAddMessageToDevice : userId : '.$userId.' : contentId : '.$contentId.' : sharedByUserEmail : '.$sharedByUserEmail);
    	try
    	{
	    	$sendStatus = 0;
	    	if($userId > 0)
	    	{
	    		$user = Appuser::byId($userId)->first();
	            
	            if(isset($user) )
	            {
	            	$orgId = 0;
	       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);
	       			// $userTokens = CommonFunctionClass::getMappedUserLoginTokenDetails($userId);
	                $contentDetails = array();
	                $responseData = array();
	                    
	                $isFolder = TRUE;
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgKey($user, "");
	                $userContent = $depMgmtObj->getContentObject($contentId, $isFolder);

    				// Log::info('sendRelativeEntryAddMessageToDevice : count($userTokens) : '.(count($userTokens)));

	                if(count($userTokens) > 0 && isset($userContent))
	                {

						$contentTitle = $userContent->content_title;

						foreach($userTokens as $token)
			            {
			            	$userSession = $token['userSession'];

			            	$attachmentsArr = array();
		                	$userContentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
		                	
		                	if(count($userContentAttachments) > 0)
		                	{
		                		$i = 0;
		                    	$performDownload = 1;
								foreach($userContentAttachments as $contentAttachment)
								{
		               				if($contentAttachment->att_cloud_storage_type_id == 0)
									{
										$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
									}
									else
									{
										$attachmentUrl = $contentAttachment->cloud_file_url; 
									}

									$attachmentsArr[$i]['name'] = $contentAttachment->filename;
			                        $attachmentsArr[$i]['pathname'] = $contentAttachment->server_filename;
			                        $attachmentsArr[$i]['size'] = $contentAttachment->filesize;
		                            $attachmentsArr[$i]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
		                            $attachmentsArr[$i]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
		                            $attachmentsArr[$i]['cloudFileId'] = $contentAttachment->cloud_file_id;
		                            $attachmentsArr[$i]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
		                            $attachmentsArr[$i]['attCreateTs'] = $contentAttachment->create_ts;
		                            $attachmentsArr[$i]['attUpdateTs'] = $contentAttachment->update_ts;
			                        $attachmentsArr[$i]['url'] = $attachmentUrl;
			                        $attachmentsArr[$i]['performDownload'] = $performDownload;
		                        	$attachmentsArr[$i]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
									$i++;
								}
							}
							$attachmentCnt = count($attachmentsArr);
							
		                	$userContentTags = $depMgmtObj->getContentTags($contentId, $userId, $isFolder);
		                	$tagsArr = array();
		                	foreach($userContentTags as $contTag)
		                	{
								$tagId = $contTag->tag_id;
		                		$tag = $depMgmtObj->getTagObject($tagId);
		                		$tagName = "";
		                		if(isset($tag))
		                		{
									$tagName = $tag->tag_name;
		                		
									$tagObj = array();
									$tagObj['id'] = sracEncryptNumberData($tagId, $userSession);
									$tagObj['name'] = $tagName;
									
									array_push($tagsArr, $tagObj);
								}
							}
							$tagCnt = count($tagsArr);
							
							if(!isset($sharedByUserEmail))
								$sharedByUserEmail = "";
							
							$sharedByUserName = "";
							if($sharedByUserEmail != "")
							{
								$sharedByUserDetails = Appuser::ofEmail($sharedByUserEmail)->first();
								if(isset($sharedByUserDetails))
									$sharedByUserName = $sharedByUserDetails->fullname;
							}

							$contentDetails['contId'] = sracEncryptNumberData($userContent->appuser_content_id, $userSession);
							$contentDetails['colorCode'] = $userContent->color_code;
							$contentDetails['isLocked'] = $userContent->is_locked;
							$contentDetails['isShareEnabled'] = $userContent->is_share_enabled;
							$contentDetails['remindBeforeMillis'] = $userContent->remind_before_millis;
							$contentDetails['repeatDuration'] = $userContent->repeat_duration;
							$contentDetails['isCompleted'] = $userContent->is_completed;
							$contentDetails['isSnoozed'] = $userContent->is_snoozed;
							$contentDetails['reminderTimestamp'] = isset($userContent->reminder_timestamp) ? $userContent->reminder_timestamp : 0;
				            $contentDetails['content'] = utf8_encode(Crypt::decrypt($userContent->content));
				            if($userContent->content_title === '' || $userContent->content_title ===null){
								$contentDetails['content_title'] = 'No Title';
							}else{
								$contentDetails['content_title'] = $userContent->content_title;
							}
				            // $contentDetails['content_title'] = $userContent->content_title;
				            $contentDetails['encContent'] = rawurlencode(Crypt::decrypt($userContent->content));
							$contentDetails['reloadContent'] = 1;
				            $contentDetails['contentTypeId'] = $userContent->content_type_id;
				            $contentDetails['folderId'] = sracEncryptNumberData($userContent->folder_id, $userSession);
				            $contentDetails['sourceId'] = sracEncryptNumberData($userContent->source_id, $userSession);
				            $contentDetails['createTimestamp'] = $userContent->create_timestamp;
				            $contentDetails['updateTimestamp'] = $userContent->update_timestamp;
				            $contentDetails['fromTimestamp'] = $userContent->from_timestamp;
				            $contentDetails['toTimestamp'] = $userContent->to_timestamp;
		                    $contentDetails['isContent'] = 1;
		                    $contentDetails['isFolder'] = 1;
		                    $contentDetails['attachmentCnt'] = $attachmentCnt;
		                    $contentDetails['attachments'] = $attachmentsArr;
		                    $contentDetails['sharedByName'] = $sharedByUserName;
		                    $contentDetails['sharedByEmail'] = $userContent->shared_by_email;
		                    $contentDetails['contentIsMarked'] = $userContent->is_marked;
		                    $contentDetails['tagCnt'] = $tagCnt;
		                    $contentDetails['tags'] = $tagsArr;
		                	$contentDetails['isRemoved'] = $userContent->is_removed;
		                	$contentDetails['removedAt'] = $userContent->removed_at;
		                	$contentDetails['syncWithCloudCalendarGoogle'] = $userContent->sync_with_cloud_calendar_google;
		                	$contentDetails['syncWithCloudCalendarOnedrive'] = $userContent->sync_with_cloud_calendar_onedrive;
		                    
		                    $responseData = $contentDetails;
			                
			                $contentTypeText = $depMgmtObj->getContentTypeText($userContent->content_type_id);
							

			                $notifText = "";
			                if($isSilent == FALSE)
			                {
								if($sharedByUserEmail != "" || $sharedByUserName != "")
			                    {
			                    	if($sharedByUserName != "")
										$notifText .= " $sharedByUserName";
									if($sharedByUserEmail != "")
										$notifText .= " <$sharedByUserEmail>";
								}
								
								// $notifText .= " - New $contentTypeText received";
								$notifText .= " - $contentTitle";
							}

    						// Log::info('sendRelativeEntryAddMessageToDevice : notifText : '.$notifText.' : token : '.$token['token']);
			    
							$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
						}
	            	}
	            }
	    	}	
    	}
        catch (Exception $e) 
        {
           // Log::info('sendRelativeEntryAddMessageToDevice : Error occured : ');
           // Log::info($e);
        }
	    	
    	return $sendStatus;					
	}
	
    public function sendGroupEntryAddMessageToDevice($userId, $contentId, $isAdd, $sharedByUserEmail, $forRestore = FALSE, $consOpCode = NULL)
    {
    	// Log::info('sendGroupEntryAddMessageToDevice : userId : '.$userId.' : contentId : '.$contentId);
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$orgId = 0;            	
       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);
                $contentDetails = array();
                $responseData = array();
                    
                $isFolder = FALSE;
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $grpContent = $depMgmtObj->getContentObject($contentId, $isFolder);

                if(count($userTokens) > 0 && isset($grpContent))
                {
					$contentTitle = $grpContent->content_title;
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$attachmentsArr = array();
	                	$groupContentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
	                	if(count($groupContentAttachments) > 0)
	                	{
	                		$i = 0;
	                    	$performDownload = 1;
							foreach($groupContentAttachments as $contentAttachment)
							{
	               				if($contentAttachment->att_cloud_storage_type_id == 0)
								{
									$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
								}
								else
								{
									$attachmentUrl = $contentAttachment->cloud_file_url; 
								}

								$attachmentsArr[$i]['name'] = $contentAttachment->filename;
		                        $attachmentsArr[$i]['pathname'] = $contentAttachment->server_filename;
		                        $attachmentsArr[$i]['size'] = $contentAttachment->filesize;
	                            $attachmentsArr[$i]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
	                            $attachmentsArr[$i]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
	                            $attachmentsArr[$i]['cloudFileId'] = $contentAttachment->cloud_file_id;
	                            $attachmentsArr[$i]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
	                            $attachmentsArr[$i]['attCreateTs'] = $contentAttachment->create_ts;
	                            $attachmentsArr[$i]['attUpdateTs'] = $contentAttachment->update_ts;
		                        $attachmentsArr[$i]['url'] = $attachmentUrl;
		                        $attachmentsArr[$i]['performDownload'] = $performDownload;
	                        	$attachmentsArr[$i]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
								$i++;
							}
						}
						$attachmentCnt = count($attachmentsArr);
						
	                	$userContentTags = $depMgmtObj->getContentTags($contentId, $userId, $isFolder);
	                	$tagsArr = array();
	                	foreach($userContentTags as $contTag)
	                	{
							$tagId = $contTag->tag_id;
	                		$tag = $depMgmtObj->getTagObject($tagId);
	                		$tagName = "";
	                		if(isset($tag))
	                		{
								$tagName = $tag->tag_name;
	                		
								$tagObj = array();
								$tagObj['id'] = sracEncryptNumberData($tagId, $userSession);
								$tagObj['name'] = $tagName;
								
								array_push($tagsArr, $tagObj);
							}
						}
						$tagCnt = count($tagsArr);
						
						if(!isset($sharedByUserEmail))
							$sharedByUserEmail = "";
						
						$sharedByUserName = "";
						if(isset($sharedByUserEmail) && $sharedByUserEmail != "")
						{
							$sharedByUserDetails = Appuser::ofEmail($sharedByUserEmail)->first();
							if(isset($sharedByUserDetails))
								$sharedByUserName = $sharedByUserDetails->fullname;
						}
	                    
	                    $contentText = "";          
	                   	if(isset($grpContent->content) && $grpContent->content != "")
	                   	{
	                   		try
	                   		{
							    $contentText = Crypt::decrypt($grpContent->content);
							  //  $contentText = rawurlencode($contentText);
							} 
							catch (DecryptException $e) 
							{
								
							}
						}

						$contentDetails['contId'] = sracEncryptNumberData($grpContent->group_content_id, $userSession);
						$contentDetails['colorCode'] = $grpContent->color_code;
						$contentDetails['isLocked'] = $grpContent->is_locked;
						$contentDetails['isShareEnabled'] = $grpContent->is_share_enabled;
						$contentDetails['remindBeforeMillis'] = $grpContent->remind_before_millis;
						$contentDetails['repeatDuration'] = $grpContent->repeat_duration;
						$contentDetails['isCompleted'] = $grpContent->is_completed;
						$contentDetails['isSnoozed'] = $grpContent->is_snoozed;
						$contentDetails['reminderTimestamp'] = isset($grpContent->reminder_timestamp) ? $grpContent->reminder_timestamp : 0;
						$contentDetails['content'] = utf8_encode(Crypt::decrypt($grpContent->content));
						if($grpContent->content_title === '' || $grpContent->content_title ===null){
							$contentDetails['content_title'] = 'No Title';
						}else{
							$contentDetails['content_title'] = $grpContent->content_title;
						}
				        // $contentDetails['content_title'] = $grpContent->content_title;
				        $contentDetails['encContent'] = rawurlencode(Crypt::decrypt($grpContent->content));
						$contentDetails['reloadContent'] = 1;
			            $contentDetails['contentTypeId'] = $grpContent->content_type_id;
			            $contentDetails['groupId'] = sracEncryptNumberData($grpContent->group_id, $userSession);
			            $contentDetails['createTimestamp'] = $grpContent->create_timestamp;
			            $contentDetails['updateTimestamp'] = $grpContent->update_timestamp;
			            $contentDetails['fromTimestamp'] = $grpContent->from_timestamp;
			            $contentDetails['toTimestamp'] = $grpContent->to_timestamp;
	                	$contentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
	                	$contentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
	                    $contentDetails['isContent'] = 1;
	                    $contentDetails['isFolder'] = 0;
	                    $contentDetails['attachmentCnt'] = $attachmentCnt;
	                    $contentDetails['attachments'] = $attachmentsArr;
	                    $contentDetails['sharedByName'] = $sharedByUserName;
	                    $contentDetails['sharedByEmail'] = $sharedByUserEmail;
	                    $contentDetails['contentIsMarked'] = $grpContent->is_marked;
	                    $contentDetails['isAdd'] = $isAdd;
	                    $contentDetails['tagCnt'] = $tagCnt;
	                    $contentDetails['tags'] = $tagsArr;

	                    $responseData = $contentDetails;
		                
		                $contentTypeText = $depMgmtObj->getContentTypeText($grpContent->content_type_id);
		                $groupName = $depMgmtObj->getGroupName($grpContent->group_id);
	                    $notifText = "$groupName";
	                    if($sharedByUserEmail != "")
	                    {
							$notifText .= " - ";
	                    	if($sharedByUserName != "")
								$notifText .= " $sharedByUserName";
							$notifText .= " <$sharedByUserEmail>";
						}
						// $notifText .= " - New $contentTypeText received";
						$notifText .= " - $contentTitle";

						if(isset($consOpCode) && trim($consOpCode) != "")
						{
							$validOpCodeArr = Config::get("app_config.validContentPushNotifOpCodeArr");
							if(in_array($consOpCode, $validOpCodeArr))
							{
								$opCodeTagChange = Config::get("app_config.content_push_notif_op_code_tag_change");
								if($consOpCode == $opCodeTagChange)
								{
									$notifText = "Content tag(s) changed";
								}
							}								
						}

						if(isset($forRestore) && $forRestore == 1)
						{
							$notifText = "";
						}

    					// Log::info('sendGroupEntryAddMessageToDevice : notifText : '.$notifText.' : token : '.$token['token']);

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendGroupDeletedMessageToDevice($userId, $delGrpId, $delGrpName, $sharedByUserEmail, $currLoginToken = NULL)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	if(isset($currLoginToken) && $currLoginToken != "")
	       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
	       		else
	       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);                

                if(isset($userTokens) && count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

	                	$responseData = array();
	                    $responseData['isContent'] = 0;
	                    $responseData['isAction'] = 1;
	                    $responseData['delGroupId'] = sracEncryptNumberData($delGrpId, $userSession);
	                    $responseData['delGroupName'] = $delGrpName;
	                    $responseData['sharedByEmail'] = $sharedByUserEmail;
		                
	            		$notifText = "$delGrpName - $sharedByUserEmail has removed you from group";

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
					}				
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendGroupRestorationMessageToDevice($isPreRestore, $userId, $grpId, $grpName, $sharedByUserEmail, $currLoginToken = NULL)
    {
		$sendStatusStr = "";
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	if(isset($currLoginToken) && $currLoginToken != "")
	       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
	       		else
	       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);                

                if(isset($userTokens) && count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                	$notifText = "";
	                	if($isPreRestore)
	                	{
		                    $responseData['isContent'] = 0;
		                    $responseData['isAction'] = 1;
		                    $responseData['delGroupId'] = sracEncryptNumberData($grpId, $userSession);
		                    $responseData['delGroupName'] = $grpName;
		                    $responseData['sharedByEmail'] = $sharedByUserEmail;
			                
		            		$notifText = $grpName." - $sharedByUserEmail has initiated group data restore. The current group data would be deleted for now.";
	                	}
	                	else
	                	{
	                		$depMgmtObj = New ContentDependencyManagementClass;
		                	$depMgmtObj->withOrgKey($user, "");
				            $groupMember = $depMgmtObj->getGroupMemberObject($grpId);
				           
				            if(isset($groupMember))
		    				{
				    			$groupPhotoUrl = "";
				    			$groupPhotoThumbUrl = "";
								$photoFilename = $groupMember->img_server_filename;
				    			if(isset($photoFilename) && $photoFilename != "")
				    			{
				    				$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl(0, $photoFilename);
				    				$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl(0, $photoFilename);
								}
								else
								{
									$photoFilename = "";
								}

			                    $responseData['isContent'] = 0;
			                    $responseData['isAction'] = 1;
			                    $responseData['addGroupId'] = sracEncryptNumberData($grpId, $userSession);
			                    $responseData['addGroupName'] = $grpName;
				            	$responseData['description'] = $groupMember->description;
					            $responseData['photoUrl'] = $groupPhotoUrl;
					            $responseData['photoThumbUrl'] = $groupPhotoThumbUrl;
								$responseData["photoFilename"] = $photoFilename;   
			                    $responseData['isUserAdmin'] = $groupMember->is_admin;
					            $responseData['hasPostRight'] = 1;
				            	$responseData['isFavorited'] = $groupMember->is_favorited;
				            	$responseData['isGrpLocked'] = $groupMember->is_locked;
			                    $responseData['sharedByEmail'] = $sharedByUserEmail;
			                    $responseData['isRename'] = 0;
					            $responseData['isTwoWay'] = $groupMember->is_two_way;
			                    $responseData['allocKb'] = $groupMember->allocated_space_kb;
			                    $responseData['usedKb'] = $groupMember->used_space_kb;
			                
				                $notifText = $grpName." - $sharedByUserEmail has restored the group data";
							}
	                	}

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
						// $sendStatusStr = " :::: --------- :::: token : ".$token." : notifText : ".$notifText." : grpId : ".$grpId." : userId : ".$userId;//." : sendStatus : ".$sendStatus;
					}				
            	}
            }
    	}	
    	return $sendStatusStr;					
	}
	
    public function sendGroupRestorationFailedMessageToDevice($userId, $grpId, $grpName, $sharedByUserEmail, $currLoginToken = NULL)
    {
		$sendStatusStr = "";
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	if(isset($currLoginToken) && $currLoginToken != "")
	       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
	       		else
	       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);                

                if(isset($userTokens) && count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                	$notifText = "";
	            		$depMgmtObj = New ContentDependencyManagementClass;
	                	$depMgmtObj->withOrgKey($user, "");
			            $groupMember = $depMgmtObj->getGroupMemberObject($grpId);
			           
			            if(isset($groupMember))
	    				{
			    			$groupPhotoUrl = "";
			    			$groupPhotoThumbUrl = "";
							$photoFilename = $groupMember->img_server_filename;
			    			if(isset($photoFilename) && $photoFilename != "")
			    			{
			    				$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl(0, $photoFilename);
			    				$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl(0, $photoFilename);
							}
							else
							{
								$photoFilename = "";
							}

		                    $responseData['isContent'] = 0;
		                    $responseData['isAction'] = 1;
		                    $responseData['addGroupId'] = sracEncryptNumberData($grpId, $userSession);
		                    $responseData['addGroupName'] = $grpName;
			            	$responseData['description'] = $groupMember->description;
				            $responseData['photoUrl'] = $groupPhotoUrl;
				            $responseData['photoThumbUrl'] = $groupPhotoThumbUrl;
							$responseData["photoFilename"] = $photoFilename;   
		                    $responseData['isUserAdmin'] = $groupMember->is_admin;
				            $responseData['hasPostRight'] = 1;
			            	$responseData['isFavorited'] = $groupMember->is_favorited;
			            	$responseData['isGrpLocked'] = $groupMember->is_locked;
		                    $responseData['sharedByEmail'] = $sharedByUserEmail;
		                    $responseData['isRename'] = 0;
				            $responseData['isTwoWay'] = $groupMember->is_two_way;
		                    $responseData['allocKb'] = $groupMember->allocated_space_kb;
		                    $responseData['usedKb'] = $groupMember->used_space_kb;
		                
			                $notifText = $grpName." - $sharedByUserEmail the group data has been rolled back";
						}

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
						// $sendStatusStr = " :::: --------- :::: token : ".$token." : notifText : ".$notifText." : grpId : ".$grpId." : userId : ".$userId;//." : sendStatus : ".$sendStatus;
					}				
            	}
            }
    	}	
    	return $sendStatusStr;					
	}
	
    public function sendGroupAddedMessageToDevice($userId, $grpId, $isRename, $sharedByUserEmail, $currLoginToken = NULL, $isAddOp = NULL, $oldGroupName = NULL)
    {
    	$sendStatus = 0;
    		
    	if(!isset($isAddOp))
    		$isAddOp = 0;
    		
    	if(!isset($oldGroupName))
    		$oldGroupName = "";
    		
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	if(isset($currLoginToken) && $currLoginToken != "")
	       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
	       		else
	       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);
                
                if(isset($userTokens) && count($userTokens)>0)
                {
		            $depMgmtObj = New ContentDependencyManagementClass;
                	$depMgmtObj->withOrgKey($user, "");
		            $groupMember = $depMgmtObj->getGroupMemberObject($grpId);
		           
		            if(isset($groupMember))
    				{
		    			$groupPhotoUrl = "";
		    			$groupPhotoThumbUrl = "";
						$photoFilename = $groupMember->img_server_filename;
		    			if(isset($photoFilename) && $photoFilename != "")
		    			{
		    				$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl(0, $photoFilename);
		    				$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl(0, $photoFilename);
						}
						else
						{
							$photoFilename = "";
						}

						foreach($userTokens as $token)
			            {
		            		$userSession = $token['userSession'];
	                	
		                	$responseData = array();
		                    $responseData['isContent'] = 0;
		                    $responseData['isAction'] = 1;
		                    $responseData['addGroupId'] = sracEncryptNumberData($grpId, $userSession);
		                    $responseData['addGroupName'] = $groupMember->name;
			            	$responseData['description'] = $groupMember->description;
				            $responseData['photoUrl'] = $groupPhotoUrl;
				            $responseData['photoThumbUrl'] = $groupPhotoThumbUrl;
							$responseData["photoFilename"] = $photoFilename;   
		                    $responseData['isUserAdmin'] = $groupMember->is_admin;
				            $responseData['hasPostRight'] = 1;
			            	$responseData['isFavorited'] = $groupMember->is_favorited;
			            	$responseData['isGrpLocked'] = $groupMember->is_locked;
		                    $responseData['sharedByEmail'] = $sharedByUserEmail;
		                    $responseData['isRename'] = $isRename;
				            $responseData['isTwoWay'] = $groupMember->is_two_way;
		                    $responseData['allocKb'] = $groupMember->allocated_space_kb;
		                    $responseData['usedKb'] = $groupMember->used_space_kb;
		                
			                $notifText = "";
			                if($isAddOp == 1)
			                {
								if($isRename == 1)	
			            			$notifText = $oldGroupName." has been renamed to ".$groupMember->name;
			            		else
			            			$notifText = $groupMember->name." - $sharedByUserEmail has added you to group";
							}

							$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
						}
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendGroupEntryDeletedMessageToDevice($userId, $delGrpContId, $delGrpName, $sharedByUserEmail)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);
                
                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession']; 

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                    $responseData['isAction'] = 1;
	                    $responseData['delGroupContentId'] = sracEncryptNumberData($delGrpContId, $userSession);
	                    $responseData['delGroupName'] = $delGrpName;
	                    $responseData['sharedByEmail'] = $sharedByUserEmail;
		              
	                	$notifText = "$delGrpName - $sharedByUserEmail - has deleted content from group";

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendMessageToDevice($userId, $message, $imgUrl)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);

                if(count($userTokens) > 0 && $message != "")
                {
					$notifDetails = array();
                    $notifDetails['isContent'] = 0;
                    $notifDetails['isAction'] = 0;
                    $notifDetails['msg'] = $message;
					$notifDetails['imgUrl'] = $imgUrl;
					
					$notifText = $message;
					$notifImg = $imgUrl;

					foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendFcmToDevice($token, $notifDetails, $notifText, $notifImg);
					}
            	}
            }
    	}
    	return $sendStatus;					
	}
	
    public function sendOtherLoginPerformedMessageToDevice($userId, $loginToken)
    {
    	$sendStatus = 0;
    	
        if($loginToken != "")
        {
            $responseData['isContent'] = 0;
            $responseData['isAction'] = 1;
            $responseData['performLogout'] = 1;
            
            $this->sendFcmToDevice($loginToken, $responseData, NULL);
    	}
          
    	return $sendStatus;					
	}

    public function sendUserQuotaToDevice($userId, $currLoginToken)
    {    	
    	$sendStatus = 0;    	
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $userConstant = $depMgmtObj->getUserConstantObject();

                if(count($userTokens) > 0 && isset($userFolder))
                {	
		            $dbSize = 0;		            
		            if($userConstant->attachment_kb_allotted != null)  
		                $attachmentSpaceAllotted = $userConstant->attachment_kb_allotted;  
		            if($userConstant->attachment_kb_available != null)  
		                $attachmentSpaceAvailable = $userConstant->attachment_kb_available; 
		            if($userConstant->db_size != null)  
		                $dbSize = $userConstant->db_size;
	                               		 
					$responseData = array();	            
		            $responseData['isContent'] = 0;
		            $responseData['isAction'] = 1;
		            $responseData['hasEmpDetails'] = 1;
		            $responseData['hasQuota'] = 1;
		            $responseData['attachmentSpaceAllotted'] = $attachmentSpaceAllotted;      
		            $responseData['attachmentSpaceAvailable'] = $attachmentSpaceAvailable;
		            $responseData['dbSize'] = $dbSize;
		            $responseData['senderToken'] = $currLoginToken;  

					foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}
    	return $sendStatus;			
	}

    public function sendFolderAddMessageToDevice($userId, $currLoginToken, $folderId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $userFolder = $depMgmtObj->getFolderObject($folderId);

                if(count($userTokens) > 0 && isset($userFolder))
                {	                	
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isFolderOp'] = 1;					
						$responseData['addId'] = sracEncryptNumberData($userFolder->appuser_folder_id, $userSession);
			            $responseData['name'] = $userFolder->folder_name;
						$responseData['iconCode'] = $userFolder->icon_code;
						$responseData['isFavorited'] = $userFolder->is_favorited;
						$responseData['folderType'] = $userFolder->folder_type_id;
	                    $responseData['virtualFolderSenderEmail'] = $userFolder->virtual_folder_sender_email;
						$responseData['appliedFilters'] = $userFolder->applied_filters;
			            $responseData['senderToken'] = $currLoginToken;

						//$responseData['defFolderIsFavorited'] = $defFolderIsFavorited;

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendFolderDeletedMessageToDevice($userId, $currLoginToken, $folderId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);

                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isFolderOp'] = 1;
	                    $responseData['delId'] = sracEncryptNumberData($folderId, $userSession);
			            $responseData['senderToken'] = $currLoginToken;

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}

    public function sendSourceAddMessageToDevice($userId, $currLoginToken, $sourceId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $userSource = $depMgmtObj->getSourceObject($sourceId);

                if(count($userTokens) > 0 && isset($userSource))
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isSourceOp'] = 1;					
						$responseData['addId'] = sracEncryptNumberData($userSource->appuser_source_id, $userSession);
			            $responseData['name'] = $userSource->source_name;
			            $responseData['senderToken'] = $currLoginToken;

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendSourceDeletedMessageToDevice($userId, $currLoginToken, $sourceId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);

                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isSourceOp'] = 1;
	                    $responseData['delId'] = sracEncryptNumberData($sourceId, $userSession);

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}

    public function sendTagAddMessageToDevice($userId, $currLoginToken, $tagId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $userTag = $depMgmtObj->getTagObject($tagId);

                if(count($userTokens) > 0 && isset($userTag))
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isTagOp'] = 1;					
						$responseData['addId'] = sracEncryptNumberData($userTag->appuser_tag_id, $userSession);
			            $responseData['name'] = $userTag->tag_name;

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendTagDeletedMessageToDevice($userId, $currLoginToken, $tagId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);

                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
	                    $responseData['isContent'] = 0;
	                	$responseData['isAction'] = 1;
						$responseData['isTagOp'] = 1;
	                    $responseData['delId'] = sracEncryptNumberData($tagId, $userSession);

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
    
    public function sendContentAddMessageToDevice($userId, $currLoginToken, $isFolder, $contentId, $notifText = NULL, $consOpCode = NULL)
    {
    	// Log::info('sendContentAddMessageToDevice : userId : '.$userId.' : contentId : '.$contentId.' : isFolder : '.json_encode($isFolder).' : notifText : '.$notifText.' : consOpCode : '.$consOpCode);
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$orgId = 0;
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);
                $contentDetails = array();
                $responseData = array();

    			// Log::info('sendContentAddMessageToDevice : userName : '.$user->fullname);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");        
                $userContent = $depMgmtObj->getContentObject($contentId, $isFolder);

				// Log::info('sendContentAddMessageToDevice : count($userTokens) : '.(count($userTokens)));

                if(count($userTokens) > 0 && isset($userContent))
                {		
    				// Log::info('sendContentAddMessageToDevice : count($userTokens) > 0 && isset($userContent)');
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$attachmentsArr = array();
	                	$userContentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
	                	if(count($userContentAttachments) > 0)
	                	{
	                		$i = 0;
	                    	$performDownload = 1;
							foreach($userContentAttachments as $contentAttachment)
							{
	               				if($contentAttachment->att_cloud_storage_type_id == 0)
								{
									$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
								}
								else
								{
									$attachmentUrl = $contentAttachment->cloud_file_url; 
								}

								$attachmentsArr[$i]['name'] = $contentAttachment->filename;
		                        $attachmentsArr[$i]['pathname'] = $contentAttachment->server_filename;
		                        $attachmentsArr[$i]['size'] = $contentAttachment->filesize;
	                            $attachmentsArr[$i]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
	                            $attachmentsArr[$i]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
	                            $attachmentsArr[$i]['cloudFileId'] = $contentAttachment->cloud_file_id;
	                            $attachmentsArr[$i]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
	                            $attachmentsArr[$i]['attCreateTs'] = $contentAttachment->create_ts;
	                            $attachmentsArr[$i]['attUpdateTs'] = $contentAttachment->update_ts;
		                        $attachmentsArr[$i]['url'] = $attachmentUrl;
		                        $attachmentsArr[$i]['performDownload'] = $performDownload;
	                        	$attachmentsArr[$i]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
								$i++;
							}
						}
						$attachmentCnt = count($attachmentsArr);
						
	                	$userContentTags = $depMgmtObj->getContentTags($contentId, $userId, $isFolder);
	                	$tagsArr = array();
	                	foreach($userContentTags as $contTag)
	                	{
							$tagId = $contTag->tag_id;
	                		$tag = $depMgmtObj->getTagObject($tagId);
	                		$tagName = "";
	                		if(isset($tag))
	                		{
								$tagName = $tag->tag_name;
	                		
								$tagObj = array();
								$tagObj['id'] = sracEncryptNumberData($tagId, $userSession);
								$tagObj['name'] = $tagName;
								
								array_push($tagsArr, $tagObj);
							}
						}
						$tagCnt = count($tagsArr);
						
						$isFolderFlag = 0;
						if($isFolder)
						{
							$isFolderFlag = 1;
						
							$userContentFolderName = "";
							$userContentFolderIcon = "";
							$userContentFolderAppliedFilters = "";
							$userContentFolderIsFavorited = 0;
							$userContentFolderTypeId = 0;
	                        $userContentVirtualFolderSenderEmail = "";
							$folderObj = $depMgmtObj->getFolderObject($userContent->folder_id);
							if(isset($folderObj))
							{
								$userContentFolderName = $folderObj->folder_name;
								$userContentFolderIcon = $folderObj->icon_code;
								$userContentFolderIsFavorited = $folderObj->is_favorited;
								$userContentFolderTypeId = $folderObj->folder_type_id;
	                            $userContentVirtualFolderSenderEmail = $folderObj->virtual_folder_sender_email;
								$userContentFolderAppliedFilters = $folderObj->applied_filters;
							}
							
							$userContentSourceName = "";
							$sourceObj = $depMgmtObj->getSourceObject($userContent->source_id);
							if(isset($sourceObj))
							{
								$userContentSourceName = $sourceObj->source_name;
							}
							
				            $contentDetails['folderId'] = sracEncryptNumberData($userContent->folder_id, $userSession);
				            $contentDetails['sourceId'] = sracEncryptNumberData($userContent->source_id, $userSession);
		                    $contentDetails['folderName'] = $userContentFolderName;
		                    $contentDetails['folderIconCode'] = $userContentFolderIcon;
		                    $contentDetails['folderIsFavorited'] = $userContentFolderIsFavorited;
		                    $contentDetails['folderType'] = $userContentFolderTypeId;
	                        $contentDetails['virtualFolderSenderEmail'] = $userContentVirtualFolderSenderEmail;
							$contentDetails['folderAppliedFilters'] = $userContentFolderAppliedFilters;
				            $contentDetails['sourceName'] = $userContentSourceName;
		                	$contentDetails['isRemoved'] = $userContent->is_removed;
		                	$contentDetails['removedAt'] = $userContent->removed_at;
						}
						else
						{
			            	$contentDetails['groupId'] = sracEncryptNumberData($userContent->group_id, $userSession);						
						}
						
						$sharedByUserEmail = $userContent->shared_by_email;
						if(!isset($sharedByUserEmail))
							$sharedByUserEmail = "";
						
						$sharedByUserName = "";
						if(isset($sharedByUserEmail) && $sharedByUserEmail != "")
						{
							$sharedByUserDetails = $depMgmtObj->getEmployeeOrUserObjectByEmail($sharedByUserEmail);
							if(isset($sharedByUserDetails))
								$sharedByUserName = $sharedByUserDetails->fullname;
						}						

						$contentDetails['contId'] = sracEncryptNumberData($contentId, $userSession);
						$contentDetails['colorCode'] = $userContent->color_code;
						$contentDetails['isLocked'] = $userContent->is_locked;
						$contentDetails['isShareEnabled'] = $userContent->is_share_enabled;
						$contentDetails['remindBeforeMillis'] = $userContent->remind_before_millis;
						$contentDetails['repeatDuration'] = $userContent->repeat_duration;
						$contentDetails['isCompleted'] = $userContent->is_completed;
						$contentDetails['isSnoozed'] = $userContent->is_snoozed;
						$contentDetails['reminderTimestamp'] = isset($userContent->reminder_timestamp) ? $userContent->reminder_timestamp : 0;
			            $contentDetails['content'] = utf8_encode(Crypt::decrypt($userContent->content));
			            if($userContent->content_title === '' || $userContent->content_title ===null){
							$contentDetails['content_title'] = 'No Title';
						}else{
							$contentDetails['content_title'] = $userContent->content_title;
						}
				        // $contentDetails['content_title'] = $userContent->content_title;
				        $contentDetails['encContent'] = rawurlencode(Crypt::decrypt($userContent->content));
						$contentDetails['reloadContent'] = 1;
			            $contentDetails['contentTypeId'] = $userContent->content_type_id;
			            $contentDetails['createTimestamp'] = $userContent->create_timestamp;
			            $contentDetails['updateTimestamp'] = $userContent->update_timestamp;
			            $contentDetails['fromTimestamp'] = $userContent->from_timestamp;
			            $contentDetails['toTimestamp'] = $userContent->to_timestamp;
	                    $contentDetails['contentIsMarked'] = $userContent->is_marked;
						$contentDetails['syncWithCloudCalendarGoogle'] = $userContent->sync_with_cloud_calendar_google;
						$contentDetails['syncWithCloudCalendarOnedrive'] = $userContent->sync_with_cloud_calendar_onedrive;
						$contentDetails['isContentOp'] = 1;
	                    $contentDetails['isContent'] = 1;
	                    $contentDetails['isFolder'] = $isFolderFlag;
	                    $contentDetails['attachmentCnt'] = $attachmentCnt;
	                    $contentDetails['attachments'] = $attachmentsArr;
	                    $contentDetails['tagCnt'] = $tagCnt;
	                    $contentDetails['tags'] = $tagsArr;
	                    $contentDetails['sharedByName'] = $sharedByUserName;
	                    $contentDetails['sharedByEmail'] = $sharedByUserEmail;

	                    $responseData = $contentDetails;

						if(isset($consOpCode) && trim($consOpCode) != "")
						{
							$validOpCodeArr = Config::get("app_config.validContentPushNotifOpCodeArr");
							if(in_array($consOpCode, $validOpCodeArr))
							{
								$opCodeTagChange = Config::get("app_config.content_push_notif_op_code_tag_change");
								if($consOpCode == $opCodeTagChange)
								{
									$notifText = "Content tag(s) changed";
								}
							}								
						}
    	
						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendContentDeletedMessageToDevice($userId, $currLoginToken, $isFolder, $contentId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);

                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$isFolderFlag = 0;
						if($isFolder)
							$isFolderFlag = 1;
							
	                	$responseData = array();
						$responseData['isContentOp'] = 1;
	                    $responseData['isContent'] = 0;
	                    $responseData['isFolder'] = $isFolderFlag;
	                	$responseData['isAction'] = 1;
	                    $responseData['delId'] = sracEncryptNumberData($contentId, $userSession);
	                    
	                    $notifText = NULL;

						$sendStatus = $this->sendFcmToDevice($token, $responseData, $notifText);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendContentAttachmentDeletedMessageToDevice($userId, $currLoginToken, $isFolder, $attachmentId)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($userId, $currLoginToken);

                if(count($userTokens) > 0)
                {
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$isFolderFlag = 0;
						if($isFolder)
							$isFolderFlag = 1;
						
	                	$responseData = array();
						$responseData['isAttachmentOp'] = 1;
	                    $responseData['isContent'] = 0;
	                	$responseData['isFolder'] = $isFolderFlag;
	                	$responseData['isAction'] = 1;
	                    $responseData['delId'] = sracEncryptNumberData($attachmentId, $userSession);

						$sendStatus = $this->sendFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}

	public function sendFcmToDeviceOLD($token, $dataToBeSent, $notificationText, $notificationIcon = NULL)
	{
		$fcmSendStatus = "";
		if(isset($token) && $token != "")
		{
			$UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
			$uuidMatches = preg_match($UUIDv4, $token);
			
			if($uuidMatches > 0)
			{
				$fcmSendStatus = $this->sendOneSignalFcmToDevice($token, $dataToBeSent, $notificationText, $notificationIcon);
				$fcmSendStatus .= "_OS_";
			}
			else
			{
				$fcmSendStatus = $this->sendFireBaseFcmToDevice($token, $dataToBeSent, $notificationText);
				$fcmSendStatus .= "_FB_";
			}
		}
		return $fcmSendStatus;
	}
	
	public function sendFireBaseFcmToDevice($token, $dataToBeSent, $notificationText)
	{		
    	$sendStatus = 0;
    	
		$optionBuiler = new OptionsBuilder();
		$optionBuiler->setTimeToLive(Config::get('app_config.fcm_ttl'));
		$optionBuiler->setContentAvailable(Config::get('app_config.fcm_content_available'));
		$optionBuiler->setPriority(Config::get('app_config.fcm_priority'));

		$notification = NULL;
		if(isset($notificationText))
		{
			$notificationBuilder = new PayloadNotificationBuilder('SocioRAC');
			$notificationBuilder->setBody($notificationText)
			                    ->setSound('default')
			                    ->setIcon('');
			$notification = $notificationBuilder->build();
		}
		//Precautionary
		$notification = NULL;
					
		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($dataToBeSent);

		$option = $optionBuiler->build();
		$data = $dataBuilder->build();

		$downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
    	
    	/*print_r('FCM downstreamResponse : ');
    	print_r($downstreamResponse);
    	print_r('</pre>');*/

		$successCnt = $downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();
		
		if($successCnt > 0)
			$sendStatus = 2;

		//return Array - you must remove all this tokens in your database
		$downstreamResponse->tokensToDelete(); 

		//return Array (key : oldToken, value : new token - you must change the token in your database )
		$downstreamResponse->tokensToModify(); 

		//return Array - you should try to resend the message to the tokens in the array
		$downstreamResponse->tokensToRetry();

		// return Array (key:token, value:errror) - in production you should remove from your database the tokens
		
		return $sendStatus;
	}
	
	public function sendOneSignalFcmToDeviceOLD($token, $dataToBeSent, $notificationText, $notificationIcon = NULL)
	{
		$sendStatus = 0;

        $assetBasePath = Config::get('app_config.assetBasePath');
        $smallIconUrl = asset($assetBasePath.Config::get('app_config.notif_small_icon'));
        $largeIconUrl = asset($assetBasePath.Config::get('app_config.notif_large_icon'));
		 	
    	$text = "";
    	$isMutableContent = FALSE;
    	if(isset($notificationText) && $notificationText != "")
    	{
			$text = "".$notificationText;
    		$isMutableContent = TRUE;
		}
    	
		$params = []; 
		$params['include_player_ids'] = array($token); 
		$contents = [ 
		   "en" => $text
		]; 
		$headings = [ 
		   "en" => "HyLyt"
		];
			
		$params['data'] = $dataToBeSent; 
		$params['mutable_content'] = $isMutableContent;
		$params['content_available'] = TRUE;
		if($isMutableContent)
    	{
			$params['android_channel_id'] = Config::get('app_config.android_notif_channel_id');
			$params['small_icon'] = $smallIconUrl;
			$params['large_icon'] = $largeIconUrl;
			$params['headings'] = $headings; 
			$params['contents'] = $contents; 
			$params['android_accent_color'] = "00F1A53E";
		}
		
		if(isset($notificationIcon) && $notificationIcon != "")
		{
			$mediaAttachments = array();
			$mediaAttachments['id'] = $notificationIcon;
			
			$params['big_picture'] = $notificationIcon;
			$params['ios_attachments'] = $mediaAttachments;
		}
		
		try {
		  $response = OneSignal::sendNotificationCustom($params);
		  $sendStatus = "SUCCESSFULLY SENT";
		}
		catch(Exception $e) {
			// Do nothing
			$sendStatus = "ERROR Encountered : ".$e;
		}
		
		return $sendStatus;
	}
	
    public function sendOSMessageToDevice($userId, $message)
    {
    	$sendStatus = 0;
    	if($userId > 0)
    	{
    		$user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
       			$userTokens = CommonFunctionClass::getAllUserLoginTokens($userId);

                if(count($userTokens) > 0 && $message != "")
                {
                	$imgUrl = 'http://174.138.41.36/uploads/content_attachments/18784_1516435769.png';
                	
					$notifDetails = array();
                    $notifDetails['isContent'] = 0;
                    $notifDetails['isAction'] = 0;
                    $notifDetails['msg'] = $message;
					$notifDetails['imgUrl'] = $imgUrl;
					
					$notificationText = $message;
					$notificationImg = $imgUrl;

					foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendFcmToDevice($token, $notifDetails, $notificationText, $notificationImg);
					}
            	}
            }
    	}
    	return $sendStatus;					
	}

	public function sendFcmToDevice($sessionDetails, $dataToBeSent, $notificationText, $notificationIcon = NULL)
	{
		$fcmSendStatus = "";

		if(isset($sessionDetails))
		{

			$token = $sessionDetails['token'];
			$isMappedApp = $sessionDetails['isMappedApp'];
			$mappedAppKeyDetails = $sessionDetails['mappedAppKeyDetails'];

			if(isset($token) && $token != "")
			{
				// $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
				// $uuidMatches = preg_match($UUIDv4, $token);
				
				// if($uuidMatches > 0)
				// {
					$fcmSendStatus = $this->sendOneSignalFcmToDevice($token, $dataToBeSent, $notificationText, $notificationIcon, $isMappedApp, $mappedAppKeyDetails);
					$fcmSendStatus .= "_OS_";
				// }
				// else
				// {
    			//  Log::info('sendFcmToDevice : sendFireBaseFcmToDevice : ');
				// 	$fcmSendStatus = $this->sendFireBaseFcmToDevice($token, $dataToBeSent, $notificationText);
				// 	$fcmSendStatus .= "_FB_";
				// }
			}
		}
			
		return $fcmSendStatus;
	}
	
	public function sendOneSignalFcmToDevice($token, $dataToBeSent, $notificationText, $notificationIcon = NULL, $isMappedApp = FALSE, $mappedAppKeyDetails = NULL)
	{
		$sendStatus = 0;

        $assetBasePath = Config::get('app_config.assetBasePath');
        $smallIconUrl = asset($assetBasePath.Config::get('app_config.notif_small_icon'));
        $largeIconUrl = asset($assetBasePath.Config::get('app_config.notif_large_icon'));

        // Log::info('sendOneSignalFcmToDevice : notificationText : ');
        // Log::info($notificationText);
        
        // Log::info('sendOneSignalFcmToDevice : token : ');
        // Log::info($token);
		 	
    	$text = "";
    	$isMutableContent = FALSE;
    	if(isset($notificationText) && $notificationText != "")
    	{
			$text = "".$notificationText;
    		$isMutableContent = TRUE;
		}
    	
		$params = []; 
		$params['include_player_ids'] = array($token); 
		$contents = [ 
		   "en" => $text
		]; 
		$headings = [ 
		   "en" => "HyLyt"
		];
			
		$params['data'] = $dataToBeSent; 
		$params['mutable_content'] = $isMutableContent;
		$params['content_available'] = TRUE;
		if($isMutableContent)
    	{
			$params['android_channel_id'] = Config::get('app_config.android_notif_channel_id');
			$params['small_icon'] = $smallIconUrl;
			$params['large_icon'] = $largeIconUrl;
			$params['headings'] = $headings; 
			$params['contents'] = $contents; 
			$params['android_accent_color'] = "00F1A53E";
		}
		
		if(isset($notificationIcon) && $notificationIcon != "")
		{
			$mediaAttachments = array();
			$mediaAttachments['id'] = $notificationIcon;
			
			$params['big_picture'] = $notificationIcon;
			$params['ios_attachments'] = $mediaAttachments;
		}

		if($isMappedApp && isset($mappedAppKeyDetails))
		{
			$osAppId = $mappedAppKeyDetails->one_signal_notif_app_id; 
			$osAuthKey = $mappedAppKeyDetails->one_signal_notif_authentication_key; 
			$osChannelId = $mappedAppKeyDetails->one_signal_notif_channel_id;
			$appName = $mappedAppKeyDetails->app_name;

			if(isset($params['android_channel_id']))
			{
				$params['android_channel_id'] = $osChannelId;
			}

			if(isset($params['headings']))
			{
				$params['headings'] = [ "en" => $appName ];
			}

			$params['app_id'] = $osAppId;
			$params['api_key'] = $osAuthKey;
		}
        
        // Log::info('sendOneSignalFcmToDevice : params : ');
        // Log::info($params);

		try {
			$response = OneSignal::sendNotificationCustom($params);
			$sendStatus = "SUCCESSFULLY SENT";
		}
		catch(Exception $e) {
			// Do nothing
			$sendStatus = "ERROR Encountered : ".$e;
		}	

        // Log::info('sendOneSignalFcmToDevice : sendStatus : ');
        // Log::info($sendStatus);
		
		return $sendStatus;
	}
}