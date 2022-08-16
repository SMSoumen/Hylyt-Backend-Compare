<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\ContentType;
use App\Models\FolderType;
use App\Models\Api\Appuser;
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
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
use App\Models\Api\CloudStorageType;
use App\Models\Api\AppuserCloudStorageToken;
use App\Models\Api\AppKeyMapping;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use DB;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use App\Libraries\FolderFilterUtilClass;
use View;

class ContentListFormulationClass 
{
    protected $withEncryption = false;  
    protected $userSessionObj = NULL;
		
	public function __construct()
    {   
    	
    }
        
    public function setWithIdEncryption($withEncryptionBool, $userSession = NULL)
    {   
        $this->withEncryption = $withEncryptionBool;
        if(isset($userSession))
        {
            $this->userSessionObj = $userSession;
        }
    }

    public function formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder, $isAttachmentView = 0) 
    {
        $idColName = "";
        if($isFolder)
        {
            if($orgId > 0)
            {
                $idColName = 'employee_contents'.'.employee_content_id';
            }
            else
            {
                $idColName = 'appuser_contents'.'.appuser_content_id';
            }                       
        }
        else
        {
            if($orgId > 0)
            {
                $idColName = 'org_group_contents'.'.group_content_id';
            }
            else
            {
                $idColName = 'group_contents'.'.group_content_id';
            }
        }

        if(!isset($isAttachmentView) && $isAttachmentView != 1)
        {
            $isAttachmentView = 0;
        }

        $isTrashFolder = FALSE;
        if($isFolder && $hasFilters == 1)
        {
            $chkIsTrashed = $filArr['chkIsTrashed'];
            if(isset($chkIsTrashed) && $chkIsTrashed == 1)
            {
                $isTrashFolder = TRUE;
            }
        }

        $userContents = $depMgmtObj->getAllContentModelObj($isFolder, $folderOrGroupId, FALSE);//, NULL, $sortBy, $sortOrder);
        if($isFolder)
            $userContents = $userContents->joinTag();
        else
        {
            $userContents = $userContents->joinTag($empOrUserId);
        }
        $userContents = $userContents->select(['*', "$idColName as content_id", DB::raw("IF(GROUP_CONCAT(tag_name) <> '', GROUP_CONCAT(tag_name), '-')  as tagStr")]);
        
        if($isAllNotes) 
        {
            if($isFolder)
            {
                if($isLocked) {
                    $userConstants = $depMgmtObj->getEmployeeOrUserConstantObject();
                    
                    $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                    $lockedFolderArr = array();
                    if(isset($userConstants))
                    {
                        $hasFolderPasscode = $userConstants->folder_passcode_enabled;
                        $folderIdStr = $userConstants->folder_id_str;
                        if($hasFolderPasscode == 1 && $folderIdStr != null ) 
                        {
                            $tempLockedFolderArr = explode($passcodeFolderIdDelimiter, $folderIdStr);

                            $lockedFolderArr = array();
                            foreach ($tempLockedFolderArr as $indLockedFolderId) {
                                if(is_numeric($indLockedFolderId) && $indLockedFolderId * 1 > 0)
                                {
                                    array_push($lockedFolderArr, $indLockedFolderId * 1);
                                }
                            }
                        }
                    }
                    
                    if(count($lockedFolderArr) > 0) {
                        $userContents = $userContents->filterExceptFolder($lockedFolderArr);
                    }
                }
                
                $chkIsConversation = $filArr['chkIsConversation'];
                if($isTrashFolder == FALSE && ($hasFilters == 0 || ($hasFilters == 1 && (!isset($chkIsConversation) || $chkIsConversation == 0))))
                {
                    // $userContents = $userContents->exceptSentFolder();
                }
            }
            else
            {
        //         if($hasFilters == 0 || (!isset($filGroupArr) || count($filGroupArr) > 0))
        //         {
           //       $userGroups = $depMgmtObj->getAllGroupsFoUser();
           //       $filUserGroupIdArr = array();
           //       foreach($userGroups as $userGroup)
           //       {
                    //  array_push($filUserGroupIdArr, $userGroup->groupId);
                    // }
        //             $userContents = $userContents->filterGroup($filUserGroupIdArr);
        //         }
            }
        }

        $dateFilterTypeIdNone = Config::get('app_config.filter_date_type_id_none'); 
        $dateFilterTypeIdDateRange = Config::get('app_config.filter_date_type_id_date_range'); 
        $dateFilterTypeIdDayCount = Config::get('app_config.filter_date_type_id_day_count'); 
        
        $hasSourceFilterAppliedForGroup = FALSE;

        $filAttachmentExtArr = array();
        $filShowAttachment = -1;
        if($hasFilters == 1)
        {
            $filFolderArr = $filArr['filFolderArr'];
            $filGroupArr = $filArr['filGroupArr'];
            $filAllNotesGroupArr = isset($filArr['filAllNotesGroupArr']) ? $filArr['filAllNotesGroupArr'] : array();
            $filSourceArr = $filArr['filSourceArr'];
            $filTagArr = $filArr['filTagArr'];   
            $filTypeArr = $filArr['filTypeArr'];   
            $filAttachmentExtArr = isset($filArr['filAttachmentExtArr']) ? $filArr['filAttachmentExtArr'] : array();   
            $filFromDate = $filArr['filFromDate'];   
            $filToDate = $filArr['filToDate'];   
            $isStarred = $filArr['chkIsStarred'];   
            $chkIsUntagged = $filArr['chkIsUntagged'];
            $chkIsLocked = $filArr['chkIsLocked'];   
            $chkIsConversation = $filArr['chkIsConversation'];
            $chkIsRestricted = $filArr['chkIsRestricted'];   
            $chkShowFolder = $filArr['chkShowFolder'];   
            $chkShowGroup = $filArr['chkShowGroup'];
            $chkIsTrashed = $filArr['chkIsTrashed'];
            $filShowAttachment = $filArr['filShowAttachment'];  
            $filRepeatStatus = isset($filArr['filRepeatStatus']) ? $filArr['filRepeatStatus'] : -1;  
            $filCompletedStatus = isset($filArr['filCompletedStatus']) ? $filArr['filCompletedStatus'] : -1;  
            $filSenderEmail = $filArr['filSenderEmail'];  
            $filDateFilterType = $filArr['filDateFilterType'];  
            $filDateDayCount = $filArr['filDateDayCount']; 
            $filExceptSentFolder = isset($filArr['filExceptSentFolder']) ? $filArr['filExceptSentFolder'] : 0; 
            
            if($isFolder && isset($filFolderArr) && count($filFolderArr) > 0)
            {
                $userContents = $userContents->filterFolder($filFolderArr);
            }
            if(!$isFolder && isset($filGroupArr) && count($filGroupArr) > 0)
            {
                $userContents = $userContents->filterGroup($filGroupArr);
            }
            else if(!$isFolder && isset($filAllNotesGroupArr))
            {
                $userContents = $userContents->filterGroup($filAllNotesGroupArr);
            }
            if(isset($filTypeArr) && count($filTypeArr) > 0)
            {
                $userContents = $userContents->filterType($filTypeArr);
            }

            if($isFolder && isset($filSourceArr) && count($filSourceArr) > 0)
            {
                $userContents = $userContents->filterSource($filSourceArr);
            }
            
            if(!$isFolder && isset($filSourceArr) && count($filSourceArr) > 0)
            {
                $hasSourceFilterAppliedForGroup = TRUE;
            }

            if(isset($filExceptSentFolder) && $filExceptSentFolder == 1)
            {
                $userContents = $userContents->exceptSentFolder();
            }
            
            if((isset($filTagArr) && count($filTagArr) > 0) || (isset($chkIsUntagged) && $chkIsUntagged == 1))
            {
                if(count($filTagArr) > 0)
                {
                    $userContents = $userContents->filterTag($filTagArr);
                }
                
                if(isset($chkIsUntagged) && $chkIsUntagged == 1)
                {
                    $userContents = $userContents->having('tagStr', '=', '-');
                }
            }
            
            if(isset($isStarred) && $isStarred == 1)
            {
                $userContents = $userContents->where('is_marked', '=', '1');
            }
            
            if(isset($chkIsLocked) && $chkIsLocked == 1)
            {
                $userContents = $userContents->filterIsLocked();
            }
            
            if(isset($filRepeatStatus) && $filRepeatStatus >= 0)
            {
                if($filRepeatStatus == 0)
                {
                    $userContents = $userContents->filterHasRepeatNone();
                }
                else if($filRepeatStatus == 1)
                {
                    $userContents = $userContents->filterHasRepeatSet();
                }
            }
            
            if(isset($filCompletedStatus) && $filCompletedStatus >= 0)
            {
                if($filCompletedStatus == 0)
                {
                    $userContents = $userContents->filterIsNotCompleted();
                }
                else if($filCompletedStatus == 1)
                {
                    $userContents = $userContents->filterIsCompleted();
                }
            }
            
            if($isFolder)
            {
                if(isset($chkIsTrashed) && $chkIsTrashed == 1)
                {
                    $userContents = $userContents->filterIsRemoved();
                }
                else
                {
                    $userContents = $userContents->filterExceptRemoved();
                }
            }
            
            if(isset($chkIsRestricted) && $chkIsRestricted == 1)
            {
                $userContents = $userContents->filterIsRestricted();
            }
            
            if($isFolder && isset($chkIsConversation) && $chkIsConversation == 1)
            {
                $userContents = $userContents->filterIsConversation();
            }
            
            // if(isset($filSenderEmail) && $filSenderEmail != '')
            // {
            //     $userContents = $userContents->filterSenderEmail($filSenderEmail);
            // }

            if($dateFilterTypeIdDayCount == $filDateFilterType && $filDateDayCount > 0)
            {
                $utcTz =  'UTC';
                $todayDateObj = Carbon::now($utcTz)->endOfDay();
                $todayDateTs = $todayDateObj->timestamp;                   
                $todayDateTs = $todayDateTs * 1000;
                $filToDate = $todayDateTs;

                $minDateObj = Carbon::now($utcTz)->subDays($filDateDayCount)->startOfDay();
                $minDateTs = $minDateObj->timestamp;                   
                $minDateTs = $minDateTs * 1000;
                $filFromDate = $minDateTs;
            }
            
            if(isset($filFromDate) && $filFromDate != "" && $filFromDate > 0)
            {
                if(isset($filFromDate) && $filFromDate != "" && $filFromDate > 0)
                {
                    $tsStrLen = strlen($filFromDate."");
                    if($tsStrLen > 13)
                    {
                        $diff = $tsStrLen - 13;
                        $divisor = pow ( 13 , $diff );
                        $filFromDate = intval($filFromDate/$divisor);                   
                    }
                    //$userContents = $userContents->where('create_timestamp', '>=', $filFromDate);
                    // $userContents = $userContents->where('from_timestamp', '>=', $filFromDate);
                    $userContents = $userContents->where('update_timestamp', '>=', $filFromDate);
                }
                
                if(isset($filToDate) && $filToDate != "" && $filToDate > 0)
                {
                    $tsStrLen = strlen($filToDate."");
                    if($tsStrLen > 13)
                    {
                        $diff = $tsStrLen - 13;
                        $divisor = pow ( 13 , $diff );
                        $filToDate = intval($filToDate/$divisor);                   
                    }
                    //$userContents = $userContents->where('create_timestamp', '<=', $filToDate);
                    // $userContents = $userContents->where('from_timestamp', '<=', $filToDate);
                    $userContents = $userContents->where('update_timestamp', '<=', $filToDate);
                }
            }
        }
        else
        {
            if($isFolder)
            {
                $userContents = $userContents->filterExceptRemoved();
            }
        }
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }
        
        $highlightStart = '<span class="highlightSearch">';
        $highlightEnd = '</span>';
        
        //$userContents->forSort($sortBy, $sortOrder);
            
        // $userContents = $userContents->get();

        if($hasSourceFilterAppliedForGroup)
        {
            $userContents = array();
        }
        else
        {
            $userContents = $userContents->get();
        }
            
        if(isset($filSenderEmail) && trim($filSenderEmail) != '')
        {
            $filSenderEmail = strtolower(trim($filSenderEmail));

            $tempUserContents = $userContents;

            $filEmailContentList = array();
            foreach($tempUserContents as $content) 
            {
                $filContentObj = $this->formulateContentObject($depMgmtObj, $isFolder, $content, $filSenderEmail, $hasFilters, $filShowAttachment, $filAttachmentExtArr);

                if(isset($filContentObj)) {
                    array_push($filEmailContentList, $content);
                }       
            }
            
            $userContents = $filEmailContentList;
        }
        
        $contentList = array();
        foreach($userContents as $content) 
        {
            $contentObj = $this->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);

            if(isset($contentObj)) {
                array_push($contentList, $contentObj);
            }       
        }
        
        $sortByContent = Config::get('app_config.sort_by_content');
        $sortByType = Config::get('app_config.sort_by_type');
        $sortByCreateDate = Config::get('app_config.sort_by_create_date');
        $sortByUpdateDate = Config::get('app_config.sort_by_update_date');
        $sortByDueDate = Config::get('app_config.sort_by_due_date');
        $sortByFolder = Config::get('app_config.sort_by_folder');
        $sortByTag = Config::get('app_config.sort_by_tag');
        $sortBySize = Config::get('app_config.sort_by_size');

        if($sortBy == $sortByContent) {
            $sortByColName = 'strippedContent';
        }
        elseif($sortBy == $sortByType) {
            $sortByColName = 'contentType';
        }
        elseif($sortBy == $sortByCreateDate) {
            $sortByColName = 'createUtc';
        }
        elseif($sortBy == $sortByUpdateDate) {
            $sortByColName = 'updateUtc';
        }
        elseif($sortBy == $sortByDueDate) {
            $sortByColName = 'startUtc';
        }
        elseif($sortBy == $sortByFolder) {
            $sortByColName = 'folderName';
        }
        elseif($sortBy == $sortByTag) {
            $sortByColName = 'tagStr';
        }
        elseif($sortBy == $sortBySize) {
            $sortByColName = 'contentSize';
        }
        
        if(!isset($sortByColName))
        {
            $sortByColName = 'updateUtc';
        }
        
        $contentListCollection = collect($contentList);
        $sortedContentList;
        //$sortedContentList = $contentListCollection->sortByDesc('isMarked');
        if(isset($sortOrder) && $sortOrder > 0) {
            
            $sortedContentList = $contentListCollection->sortBy($sortByColName, SORT_REGULAR|SORT_NATURAL|SORT_FLAG_CASE);//->sortByDesc('isMarked');//->groupBy('is_marked');
            
            //$response['inIf'] = "in if $sortByColName";
        }
        else {
            $sortedContentList = $contentListCollection->sortByDesc($sortByColName, SORT_REGULAR|SORT_NATURAL|SORT_FLAG_CASE);//->sortByDesc('isMarked');
            //$sortedContentList = $contentListCollection->sortByDesc('isMarked')->sortByDesc($sortByColName);//->groupBy('is_marked');
            //$response['inIf'] = "in else $sortByColName";
        }
        //$sortedContentList = $sortedContentList->sortByDesc('isMarked');
        $contentList = $sortedContentList->toArray();
        
        return $contentList;
    }

    
    public function formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment = -1, $filAttachmentExtArr)
    {
        $contentObj = NULL;

        if(isset($content))
        {
            $typeR = Config::get("app_config.content_type_r");
            $typeC = Config::get("app_config.content_type_c");
            $orgKey = $depMgmtObj->getOrgEmpKey();
            $orgProfileKey = $depMgmtObj->getOrgProfileKey();

            $tagsArr = array();
            $contentId = $content->content_id;

            $contentText = "";
            if(isset($content->content) && $content->content != "")
            {
                try
                {
                    $contentText = Crypt::decrypt($content->content);
                } 
                catch (\Exception $e) 
                {

                }
            }

            $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);

            $folderName = $content->folder_name; 
            $sourceName = $content->source_name; 
            $groupName = $content->name;
            $tagStr = $content->tagStr;

            $orgFolderName = $folderName;
            $orgGroupName = $groupName;

            if($orgKey != "")
            {
                $organization = $depMgmtObj->getOrganizationObject();
                $orgEmp = $depMgmtObj->getEmployeeObject();

                if(isset($organization) && isset($orgEmp))
                {
                    $orgName = $organization->system_name . " - " . $orgEmp->employee_name;

                    if($isFolder)
                    {
                        $orgFolderName = $orgFolderName . " - " . $orgName;
                    }
                    else
                    {
                        $orgGroupName = $orgGroupName . " - " . $orgName;
                    }
                }
            }

            $tagNameArr = [];
            if($tagStr != "" && $tagStr != "-")
            {
                $tagNameArr = explode(", ",$tagStr);
            }

            $addContent = FALSE;
            $filteredAddContent = FALSE; 

            $canAddForSearch = FALSE;
            $canAddForAttExt = FALSE;
            $canAddForShowAtt = FALSE;

            $hasAttachment = 0;
            $contentSize = 0;
            $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);                  
            if(isset($contentAttachments))
            {
                foreach($contentAttachments as $attachment) {
                    $hasAttachment = 1;
                    $contentSize += $attachment->filesize;

                    // if(!$addContent && $searchStr != "" && strpos(strtolower($attachment->filename), $searchStr) !== false) 
                    if($searchStr != "" && strpos(strtolower($attachment->filename), $searchStr) !== false) 
                    {
                        $addContent = TRUE;

                        $canAddForSearch = TRUE;
                    }
                    else if($searchStr == "")
                    {
                        $canAddForSearch = TRUE;
                    }

                    if($hasFilters == 1 && isset($filAttachmentExtArr) && count($filAttachmentExtArr) > 0)
                    {
                        $attExt = getExtensionStringFromFilename($attachment->filename);

                        if(in_array($attExt, $filAttachmentExtArr)) 
                        {
                            $filteredAddContent = TRUE;

                            $canAddForAttExt = TRUE;
                        }
                    }
                }
            }

            if($hasFilters == 1 && isset($filAttachmentExtArr) && count($filAttachmentExtArr) > 0)
            {

            }
            else
            {
                $canAddForAttExt = TRUE;
            }
            
            if($hasFilters == 1 && (isset($filShowAttachment) && $filShowAttachment >= -1))
            {
                if($filShowAttachment == 0 && $hasAttachment == 0)
                {
                    $filteredAddContent = TRUE;
                   
                    $canAddForShowAtt = TRUE;
                }
                else if($filShowAttachment == 1 && $hasAttachment == 1)
                {
                    $filteredAddContent = TRUE;
                   
                    $canAddForShowAtt = TRUE;
                }
                else if($filShowAttachment == -1)
                {
                    $filteredAddContent = TRUE;
                   
                    $canAddForShowAtt = TRUE;
                }
            }
            else
            {
                $canAddForShowAtt = TRUE;
            }

            if($searchStr != "") {
                if (!$addContent && strpos(strtolower($strippedContentText), $searchStr) !== false)
                {
                    $addContent = TRUE;

                    $canAddForSearch = TRUE;
                }
                else if(!$addContent && $isFolder && strpos(strtolower($folderName), $searchStr) !== false)
                {
                    $addContent = TRUE;

                    $canAddForSearch = TRUE;
                }
                else if(!$addContent && $isFolder && strpos(strtolower($sourceName), $searchStr) !== false)
                {
                    $addContent = TRUE;

                    $canAddForSearch = TRUE;
                }
                else if(!$addContent && !$isFolder && strpos(strtolower($groupName), $searchStr) !== false)
                {
                    $addContent = TRUE;

                    $canAddForSearch = TRUE;
                }
                else if(!$addContent && strpos(strtolower($tagStr), $searchStr) !== false)
                {
                    $addContent = TRUE;

                    $canAddForSearch = TRUE;
                }
            }
            else
            {
                $addContent = TRUE;

                $canAddForSearch = TRUE;
            }

            if($hasFilters == 1 && ((isset($filAttachmentExtArr) && count($filAttachmentExtArr) > 0) || (isset($filShowAttachment) && $filShowAttachment >= 0))) 
            {
                $addContent = $filteredAddContent;
            }

            $addContent = TRUE;
            if(!$canAddForSearch || !$canAddForAttExt || !$canAddForShowAtt)
            {
                $addContent = FALSE;
            }

            if($addContent)
            {           
                $folderIconCode = $content->icon_code; 
                if($isFolder && (!isset($folderName) || !isset($folderIconCode)))
                {
                    $folderObj = $depMgmtObj->getFolderObject($content->folder_id);
                    if(isset($folderObj))
                    {
                        $folderName = $folderObj->folder_name;
                        $folderIconCode = $folderObj->icon_code;
                    }
                }                   

                if(!$isFolder && !isset($groupName))
                {
                    $groupObj = $depMgmtObj->getGroupObject($content->group_id);
                    if(isset($groupObj))
                        $groupName = $groupObj->name;
                }
                
                $isConversation = 0;
                $contentSenderStr = '';
                $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($strippedContentText);
                $contentConversationDetails = $contentConversationResponse['conversation'];
                if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
                {
                    $isConversation = 1;
                    $strippedContentText = $contentConversationDetails[0]['content'];
                    $contentSenderStr = $contentConversationDetails[0]['sender'];
                }

                $dispContentTextLength = 180;

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

                $startUtc = 0;
                $endUtc = 0;
                $contentTypeId = $content->content_type_id;
                if($contentTypeId == $typeR || $contentTypeId == $typeC)
                {
                    $startUtc = $content->from_timestamp;
                    if($contentTypeId == $typeC) {
                        $endUtc = $content->to_timestamp;
                    }                       
                }
                
                if($searchStr != "") {
                    if (strpos(strtolower($strippedContentText), $searchStr) !== false) {
                        $strippedContentText = $this->searchAndHighlightText($searchStr, $strippedContentText);
                    }
                    if($isFolder && strpos(strtolower($folderName), $searchStr) !== false) {
                        $folderName = $this->searchAndHighlightText($searchStr, $folderName);
                    }
                    else if($isFolder && strpos(strtolower($sourceName), $searchStr) !== false) {
                        $sourceName = $this->searchAndHighlightText($searchStr, $sourceName);
                    }
                    else if(!$isFolder && strpos(strtolower($groupName), $searchStr) !== false) {
                        $groupName = $this->searchAndHighlightText($searchStr, $groupName);
                    }
                    else if(strpos(strtolower($tagStr), $searchStr) !== false) {
                        $tagStr = $this->searchAndHighlightText($searchStr, $tagStr);
                    }
                }
                
                $isFolderFlag = $isFolder == true ? 1 : 0;

                $sanStrippedContentText = $strippedContentText;
                // $sanStrippedContentText = htmlspecialchars($sanStrippedContentText);
                $sanStrippedContentText = utf8_encode($sanStrippedContentText);
                // $sanStrippedContentText = preg_replace("/[^a-zA-Z0-9\s@\-_.()\[\]\<\>;:]+/", "", $sanStrippedContentText);

                if($this->withEncryption)
                {
                    $contentId = sracEncryptNumberData($contentId, $this->userSessionObj);
                }

                $contentObj = array();
                $contentObj['id'] = $contentId;
                $contentObj['colorCode'] = $content->color_code;
                $contentObj['isLocked'] = $content->is_locked;
                $contentObj['isShareEnabled'] = $content->is_share_enabled;
                //$contentObj['content'] = utf8_encode($contentText);
                $contentObj['strippedContent'] = utf8_encode($strippedContentText); // rawurlencode
                $contentObj['strippedContentText'] = $sanStrippedContentText;
                $contentObj['strippedContentAsIs'] = $strippedContentText;
                $contentObj['contentType'] = $contentTypeId;
                $contentObj['isMarked'] = $content->is_marked;
                $contentObj['isCompleted'] = $content->is_completed;
                $contentObj['isRepeatEnabled'] = $content->repeat_duration != "" ? 1 : 0;
                $contentObj['repeatDurationStr'] = $content->repeat_duration;
                $contentObj['folderName'] = $folderName;
                $contentObj['orgId'] = $orgKey;
                $contentObj['folderIconCode'] = $folderIconCode;
                $contentObj['groupName'] = $groupName;
                $contentObj['tagStr'] = $tagStr;
                $contentObj['tagArr'] = $tagNameArr;
                $contentObj['hasAttachment'] = $hasAttachment;
                $contentObj['startUtc'] = $startUtc;
                $contentObj['endUtc'] = $endUtc;
                $contentObj['createUtc'] = $content->create_timestamp;
                $contentObj['updateUtc'] = $content->update_timestamp;
                $contentObj['contentSize'] = $contentSize;  
                $contentObj['senderStr'] = utf8_encode($contentSenderStr);
                $contentObj['isFolder'] = $isFolderFlag;
                $contentObj['orgFolderName'] = $orgFolderName;  
                $contentObj['orgGroupName'] = $orgGroupName;  
                $contentObj['orgProfileKey'] = $orgProfileKey;
                $contentObj['colorCode'] = $content->color_code;
                $contentObj['isRemoved'] = $content->is_removed;
                $contentObj['removedAt'] = $content->removedAt;
                $contentObj['contentConversationResponse'] = $contentConversationResponse;
                $contentObj['isConversation'] = $isConversation;
            }   
        }            
        
        return $contentObj;
    }
    
    public function searchAndHighlightText($searchStr, $mainStr, $useDecoded = TRUE) 
    {
        $highlightedStr = $mainStr;
        
        if(isset($highlightedStr) && $highlightedStr != "") {
            $startIndex = strpos(strtolower($mainStr), $searchStr);
            
            if($startIndex !== FALSE && $startIndex >= 0){
                $endIndex = $startIndex + strlen($searchStr);
                
                if($useDecoded) {
                    $startTagReplacement = "<span class='searchHighlighted'>";
                    $endTagReplacement = "</span>";
                }
                else {
                    $startTagReplacement = "&lt;span class='searchHighlighted'&gt;";
                    $endTagReplacement = "&lt;/span&gt;";
                }
                
                $highlightedStr = substr_replace($highlightedStr, $endTagReplacement, $endIndex, 0);
                $highlightedStr = substr_replace($highlightedStr, $startTagReplacement, $startIndex, 0);
            }
        }           
        
        return $highlightedStr;     
    }

    public function formulateFromAndToTimestampForRepeatEntryOfContent($contentObj, $consDateTs)
    {
        if(isset($contentObj) && isset($consDateTs))
        {
            $consDateTs = $consDateTs / 1000;

            if(isset($contentObj['startUtc']) && $contentObj['startUtc'] != "")
            {
                $exisitngFromTs = $contentObj['startUtc'] / 1000;
                $existingFromDtObj = Carbon::createFromTimeStampUTC($exisitngFromTs);

                $consFromDtObj = Carbon::createFromTimeStampUTC($consDateTs);
                $consFromDtObj->hour = ($existingFromDtObj->format("H") * 1);
                $consFromDtObj->minute = ($existingFromDtObj->format("i") * 1);
                $consFromDtObj->second = 0;
                $consFromTimeStamp = $consFromDtObj->timestamp;                   
                $consFromTs = $consFromTimeStamp * 1000;

                $contentObj['startUtc'] = $consFromTs;
            }

            if(isset($contentObj['endUtc']) && $contentObj['endUtc'] != "")
            {
                $exisitngToTs = $contentObj['endUtc'] / 1000;
                $existingToDtObj = Carbon::createFromTimeStampUTC($exisitngToTs);

                $dayDiff = isset($existingFromDtObj) ? $existingToDtObj->diffInDays($existingFromDtObj) : 0;                
            
                $consToDtObj = Carbon::createFromTimeStampUTC($consDateTs);
                $consToDtObj->hour = ($existingToDtObj->format("H")) * 1;
                $consToDtObj->minute = ($existingToDtObj->format("i")) * 1;
                $consToDtObj->second = 0;
                $consToDtObj = $consToDtObj->addDays($dayDiff);
                $consToTimeStamp = $consToDtObj->timestamp;                   
                $consToTs = $consToTimeStamp * 1000;

                $contentObj['endUtc'] = $consToTs;  
            }          
        }

        return $contentObj;
    }

}
