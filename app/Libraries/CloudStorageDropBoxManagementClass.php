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

class CloudStorageDropBoxManagementClass 
{
	protected $accessToken = NULL;
	protected $formattedAccessToken = NULL;
	protected $cloudStorageTypeCode = '';
	protected $cloudStorageTypeId = 0;

	protected $fileFolderFetchMaxCount = 0;

	protected $definedFolderTypeIdForHomeDrive = 0;

	protected $baseUrl = "";
	protected $dbHeaders;

	protected $tagForFolder = 'folder';
	protected $tagForFile = 'file';
	protected $folderFileIdPrefix = 'id:';
	protected $fileThumbnailGenFormat = 'jpeg';
	protected $fileThumbnailGenSize = 'w64h64';
	protected $fileThumbnailGenMode = 'strict';
	protected $fileThumbnailFetchMaxCount = 0;
	protected $filePreviewIsBase64 = 1;
	
	public function __construct()
    {
    	$this->fileFolderFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
    	$this->fileThumbnailFetchMaxCount = Config::get('app_config_cloud_storage.cloud_storage_file_list_size');
    }
		
	public function setBasicDetails($typeId, $typeCode)
    {   
		$this->cloudStorageTypeId = $typeId;
		$this->cloudStorageTypeCode = $typeCode;
    }
		
	public function withAccessToken($accessToken)
    {   
		$this->accessToken = $accessToken;
		$this->formattedAccessToken = "Bearer ".$accessToken;
    	$this->dbHeaders = [
    		'Authorization' => $this->formattedAccessToken
		];
    }

    public function withAppKeyMapping($appKeyMapping)
    {
    	if(isset($appKeyMapping))
    	{
    		$dropBoxAppKey = $appKeyMapping->dropbox_app_key;
    		$dropBoxAppSecret = $appKeyMapping->dropbox_app_secret;
    	}
    }
	
	private function attachHeaders($call)
	{
		if(isset($this->dbHeaders))
		{
			foreach($this->dbHeaders as $key => $val) {
				$call->withHeader($key.': '.$val);
			}
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
    
    public function fetchAccessToken($sessionCode)
    {
		$url = Config::get('app_config_cloud_storage.dropbox_api_load_account_access_token_url');

		$url = 'https://api.dropboxapi.com/oauth2/token';

        $appKey = env('DROPBOX_APP_KEY');
        $appSecret = env('DROPBOX_APP_SECRET');
        $redirectUri = env('DROPBOX_REDIRECT_URI');

		$formParamsData = array();
		$formParamsData['code'] = $sessionCode;
		$formParamsData['grant_type'] = 'authorization_code';
		$formParamsData['redirect_uri'] = $redirectUri;
		$formParamsData['client_id'] = $appKey;
		$formParamsData['client_secret'] = $appSecret;

		$data = array();
		$data['form_params'] = $formParamsData;

		$compiledUserAuthStr = $appKey . ":" . $appSecret;
		
		// $call = Curl::to($url)->withOption('USERPWD', $compiledUserAuthStr)->asJson()->withData($data);

		$call = Curl::to($url)->asJson()->withData($data);

	 	$response = $call->post();

		$fetchedAccessToken = NULL;

		if(isset($response) && isset($response->access_token) && $response->access_token != "")
		{
			$fetchedAccessToken = $response->access_token;
		}
				        
		return $response;
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
    
    public function getAllFoldersAndFiles($parentFolderName = NULL, $queryStr = "", $forFilter = FALSE)
    {
		$consFilePath = isset($parentFolderName) && trim($parentFolderName) != "" ? "/".trim($parentFolderName) : "";
		$maxResultCount = $this->fileFolderFetchMaxCount;

		$compiledFileFolderList = array();
		$hasLoadMore = 0;
		$loadMoreCursor = "";

		$hasAddFolder = 1;
		$hasAddFile = 1;
		$hasSelectFile = 1;
		$hasSearchFile = 1;

		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

		$isTokenValid = 0;

		if(isset($queryStr) && trim($queryStr) != "")
		{
			$url = Config::get('app_config_cloud_storage.dropbox_api_file_folder_search_list_url');

			$optionsData = array();
			$optionsData['path'] = "";//$consFilePath;
			$optionsData['max_results'] = $maxResultCount;
			$optionsData['file_status'] = "active";
			$optionsData['filename_only'] = false;

			$matchFieldOptionsData = array();
			$matchFieldOptionsData['include_highlights'] = false;

			// For search_v2
			$data = array();
			$data['query'] = $queryStr;
			$data['options'] = $optionsData;
			$data['match_field_options'] = $matchFieldOptionsData;
			
			$response = $this->makePostApiCall($url, $data);

			$isTokenValid = 1;

			if(isset($response) && isset($response->matches) && is_array($response->matches) && count($response->matches) > 0)
			{
				foreach ($response->matches as $indMatchEntry) {
					$indEntry = $indMatchEntry->metadata->metadata;
					
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $forFilter);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);

						$calcFileIndex = count($compiledFileFolderList) - 1;
						if($compiledFileFolderObj['isFile'] == 1 && $compiledFileFolderObj['isFileTypeImage'] == 1)
						{
							$prefixedFileId = $this->folderFileIdPrefix.$compiledFileFolderObj['fileId'];

							$fileThumbnailMapArr[$prefixedFileId] = $calcFileIndex;

							array_push($fileThumbnailFileIdArr, $prefixedFileId);
						}
					}
				}

				if($response->has_more)
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $response->cursor;
				}
			}
			else if(isset($response) && isset($response->error) && isset($response->error->{'.tag'}) && $response->error->{'.tag'} == "expired_access_token" || $response->error->{'.tag'} == "invalid_access_token")
			{
				$isTokenValid = 0;
			}
		}
		else
		{
			$url = Config::get('app_config_cloud_storage.dropbox_api_file_folder_list_url');

			$data = array();
			$data['path'] = $consFilePath;
			$data['.tag'] = $this->tagForFolder;
			$data['recursive'] = false;//true;
			$data['include_media_info'] = true;
			$data['include_deleted'] = false;
			$data['include_has_explicit_shared_members'] = false;
			$data['include_mounted_folders'] = true;
			$data['include_non_downloadable_files'] = true;
			$data['limit'] = $maxResultCount;
			
			if($forFilter == true)
			{
				$data['recursive'] = true;
			}
			
			$response = $this->makePostApiCall($url, $data);

			$isTokenValid = 1;

			if(isset($response) && isset($response->entries) && is_array($response->entries) && count($response->entries) > 0)
			{
				foreach ($response->entries as $indEntry) {
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $forFilter);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);

						$calcFileIndex = count($compiledFileFolderList) - 1;
						if($compiledFileFolderObj['isFile'] == 1 && $compiledFileFolderObj['isFileTypeImage'] == 1)
						{
							$prefixedFileId = $this->folderFileIdPrefix.$compiledFileFolderObj['fileId'];

							$fileThumbnailMapArr[$prefixedFileId] = $calcFileIndex;

							array_push($fileThumbnailFileIdArr, $prefixedFileId);
						}
					}
				}

				if($response->has_more)
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $response->cursor;
				}
			}
			else if(isset($response) && isset($response->error) && isset($response->error->{'.tag'}) && $response->error->{'.tag'} == "expired_access_token" || $response->error->{'.tag'} == "invalid_access_token")
			{
				$isTokenValid = 0;
			}
		}
		
		if(count($fileThumbnailFileIdArr) > 0)
		{
			$compiledFileFolderList = $this->fetchFileThumbnailForIdArr($fileThumbnailFileIdArr, $fileThumbnailMapArr, $compiledFileFolderList);
		}

		$apiCallData = array();
		$apiCallData['url'] = $url;
		$apiCallData['data'] = $data;
		$apiCallData['response'] = $response;
		$apiCallData['headers'] = $this->dbHeaders;

		$breadcrumbArr = $this->formulateFolderBreadCrumbArr($consFilePath);

		$folderCount = count($compiledFileFolderList);

		$compiledResponse = array();
		// $compiledResponse['apiCallData'] = $apiCallData;
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['folderCount'] = $folderCount;
		$compiledResponse['folderList'] = $compiledFileFolderList;
		$compiledResponse['breadcrumbArr'] = $breadcrumbArr;
		$compiledResponse['hasAddFolder'] = $hasAddFolder;
		$compiledResponse['hasAddFile'] = $hasAddFile;
		$compiledResponse['hasSelectFile'] = $hasSelectFile;
		$compiledResponse['hasSearchFile'] = $hasSearchFile;
		$compiledResponse['isTokenValid'] = $isTokenValid;
				        
		return $compiledResponse;
	}
    
    public function getContinuedFoldersAndFiles($parentFolderName = NULL, $queryStr = "", $cursorStr = "", $forFilter = FALSE)
    {
		$consFilePath = isset($parentFolderName) && trim($parentFolderName) != "" ? "/".trim($parentFolderName) : "";
		$maxResultCount = $this->fileFolderFetchMaxCount;

		$compiledFileFolderList = array();
		$hasLoadMore = 0;
		$loadMoreCursor = "";

		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

		if(isset($queryStr) && trim($queryStr) != "")
		{
			$url = Config::get('app_config_cloud_storage.dropbox_api_file_folder_search_list_continue_url');

			$data = array();
			$data['cursor'] = $cursorStr;
			
			$response = $this->makePostApiCall($url, $data);

			if(isset($response) && isset($response->matches) && is_array($response->matches) && count($response->matches) > 0)
			{
				foreach ($response->matches as $indMatchEntry) {
					$indEntry = $indMatchEntry->metadata->metadata;
					
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $forFilter);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);

						$calcFileIndex = count($compiledFileFolderList) - 1;
						if($compiledFileFolderObj['isFile'] == 1 && $compiledFileFolderObj['isFileTypeImage'] == 1)
						{
							$prefixedFileId = $this->folderFileIdPrefix.$compiledFileFolderObj['fileId'];

							$fileThumbnailMapArr[$prefixedFileId] = $calcFileIndex;

							array_push($fileThumbnailFileIdArr, $prefixedFileId);
						}
					}
				}

				if($response->has_more)
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $response->cursor;
				}
			}
		}
		else
		{
			$url = Config::get('app_config_cloud_storage.dropbox_api_file_folder_list_continue_url');

			$data = array();
			$data['cursor'] = $cursorStr;
			
			$response = $this->makePostApiCall($url, $data);

			if(isset($response) && isset($response->entries) && is_array($response->entries) && count($response->entries) > 0)
			{
				foreach ($response->entries as $indEntry) {
					$compiledFileFolderObj = $this->formulateFolderOrFileEntry($indEntry, $consFilePath, $forFilter);
					if(isset($compiledFileFolderObj))
					{
						array_push($compiledFileFolderList, $compiledFileFolderObj);

						$calcFileIndex = count($compiledFileFolderList) - 1;
						if($compiledFileFolderObj['isFile'] == 1 && $compiledFileFolderObj['isFileTypeImage'] == 1)
						{
							$prefixedFileId = $this->folderFileIdPrefix.$compiledFileFolderObj['fileId'];

							$fileThumbnailMapArr[$prefixedFileId] = $calcFileIndex;

							array_push($fileThumbnailFileIdArr, $prefixedFileId);
						}
					}
				}

				if($response->has_more)
				{
					$hasLoadMore = 1;
					$loadMoreCursor = $response->cursor;
				}
			}
		}
		
		if(count($fileThumbnailFileIdArr) > 0)
		{
			$compiledFileFolderList = $this->fetchFileThumbnailForIdArr($fileThumbnailFileIdArr, $fileThumbnailMapArr, $compiledFileFolderList);
		}

		$folderCount = count($compiledFileFolderList);

		$compiledResponse = array();
		$compiledResponse['hasLoadMore'] = $hasLoadMore;
		$compiledResponse['loadMoreCursor'] = $loadMoreCursor;
		$compiledResponse['folderCount'] = $folderCount;
		$compiledResponse['folderList'] = $compiledFileFolderList;
		
		// $compiledResponse['data'] = $data;
		// $compiledResponse['queryStr'] = $queryStr;
		// $compiledResponse['parentFolderName'] = $parentFolderName;
		// $compiledResponse['response'] = $response;
				        
		return $compiledResponse;
	}

	protected function formulateFolderOrFileEntry($indEntry, $consFilePath, $forFilter = FALSE)
	{
		$compiledFileFolderObj = NULL;
		if(isset($indEntry))
		{
			$indEntrTag = $indEntry->{'.tag'};
			$indEntryId = $indEntry->id;
			$indEntryId = str_replace($this->folderFileIdPrefix, "", $indEntryId);

			if($indEntrTag == $this->tagForFolder)
			{
				if(!$forFilter && $consFilePath != $indEntry->path_lower)
				{
					$thumbUrl = OrganizationClass::getOrgContentAssetFolderThumbUrl();

					$compiledFileFolderObj = array();
					$compiledFileFolderObj['isFile'] = 0;
					$compiledFileFolderObj['folderId'] = $indEntryId;
					$compiledFileFolderObj['folderName'] = $indEntry->name;
					$compiledFileFolderObj['folderPath'] = $indEntry->path_lower;
					$compiledFileFolderObj['folderDisplayPath'] = $indEntry->path_display;
					$compiledFileFolderObj['thumbUrl'] = $thumbUrl;
					$compiledFileFolderObj['baseFolderType'] = $this->definedFolderTypeIdForHomeDrive;
				}
			}
			else if($indEntrTag == $this->tagForFile)
			{
				$thumbUrl = OrganizationClass::getOrgContentAssetThumbUrl(0, $indEntry->name);
				$isFileTypeImage = checkIfFileTypeImageFromFileName($indEntry->name);
				$filePreviewStr = "";
				$hasFilePreviewStr = 0;
				$isFileTypeImageFlag = $isFileTypeImage ? 1 : 0;

				$compiledFileFolderObj = array();
				$compiledFileFolderObj['isFile'] = 1;
				$compiledFileFolderObj['fileId'] = $indEntryId;
				$compiledFileFolderObj['fileName'] = $indEntry->name;
				$compiledFileFolderObj['filePath'] = $indEntry->path_lower;
				$compiledFileFolderObj['fileDisplayPath'] = $indEntry->path_display;
				$compiledFileFolderObj['fileSize'] = $indEntry->size;
				$compiledFileFolderObj['fileSizeStr'] = CommonFunctionClass::getFileSizeStrFromSizeInBytes($indEntry->size);
				$compiledFileFolderObj['thumbUrl'] = $thumbUrl;
				$compiledFileFolderObj['isFileTypeImage'] = $isFileTypeImageFlag;
				$compiledFileFolderObj['hasFilePreviewStr'] = $hasFilePreviewStr;
				$compiledFileFolderObj['filePreviewStr'] = $filePreviewStr;
				$compiledFileFolderObj['filePreviewIsBase64'] = $this->filePreviewIsBase64;
			}
		}
		return $compiledFileFolderObj;
	}

	protected function formulateFolderBreadCrumbArr($consFilePath)
	{
		$compiledBreadcrumbArr = array();
		if(isset($consFilePath) && $consFilePath != "")
		{
			$compiledBreadcrumbObj = array();
			$compiledBreadcrumbObj['title'] = 'Home';
			$compiledBreadcrumbObj['path'] = '';
			$compiledBreadcrumbObj['baseFolderType'] = $this->definedFolderTypeIdForHomeDrive;
			array_push($compiledBreadcrumbArr, $compiledBreadcrumbObj);

			$explodedPathArr = explode("/", $consFilePath);
			$explodedPathArr = array_filter($explodedPathArr);
			foreach ($explodedPathArr as $ind => $indPathStr) {
				if($indPathStr != "")
				{
					$compPathStr = "";
					if($ind > 0)
					{
						$prefixPathArr = array_slice($explodedPathArr, 0, $ind - 1);
						$compPathStr = implode("/", $prefixPathArr);
						$compPathStr = "/".$compPathStr;
					}
					// $compPathStr = $implodedPrefixPathStr."/".$indPathStr;

					$compiledBreadcrumbObj = array();
					$compiledBreadcrumbObj['title'] = $indPathStr;
					$compiledBreadcrumbObj['path'] = $compPathStr;
					$compiledBreadcrumbObj['baseFolderType'] = $this->definedFolderTypeIdForHomeDrive;
					array_push($compiledBreadcrumbArr, $compiledBreadcrumbObj);
				}
			}
		}
		return $compiledBreadcrumbArr;
	}
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
		$fileThumbnailMapArr = array();
		$fileThumbnailFileIdArr = array();

    	$mappedDetailsArr = array();

		$url = Config::get('app_config_cloud_storage.dropbox_api_file_batch_meta_deta_url');

		$compFileIdArr = array();
		foreach ($fileIdArr as $fileId) {
			$compFileId = $this->folderFileIdPrefix.$fileId;
			array_push($compFileIdArr, $compFileId);
		}

		$data = array();
		$data['files'] = $compFileIdArr;
		
		$response = $this->makePostApiCall($url, $data);

		if(isset($response) && isset($response) && is_array($response) && count($response) > 0)
		{
			$fileResponseArr = $response;
			foreach ($fileResponseArr as $fileResponse)
			{
				$fileResult = $fileResponse->result;

				if(isset($fileResult) && isset($fileResult->name))
				{
					$indFileId = $fileResponse->file;
					$indFileId = str_replace($this->folderFileIdPrefix, "", $indFileId);

					$fileIdIndex = array_search($indFileId, $fileIdArr);
					$fileSizeBytes = isset($fileSizeArr[$fileIdIndex]) ? $fileSizeArr[$fileIdIndex] : 0;

					$fileSizeKB = CommonFunctionClass::getFileSizeInKBFromSizeInBytes($fileSizeBytes);
					if($fileSizeKB <= 0)
					{
						$fileSizeKB = 1;
					}

					$thumbUrl = OrganizationClass::getOrgContentAssetThumbUrl(0, $fileResult->name);

					$isFileTypeImage = checkIfFileTypeImageFromFileName($fileResult->name);
					$filePreviewStr = "";
					$hasFilePreviewStr = 0;
					$isFileTypeImageFlag = $isFileTypeImage ? 1 : 0;

					$compiledFileDetailsObj = array();
					$compiledFileDetailsObj['fileId'] = $indFileId;
					$compiledFileDetailsObj['fileName'] = $fileResult->name;
					$compiledFileDetailsObj['filePath'] = $fileResult->path_lower;
					$compiledFileDetailsObj['fileDisplayPath'] = $fileResult->path_display;
					$compiledFileDetailsObj['fileSizeKB'] = $fileSizeKB;
					$compiledFileDetailsObj['fileSize'] = $fileSizeBytes;
					$compiledFileDetailsObj['fileSizeStr'] = CommonFunctionClass::getFileSizeStrFromSizeInBytes($fileSizeBytes);
					$compiledFileDetailsObj['thumbUrl'] = $thumbUrl;
					$compiledFileDetailsObj['isFileTypeImage'] = $isFileTypeImageFlag;
					$compiledFileDetailsObj['hasFilePreviewStr'] = $hasFilePreviewStr;
					$compiledFileDetailsObj['filePreviewStr'] = $filePreviewStr;
					$compiledFileDetailsObj['filePreviewIsBase64'] = $this->filePreviewIsBase64;
					$compiledFileDetailsObj['fileStorageUrl'] = $fileResult->preview_url;
					$compiledFileDetailsObj['cloudStorageTypeCode'] = $this->cloudStorageTypeCode;
					$compiledFileDetailsObj['cloudStorageTypeId'] = $this->cloudStorageTypeId;

					array_push($mappedDetailsArr, $compiledFileDetailsObj);

					$calcFileIndex = count($mappedDetailsArr) - 1;
					if($isFileTypeImage == 1)
					{
						$prefixedFileId = $this->folderFileIdPrefix.$indFileId;

						$fileThumbnailMapArr[$prefixedFileId] = $calcFileIndex;

						array_push($fileThumbnailFileIdArr, $prefixedFileId);
					}
				}
					
			}
		}
		
		if(count($fileThumbnailFileIdArr) > 0)
		{
			$mappedDetailsArr = $this->fetchFileThumbnailForIdArr($fileThumbnailFileIdArr, $fileThumbnailMapArr, $mappedDetailsArr);
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
		// $compiledResponse['apiResponse'] = $response;
		$compiledResponse['mappedDetailsArr'] = $mappedDetailsArr;
		$compiledResponse['allFileDetailsFetched'] = $allFileDetailsFetched;
		$compiledResponse['fileNotFetchedErrorMsg'] = $fileNotFetchedErrorMsg;
				        
		return $compiledResponse;
	}

	protected function fetchFileThumbnailForIdArr($fileIdArr, $fileThumbnailMapArr, $mappedDetailsArr)
	{
		$fileThumbnailReqArr = array();
		foreach ($fileIdArr as $prefixedFileId)
		{
			$fileReqObjForThumb = array();
			$fileReqObjForThumb['path'] = $prefixedFileId;
			$fileReqObjForThumb['format'] = $this->fileThumbnailGenFormat;
			$fileReqObjForThumb['size'] = $this->fileThumbnailGenSize;
			$fileReqObjForThumb['mode'] = $this->fileThumbnailGenMode;

			array_push($fileThumbnailReqArr, $fileReqObjForThumb);
		}

		$totalFileThumbsToBeFetched = count($fileThumbnailReqArr);
		if($totalFileThumbsToBeFetched <= $this->fileThumbnailFetchMaxCount)
		{
			$batchThumbnailUrl = Config::get('app_config_cloud_storage.dropbox_api_file_batch_thumbnail_url');

			$batchThumbnailReqData = array();
			$batchThumbnailReqData['entries'] = $fileThumbnailReqArr;
	
			$batchThumbResponse = $this->makePostApiCall($batchThumbnailUrl, $batchThumbnailReqData);
			if(isset($batchThumbResponse) && isset($batchThumbResponse->entries) && count($batchThumbResponse->entries) > 0)
			{
				$thumbEntryArr = $batchThumbResponse->entries;
				foreach ($thumbEntryArr as $thumbEntry) 
				{
					if(isset($thumbEntry) && isset($thumbEntry->thumbnail) && $thumbEntry->thumbnail != "")
					{
						$consFileId = $thumbEntry->metadata->id;

						$consFileIndex = $fileThumbnailMapArr[$consFileId];

						if($consFileIndex >= 0)
						{
							$mappedDetailsArr[$consFileIndex]['hasFilePreviewStr'] = 1;
							$mappedDetailsArr[$consFileIndex]['filePreviewStr'] = $thumbEntry->thumbnail;
						}
					}
				}
			}
		}

		return $mappedDetailsArr;
	}
    
    public function addNewFolder($parentFolderName = "", $insFolderName)
    {
		$consFilePath = isset($parentFolderName) && trim($parentFolderName) != "" ? "/".trim($parentFolderName) : "";
		$consFilePathWithNewName = $consFilePath."/".$insFolderName;

		$consFilePathWithNewName = $this->sanitizeCompiledFileFolderPath($consFilePathWithNewName);

		$url = Config::get('app_config_cloud_storage.dropbox_api_folder_create_url');

		$data = array();
		$data['path'] = $consFilePathWithNewName;
		$data['autorename'] = false;
		
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
		$url = Config::get('app_config_cloud_storage.dropbox_api_folder_details_url');

		$data = array();
		$data['id'] = $folderId;
		
		$response = $this->makePostApiCall($url, $data);

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
		$consFolderPathWithPrefix = $this->folderFileIdPrefix.$folderId;

		$url = Config::get('app_config_cloud_storage.dropbox_api_folder_details_url');

		$data = array();
		$data['path'] = $consFolderPathWithPrefix;

		$response = $this->makePostApiCall($url, $data);

		$canBeDeleted = true;

		if(isset($response) && isset($response->path_lower))
		{
			$folderPath = $response->path_lower;

			$folderFileListUrl = Config::get('app_config_cloud_storage.dropbox_api_file_folder_list_url');

			$folderFileListData = array();
			$folderFileListData['path'] = $folderPath;
			$folderFileListData['.tag'] = $this->tagForFolder;
			$folderFileListData['recursive'] = true;
			$folderFileListData['include_media_info'] = false;
			$folderFileListData['include_deleted'] = false;
			$folderFileListData['include_has_explicit_shared_members'] = false;
			$folderFileListData['include_mounted_folders'] = true;
			$folderFileListData['include_non_downloadable_files'] = true;
			$folderFileListData['limit'] = 1;
			
			$folderFileListResponse = $this->makePostApiCall($folderFileListUrl, $folderFileListData);

			if(isset($folderFileListResponse) && isset($folderFileListResponse->entries) && is_array($folderFileListResponse->entries) && count($folderFileListResponse->entries) > 1)
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
    
    public function performFolderDelete($folderId)
    {
		$consFolderPathWithPrefix = $this->folderFileIdPrefix.$folderId;

		$response = NULL;

		$isDeleted = 0;

		$canBeDeleted = $this->checkFolderCanBeDeletedBoolean($folderId);
		if($canBeDeleted)
		{
			$url = Config::get('app_config_cloud_storage.dropbox_api_folder_delete_url');

			$data = array();
			$data['path'] = $consFolderPathWithPrefix;
			
			$response = $this->makePostApiCall($url, $data);

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;
				        
		return $compiledResponse;
	}
    
    public function uploadFile($parentFolderName, $fileObj)
    {
		$url = Config::get('app_config_cloud_storage.dropbox_api_file_upload_url');

		$consFilePath = isset($parentFolderName) && trim($parentFolderName) != "" ? "/".trim($parentFolderName) : "";		

		$consFilePath = $this->sanitizeCompiledFileFolderPath($consFilePath);

        $fileSize = $fileObj->getSize();
        $fileName = $fileObj->getClientOriginalName();
        $fileExt = $fileObj->getClientOriginalExtension();
        $fileExtWithPrefix = '.'.$fileExt;

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

	        $consFilePathWithNewName = $consFilePath."/".$fileName;
			$consFilePathWithNewName = $this->sanitizeCompiledFileFolderPath($consFilePathWithNewName);

	        $fp = fopen($orgFileRealPath, 'rb');

			$reqDbApiHeaderData = array();
			$reqDbApiHeaderData['path'] = $consFilePathWithNewName;
			$reqDbApiHeaderData['mode'] = "add";
			$reqDbApiHeaderData['autorename'] = true;
			$reqDbApiHeaderData['mute'] = false;
			$reqDbApiHeaderData['strict_conflict'] = false;

			$reqHeaderData = array();
			array_push($reqHeaderData, "Authorization: ".$this->formattedAccessToken);
			array_push($reqHeaderData, "Content-Type: application/octet-stream");
			array_push($reqHeaderData, "Dropbox-API-Arg: ".json_encode($reqDbApiHeaderData));

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
					$uplFileId = str_replace($this->folderFileIdPrefix, "", $uplFileId);

					$uplFilePath = $responseJson['path_lower'];
				}
	        }

        	FileUploadClass::removeTempCloudStorageAttachment($serverFileName);
	 	}

		$compiledResponse = array();
		$compiledResponse['fileSize'] = $fileSize;
		$compiledResponse['fileName'] = $fileName;
		$compiledResponse['uplFileId'] = $uplFileId;
		$compiledResponse['uplFilePath'] = $uplFilePath;
		$compiledResponse['uplFileName'] = $uplFileName;
		$compiledResponse['isUploaded'] = $isUploaded;
				        
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
		$consFilePathWithPrefix = $this->folderFileIdPrefix.$fileId;

		$url = Config::get('app_config_cloud_storage.dropbox_api_file_details_url');

		$data = array();
		$data['path'] = $consFilePathWithPrefix;
		$data['include_media_info'] = true;

		$response = $this->makePostApiCall($url, $data);

		$canBeDeleted = true;

		if(!isset($response) || !isset($response->is_downloadable) || !$response->is_downloadable)
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
			$url = Config::get('app_config_cloud_storage.dropbox_api_file_delete_url');

			$data = array();
			$data['path'] = $consFilePathWithPrefix;
			
			$response = $this->makePostApiCall($url, $data);

			$isDeleted = 1;
		}

		$compiledResponse = array();
		$compiledResponse['canBeDeleted'] = $canBeDeleted;
		$compiledResponse['isDeleted'] = $isDeleted;
				        
		return $compiledResponse;
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