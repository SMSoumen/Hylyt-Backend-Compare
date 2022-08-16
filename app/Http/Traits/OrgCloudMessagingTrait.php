<?php
namespace App\Http\Traits;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Org\Organization;
use App\Models\Api\OrgContent;
use App\Models\Api\OrgContentTag;
use App\Models\Api\OrgContentAttachment;
use App\Models\Api\OrgGroup;
use App\Models\Api\OrgGroupContent;
use App\Models\Api\OrgGroupContentTag;
use App\Models\Api\OrgGroupContentAttachment;
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
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use OneSignal;
use \Exception as Exception;
use Illuminate\Support\Facades\Log;

trait OrgCloudMessagingTrait {

    public function sendOrgEntryAddMessageToDevice($empId, $orgId, $contentId, $sharedByUserEmail, $sharedByEmpId = NULL)
    {     	
    	$isSilent = FALSE;
        $sendStatus = $this->sendRelativeOrgEntryAddMessageToDevice($isSilent, $empId, $orgId, $contentId, $sharedByUserEmail, $sharedByEmpId);
    	return $sendStatus;					
	} 

    public function sendOrgEntryAddSilentMessageToDevice($empId, $orgId, $contentId, $sharedByUserEmail, $sharedByEmpId = NULL)
    {     	
    	$isSilent = TRUE;
        $sendStatus = $this->sendRelativeOrgEntryAddMessageToDevice($isSilent, $empId, $orgId, $contentId, $sharedByUserEmail, $sharedByEmpId);
    	return $sendStatus;					
	} 

	public function sendRelativeOrgEntryAddMessageToDevice($isSilent = FALSE, $empId, $orgId, $contentId, $sharedByUserEmail, $sharedByEmpId = NULL)
    {     	
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    	
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
                $contentDetails = array();
                $responseData = array();
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
                $isFolderFlag = TRUE;                          
                $userContent = $depMgmtObj->getContentObject($contentId, $isFolderFlag);
                $orgCode = $depMgmtObj->getOrganizationCodeForFcm();

                if(count($userTokens) > 0 && isset($userContent))
                {
					$contentTitle = $userContent->content_title;
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$attachmentsArr = array();
	                	$userContentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolderFlag);
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
						
	                	$userContentTags = $depMgmtObj->getContentTags($contentId, $empId, $isFolderFlag);
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
						if(isset($sharedByEmpId) && $sharedByEmpId > 0)
		                {
		                	$empDepMgmtObj = New ContentDependencyManagementClass;
			                $empDepMgmtObj->withOrgIdAndEmpId($orgId, $sharedByEmpId);   
		                	$sharedByEmpDetails = $empDepMgmtObj->getEmployeeObject();
							if(isset($sharedByEmpDetails))
								$sharedByUserName = $sharedByEmpDetails->employee_name;
						}

		        		$contentDetails['orgId'] = $encOrgId;
						$contentDetails['contId'] = sracEncryptNumberData($userContent->employee_content_id, $userSession);
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
	                    $contentDetails['syncWithCloudCalendarGoogle'] = $userContent->sync_with_cloud_calendar_google;
	                    $contentDetails['syncWithCloudCalendarOnedrive'] = $userContent->sync_with_cloud_calendar_onedrive;
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

	                    $responseData = $contentDetails;
		                
		                $contentTypeText = $depMgmtObj->getContentTypeText($userContent->content_type_id);
	                    
	                    $notifText = "";
	                    if($isSilent == FALSE)
	                    {
							$notifText = "$orgCode";
		                    if($sharedByUserEmail != "" || $sharedByUserName != "")
		                    {
		                    	if($sharedByUserName != "")
									$notifText .= " $sharedByUserName";
								if($sharedByUserEmail != "")
									$notifText .= " <$sharedByUserEmail>";
									
								$notifText .= " -";
							}
						
							// $notifText .= " New $contentTypeText received";
							$notifText .= " $contentTitle";
						}

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgGroupEntryAddMessageToDevice($orgId, $empId, $contentId, $isAdd, $sharedByUserEmail, $sharedByEmpId = NULL, $forRestore = FALSE, $consOpCode = NULL)
    {
		// Log::info('sendOrgGroupEntryAddMessageToDevice : orgId : '.$orgId.' : empId : '.$empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
                $contentDetails = array();
                $responseData = array();
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
                $isFolderFlag = FALSE;  
                $grpContent = $depMgmtObj->getContentObject($contentId, $isFolderFlag);
                $orgCode = $depMgmtObj->getOrganizationCodeForFcm();

                if(count($userTokens) > 0 && isset($grpContent))
                {
					$contentTitle = $grpContent->content_title;
					foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$attachmentsArr = array();
	                	$groupContentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolderFlag);
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
						
	                	$userContentTags = $depMgmtObj->getContentTags($contentId, $empId, $isFolderFlag);
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
						if(isset($sharedByEmpId) && $sharedByEmpId > 0)
		                {
		                	$empDepMgmtObj = New ContentDependencyManagementClass;
			                $empDepMgmtObj->withOrgIdAndEmpId($orgId, $sharedByEmpId);   
		                	$sharedByEmpDetails = $empDepMgmtObj->getEmployeeObject();
							if(isset($sharedByEmpDetails))
								$sharedByUserName = $sharedByEmpDetails->employee_name;
						}

						$contentDetails['id'] = 0;
		        		$contentDetails['orgId'] = $encOrgId;
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
	                    $notifText = $orgCode."$groupName";
	                    if($sharedByUserEmail != "" || $sharedByUserName != "")
	                    {
							$notifText .= " - ";
	                    	if($sharedByUserName != "")
								$notifText .= " $sharedByUserName";
							if($sharedByUserEmail != "")
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
						
						// Log::info('sendOrgGroupEntryAddMessageToDevice : notifText: '.$notifText.' : token : '.$token['token']);

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgGroupDeletedMessageToDevice($empId, $delGrpId, $delGrpName, $orgId = NULL)
    { 	
    	if(!isset($orgId) && isset($this->organizationId))    	
    		$orgId = $this->organizationId;
    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
                
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId); 
        $orgCode = $depMgmtObj->getOrganizationCodeForFcm();
        
    	$sendStatus = 0;    	
    	if(isset($this->orgDbConName) && count($userTokens) > 0)
    	{
            foreach($userTokens as $token)
            {
            	$userSession = $token['userSession'];

            	$responseData = array();	            
	            $responseData['isContent'] = 0;
	            $responseData['isAction'] = 1;
		        $responseData['orgId'] = $encOrgId;
	            $responseData['delGroupId'] = sracEncryptNumberData($delGrpId, $userSession);
	            $responseData['delGroupName'] = $delGrpName;
		                
	            $notifText = $orgCode."You have been removed from group - $delGrpName";

				$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
			}            
    	}	
    	return $sendStatus;			
	}
	
    public function sendOrgGroupAddedMessageToDevice($empId, $grpId, $isRename, $orgId = NULL, $isAddOp = NULL, $oldGroupName = NULL)
    {
    	if(!isset($orgId) && isset($this->organizationId))    	
    		$orgId = $this->organizationId;
    		
    	if(!isset($isAddOp))
    		$isAddOp = 0;
    		
    	if(!isset($oldGroupName))
    		$oldGroupName = "";
    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
    	$user = Appuser::byId($appuserId)->first();
    	
    	$sendStatus = 0;    	
    	if(isset($userTokens) && count($userTokens) > 0)
    	{
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
            $groupMember = $depMgmtObj->getGroupMemberObject($grpId);
            $orgCode = $depMgmtObj->getOrganizationCodeForFcm();
                
    		if(isset($groupMember))
    		{
    			$groupPhotoUrl = "";
    			$groupPhotoThumbUrl = "";
				$photoFilename = $groupMember->img_server_filename;
    			if(isset($photoFilename) && $photoFilename != "")
    			{
    				$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
    				$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);
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
		            $responseData['orgId'] = $encOrgId;
		            $responseData['addGroupId'] = sracEncryptNumberData($grpId, $userSession);
		            $responseData['addGroupName'] = $groupMember->name;
		            $responseData['description'] = $groupMember->description;
		            $responseData['photoUrl'] = $groupPhotoUrl;
		            $responseData['photoThumbUrl'] = $groupPhotoThumbUrl;
					$responseData["photoFilename"] = $photoFilename;   
		            $responseData['isUserAdmin'] = $groupMember->is_admin;
		            $responseData['hasPostRight'] = $groupMember->has_post_right;
		            $responseData['isFavorited'] = $groupMember->is_favorited;
	                $responseData['isGrpLocked'] = $groupMember->is_locked;
		            $responseData['isTwoWay'] = $groupMember->is_two_way;
		            $responseData['isRename'] = $isRename;
	                $responseData['allocKb'] = $groupMember->allocated_space_kb;
	                $responseData['usedKb'] = $groupMember->used_space_kb;
		              
	                $notifText = "";
					if($isRename == 1)	
	        			$notifText = $orgCode."Group - ".$oldGroupName." has been renamed to ".$groupMember->name;
	        		else  if($isAddOp == 1)
	        			$notifText = $orgCode."You have been added to group - ".$groupMember->name;

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
				}
			}	            
    	}	
    	return $sendStatus;					
	}
    
    public function sendOrgGroupStatusChangeMessageToDevice($empId, $grpId, $orgId, $statusActive)
    {
        if(!isset($orgId) && isset($this->organizationId))      
            $orgId = $this->organizationId;
        
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
        $user = Appuser::byId($appuserId)->first();
        
        $sendStatus = 0;        
        if(isset($userTokens) && count($userTokens) > 0)
        {
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
            $groupMember = $depMgmtObj->getGroupMemberDetailsObject($grpId, FALSE);
            $group = $depMgmtObj->getGroupObject($grpId);
            $orgCode = $depMgmtObj->getOrganizationCodeForFcm();
                
            if(isset($groupMember) && isset($group))
            {
                foreach($userTokens as $token)
                {
	            	$userSession = $token['userSession'];

                	$groupName = $group->name;

                	$responseData = array();
	                $notifText = "";
	                if($statusActive == 1)
	                {
	                    $groupPhotoUrl = "";
	                    $groupPhotoThumbUrl = "";
	                    $photoFilename = $group->img_server_filename;
	                    if(isset($photoFilename) && $photoFilename != "")
	                    {
	                        $groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
	                        $groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);
	                    }
	                    else
	                    {
	                        $photoFilename = "";
	                    }
	                    
	                    $responseData['isContent'] = 0;
	                    $responseData['isAction'] = 1;
	                    $responseData['orgId'] = $encOrgId;
	                    $responseData['addGroupId'] = sracEncryptNumberData($grpId, $userSession);
	                    $responseData['addGroupName'] = $groupName;
	                    $responseData['description'] = $group->description;
	                    $responseData['photoUrl'] = $groupPhotoUrl;
	                    $responseData['photoThumbUrl'] = $groupPhotoThumbUrl;
	                    $responseData["photoFilename"] = $photoFilename;   
	                    $responseData['isUserAdmin'] = $groupMember->is_admin;
	                    $responseData['hasPostRight'] = $groupMember->has_post_right;
	                    $responseData['isFavorited'] = $groupMember->is_favorited;
	                    $responseData['isGrpLocked'] = $groupMember->is_locked;
	                    $responseData['isTwoWay'] = $groupMember->is_two_way;
	                    $responseData['isRename'] = 0;
	                    $responseData['allocKb'] = $group->allocated_space_kb;
	                    $responseData['usedKb'] = $group->used_space_kb;
	                      
	                    $notifText = $orgCode."Group has been reactivated - ".$groupName;
	                }
	                else
	                {            
	                    $responseData['isContent'] = 0;
	                    $responseData['isAction'] = 1;
	                    $responseData['orgId'] = $encOrgId;
	                    $responseData['delGroupId'] = $grpId;
	                    $responseData['delGroupName'] = $groupName;
	                            
	                    $notifText = $orgCode."Group has been deactivated - ".$groupName;
	                }

                    $sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
                }
            }               
        }   
        return $sendStatus;                 
    }
	
    public function sendOrgGroupEntryDeletedMessageToDevice($orgId, $empId, $delGrpContId, $delGrpName, $sharedByUserEmail, $currLoginToken = NULL)
    {    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
    	if(isset($currLoginToken) && $currLoginToken != "")
   			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
   		else
   			$userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId); 
                
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId); 
        $orgCode = $depMgmtObj->getOrganizationCodeForFcm();
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) && count($userTokens) > 0)
            {
	            foreach($userTokens as $token)
	            {
	            	$userSession = $token['userSession'];

	            	$responseData = array();
	                $responseData['isContent'] = 0;
	                $responseData['isAction'] = 1;
	            	$responseData['orgId'] = $encOrgId;
	                $responseData['delGroupContentId'] = sracEncryptNumberData($delGrpContId, $userSession);
	                $responseData['delGroupName'] = $delGrpName;
	                $responseData['sharedByEmail'] = $sharedByUserEmail;
		              
	                $notifText = $orgCode."$delGrpName - $sharedByUserEmail - has deleted content from group";

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
				}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgMessageToDevice($empId, $message, $imgUrl)
    {
    	$orgId = $this->organizationId;
    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
    	$user = Appuser::byId($appuserId)->first();
                
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId); 
        $orgCode = $depMgmtObj->getOrganizationCodeForFcm();
        
    	$sendStatus = 0;
    	if(isset($user) && count($userTokens) > 0 && $message != "")
        {
			$notifDetails = array();
            $notifDetails['isContent'] = 0;
            $notifDetails['isAction'] = 0;
            $notifDetails['orgId'] = $encOrgId;
            $notifDetails['msg'] = $message;
			$notifDetails['imgUrl'] = $imgUrl;

            $responseData = $notifDetails;
					
			$notifText = $orgCode.$message;
			$notifImg = $imgUrl;
            
            foreach($userTokens as $token)
            {
				$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, $notifImg, $orgId);
			}
        }
    	return $sendStatus;					
	}
	
    public function sendOrgEmployeeDetailsToDevice($empId, $organizationId = NULL)
    {
    	if(isset($this->organizationId))
    		$orgId = $this->organizationId; 
    	elseif(isset($organizationId))
    		$orgId = $organizationId;
    		
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
    	$user = Appuser::byId($appuserId)->first();
    	
    	$sendStatus = 0;    	
    	if(count($userTokens) > 0 && isset($user))
    	{
            $depMgmtObj = New ContentDependencyManagementClass;
           	$depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
            $orgEmployee = $depMgmtObj->getEmployeeObject();
            $organization = $depMgmtObj->getOrganizationObject();
					
    		if(isset($orgEmployee) && isset($organization)) 
			{         
    			$orgLogoUrl = "";
    			$orgLogoThumbUrl = "";
				$logoFilename = $organization->logo_filename;
    			if(isset($logoFilename) && $logoFilename != "")
    			{
					$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
					$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
				}
				else
				{
					$logoFilename = "";
				}
                $orgInactivityDayCount = $organization->employee_inactivity_day_count;
                
                $isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $empId);
        		$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $empId);
							
				$responseData = array();	            
	            $responseData['isContent'] = 0;
	            $responseData['isAction'] = 1;
		        $responseData['orgId'] = $encOrgId;
	            $responseData['hasEmpDetails'] = 1;
	        	$responseData['user_no'] = $orgEmployee->employee_no;
	        	$responseData['user_name'] = $orgEmployee->employee_name;
                $responseData['user_email'] = $orgEmployee->email;
	        	$responseData['user_department'] = $orgEmployee->department_name;
	        	$responseData['user_designation'] = $orgEmployee->designation_name;
            	$responseData['code'] = $organization->org_code;
            	$responseData['reg_name'] = $organization->regd_name;
            	$responseData['sys_name'] = $organization->system_name;
                $responseData['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
                $responseData['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
                $responseData['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
                $responseData['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
                $responseData['org_attachment_retain_days'] = $organization->org_attachment_retain_days;
                $responseData['org_inactivity_day_count'] = $orgInactivityDayCount;
		        // $responseData['user_status'] = $orgEmployee->is_active;
				$responseData['logo_url'] = $orgLogoUrl;
				$responseData['logo_thumb_url'] = $orgLogoThumbUrl;
				$responseData['logo_filename'] = $logoFilename;	        	
	            
	            foreach($userTokens as $token)
	            {
					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
			}           
    	}	 
    	return $sendStatus;			
	}

    public function sendOrgEmployeeRemovedToDevice($empId, $forceDelete = FALSE, $currLoginToken = NULL, $organizationId = NULL)
    {    	
        $orgId = NULL;
    	if(isset($this->organizationId))
    		$orgId = $this->organizationId; 
    	elseif(isset($organizationId))
    		$orgId = $organizationId;
    		   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
    	if(isset($currLoginToken) && $currLoginToken != "")
   			$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
   		else
   			$userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId); 
        
    	$user = Appuser::byId($appuserId)->first();
        
        $depMgmtObj = New ContentDependencyManagementClass;
       	$depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
        $orgEmployee = $depMgmtObj->getEmployeeObject();        
        $empStatus = $depMgmtObj->getEmployeeIsActive();
             
        $empDeleted = 1;
        if(isset($orgEmployee))
        {
        	$empDeleted = 0;
		}
		
		if(isset($forceDelete) && $forceDelete)
			$empDeleted = 1;
    	
    	$sendStatus = 0;    	
    	if(isset($this->orgDbConName) && count($userTokens) > 0 && isset($user))
    	{    		 
			$responseData = array();	            
            $responseData['isContent'] = 0;
            $responseData['isAction'] = 1;
	        $responseData['orgId'] = $encOrgId;
            $responseData['hasEmpDetails'] = 1;
            $responseData['empDeleted'] = $empDeleted;
            $responseData['empStatus'] = $empStatus;
            
            foreach($userTokens as $token)
            {
				$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
			}            
    	}	
    	return $sendStatus;			
	}

    public function sendOrgEmployeeShareRightsToDevice($empId, $organizationId = NULL)
    {
    	if(isset($this->organizationId))
    		$orgId = $this->organizationId; 
    	elseif(isset($organizationId))
    		$orgId = $organizationId;
    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
    	$user = Appuser::byId($appuserId)->first();
    	
    	$sendStatus = 0;    	
    	if(isset($this->orgDbConName) && count($userTokens) > 0 && isset($user))
    	{    		
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
            $orgEmpConstant = $depMgmtObj->getEmployeeConstantObject();  	
	        
	        if(isset($orgEmpConstant))
	        {
				$sracShareEnabled = $orgEmpConstant->is_srac_share_enabled;
	            $sracOrgShareEnabled = $orgEmpConstant->is_srac_org_share_enabled;
	            $sracRetailShareEnabled = $orgEmpConstant->is_srac_retail_share_enabled;
	            $sracCopyToProfileEnabled = $orgEmpConstant->is_copy_to_profile_enabled;
	            $socShareEnabled = $orgEmpConstant->is_soc_share_enabled;
	            $socFacebookEnabled = $orgEmpConstant->is_soc_facebook_enabled;
	            $socTwitterEnabled = $orgEmpConstant->is_soc_twitter_enabled;
	            $socLinkedinEnabled = $orgEmpConstant->is_soc_linkedin_enabled;
	            $socWhatsappEnabled = $orgEmpConstant->is_soc_whatsapp_enabled;
	            $socEmailEnabled = $orgEmpConstant->is_soc_email_enabled;
	            $socSmsEnabled = $orgEmpConstant->is_soc_sms_enabled;
	            $socOtherEnabled = $orgEmpConstant->is_soc_other_enabled;
			                	 
				$responseData = array();	            
	            $responseData['isContent'] = 0;
	            $responseData['isAction'] = 1;
		        $responseData['orgId'] = $encOrgId;
	            $responseData['hasEmpDetails'] = 1;
	            $responseData['hasShareRights'] = 1;  
	            $responseData['sracShareEnabled'] = $sracShareEnabled;
	            $responseData['sracOrgShareEnabled'] = $sracOrgShareEnabled;
	            $responseData['sracRetailShareEnabled'] = $sracRetailShareEnabled;
	            $responseData['sracCopyToProfileEnabled'] = $sracCopyToProfileEnabled;
	            $responseData['socShareEnabled'] = $socShareEnabled;
	            $responseData['socFacebookEnabled'] = $socFacebookEnabled;
	            $responseData['socTwitterEnabled'] = $socTwitterEnabled;
	            $responseData['socLinkedinEnabled'] = $socLinkedinEnabled;
	            $responseData['socWhatsappEnabled'] = $socWhatsappEnabled;
	            $responseData['socEmailEnabled'] = $socEmailEnabled;
	            $responseData['socSmsEnabled'] = $socSmsEnabled;
	            $responseData['socOtherEnabled'] = $socOtherEnabled;
	            
	            foreach($userTokens as $token)
	            {
					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				} 
			}          
	                       
    	}	
    	return $sendStatus;			
	}

    public function sendOrgEmployeeQuotaToDevice($empId, $orgId = NULL)
    {
    	if(!isset($orgId) && isset($this->organizationId))    	
    		$orgId = $this->organizationId;
    	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $userTokens = CommonFunctionClass::getAllUserLoginTokens($appuserId);
    	$user = Appuser::byId($appuserId)->first();
    	
    	$sendStatus = 0;    	
    	if(isset($this->orgDbConName) && count($userTokens) > 0 && isset($user))
    	{ 
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
            $orgEmpConstant = $depMgmtObj->getEmployeeConstantObject();  
            
	        if(isset($orgEmpConstant))
	        {
	            $dbSize = 0;
	            
	            if($orgEmpConstant->attachment_kb_allotted != null)  
	                $attachmentSpaceAllotted = $orgEmpConstant->attachment_kb_allotted;  
	            if($orgEmpConstant->attachment_kb_available != null)  
	                $attachmentSpaceAvailable = $orgEmpConstant->attachment_kb_available; 
	            if($orgEmpConstant->db_size != null)  
	                $dbSize = $orgEmpConstant->db_size;
	               		 
				$responseData = array();	            
	            $responseData['isContent'] = 0;
	            $responseData['isAction'] = 1;
		        $responseData['orgId'] = $encOrgId;
	            $responseData['hasEmpDetails'] = 1;
	            $responseData['hasQuota'] = 1;
	            $responseData['attachmentSpaceAllotted'] = $attachmentSpaceAllotted;      
	            $responseData['attachmentSpaceAvailable'] = $attachmentSpaceAvailable;
	            $responseData['dbSize'] = $dbSize;              
	            
	            foreach($userTokens as $token)
	            {
					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}  
			}          
    	}	
    	return $sendStatus;			
	}
	
    public function sendOrgFolderAddMessageToDevice($orgId, $empId, $currLoginToken, $folderId)
    {     	
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    	
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
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
		        		$responseData['orgId'] = $encOrgId;
			            $responseData['addId'] = sracEncryptNumberData($userFolder->employee_folder_id, $userSession);
						$responseData['name'] = $userFolder->folder_name;
						$responseData['iconCode'] = $userFolder->icon_code;
						$responseData['isFavorited'] = $userFolder->is_favorited;
						$responseData['folderType'] = $userFolder->folder_type_id;
	                    $responseData['virtualFolderSenderEmail'] = $userFolder->virtual_folder_sender_email;
	                    $responseData['appliedFilters'] = $userFolder->applied_filters;
			            $responseData['senderToken'] = $currLoginToken;

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgFolderDeletedMessageToDevice($orgId, $empId, $currLoginToken, $folderId)
    { 
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {            
	            foreach($userTokens as $token)
	            {
	            	$userSession = $token['userSession'];

	            	$responseData = array();
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
					$responseData['isFolderOp'] = 1;
	        		$responseData['orgId'] = $encOrgId;
		            $responseData['delId'] = sracEncryptNumberData($folderId, $userSession);
			        $responseData['senderToken'] = $currLoginToken;

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgSourceAddMessageToDevice($orgId, $empId, $currLoginToken, $sourceId)
    {     	
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    	
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
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
		        		$responseData['orgId'] = $encOrgId;
			            $responseData['addId'] = sracEncryptNumberData($userSource->employee_source_id, $userSession);
						$responseData['name'] = $userSource->source_name;

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgSourceDeletedMessageToDevice($orgId, $empId, $currLoginToken, $sourceId)
    { 
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {            
	            foreach($userTokens as $token)
	            {
	            	$userSession = $token['userSession'];

	            	$responseData = array();
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
					$responseData['isSourceOp'] = 1;
	        		$responseData['orgId'] = $encOrgId;
		            $responseData['delId'] = sracEncryptNumberData($sourceId, $userSession);

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgTagAddMessageToDevice($orgId, $empId, $currLoginToken, $tagId)
    {     	
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    	
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
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
		        		$responseData['orgId'] = $encOrgId;
			            $responseData['addId'] = sracEncryptNumberData($userTag->employee_tag_id, $userSession);
						$responseData['name'] = $userTag->tag_name;

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgTagDeletedMessageToDevice($orgId, $empId, $currLoginToken, $tagId)
    { 
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {
	            foreach($userTokens as $token)
	            {
	            	$userSession = $token['userSession'];

	            	$responseData = array();
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
					$responseData['isTagOp'] = 1;
	        		$responseData['orgId'] = $encOrgId;
		            $responseData['delId'] = sracEncryptNumberData($tagId, $userSession);

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
            }
    	}	
    	return $sendStatus;					
	}

    public function sendOrgContentAddMessageToDevice($empId, $orgId, $currLoginToken, $isFolder, $contentId, $consOpCode = NULL)
    {   
		// Log::info('sendOrgContentAddMessageToDevice : empId : '.$empId.' : orgId : '.$orgId.' : contentId : '.$contentId);
   		$sendStatus = 0; 
    	try 
    	{
    	 	$encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);
	        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
	    	
	    	if($appuserId > 0)
	    	{
	    		$user = Appuser::byId($appuserId)->first();
	            
	            if(isset($user) )
	            {
	        		$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
	                $contentDetails = array();
	                $responseData = array();
	                    
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
	                $userContent = $depMgmtObj->getContentObject($contentId, $isFolder);

	                if(count($userTokens) > 0 && isset($userContent))
	                {
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
							
		                	$userContentTags = $depMgmtObj->getContentTags($contentId, $empId, $isFolder);
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
									$sharedByUserName = $sharedByUserDetails->employee_name;
							}

							$compEncContentId = sracEncryptNumberData($contentId, $userSession);

			        		$contentDetails['orgId'] = $encOrgId;
							$contentDetails['contId'] = $compEncContentId;
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
		                    $contentDetails['contentIsMarked'] = $userContent->is_marked;
				            $contentDetails['createTimestamp'] = $userContent->create_timestamp;
				            $contentDetails['updateTimestamp'] = $userContent->update_timestamp;
				            $contentDetails['fromTimestamp'] = $userContent->from_timestamp;
				            $contentDetails['toTimestamp'] = $userContent->to_timestamp;
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

							$notifText = NULL;
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

							// Log::info('sendOrgContentAddMessageToDevice : before sendOrgFcmToDevice : '.' : notifText : '.$notifText);
							// Log::info('sendOrgContentAddMessageToDevice : before sendOrgFcmToDevice : '.' : token : '.$token['token']);
							// Log::info('sendOrgContentAddMessageToDevice : before sendOrgFcmToDevice : '.' : attachmentsArr : ');
							// Log::info($attachmentsArr);

							$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText);
						}
	            	}
	            }
	    	}
	 	} 
	 	catch(Exception $e)
	 	{
	        // Log::info('sendOrgContentAddMessageToDevice : e : '.$e);
	 	} 	
	        	
    	return $sendStatus;					
	}
	
    public function sendOrgContentDeletedMessageToDevice($orgId, $empId, $currLoginToken, $isFolder, $contentId)
    { 
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
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
	        		$responseData['orgId'] = $encOrgId;
		            $responseData['delId'] = sracEncryptNumberData($contentId, $userSession);

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
            }
    	}	
    	return $sendStatus;					
	}
	
    public function sendOrgContentAttachmentDeletedMessageToDevice($orgId, $empId, $currLoginToken, $isFolder, $attachmentId)
    { 
        $encOrgId = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $empId);   	
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
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
	        		$responseData['orgId'] = $encOrgId;
		            $responseData['delId'] = sracEncryptNumberData($attachmentId, $userSession);

					$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
				}
            }
    	}	
    	return $sendStatus;					
	}

	public function sendDefaultFolderChangedMessageToDevice($appuserId, $encOrgId, $currLoginToken)
	{        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {
                	$defFolderId = $userConstant->def_folder_id;
                	$defFolderName = "";
                    $defFolderIconCode = "";
                    $defFolderAppliedFilters = "";
                	$defFolderIsFavorited = 0;
                	$defFolderTypeId = 0;
                    $defFolderVirtualFolderSenderEmail = "";
                	$defFolderObj = $depMgmtObj->getFolderObject($defFolderId);
                	if(isset($defFolderObj))
                	{
                		$defFolderName = $defFolderObj->folder_name;
                		$defFolderIconCode = $defFolderObj->icon_code;
                		$defFolderIsFavorited = $defFolderObj->is_favorited;
                        $defFolderTypeId = $defFolderObj->folder_type_id;
                        $defFolderVirtualFolderSenderEmail = $defFolderObj->virtual_folder_sender_email;
                        $defFolderAppliedFilters = $defFolderObj->applied_filters;
					}
                	     
	    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
					$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        			$encOrgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId);           
		              
	            
		            foreach($userTokens as $token)
		            {
		            	$userSession = $token['userSession'];

		            	$responseData = array();
						$responseData['isConstantOp'] = 1;
		                $responseData['isContent'] = 0;
		            	$responseData['isAction'] = 1;
		        		$responseData['orgId'] = $encOrgKey;
			            $responseData['defFolderId'] = sracEncryptNumberData($defFolderId, $userSession);
			            $responseData['defFolderName'] = $defFolderName;
			            $responseData['defFolderIconCode'] = $defFolderIconCode;
						$responseData['defFolderIsFavorited'] = $defFolderIsFavorited;
						$responseData['defFolderType'] = $defFolderTypeId;
	                    $responseData['virtualFolderSenderEmail'] = $defFolderVirtualFolderSenderEmail;
	                    $responseData['defFolderAppliedFilters'] = $defFolderAppliedFilters;
			            $responseData['senderToken'] = $currLoginToken;

						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
				}
            }
    	}	
    	return $sendStatus;		
	}

	public function sendAttachmentRetainDayChangedMessageToDevice($appuserId, $encOrgId, $currLoginToken)
	{        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {    
                	$attRetainDay = $userConstant->attachment_retain_days;
                	     
	    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
					$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        			$encOrgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId);  
                	            	             
	                $responseData = array();
					$responseData['isConstantOp'] = 1;
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
	        		$responseData['orgId'] = $encOrgKey;
		            $responseData['attachmentRetainDays'] = $attRetainDay;
		            $responseData['senderToken'] = $currLoginToken;
	            
		            foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
				}
            }
    	}	
    	return $sendStatus;		
	}

	public function sendPrintPreferenceChangedMessageToDevice($appuserId, $encOrgId, $currLoginToken)
	{        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            { 
                $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                                       
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {     
               		$printFieldIdArr = array();
                    if($userConstant->print_fields != null) 
                    {
                        $printFieldIdStr = $userConstant->print_fields;
                        $printFieldIdArr = explode($passcodeFolderIdDelimiter, $printFieldIdStr);
                    }
                	     
	    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
					$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        			$encOrgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId);
        			                	            	             
	                $responseData = array();
					$responseData['isConstantOp'] = 1;
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
	        		$responseData['orgId'] = $encOrgKey;
		            $responseData['printFieldIdArr'] = $printFieldIdArr;
	            
		            foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
				}
            }
    	}	
    	return $sendStatus;		
	}

	public function sendApplicationPinChangedMessageToDevice($appuserId, $encOrgId, $currLoginToken)
	{        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {    
                	$hasPasscode = $userConstant->passcode_enabled;
                	
                	$passcode = "";
                    if($hasPasscode == 1 && $userConstant->passcode != null)  
                        $passcode = Crypt::decrypt($userConstant->passcode);
                	     
	    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
					$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        			$encOrgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId);
                	            	             
	                $responseData = array();
					$responseData['isConstantOp'] = 1;
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
	        		$responseData['orgId'] = $encOrgKey;
		            $responseData['hasPasscode'] = $hasPasscode;
		            $responseData['passcode'] = $passcode;
		            $responseData['senderToken'] = $currLoginToken;
	            
		            foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
				}
            }
    	}	
    	return $sendStatus;		
	}

	public function sendFolderPinChangedMessageToDevice($appuserId, $encOrgId, $currLoginToken)
	{        
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
        	$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant) && isset($userConstant))
                {  
                    $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                      
                	$hasFolderPasscode = $userConstant->folder_passcode_enabled; 
                	
                	$folderPasscode = "";
                	$passcodeFolderIdArr = array(); 
                	if($hasFolderPasscode == 1)
                	{
						if(isset($userConstant->folder_passcode))
	                	{
	                		$folderPasscode = Crypt::decrypt($userConstant->folder_passcode);						
						}
	                		
	                	if(isset($userConstant->folder_id_str)) 
	                    {
	                        $folderIdStr = $userConstant->folder_id_str;
	                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
	                    } 
					}
                	     
	    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
					$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        			$encOrgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId); 
                	            	             
	                $responseData = array();
					$responseData['isConstantOp'] = 1;
	                $responseData['isContent'] = 0;
	            	$responseData['isAction'] = 1;
	        		$responseData['orgId'] = $encOrgKey;
		            $responseData['hasFolderPasscode'] = $hasFolderPasscode;
		            $responseData['folderPasscode'] = $folderPasscode;
		            $responseData['folderIdArr'] = $passcodeFolderIdArr;
	            
		            foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
				}
            }
    	}	
    	return $sendStatus;		
	}

    public function sendVideoConferenceStartedOrParticipantJoinedMessageToDevice($appuserId, $encOrgId, $currLoginToken, $isStartPush, $userConference, $userConferenceParticipant, $pushForUserOrEmpName, $pushForUserOrEmpEmail)
    {        
        $sendStatus = 0;
        if($appuserId > 0 && isset($userConference) && isset($userConferenceParticipant))
        {
            $user = Appuser::byId($appuserId)->first();
            $userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId); 
                         
                $orgId = $depMgmtObj->getOrganizationId();
                $orgEmpId = $depMgmtObj->getOrganizationEmployeeId(); 

                $conferenceId = 0;
                if($orgId > 0)
                {
                    $conferenceId = $userConference->org_vc_id;
                }
                else
                {
                    $conferenceId = $userConference->sys_vc_id;
                }

                $conferenceSubject = Crypt::decrypt($userConference->meeting_title);

                $orgEmpName = $depMgmtObj->getEmployeeOrUserName();

                $orgName = "";
                $orgIconUrl = "";
                if($orgId > 0)
                {
                    $organization = $depMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgName = $organization->system_name;
                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            $orgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                        }
                    }
                }                        
	               

                if($isStartPush)
                {
                    $notificationText = 'Video Conference '.$conferenceSubject.' has been started by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }
                else
                {
                    $notificationText = 'Video Conference '.$conferenceSubject.' has been joined by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }

                 $buttonArr = NULL;
            
                foreach($userTokens as $token)
                {
	            	$userSession = $token['userSession'];

                	$responseData = array();
	                $responseData['isConstantOp'] = 0;
	                $responseData['isContent'] = 0;
	                $responseData['isAction'] = 0;
	                $responseData['isVideoConferenceAction'] = 1;
	                $responseData['orgId'] = $encOrgId;
	                $responseData['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
	                $responseData['orgName'] = $orgName;
	                $responseData['orgEmpName'] = $orgEmpName;
	                $responseData['orgIconUrl'] = $orgIconUrl;

                 	$launchUrl = NULL;
	                if($token['isWebSession'])
	                {
	                	$launchUrl = 'https://web.sociorac.com/vcDashboard';
	                }

                    $sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notificationText, NULL, $orgId, $buttonArr, $launchUrl);
                }
            }
        }   
        return $sendStatus;     
    }

    public function sendVideoConferenceStartedOrParticipantJoinedMessageToDeviceWithButtons($appuserId, $encOrgId, $currLoginToken, $isStartPush, $userConference, $userConferenceParticipant, $pushForUserOrEmpName, $pushForUserOrEmpEmail)
    {        
        $sendStatus = 0;
        if($appuserId > 0 && isset($userConference) && isset($userConferenceParticipant))
        {
            $user = Appuser::byId($appuserId)->first();
            $userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
            
            if(isset($user) && count($userTokens) > 0)
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId); 
                         
                $orgId = $depMgmtObj->getOrganizationId();
                $orgEmpId = $depMgmtObj->getOrganizationEmployeeId();

                $conferenceId = 0;
                if($orgId > 0)
                {
                    $conferenceId = $userConference->org_vc_id;
                }
                else
                {
                    $conferenceId = $userConference->sys_vc_id;
                }

                $conferenceSubject = Crypt::decrypt($userConference->meeting_title);

                $orgEmpName = $depMgmtObj->getEmployeeOrUserName();

                $orgName = "";
                $orgIconUrl = "";
                if($orgId > 0)
                {
                    $organization = $depMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgName = $organization->system_name;
                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            $orgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                        }
                    }
                }
               
                // $orgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId);

                if($isStartPush)
                {
                    $notificationText = 'Video Conference '.$conferenceSubject.' has been started by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }
                else
                {
                    $notificationText = 'Video Conference '.$conferenceSubject.' has been joined by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }

                $joinBtn = array();
                $joinBtn['id'] = 'VC_JOIN';
                $joinBtn['text'] = 'JOIN';

                $buttonArr = array();
                array_push($buttonArr, $joinBtn);
            
                foreach($userTokens as $token)
                {
	            	$userSession = $token['userSession'];

                	$responseData = array();
	                $responseData['isConstantOp'] = 0;
	                $responseData['isContent'] = 0;
	                $responseData['isAction'] = 0;
	                $responseData['isVideoConferenceAction'] = 1;
	                $responseData['orgKey'] = $encOrgId;
	                $responseData['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
	                $responseData['orgName'] = $orgName;
	                $responseData['orgEmpName'] = $orgEmpName;
	                $responseData['orgIconUrl'] = $orgIconUrl;

                 	$launchUrl = NULL;
	                if($token['isWebSession'])
	                {
	                	$launchUrl = 'https://web.sociorac.com/vcDashboard';
	                }

                    $sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notificationText, NULL, $orgId, $buttonArr, $launchUrl);
                }
            }
        }   
        return $sendStatus;     
    }
    
    public function sendOrgReminderOrCalendarEventApproachingMessageToDevice($appuserId, $orgId, $empId, $remDate, $remTime, $strippedContentText, $userContent, $contentSenderStr)
    {        
        $sendStatus = 0;
        if($appuserId > 0)
        {
            $user = Appuser::byId($appuserId)->first();
            
            if(isset($user))
            {
                $userTokenArr = CommonFunctionClass::getOnlyWebUserLoginTokens($appuserId, NULL);
                $contentDetails = array();
                $responseData = array(); 

                if(count($userTokenArr) > 0 && isset($userContent))
                {                   
                    $notifText = $strippedContentText;

                    foreach($userTokenArr as $token)
                    {
                        $responseData['isContent'] = 0;

                        $sendStatus = $this->sendOrgFcmToDevice($token, $responseData, $notifText, NULL, $orgId);
                    }
                }
            }
        }   
        return $sendStatus;                 
    }

    public function sendOrgSubscribedMessageToDevice($empId, $orgId, $currLoginToken, $orgDetails)
    {
        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    	
    	$sendStatus = 0;
    	if($appuserId > 0)
    	{
    		$user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {
        		$userTokens = CommonFunctionClass::getOtherUserLoginTokens($appuserId, $currLoginToken);
                $responseData = array();
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

                if(count($userTokens) > 0 && isset($orgDetails))
                {
                    $responseData['isOrganizationOp'] = 1;
                    $responseData['isContent'] = 0;
                    $responseData['orgDetails'] = $orgDetails;

					foreach($userTokens as $token)
		            {
						$sendStatus = $this->sendOrgFcmToDevice($token, $responseData, NULL);
					}
            	}
            }
    	}	
    	return $sendStatus;					
	}

	public function sendOrgFcmToDevice($sessionDetails, $dataToBeSent, $notificationText, $notificationIcon = NULL, $orgId = NULL, $buttonArr = NULL, $launchUrl = NULL)
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
    				$this->sendOrgOneSignalFcmToDevice($token, $dataToBeSent, $notificationText, $notificationIcon, $orgId, $buttonArr, $launchUrl, $isMappedApp, $mappedAppKeyDetails);
    			// }
    			// else
    			// {
    			// 	$this->sendOrgFireBaseFcmToDevice($token, $dataToBeSent, $notificationText);
    			// }
    		}
        }
            
        return $fcmSendStatus;
	}

	public function sendOrgFireBaseFcmToDevice($token, $dataToBeSent, $notificationText)
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
	
	public function sendOrgOneSignalFcmToDevice($token, $dataToBeSent, $notificationText, $notificationIcon = NULL, $orgId = NULL, $buttonArr = NULL, $launchUrl = NULL, $isMappedApp = FALSE, $mappedAppKeyDetails = NULL)
	{
       // Log::info('sendOrgOneSignalFcmToDevice : buttonArr : ');
       // Log::info($buttonArr);
       // Log::info('sendOrgOneSignalFcmToDevice : launchUrl : ');
       // Log::info($launchUrl);

        $assetBasePath = Config::get('app_config.assetBasePath');
        $smallIconUrl = asset($assetBasePath.Config::get('app_config.notif_small_icon'));
        $largeIconUrl = asset($assetBasePath.Config::get('app_config.notif_large_icon'));

        $orgIconUrlArr = OrganizationClass::getOrganizationLogoUrlArr($orgId);
        if(isset($orgIconUrlArr))
        {
            $smallIconUrl = $orgIconUrlArr['iconThumbUrl'];
            $largeIconUrl = $orgIconUrlArr['iconUrl'];
        }
			
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
		
        $params['contents'] = $contents;
		$params['data'] = $dataToBeSent; 
		$params['mutable_content'] = $isMutableContent; 
		$params['content_available'] = TRUE;
		if($isMutableContent)
    	{	
            $params['android_channel_id'] = Config::get('app_config.android_notif_channel_id');	
			$params['small_icon'] = $smallIconUrl;
			$params['large_icon'] = $largeIconUrl;
			$params['headings'] = $headings;  
			$params['android_accent_color'] = "00F1A53E";
		}
		
		if(isset($notificationIcon) && $notificationIcon != "")
		{
			$mediaAttachments = array();
			$mediaAttachments['id'] = $notificationIcon;
			
			$params['big_picture'] = $notificationIcon;
			$params['ios_attachments'] = $mediaAttachments;
		}

        if(isset($buttonArr) && is_array($buttonArr) && count($buttonArr) > 0)
        {
            $params['buttons'] = $buttonArr; 
        }

        if(isset($launchUrl) && $launchUrl != "")
        {
        	$params['url'] = $launchUrl;
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
       // Log::info('sendOrgOneSignalFcmToDevice : params : ');
       // Log::info($params);

        try {
            $response = OneSignal::sendNotificationCustom($params);
            $sendStatus = "SUCCESSFULLY SENT";
        }
        catch(Exception $e) {
            // Do nothing
            $sendStatus = "ERROR Encountered : ".$e;
        }
		
		return 1;
	}
    
    public function sendOrgOneSignalFcmToDeviceWithButtons($token, $dataToBeSent, $notificationText, $notificationIcon = NULL, $orgId = NULL, $buttonArr = NULL)
    { 
        $assetBasePath = Config::get('app_config.assetBasePath');
        $smallIconUrl = asset($assetBasePath.Config::get('app_config.notif_small_icon'));
        $largeIconUrl = asset($assetBasePath.Config::get('app_config.notif_large_icon'));

        $orgIconUrlArr = OrganizationClass::getOrganizationLogoUrlArr($orgId);
        if(isset($orgIconUrlArr))
        {
            $smallIconUrl = $orgIconUrlArr['iconThumbUrl'];
            $largeIconUrl = $orgIconUrlArr['iconUrl'];
        }
            
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
        
        $params['contents'] = $contents; 
        $params['data'] = $dataToBeSent; 
        $params['mutable_content'] = $isMutableContent; 
        $params['content_available'] = TRUE;
        if($isMutableContent)
        {
            $params['android_channel_id'] = Config::get('app_config.android_notif_channel_id');     
            $params['small_icon'] = $smallIconUrl;
            $params['large_icon'] = $largeIconUrl;
            $params['headings'] = $headings; 
            $params['android_accent_color'] = "00F1A53E";
        }
        
        if(isset($notificationIcon) && $notificationIcon != "")
        {
            $mediaAttachments = array();
            $mediaAttachments['id'] = $notificationIcon;
            
            $params['big_picture'] = $notificationIcon;
            $params['ios_attachments'] = $mediaAttachments;
        }

        if(isset($buttonArr) && is_array($buttonArr) && count($buttonArr) > 0)
        {
            $params['buttons'] = $buttonArr; 
        }
        
        try {
            $response = OneSignal::sendNotificationCustom($params);
        }
        catch(Exception $e) {
            // Do nothing
        }
        
        return 1;
    }

}
