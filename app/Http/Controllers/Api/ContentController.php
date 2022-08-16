<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\FolderType;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserContact;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentImage;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
use App\Models\Api\CloudCalendarType;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Org\OrganizationUser;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
use App\Libraries\MailClass;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\FolderFilterUtilClass;
use App\Libraries\ContentListFormulationClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use App\Libraries\AttachmentCloudStorageManagementClass;
use App\Libraries\CloudMailBoxManagementClass;

class ContentController extends Controller
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
        $encConOrgId = Input::get('conOrgId');
        $id = (Input::get('id'));
        $content = Input::get('content');
        $title_note=Input::get('title_note');//_CHANGES
        $oldContent = Input::get('oldContent');
        $contentType = Input::get('contentType');
        $folderId = (Input::get('folderId'));
        $folderSyncPending = Input::get('folderSyncPending');
        $folderName = Input::get('folderName');
        $folderIconCode = Input::get('iconCode');
        $folderIsFavorited = Input::get('folderIsFavorited');
        $sourceId = (Input::get('sourceId'));
        $sourceSyncPending = Input::get('sourceSyncPending');
        $sourceName = Input::get('sourceName');
        $tagList = (Input::get('tagList'));
        $tagSyncPending = Input::get('tagSyncPending');
        $tagNameArr = Input::get('tagNameArr');
        $isMarked = Input::get('isMarked');
        $createTimeStamp = Input::get('createTimeStamp');
        $updateTimeStamp = Input::get('updateTimeStamp');
        $fromTimeStamp = Input::get('fromTimeStamp');
        $toTimeStamp = Input::get('toTimeStamp');
        $removeAttachmentIdArr = (Input::get('removeAttachmentIdArr'));
        $modifiedAttachmentIdArr = (Input::get('modifiedAttachmentIdArr'));
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
        $isRemoved = Input::get('isRemoved');
        $syncWithCloudCalendarGoogle = Input::get('syncWithCloudCalendarGoogle');
        $syncWithCloudCalendarOnedrive = Input::get('syncWithCloudCalendarOnedrive');

        $isMetaUpdate = Input::get('isMetaUpdate');

        if(is_array($tagList))
            $tagsArr = $tagList;
        else
            $tagsArr = json_decode($tagList);

        if(is_array($tagNameArr))
            $tagNameArr = $tagNameArr;
        else
            $tagNameArr = json_decode($tagNameArr);
        
        if(!is_array($removeAttachmentIdArr))
            $removeAttachmentIdArr = json_decode($removeAttachmentIdArr);
            
        if(!is_array($modifiedAttachmentIdArr))
            $modifiedAttachmentIdArr = json_decode($modifiedAttachmentIdArr);
            
        $content = urldecode($content);
        $oldContent = urldecode($oldContent);
        
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
        
        if(!isset($folderIsFavorited) || $folderIsFavorited * 1 != 1) {
            $folderIsFavorited = 0;
        }
        
        if(isset($encConOrgId)) {
            $encOrgId = $encConOrgId;
        }
        
        if(!isset($noContentTextModification) || $noContentTextModification * 1 != 1) {
            $noContentTextModification = 0;
        }
        
        if(!isset($isRemoved) || $isRemoved != 1) {
            $isRemoved = 0;
            $removedAt = NULL;
        }
        
        if(!isset($syncWithCloudCalendarGoogle) || $syncWithCloudCalendarGoogle * 1 != 1) {
            $syncWithCloudCalendarGoogle = 0;
        }
        
        if(!isset($syncWithCloudCalendarOnedrive) || $syncWithCloudCalendarOnedrive * 1 != 1) {
            $syncWithCloudCalendarOnedrive = 0;
        }
        
        $response = array();

        if($encUserId != "" && $content != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                $folderId = sracDecryptNumberData($folderId, $userSession);
                $sourceId = sracDecryptNumberData($sourceId, $userSession);

                $tagsArr = sracDecryptNumberArrayData($tagsArr, $userSession);
                $removeAttachmentIdArr = sracDecryptNumberArrayData($removeAttachmentIdArr, $userSession);
                $modifiedAttachmentIdArr = sracDecryptNumberArrayData($modifiedAttachmentIdArr, $userSession);
                 
                $status = 1;
                $msg = "";
                $isFolder = TRUE; 
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);

                if(!isset($isMarked))
                    $isMarked = 0;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $userContent = $depMgmtObj->getContentObject($id, $isFolder);

                $sendIsContentTypeChangedChat = false;
                $isContentTypeChanged = false;
                $contentTypeChangedStr = "";
                if(isset($userContent))
                {
                    $oldContentTypeId = $userContent->content_type_id;
                    if($contentType != $oldContentTypeId)
                    {
                        $isContentTypeChanged = true;
                        $oldContentTypeText = $depMgmtObj->getContentTypeText($oldContentTypeId);
                        $newContentTypeText = $depMgmtObj->getContentTypeText($contentType);

                        $contentTypeChangedStr = "Content type changed from ".$oldContentTypeText." to ".$newContentTypeText;

                        $orgContentText = "";
                        if(isset($userContent->content) && $userContent->content != "")
                        {
                            try
                            {
                                $orgContentText = Crypt::decrypt($userContent->content);
                            } 
                            catch (DecryptException $e) 
                            {
                                //
                                $response['DecryptException'] = $e;
                            }
                        }

                        $contentTextIsShared = $depMgmtObj->checkIfContentIsSharedFromContentText($orgContentText);

                        if($contentTextIsShared == 1)
                        {
                            $sendIsContentTypeChangedChat = true;
                            $newContentTextIsShared = $depMgmtObj->checkIfContentIsSharedFromContentText($content);

                            if($newContentTextIsShared == 0)
                            {
                                $sharedByUserName = $depMgmtObj->getEmployeeOrUserName();
                                $sharedByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();
                                $appendedContentText = CommonFunctionClass::getSharedByAppendedString($content, $updateTimeStamp, $sharedByUserName, $sharedByUserEmail);
                                $content = $appendedContentText;
                            }
                        }
                    }
                        
                }

                $isWebRequest = CommonFunctionClass::isSessionWebRequest($userSession);
                $contentTypeIdCalendar = Config::get('app_config.content_type_c');
                if(!isset($userContent) && $isWebRequest && $contentType == $contentTypeIdCalendar)
                {
                    if($syncWithCloudCalendarGoogle == 1)
                    {
                        $cloudCalendarTypeCodeGoogle = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
                        $cloudCalendarTypeGoogle = $depMgmtObj->getCloudCalendarTypeObjectByCode($cloudCalendarTypeCodeGoogle);

                        if(isset($cloudCalendarTypeGoogle))
                        {
                            $cloudCalendarTypeNameGoogle = $cloudCalendarTypeGoogle->cloud_calendar_type_name;

                            $tagWithCloudCalendarTypeNameGoogle = $depMgmtObj->getTagObjectByName($cloudCalendarTypeNameGoogle);
                            if(!isset($tagWithCloudCalendarTypeNameGoogle))
                            {
                                $tagCalendarGoogleResponse = $depMgmtObj->addEditTag(0, $cloudCalendarTypeNameGoogle);
                                $cloudCalendarTypeNameTagIdGoogle = $tagCalendarGoogleResponse['syncId'];
                            }
                            else
                            {
                                $cloudCalendarTypeNameTagIdGoogle = $orgId > 0 ? $tagWithCloudCalendarTypeNameGoogle->employee_tag_id : $tagWithCloudCalendarTypeNameGoogle->appuser_tag_id;
                            }

                            if(isset($cloudCalendarTypeNameTagIdGoogle))
                            {
                                array_push($tagsArr, $cloudCalendarTypeNameTagIdGoogle);
                            }
                        }

                    }

                    if($syncWithCloudCalendarOnedrive == 1)
                    {
                        $cloudCalendarTypeCodeMicrosoft = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;
                        $cloudCalendarTypeMicrosoft = $depMgmtObj->getCloudCalendarTypeObjectByCode($cloudCalendarTypeCodeMicrosoft);

                        if(isset($cloudCalendarTypeMicrosoft))
                        {
                            $cloudCalendarTypeNameMicrosoft = $cloudCalendarTypeMicrosoft->cloud_calendar_type_name;

                            $tagWithCloudCalendarTypeNameMicrosoft = $depMgmtObj->getTagObjectByName($cloudCalendarTypeNameMicrosoft);
                            if(!isset($tagWithCloudCalendarTypeNameMicrosoft))
                            {
                                $tagCalendarMicrosoftResponse = $depMgmtObj->addEditTag(0, $cloudCalendarTypeNameMicrosoft);
                                $cloudCalendarTypeNameTagIdMicrosoft = $tagCalendarMicrosoftResponse['syncId'];
                            }
                            else
                            {
                                $cloudCalendarTypeNameTagIdMicrosoft = $orgId > 0 ? $tagWithCloudCalendarTypeNameMicrosoft->employee_tag_id : $tagWithCloudCalendarTypeNameMicrosoft->appuser_tag_id;
                            }

                            if(isset($cloudCalendarTypeNameTagIdMicrosoft))
                            {
                                array_push($tagsArr, $cloudCalendarTypeNameTagIdMicrosoft);
                            }
                        }
                        
                    }
                }

                if(!isset($userContent) || (isset($userContent) && $userContent->is_removed == 0))// && $userContent->is_locked == 0))
                {
                    $folderResponse = array();
                    if(isset($folderSyncPending) && $folderSyncPending == 1)
                    {
                        $folderResponse = $depMgmtObj->addEditFolder(0, $folderName, $folderIconCode, $folderIsFavorited);
                        $folderId = $folderResponse["syncId"];
                    }
                    
                    $sourceResponse = array();
                    if(isset($sourceSyncPending) && $sourceSyncPending == 1)
                    {
                        if(!isset($sourceId))
                            $sourceId = 0;
                            
                        $sourceResponse = $depMgmtObj->addEditSource($sourceId, $sourceName);
                        $sourceId = $sourceResponse["syncId"];
                    }
                    
                    $tagResponse = array();
                    if(isset($tagSyncPending) && $tagSyncPending == 1)
                    {
                        foreach($tagNameArr as $tagName)
                        {
                            $indTagResponse = $depMgmtObj->addEditTag(0, $tagName);
                            $tagId = $indTagResponse["syncId"];
                            array_push($tagsArr, $tagId);                       
                            array_push($tagResponse, $indTagResponse);                      
                        }
                    }
                
                    $response = $depMgmtObj->addEditContent($id, $content, $title_note, $contentType, $folderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, "", $syncWithCloudCalendarGoogle, $syncWithCloudCalendarOnedrive, $isRemoved, $removedAt);
                    $syncId = $response["syncId"];
                    if($syncId > 0)
                    {
                        if(isset($modifiedAttachmentIdArr))
                        {
                            foreach($modifiedAttachmentIdArr as $attId)
                            {
                                $depMgmtObj->setContentAttachmentIsModified($attId, $isFolder, 1);
                            }
                        }
                        
                        
                        if(isset($folderSyncPending) && $folderSyncPending == 1)
                        {
                            $folderResponse['syncId'] = sracEncryptNumberData($folderResponse['syncId'], $userSession);
                            $response['folderResponse'] = $folderResponse;
                        }
                        
                        if(isset($sourceSyncPending) && $sourceSyncPending == 1)
                        {
                            $sourceResponse['syncId'] = sracEncryptNumberData($sourceResponse['syncId'], $userSession);
                            $response['sourceResponse'] = $sourceResponse;
                        }
                        
                        if(isset($tagSyncPending) && $tagSyncPending == 1)
                        {
                            foreach ($tagResponse as $indTagResponse) {
                                $indTagResponse['syncId'] = sracEncryptNumberData($indTagResponse['syncId'], $userSession);
                            }
                            $response['tagResponse'] = $tagResponse;
                        }

                        if($sendIsContentTypeChangedChat)
                        {
                            $updUserContent = $depMgmtObj->getContentObject($syncId, $isFolder);
                            
                            $createTimeStamp = CommonFunctionClass::getCreateTimestamp();

                            $isChangeLogOp = 1;
                            $changeLogText = "";
                            $isDeleteOp = 0;
                            $isReplyOp = 1;
                            $isEditOp = 0;
                            $conversationIndex = -1;
                            $updateTs = $createTimeStamp;
                            $editText = '';
                            $replyText = $contentTypeChangedStr;

                            $convOpParams = array();
                            $convOpParams['isChangeLogOp'] = $isChangeLogOp;
                            $convOpParams['changeLogText'] = $changeLogText;
                            $convOpParams['isDeleteOp'] = $isDeleteOp;
                            $convOpParams['isReplyOp'] = $isReplyOp;
                            $convOpParams['replyText'] = $replyText;
                            $convOpParams['isEditOp'] = $isEditOp;
                            $convOpParams['editText'] = $editText;
                            $convOpParams['conversationIndex'] = $conversationIndex;
                            $convOpParams['updateTs'] = $updateTs;

                            $depMgmtObj->performContentConversationPartOperation($isFolder, $syncId, $updUserContent, $convOpParams);
                        }

                        
                        $sharedContentId = isset($userContent) ? $userContent->shared_content_id : 0;
                    
                        $currAttachmentCnt = $depMgmtObj->getContentAttachmentCount($syncId, $isFolder);
                        $modAttachmentCnt = $depMgmtObj->getModifiedContentAttachmentCnt($syncId, $isFolder);
                        if($attachmentCnt == $currAttachmentCnt && $modAttachmentCnt == 0)
                        {
                            //Send FCM to all
                            if($orgId > 0)
                            {                               
                                $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $syncId);    
                            }
                            else
                            {
                                $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $syncId); 
                            }
                        }
                            
                        if($sharedContentId > 0 || (isset($sendAsReply) && $sendAsReply == 1))
                        {
                            if($isMetaUpdate == 1)
                            {
                                $content = $depMgmtObj->sendFolderContentAsReply($syncId, $content, true, true);
                            }
                            else
                            {
                                $content = $depMgmtObj->sendFolderContentAsReply($syncId, NULL, true, true);
                            }
                        }
                    }
            
                    $response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
                    $response['syncContent'] = utf8_encode($content);
                    $response['encSyncContent'] = rawurlencode($content);

                }
                else if(isset($userContent))
                {
                    if($userContent->is_locked == 1)
                    {
                        $status = -1;
                        $msg = 'Locked content cannot be modified';    
                    }
                    else if($userContent->is_removed != 0)
                    {
                        $status = -1;
                        $msg = 'Removed content cannot be modified';    
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
     * Add Content.
     *
     * @return json array
     */
    public function saveOneLineChatContent()
    {
        // Log::info("===================================================== saveOneLineChatContent STARTS =====================================================");
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $encConOrgId = Input::get('conOrgId');
        $isFolderFlag = Input::get('isFolder');
        $folderOrGroupId = (Input::get('folderOrGroupId'));
        $withShare = Input::get('withShare');
        $content = Input::get('contentText');
        $loginToken = Input::get('loginToken');
        $sendToEmail = Input::get('sendToEmail');

        $isShareEnabled = Input::get('isShareEnabled');
        if(!isset($isShareEnabled) || ($isShareEnabled != 0 && $isShareEnabled != 1))
        {
            $isShareEnabled = Config::get('app_config.default_content_share_status');
        }

        $isLocked = Input::get('isLocked');
        if(!isset($isLocked) || ($isLocked != 0 && $isLocked != 1))
        {
            $isLocked = Config::get('app_config.default_content_lock_status');
        }

        $content = urldecode($content);

        if(isset($isFolderFlag) && $isFolderFlag != "" && !is_nan($isFolderFlag))
        {
            $isFolderFlag = $isFolderFlag * 1;
        }

        if(isset($encConOrgId)) {
            $encOrgId = $encConOrgId;
        }

        if(!isset($withShare))
        {
            $withShare = 0;
        }

        if(!isset($sendToEmail))
        {
            $sendToEmail = '';
        }
        
        $response = array();

        if($encUserId != "" && $content != "")
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

                $folderOrGroupId = sracDecryptNumberData($folderOrGroupId, $userSession);
                 
                $msg = "";
                $isFolder = TRUE;

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $orgEmpId = $depMgmtObj->getOrganizationEmployeeId();
                $defFolderId = $depMgmtObj->getDefaultFolderId();

                $newServerContentId = 0;
                if($defFolderId > 0)
                {
                    $status = 1;

                    $consIsFolder = TRUE;
                    $consFolderOrGroupId = $defFolderId;
                    $sharedByName = '';
                    $sharedByEmail = '';

                    $currUserOrEmpId = $depMgmtObj->getEmployeeOrUserId();
                    $currUserOrEmpName = $depMgmtObj->getEmployeeOrUserName();
                    $currUserOrEmpEmail = $depMgmtObj->getEmployeeOrUserEmail();

                    $consGroupId = 0;
                    $consGroupMemberId = 0;

                    if($folderOrGroupId > 0)
                    {
                        if($isFolderFlag == 1)
                        {
                            $folderObj = $depMgmtObj->getFolderObject($folderOrGroupId);
                            if(isset($folderObj))
                            {
                                $consIsFolder = TRUE;
                                $consFolderOrGroupId = $folderOrGroupId;
                            }
                        }
                        else
                        {
                            $groupObj = $depMgmtObj->getGroupObject($folderOrGroupId);
                            $groupMemberObj = $depMgmtObj->getGroupMemberDetailsObject($folderOrGroupId, FALSE);

                            if(isset($groupObj) && isset($groupMemberObj) && ($orgId == 0 || ($orgId > 0 && $groupMemberObj->has_post_right == 1)))
                            {
                                $consIsFolder = FALSE;
                                $consGroupId = $folderOrGroupId;
                                $consFolderOrGroupId = $folderOrGroupId;
                                $consGroupMemberId = $groupMemberObj->member_id;
                                $sharedByEmail = $currUserOrEmpEmail;
                                $sharedByName = $currUserOrEmpName;
                            }
                        }
                    }

                    // $consIsFolder = TRUE;
                    // $consFolderOrGroupId = $defFolderId;

                    $utcTz =  'UTC';
                    $createDateObj = Carbon::now($utcTz);
                    $createTimeStamp = $createDateObj->timestamp;                   
                    $createTimeStamp = $createTimeStamp * 1000;
                    $updateTimeStamp = $createTimeStamp;

                    $colorCode = Config::get('app_config.default_content_color_code');
                    $contentType = Config::get('app_config.content_type_a');
                    $folderId = $defFolderId;
                    $sourceId = 0;
                    $tagsArr = array();
                    $removeAttachmentIdArr = NULL;
                    $fromTimeStamp = "";
                    $toTimeStamp = "";
                    $isMarked = 0;
                    $remindBeforeMillis = 0;
                    $repeatDuration = 0;

                    $sharedByUserId = $depMgmtObj->getEmployeeOrUserId();
                    $sharedByUserEmail = $depMgmtObj->getEmployeeOrUserEmail();
                    $sharedByUserName = $depMgmtObj->getEmployeeOrUserName();
                    $contentAttachmentsArr = array();
                    $contentIsLocked = $isLocked;
                    $contentIsShareEnabled = $isShareEnabled;
                    $isCompleted = Config::get('app_config.default_content_is_completed_status');
                    $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
                    $reminderTimestamp = NULL;

                    $isSharePerformed = FALSE;
                    
                    $newServerContentId = 0;
                    
                    $contentAddResponse = NULL;
                    if($consIsFolder)
                    {
                        $contentAddResponse = $depMgmtObj->addEditContent(0, $content, $contentType, $consFolderOrGroupId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, NULL);
                    }
                    else
                    {
                        $appendedContentText = CommonFunctionClass::getSharedByAppendedString($content, $updateTimeStamp, $sharedByName, $sharedByEmail);

                        $contentAddResponse = $depMgmtObj->addEditGroupContent(0, $appendedContentText, $contentType, $consFolderOrGroupId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, $sharedByEmail);

                        $isSharePerformed = TRUE;
                        
                        $msg = 'Content saved successfully';
                    }

                    $newServerContentId = isset($contentAddResponse) && isset($contentAddResponse['syncId']) ? $contentAddResponse['syncId'] : 0;

                    if($newServerContentId > 0)
                    {
                        $isWebRequest = CommonFunctionClass::isSessionWebRequest($userSession);

                        $isContentShared = false;
                        $sharedContentDepMgmtObjArr = array();
                        $sharedContentIdArr = array();
                        $sharedContentIsFolder = false;
                        if(isset($sendToEmail) && trim($sendToEmail) != "")
                        {
                            $savedContentObj = $depMgmtObj->getContentObject($newServerContentId, $consIsFolder);

                            $sendToEmail = trim($sendToEmail);

                            if($orgId > 0)
                            {
                                $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
                                
                                $modelObj = New OrgEmployee;
                                $modelObj->setConnection($orgDbConName);
                                $mappedOrgEmployees = $modelObj->ofEmail($sendToEmail)->verified()->get();

                                if(isset($mappedOrgEmployees) && count($mappedOrgEmployees) > 0)
                                {
                                    foreach ($mappedOrgEmployees as $mappedOrgEmployee)
                                    {
                                        $sharedContentIsFolder = true;

                                        $isContentShared = true;
                                        $msg = 'Content sent successfully';

                                        $mappedOrgEmployeeId = $mappedOrgEmployee->employee_id;
                                        
                                        $recDepMgmtObj = New ContentDependencyManagementClass;
                                        $recDepMgmtObj->withOrgIdAndEmpId($orgId, $mappedOrgEmployeeId);   
                                        $sharedContentId = $recDepMgmtObj->createSentFolderContent($savedContentObj, $contentIsLocked, $contentIsShareEnabled, $content, $contentAttachmentsArr, $currUserOrEmpEmail, $currUserOrEmpId);
                                        $recDepMgmtObj->setContentSharedContentId(0, $sharedContentIsFolder, $sharedContentId, 0);

                                        array_push($sharedContentIdArr, $sharedContentId);
                                        array_push($sharedContentDepMgmtObjArr, $recDepMgmtObj);
                                    }
                                }
                            }
                            else
                            {
                                $mappedAppuser = Appuser::verified()->ofEmail($sendToEmail)->first();
                                
                                if(isset($mappedAppuser))
                                {
                                    $sharedContentIsFolder = true;

                                    $isContentShared = true;
                                    $msg = 'Content sent successfully';

                                    $recDepMgmtObj = New ContentDependencyManagementClass;
                                    $recDepMgmtObj->withOrgKey($mappedAppuser, "");
                                    $sharedContentId = $recDepMgmtObj->createSentFolderContent($savedContentObj, $contentIsLocked, $contentIsShareEnabled, $content, $contentAttachmentsArr, $currUserOrEmpEmail, $currUserOrEmpId);
                                    $recDepMgmtObj->setContentSharedContentId(0, $sharedContentIsFolder, $sharedContentId, 0);

                                    array_push($sharedContentIdArr, $sharedContentId);
                                    array_push($sharedContentDepMgmtObjArr, $recDepMgmtObj);
                                }
                            }

                        }
                        
                        // if($consGroupId > 0)
                        // {
                        //     $depMgmtObj->createSentGroupContent($consGroupId, $consGroupMemberId, $savedContentObj, $contentIsLocked, $contentIsShareEnabled, $content, $contentAttachmentsArr, $sharedByUserName, $sharedByUserEmail);

                        //     $isSharePerformed = TRUE;
                            
                        //     $msg = 'Content saved successfully';
                        // }
                        
                        $isAdd = TRUE;
                        $forceSendToAllDevice = TRUE;

                        if($isContentShared && count($sharedContentIdArr) > 0)
                        {
                            $depMgmtObj->deleteContent($newServerContentId, $consIsFolder, NULL, FALSE);

                            foreach ($sharedContentIdArr as $key => $sharedContentId)
                            {
                                $sharedContentDepMgmtObj = $sharedContentDepMgmtObjArr[$key];
                                $sharedContentDepMgmtObj->sendRespectiveContentModificationPush($sharedContentIsFolder, $sharedContentId, $isAdd, $sharedByEmail, $forceSendToAllDevice);
                            }
                        }
                        else
                        {
                            $depMgmtObj->sendRespectiveContentModificationPush($consIsFolder, $newServerContentId, $isAdd, $sharedByEmail, $forceSendToAllDevice);
                        }

                        // if(!$isWebRequest)
                        {
                            $decContent = $content;
                            $encDecContent = "";
                            try
                            {
                                $encDecContent = rawurlencode($decContent);
                                $decContent = utf8_encode($decContent);
                            } 
                            catch (DecryptException $e) 
                            {
                                //
                            }

                            $compContentDetails = array();
                            $compContentDetails['content'] = $decContent;
                            $compContentDetails['colorCode'] = $colorCode;
                            $compContentDetails['isLocked'] = $isLocked;
                            $compContentDetails['isShareEnabled'] = $isShareEnabled;
                            $compContentDetails['remindBeforeMillis'] = $remindBeforeMillis;
                            $compContentDetails['repeatDuration'] = $repeatDuration;
                            $compContentDetails['isCompleted'] = $isCompleted;
                            $compContentDetails['isSnoozed'] = $isSnoozed;
                            $compContentDetails['reminderTimestamp'] = $reminderTimestamp;
                            $compContentDetails['encContent'] = $encDecContent;
                            $compContentDetails['contentType'] = $contentType;
                            $compContentDetails['isMarked'] = $isMarked;
                            $compContentDetails['createTimeStamp'] = $createTimeStamp;
                            $compContentDetails['updateTimeStamp'] = $updateTimeStamp;
                            $compContentDetails['fromTimeStamp'] = $fromTimeStamp;
                            $compContentDetails['toTimeStamp'] = $toTimeStamp;
                            $compContentDetails['tagCnt'] = count($tagsArr);
                            $compContentDetails['tags'] = sracEncryptNumberArrayData($tagsArr, $userSession);
                            $compContentDetails['attachmentCnt'] = 0;
                            $compContentDetails['attachments'] = array();
                            $compContentDetails['sharedByEmail'] = $sharedByEmail;

                            if($isFolder)
                            {
                                $compContentDetails['isFolder'] = 1;
                                $compContentDetails['folderId'] = sracEncryptNumberData($consFolderOrGroupId, $userSession);
                            }
                            else
                            {
                                $compContentDetails['isFolder'] = 0;
                                $compContentDetails['groupId'] = sracEncryptNumberData($consFolderOrGroupId, $userSession);
                            }

                            $response['contentDetails'] = $compContentDetails;
                        }
                    }
                    
                    
                    // $response['defFolderId'] = $defFolderId;
                    // $response['isFolderFlag'] = $isFolderFlag;
                    // $response['folderOrGroupId'] = $folderOrGroupId;

                    // $response['consIsFolder'] = $consIsFolder;
                    // $response['consFolderOrGroupId'] = $consFolderOrGroupId;

                    // $response['consGroupId'] = $consGroupId;
                    // $response['consGroupMemberId'] = $consGroupMemberId;
                
                    $response['contentId'] = sracEncryptNumberData($newServerContentId, $userSession);
                    $response['isSharedSuccessfully'] = $isSharePerformed;
                }
                else
                {
                    $status = -1;
                    $msg = "Unable to save content";
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
        // Log::info("===================================================== saveOneLineChatContent ENDS =====================================================");

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Filtered Content List.
     *
     * @return json array
     */
    public function contentList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $folderId = Input::get('folderId');
        $groupId = Input::get('groupId');
        $isFolderFlag = Input::get('isFolder');
        $isLockedFlag = Input::get('isLocked');
        $offset = Input::get('ofs');
        $searchStr = Input::get('searchStr');
        $sortBy = Input::get('sortBy');
        $sortOrder = Input::get('sortOrder');
        $hasFilters = Input::get('hasFilters');
        $loginToken = Input::get('loginToken');
        $isAttachmentView = Input::get('isAttachmentView');
        $isFavoritesTab = Input::get('isFavoritesTab');

        // Log::info('folderId : '.$folderId.' : isFolderFlag : '.$isFolderFlag);

        if(!isset($isAttachmentView) && $isAttachmentView != 1)
        {
            $isAttachmentView = 0;
        }

        $overrideOffset = FALSE;
        if($isAttachmentView == 1)
        {
            $offset = 0;
            $overrideOffset = TRUE;
        }
        
        $filAttachmentExtArr = array();
        
        $filFolderArr = null;
        $filGroupArr = null;
        $filSourceArr = null;
        $filTypeArr = null;
        $filAttachmentTypeArr = null;
        $filTagArr = null;
        $filFromDate = null;
        $filToDate = null;
        $isStarred = null; 
        $chkIsUntagged = null;
        $chkIsLocked = null;
        $chkIsConversation = null;
        $chkShowFolder = null;
        $chkShowGroup = null;
        $chkIsRestricted = null;
        $chkIsUnread = null;
        $filShowAttachment = null;
        $filRepeatStatus = null;
        $filCompletedStatus = null;
        
        if(isset($hasFilters) && $hasFilters == 1)
        {
            $filFolderArr = Input::get('filFolderArr');
            $filGroupArr = Input::get('filGroupArr');
            $filSourceArr = Input::get('filSourceArr');
            $filTypeArr = Input::get('filTypeArr');
            $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
            $filTagArr = Input::get('filTagArr');
            $filFromDate = Input::get('fromTimeStamp');
            $filToDate = Input::get('toTimeStamp');  
            $isStarred = Input::get('chkIsStarred');  
            $chkIsUntagged = Input::get('chkIsUntagged');
            $chkIsLocked = Input::get('chkIsLocked');
            $chkIsConversation = Input::get('chkIsConversation');
            $chkShowFolder = Input::get('chkShowFolder');
            $chkShowGroup = Input::get('chkShowGroup');
            $chkIsUnread = Input::get('chkIsUnread');
            $chkIsRestricted = Input::get('chkIsRestricted');
            $filShowAttachment = Input::get('filShowAttachment');
            $filRepeatStatus = Input::get('filRepeatStatus');
            $filCompletedStatus = Input::get('filCompletedStatus');

            /*$filFolderArr = json_decode($filFolderArr);
            $filSourceArr = json_decode($filSourceArr);
            $filTypeArr = json_decode($filTypeArr);
            $filTagArr = json_decode($filTagArr);*/
            
            if(isset($filAttachmentTypeArr) && count($filAttachmentTypeArr) > 0) {
                $attachmentExtensionArr = Config::get('app_config.filter_attachment_type_extension_array');
                $extensionStr = '';
                foreach($filAttachmentTypeArr as $attachmentTypeId) {
                    if(isset($attachmentExtensionArr[$attachmentTypeId])) {
                        if($extensionStr != '')
                            $extensionStr .= ' ';
                            
                        $extensionStr .= $attachmentExtensionArr[$attachmentTypeId];
                    }
                }
                
                if($extensionStr != '') {
                    $filAttachmentExtArr = explode(' ', $extensionStr);
                    /*print_r('$filAttachmentExtArr : ');
                    print_r($filAttachmentExtArr);*/
                }           
            }     
        }
        
        $filArr = array();
        $filArr['filFolderArr'] = $filFolderArr;
        $filArr['filGroupArr'] = $filGroupArr;
        $filArr['filSourceArr'] = $filSourceArr;
        $filArr['filTagArr'] = $filTagArr;   
        $filArr['filTypeArr'] = $filTypeArr;   
        $filArr['filAttachmentExtArr'] = $filAttachmentExtArr;   
        $filArr['filFromDate'] = $filFromDate;   
        $filArr['filToDate'] = $filToDate;   
        $filArr['isStarred'] = $isStarred;   
        $filArr['chkIsUntagged'] = $chkIsUntagged;
        $filArr['chkIsLocked'] = $chkIsLocked;
        $filArr['chkIsConversation'] = $chkIsConversation;
        $filArr['chkShowFolder'] = $chkShowFolder;
        $filArr['chkShowGroup'] = $chkShowGroup;
        $filArr['chkIsUnread'] = $chkIsUnread;
        $filArr['chkIsRestricted'] = $chkIsRestricted;
        $filArr['filShowAttachment'] = $filShowAttachment;
        $filArr['filRepeatStatus'] = $filRepeatStatus;
        $filArr['filCompletedStatus'] = $filCompletedStatus;

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
                            
                $i = 0;
                $arrForSorting = array();
                $listSize = 20;
                $totalContentCount = 0;

                $contentListFormulationObj = New ContentListFormulationClass;
                $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $empOrUserId = $userId;
                if($orgId > 0)
                    $empOrUserId = $orgEmpId;
                
                
                $isAllNotes = FALSE;
                $isLocked = FALSE;
                
                $isFolder = FALSE;
                $folderOrGroupId = $groupId;
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                    $folderOrGroupId = $folderId;
                    
                    if(isset($isLockedFlag) && $isLockedFlag == 1) 
                    {
                        $isLocked = TRUE;
                    }
                    
                    if($folderOrGroupId < 0) {
                        $isAllNotes = TRUE;
                    }
                }
                // Log::info('folderOrGroupId : '.$folderOrGroupId);
                
                $showFolderHeader = FALSE;
                $showGroupHeader = FALSE;
                
                $contentList = array();
                if((!$isAllNotes) || ($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowFolder) && $chkShowFolder == 1)))))
                {
                    $contentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);
                    $showFolderHeader = TRUE;
                }
                
                /*print_r('<pre>after sortby<br/>');
                print_r($contentList);
                print_r('</pre>');*/
                
                $totalContentCount = count($contentList);
        
                if(!$overrideOffset)
                {
                    $contentList = array_slice($contentList, $offset, $listSize, FALSE);
                }
                
                $secContentList = array();
                if($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowGroup) && $chkShowGroup == 1)))) {
                    
                    $usrGroupArr = $depMgmtObj->getAllGroupsFoUser();
                    $usrGroupIdArr = array();
                    foreach($usrGroupArr as $usrGroup) {
                        array_push($usrGroupIdArr, $usrGroup->group_id);
                    }
                    $filArr['filAllNotesGroupArr'] = $usrGroupIdArr;
                    
                    $secContentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, FALSE, $folderOrGroupId, $isAllNotes, $isLocked, 1, $filArr, $searchStr, $sortBy, $sortOrder); 
                    if(count($secContentList) > 0)
                    {
                        $totalContentCount = $totalContentCount + count($secContentList);
                        
                        if(!$overrideOffset)
                        {
                            $secContentList = array_slice($secContentList, $offset, $listSize, FALSE);
                        }
                    }
                    
                    $showGroupHeader = TRUE;
                }
                $secResCnt = count($secContentList);
                
                $groupPer = array();
                $folderTypeId = 0;
                $profileCnt = 0;
                $profileShareRight = 0;
                if($orgId > 0)
                {
                    $orgConst = $depMgmtObj->getEmployeeOrUserConstantObject();
                    if(isset($orgConst))
                    {
                        $profileShareRight = $orgConst->is_srac_share_enabled;
                    }
                }
                else
                {
                    $profileShareRight = 1;
                }
                    
                $isFavorited = 0;
                if($isFolder)
                {
                    $profileCnt = OrganizationUser::ofUserEmail($user->email)->verified()->count();
                    
                    $folderObj = $depMgmtObj->getFolderObject($folderOrGroupId);
                    if(isset($folderObj))
                    {
                        $isFavorited = $folderObj->is_favorited;
                        $folderTypeId = $folderObj->folder_type_id;
                    }
                }
                else
                {
                    $group = $depMgmtObj->getGroupObject($groupId);
                    
                    if(isset($group))
                    {
                        $groupMember = $depMgmtObj->getGroupMemberDetailsObject($groupId, false);
                        $isTwoWay = $group->is_two_way;
                        $allocSpace = $group->allocated_space_kb;
                        $usedSpace = $group->used_space_kb;
                        
                        $hasPostRight = 1;
                        $isAdmin = 0;
                        if(isset($groupMember))
                        {
                            $isAdmin = $groupMember->is_admin;
                            $isFavorited = $groupMember->is_favorited;
                            if($orgId > 0)
                                $hasPostRight = $groupMember->has_post_right;
                        }
                        $groupPer['isTwoWay'] = $isTwoWay;  
                        $groupPer['hasPostRight'] = $hasPostRight;  
                        $groupPer['isAdmin'] = $isAdmin;
                    }
                }
                
                $isAllNotesFlag = $isAllNotes == TRUE ? 1 : 0;

                $status = 1;
                
                $resCnt = count($contentList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_content_found');
                
                $showLoadMore = 1;
                if($resCnt < $listSize)
                {
                    $showLoadMore = 0;
                }

                $dispAttachmentNameLength = 25;
                $primAttachmentArr = array();
                $secAttachmentArr = array();
                $attachmentView = "";
                if($isAttachmentView == 1)
                {
                    $showLoadMore = 0;

                    foreach ($contentList as $content) 
                    {
                        $contentId = $content['id'];
                        $contentIsFolder = $content['isFolder'];
                        $contentIsLocked = $content['isLocked'];
                        $contentIsShareEnabled = $content['isShareEnabled'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $contentIsFolder);
                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename);  
                            $attObj->url = $attachmentUrl;
                            $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);  
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;
                            $attObj->contentIsLocked = $contentIsLocked;
                            $attObj->contentIsShareEnabled = $contentIsShareEnabled;

                            $attachmentName = $attObj->filename;
                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($primAttachmentArr, $attObj);
                        }
                    }

                    foreach ($secContentList as $content) 
                    {
                        $contentId = $content['id'];
                        $contentIsFolder = $content['isFolder'];
                        $contentIsLocked = $content['isLocked'];
                        $contentIsShareEnabled = $content['isShareEnabled'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $contentIsFolder);
                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename);  
                            $attObj->url = $attachmentUrl;
                            $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);  
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;
                            $attObj->contentIsLocked = $contentIsLocked;
                            $attObj->contentIsShareEnabled = $contentIsShareEnabled;

                            $attachmentName = $attObj->filename;
                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($secAttachmentArr, $attObj);
                        }
                    }
                    
                    $viewDetails = array();
                    $viewDetails['isFavoritesTab'] = $isFavoritesTab;
                    $viewDetails['primAttachmentArr'] = $primAttachmentArr;
                    $viewDetails['secAttachmentArr'] = $secAttachmentArr;
                    $viewDetails['isAllNotes'] = $isAllNotesFlag;
                    $viewDetails['showFolderHeader'] = $showFolderHeader;
                    $viewDetails['showGroupHeader'] = $showGroupHeader;
               
                    $_viewToRender = View::make('content.partialview._attachmentView', $viewDetails);
                    $attachmentView = $_viewToRender->render();
                }
                
                /*if(isset($searchStr) && $searchStr != "") {
                    foreach($contentList as $contentObj) {
                        
                    }
                }*/
                
                
                $userGroups = $depMgmtObj->getAllGroupsFoUser();
                $response['userGroups'] = $userGroups;

                $response['totalContentCount'] = $totalContentCount;
                $response['contentCnt'] = $resCnt;
                $response['contentArr'] = $contentList;
                $response['secContentCnt'] = $secResCnt;
                $response['secContentArr'] = $secContentList;
                $response['profileShareRight'] = $profileShareRight;
                $response['folderTypeId'] = $folderTypeId;
                $response['profileCnt'] = $profileCnt;
                $response['groupPer'] = $groupPer;
                $response['showLoadMore'] = $showLoadMore;
                $response['isFavorited'] = $isFavorited;
                $response['isAllNotes'] = $isAllNotesFlag;
                $response['showFolderHeader'] = $showFolderHeader;
                $response['showGroupHeader'] = $showGroupHeader;
                $response['filArr'] = $filArr;
                $response['attachmentView'] = $attachmentView;
                $response['primAttachmentArr'] = $primAttachmentArr;
                $response['secAttachmentArr'] = $secAttachmentArr;
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
     * Filtered Content List.
     *
     * @return json array
     */
    public function contentListView()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $folderId = Input::get('folderId');
        $groupId = Input::get('groupId');
        $isFolderFlag = Input::get('isFolder');
        $isLockedFlag = Input::get('isLocked');
        $offset = Input::get('ofs');
        $searchStr = Input::get('searchStr');
        $sortBy = Input::get('sortBy');
        $sortOrder = Input::get('sortOrder');
        $hasFilters = Input::get('hasFilters');
        $loginToken = Input::get('loginToken');
        $isAttachmentView = Input::get('isAttachmentView');
        $isFavoritesTab = Input::get('isFavoritesTab');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');
        $listCode = Input::get('listCode');
        $forGlobalSearch = 0;//Input::get('forGlobalSearch');
        
        // Log::info('isFolderFlag : '.$isFolderFlag);
        // Log::info('folderId : '.$folderId);
        // Log::info('groupId : '.$groupId);

        if(!isset($listCode))
        {
            $listCode = "";
        }

        if(!isset($isAttachmentView) && $isAttachmentView*1 != 1)
        {
            $isAttachmentView = 0;
        }
        else
        {
            $isAttachmentView = $isAttachmentView*1;
        }

        if(!isset($forGlobalSearch) && $forGlobalSearch*1 != 1)
        {
            $forGlobalSearch = 0;
        }
        else
        {
            $forGlobalSearch = $forGlobalSearch*1;
        }

        $overrideOffset = FALSE;// ; TRUE
        if($isAttachmentView == 1)
        {
            $offset = 0;
            $overrideOffset = TRUE;
        }

        if($forGlobalSearch == 1)
        {
            $offset = 0;
            $overrideOffset = TRUE;
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

                $folderId = sracDecryptNumberData($folderId, $userSession);
                $groupId = sracDecryptNumberData($groupId, $userSession);

                // Log::info('folderId : '.$folderId);
                // Log::info('groupId : '.$groupId);
        
                $filAttachmentExtArr = array();
                
                $filFolderArr = null;
                $filGroupArr = null;
                $filSourceArr = null;
                $filTypeArr = null;
                $filAttachmentTypeArr = null;
                $filTagArr = null;
                $filFromDate = null;
                $filToDate = null;
                $isStarred = null; 
                $chkIsUntagged = null;
                $chkIsLocked = null;
                $chkIsConversation = null;
                $chkShowFolder = null;
                $chkShowGroup = null;
                $chkIsRestricted = null;
                $chkIsUnread = null;
                $filShowAttachment = null;
                $filRepeatStatus = null;
                $filCompletedStatus = null;
                $filDateFilterType = null;
                $filDateDayCount = null;
                $filSenderEmail = null; 

                $contentListHasFilters = 0;
                
                if(isset($hasFilters) && $hasFilters == 1)
                {
                    $contentListHasFilters = 1;

                    $filFolderArr = Input::get('filFolderArr');
                    $filGroupArr = Input::get('filGroupArr');
                    $filSourceArr = Input::get('filSourceArr');
                    $filTypeArr = Input::get('filTypeArr');
                    $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
                    $filTagArr = Input::get('filTagArr');
                    $filFromDate = Input::get('fromTimeStamp');
                    $filToDate = Input::get('toTimeStamp');  
                    $isStarred = Input::get('chkIsStarred');  
                    $chkIsUntagged = Input::get('chkIsUntagged');
                    $chkIsLocked = Input::get('chkIsLocked');
                    $chkIsConversation = Input::get('chkIsConversation');
                    $chkShowFolder = Input::get('chkShowFolder');
                    $chkShowGroup = Input::get('chkShowGroup');
                    $chkIsUnread = Input::get('chkIsUnread');
                    $chkIsRestricted = Input::get('chkIsRestricted');
                    $filShowAttachment = Input::get('filShowAttachment');
                    $filRepeatStatus = Input::get('filRepeatStatus');
                    $filCompletedStatus = Input::get('filCompletedStatus');
                    $filSenderEmail = Input::get('filSenderEmail');
                    $filDateFilterType = Input::get('filDateFilterType');
                    $filDateDayCount = Input::get('filDateDayCount');


                    $filFolderArr = sracDecryptNumberArrayData($filFolderArr, $userSession);
                    $filGroupArr = sracDecryptNumberArrayData($filGroupArr, $userSession);
                    $filSourceArr = sracDecryptNumberArrayData($filSourceArr, $userSession);
                    $filTagArr = sracDecryptNumberArrayData($filTagArr, $userSession);

                    /*$filFolderArr = json_decode($filFolderArr);
                    $filSourceArr = json_decode($filSourceArr);
                    $filTypeArr = json_decode($filTypeArr);
                    $filTagArr = json_decode($filTagArr);*/
                    
                    if(isset($filAttachmentTypeArr) && count($filAttachmentTypeArr) > 0) {
                        $attachmentExtensionArr = Config::get('app_config.filter_attachment_type_extension_array');
                        $extensionStr = '';
                        foreach($filAttachmentTypeArr as $attachmentTypeId) {
                            if(isset($attachmentExtensionArr[$attachmentTypeId])) {
                                if($extensionStr != '')
                                    $extensionStr .= ' ';
                                    
                                $extensionStr .= $attachmentExtensionArr[$attachmentTypeId];
                            }
                        }
                        
                        if($extensionStr != '') {
                            $filAttachmentExtArr = explode(' ', $extensionStr);
                            /*print_r('$filAttachmentExtArr : ');
                            print_r($filAttachmentExtArr);*/
                        }           
                    }     
                }
                
                $filArr = array();
                $filArr['filFolderArr'] = $filFolderArr;
                $filArr['filGroupArr'] = $filGroupArr;
                $filArr['filSourceArr'] = $filSourceArr;
                $filArr['filTagArr'] = $filTagArr;   
                $filArr['filTypeArr'] = $filTypeArr;   
                $filArr['filAttachmentExtArr'] = $filAttachmentExtArr;   
                $filArr['filFromDate'] = $filFromDate;   
                $filArr['filToDate'] = $filToDate;   
                $filArr['chkIsStarred'] = $isStarred;   
                $filArr['chkIsUntagged'] = $chkIsUntagged;
                $filArr['chkIsLocked'] = $chkIsLocked;
                $filArr['chkIsConversation'] = $chkIsConversation;
                $filArr['chkShowFolder'] = $chkShowFolder;
                $filArr['chkShowGroup'] = $chkShowGroup;
                $filArr['chkIsTrashed'] = 0;
                $filArr['chkIsUnread'] = $chkIsUnread;
                $filArr['chkIsRestricted'] = $chkIsRestricted;
                $filArr['filShowAttachment'] = $filShowAttachment;
                $filArr['filRepeatStatus'] = $filRepeatStatus;
                $filArr['filCompletedStatus'] = $filCompletedStatus;
                $filArr['filSenderEmail'] = $filSenderEmail;
                $filArr['filDateDayCount'] = $filDateDayCount;   
                $filArr['filDateFilterType'] = $filDateFilterType;  


                $contentListCodeHasFolderGroupArr = Config::get('app_config.contentListCodeHasFolderGroupArr');
                $listHasFolderGroup = isset($contentListCodeHasFolderGroupArr[$listCode]) ? $contentListCodeHasFolderGroupArr[$listCode] : 0; 

                $listCodeFavNotes = Config::get('app_config.dashMetricFavNotesCode');
                $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');
                $listCodeAllNotes = Config::get('app_config.dashMetricAllNotesCode');
                $listCodeReminderNotes = Config::get('app_config.dashMetricReminderNotesCode');
                $listCodeCalendarNotes = Config::get('app_config.dashMetricCalendarNotesCode');
                $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
                $listCodeTrashNotes = Config::get('app_config.dashMetricTrashNotesCode');
                
                $typeR = Config::get("app_config.content_type_r");
                $typeA = Config::get("app_config.content_type_a");
                $typeC = Config::get("app_config.content_type_c");
                            
                $i = 0;
                $arrForSorting = array();
                $listSize = 20;
                $totalContentCount = 0;

                $contentListFormulationObj = New ContentListFormulationClass;
                $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $appHasCloudStorage = $depMgmtObj->getAppKeyMappingHasCloudStorage();
                $appHasTypeReminder = $depMgmtObj->getAppKeyMappingHasTypeReminder();
                $appHasTypeCalendar = $depMgmtObj->getAppKeyMappingHasTypeCalendar();
                $appHasVideoConference = $depMgmtObj->getAppKeyMappingHasVideoConference();
                $appHasSourceSelection = $depMgmtObj->getAppKeyMappingHasSourceSelection();
                $appHasFolderSelection = $depMgmtObj->getAppKeyMappingHasFolderSelection();
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $empOrUserId = $userId;
                if($orgId > 0)
                    $empOrUserId = $orgEmpId;
                
                
                $isAllNotes = FALSE;
                $isLocked = FALSE;
                
                $isFolder = FALSE;
                $folderOrGroupId = $groupId;
                $selFolderDetails = NULL;
                $selGroupDetails = NULL;
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                    $folderOrGroupId = $folderId;
                    
                    if(isset($isLockedFlag) && $isLockedFlag == 1) 
                    {
                        $isLocked = TRUE;
                    }

                    if($forGlobalSearch == 1)
                    {
                        $folderOrGroupId = -1;
                    } 
                    
                    if($folderOrGroupId < 0) {
                        $isAllNotes = TRUE;
                        if($folderOrGroupId == -2)
                        {
                            $listCode = $listCodeTrashNotes;
                        }
                    }
                    else
                    {
                        $selFolderDetails = $depMgmtObj->getFolderObject($folderOrGroupId);
                    }
                }
                else
                {
                    $selGroupDetails = $depMgmtObj->getGroupObject($folderOrGroupId);
                }

                if($isAllNotes)
                {
                    if($listCode == $listCodeFavNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filArr['chkIsStarred'] = 1;
                    }
                    else if($listCode == $listCodeReminderNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filTypeReminderArr = [ $typeR ];
                        $filArr['filTypeArr'] = $filTypeReminderArr;
                    }
                    else if($listCode == $listCodeCalendarNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filTypeCalendarArr = [ $typeC ];
                        $filArr['filTypeArr'] = $filTypeCalendarArr;
                    }
                    else if($listCode == $listCodeConversationNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filArr['chkIsConversation'] = 1;
                    }
                    else if($listCode == $listCodeTrashNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 0;
                        $filArr['chkIsTrashed'] = 1;
                    }

                    $filArr['chkShowFolder'] = $chkShowFolder;
                    $filArr['chkShowGroup'] = $chkShowGroup;
                }

                $groupMember = NULL;
                $groupPer = array();
                $folderTypeId = 0;
                $profileCnt = 0;

                $virtualFolderTypeId = FolderType::$TYPE_VIRTUAL_FOLDER_ID;
                $virtualSenderFolderTypeId = FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID;
                $virtualFolderId = NULL;
                $virtualFolderFilterStr = NULL;
                    
                $isVirtualFolder = FALSE;
                $isFavorited = 0;
                if($isFolder)
                {
                    $profileCnt = OrganizationUser::ofUserEmail($user->email)->verified()->count();
                    
                    $folderObj = $depMgmtObj->getFolderObject($folderOrGroupId);
                    if(isset($folderObj))
                    {
                        $isFavorited = $folderObj->is_favorited;
                        $folderTypeId = $folderObj->folder_type_id;

                        if($folderTypeId == $virtualFolderTypeId || $folderTypeId == $virtualSenderFolderTypeId)
                        {
                            $isVirtualFolder = TRUE;
                            $virtualFolderId = $folderOrGroupId;

                            $isAllNotes = TRUE;
                            $folderOrGroupId = -1;

                            $virtualFolderFilterStr = $folderObj->applied_filters;

                            $folderFilterUtilObj = New FolderFilterUtilClass;
                            $folderFilterUtilObj->setFilterStr($virtualFolderFilterStr);

                            // Log::info('applied_filters : ');
                            // Log::info($folderObj->applied_filters);

                            if($hasFilters == 0)
                            {
                                $hasFilters = 1;

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
                                $filArr['filRepeatStatus'] = $folderFilterUtilObj->getFilterValueRepeatStatus();
                                $filArr['filCompletedStatus'] = $folderFilterUtilObj->getFilterValueCompletedStatus();
                                $filArr['filSenderEmail'] = $folderFilterUtilObj->getFilterValueSenderEmail();
                                $filArr['filDateFilterType'] = $folderFilterUtilObj->getFilterValueDateFilterType();
                                $filArr['filDateDayCount'] = $folderFilterUtilObj->getFilterValueDateFilterTypeDayCount();

                                $chkShowFolder = $folderFilterUtilObj->getFilterValueIsShowFolder();
                                $chkShowGroup = $folderFilterUtilObj->getFilterValueIsShowGroup();
                                
                                $filArr['chkShowFolder'] = $chkShowFolder;
                                $filArr['chkShowGroup'] = $chkShowGroup;
                            }
                        }
                    }
                }
                else
                {
                    $group = $depMgmtObj->getGroupObject($groupId);
                    
                    if(isset($group))
                    {
                        $groupMember = $depMgmtObj->getGroupMemberObject($groupId);
                        $isTwoWay = $group->is_two_way;
                        $allocSpace = $group->allocated_space_kb;
                        $usedSpace = $group->used_space_kb;
                        
                        $hasPostRight = 1;
                        $isAdmin = 0;
                        if(isset($groupMember))
                        {
                            $isAdmin = $groupMember->is_admin;
                            $isFavorited = $groupMember->is_favorited;
                            if($orgId > 0)
                                $hasPostRight = $groupMember->has_post_right;
                        }
                        $groupPer['isTwoWay'] = $isTwoWay;  
                        $groupPer['hasPostRight'] = $hasPostRight;  
                        $groupPer['isAdmin'] = $isAdmin;
                    }
                }

                // Log::info('Inside ContentList : isAllNotes : '.($isAllNotes?'1':'0').' : isLocked : '.($isLocked?'1':'0').' : isFolder : '.$isFolder.' : folderOrGroupId : '.$folderOrGroupId.' : filArr : ');
                // Log::info($filArr);

                $showListHeader = FALSE;
                if($offset == 0)
                {
                    $showListHeader = TRUE;
                }

                if($forGlobalSearch == 1)
                {
                    $showListHeader = FALSE;
                }
                
                $showFolderHeader = FALSE;
                $showGroupHeader = FALSE;
                
                $contentList = array();
                if((!$isAllNotes) || ($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowFolder) && $chkShowFolder == 1)))))
                {

                    $contentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

                    if($offset == 0)
                    {
                        $showFolderHeader = TRUE;
                    }

                    if($listCode == $listCodeTrashNotes)
                    {
                        $showFolderHeader = FALSE;
                    }
                    //Log::info('Fetching Folder ContentList : $contentList : '.count($contentList));
                }
                
                /*print_r('<pre>after sortby<br/>');
                print_r($contentList);
                print_r('</pre>');*/
                
                $totalPrimaryContentCount = count($contentList);
                $totalSecondaryContentCount = 0;
                $totalContentCount = count($contentList);
        
                if(!$overrideOffset)
                {
                    $contentList = array_slice($contentList, $offset, $listSize, FALSE);
                }
                $resCnt = count($contentList);
                
                $secContentList = array();
                if($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowGroup) && $chkShowGroup == 1)))) 
                {
                    if($offset == 0 || $resCnt < $listSize)
                    {
                        $usrGroupArr = $depMgmtObj->getAllGroupsFoUser();
                        $usrGroupIdArr = array();
                        foreach($usrGroupArr as $usrGroup) {
                            array_push($usrGroupIdArr, $usrGroup->group_id);
                        }
                        $filArr['filAllNotesGroupArr'] = $usrGroupIdArr;

                        $tempSecContentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, FALSE, $folderOrGroupId, $isAllNotes, $isLocked, 1, $filArr, $searchStr, $sortBy, $sortOrder); 
                        if(count($tempSecContentList) > 0)
                        {
                            $totalContentCount = $totalContentCount + count($tempSecContentList);
                            $totalSecondaryContentCount = count($tempSecContentList);
                        }

                        if($resCnt < $listSize)
                        {
                            $secOffset = $offset - $totalPrimaryContentCount;
                            if($secOffset < 0)
                            {
                                $secOffset = 0;
                            }

                            $secListSize = $listSize - $resCnt;

                            $secContentList = $tempSecContentList; 
                            if(count($secContentList) > 0)
                            {
                                if(!$overrideOffset)
                                {
                                    $secContentList = array_slice($secContentList, $secOffset, $secListSize, FALSE);
                                }
                            }
                            if($secOffset == 0)
                            {
                                $showGroupHeader = TRUE;
                            }
                        }
                    }

                    //Log::info('Fetching Group ContentList : $secContentList : '.count($secContentList));
                }
                $secResCnt = count($secContentList);
                
                $profileShareRight = 0;
                if($orgId > 0)
                {
                    $orgConst = $depMgmtObj->getEmployeeOrUserConstantObject();
                    if(isset($orgConst))
                    {
                        $profileShareRight = $orgConst->is_srac_share_enabled;
                    }
                }
                else
                {
                    $profileShareRight = 1;
                }
                
                $isAllNotesFlag = $isAllNotes == TRUE ? 1 : 0;

                $orgIsSaveShareEnabled = $depMgmtObj->getOrganizationIsFileSaveShareEnabled();

                $status = 1;


                $dispAttachmentNameLength = 25;
                $primAttachmentArr = array();
                $secAttachmentArr = array();
                $attachmentView = "";
                if($isAttachmentView == 1)
                {
                    foreach ($contentList as $content) 
                    {
                        $contentId = $content['id'];
                        $decContentId = sracDecryptNumberData($contentId, $userSession);
                        $contentIsFolder = $content['isFolder'];
                        $contentIsLocked = $content['isLocked'];
                        $contentIsShareEnabled = $content['isShareEnabled'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($decContentId, $contentIsFolder);

                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $cloudStorageTypeId = $attObj->att_cloud_storage_type_id;
                            $attachmentName = $attObj->filename;

                            if($cloudStorageTypeId == 0)
                            {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename); 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);  
                            }
                            else
                            {
                                $attachmentUrl = $attObj->cloud_file_url; 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attachmentName);  
                            }

                            $attObj->enc_content_attachment_id = sracEncryptNumberData($attObj->content_attachment_id, $userSession);
                            $attObj->url = $attachmentUrl;
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;
                            $attObj->contentIsLocked = $contentIsLocked;
                            $attObj->contentIsShareEnabled = $contentIsShareEnabled;

                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($primAttachmentArr, $attObj);
                        }
                    }

                    foreach ($secContentList as $content) 
                    {
                        $contentId = $content['id'];
                        $decContentId = sracDecryptNumberData($contentId, $userSession);
                        $contentIsFolder = $content['isFolder'];
                        $contentIsLocked = $content['isLocked'];
                        $contentIsShareEnabled = $content['isShareEnabled'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($decContentId, $contentIsFolder);
                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $cloudStorageTypeId = $attObj->att_cloud_storage_type_id;
                            $attachmentName = $attObj->filename;

                            if($cloudStorageTypeId == 0)
                            {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename); 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename); 
                            }
                            else
                            {
                                $attachmentUrl = $attObj->cloud_file_url; 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attachmentName); 
                            }

                            $attObj->enc_content_attachment_id = sracEncryptNumberData($attObj->content_attachment_id, $userSession); 
                            $attObj->url = $attachmentUrl; 
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;
                            $attObj->contentIsLocked = $contentIsLocked;
                            $attObj->contentIsShareEnabled = $contentIsShareEnabled;

                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($secAttachmentArr, $attObj);
                        }
                    }
                    
                    $viewDetails = array();
                    $viewDetails['isFavoritesTab'] = $isFavoritesTab;
                    $viewDetails['primAttachmentArr'] = $primAttachmentArr;
                    $viewDetails['secAttachmentArr'] = $secAttachmentArr;
                    $viewDetails['isAllNotes'] = $isAllNotesFlag;
                    $viewDetails['showFolderHeader'] = $showFolderHeader;
                    $viewDetails['showGroupHeader'] = $showGroupHeader;
                    $viewDetails['orgKey'] = $encOrgId;
                    $viewDetails['orgIsSaveShareEnabled'] = $orgIsSaveShareEnabled;

                    $_viewToRender = View::make('content.partialview._attachmentView', $viewDetails);
                    $attachmentView = $_viewToRender->render();
                }
                
                $resCnt = count($contentList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_content_found');
                
                $showLoadMore = 1;
                if($resCnt + $secResCnt < $listSize || $isAttachmentView == 1)
                {
                    $showLoadMore = 0;
                }

                $updOffset = $resCnt + $secResCnt + $offset;
                
                /*if(isset($searchStr) && $searchStr != "") {
                    foreach($contentList as $contentObj) {
                        
                    }
                }*/

                $folderOrGroupEncId = sracEncryptNumberData($folderOrGroupId, $userSession);

                $isVirtualFolderFlag = $isVirtualFolder == true ? 1 : 0;
                
                $userGroups = $depMgmtObj->getAllGroupsFoUser();
                $response['userGroups'] = $userGroups;

                $response['totalContentCount'] = $totalContentCount;
                $response['contentCnt'] = $resCnt;
                $response['contentArr'] = $contentList;
                $response['secContentCnt'] = $secResCnt;
                $response['secContentArr'] = $secContentList;
                $response['profileShareRight'] = $profileShareRight;
                $response['folderTypeId'] = $folderTypeId;
                $response['profileCnt'] = $profileCnt;
                $response['groupPer'] = $groupPer;
                $response['showLoadMore'] = $showLoadMore;
                $response['isFavorited'] = $isFavorited;
                $response['virtualFolderId'] = $virtualFolderId;
                $response['isVirtualFolder'] = $isVirtualFolder;
                $response['isVirtualFolderFlag'] = $isVirtualFolderFlag;
                $response['isAllNotes'] = $isAllNotesFlag;
                $response['showListHeader'] = $showListHeader;
                $response['showFolderHeader'] = $showFolderHeader;
                $response['showGroupHeader'] = $showGroupHeader;
                $response['filArr'] = $filArr;
                $response['isAttachmentView'] = $isAttachmentView;
                $response['attachmentView'] = $attachmentView;
                $response['primAttachmentArr'] = $primAttachmentArr;
                $response['selIsFolder'] = $isFolderFlag;
                $response['selFolderOrGroupId'] = $folderOrGroupId;
                $response['selFolderOrGroupEncId'] = $folderOrGroupEncId;
                $response['selFolderDetails'] = $selFolderDetails;
                $response['selGroupDetails'] = $selGroupDetails;
                $response['tzOffset'] = $tzOffset;
                $response['tzStr'] = $tzStr;
                $response['listCode'] = $listCode;
                $response['searchStr'] = $searchStr;
                $response['contentListHasFilters'] = $contentListHasFilters;
                $response['orgKey'] = $encOrgId;
                $response['orgIsSaveShareEnabled'] = $orgIsSaveShareEnabled;
                $response['appHasCloudStorage'] = $appHasCloudStorage;
                $response['appHasTypeReminder'] = $appHasTypeReminder;
                $response['appHasTypeCalendar'] = $appHasTypeCalendar;
                $response['appHasVideoConference'] = $appHasVideoConference;
                $response['appHasSourceSelection'] = $appHasSourceSelection;
                $response['appHasFolderSelection'] = $appHasFolderSelection;
                $response['forGlobalSearch'] = $forGlobalSearch;

                $viewDetails = $response;

                $_viewToRender = View::make('content.partialview._contentListView', $viewDetails);
                $contentListView = $_viewToRender->render();

                $response = array();
                $response['contentListView'] = utf8_encode($contentListView);
                $response['showLoadMore'] = $showLoadMore;
                $response['totalContentCount'] = $totalContentCount;
                $response['updOffset'] = $updOffset;
                $response['totalPrimaryContentCount'] = $totalPrimaryContentCount;
                $response['totalSecondaryContentCount'] = $totalSecondaryContentCount;
                // $response['contentList'] = $contentList;
                // $response['secContentList'] = $secContentList;
                $response['orgKey'] = $encOrgId;
                $response['groupPer'] = $groupPer;
                // $response['groupMember'] = $groupMember;
                // $response['viewDetails'] = $viewDetails;

                // $response['isFolder'] = $isFolder;
                // $response['folderOrGroupId'] = $folderOrGroupId;

                if($isVirtualFolder && $offset == 0)
                {
                    $response['hasFilters'] = $hasFilters;
                    $response['filtersFrmData'] = $virtualFolderFilterStr;
                    $response['filArr'] = $filArr;
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
    
        return Response::json(utf8ize($response));
    }
    
    public function contentDetailsModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isViewFlag = Input::get('isView');
        $isFolderFlag = Input::get('isFolder');
        $groupId = (Input::get('groupId'));
        $isFavoritesTab = Input::get('isFavoritesTab');
        $sendAsReply = Input::get('sendAsReply');
        $searchStr = Input::get('searchStr');
        
        if(!isset($isFavoritesTab))
        {
            $isFavoritesTab = 0;
        }
        
        if(!isset($sendAsReply))
        {
            $sendAsReply = 0;
        }
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }
        
        $response = array();
        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                $groupId = sracDecryptNumberData($groupId, $userSession);
                
                $typeR = Config::get("app_config.content_type_r");
                $typeA = Config::get("app_config.content_type_a");
                $typeC = Config::get("app_config.content_type_c");
                
                $status = 1;
                
                $isView = FALSE;
                if($isViewFlag == 1)
                    $isView = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $defaultFolderId = $depMgmtObj->getDefaultFolderId();
                $defaultFolderObj = $depMgmtObj->getFolderObject($defaultFolderId);

                $defaultFolder = NULL;
                $defaultFolderName = '';
                if(isset($defaultFolderObj))
                {
                    $defaultFolderName =  $defaultFolderObj->folder_name;

                    $defaultFolder = array();
                    $defaultFolder['id'] = $defaultFolderId;
                    $defaultFolder['text'] = $defaultFolderName;
                }
                
                $typeAName = $depMgmtObj->getContentTypeText($typeA);
                
                $defaultContentType = array();
                $defaultContentType['id'] = $typeA;
                $defaultContentType['text'] = $typeAName;
                
                $defaultGroup = array();
                $groups = GroupMember::ofUser($userId)->joinGroup()->ofDistinctGroup()->get();
                foreach($groups as $group) 
                {
                    $isAdmin = $group->is_admin;
                    $hasPostRight = $group->has_post_right;
                    if($isAdmin == 1 || $hasPostRight == 1)
                    {                       
                        $defaultGroup['id'] = $group->group_id;
                        $defaultGroup['text'] = $group->name;
                        break;
                    }
                }
                
                $groupObj = NULL;
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                    $isFolder = TRUE;
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $empOrUserId = $userId;
                if($orgId > 0)
                    $empOrUserId = $orgEmpId;
                
                $contentDetails = array();
                $userContent = $depMgmtObj->getContentObject($id, $isFolder);
                
                $contentText = "";
                $pageDesc = "Add";
                $contentId = 0;               
                $contentTags = NULL;               
                $contentAttachments = NULL;               
                if(isset($userContent))
                {
                    $contentId = $id;
                    $pageDesc = "Edit";
                                    
                    $contentTags = $depMgmtObj->getContentTags($contentId, $empOrUserId, $isFolder);                  
                    foreach ($contentTags as $contentTag) 
                    {
                        $contentTagObj = $depMgmtObj->getTagObject($contentTag->tag_id);
                        if(isset($contentTagObj))
                        {
                            $contentTag->tag_name = $contentTagObj->tag_name;
                        }
                    }

                    $contentTypeName = "";
                    $typeObj = $depMgmtObj->getContentTypeObject($userContent->content_type_id);
                    if(isset($typeObj))
                        $contentTypeName = $typeObj->type_name;
                    
                    $folderName = "";   
                    $sourceName = "";
                    $folderIconCode = "";
                    if($isFolder)
                    {
                        $folderObj = $depMgmtObj->getFolderObject($userContent->folder_id);
                        if(isset($folderObj))
                        {
                            $folderName = $folderObj->folder_name;
                            $folderIconCode = $folderObj->icon_code;
                        }
                        
                        $sourceObj = $depMgmtObj->getSourceObject($userContent->source_id);
                        if(isset($sourceObj))
                            $sourceName = $sourceObj->source_name;                      
                    }
                    else
                    {
                        $groupId = $userContent->group_id;
                    }                   
                    
                    $fromUtcTs = NULL; 
                    $toUtcTs = NULL;
                    $contentTypeId = $userContent->content_type_id;
                    if($contentTypeId == $typeR || $contentTypeId == $typeC)
                    {                                       
                        $fromUtcTs = $userContent->from_timestamp;                      
                        if($contentTypeId == $typeC)
                        {       
                            $toUtcTs = $userContent->to_timestamp;    
                        }
                    }
                    
                    $userContent->fromTs = $fromUtcTs;
                    $userContent->toTs = $toUtcTs;
                                   
                    $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
                    foreach($contentAttachments as $attObj)
                    {
                        $attServerFilename = $attObj->server_filename;
                        $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename);  
                        $attObj->url = $attachmentUrl;
                    }
                                  
                    if(isset($userContent->content) && $userContent->content != "")
                    {
                        try
                        {
                            $contentText = Crypt::decrypt($userContent->content);
                          //  $contentText = rawurlencode($contentText);
                        } 
                        catch (DecryptException $e) 
                        {
                            
                        }
                    }
                    
                    $contentText = str_replace("\n","",$contentText);
                    
                    $userContent->folder_name = $folderName;
                    $userContent->folder_icon_code = $folderIconCode;
                    $userContent->source_name = $sourceName;    
                    $userContent->content_text = $contentText;        
                    $userContent->type_name = $contentTypeName;  
                        
                    $remindBeforeMillisStr = $depMgmtObj->getRemindBeforeText($userContent->remind_before_millis);
                    $repeatDurationStr = $depMgmtObj->getRepeatDurationText($userContent->repeat_duration);
                          
                    $userContent->remind_before_millis_str = $remindBeforeMillisStr;        
                    $userContent->repeat_duration_str = $repeatDurationStr;        
                }  
                
                $availSpace = 0;     
                $maxAttachmentCount = 5;  
                $groupName = "";
                $groupIsTwoWay = 0;
                $groupIsAdmin = 0;
                $groupHasPostRight = 0;
                if(!$isFolder)
                {
                    $groupObj = $depMgmtObj->getGroupObject($groupId);
                    if(isset($groupObj))
                    {
                        $groupName = $groupObj->name;
                        $groupIsTwoWay = $groupObj->is_two_way;
                        $allocSpace = $groupObj->allocated_space_kb;
                        $usedSpace = $groupObj->used_space_kb;
                        $availSpace = $allocSpace - $usedSpace; 
                        
                        $groupMember = $depMgmtObj->getGroupMemberObject($groupId); 
                        if(isset($groupMember))
                        {
                            $groupIsAdmin = $groupMember->is_admin;
                            $groupHasPostRight = $groupMember->has_post_right;
                        }
                        
                        if($orgId == 0)
                        {
                            $groupHasPostRight = 1;
                        }
                    }
                }
                else
                {
                    $userConst = $depMgmtObj->getEmployeeOrUserConstantObject();
                    if(isset($userConst))
                    {
                        $allocSpace = $userConst->attachment_kb_allotted;
                        $availSpace = $userConst->attachment_kb_available;
                    }
                }
                
                //https://www.freeformatter.com/mime-types-list.html
                
                $isPremiumUser = $user->is_premium;
                
                /* for image, text, application files */                
                $attachmentType = Config::get('app_config.fv_attachment_type_normal');
                
                $isOrgOrPremiumUser = 0;            
                $individualAttachmentSize = 5;
                if($orgId > 0 || $isPremiumUser == 1)
                {
                    $isOrgOrPremiumUser = 1;
                    $individualAttachmentSize = 100;                    
                
                    /* for audio, video files */
                    $attachmentType .= Config::get('app_config.fv_attachment_type_premium');
                }
                
                if($availSpace < 0)
                    $availSpace = 0;
                
                $viewDetails = array();
                $contentConversationParts = array();
                $contentConversationDetails = array();
                $isConversation = FALSE;
                $contentSenderStr = '';
                if($isView) {
                    $pageDesc = "View"; 
                    
                    $contentText = $userContent->content_text;
                    $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);
                    
                    $strippedContentForSender = $depMgmtObj->removePrefixFromSharedContentText($strippedContentText);
                    if(isset($strippedContentForSender)) {
                        $strippedContentText = $strippedContentForSender['content'];
                        $contentSenderStr = $strippedContentForSender['sender'];
                        
                        $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($contentText);
                        $contentConversationDetails = $contentConversationResponse['conversation'];
                        // $contentConversationParts = array();
                        $contentConversationParts['conversationParts'] = $contentConversationResponse['conversationParts'];
                        $contentConversationParts['sanStr'] = $contentConversationResponse['sanStr'];

                        if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
                        {
                            $isConversation = TRUE;
                        }
                    }               
            
                    if($searchStr != "") {

                        $contentListFormulationObj = New ContentListFormulationClass;
                        $contentListFormulationObj->setWithIdEncryption(true, $userSession);

                        $useDecoded = TRUE;
                        
                        if (strpos(strtolower($contentText), $searchStr) !== false) {
                            $contentText = $contentListFormulationObj->searchAndHighlightText($searchStr, $contentText, $useDecoded);
                            $userContent->content_text = $contentText;
                        }
                        
                        $folderName = $userContent->folder_name;                        
                        if($isFolder && strpos(strtolower($folderName), $searchStr) !== false) {
                            $folderName = $contentListFormulationObj->searchAndHighlightText($searchStr, $folderName, $useDecoded);
                            $userContent->folder_name = $folderName;
                        }
                        
                        if(!$isFolder && strpos(strtolower($groupName), $searchStr) !== false) {
                            $groupName = $contentListFormulationObj->searchAndHighlightText($searchStr, $groupName, $useDecoded);
                        }
                        
                        $sourceName = $userContent->source_name;
                        if($isFolder && strpos(strtolower($sourceName), $searchStr) !== false) {
                            $sourceName = $contentListFormulationObj->searchAndHighlightText($searchStr, $sourceName, $useDecoded);
                            $userContent->source_name = $sourceName;
                        }
                        
                        /*$tagStr = $userContent->content_text;
                        if(strpos(strtolower($tagStr), $searchStr) !== false) {
                            $tagStr = $this->searchAndHighlightText($searchStr, $tagStr, $useDecoded);
                            $userContent->content_text = $tagStr;
                        }*/
                    }
                }
                else
                {
                    $typeArr = array();
                    $contentTypes = $depMgmtObj->getAllContentTypes();
                    foreach($contentTypes as $type)
                    {
                        $typeArr[$type->content_type_id] = $type->type_name;
                    }
                    $viewDetails['typeArr'] = $typeArr;
                }

                if($isFolder && isset($userContent))
                {
                    if(isset($userContent->shared_by_email) && $userContent->shared_by_email != "")
                    {
                        $sendAsReply = 1;
                    }
                }
                    
                $pageDesc = $pageDesc." "."Content";
            
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['isView'] = $isView;
                $viewDetails['isFolderFlag'] = $isFolderFlag;
                $viewDetails['isFolder'] = $isFolder;
                $viewDetails['id'] = $contentId;
                $viewDetails['content'] = $userContent;
                $viewDetails['contentTags'] = $contentTags;
                $viewDetails['contentAttachments'] = $contentAttachments;
                $viewDetails['groupId'] = $groupId;
                $viewDetails['groupName'] = $groupName;
                $viewDetails['groupIsTwoWay'] = $groupIsTwoWay;
                $viewDetails['groupIsAdmin'] = $groupIsAdmin;
                $viewDetails['groupHasPostRight'] = $groupHasPostRight;
                $viewDetails['currOrgId'] = $encOrgId;
                $viewDetails['maxAttachmentCount'] = $maxAttachmentCount;
                $viewDetails['availSpace'] = $availSpace;
                $viewDetails['individualAttachmentSize'] = $individualAttachmentSize;
                $viewDetails['isOrgOrPremiumUser'] = $isOrgOrPremiumUser;
                $viewDetails['attachmentType'] = $attachmentType;
                $viewDetails['defaultFolder'] = $defaultFolder;
                $viewDetails['defaultGroup'] = $defaultGroup;
                $viewDetails['defaultContentType'] = $defaultContentType;
                $viewDetails['searchStr'] = $searchStr;
                $viewDetails['isConversation'] = $isConversation;
                $viewDetails['contentConversationDetails'] = $contentConversationDetails;
                
                $addAttachmentViewHtml = "";
                if(!$isView)
                {
                    $addAttachmentViewHtml = View::make('content.partialview._addAttachmentRow');
                    $addAttachmentViewHtml->render();
                    $addAttachmentViewHtml = str_replace(array("\n","\r"), ' ', $addAttachmentViewHtml);    
                }
                $viewDetails['addAttachmentViewHtml'] = $addAttachmentViewHtml;
                
                $colorCodes = Config::get('app_config.content_color_code_arr');
                $colorCodeIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.color_icon_base_path'));
                
                $viewDetails['colorCodes'] = $colorCodes;
                $viewDetails['colorCodeIconBasePath'] = $colorCodeIconBasePath;
                $viewDetails['conOrgId'] = $encOrgId;
                $viewDetails['isFavoritesTab'] = $isFavoritesTab;
                $viewDetails['sendAsReply'] = $sendAsReply;
           
                $_viewToRender = View::make('content.partialview._addEditModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();
                
                $response['contentText'] = $contentText;
                $response['contentConversationDetails'] = $contentConversationDetails;
                $response['contentConversationParts'] = $contentConversationParts;
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
    
    public function contentDetailsModalNew(Request $request)
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isViewFlag = Input::get('isView');
        $isFolderFlag = Input::get('isFolder');
        $groupId = (Input::get('groupId'));
        $folderId = (Input::get('folderId'));
        $isFavoritesTab = Input::get('isFavoritesTab');
        $sendAsReply = Input::get('sendAsReply');
        $searchStr = Input::get('searchStr');
        $listCode = Input::get('listCode');
        $mappedCloudAttachmentDetailsArrStr = Input::get('mappedCloudAttachmentDetailsArrStr');
        
        if(!isset($isFavoritesTab))
        {
            $isFavoritesTab = 0;
        }
        
        if(!isset($sendAsReply))
        {
            $sendAsReply = 0;
        }
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }

        $mappedCloudAttachmentDetailsArr = NULL;
        if(isset($mappedCloudAttachmentDetailsArrStr) && trim($mappedCloudAttachmentDetailsArrStr) != "")
        {
            $mappedCloudAttachmentDetailsArrStr = trim($mappedCloudAttachmentDetailsArrStr);
            $mappedCloudAttachmentDetailsArr = json_decode($mappedCloudAttachmentDetailsArrStr);
        }
        
        $response = array();
        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                $groupId = sracDecryptNumberData($groupId, $userSession);
                $folderId = sracDecryptNumberData($folderId, $userSession);
                
                $typeR = Config::get("app_config.content_type_r");
                $typeA = Config::get("app_config.content_type_a");
                $typeC = Config::get("app_config.content_type_c");
                
                $status = 1;
                
                $isView = FALSE;
                if($isViewFlag == 1)
                    $isView = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $viewDetails = $this->compileContentDetailsSubView($encOrgId, $depMgmtObj, $isFolderFlag, $id, $isView, $request);
           
                $_viewToRender = View::make('content.partialview._contentAddEditModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();
                
                $response['view'] = $_viewToRender;
                $response['viewDetails'] = $viewDetails;
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

    public function loadContentDetailsSubView(Request $request)
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isViewFlag = Input::get('isView');
        $isFolderFlag = Input::get('isFolder');
        $isFavoritesTab = Input::get('isFavoritesTab');
        $sendAsReply = Input::get('sendAsReply');
        $searchStr = Input::get('searchStr');
        $listCode = Input::get('listCode');
        
        if(!isset($isFavoritesTab))
        {
            $isFavoritesTab = 0;
        }
        
        if(!isset($sendAsReply))
        {
            $sendAsReply = 0;
        }
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }
        
        $response = array();
        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                
                $typeR = Config::get("app_config.content_type_r");
                $typeA = Config::get("app_config.content_type_a");
                $typeC = Config::get("app_config.content_type_c");
                
                $status = 1;
                
                $isView = FALSE;
                if($isViewFlag == 1)
                    $isView = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $viewDetails = $this->compileContentDetailsSubView($encOrgId, $depMgmtObj, $isFolderFlag, $id, $isView, $request);
                
                $response['view'] = $viewDetails['detailsubViewHtml'];
                $response['contentConversationDetails'] = $viewDetails['contentConversationDetails'];
                $response['viewDetails'] = $viewDetails;
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
    
    public function compileContentDetailsSubView($encOrgId, $depMgmtObj, $isFolderFlag, $id, $isView, $request)
    {
        $searchStr = $request->input('searchStr'); 
        $listCode = $request->input('listCode'); 
        $groupId = ($request->input('groupId')); 
        $folderId = ($request->input('folderId')); 
        $isFavoritesTab = $request->input('isFavoritesTab'); 
        $sendAsReply = $request->input('sendAsReply'); 
        $forPopUp = Input::get('forPopUp');
        $preloadConversationReplyText = Input::get('preloadConversationReplyText');
        $mappedCloudAttachmentDetailsArrStr = Input::get('mappedCloudAttachmentDetailsArrStr');

        $userSession = $depMgmtObj->getAppuserSession();

        $hasAppKeyMaping = isset($userSession->mapped_app_key_id) && $userSession->mapped_app_key_id > 0 ? 1 : 0;

        $folderId = sracDecryptNumberData($folderId, $userSession);
        $groupId = sracDecryptNumberData($groupId, $userSession);
        
        if(!isset($forPopUp) && $forPopUp != 1)
        {
            $forPopUp = 0;
        }

        if(!isset($isFavoritesTab))
        {
            $isFavoritesTab = 0;
        }
        
        if(!isset($sendAsReply))
        {
            $sendAsReply = 0;
        }
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }

        $mappedCloudAttachmentDetailsArr = NULL;
        if(isset($mappedCloudAttachmentDetailsArrStr) && trim($mappedCloudAttachmentDetailsArrStr) != "")
        {
            $mappedCloudAttachmentDetailsArrStr = trim($mappedCloudAttachmentDetailsArrStr);
            $mappedCloudAttachmentDetailsArr = json_decode($mappedCloudAttachmentDetailsArrStr);
        }
        
        $typeR = Config::get("app_config.content_type_r");
        $typeA = Config::get("app_config.content_type_a");
        $typeC = Config::get("app_config.content_type_c");

        $defaultFolderId = $depMgmtObj->getDefaultFolderId();
        $defaultFolderObj = $depMgmtObj->getFolderObject($defaultFolderId);
        $defaultFolderName = $defaultFolderObj->folder_name;

        $userId = $depMgmtObj->getUserId();
        $user = $depMgmtObj->getUserObject();

        if(isset($folderId) && $folderId > 0)
        {
            $selFolderObj = $depMgmtObj->getFolderObject($folderId);
            if(isset($selFolderObj))
            {
                $selFolderName = $selFolderObj->folder_name;

                $defaultFolderId = $folderId;
                $defaultFolderName = $selFolderName;
            }
        }

        $listCodeReminderNotes = Config::get('app_config.dashMetricReminderNotesCode');
        $listCodeCalendarNotes = Config::get('app_config.dashMetricCalendarNotesCode');
        
        $defaultFolder = array();
        $defaultFolder['id'] = sracEncryptNumberData($defaultFolderId, $userSession);
        $defaultFolder['text'] = $defaultFolderName;

        $defaultContentTypeId = $typeA;          

        if(isset($listCode) && $listCode !== "")
        {
            if($listCode == $listCodeReminderNotes)
            {
                $defaultContentTypeId = $typeR;
            }
            else if($listCode == $listCodeCalendarNotes)
            {
                $defaultContentTypeId = $typeC;
            }
        }

        $defaultContentTypeText = $depMgmtObj->getContentTypeText($defaultContentTypeId); 
        
        $defaultContentType = array();
        $defaultContentType['id'] = $defaultContentTypeId;
        $defaultContentType['text'] = $defaultContentTypeText;
        
        $defaultGroup = array();
        $groups = GroupMember::ofUser($userId)->joinGroup()->ofDistinctGroup()->get();
        foreach($groups as $group) 
        {
            $isAdmin = $group->is_admin;
            $hasPostRight = $group->has_post_right;
            if($isAdmin == 1 || $hasPostRight == 1)
            {                       
                $defaultGroup['id'] = sracEncryptNumberData($group->group_id, $userSession);
                $defaultGroup['text'] = $group->name;
                break;
            }
        }
        
        $groupObj = NULL;
        $isFolder = FALSE;
        if($isFolderFlag == 1)
            $isFolder = TRUE;
        
        $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
        $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        
        $empOrUserId = $userId;
        if($orgId > 0)
            $empOrUserId = $orgEmpId;
        
        $contentDetails = array();
        $userContent = $depMgmtObj->getContentObject($id, $isFolder);

        if($isFolder)
        {
            //$userContent = $userContent->removedConsiderationForSync();
        }
                
        $contentText = "";
        $pageDesc = "Add";
        $contentId = 0;               
        $contentTags = NULL;               
        $contentAttachments = NULL;               
        if(isset($userContent) && ((!$isFolder) || ($isFolder && $userContent->is_removed <= 1)))
        {
            $contentId = $id;
            $pageDesc = "Edit";
                            
            $contentTags = $depMgmtObj->getContentTags($contentId, $empOrUserId, $isFolder);                  
            foreach ($contentTags as $contentTag) 
            {
                $contentTagObj = $depMgmtObj->getTagObject($contentTag->tag_id);
                if(isset($contentTagObj))
                {
                    $contentTag->tag_name = $contentTagObj->tag_name;
                    $contentTag->enc_tag_id = sracEncryptNumberData($contentTag->tag_id, $userSession);
                }
            }

            $contentTypeName = "";
            $typeObj = $depMgmtObj->getContentTypeObject($userContent->content_type_id);
            if(isset($typeObj))
                $contentTypeName = $typeObj->type_name;
            
            $contentFolderTypeId = 0;
            $folderName = "";   
            $sourceName = "";
            $folderIconCode = "";
            if($isFolder)
            {
                $folderObj = $depMgmtObj->getFolderObject($userContent->folder_id);
                if(isset($folderObj))
                {
                    $folderName = $folderObj->folder_name;
                    $folderIconCode = $folderObj->icon_code;
                    $contentFolderTypeId = $folderObj->folder_type_id;

                    $userContent->enc_folder_id = sracEncryptNumberData($userContent->folder_id, $userSession);
                }
                
                $sourceObj = $depMgmtObj->getSourceObject($userContent->source_id);
                if(isset($sourceObj))
                {
                    $sourceName = $sourceObj->source_name;  

                    $userContent->enc_source_id = sracEncryptNumberData($userContent->source_id, $userSession);
                }                    
            }
            else
            {
                $groupId = $userContent->group_id;
               
                $userContent->enc_group_id = sracEncryptNumberData($userContent->group_id, $userSession);
            }                   
            
            $fromUtcTs = NULL; 
            $toUtcTs = NULL;
            $contentTypeId = $userContent->content_type_id;
            if($contentTypeId == $typeR || $contentTypeId == $typeC)
            {                                       
                $fromUtcTs = $userContent->from_timestamp;                      
                if($contentTypeId == $typeC)
                {       
                    $toUtcTs = $userContent->to_timestamp;    
                }
            }
            
            $userContent->fromTs = $fromUtcTs;
            $userContent->toTs = $toUtcTs;
                           
            $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
            foreach($contentAttachments as $attObj)
            {
                $attServerFilename = $attObj->server_filename;
                $cloudStorageTypeId = $attObj->att_cloud_storage_type_id;

                if($cloudStorageTypeId == 0)
                {
                    $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename); 
                }
                else
                {
                    $attachmentUrl = $attObj->cloud_file_url; 
                }

                $attObj->url = $attachmentUrl;
                $attObj->enc_content_attachment_id = sracEncryptNumberData($attObj->content_attachment_id, $userSession);
            }
                          
            if(isset($userContent->content) && $userContent->content != "")
            {
                try
                {
                    $contentText = Crypt::decrypt($userContent->content);
                                // $contentText = utf8_encode($contentText);
                  //  $contentText = rawurlencode($contentText);
                } 
                catch (DecryptException $e) 
                {
                    $contentText = '';//'Failure';
                    
                }
            }
            
            $contentText = str_replace("\n","",$contentText);
            
            $userContent->folder_name = $folderName;
            $userContent->folder_icon_code = $folderIconCode;
            $userContent->source_name = $sourceName;    
            $userContent->content_text = $contentText;        
            $userContent->type_name = $contentTypeName;  
            $userContent->folder_type_id = $contentFolderTypeId;

            $remindBeforeMillisStr = $depMgmtObj->getRemindBeforeText($userContent->remind_before_millis);
            $repeatDurationStr = $depMgmtObj->getRepeatDurationText($userContent->repeat_duration);
                  
            $userContent->remind_before_millis_str = $remindBeforeMillisStr;        
            $userContent->repeat_duration_str = $repeatDurationStr;        
        }  
                
        $availSpace = 0;     
        $maxAttachmentCount = 5;  
        $groupName = "";
        $groupIsTwoWay = 0;
        $groupIsAdmin = 0;
        $groupHasPostRight = 0;
        if(!$isFolder)
        {
            $groupObj = $depMgmtObj->getGroupObject($groupId);
            if(isset($groupObj))
            {
                $groupName = $groupObj->name;
                $groupIsTwoWay = $groupObj->is_two_way;
                $allocSpace = $groupObj->allocated_space_kb;
                $usedSpace = $groupObj->used_space_kb;
                $availSpace = $allocSpace - $usedSpace; 
                
                $groupMember = $depMgmtObj->getGroupMemberObject($groupId); 
                if(isset($groupMember))
                {
                    $groupIsAdmin = $groupMember->is_admin;
                    $groupHasPostRight = $groupMember->has_post_right;
                }
                
                if($orgId == 0)
                {
                    $groupHasPostRight = 1;
                }
            }
        }
        else
        {
            $userConst = $depMgmtObj->getEmployeeOrUserConstantObject();
            if(isset($userConst))
            {
                $allocSpace = $userConst->attachment_kb_allotted;
                $availSpace = $userConst->attachment_kb_available;
            }
        }
        
        //https://www.freeformatter.com/mime-types-list.html
        
        $isPremiumUser = $user->is_premium;
        
        /* for image, text, application files */                
        $attachmentType = Config::get('app_config.fv_attachment_type_normal');
        
        $isOrgOrPremiumUser = 0;            
        $individualAttachmentSize = 3;
        if($orgId > 0 || $isPremiumUser == 1)
        {
            $isOrgOrPremiumUser = 1;
            $individualAttachmentSize = 100;                    
        
            /* for audio, video files */
            $attachmentType .= Config::get('app_config.fv_attachment_type_premium');
        }
        
        if($availSpace < 0)
            $availSpace = 0;
        
        $viewDetails = array();
        $contentConversationParts = array();
        $contentConversationDetails = array();
        $isConversation = FALSE;
        $contentSenderStr = '';
        if($isView) {
            $pageDesc = "View"; 
            
            $contentText = $userContent->content_text;
            $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);
            
            $strippedContentForSender = $depMgmtObj->removePrefixFromSharedContentText($strippedContentText);
            if(isset($strippedContentForSender)) {
                $strippedContentText = $strippedContentForSender['content'];
                $contentSenderStr = $strippedContentForSender['sender'];

                if($contentSenderStr != "")
                {
                    $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($contentText);
                    $contentConversationDetails = $contentConversationResponse['conversation'];
                    // $contentConversationParts = array();
                    $contentConversationParts['conversationParts'] = $contentConversationResponse['conversationParts'];
                    $contentConversationParts['sanStr'] = $contentConversationResponse['sanStr'];

                    if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
                    {
                        $isConversation = TRUE;
                    }
                }
            }              
    
            if($searchStr != "") {

                $contentListFormulationObj = New ContentListFormulationClass;
                $contentListFormulationObj->setWithIdEncryption(true, $userSession);

                $useDecoded = TRUE;
                
                if (strpos(strtolower($contentText), $searchStr) !== false) {
                    $contentText = $contentListFormulationObj->searchAndHighlightText($searchStr, $contentText, $useDecoded);
                    $userContent->content_text = $contentText;
                }
                
                $folderName = $userContent->folder_name;                        
                if($isFolder && strpos(strtolower($folderName), $searchStr) !== false) {
                    $folderName = $contentListFormulationObj->searchAndHighlightText($searchStr, $folderName, $useDecoded);
                    $userContent->folder_name = $folderName;
                }
                
                if(!$isFolder && strpos(strtolower($groupName), $searchStr) !== false) {
                    $groupName = $contentListFormulationObj->searchAndHighlightText($searchStr, $groupName, $useDecoded);
                }
                
                $sourceName = $userContent->source_name;
                if($isFolder && strpos(strtolower($sourceName), $searchStr) !== false) {
                    $sourceName = $contentListFormulationObj->searchAndHighlightText($searchStr, $sourceName, $useDecoded);
                    $userContent->source_name = $sourceName;
                }
                
                /*$tagStr = $userContent->content_text;
                if(strpos(strtolower($tagStr), $searchStr) !== false) {
                    $tagStr = $contentListFormulationObj->searchAndHighlightText($searchStr, $tagStr, $useDecoded);
                    $userContent->content_text = $tagStr;
                }*/
            }
        }
        else
        {
            $typeArr = array();
            $contentTypes = $depMgmtObj->getAllContentTypes();
            foreach($contentTypes as $type)
            {
                $typeArr[$type->content_type_id] = $type->type_name;
            }
            $viewDetails['typeArr'] = $typeArr;
        }

        if($isFolder && isset($userContent))
        {
            if(isset($userContent->shared_by_email) && $userContent->shared_by_email != "")
            {
                $sendAsReply = 1;
            }
        }


        $cloudStorageTypeList = $depMgmtObj->getAllCloudStorageTypeListForUser();
        $linkedCloudCalendarTypeArr = $depMgmtObj->getAppuserLinkedCloudCalendarTypeMapping();


        $hasLinkedCloudCalendarTypeGoogle = 0;
        $hasLinkedCloudCalendarTypeOneDrive = 0;

        $cloudCalendarTypeCodeGoogle = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
        $cloudCalendarTypeCodeMicrosoft = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;

        if(isset($linkedCloudCalendarTypeArr) && is_array($linkedCloudCalendarTypeArr) && count($linkedCloudCalendarTypeArr) > 0)
        {
                foreach($linkedCloudCalendarTypeArr as $linkedCloudCalendarTypeDetails)
                {
                    $cloudCalendarTypeCode = $linkedCloudCalendarTypeDetails['cloudCalendarTypeCode'];

                    if($cloudCalendarTypeCode == $cloudCalendarTypeCodeGoogle)
                    {
                        $hasLinkedCloudCalendarTypeGoogle = 1;
                    }
                    else if($cloudCalendarTypeCode == $cloudCalendarTypeCodeMicrosoft)
                    {
                        $hasLinkedCloudCalendarTypeOneDrive = 1;
                    }
                }
        }
            
        if($isConversation == FALSE || !isset($preloadConversationReplyText))
        {
            $preloadConversationReplyText = "";
        }

        $pageDesc = $pageDesc." "."Content";
    
        $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

        $contentConversationDetailsReversed = array_reverse($contentConversationDetails);

        $appHasCloudStorage = $depMgmtObj->getAppKeyMappingHasCloudStorage();
        $appHasTypeReminder = $depMgmtObj->getAppKeyMappingHasTypeReminder();
        $appHasTypeCalendar = $depMgmtObj->getAppKeyMappingHasTypeCalendar();
        $appHasVideoConference = $depMgmtObj->getAppKeyMappingHasVideoConference();
        $appHasSourceSelection = $depMgmtObj->getAppKeyMappingHasSourceSelection();
        $appHasFolderSelection = $depMgmtObj->getAppKeyMappingHasFolderSelection();

        $viewDetails['page_description'] = $pageDesc;
        $viewDetails['isView'] = $isView;
        $viewDetails['isFolderFlag'] = $isFolderFlag;
        $viewDetails['isFolder'] = $isFolder;
        $viewDetails['id'] = sracEncryptNumberData($contentId, $userSession);
        $viewDetails['content'] = $userContent;
        $viewDetails['contentTags'] = $contentTags;
        $viewDetails['contentAttachments'] = $contentAttachments;
        $viewDetails['groupId'] = sracEncryptNumberData($groupId, $userSession);
        $viewDetails['groupName'] = $groupName;
        $viewDetails['groupIsTwoWay'] = $groupIsTwoWay;
        $viewDetails['groupIsAdmin'] = $groupIsAdmin;
        $viewDetails['groupHasPostRight'] = $groupHasPostRight;
        $viewDetails['currOrgId'] = $encOrgId;
        $viewDetails['maxAttachmentCount'] = $maxAttachmentCount;
        $viewDetails['availSpace'] = $availSpace;
        $viewDetails['individualAttachmentSize'] = $individualAttachmentSize;
        $viewDetails['isOrgOrPremiumUser'] = $isOrgOrPremiumUser;
        $viewDetails['attachmentType'] = $attachmentType;
        $viewDetails['defaultFolder'] = $defaultFolder;
        $viewDetails['defaultGroup'] = $defaultGroup;
        $viewDetails['defaultContentType'] = $defaultContentType;
        $viewDetails['listCode'] = $listCode;
        $viewDetails['searchStr'] = $searchStr;
        $viewDetails['preloadConversationReplyText'] = $preloadConversationReplyText;
        $viewDetails['isConversation'] = $isConversation;
        $viewDetails['contentConversationDetails'] = $contentConversationDetails;
        $viewDetails['contentConversationDetailsReversed'] = $contentConversationDetailsReversed;
        $viewDetails['cloudStorageTypeList'] = $cloudStorageTypeList;
        $viewDetails['forPopUp'] = $forPopUp;
        $viewDetails['appHasCloudStorage'] = $appHasCloudStorage;
        $viewDetails['appHasTypeReminder'] = $appHasTypeReminder;
        $viewDetails['appHasTypeCalendar'] = $appHasTypeCalendar;
        $viewDetails['appHasVideoConference'] = $appHasVideoConference;
        $viewDetails['appHasSourceSelection'] = $appHasSourceSelection;
        $viewDetails['appHasFolderSelection'] = $appHasFolderSelection;
        $viewDetails['linkedCloudCalendarTypeArr'] = $linkedCloudCalendarTypeArr;
        $viewDetails['hasLinkedCloudCalendarTypeGoogle'] = $hasLinkedCloudCalendarTypeGoogle;
        $viewDetails['hasLinkedCloudCalendarTypeOneDrive'] = $hasLinkedCloudCalendarTypeOneDrive;
        
        $addAttachmentViewHtml = "";
        if(!$isView)
        {
            $addAttachmentViewHtml = View::make('content.partialview._addAttachmentRow');
            $addAttachmentViewHtml->render();
            $addAttachmentViewHtml = str_replace(array("\n","\r"), ' ', $addAttachmentViewHtml);    
        }
        $viewDetails['addAttachmentViewHtml'] = $addAttachmentViewHtml;
        
        $colorCodes = Config::get('app_config.content_color_code_arr');
        $colorCodeIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.color_icon_base_path'));

        $orgIsSaveShareEnabled = $depMgmtObj->getOrganizationIsFileSaveShareEnabled();

        $encContentText = '';
        $encStrippedContentText = '';
        $encContentConversationText = '';
        if(isset($userContent))
        {
            $contentText = $userContent->content_text;

            $encContentText = rawurlencode($contentText);

            $encStrippedContentText = $depMgmtObj->getStrippedContentText($contentText);
            $encStrippedContentText = rawurlencode($encStrippedContentText);

            if($isConversation && count($contentConversationDetails) > 0)
            {
                $encContentConversationText = rawurlencode($contentConversationDetails[0]['contentStripped']);
            }
        }
        
        $viewDetails['colorCodes'] = $colorCodes;
        $viewDetails['colorCodeIconBasePath'] = $colorCodeIconBasePath;
        $viewDetails['conOrgId'] = $encOrgId;
        $viewDetails['isFavoritesTab'] = $isFavoritesTab;
        $viewDetails['sendAsReply'] = $sendAsReply;
        $viewDetails['orgIsSaveShareEnabled'] = $orgIsSaveShareEnabled;
        $viewDetails['encodedContentText'] = $encContentText;
        $viewDetails['encodedStrippedContentText'] = $encStrippedContentText;
        $viewDetails['encodedContentConversationText'] = $encContentConversationText;
        $viewDetails['mappedCloudAttachmentDetailsArr'] = $mappedCloudAttachmentDetailsArr;
        $viewDetails['hasAppKeyMaping'] = $hasAppKeyMaping;
   
        // $scriptsViewToRender = View::make('content.partialview._contentAddEditScripts', $viewDetails);
        // $scriptsViewToRender = $scriptsViewToRender->render();
        // if($forPopUp == 1)
        // {
        //     $scriptsViewToRender = str_replace(array("\n","\r"), ' ', $scriptsViewToRender); 
        // }

        // $viewDetails['scriptsView'] = $scriptsViewToRender;
   
        $_viewToRender = View::make('content.partialview._contentAddEditSubView', $viewDetails);
        $_viewToRender = $_viewToRender->render();
        if($forPopUp == 1)
        {
            // $_viewToRender = str_replace(array("\n","\r"), ' ', $_viewToRender); 
        }
        
        $response = $viewDetails;
        $response['detailsubViewHtml'] = $_viewToRender;
        $response['contentConversationDetails'] = $contentConversationDetails;
        $response['encContentText'] = $encContentText;
        $response['encStrippedContentText'] = $encStrippedContentText;
        $response['encContentConversationText'] = $encContentConversationText;
        $response['userContent'] = $userContent;

        return $response;            
    }

    
    /**
     * Delete Content.
     *
     * @return json array
     */
    public function contentDetails()
    {
        /*DB::enableQueryLog();*/
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                
                $isFolder = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $empOrUserId = $userId;
                if($orgId > 0)
                    $empOrUserId = $orgEmpId;
                
                $contentDetails = array();
                $userContent = $depMgmtObj->getContentObject($id, $isFolder);

                // $response['orgId'] = $orgId;

                if(isset($userContent))
                {
                    $status = 1;
                    $contentId = 0;
                    if($orgId > 0)
                    {
                        $contentId = $userContent->employee_content_id;
                    }
                    else
                    {
                        $contentId = $userContent->appuser_content_id;
                    }

                    $tagsArr = array();                 
                    $contentTags = $depMgmtObj->getContentTags($contentId, $empOrUserId, $isFolder);                  
                    foreach ($contentTags as $contentTag) 
                    {
                        $tagId = $contentTag->tag_id;
                        $contentTagObj = $depMgmtObj->getTagObject($tagId);
                        if(isset($contentTagObj))
                        {
                            $tagName = $contentTagObj->tag_name;
                            $tagObj = array('id' => sracEncryptNumberData($tagId, $userSession), 'name' => $tagName);
                            array_push($tagsArr, $tagObj);
                        }
                    }
                    $tagCnt = count($tagsArr);

                    $folderName = "";
                    $folderIconCode = "";
                    $folderObj = $depMgmtObj->getFolderObject($userContent->folder_id);
                    if(isset($folderObj))
                    {
                        $folderName = $folderObj->folder_name;
                        $folderIconCode = $folderObj->icon_code;
                    }
                        

                    $sourceName = "";
                    $sourceObj = $depMgmtObj->getSourceObject($userContent->source_id);
                    if(isset($sourceObj))
                        $sourceName = $sourceObj->source_name;
                    
                    $hasAttachment = 0;
                    $attachmentsArr = array();                  
                    $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);     
                    if(isset($contentAttachments) && count($contentAttachments) > 0) 
                    {
                        $hasAttachment = 1;
                        $j = 0;  
                        $orgId = 0;                   
                        foreach ($contentAttachments as $contentAttachment) 
                        {
                            $cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                            if($cloudStorageTypeId == 0)
                            {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $contentAttachment->server_filename); 
                            }
                            else
                            {
                                $attachmentUrl = $contentAttachment->cloud_file_url; 
                            }

                            //$attachmentsArr[$j]['id'] = $contentAttachment->content_attachment_id;
                            $attachmentsArr[$j]['name'] = $contentAttachment->filename;
                            $attachmentsArr[$j]['url'] = $attachmentUrl;
                            $attachmentsArr[$j]['pathname'] = $contentAttachment->server_filename;
                            $attachmentsArr[$j]['size'] = $contentAttachment->filesize;
                            $attachmentsArr[$j]['cloudStorageTypeId'] = $cloudStorageTypeId;
                            $attachmentsArr[$j]['cloudFileUrl'] = $contentAttachment->cloud_file_url;
                            $attachmentsArr[$j]['cloudFileId'] = $contentAttachment->cloud_file_id;
                            $attachmentsArr[$j]['cloudFileThumbStr'] = $contentAttachment->cloud_file_thumb_str;
                            $attachmentsArr[$j]['attCreateTs'] = $contentAttachment->create_ts;
                            $attachmentsArr[$j]['attUpdateTs'] = $contentAttachment->update_ts;
                            $attachmentsArr[$j]['syncId'] = sracEncryptNumberData($contentAttachment->content_attachment_id, $userSession);
                            
                            $j++;
                        }
                    }
                    $attachmentCnt = count($attachmentsArr);
                    
                    $contentText = $userContent->content;
                    $contentText = Crypt::decrypt($contentText);
                    // $contentText = str_replace("\n","",$contentText);
                        
                    $sharedByEmail = "";
                    if($userContent->shared_by_email != NULL)
                        $sharedByEmail = $userContent->shared_by_email;

                    $contentDetails['colorCode'] = $userContent->color_code;
                    $contentDetails['isLocked'] = $userContent->is_locked;
                    $contentDetails['isShareEnabled'] = $userContent->is_share_enabled;
                    $contentDetails['remindBeforeMillis'] = $userContent->remind_before_millis;
                    $contentDetails['repeatDuration'] = $userContent->repeat_duration;
                    $contentDetails['isCompleted'] = $userContent->is_completed;
                    $contentDetails['isSnoozed'] = $userContent->is_snoozed;
                    $contentDetails['reminderTimestamp'] = isset($userContent->reminder_timestamp) ? $userContent->reminder_timestamp : 0;
                    $contentDetails['content'] = $contentText;
                    $contentDetails['contentType'] = $userContent->content_type_id;
                    $contentDetails['isMarked'] = $userContent->is_marked;
                    $contentDetails['folderId'] = sracEncryptNumberData($userContent->folder_id, $userSession);
                    $contentDetails['folderName'] = $folderName;
                    $contentDetails['folderIconCode'] = $folderIconCode;
                    $contentDetails['sourceId'] = sracEncryptNumberData($userContent->source_id, $userSession);
                    $contentDetails['sourceName'] = $sourceName;
                    $contentDetails['createTimeStamp'] = $userContent->create_timestamp;
                    $contentDetails['updateTimeStamp'] = $userContent->update_timestamp;
                    $contentDetails['fromTimeStamp'] = $userContent->from_timestamp;
					$contentDetails['syncWithCloudCalendarGoogle'] = $userContent->sync_with_cloud_calendar_google;
					$contentDetails['syncWithCloudCalendarOnedrive'] = $userContent->sync_with_cloud_calendar_onedrive;
                    $contentDetails['toTimeStamp'] = $userContent->to_timestamp;
                    $contentDetails['sharedByEmail'] = $sharedByEmail;
                    $contentDetails['isRemoved'] = $userContent->is_removed;
                    $contentDetails['removedAt'] = $userContent->removed_at;
                    $contentDetails['tagCnt'] = $tagCnt;
                    $contentDetails['tags'] = $tagsArr;
                    $contentDetails['hasAttachment'] = $hasAttachment;
                    $contentDetails['attachmentCnt'] = $attachmentCnt;
                    $contentDetails['attachmentsArr'] = $attachmentsArr;

                    $response['contentDetails'] = $contentDetails;

                    /*print_r('<pre>');
                    print_r(DB::getQueryLog());
                    print_r('</pre>');*/
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
     * Delete Content.
     *
     * @return json array
     */
    public function checkContentCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $isFolderFlag = Input::get('isFolder');
        $idArr = (Input::get('idArr'));
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                        
                $status = 1;
                $msg = "";
                
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                }
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $organizationId = $depMgmtObj->getOrganizationId();
                $organizationEmployeeId = $depMgmtObj->getOrganizationEmployeeId();

                $contentDeleteResponseArr = array();
                $canAllBeDeleted = 1;
                $allContentAttachmentCount = 0;
                $allContentCloudAttachmentCount = 0;

                foreach ($idArr as $key => $id) 
                {
                    $canDelete = 0;
                    $errorMsg = '';

                    $content = $depMgmtObj->getContentObject($id, $isFolder);
                    if(isset($content))
                    {
                        $canDelete = 1;

                        $contentAttachments = $depMgmtObj->getContentAttachments($id, $isFolder);
                        if(count($contentAttachments) > 0)
                        {
                            foreach ($contentAttachments as $contentAttachment) {
                                if($contentAttachment->att_cloud_storage_type_id > 0)
                                {
                                    $allContentCloudAttachmentCount++;
                                }

                                $allContentAttachmentCount++;
                            }
                        }

                        if($canDelete == 0)
                        {
                            $canAllBeDeleted = 0;
                            $errorMsg = 'Permission denied';
                        }
                    }
                    else
                    {
                        $errorMsg = 'No such content found';
                    }
                        
                    $contentDeleteResponse = array();
                    $contentDeleteResponse['canDelete'] = $canDelete;
                    $contentDeleteResponse['errorMsg'] = $errorMsg;
                        
                    $contentDeleteResponseArr[$key] = $contentDeleteResponse;
                }

                $response['contentDeleteResponseArr'] = $contentDeleteResponseArr;
                $response['canAllBeDeleted'] = $canAllBeDeleted;
                $response['allContentAttachmentCount'] = $allContentAttachmentCount;
                $response['allContentCloudAttachmentCount'] = $allContentCloudAttachmentCount;
                
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
     * Delete Content.
     *
     * @return json array
     */
    public function deleteContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $loginToken = Input::get('loginToken');
        $deleteForAll = Input::get('deleteForAll');
        $deletePermanently = Input::get('deletePermanently');
        $removedAt = Input::get('removedAt');
        $deleteCloudAttachments = Input::get('deleteCloudAttachments');

        if(!isset($deleteForAll) || $deleteForAll != 1)
        {
            $deleteForAll = 0;
        }

        if(!isset($deleteCloudAttachments) || $deleteCloudAttachments != 1)
        {
            $deleteCloudAttachments = 0;
        }

        if(!isset($deletePermanently) || $deletePermanently == "" || ($deletePermanently != 0 && $deletePermanently != 1))
        {
            $deletePermanently = 1;
        }

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                        
                $status = 1;
                $msg = "";
                
                $isFolder = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $organizationId = $depMgmtObj->getOrganizationId();
                $organizationEmployeeId = $depMgmtObj->getOrganizationEmployeeId();
                $content = $depMgmtObj->getContentObject($id, $isFolder);

                if(isset($content))
                {
                    $contentAttachments = $depMgmtObj->getContentAttachments($id, $isFolder);

                    $sharedContentId = $content->shared_content_id;
                    //Log::info('sharedContentId : '.$sharedContentId);
                    if($sharedContentId > 0)
                    {
                        $sharedByEmpOrUserId = $content->shared_by;
                        //Log::info('sharedByEmpOrUserId : '.$sharedByEmpOrUserId);

                        if($sharedByEmpOrUserId > 0)
                        {
                            $sharerDepMgmtObj = New ContentDependencyManagementClass;

                            if($organizationId > 0)
                            {
                                $sharerDepMgmtObj->withOrgIdAndEmpId($organizationId, $sharedByEmpOrUserId);
                            }
                            else
                            {
                                $sharerUser = new \stdClass();
                                $sharerUser->appuser_id = $sharedByEmpOrUserId;

                                $sharerDepMgmtObj->withOrgKey($sharerUser, $encOrgId);
                            }

                            $sharerDepMgmtObj->setContentSharedContentId($sharedContentId, $isFolder, 0, $sharedByEmpOrUserId);
                            
                            if($deleteForAll == 1)
                            {
                                $sharerDepMgmtObj->softDeleteContent($sharedContentId, $isFolder, $deletePermanently, $removedAt);
                            }
                        }
                    }

                    if($organizationId > 0)
                    {
                        $depMgmtObj->setContentSharedContentId($id, $isFolder, 0, $organizationEmployeeId);
                    }
                    else
                    {
                        $depMgmtObj->setContentSharedContentId($id, $isFolder, 0, $userId);
                    }

                    $depMgmtObj->softDeleteContent($id, $isFolder, $deletePermanently, $removedAt);

                    if($deleteCloudAttachments == 1)
                    {
                        $depMgmtObj->performDeleteContentAttachmentsFromCloud($contentAttachments);
                    }
                }
                                   
                $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder);
                $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder);
                
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
     * Delete Content.
     *
     * @return json array
     */
    public function restoreDeletedContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $loginToken = Input::get('loginToken');
        $restoreForAll = Input::get('restoreForAll');

        if(!isset($restoreForAll) || $restoreForAll != 1)
        {
            $restoreForAll = 0;
        }

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                        
                $status = 1;
                $msg = "";
                
                $isFolder = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $organizationId = $depMgmtObj->getOrganizationId();
                $content = $depMgmtObj->getContentObject($id, $isFolder);

                if(isset($content) && $content->is_removed == 1)
                {
                    $sharedContentId = $content->shared_content_id;
                    //Log::info('sharedContentId : '.$sharedContentId);
                    if($restoreForAll == 1 && $sharedContentId > 0)
                    {
                        $sharedByEmpOrUserId = $content->shared_by;
                        //Log::info('sharedByEmpOrUserId : '.$sharedByEmpOrUserId);

                        if($sharedByEmpOrUserId > 0)
                        {
                            $sharerDepMgmtObj = New ContentDependencyManagementClass;

                            if($organizationId > 0)
                            {
                                $sharerDepMgmtObj->withOrgIdAndEmpId($organizationId, $sharedByEmpOrUserId);
                            }
                            else
                            {
                                $sharerUser = new \stdClass();
                                $sharerUser->appuser_id = $sharedByEmpOrUserId;

                                $sharerDepMgmtObj->withOrgKey($sharerUser, $encOrgId);
                            }
                            
                            $sharerDepMgmtObj->restoreDeletedContent($sharedContentId, $isFolder);
                        }
                    }

                    $depMgmtObj->restoreDeletedContent($id, $isFolder);
                }
                                   
                $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder);
                $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder);
                
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
     * Delete Content.
     *
     * @return json array
     */
    public function restoreMultiDeletedContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $idArr = sracDecryptNumberArrayData(Input::get('idArr'));
        $loginToken = Input::get('loginToken');
        $restoreForAll = Input::get('restoreForAll');

        if(!isset($restoreForAll) || $restoreForAll != 1)
        {
            $restoreForAll = 0;
        }

        $idArr = jsonDecodeArrStringIfRequired($idArr);
        
        $response = array();

        if($encUserId != "" && isset($idArr) && is_array($idArr) && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                        
                $status = 1;
                $msg = "";
                
                $isFolder = TRUE;

                
                if(isset($idArr) && count($idArr) > 0)
                {
                    $status = 1;
                
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    $depMgmtObj->setCurrentLoginToken($loginToken);
                    $organizationId = $depMgmtObj->getOrganizationId();
                    
                    foreach($idArr as $id)
                    {
                        $content = $depMgmtObj->getContentObject($id, $isFolder);

                        if(isset($content) && $content->is_removed == 1)
                        {
                            $sharedContentId = $content->shared_content_id;
                            if($restoreForAll == 1 && $sharedContentId > 0)
                            {
                                $sharedByEmpOrUserId = $content->shared_by;

                                if($sharedByEmpOrUserId > 0)
                                {
                                    $sharerDepMgmtObj = New ContentDependencyManagementClass;

                                    if($organizationId > 0)
                                    {
                                        $sharerDepMgmtObj->withOrgIdAndEmpId($organizationId, $sharedByEmpOrUserId);
                                    }
                                    else
                                    {
                                        $sharerUser = new \stdClass();
                                        $sharerUser->appuser_id = $sharedByEmpOrUserId;

                                        $sharerDepMgmtObj->withOrgKey($sharerUser, $encOrgId);
                                    }
                                    
                                    $sharerDepMgmtObj->restoreDeletedContent($sharedContentId, $isFolder);
                                }
                            }

                            $depMgmtObj->restoreDeletedContent($id, $isFolder);
                        }
                    }
                }
                
                                   
                $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder);
                $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder);
                
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
     * Delete Multiple Content.
     *
     * @return json array
     */
    public function deleteMultiContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $idArr = (Input::get('idArr'));
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $deletePermanently = Input::get('deletePermanently');
        $removedAt = Input::get('removedAt');

        if(!isset($deletePermanently) || $deletePermanently == "" || ($deletePermanently != 0 && $deletePermanently != 1))
        {
            $deletePermanently = 1;
        }

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            if(isset($isFolderFlag) && $isFolderFlag != "")
            {
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                    $isFolder = TRUE;
            }
            else
                $isFolder = TRUE;
            
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                if(isset($idArr) && count($idArr) > 0)
                {
                    $status = 1;
                
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    $depMgmtObj->setCurrentLoginToken($loginToken);
                    
                    $contentGroupId = NULL; 
                    foreach($idArr as $id)
                    {
                        if(!$isFolder && !isset($contentGroupId))
                        {
                            $contentGroupObj = $depMgmtObj->getContentObject($id, $isFolder);
                            if(isset($contentGroupObj))
                                $contentGroupId = $contentGroupObj->group_id;
                        }

                        if(!$isFolder)
                        {
                            $depMgmtObj->deleteContent($id, $isFolder);
                        }
                        else
                        {
                            $depMgmtObj->softDeleteContent($id, $isFolder, $deletePermanently, $removedAt);
                        }
                    }
                    CommonFunctionClass::setLastSyncTs($userId, $loginToken);
                                   
                    $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder, $contentGroupId);
                    $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder, $contentGroupId);
                    $response["serverGrpId"] = $contentGroupId;
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
     * Toggle Content Marking.
     *
     * @return json array
     */
    public function toggleContentMarking()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userContent = $depMgmtObj->getContentObject($id, $isFolder);   
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId();            
               
                if(isset($userContent) && $userContent->is_removed == 0)
                {
                    $status = 1;
                        
                    $existingStatus = $userContent->is_marked;
                    $newStatus = 1;
                    if($existingStatus == $newStatus)
                        $newStatus = 0;
                    $userContent->is_marked = $newStatus;
                    $userContent->save();
                    
                    CommonFunctionClass::setLastSyncTs($userId, $loginToken);
                    
                    if($orgId > 0)
                    {
                        $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $id);
                    }
                    else
                    {
                        $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $id);
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
     * Move Multiple Content To Folder.
     *
     * @return json array
     */
    public function moveContentToFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $folderId = (Input::get('folderId'));
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0 && $folderId != "")
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

                $folderId = sracDecryptNumberData($folderId, $userSession);
                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                        
                $isFolder = TRUE;
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                for($i=0; $i<count($idArr); $i++)
                {
                    $status = 1;                    
                    $id = $idArr[$i];
                    $userContent = $depMgmtObj->getContentObject($id, $isFolder);   
                    if(isset($userContent) && $userContent->is_removed == 0)
                    {
                        $userContent->folder_id = $folderId;
                        $userContent->save();
                        
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
     * Copy Multiple Content To Folder.
     *
     * @return json array
     */
    public function copyContentToFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $folderId = (Input::get('folderId'));
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0 && $folderId != "")
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

                $folderId = sracDecryptNumberData($folderId, $userSession);
                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $isFolder = TRUE;
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $syncIdArr = array();               
                $syncAttachmentIdArr = array();             
                $syncAttachmentCntArr = array();                
                for($i=0; $i<count($idArr); $i++)
                {
                    $status = 1;
                    
                    $id = $idArr[$i];
                                       
                    $content = $depMgmtObj->getContentObject($id, $isFolder);
                    $contentTags = $depMgmtObj->getContentTags($id, $userId, $isFolder);
                    $contentAttachments = $depMgmtObj->getContentAttachments($id, $isFolder);                   
                    if(isset($content) && $content->is_removed == 0)
                    {                   
                        $contentText = "";
                        if(isset($content->content) && $content->content != "")
                        {
                            try
                            {
                                $contentText = Crypt::decrypt($content->content);
                            } 
                            catch (DecryptException $e) 
                            {
                                
                            }
                        }
                        
                        $contentTypeId = $content->content_type_id;
                        $isMarked = $content->is_marked;
                        $fromTimeStamp = $content->from_timestamp;
                        $toTimeStamp = $content->to_timestamp;
                        $colorCode = $content->color_code;
                        $isLocked = $content->is_locked;
                        $isShareEnabled = $content->is_share_enabled;
                        $remindBeforeMillis = $content->remind_before_millis;
                        $repeatDuration = $content->repeat_duration;
                        $sourceId = $content->source_id;                        
                        $createTimeStamp = CommonFunctionClass::getCreateTimestamp();
                        $updateTimeStamp = $createTimeStamp;
                        $isCompleted = Config::get('app_config.default_content_is_completed_status');
                        $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
                        $reminderTimestamp = $content->reminder_timestamp;
                        
                        $contTagsArr = array();
                        foreach($contentTags as $conTag)
                        {
                            $tagId = $conTag->tag_id;
                            array_push($contTagsArr, $tagId);
                        }
                        
                        $conAddDet = $depMgmtObj->addEditContent(0, $contentText, $contentTypeId, $folderId, $sourceId, $contTagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, NULL, "");
                        
                        $newContentId = $conAddDet['syncId'];
                        $newContentAttachmentIdArr = array();

                        if(isset($contentAttachments))
                        {
                            foreach ($contentAttachments as $contentAttachment) 
                            {
                                $currFile = $contentAttachment->server_filename;
                                $filename = $contentAttachment->filename;
                                $fileSize = $contentAttachment->filesize;
                                $cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                                $cloudFileUrl = $contentAttachment->cloud_file_url;
                                $cloudFileId = $contentAttachment->cloud_file_id;
                                $cloudFileThumbStr = $contentAttachment->cloud_file_thumb_str;
                                $attCreateTs = $contentAttachment->create_ts;
                                $attUpdateTs = $contentAttachment->update_ts;
                                
                                $availableKbs = $depMgmtObj->getAvailableUserQuota($isFolder);

                                if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $fileSize))
                                {
                                    if($cloudStorageTypeId > 0)
                                    {
                                        $serverFileName = '';
                                    }
                                    else
                                    {
                                        $serverFileDetails = FileUploadClass::makeAttachmentCopy($currFile, $orgId);
                                        $serverFileName = $serverFileDetails['name'];
                                    }
                                    
                                    $attResponse = $depMgmtObj->addEditContentAttachment(0, $newContentId, $filename, $serverFileName, $fileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $updateTimeStamp);
                                    if(isset($attResponse['syncId']))
                                    {
                                        $attServerId = $attResponse['syncId'];  

                                        if($cloudStorageTypeId == 0)
                                        {
                                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $serverFileName); 
                                        }
                                        else
                                        {
                                            $attachmentUrl = $cloudFileUrl; 
                                        }  
                                          
                                        $attServerObj = array();    
                                        $attServerObj['name'] = $filename;
                                        $attServerObj['pathname'] = $serverFileName;
                                        $attServerObj['size'] = $fileSize;
                                        $attServerObj['url'] = $attachmentUrl;
                                        $attServerObj['performDownload'] = 1;
                                        $attServerObj['syncId'] = $attServerId;
                                        $attServerObj['cloudStorageTypeId'] = $cloudStorageTypeId;
                                        $attServerObj['cloudFileUrl'] = $cloudFileUrl;
                                        $attServerObj['cloudFileId'] = $cloudFileId;
                                        $attServerObj['attCreateTs'] = $attCreateTs;
                                        $attServerObj['attUpdateTs'] = $attUpdateTs;
                                        
                                        array_push($newContentAttachmentIdArr, $attServerObj);
                                    }
                                }                                   
                            }
                        }                           

                        $syncIdArr[$i] = $newContentId;
                        $syncAttachmentIdArr[$i] = $newContentAttachmentIdArr;
                        $syncAttachmentCntArr[$i] = count($newContentAttachmentIdArr);
                        
                        if($orgId > 0)
                        {
                            $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $newContentId);
                        }
                        else
                        {
                            $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $newContentId);
                        }
                    }
                }   
                $depMgmtObj->recalculateUserQuota($isFolder);
                $response['syncIdArr'] = $syncIdArr;
                $response['attachmentCntArr'] = $syncAttachmentCntArr;
                $response['attachmentsArr'] = $syncAttachmentIdArr;
                                   
                $response["allocKb"] = $depMgmtObj->getAllocatedUserQuota($isFolder);
                $response["usedKb"] = $depMgmtObj->getUsedUserQuota($isFolder);
                
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
     * Merge Multiple Content Into One.
     *
     * @return json array
     */
    public function mergeContents()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            if(isset($isFolderFlag) && $isFolderFlag != "")
            {
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                    $isFolder = TRUE;
            }
            else
                $isFolder = TRUE;
            
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);

                $userEmail = $user->email;
                
                if(count($idArr) > 1)
                {
                    $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                    $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
        
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    
                    if($orgEmpId > 0)
                    {
                        $orgEmp = $depMgmtObj->getEmployeeObject();
                        $userEmail = $orgEmp->email;
                    }
                        
                    $mergeToContentId = 0;
                    $mergeToContent = "";
                    $mergeTags = array();
                    $existingTags = array();
                    for($i=0; $i<count($idArr); $i++)
                    {
                        $status = 1;                        
                        $id = $idArr[$i];
                        $userContent = $depMgmtObj->getContentObject($id, $isFolder);  
                        if(isset($userContent) && $userContent->is_removed == 0)
                        {
                            $decContent = "";
                            if(isset($userContent->content) && $userContent->content != "")
                            {
                                try
                                {
                                    $decContent = Crypt::decrypt($userContent->content);
                                } 
                                catch (DecryptException $e) 
                                {
                                    //
                                }
                            }
                            
                            if($mergeToContent != "") {
                                $mergeToContent .= "<br/>";
                            }
                    
                            $mergeToContent .= $decContent;
                            $contentTags = $depMgmtObj->getContentTags($id, $userId, $isFolder); 
                            foreach ($contentTags as $contentTag) 
                            {
                                array_push($mergeTags, $contentTag->tag_id);
                                if($mergeToContentId != 0)
                                    $contentTag->delete();
                            }

                            if($mergeToContentId == 0)
                            {
                                $mergeToContentId = $id;
                            }
                            else
                            {
                                $contentAttachments = $depMgmtObj->getContentAttachments($id, $isFolder);
                                foreach($contentAttachments as $contentAttachment) 
                                {
                                    if($orgId > 0)
                                        $contentAttachment->employee_content_id = $mergeToContentId;
                                    else
                                        $contentAttachment->appuser_content_id = $mergeToContentId;
                                    $contentAttachment->save();
                                }                              
                                
                                $depMgmtObj->deleteContent($id, $isFolder, $userEmail);
                            }                            
                        }
                    }
                    $mergedContent = $depMgmtObj->getContentObject($mergeToContentId, $isFolder);
                    $mergedContent->content = Crypt::encrypt($mergeToContent);
                    $mergedContent->save();

                    if($isFolder)
                    {
                        $uniqueTags = array_unique($mergeTags);
                    }
                    else
                    {
                        $uniqueTags = array();
                    }
                    $depMgmtObj->addEditContentTags($mergeToContentId, $uniqueTags);
                    
                    if($orgId > 0)
                    {
                        $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $mergeToContentId);
                    }
                    else
                    {
                        $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $mergeToContentId);
                    }
                    
                    $depMgmtObj->recalculateUserQuota($isFolder);
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
     * Add Tags To Multiple Content.
     *
     * @return json array
     */
    public function addTagsToContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $idArr = Input::get('idArr');
        $tagArr = Input::get('tagArr');
        $isFolder = Input::get('isFolder');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);
        $tagArr = jsonDecodeArrStringIfRequired($tagArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                $tagArr = sracDecryptNumberArrayData($tagArr, $userSession);
                
                $isFolderFlag = TRUE;
                if($isFolder == 0)
                    $isFolderFlag = FALSE;
                
                $addTags = array(); 
                foreach($tagArr as $tagId)
                {
                    array_push($addTags, $tagId);
                }
                    
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                for($i=0; $i<count($idArr); $i++)
                {
                    $status = 1;
                         
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    
                    $id = $idArr[$i]; 
                    $userContent = $depMgmtObj->getContentObject($id, $isFolderFlag);    
                    

                    if(isset($userContent) && $userContent->is_removed == 0)
                    { 
                        $contTags = array();
                        $contentTags = $depMgmtObj->getContentTags($id, $userId, TRUE); 
                        foreach ($contentTags as $contentTag) 
                        {
                            array_push($contTags, $contentTag->tag_id);
                        }
                        $mergeTags = array_merge($addTags, $contTags);
                        $uniqueTags = array_unique($mergeTags);                    
                    
                        if($isFolderFlag)
                            $depMgmtObj->addEditContentTags($id, $uniqueTags);
                        else                    
                        {
                            $empOrUserId = $userId;
                            if($orgId > 0)
                                $empOrUserId = $orgEmpId;
                                
                            $depMgmtObj->addEditGroupContentTags($id, $empOrUserId, $uniqueTags);                       
                        }
                    }
                      
                    CommonFunctionClass::setLastSyncTs($userId, $loginToken);   
                        
                    if($orgId > 0)
                    {
                        $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolderFlag, $id);
                    }
                    else
                    {
                        $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolderFlag, $id);
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
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Copy Multiple Content To Organization.
     *
     * @return json array
     */
    public function copyContentToOrganization()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $encActOrgId = Input::get('actionOrgId');
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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
                $isFolder = TRUE;
                
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $syncIdArr = array();           
                $syncAttachmentCntArr = array();
                $syncAttachmentIdArr = array();
                
                $depObj = New ContentDependencyManagementClass;
                $depObj->withOrgKey($user, $encOrgId);
                
                $actDepObj = New ContentDependencyManagementClass;
                $actDepObj->withOrgKey($user, $encActOrgId);
                
                for($i=0; $i<count($idArr); $i++)
                {
                    $status = 1;
                    
                    $id = $idArr[$i]; 
                    $content = $depObj->getContentObject($id, $isFolder);
                    $contentAttachments = $depObj->getContentAttachments($id, $isFolder);
                    
                    $actConstant = $actDepObj->getEmployeeOrUserConstantObject();
                    
                    if(isset($content) && $content->is_removed == 0 && isset($actConstant))
                    {
                        $contentText = "";
                        if(isset($content->content) && $content->content != "")
                        {
                            try
                            {
                                $contentText = Crypt::decrypt($content->content);
                            } 
                            catch (DecryptException $e) 
                            {
                                //
                            }
                        }
                            
                        $contentTypeId = $content->content_type_id;
                        $isMarked = $content->is_marked;
                        $fromTimeStamp = $content->from_timestamp;
                        $toTimeStamp = $content->to_timestamp;
                        $colorCode = $content->color_code;
                        $isLocked = $content->is_locked;
                        $isShareEnabled = $content->is_share_enabled;
                        $remindBeforeMillis = $content->remind_before_millis;
                        $repeatDuration = $content->repeat_duration;
                        $isCompleted = Config::get('app_config.default_content_is_completed_status');
                        $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
                        $reminderTimestamp = $content->reminder_timestamp;

                        $createTimeStamp = CommonFunctionClass::getCreateTimestamp();
                        $updateTimeStamp = $createTimeStamp;
                        
                        $defFolderId = $actConstant->def_folder_id;
                        $emailSourceId = $actConstant->email_source_id;
                        
                        $conAddDet = $actDepObj->addEditContent(0, $contentText, $contentTypeId, $defFolderId, $emailSourceId, NULL, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, NULL, "");
                        
                        $newContentId = $conAddDet['syncId'];
                        $newContentAttachmentIdArr = array();
                        if(isset($contentAttachments))
                        {
                            foreach ($contentAttachments as $contentAttachment) 
                            {
                                $currFile = $contentAttachment->server_filename;
                                $filename = $contentAttachment->filename;
                                $fileSize = $contentAttachment->filesize;
                                $cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                                $cloudFileUrl = $contentAttachment->cloud_file_url;
                                $cloudFileId = $contentAttachment->cloud_file_id;
                                $cloudFileThumbStr = $contentAttachment->cloud_file_thumb_str;
                                $attCreateTs = $contentAttachment->create_ts;
                                $attUpdateTs = $contentAttachment->update_ts;
                                
                                $availableKbs = $actDepObj->getAvailableUserQuota($isFolder);

                                if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $fileSize))
                                {
                                    if($cloudStorageTypeId > 0)
                                    {
                                        $serverFileName = '';
                                    }
                                    else
                                    {
                                        $serverFileDetails = FileUploadClass::makeAttachmentCopy($currFile, $orgId);
                                        $serverFileName = $serverFileDetails['name'];
                                    }
                                    
                                    $attResponse = $actDepObj->addEditContentAttachment(0, $newContentId, $filename, $serverFileName, $fileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $updateTimeStamp);
                                    if(isset($attResponse['syncId']))
                                    {
                                        $attServerId = $attResponse['syncId'];
                                        if($cloudStorageTypeId == 0)
                                        {
                                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $serverFileName); 
                                        }
                                        else
                                        {
                                            $attachmentUrl = $cloudFileUrl; 
                                        }
                                          
                                        $attServerObj = array();    
                                        $attServerObj['name'] = $filename;
                                        $attServerObj['pathname'] = $serverFileName;
                                        $attServerObj['size'] = $fileSize;
                                        $attServerObj['url'] = $attachmentUrl;
                                        $attServerObj['performDownload'] = 1;
                                        $attServerObj['syncId'] = $attServerId;
                                        $attServerObj['cloudStorageTypeId'] = $cloudStorageTypeId;
                                        $attServerObj['cloudFileUrl'] = $cloudFileUrl;
                                        $attServerObj['cloudFileId'] = $cloudFileId;
                                        $attServerObj['attCreateTs'] = $attCreateTs;
                                        $attServerObj['attUpdateTs'] = $attUpdateTs;
                                        
                                        array_push($newContentAttachmentIdArr, $attServerObj);
                                    }
                                    
                                }                               
                            }
                        }

                        $syncIdArr[$i] = $newContentId;
                        $syncAttachmentCntArr[$i] = count($newContentAttachmentIdArr);
                        $syncAttachmentIdArr[$i] = $newContentAttachmentIdArr;
                        
                        if($orgId > 0)
                        {
                            $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $newContentId);
                        }
                        else
                        {
                            $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $newContentId);
                        }          
                    }
                }  
                $actDepObj->recalculateUserQuota($isFolder);
                 
                $response['syncIdArr'] = $syncIdArr;
                $response['attachmentCntArr'] = $syncAttachmentCntArr;
                $response['attachmentsArr'] = $syncAttachmentIdArr;
                                   
                $response["allocKb"] = $actDepObj->getAllocatedUserQuota($isFolder);
                $response["usedKb"] = $actDepObj->getUsedUserQuota($isFolder);
                
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
    
    public function loadContentCloudAttachmentSelectionModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $forContent = Input::get('forContent');
        $forImport = Input::get('forImport');

        if(!isset($forContent) || $forContent != 1)
        {
            $forContent = 0;
        }

        if(!isset($forImport) || $forImport != 1)
        {
            $forImport = 0;
        }
        
        $response = array();
        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $modalTitle = "Select Attachment";

                        $viewDetails = array();
                        $viewDetails['modalTitle'] = $modalTitle;
                        $viewDetails['cloudStorageTypeCode'] = $cloudStorageTypeCode;
                        $viewDetails['forContent'] = $forContent;
                        $viewDetails['forImport'] = $forImport;
                        
                        $_viewToRender = View::make('cloudAttachment.cloudAttachmentSelectionModal', $viewDetails);
                        $_viewToRender = $_viewToRender->render();
                        
                        $response['view'] = $_viewToRender;  
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    
    public function sortContentModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $sortBy = Input::get('sortBy');
        $sortOrder = Input::get('sortOrder');
        $isFavoritesTab = Input::get('isFavoritesTab');
        
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
                
                $typeR = Config::get("app_config.content_type_r");
                $typeC = Config::get("app_config.content_type_c");
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $appHasCloudStorage = $depMgmtObj->getAppKeyMappingHasCloudStorage();
                $appHasTypeReminder = $depMgmtObj->getAppKeyMappingHasTypeReminder();
                $appHasTypeCalendar = $depMgmtObj->getAppKeyMappingHasTypeCalendar();
                $appHasVideoConference = $depMgmtObj->getAppKeyMappingHasVideoConference();
                $appHasSourceSelection = $depMgmtObj->getAppKeyMappingHasSourceSelection();
                $appHasFolderSelection = $depMgmtObj->getAppKeyMappingHasFolderSelection();
                
                $isFolder = FALSE;
                $pageDesc = "Group";
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                    $pageDesc = "Folder";
                }
                
                $pageDesc = "Sort ".$pageDesc." Content";

                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['isFolderFlag'] = $isFolderFlag;
                $viewDetails['isFolder'] = $isFolder;
                $viewDetails['sortBy'] = $sortBy;
                $viewDetails['isFavoritesTab'] = $isFavoritesTab;
                $viewDetails['appHasCloudStorage'] = $appHasCloudStorage;
                $viewDetails['appHasTypeReminder'] = $appHasTypeReminder;
                $viewDetails['appHasTypeCalendar'] = $appHasTypeCalendar;
                $viewDetails['appHasVideoConference'] = $appHasVideoConference;
                $viewDetails['appHasSourceSelection'] = $appHasSourceSelection;
                $viewDetails['appHasFolderSelection'] = $appHasFolderSelection;
                
                $_viewToRender = View::make('content.partialview._sortModal', $viewDetails);
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
    
    public function filterContentModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $isFavoritesTab = Input::get('isFavoritesTab');
        $folderOrGroupId = (Input::get('folderOrGroupId'));
        $hasFilters = Input::get('hasFilters');
        $isVirtualFolder = Input::get('isVirtualFolder');
        $virtualFolderId = (Input::get('virtualFolderId'));
        $filtersNonModifiable = Input::get('filtersNonModifiable');

        if(!isset($filtersNonModifiable) || $filtersNonModifiable != 1)
        {
            $filtersNonModifiable = 0;
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

                $folderOrGroupId = sracDecryptNumberData($folderOrGroupId, $userSession);
                $virtualFolderId = sracDecryptNumberData($virtualFolderId, $userSession);

        
                $filFolderArr = array();
                $filGroupArr = array();
                $filSourceArr = array();
                $filTagArr = array();
                $filTypeArr = array();
                $filAttachmentTypeArr = array();
                $filFromDate = 0;
                $filToDate = 0;
                $chkIsStarred = 0;
                $chkIsUntagged = 0;
                $chkIsLocked = 0;
                $chkIsConversation = 0;
                $chkShowFolder = 0;
                $chkShowGroup = 0;
                $chkIsUnread = 0;
                $chkIsRestricted = 0;
                $filAttachmentStatus = -1;
                $filRepeatStatus = -1;
                $filCompletedStatus = -1;
                $filSenderEmail = '';
                $filFromDate = 0;
                $filDateDayCount = 0;
                $filDateFilterType = Config::get('app_config.filter_date_type_id_default');
                        
                if(isset($hasFilters) && $hasFilters == 1)
                {
                    $filFolderArr = sracDecryptNumberArrayData(Input::get('filFolderArr'), $userSession);
                    $filGroupArr = sracDecryptNumberArrayData(Input::get('filGroupArr'), $userSession);
                    $filSourceArr = sracDecryptNumberArrayData(Input::get('filSourceArr'), $userSession);
                    $filTypeArr = Input::get('filTypeArr');
                    $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
                    $filTagArr = sracDecryptNumberArrayData(Input::get('filTagArr'), $userSession);
                    $filFromDate = Input::get('fromTimeStamp');
                    $filToDate = Input::get('toTimeStamp');  
                    $chkIsStarred = Input::get('chkIsStarred');  
                    $chkIsUntagged = Input::get('chkIsUntagged');
                    $chkIsLocked = Input::get('chkIsLocked');
                    $chkIsConversation = Input::get('chkIsConversation');
                    $chkShowFolder = Input::get('chkShowFolder');
                    $chkShowGroup = Input::get('chkShowGroup');
                    $chkIsUnread = Input::get('chkIsUnread');
                    $chkIsRestricted = Input::get('chkIsRestricted');
                    $filAttachmentStatus = Input::get('filShowAttachment');
                    $filRepeatStatus = Input::get('filRepeatStatus');
                    $filCompletedStatus = Input::get('filCompletedStatus');
                    $filSenderEmail = Input::get('filSenderEmail');
                    $filDateDayCount = Input::get('filDateDayCount'); 
                    $filDateFilterType = Input::get('filDateFilterType');  
                }
                else
                {
                    if($isFolderFlag == 1)
                    {
                        $chkShowFolder = 1;

                        if($folderOrGroupId <= 0)
                        {
                            $chkShowGroup = 1;
                        }
                        else
                        {
                            array_push($filFolderArr, $folderOrGroupId);
                        }
                    }
                    else
                    {
                        $chkShowGroup = 1;

                        if($folderOrGroupId > 0)
                        {
                            array_push($filGroupArr, $folderOrGroupId);
                        }
                    }
                }

                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $appHasCloudStorage = $depMgmtObj->getAppKeyMappingHasCloudStorage();
                $appHasTypeReminder = $depMgmtObj->getAppKeyMappingHasTypeReminder();
                $appHasTypeCalendar = $depMgmtObj->getAppKeyMappingHasTypeCalendar();
                $appHasVideoConference = $depMgmtObj->getAppKeyMappingHasVideoConference();
                $appHasSourceSelection = $depMgmtObj->getAppKeyMappingHasSourceSelection();
                $appHasFolderSelection = $depMgmtObj->getAppKeyMappingHasFolderSelection();
                
                $isPremiumUser = OrganizationClass::isPremiumUser($userId);
                $orgId = $depMgmtObj->getOrganizationId();
                
                if($isPremiumUser || $orgId > 0)
                {
                    $status = 1;

                    if(isset($isVirtualFolder) && $isVirtualFolder == 1 && $hasFilters == 0)
                    {
                        $virtualFolderObj = $depMgmtObj->getFolderObject($virtualFolderId);
                        if(isset($virtualFolderObj))
                        {
                            $virtualFolderFilterStr = $virtualFolderObj->applied_filters;

                            $folderFilterUtilObj = New FolderFilterUtilClass;
                            $folderFilterUtilObj->setFilterStr($virtualFolderFilterStr);

                            $hasFilters = 1;

                            $chkIsConversation = $folderFilterUtilObj->getFilterValueIsConversation();
                            $chkIsUntagged = $folderFilterUtilObj->getFilterValueIsUntagged();
                            $chkIsLocked = $folderFilterUtilObj->getFilterValueIsLocked();
                            $chkIsStarred = $folderFilterUtilObj->getFilterValueIsStarred();
                            $chkIsUnread = $folderFilterUtilObj->getFilterValueIsUnread();
                            $chkDownloadStatus = $folderFilterUtilObj->getFilterValueDownloadStatus();
                            $chkIsRestricted = $folderFilterUtilObj->getFilterValueIsRestricted();
                            $filFromDate = $folderFilterUtilObj->getFilterValueStartDateTs();
                            $filToDate = $folderFilterUtilObj->getFilterValueEndDateTs();
                            $filTypeArr = $folderFilterUtilObj->getFilterValueContentType();
                            $filFolderArr = $folderFilterUtilObj->getFilterValueFolder();
                            $filGroupArr = $folderFilterUtilObj->getFilterValueGroup();
                            $filSourceArr = $folderFilterUtilObj->getFilterValueSource();
                            $filTagArr = $folderFilterUtilObj->getFilterValueTag();
                            $filAttachmentTypeArr = $folderFilterUtilObj->getFilterValueAttachmentType();
                            $chkAttachmentStatus = $folderFilterUtilObj->getFilterValueAttachmentStatus();
                            $chkRepeatStatus = $folderFilterUtilObj->getFilterValueRepeatStatus();
                            $chkCompletedStatus = $folderFilterUtilObj->getFilterValueCompletedStatus();
                            $filSenderEmail = $folderFilterUtilObj->getFilterValueSenderEmail();
                            $filDateDayCount = $folderFilterUtilObj->getFilterValueDateFilterTypeDayCount();
                            $filDateFilterType = $folderFilterUtilObj->getFilterValueDateFilterType();

                            $chkShowFolder = $folderFilterUtilObj->getFilterValueIsShowFolder();
                            $chkShowGroup = $folderFilterUtilObj->getFilterValueIsShowGroup();
                        }
                    }
                    
                    $isFolder = FALSE;
                    $pageDesc = "Group";
                    if($isFolderFlag == 1)
                    {
                        $isFolder = TRUE;
                        $pageDesc = "Folder";
                    }

                    $isSenderVirtualFolder = 0;
                    $senderVirtualFolderEmail = '';
                    if($isFolder && $folderOrGroupId > 0)
                    {
                        $folderObj = $depMgmtObj->getFolderObject($folderOrGroupId);
                        if(isset($folderObj) && $folderObj->folder_type_id == FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID)
                        {
                            $isSenderVirtualFolder = 1;  
                            $senderVirtualFolderEmail = $folderObj->virtual_folder_sender_email;                          
                        }
                    }
                    
                    $pageDesc = "Filter ".$pageDesc." Content";
                        
                    $typeArr = array();
                    $contentTypes = $depMgmtObj->getAllContentTypes();
                    foreach($contentTypes as $type)
                    {
                        $typeArr[$type->content_type_id] = $type->type_name;
                    }   
                        
                    $attachmentTypeArr = Config::get('app_config.filter_attachment_type_text_array'); 

                    $showAttachmentArr = Config::get('app_config.filter_show_attachment_text_array'); 

                    $repeatStatusArr = Config::get('app_config.filter_repeat_status_text_array'); 
                    $completedStatusArr = Config::get('app_config.filter_completed_status_text_array'); 

                    $dateFilterTypeArr = Config::get('app_config.filter_date_type_array'); 
                    $dateFilterTypeIdNone = Config::get('app_config.filter_date_type_id_none'); 
                    $dateFilterTypeIdDateRange = Config::get('app_config.filter_date_type_id_date_range'); 
                    $dateFilterTypeIdDayCount = Config::get('app_config.filter_date_type_id_day_count'); 

                    if($filDateFilterType == $dateFilterTypeIdNone)
                    {
                        $filFromDate = '';
                        $filToDate = '';
                        $filDateDayCount = '';
                    }
                    else if($filDateFilterType == $dateFilterTypeIdDateRange)
                    {
                        $filDateDayCount = '';
                    }
                    else if($filDateFilterType == $dateFilterTypeIdDayCount)
                    {
                        $filFromDate = '';
                        $filToDate = '';
                    }
                    
                    $selGroupArr = array();
                    $selGroupIdArr = array();
                    if(count($filGroupArr) > 0)
                    {
                        foreach($filGroupArr as $groupId)
                        {
                            $groupObj = $depMgmtObj->getGroupObject($groupId);
                            
                            if(isset($groupObj)){
                                $groupName = $groupObj->name;
                                
                                $selGroupArr[$groupId] = $groupName;
                                array_push($selGroupIdArr, $groupId);
                            }
                        }
                    }   
                    
                    $selFolderArr = array();
                    $selFolderIdArr = array();
                    if(count($filFolderArr) > 0)
                    {
                        foreach($filFolderArr as $folderId)
                        {
                            $folderObj = $depMgmtObj->getFolderObject($folderId);
                            
                            if(isset($folderObj)){
                                $folderName = $folderObj->folder_name;
                                
                                $selFolderArr[$folderId] = $folderName;
                                array_push($selFolderIdArr, $folderId);
                            }
                        }
                    }
                    
                    $selSourceArr = array();
                    $selSourceIdArr = array();
                    if(count($filSourceArr) > 0)
                    {
                        foreach($filSourceArr as $sourceId)
                        {
                            $sourceObj = $depMgmtObj->getSourceObject($sourceId);
                            
                            if(isset($sourceObj)){
                                $sourceName = $sourceObj->source_name;
                                
                                $selSourceArr[$sourceId] = $sourceName;
                                array_push($selSourceIdArr, $sourceId);
                            }
                        }
                    }
                    
                    $selTagArr = array();
                    $selTagIdArr = array();
                    if(count($filTagArr) > 0)
                    {
                        foreach($filTagArr as $tagId)
                        {
                            $tagObj = $depMgmtObj->getTagObject($tagId);
                            
                            if(isset($tagObj)){
                                $tagName = $tagObj->tag_name;
                                
                                $selTagArr[$tagId] = $tagName;
                                array_push($selTagIdArr, $tagId);
                            }
                        }
                    }
                    
                    $selSenderEmailArr = array();
                    $selSenderEmailIdArr = array();
                    if(isset($filSenderEmail) && $filSenderEmail != "")
                    {
                        $empOrUserName = $depMgmtObj->getEmployeeOrUserNameByEmail($filSenderEmail);
                        
                        $response['empOrUserName'] = $empOrUserName;

                        if(isset($empOrUserName) && $empOrUserName != ""){                            
                            $selSenderEmailArr[$filSenderEmail] = $empOrUserName." [".$filSenderEmail."]";
                            array_push($selSenderEmailIdArr, $filSenderEmail);
                        }
                    }
                
                    //$intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");  
                    
                    $isAllNotes = FALSE;
                    if($isFolder && $folderOrGroupId == -1) {
                        $isAllNotes = TRUE;
                    }

                    $viewDetails['page_description'] = $pageDesc;
                    $viewDetails['isFolderFlag'] = $isFolderFlag;
                    $viewDetails['isFolder'] = $isFolder;
                    $viewDetails['folderOrGroupId'] = $folderOrGroupId;
                    //$viewDetails['intJs'] = $intJs;
                    $viewDetails['typeArr'] = $typeArr;     
                    $viewDetails['attachmentTypeArr'] = $attachmentTypeArr;     
                    $viewDetails['showAttachmentArr'] = $showAttachmentArr;     
                    $viewDetails['repeatStatusArr'] = $repeatStatusArr;     
                    $viewDetails['completedStatusArr'] = $completedStatusArr;     
                    $viewDetails['isFavoritesTab'] = $isFavoritesTab;   
                    $viewDetails['isAllNotes'] = $isAllNotes;   
                        
                    $viewDetails['selFolderArr'] = $selFolderArr;       
                    $viewDetails['selFolderIdArr'] = $selFolderIdArr;   
                    $viewDetails['selGroupArr'] = $selGroupArr;     
                    $viewDetails['selGroupIdArr'] = $selGroupIdArr; 
                    $viewDetails['selSourceArr'] = $selSourceArr;       
                    $viewDetails['selSourceIdArr'] = $selSourceIdArr;   
                    $viewDetails['selTagArr'] = $selTagArr;     
                    $viewDetails['selTagIdArr'] = $selTagIdArr;
                    $viewDetails['selSenderEmailArr'] = $selSenderEmailArr;     
                    $viewDetails['selSenderEmailIdArr'] = $selSenderEmailIdArr; 
                    $viewDetails['selTypeIdArr'] = $filTypeArr; 
                    $viewDetails['selAttachmentTypeIdArr'] = $filAttachmentTypeArr; 
                    $viewDetails['selShowAttachmentId'] = $filAttachmentStatus;
                    $viewDetails['selRepeatStatusId'] = $filRepeatStatus;
                    $viewDetails['selCompletedStatusId'] = $filCompletedStatus;
                    $viewDetails['filSenderEmail'] = $filSenderEmail;
                    
                    $viewDetails['dateFilterTypeArr'] = $dateFilterTypeArr;
                    $viewDetails['filDateFilterType'] = $filDateFilterType;
                    $viewDetails['filFromDate'] = $filFromDate;
                    $viewDetails['filToDate'] = $filToDate;
                    $viewDetails['filDateDayCount'] = $filDateDayCount;
                        
                    $viewDetails['chkIsStarred'] = $chkIsStarred;   
                    $viewDetails['chkIsUntagged'] = $chkIsUntagged; 
                    $viewDetails['chkIsLocked'] = $chkIsLocked; 
                    $viewDetails['chkIsConversation'] = $chkIsConversation; 
                    $viewDetails['chkShowFolder'] = $chkShowFolder; 
                    $viewDetails['chkShowGroup'] = $chkShowGroup;  
                    $viewDetails['chkIsUnread'] = $chkIsUnread; 
                    $viewDetails['chkIsRestricted'] = $chkIsRestricted;
                    $viewDetails['isVirtualFolder'] = $isVirtualFolder; 
                    $viewDetails['virtualFolderId'] = $virtualFolderId;
                    $viewDetails['filtersNonModifiable'] = $filtersNonModifiable;
                    
                    $viewDetails['appHasCloudStorage'] = $appHasCloudStorage;
                    $viewDetails['appHasTypeReminder'] = $appHasTypeReminder;
                    $viewDetails['appHasTypeCalendar'] = $appHasTypeCalendar;
                    $viewDetails['appHasVideoConference'] = $appHasVideoConference;
                    $viewDetails['appHasSourceSelection'] = $appHasSourceSelection;
                    $viewDetails['appHasFolderSelection'] = $appHasFolderSelection;

                    $viewDetails['isSenderVirtualFolder'] = $isSenderVirtualFolder;
                    $viewDetails['senderVirtualFolderEmail'] = $senderVirtualFolderEmail;
                    
                    $_viewToRender = View::make('content.partialview._filterModal', $viewDetails);
                    $_viewToRender = $_viewToRender->render();
                    
                    $response['view'] = $_viewToRender;
                    $response['filSenderEmail'] = $filSenderEmail;
                    $response['selSenderEmailArr'] = $selSenderEmailArr;
                    $response['selSenderEmailIdArr'] = $selSenderEmailIdArr;
                }   
                else
                {
                    $status = -1;
                    $msg = 'Premium feature not accessible without subscription';
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
     * Delete Multiple Content.
     *
     * @return json array
     */
    public function printMultiContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $offsetInMinutes = Input::get('ofs');  
        $tzStr = Input::get('tzStr'); 

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            if(isset($isFolderFlag) && $isFolderFlag != "")
            {
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                    $isFolder = TRUE;
            }
            else
                $isFolder = TRUE;
            
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                if(isset($idArr) && count($idArr) > 0)
                {
                    $status = 1;
                
                    $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                    $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                    
                    $empOrUserId = $userId;
                    if($orgId > 0)
                    {
                        $empOrUserId = $orgEmpId;
                    }
                
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    $depMgmtObj->setCurrentLoginToken($loginToken);
                    $constant = $depMgmtObj->getEmployeeOrUserConstantObject();
                    $userConstant = $depMgmtObj->getUserConstantObject();
                    
                    $printFields = array();
                    if(isset($userConstant))
                    {
                        $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
                        $printFieldsStr = $userConstant->print_fields;
                        $printFields = explode($passcodeFolderIdDelimiter, $printFieldsStr);
                    }
                    $canPrintCreateDate = FALSE;
                    $canPrintEventDate = FALSE;
                    $PRINT_EVENT_DATE_ID = 5;
                    $PRINT_CREATE_DATE_ID = 6;
                    if(in_array($PRINT_EVENT_DATE_ID, $printFields))
                        $canPrintEventDate = TRUE;
                    if(in_array($PRINT_CREATE_DATE_ID, $printFields))
                        $canPrintCreateDate = TRUE;
                
                    $typeR = Config::get("app_config.content_type_r");
                    $typeC = Config::get("app_config.content_type_c");

                    $searchStr = NULL;
                    $hasFilters = 0;
                    $filShowAttachment = -1;
                    $filAttachmentExtArr = array();
                                    
                    $contents = array();
                    foreach($idArr as $id)
                    {
                        $content = $depMgmtObj->getContentObject($id, $isFolder);
                            
                        if(isset($content))
                        {
                            $contentListFormulationObj = New ContentListFormulationClass;
                            $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                            $formContentObj = $contentListFormulationObj->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);

                            $contentText = "";
                            if(isset($content->content) && $content->content != "")
                            {
                                try
                                {
                                    $contentText = Crypt::decrypt($content->content);
                                } 
                                catch (DecryptException $e) 
                                {
                                    //
                                }
                            }
                        
                            $contentType = $content->content_type_id;
                            $contentTags = $depMgmtObj->getContentTags($id, $empOrUserId, $isFolder);                           
                            $tagStr = "";
                            foreach($contentTags as $contTag)
                            {
                                if($tagStr != "")
                                    $tagStr .= ", ";                                
                                $tag = $depMgmtObj->getTagObject($contTag->tag_id);                                 
                                $tagStr .= $tag->tag_name;
                            }
                            if($tagStr == "")
                                $tagStr = "-";
                                
                            $groupOrFolderName = "";
                            $sourceName = "";
                            if($isFolder)
                            {
                                $folderObj = $depMgmtObj->getFolderObject($content->folder_id);
                                $groupOrFolderName = $folderObj->folder_name;
                                
                                $sourceObj = $depMgmtObj->getSourceObject($content->source_id);
                                if(isset($sourceObj))
                                    $sourceName = $sourceObj->source_name;
                                    
                                if($sourceName == "")
                                    $sourceName = "-";
                            }
                            else
                            {
                                $groupObj = $depMgmtObj->getGroupObject($content->group_id);
                                $groupOrFolderName = $groupObj->name;
                            }
                            
                            $contentCreateDt = "";
                            $contentUpdateDt = "";
                            $contentEventDt = "";
                                    
                            if($offsetInMinutes != 0 && ($canPrintCreateDate || $canPrintEventDate))
                            {
                                $createUtcTs = $content->create_timestamp;
                                $createUtcTs = intval($createUtcTs/1000);
                                $locCreateDt = Carbon::createFromTimeStampUTC($createUtcTs);
                                
                                $updateUtcTs = $content->update_timestamp;
                                $updateUtcTs = intval($updateUtcTs/1000);
                                $locUpdateDt = Carbon::createFromTimeStampUTC($updateUtcTs);
                                
                                $fromUtcTs = $content->from_timestamp;
                                if(isset($fromUtcTs) && $fromUtcTs != "" && $fromUtcTs > 1000)
                                {
                                    $fromUtcTs = intval($fromUtcTs/1000);
                                    $locFromDt = Carbon::createFromTimeStampUTC($fromUtcTs);                        
                                }
                                
                                $toUtcTs = $content->to_timestamp;
                                if(isset($toUtcTs) && $toUtcTs != "" && $toUtcTs > 1000)
                                {
                                    $toUtcTs = intval($toUtcTs/1000);
                                    $locToDt = Carbon::createFromTimeStampUTC($toUtcTs);
                                }
                                
                                $offsetInMinutes = $offsetInMinutes*-1;
                                
                                $offsetIsNegative = 0;
                                if($offsetInMinutes < 0)
                                {
                                    $offsetIsNegative = 1;
                                    $offsetInMinutes = $offsetInMinutes*-1;
                                }
                                
                                $offsetHours =  $offsetInMinutes%60;                        
                                $offsetMinutes = $offsetInMinutes - ($offsetHours*60);
                                
                                if($offsetIsNegative == 1)
                                {
                                    if($offsetHours > 0)
                                    {
                                        $locCreateDt = $locCreateDt->subHours($offsetHours);
                                        $locUpdateDt = $locUpdateDt->subHours($offsetHours);
                                        
                                        if(isset($locFromDt) && $locFromDt != "")
                                        {
                                            $locFromDt = $locFromDt->subHours($offsetHours); 
                                        }
                                        
                                        if(isset($locToDt) && $locToDt != "")
                                        {
                                            $locToDt = $locToDt->subHours($offsetHours); 
                                        }
                                    }   
                                    if($offsetMinutes > 0)
                                    {
                                        $locCreateDt = $locCreateDt->subMinutes($offsetMinutes);
                                        $locUpdateDt = $locUpdateDt->subMinutes($offsetMinutes);
                                        
                                        if(isset($locFromDt) && $locFromDt != "")
                                        {
                                            $locFromDt = $locFromDt->subMinutes($offsetMinutes); 
                                        }
                                        
                                        if(isset($locToDt) && $locToDt != "")
                                        {
                                            $locToDt = $locToDt->subMinutes($offsetMinutes); 
                                        }
                                    }       
                                }
                                else
                                {
                                    if($offsetHours > 0)
                                    {   
                                        $locCreateDt = $locCreateDt->addHours($offsetHours);
                                        $locUpdateDt = $locUpdateDt->addHours($offsetHours);
                                        
                                        if(isset($locFromDt) && $locFromDt != "")
                                        {
                                            $locFromDt = $locFromDt->addHours($offsetHours); 
                                        }
                                        
                                        if(isset($locToDt) && $locToDt != "")
                                        {
                                            $locToDt = $locToDt->addHours($offsetHours); 
                                        }
                                    }       
                                    if($offsetMinutes > 0)
                                    {
                                        $locCreateDt = $locCreateDt->addMinutes($offsetMinutes);    
                                        $locUpdateDt = $locUpdateDt->addMinutes($offsetMinutes);    
                                        
                                        if(isset($locFromDt) && $locFromDt != "")
                                        {
                                            $locFromDt = $locFromDt->addMinutes($offsetMinutes); 
                                        }
                                        
                                        if(isset($locToDt) && $locToDt != "")
                                        {
                                            $locToDt = $locToDt->addMinutes($offsetMinutes); 
                                        }
                                    }                   
                                }
                    
                                $contentCreateDt = $locCreateDt->toFormattedDateString().' '.$locCreateDt->toTimeString();
                                $contentUpdateDt = $locUpdateDt->toFormattedDateString().' '.$locUpdateDt->toTimeString();
                                
                                $fromDispDateTime = "";
                                if(isset($locFromDt) && $locFromDt != "")
                                {
                                    $fromDispDateTime = $locFromDt->toFormattedDateString().' '.$locFromDt->toTimeString(); 
                                }
                                
                                $toDispDateTime = "";
                                if(isset($locToDt) && $locToDt != "")
                                {
                                    $toDispDateTime = $locToDt->toFormattedDateString().' '.$locToDt->toTimeString(); 
                                }   
                                
                                if($contentType == $typeR)
                                {
                                    $contentEventDt = "<b>On </b>".$fromDispDateTime;
                                }
                                elseif($contentType == $typeC)
                                {
                                    $contentEventDt = "<b>From </b>".$fromDispDateTime. "<b> to </b>".$toDispDateTime;
                                }
                                                        
                            }
                            
                            $contObj = array();                     
                            $contObj['source'] = $sourceName;
                            $contObj['groupOrFolder'] = $groupOrFolderName;
                            $contObj['tag'] = $tagStr;
                            $contObj['content'] = $contentText;
                            $contObj['createDt'] = $contentCreateDt;
                            $contObj['updateDt'] = $contentUpdateDt;
                            $contObj['eventDt'] = $contentEventDt;
                            $contObj['formContentObj'] = $formContentObj;
                    
                            array_push($contents, $contObj);                            
                        }
                    } 
                
                    $viewDetails = array();
                    $viewDetails['contents'] = $contents;
                    $viewDetails['printFields'] = $printFields;
                    $viewDetails['isFolder'] = $isFolder;
                    $viewDetails['tzStr'] = $tzStr;
                    $viewDetails['tzOfs'] = $offsetInMinutes;
               
                    $_viewToRender = View::make('content.partialview._contentPrint', $viewDetails);
                    $_viewToRender = $_viewToRender->render();
                    
                    $response['view'] = $_viewToRender;
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
    public function loadDeleteAppuserContentViaLinkView()
    {
        $msg = "";
        $status = 0;

        $encContentId = Input::get('c');         
    }
    
    /**
     * Register app user.
     *
     * @return json array
     */
    public function deleteAppuserContentViaLink()
    {
        $msg = "";
        $status = 0;

        $encContentId = Input::get('c');

        $response = array();

        if($encContentId != "")
        {
            $decDependencies = OrganizationClass::getAppuserContentDependenciesFromDeleteUrl($encContentId);
            if(isset($decDependencies))
            {
                $userId = $decDependencies['userId'];
                $orgId = $decDependencies['orgId'];
                $orgEmpId = $decDependencies['orgEmpId'];
                $contentId = $decDependencies['contentId'];             
                
                $user = Appuser::byId($userId)->first();
                
                $isFolder = TRUE;
                
                if(isset($user) && $user->is_verified)
                {
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);
                    $constObj = $depMgmtObj->getEmployeeOrUserConstantObject();
                    $contentObj = $depMgmtObj->getContentObject($contentId, $isFolder);
                    
                    if(isset($constObj) && isset($contentObj) && $contentObj->is_removed == 0)
                    {
                        $deletePermanently = 1;
                        $removedAt = CommonFunctionClass::getCreateTimestamp();

                        $depMgmtObj->softDeleteContent($contentId, $isFolder, $deletePermanently, $removedAt);
                        $status = 1;                    
                        $msg = "Content successfully removed";
                    }
                    else
                    {
                        $status = -1;                   
                        $msg = 'Content no longer available';
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
    
    public function getDueAppuserContents() 
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $minTs = Input::get('minTs');
        $maxTs = Input::get('maxTs');
        
        $response = array();
        if($encUserId != "" && $minTs > 0 && $maxTs > 0)
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
                
                $searchStr = '';
                $hasFilters = 0;
                $filAttachmentExtArr = array();
                $filShowAttachment = -1;
                
                /*$tsStrLen = strlen($minTs."");
                if($tsStrLen > 10)
                {
                    $diff = $tsStrLen - 10;
                    $divisor = pow ( 10 , $diff );
                    $minTs = intval($minTs/$divisor);                   
                }
                
                $tsStrLen = strlen($maxTs."");
                if($tsStrLen > 10)
                {
                    $diff = $tsStrLen - 10;
                    $divisor = pow ( 10 , $diff );
                    $maxTs = intval($maxTs/$divisor);                   
                }*/
                
                $status = 1;
                $allDueContents = array();
                $sortArrForDueContents = array();

                $contentListFormulationObj = New ContentListFormulationClass;
                $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                
                $isFolder = TRUE;
                $folderContents = $depMgmtObj->getAllDueContents($minTs, $maxTs, $isFolder, NULL);
                if(isset($folderContents))
                {
                    foreach($folderContents as $content) {
                        $contentObj = $contentListFormulationObj->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                        if(isset($contentObj)) {
                            $contentObj['isFolder'] = 1;
                            $contentObj['fOrGId'] = sracEncryptNumberData($content->folderId, $userSession);
                            array_push($allDueContents, $contentObj);
                            array_push($sortArrForDueContents, $contentObj['startUtc']);
                        }
                    }
                }
                
                $isFolder = FALSE;
                $userGroups = $depMgmtObj->getAllGroupsFoUser(NULL);
                foreach($userGroups as $grp) 
                {
                    $groupContents = $depMgmtObj->getAllDueContents($minTs, $maxTs, $isFolder, $grp->group_id);
                    if(isset($groupContents))
                    {
                        foreach($groupContents as $content) {
                            $contentObj = $contentListFormulationObj->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                            if(isset($contentObj)) {
                                $contentObj['isFolder'] = 0;
                                $contentObj['fOrGId'] = sracEncryptNumberData($grp->group_id, $userSession);
                                array_push($allDueContents, $contentObj);
                                array_push($sortArrForDueContents, $contentObj['startUtc']);
                            }
                        }
                    }
                }
                
                $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
                foreach ($userOrganizations as $userOrg) 
                {                   
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withUserIdOrgIdAndEmpId($user, $userOrg->organization_id, $userOrg->emp_id);
                
                    $isFolder = TRUE;
                    $folderContents = $depMgmtObj->getAllDueContents($minTs, $maxTs, $isFolder, NULL);
                    if(isset($folderContents))
                    {
                        foreach($folderContents as $content) {
                            $contentObj = $contentListFormulationObj->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                            if(isset($contentObj)) {
                                $contentObj['isFolder'] = 1;
                                $contentObj['fOrGId'] = sracEncryptNumberData($content->folderId, $userSession);
                                array_push($allDueContents, $contentObj);
                                array_push($sortArrForDueContents, $contentObj['startUtc']);
                            }
                        }
                    }
                    
                    $isFolder = FALSE;
                    $userGroups = $depMgmtObj->getAllGroupsFoUser(NULL);
                    foreach($userGroups as $grp) 
                    {
                        $groupContents = $depMgmtObj->getAllDueContents($minTs, $maxTs, $isFolder, $grp->group_id);
                        if(isset($groupContents))
                        {
                            foreach($groupContents as $content) {
                                $contentObj = $contentListFormulationObj->formulateContentObject($depMgmtObj, $isFolder, $content, $searchStr, $hasFilters, $filShowAttachment, $filAttachmentExtArr);
                                if(isset($contentObj)) {
                                    $contentObj['isFolder'] = 0;
                                    $contentObj['fOrGId'] = sracEncryptNumberData($grp->group_id, $userSession);
                                    array_push($allDueContents, $contentObj);
                                    array_push($sortArrForDueContents, $contentObj['startUtc']);
                                }
                            }
                        }
                    }
                }
                
                array_multisort($sortArrForDueContents, SORT_ASC, $allDueContents);
                               
                $response['contentArr'] = $allDueContents;                 
                $response['contentCnt'] = count($allDueContents);                  
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
    
    public function getDueAppuserContentsNew() 
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $listCode = Input::get('listCode');
        $tzOfs = Input::get('tzOfs');
        $calMinTs = Input::get('calMinTs');
        $calMaxTs = Input::get('calMaxTs');
        
        $response = array();
        if($encUserId != "" && $listCode != "" && $tzOfs != "")
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
                
                $searchStr = '';
                $hasFilters = 0;
                $filAttachmentExtArr = array();
                $filShowAttachment = -1;

                $offsetIsNegative = $tzOfs >= 0 ? 0 : 1; 

                $minDateObj = Carbon::now();
                $maxDateObj = Carbon::now();

                $consTzOffsetStr = "";

                if($offsetIsNegative == 1)
                {   
                    $minDateObj = $minDateObj->subMinutes($tzOfs * -1);  
                    $maxDateObj = $maxDateObj->subMinutes($tzOfs * -1);      

                    $consTzOffsetStr = "+" . ($tzOfs * -1);
                }
                else
                {   
                    $minDateObj = $minDateObj->addMinutes($tzOfs * 1); 
                    $maxDateObj = $maxDateObj->addMinutes($tzOfs * 1);   

                    $consTzOffsetStr = "-" . ($tzOfs * 1);           
                }

                $consTimeZoneObj = "";//new DateTimeZone($consTzOffsetStr);

                if($listCode == "DLY")
                {
                    $minDateObj = $minDateObj->startOfDay();
                    $maxDateObj = $maxDateObj->endOfDay();
                }
                else if($listCode == "WKLY")
                {
                    $minDateObj = $minDateObj->startOfWeek()->startOfDay();
                    $maxDateObj = $maxDateObj->endOfWeek()->endOfDay();
                }
                else if($listCode == "MTHLY")
                {
                    $minDateObj = $minDateObj->startOfMonth()->startOfDay();
                    $maxDateObj = $maxDateObj->endOfMonth()->endOfDay();
                }
                else if($listCode == "YRLY")
                {
                    $minDateObj = $minDateObj->startOfYear()->startOfDay();
                    $maxDateObj = $maxDateObj->endOfYear()->endOfDay();
                }
                else if($listCode == "N7D")
                {
                    $minDateObj = $minDateObj->startOfDay();
                    $maxDateObj = $maxDateObj->addDays(7)->endOfDay();
                }
                else if($listCode == "N30D")
                {
                    $minDateObj = $minDateObj->startOfDay();
                    $maxDateObj = $maxDateObj->addDays(30)->endOfDay();
                }
                else if($listCode == "CAL")
                {
                    if(isset($calMinTs) && $calMinTs != "")
                    {
                        $minDateObj = Carbon::createFromTimeStampUTC($calMinTs / 1000);
                    }

                    if(isset($calMaxTs) && $calMaxTs != "")
                    {
                        $maxDateObj = Carbon::createFromTimeStampUTC($calMaxTs / 1000);
                    }
                }
            
                $minDateDbStr = $minDateObj->format('Y-m-d');
                $maxDateDbStr = $maxDateObj->format('Y-m-d');

                $minTimeStamp = $minDateObj->timestamp;                   
                $minTs = $minTimeStamp * 1000;
        
                $maxTimeStamp = $maxDateObj->timestamp;                   
                $maxTs = $maxTimeStamp * 1000;

                $response['minDateDbStr'] = $minDateDbStr;
                $response['maxDateDbStr'] = $maxDateDbStr;

                $dailyDateArr = array();
                $weeklyDateArr = array();
                $monthlyDateArr = array();
                $yearlyDateArr = array();

                $currentDayOfWeekArrForQuery = array();
                $currentDayOfMonthArrForQuery = array();
                $currentMonthOfYearArrForQuery = array();

                $minMaxDatePeriod = CarbonPeriod::create($minDateDbStr, $maxDateDbStr);
                $consDateArr = $minMaxDatePeriod->toArray();

                foreach ($consDateArr as $consDateKey => $consDateObj) {

                    $consDateTimeStamp = $consDateObj->timestamp;                   
                    $consDateTs = $consDateTimeStamp * 1000;

                    array_push($dailyDateArr, $consDateTs);

                    $consDateDayOfWeek = $consDateObj->dayOfWeek;
                    $consDateDayOfWeekStr = $consDateDayOfWeek . "";

                    $consDateDayOfMonthStr = $consDateObj->format('d');

                    $consDateDayOfMonthAndYearStr = $consDateObj->format('dm');

                    if(!isset($weeklyDateArr[$consDateDayOfWeekStr]))
                    {
                        $weeklyDateArr[$consDateDayOfWeekStr] = array();
                    }
                    array_push($weeklyDateArr[$consDateDayOfWeekStr], $consDateTs);

                    if(!in_array($consDateDayOfWeekStr, $currentDayOfWeekArrForQuery))
                    {
                        array_push($currentDayOfWeekArrForQuery, $consDateDayOfWeekStr);
                    }

                    if(!isset($monthlyDateArr[$consDateDayOfMonthStr]))
                    {
                        $monthlyDateArr[$consDateDayOfMonthStr] = array();
                    }
                    array_push($monthlyDateArr[$consDateDayOfMonthStr], $consDateTs);

                    if(!in_array($consDateDayOfMonthStr, $currentDayOfMonthArrForQuery))
                    {
                        array_push($currentDayOfMonthArrForQuery, $consDateDayOfMonthStr);
                    }

                    if(!isset($yearlyDateArr[$consDateDayOfMonthAndYearStr]))
                    {
                        $yearlyDateArr[$consDateDayOfMonthAndYearStr] = array();
                    }
                    array_push($yearlyDateArr[$consDateDayOfMonthAndYearStr], $consDateTs);

                    if(!in_array($consDateDayOfMonthAndYearStr, $currentMonthOfYearArrForQuery))
                    {
                        array_push($currentMonthOfYearArrForQuery, $consDateDayOfMonthAndYearStr);
                    }
                }

                $status = 1;
                $allDueContents = array();
                $sortArrForDueContents = array();

                $repeatTypeDaily = 'DAILY';
                $repeatTypeWeekly = 'WEEKLY';
                $repeatTypeMonthly = 'MONTHLY';
                $repeatTypeYearly = 'YEARLY';

                $consQueryDatePatternStrForDaily = NULL;
                $consQueryDatePatternStrForWeekly = implode(",", $currentDayOfWeekArrForQuery);
                $consQueryDatePatternStrForMonthly = implode(",", $currentDayOfMonthArrForQuery);
                $consQueryDatePatternStrForYearly = implode(",", $currentMonthOfYearArrForQuery);

                $consQueryDatePatternStrArr = array();
                $consQueryDatePatternStrArr[$repeatTypeDaily] = $consQueryDatePatternStrForDaily;
                $consQueryDatePatternStrArr[$repeatTypeWeekly] = $consQueryDatePatternStrForWeekly;
                $consQueryDatePatternStrArr[$repeatTypeMonthly] = $consQueryDatePatternStrForMonthly;
                $consQueryDatePatternStrArr[$repeatTypeYearly] = $consQueryDatePatternStrForYearly;

                $consQueryDateArr = array();
                $consQueryDateArr[$repeatTypeDaily] = $dailyDateArr;
                $consQueryDateArr[$repeatTypeWeekly] = $weeklyDateArr;
                $consQueryDateArr[$repeatTypeMonthly] = $monthlyDateArr;
                $consQueryDateArr[$repeatTypeYearly] = $yearlyDateArr;

                $contentListFormulationObj = New ContentListFormulationClass;
                $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");

                $retContents = $depMgmtObj->getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $consQueryDateArr, $consQueryDatePatternStrArr);
                $allDueContents = array_merge($allDueContents , $retContents['formulatedContentArr']);
                $sortArrForDueContents = array_merge($sortArrForDueContents, $retContents['sortArrForContents']);
                
                
                $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
                foreach ($userOrganizations as $userOrg) 
                {                   
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withUserIdOrgIdAndEmpId($user, $userOrg->organization_id, $userOrg->emp_id);
                
                    $orgContents = $depMgmtObj->getAllFormulatedDueContentsForRangeAndRepeatDailyWeeklyMonthlyYearly($contentListFormulationObj, $consTimeZoneObj, $minTs, $maxTs, $consQueryDateArr, $consQueryDatePatternStrArr);
                    $allDueContents = array_merge($allDueContents , $orgContents['formulatedContentArr']);
                    $sortArrForDueContents = array_merge($sortArrForDueContents, $orgContents['sortArrForContents']);
                }
                
                array_multisort($sortArrForDueContents, SORT_ASC, $allDueContents);
                               
                $response['contentArr'] = $allDueContents;                 
                $response['contentCnt'] = count($allDueContents);                  
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
    
    public function getDueAppuserDashboardMetrics() 
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');
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
                $typeRFil = [ Config::get("app_config.content_type_r") ];
                $typeCFil = [ Config::get("app_config.content_type_c") ];
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $orgId = $depMgmtObj->getOrganizationId(); 

                $lockedFolderArr = array();
                if(isset($isLockedFlag) && $isLockedFlag == 1) {
                    $userConstants = $depMgmtObj->getEmployeeOrUserConstantObject();
                    
                    $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
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
                }

                $lockedAndSentFolderArr = $lockedFolderArr;
                $sentFolderId = $depMgmtObj->getSentFolderId();
                if(isset($sentFolderId) && $sentFolderId > 0)
                {
                    array_push($lockedAndSentFolderArr, $sentFolderId);
                }

                // Log::info('lockedFolderArr : ');
                // Log::info($lockedFolderArr);
                
                $favContentCnt = 0;
                $favProfileFolderGroupContentCnt = 0;
                $favFolderGroupContentCnt = 0;
                $favFolderGroupCnt = 0;
                $allContentCnt = 0;
                $reminderContentCnt = 0;
                $calendarContentCnt = 0;
                $conversationContentCnt = 0;  
                $trashContentCnt = 0;         
                
                $foldersModelObj = $depMgmtObj->getAllFoldersModelObj();
                $perFavFolders = $foldersModelObj->isFavorited()->filterExceptFolder($lockedFolderArr);
                $perFavFolders = $perFavFolders->get();
                $perFavFolderIdArr = array();
                foreach ($perFavFolders as $favFolder) {
                    if($orgId > 0)                  
                    {
                        $folderId = $favFolder->employee_folder_id;
                    }
                    else
                    {
                        $folderId = $favFolder->appuser_folder_id;
                    }  
                    array_push($perFavFolderIdArr, $folderId);
                }

                $allOrgDepMgmtObjArr = array();
                
                $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();
                $lastUserOrgIndex = count($userOrganizations) > 0 ? count($userOrganizations) - 1 : 0;

                $user = $depMgmtObj->getUserObject();

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

                foreach ($allOrgDepMgmtObjArr as $depMgmtIndex => $orgDepMgmtObj)
                {
                    $depMgmtObjOrgId = $orgDepMgmtObj->getOrganizationId(); 

                    $orgLockedFolderIdArr = array();
                    if(isset($isLockedFlag) && $isLockedFlag == 1) {
                        $orgUserEmpConstants = $orgDepMgmtObj->getEmployeeOrUserConstantObject();
                        
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

                    $orgFoldersModelObj = $orgDepMgmtObj->getAllFoldersModelObj();
                    $orgFoldersModelObj = $orgFoldersModelObj->isFavorited()->filterExceptFolder($orgLockedFolderIdArr);
                    $orgFavFolders = $orgFoldersModelObj->get();
                    $orgFavFolderIdArr = array();
                    foreach ($orgFavFolders as $favFolder) 
                    {
                        if($depMgmtObjOrgId > 0)                  
                        {
                            $favFolderId = $favFolder->employee_folder_id;
                        }
                        else
                        {
                            $favFolderId = $favFolder->appuser_folder_id;
                        }  
                        array_push($orgFavFolderIdArr, $favFolderId);
                    }

                    $forFavFolderContent = $orgDepMgmtObj->getAllContentModelObj(TRUE)->filterExceptRemoved();
                    $forFavFolderContent = $forFavFolderContent->filterFolder($orgFavFolderIdArr);
                    $favFolderGroupContentCnt += $forFavFolderContent->count();

                    // Log::info('-------------- depMgmtObjOrgId : ' . $depMgmtObjOrgId . '  --------------');

                    // Log::info('forFavFolderContent : ' . $forFavFolderContent->count());

                    $orgFavFolderCnt = $orgFoldersModelObj->count();                    
                    $favFolderGroupCnt += $orgFavFolderCnt;

                    $orgGroupsModelObj = $orgDepMgmtObj->getAllGroupsForUserModelObj();
                    $orgGroupsModelObj = $orgGroupsModelObj->isFavorited();
                    $orgFavGroups = $orgGroupsModelObj->get();
                    $orgFavGroupIdArr = array();
                    foreach ($orgFavGroups as $favGroup) {
                        array_push($orgFavGroupIdArr, $favGroup->group_id);
                    }

                    $forFavGroupContent = $orgDepMgmtObj->getAllContentModelObj(FALSE);
                    $forFavGroupContent = $forFavGroupContent->filterGroup($orgFavGroupIdArr);
                    $favFolderGroupContentCnt += $forFavGroupContent->count();

                    // Log::info('forFavGroupContent : ' . $forFavGroupContent->count());

                    $orgFavGroupCnt = $orgGroupsModelObj->count();                    
                    $favFolderGroupCnt += $orgFavGroupCnt;
                }

                // Log::info('-------------- For Folder --------------');

                $isFolder = TRUE;
                $folderContentsModelObj = $depMgmtObj->getAllContentModelObj($isFolder);

                if(isset($folderContentsModelObj))
                {
                    $forFavContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forFavContent = $forFavContent->filterIsMarked();
                    //if(count($lockedFolderArr) > 0)
                    {
                        $forFavContent = $forFavContent->filterExceptFolder($lockedFolderArr);//($lockedAndSentFolderArr);
                    }
                    $favContentCnt += $forFavContent->count();

                    // Log::info('forFavContentCnt : ' . $forFavContent->count());

                    $forAllContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    //if(count($lockedFolderArr) > 0) 
                    {
                        $forAllContent = $forAllContent->filterExceptFolder($lockedFolderArr);//($lockedAndSentFolderArr);
                    }
                    $allContentCnt += $forAllContent->count();

                    // Log::info('forAllContent : ' . $forAllContent->count());

                    $forReminderContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forReminderContent = $forReminderContent->filterType($typeRFil);
                    //if(count($lockedFolderArr) > 0) 
                    {
                        $forReminderContent = $forReminderContent->filterExceptFolder($lockedFolderArr);//($lockedAndSentFolderArr);
                    }
                    $reminderContentCnt += $forReminderContent->count();

                    // Log::info('forReminderContent : ' . $forReminderContent->count());

                    $forCalendarContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forCalendarContent = $forCalendarContent->filterType($typeCFil);
                    //if(count($lockedFolderArr) > 0) 
                    {
                        $forCalendarContent = $forCalendarContent->filterExceptFolder($lockedFolderArr);//($lockedAndSentFolderArr);
                    }
                    $calendarContentCnt += $forCalendarContent->count();

                    // Log::info('forCalendarContent : ' . $forCalendarContent->count());

                    $forConversationContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forConversationContent = $forConversationContent->filterIsConversation();
                    //if(count($lockedFolderArr) > 0) 
                    {
                        $forConversationContent = $forConversationContent->filterExceptFolder($lockedAndSentFolderArr);
                    }
                    $conversationContentCnt += $forConversationContent->count();

                    // Log::info('forConversationContent : ' . $forConversationContent->count());

                    $forSentContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forSentContent = $forSentContent->ofFolder($sentFolderId);
                    $conversationContentCnt += $forSentContent->count();

                    // Log::info('forSentContent : ' . $forSentContent->count());

                    $forTrashContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterIsRemoved();
                    $trashContentCnt += $forTrashContent->count();

                    $prfFoldersModelObj = $depMgmtObj->getAllFoldersModelObj();
                    $prfFoldersModelObj = $prfFoldersModelObj->isFavorited()->filterExceptFolder($lockedFolderArr);
                    $prfFavFolders = $prfFoldersModelObj->get();
                    $profileFavFolderIdArr = array();
                    foreach ($prfFavFolders as $favFolder) 
                    {
                        if($depMgmtObjOrgId > 0)                  
                        {
                            $favFolderId = $favFolder->employee_folder_id;
                        }
                        else
                        {
                            $favFolderId = $favFolder->appuser_folder_id;
                        }  
                        array_push($profileFavFolderIdArr, $favFolderId);
                    }

                    $forProfileFavFolderContent = $depMgmtObj->getAllContentModelObj($isFolder)->filterExceptRemoved();
                    $forProfileFavFolderContent = $forProfileFavFolderContent->filterFolder($profileFavFolderIdArr);
                    $favProfileFolderGroupContentCnt += $forProfileFavFolderContent->count();
                }
                
                $groupsModelObj = $depMgmtObj->getAllGroupsForUserModelObj();
                $forFavGroup = clone $groupsModelObj;
                $perFavGroups = $forFavGroup->isFavorited()->get();
                $perFavGroupIdArr = array();
                foreach ($perFavGroups as $favGroup) {
                    array_push($perFavGroupIdArr, $favGroup->group_id);
                }

                // Log::info('-------------- For Group --------------');
                
                $isFolder = FALSE;
                $groupContentsModelObj = $depMgmtObj->getAllContentModelObj($isFolder);
                if(isset($groupContentsModelObj))
                {
                    $forFavContent = $depMgmtObj->getAllContentModelObj($isFolder);
                    $forFavContent = $forFavContent->filterIsMarked();
                    $favContentCnt += $forFavContent->count();

                    // Log::info('forFavContentCnt : ' . $forFavContent->count());

                    $forAllContent = $depMgmtObj->getAllContentModelObj($isFolder);
                    $allContentCnt += $forAllContent->count();

                    // Log::info('forAllContent : ' . $forAllContent->count());

                    $forReminderContent = $depMgmtObj->getAllContentModelObj($isFolder);
                    $forReminderContent = $forReminderContent->filterType($typeRFil);
                    $reminderContentCnt += $forReminderContent->count();

                    // Log::info('forReminderContent : ' . $forReminderContent->count());

                    $forCalendarContent = $depMgmtObj->getAllContentModelObj($isFolder);
                    $forCalendarContent = $forCalendarContent->filterType($typeCFil);
                    $calendarContentCnt += $forCalendarContent->count();

                    // Log::info('forCalendarContent : ' . $forCalendarContent->count());

                    $forConversationContent = $depMgmtObj->getAllContentModelObj($isFolder);
                    //$forConversationContent = $forConversationContent->filterIsConversation();
                    $conversationContentCnt += $forConversationContent->count();  

                    // Log::info('forConversationContent : ' . $forConversationContent->count());

                    $prfGroupsModelObj = $depMgmtObj->getAllGroupsForUserModelObj();
                    $prfGroupsModelObj = $prfGroupsModelObj->isFavorited();
                    $prfFavGroups = $prfGroupsModelObj->get();
                    $profileFavGroupIdArr = array();
                    foreach ($prfFavGroups as $favGroup) {
                        array_push($profileFavGroupIdArr, $favGroup->group_id);
                    }

                    $forProfileFavGroupContent = $depMgmtObj->getAllContentModelObj(FALSE);
                    $forProfileFavGroupContent = $forProfileFavGroupContent->filterGroup($profileFavGroupIdArr);
                    $favProfileFolderGroupContentCnt += $forProfileFavGroupContent->count();                 
                }

                $response['favContentCnt'] = $favContentCnt;  
                $response['favFolderGroupContentCnt'] = $favFolderGroupContentCnt;
                $response['favProfileFolderGroupContentCnt'] = $favProfileFolderGroupContentCnt;
                $response['favFolderCnt'] = $favFolderGroupCnt;
                $response['allContentCnt'] = $allContentCnt;
                $response['reminderContentCnt'] = $reminderContentCnt;
                $response['calendarContentCnt'] = $calendarContentCnt;
                $response['conversationContentCnt'] = $conversationContentCnt;
                $response['trashContentCnt'] = $trashContentCnt;
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
    
    public function appuserContentDependencyModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $depCode = Input::get('depCode');
        $selectedId = Input::get('selectedId');
        $fieldId = Input::get('fieldId');
        $displayFieldId = Input::get('displayFieldId');
        $callbackName = Input::get('callbackName');
        $enableDataToggle = Input::get('enableDataToggle');
        
        $hasDone = Input::get('hasDone');
        $isMultiSelect = Input::get('isMultiSelect');
        $isMandatory = Input::get('isMandatory');
        $hasCancel = Input::get('hasCancel');
        $isIntVal = Input::get('isIntVal');
        
        $hasDisplayField = FALSE;
        if(isset($displayFieldId) && $displayFieldId != "")
        {
            $hasDisplayField = TRUE;
        }
        else
        {
            $displayFieldId = "";
        }
        
        $hasCallback = FALSE;
        if(isset($callbackName) && $callbackName != "")
        {
            $hasCallback = TRUE;
        }
        else
        {
            $callbackName = "";
        }
        
        $response = array();
        if($encUserId != "" && $depCode != "")
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
                
                $selectedIdArr = array();
                if(isset($selectedId))
                {
                    if($isMultiSelect == 1) 
                    {
                        $selectedIdArr = jsonDecodeArrStringIfRequired($selectedId);
                    }
                    else
                    {
                        array_push($selectedIdArr, $selectedId);
                    }
                }
                
                if(!isset($selectedIdArr) && !is_array($selectedIdArr))
                {
                    $selectedIdArr = array();
                }
                
                $compSelectedIdArr = array();
                $selectedTextArr = array();
                $existingDepIdArr = array();
                $tempLogArr = array();

                $showSearchBar = FALSE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                $depDataArr = array();
                $depText = "";
                
                if($depCode == "TAG")
                {
                    $showSearchBar = TRUE;
                    $depText = "Tag";
                    
                    $tags = $depMgmtObj->getAllTags();
                    foreach($tags as $tag)
                    {
                        $depData = array();
                        if($encOrgId != "")
                            $depId = ($tag->employee_tag_id);
                        else
                            $depId = ($tag->appuser_tag_id);

                        $depData['id'] = sracEncryptNumberData($depId, $userSession);
                        $depData['text'] = $tag->tag_name;

                        array_push($existingDepIdArr, $depId);
                        
                        array_push($depDataArr, $depData);
                    }
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId, $userSession);
                            // $selDepDataObj = $depMgmtObj->getTagObject($selId);
                            // if(isset($selDepDataObj))
                            // {
                            //     $selectedTextArr[sracEncryptNumberData($selId, $userSession)] = $selDepDataObj->tag_name;
                            // }

                            if(in_array($selId, $existingDepIdArr))
                            {
                                $selPosition = array_search($selId, $existingDepIdArr);

                                $depData = $depDataArr[$selPosition];

                                array_push($compSelectedIdArr, $depData['id']);
                                $selectedTextArr[$depData['id']] = $depData['text'];
                            }
                        }
                    }
                }
                else if($depCode == "FOLDER")
                {
                    $showSearchBar = TRUE;
                    $depText = "Folder";
                    
                    if($enableDataToggle == 1)
                    {
                        $depData = array();
                        $depData['id'] = sracEncryptNumberData(-1, $userSession);
                        $depData['text'] = 'Switch To Group';
                        
                        array_push($existingDepIdArr, ""); 

                        array_push($depDataArr, $depData);
                    }                       
                    
                    $folders = $depMgmtObj->getAllFolders();
                    foreach($folders as $folder)
                    {
                        if($folder->folder_type_id == 0)
                        {
                            $depData = array();
                            if($encOrgId != "")
                                $depId = ($folder->employee_folder_id);
                            else
                                $depId = ($folder->appuser_folder_id);

                            $depData['id'] = sracEncryptNumberData($depId, $userSession);
                            $depData['text'] = $folder->folder_name;

                            array_push($existingDepIdArr, $depId);
                            
                            array_push($depDataArr, $depData);
                        }
                    }
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId, $userSession);
                            // $selDepDataObj = $depMgmtObj->getFolderObject($selId);
                            // if(isset($selDepDataObj))
                            // {
                            //     $selectedTextArr[sracEncryptNumberData($selId, $userSession)] = $selDepDataObj->folder_name;
                            // }

                            if(in_array($selId, $existingDepIdArr))
                            {
                                $selPosition = array_search($selId, $existingDepIdArr);

                                $depData = $depDataArr[$selPosition];

                                array_push($compSelectedIdArr, $depData['id']);
                                $selectedTextArr[$depData['id']] = $depData['text'];
                            }
                        }
                    }
                }   
                else if($depCode == "GROUP")
                {
                    $showSearchBar = TRUE;
                    $currOrgId = $depMgmtObj->getOrganizationId();
                    
                    $depText = "Group";
                    
                    if($enableDataToggle == 1)
                    {
                        $depData = array();
                        $depData['id'] = sracEncryptNumberData(-1, $userSession);
                        $depData['text'] = 'Switch To Folder';
                        
                        array_push($existingDepIdArr, ""); 

                        array_push($depDataArr, $depData);
                    }
                    
                    $groups = $depMgmtObj->getAllGroupsFoUser();
                    foreach($groups as $group) 
                    {
                        $isAdmin = $group->is_admin;
                        $hasPostRight = $group->has_post_right;
                        if($currOrgId == 0)
                        {
                            $hasPostRight = 1;
                        }
                        if($isAdmin == 1 || $hasPostRight == 1)
                        {
                            $depData = array();
                            $depId = ($group->group_id);

                            $depData['id'] = sracEncryptNumberData($depId, $userSession);
                            $depData['text'] = $group->name;

                            array_push($existingDepIdArr, $depId); 

                            array_push($depDataArr, $depData);                         
                        }
                    }
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId, $userSession);
                            // $selDepDataObj = $depMgmtObj->getGroupObject($selId);
                            // if(isset($selDepDataObj))
                            // {
                            //     $selectedTextArr[sracEncryptNumberData($selId, $userSession)] = $selDepDataObj->name;
                            // }

                            if(in_array($selId, $existingDepIdArr))
                            {
                                $selPosition = array_search($selId, $existingDepIdArr);

                                $depData = $depDataArr[$selPosition];

                                array_push($compSelectedIdArr, $depData['id']);
                                $selectedTextArr[$depData['id']] = $depData['text'];
                            }
                        }
                    }
                }                   
                else if($depCode == "SOURCE")
                {
                    $showSearchBar = TRUE;
                    $depText = "Source";

                    // $decSelectedIdArr = sracDecryptNumberArrayData($selectedIdArr, $userSession);
                    
                    $sources = $depMgmtObj->getAllSources();

                    foreach($sources as $source)
                    {
                        $depData = array();
                        if($encOrgId != "")
                            $depId = ($source->employee_source_id);
                        else
                            $depId = ($source->appuser_source_id);

                        $depData['id'] = sracEncryptNumberData($depId, $userSession);
                        $depData['text'] = $source->source_name;

                        array_push($existingDepIdArr, $depId);
                        
                        array_push($depDataArr, $depData);
                    }
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId, $userSession);
                            // $selDepDataObj = $depMgmtObj->getSourceObject($selId);
                            // if(isset($selDepDataObj))
                            // {
                            //     $selectedTextArr[sracEncryptNumberData($selId, $userSession)] = $selDepDataObj->source_name;
                            // }

                            if(in_array($selId, $existingDepIdArr))
                            {
                                $selPosition = array_search($selId, $existingDepIdArr);

                                $depData = $depDataArr[$selPosition];

                                array_push($compSelectedIdArr, $depData['id']);
                                $selectedTextArr[$depData['id']] = $depData['text'];
                            }
                        }
                    }
                }               
                else if($depCode == "PROFILE")
                {
                    $depText = "Profile";
                    
                    $depData = array();
                    $depData['id'] = "";
                    $depData['text'] = "Personal";
                    array_push($depDataArr, $depData);
                    
                    $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
                    foreach($userOrganizations as $userOrg)
                    {
                        if(isset($userOrg->organization) && $userOrg->organization->is_active == 1)
                        {
                            $tempOrgId = $userOrg->organization_id;
                            $tempEmpId = $userOrg->emp_id;
    
                            $depMgmtObj = New ContentDependencyManagementClass;
                            $depMgmtObj->withOrgIdAndEmpId($tempOrgId, $tempEmpId);
                            $isEmpActive = $depMgmtObj->getEmployeeIsActive();

                            if($isEmpActive == 1)
                            {
                                $orgEmployeeName = $depMgmtObj->getEmployeeOrUserName();
                                // $tempOrgDepMgmtObj = New ContentDependencyManagementClass;
                                // $tempOrgDepMgmtObj->withOrgIdAndEmpId($tempOrgId, $tempEmpId);
                                // $defaultFolderId = $tempOrgDepMgmtObj->getDefaultFolderId();
                                // $defaultFolderObj = $tempOrgDepMgmtObj->getFolderObject($defaultFolderId);
                                // $defaultFolderName = $defaultFolderObj->folder_name;

                                $encOrgId = Crypt::encrypt($tempOrgId."_".$tempEmpId);
                                $depData = array();
                                $depData['id'] = $encOrgId;
                                $depData['text'] = $userOrg->organization->system_name." [".$orgEmployeeName."]";
                                // $depData['defFolderId'] = $defaultFolderId;
                                // $depData['defFolderText'] = $defaultFolderName;
                                array_push($depDataArr, $depData);
                            } 
                        }
                    }
                    
                    if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId);
                            $selDepDataObj = $depMgmtObj->getOrganizationObject($selId);
                            if(isset($selDepDataObj))
                            {
                                $selectedTextArr[$selId] = $selDepDataObj->source_name;
                            }
                        }
                    }
                }               
                else if($depCode == "REMIND")
                {
                    $depText = "Remind Before";
                    $depDataArr = $depMgmtObj->getAllRemindBeforeOptions();
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = ($encSelId);
                            $selDepDataObj = $depMgmtObj->getRemindBeforeText($selId);
                            if(isset($selDepDataObj))
                            {
                                array_push($compSelectedIdArr, $selId);
                                $selectedTextArr[($selId)] = $selDepDataObj;
                            }
                        }
                    }
                }               
                else if($depCode == "REPEAT")
                {
                    $depText = "Repeat Duration";
                    $depDataArr = $depMgmtObj->getAllRepeatDurationOptions();
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = ($encSelId);
                            $selDepDataObj = $depMgmtObj->getRepeatDurationText($selId);
                            if(isset($selDepDataObj))
                            {
                                array_push($compSelectedIdArr, $selId);
                                $selectedTextArr[($selId)] = $selDepDataObj;
                            }
                        }
                    }
                }           
                else if($depCode == "TYPE")
                {
                    $depText = "Content Type";
                    $contentTypes = $depMgmtObj->getAllContentTypes();
                    
                    foreach($contentTypes as $contentType)
                    {
                        $depData = array();
                        $depData['id'] = ($contentType->content_type_id);
                        $depData['text'] = $contentType->type_name;
                        
                        array_push($depDataArr, $depData);
                    }
                    
                    // if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = ($encSelId);
                            $selDepDataObj = $depMgmtObj->getContentTypeObject($selId);
                            if(isset($selDepDataObj))
                            {
                                array_push($compSelectedIdArr, $selId);
                                $selectedTextArr[($selId)] = $selDepDataObj->type_name;
                            }
                        }
                    }
                }           
                else if($depCode == "TEMPLATE")
                {
                    $depText = "Template";
                    $templates = $depMgmtObj->getAllTemplates();
                    
                    if(isset($templates) && count($templates) > 0)
                    {
                        foreach($templates as $template)
                        {
                            $depId = $template->template_id;

                            $depData = array();
                            $depData['id'] = sracEncryptNumberData($depId, $userSession);
                            $depData['text'] = $template->template_name;

                            array_push($existingDepIdArr, $depId);
                            
                            array_push($depDataArr, $depData);
                        }
                    }
                    
                    if($hasDisplayField)
                    {
                        foreach($selectedIdArr as $encSelId)
                        {
                            $selId = sracDecryptNumberData($encSelId, $userSession);
                            // $selDepDataObj = $depMgmtObj->getTemplateObject($selId);
                            // if(isset($selDepDataObj))
                            // {
                                // $selectedTextArr[sracEncryptNumberData($selId, $userSession)] = $selDepDataObj->template_name;
                            // }
                            if(in_array($selId, $existingDepIdArr))
                            {
                                $selPosition = array_search($selId, $existingDepIdArr);
                                $depData = $depDataArr[$selPosition];

                                $selectedTextArr[$depData['id']] = $depData['text'];
                            }
                        }
                    }
                }
                
                $modalTitle = "Select ".$depText;
                
                if($isMultiSelect == 1) {
                    $modalTitle .= "(s)";
                }

                $viewDetails = array();
                $viewDetails['modalTitle'] = $modalTitle;
                $viewDetails['depDataArr'] = $depDataArr;
                $viewDetails['selectedIdArr'] = $compSelectedIdArr;
                // $viewDetails['selectedIdArr'] = $selectedIdArr;
                $viewDetails['selectedTextArr'] = $selectedTextArr;
                $viewDetails['fieldId'] = $fieldId;
                $viewDetails['hasDisplayField'] = $hasDisplayField;
                $viewDetails['displayFieldId'] = $displayFieldId;
                $viewDetails['hasCallback'] = $hasCallback;
                $viewDetails['callbackName'] = $callbackName;
                $viewDetails['hasDone'] = $hasDone;
                $viewDetails['isMultiSelect'] = $isMultiSelect;
                $viewDetails['hasCancel'] = $hasCancel;
                $viewDetails['isMandatory'] = $isMandatory;
                $viewDetails['isIntVal'] = $isIntVal;
                $viewDetails['showSearchBar'] = $showSearchBar;
                
                $_viewToRender = View::make('content.partialview._contentDependencyModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();
                
                $response['viewDetails'] = $viewDetails;   
                $response['view'] = $_viewToRender;  
                $response['tempLogArr'] = $tempLogArr;
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
    
    public function appuserContentModifyDateTimeModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $contentTypeId = Input::get('contentTypeId');
        $fromTs = Input::get('fromTs');
        $toTs = Input::get('toTs');
        $contentId = (Input::get('contentId'));
        $isFolderFlag = Input::get('isFolder');
        $isConversationFlag = Input::get('isConversation');

        $isFolderFlag = $isFolderFlag * 1;
        $isConversationFlag = $isConversationFlag * 1;

        $isFolder = FALSE;
        if($isFolderFlag == 1)
        {
            $isFolder = TRUE;
        }

        $isConversation = FALSE;
        if($isConversationFlag == 1)
        {
            $isConversation = TRUE;
        }
        
        $response = array();
        if($encUserId != "" && $contentTypeId != "" && $contentTypeId > 0 && $contentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                
                $modalTitle = "Modify Time";

                $viewDetails = array();
                $viewDetails['modalTitle'] = $modalTitle;
                $viewDetails['hasDone'] = TRUE;
                $viewDetails['hasCancel'] = TRUE;
                $viewDetails['contentTypeId'] = $contentTypeId;
                $viewDetails['fromTs'] = $fromTs;
                $viewDetails['toTs'] = $toTs;
                $viewDetails['contentId'] = sracEncryptNumberData($contentId, $userSession);
                $viewDetails['isFolder'] = $isFolder;
                $viewDetails['isConversation'] = $isConversation;
                $viewDetails['isFolderFlag'] = $isFolderFlag;
                $viewDetails['isConversationFlag'] = $isConversationFlag;
                
                $_viewToRender = View::make('content.partialview._contentModifyDateTimeModal', $viewDetails);
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
    
    public function performContentDateTimeModification()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $contentTypeId = Input::get('contentTypeId');
        $fromTs = Input::get('fromTs');
        $toTs = Input::get('toTs');
        $contentId = (Input::get('id'));
        $isFolderFlag = Input::get('isFolder');
        $isConversationFlag = Input::get('isConversation');

        $isFolderFlag = $isFolderFlag * 1;
        $isConversationFlag = $isConversationFlag * 1;

        $isFolder = FALSE;
        if($isFolderFlag == 1)
        {
            $isFolder = TRUE;
        }

        $isConversation = FALSE;
        if($isConversationFlag == 1)
        {
            $isConversation = TRUE;
        }

        $response = array();

        if($encUserId != "" && $contentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userContent = $depMgmtObj->getContentObject($contentId, $isFolder);   
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId();  

                $contentTypeIdR = Config::get('app_config.content_type_r');
                $contentTypeIdA = Config::get('app_config.content_type_a');
                $contentTypeIdC = Config::get('app_config.content_type_c');       
               
                if(isset($userContent) && $userContent->is_locked == 0 && $userContent->is_removed == 0)
                {
                    $contentTypeId = $userContent->content_type_id;
                    if($contentTypeId == $contentTypeIdR || $contentTypeId == $contentTypeIdC)
                    {
                        $status = 1;

                        $oldFromTs = $userContent->from_timestamp;
                        $oldToTs = $userContent->to_timestamp;

                        if($contentTypeId == $contentTypeIdR)
                        {
                            $toTs = 0;   
                        }

                        $contentRemindBeforeMillis = $userContent->remind_before_millis;

                        $reminderTimestamp = $fromTs;
                        if(isset($contentRemindBeforeMillis) && !is_nan($contentRemindBeforeMillis) && $contentRemindBeforeMillis > 0)
                        {
                            $reminderTimestamp = $reminderTimestamp - $contentRemindBeforeMillis;
                        }

                        $contentDetails = array();
                        $contentDetails['from_timestamp'] = $fromTs;
                        $contentDetails['to_timestamp'] = $toTs;
                        $contentDetails['reminder_timestamp'] = $reminderTimestamp;

                        $depMgmtObj->setPartialContentDetails($contentId, $isFolder, $contentDetails);

                        if($isConversation)
                        {
                            $timeFormatStr = "h:i A";
                            $istOffsetHours = 5;
                            $istOffsetMinutes = 30;

                            $fromTimestampOld = round($oldFromTs/1000);

                            $utcFromDtOld = Carbon::createFromTimeStampUTC($fromTimestampOld);
                            $utcFromDateStrOld = $utcFromDtOld->toFormattedDateString() . ' ' . $utcFromDtOld->format($timeFormatStr);
                            
                            $istFromDtOld = Carbon::createFromTimeStampUTC($fromTimestampOld);
                            $istFromDtOld = $istFromDtOld->addHours($istOffsetHours);
                            $istFromDtOld = $istFromDtOld->addMinutes($istOffsetMinutes);
                            $istFromDateStrOld = $istFromDtOld->toFormattedDateString() . ' ' . $istFromDtOld->format($timeFormatStr);

                            $oldTimeStrUTC = $utcFromDateStrOld;
                            $oldTimeStrIST = $istFromDateStrOld;

                            if($contentTypeId == $contentTypeIdC)
                            {
                                $toTimestampOld = round($oldToTs/1000);

                                $utcToDtOld = Carbon::createFromTimeStampUTC($toTimestampOld);
                                $utcToDateStrOld = $utcToDtOld->toFormattedDateString() . ' ' . $utcToDtOld->format($timeFormatStr);
                                
                                $istToDtOld = Carbon::createFromTimeStampUTC($toTimestampOld);
                                $istToDtOld = $istToDtOld->addHours($istOffsetHours);
                                $istToDtOld = $istToDtOld->addMinutes($istOffsetMinutes);
                                $istToDateStrOld = $istToDtOld->toFormattedDateString() . ' ' . $istToDtOld->format($timeFormatStr);

                                $oldTimeStrUTC .= " - ".$utcToDateStrOld;
                                $oldTimeStrIST .= " - ".$istToDateStrOld;
                            }

                            $oldTimeStrUTC .= " UTC";
                            $oldTimeStrIST .= " IST";
                                
                            $compOldTimeStr = $oldTimeStrUTC.' or '.$oldTimeStrIST;

                            $fromTimestampNew = round($fromTs/1000);

                            $utcFromDtNew = Carbon::createFromTimeStampUTC($fromTimestampNew);
                            $utcFromDateStrNew = $utcFromDtNew->toFormattedDateString() . ' ' . $utcFromDtNew->format($timeFormatStr);
                            
                            $istFromDtNew = Carbon::createFromTimeStampUTC($fromTimestampNew);
                            $istFromDtNew = $istFromDtNew->addHours($istOffsetHours);
                            $istFromDtNew = $istFromDtNew->addMinutes($istOffsetMinutes);
                            $istFromDateStrNew = $istFromDtNew->toFormattedDateString() . ' ' . $istFromDtNew->format($timeFormatStr);

                            $newTimeStrUTC = $utcFromDateStrNew;
                            $newTimeStrIST = $istFromDateStrNew;

                            if($contentTypeId == $contentTypeIdC)
                            {
                                $toTimestampNew = round($toTs/1000);

                                $utcToDtNew = Carbon::createFromTimeStampUTC($toTimestampNew);
                                $utcToDateStrNew = $utcToDtNew->toFormattedDateString() . ' ' . $utcToDtNew->format($timeFormatStr);
                                
                                $istToDtNew = Carbon::createFromTimeStampUTC($toTimestampNew);
                                $istToDtNew = $istToDtNew->addHours($istOffsetHours);
                                $istToDtNew = $istToDtNew->addMinutes($istOffsetMinutes);
                                $istToDateStrNew = $istToDtNew->toFormattedDateString() . ' ' . $istToDtNew->format($timeFormatStr);

                                $newTimeStrUTC .= " - ".$utcToDateStrNew;
                                $newTimeStrIST .= " - ".$istToDateStrNew;
                            }

                            $newTimeStrUTC .= " UTC";
                            $newTimeStrIST .= " IST";
                                
                            $compNewTimeStr = $newTimeStrUTC.' or '.$newTimeStrIST;



                            $createTimeStamp = CommonFunctionClass::getCreateTimestamp();

                            $isChangeLogOp = 1;
                            $changeLogText = "";
                            $isDeleteOp = 0;
                            $isReplyOp = 1;
                            $isEditOp = 0;
                            $conversationIndex = -1;
                            $updateTs = $createTimeStamp;
                            $editText = '';
                            $replyText = 'Time has been changed from '.$compOldTimeStr.' to '.$compNewTimeStr;

                            $updUserContent = $depMgmtObj->getContentObject($contentId, $isFolder);

                            $convOpParams = array();
                            $convOpParams['isChangeLogOp'] = $isChangeLogOp;
                            $convOpParams['changeLogText'] = $changeLogText;
                            $convOpParams['isDeleteOp'] = $isDeleteOp;
                            $convOpParams['isReplyOp'] = $isReplyOp;
                            $convOpParams['replyText'] = $replyText;
                            $convOpParams['isEditOp'] = $isEditOp;
                            $convOpParams['editText'] = $editText;
                            $convOpParams['conversationIndex'] = $conversationIndex;
                            $convOpParams['updateTs'] = $updateTs;

                            $response = $depMgmtObj->performContentConversationPartOperation($isFolder, $contentId, $updUserContent, $convOpParams);
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
    
    public function appuserContentDependencyGlobalSearchListView()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $depCode = Input::get('depCode');
        $searchStr = Input::get('searchStr');
        $appKey = Input::get('appKey');
        $isLockedFlag = Input::get('isLocked');
        $tzStr = Input::get('tzStr');
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
        }

        $response = array();
        if($encUserId != "" && $depCode != "")
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

                $orgId = $depMgmtObj->getOrganizationId();
                
                $_viewToRender = NULL;
                $depText = "";
                
                if($depCode == "FOLDER-GROUP")
                {
                    $depFolderOrGroupArr = array();
                    $depText = "Folder/Group";   

                    $folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));                
                    
                    $folders = $depMgmtObj->getAllFolders();
                    $lockedFolderArr = $depMgmtObj->getLockedFolderIdArr();
                    
                    foreach($folders as $folder)
                    {
                        $folderName = $folder->folder_name;
                        $folderIconCode = $folder->icon_code;
                        $folderTypeId = $folder->folder_type_id;
                        $isFavorited = $folder->is_favorited;

                        if($folderTypeId != 1)
                        {
                            if($searchStr == "" || ($searchStr != "" && strpos(strtolower($folderName), $searchStr) !== false))
                            {
                                if($encOrgId != "")
                                    $folderId = $folder->employee_folder_id;
                                else
                                    $folderId = $folder->appuser_folder_id;
                    
                                $folderIsLocked = 0;
                                if(in_array($folderId, $lockedFolderArr))
                                {
                                    $folderIsLocked = 1;
                                }

                                if($isLockedFlag == 0 || ($isLockedFlag == 1 && $folderIsLocked == 0)) 
                                {
                                    $depData = array();
                                    $depData['id'] = sracEncryptNumberData($folderId, $userSession);
                                    $depData['text'] = $folderName;
                                    $depData['isFolder'] = 1;
                                    $depData['folderTypeId'] = $folder->folder_type_id;
                                    $depData['iconUrl'] = $folderIconBasePath.'/'.$folderIconCode.'.png';
                                    $depData["isFavorited"] = $isFavorited;
                                    
                                    array_push($depFolderOrGroupArr, $depData);
                                }
                            }                                
                        }
                    }

                    $assetBasePath = Config::get('app_config.assetBasePath');
                    $baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
                    $defGroupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconWhiteGroupPath'));
                    
                    $groups = $depMgmtObj->getAllGroupsFoUser();
                    foreach($groups as $group) 
                    {
                        $isAdmin = $group->is_admin;
                        $hasPostRight = $group->has_post_right;
                        $groupName = $group->name;
                        $photoFilename = $group->img_server_filename;
                        $isFavorited = $group->is_favorited;

                        if($orgId == 0)
                        {
                            $hasPostRight = 1;
                        }

                        $groupPhotoUrl = $defGroupIconPath;
                        $groupPhotoThumbUrl = $defGroupIconPath;
                        if(isset($photoFilename) && $photoFilename != "")
                        {
                            $groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
                            $groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);                          
                        }

                        if($searchStr == "" || ($searchStr != "" && strpos(strtolower($groupName), $searchStr) !== false))
                        {
                            // if($isAdmin == 1 || $hasPostRight == 1)
                            {
                                $depData = array();
                                $depData['id'] = sracEncryptNumberData($group->group_id, $userSession);
                                $depData['text'] = $groupName;
                                $depData['isFolder'] = 0;
                                $depData['folderTypeId'] = -1;
                                $depData['iconUrl'] = $groupPhotoThumbUrl;
                                $depData["isFavorited"] = $isFavorited;
                                
                                array_push($depFolderOrGroupArr, $depData);
                            }
                        }
                    }

                    usort($depFolderOrGroupArr, function($a, $b)
                    {
                        return strcmp(strtolower($a['text']), strtolower($b['text']));
                    });

                    $viewDetails = array();
                    $viewDetails['depFolderOrGroupArr'] = $depFolderOrGroupArr;
                    
                    $_viewToRender = View::make('content.partialview._contentDependencyGlobalSearchListView', $viewDetails);
                    $_viewToRender = $_viewToRender->render();
                }
                else if($depCode == "MEDIA")
                {
                    $depMediaAttachmentArr = array();
                    $dispAttachmentNameLength = 30;


                    $folderAttachmentModelObj = $depMgmtObj->getAllAttachmentModelObj(TRUE);
                    $folderAttachments = $folderAttachmentModelObj->joinContentTable()->get();
                    foreach($folderAttachments as $attObj)
                    {
                        $attServerFilename = $attObj->server_filename;
                        $cloudStorageTypeId = $attObj->att_cloud_storage_type_id;
                        $attachmentName = $attObj->filename;

                        if($searchStr == "" || ($searchStr != "" && strpos(strtolower($attachmentName), $searchStr) !== false))
                        {
                            if($cloudStorageTypeId == 0)
                            {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename); 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);
                            }
                            else
                            {
                                $attachmentUrl = $attObj->cloud_file_url; 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attachmentName);
                            }

                            $attObj->url = $attachmentUrl;
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $orgId == 0 ? sracEncryptNumberData($attObj->appuser_content_id, $userSession) : sracEncryptNumberData($attObj->employee_content_id, $userSession);
                            $attObj->isFolder = 1;

                            $attachmentName = $attObj->filename;
                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($depMediaAttachmentArr, $attObj);
                        }
                    }

                    $userGroupIdArr = array();
                    $groups = $depMgmtObj->getAllGroupsFoUser();
                    foreach($groups as $group) 
                    {
                        array_push($userGroupIdArr, $group->group_id);
                    }
                    $groupAttachmentModelObj = $depMgmtObj->getAllAttachmentModelObj(FALSE);
                    $groupAttachmentModelObj = $groupAttachmentModelObj->joinContentTable()->withinGroupIdArr($userGroupIdArr);
                    $groupAttachments = $groupAttachmentModelObj->get();
                    foreach($groupAttachments as $attObj)
                    {
                        $attServerFilename = $attObj->server_filename;
                        $cloudStorageTypeId = $attObj->att_cloud_storage_type_id;
                        $attachmentName = $attObj->filename;

                        if($searchStr == "" || ($searchStr != "" && strpos(strtolower($attachmentName), $searchStr) !== false))
                        {
                            if($cloudStorageTypeId == 0)
                            {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename); 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);
                            }
                            else
                            {
                                $attachmentUrl = $attObj->cloud_file_url; 
                                $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attachmentName);
                            }

                            $attObj->url = $attachmentUrl;  
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = sracEncryptNumberData($attObj->group_content_id, $userSession);
                            $attObj->isFolder = 0;

                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            // array_push($depMediaAttachmentArr, $attObj);
                        }
                    }

                    usort($depMediaAttachmentArr, function($a, $b)
                    {
                        return strcmp(strtolower($a->stripped_filename), strtolower($b->stripped_filename));
                    });

                    $viewDetails = array();
                    $viewDetails['depMediaAttachmentArr'] = $depMediaAttachmentArr;

                // $response['depMediaAttachmentArr'] = $depMediaAttachmentArr;   
                    
                    $_viewToRender = View::make('content.partialview._contentDependencyGlobalSearchListView', $viewDetails);
                    $_viewToRender = $_viewToRender->render();
                }
                else if($depCode == "GOOGLE-DRIVE" || $depCode == "DROPBOX" || $depCode == "ONE-DRIVE")
                {   
                    if($depCode == "DROPBOX")   
                    {
                        $cloudStorageTypeCode = 'DRP-BX';
                    }  
                    else if($depCode == "GOOGLE-DRIVE")
                    {
                        $cloudStorageTypeCode = 'GGL-DRV';
                    }       
                    else if($depCode == "ONE-DRIVE")
                    {
                        $cloudStorageTypeCode = 'ONE-DRV';
                    }     


                    $showNoSearchStrMsg = 0;
                    $loadMediaList = true;
                    if($depCode == "ONE-DRIVE")
                    {
                        if($searchStr == '')
                        {
                            $showNoSearchStrMsg = 1;

                            $loadMediaList = false;
                        }
                    }   

                    $parentFolderName = '';
                    $baseFolderTypeId = NULL;

                    $compFolderFileList = array();

                    $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                    if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                    {
                        $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                        if($loadMediaList)
                        {
                            if(isset($accessToken) && $accessToken != "")
                            {
                                $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                                $attCldStrgMgmtObj->withAppKey($appKey);
                                $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                                $fetchedFolderList = $attCldStrgMgmtObj->getFilteredFoldersAndFiles($parentFolderName, $searchStr, $baseFolderTypeId, FALSE);

                                $hasLoadMore = $fetchedFolderList['hasLoadMore'];
                                $loadMoreCursor = $fetchedFolderList['loadMoreCursor'];
                                $isGeneralExhausted = isset($fetchedFolderList['isGeneralExhausted']) ? $fetchedFolderList['isGeneralExhausted'] : 0;
                                $primaryFolderCount = $fetchedFolderList['folderCount'];
                                $primaryFolderList = $fetchedFolderList['folderList'];

                                $appendedFiles = array();
                                $appendedFileCount = 0;

                                $allRecordsExhausted = FALSE;
                                do
                                {
                                    if($hasLoadMore == 1 && $loadMoreCursor != "")
                                    {
                                        $fetchedFolderContinuedList = $attCldStrgMgmtObj->getContinuedFilteredFoldersAndFiles($parentFolderName, $searchStr, $loadMoreCursor, NULL, $isGeneralExhausted);
                                        if(isset($fetchedFolderContinuedList) && isset($fetchedFolderContinuedList['folderCount']) && $fetchedFolderContinuedList['folderCount'] > 0)
                                        {
                                            $hasLoadMore = $fetchedFolderContinuedList['hasLoadMore'];
                                            $loadMoreCursor = $fetchedFolderContinuedList['loadMoreCursor'];
                                            $isGeneralExhausted = isset($fetchedFolderContinuedList['isGeneralExhausted']) ? $fetchedFolderContinuedList['isGeneralExhausted'] : 0;

                                            $continuedFolderCount = $fetchedFolderContinuedList['folderCount'];
                                            $continuedFolderList = $fetchedFolderContinuedList['folderList'];

                                            $appendedFiles = array_merge($appendedFiles, $continuedFolderList);
                                            $appendedFileCount += $continuedFolderCount;
                                        }
                                        else
                                        {
                                            $allRecordsExhausted = TRUE;
                                        }
                                    }
                                    else
                                    {
                                        $allRecordsExhausted = TRUE;
                                    }
                                }while(!$allRecordsExhausted);


                                $primaryFolderList = array_merge($primaryFolderList, $appendedFiles);
                                $primaryFolderCount += $appendedFileCount;

                                $compFolderFileList = $fetchedFolderList;
                                $compFolderFileList['folderList'] = $primaryFolderList;
                                $compFolderFileList['folderCount'] = $primaryFolderCount;
                            }
                        }

                        $viewDetails = array();
                        $viewDetails['folderResponse'] = $compFolderFileList;
                        $viewDetails['queryStr'] = $searchStr;
                        $viewDetails['isPrimaryListLoad'] = FALSE;
                        $viewDetails['hideOperations'] = 1;
                        $viewDetails['showNoSearchStrMsg'] = $showNoSearchStrMsg;

                        $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                        $_viewToRender = $_viewToRender->render();                       
                    }                        
                }
                else if($depCode == "GOOGLE-MAIL" || $depCode == "MICROSOFT-MAIL")
                {   
                    $cloudMailBoxTypeCode = "";
                    if($depCode == "GOOGLE-MAIL")
                    {
                        $cloudMailBoxTypeCode = 'GGL-ML';
                    }       
                    else if($depCode == "MICROSOFT-MAIL")
                    {
                        $cloudMailBoxTypeCode = 'MS-ML';
                    }   

                    $compMessageList = array();

                    $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);

                    if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                    {
                        $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                        $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);
                        
                        if(isset($accessToken) && $accessToken != "")
                        {
                            $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                            $cldMailBoxMgmtObj->withAppKey($appKey);
                            $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                            $fetchedMessageList = $cldMailBoxMgmtObj->getAllMailBoxMessages($searchStr);

                            $hasLoadMore = $fetchedMessageList['hasLoadMore'];
                            $loadMoreCursor = $fetchedMessageList['loadMoreCursor'];
                            $primaryMessageCount = $fetchedMessageList['messageCount'];
                            $primaryMessageList = $fetchedMessageList['messageList'];

                            $appendedMessages = array();
                            $appendedMessageCount = 0;

                            $allRecordsExhausted = FALSE;
                            do
                            {
                                if($hasLoadMore == 1 && $loadMoreCursor != "")
                                {
                                    $fetchedMessageContinuedList = $cldMailBoxMgmtObj->getAllMailBoxMessages($searchStr, $loadMoreCursor);
                                    if(isset($fetchedMessageContinuedList) && isset($fetchedMessageContinuedList['messageCount']) && $fetchedMessageContinuedList['messageCount'] > 0)
                                    {
                                        $hasLoadMore = $fetchedMessageContinuedList['hasLoadMore'];
                                        $loadMoreCursor = $fetchedMessageContinuedList['loadMoreCursor'];

                                        $continuedMessageCount = $fetchedMessageContinuedList['messageCount'];
                                        $continuedMessageList = $fetchedMessageContinuedList['messageList'];

                                        $appendedMessages = array_merge($appendedMessages, $continuedMessageList);
                                        $appendedMessageCount += $continuedMessageCount;
                                    }
                                    else
                                    {
                                        $allRecordsExhausted = TRUE;
                                    }
                                }
                                else
                                {
                                    $allRecordsExhausted = TRUE;
                                }
                            }while(!$allRecordsExhausted);


                            $primaryMessageList = array_merge($primaryMessageList, $appendedMessages);
                            $primaryMessageCount += $appendedMessageCount;

                            $compMessageList = $fetchedMessageList;
                            $compMessageList['messageList'] = $primaryMessageList;
                            $compMessageList['messageCount'] = $primaryMessageCount;
                        }
                        

                        $viewDetails = array();
                        $viewDetails['messageResponse'] = $compMessageList;
                        $viewDetails['queryStr'] = $searchStr;
                        $viewDetails['isPrimaryListLoad'] = FALSE;
                        $viewDetails['hideOperations'] = 0;
                        $viewDetails['tzStr'] = $tzStr;
                        $viewDetails['orgKey'] = $encOrgId;

                        $_viewToRender = View::make('cloudMailBox.partialview._messageSubView', $viewDetails);
                        $_viewToRender = $_viewToRender->render();                       
                    }                        
                }
                
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
    
    public function appuserContentInfoModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $isFolderFlag = Input::get('isFolder');
        $contentId = (Input::get('id'));
        
        $response = array();
        if($encUserId != "" && $contentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                $modalTitle = "Content Info";
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                }
                else
                {
                    $isFolder = FALSE;
                }
                
                $createTs = 0;
                $updateTs = 0;
                $contentSizeKb = 0;
                $contentAttachments = array();
                $contentIsRemoved = 0;
                $contentRemovedAt = 0;
                
                $content = $depMgmtObj->getContentObject($contentId, $isFolder);
                if(isset($content))
                {
                    $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
                    foreach($contentAttachments as $attachment)
                    {
                        $contentSizeKb += $attachment->filesize;
                    }
                    
                    $createTs = $content->create_timestamp;
                    $updateTs = $content->update_timestamp;

                    if($isFolder && $content->is_removed != 0)
                    {
                        $contentIsRemoved = 1;
                        $contentRemovedAt = $content->removed_at;
                    }
                }
                    
                if($contentSizeKb < 1000)
                {
                    $contentSizeStr = $contentSizeKb." KB(s)";
                }
                else
                {
                    $contentSizeStr = round($contentSizeKb/1024, 2)." MB(s)";
                }
                    
                $attachmentCount = count($contentAttachments);

                $isConversation = false;
                $conversationThreadCount = 0;
                     
                $contentText = "";
                if(isset($content->content) && $content->content != "")
                {
                    try
                    {
                        $contentText = Crypt::decrypt($content->content);
                    } 
                    catch (DecryptException $e) 
                    {
                        
                    }
                }
                        
                $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);
                $strippedContentForSender = $depMgmtObj->removePrefixFromSharedContentText($strippedContentText);
                if(isset($strippedContentForSender)) {
                    $strippedContentText = $strippedContentForSender['content'];
                    $contentSenderStr = $strippedContentForSender['sender'];

                    if($contentSenderStr != "")
                    {
                        $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($contentText);
                        $contentConversationDetails = $contentConversationResponse['conversation'];

                        if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
                        {
                            $isConversation = TRUE;
                            $conversationThreadCount = count($contentConversationDetails);
                        }
                    }
                }   

                $viewDetails = array();
                $viewDetails['modalTitle'] = $modalTitle;
                $viewDetails['isConversation'] = $isConversation;
                $viewDetails['conversationThreadCount'] = $conversationThreadCount;
                $viewDetails['noteSizeStr'] = $contentSizeStr;
                $viewDetails['createTs'] = $createTs;
                $viewDetails['updateTs'] = $updateTs;
                $viewDetails['attachmentCnt'] = $attachmentCount;
                $viewDetails['isRemoved'] = $contentIsRemoved;
                $viewDetails['removedAt'] = $contentRemovedAt;
                
                $_viewToRender = View::make('content.partialview._contentInfoModal', $viewDetails);
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
    
    public function appuserContentAttachmentInfoModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $isFolderFlag = Input::get('isFolder');
        $attachmentId = (Input::get('attachmentId'));
        $contentId = (Input::get('contentId'));
        
        $response = array();
        if($encUserId != "" && $contentId != "" && $attachmentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);
                $attachmentId = sracDecryptNumberData($attachmentId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                }
                else
                {
                    $isFolder = FALSE;
                }
                
                $createTs = 0;
                $updateTs = 0;
                $attachmentSizeKb = 0;

                $attSourceStr = "HyLyt";
                
                $content = $depMgmtObj->getContentObject($contentId, $isFolder);
                $contentAttachment = $depMgmtObj->getContentAttachment($attachmentId, $isFolder);
                if(isset($content) && isset($contentAttachment))
                {
                    $attachmentSizeKb = $contentAttachment->filesize;
                    
                    $createTs = $contentAttachment->create_ts;
                    $updateTs = $contentAttachment->update_ts;

                    $attCloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                    if($attCloudStorageTypeId > 0)
                    {
                        $attCloudStorageType = $depMgmtObj->getCloudStorageTypeObjectById($attCloudStorageTypeId);
                        if(isset($attCloudStorageType))
                        {
                            $attSourceStr = $attCloudStorageType->cloud_storage_type_name;
                        }
                    }
                }
                    
                if($attachmentSizeKb < 1000)
                {
                    $attachmentSizeStr = $attachmentSizeKb." KB(s)";
                }
                else
                {
                    $attachmentSizeStr = round($attachmentSizeKb/1024, 2)." MB(s)";
                }

                $viewDetails = array();
                $viewDetails['createTs'] = $createTs;
                $viewDetails['updateTs'] = $updateTs;
                $viewDetails['attSizeStr'] = $attachmentSizeStr;
                $viewDetails['attSourceStr'] = $attSourceStr;
                
                $_viewToRender = View::make('content.partialview._contentAttachmentInfoModal', $viewDetails);
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
    
    public function appuserFolderOrGroupContentInfoModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $isFolderFlag = Input::get('isFolder');
        $folderOrGroupId = (Input::get('id'));
        $isFolderLocked = Input::get('isFolderLocked');
        
        $response = array();
        if($encUserId != "" && $folderOrGroupId != "")
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
                
                $folderOrGroupId = sracDecryptNumberData($folderOrGroupId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $lockedFolderArr = $depMgmtObj->getLockedFolderIdArr();
                $sentFolderId = $depMgmtObj->getSentFolderId();
                
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;                   
                    $modalTitle = "Folder Content Info";
                    
                    if($folderOrGroupId > 0)
                    {
                        $folder = $depMgmtObj->getFolderObject($folderOrGroupId);
                        if(isset($folder))
                        {
                            $dispName = $folder->folder_name;
                        }
                    }
                    else
                    {
                        $dispName = "All Note(s)";
                    }                   
                }
                else
                {
                    $isFolder = FALSE;
                    $modalTitle = "Group Content Info";

                    $dispName = $depMgmtObj->getGroupName($folderOrGroupId);
                }
                $contentSizeKb = 0;
                $allContents = $depMgmtObj->getAllContents($isFolder, $folderOrGroupId);
                if($isFolder)
                {
                    $allContents = $allContents->filterExceptRemoved();
                }
                $contentCount = 0;
                foreach($allContents as $content)
                {
                    $contentId = 0;
                    $toBeConsidered = TRUE;
                    if($isFolder)
                    {
                        if($folderOrGroupId == -1 && (($isFolderLocked == 1 && in_array($content->folderId, $lockedFolderArr)) || ($content->folderId == $sentFolderId)))
                        {
                            $response['con_'.$content->folder_name] = 'Condition met for : '.$content->folderId;
                            $toBeConsidered = FALSE;                            
                        }
                        
                        if($encOrgId != "")
                        {
                            $contentId = $content->employee_content_id;
                        }
                        else
                        {
                            $contentId = $content->appuser_content_id;
                        }
                    }
                    else
                    {
                        $contentId = $content->group_content_id;
                    }
                    
                    if($toBeConsidered == TRUE)
                    {
                        $contentCount++;
                        $response['Con_Cnt_'.$content->folder_name] = 'Condition only met for : '.$content->folderId;
                        $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolder);
                        foreach($contentAttachments as $attachment)
                        {
                            $contentSizeKb += $attachment->filesize;
                        }
                    }
                }
                
                if($contentSizeKb < 1000)
                {
                    $contentSizeStr = $contentSizeKb." KB(s)";
                }
                else
                {
                    $contentSizeStr = round($contentSizeKb/1024, 2)." MB(s)";
                }           

                $viewDetails = array();
                $viewDetails['modalTitle'] = $modalTitle;
                $viewDetails['dispName'] = $dispName;
                $viewDetails['contentCount'] = $contentCount;
                $viewDetails['contentSizeStr'] = $contentSizeStr;
                
                $_viewToRender = View::make('content.partialview._folderOrGroupInfoModal', $viewDetails);
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
     * Toggle Content Marking.
     *
     * @return json array
     */
    public function modifyContentDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = Input::get('id');
        $isFolderFlag = Input::get('isFolder');
        
        $colorCode = Input::get('colorCode');

        $sourceId = Input::get('sourceId');
        $sourceSyncPending = Input::get('sourceSyncPending');
        $sourceName = Input::get('sourceName');

        $tagList = Input::get('tagList');
        $tagSyncPending = Input::get('tagSyncPending');
        $tagNameArr = Input::get('tagNameArr');

        $folderId = Input::get('folderId');
        $folderSyncPending = Input::get('folderSyncPending');
        $folderName = Input::get('folderName');
        $folderIconCode = Input::get('iconCode');
        $folderIsFavorited = Input::get('folderIsFavorited');

        $opCodeTagChange = Config::get("app_config.content_push_notif_op_code_tag_change");
        $notifOpCode = "";

        $response = array();

        if($encUserId != "" && $id != "")
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

            $responseLogs = array();
            
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
                
                $id = sracDecryptNumberData($id, $userSession);
                $folderId = sracDecryptNumberData($folderId, $userSession);
                $sourceId = sracDecryptNumberData($sourceId, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag * 1 == 1)
                    {
                        $isFolder = TRUE;
                    }
                }
                else
                    $isFolder = TRUE;

                $responseLogs['isFolder'] = $isFolder;
                $responseLogs['isFolderFlag'] = $isFolderFlag;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userContent = $depMgmtObj->getContentObject($id, $isFolder);   
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId(); 
                
                $responseLogs['userContent'] = $userContent;           
               
                if(isset($userContent) && ((!$isFolder) || ($isFolder && $userContent->is_removed == 0)))
                {
                    $saveProcessed = FALSE;

                    $isFolderSyncPerformed = false;
                    $folderResponse = array();

                    $isSourceSyncPerformed = false;
                    $sourceResponse = array();

                    $isTagSyncPerformed = false;
                    $tagResponse = array();
                    
                    if($isFolder)
                    {
                        if(isset($folderSyncPending) && $folderSyncPending == 1 && isset($folderName) && trim($folderName) != "")
                        {
                            $folderResponse = $depMgmtObj->addEditFolder(0, $folderName, $folderIconCode, $folderIsFavorited);
                            $folderId = $folderResponse["syncId"];
                            $isFolderSyncPerformed = true;
                        }

                        if(isset($folderId) && $folderId > 0)
                        {
                            $responseLogs['folderId'] = $folderId;
                            $folderObj = $depMgmtObj->getFolderObject($folderId);
                            if(isset($folderObj))
                            {
                                $userContent->folder_id = $folderId;
                                $userContent->save();
                                
                                $saveProcessed = TRUE;
                                $responseLogs['saveProcessed'] = 'FOR FOLDER';
                            }
                        }
                                           
                        if(!$saveProcessed)
                        {
                            if(isset($sourceSyncPending) && $sourceSyncPending == 1 && isset($sourceName) && trim($sourceName) != "")
                            {                                
                                $sourceResponse = $depMgmtObj->addEditSource(0, $sourceName);
                                $sourceId = $sourceResponse["syncId"];
                                $isSourceSyncPerformed = true;
                            }

                            if(isset($sourceId) && $sourceId > 0)
                            {
                                $responseLogs['sourceId'] = $sourceId;
                                $sourceObj = $depMgmtObj->getSourceObject($sourceId);
                                if(isset($sourceObj))
                                {
                                    $userContent->source_id = $sourceId;
                                    $userContent->save();
                                    
                                    $saveProcessed = TRUE;
                                    $responseLogs['saveProcessed'] = 'FOR SOURCE';
                                }
                            }
                        }
                    }
                    
                    if(!$saveProcessed && isset($colorCode) && $colorCode != '')
                    {
                        $userContent->color_code = $colorCode;
                        $userContent->save();
                        
                        $saveProcessed = TRUE;
                        $responseLogs['saveProcessed'] = 'FOR COLOR';
                    }
                    
                    if(!$saveProcessed && isset($tagList))
                    {
                        if(is_array($tagList))
                            $tagsArr = $tagList;
                        else
                            $tagsArr = json_decode($tagList);

                        if(isset($tagSyncPending) && $tagSyncPending == 1 && isset($tagNameArr) && count($tagNameArr) > 0)
                        {
                            foreach($tagNameArr as $tagName)
                            {
                                $indTagResponse = $depMgmtObj->addEditTag(0, $tagName);
                                $tagId = $indTagResponse["syncId"];
                                array_push($tagsArr, $tagId);                       
                                array_push($tagResponse, $indTagResponse); 
                                $isTagSyncPerformed = true;                     
                            }
                        }
                                
                        if(is_array($tagsArr))
                        {
                            $tagsArr = sracDecryptNumberArrayData($tagsArr, $userSession);
                            $responseLogs['tagsArr'] = $tagsArr;
                            if($isFolder)
                            {
                                $depMgmtObj->addEditContentTags($id, $tagsArr);
                            }
                            else
                            {
                                if($orgId > 0)
                                    $userOrEmpId = $orgEmpId;
                                else
                                    $userOrEmpId = $userId;
                                    
                                $depMgmtObj->addEditGroupContentTags($id, $userOrEmpId, $tagsArr);
                            }

                            if(!$isFolder)
                            {
                                $updUserContent = $depMgmtObj->getContentObject($id, $isFolder);

                                $contentText = "";
                                if(isset($updUserContent->content) && $updUserContent->content != "")
                                {
                                    try
                                    {
                                        $contentText = Crypt::decrypt($updUserContent->content);
                                    } 
                                    catch (\Exception $e) 
                                    {

                                    }
                                }

                                $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);

                                $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($strippedContentText);
                                $contentConversationDetails = $contentConversationResponse['conversation'];
                                if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
                                {
                                    $createTimeStamp = CommonFunctionClass::getCreateTimestamp();

                                    $isChangeLogOp = 1;
                                    $changeLogText = "";
                                    $isDeleteOp = 0;
                                    $isReplyOp = 1;
                                    $isEditOp = 0;
                                    $conversationIndex = -1;
                                    $updateTs = $createTimeStamp;
                                    $editText = '';
                                    $replyText = 'Tag(s) have been changed';

                                    $convOpParams = array();
                                    $convOpParams['isChangeLogOp'] = $isChangeLogOp;
                                    $convOpParams['changeLogText'] = $changeLogText;
                                    $convOpParams['isDeleteOp'] = $isDeleteOp;
                                    $convOpParams['isReplyOp'] = $isReplyOp;
                                    $convOpParams['replyText'] = $replyText;
                                    $convOpParams['isEditOp'] = $isEditOp;
                                    $convOpParams['editText'] = $editText;
                                    $convOpParams['conversationIndex'] = $conversationIndex;
                                    $convOpParams['updateTs'] = $updateTs;

                                    $response = $depMgmtObj->performContentConversationPartOperation($isFolder, $id, $updUserContent, $convOpParams);
                                }
                            }                                
                        
                            $saveProcessed = TRUE;
                            $responseLogs['saveProcessed'] = 'FOR TAG';

                            $notifOpCode = $opCodeTagChange;
                        }                            
                    }

                    $responseLogs['isFolderSyncPerformed'] = $isFolderSyncPerformed;
                    $responseLogs['isSourceSyncPerformed'] = $isSourceSyncPerformed;
                    $responseLogs['isTagSyncPerformed'] = $isTagSyncPerformed;

                    CommonFunctionClass::setLastSyncTs($userId, $loginToken);

                    $response['responseLogs'] = $responseLogs;
                    
                    if($saveProcessed)
                    {
                        $status = 1;

                        // if($orgId > 0)
                        // {
                        //     $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $id);
                        // }
                        // else
                        // {
                        //     $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $id);
                        // }

                        // $modContentResponseObj = array();
                        // $modContentResponseObj['contentId'] = sracEncryptNumberData($id, $userSession);
                        // $modContentResponseObj['isFolder'] = $isFolderFlag;
                        // $modContentResponseObj['userId'] = sracEncryptNumberData($userId, $userSession);
                        // $modContentResponseObj['orgId'] = $encOrgId;

                        // $compContentArr = array();
                        // array_push($compContentArr, $modContentResponseObj);
                        // $response['compContentArr'] = json_encode($compContentArr);
                        
                        if($isFolderSyncPerformed)
                        {
                            $folderResponse['syncId'] = sracEncryptNumberData($folderResponse['syncId'], $userSession);
                            $response['folderResponse'] = $folderResponse;
                        }
                        
                        if($isSourceSyncPerformed)
                        {
                            $sourceResponse['syncId'] = sracEncryptNumberData($sourceResponse['syncId'], $userSession);
                            $response['sourceResponse'] = $sourceResponse;
                        }
                        
                        if($isTagSyncPerformed)
                        {
                            foreach ($tagResponse as $indTagResponse) {
                                $indTagResponse['syncId'] = sracEncryptNumberData($indTagResponse['syncId'], $userSession);
                            }
                            $response['tagResponse'] = $tagResponse;
                        }

                        $response['notifOpCode'] = $notifOpCode;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_data');
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
     * Check Multiple Content Can Be Shared.
     *
     * @return json array
     */
    public function checkContentCanBeShared()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $isFolder = Input::get('isFolder');
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        $response['idArr'] = $idArr;

        if($encUserId != "" && is_array($idArr) && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $someContentNotShareable = 0;
                $someContentLocked = 0;
                $someContentRemoved = 0;
                for($i=0; $i<count($idArr); $i++)
                {                 
                    $id = $idArr[$i];
                    $userContentLocked = $depMgmtObj->getContentLockStatus($id, $isFolder); 
                    if($userContentLocked == 1)
                    {
                        $someContentLocked = 1;
                    }

                    $userContentShareEnabled = $depMgmtObj->getContentShareEnabledStatus($id, $isFolder); 
                    if($userContentShareEnabled == 0)
                    {
                        $someContentNotShareable = 1;
                    }

                    $userContentRemoved = $depMgmtObj->getContentRemovedStatus($id, $isFolder); 
                    if($userContentRemoved == 1)
                    {
                        $someContentRemoved = 1;
                    }
                }
                
                // if($someContentLocked == 1) {
                //     $status = -1;
                //     $msg = 'Selection contains some locked content(s)';
                // }
                // else 
                if($someContentNotShareable == 1) {
                    $status = -1;
                    $msg = 'Selection contains some restricted content(s)';
                }
                else if($someContentRemoved == 1) {
                    $status = -1;
                    $msg = 'Selection contains some removed content(s)';
                }
                else {
                    $status = 1;   
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
     * Add Content.
     *
     * @return json array
     */
    public function performContentConversationPartOperation()
    {
        // Log::info('====================== performContentConversationPartOperation starts =============================');
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');
        $contentId = (Input::get('contentId'));
        $isFolderFlag = Input::get('isFolder');
        $conversationIndex = Input::get('convIndex');
        $updateTs = Input::get('updateTimeStamp');
        $isDeleteOp = Input::get('isDeleteOp');
        $isReplyOp = Input::get('isReplyOp');
        $replyText = Input::get('replyText');
        $isEditOp = Input::get('isEditOp');
        $editText = Input::get('editText');

        if(!isset($isDeleteOp) || $isDeleteOp != 1)
        {
            $isDeleteOp = 0;
        }

        if(!isset($isReplyOp) || $isReplyOp != 1)
        {
            $isReplyOp = 0;
        }

        $response = array();
        if($encUserId != "" && $contentId != "" && $conversationIndex >= -1 && $updateTs > 0 && ( ($isDeleteOp == 1) || ($isReplyOp == 1 && $replyText != "") || ($isEditOp == 1 && $editText != "") ))
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

                $contentId = sracDecryptNumberData($contentId, $userSession);

                $isFolder = FALSE;
                if(isset($isFolderFlag) && $isFolderFlag == 1)
                {
                    $isFolder = TRUE;                    
                }
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $content = $depMgmtObj->getContentObject($contentId, $isFolder);  

                if(isset($content) && ((!$isFolder) || ($isFolder && $content->is_removed == 0)))
                {
                    $convOpParams = array();
                    $convOpParams['isChangeLogOp'] = 0;
                    $convOpParams['changeLogText'] = "";
                    $convOpParams['isDeleteOp'] = $isDeleteOp;
                    $convOpParams['isReplyOp'] = $isReplyOp;
                    $convOpParams['replyText'] = $replyText;
                    $convOpParams['isEditOp'] = $isEditOp;
                    $convOpParams['editText'] = $editText;
                    $convOpParams['conversationIndex'] = $conversationIndex;
                    $convOpParams['updateTs'] = $updateTs;

                    $response = $depMgmtObj->performContentConversationPartOperation($isFolder, $contentId, $content, $convOpParams);

                    $status = $response['status'];
                    $msg = $response['msg'];
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

        // Log::info('====================== performContentConversationPartOperation ends =============================');

        return Response::json($response);
    }

    /**
     * Add Content.
     *
     * @return json array
     */
    public function performContentModificationRespectivePush()
    {
        // Log::info('====================== performContentModificationRespectivePush starts =============================');
        
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');
        $contentId = Input::get('contentId');
        $isFolderFlag = Input::get('isFolder');
        $isSilent = Input::get('isSilent');
        $opCode = Input::get('notifOpCode');

        if(!isset($isSilent) || $isSilent != 1)
        {
            $isSilent = 0;
        }

        if(!isset($opCode))
        {
            $opCode = "";
        }

        $response = array();
        if($encUserId != "" && $contentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);

                $isFolder = FALSE;
                if(isset($isFolderFlag) && $isFolderFlag == 1)
                {
                    $isFolder = TRUE;                    
                }
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $sharedByName = $user->fullname;  
                $sharedByEmail = $user->email;

                $orgEmployee = $depMgmtObj->getPlainEmployeeObject();
                if(isset($orgEmployee))
                {   
                    $sharedByName = $orgEmployee->employee_name;
                    $sharedByEmail = $orgEmployee->email;          
                }

                $sendSilentPushOnly = false;
                if($isSilent == 1)
                {
                    $sendSilentPushOnly = true;
                }

                $isAdd = 0;
                $depMgmtObj->sendRespectiveContentModificationPush($isFolder, $contentId, $isAdd, $sharedByEmail, false, $sendSilentPushOnly, $opCode);

                $status = 1;
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

        // Log::info('====================== performContentModificationRespectivePush ends =============================');

        return Response::json($response);
    }

    /**
     * Add Content.
     *
     * @return json array
     */
    public function performContentSharingRespectivePush()
    {
        // Log::info('====================== performContentSharingRespectivePush starts =============================');
        $msg = "";
        $status = 0;
        $response = array();

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');
        $compContentArrStr = Input::get('compContentArr');

        $compContentArr = json_decode($compContentArrStr, true);

        if($encUserId != "" && is_array($compContentArr) && count($compContentArr) > 0)
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $orgEmpId = $depMgmtObj->getOrganizationEmployeeId();

                $senderUserOrEmpId = $depMgmtObj->getEmployeeOrUserId();
                $sharedByName = $depMgmtObj->getEmployeeOrUserName();
                $sharedByEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $isAdd = 1;

                foreach ($compContentArr as $key => $compContentObj) {
                    $encContentId = $compContentObj['contentId'];
                    $isFolderFlag = $compContentObj['isFolder'];
                    $encContentOrgKey = $compContentObj['orgId'];
                    $encContentUserId = $compContentObj['userId'];

                    $contentId = sracDecryptNumberData($encContentId, $userSession);
                    $contUserId = sracDecryptNumberData($encContentUserId, $userSession);

                    $contAppUser = New Appuser;
                    $contAppUser->appuser_id = $contUserId;

                    $isFolder = FALSE;
                    if(isset($isFolderFlag) && $isFolderFlag * 1 == 1)
                    {
                        $isFolder = TRUE;                    
                    }

                    $contentDepMgmtObj = New ContentDependencyManagementClass;
                    $contentDepMgmtObj->withOrgKey($contAppUser, $encContentOrgKey);
                    $contentDepMgmtObj->setCurrentLoginToken($loginToken);

                    $contentOrgId = $contentDepMgmtObj->getOrganizationId();
                    $contentOrgEmpId = $contentDepMgmtObj->getOrganizationEmployeeId();

                    if($isFolder)
                    {
                        if($contentOrgId > 0)
                        {
                            $this->sendOrgContentAddMessageToDevice($contentOrgEmpId, $contentOrgId, null, $isFolder, $contentId);    
                            MailClass::sendOrgContentAddedMail($contentOrgId, $contentOrgEmpId, $contentId, $sharedByEmail);
                            MailClass::sendOrgContentDeliveredMail($contentOrgId, $contentOrgEmpId, $sharedByEmail);
                        }         
                        else
                        {
                            $this->sendEntryAddMessageToDevice($contUserId, $contentId, $sharedByEmail);
                            MailClass::sendContentAddedMail($contUserId, $contentId, $sharedByEmail);
                            MailClass::sendContentDeliveredMail($contUserId, $sharedByEmail);
                        }
                    }
                    else
                    {     
                        $encGroupId = isset($compContentObj['groupId']) ? $compContentObj['groupId'] : '';
                        $groupId = sracDecryptNumberData($encGroupId, $userSession);

                        $groupMembers = $depMgmtObj->getGroupMembers($groupId);
                        if(isset($groupMembers))
                        {
                            if($contentOrgId > 0)
                            {
                                foreach($groupMembers as $groupMember)
                                {   
                                    $memberEmpId = $groupMember->employee_id;

                                    $tempDepMgmtObj = New ContentDependencyManagementClass;                             
                                    $tempDepMgmtObj->withOrgIdAndEmpId($contentOrgId, $memberEmpId);   
                                    $orgEmployee = $tempDepMgmtObj->getPlainEmployeeObject();
                                    
                                    if(isset($orgEmployee) && $orgEmployee->is_active == 1)
                                    {
                                        if($memberEmpId != $orgEmpId)
                                        {
                                            $this->sendOrgGroupEntryAddMessageToDevice($contentOrgId, $memberEmpId, $contentId, $isAdd, $sharedByEmail, $contentOrgEmpId);
                                            MailClass::sendOrgContentAddedMail($contentOrgId, $memberEmpId, $contentId, $sharedByEmail, $groupId);
                                        }
                                        else
                                        {
                                            $this->sendOrgContentAddMessageToDevice($memberEmpId, $contentOrgId, null, $isFolder, $contentId);
                                        }
                                    }
                                    
                                }
                            }
                            else
                            {
                                foreach($groupMembers as $groupMember)
                                {
                                    $memberUserId = $groupMember->member_appuser_id;

                                    if($memberUserId != $userId)
                                    {
                                        $this->sendGroupEntryAddMessageToDevice($memberUserId, $contentId, $isAdd, $sharedByEmail);                    
                                        MailClass::sendContentAddedMail($memberUserId, $contentId, $sharedByEmail, $groupId);  
                                    }   
                                    else
                                    {
                                        $this->sendContentAddMessageToDevice($memberUserId, null, $isFolder, $contentId);
                                    }             
                                }
                            }
                        }
                    }
                }

                $status = 1;
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

        // Log::info('====================== performContentSharingRespectivePush ends =============================');

        return Response::json($response);
    }


    /**
     * Check Multiple Content Can Be Shared.
     *
     * @return json array
     */
    public function checkContentCanBePrinted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $isFolder = Input::get('isFolder');
        $idArr = Input::get('idArr');
        $loginToken = Input::get('loginToken');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $someContentNotShareable = 0;
                $someContentLocked = 0;
                $someContentRemoved = 0;
                for($i=0; $i<count($idArr); $i++)
                {                 
                    $id = $idArr[$i];
                    $userContentLocked = $depMgmtObj->getContentLockStatus($id, $isFolder); 
                    if($userContentLocked == 1)
                    {
                        $someContentLocked = 1;
                    }

                    $userContentShareEnabled = $depMgmtObj->getContentShareEnabledStatus($id, $isFolder); 
                    if($userContentShareEnabled == 0)
                    {
                        $someContentNotShareable = 1;
                    }

                    $userContentRemoved = $depMgmtObj->getContentRemovedStatus($id, $isFolder); 
                    if($userContentRemoved == 1)
                    {
                        $someContentRemoved = 1;
                    }
                }
                
                // if($someContentLocked == 1) {
                //     $status = -1;
                //     $msg = 'Selection contains some locked content(s)';
                // }
                // else 
                if($someContentNotShareable == 1) {
                    $status = -1;
                    $msg = 'Selection contains some restricted content(s)';
                }
                else if($someContentRemoved == 1) {
                    $status = -1;
                    $msg = 'Selection contains some removed content(s)';
                }
                else {
                    $status = 1;   
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
    
    public function webVideoTutorialModal()
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
                
                $videoTutorialEmbedUrl = Config::get('app_config.retailUserWebVideoTutorialEmbedLink');

                $viewDetails = array();
                $viewDetails['videoTutorialEmbedUrl'] = $videoTutorialEmbedUrl;
                
                $_viewToRender = View::make('content.partialview._viewWebVideoTutorialModal', $viewDetails);
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
    
    public function contentShareRecipientSelectionModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolder = Input::get('isFolder');
        $forContent = Input::get('forContent');
        $performContentRemove = Input::get('performContentRemove');

        $oneLineContentText = Input::get('oneLineContentText');
        $isOneLineQuickShare = Input::get('isOneLineQuickShare');
        if(!isset($isOneLineQuickShare) || $isOneLineQuickShare != 1)
        {
            $isOneLineQuickShare = 0;
            $oneLineContentText = "";
        }

        if(!isset($performContentRemove) || $performContentRemove != 1)
        {
            $performContentRemove = 0;
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
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();

                $viewDetails = array();
                $viewDetails['currUserId'] = $encUserId;
                $viewDetails['currOrgKey'] = $encOrgId;
                $viewDetails['currOrgId'] = $orgId;
                $viewDetails['currLoginToken'] = $loginToken;
                $viewDetails['currIsFolder'] = $isFolder;
                $viewDetails['forContent'] = $forContent;
                $viewDetails['performContentRemove'] = $performContentRemove;
                $viewDetails['isOneLineQuickShare'] = $isOneLineQuickShare;
                $viewDetails['oneLineContentText'] = $oneLineContentText;
                
                $_viewToRender = View::make('content.partialview._contentShareRecipientSelectionModal', $viewDetails);
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

    public function partContentShareRecipientSelectionModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $convText = Input::get('convText');
        
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();

                $viewDetails = array();
                $viewDetails['currUserId'] = $encUserId;
                $viewDetails['currOrgKey'] = $encOrgId;
                $viewDetails['currOrgId'] = $orgId;
                $viewDetails['currLoginToken'] = $loginToken;
                $viewDetails['convText'] = $convText;
                
                $_viewToRender = View::make('content.partialview._contentPartShareRecipientSelectionModal', $viewDetails);
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

    public function partContentShareConverationSelectionModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $convText = Input::get('convText');
        $selAppuserContactId = (Input::get('selAppuserContactId'));
        $selGroupId = (Input::get('selGroupId'));
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');
        
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

                $selAppuserContactId = sracDecryptNumberData($selAppuserContactId, $userSession);
                $selGroupId = sracDecryptNumberData($selGroupId, $userSession);
                
                $status = 1;

                $contentList = array();
                $secContentList = array();

                $showFolderHeader = FALSE;
                $showGroupHeader = FALSE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();

                $empOrUserId = $depMgmtObj->getEmployeeOrUserId();
                $isLocked = FALSE;
                $searchStr = '';
                $sortBy = 3;
                $sortOrder = -1; 

                $selAppuserEmail = '';

                $response['selAppuserContactId'] = $selAppuserContactId; 

                if(isset($selAppuserContactId) && $selAppuserContactId > 0)
                {
                    $selAppuserContact = AppuserContact::ofUserContact($selAppuserContactId)->first();

                    $response['selAppuserContact'] = $selAppuserContact; 

                    if(isset($selAppuserContact))
                    {        
                        $selAppuserEmail = $selAppuserContact->email;

                        $isAllNotes = TRUE;
                        $folderOrGroupId = -1;

                        $isFolder = TRUE;
                        $isFolderFlag = 1;

                        $chkShowFolder = 1;
                        $chkShowGroup = 0;

                        $hasFilters = 1;

                        $folderFilterUtilObj = New FolderFilterUtilClass;
                        $folderFilterUtilObj->setFilterStr('');

                        $folderModelObj = $depMgmtObj->getAllFoldersModelObj();
                        $usrFolderArr = $folderModelObj->isNotSentFolder()->get();
                        $usrFolderIdArr = array();
                        foreach($usrFolderArr as $usrFolder) {
                            if($orgId > 0)
                            {
                                array_push($usrFolderIdArr, $usrFolder->employee_folder_id);
                            }
                            else
                            {
                                array_push($usrFolderIdArr, $usrFolder->appuser_folder_id);
                            }
                        }

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
                        $filArr['filRepeatStatus'] = $folderFilterUtilObj->getFilterValueRepeatStatus();
                        $filArr['filCompletedStatus'] = $folderFilterUtilObj->getFilterValueCompletedStatus();
                        $filArr['filSenderEmail'] = $folderFilterUtilObj->getFilterValueSenderEmail();
                        $filArr['filDateFilterType'] = $folderFilterUtilObj->getFilterValueDateFilterType();
                        $filArr['filDateDayCount'] = $folderFilterUtilObj->getFilterValueDateFilterTypeDayCount();
                        $filArr['chkIsTrashed'] = 0;
                        $filArr['chkShowFolder'] = $chkShowFolder;
                        $filArr['chkShowGroup'] = $chkShowGroup;
                        $filArr['filSenderEmail'] = $selAppuserEmail; 
                        $filArr['filExceptSentFolder'] = 1;               

                        $contentListFormulationObj = New ContentListFormulationClass;
                        $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                        $contentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

                        // if(count($contentList) > 0)
                        // {
                        //     $showFolderHeader = TRUE;
                        // }

                        // $isFolder = FALSE;

                        // $usrGroupArr = $depMgmtObj->getAllGroupsFoUser();
                        // $usrGroupIdArr = array();
                        // foreach($usrGroupArr as $usrGroup) {
                        //     array_push($usrGroupIdArr, $usrGroup->group_id);
                        // }
                        // $filArr['filAllNotesGroupArr'] = $usrGroupIdArr;

                        // $secContentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

                        // if(count($secContentList) > 0)
                        // {
                        //     $showGroupHeader = TRUE;
                        // }
                    }
                }
                elseif(isset($selGroupId) && $selGroupId > 0)
                {
                    $isAllNotes = FALSE;
                    $isFolder = FALSE;
                    $folderOrGroupId = $selGroupId;

                    $isFolderFlag = 0;

                    $chkShowFolder = 0;
                    $chkShowGroup = 1;
                        
                    $hasFilters = 0;    
                    $filArr = NULL;              

                    $contentListFormulationObj = New ContentListFormulationClass;
                    $contentListFormulationObj->setWithIdEncryption(true, $userSession);
                    $contentList = $contentListFormulationObj->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

                    if(count($contentList) > 0)
                    {
                        $showGroupHeader = TRUE;
                    }
                }

                $response['filArr'] = $filArr; 

                $resCnt = count($contentList); 
                $secResCnt = count($secContentList); 

                $viewDetails = array();
                $viewDetails['currUserId'] = $encUserId;
                $viewDetails['currOrgKey'] = $encOrgId;
                $viewDetails['currOrgId'] = $orgId;
                $viewDetails['currLoginToken'] = $loginToken;
                $viewDetails['selAppuserContactId'] = $selAppuserContactId;
                $viewDetails['selGroupId'] = $selGroupId;
                $viewDetails['convText'] = $convText;
                $viewDetails['contentArr'] = $contentList;
                $viewDetails['contentCnt'] = $resCnt;
                $viewDetails['secContentArr'] = $secContentList;
                $viewDetails['secContentCnt'] = $secResCnt;
                $viewDetails['isAllNotes'] = $isAllNotes;
                $viewDetails['orgKey'] = $encOrgId;
                $viewDetails['tzOffset'] = $tzOffset;
                $viewDetails['tzStr'] = $tzStr;
                $viewDetails['selIsFolder'] = $isFolderFlag;
                $viewDetails['selFolderOrGroupId'] = $folderOrGroupId;
                $viewDetails['showSelection'] = TRUE;
                $viewDetails['disableViewContentDetails'] = TRUE;
                $viewDetails['showFolderHeader'] = $showFolderHeader;
                $viewDetails['showGroupHeader'] = $showGroupHeader;
                $viewDetails['sendToEmail'] = $selAppuserEmail;
                
                $_viewToRender = View::make('content.partialview._contentPartShareConversationSelectionModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();
                
                $response['view'] = $_viewToRender;
                $response['viewDetails'] = $viewDetails; 
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

    public function performAppuserPartContentShareToConversation()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $convText = Input::get('convText');
        $selAppuserContactId = (Input::get('selAppuserContactId'));
        $selGroupId = (Input::get('selGroupId'));
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');
        $isFolderFlag = Input::get('isFolder');
        $selContentIdArr = (Input::get('selContentIdArr'));

        $selContentIdArr = jsonDecodeArrStringIfRequired($selContentIdArr);
        
        $response = array();
        if($encUserId != "" && isset($selContentIdArr) && count($selContentIdArr) > 0)
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

                $selAppuserContactId = sracDecryptNumberData($selAppuserContactId, $userSession);
                $selGroupId = sracDecryptNumberData($selGroupId, $userSession);
                $selContentIdArr = sracDecryptNumberArrayData($selContentIdArr, $userSession);
                
                $status = 1;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();


                $isFolder = FALSE;
                if(isset($isFolderFlag) && $isFolderFlag == 1)
                {
                    $isFolder = TRUE;                    
                }

                for($i=0; $i<count($selContentIdArr); $i++)
                {                 
                    $selContentId = $selContentIdArr[$i];

                    // $userContentLocked = $depMgmtObj->getContentLockStatus($selContentId, $isFolder); 
                    // $userContentShareEnabled = $depMgmtObj->getContentShareEnabledStatus($selContentId, $isFolder);
                    // $userContentRemoved = $depMgmtObj->getContentRemovedStatus($selContentId, $isFolder);                        

                    $content = $depMgmtObj->getContentObject($selContentId, $isFolder);  

                    if(isset($content))
                    {
                        $updateTs = CommonFunctionClass::getCreateTimestamp();

                        $convOpParams = array();
                        $convOpParams['isChangeLogOp'] = 0;
                        $convOpParams['changeLogText'] = "";
                        $convOpParams['isDeleteOp'] = 0;
                        $convOpParams['isReplyOp'] = 1;
                        $convOpParams['replyText'] = $convText;
                        $convOpParams['isEditOp'] = 0;
                        $convOpParams['editText'] = '';
                        $convOpParams['conversationIndex'] = -1;
                        $convOpParams['updateTs'] = $updateTs;

                        $contResponse = $depMgmtObj->performContentConversationPartOperation($isFolder, $selContentId, $content, $convOpParams);

                        $status = 1;
                        $msg = 'Content Sent successfully';
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_data');
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
    
    public function profileDefaultFolderGroupDetails()
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
                $depMgmtObj->setCurrentLoginToken($loginToken);


                $fetchedDefFolderId = $depMgmtObj->getDefaultFolderId();
                $defaultFolderObj = $depMgmtObj->getFolderObject($fetchedDefFolderId);

                $defaultFolderId = 0;
                $defaultFolderName = '';
                if(isset($defaultFolderObj))
                {
                    $defaultFolderId = $fetchedDefFolderId;
                    $defaultFolderName = $defaultFolderObj->folder_name;
                }
                
                $response['defaultFolderId'] = $defaultFolderId;   
                $response['defaultFolderName'] = $defaultFolderName;   
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
     * Copy Multiple Group Content To Folder.
     *
     * @return json array
     */
    public function copyGroupContentToFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $copyToFolderId = (Input::get('copyToFolderId'));
        $groupId = (Input::get('groupId'));
        $contentId = (Input::get('contentId'));

        $response = array();

        if($encUserId != "" && $contentId != "" && $groupId != "" && $copyToFolderId != "")
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

                $copyToFolderId = sracDecryptNumberData($copyToFolderId, $userSession);
                $groupId = sracDecryptNumberData($groupId, $userSession);
                $contentId = sracDecryptNumberData($contentId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                                   
                $isFolder = TRUE;
                $isFolderForContent = FALSE;
                $content = $depMgmtObj->getContentObject($contentId, $isFolderForContent);
                $contentTags = $depMgmtObj->getContentTags($contentId, $userId, $isFolderForContent);
                $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $isFolderForContent);                   
                if(isset($content) && $content->is_removed == 0)
                {   
                    $status = 1;

                    $contentText = "";
                    if(isset($content->content) && $content->content != "")
                    {
                        try
                        {
                            $contentText = Crypt::decrypt($content->content);
                        } 
                        catch (DecryptException $e) 
                        {
                            
                        }
                    }
                    
                    $contentTypeId = $content->content_type_id;
                    $isMarked = $content->is_marked;
                    $fromTimeStamp = $content->from_timestamp;
                    $toTimeStamp = $content->to_timestamp;
                    $colorCode = $content->color_code;
                    $isLocked = $content->is_locked;
                    $isShareEnabled = $content->is_share_enabled;
                    $remindBeforeMillis = $content->remind_before_millis;
                    $repeatDuration = $content->repeat_duration;
                    $sourceId = 0;                        
                    $createTimeStamp = CommonFunctionClass::getCreateTimestamp();
                    $updateTimeStamp = $createTimeStamp;
                    $isCompleted = Config::get('app_config.default_content_is_completed_status');
                    $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
                    $reminderTimestamp = $content->reminder_timestamp;
                    
                    $contTagsArr = array();
                    foreach($contentTags as $conTag)
                    {
                        $tagId = $conTag->tag_id;
                        array_push($contTagsArr, $tagId);
                    }
                    
                    $conAddDet = $depMgmtObj->addEditContent(0, $contentText, $contentTypeId, $copyToFolderId, $sourceId, $contTagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, NULL, "");
                    
                    $newContentId = $conAddDet['syncId'];
                    $newContentAttachmentIdArr = array();

                    if(isset($contentAttachments))
                    {
                        foreach ($contentAttachments as $contentAttachment) 
                        {
                            $currFile = $contentAttachment->server_filename;
                            $filename = $contentAttachment->filename;
                            $fileSize = $contentAttachment->filesize;
                            $cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                            $cloudFileUrl = $contentAttachment->cloud_file_url;
                            $cloudFileId = $contentAttachment->cloud_file_id;
                            $cloudFileThumbStr = $contentAttachment->cloud_file_thumb_str;
                            $attCreateTs = $contentAttachment->create_ts;
                            $attUpdateTs = $contentAttachment->update_ts;
                            
                            $availableKbs = $depMgmtObj->getAvailableUserQuota($isFolder);

                            if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $fileSize))
                            {
                                if($cloudStorageTypeId > 0)
                                {
                                    $serverFileName = '';
                                }
                                else
                                {
                                    $serverFileDetails = FileUploadClass::makeAttachmentCopy($currFile, $orgId);
                                    $serverFileName = $serverFileDetails['name'];
                                }
                                
                                $attResponse = $depMgmtObj->addEditContentAttachment(0, $newContentId, $filename, $serverFileName, $fileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $updateTimeStamp);
                                if(isset($attResponse['syncId']))
                                {
                                    $attServerId = $attResponse['syncId'];  

                                    if($cloudStorageTypeId == 0)
                                    {
                                        $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $serverFileName); 
                                    }
                                    else
                                    {
                                        $attachmentUrl = $cloudFileUrl; 
                                    }  
                                      
                                    $attServerObj = array();    
                                    $attServerObj['name'] = $filename;
                                    $attServerObj['pathname'] = $serverFileName;
                                    $attServerObj['size'] = $fileSize;
                                    $attServerObj['url'] = $attachmentUrl;
                                    $attServerObj['performDownload'] = 1;
                                    $attServerObj['syncId'] = $attServerId;
                                    $attServerObj['cloudStorageTypeId'] = $cloudStorageTypeId;
                                    $attServerObj['cloudFileUrl'] = $cloudFileUrl;
                                    $attServerObj['cloudFileId'] = $cloudFileId;
                                    $attServerObj['attCreateTs'] = $attCreateTs;
                                    $attServerObj['attUpdateTs'] = $attUpdateTs;
                                    
                                    array_push($newContentAttachmentIdArr, $attServerObj);
                                }
                            }                                   
                        }
                    }

                    $depMgmtObj->recalculateUserQuota($isFolder);

                    // if($orgId > 0)
                    // {
                    //     $this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $newContentId);
                    // }
                    // else
                    // {
                    //     $this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $newContentId);
                    // }

                    $isAdd = TRUE;
                    $sharedByEmail = "";
                    $depMgmtObj->sendRespectiveContentModificationPush($isFolder, $newContentId, $isAdd, $sharedByEmail);
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
     * Toggle Content Marking.
     *
     * @return json array
     */
    public function setMultiContentCompletionStatusAsCompleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $idArr = Input::get('idArr');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);  
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId();   

                foreach($idArr as $id)
                {   
                    $userContent = $depMgmtObj->getContentObject($id, $isFolder);       
               
                    if(isset($userContent) && $userContent->is_removed == 0)
                    {
                        $status = 1;
                            
                        $existingStatus = $userContent->is_completed;
                        $newStatus = 1;
                        if($existingStatus != $newStatus)
                        {
                            $depMgmtObj->setContentIsCompletedFlag($id, $isFolder, $newStatus);
                        }
                        
                        CommonFunctionClass::setLastSyncTs($userId, $loginToken);
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_data');
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
     * Toggle Content Marking.
     *
     * @return json array
     */
    public function setMultiContentCompletionStatusAsPending()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $idArr = Input::get('idArr');

        $idArr = jsonDecodeArrStringIfRequired($idArr);

        $response = array();

        if($encUserId != "" && count($idArr) > 0)
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

                $idArr = sracDecryptNumberArrayData($idArr, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);  
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId();   

                foreach($idArr as $id)
                {
                    $userContent = $depMgmtObj->getContentObject($id, $isFolder); 

                    if(isset($userContent) && $userContent->is_removed == 0)
                    {
                        $status = 1;
                            
                        $existingStatus = $userContent->is_completed;
                        $newStatus = 0;
                        if($existingStatus != $newStatus)
                        {
                            $depMgmtObj->setContentIsCompletedFlag($id, $isFolder, $newStatus);
                        }
                        
                        CommonFunctionClass::setLastSyncTs($userId, $loginToken);
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_data');
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
     * Toggle Content Marking.
     *
     * @return json array
     */
    public function markContentReminderStatusAsSnoozed()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $id = (Input::get('id'));
        $reminderTimestamp = Input::get('reminderTimestamp');

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userContent = $depMgmtObj->getContentObject($id, $isFolder);   
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId();            
               
                if(isset($userContent) && $userContent->is_removed == 0)
                {
                    $status = 1;
                        
                    $existingStatus = $userContent->is_snoozed;

                    if(!isset($reminderTimestamp))
                    {
                        $currReminderTimestamp = $userContent->reminder_timestamp;
                        if(isset($currReminderTimestamp) && $currReminderTimestamp > 0)
                        {
                            $addOnMins = 60000 * 10; // 10 MINS
                            $reminderTimestamp = $currReminderTimestamp + $addOnMins;
                        }
                    }

                    $depMgmtObj->setContentReminderStatusAsSnoozed($id, $isFolder, $reminderTimestamp);
                    
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

    public function checkCalendarContentTimingForOverlapping()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $encConOrgId = Input::get('conOrgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $id = (Input::get('id'));
        $reminderTimestamp = Input::get('reminderTimestamp');
        $contentType = Input::get('contentType');
        $fromTimeStamp = Input::get('fromTimeStamp');
        $toTimeStamp = Input::get('toTimeStamp');

        $contentTypeIdC = Config::get('app_config.content_type_c');

        $response = array();

        if($encUserId != "" && $contentType > 0 && $contentType == $contentTypeIdC)
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

                $id = sracDecryptNumberData($id, $userSession);
            
                if(isset($isFolderFlag) && $isFolderFlag != "")
                {
                    $isFolder = FALSE;
                    if($isFolderFlag == 1)
                        $isFolder = TRUE;
                }
                else
                    $isFolder = TRUE;
                        
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encConOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);  
                $orgId = $depMgmtObj->getOrganizationId();          
                $orgEmpId = $depMgmtObj->getOrgEmployeeId(); 

                $overLappingDetails = $depMgmtObj->checkCalendarContentTimingForOverLapping($isFolder, $id, $fromTimeStamp, $toTimeStamp); 

                $status = 1;
                $response['overLappingDetails'] = $overLappingDetails;           
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