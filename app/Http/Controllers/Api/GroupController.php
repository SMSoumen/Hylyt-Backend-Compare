<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContact;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
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
use File;
use App\Libraries\MailClass;
use App\Libraries\FileUploadClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentManagementClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use View;

class GroupController extends Controller
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
     
      /**
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getAppuserGroupList()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId'); 
        $searchStr = Input::get('searchStr');
        $loginToken = Input::get('loginToken');
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
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);  
                $userGroups = $depMgmtObj->getAllGroupsFoUser($searchStr);
                
                $i = 0; 
                $isFolder = FALSE;
                $groupList = array(); 
                $groupSelectList = array(); 
                $arrForSorting = array();                
                foreach ($userGroups as $userGroup) 
                {
					$grpId = $userGroup->group_id;
					$name = $userGroup->name;
					$isTwoWay = $userGroup->is_two_way;
					$isFavorited = $userGroup->is_favorited;
					$isGrpLocked = $userGroup->is_locked;
					$description = $userGroup->description;
					 
					$isAdmin = 0;					
					$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
	    			if($isUserGroupAdmin)  
	    			{
						$isAdmin = 1;
					}

					$hasPostRight = 0;					
					$userHasGroupPostRight = $depMgmtObj->getUserHasGroupPostRight($grpId);
	    			if($userHasGroupPostRight)  
	    			{
						$hasPostRight = 1;
					}
					
					if($orgId > 0)
                    {	
                    	$idColName = 'org_group_contents'.'.group_content_id';
					}
					else
                    { 
                    	$idColName = 'group_contents'.'.group_content_id';
					}
					
					$groupContents = $depMgmtObj->getAllContentModelObj($isFolder, $grpId);
    				$groupContents = $groupContents->select(["$idColName as content_id"]);
    				$groupContents = $groupContents->get();
    				
					$groupContentCnt = count($groupContents);	  

					
					$photoFilename = $userGroup->img_server_filename;
					$groupPhotoUrl = "";
					$groupPhotoThumbUrl = "";
					if(isset($photoFilename) && $photoFilename != "")
					{
						$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
						$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
					}     
					
					if($forShare == 0 || ($forShare == 1 && ($hasPostRight == 1 || $isAdmin == 1)))
					{
						$groupSelectList[$i]["id"] = sracEncryptNumberData($grpId, $userSession);
						$groupSelectList[$i]["text"] = $name;
						
						
						$groupList[$i]["syncId"] = sracEncryptNumberData($grpId, $userSession);
						$groupList[$i]["name"] = $name;
						$groupList[$i]["description"] = $description;
		                $groupList[$i]['contentCnt'] = $groupContentCnt;
						$groupList[$i]["isUserAdmin"] = $isAdmin;
						$groupList[$i]["hasPostRight"] = $hasPostRight;
						$groupList[$i]["isTwoWay"] = $isTwoWay;
						$groupList[$i]["isFavorited"] = $isFavorited;
						$groupList[$i]["isGrpLocked"] = $isGrpLocked;
						$groupList[$i]["photoUrl"] = $groupPhotoUrl;
						$groupList[$i]["photoThumbUrl"] = $groupPhotoThumbUrl;
						$groupList[$i]["photoFilename"] = $photoFilename;
		                $arrForSorting[$i] = strtolower($name);
						$i++;
					}		
				} 
                array_multisort($arrForSorting, $groupList);   
                array_multisort($arrForSorting, $groupSelectList);   

				$groupCnt = count($groupList);      
				$response["groupCnt"] = $groupCnt;          
				$response["groupRes"] = $groupList;   
				
				if($forShare == 1)
				{        
					$response["results"] = $groupSelectList;  
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
    public function saveGroupDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');        
        $grpId = Input::get('grpId');
        $grpName = Input::get('grpName');
        $description = Input::get('description');
        $isFavorited = Input::get('isFavorited');
        $memberContactIdArr = Input::get('memberArr');
        $loginToken = Input::get('loginToken');
		$groupPhotoFile = Input::file('photo_file');
		$isGrpLocked = Input::get('isGrpLocked');
		$isOpenGroup = Input::get('isOpenGroup');
        
        if(!isset($isFavorited)) {
			$isFavorited = 0;
		}

		if(!isset($isGrpLocked) || $isGrpLocked != 1)
		{
			$isGrpLocked = 0;
		}

		if(!isset($isOpenGroup) || $isOpenGroup != 1)
		{
			$isOpenGroup = 0;
		}
        
        $memberContactIdArr = json_decode($memberContactIdArr);

        $response = array();

        if($encUserId != "" || count($memberContactIdArr) > 0)
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
                $memberContactIdArr = sracDecryptNumberArrayData($memberContactIdArr, $userSession);
				
				$defaultGroupSpaceKb = Config::get('app_config.default_group_space_kb');
				
            	$isAdd = TRUE;  
            	$oldGroupName = "";
            	$sharedByUserEmail = $user->email;
            	$hasSpace = TRUE;
            	
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");		                
            	$availableKbs = $depMgmtObj->getAvailableUserQuota(TRUE);

            	$generateOpenGroupRegCode = FALSE;
            	$existingOpenGroupRegCode = NULL;
            	
            	$group = Group::byId($grpId)->first();
            	if(!isset($group))
            	{
            		if($availableKbs >= $defaultGroupSpaceKb)
            		{
						$group = new Group;
						$group->created_by = $userId;
						$group->allocated_space_kb = $defaultGroupSpaceKb;
						$group->used_space_kb = 0;
						$group->is_two_way = 1;
					}
					else
					{
						$hasSpace = FALSE;
					}	    

		            if($isOpenGroup == 1)
		            {
		            	$generateOpenGroupRegCode = TRUE;
		            }				
				}
				else
				{
					$isAdd = FALSE;
					$oldGroupName = $group->name;    

		            $existingOpenGroupRegCode = $group->open_group_reg_code;

		            if($isOpenGroup == 1 && $group->is_open_group == 0)
		            {
		            	$generateOpenGroupRegCode = TRUE;
		            }
				}
				
				if($hasSpace)
				{
	                $status = 1;
					
					$fileName = "";
		        	if(isset($groupPhotoFile) && File::exists($groupPhotoFile) && $groupPhotoFile->isValid()) 
		            {
		                $fileUpload = new FileUploadClass;
		                $fileName = $fileUpload->uploadOrganizationGroupPhotoImage($groupPhotoFile, 0);
		            }    

		            $openGroupRegCode = NULL;
		            if($isOpenGroup == 1)
		            {
		            	if($generateOpenGroupRegCode)
			            {
			            	$existingGroupRegCodeArr = [];
			            	$allOpenGroups = Group::exceptId($grpId)->isOpenGroup()->get();
			            	foreach ($allOpenGroups as $openGroup) {
			            		if(isset($openGroup->open_group_reg_code) && $openGroup->open_group_reg_code != "")
			            		{
				            		$decRegCode = Crypt::decrypt($openGroup->open_group_reg_code);
				            		array_push($existingGroupRegCodeArr, $decRegCode);			            			
			            		}
			            	}

			            	$genGroupRegCode = CommonFunctionClass::generateOpenGroupRegistrationCodeString($existingGroupRegCodeArr);

			            	$openGroupRegCode = Crypt::encrypt($genGroupRegCode);
			            }
			            elseif(isset($existingOpenGroupRegCode))
			            {
			            	$openGroupRegCode = $existingOpenGroupRegCode;
			            }
		            }
	                
					$group->name = $grpName;
					$group->description = $description;
					$group->img_server_filename = $fileName;
					$group->is_open_group = $isOpenGroup;
					$group->open_group_reg_code = $openGroupRegCode;
					$group->save();
					$serverGroupId = $group->group_id;
			
					$isRename = 0;
					if(!$isAdd && $oldGroupName != $grpName)
						$isRename = 1;
					         	
                	$existingMemberUserIdArr = array();
            		$groupMembers = GroupMember::ofGroup($serverGroupId)->get();   
					foreach($groupMembers as $gm)
					{
						$memUserId = $gm->member_appuser_id;
						array_push($existingMemberUserIdArr, $memUserId);
					}
	            	         
	                $memberUserIdArr = array();
					array_push($memberUserIdArr, $userId);
					foreach ($memberContactIdArr as $memberContactId) 
	                {
	            		$memberUserDetails = AppuserContact::ofUserContact($memberContactId)->first();
	            		
	            		if(isset($memberUserDetails))
	            		{
	            			$selContactEmail = $memberUserDetails->email;
            				$selContactContactNo = $memberUserDetails->contact_no;

	            			// $userRegistered = Appuser::ofEmail($selContactEmail)->active()->first();
	            			$userRegistered = Appuser::forRegisteredAppUserByEmailOrContactNo($selContactEmail, $selContactContactNo)->active()->first();
		            		if(isset($userRegistered))
		            		{
								$registeredAppuserId = $userRegistered->appuser_id;
								$memberUserDetails->regd_appuser_id = $registeredAppuserId;
								$memberUserDetails->save();
								
								array_push($memberUserIdArr, $registeredAppuserId);	
		            		}				
						}
	                }  
	                
	                foreach ($memberUserIdArr as $memberUserId) 
	                {
	                	if(!in_array($memberUserId, $existingMemberUserIdArr))	
	                	{	
	            			//Manage is admin thingy
	            			$isAdmin = 0;
	            			$isLocked = 0;
	            			$isGroupSelfJoined = 0;
	            			if($memberUserId == $userId)
	            			{
	            				$isAdmin = 1;

	            				if(isset($isGrpLocked) && $isGrpLocked == 1)
	            				{
            						$isLocked = 1;
	            				}
	            			}	

	            			
							//Insert Member
							$groupMember = New GroupMember;
							$groupMember->group_id = $serverGroupId;
							$groupMember->member_appuser_id = $memberUserId;
							$groupMember->is_admin = $isAdmin;
							$groupMember->is_locked = $isLocked;
							$groupMember->is_self_joined = $isGroupSelfJoined;
							//$groupMember->has_post_right = 1;
							$groupMember->is_favorited = $isFavorited;
							$groupMember->save();
							
							MailClass::sendUserAddedToGroupMail($memberUserId, $group, $user);
							
							$isAddOp = 1;
							if($memberUserId != $userId)
							{										
	       						$this->sendGroupAddedMessageToDevice($memberUserId, $serverGroupId, $isRename, $sharedByUserEmail, NULL, $isAddOp, $oldGroupName);
							}
							else
							{
	       						$this->sendGroupAddedMessageToDevice($memberUserId, $serverGroupId, $isRename, $sharedByUserEmail, $loginToken, $isAddOp, $oldGroupName);
							}
						}
	                }
	                
	                foreach ($existingMemberUserIdArr as $memberUserId) 
	                {
	                	if(!in_array($memberUserId, $memberUserIdArr))	
	                	{
							//Delete Member
	            			$groupMember = GroupMember::ofUser($memberUserId)->first();
	            			$groupMember->delete();
	            			
	            			if($memberUserId != $userId)
							{										
	       						$this->sendGroupDeletedMessageToDevice($memberUserId, $serverGroupId, $grpName, $sharedByUserEmail);
							}
							else
							{
	       						$this->sendGroupDeletedMessageToDevice($memberUserId, $serverGroupId, $grpName, $sharedByUserEmail, $loginToken);
							}
						}
	                }

					$depMgmtObj->recalculateUserQuota(TRUE);
					
					$perAllocatedSpaceKb = $depMgmtObj->getAllocatedUserQuota(TRUE);
					$perAvailableSpaceKb = $depMgmtObj->getAvailableUserQuota(TRUE);
					$perUsedSpaceKb = $depMgmtObj->getUsedUserQuota(TRUE);
					
					$orgId = 0;
					$photoFilename = $group->img_server_filename;
					$groupPhotoUrl = "";
					$groupPhotoThumbUrl = "";
					if(isset($photoFilename) && $photoFilename != "")
					{
						$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
						$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
					}
					
					$response['syncId'] = sracEncryptNumberData($serverGroupId, $userSession);
					$response['hasPostRight'] = 1;
					$response['isTwoWay'] = $group->is_two_way;
					$response['allocKb'] = $group->allocated_space_kb;
					$response['usedKb'] = $group->used_space_kb;
					$response['perAllocKb'] = $perAllocatedSpaceKb;
					$response['perUsedKb'] = $perUsedSpaceKb;
					$response['isFavorited'] = $isFavorited;
					$response['isGrpLocked'] = $isGrpLocked;
					$response['photoUrl'] = $groupPhotoUrl;
					$response['photoThumbUrl'] = $groupPhotoThumbUrl;
				}
				else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_insufficient_space');     
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
       			
				$grpExists = 0;       			
        		if($grpId > 0)
        		{	
        			$encOrgId = "";
        			
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgKey($user, $encOrgId);

	                $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();
		                				
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
						$grpDetails["isOpenGroup"] = $editGroup->is_open_group;

							
						$isFolder = FALSE;
					
						$photoFilename = $editGroup->img_server_filename;
						$groupPhotoUrl = "";
						$groupPhotoThumbUrl = "";
						if(isset($photoFilename) && $photoFilename != "")
						{
							$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl(0, $photoFilename);
							$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl(0, $photoFilename);
						}  
						
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
            					$memberUserId = $grpMember->member_appuser_id;
            					$memberIsAdmin = $grpMember->is_admin;
            					$memberHasPostRight = 1;//$grpMember->has_post_right;
								$memberEmail = $grpMember->email;
            					$memberName = $grpMember->name;
								
								$isSelf = 0;            					
            					if($memberUserId == $userId)
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
            					
            					$memberDetails = array();
            					$memberDetails["id"] = sracEncryptNumberData($memberUserId, $userSession);
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
						$totalMemberCount = 0;
						if(isset($memberArr)) {
							$totalMemberCount = count($memberArr);
						}

						if($isUserAdmin == 1 && $editGroup->is_open_group == 1 && isset($editGroup->open_group_reg_code) && $editGroup->open_group_reg_code != "")
						{
							$decOpenGroupRegCode = Crypt::decrypt($editGroup->open_group_reg_code);
							$grpDetails["openGroupRegCode"] = $decOpenGroupRegCode;
					
							$androidAppLink = Config::get('app_config.androidAppLink');
							$iosAppLink = Config::get('app_config.iosAppLink');
							$webAppLink = Config::get('app_config.webAppLink');

							$shareGroupInviteStr = $userOrEmpName." is inviting you to a group."."<br/><br/>";
							$shareGroupInviteStr .= "Group Name: ".$editGroup->name."<br/>";
							$shareGroupInviteStr .= "Registration Code: ".$decOpenGroupRegCode."<br/><br/>";

							$shareGroupInviteStr .= "If you are a HyLyt user, then join via Android/iOS App or Website."."<br/><br/>";
							$shareGroupInviteStr .= " - OR - <br/><br/>";
							$shareGroupInviteStr .= "If you are not a HyLyt user, you can join HyLyt using the following links."."<br/>";
							$shareGroupInviteStr .= "Android: ".$androidAppLink."<br/>";
							$shareGroupInviteStr .= "iOS: ".$iosAppLink."<br/>";
							$shareGroupInviteStr .= "Web: ".$webAppLink."";

							$grpDetails["shareGroupInviteStr"] = $shareGroupInviteStr;
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
    public function modifyQuotaDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $grpId = Input::get('grpId');
        $newQuotaMb = Input::get('quotaMb');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $grpId != "" && $newQuotaMb != "" && $newQuotaMb > 0)
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
				             				
        		$group = Group::byId($grpId)->first();        		
        		if(isset($group))
        		{
					$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
					
					if(isset($isUserGroupAdmin))
					{
						$isFolder = TRUE;
		            	$encOrgId = "";
		            	
		            	$newQuotaKb = $newQuotaMb*1024;
		            	$oldQuotaKb = $group->allocated_space_kb;
		            	$usedQuotaKb = $group->used_space_kb;
		            	
		                $depMgmtObj = New ContentDependencyManagementClass;
		                $depMgmtObj->withOrgKey($user, $encOrgId);
		                $availableKbs = $depMgmtObj->getAvailableUserQuota($isFolder);
		                
		                $quotaDiff = 0;
		                if($newQuotaKb > $oldQuotaKb)
		                	$quotaDiff = $newQuotaKb - $oldQuotaKb;
		                
		                if($availableKbs >= $quotaDiff && $usedQuotaKb <= $newQuotaKb)
	                	{
		                	$status = 1;		
							$group->allocated_space_kb = $newQuotaKb;
							$group->save();
							
		    				$depMgmtObj->recalculateUserQuota($isFolder);
		    				
		    				$isRename = 0;
		    				$sharedByUserEmail = $user->email;
							
		    				$groupMembers = GroupMember::ofGroup($grpId)->get();	                
			                foreach ($groupMembers as $member) 
			                {
								$memberUserId = $member->member_appuser_id;
								
								if($memberUserId != $userId)
	       							$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail);
	       						else
	       							$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, $loginToken);
			                }
			                
							$perAllocatedSpaceKb = $depMgmtObj->getAllocatedUserQuota(TRUE);
							$perAvailableSpaceKb = $depMgmtObj->getAvailableUserQuota(TRUE);
							$perUsedSpaceKb = $depMgmtObj->getUsedUserQuota(TRUE);
			                
			                $response["allocKb"] = $group->allocated_space_kb;
			                $response["usedKb"] = $group->used_space_kb;
							$response['perAllocKb'] = $perAllocatedSpaceKb;
							$response['perUsedKb'] = $perUsedSpaceKb;
						}
		                else
		                {
							$status = -1;
		           	 		$msg = Config::get('app_config_notif.err_insufficient_space');
						}
					}
        			else
					{
						$status = -1;
	            		$msg = 'User does not have the rights to perform this operation.';
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
     * Add Content.
     *
     * @return json array
     */
    public function saveContentDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $groupId = Input::get('groupId');
        $content = Input::get('content');		
        $contentTitle = (Input::get('content_title') !== NULL) ? Input::get('content_title') : substr($content, 0, 100);
        $oldContent = Input::get('oldContent');
        $contentType = Input::get('contentType');
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
        	
        $encOrgId = "";        	
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

                $groupId = sracDecryptNumberData($groupId, $userSession);
                $id = sracDecryptNumberData($id, $userSession);
                $removeAttachmentIdArr = sracDecryptNumberArrayData($removeAttachmentIdArr, $userSession);
                $modifiedAttachmentIdArr = sracDecryptNumberArrayData($modifiedAttachmentIdArr, $userSession);
                $tagsArr = sracDecryptNumberArrayData($tagsArr, $userSession);
				
		        $sharedByUserName = $user->fullname;  
            	$sharedByUserEmail = $user->email;          	
            	$groupMember = GroupMember::ofGroup($groupId)->ofUser($userId)->first();
            	
            	if(isset($groupMember))
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
	                    $groupContent = GroupContent::byId($id)->first();
	                    
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
                    $group = $depMgmtObj->getGroupObject($groupId);
	                
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
							$content = CommonFunctionClass::getSharedByAppendedString($content, $updateTimeStamp, $sharedByUserName, $sharedByUserEmail);
						}
						
						//Pankaj -- set content title
                
                        if (isset($groupContent) && !empty($groupContent->shared_by_email))
                        {
                            if(!empty($groupContent->content_title) && $groupContent->content_title != $contentTitle){
                                $separatorStr = Config::get('app_config.conversation_part_separator');
                        		$appendedStr = "";
                        		$appendedStr .= '<br>'.$separatorStr.'<br>';
                        		$appendedStr .= '-->'.ucwords($sharedByUserName).' ('.strtolower($sharedByUserEmail).')'.' ['.$updateTimeStamp.']';
                        		$appendedStr .= 'Title changed from '.$groupContent->content_title;
                                $content = preg_replace('/(<\/p>)/i', '</p>'.$appendedStr, $content, 1);
                            }
                        }

		                $response = $depMgmtObj->addEditGroupContent($id, $content, $contentTitle, $contentType, $groupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByUserEmail, $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive);
		                
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
				            $isRename = 0;
		            		$otherGroupMembers = GroupMember::ofGroup($groupId)->get();
		            		foreach($otherGroupMembers as $member) 
		                    {
		                        $memberUser = $member->memberAppuser;
		                        if(isset($memberUser))
		                        {
									$memberUserId = $memberUser->appuser_id;
	                        
									if($memberUserId != $userId)
									{
										$this->sendGroupEntryAddMessageToDevice($memberUserId, $groupContentId, $isAdd, $sharedByUserEmail);
						           		$this->sendGroupAddedMessageToDevice($memberUserId, $groupId, $isRename, $sharedByUserEmail);
									}
									else
									{
										$this->sendContentAddMessageToDevice($memberUserId, $loginToken, $isFolder, $groupContentId);
	       								$this->sendGroupAddedMessageToDevice($memberUserId, $groupId, $isRename, $sharedByUserEmail, $loginToken);
									}
								}								
							}							
					
							if(isset($sendAsReply) && $sendAsReply == 1)
							{
								// $depMgmtObj->sendContentAsReply($encUserId, "", $loginToken, $groupContentId, $isFolder);
							}                      
	                    }

                		$response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
			            $response['sharedByEmail'] = $sharedByUserEmail;
						$response['syncContent'] = utf8_encode($content);
						$response['syncContentTitle'] = utf8_encode($contentTitle);
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
                if($grpId > 0)
                {
                	$encOrgId = "";
                	
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgKey($user, $encOrgId);
	                	            		
	       			$group = $depMgmtObj->getGroupObject($grpId);
            		if(isset($group))
            		{
						$isFolder = FALSE;	
							
        				$status = 1; 
        				$grpName = $group->name;      									
						
        				$groupContent = $depMgmtObj->getContentObject($grpContId, $isFolder);
        				if(isset($groupContent))
        				{				
				        	$depMgmtObj->deleteContent($grpContId, $isFolder, $sharedByUserEmail);							
						}

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
    public function performRename()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $grpId = Input::get('grpId');
        $grpName = Input::get('grpName');
        $description = Input::get('description');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $grpId != "" && $grpName != "")
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

                if($grpId > 0)
                {
		            $depMgmtObj = New ContentDependencyManagementClass;
                	$depMgmtObj->withOrgKey($user, "");
                	
            		$group = $depMgmtObj->getGroupObject($grpId);
            		if(isset($group))
            		{
                		$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
            			if($isUserGroupAdmin)
            			{
            				$status = 1;
            				$sharedByUserEmail = $user->email;
			
							$oldGroupName = $group->name;
            				$isAddOp = 0;
							$isRename = 0;
							if($oldGroupName != $grpName)
							{
								$isAddOp = 1;
								$isRename = 1;
							}
	            								
							$group->name = $grpName;   
							$group->description = $description;   
							$group->save();
            				
							//Send FCM to all
							$groupMembers = GroupMember::ofGroup($grpId)->get();
							if(isset($groupMembers) && count($groupMembers) > 0)
							{
								foreach($groupMembers as $groupMember)
								{
									$memberUserId = $groupMember->member_appuser_id;
									
									if($memberUserId != $userId)
									{										
			       						$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, NULL, $isAddOp, $oldGroupName);
									}
									else
									{
										$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, $loginToken, $isAddOp, $oldGroupName);
									}
								}
							}
            			}
            			else
            			{
			                $status = -1;
			                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
     * rename group.
     *
     * @return json array
     */
    public function toggleOpenGroupStatus()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $grpId = Input::get('grpId');
        $isOpenGroup = Input::get('isOpenGroup');

		if(!isset($isOpenGroup) || $isOpenGroup != 1)
		{
			$isOpenGroup = 0;
		}

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
				 
                if($grpId > 0)
                {
		            $depMgmtObj = New ContentDependencyManagementClass;
                	$depMgmtObj->withOrgKey($user, "");
                	
            		$group = $depMgmtObj->getGroupObject($grpId);
            		if(isset($group))
            		{
                		$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
            			if($isUserGroupAdmin)
            			{
            				$status = 1;	
				  

							$existingOpenGroupRegCode = $group->open_group_reg_code;

							$generateOpenGroupRegCode = FALSE; 
							if($isOpenGroup == 1 && $group->is_open_group == 0)
							{
								$generateOpenGroupRegCode = TRUE;
							}

				            $openGroupRegCode = NULL;
				            if($isOpenGroup == 1)
				            {
				            	if($generateOpenGroupRegCode)
					            {
					            	$existingGroupRegCodeArr = [];
					            	$allOpenGroups = Group::exceptId($grpId)->isOpenGroup()->get();
					            	foreach ($allOpenGroups as $openGroup) {
					            		if(isset($openGroup->open_group_reg_code) && $openGroup->open_group_reg_code != "")
					            		{
						            		$decRegCode = Crypt::decrypt($openGroup->open_group_reg_code);
						            		array_push($existingGroupRegCodeArr, $decRegCode);			            			
					            		}
					            	}

					            	$genGroupRegCode = CommonFunctionClass::generateOpenGroupRegistrationCodeString($existingGroupRegCodeArr);

					            	$openGroupRegCode = Crypt::encrypt($genGroupRegCode);
					            }
					            elseif(isset($existingOpenGroupRegCode))
					            {
					            	$openGroupRegCode = $existingOpenGroupRegCode;
					            }
		            		}	
	            								
							$group->is_open_group = $isOpenGroup;
							$group->open_group_reg_code = $openGroupRegCode;
							$group->save();
            			}
            			else
            			{
			                $status = -1;
			                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
    
    public function loadJoinOpenGroupModal()
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
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
              
                $viewDetails = array();
                $viewDetails['orgKey'] = $encOrgId;
                $viewDetails['userId'] = $encUserId;
                $viewDetails['loginToken'] = $loginToken;
                
                $_viewToRender = View::make('orggroup.partialview._joinOpenGroupModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();
                
                $response['view'] = $_viewToRender;
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
    public function joinOpenGroupAsMember()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $inpOpenGroupRegCode = Input::get('openGroupRegCode');

        $response = array();

        if($encUserId != "" && $inpOpenGroupRegCode != "")
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

            	$mappedOpenGroup = NULL;
            	$allOpenGroups = Group::isOpenGroup()->get();
            	foreach ($allOpenGroups as $openGroup) {
            		if(isset($openGroup->open_group_reg_code) && $openGroup->open_group_reg_code != "")
            		{
	            		$decRegCode = Crypt::decrypt($openGroup->open_group_reg_code);

	            		if($decRegCode == $inpOpenGroupRegCode)
	            		{
	            			$mappedOpenGroup = $openGroup;
	            			break;
	            		}        			
            		}
            	}

            	if(isset($mappedOpenGroup))
            	{
		            $status = 1;
	                $msg = 'Group joined';   
            		
            		$sharedByUserEmail = $user->email;

            		$grpId = $mappedOpenGroup->group_id;
	    			$grpName = $mappedOpenGroup->name;

					$memberUserId = $userId;

					$existingGroupMember = GroupMember::ofGroup($grpId)->ofUser($memberUserId)->first();

					if(!isset($existingGroupMember))
					{
						//Manage is admin thingy
            			$isAdmin = 0;
            			$isGroupSelfJoined = 1;
            			
            			$defGroupIsFavorited = Config::get('app_config.default_group_is_favorited');
            			$defGroupIsLocked = Config::get('app_config.default_group_is_locked');
            			
						//Insert Member
						$groupMember = New GroupMember;
						$groupMember->group_id = $grpId;
						$groupMember->member_appuser_id = $memberUserId;
						$groupMember->is_admin = $isAdmin;
						$groupMember->is_locked = $defGroupIsLocked;
						$groupMember->is_favorited = $defGroupIsFavorited;
						$groupMember->is_self_joined = $isGroupSelfJoined;
						$groupMember->save();

						
						$isRename = 0;		
						$isAddOp = 1;
						$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, NULL, $isAddOp);				
						MailClass::sendUserAddedToGroupMail($memberUserId, $mappedOpenGroup, $user);
					}
	    			else
	    			{
		                $status = -1;
		                $msg = 'You are already a group member';    	    				
	    			}
            	}
    			else
    			{
	                $status = -1;
	                $msg = 'No such open group found';    	    				
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
     * rename group.
     *
     * @return json array
     */
    public function uploadGroupPhoto()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $grpId = Input::get('grpId');
        $loginToken = Input::get('loginToken');
		$groupPhotoFile = Input::file('photo_file');

        $response = array();

        if($encUserId != "" && $grpId != "" && isset($groupPhotoFile))
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
				 
                if($grpId > 0)
                {
		            $depMgmtObj = New ContentDependencyManagementClass;
                	$depMgmtObj->withOrgKey($user, "");
                	
            		$group = $depMgmtObj->getGroupObject($grpId);
            		if(isset($group))
            		{	
                		$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
            			if($isUserGroupAdmin)
            			{
            				$status = 1;
            				$sharedByUserEmail = $user->email;
			
							$fileName = "";
				        	if(isset($groupPhotoFile) && File::exists($groupPhotoFile) && $groupPhotoFile->isValid()) 
				            {
				                $fileUpload = new FileUploadClass;
				                $fileName = $fileUpload->uploadOrganizationGroupPhotoImage($groupPhotoFile, 0);
				            }   
	            				
            				$isAddOp = 0;
							$isRename = 0;
											
							$group->img_server_filename = $fileName;  
							$group->save();
            				
							//Send FCM to all
							$groupMembers = GroupMember::ofGroup($grpId)->get();
							if(isset($groupMembers) && count($groupMembers) > 0)
							{
								foreach($groupMembers as $groupMember)
								{
									$memberUserId = $groupMember->member_appuser_id;
									
									if($memberUserId != $userId)
									{										
			       						$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, NULL);
									}
									else
									{
										$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, $loginToken);
									}
								}
							}
					
							$orgId = 0;
							$photoFilename = $group->img_server_filename;
							$groupPhotoUrl = "";
							$groupPhotoThumbUrl = "";
							if(isset($photoFilename) && $photoFilename != "")
							{
								$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
								$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
							}
							
							$response['photoUrl'] = $groupPhotoUrl;
							$response['photoThumbUrl'] = $groupPhotoThumbUrl;
            			}
            			else
            			{
			                $status = -1;
			                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
     * remove group user.
     *
     * @return json array
     */
    public function removeUser()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $grpId = Input::get('grpId');
        $memberId = Input::get('memberId');
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
            
            if(isset($user) && $memberId != $userId)
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
                $memberId = sracDecryptNumberData($memberId, $userSession);
				  
                if($grpId > 0)
                {
                	$sharedByUserEmail = $user->email;
                	
            		$group = Group::findOrFail($grpId);
            		if(isset($group))
            		{	
            			$grpName = $group->name;
            			$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
            			if(isset($isUserGroupAdmin))
            			{
            				$status = 1;
            				
            				$groupUser = GroupMember::ofGroup($grpId)->OfUser($memberId)->first();
            				if(isset($groupUser))
            				{
		        				$groupContents = GroupContent::ofGroup($grpId)->get();
		        				if(isset($groupContents) && count($groupContents) > 0)
		        				{
									foreach($groupContents as $groupContent)
									{
										$groupContentId = $groupContent->group_content_id;
			                    		GroupContentTag::ofGroupContentAndUser($groupContentId, $memberId)->delete();								
									}
								}
								
		           				$this->sendGroupDeletedMessageToDevice($memberId, $grpId, $grpName, $sharedByUserEmail);		       						
								MailClass::sendUserRemovedFromGroupMail($memberId, $group, $user);									
            					
								$groupUser->delete();
							}
		           			
            			}
            			else
            			{
			                $status = -1;
			                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
     * remove group user.
     *
     * @return json array
     */
    public function exitGroup()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
				   
                if($grpId > 0)
                {
            		$group = Group::findOrFail($grpId);
            		if(isset($group))
            		{	
        				$status = 1;
        				
        				$groupUser = GroupMember::ofGroup($grpId)->OfUser($userId)->first();
        				if(isset($groupUser))
        				{
							$isUserAdmin = $groupUser->is_admin;
							
							$groupUser->delete();
							
							$groupContents = GroupContent::ofGroup($grpId)->get();
	        				if(isset($groupContents) && count($groupContents) > 0)
	        				{
								foreach($groupContents as $groupContent)
								{
									$groupContentId = $groupContent->group_content_id;
		                    		$contentTags = GroupContentTag::ofGroupContentAndUser($groupContentId, $userId)->delete();								
								}
							}
							
							if($isUserAdmin == 1)
							{
								$firstGroupUser = GroupMember::ofGroup($grpId)->first();
								if(isset($firstGroupUser))
		        				{
									$firstGroupUser->is_admin = 1;
									$firstGroupUser->save();
								}
							}								
							   	    
							$groupMembers = GroupMember::ofGroup($grpId)->get();						
							foreach($groupMembers as $groupMember)
							{
								$memberUserId = $groupMember->member_appuser_id;
								MailClass::sendUserHasExitGroupMail($memberUserId, $group, $user);												
							}	
							
							$grpName = $group->group_name;
							$sharedByUserEmail = $user->email;
							
							$this->sendGroupDeletedMessageToDevice($userId, $grpId, $grpName, $sharedByUserEmail, $loginToken);
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
     * remove group user.
     *
     * @return json array
     */
    public function deleteGroup()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
				  
                if($grpId > 0)
                {
                	$sharedByUserEmail = $user->email;
                	
            		$group = Group::byId($grpId)->first();
            		if(isset($group))
            		{	
            			$grpName = $group->name;
        				$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
            			if(isset($isUserGroupAdmin))
            			{
            				$status = 1;
							
	        				$groupContents = GroupContent::ofGroup($grpId)->get();
	        				if(isset($groupContents) && count($groupContents) > 0)
	        				{
								foreach($groupContents as $groupContent)
								{
									$groupContentId = $groupContent->group_content_id;
		                    		GroupContentTag::ofGroupContent($groupContentId)->delete();
		                    		GroupContentAttachment::ofGroupContent($groupContentId)->delete();
		                    		$groupContent->delete();									
								}
							}
            				
							//Send FCM to all
            				$groupMembers = GroupMember::ofGroup($grpId)->get();
            				if(isset($groupMembers) && count($groupMembers) > 0)
	        				{
								foreach($groupMembers as $groupMember)
								{
									$memberUserId = $groupMember->member_appuser_id;
									
									if($memberUserId != $userId)
									{
		           						$this->sendGroupDeletedMessageToDevice($memberUserId, $grpId, $grpName, $sharedByUserEmail);	
										MailClass::sendGroupDeletedMail($memberUserId, $group, $user);										
									}
									else
									{										
										$this->sendGroupDeletedMessageToDevice($memberUserId, $grpId, $grpName, $sharedByUserEmail, $loginToken);
									}
									$groupMember->delete();
								}
							}	
							$group->delete();
                    
			                $depMgmtObj = New ContentDependencyManagementClass;
			                $depMgmtObj->withOrgKey($user, "");
			                $depMgmtObj->recalculateUserQuota(TRUE);	
			                
							$perAllocatedSpaceKb = $depMgmtObj->getAllocatedUserQuota(TRUE);
							$perAvailableSpaceKb = $depMgmtObj->getAvailableUserQuota(TRUE);
							$perUsedSpaceKb = $depMgmtObj->getUsedUserQuota(TRUE);
							
							$response['perAllocKb'] = $perAllocatedSpaceKb;
							$response['perUsedKb'] = $perUsedSpaceKb;		
            			}
            			else
            			{
			                $status = -1;
			                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
     * add member to group.
     *
     * @return json array
     */
    public function addGroupMember()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        
        $grpId = Input::get('grpId');
        $memberContactIdArr = Input::get('memberArr');
        $loginToken = Input::get('loginToken');
        
        $memberContactIdArr = json_decode($memberContactIdArr);

        $response = array();

        if($encUserId != "" && count($memberContactIdArr) > 0 && $grpId != "")
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
                $memberContactIdArr = sracDecryptNumberArrayData($memberContactIdArr, $userSession);
				
        		$group = Group::findOrFail($grpId);            		
	    		if(isset($group))
	    		{	
	    			$grpName = $group->name;
	    			$isAdd = 1;
	    			$sharedByUserEmail = $user->email;
	    			
					$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
	    			if(isset($isUserGroupAdmin))
	    			{
	    				$status = 1;
						$memberUserIdArr = array();
						foreach ($memberContactIdArr as $memberContactId) 
		                {
		            		$memberUserDetails = AppuserContact::ofUserContact($memberContactId)->first();
		            		
		            		if(isset($memberUserDetails))
		            		{
			            		$selContactEmail = $memberUserDetails->email;
			            		$selContactContactNo = $memberUserDetails->contact_no;

		            			$userRegistered = Appuser::forRegisteredAppUserByEmailOrContactNo($selContactEmail, $selContactContactNo)->active()->first();
			            		if(isset($userRegistered))
			            		{
									$registeredAppuserId = $userRegistered->appuser_id;
									$memberUserDetails->regd_appuser_id = $registeredAppuserId;
									$memberUserDetails->save();

									array_push($memberUserIdArr, $registeredAppuserId);	
			            		}
							}
		                }
		                
		                $existingMemberUserIdArr = array();
	            		$groupMembers = GroupMember::ofGroup($grpId)->get();

						foreach($groupMembers as $gm)
						{
							$memUserId = $gm->member_appuser_id;
							array_push($existingMemberUserIdArr, $memUserId);
						}
						
						foreach ($memberUserIdArr as $memberUserId) 
		                {
		                	if(!in_array($memberUserId, $existingMemberUserIdArr))	
		                	{	
		            			//Manage is admin thingy
		            			$isAdmin = 0;
		            			$isGroupSelfJoined = 0;
		            			
		            			$defGroupIsFavorited = Config::get('app_config.default_group_is_favorited');
		            			$defGroupIsLocked = Config::get('app_config.default_group_is_locked');
		            			
								//Insert Member
								$groupMember = New GroupMember;
								$groupMember->group_id = $grpId;
								$groupMember->member_appuser_id = $memberUserId;
								$groupMember->is_admin = $isAdmin;
								$groupMember->is_locked = $defGroupIsLocked;
								$groupMember->is_favorited = $defGroupIsFavorited;
								$groupMember->is_self_joined = $isGroupSelfJoined;
								$groupMember->save();						
								
								//Send FCM to all	
    							$isRename = 0;								
								if($memberUserId != $userId)
								{
									$isAddOp = 1;
	       							$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, NULL, $isAddOp);				
									MailClass::sendUserAddedToGroupMail($memberUserId, $group, $user);
								}
								else
								{
									$isAddOp = 0;
	       							$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, $loginToken, $isAddOp);
								}										       		
							}
		                }
	    			}
	    			else
	    			{
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');    
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
				 
                $grpId = sracDecryptNumberData($grpId, $userSession);
                $grpContentId = sracDecryptNumberData($grpContentId, $userSession);
				
		        $orgId = 0;
        		$group = Group::byId($grpId)->first();            		
	    		if(isset($group))
	    		{	
					$isUserGroupMember = GroupMember::isUserGroupMember($grpId, $userId)->first();
	    			if(isset($isUserGroupMember))
	    			{
	    				$status = 1;
	    				$groupContent = GroupContent::byGroupContentId($grpContentId)->first();
						if(isset($groupContent))
	    				{
	    					$grpContentDetails = array();
	                    
		                    
							$performDownload = 1;
		                    
		                    
							$attachmentsArr = array();
		                	$groupContentAttachments = GroupContentAttachment::ofGroupContent($grpContentId)->get();
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
		                   
		                   	$contentText = "";
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
									
								}
							}
							
		                    $grpContentDetails['syncId'] = sracEncryptNumberData($grpContentId, $userSession);
		                    $grpContentDetails['content'] = $decContent;
		                    if($groupContent->content_title === '' || $groupContent->content_title ===null){
								$grpContentDetails['content_title'] = 'No Title';
							}else{
								$grpContentDetails['content_title'] = $groupContent->content_title;
							}
		                  //  $grpContentDetails['content_title'] = $groupContent->content_title;
	                    	$grpContentDetails['encContent'] = $encDecContent;
		                    $grpContentDetails['contentType'] = $groupContent->content_type_id;
		                    $grpContentDetails['isMarked'] = $groupContent->is_marked;
		                    $grpContentDetails['createDate'] = $groupContent->create_date;
		                    $grpContentDetails['createTime'] = $groupContent->create_time;
		                    $grpContentDetails['fromDate'] = $groupContent->from_date;
		                    $grpContentDetails['fromTime'] = $groupContent->from_time;
		                    $grpContentDetails['toDate'] = $groupContent->to_date;
		                    $grpContentDetails['toTime'] = $groupContent->to_time;
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
		                	$msg = Config::get('app_config_notif.inf_no_content_found').'1'; 

        					$tempEncGrpContentId = Input::get('contentId');
        					$tempEncGrpId = Input::get('grpId');

		                	Log::info('================ no content happened RET start ================');
		                	Log::info('grpContentId : '.$grpContentId);
		                	Log::info('tempEncGrpContentId : '.$tempEncGrpContentId);
		                	Log::info('grpId : '.$grpId);
		                	Log::info('tempEncGrpId : '.$tempEncGrpId);
		                	Log::info('================ no content happened RET end ================');
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
						
						$orgId = 0;
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
	                   	
	                   	$contentText = "";
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
								
							}
						}
						
	                    $grpContentDetails['syncId'] = $grpContent->group_content_id;
	                    $grpContentDetails['content'] = $decContent;
	                    if($grpContent->content_title === '' || $grpContent->content_title ===null){
							$grpContentDetails['content_title'] = 'No Title';
						}else{
							$grpContentDetails['content_title'] = $grpContent->content_title;
						}
	                   // $grpContentDetails['content_title'] = $grpContent->content_title;
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
     * save app user broadcast details.
     *
     * @return json array
     */
    public function invertFavoritedStatus()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');        
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
                $depMgmtObj->withOrgKey($user, "");		  
                $depMgmtObj->setGroupFavoritedStatus($grpId, $isFavorited);	
                
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
     * Sync User Data.
     *
     * @return json array
     */
    public function performGroupDataRestoreWithoutDelete()
    {
        set_time_limit(0);

        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $groupId = Input::get('grpId');
        $groupName = Input::get('grpName');
        $groupDescription = Input::get('grpDescription');
		$groupPhotoFile = Input::file('grpPhotoFile');
        $grpIsFavorited = Input::get('grpIsFavorited');
        $grpIsTwoWay = Input::get('grpIsTwoWay');
        $grpAllocatedSpaceKb = Input::get('grpAllocatedSpaceKb');
        $grpUsedSpaceKb = Input::get('grpUsedSpaceKb');
        $grpIsLocked = Input::get('grpIsLocked');
        
        $memberEmailArr = Input::get('memberEmailArr');
        // $tagArr = Input::get('tagArr');
        $contentArr = Input::get('contentArr');

        $groupId = isset($groupId) && $groupId != "" ? $groupId * 1 : 0;
        $grpIsFavorited = isset($grpIsFavorited) && $grpIsFavorited != "" ? $grpIsFavorited * 1 : 0;
        $grpIsTwoWay = isset($grpIsTwoWay) && $grpIsTwoWay != "" ? $grpIsTwoWay * 1 : 0;
        $grpAllocatedSpaceKb = isset($grpAllocatedSpaceKb) && $grpAllocatedSpaceKb != "" ? $grpAllocatedSpaceKb * 1 : 0;
        $grpUsedSpaceKb = isset($grpUsedSpaceKb) && $grpUsedSpaceKb != "" ? $grpUsedSpaceKb * 1 : 0;
        $grpIsLocked = 0; //isset($grpIsLocked) && $grpIsLocked != "" ? $grpIsLocked * 1 : 0;

        $memberEmailArr = json_decode($memberEmailArr);
        // $tagArr = json_decode($tagArr);
        $contentArr = json_decode($contentArr);

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

                $isFolder = FALSE;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $restoredByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $group = $depMgmtObj->getGroupObject($groupId);

                if(isset($group) && $orgId == 0)
                {
                	$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($groupId);

                	if($isUserGroupAdmin)
                	{
                		$status = 1;
	        			$responseLogArr = array();

	                	$fileName = "";
			        	if(isset($groupPhotoFile) && File::exists($groupPhotoFile) && $groupPhotoFile->isValid()) 
			            {
			                $fileUpload = new FileUploadClass;
			                $fileName = $fileUpload->uploadOrganizationGroupPhotoImage($groupPhotoFile, $orgId);
			            } 

						$group->created_by = $userId;
						$group->allocated_space_kb = $grpAllocatedSpaceKb;
						$group->used_space_kb = $grpUsedSpaceKb;
						$group->is_two_way = $grpIsTwoWay;
									
						$group->img_server_filename = $fileName;
						$group->name = $groupName;   
						$group->description = $groupDescription;
						$group->save();

						$responseLogArr['1_group_details'] = $group;

		                // remove current group members
		                $groupMemberDelLogArr = array();
	            		$isPreRestore = TRUE;
						$existingGroupMembers = $depMgmtObj->getGroupMembers($groupId);
						foreach ($existingGroupMembers as $grpMember) 
		                {
		                	$memberId = $grpMember->member_id;
		                	$memberAppuserId = $grpMember->member_appuser_id;

		                	// Silently
		                	if($orgId == 0)
		                	{
	           					$sendStatus = $this->sendGroupRestorationMessageToDevice($isPreRestore, $memberAppuserId, $groupId, $groupName, $restoredByUserEmail);

	           					$grpMember->isPushSent = "true_".$sendStatus;
		                	}
		                	else
		                	{

		                	}

		                	array_push($groupMemberDelLogArr, $grpMember);
	           				$grpMember->delete();	
		                }

						$responseLogArr['2_group_remove_members'] = $groupMemberDelLogArr; 

		                // remove current group contents
		                $groupContentDelLogArr = array();
						$existingGroupContents = $depMgmtObj->getAllContents($isFolder, $groupId);
		               	foreach ($existingGroupContents as $content) 
		                {
	                    	$contentId = $content->group_content_id;
	                        $depMgmtObj->deleteContent($contentId, $isFolder);
		                	array_push($groupContentDelLogArr, $contentId);
		                }
						$responseLogArr['3_group_remove_contents'] = $groupContentDelLogArr; 

		                $usedTagNameArr = array();
		                $contentTagMapping = array();

		                // restore group contents
		                $groupContentAddLogArr = array();
		                $finalContentResponse = array(); 
		                if(isset($contentArr) && count($contentArr)>0)
		                {
		                    for($i=0; $i<count($contentArr); $i++)
		                    {
		                        $entryObj = $contentArr[$i];

		                        $serId = 0;
		                        $content = $entryObj->content;
		                        $contentTitle = $entryObj->content_title;
		                        $contentType = $entryObj->contentType;
		                        $isMarked = $entryObj->isMarked;
		                        $createTimeStamp = $entryObj->createTimeStamp;
		                        $updateTimeStamp = $entryObj->updateTimeStamp;
		                        $fromTimeStamp = $entryObj->fromTimeStamp;
		                        $toTimeStamp = $entryObj->toTimeStamp;
		                        $colorCode = $entryObj->colorCode;
		                    	$contentIsLocked = $entryObj->isLocked;
		                    	$grpContentSharedByEmail = $entryObj->senderEmail;
		                    	$contentIsShareEnabled = $entryObj->isShareEnabled;
        
						        if(!isset($contentIsShareEnabled)) {
						            $contentIsShareEnabled = Config::get('app_config.default_content_share_status');
						        }

		                    	$tagsArr = array();
		                    	
			                	$remindBeforeMillis = 0;
			                	if(isset($entryObj->remindBeforeMillis))
			                		$remindBeforeMillis = $entryObj->remindBeforeMillis;
			                	
			                	$repeatDuration = "";
			                	if(isset($entryObj->repeatDuration))
			                		$repeatDuration = $entryObj->repeatDuration;
						                    	
			                	$contentIsCompleted = 0;
			                	if(isset($entryObj->isCompleted))
			                		$contentIsCompleted = $entryObj->isCompleted;
		                    	
			                	$contentIsSnoozed = 0;
			                	if(isset($entryObj->isSnoozed))
			                		$contentIsSnoozed = $entryObj->isSnoozed;
		                    	
			                	$contentReminderTimestamp = NULL;
			                	if(isset($entryObj->reminderTimestamp))
			                		$contentReminderTimestamp = $entryObj->reminderTimestamp;

		                        if(!isset($isMarked))
		                            $isMarked = 0;

		                		array_push($groupContentAddLogArr, $content);
				                
				                $id = 0;
				            	$content = urldecode($content);
				            	$contentResponse = $depMgmtObj->addEditGroupContent($serId, $content,$contentTitle, $contentType, $groupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $contentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $contentIsCompleted, $contentIsSnoozed, $contentReminderTimestamp, array(), $grpContentSharedByEmail);
		                        $id = $contentResponse["syncId"];

		                        if($id > 0)
		                        {
			                    	$tagNameArr = array();
			                        $tagList = $entryObj->tagList;
							        if(is_array($tagList))
							        	$tagNameArr = $tagList;
									else
							        	$tagNameArr = json_decode($tagList);

							        if(count($tagNameArr) > 0)
							        {
							        	$usedTagNameArr = array_merge($usedTagNameArr, $tagNameArr);
							        	$contentTagMapping[$id] = $tagNameArr;
							        }
		                        }

		                        $finalContentResponse[$i]["syncId"] = $id;
		                    }
		                }
						$responseLogArr['4_group_add_contents'] = $groupContentAddLogArr; 

						$usedTagNameArr = array_unique($usedTagNameArr);

		                // restore group members
		                $groupMemberAddLogArr = array();
		                $groupMemberEmailLogArr = array();
	            		$isPreRestore = FALSE;
		             	if($orgId > 0)
		             	{
			               	
		             	}
		             	else
		             	{
			               	foreach ($memberEmailArr as $memberEmail) 
			                {
		                		array_push($groupMemberEmailLogArr, $memberEmail);

		            			$memberUser = Appuser::active()->where('email','=',$memberEmail)->first();
		            			if(isset($memberUser))
		            			{
		            				$memberUserId = $memberUser->appuser_id;

		            				$appuserContact = AppuserContact::ofUser($userId)->ofRegisteredUser($memberUserId)->first();
		            				$appuserContactId = 0;
		            				if(isset($appuserContact))
		            				{
		            					$appuserContactId = $appuserContact->appuser_contact_id;
		            				}

		            				$isMemberAdmin = 0;
		            				if($memberUserId == $userId)
		            				{
		            					$isMemberAdmin = 1;
		            				}

					                $consDepMgmtObj = New ContentDependencyManagementClass;
					                $consDepMgmtObj->withOrgKey($memberUser, $encOrgId);

					                $userTagNameIdMapArr = array();
					                if(isset($usedTagNameArr) && count($usedTagNameArr)>0)
					                {
					                	foreach ($usedTagNameArr as $i => $usedTagName)
					                    {
					                        $tagExistsFlag = FALSE;
					                        $tagExists = $consDepMgmtObj->getTagObjectByName($usedTagName);

					                        $tagId = 0;
					                        if(isset($tagExists))
					                        {
					                        	$tagExistsFlag = TRUE;
												if($orgId > 0)
								    			{
													$tagId = $tagExists->employee_tag_id;  				
												}
												else
												{
													$tagId = $tagExists->appuser_tag_id;
												}
					                        }
					                        else
					                        {
								                $tagResponse = $consDepMgmtObj->addEditTag(0, $usedTagName);
			                        			$tagId = $tagResponse["syncId"];
					                        }					                        
											
		                        			if($tagId > 0)
		                        			{
							                	$userTagNameIdMapArr[$usedTagName] = $tagId; // ." : exists : ".$tagExistsFlag;
		                        			}
					                    }
					                }

					                $groupMemberData = array();
					                $groupMemberData['group_id'] = $groupId;
					                $groupMemberData['member_appuser_id'] = $memberUserId;
					                $groupMemberData['appuser_contact_id'] = $appuserContactId;
					                $groupMemberData['is_admin'] = $isMemberAdmin;
					                $groupMemberData['is_favorited'] = $grpIsFavorited;
					                $groupMemberData['is_locked'] = $grpIsLocked;

									$groupMember = NULL;
					                $groupMember = GroupMember::create($groupMemberData);

					                if(isset($groupMember) && count($contentTagMapping) > 0)
					                {
					                	foreach ($contentTagMapping as $contentId => $contentTagNameArr) {
					                		$contentTagIdArr = array();
					                		foreach ($contentTagNameArr as $tagName) {
					                			if(isset($userTagNameIdMapArr[$tagName]))
					                			{
					                				$contentTagId = $userTagNameIdMapArr[$tagName];
					                				array_push($contentTagIdArr, $contentTagId);
					                			}
					                		}
					                		if(count($contentTagIdArr) > 0)
					                		{
					                			$consDepMgmtObj->addEditGroupContentTags($contentId, $memberUserId, $contentTagIdArr);
					                		}
					                	}
					                }

					                // add group content tags
	           						$sendStatus = $this->sendGroupRestorationMessageToDevice($isPreRestore, $memberUserId, $groupId, $groupName, $restoredByUserEmail);	

	           						$groupMemberData['isPushSent'] = "true_".$sendStatus;		

		                			array_push($groupMemberAddLogArr, $groupMemberData);						
		            			}
			                }
		             	}

						$responseLogArr['5_group_member_emails'] = $groupMemberEmailLogArr;
						$responseLogArr['6_group_add_members'] = $groupMemberAddLogArr;

		                $response["contentResponse"] = $finalContentResponse;
		                $response['responseLogArr'] = $responseLogArr;
		                $response['usedTagNameArr'] = $usedTagNameArr;
		                $response['contentTagMapping'] = $contentTagMapping;
		                $response['memberEmailArr'] = $memberEmailArr;
                	}
		            else
		            {  
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');        
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


    /**
     * Sync User Data.
     *
     * @return json array
     */
    public function performGroupDataRestore()
    {
        set_time_limit(0);

         Log::info('Inside performGroupDataRestore');

        $msg = "";
        $status = 0;
        $response = array();

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $backupGroupId = Input::get('backupGrpId');
        $baseGroupId = Input::get('grpId');
        $groupName = Input::get('grpName');
        $groupDescription = Input::get('grpDescription');
		$groupPhotoFile = Input::file('grpPhotoFile');
        $grpIsFavorited = Input::get('grpIsFavorited');
        $grpIsLocked = Input::get('grpIsLocked');
        $grpIsTwoWay = Input::get('grpIsTwoWay');
        $grpAllocatedSpaceKb = Input::get('grpAllocatedSpaceKb');
        $grpUsedSpaceKb = Input::get('grpUsedSpaceKb');
        
        $memberEmailArr = Input::get('memberEmailArr');
        $contentArr = Input::get('contentArr');

        $baseGroupId = isset($baseGroupId) && $baseGroupId != "" ? $baseGroupId : "";
        $backupGroupId = isset($backupGroupId) && $backupGroupId != "" ? $backupGroupId : "";

        $grpIsFavorited = isset($grpIsFavorited) && $grpIsFavorited != "" ? $grpIsFavorited * 1 : 0;
        $grpIsTwoWay = isset($grpIsTwoWay) && $grpIsTwoWay != "" ? $grpIsTwoWay * 1 : 0;
        $grpAllocatedSpaceKb = isset($grpAllocatedSpaceKb) && $grpAllocatedSpaceKb != "" ? $grpAllocatedSpaceKb * 1 : 0;
        $grpUsedSpaceKb = isset($grpUsedSpaceKb) && $grpUsedSpaceKb != "" ? $grpUsedSpaceKb * 1 : 0;
        $grpIsLocked = isset($grpIsLocked) && $grpIsLocked != "" ? $grpIsLocked * 1 : 0;

		// $response['reqContentsStr'] = $contentArr;

        $memberEmailArr = json_decode($memberEmailArr);
        $contentArr = json_decode($contentArr);

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
				
                $baseGroupId = sracDecryptNumberData($baseGroupId, $userSession);
                $backupGroupId = sracDecryptNumberData($backupGroupId, $userSession);

                $isFolder = FALSE;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $restoredByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $baseGroup = $depMgmtObj->getGroupObject($baseGroupId);
				Log::info('baseGroup');
				Log::info($baseGroup);
				Log::info('backupGroupId: '.$backupGroupId.' : orgId : '.$orgId);


                if(isset($baseGroup) && $orgId == 0 && $backupGroupId > 0)
                {
                	$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($baseGroupId);

                	if($isUserGroupAdmin)
                	{
                		$baseGroupName = $baseGroup->name;
                		$existingRestoreIdLogStr = $baseGroup->restore_id_log_str;
                		$isValidBackup = $this->getIsValidBackupForRestore($baseGroupId, $existingRestoreIdLogStr, $backupGroupId);

                		if($isValidBackup)
                		{
                			try
                			{
	     						Log::info('1_add_group BEGINS: ');
	                			$status = 1;
	                			$restoredGroupDetails = NULL;
	                			$finalContentResponse = array();
			        			$responseLogArr = array();

			                	$fileName = "";
					        	if(isset($groupPhotoFile) && File::exists($groupPhotoFile) && $groupPhotoFile->isValid()) 
					            {
					                $fileUpload = new FileUploadClass;
					                $fileName = $fileUpload->uploadOrganizationGroupPhotoImage($groupPhotoFile, $orgId);
					            }

	    						$existingRestoreIdLogArr = $this->getGroupRestoreIdArr($existingRestoreIdLogStr);
	    						array_push($existingRestoreIdLogArr, $baseGroupId);

					            $updRestoreIdLogStr = $this->getGroupRestoreIdStr($existingRestoreIdLogArr);

					            $insGroupDetails = array();
								$insGroupDetails['name'] = $groupName;   
								$insGroupDetails['description'] = $groupDescription;
								$insGroupDetails['img_server_filename'] = $fileName;
								$insGroupDetails['allocated_space_kb'] = $grpAllocatedSpaceKb;
								$insGroupDetails['used_space_kb'] = 0; // $grpUsedSpaceKb;
								$insGroupDetails['is_two_way'] = $grpIsTwoWay;
								$insGroupDetails['created_by'] = $userId;
								$insGroupDetails['restore_id_log_str'] = $updRestoreIdLogStr;

								$groupModelObj = New Group;
								$groupTableName = $groupModelObj->table;

								$restoredGroupId = DB::table($groupTableName)->insertGetId($insGroupDetails);

								if($restoredGroupId > 0)
								{
		                			$restoredGroup = $depMgmtObj->getGroupObject($restoredGroupId);
									$responseLogArr['1_add_group'] = $restoredGroup; 
	         						Log::info('1_add_group ENDS: '.$restoredGroupId);

									$restoredGroupDetails = array();
									$restoredGroupDetails['id'] = sracEncryptNumberData($restoredGroupId, $userSession);
									$restoredGroupDetails['name'] = $groupName;

				            		$isPreRestore = TRUE;
									$existingGroupMembers = $depMgmtObj->getGroupMembers($baseGroupId);
									foreach ($existingGroupMembers as $grpMember) 
					                {
					                	$memberId = $grpMember->member_id;
					                	$memberAppuserId = $grpMember->member_appuser_id;

					                	// Silently
					                	if($orgId == 0)
					                	{
				           					$sendStatus = $this->sendGroupRestorationMessageToDevice($isPreRestore, $memberAppuserId, $baseGroupId, $groupName, $restoredByUserEmail, $loginToken);

				           					$grpMember->isPushSent = "true_".$sendStatus;
					                	}
					                	else
					                	{

					                	}
					                }

									$usedTagNameArr = array();
					                $contentTagMapping = array();
	         						Log::info('3_group_add_contents BEGINS: ');

					                // restore group contents
					                $groupContentAddLogArr = array();
					                if(isset($contentArr) && count($contentArr)>0)
					                {
					                    for($i=0; $i<count($contentArr); $i++)
					                    {
					                        $entryObj = $contentArr[$i];

								            $id = 0;
					                        if(isset($entryObj) && isset($entryObj->content))
					                        {
					                        	$serId = 0;
						                        $content = $entryObj->content;
												$contentTitle = $entryObj->content_title;
	         									Log::info('5_group_add_contents : '.$i.' : '.$content.' : Will insert');
						                        $contentType = $entryObj->contentType;
						                        $isMarked = $entryObj->isMarked;
						                        $createTimeStamp = $entryObj->createTimeStamp;
						                        $updateTimeStamp = $entryObj->updateTimeStamp;
						                        $fromTimeStamp = $entryObj->fromTimeStamp;
						                        $toTimeStamp = $entryObj->toTimeStamp;
						                        $colorCode = $entryObj->colorCode;
						                    	$contentIsLocked = $entryObj->isLocked;
						                    	$grpContentSharedByEmail = $entryObj->senderEmail;
						                    	$contentIsShareEnabled = $entryObj->isShareEnabled;

						                    	$tagsArr = array();
						                    	
							                	$remindBeforeMillis = 0;
							                	if(isset($entryObj->remindBeforeMillis))
							                		$remindBeforeMillis = $entryObj->remindBeforeMillis;
							                	
							                	$repeatDuration = "";
							                	if(isset($entryObj->repeatDuration))
							                		$repeatDuration = $entryObj->repeatDuration;
						                    	
							                	$contentIsCompleted = 0;
							                	if(isset($entryObj->isCompleted))
							                		$contentIsCompleted = $entryObj->isCompleted;
						                    	
							                	$contentIsSnoozed = 0;
							                	if(isset($entryObj->isSnoozed))
							                		$contentIsSnoozed = $entryObj->isSnoozed;
						                    	
							                	$contentReminderTimestamp = NULL;
							                	if(isset($entryObj->reminderTimestamp))
							                		$contentReminderTimestamp = $entryObj->reminderTimestamp;

						                        if(!isset($isMarked))
						                            $isMarked = 0;
				        
										        if(!isset($contentIsLocked)) {
										            $contentIsLocked = Config::get('app_config.default_content_lock_status');
										        }
				        
										        if(!isset($contentIsShareEnabled)) {
										            $contentIsShareEnabled = Config::get('app_config.default_content_share_status');
										        }

						                		array_push($groupContentAddLogArr, $content);
								                
								            	$content = urldecode($content);
								            	$contentResponse = $depMgmtObj->addEditGroupContent($serId, $content, $contentTitle, $contentType, $restoredGroupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $contentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $contentIsCompleted, $contentIsSnoozed, $contentReminderTimestamp, array(), $grpContentSharedByEmail);
						                        $id = $contentResponse["syncId"];
	         									Log::info('5_group_add_contents : '.$i.' : '.$content.' : Inserted At : '.$id);

						                        if($id > 0)
						                        {
							                    	$tagNameArr = array();
							                        $tagList = $entryObj->tagList;
											        if(is_array($tagList))
											        	$tagNameArr = $tagList;
													else
											        	$tagNameArr = json_decode($tagList);

											        if(count($tagNameArr) > 0)
											        {
											        	$usedTagNameArr = array_merge($usedTagNameArr, $tagNameArr);
											        	$contentTagMapping[$id] = $tagNameArr;
											        }
						                        }
					                        }

					                        $finalContentResponse[$i]["syncId"] = sracEncryptNumberData($id, $userSession);
					                    }
					                }
									$responseLogArr['3_group_add_contents'] = $groupContentAddLogArr; 
	         						Log::info('3_group_add_contents ENDS: ');

									$usedTagNameArr = array_unique($usedTagNameArr);// restore group members
					                $groupMemberAddLogArr = array();
					                $groupMemberEmailLogArr = array();
				            		$isPreRestore = FALSE;
					             	if($orgId > 0)
					             	{
						               	
					             	}
					             	else
					             	{
	         							Log::info('4_group_add_members BEGINS: ');

						               	foreach ($memberEmailArr as $memberEmail) 
						                {
					                		array_push($groupMemberEmailLogArr, $memberEmail);

	     									Log::info('4_group_add_members : '.$memberEmail.' : Will insert');

					            			$memberUser = Appuser::active()->where('email','=',$memberEmail)->first();
					            			if(isset($memberUser))
					            			{
					            				$memberUserId = $memberUser->appuser_id;
	     										Log::info('7_group_add_members : '.$memberEmail.' : memberUserId : '.$memberUserId);

					            				$appuserContact = AppuserContact::ofUser($userId)->ofRegisteredUser($memberUserId)->first();
					            				$appuserContactId = 0;
					            				if(isset($appuserContact))
					            				{
					            					$appuserContactId = $appuserContact->appuser_contact_id;
					            				}

					            				$isMemberAdmin = 0;
					            				if($memberUserId == $userId)
					            				{
					            					$isMemberAdmin = 1;
					            				}

								                $consDepMgmtObj = New ContentDependencyManagementClass;
								                $consDepMgmtObj->withOrgKey($memberUser, $encOrgId);

								                $userTagNameIdMapArr = array();
								                if(isset($usedTagNameArr) && count($usedTagNameArr)>0)
								                {
								                	foreach ($usedTagNameArr as $i => $usedTagName)
								                    {
								                        $tagExistsFlag = FALSE;
								                        $tagExists = $consDepMgmtObj->getTagObjectByName($usedTagName);

								                        $tagId = 0;
								                        if(isset($tagExists))
								                        {
								                        	$tagExistsFlag = TRUE;
															if($orgId > 0)
											    			{
																$tagId = $tagExists->employee_tag_id;  				
															}
															else
															{
																$tagId = $tagExists->appuser_tag_id;
															}
								                        }
								                        else
								                        {
											                $tagResponse = $consDepMgmtObj->addEditTag(0, $usedTagName);
						                        			$tagId = $tagResponse["syncId"];
								                        }					                        
														
					                        			if($tagId > 0)
					                        			{
										                	$userTagNameIdMapArr[$usedTagName] = $tagId; // ." : exists : ".$tagExistsFlag;
					                        			}
								                    }
								                }

								                $groupMemberData = array();
								                $groupMemberData['group_id'] = $restoredGroupId;
								                $groupMemberData['member_appuser_id'] = $memberUserId;
								                $groupMemberData['appuser_contact_id'] = $appuserContactId;
								                $groupMemberData['is_admin'] = $isMemberAdmin;
								                $groupMemberData['is_favorited'] = $grpIsFavorited;
								                $groupMemberData['is_locked'] = $grpIsLocked;

												$groupMember = NULL;
								                $groupMember = GroupMember::create($groupMemberData);

								                if(isset($groupMember) && count($contentTagMapping) > 0)
								                {
	     											Log::info('7_group_add_members : '.$memberEmail.' : groupMember : ');
								                	foreach ($contentTagMapping as $contentId => $contentTagNameArr) {
								                		$contentTagIdArr = array();
								                		foreach ($contentTagNameArr as $tagName) {
								                			if(isset($userTagNameIdMapArr[$tagName]))
								                			{
								                				$contentTagId = $userTagNameIdMapArr[$tagName];
								                				array_push($contentTagIdArr, $contentTagId);
								                			}
								                		}
								                		if(count($contentTagIdArr) > 0)
								                		{
								                			$contentTagIdArrStr = implode(", ", $contentTagIdArr);
	     													Log::info('7_group_add_member_tags : '.$memberEmail.' : contentId : '.$contentId.' : contentTagIdArr : '.$contentTagIdArrStr);
								                			$consDepMgmtObj->addEditGroupContentTags($contentId, $memberUserId, $contentTagIdArr);
								                		}
								                	}
								                }

								                // add group content tags
				           						// $sendStatus = $this->sendGroupRestorationMessageToDevice($isPreRestore, $memberUserId, $restoredGroupId, $groupName, $restoredByUserEmail);	

				           						// $groupMemberData['isPushSent'] = "true_".$sendStatus;		

					                			array_push($groupMemberAddLogArr, $groupMemberData);						
					            			}
						                }
					             	}

									$responseLogArr['6_group_member_emails'] = $groupMemberEmailLogArr;
									$responseLogArr['7_group_add_members'] = $groupMemberAddLogArr;

	         						Log::info('7_group_add_members ENDS: ');
								}

				                $response["contentResponse"] = $finalContentResponse;
				                $response["restoredGroup"] = $restoredGroupDetails;
				                //$response['responseLogArr'] = $responseLogArr;
				                //$response['usedTagNameArr'] = $usedTagNameArr;
				                //$response['contentTagMapping'] = $contentTagMapping;
				                //$response['memberEmailArr'] = $memberEmailArr;
	     						Log::info('response COMPILED');
	     						Log::info($response);
	     						Log::info('==========');
	     					}
	     					catch(Exception $e)
	     					{
         						Log::info('ERROR ENCOUNTERED : ');
         						Log::info($e);

	     					}
                		}
                		else
                		{
			                $status = -1;
			                $msg = "Not a valid backup for restoring the group - ".$baseGroupName;     
                		}	
                	}
		            else
		            {  
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');        
		            }
                }
		        else
		        {
		            $status = -1;
		            $msg = Config::get('app_config_notif.err_invalid_data').' 1';
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
            $msg = Config::get('app_config_notif.err_invalid_data').' 2';
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";
		Log::info('response SENT');

        return Response::json($response);
    }

    public function checkGroupBackupCanBeRestored()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $groupId = Input::get('grpId');
        $backupGroupId = Input::get('backupGrpId');

        $groupId = isset($groupId) && $groupId != "" ? $groupId : "";
        $backupGroupId = isset($backupGroupId) && $backupGroupId != "" ? $backupGroupId : "";

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
				
                $groupId = sracDecryptNumberData($groupId, $userSession);
                $backupGroupId = sracDecryptNumberData($backupGroupId, $userSession);

                $isFolder = FALSE;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $restoredByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $group = $depMgmtObj->getGroupObject($groupId);

                if(isset($group) && $orgId == 0 && $backupGroupId > 0)
                {
                	$groupName = $group->name;
                	$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($groupId);

                	if($isUserGroupAdmin)
                	{
                		$restoreIdLogStr = $group->restore_id_log_str;
                		$isValidBackup = $this->getIsValidBackupForRestore($groupId, $restoreIdLogStr, $backupGroupId);

                		if($isValidBackup)
                		{
                			$status = 1;
                		}
                		else
                		{
			                $status = -1;
			                $msg = "Not a valid backup for restoring the group - ".$groupName;     
                		}	        			
                	}
		            else
		            {  
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');        
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

    public function getIsValidBackupForRestore($groupId, $restoreIdLogStr, $backupGroupId)
    {
    	$restoreIdLogArr = $this->getGroupRestoreIdArr($restoreIdLogStr);
    	array_push($restoreIdLogArr, $groupId);

    	$isValid = FALSE;
    	if(in_array($backupGroupId, $restoreIdLogArr))
    	{
    		$isValid = TRUE;
    	}
    	return $isValid;
    }

    public function getGroupRestoreIdArr($restoreIdLogStr)
    {
    	$restoreIdLogArr = array();
    	if(isset($restoreIdLogStr) && $restoreIdLogStr != "")
    	{
			$restoreIdLogArr = explode(",", $restoreIdLogStr);
    	}
    	return $restoreIdLogArr;
    }

    public function getGroupRestoreIdStr($restoreIdLogArr)
    {
    	$restoreIdLogStr = "";
    	if(isset($restoreIdLogArr) && is_array($restoreIdLogArr) && count($restoreIdLogArr) > 0)
    	{
			$restoreIdLogStr = implode(",", $restoreIdLogArr);
    	}
    	return $restoreIdLogStr;
    }

    public function groupRestoreProcessCompleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $groupId = Input::get('grpId');
        $baseGroupId = Input::get('baseGrpId');

		Log::info('1 : groupId : ');
		Log::info($groupId);

		Log::info('1 : baseGroupId : ');
		Log::info($baseGroupId);

        $groupId = isset($groupId) && $groupId != "" ? $groupId : "";
        $baseGroupId = isset($baseGroupId) && $baseGroupId != "" ? $baseGroupId : "";

		Log::info('2 : groupId : ');
		Log::info($groupId);

		Log::info('2 : baseGroupId : ');
		Log::info($baseGroupId);

        $response = array();
        $responseLogArr = array();

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

                $groupId = sracDecryptNumberData($groupId, $userSession);
                $baseGroupId = sracDecryptNumberData($baseGroupId, $userSession);

				Log::info('3 : groupId : ');
				Log::info($groupId);

				Log::info('3 : baseGroupId : ');
				Log::info($baseGroupId);

                $isFolder = FALSE;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $restoredByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $group = $depMgmtObj->getGroupObject($groupId);
                $baseGroup = $depMgmtObj->getGroupObject($baseGroupId);

                if(isset($group) && isset($baseGroup) && $orgId == 0)
                {
                	$groupName = $group->name;
                	$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($groupId);

                	if($isUserGroupAdmin)
                	{
                		$status = 1;

						Log::info('baseGroup');
						Log::info($baseGroup);

		                if(isset($baseGroup))
		                {
		                	$isUserBaseGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($baseGroupId);
		                	if($isUserBaseGroupAdmin)
                			{
                				Log::info('1_group_remove_members BEGINS: ');
				                // remove current group members
				                $groupMemberDelLogArr = array();
								$existingGroupMembers = $depMgmtObj->getGroupMembers($baseGroupId);
								foreach ($existingGroupMembers as $grpMember) 
				                {
				                	array_push($groupMemberDelLogArr, $grpMember);
			           				$grpMember->delete();	
				                }

								$responseLogArr['1_group_remove_members'] = $groupMemberDelLogArr;
		 						Log::info('1_group_remove_members ENDS: ');

		 						Log::info('2_group_remove_contents BEGINS: ');
				                // remove current group contents
				                $groupContentDelLogArr = array();
								$existingGroupContents = $depMgmtObj->getAllContents($isFolder, $baseGroupId);
				               	foreach ($existingGroupContents as $content) 
				                {
			                    	$contentId = $content->group_content_id;
			                        $depMgmtObj->deleteContent($contentId, $isFolder);
				                	array_push($groupContentDelLogArr, $contentId);
				                }
								$responseLogArr['2_group_remove_contents'] = $groupContentDelLogArr;
		 						Log::info('2_group_remove_contents ENDS: ');

		 						Log::info('3_remove_base_group BEGINS : ');
								// delete base group
								$baseGroup->delete();

								$responseLogArr['3_remove_base_group'] = $baseGroup;
		 						Log::info('3_remove_base_group ENDS: ');
                			}
                		}	

 						Log::info('4_send_group_restored_push BEGINS : ');
	            		$isPreRestore = FALSE;
						$groupMembers = $depMgmtObj->getGroupMembers($groupId);
						foreach ($groupMembers as $grpMember) 
		                {
		                	$memberId = $grpMember->member_id;
		                	$memberAppuserId = $grpMember->member_appuser_id;

	 						Log::info('4_send_group_restored_push : memberId : '.$memberId);

		                	if($orgId == 0)
		                	{
           						$sendStatus = $this->sendGroupRestorationMessageToDevice($isPreRestore, $memberAppuserId, $groupId, $groupName, $restoredByUserEmail, $loginToken);	
		                	}
		                	else
		                	{

		                	}
		                }

 						Log::info('4_send_group_restored_push ENDS : ');
                		       			
                	}
		            else
		            {  
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');        
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

    public function groupRestoreProcessFailed()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');   
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $groupId = Input::get('grpId');
        $baseGroupId = Input::get('baseGrpId');

        $groupId = isset($groupId) && $groupId != "" ? $groupId * 1 : 0;

        $response = array();
        $responseLogArr = array();

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
				
                $groupId = sracDecryptNumberData($groupId, $userSession);
                $baseGroupId = sracDecryptNumberData($baseGroupId, $userSession);

                $isFolder = FALSE;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $restoredByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $group = $depMgmtObj->getGroupObject($groupId);
                $baseGroup = $depMgmtObj->getGroupObject($baseGroupId);

                if(isset($group) && isset($baseGroup) && $orgId == 0)
                {
                	$groupName = $group->name;
                	$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($groupId);
                	$isUserBaseGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($baseGroupId);

                	if($isUserGroupAdmin && $isUserBaseGroupAdmin)
                	{
                		$status = 1;

                		$baseGroupName = $baseGroup->name;

						Log::info('baseGroup');
						Log::info($baseGroup);

						Log::info('group');
						Log::info($group);

        				Log::info('1_restore_group_remove_members BEGINS: ');
		                // remove current group members
		                $groupMemberDelLogArr = array();
						$existingGroupMembers = $depMgmtObj->getGroupMembers($groupId);
						foreach ($existingGroupMembers as $grpMember) 
		                {
		                	array_push($groupMemberDelLogArr, $grpMember);
	           				$grpMember->delete();	
		                }

						$responseLogArr['1_restore_group_remove_members'] = $groupMemberDelLogArr;
 						Log::info('1_restore_group_remove_members ENDS: ');

 						Log::info('2_restore_group_remove_contents BEGINS: ');
		                // remove current group contents
		                $groupContentDelLogArr = array();
						$existingGroupContents = $depMgmtObj->getAllContents($isFolder, $groupId);
		               	foreach ($existingGroupContents as $content) 
		                {
	                    	$contentId = $content->group_content_id;
	                        $depMgmtObj->deleteContent($contentId, $isFolder);
		                	array_push($groupContentDelLogArr, $contentId);
		                }
						$responseLogArr['2_restore_group_remove_contents'] = $groupContentDelLogArr;
 						Log::info('2_restore_group_remove_contents ENDS: ');

 						Log::info('3_remove_restore_group BEGINS : ');
						// delete base group
						$group->delete();

						$responseLogArr['3_remove_restore_group'] = $group;
 						Log::info('3_remove_restore_group ENDS: ');

 						Log::info('4_send_base_group_restored_push BEGINS : ');
						$groupMembers = $depMgmtObj->getGroupMembers($baseGroupId);
						foreach ($groupMembers as $grpMember) 
		                {
		                	$memberId = $grpMember->member_id;
		                	$memberAppuserId = $grpMember->member_appuser_id;

	 						Log::info('4_send_base_group_restored_push : memberId : '.$memberId);

		                	if($orgId == 0)
		                	{
           						$sendStatus = $this->sendGroupRestorationFailedMessageToDevice($memberAppuserId, $baseGroupId, $baseGroupName, $restoredByUserEmail, $loginToken);	
		                	}
		                	else
		                	{

		                	}
		                }

 						Log::info('4_send_base_group_restored_push ENDS : ');
                		       			
                	}
		            else
		            {  
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_group_user_not_admin');        
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
}
