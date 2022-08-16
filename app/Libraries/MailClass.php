<?php 
namespace App\Libraries;

use Config;
use Twilio;
use App\Models\Api\SessionType;
use App\Models\Api\AppuserOtp;
use App\Models\Api\DeletedAppuser;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\Maillog;
use App\Models\Api\GroupMember;
use Crypt;
use Guzzle;
use Mail;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationServer;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\EnterpriseCoupon;
use App\Models\Org\EnterpriseCouponCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;

class MailClass 
{
	static $systemName;
	static $systemLogoHtml;
	static $systemMailDisclaimer;
	static $orgLogoHeight;
	
	private static function init()
	{
		MailClass::$systemName = Config::get('app_config.system_name');
		MailClass::$systemLogoHtml = '<br/><br/><img src="'.asset(Config::get('app_config.assetBasePath').Config::get('app_config.company_logo_for_email_footer')).'" alt="'.MailClass::$systemName.' Logo" height="40" />';
		MailClass::$systemMailDisclaimer = '';
		MailClass::$orgLogoHeight = '40';
	}

	private static function compileAppKeyMappedDependencied($appKeyMapping)
	{
		if(isset($appKeyMapping))
		{
			$appName = $appKeyMapping->app_name;
			$appLogoUrl = $appKeyMapping->app_logo_thumb_url;
			$appSmtpKey = $appKeyMapping->smtp_key;
			$appSmtpEmail = $appKeyMapping->smtp_email;

			if(isset($appLogoUrl) && $appLogoUrl != "")
			{
				MailClass::$systemLogoHtml = '<br/><br/><img src="'.asset(Config::get('app_config.assetBasePath').Config::get('app_config.company_flavor_logo_base_path').$appLogoUrl).'" alt="'.$appName.' Logo" height="40" />';
			}

			MailClass::$systemName = $appName;

			if(isset($appSmtpKey) && $appSmtpKey != "" && isset($appSmtpEmail) && $appSmtpEmail != "")
			{
				$decAppSmtpKey = ($appSmtpKey); // Crypt::decrypt($smtpKey);

				$config = array(
                    'driver'     => env('MAIL_DRIVER'),
                    'host'       => env('MAIL_HOST'),
                    'port'       => env('MAIL_PORT'),
                    'from'       => array('address' => $appSmtpEmail, 'name' => $appName),
                    'encryption' => env('MAIL_ENCRYPTION'),
                    'username'   => env('MAIL_USERNAME'),
                    'password'   => $decAppSmtpKey
                );

                Config::set('mail', $config);
			}
		}			
	}

	private static function getOrgLogoHtmlStr($logoUrl, $orgName)
	{
		$orgLogoHtml = '<br/><br/><img src="'.$logoUrl.'" alt="'.$orgName.' Logo" height="40" />';
		return $orgLogoHtml;
	}
	
	private static function setupOrganizationRelevantSmtpDetails($orgId)
	{
		if(isset($orgId) && $orgId > 0)
		{
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
	    	$orgServer = OrganizationServer::ofOrganization($orgId)->first();

			if(isset($organization) && $organization->is_active == 1 && isset($orgServer))
			{
				$orgEmail = $organization->email;
				$orgName = $organization->regd_name;

				$isAppSmtp = $orgServer->is_app_smtp_server;
				$smtpEmail = $orgServer->smtp_email;
				$smtpKey = $orgServer->smtp_key;

				if($isAppSmtp == 0 && isset($smtpKey) && $smtpKey != "")
				{
					$decSmtpKey = Crypt::decrypt($smtpKey);

					$config = array(
	                    'driver'     => env('MAIL_DRIVER'),
	                    'host'       => env('MAIL_HOST'),
	                    'port'       => env('MAIL_PORT'),
	                    'from'       => array('address' => $smtpEmail, 'name' => $orgName),
	                    'encryption' => env('MAIL_ENCRYPTION'),
	                    'username'   => env('MAIL_USERNAME'),
	                    'password'   => $decSmtpKey
	                );
	                Config::set('mail', $config);
				}
			}
		}
	}
	
	private static function resetSetupOrganizationSmtpDetails()
	{
		$config = array(
            'driver'     => env('MAIL_DRIVER'),
            'host'       => env('MAIL_HOST'),
            'port'       => env('MAIL_PORT'),
            'from'       => array('address' => env('MAIL_FROM_ADDRESS'), 'name' => env('MAIL_FROM_NAME')),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD')
        );
        Config::set('mail', $config);
	}
	
	public static function sendVerificationCodeMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Registration Verification";
		$user = Appuser::findOrFail($userId);
        $decVerCode = Crypt::decrypt($user->verification_code);
        $userName = $user->fullname;
        $userEmail = $user->email;
        
        $verUserLink = Config::get('app_config.url_verify_appuser');
        $randomCode = MailClass::getRandomCode();        
       	$encUserId = Crypt::encrypt($randomCode."_".$userId);
		$verifyLink = url($verUserLink.$encUserId);

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['verCode'] = $decVerCode;
		$data['verifyLink'] = $verifyLink;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.registrationVerification', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendWelcomeMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Welcome";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email;

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$showAccountDeactivationStr = FALSE;
		$isPremium = $user->is_premium;

		if($isPremium == 1)
		{
			$premiumActivationDate = $user->premium_activation_date;
			$premiumExpirationDate = $user->premium_expiration_date;
		
            $allottedDays = Config::get('app_config.default_user_premium_trial_day_count');

			$consPremiumActivationDt = Carbon::createFromFormat('Y-m-d', $premiumActivationDate);
			$consPremiumExpirationDt = Carbon::createFromFormat('Y-m-d', $premiumExpirationDate);

			$dayDiff = $consPremiumActivationDt->diffInDays($consPremiumExpirationDt);

			if($dayDiff == $allottedDays)
			{
				$showAccountDeactivationStr = TRUE;
			}
		}

		$data = array();
		$data['name'] = $userName;
		$data['showAccountDeactivationStr'] = $showAccountDeactivationStr;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.welcome', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendSocialLoginPasswordIntimationMail($userId, $genPassword)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Login Credentials";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email;
        // $userEmail = 'amruta@itechnosol.com'; 

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['genPassword'] = $genPassword;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.socialLoginPasswordIntimation', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendOtpMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."OTP Verification";
		$user = Appuser::findOrFail($userId);
        $userOtp = AppuserOtp::where('appuser_id','=',$userId)->first();
        $encOtp = $userOtp->otp;
        $decOtp = Crypt::decrypt($encOtp);
        $userName = $user->fullname;
        $userEmail = $user->email;
        
        $verUserLink = Config::get('app_config.url_verify_appuser');
        $randomCode = MailClass::getRandomCode();        
       	$encUserId = Crypt::encrypt($randomCode."_".$userId);
		$verifyLink = url($verUserLink.$encUserId);

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['otp'] = $decOtp;
		$data['verifyLink'] = $verifyLink;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.otpVerification', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendFeedbackAcknowledgementMail($userId, $feedback)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Feedback Received";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');

		$data = array();
		$data['name'] = $userName;
		$data['feedback'] = $feedback;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.feedbackAcknowledgement', $data, function($message) use ($mailSubject, $systemHelpEmail, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});
		}
		catch(\Exception $e)
		{
			// Error occured
		}

		$data['email'] = $userEmail;
		$data['contact'] = $user->contact;
		$data['city'] = $user->city;
		$data['country'] = $user->country;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.feedbackIntimation', $data, function($message) use ($mailSubject, $systemHelpEmail, $systemHelpEmailName)
			{
			    $message->to($systemHelpEmail, $systemHelpEmailName)->subject($mailSubject);
			});
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendForgotPinMail($userId, $pin)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Forgot PIN Request";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$appKeyMapping = CommonFunctionClass::getUserSessionAppKeyMapping();
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['pin'] = $pin;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.forgotPin', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendForgotFolderPinMail($userId, $pin)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Forgot Folder PIN Request";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$appKeyMapping = CommonFunctionClass::getUserSessionAppKeyMapping();
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['pin'] = $pin;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.forgotFolderPin', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendNotificationMailToUser($userId, $notifText, $notifImageUrl)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." Notification";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$appKeyMapping = CommonFunctionClass::getUserSessionAppKeyMapping();
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['notifText'] = $notifText;
		$data['notifImageUrl'] = $notifImageUrl;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		if($userEmail != "")
		{
			try
			{
				Mail::send('email.systemNotification', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendReminderMail($userId, $orgId, $userOrEmpId, $reminderDate, $reminderTime, $reminderText, $userOrEmpContent, $contentSenderStr)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Reminder for your task";
		$user = Appuser::findOrFail($userId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$orgName = "";
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if($orgId == 0)
		{
	        $userName = $user->fullname;
	        $userEmail = $user->email;			
		}
		else if(isset($organization))
		{			
			$orgName = $organization->regd_name;

			$employee = OrganizationClass::getOrgEmployeeObject($orgId, $userOrEmpId);
	        
	        $userName = $employee->employee_name;
	        $userEmail = $employee->email;

			$logoFilename = $organization->logo_filename;
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
				$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
			}

			$data = array();
			$data['name'] = $userName;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		}

        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        
        $reminderText = CommonFunctionClass::brToNl($reminderText);

        $utcFromDateStr = "";
        $istFromDateStr = "";
        $combinedFromDateStr = "";
        $utcToDateStr = "";
        $istToDateStr = "";
        $combinedToDateStr = "";

        $contentTypeId = $userOrEmpContent->content_type_id;

		$fromTimestamp = $userOrEmpContent->from_timestamp;
		if(isset($fromTimestamp) && is_numeric($fromTimestamp))
		{
			$combinedFromDateStr = formatTimeStampToUTCAndISTDateTimeString($fromTimestamp);
		}

		$toTimestamp = $userOrEmpContent->to_timestamp;
		if($contentTypeId == $typeC && isset($toTimestamp) && is_numeric($toTimestamp))
		{
			$combinedToDateStr = formatTimeStampToUTCAndISTDateTimeString($toTimestamp);
		}

		$data = array();
		$data['name'] = $userName;
		$data['reminderDate'] = $reminderDate;
		$data['reminderTime'] = $reminderTime;
		$data['utcFromDateStr'] = $utcFromDateStr;
		$data['istFromDateStr'] = $istFromDateStr;
		$data['combinedFromDateStr'] = $combinedFromDateStr;
		$data['utcToDateStr'] = $utcToDateStr;
		$data['istToDateStr'] = $istToDateStr;
		$data['combinedToDateStr'] = $combinedToDateStr;
		$data['reminderText'] = $reminderText;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $orgName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.reminder', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendPasswordChangedMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Password Changed";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}


		$data = array();
		$data['name'] = $userName;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.passwordChanged', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendAccountDeletedMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Goodbye";
		$user = DeletedAppuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
        
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        
		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['user'] = $user;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.accountDeleted', $data, function($message) use ($mailSubject, $userEmail, $userName, $systemHelpEmail)
				{
				    $message->to($userEmail, $userName)->bcc($systemHelpEmail)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendQuotaChangedMail($userId, $oldAllottedKb)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."App Quota Changed";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
        
        $userConstant = AppuserConstant::ofUser($userId)->first();
        
		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['oldAllottedKb'] = $oldAllottedKb;
		$data['userConstant'] = $userConstant;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.quotaChanged', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendInactivityMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."We Miss You";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
        
        $bccEmail = "chirayu@itechnosol.com";
        
        $inactMailLink = Config::get('app_config.url_inac_mail_unsub');
        $randomCode = MailClass::getRandomCode();        
       	$encUserId = Crypt::encrypt($randomCode."_".$userId);
		$unsubLink = url($inactMailLink.$encUserId);

		$data = array();
		$data['name'] = $userName;
		$data['unsubLink'] = $unsubLink;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.inactivityReminder', $data, function($message) use ($mailSubject, $userEmail, $userName, $bccEmail)
				{
				    // $message->to($userEmail, $userName)->bcc($bccEmail)->subject($mailSubject);
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}			
	}

	public static function sendInactiveUserlistMail($userList)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."List of Inactive Users";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
        
        $bccEmail = "chirayu@itechnosol.com";

		$data = array();
		$data['userList'] = $userList;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.inactiveUserList', $data, function($message) use ($mailSubject, $systemHelpEmail, $systemHelpEmailName, $bccEmail)
			{
			    $message->to($systemHelpEmail, $systemHelpEmailName)->bcc($bccEmail)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendNotVerifiedReminderMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Verification Pending";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
        
        $bccEmail[0] = $systemHelpEmail;
        // $bccEmail[1] = "chirayu@itechnosol.com";
        
        $randomCode = MailClass::getRandomCode();        
       	$encUserId = Crypt::encrypt($randomCode."_".$userId);
       	
        $verPendMailLink = Config::get('app_config.url_ver_pend_mail_unsub');
		$unsubLink = url($verPendMailLink.$encUserId);
        
        $verUserLink = Config::get('app_config.url_verify_appuser');
		$verifyLink = url($verUserLink.$encUserId);

		$data = array();
		$data['name'] = $userName;
		$data['unsubLink'] = $unsubLink;
		$data['verifyLink'] = $verifyLink;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.verificationPendingReminder', $data, function($message) use ($mailSubject, $userEmail, $userName, $bccEmail)
				{
				    $message->to($userEmail, $userName)->bcc($bccEmail)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}			
	}

	public static function sendContentAddedMail($userId, $contentId, $sharedByUserEmail = "", $grpId = NULL, $forceSendMail = NULL)
	{
		$orgId = 0;
		MailClass::init();
		$user = Appuser::byId($userId)->first();
		$canSendContentAddedMail = OrganizationClass::canSendContentAddedMail($orgId, $user);

		if(!$canSendContentAddedMail)
		{
			if(isset($forceSendMail) && $forceSendMail == 1)
			{
				$canSendContentAddedMail = TRUE;
			}
		}

		if($canSendContentAddedMail)
		{			

            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgKey($user, '');
			
	        $userName = $user->fullname;
	        $userEmail = $user->email;
	        
	        $bccEmail = "chirayu@itechnosol.com";
	        
	        $senderDetailsStr = "";
			
			$contentTypeText = "Content";
            $mailSubject = MailClass::$systemName;
            if($sharedByUserEmail != "")
			{
				$mailSubject .= " - ";
				
				$sharedByUserDetails = Appuser::ofEmail($sharedByUserEmail)->first();
				if(isset($sharedByUserDetails))
				{
					$sharedByUserName = $sharedByUserDetails->fullname;
	            	if($sharedByUserName != "")
						$mailSubject .= " $sharedByUserName";
				}
				if($sharedByUserEmail != "")
					$mailSubject .= " <$sharedByUserEmail>";
			
				$senderDetailsStr = "$sharedByUserName ($sharedByUserEmail)";
			}

			$mailSubject .= " - New $contentTypeText received";

			$contentText = "";
			
			$remContentUrl = NULL;
			if(isset($grpId) && $grpId > 0){
				$group = $depMgmtObj->getGroupObject($grpId);
				if(isset($group))
				{
					$grpName = $group->name;
					$senderDetailsStr .= ", in $grpName";
				}
				$isFolder = FALSE;
			}
			else 
			{
				$remContentUrl = OrganizationClass::getAppuserContentDeleteUrl($userId, 0, 0, $contentId);
				/*$decParts = $userId."|"."0"."|"."0"."|".$contentId;
       			$encContentId = Crypt::encrypt($decParts);
       	
		    	$remContentUrl = $decParts."-".$encContentId;*/
				$isFolder = TRUE;
			}

	        $typeR = Config::get("app_config.content_type_r");
	        $typeC = Config::get("app_config.content_type_c");
	        $istOffsetHours = 5;
	        $istOffsetMinutes = 30;
			
			$contentText = "";
			$contentTypeText = "";
	        $utcFromDateStr = "";
	        $istFromDateStr = "";
	        $utcToDateStr = "";
	        $istToDateStr = "";
	        $combinedFromDateStr = "";
	        $combinedToDateStr = "";

			$contentObj = $depMgmtObj->getContentObject($contentId, $isFolder);
			if(isset($contentObj))
			{
				$contentText = $contentObj->content;
				$contentText = Crypt::decrypt($contentText);

        		$contentTypeId = $contentObj->content_type_id;

                $typeObj = $depMgmtObj->getContentTypeObject($contentTypeId);
                if(isset($typeObj))
                    $contentTypeText = $typeObj->type_name;

				$fromTimestamp = $contentObj->from_timestamp;
				$toTimestamp = $contentObj->to_timestamp;

				if(($contentTypeId == $typeR || $contentTypeId == $typeC) && isset($fromTimestamp) && is_numeric($fromTimestamp))
				{
					$combinedFromDateStr = formatTimeStampToUTCAndISTDateTimeString($fromTimestamp);
				}

				if($contentTypeId == $typeC && isset($toTimestamp) && is_numeric($toTimestamp))
				{
					$combinedToDateStr = formatTimeStampToUTCAndISTDateTimeString($toTimestamp);
				}
			}
			else
			{
			}
			
			// $remContentUrl = NULL;

			$data = array();
			$data['name'] = $userName;
			$data['contentText'] = $contentText;
			$data['contentTypeText'] = $contentTypeText;
			$data['senderDetailsStr'] = $senderDetailsStr;
			$data['utcFromDateStr'] = $utcFromDateStr;
			$data['istFromDateStr'] = $istFromDateStr;
			$data['utcToDateStr'] = $utcToDateStr;
			$data['istToDateStr'] = $istToDateStr;
			$data['combinedFromDateStr'] = $combinedFromDateStr;
			$data['combinedToDateStr'] = $combinedToDateStr;
			$data['remContentUrl'] = $remContentUrl;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			if($userEmail != "")
			{
				try
				{
					Mail::send('email.contentEntryAdded', $data, function($message) use ($mailSubject, $userEmail, $userName, $bccEmail)
					{
					    $message->to($userEmail, $userName)->subject($mailSubject); //->bcc($bccEmail)
					});
				}
				catch(\Exception $e)
				{
					// Error occured
					// Log::info('sendContentAddedMail mail error :');
					// Log::info($e);
				}
			}
		}			
	}

	public static function sendInviteContactMail($userId, $contactEmail)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Invitation";
		$senderUser = Appuser::findOrFail($userId);
		
        $senderName = $senderUser->fullname;
        $senderEmail = $senderUser->email;

        $userName = "";
        $userEmail = $contactEmail;

		$data = array();
		$data['name'] = $userName;
		$data['senderName'] = $senderName;
		$data['senderEmail'] = $senderEmail;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.inviteContact', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendContentDeliveredMail($sharedToUserId, $sharedByEmail)
	{
		$orgId = 0;
		if(OrganizationClass::canSendContentDeliveredMail($orgId))
		{
			MailClass::init();
			$mailSubject = MailClass::$systemName." - "."Content delivered";
			$sharedTo = Appuser::byId($sharedToUserId)->first();
			
	        $sharedToName = $sharedTo->fullname;
	        $sharedToEmail = $sharedTo->email;
	        
	        $bccEmail = "chirayu@itechnosol.com";
			
			$sharedByUserDetails = Appuser::ofEmail($sharedByEmail)->first();
			$sharedByName = "";
			if(isset($sharedByUserDetails))
				$sharedByName = $sharedByUserDetails->fullname;
				
			$data = array();
			$data['name'] = $sharedByName;
			$data['receiverName'] = $sharedToName;
			$data['receiverEmail'] = $sharedToEmail;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			if($sharedByEmail != "")
			{
				try
				{
					Mail::send('email.contentEntryDelivered', $data, function($message) use ($mailSubject, $sharedByEmail, $sharedByName, $bccEmail)
					{
					    $message->to($sharedByEmail, $sharedByName)->subject($mailSubject);//->bcc($bccEmail)
					});			
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}			
	}

	public static function sendUserAddedToGroupMail($userId, $group, $sharedByUser)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Added to group ";
		$user = Appuser::findOrFail($userId);
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}
		
        $userName = $user->fullname;
        $userEmail = $user->email;

        $groupAdminEmailArr = array();
        $isSelfJoined = FALSE;
        if($group->is_open_group == 1 && $userId == $sharedByUser->appuser_id)
        {
        	$isSelfJoined = TRUE;
			$mailSubject = MailClass::$systemName." - "."Joined the group ";

   	 		$adminGroupMembers = GroupMember::isGroupAdmin($group->group_id)->get();
   	 		if(isset($adminGroupMembers) && count($adminGroupMembers) > 0)
   	 		{
   	 			foreach ($adminGroupMembers as $adminGroupMember) {
   	 				$grpAdminAppuser = $adminGroupMember->memberAppuser;

   	 				if(isset($grpAdminAppuser) && $grpAdminAppuser->is_active == 1)
   	 				{
   	 					$grpAdminEmail = $grpAdminAppuser->email;
        				array_push($groupAdminEmailArr, $grpAdminEmail);
   	 				}
   	 			}
   	 		}
        }

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $userEmail;
		$data['isSelfJoined'] = $isSelfJoined;
		$data['addedByName'] = $sharedByUser->fullname;
		$data['addedByEmail'] = $sharedByUser->email;
		$data['groupName'] = $groupName;
		$data['isOpenGroup'] = $group->is_open_group;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{

			if($isSelfJoined)
			{
				try
				{
					Mail::send('email.selfJoinedToGroupAcknowledgement', $data, function($message) use ($mailSubject, $userEmail, $userName)
					{
					    $message->to($userEmail, $userName)->subject($mailSubject);
					});	

					Mail::send('email.selfJoinedToGroupIntimation', $data, function($message) use ($mailSubject, $groupAdminEmailArr)
					{
					    $message->cc($groupAdminEmailArr)->subject($mailSubject);
					});			
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
			else
			{
				try
				{
					Mail::send('email.addedToGroup', $data, function($message) use ($mailSubject, $userEmail, $userName)
					{
					    $message->to($userEmail, $userName)->subject($mailSubject);
					});			
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}				
		}
	}

	public static function sendUserRemovedFromGroupMail($userId, $group, $sharedByUser)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Removed from group";
		$user = Appuser::findOrFail($userId);
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['addedByName'] = $sharedByUser->fullname;
		$data['addedByEmail'] = $sharedByUser->email;
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.removedFromGroup', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendUserHasExitGroupMail($userId, $group, $leftUser)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Left the group";
		$user = Appuser::findOrFail($userId);
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['leftUserName'] = $leftUser->fullname;
		$data['leftUserEmail'] = $leftUser->email;
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.userLeftGroup', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendGroupDeletedMail($userId, $group, $deletedByUser)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Group Deleted";
		$user = Appuser::findOrFail($userId);
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['deletedByUserName'] = $deletedByUser->fullname;
		$data['deletedByUserEmail'] = $deletedByUser->email;
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.groupDeleted', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendLoginPerformedMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."New Sign-In";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				Mail::send('email.loginPerformed', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});			
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}
	
	public static function sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verCode, $orgId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Organization Verification";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$orgCode = $organization->org_code;

		$appKeyMapping = $organization->appKeyMapping;
        
        $verUserLink = Config::get('app_config.url_verify_orgemployee');
        $randomCode = MailClass::getRandomCode();        
       	$encUserId = Crypt::encrypt($randomCode."_".$empEmail."_".$orgCode."_".$verCode);
		$verifyLink = url($verUserLink.$encUserId);

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['orgCode'] = $orgCode;
		$data['empEmail'] = $empEmail;
		$data['verCode'] = $verCode;
		$data['enterpAccountAutoSetupLink'] = $verifyLink;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.organizationSubscriptionVerification', $data, function($message) use ($mailSubject, $empEmail, $empName)
			{
			    $message->to($empEmail, $empName)->subject($mailSubject);
			});
			MailClass::resetSetupOrganizationSmtpDetails();
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}
	
	public static function sendOrgSubscribedMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Organization Joined";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);		
		$employee = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
        
        $empName = $employee->employee_name;
        $empEmail = $employee->email;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.organizationSubscribed', $data, function($message) use ($mailSubject, $empEmail, $empName)
			{
			    $message->to($empEmail, $empName)->subject($mailSubject);
			});
			MailClass::resetSetupOrganizationSmtpDetails();
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}
	
	public static function sendOrgAdminCredentailMail($orgAdmin)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Organization Admin Credentials";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgAdmin->organization_id);
		
		$email = $orgAdmin->admin_email;
		$name = $orgAdmin->fullname;
		$encPassword = $orgAdmin->password;
		$password = Crypt::decrypt($encPassword);
		$mgmtSysLink = Config::get('app_config.mgmt_sys_link');

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgAdmin->organization_id, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$appKeyMapping = $organization->appKeyMapping;
		if(isset($appKeyMapping))
		{
			$mgmtSysLink .= "app/".$appKeyMapping->app_code;

			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $name;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['orgCode'] = $organization->org_code;
		$data['email'] = $email;
		$data['password'] = $password;
		$data['mgmtSysLink'] = $mgmtSysLink;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.organizationAdminCredentials', $data, function($message) use ($mailSubject, $email, $name)
			{
			    $message->to($email, $name)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendOrgContentAddedMail($orgId, $orgEmpId, $contentId, $sharedByUserEmail = "", $grpId = NULL, $forceSendMail = NULL)
	{
		$canSendContentAddedMail = OrganizationClass::canSendContentAddedMail($orgId);

		if(!$canSendContentAddedMail)
		{
			if(isset($forceSendMail) && $forceSendMail == 1)
			{
				$canSendContentAddedMail = TRUE;
			}
		}

		//if()
		{
			MailClass::init();
			//$mailSubject = MailClass::$systemName." - "."New content received";
			$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
                    
            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
            $orgCode = $depMgmtObj->getOrganizationCodeForFcm();

            $typeR = Config::get("app_config.content_type_r");
            $typeA = Config::get("app_config.content_type_a");
            $typeC = Config::get("app_config.content_type_c");

            $isFolder = TRUE;
            if(isset($grpId) && $grpId > 0)
            {
            	$isFolder = FALSE;
            }

			$contentObj = $depMgmtObj->getContentObject($contentId, $isFolder);
			$contentIsROrC = FALSE;
			if(isset($contentObj) && ($contentObj->content_type_id == $typeR || $contentObj->content_type_id == $typeC) )
			{
				$contentIsROrC = TRUE;
			}

			if(isset($contentObj) && ($canSendContentAddedMail || $contentIsROrC))
			{
                $organization = OrganizationClass::getOrganizationFromOrgId($orgId);
                
                $logoFilename = $organization->logo_filename;
                $orgLogoThumbUrl = "";
                $orgLogoHtml = "";
                if(isset($logoFilename) && $logoFilename != "")
                {
                $orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                $orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
                }
                
                $userName = $user->employee_name;
                $userEmail = $user->email;
                
                $bccEmail = "chirayu@itechnosol.com";
                
                $sharedByUserName = "";
                $contentTypeText = "Content";
                $mailSubject = MailClass::$systemName." - ".$orgCode;
                if($sharedByUserEmail != "")
                {
                    $sharedByUserDetails = OrganizationClass::getOrgEmployeeObjectByEmail($orgId, $sharedByUserEmail);
                    if(isset($sharedByUserDetails))
                    {
                        $sharedByUserName = $sharedByUserDetails->employee_name;
                        if($sharedByUserName != "")
                            $mailSubject .= " $sharedByUserName";
                    }
                    if($sharedByUserEmail != "")
                        $mailSubject .= " <$sharedByUserEmail>";
                    
                    $mailSubject .= " -";
                }
                
                $mailSubject .= " New $contentTypeText received";		
                
                $remContentUrl = NULL;
                $senderDetailsStr = "";
                if($sharedByUserName != "")
                {
                    $senderDetailsStr = $sharedByUserName;
                }
                if($sharedByUserEmail != "")
                {
                    $senderDetailsStr .= " ($sharedByUserEmail)";	
                }

                if(isset($grpId) && $grpId > 0)
                {
                    $group = $depMgmtObj->getGroupObject($grpId);
                    if(isset($group))
                    {
                        $grpName = $group->name;
                        $senderDetailsStr .= ", in $grpName";
                    }
                    $isFolder = FALSE;
                }
                else 
                {
                    $userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $orgEmpId);
                    $remContentUrl = OrganizationClass::getAppuserContentDeleteUrl($userId, $orgId, $orgEmpId, $contentId);
                    $isFolder = TRUE;
                }
                
                if($senderDetailsStr != "")
                {
                $senderDetailsStr .= ", ";
                }	
                $senderDetailsStr .= $organization->regd_name;

		        $istOffsetHours = 5;
		        $istOffsetMinutes = 30;
                
                $contentText = "";
				$contentTypeText = "";
		        $utcFromDateStr = "";
		        $istFromDateStr = "";
		        $utcToDateStr = "";
		        $istToDateStr = "";
		        $combinedFromDateStr = "";
		        $combinedToDateStr = "";

                if(isset($contentObj))
                {
	                $contentText = $contentObj->content;
	                $contentText = Crypt::decrypt($contentText);

	        		$contentTypeId = $contentObj->content_type_id;

	                $typeObj = $depMgmtObj->getContentTypeObject($contentTypeId);
	                if(isset($typeObj))
	                    $contentTypeText = $typeObj->type_name;

					$fromTimestamp = $contentObj->from_timestamp;
					$toTimestamp = $contentObj->to_timestamp;

					if(($contentTypeId == $typeR || $contentTypeId == $typeC) && isset($fromTimestamp) && is_numeric($fromTimestamp))
					{
						$combinedFromDateStr = formatTimeStampToUTCAndISTDateTimeString($fromTimestamp);
					}

					if($contentTypeId == $typeC && isset($toTimestamp) && is_numeric($toTimestamp))
					{
						$combinedToDateStr = formatTimeStampToUTCAndISTDateTimeString($toTimestamp);
					}
                }

                $data = array();
                $data['name'] = $userName;
                $data['contentText'] = $contentText;
				$data['contentTypeText'] = $contentTypeText;
                $data['senderDetailsStr'] = $senderDetailsStr;
				$data['utcFromDateStr'] = $utcFromDateStr;
				$data['istFromDateStr'] = $istFromDateStr;
				$data['utcToDateStr'] = $utcToDateStr;
				$data['istToDateStr'] = $istToDateStr;
				$data['combinedFromDateStr'] = $combinedFromDateStr;
				$data['combinedToDateStr'] = $combinedToDateStr;
                $data['orgLogoUrl'] = $orgLogoThumbUrl;
                $data['orgLogoHtml'] = $orgLogoHtml;
                $data['orgName'] = $organization->regd_name;
				$data['remContentUrl'] = $remContentUrl;
                $data['systemLogoHtml'] = MailClass::$systemLogoHtml;
                $data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
                
                if($userEmail != "")
                {
                    try
                    {
                    	MailClass::setupOrganizationRelevantSmtpDetails($orgId);
                        Mail::send('email.contentEntryAdded', $data, function($message) use ($mailSubject, $userEmail, $userName, $bccEmail)
                        {
                            $message->to($userEmail, $userName)->subject($mailSubject);//->bcc($bccEmail)
                        });		
                        MailClass::resetSetupOrganizationSmtpDetails();	
                    }
                    catch(\Exception $e)
                    {
                        // Error occured
                    }
                }
			}
        }			
	}

	public static function sendOrgContentDeliveredMail($orgId, $sharedToOrgEmpId, $sharedByEmail)
	{
		if(OrganizationClass::canSendContentDeliveredMail($orgId))
		{
			MailClass::init();
			$mailSubject = MailClass::$systemName." - "."Content delivered";
			
			$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
			$sharedTo = OrganizationClass::getOrgEmployeeObject($orgId, $sharedToOrgEmpId);

			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			$orgLogoHtml = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
				$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
			}
			
	        $sharedToName = $sharedTo->employee_name;
	        $sharedToEmail = $sharedTo->email;
	        
	        $bccEmail = "chirayu@itechnosol.com";
			
			$sharedByUserDetails = OrganizationClass::getOrgEmployeeObjectByEmail($orgId, $sharedByEmail);
			$sharedByName = "";
			if(isset($sharedByUserDetails))
				$sharedByName = $sharedByUserDetails->employee_name;
				
			$data = array();
			$data['name'] = $sharedByName;
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgLogoHtml'] = $orgLogoHtml;
			$data['orgName'] = $organization->regd_name;
			$data['receiverName'] = $sharedToName;
			$data['receiverEmail'] = $sharedToEmail;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			if($sharedByEmail != "")
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.contentEntryDelivered', $data, function($message) use ($mailSubject, $sharedByEmail, $sharedByName, $bccEmail)
					{
					    $message->to($sharedByEmail, $sharedByName)->subject($mailSubject);//->bcc($bccEmail)
					});
					MailClass::resetSetupOrganizationSmtpDetails();
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}			
	}

	public static function sendOrgQuotaChangedMail($orgId, $orgEmpId, $oldAllottedKb)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Enterprise Quota Changed";
		$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$appKeyMapping = $organization->appKeyMapping;
		
        $userName = $user->employee_name;
        $userEmail = $user->email;

     	$depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
		$userConstant = $depMgmtObj->getEmployeeOrUserConstantObject();

		$data = array();
		$data['name'] = $userName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['oldAllottedKb'] = $oldAllottedKb;
		$data['userConstant'] = $userConstant;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}

		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.quotaChanged', $data, function($message) use ($mailSubject, $userEmail, $userName, $orgAdminEmailArr)
				{
				    $message->to($userEmail, $userName)->bcc($orgAdminEmailArr)->subject($mailSubject);
				});	
				MailClass::resetSetupOrganizationSmtpDetails();		
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendForgotOrgFolderPinMail($orgId, $orgEmpId, $pin)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Forgot Folder PIN Request";
		$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		
        $userName = $user->employee_name;
        $userEmail = $user->email;

		$appKeyMapping = CommonFunctionClass::getUserSessionAppKeyMapping();
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['pin'] = $pin;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.forgotFolderPin', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}
	
	public static function sendOrgGroupDeletedMail($orgId, $orgEmpId, $group)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Group Deleted";
		$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		
        $userName = $user->employee_name;
        $userEmail = $user->email;
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}

		$data = array();
		$data['name'] = $userName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['deletedByUserName'] = "";
		$data['deletedByUserEmail'] = "";
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.groupDeleted', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgEmpAddedToGroupMail($orgId, $orgEmpId, $group)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Added to group ";
		$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		
        $userName = $user->employee_name;
        $userEmail = $user->email;
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}

		$data = array();
		$data['name'] = $userName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['addedByName'] = "";
		$data['addedByEmail'] = "";
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.addedToGroup', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgEmpRemovedFromGroupMail($orgId, $orgEmpId, $group)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Removed from group";
		$user = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		
        $userName = $user->employee_name;
        $userEmail = $user->email;
		
		$groupName = "";
		if(isset($group))
		{
			$groupName = $group->name;
		}

		$data = array();
		$data['name'] = $userName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['addedByName'] = "";
		$data['addedByEmail'] = "";
		$data['groupName'] = $groupName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		if($userEmail != "")
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.removedFromGroup', $data, function($message) use ($mailSubject, $userEmail, $userName)
				{
				    $message->to($userEmail, $userName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}
	
	public static function sendOrgEmployeeDeactivatedMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Employee Deactivated";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);		
		$employee = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
        
        $empName = $employee->employee_name;
        $empEmail = $employee->email;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$appKeyMapping = $organization->appKeyMapping;

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if($empEmail != "" && (isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0)) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.employeeAccountDeactivated', $data, function($message) use ($mailSubject, $empEmail, $empName, $orgAdminEmailArr)
				{
				    $message->to($empEmail, $empName)->bcc($orgAdminEmailArr)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}
	
	public static function sendOrgEmployeeReactivatedMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Employee Account Reactivated";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);		
		$employee = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
        
        $empName = $employee->employee_name;
        $empEmail = $employee->email;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$appKeyMapping = $organization->appKeyMapping;

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if($empEmail != "" && (isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0)) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.employeeAccountReactivated', $data, function($message) use ($mailSubject, $empEmail, $empName, $orgAdminEmailArr, $employee)
				{
					if($employee->is_verified == 0)
					{
				   		$message->cc($orgAdminEmailArr)->subject($mailSubject);
					}
					else
					{
				    	$message->to($empEmail, $empName)->bcc($orgAdminEmailArr)->subject($mailSubject);
					}
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
				// Log::info('sendOrgEmployeeReactivatedMail : Exception : '.$e);
			}
		}
	}

	public static function sendBirthdayUserlistMail($orgId, $userList)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Birthday User List";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['userList'] = $userList;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.birthdayUserList', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgAbuseReportAcknowledgementMail($orgId, $orgEmpId, $abuseReport)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Abuse Report Received";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
        
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        
        $bccEmail = $systemHelpEmail;
        
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        $empModelObj = New OrgEmployee;
        $empModelObj->setConnection($orgDbConName);
        $employee = $empModelObj->select(["*"])
                ->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
			
        $empNo = $employee->employee_no;
        $empName = $employee->employee_name;
        $empEmail = $employee->email;
        $empContact = $employee->contact;
        $deptName = $employee->department_name;
        $desigName = $employee->designation_name;

		$data = array();
		$data['name'] = $empName;
		$data['abuseReport'] = $abuseReport;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.abuseReportAcknowledgement', $data, function($message) use ($mailSubject, $empEmail, $empName)
			{
			    $message->to($empEmail, $empName)->subject($mailSubject);
			});
			MailClass::resetSetupOrganizationSmtpDetails();	
		}
		catch(\Exception $e)
		{
			// Error occured
		}

		$data['empNo'] = $empNo;
		$data['email'] = $empEmail;
		$data['contact'] = $empContact;
		$data['department'] = $deptName;
		$data['designation'] = $desigName;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.abuseReportIntimation', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgContactReportAcknowledgementMail($orgId, $orgEmpId, $contactReport)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Contact Report Received";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
        
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        $empModelObj = New OrgEmployee;
        $empModelObj->setConnection($orgDbConName);
        $employee = $empModelObj->select(["*"])
                ->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
			
        $empNo = $employee->employee_no;
        $empName = $employee->employee_name;
        $empEmail = $employee->email;
        $empContact = $employee->contact;
        $deptName = $employee->department_name;
        $desigName = $employee->designation_name;

		$data = array();
		$data['name'] = $empName;
		$data['contactReport'] = $contactReport;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.contactReportAcknowledgement', $data, function($message) use ($mailSubject, $empEmail, $empName)
			{
			    $message->to($empEmail, $empName)->subject($mailSubject);
			});
			MailClass::resetSetupOrganizationSmtpDetails();
		}
		catch(\Exception $e)
		{
			// Error occured
		}

		$data['empNo'] = $empNo;
		$data['email'] = $empEmail;
		$data['contact'] = $empContact;
		$data['department'] = $deptName;
		$data['designation'] = $desigName;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.contactReportIntimation', $data, function($message) use ($mailSubject, $orgAdminEmailArr)
				{
				    $message->cc($orgAdminEmailArr)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgEmployeeLeftMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Employee Left Organization";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		$appKeyMapping = $organization->appKeyMapping;
        
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        $empModelObj = New OrgEmployee;
        $empModelObj->setConnection($orgDbConName);
        $employee = $empModelObj->select(["*"])
                ->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
			
        $empNo = $employee->employee_no;
        $empName = $employee->employee_name;
        $empEmail = $employee->email;
        $empContact = $employee->contact;
        $deptName = $employee->department_name;
        $desigName = $employee->designation_name;

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.employeeLeftOrganizationAcknowledgement', $data, function($message) use ($mailSubject, $empEmail, $empName)
			{
			    $message->to($empEmail, $empName)->subject($mailSubject);
			});
			MailClass::resetSetupOrganizationSmtpDetails();		
		}
		catch(\Exception $e)
		{
			// Error occured
		}
		
		$data['empNo'] = $empNo;
		$data['email'] = $empEmail;
		$data['contact'] = $empContact;
		$data['department'] = $deptName;
		$data['designation'] = $desigName;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.employeeLeftOrganizationIntimation', $data, function($message) use ($mailSubject, $orgAdminEmailArr)
				{
				    $message->cc($orgAdminEmailArr)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();	
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendAccountRequestMail($userId, $forEnterprise, $requestDetails)
	{
		MailClass::init();
		
		if($forEnterprise == 1)
			$accType = "Enterprise";
		else
			$accType = "Premium";
			
		$mailSubject = MailClass::$systemName." - "." $accType Account Request Received";
		
		$user = Appuser::byId($userId)->first();		
        $userName = $user->fullname;
        $userEmail = $user->email;
			
        $adminEmail = Config::get('app_config_mail.system_account_request_email');
        $adminName = Config::get('app_config_mail.system_account_request_email_name');

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $userEmail;
		$data['accType'] = $accType;
		$data['forEnterprise'] = $forEnterprise;
		$data['contact'] = $requestDetails['contact'];
		$data['orgName'] = $requestDetails['orgName'];
		$data['userCount'] = $requestDetails['userCount'];
		$data['spaceGb'] = $requestDetails['spaceGb'];
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.accountRequestAcknowledgement', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});
			
			Mail::send('email.accountRequestIntimation', $data, function($message) use ($mailSubject, $adminEmail, $adminName)
			{
			    $message->to($adminEmail, $adminName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendOrgUserAutoEnrolledMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Organization User Auto Enrolled";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);	
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
		
		$employee = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
        $empName = $employee->employee_name;
        $empEmail = $employee->email;
        $empContact = $employee->contact;
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['name'] = $orgAdminEmailName;
		$data['email'] = $orgAdminEmail;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		$data['empName'] = $empName;
		$data['empEmail'] = $empEmail;
		$data['empContact'] = $empContact;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.organizationAutoEnrolled', $data, function($message) use ($mailSubject, $orgAdminEmailArr)
				{
				    $message->cc($orgAdminEmailArr)->subject($mailSubject);
				});			
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendOrgSystemScreenshotTakenMail($orgId, $orgEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."System Screenshot Taken";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}
        
        $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        $empModelObj = New OrgEmployee;
        $empModelObj->setConnection($orgDbConName);
        $employee = $empModelObj->select(["*"])
                ->joinDepartmentTable()->joinDesignationTable()->byId($orgEmpId)->first();
			
        $empNo = $employee->employee_no;
        $empName = $employee->employee_name;
        $empEmail = $employee->email;
        $empContact = $employee->contact;
        $deptName = $employee->department_name;
        $desigName = $employee->designation_name;

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		$data['empNo'] = $empNo;
		$data['email'] = $empEmail;
		$data['contact'] = $empContact;
		$data['department'] = $deptName;
		$data['designation'] = $desigName;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgScreenshotTakenIntimation', $data, function($message) use ($mailSubject, $orgAdminEmailArr)
				{
				    $message->cc($orgAdminEmailArr)->subject($mailSubject);
				});			
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}

			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgScreenshotTakenAcknowledgement', $data, function($message) use ($mailSubject, $empEmail, $empName)
				{
				    $message->to($empEmail, $empName)->subject($mailSubject);
				});		
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}
	
	public static function sendOrgEmployeeContentRestoredMail($orgId, $orgEmpId, $restoreDataMetrics)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Content(s) Restored";
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);		
		$employee = OrganizationClass::getOrgEmployeeObject($orgId, $orgEmpId);
        
        $empName = $employee->employee_name;
        $empEmail = $employee->email;

		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
		}

		$data = array();
		$data['name'] = $empName;
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgLogoHtml'] = $orgLogoHtml;
		$data['orgName'] = $organization->regd_name;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		$data['restoreDataMetrics'] = $restoreDataMetrics;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if($empEmail != "" && (isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0)) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.employeeAccountContentRestoredIntimation', $data, function($message) use ($mailSubject, $orgAdminEmailArr)
				{
				    $message->cc($orgAdminEmailArr)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}

			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.employeeAccountContentRestored', $data, function($message) use ($mailSubject, $empEmail, $empName)
				{
				    $message->to($empEmail, $empName)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendProfileQuotaExhaustWarningMail($orgId, $userOrEmpId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." Quota Exhaustion Warning";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
	
        $systemOwnerEmail = Config::get('app_config_mail.system_owner_email');
        $systemOwnerEmailName = Config::get('app_config_mail.system_owner_email_name');
        
        $systemSalesEmail = Config::get('app_config_mail.system_sales_email');
        $systemSalesEmailName = Config::get('app_config_mail.system_sale_email_name');
        
        $bccEmail = array();
        $bccEmail[0] = $systemHelpEmail;
        $bccEmail[1] = $systemOwnerEmail;
        $bccEmail[2] = $systemSalesEmail;
        $bccEmail[3] = "chirayu@itechnosol.com";
		
		$allottedKb = NULL;
       	$availableKb = NULL;
       	$usedKb = NULL;
        
        $isOrg = FALSE;
        $orgName = '';
		$orgLogoThumbUrl = "";
		$orgLogoHtml = "";
        if($orgId > 0) {
	        $depMgmtObj = New ContentDependencyManagementClass;
	       	$depMgmtObj->withOrgIdAndEmpId($orgId, $userOrEmpId);
	       	
			$organization = $depMgmtObj->getOrganizationObject();	
			if(isset($organization)) {
				$isOrg = TRUE;
				$orgName = $organization->regd_name;

				$logoFilename = $organization->logo_filename;
				if(isset($logoFilename) && $logoFilename != "")
				{
					$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
					$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
				}
				
		       	$employee = $depMgmtObj->getEmployeeObject();
		        $userName = $employee->employee_name;
		        $userEmail = $employee->email;
		        
		       	$orgEmployeeConstant = $depMgmtObj->getEmployeeConstantObject();
		       	if(isset($orgEmployeeConstant)) {
					$allottedKb = $orgEmployeeConstant->attachment_kb_allotted;
			       	$availableKb = $orgEmployeeConstant->attachment_kb_available;
			       	$usedKb = $orgEmployeeConstant->attachment_kb_used;
				}	
			}
		}
		
		if(!$isOrg) {
			$user = Appuser::byId($userOrEmpId)->first();	
			if(isset($user)) {
		        $userName = $user->fullname;
		        $userEmail = $user->email;
		        $userConstant = $user->userConstants;
		        if(isset($userConstant)) {
			       	$allottedKb = $userConstant->attachment_kb_allotted;
			       	$availableKb = $userConstant->attachment_kb_available;
			       	$usedKb = $userConstant->attachment_kb_used;
				}
			}	
		}
		
		if(isset($allottedKb) && isset($usedKb) && $allottedKb > 0) {
			$usedPercent = round(($usedKb/$allottedKb)*100);
			
			$allottedQuotaStr = "";
			if($allottedKb > 1000) {
				$allottedMb = round($allottedKb/1024, 2);
				if($allottedMb > 1000) {
					$allottedGb = round($allottedMb/1024, 2);	
					$allottedQuotaStr = $allottedGb." GB(s)";			
				}
				else {
					$allottedQuotaStr = $allottedMb." MB(s)";
				}
			}
			else {
				$allottedQuotaStr = $allottedKb." KB(s)";
			}

			$data = array();
			$data['name'] = $userName;
			$data['isOrg'] = $isOrg;
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgLogoHtml'] = $orgLogoHtml;
			$data['orgName'] = $orgName;
			$data['usedPercent'] = $usedPercent;
			$data['allottedQuotaStr'] = $allottedQuotaStr;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.quotaExhaustWarning', $data, function($message) use ($mailSubject, $userEmail, $userName, $bccEmail)
				{
				    $message->to($userEmail, $userName)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendAnalyticsMail($startTs, $endTs)
	{
    	set_time_limit(0);

		MailClass::init();
		
		$mailSubject = MailClass::$systemName." - "."Analytics for Retail";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
	
        $systemOwnerEmail = Config::get('app_config_mail.system_owner_email');
        $systemOwnerEmailName = Config::get('app_config_mail.system_owner_email_name');
        
        $systemSalesEmail = Config::get('app_config_mail.system_sales_email');
        $systemSalesEmailName = Config::get('app_config_mail.system_sale_email_name');
        
        $bccEmail = array();
        $bccEmail[0] = $systemOwnerEmail;
        $bccEmail[1] = $systemSalesEmail;
        $bccEmail[2] = "chirayu@itechnosol.com";
        
        $orgBccEmailArr = array();
        $orgBccEmailArr[0] = $systemHelpEmail;
        $orgBccEmailArr[1] = $systemOwnerEmail;
        $orgBccEmailArr[2] = $systemSalesEmail;
        $orgBccEmailArr[3] = "chirayu@itechnosol.com";
        
        /* for the purpose of testing only */
        /*$systemHelpEmail = "amruta@itechnosol.com";
        $bccEmail = [ ];
        $orgBccEmailArr = [ $systemHelpEmail ];*/
        /* for the purpose of testing only */
		
		$utcStartDt = Carbon::createFromTimeStampUTC($startTs);		
		$startDtStr = $utcStartDt->toDateTimeString();
		
		$utcEndDt = Carbon::createFromTimeStampUTC($endTs);		
		$endDtStr = $utcEndDt->toDateTimeString();
		
		//print_r('sendAnalyticsMail : $startDtStr : '.$startDtStr.' : $endDtStr : '.$endDtStr."<br/>");
        
		$startDateFormatted = $utcStartDt->toFormattedDateString();	
		$endDateFormatted = $utcEndDt->toFormattedDateString();	

		$data = array();
		$data['name'] = $systemHelpEmailName;
		$data['startDate'] = $startDateFormatted;
		$data['endDate'] = $endDateFormatted;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		// For Retail
		$orgId = 0;
		$orgEmpId = 0;
		$appusers = Appuser::verified()->get();
		$compiledResults = array();
		foreach($appusers as $appuser)
		{        		
	        $depMgmtObj = New ContentDependencyManagementClass;
	       	$depMgmtObj->withUserIdOrgIdAndEmpId($appuser, $orgId, $orgEmpId);
	       	
			//print_r('$appuser : '.$appuser->fullname."<br/>");
	       	
			$isFolder = TRUE;
			$grpId = NULL;
		
	       	$contentsCreated = $depMgmtObj->getAllContentsCreatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
	       	$contentsUpdated = $depMgmtObj->getAllContentsUpdatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
		    $attachments = $depMgmtObj->getAllContentAttachmentsAddedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
	       	$contentCreatedCount = 0;
	       	if(isset($contentsCreated))
	       		$contentCreatedCount = count($contentsCreated);
	       	$contentUpdatedCount = 0;
	       	if(isset($contentsUpdated))
	       		$contentUpdatedCount = count($contentsUpdated);
	       	
	       	$quotaUsedKb = 0;
	       	if(isset($attachments)) {
				foreach($attachments as $att) {
					$quotaUsedKb += $att->filesize;
				}
			}
			if($quotaUsedKb > 0) {
				if($quotaUsedKb > 1000) {
					$quotaUsedMb = round($quotaUsedKb/1024, 2);
					$quotaUsedStr = "$quotaUsedMb KB(s)";
				}
				else {
					$quotaUsedStr = "$quotaUsedKb KB(s)";
				}
			}
			else {
				$quotaUsedStr = "0 KB(s)";
			}
			
			//print_r('$contentCreatedCount : '.$contentCreatedCount.' : $contentUpdatedCount : '.$contentUpdatedCount."<br/>");
			
			$grpContentCreatedCount = 0;
			$grpContentUpdatedCount = 0;
	       	$grpQuotaUsedKb = 0;
			
			$allUserGroups = $depMgmtObj->getAllGroupsFoUser();
			//print_r('$allUserGroups : '.count($allUserGroups)."<br/>");
			//$allUserGroups = array();
			foreach ($allUserGroups as $usrGroup) 
	    	{	
	    		$isFolder = FALSE;
	    		$grpId = $usrGroup->group_id;
	    		
					       	
				$grpContentsCreated = $depMgmtObj->getAllContentsCreatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
		       	$grpContentsUpdated = $depMgmtObj->getAllContentsUpdatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
		       	$grpContentAttachments = $depMgmtObj->getAllContentAttachmentsAddedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
				
		       	if(isset($grpContentsCreated) && count($grpContentsCreated) > 0)
		       		$grpContentCreatedCount += count($grpContentsCreated);
		       	if(isset($grpContentsUpdated) && count($grpContentsUpdated) > 0)
		       		$grpContentUpdatedCount += count($grpContentsUpdated);
		       		
		       	if(isset($grpContentAttachments)) {
					foreach($grpContentAttachments as $att) {
						$grpQuotaUsedKb += $att->filesize;
					}
				}
				
			       
			}
			
			$grpQuotaUsedStr = '';
			if($grpQuotaUsedKb > 0) {
				if($grpQuotaUsedKb > 1000) {
					$grpQuotaUsedMb = round($grpQuotaUsedKb/1024, 2);
					$grpQuotaUsedStr = "$grpQuotaUsedMb KB(s)";
				}
				else {
					$grpQuotaUsedStr = "$grpQuotaUsedKb KB(s)";
				}
			}
			else {
				$grpQuotaUsedStr = "0 KB(s)";
			}
			
			$totalContentCount = $contentCreatedCount + $contentUpdatedCount;
			$totalGrpContentCount = $grpContentCreatedCount + $grpContentUpdatedCount;
			$totalAllContentCount = $totalContentCount + $totalGrpContentCount;
				
			$appuserResult = array();
			$appuserResult['name'] = $appuser->fullname;
			$appuserResult['email'] = $appuser->email;
			$appuserResult['contact'] = $appuser->contact;
			$appuserResult['contentCreated'] = $contentCreatedCount;
			$appuserResult['contentUpdated'] = $contentUpdatedCount;
			$appuserResult['quotaUsed'] = $quotaUsedStr;
			$appuserResult['totalContentCount'] = $totalContentCount;
			$appuserResult['grpContentCreated'] = $grpContentCreatedCount;
			$appuserResult['grpContentUpdated'] = $grpContentUpdatedCount;
			$appuserResult['grpQuotaUsed'] = $grpQuotaUsedStr;
			$appuserResult['totalGrpContentCount'] = $totalGrpContentCount;
			$appuserResult['totalAllContentCount'] = $totalAllContentCount;
			
			array_push($compiledResults, $appuserResult);
		}
		
		$sortOrder = -1;
		$sortByColName = 'totalAllContentCount'; //'contentCreated';				
		$compiledResultsCollection = collect($compiledResults);
		$sortedCompiledResults;
		if(isset($sortOrder) && $sortOrder > 0) {
			$sortedCompiledResults = $compiledResultsCollection->sortBy($sortByColName);
		}
		else {
			$sortedCompiledResults = $compiledResultsCollection->sortBy($sortByColName, SORT_REGULAR, true);
		}
		$compiledResults = $sortedCompiledResults->toArray();
		
		$orgId = 0;
        $depMgmtObj = New ContentDependencyManagementClass;
       	$depMgmtObj->withOrgId($orgId);
		$orgGroups = array();//$depMgmtObj->getAllGroupsForOrganization();
	    $compiledGroupResults = array();
    	foreach ($orgGroups as $orgGroup) 
    	{		
			$isFolder = FALSE;	
    		$grpId = $orgGroup->group_id;
	       	
	       	$contentsCreated = $depMgmtObj->getAllContentsCreatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
	       	$contentsUpdated = $depMgmtObj->getAllContentsUpdatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
	       	$attachments = $depMgmtObj->getAllContentAttachmentsAddedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
			
	       	$contentCreatedCount = 0;
	       	if(isset($contentsCreated))
	       		$contentCreatedCount = count($contentsCreated);
	       	$contentUpdatedCount = 0;
	       	if(isset($contentsUpdated))
	       		$contentUpdatedCount = count($contentsUpdated);
	       	$quotaUsedKb = 0;
	       	if(isset($attachments)) {
				foreach($attachments as $att) {
					$quotaUsedKb += $att->filesize;
				}
			}
			if($quotaUsedKb > 0) {
				if($quotaUsedKb > 1000) {
					$quotaUsedMb = round($quotaUsedKb/1024, 2);
					$quotaUsedStr = "$quotaUsedMb KB(s)";
				}
				else {
					$quotaUsedStr = "$quotaUsedKb KB(s)";
				}
			}
			else {
				$quotaUsedStr = "0 KB(s)";
			}
			
			$empResult = array();
			$empResult['name'] = $orgGroup->name;
			$empResult['contentCreated'] = $contentCreatedCount;
			$empResult['contentUpdated'] = $contentUpdatedCount;
			$empResult['totalContentCount'] = $contentCreatedCount + $contentUpdatedCount;
			$empResult['quotaUsed'] = $quotaUsedStr;
			
			array_push($compiledGroupResults, $empResult);
		}
		
		$sortOrder = -1;
		$sortByColName = 'totalContentCount'; //'contentCreated';				
		$compiledGroupResultsCollection = collect($compiledGroupResults);
		$sortedCompiledGroupResults;
		if(isset($sortOrder) && $sortOrder > 0) {
			$sortedCompiledGroupResults = $compiledGroupResultsCollection->sortBy($sortByColName);
		}
		else {
			$sortedCompiledGroupResults = $compiledGroupResultsCollection->sortBy($sortByColName, SORT_REGULAR, true);
		}
		$compiledGroupResults = $sortedCompiledGroupResults->toArray();
		
		$compiledGroupResults = NULL;
		
		$data['isRetail'] = TRUE;
		$data['orgName'] = 'Retail';
		$data['compiledResults'] = $compiledResults;		
		$data['compiledGroupResults'] = $compiledGroupResults;	

		try
		{	
			Mail::send('email.organizationAnalytics', $data, function($message) use ($mailSubject, $systemHelpEmail, $systemHelpEmailName, $bccEmail)
			{
				$message->to($systemHelpEmail, $systemHelpEmailName)->subject($mailSubject);
				if(isset($bccEmail) && count($bccEmail) > 0) {
					$message->bcc($bccEmail);
				}
			});				
		}
		catch(\Exception $e)
		{
			// Error occured
		}
		
		// For Organizations
		$organizations = Organization::active()->get();
        foreach ($organizations as $org) 
        {
        	$orgId = $org->organization_id;
        	$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
        	
        	if(isset($orgDbConName) && $orgDbConName != "") {
				$modelObj = New OrgEmployee;
		        $modelObj->setConnection($orgDbConName);
	        	$orgEmployees = $modelObj->verifiedAndActive()->get();
	        	
	        	$hasEmpMetrics = FALSE;
	        	$hasGrpMetrics = FALSE;
	        	
	        	$compiledResults = array();
	        	foreach ($orgEmployees as $orgEmployee) 
	        	{			
	        		$orgEmpId = $orgEmployee->employee_id;	
	        		
			        $depMgmtObj = New ContentDependencyManagementClass;
			       	$depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);
			       	
					$isFolder = TRUE;	
					$grpId = NULL;
			       	
			       	$contentsCreated = $depMgmtObj->getAllContentsCreatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
			       	$contentsUpdated = $depMgmtObj->getAllContentsUpdatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
			       	$attachments = $depMgmtObj->getAllContentAttachmentsAddedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
					
			       	$contentCreatedCount = 0;
			       	if(isset($contentsCreated))
			       		$contentCreatedCount = count($contentsCreated);
			       	$contentUpdatedCount = 0;
			       	if(isset($contentsUpdated))
			       		$contentUpdatedCount = count($contentsUpdated);
			       	$quotaUsedKb = 0;
			       	if(isset($attachments)) {
						foreach($attachments as $att) {
							$quotaUsedKb += $att->filesize;
						}
					}
					
					$quotaUsedKb += $contentCreatedCount*2; //add 2 kb per content
					
					if($contentCreatedCount + $contentUpdatedCount + $quotaUsedKb > 0) {
						$hasEmpMetrics = TRUE; 
					}
					
					if($quotaUsedKb > 0) {
						if($quotaUsedKb > 1000) {
							$quotaUsedMb = round($quotaUsedKb/1024, 2);
							$quotaUsedStr = "$quotaUsedMb KB(s)";
						}
						else {
							$quotaUsedStr = "$quotaUsedKb KB(s)";
						}
					}
					else {
						$quotaUsedStr = "0 KB(s)";
					}
					
					$empResult = array();
					$empResult['name'] = $orgEmployee->employee_name;
					$empResult['email'] = $orgEmployee->email;
					$empResult['contact'] = $orgEmployee->contact;
					$empResult['contentCreated'] = $contentCreatedCount;
					$empResult['contentUpdated'] = $contentUpdatedCount;
					$empResult['totalContentCount'] = $contentCreatedCount + $contentUpdatedCount;
					$empResult['quotaUsed'] = $quotaUsedStr;
					
					array_push($compiledResults, $empResult);
				}
			
				$sortOrder = -1;
				$sortByColName = 'totalContentCount'; //'contentCreated';				
				$compiledResultsCollection = collect($compiledResults);
				$sortedCompiledResults;
				if(isset($sortOrder) && $sortOrder > 0) {
					$sortedCompiledResults = $compiledResultsCollection->sortBy($sortByColName);
				}
				else {
					$sortedCompiledResults = $compiledResultsCollection->sortBy($sortByColName, SORT_REGULAR, true);
				}
				$compiledResults = $sortedCompiledResults->toArray();
				
		        $depMgmtObj = New ContentDependencyManagementClass;
		       	$depMgmtObj->withOrgId($orgId);
				$orgGroups = $depMgmtObj->getAllGroupsForOrganization();
	        	$compiledGroupResults = array();
	        	foreach ($orgGroups as $orgGroup) 
	        	{	
					$isFolder= FALSE;		
	        		$grpId = $orgGroup->group_id;
			       	
			       	$contentsCreated = $depMgmtObj->getAllContentsCreatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
			       	$contentsUpdated = $depMgmtObj->getAllContentsUpdatedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
			       	$attachments = $depMgmtObj->getAllContentAttachmentsAddedInDuration($isFolder, $grpId, $startDtStr, $endDtStr);
					
			       	$contentCreatedCount = 0;
			       	if(isset($contentsCreated))
			       		$contentCreatedCount = count($contentsCreated);
			       	$contentUpdatedCount = 0;
			       	if(isset($contentsUpdated))
			       		$contentUpdatedCount = count($contentsUpdated);
			       	$quotaUsedKb = 0;
			       	if(isset($attachments)) {
						foreach($attachments as $att) {
							$quotaUsedKb += $att->filesize;
						}
					}
					
					$quotaUsedKb += $contentCreatedCount*2; //add 2 kb per content
					
					if($contentCreatedCount + $contentUpdatedCount + $quotaUsedKb > 0) {
						$hasGrpMetrics = TRUE; 
					}
					
					if($quotaUsedKb > 0) {
						if($quotaUsedKb > 1000) {
							$quotaUsedMb = round($quotaUsedKb/1024, 2);
							$quotaUsedStr = "$quotaUsedMb KB(s)";
						}
						else {
							$quotaUsedStr = "$quotaUsedKb KB(s)";
						}
					}
					else {
						$quotaUsedStr = "0 KB(s)";
					}
					
					$empResult = array();
					$empResult['name'] = $orgGroup->name;
					$empResult['contentCreated'] = $contentCreatedCount;
					$empResult['contentUpdated'] = $contentUpdatedCount;
					$empResult['totalContentCount'] = $contentCreatedCount + $contentUpdatedCount;
					$empResult['quotaUsed'] = $quotaUsedStr;
					
					array_push($compiledGroupResults, $empResult);
				}
		
				$sortOrder = -1;
				$sortByColName = 'totalContentCount'; //'contentCreated';				
				$compiledGroupResultsCollection = collect($compiledGroupResults);
				$sortedCompiledGroupResults;
				if(isset($sortOrder) && $sortOrder > 0) {
					$sortedCompiledGroupResults = $compiledGroupResultsCollection->sortBy($sortByColName);
				}
				else {
					$sortedCompiledGroupResults = $compiledGroupResultsCollection->sortBy($sortByColName, SORT_REGULAR, true);
				}
				$compiledGroupResults = $sortedCompiledGroupResults->toArray();
				
				$orgAdminEmailArr = array();
				$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
				foreach($orgAdmins as $admin) {
					/* Comment this for the purpose of testing */
					array_push($orgAdminEmailArr, $admin->admin_email);
					/* Comment this for the purpose of testing */
				}
				
				$orgName = $org->regd_name;

				$logoFilename = $org->logo_filename;
				$orgLogoThumbUrl = "";
				$orgLogoHtml = "";
				if(isset($logoFilename) && $logoFilename != "")
				{
					$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
					$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $org->regd_name);
				}
			
				$data['isRetail'] = FALSE;
				$data['orgLogoUrl'] = $orgLogoThumbUrl;
				$data['orgLogoHtml'] = $orgLogoHtml;
				$data['orgName'] = $orgName;
				$data['compiledResults'] = $compiledResults;
				$data['compiledGroupResults'] = $compiledGroupResults;	
				
				$mailSubject = MailClass::$systemName." - ".$orgName." - "."Analytics for Enterprise";
				
				if($hasEmpMetrics || $hasGrpMetrics) 
                {
                	try
                	{
                		MailClass::setupOrganizationRelevantSmtpDetails($orgId);
						Mail::send('email.organizationAnalytics', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $orgBccEmailArr)
						{
							$message->subject($mailSubject);
							if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) {
						    	$message->cc($orgAdminEmailArr);
							}
							if(isset($orgBccEmailArr) && count($orgBccEmailArr) > 0) {
						    	$message->bcc($orgBccEmailArr);
							}
						});
						MailClass::resetSetupOrganizationSmtpDetails();
					}
					catch(\Exception $e)
					{
						// Error occured
					}
				}
			}   
		}
	}

	public static function sendBackupGeneratedMail($orgId, $backupDesc, $createdAt, $createdBy)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Backup Generated";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;
		
		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
		}
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgName'] = $organization->regd_name;
		$data['backupDesc'] = $backupDesc;
		$data['createdAt'] = $createdAt;
		$data['createdBy'] = $createdBy;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgBackupCreated', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendBackupDeletedMail($orgId, $backupDesc, $createdAt, $createdBy, $deletedAt, $deletedBy)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Backup Deleted";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;
		
		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
		}
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgName'] = $organization->regd_name;
		$data['backupDesc'] = $backupDesc;
		$data['createdAt'] = $createdAt;
		$data['createdBy'] = $createdBy;
		$data['deletedAt'] = $deletedAt;
		$data['deletedBy'] = $deletedBy;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgBackupDeleted', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendBackupDownloadedMail($orgId, $backupDesc, $createdAt, $createdBy, $downloadedAt, $downloadedBy)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Backup Downloaded";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;
		
		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
		}
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgName'] = $organization->regd_name;
		$data['backupDesc'] = $backupDesc;
		$data['createdAt'] = $createdAt;
		$data['createdBy'] = $createdBy;
		$data['downloadedAt'] = $downloadedAt;
		$data['downloadedBy'] = $downloadedBy;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgBackupDownloaded', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendBackupRestoredMail($orgId, $backupDesc, $createdAt, $createdBy, $restoredAt, $restoredBy)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Backup Restored";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		$orgAdminEmail = $organization->email;
		$orgAdminEmailName = $organization->regd_name;
		
		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
		}
        
        $bccEmail = $systemHelpEmail;

		$data = array();
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['orgName'] = $organization->regd_name;
		$data['backupDesc'] = $backupDesc;
		$data['createdAt'] = $createdAt;
		$data['createdBy'] = $createdBy;
		$data['restoredAt'] = $restoredAt;
		$data['restoredBy'] = $restoredBy;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		$orgAdminEmailArr = array();
		$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
		foreach($orgAdmins as $admin) {
			array_push($orgAdminEmailArr, $admin->admin_email);
		}
		
		if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
		{
			try
			{
				MailClass::setupOrganizationRelevantSmtpDetails($orgId);
				Mail::send('email.orgBackupRestored', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
				{
				    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
				});
				MailClass::resetSetupOrganizationSmtpDetails();
			}
			catch(\Exception $e)
			{
				// Error occured
			}
		}
	}

	public static function sendAppuserPremiumSignupMail($userId, $refCode)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."New Premium User Sign Up";
		$user = Appuser::findOrFail($userId);
		
        $userName = $user->fullname;
        $userEmail = $user->email;
        $systemHelpEmail = Config::get('app_config_mail.system_owner_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_owner_email_name');
        
        $bccEmailArr = array();
        $bccEmailArr[0] = Config::get('app_config_mail.system_admin_email');

		$appKeyMapping = $user->appKeyMapping;
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['refCode'] = $refCode;
		$data['email'] = $userEmail;
		$data['contact'] = $user->contact;
		$data['city'] = $user->city;
		$data['country'] = $user->country;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			Mail::send('email.premiumUserSignUpIntimation', $data, function($message) use ($mailSubject, $systemHelpEmail, $systemHelpEmailName, $bccEmailArr)
			{
			    $message->to($systemHelpEmail, $systemHelpEmailName)->subject($mailSubject);
	  			if(isset($bccEmailArr) && count($bccEmailArr) > 0) {
	  		    	$message->cc($bccEmailArr);
	  			}
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendOrganizationReferralSignupMail($orgId, $refCode)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."New Organization Referral Sign Up";
		$organization = Organization::findOrFail($orgId);
		
		$logoFilename = $organization->logo_filename;
		$orgLogoThumbUrl = "";
		if(isset($logoFilename) && $logoFilename != "")
		{
			$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
		}

        $regdName = $organization->regd_name;
        $orgEmail = $organization->email;
        $systemHelpEmail = Config::get('app_config_mail.system_owner_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_owner_email_name');

        $bccEmailArr = array();
        $bccEmailArr[0] = Config::get('app_config_mail.system_admin_email');

		$data = array();
		$data['orgLogoUrl'] = $orgLogoThumbUrl;
		$data['regdName'] = $regdName;
		$data['orgCode'] = $organization->org_code;
		$data['refCode'] = $refCode;
		$data['email'] = $orgEmail;
		$data['contact'] = $organization->phone;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
		
		try
		{
			MailClass::setupOrganizationRelevantSmtpDetails($orgId);
			Mail::send('email.refOrganizationSignUpIntimation', $data, function($message) use ($mailSubject, $systemHelpEmail, $systemHelpEmailName, $bccEmailArr)
			{
			    $message->to($systemHelpEmail, $systemHelpEmailName)->subject($mailSubject);
	  			if(isset($bccEmailArr) && count($bccEmailArr) > 0) {
	  		    	$message->cc($bccEmailArr);
	  			}
			});
			MailClass::resetSetupOrganizationSmtpDetails();
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendAppuserPremiumSubscriptionExpiredMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Business Subscription Expired";
		$user = Appuser::byId($userId)->active()->first();

		if(isset($user))
		{
			$userName = $user->fullname;
	        $userEmail = $user->email;

			$data = array();
			$data['name'] = $userName;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			if($userEmail != "")
			{
				try
				{
					Mail::send('email.appuserPremiumSubscriptionExpired', $data, function($message) use ($mailSubject, $userEmail, $userName)
					{
					    $message->to($userEmail, $userName)->subject($mailSubject);
					});			
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendAppuserPremiumSubscriptionExpiryDueMail($userId, $laterDayCount, $dayCntLaterDtStr)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Your Business subscription expires in ".$laterDayCount. " day(s)";
		$user = Appuser::byId($userId)->active()->first();

		if(isset($user))
		{
			$userName = $user->fullname;
	        $userEmail = $user->email;

			$data = array();
			$data['name'] = $userName;
			$data['laterDayCount'] = $laterDayCount;
			$data['dayCntLaterDtStr'] = $dayCntLaterDtStr;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			if($userEmail != "")
			{
				try
				{
					Mail::send('email.appuserPremiumSubscriptionExpiryDue', $data, function($message) use ($mailSubject, $userEmail, $userName)
					{
					    $message->to($userEmail, $userName)->subject($mailSubject);
					});			
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendOrganizationSubscriptionExpiryDueMail($orgId, $laterDayCount, $dayCntLaterDtStr)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Your Enterprise subscription expires in ".$laterDayCount. " day(s)";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		if(isset($organization))
		{
			$orgAdminEmail = $organization->email;
			$orgAdminEmailName = $organization->regd_name;
			
			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			}
	        
	        $bccEmail = $systemHelpEmail;

			$data = array();
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgName'] = $organization->regd_name;
			$data['laterDayCount'] = $laterDayCount;
			$data['dayCntLaterDtStr'] = $dayCntLaterDtStr;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			$orgAdminEmailArr = array();
			$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
			foreach($orgAdmins as $admin) {
				array_push($orgAdminEmailArr, $admin->admin_email);
			}

			if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.orgEnterpriseSubscriptionExpiryDue', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
					{
					    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
					});
					MailClass::resetSetupOrganizationSmtpDetails();
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendOrganizationSubscriptionExpiredMail($orgId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Your Enterprise subscription has expired";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		if(isset($organization))
		{
			$orgAdminEmail = $organization->email;
			$orgAdminEmailName = $organization->regd_name;
			
			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			}
	        
	        $bccEmail = $systemHelpEmail;

			$data = array();
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgName'] = $organization->regd_name;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			$orgAdminEmailArr = array();
			$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
			foreach($orgAdmins as $admin) {
				array_push($orgAdminEmailArr, $admin->admin_email);
			}

			if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.orgEnterpriseSubscriptionExpired', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
					{
					    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
					});			
					MailClass::resetSetupOrganizationSmtpDetails();	
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}


	public static function sendOrganizationQuotaExhaustedMail($orgId, $allottedGb)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Enterprise allotted space exhausted";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		if(isset($organization))
		{
			$orgAdminEmail = $organization->email;
			$orgAdminEmailName = $organization->regd_name;
			
			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			}
	        
	        $bccEmail = $systemHelpEmail;

			$data = array();
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgName'] = $organization->regd_name;
			$data['allottedGb'] = $allottedGb;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			$orgAdminEmailArr = array();
			$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
			foreach($orgAdmins as $admin) {
				array_push($orgAdminEmailArr, $admin->admin_email);
			}

			if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.organizationQuotaExhausted', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
					{
					    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
					});			
					MailClass::resetSetupOrganizationSmtpDetails();	
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendOrganizationUserCountExhaustedMail($orgId, $allottedUserCount)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Your Enterprise account's allotted user are exhausted";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		if(isset($organization))
		{
			$orgAdminEmail = $organization->email;
			$orgAdminEmailName = $organization->regd_name;
			
			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			}
	        
	        $bccEmail = $systemHelpEmail;

			$data = array();
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgName'] = $organization->regd_name;
			$data['allottedUserCount'] = $allottedUserCount;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			$orgAdminEmailArr = array();
			$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
			foreach($orgAdmins as $admin) {
				array_push($orgAdminEmailArr, $admin->admin_email);
			}

			if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.organizationUserCountExhausted', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
					{
					    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
					});			
					MailClass::resetSetupOrganizationSmtpDetails();	
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendOrganizationEnterpriseCouponCodeUtilizedMail($orgId, $enterpriseCouponCodeId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "."Enterprise allotted user accounts exhausted";
		
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $systemHelpEmailName = Config::get('app_config_mail.system_help_email_name');

        $enterpriseCouponCode = EnterpriseCouponCode::byId($enterpriseCouponCodeId)->first();
		
		$organization = OrganizationClass::getOrganizationFromOrgId($orgId);
		if(isset($organization) && isset($enterpriseCouponCode))
		{
	        $enterpriseCouponId = $enterpriseCouponCode->enterprise_coupon_id;
	        $enterpriseCoupon = EnterpriseCoupon::byId($enterpriseCouponId)->first();
	        
			$orgAdminEmail = $organization->email;
			$orgAdminEmailName = $organization->regd_name;
			
			$logoFilename = $organization->logo_filename;
			$orgLogoThumbUrl = "";
			if(isset($logoFilename) && $logoFilename != "")
			{
				$orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
			}
	        
	        $bccEmail = $systemHelpEmail;

			$data = array();
			$data['orgLogoUrl'] = $orgLogoThumbUrl;
			$data['orgName'] = $organization->regd_name;
			$data['enterpriseCouponCode'] = $enterpriseCouponCode;
			$data['enterpriseCoupon'] = $enterpriseCoupon;
			$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
			$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;
			
			$orgAdminEmailArr = array();
			$orgAdmins = OrganizationClass::getOrganizationAdministrators($orgId);
			foreach($orgAdmins as $admin) {
				array_push($orgAdminEmailArr, $admin->admin_email);
			}

			if(isset($orgAdminEmailArr) && count($orgAdminEmailArr) > 0) 
			{
				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.organizationEnterpriseCouponCodeUtilized', $data, function($message) use ($mailSubject, $orgAdminEmailArr, $bccEmail)
					{
					    $message->cc($orgAdminEmailArr)->bcc($bccEmail)->subject($mailSubject);
					});
					MailClass::resetSetupOrganizationSmtpDetails();
				}
				catch(\Exception $e)
				{
					// Error occured
				}
			}
		}
	}

	public static function sendVideoConferenceStartedOrParticipantJoinedMail($appuserId, $encOrgId, $isStartPush, $userConference, $userConferenceParticipant, $pushForUserOrEmpName, $pushForUserOrEmpEmail)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." Quota Exhaustion Warning";

        if($appuserId > 0 && isset($userConference) && isset($userConferenceParticipant))
        {
            $user = Appuser::byId($appuserId)->first();
            
            if(isset($user) )
            {                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId); 
                         
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId); 

                $conferenceId = 0;
                if($orgId > 0)
                {
                    $conferenceId = $userConference->org_vc_id;
                }
                else
                {
                    $conferenceId = $userConference->sys_vc_id;
                }

                $conferenceSubject = Crypt::decrypt($userConference->meeting_title);

                $orgEmpName = $depMgmtObj->getEmployeeOrUserName();
                $orgEmpEmail = $depMgmtObj->getEmployeeOrUserEmail();

                $orgName = "";
                $orgLogoThumbUrl = "";
                $orgLogoHtml = "";
                if($orgId > 0)
                {
                    $organization = $depMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgName = $organization->system_name;
                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            $orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
							$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
                        }
                    }
                }

                $mailSubject = "";
                $confStartStr = "";

                if($isStartPush)
                {
                    $mailSubject = 'Video Conference Started';
                    $confStartStr = 'Video Conference '.$conferenceSubject.' has been started by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }
                else
                {
                    $mailSubject = 'Video Conference Has a New Participant';
                    $confStartStr = 'Video Conference '.$conferenceSubject.' has been joined by '.$pushForUserOrEmpName.' <'.$pushForUserOrEmpEmail.'>';
                }

                $utcFromDateStr = "";
                $utcToDateStr = "";

                $istFromDateStr = "";
                $istToDateStr = "";
                                          
				$data = array();
				$data['name'] = $orgEmpName;
				$data['orgLogoUrl'] = $orgLogoThumbUrl;
				$data['orgLogoHtml'] = $orgLogoHtml;
				$data['orgName'] = $orgName;
				$data['conferenceSubject'] = $conferenceSubject;
				$data['confStartStr'] = $confStartStr;
				$data['pushForUserOrEmpEmail'] = $pushForUserOrEmpEmail;
				$data['pushForUserOrEmpName'] = $pushForUserOrEmpName;
				$data['utcFromDateStr'] = $utcFromDateStr;
				$data['utcToDateStr'] = $utcToDateStr;
				$data['istFromDateStr'] = $istFromDateStr;
				$data['istToDateStr'] = $istToDateStr;
				$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
				$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.videoConferenceStartedOrParticipantJoined', $data, function($message) use ($mailSubject, $orgEmpEmail, $orgEmpName)
					{
					    $message->to($orgEmpEmail, $orgEmpName)->subject($mailSubject);
					});		
					MailClass::resetSetupOrganizationSmtpDetails();		
				}
				catch(\Exception $e)
				{
					// Error occured
				}
            }
        }
	}

	public static function sendVideoConferenceParticipantInvitationMail($orgId, $userConference, $recipientName, $recipientEmail, $invitedByName, $invitedByEmail)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." Invitation to Join Video Conference";

        if(isset($userConference))
        {            
            if($recipientEmail != "")
            {     
                $conferenceSubject = Crypt::decrypt($userConference->meeting_title);
				$videoConferenceCode = $userConference->conference_code;
				$videoConferencePassword = Crypt::decrypt($userConference->password);

                $orgName = "";
                $orgLogoThumbUrl = "";
                $orgLogoHtml = "";
                if($orgId > 0)
                {
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgId($orgId); 
                    $organization = $depMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgName = $organization->system_name;
                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            $orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
							$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
                        }
                    }
                }

    			$timeFormatStr = "h:i A";
		        $istOffsetHours = 5;
		        $istOffsetMinutes = 30;

				$fromTimestamp = round($userConference->scheduled_start_ts/1000);

				$utcFromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
				$utcFromDateStr = $utcFromDt->toFormattedDateString() . ' ' . $utcFromDt->format($timeFormatStr);
				
				$istFromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
				$istFromDt = $istFromDt->addHours($istOffsetHours);
				$istFromDt = $istFromDt->addMinutes($istOffsetMinutes);
				$istFromDateStr = $istFromDt->toFormattedDateString() . ' ' . $istFromDt->format($timeFormatStr);

				$toTimestamp = round($userConference->scheduled_end_ts/1000);

				$utcToDt = Carbon::createFromTimeStampUTC($toTimestamp);
				$utcToDateStr = $utcToDt->toFormattedDateString() . ' ' . $utcToDt->format($timeFormatStr);
				
				$istToDt = Carbon::createFromTimeStampUTC($toTimestamp);
				$istToDt = $istToDt->addHours($istOffsetHours);
				$istToDt = $istToDt->addMinutes($istOffsetMinutes);
				$istToDateStr = $istToDt->toFormattedDateString() . ' ' . $istToDt->format($timeFormatStr);
                    
				$meetingTimeStrUTC = $utcFromDateStr." to ".$utcToDateStr;
				$meetingTimeStrIST = $istFromDateStr." to ".$istToDateStr;
					                      
				$data = array();
				$data['name'] = $recipientName;
				$data['recipientName'] = $recipientName;
				$data['recipientEmail'] = $recipientEmail;
				$data['orgLogoUrl'] = $orgLogoThumbUrl;
				$data['orgLogoHtml'] = $orgLogoHtml;
				$data['orgName'] = $orgName;
				$data['conferenceSubject'] = $conferenceSubject;
				$data['videoConferenceCode'] = $videoConferenceCode;
				$data['videoConferencePassword'] = $videoConferencePassword;
				$data['invitedByName'] = $invitedByName;
				$data['invitedByEmail'] = $invitedByEmail;
				$data['utcFromDateStr'] = $utcFromDateStr;
				$data['utcToDateStr'] = $utcToDateStr;
				$data['istFromDateStr'] = $istFromDateStr;
				$data['istToDateStr'] = $istToDateStr;
				$data['meetingTimeStrUTC'] = $meetingTimeStrUTC;
				$data['meetingTimeStrIST'] = $meetingTimeStrIST;
				$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
				$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.videoConferenceParticipantInvitation', $data, function($message) use ($mailSubject, $recipientEmail, $recipientName)
					{
					    $message->to($recipientEmail, $recipientName)->subject($mailSubject);
					});		
					MailClass::resetSetupOrganizationSmtpDetails();		
				}
				catch(\Exception $e)
				{
					// Error occured
					// Log::info('Mail send error : ');
					// Log::info($e);
				}
            }
        }
	}

	public static function sendGroupMembershipInvitationMail($userId, $orgId, $orgEmpId, $groupId, $group, $recipientName, $recipientEmail, $invitedByName, $invitedByEmail)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." Invitation to Join Group";

        if(isset($group))
        {            
            if($recipientEmail != "")
            {     
                $groupName = $group->name;

                $orgName = "";
                $orgLogoThumbUrl = "";
                $orgLogoHtml = "";
                if($orgId > 0)
                {
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgId($orgId); 
                    $organization = $depMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgName = $organization->system_name;
                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            $orgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
							$orgLogoHtml = MailClass::getOrgLogoHtmlStr($orgLogoThumbUrl, $organization->regd_name);
                        }
                    }
                }

				$groupJoinLink = OrganizationClass::getAppuserJoinGroupInvitationUrl($userId, $orgId, $orgEmpId, $groupId, $recipientEmail);
					                      
				$data = array();
				$data['name'] = $recipientName;
				$data['recipientName'] = $recipientName;
				$data['recipientEmail'] = $recipientEmail;
				$data['orgLogoUrl'] = $orgLogoThumbUrl;
				$data['orgLogoHtml'] = $orgLogoHtml;
				$data['orgName'] = $orgName;
				$data['groupName'] = $groupName;
				$data['groupJoinLink'] = $groupJoinLink;
				$data['invitedByName'] = $invitedByName;
				$data['invitedByEmail'] = $invitedByEmail;
				$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
				$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

				try
				{
					MailClass::setupOrganizationRelevantSmtpDetails($orgId);
					Mail::send('email.groupMembershipInvitation', $data, function($message) use ($mailSubject, $recipientEmail, $recipientName)
					{
					    $message->to($recipientEmail, $recipientName)->subject($mailSubject);
					});		
					MailClass::resetSetupOrganizationSmtpDetails();
				}
				catch(\Exception $e)
				{
					// Error occured
					Log::info('Mail send error : ');
					Log::info($e);
				}
            }
        }
	}

	public static function sendCloudStorageTypeAccountLinkedMail($userId, $cloudStorageType)
	{
		$cloudStorageTypeName = $cloudStorageType->cloud_storage_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudStorageTypeName." Account Linked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudStorageTypeName'] = $cloudStorageTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudStorageTypeAccountLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendCloudStorageTypeAccountUnLinkedMail($userId, $cloudStorageType)
	{
		$cloudStorageTypeName = $cloudStorageType->cloud_storage_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudStorageTypeName." Account Unlinked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudStorageTypeName'] = $cloudStorageTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudStorageTypeAccountUnLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendCloudCalendarTypeAccountLinkedMail($userId, $cloudCalendarType)
	{
		$cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudCalendarTypeName." Account Linked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudCalendarTypeName'] = $cloudCalendarTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudCalendarTypeAccountLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendCloudCalendarTypeAccountUnLinkedMail($userId, $cloudCalendarType)
	{
		$cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudCalendarTypeName." Account Unlinked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudCalendarTypeName'] = $cloudCalendarTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudCalendarTypeAccountUnLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendCloudMailBoxTypeAccountLinkedMail($userId, $cloudMailBoxType)
	{
		$cloudMailBoxTypeName = $cloudMailBoxType->cloud_mail_box_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudMailBoxTypeName." Account Linked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudMailBoxTypeName'] = $cloudMailBoxTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudMailBoxTypeAccountLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendCloudMailBoxTypeAccountUnLinkedMail($userId, $cloudMailBoxType)
	{
		$cloudMailBoxTypeName = $cloudMailBoxType->cloud_mail_box_type_name;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - ".$cloudMailBoxTypeName." Account Unlinked";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['cloudMailBoxTypeName'] = $cloudMailBoxTypeName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.cloudMailBoxTypeAccountUnLinked', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendNewAppuserSessionEstablishedMail($userId, $appuserSession)
	{
		$sessionType = $appuserSession->type->session_type_name;
		$clientDetails = $appuserSession->client_details;
		$deviceModelName = $appuserSession->device_model_name;
		$deviceUniqueId = $appuserSession->device_unique_id;
		$ipAddress = $appuserSession->ip_address;
		$mappedAppKeyId = $appuserSession->mapped_app_key_id;
		$appKeyMapping = $appuserSession->appKeyMapping;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." User logged in a new device";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['sessionType'] = $sessionType;
		$data['clientDetails'] = $clientDetails;
		$data['deviceModelName'] = $deviceModelName;
		$data['deviceUniqueId'] = $deviceUniqueId;
		$data['ipAddress'] = $ipAddress;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Log::info('email.appuserSessionEstablished : userEmail : '.$userEmail.' : userName : '.$userName);
			Mail::send('email.appuserSessionEstablished', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});	
			Log::info('email.appuserSessionEstablished : successful : ');		
		}
		catch(\Exception $e)
		{
			// Error occured
			Log::info('email.appuserSessionEstablished : exception : ');
			Log::info($e);
		}
	}

	public static function sendAppuserSessionRemovedMail($userId, $appuserSession)
	{
		$sessionType = $appuserSession->type->session_type_name;
		$clientDetails = $appuserSession->client_details;
		$deviceModelName = $appuserSession->device_model_name;
		$deviceUniqueId = $appuserSession->device_unique_id;
		$ipAddress = $appuserSession->ip_address;
		$mappedAppKeyId = $appuserSession->mapped_app_key_id;
		$appKeyMapping = $appuserSession->appKeyMapping;

		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." User session removed";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email;

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['sessionType'] = $sessionType;
		$data['clientDetails'] = $clientDetails;
		$data['deviceModelName'] = $deviceModelName;
		$data['deviceUniqueId'] = $deviceUniqueId;
		$data['ipAddress'] = $ipAddress;
		$data['appKeyMapping'] = $appKeyMapping;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.appuserSessionRemoved', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendAppuserAuthenticationIncorrectPasswordMail($userId)
	{
		MailClass::init();
		$mailSubject = MailClass::$systemName." - "." User unsuccessful login attempt";
		$user = Appuser::findOrFail($userId);

        $userName = $user->fullname;
        $userEmail = $user->email; 

    	$sessTypeId = Input::get('sessType');
    	$ipAddress = Input::get('ipAddress');
    	$deviceUniqueId = Input::get('deviceUniqueId');
    	$clientDetailsStr = Input::get('clientDetailsStr');
    	$deviceModelName = Input::get('deviceModelName');
    	
    	$sessTypeObj = SessionType::byId($sessTypeId)->first();
		$sessionType = isset($sessTypeObj) ? $sessTypeObj->session_type_name : '';

		$appKeyMapping = CommonFunctionClass::getUserSessionAppKeyMapping();
		if(isset($appKeyMapping))
		{
			MailClass::compileAppKeyMappedDependencied($appKeyMapping);
		}

		$data = array();
		$data['name'] = $userName;
		$data['email'] = $user->email;
		$data['sessionType'] = $sessionType;
		$data['clientDetails'] = $clientDetailsStr;
		$data['deviceModelName'] = $deviceModelName;
		$data['deviceUniqueId'] = $deviceUniqueId;
		$data['ipAddress'] = $ipAddress;
		$data['systemName'] = MailClass::$systemName;
		$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		try
		{
			Mail::send('email.appuserUnsuccessfulLoginAttempt', $data, function($message) use ($mailSubject, $userEmail, $userName)
			{
			    $message->to($userEmail, $userName)->subject($mailSubject);
			});			
		}
		catch(\Exception $e)
		{
			// Error occured
		}
	}

	public static function sendTestCloudCalendarSyncPerformedMail($appuserCloudCalendarToken, $cloudCalendarType, $syncToken, $responseForApiCall)
	{
		// Log::info('WILL ATTEMPT sendTestCloudCalendarSyncPerformedMail');
		// try
		// {
		// 	$cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;

		// 	MailClass::init();

		// 	$mailSubject = MailClass::$systemName." - "." Calendar Account Sync Performed";
		// 	if(!isset($syncToken) || $syncToken == "")
		// 	{
		// 		$mailSubject .= ' - TOKEN RESET HAPPENED';
		// 	}

		// 	$userId = $appuserCloudCalendarToken->appuser_id;

		// 	$user = Appuser::findOrFail($userId);

	 //        $userName = 'Test';
	 //        $userEmail = 'amruta@itechnosol.com'; 

		// 	$data = array();
		// 	$data['name'] = $userName;
		// 	$data['email'] = $user->email;
		// 	$data['appuserCloudCalendarToken'] = $appuserCloudCalendarToken;
		// 	$data['cloudCalendarTypeName'] = $cloudCalendarTypeName;
		// 	$data['syncToken'] = $syncToken;
		// 	$data['responseForApiCall'] = $responseForApiCall;
		// 	$data['systemLogoHtml'] = MailClass::$systemLogoHtml;
		// 	$data['disclaimerHtml'] = MailClass::$systemMailDisclaimer;

		// 	Mail::send('email.tempCloudCalendarPeriodicDataSyncPerformed', $data, function($message) use ($mailSubject, $userEmail, $userName)
		// 	{
		// 	    $message->to($userEmail, $userName)->subject($mailSubject);
		// 	});			
		// }
		// catch(\Exception $e)
		// {
		// 	// Error occured
		// 	Log::info('sendTestCloudCalendarSyncPerformedMail : ERROR : ');
		// 	Log::info($e);
		// }
	}
    
    /**
     * Generate random number for code.
     *
     * @return integer
     */
    public static function getRandomCode()
    {
        $minVal = 10000;
        $maxVal = 99999;

        $randonNum = rand($minVal, $maxVal);

        return $randonNum;
    }
}