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

class CloudStorageOneDriveManagementClass 
{
	protected $apiKey = NULL;
	protected $accessToken = NULL;
	protected $formattedAccessToken = NULL;
	protected $cloudStorageTypeCode = '';
	protected $cloudStorageTypeId = 0;

	protected $fileFolderFetchMaxCount = 0;

	protected $baseUrl = "";
	protected $dbHeaders;

	protected $mimeTypeForFolder = 'application/vnd.microsoft-apps.folder';

	protected $definedFolderTypeIdForHomeDrive = 0;
	protected $definedFolderNameForRootDrive = 'root';
	protected $definedFolderIdForRootDrive = 'root';
	protected $definedFolderTypeIdForRootDrive = 1;
	protected $definedFolderNameForSharedDrive = 'Shared with Me';
	protected $definedFolderIdForSharedDrive = 'sharedWithMe';
	protected $definedFolderTypeIdForSharedDrive = 2;

	protected $folderFileIdPrefix = 'id:';
	protected $fileThumbnailGenFormat = 'jpeg';
	protected $fileThumbnailGenSize = 'w64h64';
	protected $fileThumbnailGenMode = 'strict';
	protected $fileThumbnailFetchMaxCount = 0;
	protected $filePreviewIsBase64 = 0;

	protected $urlPlaceHolderItemId = '{{item-id}}';
	protected $urlPlaceHolderDriveId = '{{drive-id}}';
	protected $urlPlaceHolderSearchStr = '{{search-str}}';
	protected $urlPlaceHolderFileName = '{{file-name}}';
	
	public function __construct()
    {
    	$this->fileFolderFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
    	$this->fileThumbnailFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
		$this->apiKey = env('MICROSOFT_API_KEY');
    }
		
	public function setBasicDetails($typeId, $typeCode)
    {   
		$this->cloudStorageTypeId = $typeId;
		$this->cloudStorageTypeCode = $typeCode;
    }
		
	public function withAccessToken($accessToken)
    {   
        Log::info('withAccessToken : accessToken : '.$accessToken);
		$this->accessToken = $accessToken;
		$this->formattedAccessToken = "Bearer ".$accessToken;
    	$this->dbHeaders = [
    		'Authorization' => $this->formattedAccessToken
		];
    }

    public function withAppKeyMapping($appKeyMapping)
    {
        Log::info('withAppKeyMapping: ');
    	if(isset($appKeyMapping))
    	{
    		$microsoftApiKey = $appKeyMapping->microsoft_api_key;
        	Log::info('microsoftApiKey: '.$microsoftApiKey);
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
    
    public function getBaseFolderDrives($parentFolderId = NULL, $queryStr = "", $cursorStr = "")
    {
		$thumbUrl = OrganizationClass::getOrgContentAssetFolderThumbUrl();

		$indRootEntryId = $this->definedFolderIdForRootDrive;

		$compiledRootFolderObj = array();
		$compiledRootFolderObj['isFile'] = 0;
		$compiledRootFolderObj['folderId'] = $indRootEntryId;
		$compiledRootFolderObj['folderName'] = $this->definedFolderNameForRootDrive;
		$compiledRootFolderObj['folderPath'] = $indRootEntryId;
		$compiledRootFolderObj['folderDisplayPath'] = $this->definedFolderNameForRootDrive;
		$compiledRootFolderObj['thumbUrl'] = $thumbUrl;
		$compiledRootFolderObj['hideDeleteBtn'] = 1;
		$compiledRootFolderObj['baseFolderType'] = $this->definedFolderTypeIdForRootDrive;

		$indSharedEntryId = $this->definedFolderIdForSharedDrive;

		$compiledSharedFolderObj = array();
		$compiledSharedFolderObj['isFile'] = 0;
		$compiledSharedFolderObj['folderId'] = $indSharedEntryId;
		$compiledSharedFolderObj['folderName'] = $this->definedFolderNameForSharedDrive;
		$compiledSharedFolderObj['folderPath'] = $indSharedEntryId;
		$compiledSharedFolderObj['folderDisplayPath'] = $this->definedFolderNameForSharedDrive;
		$compiledSharedFolderObj['thumbUrl'] = $thumbUrl;
		$compiledSharedFolderObj['hideDeleteBtn'] = 1;
		$compiledSharedFolderObj['baseFolderType'] = $this->definedFolderTypeIdForSharedDrive;

		$compiledFileFolderList = array();
		array_push($compiledFileFolderList, $compiledRootFolderObj);
		array_push($compiledFileFolderList, $compiledSharedFolderObj);

		return $compiledFileFolderList;
    }

    public function checkAccessTokenValidity()
    {
    	$response = array();

    	$isTokenValid = 0;

    	$fileFolderListResponse = $this->getAllFoldersAndFiles();
    	if(isset($fileFolderListResponse) && isset($fileFolderListResponse['isTokenValid']))
    	{
    		$isTokenValid = $fileFolderListResponse['isTokenValid'];
    	}

    	$response['isTokenValid'] = $isTokenValid;

    	return $response;
    }
    
    public function getAllFoldersAndFiles($parentFolderId = NULL, $queryStr = "", $cursorStr = "", $baseFolderTypeId = NULL)
    {
    	$consFilePath = "";

		$maxResultCount = $this->fileFolderFetchMaxCount;

		$compiledFileFolderList = array();
		$hasLoadMore = 0;
		$loadMoreCursor = "";

		$hasAddFolder = 1;
		$hasAddFile = 1;
		$hasSelectFile = 1;
		$hasSearchFile = 1;

		$hasCursor = false;
		if(isset($cursorStr) && trim($cursorStr) != "")
		{
			$hasCursor = true;
			$cursorStr = trim($cursorStr);
		}

		$apiCallData = array();

		$data = array();
		$data['$expand'] = 'thumbnails';

		$hasSearchCriteria = false;

		if(isset($queryStr) && trim($queryStr) != "")
		{
			$url = Config::get('app_config_cloud_storage.one_drive_api_file_folder_search_list_url');
			
			$url = str_replace($this->urlPlaceHolderSearchStr, $queryStr, $url);

			$hasSearchCriteria = true;
		}
		else if(isset($parentFolderId) && trim($parentFolderId) != "")
		{
			$url = Config::get('app_config_cloud_storage.one_drive_api_file_folder_list_url');
			
			$url = str_replace($this->urlPlaceHolderItemId, $parentFolderId, $url);
		}
		else
		{
			$url = Config::get('app_config_cloud_storage.one_drive_api_root_file_folder_list_url');
		}

		if($hasCursor)
		{
			$data['pageToken'] = $cursorStr;
		}

		$response = $this->makeGetApiCall($url, $data);

		$isTokenValid = 1;

		$parentObj = NULL;

		if(isset($response) && isset($response->value) && is_array($response->value) && count($response->value) > 0)
		{
			foreach ($response->value as $indEntry) {
				$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $baseFolderTypeId);
				if(isset($compiledFileFolderObj))
				{
					if(!$hasSearchCriteria || ($hasSearchCriteria && $compiledFileFolderObj['isFile'] == 1))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);
					}
				}
				if(!isset($parentObj) && isset($indEntry) && isset($indEntry->parentReference))
				{
					$parentObj = $indEntry->parentReference;
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
		// $apiCallData['dbHeaders'] = $this->dbHeaders;

		$folderCount = count($compiledFileFolderList);

		$compiledResponse = array();
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['folderCount'] = $folderCount;
		$compiledResponse['folderList'] = $compiledFileFolderList;
		$compiledResponse['hasAddFolder'] = $hasAddFolder;
		$compiledResponse['hasAddFile'] = $hasAddFile;
		$compiledResponse['hasSelectFile'] = $hasSelectFile;
		$compiledResponse['hasSearchFile'] = $hasSearchFile;
		$compiledResponse['isTokenValid'] = $isTokenValid;
		// $compiledResponse['apiCallData'] = $apiCallData;

		if(!$hasCursor)
		{
			$breadcrumbArr = $this->formulateFolderBreadCrumbArr($parentFolderId, $baseFolderTypeId, $parentObj);
			$compiledResponse['breadcrumbArr'] = $breadcrumbArr;
		}
				        
		return $compiledResponse;
	}
    
    public function getContinuedFoldersAndFiles($parentFolderName = NULL, $queryStr = "", $cursorStr = "", $baseFolderTypeId = NULL)
    {
		$compiledResponse = $this->getAllFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId);	        
		return $compiledResponse;
	}
    
    public function getFilteredFoldersAndFiles($parentFolderId = NULL, $queryStr = "", $cursorStr = "", $baseFolderTypeId = NULL, $isGeneralExhausted = FALSE)
    {
    	$consFilePath = "";

		$maxResultCount = $this->fileFolderFetchMaxCount;

		$compiledFileFolderList = array();
		$hasLoadMore = 0;
		$loadMoreCursor = "";

		$hasAddFolder = 0;
		$hasAddFile = 0;
		$hasSelectFile = 1;
		$hasSearchFile = 0;

		$hasCursor = false;
		if(isset($cursorStr) && trim($cursorStr) != "")
		{
			$hasCursor = true;
			$cursorStr = trim($cursorStr);
		}

		$apiCallData = array();

		$data = array();
		$data['key'] = $this->apiKey;
		$data['pageSize'] = $maxResultCount;
		$data['fields'] = 'files(id,name,iconLink,webViewLink,webContentLink,size,thumbnailLink,mimeType,viewersCanCopyContent,starred),nextPageToken,incompleteSearch';
		$data['includeItemsFromAllDrives'] = true;
		$data['supportsAllDrives'] = true;


		$url = Config::get('app_config_cloud_storage.one_drive_api_file_folder_list_url');

		$compiledQueryStr = "trashed=false";

		$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
		$compiledQueryStr .= "mimeType != '".$this->mimeTypeForFolder."'";

		if($hasCursor)
		{
			$data['pageToken'] = $cursorStr;
		}

		if(isset($queryStr) && trim($queryStr) != "")
		{
			$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
			$compiledQueryStr .= "fullText contains '".$queryStr."'";
		}

		$sharedFolderCompiledQueryStr = $compiledQueryStr;

		$sharedFolderCompiledQueryStr .= $sharedFolderCompiledQueryStr != "" ? " and " : "";
		$sharedFolderCompiledQueryStr .= "sharedWithMe=true";

		if($compiledQueryStr != "")
		{

			$compiledQueryStr = str_replace(" ", "%20", $compiledQueryStr);
			$compiledQueryStr = str_replace("'", "%22", $compiledQueryStr);

			$data['q'] = $compiledQueryStr;
		}

		if($sharedFolderCompiledQueryStr != "")
		{
			$sharedFolderCompiledQueryStr = str_replace(" ", "%20", $sharedFolderCompiledQueryStr);
			$sharedFolderCompiledQueryStr = str_replace("'", "%22", $sharedFolderCompiledQueryStr);
		}

		$generalResponse = $this->makeGetApiCall($url, $data);

		if(isset($generalResponse) && isset($generalResponse->files) && is_array($generalResponse->files) && count($generalResponse->files) > 0)
		{
			foreach ($generalResponse->files as $indEntry) {
				$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $baseFolderTypeId);
				if(isset($compiledFileFolderObj))
				{
					array_push($compiledFileFolderList, $compiledFileFolderObj);
				}
			}

			if(isset($generalResponse->nextPageToken))
			{
				$hasLoadMore = 1;
				$loadMoreCursor = $generalResponse->nextPageToken;
			}
			else
			{
				$isGeneralExhausted = TRUE;
			}
		}
		
		$folderCount = count($compiledFileFolderList);

		if($isGeneralExhausted && $folderCount < $maxResultCount)
		{
			// $data['pageSize'] = $maxResultCount - $folderCount;
			$data['q'] = $sharedFolderCompiledQueryStr;

			$sharedResponse = $this->makeGetApiCall($url, $data);

			if(isset($sharedResponse) && isset($sharedResponse->files) && is_array($sharedResponse->files) && count($sharedResponse->files) > 0)
			{
				foreach ($sharedResponse->files as $indEntry) {
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $baseFolderTypeId);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);
					}
				}

				if(isset($sharedResponse->nextPageToken))
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $sharedResponse->nextPageToken;
				}
			}
		}
		

		$folderCount = count($compiledFileFolderList);

		$compiledResponse = array();
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['isGeneralExhausted'] = $isGeneralExhausted ? 1 : 0;
		$compiledResponse['folderCount'] = $folderCount;
		$compiledResponse['folderList'] = $compiledFileFolderList;
		$compiledResponse['hasAddFolder'] = $hasAddFolder;
		$compiledResponse['hasAddFile'] = $hasAddFile;
		$compiledResponse['hasSelectFile'] = $hasSelectFile;
		$compiledResponse['hasSearchFile'] = $hasSearchFile;
		// $compiledResponse['generalResponse'] = $generalResponse;
		// $compiledResponse['data'] = $data;

		if(!$hasCursor)
		{
			$breadcrumbArr = $this->formulateFolderBreadCrumbArr($parentFolderId, $baseFolderTypeId);
			$compiledResponse['breadcrumbArr'] = $breadcrumbArr;
		}
				        
		return $compiledResponse;
	}
    
    public function getContinuedFilteredFoldersAndFiles($parentFolderName = NULL, $queryStr = "", $cursorStr = "", $baseFolderTypeId = NULL, $isGeneralExhausted = FALSE)
    {
		$compiledResponse = $this->getFilteredFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId, $isGeneralExhausted);	        
		return $compiledResponse;
	}

	protected function formulateFolderOrFileEntry($indEntry, $consFilePath, $baseFolderTypeId = NULL)
	{
		$compiledFileFolderObj = NULL;
		if(isset($indEntry))
		{
			$indEntryId = $indEntry->id;

			if(isset($indEntry->folder))
			{
				// if($consFilePath != $indEntry->path_lower)
				{
					$thumbUrl = OrganizationClass::getOrgContentAssetFolderThumbUrl();

					$compiledFileFolderObj = array();
					$compiledFileFolderObj['isFile'] = 0;
					$compiledFileFolderObj['folderId'] = $indEntryId;
					$compiledFileFolderObj['folderName'] = $indEntry->name;
					$compiledFileFolderObj['folderPath'] = $indEntryId;
					$compiledFileFolderObj['folderDisplayPath'] = $indEntry->name;
					$compiledFileFolderObj['thumbUrl'] = $thumbUrl;
					$compiledFileFolderObj['baseFolderType'] = isset($baseFolderTypeId) ? $baseFolderTypeId : 0;
				}
			}
			elseif(isset($indEntry->file))
			{
				$thumbUrl = OrganizationClass::getOrgContentAssetThumbUrl(0, $indEntry->name);
				$isFileTypeImage = checkIfFileTypeImageFromFileName($indEntry->name);
				$filePreviewStr = isset($indEntry->thumbnails[0]) && isset($indEntry->thumbnails[0]->medium) && $indEntry->thumbnails[0]->medium->url != '' && $isFileTypeImage ? $indEntry->thumbnails[0]->medium->url : '';
				$hasFilePreviewStr = $filePreviewStr != '' ? 1 : 0;
				$isFileTypeImageFlag = $isFileTypeImage ? 1 : 0;
				$fileSizeBytes = isset($indEntry->size) ? $indEntry->size : 0;
				
				$fileSizeKB = CommonFunctionClass::getFileSizeInKBFromSizeInBytes($fileSizeBytes);
				if($fileSizeKB <= 0)
				{
					$fileSizeKB = 1;
				}

				$compiledFileFolderObj = array();
				$compiledFileFolderObj['isFile'] = 1;
				$compiledFileFolderObj['fileId'] = $indEntryId;
				$compiledFileFolderObj['fileName'] = $indEntry->name;
				$compiledFileFolderObj['filePath'] = $indEntryId;
				$compiledFileFolderObj['fileDisplayPath'] = $indEntry->name;
				$compiledFileFolderObj['fileSizeKB'] = $fileSizeKB;
				$compiledFileFolderObj['fileSize'] = $fileSizeBytes;
				$compiledFileFolderObj['fileSizeStr'] = CommonFunctionClass::getFileSizeStrFromSizeInBytes($fileSizeBytes);
				$compiledFileFolderObj['thumbUrl'] = $thumbUrl;//isset($indEntry->iconLink) && $indEntry->iconLink != '' ? $indEntry->iconLink : '';
				$compiledFileFolderObj['isFileTypeImage'] = $isFileTypeImageFlag;
				$compiledFileFolderObj['hasFilePreviewStr'] = $hasFilePreviewStr;
				$compiledFileFolderObj['filePreviewStr'] = $filePreviewStr;
				$compiledFileFolderObj['filePreviewIsBase64'] = $this->filePreviewIsBase64;
			}
		}
		return $compiledFileFolderObj;
	}

	protected function formulateFolderBreadCrumbArr($parentFolderId, $baseFolderTypeId = NULL)
	{
		$compiledBreadcrumbArr = array();

		$compiledResponseArr = array();

		$compiledBreadcrumbObjRoot = array();
		$compiledBreadcrumbObjRoot['title'] = 'Home';
		$compiledBreadcrumbObjRoot['path'] = '';
		$compiledBreadcrumbObjRoot['baseFolderType'] = $this->definedFolderTypeIdForHomeDrive;
		// array_push($compiledBreadcrumbArr, $compiledBreadcrumbObj);


		if(isset($parentFolderId) && $parentFolderId != "")
		{
			$consParentFolderId = $parentFolderId;
			$parentsExhausted = false;

			$i = 1;

			$fetchedBreadcrumbArr = array();
			$existingFolderIdArr = array();

			do
			{
				$url = Config::get('app_config_cloud_storage.one_drive_api_file_details_url');
			
				$url = str_replace($this->urlPlaceHolderItemId, $consParentFolderId, $url);

				$data = array();

				$folderDetailsResponse = $this->makeGetApiCall($url, $data);

				$compiledResponseArr['data_'.$i] = $data;
				$compiledResponseArr['url'.$i] = $url;
				$compiledResponseArr['folderDetailsResponse_'.$i] = $folderDetailsResponse;

				$i++;

				if(isset($folderDetailsResponse) && isset($folderDetailsResponse->id))
				{
					$fetchedFolderId = $folderDetailsResponse->id;
					$fetchedFolderName = $folderDetailsResponse->name;

					$compiledBreadcrumbObj = array();
					$compiledBreadcrumbObj['title'] = $fetchedFolderName;
					$compiledBreadcrumbObj['path'] = $fetchedFolderId;
					$compiledBreadcrumbObj['baseFolderType'] = $baseFolderTypeId;

					if(isset($folderDetailsResponse->parentReference) && isset($folderDetailsResponse->parentReference->id))
					{
						$consParentFolderId = $folderDetailsResponse->parentReference->id;
					}
					else
					{
						$parentsExhausted = true;
					}

					if($parentsExhausted && $fetchedFolderName == $this->definedFolderNameForRootDrive)
					{

					}
					else
					{
						if(!in_array($fetchedFolderId, $existingFolderIdArr))// && $fetchedFolderId != $parentFolderId)
						{
							array_push($fetchedBreadcrumbArr, $compiledBreadcrumbObj);
							array_push($existingFolderIdArr, $fetchedFolderId);							
						}
					}
				}
				else
				{
					$parentsExhausted = true;
				}
			}while(!$parentsExhausted);

			if(count($fetchedBreadcrumbArr) > 0)
			{
				$reversedBreadcrumbArr = array_reverse($fetchedBreadcrumbArr);

				$compiledBreadcrumbArr = array_merge($compiledBreadcrumbArr, $reversedBreadcrumbArr);
			}
		}

		if(count($compiledBreadcrumbArr) > 0)
		{
			array_unshift($compiledBreadcrumbArr, $compiledBreadcrumbObjRoot);
		}

		$compiledResponse = array();
		$compiledResponse['compiledBreadcrumbArr'] = $compiledBreadcrumbArr;
		$compiledResponse['compiledResponseArr'] = $compiledResponseArr;

		return $compiledBreadcrumbArr;//$compiledResponse;
	}
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
    	$consFilePath = '';

		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

    	$mappedDetailsArr = array();

		$url = Config::get('app_config_cloud_storage.one_drive_api_file_details_url');

		$data = array();

		$compFileIdArr = array();
		$fileDetailResponseArr = array();
		foreach ($fileIdArr as $fileId) {
			array_push($compFileIdArr, $fileId);

			$urlWithIdAppended = $url;			
			$urlWithIdAppended = str_replace($this->urlPlaceHolderItemId, $fileId, $urlWithIdAppended);

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
					$compiledFileDetailsObj['fileStorageUrl'] = $fileResponse->webUrl;
					$compiledFileDetailsObj['cloudStorageTypeCode'] = $this->cloudStorageTypeCode;
					$compiledFileDetailsObj['cloudStorageTypeId'] = $this->cloudStorageTypeId;

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
    
    public function addNewFolder($parentFolderId = "", $insFolderName)
    {
		$url = Config::get('app_config_cloud_storage.one_drive_api_folder_create_url');

		$data = array();
		$data['name'] = $insFolderName;
		$data['folder'] = json_decode('{}');
		$data['@microsoft.graph.conflictBehavior'] = "rename";

		if(isset($parentFolderId) && trim($parentFolderId) != "")
		{
			$parentFolderId = trim($parentFolderId);
			$url = str_replace($this->urlPlaceHolderItemId, $parentFolderId, $url);
		}
		else
		{
			$url = str_replace($this->urlPlaceHolderItemId, "root", $url);
		}
		
		$response = $this->makePostApiCall($url, $data);

		if(isset($response))
		{

		}	

		$compiledResponse = array();
		$compiledResponse['apiResponse'] = $response;
		$compiledResponse['apiRequest'] = $data;
				        
		return $compiledResponse;
	}
    
    public function loadFolderDetails($folderId)
    {
		$url = Config::get('app_config_cloud_storage.one_drive_api_folder_details_url');

		$data = array();

		$urlWithIdAppended = str_replace($this->urlPlaceHolderItemId, $folderId, $url);
		
		$response = $this->makeGetApiCall($urlWithIdAppended, $data);

		if(isset($response))
		{

		}	

		$compiledResponse = array();
		$compiledResponse['apiResponse'] = $response;
		$compiledResponse['apiRequest'] = $data;
				        
		return $compiledResponse;
	}
    
    public function checkFolderCanBeDeleted($folderId)
    {
		$canBeDeleted = $this->checkFolderCanBeDeletedBoolean($folderId);

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
			$validationMsg = 'Contains files. Cannot be deleted.';
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeletedFlag;
		$compiledResponse['validationMsg'] = $validationMsg;
				        
		return $compiledResponse;
	}
    
    private function checkFolderCanBeDeletedBoolean($folderId)
    {
		$canBeDeleted = true;
		
		$folderDetailsUrl = Config::get('app_config_cloud_storage.one_drive_api_folder_details_url');

		$folderDetailsData = array();
		$urlWithIdAppended = str_replace($this->urlPlaceHolderItemId, $folderId, $folderDetailsUrl);

		$folderDetailsResponse = $this->makeGetApiCall($urlWithIdAppended, $folderDetailsData);

		if(!isset($folderDetailsResponse) || !isset($folderDetailsResponse->folder) || !isset($folderDetailsResponse->folder->childCount) || $folderDetailsResponse->folder->childCount > 0)
		{
			$canBeDeleted = false;
		}		
				        
		return $canBeDeleted;
	}
    
    public function performFolderDelete($folderId)
    {
		$response = NULL;

		$isDeleted = 0;

		$canBeDeleted = $this->checkFolderCanBeDeletedBoolean($folderId);
		if($canBeDeleted)
		{
			$url = Config::get('app_config_cloud_storage.one_drive_api_folder_delete_url');

			$urlWithData = str_replace($this->urlPlaceHolderItemId, $folderId, $url);

			$call = Curl::to($urlWithData)->asJson();
			$call = $this->attachHeaders($call);
			
		 	$response = $call->delete();

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;
				        
		return $compiledResponse;
	}
    
    public function uploadFile($parentFolderId, $fileObj)
    {
        $fileSize = $fileObj->getSize();
        $fileName = $fileObj->getClientOriginalName();
        $fileExt = $fileObj->getClientOriginalExtension();
        $fileExtWithPrefix = '.'.$fileExt;
        $fileMimeType = getMimeTypeFromFilename($fileName);

        if(!isset($parentFolderId) || $parentFolderId == "")
        {
        	$parentFolderId = "root";
        }

        $fileNameWithOutExt = str_replace($fileExtWithPrefix, "", $fileName);

        $response = NULL;
        $reqHeaderData = NULL;
        $renameCallResponse = NULL;

        $isUploaded = 0;
        $uplFileName = '';
        $uplFilePath = '';
        $uplFileId = '';

        $orgId = 0;
        $serverFileName = FileUploadClass::uploadTempCloudStorageAttachment($fileObj, $fileExtWithPrefix);

        $thresholdForResumableFileUpload = 4 * 1024 * 1024;// 4 MB

        if(isset($serverFileName) && $serverFileName != "")
        {
        	$projectBasePath = "/var/www/html/public";
        	$orgAssetDirPath = OrganizationClass::getTempCloudStorageAssetDirPath();
	        $orgFileRealPath = $projectBasePath."/".$orgAssetDirPath."/".$serverFileName;

	        $performLargeFileUpload = true;//false;

        	if($fileSize <= $thresholdForResumableFileUpload)
        	{
        		$url = Config::get('app_config_cloud_storage.one_drive_api_file_upload_url');
				$url = str_replace($this->urlPlaceHolderItemId, urlencode($parentFolderId), $url);
				$url = str_replace($this->urlPlaceHolderFileName, urlencode($fileName), $url);

		        $fp = fopen($orgFileRealPath, 'rb');

				$reqHeaderData = array();
				array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
				array_push($reqHeaderData, "Content-Type: $fileMimeType {title:'$fileNameWithOutExt'}");
				array_push($reqHeaderData, "Content-Length: $fileSize");

				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaderData);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);

				$response = curl_exec($ch);
				$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

				curl_close($ch);
				fclose($fp);

				if (200 === $responseCode && isset($response))
				{
					$responseJson = json_decode($response, true);
					if(isset($responseJson) && isset($responseJson['id']) && $responseJson['id'] != '')
					{
						$isUploaded = 1;
						$uplFileName = $responseJson['name'];
						$uplFileId = $responseJson['id'];
					}
		        }

	        	FileUploadClass::removeTempCloudStorageAttachment($serverFileName);
        	}        	
        	else if($performLargeFileUpload)
        	{
        		$createSessionUrl = Config::get('app_config_cloud_storage.one_drive_api_file_upload_create_session_url');
				$createSessionUrl = str_replace($this->urlPlaceHolderItemId, urlencode($parentFolderId), $createSessionUrl);
				$createSessionUrl = str_replace($this->urlPlaceHolderFileName, urlencode($fileName), $createSessionUrl);

				$createSessionItemData = array();
				$createSessionItemData['name'] = $fileName;
				$createSessionItemData['description'] = "";
				$createSessionItemData['fileSize'] = $fileSize;
				$createSessionItemData['@microsoft.graph.conflictBehavior'] = "rename";

				$createSessionData = array();
				$createSessionData['item'] = $createSessionItemData;
				
				$createSessionResponse = $this->makePostApiCall($createSessionUrl, $createSessionData);
				if(isset($createSessionResponse) && isset($createSessionResponse->uploadUrl) && $createSessionResponse->uploadUrl != '')
				{
					$uploadSessionUrl = $createSessionResponse->uploadUrl;

					$fp = fopen($orgFileRealPath, 'rb');

					$reqHeaderData = array();
					array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
					array_push($reqHeaderData, "Content-Type: $fileMimeType {title:'$fileNameWithOutExt'}");
					array_push($reqHeaderData, "Content-Length: $fileSize");

					$ch = curl_init($uploadSessionUrl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaderData);
					curl_setopt($ch, CURLOPT_PUT, true);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
					curl_setopt($ch, CURLOPT_INFILE, $fp);
					curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);

					$response = curl_exec($ch);
					$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

					curl_close($ch);
					fclose($fp);

					if ((200 === $responseCode || 201 === $responseCode || 202 === $responseCode) && isset($response))
					{
						$responseJson = json_decode($response, true);
						if(isset($responseJson) && isset($responseJson['id']) && $responseJson['id'] != '')
						{
							$isUploaded = 1;
							$uplFileName = $responseJson['name'];
							$uplFileId = $responseJson['id'];
						}
			        }

		        	FileUploadClass::removeTempCloudStorageAttachment($serverFileName);

					/*
				 	$fp = fopen($orgFileRealPath, 'rb');

				 	$contents = fread($fp, $fileSize); 
					$byteArray = unpack("N*", $contents); 

					fclose($fp);

					Log::info( $byteArray );

					for($n = 0; $n < 16; $n++)
					{ 
						$byteWiseContent = $byteArray[$n];
					    Log::info( $byteWiseContent ); 

					    $byteRangeStart = 0;
					    $byteRangeEnd = 25;

					    $byteWiseContentSize = 16;

					    $reqHeaderData = array();
						array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
						array_push($reqHeaderData, "Content-Length: $byteWiseContentSize");
						array_push($reqHeaderData, "Content-Range: bytes $byteRangeStart-$byteRangeEnd/$fileSize");

						$ch = curl_init($url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaderData);
						curl_setopt($ch, CURLOPT_PUT, true);
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
						curl_setopt($ch, CURLOPT_INFILE, $byteWiseContent);
						curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);

						$response = curl_exec($ch);
						$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
						
						curl_close($ch);

						if (202 === $responseCode && isset($response))
						{
							$responseJson = json_decode($response, true);
							if(isset($responseJson) && isset($responseJson['nextExpectedRanges']) && $responseJson['id'] != '')
							{
								$isUploaded = 1;
								$uplFileName = $responseJson['name'];
								$uplFileId = $responseJson['id'];
							}
				        }
					}
					*/
				}
        	}				
	 	}

		$compiledResponse = array();
		// $compiledResponse['url'] = $url;
		$compiledResponse['fileSize'] = $fileSize;
		$compiledResponse['fileMimeType'] = $fileMimeType;
		$compiledResponse['fileName'] = $fileName;
		$compiledResponse['uplFileId'] = $uplFileId;
		$compiledResponse['uplFileName'] = $uplFileName;
		$compiledResponse['isUploaded'] = $isUploaded;

		$compiledResponse['response'] = $response;
		$compiledResponse['reqHeaderData'] = $reqHeaderData;
		$compiledResponse['renameCallResponse'] = $renameCallResponse;
				        
		return $compiledResponse;
	}
    
    public function checkFileCanBeDeleted($fileId)
    {
		$canBeDeleted = $this->checkFileCanBeDeletedBoolean($fileId);

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
			$validationMsg = 'File inaccessible.';
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeletedFlag;
		$compiledResponse['validationMsg'] = $validationMsg;
				        
		return $compiledResponse;
	}
    
    public function checkFileCanBeDeletedBoolean($fileId)
    {
		$url = Config::get('app_config_cloud_storage.one_drive_api_file_details_url');

		$data = array();

		$urlWithIdAppended = str_replace($this->urlPlaceHolderItemId, $fileId, $url);

		$response = $this->makeGetApiCall($urlWithIdAppended, $data);

		$canBeDeleted = true;

		if(!isset($response) || !isset($response->id))
		{
			$canBeDeleted = false;
		}
				        
		return $canBeDeleted;
	}
    
    public function performFileDelete($fileId)
    {
		$consFilePathWithPrefix = $this->folderFileIdPrefix.$fileId;

		$response = NULL;

		$isDeleted = 0;

		$canBeDeleted = $this->checkFileCanBeDeletedBoolean($fileId);
		if($canBeDeleted)
		{
			$url = Config::get('app_config_cloud_storage.one_drive_api_file_delete_url');

			$urlWithData = str_replace($this->urlPlaceHolderItemId, $fileId, $url);

			$call = Curl::to($urlWithData)->asJson();
			$call = $this->attachHeaders($call);
			
		 	$response = $call->delete();

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;

		$compiledResponse['response'] = $response;
				        
		return $compiledResponse;
	}

	public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
	{
		$response = NULL;

		$url = Config::get('app_config_cloud_storage.one_drive_api_refresh_access_token_url');

		$dataStr  = 'client_id=' . $consClientId;
		$dataStr .= '&scope=' . urlencode('https://graph.microsoft.com/files.readwrite.all offline_access');
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
}