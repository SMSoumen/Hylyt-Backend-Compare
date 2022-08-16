<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserConstant;
use App\Models\Api\PrintField;
use App\Models\Api\CloudStorageType;
use App\Models\Api\CloudCalendarType;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
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
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use URL;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{ 
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;  
    
    public function __construct()
    {
        
    }
    
    /**
     * Sync All Content List.
     *
     * @return json array
     */
    public function loginDataSync()
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
                
                $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
				  
                $status = 1; 
				
				$refId = 1;
				$orgList = array();
				$orgArr = array();
				$perAttachmentSize = 0;
				$orgAttachmentSize = array();
	            $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
	            foreach ($userOrganizations as $userOrg) 
                {
                	$organization = $userOrg->organization;
                	
                	if(isset($organization)) {
	                	$orgDetails = array();
	                	$orgId = $userOrg->organization_id;
	                	$empEmail = $userOrg->emp_email;
	                	$empId = $userOrg->emp_id;
                	
	                	$orgAttachmentSize[$orgId] = 0;
	                	
						$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
	                	
						$modelObj = New OrgEmployee;
		                $modelObj->setConnection($orgDbConName);
		                $orgEmployee = $modelObj->select(['*', 'org_employees.is_active as emp_is_active'])->joinDepartmentTable()->joinDesignationTable()->byId($empId)->first();
		                
						$logoFilename = $organization->logo_filename;
						$orgLogoUrl = "";
						$orgLogoThumbUrl = "";
						if(isset($logoFilename) && $logoFilename != "")
						{
							$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
							$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
						}

						$orgInactivityDayCount = $organization->employee_inactivity_day_count;
                		$orgAttachmentRetainDays = $organization->org_attachment_retain_days;

                		$isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $empId);
                		$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $empId);

						$encOrgId = Crypt::encrypt($orgId."_".$empId);
	                	$orgDetails['key'] = $encOrgId;
	                	$orgDetails['map_key'] = $orgEmployee->org_emp_key;
						$orgDetails['code'] = $organization->org_code;
	                	$orgDetails['reg_name'] = $organization->regd_name;
	                	$orgDetails['sys_name'] = $organization->system_name;
	                	$orgDetails['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
	                	$orgDetails['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
	                	$orgDetails['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
	                	$orgDetails['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
	                	$orgDetails['ref_id'] = $refId;
	                	$orgDetails['logo_url'] = $orgLogoUrl;
	                	$orgDetails['logo_thumb_url'] = $orgLogoThumbUrl;
	                	$orgDetails['logo_filename'] = $logoFilename;
	                	$orgDetails['user_no'] = $orgEmployee->employee_no;
	                	$orgDetails['user_name'] = $orgEmployee->employee_name;
	                	$orgDetails['user_email'] = $orgEmployee->email;
	                	$orgDetails['user_department'] = isset($orgEmployee->department_name) ? $orgEmployee->department_name : "";
	                	$orgDetails['user_designation'] = isset($orgEmployee->designation_name) ? $orgEmployee->designation_name : "";
	                	$orgDetails['user_status'] = $orgEmployee->emp_is_active;
            			$orgDetails['org_attachment_retain_days'] = $orgAttachmentRetainDays;
            			$orgDetails['org_inactivity_day_count'] = $orgInactivityDayCount;
	                	
	                	$modelObj = New OrgEmployeeConstant;
	                	$modelObj = $modelObj->setConnection($orgDbConName);
	                	$orgEmpConstant = $modelObj->ofEmployee($empId)->first();
	                	
	                	if(isset($orgEmpConstant))
	                	{  
			                $folderPasscode = "";  
			                $passcodeFolderIdArr = array();  
			                $attachmentSpaceAllotted = 0;
			                $attachmentSpaceAvailable = 0;
			                $attachmentSpaceUsed = 0;
			                $dbSize = 0;
	                
							$defFolderId = ($orgEmpConstant->def_folder_id);
		                    $emailSourceId = ($orgEmpConstant->email_source_id);  
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
		                        $folderPasscode = Crypt::decrypt($orgEmpConstant->folder_passcode);   
		                    if($orgEmpConstant->folder_id_str != null) 
		                    {
		                        $folderIdStr = $orgEmpConstant->folder_id_str;
		                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
		                    }  
		                    if($orgEmpConstant->attachment_kb_allotted != null)  
		                        $attachmentSpaceAllotted = $orgEmpConstant->attachment_kb_allotted;  
		                    if($orgEmpConstant->attachment_kb_available != null)  
		                        $attachmentSpaceAvailable = $orgEmpConstant->attachment_kb_available;  
		                    if($orgEmpConstant->attachment_kb_used != null)  
		                        $attachmentSpaceUsed = $orgEmpConstant->attachment_kb_used; 
		                    if($orgEmpConstant->db_size != null)  
		                        $dbSize = $orgEmpConstant->db_size;

							$orgDetails['defFolderId'] = sracEncryptNumberData($defFolderId, $userSession);
			                $orgDetails['emailSourceId'] = sracEncryptNumberData($emailSourceId, $userSession);
			                $orgDetails['hasFolderPasscode'] = $hasFolderPasscode;    
			                $orgDetails['folderPasscode'] = $folderPasscode;  
			                $orgDetails['folderIdArr'] = sracEncryptNumberArrayData($passcodeFolderIdArr, $userSession);   
			                $orgDetails['attachmentSpaceAllotted'] = $attachmentSpaceAllotted;      
			                $orgDetails['attachmentSpaceAvailable'] = $attachmentSpaceAvailable; 
			                $orgDetails['attachmentSpaceUsed'] = $attachmentSpaceUsed;
			                $orgDetails['dbSize'] = $dbSize;                
			                $orgDetails['sracShareEnabled'] = $sracShareEnabled;
			                $orgDetails['sracOrgShareEnabled'] = $sracOrgShareEnabled;
				            $orgDetails['sracRetailShareEnabled'] = $sracRetailShareEnabled;
				            $orgDetails['sracCopyToProfileEnabled'] = $sracCopyToProfileEnabled;
			                $orgDetails['socShareEnabled'] = $socShareEnabled;
			                $orgDetails['socFacebookEnabled'] = $socFacebookEnabled;
			                $orgDetails['socTwitterEnabled'] = $socTwitterEnabled;
			                $orgDetails['socLinkedinEnabled'] = $socLinkedinEnabled;
			                $orgDetails['socWhatsappEnabled'] = $socWhatsappEnabled;
			                $orgDetails['socEmailEnabled'] = $socEmailEnabled;
			                $orgDetails['socSmsEnabled'] = $socSmsEnabled;
			                $orgDetails['socOtherEnabled'] = $socOtherEnabled;
	                	
		                	$templateArr = array();
		                	$modelObj = New OrgTemplate;
		                	$modelObj = $modelObj->setConnection($orgDbConName);
		                	$orgTemplates = $modelObj->get();
		                	foreach($orgTemplates as $orgTemplate)
		                	{
								$tempDetails = array();
								$tempDetails['id'] = sracEncryptNumberData($orgTemplate->template_id, $userSession);
								$tempDetails['text'] = $orgTemplate->template_text;
								
								array_push($templateArr, $tempDetails);
							}
		                	$orgDetails['templateCnt'] = count($templateArr);
		                	$orgDetails['templateArr'] = $templateArr;
		                	
		                	array_push($orgList, $orgDetails);
		                	array_push($orgArr, array('id'=>$orgId, 'empId'=>$empId, 'refId'=>$refId++));
						}
					}
				}
				$orgCnt = count($orgList); 

                $userConstants = AppuserConstant::ofUser($userId)->first();
                
                $defFolderId = 0;
                $emailSourceId = 0;
                $hasPasscode = 0;    
                $passcode = "";  
                $hasFolderPasscode = 0;    
                $folderPasscode = "";  
                $passcodeFolderIdArr = array();  
                $printFieldIdArr = array();
                $attachmentSpaceAllotted = 0;
                $attachmentSpaceAvailable = 0;
                $attachmentSpaceUsed = 0;
                $dbSize = 0;
                
                $sracShareEnabled = 0;
                $socShareEnabled = 0;
                $socFacebookEnabled = 0;
                $socTwitterEnabled = 0;
                $socLinkedinEnabled = 0;
                $socWhatsappEnabled = 0;
                $socEmailEnabled = 0;
                $socSmsEnabled = 0;
                $socOtherEnabled = 0;

                $tzId = "";
                $tzIsNegative = 0;
                $tzOffsetHour = 0;
                $tzOffsetMinute = 0;
                $attachmentRetainDays = 0;
                
				$utcTz =  'UTC';		
                $utcToday = Carbon::now($utcTz);
				$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
				$utcTodayTs = $utcTodayDt->timestamp;
				$utcRetainMinDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);

                if(isset($userConstants))
                {
                    $defFolderId = $userConstants->def_folder_id;
                    $emailSourceId = $userConstants->email_source_id; 
                    $hasPasscode = $userConstants->passcode_enabled;  
                    $hasFolderPasscode = $userConstants->folder_passcode_enabled;  
	                    
	                $sracShareEnabled = $userConstants->is_srac_share_enabled;
	                $socShareEnabled = $userConstants->is_soc_share_enabled;
	                $socFacebookEnabled = $userConstants->is_soc_facebook_enabled;
	                $socTwitterEnabled = $userConstants->is_soc_twitter_enabled;
	                $socLinkedinEnabled = $userConstants->is_soc_linkedin_enabled;
	                $socWhatsappEnabled = $userConstants->is_soc_whatsapp_enabled;
	                $socEmailEnabled = $userConstants->is_soc_email_enabled;
	                $socSmsEnabled = $userConstants->is_soc_sms_enabled;
	                $socOtherEnabled = $userConstants->is_soc_other_enabled;   
	                
                    if($userConstants->passcode != null)  
                        $passcode = Crypt::decrypt($userConstants->passcode);     
                    if($userConstants->folder_passcode != null)  
                        $folderPasscode = Crypt::decrypt($userConstants->folder_passcode);   
                    if($userConstants->folder_id_str != null) 
                    {
                        $folderIdStr = $userConstants->folder_id_str;
                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
                    }  
                    if($userConstants->print_fields != null) 
                    {
                        $printFieldIdStr = $userConstants->print_fields;
                        $printFieldIdArr = explode($passcodeFolderIdDelimiter, $printFieldIdStr);
                    }   
                    if($userConstants->attachment_kb_allotted != null)  
                        $attachmentSpaceAllotted = $userConstants->attachment_kb_allotted;  
                    if($userConstants->attachment_kb_available != null)  
                        $attachmentSpaceAvailable = $userConstants->attachment_kb_available; 
                    if($userConstants->attachment_kb_used != null)  
                        $attachmentSpaceUsed = $userConstants->attachment_kb_used;
                    if($userConstants->db_size != null)  
                        $dbSize = $userConstants->db_size;  

                    $attachmentRetainDays = $userConstants->attachment_retain_days;
                }       
                
                if($attachmentRetainDays >= 0)
                {
					$utcRetainMinDt = $utcRetainMinDt->subMinutes($attachmentRetainDays*24*60);    
					$utcRetainMinTs = $utcRetainMinDt->timestamp;					
				}               

                $i = 0;
                $fieldList = array();
                $printFields = PrintField::get();
                foreach ($printFields as $field) 
                {
                    $fieldList[$i]['id'] = $field->print_field_id;
                    $fieldList[$i]['name'] = $field->field_name;
                    $i++;
                }                
                $printFieldCnt = count($fieldList); 

                $perDepMgmtObj = New ContentDependencyManagementClass;
                $perDepMgmtObj->withOrgKey($user, "");

                $cloudStorageTypeList = $perDepMgmtObj->getAllCloudStorageTypeListForUser();             
                $cloudStorageTypeCnt = count($cloudStorageTypeList); 

                $cloudCalendarTypeList = $perDepMgmtObj->getAllCloudCalendarTypeListForUser();             
                $cloudCalendarTypeCnt = count($cloudCalendarTypeList); 

                $cloudMailBoxTypeList = $perDepMgmtObj->getAllCloudMailBoxTypeListForUser();             
                $cloudMailBoxTypeCnt = count($cloudMailBoxTypeList); 

                $i = 0;
                $tagList = array();
                $userTags = AppuserTag::ofUser($userId)->get();
                foreach ($userTags as $tag) 
                {
                    $tagList[$i]['name'] = $tag->tag_name;
                    $tagList[$i]['syncId'] = sracEncryptNumberData($tag->appuser_tag_id, $userSession);
                    $tagList[$i]['orgId'] = 0;
                    $i++;
                }      
                
                foreach($orgArr as $org)
                {
                	$orgId = $org['id'];
                	$orgRefId = $org['refId'];
                	$orgEmpId = $org['empId'];
                	
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
	                $modelObj = New OrgEmployeeTag;
	                $modelObj->setConnection($orgDbConName);

	                $userTags = $modelObj->ofEmployee($orgEmpId)->get();
	                
	                foreach ($userTags as $tag) 
	                {
	                    $tagList[$i]['name'] = $tag->tag_name;
	                    $tagList[$i]['syncId'] = sracEncryptNumberData($tag->employee_tag_id, $userSession);
	                    $tagList[$i]['orgId'] = $orgRefId;
	                    $i++;
	                } 
				}           
                $userTagCnt = count($tagList);

                $i = 0;
                $folderList = array();
                $userFolders = AppuserFolder::ofUser($userId)->get();
                foreach ($userFolders as $folder) 
                {
                    $folderList[$i]['name'] = $folder->folder_name;
                    $folderList[$i]['iconCode'] = $folder->icon_code;
	                $folderList[$i]['isFavorited'] = $folder->is_favorited;
	                $folderList[$i]['folderType'] = $folder->folder_type_id;
	                $folderList[$i]['virtualFolderSenderEmail'] = $folder->virtual_folder_sender_email;
	                $folderList[$i]['appliedFilters'] = $folder->applied_filters;
                    $folderList[$i]['syncId'] = sracEncryptNumberData($folder->appuser_folder_id, $userSession);
                    $folderList[$i]['orgId'] = 0;
                    $i++;
                }
                foreach($orgArr as $org)
                {
                	$orgId = $org['id'];
                	$orgRefId = $org['refId'];
                	$orgEmpId = $org['empId'];
                	
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
	                $modelObj = New OrgEmployeeFolder;
	                $modelObj->setConnection($orgDbConName);

	                $userFolders = $modelObj->ofEmployee($orgEmpId)->get();
	                foreach ($userFolders as $folder) 
	                {
	                    $folderList[$i]['name'] = $folder->folder_name;
	                    $folderList[$i]['iconCode'] = $folder->icon_code;
	                	$folderList[$i]['isFavorited'] = $folder->is_favorited;
	               		$folderList[$i]['folderType'] = $folder->folder_type_id;
	               		$folderList[$i]['virtualFolderSenderEmail'] = $folder->virtual_folder_sender_email;
	                	$folderList[$i]['appliedFilters'] = $folder->applied_filters;
	                    $folderList[$i]['syncId'] = sracEncryptNumberData($folder->employee_folder_id, $userSession);
	                    $folderList[$i]['orgId'] = $orgRefId;
	                    $i++;
	                }
				}
                $folderCnt = count($folderList);

                $i = 0;
                $sourceList = array();
                $userSources = AppuserSource::ofUser($userId)->get();
                foreach ($userSources as $source) 
                {
                    $sourceList[$i]['name'] = $source->source_name;
                    $sourceList[$i]['syncId'] = sracEncryptNumberData($source->appuser_source_id, $userSession);
                    $sourceList[$i]['orgId'] = 0;
                    $i++;
                }
                foreach($orgArr as $org)
                {
                	$orgId = $org['id'];
                	$orgRefId = $org['refId'];
                	$orgEmpId = $org['empId'];
                	
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
	                $modelObj = New OrgEmployeeSource;
	                $modelObj->setConnection($orgDbConName);

	                $userSources = $modelObj->ofEmployee($orgEmpId)->get();
	                foreach ($userSources as $source) 
	                {
	                    $sourceList[$i]['name'] = $source->source_name;
	                    $sourceList[$i]['syncId'] = sracEncryptNumberData($source->employee_source_id, $userSession);
	                    $sourceList[$i]['orgId'] = $orgRefId;
	                    $i++;
	                }
				}
                $sourceCnt = count($sourceList);
                
                $grpConCnt = 0;
                $totalAttachmentSize = 0;
                
                $i = 0;
                $groupList = array();     
                $userGroups = GroupMember::ofUser($userId)->joinGroup()->ofDistinctGroup()->get();
                foreach($userGroups as $userGroup) 
                {
	                $orgId = 0;
	                
					$grpId = $userGroup->group_id;
					$isTwoWay = $userGroup->is_two_way;
					$isFavorited = $userGroup->is_favorited;
					$isGrpLocked = $userGroup->is_locked;
					$description = $userGroup->description;
					$name = $userGroup->name;
					$allocKb = $userGroup->allocated_space_kb;
					$usedKb = $userGroup->used_space_kb;
					
					$isAdmin = 0;					
					$isUserGroupAdmin = GroupMember::isUserGroupAdmin($grpId, $userId)->first();
	    			if(isset($isUserGroupAdmin))  
	    			{
						$isAdmin = 1;
					} 
						
					$photoFilename = $userGroup->img_server_filename;
					$groupPhotoUrl = "";
					$groupPhotoThumbUrl = "";
					if(isset($photoFilename) && $photoFilename != "")
					{
						$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
						$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
					}
					
					$contentsArr = array();
                	$grpContents = GroupContent::ofGroup($grpId)->get();
                	foreach($grpContents as $grpContent)
                	{
						$grpContentDetails = array();
	                    					
						$tagsArr = array();
	                    $contentTags = GroupContentTag::ofGroupContent($grpContent->group_content_id)->get();
	                    foreach ($contentTags as $contentTag) 
	                    {
							array_push($tagsArr, $contentTag->tag_id);
	                    }
	                    $tagCnt = count($tagsArr);
	                    
		                $performDownload = 0;
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
	                    
	                    $contentAttachments = GroupContentAttachment::ofGroupContent($grpContent->group_content_id)->get();

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
	                   	$encDecContent = "";
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
	                    if($grpContent->content_title === '' || $grpContent->content_title ===null){
							$grpContentDetails['content_title'] = 'No Title';
						}else{
							$grpContentDetails['content_title'] = $grpContent->content_title;
						}
	                   // $grpContentDetails['content_title'] = $grpContent->content_title;
	                    $grpContentDetails['content'] = $decContent;
                    	$grpContentDetails['colorCode'] = $grpContent->color_code;
                    	$grpContentDetails['isLocked'] = $grpContent->is_locked;
                    	$grpContentDetails['isShareEnabled'] = $grpContent->is_share_enabled;
                    	$grpContentDetails['remindBeforeMillis'] = $grpContent->remind_before_millis;
                    	$grpContentDetails['repeatDuration'] = $grpContent->repeat_duration;
						$grpContentDetails['isCompleted'] = $grpContent->is_completed;
						$grpContentDetails['isSnoozed'] = $grpContent->is_snoozed;
						$grpContentDetails['reminderTimestamp'] = isset($grpContent->reminder_timestamp) ? $grpContent->reminder_timestamp : 0;
	                    $grpContentDetails['encContent'] = $encDecContent;
	                    $grpContentDetails['contentType'] = $grpContent->content_type_id;
	                    $grpContentDetails['groupId'] = sracEncryptNumberData($grpContent->group_id, $userSession);
	                    $grpContentDetails['isMarked'] = $grpContent->is_marked;
	                    $grpContentDetails['createTimeStamp'] = $grpContent->create_timestamp;
	                    $grpContentDetails['updateTimeStamp'] = $grpContent->update_timestamp;
						$grpContentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
						$grpContentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
	                    $grpContentDetails['fromTimeStamp'] = $frmTs;
	                    $grpContentDetails['toTimeStamp'] = $toTs;
	                    $grpContentDetails['tagCnt'] = $tagCnt;
	                    $grpContentDetails['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
	                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
	                    $grpContentDetails['attachments'] = $attachmentsArr;
                    	$grpContentDetails['sharedByEmail'] = $sharedByEmail;
						
						array_push($contentsArr, $grpContentDetails);
					}
					
					$hasPostRight = 1;
										
					$groupList[$i]["syncId"] = sracEncryptNumberData($grpId, $userSession);
					$groupList[$i]["name"] = $name;
					$groupList[$i]["orgId"] = $name;
					$groupList[$i]["isUserAdmin"] = $isAdmin;
					$groupList[$i]["hasPostRight"] = $hasPostRight;
					$groupList[$i]["isTwoWay"] = $isTwoWay;
					$groupList[$i]["isFavorited"] = $isFavorited;
					$groupList[$i]["isGrpLocked"] = $isGrpLocked;
					$groupList[$i]["description"] = $description;
					$groupList[$i]["photoUrl"] = $groupPhotoUrl;
					$groupList[$i]["photoThumbUrl"] = $groupPhotoThumbUrl;
					$groupList[$i]["photoFilename"] = $photoFilename;
					$groupList[$i]["contentArr"] = $contentsArr;
					$groupList[$i]["contentCnt"] = count($contentsArr);
					$groupList[$i]["allocKb"] = $allocKb;
					$groupList[$i]["usedKb"] = $usedKb;
	                $groupList[$i]['orgId'] = 0;
					$i++;
					$grpConCnt += count($contentsArr);		
				} 
                foreach($orgArr as $org)
                {
	        		$grpPhotoBaseUrl = Config::get('app_config.url_path_org_group_photo');   
	        
                	$orgId = $org['id'];
                	$orgRefId = $org['refId'];
                	$orgEmpId = $org['empId'];
                	
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
	                $modelObj = New OrgGroupMember;
	                $modelObj->setConnection($orgDbConName);

	                $userGroups = $modelObj->joinGroupTable()->ofEmployee($orgEmpId)->ofDistinctGroup()->get();
	                foreach($userGroups as $userGroup) 
	                {	                   
						$grpId = $userGroup->group_id;
						$isTwoWay = $userGroup->is_two_way;
						$name = $userGroup->name;
						$allocKb = $userGroup->allocated_space_kb;
						$usedKb = $userGroup->used_space_kb;
						$isAdmin = $userGroup->is_admin;
						$hasPostRight = $userGroup->has_post_right;
						$isFavorited = $userGroup->is_favorited;
						$isGrpLocked = $userGroup->is_locked;
						$description = $userGroup->description;
						
						$photoFilename = $userGroup->img_server_filename;
						$groupPhotoUrl = "";
						$groupPhotoThumbUrl = "";
						if(isset($photoFilename) && $photoFilename != "")
						{
							$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
							$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
						}
						
						/*$modelObj = New OrgGroupMember;
		                $modelObj->setConnection($orgDbConName);*/

		                /*$isUserGroupAdmin = $modelObj->isEmployeeGroupAdmin($grpId, $empId)->first();
		    			if(isset($isUserGroupAdmin))  
		    			{
							$isAdmin = 1;
						}*/

		                /*$isUserHavingPostRights = $modelObj->employeeHasPostRight($grpId, $empId)->first();
		    			if(isset($isUserHavingPostRights))  
		    			{
							$hasPostRight = 1;
						} */
						
						$contentsArr = array();
						$modelObj = New OrgGroupContent;
		                $modelObj->setConnection($orgDbConName);

		                $grpContents = $modelObj->ofGroup($grpId)->get();
	                	foreach($grpContents as $grpContent)
	                	{
							$grpContentDetails = array();
		                    					
							$tagsArr = array();
							$modelObj = New OrgGroupContentTag;
			                $modelObj->setConnection($orgDbConName);

			                $contentTags = $modelObj->ofGroupContent($grpContent->group_content_id)->get();
		                    foreach ($contentTags as $contentTag) 
		                    {
								array_push($tagsArr, $contentTag->tag_id);
		                    }
		                    $tagCnt = count($tagsArr);
		                    
		                    if($attachmentRetainDays >= 0)
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
							}
		                	$performDownload = 0;

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
                    		$grpContentDetails['colorCode'] = $grpContent->color_code;
                    		$grpContentDetails['isLocked'] = $grpContent->is_locked;
                    		$grpContentDetails['isShareEnabled'] = $grpContent->is_share_enabled;
	                    	$grpContentDetails['remindBeforeMillis'] = $grpContent->remind_before_millis;
	                    	$grpContentDetails['repeatDuration'] = $grpContent->repeat_duration;
							$grpContentDetails['isCompleted'] = $grpContent->is_completed;
							$grpContentDetails['isSnoozed'] = $grpContent->is_snoozed;
							$grpContentDetails['reminderTimestamp'] = isset($grpContent->reminder_timestamp) ? $grpContent->reminder_timestamp : 0;
							if($grpContent->content_title === '' || $grpContent->content_title ===null){
    							$grpContentDetails['content_title'] = 'No Title';
    						}else{
    							$grpContentDetails['content_title'] = $grpContent->content_title;
    						}
		                  //  $grpContentDetails['content_title'] = $grpContent->content_title;
		                    $grpContentDetails['content'] = $decContent;
	                    	$grpContentDetails['encContent'] = $encDecContent;
		                    $grpContentDetails['contentType'] = $grpContent->content_type_id;
		                    $grpContentDetails['groupId'] = sracEncryptNumberData($grpContent->group_id, $userSession);
		                    $grpContentDetails['isMarked'] = $grpContent->is_marked;
		                    $grpContentDetails['createTimeStamp'] = $grpContent->create_timestamp;
		                    $grpContentDetails['updateTimeStamp'] = $grpContent->update_timestamp;
							$grpContentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
							$grpContentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
		                    $grpContentDetails['fromTimeStamp'] = $frmTs;
		                    $grpContentDetails['toTimeStamp'] = $toTs;
		                    $grpContentDetails['tagCnt'] = $tagCnt;
		                    $grpContentDetails['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
		                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
		                    $grpContentDetails['attachments'] = $attachmentsArr;
	                    	$grpContentDetails['sharedByEmail'] = $sharedByEmail;
							
							array_push($contentsArr, $grpContentDetails);
						}  
											
						$groupList[$i]["syncId"] = sracEncryptNumberData($grpId, $userSession);
						$groupList[$i]["name"] = $name;
						$groupList[$i]["isUserAdmin"] = $isAdmin;
						$groupList[$i]["hasPostRight"] = $hasPostRight;
						$groupList[$i]["isTwoWay"] = $isTwoWay;
						$groupList[$i]["isFavorited"] = $isFavorited;
						$groupList[$i]["isGrpLocked"] = $isGrpLocked;
						$groupList[$i]["description"] = $description;
						$groupList[$i]["photoUrl"] = $groupPhotoUrl;
						$groupList[$i]["photoThumbUrl"] = $groupPhotoThumbUrl;
						$groupList[$i]["photoFilename"] = $photoFilename;
						$groupList[$i]["contentArr"] = $contentsArr;
						$groupList[$i]["contentCnt"] = count($contentsArr);
	                    $groupList[$i]['orgId'] = $orgRefId;
						$groupList[$i]["allocKb"] = $allocKb;
						$groupList[$i]["usedKb"] = $usedKb;
						$i++;
						$grpConCnt += count($contentsArr);
	                }
				}
				$groupCnt = count($groupList);

                $i = 0;
                $contentList = array();
                $userContents = AppuserContent::ofUser($userId)->removedConsiderationForSync()->get();
                foreach ($userContents as $content) 
                {
                    $tagsArr = array();
                    $contentTags = AppuserContentTag::ofUserContent($content->appuser_content_id)->get();
                    foreach ($contentTags as $contentTag) 
                    {
                        array_push($tagsArr, $contentTag->tag_id);
                    }
                    $tagCnt = count($tagsArr);
                    
                    if($attachmentRetainDays >= 0)
                	{                		
	                    $performDownload = 0;
						$utcContentCreateDt = Carbon::createFromTimeStampUTC($content->create_timestamp);
						$utcContentCreateTs = $utcContentCreateDt->timestamp;
	                    if($utcContentCreateTs >= $utcRetainMinTs)
	                    {
							$performDownload = 1;
						}
					}
					else
					{
						$performDownload = 1;
					}
		            $performDownload = 0;

                    $j = 0;
                    $attachmentsArr = array();
                    $contentAttachments = AppuserContentAttachment::ofUserContent($content->appuser_content_id)->get();
                    foreach ($contentAttachments as $contentAttachment) 
                    {  
                    	$orgId = 0;

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
	                  	$perAttachmentSize += $contentAttachment->filesize;
	                    
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
                   	 	
                    $contentList[$i]['syncId'] = sracEncryptNumberData($content->appuser_content_id, $userSession);
                    if($content->content_title === '' || $content->content_title ===null){
						$contentList[$i]['content_title'] = 'No Title';
					}else{
						$contentList[$i]['content_title'] = $content->content_title;
					}
                    // $contentList[$i]['content_title'] = $content->content_title;
                    $contentList[$i]['content'] = $decContent;
                    $contentList[$i]['colorCode'] = $content->color_code;
                    $contentList[$i]['isLocked'] = $content->is_locked;
                    $contentList[$i]['isShareEnabled'] = $content->is_share_enabled;
                	$contentList[$i]['remindBeforeMillis'] = $content->remind_before_millis;
                	$contentList[$i]['repeatDuration'] = $content->repeat_duration;
					$contentList[$i]['isCompleted'] = $content->is_completed;
					$contentList[$i]['isSnoozed'] = $content->is_snoozed;
					$contentList[$i]['reminderTimestamp'] = isset($content->reminder_timestamp) ? $content->reminder_timestamp : 0;
	                $contentList[$i]['encContent'] = $encDecContent;
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
                    $contentList[$i]['orgId'] = 0;
                    $i++;
                }       

				$intOrgRef = 0;
                foreach($orgArr as $org)
                {
                	$orgId = $org['id'];
                	$orgRefId = $org['refId'];
                	$orgEmpId = $org['empId'];
                	
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
	                $modelObj = New OrgEmployeeContent;
	                $modelObj->setConnection($orgDbConName);

	                $userContents = $modelObj->ofEmployee($orgEmpId)->removedConsiderationForSync()->get();
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
	                    
	                    if($attachmentRetainDays >= 0)
	                	{                		
		                    $performDownload = 0;
							$utcContentCreateDt = Carbon::createFromTimeStampUTC($content->create_timestamp);
							$utcContentCreateTs = $utcContentCreateDt->timestamp;
		                    if($utcContentCreateTs >= $utcRetainMinTs)
		                    {
								$performDownload = 1;
							}
						}
						else
						{
							$performDownload = 1;
						}
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
								$attUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
							}
							else
							{
								$attUrl = $contentAttachment->cloud_file_url; 
							}

	                        $attachmentsArr[$j]['name'] = $contentAttachment->filename;
	                        $attachmentsArr[$j]['size'] = $contentAttachment->filesize;
                            $attachmentsArr[$j]['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
                            $attachmentsArr[$j]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
                            $attachmentsArr[$j]['cloudFileId'] = $contentAttachment->cloud_file_id;
                    		$attachmentsArr[$j]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
                            $attachmentsArr[$j]['attCreateTs'] = $contentAttachment->create_ts;
                            $attachmentsArr[$j]['attUpdateTs'] = $contentAttachment->update_ts;
	                        $attachmentsArr[$j]['url'] = $attUrl;	                        
	                        $attachmentsArr[$j]['performDownload'] = $performDownload;
	                        $attachmentsArr[$j]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
		                  	
		                  	$orgAttachmentSize[$orgId] += $contentAttachment->filesize;
		                    
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
	                    if($content->content_title === '' || $content->content_title ===null){
							$contentList[$i]['content_title'] = 'No Title';
						}else{
							$contentList[$i]['content_title'] = $content->content_title;
						}
	                   // $contentList[$i]['content_title'] = $content->content_title;
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
	                    $contentList[$i]['orgId'] = $orgRefId;
	                    $i++;
	                    $intOrgRef++;
	                }
	                
				}      
                $conCnt = count($contentList);
                
                if($conCnt + $grpConCnt > 0)
                {
                	$perAttachmentSize += 1*1000;
                }
                
                $updatedAttachmentSpaceAvailable = $attachmentSpaceAllotted - $perAttachmentSize;   
                
                if($updatedAttachmentSpaceAvailable < 0)
                	$updatedAttachmentSpaceAvailable = 0;
                
				// $attachmentSpaceAvailable = $updatedAttachmentSpaceAvailable;
				// if(isset($userConstants))
				// {
				// 	$userConstants->attachment_kb_available = $attachmentSpaceAvailable;
				// 	$userConstants->save();
				// }
				
				$intOrgRef = 0;
				foreach($orgArr as $org)
                {
                	$orgId = $org['id'];  
                	$orgEmpId = $org['empId'];              	
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
	                $orgEmpConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
	                if(isset($orgEmpConstant))
	                {
						$orgAttachmentSpaceAllotted = $orgEmpConstant->attachment_kb_allotted;
	                
	                	$orgAttachmentSizeUsed = $orgAttachmentSize[$orgId];
	                	$updatedOrgAttachmentSpaceAvailable = $orgAttachmentSpaceAllotted - $orgAttachmentSizeUsed;   
		                if($updatedOrgAttachmentSpaceAvailable < 0)
		                	$updatedOrgAttachmentSpaceAvailable = 0;
		                
						$orgAttachmentSpaceAvailable = $updatedOrgAttachmentSpaceAvailable;
						if(isset($orgEmpConstant))
						{
							$orgEmpConstant->attachment_kb_available = $orgAttachmentSpaceAvailable;
							$orgEmpConstant->save();
						}
						    
		                $orgList[$intOrgRef]['attachmentSpaceAvailable'] = $orgAttachmentSpaceAvailable;
		                $intOrgRef++;
					}   
				}	
						
                $userConstantDetails = array();
				$userConstantDetails['isPremium'] = $user->is_premium;
                $userConstantDetails['isAccountDisabled'] = $user->is_account_disabled;
                $userConstantDetails['defFolderId'] = sracEncryptNumberData($defFolderId, $userSession);
                $userConstantDetails['emailSourceId'] = sracEncryptNumberData($emailSourceId, $userSession);
                $userConstantDetails['hasPasscode'] = $hasPasscode;    
                $userConstantDetails['passcode'] = $passcode;  
                $userConstantDetails['hasFolderPasscode'] = $hasFolderPasscode;    
                $userConstantDetails['folderPasscode'] = $folderPasscode;  
                $userConstantDetails['folderIdArr'] = sracEncryptNumberArrayData($passcodeFolderIdArr, $userSession);   
                $userConstantDetails['printFieldIdArr'] = $printFieldIdArr;   
                $userConstantDetails['attachmentSpaceAllotted'] = $attachmentSpaceAllotted;      
                $userConstantDetails['attachmentSpaceAvailable'] = $attachmentSpaceAvailable;
                $userConstantDetails['attachmentSpaceUsed'] = $attachmentSpaceUsed;
                $userConstantDetails['dbSize'] = $dbSize;
                $userConstantDetails['attachmentRetainDays'] = $attachmentRetainDays;
                $userConstantDetails['sracShareEnabled'] = $sracShareEnabled;
                $userConstantDetails['socShareEnabled'] = $socShareEnabled;
                $userConstantDetails['socFacebookEnabled'] = $socFacebookEnabled;
                $userConstantDetails['socTwitterEnabled'] = $socTwitterEnabled;
                $userConstantDetails['socLinkedinEnabled'] = $socLinkedinEnabled;
                $userConstantDetails['socWhatsappEnabled'] = $socWhatsappEnabled;
                $userConstantDetails['socEmailEnabled'] = $socEmailEnabled;
                $userConstantDetails['socSmsEnabled'] = $socSmsEnabled;
		        $userConstantDetails['socOtherEnabled'] = $socOtherEnabled;

                $response['printFieldCnt'] = $printFieldCnt;
                $response['printFieldArr'] = $fieldList;
                $response['cloudStorageTypeCnt'] = $cloudStorageTypeCnt;
                $response['cloudStorageTypeArr'] = $cloudStorageTypeList;
                $response['cloudCalendarTypeCnt'] = $cloudCalendarTypeCnt;
                $response['cloudCalendarTypeArr'] = $cloudCalendarTypeList;
                $response['cloudMailBoxTypeCnt'] = $cloudMailBoxTypeCnt;
                $response['cloudMailBoxTypeArr'] = $cloudMailBoxTypeList;
                $response['tagCnt'] = $userTagCnt;
                $response['tagArr'] = $tagList;
                $response['folderCnt'] = $folderCnt;
                $response['folderArr'] = $folderList;
                $response['sourceCnt'] = $sourceCnt;
                $response['sourceArr'] = $sourceList;
                $response['groupCnt'] = $groupCnt;
                $response['groupArr'] = $groupList;
                $response['contentCnt'] = $conCnt;
                $response['contentArr'] = $contentList;
                $response['userConstant'] = $userConstantDetails;
                $response['orgCnt'] = $orgCnt;
                $response['orgArr'] = $orgList;
                
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
     * Sync User Constants.
     *
     * @return json array
     */
    public function userConstantDataSync()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $tzId = Input::get('tzId');
        $tzIsNegative = Input::get('tzIsNegative');
        $tzOffsetHour = Input::get('tzOffsetHour');
        $tzOffsetMinute = Input::get('tzOffsetMinute');
        $defFolderId = Input::get('defFolderId');
        $defTagId = Input::get('defTagId');
        $hasPasscode = Input::get('hasPasscode');
        $passcode = Input::get('passcode');
        $hasFolderPasscode = Input::get('hasFolderPasscode');
        $folderPasscode = Input::get('folderPasscode');
        $folderIdArr = Input::get('folderIdArr');
        $printFieldIdArr = Input::get('printFieldIdArr');
        $attachmentSpaceAllotted = Input::get('attachmentSpaceAllotted');
        $attachmentSpaceAvailable = Input::get('attachmentSpaceAvailable');
        $dbSize = Input::get('dbSize');
        $attachmentRetainDays = Input::get('attachmentRetainDays');
        $loginToken = Input::get('loginToken');

        $folderIdArr = json_decode($folderIdArr);
        $printFieldIdArr = json_decode($printFieldIdArr);

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

                $defFolderId = sracDecryptNumberData($defFolderId, $userSession);
                $defTagId = sracDecryptNumberData($defTagId, $userSession);
                $folderIdArr = sracDecryptNumberArrayData($folderIdArr, $userSession);

                $userConstant = AppuserConstant::ofUser($userId)->first();
                if(isset($userConstant))
                {
                    $status = 1;  
                    $printFieldIdStr = "";
                    $folderIdStr = "";

                    $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter'); 
                    if($folderIdArr != null && count($folderIdArr) > 0) 
                    {
                        $folderIdStr = implode($passcodeFolderIdDelimiter, $folderIdArr);
                    }   
                    if($printFieldIdArr != null && count($printFieldIdArr) > 0) 
                    {
                        $printFieldIdStr = implode($passcodeFolderIdDelimiter, $printFieldIdArr);
                    }   

                    $userConstant->timezone_id = $tzId;
                    $userConstant->utc_offset_is_negative = $tzIsNegative;
                    $userConstant->utc_offset_hour = $tzOffsetHour;
                    $userConstant->utc_offset_minute = $tzOffsetMinute;

                    $userConstant->def_folder_id = $defFolderId;
                    $userConstant->def_tag_id = $defTagId;
                    $userConstant->passcode_enabled = $hasPasscode;
                    $userConstant->passcode = Crypt::encrypt($passcode);
                    $userConstant->folder_passcode_enabled = $hasFolderPasscode;
                    $userConstant->folder_passcode = Crypt::encrypt($folderPasscode);
                    $userConstant->folder_id_str = $folderIdStr;
                    $userConstant->print_fields = $printFieldIdStr;
                    //$userConstant->attachment_kb_allotted = $attachmentSpaceAllotted;
                    $userConstant->attachment_kb_available = $attachmentSpaceAvailable;
                    $userConstant->db_size = $dbSize;
                    $userConstant->attachment_retain_days = $attachmentRetainDays;
                    $userConstant->save();  
                    
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);                  
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
     * Sync User Data.
     *
     * @return json array
     */
    public function userDataPrimaryReSync()
    {
        // Log::info('------------------------------------------------------- userDataPrimaryReSync --------------------------------------------------------------');
        set_time_limit(0);

        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $orgMapArr = Input::get('orgMapArr');
        $dataRestore = Input::get('dataRestore');
        $folderArr = Input::get('folderArr');
        $tagArr = Input::get('tagArr');
        $sourceArr = Input::get('sourceArr');

        $delFolderArr = Input::get('delFolderArr');
        $delTagArr = Input::get('delTagArr');
        $delSourceArr = Input::get('delSourceArr');
        $delContentArr = Input::get('delContentArr');
        $delContentAttachmentArr = Input::get('delContentAttachmentArr');
        $loginToken = Input::get('loginToken');

        // Log::info('1 : folderArr : ');
        // Log::info($folderArr);
        // Log::info('1 : tagArr : ');
        // Log::info($tagArr);
        // Log::info('1 : sourceArr : ');
        // Log::info($sourceArr);
        // Log::info('1 : dataRestore : ');
        // Log::info($dataRestore);
        // Log::info('1 : orgMapArr : ');
        // Log::info($orgMapArr);

        // Log::info('1 : delFolderArr : ');
        // Log::info($delFolderArr);
        // Log::info('1 : delTagArr : ');
        // Log::info($delTagArr);
        // Log::info('1 : delSourceArr : ');
        // Log::info($delSourceArr);
        // Log::info('1 : delContentArr : ');
        // Log::info($delContentArr);
        // Log::info('1 : delContentAttachmentArr : ');
        // Log::info($delContentAttachmentArr);


        $orgMapArr = json_decode($orgMapArr);
        $folderArr = json_decode($folderArr);
        $tagArr = json_decode($tagArr);
        $sourceArr = json_decode($sourceArr);
        $delFolderArr = json_decode($delFolderArr);
        $delTagArr = json_decode($delTagArr);
        $delSourceArr = json_decode($delSourceArr);
        $delContentArr = json_decode($delContentArr);
        $delContentAttachmentArr = json_decode($delContentAttachmentArr);

        // Log::info('2 : folderArr : ');
        // Log::info($folderArr);
        // Log::info('2 : tagArr : ');
        // Log::info($tagArr);
        // Log::info('2 : sourceArr : ');
        // Log::info($sourceArr);
        // Log::info('2 : dataRestore : ');
        // Log::info($dataRestore);
        // Log::info('2 : orgMapArr : ');
        // Log::info($orgMapArr);

        // Log::info('2 : delFolderArr : ');
        // Log::info($delFolderArr);
        // Log::info('2 : delTagArr : ');
        // Log::info($delTagArr);
        // Log::info('2 : delSourceArr : ');
        // Log::info($delSourceArr);
        // Log::info('2 : delContentArr : ');
        // Log::info($delContentArr);
        // Log::info('2 : delContentAttachmentArr : ');
        // Log::info($delContentAttachmentArr);

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
                
                $orgRefToSerMap = array();
                $orgRefToSerEmpMap = array();
                for($i=0; $i<count($orgMapArr); $i++)
                {
                	$orgLocId = $orgMapArr[$i]->id;
                	$encOrgKey = $orgMapArr[$i]->key;                	
                	$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgKey);
                	$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgKey);
                	$orgRefToSerMap[$orgLocId] = $orgId;		
                	$orgRefToSerEmpMap[$orgLocId] = $orgEmpId;		
				}

                if($dataRestore*1 == 1)
                {
                    $userContents = AppuserContent::ofUser($userId)->get();
                   	foreach ($userContents as $userContent) 
                    {
                        if(isset($userContent))
                        {
                            AppuserContentTag::ofUserContent($userContent->appuser_content_id)->delete();
                            $contentAttachments = AppuserContentAttachment::ofUserContent($userContent->appuser_content_id)->get();
                            foreach ($contentAttachments as $attachment) 
                            {
                                $fileName = $attachment->server_filename;
                                FileUploadClass::removeAttachment($fileName);
                                $attachment->delete();
                            }
                            $userContent->delete();
                        }
                    }
                    
                    AppuserFolder::ofUser($userId)->delete();
                    AppuserTag::ofUser($userId)->delete();
                    AppuserSource::ofUser($userId)->delete();
                    
                    //Remove Organization Details
                }

                $folderResponse = array(); 
                if(isset($folderArr) && count($folderArr)>0)
                {
			        // Log::info('List : folderArr : ');
                    for($i=0; $i<count($folderArr); $i++)
                    {
                        $entryObj = $folderArr[$i];

				        // Log::info('i : '.$i);

                        $locOrgId = $entryObj->orgId;
                        $serId = sracDecryptNumberData($entryObj->serId, $userSession);
                        $name = $entryObj->name;
                        $iconCode = $entryObj->iconCode;
                        $isFavorited = isset($entryObj->isFavorited) ? $entryObj->isFavorited : 0;
                        $folderTypeId = $entryObj->folderType;
                        $appliedFilters = isset($entryObj->appliedFilters) ? $entryObj->appliedFilters : NULL;
                        
				        // Log::info('serId : ');
				        // Log::info($serId);
				        // Log::info('name : ');
				        // Log::info($name);

		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);                   
                        
		                $insResponse = $depMgmtObj->addEditFolder($serId, $name, $iconCode, $isFavorited, $folderTypeId, $appliedFilters);
		                $insResponse['syncId'] = sracEncryptNumberData($insResponse['syncId'], $userSession);

				        // Log::info('insResponse : ');
				        // Log::info($insResponse);

                        $folderResponse[$i] = $insResponse;
                    }
                }
                $response["folderResponse"] = $folderResponse;

                $tagResponse = array(); 
                if(isset($tagArr) && count($tagArr)>0)
                {
			        // Log::info('List : tagArr : ');
                    for($i=0; $i<count($tagArr); $i++)
                    {
                        $entryObj = $tagArr[$i];

				        // Log::info('i : '.$i);

                        $locOrgId = $entryObj->orgId;
                        $serId = sracDecryptNumberData($entryObj->serId, $userSession);
                        $name = $entryObj->name;
                        
				        // Log::info('serId : ');
				        // Log::info($serId);
				        // Log::info('name : ');
				        // Log::info($name);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);                       
                        
		                $insResponse = $depMgmtObj->addEditTag($serId, $name); 
		                $insResponse['syncId'] = sracEncryptNumberData($insResponse['syncId'], $userSession);
                        $tagResponse[$i] = $insResponse;
                    }
                }
                $response["tagResponse"] = $tagResponse;

                $sourceResponse = array(); 
                if(isset($sourceArr) && count($sourceArr)>0)
                {
			        // Log::info('List : sourceArr : ');
                    for($i=0; $i<count($sourceArr); $i++)
                    {
                        $entryObj = $sourceArr[$i];

				        // Log::info('i : '.$i);

                        $locOrgId = $entryObj->orgId;
                        $serId = sracDecryptNumberData($entryObj->serId, $userSession);
                        $name = $entryObj->name;
                        
				        // Log::info('serId : ');
				        // Log::info($serId);
				        // Log::info('name : ');
				        // Log::info($name);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);                     
                        
		                $insResponse = $depMgmtObj->addEditSource($serId, $name);
		                $insResponse['syncId'] = sracEncryptNumberData($insResponse['syncId'], $userSession);
		                
				        // Log::info('insResponse : ');
				        // Log::info($insResponse);

                        $sourceResponse[$i] = $insResponse;
                    }
                }
                $response["sourceResponse"] = $sourceResponse;

                if(isset($delFolderArr) && count($delFolderArr)>0)
                {
			        // Log::info('List : delFolderArr : ');
                    for($i=0; $i<count($delFolderArr); $i++)
                    {
				        // Log::info('i : '.$i);

                        $serId = sracDecryptNumberData($delFolderArr[$i]->serId, $userSession);
                        $locOrgId = $delFolderArr[$i]->orgId;

				        // Log::info('serId : ');
				        // Log::info($serId);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);

		                $depMgmtObj->deleteFolder($serId);
                    }
                }
                
                if(isset($delTagArr) && count($delTagArr)>0)
                {
			        // Log::info('List : delTagArr : ');
                    for($i=0; $i<count($delTagArr); $i++)
                    {
				        // Log::info('i : '.$i);

                        $serId = sracDecryptNumberData($delTagArr[$i]->serId, $userSession);
                        $locOrgId = $delTagArr[$i]->orgId;

				        // Log::info('serId : ');
				        // Log::info($serId);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);

		                $depMgmtObj->deleteTag($serId);
                    }
                }

                if(isset($delSourceArr) && count($delSourceArr)>0)
                {
			        // Log::info('List : delSourceArr : ');
                    for($i=0; $i<count($delSourceArr); $i++)
                    {
				        // Log::info('i : '.$i);

                        $serId = sracDecryptNumberData($delSourceArr[$i]->serId, $userSession);
                        $locOrgId = $delSourceArr[$i]->orgId;

				        // Log::info('serId : ');
				        // Log::info($serId);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);

		                $depMgmtObj->deleteSource($serId);
                    }
                }

                if(isset($delContentArr) && count($delContentArr)>0)
                {
			        // Log::info('List : delContentArr : ');
                	$isFolder = TRUE;
                    for($i=0; $i<count($delContentArr); $i++)
                    {
				        // Log::info('i : '.$i);

                        $serId = sracDecryptNumberData($delContentArr[$i]->serId, $userSession);
                        $locOrgId = $delContentArr[$i]->orgId;
                        $removedAt = isset($delContentArr[$i]->removedAt) ? $delContentArr[$i]->removedAt : null;

				        // Log::info('serId : ');
				        // Log::info($serId);
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);

	                    if($orgId > 0)
	                    {
	                        $depMgmtObj->setContentSharedContentId($serId, $isFolder, 0, $orgEmpId);
	                    }
	                    else
	                    {
	                        $depMgmtObj->setContentSharedContentId($serId, $isFolder, 0, $userId);
	                    }
	                    

	                    $depMgmtObj->softDeleteContent($serId, $isFolder, 1, $removedAt);
                    }
                }
                
                if(isset($delContentAttachmentArr) && count($delContentAttachmentArr)>0)
                {
                    for($i=0; $i<count($delContentAttachmentArr); $i++)
                    {
                        $serId = sracDecryptNumberData($delContentAttachmentArr[$i]->serId, $userSession);
                        $locOrgId = $delContentAttachmentArr[$i]->orgId;
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId]; 
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);
		            	
		                $depMgmtObj->deleteContentAttachment($serId);
                    }
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
    public function userDataSecondaryReSync()
    {
        // Log::info('------------------------------------------------------- userDataSecondaryReSync --------------------------------------------------------------');
        set_time_limit(0);

        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $orgMapArr = Input::get('orgMapArr');
        $contentArr = Input::get('contentArr');
        $loginToken = Input::get('loginToken');

        // Log::info('1 : orgMapArr : ');
        // Log::info($orgMapArr);
        // Log::info('1 : contentArr : ');
        // Log::info($contentArr);
      
        $orgMapArr = json_decode($orgMapArr);
        $contentArr = json_decode($contentArr);

        // Log::info('2 : orgMapArr : ');
        // Log::info($orgMapArr);
        // Log::info('2 : contentArr : ');
        // Log::info($contentArr);

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
                
                $orgRefToSerMap = array();
                $orgRefToSerEmpMap = array();
                for($i=0; $i<count($orgMapArr); $i++)
                {
                	$orgLocId = $orgMapArr[$i]->id;
                	$encOrgKey = $orgMapArr[$i]->key;                  	
                	$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgKey);
                	$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgKey);
                	$orgRefToSerMap[$orgLocId] = $orgId;		
                	$orgRefToSerEmpMap[$orgLocId] = $orgEmpId;		
				}

                $finalContentResponse = array(); 
                if(isset($contentArr) && count($contentArr)>0)
                {
			        // Log::info('List : contentArr : ');

                	$isFolder = TRUE;
                    for($i=0; $i<count($contentArr); $i++)
                    {
                        $entryObj = $contentArr[$i];

                        $locOrgId = $entryObj->orgId;
                        $serId = sracDecryptNumberData($entryObj->serId, $userSession);
                        $content = $entryObj->content;
						$contentTitle = $entryObj->content_title;
                        $contentType = $entryObj->contentType;
                        $isAdd = $entryObj->isAdd;
                        $folderId = sracDecryptNumberData($entryObj->folderId, $userSession);
                        $sourceId = sracDecryptNumberData($entryObj->sourceId, $userSession);
                        $isMarked = $entryObj->isMarked;
                        $createTimeStamp = $entryObj->createTimeStamp;
                        $updateTimeStamp = $entryObj->updateTimeStamp;
                        $fromTimeStamp = $entryObj->fromTimeStamp;
                        $toTimeStamp = $entryObj->toTimeStamp;
                        $colorCode = $entryObj->colorCode;
                    	$contentIsLocked = $entryObj->isLocked;
                    	$syncWithCloudCalendarGoogle = isset($entryObj->syncWithCloudCalendarGoogle) ? $entryObj->syncWithCloudCalendarGoogle : 0;
                    	$syncWithCloudCalendarOnedrive = isset($entryObj->syncWithCloudCalendarOnedrive) ? $entryObj->syncWithCloudCalendarOnedrive : 0;
                    	$contentIsRemoved = isset($entryObj->isRemoved) ? $entryObj->isRemoved : 0;
                    	$contentRemovedAt = isset($entryObj->removedAt) ? $entryObj->removedAt : null;
                    	$contentIsShareEnabled = isset($entryObj->isShareEnabled) ? $entryObj->isShareEnabled : Config::get('app_config.default_content_share_status');
                    	$isCompleted = isset($entryObj->isCompleted) ? $entryObj->isCompleted : 0;
                    	$isSnoozed = isset($entryObj->isSnoozed) ? $entryObj->isSnoozed : 0;
                    	$reminderTimestamp = isset($entryObj->reminderTimestamp) ? $entryObj->reminderTimestamp : 0;
                    	
				        // Log::info('i : '.$i);
				        // Log::info('locOrgId : ');
				        // Log::info($locOrgId);
				        // Log::info('serId : ');
				        // Log::info($serId);
				        // Log::info('content : ');
				        // Log::info($content);
				        // Log::info('folderId : ');
				        // Log::info($folderId);

	                	$remindBeforeMillis = 0;
	                	if(isset($entryObj->remindBeforeMillis))
	                		$remindBeforeMillis = $entryObj->remindBeforeMillis;
	                	
	                	$repeatDuration = "";
	                	if(isset($entryObj->repeatDuration))
	                		$repeatDuration = $entryObj->repeatDuration;
                        $tagList = $entryObj->tagList;
                        
				        if(is_array($tagList))
				        	$tagsArr = $tagList;
						else
				        	$tagsArr = json_decode($tagList);

				        $tagsArr = sracDecryptNumberArrayData($tagsArr, $userSession);

                        if(!isset($isMarked))
                            $isMarked = 0;
				        
				        if(!isset($contentIsLocked)) {
				            $contentIsLocked = Config::get('app_config.default_content_lock_status');
				        }

				        if(!isset($contentIsShareEnabled)) {
				            $contentIsShareEnabled = Config::get('app_config.default_content_share_status');
				        }
                        
		                $orgId = 0;
		                $orgEmpId = 0;
		                if(isset($locOrgId) && $locOrgId > 0)
		           		{
		           			$orgId = $orgRefToSerMap[$locOrgId];
		           			$orgEmpId = $orgRefToSerEmpMap[$locOrgId];
						}
		                
		                $depMgmtObj = New ContentDependencyManagementClass;
		            	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
		            	$depMgmtObj->setCurrentLoginToken($loginToken);
		            	$content = urldecode($content);
		            	$contentResponse = $depMgmtObj->addEditContent($serId, $content, $contentTitle, $contentType, $folderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $contentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, array(), "", $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive, $contentIsRemoved, $contentRemovedAt);
                        $id = $contentResponse["syncId"];
                        $finalContentResponse[$i]["syncId"] = sracEncryptNumberData($id, $userSession);
                        
				        // Log::info('contentResponse : ');
				        // Log::info($contentResponse);
				        
                        if($orgId > 0)
						{								
	   						$this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $id);	
						}
						else
						{
			           		$this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $id);
						}
                    }
                }
                $response["contentResponse"] = $finalContentResponse;
                
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
     * Sync User Quota.
     *
     * @return json array
     */
    public function userQuotaSync()
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
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                
                if(isset($userConstant))
                {
                    $status = 1;  
                    
                    $attachmentSpaceAllotted = $userConstant->attachment_kb_allotted;
        			$response['spaceAllotted'] = $attachmentSpaceAllotted;
                    
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);                  
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
     * Sync User Used Quota.
     *
     * @return json array
     */
    public function userQuotaUsageSync()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');      
        $encOrgId = Input::get('orgId');
        $attachmentSpaceAvailable = Input::get('attachmentSpaceAvailable');
        $dbSize = Input::get('dbSize');
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
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
	                
                if(isset($userConstant))
                {
                    $status = 1;

                    $userConstant->attachment_kb_available = $attachmentSpaceAvailable;
                    $userConstant->db_size = $dbSize;
                    $userConstant->save();  
                    
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);                  
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
     * Sync User Share Rights.
     *
     * @return json array
     */
    public function userShareRightsSync()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');

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
					$modelObj = New OrgEmployeeConstant;
					$modelObj->setConnection($orgDbConName);					
					$constantObj = $modelObj->ofEmployee($orgEmpId)->first();
				}
				else
				{
                	$constantObj = AppuserConstant::ofUser($userId)->first();
				}
				
                if(isset($constantObj))
                {
                    $status = 1; 
	                    
	                $sracShareEnabled = $constantObj->is_srac_share_enabled;
	                $socShareEnabled = $constantObj->is_soc_share_enabled;
	                $socFacebookEnabled = $constantObj->is_soc_facebook_enabled;
	                $socTwitterEnabled = $constantObj->is_soc_twitter_enabled;
	                $socLinkedinEnabled = $constantObj->is_soc_linkedin_enabled;
	                $socWhatsappEnabled = $constantObj->is_soc_whatsapp_enabled;
	                $socEmailEnabled = $constantObj->is_soc_email_enabled;
	                $socSmsEnabled = $constantObj->is_soc_sms_enabled;
	                $socOtherEnabled = $constantObj->is_soc_other_enabled; 
	                
	                $sracOrgShareEnabled = 0;
	                $sracRetailShareEnabled = 0;
	                $sracCopyToProfileEnabled = 0;
	            	if(isset($orgDbConName)) {
			            $sracOrgShareEnabled = $constantObj->is_srac_org_share_enabled;
			            $sracRetailShareEnabled = $constantObj->is_srac_retail_share_enabled;
			            $sracCopyToProfileEnabled = $constantObj->is_copy_to_profile_enabled;
					}
                    
	                $response['sracShareEnabled'] = $sracShareEnabled;
	            	$response['sracOrgShareEnabled'] = $sracOrgShareEnabled;
		            $response['sracRetailShareEnabled'] = $sracRetailShareEnabled;
		            $response['sracCopyToProfileEnabled'] = $sracCopyToProfileEnabled;
		            
	                $response['socShareEnabled'] = $socShareEnabled;
	                $response['socFacebookEnabled'] = $socFacebookEnabled;
	                $response['socTwitterEnabled'] = $socTwitterEnabled;
	                $response['socLinkedinEnabled'] = $socLinkedinEnabled;
	                $response['socWhatsappEnabled'] = $socWhatsappEnabled;
	                $response['socEmailEnabled'] = $socEmailEnabled;
	                $response['socSmsEnabled'] = $socSmsEnabled;
	                $response['socOtherEnabled'] = $socOtherEnabled;                                
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
     * Sync User Share Rights.
     *
     * @return json array
     */
    public function periodicDataSync()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $groupForSync = Input::get('groupForSync');
        $syncTs = Input::get('lastSyncTs');

        
        $groupForSync = json_decode($groupForSync);

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
				$passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
				
				$tsStrLen = strlen($syncTs."");
				if($tsStrLen > 10)
				{
					$diff = $tsStrLen - 10;
					$divisor = pow ( 10 , $diff );
					$syncTs = intval($syncTs/$divisor);					
				}		
						
				$utcDt = Carbon::createFromTimeStampUTC($syncTs);		
				$utcDt->subMinute();
				$dtStr = $utcDt->toDateTimeString();

                $perDepMgmtObj = New ContentDependencyManagementClass;
                $perDepMgmtObj->withOrgKey($user, "");

                $cloudStorageTypeList = $perDepMgmtObj->getAllCloudStorageTypeListForUser();             
                $cloudStorageTypeCnt = count($cloudStorageTypeList);

                $cloudCalendarTypeList = $perDepMgmtObj->getAllCloudCalendarTypeListForUser();             
                $cloudCalendarTypeCnt = count($cloudCalendarTypeList);

                $cloudMailBoxTypeList = $perDepMgmtObj->getAllCloudMailBoxTypeListForUser();             
                $cloudMailBoxTypeCnt = count($cloudMailBoxTypeList);
				
				$userConstant = $user->userConstants;
				$userConstants['isPremium'] = $user->is_premium;
                $userConstants['isAccountDisabled'] = $user->is_account_disabled;
				$userConstants['attachmentRetainDays'] = $userConstant->attachment_retain_days;
				
                $hasPasscode = $userConstant->passcode_enabled;
                if(!isset($hasPasscode))
                	$hasPasscode = 0;
                	  
				$passcode = "";
                if($hasPasscode == 1 && isset($userConstant->passcode) && $userConstant->passcode != "")  
                    $passcode = Crypt::decrypt($userConstant->passcode);  
                        		
				$userConstants['hasPasscode'] = $hasPasscode;		
				$userConstants['passcode'] = $passcode;
				
				$orgConstantArr = array();
				$orgConstantArr['defFolderId'] = sracEncryptNumberData($userConstant->def_folder_id, $userSession);
				$folderName = '';
				$folderIconCode = '';
				$folderIsFavorited = 0;
				if(isset($userConstant->defaultFolder)) {
					$folderName = $userConstant->defaultFolder->folder_name;
					$folderIconCode = $userConstant->defaultFolder->icon_code;
					$folderIsFavorited = $userConstant->defaultFolder->is_favorited;
				}
				$orgConstantArr['defFolderName'] = $folderName;
				$orgConstantArr['defFolderIconCode'] = $folderIconCode;
				$orgConstantArr['defFolderIsFavorited'] = $folderIsFavorited;
				
				$hasFolderPasscode = $userConstant->folder_passcode_enabled; 
                if(!isset($hasFolderPasscode))
                	$hasFolderPasscode = 0;
                	  
				$folderPasscode = "";
				$passcodeFolderIdArr = array();
                if($hasFolderPasscode == 1 && isset($userConstant->folder_passcode) && $userConstant->folder_passcode != "")  
                {
                	$folderPasscode = Crypt::decrypt($userConstant->folder_passcode);
                	if($userConstant->folder_id_str != null) 
                    {
                        $folderIdStr = $userConstant->folder_id_str;
                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
                    }  
				}
				
				$orgConstantArr['hasFolderPasscode'] = $hasFolderPasscode;
				$orgConstantArr['folderPasscode'] = $folderPasscode;
				$orgConstantArr['folderIdArr'] = sracEncryptNumberArrayData($passcodeFolderIdArr, $userSession);
				
                $orgConstantArr['sracShareEnabled'] = $userConstant->is_srac_share_enabled;
                $orgConstantArr['socShareEnabled'] = $userConstant->is_soc_share_enabled;
                $orgConstantArr['socFacebookEnabled'] = $userConstant->is_soc_facebook_enabled;
                $orgConstantArr['socTwitterEnabled'] = $userConstant->is_soc_twitter_enabled;
                $orgConstantArr['socLinkedinEnabled'] = $userConstant->is_soc_linkedin_enabled;
                $orgConstantArr['socWhatsappEnabled'] = $userConstant->is_soc_whatsapp_enabled;
                $orgConstantArr['socEmailEnabled'] = $userConstant->is_soc_email_enabled;
                $orgConstantArr['socSmsEnabled'] = $userConstant->is_soc_sms_enabled;
		        $orgConstantArr['socOtherEnabled'] = $userConstant->is_soc_other_enabled;
				 
				$refId = 0;
				$orgList = array();
				$orgArr = array();
				
				//personal                	
                $orgDetails = array();
            	$orgDetails['ref_id'] = $refId;
            	$orgDetails['map_key'] = "";      
        		$orgDetails['user_email'] = $user->email;
				$orgDetails['allocKb'] = $userConstant->attachment_kb_allotted;
				$orgDetails['usedKb'] = $userConstant->attachment_kb_used;
				$orgDetails['availableKb'] = $userConstant->attachment_kb_available;		
				$orgDetails['const'] = $orgConstantArr;		      	
            	array_push($orgList, $orgDetails);
            	array_push($orgArr, array('id'=>0, 'empId'=>0, 'refId'=>$refId++));				
				
				$userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
	            foreach ($userOrganizations as $userOrg) 
                {
                	$orgDetails = array();
                	$orgId = $userOrg->organization_id;	                
	                $organization = $userOrg->organization;
	                if(isset($organization)) {
						$empEmail = $userOrg->emp_email;
	                	$empId = $userOrg->emp_id;
	                	
	                	$orgAttachmentSize[$orgId] = 0;
	                	
						$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);	                
		                
				        $depMgmtObj = New ContentDependencyManagementClass;
				       	$depMgmtObj->withOrgIdAndEmpId($orgId, $empId);
				       	$orgEmployee = $depMgmtObj->getEmployeeObject();
				       	$orgEmployeeConstant = $depMgmtObj->getEmployeeConstantObject();
				       	
				       	if(isset($orgEmployeeConstant))
				       	{
							$orgEmployeeIsActive = $depMgmtObj->getEmployeeIsActive();
						
							$orgConstantArr = array();
							$orgConstantArr['defFolderId'] = sracEncryptNumberData($orgEmployeeConstant->def_folder_id, $userSession);
							$folderName = '';
							$folderIconCode = '';
							$folderIsFavorited = 0;
							$defFolderObj = $depMgmtObj->getFolderObject($orgEmployeeConstant->def_folder_id);
							if(isset($defFolderObj)) {
								$folderName = $defFolderObj->folder_name;
								$folderIconCode = $defFolderObj->icon_code;
								$folderIsFavorited = $defFolderObj->is_favorited;
							}
							$orgConstantArr['defFolderName'] = $folderName;
							$orgConstantArr['defFolderIconCode'] = $folderIconCode;
							$orgConstantArr['defFolderIsFavorited'] = $folderIsFavorited;
							
							$hasFolderPasscode = $orgEmployeeConstant->folder_passcode_enabled; 
			                if(!isset($hasFolderPasscode))
			                	$hasFolderPasscode = 0;
			                	  
							$folderPasscode = "";
							$passcodeFolderIdArr = array();
			                if($hasFolderPasscode == 1 && isset($orgEmployeeConstant->folder_passcode) && $orgEmployeeConstant->folder_passcode != "")  
			                {
			                	$folderPasscode = Crypt::decrypt($orgEmployeeConstant->folder_passcode);
			                	if($orgEmployeeConstant->folder_id_str != null) 
			                    {
			                        $folderIdStr = $orgEmployeeConstant->folder_id_str;
			                        $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
			                    }  
							}
							
							$orgConstantArr['hasFolderPasscode'] = $hasFolderPasscode;
							$orgConstantArr['folderPasscode'] = $folderPasscode;
							$orgConstantArr['folderIdArr'] = sracEncryptNumberArrayData($passcodeFolderIdArr, $userSession);
							
			                $orgConstantArr['sracShareEnabled'] = $orgEmployeeConstant->is_srac_share_enabled;
				            $orgConstantArr['sracOrgShareEnabled'] = $orgEmployeeConstant->is_srac_org_share_enabled;
				            $orgConstantArr['sracRetailShareEnabled'] = $orgEmployeeConstant->is_srac_retail_share_enabled;
				            $orgConstantArr['sracCopyToProfileEnabled'] = $orgEmployeeConstant->is_copy_to_profile_enabled;
			                $orgConstantArr['socShareEnabled'] = $orgEmployeeConstant->is_soc_share_enabled;
			                $orgConstantArr['socFacebookEnabled'] = $orgEmployeeConstant->is_soc_facebook_enabled;
			                $orgConstantArr['socTwitterEnabled'] = $orgEmployeeConstant->is_soc_twitter_enabled;
			                $orgConstantArr['socLinkedinEnabled'] = $orgEmployeeConstant->is_soc_linkedin_enabled;
			                $orgConstantArr['socWhatsappEnabled'] = $orgEmployeeConstant->is_soc_whatsapp_enabled;
			                $orgConstantArr['socEmailEnabled'] = $orgEmployeeConstant->is_soc_email_enabled;
			                $orgConstantArr['socSmsEnabled'] = $orgEmployeeConstant->is_soc_sms_enabled;
					        $orgConstantArr['socOtherEnabled'] = $orgEmployeeConstant->is_soc_other_enabled;
			                
							$logoFilename = $organization->logo_filename;
							$orgLogoUrl = "";
							$orgLogoThumbUrl = "";
							if(isset($logoFilename) && $logoFilename != "")
							{
								$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
								$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
							}
							$orgInactivityDayCount = $organization->employee_inactivity_day_count;
                			$orgAttachmentRetainDays = $organization->org_attachment_retain_days;

							$orgEmpId = $orgEmployee->employee_id;

                			$isEmpFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $orgEmpId);
                			$isEmpScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($orgId, $orgEmpId);

		                	$encOrgId = Crypt::encrypt($orgId."_".$orgEmpId);
		                	$orgDetails['ref_id'] = $refId;
		                	$orgDetails['map_key'] = $orgEmployee->org_emp_key;
		                	$orgDetails['key'] = $encOrgId;
							$orgDetails['code'] = $organization->org_code;
		                	$orgDetails['reg_name'] = $organization->regd_name;
		                	$orgDetails['sys_name'] = $organization->system_name;
	                		$orgDetails['is_app_pin_enforced'] = $organization->is_app_pin_enforced;
	                		$orgDetails['is_file_save_share_enabled'] = $isEmpFileSaveShareEnabled;
	                		$orgDetails['is_screen_share_enabled'] = $isEmpScreenShareEnabled;
	                		$orgDetails['base_redirection_code'] = isset($organization->baseRedirection) ? $organization->baseRedirection->redirection_code : '';
		                	$orgDetails['logo_url'] = $orgLogoUrl;
		                	$orgDetails['logo_thumb_url'] = $orgLogoThumbUrl;
		                	$orgDetails['logo_filename'] = $logoFilename;
		                	$orgDetails['user_no'] = $orgEmployee->employee_no;
		                	$orgDetails['user_name'] = $orgEmployee->employee_name;
		                	$orgDetails['user_department'] = isset($orgEmployee->department_name)?$orgEmployee->department_name:"";
		                	$orgDetails['user_designation'] = isset($orgEmployee->designation_name)?$orgEmployee->designation_name:"";
	                		$orgDetails['user_email'] = $orgEmployee->email;
		                	$orgDetails['user_status'] = $orgEmployeeIsActive;
							$orgDetails['allocKb'] = $orgEmployeeConstant->attachment_kb_allotted;
							$orgDetails['usedKb'] = $orgEmployeeConstant->attachment_kb_used;
							$orgDetails['availableKb'] = $orgEmployeeConstant->attachment_kb_available;		
							$orgDetails['const'] = $orgConstantArr;		
            				$orgDetails['org_attachment_retain_days'] = $orgAttachmentRetainDays;
							$orgDetails['org_inactivity_day_count'] = $orgInactivityDayCount;
		                	
		                	array_push($orgList, $orgDetails);
		                	array_push($orgArr, array('id'=>$orgId, 'empId'=>$empId, 'refId'=>$refId++));
						}
					}
				}
                
                $delTagList = array();
	            $delFolderList = array();
	            $delSourceList = array();
	            $delContentList = array();
                
                $tagList = array();
	            $folderList = array();
	            $sourceList = array();
	            $contentList = array();
	            $groupList = array();
                foreach($orgArr as $org)
                {
                	$orgId = $org['id'];
                	$orgEmpId = $org['empId'];
                	$orgRefId = $org['refId'];
                	
                	$userOrEmpId = $userId;
                	if($orgId > 0 && $orgEmpId > 0)
                		$userOrEmpId = $orgEmpId;
				
					$pkIdPrefix = 'appuser_';
					if($orgId > 0)
					{
						$pkIdPrefix = 'employee_';
					}
					$userRelFieldId = $pkIdPrefix.'id';
                	
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
                	
                	$userTags = $depMgmtObj->getAllTagsToBeSynced($dtStr);	
                	if(isset($userTags) && count($userTags)>0)
                	{						
	                	$pkId = $pkIdPrefix.'tag_id';
	                	foreach ($userTags as $tag) 
		                {
		                	$tagObj = array();
		                    $tagObj['name'] = $tag->tag_name;
		                    $tagObj['syncId'] = sracEncryptNumberData($tag->$pkId, $userSession);
		                    $tagObj['orgId'] = $orgRefId;
		                    array_push($tagList, $tagObj);
		                }
					}
	                
	                $userFolders = $depMgmtObj->getAllFoldersToBeSynced($dtStr);
	                if(isset($userFolders) && count($userFolders)>0)
                	{						
	                	$pkId = $pkIdPrefix.'folder_id';	
		                foreach ($userFolders as $folder) 
		                {
		                	$folderObj = array();
		                    $folderObj['name'] = $folder->folder_name;
		                    $folderObj['iconCode'] = $folder->icon_code;
		                    $folderObj['isFavorited'] = $folder->is_favorited;
		                    $folderObj['folderType'] = $folder->folder_type_id;
	                		$folderObj['virtualFolderSenderEmail'] = $folder->virtual_folder_sender_email;
	                		$folderObj['appliedFilters'] = $folder->applied_filters;
		                    $folderObj['syncId'] = sracEncryptNumberData($folder->$pkId, $userSession);
		                    $folderObj['orgId'] = $orgRefId;
		                    array_push($folderList, $folderObj);
		                }
					}
	                
	                $userSources = $depMgmtObj->getAllSourcesToBeSynced($dtStr);
	                if(isset($userSources) && count($userSources)>0)
                	{						
	                	$pkId = $pkIdPrefix.'source_id';	
		                foreach ($userSources as $source) 
		                {
		                	$sourceObj = array();
		                    $sourceObj['name'] = $source->source_name;
		                    $sourceObj['syncId'] = sracEncryptNumberData($source->$pkId, $userSession);
		                    $sourceObj['orgId'] = $orgRefId;
		                    array_push($sourceList, $sourceObj);
		                }
					}
	                
	                $delSources = $depMgmtObj->getAllDeletedSourcesToBeSynced($dtStr);
	                foreach ($delSources as $delObject) 
	                {
	                	$delObj = array();
	                    $delObj['syncId'] = sracEncryptNumberData($delObject->id, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delSourceList, $delObj);
	                } 
	                
	                $delFolders = $depMgmtObj->getAllDeletedFoldersToBeSynced($dtStr);
	                foreach ($delFolders as $delObject) 
	                {
	                	$delObj = array();
	                    $delObj['syncId'] = sracEncryptNumberData($delObject->id, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delFolderList, $delObj);
	                } 
	                
	                $delTags = $depMgmtObj->getAllDeletedTagsToBeSynced($dtStr);
	                foreach ($delTags as $delObject) 
	                {
	                	$delObj = array();
	                    $delObj['syncId'] = sracEncryptNumberData($delObject->id, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delTagList, $delObj);
	                } 
	                
	                $isFolder = TRUE;
	                $delContents = $depMgmtObj->getAllDeletedContentsToBeSynced($isFolder, $dtStr);
	                foreach ($delContents as $delObject) 
	                {
	                	$delObj = array();
	                    $delObj['isFolder'] = 1;
	                    $delObj['syncId'] = sracEncryptNumberData($delObject->id, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delContentList, $delObj);
	                }
	                $softDelContents = $depMgmtObj->getAllSoftDeletedContentsToBeSynced($isFolder, $dtStr);
	                foreach ($softDelContents as $delObject) 
	                {
	                	$pkId = $pkIdPrefix.'content_id';
	                	$contentId = $delObject->$pkId;

	                	$delObj = array();
	                    $delObj['isFolder'] = 1;
	                    $delObj['syncId'] = sracEncryptNumberData($contentId, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delContentList, $delObj);
	                }
	                
	                $performDownload = 1;	                
	                $userContents = $depMgmtObj->getAllContentsToBeSynced($isFolder, $dtStr);
		            if(isset($userContents) && count($userContents)>0)
                	{						
	                	$pkId = $pkIdPrefix.'content_id';
	                	$keyFolderId = $pkIdPrefix.'folder_id';
	                	$keyTagId = $pkIdPrefix.'tag_id';
		                foreach ($userContents as $content) 
		                {	
		                	$contentId = $content->$pkId;
		                	
							$tagsArr = array();	                    
			                $contentTags = $depMgmtObj->getContentTags($contentId, $userOrEmpId, $isFolder);
		                    foreach ($contentTags as $contentTag) 
		                    {
		                        array_push($tagsArr, $contentTag->tag_id);
		                    }
		                    $tagCnt = count($tagsArr);

		                    $attachmentsArr = array();
			                $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
		                    foreach ($contentAttachments as $contentAttachment) 
		                    {
		                    	if($contentAttachment->att_cloud_storage_type_id == 0)
								{
									$attUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
								}
								else
								{
									$attUrl = $contentAttachment->cloud_file_url; 
								}  
		                    	
		                    	$attachmentObj = array();
		                        $attachmentObj['name'] = $contentAttachment->filename;
		                        $attachmentObj['size'] = $contentAttachment->filesize;
	                            $attachmentObj['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
	                            $attachmentObj['cloudFileUrl'] = $contentAttachment->cloud_file_url;
	                            $attachmentObj['cloudFileId'] = $contentAttachment->cloud_file_id;
                    			$attachmentObj['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
	                            $attachmentObj['attCreateTs'] = $contentAttachment->create_ts;
	                            $attachmentObj['attUpdateTs'] = $contentAttachment->update_ts;
		                        $attachmentObj['url'] = $attUrl;	                        
		                        $attachmentObj['performDownload'] = $performDownload;
		                        $attachmentObj['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
			                  	array_push($attachmentsArr, $attachmentObj);
			                  	
			                  	//$orgAttachmentSize[$orgId] += $contentAttachment->filesize;
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
	                	
		                   	$contentObj = array();
		                    $contentObj['syncId'] = sracEncryptNumberData($contentId, $userSession);
		                    
							if($content->content_title === '' || $content->content_title ===null){
								$contentObj['content_title'] = 'No Title';
							}else{
								$contentObj['content_title'] = $content->content_title;
							}
				// 			$contentObj['content_title'] = $content->content_title;
		                    $contentObj['content'] = $decContent;
		                    $contentObj['colorCode'] = $content->color_code;
		                    $contentObj['isLocked'] = $content->is_locked;
                			$contentObj['isShareEnabled'] = $content->is_share_enabled;
		                    $contentObj['remindBeforeMillis'] = $content->remind_before_millis;
		                    $contentObj['repeatDuration'] = $content->repeat_duration;
							$contentObj['isCompleted'] = $content->is_completed;
							$contentObj['isSnoozed'] = $content->is_snoozed;
							$contentObj['reminderTimestamp'] = isset($content->reminder_timestamp) ? $content->reminder_timestamp : 0;
	                    	$contentObj['encContent'] = $encDecContent;
		                    $contentObj['contentType'] = $content->content_type_id;
		                    $contentObj['folderId'] = sracEncryptNumberData($content->$keyFolderId, $userSession);
		                    $contentObj['isMarked'] = $content->is_marked;
		                    $contentObj['source'] = sracEncryptNumberData($content->source_id, $userSession);
		                    $contentObj['createTimeStamp'] = $content->create_timestamp;
		                    $contentObj['updateTimeStamp'] = $content->update_timestamp;
							$contentObj['syncWithCloudCalendarGoogle'] = $content->sync_with_cloud_calendar_google;
							$contentObj['syncWithCloudCalendarOnedrive'] = $content->sync_with_cloud_calendar_onedrive;
		                    $contentObj['fromTimeStamp'] = $frmTs;
		                    $contentObj['toTimeStamp'] = $toTs;
		                    $contentObj['tagCnt'] = $tagCnt;
		                    $contentObj['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
		                    $contentObj['attachmentCnt'] = $attachmentCnt;
		                    $contentObj['attachments'] = $attachmentsArr;
		                    $contentObj['sharedByEmail'] = $sharedByEmail;
		                    $contentObj['orgId'] = $orgRefId;
		                	$contentObj['isRemoved'] = $content->is_removed;
		                	$contentObj['removedAt'] = $content->removed_at;
		                    
		                    array_push($contentList, $contentObj);
		                } 
					}
	                
	                $isFolder = FALSE;

	                $delGrpContents = $depMgmtObj->getAllDeletedContentsToBeSynced($isFolder, $dtStr);
	                foreach ($delGrpContents as $delObject) 
	                {
	                	$delObj = array();
	                    $delObj['isFolder'] = 0;
	                    $delObj['syncId'] = sracEncryptNumberData($delObject->id, $userSession);
	                    $delObj['orgId'] = $orgRefId;
	                    array_push($delContentList, $delObj);
	                }
	                
	                $performDownload = 1;	                
	                $userGroups = $depMgmtObj->getAllGroupsFoUser(NULL);
	                foreach($userGroups as $userGroup) 
	                {	                   
						$grpId = $userGroup->group_id;
						$isTwoWay = $userGroup->is_two_way;
						$allocKb = $userGroup->allocated_space_kb;
						$usedKb = $userGroup->used_space_kb;
						$name = $userGroup->name;
						$description = $userGroup->description;
						
						$photoFilename = "";
						$groupPhotoUrl = "";
						$groupPhotoThumbUrl = "";						
						$photoFilename = $userGroup->img_server_filename;
						if(isset($photoFilename) && $photoFilename != "")
						{
							$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
							$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
						}				

						$isAdmin = 0;
						$hasPostRight = 1;
						$isFavorited = 0;
						$isGrpLocked = 0;
						
						$groupMember = $depMgmtObj->getGroupMemberObject($grpId);
		    			if(isset($groupMember))  
		    			{
							$isAdmin = $groupMember->is_admin;
							$isFavorited = $groupMember->is_favorited;
							$isGrpLocked = $groupMember->is_locked;
							if($orgId > 0)
								$hasPostRight = $groupMember->has_post_right;
						}
						
						$isPrimarySynced = TRUE;
						if(isset($groupForSync) && count($groupForSync) > 0)
						{
							foreach($groupForSync as $syncGrp)
							{
								$sGrpId = sracDecryptNumberData($syncGrp->grpId, $userSession);
								$sGrpEncOrgId = $syncGrp->orgId;				
								$sGrpDecOrgId = OrganizationClass::getOrgIdFromOrgKey($sGrpEncOrgId);	
								if($sGrpId == $grpId && $sGrpDecOrgId == $orgId)
								{
									$isPrimarySynced = FALSE;
								}
							}
						}
						
						$grpContentsArr = array();		                
						$grpContents = $depMgmtObj->getAllContentsToBeSynced($isFolder, $dtStr, $grpId, $isPrimarySynced);
						if(isset($grpContents) && count($grpContents) > 0)
						{
							foreach($grpContents as $grpContent)
		                	{
		                		$grpContentId = $grpContent->group_content_id;
		                		
								$grpContentDetails = array();		                    					
								$tagsArr = array();
				                $contentTags = $depMgmtObj->getContentTags($grpContentId, $userOrEmpId, $isFolder);
			                    foreach ($contentTags as $contentTag) 
			                    {
									array_push($tagsArr, $contentTag->tag_id);
			                    }
			                    $tagCnt = count($tagsArr);

			                    $attachmentsArr = array();
				                $contentAttachments = $depMgmtObj->getContentAttachments($grpContentId, $isFolder);
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

			                    	$attObj = array();      
			                        $attObj['name'] = $contentAttachment->filename;
			                        $attObj['pathname'] = $contentAttachment->server_filename;
			                        $attObj['size'] = $contentAttachment->filesize;
		                            $attObj['cloudStorageTypeId'] = $contentAttachment->att_cloud_storage_type_id;
		                            $attObj['cloudFileUrl'] = $contentAttachment->cloud_file_url;
		                            $attObj['cloudFileId'] = $contentAttachment->cloud_file_id;
                    				$attObj['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
		                            $attObj['attCreateTs'] = $contentAttachment->create_ts;
		                            $attObj['attUpdateTs'] = $contentAttachment->update_ts;
			                        $attObj['url'] = $attachmentUrl;
			                        $attObj['performDownload'] = $performDownload;
			                        $attObj['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
			                       
			                       	array_push($attachmentsArr, $attObj);
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
			                    if($grpContent->content_title === '' || $grpContent->content_title ===null){
        							$grpContentDetails['content_title'] = 'No Title';
        						}else{
        							$grpContentDetails['content_title'] = $grpContent->content_title;
        						}
								// $grpContentDetails['content_title'] = $grpContent->content_title;
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
			                    $grpContentDetails['groupId'] = sracEncryptNumberData($grpContent->group_id, $userSession);
			                    $grpContentDetails['isMarked'] = $grpContent->is_marked;
			                    $grpContentDetails['createTimeStamp'] = $grpContent->create_timestamp;
			                    $grpContentDetails['updateTimeStamp'] = $grpContent->update_timestamp;
								$grpContentDetails['syncWithCloudCalendarGoogle'] = $grpContent->sync_with_cloud_calendar_google;
								$grpContentDetails['syncWithCloudCalendarOnedrive'] = $grpContent->sync_with_cloud_calendar_onedrive;
			                    $grpContentDetails['fromTimeStamp'] = $frmTs;
			                    $grpContentDetails['toTimeStamp'] = $toTs;
			                    $grpContentDetails['tagCnt'] = $tagCnt;
			                    $grpContentDetails['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
			                    $grpContentDetails['attachmentCnt'] = $attachmentCnt;
			                    $grpContentDetails['attachments'] = $attachmentsArr;
		                    	$grpContentDetails['sharedByEmail'] = $sharedByEmail;
								
								array_push($grpContentsArr, $grpContentDetails);
							}						
						}
						
						$groupObj = array();				
						$groupObj["syncId"] = sracEncryptNumberData($grpId, $userSession);
						$groupObj["name"] = $name;
						// $groupObj["isPrimarySynced"] = $isPrimarySynced;
						$groupObj["description"] = $description;
						$groupObj["isUserAdmin"] = $isAdmin;
						$groupObj["hasPostRight"] = $hasPostRight;
						$groupObj["isFavorited"] = $isFavorited;
						$groupObj["isGrpLocked"] = $isGrpLocked;
						$groupObj["isTwoWay"] = $isTwoWay;
						$groupObj["allocKb"] = $allocKb;
						$groupObj["usedKb"] = $usedKb;
						$groupObj["photoUrl"] = $groupPhotoUrl;
						$groupObj["photoThumbUrl"] = $groupPhotoThumbUrl;
						$groupObj["photoFilename"] = $photoFilename;	
						$groupObj["contentArr"] = $grpContentsArr;
						$groupObj["contentCnt"] = count($grpContentsArr);
	                    $groupObj['orgId'] = $orgRefId;
	                    
	                    array_push($groupList, $groupObj);
	                }
				}
                
                $response['orgCnt'] = count($orgList);
                $response['orgArr'] = $orgList;
                $response['tagCnt'] = count($tagList);
                $response['tagArr'] = $tagList;
                $response['folderCnt'] = count($folderList);
                $response['folderArr'] = $folderList;
                $response['sourceCnt'] = count($sourceList);
                $response['sourceArr'] = $sourceList;
                $response['groupCnt'] = count($groupList);
                $response['groupArr'] = $groupList;
                $response['contentCnt'] = count($contentList);
                $response['contentArr'] = $contentList;
                $response['delTagCnt'] = count($delTagList);
                $response['delTagArr'] = $delTagList;
                $response['delFolderCnt'] = count($delFolderList);
                $response['delFolderArr'] = $delFolderList;
                $response['delSourceCnt'] = count($delSourceList);
                $response['delSourceArr'] = $delSourceList;
                $response['delContentCnt'] = count($delContentList);
                $response['delContentArr'] = $delContentList;
                $response['userConst'] = $userConstants;
                $response['cloudStorageTypeCnt'] = $cloudStorageTypeCnt;
                $response['cloudStorageTypeArr'] = $cloudStorageTypeList;
                $response['cloudCalendarTypeCnt'] = $cloudCalendarTypeCnt;
                $response['cloudCalendarTypeArr'] = $cloudCalendarTypeList;
                $response['cloudMailBoxTypeCnt'] = $cloudMailBoxTypeCnt;
                $response['cloudMailBoxTypeArr'] = $cloudMailBoxTypeList;
                
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