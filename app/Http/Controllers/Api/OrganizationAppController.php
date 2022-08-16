<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\PrintField;
use App\Models\Api\AppuserContact;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppKeyMapping;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationUser;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\Api\OrgEmployeeFolder;
use App\Models\Org\Api\OrgEmployeeSource;
use App\Models\Org\Api\OrgEmployeeTag;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgTemplate;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Org\Api\OrgEmployeeBadge;
use App\Models\Org\Api\OrgEmployeeFieldValue;
use App\Models\Org\OrganizationUserField;
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
use DB;
use View;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use URL;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\MailClass;
use App\Http\Traits\OrgCloudMessagingTrait;
use App\Http\Traits\CloudMessagingTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class OrganizationAppController extends Controller
{
	use OrgCloudMessagingTrait;
	use CloudMessagingTrait;
	   
    public function __construct()
    {
        
    }
    
    /**
     * Subscribe to organization.
     *
     * @return json array
     */
    public function subscribeOrganization()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');        
        $orgCode = Input::get('orgCode');
        $code = Input::get('verCode');
        $empEmail = Input::get('empEmail');
        
        $response = array();

        if($encUserId != "" && $code != "" && $orgCode != "" && $empEmail != "")
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
				
				$organization = Organization::byCode($orgCode)->first();				
				
				if(isset($organization))
				{
					$orgId = $organization->organization_id;
					$userEmail = $user->email;
					$userName = $user->fullname;
					$isSelfEnroll = $organization->org_self_enroll_enabled;
					$selfEnrollVerCode = $organization->self_enroll_verification_code;
					$decSelfEnrollVerCode = "";
					if($selfEnrollVerCode != "")
						$decSelfEnrollVerCode = Crypt::decrypt($selfEnrollVerCode);
					
					if(isset($isSelfEnroll) && $isSelfEnroll == 1 && $decSelfEnrollVerCode != "" && $decSelfEnrollVerCode == $code)
	                {
						$status = 1;
						$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
						                
   						$orgUser = OrganizationUser::ofOrganization($orgId)->ofEmpEmail($userEmail)->ofUserEmail($userEmail)->isSelfRegistered()->first();
   						if(!isset($orgUser))
						{
							$employeeDetails = array();
					        $employeeDetails['employee_no'] = "";
					        $employeeDetails['employee_name'] = $userName;
					        $employeeDetails['contact'] = $user->contact;
					        $employeeDetails['dob'] = "";
					        $employeeDetails['department_id'] = 0;
					        $employeeDetails['designation_id'] = 0;
					        $employeeDetails['start_date'] = date(Config::get('app_config.date_db_format'));
					        $employeeDetails['emergency_contact'] = $user->contact;
					        $employeeDetails['gender'] = $user->gender;						                                
			                $employeeDetails['email'] = $userEmail;
			                $employeeDetails['is_self_registered'] = 1;
			                $employeeDetails['is_verified'] = 1;
			                $employeeDetails['org_emp_key'] = 1;
			                $employeeDetails['created_by'] = $userId;
			                $employeeDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();							
							
							//create org user               
					        $modelObj = New OrgEmployee;
					        $modelObj->setConnection($orgDbConName);
					        $empTableName = $modelObj->table; 
					    	
							$orgEmpId = DB::connection($orgDbConName)->table($empTableName)->insertGetId($employeeDetails);  
							$orgEmployee = $modelObj->byId($orgEmpId)->first();
					        if(isset($orgEmployee))
					        {
								$encOrgEmpId = Hash::make($orgId."_".$orgEmpId);
						        $orgEmployee->org_emp_key = $encOrgEmpId;
						        $orgEmployee->save();
							}
														              
					        $orgUser = New OrganizationUser;
					        $orgUser->organization_id = $orgId;
					        $orgUser->appuser_email = $userEmail;
					        $orgUser->emp_email = $userEmail;
					        $orgUser->is_verified = 1;
					        $orgUser->emp_id = $orgEmpId;
					        $orgUser->is_self_registered = 1;
					        $orgUser->save();					       	  
							
							//look for auto 
					        OrganizationClass::setEmployeeDefaultParams($orgId, $orgEmpId);

	                        //Send message for welcome
	                        MailClass::sendOrgSubscribedMail($orgId, $orgEmpId);
				
							$modelObj = New OrgEmployee;
			                $modelObj->setConnection($orgDbConName);
			                $orgEmployee = $modelObj->select(['*', $empTableName.'.is_active as is_active'])->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
			                
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
                			$orgAttachmentRetainDays = $organization->org_attachment_retain_days;

                			$isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $orgEmpId);
                			$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $orgEmpId);
	                        
							$encOrgId = Crypt::encrypt($orgId."_".$orgEmpId);
	                        $orgDetails = array();
		                	$orgDetails['key'] = $encOrgId;
		                	$orgDetails['map_key'] = $orgEmployee->org_emp_key;
		                	$orgDetails['code'] = $organization->org_code;
		                	$orgDetails['reg_name'] = $organization->regd_name;
		                	$orgDetails['sys_name'] = $organization->system_name;
	                		$orgDetails['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
	                		$orgDetails['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
	                		$orgDetails['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
	                		$orgDetails['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
		                	$orgDetails['user_no'] = $orgEmployee->employee_no;
		                	$orgDetails['user_name'] = $orgEmployee->employee_name;
	                		$orgDetails['user_email'] = $orgEmployee->email;
		                	$orgDetails['user_department'] = isset($orgEmployee->department_name)?$orgEmployee->department_name:"";
		                	$orgDetails['user_designation'] = isset($orgEmployee->designation_name)?$orgEmployee->designation_name:"";
		                	$orgDetails['user_status'] = $orgEmployee->is_active;
        					$orgDetails['logo_url'] = $orgLogoUrl;
        					$orgDetails['logo_thumb_url'] = $orgLogoThumbUrl;
        					$orgDetails['logo_filename'] = $logoFilename;
            				$orgDetails['org_attachment_retain_days'] = $orgAttachmentRetainDays;
							
							$response['orgDetails'] = $orgDetails;
							
							$this->sendOrgSubscribedMessageToDevice($orgEmpId, $orgId, $loginToken, $orgDetails);
							MailClass::sendOrgUserAutoEnrolledMail($orgId, $orgEmpId);
						}
						else
						{
			                $status = -1;
			                $msg = "You Have Already Subscribed To This Organization";  
						}
					}
					else
					{
						$orgUsers = OrganizationUser::ofEmpEmail($empEmail)->ofOrganization($orgId)->get();	            	
		            	if(isset($orgUsers) && count($orgUsers) > 0)
						{
							foreach($orgUsers as $orgUser)
							{
								$isVerified = $orgUser->is_verified;	
								$verificationCode = $orgUser->verification_code;
					            $orgEmpId = $orgUser->emp_id;
        
						        $depMgmtObj = New ContentDependencyManagementClass;
						       	$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
						        $isActive = $depMgmtObj->getEmployeeIsActive();
					            
								if($isVerified == 0 && $verificationCode != "" && $isActive == 1)
								{
									$decVerCode = Crypt::decrypt($verificationCode);
				                    if($decVerCode == $code)
				                    {
				                    	//$existingOrgEmpId = OrganizationClass::getOrgEmployeeIdFromUser($orgId, $user);
				                    	//if($existingOrgEmpId == 0)	            
							            
							            {
											$status = 1;
					                    	
					                    	$orgUser->verification_code = '';
					                        $orgUser->is_verified = 1;
					                        $orgUser->appuser_email = $userEmail;
					                        $orgUser->save();
					                        
					                        //Create default folder(s) & tag(s)
					                        OrganizationClass::setEmployeeDefaultParams($orgId, $orgEmpId);

					                        //Send message for welcome
					                        MailClass::sendOrgSubscribedMail($orgId, $orgEmpId);
								
											$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
											$modelObj = New OrgEmployee;
							                $modelObj->setConnection($orgDbConName);
							                $orgEmployee = $modelObj->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
							                
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
                							$orgAttachmentRetainDays = $organization->org_attachment_retain_days;

                							$isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $orgEmpId);
                							$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $orgEmpId);
					                        
											$encOrgId = Crypt::encrypt($orgId."_".$orgEmpId);
					                        $orgDetails = array();
						                	$orgDetails['key'] = $encOrgId;
						                	$orgDetails['map_key'] = $orgEmployee->org_emp_key;
						                	$orgDetails['code'] = $organization->org_code;
						                	$orgDetails['reg_name'] = $organization->regd_name;
						                	$orgDetails['sys_name'] = $organization->system_name;
	                						$orgDetails['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
	                						$orgDetails['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
	                						$orgDetails['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
	                						$orgDetails['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
						                	$orgDetails['user_no'] = $orgEmployee->employee_no;
						                	$orgDetails['user_name'] = $orgEmployee->employee_name;
	                						$orgDetails['user_email'] = $orgEmployee->email;
						                	$orgDetails['user_department'] = $orgEmployee->department_name;
						                	$orgDetails['user_designation'] = $orgEmployee->designation_name;
						                	$orgDetails['user_status'] = $isActive;
		                					$orgDetails['logo_url'] = $orgLogoUrl;
		                					$orgDetails['logo_thumb_url'] = $orgLogoThumbUrl;
		                					$orgDetails['logo_filename'] = $logoFilename;
            								$orgDetails['org_attachment_retain_days'] = $orgAttachmentRetainDays;
            								$orgDetails['org_inactivity_day_count'] = $orgInactivityDayCount;
											
											$response['orgDetails'] = $orgDetails;
											
											$this->sendOrgSubscribedMessageToDevice($orgEmpId, $orgId, $loginToken, $orgDetails);
										}
							            /*else
							            {
							                $status = -1;
							                $msg = "User Already Subscribed To This Organization With Another Account";       
							            }*/
							            
							            break;
									}
									else
									{
										$status = -1;
					                	$msg = "Invalid Credentials"; 
									}
								}
							}
							
							if($status != 1)
							{
								$status = -1;
			                	$msg = "User Not Eligible For Organization";    
							}
				        }
						else
			            {
			                $status = -1;
			                $msg = "User Not Eligible For Organization";       
			            }						
					}
											
				}
				else
	            {
	                $status = -1;
	                $msg = "No Such Organization";       
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
	
	public function organizationPrimarySync()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');        
        $encOrgId = Input::get('orgId');    
        
        $response = array();

        if($encUserId != "" && $encOrgId != "")
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
					
	            $constDetails = array();	
				$organization = OrganizationClass::getOrganizationFromOrgKey($encOrgId);
				if(isset($organization))
				{
					$status = 1;
					$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
		            $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
					$tagList = array();
	                $modelObj = New OrgEmployeeTag;
	                $modelObj->setConnection($orgDbConName);
	                $userTags = $modelObj->ofEmployee($orgEmpId)->get();	                
	                foreach ($userTags as $tag) 
	                {
	                	$tagDet = array();
	                    $tagDet['name'] = $tag->tag_name;
	                    $tagDet['syncId'] = sracEncryptNumberData($tag->employee_tag_id, $userSession);
	                    array_push($tagList, $tagDet);
	                }        
	                $tagCnt = count($tagList);
	                
	                $folderList = array();
	                $modelObj = New OrgEmployeeFolder;
	                $modelObj->setConnection($orgDbConName);
	                $userFolders = $modelObj->ofEmployee($orgEmpId)->get();
	                foreach ($userFolders as $folder) 
	                {
	                	$folderDet = array();
	                    $folderDet['name'] = $folder->folder_name;
	                    $folderDet['iconCode'] = $folder->icon_code;
	                    $folderDet['isFavorited'] = $folder->is_favorited;
	                    $folderDet['folderType'] = $folder->folder_type_id;
                		$folderDet['virtualFolderSenderEmail'] = $folder->virtual_folder_sender_email;
	                    $folderDet['appliedFilters'] = $folder->applied_filters;
	                    $folderDet['syncId'] = sracEncryptNumberData($folder->employee_folder_id, $userSession);
	                    array_push($folderList, $folderDet);
	                }
                	$folderCnt = count($folderList);
                	
                	$sourceList = array();
	                $modelObj = New OrgEmployeeSource;
	                $modelObj->setConnection($orgDbConName);
	                $userSources = $modelObj->ofEmployee($orgEmpId)->get();
	                foreach ($userSources as $source) 
	                {
	                	$sourceDet = array();
	                    $sourceDet['name'] = $source->source_name;
	                    $sourceDet['syncId'] = sracEncryptNumberData($source->employee_source_id, $userSession);
	                    array_push($sourceList, $sourceDet);
	                }
                	$sourceCnt = count($sourceList);
	                
	                //Constants
	                $modelObj = New OrgEmployeeConstant;
                	$modelObj = $modelObj->setConnection($orgDbConName);
                	$orgEmpConstant = $modelObj->ofEmployee($orgEmpId)->first();                	
                	if(isset($orgEmpConstant))
                	{  
		                $folderPasscode = "";  
		                $passcodeFolderIdArr = array();  
		                $attachmentSpaceAllotted = 0;
		                $attachmentSpaceAvailable = 0;
		                $dbSize = 0;
                
						$defFolderId = $orgEmpConstant->def_folder_id;
	                    $emailSourceId = $orgEmpConstant->email_source_id;  
	                    $hasFolderPasscode = $orgEmpConstant->folder_passcode_enabled;   
	                    
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
	                       
	                    if($orgEmpConstant->folder_passcode != null)  
	                        $folderPasscode = $orgEmpConstant->folder_passcode;   
	                    if($orgEmpConstant->folder_id_str != null) 
	                    {
                			$passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
	                        $folderIdStr = $orgEmpConstant->folder_id_str;
	                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
	                    }  
	                    if($orgEmpConstant->attachment_kb_allotted != null)  
	                        $attachmentSpaceAllotted = $orgEmpConstant->attachment_kb_allotted;  
	                    if($orgEmpConstant->attachment_kb_available != null)  
	                        $attachmentSpaceAvailable = $orgEmpConstant->attachment_kb_available; 
	                    if($orgEmpConstant->db_size != null)  
	                        $dbSize = $orgEmpConstant->db_size;
	                      
						$constDetails['defFolderId'] = sracEncryptNumberData($defFolderId, $userSession);
		                $constDetails['emailSourceId'] = sracEncryptNumberData($emailSourceId, $userSession);
		                $constDetails['hasFolderPasscode'] = $hasFolderPasscode;    
		                $constDetails['folderPasscode'] = $folderPasscode;  
		                $constDetails['folderIdArr'] = sracEncryptNumberArrayData($passcodeFolderIdArr, $userSession);   
		                $constDetails['attachmentSpaceAllotted'] = $attachmentSpaceAllotted;      
		                $constDetails['attachmentSpaceAvailable'] = $attachmentSpaceAvailable;
		                $constDetails['dbSize'] = $dbSize;                
		                $constDetails['sracShareEnabled'] = $sracShareEnabled;		                
		                $constDetails['sracOrgShareEnabled'] = $sracOrgShareEnabled;
				        $constDetails['sracCopyToProfileEnabled'] = $sracCopyToProfileEnabled;
				        $constDetails['sracRetailShareEnabled'] = $sracRetailShareEnabled;
		                $constDetails['socShareEnabled'] = $socShareEnabled;
		                $constDetails['socFacebookEnabled'] = $socFacebookEnabled;
		                $constDetails['socTwitterEnabled'] = $socTwitterEnabled;
		                $constDetails['socLinkedinEnabled'] = $socLinkedinEnabled;
		                $constDetails['socWhatsappEnabled'] = $socWhatsappEnabled;
		                $constDetails['socEmailEnabled'] = $socEmailEnabled;
		                $constDetails['socSmsEnabled'] = $socSmsEnabled;
		                $constDetails['socOtherEnabled'] = $socOtherEnabled;
					}
					
					$templateArr = array();
                	$modelObj = New OrgTemplate;
                	$modelObj = $modelObj->setConnection($orgDbConName);
                	$orgTemplates = $modelObj->get();
                	foreach($orgTemplates as $orgTemplate)
                	{
						$tempDetails = array();
						$tempDetails['id'] = sracEncryptNumberData($orgTemplate->template_id, $userSession);
						$tempDetails['name'] = $orgTemplate->template_name;
						$tempDetails['text'] = $orgTemplate->template_text;
						
						array_push($templateArr, $tempDetails);
					}
	                
	                //Check if he is added to any groups           	
                	$groupList = array();
	                $modelObj = New OrgGroupMember;
	                $modelObj->setConnection($orgDbConName);
	                $userGroups = $modelObj->joinGroupTable()->ofEmployee($orgEmpId)->get();
	                foreach ($userGroups as $group) 
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
						
	                	$groupDet = array();
	                    $groupDet['name'] = $group->name;
	                    $groupDet['syncId'] = sracEncryptNumberData($group->group_id, $userSession);
	                    $groupDet['isUserAdmin'] = $group->is_admin;
	                    $groupDet['hasPostRight'] = $group->has_post_right;
	                    $groupDet['isTwoWay'] = $group->is_two_way;  
	                    $groupDet['isFavorited'] = $group->is_favorited;  
	                    $groupDet['isGrpLocked'] = $group->is_locked; 
	                    $groupDet['description'] = $group->description;  
						$groupDet['photoUrl'] = $groupPhotoUrl; 
						$groupDet['photoThumbUrl'] = $groupPhotoThumbUrl; 
						$groupDet["photoFilename"] = $photoFilename;     
						$groupDet["allocKb"] = $group->allocated_space_kb;
						$groupDet["usedKb"] = $group->used_space_kb;            
					
						$grpContentList = array();
		                $modelObj = New OrgGroupContent;
		                $modelObj->setConnection($orgDbConName);
	                	$grpContents = $modelObj->ofGroup($group->group_id)->get();
	                	foreach($grpContents as $grpContent)
	                	{
							$grpContentDetails = array();
		                    					
							$conTagsArr = array();
			                $modelObj = New OrgGroupContentTag;
			                $modelObj->setConnection($orgDbConName);
		                    $contentTags = $modelObj->ofGroupContent($grpContent->group_content_id)->get();
		                    foreach ($contentTags as $contentTag) 
		                    {
								array_push($conTagsArr, $contentTag->tag_id);
		                    }
		                    $conTagCnt = count($conTagsArr);
		                    $performDownload = 1;

		                    $attachmentsArr = array();
		                    
			                $modelObj = New OrgGroupContentAttachment;
			                $modelObj->setConnection($orgDbConName);
		                    $contentAttachments = $modelObj->ofGroupContent($grpContent->group_content_id)->get();

		                    $j = 0;
		                    foreach ($contentAttachments as $contentAttachment) 
		                    {
	                   			if($contentAttachment->att_cloud_storage_type_id == 0)
								{
									$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
								}
								else
								{
									$attachmentUrl = $contentAttachment->cloud_file_url; 
								}

                                $attachmentsArr[$j]['name'] = $contentAttachment->filename;
		                        $attachmentsArr[$j]['pathname'] = $contentAttachment->server_filename;
		                        $attachmentsArr[$j]['size'] = $contentAttachment->filesize;
	                            $attachmentsArr[$j]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
	                            $attachmentsArr[$j]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
	                            $attachmentsArr[$j]['cloudFileId'] = $contentAttachment->cloud_file_id;
                            	$attachmentsArr[$j]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
	                            $attachmentsArr[$j]['attCreateTs'] = $contentAttachment->create_ts;
	                            $attachmentsArr[$j]['attUpdateTs'] = $contentAttachment->update_ts;
		                        $attachmentsArr[$j]['url'] = $attachmentUrl;
		                        $attachmentsArr[$j]['performDownload'] = $performDownload;
		                        $attachmentsArr[$j]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
		                        //$totalAttachmentSize += $contentAttachment->filesize;
		                        $j++;
		                    }
		                    $attachmentCnt = count($attachmentsArr);
							
							$frmTs = 0;
		                    if($grpContent->from_timestamp != NULL)
		                   	 	$frmTs = $grpContent->from_timestamp;
							
							$toTs = 0;
		                    if($grpContent->to_timestamp != NULL)
		                   	 	$toTs = $grpContent->to_timestamp;
		                   	 	
		                   	$sharedByEmail = "";
		                   	if($grpContent->shared_by_email != NULL)
		                   		$sharedByEmail = $grpContent->shared_by_email;
						
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
							
		                    $grpContentDetails['syncId'] = sracEncryptNumberData($grpContent->group_content_id, $userSession);
		                    $grpContentDetails['content'] = $decContent;
	                    	$grpContentDetails['encContent'] = $encDecContent;
		                    $grpContentDetails['colorCode'] = $grpContent->color_code;
		                    $grpContentDetails['isLocked'] = $grpContent->is_locked;
	                    	$grpContentDetails['isShareEnabled'] = $grpContent->is_share_enabled;
		                    $grpContentDetails['remindBeforeMillis'] = $grpContent->remind_before_millis;
		                    $grpContentDetails['repeatDuration'] = $grpContent->repeat_duration;
							$grpContentDetails['isCompleted'] = $grpContent->is_completed;
							$grpContentDetails['isSnoozed'] = $grpContent->is_snoozed;
							$grpContentDetails['reminderTimestamp'] = isset($grpContent->reminder_timestamp) ? $grpContent->reminder_timestamp : 0;
		                    $grpContentDetails['contentType'] = $grpContent->content_type_id;
		                    $grpContentDetails['isMarked'] = $grpContent->is_marked;
		                    $grpContentDetails['createTimeStamp'] = $grpContent->create_timestamp;
		                    $grpContentDetails['updateTimeStamp'] = $grpContent->update_timestamp;
							$grpContentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
							$grpContentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
		                    $grpContentDetails['fromTimeStamp'] = $frmTs;
		                    $grpContentDetails['toTimeStamp'] = $toTs;
		                    $grpContentDetails['tagCnt'] = $conTagCnt;
		                    $grpContentDetails['tags'] = sracEncryptNumberArrayData($conTagsArr, $userSession);
		                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
		                    $grpContentDetails['attachments'] = $attachmentsArr;
	                    	$grpContentDetails['sharedByEmail'] = $sharedByEmail;
	                    	
	                    	array_push($grpContentList, $grpContentDetails);
						}
							
						$groupDet['contentCnt'] = count($grpContentList);
						$groupDet['contentArr'] = $grpContentList;
                    	array_push($groupList, $groupDet);
	                }
                	$groupCnt = count($groupList);
					
					$contentList = array();
					$i = 0;
	                $modelObj = New OrgEmployeeContent;
	                $modelObj->setConnection($orgDbConName);

	                $userContents = $modelObj->ofEmployee($orgEmpId)->get();
	                foreach ($userContents as $content) 
	                {
	                    $tagsArr = array();
	                    
	                    $modelObj = New OrgEmployeeContentTag;
		                $modelObj->setConnection($orgDbConName);

		                $contentTags = $modelObj->ofEmployeeContent($content->employee_content_id)->get();
	                    foreach ($contentTags as $contentTag) 
	                    {
	                        array_push($tagsArr, $contentTag->tag_id);
	                    }
	                    $tagCnt = count($tagsArr);
	                    $performDownload = 0;

	                    $j = 0;
	                    $attachmentsArr = array();
	                    $modelObj = New OrgEmployeeContentAttachment;
		                $modelObj->setConnection($orgDbConName);

		                $contentAttachments = $modelObj->ofEmployeeContent($content->employee_content_id)->get();
	                    foreach ($contentAttachments as $contentAttachment) 
	                    {
                   			if($contentAttachment->att_cloud_storage_type_id == 0)
							{
								$attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
							}
							else
							{
								$attachmentUrl = $contentAttachment->cloud_file_url; 
							}

	                        $attachmentsArr[$j]['name'] = $contentAttachment->filename;
	                        $attachmentsArr[$j]['size'] = $contentAttachment->filesize;
                            $attachmentsArr[$j]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
                            $attachmentsArr[$j]['cloudFileUrl'] = $contentAttachment->cloud_file_url;	
                            $attachmentsArr[$j]['cloudFileId'] = $contentAttachment->cloud_file_id;
                        	$attachmentsArr[$j]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
                            $attachmentsArr[$j]['attCreateTs'] = $contentAttachment->create_ts;
                            $attachmentsArr[$j]['attUpdateTs'] = $contentAttachment->update_ts;
	                        $attachmentsArr[$j]['url'] = $attachmentUrl;	                        
	                        $attachmentsArr[$j]['performDownload'] = $performDownload;
	                        $attachmentsArr[$j]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
		                  	
		                  	//$orgAttachmentSize[$orgId] += $contentAttachment->filesize;
		                    
	                        $j++;
	                    }
	                    $attachmentCnt = count($attachmentsArr);
						
						$frmTs = 0;
	                    if($content->from_timestamp != NULL)
	                   	 	$frmTs = $content->from_timestamp;
						
						$toTs = 0;
	                    if($content->to_timestamp != NULL)
	                   	 	$toTs = $content->to_timestamp;
	                   	 	
	                   	$sharedByEmail = "";
	                   	if($content->shared_by_email != NULL)
	                   		$sharedByEmail = $content->shared_by_email;
	                   	
	                   	$decContent = "";
	                   	if(isset($content->content) && $content->content != "")
	                   	{
	                   		try
	                   		{
							    $decContent = Crypt::decrypt($content->content);
						    	$encDecContent = rawurlencode($decContent);
						    	$decContent = utf8_encode($decContent);
							} 
							catch (DecryptException $e) 
							{
							    //
							}
						}
	                   	 	
	                    $contentList[$i]['syncId'] = sracEncryptNumberData($content->employee_content_id, $userSession);
	                    $contentList[$i]['content'] = $decContent;
	                    $contentList[$i]['encContent'] = $encDecContent;
		                $contentList[$i]['colorCode'] = $content->color_code;
		                $contentList[$i]['isLocked'] = $content->is_locked;
		                $contentList[$i]['isShareEnabled'] = $content->is_share_enabled;
	                    $contentList[$i]['remindBeforeMillis'] = $content->remind_before_millis;
	                    $contentList[$i]['repeatDuration'] = $content->repeat_duration;
						$contentList[$i]['isCompleted'] = $content->is_completed;
						$contentList[$i]['isSnoozed'] = $content->is_snoozed;
						$contentList[$i]['reminderTimestamp'] = isset($content->reminder_timestamp) ? $content->reminder_timestamp : 0;
	                    $contentList[$i]['contentType'] = $content->content_type_id;
	                    $contentList[$i]['folderId'] = sracEncryptNumberData($content->folder_id, $userSession);
	                    $contentList[$i]['isMarked'] = $content->is_marked;
	                    $contentList[$i]['source'] = sracEncryptNumberData($content->source_id, $userSession);
	                    $contentList[$i]['createTimeStamp'] = $content->create_timestamp;
	                    $contentList[$i]['updateTimeStamp'] = $content->update_timestamp;
						$contentList[$i]['syncWithCloudCalendarGoogle'] = $content->sync_with_cloud_calendar_google;
						$contentList[$i]['syncWithCloudCalendarOnedrive'] = $content->sync_with_cloud_calendar_onedrive;
	                    $contentList[$i]['fromTimeStamp'] = $frmTs;
	                    $contentList[$i]['toTimeStamp'] = $toTs;
	                    $contentList[$i]['tagCnt'] = $tagCnt;
	                    $contentList[$i]['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
	                    $contentList[$i]['attachmentCnt'] = $attachmentCnt;
	                    $contentList[$i]['attachments'] = $attachmentsArr;
	                    $contentList[$i]['sharedByEmail'] = $sharedByEmail;
	                	$contentList[$i]['isRemoved'] = $content->is_removed;
	                	$contentList[$i]['removedAt'] = $content->removed_at;
	                    $i++;
	                }

					if(isset($constDetails))
					{
		                $response['tagCnt'] = $tagCnt;
		                $response['tagArr'] = $tagList;
		                $response['folderCnt'] = $folderCnt;
		                $response['folderArr'] = $folderList;
		                $response['sourceCnt'] = $sourceCnt;
		                $response['sourceArr'] = $sourceList;
		                $response['constArr'] = $constDetails;
		                $response['groupCnt'] = $groupCnt;
		                $response['groupArr'] = $groupList;
	                	$response['templateCnt'] = count($templateArr);
	                	$response['templateArr'] = $templateArr;
	                	$response['contentArr'] = $contentList;
	                	$response['contentCnt'] = count($contentList);
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

        $response = array();
        
        if($encUserId != "" && $encOrgId != "")
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
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);					
				}
           			
           		if(isset($orgDbConName))
           		{					     
	                $status = 1;
	                
	                $i = 0;
	                $groupList = array();     
	                
					$modelObj = New OrgGroupMember;
					$modelObj = $modelObj->setConnection($orgDbConName);
					
	                $userGroups = $modelObj->ofEmployee($orgEmpId)->ofDistinctGroup();
	    			$userGroups = $userGroups->joinGroupTable();
	                
	                if(isset($searchStr) && $searchStr != "")
	                {		
	                	$userGroups = $userGroups->where(function($query) use ($searchStr)
							            {
							                $query->where('name','like',"%$searchStr%");
							            });		
					}
					
					$userGroups = $userGroups->orderBy('name', 'ASC');
					$userGroupArr = $userGroups->get();
	                  
	        		$grpPhotoBaseUrl = Config::get('app_config.url_path_org_group_photo');  
	                foreach ($userGroupArr as $userGroup) 
	                {
						$grpId = $userGroup->group_id;
						$name = $userGroup->name;
						$isTwoWay = $userGroup->is_two_way;						 
						$isAdmin = $userGroup->is_admin;
						$hasPostRight = $userGroup->has_post_right;  
	                    $isFavorited = $userGroup->is_favorited;  
	                    $isGrpLocked = $userGroup->is_locked;  
		    			
		    			$groupPhotoUrl = "";
						$photoFilename = $userGroup->img_server_filename;
		    			if(isset($photoFilename) && $photoFilename != "")
		    			{
		    				$groupPhotoUrl = url($grpPhotoBaseUrl.$photoFilename);
						}
						
						if($hasPostRight == 1)
						{
							$groupList[$i]["syncId"] = sracEncryptNumberData($grpId, $userSession);
							$groupList[$i]["name"] = $name;
							$groupList[$i]["isUserAdmin"] = $isAdmin;
							$groupList[$i]["hasPostRight"] = $hasPostRight;
							$groupList[$i]["isFavorited"] = $isFavorited;
							$groupList[$i]["isGrpLocked"] = $isGrpLocked;
							$groupList[$i]["isTwoWay"] = $isTwoWay;
							$groupList[$i]["photoUrl"] = $groupPhotoUrl;
							$i++;	
						}		
					} 
					$groupCnt = count($groupList);      
					$response["groupCnt"] = $groupCnt;          
					$response["groupRes"] = $groupList;  
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
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getAppuserEmployeeList()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $searchStr = Input::get('searchStr');
        $loginToken = Input::get('loginToken');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "")
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
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}		        
           			
           		if(isset($orgDbConName))
           		{					     
	                $status = 1;
	                
	                $i = 0;
	                $groupList = array();     
	                
					$modelObj = New OrgEmployee;
					$modelObj = $modelObj->setConnection($orgDbConName);
					
	                $userUsers = $modelObj->verified()->active()->exceptEmployee($orgEmpId)->ofDistinctEmployee();
	                
	                if(isset($searchStr) && $searchStr != "")
	                {		
	                	$userUsers = $userUsers->where(function($query) use ($searchStr)
							            {
							                $query->where('employee_name','like',"%$searchStr%");
							            });		
					}
					
					$userList = array();
					$userSelectList = array();

					$userUsers = $userUsers->orderBy('employee_name', 'ASC');
					$userUserArr = $userUsers->get(); 
	                foreach ($userUserArr as $userUser) 
	                {
						$id = $userUser->employee_id;
						$name = $userUser->employee_name;
						$email = $userUser->email;
						$isVerified = $userUser->is_verified;
						
						$email = sanitizeEmailString($email);

						if($isVerified == 1)
						{
							$userDetails = array();
							$userDetails['id'] = sracEncryptNumberData($id, $userSession);
							$userDetails['name'] = $name;
							$userDetails['email'] = $email;
							array_push($userList, $userDetails);

							$userSelectObj = array();
							$userSelectObj["id"] = sracEncryptNumberData($id, $userSession);
							$userSelectObj["text"] = $name." [".$email."]";
							array_push($userSelectList, $userSelectObj);
						}
					} 
					$userCnt = count($userList);      
					$response["userCnt"] = $userCnt;          
					$response["userRes"] = $userList;
				   
					$response["results"] = $userSelectList; 
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
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getAppuserSenderEmailMappedList()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $searchStr = Input::get('searchStr');
        $loginToken = Input::get('loginToken');
        $isSenderVirtualFolder = Input::get('isSenderVirtualFolder');
        $senderVirtualFolderEmail = Input::get('senderVirtualFolderEmail');

        if(!isset($isSenderVirtualFolder) || $isSenderVirtualFolder != 1)
        {
        	$isSenderVirtualFolder = 0;
        	$senderVirtualFolderEmail = '';
        }

        if(!isset($senderVirtualFolderEmail))
        {
        	$senderVirtualFolderEmail = '';
        }

        $response = array();
        
        if($encUserId != "")// && $encOrgId != "")
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
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}		        
           			
           		if(isset($orgDbConName))
           		{					     
	                $status = 1;
	                
	                $i = 0;
	                $groupList = array();     
	                
					$modelObj = New OrgEmployee;
					$modelObj = $modelObj->setConnection($orgDbConName);
					
	                $userUsers = $modelObj->verified()->exceptEmployee($orgEmpId)->ofDistinctEmployee();
	                
	                if(isset($searchStr) && $searchStr != "")
	                {		
	                	$userUsers = $userUsers->where(function($query) use ($searchStr)
							            {
							                $query->where('employee_name','like',"%$searchStr%");
							                $query->orWhere('email','like',"%$searchStr%");
							            });		
					}
					
					$userList = array();
					$userUsers = $userUsers->orderBy('employee_name', 'ASC');

					if($isSenderVirtualFolder == 1 && $senderVirtualFolderEmail != "")
					{
						$userUsers = $userUsers->where('email', $senderVirtualFolderEmail);
					}

					$userUserArr = $userUsers->get(); 
	                foreach ($userUserArr as $userUser) 
	                {
						$id = $userUser->employee_id;
						$name = $userUser->employee_name;
						$email = $userUser->email;
						$isVerified = $userUser->is_verified;
						
						$email = sanitizeEmailString($email);

						if($isVerified == 1)
						{
							$userDetails = array();
							$userDetails['id'] = $email;
							$userDetails['text'] = $name." [".$email."]";
							
							array_push($userList, $userDetails);
						}
					}
					$userCnt = count($userList);      
					$response["userCnt"] = $userCnt;          
					$response["results"] = $userList;  
				}
				else if($orgId == 0)
				{
	                $userRegEmail = $user->email;
	                
	            	$userCont = AppuserContact::ofUser($userId)->where('email','<>',$userRegEmail);
	                
	                $isBlockedValArr = array();
	                array_push($isBlockedValArr, 0);
					
	                $userCont = $userCont->whereIn('is_blocked',$isBlockedValArr);
	                	
	                if(isset($searchStr) && $searchStr != "")
	                {		
	                	$userCont = $userCont->where(function($query) use ($searchStr)
								            {
								                $query->where('name','like',"%$searchStr%")
								                      ->orWhere('email','like',"%$searchStr%");
								            });			
					}

					if($isSenderVirtualFolder == 1 && $senderVirtualFolderEmail != "")
					{
						$userCont = $userCont->where('email', $senderVirtualFolderEmail);
					}
					
					$userCont = $userCont->orderBy('name', 'asc');
					$userCont = $userCont->where('is_srac_regd', '=', '1');
	                
	                $userContArr = $userCont->get();
                

	                $i = 0;
	                $userList = array();
	                foreach ($userContArr as $usrCont) 
	                {
						$contId = $usrCont->appuser_contact_id;
						$name = $usrCont->name;
						$email = $usrCont->email;
						$isRegd = $usrCont->is_srac_regd;
						$isBlocked = $usrCont->is_blocked;
						$isEmailRegistered = $isRegd;
						
						$email = sanitizeEmailString($email);
							
						if($isEmailRegistered == 1)
						{							
							$userList[$i]["id"] = $email;
							$userList[$i]["text"] = $name." [".$email."]";

							$i++;
						}							
					} 

					$userCnt = count($userList);      
					$response["userCnt"] = $userCnt;          
					$response["results"] = $userList;    
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

	public function shareOrgContent()
	{
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $contentArr = Input::get('contentArr');
        $contactIdArr = Input::get('contactIdArr');
        $orgEmpIdArr = Input::get('userIdArr');
        $groupIdArr = Input::get('groupIdArr');
        $emailArr = Input::get('emailArr');
        $isFolder = Input::get('isFolder');
        $inpIsLocked = Input::get('isLocked');
        $loginToken = Input::get('loginToken');
        $inpIsShareEnabled = Input::get('isShareEnabled');
		$contentIdArr = Input::get('idArr');

     	$oneLineContentText = Input::get('oneLineContentText');
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
        
        $contactIdArr = jsonDecodeArrStringIfRequired($contactIdArr);
        $orgEmpIdArr = jsonDecodeArrStringIfRequired($orgEmpIdArr);
        $groupIdArr = jsonDecodeArrStringIfRequired($groupIdArr);
        $emailArr = jsonDecodeArrStringIfRequired($emailArr);


    	$contentArr = jsonDecodeArrStringIfRequired($contentArr);
    	$contentIdArr = jsonDecodeArrStringIfRequired($contentIdArr);
        
        if(!isset($inpIsLocked)) {
			$inpIsLocked = Config::get('app_config.default_content_lock_status');
		}

        if(!isset($inpIsShareEnabled)) {
			$inpIsShareEnabled = Config::get('app_config.default_content_share_status');
		}
		
		// $contentIdArr = array();
		// if(!isset($contentArr))
		// {

		// 	$contentIdArr = Input::get('idArr');
		//       	$contentIdArr = jsonDecodeArrStringIfRequired($contentIdArr);
		//      		$contentIdArr = json_decode($contentIdArr);
		//   	 	if(!isset($contentIdArr))
		//   	 	{
		// 		$contentIdArr = array();
		// 	}
		// }



        $response = array();

        // $response['contentArr'] = $contentArr;
        // $response['contentIdArr'] = $contentIdArr;
        // $response['contactIdArr'] = $contactIdArr;
        // $response['orgEmpIdArr'] = $orgEmpIdArr;
        // $response['groupIdArr'] = $groupIdArr;
        // $response['emailArr'] = $emailArr;

        if($encUserId != "" && (($isOneLineQuickShare == 0 && (count($contentArr) + count($contentIdArr) > 0)) || ($isOneLineQuickShare == 1 && $oneLineContentText != "")) && (count($contactIdArr) + count($orgEmpIdArr) + count($groupIdArr) + count($emailArr) > 0))
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

                $contentIdArr = sracDecryptNumberArrayData($contentIdArr, $userSession);
                $contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
                $orgEmpIdArr = sracDecryptNumberArrayData($orgEmpIdArr, $userSession);
                $groupIdArr = sracDecryptNumberArrayData($groupIdArr, $userSession);
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{        
	                $status = 1;
	                $sharedByUserEmail = $user->email;
	                $sharedByUserName = $user->fullname;
	                
	                $empModelObj = New OrgEmployee;
	                $empModelObj->setConnection($orgDbConName);
	                
	                $sharerEmployee = $empModelObj->byId($orgEmpId)->first();
	                $sharedByEmpEmail = $sharerEmployee->email;             
	                $sharedByEmpName = $sharerEmployee->employee_name;
	                
					$empConstantModelObj = New OrgEmployeeConstant;
					$empConstantModelObj->setConnection($orgDbConName);					
					$empConstantObj = $empConstantModelObj->ofEmployee($orgEmpId)->first();
					
					$hasRightToShareOutsideOrg = FALSE;
					if(isset($empConstantObj) && isset($empConstantObj->is_srac_retail_share_enabled) && $empConstantObj->is_srac_retail_share_enabled == 1) {
						$hasRightToShareOutsideOrg = TRUE;
					}
					
					$hasRightToShareWithinOrg = FALSE;
					if(isset($empConstantObj) && isset($empConstantObj->is_srac_org_share_enabled) && $empConstantObj->is_srac_org_share_enabled == 1) {
						$hasRightToShareWithinOrg = TRUE;
					}
	                
	                $j=0;
	                $finalShareUserIdArr = array();
	                if($hasRightToShareOutsideOrg) {
						$shareUserIdArr = array();
		                for($i=0; $i<count($contactIdArr); $i++)
		                {
		                	$contactId = $contactIdArr[$i];
		                	
		                	if($contactId > 0)
		                	{
								$shareUser = AppuserContact::findOrFail($contactId);
		                	
			                	if(isset($shareUser))
			                	{
				            		$selContactEmail = $shareUser->email;
				            		$selContactContactNo = $shareUser->contact_no;
				            		$memberAppuserId = $shareUser->regd_appuser_id;

			                		$shareAppUser = Appuser::forRegisteredAppUserByEmailOrContactNo($selContactEmail, $selContactContactNo)->active()->first();
				            		if(isset($shareAppUser))
				            		{
										$memberAppuserId = $shareAppUser->appuser_id;
										$shareUser->regd_appuser_id = $memberAppuserId;
										$shareUser->save();
				            		}
                			

			                		if(isset($shareAppUser))
			                		{
										$shareUserIdArr[$j] = $memberAppuserId;
										$j++;								
									}
								}
							}                	
		                }
	                	$shareUserIdArr = array_unique($shareUserIdArr);
	                
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
													
							if(!isset($isSharedUserBlocked) && !isset($isUserBlocked))
							{
								array_push($finalShareUserIdArr, $shareUserId);
							}
						}
					}
	                
	                $j=0;
	                $shareEmpIdArr = array();
	                if($hasRightToShareWithinOrg) {
		                for($i=0; $i<count($orgEmpIdArr); $i++)
		                {
		                	$shareOrgEmpId = $orgEmpIdArr[$i];	   
		                	
		                	$idUser = $empModelObj->active()->verified()->byId($shareOrgEmpId)->first();
		                		
	                		if(isset($idUser))
	                		{
								$shareEmpIdArr[$j] = $shareOrgEmpId;
								$j++;
							}                	
		                }

		                for($i=0; $i<count($emailArr); $i++)
		                {
		                	$email = $emailArr[$i];
		                	
		                	if($email != "")
		                	{
		                		$emailUser = $empModelObj->active()->verified()->ofEmail($email)->first();
		                		
		                		if(isset($emailUser))
		                		{
									$shareEmpIdArr[$j] = $emailUser->employee_id;
									$j++;							
								}
							}                	
		                }
		                
		                $shareEmpIdArr = array_unique($shareEmpIdArr);
					}
	                
	                $grpModelObj = New OrgGroup;
	                $grpModelObj->setConnection($orgDbConName);
	                
	                $grpMemModelObj = New OrgGroupMember;
	                $grpMemModelObj->setConnection($orgDbConName);

	                $j = 0;
	                $finalShareGroupIdArr = array();
	                $memberIdArr = array();
	                $groups = array();
	                for($i=0; $i<count($groupIdArr); $i++)
	                {
	                	$grpId = $groupIdArr[$i];

	                	if($grpId > 0)
	                	{
	                		$groupMem = $grpMemModelObj->joinGroupTable()->isEmployeeGroupMember($grpId, $orgEmpId)->first();
		                	
		                	if(isset($groupMem))
		                	{
								$isGroupTwoWay = $groupMem->is_two_way;
								$memHasPostRight = $groupMem->has_post_right;
								$isMemAdmin = $groupMem->is_admin;

			                	if($memHasPostRight == 1 || $isMemAdmin == 1)
			                	{
									$finalShareGroupIdArr[$j] = $groupMem->employee_id;
									$memberIdArr[$j] = $groupMem->member_id;
									$groups[$j] = $groupMem;
									$j++;
								} 
							}
						}		           	
	                }
	                
	                if(count($finalShareUserIdArr) + count($shareEmpIdArr) + count($groups) > 0)
	                {	
						$totalContentSent = 0;

		                $isFolderBool = FALSE;
		                if($isFolder == 1)
		                {
		                	$isFolderBool = TRUE;
		                }
		                
		                $depMgmtObj = New ContentDependencyManagementClass;
		                $depMgmtObj->withOrgKey($user, $encOrgId);
	                	
						$appUsers = Appuser::whereIn('appuser_id', $finalShareUserIdArr)->get();	
											
						$orgEmployees = $empModelObj->byIdArr($shareEmpIdArr)->active()->get();

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
											array_push($attachmentArr, $attachment->content_attachment_id);
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
							
		                $conModelObj = New OrgEmployeeContent;
		                $conModelObj->setConnection($orgDbConName);
		                
		                $grpConModelObj = New OrgGroupContent;
		                $grpConModelObj->setConnection($orgDbConName);
							
		                $conAttModelObj = New OrgEmployeeContentAttachment;
		                $conAttModelObj->setConnection($orgDbConName);
		                
		                $grpConAttModelObj = New OrgGroupContentAttachment;
		                $grpConAttModelObj->setConnection($orgDbConName);
	                
						$compGeneratedContentArr = array();

						for($i=0; $i<count($contentArr); $i++)
		                {
		                	$contObj = $contentArr[$i];
		                	$contId = sracDecryptNumberData($contObj->id, $userSession);
		                	$hasAttachment = $contObj->hasAtt;
		                	
		                	$userContent = NULL;
		                	if($isFolder == 1)
		                    	$userContent = $conModelObj->byId($contId)->first();
		                    else
		                    	$userContent = $grpConModelObj->byGroupContentId($contId)->first();
		                    
		                    if(isset($userContent))
			                {
			                	$totalContentSent++;	

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
			                    				$contentAttachmentsArr = $conAttModelObj->byIdArr($attachmentArr)->get();
			                    			else
			                    				$contentAttachmentsArr = $grpConAttModelObj->byIdArr($attachmentArr)->get();
										}
									}		                
			                		
			                		//For Org Users
			                		if(count($orgEmployees) > 0)
			                		{
			        					$addedEmployeeContentResponseArr = $this->addEmployeeContent($orgEmployees, $orgId, $userContent, $contentIsLocked, $inpIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByEmpName, $sharedByEmpEmail, $orgEmpId, $userSession);   

			        					if(count($addedEmployeeContentResponseArr) > 0)
			        					{
			        						$compGeneratedContentArr = array_merge($compGeneratedContentArr, $addedEmployeeContentResponseArr);
			        					}  
									}
								
									//For Groups
									if(count($groups) > 0)
			                		{
				        				$addedGroupContentResponseArr = $this->addGroupContent($orgEmpId, $groups, $memberIdArr, $finalShareGroupIdArr, $orgId, $userContent, $contentIsLocked, $inpIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByEmpName, $sharedByEmpEmail, $userSession);

			        					if(count($addedGroupContentResponseArr) > 0)
			        					{
			        						$compGeneratedContentArr = array_merge($compGeneratedContentArr, $addedGroupContentResponseArr);
			        					}
			                		}
			                		
			                		//For Retail Users
			                		if(count($appUsers) > 0)
			                		{
			        					$addedFolderContentResponseArr = $this->addUserContent($appUsers, $userContent, $contentIsLocked, $inpIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $userSession);   

			        					if(count($addedFolderContentResponseArr) > 0)
			        					{
			        						$compGeneratedContentArr = array_merge($compGeneratedContentArr, $addedFolderContentResponseArr);
			        					}  
									}

			                		if($performContentRemove == 1)
			                		{
			                			$depMgmtObj->deleteContent($contId, $isFolderBool);
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
    
    public function addEmployeeContent($orgEmployees, $orgId, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByEmpName, $sharedByUserEmail, $sharedByEmpId, $userSession)
    {
    	$addedEmployeeContentResponseArr = array();
		foreach($orgEmployees as $orgEmployee)
		{
			$empId = $orgEmployee->employee_id;
	        $appuserId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
    		$user = Appuser::byId($appuserId)->first();
			
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);   
       		$addedFolderContentId = $depMgmtObj->createSentFolderContent($mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByUserEmail, $sharedByEmpId);

            if($addedFolderContentId > 0)
            {
				$encOrgId = Crypt::encrypt($orgId."_".$empId);

            	$addedEmployeeContentResponseObj = array();
            	$addedEmployeeContentResponseObj['contentId'] = sracEncryptNumberData($addedFolderContentId, $userSession);
            	$addedEmployeeContentResponseObj['isFolder'] = 1;
            	$addedEmployeeContentResponseObj['empId'] = sracEncryptNumberData($empId, $userSession);
				$addedEmployeeContentResponseObj['orgId'] = $encOrgId;
            	$addedEmployeeContentResponseObj['userId'] = sracEncryptNumberData($appuserId, $userSession);
            	
            	array_push($addedEmployeeContentResponseArr, $addedEmployeeContentResponseObj); 
            }
		}
		return $addedEmployeeContentResponseArr;
	}
    
    public function addGroupContent($empId, $groups, $memberIdArr, $memberEmpIdArr, $orgId, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByEmpName, $sharedByUserEmail, $userSession)
    {
    	$addedGroupContentResponseArr = array();
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);  
		foreach($groups as $i => $group)
		{
			$groupId = $group->group_id;   
			$memberId = $memberIdArr[$i];
			$memberEmpId = $memberEmpIdArr[$i];

	        $recDepMgmtObj = New ContentDependencyManagementClass;
	        $recDepMgmtObj->withOrgIdAndEmpId($orgId, $memberEmpId); 
	        $appuserId = $recDepMgmtObj->getUserId();

            $addedGroupContentId = $depMgmtObj->createSentGroupContent($groupId, $memberId, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByEmpName, $sharedByUserEmail);

            if($addedGroupContentId > 0)
            {
				$encOrgId = Crypt::encrypt($orgId."_".$empId);

            	$addedGroupContentResponseObj = array();
            	$addedGroupContentResponseObj['contentId'] = sracEncryptNumberData($addedGroupContentId, $userSession);
            	$addedGroupContentResponseObj['isFolder'] = 0;
            	$addedGroupContentResponseObj['groupId'] = sracEncryptNumberData($groupId, $userSession);
				$addedGroupContentResponseObj['orgId'] = $encOrgId;
            	$addedGroupContentResponseObj['userId'] = sracEncryptNumberData($appuserId, $userSession);

            	array_push($addedGroupContentResponseArr, $addedGroupContentResponseObj); 
            }
		}
		return $addedGroupContentResponseArr;
	}
    
    public function addUserContent($appUsers, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail, $userSession)
    {
    	$addedFolderContentResponseArr = array();
		foreach($appUsers as $appUser)
		{
			$userId = $appUser->appuser_id;

			//if($userId == 1 || $userId == 4 || $userId == 13 || $userId == 85)
			{
	            $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($appUser, "");   
	            $userConstantDetails = $depMgmtObj->getUserConstantObject();
				
				if(isset($userConstantDetails))
		    	{
		    		$defFolderId = $userConstantDetails->def_folder_id;
		    		$emailSourceId = $userConstantDetails->email_source_id;

					$utcTz =  'UTC';
		    		$createDateObj = Carbon::now($utcTz);
		    		$createTimeStamp = $createDateObj->timestamp;		    		
		    		$createTimeStamp = $createTimeStamp * 1000;
		    		$updateTimeStamp = $createTimeStamp;

		            $contentType = $mainContent->content_type_id;
		           	$folderId = $defFolderId;
		            $sourceId = $emailSourceId;
		            $tagsArr = array();
		            $removeAttachmentIdArr = NULL;
		            $fromTimeStamp = $mainContent->from_timestamp;
		            $toTimeStamp = $mainContent->to_timestamp;
		            $isMarked = $mainContent->is_marked;
		            $colorCode = $mainContent->color_code;
		            $remindBeforeMillis = $mainContent->remind_before_millis;
		            $repeatDuration = $mainContent->repeat_duration;
		            $isCompleted = Config::get('app_config.default_content_is_completed_status');
		            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
		            $reminderTimestamp = $mainContent->reminder_timestamp;

					$appendedContentText = CommonFunctionClass::getSharedByAppendedString($contentText, $createTimeStamp, $sharedByUserName, $sharedByUserEmail);
				            
                	$response = $depMgmtObj->addEditContent(0, $appendedContentText, $contentType, $folderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, "");
                	
                	$newServerContentId = $response['syncId'];
		            
		            $this->addUserContentAttachments($appUser, $newServerContentId, $contentAttachmentsArr);

		            if($newServerContentId > 0)
		            {
		            	$addedFolderContentResponseObj = array();
		            	$addedFolderContentResponseObj['contentId'] = sracEncryptNumberData($newServerContentId, $userSession);
		            	$addedFolderContentResponseObj['isFolder'] = 1;
		            	$addedFolderContentResponseObj['userId'] = sracEncryptNumberData($userId, $userSession);
						$addedFolderContentResponseObj['orgId'] = "";
		            	
		            	array_push($addedFolderContentResponseArr, $addedFolderContentResponseObj); 
		            }

					/* WILL SEND NOTIFICATIONS SEPARATELY */
					/*
		   			$this->sendEntryAddMessageToDevice($userId, $newServerContentId, $sharedByUserEmail);
	                
					MailClass::sendContentAddedMail($userId, $newServerContentId, $sharedByUserEmail);
					MailClass::sendContentDeliveredMail($userId, $sharedByUserEmail);
					*/
					/* WILL SEND NOTIFICATIONS SEPARATELY */
		    	}
			}
		}
		return $addedFolderContentResponseArr;
	}
	
	public function addUserContentAttachments($user, $contentId, $contAttachments)
	{
		$orgId = 0;
		$orgEmpId = 0;
		
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
        
		foreach($contAttachments as $contAttachment)
		{	
            $cloudStorageTypeId = $contAttachment->att_cloud_storage_type_id;
            if($cloudStorageTypeId > 0)
            {
                $serverFileName = '';
            }
            else
            {
           		$serverFileDetails = FileUploadClass::makeAttachmentCopy($contAttachment->server_filename, $orgId);
                $serverFileName = $serverFileDetails['name'];
            }
	        
	        $serverFileSize = $contAttachment->filesize;
			
        	if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
            {
            	$cloudFileUrl = $contAttachment->cloud_file_url;
	            $cloudFileId = $contAttachment->cloud_file_id;
	            $cloudFileThumbStr = $contAttachment->cloud_file_thumb_str;
	            $attCreateTs = $contAttachment->create_ts;
	            $attUpdateTs = $contAttachment->update_ts;

            	$response = $depMgmtObj->addEditContentAttachment(0, $contentId, $contAttachment->filename, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
            }
		}
	}
    
    /**
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getAppuserTemplateList()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $selOrgId = Input::get('selOrgId');
        $isSelectInst = Input::get('isSelect');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "")
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
				
				if(!isset($isSelectInst))
					$isSelectInst = 0;
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{					     
	                $status = 1;
	                
	                $i = 0;
	                $templateList = array();  
	                $selTemplateList = array();   
	                
					$modelObj = New OrgTemplate;
					$modelObj = $modelObj->setConnection($orgDbConName);					
	                $templates = $modelObj->get();	                  
	                foreach ($templates as $template) 
	                {						
						$templateList[$i]['id'] = sracEncryptNumberData($template->template_id, $userSession);
						$templateList[$i]['name'] = $template->template_name;
						$templateList[$i]['text'] = $template->template_text;
						$i++;
						
						$tempObj = array();
						$tempObj["id"] = sracEncryptNumberData($template->template_id, $userSession);
						$tempObj["text"] =  $template->template_name;
						array_push($selTemplateList, $tempObj);
					} 
					$templateCnt = count($templateList);      
					$response["templateCnt"] = $templateCnt;          
					$response["templateArr"] = $templateList;  
					$response["results"] = $selTemplateList;  
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
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getTemplateDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $templateId = Input::get('tempId');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "")
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

                $templateId = sracDecryptNumberData($templateId, $userSession);
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{					     
	                $status = 1;  
	                $templateName = "";
	                $selTemplateText = "";
	                
					$modelObj = New OrgTemplate;
					$modelObj = $modelObj->setConnection($orgDbConName);					
	                $template = $modelObj->byId($templateId)->first();	                  
	                if(isset($template)) 
	                {
						$templateName = $template->template_name;
						$selTemplateText = $template->template_text;
					}        
					$response["name"] = $templateName;  
					$response["text"] = $selTemplateText;  
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
     * report abuse
     *
     * @return json array
     */
    public function appuserOrgReportAbuse()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $abuseReport = Input::get('abuseReport');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "" && $abuseReport != "")
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
		        $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0 && $orgEmpId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
           			
	           		if(isset($orgDbConName))
	           		{					     
		                $status = 1;  
		                MailClass::sendOrgAbuseReportAcknowledgementMail($orgId, $orgEmpId, $abuseReport);
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
     * contact admin
     *
     * @return json array
     */
    public function appuserOrgContactAdmin()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $contactReport = Input::get('contactReport');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "" && $contactReport != "")
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
		        $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0 && $orgEmpId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
           			
	           		if(isset($orgDbConName))
	           		{					     
		                $status = 1;  
		                MailClass::sendOrgContactReportAcknowledgementMail($orgId, $orgEmpId, $contactReport);
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
     * get app user broadcasts.
     *
     * @return json array
     */
    public function getEmployeeDetails()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $response = array();
        
        if($encUserId != "" && $encOrgId != "")
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
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{			            
	                $empModelObj = New OrgEmployee;
	                $empModelObj->setConnection($orgDbConName);
	                $employee = $empModelObj->select(["*", \DB::raw("GROUP_CONCAT(DISTINCT badge_name ORDER BY  badge_name ASC SEPARATOR ', ') as badges")])
			                ->joinDepartmentTable()->joinDesignationTable()->joinBadgeTable()
			                ->byId($orgEmpId)->first();
			                
			        $organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		                
	                if(isset($employee) && isset($organization))
	                {
		                $status = 1;
			                
		                $logoFilename = $organization->logo_filename;
						$orgLogoUrl = "";
						$orgLogoThumbUrl = "";
						if(isset($logoFilename) && $logoFilename != "")
						{
							$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
							$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
						}		            
						
			            $response['orgLogoUrl'] = $orgLogoUrl;
			            $response['orgName'] = $organization->system_name;
			            $response['orgEmail'] = $organization->app_email;
			            $response['orgWebsite'] = $organization->app_website;
			            $response['orgPhone'] = $organization->app_phone;
			            $response['orgDescription'] = $organization->app_description;
		            
		                $usrFieldsArr = array();
		                
		                $empNo = $employee->employee_no;
		                $empName = $employee->employee_name;
		                $deptName = $employee->department_name;
		                $desigName = $employee->designation_name;
		                $empContact = $employee->contact_number;
		                $empAddress = $employee->address;
		                $empEmail = $employee->email;
		                $empBadges = $employee->badges;
		                $empDob = dbToDispDate($employee->dob);
		                
		                if(isset($empNo) && $empNo != "")
		                {
		                	$fieldObj = array();
							$fieldObj['fldTitle'] = 'ID';
							$fieldObj['fldValue'] = $empNo;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empName) && $empName != "")
		                {
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Name';
							$fieldObj['fldValue'] = $empName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($deptName) && $deptName != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Department';
							$fieldObj['fldValue'] = $deptName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($desigName) && $desigName != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Designation';
							$fieldObj['fldValue'] = $desigName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empEmail) && $empEmail != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Email';
							$fieldObj['fldValue'] = $empEmail;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empContact) && $empContact != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Contact';
							$fieldObj['fldValue'] = $empContact;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empAddress) && $empAddress != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Address';
							$fieldObj['fldValue'] = $empAddress;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empDob) && $empDob != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Date of Birth';
							$fieldObj['fldValue'] = $empDob;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empBadges) && $empBadges != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Badges';
							$fieldObj['fldValue'] = $empBadges;					
							array_push($usrFieldsArr, $fieldObj);
						}
									
			            $fldModelObj = New OrgEmployeeFieldValue;
			            $fldModelObj->setConnection($orgDbConName);
			            $empFieldValues = $fldModelObj->ofEmployee($orgEmpId)->get();
			            $arrEmpFieldValue = array();
			            foreach($empFieldValues as $empFldVal)
			            {
							$arrEmpFieldValue[$empFldVal->org_field_id] = $empFldVal->field_value;
						}
						
			            $orgUserFields = OrganizationUserField::ofOrganization($orgId)->active()->orderByPosition()->get();
						foreach($orgUserFields as $usrField)
						{
							$fieldId = $usrField->org_field_id;
							$fieldDispName = $usrField->field_display_name;
												
							if(isset($arrEmpFieldValue[$fieldId]))
								$fieldValue = $arrEmpFieldValue[$fieldId];
							
							if(isset($fieldValue) && $fieldValue != "")
							{
								$fieldObj = array();
								$fieldObj['fldTitle'] = $fieldDispName;
								$fieldObj['fldValue'] = $fieldValue;								
								array_push($usrFieldsArr, $fieldObj);
							}								
						}       
						
						$response["usrFieldsArr"] = $usrFieldsArr;  
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
    
    public function loadSelectProfileModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isLockedFlag = Input::get('isLocked');
		
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
				$selectedOrgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$selectedOrgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
				$isOrgSelected = FALSE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $orgId = $depMgmtObj->getOrganizationId(); 

                $allOrgDepMgmtObjArr = array();
                
                $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();

                $tmpPerDepMgmtObj = New ContentDependencyManagementClass;
                $tmpPerDepMgmtObj->withUserIdOrgIdAndEmpId($user, 0, 0);
                array_push($allOrgDepMgmtObjArr, $tmpPerDepMgmtObj);

                foreach ($userOrganizations as $userOrgIndex => $userOrg) 
                {
                    $tmpOrgId = $userOrg->organization_id;                 
                    $tmpOrganization = $userOrg->organization;
                    if(isset($tmpOrganization)) 
                    {
                        $tempEmpId = $userOrg->emp_id;

                        $tmpOrgDepMgmtObj = New ContentDependencyManagementClass;
                        $tmpOrgDepMgmtObj->withOrgIdAndEmpId($tmpOrgId, $tempEmpId);

                        array_push($allOrgDepMgmtObjArr, $tmpOrgDepMgmtObj);
                    }
                }

                $orgForLogs = array();    

                $orgList = array();          
                $arrForSorting = array();
                foreach ($allOrgDepMgmtObjArr as $depMgmtIndex => $orgDepMgmtObj)
                {
                    $depMgmtObjOrgId = $orgDepMgmtObj->getOrganizationId(); 
                    $depMgmtObjOrgEmpId = $orgDepMgmtObj->getOrganizationEmployeeId(); 
                    $consOrgEmployee = $orgDepMgmtObj->getPlainEmployeeObject();
                    $consOrganization = $orgDepMgmtObj->getOrganizationObject();

                    $orgLog = array();
                    $orgLog['OrgId'] = $depMgmtObjOrgId;
                    $orgLog['OrgEmpId'] = $depMgmtObjOrgEmpId;

					if(($depMgmtObjOrgId == 0 && isset($user) && $user->is_active == 1) || ($depMgmtObjOrgId > 0 && isset($consOrgEmployee) && $consOrgEmployee->is_active == 1 && $consOrgEmployee->has_web_access == 1))
					{
		                $orgIsSelected = 0;
		                if($selectedOrgId == $depMgmtObjOrgId && $selectedOrgEmpId == $depMgmtObjOrgEmpId)
		                {
		                	$orgIsSelected = 1;
		                	if($selectedOrgId > 0)
		                	{
		                		$isOrgSelected = TRUE;
		                	}
						}
	                    
                        $orgUserEmpConstants = $orgDepMgmtObj->getEmployeeOrUserConstantObject();

						$orgAllottedKbs = 0;
						$orgAvailableKbs = 0;
						
	                    if(isset($orgUserEmpConstants) && $orgUserEmpConstants->attachment_kb_allotted != null)  
	                        $orgAllottedKbs = $orgUserEmpConstants->attachment_kb_allotted;  
	                    if(isset($orgUserEmpConstants) && $orgUserEmpConstants->attachment_kb_available != null)  
	                        $orgAvailableKbs = $orgUserEmpConstants->attachment_kb_available; 
	                        
	                    $orgUtilizedKbs = $orgAllottedKbs - $orgAvailableKbs;
						$orgUtilizedMbs = floor($orgUtilizedKbs/1024); // ceil($orgUtilizedKbs/1024); // round($orgUtilizedKbs/1024, 2); 

                    	$orgLog['orgAllottedKbs'] = $orgAllottedKbs;
                    	$orgLog['orgAvailableKbs'] = $orgAvailableKbs;

	                    $orgLockedFolderIdArr = array();
	                    if(isset($isLockedFlag) && $isLockedFlag == 1) {
	                        
	                        $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
	                        if(isset($orgUserEmpConstants))
	                        {
	                            $hasFolderPasscode = $orgUserEmpConstants->folder_passcode_enabled;
	                            $folderIdStr = $orgUserEmpConstants->folder_id_str;
	                            if($hasFolderPasscode == 1 && $folderIdStr != null ) 
	                            {
	                                $orgLockedFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
	                            }
	                        }
	                    }

		                $lockedAndSentFolderArr = $orgLockedFolderIdArr;
		                // $sentFolderId = $orgDepMgmtObj->getSentFolderId();
		                // if(isset($sentFolderId) && $sentFolderId > 0)
		                // {
		                //     array_push($lockedAndSentFolderArr, $sentFolderId);
		                // }

                    	$orgLog['orgLockedFolderIdArr'] = $orgLockedFolderIdArr;

                    	$allContentCnt = 0;

	                    $forAllContent = $orgDepMgmtObj->getAllContentModelObj(TRUE)->filterExceptRemoved();
	                    $forAllContent = $forAllContent->filterExceptFolder($lockedAndSentFolderArr);
	                    $allContentCnt += $forAllContent->count();

	                    $forAllContent = $orgDepMgmtObj->getAllContentModelObj(FALSE);
	                    $allContentCnt += $forAllContent->count();

	                    $consUserIsActive = 0;
	                    $consUserEmail = "";
	                    $consUserName = "";
	                    $consUserNo = "";
	                    $orgLogoUrl = "";
	                    $orgName = "";
	                    if($depMgmtObjOrgId > 0)
	                    {
	                    	$consUserIsActive = $consOrgEmployee->is_active;
	                    	$consUserEmail = $consOrgEmployee->email;
	                    	$consUserName = $consOrgEmployee->employee_name;
	                    	$consUserNo = $consOrgEmployee->employee_no;
	                    	$orgName = $consOrganization->system_name;
					    
		                	$logoFilename = $consOrganization->logo_filename;
		                	$orgLogoUrl = "";
							if(isset($logoFilename) && $logoFilename != "")
							{
								$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($depMgmtObjOrgId, $logoFilename);
							}
	                    }
	                    else
	                    {
	                    	$consUserIsActive = $user->is_active;
	                    	$consUserEmail = $user->email;
	                    	$consUserName = $user->fullname;

							$assetBasePath = Config::get('app_config.assetBasePath');
							$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
							$orgLogoUrl = $baseIconThemeUrl.Config::get('app_config_asset.appIconDefaultUserProfilePath');
				
							$isPremiumStr = "";
							if($user->is_premium == 1)
							{
								$isPremiumStr = " - Business";
							}

	                    	$orgName = "Personal".$isPremiumStr;
	                    }

						$orgMetricStr = $allContentCnt.' Notes - '.$orgUtilizedMbs.' MB utilized';

						$currEncOrgId = $depMgmtObjOrgId > 0 ? Crypt::encrypt($depMgmtObjOrgId."_".$depMgmtObjOrgEmpId) : ""; 

		                $actOrg = New OrganizationUser;
		                $actOrg->id = $currEncOrgId;
		                $actOrg->is_selected = $orgIsSelected;
		                $actOrg->name = $orgName;
		                $actOrg->url = $orgLogoUrl;
		                $actOrg->user_no = $consUserNo;
		                $actOrg->user_name = $consUserName;
	                	$actOrg->user_email = $consUserEmail;
	                	$actOrg->user_status = $consUserIsActive;
                		$actOrg->metrics_Str = $orgMetricStr;
	                
	                	array_push($arrForSorting, $orgName);				
	                	array_push($orgList, $actOrg);
					}    

                    $orgForLogs[$depMgmtIndex] = $orgLog;             
                }

                $viewDetails['organizations'] = $orgList;
                $viewDetails['isOrgSelected'] = $isOrgSelected;
           
	            $_viewToRender = View::make('content.partialview._selectProfileModal', $viewDetails);
	            $_viewToRender = $_viewToRender->render();
	            
	            $response['view'] = $_viewToRender;
	            $response['orgForLogs'] = $orgForLogs; 
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
     * Profile List.
     *
     * @return json array
     */
    public function loadSelectProfileList()
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
                
                $profileArr = array();
                $arrForSorting = array();

				$selectedOrgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$selectedOrgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
				$userProfiles = OrganizationUser::ofUserEmail($user->email)->verified()->get();
                foreach ($userProfiles as $userOrg) 
                {
                	$orgId = $userOrg->organization_id;
                	{
						$organization = $userOrg->organization;	
						if(isset($organization)) 
						{
							$orgName = $organization->system_name;
							$orgCode = $organization->org_code;
		                	$empId = $userOrg->emp_id;
		                	
							$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);					
							$modelObj = New OrgEmployee;
			                $modelObj->setConnection($orgDbConName);
			                $orgEmployee = $modelObj->byId($empId)->first();
			                
			                if($orgEmployee->is_active == 1)
		                	{
			                	$empname = $orgEmployee->employee_name;

			                	$orgIsSelected = 0;
				                if($selectedOrgId == $orgId && $selectedOrgEmpId == $empId)
				                {
				                	$orgIsSelected = 1;
								}

								if($orgIsSelected == 0)
								{
									$profileName = $orgCode." - ".$empname;
									$encOrgId = Crypt::encrypt($orgId."_".$empId);
									
									$profileObj = array();
									$profileObj["id"] = $encOrgId;
									$profileObj["text"] = $profileName;
									array_push($profileArr, $profileObj);
									array_push($arrForSorting, $profileName);
								}
							}
						}						
					}                	
                }
                array_multisort($arrForSorting, $profileArr);  

				if($selectedOrgId > 0 && $selectedOrgEmpId > 0)
				{
					$profileObj = array();
					$profileObj["id"] = " ";
					$profileObj["text"] = "Retail";
					array_unshift($profileArr, $profileObj);
				} 

				$response = array('results' => $profileArr );
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
    
    public function loadProfileSettingsModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isLockedFlag = Input::get('isLocked');
		
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
                $folderName = "";
                $quotaStr = "";
                $printFieldStr = "";
                $noteCount = 0;
                $defFolderId = 0;
                
                $lastSyncTs = $userSession->last_sync_ts;
                $lastSyncStr = dbToLongDispDateTime($lastSyncTs);
                
	            $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);   
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $depMgmtObj->recalculateUserQuota(TRUE);
	            $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject(); 
                $orgLockedFolderIdArr = array();

                $appHasCloudStorage = $depMgmtObj->getAppKeyMappingHasCloudStorage();
                $appHasTypeReminder = $depMgmtObj->getAppKeyMappingHasTypeReminder();
                $appHasTypeCalendar = $depMgmtObj->getAppKeyMappingHasTypeCalendar();
                $appHasVideoConference = $depMgmtObj->getAppKeyMappingHasVideoConference();
                $appHasSourceSelection = $depMgmtObj->getAppKeyMappingHasSourceSelection();
                $appHasFolderSelection = $depMgmtObj->getAppKeyMappingHasFolderSelection();
                $appHasImportOptions = $depMgmtObj->getAppKeyMappingHasImportOptions();
                $appHasIntegrationOptions = $depMgmtObj->getAppKeyMappingHasIntegrationOptions();
	            
	            $onlyUserObject = $depMgmtObj->getUserConstantObject();
				if(isset($userConstant))
		    	{
		    		$defFolderId = $userConstant->def_folder_id;
		    		$folderObj = $depMgmtObj->getFolderObject($defFolderId);
		    		if(isset($folderObj))
		    			$folderName = $folderObj->folder_name; 
		    		
		    		$printFieldIdArr = array();	
                    if($onlyUserObject->print_fields != null) 
                    {
                		$passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                        $printFieldIdStr = $onlyUserObject->print_fields;
                        $printFieldIdArr = explode($passcodeFolderIdDelimiter, $printFieldIdStr);
                    }
                    		    		
	                $printFields = PrintField::orderBy('field_name', 'ASC')->get();
	                foreach ($printFields as $field) 
	                {
	                    $fieldId = $field->print_field_id;
	                    $fieldName = $field->field_name;
	                    
	                    $printFieldStr .= "<li>";
	                    $printFieldStr .= "&nbsp;&nbsp;$fieldName";
	                    if(in_array($fieldId, $printFieldIdArr))
	                    	$printFieldStr .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class='fa fa-check'></i>";
	                    $printFieldStr .= "</li>";
	                } 
		    			
		    		$allottedKb = $userConstant->attachment_kb_allotted;
		    		$availableKb = $userConstant->attachment_kb_available;
		    		$usedKb = $userConstant->attachment_kb_used;
		    		
		    		$availableMb = ceil($availableKb/1024);
		    		$allottedMb = ceil($allottedKb/1024);
		    		$usedMb = floor($usedKb/1024); // ceil($usedKb/1024);
		    			
		    		// $quotaStr = "<b>".$availableMb." MB</b> remaining of your <b>".$allottedMb." MB</b> space limit.";

		    		$quotaStr = "<b>".$usedMb." MB</b> used of your <b>".$allottedMb." MB</b> space limit.";
		    		
	                if(isset($isLockedFlag) && $isLockedFlag == 1) {
	                    
	                    $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
	                    if(isset($userConstant))
	                    {
	                        $hasFolderPasscode = $userConstant->folder_passcode_enabled;
	                        $folderIdStr = $userConstant->folder_id_str;
	                        if($hasFolderPasscode == 1 && $folderIdStr != null ) 
	                        {
	                            $orgLockedFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
	                        }
	                    }
	                }
				}

                $lockedAndSentFolderArr = $orgLockedFolderIdArr;
                // $sentFolderId = $depMgmtObj->getSentFolderId();
                // if(isset($sentFolderId) && $sentFolderId > 0)
                // {
                //     array_push($lockedAndSentFolderArr, $sentFolderId);
                // }

            	$allContentCnt = 0;

                $forAllContent = $depMgmtObj->getAllContentModelObj(TRUE)->filterExceptRemoved();
                $forAllContent = $forAllContent->filterExceptFolder($lockedAndSentFolderArr);
                $allContentCnt += $forAllContent->count();

                $forAllContent = $depMgmtObj->getAllContentModelObj(FALSE);
                $allContentCnt += $forAllContent->count();
                
                if($folderName == "")
                	$folderName = "-";

                $cloudStorageTypeList = $depMgmtObj->getAllCloudStorageTypeListForUser();
                
				$viewDetails = array();
                $viewDetails['folderId'] = $defFolderId;
                $viewDetails['folderName'] = $folderName;
                $viewDetails['quotaStr'] = $quotaStr;
                $viewDetails['noteCount'] = $allContentCnt;
                $viewDetails['lastSyncStr'] = $lastSyncStr;
                $viewDetails['printFieldStr'] = $printFieldStr;
                    
                $viewDetails['appHasCloudStorage'] = $appHasCloudStorage;
                $viewDetails['appHasTypeReminder'] = $appHasTypeReminder;
                $viewDetails['appHasTypeCalendar'] = $appHasTypeCalendar;
                $viewDetails['appHasVideoConference'] = $appHasVideoConference;
                $viewDetails['appHasSourceSelection'] = $appHasSourceSelection;
                $viewDetails['appHasFolderSelection'] = $appHasFolderSelection;
                $viewDetails['appHasImportOptions'] = $appHasImportOptions;
                $viewDetails['appHasIntegrationOptions'] = $appHasIntegrationOptions;

                $viewDetails['cloudStorageTypeList'] = $cloudStorageTypeList;
           
	            $_viewToRender = View::make('content.partialview._profileSettingsModal', $viewDetails);
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
    
    public function loadAppuserInformationModal()
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
                $depMgmtObj->withOrgKey($user, ''); 
                $user = $depMgmtObj->getUserObject();


                $profilePhotoUrl = "";
                $profilePhotoThumbUrl = "";
                if(isset($user))
                {
                	$photoFilename = $user->img_server_filename;

                	if(isset($photoFilename) && $photoFilename != "")
	                {
	                    $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
	                    $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
	                }
                }	                
                
				$viewDetails = array();
            	$viewDetails['user'] = $user;
                $viewDetails["photoUrl"] = $profilePhotoUrl;
                $viewDetails["photoThumbUrl"] = $profilePhotoThumbUrl;
                
	            $_viewToRender = View::make('content.partialview._userInformationModal', $viewDetails);
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
    
    public function loadAppuserOrgInformationModal()
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
				
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{				
	                $empModelObj = New OrgEmployee;
	                $empModelObj->setConnection($orgDbConName);
	                $employee = $empModelObj->select(["*", \DB::raw("GROUP_CONCAT(DISTINCT badge_name ORDER BY  badge_name ASC SEPARATOR ', ') as badges")])
			                ->joinDepartmentTable()->joinDesignationTable()->joinBadgeTable()
			                ->byId($orgEmpId)->first();
			        
			        $organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		                
	                if(isset($organization) && isset($employee))
	                {
		                $status = 1;
		                
		                $logoFilename = $organization->logo_filename;
						$orgLogoUrl = "";
						$orgLogoThumbUrl = "";
						if(isset($logoFilename) && $logoFilename != "")
						{
							$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
							$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
						}		            
						
						$viewDetails = array();
			            $viewDetails['orgLogoUrl'] = $orgLogoUrl;
			            $viewDetails['orgName'] = $organization->system_name;
			            $viewDetails['orgEmail'] = $organization->app_email;
			            $viewDetails['orgWebsite'] = $organization->app_website;
			            $viewDetails['orgPhone'] = $organization->app_phone;		                
			            $viewDetails['orgDescription'] = $organization->app_description;
			            
		                $usrFieldsArr = array();
		        
		                $empNo = $employee->employee_no;
		                $empName = $employee->employee_name;
		                $deptName = $employee->department_name;
		                $desigName = $employee->designation_name;
		                $empContact = $employee->contact;
		                $empAddress = $employee->address;
		                $empEmail = $employee->email;
		                $empBadges = $employee->badges;
		                $empDob = dbToDispDate($employee->dob);
		                
		                if(isset($empNo) && $empNo != "")
		                {
		                	$fieldObj = array();
							$fieldObj['fldTitle'] = 'ID';
							$fieldObj['fldValue'] = $empNo;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empName) && $empName != "")
		                {
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Name';
							$fieldObj['fldValue'] = $empName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($deptName) && $deptName != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Department';
							$fieldObj['fldValue'] = $deptName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($desigName) && $desigName != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Designation';
							$fieldObj['fldValue'] = $desigName;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empEmail) && $empEmail != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Email';
							$fieldObj['fldValue'] = $empEmail;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empContact) && $empContact != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Contact';
							$fieldObj['fldValue'] = $empContact;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empAddress) && $empAddress != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Address';
							$fieldObj['fldValue'] = $empAddress;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empDob) && $empDob != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Date of Birth';
							$fieldObj['fldValue'] = $empDob;					
							array_push($usrFieldsArr, $fieldObj);
						}
		                
		                if(isset($empBadges) && $empBadges != "")
		                {			                
			                $fieldObj = array();
							$fieldObj['fldTitle'] = 'Badges';
							$fieldObj['fldValue'] = $empBadges;					
							array_push($usrFieldsArr, $fieldObj);
						}
									
			            $fldModelObj = New OrgEmployeeFieldValue;
			            $fldModelObj->setConnection($orgDbConName);
			            $empFieldValues = $fldModelObj->ofEmployee($orgEmpId)->get();
			            $arrEmpFieldValue = array();
			            foreach($empFieldValues as $empFldVal)
			            {
							$arrEmpFieldValue[$empFldVal->org_field_id] = $empFldVal->field_value;
						}
						
			            $orgUserFields = OrganizationUserField::ofOrganization($orgId)->active()->orderByPosition()->get();
						foreach($orgUserFields as $usrField)
						{
							$fieldId = $usrField->org_field_id;
							$fieldDispName = $usrField->field_display_name;
												
							if(isset($arrEmpFieldValue[$fieldId]))
								$fieldValue = $arrEmpFieldValue[$fieldId];
							
							if(isset($fieldValue) && $fieldValue != "")
							{
								$fieldObj = array();
								$fieldObj['fldTitle'] = $fieldDispName;
								$fieldObj['fldValue'] = $fieldValue;								
								array_push($usrFieldsArr, $fieldObj);
							}								
						} 
						
			            $viewDetails['usrFieldsArr'] = $usrFieldsArr;
			       
			            $_viewToRender = View::make('content.partialview._userOrgInformationModal', $viewDetails);
			            $_viewToRender = $_viewToRender->render();
			            
			            $response['view'] = $_viewToRender;
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
	
    public function appuserOrgLeave()
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
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                if($orgId > 0)
                {
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);   
                    $employee = $depMgmtObj->getEmployeeObject();
                    
                    if(isset($employee))
                    {
                        $status = 1;	                
                        $depMgmtObj->orgEmployeeLeave();
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
	
	public function appuserOrgScreenshotTaken()
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
				
				$depMgmtObj = New ContentDependencyManagementClass;
				$depMgmtObj->withOrgKey($user, $encOrgId);  

				$orgId = $depMgmtObj->getOrganizationId(); 
				if($orgId > 0)
				{
					$employee = $depMgmtObj->getEmployeeObject();

					if(isset($employee))
					{
						$status = 1;

						$orgEmpId = $depMgmtObj->getOrganizationEmployeeId(); 
		                MailClass::sendOrgSystemScreenshotTakenMail($orgId, $orgEmpId);
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
     * Register app user.
     *
     * @return json array
     */
    public function subscribeAppuserOrganizationViaLink()
    {
        $msg = "";
        $status = 0;

        $encUserOrganizationId = Input::get('uo');

        $response = array();

        if($encUserOrganizationId != "")
        {
            $decParts = Crypt::decrypt($encUserOrganizationId);
        	$parts = explode("_",$decParts);
        	if(count($parts) > 3)
        	{
        		$empEmail = $parts[1];
        		$orgCode = $parts[2];
        		$verCode = $parts[3];
				
				$organization = Organization::byCode($orgCode)->first();	
				
				if(isset($organization))
				{
					$orgId = $organization->organization_id;
					$isSelfEnroll = $organization->org_self_enroll_enabled;
					$selfEnrollVerCode = $organization->self_enroll_verification_code;			
				
					$orgUsers = OrganizationUser::ofEmpEmail($empEmail)->ofOrganization($orgId)->get();	            	
	            	if(isset($orgUsers) && count($orgUsers) > 0)
					{
						foreach($orgUsers as $orgUser)
						{
							$isVerified = $orgUser->is_verified;	
							$verificationCode = $orgUser->verification_code;
				            $orgEmpId = $orgUser->emp_id;
    
					        $depMgmtObj = New ContentDependencyManagementClass;
					       	$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
					        $isActive = $depMgmtObj->getEmployeeIsActive();
				            
							if($isVerified == 0 && $verificationCode != "" && $isActive == 1)
							{
								$decVerCode = Crypt::decrypt($verificationCode);
			                    if($decVerCode == $verCode)
			                    {    
									$userFromEmail = Appuser::ofEmail($empEmail)->first();
				                    	
					       			$orgEmployee = $depMgmtObj->getEmployeeObject();

									$user = null;
									if(isset($userFromEmail) && $userFromEmail->is_active == 1)
									{
										$user = $userFromEmail;
									}
									elseif(!isset($userFromEmail))
									{
						                $genPassword = CommonFunctionClass::generateAppuserPassword();
						                $encPassword = Hash::make($genPassword);
               							$fullname = $orgEmployee->employee_name;
               							$contact = $orgEmployee->contact;
               							$gender = $orgEmployee->gender;
               							$country = "";
               							$city = "";
										
										// create new user
						                $userData = array();
						                $userData['email'] = $empEmail;
						                $userData['password'] = $encPassword;
						                $userData['fullname'] = $fullname;
						                $userData['contact'] = $contact;
						                $userData['gender'] = $gender;
						                $userData['country'] = "";
						                $userData['city'] = "";
						                $userData['ref_code'] = "";
						                $userData['is_verified'] = 1;
						                $userData['is_app_registered'] = Appuser::$_IS_EMAIL_REGISTERED;
						                $userData['is_active'] = 1;
						                $userData['is_premium'] = 0;
						                $userData['has_referral'] = 0;
						                $userData['has_coupon'] = 0;

						                $user = Appuser::create($userData);

                						$userId = $user->appuser_id;

					                    //Create default folder(s) & tag(s)
            							$depMgmtObj->setAppuserDefaultParamsPostVerification($userId);

					                    //Send message for welcome
					                    MailClass::sendWelcomeMail($userId);

					                    //Send message for password
					                    MailClass::sendSocialLoginPasswordIntimationMail($userId, $genPassword);
									}	  

									if(isset($user))
									{
										$userEmail = $user->email;

										$status = 1;
				                    	
				                    	$orgUser->verification_code = '';
				                        $orgUser->is_verified = 1;
				                        $orgUser->appuser_email = $userEmail;
				                        $orgUser->save();
				                        
				                        //Create default folder(s) & tag(s)
				                        OrganizationClass::setEmployeeDefaultParams($orgId, $orgEmpId);

				                        //Send message for welcome
				                        MailClass::sendOrgSubscribedMail($orgId, $orgEmpId);
							
										$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
										$modelObj = New OrgEmployee;
						                $modelObj->setConnection($orgDbConName);
						                $orgEmployee = $modelObj->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
						                
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

										$orgInactivityDayCount = isset($organization->employee_inactivity_day_count) && !is_nan($organization->employee_inactivity_day_count) ? $organization->employee_inactivity_day_count : -1;
	        							$orgAttachmentRetainDays = $organization->org_attachment_retain_days;

            							$isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $orgEmpId);
                						$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $orgEmpId);
				                        
										$encOrgId = Crypt::encrypt($orgId."_".$orgEmpId);
				                        $orgDetails = array();
					                	$orgDetails['key'] = $encOrgId;
					                	$orgDetails['map_key'] = $orgEmployee->org_emp_key;
					                	$orgDetails['code'] = $organization->org_code;
					                	$orgDetails['reg_name'] = $organization->regd_name;
					                	$orgDetails['sys_name'] = $organization->system_name;
	            						$orgDetails['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
	            						$orgDetails['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
	                					$orgDetails['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
	            						$orgDetails['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
					                	$orgDetails['user_no'] = $orgEmployee->employee_no;
					                	$orgDetails['user_name'] = $orgEmployee->employee_name;
	            						$orgDetails['user_email'] = $orgEmployee->email;
					                	$orgDetails['user_department'] = $orgEmployee->department_name;
					                	$orgDetails['user_designation'] = $orgEmployee->designation_name;
					                	$orgDetails['user_status'] = $isActive;
	                					$orgDetails['logo_url'] = $orgLogoUrl;
	                					$orgDetails['logo_thumb_url'] = $orgLogoThumbUrl;
	                					$orgDetails['logo_filename'] = $logoFilename;
	    								$orgDetails['org_attachment_retain_days'] = $orgAttachmentRetainDays;
	    								$orgDetails['org_inactivity_day_count'] = $orgInactivityDayCount;
										
										$response['orgDetails'] = $orgDetails;

										$loginToken = null;
										
										$this->sendOrgSubscribedMessageToDevice($orgEmpId, $orgId, $loginToken, $orgDetails);
									}
							            
						            break;
										
								}
								else
								{
									$status = -1;
				                	$msg = "Invalid Credentials"; 
								}
							}
						}
						
						if($status != 1)
						{
							$status = -1;
		                	$msg = "User Not Eligible For Organization";    
						}
			        }
					else
		            {
		                $status = -1;
		                $msg = "User Not Eligible For Organization";       
		            }
										
				}
				else
	            {
	                $status = -1;
	                $msg = "No Such Organization";       
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

        if($status > 0)
        {
            $url = Config::get("app_config.url_appuser_hylyt_web_login");
            return Redirect::to($url);
        }
        else
        {
	        //return Response::json($response);
	        print_r("<h1>");
	        print_r($msg);        
	        print_r("</h1>");
        }
    }
    
    /**
     * Subscribe to organization.
     *
     * @return json array
     */
    public function loadOrganizationDetailsForCnameMapping()
    {
        $msg = "";
        $status = 0;
        
        $orgCode = Input::get('orgCode');
        
        $response = array();

        if($orgCode != "")
        {				
			$organization = Organization::byCode($orgCode)->first();				
			
			if(isset($organization) && $organization->is_active == 1)
			{
				$status = 1;

				$orgId = $organization->organization_id;

    			$orgLogoUrl = "";
    			$orgLogoThumbUrl = "";
				$logoFilename = $organization->logo_filename;
    			if(isset($logoFilename) && $logoFilename != "")
    			{
					$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
					$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
				}

				$orgDetails = array();
        		$orgDetails['orgCode'] = $orgCode;	
            	$orgDetails['orgRegName'] = $organization->regd_name;
            	$orgDetails['orgSysName'] = $organization->system_name;
				$orgDetails['orgLogoUrl'] = $orgLogoUrl;
				$orgDetails['orgLogoThumbUrl'] = $orgLogoThumbUrl;			
        		
        		$response['orgDetails'] = $orgDetails;	
			}
			else
            {
                $status = -1;
                $msg = "No Such Organization";       
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
     * Subscribe to organization.
     *
     * @return json array
     */
    public function loadWLAppDetailsForCnameMapping()
    {
        $msg = "";
        $status = 0;
        
        $appCode = Input::get('appCode');
        
        $response = array();

        if($appCode != "")
        {				
			$appKeyMapping = AppKeyMapping::active()->byAppCode($appCode)->first();				
			
			if(isset($appKeyMapping))
			{
				$status = 1;

				$appDetails = array();
        		$appDetails['appCode'] = $appCode;	
            	$appDetails['appName'] = $appKeyMapping->app_name;
            	$appDetails['appKey'] = $appKeyMapping->app_key;
            	$appDetails['appLogoUrl'] = $appKeyMapping->app_logo_full_url;
				$appDetails['appLogoThumbUrl'] = $appKeyMapping->app_logo_thumb_url;	

                $appFeatureSettings = array();  
                $appFeatureSettings['baseRedirectionCode'] = isset($appKeyMapping->baseRedirection) ? $appKeyMapping->baseRedirection->redirection_code : '';
                $appFeatureSettings['hasSocialLogin'] = $appKeyMapping->has_social_login;
                $appFeatureSettings['defThemeName'] = $appKeyMapping->def_theme_name;
                $appFeatureSettings['hasThemeOption'] = $appKeyMapping->has_theme_option;
                $appFeatureSettings['hasImportOptions'] = $appKeyMapping->has_import_options;
                $appFeatureSettings['hasIntegrationOptions'] = $appKeyMapping->has_integration_options;
                $appFeatureSettings['hasCloudStorage'] = $appKeyMapping->has_cloud_storage;
                $appFeatureSettings['hasTypeReminder'] = $appKeyMapping->has_type_reminder;
                $appFeatureSettings['hasTypeCalendar'] = $appKeyMapping->has_type_calendar;	
                $appFeatureSettings['hasVideoConference'] = $appKeyMapping->has_video_conference;		

        		$response['appDetails'] = $appDetails;
        		$response['appFeatureSettings'] = $appFeatureSettings;
			}
			else
            {
                $status = -1;
                $msg = "No Such Organization";       
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
