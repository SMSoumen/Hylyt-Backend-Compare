<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use DB;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Traits\OrgCloudMessagingTrait;

class AppuserConstantController extends Controller
{  
    use OrgCloudMessagingTrait;
      	
	public function __construct()
    {
    	
    }
    
    public function syncDefaultFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $defFolderId = Input::get('defFolderId');
        $folderName = Input::get('folderName');
        $iconCode = Input::get('iconCode');
        $loginToken = Input::get('loginToken');
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user))
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}   

                $defFolderId = sracDecryptNumberData($defFolderId, $userSession);

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $folderObj = $depMgmtObj->getFolderObject($defFolderId);
                if(!isset($folderObj) && $folderName != "")
                {
                	$folderRes = $depMgmtObj->addEditFolder(0, $folderName, $iconCode);
                	$defFolderId = $folderRes['syncId'];
				}
				
				if($defFolderId > 0)
				{
					$userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
	                if(isset($userConstant))
	                {
	                    $status = 1;
	                    
	                    $userConstant->def_folder_id = $defFolderId;
	                    $userConstant->save();
	                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);
	                	
	                	$this->sendDefaultFolderChangedMessageToDevice($userId, $encOrgId, $loginToken);                  
	                }
		            else
		            {
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_invalid_user');       
		            }
				}	                
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
		
	}
    
    public function syncAttachmentRetainDay()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');        
        $attachmentRetainDays = Input::get('attachmentRetainDays');
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user))
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}   

                $encOrgId = "";
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                
                if(isset($userConstant))
                {
                    $status = 1;
                    
                    $userConstant->attachment_retain_days = $attachmentRetainDays;
                    $userConstant->save();
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);
                	             	
                	$this->sendAttachmentRetainDayChangedMessageToDevice($userId, $encOrgId, $loginToken);                
                }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
		
	}
    
    public function syncPrintPreference()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');        
        $printFieldIdArr = Input::get('printFieldIdArr');
        
        $printFieldIdArr = json_decode($printFieldIdArr);
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user))
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                $encOrgId = "";
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {
                    $status = 1;
                    
                    $printFieldIdStr = "";
                    if($printFieldIdArr != null && count($printFieldIdArr) > 0) 
                    {
                    	$passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter'); 
                        $printFieldIdStr = implode($passcodeFolderIdDelimiter, $printFieldIdArr);
                    }  
                    
                    $userConstant->print_fields = $printFieldIdStr;
                    $userConstant->save();
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken);  
                	                 	
                	$this->sendPrintPreferenceChangedMessageToDevice($userId, $encOrgId, $loginToken);                
                }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
		
	}
    
    public function syncAppPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');  
        $hasPasscode = Input::get('hasPasscode');
        $passcode = Input::get('passcode');
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user))
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                $encOrgId = "";
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {
                    $status = 1;
                    
                    if($hasPasscode == 0)
                    	$passcode = "";
                    else 
                    	$passcode = Crypt::encrypt($passcode);
                    
                    $userConstant->passcode_enabled = $hasPasscode;
                    $userConstant->passcode = $passcode;
                    $userConstant->save();
                	CommonFunctionClass::setLastSyncTs($userId, $loginToken); 
                	             	
                	$this->sendApplicationPinChangedMessageToDevice($userId, $encOrgId, $loginToken);                 
                }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
		
	}
    
    public function syncAppFolderPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken'); 
        $hasFolderPasscode = Input::get('hasFolderPasscode');
        $folderPasscode = Input::get('folderPasscode');
        $folderIdArr = Input::get('folderIdArr');       
        
        $folderIdArr = json_decode($folderIdArr);
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user))
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}   
                
                $folderIdArr = sracDecryptNumberArrayData($folderIdArr, $userSession);

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstant))
                {
                    $status = 1;
                    
                    $folderIdStr = "";
                    if($hasFolderPasscode == 0)
                    {
                    	$folderPasscode = "";
                    }
                    elseif($folderIdArr != null && count($folderIdArr) > 0) 
                    {
                    	$passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter'); 
                        $folderIdStr = implode($passcodeFolderIdDelimiter, $folderIdArr);
                    }

                    if($hasFolderPasscode == 0)
                    {        
                        $userGroups = $depMgmtObj->getAllGroupsFoUser();
                        foreach ($userGroups as $group) 
                        {
                            $groupId = $group->group_id;                        
                            $isGrpLocked = $group->is_locked;

                            if(isset($isGrpLocked) && $isGrpLocked == 1)
                            {
                                $depMgmtObj->setGroupLockedStatus($groupId, 0);
                            }
                        }
                    }
                    else if($folderPasscode != "")
                    {
                        $folderPasscode = Crypt::encrypt($folderPasscode);
                    }
                    
                    $userConstant->folder_passcode_enabled = $hasFolderPasscode;
                    $userConstant->folder_passcode = $folderPasscode;
                    $userConstant->folder_id_str = $folderIdStr;
                    $userConstant->save();

                	CommonFunctionClass::setLastSyncTs($userId, $loginToken); 
                	  
                	$this->sendFolderPinChangedMessageToDevice($userId, $encOrgId, $loginToken);
                }
	            else
	            {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_invalid_user');       
	            }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
		
	}
}