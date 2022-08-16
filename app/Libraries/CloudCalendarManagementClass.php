<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\Api\CloudCalendarType;
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
use App\Libraries\CalendarSyncGoogleCalendarManagementClass;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class CloudCalendarManagementClass 
{
    protected $appKeyMapping = NULL;
	protected $accessToken = NULL;
	protected $cloudCalendarTypeCode = NULL;
	protected $cloudCalendarTypeId = NULL;

	protected $GOOGLE_CALENDAR_TYPE_CODE = '';
    protected $MICROSOFT_CALENDAR_TYPE_CODE = '';

	protected $cloudCalendarIsGoogleCalendar = FALSE;
	protected $googleCalendarMgmtObj = NULL;

    protected $cloudCalendarIsMicrosoftCalendar = FALSE;
    protected $microsoftCalendarMgmtObj = NULL;
		
	public function __construct()
    {
    	$this->GOOGLE_CALENDAR_TYPE_CODE = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
        $this->MICROSOFT_CALENDAR_TYPE_CODE = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;
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
        
    public function withCalendarTypeCode($calendarTypeCode)
    {
        if(isset($calendarTypeCode) && trim($calendarTypeCode) != "")
        {
            $cloudCalendarType = CloudCalendarType::byCode($calendarTypeCode)->first();
            if(isset($cloudCalendarType))
            {
                $this->setupCloudDependency($cloudCalendarType);
            }
        }
    }
		
	public function withAccessTokenAndCalendarTypeCode($accessToken, $calendarTypeCode)
    {
		$this->accessToken = $accessToken;

		if(isset($calendarTypeCode) && trim($calendarTypeCode) != "")
		{
			$cloudCalendarType = CloudCalendarType::byCode($calendarTypeCode)->first();
			if(isset($cloudCalendarType))
			{
                $this->setupCloudDependency($cloudCalendarType);
			}
		}
    }
        
    public function withAccessTokenAndCalendarTypeObject($accessToken, $cloudCalendarType)
    {
        $this->accessToken = $accessToken;
        $this->setupCloudDependency($cloudCalendarType);
    }

    private function setupCloudDependency($cloudCalendarType)
    {
        if(isset($cloudCalendarType))
        {
            $this->cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;
            $this->cloudCalendarTypeCode = $cloudCalendarType->cloud_calendar_type_code;

            if($this->cloudCalendarTypeCode == $this->GOOGLE_CALENDAR_TYPE_CODE)
            {
                $this->forGoogleCalendar();
            }
            else if($this->cloudCalendarTypeCode == $this->MICROSOFT_CALENDAR_TYPE_CODE)
            {
                $this->forMicrosoftCalendar();
            }
        }
    }
		
	public function forGoogleCalendar()
    {   
		$this->cloudCalendarIsGoogleCalendar = TRUE;
        $this->googleCalendarMgmtObj = New CalendarSyncGoogleCalendarManagementClass;
        $this->googleCalendarMgmtObj->setBasicDetails($this->cloudCalendarTypeId, $this->cloudCalendarTypeCode);
		if(isset($this->accessToken) && $this->accessToken != '')
		{
			$this->googleCalendarMgmtObj->withAccessToken($this->accessToken);
		}
        if(isset($this->appKeyMapping))
        {
            Log::info('forGoogleCalendar appKeyMapping exists : ');
            $this->googleCalendarMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
        else
        {
            Log::info('forGoogleCalendar appKeyMapping does not exist : ');
        }
    }
        
    public function forMicrosoftCalendar()
    {   
        $this->cloudCalendarIsMicrosoftCalendar = TRUE;
        $this->microsoftCalendarMgmtObj = New CalendarSyncMicrosoftCalendarManagementClass;
        $this->microsoftCalendarMgmtObj->setBasicDetails($this->cloudCalendarTypeId, $this->cloudCalendarTypeCode);
        if(isset($this->accessToken) && $this->accessToken != '')
        {
            $this->microsoftCalendarMgmtObj->withAccessToken($this->accessToken);
        }
        if(isset($this->appKeyMapping))
        {
            Log::info('forMicrosoftCalendar appKeyMapping exists : ');
            $this->microsoftCalendarMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
        else
        {
            Log::info('forMicrosoftCalendar appKeyMapping does not exist : ');
        }
    }
    
    public function fetchAccessToken($sessionCode)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->fetchAccessToken($sessionCode);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->fetchAccessToken($sessionCode);
        }
        return $response;
    }

    public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        return $response;
    }

    public function checkAccessTokenValidity()
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->checkAccessTokenValidity();
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->checkAccessTokenValidity();
        }
        return $response;
    }
    
    public function getAllCalendars($queryStr)
    {
    	$response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
    	{
    		$response = $this->googleCalendarMgmtObj->getAllCalendars($queryStr);
    	}
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->getAllCalendars($queryStr);
        }
    	return $response;
    }  
    
    public function getAllCalendarEvents($calendarId, $queryStr = "", $cursorStr = NULL, $syncToken = NULL, $isPrimarySync = NULL)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->getAllCalendarEvents($calendarId, $queryStr, $cursorStr, $syncToken, $isPrimarySync);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->getAllCalendarEvents($calendarId, $queryStr, $cursorStr, $syncToken, $isPrimarySync);
        }
        return $response;
    } 
    
    public function getCalendarEventDetails($calendarId, $eventId)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->getCalendarEventDetails($calendarId, $eventId);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->getCalendarEventDetails($calendarId, $eventId);
        }
        return $response;
    } 
    
    public function checkEventCanBeDeleted($calendarId, $eventId)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->checkEventCanBeDeleted($calendarId, $eventId);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->checkEventCanBeDeleted($calendarId, $eventId);
        }
        return $response;
    }
    
    public function performEventDelete($calendarId, $eventId)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->performEventDelete($calendarId, $eventId);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->performEventDelete($calendarId, $eventId);
        }
        return $response;
    }
    
    public function addNewEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->addNewEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->addNewEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
        }
        return $response;
    }
    
    public function updateExistingEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->updateExistingEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->updateExistingEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
        }
        return $response;
    }
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
        $response = NULL;
        if($this->cloudCalendarIsGoogleCalendar)
        {
            $response = $this->googleCalendarMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        else if($this->cloudCalendarIsMicrosoftCalendar)
        {
            $response = $this->microsoftCalendarMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        return $response;
    }
    
    
}