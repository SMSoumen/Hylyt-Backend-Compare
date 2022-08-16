<?php 

namespace App\Libraries;

use Config;
use Image;
use File;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationServer;
use App\Models\Org\OrganizationUser;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeBadge;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\Api\OrgEmployeeFolder;
use App\Models\Org\Api\OrgEmployeeTag;
use App\Models\Org\Api\OrgEmployeeSource;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Api\Appuser;
use DB;
use App;
use Input;
use Crypt;
use App\Libraries\MailClass;

class TempOrganizationClass 
{
	public static function getOrgBaseAssetFolderDir($orgId)
	{  
        $orgFolderDirPath = "";
        if($orgId > 0)
        {        	
	    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($orgServer))
			{
				$isAppServer = $orgServer->is_app_file_server;
				
				$publicFolder = Config::get("app_config.publicUrlPath");
				$baseAssetFolder = Config::get("app_config.temp_org_base_asset_folder_name");				
				$orgAssetFoldername = TempOrganizationClass::getOrgBaseAssetFolderName($orgId);
				
				$orgFolderDirPath = $baseAssetFolder."/".$orgAssetFoldername."";
				if($isAppServer == 1)
				{
					$orgFolderDirPath = $publicFolder.$orgFolderDirPath;
				}	
			}
		}
		else
		{
			$publicFolder = Config::get("app_config.publicUrlPath");
			$baseAssetFolder = Config::get("app_config.temp_per_base_asset_folder_name");
			$orgFolderDirPath = $publicFolder.$baseAssetFolder;		
		}
		return $orgFolderDirPath;
	}

	public static function getOrgBaseAssetFolderName($orgId)
	{  
        $orgFolderName = "";
        if($orgId > 0)
        {
			$orgCode = OrganizationClass::getOrgCode($orgId);
			if($orgCode != "")
				$orgFolderName = $orgId."_".$orgCode;
		}
		return $orgFolderName;
	}
	
	public static function getOrgContentAssetDirPath($orgId)
	{
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_content_attachment_folder_name');
		else
			$assetDirName = Config::get('app_config.per_content_attachment_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgGroupPhotoDirPath($orgId)
	{
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_group_photo_folder_name');
		else
			$assetDirName = Config::get('app_config.per_group_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgPhotoDirPath($orgId)
	{
		$orgId = 0;
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		$assetDirName = Config::get('app_config.per_org_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgEmployeePhotoAssetDirPath($orgId)
	{
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_employee_photo_folder_name');
		else
			$assetDirName = Config::get('app_config.per_employee_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgMlmNotificationAssetDirPath($orgId)
	{
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_mlm_notif_folder_name');
		else
			$assetDirName = Config::get('app_config.per_mlm_notif_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgMlmContentAdditionAssetDirPath($orgId)
	{
		$orgAssetBasePath = TempOrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_mlm_content_addition_folder_name');
		else
			$assetDirName = Config::get('app_config.per_mlm_content_addition_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}	
}