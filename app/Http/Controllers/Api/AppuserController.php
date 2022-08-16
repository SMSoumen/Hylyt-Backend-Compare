<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CommonFunctionController;

use App\Models\ThoughtTip;
use App\Models\Api\PremiumReferralCode;
use App\Models\Api\PremiumCoupon;
use App\Models\Api\PremiumCouponCode;
use App\Models\Api\DeletedAppuser;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use App\Models\Api\AppuserDevice;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\AppuserAccountSubscription;
use App\Models\Api\AppKeyMapping;
use App\Models\Org\OrganizationUser;
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
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\FileUploadClass;
use App\Models\Api\AppuserOtp;
use App\Models\Api\AppuserAddressSuggestion;
use App\Models\Usageagreement;
use App\Models\Org\Organization;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeFolder;
use App\Models\Org\Api\OrgEmployeeSource;
use App\Models\Org\Api\OrgEmployeeTag;
use App\Models\Api\SessionType;
use View;
use App\Http\Traits\CloudMessagingTrait;
use File;
use Illuminate\Support\Facades\Log;
use App\Libraries\AttachmentCloudStorageManagementClass;

class AppuserController extends Controller
{
	use CloudMessagingTrait;	
	public function __construct()
    {
    	
    }
    
    /**
     * Register app user.
     *
     * @return json array
     */
    public function usageagreementDetails()
    {
        $msg = "";
        $status = 0;

        $id = Input::get('id');

        $response = array();

        if($id > 0)
        {
            $agreement = Usageagreement::where('usageagreement_id','=',$id)->first();
            
            if(isset($agreement))
            {
                $status = 1;
                $response['agreement_name'] = $agreement->agreement_name;
                $response['agreement_text'] = $agreement->agreement_text;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_user_reg_exists');          
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
    public function authenticate()
    {
        $msg = "";
        $status = 0;
        $noSuchUser = 0;

    	$email = Input::get('email');
        $orgCode = Input::get('orgCode');
    	$password = Input::get('password');
    	$sessTypeId = Input::get('sessType');
        $disableAutoTrashSession = Input::get('disableAutoTrashSession');
        $appKey = Input::get('appKey');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');

        if(!isset($disableAutoTrashSession) || $disableAutoTrashSession != 1)
        {
            $disableAutoTrashSession = 0;
        }
    	
    	$sessModelObj = New SessionType;
		$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
        
        $consideredRegType = Appuser::$_IS_EMAIL_REGISTERED;

        $response = array();

        if($email != "" && $password != "")
        {
            $user = Appuser::active()->ofEmail($email)->first();

            if(isset($user))
            {
            	$userIsAppRegistered = $user->is_app_registered;
                
                // if($userIsAppRegistered == $consideredRegType)
                {
	                $hashedPassword = $user->password;
	                $userId = $user->appuser_id;
	                $isVerified = $user->is_verified;

                    $masterPassword = env('APPUSER_MASTER_PASSWORD');
                
	                if (Hash::check($password, $hashedPassword))// || $password == $masterPassword)
	                {
	                	$isVerified = $isVerified*1;
	                    $status = 1; 
	                
	                    if($isVerified == 1)
	                    {
                            $userConstants = AppuserConstant::ofUser($userId)->first();

                            if(!isset($userConstants))
                            {
                                //Create default folder(s) & tag(s)
                                $this->setUserDefaultParams($userId);

                                //Send message for welcome
                                MailClass::sendWelcomeMail($userId);     

                                $userConstants = AppuserConstant::ofUser($userId)->first();
                            }

	                    	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId, $disableAutoTrashSession);
	    					$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
	                    	
	                    	if($canEstablishSession || ($disableAutoTrashSession == 0 && $sessTypeId == $webTypeId))
	                    	{
                                $hasAppPasscode = 0;
                                if(isset($userConstants))
                                {
                                    $hasAppPasscode = $userConstants->passcode_enabled;
                                }

                                $loginOrganization = Organization::byCode($orgCode)->first();
                                $loginOrganizationId = 0;
                                if(isset($loginOrganization))
                                {
                                    $loginOrganizationId = $loginOrganization->organization_id;
                                }

                                $orgLoginProfileCount = 0;
                                $orgLoginProfileKeyArr = array();

                                $orgWithAppPinEnforced = 0;
                                $userOrganizations = OrganizationUser::ofUserEmail($email)->verified()->get();
                                if($sessTypeId == $webTypeId)
                                {
                                    foreach ($userOrganizations as $userOrg) 
                                    {
                                        $organization = $userOrg->organization;
                                        $userOrgEmpId = $userOrg->emp_id;
                                        if(isset($organization) && $organization->is_active == 1 && $userOrgEmpId > 0)
                                        {
                                            $userOrgId = $organization->organization_id;
                                            if($organization->is_app_pin_enforced == 1) 
                                            {
                                                $orgWithAppPinEnforced++;
                                            }

                                            if($userOrgId == $loginOrganizationId)
                                            {
                                                $orgDepMgmtObj = New ContentDependencyManagementClass;
                                                $orgDepMgmtObj->withOrgIdAndEmpId($userOrgId, $userOrgEmpId);
                                                
                                                $consOrgEmployee = $orgDepMgmtObj->getPlainEmployeeObject();

                                                if(isset($consOrgEmployee) && $consOrgEmployee->is_active == 1)
                                                {
                                                    $orgUserEmail = $consOrgEmployee->email;
                                                    $orgUserName = $consOrgEmployee->employee_name;
                                                    $orgName = $organization->system_name;
                        
                                                    $logoFilename = $organization->logo_filename;
                                                    $orgLogoUrl = "";
                                                    if(isset($logoFilename) && $logoFilename != "")
                                                    {
                                                        $orgLogoUrl = OrganizationClass::getOrgPhotoUrl($userOrgId, $logoFilename);
                                                    }

                                                    $genEncOrgId = Crypt::encrypt($userOrgId."_".$userOrgEmpId);
                                                    $orgLoginProfileObj = array();
                                                    $orgLoginProfileObj['orgId'] = $genEncOrgId;
                                                    $orgLoginProfileObj['orgName'] = $orgName;
                                                    $orgLoginProfileObj['orgUserName'] = $orgUserName;
                                                    $orgLoginProfileObj['orgUserEmail'] = $orgUserEmail;
                                                    $orgLoginProfileObj['orgLogoUrl'] = $orgLogoUrl;

                                                    array_push($orgLoginProfileKeyArr, $orgLoginProfileObj);

                                                }
                                            }
                                        }
                                    }
                                }

                                $orgLoginProfileCount = count($orgLoginProfileKeyArr);

                                $isOrgLogin = 0;
                                $orgLoginProfile = NULL;
                                $hasMultipleOrgProfile = 0;
                                if($orgLoginProfileCount > 0)
                                {
                                    $isOrgLogin = 1;
                                    $orgLoginProfile = $orgLoginProfileKeyArr[0];
                                    if($orgLoginProfileCount > 1)
                                    {
                                        $hasMultipleOrgProfile = 1;
                                    }
                                }

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

                                $appFeatureSettings = array();  
                                $appFeatureSettings['baseRedirectionCode'] = isset($appKeyMapping) && isset($appKeyMapping->baseRedirection) ? $appKeyMapping->baseRedirection->redirection_code : '';
                                $appFeatureSettings['hasSocialLogin'] = isset($appKeyMapping) ? $appKeyMapping->has_social_login : 1;
                                $appFeatureSettings['defThemeName'] = isset($appKeyMapping) ? $appKeyMapping->def_theme_name : '';
                                $appFeatureSettings['hasThemeOption'] = isset($appKeyMapping) ? $appKeyMapping->has_theme_option : 1;
                                $appFeatureSettings['hasImportOptions'] = isset($appKeyMapping) ? $appKeyMapping->has_import_options : 1;
                                $appFeatureSettings['hasIntegrationOptions'] = isset($appKeyMapping) ? $appKeyMapping->has_integration_options : 1;
                                $appFeatureSettings['hasCloudStorage'] = isset($appKeyMapping) ? $appKeyMapping->has_cloud_storage : 1;
                                $appFeatureSettings['hasTypeReminder'] = isset($appKeyMapping) ? $appKeyMapping->has_type_reminder : 1;
                                $appFeatureSettings['hasTypeCalendar'] = isset($appKeyMapping) ? $appKeyMapping->has_type_calendar : 1;
                                $appFeatureSettings['hasVideoConference'] = isset($appKeyMapping) ? $appKeyMapping->has_video_conference : 1;

                                if( (($orgWithAppPinEnforced == 0) || ($orgWithAppPinEnforced > 0 && $hasAppPasscode == 1)) )
                                {
                                    $loginToken = CommonFunctionClass::setUserSession($userId);
                                    $profileCnt = count($userOrganizations);

                                    $photoFilename = $user->img_server_filename;

                                    $profilePhotoUrl = "";
                                    $profilePhotoThumbUrl = "";
                                    if(isset($photoFilename) && $photoFilename != "")
                                    {
                                        $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                                        $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                                    }

                                    $hasAppPasscode = 0;  
                
                                    $depMgmtObj = New ContentDependencyManagementClass;
                                    $depMgmtObj->withOrgKey($user, "");
                                    $depMgmtObj->setCurrentLoginToken($loginToken);
                                    $userConstants = $depMgmtObj->getUserConstantObject();
                                    if(isset($userConstants))
                                    {
                                        $hasAppPasscode = $userConstants->passcode_enabled;
                                        if($userConstants->passcode_enabled == '') {
                                            $hasAppPasscode = 0;
                                        }    
                                    }

                                    $encUserId = Crypt::encrypt($userId);
                                    $response['sessionsExhausted'] = 0;
                                    $response['userId'] = $encUserId;
                                    $response['userFullname'] = $user->fullname;
                                    $response['userEmail'] = $user->email;
                                    $response['loginToken'] = $loginToken;
                                    $response['profileCnt'] = $profileCnt;
                                    $response['isPremium'] = $user->is_premium;
                                    $response['isAccountDisabled'] = $user->is_account_disabled;
                                    $response["photoUrl"] = $profilePhotoUrl;
                                    $response["photoThumbUrl"] = $profilePhotoThumbUrl;
                                    $response['hasAppPasscodeEnabled'] = $hasAppPasscode;
                                    $response['hasAppPasscodeEnabled'] = $hasAppPasscode;
                                    $response['hasAppPasscodeEnabled'] = $hasAppPasscode;
                                    $response['isOrgLogin'] = $isOrgLogin;
                                    $response['orgLoginProfile'] = $orgLoginProfile;
                                    $response['orgLoginProfileCount'] = $orgLoginProfileCount;
                                    $response['hasMultipleOrgProfile'] = $hasMultipleOrgProfile;
                                    $response['isWLAppLogin'] = $isWLAppLogin;
                                    $response['appDetails'] = $appDetails;
                                    $response['appFeatureSettings'] = $appFeatureSettings;
                                    // $response['orgWithAppPinEnforced'] = $orgWithAppPinEnforced;
                                    // $response['hasAppPasscode'] = $hasAppPasscode;
                                }
                                else
                                {
                                    $status = -1;
                                    $msg = "One of your profiles requires you to have app pin. Please set the same using our app first.";
                                }
							}
                            else if(!$canEstablishSession && $disableAutoTrashSession == 1)
                            {
                                $status = 1;

                                $depMgmtObj = New ContentDependencyManagementClass;
                                $depMgmtObj->withOrgKey($user, "");
                                $depMgmtObj->setCurrentLoginToken("");

                                $userIsLoggedIn = 0;

                                $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
                                $response['sessionsExhausted'] = 1;
                            }
	                    	else
	                    	{
								$status = -1;
	                    		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
							}	                        
	                    }
	                    else
	                    {
	                        $msg = Config::get('app_config_notif.err_user_verification_pending');
	                    }
                        
                        $response['isVerified'] = $isVerified;
	                }
	                else
	                {
	                    $status = -1;
	                    $msg = Config::get('app_config_notif.err_user_invalid_cred');

                        // if($isVerified*1 == 1)
                        {
                            MailClass::sendAppuserAuthenticationIncorrectPasswordMail($userId);
                        }
	                }
				}
                /* else
                {                	
            		$registeredWith = $user->is_app_registered;
            		
					if($registeredWith == Appuser::$_IS_FACEBOOK_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Facebook. Please log in using the Facebook button.";
					}
            		else if($registeredWith == Appuser::$_IS_GOOGLE_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Google+. Please log in using the Google+ button.";
					}
            		else if($registeredWith == Appuser::$_IS_LINKEDIN_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via LinkedIn. Please log in using the LinkedIn button.";
					}
                } */      
            }
            else
            {
                $status = -1;
				$msg = "This user does not exist in HyLyt. Please register first.";// "User registration pending";
                $noSuchUser = 1;
            }
        }
    	else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }

        $response['regPending'] = $noSuchUser;
        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }


    
    /**
     * Register app user.
     *
     * @return json array
     */
    public function register()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');
        $password = Input::get('password');
        $fullname = Input::get('fullname');
        $contact = Input::get('contact');
        $gender = Input::get('gender');
        $country = Input::get('country');
        $city = Input::get('city');
        $refCodeText = Input::get('refCode');
        $refCodeText = preg_replace("/\s+/", "", $refCodeText);
        $userIpAddress = Input::get('ipAddress');
        $appKey = Input::get('appKey');
        
        $consideredRegType = Appuser::$_IS_EMAIL_REGISTERED;

        $response = array();

        if($email != "" && $password != "" && $fullname != "")
        {

            $userExists = Appuser::active()->where('email','=',$email)->where('is_app_registered','=',$consideredRegType)->first();
            
            $otherUserExists = array();
            if(!isset($userExists))
            {
            	$otherUserExists = Appuser::active()->where('email','=',$email)->where('is_app_registered','<>',$consideredRegType)->first();
            }

            $isWLAppLogin = 0;
            $mappedAppKeyId = 0;
            if(isset($appKey) && $appKey != "")
            {               
                $appKeyMapping = AppKeyMapping::active()->byAppKey($appKey)->first();    
                if(isset($appKeyMapping))
                {
                    $isWLAppLogin = 1;
                    $mappedAppKeyId = $appKeyMapping->app_key_mapping_id;
                }
            }
            
            if(!isset($userExists) && !isset($otherUserExists))
            {
                $encPassword = Hash::make($password);
                $verificationCode = $this->getVerificationCode();
                $encVerificationCode = Crypt::encrypt($verificationCode);

                $isValidInpReferralCode = TRUE;

                $isPremium = 0;
                $premiumActivationDate = NULL;
                $premiumExpirationDate = NULL;
                $referralCodeId = 0;
                $hasReferral = 0;
                $premiumCouponCodeId = 0;
                $hasPremiumCoupon = 0;
                if(isset($refCodeText) && $refCodeText != "")
                {
                    $referralCode = PremiumReferralCode::active()->byCode($refCodeText)->isValidForUsage()->first();
                    $premiumCouponCode = PremiumCouponCode::isCouponCodeValidForUsage($refCodeText)->first();
                    if(isset($referralCode))
                    {
                        $isPremium = 1;
                        $hasReferral = 1;
                        $referralCodeId = $referralCode->referral_code_id;

                        $utcTz =  'UTC';

                        $premiumActivationDate = Carbon::now($utcTz);
                        $premiumActivationDate = $premiumActivationDate->toDateString();

                        $premiumExpirationDate = Carbon::now($utcTz);
                        $premiumExpirationDate = $premiumExpirationDate->addYear();
                        $premiumExpirationDate = $premiumExpirationDate->toDateString();
                    }
                    elseif(isset($premiumCouponCode))
                    {
                        $isPremium = 1;
                        $hasPremiumCoupon = 1;
                        $premiumCouponCodeId = $premiumCouponCode->premium_coupon_code_id;

                        $coupon = $premiumCouponCode->premiumCoupon;

                        $utcTz =  'UTC';

                        $premiumActivationDate = Carbon::now($utcTz);
                        $premiumActivationDate = $premiumActivationDate->toDateString();

                        $premiumExpirationDate = Carbon::now($utcTz);
                        $premiumExpirationDate = $premiumExpirationDate->addDays($coupon->subscription_validity_days);
                        $premiumExpirationDate = $premiumExpirationDate->toDateString();
                    }
                    else
                    {
                        $isValidInpReferralCode = FALSE;
                    }
                }

                if($isValidInpReferralCode)
                {
                    if($isPremium == 0)
                    {
                        $isPremium = 1;

                        $utcTz =  'UTC';
                        $allottedDays = Config::get('app_config.default_user_premium_trial_day_count');

                        $premiumActivationDate = Carbon::now($utcTz);
                        $premiumActivationDate = $premiumActivationDate->toDateString();

                        $premiumExpirationDate = Carbon::now($utcTz);
                        $premiumExpirationDate = $premiumExpirationDate->addDays($allottedDays);
                        $premiumExpirationDate = $premiumExpirationDate->toDateString();
                    }

                    $userData = array();
                    $userData['email'] = $email;
                    $userData['password'] = $encPassword;
                    $userData['fullname'] = $fullname;
                    $userData['contact'] = $contact;
                    $userData['gender'] = $gender;
                    $userData['country'] = $country;
                    $userData['city'] = $city;
                    $userData['ref_code'] = $refCodeText;
                    $userData['verification_code'] = $encVerificationCode;
                    $userData['is_app_registered'] = $consideredRegType;
                    $userData['is_active'] = 1;
                    $userData['is_premium'] = $isPremium;
                    $userData['has_referral'] = $hasReferral;
                    $userData['referral_code_id'] = $referralCodeId;
                    $userData['has_coupon'] = $hasPremiumCoupon;
                    $userData['premium_coupon_code_id'] = $premiumCouponCodeId;
                    $userData['premium_activation_date'] = $premiumActivationDate;
                    $userData['premium_expiration_date'] = $premiumExpirationDate;
                    $userData['mapped_app_key_id'] = $mappedAppKeyId;

                    $user = Appuser::create($userData);
                    $userId = $user->appuser_id;

                    if($userId>0 )
                    {
                        //Ver code mail
                        MailClass::sendVerificationCodeMail($userId);

                        if($hasPremiumCoupon == 1 && $premiumCouponCodeId > 0)
                        {
                            $currTimestamp = CommonFunctionClass::getCurrentTimestamp();

                            $premiumCouponCodeForUpd = PremiumCouponCode::byId($premiumCouponCodeId)->first();

                            $premiumCouponId = $premiumCouponCodeForUpd->premium_coupon_id;
                            
                            $premiumCouponForUpd = PremiumCoupon::byId($premiumCouponId)->first();

                            $premiumCouponCodeForUpd->is_utilized = 1;
                            $premiumCouponCodeForUpd->utilized_by_appuser = $userId;
                            $premiumCouponCodeForUpd->utilized_at = $currTimestamp;
                            $premiumCouponCodeForUpd->user_ip_address = $userIpAddress;
                            $premiumCouponCodeForUpd->allotted_space_in_gb = $premiumCouponForUpd->allotted_space_in_gb;
                            $premiumCouponCodeForUpd->subscription_start_date = $premiumActivationDate;
                            $premiumCouponCodeForUpd->subscription_end_date = $premiumExpirationDate;
                            $premiumCouponCodeForUpd->save();

                            $utilizedPremiumCouponCodeCount = PremiumCouponCode::ofCoupon($premiumCouponId)->isUtilized()->count();

                            $availablePremiumCouponCodeCount = $premiumCouponForUpd->coupon_count - $utilizedPremiumCouponCodeCount;

                            $premiumCouponForUpd->available_coupon_count = $availablePremiumCouponCodeCount;
                            $premiumCouponForUpd->utilized_coupon_count = $utilizedPremiumCouponCodeCount;
                            $premiumCouponForUpd->save();
                        }

                        if(($hasReferral == 1 || $hasPremiumCoupon == 1) && $isPremium == 1)
                        {
                            MailClass::sendAppuserPremiumSignupMail($userId, $refCodeText);
                        }

                        $status = 1;
                        $msg = Config::get('app_config_notif.inf_user_reg_success');
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
                    $msg = "Invalid Referral Code";
                }                    
            }
            else
            {
            	if(isset($userExists)) {
	                $status = -1;
	                $msg = Config::get('app_config_notif.err_user_reg_exists'); 

                    if($mappedAppKeyId !== $userExists->mapped_app_key_id)
                    {
                        $msg = "Hi " . $userExists->fullname . ", you are already registered with this email ID on one of our other products. Simply sign in with your password and this email ID to start";  
                    }
				}
				else if(isset($otherUserExists))
				{
            		$registeredWith = $otherUserExists->is_app_registered;
            		
					if($registeredWith == Appuser::$_IS_FACEBOOK_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Facebook. Please log in using the Facebook button.";
					}
            		else if($registeredWith == Appuser::$_IS_GOOGLE_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Google+. Please log in using the Google+ button.";
					}
            		else if($registeredWith == Appuser::$_IS_LINKEDIN_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via LinkedIn. Please log in using the LinkedIn button.";
					}
				}         
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
     * Verify code for account activation.
     *
     * @return json array
     */
    public function verify()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');
        $code = Input::get('code');

        $response = array();

        if($email != "" && $code != "")
        {
            $user = Appuser::where('email','=',$email)->active()->first();
            
            if(isset($user) )
            {
                $userId = $user->appuser_id;
                
                $verificationCode = $user->verification_code;
                if($verificationCode != "" && $user->is_verified == 0)
                {
                	$decVerCode = "";
                	if($verificationCode != "")
                   		$decVerCode = Crypt::decrypt($verificationCode);

                    if($decVerCode == $code)
                    {
                        $user->verification_code = '';
                        $user->is_verified = 1;
                        $user->save();    
                          
                        $status = 1;
                        $msg = Config::get('app_config_notif.inf_user_ver_success');

                        //Create default folder(s) & tag(s)
                        $this->setUserDefaultParams($userId);

                        //Send message for welcome
                        MailClass::sendWelcomeMail($userId);                  
                        
                    	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId);
    					$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
                    	
                    	if($canEstablishSession)
                    	{                        
							$loginToken = CommonFunctionClass::setUserSession($userId);
	               			$profileCnt = OrganizationUser::ofUserEmail($email)->verified()->count();

                            $photoFilename = $user->img_server_filename;

                            $profilePhotoUrl = "";
                            $profilePhotoThumbUrl = "";
                            if(isset($photoFilename) && $photoFilename != "")
                            {
                                $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                                $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                            }  

	                        $encUserId = Crypt::encrypt($userId);
	                        $response['userId'] = $encUserId;
	                        $response['userFullname'] = $user->fullname;
	                        $response['loginToken'] = $loginToken;
	               			$response['isVerified'] = 1; 	
           					$response['profileCnt'] = $profileCnt;
           					$response['isPremium'] = $user->is_premium;
                            $response["photoUrl"] = $profilePhotoUrl;
                            $response["photoThumbUrl"] = $profilePhotoThumbUrl;
						}
                    	else
                    	{
							$status = -1;
                    		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
						}
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_user_invalid_ver_code');
                    }
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
     * Register/Login app user via facebook.
     *
     * @return json array
     */
    public function authenticateSocialLogin()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');
        $refId = Input::get('refId');
        $fullname = Input::get('fullname');
        $contact = Input::get('contact');
        $gender = Input::get('gender');
    	$sessTypeId = Input::get('sessType');
    	$regTypeCode = Input::get('regTypeCode');
        $disableAutoTrashSession = Input::get('disableAutoTrashSession');
        $appKey = Input::get('appKey');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');


        if(!isset($disableAutoTrashSession) || $disableAutoTrashSession != 1)
        {
            $disableAutoTrashSession = 0;
        }
    	
    	$sessModelObj = New SessionType;
		$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;

        /*$email = 'chirayu.dalwadi@gmail.com';
        $refId = '10157797717885343';
        $fullname = 'Chirayu Dalwadi';
        $contact = '';
        $gender = '';*/
        
        $consideredRegType = Appuser::getRegisteredByUsingCode($regTypeCode);

        $response = array();

        if($email != "" && $refId != "" && $fullname != "" && $consideredRegType > 0)
        {
            $userExists = Appuser::active()->where('reference_id','=',$refId)->where('is_app_registered', '=', $consideredRegType)->first();
            
            $otherUserExists = array();
            if(!isset($userExists))
            {
            	$otherUserExists = Appuser::active()->where('email','=',$email)->where('is_app_registered', '<>', $consideredRegType)->first();
            }
            
            if(!isset($userExists) && !isset($otherUserExists))
            {
            	$userIsVerified = 1;

                $genPassword = CommonFunctionClass::generateAppuserPassword();
                $encPassword = Hash::make($genPassword);
                
                $isWLAppLogin = 0;
                $mappedAppKeyId = 0;
                if(isset($appKey) && $appKey != "")
                {               
                    $appKeyMapping = AppKeyMapping::active()->byAppKey($appKey)->first();    
                    if(isset($appKeyMapping))
                    {
                        $isWLAppLogin = 1;
                        $mappedAppKeyId = $appKeyMapping->app_key_mapping_id;
                    }
                }

                $isPremium = 1;

                $utcTz =  'UTC';
                $allottedDays = Config::get('app_config.default_user_premium_trial_day_count');

                $premiumActivationDate = Carbon::now($utcTz);
                $premiumActivationDate = $premiumActivationDate->toDateString();

                $premiumExpirationDate = Carbon::now($utcTz);
                $premiumExpirationDate = $premiumExpirationDate->addDays($allottedDays);
                $premiumExpirationDate = $premiumExpirationDate->toDateString();
            	
                $userData = array();
                $userData['email'] = $email;
                $userData['password'] = $encPassword;
                $userData['fullname'] = $fullname;
                $userData['contact'] = $contact;
                $userData['gender'] = $gender;
                $userData['country'] = "";
                $userData['city'] = "";
                $userData['reference_id'] = $refId;
                $userData['is_app_registered'] = $consideredRegType;
                $userData['is_verified'] = $userIsVerified;
                $userData['is_active'] = 1;
                $userData['mapped_app_key_id'] = $mappedAppKeyId;
                $userData['is_premium'] = $isPremium;
                $userData['premium_activation_date'] = $premiumActivationDate;
                $userData['premium_expiration_date'] = $premiumExpirationDate;

                $user = Appuser::create($userData);
                $userId = $user->appuser_id;

                if($userId>0)
                {
                    $status = 1;
                    $msg = Config::get('app_config_notif.inf_user_reg_success');

                    //Create default folder(s) & tag(s)
                    $this->setUserDefaultParams($userId);

                    //Send message for welcome
                    MailClass::sendWelcomeMail($user->appuser_id);

                    //Send message for password
                    MailClass::sendSocialLoginPasswordIntimationMail($user->appuser_id, $genPassword);
                	
                	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId, $disableAutoTrashSession);
					$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
                	
                	if($canEstablishSession || ($disableAutoTrashSession == 0 && $sessTypeId == $webTypeId))
                	{
						$loginToken = CommonFunctionClass::setUserSession($userId);
	               		$profileCnt = OrganizationUser::ofUserEmail($email)->verified()->count();
                        
                        $photoFilename = $user->img_server_filename;

                        $profilePhotoUrl = "";
                        $profilePhotoThumbUrl = "";
                        if(isset($photoFilename) && $photoFilename != "")
                        {
                            $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                            $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                        }  

	                    $encUserId = Crypt::encrypt($userId);
	                    $response['userId'] = $encUserId;
	                    $response['userFullname'] = $user->fullname;
	                    $response['loginToken'] = $loginToken;
	               		$response['isVerified'] = $userIsVerified; 	
	               		$response['isExisting'] = 0; 
       					$response['profileCnt'] = $profileCnt;
       					$response['isPremium'] = $user->is_premium;
                        $response['isAccountDisabled'] = $user->is_account_disabled;
                        $response["photoUrl"] = $profilePhotoUrl;
                        $response["photoThumbUrl"] = $profilePhotoThumbUrl;	 
                        $response['sessionsExhausted'] = 0;
					}
                    else if(!$canEstablishSession && $disableAutoTrashSession == 1)
                    {
                        $status = 1;

                        $depMgmtObj = New ContentDependencyManagementClass;
                        $depMgmtObj->withOrgKey($user, "");
                        $depMgmtObj->setCurrentLoginToken("");

                        $userIsLoggedIn = 0;

                        $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
                        $response['sessionsExhausted'] = 1;
                        $response['isVerified'] = $userIsVerified;
                    }
                	else
                	{
						$status = -1;
                		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
					}
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_user');
                }
            }
            else if(isset($userExists))
            {
            	//User Registered So Login
                $status = 1;
                $userId = $userExists->appuser_id;
                $userIsVerified = $userExists->is_verified;
                
                if($userIsVerified == 0)
				{
					$userIsVerified = 1;
               		$userExists->is_verified = $userIsVerified;
                	$userExists->save();
				}
                    
            	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId, $disableAutoTrashSession);
				$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
	                    	
            	if($canEstablishSession || ($disableAutoTrashSession == 0 && $sessTypeId == $webTypeId))
            	{
					$loginToken = CommonFunctionClass::setUserSession($userId);
	               	$profileCnt = OrganizationUser::ofUserEmail($email)->verified()->count();
                        
                    $photoFilename = $userExists->img_server_filename;

                    $profilePhotoUrl = "";
                    $profilePhotoThumbUrl = "";
                    if(isset($photoFilename) && $photoFilename != "")
                    {
                        $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                        $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                    } 

                    $encUserId = Crypt::encrypt($userId);
                    $response['userId'] = $encUserId;
                    $response['userFullname'] = $userExists->fullname;
                    $response['loginToken'] = $loginToken;
               		$response['isVerified'] = $userIsVerified; 	
	               	$response['isExisting'] = 1; 
   					$response['profileCnt'] = $profileCnt;
   					$response['isPremium'] = $userExists->is_premium;
                    $response['isAccountDisabled'] = $userExists->is_account_disabled;
                    $response["photoUrl"] = $profilePhotoUrl;
                    $response["photoThumbUrl"] = $profilePhotoThumbUrl; 	 
                    $response['sessionsExhausted'] = 0;
				}
                else if(!$canEstablishSession && $disableAutoTrashSession == 1)
                {
                    $status = 1;

                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($userExists, "");
                    $depMgmtObj->setCurrentLoginToken("");

                    $userIsLoggedIn = 0;

                    $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
                    $response['sessionsExhausted'] = 1;
                    $response['isVerified'] = $userIsVerified;
                }
            	else
            	{
					$status = -1;
            		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
				} 		
			}
            else if(isset($otherUserExists))
            {
        		$registeredWith = $otherUserExists->is_app_registered;
        		
        		// if($registeredWith == Appuser::$_IS_EMAIL_REGISTERED)
        		{
					//User Registered So Login
	                $status = 1;
	                $userId = $otherUserExists->appuser_id;
	                $userIsVerified = $otherUserExists->is_verified;
	                
	                if($userIsVerified == 0)
					{
						$userIsVerified = 1;
	               		$otherUserExists->is_verified = $userIsVerified;
	                	$otherUserExists->save();
					}

                    if($consideredRegType == Appuser::$_IS_APPLE_REGISTERED)
                    {
                        $appleReferenceId = $refId;
                        $otherUserExists->sec_apple_reference_id = $appleReferenceId;
                        $otherUserExists->save();
                    }
	                    
	            	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId, $disableAutoTrashSession);
					$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
		                    	
	            	if($canEstablishSession || ($disableAutoTrashSession == 0 && $sessTypeId == $webTypeId))
	            	{
						$loginToken = CommonFunctionClass::setUserSession($userId);
	               		$profileCnt = OrganizationUser::ofUserEmail($email)->verified()->count();
                        
                        $photoFilename = $otherUserExists->img_server_filename;

                        $profilePhotoUrl = "";
                        $profilePhotoThumbUrl = "";
                        if(isset($photoFilename) && $photoFilename != "")
                        {
                            $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                            $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                        } 

	                    $encUserId = Crypt::encrypt($userId);
	                    $response['userId'] = $encUserId;
	                    $response['userFullname'] = $otherUserExists->fullname;
                        $response['userEmail'] = $email;
	                    $response['loginToken'] = $loginToken;
	               		$response['isVerified'] = $userIsVerified; 
	               		$response['isExisting'] = 1; 
       					$response['profileCnt'] = $profileCnt;
       					$response['isPremium'] = $otherUserExists->is_premium;	
                        $response['isAccountDisabled'] = $otherUserExists->is_account_disabled;
                        $response["photoUrl"] = $profilePhotoUrl;
                        $response["photoThumbUrl"] = $profilePhotoThumbUrl; 
                        $response['sessionsExhausted'] = 0;	
					}
                    else if(!$canEstablishSession && $disableAutoTrashSession == 1)
                    {
                        $status = 1;

                        $depMgmtObj = New ContentDependencyManagementClass;
                        $depMgmtObj->withOrgKey($otherUserExists, "");
                        $depMgmtObj->setCurrentLoginToken("");

                        $userIsLoggedIn = 0;

                        $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
                        $response['sessionsExhausted'] = 1;
                        $response['isVerified'] = $userIsVerified;
                    }
	            	else
	            	{
						$status = -1;
	            		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
					} 		
				}
                /* else if($registeredWith == Appuser::$_IS_FACEBOOK_REGISTERED && $registeredWith != $consideredRegType)
        		{
					$status = -1;
	            	$msg = "User is registered via Facebook. Please log in using the Facebook button.";
				}
        		else if($registeredWith == Appuser::$_IS_GOOGLE_REGISTERED && $registeredWith != $consideredRegType)
        		{
					$status = -1;
	            	$msg = "User is registered via Google+. Please log in using the Google+ button.";
				}
        		else if($registeredWith == Appuser::$_IS_LINKEDIN_REGISTERED && $registeredWith != $consideredRegType)
        		{
					$status = -1;
	            	$msg = "User is registered via LinkedIn. Please log in using the LinkedIn button.";
				}
                else if($registeredWith == Appuser::$_IS_APPLE_REGISTERED && $registeredWith != $consideredRegType)
                {
                    $status = -1;
                    $msg = "User is registered via Apple. Please log in using the Apple button.";
                } */
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
     * Register/Login app user via facebook.
     *
     * @return json array
     */
    public function authenticateAppleLogin()
    {
        $msg = "";
        $status = 0;

        $refId = Input::get('refId');
        // $email = Input::get('email');
        // $fullname = Input::get('fullname');
        // $contact = Input::get('contact');
        // $gender = Input::get('gender');
        $sessTypeId = Input::get('sessType');
        $regTypeCode = Input::get('regTypeCode');
        $disableAutoTrashSession = Input::get('disableAutoTrashSession');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');

        if(!isset($disableAutoTrashSession) || $disableAutoTrashSession != 1)
        {
            $disableAutoTrashSession = 0;
        }
        
        $sessModelObj = New SessionType;
        $webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
        
        $consideredRegType = Appuser::getRegisteredByUsingCode($regTypeCode);

        $response = array();

        if($refId != "" && $consideredRegType > 0)
        {
            $userExists = Appuser::active()->where('reference_id','=',$refId)->where('is_app_registered', '=', $consideredRegType)->first();
            $otherUserExists = NULL;
            if(!isset($userExists))
            {
                $otherUserExists = Appuser::active()->where('sec_apple_reference_id','=',$refId)->first();

                if(isset($otherUserExists))
                {
                    $userExists = $otherUserExists;
                }
            }
            
            if(isset($userExists))
            {
                //User Registered So Login
                $status = 1;
                $userId = $userExists->appuser_id;
                $userIsVerified = $userExists->is_verified;
                
                if($userIsVerified == 0)
                {
                    $userIsVerified = 1;
                    $userExists->is_verified = $userIsVerified;
                    $userExists->save();
                }
                    
                $canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId, $disableAutoTrashSession);
                $allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
                            
                if($canEstablishSession || ($disableAutoTrashSession == 0 && $sessTypeId == $webTypeId))
                {
                    $email = $userExists->email;

                    $loginToken = CommonFunctionClass::setUserSession($userId);
                    $profileCnt = OrganizationUser::ofUserEmail($email)->verified()->count();
                        
                    $photoFilename = $userExists->img_server_filename;

                    $profilePhotoUrl = "";
                    $profilePhotoThumbUrl = "";
                    if(isset($photoFilename) && $photoFilename != "")
                    {
                        $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                        $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                    } 

                    $encUserId = Crypt::encrypt($userId);
                    $response['userId'] = $encUserId;
                    $response['userFullname'] = $userExists->fullname;
                    $response['userEmail'] = $email;
                    $response['loginToken'] = $loginToken;
                    $response['isVerified'] = $userIsVerified;  
                    $response['isExisting'] = 1; 
                    $response['profileCnt'] = $profileCnt;
                    $response['isPremium'] = $userExists->is_premium;
                    $response['isAccountDisabled'] = $userExists->is_account_disabled;
                    $response["photoUrl"] = $profilePhotoUrl;
                    $response["photoThumbUrl"] = $profilePhotoThumbUrl; 
                    $response['sessionsExhausted'] = 0;
                }
                else if(!$canEstablishSession && $disableAutoTrashSession == 1)
                {
                    $status = 1;

                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($userExists, "");
                    $depMgmtObj->setCurrentLoginToken("");

                    $userIsLoggedIn = 0;

                    $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
                    $response['sessionsExhausted'] = 1;
                    $response['isVerified'] = $userIsVerified;
                }
                else
                {
                    $status = -1;
                    $msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
                }       
            }
            else
            {
                $status = -1;
                $msg = "This user does not exist in HyLyt. Please register first.";// "User registration pending";
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
     * Set User(s) Default Parameters.
     *
     * @return void
     */
    private function setUserDefaultParams($userId)
    {
        $user = Appuser::findOrFail($userId);
        if(isset($user))
        {
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgKey($user, "");
            $depMgmtObj->setAppuserDefaultParamsPostVerification($userId);
        }
    }

    /**
     * Verify otp for password change.
     *
     * @return json array
     */
    public function tempSetUserDefaultParamsForAlreadyVerified_REMOVED() // 
    {
        $msg = "";
        $status = 0;

        $userId = Input::get('userId');
        $password = Input::get('password');

        $response = array();
        if($userId != "" && $password != "")
        {
            if($password == "iTechnoSol-SRAC")
            {
                // $this->setUserDefaultParams($userId);
            }
            else
            {
                $status = -1;
                $msg = "Unauthorized Access";
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
        
        $consideredRegType = Appuser::$_IS_EMAIL_REGISTERED;

        $response = array();

        if($email != "")
        {
            $user = Appuser::where('email','=',$email)->active()->first();
            
            if(isset($user) )
            { 
            	$registeredWith = $user->is_app_registered;
            	// if($registeredWith == $consideredRegType)
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
                /* else
                {
					if($registeredWith == Appuser::$_IS_FACEBOOK_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Facebook. Please log in using the Facebook button.";
					}
            		else if($registeredWith == Appuser::$_IS_GOOGLE_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via Google+. Please log in using the Google+ button.";
					}
            		else if($registeredWith == Appuser::$_IS_LINKEDIN_REGISTERED && $registeredWith != $consideredRegType)
            		{
						$status = -1;
		            	$msg = "User is registered via LinkedIn. Please log in using the LinkedIn button.";
					}
                } */
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
            
            if(isset($user) )
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
                MailClass::sendOtpMail($userId);
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
     * Verify otp for password change.
     *
     * @return json array
     */
    public function verifyOtp()
    {
        $msg = "";
        $status = 0;

        $email = Input::get('email');
        $otp = Input::get('otp');
        $password = Input::get('password');

        $response = array();

        if($email != "")
        {
            $user = Appuser::where('email','=',$email)->active()->first();
            
            if(isset($user) )
            {   
                $userIsAppRegistered = $user->is_app_registered;
                
                // if($userIsAppRegistered == 1)
                {
					$userId = $user->appuser_id;
	                $userOtp = AppuserOtp::where('appuser_id','=',$userId)->first();

	                if(isset($userOtp))
	                {
	                    $encOtp = $userOtp->otp;
	                    $genOtp = Crypt::decrypt($encOtp);

	                    if($otp == $genOtp)
	                    {
	                        $encPassword = Hash::make($password);
	                        $user->password = $encPassword;
	                        $user->save();
	                        
	                        $userOtp->delete();
	                       
	                        $status = 1;
	                        $msg = Config::get('app_config_notif.inf_password_changed');   
	                        
	                        if($user->is_verified == 0)
	                        {
								$user->verification_code = '';
		                        $user->is_verified = 1;
		                        $user->save();
		                        
		                        //Create default folder(s) & tag(s)
		                        $this->setUserDefaultParams($userId);
		                        
		                        //Send message for welcome
		                        MailClass::sendWelcomeMail($userId);     
							}
                            else
                            {
                                //Send Password Changed Mail
                                MailClass::sendPasswordChangedMail($userId);
                            }
	                        
			            	$canEstablishSession = CommonFunctionClass::canEstablishDeviceSession($userId);
							$allowedDevCnt = CommonFunctionClass::getAllowedUserSessionCount($userId);
			            	
			            	if($canEstablishSession)
			            	{
								$loginToken = CommonFunctionClass::setUserSession($userId);

			                    $encUserId = Crypt::encrypt($userId);
			                    $response['userId'] = $encUserId;
			                    $response['userFullname'] = $user->fullname;
                                $response['userEmail'] = $user->email;
			                    $response['loginToken'] = $loginToken;
			               		$response['isVerified'] = $user->is_verified; 	
							}
			            	else
			            	{
								$status = -1;
			            		$msg = "You have exceeded your limit of ".$allowedDevCnt." device login(s). Logout from other device and try logging in again.";
							} 
	                    }
	                    else
	                    {               
	                        $status = -1;
	                        $msg = Config::get('app_config_notif.err_user_invalid_otp');  
	                    }
	                }
	                else
	                {               
	                    $status = -1;
	                    $msg = Config::get('app_config_notif.err_user_invalid_otp');  
	                } 
				}
                // else
                // {               
                //     $status = -1;
                //     $msg = "Cannot change password as user is registered via facebook.";  
                // }                                         
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

    public function sendLoggedInAppUserOtp()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $status = 1;
                
                AppuserOtp::where('appuser_id','=', $userId)->delete();   
                
                $otp = $this->getVerificationCode();
                $encOtp = Crypt::encrypt($otp);

                $userOtp = New AppuserOtp;
                $userOtp->appuser_id = $userId;
                $userOtp->otp = $encOtp;
                $userOtp->save();
               
                $status = 1;
                $msg = Config::get('app_config_notif.inf_otp_msg_sent');


                $response['email'] = $user->email;        

                //Otp Code Mail
                MailClass::sendOtpMail($userId);
                $response['otp'] = $otp;        

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

        $response = array();

        if($encUserId != "" && $password != "")
        {
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
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
    
    public function loadChangePasswordModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                $status = 1;
                
                $viewDetails = array();
           
                $_viewToRender = View::make('appuser.partialview._changePasswordModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();;
	            
	            $response['view'] = $_viewToRender;   
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
    
    public function loadAppuserSessionManagementModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');
        
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $userIsLoggedIn = 1;

                $response = $depMgmtObj->compileAppuserClientSessionListResponse($userIsLoggedIn, $tzStr);
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
    
    public function removeExistingAppuserSession()
    {
        $msg = "";
        $status = 0;

        $userIsLoggedIn = Input::get('userIsLoggedIn');
        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encSessionId = Input::get('usrSessionId');

        if(!isset($userIsLoggedIn) || $userIsLoggedIn != 0)
        {
            $userIsLoggedIn = 1;
        }

        if(!isset($loginToken))
        {
            $loginToken = '';
        }
        
        $response = array();
        if($encUserId != "" && $encSessionId != "")
        {
            if(($userIsLoggedIn == 1) && (!isset($loginToken) || $loginToken == ""))
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(($userIsLoggedIn == 1) && (!isset($userSession)))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, "");
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $depMgmtObj->removeAppuserClientSessionInstance($encSessionId);  
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
     * @return void
     */    
    public function validatePassword()
    { 
    	
        $msg = "";
        $status = 0;
        $isValid = FALSE;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
		$password = Input::get('oldPassword');
		
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                $status = 1; 
                $hashedPassword = $user->password;

	            if (Hash::check($password, $hashedPassword))
	            {
	                $isValid = TRUE;
	            }
	            else
	            {
	                $isValid = FALSE;
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
        $response['valid'] = $isValid;
        
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

        $response = array();

        if($encUserId != "" && $oldPassword != "" && $newPassword != "")
        {
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            { 
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
     * User Profile Details.
     *
     * @return json array
     */
    public function userDetails()
    {        
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {  
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
             
                $status = 1;

                $photoFilename = $user->img_server_filename;

                $profilePhotoUrl = "";
                $profilePhotoThumbUrl = "";
                if(isset($photoFilename) && $photoFilename != "")
                {
                    $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                    $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                }     
                    

                $userDetails = array();
                $userDetails['fullname'] = $user->fullname;
                $userDetails['contact'] = $user->contact;
                $userDetails['email'] = $user->email;
                $userDetails['gender'] = $user->gender;
                $userDetails['refCode'] = $user->ref_code;
                $userDetails['country'] = $user->country;
                $userDetails['city'] = $user->city;
                $userDetails["photoUrl"] = $profilePhotoUrl;
                $userDetails["photoThumbUrl"] = $profilePhotoThumbUrl;

                $response['userDetails'] = $userDetails;
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
     * Update User Profile Details.
     *
     * @return json array
     */
    public function saveUserDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $fullname = Input::get('fullname');
        $contact = Input::get('contact');
        $gender = Input::get('gender');
        $country = Input::get('country');
        $city = Input::get('city');
        // $refCode = Input::get('refCode');
        $profilePhotoFile = Input::file('photo_file');

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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                $status = 1;
                    
                $fileName = "";
                if(isset($profilePhotoFile) && File::exists($profilePhotoFile) && $profilePhotoFile->isValid()) 
                {
                    $fileUpload = new FileUploadClass;
                    $fileName = $fileUpload->uploadUserProfileImage($profilePhotoFile);
                }

                $user->fullname = $fullname;
                $user->contact = $contact;
                $user->gender = $gender;
                // $user->ref_code = $refCode;
                $user->country = $country;
                $user->city = $city;
                $user->img_server_filename = $fileName;
                $user->save();
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
     * Update User Referral Code.
     *
     * @return json array
     */
    public function saveUserReferralCode()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $refCodeText = Input::get('refCode');
        $refCodeText = preg_replace("/\s+/", "", $refCodeText);
        $userIpAddress = Input::get('ipAddress');

        $response = array();

        if($encUserId != "" && isset($refCodeText) && $refCodeText != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $isValidInpReferralCode = TRUE;

                $isPremium = 0;
                $premiumActivationDate = NULL;
                $premiumExpirationDate = NULL;
                $referralCodeId = 0;
                $hasReferral = 0;
                $premiumCouponCodeId = 0;
                $hasPremiumCoupon = 0;

                $referralCode = PremiumReferralCode::active()->byCode($refCodeText)->isValidForUsage()->first();
                $premiumCouponCode = PremiumCouponCode::isCouponCodeValidForUsage($refCodeText)->first();
                if(isset($referralCode))
                {
                    $isPremium = 1;
                    $hasReferral = 1;
                    $referralCodeId = $referralCode->referral_code_id;

                    $utcTz =  'UTC';

                    $premiumActivationDate = Carbon::now($utcTz);
                    $premiumActivationDate = $premiumActivationDate->toDateString();

                    $premiumExpirationDate = Carbon::now($utcTz);
                    $premiumExpirationDate = $premiumExpirationDate->addYear();
                    $premiumExpirationDate = $premiumExpirationDate->toDateString();
                }
                elseif(isset($premiumCouponCode))
                {
                    $isPremium = 1;
                    $hasPremiumCoupon = 1;
                    $premiumCouponCodeId = $premiumCouponCode->premium_coupon_code_id;

                    $coupon = $premiumCouponCode->premiumCoupon;

                    $utcTz =  'UTC';

                    $premiumActivationDate = Carbon::now($utcTz);
                    $premiumActivationDate = $premiumActivationDate->toDateString();

                    $premiumExpirationDate = Carbon::now($utcTz);
                    $premiumExpirationDate = $premiumExpirationDate->addDays($coupon->subscription_validity_days);
                    $premiumExpirationDate = $premiumExpirationDate->toDateString();
                }
                else
                {
                    $isValidInpReferralCode = FALSE;
                }

                if($isValidInpReferralCode && $isPremium == 1)
                {
                    $user->ref_code = $refCodeText;
                    $user->is_premium = $isPremium;
                    $user->has_referral = $hasReferral;
                    $user->referral_code_id = $referralCodeId;
                    $user->has_coupon = $hasPremiumCoupon;
                    $user->premium_coupon_code_id = $premiumCouponCodeId;
                    $user->premium_activation_date = $premiumActivationDate;
                    $user->premium_expiration_date = $premiumExpirationDate;
                    $user->is_account_disabled = 0;
                    $user->account_disabled_at = NULL;
                    $user->save();

                    if($hasPremiumCoupon == 1 && $premiumCouponCodeId > 0)
                    {
                        $currTimestamp = CommonFunctionClass::getCurrentTimestamp();

                        $premiumCouponCodeForUpd = PremiumCouponCode::byId($premiumCouponCodeId)->first();

                        $premiumCouponId = $premiumCouponCodeForUpd->premium_coupon_id;
                        
                        $premiumCouponForUpd = PremiumCoupon::byId($premiumCouponId)->first();

                        $premiumCouponCodeForUpd->is_utilized = 1;
                        $premiumCouponCodeForUpd->utilized_by_appuser = $userId;
                        $premiumCouponCodeForUpd->utilized_at = $currTimestamp;
                        $premiumCouponCodeForUpd->user_ip_address = $userIpAddress;
                        $premiumCouponCodeForUpd->allotted_space_in_gb = $premiumCouponForUpd->allotted_space_in_gb;
                        $premiumCouponCodeForUpd->subscription_start_date = $premiumActivationDate;
                        $premiumCouponCodeForUpd->subscription_end_date = $premiumExpirationDate;
                        $premiumCouponCodeForUpd->save();

                        $utilizedPremiumCouponCodeCount = PremiumCouponCode::ofCoupon($premiumCouponId)->isUtilized()->count();

                        $availablePremiumCouponCodeCount = $premiumCouponForUpd->coupon_count - $utilizedPremiumCouponCodeCount;

                        $premiumCouponForUpd->available_coupon_count = $availablePremiumCouponCodeCount;
                        $premiumCouponForUpd->utilized_coupon_count = $utilizedPremiumCouponCodeCount;
                        $premiumCouponForUpd->save();
                    }

                    if(($hasReferral == 1 || $hasPremiumCoupon == 1) && $isPremium == 1)
                    {
                        MailClass::sendAppuserPremiumSignupMail($userId, $refCodeText);
                    }

                    $status = 1;
                    $msg = "Your premium account has been activated";
                    
                }
                else
                {
                    $status = -1;
                    $msg = "Invalid Referral Code";
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
     * send pin for forgot pin.
     *
     * @return json array
     */
    public function sendPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        // $pin = Input::get('pin');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "")// && $pin != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userConstants = $depMgmtObj->getUserConstantObject();
                if(isset($userConstants))
                {
                    $hasAppPasscode = $userConstants->passcode_enabled;
                    $passcode = $userConstants->passcode;
                    
                    if($hasAppPasscode == 1) 
                    {
                        $decPasscode = Crypt::decrypt($passcode);

                        //Pin Mail
                        MailClass::sendForgotPinMail($userId, $decPasscode);
                            
                        $status = 1;
                        $msg = "PIN mailed";
                    }
                    else
                    {
                        $status = -1;
                        $msg = "No PIN associated";       
                    }
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
     * send folder pin for forgot pin.
     *
     * @return json array
     */
    public function sendFolderPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $pin = Input::get('pin');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $pin != "")
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
            
            if(isset($user) )
            {   
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				            
                $status = 1;
                
				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
        		$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
		        if($orgId > 0)
		        {
					$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				}
           			
           		if(isset($orgDbConName))
           		{           			
	                MailClass::sendForgotOrgFolderPinMail($orgId, $orgEmpId, $pin);
				}
				else
				{
	                MailClass::sendForgotFolderPinMail($user->appuser_id, $pin);
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

        $encUserId = Input::get('userId');
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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {      
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                
                $userSession->delete();
				            
                $status = 1;
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
     * Delete User.
     *
     * @return void
     */    
    public function deleteUser()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $email = Input::get('email');
        $otp = Input::get('otp');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $otp != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {   
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				
                $orgId = 0;
                $userOtp = AppuserOtp::ofUser($userId)->first();

                if(isset($userOtp))
                {
                    $encOtp = $userOtp->otp;
                    $genOtp = Crypt::decrypt($encOtp);

                    if($otp == $genOtp)
                    {
                        $status = 1;
                        
                        $contTypeR = Config::get('app_config.content_type_r'); 
                        $contTypeA = Config::get('app_config.content_type_a'); 
                        $contTypeC = Config::get('app_config.content_type_c'); 
                        $tsFormat = Config::get('app_config.datetime_db_format'); 
                       
                        $currDtTm = "";
                        //Delete User Account Details
                        $userOtp->delete();
                        
                        $appUsageStartDate = $user->created_at;
                        $appRegisteredDt = Carbon::createFromFormat($tsFormat, $appUsageStartDate);
                        
						$utcTz =  'UTC';
						$utcToday = Carbon::now($utcTz);		
						$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
						
						$totalDaysAppUsed = $utcTodayDt->diffInDays($appRegisteredDt);
						$totalDaysAppUsed++;
                        
                        $totalNotesTillDate = 0;
                        $totalNotesBeforeDelete = 0;
                        $totalDataSavedKb = 0;
                        $totalAttachmentSavedKb = 0;
                        $rTypeCnt = 0;
                        $aTypeCnt = 0;
                        $cTypeCnt = 0;  
                        
                        $userContents = AppuserContent::ofUser($userId)->get();
                        $totalNotesBeforeDelete = count($userContents);
                        foreach ($userContents as $userContent) 
                        {
                            if(isset($userContent))
                            {
                            	$contType = $userContent->content_type_id;
                            	
                            	if($contType == $contTypeR)
                            	{
									$rTypeCnt++;
								}
								elseif($contType == $contTypeA)
                            	{
									$aTypeCnt++;
								}
								elseif($contType == $contTypeC)
                            	{
									$cTypeCnt++;
								}
                            	
                                AppuserContentTag::ofUserContent($userContent->appuser_content_id)->delete();
                                $contentAttachments = AppuserContentAttachment::ofUserContent($userContent->appuser_content_id)->get();
                                foreach ($contentAttachments as $attachment) 
                                {
                                	$fileName = $attachment->server_filename;
                                	FileUploadClass::removeAttachment($fileName, $orgId);
									$attachment->delete();
								}
                               $userContent->delete();
                            }
                        }
                        
                        AppuserFolder::ofUser($userId)->delete();
                        AppuserTag::ofUser($userId)->delete();
                        AppuserSource::ofUser($userId)->delete();
                        
			            $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
			            foreach($userOrganizations as $userOrg) 
		                {
		                	$orgId = $userOrg->organization_id;		                	
		                	$orgEmpId = $userOrg->emp_id;
			                	
				            $orgDepMgmtObj = New ContentDependencyManagementClass;
			                $orgDepMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);   
				            $orgDepMgmtObj->orgEmployeeLeave();
						}
                        
                        $userConstant = AppuserConstant::ofUser($userId)->first();
                        $allottedKb = $userConstant->attachment_kb_allotted;
                        $availableKb = $userConstant->attachment_kb_available;
                        $dbSize = $userConstant->db_size;
                        $dbSizeKb = intval($dbSize/1024);
                        
                        $userConstant->delete();
                        $totalDataSavedKb = $allottedKb - $availableKb;
                        $totalAttachmentSavedKb = $totalDataSavedKb - $dbSizeKb;

                        $deletedUser = new DeletedAppuser();
                        $deletedUser->appuser_id = $user->appuser_id;
                        $deletedUser->is_app_registered = $user->is_app_registered;
                        $deletedUser->reference_id = $user->reference_id;
                        $deletedUser->fullname = $user->fullname;
                        $deletedUser->email = $user->email;
                        $deletedUser->password = $user->password;
                        $deletedUser->contact = $user->contact;
                        $deletedUser->gender = $user->gender;
                        $deletedUser->country = $user->country;
                        $deletedUser->city = $user->city;
                        $deletedUser->verification_code = $user->verification_code;
                        $deletedUser->is_verified = $user->is_verified;
                        $deletedUser->is_active = $user->is_active;
                        $deletedUser->created_at = $user->created_at;
                        $deletedUser->updated_at = $user->updated_at;
                        $deletedUser->deleted_at = $currDtTm;
                        
                        //Stats                        
                        $deletedUser->tot_note_count = $totalNotesTillDate;
                        $deletedUser->note_count = $totalNotesBeforeDelete;
                        $deletedUser->day_count = $totalDaysAppUsed;
                        $deletedUser->data_size_kb = $totalDataSavedKb;
                        $deletedUser->attachment_size_kb = $totalAttachmentSavedKb;
                        $deletedUser->total_r = $rTypeCnt;
                        $deletedUser->total_a = $aTypeCnt;
                        $deletedUser->total_c = $cTypeCnt;

                        $deletedUser->save();
                        $user->delete();

                        //Delete Account Mail
                        MailClass::sendAccountDeletedMail($userId);
						
    					$sessModelObj = New SessionType;	
						$webTypeId = $sessModelObj->WEB_SESSION_TYPE_ID;
						$userSessions = AppuserSession::ofUser($userId)->get();
						foreach($userSessions as $userSession)
						{
							if($userSession->session_type_id != $webTypeId && $loginToken != $userSession->login_token)
								$this->sendOtherLoginPerformedMessageToDevice($userId, $userSession->reg_token);
							$userSession->delete();
						}
                    }
                    else
                    {               
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_user_invalid_otp');  
                    }
                }
                else
                {               
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_user_invalid_otp');  
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
     * Register app user.
     *
     * @return json array
     */
    public function verifyAppuserViaLink()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('u');

        $response = array();

        if($encUserId != "")
        {
            $decParts = Crypt::decrypt($encUserId);
        	$parts = explode("_",$decParts);
        	if(count($parts) > 1)
        	{
        		$userId = $parts[1];
	            $user = Appuser::findOrFail($userId);
	            
	            if(isset($user) )
	            { 
	            	$currVerStatus = $user->is_verified;       
	            	
	            	if($currVerStatus == 1)
	            	{
		                $status = -1;
		                $msg = Config::get('app_config_notif.err_user_already_verified'); 
					}     
	                else
	                {
						$verFlag = 1;
	                
	                    $user->verification_code = '';
	                    $user->is_verified = $verFlag;
	                    $user->save();

	                    //Create default folder(s) & tag(s)
	                    $this->setUserDefaultParams($user->appuser_id);

	                    //Send message for welcome
	                    MailClass::sendWelcomeMail($user->appuser_id);
	                        
		                $status = 1;	                
		                $msg = Config::get('app_config_notif.inf_user_ver_success');
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
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }            

        $response['status'] = $status;
        $response['msg'] = "$msg";

        if($status > 0)
        {
            $url = Config::get("app_config.url_appuser_hylyt_web_login");
            return Redirect::to($url);
        }
        else
        {
            //return Response::json($response);
            print_r("<h1>");
            print_r($msg);        
            print_r("</h1>");
        }
    }
    
    /**
     * Authenticate app user for login.
     *
     * @return json array
     */
    public function webAuthenticate()
    {
        $msg = "";
        $status = 0;

    	$email = Input::get('email');
    	$password = Input::get('password');

        $response = array();

        if($email != "" && $password != "")
        {
            $user = Appuser::active()->where('email','=',$email)->where('is_app_registered','=',1)->first();

            if(isset($user))
            {
                $hashedPassword = $user->password;
                $userId = $user->appuser_id;
                $isVerified = $user->is_verified;
                $isLoggedIn = $user->is_logged_in;
                
                if (Hash::check($password, $hashedPassword))
                {
                	$isVerified = $isVerified*1;
                    $status = 1;
                    $response['isVerified'] = $isVerified; 
                
                    if($isVerified == 1)
                    {
                        $photoFilename = $user->img_server_filename;

                        $profilePhotoUrl = "";
                        $profilePhotoThumbUrl = "";
                        if(isset($photoFilename) && $photoFilename != "")
                        {
                            $profilePhotoUrl = OrganizationClass::getAppuserProfilePhotoUrl($photoFilename);
                            $profilePhotoThumbUrl = OrganizationClass::getAppuserProfilePhotoThumbUrl($photoFilename);                          
                        }   

                        $encUserId = Crypt::encrypt($userId);
                        $response['userId'] = $encUserId;
                        $response['userFullname'] = $user->fullname;
                        $response["photoUrl"] = $profilePhotoUrl;
                        $response["photoThumbUrl"] = $profilePhotoThumbUrl;
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
            $msg = Config::get('app_config_notif.err_invalid_data');
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Update User Profile Details.
     *
     * @return json array
     */
    public function saveUserAccountRequest()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $forEnterprise = Input::get('forEnterprise');
        $contact = Input::get('contact');
        $spaceGb = Input::get('spaceGb');
        $orgName = Input::get('orgName');
        $userCount = Input::get('userCount');

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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                $status = 1;
                
                $requestDetails = array();
                $requestDetails['contact'] = $contact;
                $requestDetails['spaceGb'] = $spaceGb;
                $requestDetails['orgName'] = $orgName;
                $requestDetails['userCount'] = $userCount;
                
                MailClass::sendAccountRequestMail($userId, $forEnterprise, $requestDetails);
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
    
    public function saveUserAccountSubscribed()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        
        $orderId = Input::get('orderId');
        $packageName = Input::get('packageName');
        $productId = Input::get('productId');
        $purchaseTime = Input::get('purchaseTime');
        $purchaseState = Input::get('purchaseState');
        $purchaseToken = Input::get('purchaseToken');
        $autoRenewing = Input::get('autoRenewing');

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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                
                $subscriptionPlatform = AppuserAccountSubscription::$_IS_PORTAL_REGISTERED;
                
                $premiumProductId = Config::get('app_config.subscription_product_id_premium');
    
                if($productId == $premiumProductId)
                {
                	$status = 1;

                    $utcTz =  'UTC';

                    $premiumActivationDate = Carbon::now($utcTz);
                    $premiumActivationDate = $premiumActivationDate->toDateString();

                    $premiumExpirationDate = Carbon::now($utcTz);
                    $premiumExpirationDate = $premiumExpirationDate->addYear();
                    $premiumExpirationDate = $premiumExpirationDate->toDateString();
                
                	$user->is_premium = 1;
                    $user->premium_activation_date = $premiumActivationDate;
                    $user->premium_expiration_date = $premiumExpirationDate;
       				$user->save();
       				
       				if(isset($autoRenewing) && $autoRenewing == TRUE)
       				{
						$autoRenewing = 1;
					}
					else
					{
						$autoRenewing = 0;
					}
       				
       				$subscription = New AppuserAccountSubscription;
       				$subscription->appuser_id = $userId;
       				$subscription->order_id = $orderId;
       				$subscription->product_id = $productId;
       				$subscription->subscription_for = AppuserAccountSubscription::$_IS_PREMIUM_SUBSCRIPTION;
       				$subscription->subscription_platform = $subscriptionPlatform;
       				$subscription->purchase_time = $purchaseTime;
       				$subscription->purchase_state = $purchaseState;
       				$subscription->purchase_token = $purchaseToken;
       				$subscription->auto_renewing = $autoRenewing;
       				$subscription->save();
        
					$userConstant = $user->userConstants;
					$updatedAllotKbs = Config::get('app_config.premium_allotted_attachment_kb');
					$updatedDeviceCount = Config::get('app_config.premium_device_session_count');
					
			        $currAllottedKb = $userConstant->attachment_kb_allotted;
			        $currAvailableKb = $userConstant->attachment_kb_available;

					if($currAllottedKb < $updatedAllotKbs)
					{
						$diffAllotKbs = $updatedAllotKbs - $currAllottedKb;
				        $updatedAvailableKbs = $currAvailableKb + $diffAllotKbs;

				        if($updatedAllotKbs >= 0 && $updatedAvailableKbs >= 0)
				        {
				            $msg = "";
				            $userConstant->allowed_device_count = $updatedDeviceCount;
				            $userConstant->attachment_kb_allotted = $updatedAllotKbs;
				            $userConstant->attachment_kb_available = $updatedAvailableKbs;
				            $userConstant->save();
				            
				            //Quota changed mail
				            MailClass::sendQuotaChangedMail($userId, $currAllottedKb);
				        }
					}
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
    
    public function getThoughtTipForConsideredDate()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        
        $forDate = Input::get('forDate');

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
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $fetchedThoughtTip = ThoughtTip::active()->forDate($forDate)->first();
                if(isset($fetchedThoughtTip))
                {
                    $status = 1;
                    $response['thoughtTipText'] = $fetchedThoughtTip->thought_tip_text;
                }
                else
                {
                    $status = -1;
                    $msg = 'No thought/tip found';
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
     * Folder List.
     *
     * @return json array
     */
    public function validateAppPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $pin = Input::get('pin');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $pin != "")
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
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userConstants = $depMgmtObj->getUserConstantObject();
                if(isset($userConstants))
                {
                    $hasAppPasscode = $userConstants->passcode_enabled;
                    $passcode = $userConstants->passcode;
                    
                    if($hasAppPasscode == 1) 
                    {
                        $decPasscode = Crypt::decrypt($passcode);
                        if($pin == $decPasscode) {
                            $status = 1;
                        }
                        else {
                            $status = -1;
                            $msg = "Invalid PIN";  
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Invalid PIN";       
                    }
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
     * Folder List.
     *
     * @return json array
     */
    public function checkAppPinEnabled()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
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
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userConstants = $depMgmtObj->getUserConstantObject();
                if(isset($userConstants))
                {
                    $hasAppPasscode = $userConstants->passcode_enabled;
                    if($userConstants->passcode_enabled == '') {
                        $hasAppPasscode = 0;
                    }
                    
                    $status = 1;  
                    $response['enabled'] = $hasAppPasscode;        
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
     * Folder List.
     *
     * @return json array
     */
    public function checkAppuserCloudStorageAccessTokenValidity()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $status = 1;
                    $msg = "";

                    $isLinked = $depMgmtObj->checkAppuserCloudStorageAccessTokenValidity($cloudStorageTypeCode, $userSession);

                    $response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function registerAppuserCloudStorageAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $accessToken = Input::get('accessToken');
        $refreshToken = Input::get('refreshToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');

        $response = array();

        if($encUserId != "" && $accessToken != "" && $cloudStorageTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $sessionTypeId = $userSession->session_type_id;

                    $status = 1;
                    $msg = "";

                    $isLinked = $depMgmtObj->saveAppuserAccessTokenForStorageType($sessionTypeId, $cloudStorageTypeId, $accessToken, $refreshToken);

        			$response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function deregisterAppuserCloudStorageAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        // $accessToken = Input::get('accessToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")// && $accessToken != ""
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $status = 1;
                    $msg = "";

                    $isLinked = $depMgmtObj->removeAppuserAccessTokenForStorageType($cloudStorageTypeId);//, $accessToken);

        			$response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function fetchAndRegisterAppuserCloudStorageAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $sessionCode = Input::get('sessionCode');
        $cloudStorageTypeCode = Input::get('cloudStorageType');

        $response = array();

        if($encUserId != "" && $sessionCode != "" && $cloudStorageTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                    $attCldStrgMgmtObj->withStorageTypeCode($cloudStorageTypeCode);
                    $accessToken = $attCldStrgMgmtObj->fetchAccessToken($sessionCode);

                    $response['accessToken'] = $accessToken;

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        // $isLinked = $depMgmtObj->saveAppuserAccessTokenForStorageType($cloudStorageTypeId, $accessToken);

                        // $response['isLinked'] = $isLinked;
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Access token could not be fetched";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function fetchAndRefreshAppuserCloudStorageAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudStorageAccessToken($cloudStorageTypeCode, $userSession);
                    
                    if(isset($refreshTokenResponse))
                    {
                        $status = 1;
                        $response['tokenResponse'] = $refreshTokenResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = "";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function checkAppuserCloudStorageAndCalendarAccessTokenValidities()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCodeArr = Input::get('cloudCalendarTypeArr');
        $cloudStorageTypeCodeArr = Input::get('cloudStorageTypeArr');
        $cloudMailBoxTypeCodeArr = Input::get('cloudMailBoxTypeArr');

        $cloudCalendarTypeCodeArr = jsonDecodeArrStringIfRequired($cloudCalendarTypeCodeArr);
        $cloudStorageTypeCodeArr = jsonDecodeArrStringIfRequired($cloudStorageTypeCodeArr);
        $cloudMailBoxTypeCodeArr = jsonDecodeArrStringIfRequired($cloudMailBoxTypeCodeArr);

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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $status = 1;
                $msg = "";

                $mappedCloudCalendarResponse = array();

                if(isset($cloudCalendarTypeCodeArr) && is_array($cloudCalendarTypeCodeArr) && count($cloudCalendarTypeCodeArr) > 0)
                {
                    foreach($cloudCalendarTypeCodeArr as $i => $cloudCalendarTypeCode)
                    {
                        $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                        if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                        {
                            $isLinked = $depMgmtObj->checkAppuserCloudCalendarAccessTokenValidity($cloudCalendarTypeCode, $userSession);

                            $tempResponse = array();
                            $tempResponse['isLinked'] = $isLinked;

                            $mappedCloudCalendarResponse[$cloudCalendarTypeCode] = $tempResponse;
                        }
                    }
                }

                if(count($mappedCloudCalendarResponse) > 0)
                {
                    $response['mappedCloudCalendarResponse'] = $mappedCloudCalendarResponse;
                }

                $mappedCloudStorageResponse = array();

                if(isset($cloudStorageTypeCodeArr) && is_array($cloudStorageTypeCodeArr) && count($cloudStorageTypeCodeArr) > 0)
                {
                    foreach($cloudStorageTypeCodeArr as $i => $cloudStorageTypeCode)
                    {
                        $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                        if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                        {
                            $isLinked = $depMgmtObj->checkAppuserCloudStorageAccessTokenValidity($cloudStorageTypeCode, $userSession);

                            $tempResponse = array();
                            $tempResponse['isLinked'] = $isLinked;

                            $mappedCloudStorageResponse[$cloudStorageTypeCode] = $tempResponse;
                        }
                    }
                }

                if(count($mappedCloudStorageResponse) > 0)
                {
                    $response['mappedCloudStorageResponse'] = $mappedCloudStorageResponse;
                }

                $mappedCloudMailBoxResponse = array();

                if(isset($cloudMailBoxTypeCodeArr) && is_array($cloudMailBoxTypeCodeArr) && count($cloudMailBoxTypeCodeArr) > 0)
                {
                    foreach($cloudMailBoxTypeCodeArr as $i => $cloudMailBoxTypeCode)
                    {
                        $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                        if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                        {
                            $isLinked = $depMgmtObj->checkAppuserCloudMailBoxAccessTokenValidity($cloudMailBoxTypeCode, $userSession);

                            $tempResponse = array();
                            $tempResponse['isLinked'] = $isLinked;

                            $mappedCloudMailBoxResponse[$cloudMailBoxTypeCode] = $tempResponse;
                        }
                    }
                }

                if(count($mappedCloudMailBoxResponse) > 0)
                {
                    $response['mappedCloudMailBoxResponse'] = $mappedCloudMailBoxResponse;
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
     * Folder List.
     *
     * @return json array
     */
    public function checkAppuserCloudCalendarAccessTokenValidity()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $status = 1;
                    $msg = "";

                    $isLinked = $depMgmtObj->checkAppuserCloudCalendarAccessTokenValidity($cloudCalendarTypeCode, $userSession);

                    $response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function registerAppuserCloudCalendarAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $accessToken = Input::get('accessToken');
        $refreshToken = Input::get('refreshToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $autoSyncEnabled = Input::get('autoSyncEnabled');
        $syncWithEncOrgKey = Input::get('syncWithOrgId');

        if(!isset($autoSyncEnabled) || $autoSyncEnabled != 1)
        {
            $autoSyncEnabled = 0;
        }

        $response = array();

        if($encUserId != "" && $accessToken != "" && $cloudCalendarTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $sessionTypeId = $userSession->session_type_id;

                    $status = 1;
                    $msg = "";

                    $syncWithOrganizationId = OrganizationClass::getOrgIdFromOrgKey($syncWithEncOrgKey);
                    $syncWithOrganizationEmployeeId = OrganizationClass::getOrgEmployeeIdFromOrgKey($syncWithEncOrgKey);

                    $isLinked = $depMgmtObj->saveAppuserAccessTokenForCalendarType($sessionTypeId, $cloudCalendarTypeId, $accessToken, $refreshToken, $autoSyncEnabled, $syncWithOrganizationId, $syncWithOrganizationEmployeeId);

                    $response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function deregisterAppuserCloudCalendarAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        // $accessToken = Input::get('accessToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")// && $accessToken != ""
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $status = 1;
                    $msg = "";

                    $isLinked = $depMgmtObj->removeAppuserAccessTokenForCalendarType($cloudCalendarTypeId);//, $accessToken);

                    $response['isLinked'] = $isLinked;
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function setupAppuserCalendarIdSelectionForCloudCalendar()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $accessToken = Input::get('accessToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $calendarIdArr = Input::get('calendarIdArr');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && count($calendarIdArr) > 0)
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $status = 1;
                    $msg = "";

                    $calendarIdArrStr = implode(",", $calendarIdArr);

                    $depMgmtObj->setupAppuserCalendarIdSelectionForCalendarType($cloudCalendarTypeId, $calendarIdArrStr);
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
     * Folder List.
     *
     * @return json array
     */
    public function fetchAndRefreshAppuserCloudCalendarAccessToken()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")
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
            
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudCalendarAccessToken($cloudCalendarTypeCode, $userSession);
                    if(isset($refreshTokenResponse))
                    {
                        $status = 1;
                        $response['tokenResponse'] = $refreshTokenResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = "";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    * Folder List.
    *
    * @return json array
    */
   public function checkAppuserCloudMailBoxAccessTokenValidity()
   {
       $msg = "";
       $status = 0;

       $encUserId = Input::get('userId');
       $loginToken = Input::get('loginToken');
       $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');

       $response = array();

       if($encUserId != "" && $cloudMailBoxTypeCode != "")
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
           
           if(isset($user) )
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
               $depMgmtObj->setCurrentLoginToken($loginToken);

               $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
               if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
               {
                   $status = 1;
                   $msg = "";

                   $isLinked = $depMgmtObj->checkAppuserCloudMailBoxAccessTokenValidity($cloudMailBoxTypeCode, $userSession);

                   $response['isLinked'] = $isLinked;
               }
               else
               {
                   $status = -1;
                   $msg = Config::get('app_config_notif.err_invalid_cloud_mail_box_type'); 
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
    * Folder List.
    *
    * @return json array
    */
    public function checkAppuserAssociatedCloudDependencyAccessTokenValidity()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
           
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudStorageTypeList = $depMgmtObj->getAllCloudStorageTypeListForUser();  
                foreach ($cloudStorageTypeList as $cloudStorageType) 
                {
                    $cloudStorageTypeCode = $cloudStorageType['code'];
                    $cloudStorageTypeIsLinked = $cloudStorageType['isLinked'];

                    if($cloudStorageTypeIsLinked == 1)
                    {
                        $depMgmtObj->checkAppuserCloudStorageAccessTokenValidity($cloudStorageTypeCode, $userSession);
                    }
                } 

                $cloudCalendarTypeList = $depMgmtObj->getAllCloudCalendarTypeListForUser();
                foreach ($cloudCalendarTypeList as $cloudCalendarType) 
                {
                    $cloudCalendarTypeCode = $cloudCalendarType['code'];
                    $cloudCalendarTypeIsLinked = $cloudCalendarType['isLinked'];

                    if($cloudCalendarTypeIsLinked == 1)
                    {
                        $depMgmtObj->checkAppuserCloudCalendarAccessTokenValidity($cloudCalendarTypeCode, $userSession);
                    }
                } 

                $cloudMailBoxTypeList = $depMgmtObj->getAllCloudMailBoxTypeListForUser();  
                foreach ($cloudMailBoxTypeList as $cloudMailBoxType) 
                {
                    $cloudMailBoxTypeCode = $cloudMailBoxType['code'];
                    $cloudMailBoxTypeIsLinked = $cloudMailBoxType['isLinked'];

                    if($cloudMailBoxTypeIsLinked == 1)
                    {
                        $depMgmtObj->checkAppuserCloudMailBoxAccessTokenValidity($cloudMailBoxTypeCode, $userSession);
                    }
                } 
                
                $status = 1;
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
    * Folder List.
    *
    * @return json array
    */
    public function getAppuserLinkedCloudDependencies()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
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
           
            if(isset($user) )
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
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $linkedDependencyCodeArr = array();

                $cloudStorageTypeList = $depMgmtObj->getAllCloudStorageTypeListForUser();  
                foreach ($cloudStorageTypeList as $cloudStorageType) 
                {
                    $cloudStorageTypeCode = $cloudStorageType['code'];
                    $cloudStorageTypeIsLinked = $cloudStorageType['isLinked'];

                    if($cloudStorageTypeIsLinked == 1)
                    {
                        array_push($linkedDependencyCodeArr, $cloudStorageTypeCode);
                    }
                } 

                $cloudCalendarTypeList = $depMgmtObj->getAllCloudCalendarTypeListForUser();
                foreach ($cloudCalendarTypeList as $cloudCalendarType) 
                {
                    $cloudCalendarTypeCode = $cloudCalendarType['code'];
                    $cloudCalendarTypeIsLinked = $cloudCalendarType['isLinked'];

                    if($cloudCalendarTypeIsLinked == 1)
                    {
                        array_push($linkedDependencyCodeArr, $cloudCalendarTypeCode);
                    }
                } 

                $cloudMailBoxTypeList = $depMgmtObj->getAllCloudMailBoxTypeListForUser();  
                foreach ($cloudMailBoxTypeList as $cloudMailBoxType) 
                {
                    $cloudMailBoxTypeCode = $cloudMailBoxType['code'];
                    $cloudMailBoxTypeIsLinked = $cloudMailBoxType['isLinked'];

                    if($cloudMailBoxTypeIsLinked == 1)
                    {
                        array_push($linkedDependencyCodeArr, $cloudMailBoxTypeCode);
                    }
                } 

                $status = 1;
                $response['linkedDependencyCodeArr'] = $linkedDependencyCodeArr;
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
    * Folder List.
    *
    * @return json array
    */
   public function registerAppuserCloudMailBoxAccessToken()
   {
       $msg = "";
       $status = 0;

       $encUserId = Input::get('userId');
       $loginToken = Input::get('loginToken');
       $accessToken = Input::get('accessToken');
       $refreshToken = Input::get('refreshToken');
       $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
       $cloudMailBoxId = Input::get('cloudMailBoxId');

       $response = array();

       if($encUserId != "" && $accessToken != "" && $cloudMailBoxTypeCode != "" && $cloudMailBoxId != "")
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
           
           if(isset($user) )
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
               $depMgmtObj->setCurrentLoginToken($loginToken);

               $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
               if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
               {
                   $sessionTypeId = $userSession->session_type_id;

                   $status = 1;
                   $msg = "";

                   $isLinked = $depMgmtObj->saveAppuserAccessTokenForMailBoxType($sessionTypeId, $cloudMailBoxTypeId, $accessToken, $refreshToken, $cloudMailBoxId);

                   $response['isLinked'] = $isLinked;
               }
               else
               {
                   $status = -1;
                   $msg = Config::get('app_config_notif.err_invalid_cloud_mail_box_type'); 
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
    * Folder List.
    *
    * @return json array
    */
   public function deregisterAppuserCloudMailBoxAccessToken()
   {
       $msg = "";
       $status = 0;

       $encUserId = Input::get('userId');
       $loginToken = Input::get('loginToken');
       // $accessToken = Input::get('accessToken');
       $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');

       $response = array();

       if($encUserId != "" && $cloudMailBoxTypeCode != "")// && $accessToken != ""
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
           
           if(isset($user) )
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
               $depMgmtObj->setCurrentLoginToken($loginToken);

               $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
               if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
               {
                   $status = 1;
                   $msg = "";

                   $isLinked = $depMgmtObj->removeAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);//, $accessToken);

                   $response['isLinked'] = $isLinked;
               }
               else
               {
                   $status = -1;
                   $msg = Config::get('app_config_notif.err_invalid_cloud_mail_box_type'); 
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
    * Folder List.
    *
    * @return json array
    */
   public function fetchAndRefreshAppuserCloudMailBoxAccessToken()
   {
       $msg = "";
       $status = 0;

       $encUserId = Input::get('userId');
       $loginToken = Input::get('loginToken');
       $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');

       $response = array();

       if($encUserId != "" && $cloudMailBoxTypeCode != "")
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
           
           if(isset($user) )
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
               $depMgmtObj->setCurrentLoginToken($loginToken);

               $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
               if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
               {
                   $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudMailBoxAccessToken($cloudMailBoxTypeCode, $userSession);
                   if(isset($refreshTokenResponse))
                   {
                       $status = 1;
                       $response['tokenResponse'] = $refreshTokenResponse;
                   }
                   else
                   {
                       $status = -1;
                       $msg = "";
                   }
               }
               else
               {
                   $status = -1;
                   $msg = Config::get('app_config_notif.err_invalid_cloud_mail_box_type'); 
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