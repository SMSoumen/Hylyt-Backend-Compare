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

class MailBoxSyncGoogleMailManagementClass 
{
	protected $apiKey = NULL;
	protected $accessToken = NULL;
	protected $formattedAccessToken = NULL;
	protected $mailBoxId = NULL;
	protected $sanMailBoxId = NULL;
	protected $cloudMailBoxTypeCode = '';
	protected $cloudMailBoxTypeId = 0;

	protected $maximumMessageCount = 25;

	protected $baseUrl = "";
	protected $dbHeaders;

	protected $mimeTypeForDefaultMailBox = 'group.v.mailBox.google.com';

	protected $definedMailBoxIdForPrimary = 'primary';

	protected $messageStatusForCancelled = 'cancelled';
	protected $messageStatusForConfirmed = 'confirmed';

	protected $urlPlaceHolderMailBoxId = '{{email-str}}';
	protected $urlPlaceHolderMessageId = '{{message-id}}';
	protected $urlPlaceHolderAttachmentId = '{{attachment-id}}';
	
	public function __construct()
    {
		$this->apiKey = env('GOOGLE_API_KEY');
    }
		
	public function setBasicDetails($typeId, $typeCode)
    {   
		$this->cloudMailBoxTypeId = $typeId;
		$this->cloudMailBoxTypeCode = $typeCode;
    }
		
	public function withAccessToken($accessToken)
    {
		$this->accessToken = $accessToken;
		$this->formattedAccessToken = "Bearer ".$accessToken;
    	$this->dbHeaders = [
    		'Authorization' => $this->formattedAccessToken
		];
    }
		
	public function withMailBoxId($mailBoxId)
    {
		$this->mailBoxId = $mailBoxId;
		$this->sanMailBoxId = ($mailBoxId); // urlencode
    }

    public function withAppKeyMapping($appKeyMapping)
    {
    	if(isset($appKeyMapping))
    	{
    		$googleApiKey = $appKeyMapping->google_api_key;
    		$this->apiKey = $googleApiKey;
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

    	$mailBoxListResponse = $this->getAllMailBoxMessages();
    	if(isset($mailBoxListResponse) && isset($mailBoxListResponse['isTokenValid']))
    	{
    		$isTokenValid = $mailBoxListResponse['isTokenValid'];
    	}

    	$response['isTokenValid'] = $isTokenValid;

    	return $response;
    }
    
    public function getAllMailBoxMessages($queryStr = "", $cursorStr = NULL)
    {
		$compiledMailBoxMessageList = array();
		$hasLoadMore = FALSE;
		$loadMoreCursor = NULL;

		$isTokenValid = 0;

		$apiCallData = array();

		$data = array();
		$data['maxResults'] = $this->maximumMessageCount;
		// $data['showDeleted'] =  true;

		$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_load_mail_box_message_list_url'); 
		if(isset($cursorStr) && trim($cursorStr) != "")
		{
			$data['pageToken'] = trim($cursorStr);
		}
		if(isset($queryStr) && trim($queryStr) != "")
		{
			$data['q'] = trim($queryStr);
		}

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);

		$response = $this->makeGetApiCall($url, $data);

    	$messageIdArr = array();

		if(isset($response) && isset($response->messages) && is_array($response->messages))
		{
			$isTokenValid = 1;

			if(count($response->messages) > 0)
			{
				foreach ($response->messages as $indEntry) {
					array_push($messageIdArr, $indEntry->id);
					// $compiledMailBoxMessageObj = $this->formulateMailBoxMessageEntry($indEntry);
					// if(isset($compiledMailBoxMessageObj))
					// {
					// 	array_push($compiledMailBoxMessageList, $compiledMailBoxMessageObj);
					// }
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

		if(count($messageIdArr) > 0)
		{
			$mappedDetailsArr = $this->getSelectedMessageMappedDetailsArr($messageIdArr);

			$compiledMailBoxMessageList = $mappedDetailsArr;
		}

		$messageCount = count($compiledMailBoxMessageList);

		$compiledResponse = array();
		$compiledResponse['isTokenValid'] = $isTokenValid;
		$compiledResponse['messageCount'] = $messageCount;
		$compiledResponse['messageList'] = $compiledMailBoxMessageList;
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;

		// $compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}

    public function getSelectedMessageMappedDetailsArr($messageIdArr)
    {
    	$consMessagePath = '';

    	$mappedDetailsArr = array();

		$url = 'https://www.googleapis.com/batch/gmail/v1';

		$reqLineBreakStr = "\r\n";
		$reqContentTypeStr = 'Content-Type: application/http';

		$reqSepStr = 'hylyt_det_fetch';
		$reqSepPrefix = '--';
		$reqSepStrWPrefix = $reqSepPrefix . $reqSepStr;

		$reqHeaderData = array();
		array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
		array_push($reqHeaderData, 'Content-Type: multipart/mixed;boundary="'.$reqSepStr.'"');

		$compFormReqArrStr = "";
		foreach ($messageIdArr as $messageId) {

			$sanMessageId = urlencode($messageId);

			$baseUrlForIndReq = Config::get('app_config_cloud_mailbox.google_mail_box_api_load_mail_box_message_details_for_batch_url'); 
			$baseUrlForIndReq = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $baseUrlForIndReq);
			$baseUrlForIndReq = str_replace($this->urlPlaceHolderMessageId, $sanMessageId, $baseUrlForIndReq);

			$compFormReqArrStr .= $reqSepStrWPrefix . $reqLineBreakStr;
			$compFormReqArrStr .= $reqContentTypeStr . $reqLineBreakStr . $reqLineBreakStr;
			$compFormReqArrStr .= 'GET ' . $baseUrlForIndReq . $reqLineBreakStr;// . $reqLineBreakStr;
		}

		$compFormReqArrStr .= $reqSepStrWPrefix . $reqSepPrefix . '  ';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaderData);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $compFormReqArrStr);

		$response = curl_exec($ch);
		$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

		curl_close($ch);

		$apiCallData = array();
		$apiCallData['url'] = $url;
		$apiCallData['reqHeaderData'] = $reqHeaderData;
		$apiCallData['compFormReqArrStr'] = $compFormReqArrStr;

		$jsonLineArr = array();

		if(isset($response) && $response != "")
		{
			$lines = explode("\r\n", $response);

			foreach ($lines as $line) {
				if (isset($line) && $line != "" && substr($line, 0, 1) == '{') {
		           	$messageResponse = json_decode($line);

					if(isset($messageResponse) && isset($messageResponse->id))
					{
						$compiledMessageDetailsObj = $this->formulateMailBoxMessageEntry($messageResponse, true);

						array_push($mappedDetailsArr, $compiledMessageDetailsObj);
					}
		       	}
			}
		 	
		}
		
		// $compiledResponse = array();
		// $compiledResponse['mappedDetailsArr'] = $mappedDetailsArr;
				        
		return $mappedDetailsArr;
	}
    
    public function getMailBoxMessageDetails($messageId)
    {
		$compiledMailBoxMessageObj = NULL;

		$apiCallData = array();

		$data = array();

		$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_load_mail_box_message_details_url'); 

		$sanMessageId = urlencode($messageId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);
		$url = str_replace($this->urlPlaceHolderMessageId, $sanMessageId, $url);

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response->id) && isset($response->threadId))
		{
			$indEntry = $response;
			$compiledMailBoxMessageObj = $this->formulateMailBoxMessageEntry($indEntry, true);
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$compiledResponse = array();
		$compiledResponse['messageDetails'] = $compiledMailBoxMessageObj;
		// $compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
    
    public function getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType)
    {
		$compiledMailBoxMessageAttachmentObj = NULL;

		$apiCallData = array();

		$data = array();

		$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_load_mail_box_message_attachment_details_url'); 

		$sanMessageId = urlencode($messageId);
		$sanAttachmentId = urlencode($attachmentId);

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);
		$url = str_replace($this->urlPlaceHolderMessageId, $sanMessageId, $url);
		$url = str_replace($this->urlPlaceHolderAttachmentId, $sanAttachmentId, $url);

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response->size) && isset($response->data))
		{
			$compiledMailBoxMessageAttachmentObj = array();
			$compiledMailBoxMessageAttachmentObj['messageId'] = $messageId;
			$compiledMailBoxMessageAttachmentObj['attachmentId'] = $attachmentId;
			$compiledMailBoxMessageAttachmentObj['fileName'] = $fileName;
			$compiledMailBoxMessageAttachmentObj['fileMimeType'] = $fileMimeType;
			$compiledMailBoxMessageAttachmentObj['fileSize'] = $response->size;
			$compiledMailBoxMessageAttachmentObj['fileContent'] = $response->data;
		}

		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$compiledResponse = array();
		$compiledResponse['attachmentDetails'] = $compiledMailBoxMessageAttachmentObj;
		
		// $compiledMailBoxMessageAttachmentObj['apiCallData'] = $apiCallData;
				        
		return $compiledMailBoxMessageAttachmentObj; //$compiledResponse
	}

	protected function formulateMailBoxMessageEntry($indEntry, $isDetailed = FALSE)
	{
		$utcTz =  'UTC';

		$compiledMessageObj = NULL;
		if(isset($indEntry) && isset($indEntry->id))
		{
			$indEntryId = $indEntry->id;
			$threadId = $indEntry->threadId;

			$internalDateTs = 0;
			$snippet = "";

			if(isset($indEntry->snippet))
			{
				$internalDateTs = $indEntry->internalDate;
				$snippet = $indEntry->snippet;
			}

			$isUnread = 0;
			$isReceived = 0;
			$isSent = 0;
			$mailContainerLabel = "";

			if(isset($indEntry->labelIds) && is_array($indEntry->labelIds))
			{
				if(in_array("INBOX", $indEntry->labelIds))
				{
					$mailContainerLabel = "Inbox";
					$isReceived = 1;
					if(in_array("UNREAD", $indEntry->labelIds))
					{
						$isUnread = 1;
					}
				}
				else if(in_array("SENT", $indEntry->labelIds))
				{
					$mailContainerLabel = "Sent";
					$isSent = 1;
				}
			}

			$mailSubject = "";
			$senderOrReceiverEmail = "";

			if(isset($indEntry->payload) && isset($indEntry->payload->headers) && is_array($indEntry->payload->headers))
			{
				foreach ($indEntry->payload->headers as $headerObj)
				{
					$headerKey = $headerObj->name;
					$headerValue = $headerObj->value;

					if($headerKey == "Subject")
					{
						$mailSubject = $headerValue;
					}

					if($isSent == 1 && $headerKey == "To")
					{
						$senderOrReceiverEmail = $headerValue;
					}
					else if($isReceived == 1 && $headerKey == "From")
					{
						$senderOrReceiverEmail = $headerValue;
					}
				}
			}

			$compiledMessageObj = array();
			$compiledMessageObj['cloudMailBoxTypeCode'] = $this->cloudMailBoxTypeCode;
			$compiledMessageObj['cloudMailBoxTypeId'] = $this->cloudMailBoxTypeId;

			$compiledMessageObj['messageId'] = $indEntryId;
			$compiledMessageObj['threadId'] = $threadId;	

			$compiledMessageObj['mailDateTs'] = $internalDateTs;
			$compiledMessageObj['snippet'] = $snippet;

			$compiledMessageObj['mailSubject'] = $mailSubject;
			$compiledMessageObj['senderOrReceiverEmail'] = $senderOrReceiverEmail;
			$compiledMessageObj['mailContainerLabel'] = $mailContainerLabel;

			$compiledMessageObj['isUnread'] = $isUnread;
			$compiledMessageObj['isReceived'] = $isReceived;		
			$compiledMessageObj['isSent'] = $isSent;

			if($isDetailed)
			{
				$attachments = array();
				$mailDetailedContentHtml = "";
				$mailDetailedContentPlain = "";

				if(isset($indEntry->payload) && isset($indEntry->payload->parts) && is_array($indEntry->payload->parts))
				{
					foreach ($indEntry->payload->parts as $partObj)
					{
						$partId = $partObj->partId;
						$mimeType = $partObj->mimeType;
						$filename = $partObj->filename;
						$partBody = isset($partObj->body) ? $partObj->body : null;
						$partSubParts = isset($partObj->parts) ? $partObj->parts : null;

						if($filename != "" && isset($partBody))
						{
							$filesize = $partBody->size;
							$attachmentId = $partBody->attachmentId;

							$attachmentObj = array();
							$attachmentObj['fileName'] = $filename;
							$attachmentObj['fileSize'] = $filesize;
							$attachmentObj['fileMimeType'] = $mimeType;
							$attachmentObj['attachmentId'] = $attachmentId;

							array_push($attachments, $attachmentObj);
						}
						elseif(isset($partSubParts) && is_array($partSubParts))
						{
							foreach ($partSubParts as $subPartObj)
							{
								$subPartId = $subPartObj->partId;
								$subPartMimeType = $subPartObj->mimeType;
								$subPartBody = isset($subPartObj->body) ? $subPartObj->body : null;

								if(isset($subPartBody))
								{
									$subPartBodyDataEnc = $subPartBody->data;
									$subPartBodyDataDec = base64url_decode($subPartBodyDataEnc);

									if($subPartMimeType == "text/html")
									{
										$subPartBodyDataDec = str_replace("\r\n", "<br/>", $subPartBodyDataDec);

										$mailDetailedContentHtml = $subPartBodyDataDec;
									}
									else if($subPartMimeType == "text/plain")
									{
										$mailDetailedContentPlain = $subPartBodyDataDec;
									}
								}
							}
						}
						elseif(isset($partBody))
						{
							$partBodyDataEnc = $partBody->data;
							$partBodyDataDec = base64url_decode($partBodyDataEnc);

							if($mimeType == "text/html")
							{
								$partBodyDataDec = str_replace("\r\n", "<br/>", $partBodyDataDec);

								$mailDetailedContentHtml = $partBodyDataDec;
							}
							else if($mimeType == "text/plain")
							{
								$mailDetailedContentPlain = $partBodyDataDec;
							}							
						}
					}
				}
				elseif(isset($indEntry->payload) && isset($indEntry->payload->partId) && isset($indEntry->payload->body))
				{
					$partId = $indEntry->payload->partId;
					$mimeType = $indEntry->payload->mimeType;
					$filename = $indEntry->payload->filename;
					$partBody = isset($indEntry->payload->body) ? $indEntry->payload->body : null;

					if(isset($partBody))
					{
						$partBodyDataEnc = $partBody->data;
						$partBodyDataDec = base64url_decode($partBodyDataEnc);

						if($mimeType == "text/html")
						{
							$partBodyDataDec = str_replace("\r\n", "<br/>", $partBodyDataDec);

							$mailDetailedContentHtml = $partBodyDataDec;
						}
						else if($mimeType == "text/plain")
						{
							$mailDetailedContentPlain = $partBodyDataDec;
						}							
					}
				}

				if($mailDetailedContentPlain != "" && $mailDetailedContentHtml == "")
				{
					$mailDetailedContentHtml = $mailDetailedContentPlain;
					$mailDetailedContentHtml = str_replace("\r\n", "<br/>", $mailDetailedContentHtml);
				}

				$compiledMessageObj['detailedContentHtml'] = $mailDetailedContentHtml;	
				$compiledMessageObj['detailedContentPlain'] = $mailDetailedContentPlain;	

				$compiledMessageObj['attachments'] = $attachments;	
				$compiledMessageObj['attachmentCount'] = count($attachments);
			}			
		}
		return $compiledMessageObj;
	}
    
    public function checkMessageCanBeDeleted($messageId)
    {
		$canBeDeleted = $this->checkMessageCanBeDeletedBoolean($messageId);

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
			$validationMsg = 'Message not owned by you. Cannot be deleted.';
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeletedFlag;
		$compiledResponse['validationMsg'] = $validationMsg;
				        
		return $compiledResponse;
	}
    
    private function checkMessageCanBeDeletedBoolean($messageId)
    {
		$canBeDeleted = true;

		$detailsResponse = $this->getMailBoxMessageDetails($messageId);

		if(isset($detailsResponse) && isset($detailsResponse['messageDetails']))
		{
			
		}
		else
		{
			$canBeDeleted = false;
		}
				        
		return $canBeDeleted;
	}
    
    public function performMessageDelete($messageId)
    {
		$response = NULL;

		$isDeleted = 0;

		$apiCallData = array();

		$canBeDeleted = $this->checkMessageCanBeDeletedBoolean($messageId);
		if($canBeDeleted)
		{
			$data = array();

			$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_delete_mail_box_message_url'); 

			$sanMessageId = urlencode($messageId);

			$url = $baseUrl;
			$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);
			$url = str_replace($this->urlPlaceHolderMessageId, $sanMessageId, $url);

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
    
    public function addNewMessage($subject, $snippet, $sendToEmail, $sendCcEmail, $sendBccEmail)
    {
		$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_create_mail_box_message_url'); 

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);
			
		$emailContentStr  = "From: " . $this->mailBoxId . "\n";
		if(isset($sendToEmail) && $sendToEmail != "")
		{
			$emailContentStr .= "To: " . $sendToEmail . "\n";
		}

		if(isset($sendCcEmail) && $sendCcEmail != "")
		{
			$emailContentStr .= "Cc: " . $sendCcEmail . "\n";
		}

		if(isset($sendBccEmail) && $sendBccEmail != "")
		{
			$emailContentStr .= "Bcc: " . $sendBccEmail . "\n";
		}

		$emailContentStr .= "Subject: " . $subject . "\n";
		$emailContentStr .= $snippet;
		
		$data = array();
		$data['raw'] = base64_encode($emailContentStr);

		// $this->dbHeaders['To'] = $sendToEmail;

		$response = $this->makePostApiCall($url, $data);

		$syncMessageId = '';
		if(isset($response) && isset($response->id))
		{
			$syncMessageId = $response->id;
		}	

		$apiCallData = array();
		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;

		$compiledResponse = array();
		$compiledResponse['syncMessageId'] = $syncMessageId;

		$compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
    
    public function updateExistingMessage($tzOfs, $messageId, $startTs, $endTs, $summary, $description)
    {
    	$messageDetailsResponse = $this->getMailBoxMessageDetails($messageId);

    	if(isset($messageDetailsResponse) && isset($messageDetailsResponse['messageDetails']))
    	{
    		$url = Config::get('app_config_cloud_mailbox.google_mail_box_api_update_mail_box_message_url');

			$startDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($startTs, $tzOfs);
			$endDateTimeStr = CommonFunctionClass::getDBDateTimeStrFromUTCTimeStamp($endTs, $tzOfs);

			$data = array();
			$data['visibility'] = 'private';
			$data['summary'] = $summary;
			$data['description'] = '';//$description;
			$data['start'] = ([ "dateTime" => $startDateTimeStr ]);
			$data['end'] = ([ "dateTime" => $endDateTimeStr ]);

			$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_update_mail_box_message_url'); 

			$sanMessageId = urlencode($messageId);

			$url = $baseUrl;
			$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);
			$url = str_replace($this->urlPlaceHolderMessageId, $sanMessageId, $url);
			
			$response = $this->makePutApiCall($url, $data);

			$syncMessageId = '';
			$isSyncMessageIdDiff = 0;
			if(isset($response) && isset($response->id))
			{
				$syncMessageId = $response->id;
				if($syncMessageId != $messageId)
				{
					$isSyncMessageIdDiff = 1;
				}
			}	

			$compiledResponse = array();
			$compiledResponse['syncMessageId'] = $syncMessageId;
			$compiledResponse['isSyncMessageIdDiff'] = $isSyncMessageIdDiff;

			$compiledResponse['url'] = $url;
			$compiledResponse['apiResponse'] = $response;
			$compiledResponse['apiRequest'] = $data;
					        
			return $compiledResponse;
    	}
		else
		{
			$compiledResponse = $this->getMailBoxMessageDetails($tzOfs, $messageId, $startTs, $endTs, $summary, $description);
					        
			return $compiledResponse;
		}	
	}

	public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
	{
		$response = NULL;

		$url = Config::get('app_config_cloud_mailbox.google_mail_box_api_refresh_access_token_url');

		$data = array();
		$data['grant_type'] = 'refresh_token';
		$data['client_id'] = $consClientId;
		$data['client_secret'] = $consClientSecret;
		$data['refresh_token'] = $refreshToken;

		$call = Curl::to($url)->asJson()->withData($data);		
	 	$response = $call->post();

		$compiledResponse = array();
		$compiledResponse['response'] = $response;
		// $compiledResponse['data'] = $data;
		// $compiledResponse['url'] = $url;
				        
		return $response;//$compiledResponse;
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
    
    public function getMailBoxMessagesForPeriodicSync($syncToken = NULL, $cursorStr = NULL)
    {
		$compiledMailBoxMessageList = array();
		$hasLoadMore = FALSE;
		$loadMoreCursor = NULL;

		$apiCallData = array();

		$data = array();

		$baseUrl = Config::get('app_config_cloud_mailbox.google_mail_box_api_load_mail_box_message_list_url'); 
		if(isset($cursorStr) && trim($cursorStr) != "")
		{
			$data['pageToken'] = trim($cursorStr);
		}

		if(isset($syncToken) && trim($syncToken) != "")
		{
			$data['syncToken'] = trim($syncToken);
		}

		$url = $baseUrl;
		$url = str_replace($this->urlPlaceHolderMailBoxId, $this->sanMailBoxId, $url);

		$response = $this->makeGetApiCall($url, $data);

		if(isset($response) && isset($response->items) && is_array($response->items) && count($response->items) > 0)
		{
			foreach ($response->items as $indEntry) {
				$compiledMailBoxMessageObj = $this->formulateMailBoxMessageEntry($indEntry);
				if(isset($compiledMailBoxMessageObj))
				{
					array_push($compiledMailBoxMessageList, $compiledMailBoxMessageObj);
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

		$messageCount = count($compiledMailBoxMessageList);

		$compiledResponse = array();
		$compiledResponse['messageCount'] = $messageCount;
		$compiledResponse['messageList'] = $compiledMailBoxMessageList;
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		// $compiledResponse['apiCallData'] = $apiCallData;
				        
		return $compiledResponse;
	}
}