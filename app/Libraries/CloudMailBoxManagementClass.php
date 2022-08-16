<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\Api\CloudMailBoxType;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Org\OrganizationUser;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
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
use App\Libraries\FileUploadClass;
use App\Libraries\MailClass;
use App\Libraries\MailBoxSyncGoogleMailManagementClass;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class CloudMailBoxManagementClass 
{
    protected $appKeyMapping = NULL;
    protected $accessToken = NULL;
    protected $mailBoxId = NULL;
    protected $cloudMailBoxTypeCode = NULL;
    protected $cloudMailBoxTypeId = NULL;

    protected $GOOGLE_MAILBOX_TYPE_CODE = '';
    protected $MICROSOFT_MAILBOX_TYPE_CODE = '';

    protected $cloudMailBoxIsGoogleMailBox = FALSE;
    protected $googleMailBoxMgmtObj = NULL;

    protected $cloudMailBoxIsMicrosoftMailBox = FALSE;
    protected $microsoftMailBoxMgmtObj = NULL;
        
    public function __construct()
    {
        $this->GOOGLE_MAILBOX_TYPE_CODE = CloudMailBoxType::$GOOGLE_MAILBOX_TYPE_CODE;
        $this->MICROSOFT_MAILBOX_TYPE_CODE = CloudMailBoxType::$MICROSOFT_MAILBOX_TYPE_CODE;
    }
        
    public function withAppKey($encAppKey)
    {
        Log::info('withAppKey : encAppKey : '.$encAppKey);
        if(isset($encAppKey) && trim($encAppKey) != "")
        {
            $encAppKey = trim($encAppKey);

            $decAppKey = $encAppKey;

            $mappedAppKey = AppKeyMapping::active()->byAppKey($decAppKey)->first();
            if(isset($mappedAppKey))
            {
                $this->appKeyMapping = $mappedAppKey;
            }
        }
    }
        
    public function withMailBoxTypeCode($mailBoxTypeCode)
    {
        if(isset($mailBoxTypeCode) && trim($mailBoxTypeCode) != "")
        {
            $cloudMailBoxType = CloudMailBoxType::byCode($mailBoxTypeCode)->first();
            if(isset($cloudMailBoxType))
            {
                $this->setupCloudDependency($cloudMailBoxType);
            }
        }
    }
        
    public function withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $mailBoxTypeCode)
    {
        $this->accessToken = $accessToken;
        $this->mailBoxId = $mailBoxId;

        if(isset($mailBoxTypeCode) && trim($mailBoxTypeCode) != "")
        {
            $cloudMailBoxType = CloudMailBoxType::byCode($mailBoxTypeCode)->first();
            Log::info('cloudMailBoxType : ');
            Log::info($cloudMailBoxType);
            if(isset($cloudMailBoxType))
            {
                $this->setupCloudDependency($cloudMailBoxType);
            }
        }
    }
        
    public function withAccessTokenAndMailBoxTypeObject($accessToken, $mailBoxId, $cloudMailBoxType)
    {
        $this->accessToken = $accessToken;
        $this->mailBoxId = $mailBoxId;
        $this->setupCloudDependency($cloudMailBoxType);
    }

    private function setupCloudDependency($cloudMailBoxType)
    {
        if(isset($cloudMailBoxType))
        {
            $this->cloudMailBoxTypeId = $cloudMailBoxType->cloud_mail_box_type_id;
            $this->cloudMailBoxTypeCode = $cloudMailBoxType->cloud_mail_box_type_code;

            if($this->cloudMailBoxTypeCode == $this->GOOGLE_MAILBOX_TYPE_CODE)
            {
                $this->forGoogleMailBox();
            }
            else if($this->cloudMailBoxTypeCode == $this->MICROSOFT_MAILBOX_TYPE_CODE)
            {
                $this->forMicrosoftMailBox();
            }
        }
    }
        
    public function forGoogleMailBox()
    {   
        $this->cloudMailBoxIsGoogleMailBox = TRUE;
        $this->googleMailBoxMgmtObj = New MailBoxSyncGoogleMailManagementClass;
        $this->googleMailBoxMgmtObj->setBasicDetails($this->cloudMailBoxTypeId, $this->cloudMailBoxTypeCode);
        if(isset($this->accessToken) && $this->accessToken != '')
        {
            $this->googleMailBoxMgmtObj->withAccessToken($this->accessToken);
        }
        if(isset($this->mailBoxId) && $this->mailBoxId != '')
        {
            $this->googleMailBoxMgmtObj->withMailBoxId($this->mailBoxId);
        }
        if(isset($this->appKeyMapping))
        {
            Log::info('forGoogleMailBox appKeyMapping exists : ');
            $this->googleMailBoxMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
        else
        {
            Log::info('forGoogleMailBox appKeyMapping does not exist : ');
        }
    }
        
    public function forMicrosoftMailBox()
    {   
        $this->cloudMailBoxIsMicrosoftMailBox = TRUE;
        $this->microsoftMailBoxMgmtObj = New MailBoxSyncMicrosoftMailManagementClass;
        $this->microsoftMailBoxMgmtObj->setBasicDetails($this->cloudMailBoxTypeId, $this->cloudMailBoxTypeCode);
        if(isset($this->accessToken) && $this->accessToken != '')
        {
            $this->microsoftMailBoxMgmtObj->withAccessToken($this->accessToken);
        }
        if(isset($this->mailBoxId) && $this->mailBoxId != '')
        {
            $this->microsoftMailBoxMgmtObj->withMailBoxId($this->mailBoxId);
        }
        if(isset($this->appKeyMapping))
        {
            Log::info('forMicrosoftMailBox appKeyMapping exists : ');
            $this->microsoftMailBoxMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
        else
        {
            Log::info('forMicrosoftMailBox appKeyMapping does not exist : ');
        }
    }
    
    public function fetchAccessToken($sessionCode)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->fetchAccessToken($sessionCode);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->fetchAccessToken($sessionCode);
        }
        return $response;
    }

    public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        return $response;
    }

    public function checkAccessTokenValidity()
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->checkAccessTokenValidity();
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->checkAccessTokenValidity();
        }
        return $response;
    }
    
    public function getAllMailBoxMessages($queryStr = "", $cursorStr = NULL)
    {
        Log::info('getAllMailBoxMessages : queryStr : '.$queryStr);
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->getAllMailBoxMessages($queryStr, $cursorStr);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->getAllMailBoxMessages($queryStr, $cursorStr);
        }
        return $response;
    } 
    
    public function getMailBoxMessageDetails($messageId)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->getMailBoxMessageDetails($messageId);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->getMailBoxMessageDetails($messageId);
        }
        return $response;
    } 
    
    public function getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType);
        }
        return $response;
    } 
    
    public function checkMessageCanBeDeleted($messageId)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->checkMessageCanBeDeleted($messageId);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->checkMessageCanBeDeleted($messageId);
        }
        return $response;
    }
    
    public function performMessageDelete($messageId)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->performMessageDelete($messageId);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->performMessageDelete($messageId);
        }
        return $response;
    }
    
    public function addNewMessage($subject, $snippet, $sendToEmail, $sendCcEmail, $sendBccEmail)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->addNewMessage($subject, $snippet, $sendToEmail, $sendCcEmail, $sendBccEmail);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->addNewMessage($subject, $snippet, $sendToEmail, $sendCcEmail, $sendBccEmail);
        }
        return $response;
    }
    
    public function updateExistingMessage($tzOfs, $messageId, $startTs, $endTs, $summary, $description)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->updateExistingMessage($tzOfs, $messageId, $startTs, $endTs, $summary, $description);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->updateExistingMessage($tzOfs, $messageId, $startTs, $endTs, $summary, $description);
        }
        return $response;
    }
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
        $response = NULL;
        if($this->cloudMailBoxIsGoogleMailBox)
        {
            $response = $this->googleMailBoxMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        else if($this->cloudMailBoxIsMicrosoftMailBox)
        {
            $response = $this->microsoftMailBoxMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        return $response;
    }
    
    
}