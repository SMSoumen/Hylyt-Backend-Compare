<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContact;
use App\Models\Api\Group;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentAttachment;
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
use View;
use App\Libraries\MailClass;
use App\Libraries\FileUploadClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class OrganizationGroupController extends Controller
{	
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;
    
	public function __construct()
    {
    	
    }
    
    /**
     * Add Content.
     *
     * @return json array
     */
    public function saveContentDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $groupId = Input::get('groupId');
        $content = Input::get('content');
        $oldContent = Input::get('oldContent');
        $contentTypeId = Input::get('contentType');
        $tagList = Input::get('tagList');
        $isMarked = Input::get('isMarked');
        $createTimeStamp = Input::get('createTimeStamp');
        $updateTimeStamp = Input::get('updateTimeStamp');
        $fromTimeStamp = Input::get('fromTimeStamp');
        $toTimeStamp = Input::get('toTimeStamp');
        $removeAttachmentIdArr = Input::get('removeAttachmentIdArr');
        $modifiedAttachmentIdArr = Input::get('modifiedAttachmentIdArr');
        $id = Input::get('id');
        $attachmentCnt = Input::get('attachmentCnt');
        $loginToken = Input::get('loginToken');
        $sendAsReply = Input::get('sendAsReply');
        $colorCode = Input::get('colorCode');
        $isLocked = Input::get('isLocked');
        $isShareEnabled = Input::get('isShareEnabled');
        $remindBeforeMillis = Input::get('remindBeforeMillis');
        $repeatDuration = Input::get('repeatDuration');
        $isCompleted = Input::get('isCompleted');
        $isSnoozed = Input::get('isSnoozed');
        $reminderTimestamp = Input::get('reminderTimestamp');
        $syncWithCloudCalendarGoogle = Input::get('syncWithCloudCalendarGoogle');
        $syncWithCloudCalendarOnedrive = Input::get('syncWithCloudCalendarOnedrive');

        $isMetaUpdate = Input::get('isMetaUpdate');
		
		if(!isset($colorCode) || $colorCode == '') {
			$colorCode = Config::get('app_config.default_content_color_code');
		}
        
        if(!isset($isLocked)) {
			$isLocked = Config::get('app_config.default_content_lock_status');
		}
        
        if(!isset($isCompleted)) {
            $isCompleted = Config::get('app_config.default_content_is_completed_status');
        }
        
        if(!isset($isSnoozed)) {
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
        }
        
        if(!isset($isShareEnabled)) {
            $isShareEnabled = Config::get('app_config.default_content_share_status');
        }
        
        if(!isset($isMetaUpdate) || $isMetaUpdate * 1 != 1) {
			$isMetaUpdate = 0;
		}
        
        if(!isset($syncWithCloudCalendarGoogle) || $syncWithCloudCalendarGoogle * 1 != 1) {
            $syncWithCloudCalendarGoogle = 0;
        }
        
        if(!isset($syncWithCloudCalendarOnedrive) || $syncWithCloudCalendarOnedrive * 1 != 1) {
            $syncWithCloudCalendarOnedrive = 0;
        }

        if(is_array($tagList))
        	$tagsArr = $tagList;
		else
        	$tagsArr = json_decode($tagList);
        
        if(!is_array($removeAttachmentIdArr))
        	$removeAttachmentIdArr = json_decode($removeAttachmentIdArr);
        	
        if(!is_array($modifiedAttachmentIdArr))
        	$modifiedAttachmentIdArr = json_decode($modifiedAttachmentIdArr);
        
        $content = urldecode($content);
        $oldContent = urldecode($oldContent);
        
        $response = array();
        if($encUserId != "" && $content != "" && $groupId != "")
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

                $groupId = sracDecryptNumberData($groupId, $userSession);
                $id = sracDecryptNumberData($id, $userSession);
                $removeAttachmentIdArr = sracDecryptNumberArrayData($removeAttachmentIdArr, $userSession);
                $modifiedAttachmentIdArr = sracDecryptNumberArrayData($modifiedAttachmentIdArr, $userSession);
                $tagsArr = sracDecryptNumberArrayData($tagsArr, $userSession);
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
            	{	
		        	$sharedByUserName = $user->fullname; 
	            	$sharedByUserEmail = $user->email; 
	                
	                $empModelObj = New OrgEmployee;
	                $empModelObj->setConnection($orgDbConName);
	                
	                $sharerEmployee = $empModelObj->byId($orgEmpId)->first();
	                $sharedByEmpEmail = $sharerEmployee->email;             
	                $sharedByEmpName = $sharerEmployee->employee_name;
	            	
	            	$grpMemModelObj = New OrgGroupMember;
	            	$grpMemModelObj->setConnection($orgDbConName);     	
	            	$groupMember = $grpMemModelObj->ofGroup($groupId)->ofEmployee($orgEmpId)->first();

	            	$groupModelObj = New OrgGroup;
	            	$groupModelObj->setConnection($orgDbConName);  
	            	$group = $groupModelObj->byId($groupId)->first();
            	
	            	if(isset($groupMember) && isset($group) && $group->is_group_active == 1)
	            	{
		                $status = 1;
		                $msg = "";
		                $contentMatchConflict = FALSE;
						$isFolder = FALSE;

		                if(!isset($isMarked))
		                    $isMarked = 0;

						$isAdd = 1;
						$groupContent = NULL;
		                if($id > 0)
		                {
			            	$grpConModelObj = New OrgGroupContent;
			            	$grpConModelObj->setConnection($orgDbConName);
		                    $groupContent = $grpConModelObj->byId($id)->first();
		                    
		                    if(isset($groupContent))
		                    {
		                    	$isAdd = 0;
								$existingGroupContent = "";
								if(isset($groupContent->content) && $groupContent->content != "")
								{
									try
									{
										$existingGroupContent = Crypt::decrypt($groupContent->content);
									} 
									catch (DecryptException $e) 
									{
										//
									}
								}
								
								if(strcasecmp($existingGroupContent, $oldContent) != 0)
								{
									//$contentMatchConflict = TRUE;
								}
								
			                	//$response['oldContent'] = $oldContent;
			                	//$response['existingGroupContent'] = $existingGroupContent;
							}
		                }
                    
		                $depMgmtObj = New ContentDependencyManagementClass;
		                $depMgmtObj->withOrgKey($user, $encOrgId);
		                $depMgmtObj->setCurrentLoginToken($loginToken);
		                
		                if(!$contentMatchConflict)
		                {            
							$contentNotChanged = FALSE;
							$attachmentRemoved = FALSE;
							if(isset($groupContent) && $groupContent->content != "")
							{
								$existingContent = $groupContent->content;
								$decryptedExistingContent = "";
		                   		try
		                   		{
								    $decryptedExistingContent = Crypt::decrypt($existingContent);
								} 
								catch (DecryptException $e) 
								{
									
								}
								
								if($decryptedExistingContent == $content)
								{
									$contentNotChanged = TRUE;
								}
							}

							if(!$contentNotChanged && $isMetaUpdate == 0)
							{                   
								$content = CommonFunctionClass::getSharedByAppendedString($content, $updateTimeStamp, $sharedByEmpName, $sharedByEmpEmail);
							}
							
			                $response = $depMgmtObj->addEditGroupContent($id, $content, $contentTypeId, $groupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmpEmail, $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive);
			                
			                if(isset($modifiedAttachmentIdArr))
			                {
								foreach($modifiedAttachmentIdArr as $attId)
								{
									$depMgmtObj->setContentAttachmentIsModified($attId, $isFolder, 1);
								}
							}
							
                			$groupContentId = $response["syncId"];
                			$depMgmtObj->setGroupContentCreator($groupContentId, $groupMember->member_id);				           
			    			$currAttachmentCnt = $depMgmtObj->getContentAttachmentCount($groupContentId, $isFolder);
			    			$modAttachmentCnt = $depMgmtObj->getModifiedContentAttachmentCnt($groupContentId, $isFolder);
			        		if($attachmentCnt == $currAttachmentCnt && (!$contentNotChanged || $attachmentRemoved) && $modAttachmentCnt == 0)
			        		{
		            			$otherGroupMembers = $grpMemModelObj->ofGroup($groupId)->get();
		            			$isRename = 0;
			            		foreach($otherGroupMembers as $member) 
			                    {
									$memberEmpId = $member->employee_id;
						        
					        		if($memberEmpId != $orgEmpId)
									{	
						        		$empDepMgmtObj = New ContentDependencyManagementClass;								
								       	$empDepMgmtObj->withOrgIdAndEmpId($orgId, $memberEmpId);   
								        $orgEmployee = $empDepMgmtObj->getPlainEmployeeObject();
								        
								        if(isset($orgEmployee) && $orgEmployee->is_active == 1)
											$this->sendOrgGroupEntryAddMessageToDevice($orgId, $memberEmpId, $groupContentId, $isAdd, $sharedByEmpEmail, $orgEmpId);
									}
									else
									{
										$this->sendOrgContentAddMessageToDevice($memberEmpId, $orgId, $loginToken, $isFolder, $groupContentId);
									}
									$this->sendOrgGroupAddedMessageToDevice($memberEmpId, $groupId, $isRename, $orgId);
									
									if(isset($sendAsReply) && $sendAsReply == 1)
									{
										// $depMgmtObj->sendContentAsReply($encUserId, $encOrgId, $loginToken, $groupContentId, $isFolder);
									}               
		                    	}								
							}	

                			$response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
			                $response['sharedByEmail'] = $sharedByEmpEmail;
							$response['syncContent'] = utf8_encode($content);
							$response['encSyncContent'] = rawurlencode($content);
			            }
			            else
			            {
			                $status = -1;
			                $msg = "Content was changed before update. Try making changes again.";   
						}
			            
			            CommonFunctionClass::setLastSyncTs($userId, $loginToken);
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
     * save app user broadcast details.
     *
     * @return json array
     */
    public function loadAddEditGroupDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $grpId = Input::get('grpId');
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
                   
                $status = 1;
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
				
           		if(isset($orgDbConName))
            	{
					$grpExists = 0;       			
	        		if($grpId > 0)
	        		{
		                $depMgmtObj = New ContentDependencyManagementClass;
		                $depMgmtObj->withOrgKey($user, $encOrgId);
		                				
	            		$editGroup = $depMgmtObj->getGroupObject($grpId);
	            		$editGroupMembers = $depMgmtObj->getGroupMembers($grpId);
		                      		
	            		if(isset($editGroup))
	            		{
							$grpExists = 1;
							$grpMemberCnt = count($editGroupMembers);
							
							$grpDetails["name"] = $editGroup->name;
							$grpDetails["description"] = $editGroup->description;
							$grpDetails["memberCnt"] = $grpMemberCnt;
							$grpDetails["isTwoWay"] = $editGroup->is_two_way;
							$grpDetails["allocKb"] = $editGroup->allocated_space_kb;
							$grpDetails["usedKb"] = $editGroup->used_space_kb;
							
							$isFolder = FALSE;
							
							$totalNoteCount = $depMgmtObj->getAllContentModelObj($isFolder, $grpId)->count();
							$activeMemberCount = 0;
							$isUserAdmin = 0;
							$hasPostRight = 0;
							$isGroupFavorited = 0;
							$isGrpLocked = 0;
							if($grpMemberCnt > 0)
	            			{
	            				$adminMemberArr = array();
	            				$activeMemberArr = array();
	            				$inActiveMemberArr = array();
	            				foreach($editGroupMembers as $grpMember)
	            				{
	            					$memberEmpId = $grpMember->employee_id;
	            					$memberIsAdmin = $grpMember->is_admin;
	            					$memberHasPostRight = $grpMember->has_post_right;
	            					$memberName = $grpMember->name;
									$memberEmail = $grpMember->email;
			    					$memberIsGhost = 0;
			    					if(isset($grpMember->is_ghost)) {
			    						$memberIsGhost = $grpMember->is_ghost;							
									}
									
									if($memberIsGhost == 0) {
										$isSelf = 0;            					
		            					if($memberEmpId == $orgEmpId)
		            					{
											$isSelf = 1;
		            						if($memberIsAdmin == 1)
											{
												$isUserAdmin = 1;
											}
		            						if($memberHasPostRight == 1)
											{
												$hasPostRight = 1;
											}
											$isGroupFavorited = $grpMember->is_favorited;
											$isGrpLocked = $grpMember->is_locked;
										}							
	            					
	            						$isActiveMember = 0;
		            					$memberNoteCount = $depMgmtObj->getGroupMemberContentCount($grpId, $grpMember->member_id);
		            					if($memberNoteCount > 0)
		            					{
											$activeMemberCount++;
											$isActiveMember = 1;
										}

										$enrollmentStr = "";
										if($grpMember->is_verified == 0)
										{
											$enrollmentStr = "Pending";
										}
			            					
		            					$memberDetails = array();
		            					$memberDetails["id"] = sracEncryptNumberData($memberEmpId, $userSession);
		            					$memberDetails["name"] = $memberName;
		            					$memberDetails["isAdmin"] = $memberIsAdmin;
		            					$memberDetails["isSelf"] = $isSelf;
	            						$memberDetails["isActive"] = $isActiveMember;
		            					$memberDetails["email"] = $memberEmail;
	            						$memberDetails["noteCount"] = $memberNoteCount;
            							$memberDetails["enrollmentStr"] = $enrollmentStr;
		            					
		            					if($memberIsAdmin == 1) {
											array_push($adminMemberArr, $memberDetails);
										}
										else if($isActiveMember == 1) {
											array_push($activeMemberArr, $memberDetails);									
										}
										else {
											array_push($inActiveMemberArr, $memberDetails);									
										}
									}
								}
	            				$memberArr = array();
	            				if(count($adminMemberArr) > 0) {
									$adminMemberArr = collect($adminMemberArr);
									$adminMemberArr = $adminMemberArr->sortBy('name');
									$adminMemberArr = $adminMemberArr->toArray();
									
									$memberArr = array_merge($memberArr, $adminMemberArr);
								}
	            				if(count($activeMemberArr) > 0) {
									$activeMemberArr = collect($activeMemberArr);
									$activeMemberArr = $activeMemberArr->sortByDesc('noteCount');
									$activeMemberArr = $activeMemberArr->toArray();
									
									$memberArr = array_merge($memberArr, $activeMemberArr);
								}
	            				if(count($inActiveMemberArr) > 0) {
									$inActiveMemberArr = collect($inActiveMemberArr);
									$inActiveMemberArr = $inActiveMemberArr->sortBy('name');
									$inActiveMemberArr = $inActiveMemberArr->toArray();
									
									$memberArr = array_merge($memberArr, $inActiveMemberArr);
								}
	            				$grpDetails["members"] = $memberArr;
	            			}
			    			$groupPhotoUrl = "";
							$groupPhotoThumbUrl = "";
							$photoFilename = $editGroup->img_server_filename;
			    			if(isset($photoFilename) && $photoFilename != "")
			    			{
		                   		$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
		                   		$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);
							}
							else
							{
								$photoFilename = "";
							}
							$totalMemberCount = 0;
							if(isset($memberArr)) {
								$totalMemberCount = count($memberArr);
							}
						
							$grpDetails["isUserAdmin"] = $isUserAdmin;
							$grpDetails["hasPostRight"] = $hasPostRight;
							$grpDetails["isFavorited"] = $isGroupFavorited;
							$grpDetails["isGrpLocked"] = $isGrpLocked;
							$grpDetails["photoUrl"] = $groupPhotoUrl;
							$grpDetails["photoThumbUrl"] = $groupPhotoThumbUrl;
							$grpDetails["photoFilename"] = $photoFilename;
							$grpDetails["totalNoteCount"] = $totalNoteCount;
							$grpDetails["activeMemberCount"] = $activeMemberCount;
							$grpDetails["totalMemberCount"] = $totalMemberCount;
							
	       					$response['grpDetails'] = $grpDetails;
						}
					}
       				$response['grpExists'] = $grpExists;
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
     * delete group content.
     *
     * @return json array
     */
    public function deleteContent()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $grpId = Input::get('grpId');
        $grpContId = Input::get('grpContId');
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
                $grpContId = sracDecryptNumberData($grpContId, $userSession);
				
            	$sharedByUserEmail = $user->email;  
            	
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
            	{
	                if($grpId > 0)
	                {
		                $empModelObj = New OrgEmployee;
		                $empModelObj->setConnection($orgDbConName);
		                
		                $sharerEmployee = $empModelObj->byId($orgEmpId)->first();
		                $sharedByEmpEmail = $sharerEmployee->email;             
		                $sharedByEmpName = $sharerEmployee->employee_name;
	                
		                $depMgmtObj = New ContentDependencyManagementClass;
		                $depMgmtObj->withOrgKey($user, $encOrgId);
		                	            		
		       			$group = $depMgmtObj->getGroupObject($grpId);
	            		if(isset($group) && $group->is_group_active == 1)
	            		{	
							$isFolder = FALSE;
							
	        				$status = 1; 
	        				$grpName = $group->name;
				            $depMgmtObj->deleteContent($grpContId, $isFolder, $sharedByEmpEmail);      

			                $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder, $grpId);
			                $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder, $grpId);         			
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
     * add member to group.
     *
     * @return json array
     */
    public function syncContent()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');        
        $grpId = Input::get('grpId');
        $grpContentId = Input::get('contentId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $grpId != "" && $grpContentId != "")
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
				 
	            $grpId = sracDecryptNumberData($grpId, $userSession);
	            $grpContentId = sracDecryptNumberData($grpContentId, $userSession);
				
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);  
				
		        $group = $depMgmtObj->getGroupObject($grpId);           		
	    		if(isset($group) && $group->is_group_active == 1)
	    		{
		        	$isUserGroupMember = $depMgmtObj->getGroupMemberObject($grpId); 
	    			if(isset($isUserGroupMember))
	    			{
	    				$status = 1;
	    				 
		                $isFolderFlag = FALSE;  
		                $groupContent = $depMgmtObj->getContentObject($grpContentId, $isFolderFlag);
						if(isset($groupContent))
	    				{
	    					$grpContentDetails = array();
							$performDownload = 1;
							
							$attachmentsArr = array();
		                	$groupContentAttachments = $depMgmtObj->getContentAttachments($grpContentId, $isFolderFlag);
		                	if(count($groupContentAttachments) > 0)
		                	{
		                		$i = 0;
		                    	$performDownload = 0;
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
							
							$frmTs = 0;
		                    if($groupContent->from_timestamp != NULL)
		                   	 	$frmTs = $groupContent->from_timestamp;
							
							$toTs = 0;
		                    if($groupContent->to_timestamp != NULL)
		                   	 	$toTs = $groupContent->to_timestamp;
		                   	 	
							$decContent = "";
							if(isset($groupContent->content) && $groupContent->content != "")
							{
								try
								{
									$decContent = Crypt::decrypt($groupContent->content);
							    	$encDecContent = rawurlencode($decContent);
							    	$decContent = utf8_encode($decContent);
								} 
								catch (DecryptException $e) 
								{
									//
								}
							}
							
		                    $grpContentDetails['syncId'] = sracEncryptNumberData($grpContentId, $userSession);
		                    $grpContentDetails['content'] = $decContent;
	                    	$grpContentDetails['encContent'] = $encDecContent;
		                    $grpContentDetails['contentType'] = $groupContent->content_type_id;
		                    $grpContentDetails['isMarked'] = $groupContent->is_marked;
		                    $grpContentDetails['createTimeStamp'] = $groupContent->create_timestamp;
		                    $grpContentDetails['updateTimeStamp'] = $groupContent->update_timestamp;
							$grpContentDetails['syncWithCloudCalendarGoogle'] = $groupContent->sync_with_cloud_calendar_google;
							$grpContentDetails['syncWithCloudCalendarOnedrive'] = $groupContent->sync_with_cloud_calendar_onedrive;
		                    $grpContentDetails['isLocked'] = $groupContent->is_locked;
	                    	$grpContentDetails['isShareEnabled'] = $groupContent->is_share_enabled;
					        $grpContentDetails['colorCode'] = $groupContent->color_code;
		                	$grpContentDetails['remindBeforeMillis'] = $groupContent->remind_before_millis;
					    	$grpContentDetails['repeatDuration'] = $groupContent->repeat_duration;
							$grpContentDetails['isCompleted'] = $groupContent->is_completed;
							$grpContentDetails['isSnoozed'] = $groupContent->is_snoozed;
							$grpContentDetails['reminderTimestamp'] = isset($groupContent->reminder_timestamp) ? $groupContent->reminder_timestamp : 0;
		                    $grpContentDetails['fromTimeStamp'] = $frmTs;
		                    $grpContentDetails['toTimeStamp'] = $toTs;
		                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
		                    $grpContentDetails['attachments'] = $attachmentsArr;
							
							$response["contentDetails"] = $grpContentDetails;		    				
	    				}
		    			else
		    			{
			                $status = -1;
		                	$msg = Config::get('app_config_notif.inf_no_content_found').'2'; 

        					$tempEncGrpContentId = Input::get('contentId');
        					$tempEncGrpId = Input::get('grpId');

		                	Log::info('================ no content happened ORG start ================');
		                	Log::info('grpContentId : '.$grpContentId);
		                	Log::info('tempEncGrpContentId : '.$tempEncGrpContentId);
		                	Log::info('grpId : '.$grpId);
		                	Log::info('tempEncGrpId : '.$tempEncGrpId);
		                	Log::info('================ no content happened ORG end ================');
						}
	    			}
	    			else
	    			{
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_user_not_group_member');    
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
     * resync group data.
     *
     * @return json array
     */
    public function resyncData()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId'); 
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
        		
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $i = 0;
                $groupList = array();     
                $userGroups = GroupMember::ofUser($userId)->ofDistinctGroup()->get();           
                foreach ($userGroups as $userGroup) 
                {
					$grpId = $userGroup->group_id;
					$name = $userGroup->group->name;
					
					$isGrpLocked = $userGroup->is_locked;
					$isFavorited = $userGroup->is_favorited;

					$isAdmin = 0;					
					$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
	    			if(isset($isUserGroupAdmin))  
	    			{
						$isAdmin = 1;
					} 
					
					$contentsArr = array();
                	$grpContents = GroupContent::ofGroup($grpId)->get();
                	foreach($grpContents as $grpContent)
                	{
						$grpContentDetails = array();
	                    
	                    /*if($attachmentRetainDays >= 0)
	                	{                		
		                    $performDownload = 0;
							$utcContentCreateDt = Carbon::createFromTimeStampUTC($grpContent->create_timestamp);
							$utcContentCreateTs = $utcContentCreateDt->timestamp;
		                    if($utcContentCreateTs >= $utcRetainMinTs)
		                    {
								$performDownload = 1;
							}
						}
						else
						{
							$performDownload = 1;
						}*/

	                    $attachmentsArr = array();
	                    /*
	                    $contentAttachments = AppuserContentAttachment::ofUserContent($grpContent->group_content_id)->get();

	                    $j = 0;
	                    foreach ($contentAttachments as $contentAttachment) 
	                    {  
	                   		$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename);          
	                        $attachmentsArr[$j]['name'] = $contentAttachment->filename;
	                        $attachmentsArr[$j]['pathname'] = $contentAttachment->server_filename;
	                        $attachmentsArr[$j]['size'] = $contentAttachment->filesize;
	                        $attachmentsArr[$j]['url'] = $attachmentUrl;
	                        $attachmentsArr[$j]['performDownload'] = $performDownload;
	                        $attachmentsArr[$j]['syncId'] = $contentAttachment->content_attachment_id;
	                        $j++;
	                    }*/
	                    $attachmentCnt = count($attachmentsArr);
	                    					
						$tagsArr = array();
	                    $contentTags = GroupContentTag::ofGroupContent($grpContent->group_content_id)->get();
	                    foreach ($contentTags as $contentTag) 
	                    {
	                        $tagDetails = $contentTag->tag()->ofUser($userId)->first();                    
	                        if(isset($tagDetails))
	                        {
								array_push($tagsArr, $tagDetails->tag_id);
	                        }
	                    }
	                    $tagCnt = count($tagsArr);
						
						$frmTs = 0;
	                    if($grpContent->from_timestamp != NULL)
	                   	 	$frmTs = $grpContent->from_timestamp;
						
						$toTs = 0;
	                    if($grpContent->to_timestamp != NULL)
	                   	 	$toTs = $grpContent->to_timestamp;
                   	 	
	                   	$sharedByEmail = "";
	                   	/*if($grpContent->shared_by_email != NULL)
	                   		$sharedByEmail = $grpContent->shared_by_email;*/
	                   		
						$decContent = "";
						if(isset($grpContent->content) && $grpContent->content != "")
						{
							try
							{
								$decContent = Crypt::decrypt($grpContent->content);
						    	$encDecContent = rawurlencode($decContent);
						    	$decContent = utf8_encode($decContent);
							} 
							catch (DecryptException $e) 
							{
								//
							}
						}
						
	                    $grpContentDetails['syncId'] = $grpContent->group_content_id;
	                    $grpContentDetails['content'] = $decContent;
	                    $grpContentDetails['encContent'] = $encDecContent;
	                    $grpContentDetails['contentType'] = $grpContent->content_type_id;
	                    $grpContentDetails['groupId'] = $grpContent->group_id;
	                    $grpContentDetails['isMarked'] = $grpContent->is_marked;
	                    $grpContentDetails['createDate'] = $grpContent->create_date;
	                    $grpContentDetails['createTime'] = $grpContent->create_time;
	                    $grpContentDetails['fromDate'] = $grpContent->from_date;
	                    $grpContentDetails['fromTime'] = $grpContent->from_time;
	                    $grpContentDetails['toDate'] = $grpContent->to_date;
	                    $grpContentDetails['toTime'] = $grpContent->to_time;
	                    $grpContentDetails['createTimeStamp'] = $grpContent->create_timestamp;
	                    $grpContentDetails['updateTimeStamp'] = $grpContent->update_timestamp;
						$grpContentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
						$grpContentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
		                $grpContentDetails['isLocked'] = $grpContent->is_locked;
                    	$grpContentDetails['isShareEnabled'] = $grpContent->is_share_enabled;
					    $grpContentDetails['colorCode'] = $grpContent->color_code;
	                	$grpContentDetails['remindBeforeMillis'] = $grpContent->remind_before_millis;
				    	$grpContentDetails['repeatDuration'] = $grpContent->repeat_duration;
						$grpContentDetails['isCompleted'] = $grpContent->is_completed;
						$grpContentDetails['isSnoozed'] = $grpContent->is_snoozed;
						$grpContentDetails['reminderTimestamp'] = isset($grpContent->reminder_timestamp) ? $grpContent->reminder_timestamp : 0;
	                    $grpContentDetails['sharedByEmail'] = $sharedByEmail;
	                    $grpContentDetails['fromTimeStamp'] = $frmTs;
	                    $grpContentDetails['toTimeStamp'] = $toTs;
	                    $grpContentDetails['tagCnt'] = $tagCnt;
	                    $grpContentDetails['tags'] = $tagsArr;
	                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
	                    $grpContentDetails['attachments'] = $attachmentsArr;
						
						array_push($contentsArr, $grpContentDetails);
					}  
										
					$groupList[$i]["syncId"] = $grpId;
					$groupList[$i]["name"] = $name;
					$groupList[$i]["isUserAdmin"] = $isAdmin;
					$groupList[$i]["contentArr"] = $contentsArr;
					$groupList[$i]["contentCnt"] = count($contentsArr);
					$groupList[$i]["isGrpLocked"] = $isGrpLocked;
					$groupList[$i]["isFavorited"] = $isFavorited;
					$i++;		
				} 
				$groupCnt = count($groupList);  
                
                $response['groupCnt'] = $groupCnt;
                $response['groupArr'] = $groupList;      			
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
     * Folder List.
     *
     * @return json array
     */
    public function loadSelectGroupList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $selOrgId = Input::get('selOrgId');
        $loginToken = Input::get('loginToken');
        $searchStr = Input::get('searchStr');

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
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);	
                
                $groupArr = array();
                $arrForSorting = array();                
                $userGroups = $depMgmtObj->getAllGroupsFoUser();
                foreach ($userGroups as $group) 
                {
					$groupId = $group->group_id;						
					$groupName = $group->name;
					
					$groupObj = array();
					$groupObj["id"] = sracEncryptNumberData($groupId, $userSession);
					$groupObj["text"] = $groupName;
					array_push($groupArr, $groupObj);
					array_push($arrForSorting, $groupName);
                }
                array_multisort($arrForSorting, $groupArr);   

                $status = 1;
				$response = array('results' => $groupArr );
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
    public function loadGroupDetailsModal()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId'); 
        $grpId = Input::get('groupId');
        $loginToken = Input::get('loginToken');
        $defIconUrl = asset(Config::get('app_config.icon_default_app_group'));

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

                $grpId = sracDecryptNumberData($grpId, $userSession);
				             
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);  
                $orgId = $depMgmtObj->getOrganizationId(); 
				
		        $group = $depMgmtObj->getGroupObject($grpId);

		        if(isset($group) && ($orgId == 0 || ($orgId > 0 && $group->is_group_active == 1)))
		        {
		        	$members = $depMgmtObj->getGroupMembers($grpId);
			        $isUserAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
					$isFolder = FALSE;
					$totalNoteCount = $depMgmtObj->getAllContentModelObj($isFolder, $grpId)->count();
			        
	    			$memberArr = array();
	    			$activeMemberCount = 0;
					if(isset($members) && count($members) > 0)
	    			{
	    				$adminMemberArr = array();
	    				$activeMemberArr = array();
	    				$inActiveMemberArr = array();
	    				foreach($members as $grpMember)
	    				{
	    					$memberEmpId = $grpMember->employee_id;
	    					$memberIsAdmin = $grpMember->is_admin;
	    					$memberIsGhost = 0;
	    					if(isset($grpMember->is_ghost)) {
	    						$memberIsGhost = $grpMember->is_ghost;							
							}
	    					
	    					if($memberIsGhost == 0) {
								$isActiveMember = 0;
		    					$memberNoteCount = $depMgmtObj->getGroupMemberContentCount($grpId, $grpMember->member_id);
		    					if($memberNoteCount > 0)
		    					{
									$activeMemberCount++;
									$isActiveMember = 1;
								}
								
								$grpMember->isActive = $isActiveMember;
		            			$grpMember->noteCount = $memberNoteCount;
		    					
		    					if($memberIsAdmin == 1) {
									array_push($adminMemberArr, $grpMember);
								}
								else if($isActiveMember == 1) {
									array_push($activeMemberArr, $grpMember);									
								}
								else {
									array_push($inActiveMemberArr, $grpMember);									
								}
							}
						}
						
	    				if(count($adminMemberArr) > 0) {
							$adminMemberArr = collect($adminMemberArr);
							$adminMemberArr = $adminMemberArr->sortBy('name');
							$adminMemberArr = $adminMemberArr->toArray();
							
							$memberArr = array_merge($memberArr, $adminMemberArr);
						}
	    				if(count($activeMemberArr) > 0) {
							$activeMemberArr = collect($activeMemberArr);
							$activeMemberArr = $activeMemberArr->sortByDesc('noteCount');
							$activeMemberArr = $activeMemberArr->toArray();
							
							$memberArr = array_merge($memberArr, $activeMemberArr);
						}
	    				if(count($inActiveMemberArr) > 0) {
							$inActiveMemberArr = collect($inActiveMemberArr);
							$inActiveMemberArr = $inActiveMemberArr->sortBy('name');
							$inActiveMemberArr = $inActiveMemberArr->toArray();
							
							$memberArr = array_merge($memberArr, $inActiveMemberArr);
						}
	    			}		        		        
			        
			        $totalAvailableSpaceKb = $depMgmtObj->getAvailableUserQuota(TRUE);
	                
	                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
			        if($orgId > 0)
			        	$isUserAdmin = FALSE;
			        
			        $isOpenGroup = 0;
			        $allottedSpaceMb = 0;
			        $usedSpaceMb = 0;
			        $availableSpaceMb = 0;
			        if(isset($group))
					{
						$allottedKbs = $group->allocated_space_kb;
						$usedKbs = $group->used_space_kb;
						$availableKb = $allottedKbs - $usedKbs;
						
						$totalAvailableSpaceKb += $allottedKbs;
						
						$allottedSpaceMb = CommonFunctionClass::convertKbToMb($allottedKbs);
						$availableSpaceMb = CommonFunctionClass::convertKbToMb($availableKb);
						$usedSpaceMb = $allottedSpaceMb - $availableSpaceMb;
					}
			        
			        $totalAvailableSpaceMb = CommonFunctionClass::convertKbToMb($totalAvailableSpaceKb);
			        
			        $groupQuotaStr = "$availableSpaceMb MB remaining of your $allottedSpaceMb MB space limit";
			        
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
						$groupPhotoThumbUrl = $defIconUrl;
					}
					$totalMemberCount = 0;
					if(isset($memberArr)) {
						$totalMemberCount = count($memberArr);
					}

					$showSendInviteBtn = false;
					if($isUserAdmin && $orgId == 0)
					{
						$showSendInviteBtn = true;
					}
			        
			        $viewDetails = array();
			        $viewDetails['groupId'] = sracEncryptNumberData($grpId, $userSession);
			        $viewDetails['group'] = $group;
			        $viewDetails['members'] = $memberArr;
			        $viewDetails['isUserAdmin'] = $isUserAdmin;
			        $viewDetails['groupQuotaStr'] = $groupQuotaStr;
			        $viewDetails['allottedSpaceMb'] = $allottedSpaceMb;
			        $viewDetails['usedSpaceMb'] = $usedSpaceMb;
			        $viewDetails['totalAvailableSpaceMb'] = $totalAvailableSpaceMb;
					$viewDetails["totalNoteCount"] = $totalNoteCount;
					$viewDetails["activeMemberCount"] = $activeMemberCount;
					$viewDetails["totalMemberCount"] = $totalMemberCount;
					$viewDetails["groupPhotoUrl"] = $groupPhotoUrl;
					$viewDetails["groupPhotoThumbUrl"] = $groupPhotoThumbUrl;
	                $viewDetails['orgKey'] = $encOrgId;
	                $viewDetails['showSendInviteBtn'] = $showSendInviteBtn;

					$isOpenGroup = isset($group->is_open_group) ? $group->is_open_group : 0;

					$viewDetails["isOpenGroup"] = $isOpenGroup;

					if($isUserAdmin && $isOpenGroup == 1 && isset($group->open_group_reg_code) && $group->open_group_reg_code != "")
					{
						$decOpenGroupRegCode = Crypt::decrypt($group->open_group_reg_code);
						$viewDetails["openGroupRegCode"] = $decOpenGroupRegCode;

						$shareJoinGroupStr = $depMgmtObj->getGroupShareJoinInvitationLink($group);
						$viewDetails["openGroupJoinHtml"] = $shareJoinGroupStr;
					}
	           
		            $_viewToRender = View::make('orggroup.partialview._groupDetailsModal', $viewDetails);
		            $_viewToRender = $_viewToRender->render();
		            
		            $response['view'] = $_viewToRender;
		            $response['memberArr'] = $memberArr;
		            $response['group'] = $group;

                	CommonFunctionClass::setLastSyncTs($userId, $loginToken); 
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
    
    public function validateGroupName()
    {
    	$isAvailable = FALSE;
    	
        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId'); 
        $grpId = Input::get('groupId');
        $loginToken = Input::get('loginToken');
        $groupName = Input::get('group_name');
        
        $response = array();
        if($encUserId != "" && isset($loginToken) && $loginToken != "")
        {
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(isset($userSession))
	        	{	
				
                	$grpId = sracDecryptNumberData($grpId, $userSession);

	                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
	                
	                if($orgId > 0)
	                {
						$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
						$modelObj = New OrgGroup;
						$modelObj->setConnection($orgDbConName);
					}
					else
					{
						$modelObj = New Group;
					}
		            
		            if(isset($modelObj))
		            {
						$modelObj = $modelObj->where('name','=',$groupName);
						
						if($grpId > 0)
							$modelObj = $modelObj->byId($grpId);
							
		            	$grpData = $modelObj->first();
					}
				                 
			        
			        if(!isset($grpData))
			            $isAvailable = TRUE;
				}
                
            }
        }
        $response['valid'] = $isAvailable;

        return Response::json($response); 
	}

    
    /**
     * save app user broadcast details.
     *
     * @return json array
     */
    public function invertFavoritedStatus()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');      
        $grpId = Input::get('grpId');
        $isFavorited = Input::get('isFavorited');
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
				
				$status = 1;
				
				$isFavorited = $isFavorited*1;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);		 
				
		        $group = $depMgmtObj->getGroupObject($grpId);

		        if(isset($group) && $group->is_group_active == 1)
		        {
                	$depMgmtObj->setGroupFavoritedStatus($grpId, $isFavorited);
		        }
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken); 
                
       			$response['isFavorited'] = $isFavorited;
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
    public function invertLockedStatus()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');  
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $grpId = Input::get('grpId');
        $isGrpLocked = Input::get('isGrpLocked');
        $folId = Input::get('folId');
        $isFolLocked = Input::get('isFolLocked');

		if(!isset($isGrpLocked) || $isGrpLocked != 1)
		{
			$isGrpLocked = 0;
		}

		if(!isset($isFolLocked) || $isFolLocked != 1)
		{
			$isFolLocked = 0;
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
                $folId = sracDecryptNumberData($folId, $userSession);

				$isFolder = FALSE;
				if($isFolderFlag == 1)
				{
					$isFolder = TRUE;
				}
				
				$isGrpLocked = $isGrpLocked*1;
				$isFolLocked = $isFolLocked*1;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);	
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();	 

                if(isset($userConstant))
                {
                	$hasFolderPasscode = $userConstant->folder_passcode_enabled; 
		        	if($hasFolderPasscode == 1)
		        	{
		        		$orgId = $depMgmtObj->getOrganizationId();

				        if($isFolder)
				        {
				        	$folder = $depMgmtObj->getFolderObject($folId);
							if(isset($folder))
					        {
			                	$depMgmtObj->setFolderLockedStatus($folId, $isFolLocked);

		                		$this->sendFolderPinChangedMessageToDevice($userId, $encOrgId, $loginToken);
					        }
				        }
				        else
				        {
				        	$group = $depMgmtObj->getGroupObject($grpId);
							if(isset($group) && $group->is_group_active == 1)
					        {
			                	$depMgmtObj->setGroupLockedStatus($grpId, $isGrpLocked);

			    				$isAddOp = 0;
								$isRename = 0;

			    				if($orgId > 0)
			    				{
			    					$orgEmpId = $depMgmtObj->getOrganizationEmployeeId();
									$this->sendOrgGroupAddedMessageToDevice($orgEmpId, $grpId, $isRename, $orgId, $isAddOp);
			    				}
			    				else
			    				{
			    					$sharedByUserEmail = $user->email;
									$this->sendGroupAddedMessageToDevice($userId, $grpId, $isRename, $sharedByUserEmail, $loginToken, $isAddOp);
			    				}
					
					        }
				        }
                
		                $status = 1;
		       			$response['isGrpLocked'] = $isGrpLocked;
		       			$response['isFolLocked'] = $isFolLocked;
		        	}
		            else
		            {
		                $status = -1;
		                $msg = 'Enable Folder/Group PIN first';       
		            }
                }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_data');       
	            }
	                
            	CommonFunctionClass::setLastSyncTs($userId, $loginToken);
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
    
    public function getGroupMembershipShareModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $grpId = Input::get('grpId');
        
        $response = array();
        if($encUserId != "" && $grpId != "")
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $group = $depMgmtObj->getGroupObject($grpId); 
                $isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId); 

                if($isUserGroupAdmin)
                {
               		$status = 1;

               		$pageDesc = 'Invite';

	                $viewDetails = array();
	                $viewDetails['page_description'] = $pageDesc;
	                $viewDetails['tzOfs'] = $offsetInMinutes * 1;
	                $viewDetails['tzStr'] = $tzStr;
	                $viewDetails['orgKey'] = $encOrgId;
	                $viewDetails['groupId'] = sracEncryptNumberData($grpId, $userSession);
	                $viewDetails['group'] = $group;
	           
	                $_viewToRender = View::make('orggroup.partialview._groupShareMembershipModal', $viewDetails);
	                $_viewToRender = $_viewToRender->render();

	                $response['view'] = $_viewToRender; 
                }
                else
                {
                    $status = -1;
                    $msg = 'The group invitation can only be sent by the admin';   
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
    
    public function sendGroupJoinInvitation()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $grpId = Input::get('grpId');
        $recipientName = Input::get('recipientName');
        $recipientEmail = Input::get('recipientEmail');
        
        $response = array();
        if($encUserId != "" && $grpId != "" && $recipientName != "" && $recipientEmail != "")
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

                $grpId = sracDecryptNumberData($grpId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $isEmailValid = CommonFunctionClass::validateEmailAddress($recipientEmail);
                if($isEmailValid)
                {
                    $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();
        
                    $group = $depMgmtObj->getGroupObject($grpId); 
                    if(isset($group))
                    {
                		$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId); 

                		if($isUserGroupAdmin)
                		{
                            $status = 1;
                            $depMgmtObj->onGroupParticipantInvited($grpId, $group, $recipientName, $recipientEmail);

	                        /* 
                			$isOpenGroup = isset($group->is_open_group) ? $group->is_open_group : 0;
	                        if($isOpenGroup == 1)
	                        {
	                            $status = 1;
	                            $depMgmtObj->onGroupParticipantInvited($grpId, $group, $recipientName, $recipientEmail);
	                        }
	                        else
	                        {
	                            $status = -1;
	                            $msg = 'The group invitation cannot be sent as it is not an open group';   
	                        }
	                        */
                		}
                        else
                        {
                            $status = -1;
                            $msg = 'The group invitation can only be sent by the admin';   
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
                    $msg = 'Not a valid email';     
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
    public function joinOrgGroupAsMemberViaLink()
    {
        $msg = "";
        $status = 0;

        $encGroupId = Input::get('g');

        $response = array();

        if($encGroupId != "")
        {
            $decDependencies = OrganizationClass::getAppuserJoinGroupDependenciesFromInvitationUrl($encGroupId);
            if(isset($decDependencies))
            {
                $userId = $decDependencies['userId'];
                $orgId = $decDependencies['orgId'];
                $orgEmpId = $decDependencies['orgEmpId'];
                $groupId = $decDependencies['groupId']; 
                $recipientEmail = $decDependencies['email'];            
                
                $user = Appuser::byId($userId)->first();
                
                if(isset($user) && $user->is_verified)
                {
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
                    $constObj = $depMgmtObj->getEmployeeOrUserConstantObject();

                    $group = $depMgmtObj->getGroupObject($groupId); 
                    if(isset($group))
                    {
                		$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($groupId); 

                		if($isUserGroupAdmin)
                		{
				            $appuserWithEmailExists = Appuser::ofEmail($recipientEmail)->verified()->first();
				            if(isset($appuserWithEmailExists))
				            {
				                $participantAppuserId = $appuserWithEmailExists->appuser_id;

			                    $memberDepMgmtObj = New ContentDependencyManagementClass;
			                    $memberDepMgmtObj->withUserIdOrgIdAndEmpId($appuserWithEmailExists, $orgId, $orgEmpId);

				                $isAdmin = 0;
				                $isGroupSelfJoined = 1;
						            			
				    			$isFavorited = Config::get('app_config.default_group_is_favorited');
				    			$isLocked = Config::get('app_config.default_group_is_locked');
								$sharedByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

				                $memberDepMgmtObj->addNewGroupMember($groupId, $group, $participantAppuserId, $isAdmin, $isLocked, $isFavorited, $isGroupSelfJoined, $sharedByUserEmail); 

                            	$status = 1;
                            	$msg = 'You have been added as member to the group';
				            }
				            else
				            {
                            	$status = -1;
                            	$msg = 'Please sign up with HyLyt first';
				            }
                		}
                        else
                        {
                            $status = -1;
                            $msg = 'The group invitation can only be sent by the admin';   
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = 'No such group';     
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
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }            

        $response['status'] = $status;
        $response['msg'] = "$msg";

        //return Response::json($response);
        print_r("<h4>");
        print_r($msg);        
        print_r("</h4>");
    }
}
