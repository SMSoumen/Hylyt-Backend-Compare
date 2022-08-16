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

class CloudStorageGoogleDriveManagementClass 
{
	protected $apiKey = NULL;
	protected $accessToken = NULL;
	protected $formattedAccessToken = NULL;
	protected $cloudStorageTypeCode = '';
	protected $cloudStorageTypeId = 0;

	protected $fileFolderFetchMaxCount = 0;

	protected $baseUrl = "";
	protected $dbHeaders;

	protected $mimeTypeForFolder = 'application/vnd.google-apps.folder';

	protected $definedFolderTypeIdForHomeDrive = 0;
	protected $definedFolderNameForRootDrive = 'My Drive';
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
	
	public function __construct()
    {
    	$this->fileFolderFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
    	$this->fileThumbnailFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
		$this->apiKey = env('GOOGLE_API_KEY');
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
    		$googleApiKey = $appKeyMapping->google_api_key;
        Log::info('googleApiKey: '.$googleApiKey);
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
    
    public function getAllFoldersAndFilesREFFFE($parentFolderId = NULL, $queryStr = "", $cursorStr = "", $baseFolderTypeId = NULL)
    {
        $driveService = $this->service;
        $files = array();
        // [START drive_search_files]
        $pageToken = null;
        do {
            $response = $driveService->files->listFiles(array(
                'q' => "mimeType='image/jpeg'",
                'spaces' => 'drive',
                'pageToken' => $pageToken,
                'fields' => 'nextPageToken, files(id, name)',
            ));
            foreach ($response->files as $file) {
                printf("Found file: %s (%s)\n", $file->name, $file->id);
            }
            // [START_EXCLUDE silent]
            array_push($files, $response->files);
            // [END_EXCLUDE]

            $pageToken = $response->pageToken;
        } while ($pageToken != null);
        // [END drive_search_files]
        return $files;
    }

    public function checkAccessTokenValidity()
    {
    	$response = array();

    	$isTokenValid = 0;

    	$fileFolderListResponse = $this->getAllFoldersAndFiles($this->definedFolderIdForRootDrive);
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
		$data['key'] = $this->apiKey;
		$data['pageSize'] = $maxResultCount;
		$data['fields'] = 'files(id,name,iconLink,webViewLink,webContentLink,size,thumbnailLink,mimeType,viewersCanCopyContent,starred),nextPageToken,incompleteSearch';
		// $data['sharedWithMe'] = 'true';
		$data['includeItemsFromAllDrives'] = true;
		$data['supportsAllDrives'] = true;

		// $data['corpora'] = 'user';

		// $data['supportsTeamDrives'] = 'true';
		// $data['corpora'] = 'allDrives';
		// $data['corpus'] = 'user';
		// $data['spaces'] = 'drive';
		// $data['ownedByMe'] = true;
		// $data['shared'] = true;

		$url = Config::get('app_config_cloud_storage.google_drive_api_file_folder_list_url');

		$compiledQueryStr = "trashed=false"; 


		if(isset($queryStr) && trim($queryStr) != "")
		{
			// $compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
			// $compiledQueryStr .= "'".$this->definedFolderIdForRootDrive."' in parents";		
		}
		else if(isset($parentFolderId) && trim($parentFolderId) != "")
		{
			if($parentFolderId == $this->definedFolderIdForSharedDrive)
			{
				$hasAddFolder = 0;
				$hasAddFile = 0;

				$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
				$compiledQueryStr .= "sharedWithMe=true";
			}
			else
			{
				$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
				$compiledQueryStr .= "'".$parentFolderId."' in parents";
			}
		}
		else
		{
			$rootFolderId = $this->definedFolderIdForRootDrive;

			$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
			$compiledQueryStr .= "'".$rootFolderId."' in parents";
		}

		if($hasCursor)
		{
			$data['pageToken'] = $cursorStr;
		}

		if(isset($queryStr) && trim($queryStr) != "")
		{
			$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
			$compiledQueryStr .= "fullText contains '".$queryStr."'";
			// $compiledQueryStr .= "name contains '".$queryStr."'";
		}

		if($compiledQueryStr != "")
		{
			$compiledQueryStr = str_replace(" ", "%20", $compiledQueryStr);
			$compiledQueryStr = str_replace("'", "%22", $compiledQueryStr);

			$data['q'] = $compiledQueryStr;
		}

		$isTokenValid = 0;

		if(isset($parentFolderId) && trim($parentFolderId) != "")
		{
			$response = $this->makeGetApiCall($url, $data);

			$isTokenValid = 1;

			if(isset($response) && isset($response->files) && is_array($response->files) && count($response->files) > 0)
			{
				foreach ($response->files as $indEntry) {
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $baseFolderTypeId);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);
					}
				}

				if(isset($response->nextPageToken))
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $response->nextPageToken;
				}
			}

			if(isset($response) && isset($response->error) && isset($response->error->code) && $response->error->code == 401)
			{
				$isTokenValid = 0;
			}

			$apiCallData['url'] = $url;
			$apiCallData['data'] = $data;
			$apiCallData['response'] = $response;
		}
		else
		{
			$hasAddFolder = 0;
			$hasAddFile = 0;
			$hasSelectFile = 0;
			$hasSearchFile = 0;

			$compiledFileFolderList = $this->getBaseFolderDrives($parentFolderId, $queryStr, $cursorStr);
			$isTokenValid = 1;
		}


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
			$breadcrumbArr = $this->formulateFolderBreadCrumbArr($parentFolderId, $baseFolderTypeId);
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


		$url = Config::get('app_config_cloud_storage.google_drive_api_file_folder_list_url');

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
			$indEntryMimeType = $indEntry->mimeType;
			$indEntryId = $indEntry->id;

			if($indEntryMimeType == $this->mimeTypeForFolder)
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
			else
			{
				$thumbUrl = OrganizationClass::getOrgContentAssetThumbUrl(0, $indEntry->name);
				$isFileTypeImage = checkIfFileTypeImageFromFileName($indEntry->name);
				$filePreviewStr = isset($indEntry->thumbnailLink) && $indEntry->thumbnailLink != '' && $isFileTypeImage ? $indEntry->thumbnailLink : '';
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

		if(isset($parentFolderId) && $parentFolderId != "")
		{
			$compiledBreadcrumbObj = array();
			$compiledBreadcrumbObj['title'] = 'Home';
			$compiledBreadcrumbObj['path'] = '';
			$compiledBreadcrumbObj['baseFolderType'] = $this->definedFolderTypeIdForHomeDrive;
			array_push($compiledBreadcrumbArr, $compiledBreadcrumbObj);

			$consParentFolderId = $parentFolderId;
			$parentsExhausted = false;

			$i = 1;

			$fetchedBreadcrumbArr = array();

			if($parentFolderId == $this->definedFolderIdForRootDrive || (isset($baseFolderTypeId) && $baseFolderTypeId == $this->definedFolderTypeIdForRootDrive))
			{
				$compiledRootBreadcrumbObj = array();
				$compiledRootBreadcrumbObj['title'] = $this->definedFolderNameForRootDrive;
				$compiledRootBreadcrumbObj['path'] = $this->definedFolderIdForRootDrive;
				$compiledRootBreadcrumbObj['baseFolderType'] = $this->definedFolderTypeIdForRootDrive;
				array_push($compiledBreadcrumbArr, $compiledRootBreadcrumbObj);
			}
			elseif($parentFolderId == $this->definedFolderIdForSharedDrive || (isset($baseFolderTypeId) && $baseFolderTypeId == $this->definedFolderTypeIdForSharedDrive))
			{
				$compiledSharedBreadcrumbObj = array();
				$compiledSharedBreadcrumbObj['title'] = $this->definedFolderNameForSharedDrive;
				$compiledSharedBreadcrumbObj['path'] = $this->definedFolderIdForSharedDrive;
				$compiledSharedBreadcrumbObj['baseFolderType'] = $this->definedFolderTypeIdForSharedDrive;
				array_push($compiledBreadcrumbArr, $compiledSharedBreadcrumbObj);
			}
			
			if($parentFolderId != $this->definedFolderIdForRootDrive && $parentFolderId != $this->definedFolderIdForSharedDrive)
			{
				do
				{
					$url = Config::get('app_config_cloud_storage.google_drive_api_file_details_url');

					$data = array();
					$data['key'] = $this->apiKey;
					$data['fields'] = 'id,name,mimeType,parents';

					$urlWithIdAppended = $url.'/'.$consParentFolderId;

					$folderDetailsResponse = $this->makeGetApiCall($urlWithIdAppended, $data);

					$compiledResponseArr['data_'.$i] = $data;
					$compiledResponseArr['urlWithIdAppended_'.$i] = $urlWithIdAppended;
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

						if(isset($folderDetailsResponse->parents) && count($folderDetailsResponse->parents) > 0)
						{
							$consParentFolderId = $folderDetailsResponse->parents[0];
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
							array_push($fetchedBreadcrumbArr, $compiledBreadcrumbObj);
						}
					}
					else
					{
						$parentsExhausted = true;
					}
				}while(!$parentsExhausted);
			}

				

			if(count($fetchedBreadcrumbArr) > 0)
			{
				$reversedBreadcrumbArr = array_reverse($fetchedBreadcrumbArr);

				$compiledBreadcrumbArr = array_merge($compiledBreadcrumbArr, $reversedBreadcrumbArr);
			}
		}
		// $compiledResponse = array();
		// $compiledResponse['compiledBreadcrumbArr'] = $compiledBreadcrumbArr;
		// $compiledResponse['compiledResponseArr'] = $compiledResponseArr;

		return $compiledBreadcrumbArr;//$compiledResponse;
	}
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
    	$consFilePath = '';

		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

    	$mappedDetailsArr = array();

		$url = Config::get('app_config_cloud_storage.google_drive_api_file_details_url');

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
					$compiledFileDetailsObj['fileStorageUrl'] = $fileResponse->webViewLink;
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
		$url = Config::get('app_config_cloud_storage.google_drive_api_folder_create_url');

		$data = array();
		$data['name'] = $insFolderName;
		$data['mimeType'] = $this->mimeTypeForFolder;

		if(isset($parentFolderId) && trim($parentFolderId) != "")
		{
			$parentFolderId = trim($parentFolderId);
			$data['parents'] = [ $parentFolderId ];
		}

		$urlWithKey = $url."?key=".$this->apiKey;
		
		$response = $this->makePostApiCall($urlWithKey, $data);

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
		$url = Config::get('app_config_cloud_storage.google_drive_api_folder_details_url');

		$data = array();
		$data['key'] = $this->apiKey;
		$data['fields'] = 'id,name,size,mimeType,starred,capabilities(canDelete)';

		$urlWithIdAppended = $url.'/'.$folderId;
		
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

		$fileListData = array();
		$fileListData['key'] = $this->apiKey;
		$fileListData['pageSize'] = 1;

		$fileListUrl = Config::get('app_config_cloud_storage.google_drive_api_file_folder_list_url');

		$compiledQueryStr = "trashed=false"; 
		$compiledQueryStr .= $compiledQueryStr != "" ? " and " : "";
		$compiledQueryStr .= "'".$folderId."' in parents";

		if($compiledQueryStr != "")
		{
			$compiledQueryStr = str_replace(" ", "%20", $compiledQueryStr);
			$compiledQueryStr = str_replace("'", "%22", $compiledQueryStr);

			$fileListData['q'] = $compiledQueryStr;
		}
		
		$fileListResponse = $this->makeGetApiCall($fileListUrl, $fileListData);

		if(isset($fileListResponse) && isset($fileListResponse->files) && is_array($fileListResponse->files) && count($fileListResponse->files) > 0)
		{
			$canBeDeleted = false;
		}
		else
		{
			$folderDetailsUrl = Config::get('app_config_cloud_storage.google_drive_api_folder_details_url');

			$folderDetailsData = array();
			$folderDetailsData['key'] = $this->apiKey;
			$folderDetailsData['fields'] = 'id,name,size,mimeType,starred,capabilities(canDelete)';

			$urlWithIdAppended = $folderDetailsUrl.'/'.$folderId;

			$folderDetailsResponse = $this->makeGetApiCall($urlWithIdAppended, $folderDetailsData);

			if(!isset($folderDetailsResponse) || !isset($folderDetailsResponse->capabilities) || !isset($folderDetailsResponse->capabilities->canDelete) || !$folderDetailsResponse->capabilities->canDelete)
			{
				$canBeDeleted = false;
			}
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
			$url = Config::get('app_config_cloud_storage.google_drive_api_folder_delete_url');

			$urlWithData = $url."/".$folderId."/trash"."?key=".$this->apiKey;

			$call = Curl::to($urlWithData)->asJson();
			$call = $this->attachHeaders($call);
			
		 	$response = $call->post();//delete();

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;
				        
		return $compiledResponse;
	}
    
    public function uploadFile($parentFolderId, $fileObj)
    {
		$url = Config::get('app_config_cloud_storage.google_drive_api_file_upload_url');
		$url .= '?key='.$this->apiKey.'&uploadType='.'multipart';//'media';

        $fileSize = $fileObj->getSize();
        $fileName = $fileObj->getClientOriginalName();
        $fileExt = $fileObj->getClientOriginalExtension();
        $fileExtWithPrefix = '.'.$fileExt;
        $fileMimeType = getMimeTypeFromFilename($fileName);

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

        if(isset($serverFileName) && $serverFileName != "")
        {
        	$projectBasePath = "/var/www/html/public";
        	$orgAssetDirPath = OrganizationClass::getTempCloudStorageAssetDirPath();
	        $orgFileRealPath = $projectBasePath."/".$orgAssetDirPath."/".$serverFileName;

	        $fp = fopen($orgFileRealPath, 'rb');

			$reqHeaderData = array();
			array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
			array_push($reqHeaderData, "Content-Type: $fileMimeType {title:'$fileNameWithOutExt'}");
			array_push($reqHeaderData, "Content-Length: $fileSize");


			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaderData);
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
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

					if($uplFileId != '')
					{
						$renameUrl = Config::get('app_config_cloud_storage.google_drive_api_file_rename_url');
						$renameUrlWithData = $renameUrl."/".$uplFileId.'?key='.$this->apiKey;

						$renameFileData = array();
						$renameFileData['name'] = $fileName;
						$renameFileData['mimeType'] = $fileMimeType;

						if(isset($parentFolderId) && trim($parentFolderId) != "")
						{
							$parentFolderId = trim($parentFolderId);

							$renameUrlWithData .= '&addParents='.$parentFolderId.'';
						}

						$renameCall = Curl::to($renameUrlWithData)->asJson()->withData($renameFileData);
						$renameCall = $this->attachHeaders($renameCall);
						
					 	$renameCallResponse = $renameCall->patch();
						if(isset($renameCallResponse) && isset($renameCallResponse->id) && $renameCallResponse->id != '')
						{
							$uplFileName = $renameCallResponse->name;
						}
					}
				}
	        }

        	FileUploadClass::removeTempCloudStorageAttachment($serverFileName);
	 	}

		$compiledResponse = array();
		$compiledResponse['fileSize'] = $fileSize;
		$compiledResponse['fileMimeType'] = $fileMimeType;
		$compiledResponse['fileName'] = $fileName;
		$compiledResponse['uplFileId'] = $uplFileId;
		$compiledResponse['uplFileName'] = $uplFileName;
		$compiledResponse['isUploaded'] = $isUploaded;

		// $compiledResponse['response'] = $response;
		// $compiledResponse['reqHeaderData'] = $reqHeaderData;
		// $compiledResponse['renameCallResponse'] = $renameCallResponse;
				        
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
		$url = Config::get('app_config_cloud_storage.google_drive_api_file_details_url');

		$data = array();
		$data['key'] = $this->apiKey;
		$data['fields'] = 'id,name,size,mimeType,starred,capabilities(canDelete)';

		$urlWithIdAppended = $url.'/'.$fileId;

		$response = $this->makeGetApiCall($urlWithIdAppended, $data);

		$canBeDeleted = true;

		if(!isset($response) || !isset($response->capabilities) || !isset($response->capabilities->canDelete) || !$response->capabilities->canDelete)
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
			$url = Config::get('app_config_cloud_storage.google_drive_api_file_delete_url');

			// $urlWithData = $url."/".$fileId."?key=".$this->apiKey;
			$urlWithData = $url."/".$fileId."/trash"."?key=".$this->apiKey;

			$call = Curl::to($urlWithData)->asJson();
			$call = $this->attachHeaders($call);
			
		 	$response = $call->post();//delete();

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

		$url = Config::get('app_config_cloud_storage.google_drive_api_refresh_access_token_url');

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
}