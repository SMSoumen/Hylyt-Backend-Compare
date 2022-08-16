<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\Api\CloudStorageType;
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
use App\Libraries\CloudStorageDropBoxManagementClass;
use App\Libraries\CloudStorageGoogleDriveManagementClass;
use App\Libraries\CloudStorageOneDriveManagementClass;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class AttachmentCloudStorageManagementClass 
{
    protected $appKeyMapping = NULL;
	protected $accessToken = NULL;
	protected $cloudStorageTypeCode = NULL;
	protected $cloudStorageTypeId = NULL;

	protected $DROPBOX_TYPE_CODE = '';
	protected $GOOGLE_DRIVE_TYPE_CODE = '';
	protected $ONEDRIVE_TYPE_CODE = '';

	protected $cloudStorageIsDropBox = FALSE;
	protected $dropBoxMgmtObj = NULL;

	protected $cloudStorageIsGoogleDrive = FALSE;
	protected $googleDriveMgmtObj = NULL;

	protected $cloudStorageIsOneDrive = FALSE;
	protected $oneDriveMgmtObj = NULL;
		
	public function __construct()
    {
    	$this->DROPBOX_TYPE_CODE = CloudStorageType::$DROPBOX_TYPE_CODE;
    	$this->GOOGLE_DRIVE_TYPE_CODE = CloudStorageType::$GOOGLE_DRIVE_TYPE_CODE;
    	$this->ONEDRIVE_TYPE_CODE = CloudStorageType::$ONEDRIVE_TYPE_CODE;
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
        
    public function withStorageTypeCode($storageTypeCode)
    {
        if(isset($storageTypeCode) && trim($storageTypeCode) != "")
        {
            $cloudStorageType = CloudStorageType::byCode($storageTypeCode)->first();
            if(isset($cloudStorageType))
            {
                $this->setupCloudDependency($cloudStorageType);
            }
        }
    }
		
	public function withAccessTokenAndStorageTypeCode($accessToken, $storageTypeCode)
    {
		$this->accessToken = $accessToken;

		if(isset($storageTypeCode) && trim($storageTypeCode) != "")
		{
			$cloudStorageType = CloudStorageType::byCode($storageTypeCode)->first();
			if(isset($cloudStorageType))
			{
                $this->setupCloudDependency($cloudStorageType);
			}
		}
    }
        
    public function withAccessTokenAndStorageTypeObject($accessToken, $cloudStorageType)
    {
        $this->accessToken = $accessToken;
        $this->setupCloudDependency($cloudStorageType);
    }

    private function setupCloudDependency($cloudStorageType)
    {
        if(isset($cloudStorageType))
        {
            $this->cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;
            $this->cloudStorageTypeCode = $cloudStorageType->cloud_storage_type_code;

            if($this->cloudStorageTypeCode == $this->DROPBOX_TYPE_CODE)
            {
                $this->forDropBox();
            }
            elseif($this->cloudStorageTypeCode == $this->GOOGLE_DRIVE_TYPE_CODE)
            {
                $this->forGoogleDrive();
            }
            elseif($this->cloudStorageTypeCode == $this->ONEDRIVE_TYPE_CODE)
            {
                $this->forOneDrive();
            }
        }
    }
		
	public function forDropBox()
    {   
		$this->cloudStorageIsDropBox = TRUE;
        $this->dropBoxMgmtObj = New CloudStorageDropBoxManagementClass;
        $this->dropBoxMgmtObj->setBasicDetails($this->cloudStorageTypeId, $this->cloudStorageTypeCode);
		if(isset($this->accessToken) && $this->accessToken != '')
		{
			$this->dropBoxMgmtObj->withAccessToken($this->accessToken);
		}
        if(isset($this->appKeyMapping))
        {
                Log::info('forDropBox appKeyMapping exists : ');
            $this->dropBoxMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
    }
		
	public function forGoogleDrive()
    {   
		$this->cloudStorageIsGoogleDrive = TRUE;
        $this->googleDriveMgmtObj = New CloudStorageGoogleDriveManagementClass;
        $this->googleDriveMgmtObj->setBasicDetails($this->cloudStorageTypeId, $this->cloudStorageTypeCode);
		if(isset($this->accessToken) && $this->accessToken != '')
		{
			$this->googleDriveMgmtObj->withAccessToken($this->accessToken);
		}
        if(isset($this->appKeyMapping))
        {
            $this->googleDriveMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
    }
		
	public function forOneDrive()
    {   
		$this->cloudStorageIsOneDrive = TRUE;
        $this->oneDriveMgmtObj = New CloudStorageOneDriveManagementClass;
        $this->oneDriveMgmtObj->setBasicDetails($this->cloudStorageTypeId, $this->cloudStorageTypeCode);
		if(isset($this->accessToken) && $this->accessToken != '')
		{
			$this->oneDriveMgmtObj->withAccessToken($this->accessToken);
		}
        if(isset($this->appKeyMapping))
        {
            $this->oneDriveMgmtObj->withAppKeyMapping($this->appKeyMapping);
        }
    }
    
    public function fetchAccessToken($sessionCode)
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->fetchAccessToken($sessionCode);
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->fetchAccessToken($sessionCode);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->fetchAccessToken($sessionCode);
        }
        return $response;
    }

    public function refreshAccessToken($refreshToken, $consClientId, $consClientSecret)
    {
        $response = NULL;
        // if($this->cloudStorageIsDropBox)
        // {
        //     $response = $this->dropBoxMgmtObj->fetchAccessToken($refreshToken, $consClientId, $consClientSecret);
        // }
        // else 
        if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->refreshAccessToken($refreshToken, $consClientId, $consClientSecret);
        }
        return $response;
    }

    public function checkAccessTokenValidity()
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->checkAccessTokenValidity();
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->checkAccessTokenValidity();
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->checkAccessTokenValidity();
        }
        return $response;
    }
    
    public function getAllFoldersAndFiles($parentFolderName, $queryStr, $baseFolderTypeId = NULL)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr, FALSE);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr, "", $baseFolderTypeId);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr);
    	}
    	return $response;
    }  
    
    public function getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId = NULL)
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, FALSE);
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId = NULL);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr);
        }
        return $response;
    } 
    
    public function getFilteredFoldersAndFiles($parentFolderName, $queryStr, $baseFolderTypeId = NULL, $isGeneralExhausted = FALSE)
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr, TRUE);
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->getFilteredFoldersAndFiles($parentFolderName, $queryStr, "", $baseFolderTypeId, $isGeneralExhausted);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr);
        }
        return $response;
    }  
    
    public function getContinuedFilteredFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId = NULL, $isGeneralExhausted = FALSE)
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, TRUE);
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->getContinuedFilteredFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, $baseFolderTypeId = NULL, $isGeneralExhausted);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr);
        }
        return $response;
    } 
    
    public function getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr)
    {
        $response = NULL;
        if($this->cloudStorageIsDropBox)
        {
            $response = $this->dropBoxMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        else if($this->cloudStorageIsGoogleDrive)
        {
            $response = $this->googleDriveMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        else if($this->cloudStorageIsOneDrive)
        {
            $response = $this->oneDriveMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
        }
        return $response;
    }
    
    public function uploadFile($parentFolderName, $fileObj)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->uploadFile($parentFolderName, $fileObj);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->uploadFile($parentFolderName, $fileObj);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->uploadFile($parentFolderName, $fileObj);
    	}
    	return $response;
    }
    
    public function checkFileCanBeDeleted($fileId)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->checkFileCanBeDeleted($fileId);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->checkFileCanBeDeleted($fileId);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->checkFileCanBeDeleted($fileId);
    	}
    	return $response;
    }
    
    public function performFileDelete($fileId)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->performFileDelete($fileId);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->performFileDelete($fileId);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->performFileDelete($fileId);
    	}
    	return $response;
    }
    
    public function addNewFolder($parentFolderName, $folderName)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->addNewFolder($parentFolderName, $folderName);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->addNewFolder($parentFolderName, $folderName);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->addNewFolder($parentFolderName, $folderName);
    	}
    	return $response;
    }
    
    public function checkFolderCanBeDeleted($folderId)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->checkFolderCanBeDeleted($folderId);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->checkFolderCanBeDeleted($folderId);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->checkFolderCanBeDeleted($folderId);
    	}
    	return $response;
    }
    
    public function performFolderDelete($folderId)
    {
    	$response = NULL;
    	if($this->cloudStorageIsDropBox)
    	{
    		$response = $this->dropBoxMgmtObj->performFolderDelete($folderId);
    	}
    	else if($this->cloudStorageIsGoogleDrive)
    	{
    		$response = $this->googleDriveMgmtObj->performFolderDelete($folderId);
    	}
    	else if($this->cloudStorageIsOneDrive)
    	{
    		$response = $this->oneDriveMgmtObj->performFolderDelete($folderId);
    	}
    	return $response;
    }
    
}