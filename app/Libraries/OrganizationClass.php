<?php 

namespace App\Libraries;

use Config;
use Image;
use File;
use App\Models\Org\Api\OrgBackup;
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
use App\Http\Traits\OrgCloudMessagingTrait;
use App\Libraries\CommonFunctionClass;
use Illuminate\Support\Facades\Log;

class OrganizationClass 
{
    use OrgCloudMessagingTrait;
    
	public static function createOrganizationDependencies($orgId)
	{
		OrganizationClass::createOrganizationDatabase($orgId);
		OrganizationClass::createOrganizationAssetFolders($orgId);
	}
	
	public static function createOrganizationDatabase($orgId)
	{
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {
        	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($orgServer))
			{
				$isAppServer = $orgServer->is_app_db_server;
				$orgDbName = $orgServer->dbname;

				if($isAppServer == 1)
				{
		        	DB::beginTransaction();
					DB::statement('CREATE DATABASE IF NOT EXISTS '.$orgDbName);
					DB::commit();

					$orgCreateTableQueries = Config::get('app_config_sql.create_table_sql');

					DB::connection($orgDbConName)->beginTransaction();

					if(isset($orgCreateTableQueries) && count($orgCreateTableQueries) > 0)
					{
						foreach ($orgCreateTableQueries as $orgCreateTableQuery) {

							if($orgCreateTableQuery != "")
								DB::connection($orgDbConName)->statement($orgCreateTableQuery);
						}

					}

					DB::connection($orgDbConName)->commit();
				}
			}
        }			
	}
	
	public static function createOrganizationAssetFolders($orgId)
	{
    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_file_server;
			$hostname = $orgServer->file_host;
			
			$attDir = OrganizationClass::getOrgContentAssetDirPath($orgId);
			$attThumbDir = $attDir.'/'.Config::get('app_config.thumb_photo_folder_name');
			$grpPhotoDir = OrganizationClass::getOrgGroupPhotoDirPath($orgId);
			$grpPhotoThumbDir = $grpPhotoDir.'/'.Config::get('app_config.thumb_photo_folder_name');
			$contDir = OrganizationClass::getOrgMlmContentAdditionAssetDirPath($orgId);
			$contThumbDir = $contDir.'/'.Config::get('app_config.thumb_photo_folder_name');
			$notifDir = OrganizationClass::getOrgMlmNotificationAssetDirPath($orgId);
			$empPhotoDir = OrganizationClass::getOrgEmployeePhotoAssetDirPath($orgId);
			
			if($isAppServer == 1)
			{				
				OrganizationClass::validateAndCreateLocalDir($attDir);
				OrganizationClass::validateAndCreateLocalDir($attThumbDir);
				OrganizationClass::validateAndCreateLocalDir($grpPhotoDir);
				OrganizationClass::validateAndCreateLocalDir($grpPhotoThumbDir);
				OrganizationClass::validateAndCreateLocalDir($contDir);
				OrganizationClass::validateAndCreateLocalDir($contThumbDir);
				OrganizationClass::validateAndCreateLocalDir($notifDir);
				OrganizationClass::validateAndCreateLocalDir($empPhotoDir);
			}
			else
			{
				OrganizationClass::validateAndCreateRemoteDir($hostname, $attDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $attThumbDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $grpPhotoDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $grpPhotoThumbDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $contDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $contThumbDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $notifDir);
				OrganizationClass::validateAndCreateRemoteDir($hostname, $empPhotoDir);
			}		
		}
	}
	
	public static function validateAndCreateLocalDir($path)
	{
		if (isset($path) && $path != "" && !File::isDirectory($path)) 
        {
        	$oldmask = umask(0);
        	$result = File::makeDirectory($path, 0777, true, true);
        	umask($oldmask);
		}
	}
	
	public static function validateAndCreateRemoteDir($host, $path)
	{
		$postData = array();
		$postData['dirPath'] = $path;
		
		$createDirUrl = $host.Config::get('app_config.create_remote_org_folder_url_suffix');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_URL, $createDirUrl);
		curl_exec($ch);
		curl_close($ch);
	}

	public static function deleteOrganizationDependencies($orgId)
	{
		OrganizationClass::deleteOrganizationDatabase($orgId);
		OrganizationClass::deleteOrganizationAssetFolders($orgId);
	}
	
	public static function deleteOrganizationDatabase($orgId)
	{
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {
        	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($orgServer))
			{
				$isAppServer = $orgServer->is_app_db_server;
				$orgDbName = $orgServer->dbname;

				if($isAppServer == 1)
				{
		        	DB::beginTransaction();
					DB::statement('DROP DATABASE IF EXISTS '.$orgDbName);
					DB::commit();

					DB::connection($orgDbConName)->commit();
				}
			}
        }			
	}
	
	public static function deleteOrganizationAssetFolders($orgId)
	{
    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_file_server;
			$hostname = $orgServer->file_host;
			
			$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
			
			if($isAppServer == 1)
			{				
				OrganizationClass::validateAndDeleteLocalDir($orgAssetBasePath);
			}
			else
			{
				OrganizationClass::validateAndDeleteRemoteDir($hostname, $orgAssetBasePath);
			}		
		}
	}
	
	public static function validateAndDeleteLocalDir($path)
	{
		if (isset($path) && $path != "" && File::isDirectory($path)) 
        {
        	//$result = File::deleteDirectory($path);
		}
	}
	
	public static function validateAndDeleteRemoteDir($host, $path)
	{
		$postData = array();
		$postData['dirPath'] = $path;
		
		$deleteDirUrl = $host.Config::get('app_config.delete_remote_org_folder_url_suffix');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_URL, $deleteDirUrl);
		curl_exec($ch);
		curl_close($ch);
	}

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
				$baseAssetFolder = Config::get("app_config.org_base_asset_folder_name");				
				$orgAssetFoldername = OrganizationClass::getOrgBaseAssetFolderName($orgId);
				
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
			$baseAssetFolder = Config::get("app_config.per_base_asset_folder_name");
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

	public static function getOrganizationWebAppAccessUrl($orgId)
	{  
        $orgWebUrl = "https://web.sociorac.com/";
        if($orgId > 0)
        {
			$orgCode = OrganizationClass::getOrgCode($orgId);
			if($orgCode != "")
			{
				$orgWebUrl .= "org/".$orgCode;
			}
		}
		return $orgWebUrl;
	}

	public static function getOrgBaseBackupFolderDir($orgId)
	{  
        $orgFolderDirPath = "";
        if($orgId > 0)
        {        	
	    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($orgServer))
			{
				$isAppServer = $orgServer->is_app_file_server;
				
				$publicFolder = Config::get("app_config.publicUrlPath");
				$baseBackupFolder = Config::get("app_config.org_base_backup_folder_name");				
				$orgAssetFoldername = OrganizationClass::getOrgBaseAssetFolderName($orgId);
				
				$orgFolderDirPath = $baseBackupFolder."/".$orgAssetFoldername."";
				if($isAppServer == 1)
				{
					$orgFolderDirPath = $publicFolder.$orgFolderDirPath;
				}	
			}
		}
		else
		{
			$publicFolder = Config::get("app_config.publicUrlPath");
			$baseBackupFolder = Config::get("app_config.per_base_backup_folder_name");
			$orgFolderDirPath = $publicFolder.$baseBackupFolder;		
		}
		return $orgFolderDirPath;
	}

	public static function configureConnectionForOrganization($orgId)
	{
		$orgDbConName = "";

		$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_db_server;
			$orgDbName = $orgServer->dbname;

        	$orgDbConName = 'orgDb'.$orgId;

			// Just get access to the config. 
		    $config = App::make('config');

		    // Will contain the array of connections that appear in our database config file.
		    $connections = $config->get('database.connections');

		    // This line pulls out the default connection by key (by default it's `mysql`)
		    $defaultConnection = $connections[$config->get('database.default')];

		    // Now we simply copy the default connection information to our new connection.
		    $newConnection = $defaultConnection;
		    // Override the database name.
	        $newConnection['driver']   = 'mysql';
		    $newConnection['database'] = $orgDbName;

		    if($isAppServer == 0)
		    {
				$orgHost = $orgServer->host;
				$orgUserName = $orgServer->username;
				$orgPassword = $orgServer->password;

			    $newConnection['host'] = $orgHost;
			    $newConnection['username'] = $orgUserName;
			    $newConnection['password'] = $orgPassword;		    	
		    }

		    // This will add our new connection to the run-time configuration for the duration of the request.
		    App::make('config')->set('database.connections.'.$orgDbConName, $newConnection);
		}
		return $orgDbConName;
	}
	
	public static function getOrgContentAssetDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_content_attachment_folder_name');
		else
			$assetDirName = Config::get('app_config.per_content_attachment_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgGroupPhotoDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
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
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		$assetDirName = Config::get('app_config.per_org_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getTempCloudStorageAssetDirPath()
	{
		$orgId = 0;
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		$assetDirName = Config::get('app_config.cloud_storage_temp_attachment_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}

	public static function getOrgEmployeePhotoAssetDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_employee_photo_folder_name');
		else
			$assetDirName = Config::get('app_config.per_employee_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgMlmNotificationAssetDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_mlm_notif_folder_name');
		else
			$assetDirName = Config::get('app_config.per_mlm_notif_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgMlmContentAdditionAssetDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_mlm_content_addition_folder_name');
		else
			$assetDirName = Config::get('app_config.per_mlm_content_addition_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgBackupContentAssetDirPath($orgId)
	{
		$orgAssetBasePath = OrganizationClass::getOrgBaseBackupFolderDir($orgId);
		if($orgId > 0)
			$assetDirName = Config::get('app_config.org_backup_content_attachment_folder_name');
		else
			$assetDirName = Config::get('app_config.per_backup_content_attachment_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getAppuserProfilePhotoDirPath()
	{
		$orgId = 0;
		$orgAssetBasePath = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
		$assetDirName = Config::get('app_config.per_appuser_photo_folder_name');
		$assetDirPath = $orgAssetBasePath."/".$assetDirName;
		return $assetDirPath;
	}
	
	public static function getOrgEmployeePhotoAssetUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
			$orgAssetDirPath = OrganizationClass::getOrgEmployeePhotoAssetDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgMlmNotificationAssetUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
	        $orgAssetDirPath = OrganizationClass::getOrgMlmNotificationAssetDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgMlmContentAdditionAssetUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
	        $orgAssetDirPath = OrganizationClass::getOrgMlmContentAdditionAssetDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgGroupPhotoUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
	        $orgAssetDirPath = OrganizationClass::getOrgGroupPhotoDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgGroupPhotoThumbUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
	        $orgAssetDirPath = OrganizationClass::getOrgGroupPhotoDirPath($orgId);
	        $orgAssetDirPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgPhotoUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
			$orgId = 0;
	        $orgAssetDirPath = OrganizationClass::getOrgPhotoDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgPhotoThumbUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
			$orgId = 0;
	        $orgAssetDirPath = OrganizationClass::getOrgPhotoDirPath($orgId);
	        $orgAssetDirPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgContentAssetUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
	        $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgContentAssetThumbUrl_CantUser($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
			$consFilename = $serverFilename;
			$fileNameExpArr = explode(".", $consFilename);
		    $extension = isset($fileNameExpArr) ? end($fileNameExpArr) : "";
			$isTypeImage = checkIfFileTypeImageFromExtension($extension);
	        if($isTypeImage)
	        {
	        	$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId).'/'.'thumb';
	        	$serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
	        }
			return $serverFileUrl;
		}
        else
        	return "";
	}
	
	public static function getOrgContentAssetThumbUrl($orgId, $serverFilename)
	{
		if($serverFilename != "")
		{
			$consFilename = $serverFilename;
			$fileNameExpArr = explode(".", $consFilename);
		    $extension = isset($fileNameExpArr) ? end($fileNameExpArr) : "";

		    $fileNeedleDoc = 'doc';
		    $fileNeedlePdf = 'pdf';
		    $fileNeedleTxt = 'txt';

		    $placeHolderImageImg = 'img';
		    $placeHolderImageDoc = 'doc';
		    $placeHolderImagePdf = 'pdf';
		    $placeHolderImageTxt = 'txt';

		    $placeHolderImagePostfix = "";

	        {
	        	switch ($extension) {
	        		case 'pdf':
	        			{
							$placeHolderImagePostfix = "ic_pdf";
	        				break;
	        			}
	        		case 'docx':
	        			{
							$placeHolderImagePostfix = "ic_docx";
	        				break;
	        			}
	        		case 'doc':
	        			{
							$placeHolderImagePostfix = "ic_doc";
	        				break;
	        			}
	        		case 'pptx':
	        			{
							$placeHolderImagePostfix = "ic_ppt";
	        				break;
	        			}
	        		case 'ppt':
	        			{
							$placeHolderImagePostfix = "ic_ppt";
	        				break;
	        			}
	        		case 'jpeg':
	        			{
							$placeHolderImagePostfix = "ic_jpg";
	        				break;
	        			}
	        		case 'jpg':
	        			{
							$placeHolderImagePostfix = "ic_jpg";
	        				break;
	        			}
	        		case 'png':
	        			{
							$placeHolderImagePostfix = "ic_png";
	        				break;
	        			}
	        		case 'txt':
	        			{
							$placeHolderImagePostfix = "ic_txt";
	        				break;
	        			}
	        		case 'xlsx':
	        			{
							$placeHolderImagePostfix = "ic_xlsx";
	        				break;
	        			}
	        		case 'mp3':
	        			{
							$placeHolderImagePostfix = "ic_mp3";
	        				break;
	        			}
	        		case 'mp4':
	        			{
							$placeHolderImagePostfix = "ic_mp4";
	        				break;
	        			}
	        		case 'avi':
	        			{
							$placeHolderImagePostfix = "ic_avi";
	        				break;
	        			}
	        		default:
	        			{
							$placeHolderImagePostfix = "ic_default";
	        				break;
	        			}
	        	}
	        }


			$publicFolder = Config::get("app_config.publicUrlPath");
			$orgAttachmentPlaceholderDirPath = $publicFolder."/assets/images/attachmentPlaceHolders";	

	        $serverFileUrl = url($orgAttachmentPlaceholderDirPath)."/".$placeHolderImagePostfix.".png";

			return $serverFileUrl;
		}
        else
        	return "";
	}
	
	public static function getOrgContentAssetFolderThumbUrl()
	{
		$placeHolderImagePostfix = "ic_cloud_storage_folder";

		$publicFolder = Config::get("app_config.publicUrlPath");
		$orgAttachmentPlaceholderDirPath = $publicFolder."/assets/images/attachmentPlaceHolders";	

        $serverFileUrl = url($orgAttachmentPlaceholderDirPath)."/".$placeHolderImagePostfix.".png";

		return $serverFileUrl;
	}

	public static function getAppuserProfilePhotoUrl($serverFilename)
	{
		if($serverFilename != "")
		{
			$orgId = 0;
	        $orgAssetDirPath = OrganizationClass::getAppuserProfilePhotoDirPath($orgId);
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getAppuserProfilePhotoThumbUrl($serverFilename)
	{
		if($serverFilename != "")
		{
			$orgId = 0;
	        $orgAssetDirPath = OrganizationClass::getAppuserProfilePhotoDirPath($orgId);
	        $orgAssetDirPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');
			return $serverFileUrl = OrganizationClass::getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename);
		}
        else
        	return "";
	}
	
	public static function getOrgAssetUrl($orgId, $orgAssetDirPath, $serverFilename)
	{
		$serverFileUrl = "";
		if($orgId > 0)
		{
			$orgServer = OrganizationServer::ofOrganization($orgId)->first();
			if(isset($orgServer))
			{
				$isAppFileServer = $orgServer->is_app_file_server;
				$hostname = $orgServer->file_host;
				
	        	$filePath = $orgAssetDirPath."/".$serverFilename;
	            if($isAppFileServer == 1)
	            {
	            	$serverFileUrl = url($filePath);				
				}
				else
				{	
					$clientSysName = Config::get('app_config.enterp_org_client_sys_name');					
					$serverFileUrl = $hostname."/".$clientSysName."/".$filePath;										
				}
			}
		}
		else
		{
	        $filePath = $orgAssetDirPath."/".$serverFilename;
	        $serverFileUrl = url($filePath);
		}			
		return $serverFileUrl;		
	}

	public static function getOrgKeyFromOrgId($orgId)
	{
		$orgKey = "";
    	$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
    	if(isset($organization))
    	{
			$orgKey = $organization->org_key;
		}		
		return $orgKey;
	}

	public static function getOrgEmpKeyFromOrgAndEmpId($orgId, $orgEmpId)
	{
		$orgEmpKey = "";
		if($orgId > 0 && $orgEmpId > 0)
		{
			$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
			if(isset($orgDbConName))
			{
				$modelObj = New OrgEmployee;
				$modelObj->setConnection($orgDbConName);
				$orgEmp = $modelObj->byId($orgEmpId)->first();
				if(isset($orgEmp))
				{
					$orgEmpKey = $orgEmp->org_emp_key;
				}
			}
		}
		return $orgEmpKey;
	}

	public static function getOrgEmpProfileKeyFromOrgAndEmpId($orgId, $orgEmpId)
	{
		$currEncOrgId = "";
		if($orgId > 0 && $orgEmpId > 0)
		{
			$currEncOrgId = Crypt::encrypt($orgId."_".$orgEmpId);
		}
		return $currEncOrgId;
	}

	public static function getOrganizationFromOrgId($orgId)
	{
		$organization = NULL;
        if(isset($orgId) && $orgId > 0)
        {
        	$organization = Organization::byId($orgId)->first();
		}		
		return $organization;
	}

	public static function getOrgIdFromOrgKey($orgKey)
	{
		$orgId = 0;
		
		try
		{
			if(isset($orgKey) && trim($orgKey) != "")
			{
				$orgKey = trim($orgKey);

				$orgDecStr = Crypt::decrypt($orgKey);
				
				if($orgDecStr != "")
				{
					$expArr = explode("_",$orgDecStr);
					if(isset($expArr[0]))
					{
						$orgId = $expArr[0];
						
						$organization = Organization::byId($orgId)->first();
				    	if(!isset($organization))
				    	{
							$orgId = 0;
						}
					}
				}
			}
		}
        catch (DecryptException $e) 
        {
			$orgId = 0;
        }
			
		return $orgId;
	}

	public static function getOrgEmployeeIdFromOrgKey($orgKey)
	{
		$orgEmpId = 0;
		
		try
		{
			if(isset($orgKey) && trim($orgKey) != "")
			{
				$orgKey = trim($orgKey);
				
				$orgDecStr = Crypt::decrypt($orgKey);
				
				if($orgDecStr != "")
				{
					$expArr = explode("_",$orgDecStr);
					if(isset($expArr[1]))
					{
						$orgEmpId = $expArr[1];
					}
				}
			}
		}
        catch (DecryptException $e) 
        {
			$orgEmpId = 0;
        }

		return $orgEmpId;
	}

	public static function getOrganizationFromOrgKey($orgKey)
	{
		$organization = NULL;
        if(isset($orgKey) && $orgKey != "")
        {
        	$orgId = OrganizationClass::getOrgIdFromOrgKey($orgKey);
        	$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		}		
		return $organization;
	}

	public static function getUserIdFromOrgEmployee($orgId, $empId)
	{
		$userId = 0;
		$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {		
			$orgUser = OrganizationUser::byEmpId($empId)->ofOrganization($orgId)->first();			
			if(isset($orgUser) && $orgUser->is_verified == 1)
			{
				$userEmail = $orgUser->appuser_email;
				$user = Appuser::ofEmail($userEmail)->active()->first();
				
				if(isset($user))
				{
					$userId = $user->appuser_id;
				}
			}
		}
		
		return $userId;
	}

	public static function getOrgEmployeeObject($orgId, $orgEmpId)
	{
		$orgEmployee = NULL;
		$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        if($orgDbConName != "")
        {
			$modelObj = New OrgEmployee;
        	$modelObj = $modelObj->setConnection($orgDbConName);        	
        	$orgEmployee = $modelObj->byId($orgEmpId)->first();
		}
		
		return $orgEmployee;
	}

	public static function getOrgEmployeeObjectByEmail($orgId, $orgEmpEmail)
	{
		$orgEmployee = NULL;
		$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        if($orgDbConName != "")
        {
			$modelObj = New OrgEmployee;
        	$modelObj = $modelObj->setConnection($orgDbConName);        	
        	$orgEmployee = $modelObj->ofEmail($orgEmpEmail)->first();
		}
		
		return $orgEmployee;
	}

	public static function getOrgCode($orgId)
	{
		$orgCode = "";
		$organization = Organization::byId($orgId)->first();

        if(isset($organization))
        {
        	$orgCode = $organization->org_code;
		}
		
		return $orgCode;
	}

	public static function getOrganizationLogoUrlArr($orgId)
	{
		$orgIconUrlArr = NULL;

		if(isset($orgId) && $orgId > 0)
		{
			$organization = Organization::byId($orgId)->first();
	        if(isset($organization))
	        {
	            $logoFilename = $organization->logo_filename;
	            if(isset($logoFilename) && $logoFilename != "")
	            {
	                $orgIconUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
	                $orgIconThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);

			        $orgIconUrlArr = array();
			        $orgIconUrlArr['iconUrl'] = $orgIconUrl;
			        $orgIconUrlArr['iconThumbUrl'] = $orgIconThumbUrl;
	            }
	        }
	    }

        return $orgIconUrlArr;
	}
	
	public static function removeOrganizationEmployeeRetailShareRights($orgId)
	{
		$depMgmtObj = New ContentDependencyManagementClass;
		$depMgmtObj->withOrgId($orgId);	
		$orgEmployees = $depMgmtObj->getAllEmployees();
		
		if(isset($orgEmployees))
        {
        	foreach($orgEmployees as $emp)
            {
				$orgEmpId = $emp->employee_id;
				//Remove Rights
				
				$empDepMgmtObj = New ContentDependencyManagementClass;
				$empDepMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
				$employeeConst = $empDepMgmtObj->getEmployeeConstantObject();
                
                if(isset($employeeConst) && ($employeeConst->is_srac_retail_share_enabled == 1 || $employeeConst->is_copy_to_profile_enabled == 1))
		        {
		        	$employeeConst->is_srac_retail_share_enabled = 0;
		        	$employeeConst->is_copy_to_profile_enabled = 0;
		        	$employeeConst->save();
		        	
		        	//SEND FCM
					//$this->sendOrgEmployeeShareRightsToDevice($id);
				}		        
			}
		}
	}
	
	public function removeOrDeactivateOrganizationEmployees($orgId, $removeEmployee, $forceDelete = FALSE)
	{
		$depMgmtObj = New ContentDependencyManagementClass;
		$depMgmtObj->withOrgId($orgId);	
		$orgEmployees = $depMgmtObj->getAllEmployees();
		if($removeEmployee)
		{
			if($orgEmployees != NULL)
            {
            	foreach($orgEmployees as $emp)
	            {
					$orgEmpId = $emp->employee_id;
					$this->sendOrgEmployeeRemovedToDevice($orgEmpId, $forceDelete, NULL, $orgId);
					$this->removeEmployeeDependencies($orgId, $orgEmpId, $removeEmployee);
				}
			}
		}
		else
		{
			if($orgEmployees != NULL)
            {
	            foreach($orgEmployees as $emp)
	            {
					$orgEmpId = $emp->employee_id;
					
					if($emp->is_active == 1)
					{
						$emp->is_active = 0;
		            	$emp->save();
		            	
						$this->sendOrgEmployeeRemovedToDevice($orgEmpId, $forceDelete, NULL, $orgId);
					}            	
				}
			}
		}
	}
	
	public static function removeEmployeeDependencies($orgId, $orgEmpId, $removeEmployee)
	{
		$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {          
            $modelObj = New OrgEmployee;
            $modelObj = $modelObj->setConnection($orgDbConName);
            $orgEmployee = $modelObj->byId($orgEmpId)->first();
            
            if(isset($orgEmployee))
            {
            	//Remove All Folders
            	$modelObj = New OrgEmployeeFolder;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Tags
            	$modelObj = New OrgEmployeeTag;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Sources
            	$modelObj = New OrgEmployeeSource;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Contents
            	$modelObj = New OrgEmployeeContent;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$contents = $modelObj->ofEmployee($orgEmpId)->get();
	              
            	//Remove All Group Content Tags
	            $modelObj = New OrgGroupContentTag;
				$modelObj = $modelObj->setConnection($orgDbConName);					
                $modelObj->ofEmployee($orgEmpId)->delete();
                
				$userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $orgEmpId);					
				$user = Appuser::byId($userId)->first();
            	
				if(isset($user))
				{ 
					$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);  
				}
				else
	            {
					$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);  
				}
            	if(isset($contents))
            	{         		
					foreach($contents as $content)
					{
						$contentId = $content->employee_content_id;						
						$depMgmtObj->deleteContent($contentId);						
					}	            						
				}
	            	
            	if($removeEmployee)
            	{
			        $modelObj = New OrgEmployeeConstant;
			        $modelObj->setConnection($orgDbConName);
		            $modelObj->ofEmployee($orgEmpId)->delete();
		            
		            //Remove From Groups
		            $modelObj = New OrgGroupMember;
					$modelObj = $modelObj->setConnection($orgDbConName);					
	                $modelObj->ofEmployee($orgEmpId)->delete();
		            
			        OrganizationUser::ofOrganization($orgId)->byEmpId($orgEmpId)->delete();
            		
		            $orgEmployee->is_deleted = 1;
		            $orgEmployee->save();
		            $orgEmployee->delete();
				}	
		            
		        $depMgmtObj->recalculateOrgSubscriptionParams();            	
			}
		}
	}
	
	public static function removeAppuserDependencies($userId, $removeUser)
	{
		$user = Appuser::byId($userId)->first();

        if(isset($user))
        {          
            $modelObj = New OrgEmployee;
            $modelObj = $modelObj->setConnection($orgDbConName);
            $orgEmployee = $modelObj->byId($orgEmpId)->first();
            
            if(isset($orgEmployee))
            {
            	//Remove All Folders
            	$modelObj = New OrgEmployeeFolder;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Tags
            	$modelObj = New OrgEmployeeTag;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Sources
            	$modelObj = New OrgEmployeeSource;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$modelObj->ofEmployee($orgEmpId)->delete();
            	
            	//Remove All Contents
            	$modelObj = New OrgEmployeeContent;
            	$modelObj = $modelObj->setConnection($orgDbConName);
            	$contents = $modelObj->ofEmployee($orgEmpId)->get();
	              
            	//Remove All Group Content Tags
	            $modelObj = New OrgGroupContentTag;
				$modelObj = $modelObj->setConnection($orgDbConName);					
                $modelObj->ofEmployee($orgEmpId)->delete();
                
				$userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $orgEmpId);					
				$user = Appuser::byId($userId)->first();
            	
				if(isset($user))
				{ 
					$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);  
				}
				else
	            {
					$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);  
				}
            	if(isset($contents))
            	{         		
					foreach($contents as $content)
					{
						$contentId = $content->employee_content_id;						
						$depMgmtObj->deleteContent($contentId);						
					}	            						
				}
	            	
            	if($removeEmployee)
            	{
			        $modelObj = New OrgEmployeeConstant;
			        $modelObj->setConnection($orgDbConName);
		            $modelObj->ofEmployee($orgEmpId)->delete();
		            
		            //Remove From Groups
		            $modelObj = New OrgGroupMember;
					$modelObj = $modelObj->setConnection($orgDbConName);					
	                $modelObj->ofEmployee($orgEmpId)->delete();
		            
			        OrganizationUser::ofOrganization($orgId)->byEmpId($orgEmpId)->delete();
            		
		            $orgEmployee->is_deleted = 1;
		            $orgEmployee->save();
		            $orgEmployee->delete();
				}	
		            
		        $depMgmtObj->recalculateOrgSubscriptionParams();            	
			}
		}
	}
	
	public static function setEmployeeDefaultParams($orgId, $orgEmpId)
	{
		$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {		                        
            $modelObj = New OrgEmployee;
            $modelObj = $modelObj->setConnection($orgDbConName);
            $orgEmployee = $modelObj->byId($orgEmpId)->first();
            
            if(isset($orgEmployee))
            {
	            $orgEmployee->is_verified = 1;
	            $orgEmployee->save();
	            
	            $isSelfRegistered = $orgEmployee->is_self_registered;
			                        
		        $defaultFolderArr = Config::get('app_config.default_folder_arr');
		        $defaultTagArr = Config::get('app_config.default_tag_arr');
		        $defaultSourceArr = Config::get('app_config.default_source_arr');
		        $defFolderName = Config::get('app_config.default_folder');
		        $emailSource = Config::get('app_config.source_email_text');
		        $defAllottedAttachmentKbs = Config::get('app_config.default_allotted_attachment_kb');
		        $defSelfRegdAllottedAttachmentKbs = Config::get('app_config.default_self_regd_allotted_attachment_kb');
        		$defFolderIconCode = Config::get('app_config.default_folder_icon_code');
				$defFolderIsFavorited = Config::get('app_config.default_folder_is_favorited');
				
				$depMgmtObj = New ContentDependencyManagementClass;
	        	$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);

		        $defFolderId = 0;
		        $defTagId = 0;
		        $emailSourceId = 0;
			        
		        for($i=0; $i<count($defaultFolderArr); $i++)
		        {   
		        	$folderId = 0;  
		            $name = $defaultFolderArr[$i];
		            
					$userFolder = $depMgmtObj->getFolderObjectByName($name);
		            if(!isset($userFolder))
		            {
		    			$folderResponse = $depMgmtObj->addEditFolder(0, $name, $defFolderIconCode, $defFolderIsFavorited);
		    			$folderId = $folderResponse['syncId'];
					}
					else
					{
						$folderId = $userFolder->appuser_folder_id;
					}

		            if($name == $defFolderName)
		            {
		                $defFolderId = $folderId;
		            }
		        }
		        
	        	$depMgmtObj->getSentFolder();
	        	
	        	for($i=0; $i<count($defaultTagArr); $i++)
		        {
		            $name = $defaultTagArr[$i];
		            
		            $userTag = $depMgmtObj->getTagObjectByName($name);
		            if(!isset($userTag))
		            {	            
		    			$tagResponse = $depMgmtObj->addEditTag(0, $name);
					}
		        }

		        // $depMgmtObj->setupOrgEmployeeTagsForAllExistingOrgSystemTags();
		        
		        for($i=0; $i<count($defaultSourceArr); $i++)
		        {   
		        	$sourceId = 0;  
		            $name = $defaultSourceArr[$i];
		            
		            $userSource = $depMgmtObj->getSourceObjectByName($name);
		            if(!isset($userSource))
		            {
		    			$sourceResponse = $depMgmtObj->addEditSource(0, $name);
		    			$sourceId = $sourceResponse['syncId'];
					}
					else
					{
						$sourceId = $userSource->appuser_source_id;
					}

		            if($name == $emailSource)
		            {
		                $emailSourceId = $sourceId;
		            }
		        }
        
				$isExistingEmployee = TRUE;
		        $modelObj = New OrgEmployeeConstant;
		        $modelObj->setConnection($orgDbConName);
		        $userConstant = $modelObj->ofEmployee($orgEmpId)->first();
	            $tableName = $modelObj->table;	            
	            if(!isset($userConstant))
	            {
            		$hasRetailShareRights = OrganizationClass::orgHasRetailShareEnabled($orgId);
            		$retailRightsAllotted = 0;
            		if($hasRetailShareRights) {
						$retailRightsAllotted = 1;
					}

					$isFileSaveShareEnabled = OrganizationClass::orgHasFileSaveShareEnabled($orgId);
					$isScreenShareEnabled = OrganizationClass::orgHasScreenShareEnabled($orgId);
            		
	            	$isExistingEmployee = FALSE;
	            	$allottedKbs = $defAllottedAttachmentKbs;
	            	$rightsAllotted = 1;
	            	if($isSelfRegistered == 1)
	            	{
	            		$allottedKbs = $defSelfRegdAllottedAttachmentKbs;
	            		$rightsAllotted = 0;
	            		$retailRightsAllotted = 0;
					}
	            		
					$constantDetails = array();
			        $constantDetails['employee_id'] = $orgEmpId;
			        $constantDetails['def_folder_id'] = $defFolderId;
			        $constantDetails['email_source_id'] = $emailSourceId;
			        $constantDetails['folder_passcode_enabled'] = 0;
			        $constantDetails['folder_passcode'] = "";
			        $constantDetails['folder_id_str'] = "";
			        $constantDetails['attachment_kb_allotted'] = $allottedKbs;
			        $constantDetails['is_srac_share_enabled'] = $rightsAllotted;
			        $constantDetails['is_srac_org_share_enabled'] = $rightsAllotted;
			        $constantDetails['is_srac_retail_share_enabled'] = $retailRightsAllotted;
			        $constantDetails['is_copy_to_profile_enabled'] = $retailRightsAllotted;
			        $constantDetails['is_soc_share_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_facebook_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_twitter_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_linkedin_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_whatsapp_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_email_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_sms_enabled'] = $rightsAllotted;
			        $constantDetails['is_soc_other_enabled'] = $rightsAllotted;
			        $constantDetails['is_file_save_share_enabled'] = $isFileSaveShareEnabled;
			        $constantDetails['is_screen_share_enabled'] = $isScreenShareEnabled;
			        
			        DB::connection($orgDbConName)->table($tableName)->insertGetId($constantDetails);
				}	

				if(!$isExistingEmployee)
				{
					$modelObj = New OrgGroup;
			        $modelObj->setConnection($orgDbConName);
			        $appGroups = $modelObj->autoEnroll()->get();
			        if(isset($appGroups) && count($appGroups) > 0)
			        {
		                $modelObj = New OrgGroupMember;
		                $tableName = $modelObj->table; 
		                $addedGroupIdArr = array();
						foreach($appGroups as $appGroup)
						{	 
							$groupId = $appGroup->group_id; 
							
							if(!in_array($groupId, $addedGroupIdArr)) 
							{
								$empDetails = array();
								$empDetails['group_id'] = $groupId;
								$empDetails['employee_id'] = $orgEmpId;
								$empDetails['is_admin'] = 0;
								$empDetails['has_post_right'] = 0;
								
								array_push($addedGroupIdArr, $groupId);
								
			                	DB::connection($orgDbConName)->table($tableName)->insert($empDetails);						
								MailClass::sendOrgEmpAddedToGroupMail($orgId, $orgEmpId, $appGroup);
							}               
							
						}
					}	
				}        
			}
		}		
	}
	
	public static function canSendContentAddedMail($orgId, $appuser = NULL)
	{
		$canSend = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgSubscription = $organization->subscription;
				if(isset($orgSubscription->content_added_mail_enabled) && $orgSubscription->content_added_mail_enabled == 1)
				{
					$canSend = TRUE;
				}
			}
		}
		else
		{
			if(isset($appuser) && $appuser->is_premium == 1) {
				$canSend = Config::get('app_config_mail.content_added_mail_premium_enabled');			
			}
			else {
				$canSend = Config::get('app_config_mail.content_added_mail_retail_enabled');	
			}
		}	
		//$canSend = TRUE;		
		return $canSend;
	}
	
	public static function canSendContentDeliveredMail($orgId, $appuser = NULL)
	{
		$canSend = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgSubscription = $organization->subscription;
				if(isset($orgSubscription->content_delivered_mail_enabled) && $orgSubscription->content_delivered_mail_enabled == 1)
				{
					$canSend = TRUE;
				}
			}
		}
		else
		{
			if(isset($appuser) && $appuser->is_premium == 1) {
				$canSend = Config::get('app_config_mail.content_delivered_mail_premium_enabled');			
			}
			else {
				$canSend = Config::get('app_config_mail.content_delivered_mail_retail_enabled');	
			}
		}			
		return $canSend;
	}
	
	public static function isPremiumUser($userId)
	{
		$isPremiumUser = FALSE;
		
		$appuser = Appuser::byId($userId)->isPremium()->first();
		if(isset($appuser))
		{
			$isPremiumUser = TRUE;
		}
		
		return $isPremiumUser;
	}
	
	public static function canSendReminderMail($orgId)
	{
		$canSend = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgSubscription = $organization->subscription;
				if(isset($orgSubscription->reminder_mail_enabled) && $orgSubscription->reminder_mail_enabled == 1)
				{
					$canSend = TRUE;
				}
			}
		}
		else
		{
			$canSend = Config::get('app_config_mail.reminder_mail_retail_enabled');
		}			
		return $canSend;
	}
	
	public static function canSendBirthdayMail($orgId)
	{
		$canSend = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgSubscription = $organization->subscription;
				if(isset($orgSubscription->birthday_mail_enabled) && $orgSubscription->birthday_mail_enabled == 1)
				{
					$canSend = TRUE;
				}
			}
		}
		else
		{
			$canSend = Config::get('app_config_mail.birthday_mail_retail_enabled');
		}			
		return $canSend;
	}
	
	public static function orgHasRetailShareEnabled($orgId)
	{
		$isEnabled = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgSubscription = $organization->subscription;
				if(isset($orgSubscription->retail_share_enabled) && $orgSubscription->retail_share_enabled == 1)
				{
					$isEnabled = TRUE;
				}
			}
		}			
		return $isEnabled;
	}
	
	public static function orgHasFileSaveShareEnabled($orgId)
	{
		$isEnabled = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				if(isset($organization->is_file_save_share_enabled) && $organization->is_file_save_share_enabled == 1)
				{
					$isEnabled = TRUE;
				}
			}
		}			
		return $isEnabled;
	}
	
	public static function orgHasScreenShareEnabled($orgId)
	{
		$isEnabled = FALSE;
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				if(isset($organization->is_screen_share_enabled) && $organization->is_screen_share_enabled == 1)
				{
					$isEnabled = TRUE;
				}
			}
		}			
		return $isEnabled;
	}
	
	public static function getOrganizationEmployeeHasFileSaveShareEnabled($orgId, $orgEmpId)
	{
		$isEnabled = 0;
		if($orgId > 0 && $orgEmpId > 0)
		{
	        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

	        $modelObj = New OrgEmployee;
	        $tablename = $modelObj->table;
	        $modelObj->setConnection($orgDbConName);

    		$selectArr = [$tablename.'.employee_id', 'is_file_save_share_enabled'];
			   
        	$employee = $modelObj->select($selectArr)->joinConstantTable()->verifiedAndActive()->byId($orgEmpId)->first();
        	if(isset($employee))
        	{
        		$isEnabled = $employee->is_file_save_share_enabled;
        	}
		}			
		return $isEnabled;
	}
	
	public static function getOrganizationEmployeeHasScreenShareEnabled($orgId, $orgEmpId)
	{
		$isEnabled = 0;
		if($orgId > 0 && $orgEmpId > 0)
		{
	        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

	        $modelObj = New OrgEmployee;
	        $tablename = $modelObj->table;
	        $modelObj->setConnection($orgDbConName);

    		$selectArr = [$tablename.'.employee_id', 'is_screen_share_enabled'];
			   
        	$employee = $modelObj->select($selectArr)->joinConstantTable()->verifiedAndActive()->byId($orgEmpId)->first();
        	if(isset($employee))
        	{
        		$isEnabled = $employee->is_screen_share_enabled;
        	}
		}			
		return $isEnabled;
	}
	
	public static function getOrganizationAdministrators($orgId)
	{
		$orgAdmins = array();
		if($orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			if($organization->is_active == 1)
			{
				$orgAdmins = OrganizationAdministration::ofOrganization($orgId)->active()->onlyAdministrator()->get();
			}
		}			
		return $orgAdmins;
	}
    
    /**
     * Get Appuser Resultset for datatables
     *
     * @return string
     */
    public static function getEmployeeResultSet($orgId, $empIdArr = NULL)
    {
        $dateFormat = Config::get('app_config.sql_date_db_format');
        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');
        
        $verTextIsVerified = Config::get('app_config.is_verified_text'); 
        $verTextIsPending = Config::get('app_config.verification_pending_text'); 
        $verTextIsSelfVerified = Config::get('app_config.is_self_verified_text');

        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        $modelObj = New OrgEmployee;
        $tablename = $modelObj->table;
        $modelObj->setConnection($orgDbConName);
        
        $badgeModelObj = New OrgEmployeeBadge;
        $badgeTablename = $badgeModelObj->table;

        $forSend = Input::get('forSend');
        $forEmpList = Input::get('forEmpList');
        $forGroupList = Input::get('forGroupList');
    	$forDashboard = Input::get('forDashboard');
    	$onlyDeleted = Input::get('onlyDeleted');
        
        $verStatus = Input::get('verStatus');
        $departmentId = Input::get('department');
        $designationId = Input::get('designation');
        $badgeId = Input::get('badge');

    	$departmentId = sracDecryptNumberData($departmentId);
    	$designationId = sracDecryptNumberData($designationId);
    	$badgeId = sracDecryptNumberData($badgeId);
        
        if($forEmpList == 1)
        {
    		$selectArr = [$tablename.'.employee_id', 'employee_no', 'employee_name', 'department_name', 'designation_name', 'email', 'contact', \DB::raw("DATE_FORMAT(dob, '$dateFormat') as dob"), \DB::raw("GROUP_CONCAT(DISTINCT badge_name ORDER BY  badge_name ASC SEPARATOR ', ') as badges"), \DB::raw("IF(is_self_registered = 1, '$verTextIsSelfVerified', IF(is_verified=1, '$verTextIsVerified', '$verTextIsPending')) as verification_status"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("COUNT(employee_content_id) as note_count"), 'has_web_access', 'is_verified', "$tablename.is_active as is_emp_active"];

    		if(!isset($forDashboard) || $forDashboard != 1)
            {
            	$shareRightsSelection = ['is_srac_share_enabled', 'is_srac_org_share_enabled', 'is_srac_retail_share_enabled', 'is_copy_to_profile_enabled', 'is_soc_share_enabled', 'is_soc_facebook_enabled', 'is_soc_twitter_enabled', 'is_soc_linkedin_enabled', 'is_soc_whatsapp_enabled', 'is_soc_email_enabled', 'is_soc_sms_enabled', 'is_soc_other_enabled', 'is_file_save_share_enabled', 'is_screen_share_enabled'];
            	$selectArr = array_merge($selectArr, $shareRightsSelection);
            }
            else
            {
            	$shareRightsSelection = ['is_file_save_share_enabled', 'is_screen_share_enabled'];
            	$selectArr = array_merge($selectArr, $shareRightsSelection);
            }
        }
        elseif($forGroupList == 1)
        	$selectArr = [$tablename.'.employee_id', 'employee_no', 'employee_name', 'department_name', 'designation_name', 'email', \DB::raw("GROUP_CONCAT(DISTINCT badge_name ORDER BY  badge_name ASC SEPARATOR ', ') as badges"), \DB::raw("IF(is_self_registered = 1, '$verTextIsSelfVerified', IF(is_verified=1, '$verTextIsVerified', '$verTextIsPending')) as verification_status")];
        else/*if($forSend == 1)*/
        	$selectArr = [$tablename.'.employee_id', 'employee_no', 'employee_name', 'department_name', 'designation_name', 'email', 'contact', \DB::raw("DATE_FORMAT(dob, '$dateFormat') as dob"), \DB::raw("GROUP_CONCAT(DISTINCT badge_name ORDER BY  badge_name ASC SEPARATOR ', ') as badges"), \DB::raw("IF(is_self_registered = 1, '$verTextIsSelfVerified', IF(is_verified=1, '$verTextIsVerified', '$verTextIsPending')) as verification_status")];
            
        $employees = $modelObj->select($selectArr)
        			->joinDepartmentTable()
        			->joinDesignationTable()
        			->joinBadgeTable();
        			
        if($forEmpList == 1)
        {
        	$employees = $employees->joinConstantTable();
        	$employees = $employees->joinContents();
		}

		if(isset($onlyDeleted) && $onlyDeleted == 1)
        {
        	$employees = $employees->onlyDeleted();
        }
        	
        if($forSend == 1)
            $employees->where('is_verified','=',1);       	

        if(isset($verStatus) && $verStatus != "")
        {
    		$verStatus = sracDecryptNumberData($verStatus);

        	if($verStatus == 0)
        	{
        		$employees->where('is_verified', '=', 0);
        	}	
        	else if($verStatus == 1)
        	{
        		$employees->where('is_verified', '=', 1);
        		$employees->where('is_self_registered', '=', 0);
        	}
        	else if($verStatus == 2)
        	{
        		$employees->where('is_verified', '=', 1);
        		$employees->where('is_self_registered', '=', 1);
        	}
		}

        if(isset($departmentId) && $departmentId != "" && $departmentId > 0)
            $employees->where($tablename.'.department_id','=',$departmentId);

        if(isset($designationId) && $designationId != "" && $designationId > 0)
            $employees->where($tablename.'.designation_id','=',$designationId);

        if(isset($badgeId) && $badgeId != "" && $badgeId > 0)
            $employees->where($badgeTablename.'.badge_id','=',$badgeId);

        if(isset($empIdArr) && count($empIdArr) > 0)
        {
            $employees->whereIn($tablename.'.employee_id', $empIdArr);
        }

            
        return $employees;
    }
    
    public static function getAppuserContentDeleteUrl($userId, $orgId, $orgEmpId, $contentId)
    {
    	$decParts = $userId."|".$orgId."|".$orgEmpId."|".$contentId;
    	
        $delContentLink = Config::get('app_config.url_delete_appuser_content');
        $randomCode = MailClass::getRandomCode();        
       	$encContentId = rawurlencode(Crypt::encrypt($decParts));
		return $link = url($delContentLink.$encContentId);
	}
	
	public static function getAppuserContentDependenciesFromDeleteUrl($encContentId)
    {
    	$userId = 0;
    	$orgId = 0;
    	$orgEmpId = 0;
    	$contentId = 0;
    	
        $encContentId = urldecode($encContentId);
    	$decParts = Crypt::decrypt($encContentId);
    	$parts = explode("|",$decParts);
    	
    	if(count($parts) == 4)
        {
        	$userId = $parts[0];
        	$orgId = $parts[1];
        	$orgEmpId = $parts[2];
        	$contentId = $parts[3];
		}
    	
    	$dependencies = array();
    	$dependencies['userId'] = $userId;
    	$dependencies['orgId'] = $orgId;
    	$dependencies['orgEmpId'] = $orgEmpId;
    	$dependencies['contentId'] = $contentId;
    	
    	return $dependencies;
	}
    
    public static function getAppuserJoinGroupInvitationUrl($userId, $orgId, $orgEmpId, $groupId, $email)
    {
    	$decParts = $userId."|".$orgId."|".$orgEmpId."|".$groupId."|".$email;
    	
        $delContentLink = Config::get('app_config.url_appuser_join_group_invitation');
        $randomCode = MailClass::getRandomCode();        
       	$encJoinId = rawurlencode(Crypt::encrypt($decParts));
		return $link = url($delContentLink.$encJoinId);
	}
	
	public static function getAppuserJoinGroupDependenciesFromInvitationUrl($encJoinId)
    {
    	$userId = 0;
    	$orgId = 0;
    	$orgEmpId = 0;
    	$groupId = 0;
    	$email = "";
    	
        $encJoinId = urldecode($encJoinId);
    	$decParts = Crypt::decrypt($encJoinId);
    	$parts = explode("|",$decParts);
    	
    	if(count($parts) == 5)
        {
        	$userId = $parts[0];
        	$orgId = $parts[1];
        	$orgEmpId = $parts[2];
        	$groupId = $parts[3];
        	$email = $parts[4];
		}
    	
    	$dependencies = array();
    	$dependencies['userId'] = $userId;
    	$dependencies['orgId'] = $orgId;
    	$dependencies['orgEmpId'] = $orgEmpId;
    	$dependencies['groupId'] = $groupId;
    	$dependencies['email'] = $email;
    	
    	return $dependencies;
	}

	private static function getMBsFromBytes($sizeInBytes)
	{
		$sizeInMBs = round($sizeInBytes / 1048576); // 1024*1024
		return $sizeInMBs;
	}

	public static function getTotalFolderSizeInMBs($folderPath)
	{
		$totalSizeInBytes = OrganizationClass::getTotalFolderSizeInBytes($folderPath);
		$totalSizeInMBs = OrganizationClass::getMBsFromBytes($totalSizeInBytes);
		return $totalSizeInMBs;
	}

	private static function getTotalFolderSizeInBytes($folderPath)
	{
		$dir = $folderPath;
		$total_size = 0;
		$count = 0;
		$dir_array = scandir($dir);
		foreach($dir_array as $key=>$filename)
		{
			if($filename!=".." && $filename!=".")
			{
				if(is_dir($dir."/".$filename))
				{
					$new_foldersize = OrganizationClass::getTotalFolderSizeInBytes($dir."/".$filename);
					$total_size = $total_size+ $new_foldersize;
				}
				else if(is_file($dir."/".$filename))
				{
					$total_size = $total_size + filesize($dir."/".$filename);
					$count++;
				}
			}
		}
		return $total_size;
	}

	public static function getOrganizationAvailableSpaceInMBs($orgId)
	{
		$orgAvailableSpaceMBs = 0;
		$orgSubscription = OrganizationSubscription::ofOrganization($orgId)->first();
		if(isset($orgSubscription))
		{
			$allotedQuotaGBs = $orgSubscription->allotted_quota_in_gb;
			$allotedQuotaMBs = $allotedQuotaGBs * 1024;
			$usedQuotaMBs = $orgSubscription->used_quota_in_mb;

			$orgAvailableSpaceMBs = $allotedQuotaMBs - $usedQuotaMBs;

			if($orgAvailableSpaceMBs < 0)
			{
				$orgAvailableSpaceMBs = 0;
			}
		}
		return $orgAvailableSpaceMBs;
	}

	public static function getOrganizationAllottedSpaceInMBs($orgId)
	{
		$orgAllotedQuotaMBs = 0;
		$orgSubscription = OrganizationSubscription::ofOrganization($orgId)->first();
		if(isset($orgSubscription))
		{
			$allotedQuotaGBs = $orgSubscription->allotted_quota_in_gb;
			$orgAllotedQuotaMBs = $allotedQuotaGBs * 1024;
		}
		return $orgAllotedQuotaMBs;
	}

	public static function getTotalServerAvailableSpaceInMBs()
	{
        $serverAvailableSpaceBytes = disk_free_space('/'); // In Bytes
        $serverAvailableSpaceMBs = OrganizationClass::getMBsFromBytes($serverAvailableSpaceBytes);
		return $serverAvailableSpaceMBs;
	}
	
	public static function createOrganizationDataBackup($orgId)
	{
		$msgLogs = '';
		$orgBackupFolderName = '';
    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
	        $serverAvailableSpaceMBs = OrganizationClass::getTotalServerAvailableSpaceInMBs();

			$isAppServer = $orgServer->is_app_db_server;
			$orgDbName = $orgServer->dbname;

			$currentDateTime = date('Ymd_His');

			$orgAssetDir = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
	        $orgAssetDirSizeMBs = OrganizationClass::getTotalFolderSizeInMBs($orgAssetDir);

	        if($serverAvailableSpaceMBs >= $orgAssetDirSizeMBs)
	        {
	        	$orgBackupBasePath = OrganizationClass::getOrgBaseBackupFolderDir($orgId);
	            $orgBackupFolderName = $currentDateTime;
	            $orgBackupBaseDir = $orgBackupBasePath . "/" . $orgBackupFolderName;

	            $orgBackupDbDumpFileName = "org-dump" . "." . "sql";
	            $orgBackupDbDumpPath = $orgBackupBaseDir . "/" . $orgBackupDbDumpFileName;

	            $orgUsersBackupDbDumpFileName = "org-user-dump" . "." . "sql";
	            $orgUsersBackupDbDumpPath = $orgBackupBaseDir . "/" . $orgUsersBackupDbDumpFileName;

	            $orgBackupAssetDir = $orgBackupBaseDir . "/" . Config::get('app_config.org_backup_asset_folder_name');

				if($isAppServer == 1)
				{
                    // Log::info('Inside isAppServer : orgBackupBaseDir : '.$orgBackupBaseDir);
					OrganizationClass::validateAndCreateLocalDir($orgBackupBaseDir);

					$orgBackupDir = OrganizationClass::getOrgContentAssetDirPath($orgId);
                    // Log::info('orgBackupDir : '.$orgBackupDir);
		            
		            $appDbName = env('DB_DATABASE');
		            $dbUserName = env('DB_USERNAME');
		            $dbPassword = env('DB_PASSWORD');

                    // Log::info('appDbName : '.$appDbName.' : dbUserName : '.$dbUserName.' : dbPassword : '.$dbPassword);

		            $orgUsersModels = New OrganizationUser;
		            $orgUsersTableName = $orgUsersModels->table;

                    // Log::info('orgUsersTableName : '.$orgUsersTableName);

	           		\Spatie\DbDumper\Databases\MySql::create()
		                        ->setDbName($orgDbName)
		                        ->setUserName($dbUserName)
		                        ->setPassword($dbPassword)
		                        ->dumpToFile($orgBackupDbDumpPath);

	           		\Spatie\DbDumper\Databases\MySql::create()
		                        ->setDbName($appDbName)
		                        ->setUserName($dbUserName)
		                        ->setPassword($dbPassword)
		                        ->includeTables($orgUsersTableName)
		                        ->dumpToFile($orgUsersBackupDbDumpPath);

					OrganizationClass::validateAndCreateLocalDir($orgBackupAssetDir);

					File::copyDirectory($orgAssetDir, $orgBackupAssetDir);	

					$zip_file = $orgBackupBaseDir . "/" . $orgBackupFolderName . '.zip';
					$zip = new \ZipArchive();
					$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

					$path = $orgBackupBaseDir;
			        $msgLogs .= 'path : ' . $path . '<br/>';
					$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
					$consPath = public_path() . "/" . $path;
			        $msgLogs .= 'consPath : ' . $consPath . '<br/>';
					foreach ($files as $name => $file)
					{
					    // We're skipping all subfolders
					    if (!$file->isDir()) {
					        $filePath     = $file->getRealPath();

					        $msgLogs .= 'filePath : ' . $filePath . '<br/>';

					        // extracting filename with substr/strlen
					        $relativePath = substr($filePath, strlen($consPath) + 1);

					        $msgLogs .= 'relativePath : ' . $relativePath . '<br/>';

					       	$zip->addFile($filePath, $relativePath);


					    }
					}
					$zip->close();

					File::delete($orgBackupDbDumpPath);	
					File::delete($orgUsersBackupDbDumpPath);	
					File::deleteDirectory($orgBackupAssetDir);	

					// return $orgBackupFolderName; //$msgLogs;
				}		
	        }
	            
		}

		return $orgBackupFolderName;
	}
	
	public static function deleteOrganizationDataBackup($orgId, $orgBackupFolderName)
	{
    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_db_server;

            $orgBackupBasePath = OrganizationClass::getOrgBaseBackupFolderDir($orgId);
            $orgBackupBaseDir = $orgBackupBasePath . "/" . $orgBackupFolderName;

			if($isAppServer == 1)
			{
				File::deleteDirectory($orgBackupBaseDir);	
			}		
		}
	}

	public function restoreOrganizationDataBackup($orgId, $orgBackupFolderName)
	{
		$status = 0;
		$msg = '';

    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_db_server;

            $orgBackupBasePath = OrganizationClass::getOrgBaseBackupFolderDir($orgId);
            $orgBackupBaseDir = $orgBackupBasePath . "/" . $orgBackupFolderName;
            $orgBackupExtractedFolderName = 'unzipped';
            $orgBackupExtractedDir = $orgBackupBaseDir . "/" . $orgBackupExtractedFolderName;

            $orgBackupDbDumpFileName = "org-dump" . "." . "sql";
            $orgBackupDbDumpPath = $orgBackupExtractedDir . "/" . $orgBackupDbDumpFileName;

            $orgUsersBackupDbDumpFileName = "org-user-dump" . "." . "sql";
            $orgUsersBackupDbDumpPath = $orgBackupExtractedDir . "/" . $orgUsersBackupDbDumpFileName;

			if($isAppServer == 1)
			{
				try
				{
					$zip_file = $orgBackupBaseDir . "/" . $orgBackupFolderName . '.zip';

					$orgAssetDir = OrganizationClass::getOrgBaseAssetFolderDir($orgId);
					$orgAssetTempDir = $orgAssetDir . "_TMP";
	            	$orgBackupAssetDir = $orgBackupExtractedDir . "/" . Config::get('app_config.org_backup_asset_folder_name');

					$zip = new \ZipArchive;
					$res = $zip->open($zip_file);
					if ($res === TRUE) 
					{
						$zip->extractTo($orgBackupExtractedDir . "/");
						$zip->close();

						File::copyDirectory($orgBackupAssetDir, $orgAssetTempDir);

						File::deleteDirectory($orgAssetDir);

						File::moveDirectory($orgAssetTempDir, $orgAssetDir);

	                	$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

	                	$orgRestoreTempDbConName = $this->createOrganizationRestoreTempDatabase($orgId, $orgBackupDbDumpPath, $orgUsersBackupDbDumpPath);

						File::deleteDirectory($orgBackupExtractedDir);

						$status = 1;
						$msg = 'Data restored successfully ';
					} 
					else 
					{
						$status = -1;
						$msg = 'Restore failed';
					}
				}
				catch(Exception $e)
				{
					$status = -1;
					$msg = 'Restore failed ' . $e;
				}
			}
			else
			{
				$status = -1;
				$msg = 'Invalid Data';
			}
		}
		else
		{
			$status = -1;
			$msg = 'Invalid Data';
		}

		$result = New \stdClass;
		$result->status = $status;
		$result->msg = $msg;

		return $result;
	}
	
	public function createOrganizationRestoreTempDatabase($orgId, $orgBackupDbDumpPath, $orgUsersBackupDbDumpPath)
	{
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        if($orgDbConName != "")
        {
        	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($orgServer))
			{
				$isAppServer = $orgServer->is_app_db_server;
				$orgDbName = $orgServer->dbname;
				$orgRestoreDbName = $orgDbName; // . '_tmp';

				if($isAppServer == 1)
				{
					$orgBackupDir = OrganizationClass::getOrgContentAssetDirPath($orgId);
		            
		            $dbUserName = env('DB_USERNAME');
		            $dbPassword = env('DB_PASSWORD');

		            $orgBackupModelObj = New OrgBackup;
		            $orgBackupModelObj->setConnection($orgDbConName);
		            $orgBackupTableName = $orgBackupModelObj->table;
		            $preRestoreBackups = $orgBackupModelObj->get();

					// Set all employees to inactive
					$removeEmployee = FALSE;
					$forceDelete = FALSE;
					// OrganizationClass::removeOrDeactivateOrganizationEmployees($orgId, $removeEmployee, $forceDelete);
		            
		            $orgEmployeeModelObj = New OrgEmployee;
		            $orgEmployeeModelObj->setConnection($orgDbConName);
       				$activeOrgEmployees = $orgEmployeeModelObj->active()->verified()->get();
       				foreach ($activeOrgEmployees as $index => $orgEmployee) {
       					$orgEmployeeId = $orgEmployee->employee_id;
						$this->sendOrgEmployeeRemovedToDevice($orgEmployeeId, TRUE, NULL, $orgId); 
       				}

					$dropCurrentDbSqlStr = 'DROP DATABASE IF EXISTS ' . $orgDbName . ';';

		        	DB::beginTransaction();
					DB::statement($dropCurrentDbSqlStr);
					// DB::connection($orgDbConName)->statement($renameRestoredDbSqlStr);
					DB::commit();

		        	DB::beginTransaction();
					DB::statement('CREATE DATABASE IF NOT EXISTS '.$orgRestoreDbName);
					DB::commit();

					$orgBackupDbDump = File::get($orgBackupDbDumpPath);
					DB::connection($orgDbConName)->getPdo()->exec($orgBackupDbDump);

					$orgUsersBackupDbDump = File::get($orgUsersBackupDbDumpPath);
					DB::connection($orgDbConName)->getPdo()->exec($orgUsersBackupDbDump);

					$backedupOrgUserModel = New OrganizationUser;
					$backedupOrgUserModel->setConnection($orgDbConName);
					$backedupOrgUsers = $backedupOrgUserModel->ofOrganization($orgId)->get();
       				$currentOrgUsers = OrganizationUser::ofOrganization($orgId)->get();


       				/* $backedUpOrgUserIdArr = array();
					foreach ($backedupOrgUsers as $index => $orguser) {
						$backedUpOrgUserIdArr[$index] = $orguser->organization_user_id;
					}

					$currentOrgUserIdArr = array();
       				OrganizationUser::ofOrganization($orgId)->delete();
       				foreach ($currentOrgUsers as $index => $orguser) {
       					$currOrgUserId = $orguser->organization_user_id;
						$currentOrgUserIdArr[$index] = $currOrgUserId;
       					if(in_array($backedUpOrgUserIdArr, $currOrgUserId))
       					{
       						// Update
       						// Not required
       						$backedupOrgUserIndex = array_search($currOrgUserId, $backedUpOrgUserIdArr);
       						$backedupOrgUser = $backedupOrgUsers[$backedupOrgUserIndex];

       						$updOrgUser = OrganizationUser::byId($currOrgUserId)->first();
				            $updOrgUser->is_verified = 0;
				            $updOrgUser->verification_code = NULL;
				            $updOrgUser->save();
       					}
       					else
       					{
       						// Delete
       						OrganizationUser::byId($currOrgUserId)->delete();
       					}
					}

					foreach ($backedupOrgUsers as $index => $orguser) {
						$backedUpOrgUserId = $orguser->organization_user_id;
						if(!in_array($currentOrgUserIdArr, $backedUpOrgUserId))
       					{
       						// Insert with ID
       						// Not required ???
       					}
					}*/

       				OrganizationUser::ofOrganization($orgId)->delete();
					foreach ($backedupOrgUsers as $index => $orguser) 
					{
						$verificationCode = CommonFunctionClass::generateVerificationCode();
						$encVerificationCode = Crypt::encrypt($verificationCode);

       					$orgUserObj = array();
				        $orgUserObj['organization_user_id'] = $orguser->organization_user_id;
				        $orgUserObj['organization_id'] = $orguser->organization_id;
				        $orgUserObj['appuser_email'] = $orguser->appuser_email;
				        $orgUserObj['is_verified'] = 0;
				        $orgUserObj['verification_code'] = $encVerificationCode;
				        $orgUserObj['emp_email'] = $orguser->emp_email;
				        $orgUserObj['emp_id'] = $orguser->emp_id;
				        $orgUserObj['is_self_registered'] = $orguser->is_self_registered;
				        
				        OrganizationUser::insert($orgUserObj);						
					}

			        $orgEmployeeModelObj = New OrgEmployee;
			        $orgEmployeeModelObj->setConnection($orgDbConName);
			        $orgEmpTableName = $orgEmployeeModelObj->table; 

					// Set all employees to inactive
       				$currentOrgEmployees = $orgEmployeeModelObj->active()->get();
       				foreach ($currentOrgEmployees as $index => $orgEmployee) {
       					$orgEmployee->is_active = 0;
       					$orgEmployee->is_verified = 0;
       					$orgEmployee->is_self_registered = 0;
       					$orgEmployee->save();
       				}

		            $orgBackupModelObj = New OrgBackup;
		            $orgBackupModelObj->setConnection($orgDbConName);
					// $orgBackupModelObj->query()->truncate();
					DB::connection($orgDbConName)->table($orgBackupTableName)->truncate();
					foreach ($preRestoreBackups as $index => $preRestoreBackup) 
					{
       					$backup = array();
				        $backup['backup_id'] = $preRestoreBackup->backup_id;
				        $backup['backup_desc'] = $preRestoreBackup->backup_desc;
				        $backup['backup_db_version'] = $preRestoreBackup->backup_db_version;
				        $backup['backup_filepath'] = $preRestoreBackup->backup_filepath;
				        $backup['created_by'] = $preRestoreBackup->created_by;
				        $backup['created_at'] = $preRestoreBackup->created_at;
				        $backup['is_deleted'] = $preRestoreBackup->is_deleted;
				        
				        DB::connection($orgDbConName)->table($orgBackupTableName)->insert($backup);
       				}


				}
			}
        }

        return $orgDbConName;		
	}

	public static function configureConnectionForOrganizationRestore($orgId)
	{
		$orgDbConName = "";

		$orgServer = OrganizationServer::ofOrganization($orgId)->first();

		if(isset($orgServer))
		{
			$isAppServer = $orgServer->is_app_db_server;
			$orgDbName = $orgServer->dbname . '_tmp';

        	$orgDbConName = 'orgDb'.$orgId.'_tmp';

			// Just get access to the config. 
		    $config = App::make('config');

		    // Will contain the array of connections that appear in our database config file.
		    $connections = $config->get('database.connections');

		    // This line pulls out the default connection by key (by default it's `mysql`)
		    $defaultConnection = $connections[$config->get('database.default')];

		    // Now we simply copy the default connection information to our new connection.
		    $newConnection = $defaultConnection;
		    // Override the database name.
	        $newConnection['driver']   = 'mysql';
		    $newConnection['database'] = $orgDbName;

		    if($isAppServer == 0)
		    {
				$orgHost = $orgServer->host;
				$orgUserName = $orgServer->username;
				$orgPassword = $orgServer->password;

			    $newConnection['host'] = $orgHost;
			    $newConnection['username'] = $orgUserName;
			    $newConnection['password'] = $orgPassword;		    	
		    }

		    // This will add our new connection to the run-time configuration for the duration of the request.
		    App::make('config')->set('database.connections.'.$orgDbConName, $newConnection);
		}
		return $orgDbConName;
	}
	
	public static function getCloudStorageIconAssetUrl($icFilename)
	{
		$iconUrl = "";
		if(isset($icFilename) && $icFilename != "")
		{
			$orgAssetDirPath = Config::get('app_config.appCloudStorageIconPath');
	        $filePath = $orgAssetDirPath."/".$icFilename;
	        $iconUrl = url($filePath);
		}			
		return $iconUrl;		
	}
	
	public static function getCloudCalendarIconAssetUrl($icFilename)
	{
		$iconUrl = "";
		if(isset($icFilename) && $icFilename != "")
		{
			$orgAssetDirPath = Config::get('app_config.appCloudCalendarIconPath');
	        $filePath = $orgAssetDirPath."/".$icFilename;
	        $iconUrl = url($filePath);
		}			
		return $iconUrl;		
	}
	
	public static function getCloudMailBoxIconAssetUrl($icFilename)
	{
		$iconUrl = "";
		if(isset($icFilename) && $icFilename != "")
		{
			$orgAssetDirPath = Config::get('app_config.appCloudMailBoxIconPath');
	        $filePath = $orgAssetDirPath."/".$icFilename;
	        $iconUrl = url($filePath);
		}			
		return $iconUrl;		
	}
}