<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\Api\AppuserContact;
use App\Models\Api\DependencyType;
use App\Models\Api\DeletedDependency;
use App\Models\ContentType;
use App\Models\FolderType;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserFolder;
use App\Models\Org\Api\OrgEmployeeFolder;
use App\Models\Api\AppuserSource;
use App\Models\Org\Api\OrgEmployeeSource;
use App\Models\Api\AppuserTag;
use App\Models\Org\Api\OrgEmployeeTag;
use App\Models\Org\OrganizationUser;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupMemberInvite;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
use App\Models\Api\CloudStorageType;
use App\Models\Api\AppuserCloudStorageToken;
use App\Models\Api\CloudCalendarType;
use App\Models\Api\AppuserCloudCalendarToken;
use App\Models\Api\CloudMailBoxType;
use App\Models\Api\AppuserCloudMailBoxToken;
use App\Models\Api\AppKeyMapping;
use App\Models\Api\AppuserContentCloudCalendarMapping;
use App\Models\Org\Api\OrgEmployeeContentCloudCalendarMapping;
use App\Models\Api\AppuserContentAdditionalData;
use App\Models\Org\Api\OrgEmployeeContentAdditionalData;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupMemberInvite;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Org\Api\OrgTemplate;
use App\Models\Org\Api\OrgSystemTag;
use App\Models\Api\SysVideoConference;
use App\Models\Api\SysVideoConferenceParticipant;
use App\Models\Api\SysVideoConferenceInvite;
use App\Models\Org\Api\OrgVideoConference;
use App\Models\Org\Api\OrgVideoConferenceParticipant;
use App\Models\Org\OrganizationVideoConferenceInvite;
use App\Models\Api\PremiumReferralCode;
use App\Models\Api\PremiumCoupon;
use App\Models\Api\PremiumCouponCode;
use App\Models\Api\SessionType;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\FileUploadClass;
use App\Libraries\MailClass;
use App\Libraries\ContentListFormulationClass;
use App\Libraries\AttachmentCloudStorageManagementClass;
use App\Libraries\CloudCalendarManagementClass;
use DB;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use App\Libraries\FolderFilterUtilClass;
use View;

class ContentDependencyManagementClass 
{
    use CloudMessagingTrait;	
    use OrgCloudMessagingTrait;	
	
	protected $userId = 0;	  
    protected $orgId = 0;
	protected $orgEmpId = 0;
	protected $orgDbConName = NULL;
	protected $currLoginToken = NULL;
	protected $appKey = NULL;
	protected $appKeyMapping = NULL;
	protected $appKeyMappingId = 0;
	protected $appuserSession = NULL;

	protected $cloudCalendarSyncOperationTypeCreation = 'INS';
	protected $cloudCalendarSyncOperationTypeModification = 'MOD';
	protected $cloudCalendarSyncOperationTypeDeletion = 'DEL';
		
	public function __construct()
    {   
    	
    }
		
	public function withOrgKey($user, $encOrgId)
    {   
		$this->userId = $user->appuser_id; 
			           
        if(isset($encOrgId) && $encOrgId != "")
   		{
   			$this->orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
			$this->orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
   			
   			if($this->orgId > 0)
	        {
				$this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->orgId);
			}
		}
    }
    
    public function withOrgIdAndEmpId($orgId, $empId)
    {
    	$this->orgId = $orgId;
    	$this->orgEmpId = $empId;
   			
        if($this->orgId > 0)
        {
			$this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->orgId);
			$this->userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
		}
	}
    
    public function withOrgId($orgId)
    {
    	$this->orgId = $orgId;
   			
        if($this->orgId > 0)
        {
			$this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->orgId);
		}
	}
    
    public function withUserIdOrgIdAndEmpId($user, $orgId, $empId)
    {
		$this->userId = $user->appuser_id;
    	$this->orgId = $orgId;
    	$this->orgEmpId = $empId;
   			
        if($this->orgId > 0)
        {
			$this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->orgId);
			$this->userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $empId);
		}
	}
    
    public function setCurrentLoginToken($token)
    {
    	$this->currLoginToken = $token;
    	$this->setupAppuserSession();
	}

	private function setupAppuserSession()
	{
		if(isset($this->userId) && $this->userId != "" && isset($this->currLoginToken) && $this->currLoginToken != "")
		{
            $userSession = CommonFunctionClass::getUserSession($this->userId, $this->currLoginToken);
            if(isset($userSession))
            {
            	$this->appuserSession = $userSession;
            	if(isset($userSession->appKeyMapping))
                {
                	$this->appKeyMapping = $userSession->appKeyMapping;
                }
            }
		}
	}
	
	public function getAppuserSession()
	{
		return $this->appuserSession;
	}
    
    public function setAppKeyStr($appKeyStr)
    {
    	if(isset($appKeyStr) && trim($appKeyStr) != "")
    	{
    		$appKeyStr = trim($appKeyStr);

            $mappedAppKey = AppKeyMapping::active()->byAppKey($appKeyStr)->first();
            if(isset($mappedAppKey))
            {
            	$this->appKey = $appKeyStr;
                $this->appKeyMapping = $mappedAppKey;
                $this->appKeyMappingId = $mappedAppKey->app_key_mapping_id;
            }
    	}
	}
	
	public function getAppKeyMapping()
	{
		return $this->appKeyMapping;
	}
	
	public function getAppKeyMappingId()
	{
		return $this->appKeyMappingId;
	}
	
	public function getOrganizationId()
	{
		return $this->orgId;
	}
	
	public function getUserId()
	{
		return $this->userId;
	}
	
	public function getCurrentLoginToken()
	{
		return $this->currLoginToken;
	}
	
	public function getOrganizationEmployeeId()
	{
		return $this->orgEmpId;
	}
	
	public function getOrganizationObject()
	{
		return $organization = Organization::ofOrganization($this->orgId)->first();
	}
	
	public function getAppKeyMappingHasCloudStorage()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_cloud_storage : 1;
	}
	
	public function getAppKeyMappingHasTypeReminder()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_type_reminder : 1;
	}
	
	public function getAppKeyMappingHasTypeCalendar()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_type_calendar : 1;
	}
	
	public function getAppKeyMappingHasImportOptions()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_import_options : 1;
	}
	
	public function getAppKeyMappingHasIntegrationOptions()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_integration_options : 1;
	}
	
	public function getAppKeyMappingHasVideoConference()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_video_conference : 1;
	}
	
	public function getAppKeyMappingHasSourceSelection()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_source_selection : 1;
	}
	
	public function getAppKeyMappingHasFolderSelection()
	{
		return isset($this->appKeyMapping) ? $this->appKeyMapping->has_folder_selection : 1;
	}
    
    public function addEditFolder($id, $name, $iconCode, $isFavorited = 0, $folderTypeId = 0, $appliedFilters = NULL, $virtualSenderEmail = NULL)
    {
		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
		$name = trim($name);
		
		if(!isset($iconCode) || $iconCode == '')
			$iconCode = Config::get('app_config.default_folder_icon_code');
		
		if(!isset($isFavorited) || $isFavorited == '')
			$isFavorited = Config::get('app_config.default_folder_is_favorited');

		if($folderTypeId == FolderType::$TYPE_VIRTUAL_FOLDER_ID || $folderTypeId == FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID)
		{

		}
		else
		{
			$appliedFilters = NULL;
		}
		
        $folderDetails = array();
        $folderDetails['folder_name'] = $name;	
        $folderDetails['icon_code'] = $iconCode;	
        $folderDetails['is_favorited'] = $isFavorited;	
        $folderDetails['folder_type_id'] = $folderTypeId;	
        $folderDetails['applied_filters'] = $appliedFilters;

        if(isset($virtualSenderEmail) && trim($virtualSenderEmail) != "")
        {
       		$folderDetails['virtual_folder_sender_email'] = trim($virtualSenderEmail);
        }

        if($folderTypeId != FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID)
        {
       		$folderDetails['virtual_folder_sender_email'] = "";
        }

		if(isset($this->orgDbConName))
    	{		       			
			$modelObj = New OrgEmployeeFolder;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
		}
		else
		{
			$modelObj = New AppuserFolder;
		}
		
        $userFolder = $modelObj->byId($id)->first();
		if(isset($userFolder))
        {
       		$folderDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
       		
        	$userFolder->update($folderDetails);
			$syncId = $id;
		}
		else
		{
        	if(isset($this->orgDbConName))
        	{
       			$folderDetails['employee_id'] = $this->orgEmpId;
       				       			
				$modelObj = New OrgEmployeeFolder;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$tableName = $modelObj->table;	
				$modelObj = $modelObj->ofEmployee($this->orgEmpId);							
						       			
				$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
			}
			else
        	{
       			$folderDetails['appuser_id'] = $this->userId;
				
				$modelObj = New AppuserFolder;
				$tableName = $modelObj->table;
				$modelObj = $modelObj->ofUser($this->userId);
				
				$insModelObj = DB::table($tableName);
			}
								
			$conflictResolved = FALSE;
			$nameSr = 1;
			$newName = $name;
			do{
				$objExists = $modelObj->byName($newName)->first();
				if(isset($objExists))
				{
					$hasConflict = 1;
					$newName = $name." ".($nameSr++);
				}
				else
				{
					$conflictResolved = TRUE;
					$folderDetails['folder_name'] = $newName;
				}
			}while(!$conflictResolved);
			
       		$folderDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();	
			$syncId = $insModelObj->insertGetId($folderDetails);
		}
		
		if($syncId > 0)
		{
			if(isset($this->orgDbConName))
    		{
    			$this->sendOrgFolderAddMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $syncId);
			}
			else
			{
				$this->sendFolderAddMessageToDevice($this->userId, $this->currLoginToken, $syncId);
			}
		}		
		
		$response["syncId"] = $syncId;
		$response["newName"] = $newName;
		$response["hasConflict"] = $hasConflict;
		
		return $response;
	}
    
    public function deleteFolder($id)
    {	
    	$response = array();	
		if($id > 0)
		{
			if(isset($this->orgDbConName))
	    	{		       			
				$modelObj = New OrgEmployeeFolder;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New AppuserFolder;
			}
			
	        $userFolder = $modelObj->byId($id)->isNotSentFolder()->first();
			if(isset($userFolder))
	        {
	        	$deletedContents = $this->performDeleteForVirtualFolderContents($userFolder);

	        	// $response['deletedContentsResponse'] = $deletedContents;

	        	$this->logFolderAsDeleted($id);
	        	$userFolder->delete();
	        	
				if(isset($this->orgDbConName))
        		{
        			$this->sendOrgFolderDeletedMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $id);
				}
				else
				{
					$this->sendFolderDeletedMessageToDevice($this->userId, $this->currLoginToken, $id);
				}    	
			}
		}
    	return $response;
	}

	public function performDeleteForVirtualFolderContents($folderObj)
	{
    	$response = array();
		if(isset($folderObj))
		{
			$userFolderTypeId = $folderObj->folder_type_id;

	        $response['userFolderTypeId'] = $userFolderTypeId;

       		$virtualFolderTypeId = FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID;
       		$virtualSenderFolderTypeId = FolderType::$TYPE_VIRTUAL_FOLDER_ID;
       		
        	if($userFolderTypeId == $virtualFolderTypeId || $userFolderTypeId == $virtualSenderFolderTypeId)
        	{
        		$userFolderAppliedFilters = $folderObj->applied_filters;

	        	$response['userFolderAppliedFilters'] = $userFolderAppliedFilters;

                $folderFilterUtilObj = New FolderFilterUtilClass;
                $folderFilterUtilObj->setFilterStr($userFolderAppliedFilters);

                $hasFilters = 1;

                $filArr = array();
                $filArr['chkIsConversation'] = $folderFilterUtilObj->getFilterValueIsConversation();
                $filArr['chkIsUntagged'] = $folderFilterUtilObj->getFilterValueIsUntagged();
                $filArr['chkIsLocked'] = $folderFilterUtilObj->getFilterValueIsLocked();
                $filArr['chkIsStarred'] = $folderFilterUtilObj->getFilterValueIsStarred();
                $filArr['chkIsLocked'] = $folderFilterUtilObj->getFilterValueIsRestricted();
                $filArr['chkDownloadStatus'] = $folderFilterUtilObj->getFilterValueDownloadStatus();
                $filArr['chkIsRestricted'] = $folderFilterUtilObj->getFilterValueIsRestricted();
                $filArr['filFromDate'] = $folderFilterUtilObj->getFilterValueStartDateTs();
                $filArr['filToDate'] = $folderFilterUtilObj->getFilterValueEndDateTs();
                $filArr['filTypeArr'] = $folderFilterUtilObj->getFilterValueContentType();
                $filArr['filFolderArr'] = $folderFilterUtilObj->getFilterValueFolder();
                $filArr['filGroupArr'] = $folderFilterUtilObj->getFilterValueGroup();
                $filArr['filSourceArr'] = $folderFilterUtilObj->getFilterValueSource();
                $filArr['filTagArr'] = $folderFilterUtilObj->getFilterValueTag();
                $filArr['filAttachmentTypeArr'] = $folderFilterUtilObj->getFilterValueAttachmentType();
                $filArr['filShowAttachment'] = $folderFilterUtilObj->getFilterValueAttachmentStatus();
                $filArr['filSenderEmail'] = $folderFilterUtilObj->getFilterValueSenderEmail();
                $filArr['filDateFilterType'] = $folderFilterUtilObj->getFilterValueDateFilterType();
                $filArr['filDateDayCount'] = $folderFilterUtilObj->getFilterValueDateFilterTypeDayCount();
            	$filArr['chkIsTrashed'] = 0;

                $chkShowFolder = $folderFilterUtilObj->getFilterValueIsShowFolder();
                $chkShowGroup = 0;//$folderFilterUtilObj->getFilterValueIsShowGroup();

                if(isset($chkShowFolder) && $chkShowFolder == 1)
                {
                	$filArr['chkShowFolder'] = $chkShowFolder;
	                $filArr['chkShowGroup'] = $chkShowGroup;

	                $empOrUserId = $this->getEmployeeOrUserId();
	                $isFolder = TRUE;
	                $isAllNotes = TRUE;
	                $isLocked = FALSE;
	                $folderOrGroupId = -1;
	                $searchStr = '';
	                $sortBy = 3;
	       			$sortOrder = -1;        		

	                $contentListFormulationObj = New ContentListFormulationClass;
                	$contentListFormulationObj->setWithIdEncryption(false);
	                $contentList = $contentListFormulationObj->formulateUserContentList($this, $this->orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

		        	// $response['contentList'] = $contentList;

	        		if(isset($contentList) && count($contentList) > 0)
	        		{
	        			for($i = 0; $i < count($contentList); $i++)
	        			{
	        				$contentObj = $contentList[$i];
	                        $contentId = $contentObj['id'];
				           	$contentSenderStr = $contentObj['senderStr'];
				           	$contentIsConversation = $contentObj['isConversation'];
		        			
		        			$response['contentId'.$i] = $contentId;
		        			$response['contentSenderStr'.$i] = $contentSenderStr;
		        			$response['contentIsConversation'.$i] = $contentIsConversation;

	                        $isConversation = FALSE;
		                    if(isset($contentIsConversation) && $contentIsConversation == 1)
		                    {
		                        $isConversation = TRUE;
                        		$this->deleteContent($contentId, $isFolder);
		                    }
		        			$response['isConversation'.$i.''] = $isConversation;
	        			}
	        		}
                }                
	                
        	}
		}
    	return $response;        	
	}
    
    public function addEditSource($id, $name)
    {
		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
		$name = trim($name);
		
        $sourceDetails = array();
        $sourceDetails['source_name'] = $name;		
		if(isset($this->orgDbConName))
    	{		       			
			$modelObj = New OrgEmployeeSource;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
		}
		else
		{
			$modelObj = New AppuserSource;
		}
		
        $userSource = $modelObj->byId($id)->first();
		if(isset($userSource))
        {
       		$sourceDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
       		
        	$userSource->update($sourceDetails);
			$syncId = $id;
		}
		else
		{
        	if(isset($this->orgDbConName))
        	{
       			$sourceDetails['employee_id'] = $this->orgEmpId;	
       				       			
				$modelObj = New OrgEmployeeSource;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$tableName = $modelObj->table;	
				$modelObj = $modelObj->ofEmployee($this->orgEmpId);							
						       			
				$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
			}
			else
        	{
       			$sourceDetails['appuser_id'] = $this->userId;
				
				$modelObj = New AppuserSource;
				$tableName = $modelObj->table;
				$modelObj = $modelObj->ofUser($this->userId);
				
				$insModelObj = DB::table($tableName);
			}
								
			$conflictResolved = FALSE;
			$nameSr = 1;
			$newName = $name;
			do{
				$objExists = $modelObj->byName($newName)->first();
				if(isset($objExists))
				{
					$hasConflict = 1;
					$newName = $name." ".($nameSr++);
				}
				else
				{
					$conflictResolved = TRUE;
					$sourceDetails['source_name'] = $newName;
				}
			}while(!$conflictResolved);
			
       		$sourceDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
			$syncId = $insModelObj->insertGetId($sourceDetails);
		}
		
		if($syncId > 0)
		{
			if(isset($this->orgDbConName))
    		{
    			$this->sendOrgSourceAddMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $syncId);
			}
			else
			{
				$this->sendSourceAddMessageToDevice($this->userId, $this->currLoginToken, $syncId);
			}
		}		
		
		$response["syncId"] = $syncId;
		$response["newName"] = $newName;
		$response["hasConflict"] = $hasConflict;
		
		return $response;
	}
    
    public function deleteSource($id)
    {		
		if($id > 0)
		{
			if(isset($this->orgDbConName))
	    	{		       			
				$modelObj = New OrgEmployeeSource;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New AppuserSource;
			}
			
	        $userSource = $modelObj->byId($id)->first();
			if(isset($userSource))
	        {
	        	$this->logSourceAsDeleted($id);
	        	$userSource->delete();
	        	
				if(isset($this->orgDbConName))
        		{
        			$this->sendOrgSourceDeletedMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $id);
				}
				else
				{
					$this->sendSourceDeletedMessageToDevice($this->userId, $this->currLoginToken, $id);
				}      	
			}
		}
	}
    
    public function addEditTag($id, $name)
    {
		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
		$name = trim($name);
		
        $tagDetails = array();
        $tagDetails['tag_name'] = $name;	
		if(isset($this->orgDbConName))
    	{		       			
			$modelObj = New OrgEmployeeTag;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
		}
		else
		{
			$modelObj = New AppuserTag;
		}
		
        $userTag = $modelObj->byId($id)->first();
		if(isset($userTag))
        {
       		$tagDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
       		
        	$userTag->update($tagDetails);
			$syncId = $id;
		}
		else
		{       		
        	if(isset($this->orgDbConName))
        	{
       			$tagDetails['employee_id'] = $this->orgEmpId;		       				
       				       			
				$modelObj = New OrgEmployeeTag;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$tableName = $modelObj->table;	
				$modelObj = $modelObj->ofEmployee($this->orgEmpId);							
						       			
				$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
			}
			else
        	{
       			$tagDetails['appuser_id'] = $this->userId;
				
				$modelObj = New AppuserTag;
				$tableName = $modelObj->table;
				$modelObj = $modelObj->ofUser($this->userId);
				
				$insModelObj = DB::table($tableName);
			}
								
			$conflictResolved = FALSE;
			$nameSr = 1;
			$newName = $name;
			do{
				$objExists = $modelObj->byName($newName)->first();
				if(isset($objExists))
				{
					$hasConflict = 1;
					$newName = $name." ".($nameSr++);
				}
				else
				{
					$conflictResolved = TRUE;
					$tagDetails['tag_name'] = $newName;
				}
			}while(!$conflictResolved);
			
       		$tagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
       		
			$syncId = $insModelObj->insertGetId($tagDetails);
		}
		
		if($syncId > 0)
		{
			if(isset($this->orgDbConName))
    		{
    			$this->sendOrgTagAddMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $syncId);
			}
			else
			{
				$this->sendTagAddMessageToDevice($this->userId, $this->currLoginToken, $syncId);
			}
		}	
		
		$response["syncId"] = $syncId;
		$response["newName"] = $newName;
		$response["hasConflict"] = $hasConflict;
		
		return $response;
	}
    
    public function deleteTag($id)
    {		
		if($id > 0)
		{
			if(isset($this->orgDbConName))
	    	{		       			
				$modelObj = New OrgEmployeeTag;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New AppuserTag;
			}
			
	        $userTag = $modelObj->byId($id)->first();
			if(isset($userTag))
	        {
	        	$this->logTagAsDeleted($id);
	        	$userTag->delete();
	        	
				if(isset($this->orgDbConName))
        		{
        			$this->sendOrgTagDeletedMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $id);
				}
				else
				{
					$this->sendTagDeletedMessageToDevice($this->userId, $this->currLoginToken, $id);
				}     	
			}
		}
	}

	private function getFormattedAttachmentName($filename) {
		$formattedFilename = '';
		if(isset($filename) && $filename != "") {
			$tmp = explode(".", $filename);
			
			if(count($tmp) > 1) {
				$extension = end($tmp);
				$onlyFilename = basename($filename, ".".$extension);
				$formattedFilename = $onlyFilename.".".strtolower($extension);
			}
		}
		
		if($formattedFilename == '') {
			$formattedFilename = $filename;
		}
		
		return $formattedFilename;
	}
    
    public function addEditContentAttachment($id, $contentId, $filename, $serverFileName, $serverFileSize, $cloudStorageTypeId = 0, $cloudFileUrl = "", $cloudFileId = "", $cloudFileThumbStr = "", $attCreateTs = NULL, $attUpdateTs = NULL)
    {
		$response = array();
		$syncId = 0;
		
		$formattedFilename = $this->getFormattedAttachmentName($filename);

		$cloudFileThumbStr = '';
		
        $attDetails = array();
        $attDetails['filename'] = $formattedFilename;
        $attDetails['server_filename'] = $serverFileName;
        $attDetails['filesize'] = $serverFileSize;
        $attDetails['att_cloud_storage_type_id'] = $cloudStorageTypeId;
        $attDetails['cloud_file_url'] = $cloudFileUrl;
        $attDetails['cloud_file_id'] = $cloudFileId;
        $attDetails['cloud_file_thumb_str'] = $cloudFileThumbStr;

        if(isset($attUpdateTs))
    	{
    		$attDetails['update_ts'] = $attUpdateTs;
    	}
        if(isset($attCreateTs))
    	{
	    	$attDetails['create_ts'] = $attCreateTs;
	    }
        
        $conName = "";
		if(isset($this->orgDbConName))
    	{
    		$conName = $this->orgDbConName;
    		
        	$attDetails['employee_content_id'] = $contentId;	       				
   			       			
			$modelObj = New OrgEmployeeContentAttachment;
			$modelObj->setConnection($conName);
			$attTableName = $modelObj->table;	
		}
		else
		{
       		$attDetails['appuser_content_id'] = $contentId;
			$modelObj = New AppuserContentAttachment;
			$attTableName = $modelObj->table;
		}
		
		$attObj = $modelObj->byId($id)->first();
		if(isset($attObj))
        {
   			$attDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();	
   			$attDetails['is_modified'] = 0;	

        	$prevCloudStorageTypeId = $attObj->att_cloud_storage_type_id;
        	$prevFilename = $attObj->server_filename;
        	
        	$attObj->update($attDetails);
			$syncId = $id;
			
			if($prevCloudStorageTypeId == 0 && isset($prevFilename) && $prevFilename != "")
				FileUploadClass::removeAttachment($prevFilename, $this->orgId);
		}
		else
		{
   			$attDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();   			
			$syncId = DB::connection($conName)->table($attTableName)->insertGetId($attDetails);			
		}
		
		$response["syncId"] = $syncId;
		
		return $response;
	}
    
    public function setContentUpdated($id, $isFolder)
    {
    	if($isFolder)
    	{
			if(isset($this->orgDbConName))
	    	{ 
				$modelObj = New OrgEmployeeContent;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New AppuserContent;	
			}			
		}
		else
		{
			if(isset($this->orgDbConName))
	    	{
				$modelObj = New OrgGroupContent;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{	
				$modelObj = New GroupContent;	
			}
		}
		
        $userContent = $modelObj->byId($id)->first();
		if(isset($userContent))
        {
        	$contentDetails = array();
   			$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
   			
        	$userContent->update($contentDetails);
		}
	}
    //_CHANGES
    public function addEditContent($id, $content,$title_note, $contentTypeId, $folderId, $sourceId, $tagsArr, $isMarked, $createTs, $updateTs, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail = "",  $syncWithCloudCalendarGoogle = 0, $syncWithCloudCalendarOnedrive = 0, $isRemoved = 0, $removedAt = NULL)
    {
		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
		if(!isset($colorCode) || $colorCode == '') {
			$colorCode = Config::get('app_config.default_content_color_code');
		}
		
		if(!isset($isLocked)) {
			$isLocked = Config::get('app_config.default_content_lock_status');
		}
		
		if(!isset($isShareEnabled)) {
			$isShareEnabled = Config::get('app_config.default_content_share_status');
		}
		
		if(!isset($remindBeforeMillis)) {
			$remindBeforeMillis = NULL;
		}
		
		if(!isset($repeatDuration)) {
			$repeatDuration = NULL;
		}
		
		if(!isset($isCompleted)) {
			$isCompleted = Config::get('app_config.default_content_is_completed_status');
		}
		
		if(!isset($isSnoozed)) {
			$isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
		}
        
        if(!isset($isRemoved) || $isRemoved != 1) {
            $isRemoved = 0;
            $removedAt = NULL;
        }

        $consSyncWithCloudCalendarGoogle = $syncWithCloudCalendarGoogle * 1;
        if($consSyncWithCloudCalendarGoogle > 1)
        {
        	$consSyncWithCloudCalendarGoogle = 1;
        }

        $consSyncWithCloudCalendarOnedrive = $syncWithCloudCalendarOnedrive * 1;
        if($consSyncWithCloudCalendarOnedrive > 1)
        {
        	$consSyncWithCloudCalendarOnedrive = 1;
        }
		
        $contentDetails = array();
        $contentDetails['content'] = Crypt::encrypt($content);
		$contentDetails['title_note'] = $title_note;//_CHANGES
        $contentDetails['content_type_id'] = $contentTypeId;
        $contentDetails['folder_id'] = $folderId;
        $contentDetails['source_id'] = $sourceId;
        $contentDetails['is_marked'] = $isMarked;
        $contentDetails['create_timestamp'] = $createTs;
        $contentDetails['update_timestamp'] = $updateTs;
        $contentDetails['from_timestamp'] = $fromTimeStamp;
        $contentDetails['to_timestamp'] = $toTimeStamp;	
        $contentDetails['color_code'] = $colorCode;	
        $contentDetails['is_locked'] = $isLocked;	
        $contentDetails['is_share_enabled'] = $isShareEnabled;	
        $contentDetails['remind_before_millis'] = $remindBeforeMillis;	
        $contentDetails['repeat_duration'] = $repeatDuration;
        $contentDetails['is_completed'] = $isCompleted;	
        $contentDetails['is_snoozed'] = $isSnoozed;	
        $contentDetails['reminder_timestamp'] = $reminderTimestamp;	
        $contentDetails['is_removed'] = $isRemoved;	
        $contentDetails['removed_at'] = $removedAt;	
        $contentDetails['sync_with_cloud_calendar_google'] = $consSyncWithCloudCalendarGoogle;	
        $contentDetails['sync_with_cloud_calendar_onedrive'] = $consSyncWithCloudCalendarOnedrive;	
		
		if(isset($sharedByEmail) && $sharedByEmail != "")
		{
       		$contentDetails['shared_by_email'] = $sharedByEmail;
		}
		
		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			
   			$contentDetails['employee_id'] = $this->orgEmpId;		       			
   			       			
			$modelObj = New OrgEmployeeContent;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$contentTableName = $modelObj->table;		
   			       			
			$tagModelObj = New OrgEmployeeContentTag;
			$tagModelObj = $tagModelObj->setConnection($this->orgDbConName);
			$contentTagTableName = $tagModelObj->table;	
   			       			
			$attachmentModelObj = New OrgEmployeeContentAttachment;
			$attachmentModelObj = $attachmentModelObj->setConnection($this->orgDbConName);
			$contentAttachmentTableName = $attachmentModelObj->table;
		}
		else
		{
       		$contentDetails['appuser_id'] = $this->userId;
			$modelObj = New AppuserContent;
			$contentTableName = $modelObj->table;		
   			       			
			$tagModelObj = New AppuserContentTag;
			$contentTagTableName = $tagModelObj->table;
   			       			
			$attachmentModelObj = New AppuserContentAttachment;
			$contentAttachmentTableName = $attachmentModelObj->table;
		}

		$retSyncId = 0;
        $userContent = $modelObj->byId($id)->first();

        // Log::info('addEditContent : userContent : ');
        // Log::info($userContent);

		if(isset($userContent))
		{
			$retSyncId = $id;
			// if(isset($userContent->is_locked) && $userContent->is_locked == 0 && isset($userContent->is_removed) && $userContent->is_removed == 0)

			if((isset($userContent->is_removed) && $userContent->is_removed == 0) || $isRemoved == 0)
	        {
   				$contentDetails['created_at'] = $userContent->created_at;
	   			$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();

		        // Log::info('addEditContent : EDIT contentDetails : ');
		        // Log::info($contentDetails);
	   			
	        	$userContent->update($contentDetails);
				$syncId = $id;
			}
		}
		else
		{	
   			$contentDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();	
	   		$contentDetails['updated_at'] = NULL;	

	        // Log::info('addEditContent : ADD contentDetails : ');
	        // Log::info($contentDetails);
   					
			$syncId = DB::connection($conName)->table($contentTableName)->insertGetId($contentDetails);
			$retSyncId = $syncId;
		}
		
		if($syncId > 0)
		{
        	$refetchedUserContent = $modelObj->byId($syncId)->first();

	        // Log::info('addEditContent : refetchedUserContent : ');
	        // Log::info($refetchedUserContent);

			if(isset($this->orgDbConName))
			{	
				$contentTags = $tagModelObj->ofEmployeeContent($syncId)->get();
			}
			else
			{
				$contentTags = $tagModelObj->ofUserContent($syncId)->get();				
			}
			
	        $existingTags = array(); 
	        if(isset($contentTags) && count($contentTags) > 0)
	        {
	        	foreach ($contentTags as $contentTag) 
	            {
	                $tagId = $contentTag->tag_id;
	                if($tagId > 0 && (!isset($tagsArr) || !is_array($tagsArr) || !in_array($tagId, $tagsArr)))
	                {
	                    $contentTag->delete();
	                } 
	                else
	                {
	                	array_push($existingTags, $tagId);						
					}   
	            }
			}
			
			if(isset($tagsArr) && count($tagsArr) > 0)
	        {
	        	$tagDetails = array();
	            $tagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
	        	
	        	if($conName == "")
	        	{
	        		$tagDetails['appuser_content_id'] = $syncId;					
				}
	        	else
	        	{
	        		$tagDetails['employee_content_id'] = $syncId;
				}
				
				foreach($tagsArr as $tagId)
	            {
	                if($tagId > 0 && !in_array($tagId, $existingTags))
	                {
	                	$tagDetails['tag_id'] = $tagId;	
	                	DB::connection($conName)->table($contentTagTableName)->insert($tagDetails);
	                }                            
	            }
			}
			
			if(isset($removeAttachmentIdArr) && is_array($removeAttachmentIdArr) && count($removeAttachmentIdArr) > 0)
			{
				foreach($removeAttachmentIdArr as $serverAttachmentId) 
	            {                    
	            	$contentAttachment = $attachmentModelObj->byId($serverAttachmentId)->first();
	            	if(isset($contentAttachment))
	            	{
	            		if($contentAttachment->att_cloud_storage_type_id == 0)
						{
							$filename = $contentAttachment->server_filename;
	                    	FileUploadClass::removeAttachment($filename, $this->orgId);
	                    }
	            		$contentAttachment->delete();  
					}                                               
	            } 
			}
			
			$isFolder = TRUE; 
        	$this->recalculateUserQuota($isFolder);

        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderId);
        	
        	/*if($syncId > 0)
			{
				if(isset($this->currLoginToken) && $this->currLoginToken != "")
				{
					if(isset($this->orgDbConName))
	        		{
	        			$this->sendOrgContentAddMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $syncId);
					}
					else
					{
						$this->sendContentAddMessageToDevice($this->userId, $this->currLoginToken, $syncId);
					}
				}
			}*/	

			if($syncWithCloudCalendarGoogle == 1 || $syncWithCloudCalendarOnedrive == 1)
			{
				$response["checkAndSetupCalendarContentForLinkedCloudCalendar"] = 1;
				$response["calendarContentForLinkedCloudCalendar"] = $this->checkAndSetupCalendarContentForLinkedCloudCalendar($this->cloudCalendarSyncOperationTypeModification, $isFolder, $folderId, $syncId, NULL);
			}

    		$this->checkAndSetupContentForAdditionalDataMapping($isFolder, $folderId, $syncId);
		}
		
		$response["syncId"] = $retSyncId;
		
		return $response;
	}

    public function createSentGroupContent($groupId, $memberId, $mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contAttachments, $sharedByUserName, $sharedByUserEmail)
    {
    	$resGroupContentId = 0;

    	if(isset($mainContent))
    	{
			$createTimeStamp = CommonFunctionClass::getCreateTimestamp();
			$updateTimeStamp = $createTimeStamp;

			$contentIsConversation = $this->checkIfContentIsSharedFromContentText($contentText);
			if($contentIsConversation)
			{
	        	$contentForwardedPrefixText = Config::get('app_config.content_is_forwarded_indicator');
				$contentText = $contentForwardedPrefixText.$contentText;
			}

			$appendedContentText = CommonFunctionClass::getSharedByAppendedString($contentText, $updateTimeStamp, $sharedByUserName, $sharedByUserEmail);

			$contentTypeId = $mainContent->content_type_id;
			$fromTimeStamp = $mainContent->from_timestamp;
			$toTimeStamp = $mainContent->to_timestamp;
			$isMarked = $mainContent->is_marked;
			$colorCode = $mainContent->color_code;
			$remindBeforeMillis = $mainContent->remind_before_millis;
			$repeatDuration = $mainContent->repeat_duration;
			$tagsArr = array();
			$removeAttachmentIdArr = NULL;
            $isCompleted = Config::get('app_config.default_content_is_completed_status');
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
            $reminderTimestamp = $mainContent->reminder_timestamp;
			  
			$response = $this->addEditGroupContent(0, $appendedContentText, $contentTypeId, $groupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $contentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByUserEmail);

			$newServerContentId = $response['syncId'];
			$this->setGroupContentCreator($groupId, $memberId);

			// Log::info('createSentGroupContent : contAttachments : ');
			// Log::info($contAttachments);
			
			foreach($contAttachments as $contAttachment)
			{
	            $cloudStorageTypeId = $contAttachment->att_cloud_storage_type_id;
	            if($cloudStorageTypeId > 0)
                {
                    $serverFileName = '';
                }
                else
                {
	           		$serverFileDetails = FileUploadClass::makeAttachmentCopy($contAttachment->server_filename, $this->orgId);
                    $serverFileName = $serverFileDetails['name'];
                }

	            $serverFileSize = $contAttachment->filesize;
	            $cloudFileUrl = $contAttachment->cloud_file_url;
	            $cloudFileId = $contAttachment->cloud_file_id;
	            $cloudFileThumbStr = $contAttachment->cloud_file_thumb_str;
	            $attCreateTs = $contAttachment->create_ts;
	            $attUpdateTs = $contAttachment->update_ts;
				
                if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
	            {
	            	$response = $this->addEditGroupContentAttachment(0, $newServerContentId, $contAttachment->filename, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
				}
			}

			$resGroupContentId = $newServerContentId;

			/* WILL SEND NOTIFICATIONS SEPARATELY */
			/*
			$groupMembers = $this->getGroupMembers($groupId);
			if(isset($groupMembers))
			{
    			$isAdd = 1;
    			if($this->orgId > 0)
    			{
					foreach($groupMembers as $groupMember)
					{	
						$memberEmpId = $groupMember->employee_id;
						
		        		$tempDepMgmtObj = New ContentDependencyManagementClass;								
				       	$tempDepMgmtObj->withOrgIdAndEmpId($this->orgId, $memberEmpId);   
				        $orgEmployee = $tempDepMgmtObj->getPlainEmployeeObject();
				        
				        if(isset($orgEmployee) && $orgEmployee->is_active == 1)
						{
							$this->sendOrgGroupEntryAddMessageToDevice($this->orgId, $memberEmpId, $newServerContentId, $isAdd, $sharedByUserEmail, $this->orgEmpId);
							MailClass::sendOrgContentAddedMail($this->orgId, $memberEmpId, $newServerContentId, $sharedByUserEmail, $groupId);
						}
						
					}
				}
				else
				{
					foreach($groupMembers as $groupMember)
					{
						$memberUserId = $groupMember->member_appuser_id;            
						$this->sendGroupEntryAddMessageToDevice($memberUserId, $newServerContentId, $isAdd, $sharedByUserEmail);		            
						MailClass::sendContentAddedMail($memberUserId, $newServerContentId, $sharedByUserEmail, $groupId);					
					}
				}
			}
			*/
			/* WILL SEND NOTIFICATIONS SEPARATELY */
			
		}
		
		return $resGroupContentId;
	}

    public function createSentFolderContent($mainContent, $contentIsLocked, $contentIsShareEnabled, $contentText, $contAttachments, $sharedByUserEmail, $sharedByUserOrEmpId = NULL)
    {
    	$resFolderContentId = 0;
    	$isFolder = TRUE;
    	if(isset($mainContent))
    	{
			if(!isset($contentIsLocked))
	    	{
				$contentIsLocked = Config::get('app_config.default_content_lock_status');
			}

			if(!isset($contentIsShareEnabled))
	    	{
				$contentIsShareEnabled = Config::get('app_config.default_content_share_status');
			}

			$senderDepMgmtObj = New ContentDependencyManagementClass;
			if($this->orgId > 0)
        	{
				$receiverUserOrEmpId = $this->orgEmpId;
        		$senderUserOrEmpId = $mainContent->employee_id;
				if(!isset($senderUserOrEmpId))
				{
					$senderUserOrEmpId = $sharedByUserOrEmpId;
				}
            	$senderDepMgmtObj->withOrgIdAndEmpId($this->orgId, $senderUserOrEmpId);   
			}
			else
			{
				$receiverUserOrEmpId = $this->userId;
				$senderUserOrEmpId = $mainContent->appuser_id;
				if(!isset($senderUserOrEmpId))
				{
					$senderUserOrEmpId = $sharedByUserOrEmpId;
				}
				$appUser = New Appuser;
				$appUser->appuser_id = $senderUserOrEmpId;
            	$senderDepMgmtObj->withOrgKey($appUser, "");   
			}
			
			$sentToEmail = $this->getEmployeeOrUserEmail();
			$sentToName = $this->getEmployeeOrUserName();
			
			$sentByEmail = $senderDepMgmtObj->getEmployeeOrUserEmail();
			$sentByName = $senderDepMgmtObj->getEmployeeOrUserName();

    		$createTimeStamp = CommonFunctionClass::getCreateTimestamp();
    		$updateTimeStamp = $createTimeStamp;
            $sourceId = 0;
            $tagsArr = array();
            $removeAttachmentIdArr = NULL;

            $contentIsConversation = $this->checkIfContentIsSharedFromContentText($contentText);
            if($contentIsConversation == 1)
            {
	        	$contentForwardedPrefixText = Config::get('app_config.content_is_forwarded_indicator');
				$contentText = $contentForwardedPrefixText.$contentText;
            }
			
			$senderContentText = CommonFunctionClass::getSentByAppendedString($contentText, $createTimeStamp, $sentByName, $sentByEmail, $sentToName, $sentToEmail);
			$receiverContentText = CommonFunctionClass::getSentToAppendedString($contentText, $createTimeStamp, $sentByName, $sentByEmail, $sentToName, $sentToEmail);

            $contentType = $mainContent->content_type_id;
            $fromTimeStamp = $mainContent->from_timestamp;
            $toTimeStamp = $mainContent->to_timestamp;
            $isMarked = $mainContent->is_marked;
            $colorCode = $mainContent->color_code;
            $remindBeforeMillis = $mainContent->remind_before_millis;
            $repeatDuration = $mainContent->repeat_duration;
            $isCompleted = Config::get('app_config.default_content_is_completed_status');
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
            $reminderTimestamp = $mainContent->reminder_timestamp;
			
			$sharedContentId = 0;
			// if($contentIsLocked == 0)
			{
				if(isset($senderDepMgmtObj))
				{
					$sentFolderId = $senderDepMgmtObj->getSentFolderId();

					$sentFolderContentIsLocked = $contentIsLocked; //0; //1;
					$sentFolderContentIsShareEnabled = $contentIsShareEnabled;
					
					$sentResponse = $senderDepMgmtObj->addEditContent(0, $senderContentText, $contentType, $sentFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $sentFolderContentIsLocked, $sentFolderContentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, ""); // sentToEmail
					
					$sharedContentId = $sentResponse['syncId'];
		            
		            foreach($contAttachments as $contAttachment)
					{
						$serverFileSize = $contAttachment->filesize;
			            $cloudStorageTypeId = $contAttachment->att_cloud_storage_type_id;
			            $cloudFileUrl = $contAttachment->cloud_file_url;
			            $cloudFileId = $contAttachment->cloud_file_id;
			            $cloudFileThumbStr = $contAttachment->cloud_file_thumb_str;
			            $attCreateTs = $contAttachment->create_ts;
			            $attUpdateTs = $contAttachment->update_ts;

			            $availableKbs = $senderDepMgmtObj->getAvailableUserQuota($isFolder);
	                
                		if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $serverFileSize))
		                {
				            if($cloudStorageTypeId > 0)
			                {
			                    $serverFileName = '';
			                }
			                else
			                {
				           		$serverFileDetails = FileUploadClass::makeAttachmentCopy($contAttachment->server_filename, $this->orgId);
			                    $serverFileName = $serverFileDetails['name'];
			                }
							
				            if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
				            {
				            	$attResponse = $senderDepMgmtObj->addEditContentAttachment(0, $sharedContentId, $contAttachment->filename, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
							}
						}
					}
					
					
		        	if($this->orgId > 0)
				    {
						$this->sendOrgEntryAddSilentMessageToDevice($senderUserOrEmpId, $this->orgId, $sharedContentId, "");
					}         
					else
					{
		        		$this->sendEntryAddSilentMessageToDevice($senderUserOrEmpId, $sharedContentId, "");
					}


					$senderDepMgmtObj->createContentSenderVirtualFolderForReceiver($receiverUserOrEmpId, $sentToEmail);
				}
			}
	         
	    	$defFolderId = $this->getDefaultFolderId();	
			if($defFolderId > 0)
			{
				$contentSharedByUserEmail = "";
				//if($contentType == Config::get('app_config.content_type_a'))
				{
					$contentSharedByUserEmail = $sharedByUserEmail;
				}


	        	$response = $this->addEditContent(0, $receiverContentText, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $contentIsLocked, $contentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $contentSharedByUserEmail);
	        	
	        	$newServerContentId = $response['syncId'];
	        	
	        	if($sharedContentId > 0)
	        	{
					$this->setContentSharedContentId($newServerContentId, $isFolder, $sharedContentId, $senderUserOrEmpId);
					$senderDepMgmtObj->setContentSharedContentId($sharedContentId, $isFolder, $newServerContentId, $receiverUserOrEmpId);
				}
	            
	            foreach($contAttachments as $contAttachment)
				{
		            $serverFileSize = $contAttachment->filesize;
		            $cloudStorageTypeId = $contAttachment->att_cloud_storage_type_id;
		            $cloudFileUrl = $contAttachment->cloud_file_url;
		            $cloudFileId = $contAttachment->cloud_file_id;
		            $cloudFileThumbStr = $contAttachment->cloud_file_thumb_str;
		            $attCreateTs = $contAttachment->create_ts;
		            $attUpdateTs = $contAttachment->update_ts;

		            $availableKbs = $this->getAvailableUserQuota($isFolder);
                
                	if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $serverFileSize))
	                {
			            if($cloudStorageTypeId > 0)
		                {
		                    $serverFileName = '';
		                }
		                else
		                {
			           		$serverFileDetails = FileUploadClass::makeAttachmentCopy($contAttachment->server_filename, $this->orgId);
		                    $serverFileName = $serverFileDetails['name'];
		                }
						
			           	if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
			            {
			            	$response = $this->addEditContentAttachment(0, $newServerContentId, $contAttachment->filename, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
						}
					}
				}
				
				$resFolderContentId = $newServerContentId;
			    
				/* WILL SEND NOTIFICATIONS SEPARATELY */
				/*
			    if($this->orgId > 0)
			    {
					$this->sendOrgEntryAddMessageToDevice($this->orgEmpId, $this->orgId, $newServerContentId, $sharedByUserEmail, $senderUserOrEmpId);
					MailClass::sendOrgContentAddedMail($this->orgId, $this->orgEmpId, $newServerContentId, $sharedByUserEmail);
					MailClass::sendOrgContentDeliveredMail($this->orgId, $this->orgEmpId, $sharedByUserEmail);
				}         
				else
				{
					$this->sendEntryAddMessageToDevice($this->userId, $newServerContentId, $sharedByUserEmail);
					MailClass::sendContentAddedMail($this->userId, $newServerContentId, $sharedByUserEmail);
					MailClass::sendContentDeliveredMail($this->userId, $sharedByUserEmail);
				}
				*/
				/* WILL SEND NOTIFICATIONS SEPARATELY */

				$this->createContentSenderVirtualFolderForReceiver($senderUserOrEmpId, $sharedByUserEmail);
	    	}
		}
		return $resFolderContentId;
	}
	
	public function sendFolderContentAsReply($contentId, $replaceContentText = NULL, $sendRespectivePush = TRUE, $sendRespectivePushSilently = FALSE)
	{
		$isFolder = TRUE;
		$contentText = "";
		if(isset($contentId) && $contentId > 0)
		{
			$contentObj = $this->getContentObject($contentId, $isFolder);
			$contentAttachments = $this->getContentAttachments($contentId, $isFolder);
			$sharedByUserEmail = $this->getEmployeeOrUserEmail();
			
			if(isset($contentObj))
			{
				$contentText = $contentObj->content;
				$contentText = Crypt::decrypt($contentText);
				
				$senderEmail = $contentObj->shared_by_email;
				$sharedContentId = $contentObj->shared_content_id;
				$contentIsLocked = $contentObj->is_locked;
				$contentIsShareEnabled = $contentObj->is_share_enabled;
	            $contentType = $contentObj->content_type_id;

				if($sharedContentId > 0)// && $contentIsLocked == 0)// && ($contentType == Config::get('app_config.content_type_a')))
				{
					$senderUserOrEmpId = 0;
					$senderDepMgmtObj = New ContentDependencyManagementClass;
					if(isset($contentObj->shared_by) && $contentObj->shared_by != "")
					{
			        	$senderUserOrEmpId = $contentObj->shared_by;
						if($this->orgId > 0)
			        	{
			            	$senderDepMgmtObj->withOrgIdAndEmpId($this->orgId, $senderUserOrEmpId);   
						}
						else
						{
							$appUser = New Appuser;
							$appUser->appuser_id = $senderUserOrEmpId;
			            	$senderDepMgmtObj->withOrgKey($appUser, "");   
						}
					}
					
					$receiverUserOrEmpId = 0;
					if($this->orgId > 0)
					{
						$receiverUserOrEmpId = $this->orgEmpId;
					}
					else
					{
						$receiverUserOrEmpId = $this->userId;
					}
			
					$sentToEmail = $senderDepMgmtObj->getEmployeeOrUserEmail();
					$sentToName = $senderDepMgmtObj->getEmployeeOrUserName();
					
					$sentByEmail = $this->getEmployeeOrUserEmail();
					$sentByName = $this->getEmployeeOrUserName();

		    		$createTimeStamp = CommonFunctionClass::getCreateTimestamp();
		    		$updateTimeStamp = $createTimeStamp;
					
					if(!isset($replaceContentText) || $replaceContentText == FALSE)
					{
						$contentText = CommonFunctionClass::getSentToAppendedString($contentText, $updateTimeStamp, $sentByName, $sentByEmail, $sentToName, $sentToEmail);
					}
					
					$this->setContentText($contentId, $isFolder, $contentText);
					
					$sharedContentObj = $senderDepMgmtObj->getContentObject($sharedContentId, $isFolder);
					$defFolderId = $senderDepMgmtObj->getDefaultFolderId();
					$usrSentFolderId = $senderDepMgmtObj->getSentFolderId();
			        $replyContentIsLocked = $contentIsLocked;//Config::get('app_config.default_content_lock_status');
			        $replyContentIsShareEnabled = Config::get('app_config.default_content_share_status');

		            $fromTimeStamp = $contentObj->from_timestamp;
		            $toTimeStamp = $contentObj->to_timestamp;

			        $isContentModified = FALSE;

					if(!isset($sharedContentObj))
					{
			            $sourceId = 0;
			            $tagsArr = array();
			            $removeAttachmentIdArr = NULL;

			            $isMarked = $contentObj->is_marked;
			            $colorCode = $contentObj->color_code;
			            $remindBeforeMillis = $contentObj->remind_before_millis;
			            $repeatDuration = $contentObj->repeat_duration;
			            $isCompleted = Config::get('app_config.default_content_is_completed_status');
			            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
			            $reminderTimestamp = $contentObj->reminder_timestamp;
			            
	            
						$contentResponse = $senderDepMgmtObj->addEditContent(0, $contentText, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $replyContentIsLocked, $replyContentIsShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByUserEmail);
						$sharedContentId = $contentResponse['syncId'];
						$senderDepMgmtObj->setContentSharedContentId($contentId, $isFolder, $sharedContentId, $senderUserOrEmpId);
						$senderDepMgmtObj->setContentSharedContentId($sharedContentId, $isFolder, $contentId, $receiverUserOrEmpId);

						$isContentModified = TRUE;
					}
					else
					{
						$existingSharedContentFolderId = $sharedContentObj->folder_id;
						if($existingSharedContentFolderId != $defFolderId && $existingSharedContentFolderId != $usrSentFolderId)
						{
							$defFolderId = $existingSharedContentFolderId;
							$isContentModified = TRUE;
						}

						if($fromTimeStamp != $sharedContentObj->from_timestamp)
						{
							$isContentModified = TRUE;
						}
						else if($toTimeStamp != $sharedContentObj->to_timestamp)
						{
							$isContentModified = TRUE;
						}
						else if($contentType != $sharedContentObj->content_type_id)
						{
							$isContentModified = TRUE;
						}
					}
					/*else
					{*/

						$senderDepMgmtObj->setSharedContentReplyDetails($sharedContentId, $isFolder, $defFolderId, $contentText, $replyContentIsLocked, $sharedByUserEmail, $contentType, $fromTimeStamp, $toTimeStamp);
					/*}*/
					
					$sharedContentAttachments = $senderDepMgmtObj->getContentAttachments($sharedContentId, $isFolder);
					if(isset($sharedContentAttachments))
	            	{
						foreach($sharedContentAttachments as $conAttachment)
	            		{
	            			if($conAttachment->att_cloud_storage_type_id == 0)
							{
								$filename = $conAttachment->server_filename;
		                        FileUploadClass::removeAttachment($filename, $senderDepMgmtObj->orgId);
		                    }
	                        $conAttachment->delete();
	                        $isContentModified = TRUE;
						}
					}
					
					foreach($contentAttachments as $contAttachment)
					{
			            $serverFileSize = $contAttachment->filesize;
			            $cloudStorageTypeId = $contAttachment->att_cloud_storage_type_id;
			            $cloudFileUrl = $contAttachment->cloud_file_url;
			            $cloudFileId = $contAttachment->cloud_file_id;
			            $cloudFileThumbStr = $contAttachment->cloud_file_thumb_str;
			            $attCreateTs = $contAttachment->create_ts;
			            $attUpdateTs = $contAttachment->update_ts;

			            $availableKbs = $senderDepMgmtObj->getAvailableUserQuota($isFolder);
	                
                		if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $serverFileSize))
		                {
				            if($cloudStorageTypeId > 0)
			                {
			                    $serverFileName = '';
			                }
			                else
			                {
				           		$serverFileDetails = FileUploadClass::makeAttachmentCopy($contAttachment->server_filename, $senderDepMgmtObj->orgId);
			                    $serverFileName = $serverFileDetails['name'];
			                }

			            	if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
				            {
				            	$response = $senderDepMgmtObj->addEditContentAttachment(0, $sharedContentId, $contAttachment->filename, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
							}

							$isContentModified = TRUE;
						}
					}

					if(!$isContentModified)
					{
						$sendRespectivePush = FALSE;
					}
					
					if($sendRespectivePush)
					{
						if($this->orgId > 0)
					 	{
					 		if($sendRespectivePushSilently)
					 		{
								$this->sendOrgEntryAddSilentMessageToDevice($senderDepMgmtObj->orgEmpId, $senderDepMgmtObj->orgId, $sharedContentId, $sharedByUserEmail, $this->orgEmpId);
					 		}
					 		else
					 		{
								$this->sendOrgEntryAddMessageToDevice($senderDepMgmtObj->orgEmpId, $senderDepMgmtObj->orgId, $sharedContentId, $sharedByUserEmail, $this->orgEmpId);
					 		}
						}
						else
						{
					 		if($sendRespectivePushSilently)
					 		{
								$this->sendEntryAddSilentMessageToDevice($senderDepMgmtObj->userId, $sharedContentId, $sharedByUserEmail);
					 		}
					 		else
					 		{
								$this->sendEntryAddMessageToDevice($senderDepMgmtObj->userId, $sharedContentId, $sharedByUserEmail);
							}
						}
					}							
				}
			}	
		}
				
		return $contentText;
	}

    public function setContentSharedContentId($id, $isFolder, $sharedId, $empOrUserId)
    {
    	// Log::info('setContentSharedContentId : id : '.$id.' : sharedId : '.$sharedId.' : empOrUserId : '.$empOrUserId);
    	$contentDetails = array();
		$contentDetails['shared_content_id'] = $sharedId;
		$contentDetails['shared_by'] = $empOrUserId;
		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
   		
		$this->setPartialContentDetails($id, $isFolder, $contentDetails); 	
	}

    public function setSharedContentReplyDetails($id, $isFolder, $folderOrGroupId, $contentText, $replyContentIsLocked, $sharedByUserEmail, $contentType, $fromTs, $toTs)
    {
        $contentDetails = array();
		$contentDetails['content'] = Crypt::encrypt($contentText);
		$contentDetails['is_locked'] = $replyContentIsLocked;//0;
		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		$contentDetails['shared_by_email'] = $sharedByUserEmail;
		$contentDetails['content_type_id'] = $contentType;
		$contentDetails['from_timestamp'] = $fromTs;
		$contentDetails['to_timestamp'] = $toTs;
		
		if($isFolder)
		{
			$contentDetails['folder_id'] = $folderOrGroupId;
		}
		$this->setPartialContentDetails($id, $isFolder, $contentDetails);
	}

    public function setContentText($id, $isFolder, $contentText)
    {
    	$contentDetails = array();
		$contentDetails['content'] = Crypt::encrypt($contentText);
		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		$this->setPartialContentDetails($id, $isFolder, $contentDetails);        	
	}

    public function setContentIsCompletedFlag($id, $isFolder, $isCompleted)
    {
    	$contentDetails = array();
		$contentDetails['is_completed'] = $isCompleted;
		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		$this->setPartialContentDetails($id, $isFolder, $contentDetails);     
        
        if($this->orgId > 0)
        {
            $this->sendOrgContentAddMessageToDevice($this->orgEmpId, $this->orgId, $this->currLoginToken, $isFolder, $id);
        }
        else
        {
            $this->sendContentAddMessageToDevice($this->userId, $this->currLoginToken, $isFolder, $id);
        }   	
	}

    public function setContentReminderStatusAsSnoozed($id, $isFolder, $reminderTimestamp)
    {
    	$contentDetails = array();
		$contentDetails['is_snoozed'] = 1;
		$contentDetails['reminder_timestamp'] = $reminderTimestamp;
		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		$this->setPartialContentDetails($id, $isFolder, $contentDetails);     
        
        if($this->orgId > 0)
        {
            $this->sendOrgContentAddMessageToDevice($this->orgEmpId, $this->orgId, $this->currLoginToken, $isFolder, $id);
        }
        else
        {
            $this->sendContentAddMessageToDevice($this->userId, $this->currLoginToken, $isFolder, $id);
        }   	
	}

    public function setPartialContentDetails($id, $isFolder, $updatedDetails)
    {
    	if($isFolder)
    	{
			if(isset($this->orgDbConName))
	    	{ 
				$modelObj = New OrgEmployeeContent;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New AppuserContent;	
			}			
		}
		else
		{
			if(isset($this->orgDbConName))
	    	{
				$modelObj = New OrgGroupContent;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{	
				$modelObj = New GroupContent;	
			}
		}
		
        $userContent = $modelObj->byId($id)->first();
		if(isset($userContent))
        {   			
        	$userContent->update($updatedDetails);

    		$folderOrGroupId = 0;
        	if($isFolder)
    		{
    			$folderOrGroupId = $userContent->folder_id;
    		}
    		else
    		{
    			$folderOrGroupId = $userContent->group_id;
    		}

        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId);

			$this->checkAndSetupCalendarContentForLinkedCloudCalendar($this->cloudCalendarSyncOperationTypeModification, $isFolder, $folderOrGroupId, $id, NULL);
		}
	}
    
    public function addEditContentTags($id, $tagsArr)
    {		
		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;	
   			       			
			$tagModelObj = New OrgEmployeeContentTag;
			$tagModelObj = $tagModelObj->setConnection($this->orgDbConName);
			$contentTagTableName = $tagModelObj->table;	
		}
		else
		{		
			$tagModelObj = New AppuserContentTag;
			$contentTagTableName = $tagModelObj->table;
		}
			
		if(isset($this->orgDbConName))
		{	
			$contentTags = $tagModelObj->ofEmployeeContent($id)->get();
		}
		else
		{
			$contentTags = $tagModelObj->ofUserContent($id)->get();				
		}
		
        $existingTags = array(); 
        if(isset($contentTags) && count($contentTags) > 0)
        {
        	foreach ($contentTags as $contentTag) 
            {
                $tagId = $contentTag->tag_id;
                if($tagId > 0 && !in_array($tagId, $tagsArr))
                {
                    $contentTag->delete();
                } 
                else
                {
                	array_push($existingTags, $tagId);						
				}   
            }
		}
        
        if(count($tagsArr) > 0)
        {
        	$tagDetails = array();
            $tagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
        	
        	if($conName == "")
        	{
        		$tagDetails['appuser_content_id'] = $id;					
			}
        	else
        	{
        		$tagDetails['employee_content_id'] = $id;
			}
			
			foreach($tagsArr as $tagId)
            {
                if($tagId > 0 && !in_array($tagId, $existingTags))
                {
                	$tagDetails['tag_id'] = $tagId;	
                	DB::connection($conName)->table($contentTagTableName)->insert($tagDetails);
                }                            
            }
		}
	}
    
    public function addEditGroupContentTags($id, $userOrEmpId, $tagsArr)
    {		
    	$groupContent = $this->getContentObject($id, FALSE);

    	if(isset($groupContent))
    	{
    		$groupId = $groupContent->group_id;

    		$conName = "";
			if(isset($this->orgDbConName))
	    	{	
				$conName = $this->orgDbConName;	
	   			       			
				$tagModelObj = New OrgGroupContentTag;
				$tagModelObj = $tagModelObj->setConnection($this->orgDbConName);
				$contentTagTableName = $tagModelObj->table;	
				$contentTags = $tagModelObj->ofGroupContentAndEmployee($id, $userOrEmpId)->get();
			}
			else
			{		
				$tagModelObj = New GroupContentTag;
				$contentTagTableName = $tagModelObj->table;
				$contentTags = $tagModelObj->ofGroupContentAndUser($id, $userOrEmpId)->get();
			}
			
	        $existingTags = array(); 
	        if(isset($contentTags) && count($contentTags) > 0)
	        {
	        	foreach ($contentTags as $contentTag) 
	            {
	                $tagId = $contentTag->tag_id;
	                if($tagId > 0 && !in_array($tagId, $tagsArr))
	                {
	                    $contentTag->delete();
	                } 
	                else
	                {
	                	array_push($existingTags, $tagId);						
					}   
	            }
			}
	        
	        if(count($tagsArr) > 0)
	        {
	        	$tagDetails = array();
	            $tagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
	        	$tagDetails['group_content_id'] = $id;
	        	
	        	if($conName == "")
	        	{		
	        		$tagDetails['appuser_id'] = $userOrEmpId;			
				}
	        	else
	        	{
	        		$tagDetails['employee_id'] = $userOrEmpId;
				}
				
				foreach($tagsArr as $tagId)
	            {
	                if($tagId > 0 && !in_array($tagId, $existingTags))
	                {
	                	$tagDetails['tag_id'] = $tagId;	
	                	DB::connection($conName)->table($contentTagTableName)->insert($tagDetails);
	                }                            
	            }
			}
	           
			$contentTags = array();
			if($conName != "")	
			{
				$contentTags = $tagModelObj->ofGroupContentAndEmployee($id, $this->orgEmpId)->get();
			}
			else
			{
				$contentTags = $tagModelObj->ofGroupContentAndUser($id, $this->userId)->get();
			}

			$groupMembers = $this->getGroupMembers($groupId);

	        if(isset($groupMembers) && count($groupMembers) > 0)
	        {        	        	
				foreach($groupMembers as $groupMember)
				{	
		        	$memberTagDetails = array();
		            $memberTagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
		        	$memberTagDetails['group_content_id'] = $id;

					$tempMemberDepMgmtObj = NULL;
					$memberExistingContentTags = array();
					if($this->orgId > 0)
					{
						$memberEmpId = $groupMember->employee_id;
						if($memberEmpId != $this->orgEmpId)
						{
							$tempMemberDepMgmtObj = New ContentDependencyManagementClass;
							$tempMemberDepMgmtObj->withOrgIdAndEmpId($this->orgId, $memberEmpId);
							
							$memberTagDetails['employee_id'] = $memberEmpId;

							$memberExistingContentTags = $tagModelObj->ofGroupContentAndEmployee($id, $memberEmpId)->get();
						}
					}
					else
					{
						$memberUserId = $groupMember->member_appuser_id;
						if($memberUserId != $this->userId)
						{
							$tempUser = new \stdClass;
							$tempUser->appuser_id = $memberUserId;
							
							$tempMemberDepMgmtObj = New ContentDependencyManagementClass;
							$tempMemberDepMgmtObj->withUserIdOrgIdAndEmpId($tempUser, 0, 0);
							
				        	$memberTagDetails['appuser_id'] = $memberUserId;

							$memberExistingContentTags = $tagModelObj->ofGroupContentAndUser($id, $memberUserId)->get();
						}
					}

					if($this->orgId > 0)
					{
				        $memberExistingContentTagIdArr = array();
				        if(isset($memberExistingContentTags) && count($memberExistingContentTags) > 0)
				        {
				        	foreach ($memberExistingContentTags as $memberExistingContentTag) 
				            {
				                $memberExistingContentTagId = $memberExistingContentTag->tag_id; 
				                array_push($memberExistingContentTagIdArr, $memberExistingContentTagId);
				            }
						}
						
						if(isset($tempMemberDepMgmtObj))
						{
							$memberUpdatedContentTagIdArr = array();
				        	foreach ($contentTags as $contentTag) 
				            {
				                $tagId = $contentTag->tag_id; 

				                $contentTagObj = $this->getTagObject($tagId);
				                if(isset($contentTagObj))
				                {
				                    $tagName = $contentTagObj->tag_name;
				                 
					                $memberTagId = 0;
									$tagObj = $tempMemberDepMgmtObj->getTagObjectByName($tagName);
									if(!isset($tagObj))
									{
										$tagRes = $tempMemberDepMgmtObj->addEditTag(0, $tagName);
										$memberTagId = $tagRes["syncId"];
									}
									else
									{
										if($this->orgId > 0)
						    			{
											$memberTagId = $tagObj->employee_tag_id;  				
										}
										else
										{
											$memberTagId = $tagObj->appuser_tag_id;
										}
									}
									
									if($memberTagId > 0)
									{
					                	array_push($memberUpdatedContentTagIdArr, $memberTagId);
										if(!in_array($memberTagId, $memberExistingContentTagIdArr))
						                {
											$memberTagDetails['tag_id'] = $memberTagId;	
						                	DB::connection($conName)->table($contentTagTableName)->insert($memberTagDetails);				
										}
									}
								}
				            }

				        	if(isset($memberExistingContentTags) && count($memberExistingContentTags) > 0)
					        {
					        	foreach ($memberExistingContentTags as $memberExistingContentTag) 
					            {
					                $memberExistingContentTagId = $memberExistingContentTag->tag_id; 
					                if($memberExistingContentTagId > 0 && !in_array($memberExistingContentTagId, $memberUpdatedContentTagIdArr))
					                {
					                    $memberExistingContentTag->delete();
					                } 
					            }
							}
						}
					}	
				}
				
			}
    	}
	}
    
    public function addEditGroupContentAttachment($id, $contentId, $filename, $serverFileName, $serverFileSize, $cloudStorageTypeId = 0, $cloudFileUrl = "", $cloudFileId = "", $cloudFileThumbStr = "", $attCreateTs = NULL, $attUpdateTs = NULL)
    {
		$response = array();
		$syncId = 0;
		
		$formattedFilename = $this->getFormattedAttachmentName($filename);

		$cloudFileThumbStr = '';
		
        $attDetails = array();
        $attDetails['filename'] = $formattedFilename;
        $attDetails['server_filename'] = $serverFileName;
        $attDetails['filesize'] = $serverFileSize;
        $attDetails['att_cloud_storage_type_id'] = $cloudStorageTypeId;
        $attDetails['cloud_file_url'] = $cloudFileUrl;
        $attDetails['cloud_file_id'] = $cloudFileId;
        $attDetails['cloud_file_thumb_str'] = $cloudFileThumbStr;

        if(isset($attUpdateTs))
    	{
    		$attDetails['update_ts'] = $attUpdateTs;
    	}
        if(isset($attCreateTs))
    	{
	    	$attDetails['create_ts'] = $attCreateTs;
	    }
        
        $conName = "";
		if(isset($this->orgDbConName))
    	{
    		$conName = $this->orgDbConName;
    		
        	$attDetails['group_content_id'] = $contentId;	       				
   			       			
			$modelObj = New OrgGroupContentAttachment;
			$modelObj->setConnection($conName);
			$attTableName = $modelObj->table;	
		}
		else
		{
       		$attDetails['group_content_id'] = $contentId;
			$modelObj = New GroupContentAttachment;
			$attTableName = $modelObj->table;
		}
		
		$attObj = $modelObj->byId($id)->first();
		if(isset($attObj))
        {
   			$attDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();	
   			$attDetails['is_modified'] = 0;	
   			
        	$prevCloudStorageTypeId = $attObj->att_cloud_storage_type_id;
        	$prevFilename = $attObj->server_filename;
        	$attObj->update($attDetails);
			$syncId = $id;	
			
			if($prevCloudStorageTypeId == 0 && isset($prevFilename) && $prevFilename != "")
				FileUploadClass::removeAttachment($prevFilename, $this->orgId);
		}
		else
		{
   			$attDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();   			
			$syncId = DB::connection($conName)->table($attTableName)->insertGetId($attDetails);			
		}
		
		$response["syncId"] = $syncId;
		
		return $response;
	}
    
    public function addEditGroupContent($id, $content, $contentTypeId, $groupId, $tagsArr, $isMarked, $createTs, $updateTs, $fromTs, $toTs, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail = "")
    {
		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
		if(!isset($sharedByEmail))
			$sharedByEmail = "";
		
		if(!isset($colorCode) || $colorCode == '') {
			$colorCode = Config::get('app_config.default_content_color_code');
		}
		
		if(!isset($isLocked)) {
			$isLocked = Config::get('app_config.default_content_lock_status');
		}
		
		if(!isset($isShareEnabled)) {
			$isShareEnabled = Config::get('app_config.default_content_share_status');
		}
		
		if(!isset($remindBeforeMillis)) {
			$remindBeforeMillis = NULL;
		}
		
		if(!isset($repeatDuration)) {
			$repeatDuration = NULL;
		}
		
		if(!isset($isCompleted)) {
			$isCompleted = Config::get('app_config.default_content_is_completed_status');
		}
		
		if(!isset($isSnoozed)) {
			$isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
		}
		
		if(!isset($updateTs) || $updateTs == 0) {
			$updateTs = $createTs;
		}
		
        $contentDetails = array();
        $contentDetails['content'] = Crypt::encrypt($content);
        $contentDetails['content_type_id'] = $contentTypeId;
        $contentDetails['group_id'] = $groupId;
        $contentDetails['is_marked'] = $isMarked;
        $contentDetails['create_timestamp'] = $createTs;
        $contentDetails['update_timestamp'] = $updateTs;
        $contentDetails['from_timestamp'] = $fromTs;
        $contentDetails['to_timestamp'] = $toTs;
        $contentDetails['shared_by_email'] = $sharedByEmail;
        $contentDetails['color_code'] = $colorCode;	
        $contentDetails['is_locked'] = $isLocked;	
        $contentDetails['is_share_enabled'] = $isShareEnabled;	
        $contentDetails['remind_before_millis'] = $remindBeforeMillis;	
        $contentDetails['repeat_duration'] = $repeatDuration;
        $contentDetails['is_completed'] = $isCompleted;	
        $contentDetails['is_snoozed'] = $isSnoozed;	
        $contentDetails['reminder_timestamp'] = $reminderTimestamp;	
		
		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;   					       			
   			       			
			$modelObj = New OrgGroupContent;
			$modelObj->setConnection($this->orgDbConName);
			$contentTableName = $modelObj->table;
   			       			
			$tagModelObj = New OrgGroupContentTag;
			$tagModelObj->setConnection($this->orgDbConName);
			$contentTagTableName = $tagModelObj->table;
   			       			
			$attachmentModelObj = New OrgGroupContentAttachment;
			$attachmentModelObj->setConnection($this->orgDbConName);
			$contentAttachmentTableName = $attachmentModelObj->table;	
		}
		else
		{       		
			$modelObj = New GroupContent;
			$contentTableName = $modelObj->table;
   			       			
			$tagModelObj = New GroupContentTag;
			$contentTagTableName = $tagModelObj->table;
   			       			
			$attachmentModelObj = New GroupContentAttachment;
			$contentAttachmentTableName = $attachmentModelObj->table;	
		}
		
		$isContentAdd = FALSE;
        $userContent = $modelObj->byId($id)->first();
		if(isset($userContent))
        {
   			$contentDetails['created_at'] = $userContent->created_at;
	   		$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();		
	   		
        	$userContent->update($contentDetails);
			$syncId = $id;
		}
		else
		{		
   			$contentDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();	
	   		$contentDetails['updated_at'] = NULL;		
   					
			$syncId = DB::connection($conName)->table($contentTableName)->insertGetId($contentDetails);
			$isContentAdd = TRUE;
		}

		$userOrEmpId = $this->getEmployeeOrUserId();

		$this->addEditGroupContentTags($syncId, $userOrEmpId, $tagsArr);

		if(isset($removeAttachmentIdArr) && count($removeAttachmentIdArr) > 0)
		{
			foreach($removeAttachmentIdArr as $serverAttachmentId) 
            {                    
            	$contentAttachment = $attachmentModelObj->byId($serverAttachmentId)->first();
            	if(isset($contentAttachment))
            	{
        			if($contentAttachment->att_cloud_storage_type_id == 0)
					{
						$filename = $contentAttachment->server_filename;
	                    FileUploadClass::removeAttachment($filename, $this->orgId);
	                }
            		$contentAttachment->delete();  
				}                                               
            }  
		}
        $isFolder = FALSE;
        $this->recalculateUserQuota($isFolder, $groupId);

    	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $groupId);

    	$this->checkAndSetupContentForAdditionalDataMapping($isFolder, $groupId, $syncId);
		
		$response["syncId"] = $syncId;
		
		return $response;
	}
    
    public function restoreDeletedContent($id, $isFolder = TRUE)
    {		
		if($id > 0)
		{
			if($isFolder)
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgEmployeeContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					// $modelObj = New OrgEmployeeContentTag;
					// $modelObj->setConnection($this->orgDbConName);
	    //         	$modelObj->ofEmployeeContent($id)->delete();
	            	
					// $modelObj = New OrgEmployeeContentAttachment;
					// $modelObj->setConnection($this->orgDbConName);
	    //         	$conAttachments = $modelObj->ofEmployeeContent($id)->get();
				}
				else
				{
					$userContent = AppuserContent::byId($id)->first();
	            	//AppuserContentTag::ofUserContent($id)->delete();
	       	 		//$conAttachments = AppuserContentAttachment::ofUserContent($id)->get();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgGroupContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					// $modelObj = New OrgGroupContentTag;
					// $modelObj->setConnection($this->orgDbConName);
	    //         	$modelObj->ofGroupContentAndEmployee($id, $this->orgEmpId)->delete();
	            	
					// $modelObj = New OrgGroupContentAttachment;
					// $modelObj->setConnection($this->orgDbConName);
	    //         	$conAttachments = $modelObj->ofGroupContent($id)->get();
				}
				else
				{
					$userContent = GroupContent::byId($id)->first();
	            	//GroupContentTag::ofGroupContentAndUser($id, $this->userId)->delete();
	       	 		//$conAttachments = GroupContentAttachment::ofGroupContent($id)->get();
				}
				
			}				
						
			if(isset($userContent))
            {
        		$userContent->is_removed = 0;
            	$userContent->removed_at = NULL;
   				// $userContent->created_at = $userContent->created_at;
	   			$userContent->updated_at = CommonFunctionClass::getCurrentTimestamp();
            	$userContent->save();
            	
            	$this->sendRespectiveContentModificationPush($isFolder, $id, FALSE, ""); 

            	$folderOrGroupId = 0;
	        	if($isFolder)
	    		{
	    			$folderOrGroupId = $userContent->folder_id;
	    		}
	    		else
	    		{
	    			$folderOrGroupId = $userContent->group_id;
	    		}

	        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId);           	
			}
		}
	}

    public function softDeleteContent($id, $isFolder = TRUE, $deletePermanently = 0, $removedAt = NULL)
    {		
		if($id > 0)
		{
			if($isFolder)
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgEmployeeContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					// $modelObj = New OrgEmployeeContentTag;
					// $modelObj->setConnection($this->orgDbConName);
	    			// $modelObj->ofEmployeeContent($id)->delete();
	            	
					// $modelObj = New OrgEmployeeContentAttachment;
					// $modelObj->setConnection($this->orgDbConName);
    				// $conAttachments = $modelObj->ofEmployeeContent($id)->get();
				}
				else
				{
					$userContent = AppuserContent::byId($id)->first();
	            	//AppuserContentTag::ofUserContent($id)->delete();
	       	 		//$conAttachments = AppuserContentAttachment::ofUserContent($id)->get();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgGroupContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					// $modelObj = New OrgGroupContentTag;
					// $modelObj->setConnection($this->orgDbConName);
	    			// $modelObj->ofGroupContentAndEmployee($id, $this->orgEmpId)->delete();
	            	
					// $modelObj = New OrgGroupContentAttachment;
					// $modelObj->setConnection($this->orgDbConName);
	    			// $conAttachments = $modelObj->ofGroupContent($id)->get();
				}
				else
				{
					$userContent = GroupContent::byId($id)->first();
	            	//GroupContentTag::ofGroupContentAndUser($id, $this->userId)->delete();
	       	 		//$conAttachments = GroupContentAttachment::ofGroupContent($id)->get();
				}
				
			}				
						
			if(isset($userContent))
            {

				if($deletePermanently == 1)
				{
            		$userContent->is_removed = 2;
				}
				else
				{
            		$userContent->is_removed = 1;
				}

				if(!isset($removedAt) && $removedAt > 0 && strlen($removedAt.'') < 10)
				{
                    $removedAt = CommonFunctionClass::getCreateTimestamp();
				}

            	$userContent->removed_at = $removedAt;
   				// $userContent->created_at = $userContent->created_at;
	   			$userContent->updated_at = CommonFunctionClass::getCurrentTimestamp();
            	$userContent->save();
            	
            	$this->sendRespectiveContentModificationPush($isFolder, $id, FALSE, "");

	    		$folderOrGroupId = 0;
	        	if($isFolder)
	    		{
	    			$folderOrGroupId = $userContent->folder_id;
	    		}
	    		else
	    		{
	    			$folderOrGroupId = $userContent->group_id;
	    		}

	        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId);

				$this->checkAndSetupCalendarContentForLinkedCloudCalendar($this->cloudCalendarSyncOperationTypeDeletion, $isFolder, $folderOrGroupId, $id, $userContent);
			}
		}
	}
    
    public function deleteContent($id, $isFolder = TRUE, $sharedByUserEmail = NULL, $continuePerformingPush = true)
    {		
        // Log::info('deleteContent : id : '.$id.' : isFolder : '.json_encode($isFolder).' : continuePerformingPush : '.json_encode($continuePerformingPush));
		if($id > 0)
		{
			if($isFolder)
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgEmployeeContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					$modelObj = New OrgEmployeeContentTag;
					$modelObj->setConnection($this->orgDbConName);
	            	$modelObj->ofEmployeeContent($id)->delete();
	            	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachments = $modelObj->ofEmployeeContent($id)->get();
				}
				else
				{
					$userContent = AppuserContent::byId($id)->first();
	            	AppuserContentTag::ofUserContent($id)->delete();
	       	 		$conAttachments = AppuserContentAttachment::ofUserContent($id)->get();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
		    	{
					$modelObj = New OrgGroupContent;
					$modelObj->setConnection($this->orgDbConName);
	            	$userContent = $modelObj->byId($id)->first();
	            	
					$modelObj = New OrgGroupContentTag;
					$modelObj->setConnection($this->orgDbConName);
	            	$modelObj->ofGroupContentAndEmployee($id, $this->orgEmpId)->delete();
	            	
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachments = $modelObj->ofGroupContent($id)->get();
				}
				else
				{
					$userContent = GroupContent::byId($id)->first();
	            	GroupContentTag::ofGroupContentAndUser($id, $this->userId)->delete();
	       	 		$conAttachments = GroupContentAttachment::ofGroupContent($id)->get();
				}
				
			}				
						
			if(isset($userContent))
            {
				$grpId = $userContent->group_id;
				
            	if(isset($conAttachments))
            	{
					foreach($conAttachments as $conAttachment)
            		{
	        			if($conAttachment->att_cloud_storage_type_id == 0)
						{
							$filename = $conAttachment->server_filename;
	                        FileUploadClass::removeAttachment($filename, $this->orgId);
	                    }
                        $conAttachment->delete();
					}
				}

	    		$folderOrGroupId = 0;
	        	if($isFolder)
	    		{
	    			$folderOrGroupId = $userContent->folder_id;
	    		}
	    		else
	    		{
	    			$folderOrGroupId = $userContent->group_id;
	    		}

	        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId);
				
	        	$this->logContentAsDeleted($id, $isFolder);

            	$userContent->delete();

            	$this->recalculateUserQuota($isFolder, $grpId);

				$this->checkAndSetupCalendarContentForLinkedCloudCalendar($this->cloudCalendarSyncOperationTypeDeletion, $isFolder, $folderOrGroupId, $id, $userContent);

				if($continuePerformingPush)
				{
					if($isFolder)
					{	        	
			        	//if(isset($this->currLoginToken) && $this->currLoginToken != "")
						{
							if(isset($this->orgDbConName))
			        		{
			        			$this->sendOrgContentDeletedMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $isFolder, $id);
							}
							else
							{
								$this->sendContentDeletedMessageToDevice($this->userId, $this->currLoginToken, $isFolder, $id);
							}
						}
					}
					else
					{
		        		$groupObj = $this->getGroupObject($grpId);	
		        		
		        		if(!isset($sharedByUserEmail))
		        			$sharedByUserEmail = "";
		        		
		        		if(isset($groupObj))
		        		{
			        		$grpName = $groupObj->name;
			           		$isRename = 0;
							if(isset($this->orgDbConName))
				        	{
			        			$grpMemModelObj = New OrgGroupMember;
			        			$grpMemModelObj->setConnection($this->orgDbConName);								
								$groupMembers = $grpMemModelObj->ofGroup($grpId)->get();	
									
			            		foreach($groupMembers as $member) 
			                    {
									$memberEmpId = $member->employee_id;	
					        		$depMgmtObj = New ContentDependencyManagementClass;								
							       	$depMgmtObj->withOrgIdAndEmpId($this->orgId, $memberEmpId);   
							        $orgEmployee = $depMgmtObj->getPlainEmployeeObject();

							        if(isset($orgEmployee) && $orgEmployee->is_active == 1)
		       						{						
										if($memberEmpId != $this->orgEmpId)
										{
				       						$this->sendOrgGroupEntryDeletedMessageToDevice($this->orgId, $memberEmpId, $id, $grpName, $sharedByUserEmail);	
										}
										else
										{
											$this->sendOrgContentDeletedMessageToDevice($this->orgId, $memberEmpId, $this->currLoginToken, $isFolder, $id);
										}
										$this->sendOrgGroupAddedMessageToDevice($memberEmpId, $grpId, $isRename, $this->orgId);
									}	
								}
							}
							else
							{                    		
	                    		$groupMembers = GroupMember::ofGroup($grpId)->get();
								if(isset($groupMembers) && count($groupMembers) > 0)
		        				{	
		        					foreach($groupMembers as $groupMember)
		        					{						
										$memberUserId = $groupMember->member_appuser_id;
										if($memberUserId != $this->userId)
										{
			           						$this->sendGroupEntryDeletedMessageToDevice($memberUserId, $id, $grpName, $sharedByUserEmail);
		       								$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail);		
										}
										else
										{
											$this->sendContentDeletedMessageToDevice($memberUserId, $this->currLoginToken, $isFolder, $id);
											if(!$isFolder)
											{
												$this->sendGroupAddedMessageToDevice($memberUserId, $grpId, $isRename, $sharedByUserEmail, $this->currLoginToken);	
											}
										}
									}
								}							
							}						
						}
					}
				}
			}
		}
	}
    
    public function performDeleteContentAttachmentsFromCloud($contentAttachments)
    {	
        if(isset($contentAttachments) && count($contentAttachments) > 0)
        {
            foreach ($contentAttachments as $contentAttachment) 
            {
            	$cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
            	$fileId = $contentAttachment->cloud_file_id;
                if($cloudStorageTypeId > 0 && $fileId != "")
                {
                    $accessToken = $this->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                    	$cloudStorageType = $this->getCloudStorageTypeObjectById($cloudStorageTypeId);

	                    $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
	                    $attCldStrgMgmtObj->withAccessTokenAndStorageTypeObject($accessToken, $cloudStorageType);
	                    $attCldStrgMgmtObj->performFileDelete($fileId);
	                }
                }
            }
        }
	}
	
	public function getOrgProfileKey()
	{
		$orgKey = "";
		if($this->orgId > 0 && $this->orgEmpId > 0)
		{
			$orgKey = Crypt::encrypt($this->orgId."_".$this->orgEmpId); 
		}
		return $orgKey;
	}
	
	public function getOrgEmpKey()
	{
		$orgKey = "";
		if($this->orgId > 0 && $this->orgEmpId > 0)
		{
			$orgKey = OrganizationClass::getOrgEmpKeyFromOrgAndEmpId($this->orgId, $this->orgEmpId);
		}
		return $orgKey;
	}
	
	public function getOrgEmployeeId()
	{
		return $this->orgEmpId;
	}
    
    public function getContentAttachment($id, $isFolder = TRUE)
    {	
    	$conAttachment = NULL;	
		if($id > 0)
		{
			if($isFolder)	
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = AppuserContentAttachment::byId($id)->first();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = GroupContentAttachment::byId($id)->first();
				}
			}
		}
		return $conAttachment;
	}
    
    public function deleteContentAttachment($id)
    {	
   	 	$conAttachment = $this->getContentAttachment($id);
		
        if(isset($conAttachment))
        {
			if($conAttachment->att_cloud_storage_type_id == 0)
			{
				$filename = $conAttachment->server_filename;
	            FileUploadClass::removeAttachment($filename, $this->orgId);
	        }
        	$conAttachment->delete();
        	
        	if(isset($this->currLoginToken) && $this->currLoginToken != "")
			{
				$isFolder = TRUE;
				if(isset($this->orgDbConName))
        		{
        			$this->sendOrgContentAttachmentDeletedMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $isFolder, $id);
				}
				else
				{
					$this->sendContentAttachmentDeletedMessageToDevice($this->userId, $this->currLoginToken, $isFolder, $id);
				}
			}
		}
	}
    
    public function deleteFolderOrGroupContentAttachment($id, $isFolder)
    {		
		if($id > 0)
		{	
			if($isFolder)
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = AppuserContentAttachment::byId($id)->first();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = GroupContentAttachment::byId($id)->first();
				}
			}
			
            if(isset($conAttachment))
            {
            	if($conAttachment->att_cloud_storage_type_id == 0)
				{
					$filename = $conAttachment->server_filename;
	            	FileUploadClass::removeAttachment($filename, $this->orgId);
	            }
	        	$conAttachment->delete();
			}
		}
	}
    
    public function setContentAttachmentIsModified($id, $isFolder, $isModFlag)
    {		
		if($id > 0)
		{	
			if($isFolder)
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = AppuserContentAttachment::byId($id)->first();
				}
			}
			else
			{
				if(isset($this->orgDbConName))
	            {	                    	
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$conAttachment = $modelObj->byId($id)->first();
				}
				else
				{
	       	 		$conAttachment = GroupContentAttachment::byId($id)->first();
				}
			}
				
			
            if(isset($conAttachment))
            {
				$conAttachment->is_modified = $isModFlag;
				$conAttachment->save();
			}
		}
	}
	
	public function getContentObject($id, $isFolder)
	{
		$content = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	if($isFolder)	                    	
				{
					$modelObj = New OrgEmployeeContent;
				}
				else
				{
					$modelObj = New OrgGroupContent;
				}
				$modelObj->setConnection($this->orgDbConName);
            	$content = $modelObj->byId($id)->first();
			}
			else
			{
            	if($isFolder)	                    	
				{
       	 			$content = AppuserContent::byId($id)->first();
				}
				else
				{
       	 			$content = GroupContent::byId($id)->first();					
				}
			}
		}
		
		return $content;
	}
	
	public function getContentsReceivedFromSender($senderUserOrEmpEmail, $isFolder = TRUE, $folderOrGroupId = NULL)
	{
		$contents = NULL;
			
		if(isset($senderUserOrEmpEmail) && $senderUserOrEmpEmail != "")
		{
			$modelObj = NULL;	
			if(isset($this->orgDbConName))
	        {
	        	if($isFolder)	                    	
				{		
					$modelObj = New OrgEmployeeContent;
					$modelObj->setConnection($this->orgDbConName);
	        		$modelObj = $modelObj->ofEmployee($this->orgEmpId);
	        		
	        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
	        			$modelObj = $modelObj->ofFolder($folderOrGroupId);

	        		$modelObj = $modelObj->filterExceptRemoved();
				}
				else
				{        		
					$modelObj = New OrgGroupContent;
					$modelObj->setConnection($this->orgDbConName);
					if(isset($folderOrGroupId) && $folderOrGroupId > 0)
	        		{
	        			$modelObj = $modelObj->ofGroup($folderOrGroupId);
	        		}
	        		else
	        		{
	        			$empGroupIdArr = [];
	        			$employeeGroups = $this->getAllGroupsFoUser();
	        			foreach ($employeeGroups as $ind => $employeeGroup) {
	        				$empGrpId = $employeeGroup->group_id;
	        				$empGroupIdArr[$ind] = $empGrpId;
	        			}
	        			$modelObj = $modelObj->filterGroup($empGroupIdArr);
	        		}
				}
			}
			else
			{
	        	if($isFolder)	                    	
				{
	   	 			$modelObj = AppuserContent::ofUser($this->userId);
	        		
	        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
	        			$modelObj = $modelObj->ofFolder($folderOrGroupId); 

	                $modelObj = $modelObj->filterExceptRemoved();
				}
				else
				{
	   	 			$modelObj = New GroupContent;
	   	 			if(isset($folderOrGroupId) && $folderOrGroupId > 0)
	        		{
	        			$modelObj = $modelObj->ofGroup($folderOrGroupId);
	        		}
	        		else
	        		{
	        			$userGroupIdArr = [];
	        			$userGroups = $this->getAllGroupsFoUser();
	        			foreach ($userGroups as $ind => $userGroup) {
	        				$usrGrpId = $userGroup->group_id;
	        				$userGroupIdArr[$ind] = $usrGrpId;
	        			}
	        			$modelObj = $modelObj->filterGroup($userGroupIdArr);
	        		}				
				}
			}

			if(isset($modelObj))
			{
                $contents = $modelObj->filterSenderEmail($senderUserOrEmpEmail);
				$contents = $contents->get();
			}
		}
		
		return $contents;
	}
	
	public function getContentLockStatus($id, $isFolder)
	{
        $content = $this->getContentObject($id, $isFolder);
		$isLocked = 0;
        if(isset($content) && $content->is_locked == 1)
        {
            $isLocked = 1;
        }
        return $isLocked;
	}
	
	public function getContentShareEnabledStatus($id, $isFolder)
	{
        $content = $this->getContentObject($id, $isFolder);
		$isShareEnabled = 0;
        if(isset($content) && $content->is_share_enabled == 1)
        {
            $isShareEnabled = 1;
        }
        return $isShareEnabled;
	}

	public function getContentRemovedStatus($id, $isFolder)
	{
		$isRemoved = 0;
		if($isFolder)
		{
        	$content = $this->getContentObject($id, $isFolder);
	        if(isset($content) && $content->is_removed != 0)
	        {
	            $isRemoved = 1;
	        }
		}
        return $isRemoved;
	}
	
	public function getContentTags($id, $empOrUserId, $isFolder)
	{
		$contentTags = array();
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	if($isFolder)	                    	
				{	                    	
					$modelObj = New OrgEmployeeContentTag;
					$modelObj->setConnection($this->orgDbConName);
	            	$contentTags = $modelObj->ofEmployeeContent($id)->get();
				}
				else
				{
					$modelObj = New OrgGroupContentTag;	
					$modelObj->setConnection($this->orgDbConName);
	            	$contentTags = $modelObj->ofGroupContentAndEmployee($id, $empOrUserId)->get();				
				}
			}
			else
			{
            	if($isFolder)	                    	
				{
       	 			$contentTags = AppuserContentTag::ofUserContent($id)->get();
				}
				else
				{
       	 			$contentTags = GroupContentTag::ofGroupContentAndUser($id, $empOrUserId)->get();
				}
			}
		}
		
		return $contentTags;
	}
	
	public function getContentAttachments($id, $isFolder)
	{
		$contentAttachments = array();
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	if($isFolder)	                    	
				{		                    	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$contentAttachments = $modelObj->ofEmployeeContent($id)->get();
				}
				else
				{
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$contentAttachments = $modelObj->ofGroupContent($id)->get();
				}
			}
			else
			{
            	if($isFolder)	                    	
				{	
       	 			$contentAttachments = AppuserContentAttachment::ofUserContent($id)->get();
				}
				else
				{
       	 			$contentAttachments = GroupContentAttachment::ofGroupContent($id)->get();
				}
			}
		}
		
		return $contentAttachments;
	}
	
	public function getModifiedContentAttachmentCnt($id, $isFolder)
	{
		$conAttArr = ContentDependencyManagementClass::getModifiedContentAttachments($id, $isFolder);		
		return $cnt = count($conAttArr);
	}
	
	public function getModifiedContentAttachments($id, $isFolder)
	{
		$contentAttachments = array();
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	if($isFolder)	                    	
				{		                    	
					$modelObj = New OrgEmployeeContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$contentAttachments = $modelObj->ofEmployeeContent($id);
				}
				else
				{
					$modelObj = New OrgGroupContentAttachment;
					$modelObj->setConnection($this->orgDbConName);
	            	$contentAttachments = $modelObj->ofGroupContent($id);
				}
			}
			else
			{
            	if($isFolder)	                    	
				{	
       	 			$contentAttachments = AppuserContentAttachment::ofUserContent($id);
				}
				else
				{
       	 			$contentAttachments = GroupContentAttachment::ofGroupContent($id);
				}
			}
			
			if(isset($contentAttachments))
			{
				$contentAttachments = $contentAttachments->isModified();
				$contentAttachments = $contentAttachments->get();
			}
		}
		
		return $contentAttachments;
	}
	
	public function getGroupObject($id)
	{
		$group = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgGroup;
				$modelObj->setConnection($this->orgDbConName);
            	$group = $modelObj->byId($id)->first();
			}
			else
			{
       	 		$group = Group::byId($id)->first();
			}
		}
		
		return $group;
	}
	
	public function setGroupFavoritedStatus($id, $isFavorited) {
		if(!isset($isFavorited)) {
			$isFavorited = 0;
		}
		
		$groupMember = $this->getGroupMemberDetailsObject($id, FALSE);
		if(isset($groupMember)) 
		{
			$groupMember->is_favorited = $isFavorited;
			$groupMember->save();
		}
	}
	
	public function setGroupLockedStatus($id, $isLocked) {
		if(!isset($isLocked)) {
			$isLocked = 0;
		}
		
		$groupMember = $this->getGroupMemberDetailsObject($id, FALSE);
		if(isset($groupMember)) 
		{
			$groupMember->is_locked = $isLocked;
			$groupMember->save();
		}
	}

	public function getGroupShareJoinInvitationLink($group) 
	{
        $userOrEmpName = $this->getEmployeeOrUserName();
        $groupName = $group->name;

		$androidAppLink = Config::get('app_config.androidAppLink');
		$iosAppLink = Config::get('app_config.iosAppLink');
		$webAppLink = Config::get('app_config.webAppLink');

		$shareJoinGroupStr = $userOrEmpName." is inviting you to join a group."."<br/><br/>";
		$shareJoinGroupStr .= "Group: ".$groupName."<br/>";

		$shareJoinGroupStr .= "If you are a HyLyt user, then join via Android/iOS App or Website."."<br/><br/>";
		$shareJoinGroupStr .= " - OR - <br/><br/>";
		$shareJoinGroupStr .= "If you are not a HyLyt user, you can join HyLyt using the following link."."<br/>";
		$shareJoinGroupStr .= "Android: ".$androidAppLink."<br/>";
		$shareJoinGroupStr .= "iOS: ".$iosAppLink."<br/>";
		$shareJoinGroupStr .= "Web: ".$webAppLink."";

		return $shareJoinGroupStr;
	}

	public function onGroupParticipantInvited($groupId, $group, $recipientName, $recipientEmail)
	{
		$sharedByUserEmail = $this->getEmployeeOrUserEmail();
		/*
		if(isset($this->orgDbConName))
    	{
        	$modelObj = New OrgEmployee;
			$modelObj->setConnection($this->orgDbConName);
        	$employeeWithEmailExist = $modelObj->ofEmail($recipientEmail)->verifiedAndActive()->first();
            if(isset($employeeWithEmailExist))
            {
                $memberId = $employeeWithEmailExist->employee_id;
                $participantIsModerator = 0;
            	$participantIsEmployee = 1;
                $existingParticipantContentId = 0;
                $isScheduled = 1;

                $this->addVideoConferenceParticipant($groupId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled);
            }
            else
            {
            	$appuserWithEmailExists = Appuser::ofEmail($recipientEmail)->verified()->first();
            	if(isset($appuserWithEmailExists))
	            {
	                $participantId = $appuserWithEmailExists->appuser_id;
	                $participantIsModerator = 0;
                	$participantIsEmployee = 0;
	                $existingParticipantContentId = 0;
	                $isScheduled = 1;

	                $this->addVideoConferenceParticipant($groupId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled); 
	            }
	            else
	            {
	                $this->addVideoConferenceParticipantInvitation($groupId, $videoConference, $recipientName, $recipientEmail); 
	            }
            }
    	}
    	else
    	*/
    	{
            $appuserWithEmailExists = Appuser::ofEmail($recipientEmail)->verified()->first();
            if(isset($appuserWithEmailExists))
            {
                $participantAppuserId = $appuserWithEmailExists->appuser_id;

                $isAdmin = 0;
                $isGroupSelfJoined = 0;
		            			
    			$isFavorited = Config::get('app_config.default_group_is_favorited');
    			$isLocked = Config::get('app_config.default_group_is_locked');

                $this->addNewGroupMember($groupId, $group, $participantAppuserId, $isAdmin, $isLocked, $isFavorited, $isGroupSelfJoined, $sharedByUserEmail); 
            }
            else
            {
                $this->addGroupMembershipInvitation($groupId, $group, $recipientName, $recipientEmail); 
            }
    	}
	}
    
    public function addGroupMembershipInvitation($groupId, $group, $recipientName, $recipientEmail)
    {
		$response = array();

		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			       				
			$modelObj = New OrgGroup;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$groupTableName = $modelObj->table;
   			       			
			$memberModelObj = New OrgGroupMember;
			$memberModelObj = $memberModelObj->setConnection($this->orgDbConName);
			$groupMemberTableName = $memberModelObj->table;
   			       			
			$inviteModelObj = New OrgGroupMemberInvite;
			$groupInviteTableName = $inviteModelObj->table;
		}
		else
		{
			$modelObj = New Group;
			$groupTableName = $modelObj->table;		
   			       			
			$memberModelObj = New GroupMember;
			$groupMemberTableName = $memberModelObj->table;
   			       			
			$inviteModelObj = New GroupMemberInvite;
			$groupInviteTableName = $inviteModelObj->table;
		}
		
		if($groupId > 0 && $recipientEmail != '')
		{	
			$groupInvitation = $inviteModelObj->byGroup($groupId)->byEmail($recipientEmail)->first();

			if(!isset($groupInvitation))
			{
	        	$inviteDetails = array();
	            $inviteDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
	        	
	        	if($conName == "")
	        	{
	        		$inviteDetails['group_id'] = $groupId;					
				}
	        	else
	        	{
	        		$inviteDetails['group_id'] = $groupId;
	        		$inviteDetails['organization_id'] = $this->orgId;
				}   

	            $inviteDetails['name'] = $recipientName;
	            $inviteDetails['email'] = $recipientEmail;         	
            	
            	DB::table($groupInviteTableName)->insert($inviteDetails);

				$sharedByEmail = $this->getEmployeeOrUserEmail();
				$sharedByName = $this->getEmployeeOrUserName();

            	MailClass::sendGroupMembershipInvitationMail($this->userId, $this->orgId, $this->orgEmpId, $groupId, $group, $recipientName, $recipientEmail, $sharedByName, $sharedByEmail);
			}					
		}
		
		return $response;
	}
    
    public function setFolderLockedStatus($id, $isLocked)
    {
		if(!isset($isLocked)) {
			$isLocked = 0;
		}
		
		$folder = $this->getFolderObject($id);
		if(isset($folder)) 
		{
			$userOrEmpConstant = $this->getEmployeeOrUserConstantObject();
			if(isset($userOrEmpConstant)) 
            {
                $folderIdStr = $userOrEmpConstant->folder_id_str != null ? $userOrEmpConstant->folder_id_str : '';

                $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                $passcodeFolderIdArr = explode($passcodeFolderIdDelimiter, $folderIdStr);

                $folderExistsAtIndex = array_search($id, $passcodeFolderIdArr);
                if($isLocked)
                {
                	if(!$folderExistsAtIndex)
                	{
                		array_push($passcodeFolderIdArr, $id);
                	}
                }
                else
                {
                	if($folderExistsAtIndex >= 0)
                	{
                		unset($passcodeFolderIdArr[$folderExistsAtIndex]);
                	}                	
                }

                $updPasscodeFolderIdStr = implode($passcodeFolderIdDelimiter, $passcodeFolderIdArr);
                $userOrEmpConstant->folder_id_str = $updPasscodeFolderIdStr;
                $userOrEmpConstant->save();
            }  
		}
	}
    
    public function setFolderFavoritedStatus($id, $isFavorited)
    {
		if(!isset($isFavorited)) {
			$isFavorited = 0;
		}
		
		$folder = $this->getFolderObject($id);
		if(isset($folder)) 
		{
			$folder->is_favorited = $isFavorited;
			$folder->save();
			return $folder->is_favorited;
		}
		else
		{
			return 0;
		}
	}
	
	public function getGroupMemberObject($id) {
		return $this->getGroupMemberDetailsObject($id, TRUE);
	}
	
	public function getGroupMemberDetailsObject($id, $withJoins)
	{
		$groupMember = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgGroupMember;
				$modelObj->setConnection($this->orgDbConName);
            	$groupMember = $modelObj->isEmployeeGroupMember($id, $this->orgEmpId);
            	if($withJoins)
            	{
            		$groupMember = $groupMember->joinGroupTable();
				}
            	$groupMember = $groupMember->first();
			}
			else
			{
       	 		$groupMember = GroupMember::isUserGroupMember($id, $this->userId);
       	 		if($withJoins)
            	{            		
            		$groupMember = $groupMember->joinGroup();
				}
				$groupMember = $groupMember->first();
			}
		}
		
		return $groupMember;
	}
	
	public function getGroupMembers($id)
	{
		$groupMembers = NULL;
			
		if($id > 0)
		{	
			$selectArr = ['email', 'is_admin', 'is_favorited', 'member_id'];	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgGroupMember;
				$tableName = $modelObj->table;
				$modelObj->setConnection($this->orgDbConName);
            	$modelObj = $modelObj->ofGroup($id);
				$modelObj = $modelObj->joinEmployeeTable();
				$nameField = 'employee_name';
				array_push($selectArr, "$tableName.employee_id as employee_id");
				array_push($selectArr, 'has_post_right');
				array_push($selectArr, 'is_ghost');
				array_push($selectArr, "is_verified");
			}
			else
			{
       	 		$modelObj = GroupMember::ofGroup($id);
				$modelObj = $modelObj->joinUserTable();
				$nameField = 'fullname';
				array_push($selectArr, 'member_appuser_id');
			}
			array_push($selectArr, $nameField.' as name');
			$modelObj = $modelObj->select($selectArr);
			$groupMembers = $modelObj->get();
		}
		
		return $groupMembers;
	}
	
	public function getUserIsGroupAdmin($id)
	{
		$isUserAdmin = FALSE;
			
		if($id > 0)
		{	
			if(isset($this->orgId) && $this->orgId > 0)
            {
            	$modelObj = New OrgGroupMember;
				$modelObj->setConnection($this->orgDbConName);
            	$modelObj = $modelObj->isEmployeeGroupAdmin($id, $this->orgEmpId);
			}
			else
			{
       	 		$modelObj = GroupMember::isUserGroupAdmin($id, $this->userId);
			}
			$groupMember = $modelObj->first();
			if(isset($groupMember))
				$isUserAdmin = TRUE;
		}
		
		return $isUserAdmin;
	}
	
	public function getUserHasGroupPostRight($id)
	{
		$hasPostRight = FALSE;
			
		if($id > 0)
		{	
			if(isset($this->orgId) && $this->orgId > 0)
            {
            	$modelObj = New OrgGroupMember;
				$modelObj->setConnection($this->orgDbConName);
            	$modelObj = $modelObj->employeeHasPostRight($id, $this->orgEmpId);
            	
				$groupMember = $modelObj->first();
				if(isset($groupMember))
					$hasPostRight = TRUE;
			}
			else
			{
				$hasPostRight = TRUE;
			}
		}
		
		return $hasPostRight;
	}
	
	public function getAllActiveAndVerifiedEmployees()
	{
		$employees = NULL;
			
		if($this->orgId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployee;
				$modelObj->setConnection($this->orgDbConName);
            	$employees = $modelObj->verifiedAndActive()->get();
			}
		}
		
		return $employees;
	}
	
	public function getAllEmployees()
	{
		$employees = NULL;
			
		if($this->orgId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployee;
				$modelObj->setConnection($this->orgDbConName);
            	$employees = $modelObj->get();
			}
		}
		
		return $employees;
	}
	
	public function getEmployeeObject()
	{
		$employee = NULL;
			
		if($this->orgEmpId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployee;
				$modelObj->setConnection($this->orgDbConName);
            	$employee = $modelObj->byId($this->orgEmpId)->joinDepartmentTable()->joinDesignationTable()->first();
			}
		}
		
		return $employee;
	}
	
	public function getPlainEmployeeObject()
	{
		$employee = NULL;
			
		if($this->orgEmpId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployee;
				$modelObj->setConnection($this->orgDbConName);
            	$employee = $modelObj->byId($this->orgEmpId)->first();
			}
		}
		
		return $employee;
	}
	
	public function getUserObject()
	{
		$user = Appuser::byId($this->userId)->first();
		return $user;
	}
	
	public function getEmployeeIsActive()
	{
		$employeeIsActive = 0;
			
		if($this->orgEmpId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployee;
				$modelObj->setConnection($this->orgDbConName);
            	$employee = $modelObj->select(['is_active'])->byId($this->orgEmpId)->first();
            	
            	if(isset($employee))
            		$employeeIsActive = $employee->is_active;
			}
		}
		
		return $employeeIsActive;
	}
	
	public function getEmployeeConstantObject()
	{
		$employeeConst = NULL;
			
		if($this->orgEmpId > 0)
		{	
			if(isset($this->orgDbConName))
            {
            	$modelObj = New OrgEmployeeConstant;
				$modelObj->setConnection($this->orgDbConName);
            	$employeeConst = $modelObj->ofEmployee($this->orgEmpId)->first();
			}
		}
		
		return $employeeConst;
	}
	
	public function getUserConstantObject()
	{
		$userConst = NULL;
			
		if($this->userId > 0)
		{	
        	$modelObj = New AppuserConstant;
        	$userConst = $modelObj->ofUser($this->userId)->first();
		}
		
		return $userConst;
	}
	
	public function getEmployeeOrUserConstantObject()
	{
		if($this->orgId > 0)
			return $this->getEmployeeConstantObject();
		else
			return $this->getUserConstantObject();
	}
	
	public function getEmployeeOrUserId()
	{
		$empOrUserId = 0;
		if($this->orgId > 0)
		{
			$empOrUserId = $this->orgEmpId;
		}
		else
		{
			$empOrUserId = $this->userId;
		}
		return $empOrUserId;
	}
	
	public function getEmployeeOrUserEmail()
	{
		$email = "";
		if($this->orgId > 0)
		{
			$employee = $this->getEmployeeObject();
			if(isset($employee))
			{
				$email = $employee->email;
			}
		}
		else
		{
			$user = $this->getUserObject();
			if(isset($user))
			{
				$email = $user->email;
			}
		}
		return $email;
	}
	
	public function getEmployeeOrUserName()
	{
		$email = "";
		if($this->orgId > 0)
		{
			$employee = $this->getEmployeeObject();
			if(isset($employee))
			{
				$email = $employee->employee_name;
			}
		}
		else
		{
			$user = $this->getUserObject();
			if(isset($user))
			{
				$email = $user->fullname;
			}
		}
		return $email;
	}
	
	public function getContentAttachmentCount($id, $isFolder)
	{
		$attachmentCount = 0;
			
		$attachments = $this->getContentAttachments($id, $isFolder);
		if(isset($attachments) && count($attachments) > 0)
		{
			$attachmentCount = count($attachments);
		}
		
		return $attachmentCount;
	}
	
	public function getOrganizationParams()
	{
		return $orgSubscription = OrganizationSubscription::ofOrganization($this->orgId)->first();
	}
	
	public function getUserCountParams()
	{
		$allocatedUserCount = 0;
		$usedUserCount = 0;
		$availableUserCount = 0;
		$orgSubscription = $this->getOrganizationParams();
		
		if(isset($orgSubscription))
		{
			$allocatedUserCount = $orgSubscription->user_count;
			$usedUserCount = $orgSubscription->used_user_count;
			$availableUserCount = $allocatedUserCount - $usedUserCount;
			
			if($availableUserCount < 0)
				$availableUserCount = 0;
		}
		
		$userParams = array();
		$userParams['alloc'] = $allocatedUserCount;
		$userParams['used'] = $usedUserCount;
		$userParams['avail'] = $availableUserCount;
		
		return $userParams;
	}
	
	public function getQuotaParams()
	{
		$allocatedQuotaMb = 0;
		$usedQuotaMb = 0;
		$availableQuotaMb = 0;
		$orgSubscription = $this->getOrganizationParams();
		
		if(isset($orgSubscription))
		{
			$allocatedQuotaGb = $orgSubscription->allotted_quota_in_gb;
			$allocatedQuotaMb = $allocatedQuotaGb*1024;
			$usedQuotaMb = $orgSubscription->used_quota_in_mb;
			$availableQuotaMb = $allocatedQuotaMb - $usedQuotaMb;
			
			if($availableQuotaMb < 0)
				$availableQuotaMb = 0;
		}
		
		$userParams = array();
		$userParams['alloc'] = $allocatedQuotaMb;
		$userParams['used'] = $usedQuotaMb;
		$userParams['avail'] = $availableQuotaMb;
		
		return $userParams;
	}
	
	public function recalculateOrgSubscriptionParams()
	{
		$quotaParams = $this->getQuotaParams();
		if(isset($quotaParams['alloc']))
		{
			$allocQuotaMb = $quotaParams['alloc'];
			$usedQuotaMb = $quotaParams['used'];
			$actUsedQuotaKb = $this->calculateActualUsedQuota();
			
			$actUsedQuotaMb = ceil($actUsedQuotaKb/1024);
			
			if($usedQuotaMb != $actUsedQuotaMb)
			{
				$this->setUsedQuota($actUsedQuotaMb);
			}
		}
		$userCountParams = $this->getUserCountParams();
		if(isset($userCountParams['alloc']))
		{
			$allocUserCnt = $userCountParams['alloc'];
			$usedUserCnt = $userCountParams['used'];
			$actUsedUserCnt = $this->calculateActualUserCount();
			
			if($usedUserCnt != $actUsedUserCnt)
			{
				$this->setUsedUserCount($actUsedUserCnt);
			}
		}
	}
	
	public function calculateActualUsedQuota()
	{		
		$allocEmpSpaceKb = 0;
		$empModel = New OrgEmployee;
		$empModel->setConnection($this->orgDbConName);
		$orgEmployees = $empModel->ofDistinctEmployee()->joinConstantTable()->get();
		if(isset($orgEmployees))
		{		
			foreach($orgEmployees as $orgEmp)
			{
				$allocEmpSpaceKb += $orgEmp->attachment_kb_allotted;
			}
		}
		$allocGroupSpaceKb = 0;
		$grpModel = New OrgGroup;
		$grpModel->setConnection($this->orgDbConName);
		$orgGroups = $grpModel->get();
		if(isset($orgGroups))
		{		
			foreach($orgGroups as $orgGrp)
			{
				$allocGroupSpaceKb += $orgGrp->allocated_space_kb;
			}
		}
		
		$usedQuota = $allocEmpSpaceKb + $allocGroupSpaceKb;
		
		return $usedQuota;
	}
	
	public function calculateActualUserCount()
	{
		$usedUserCount = 0;
		
		$empModel = New OrgEmployee;
		$empModel->setConnection($this->orgDbConName);
		$orgEmployees = $empModel->ofDistinctEmployee()->get();
		
		if(isset($orgEmployees))
		{
			$usedUserCount = count($orgEmployees);
		}
		
		return $usedUserCount;
	}
	
	public function setUsedQuota($usedQuota)
	{
		if($usedQuota < 0)
		{
			$usedQuota = 0;
		}
		
		$orgSubscription = $this->getOrganizationParams();
		
		if(isset($orgSubscription))
		{
			$orgSubscription->used_quota_in_mb = $usedQuota;
			$orgSubscription->save();
		}
	}
	
	public function setUsedUserCount($usedUserCount)
	{
		if($usedUserCount < 0)
		{
			$usedUserCount = 0;
		}
		
		$orgSubscription = $this->getOrganizationParams();
		
		if(isset($orgSubscription))
		{
			$orgSubscription->used_user_count = $usedUserCount;
			$orgSubscription->save();
		}
	}
	
    public function setGroupContentCreator($id, $memberId)
    {
    	$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			       			
			$modelObj = New OrgGroupContent;
			$modelObj->setConnection($this->orgDbConName);
		}
		else
		{       		
			$modelObj = New GroupContent;
		}
		
        $userContent = $modelObj->byId($id)->first();
		if(isset($userContent))
        {
	        $contentDetails = array();
	        $contentDetails['created_by_member_id'] = $memberId;
	        
        	$userContent->update($contentDetails);
		}
	}
	
	public function getGroupMemberContentCount($groupId, $memberId)
	{
		$contentCount = 0;
		$isFolder = FALSE;
		$modelObj = $this->getAllContentModelObj($isFolder, $groupId);
		if(isset($modelObj))
		{
			$contentCount = $modelObj->where('created_by_member_id', '=', $memberId)->count();
		}
		return $contentCount;
	}
	
	public function getAllContentModelObj($isFolder, $folderOrGroupId = NULL, $exceptRemoved = TRUE, $searchStr = NULL, $sortBy = NULL, $sortOrder = NULL)
	{
		$sortByContent = Config::get('app_config.sort_by_content');
		$sortByType = Config::get('app_config.sort_by_type');
		$sortByCreateDate = Config::get('app_config.sort_by_create_date');
		$sortByUpdateDate = Config::get('app_config.sort_by_update_date');
		$sortByDueDate = Config::get('app_config.sort_by_due_date');
		$sortByFolder = Config::get('app_config.sort_by_folder');
		$sortByTag = Config::get('app_config.sort_by_tag');
	
		$modelObj = NULL;	
		if(isset($this->orgDbConName))
        {
        	if($isFolder)	                    	
			{		
				$modelObj = New OrgEmployeeContent;
				$modelObj->setConnection($this->orgDbConName);
        		$modelObj = $modelObj->ofEmployee($this->orgEmpId);
        		
        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        			$modelObj = $modelObj->ofFolder($folderOrGroupId);

        		if(isset($folderOrGroupId) && $folderOrGroupId == -2)
        		{
                    $modelObj = $modelObj->filterIsRemoved();
        		}
        		elseif($exceptRemoved == TRUE)
        		{
        			$modelObj = $modelObj->filterExceptRemoved();
        		}
        			
        		$modelObj = $modelObj->joinFolder();
        		$modelObj = $modelObj->joinSource();

				$folderModelObj = New OrgEmployeeFolder;
				$folderTablename = $folderModelObj->table;
                $modelObj = $modelObj->where($folderTablename.'.employee_id', '=', $this->orgEmpId);
			}
			else
			{        		
				$modelObj = New OrgGroupContent;
				$modelObj->setConnection($this->orgDbConName);
				if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        		{
        			$modelObj = $modelObj->ofGroup($folderOrGroupId);
        		}
        		else
        		{
        			$empGroupIdArr = [];
        			$employeeGroups = $this->getAllGroupsFoUser();
        			foreach ($employeeGroups as $ind => $employeeGroup) {
        				$empGrpId = $employeeGroup->group_id;
        				$empGroupIdArr[$ind] = $empGrpId;
        			}
        			$modelObj = $modelObj->filterGroup($empGroupIdArr);
        		}
        		$modelObj = $modelObj->joinGroup();
			}
		}
		else
		{
        	if($isFolder)	                    	
			{
   	 			$modelObj = AppuserContent::ofUser($this->userId);
        		
        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        			$modelObj = $modelObj->ofFolder($folderOrGroupId); 

        		if(isset($folderOrGroupId) && $folderOrGroupId == -2)
        		{
                    $modelObj = $modelObj->filterIsRemoved();
        		}
        		elseif($exceptRemoved == TRUE)
        		{
                    $modelObj = $modelObj->filterExceptRemoved();
        		}
        			
        		$modelObj = $modelObj->joinFolder();
        		$modelObj = $modelObj->joinSource();

				$folderModelObj = New AppuserFolder;
				$folderTablename = $folderModelObj->table;
                $modelObj = $modelObj->where($folderTablename.'.appuser_id', '=', $this->userId);
			}
			else
			{
   	 			$modelObj = New GroupContent;
   	 			if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        		{
        			$modelObj = $modelObj->ofGroup($folderOrGroupId);
        		}
        		else
        		{
        			$userGroupIdArr = [];
        			$userGroups = $this->getAllGroupsFoUser();
        			foreach ($userGroups as $ind => $userGroup) {
        				$usrGrpId = $userGroup->group_id;
        				$userGroupIdArr[$ind] = $usrGrpId;
        			}
        			$modelObj = $modelObj->filterGroup($userGroupIdArr);
        		}
        		$modelObj = $modelObj->joinGroup();					
			}
		}
		
		if(isset($modelObj))
    	{    
    		// $modelObj = $modelObj->orderBy("is_marked", "DESC");		
    		/*if(isset($sortBy) && $sortBy > 0)
    		{
    			$sortOrderStr = "DESC";
				if(isset($sortOrder) && $sortOrder > 0)
					$sortOrderStr = "ASC";
				
				$sortColStr = "";
				switch ($sortBy) {
				    case $sortByType:
				        $sortColStr = "content_type_id";
				        break;
				    case $sortByCreateDate:
				        $sortColStr = "create_timestamp";
				        break;
				    case $sortByUpdateDate:
				        $sortColStr = "update_timestamp";
				        break;
				    case $sortByDueDate:
				        $sortColStr = "from_timestamp";
				        break;
				    case $sortByFolder:
				        $sortColStr = "folder_name";
				    case $sortByTag:
				        $sortColStr = "tagStr";
				        break;
				    default:
        				$sortColStr = "";
        				break;
				}
				if($sortColStr != "")
    				$modelObj = $modelObj->orderBy($sortColStr, $sortOrderStr);
			}*/
		}
    		
		return $modelObj;
	}
	
	public function getAllContents($isFolder, $folderOrGroupId = NULL)
	{
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder, $folderOrGroupId);	
		if(isset($modelObj))
		{
			if($isFolder)
			{
				$contentTableName = $this->getContentTablename($isFolder);
				$modelObj->select(['*', "$contentTableName.folder_id as folderId"]);
			}
			
			$contents = $modelObj->get();
		}
		return $contents;
	}
	
	public function getAllFolders()
	{	
		$folders = NULL;
		$foldersModelObj = $this->getAllFoldersModelObj();
		if(isset($foldersModelObj))
			$folders = $foldersModelObj->get();
		return $folders;
	}
	
	public function getAllFoldersModelObj()
	{
		$foldersModelObj = NULL;
		if(isset($this->orgDbConName))
        {
			$modelObj = New OrgEmployeeFolder;
			$modelObj->setConnection($this->orgDbConName);
        	$foldersModelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$foldersModelObj = AppuserFolder::ofUser($this->userId);
		}			
		return $foldersModelObj;
	}
	
	public function getDefaultFolderId()
	{
		$defaultFolderId = 0;
		$constantObj = $this->getEmployeeOrUserConstantObject();
		if(isset($constantObj))
		{
			$defaultFolderId = $constantObj->def_folder_id;
		}
		return $defaultFolderId;
	}
	
	public function getDefaultFolder()
	{
		$defaultFolderId = $this->getDefaultFolderId();
		$folderObj = $this->getFolderObject($defaultFolderId);
		return $folderObj;
	}
	
	public function getSentFolderId()
	{
		$sentFolderId = 0;
		$sentFolder = $this->getSentFolder();
		if(isset($sentFolder))
		{
			if(isset($this->orgDbConName))
       		{
				$sentFolderId = $sentFolder->employee_folder_id;
			}
			else
			{
				$sentFolderId = $sentFolder->appuser_folder_id;
			}
		}
		return $sentFolderId;
	}
	
	public function getSentFolder()
	{
		$folderModelObj = NULL;
		if(isset($this->orgDbConName))
        {
			$modelObj = New OrgEmployeeFolder;
			$modelObj->setConnection($this->orgDbConName);
        	$folderModelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$folderModelObj = AppuserFolder::ofUser($this->userId);
		}	
		
		$folderModelObj = $folderModelObj->isSentFolder()->first();
		
		if(!isset($folderModelObj))
		{
			$name = Config::get('app_config.sent_folder_name');
			$iconCode = Config::get('app_config.default_folder_icon_code');
			$isFavorited = 0;
			$folderTypeId = FolderType::$TYPE_SENT_FOLDER_ID;
			$folderResponse = $this->addEditFolder(0, $name, $iconCode, $isFavorited, $folderTypeId);
			$sentFolderId = $folderResponse['syncId'];
			
			if($sentFolderId > 0)
			{
				$folderModelObj = $this->getFolderObject($sentFolderId);
				
				if(isset($this->currLoginToken) && $this->currLoginToken != "")
				{
					if(isset($this->orgDbConName))
	        		{
	        			$this->sendOrgFolderAddMessageToDevice($this->orgId, $this->orgEmpId, $this->currLoginToken, $sentFolderId);
					}
					else
					{
						$this->sendFolderAddMessageToDevice($this->userId, $this->currLoginToken, $sentFolderId);
					}
				}
			}	
		}
				
		return $folderModelObj;
	}
	
	public function getAllTagsModelObj()
	{
		$tagsModelObj = NULL;
		if(isset($this->orgDbConName))
        {
			$modelObj = New OrgEmployeeTag;
			$modelObj->setConnection($this->orgDbConName);
        	$tagsModelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$tagsModelObj = AppuserTag::ofUser($this->userId);
		}
		return $tagsModelObj;
	}
	
	public function getAllTags()
	{
		$tags = NULL;
		$tagsModelObj = $this->getAllTagsModelObj();
		if(isset($tagsModelObj))
			$tags = $tagsModelObj->get();
		return $tags;
	}
	
	public function getAllSourcesModelObj()
	{
		$sourcesModelObj = NULL;
		if(isset($this->orgDbConName))
        {
			$modelObj = New OrgEmployeeSource;
			$modelObj->setConnection($this->orgDbConName);
        	$sourcesModelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$sourcesModelObj = AppuserSource::ofUser($this->userId);
		}		
		return $sourcesModelObj;
	}
	
	public function getAllSources()
	{
		$sources = NULL;
		$sourcesModelObj = $this->getAllSourcesModelObj();
		if(isset($sourcesModelObj))
			$sources = $sourcesModelObj->get();	
		return $sources;
	}
	
	public function getContentTablename($isFolder)
	{
		$tablename = "";
		if($isFolder)
		{
			if($this->orgId > 0)
			{
				$modelObj = New OrgEmployeeContent;
			}
			else
			{
				$modelObj = New AppuserContent;
			}
		}
		else
		{
			if($this->orgId > 0)
			{
				$modelObj = New OrgGroupContent;
			}
			else
			{
				$modelObj = New GroupContent;
			}
		}
		$tablename = $modelObj->table;
		return $tablename;
	}
	
	public function getContentAttachmentTablename($isFolder)
	{
		$tablename = "";
		if($isFolder)
		{
			if($this->orgId > 0)
			{
				$modelObj = New OrgEmployeeContentAttachment;
			}
			else
			{
				$modelObj = New AppuserContentAttachment;
			}
		}
		else
		{
			if($this->orgId > 0)
			{
				$modelObj = New OrgGroupContentAttachment;
			}
			else
			{
				$modelObj = New GroupContentAttachment;
			}
		}
		$tablename = $modelObj->table;
		return $tablename;
	}
	
	public function getFolderObject($id)
	{
		$folder = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeFolder;
				$modelObj->setConnection($this->orgDbConName);
            	$folder = $modelObj->byId($id)->first();
			}
			else
			{
       	 		$folder = AppuserFolder::byId($id)->first();
			}
		}
		
		return $folder;
	}
	
	public function getTagObject($id)
	{
		$tag = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeTag;
				$modelObj->setConnection($this->orgDbConName);
            	$tag = $modelObj->byId($id)->first();
			}
			else
			{
       	 		$tag = AppuserTag::byId($id)->first();
			}
		}
		
		return $tag;
	}
	
	public function getSourceObject($id)
	{
		$source = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeSource;
				$modelObj->setConnection($this->orgDbConName);
            	$source = $modelObj->byId($id)->first();
			}
			else
			{
       	 		$source = AppuserSource::byId($id)->first();
			}
		}
		
		return $source;
	}
	
	public function getFolderObjectByName($name)
	{
		$folder = NULL;
			
		if(isset($name) && $name != "")
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeFolder;
				$modelObj->setConnection($this->orgDbConName);
            	$folder = $modelObj->ofEmployee($this->orgEmpId)->byName($name)->first();
			}
			else
			{
       	 		$folder = AppuserFolder::ofUser($this->userId)->byName($name)->first();
			}
		}
		
		return $folder;
	}
	
	public function getTagObjectByName($name)
	{
		$tag = NULL;
			
		if(isset($name) && $name != "")
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeTag;
				$modelObj->setConnection($this->orgDbConName);
            	$tag = $modelObj->ofEmployee($this->orgEmpId)->byName($name)->first();
			}
			else
			{
       	 		$tag = AppuserTag::ofUser($this->userId)->byName($name)->first();
			}
		}
		
		return $tag;
	}
	
	public function getSourceObjectByName($name)
	{
		$source = NULL;
			
		if(isset($name) && $name != "")
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgEmployeeSource;
				$modelObj->setConnection($this->orgDbConName);
            	$source = $modelObj->ofEmployee($this->orgEmpId)->byName($name)->first();
			}
			else
			{
       	 		$source = AppuserSource::ofUser($this->userId)->byName($name)->first();
			}
		}
		
		return $source;
	}
	
	public function getAllGroupsFoUser($searchStr = NULL)
	{
		$modelObj = $this->getAllGroupsForUserModelObj($searchStr);
		$groups = $modelObj->get();	
		return $groups;
	}
	
	public function getAllGroupsForUserModelObj($searchStr = NULL)
	{
		//$groupModelObj = NULL;
		if(isset($this->orgDbConName))
        {
        	$modelObj = New OrgGroupMember;
			$modelObj->setConnection($this->orgDbConName);
			if($this->orgEmpId > 0)
        		$modelObj = $modelObj->ofEmployee($this->orgEmpId);
        		
        	$modelObj = $modelObj->ofDistinctGroup();
			$modelObj = $modelObj->joinGroupTable();  
			
			//$groupModelObj = New OrgGroup;
		}
		else
		{
   	 		$modelObj = GroupMember::ofDistinctGroup();
   	 		if($this->userId > 0)
   	 			$modelObj = $modelObj->ofUser($this->userId);
   	 			
			$modelObj = $modelObj->joinGroup();
			
			//$groupModelObj = New Group;  
		}	
		      
        if(isset($searchStr) && $searchStr != "")
        {		
        	$modelObj = $modelObj->where(function($query) use ($searchStr)
				            {
				                $query->where('name','like',"%$searchStr%");
				            });		
		}	
		
		//$contentTableName = $groupModelObj->table;
		//$idColName = 'group_id';
		
	//	$selectArr = [ "*", "$contentTableName.$idColName as groupId" ];
	//	$modelObj->select($selectArr);
		
		return $modelObj;
	}
	
	public function getAllGroupsForOrganization()
	{
		$groups = NULL;
		if(isset($this->orgDbConName))
        {
        	$modelObj = New OrgGroup;
			$modelObj->setConnection($this->orgDbConName);
			$groups = $modelObj->get();	
		}
		else
		{
        	$groups = Group::get();	
		}	
		return $groups;
	}

	/* Implementation Pending */
    public function addEditGroup($groupId, $name)
    {
    }
    
    public function addNewGroupMember($groupId, $group, $memberEmpOrUserId, $isAdmin, $isLocked, $isFavorited, $isGroupSelfJoined, $sharedByUserEmail)
    {
		$response = array();
		$syncId = 0;
		                
		if(isset($this->orgDbConName))
    	{
    	}
    	else
    	{
			$existingMemberUserIdArr = array();
			$isUserGroupMember = GroupMember::isUserGroupMember($groupId, $memberEmpOrUserId)->first();

			if(!isset($isUserGroupMember))
			{
				$groupMember = New GroupMember;
				$groupMember->group_id = $groupId;
				$groupMember->member_appuser_id = $memberEmpOrUserId;
				$groupMember->is_admin = $isAdmin;
				$groupMember->is_locked = $isLocked;
				$groupMember->is_favorited = $isFavorited;
				$groupMember->is_self_joined = $isGroupSelfJoined;
				$groupMember->save();	

				$user = $this->getUserObject();

				$isAddOp = 1;
				$isRename = 0;		
				$this->sendGroupAddedMessageToDevice($memberEmpOrUserId, $groupId, $isRename, $sharedByUserEmail, NULL, $isAddOp);				
				MailClass::sendUserAddedToGroupMail($memberEmpOrUserId, $group, $user);
			}
    	}			
		
		$response["syncId"] = $syncId;		
		return $response;
	}
	
	public function getAllContentTypes()
	{
		$contentTypes = ContentType::get();
		return $contentTypes;
	}
	
	public function getContentTypeObject($id)
	{
       	$contentType = ContentType::byId($id)->first();
		return $contentType;
	}
	
	private function makeCurlServerCall($url, $data)
	{
		$postData = http_build_query($data);		
		
		$ch = curl_init();	
		
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    
		curl_exec($ch);
		curl_close($ch);
	}
	
	public function logAttachmentAsDeleted($id)
	{
		$depTypeId = DependencyType::$ATTACHMENT_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logContentAsDeleted($id, $isFolder)
	{
		if($isFolder)
			$depTypeId = DependencyType::$FOLDER_CONTENT_TYPE_ID;
		else
			$depTypeId = DependencyType::$GROUP_CONTENT_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logTagAsDeleted($id)
	{
		$depTypeId = DependencyType::$TAG_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logSourceAsDeleted($id)
	{
		$depTypeId = DependencyType::$SOURCE_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logFolderAsDeleted($id)
	{
		$depTypeId = DependencyType::$FOLDER_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logVideoConferenceAsDeleted($id)
	{
		$depTypeId = DependencyType::$CONFERENCE_TYPE_ID;
		$this->logDependencyAsDeleted($depTypeId, $id);
	}
	
	public function logDependencyAsDeleted($depTypeId, $id)
	{
		$currTs = CommonFunctionClass::getCurrentTimestamp();		
		$userOrEmpId = $this->userId;
		
		$modelObj = New DeletedDependency;
		if(isset($this->orgDbConName))
    	{		      			
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$userOrEmpId = $this->orgEmpId;
		}
		
		$modelObj->appuser_id = $userOrEmpId;
		$modelObj->id = $id;
		$modelObj->dependency_type_id = $depTypeId;
		$modelObj->deleted_at = $currTs;
		$modelObj->save();
	}
	
	public function getAllFoldersToBeSynced($dtStr)
	{		
		/*$pkIdName = 'appuser';
		if($this->orgId > 0)
			$pkIdName = 'employee';*/

		$modelObj = $this->getAllFoldersModelObj();
		//$modelObj->select([$pkIdName.'_folder_id as folder_id', 'folder_name']);		
        $modelObj->where(function($modelObj) use ($dtStr){
			$modelObj->orWhere('created_at', '>=', $dtStr);
			$modelObj->orWhere('updated_at', '>=', $dtStr);
        });
		
		return $modelObj->get();
	}
	
	public function getAllTagsToBeSynced($dtStr)
	{		
		/*$pkIdName = 'appuser';
		if($this->orgId > 0)
			$pkIdName = 'employee';*/

		$modelObj = $this->getAllTagsModelObj();
		//$modelObj->select([$pkIdName.'_tag_id as tag_id', 'tag_name']);		
        $modelObj->where(function($modelObj) use ($dtStr){
			$modelObj->orWhere('created_at', '>=', $dtStr);
			$modelObj->orWhere('updated_at', '>=', $dtStr);
        });
		
		return $modelObj->get();
	}
	
	public function getAllSourcesToBeSynced($dtStr)
	{		
		/*$pkIdName = 'appuser';
		if($this->orgId > 0)
			$pkIdName = 'employee';*/

		$modelObj = $this->getAllSourcesModelObj();
		//$modelObj->select([$pkIdName.'_source_id as source_id', 'source_name']);		
        $modelObj->where(function($modelObj) use ($dtStr){
			$modelObj->orWhere('created_at', '>=', $dtStr);
			$modelObj->orWhere('updated_at', '>=', $dtStr);
        });
		
		return $modelObj->get();
	}
	
	public function getAllContentsToBeSynced($isFolder, $dtStr, $grpId = NULL, $isGrpPrimarySynced = FALSE)
	{		
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId, FALSE);		
		if(isset($modelObj))
		{
			if($isFolder || (!$isFolder && $isGrpPrimarySynced))
			{
				$tablename = $this->getContentTablename($isFolder);

				if($isFolder)
				{
					$modelObj = $modelObj->removedConsiderationForSync();
				}
				
		        $modelObj->where(function($modelObj) use ($dtStr, $tablename){
					$modelObj->orWhere($tablename.'.created_at', '>=', $dtStr);
					$modelObj->orWhere($tablename.'.updated_at', '>=', $dtStr);
		        });
			}
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}
	
	public function getAllVideoConferencesToBeSynced($dtStr)
	{		
		$videoConferences = NULL;
		$modelObj = $this->getAllVideoConferenceModelObj();		
		if(isset($modelObj))
		{
	        $modelObj->where(function($modelObj) use ($dtStr, $tablename){
				$modelObj->orWhere($tablename.'.created_at', '>=', $dtStr);
				$modelObj->orWhere($tablename.'.updated_at', '>=', $dtStr);
	        });
	        
	        $videoConferences = $modelObj->get();
		}	
		return $videoConferences;
	}
	
	public function getAllSoftDeletedContentsToBeSynced($isFolder, $dtStr, $grpId = NULL, $isGrpPrimarySynced = FALSE)
	{		
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId, FALSE);		
		if(isset($modelObj))
		{
			if($isFolder || (!$isFolder && $isGrpPrimarySynced))
			{
				$tablename = $this->getContentTablename($isFolder);

				if($isFolder)
				{
					$modelObj = $modelObj->filterIsDeletedPermanently();
				}
				
		        $modelObj->where(function($modelObj) use ($dtStr, $tablename){
					$modelObj->orWhere($tablename.'.created_at', '>=', $dtStr);
					$modelObj->orWhere($tablename.'.updated_at', '>=', $dtStr);
		        });
			}
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}
	
	public function getAllDeletedContentsToBeSynced($isFolder, $dtStr)
	{
		if($isFolder)
			$depTypeId = DependencyType::$FOLDER_CONTENT_TYPE_ID;
		else
			$depTypeId = DependencyType::$GROUP_CONTENT_TYPE_ID;
		return $this->getAllDeletedDependencyToBeSynced($dtStr, $depTypeId, $isFolder);
	} 
	
	public function getAllDeletedSourcesToBeSynced($dtStr)
	{
		$depTypeId = DependencyType::$SOURCE_TYPE_ID;
		return $this->getAllDeletedDependencyToBeSynced($dtStr, $depTypeId);
	} 
	
	public function getAllDeletedTagsToBeSynced($dtStr)
	{
		$depTypeId = DependencyType::$TAG_TYPE_ID;
		return $this->getAllDeletedDependencyToBeSynced($dtStr, $depTypeId);
	} 
	
	public function getAllDeletedFoldersToBeSynced($dtStr)
	{
		$depTypeId = DependencyType::$FOLDER_TYPE_ID;
		return $this->getAllDeletedDependencyToBeSynced($dtStr, $depTypeId);
	} 
	
	public function getAllDeletedVideoConferencesToBeSynced($dtStr)
	{
		$depTypeId = DependencyType::$CONFERENCE_TYPE_ID;
		return $this->getAllDeletedDependencyToBeSynced($dtStr, $depTypeId);
	} 
	
	public function getAllDeletedDependencyToBeSynced($dtStr, $depTypeId, $isFolder = NULL)
	{        
        $userOrEmpId = $this->userId;
		$modelObj = New DeletedDependency;
		if(isset($this->orgDbConName))
    	{		      			
			$modelObj->setConnection($this->orgDbConName);
			$userOrEmpId = $this->orgEmpId;
		}
		
		if(!isset($isFolder))
		{
			$modelObj = $modelObj->where('appuser_id', '=', $userOrEmpId);
		}
		$modelObj = $modelObj->where('dependency_type_id', '=', $depTypeId);
		$modelObj = $modelObj->where('deleted_at', '>=', $dtStr);
		
		return $modelObj->get();
	}
	
	public function getAllContentsCreatedInDuration($isFolder, $grpId = NULL, $startDtStr, $endDtStr)
	{
		$tablename = $this->getContentTablename($isFolder, $grpId);
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId);
		$modelObj->where($tablename.'.created_at', '>=', $startDtStr)
				 ->where($tablename.'.created_at', '<=', $endDtStr);
		return $modelObj->get();
	}
	
	public function getAllContentsUpdatedInDuration($isFolder, $grpId = NULL, $startDtStr, $endDtStr)
	{
		$tablename = $this->getContentTablename($isFolder, $grpId);
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId);
		$modelObj->where($tablename.'.updated_at', '>=', $startDtStr)
				 ->where($tablename.'.updated_at', '<=', $endDtStr);
				 
		/*$modelObj->where(function($query) use ($tablename, $startDtStr){
		        $query->where($tablename.'.created_at', '=', 'NULL')
		              ->orWhere($tablename.'.created_at', '<', $startDtStr);
    	});*/
		// ->where($tablename.'.created_at', '<', $startDtStr);
		
		return $modelObj->get();
	}
	
	public function getAllContentAttachmentsAddedInDuration($isFolder, $grpId = NULL, $startDtStr, $endDtStr)
	{
		$tablename = $this->getContentAttachmentTablename($isFolder, $grpId);
		$modelObj = $this->getAllAttachmentModelObj($isFolder, $grpId);
        $modelObj->where(function($modelObj) use ($tablename, $startDtStr, $endDtStr){
			$modelObj->orWhere(function($modelObj) use ($tablename, $startDtStr, $endDtStr) {
				$modelObj->where($tablename.'.created_at', '>=', $startDtStr)
				 		 ->where($tablename.'.created_at', '<=', $endDtStr);
			});
			$modelObj->orWhere(function($modelObj) use ($tablename, $startDtStr, $endDtStr) {
				$modelObj->where($tablename.'.updated_at', '>=', $startDtStr)
				 		 ->where($tablename.'.updated_at', '<=', $endDtStr);				
			});
        });
		$attachments = $modelObj->joinContentTable()->get();
		
		return $attachments;
	}
	
	public function getAllDueContents($minTs, $maxTs, $isFolder, $grpId = NULL)
	{		
        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId);		
		if(isset($modelObj))
		{
			$modelObj->whereIn('content_type_id', [ $typeR, $typeC]);
			$modelObj->whereBetween('from_timestamp', [ $minTs, $maxTs]);
			$contentTableName = $this->getContentTablename($isFolder);
			
			$idColName = "";
			if($isFolder)
            {
				$modelObj = $modelObj->filterExceptRemoved();
                if($this->orgId > 0)
                {
                	$idColName = 'employee_content_id';
				}
				else
				{
                	$idColName = 'appuser_content_id';
				}						
			}
			else
			{
               $idColName = 'group_content_id';
			}
			
			$selectArr = [ "*", "$contentTableName.$idColName as content_id" ];
			
			if($isFolder)
			{
				array_push($selectArr, "$contentTableName.folder_id as folderId");
				// $modelObj->isNotSentFolder();
			}
			
			$modelObj->select($selectArr);
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}
	
	public function getAllDueContentsForDateRange($minTs, $maxTs, $isFolder)
	{		
        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder);		
		if(isset($modelObj))
		{
			$modelObj->whereIn('content_type_id', [ $typeR, $typeC]);
			$modelObj->whereBetween('from_timestamp', [ $minTs, $maxTs]);
			$contentTableName = $this->getContentTablename($isFolder);
			
			$idColName = "";
			if($isFolder)
            {
				$modelObj = $modelObj->filterExceptRemoved();
                if($this->orgId > 0)
                {
                	$idColName = 'employee_content_id';
				}
				else
				{
                	$idColName = 'appuser_content_id';
				}						
			}
			else
			{
               $idColName = 'group_content_id';
			}
			
			$selectArr = [ "*", "$contentTableName.$idColName as content_id" ];
			
			if($isFolder)
			{
				array_push($selectArr, "$contentTableName.folder_id as folderId");
				// $modelObj->isNotSentFolder();
			}
			
			$modelObj->select($selectArr);
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}
	
	public function getAllFormulatedDueContentsForDateRange($contentListFormulationObj, $minTs, $maxTs, $isFolder)
	{
		$formulatedRepeatContentArr = array();
		$sortArrForDueContents = array();

        $isFolderFlag = $isFolder ? 1 : 0;
        $folderOrGroupIdFieldName = $isFolder ? "folderId" : "groupId";

        $searchStr = '';
        $hasFilters = 0;
        $filShowAttachment = -1;
        $filAttachmentExtArr = array();
        $folderContents = $this->getAllDueContentsForDateRange($minTs, $maxTs, $isFolder);

        if(isset($folderContents))
        {
            foreach($folderContents as $content) {
                $contentObj = $contentListFormulationObj->formulateContentObject($this, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                if(isset($contentObj)) {
	            	$folderGroupId = $content->{$folderOrGroupIdFieldName};
	            	$encFolderGroupId = sracEncryptNumberData($folderGroupId, $this->appuserSession);

                    $contentObj['isFolder'] = $isFolderFlag;
                    $contentObj['fOrGId'] = $encFolderGroupId;
                    array_push($formulatedRepeatContentArr, $contentObj);
                    array_push($sortArrForDueContents, $contentObj['startUtc']);
                }
            }
        }

        $response = array();
        $response['formulatedContentArr'] = $formulatedRepeatContentArr;
        $response['sortArrForContents'] = $sortArrForDueContents;

        return $response;
	}
	
	public function getAllDueContentsForRepeatDailyWeeklyMonthlyYearly($repeatType, $minTs, $maxTs, $isFolder, $grpId = NULL, $consQueryDatePatternStr = NULL)
	{		
        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");

        $repeatTypeDaily = 'DAILY';
        $repeatTypeWeekly = 'WEEKLY';
        $repeatTypeMonthly = 'MONTHLY';
        $repeatTypeYearly = 'YEARLY';
        
		$contents = NULL;
		$modelObj = $this->getAllContentModelObj($isFolder, $grpId);		
		if(isset($modelObj))
		{
			$modelObj->whereIn('content_type_id', [ $typeC ]);
			$modelObj->where('repeat_duration', '=', $repeatType);
			$modelObj->where('from_timestamp', '<', $minTs);
			// $modelObj->where('to_timestamp', '>=', $minTs);

			$contentTableName = $this->getContentTablename($isFolder);
			
			$idColName = "";
			if($isFolder)
            {
				$modelObj = $modelObj->filterExceptRemoved();
                if($this->orgId > 0)
                {
                	$idColName = 'employee_content_id';
				}
				else
				{
                	$idColName = 'appuser_content_id';
				}						
			}
			else
			{
               $idColName = 'group_content_id';
			}
			
			$selectArr = [ "*", "$contentTableName.$idColName as content_id" ];
			
			if($isFolder)
			{
				array_push($selectArr, "$contentTableName.folder_id as folderId");
				// $modelObj->isNotSentFolder();
			}

	        $colWeekDay = "weekDay";
	        $colMonthDay = "monthDay";
	        $colMonthYear = "monthYear";

			if($repeatType == $repeatTypeDaily)
			{

			}
			else if($repeatType == $repeatTypeWeekly)
			{
				array_push($selectArr, \DB::raw("DATE_FORMAT(FROM_UNIXTIME(from_timestamp/1000), '%w') AS ".$colWeekDay));

				if(isset($consQueryDatePatternStr) && $consQueryDatePatternStr != "")
				{
					$modelObj->havingRaw($colWeekDay." IN (".$consQueryDatePatternStr.")");
				}
			}
			else if($repeatType == $repeatTypeMonthly)
			{
				array_push($selectArr, \DB::raw("DATE_FORMAT(FROM_UNIXTIME(from_timestamp/1000), '%d') AS ".$colMonthDay));

				if(isset($consQueryDatePatternStr) && $consQueryDatePatternStr != "")
				{
					$modelObj->havingRaw($colMonthDay." IN (".$consQueryDatePatternStr.")");
				}
			}
			else if($repeatType == $repeatTypeYearly)
			{
				array_push($selectArr, \DB::raw("DATE_FORMAT(FROM_UNIXTIME(from_timestamp/1000), '%d%m') AS ".$colMonthYear));

				if(isset($consQueryDatePatternStr) && $consQueryDatePatternStr != "")
				{
					$modelObj->havingRaw($colMonthYear." IN (".$consQueryDatePatternStr.")");
				}
			}
			
			$modelObj->select($selectArr);
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}

	public function getAllFormulatedDueContentsForRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $repeatType, $minTs, $maxTs, $isFolder, $grpId = NULL, $consDateArr = array(), $consQueryDatePatternStr = NULL, $consDatePatternCol = NULL)
	{		
		$formulatedRepeatContentArr = array();
		$sortArrForDueContents = array();

        $contentsForRepeatDuration = $this->getAllDueContentsForRepeatDailyWeeklyMonthlyYearly($repeatType, $minTs, $maxTs, $isFolder, $grpId, $consQueryDatePatternStr);

        $repeatTypeDaily = 'DAILY';
        $repeatTypeWeekly = 'WEEKLY';
        $repeatTypeMonthly = 'MONTHLY';
        $repeatTypeYearly = 'YEARLY';

        $colWeekDay = "weekDay";
        $colMonthDay = "monthDay";
        $colMonthYear = "monthYear";

        $isFolderFlag = $isFolder ? 1 : 0;
        $folderOrGroupIdFieldName = $isFolder ? "folderId" : "groupId";

        $searchStr = '';
        $hasFilters = 0;
        $filShowAttachment = -1;
        $filAttachmentExtArr = array();

        if(isset($contentsForRepeatDuration))
        {
        	if($repeatType == $repeatTypeDaily)
        	{
	            foreach($contentsForRepeatDuration as $content) 
	            {
	                $contentObj = $contentListFormulationObj->formulateContentObject($this, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);

	                if(isset($contentObj)) 
	                {
		            	$folderGroupId = $content->{$folderOrGroupIdFieldName};
		            	$encFolderGroupId = sracEncryptNumberData($folderGroupId, $this->appuserSession);

			            foreach ($consDateArr as $consDateTs) 
			            {
			            	$contentObj = $contentListFormulationObj->formulateFromAndToTimestampForRepeatEntryOfContent($contentObj, $consDateTs);

                            $contentObj['isFolder'] = $isFolderFlag;
                            $contentObj['fOrGId'] = $encFolderGroupId;

		                    array_push($formulatedRepeatContentArr, $contentObj);
		                    array_push($sortArrForDueContents, $contentObj['startUtc']);
		                }
	                }
	            }
        	}
        	else
        	{
                foreach($contentsForRepeatDuration as $content) 
                {
                    $contentObj = $contentListFormulationObj->formulateContentObject($this, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                    
                    if(isset($contentObj))
                    {
		            	$folderGroupId = $content->{$folderOrGroupIdFieldName};
		            	$encFolderGroupId = sracEncryptNumberData($folderGroupId, $this->appuserSession);

                        $contentDatePatternVal = $content->{$consDatePatternCol};
                        $repeatRelevantDateArr = isset($consDateArr[$contentDatePatternVal]) ? $consDateArr[$contentDatePatternVal] : array();
                        foreach ($repeatRelevantDateArr as $consDateTs) 
                        {
			            	$contentObj = $contentListFormulationObj->formulateFromAndToTimestampForRepeatEntryOfContent($contentObj, $consDateTs);

                            $contentObj['isFolder'] = $isFolderFlag;
                            $contentObj['fOrGId'] = $encFolderGroupId;

                            array_push($formulatedRepeatContentArr, $contentObj);
                            array_push($sortArrForDueContents, $contentObj['startUtc']);
                        }
                    }                        
                }

        	}            
        }

        $response = array();
        $response['formulatedContentArr'] = $formulatedRepeatContentArr;
        $response['sortArrForContents'] = $sortArrForDueContents;

        return $response;
	}
	
	public function getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearlyForFolderOrGroup($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $isFolder, $consQueryDateArr, $consQueryDatePatternStrArr)
	{
        $allDueContents = array();
        $sortArrForDueContents = array();

        $consFolderOrGroupId = NULL;

        $repeatTypeDaily = 'DAILY';
        $repeatTypeWeekly = 'WEEKLY';
        $repeatTypeMonthly = 'MONTHLY';
        $repeatTypeYearly = 'YEARLY';

        $colDailyPatternField = NULL;
        $colWeeklyPatternField = "weekDay";
        $colMonthlyPatternField = "monthDay";
        $colYearlyPatternField = "monthYear";

        $consQueryDatePatternStrForDaily = $consQueryDatePatternStrArr[$repeatTypeDaily];
        $consQueryDatePatternStrForWeekly = $consQueryDatePatternStrArr[$repeatTypeWeekly];
        $consQueryDatePatternStrForMonthly = $consQueryDatePatternStrArr[$repeatTypeMonthly];
        $consQueryDatePatternStrForYearly = $consQueryDatePatternStrArr[$repeatTypeYearly];

        $dailyDateArr = $consQueryDateArr[$repeatTypeDaily];
        $weeklyDateArr = $consQueryDateArr[$repeatTypeWeekly];
        $monthlyDateArr = $consQueryDateArr[$repeatTypeMonthly];
        $yearlyDateArr = $consQueryDateArr[$repeatTypeYearly];

        /* for range starts */
        $folderGroupContentsForRange = $this->getAllFormulatedDueContentsForDateRange($contentListFormulationObj, $minTs, $maxTs, $isFolder);
        $allDueContents = array_merge($allDueContents , $folderGroupContentsForRange['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderGroupContentsForRange['sortArrForContents']);
        /* for range ends */

        /* for daily starts */
        $folderGroupContentsForRepeatDaily = $this->getAllFormulatedDueContentsForRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $repeatTypeDaily, $minTs, $maxTs, $isFolder, $consFolderOrGroupId, $dailyDateArr, $consQueryDatePatternStrForDaily, $colDailyPatternField);
        $allDueContents = array_merge($allDueContents , $folderGroupContentsForRepeatDaily['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderGroupContentsForRepeatDaily['sortArrForContents']);
        /* for daily ends */

        /* for weekly starts */
        $folderGroupContentsForRepeatWeekly = $this->getAllFormulatedDueContentsForRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $repeatTypeWeekly, $minTs, $maxTs, $isFolder, $consFolderOrGroupId, $weeklyDateArr, $consQueryDatePatternStrForWeekly, $colWeeklyPatternField);
        $allDueContents = array_merge($allDueContents , $folderGroupContentsForRepeatWeekly['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderGroupContentsForRepeatWeekly['sortArrForContents']);
        /* for weekly ends */

        /* for monthly starts */
        $folderGroupContentsForRepeatMonthly = $this->getAllFormulatedDueContentsForRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $repeatTypeMonthly, $minTs, $maxTs, $isFolder, $consFolderOrGroupId, $monthlyDateArr, $consQueryDatePatternStrForMonthly, $colMonthlyPatternField);
        $allDueContents = array_merge($allDueContents , $folderGroupContentsForRepeatMonthly['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderGroupContentsForRepeatMonthly['sortArrForContents']);
        /* for monthly ends */

        /* for yearly starts */
        $folderGroupContentsForRepeatYearly = $this->getAllFormulatedDueContentsForRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $repeatTypeYearly, $minTs, $maxTs, $isFolder, $consFolderOrGroupId, $yearlyDateArr, $consQueryDatePatternStrForYearly, $colYearlyPatternField);
        $allDueContents = array_merge($allDueContents , $folderGroupContentsForRepeatYearly['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderGroupContentsForRepeatYearly['sortArrForContents']);
        /* for yearly ends */

        $response = array();
        $response['formulatedContentArr'] = $allDueContents;
        $response['sortArrForContents'] = $sortArrForDueContents;

        return $response;
	}
	
	public function getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $consQueryDateArr, $consQueryDatePatternStrArr)
	{
        $allDueContents = array();
        $sortArrForDueContents = array();

        /* for folder starts */
        $isFolder = TRUE;
        $folderContents = $this->getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearlyForFolderOrGroup($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $isFolder, $consQueryDateArr, $consQueryDatePatternStrArr);
        $allDueContents = array_merge($allDueContents , $folderContents['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $folderContents['sortArrForContents']);
        /* for folder ends */
        
        /* for folder starts */
        $isFolder = FALSE;
        $groupContents = $this->getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearlyForFolderOrGroup($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $isFolder, $consQueryDateArr, $consQueryDatePatternStrArr);
        $allDueContents = array_merge($allDueContents , $groupContents['formulatedContentArr']);
        $sortArrForDueContents = array_merge($sortArrForDueContents, $groupContents['sortArrForContents']);
        /* for folder ends */

        $response = array();
        $response['formulatedContentArr'] = $allDueContents;
        $response['sortArrForContents'] = $sortArrForDueContents;

        return $response;
	}
	
	public function getAllAttachments($isFolder, $grpId = NULL)
	{
		$attachments = array();
		
		$modelObj = $this->getAllAttachmentModelObj($isFolder, $grpId);
		if(isset($modelObj))
		{
			$modelObj = $modelObj->select(['filesize', 'att_cloud_storage_type_id']);
			$modelObj = $modelObj->joinContentTable();

			if($isFolder)
			{
				$modelObj = $modelObj->forNonDeletedContent();
			}

			$attachments = $modelObj->get();
		}   	
		
		return $attachments;
	}	
	
	public function getAllAttachmentModelObj($isFolder, $grpId = NULL)
	{
		if($isFolder)	                    	
		{
			if($this->orgId > 0)
       		{
				$modelObj = New OrgEmployeeContentAttachment;
				$modelObj->setConnection($this->orgDbConName);
            	$modelObj = $modelObj->forEmployee($this->orgEmpId);       			
			}
			else
			{
   	 			$modelObj = AppuserContentAttachment::forUser($this->userId);				
			}
		}
		else
		{
			if($this->orgId > 0)
       		{
				$modelObj = New OrgGroupContentAttachment;
				$modelObj->setConnection($this->orgDbConName);       			
			}
			else
			{
   	 			$modelObj = New GroupContentAttachment;				
			}

			if(isset($grpId))
			{
				$modelObj = $modelObj->forGroup($grpId);	
			}	
		}	
		
		return $modelObj;
	}	
	
	public function getAvailableUserQuota($isFolder, $grpId = NULL)
	{
		$this->recalculateUserQuota($isFolder, $grpId);
		
		$availableKbs = 0;
		$allottedKbs = 0;	
		$usedKbs = 0;	
		if($isFolder)
		{
			$empOrUserConst = $this->getEmployeeOrUserConstantObject();
			if(isset($empOrUserConst))
			{
				$allottedKbs = $empOrUserConst->attachment_kb_allotted;
				$usedKbs = $empOrUserConst->attachment_kb_used;
			}
		}
		elseif(isset($grpId) && $grpId > 0)
		{
			$group = $this->getGroupObject($grpId);
			if(isset($group))
			{
				$allottedKbs = $group->allocated_space_kb;
				$usedKbs = $group->used_space_kb;
			}
		}
		$availableKbs = $allottedKbs - $usedKbs;		
		if($availableKbs < 0)
			$availableKbs = 0;
			
		return $availableKbs;
	}
	
	public function getUsedUserQuota($isFolder, $grpId = NULL)
	{
		$allottedKbs = 0;	
		$usedKbs = 0;	
		if($isFolder)
		{
			$empOrUserConst = $this->getEmployeeOrUserConstantObject();
			if(isset($empOrUserConst))
			{
				$allottedKbs = $empOrUserConst->attachment_kb_allotted;
				$usedKbs = $empOrUserConst->attachment_kb_used;
			}
		}
		elseif(isset($grpId) && $grpId > 0)
		{
			$group = $this->getGroupObject($grpId);
			if(isset($group))
			{
				$allottedKbs = $group->allocated_space_kb;
				$usedKbs = $group->used_space_kb;
			}
		}			
		return $usedKbs;
	}
	
	public function getAllocatedUserQuota($isFolder, $grpId = NULL)
	{
		$allottedKbs = 0;	
		$usedKbs = 0;	
		if($isFolder)
		{
			$empOrUserConst = $this->getEmployeeOrUserConstantObject();
			if(isset($empOrUserConst))
			{
				$allottedKbs = $empOrUserConst->attachment_kb_allotted;
				$usedKbs = $empOrUserConst->attachment_kb_used;
			}
		}
		elseif(isset($grpId) && $grpId > 0)
		{
			$group = $this->getGroupObject($grpId);
			if(isset($group))
			{
				$allottedKbs = $group->allocated_space_kb;
				$usedKbs = $group->used_space_kb;
			}
		}			
		return $allottedKbs;
	}
	
	public function recalculateUserQuota($isFolder, $grpId = NULL)
	{
		$usedKbs = 0;	
		$allRelAttachments = $this->getAllAttachments($isFolder, $grpId);
		
		foreach($allRelAttachments as $attObj)
		{
			$attSizeKb = $attObj->filesize;
			if($attObj->att_cloud_storage_type_id == 0)
			{
				$usedKbs += $attSizeKb;
			}
		}
		
		if($isFolder)
		{
			if($this->orgId == 0)
			{
				$allGroups = $this->getAllGroupsFoUser();
				
				foreach($allGroups as $grpObj)
				{
					$isUserAdmin = $grpObj->is_admin;
					$grpSpaceKb = $grpObj->allocated_space_kb;
					
					if($isUserAdmin == 1)
					{
						$usedKbs += $grpSpaceKb;
					}
				}
			}
		}
		
		if($usedKbs < 0)
			$usedKbs = 0;
		
		if($isFolder)
		{
			$empOrUserConst = $this->getEmployeeOrUserConstantObject();
			if(isset($empOrUserConst))
			{
				$allottedKbs = $empOrUserConst->attachment_kb_allotted;
				
				if($usedKbs > $allottedKbs)
					$usedKbs = $allottedKbs;
					
				$usedKbs = round($usedKbs);
				
				$availableKbs = $allottedKbs - $usedKbs;				
				if($availableKbs < 0)
					$availableKbs = 0;
				
				$empOrUserConst->attachment_kb_available = $availableKbs;
				$empOrUserConst->attachment_kb_used = $usedKbs;
				$empOrUserConst->save();
			}
		}
		elseif(isset($grpId) && $grpId > 0)
		{
			$group = $this->getGroupObject($grpId);
			if(isset($group))
			{
				$allottedKbs = $group->allocated_space_kb;
				
				if($usedKbs > $allottedKbs)
					$usedKbs = $allottedKbs;
					
				$usedKbs = round($usedKbs);
					
				$group->used_space_kb = $usedKbs;
				$group->save();
			}
		}
	}
	
	public function getContentTypeText($id)
	{
		$typeText = "";	
		$typeObj = $this->getContentTypeObject($id);
		if(isset($typeObj))
			$typeText = $typeObj->type_name;
		return $typeText;
	}
	
	public function getGroupName($id)
	{
		$groupName = "";
			
		$groupObj = $this->getGroupObject($id);
		if(isset($groupObj))
			$groupName = $groupObj->name;
		
		return $groupName;
	}
	
	public function appendSharedByText($contentText)
	{
		if($this->orgEmpId > 0)
		{
			$employee = $this->getEmployeeObject();
			if(isset($employee))
			{
				$sharedByName = $employee->employee_name;
				$sharedByEmail = $employee->email;
			}
		}
		else
		{
			$user = $this->getUserObject();
			if(isset($user))
			{
				$sharedByName = $user->fullname;
				$sharedByEmail = $user->email;
			}
		}

		$sharedByText = "";
		if(isset($sharedByName) && $sharedByName != "")
	    	$sharedByText .= 'From - '.$sharedByName;
	    
		if(isset($sharedByEmail) && $sharedByEmail != "")
	    	$sharedByText .= ' ('.$sharedByEmail.')';
		
		$sharedContent = "";
		if($sharedByText != "")
			$sharedContent = $sharedByText."<br/>";
			
		$sharedContent .= $contentText;
		
		return $sharedContent;
	}
	
	public function getEmployeeOrUserObjectById($consId)
	{
		$employeeOrUserObject = NULL;

		if($this->orgEmpId > 0)
		{
        	$modelObj = New OrgEmployee;
			$modelObj->setConnection($this->orgDbConName);
        	$employee = $modelObj->byId($consId)->first();
			if(isset($employee))
			{
				$employeeOrUserObject = $employee;
			}
		}
		else
		{
			$user = Appuser::byId($consId)->first();
			if(isset($user))
			{
				$employeeOrUserObject = $user;
			}
		}
		
		return $employeeOrUserObject;
	}
	
	public function getEmployeeOrUserObjectByEmail($consEmail)
	{
		$employeeOrUserObject = NULL;

		if($this->orgEmpId > 0)
		{
        	$modelObj = New OrgEmployee;
			$modelObj->setConnection($this->orgDbConName);
        	$employee = $modelObj->ofEmail($consEmail)->active()->first();
			if(isset($employee))
			{
				$employeeOrUserObject = $employee;
			}
		}
		else
		{
			$user = Appuser::ofEmail($consEmail)->active()->first();
			if(isset($user))
			{
				$employeeOrUserObject = $user;
			}
		}
		
		return $employeeOrUserObject;
	}
	
	public function getEmployeeOrUserNameByEmail($consEmail)
	{
		$employeeOrUserName = '';

		$employeeOrUserObject = $this->getEmployeeOrUserObjectByEmail($consEmail);
		if(isset($employeeOrUserObject))
		{
			if($this->orgEmpId > 0)
			{
				$employeeOrUserName = $employeeOrUserObject->employee_name;
			}
			else
			{
				$employeeOrUserName = $employeeOrUserObject->fullname;
			}			
		}
		
		return $employeeOrUserName;
	}
	
	public function getOrganizationCodeForFcm()
	{
		$orgCode = "";
		$organization = $this->getOrganizationObject();
		if(isset($organization))
			$orgCode = $organization->org_code." - ";	
		return $orgCode;
	}
	
	public function getOrganizationIsFileSaveShareEnabled()
	{
		$isFileSaveShareEnabled = 1;
		$organization = $this->getOrganizationObject();
		if(isset($organization))
		{
			$isFileSaveShareEnabled = $organization->is_file_save_share_enabled;
			
			if($this->orgEmpId > 0)
			{
				$isFileSaveShareEnabled = OrganizationClass::getOrganizationEmployeeHasFileSaveShareEnabled($this->orgId, $this->orgEmpId);
			}
		}	
		return $isFileSaveShareEnabled;
	}
	
	public function getOrganizationIsScreenShareEnabled()
	{
		$isScreenShareEnabled = 1;
		$organization = $this->getOrganizationObject();
		if(isset($organization))
		{
			$isScreenShareEnabled = $organization->is_screen_share_enabled;
			
			if($this->orgEmpId > 0)
			{
				$isScreenShareEnabled = OrganizationClass::getOrganizationEmployeeHasScreenShareEnabled($this->orgId, $this->orgEmpId);
			}
		}	
		return $isScreenShareEnabled;
	}
	
	public function orgEmployeeLeave()
	{
		$employee = $this->getEmployeeObject();
		            
        if(isset($employee))
        {        
			$employee->is_active = 0;
            $employee->is_verified = 0;
            $employee->is_self_registered = 0;
            $employee->updated_by = $this->userId;
            $employee->save();		        		
				
			$organizationUser = OrganizationUser::byEmpId($this->orgEmpId)->ofOrganization($this->orgId)->first();
			if(isset($organizationUser))
			{
				$verificationCode = CommonFunctionClass::generateVerificationCode();
				$encVerificationCode = Crypt::encrypt($verificationCode);
				
				$organizationUser->appuser_email = "";
				$organizationUser->is_verified = 0;
				$organizationUser->verification_code = $encVerificationCode;
				$organizationUser->save();
			}	
			
            $forceDelete = TRUE;	
			$this->sendOrgEmployeeRemovedToDevice($this->orgEmpId, $forceDelete, $this->currLoginToken, $this->orgId);
			MailClass::sendOrgEmployeeLeftMail($this->orgId, $this->orgEmpId);
		}
	}
	
	public function getAllUserOrganizationProfiles()
	{
		$user = $this->getUserObject();
		   
		$userOrganizations = array();        
        if(isset($user))
        {       
			$userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get(); 
		}
		return $userOrganizations;
	}
	
	public function getAllRemindBeforeOptions()
	{       
       $option1 = array( "id" => 0, "text" => "On Time" );
       $option2 = array( "id" => 900000, "text" => "Before 15 Mins" );
       $option3 = array( "id" => 1800000, "text" => "Before 30 Mins" );
       $option4 = array( "id" => 3600000, "text" => "Before 1 Hour" );
       $option5 = array( "id" => 7200000, "text" => "Before 2 Hours" );
       $option6 = array( "id" => 21600000, "text" => "Before 6 Hours" );
       $option7 = array( "id" => 43200000, "text" => "Before 12 Hours" );
       
       $options = [ $option1, $option2, $option3, $option4, $option5, $option6, $option7 ] ;
       
       return $options;
	}
	
	public function getAllRepeatDurationOptions()
	{
       $option1 = array( "id" => "", "text" => "Don't Repeat" );
       $option2 = array( "id" => "HOURLY", "text" => "Hourly" );
       $option3 = array( "id" => "DAILY", "text" => "Daily" );
       $option4 = array( "id" => "WEEKLY", "text" => "Weekly" );
       $option5 = array( "id" => "MONTHLY", "text" => "Monthly" );
       $option6 = array( "id" => "YEARLY", "text" => "Yearly" );
       
       $options = [ $option1, $option2, $option3, $option4, $option5, $option6 ] ;
       
       return $options;
	}
	
	public function getRemindBeforeText($id)
	{
		$options = $this->getAllRemindBeforeOptions();
		$text = "";
		if(isset($id))
		{
			foreach($options as $option)
			{
				if($option['id'] == $id)
				{
					$text = $option['text'];
					break;
				}
			}
		}
		else
		{
			$text = $options[0]['text'];
		}	
		return $text;
	}
	
	public function getRepeatDurationText($id)
	{		
		$options = $this->getAllRepeatDurationOptions();
		$text = "";
		if(isset($id))
		{
			foreach($options as $option)
			{
				if($option['id'] == $id)
				{
					$text = $option['text'];
					break;
				}
			}
		}
		else
		{
			$text = $options[0]['text'];
		}	
		return $text;
	}
	
	public function getFolderLockStatus()
	{
        $userConstants = $this->getEmployeeOrUserConstantObject();
		$isLocked = 0;
        if(isset($userConstants))
        {
            $isLocked = $userConstants->folder_passcode_enabled;
        }
        return $isLocked;
	}
	
	public function getLockedFolderIdArr()
	{
        $userConstants = $this->getEmployeeOrUserConstantObject();
		$lockedFolderArr = array();
        if(isset($userConstants))
        {
            $hasFolderPasscode = $userConstants->folder_passcode_enabled;
            $folderIdStr = $userConstants->folder_id_str;
            $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
            if($hasFolderPasscode == 1 && $folderIdStr != null ) 
            {
                $lockedFolderArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
			}
        }
        return $lockedFolderArr;
	}
	
	public function getAllTemplates()
	{
		$templates = NULL;
			
		if($this->orgId > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgTemplate;
				$modelObj->setConnection($this->orgDbConName); 					
	            $templates = $modelObj->get();
			}
		}
		
		return $templates;
	}
	
	public function getTemplateObject($id)
	{
		$template = NULL;
			
		if($id > 0 && $this->orgId > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgTemplate;
				$modelObj->setConnection($this->orgDbConName); 					
	            $template = $modelObj->byId($id)->first();	 
			}
		}
		
		return $template;
	}
	
	public function sanitizeContentTextForDisplay($contentText) 
	{
		
	}
	
	public function getStrippedContentText($contentText) 
	{
		$strippedContentText = CommonFunctionClass::getStrippedContentText($contentText);
		return $strippedContentText;
	}

    public function trimAdditionalBrTagsFromString($str)
    {
		$strippedStr = preg_replace('/^(<br\s*\/?>)*|(<br\s*\/?>)*$/i', '', $str);
		return $strippedStr;
    }
	
   	public function getConversationArrayFromSharedContentText($contentText) {
        
   		$conversationArr = NULL;
   		$conversationParts = NULL;

        $sanStr = $contentText;

        $contentIsConversation = $this->checkIfContentIsSharedFromContentText($contentText);
		// Log::info('getConversationArrayFromSharedContentText : $contentText : '.$contentText.' : contentIsConversation : '.$contentIsConversation);
        //if($contentIsConversation == 1)
        {
        	$separatorStr = Config::get('app_config.conversation_part_separator');
	        
	        $conversationParts = explode($separatorStr, $sanStr);
			//Log::info('getConversationArrayFromSharedContentText : $conversationParts : ');
			//Log::info($conversationParts);
	        
	        $conversationArr = array();
	        
	        if(count($conversationParts) > 0) {
	        	foreach($conversationParts as $conversationItem) 
	        	{
	        		$convDetails = $this->formulateContentMetaDataFromContentPart($conversationItem);
	        		if(isset($convDetails))
	        		{
						array_push($conversationArr, $convDetails);       			
	        		}
				}
			}
        }
		// Log::info('getConversationArrayFromSharedContentText : $conversationArr : ');
		// Log::info($conversationArr);	        
		
		$respDetails = array();
		$respDetails['conversationParts'] = $conversationParts;
		$respDetails['conversation'] = $conversationArr;
		$respDetails['sanStr'] = $sanStr;
		   
        return $respDetails;
    }
	
   	public function removePrefixFromSharedContentText($contentText) {
        $sanStr = $contentText;
        $senderStr = '';
        
        $isSharedContent = $this->checkIfContentIsSharedFromContentText($contentText);
        if($isSharedContent == 1) {
			$startIndex = strpos($contentText, "]");
			if($startIndex < 0) {
				$startIndex = strpos($contentText, ")");
			}	        
	        if($startIndex >= 0) {
	    		$afIndex = $startIndex + 1;
	            $sanStr = substr($contentText, $afIndex);
	            
	            $senderStr = $this->getSenderDetailsLineFromContentText($contentText);
	        }
		}
		
		$respDetails = array();
		$respDetails['content'] = $sanStr;
		$respDetails['sender'] = $senderStr;
		   
        return $respDetails;
    }
	
	public function checkIfContentIsSharedFromContentText($contentText)
	{
		$isSharedContent = 0;
		
        if(strlen($contentText) > 23)
        {        	
            $startIndex = 0;
            $strLength = 23;
    		$retrievedSeparatorStr = substr($contentText, $startIndex, $strLength);
            
            $separatorStr = Config::get('app_config.conversation_part_separator');   
			$appendedStr = '<br>'.$separatorStr.'<br>';

			// Log::info('retrievedSeparatorStr : '.$retrievedSeparatorStr);
			// Log::info('separatorStr : '.$separatorStr);
			// Log::info('strpos($retrievedSeparatorStr, $separatorStr) !== false : ');
			// Log::info((strpos($retrievedSeparatorStr, $separatorStr) !== false));

            // if($retrievedSeparatorStr == $appendedStr)
            if(strpos($retrievedSeparatorStr, $separatorStr) !== false)
            {
                $isSharedContent = 1;
            }
        }
        
		return $isSharedContent;
	}
	
	public function getSenderDetailsLineFromContentText($contentText)
    {
    	$startIndex = strpos($contentText, ">");
    	$afIndex = $startIndex + 1;
        $endIndex = strpos($contentText, ")");
    	$afIndex2 = $endIndex + 1;
    	$senderStrLength = $afIndex2 - $afIndex;
    	$senderDetailsStr = substr($contentText, $afIndex, $senderStrLength);
    	return $senderDetailsStr;        
    }
	
	public function getSentAtDetailsLineFromContentText($contentText)
    {
    	$startIndex = strpos($contentText, "[");
    	$afIndex = $startIndex + 1;
        $endIndex = strpos($contentText, "]");
    	$afIndex2 = $endIndex;// + 1;
    	$senderStrLength = $afIndex2 - $afIndex;
    	$sentAtDetailsStr = substr($contentText, $afIndex, $senderStrLength);

    	if($sentAtDetailsStr != "" && is_numeric($sentAtDetailsStr))
    	{
    		$sentAtDetailsStr = $sentAtDetailsStr * 1;
    	}
    	else
    	{
    		$sentAtDetailsStr = "";
    	}

    	return $sentAtDetailsStr;        
    }

    public function formulateContentMetaDataFromContentPart($contentText)
    {
    	$convDetails = NULL;

    	$empOrUserEmail = $this->getEmployeeOrUserEmail();

    	$separatorStr = Config::get('app_config.conversation_part_separator');
        $replySeparatorStr = Config::get('app_config.conversation_reply_separator');   
        $editSeparatorStr = Config::get('app_config.conversation_part_is_edited_indicator');
        $isDeletedIndicatorStr = Config::get('app_config.conversation_part_is_deleted_indicator');
        $isForwardedIndicatorStr = Config::get('app_config.content_is_forwarded_indicator_only');

        $sanConversationStr = "";
        $sanConversationStrStripped = "";
        $contentTsStr = "";
        $contentSenderStr = "";
        $contentStr = "";

        $editOrDeleteBaseStr = "";
        $editOrDeleteBaseStrStripped = "";
        $editOrDeleteTsStr = "";
        $editedOrDeletedByStr = "";

        $isEdited = false;
        $isDeleted = false;
        $isForwarded = false;

        $hasReply = false;
        $baseReplyStr = "";
        $replyTsStr = "";
        $replySenderStr = "";
        $replyStr = "";
        $replyStrStripped = "";

        // Log::info('contentText: '.$contentText);

        if(isset($contentText) && $contentText != "")
        {
       		$conversationItem = $contentText;
        	// $conversationItem = $separatorStr.$conversationItem;
			$hasTs = TRUE;
			$startIndex = strpos($conversationItem, "]");
	        if($startIndex < 0)
			{
				$hasTs = FALSE;
				$startIndex = strpos($conversationItem, ")");
			}	
        	
        	// Log::info('hasTs: '.json_encode($hasTs).' : startIndex : '.$startIndex);

	        if($startIndex >= 0)
            {
	    		$afIndex = $startIndex + 1;
	            $sanConversationStr = substr($conversationItem, $afIndex);
	            
	            $contentSenderStr = $this->getSenderDetailsLineFromContentText($conversationItem);
	            $contentTsStr = $this->getSentAtDetailsLineFromContentText($conversationItem);

	            // Log::info('sanConversationStr : '.$sanConversationStr.' : contentSenderStr : '.$contentSenderStr.' : contentTsStr : '.$contentTsStr);
	            
	            if($sanConversationStr != "" && $contentSenderStr != "") 
	            {
        			$contentSenderStr = trim($contentSenderStr);

        			$first3SenderChar = substr($contentSenderStr, 0, 3);
        			$first6SenderChar = substr($contentSenderStr, 0, 6);
        			if($first3SenderChar == "-->")
        			{
        				$contentSenderStr = substr($contentSenderStr, 3);
        			}
        			else if($first6SenderChar == '--&gt;')
        			{
        				$contentSenderStr = substr($contentSenderStr, 6);
        			}
        			else if(!checkIfStringContainsEmail($contentSenderStr))
        			{
        				$contentSenderStr = '';
        			}
				}

				if(strlen($sanConversationStr) > 23)
				{
					$sanConversationStr = htmlspecialchars_decode($sanConversationStr, ENT_NOQUOTES);

		            $flagPatternStartIndex = 0;
		            $flagPatternStrLength = 23;
		    		$flagPatternStr = substr($sanConversationStr, $flagPatternStartIndex, $flagPatternStrLength);
	            	// Log::info('sanConversationStr : '.$sanConversationStr);
	            	// Log::info('flagPatternStr : '.$flagPatternStr);
	            	// Log::info('isDeletedIndicatorStr : '.$isDeletedIndicatorStr);

		    		if(strpos($flagPatternStr, $isDeletedIndicatorStr) !== false)
            		{
            			$isDeleted = TRUE;

            			$actionDetails = $this->getChatActionDetailsFromContentText('DLT', $sanConversationStr);

            			$sanConversationStr = $actionDetails['content'];
            			$editedOrDeletedByStr = $actionDetails['sender'];
            			$editOrDeleteTsStr = $actionDetails['sentAt'];	            
            		}
            		// else if(strpos($flagPatternStr, $editSeparatorStr) !== false)
            		// {
            		// 	$isEdited = TRUE;

            		// 	$actionDetails = $this->getChatActionDetailsFromContentText('EDT', $sanConversationStr);

            		// 	$sanConversationStr = $actionDetails['content'];
            		// 	$editedOrDeletedByStr = $actionDetails['sender'];
            		// 	$editOrDeleteTsStr = $actionDetails['sentAt'];	
            		// }
            		else if(strpos($flagPatternStr, $isForwardedIndicatorStr) !== false)
            		{
            			$isForwarded = TRUE;

            			$actionDetails = $this->getChatActionDetailsFromContentText('FWD', $sanConversationStr);

            			$sanConversationStr = $actionDetails['content'];
            			$editedOrDeletedByStr = $actionDetails['sender'];
            			$editOrDeleteTsStr = $actionDetails['sentAt'];	
            		}

					if(strpos($sanConversationStr, $replySeparatorStr) !== false)
					{
	        			$convPartsForReply = explode($replySeparatorStr, $sanConversationStr);
						if(count($convPartsForReply) > 1)
						{
            				$hasReply = TRUE;

            				$sanConversationStr = $convPartsForReply[0];
            				$baseReplyStr = $convPartsForReply[1];

	            			$actionDetails = $this->getChatActionDetailsFromContentText('RPL', $baseReplyStr);

	            			$replyStr = $actionDetails['content'];
	            			$replySenderStr = $actionDetails['sender'];
	            			$replyTsStr = $actionDetails['sentAt'];	
						}
					}

					if(strpos($sanConversationStr, $editSeparatorStr) !== false)
					{
	        			$convPartsForEdit = explode($editSeparatorStr, $sanConversationStr);
						if(count($convPartsForEdit) > 1)
						{
            				$isEdited = TRUE;

            				$sanConversationStr = $convPartsForEdit[0];
            				$baseEditStr = $convPartsForEdit[1];

	            			$actionDetails = $this->getChatActionDetailsFromContentText('EDT', $baseEditStr);

	            			$editOrDeleteBaseStr = $actionDetails['content'];
	            			$editedOrDeletedByStr = $actionDetails['sender'];
	            			$editOrDeleteTsStr = $actionDetails['sentAt'];	
						}
					}

					
	            	// Log::info('flagPatternStr : '.$flagPatternStr.' : isDeletedIndicatorStr : '.$isDeletedIndicatorStr.' : isDeleted : '.$isDeleted);
	            	// Log::info('flagPatternStr : '.$flagPatternStr.' : editSeparatorStr : '.$editSeparatorStr.' : isEdited : '.$isEdited);
	            	// Log::info('flagPatternStr : '.$flagPatternStr.' : isForwardedIndicatorStr : '.$isForwardedIndicatorStr.' : isForwarded : '.$isForwarded);
	            	// Log::info('replySeparatorStr : '.$replySeparatorStr.' : hasReply : '.$hasReply);
	            	
				}

				if($sanConversationStr != "")
				{
					$sanConversationStr = $this->trimAdditionalBrTagsFromString($sanConversationStr);

					$sanConversationStrStripped = $this->getStrippedContentText($sanConversationStr);
				}

				if($editOrDeleteBaseStr != "")
				{
					$editOrDeleteBaseStrStripped = $this->getStrippedContentText($editOrDeleteBaseStr);
				}

				if($replyStr != "")
				{
					$replyStrStripped = $this->getStrippedContentText($replyStr);
				}

				if($contentTsStr != "")
				{	       
					$isUserMsgSender = 0;
					if (strpos($contentSenderStr, $empOrUserEmail) !== false) {
						$isUserMsgSender = 1;
					}

					$contentSenderStrBase = $contentSenderStr;
					$editedOrDeletedByStrBase = $editedOrDeletedByStr;
					$replySenderStrBase = $replySenderStr;

					if($this->orgId > 0)
					{
        				$contentSenderStr = $this->formulateContentSenderNameFromSenderClubbedStr($contentSenderStr);

        				$editedOrDeletedByStr = $this->formulateContentSenderNameFromSenderClubbedStr($editedOrDeletedByStr);

        				$replySenderStr = $this->formulateContentSenderNameFromSenderClubbedStr($replySenderStr);
					}

					// if($sanConversationStr !== "")
					{
						$convDetails = array();
						$convDetails['content'] = $sanConversationStr;
						$convDetails['contentStripped'] = $sanConversationStrStripped;
						$convDetails['sender'] = $contentSenderStr;
						$convDetails['senderBase'] = $contentSenderStrBase;
						$convDetails['sentAt'] = $contentTsStr;
						$convDetails['isUserMsgSender'] = $isUserMsgSender;

						$convDetails['isDeleted'] = $isDeleted;
						$convDetails['isEdited'] = $isEdited;
						$convDetails['isForwarded'] = $isForwarded;

						$convDetails['editOrDeleteStr'] = $editOrDeleteBaseStr;
						$convDetails['editOrDeleteStrStripped'] = $editOrDeleteBaseStrStripped;
						$convDetails['editedOrDeletedAt'] = $editOrDeleteTsStr;
						$convDetails['editedOrDeletedBy'] = $editedOrDeletedByStr;
						$convDetails['editedOrDeletedByBase'] = $editedOrDeletedByStrBase;

						$convDetails['hasReply'] = $hasReply;
						$convDetails['repliedAt'] = $replyTsStr;
						$convDetails['replySender'] = $replySenderStr;
						$convDetails['replySenderBase'] = $replySenderStrBase;
						$convDetails['replyContent'] = $replyStr;
						$convDetails['replyContentStripped'] = $replyStrStripped;
					}
				}
	            
	            // Log::info('convDetails : ');
	            // Log::info($convDetails);

	        }
        }

		return $convDetails;
    }
	
	public function getChatActionDetailsFromContentText($code, $contentText)
    {
    	$consIndicatorStr = "";
    	if($code === 'DLT')
    	{
    		$consIndicatorStr = Config::get('app_config.conversation_part_is_deleted_indicator');
    	}
    	else if($code === 'EDT')
    	{
    		$consIndicatorStr = Config::get('app_config.conversation_part_is_edited_indicator');
    	}
    	else if($code === 'FWD')
    	{
    		$consIndicatorStr = Config::get('app_config.content_is_forwarded_indicator_only');
    	}
    	$consIndicatorAppendedStr = '<br>'.$consIndicatorStr.'<br>';
    	$contentText = str_replace($consIndicatorAppendedStr, "", $contentText);
    	$contentText = str_replace($consIndicatorStr, "", $contentText);
    	//$contentText = str_replace("-->", "", $contentText);

        $actionByStr = $this->getSenderDetailsLineFromContentText($contentText);
        $actionTsStr = $this->getSentAtDetailsLineFromContentText($contentText);
        // Log::info('getChatActionDetailsFromContentText : code : '.$code.' : contentText : '.$contentText);

		
		$actionStrStartIndex = strpos($contentText, "]");
        if($actionStrStartIndex === false)
		{
			$actionStrStartIndex = strpos($contentText, ")");
		}

        if($actionStrStartIndex !== false) 
        {
    		$afActionStrIndex = $actionStrStartIndex + 1;
            $contentText = substr($contentText, $afActionStrIndex);
        }

    	$actionByStr = str_replace("-->", "", $actionByStr);
    	$actionByStr = str_replace("<br>", "", $actionByStr);

		$convActionDetails = array();
		$convActionDetails['content'] = $contentText;
		$convActionDetails['sender'] = $actionByStr;
		$convActionDetails['sentAt'] = $actionTsStr;

        // Log::info('getChatActionDetailsFromContentText : convActionDetails : ');
        // Log::info($convActionDetails);
     
     	return $convActionDetails;
    }

    public function formulateContentSenderNameFromSenderClubbedStr($senderStr)
    {
    	$senderName = "";

    	if(isset($senderStr) && $senderStr != "")
    	{ 
    		$nameStartIndex = 0;
    		$nameEndIndex = strpos($senderStr, "(");
    		$senderName = substr($senderStr, $nameStartIndex, $nameEndIndex);
    	}

    	return $senderName;
    }

    public function formulateContentSenderEmailFromSenderClubbedStr($senderStr)
    {
    	$senderEmail = "";
    	
    	if(isset($senderStr) && $senderStr != "")
    	{ 
    		$emailStartIndex = strpos($senderStr, "(");
    		$emailEndIndex = strpos($senderStr, "(");
    		$senderEmail = substr($senderStr, $emailStartIndex + 1, $emailEndIndex);
    	}

    	return $senderEmail;
    }
    
    public function userHasContentModificationRight($isFolder, $content)
    {
    	$hasModifyRights = FALSE;

        $contentGroupId = 0;
        $groupMember = NULL;
        if(!$isFolder)
        {
            $contentGroupId = $content->group_id;
            
            $group = $this->getGroupObject($contentGroupId);
            $groupMember = $this->getGroupMemberDetailsObject($contentGroupId, FALSE);

            if(isset($group) && isset($groupMember))
            {
                if(($this->orgId == 0 || $groupMember->has_post_right == 1)) // $group->is_two_way == 1 && 
                {
                    $hasModifyRights = TRUE;
                }
            }
        }
        else
        {
            $hasModifyRights = TRUE;
        }

        return $hasModifyRights;
    }
    
    public function modifyUserOrGroupContent($isFolder, $id, $contentText, $updateTs)
    {		
        $contentDetails = array();
        $contentDetails['content'] = Crypt::encrypt($contentText);
        $contentDetails['update_timestamp'] = $updateTs;
		
		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;	 

			if($isFolder)
			{
				$modelObj = New OrgEmployeeContent;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$contentTableName = $modelObj->table;
			}  
			else
			{
				$modelObj = New OrgGroupContent;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$contentTableName = $modelObj->table;	
			}   			
   			       			
		}
		else
		{
			if($isFolder)
			{
				$modelObj = New AppuserContent;
				$contentTableName = $modelObj->table;
			}
			else
			{
				$modelObj = New GroupContent;
				$contentTableName = $modelObj->table;
			}
		}
		
		$retSyncId = 0;
        $userContent = $modelObj->byId($id)->first();
		if(isset($userContent))
		{
			$retSyncId = $id;
			//if(isset($userContent->is_locked) && $userContent->is_locked == 0)
	        {
   				$contentDetails['created_at'] = $userContent->created_at;
	   			$contentDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
	   			
	        	$userContent->update($contentDetails);
				$syncId = $id;
			}

    		$folderOrGroupId = 0;
        	if($isFolder)
    		{
    			$folderOrGroupId = $userContent->folder_id;
    		}
    		else
    		{
    			$folderOrGroupId = $userContent->group_id;
    		}

        	$this->recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId);

			$this->checkAndSetupCalendarContentForLinkedCloudCalendar($this->cloudCalendarSyncOperationTypeModification, $isFolder, $folderOrGroupId, $id, NULL);
		}
		
		return $retSyncId;
	}

	public function sendRespectiveContentModificationPush($isFolder, $contentId, $isAdd, $sharedByEmail, $forceSendToAllDevice = false, $sendSilentPushOnly = false, $opCode = NULL)
	{
		// Log::info('sendRespectiveContentModificationPush : contentId : '.$contentId.' : isFolder : '.json_encode($isFolder).' : opCode : '.$opCode);

		$content = $this->getContentObject($contentId, $isFolder);

		$forRestoreSilentPush = 0;
		if($sendSilentPushOnly)
		{
			$forRestoreSilentPush = 1;
		}

		if(isset($content))
		{
			$consUserCurrLoginToken = $this->currLoginToken;
			if($forceSendToAllDevice)
			{
				$consUserCurrLoginToken = null;
			}

			$isPushForTagModification = FALSE;
			$opCodeForTagChange = Config::get("app_config.content_push_notif_op_code_tag_change");
			$validOpCodeArr = Config::get("app_config.validContentPushNotifOpCodeArr");
			$consOpCode = NULL;
			if(isset($opCode) && trim($opCode) != "" && in_array($opCode, $validOpCodeArr))
			{
				$consOpCode = $opCode;
				if($opCode == $opCodeForTagChange)
				{
					$isPushForTagModification = TRUE;
				}
			}

			if($isFolder)
	        {
	        	if($content->is_removed == 2)
	        	{
		            if($this->orgId > 0)
		            {
						// Log::info('sendRespectiveContentModificationPush : Self sendOrgContentDeletedMessageToDevice ');
		                $this->sendOrgContentDeletedMessageToDevice($this->orgId, $this->orgEmpId, $consUserCurrLoginToken, $isFolder, $contentId);    
		            }
		            else
		            {
						// Log::info('sendRespectiveContentModificationPush : Self sendContentDeletedMessageToDevice ');
		                $this->sendContentDeletedMessageToDevice($this->userId, $consUserCurrLoginToken, $isFolder, $contentId);
		            }
	        	}
				else
				{
					$notifText = NULL;
		            if($this->orgId > 0)
		            {           
						// Log::info('sendRespectiveContentModificationPush : Self sendContentAddMessageToDevice ');                
		                $this->sendOrgContentAddMessageToDevice($this->orgEmpId, $this->orgId, $consUserCurrLoginToken, $isFolder, $contentId);    
		            }
		            else
		            {   
						// Log::info('sendRespectiveContentModificationPush : Self sendContentAddMessageToDevice ');
		                $this->sendContentAddMessageToDevice($this->userId, $consUserCurrLoginToken, $isFolder, $contentId, $notifText); 
		            }

		            $respSharedContentId = $content->shared_content_id;

		            if(isset($respSharedContentId) && $respSharedContentId > 0)
		            {
		            	if(!$isPushForTagModification)
		            	{
		            		$respSharedContent = $this->getContentObject($respSharedContentId, $isFolder);
			            	if(isset($respSharedContent))
			            	{
			            		$recCurrLoginToken = NULL;
					            if($this->orgId > 0)
					            {  
					            	$recOrgEmpId = $respSharedContent->employee_id;
									// Log::info('sendRespectiveContentModificationPush : Shared sendOrgContentAddMessageToDevice : recOrgEmpId : '.$recOrgEmpId);
									$this->sendOrgContentAddMessageToDevice($recOrgEmpId, $this->orgId, $recCurrLoginToken, $isFolder, $respSharedContentId, $consOpCode);    
					            }
					            else
					            {
					            	$recUserId = $respSharedContent->appuser_id;
									// Log::info('sendRespectiveContentModificationPush : Shared sendContentAddMessageToDevice : recUserId : '.$recUserId);
									$this->sendContentAddMessageToDevice($recUserId, $recCurrLoginToken, $isFolder, $respSharedContentId, $notifText, $consOpCode); 
					            }
			            	}
		            	}
		            }
				}	
	        }
	        else
	        {
	            $contentGroupId = $content->group_id;
	            $allGroupMembers = $this->getGroupMembers($contentGroupId);

	            if($this->orgId > 0)
	            {
	                foreach($allGroupMembers as $member) 
	                {
	                    $memberEmpId = $member->employee_id;
	                    if($memberEmpId != $this->orgEmpId)
	                    {   
	                        $empDepMgmtObj = New ContentDependencyManagementClass;                              
	                        $empDepMgmtObj->withOrgIdAndEmpId($this->orgId, $memberEmpId); 
	                        $orgEmployee = $empDepMgmtObj->getPlainEmployeeObject();
	                        
	                        if(isset($orgEmployee) && $orgEmployee->is_active == 1)
	                        {
								// Log::info('sendRespectiveContentModificationPush : Member Shared sendOrgGroupEntryAddMessageToDevice : memberEmpId : '.$memberEmpId);
	                            $this->sendOrgGroupEntryAddMessageToDevice($this->orgId, $memberEmpId, $contentId, $isAdd, $sharedByEmail, $this->orgEmpId, $forRestoreSilentPush, $consOpCode);
	                        }
	                    }
	                    else
	                    {
							// Log::info('sendRespectiveContentModificationPush : Member Self sendOrgContentAddMessageToDevice : memberEmpId : '.$memberEmpId);
	                        $this->sendOrgContentAddMessageToDevice($memberEmpId, $this->orgId, $consUserCurrLoginToken, $isFolder, $contentId);
	                    }
	                }
	            } 
	            else
	            {
	                foreach($allGroupMembers as $member) 
	                {
	                    $memberUser = $member->memberAppuser;
	                    if(isset($memberUser))
	                    {
	                        $memberUserId = $memberUser->appuser_id;
	                
	                        if($memberUserId != $this->userId)
	                        { 
								// Log::info('sendRespectiveContentModificationPush : Member Shared sendGroupEntryAddMessageToDevice : memberUserId : '.$memberUserId);
	                            $this->sendGroupEntryAddMessageToDevice($memberUserId, $contentId, $isAdd, $sharedByEmail, $forRestoreSilentPush, $consOpCode);
	                        }
	                        else
	                        {
								// Log::info('sendRespectiveContentModificationPush : Member Self sendContentAddMessageToDevice : memberUserId : '.$memberUserId);
	                            $this->sendContentAddMessageToDevice($memberUserId, $consUserCurrLoginToken, $isFolder, $contentId);
	                        }
	                    }                               
	                } 
	            } 
	        }
		}			
	}
	
	public function getAllContentsForRestore($isFolder, $folderOrGroupId = NULL)
	{
		$contents = NULL;
		$modelObj = NULL;	
		if(isset($this->orgDbConName))
        {
        	if($isFolder)	                    	
			{		
				$modelObj = New OrgEmployeeContent;
				$modelObj->setConnection($this->orgDbConName);
        		$modelObj = $modelObj->ofEmployee($this->orgEmpId);
        		
        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        			$modelObj = $modelObj->ofFolder($folderOrGroupId);
                
                $modelObj = $modelObj->removedConsiderationForRestore();
			}
		}
		else
		{
        	if($isFolder)	                    	
			{
   	 			$modelObj = AppuserContent::ofUser($this->userId);
        		
        		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
        			$modelObj = $modelObj->ofFolder($folderOrGroupId); 
        		
                $modelObj = $modelObj->removedConsiderationForRestore();
			}
		}		
		if(isset($modelObj))
		{
			$contentTableName = $this->getContentTablename($isFolder);
			
			$idColName = "";
			if($isFolder)
            {
                if($this->orgId > 0)
                {
                	$idColName = 'employee_content_id';
				}
				else
				{
                	$idColName = 'appuser_content_id';
				}						
			}
			else
			{
               $idColName = 'group_content_id';
			}
			
			$selectArr = [ "*", "$contentTableName.$idColName as content_id" ];
			
			if($isFolder)
			{
				array_push($selectArr, "$contentTableName.folder_id as folderId");
			}
			
			$modelObj->select($selectArr);
			$modelObj->orderBy('create_timestamp', 'asc');
	        
	        $contents = $modelObj->get();
		}	
		return $contents;
	}
	
	public function getContentMetricsForRestore()
	{
        $restoreContentCount = 0;
        $restoreContentAttachmentCount = 0;
        $restoreContentSizeKb = 0;
        $restoreContentSizeStr = '';
        $oldestContentDate = '';
        $newestContentDate = '';

		$isFolder = TRUE;
    	$trashedContents = $this->getAllContentsForRestore($isFolder);
    	if(isset($trashedContents) && count($trashedContents) > 0)
    	{
    		$restoreContentCount = count($trashedContents);
    		foreach ($trashedContents as $contKey => $trashedContent) 
    		{
    			$consContentId = $trashedContent->content_id;
    			$trashedContentAttachments = $this->getContentAttachments($consContentId, $isFolder);
    			foreach ($trashedContentAttachments as $attKey => $contentAttachment) 
    			{
    				$restoreContentAttachmentCount++;
    				$restoreContentSizeKb += $contentAttachment->filesize;
    			}
    		}

    		$oldestContentDate = $trashedContents[0]->create_timestamp;
    		$newestContentDate = $trashedContents[$restoreContentCount - 1]->create_timestamp;
    	}

    	if($restoreContentSizeKb > 0)
    	{
    		$restoreContentSizeMb = floor($restoreContentSizeKb/1024);

    		if($restoreContentSizeMb > 0)
    		{
    			$restoreContentSizeStr = $restoreContentSizeMb.' MB(s)';
    		}
    		else
    		{
    			$restoreContentSizeStr = $restoreContentSizeKb.' KB(s)';
    		}
    	}

    	$metricsData = array();
        $metricsData['trashedContents'] = $trashedContents;
        $metricsData['restoreContentCount'] = $restoreContentCount;
        $metricsData['restoreContentSizeKb'] = $restoreContentSizeKb;
        $metricsData['restoreContentSizeStr'] = $restoreContentSizeStr;
        $metricsData['oldestContentDate'] = $oldestContentDate;
        $metricsData['newestContentDate'] = $newestContentDate;

        return $metricsData;
	}
	
	public function getVideoConferenceObject($id, $validateCreator)
	{
		$videoConference = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgVideoConference;
				$modelObj->setConnection($this->orgDbConName);

	        	if($validateCreator)
	        	{
	        		$modelObj = $modelObj->forEmployee($this->orgEmpId);
	        	}
			}
			else
			{
       	 		$modelObj = New SysVideoConference;

	        	if($validateCreator)
	        	{
	        		$modelObj = $modelObj->forAppuser($this->userId);
	        	}
			}

			if(isset($modelObj))
			{
        		$videoConference = $modelObj->byId($id)->first();
			}
		}
		
		return $videoConference;
	}
	
	public function getVideoConferenceParticipants($id)
	{
		$videoConferenceParticipants = NULL;
			
		if($id > 0)
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgVideoConferenceParticipant;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{
       	 		$modelObj = New SysVideoConferenceParticipant;
			}

			if(isset($modelObj))
			{
        		$videoConferenceParticipants = $modelObj->byVideoConference($id)->get();
			}
		}
		
		return $videoConferenceParticipants;
	}
	
	public function getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication = FALSE, $conferencePassword = NULL)
	{
		$videoConferenceId = NULL;
			
		if(isset($conferenceCode) && $conferenceCode != "")
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgVideoConference;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{
       	 		$modelObj = New SysVideoConference;
			}

    		$fetchedVideoConference = $modelObj->byConferenceCode($conferenceCode)->first();

			if(isset($fetchedVideoConference))
			{
				if(isset($this->orgDbConName) && $this->orgDbConName != "")
	            {
					$fetchedVideoConferenceId = $fetchedVideoConference->org_vc_id;
				}
				else
				{
					$fetchedVideoConferenceId = $fetchedVideoConference->sys_vc_id;
				}

				if($performAuthentication)
				{
					$decPassword = Crypt::decrypt($fetchedVideoConference->password);

					if($decPassword == $conferencePassword)
					{
						$videoConferenceId = $fetchedVideoConferenceId;
					}
				}
				else
				{
					$videoConferenceId = $fetchedVideoConferenceId;
				}
			}

		}
		
		return $videoConferenceId;
	}
    
    public function addEditVideoConference($id, $meetingTitle, $startTimeStamp, $endTimeStamp, $isOpenConference, $participantIdArr, $participantIsModeratorArr, $isScheduled = 1)
    {
    	// Log::info('id : '.$id.' : meetingTitle : '.$meetingTitle);

		$response = array();
		$hasConflict = 0;
		$syncId = 0;
		$newName = "";
		
        $conferenceDetails = array();
        $conferenceDetails['meeting_title'] = Crypt::encrypt($meetingTitle);
        $conferenceDetails['scheduled_start_ts'] = $startTimeStamp;
        $conferenceDetails['scheduled_end_ts'] = $endTimeStamp;
        $conferenceDetails['is_open_conference'] = $isOpenConference;

		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			
   			$conferenceDetails['creator_employee_id'] = $this->orgEmpId;
   			       				
			$modelObj = New OrgVideoConference;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$conferenceTableName = $modelObj->table;
   			       			
			$participantModelObj = New OrgVideoConferenceParticipant;
			$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
			$conferenceParticipantTableName = $participantModelObj->table;
		}
		else
		{
       		$conferenceDetails['creator_appuser_id'] = $this->userId;

			$modelObj = New SysVideoConference;
			$conferenceTableName = $modelObj->table;		
   			       			
			$participantModelObj = New SysVideoConferenceParticipant;
			$conferenceParticipantTableName = $participantModelObj->table;
		}
		
		$retSyncId = 0;
		$genVideoConferenceCode = "";
        $videoConference = $this->getVideoConferenceObject($id, TRUE);
		if(isset($videoConference))
		{
			$genVideoConferenceCode = ($videoConference->conference_code);
			$genVideoConferencePassword = Crypt::decrypt($videoConference->password);

			$retSyncId = $id;
			$conferenceDetails['created_at'] = $videoConference->created_at;
   			$conferenceDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
   			
        	$videoConference->update($conferenceDetails);
			$syncId = $id;
		}
		else
		{
			$meetingCodeStrLength = Config::get('app_config.video_conference_meeting_code_base_length');
			$genVideoConferenceCode = CommonFunctionClass::generateRandomNumericString($meetingCodeStrLength);
			$genMeetingId = CommonFunctionClass::generateRandomAlphaNumericString(16);
			$genVideoConferencePassword = CommonFunctionClass::generateRandomNumericString(6);

			if($this->orgId > 0)
		    {
		    	$orgIdAppendage = str_pad($this->orgId, 4, '0', STR_PAD_LEFT);
		    	$genVideoConferenceCode = $genVideoConferenceCode.''.$orgIdAppendage;
		    }

			$conferenceDetails['conference_code'] = ($genVideoConferenceCode);
			$conferenceDetails['gen_meeting_id'] = Crypt::encrypt($genMeetingId);
			$conferenceDetails['password'] = Crypt::encrypt($genVideoConferencePassword);
   			$conferenceDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();	
	   		$conferenceDetails['updated_at'] = NULL;	
   					
			$syncId = DB::connection($conName)->table($conferenceTableName)->insertGetId($conferenceDetails);
			$retSyncId = $syncId;
		}

    	// Log::info('retSyncId : '.$retSyncId);
		
		if($syncId > 0)
		{	
			$conferenceParticipants = $participantModelObj->byVideoConference($syncId)->get();
			
    		// Log::info('conferenceParticipants : ');
    		// Log::info($conferenceParticipants);

	        $existingParticipantIdArr = array();
	        $existingParticipantObjArr = array();
	        $existingParticipantContentIdArr = array();
	        if(isset($conferenceParticipants) && count($conferenceParticipants) > 0)
	        {
	        	foreach ($conferenceParticipants as $conferenceParticipant) 
	            {
	                $participantId = $conferenceParticipant->participant_id;
                	$participantContentId = $conferenceParticipant->scheduled_content_id;
	                if($participantId > 0 && (!isset($participantIdArr) || !is_array($participantIdArr) || !in_array($participantId, $participantIdArr)))
	                {
	                	if($participantContentId > 0)
	                	{
		                	$prtDepMgmtObj = New ContentDependencyManagementClass;
		                	if($this->orgId > 0)
		                	{
			                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
		                	}
		                	else
		                	{
		                		$prtUser =  new \stdClass();
		                		$prtUser->appuser_id = $participantId;

			                	$prtDepMgmtObj->withOrgKey($prtUser, "");
		                	}

		                	$prtDepMgmtObj->deleteContent($participantContentId, TRUE);
	                	}
			    		// Log::info('delete existingParticipant : ');
			    		// Log::info($conferenceParticipant);

	                    $conferenceParticipant->delete();
	                } 
	                else
	                {
	                	array_push($existingParticipantIdArr, $participantId);	
	                	array_push($existingParticipantObjArr, $conferenceParticipant);	
	                	array_push($existingParticipantContentIdArr, $participantContentId);						
					}   
	            }
			}

    		// Log::info('existingParticipantIdArr : ');
    		// Log::info($existingParticipantIdArr);

    		// Log::info('existingParticipantContentIdArr : ');
    		// Log::info($existingParticipantContentIdArr);

			$createTs = CommonFunctionClass::getCreateTimestamp();
			$updateTs = CommonFunctionClass::getCreateTimestamp();
			$fromTimeStamp = $startTimeStamp;
			$toTimeStamp = $endTimeStamp;
			$sharedByEmail = $this->getEmployeeOrUserEmail();
			$userOrEmpName = $this->getEmployeeOrUserName();

			if($isScheduled == 1)
				$contentText = $userOrEmpName." is inviting you to a meeting."."<br/><br/>";
			else
				$contentText = $userOrEmpName." has started a meeting. Kindly join immediately."."<br/><br/>";

			$contentText .= "Topic: ".$meetingTitle."<br/>";
			$contentText .= "Meeting ID: ".$genVideoConferenceCode."<br/>";
			$contentText .= "PIN: ".$genVideoConferencePassword."<br/>";

			$appendedContentText = CommonFunctionClass::getSharedByAppendedString($contentText, $updateTs, $userOrEmpName, $sharedByEmail);
			
			$colorCode = Config::get('app_config.default_content_color_code');
			$isLocked = 1;
			$isShareEnabled = $isOpenConference == 1 ? 1 : 0;
			$remindBeforeMillis = NULL;
			$repeatDuration = NULL;
			$sourceId = NULL;
			$isRemoved = 0;
			$removedAt = NULL;
			$contentTypeId = Config::get('app_config.content_type_c');
			$tagsArr = array();
			$removeAttachmentIdArr = array();
			$isMarked = 0;
			$isCompleted = Config::get('app_config.default_content_is_completed_status');
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
            $reminderTimestamp = $startTimeStamp;
			
			if(isset($participantIdArr) && count($participantIdArr) > 0)
	        {
				$isFolder = TRUE;
				
				foreach($participantIdArr as $participantIndex => $participantId)
	            {
	                if($participantId > 0)
	                {
	                	$participantIsModerator = isset($participantIsModeratorArr[$participantIndex]) ? $participantIsModeratorArr[$participantIndex] : 0;

			    		// Log::info('participantId : '.$participantId);

			    		$existingVideoConferenceParticipant = NULL;

	                	$existingParticipantContentId = 0;
	                	if(!in_array($participantId, $existingParticipantIdArr))
	                	{

	                	}
	                	else
	                	{
	                		$existingParticipantIndex = array_search($participantId, $existingParticipantIdArr);
	                		$existingParticipantContentId = $existingParticipantContentIdArr[$existingParticipantIndex];

	                		$existingVideoConferenceParticipant = $existingParticipantObjArr[$existingParticipantIndex];
	                	}

			    		// Log::info('existingParticipantContentId : '.$existingParticipantContentId);

	                	$prtDepMgmtObj = New ContentDependencyManagementClass;
	                	if($this->orgId > 0)
	                	{
		                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
	                	}
	                	else
	                	{
	                		$prtUser =  new \stdClass();
	                		$prtUser->appuser_id = $participantId;

		                	$prtDepMgmtObj->withOrgKey($prtUser, "");
	                	}
						$usrDefFolderId = $prtDepMgmtObj->getDefaultFolderId();

	                	$prtContentResponse = $prtDepMgmtObj->addEditContent($existingParticipantContentId, $appendedContentText, $contentTypeId, $usrDefFolderId, $sourceId, $tagsArr, $isMarked, $createTs, $updateTs, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail);

	                	$participantContentId = $prtContentResponse['syncId'];

	                	$isAdd = 0;
	                	if($existingParticipantContentId != $participantContentId)
	                	{			
	                		$isAdd = 1;   

						    if($this->orgId > 0)
						    {
								MailClass::sendOrgContentAddedMail($this->orgId, $participantId, $participantContentId, $sharedByEmail, NULL, 1);
							}         
							else
							{
								MailClass::sendContentAddedMail($participantId, $participantContentId, $sharedByEmail, NULL, 1);
							}
	                	}

			        	$participantDetails = array();
			        	
			        	if($conName == "")
			        	{
			        		$participantDetails['sys_vc_id'] = $syncId;					
						}
			        	else
			        	{
			        		$participantDetails['org_vc_id'] = $syncId;
		            		$participantDetails['is_employee'] = 1;	
						}
	                	
	                	$participantDetails['participant_id'] = $participantId;	
	                	$participantDetails['scheduled_content_id'] = $participantContentId;
	                	$participantDetails['is_moderator'] = $participantIsModerator;
	                	
	                	if(isset($existingVideoConferenceParticipant))
	                	{				
	            			$participantDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();

				        	$existingVideoConferenceParticipant->update($participantDetails);	                		
	                	}
	                	else
                		{
	            			$participantDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();

	                		DB::connection($conName)->table($conferenceParticipantTableName)->insert($participantDetails);
	                	}

	                	// Log::info('sendRespectiveContentModificationPush : participantContentId : '.$participantContentId.' : isAdd : '.$isAdd.' : sharedByEmail : '.$sharedByEmail);

						$prtDepMgmtObj->sendRespectiveContentModificationPush($isFolder, $participantContentId, $isAdd, $sharedByEmail);
	                }                            
	            }
			}		
		}
		
		$response["syncId"] = $retSyncId;
		$response["syncConferenceCode"] = $genVideoConferenceCode;
		
		return $response;
	}
    
    public function deleteVideoConference($id)
    {
		if(isset($id) && $id > 0)
		{
			if(isset($this->orgDbConName))
	    	{
				$modelObj = New OrgVideoConference;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$conferenceTableName = $modelObj->table;
            	$userConference = $modelObj->byId($id)->first();
	   			       			
				$participantModelObj = New OrgVideoConferenceParticipant;
				$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
				$conferenceParticipantTableName = $participantModelObj->table;
            	$conferenceParticipants = $participantModelObj->byVideoConference($id)->get();
			}
			else
			{
				$modelObj = New SysVideoConference;
				$conferenceTableName = $modelObj->table;	
            	$userConference = $modelObj->byId($id)->first();	
	   			       			
				$participantModelObj = New SysVideoConferenceParticipant;
				$conferenceParticipantTableName = $participantModelObj->table;
            	$conferenceParticipants = $participantModelObj->byVideoConference($id)->get();
			}				
						
			if(isset($userConference))
            {
            	$isFolder = TRUE;		
            	if(isset($conferenceParticipants))
            	{
					foreach($conferenceParticipants as $conferenceParticipant)
            		{
	                	$participantContentId = $conferenceParticipant->scheduled_content_id;
	                	$participantId = $conferenceParticipant->participant_id;

	                	if($participantContentId > 0)
	                	{
		                	$prtDepMgmtObj = New ContentDependencyManagementClass;
		                	if($this->orgId > 0)
		                	{
			                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
		                	}
		                	else
		                	{
		                		$prtUser =  new \stdClass();
		                		$prtUser->appuser_id = $participantId;

			                	$prtDepMgmtObj->withOrgKey($prtUser, "");
		                	}

		                	$prtDepMgmtObj->deleteContent($participantContentId, $isFolder);
            				
            				$conferenceParticipant->delete();
	                	}
					}
				}
				
	        	$this->logVideoConferenceAsDeleted($id);
            	$userConference->delete();
			}
		}
	}
    
    public function canUserJoinVideoConference($id)
    {
    	$response = array();
    	$canJoinStatus = 0;
    	$canJoinStatusMessage = "";

		if(isset($id) && $id > 0)
		{
			if(isset($this->orgDbConName))
	    	{
				$modelObj = New OrgVideoConference;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
	   			       			
				$participantModelObj = New OrgVideoConferenceParticipant;
				$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New SysVideoConference;	
	   			       			
				$participantModelObj = New SysVideoConferenceParticipant;
			}

			$userOrEmpId = $this->getEmployeeOrUserId();

        	$userConference = $modelObj->byId($id)->first();
        	$userConferenceParticipant = $participantModelObj->byVideoConference($id)->byParticipant($userOrEmpId)->first();

        	if(!isset($userConferenceParticipant) && isset($this->orgDbConName))
        	{
				$userId = $this->getUserId();
        		$userConferenceParticipant = $participantModelObj->byVideoConference($id)->byParticipantAppuser($userId)->first();
        	}
						
			if(isset($userConference) && (isset($userConferenceParticipant) || ($userConference->is_open_conference == 1)))
            {
            	$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

                $utcTz =  'UTC';
                $conMinStartDateTimeObj = Carbon::now($utcTz)->addHour();
                $conMinStartDateTimeObj->second = 0;
                $conMinStartTimeStamp = $conMinStartDateTimeObj->timestamp;       
                $conMinStartTimeStamp = $conMinStartTimeStamp * 1000; 


                $conMinWaitingRoomDateTimeObj = Carbon::now($utcTz)->addMinutes(5);
                $conMinWaitingRoomDateTimeObj->second = 0;
                $conMinWaitingRoomTimeStamp = $conMinWaitingRoomDateTimeObj->timestamp;       
                $conMinWaitingRoomTimeStamp = $conMinWaitingRoomTimeStamp * 1000; 

            	if($currentTimestamp >= $userConference->scheduled_start_ts || $conMinStartTimeStamp >= $userConference->scheduled_start_ts)// && ($conMinStartTimeStamp <= $userConference->scheduled_end_ts || $userConference->is_running == 1))
            	{
            		$allowToJoin = FALSE;
            		$allowWaitingRoomJoin = FALSE;
            		if($userConference->is_running == 1)
            		{
            			$allowToJoin = TRUE;
            		}
            		elseif($userConference->is_running == 0)
					{
						if($currentTimestamp <= $userConference->scheduled_end_ts)
						{
							if(($this->orgId == 0 && $userConference->creator_appuser_id == $this->userId) || ($this->orgId > 0 && $userConference->creator_employee_id == $this->orgEmpId))
							{
            					$allowToJoin = TRUE;
							}
							else
							{
								$canJoinStatus = -1;
								$canJoinStatusMessage = 'The conference hasn\'t started yet';

								if($conMinWaitingRoomTimeStamp >= $userConference->scheduled_start_ts)
								{
									$allowWaitingRoomJoin = TRUE;
								}
							}
						}
						else
						{
							$canJoinStatus = -1;
							$canJoinStatusMessage = 'The conference already expired';
						}
					}

					if($allowToJoin || $allowWaitingRoomJoin)
					{
						$userOrEmpName = $this->getEmployeeOrUserName();
						$userOrEmpEmail = $this->getEmployeeOrUserEmail();
						$userOrgName = "";
						$organizationDetails = $this->getOrganizationObject();
						if(isset($organizationDetails))
						{
							$userOrgName = $organizationDetails->system_name;
						}

						$participantIsInvited = 0;
						if(isset($userConferenceParticipant))
						{
							$participantIsInvited = 1;							
						}

						$domainName = "hylyt.co.in";
						$domainUrl = "https://".$domainName;

						if($allowToJoin)
						{
							$canJoinStatus = 1;
						}
						else
						{
							$canJoinStatus = -1;
						}
						
						$response['allowWaitingRoomJoin'] = $allowWaitingRoomJoin ? 1 : 0;

						$response['conferenceId'] = $id;
						$response['conferenceCode'] = $userConference->conference_code;
						$response['meetingId'] = Crypt::decrypt($userConference->gen_meeting_id);
						$response['conferenceSubject'] = Crypt::decrypt($userConference->meeting_title);
						$response['isOpenConference'] = $userConference->is_open_conference;

						$response['domainName'] = $domainName;
						$response['domainUrl'] = $domainUrl;
						$response['organizationName'] = $userOrgName;
						$response['participantName'] = $userOrEmpName;
						$response['participantEmail'] = $userOrEmpEmail;
						$response['userConference'] = $userConference;
						$response['participantIsInvited'] = $participantIsInvited;
					}
            	}
            	else
            	{
					$canJoinStatus = -1;
					$canJoinStatusMessage = 'The conference has already expired';
            	}
			}
			else
			{
				$canJoinStatus = -1;
				$canJoinStatusMessage = 'You do not have access to any such conference';
			}
		}
    	else
    	{
			$canJoinStatus = -1;
			$canJoinStatusMessage = 'You do not have access to any such conference';
    	}

		$response['status'] = $canJoinStatus;
		$response['msg'] = $canJoinStatusMessage;

		return $response;
	}
    
    public function onVideoConferenceParticipantRemoved($id)
    {
		if(isset($id) && $id > 0)
		{
			$conName = "";
			if(isset($this->orgDbConName))
	    	{
				$conName = $this->orgDbConName;
	   			       				
				$modelObj = New OrgVideoConference;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$conferenceTableName = $modelObj->table;
	   			       			
				$participantModelObj = New OrgVideoConferenceParticipant;
				$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
				$conferenceParticipantTableName = $participantModelObj->table;
			}
			else
			{
				$modelObj = New SysVideoConference;
				$conferenceTableName = $modelObj->table;
	   			       			
				$participantModelObj = New SysVideoConferenceParticipant;
				$conferenceParticipantTableName = $participantModelObj->table;
			}

			$userOrEmpId = $this->getEmployeeOrUserId();

        	$userConference = $modelObj->byId($id)->first();

        	$userConferenceParticipant = clone $participantModelObj;
        	$userConferenceParticipant = $userConferenceParticipant->byVideoConference($id)->byParticipant($userOrEmpId)->first();

        	$orgParticipantIsAppuser = FALSE;
        	$orgParticipantAppuserId = $this->getUserId();
        	if(!isset($userConferenceParticipant) && isset($this->orgDbConName))
        	{
        		$orgParticipantIsAppuser = TRUE;
        		$userConferenceParticipant = $participantModelObj->byVideoConference($id)->byParticipantAppuser($orgParticipantAppuserId)->first();
        	}
						
			if(isset($userConference) && isset($userConferenceParticipant))
            {	
            	$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

            	if($currentTimestamp >= $userConference->scheduled_start_ts && ($currentTimestamp <= $userConference->scheduled_end_ts || $userConference->is_running == 1))
            	{
            		$isConferenceStarted = FALSE;
					if($userConference->is_running == 0) 
					{
            			$isConferenceStarted = TRUE;

						$userConference->is_running = 1;
						$userConference->actual_start_ts = $currentTimestamp;
						$userConference->save();	
					}

					if(isset($userConferenceParticipant))
					{
						if($userConferenceParticipant->has_attended == 0)
						{
	                		$participantContentId = $userConferenceParticipant->scheduled_content_id;

		                	if($participantContentId > 0)
		                	{
			                	$prtDepMgmtObj = New ContentDependencyManagementClass;
			                	if($this->orgId > 0)
			                	{
				                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
			                	}
			                	else
			                	{
			                		$prtUser =  new \stdClass();
			                		$prtUser->appuser_id = $participantId;

				                	$prtDepMgmtObj->withOrgKey($prtUser, "");
			                	}

			                	$prtDepMgmtObj->deleteContent($participantContentId, $isFolder);
	            				
	            				$userConferenceParticipant->delete();
					
								$userOrEmpName = $this->getEmployeeOrUserName();
								$userOrEmpEmail = $this->getEmployeeOrUserEmail();

								if($orgParticipantIsAppuser)
			        			{
			        				$userObject = $this->getUserObject();
									$userOrEmpName = $userObject->fullname;
									$userOrEmpEmail = $userObject->email;
			        			}
		                	}
						}
					}

            	}		
			}
		}
	}
    
    public function onVideoConferenceJoinedByUser($id, $sendWithButtons = NULL)
    {
		if(isset($id) && $id > 0)
		{
			$conName = "";
			if(isset($this->orgDbConName))
	    	{
				$conName = $this->orgDbConName;
	   			       				
				$modelObj = New OrgVideoConference;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
				$conferenceTableName = $modelObj->table;
	   			       			
				$participantModelObj = New OrgVideoConferenceParticipant;
				$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
				$conferenceParticipantTableName = $participantModelObj->table;
			}
			else
			{
				$modelObj = New SysVideoConference;
				$conferenceTableName = $modelObj->table;
	   			       			
				$participantModelObj = New SysVideoConferenceParticipant;
				$conferenceParticipantTableName = $participantModelObj->table;
			}

			$userOrEmpId = $this->getEmployeeOrUserId();

        	$userConference = $modelObj->byId($id)->first();

        	$userConferenceParticipant = clone $participantModelObj;
        	$userConferenceParticipant = $userConferenceParticipant->byVideoConference($id)->byParticipant($userOrEmpId)->first();

        	$orgParticipantIsAppuser = FALSE;
        	$orgParticipantAppuserId = $this->getUserId();
        	if(!isset($userConferenceParticipant) && isset($this->orgDbConName))
        	{
        		$orgParticipantIsAppuser = TRUE;
        		$userConferenceParticipant = $participantModelObj->byVideoConference($id)->byParticipantAppuser($orgParticipantAppuserId)->first();
        	}
						
			if(isset($userConference) && (isset($userConferenceParticipant) || ($userConference->is_open_conference == 1)))
            {	
            	$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

            	// if($currentTimestamp >= $userConference->scheduled_start_ts && ($currentTimestamp <= $userConference->scheduled_end_ts || $userConference->is_running == 1))
            	{
            		$isConferenceStarted = FALSE;

        			$conferenceAttendants = clone $participantModelObj; 
	        		$conferenceAttendants = $conferenceAttendants->byVideoConference($id)->hasAttended()->get();			
	    			// if(isset($conferenceAttendants) && count($conferenceAttendants) > 0)
	    			// {
					// }
					// else

					if($userConference->is_running == 0) 
					{
            			$isConferenceStarted = TRUE;

						$userConference->is_running = 1;
						$userConference->actual_start_ts = $currentTimestamp;
						$userConference->save();	
					}

					if(isset($userConferenceParticipant))
					{
						if($userConferenceParticipant->has_attended == 0)
						{
							$userConferenceParticipant->has_attended = 1;
							$userConferenceParticipant->conf_entry_ts = $currentTimestamp;
							$userConferenceParticipant->save();
						}
					}
					else
					{
						$participantContentId = 0;
						$participantIsModerator = 0;

			        	$participantDetails = array();
			            $participantDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
			        	if($conName == "")
			        	{
			        		$participantDetails['sys_vc_id'] = $id;	
	                		$participantDetails['participant_id'] = $userOrEmpId;				
						}
			        	else
			        	{
        					$orgParticipantIsAppuser = TRUE;
							$participantIsEmployee = 0;

			        		$participantDetails['org_vc_id'] = $id;
	                		$participantDetails['is_employee'] = $participantIsEmployee;
	                		$participantDetails['participant_appuser_id'] = $orgParticipantAppuserId;
	                		$participantDetails['participant_id'] = 0;//$userOrEmpId;	
						}

	                	$participantDetails['scheduled_content_id'] = $participantContentId;
	                	$participantDetails['is_moderator'] = $participantIsModerator;
	                	$participantDetails['is_self_joined'] = 1;
	                	$participantDetails['has_attended'] = 1;
	                	$participantDetails['conf_entry_ts'] = $currentTimestamp;

	                	DB::connection($conName)->table($conferenceParticipantTableName)->insertGetId($participantDetails);
					}
					
					$userOrEmpName = $this->getEmployeeOrUserName();
					$userOrEmpEmail = $this->getEmployeeOrUserEmail();

					if($orgParticipantIsAppuser)
        			{
        				$userObject = $this->getUserObject();
						$userOrEmpName = $userObject->fullname;
						$userOrEmpEmail = $userObject->email;
        			}

        			$otherConferenceParticipants = clone $participantModelObj; 

					if($orgParticipantIsAppuser)
        			{
        				$otherConferenceParticipants = $otherConferenceParticipants->byVideoConference($id)->excludeParticipantAppuser($orgParticipantAppuserId)->get();
        			}
        			else
        			{
        				$otherConferenceParticipants = $otherConferenceParticipants->byVideoConference($id)->excludeParticipant($userOrEmpId)->get();
        			}
        			foreach ($otherConferenceParticipants as $otherConferenceParticipant) 
        			{
        				if($this->orgId == 0)
        				{
        					$participantUserId = $otherConferenceParticipant->participant_id;
        					$orgKey = "";
        				}
        				else
        				{
        					if($otherConferenceParticipant->is_employee == 1)
        					{
	        					$participantOrgEmpId = $otherConferenceParticipant->participant_id;

	                			$prtDepMgmtObj = New ContentDependencyManagementClass;
		                		$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantOrgEmpId);
		                		$participantUserId = $prtDepMgmtObj->getUserId();

								$orgKey = OrganizationClass::getOrgEmpProfileKeyFromOrgAndEmpId($this->orgId, $participantOrgEmpId);
                            }
        					else
        					{
	        					$participantUserId = $otherConferenceParticipant->participant_appuser_id;
	        					$orgKey = "";
        					}
        				}

        				if(isset($sendWithButtons) && $sendWithButtons == 1)
        				{
        					$this->sendVideoConferenceStartedOrParticipantJoinedMessageToDeviceWithButtons($participantUserId, $orgKey, $this->currLoginToken, $isConferenceStarted, $userConference, $userConferenceParticipant, $userOrEmpName, $userOrEmpEmail);
        				}
        				else
        				{
        					$this->sendVideoConferenceStartedOrParticipantJoinedMessageToDevice($participantUserId, $orgKey, $this->currLoginToken, $isConferenceStarted, $userConference, $userConferenceParticipant, $userOrEmpName, $userOrEmpEmail);
        				}
        				MailClass::sendVideoConferenceStartedOrParticipantJoinedMail($participantUserId, $orgKey, $isConferenceStarted, $userConference, $userConferenceParticipant, $userOrEmpName, $userOrEmpEmail);
        			}
            	}
            	// else
            	// {

            	// }		
			}
		}
	}
    
    public function onVideoConferenceExitedByUser($id, $stopConference = FALSE)
    {
		if(isset($id) && $id > 0)
		{
			if(isset($this->orgDbConName))
	    	{
				$modelObj = New OrgVideoConference;
				$modelObj = $modelObj->setConnection($this->orgDbConName);
	   			       			
				$participantModelObj = New OrgVideoConferenceParticipant;
				$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
			}
			else
			{
				$modelObj = New SysVideoConference;	
	   			       			
				$participantModelObj = New SysVideoConferenceParticipant;
			}

			$userOrEmpId = $this->getEmployeeOrUserId();

        	$userConference = $modelObj->byId($id)->first();
        	$userConferenceParticipant = $participantModelObj->byVideoConference($id)->byParticipant($userOrEmpId)->first();
						
			if(isset($userConference) && isset($userConferenceParticipant))
            {	
            	$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

				$userConferenceParticipant->conf_exit_ts = $currentTimestamp;
				$userConferenceParticipant->save();

            	if($userConference->is_running == 1)
            	{
        			$conferenceAttendants = $participantModelObj->byVideoConference($id)->hasAttended()->hasExitPending()->get();
		            
		            if($stopConference || count($conferenceAttendants) == 0)
					{
						$userConference->is_running = 0;
						$userConference->actual_end_ts = $currentTimestamp;
						$userConference->save();

	        			foreach ($conferenceAttendants as $conferenceAttendant) 
	        			{
	        				$conferenceAttendant->conf_exit_ts = $currentTimestamp;
							$conferenceAttendant->save();
    					}		
					}
            	}
            	else
            	{

            	}		
			}
		}
	}

	public function fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword)
	{
        $videoConferenceId = $this->getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication, $conferencePassword); 

        $conferenceId = NULL;
        $consDepMgmtObj = $this;

        if(!isset($videoConferenceId))
        {
            $meetingCodeStrLength = Config::get('app_config.video_conference_meeting_code_base_length');
            if(strlen($conferenceCode) > $meetingCodeStrLength)
            {
                $meetingOrgIdStr = substr($conferenceCode, $meetingCodeStrLength);
                if(isset($meetingOrgIdStr) && $meetingOrgIdStr != '')
                {
                    $meetingOrgIdInt = intval($meetingOrgIdStr);

                    if($meetingOrgIdInt > 0)
                    {
                    	$user = $this->getUserObject();
                    	
                        $meetingOrgDepMgmtObj = New ContentDependencyManagementClass;
                        $meetingOrgDepMgmtObj->withOrgKey($user, "");
                        $meetingOrgDepMgmtObj->withOrgId($meetingOrgIdInt);

                        $videoConferenceId = $meetingOrgDepMgmtObj->getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication, $conferencePassword);
                        if(isset($videoConferenceId))
                        {
                            $conferenceId = $videoConferenceId;

                            $consDepMgmtObj = $meetingOrgDepMgmtObj;
                        }
                    }
                }
            }
            else if($this->orgId > 0)
            {
            	$user = $this->getUserObject();
            	
                $meetingOrgDepMgmtObj = New ContentDependencyManagementClass;
                $meetingOrgDepMgmtObj->withOrgKey($user, "");

                $videoConferenceId = $meetingOrgDepMgmtObj->getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication, $conferencePassword);
                if(isset($videoConferenceId))
                {
                    $conferenceId = $videoConferenceId;

                    $consDepMgmtObj = $meetingOrgDepMgmtObj;
                }
            }
        }
        else
        {
            $conferenceId = $videoConferenceId;
        }

        $fetchedConferenceResponse = NULL;
        if(isset($conferenceId))
        {
        	$fetchedConferenceResponse = array();
        	$fetchedConferenceResponse['conferenceId'] = $conferenceId;
        	$fetchedConferenceResponse['confDepMgmtObj'] = $consDepMgmtObj;
        }

        return $fetchedConferenceResponse;
	}
	
	public function getVideoConferenceListByListCode($listCode)
	{
		$videoConferences = NULL;
			
		if(isset($listCode) && $listCode != "")
		{	
			if(isset($this->orgDbConName))
            {
				$modelObj = New OrgVideoConferenceParticipant;
				$modelObj->setConnection($this->orgDbConName);
				$conferenceParticipantTableName = $modelObj->table;

				$modelObj = $modelObj->select(['*', $conferenceParticipantTableName.'.org_vc_id as cons_vc_id', 'creator_employee_id as creator_id', 'employee_name as creator_name', 'email as creator_email']);
				$modelObj = $modelObj->joinOrgVideoConferenceTable();
				$modelObj = $modelObj->joinOrgVideoConferenceCreatorTable();
			}
			else
			{
       	 		$modelObj = New SysVideoConferenceParticipant;
				$conferenceParticipantTableName = $modelObj->table;

				$modelObj = $modelObj->select(['*', $conferenceParticipantTableName.'.sys_vc_id as cons_vc_id', 'creator_appuser_id as creator_id', 'fullname as creator_name', 'email as creator_email']);
				$modelObj = $modelObj->joinSysVideoConferenceTable();
				$modelObj = $modelObj->joinSysVideoConferenceCreatorTable();
			}

			if(isset($modelObj))
			{
				$currTs = CommonFunctionClass::getCreateTimestamp();
				$userOrEmpId = $this->getEmployeeOrUserId();

    			$videoConferences = $modelObj->byParticipant($userOrEmpId);

    			$sortOrderStr = 'DESC';

				if($listCode == 'UPC')
				{
    				$videoConferences = $videoConferences->conferenceIsUpcoming($currTs);

    				$sortOrderStr = 'ASC';
				}
				else if($listCode == 'PST')
				{
    				$videoConferences = $videoConferences->conferenceIsPast($currTs);
				}
				else if($listCode == 'ATT')
				{
    				$videoConferences = $videoConferences->conferenceIsPast($currTs);
    				$videoConferences = $videoConferences->hasAttended();
				}

        		$videoConferences->orderBy('scheduled_start_ts', $sortOrderStr);
        		$videoConferences = $videoConferences->get();
			}
		}
		
		return $videoConferences;
	}

	public function getUserVideoConferenceInformation($id, $overrideUserMemberValidation = FALSE)
	{
    	$response = array();
    	$informationStatus = 0;
    	$informationStatusMessage = "";

    	if(isset($id) && $id > 0)
    	{
    		$userConference = $this->getVideoConferenceObject($id, FALSE);

	        if(isset($userConference))
	        {
				$userOrEmpId = $this->getEmployeeOrUserId();
	        	$userConferenceParticipants = $this->getVideoConferenceParticipants($id);
	        	$userOrEmpIsModerator = false;

	        	$totalInvites = count($userConferenceParticipants);
	        	$totalAttendants = 0;
	        	$allowToViewInformation = FALSE;

				$conferenceModeratorParticipantArr = array();
				$conferenceNormalParticipantArr = array();

				$userSession = $this->getAppuserSession();

				foreach ($userConferenceParticipants as $key => $conferenceParticipant) 
				{
					$participantId = $conferenceParticipant->participant_id;

                	$prtDepMgmtObj = New ContentDependencyManagementClass;
                	if($this->orgId > 0 && $conferenceParticipant->is_employee == 1)
                	{
	                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
                	}
                	else
                	{
                		if($this->orgId > 0 && $conferenceParticipant->is_employee == 0)
						{
							$participantId = $conferenceParticipant->participant_appuser_id;
						}

                		$prtUser =  new \stdClass();
                		$prtUser->appuser_id = $participantId;

	                	$prtDepMgmtObj->withOrgKey($prtUser, "");
                	}

					$participantName = $prtDepMgmtObj->getEmployeeOrUserName();
					$participantEmail = $prtDepMgmtObj->getEmployeeOrUserEmail();

		        	$participantDetailsObj = array();
					$participantDetailsObj['participantId'] = sracEncryptNumberData($participantId, $userSession);
					$participantDetailsObj['participantName'] = $participantName;
					$participantDetailsObj['participantEmail'] = $participantEmail;
					$participantDetailsObj['isModerator'] = $conferenceParticipant->is_moderator;
					$participantDetailsObj['hasAttended'] = $conferenceParticipant->has_attended;
					$participantDetailsObj['conferenceEntryTs'] = $conferenceParticipant->conf_entry_ts;
					$participantDetailsObj['conferenceExitTs'] = $conferenceParticipant->conf_exit_ts;

					if($conferenceParticipant->has_attended == 1)
					{
						$totalAttendants++;
					}

					if($conferenceParticipant->is_moderator == 1)
					{
						array_push($conferenceModeratorParticipantArr, $participantDetailsObj);
					}
					else
					{
						array_push($conferenceNormalParticipantArr, $participantDetailsObj);
					}

					if($participantId == $userOrEmpId)
            		{
	        			$allowToViewInformation = TRUE;

	        			if($conferenceParticipant->is_moderator == 1)
	        			{
	        				$userOrEmpIsModerator = TRUE;
	        			}
            		}
				}

				$conferenceModeratorParticipantArr = collect($conferenceModeratorParticipantArr)->sortBy('participantName')->toArray();
				$conferenceNormalParticipantArr = collect($conferenceNormalParticipantArr)->sortBy('participantName')->toArray();

				$conferenceParticipantArr = array_merge($conferenceModeratorParticipantArr, $conferenceNormalParticipantArr);

	        	if($allowToViewInformation || $overrideUserMemberValidation)
	        	{
					$isCreator = 0;

                	$crtDepMgmtObj = New ContentDependencyManagementClass;
                	if($this->orgId > 0)
                	{
						$creatorId = $userConference->creator_employee_id;
						if($creatorId == $this->orgEmpId)
						{
							$isCreator = 1;
						}

	                	$crtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $creatorId);
                	}
                	else
                	{
						$creatorId = $userConference->creator_appuser_id;
						if($creatorId == $this->userId)
						{
							$isCreator = 1;
						}

                		$prtUser =  new \stdClass();
                		$prtUser->appuser_id = $creatorId;

	                	$crtDepMgmtObj->withOrgKey($prtUser, "");
                	}
                	
					$creatorName = $crtDepMgmtObj->getEmployeeOrUserName();
					$creatorEmail = $crtDepMgmtObj->getEmployeeOrUserEmail();

					$userOrEmpName = $this->getEmployeeOrUserName();
					$userOrEmpEmail = $this->getEmployeeOrUserEmail();
					$userOrgName = "";
					$organizationDetails = $this->getOrganizationObject();
					if(isset($organizationDetails))
					{
						$userOrgName = $organizationDetails->system_name;
					}

            		$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

					$isUpcoming = 0;
					if($currentTimestamp <= $userConference->scheduled_start_ts || $currentTimestamp <= $userConference->scheduled_end_ts)
					{
						$isUpcoming = 1;
					}

	                $utcTz =  'UTC';
	                $conMinStartDateTimeObj = Carbon::now($utcTz)->addHour();
	                $conMinStartDateTimeObj->second = 0;
	                $conMinStartTimeStamp = $conMinStartDateTimeObj->timestamp;       
	                $conMinStartTimeStamp = $conMinStartTimeStamp * 1000; 

			        $isUserConferenceRunning = $userConference->is_running;
			        $canJoinConference = $isUserConferenceRunning;

			        $canStartConference = 0;
			        $canCancelConference = 0;
			        if($isUserConferenceRunning == 0)
			        {
			            if($isCreator == 1 && ($currentTimestamp >= $userConference->scheduled_start_ts || $conMinStartTimeStamp >= $userConference->scheduled_start_ts) && $currentTimestamp <= $userConference->scheduled_end_ts)
			            {
			                $canStartConference = 1;
			                $canCancelConference = 1;
			            }

			            if($isCreator == 1 && $currentTimestamp < $userConference->scheduled_start_ts)
			            {
			                $canCancelConference = 1;
			            }
			            else if($isCreator == 1 && (!isset($userConference->actual_start_ts) || $userConference->actual_start_ts == 0))
			            {
			                $canCancelConference = 1;
			            }
			        }

			        if($canJoinConference == 1)
			        {
		                if($currentTimestamp > $userConference->scheduled_end_ts)
		                {
		                    $canJoinConference = 0;
		                    $canCancelConference = 0;
		                }
			        }

			        if($canCancelConference == 1)
			        {
		                if($currentTimestamp > $userConference->scheduled_end_ts)
			            {
			                $canCancelConference = 0;
			            }
			        }

					$meetingTitle = Crypt::decrypt($userConference->meeting_title);
					$videoConferencePassword = Crypt::decrypt($userConference->password);
					$videoConferenceCode = $userConference->conference_code;

		        	$conferenceDetailsObj = array();
					$conferenceDetailsObj['conferenceId'] = sracEncryptNumberData($id, $userSession);
					$conferenceDetailsObj['conferenceCode'] = $videoConferenceCode;
					$conferenceDetailsObj['conferencePin'] = $videoConferencePassword;
					$conferenceDetailsObj['conferenceSubject'] = $meetingTitle;
					$conferenceDetailsObj['isOpenConference'] = $userConference->is_open_conference;
					$conferenceDetailsObj['scheduledStartTs'] = $userConference->scheduled_start_ts;
					$conferenceDetailsObj['scheduledEndTs'] = $userConference->scheduled_end_ts;
					$conferenceDetailsObj['isRunning'] = $userConference->is_running;
					$conferenceDetailsObj['actualStartTs'] = $userConference->actual_start_ts;
					$conferenceDetailsObj['actualEndTs'] = $userConference->actual_end_ts;
					$conferenceDetailsObj['totalInvites'] = $totalInvites;
					$conferenceDetailsObj['totalAttendants'] = $totalAttendants;
					$conferenceDetailsObj['isUpcoming'] = $isUpcoming;
					$conferenceDetailsObj['canCancelConference'] = $canCancelConference;
        			$conferenceDetailsObj['canStartConference'] = $canStartConference;
					$conferenceDetailsObj['canJoinConference'] = $canJoinConference;

					$conferenceDetailsObj['organizationName'] = $userOrgName;
					$conferenceDetailsObj['creatorName'] = $creatorName;
					$conferenceDetailsObj['creatorEmail'] = $creatorEmail;
                	$conferenceDetailsObj['isCreator'] = $isCreator;
                	$conferenceDetailsObj['isUserModerator'] = $userOrEmpIsModerator;
					
					$androidAppLink = Config::get('app_config.androidAppLink');
					$iosAppLink = Config::get('app_config.iosAppLink');
					$webAppLink = Config::get('app_config.webAppLink');

        			$timeFormatStr = "h:i A";
			        $istOffsetHours = 5;
			        $istOffsetMinutes = 30;

					$fromTimestamp = round($userConference->scheduled_start_ts/1000);

					$utcFromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
					$utcFromDateStr = $utcFromDt->toFormattedDateString() . ' ' . $utcFromDt->format($timeFormatStr);
					
					$istFromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
					$istFromDt = $istFromDt->addHours($istOffsetHours);
					$istFromDt = $istFromDt->addMinutes($istOffsetMinutes);
					$istFromDateStr = $istFromDt->toFormattedDateString() . ' ' . $istFromDt->format($timeFormatStr);

					$toTimestamp = round($userConference->scheduled_end_ts/1000);

					$utcToDt = Carbon::createFromTimeStampUTC($toTimestamp);
					$utcToDateStr = $utcToDt->toFormattedDateString() . ' ' . $utcToDt->format($timeFormatStr);
					
					$istToDt = Carbon::createFromTimeStampUTC($toTimestamp);
					$istToDt = $istToDt->addHours($istOffsetHours);
					$istToDt = $istToDt->addMinutes($istOffsetMinutes);
					$istToDateStr = $istToDt->toFormattedDateString() . ' ' . $istToDt->format($timeFormatStr);

					$meetingTimeStrUTC = $utcFromDateStr." to ".$utcToDateStr;
					$meetingTimeStrIST = $istFromDateStr." to ".$istToDateStr;

					$shareConferenceInviteStr = $userOrEmpName." is inviting you to a meeting."."<br/><br/>";
					$shareConferenceInviteStr .= "Topic: ".$meetingTitle."<br/>";
					$shareConferenceInviteStr .= "Time (UTC): ".$meetingTimeStrUTC."<br/>";
					$shareConferenceInviteStr .= "Time (IST): ".$meetingTimeStrIST."<br/>";
					$shareConferenceInviteStr .= "Meeting ID: ".$videoConferenceCode."<br/>";
					$shareConferenceInviteStr .= "PIN: ".$videoConferencePassword."<br/><br/>";

					$shareConferenceInviteStr .= "If you are a HyLyt user, then join via Android/iOS App or Website."."<br/><br/>";
					$shareConferenceInviteStr .= " - OR - <br/><br/>";
					$shareConferenceInviteStr .= "If you are not a HyLyt user, you can join HyLyt using the following link."."<br/>";
					$shareConferenceInviteStr .= "Android: ".$androidAppLink."<br/>";
					$shareConferenceInviteStr .= "iOS: ".$iosAppLink."<br/>";
					$shareConferenceInviteStr .= "Web: ".$webAppLink."";

					$conferenceDetailsObj['shareConferenceInviteStr'] = $shareConferenceInviteStr;
					$conferenceDetailsObj['meetingTimeStrUTC'] = $meetingTimeStrUTC;
					$conferenceDetailsObj['meetingTimeStrIST'] = $meetingTimeStrIST;

					$appDependenciesObj = array();
					$appDependenciesObj['androidAppLink'] = $androidAppLink;
					$appDependenciesObj['iosAppLink'] = $iosAppLink;
					$appDependenciesObj['webAppLink'] = $webAppLink;

					$informationStatus = 1;
					$response['appDependencies'] = $appDependenciesObj;
					$response['videoConference'] = $conferenceDetailsObj;
					$response['participants'] = $conferenceParticipantArr;
	        	}
		        else
		        {
		            $informationStatus = -1;
		            $informationStatusMessage = "No such conference found";
		        }
	        }
	        else
	        {
	            $informationStatus = -1;
	            $informationStatusMessage = "No such conference found";
	        }
    	}
        else
        {
            $informationStatus = -1;
            $informationStatusMessage = "No such conference found";
        }	       

		$response['status'] = $informationStatus;
		$response['msg'] = $informationStatusMessage;

		return $response;
	}

	public function saveUserVideoConferenceInformationAsContent($id, $tzOfs)
	{
    	$response = array();
    	$informationStatus = 0;
    	$informationStatusMessage = "";

    	if(isset($id) && $id > 0)
    	{
            $videoConferenceResponse = $this->getUserVideoConferenceInformation($id, FALSE); 

	        if(isset($videoConferenceResponse))
	        {
	        	$informationStatus = $videoConferenceResponse['status'];
                $informationStatusMessage = $videoConferenceResponse['msg'];

                if($informationStatus > 0)
                {
                	$videoConference = $videoConferenceResponse['videoConference'];
                	$participants = $videoConferenceResponse['participants'];

                	$conferenceSubject = $videoConference['conferenceSubject'];
                	$isOpenConference = $videoConference['isOpenConference'];
                	$scheduledStartTs = $videoConference['scheduledStartTs'];
                	$scheduledEndTs = $videoConference['scheduledEndTs'];
                	$isRunning = $videoConference['isRunning'];
                	$actualStartTs = $videoConference['actualStartTs'];
                	$actualEndTs = $videoConference['actualEndTs'];
                	$totalInvites = $videoConference['totalInvites'];
                	$totalAttendants = $videoConference['totalAttendants'];
                	$creatorName = $videoConference['creatorName'];
                	$creatorEmail = $videoConference['creatorEmail'];
                	$isCreator = $videoConference['isCreator'];

                	$hasBeenStarted = FALSE;

                	$formattedScheduledStartTime = dbToDispDateTimeWithTZOffset($scheduledStartTs, $tzOfs);
                	$formattedScheduledEndTime = dbToDispDateTimeWithTZOffset($scheduledEndTs, $tzOfs);

                	$formattedScheduledStartTimeCombined = formatTimeStampToUTCAndISTDateTimeString($scheduledStartTs);
                	$formattedScheduledEndTimeCombined = formatTimeStampToUTCAndISTDateTimeString($scheduledEndTs);

                	$formattedActualStartTime = NULL;
                	$formattedActualStartTimeCombined = NULL;
                	if(isset($actualStartTs) && $actualStartTs > 0)
                	{
                		$hasBeenStarted = TRUE;
            			$formattedActualStartTime = dbToDispDateTimeWithTZOffset($actualStartTs, $tzOfs);

	                	$formattedActualStartTimeCombined = formatTimeStampToUTCAndISTDateTimeString($actualStartTs);
                	}

                	$formattedActualEndTime = NULL;
                	$formattedActualEndTimeCombined = NULL;
                	if(isset($actualEndTs) && $actualEndTs > 0)
                	{
            			$formattedActualEndTime = dbToDispDateTimeWithTZOffset($actualEndTs, $tzOfs);
            			
	                	$formattedActualEndTimeCombined = formatTimeStampToUTCAndISTDateTimeString($actualEndTs);
                	}

                	$contentStr = "";
           			$contentStr .= "<br><b>".$conferenceSubject."</b> ";
           			$contentStr .= "<br>Start Time - <b>".$formattedScheduledStartTimeCombined."</b> ";
           			$contentStr .= "<br>End Time - <b>".$formattedScheduledEndTimeCombined."</b> ";

           			$contentStr .= "<br><br>Total Invites - <i><b>".$totalInvites."</b></i> ";
           			if($hasBeenStarted)
           			{
           				$contentStr .= "<br>Total Attendants - <i><b>".$totalAttendants."</b></i> ";
           			}

           			$contentStr .= "<br><br><i><b>Participants</b></i><br><br> ";

           			$participantListStr = "";

           			$participantSrNo = 1;
                    foreach ($participants as $participant) 
                    {
                    	$participantName = $participant['participantName'];
                    	$participantEmail = $participant['participantEmail'];
                    	$isModerator = $participant['isModerator'];
                    	$hasAttended = $participant['hasAttended'];
                    	$conferenceEntryTs = $participant['conferenceEntryTs'];
                    	$conferenceExitTs = $participant['conferenceExitTs'];

                    	$formattedConferenceEntryTime = NULL;
	                    $formattedConferenceExitTimeCombined = NULL;
	                    
                    	$formattedConferenceEntryTime = NULL;
	                    $formattedConferenceExitTimeCombined = NULL;

                    	if($hasAttended == 1)
                    	{
	                    	if(isset($conferenceEntryTs) && $conferenceEntryTs > 0)
	                    	{
	                			$formattedConferenceEntryTime = dbToDispDateTimeWithTZOffset($conferenceEntryTs, $tzOfs);
	                			$formattedConferenceEntryTimeCombined = formatTimeStampToUTCAndISTDateTimeString($conferenceEntryTs);
	                    	}

	                    	if(isset($conferenceExitTs) && $conferenceExitTs > 0)
	                    	{
	                			$formattedConferenceExitTime = dbToDispDateTimeWithTZOffset($conferenceExitTs, $tzOfs);
	                			$formattedConferenceExitTimeCombined = formatTimeStampToUTCAndISTDateTimeString($conferenceExitTs);
	                    	}
                    	}

           				$participantListStr .= "<b>".$participantSrNo.". ".$participantName."</b>";
           				$participantListStr .= "<br> &nbsp &nbsp ".$participantEmail."";

           				if($hasAttended == 1)
                    	{
		                    $participantListStr .= "<br> &nbsp &nbsp ".$formattedConferenceEntryTimeCombined;
		                    if(isset($formattedConferenceExitTimeCombined))
		                    {
		                    	$participantListStr .= " - ".$formattedConferenceExitTimeCombined;
		                    }                 		
                    	}
                    	else
                    	{
		                    $participantListStr .= "<br> &nbsp &nbsp "."Did not attend";
		                }

	                    $participantListStr .= "<br><br>";

           				$participantSrNo++;
                    }

                    $contentStr .= $participantListStr;

                    $utcTz =  'UTC';
                    $createDateObj = Carbon::now($utcTz);
                    $createTimeStamp = $createDateObj->timestamp;                   
                    $createTimeStamp = $createTimeStamp * 1000;
                    $updateTimeStamp = $createTimeStamp;

                	$defFolderId = $this->getDefaultFolderId();
                    $colorCode = Config::get('app_config.default_content_color_code');
                    $isLocked = 1;
                    $isShareEnabled = 1;
                    $contentType = Config::get('app_config.content_type_a');
                    $sourceId = 0;
                    $tagsArr = array();
                    $removeAttachmentIdArr = NULL;
                    $fromTimeStamp = "";
                    $toTimeStamp = "";
                    $isMarked = 0;
                    $remindBeforeMillis = 0;
                    $repeatDuration = 0;
		            $isCompleted = Config::get('app_config.default_content_is_completed_status');
		            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
		            $reminderTimestamp = NULL;

                    $isFolder = TRUE;

                    $contentAddResponse = $this->addEditContent(0, $contentStr, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, NULL);
                    $newServerContentId = $contentAddResponse['syncId'];   

                    if($newServerContentId > 0)
                    {
                    	$informationStatus = 1;
                    	$informationStatusMessage = 'Logs saved as content';

                        $isAdd = TRUE;
                        $this->sendRespectiveContentModificationPush($isFolder, $newServerContentId, $isAdd, NULL);
                    }
                    else
                    {
                    	$informationStatus = -1;
                    	$informationStatusMessage = 'Something went wrong';
                    }
                }			
	        }
	        else
	        {
	            $informationStatus = -1;
	            $informationStatusMessage = "No such conference found";
	        }
    	}
        else
        {
            $informationStatus = -1;
            $informationStatusMessage = "No such conference found";
        }	       

		$response['status'] = $informationStatus;
		$response['msg'] = $informationStatusMessage;

		return $response;
	}

	public function onVideoConferenceParticipantInvited($conferenceId, $videoConference, $recipientName, $recipientEmail)
	{
		if(isset($this->orgDbConName))
    	{
        	$modelObj = New OrgEmployee;
			$modelObj->setConnection($this->orgDbConName);
        	$employeeWithEmailExist = $modelObj->ofEmail($recipientEmail)->verifiedAndActive()->first();
            if(isset($employeeWithEmailExist))
            {
                $participantId = $employeeWithEmailExist->employee_id;
                $participantIsModerator = 0;
            	$participantIsEmployee = 1;
                $existingParticipantContentId = 0;
                $isScheduled = 1;

                $this->addVideoConferenceParticipant($conferenceId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled);
            }
            else
            {
            	$appuserWithEmailExists = Appuser::ofEmail($recipientEmail)->verified()->first();
            	if(isset($appuserWithEmailExists))
	            {
	                $participantId = $appuserWithEmailExists->appuser_id;
	                $participantIsModerator = 0;
                	$participantIsEmployee = 0;
	                $existingParticipantContentId = 0;
	                $isScheduled = 1;

	                $this->addVideoConferenceParticipant($conferenceId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled); 
	            }
	            else
	            {
	                $this->addVideoConferenceParticipantInvitation($conferenceId, $videoConference, $recipientName, $recipientEmail); 
	            }
            }
    	}
    	else
    	{
            $appuserWithEmailExists = Appuser::ofEmail($recipientEmail)->verified()->first();
            if(isset($appuserWithEmailExists))
            {
                $participantId = $appuserWithEmailExists->appuser_id;
                $participantIsModerator = 0;
                $participantIsEmployee = 0;
                $existingParticipantContentId = 0;
                $isScheduled = 1;

                $this->addVideoConferenceParticipant($conferenceId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled); 
            }
            else
            {
                $this->addVideoConferenceParticipantInvitation($conferenceId, $videoConference, $recipientName, $recipientEmail); 
            }
    	}
	}
    
    public function addVideoConferenceParticipant($conferenceId, $conference, $participantId, $participantIsModerator, $participantIsEmployee = 1, $existingParticipantContentId = 0, $isScheduled = 1)
    {
		$response = array();

		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			       				
			$modelObj = New OrgVideoConference;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$conferenceTableName = $modelObj->table;
   			       			
			$participantModelObj = New OrgVideoConferenceParticipant;
			$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
			$conferenceParticipantTableName = $participantModelObj->table;
		}
		else
		{
			$modelObj = New SysVideoConference;
			$conferenceTableName = $modelObj->table;		
   			       			
			$participantModelObj = New SysVideoConferenceParticipant;
			$conferenceParticipantTableName = $participantModelObj->table;
		}
		
		if($conferenceId > 0 && $participantId > 0)
		{	
        	if($this->orgId > 0 && $participantIsEmployee == 0)
        	{
				$conferenceParticipant = $participantModelObj->byVideoConference($conferenceId)->byParticipantAppuser($participantId)->first();
        	}
        	else
        	{
				$conferenceParticipant = $participantModelObj->byVideoConference($conferenceId)->byParticipant($participantId)->first();
        	}

			if(!isset($conferenceParticipant))
			{
				$createTs = CommonFunctionClass::getCreateTimestamp();
				$updateTs = CommonFunctionClass::getCreateTimestamp();
				$fromTimeStamp = $conference->scheduled_start_ts;
				$toTimeStamp = $conference->scheduled_end_ts;
				$sharedByEmail = $this->getEmployeeOrUserEmail();
				$userOrEmpName = $this->getEmployeeOrUserName();

				if($isScheduled == 1)
					$contentText = $userOrEmpName." is inviting you to a meeting."."<br/><br/>";
				else
					$contentText = $userOrEmpName." has started a meeting. Kindly join immediately."."<br/><br/>";

				$meetingTitle = Crypt::decrypt($conference->meeting_title);
				$videoConferenceCode = $conference->conference_code;
				$videoConferencePassword = Crypt::decrypt($conference->password);

				$contentText .= "Topic: ".$meetingTitle."<br/>";
				$contentText .= "Meeting ID: ".$videoConferenceCode."<br/>";
				$contentText .= "PIN: ".$videoConferencePassword."<br/>";

				$appendedContentText = CommonFunctionClass::getSharedByAppendedString($contentText, $updateTs, $userOrEmpName, $sharedByEmail);
				
				$colorCode = Config::get('app_config.default_content_color_code');
				$isLocked = 1;
				$isShareEnabled = 0;
				$remindBeforeMillis = NULL;
				$repeatDuration = NULL;
				$sourceId = NULL;
				$isRemoved = 0;
				$removedAt = NULL;
				$contentTypeId = Config::get('app_config.content_type_c');
				$tagsArr = array();
				$removeAttachmentIdArr = array();
				$isMarked = 0;
	            $isCompleted = Config::get('app_config.default_content_is_completed_status');
	            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
	            $reminderTimestamp = $fromTimeStamp;

	        	$participantDetails = array();
	            $participantDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
            	$participantDetails['participant_id'] = $participantId;	
	        	
	        	if($conName == "")
	        	{
	        		$participantDetails['sys_vc_id'] = $conferenceId;					
				}
	        	else
	        	{
	        		$participantDetails['org_vc_id'] = $conferenceId;
	        		$participantDetails['is_employee'] = $participantIsEmployee;

	        		if($participantIsEmployee == 0)
	        		{
            			$participantDetails['participant_id'] = 0;	
            			$participantDetails['participant_appuser_id'] = $participantId;	
	        		}
				}

				$isFolder = TRUE;

            	$prtDepMgmtObj = New ContentDependencyManagementClass;
            	if($this->orgId > 0 && $participantIsEmployee == 1)
            	{
                	$prtDepMgmtObj->withOrgIdAndEmpId($this->orgId, $participantId);
            	}
            	else
            	{
            		$prtUser =  new \stdClass();
            		$prtUser->appuser_id = $participantId;

                	$prtDepMgmtObj->withOrgKey($prtUser, "");
            	}
				$usrDefFolderId = $prtDepMgmtObj->getDefaultFolderId();

            	$prtContentResponse = $prtDepMgmtObj->addEditContent($existingParticipantContentId, $appendedContentText, $contentTypeId, $usrDefFolderId, $sourceId, $tagsArr, $isMarked, $createTs, $updateTs, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail);

            	$participantContentId = $prtContentResponse['syncId'];

            	$isAdd = 0;
            	if($existingParticipantContentId != $participantContentId)
            	{			
            		$isAdd = 1;   

				    if($this->orgId > 0)
				    {
						MailClass::sendOrgContentAddedMail($this->orgId, $participantId, $participantContentId, $sharedByEmail, NULL, 1);
					}         
					else
					{
						MailClass::sendContentAddedMail($participantId, $participantContentId, $sharedByEmail, NULL, 1);
					}
            	}
            	
            	$participantDetails['scheduled_content_id'] = $participantContentId;
            	$participantDetails['is_moderator'] = $participantIsModerator;

            	DB::connection($conName)->table($conferenceParticipantTableName)->insert($participantDetails);

				$prtDepMgmtObj->sendRespectiveContentModificationPush($isFolder, $participantContentId, $isAdd, $sharedByEmail);
			}					
		}
		
		return $response;
	}
    
    public function addVideoConferenceParticipantInvitation($conferenceId, $conference, $recipientName, $recipientEmail)
    {
		$response = array();

		$conName = "";
		if(isset($this->orgDbConName))
    	{	
			$conName = $this->orgDbConName;
   			       				
			$modelObj = New OrgVideoConference;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$conferenceTableName = $modelObj->table;
   			       			
			$participantModelObj = New OrgVideoConferenceParticipant;
			$participantModelObj = $participantModelObj->setConnection($this->orgDbConName);
			$conferenceParticipantTableName = $participantModelObj->table;
   			       			
			$inviteModelObj = New OrganizationVideoConferenceInvite;
			$conferenceInviteTableName = $inviteModelObj->table;
		}
		else
		{
			$modelObj = New SysVideoConference;
			$conferenceTableName = $modelObj->table;		
   			       			
			$participantModelObj = New SysVideoConferenceParticipant;
			$conferenceParticipantTableName = $participantModelObj->table;
   			       			
			$inviteModelObj = New SysVideoConferenceInvite;
			$conferenceInviteTableName = $inviteModelObj->table;
		}
		
		if($conferenceId > 0 && $recipientEmail != '')
		{	
			$conferenceInvitation = $inviteModelObj->byVideoConference($conferenceId)->byEmail($recipientEmail)->first();

			if(!isset($conferenceInvitation))
			{
	        	$inviteDetails = array();
	            $inviteDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
	        	
	        	if($conName == "")
	        	{
	        		$inviteDetails['sys_vc_id'] = $conferenceId;					
				}
	        	else
	        	{
	        		$inviteDetails['org_vc_id'] = $conferenceId;
	        		$inviteDetails['organization_id'] = $this->orgId;
				}   

	            $inviteDetails['name'] = $recipientName;
	            $inviteDetails['email'] = $recipientEmail;         	
            	
            	DB::table($conferenceInviteTableName)->insert($inviteDetails); // connection($conName)->

				$sharedByEmail = $this->getEmployeeOrUserEmail();
				$sharedByName = $this->getEmployeeOrUserName();

            	MailClass::sendVideoConferenceParticipantInvitationMail($this->orgId, $conference, $recipientName, $recipientEmail, $sharedByName, $sharedByEmail);
			}					
		}
		
		return $response;
	}
    
    /**
     * Set User(s) Default Parameters.
     *
     * @return void
     */
    public function setAppuserDefaultParamsPostVerification($userId)
    {
        $user = Appuser::byId($userId)->first();
        if(isset($user))
        {
            $isPremiumUser = $user->is_premium;

            $defaultFolderArr = Config::get('app_config.default_folder_arr');
            $defaultTagArr = Config::get('app_config.default_tag_arr');
            $defaultSourceArr = Config::get('app_config.default_source_arr');
            $defFolderName = Config::get('app_config.default_folder');
            $defTagName = Config::get('app_config.default_tag');
            $defPrintField = Config::get('app_config.default_print_field');
            $defAllottedAttachmentKbs = Config::get('app_config.default_allotted_attachment_kb');
            $defUserDeviceCount = Config::get('app_config.default_device_session_count');
            $emailSource = Config::get('app_config.source_email_text');
            $defAttachmentRetainDays = Config::get('app_config.default_attachment_retain_days');
            $defTimezoneId = Config::get('app_config.default_timezone_id');
            $defOffsetIsNegative = Config::get('app_config.default_offset_is_negative');
            $defOffsetHour = Config::get('app_config.default_offset_hour');
            $defOffsetMinute = Config::get('app_config.default_offset_minute');
            $defFolderIconCode = Config::get('app_config.default_folder_icon_code');
            $defFolderIsFavorited = Config::get('app_config.default_folder_is_favorited');

            if($isPremiumUser == 1)
            {
                $defAllottedAttachmentKbs = Config::get('app_config.premium_allotted_attachment_kb');
                $defUserDeviceCount = Config::get('app_config.premium_device_session_count');

                if($user->has_coupon == 1 && $user->premium_coupon_code_id > 0 )
                {
                    $premiumCouponCode = PremiumCouponCode::byId($user->premium_coupon_code_id)->first();
                    if(isset($premiumCouponCode))
                    {
                        $couponAllottedSpaceGbs = $premiumCouponCode->allotted_space_in_gb;
                        $defAllottedAttachmentKbs = round($couponAllottedSpaceGbs * 1024 * 1024);
                    }
                }
            }

            $defFolderId = 0;
            $defTagId = 0;
            $emailSourceId = 0;

            for($i=0; $i<count($defaultFolderArr); $i++)
            {     
                $name = $defaultFolderArr[$i];
                
                $userFolder = AppuserFolder::ofUser($userId)->byName($name)->first();
                if(!isset($userFolder))
                {
                    $userFolder = New AppuserFolder;
                    $userFolder->appuser_id = $userId;
                    $userFolder->folder_name = $name;
                    $userFolder->icon_code = $defFolderIconCode;
                    $userFolder->is_favorited = $defFolderIsFavorited;
                    $userFolder->save();
                }

                if($name == $defFolderName)
                {
                    $defFolderId = $userFolder->appuser_folder_id;
                }
            }

            for($i=0; $i<count($defaultTagArr); $i++)
            {     
                $name = $defaultTagArr[$i];
                
                $userTag = AppuserTag::ofUser($userId)->byName($name)->first();
                if(!isset($userTag))
                {
                    $userTag = New AppuserTag;
                    $userTag->appuser_id = $userId;
                    $userTag->tag_name = $name;
                    $userTag->save();
                }

                if($name == $defTagName)
                {
                    $defTagId = $userTag->appuser_tag_id;
                }
            }

            for($i=0; $i<count($defaultSourceArr); $i++)
            {     
                $name = $defaultSourceArr[$i];
                $userSource = AppuserSource::ofUser($userId)->byName($name)->first();
                if(!isset($userSource))
                {    
                    $userSource = New AppuserSource;
                    $userSource->appuser_id = $userId;
                    $userSource->source_name = $name;
                    $userSource->save();
                }

                if($name == $emailSource)
                {
                    $emailSourceId = $userSource->appuser_source_id;
                }
            }

            $userConstant = AppuserConstant::ofUser($userId)->first();
            if(!isset($userConstant))
            {
                $userConstant = New AppuserConstant;
                $userConstant->appuser_id = $userId;
                $userConstant->def_folder_id = $defFolderId;
                $userConstant->def_tag_id = $defTagId;
                $userConstant->email_source_id = $emailSourceId;
                $userConstant->passcode_enabled = 0;
                $userConstant->passcode = "";
                $userConstant->folder_passcode_enabled = 0;
                $userConstant->folder_passcode = "";
                $userConstant->folder_id_str = "";
                $userConstant->print_fields = $defPrintField;
                $userConstant->attachment_kb_allotted = $defAllottedAttachmentKbs;
                $userConstant->attachment_kb_available = $defAllottedAttachmentKbs;
                $userConstant->attachment_retain_days = $defAttachmentRetainDays;
                $userConstant->utc_offset_is_negative = $defOffsetIsNegative;
                $userConstant->utc_offset_hour = $defOffsetHour;
                $userConstant->utc_offset_minute = $defOffsetMinute;
                $userConstant->is_srac_share_enabled = 1;
                $userConstant->is_soc_share_enabled = 1;
                $userConstant->is_soc_facebook_enabled = 1;
                $userConstant->is_soc_twitter_enabled = 1;
                $userConstant->is_soc_linkedin_enabled = 1;
                $userConstant->is_soc_whatsapp_enabled = 1;
                $userConstant->is_soc_email_enabled = 1;
                $userConstant->is_soc_sms_enabled = 1;
                $userConstant->is_soc_other_enabled = 1;
                $userConstant->allowed_device_count = $defUserDeviceCount;
                $userConstant->save();

                if($isPremiumUser == 1)
                {
                    // MailClass::sendQuotaChangedMail($userId, $currAllottedKb);
                }
            }

            $userFullName = $user->fullname;
            $userEmail = $user->email;

            $sysVcInvites = SysVideoConferenceInvite::byEmail($userEmail)->get();
            if(isset($sysVcInvites) && count($sysVcInvites) > 0)
            {
                $currentTimestamp = CommonFunctionClass::getCreateTimestamp();
                
            	foreach ($sysVcInvites as $vcInvite) 
            	{
            		$videoConferenceId = $vcInvite->sys_vc_id;
            		$videoConference = $vcInvite->sysVideoConference;

                    if(isset($videoConference) && ($currentTimestamp < $videoConference->scheduled_start_ts || $currentTimestamp < $videoConference->scheduled_end_ts))
            		{
            			$participantId = $userId;
	                    $participantIsModerator = 0;
	                    $participantIsEmployee = 0;
	                    $existingParticipantContentId = 0;
	                    $isScheduled = 1;

	                    $this->addVideoConferenceParticipant($videoConferenceId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled); 

	                    $vcInvite->delete();
            		}
            	}
            }

            $orgVcInvites = OrganizationVideoConferenceInvite::byEmail($userEmail)->get();
            if(isset($orgVcInvites) && count($orgVcInvites) > 0)
            {
                $currentTimestamp = CommonFunctionClass::getCreateTimestamp();
                
            	foreach ($orgVcInvites as $vcInvite) 
            	{
            		$organizationId = $vcInvite->organization_id;
            		$videoConferenceId = $vcInvite->org_vc_id;
            		$organization = $vcInvite->organization;

            		if(isset($organization) && $organization->is_active == 1)
            		{
		            	$orgDepMgmtObj = New ContentDependencyManagementClass;
		                $orgDepMgmtObj->withOrgId($organizationId);
            			
            			$videoConference = $orgDepMgmtObj->getVideoConferenceObject($videoConferenceId, FALSE);

	                    if(isset($videoConference) && ($currentTimestamp < $videoConference->scheduled_start_ts || $currentTimestamp < $videoConference->scheduled_end_ts))
	            		{
							// $participantId = $userId;
							// $participantIsModerator = 0;
							// $participantIsEmployee = 1;
							// $existingParticipantContentId = 0;
							// $isScheduled = 1;

	            			$creatorOrgEmpId = $videoConference->creator_employee_id;
		                	$orgDepMgmtObj->withOrgIdAndEmpId($organizationId, $creatorOrgEmpId);
                            $orgDepMgmtObj->onVideoConferenceParticipantInvited($videoConferenceId, $videoConference, $userFullName, $userEmail);

		                    // $orgDepMgmtObj->addVideoConferenceParticipant($videoConferenceId, $videoConference, $participantId, $participantIsModerator, $participantIsEmployee, $existingParticipantContentId, $isScheduled); 
	                    	
	                    	$vcInvite->delete();
	            		}
            		}
            	}
            }
        }
    }
	
	public function getAllCloudStorageTypeListForUser()
	{
		$i = 0;
	    $cloudStorageTypeList = array();
	    $cloudStorageTypes = CloudStorageType::get();
	    foreach ($cloudStorageTypes as $cloudStorageType) 
	    {
            // if($i == 0 || $i == 1)
            {
                $cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;
                $cloudStorageTypeIconUrl = OrganizationClass::getCloudStorageIconAssetUrl($cloudStorageType->cloud_storage_icon_url);
                $cloudStorageTypeIsLinked = $this->getAppuserHasCloudStorageTypeLinked($cloudStorageTypeId);
                
                $cloudStorageTypeList[$i]['id'] = $cloudStorageTypeId;
                $cloudStorageTypeList[$i]['name'] = $cloudStorageType->cloud_storage_type_name;
                $cloudStorageTypeList[$i]['code'] = $cloudStorageType->cloud_storage_type_code;
                $cloudStorageTypeList[$i]['iconUrl'] = $cloudStorageTypeIconUrl;
                $cloudStorageTypeList[$i]['isLinked'] = $cloudStorageTypeIsLinked;
                $i++;
            }
	    }
		
		return $cloudStorageTypeList;
	}
	
	public function getCloudStorageTypeObjectByCode($typeCode)
	{
		$cloudStorageType = NULL;
			
		if(isset($typeCode) && $typeCode != "")
		{	
        	$cloudStorageType = CloudStorageType::byCode($typeCode)->first();
		}
		
		return $cloudStorageType;
	}
	
	public function getCloudStorageTypeObjectById($typeId)
	{
		$cloudStorageType = NULL;
			
		if(isset($typeId) && $typeId > 0)
		{	
        	$cloudStorageType = CloudStorageType::byId($typeId)->first();
		}
		
		return $cloudStorageType;
	}
	
	public function getCloudStorageTypeIdFromCode($typeCode)
	{
		$cloudStorageTypeId = 0;
		$cloudStorageType = $this->getCloudStorageTypeObjectByCode($typeCode);
		if(isset($cloudStorageType))
		{	
        	$cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;
		}
		
		return $cloudStorageTypeId;
	}

	public function getAppuserHasCloudStorageTypeLinked($cloudStorageTypeId)
	{
		$cloudStorageTypeIsLinked = 0;
		if($cloudStorageTypeId > 0)
		{
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();
        	if(isset($appuserCloudStorageToken))
        	{
        		$cloudStorageTypeIsLinked = 1;
        	}
		}
		return $cloudStorageTypeIsLinked;
	}

	public function getAppuserAccessTokenForStorageType($cloudStorageTypeId)
	{
		$accessToken = "";
		if($cloudStorageTypeId > 0)
		{
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();
        	if(isset($appuserCloudStorageToken))
        	{
        		$accessToken = $appuserCloudStorageToken->access_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserRefreshTokenForStorageType($cloudStorageTypeId)
	{
		$accessToken = "";
		if($cloudStorageTypeId > 0)
		{
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();
        	if(isset($appuserCloudStorageToken))
        	{
        		$accessToken = $appuserCloudStorageToken->refresh_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserMappedTokenDetailsForStorageType($cloudStorageTypeId)
	{
		$appuserCloudStorageToken = NULL;
		if($cloudStorageTypeId > 0)
		{
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();
		}
		return $appuserCloudStorageToken;
	}

	public function saveAppuserAccessTokenForStorageType($sessionTypeId, $cloudStorageTypeId, $accessToken, $refreshToken)
	{
		$isLinked = 0;
		if($cloudStorageTypeId > 0 && $accessToken != "")
		{
    		$cloudStorageType = $this->getCloudStorageTypeObjectById($cloudStorageTypeId);
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();

        	$isAdd = FALSE;

        	if(!isset($appuserCloudStorageToken))
        	{
        		$isAdd = TRUE;

        		$appuserCloudStorageToken = New AppuserCloudStorageToken;
        		$appuserCloudStorageToken->appuser_id = $this->userId;
        		$appuserCloudStorageToken->cloud_storage_type_id = $cloudStorageTypeId;
        	}

    		$cloudStorageTypeCodeGoogle = CloudStorageType::$GOOGLE_DRIVE_TYPE_CODE;
    		$cloudStorageTypeCodeOneDrive = CloudStorageType::$ONEDRIVE_TYPE_CODE;
        	$tokenRefreshDueTs = NULL;
        	if($cloudStorageType->cloud_storage_type_code == $cloudStorageTypeCodeGoogle || $cloudStorageType->cloud_storage_type_code == $cloudStorageTypeCodeOneDrive)
        	{
				$utcTz =  'UTC';
				$utcToday = Carbon::now($utcTz);

				$consRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
				$consRefreshDt = $consRefreshDt->addMinutes(59);	
				$consRefreshTs = $consRefreshDt->timestamp;

        		$tokenRefreshDueTs = $consRefreshTs * 1000;
        	}
    		
    		$appuserCloudStorageToken->access_token = $accessToken;
    		$appuserCloudStorageToken->refresh_token = $refreshToken;
    		$appuserCloudStorageToken->token_refresh_due_ts = $tokenRefreshDueTs;
    		$appuserCloudStorageToken->session_type_id = $sessionTypeId;
    		$appuserCloudStorageToken->save();

    		if($isAdd)
    		{
    			MailClass::sendCloudStorageTypeAccountLinkedMail($this->userId, $cloudStorageType);
    		}

    		$isLinked = 1;
		}
		return $isLinked;
	}

	public function removeAppuserAccessTokenForStorageType($cloudStorageTypeId)//, $accessToken)
	{
		$isLinked = 0;
		if($cloudStorageTypeId > 0)// && $accessToken != "")
		{
        	$appuserCloudStorageToken = AppuserCloudStorageToken::ofUserAndCloudStorageType($this->userId, $cloudStorageTypeId)->first();//->ofAccessToken($accessToken)
        	if(isset($appuserCloudStorageToken))
        	{
    			$appuserCloudStorageToken->delete();

	    		$cloudStorageType = $this->getCloudStorageTypeObjectById($cloudStorageTypeId);
	    		MailClass::sendCloudStorageTypeAccountUnLinkedMail($this->userId, $cloudStorageType);
        	}
		}
		return $isLinked;
	}

	public function fetchAndRefreshAppuserCloudStorageAccessToken($cloudStorageTypeCode, $userSession = NULL)
	{
		$refreshTokenResponse = NULL;

        $cloudStorageTypeId = $this->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
        if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
        {
        	$cloudStorageMappedTokenDetails = $this->getAppuserMappedTokenDetailsForStorageType($cloudStorageTypeId);
        	if(isset($cloudStorageMappedTokenDetails))
        	{
        		$accessToken = $cloudStorageMappedTokenDetails->access_token;
	            $refreshToken = $cloudStorageMappedTokenDetails->refresh_token;
	            $sessionTypeId = $cloudStorageMappedTokenDetails->session_type_id;
	            
	            if(isset($accessToken) && trim($accessToken) != "" && isset($refreshToken) && trim($refreshToken) != "")
	            {
					$attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
			        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);

			        $consClientId = env('GOOGLE_CLIENT_ID');
			        $consClientSecret = env('GOOGLE_CLIENT_SECRET');
			        
			        $sessModelObj = New SessionType;
			        $iosTypeId = $sessModelObj->IOS_SESSION_TYPE_ID;
			        $androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;

			        $userSessionTypeId = $sessionTypeId;//isset($userSession) ? $userSession->session_type_id : $sessionTypeId;

                    if($cloudStorageTypeCode == CloudStorageType::$GOOGLE_DRIVE_TYPE_CODE)
                    {
                    	$consClientId = env('GOOGLE_CLIENT_ID');
	                    $consClientSecret = env('GOOGLE_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('GOOGLE_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }
                    else if($cloudStorageTypeCode == CloudStorageType::$ONEDRIVE_TYPE_CODE)
                    {
                    	$consClientId = env('MICROSOFT_CLIENT_ID');
	                    $consClientSecret = env('MICROSOFT_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
	                    else if($userSessionTypeId == $androidTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_ANDROID_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }                 
			        
			        $refreshTokenResponse = $attCldStrgMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);

			        if(isset($refreshTokenResponse) && isset($refreshTokenResponse->access_token) && $refreshTokenResponse->access_token != "")
			        {
			            $updAccessToken = $accessToken;
			            if($refreshTokenResponse->access_token != $accessToken)
			            {
			                $updAccessToken = $refreshTokenResponse->access_token;
			            }
			            
			            $updRefreshToken = $refreshToken;
			            if(isset($refreshTokenResponse->refresh_token) && $refreshTokenResponse->refresh_token != "" && $refreshTokenResponse->refresh_token != $refreshToken)
			            {
			                $updRefreshToken = $refreshTokenResponse->refresh_token;
			            }
			            
			            $this->saveAppuserAccessTokenForStorageType($sessionTypeId, $cloudStorageTypeId, $updAccessToken, $updRefreshToken);

						if($cloudStorageTypeCode == CloudStorageType::$GOOGLE_DRIVE_TYPE_CODE)
	                    {
					    	$consCloudCalendarTypeCode = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
					    }
						else if($cloudStorageTypeCode == CloudStorageType::$ONEDRIVE_TYPE_CODE)
	                    {
					    	$consCloudCalendarTypeCode = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;
					    }

					    if(isset($consCloudCalendarTypeCode))
					    {
					    	$cloudCalendarType = CloudCalendarType::byCode($consCloudCalendarTypeCode)->first();

					    	if(isset($cloudCalendarType))
					    	{
					        	$consCloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;
        						$cloudCalendarMappedTokenDetails = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $consCloudCalendarTypeId)->ofAccessToken($accessToken)->first();
        						if(isset($cloudCalendarMappedTokenDetails))
        						{
					                $autoSyncEnabled = $cloudCalendarMappedTokenDetails->auto_sync_enabled;
					                $syncWithOrganizationId = $cloudCalendarMappedTokenDetails->sync_with_organization_id;
					                $syncWithOrganizationEmployeeId = $cloudCalendarMappedTokenDetails->sync_with_organization_employee_id;

                        			$this->saveAppuserAccessTokenForCalendarType($sessionTypeId, $consCloudCalendarTypeId, $updAccessToken, $updRefreshToken, $autoSyncEnabled, $syncWithOrganizationId, $syncWithOrganizationEmployeeId);
        						}
					        }
					    }
			        }
			    }
        	}	            
	    }

	    return $refreshTokenResponse;
	}

	public function checkAppuserCloudStorageAccessTokenValidity($cloudStorageTypeCode, $userSession = NULL)
	{
		$isLinked = 0;

        $cloudStorageTypeId = $this->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
        if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
        {
        	$cloudStorageMappedTokenDetails = $this->getAppuserMappedTokenDetailsForStorageType($cloudStorageTypeId);
        	if(isset($cloudStorageMappedTokenDetails))
        	{
        		$accessToken = $cloudStorageMappedTokenDetails->access_token;
	            $sessionTypeId = $cloudStorageMappedTokenDetails->session_type_id;
	            
	            if(isset($accessToken) && trim($accessToken) != "")
	            {
					$attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
			        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);        
			        
			        $checkTokenResponse = $attCldStrgMgmtObj->checkAccessTokenValidity();

			        if(isset($checkTokenResponse) && isset($checkTokenResponse['isTokenValid']))
			        {
			           $isLinked = $checkTokenResponse['isTokenValid'];
			        }
			    }
        	}	            
	    }

	    return $isLinked;
	}
	
	public function getAllCloudCalendarTypeListForUser()
	{
		$i = 0;
	    $cloudCalendarTypeList = array();
	    $cloudCalendarTypes = CloudCalendarType::get();
	    foreach ($cloudCalendarTypes as $cloudCalendarType) 
	    {
            if($i == 0 || $i == 1)
            {
                $cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;
                $cloudCalendarTypeIconUrl = OrganizationClass::getCloudCalendarIconAssetUrl($cloudCalendarType->cloud_calendar_icon_url);
                $cloudCalendarTypeIsLinked = $this->getAppuserHasCloudCalendarTypeLinked($cloudCalendarTypeId);
                
                $cloudCalendarTypeList[$i]['id'] = $cloudCalendarTypeId;
                $cloudCalendarTypeList[$i]['name'] = $cloudCalendarType->cloud_calendar_type_name;
                $cloudCalendarTypeList[$i]['code'] = $cloudCalendarType->cloud_calendar_type_code;
                $cloudCalendarTypeList[$i]['iconUrl'] = $cloudCalendarTypeIconUrl;
                $cloudCalendarTypeList[$i]['isLinked'] = $cloudCalendarTypeIsLinked;
                $i++;
            }
	    }
		
		return $cloudCalendarTypeList;
	}
	
	public function getCloudCalendarTypeObjectByCode($typeCode)
	{
		$cloudCalendarType = NULL;
			
		if(isset($typeCode) && $typeCode != "")
		{	
        	$cloudCalendarType = CloudCalendarType::byCode($typeCode)->first();
		}
		
		return $cloudCalendarType;
	}
	
	public function getCloudCalendarTypeObjectById($typeId)
	{
		$cloudCalendarType = NULL;
			
		if(isset($typeId) && $typeId > 0)
		{	
        	$cloudCalendarType = CloudCalendarType::byId($typeId)->first();
		}
		
		return $cloudCalendarType;
	}
	
	public function getCloudCalendarTypeIdFromCode($typeCode)
	{
		$cloudCalendarTypeId = 0;
		$cloudCalendarType = $this->getCloudCalendarTypeObjectByCode($typeCode);
		if(isset($cloudCalendarType))
		{	
        	$cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;
		}
		
		return $cloudCalendarTypeId;
	}

	public function getAppuserHasCloudCalendarTypeLinked($cloudCalendarTypeId)
	{
		$cloudCalendarTypeIsLinked = 0;
		if($cloudCalendarTypeId > 0)
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();
        	if(isset($appuserCloudCalendarToken))
        	{
        		$cloudCalendarTypeIsLinked = 1;
        	}
		}
		return $cloudCalendarTypeIsLinked;
	}

	public function getAppuserLinkedCloudCalendarTypeMapping()
	{
	    $linkedCloudCalendarTypeList = array();
	    $cloudCalendarTypes = CloudCalendarType::get();
	    foreach ($cloudCalendarTypes as $cloudCalendarType) 
	    {
            $cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;
            $cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;
            $cloudCalendarTypeCode = $cloudCalendarType->cloud_calendar_type_code;

			$cloudCalendarTypeIsLinked = $this->getAppuserHasCloudCalendarTypeLinked($cloudCalendarTypeId);
			if($cloudCalendarTypeIsLinked == 1)
			{
				$appuserCloudCalendarToken = $this->getAppuserMappedTokenDetailsForCalendarType($cloudCalendarTypeId);

				if(isset($appuserCloudCalendarToken))
				{
		            $accessToken = $appuserCloudCalendarToken->access_token;
		            $calendarIdArrStr = $appuserCloudCalendarToken->calendar_id_arr_str;
		            $calendarIsAutoSyncEnabled = $appuserCloudCalendarToken->auto_sync_enabled;
		            $syncWithOrganizationId = $appuserCloudCalendarToken->sync_with_organization_id;
		            $syncWithOrganizationEmployeeId = $appuserCloudCalendarToken->sync_with_organization_employee_id;

            		$calendarIdArr = [ $calendarIdArrStr ];//explode(",", $calendarIdArrStr);

					$linkedCloudCalendarTypeDetails = array();
					$linkedCloudCalendarTypeDetails['cloudCalendarTypeId'] = $cloudCalendarTypeId;
					$linkedCloudCalendarTypeDetails['cloudCalendarTypeName'] = $cloudCalendarTypeName;
					$linkedCloudCalendarTypeDetails['cloudCalendarTypeCode'] = $cloudCalendarTypeCode;
					$linkedCloudCalendarTypeDetails['cloudCalendarIdArr'] = $calendarIdArr;
					$linkedCloudCalendarTypeDetails['accessToken'] = $accessToken;
					$linkedCloudCalendarTypeDetails['isAutoSyncEnabled'] = $calendarIsAutoSyncEnabled;
					$linkedCloudCalendarTypeDetails['syncWithOrganizationId'] = $syncWithOrganizationId;
					$linkedCloudCalendarTypeDetails['syncWithOrganizationEmployeeId'] = $syncWithOrganizationEmployeeId;

					array_push($linkedCloudCalendarTypeList, $linkedCloudCalendarTypeDetails);
				}
			}
		}
		return $linkedCloudCalendarTypeList;
	}

	public function getAppuserAccessTokenForCalendarType($cloudCalendarTypeId)
	{
		$accessToken = "";
		if($cloudCalendarTypeId > 0)
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();
        	if(isset($appuserCloudCalendarToken))
        	{
        		$accessToken = $appuserCloudCalendarToken->access_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserRefreshTokenForCalendarType($cloudCalendarTypeId)
	{
		$accessToken = "";
		if($cloudCalendarTypeId > 0)
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();
        	if(isset($appuserCloudCalendarToken))
        	{
        		$accessToken = $appuserCloudCalendarToken->refresh_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserMappedTokenDetailsForCalendarType($cloudCalendarTypeId)
	{
		$appuserCloudCalendarToken = NULL;
		if($cloudCalendarTypeId > 0)
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();
		}
		return $appuserCloudCalendarToken;
	}

	public function saveAppuserAccessTokenForCalendarType($sessionTypeId, $cloudCalendarTypeId, $accessToken, $refreshToken, $autoSyncEnabled = 0, $syncWithOrganizationId = 0, $syncWithOrganizationEmployeeId = 0)
	{
		$isLinked = 0;
		if($cloudCalendarTypeId > 0 && $accessToken != "")
		{
    		$cloudCalendarType = $this->getCloudCalendarTypeObjectById($cloudCalendarTypeId);
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();

        	$cloudCalendarTypeCode = $cloudCalendarType->cloud_calendar_type_code;

        	$isAdd = FALSE;

        	if(!isset($appuserCloudCalendarToken))
        	{
        		$isAdd = TRUE;

        		$appuserCloudCalendarToken = New AppuserCloudCalendarToken;
        		$appuserCloudCalendarToken->appuser_id = $this->userId;
        		$appuserCloudCalendarToken->cloud_calendar_type_id = $cloudCalendarTypeId;
        	}

    		$cloudCalendarTypeCodeGoogle = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
    		$cloudCalendarTypeCodeMicrosoft = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;

    		$consCalendarId = '';

			$utcTz =  'UTC';
			$utcToday = Carbon::now($utcTz);

			$consRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
			$consRefreshDt = $consRefreshDt->addMinutes(59);	
			$consRefreshTs = $consRefreshDt->timestamp;

        	$tokenRefreshDueTs = NULL;
        	if($cloudCalendarTypeCode == $cloudCalendarTypeCodeGoogle)
        	{
        		$tokenRefreshDueTs = $consRefreshTs * 1000;

        		$consCalendarId = 'primary';
        	}
        	else if($cloudCalendarTypeCode == $cloudCalendarTypeCodeMicrosoft)
        	{
        		$tokenRefreshDueTs = $consRefreshTs * 1000;
        		
                $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);

				$calendarListResponse = $cldCalendarMgmtObj->getAllCalendars("");
				if(isset($calendarListResponse))
				{
					$calendarList = $calendarListResponse['calendarList'];
					foreach ($calendarList as $calendarObj) {
						if($calendarObj['isPrimary'] == 1)
						{
							$consCalendarId = $calendarObj['calendarId'];
							break;
						}
					}
				}
        	}

        	if($autoSyncEnabled == 0)
        	{
        		$syncWithOrganizationId = 0;
        		$syncWithOrganizationEmployeeId = 0;
        	}
    		
    		$appuserCloudCalendarToken->access_token = $accessToken;
    		$appuserCloudCalendarToken->refresh_token = $refreshToken;
    		$appuserCloudCalendarToken->session_type_id = $sessionTypeId;
    		$appuserCloudCalendarToken->auto_sync_enabled = $autoSyncEnabled;
    		$appuserCloudCalendarToken->sync_with_organization_id = $syncWithOrganizationId;
    		$appuserCloudCalendarToken->sync_with_organization_employee_id = $syncWithOrganizationEmployeeId;
    		$appuserCloudCalendarToken->token_refresh_due_ts = $tokenRefreshDueTs;
    		$appuserCloudCalendarToken->calendar_id_arr_str = $consCalendarId;

    		if($isAdd)
    		{
    			$appuserCloudCalendarToken->sync_token = NULL;
	    		$appuserCloudCalendarToken->last_sync_performed_at = NULL;
	    		$appuserCloudCalendarToken->next_sync_due_at = NULL;
	    	}

    		$appuserCloudCalendarToken->save();

    		if($isAdd)
    		{
    			MailClass::sendCloudCalendarTypeAccountLinkedMail($this->userId, $cloudCalendarType);
    		}

    		$isLinked = 1;
		}
		return $isLinked;
	}

	public function removeAppuserAccessTokenForCalendarType($cloudCalendarTypeId)//, $accessToken)
	{
		$isLinked = 0;
		if($cloudCalendarTypeId > 0)// && $accessToken != "")
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();//->ofAccessToken($accessToken)
        	if(isset($appuserCloudCalendarToken))
        	{
    			$appuserCloudCalendarToken->delete();

	    		$cloudCalendarType = $this->getCloudCalendarTypeObjectById($cloudCalendarTypeId);
	    		MailClass::sendCloudCalendarTypeAccountUnLinkedMail($this->userId, $cloudCalendarType);
        	}
		}
		return $isLinked;
	}

	public function setupAppuserCalendarIdSelectionForCalendarType($cloudCalendarTypeId, $calendarIdStr)
	{
		if($cloudCalendarTypeId > 0 && $calendarIdStr != "")
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();//->ofAccessToken($accessToken)
        	if(isset($appuserCloudCalendarToken))
        	{
        		$appuserCloudCalendarToken->calendar_id_arr_str = $calendarIdStr;
    			$appuserCloudCalendarToken->save();
        	}
		}
	}

	public function setupAppuserCalendarSyncTokenDetailsForCalendarType($cloudCalendarTypeId, $syncToken)
	{
		if($cloudCalendarTypeId > 0)// && $syncToken != "")
		{
        	$appuserCloudCalendarToken = AppuserCloudCalendarToken::ofUserAndCloudCalendarType($this->userId, $cloudCalendarTypeId)->first();
        	if(isset($appuserCloudCalendarToken) && $appuserCloudCalendarToken->auto_sync_enabled == 1)
        	{
				$currTs = CommonFunctionClass::getCreateTimestamp();

	        	$nextSyncTs = NULL;

				$utcTz =  'UTC';
				$utcToday = Carbon::now($utcTz);

				$consRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
				$consRefreshDt = $consRefreshDt->addMinutes(5);	
				$consRefreshTs = $consRefreshDt->timestamp;

        		$nextSyncTs = $consRefreshTs * 1000;

        		// if(isset($syncToken) && $syncToken != "")
        		{
        			$appuserCloudCalendarToken->sync_token = $syncToken;
	        		$appuserCloudCalendarToken->last_sync_performed_at = $currTs;
	        	}

        		$appuserCloudCalendarToken->next_sync_due_at = $nextSyncTs;
    			$appuserCloudCalendarToken->save();
        	}
		}
	}

	public function fetchAndRefreshAppuserCloudCalendarAccessToken($cloudCalendarTypeCode, $userSession = NULL)
	{
		$refreshTokenResponse = NULL;

        $cloudCalendarTypeId = $this->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
        if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
        {
        	$cloudCalendarMappedTokenDetails = $this->getAppuserMappedTokenDetailsForCalendarType($cloudCalendarTypeId);
            if(isset($cloudCalendarMappedTokenDetails))
            {
                $accessToken = $cloudCalendarMappedTokenDetails->access_token;
                $refreshToken = $cloudCalendarMappedTokenDetails->refresh_token;
                $sessionTypeId = $cloudCalendarMappedTokenDetails->session_type_id;
                $autoSyncEnabled = $cloudCalendarMappedTokenDetails->auto_sync_enabled;
                $syncWithOrganizationId = $cloudCalendarMappedTokenDetails->sync_with_organization_id;
                $syncWithOrganizationEmployeeId = $cloudCalendarMappedTokenDetails->sync_with_organization_employee_id;

                if(isset($accessToken) && trim($accessToken) != "" && isset($refreshToken) && trim($refreshToken) != "")
                {
                    $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                    $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                    
                    $sessModelObj = New SessionType;
                    $iosTypeId = $sessModelObj->IOS_SESSION_TYPE_ID;
			        $androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
                    
                    $userSessionTypeId = $sessionTypeId;//isset($userSession) ? $userSession->session_type_id : $sessionTypeId;

                    if($cloudCalendarTypeCode == CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE)
                    {
                    	$consClientId = env('GOOGLE_CLIENT_ID');
	                    $consClientSecret = env('GOOGLE_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('GOOGLE_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }
                    else if($cloudCalendarTypeCode == CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE)
                    {
                    	$consClientId = env('MICROSOFT_CLIENT_ID');
	                    $consClientSecret = env('MICROSOFT_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
	                    else if($userSessionTypeId == $androidTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_ANDROID_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }
	                                           
                    
                    $refreshTokenResponse = $cldCalendarMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
                    
                    if(isset($refreshTokenResponse) && isset($refreshTokenResponse->access_token) && $refreshTokenResponse->access_token != "")
                    {
                        $updAccessToken = $accessToken;
                        if($refreshTokenResponse->access_token != $accessToken)
                        {
                            $updAccessToken = $refreshTokenResponse->access_token;
                        }
                        
                        $updRefreshToken = $refreshToken;
                        if(isset($refreshTokenResponse->refresh_token) && $refreshTokenResponse->refresh_token != "" && $refreshTokenResponse->refresh_token != $refreshToken)
                        {
                            $updRefreshToken = $refreshTokenResponse->refresh_token;
                        }
                        
                        $this->saveAppuserAccessTokenForCalendarType($sessionTypeId, $cloudCalendarTypeId, $updAccessToken, $updRefreshToken, $autoSyncEnabled, $syncWithOrganizationId, $syncWithOrganizationEmployeeId);
                    }

                    // $refreshTokenResponse->consClientId = $consClientId;
                    // $refreshTokenResponse->consClientSecret = $consClientSecret;
                }
            }
	    }

	    return $refreshTokenResponse;
	}

	public function checkAppuserCloudCalendarAccessTokenValidity($cloudCalendarTypeCode, $userSession = NULL)
	{
		$isLinked = 0;

        $cloudCalendarTypeId = $this->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
        if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
        {
        	$cloudCalendarMappedTokenDetails = $this->getAppuserMappedTokenDetailsForCalendarType($cloudCalendarTypeId);
        	if(isset($cloudCalendarMappedTokenDetails))
        	{
        		$accessToken = $cloudCalendarMappedTokenDetails->access_token;
	            $sessionTypeId = $cloudCalendarMappedTokenDetails->session_type_id;
	            
	            if(isset($accessToken) && trim($accessToken) != "")
	            {
					$cldCalendarMgmtObj = New CloudCalendarManagementClass;
			        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);        
			        
			        $checkTokenResponse = $cldCalendarMgmtObj->checkAccessTokenValidity();

			        if(isset($checkTokenResponse) && isset($checkTokenResponse['isTokenValid']))
			        {
			           $isLinked = $checkTokenResponse['isTokenValid'];
			        }
			    }
        	}	            
	    }

	    return $isLinked;
	}

	
	
	public function getAllCloudMailBoxTypeListForUser()
	{
		$i = 0;
	    $cloudMailBoxTypeList = array();
	    $cloudMailBoxTypes = CloudMailBoxType::get();
	    foreach ($cloudMailBoxTypes as $cloudMailBoxType) 
	    {
            if($i == 0 || $i == 1)
            {
                $cloudMailBoxTypeId = $cloudMailBoxType->cloud_mail_box_type_id;
                $cloudMailBoxTypeIconUrl = OrganizationClass::getCloudMailBoxIconAssetUrl($cloudMailBoxType->cloud_mailBox_icon_url);
                $cloudMailBoxTypeIsLinked = $this->getAppuserHasCloudMailBoxTypeLinked($cloudMailBoxTypeId);
                
                $cloudMailBoxTypeList[$i]['id'] = $cloudMailBoxTypeId;
                $cloudMailBoxTypeList[$i]['name'] = $cloudMailBoxType->cloud_mail_box_type_name;
                $cloudMailBoxTypeList[$i]['code'] = $cloudMailBoxType->cloud_mail_box_type_code;
                $cloudMailBoxTypeList[$i]['iconUrl'] = $cloudMailBoxTypeIconUrl;
                $cloudMailBoxTypeList[$i]['isLinked'] = $cloudMailBoxTypeIsLinked;
                $i++;
            }
	    }
		
		return $cloudMailBoxTypeList;
	}
	
	public function getCloudMailBoxTypeObjectByCode($typeCode)
	{
		$cloudMailBoxType = NULL;
			
		if(isset($typeCode) && $typeCode != "")
		{	
        	$cloudMailBoxType = CloudMailBoxType::byCode($typeCode)->first();
		}
		
		return $cloudMailBoxType;
	}
	
	public function getCloudMailBoxTypeObjectById($typeId)
	{
		$cloudMailBoxType = NULL;
			
		if(isset($typeId) && $typeId > 0)
		{	
        	$cloudMailBoxType = CloudMailBoxType::byId($typeId)->first();
		}
		
		return $cloudMailBoxType;
	}
	
	public function getCloudMailBoxTypeIdFromCode($typeCode)
	{
		$cloudMailBoxTypeId = 0;
		$cloudMailBoxType = $this->getCloudMailBoxTypeObjectByCode($typeCode);
		if(isset($cloudMailBoxType))
		{	
        	$cloudMailBoxTypeId = $cloudMailBoxType->cloud_mail_box_type_id;
		}
		
		return $cloudMailBoxTypeId;
	}

	public function getAppuserHasCloudMailBoxTypeLinked($cloudMailBoxTypeId)
	{
		$cloudMailBoxTypeIsLinked = 0;
		if($cloudMailBoxTypeId > 0)
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();
        	if(isset($appuserCloudMailBoxToken))
        	{
        		$cloudMailBoxTypeIsLinked = 1;
        	}
		}
		return $cloudMailBoxTypeIsLinked;
	}

	public function getAppuserLinkedCloudMailBoxTypeMapping()
	{
	    $linkedCloudMailBoxTypeList = array();
	    $cloudMailBoxTypes = CloudMailBoxType::get();
	    foreach ($cloudMailBoxTypes as $cloudMailBoxType) 
	    {
            $cloudMailBoxTypeId = $cloudMailBoxType->cloud_mail_box_type_id;
            $cloudMailBoxTypeName = $cloudMailBoxType->cloud_mail_box_type_name;
            $cloudMailBoxTypeCode = $cloudMailBoxType->cloud_mail_box_type_code;

			$cloudMailBoxTypeIsLinked = $this->getAppuserHasCloudMailBoxTypeLinked($cloudMailBoxTypeId);
			if($cloudMailBoxTypeIsLinked == 1)
			{
				$appuserCloudMailBoxToken = $this->getAppuserMappedTokenDetailsForMailBoxType($cloudMailBoxTypeId);

				if(isset($appuserCloudMailBoxToken))
				{
            		$cloudMailBoxId = $appuserCloudMailBoxToken->cloud_mail_box_id;
		            $accessToken = $appuserCloudMailBoxToken->access_token;

					$linkedCloudMailBoxTypeDetails = array();
					$linkedCloudMailBoxTypeDetails['cloudMailBoxTypeId'] = $cloudMailBoxTypeId;
					$linkedCloudMailBoxTypeDetails['cloudMailBoxTypeName'] = $cloudMailBoxTypeName;
					$linkedCloudMailBoxTypeDetails['cloudMailBoxTypeCode'] = $cloudMailBoxTypeCode;
					$linkedCloudMailBoxTypeDetails['cloudMailBoxId'] = $cloudMailBoxId;
					$linkedCloudMailBoxTypeDetails['accessToken'] = $accessToken;

					array_push($linkedCloudMailBoxTypeList, $linkedCloudMailBoxTypeDetails);
				}
			}
		}
		return $linkedCloudMailBoxTypeList;
	}

	public function getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId)
	{
		$accessToken = "";
		if($cloudMailBoxTypeId > 0)
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();
        	if(isset($appuserCloudMailBoxToken))
        	{
        		$accessToken = $appuserCloudMailBoxToken->access_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserRefreshTokenForMailBoxType($cloudMailBoxTypeId)
	{
		$accessToken = "";
		if($cloudMailBoxTypeId > 0)
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();
        	if(isset($appuserCloudMailBoxToken))
        	{
        		$accessToken = $appuserCloudMailBoxToken->refresh_token;
        	}
		}
		return $accessToken;
	}

	public function getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId)
	{
		$mailBoxId = "";
		if($cloudMailBoxTypeId > 0)
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();
        	if(isset($appuserCloudMailBoxToken))
        	{
        		$mailBoxId = $appuserCloudMailBoxToken->cloud_mail_box_id;
        	}
		}
		return $mailBoxId;
	}

	public function getAppuserMappedTokenDetailsForMailBoxType($cloudMailBoxTypeId)
	{
		$appuserCloudMailBoxToken = NULL;
		if($cloudMailBoxTypeId > 0)
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();
		}
		return $appuserCloudMailBoxToken;
	}

	public function saveAppuserAccessTokenForMailBoxType($sessionTypeId, $cloudMailBoxTypeId, $accessToken, $refreshToken, $mailBoxId)
	{
		$isLinked = 0;
		if($cloudMailBoxTypeId > 0 && $accessToken != "")
		{
    		$cloudMailBoxType = $this->getCloudMailBoxTypeObjectById($cloudMailBoxTypeId);
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();

        	$cloudMailBoxTypeCode = $cloudMailBoxType->cloud_mail_box_type_code;

        	$isAdd = FALSE;

        	if(!isset($appuserCloudMailBoxToken))
        	{
        		$isAdd = TRUE;

        		$appuserCloudMailBoxToken = New AppuserCloudMailBoxToken;
        		$appuserCloudMailBoxToken->appuser_id = $this->userId;
        		$appuserCloudMailBoxToken->cloud_mail_box_type_id = $cloudMailBoxTypeId;
        	}

    		$cloudMailBoxTypeCodeGoogle = CloudMailBoxType::$GOOGLE_MAILBOX_TYPE_CODE;
    		$cloudMailBoxTypeCodeMicrosoft = CloudMailBoxType::$MICROSOFT_MAILBOX_TYPE_CODE;

    		$consMailBoxId = '';

        	$tokenRefreshDueTs = NULL;
        	if($cloudMailBoxTypeCode == $cloudMailBoxTypeCodeGoogle)
        	{
				$utcTz =  'UTC';
				$utcToday = Carbon::now($utcTz);

				$consRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
				$consRefreshDt = $consRefreshDt->addMinutes(59);	
				$consRefreshTs = $consRefreshDt->timestamp;

        		$tokenRefreshDueTs = $consRefreshTs * 1000;

        		$consMailBoxId = 'primary';
        	}
        	else if($cloudMailBoxTypeCode == $cloudMailBoxTypeCodeMicrosoft)
        	{
                $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);

				$mailBoxListResponse = $cldMailBoxMgmtObj->getAllMailBoxs("");
				if(isset($mailBoxListResponse))
				{
					$mailBoxList = $mailBoxListResponse['mailBoxList'];
					foreach ($mailBoxList as $mailBoxObj) {
						if($mailBoxObj['isPrimary'] == 1)
						{
							$consMailBoxId = $mailBoxObj['mailBoxId'];
							break;
						}
					}
				}
        	}
    		
    		$appuserCloudMailBoxToken->access_token = $accessToken;
    		$appuserCloudMailBoxToken->cloud_mail_box_id = $mailBoxId;
    		$appuserCloudMailBoxToken->refresh_token = $refreshToken;
    		$appuserCloudMailBoxToken->session_type_id = $sessionTypeId;
    		$appuserCloudMailBoxToken->token_refresh_due_ts = $tokenRefreshDueTs;
    		$appuserCloudMailBoxToken->save();

    		if($isAdd)
    		{
    			MailClass::sendCloudMailBoxTypeAccountLinkedMail($this->userId, $cloudMailBoxType);
    		}

    		$isLinked = 1;
		}
		return $isLinked;
	}

	public function removeAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId)//, $accessToken)
	{
		$isLinked = 0;
		if($cloudMailBoxTypeId > 0)// && $accessToken != "")
		{
        	$appuserCloudMailBoxToken = AppuserCloudMailBoxToken::ofUserAndCloudMailBoxType($this->userId, $cloudMailBoxTypeId)->first();//->ofAccessToken($accessToken)
        	if(isset($appuserCloudMailBoxToken))
        	{
    			$appuserCloudMailBoxToken->delete();

	    		$cloudMailBoxType = $this->getCloudMailBoxTypeObjectById($cloudMailBoxTypeId);
	    		MailClass::sendCloudMailBoxTypeAccountUnLinkedMail($this->userId, $cloudMailBoxType);
        	}
		}
		return $isLinked;
	}

	public function fetchAndRefreshAppuserCloudMailBoxAccessToken($cloudMailBoxTypeCode, $userSession = NULL)
	{
		$refreshTokenResponse = NULL;

        $cloudMailBoxTypeId = $this->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
        if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
        {
        	$cloudMailBoxMappedTokenDetails = $this->getAppuserMappedTokenDetailsForMailBoxType($cloudMailBoxTypeId);
            if(isset($cloudMailBoxMappedTokenDetails))
            {
                $accessToken = $cloudMailBoxMappedTokenDetails->access_token;
                $refreshToken = $cloudMailBoxMappedTokenDetails->refresh_token;
                $sessionTypeId = $cloudMailBoxMappedTokenDetails->session_type_id;
	            $mailBoxId = $cloudMailBoxMappedTokenDetails->cloud_mail_box_id;

                if(isset($accessToken) && trim($accessToken) != "" && isset($refreshToken) && trim($refreshToken) != "")
                {
                    $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                    $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                    
                    $sessModelObj = New SessionType;
                    $iosTypeId = $sessModelObj->IOS_SESSION_TYPE_ID;
			        $androidTypeId = $sessModelObj->ANDROID_SESSION_TYPE_ID;
                    
                    $userSessionTypeId = $sessionTypeId;//isset($userSession) ? $userSession->session_type_id : $sessionTypeId;

                    if($cloudMailBoxTypeCode == CloudMailBoxType::$GOOGLE_MAILBOX_TYPE_CODE)
                    {
                    	$consClientId = env('GOOGLE_CLIENT_ID');
	                    $consClientSecret = env('GOOGLE_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('GOOGLE_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }
                    else if($cloudMailBoxTypeCode == CloudMailBoxType::$MICROSOFT_MAILBOX_TYPE_CODE)
                    {
                    	$consClientId = env('MICROSOFT_CLIENT_ID');
	                    $consClientSecret = env('MICROSOFT_CLIENT_SECRET');

	                    if($userSessionTypeId == $iosTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_IOS_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
	                    else if($userSessionTypeId == $androidTypeId)
	                    {
	                        $consClientId = env('MICROSOFT_ANDROID_CLIENT_ID');
	                        $consClientSecret = '';
	                    }
                    }      
                    
                    $refreshTokenResponse = $cldMailBoxMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
                    
                    if(isset($refreshTokenResponse) && isset($refreshTokenResponse->access_token) && $refreshTokenResponse->access_token != "")
                    {
                        $updAccessToken = $accessToken;
                        if($refreshTokenResponse->access_token != $accessToken)
                        {
                            $updAccessToken = $refreshTokenResponse->access_token;
                        }
                        
                        $updRefreshToken = $refreshToken;
                        if(isset($refreshTokenResponse->refresh_token) && $refreshTokenResponse->refresh_token != "" && $refreshTokenResponse->refresh_token != $refreshToken)
                        {
                            $updRefreshToken = $refreshTokenResponse->refresh_token;
                        }
                        
                        $this->saveAppuserAccessTokenForMailBoxType($sessionTypeId, $cloudMailBoxTypeId, $updAccessToken, $updRefreshToken);
                    }

                    // $refreshTokenResponse->consClientId = $consClientId;
                    // $refreshTokenResponse->consClientSecret = $consClientSecret;
                }
            }
	    }

	    return $refreshTokenResponse;
	}

	public function checkAppuserCloudMailBoxAccessTokenValidity($cloudMailBoxTypeCode, $userSession = NULL)
	{
		$isLinked = 0;

        $cloudMailBoxTypeId = $this->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
        if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
        {
        	$cloudMailBoxMappedTokenDetails = $this->getAppuserMappedTokenDetailsForMailBoxType($cloudMailBoxTypeId);
        	if(isset($cloudMailBoxMappedTokenDetails))
        	{
        		$accessToken = $cloudMailBoxMappedTokenDetails->access_token;
	            $sessionTypeId = $cloudMailBoxMappedTokenDetails->session_type_id;
	            $mailBoxId = $cloudMailBoxMappedTokenDetails->cloud_mail_box_id;
	            
	            if(isset($accessToken) && trim($accessToken) != "")
	            {
					$cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
			        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);        
			        
			        $checkTokenResponse = $cldMailBoxMgmtObj->checkAccessTokenValidity();

			        if(isset($checkTokenResponse) && isset($checkTokenResponse['isTokenValid']))
			        {
			           $isLinked = $checkTokenResponse['isTokenValid'];
			        }
			    }
        	}	            
	    }

	    return $isLinked;
	}
	

	public function createContentSenderVirtualFolderForReceiver($senderEmpOrUserId, $senderEmpOrUserEmail)//, $receiverEmpOrUserId)
	{
		// $isFolder = TRUE;
		// $contentListReceivedFromSender = $this->getContentsReceivedFromSender($senderEmpOrUserEmail, $isFolder);
		// if(isset($contentListReceivedFromSender) && count($contentListReceivedFromSender) == 1)

		$folderModelObj = $this->getAllFoldersModelObj();
		$virtualSenderFolderExists = $folderModelObj->virtualSenderFolderForEmailExists($senderEmpOrUserEmail)->first();
		if(!isset($virtualSenderFolderExists))
		{
			if($this->orgId > 0)
			{
		        $senderDepMgmtObj = New ContentDependencyManagementClass;
		        $senderDepMgmtObj->withOrgIdAndEmpId($this->orgId, $senderEmpOrUserId);

		        // $receiverDepMgmtObj = New ContentDependencyManagementClass;
		        // $receiverDepMgmtObj->withOrgIdAndEmpId($this->orgId, $receiverEmpOrUserId);
			}
			else
			{
				$orgKey = "";

	    		$senderUser =  new \stdClass();
	    		$senderUser->appuser_id = $senderEmpOrUserId;

		        $senderDepMgmtObj = New ContentDependencyManagementClass;
		        $senderDepMgmtObj->withOrgKey($senderUser, $orgKey);

	    		// $receiverUser =  new \stdClass();
	    		// $receiverUser->appuser_id = $receiverEmpOrUserId;

		        // $receiverDepMgmtObj = New ContentDependencyManagementClass;
		        // $receiverDepMgmtObj->withOrgKey($receiverUser, $orgKey);
			}

			$senderName = $senderDepMgmtObj->getEmployeeOrUserName();

			$chkShowFolder = 1;
			$chkShowGroup = 1;

	        $folderFilterUtilObj = New FolderFilterUtilClass;
	        $folderFilterUtilObj->setFilterStr('');
	        $folderFilterUtilObj->setFilterValueIsShowFolder($chkShowFolder);
	        $folderFilterUtilObj->setFilterValueIsShowGroup($chkShowGroup);
	        $folderFilterUtilObj->setFilterValueSenderEmail($senderEmpOrUserEmail);

	        $updEncFilterStr = $folderFilterUtilObj->compileFilterStr();

	        $compFolderName = $senderName;
	        // $compFolderName = str_replace(' ', '-', $compFolderName); 
	        $compFolderName = preg_replace('/[^A-Za-z0-9\-]/', ' ', $compFolderName);

	        $compFolderNameLength = strlen($compFolderName);
	        if($compFolderNameLength > 20)
	        {
	        	$compFolderName = substr($compFolderName, 0, 20);
	        }

			$iconCode = Config::get('app_config.default_virtual_sender_folder_icon_code');
	        $isFavorited = 0;
	        $virtualFolderTypeId = FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID;
	        $virtualSenderEmail = $senderEmpOrUserEmail;

	        $folderCreateResponse = $this->addEditFolder(0, $compFolderName, $iconCode, $isFavorited, $virtualFolderTypeId, $updEncFilterStr, $virtualSenderEmail); // $receiverDepMgmtObj
		}

		$this->recalculateRelevantVirtualSenderFolderContentModifiedTs($senderEmpOrUserEmail);			
	}

	public function getAppuserClientSessionList($sessionTypeId = NULL)
	{
		$appuserSessions = AppuserSession::ofUser($this->userId);

		if(isset($sessionTypeId) && $sessionTypeId > 0)
		{
			$appuserSessions = $appuserSessions->ofSessionType($sessionTypeId);
		}

		$currLoginTokenTemp = $this->currLoginToken;

		$appuserSessions = $appuserSessions->get();

		$compiledAppuserSessionList = array();

        $arrForSorting = array();
		foreach ($appuserSessions as $key => $appuserSession) 
		{
			$isCurrSession = 0;
			if(isset($currLoginTokenTemp) && $currLoginTokenTemp != "" && $currLoginTokenTemp == $appuserSession->login_token)
			{
				$isCurrSession = 1;
			}
			$compiledSessionObj = array();
			$compiledSessionObj['id'] = Crypt::encrypt($appuserSession->appuser_session_id);
			$compiledSessionObj['lastSyncedAt'] = $appuserSession->last_sync_ts;
			$compiledSessionObj['sessionType'] = $appuserSession->type->session_type_name;
			$compiledSessionObj['ipAddress'] = $appuserSession->ip_address;
			$compiledSessionObj['clientDetails'] = $appuserSession->client_details;
			$compiledSessionObj['deviceModelName'] = $appuserSession->device_model_name;
			$compiledSessionObj['isCurrSession'] = $isCurrSession;

			$compiledAppuserSessionList[$key] = $compiledSessionObj;

			$arrForSorting[$key] = $isCurrSession;
		}
        
        array_multisort($arrForSorting, $compiledAppuserSessionList);
        $compiledAppuserSessionList = array_reverse($compiledAppuserSessionList); 

		return $compiledAppuserSessionList;
	}

	public function removeAppuserClientSessionInstance($encSessionId)
	{
		if(isset($encSessionId) && trim($encSessionId) != "")
		{
			$decSessionId = Crypt::decrypt($encSessionId);
			if($decSessionId > 0)
			{
				$appuserSession = AppuserSession::ofUser($this->userId)->byId($decSessionId)->first();

				if(isset($appuserSession))
				{
					MailClass::sendAppuserSessionRemovedMail($this->userId, $appuserSession);
					// remove the session
					$appuserSession->delete();
				}
			}
		}
	}

	public function compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr = '')
	{
        $encUserId = Crypt::encrypt($this->userId);
        $loginToken = $userIsLoggedIn == 1 ? $this->currLoginToken : "";

        $existingUserSessionList = $this->getAppuserClientSessionList();

        $viewDetails = array();
        $viewDetails['userSessions'] = $existingUserSessionList;
        $viewDetails['userIsLoggedIn'] = $userIsLoggedIn;
        $viewDetails['userId'] = $encUserId;
        $viewDetails['loginToken'] = $loginToken;
        $viewDetails['tzStr'] = $tzStr;
   
        $_viewToRender = View::make('appuser.partialview._appuserSessionManagementModal', $viewDetails);
        $_viewToRender = $_viewToRender->render();

        $response = $viewDetails;

        $response['sessionManagementView'] = $_viewToRender;

        return $response;
	}

	public function sendAppuserContactHiMessageWhereverRelevant()
	{
		$user = $this->getUserObject();
		if(isset($user))
		{
			$userRegEmail = $user->email;

			$userContacts = AppuserContact::ofUser($this->userId)->exceptEmail($userRegEmail)->isNotBlocked()->isSracRegisteredUser()->isHiMessageSendPending()->get();

			// print_r('sendToContactCount : '.count($userContacts).'<br/>');

			$isFolder = TRUE;
			$hiMessageContent = 'Hi';
			$sharedByUserEmail = $userRegEmail;
			$sharedByUserId = $this->userId;
            foreach ($userContacts as $usrCont) 
            {
				$contId = $usrCont->appuser_contact_id;
				$name = $usrCont->name;
				$email = $usrCont->email;
				$contactNo = $usrCont->contact_no;
				$isRegd = $usrCont->is_srac_regd;
				$isBlocked = $usrCont->is_blocked;
				$contRegAppuserId = $usrCont->regd_appuser_id;
				
				$contactAppuser = Appuser::byId($contRegAppuserId)->verified()->active()->first();
				if(isset($contactAppuser) && $contactAppuser->email != "")
				{
	        		// print_r('contRegAppuserId : '.$contRegAppuserId.' : contactAppuserEmail : '.$contactAppuser->email.' : name : '.$name.'<br/>');

                    $contUsrDepMgmtObj = New ContentDependencyManagementClass;
                    $contUsrDepMgmtObj->withOrgKey($contactAppuser, "");

					// $contUsrDepMgmtObj->quickCreateAppuserArchiveContent($isFolder, NULL, $hiMessageContent, $sharedByUserEmail, $sharedByUserId);
				}

				$usrCont->is_hi_msg_sent = 1;
				$usrCont->save(); //Was commented before 
			}
		}
	}

	public function quickCreateAppuserArchiveContent($isFolder = TRUE, $folderOrGroupId = NULL, $contentText, $sharedByUserOrEmpEmail = NULL, $sharedByUserOrEmpId = NULL, $additionalContentParams = NULL, $continuePerformingPush = TRUE)
	{
        $newServerContentId = 0;
		$consFolderOrGroupId = NULL;
        $sharedByEmail = '';

		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
		{
			if($isFolder)
			{
				$folderObj = $this->getFolderObject($folderOrGroupId);
				if(isset($folderObj))
				{
					$consFolderOrGroupId = $folderOrGroupId;
				}
			}
			else
			{
				$groupObj = $this->getGroupObject($folderOrGroupId);
                $groupMemberObj = $this->getGroupMemberDetailsObject($folderOrGroupId, FALSE);
				if(isset($groupObj) && isset($groupMemberObj) && $groupMemberObj->has_post_right == 1)
				{
					$consFolderOrGroupId = $folderOrGroupId;
                    $sharedByEmail = $this->getEmployeeOrUserEmail();
				}
			}
		}

		if($isFolder && !isset($consFolderOrGroupId))
		{
			$consFolderOrGroupId = $this->getDefaultFolderId();
		}

		if(isset($consFolderOrGroupId) && $consFolderOrGroupId > 0)
		{
			$utcTz =  'UTC';
	        $createDateObj = Carbon::now($utcTz);
	        $createTimeStamp = $createDateObj->timestamp;                   
	        $createTimeStamp = $createTimeStamp * 1000;
	        $updateTimeStamp = $createTimeStamp;

	        $colorCode = Config::get('app_config.default_content_color_code');
	        $isLocked = Config::get('app_config.default_content_lock_status');
	        $isShareEnabled = Config::get('app_config.default_content_share_status');
	        $contentType = Config::get('app_config.content_type_a');
	        $sourceId = 0;
	        $tagsArr = array();
	        $removeAttachmentIdArr = NULL;
	        $fromTimeStamp = "";
	        $toTimeStamp = "";
	        $isMarked = 0;
	        $remindBeforeMillis = 0;
	        $repeatDuration = 0;
            $isCompleted = Config::get('app_config.default_content_is_completed_status');
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
            $reminderTimestamp = NULL;
	                    
	        if($isFolder)
	        {
	            $response = $this->addEditContent(0, $contentText, $contentType, $consFolderOrGroupId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByUserOrEmpEmail);
	            $newServerContentId = $response['syncId']; 
	        }
	        else
	        {
	            $response = $this->addEditGroupContent(0, $contentText, $contentType, $consFolderOrGroupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail); 
	            $newServerContentId = $response['syncId'];
	        }

            if($newServerContentId > 0 && $continuePerformingPush)
            {
                $isAdd = TRUE;
                $this->sendRespectiveContentModificationPush($isFolder, $newServerContentId, $isAdd, $sharedByEmail);
            }
		}

		$response = array();
        $response['syncId'] = $newServerContentId;
	    
	    return $response;    
	}

	public function quickCreateOrUpdateAppuserCalendarContentForCloudCalendar($existingContentId = 0, $isFolder = TRUE, $folderOrGroupId = NULL, $contentText, $fromTimeStamp, $toTimeStamp, $syncWithCloudCalendarGoogle = 0, $syncWithCloudCalendarOnedrive = 0, $ipTagsArr = NULL, $sharedByUserOrEmpEmail = NULL, $sharedByUserOrEmpId = NULL, $additionalContentParams = NULL)
	{
        $newServerContentId = 0;
		$consFolderOrGroupId = NULL;
        $sharedByEmail = '';

		if(isset($folderOrGroupId) && $folderOrGroupId > 0)
		{
			if($isFolder)
			{
				$folderObj = $this->getFolderObject($folderOrGroupId);
				if(isset($folderObj))
				{
					$consFolderOrGroupId = $folderOrGroupId;
				}
			}
			else
			{
				$groupObj = $this->getGroupObject($folderOrGroupId);
                $groupMemberObj = $this->getGroupMemberDetailsObject($folderOrGroupId, FALSE);
				if(isset($groupObj) && isset($groupMemberObj) && $groupMemberObj->has_post_right == 1)
				{
					$consFolderOrGroupId = $folderOrGroupId;
                    $sharedByEmail = $this->getEmployeeOrUserEmail();
				}
			}
		}

		if($isFolder && !isset($consFolderOrGroupId))
		{
			$consFolderOrGroupId = $this->getDefaultFolderId();
		}

		if(isset($consFolderOrGroupId) && $consFolderOrGroupId > 0)
		{
			$utcTz =  'UTC';
	        $createDateObj = Carbon::now($utcTz);
	        $createTimeStamp = $createDateObj->timestamp;
	        $createTimeStamp = $createTimeStamp * 1000;
	        $updateTimeStamp = $createTimeStamp;


	        $colorCode = Config::get('app_config.default_content_color_code');
	        $isLocked = Config::get('app_config.default_content_lock_status');
	        $isShareEnabled = Config::get('app_config.default_content_share_status');
	        $contentType = Config::get('app_config.content_type_c');
	        $sourceId = 0;
	        $tagsArr = array();
	        $removeAttachmentIdArr = NULL;
	        $isMarked = 0;
	        $remindBeforeMillis = 0;
	        $repeatDuration = 0;
            $isCompleted = Config::get('app_config.default_content_is_completed_status');
            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
            $reminderTimestamp = $fromTimeStamp;

            if(isset($ipTagsArr) && is_array($ipTagsArr) && count($ipTagsArr) > 0)
            {
            	$tagsArr = $ipTagsArr;
            }

	        $userOrEmpId = $this->getEmployeeOrUserId();

	        $consContentId = 0;
			if(isset($existingContentId) && $existingContentId > 0)
			{
				$existingContentDetails = $this->getContentObject($existingContentId, $isFolder);

				if(isset($existingContentDetails))
				{
					$consContentId = $existingContentId;

					$existingContentTags = $this->getContentTags($existingContentId, $userOrEmpId, $isFolder);
					foreach ($existingContentTags as $contentTag)
					{
						$tagId = $contentTag->tag_id;
						if(!in_array($tagId, $tagsArr))
						{
							array_push($tagsArr, $tagId);
						}						
					}

					$colorCode = $existingContentDetails->color_code;
					$isLocked = $existingContentDetails->is_locked;
					$isShareEnabled = $existingContentDetails->is_share_enabled;
					$sourceId = isset($existingContentDetails->source_id) ? $existingContentDetails->source_id : 0;
					$isMarked = isset($existingContentDetails->is_marked) ? $existingContentDetails->is_marked : 0;
					$remindBeforeMillis = isset($existingContentDetails->remind_before_millis) ? $existingContentDetails->remind_before_millis : 0;
					$repeatDuration = isset($existingContentDetails->repeat_duration) ? $existingContentDetails->repeat_duration : 0;
					$createTimeStamp = $existingContentDetails->create_timestamp;
					$isCompleted = isset($existingContentDetails->is_completed) ? $existingContentDetails->is_completed : 0;
					$isSnoozed = isset($existingContentDetails->is_snoozed) ? $existingContentDetails->is_snoozed : 0;
					$reminderTimestamp = isset($existingContentDetails->reminder_timestamp) ? $existingContentDetails->reminder_timestamp : 0;
				}
			}
	                    
	        if($isFolder)
	        {
	            $response = $this->addEditContent($consContentId, $contentText, $contentType, $consFolderOrGroupId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByUserOrEmpEmail, $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive);
	            $newServerContentId = $response['syncId']; 
	        }
	        else
	        {
	            $response = $this->addEditGroupContent($consContentId, $contentText, $contentType, $consFolderOrGroupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail); 
	            $newServerContentId = $response['syncId'];
	        }

            if($newServerContentId > 0)
            {
                $isAdd = TRUE;
                $this->sendRespectiveContentModificationPush($isFolder, $newServerContentId, $isAdd, $sharedByEmail);
            }
		}

		$response = array();
        $response['syncId'] = $newServerContentId;
	    
	    return $response;    
	}
	
	public function recalculateFolderOrGroupContentModifiedTs($isFolder, $folderOrGroupId)
	{
		if($isFolder)
		{
			if(isset($this->orgDbConName))
	        {
	        	$modelObj = New OrgEmployeeFolder;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{
	   	 		$modelObj = New AppuserFolder;  
			}
		}
		else
		{
			if(isset($this->orgDbConName))
	        {
	        	$modelObj = New OrgGroup;
				$modelObj->setConnection($this->orgDbConName);
			}
			else
			{
	   	 		$modelObj = New Group;  
			}
		}

		if(isset($modelObj))
		{
			$modelObj = $modelObj->byId($folderOrGroupId);

			$fetchedFolderOrGroup = $modelObj->first();
			if(isset($fetchedFolderOrGroup))
			{
                $currentTimestamp = CommonFunctionClass::getCreateTimestamp();

				$fetchedFolderOrGroup->content_modified_at = $currentTimestamp;
				$fetchedFolderOrGroup->save();
			}
		}
		
	}
	
	public function recalculateRelevantVirtualSenderFolderContentModifiedTs($senderEmpOrUserEmail)
	{
		if(isset($senderEmpOrUserEmail) && trim($senderEmpOrUserEmail) != "")
		{
			$senderEmpOrUserEmail = trim($senderEmpOrUserEmail);

			$folderModelObj = $this->getAllFoldersModelObj();
			$virtualSenderFolderExists = $folderModelObj->virtualSenderFolderForEmailExists($senderEmpOrUserEmail)->first();
			if(isset($virtualSenderFolderExists))
			{
	            $currentTimestamp = CommonFunctionClass::getCreateTimestamp();

				$virtualSenderFolderExists->content_modified_at = $currentTimestamp;
				$virtualSenderFolderExists->save();
			}
		}	
	}

	public function performContentConversationPartOperation($isFolder, $contentId, $content, $convOpParams)
	{
        $status = 0;
        $msg = '';
        $response = array();

        if(isset($convOpParams) && isset($content) && ((!$isFolder) || ($isFolder && $content->is_removed == 0)))
        {
			$conversationIndex = $convOpParams['conversationIndex'];
			$updateTs = $convOpParams['updateTs'];
			$isChangeLogOp = isset($convOpParams['isChangeLogOp']) ? $convOpParams['isChangeLogOp'] : 0;
			$changeLogText = isset($convOpParams['changeLogText']) ? $convOpParams['changeLogText'] : "";
			$isDeleteOp = $convOpParams['isDeleteOp'];
			$isReplyOp = $convOpParams['isReplyOp'];
			$replyText = $convOpParams['replyText'];
			$isEditOp = $convOpParams['isEditOp'];
			$editText = $convOpParams['editText'];

			// Log::info('isChangeLogOp : '.$isChangeLogOp.' : isReplyOp : '.$isReplyOp.' : replyText : '.$replyText);

            $contentCanBeModified = FALSE;
            if($content->is_locked == 1)
            {
                if($isReplyOp == 1 || $isDeleteOp == 1)
                {
                    $contentCanBeModified = TRUE;
                }
            }
            else
            {
                $contentCanBeModified = TRUE;
            }
            // Log::info('contentCanBeModified : '.json_encode($contentCanBeModified));
            
            if($contentCanBeModified)
            {
                $orgId = $this->getOrganizationId();  
                $orgEmpId = $this->getOrgEmployeeId();
                $user = $this->getUserObject();

                $sharedByName = "";  
                $sharedByEmail = "";  
                
                $sharedByUserName = $user->fullname;  
                $sharedByUserEmail = $user->email; 
                $sharedByEmpEmail = "";             
                $sharedByEmpName = "";

                $orgEmployee = $this->getPlainEmployeeObject();
                if(isset($orgEmployee))
                {   
                    $sharedByEmpName = $orgEmployee->employee_name;
                    $sharedByEmpEmail = $orgEmployee->email;          
                }

                if($orgId > 0)
                {
                    $sharedByName = $sharedByEmpName;  
                    $sharedByEmail = $sharedByEmpEmail;
                }
                else
                {
                    $sharedByName = $sharedByUserName;  
                    $sharedByEmail = $sharedByUserEmail;
                }

                $orgContentText = "";
                if(isset($content->content) && $content->content != "")
                {
                    try
                    {
                        $orgContentText = Crypt::decrypt($content->content);
                    } 
                    catch (DecryptException $e) 
                    {
                        //
                        $response['DecryptException'] = $e;
                    }
                    $response['encContent'] = $content->content;
                }
                $response['orgContentText'] = $orgContentText;

                $contentTextIsShared = $this->checkIfContentIsSharedFromContentText($orgContentText);

                $response['contentTextIsShared'] = $contentTextIsShared;

                if($contentTextIsShared == 1)
                {
                    $hasModifyRights = $this->userHasContentModificationRight($isFolder, $content);

                    //$response['hasModifyRights'] = $hasModifyRights;

                    if($hasModifyRights)
                    {
                        $updContentText = $orgContentText;

                        $contentConversationResponse = $this->getConversationArrayFromSharedContentText($orgContentText);
                        $contentConversationDetailsArr = $contentConversationResponse['conversation'];
                        $contentConversationPartsArr = $contentConversationResponse['conversationParts'];
                        $contentConversationExistingText = $contentConversationResponse['sanStr'];

        				$contentConversationDetailsReversed = array_reverse($contentConversationDetailsArr);

                        $conversationPartsCount = count($contentConversationDetailsArr);

                        if(count($contentConversationPartsArr) > count($contentConversationDetailsArr))
                        {
                            array_splice($contentConversationPartsArr, 0, 1);
                        }
                        
                        $response['existingContentText'] = $contentConversationExistingText;
                        $response['existingContentConversationPartsArr'] = $contentConversationPartsArr;

                        if($conversationPartsCount > $conversationIndex)
                        {
                            $updContentConversationPartsArr = $contentConversationPartsArr;

                            $selContentConversationPartStr = $conversationIndex >= 0 ? $updContentConversationPartsArr[$conversationIndex] : NULL;
                            $selContentConversationDetailsObj = $conversationIndex >= 0 ? $contentConversationDetailsArr[$conversationIndex] : NULL;

                            if($isDeleteOp == 1 && $conversationIndex >= 0)
                            {
                                // array_splice($updContentConversationPartsArr, $conversationIndex, 1);
                                $deleteAppendedStr = CommonFunctionClass::getContentPartDeleteAppendedString($selContentConversationDetailsObj, $updateTs, $sharedByName, $sharedByEmail);
                                $updContentConversationPartsArr[$conversationIndex] = $deleteAppendedStr;
                            }
                            else if($isEditOp == 1 && $conversationIndex >= 0)
                            {
                                $editAppendedStr = CommonFunctionClass::getContentPartEditAppendedString($selContentConversationDetailsObj, $editText, $updateTs, $sharedByName, $sharedByEmail);
                                $updContentConversationPartsArr[$conversationIndex] = $editAppendedStr;
                            }
                            else if($isReplyOp == 1 && $conversationIndex >= -1)
                            {
								// $replyAppendedStr = CommonFunctionClass::getContentPartReplyAppendedStringForChangeLog($replyText, $updateTs, $sharedByName, $sharedByEmail);

                                $replyAppendedStr = CommonFunctionClass::getContentPartReplyAppendedString($selContentConversationDetailsObj, $replyText, $updateTs, $sharedByName, $sharedByEmail);

                                array_unshift($updContentConversationPartsArr, $replyAppendedStr);
                            }

                            if(isset($updContentConversationPartsArr) && count($updContentConversationPartsArr) > 0)
                            {
                                $separatorStr = Config::get('app_config.conversation_part_separator');                                
                                $updContentText = implode($separatorStr, $updContentConversationPartsArr);

                                if($updContentText != "")
                                {
                                    $sharedNoteDepiction = Config::get('app_config.conversation_part_separator_with_br');
                                    $updContentText = $sharedNoteDepiction.$updContentText;
                                }
                            }
                            
                            $response['updContentConversationPartsArr'] = $updContentConversationPartsArr;
                            $response['updContentText'] = $updContentText;

                            if($updContentText != "")
                            {
                                $this->modifyUserOrGroupContent($isFolder, $contentId, $updContentText, $updateTs);

                                $sharedContentObj = NULL;
                                $sharedContentId = 0;
                                if($isFolder)
                                {
                                    $this->sendFolderContentAsReply($contentId, TRUE, FALSE);
                                    $sharedContentId = $content->shared_content_id;
                                    if($sharedContentId > 0)
                                    {
                                        // $this->modifyUserOrGroupContent($isFolder, $sharedContentId, $updContentText, $updateTs);
                                        // $sharedContentObj = $this->getContentObject($sharedContentId, $isFolder);
                                        // if(!isset($sharedContentObj))
                                        // {
                                            // $this->sendFolderContentAsReply($contentId);
                                            // $updContentObj = $this->getContentObject($contentId, $isFolder);
                                            // $sharedContentId = $updContentObj->shared_content_id;
                                            // $sharedContentObj = $this->getContentObject($sharedContentId, $isFolder);
                                        // }
                                    }
                                    $response['sharedContentId'] = $sharedContentId;
                                    $response['sharedContentObj'] = $sharedContentObj;
                                }

                                if(isset($sharedContentObj))
                                {
                                    $response['updSharedContentText'] = $updContentText;
                                    //$this->modifyUserOrGroupContent($isFolder, $sharedContentId, $updContentText, $updateTs);
                                }

								/* WILL SEND NOTIFICATIONS SEPARATELY */
                                /*
                                $isAdd = 0;
                                $this->sendRespectiveContentModificationPush($isFolder, $contentId, $isAdd, $sharedByEmail);
								*/
                                /* WILL SEND NOTIFICATIONS SEPARATELY */
                            }
                        }

                        $status = 1;
        
                        $response['syncContent'] = utf8_encode($updContentText);
                        $response['encSyncContent'] = rawurlencode($updContentText);
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
                    $msg = 'Not a conversation content';
                } 

            }   
            else
            {
                $status = -1;
                $msg = 'Locked content cannot be modified';
            }
        }

        $response['status'] = $status;
        $response['msg'] = $msg;

        return $response;
	}

	public function checkAndSetupCalendarContentForLinkedCloudCalendar($actionCode, $isFolder, $folderOrGroupId, $contentId, $mainContent = NULL)
	{
		$eventResponse = NULL;

    	if(!isset($mainContent))
    	{
    		$mainContent = $this->getContentObject($contentId, $isFolder);
    	}

    	if(isset($mainContent))
    	{
        	$typeC = Config::get("app_config.content_type_c");
			$contentTypeId = $mainContent->content_type_id;
			$syncWithCloudCalendarGoogle = $mainContent->sync_with_cloud_calendar_google;
			$syncWithCloudCalendarOnedrive = $mainContent->sync_with_cloud_calendar_onedrive;

			if($contentTypeId == $typeC && ($syncWithCloudCalendarGoogle == 1 || $syncWithCloudCalendarOnedrive == 1))
			{
				$linkedCloudCalendarTypeArr = $this->getAppuserLinkedCloudCalendarTypeMapping();

	    		if(isset($linkedCloudCalendarTypeArr) && count($linkedCloudCalendarTypeArr) > 0)
	    		{
		            $orgContentText = "";
		            if(isset($mainContent->content) && $mainContent->content != "")
		            {
		                try
		                {
		                    $orgContentText = Crypt::decrypt($mainContent->content);
		                } 
		                catch (DecryptException $e) 
		                {
		                    //
		                }
		            }

        			$tzOffset = Input::get('tzOfs');
               		$tzOfs = isset($tzOffset) && !is_nan($tzOffset) ? $tzOffset : -330;

               		$sanContentText = strip_tags($orgContentText);
               		$sanContentTextLength = strlen($sanContentText);

		            $maxSummaryLength = 100;
		            $maxDescriptionLenght = 400;

		            $summary = "";
		            if($sanContentTextLength > $maxSummaryLength)
		            {
						$summary = substr($sanContentText, 0, $maxSummaryLength) . '...';
		            }
		            else
		            {
		            	$summary = $sanContentText;
		            }

		            $description = "";
		            if($sanContentTextLength > $maxDescriptionLenght)
		            {
						$description = substr($sanContentText, 0, $maxDescriptionLenght) . '...';
		            }
		            else
		            {
		            	$description = $sanContentText;
		            }

		            $startTs = $mainContent->from_timestamp;
		            $endTs = $mainContent->to_timestamp;

			    	$srcIsHylyt = 1;

	    			foreach ($linkedCloudCalendarTypeArr as $linkedCloudCalendarTypeDetails) 
	    			{
	    				$cloudCalendarTypeId = $linkedCloudCalendarTypeDetails['cloudCalendarTypeId'];
						$cloudCalendarTypeName = $linkedCloudCalendarTypeDetails['cloudCalendarTypeName'];
						$cloudCalendarTypeCode = $linkedCloudCalendarTypeDetails['cloudCalendarTypeCode'];
						$calendarIdArr = $linkedCloudCalendarTypeDetails['cloudCalendarIdArr'];
						$accessToken = $linkedCloudCalendarTypeDetails['accessToken'];

						$performEventSync = FALSE;

						if($cloudCalendarTypeCode == CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE)
						{
							if($syncWithCloudCalendarGoogle == 1)
							{
								$performEventSync = TRUE;
							}
						}
						else if($cloudCalendarTypeCode == CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE)
						{
							if($syncWithCloudCalendarOnedrive == 1)
							{
								$performEventSync = TRUE;
							}
						}

						if($performEventSync)
						{
			                $cldCalendarMgmtObj = New CloudCalendarManagementClass;
			                $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);

			                foreach ($calendarIdArr as $calendarId) 
		                	{
			                	$existingCloudCalendarContentMapping = $this->getCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId);

				                if($actionCode == $this->cloudCalendarSyncOperationTypeCreation || $actionCode == $this->cloudCalendarSyncOperationTypeModification)
				                {
				                	$savedEventId = NULL;
				                	if(isset($existingCloudCalendarContentMapping))
				                	{
				                		$cloudCalendarRefEventId = $existingCloudCalendarContentMapping->reference_id;

				                        $eventResponse = $cldCalendarMgmtObj->updateExistingEvent($tzOfs, $calendarId, $cloudCalendarRefEventId, $startTs, $endTs, $summary, $description);
				                        if(isset($eventResponse) && isset($eventResponse['syncEventId']))
				                        {
				                        	$savedEventId = $eventResponse['syncEventId'];
				                        }				                	
				                	}
				                	else
				                	{
			                        	$genEventId = CommonFunctionClass::generateRetailCloudCalendarEventIdString();

				                        $eventResponse = $cldCalendarMgmtObj->addNewEvent($tzOfs, $calendarId, $genEventId, $startTs, $endTs, $summary, $description);
				                        if(isset($eventResponse) && isset($eventResponse['syncEventId']))
				                        {
				                        	$savedEventId = $eventResponse['syncEventId'];
				                        }
				                	}

				                	if(isset($savedEventId) && $savedEventId != "")
				                	{
				                		$this->setCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId, $savedEventId, $srcIsHylyt);
				                	}
				                }
				                else if($actionCode == $this->cloudCalendarSyncOperationTypeDeletion)
				                {
				                	if(isset($existingCloudCalendarContentMapping))
				                	{
				                		$cloudCalendarRefEventId = $existingCloudCalendarContentMapping->reference_id;

		                        		$eventResponse = $cldCalendarMgmtObj->performEventDelete($calendarId, $cloudCalendarRefEventId);

				                		$this->deleteCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId);
				                	}
				                }
				            }
				        }
	    			}	                	
	    		}
			}	
    	}

    	$compResponse = array();
    	$compResponse['eventResponse'] = $eventResponse;

    	return $compResponse;
	}

	public function getCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId)
	{
		$fetchedCloudCalendarContentMapping = NULL;

		if(isset($this->orgDbConName))
        {
        	$modelObj = New OrgEmployeeContentCloudCalendarMapping;
			$modelObj->setConnection($this->orgDbConName);
			$modelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$modelObj = New AppuserContentCloudCalendarMapping;  
			$modelObj = $modelObj->ofUser($this->userId);
		}

		if(isset($modelObj))
		{
			$modelObj = $modelObj->ofCloudCalendarType($cloudCalendarTypeId);
			$modelObj = $modelObj->byCalendarId($calendarId);

			if($isFolder)
			{
				$modelObj = $modelObj->ofFolderContent($contentId);
			}
			else
			{
				$modelObj = $modelObj->ofGroupContent($contentId);
			}

			$fetchedCloudCalendarContentMapping = $modelObj->first();
		}

		return $fetchedCloudCalendarContentMapping;
	}

	public function setCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId, $eventId, $srcIsHylyt)
	{
    	// Log::info('setCloudCalendarContentMapping : folderOrGroupId : '.$folderOrGroupId.' : contentId : '.$contentId.' : cloudCalendarTypeId : '.$cloudCalendarTypeId);
    	// Log::info('setCloudCalendarContentMapping : calendarId : '.$calendarId.' : eventId : '.$eventId);

		$currTs = CommonFunctionClass::getCurrentTimestamp();

		$fetchedCloudCalendarContentMapping = $this->getCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId);
		
        $contentCalendarMappingDetails = array();
        $contentCalendarMappingDetails['updated_at'] = $currTs;
   		$contentCalendarMappingDetails['reference_id'] = $eventId;
   		$contentCalendarMappingDetails['src_is_hylyt'] = $srcIsHylyt;

    	if(isset($this->orgDbConName))
    	{
			$modelObj = New OrgEmployeeContentCloudCalendarMapping;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$tableName = $modelObj->table;	
			$modelObj = $modelObj->ofEmployee($this->orgEmpId);							
					       			
			$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
		}
		else
    	{
			$modelObj = New AppuserContentCloudCalendarMapping;
			$tableName = $modelObj->table;
			$modelObj = $modelObj->ofUser($this->userId);
			
			$insModelObj = DB::table($tableName);
		}
   		
   		$syncId = 0;
   		if(!isset($fetchedCloudCalendarContentMapping))
		{
    		// Log::info('setCloudCalendarContentMapping : fetchedCloudCalendarContentMapping not set : ');
	    	if(isset($this->orgDbConName))
	    	{
	   			$contentCalendarMappingDetails['org_employee_id'] = $this->orgEmpId;
			}
			else
	    	{
	   			$contentCalendarMappingDetails['appuser_id'] = $this->userId;
			}

   			$contentCalendarMappingDetails['created_at'] = $currTs;
   			$contentCalendarMappingDetails['mapped_cloud_calendar_type_id'] = $cloudCalendarTypeId;
   			$contentCalendarMappingDetails['is_folder'] = $isFolder ? 1 : 0;
   			$contentCalendarMappingDetails['usr_content_id'] = $isFolder ? $contentId : 0;
   			$contentCalendarMappingDetails['grp_content_id'] = !$isFolder ? $contentId : 0;
   			$contentCalendarMappingDetails['calendar_id'] = $calendarId;

			$syncId = $insModelObj->insertGetId($contentCalendarMappingDetails);
		}
		else
		{
    		// Log::info('setCloudCalendarContentMapping : fetchedCloudCalendarContentMapping is set : ');
        	$fetchedCloudCalendarContentMapping->update($contentCalendarMappingDetails);

	    	if(isset($this->orgDbConName))
	    	{
	   			$syncId = $fetchedCloudCalendarContentMapping->employee_content_cloud_calendar_mapping_id;
			}
			else
	    	{
	   			$syncId = $fetchedCloudCalendarContentMapping->appuser_content_cloud_calendar_mapping_id;
			}
		}

    	// Log::info('setCloudCalendarContentMapping : syncId : '.$syncId);
		return $syncId;
	}

	public function getCloudCalendarEventToUserContentMapping($isFolder, $cloudCalendarTypeId, $calendarId, $eventId)
	{
		$fetchedCloudCalendarContentMapping = NULL;

		if(isset($this->orgDbConName))
        {
        	$modelObj = New OrgEmployeeContentCloudCalendarMapping;
			$modelObj->setConnection($this->orgDbConName);
			$modelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$modelObj = New AppuserContentCloudCalendarMapping;  
			$modelObj = $modelObj->ofUser($this->userId);
		}

		if(isset($modelObj))
		{
			$modelObj = $modelObj->ofCloudCalendarType($cloudCalendarTypeId);
			$modelObj = $modelObj->byCalendarId($calendarId)->byReferenceId($eventId);

			if($isFolder)
			{
				$modelObj = $modelObj->isFolder();
			}
			else
			{
				$modelObj = $modelObj->isFroup();
			}

			$fetchedCloudCalendarContentMapping = $modelObj->first();
		}

		return $fetchedCloudCalendarContentMapping;
	}

	public function deleteCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId)
	{
		$fetchedCloudCalendarContentMapping = $this->getCloudCalendarContentMapping($isFolder, $folderOrGroupId, $contentId, $cloudCalendarTypeId, $calendarId);
		
   		if(isset($fetchedCloudCalendarContentMapping))
		{			
        	$fetchedCloudCalendarContentMapping->delete();

			$this->deleteContent($contentId, $isFolder);
		}
	}

	public function calculateContentReminderNotificationTimeStamp($mainContent = NULL, $isNextEntry = 0, $primNotifTs = 0)
	{
		$nextReminderTs = NULL;

		if($isNextEntry == 0)
		{
			$fromTimestamp = $mainContent->from_timestamp;
			$remindBeforeMillis = $mainContent->remind_before_millis;

			$nextReminderTs = $fromTimestamp;

			if(isset($remindBeforeMillis) && !is_nan($remindBeforeMillis) && $remindBeforeMillis > 0)
			{
				$nextReminderTs = $fromTimestamp - $remindBeforeMillis;
			}
		}
		else
		{
			if(isset($mainContent->repeat_duration) && trim($mainContent->repeat_duration) != '' && isset($primNotifTs) && !is_nan($primNotifTs) && $primNotifTs > 0)
			{
				$repeatDuration = $mainContent->repeat_duration;

				$nextReminderObj = Carbon::createFromTimeStampUTC(floor($primNotifTs/1000));

				$repeatDurationCode = strtoupper($repeatDuration);

				if($repeatDurationCode == 'YEARLY')
				{
					$nextReminderObj->addYear();
				}
				else if($repeatDurationCode == 'MONTHLY')
				{
					$nextReminderObj->addMonth();
				}
				else if($repeatDurationCode == 'WEEKLY')
				{
					$nextReminderObj->addWeek();
				}
				else if($repeatDurationCode == 'DAILY')
				{
					$nextReminderObj->addDay();
				}
				else if($repeatDurationCode == 'HOURLY')
				{
					$nextReminderObj->addHour();
				}

				$nextReminderTs = $nextReminderObj->timestamp;
				$nextReminderTs = $nextReminderTs * 1000;
			}
		}
			
		return $nextReminderTs;
	}

	public function checkAndSetupContentForAdditionalDataMapping($isFolder, $folderOrGroupId, $contentId, $mainContent = NULL, $consAdditionalDataArr = NULL)
	{
    	if(!isset($mainContent))
    	{
    		$mainContent = $this->getContentObject($contentId, $isFolder);
    	}

    	if(isset($mainContent))
    	{
        	$typeR = Config::get("app_config.content_type_r");
        	$typeC = Config::get("app_config.content_type_c");
			$contentTypeId = $mainContent->content_type_id;

			if($contentTypeId == $typeR || $contentTypeId == $typeC)
			{
				$fromTimestamp = $mainContent->from_timestamp;
				$toTimestamp = $mainContent->to_timestamp;
				$remindBeforeMillis = $mainContent->remind_before_millis;
				$repeatDuration = $mainContent->repeat_duration;

				if(isset($consAdditionalDataArr) && !isset($consAdditionalDataArr['notif_reminder_ts']))
				{
					$nextReminderTs = $consAdditionalDataArr['notif_reminder_ts'];
				}
				else
				{
					$currTs = CommonFunctionClass::getCreateTimestamp();

					$nextReminderTs = $this->calculateContentReminderNotificationTimeStamp($mainContent, 0);
				}

				if($nextReminderTs > 0)
				{
					$additionalDataArr = array();
					$additionalDataArr['notif_reminder_ts'] = $nextReminderTs;

					$this->setMappedContentAdditionalData($isFolder, $folderOrGroupId, $contentId, $additionalDataArr);
				}
			}	
    	}
	}

	public function getMappedContentAdditionalData($isFolder, $folderOrGroupId, $contentId)
	{
		$fetchedContentMapping = NULL;

		if(isset($this->orgDbConName))
        {
        	$modelObj = New OrgEmployeeContentAdditionalData;
			$modelObj->setConnection($this->orgDbConName);
			$modelObj = $modelObj->ofEmployee($this->orgEmpId);
		}
		else
		{
   	 		$modelObj = New AppuserContentAdditionalData;  
			$modelObj = $modelObj->ofUser($this->userId);
		}

		if(isset($modelObj))
		{
			if($isFolder)
			{
				$modelObj = $modelObj->ofFolderContent($contentId);
			}
			else
			{
				$modelObj = $modelObj->ofGroupContent($contentId);
			}

			$fetchedContentMapping = $modelObj->first();
		}

		return $fetchedContentMapping;
	}

	public function setMappedContentAdditionalData($isFolder, $folderOrGroupId, $contentId, $additionalDataArr)
	{
		$currTs = CommonFunctionClass::getCurrentTimestamp();

		$fetchedContentMapping = $this->getMappedContentAdditionalData($isFolder, $folderOrGroupId, $contentId);
		
        $contentAdditionalDetails = array();
        $contentAdditionalDetails['updated_at'] = $currTs;
   		$contentAdditionalDetails['notif_reminder_ts'] = $additionalDataArr['notif_reminder_ts'];

    	if(isset($this->orgDbConName))
    	{
			$modelObj = New OrgEmployeeContentAdditionalData;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$tableName = $modelObj->table;	
			$modelObj = $modelObj->ofEmployee($this->orgEmpId);							
					       			
			$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
		}
		else
    	{
			$modelObj = New AppuserContentAdditionalData;
			$tableName = $modelObj->table;
			$modelObj = $modelObj->ofUser($this->userId);
			
			$insModelObj = DB::table($tableName);
		}
   		
   		$syncId = 0;
   		if(!isset($fetchedContentMapping))
		{
    		// Log::info('setMappedContentAdditionalData : fetchedContentMapping not set : ');
	    	if(isset($this->orgDbConName))
	    	{
	   			$contentAdditionalDetails['org_employee_id'] = $this->orgEmpId;
			}
			else
	    	{
	   			$contentAdditionalDetails['appuser_id'] = $this->userId;
			}

   			$contentAdditionalDetails['created_at'] = $currTs;
   			$contentAdditionalDetails['is_folder'] = $isFolder ? 1 : 0;
   			$contentAdditionalDetails['usr_content_id'] = $isFolder ? $contentId : 0;
   			$contentAdditionalDetails['grp_content_id'] = !$isFolder ? $contentId : 0;

			$syncId = $insModelObj->insertGetId($contentAdditionalDetails);
		}
		else
		{
    		// Log::info('setMappedContentAdditionalData : fetchedContentMapping is set : ');
        	$fetchedContentMapping->update($contentAdditionalDetails);

	    	if(isset($this->orgDbConName))
	    	{
	   			$syncId = $fetchedContentMapping->employee_content_additional_data_id;
			}
			else
	    	{
	   			$syncId = $fetchedContentMapping->appuser_content_additional_data_id;
			}
		}

		// Log::info('setMappedContentAdditionalData : syncId : '.$syncId);
		return $syncId;
	}

	public function deleteMAppedContentAdditionalData($isFolder, $folderOrGroupId, $contentId)
	{
		$fetchedContentMapping = $this->getMappedContentAdditionalData($isFolder, $folderOrGroupId, $contentId);
		
   		if(isset($fetchedContentMapping))
		{
        	$fetchedContentMapping->delete();
		}
	}

	public function performSyncForLinkedCloudCalendarContentSetup($cloudCalendarTypeId)
	{
		$response = array();
		$insEventArr = array();
		$updEventArr = array();
		$delEventArr = array();
		$updContentTextArr = array();

		$cloudCalendarType = $this->getCloudCalendarTypeObjectById($cloudCalendarTypeId);
	
		if(isset($cloudCalendarType))
		{
			$appuserCloudCalendarToken = $this->getAppuserMappedTokenDetailsForCalendarType($cloudCalendarTypeId);

			if(isset($appuserCloudCalendarToken))
			{
				$isAutoSyncEnabled = $appuserCloudCalendarToken->auto_sync_enabled;
				// $response['isAutoSyncEnabled'] = $isAutoSyncEnabled;

				if($isAutoSyncEnabled == 1)
				{
					$accessToken = $appuserCloudCalendarToken->access_token;
					$syncToken = $appuserCloudCalendarToken->sync_token;
					$calendarId = $appuserCloudCalendarToken->calendar_id_arr_str;
					$syncWithOganizationId = $appuserCloudCalendarToken->sync_with_organization_id;
					$syncWithOganizationEmployeeId = $appuserCloudCalendarToken->sync_with_organization_employee_id;

					$cloudCalendarTypeCode = $cloudCalendarType->cloud_calendar_type_code;

					$syncWithCloudCalendarGoogle = 0;
					if($cloudCalendarTypeCode == CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE)
					{
						$syncWithCloudCalendarGoogle = 2;
					}

					$syncWithCloudCalendarOnedrive = 0;
					if($cloudCalendarTypeCode == CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE)
					{
						$syncWithCloudCalendarOnedrive = 2;
					}
					// $response['syncWithCloudCalendarGoogle'] = $syncWithCloudCalendarGoogle;
					// $response['syncWithCloudCalendarOnedrive'] = $syncWithCloudCalendarOnedrive;

					$newSyncToken = NULL;

					$reqQueryStr = "";
					$reqSyncToken = NULL;
					$reqNextPageToken = NULL;
					$isPrimarySync = TRUE;
					if(isset($syncToken) && trim($syncToken) != "")
					{
						$isPrimarySync = FALSE;
						$reqSyncToken = $syncToken;
					}
					else
					{

					}

                    $cloudCalendarTypeNameTagId = 0;
                    $cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;

	                $cldCalendarMgmtObj = New CloudCalendarManagementClass;
	                $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);

	                $allCalendarEntriesExhausted = FALSE;

	                $allFetchedCalendarEventCount = 0;
	                $allFetchedCalendarEventList = array();

	                $iterationNo = 0;

	                do
	                {
	                	$iterationNo++;
                        $eventListResponse = $cldCalendarMgmtObj->getAllCalendarEvents($calendarId, $reqQueryStr, $reqNextPageToken, $reqSyncToken, $isPrimarySync);
                        if(isset($eventListResponse) && isset($eventListResponse['eventList']))
                        {
                        	$fetchedEventCount = $eventListResponse['eventCount'];
                        	$fetchedEventList = $eventListResponse['eventList'];
                        	$eventListHasLoadMore = $eventListResponse['hasLoadMore'];
                        	$eventListLoadMoreCursor = $eventListResponse['loadMoreCursor'];
                        	$eventListHasSyncToken = $eventListResponse['hasSyncToken'];
                        	$eventListRetSyncToken = $eventListResponse['retSyncToken'];

                        	$allFetchedCalendarEventCount = $allFetchedCalendarEventCount + ( $fetchedEventCount * 1 );

                        	if(isset($fetchedEventList) && is_array($fetchedEventList) && count($fetchedEventList) > 0)
                        	{
                        		$allFetchedCalendarEventList = array_merge($allFetchedCalendarEventList, $fetchedEventList);
                        	}

                        	if($eventListHasSyncToken && isset($eventListRetSyncToken) && trim($eventListRetSyncToken) != "")
                        	{
                        		$newSyncToken = trim($eventListRetSyncToken);
                        	}

                        	if($eventListHasLoadMore && isset($eventListLoadMoreCursor) && trim($eventListLoadMoreCursor) != "")
                        	{
                        		$reqNextPageToken = trim($eventListLoadMoreCursor);
                        	}
                        	else
                        	{
                        		$allCalendarEntriesExhausted = TRUE;
                        	}
                        }
                        else
                        {
                    		$allCalendarEntriesExhausted = TRUE;
                        }

						$response['eventListResponse'.$iterationNo] = $eventListResponse;
						$response['allCalendarEntriesExhausted'.$iterationNo] = $allCalendarEntriesExhausted;
	                }
	                while(!$allCalendarEntriesExhausted);

					// if($syncWithCloudCalendarOnedrive > 0)
					// {
					// 	$lastSyncDateStr = CommonFunctionClass::getLastSyncTimestampForMicrosoftCalendarSyncing();
					// 	$response['lastSyncDateStr'] = $lastSyncDateStr;

					// 	$newSyncToken = $lastSyncDateStr;
					// }

					// $response['newSyncToken'] = $newSyncToken;
					$response['allFetchedCalendarEventCount'] = $allFetchedCalendarEventCount;
					$response['allFetchedCalendarEventList'] = $allFetchedCalendarEventList;

					$this->setupAppuserCalendarSyncTokenDetailsForCalendarType($cloudCalendarTypeId, $newSyncToken);

    				MailClass::sendTestCloudCalendarSyncPerformedMail($appuserCloudCalendarToken, $cloudCalendarType, $newSyncToken, $response);

					if(count($allFetchedCalendarEventList) > 0)
					{
	            		$currentTimestamp = CommonFunctionClass::getCreateTimestamp();

						$isFolder = TRUE;
						$folderOrGroupId = NULL;

        				$currUserObj = $this->getUserObject();
        				$consOrgKey = "";
						if($syncWithOganizationId > 0)
            			{
            				$consOrgKey = Crypt::encrypt($syncWithOganizationId."_".$syncWithOganizationEmployeeId);       				
            			}

        				$syncWithOrgDepMgmtObj = New ContentDependencyManagementClass;
		                $syncWithOrgDepMgmtObj->withOrgKey($currUserObj, $consOrgKey);	

		                $srcIsHylyt = 0;		                

						foreach ($allFetchedCalendarEventList as $eventIndex => $fetchedCalendarEventObj) 
						{
							$refEventId = $fetchedCalendarEventObj['eventId'];
							$isCancelled = $fetchedCalendarEventObj['isCancelled'];
							$isConfirmed = $fetchedCalendarEventObj['isConfirmed'];

							if($isCancelled == 0 && isset($fetchedCalendarEventObj['summary']))
							{
								$eventSummary = $fetchedCalendarEventObj['summary'];
								$eventDescription = $fetchedCalendarEventObj['description'];
								$eventStartTs = $fetchedCalendarEventObj['startTs'];
								$eventEndTs = $fetchedCalendarEventObj['endTs'];

								$contentText = $eventSummary;
								// if(isset($eventDescription) && $eventDescription != "")
								// {
								// 	$contentText .= " -- " . $eventDescription;
								// }
								array_push($updContentTextArr, $contentText);

								if($eventStartTs >= $currentTimestamp)
								{
									$fetchedCloudCalendarContentMapping = $syncWithOrgDepMgmtObj->getCloudCalendarEventToUserContentMapping($isFolder, $cloudCalendarTypeId, $calendarId, $refEventId);

									$existingContentId = 0;
									if(isset($fetchedCloudCalendarContentMapping))
									{
										//update
										$existingContentId = $fetchedCloudCalendarContentMapping->usr_content_id;

										array_push($updEventArr, $fetchedCalendarEventObj);
									}
									else
									{
										//insert

										array_push($insEventArr, $fetchedCalendarEventObj);
									}
                    
				                    if($cloudCalendarTypeNameTagId <= 0)
				                    {
					                    $tagWithCloudCalendarTypeName = $this->getTagObjectByName($cloudCalendarTypeName);
					                    if(!isset($tagWithCloudCalendarTypeName))
					                    {
					                        $tagResponse = $this->addEditTag(0, $cloudCalendarTypeName);
					                        $cloudCalendarTypeNameTagId = $tagResponse['syncId'];
					                    }
					                    else
					                    {
					                        $cloudCalendarTypeNameTagId = $this->orgId > 0 ? $tagWithCloudCalendarTypeName->employee_tag_id : $tagWithCloudCalendarTypeName->appuser_tag_id;
					                    }
				                    }
				                    
				                    $tagsArr = array();
				                    if($cloudCalendarTypeNameTagId > 0)
				                    {
				                        array_push($tagsArr, $cloudCalendarTypeNameTagId);
				                    }

									$syncedContentResponse = $syncWithOrgDepMgmtObj->quickCreateOrUpdateAppuserCalendarContentForCloudCalendar($existingContentId, $isFolder, $folderOrGroupId, $contentText, $eventStartTs, $eventEndTs, $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive, $tagsArr);

									$syncedContentId = $syncedContentResponse['syncId'];

									if($syncedContentId > 0)
									{
										$syncWithOrgDepMgmtObj->setCloudCalendarContentMapping($isFolder, $folderOrGroupId, $syncedContentId, $cloudCalendarTypeId, $calendarId, $refEventId, $srcIsHylyt);
									}
								}									
							}
							else if($isCancelled == 1)
							{
								$fetchedCloudCalendarContentMapping = $syncWithOrgDepMgmtObj->getCloudCalendarEventToUserContentMapping($isFolder, $cloudCalendarTypeId, $calendarId, $refEventId);
								if(isset($fetchedCloudCalendarContentMapping))
								{
									$existingContentId = $fetchedCloudCalendarContentMapping->usr_content_id;
									$syncWithOrgDepMgmtObj->deleteCloudCalendarContentMapping($isFolder, $folderOrGroupId, $existingContentId, $cloudCalendarTypeId, $calendarId);
									
									$fetchedCalendarEventObj['fetchedCloudCalendarContentMapping'] = $fetchedCloudCalendarContentMapping;
									array_push($delEventArr, $fetchedCalendarEventObj);
								}
							}								
						}
					}

				}
					
			}
		}

		$response['insEventCount'] = count($insEventArr);
		$response['updEventCount'] = count($updEventArr);
		$response['delEventCount'] = count($delEventArr);
		$response['insEventArr'] = $insEventArr;
		$response['updEventArr'] = $updEventArr;
		$response['delEventArr'] = $delEventArr;
		$response['updContentTextArr'] = $updContentTextArr;

		return $response;			
	}
	
	public function checkCalendarContentTimingForOverLapping($isFolder, $id, $fromTimeStamp, $toTimeStamp)
	{
		$folderOrGroupId = NULL;
		$exceptRemoved = TRUE;
		$searchStr = NULL;
		$sortBy = NULL;
		$sortOrder = NULL;

		$allFolderOrGroupContentsForProfileModel = $this->getAllContentModelObj($isFolder, $folderOrGroupId, $exceptRemoved, $searchStr, $sortBy, $sortOrder);

		$hasOverLappingEvent = 0;

		if(isset($allFolderOrGroupContentsForProfileModel))
		{
            $allFolderOrGroupContentsForProfileModel = $allFolderOrGroupContentsForProfileModel->where('from_timestamp', '<=', $fromTimeStamp);
            $allFolderOrGroupContentsForProfileModel = $allFolderOrGroupContentsForProfileModel->where('to_timestamp', '>=', $toTimeStamp);
			$allFolderOrGroupContentsForProfileResults = $allFolderOrGroupContentsForProfileModel->first();

			if(isset($allFolderOrGroupContentsForProfileResults) && is_array($allFolderOrGroupContentsForProfileResults) && count($allFolderOrGroupContentsForProfileResults) > 0)
			{
				$hasOverLappingEvent = 1;
			}
		}

		$overLappingDetails = array();
		$overLappingDetails['isOverLapping'] = $hasOverLappingEvent;

		return $overLappingDetails;
	}
	
	public function performAppuserAccountDelete()
	{
		$contTypeR = Config::get('app_config.content_type_r'); 
        $contTypeA = Config::get('app_config.content_type_a'); 
        $contTypeC = Config::get('app_config.content_type_c'); 
        $tsFormat = Config::get('app_config.datetime_db_format'); 
       
        $currDtTm = "";

        $userId = $this->userId;

        $user = Appuser::byId($userId)->first();

        if(isset($user))
        {
        	$appUsageStartDate = $user->created_at;
	        $appRegisteredDt = Carbon::createFromFormat($tsFormat, $appUsageStartDate);
	        
			$utcTz =  'UTC';
			$utcToday = Carbon::now($utcTz);		
			$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
			
			$totalDaysAppUsed = $utcTodayDt->diffInDays($appRegisteredDt);
			$totalDaysAppUsed++;
	        
	        $totalNotesTillDate = 0;
	        $totalNotesBeforeDelete = 0;
	        $totalDataSavedKb = 0;
	        $totalAttachmentSavedKb = 0;
	        $rTypeCnt = 0;
	        $aTypeCnt = 0;
	        $cTypeCnt = 0;  

	        $orgId = 0;
	        
	        $userContents = AppuserContent::ofUser($userId)->get();
	        $totalNotesBeforeDelete = count($userContents);
	        foreach ($userContents as $userContent) 
	        {
	            if(isset($userContent))
	            {
	            	$contType = $userContent->content_type_id;
	            	
	            	if($contType == $contTypeR)
	            	{
						$rTypeCnt++;
					}
					elseif($contType == $contTypeA)
	            	{
						$aTypeCnt++;
					}
					elseif($contType == $contTypeC)
	            	{
						$cTypeCnt++;
					}
	            	
	                AppuserContentTag::ofUserContent($userContent->appuser_content_id)->delete();
	                $contentAttachments = AppuserContentAttachment::ofUserContent($userContent->appuser_content_id)->get();
	                foreach ($contentAttachments as $attachment) 
	                {
	                	$fileName = $attachment->server_filename;
	                	FileUploadClass::removeAttachment($fileName, $orgId);
						$attachment->delete();
					}
	               $userContent->delete();
	            }
	        }
	        
	        AppuserFolder::ofUser($userId)->delete();
	        AppuserTag::ofUser($userId)->delete();
	        AppuserSource::ofUser($userId)->delete();
	        
	        $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
	        foreach($userOrganizations as $userOrg) 
	        {
	        	$orgId = $userOrg->organization_id;		                	
	        	$orgEmpId = $userOrg->emp_id;
	            	
	            $orgDepMgmtObj = New ContentDependencyManagementClass;
	            $orgDepMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);   
	            $orgDepMgmtObj->orgEmployeeLeave();
			}
	        
	        $userConstant = AppuserConstant::ofUser($userId)->first();
	        $allottedKb = $userConstant->attachment_kb_allotted;
	        $availableKb = $userConstant->attachment_kb_available;
	        $dbSize = $userConstant->db_size;
	        $dbSizeKb = intval($dbSize/1024);
	        
	        $userConstant->delete();
	        $totalDataSavedKb = $allottedKb - $availableKb;
	        $totalAttachmentSavedKb = $totalDataSavedKb - $dbSizeKb;

	        $deletedUser = new DeletedAppuser();
	        $deletedUser->appuser_id = $user->appuser_id;
	        $deletedUser->is_app_registered = $user->is_app_registered;
	        $deletedUser->reference_id = $user->reference_id;
	        $deletedUser->fullname = $user->fullname;
	        $deletedUser->email = $user->email;
	        $deletedUser->password = $user->password;
	        $deletedUser->contact = $user->contact;
	        $deletedUser->gender = $user->gender;
	        $deletedUser->country = $user->country;
	        $deletedUser->city = $user->city;
	        $deletedUser->verification_code = $user->verification_code;
	        $deletedUser->is_verified = $user->is_verified;
	        $deletedUser->is_active = $user->is_active;
	        $deletedUser->created_at = $user->created_at;
	        $deletedUser->updated_at = $user->updated_at;
	        $deletedUser->deleted_at = $currDtTm;
	        
	        //Stats                        
	        $deletedUser->tot_note_count = $totalNotesTillDate;
	        $deletedUser->note_count = $totalNotesBeforeDelete;
	        $deletedUser->day_count = $totalDaysAppUsed;
	        $deletedUser->data_size_kb = $totalDataSavedKb;
	        $deletedUser->attachment_size_kb = $totalAttachmentSavedKb;
	        $deletedUser->total_r = $rTypeCnt;
	        $deletedUser->total_a = $aTypeCnt;
	        $deletedUser->total_c = $cTypeCnt;

	        $deletedUser->save();
	        $user->delete();

	        //Delete Account Mail
	        MailClass::sendAccountDeletedMail($userId);
			
			$sessModelObj = New SessionType;	
			$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
			$userSessions = AppuserSession::ofUser($userId)->get();
			foreach($userSessions as $userSession)
			{
				if($userSession->session_type_id != $webTypeId && $loginToken != $userSession->login_token)
					$this->sendOtherLoginPerformedMessageToDevice($userId, $userSession->reg_token);
				$userSession->delete();
			}
        } 
	}

    public function setupOrgEmployeeTagBasedOnOrgSystemTag($systemTagId, $systemTagName)
    {   
    	$tagSetupDetailLog = array();
    	$syncId = 0;		
    	if(isset($this->orgDbConName))
    	{
			$modelObj = New OrgEmployeeTag;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$tableName = $modelObj->table;

	        $tagDetails = array();
	        $tagDetails['tag_name'] = $systemTagName;
		
	        $userTagExists = $modelObj->ofEmployee($this->orgEmpId)->ofRelatedSystemTag($systemTagId)->first();
			if(isset($userTagExists))
	        {
				$tagSetupDetailLog['userTagExists'] = 1;
	       		$tagDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
	       		
	        	$userTagExists->update($tagDetails);
				$syncId = $userTagExists->employee_tag_id;
			}
			else
			{
				$userTagWithNameExists = $modelObj->ofEmployee($this->orgEmpId)->byName($systemTagName)->first();	
				if(isset($userTagWithNameExists))
				{
					$tagSetupDetailLog['userTagWithNameExists'] = 1;
       				$tagDetails['rel_system_tag_id'] = $systemTagId;
		       		$tagDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		       		
		        	$userTagWithNameExists->update($tagDetails);
					$syncId = $userTagWithNameExists->employee_tag_id;
				}
				else
				{
					$tagSetupDetailLog['userTagDoesNotExist'] = 1;
       				$tagDetails['employee_id'] = $this->orgEmpId;
       				$tagDetails['rel_system_tag_id'] = $systemTagId;
	       			$tagDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();

					$insModelObj = DB::connection($this->orgDbConName)->table($tableName);
					$syncId = $insModelObj->insertGetId($tagDetails);
				}
			}
			$tagSetupDetailLog['tagDetails'] = $tagDetails;
		}
		$tagSetupDetailLog['syncId'] = $syncId;
		return $tagSetupDetailLog;
		// return $syncId;
    }

    public function sendOrgEmployeeTagBasedOnOrgSystemTagModifiedNotification($systemTagId)
    {	
    	if(isset($this->orgDbConName))
    	{
			$modelObj = New OrgEmployeeTag;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
		
	        $userTagExists = $modelObj->ofEmployee($this->orgEmpId)->ofRelatedSystemTag($systemTagId)->first();
			if(isset($userTagExists))
	        {
				$syncId = $userTagExists->employee_tag_id;
    			$this->sendOrgTagAddMessageToDevice($this->orgId, $this->orgEmpId, NULL, $syncId);
			}
		}
    }

    public function resetOrgEmployeeTagOnOrgSystemTagRemoved($systemTagId)
    {		
    	if(isset($this->orgDbConName))
    	{
			$modelObj = New OrgEmployeeTag;
			$modelObj = $modelObj->setConnection($this->orgDbConName);
			$tableName = $modelObj->table;

	        $userTagExists = $modelObj->ofEmployee($this->orgEmpId)->ofRelatedSystemTag($systemTagId)->first();
			if(isset($userTagExists))
	        {
				$syncId = $userTagExists->employee_tag_id;

                $contentTagModelObj = New OrgEmployeeContentTag;
                $contentTagModelObj->setConnection($this->orgDbConName);
                $contentTagModelObj = $contentTagModelObj->where('tag_id', '=', $syncId);
                $usedTags = $contentTagModelObj->first();
                
                if(isset($usedTags))
                {
	        		$tagDetails = array();
                	$tagDetails['rel_system_tag_id'] = NULL;
		       		$tagDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();
		       		
		        	$userTagExists->update($tagDetails);
	    			$this->sendOrgTagAddMessageToDevice($this->orgId, $this->orgEmpId, NULL, $syncId);
                }
                else
                {
                	$this->deleteTag($syncId);
                }		       		
			}
		}
    }

    public function setupOrgEmployeeTagsForAllExistingOrgSystemTags()
    {		
    	if(isset($this->orgDbConName))
    	{
            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);
            $activeSystemTags = $modelObj->active()->get();

			for($i=0; $i<count($activeSystemTags); $i++)
			{  
				$activeSystemTag = $activeSystemTags[i];
				$systemTagId = $activeSystemTag->system_tag_id;
				$systemTagName = $activeSystemTag->tag_name;

				$this->setupOrgEmployeeTagBasedOnOrgSystemTag($systemTagId, $systemTagName);
			}
		}
    }


}
