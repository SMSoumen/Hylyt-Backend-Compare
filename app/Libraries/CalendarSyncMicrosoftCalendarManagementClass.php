<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;

class CalendarSyncMicrosoftCalendarManagementClass 
{
	protected $apiKey = NULL;
	protected $accessToken = NULL;
	protected $formattedAccessToken = NULL;
	protected $cloudCalendarTypeCode = '';
	protected $cloudCalendarTypeId = 0;

	protected $baseUrl = "";
	protected $dbHeaders;

	protected $mimeTypeForDefaultCalendar = '';

	protected $definedCalendarIdForPrimary = 'primary';

	protected $eventStatusForCancelled = 'cancelled';
	protected $eventStatusForConfirmed = 'confirmed';

	protected $urlPlaceHolderCalendarId = '{{calendar-id}}';
	protected $urlPlaceHolderEventId = '{{event-id}}';
	
	public function __construct()
    {
		$this->apiKey = env('MICROSOFT_API_KEY');
    }
		
	public function setBasicDetails($typeId, $typeCode)
    {   
		$this->cloudCalendarTypeId = $typeId;
		$this->cloudCalendarTypeCode = $typeCode;
    }
		
	public function withAccessToken($accessToken)
    {   
		$this->accessToken = $accessToken;

		$sanAccessToken = $accessToken;

		$this->formattedAccessToken = "Bearer ".$sanAccessToken;
    	$this->dbHeaders = [
    		'Authorization' => $this->formattedAccessToken
		];
    }

    public function withAppKeyMapping($appKeyMapping)
    {
    	if(isset($appKeyMapping))
    	{
    		$microsoftApiKey = $appKeyMapping->microsoft_api_key;
    		$this->apiKey = $microsoftApiKey;
    	}
    }
	
	private function attachHeaders($call)
	{
		foreach($this->dbHeaders as $key => $val) {
			$call->withHeader($key.': '.$val);
		}
		return $call;
	}  

	private function makePostApiCall($url, $data)
	{		
		$call = Curl::to($url)->asJson()->withData($data);
		$call = $this->attachHeaders($call);
		
	 	$response = $call->post();
				        
		return $response;
	}

	private function makePutApiCall($url, $data)
	{		
		$call = Curl::to($url)->asJson()->withData($data);
		$call = $this->attachHeaders($call);
		
	 	$response = $call->put();
				        
		return $response;
	}

	private function makePatchApiCall($url, $data)
	{		
		$call = Curl::to($url)->asJson()->withData($data);
		$call = $this->attachHeaders($call);
		
	 	$response = $call->patch();
				        
		return $response;
	}

	private function makeGetApiCallWithHeaderData($url, $dataArr, $headerArr)
	{		
		$appendedDataStr = '';
		foreach ($dataArr as $key => $value) {

			if(gettype($value) == 'boolean')
			{
				if($value)
				{
					$reqValue = 'true';
				}
				else
				{
					$reqValue = 'false';
				}
			}
			else
			{
				$reqValue = $value;
			}
			$appendedDataStr .= $appendedDataStr != '' ? '&' : '?';
			$appendedDataStr .= $key.'='.$reqValue;
		}

		// $appendedDataStr = http_build_query($dataArr);

		$urlWithData = $url.$appendedDataStr;

		$call = Curl::to($urlWithData)->asJson();
		$call = $this->attachHeaders($call);
		foreach($headerArr as $key => $val) {
			$call->withHeader($key.': '.$val);
		}
		
	 	$response = $call->get();
				        
		return $response;
	}

	private function makeGetApiCall($url, $dataArr)
	{
		$appendedDataStr = '';
		foreach ($dataArr as $key => $value) {

			if(gettype($value) == 'boolean')
			{
				if($value)
				{
					$reqValue = 'true';
				}
				else
				{
					$reqValue = 'false';
				}
			}
			else
			{
				$reqValue = $value;
			}
			$appendedDataStr .= $appendedDataStr != '' ? '&' : '?';
			$appendedDataStr .= $key.'='.$reqValue;
		}

		// $appendedDataStr = http_build_query($dataArr);

		$urlWithData = $url.$appendedDataStr;

		$call = Curl::to($urlWithData)->asJson();
		$call = $this->attachHeaders($call);
		
	 	$response = $call->get();
				        
		return $response;
	}

    public function checkAccessTokenValidity()
    {
    	$response = array();

    	$isTokenValid = 0;

    	$calendarListResponse = $this->getAllCalendars();
    	if(isset($calendarListResponse) && isset($calendarListResponse['isTokenValid']))
    	{
    		$isTokenValid = $calendarListResponse['isTokenValid'];
    	}

    	$response['isTokenValid'] = $isTokenValid;

    	return $response;
    }
    
    public function getAllCalendars($queryStr = "")
    {
		$compiledCalendarList = array();

		$apiCallData = array();

		$data = array();

		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_list_url'); 

		$response = $this->makeGetApiCall($url, $data);

		$isTokenValid = 1;

		if(isset($response) && isset($response->value) && is_array($response->value) && count($response->value) > 0)
		{
			foreach ($response->value as $indEntry) {
				$compiledCalendarObj = $this->formulateCalendarEntry($indEntry);
				if(isset($compiledCalendarObj))
				{
					array_push($compiledCalendarList, $compiledCalendarObj);
				}
			}

			if(isset($response->nextPageToken))
			{
				$hasLoadMore = 1;
				$loadMoreCursor = $response->nextPageToken;
			}
		}

		if(isset($response) && isset($response->error) && isset($response->error->code) && $response->error->code == "InvalidAuthenticationToken")
		{
			$isTokenValid = 0;
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;
		// $apiCallData['headers'] = $this->dbHeaders;

		$calendarCount = count($compiledCalendarList);

		$compiledResponse = array();
		$compiledResponse['calendarCount'] = $calendarCount;
		$compiledResponse['calendarList'] = $compiledCalendarList;
		// $compiledResponse['apiCallData'] = $apiCallData;
		$compiledResponse['isTokenValid'] = $isTokenValid;
				        
		return $compiledResponse;
	}
    
    public function getCalendarDetails()
    {
		$compiledCalendarObj = NULL;

		$apiCallData = array();

		$data = array();

		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_list_url'); 

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response) && isset($response->id) && $response->id != "")
		{
			$compiledCalendarObj = $this->formulateCalendarEntry($response);
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$compiledResponse = array();
		$compiledResponse['calendarData'] = $compiledCalendarObj;
		// $compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}

	protected function formulateCalendarEntry($indEntry)
	{
		$compiledCalendarObj = NULL;
		if(isset($indEntry))
		{
			$indEntryId = $indEntry->id;

			$compiledCalendarObj = array();
			$compiledCalendarObj['calendarId'] = $indEntryId;
			$compiledCalendarObj['calendarName'] = $indEntry->name;
			$compiledCalendarObj['description'] = isset($indEntry->description) ? $indEntry->description : '';
			$compiledCalendarObj['isPrimary'] = (isset($indEntry->isDefaultCalendar) && $indEntry->isDefaultCalendar == true ? 1 : 0);
			// $compiledCalendarObj['timeZone'] = $indEntry->timeZone;
			$compiledCalendarObj['colorId'] = $indEntry->hexColor;
			$compiledCalendarObj['backgroundColor'] = $indEntry->hexColor;
			$compiledCalendarObj['foregroundColor'] = $indEntry->hexColor;
		}
		return $compiledCalendarObj;
	}
    
    public function getAllCalendarEvents($calendarId, $queryStr = "", $skipEntries = NULL, $syncToken = NULL, $isPrimarySync = NULL)
    {
		$compiledCalendarEventList = array();
		$hasLoadMore = FALSE;
		$loadMoreCursor = NULL;

		$hasSyncToken = FALSE;
		$retSyncToken = NULL;


		$minStartDateForCalendar = '1970-01-01T00:00:00Z';
		$maxStartDateForCalendar = '2060-12-31T23:59:59Z';

		$loadEventCount = 25;

		$nextSkipEntryCount = 0;

		$apiCallData = array();

		$headerArr = array();

		$data = array();
		// $data['showDeleted'] =  true;
		
		$data['$top'] = $loadEventCount;

		$isSyncCalendarEntriesCall = false;

		$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_url'); 
		if(isset($skipEntries) && is_numeric($skipEntries))
		{
			$data['$skip'] = $skipEntries;

			$nextSkipEntryCount = $skipEntries + $loadEventCount;
		}
		else
		{
			$nextSkipEntryCount = $loadEventCount;
		}

		if(isset($isPrimarySync) && $isPrimarySync == true)
		{
			$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_for_sync_url');

			$headerArr['Prefer'] = 'odata.maxpagesize=20'; 

			$data = array();

			if(isset($skipEntries) && !is_numeric($skipEntries) && $skipEntries != "")
			{
				$data['$skiptoken'] = $skipEntries;
			}
			else
			{
				$data['startdatetime'] = $minStartDateForCalendar;
				$data['enddatetime'] = $maxStartDateForCalendar;				
			}
		}

		if(isset($syncToken) && trim($syncToken) != "")
		{
			$isSyncCalendarEntriesCall = true;

			$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_for_sync_url'); 

			// $lastSyncDateStr = CommonFunctionClass::getLastSyncTimestampForMicrosoftCalendarSyncing();
			// $apiCallData['lastSyncDateStr'] = $lastSyncDateStr;

			$headerArr['Prefer'] = 'odata.maxpagesize=20';

			$data = array();

			if(isset($skipEntries) && !is_numeric($skipEntries) && $skipEntries != "")
			{
				$data['$skiptoken'] = $skipEntries;
			}
			else
			{
				$data['$deltatoken'] = $syncToken;

				$data['startdatetime'] = $minStartDateForCalendar;
				$data['enddatetime'] = $maxStartDateForCalendar;				
			}

			// $data['startDateTime'] = trim($lastSyncedATDateTime);
			// $data['endDateTime'] = trim($lastSyncDateStr);
		}

		if(!isset($calendarId) || trim($calendarId) == "")
		{
			$calendarListResponse = $this->getAllCalendars();
			if(isset($calendarListResponse))
			{
				$calendarList = $calendarListResponse['calendarList'];
				foreach ($calendarList as $calendarObj) {
					if($calendarObj['isPrimary'] == 1)
					{
						$calendarId = $calendarObj['calendarId'];
						break;
					}
				}
			}
			$apiCallData['calendarListResponse'] = $calendarListResponse;
		}

		$sanCalendarId = urlencode($calendarId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);

		$response = $this->makeGetApiCallWithHeaderData($url, $data, $headerArr);

		if(isset($response) && isset($response->value) && is_array($response->value))
		{
			if(count($response->value) > 0)
			{
				foreach ($response->value as $indEntry) {
					$compiledCalendarEventObj = $this->formulateCalendarEventEntry($indEntry);
					if(isset($compiledCalendarEventObj))
					{
						array_push($compiledCalendarEventList, $compiledCalendarEventObj);
					}
				}
			}

			if(isset($response->{'@odata.nextLink'}))
			{
				$nextLinkUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_for_sync_url'); 

				$retNextLink = $response->{'@odata.nextLink'};

				$sanNextToken = str_replace($nextLinkUrl, "", $retNextLink);

				$sanNextToken = str_replace('$skiptoken=', "", $sanNextToken);
				$sanNextToken = str_replace("?", "", $sanNextToken);

				$hasLoadMore = true;
				$loadMoreCursor = $sanNextToken;
			}

			if(isset($response->{'@odata.deltaLink'}))
			{
				$deltaLinkUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_for_sync_url'); 

				$retDeltaLink = $response->{'@odata.deltaLink'};

				$sanDeltaToken = str_replace($deltaLinkUrl, "", $retDeltaLink);
				$sanDeltaToken = str_replace('$deltatoken=', "", $sanDeltaToken);
				$sanDeltaToken = str_replace("?", "", $sanDeltaToken);

				$hasSyncToken = true;
				$retSyncToken = $sanDeltaToken;
			}

			// if(isset($response->nextSyncToken))
			// {
			// 	$hasSyncToken = true;
			// 	$retSyncToken = $response->nextSyncToken;
			// }
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;
		$apiCallData['headers'] = $this->dbHeaders;


		$eventCount = count($compiledCalendarEventList);

		$compiledResponse = array();
		$compiledResponse['eventCount'] = $eventCount;
		$compiledResponse['eventList'] = $compiledCalendarEventList;
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['hasSyncToken'] = $hasSyncToken;
		$compiledResponse['retSyncToken'] = $retSyncToken;
		$compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
    
    public function getCalendarEventDetails($calendarId, $eventId)
    {
		Log::info('getCalendarEventDetails : calendarId : '.$calendarId.' : eventId : '.$eventId);

		$compiledCalendarEventObj = NULL;

		$apiCallData = array();

		$data = array();

		$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_details_url'); 

		$sanCalendarId = urlencode($calendarId);
		$sanEventId = urlencode($eventId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);
		$url = str_replace($this->urlPlaceHolderEventId, $sanEventId, $url);

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response->importance) && isset($response->id))
		{
			$indEntry = $response;
			$compiledCalendarEventObj = $this->formulateCalendarEventEntry($indEntry);

			Log::info('getCalendarEventDetails : response : ');
			Log::info($compiledCalendarEventObj);
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$compiledResponse = array();
		$compiledResponse['eventDetails'] = $compiledCalendarEventObj;
		$compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}

	protected function formulateCalendarEventEntry($indEntry)
	{
		$utcTz =  'UTC';

		$compiledEventObj = NULL;
		if(isset($indEntry) && isset($indEntry->id))// && isset($indEntry->{'@odata.type'}))
		{
			$indEntryId = $indEntry->id;
			$eventStatusIsCancelled = isset($indEntry->isCancelled) ? $indEntry->isCancelled : 0;
			$eventStatusIsDraft = isset($indEntry->isDraft) ? $indEntry->isDraft : 0;

			if(isset($indEntry->{'@removed'}))
			{
				$eventStatusIsCancelled = true;
			}

			$isCancelledEvent = 0;
			$isConfirmedEvent = 0;

			if($eventStatusIsCancelled == true)
			{
				$isCancelledEvent = 1;
			}
			
			if($eventStatusIsDraft == false)
			{
				$isConfirmedEvent = 1;
			}

			$compiledEventObj = array();
			$compiledEventObj['eventId'] = $indEntryId;
			$compiledEventObj['isCancelled'] = $isCancelledEvent;
			$compiledEventObj['isConfirmed'] = $isConfirmedEvent;

			if($isCancelledEvent == 0 && isset($indEntry->subject))
			{
				$startObj = $indEntry->start;
				$endObj = $indEntry->end;

				$startTs = NULL;
				if(isset($startObj))
				{
					if(isset($startObj->date))
					{
						$consStartDateObj = new Carbon($startObj->date);//Carbon::createFromFormat('Y-m-d', $startObj->date);
					}
					else if(isset($startObj->dateTime))
					{
						$consStartDateObj = new Carbon($startObj->dateTime);//Carbon::createFromFormat('Y-m-d\TH:i:sZ', $startObj->dateTime);
						// $startObj->timeZone
					}

					if(isset($consStartDateObj))
					{
				        $startTs = $consStartDateObj->timestamp;                   
				        $startTs = $startTs * 1000;
					}
				}

				$endTs = NULL;
				if(isset($endObj))
				{
					if(isset($endObj->date))
					{
						$consEndDateObj = new Carbon($endObj->date);
					}
					else if(isset($endObj->dateTime))
					{
						$consEndDateObj = new Carbon($endObj->dateTime);
					}

					if(isset($consEndDateObj))
					{
				        $endTs = $consEndDateObj->timestamp;                   
				        $endTs = $endTs * 1000;
					}
				}

				$compiledEventObj['htmlLink'] = isset($indEntry->webLink) ? $indEntry->webLink : '';
				$compiledEventObj['created'] = isset($indEntry->createdDateTime) ? $indEntry->createdDateTime : '';
				$compiledEventObj['updated'] = isset($indEntry->lastModifiedDateTime) ? $indEntry->lastModifiedDateTime : '';
				$compiledEventObj['summary'] = $indEntry->subject;
				$compiledEventObj['description'] = isset($indEntry->description) ? $indEntry->description : '';
				$compiledEventObj['isSelfCreator'] = (isset($indEntry->isOrganizer) && $indEntry->isOrganizer == true ? 1 : 0);
				$compiledEventObj['isSelfOrganizer'] = (isset($indEntry->isOrganizer) && $indEntry->isOrganizer == true ? 1 : 0);
				$compiledEventObj['isAllDay'] = (isset($indEntry->isAllDay) && $indEntry->isAllDay == true ? 1 : 0);
				$compiledEventObj['startObj'] = $startObj;
				$compiledEventObj['endObj'] = $endObj;
				$compiledEventObj['startTs'] = $startTs;
				$compiledEventObj['endTs'] = $endTs;
				$compiledEventObj['visibility'] = isset($indEntry->visibility) ? $indEntry->visibility : "";
			}				
		}
		return $compiledEventObj;
	}
    
    public function checkEventCanBeDeleted($calendarId, $eventId)
    {
		$canBeDeleted = $this->checkEventCanBeDeletedBoolean($calendarId, $eventId);

		$canBeDeletedFlag = 0;
		$validationMsg = '';
		if($canBeDeleted)
		{
			$canBeDeletedFlag = 1;
			$validationMsg = '';
		}
		else
		{
			$canBeDeletedFlag = 0;
			$validationMsg = 'Event not owned by you. Cannot be deleted.';
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeletedFlag;
		$compiledResponse['validationMsg'] = $validationMsg;
				        
		return $compiledResponse;
	}
    
    private function checkEventCanBeDeletedBoolean($calendarId, $eventId)
    {
		$canBeDeleted = true;

		$detailsResponse = $this->getCalendarEventDetails($calendarId, $eventId);

		if(isset($detailsResponse) && isset($detailsResponse['eventDetails']))
		{
			if($detailsResponse['eventDetails']['isSelfCreator'] == 0)
			{
				$canBeDeleted = false;
			}
		}
		else
		{
			$canBeDeleted = false;
		}
				        
		return $canBeDeleted;
	}
    
    public function performEventDelete($calendarId, $eventId)
    {
		$response = NULL;

		$isDeleted = 0;

		$apiCallData = array();

		$canBeDeleted = $this->checkEventCanBeDeletedBoolean($calendarId, $eventId);
		if($canBeDeleted)
		{
			$data = array();

			$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_delete_calendar_event_url'); 

			$sanCalendarId = urlencode($calendarId);
			$sanEventId = urlencode($eventId);

			$url = $baseUrl;
			$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);
			$url = str_replace($this->urlPlaceHolderEventId, $sanEventId, $url);

			$call = Curl::to($url);
			$call = $this->attachHeaders($call);
			
		 	$response = $call->delete();

			$apiCallData['url'] = $url;
			$apiCallData['data'] = $data;
			$apiCallData['response'] = $response;

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;
		$compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
    
    public function addNewEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description)
    {
		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_create_calendar_event_url');

		$startDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($startTs, $tzOfs);
		$endDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($endTs, $tzOfs);

		// Log::info('startTs : '.$startTs.' : tzOfs : '.$tzOfs.' : startDateTimeStr : '.$startDateTimeStr);
		// Log::info('endTs : '.$endTs.' : tzOfs : '.$tzOfs.' : endDateTimeStr : '.$endDateTimeStr);

		Log::info('addNewEvent : startTs : '.$startTs.' : tzOfs : '.$tzOfs.' : startDateTimeStr : '.$startDateTimeStr);
		Log::info('endTs : '.$endTs.' : tzOfs : '.$tzOfs.' : endDateTimeStr : '.$endDateTimeStr);

		$eventId = '';

		$data = array();
		// $data['id'] = $eventId;
		$data['allowNewTimeProposals'] = true;
		$data['subject'] = $summary;
		$data['body'] = ([ "contentType" => "html", "content" => "" ]);
		$data['start'] = ([ "dateTime" => $startDateTimeStr, "timeZone" => "UTC" ]);
		$data['end'] = ([ "dateTime" => $endDateTimeStr, "timeZone" => "UTC" ]);

		$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_create_calendar_event_url'); 

		$sanCalendarId = urlencode($calendarId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);

		
		$response = $this->makePostApiCall($url, $data);


		$syncEventId = '';
		$isSyncEventIdDiff = 0;
		if(isset($response) && isset($response->id))
		{
			$syncEventId = $response->id;
			if($syncEventId != $eventId)
			{
				$isSyncEventIdDiff = 1;
			}
		}	

		$compiledResponse = array();
		$compiledResponse['syncEventId'] = $syncEventId;
		$compiledResponse['isSyncEventIdDiff'] = $isSyncEventIdDiff;

		$compiledResponse['url'] = $url;
		$compiledResponse['apiResponse'] = $response;
		$compiledResponse['apiRequest'] = $data;
				        
		return $compiledResponse;
	}
    
    public function updateExistingEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description)
    {
    	$eventDetailsResponse = $this->getCalendarEventDetails($calendarId, $eventId);

    	if(isset($eventDetailsResponse) && isset($eventDetailsResponse['eventDetails']))
    	{
    		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_update_calendar_event_url');

			$startDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($startTs, $tzOfs);
			$endDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($endTs, $tzOfs);

			Log::info('updateExistingEvent : startTs : '.$startTs.' : tzOfs : '.$tzOfs.' : startDateTimeStr : '.$startDateTimeStr);
			Log::info('endTs : '.$endTs.' : tzOfs : '.$tzOfs.' : endDateTimeStr : '.$endDateTimeStr);

			$data = array();
			$data['allowNewTimeProposals'] = true;
			$data['subject'] = $summary;
			$data['body'] = ([ "contentType" => "html", "content" => "" ]);
			$data['start'] = ([ "dateTime" => $startDateTimeStr, "timeZone" => "UTC" ]);
			$data['end'] = ([ "dateTime" => $endDateTimeStr, "timeZone" => "UTC" ]);

			$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_update_calendar_event_url'); 

			$sanCalendarId = urlencode($calendarId);
			$sanEventId = urlencode($eventId);

			$url = $baseUrl;
			$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);
			$url = str_replace($this->urlPlaceHolderEventId, $sanEventId, $url);
			
			$response = $this->makePatchApiCall($url, $data);

			$syncEventId = '';
			$isSyncEventIdDiff = 0;
			if(isset($response) && isset($response->id))
			{
				$syncEventId = $response->id;
				if($syncEventId != $eventId)
				{
					$isSyncEventIdDiff = 1;
				}
			}	

			$compiledResponse = array();
			$compiledResponse['syncEventId'] = $syncEventId;
			$compiledResponse['isSyncEventIdDiff'] = $isSyncEventIdDiff;

			$compiledResponse['url'] = $url;
			$compiledResponse['apiResponse'] = $response;
			$compiledResponse['apiRequest'] = $data;
					        
			return $compiledResponse;
    	}
		else
		{
			$compiledResponse = $this->getCalendarEventDetails($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
					        
			return $compiledResponse;
		}	
	}
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
    	$consFilePath = '';

		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

    	$mappedDetailsArr = array();

		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_file_details_url');

		$data = array();
		$data['key'] = $this->apiKey;
		$data['fields'] = 'id,name,iconLink,webViewLink,webContentLink,size,thumbnailLink,mimeType,viewersCanCopyContent,starred';

		$compFileIdArr = array();
		$fileDetailResponseArr = array();
		foreach ($fileIdArr as $fileId) {
			array_push($compFileIdArr, $fileId);

			$urlWithIdAppended = $url.'/'.$fileId;

			$fileDetailResponse = $this->makeGetApiCall($urlWithIdAppended, $data);

			$fileDetailResponseArr[$fileId] = $fileDetailResponse;
		}

		if(isset($fileDetailResponseArr) && isset($fileDetailResponseArr) && is_array($fileDetailResponseArr) && count($fileDetailResponseArr) > 0)
		{
			$fileResponseArr = $fileDetailResponseArr;
			foreach ($fileResponseArr as $fileResponse)
			{
				if(isset($fileResponse) && isset($fileResponse->name))
				{
					$compiledFileDetailsObj = $this->formulateFolderOrFileEntry($fileResponse, $consFilePath);
					$compiledFileDetailsObj['fileCalendarUrl'] = $fileResponse->webViewLink;
					$compiledFileDetailsObj['cloudCalendarTypeCode'] = $this->cloudCalendarTypeCode;
					$compiledFileDetailsObj['cloudCalendarTypeId'] = $this->cloudCalendarTypeId;

					array_push($mappedDetailsArr, $compiledFileDetailsObj);
				}
					
			}
		}

		$filesRequested = count($compFileIdArr);
		$filesFetched = count($mappedDetailsArr);
		$filesNotFetched = $filesRequested - $filesFetched;
		$allFileDetailsFetched = $filesNotFetched == 0 ? 1 : 0;

		$fileNotFetchedErrorMsg = "";
		if($allFileDetailsFetched == 0)
		{
			if($filesFetched > 0)
			{
				$fileNotFetchedErrorMsg = $filesNotFetched." of ".$filesRequested." file(s) could not be linked";
			}
			else
			{
				$fileNotFetchedErrorMsg = "All of the selected ".$filesRequested." file(s) could not be linked";
			}
		}
		
		$compiledResponse = array();
		// $compiledResponse['fileDetailResponseArr'] = $fileDetailResponseArr;
		$compiledResponse['mappedDetailsArr'] = $mappedDetailsArr;
		$compiledResponse['allFileDetailsFetched'] = $allFileDetailsFetched;
		$compiledResponse['fileNotFetchedErrorMsg'] = $fileNotFetchedErrorMsg;
				        
		return $compiledResponse;
	}

	public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
	{
		$response = NULL;

		$url = Config::get('app_config_cloud_calendar.microsoft_calendar_api_refresh_access_token_url');

		$dataStr  = 'client_id=' . $consClientId;
		$dataStr .= '&scope=' . urlencode('https://graph.microsoft.com/calendars.readwrite');
		$dataStr .= '&grant_type=' . 'refresh_token';
		$dataStr .= '&refresh_token=' . urlencode($refreshToken);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $dataStr,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		$baseResponse = curl_exec($curl);

		curl_close($curl);

		if(isset($baseResponse) && trim($baseResponse) != "")
		{
			$response = json_decode($baseResponse);
		}
				        
		return $response;
	}

	private function sanitizeCompiledFileFolderPath($compPath)
	{
		$sanPath = '';
		if(isset($compPath) && $compPath != '')
		{
			$sanPath = $compPath;
			$sanPath = str_replace("//", "/", $sanPath);
		}
		return $sanPath;
	}
    
    public function getCalendarEventsForPeriodicSync($calendarId, $syncToken = NULL, $cursorStr = NULL)
    {
		$compiledCalendarEventList = array();
		$hasLoadMore = FALSE;
		$loadMoreCursor = NULL;

		$apiCallData = array();

		$data = array();

		$baseUrl = Config::get('app_config_cloud_calendar.microsoft_calendar_api_load_calendar_event_list_url'); 
		if(isset($cursorStr) && trim($cursorStr) != "")
		{
			$data['pageToken'] = trim($cursorStr);
		}

		if(isset($syncToken) && trim($syncToken) != "")
		{
			$data['syncToken'] = trim($syncToken);
		}

		$sanCalendarId = urlencode($calendarId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderCalendarId, $sanCalendarId, $url);

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response->items) && is_array($response->items) && count($response->items) > 0)
		{
			foreach ($response->items as $indEntry) {
				$compiledCalendarEventObj = $this->formulateCalendarEventEntry($indEntry);
				if(isset($compiledCalendarEventObj))
				{
					array_push($compiledCalendarEventList, $compiledCalendarEventObj);
				}
			}

			if(isset($response->nextPageToken))
			{
				$hasLoadMore = true;
				$loadMoreCursor = $response->nextPageToken;
			}
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$eventCount = count($compiledCalendarEventList);

		$compiledResponse = array();
		$compiledResponse['eventCount'] = $eventCount;
		$compiledResponse['eventList'] = $compiledCalendarEventList;
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		// $compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
}