<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Validator;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use App\Models\Api\AppuserDevice;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupMember;
use App\Models\Api\AppuserContact;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\GroupContentAttachment;
use App\Models\Api\AppuserContentTag;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationUser;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\OrganizationServer;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Libraries\FileUploadClass;
use DB;
use Crypt;
use Config;
use File;
use Image;
use App\Libraries\CommonFunctionClass;
use App\Libraries\MailClass;

use App\Libraries\TempOrganizationClass;

class TempController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    public function encPass_done() // _done
    {		
		$encPass = "eyJpdiI6ImM2S3BPRW9KZ1NFc3BjRCttNWVvRWc9PSIsInZhbHVlIjoiWnl3VWNsdlRwRlJcLzJXZWg4STJOc2Q5aW16VzNSSHNTZnB5NnQ0dDFhakU9IiwibWFjIjoiZDNhYTQ2YzcwM2E5ZmYwYWM1ODdiM2M4OTQ1YjUwZmI4OTRhNDc5Yzg2OThmMmI2MWQ3NTQ1NzkzODY1NjZhYSJ9";
		$decPass = Crypt::decrypt($encPass);
		// echo $decPass;

		// echo '<br/><br/>';

		$decPass = "CPWmXlvlxi7Dd0l";
		$encPass = Crypt::encrypt($decPass);
		// echo $encPass;
	}
    
    public function recalUserQuota_done()
    {
        $isFolder = TRUE;
		
		$orgId = 0;
		$orgEmpId = 0;
		print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
        
        /*$appusers = Appuser::where('is_verified','=',1)->get();
        foreach($appusers as $user)
		{
			$depMgmtObj = New ContentDependencyManagementClass;
        	$depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $orgEmpId);				
	        $depMgmtObj->recalculateUserQuota($isFolder);
		}*/
        
		/*$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
			
			$empModelObj = New OrgEmployee;
            $empModelObj->setConnection($orgDbConName);
            $employees = $empModelObj->get();
            foreach($employees as $emp)
			{
				$orgEmpId = $emp->employee_id;
				$depMgmtObj = New ContentDependencyManagementClass;
	       		$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
	       		$depMgmtObj->recalculateUserQuota($isFolder);
			}			
		}*/
	}
    
    public function recalGrpQuota_done()
    {
        $isFolder = FALSE;
		
		$orgId = 0;
		print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgId($orgId);
        $groups = $depMgmtObj->getAllGroupsFoUser();
        foreach($groups as $group)
		{
			$groupId = $group->group_id;				
	        $depMgmtObj->recalculateUserQuota($isFolder, $groupId);	
			
			print_r("<br/>"."For Group : ".$groupId."   \t ");
		}
        
		$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
			
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgId($orgId);
	        $groups = $depMgmtObj->getAllGroupsFoUser();
            foreach($groups as $group)
			{
				$groupId = $group->group_id;				
		        $depMgmtObj->recalculateUserQuota($isFolder, $groupId);	
				
				print_r("<br/>"."For Group : ".$groupId."   \t ");
			}			
		}
	}
    
    public function mapOrgEmpKey_done()
    {
		$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
            $empModelObj = New OrgEmployee;
            $empModelObj->setConnection($orgDbConName);
            $employees = $empModelObj->get();
            foreach($employees as $emp)
			{
				$empId = $emp->employee_id;
				$empEmail = $emp->email;	
				
				$orgUser = OrganizationUser::ofEmpEmail($empEmail)->ofOrganization($orgId)->first();
				
				if(isset($orgUser))
				{
					$orgUser->emp_id = $empId;
		        	$orgUser->save();
					print_r("<br/>"."For User : ".$orgUser->organization_user_id);
				}
					
				$encOrgEmpId = Hash::make($orgId."_".$empId);
		        $emp->org_emp_key = $encOrgEmpId;
		        $emp->save();
				print_r("<br/>"."For Employee : ".$empId."   \t "."Hash : ".$encOrgEmpId);
			}
			
		}
	}
    
    public function mapOrgSelfEnrolledUsers()
    {
		$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
            $empModelObj = New OrgEmployee;
            $empModelObj->setConnection($orgDbConName);
            $employees = $empModelObj->get();
            foreach($employees as $emp)
			{
				$empId = $emp->employee_id;
				$empEmail = $emp->email;	
				
				$orgUser = OrganizationUser::ofEmpEmail($empEmail)->ofOrganization($orgId)->first();
				
				if(isset($orgUser))
				{
					$orgUser->emp_id = $empId;
		        	$orgUser->save();
					print_r("<br/>"."For User : ".$orgUser->organization_user_id);
				}
					
				$encOrgEmpId = Hash::make($orgId."_".$empId);
		        $emp->org_emp_key = $encOrgEmpId;
		        $emp->save();
				print_r("<br/>"."For Employee : ".$empId."   \t "."Hash : ".$encOrgEmpId);
			}
			
		}
	}
    
    public function mapAppuserLastSyncTs()
    {
		
		$allAppusers = Appuser::select(['appusers.appuser_id as appuser_id', 'fullname', 'email'])
			        			->get();
		foreach($allAppusers as $index => $appuser)
		{
			$appuserId = $appuser->appuser_id;

            $maxAppuserSession = AppuserSession::ofUser($appuserId)->orderBy('last_sync_ts', 'DESC')->first();
            if(isset($maxAppuserSession))
            {
            	$lastSyncedAt = $maxAppuserSession->last_sync_ts;

				print_r("<br/><br/>"."For appuserId : ".$appuserId." : lastSyncedAt : ".$lastSyncedAt."<br/>");

				if(isset($lastSyncedAt) && $lastSyncedAt != "" && $lastSyncedAt != "0000-00-00 00:00:00")
				{
	            	$appuser->last_sync_ts = $lastSyncedAt;
	            	$appuser->save();

					print_r("<br/>"."Value saved <br/>");

				}
            }
		}
	}
    
    public function mapAppuserSocialLoginPassword()
    {
    	$srno = 0;
        $emailRegType = Appuser::$_IS_EMAIL_REGISTERED;
		
		$allAppusers = Appuser::where('is_app_registered', '!=', $emailRegType)->limit(5)->get();
		foreach($allAppusers as $index => $appuser)
		{
			$appuserId = $appuser->appuser_id;
        	$userName = $appuser->fullname;

            if(isset($appuser->password) && $appuser->password != "")
            {

            }
            else
            {
                $genPassword = CommonFunctionClass::generateAppuserPassword();
                $encPassword = Hash::make($genPassword);

                $appuser->password = $encPassword;
                // $appuser->save();

                $srno++;

                print_r('srno : '.$srno.'<br/>');
                print_r('appuserId : '.$appuserId.' : userName : '.$userName.' : genPassword : '.$genPassword.'<br/>');
                print_r('is_deleted : '.$appuser->is_deleted.' : is_active : '.$appuser->is_active.'<br/>');

            	if($appuser->is_active == 1 && $appuser->is_deleted == 0)
            	{
                	print_r('will send mail<br/>');
                    // MailClass::sendSocialLoginPasswordIntimationMail($appuserId, $genPassword);
            	}
            	else
            	{
                	print_r('wont send mail<br/>');
            	}

            	print_r('<br/>=================-------======================<br/><br/>');
            }
		}
	}
    
    public function testEncryption12()
    {
		$keyForEnc = "1";
		
		for($i=0; $i<10; $i++)
		{
			$encKey = $this->getEncValue($keyForEnc);
			
			print_r(($i+1).") ");
			print_r($encKey);
			print_r("<br/>");
		}
	}
	
	function getEncValue($plaintext)
	{
		$keyForUse = "SomeKeyITIS";
		//return md5($plaintext);
		
		$c = new McryptCipher($keyForUse);
		$encrypted = $c->encrypt($plaintext);
		$decrypted = $c->decrypt($encrypted);
		return $encrypted;
	}
	
	function encrypt( $key, $plaintext, $meta = '' ) {
		// Generate valid key
		$key = hash_pbkdf2( 'sha256', $key, '', 10000, 0, true );
		// Serialize metadata
		$meta = serialize($meta);
		// Derive two subkeys from the original key
		$mac_key = hash_hmac( 'sha256', 'mac', $key, true );
		$enc_key = hash_hmac( 'sha256', 'enc', $key, true );
		$enc_key = substr( $enc_key, 0, 32 );
		// Derive a "synthetic IV" from the nonce, plaintext and metadata
		$temp = $nonce = ( 16 > 0 ? mcrypt_create_iv( 16 ) : "" );
		$temp .= hash_hmac( 'sha256', $plaintext, $mac_key, true );
		$temp .= hash_hmac( 'sha256', $meta, $mac_key, true );
		$mac = hash_hmac( 'sha256', $temp, $mac_key, true );
		$siv = substr( $mac, 0, 16 );
		// Encrypt the message
		$enc = mcrypt_encrypt( 'rijndael-128', $enc_key, $plaintext, 'ctr', $siv );
		return base64_encode( $siv . $nonce . $enc );
	}
	
    public function mapLiveDbToLocDbTags()
    {
    	set_time_limit(0);
    	
    	//$remUserIdArr = [392];
    	//$remUserIdArr = [392, 56];
    	$remUserIdArr = [392, 56, 13, 102, 631, 654, 528];
    	$remUserIdArr = [392, 56, 13, 102, 631, 654, 528, 72];
    	$remUserIdArr = [72];
    	$allUsers = array();
		$allUsers = Appuser::exists()->where('is_verified','=','1')->get();
		
		foreach($allUsers as $user)
		{
			$userId = $user->appuser_id;
			
			if(in_array($userId, $remUserIdArr))
			{				
				$allTags = AppuserTag::ofUser($userId)->get();			
				$tagLocToSyncMap = array();
				foreach($allTags as $tagObj)
				{
					$locId = $tagObj->tag_id;
					$syncId = $tagObj->appuser_tag_id;
					
					DB::table('appuser_content_tags')
						->where('appuser_id', $userId)
						->where('tag_id', $locId)
						->update(['source_id' => $syncId]);
				}
				
				print_r("Data has been updated for  : ".$userId."<br/>");
			}	
		}
	}
    
    public function mapLiveDbToLocDb56()
    {
    	set_time_limit(0);
    	
    	//$remUserIdArr = [392];
    	//$remUserIdArr = [392, 56];
    	$remUserIdArr = [392, 56, 13, 102, 631, 654, 528];
    	$remUserIdArr = [392, 56, 13, 102, 631, 654, 528, 72];
    	$allUsers = array();
		$allUsers = Appuser::exists()->where('is_verified','=','1')->get();
		
		foreach($allUsers as $user)
		{
			$userId = $user->appuser_id;
			
			if(!in_array($userId, $remUserIdArr))
			{				
				$allTags = AppuserTag::ofUser($userId)->get();			
				$tagLocToSyncMap = array();
				foreach($allTags as $tagObj)
				{
					$locId = $tagObj->tag_id;
					$syncId = $tagObj->appuser_tag_id;
					
					DB::table('appuser_content_tags')
						->join('appuser_contents', 'appuser_contents.appuser_content_id', '=', 'appuser_content_tags.appuser_content_id')
						->where('appuser_id', $userId)
						->where('tag_id', $locId)
						->update(['tag_id' => $syncId]);
				}
				
				print_r("Data has been updated for  : ".$userId."<br/>");
			}	
		}
	}
    
    public function mapLiveDbToLocDb()
    {
    	set_time_limit(0);
    	
    	//$remUserIdArr = [392];
    	$remUserIdArr = [];
    	$allUsers = array();
		$allUsers = Appuser::exists()->where('is_verified','=','1')->get();
		
		foreach($allUsers as $user)
		{
			$userId = $user->appuser_id;
			
			if(!in_array($userId, $remUserIdArr))
			{				
				$allFolders = AppuserFolder::ofUser($userId)->get();			
				$folderLocToSyncMap = array();
				foreach($allFolders as $folObj)
				{
					$locId = $folObj->folder_id;
					$syncId = $folObj->appuser_folder_id;
					$folderLocToSyncMap[$locId] = $syncId;
				}	
				
				$userConst = AppuserConstant::ofUser($userId)->first();
				if(isset($userConst))
				{					
					$locFolId = $userConst->def_folder_id;
					$syncFolId = 0;
					if(isset($folderLocToSyncMap[$locFolId]))
					{
						$syncFolId = $folderLocToSyncMap[$locFolId];
						$userConst->def_folder_id = $syncFolId;
					}
					$userConst->save();
				}
				
				print_r("Data has been updated for  : ".$userId."<br/>");
			}	
		}
	}
    
    public function mapLiveDbToLocDbOrg()
    {
    	set_time_limit(0);
    	
    	//$remUserIdArr = [392];
    	$remUserIdArr = [392, 56];
    	$allUsers = array();
		$allUsers = Appuser::exists()->where('is_verified','=','1')->get();
		
		foreach($allUsers as $user)
		{
			$userId = $user->appuser_id;
			
			if(!in_array($userId, $remUserIdArr))
			{
				$regToken = $user->reg_token;
				$loginToken = $user->login_token;
				
				if($regToken != "" && $loginToken != "")
				{
					$userSessionData = array();
					$userSessionData["appuser_id"] = $userId;
					$userSessionData["reg_token"] = $regToken;
					$userSessionData["login_token"] = $loginToken;
					$userSessionData["last_sync_ts"] = $user->last_sync_ts;
					
					print_r('userSessionData: ');
					print_r($userSessionData);
				}					
				
				//$userSession = AppuserSession::create($userSessionData);
				
				$allFolders = AppuserFolder::ofUser($userId)->get();			
				$folderLocToSyncMap = array();
				foreach($allFolders as $folObj)
				{
					$locId = $folObj->folder_id;
					$syncId = $folObj->appuser_folder_id;
					$folderLocToSyncMap[$locId] = $syncId;
				}	
				
				$allSources = AppuserSource::ofUser($userId)->get();			
				$sourceLocToSyncMap = array();
				foreach($allSources as $srcObj)
				{
					$locId = $srcObj->source_id;
					$syncId = $srcObj->appuser_source_id;
					$sourceLocToSyncMap[$locId] = $syncId;
				}
				
				$allTags = AppuserTag::ofUser($userId)->get();			
				$tagLocToSyncMap = array();
				foreach($allTags as $tagObj)
				{
					$locId = $tagObj->tag_id;
					$syncId = $tagObj->appuser_tag_id;
					$tagLocToSyncMap[$locId] = $syncId;
				}
				
				$allContents = AppuserContent::ofUser($userId)->get();
				foreach($allContents as $conObj)
				{
					$locFolId = $conObj->folder_id;
					$syncFolId = 0;
					if(isset($folderLocToSyncMap[$locFolId]))
					{
						$syncFolId = $folderLocToSyncMap[$locFolId];
						$conObj->folder_id = $syncFolId;
					}
					
					$locSrcId = $conObj->source_id;
					$syncSrcId = 0;
					if(isset($sourceLocToSyncMap[$locSrcId]))
					{
						$syncSrcId = $sourceLocToSyncMap[$locSrcId];
						$conObj->source_id = $syncSrcId;
					}
					
            		/*$contTags = AppuserContentTag::ofUserContent($conObj->appuser_content_id)->get();
            		foreach($contTags as $conTag)
            		{
						$locTagId = $conTag->tag_id;
						$syncTagId = 0;
						if(isset($folderLocToSyncMap[$locTagId]))
						{
							$syncTagId = $folderLocToSyncMap[$locTagId];
							$conTag->tag_id = $syncTagId;
							//$conTag->save();
						}
					}*/
					
					//$conObj->save();
				}
				
				$userConst = AppuserConstant::ofUser($userId)->first();
				if(isset($userConst))
				{					
					$locFolId = $userConst->default_folder_id;
					$syncFolId = 0;
					if(isset($folderLocToSyncMap[$locFolId]))
					{
						$syncFolId = $folderLocToSyncMap[$locFolId];
						$userConst->default_folder_id = $syncFolId;
					}
					
					$locSrcId = $userConst->email_source_id;
					$syncSrcId = 0;
					if(isset($sourceLocToSyncMap[$locSrcId]))
					{
						$syncSrcId = $sourceLocToSyncMap[$locSrcId];
						$userConst->email_source_id = $syncSrcId;
					}
					//$userConst->save();
				}
				
				print_r("Data has been updated for  : ".$userId."<br/>");
			}
			else
			{
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
				//remove user	
				/*AppuserFolder::ofUser($userId)->delete();
				AppuserSource::ofUser($userId)->delete();
				AppuserTag::ofUser($userId)->delete();*/
				/*$allContents = AppuserContent::ofUser($userId)->get();
				foreach($allContents as $contObj)
				{
					$contId = $contObj->appuser_content_id;					
                	//$depMgmtObj->deleteContent($contId);
				}*/
				print_r("Data has been deleted for  : ".$userId."<br/>");
			}	
		}
	}

	public function encryptUserPins_temp() {
		$userConstants = AppuserConstant::get();
		foreach($userConstants as $usr)
		{
			$hasPassword = $usr->passcode_enabled;
			$password = $usr->passcode;	
			
			print_r("<br/>"."For User : ".$usr->appuser_id."<br/>");
			
			$encPassword = "";
			if($hasPassword == 1) {
				$encPassword = Crypt::encrypt($password);
				print_r("password : ".$password." : encPassword : ".$encPassword."<br/>");
			}
			
			$hasFolderPassword = $usr->folder_passcode_enabled;	
			$folderPassword = $usr->folder_passcode;
			
			$encFolderPassword = "";
			if($hasFolderPassword == 1) {
				$encFolderPassword = Crypt::encrypt($folderPassword);
				print_r("folderPassword : ".$folderPassword." : encFolderPassword : ".$encFolderPassword."<br/>");
			}
			
			
	        $usr->passcode = $encPassword;
	        $usr->folder_passcode = $encFolderPassword;
	        //$usr->save();
		}
		
		
		$organizations = Organization::where('is_deleted', '=', '0')->get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
            if($orgDbConName != "") {
				$empModelObj = New OrgEmployeeConstant;
	            $empModelObj->setConnection($orgDbConName);
	            $employeeConstants = $empModelObj->get();
	            foreach($employeeConstants as $emp)
				{				
					$hasFolderPassword = $emp->folder_passcode_enabled;	
					$folderPassword = $emp->folder_passcode;
				
					$encFolderPassword = "";
					if($hasFolderPassword == 1) {
						$encFolderPassword = Crypt::encrypt($folderPassword);
						print_r("folderPassword : ".$folderPassword." : encFolderPassword : ".$encFolderPassword."<br/>");
					}
					
			        $emp->folder_passcode = $encFolderPassword;
			        //$emp->save();
				}
			}
		}
	}

	public function encryptAttachmentAndCreateThumb_temp() {
    	set_time_limit(0);
		//print_r("<br/><br/>"."For Personal : <br/>");
		
		/* Retail */
			$orgId = 0;
			/* Content Attachment */			
			/*$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
			$tempOrgAssetDirPath = TempOrganizationClass::getOrgContentAssetDirPath($orgId);
			
			$contentAttachments = AppuserContentAttachment::get();
			print_r("For Folder contentAttachments : <br/>");
			foreach($contentAttachments as $attachment)
			{
				$filename = $attachment->server_filename;
				if(isset($filename) && $filename != "") {
					print_r("For filename : $filename<br/>");
					$this->encryptFile($filename, $tempOrgAssetDirPath, $orgAssetDirPath);
				}
			}*/
			/* Content Attachment */
			
			/* Group Content Attachment */			
			/*$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
			$tempOrgAssetDirPath = TempOrganizationClass::getOrgContentAssetDirPath($orgId);
			
			$contentAttachments = GroupContentAttachment::get();
			print_r("For Group contentAttachments : <br/>");
			foreach($contentAttachments as $attachment)
			{
				$filename = $attachment->server_filename;
				if(isset($filename) && $filename != "") {
					print_r("For filename : $filename<br/>");
					$this->encryptFile($filename, $tempOrgAssetDirPath, $orgAssetDirPath);
				}
			}*/
			/* Group Content Attachment */
			
		/* Retail */
		
		
		$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
			
			if(isset($orgDbConName) && $orgDbConName != "") {
				
				/* Content Attachment */
				/*$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
				$tempOrgAssetDirPath = TempOrganizationClass::getOrgContentAssetDirPath($orgId);
				
	            $contAttModelObj = New OrgEmployeeContentAttachment;
	            $contAttModelObj->setConnection($orgDbConName);
	            $contentAttachments = $contAttModelObj->get();
				print_r("For Folder contentAttachments : <br/>");
	            foreach($contentAttachments as $attachment)
				{
					$filename = $attachment->server_filename;
					if(isset($filename) && $filename != "") {
						print_r("For filename : $filename<br/>");
						//$this->encryptFile($filename, $tempOrgAssetDirPath, $orgAssetDirPath);
					}
				}*/
				/* Content Attachment */
				
				/* Group Content Attachment */			
				/*$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
				$tempOrgAssetDirPath = TempOrganizationClass::getOrgContentAssetDirPath($orgId);
				
	            $contAttModelObj = New OrgGroupContentAttachment;
	            $contAttModelObj->setConnection($orgDbConName);
	            $contentAttachments = $contAttModelObj->get();
				print_r("For Group contentAttachments : <br/>");
				foreach($contentAttachments as $attachment)
				{
					$filename = $attachment->server_filename;
					if(isset($filename) && $filename != "") {
						print_r("For filename : $filename<br/>");
						//$this->encryptFile($filename, $tempOrgAssetDirPath, $orgAssetDirPath);					
					}
				}*/
				/* Group Content Attachment */
				
			}
		}
	}
	
	private function encryptFile($filename, $tempOrgAssetDirPath, $orgAssetDirPath)
	{	
		if(isset($filename) && $filename != '' && isset($tempOrgAssetDirPath) && $tempOrgAssetDirPath != '' && isset($orgAssetDirPath) && $orgAssetDirPath != '') {
			$orgThumbAssetDirPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');	
		    $orgFilePath = $tempOrgAssetDirPath."/".$filename; 
			
			print_r("encryptFile orgFilePath : $orgFilePath<br/>");
			
		    if(File::exists($orgFilePath)) {
		    	$newFilePath = $orgAssetDirPath.'/'.$filename;
				print_r("newFilePath : $newFilePath<br/>");
		    	
		    	$fileContent = File::get($orgFilePath);	
		    	$encFileContent = Crypt::encrypt($fileContent);
		    	File::put($newFilePath, $encFileContent);
		    	
		    	$isTypeImage = checkIfFileTypeImageFromFileName($filename);
					print_r("isTypeImage : $isTypeImage<br/>");
			
				if($isTypeImage) {
					$imagePath = $orgFilePath;
					list($imageWidth, $imageHeight) = getimagesize($imagePath);

					/* Thumb Image Upload Begins */
			        $thumbsHeight = Config::get('app_config.thumb_photo_h');
			        $thumbsWidth = Config::get('app_config.thumb_photo_w');

			        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
			        $thumbHeight = $thumbDimensions['height'];
			        $thumbWidth = $thumbDimensions['width'];

			        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
			        $thumbImage = Image::make($imagePath)
			        					->resize($thumbWidth, $thumbHeight,  function ($c) {
										    $c->aspectRatio();
										    $c->upsize();
										});

					$thumbBackground->insert($thumbImage, 'center');
					
		    		$tempThumbFilePath = $orgThumbAssetDirPath."/temp_".$filename;					
					$thumbBackground->save($tempThumbFilePath);		    	
		    		$thumbFileContent = File::get($tempThumbFilePath);	
		        	File::delete($tempThumbFilePath);
		    		
		    		$newThumbFilePath = $orgThumbAssetDirPath."/".$filename;
		        	$encThumbFileContent = Crypt::encrypt($thumbFileContent);
		        	File::put($newThumbFilePath, $encThumbFileContent);
					/* Thumb Image Upload Ends */
		        	
				}
				
			}
		}
	}
	
	public function addIsGhostColumnToGroupOrgDb()
	{	
		$colSqlStr = "is_ghost smallint(1) NOT NULL DEFAULT '0'";
	
	}
    
    public function modifyAllOrganizationDatabase_USE_LATER() // _USE_LATER
    {
    	$orgModifyTableQuery1 = "ALTER TABLE `employee_constants` ADD `is_file_save_share_enabled` SMALLINT(1) NOT NULL DEFAULT '1' AFTER `is_soc_other_enabled`;";
    	$orgModifyTableQuery2 = "ALTER TABLE `employee_content_attachments` ADD `att_cloud_storage_type_id` INT(2) NOT NULL DEFAULT '0' AFTER `server_filename`, ADD `cloud_file_url` VARCHAR(300) NULL DEFAULT NULL AFTER `att_cloud_storage_type_id`, ADD `cloud_file_id` VARCHAR(150) NULL DEFAULT NULL AFTER `cloud_file_url`, ADD `create_ts` BIGINT NULL DEFAULT NULL AFTER `cloud_file_id`, ADD `update_ts` BIGINT NULL DEFAULT NULL AFTER `create_ts`, ADD `cloud_file_thumb_str` VARCHAR(500) NULL DEFAULT NULL AFTER `update_ts`;";
    	$orgModifyTableQuery3 = "ALTER TABLE `org_group_content_attachments` ADD `att_cloud_storage_type_id` INT(2) NOT NULL DEFAULT '0' AFTER `server_filename`, ADD `cloud_file_url` VARCHAR(300) NULL DEFAULT NULL AFTER `att_cloud_storage_type_id`, ADD `cloud_file_id` VARCHAR(150) NULL DEFAULT NULL AFTER `cloud_file_url`, ADD `create_ts` BIGINT NULL DEFAULT NULL AFTER `cloud_file_id`, ADD `update_ts` BIGINT NULL DEFAULT NULL AFTER `create_ts`, ADD `cloud_file_thumb_str` VARCHAR(500) NULL DEFAULT NULL AFTER `update_ts`;";
    	$orgModifyTableQuery4 = "ALTER TABLE `employee_folders` ADD `virtual_folder_sender_email` VARCHAR(150) NULL DEFAULT NULL AFTER `applied_filters`;";

    	$orgModifyTableQuery5 = "ALTER TABLE `employee_folders` ADD `content_modified_at` BIGINT NULL DEFAULT NULL AFTER `virtual_folder_sender_email`;";
    	$orgModifyTableQuery6 = "ALTER TABLE `org_groups` ADD `content_modified_at` BIGINT NULL DEFAULT NULL AFTER `used_space_kb`;";

    	/* 14/03/2021 */
    	$orgModifyTableQuery7 = "ALTER TABLE `employee_contents` ADD `sync_with_cloud_calendar_google` TINYINT(1) NOT NULL DEFAULT '0' AFTER `update_timestamp`, ADD `sync_with_cloud_calendar_onedrive` TINYINT(1) NOT NULL DEFAULT '0' AFTER `sync_with_cloud_calendar_google`;";
    	$orgModifyTableQuery8 = "ALTER TABLE `org_group_contents` ADD `sync_with_cloud_calendar_google` TINYINT(1) NOT NULL DEFAULT '0' AFTER `repeat_duration`, ADD `sync_with_cloud_calendar_onedrive` TINYINT(1) NOT NULL DEFAULT '0' AFTER `sync_with_cloud_calendar_google`;";
    	$orgInsertTableQuery1 = "CREATE TABLE IF NOT EXISTS `org_employee_content_cloud_calendar_mappings` ( `employee_content_cloud_calendar_mapping_id` bigint(20) NOT NULL AUTO_INCREMENT, `org_employee_id` bigint(20) NOT NULL, `mapped_cloud_calendar_type_id` int(3) NOT NULL, `is_folder` tinyint(1) DEFAULT '1', `usr_content_id` bigint(20) DEFAULT NULL, `grp_content_id` bigint(20) DEFAULT NULL, `reference_id` varchar(800) DEFAULT NULL, `calendar_id` varchar(400) NOT NULL, `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`employee_content_cloud_calendar_mapping_id`), KEY `org_employee_id` (`org_employee_id`) ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";


    	$orgModifyTableQuery9 = "ALTER TABLE `org_employee_content_cloud_calendar_mappings` ADD `src_is_hylyt` TINYINT(1) NOT NULL DEFAULT '1' AFTER `calendar_id`;";

    	/* 24/04/2021 */
    	$orgModifyTableQuery10 = "ALTER TABLE `employee_contents` ADD `is_completed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `repeat_duration`, ADD `is_snoozed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_completed`, ADD `reminder_timestamp` BIGINT(20) NULL DEFAULT NULL AFTER `is_snoozed`;";
    	$orgModifyTableQuery11 = "ALTER TABLE `org_group_contents` ADD `is_completed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `repeat_duration`, ADD `is_snoozed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_completed`, ADD `reminder_timestamp` BIGINT(20) NULL DEFAULT NULL AFTER `is_snoozed`;";

    	/* 13/08/2021 */
    	/* --- CANCELLED --- */
    	$orgModifyTableQuery12 = "ALTER TABLE `employee_contents` ADD `upcoming_reminder_timestamp` BIGINT(20) NULL DEFAULT NULL AFTER `reminder_timestamp`;";
    	$orgModifyTableQuery13 = "ALTER TABLE `org_group_contents` ADD `upcoming_reminder_timestamp` BIGINT(20) NULL DEFAULT NULL AFTER `reminder_timestamp`;";
    	/* --- CANCELLED --- */

    	/* 31/08/2021 */
    	$orgModifyTableQuery14 = "ALTER TABLE `employee_constants` ADD `is_screen_share_enabled` smallint(1) NOT NULL DEFAULT '0' AFTER `is_file_save_share_enabled`;";

    	/* 06/09/2021 */
    	$orgInsertTableQuery2 = "CREATE TABLE IF NOT EXISTS `org_employee_content_additional_data` ( `employee_content_additional_data_id` bigint(20) NOT NULL AUTO_INCREMENT, `org_employee_id` bigint(20) NOT NULL, `is_folder` tinyint(1) DEFAULT '1', `usr_content_id` bigint(20) DEFAULT NULL, `grp_content_id` bigint(20) DEFAULT NULL, `notif_reminder_ts` BIGINT(20) NULL DEFAULT NULL, `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`employee_content_additional_data_id`), KEY `org_employee_id` (`org_employee_id`) ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

    	/* 01/04/2022 */
    	$orgInsertTableQuery3 = "CREATE TABLE IF NOT EXISTS `system_tags` ( `system_tag_id` bigint(20) NOT NULL AUTO_INCREMENT, `tag_name` varchar(255) NOT NULL, `is_active` smallint(6) NOT NULL DEFAULT '1', `created_by` int(11) NOT NULL DEFAULT '0', `updated_by` int(11) NOT NULL DEFAULT '0', `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, `is_deleted` smallint(6) NOT NULL DEFAULT '0', `deleted_by` int(11) NOT NULL DEFAULT '0', `deleted_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`system_tag_id`) ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

    	/* 03/04/2022 */
    	$orgModifyTableQuery15 = "ALTER TABLE `employee_tags` ADD `rel_system_tag_id` BIGINT(20) NULL DEFAULT NULL AFTER `tag_name`;";

		$orgModifyTableQueries = array();


		/* DONE */
		// array_push($orgModifyTableQueries, $orgModifyTableQuery1);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery2);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery3);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery4);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery5);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery6);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery7);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery8);
		// array_push($orgModifyTableQueries, $orgInsertTableQuery1);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery9);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery10);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery11);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery12);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery13);
		// array_push($orgModifyTableQueries, $orgModifyTableQuery14);
		// array_push($orgModifyTableQueries, $orgInsertTableQuery2);
		// array_push($orgModifyTableQueries, $orgInsertTableQuery3);
		array_push($orgModifyTableQueries, $orgModifyTableQuery15);
		/* DONE */

		$orgSrNo = 1;

		$organizations = Organization::get();
		$totalOrgCount = count($organizations);
		$totalOrgDbChangesMade = 0;
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			
			print_r("<br/><br/>".$orgSrNo.") For Organization : ".$orgId." : org : ".$org->regd_name."<br/>");
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

	        if($orgDbConName != "")
	        {
	        	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

				if(isset($orgServer))
				{
					$isAppServer = $orgServer->is_app_db_server;
					$orgDbName = $orgServer->dbname;

					print_r("orgId : ".$orgId." : isAppServer : ".$isAppServer."<br/>");

					if($isAppServer == 1)
					{
						$dbExistsQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
				        $dbExists = DB::select($dbExistsQuery, [$orgDbName]);
				        if (empty($dbExists)) 
				        {
							print_r("orgId : ".$orgId." : No such db exists : ".$orgDbName."<br/>");
				        } 
				        else 
				        {
							print_r("orgId : ".$orgId." : Db already exists : ".$orgDbName."<br/>");

							DB::connection($orgDbConName)->beginTransaction();

							if(isset($orgModifyTableQueries) && count($orgModifyTableQueries) > 0)
							{
								foreach ($orgModifyTableQueries as $orgModifyTableQuery) 
								{
									if($orgModifyTableQuery != "")
									{
										$totalOrgDbChangesMade++;
										print_r("orgId : ".$orgId." : orgModifyTableQuery : ".$orgModifyTableQuery."<br/>");
										// DB::connection($orgDbConName)->statement($orgModifyTableQuery);
									}	
								}
							}

							DB::connection($orgDbConName)->commit();
				        }
					}
				}
	        }	

	        $orgSrNo++;		
		}

		print_r("<br/><br/>"."TOTAL ORG : ".$totalOrgCount." : TOTAL ORG CHANGES MADE : ".$totalOrgDbChangesMade."<br/>");
	}
	
	private function addColumnToOrgDb($colSqlStr)
	{	
		if(isset($filename) && $filename != '' && isset($tempOrgAssetDirPath) && $tempOrgAssetDirPath != '' && isset($orgAssetDirPath) && $orgAssetDirPath != '') {
			$orgThumbAssetDirPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');	
		    $orgFilePath = $tempOrgAssetDirPath."/".$filename; 
			
			print_r("encryptFile orgFilePath : $orgFilePath<br/>");
			
		    if(File::exists($orgFilePath)) {
		    	$newFilePath = $orgAssetDirPath.'/'.$filename;
				print_r("newFilePath : $newFilePath<br/>");
		    	
		    	$fileContent = File::get($orgFilePath);	
		    	$encFileContent = Crypt::encrypt($fileContent);
		    	File::put($newFilePath, $encFileContent);
		    	
		    	$isTypeImage = checkIfFileTypeImageFromFileName($filename);
					print_r("isTypeImage : $isTypeImage<br/>");
			
				if($isTypeImage) {
					$imagePath = $orgFilePath;
					list($imageWidth, $imageHeight) = getimagesize($imagePath);

					/* Thumb Image Upload Begins */
			        $thumbsHeight = Config::get('app_config.thumb_photo_h');
			        $thumbsWidth = Config::get('app_config.thumb_photo_w');

			        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
			        $thumbHeight = $thumbDimensions['height'];
			        $thumbWidth = $thumbDimensions['width'];

			        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
			        $thumbImage = Image::make($imagePath)
			        					->resize($thumbWidth, $thumbHeight,  function ($c) {
										    $c->aspectRatio();
										    $c->upsize();
										});

					$thumbBackground->insert($thumbImage, 'center');
					
		    		$tempThumbFilePath = $orgThumbAssetDirPath."/temp_".$filename;					
					$thumbBackground->save($tempThumbFilePath);		    	
		    		$thumbFileContent = File::get($tempThumbFilePath);	
		        	File::delete($tempThumbFilePath);
		    		
		    		$newThumbFilePath = $orgThumbAssetDirPath."/".$filename;
		        	$encThumbFileContent = Crypt::encrypt($thumbFileContent);
		        	File::put($newThumbFilePath, $encThumbFileContent);
					/* Thumb Image Upload Ends */
		        	
				}
				
			}
		}
	}

	public function formatAttachmentName_temp() {
    	set_time_limit(0);
		print_r("<br/><br/>"."For Personal : <br/>");
		
		/* Retail */
			$orgId = 0;
			/* Content Attachment */			
					
			$contentAttachments = AppuserContentAttachment::get();
			print_r("For Folder contentAttachments : <br/>");
			foreach($contentAttachments as $attachment)
			{
				$attachmentId = $attachment->content_attachment_id;
				$filename = $attachment->filename;
			 	$formattedFilename = $this->getFormattedAttachmentName($filename);
        		
        		if($formattedFilename != $filename && $formattedFilename != '') {
	        		$attachment->filename = $formattedFilename;
	        		// $attachment->save();
					
					print_r("For ID : $attachmentId filename : $filename : formattedFilename : $formattedFilename<br/>");
				}
					
			}
			/* Content Attachment */
			
			/* Group Content Attachment */
			$contentAttachments = GroupContentAttachment::get();
			print_r("For Group contentAttachments : <br/>");
			foreach($contentAttachments as $attachment)
			{
				$attachmentId = $attachment->content_attachment_id;
				$filename = $attachment->filename;
			 	$formattedFilename = $this->getFormattedAttachmentName($filename);
        		
        		if($formattedFilename != $filename) {
	        		$attachment->filename = $formattedFilename;
	        		// $attachment->save();
					
					print_r("For ID : $attachmentId filename : $filename : formattedFilename : $formattedFilename<br/>");
				}
			}
			/* Group Content Attachment */
			
		/* Retail */
		
		
		$organizations = Organization::get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
			
			if(isset($orgDbConName) && $orgDbConName != "") {
				
				/* Content Attachment */				
	            $contAttModelObj = New OrgEmployeeContentAttachment;
	            $contAttModelObj->setConnection($orgDbConName);
	            $contentAttachments = $contAttModelObj->get();
				print_r("For Folder contentAttachments : <br/>");
	            foreach($contentAttachments as $attachment)
				{
					$attachmentId = $attachment->content_attachment_id;
					$filename = $attachment->filename;
				 	$formattedFilename = $this->getFormattedAttachmentName($filename);
	        		
	        		if($formattedFilename != $filename && $formattedFilename != '') {
		        		$attachment->filename = $formattedFilename;
		        		$attachment->save();
						
						print_r("For ID : $attachmentId filename : $filename : formattedFilename : $formattedFilename<br/>");
					}
				}
				/* Content Attachment */
				
				/* Group Content Attachment */
	            $contAttModelObj = New OrgGroupContentAttachment;
	            $contAttModelObj->setConnection($orgDbConName);
	            $contentAttachments = $contAttModelObj->get();
				print_r("For Group contentAttachments : <br/>");
				foreach($contentAttachments as $attachment)
				{
					$attachmentId = $attachment->content_attachment_id;
					$filename = $attachment->filename;
				 	$formattedFilename = $this->getFormattedAttachmentName($filename);
	        		
	        		if($formattedFilename != $filename && $formattedFilename != '') {
		        		$attachment->filename = $formattedFilename;
		        		// $attachment->save();
						
						print_r("For ID : $attachmentId filename : $filename : formattedFilename : $formattedFilename<br/>");
					}
				}
				/* Group Content Attachment */
				
			}
		}
	}

	private function getFormattedAttachmentName($filename) {
		$formattedFilename = '';
		if(isset($filename) && $filename != "") {
			$tmp = explode(".", $filename);
			
			if(count($tmp) > 1) {
				$extension = end($tmp);
				$onlyFilename = basename($filename, ".".$extension);
				$formattedFilename = $onlyFilename.".".strtolower($extension);
			}
		}
		return $formattedFilename;
	}

	public function sanitizeAppuserContactDetails_temp() {
    	set_time_limit(0);					
			
		$srNo = 1;		
    	$userContacts = AppuserContact::get();
		print_r("For AppuserContacts : <br/>");
		foreach($userContacts as $userContact)
		{
			$contactNo = $userContact->contact_no;
			$sanContactNo = sanitizeContactNoString($contactNo);
		 	
		 	if(isset($contactNo) && $contactNo != "")
		 	{
				print_r("$srNo) contactNo : $contactNo & sanContactNo : $sanContactNo<br/>");

				$userContact->org_contact_no = $contactNo;
				$userContact->contact_no = $sanContactNo;
				// $userContact->save();

				$srNo++;
		 	}			
		}
	}
    
    public function setupAllOrganizationContentReminderTimeStamp() // _USE_LATER
    {
		$orgSrNo = 0;

        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");

        $filTypeArr = [ $typeR, $typeC ];

		$orgId = 0;
		$encOrgId = "";

		$isFolder = TRUE;
		$allRCFolderContents = AppuserContent::filterType($filTypeArr)->get();
		foreach($allRCFolderContents as $folderContent)
		{
			$contentId = $folderContent->appuser_content_id;
			$contentFromTimeStamp = $folderContent->from_timestamp;

			if(isset($contentFromTimeStamp) && $contentFromTimeStamp > 0)
			{
				$contentRemindBeforeMillis = $folderContent->remind_before_millis;

				$baseUser =  new \stdClass();
	    		$baseUser->appuser_id = $folderContent->appuser_id;

	            $depMgmtObj = New ContentDependencyManagementClass;
	            $depMgmtObj->withOrgKey($baseUser, $encOrgId);

	            $reminderTimestamp = $contentFromTimeStamp;
	            if(isset($contentRemindBeforeMillis) && !is_nan($contentRemindBeforeMillis) && $contentRemindBeforeMillis > 0)
	            {
	            	$reminderTimestamp = $reminderTimestamp - $contentRemindBeforeMillis;
	            }

		    	$contentDetails = array();
				$contentDetails['reminder_timestamp'] = $reminderTimestamp;

	            // $depMgmtObj->setPartialContentDetails($contentId, $isFolder, $contentDetails);
			}
		}

		$isFolder = FALSE;
		$allRCGroupContents = GroupContent::filterType($filTypeArr)->get();
		foreach($allRCGroupContents as $groupContent)
		{
			$contentId = $groupContent->group_content_id;
			$contentFromTimeStamp = $groupContent->from_timestamp;

			if(isset($contentFromTimeStamp) && $contentFromTimeStamp > 0)
			{
				$contentRemindBeforeMillis = $groupContent->remind_before_millis;

				$createdByMemberId = $groupContent->created_by_member_id;

       	 		$groupMember = GroupMember::ofMember($createdByMemberId)->first();

       	 		if(isset($groupMember))
       	 		{
       	 			$appUserId = $groupMember->member_appuser_id;

       	 			$baseUser = new \stdClass();
		    		$baseUser->appuser_id = $appUserId;

		            $depMgmtObj = New ContentDependencyManagementClass;
		            $depMgmtObj->withOrgKey($baseUser, $encOrgId);

		            $reminderTimestamp = $contentFromTimeStamp;
		            if(isset($contentRemindBeforeMillis) && !is_nan($contentRemindBeforeMillis) && $contentRemindBeforeMillis > 0)
		            {
		            	$reminderTimestamp = $reminderTimestamp - $contentRemindBeforeMillis;
		            }

			    	$contentDetails = array();
					$contentDetails['reminder_timestamp'] = $reminderTimestamp;

		            // $depMgmtObj->setPartialContentDetails($contentId, $isFolder, $contentDetails);
       	 		}					
			}
		}

		$organizations = Organization::get();
		$totalOrgCount = count($organizations);
		$totalOrgDbChangesMade = 0;
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

	        if($orgDbConName != "")
	        {
	        	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

				if(isset($orgServer))
				{
					$isAppServer = $orgServer->is_app_db_server;
					$orgDbName = $orgServer->dbname;

					$isFolder = TRUE;
					$folderContentModelObj = New OrgEmployeeContent;
					$folderContentModelObj = $folderContentModelObj->setConnection($orgDbConName);

					$allRCFolderContents = $folderContentModelObj->filterType($filTypeArr)->get();
					foreach($allRCFolderContents as $folderContent)
					{
						$contentId = $folderContent->employee_content_id;
						$contentFromTimeStamp = $folderContent->from_timestamp;

						if(isset($contentFromTimeStamp) && $contentFromTimeStamp > 0)
						{
							$contentRemindBeforeMillis = $folderContent->remind_before_millis;

				    		$orgEmpId = $folderContent->employee_id;

				            $depMgmtObj = New ContentDependencyManagementClass;
							$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);

				            $reminderTimestamp = $contentFromTimeStamp;
				            if(isset($contentRemindBeforeMillis) && !is_nan($contentRemindBeforeMillis) && $contentRemindBeforeMillis > 0)
				            {
				            	$reminderTimestamp = $reminderTimestamp - $contentRemindBeforeMillis;
				            }

					    	$contentDetails = array();
							$contentDetails['reminder_timestamp'] = $reminderTimestamp;

				            // $depMgmtObj->setPartialContentDetails($contentId, $isFolder, $contentDetails);
						}
					}

					$isFolder = FALSE;
					$groupContentModelObj = New OrgGroupContent;
					$groupContentModelObj = $groupContentModelObj->setConnection($orgDbConName);

					$allRCGroupContents = $groupContentModelObj->filterType($filTypeArr)->get();
					foreach($allRCGroupContents as $groupContent)
					{
						$contentId = $groupContent->group_content_id;
						$contentFromTimeStamp = $groupContent->from_timestamp;

						if(isset($contentFromTimeStamp) && $contentFromTimeStamp > 0)
						{
							$contentRemindBeforeMillis = $groupContent->remind_before_millis;

							$createdByMemberId = $groupContent->created_by_member_id;

							$groupMemberModelObj = New OrgGroupMember;
							$groupMemberModelObj = $groupMemberModelObj->setConnection($orgDbConName);
			       	 		$groupMember = $groupMemberModelObj->ofMember($createdByMemberId)->first();

			       	 		if(isset($groupMember))
			       	 		{
			       	 			$orgEmpId = $groupMember->employee_id;

					            $depMgmtObj = New ContentDependencyManagementClass;
								$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);

					            $reminderTimestamp = $contentFromTimeStamp;
					            if(isset($contentRemindBeforeMillis) && !is_nan($contentRemindBeforeMillis) && $contentRemindBeforeMillis > 0)
					            {
					            	$reminderTimestamp = $reminderTimestamp - $contentRemindBeforeMillis;
					            }

						    	$contentDetails = array();
								$contentDetails['reminder_timestamp'] = $reminderTimestamp;

					            // $depMgmtObj->setPartialContentDetails($contentId, $isFolder, $contentDetails);
			       	 		}					
						}
					}
				}
			}	

	        $orgSrNo++;		
		}

		print_r("<br/><br/>"."TOTAL ORG : ".$totalOrgCount." : TOTAL ORG CHANGES MADE : ".$totalOrgDbChangesMade."<br/>");
	}


	public function createOrganizationMissingAttachmentUploadFolders_USE_LATER() // _USE_LATER
	{
    	set_time_limit(0);		

		// $tempOrgId = 1;
		// $tempOrg = Organization::where('organization_id', '=', $tempOrgId)->first();
		// $organizations = [ $tempOrg ];
		
		$organizations = Organization::get();

		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
            $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
            
			print_r("<br/><br/>"."For Organization : ".$orgId."<br/>");
			
			if(isset($orgDbConName) && $orgDbConName != "") {
				
				print_r("<br/><br/>"."Create Folders : ".$orgId."<br/>");

				// OrganizationClass::createOrganizationAssetFolders($orgId);
				
			}
		}
	}
}




class McryptCipher
{
    const PBKDF2_HASH_ALGORITHM = 'SHA256';
    const PBKDF2_ITERATIONS = 64000;
    const PBKDF2_SALT_BYTE_SIZE = 32;
    // 32 is the maximum supported key size for the MCRYPT_RIJNDAEL_128
    const PBKDF2_HASH_BYTE_SIZE = 32;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $secureEncryptionKey;

    /**
     * @var string
     */
    private $secureHMACKey;

    /**
     * @var string
     */
    private $pbkdf2Salt;

    public function __construct($password)
    {
        $this->password = $password;
    }

    /**
     * Compares two strings.
     *
     * This method implements a constant-time algorithm to compare strings.
     * Regardless of the used implementation, it will leak length information.
     *
     * @param string $knownHash The string of known length to compare against
     * @param string $userHash   The string that the user can control
     *
     * @return bool true if the two strings are the same, false otherwise
     *
     * @see https://github.com/symfony/security-core/blob/master/Util/StringUtils.php
     */
    private function equalHashes($knownHash, $userHash)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($knownHash, $userHash);
        }

        $knownLen = strlen($knownHash);
        $userLen = strlen($userHash);

        if ($userLen !== $knownLen) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $knownLen; $i++) {
            $result |= (ord($knownHash[$i]) ^ ord($userHash[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return 0 === $result;
    }

    /**
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     *
     * @param string $algorithm The hash algorithm to use. Recommended: SHA256
     * @param string $password The password
     * @param string $salt A salt that is unique to the password
     * @param int $count Iteration count. Higher is better, but slower. Recommended: At least 1000
     * @param int $key_length The length of the derived key in bytes
     * @param bool $raw_output If true, the key is returned in raw binary format. Hex encoded otherwise
     * @return string A $key_length-byte key derived from the password and salt
     *
     * @see https://defuse.ca/php-pbkdf2.htm
     */
    private function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true)) {
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        }
        if ($count <= 0 || $key_length <= 0) {
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        }

        if (function_exists('hash_pbkdf2')) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length *= 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
        }

        $hash_length = strlen(hash($algorithm, '', true));
        $block_count = ceil($key_length / $hash_length);

        $output = '';
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack('N', $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) {
            return substr($output, 0, $key_length);
        } else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }

    /**
     * Creates secure PBKDF2 derivatives out of the password.
     *
     * @param null $pbkdf2Salt
     */
    private function derivateSecureKeys($pbkdf2Salt = null)
    {
        if ($pbkdf2Salt) {
            $this->pbkdf2Salt = $pbkdf2Salt;
        }
        else {
            $this->pbkdf2Salt = mcrypt_create_iv(self::PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM);
        }

        list($this->secureEncryptionKey, $this->secureHMACKey) = str_split(
            $this->pbkdf2(self::PBKDF2_HASH_ALGORITHM, $this->password, $this->pbkdf2Salt, self::PBKDF2_ITERATIONS, self::PBKDF2_HASH_BYTE_SIZE * 2, true),
            self::PBKDF2_HASH_BYTE_SIZE
        );
    }

    /**
     * Calculates HMAC for the message.
     *
     * @param string $message
     * @return string
     */
    private function hmac($message)
    {
        return hash_hmac(self::PBKDF2_HASH_ALGORITHM, $message, $this->secureHMACKey, true);
    }

    /**
     * Encrypts the input text
     *
     * @param string $input
     * @return string Format: hmac:pbkdf2Salt:iv:encryptedText
     */
    public function encrypt($input)
    {
        $this->derivateSecureKeys();

        $mcryptIvSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        // By default mcrypt_create_iv() function uses /dev/random as a source of random values.
        // If server has low entropy this source could be very slow.
        // That is why here /dev/urandom is used.
        $iv = mcrypt_create_iv($mcryptIvSize, MCRYPT_DEV_URANDOM);

        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->secureEncryptionKey, $input, MCRYPT_MODE_CBC, $iv);

        $hmac = $this->hmac($this->pbkdf2Salt . $iv . $encrypted);

        return implode(':', array(
            base64_encode($hmac),
            base64_encode($this->pbkdf2Salt),
            base64_encode($iv),
            base64_encode($encrypted)
        ));
    }

    /**
     * Decrypts the input text.
     *
     * @param string $input Format: hmac:pbkdf2Salt:iv:encryptedText
     * @return string
     */
    public function decrypt($input)
    {
        list($hmac, $pbkdf2Salt, $iv, $encrypted) = explode(':', $input);

        $hmac = base64_decode($hmac);
        $pbkdf2Salt = base64_decode($pbkdf2Salt);
        $iv = base64_decode($iv);
        $encrypted = base64_decode($encrypted);

        $this->derivateSecureKeys($pbkdf2Salt);

        $calculatedHmac = $this->hmac($pbkdf2Salt . $iv . $encrypted);

        if (!$this->equalHashes($calculatedHmac, $hmac)) {
            trigger_error('HMAC ERROR: Invalid HMAC.', E_USER_ERROR);
        }

        // mcrypt_decrypt() pads the *RETURN STRING* with nulls ('\0') to fill out to n * blocksize.
        // rtrim() is used to delete them.
        return rtrim(
            mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->secureEncryptionKey, $encrypted, MCRYPT_MODE_CBC, $iv),
            "\0"
        );
    }
}