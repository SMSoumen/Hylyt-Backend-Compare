<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Libraries\MailClass;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserCloudStorageToken;
use App\Models\Api\AppuserCloudCalendarToken;
use App\Models\Api\AppuserCloudMailBoxToken;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentAdditionalData;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentImage;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\AppOperation;
use App\Models\Api\CloudStorageType;
use App\Models\Api\CloudCalendarType;
use App\Models\Api\CloudMailBoxType;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use DB;
use App\Libraries\ImageUploadClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Ddeboer\Imap\Server;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Date\After;
use Carbon\Carbon;
use Mail;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\OrganizationUser;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\CloudCalendarManagementClass;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentAdditionalData;
use Illuminate\Support\Facades\Log;
use File;

class EmailHandlerController extends Controller
{   
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;

    public function __construct()
    {
    }
    
    /**
     * Add Content.
     *
     * @return json array
     */
    public function saveContentDetails()
    {
    	Log::info(' ---------------- saveContentDetails ------------------- ');
    	set_time_limit(0);
    	
    	/*$data = array();
    	$data['func'] = 'saveContentDetails';
    	$data['startTime'] = Carbon::now();
    	$mailSubject = "Email Reader Accessed";
    	
		Mail::send('email.notification', $data, function($message) use ($mailSubject)
		{
		    $message->to("chirayu.dalwadi@gmail.com", "Chirayu")->subject($mailSubject);
		});*/

		$server = new Server(Config::get('app_config_mail.system_scan_email_server_imap'));

		// $connection is instance of \Ddeboer\Imap\Connection
        $systemScanEmail = Config::get('app_config_mail.system_scan_email');
        $systemScanEmailPassword = Config::get('app_config_mail.system_scan_email_password');
		$connection = $server->authenticate($systemScanEmail, $systemScanEmailPassword);

		$mailbox = $connection->getMailbox('INBOX');

 		$appOperation = AppOperation::first();
		$lastHandledMail = $appOperation->last_scanned_mail_id;
		$messageCnt = $mailbox->count();
		
		for($i=$lastHandledMail+1; $i<=$messageCnt; $i++)
		{
			try
			{
				$message = $mailbox->getMessage($i);
				//if($message != NULL)
				{
				
					$subject = $message->getSubject();
					$from = $message->getFrom()->getAddress();
					$content = $message->getBodyHtml();
					if(!isset($content) || $content == "")
						$content = $message->getBodyText();
						
					$attachments = $message->getAttachments();
					
					if(isset($content) && $content != "")
					{ 
						// $allowedTagArr = ['p', 'ul', 'ol', 'li', 'b', 'i', 'u'];
						// $allowedTagStr = "";
						// foreach ($allowedTagArr as $allowedIndTag) {
						// 	$allowedTagStr .= '<'.$allowedIndTag.'>';
						// }

						// $content = trim($content);
						// $content = strip_tags($content, $allowedTagStr); 
					}
					
					if(isset($content) && $content != "")
					{
						$user = Appuser::ofEmail($from)->active()->first();

						$hasOrgSpecified = FALSE;
						$subjectOrgId = NULL;
						$consOrgSubject = NULL;
				    	$organization = Organization::byCode($subject)->active()->first();
				    	if(isset($organization))
				    	{
				    		$hasOrgSpecified = TRUE;
					    	$subjectOrgId = $organization->organization_id;
					    	$consOrgSubject = $subject;
				    	}
				    	
				    	$addContentToRetailProfile = FALSE;
			    		$hasRetailUser = FALSE;
			            if(isset($user))
			            {
			            	$hasRetailUser = TRUE;
			            	$addContentToRetailProfile = TRUE;
			            }
			            
			       		// if((!$hasOrgSpecified && !$hasRetailUser) || ($hasOrgSpecified))
			            {
			            	$alreadyUsedOrgIdArr = array();

	            			$orgUsers = OrganizationUser::ofEmpEmail($from)->verified();
	            			if($hasOrgSpecified)
	            			{
	            				$orgUsers = $orgUsers->ofOrganization($subjectOrgId);
	            			}
	            			$orgUsers = $orgUsers->get();
	            			if(isset($orgUsers) && count($orgUsers) > 0)
	            			{			            		
	            				foreach ($orgUsers as $orgUser) 
	            				{
			            			if(isset($orgUser->organization) && $orgUser->organization->is_active == 1)
			            			{
			            				if($hasOrgSpecified)
			            				{
			            					$addContentToRetailProfile = FALSE;
			            				}

    									$orgId = $orgUser->organization_id;

    									if(!in_array($orgId, $alreadyUsedOrgIdArr))
    									{
    										$orgEmpId = $orgUser->emp_id;

								            $depMgmtObj = New ContentDependencyManagementClass;
							                $depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);

							                $orgEmployee = $depMgmtObj->getPlainEmployeeObject();
							                if(isset($orgEmployee) && $orgEmployee->is_active == 1)
							                {
							                	$consOrgAppuser = $depMgmtObj->getUserObject();
							                	$orgCode = $orgUser->organization->org_code;

												// Log::info('addContentDetails via orgEmployee orgCode: '.$orgCode);

			            						$this->addContentDetails($consOrgAppuser, $content, $subject, $from, $orgCode, $attachments);
    											
    											array_push($alreadyUsedOrgIdArr, $orgId);
							                }
    									}			    									
			            			}
	            				}
	            			}
			            }

			            if($addContentToRetailProfile && $hasRetailUser)
			            {
							// Log::info('addContentDetails via addContentToRetailProfile && hasRetailUser: ');
							$this->addContentDetails($user, $content, $subject, $from, $consOrgSubject, $attachments);
			            }
					}

				}
			}
			catch(Exception $e)
			{
				Log::info('Error saveContentDetails : $i : '.$i.' : message  : '.$e->getMessage());
			}	
			finally 
			{
				$message->markAsSeen();
				
	        	$appOperation->last_scanned_mail_id = $i;
	        	$appOperation->save();
			}	   
		}
		
		/*$spamMailbox = $connection->getMailbox('SPAM');

		$spamMessageCnt = $spamMailbox->count();
		$lastHandledSpamMail = $appOperation->last_scanned_spam_mail_id;
		
		for($i=$lastHandledSpamMail+1; $i<=$spamMessageCnt; $i++)
		{
			$message = $spamMailbox->getMessage($i);
		    $subject = $message->getSubject();
			$from = $message->getFrom();
			$content = $message->getBodyHtml();

			$user = Appuser::ofEmailAppRegistered($from)->active()->first();

            if(isset($user))
            {
            	$userId = $user->appuser_id;            	
            	$this->addContentDetails($userId, $content);
            
            }
            else
            {
            	$user = Appuser::OfEmail($from)->active()->first();

	            if(isset($user))
	            {
            		$userId = $user->appuser_id;
            		$this->addContentDetails($userId, $content);
	            }
            }
        	$appOperation->last_scanned_spam_mail_id = $i;
        	$appOperation->save();
		}*/
    }

    function addContentDetails($user, $content, $contentTitle, $sharedByUserEmail, $subject, $attachments = NULL)
    {	
    	$noOrgAttached = TRUE;            	
    	$organization = NULL;
    	if(isset($subject) && $subject != "")
    	{
    		$organization = Organization::byCode($subject)->active()->first();
    	}

    	if(isset($organization))
    	{
	    	$orgId = $organization->organization_id;
			$orgUsers = OrganizationUser::ofOrganization($orgId)->ofUserEmail($sharedByUserEmail)->verified()->get();

			// Log::info('addContentDetails orgId : '.$orgId);

			if(!isset($orgUsers) || count($orgUsers) == 0)
			{
				$orgUsers = OrganizationUser::ofOrganization($orgId)->ofEmpEmail($sharedByUserEmail)->verified()->get();
			}
			
			if(isset($orgUsers) && count($orgUsers) > 0)
			{
				foreach($orgUsers as $orgUser)
				{
					$orgEmpId = $orgUser->emp_id;
					
					// Log::info('addContentDetails orgEmpId : '.$orgEmpId);
							
		            $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgIdAndEmpId($orgId, $orgEmpId);   
		            $orgEmpConstant = $depMgmtObj->getEmployeeConstantObject();  	
		            $orgEmployee = $depMgmtObj->getPlainEmployeeObject();  	
					if(isset($orgEmpConstant) && isset($orgEmployee) && $orgEmployee->is_active == 1)
			    	{
						$noOrgAttached = FALSE;
						
			    		$defFolderId = $orgEmpConstant->def_folder_id;
			    		$emailSourceId = $orgEmpConstant->email_source_id;

						$utcTz =  'UTC';
			    		$createDateObj = Carbon::now($utcTz);
			    		$createTimeStamp = $createDateObj->timestamp;		    		
			    		$createTimeStamp = $createTimeStamp * 1000;
			    		$updateTimeStamp = $createTimeStamp;

			            $colorCode = Config::get('app_config.default_content_color_code');
			            $isLocked = Config::get('app_config.default_content_lock_status');
			            $isShareEnabled = Config::get('app_config.default_content_share_status');
			            $contentType = Config::get('app_config.content_type_a');
			           	$folderId = $defFolderId;
			            $sourceId = $emailSourceId;
			            $tagsArr = array();
			            $removeAttachmentIdArr = NULL;
			            $fromTimeStamp = "";
			            $toTimeStamp = "";
			            $isMarked = 0;
			            $remindBeforeMillis = 0;
			            $repeatDuration = 0;
			            $isCompleted = Config::get('app_config.default_content_is_completed_status');
			            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
			            $reminderTimestamp = NULL;
			            
	                	$response = $depMgmtObj->addEditContent(0, $content, $contentTitle, $contentType, $folderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, NULL);	                	
	                	$newServerContentId = $response['syncId'];


						// Log::info('addContentDetails newServerContentId : '.$newServerContentId);
	                	
	                	if(isset($attachments) && count($attachments) > 0) {
							foreach ($attachments as $attachment) {
							    // $attachment is instance of \Ddeboer\Imap\Message\Attachment
							    $fileName = $attachment->getFilename();
							    $serverFileDetails = FileUploadClass::uploadAttachmentContent($fileName, $attachment->getDecodedContent(), $orgId);
			                    $serverFileName = $serverFileDetails['name'];
			                    $serverFileSize = $serverFileDetails['size'];
			                    
								// Log::info('addContentDetails attachment : fileName : '.$fileName.' : serverFileName : '.$serverFileName.' : serverFileSize : '.$serverFileSize);

			                    if($serverFileName != "" && $serverFileSize > 0)
	                    		{
	                    			$attId = 0;
	                    			$attResponse = $depMgmtObj->addEditContentAttachment($attId, $newServerContentId, $fileName, $serverFileName, $serverFileSize);
								}
							}
						}
		                
		                $this->sendOrgEntryAddMessageToDevice($orgEmpId, $orgId, $newServerContentId, NULL);
		                
						MailClass::sendOrgContentAddedMail($orgId, $orgEmpId, $newServerContentId, NULL);
			    	}
			
				}
			}
		}

		if($noOrgAttached)
		{ 
			$orgId = 0;
			$userId = $user->appuser_id;
							
			// Log::info('addContentDetails userId : '.$userId);

            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgKey($user, "");   
            $appuserConstant = $depMgmtObj->getUserConstantObject();

	    	if(isset($appuserConstant))
	    	{
	    		$defFolderId = $appuserConstant->def_folder_id;
	    		$emailSourceId = $appuserConstant->email_source_id;

				$utcTz =  'UTC';
	    		$createDateObj = Carbon::now($utcTz);
	    		$createTimeStamp = $createDateObj->timestamp;		    		
	    		$createTimeStamp = $createTimeStamp * 1000;
			    $updateTimeStamp = $createTimeStamp;

			    $colorCode = Config::get('app_config.default_content_color_code');
			    $isLocked = Config::get('app_config.default_content_lock_status');
	            $isShareEnabled = Config::get('app_config.default_content_share_status');
	            $contentType = Config::get('app_config.content_type_a');
	           	$folderId = $defFolderId;
	            $sourceId = $emailSourceId;
	            $tagsArr = array();
	            $removeAttachmentIdArr = NULL;
	            $fromTimeStamp = "";
	            $toTimeStamp = "";
	            $isMarked = 0;
	            $remindBeforeMillis = 0;
	            $repeatDuration = 0;
	            $isCompleted = Config::get('app_config.default_content_is_completed_status');
	            $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');
	            $reminderTimestamp = NULL;

	        	$response = $depMgmtObj->addEditContent(0, $content, $contentTitle, $contentType, $folderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr, NULL);
	        	
	        	$newServerContentId = $response['syncId'];
	                	
				// Log::info('addContentDetails newServerContentId : '.$newServerContentId);

            	if(isset($attachments) && count($attachments) > 0) {
					foreach ($attachments as $attachment) {
					    $fileName = $attachment->getFilename();
					    $serverFileDetails = FileUploadClass::uploadAttachmentContent($fileName, $attachment->getDecodedContent(), $orgId);
	                    $serverFileName = $serverFileDetails['name'];
	                    $serverFileSize = $serverFileDetails['size'];
	                    
	                    if($serverFileName != "" && $serverFileSize > 0)
                		{
                			$attId = 0;
                			$attResponse = $depMgmtObj->addEditContentAttachment($attId, $newServerContentId, $fileName, $serverFileName, $serverFileSize);
						}
					}
				}
	        	
	            $this->sendEntryAddMessageToDevice($userId, $newServerContentId, NULL);
				MailClass::sendContentAddedMail($userId, $newServerContentId);
	    	}
		}
    }
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function sendReminderMailAndNotification()
    {
    	// Log::info(' ---------------- sendReminderMailAndNotification ------------------- ');
    	set_time_limit(0);

		$utcTz =  'UTC';
		$beforeAfterMinuteForUtc = 5;//10;
		
		$hoursBefore =  Config::get('app_config_mail.hour_diff_for_reminder_mail');
		$minsBefore = $hoursBefore * 60;
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->addMinutes($beforeAfterMinuteForUtc);
		
		$maxDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxDt = $maxDt->addMinutes($beforeAfterMinuteForUtc*2);

		// print_r('min : ');
		// print_r($minDt);
		// print_r('max : ');
		// print_r($maxDt);
		
		$minTs = $minDt->timestamp;
		$maxTs = $maxDt->timestamp;
		
		$minTs = $minTs*1000;
		$maxTs = $maxTs*1000;
		
		// print_r('minTs : ');
		// print_r($minTs);
		// print_r('maxTs : ');
		// print_r($maxTs);

        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        $typeRCFil = [ $typeR, $typeC ];
		
		$orgId = 0;		
		if(OrganizationClass::canSendReminderMail($orgId))
		{
			$modelObjContent = New AppuserContent;
			$tableNameContent = $modelObjContent->table;

			$modelObjAdditionalData = New AppuserContentAdditionalData;
			$tableNameAdditionalData = $modelObjAdditionalData->table;

			$isFolder = TRUE;

			$allUserContents = AppuserContentAdditionalData::join($tableNameContent, $tableNameContent.'.appuser_content_id', '=', $tableNameAdditionalData.'.usr_content_id')
									->whereIn($tableNameContent.'.content_type_id', $typeRCFil)
									->where($tableNameContent.'.is_removed', 0)
									->where('is_folder', '=', 1)
									->whereNotNull('notif_reminder_ts')
									->whereNotIn('notif_reminder_ts',array("",0))
									->where('notif_reminder_ts', '>=', $minTs)
									->where('notif_reminder_ts', '<', $maxTs);
			
			$allUserContents = $allUserContents->get();

			foreach($allUserContents as $userContent)
			{
				$userId = $userContent->appuser_id;		

				if(OrganizationClass::isPremiumUser($userId))
				{
			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgKey($userContent, "");	

					$this->fireReminderMail($orgId, $userId, $userContent, $depMgmtObj);

					$this->fireReminderNotification($orgId, $userId, $userContent, $depMgmtObj);

					$nextReminderTs = $depMgmtObj->calculateContentReminderNotificationTimeStamp($userContent, 1, $userContent->notif_reminder_ts);

					if(isset($nextReminderTs) && $nextReminderTs > 0)
					{
						$consAdditionalDataArr = array();
						$consAdditionalDataArr['notif_reminder_ts'] = $nextReminderTs;

						$depMgmtObj->checkAndSetupContentForAdditionalDataMapping($isFolder, $userContent->folder_id, $userContent->appuser_content_id, $userContent, $consAdditionalDataArr);
					}
				}
			}
		}		
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;
			
			if(OrganizationClass::canSendReminderMail($orgId))
			{
				$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

				$isFolder = TRUE;
				
				$modelObjContent = New OrgEmployeeContent;
				$tableNameContent = $modelObjContent->table;
				$modelObjContent->setConnection($orgDbConName);
				
				$modelObjAdditionalData = New OrgEmployeeContentAdditionalData;
				$tableNameAdditionalData = $modelObjAdditionalData->table;
				$modelObjAdditionalData->setConnection($orgDbConName);

				$allUserContents = $modelObjAdditionalData->join($tableNameContent, $tableNameContent.'.employee_content_id', '=', $tableNameAdditionalData.'.usr_content_id')
										->whereIn($tableNameContent.'.content_type_id', $typeRCFil)
										->where($tableNameContent.'.is_removed', 0)
										->where('is_folder', '=', 1)
										->whereNotNull('notif_reminder_ts')
										->whereNotIn('notif_reminder_ts',array("",0))
										->where('notif_reminder_ts', '>=', $minTs)
										->where('notif_reminder_ts', '<', $maxTs);
												
				$allUserContentsSql = $allUserContents->toSql();
			
				$allUserContents = $allUserContents->get();
										
				foreach($allUserContents as $userContent)
				{	
					$empId = $userContent->employee_id;	

			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

					$this->fireReminderMail($orgId, $empId, $userContent, $depMgmtObj);

					$this->fireReminderNotification($orgId, $empId, $userContent, $depMgmtObj);

					$nextReminderTs = $depMgmtObj->calculateContentReminderNotificationTimeStamp($userContent, 1, $userContent->notif_reminder_ts);

					if(isset($nextReminderTs) && $nextReminderTs > 0)
					{
						$consAdditionalDataArr = array();
						$consAdditionalDataArr['notif_reminder_ts'] = $nextReminderTs;

						$depMgmtObj->checkAndSetupContentForAdditionalDataMapping($isFolder, $userContent->folder_id, $userContent->employee_content_id, $userContent, $consAdditionalDataArr);
					}
				}
			}
		}
    } 
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function sendReminderMail()
    {
    	set_time_limit(0);
    	
    	/*$data = array();
    	$data['func'] = 'sendReminderMail';
    	$data['startTime'] = Carbon::now();
    	$mailSubject = "Send Reminder Accessed";
    	
		Mail::send('email.notification', $data, function($message) use ($mailSubject)
		{
		    $message->to("chirayu.dalwadi@gmail.com", "Chirayu")->subject($mailSubject);
		});*/

		$utcTz =  'UTC';
		$beforeAfterMinuteForUtc = 5;//10;
		
		$hoursBefore =  Config::get('app_config_mail.hour_diff_for_reminder_mail');
		$minsBefore = $hoursBefore * 60;
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->addMinutes($beforeAfterMinuteForUtc);
		
		$maxDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxDt = $maxDt->addMinutes($beforeAfterMinuteForUtc*2);
		
		$minTs = $minDt->timestamp;
		$maxTs = $maxDt->timestamp;
		
		// $minTs = $minTs*1000;
		// $maxTs = $maxTs*1000;
		
		// echo '<br/>minDt '.$minTs;
		// echo '<br/>maxDt '.$maxTs;

        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        $typeRCFil = [ $typeR, $typeC ];
		
		$orgId = 0;		
		if(OrganizationClass::canSendReminderMail($orgId))
		{
			$allUserContents = AppuserContent::select('appuser_id', 'content_type_id', 'content','content_title', 'appuser_content_id', 'from_timestamp', 'to_timestamp')
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->whereIn('content_type_id', $typeRCFil)
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd
			
			$allUserContents = $allUserContents->get();

			foreach($allUserContents as $userContent)
			{
				$userId = $userContent->appuser_id;				
				if(OrganizationClass::isPremiumUser($userId))
				{
			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgKey($userContent, "");	

					$this->fireReminderMail($orgId, $userId, $userContent, $depMgmtObj);
				}
			}
		}		
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;
			
			if(OrganizationClass::canSendReminderMail($orgId))
			{
				$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				
				$conModelObj = New OrgEmployeeContent;
				$conModelObj->setConnection($orgDbConName);
				$allUserContents = $conModelObj->select(['employee_id', 'content_type_id', 'content', 'content_title', 'employee_content_id', 'from_timestamp', 'to_timestamp'])
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->whereIn('content_type_id', $typeRCFil)
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd;
												
				$allUserContentsSql = $allUserContents->toSql();
			
				$allUserContents = $allUserContents->get();
										
				foreach($allUserContents as $userContent)
				{	
					$empId = $userContent->employee_id;	

			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

					$this->fireReminderMail($orgId, $empId, $userContent, $depMgmtObj);
				}
			}
		}
		
    }   
    
    public function fireReminderMail($orgId, $userOrEmpId, $userContent, $depMgmtObj)
    {    	
    	if($orgId == 0)	
			$userId = $userOrEmpId;
		else
			$userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $userOrEmpId);
		
		$fromTimestamp = floor($userContent->from_timestamp/1000);	
		$encContent = $userContent->content;
		$contentText = Crypt::decrypt($encContent);

        $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);
        $contentSenderStr = '';
        $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($strippedContentText);
        $contentConversationDetails = $contentConversationResponse['conversation'];
        if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
        {
            $strippedContentText = $contentConversationDetails[0]['content'];
            $contentSenderStr = $contentConversationDetails[0]['sender'];
        }
        
        $userConstants = AppuserConstant::ofUser($userId)->first();
		
		$offsetIsNegative = $userConstants->utc_offset_is_negative;
		$offsetHours = $userConstants->utc_offset_hour;
		$offsetMinutes = $userConstants->utc_offset_minute;
		
		$fromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
		
		if($offsetIsNegative == 1)
		{	
			$fromDt = $fromDt->subHours($offsetHours);
			$fromDt = $fromDt->subMinutes($offsetMinutes);		
		}
		else
		{	
			$fromDt = $fromDt->addHours($offsetHours);
			$fromDt = $fromDt->addMinutes($offsetMinutes);				
		}
		
		$remDate = $fromDt->toFormattedDateString();
		$remTime = $fromDt->toTimeString();
		
		MailClass::sendReminderMail($userId, $orgId, $userOrEmpId, $remDate, $remTime, $strippedContentText, $userContent, $contentSenderStr);

		$this->sendOrgReminderOrCalendarEventApproachingMessageToDevice($userId, $orgId, $userOrEmpId, $remDate, $remTime, $strippedContentText, $userContent, $contentSenderStr);
	}
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function sendReminderWebNotifications()
    {
    	set_time_limit(0);
    	
    	/*$data = array();
    	$data['func'] = 'sendReminderMail';
    	$data['startTime'] = Carbon::now();
    	$mailSubject = "Send Reminder Accessed";
    	
		Mail::send('email.notification', $data, function($message) use ($mailSubject)
		{
		    $message->to("chirayu.dalwadi@gmail.com", "Chirayu")->subject($mailSubject);
		});*/

		$utcTz =  'UTC';
		$beforeAfterMinuteForUtc = 5;//10;
		
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->addMinutes($beforeAfterMinuteForUtc);
		
		$maxDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxDt = $maxDt->addMinutes($beforeAfterMinuteForUtc*2);
		
		$minTs = $minDt->timestamp;
		$maxTs = $maxDt->timestamp;
		
		// $minTs = $minTs*1000;
		// $maxTs = $maxTs*1000;
		
		// echo '<br/>minDt '.$minTs;
		// echo '<br/>maxDt '.$maxTs;

        $typeR = Config::get("app_config.content_type_r");
        $typeC = Config::get("app_config.content_type_c");
        $typeRCFil = [ $typeR, $typeC ];
		
		$orgId = 0;		
		$empId = 0;
		// if(OrganizationClass::canSendReminderMail($orgId))
		{
    		// Log::info('sendReminderWebNotifications : orgId : '.$orgId.' : empId : '.$empId);
			$allUserContents = AppuserContent::select('appuser_id', 'content_type_id', 'content', 'content_title', 'appuser_content_id', 'from_timestamp', 'to_timestamp', 'repeat_duration')
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->whereIn('content_type_id', $typeRCFil)
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd
			
			$allUserContents = $allUserContents->get();

			foreach($allUserContents as $userContent)
			{
				$userId = $userContent->appuser_id;				
				if(OrganizationClass::isPremiumUser($userId))
				{
    				// Log::info('sendReminderWebNotifications : userId : '.$userId);

			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgKey($userContent, "");	

					$this->fireReminderNotification($orgId, $userId, $userContent, $depMgmtObj);
				}
			}
		}		
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;

    		// Log::info('sendReminderWebNotifications : orgId : '.$orgId);
			
			// if(OrganizationClass::canSendReminderMail($orgId))
			{
				$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				
				$conModelObj = New OrgEmployeeContent;
				$conModelObj->setConnection($orgDbConName);
				$allUserContents = $conModelObj->select(['employee_id', 'content_type_id', 'content','content_title', 'employee_content_id', 'from_timestamp', 'to_timestamp'])
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->whereIn('content_type_id', $typeRCFil)
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd;
												
				$allUserContentsSql = $allUserContents->toSql();
			
				$allUserContents = $allUserContents->get();
										
				foreach($allUserContents as $userContent)
				{	
					$empId = $userContent->employee_id;	
    				// Log::info('sendReminderWebNotifications : empId : '.$empId);

			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

					$this->fireReminderNotification($orgId, $empId, $userContent, $depMgmtObj);
				}
			}
		}
		
    }  
    
    public function fireReminderNotification($orgId, $userOrEmpId, $userContent, $depMgmtObj)
    {    	
    	if($orgId == 0)	
			$userId = $userOrEmpId;
		else
			$userId = OrganizationClass::getUserIdFromOrgEmployee($orgId, $userOrEmpId);
		
		$fromTimestamp = floor($userContent->from_timestamp/1000);
		$encContent = $userContent->content;
		$contentText = Crypt::decrypt($encContent);

        $strippedContentText = $depMgmtObj->getStrippedContentText($contentText);
        $contentSenderStr = '';
        $contentConversationResponse = $depMgmtObj->getConversationArrayFromSharedContentText($strippedContentText);
        $contentConversationDetails = $contentConversationResponse['conversation'];
        if(isset($contentConversationDetails) && count($contentConversationDetails) > 0)
        {
            $strippedContentText = $contentConversationDetails[0]['content'];
            $contentSenderStr = $contentConversationDetails[0]['sender'];
        }
        
        $userConstants = AppuserConstant::ofUser($userId)->first();
		
		$offsetIsNegative = $userConstants->utc_offset_is_negative;
		$offsetHours = $userConstants->utc_offset_hour;
		$offsetMinutes = $userConstants->utc_offset_minute;
		
		$fromDt = Carbon::createFromTimeStampUTC($fromTimestamp);
		
		if($offsetIsNegative == 1)
		{	
			$fromDt = $fromDt->subHours($offsetHours);
			$fromDt = $fromDt->subMinutes($offsetMinutes);		
		}
		else
		{	
			$fromDt = $fromDt->addHours($offsetHours);
			$fromDt = $fromDt->addMinutes($offsetMinutes);				
		}
		
		$remDate = $fromDt->toFormattedDateString();
		$remTime = $fromDt->toTimeString();

    	// Log::info('fireReminderNotification : remDate : '.$remDate.' : remTime : '.$remTime);

		$this->sendOrgReminderOrCalendarEventApproachingMessageToDevice($userId, $orgId, $userOrEmpId, $remDate, $remTime, $strippedContentText, $userContent, $contentSenderStr);
	} 
    
    public function sendReminderMailTest()
    {
    	// print_r('sendReminderMail' . '<br/>');
    	set_time_limit(0);
    	
    	/*$data = array();
    	$data['func'] = 'sendReminderMail';
    	$data['startTime'] = Carbon::now();
    	$mailSubject = "Send Reminder Accessed";
    	
		Mail::send('email.notification', $data, function($message) use ($mailSubject)
		{
		    $message->to("chirayu.dalwadi@gmail.com", "Chirayu")->subject($mailSubject);
		});*/

		$utcTz =  'UTC';
		$beforeAfterMinuteForUtc = 5;//10;
		
		$hoursBefore =  Config::get('app_config_mail.hour_diff_for_reminder_mail');
		$minsBefore = $hoursBefore * 60;
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->addMinutes($beforeAfterMinuteForUtc);
		
		$maxDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxDt = $maxDt->addMinutes($beforeAfterMinuteForUtc*2);
		
		$minTs = $minDt->timestamp;
		$maxTs = $maxDt->timestamp;
		
		/*$minTs = $minTs*1000;
		$maxTs = $maxTs*1000;
		
		echo '<br/>minDt '.$minTs;
		echo '<br/>maxDt '.$maxTs;*/
		
		$orgId = 0;		
		if(OrganizationClass::canSendReminderMail($orgId))
		{
			$allUserContents = AppuserContent::select('appuser_id','content', 'content_title','appuser_content_id', 'from_timestamp', 'to_timestamp')
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd
			
			$allUserContents = $allUserContents->get();
									
			foreach($allUserContents as $userContent)
			{
				$userId = $userContent->appuser_id;
				if(OrganizationClass::isPremiumUser($userId))
				{
			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgKey($userContent, "");	

					$this->fireReminderMail($orgId, $userId, $userContent, $depMgmtObj);
				}
			}
		}		
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;
			
			if(OrganizationClass::canSendReminderMail($orgId))
			{
				$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				
				$conModelObj = New OrgEmployeeContent;
				$conModelObj->setConnection($orgDbConName);
				$allUserContents = $conModelObj->select(['employee_id', 'content', 'content_title', 'employee_content_id', 'from_timestamp', 'to_timestamp'])
									->whereNotNull('from_timestamp')
									->whereNotIn('from_timestamp',array("",0))
									->having('fts', '>=', $minTs)
									->having('fts', '<', $maxTs)
									->addSelect(DB::raw('FLOOR(from_timestamp/1000) as fts'));//Rem 1000 if not reqd;
												
				$allUserContentsSql = $allUserContents->toSql();
			
				$allUserContents = $allUserContents->get();
										
				foreach($allUserContents as $userContent)
				{	
					$empId = $userContent->employee_id;
			        $depMgmtObj = New ContentDependencyManagementClass;
			        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

					$this->fireReminderMail($orgId, $empId, $userContent, $depMgmtObj);
				}
			}
		}
		
    }
    
    /**
     * Send Inactivity Mail.
     *
     * @return json array
     */
    public function sendInactivityReminderMail()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		$weekDiff = 1;
		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(0)->minute(0)->second(0);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->subWeeks($weekDiff);
		
		$minDtStr = $minDt->toDateTimeString();
		
		$allInactiveUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
								->whereNotNull('last_sync_ts')
								->whereNotIn('last_sync_ts', array("","0000-00-00 00:00:00"))
								->whereDate('last_sync_ts', '<=', $minDtStr)
								->where('is_verified','=','1')
								->where('inact_rem_mail_unsub','<>','1')
								->get();				
			
		if(count($allInactiveUsers) > 0)
		{
			foreach($allInactiveUsers as $user)
			{
				$userId = $user->appuser_id;
				//if($userId == 5)
				{
					MailClass::sendInactivityMail($userId);
				}
			}
			
			MailClass::sendInactiveUserlistMail($allInactiveUsers);
		}
    }
    
    /**
     * Send Inactivity Mail.
     *
     * @return json array
     */
    public function sendBirthdayReminderMail()
    {
    	//DB::enableQueryLog();
    	set_time_limit(0);

		$utcTz =  'UTC';		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(0)->minute(0)->second(0);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);		
		$minDtStr = $minDt->toDateString();
		
		$minDtStr = substr($minDtStr, 4);
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;
			
			if(OrganizationClass::canSendBirthdayMail($orgId))
			{
				$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
					
				$empModelObj = New OrgEmployee;
				$empModelObj->setConnection($orgDbConName);				
				$empModelObj = $empModelObj->select(['employee_id', 'employee_no', 'employee_name', 'email', 'contact', DB::raw("substr(dob, 5) as dobStr"), "dob as birthdt"]);
				$empModelObj = $empModelObj->whereNotNull('dob');
				$empModelObj = $empModelObj->whereNotIn('dob', array("", "0000-00-00", "1970-01-01"));
				$empModelObj = $empModelObj->havingRaw("dobStr = '$minDtStr'");
				$empModelObj = $empModelObj->where('is_verified','=','1');
				$empModelObj = $empModelObj->where('is_active','=','1');
				$empModelObj = $empModelObj->groupBy('employee_id');
							
				$birthdayUsers = $empModelObj->get();				
					
				if(isset($birthdayUsers) && count($birthdayUsers) > 0)
				{	
					MailClass::sendBirthdayUserlistMail($orgId, $birthdayUsers);
				}	
			}
						
		}	
		//dd(DB::getQueryLog());	
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function sendVerificationPendingMail()
    {
    	set_time_limit(0);

		//$utcTz =  'UTC';
		//$weekDiff = 1;
		
		//$utcToday = Carbon::now($utcTz);	
		//$utcToday->hour(0)->minute(0)->second(0);
		
		//$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		//$minDt = $minDt->subWeeks($weekDiff);
		
		//$minDtStr = $minDt->toDateTimeString();
		//->whereDate('created_at', '<=', $minDtStr)
		
		$allNonVerifiedUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
								->whereNotNull('created_at')
								->whereNotIn('created_at', array("","0000-00-00 00:00:00"))
								->where('is_verified','=','0')
								->where('ver_pend_mail_unsub','<>','1')
								->get();						
			
		if(count($allNonVerifiedUsers) > 0)
		{
			foreach($allNonVerifiedUsers as $user)
			{
				$userId = $user->appuser_id;
				/*if($userId == 1)*/
					MailClass::sendNotVerifiedReminderMail($userId);
			}
		}
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function sendQuotaExhaustWarningMail()
    {
    	set_time_limit(0);

        $appuserModelObj = New Appuser;
        $appuserTableName = $appuserModelObj->table;
        
        $orgId = 0;
        $allVerifiedUsers = Appuser::select($appuserTableName.'.appuser_id as appuser_id', 'attachment_kb_allotted', 'attachment_kb_available')
								->verified()
								->joinConstant()
								->havingRaw('(attachment_kb_available/attachment_kb_allotted)*100 <=  20')
								->get();						
			
		if(isset($allVerifiedUsers) && count($allVerifiedUsers) > 0)
		{
			foreach($allVerifiedUsers as $user)
			{
				$userId = $user->appuser_id;
				$allottedKb = $user->attachment_kb_allotted;
				$availableKb = $user->attachment_kb_available;
				//MailClass::sendProfileQuotaExhaustWarningMail($orgId, $userId, $allottedKb, $availableKb);
			}
		}
		
        $employeeModelObj = New OrgEmployee;
        $employeeTableName = $employeeModelObj->table;
		
		$organizations = Organization::active()->get();
		foreach($organizations as $org)
		{
			$orgId = $org->organization_id;
			$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				
			$empModelObj = New OrgEmployee;
			$empModelObj->setConnection($orgDbConName);	
			
			$allVerifiedEmployees = $empModelObj->select($employeeTableName.'.employee_id as employee_id', 'attachment_kb_allotted', 'attachment_kb_available')
									->verified()
									->joinConstantTable()
									->havingRaw('(attachment_kb_available/attachment_kb_allotted)*100 <=  20')
									->get();						
				
			if(isset($allVerifiedEmployees) && count($allVerifiedEmployees) > 0)
			{
				foreach($allVerifiedEmployees as $emp)
				{
					$empId = $emp->employee_id;
					$allottedKb = $emp->attachment_kb_allotted;
					$availableKb = $emp->attachment_kb_available;
					//MailClass::sendProfileQuotaExhaustWarningMail($orgId, $empId, $allottedKb, $availableKb);
				}
			}
		}
    }

	public function sendAnalyticsMail() {
		// Log::info(' ----- sendAnalyticsMail starts ----- ');
    	set_time_limit(0);

		$utcTz =  'UTC';		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(23)->minute(59)->second(59);
		$endTs = $utcToday->timestamp;
		
		$utcWeekBack = Carbon::createFromTimeStampUTC($utcToday->timestamp);	
		$utcWeekBack->subWeek();	
		$utcWeekBack->hour(0)->minute(0)->second(0);
		$startTs = $utcWeekBack->timestamp;
		
    	MailClass::sendAnalyticsMail($startTs, $endTs);
		// Log::info(' ----- sendAnalyticsMail ends ----- ');
	}
    
    /**
     * Send Inactivity Mail.
     *
     * @return json array
     */
    public function removeDeactivatedAppUserAccounts()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		$dayDiff = 7;
		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(0)->minute(0)->second(0);
		
		$minDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$minDt = $minDt->subDays($dayDiff);
		
		$minDtStr = $minDt->toDateTimeString();
		
		$allDeactivationUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
								->where('deactivated_at', '<=', $minDtStr)
								->where('is_active','=','0')
								->get();				
			
        $orgId = 0;
        $empId = 0;

		if(count($allDeactivationUsers) > 0)
		{
			foreach($allDeactivationUsers as $user)
			{
				$userId = $user->appuser_id;

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $empId);
                // $depMgmtObj->performAppuserAccountDelete();
			}
		}
    }

    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function removeTempDecryptedAttachments()
    {
    	set_time_limit(0);
	
		$orgIdArr = [0];
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;			
			array_push($orgIdArr, $orgId);
		}

		foreach($orgIdArr as $orgId)
		{
			$orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);

	        $orgAssetDirPath = $orgAssetDirPath.'/';
			$log_files = File::glob($orgAssetDirPath.'tmp_dec_*');

			// Log::info('Found log_files : orgId : '.$orgId.' : orgAssetDirPath : '.$orgAssetDirPath);
			// Log::info($log_files);

			if($log_files !== FALSE)
			{
				foreach ($log_files as $key => $log_file_name_with_path) 
				{
					$consFilePath = $log_file_name_with_path;// $orgAssetDirPath.$onlyFileName;
					File::delete($consFilePath);
				}
			}
		}
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function checkAndUpdateAppuserPremiumAccountValidity()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(0)->minute(0)->second(0);
		$utcTodayDtStr = $utcToday->toDateString();

		$currTs = CommonFunctionClass::getCreateTimestamp();
		
		$allPremiumExpiredUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
										->whereNotNull('created_at')
										->whereNotIn('created_at', array("","0000-00-00 00:00:00"))
										->where('is_premium','=','1')
										->whereNotNull('premium_expiration_date')
										->whereNotIn('premium_expiration_date', array("", "0000-00-00", "1970-01-01"))
										->whereDate('premium_expiration_date', '<', $utcTodayDtStr)
										->get();	


										// ->whereNotNull('premium_activation_date')
										// ->whereNotIn('premium_activation_date', array("", "0000-00-00", "1970-01-01"))
										// ->whereDate('premium_activation_date', '>', $minDtStr)					
			
		if(count($allPremiumExpiredUsers) > 0)
		{
			foreach($allPremiumExpiredUsers as $user)
			{
				$userId = $user->appuser_id;

	       		$userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();

				if(isset($userOrganizations) && count($userOrganizations) > 0)
				{
					$user->is_account_disabled = 0;
					$user->account_disabled_at = NULL;
				}
				else
				{
					$user->is_account_disabled = 1;
					$user->account_disabled_at = $currTs;	       			
				}

				$user->is_premium = 0;
				$user->save();
				
				MailClass::sendAppuserPremiumSubscriptionExpiredMail($userId);
			}
		}
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function setOrganizationEmployeeInactivityByDefinedPeriod()
    {
    	set_time_limit(0);
		
		$organizationsWithInactivitySet = Organization::active()->hasEmployeeInactivityDaySet()->get();
		foreach($organizationsWithInactivitySet as $org)
		{
			$orgId = $org->organization_id;
			$empInactivityDayCount = $org->employee_inactivity_day_count;

			$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

        	$modelObj = New OrgEmployee;
			$modelObj->setConnection($orgDbConName);
			
			$allRelevantEmployees = $modelObj->select('employee_id as employee_id')
											->active()
											->verified()
											->get();	

			$utcTz =  'UTC';
			
			$utcMinSyncDt = Carbon::now($utcTz);	
			$utcMinSyncDt->hour(23)->minute(59)->second(59);
			$utcMinSyncDt = $utcMinSyncDt->subDays($empInactivityDayCount);	
			$utcMinSyncTs = $utcMinSyncDt->timestamp;	

			//print_r("<br/><br/>"."For : orgId : ".$orgId." : empInactivityDayCount : ".$empInactivityDayCount." : utcMinSyncDt : ".$utcMinSyncDt." : utcMinSyncTs : ".$utcMinSyncTs."<br/>");			
				
			if(isset($allRelevantEmployees) && count($allRelevantEmployees) > 0)
			{
				foreach($allRelevantEmployees as $emp)
				{
					$empId = $emp->employee_id;
					$setAsInactive = 0;
					$mappedAppuserLastSyncDtStr = "";
					$mappedAppuserLastSyncTs = "";
					$mappedAppuserLastSyncDtObj = "";

					$mappedOrgUser = OrganizationUser::byEmpId($empId)->first();
					if(isset($mappedOrgUser))
					{
						$appuserEmail = $mappedOrgUser->appuser_email;
						$mappedAppuser = Appuser::select(['appuser_id as appuser_id', 'fullname', 'email', 'last_sync_ts'])
							        			->ofEmail($appuserEmail)
							        			->first();

						if(isset($mappedAppuser))
						{
							$mappedAppuserLastSyncDtStr = $mappedAppuser->last_sync_ts;
							if(!isset($mappedAppuserLastSyncDtStr) || $mappedAppuserLastSyncDtStr == "0000-00-00 00:00:00")// || 
							{
								// set the employee as inactive	
								$setAsInactive = 1;
							}
							else
							{
								$mappedAppuserLastSyncDtObj = Carbon::createFromFormat('Y-m-d H:i:s', $mappedAppuserLastSyncDtStr);
								$mappedAppuserLastSyncTs = $mappedAppuserLastSyncDtObj->timestamp;
								if($mappedAppuserLastSyncTs < $utcMinSyncTs)
								{
									$setAsInactive = 1;
								}

							}

							if($setAsInactive == 1)
							{
					            $emp->is_active = 0;
					            $emp->save();
							
								$forceDelete = FALSE;
								$currLoginToken = NULL;
								$this->sendOrgEmployeeRemovedToDevice($empId, $forceDelete, $currLoginToken, $orgId);
								MailClass::sendOrgEmployeeDeactivatedMail($orgId, $empId);
							}
						}
					}

					//print_r("<br/><br/>"."For : orgId : ".$orgId." : empId : ".$empId." : set as inactive : ".$setAsInactive." : mappedAppuserLastSyncTs : ".$mappedAppuserLastSyncTs." : mappedAppuserLastSyncDtObj : ".$mappedAppuserLastSyncDtObj."<br/>");
				}
			}
		}
    }
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function checkAndDeleteAppuserRemovedContent()
    {
    	set_time_limit(0);
    	
    	/*$data = array();
    	$data['func'] = 'sendReminderMail';
    	$data['startTime'] = Carbon::now();
    	$mailSubject = "Send Reminder Accessed";
    	
		Mail::send('email.notification', $data, function($message) use ($mailSubject)
		{
		    $message->to("chirayu.dalwadi@gmail.com", "Chirayu")->subject($mailSubject);
		});*/

		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$maxPersonalDeleteDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxPersonalDeleteDt = $maxPersonalDeleteDt->subDays(31);	
		$maxPersonalDeleteTs = $maxPersonalDeleteDt->timestamp;
		
		$maxEnterpDeleteDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxEnterpDeleteDt = $maxEnterpDeleteDt->subDays(121);	
		$maxEnterpDeleteTs = $maxEnterpDeleteDt->timestamp;
		
		$maxEnterpSoftDeleteDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxEnterpSoftDeleteDt = $maxEnterpSoftDeleteDt->subDays(31);	
		$maxEnterpSoftDeleteTs = $maxEnterpSoftDeleteDt->timestamp;
		
		/*$minTs = $minTs*1000;
		$maxTs = $maxTs*1000;
		
		echo '<br/>minDt '.$minTs;
		echo '<br/>maxDt '.$maxTs;*/
		
		$allUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')->get();

		$isFolder = TRUE;							

		$orgId = 0;
		$empId = 0;
		$allUserContentsForDelete = AppuserContent::select('appuser_id', 'content', 'content_title', 'appuser_content_id')
										->whereIn('is_removed', [ 1, 2 ])
										->having('rts', '<=', $maxPersonalDeleteTs)
										->addSelect(DB::raw('FLOOR(removed_at/1000) as rts'));//Rem 1000 if not reqd
		
		$allUserContentsForDelete = $allUserContentsForDelete->get();
								
		foreach($allUserContentsForDelete as $userContent)
		{
			$user = $userContent;
			$userContentId = $userContent->appuser_content_id;

	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withUserIdOrgIdAndEmpId($user, $orgId, $empId);

			$depMgmtObj->deleteContent($userContentId, $isFolder);
		}
				
		$organizations = Organization::active()->get();
		foreach($organizations as $organization)
		{
			$orgId = $organization->organization_id;

			$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
				
			$conModelObj = New OrgEmployeeContent;
			$conModelObj->setConnection($orgDbConName);
			$allEmployeeContentsForDelete = $conModelObj->select(['employee_id', 'content', 'content_title', 'employee_content_id'])
														->whereIn('is_removed', [ 1, 2 ])
														->having('rts', '<=', $maxEnterpDeleteTs)
														->addSelect(DB::raw('FLOOR(removed_at/1000) as rts'));//Rem 1000 if not reqd
		
			$allEmployeeContentsForDelete = $allEmployeeContentsForDelete->get();
									
			foreach($allEmployeeContentsForDelete as $employeeContent)
			{	
				$empId = $employeeContent->employee_id;	
				$empContentId = $employeeContent->employee_content_id;

		        $depMgmtObj = New ContentDependencyManagementClass;
		        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

				$depMgmtObj->deleteContent($empContentId, $isFolder);
			}
				
			$conModelObj = New OrgEmployeeContent;
			$conModelObj->setConnection($orgDbConName);
			$allEmployeeContentsForSoftDeletePermanently = $conModelObj->select(['employee_id', 'content', 'content_title', 'employee_content_id'])
																		->whereIn('is_removed', [ 1 ])
																		->having('rts', '<=', $maxEnterpSoftDeleteTs)
																		->addSelect(DB::raw('FLOOR(removed_at/1000) as rts'));//Rem 1000 if not reqd
		
			$allEmployeeContentsForSoftDeletePermanently = $allEmployeeContentsForSoftDeletePermanently->get();
									
			foreach($allEmployeeContentsForSoftDeletePermanently as $employeeContent)
			{	
				$empId = $employeeContent->employee_id;	
				$empContentId = $employeeContent->employee_content_id;

		        $depMgmtObj = New ContentDependencyManagementClass;
		        $depMgmtObj->withOrgIdAndEmpId($orgId, $empId);

				$depMgmtObj->softDeleteContent($empContentId, $isFolder, 1);
			}
		}
		
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function sendEnterprisePremiumSubscriptionExpiryDueMail()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$todayDtStr = $utcTodayDt->toDateString();

		$consLaterDayCountArr = [ 1, 7, 15, 30 ];

		foreach ($consLaterDayCountArr as $consLaterDayCount) 
		{
			$consDayCntLaterDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
			$consDayCntLaterDt = $consDayCntLaterDt->addDays($consLaterDayCount);	
			$consDayCntLaterDtStr = $consDayCntLaterDt->toDateString();

			$allPremiumExpiryDueUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
												->whereNotNull('created_at')
												->whereNotIn('created_at', array("","0000-00-00 00:00:00"))
												->where('is_premium','=','1')
												->whereNotNull('premium_expiration_date')
												->whereDate('premium_expiration_date', '=', $consDayCntLaterDtStr)
												->get();

			foreach ($allPremiumExpiryDueUsers as $key => $user) 
			{
				$userId = $user->appuser_id;
				MailClass::sendAppuserPremiumSubscriptionExpiryDueMail($userId, $consLaterDayCount, $consDayCntLaterDtStr);
			
			}

			$allExpiryDueOrganizationSubscriptions = OrganizationSubscription::whereNotNull('expiration_date')
														->whereDate('expiration_date', '=', $consDayCntLaterDtStr)
														->get();

			foreach ($allExpiryDueOrganizationSubscriptions as $key => $organizationSubscription) 
			{
				if(isset($organizationSubscription->organization) && $organizationSubscription->organization->is_active == 1 && $organizationSubscription->organization->is_deleted == 0)
				{
					$organizationId = $organizationSubscription->organization->organization_id;
					MailClass::sendOrganizationSubscriptionExpiryDueMail($organizationId, $consLaterDayCount, $consDayCntLaterDtStr);
				}				
			}
	 	}
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function checkAndUpdateEnterprisePremiumSubscriptionValidity()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		
		$utcToday = Carbon::now($utcTz);	
		$utcToday->hour(0)->minute(0)->second(0);
		$utcTodayDtStr = $utcToday->toDateString();
		
		$allExpiredOrganizationSubscriptions = OrganizationSubscription::whereNotNull('expiration_date')
													->whereDate('expiration_date', '<', $utcTodayDtStr)
													->get();		
						
		if(count($allExpiredOrganizationSubscriptions) > 0)
		{
			foreach($allExpiredOrganizationSubscriptions as $organizationSubscription)
			{
				if(isset($organizationSubscription->organization) && $organizationSubscription->organization->is_active == 1 && $organizationSubscription->organization->is_deleted == 0)
				{
					$organizationId = $organizationSubscription->organization->organization_id;

       				$organization = Organization::ofOrganization($organizationId)->first();
       				if(isset($organization))
       				{
                		$orgClassObj = New OrganizationClass;
                    	$orgClassObj->removeOrDeactivateOrganizationEmployees($organizationId, FALSE);

		                $organization->is_active = 0;
		                $organization->save();

						MailClass::sendOrganizationSubscriptionExpiredMail($organizationId);
       				}
				}
			}
		}
    }
    
    /**
     * Send Verification Pending Mail.
     *
     * @return json array
     */
    public function sendEnterpriseRelevantWarningMails()
    {
    	set_time_limit(0);

		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$todayDtStr = $utcTodayDt->toDateString();

		$allQuotaExhaustOrganizationSubscriptions = OrganizationSubscription::select('*', DB::raw('(allotted_quota_in_gb * 1024) - used_quota_in_mb AS available_quota_in_mb'))
														->groupBy('organization_id')
 														->having('available_quota_in_mb', '<=', 0)
														->whereDate('expiration_date', '>', $todayDtStr)
														->get();
		
		foreach ($allQuotaExhaustOrganizationSubscriptions as $key => $organizationSubscription) 
		{
			if(isset($organizationSubscription->organization) && $organizationSubscription->organization->is_active == 1 && $organizationSubscription->organization->is_deleted == 0)
			{
				$organizationId = $organizationSubscription->organization->organization_id;
				MailClass::sendOrganizationQuotaExhaustedMail($organizationId, $organizationSubscription->allotted_quota_in_gb);	
			}				
		}

		$allUserExhaustOrganizationSubscriptions = OrganizationSubscription::select('*', DB::raw('user_count - used_user_count AS available_user_count'))
														->groupBy('organization_id')
 														->having('available_user_count', '<=', 0)
														->whereDate('expiration_date', '>', $todayDtStr)
														->get();

		foreach ($allUserExhaustOrganizationSubscriptions as $key => $organizationSubscription) 
		{
			if(isset($organizationSubscription->organization) && $organizationSubscription->organization->is_active == 1 && $organizationSubscription->organization->is_deleted == 0)
			{
				$organizationId = $organizationSubscription->organization->organization_id;
				MailClass::sendOrganizationUserCountExhaustedMail($organizationId, $organizationSubscription->user_count);	
			}				
		}
    }
    
    /**
     * Send Inactivity Mail.
     *
     * @return json array
     */
    public function sendAppuserSracContactPendingHiMails()
    {
        // print_r('============================ sendAppuserSracContactPendingHiMails START ====================================='.'<br/>');
    	//DB::enableQueryLog();
    	set_time_limit(0);

		$allUsers = Appuser::select('appuser_id', 'fullname', 'email', 'contact')
								->active()
								->whereNotNull('created_at')
								->whereNotIn('created_at', array("","0000-00-00 00:00:00"))
								->get();

		foreach($allUsers as $appUser)
		{
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgKey($appUser, "");

	        // print_r('================================================================='.'<br/>');
	        // print_r('appUser : '.$appUser->appuser_id.' : fullname : '.$appUser->fullname.'<br/>');
	        // print_r('================================================================='.'<br/>');

			$depMgmtObj->sendAppuserContactHiMessageWhereverRelevant();
		}	

        // print_r('============================ sendAppuserSracContactPendingHiMails END ====================================='.'<br/>');
    }
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function checkAndRefreshAppuserCloudStorageTokens()
    {
    	set_time_limit(0);

        // print_r('============================ checkAndRefreshAppuserCloudStorageTokens START ====================================='.'<br/>');

		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$maxTokenRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxTokenRefreshTs = $maxTokenRefreshDt->timestamp * 1000;

		$isFolder = TRUE;							

		$orgId = 0;
		$empId = 0;

    	$cloudStorageTypeCodeGoogle = CloudStorageType::$GOOGLE_DRIVE_TYPE_CODE;
    	$cloudStorageTypeCodeOneDrive = CloudStorageType::$ONEDRIVE_TYPE_CODE;

		$consCloudStorageTypeCodeArr = array();
		array_push($consCloudStorageTypeCodeArr, $cloudStorageTypeCodeGoogle);
		array_push($consCloudStorageTypeCodeArr, $cloudStorageTypeCodeOneDrive);

		foreach($consCloudStorageTypeCodeArr as $cloudStorageTypeCode)
		{
	    	$cloudStorageType = CloudStorageType::byCode($cloudStorageTypeCode)->first();

	    	if(isset($cloudStorageType))
	    	{
	        	$cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;

		        // print_r('================================================================='.'<br/>');
		        // print_r('cloudStorageTypeCode : '.$cloudStorageTypeCode.' : cloudStorageTypeId : '.$cloudStorageTypeId.' : maxTokenRefreshTs : '.$maxTokenRefreshTs.'<br/>');
		        // print_r('================================================================='.'<br/>');

	    		$allTokensForRefresh = AppuserCloudStorageToken::ofCloudStorageType($cloudStorageTypeId)->isTokenRefreshDue($maxTokenRefreshTs)->get();			
				foreach($allTokensForRefresh as $tokenForRefresh)
				{
					$user = $tokenForRefresh->appuser;

					if(isset($user) && $user->is_active == 1 && $user->is_verified == 1 && $user->is_deleted == 0)
					{
						$depMgmtObj = New ContentDependencyManagementClass;
				        $depMgmtObj->withOrgKey($user, "");

				        // print_r('================================================================='.'<br/>');
				        // print_r('user : '.$user->appuser_id.' : fullname : '.$user->fullname.'<br/>');
				        // print_r('================================================================='.'<br/>');
		                
		                $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudStorageAccessToken($cloudStorageTypeCode);
		                
				        // print_r('================================================================='.'<br/>');
				        // print_r('refreshTokenResponse : '.'<br/>');
				        // print_r($refreshTokenResponse);
				        // print_r('<br/>');
				        // print_r('================================================================='.'<br/>');
					}			        
				}
	    	}	
	    }

    	$cloudCalendarTypeCodeGoogle = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
    	$cloudCalendarTypeCodeMicrosoft = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;

		$consCloudCalendarTypeCodeArr = array();
		array_push($consCloudCalendarTypeCodeArr, $cloudCalendarTypeCodeGoogle);
		array_push($consCloudCalendarTypeCodeArr, $cloudCalendarTypeCodeMicrosoft);

		foreach($consCloudCalendarTypeCodeArr as $cloudCalendarTypeCode)
		{
	    	$cloudCalendarType = CloudCalendarType::byCode($cloudCalendarTypeCode)->first();

	    	if(isset($cloudCalendarType))
	    	{
	        	$cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;

		        // print_r('================================================================='.'<br/>');
		        // print_r('cloudCalendarTypeCode : '.$cloudCalendarTypeCode.' : cloudCalendarTypeId : '.$cloudCalendarTypeId.' : maxTokenRefreshTs : '.$maxTokenRefreshTs.'<br/>');
		        // print_r('================================================================='.'<br/>');

	    		$allTokensForRefresh = AppuserCloudCalendarToken::ofCloudCalendarType($cloudCalendarTypeId)->isTokenRefreshDue($maxTokenRefreshTs)->get();			
				foreach($allTokensForRefresh as $tokenForRefresh)
				{
					$user = $tokenForRefresh->appuser;

					if(isset($user) && $user->is_active == 1 && $user->is_verified == 1 && $user->is_deleted == 0)
					{
						$depMgmtObj = New ContentDependencyManagementClass;
				        $depMgmtObj->withOrgKey($user, "");

				        // print_r('================================================================='.'<br/>');
				        // print_r('user : '.$user->appuser_id.' : fullname : '.$user->fullname.'<br/>');
				        // print_r('================================================================='.'<br/>');
		                
		                $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudCalendarAccessToken($cloudCalendarTypeCode);
		                
				        // print_r('================================================================='.'<br/>');
				        // print_r('refreshTokenResponse : '.'<br/>');
				        // print_r($refreshTokenResponse);
				        // print_r('<br/>');
				        // print_r('================================================================='.'<br/>');
					}			        
				}
	    	}	
	    }
    

    	$cloudMailBoxTypeCodeGoogle = CloudMailBoxType::$GOOGLE_MAILBOX_TYPE_CODE;
    	// $cloudMailBoxTypeCodeMicrosoft = CloudMailBoxType::$MICROSOFT_MAILBOX_TYPE_CODE;

		$consCloudMailBoxTypeCodeArr = array();
		array_push($consCloudMailBoxTypeCodeArr, $cloudMailBoxTypeCodeGoogle);
		// array_push($consCloudMailBoxTypeCodeArr, $cloudMailBoxTypeCodeMicrosoft);

		foreach($consCloudMailBoxTypeCodeArr as $cloudMailBoxTypeCode)
		{
	    	$cloudMailBoxType = CloudMailBoxType::byCode($cloudMailBoxTypeCode)->first();

	    	if(isset($cloudMailBoxType))
	    	{
	        	$cloudMailBoxTypeId = $cloudMailBoxType->cloud_mail_box_type_id;

		        // print_r('================================================================='.'<br/>');
		        // print_r('cloudMailBoxTypeCode : '.$cloudMailBoxTypeCode.' : cloudMailBoxTypeId : '.$cloudMailBoxTypeId.' : maxTokenRefreshTs : '.$maxTokenRefreshTs.'<br/>');
		        // print_r('================================================================='.'<br/>');

	    		$allTokensForRefresh = AppuserCloudMailBoxToken::ofCloudMailBoxType($cloudMailBoxTypeId)->isTokenRefreshDue($maxTokenRefreshTs)->get();			
				foreach($allTokensForRefresh as $tokenForRefresh)
				{
					$user = $tokenForRefresh->appuser;

					if(isset($user) && $user->is_active == 1 && $user->is_verified == 1 && $user->is_deleted == 0)
					{
						$depMgmtObj = New ContentDependencyManagementClass;
				        $depMgmtObj->withOrgKey($user, "");

				        // print_r('================================================================='.'<br/>');
				        // print_r('user : '.$user->appuser_id.' : fullname : '.$user->fullname.'<br/>');
				        // print_r('================================================================='.'<br/>');
		                
		                $refreshTokenResponse = $depMgmtObj->fetchAndRefreshAppuserCloudMailBoxAccessToken($cloudMailBoxTypeCode);
		                
				        // print_r('================================================================='.'<br/>');
				        // print_r('refreshTokenResponse : '.'<br/>');
				        // print_r($refreshTokenResponse);
				        // print_r('<br/>');
				        // print_r('================================================================='.'<br/>');
					}			        
				}
	    	}	
	    }
	    
        // print_r('============================ checkAndRefreshAppuserCloudStorageTokens END ====================================='.'<br/>');
    }
	
    /**
     * Send Reminer Mail.
     *
     * @return json array
     */
    public function checkAndResyncAppuserCloudCalendarAutoSyncChanges()
    {
    	set_time_limit(0);

    	$response = array();

        // Log::info('============================ checkAndResyncAppuserCloudCalendarAutoSyncChanges START =====================================');

		$utcTz =  'UTC';
		$utcToday = Carbon::now($utcTz);		
		$utcTodayDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		
		$maxTokenRefreshDt = Carbon::createFromTimeStampUTC($utcToday->timestamp);
		$maxTokenRefreshTs = $maxTokenRefreshDt->timestamp * 1000;

		$isFolder = TRUE;							

		$orgId = 0;
		$empId = 0;

    	$cloudCalendarTypeCodeGoogle = CloudCalendarType::$GOOGLE_CALENDAR_TYPE_CODE;
    	$cloudCalendarTypeCodeMicrosoft = CloudCalendarType::$MICROSOFT_CALENDAR_TYPE_CODE;

		$consCloudCalendarTypeCodeArr = array();
		array_push($consCloudCalendarTypeCodeArr, $cloudCalendarTypeCodeGoogle);
		array_push($consCloudCalendarTypeCodeArr, $cloudCalendarTypeCodeMicrosoft);

    	$response['consCloudCalendarTypeCodeArr'] = $consCloudCalendarTypeCodeArr;

		foreach($consCloudCalendarTypeCodeArr as $cloudCalendarTypeIndex => $cloudCalendarTypeCode)
		{
			$cloudCalendarType = CloudCalendarType::byCode($cloudCalendarTypeCode)->first();

	    	if(isset($cloudCalendarType))
	    	{
	        	$cloudCalendarTypeId = $cloudCalendarType->cloud_calendar_type_id;

    			$response['cloudCalendarTypeCode_'.$cloudCalendarTypeIndex] = $cloudCalendarTypeCode;

		        Log::info('=================================================================');
		        Log::info('cloudCalendarTypeCode : '.$cloudCalendarTypeCode.' : cloudCalendarTypeId : '.$cloudCalendarTypeId.' : maxTokenRefreshTs : '.$maxTokenRefreshTs.'<br/>');
		        Log::info('=================================================================');

	    		$allTokensForRefresh = AppuserCloudCalendarToken::ofCloudCalendarType($cloudCalendarTypeId)->isCalendarAutoSyncDue($maxTokenRefreshTs)->get();	
    			$response['allTokensForRefresh_'.$cloudCalendarTypeIndex] = $allTokensForRefresh;		
				foreach($allTokensForRefresh as $refreshTokenIndex => $tokenForRefresh)
				{
					$user = $tokenForRefresh->appuser;

					if(isset($user) && $user->is_active == 1 && $user->is_verified == 1 && $user->is_deleted == 0 && isset($tokenForRefresh->sync_token) && $tokenForRefresh->sync_token != "")
					{
						$depMgmtObj = New ContentDependencyManagementClass;
				        $depMgmtObj->withOrgKey($user, "");

				        Log::info('================================================================='.'<br/>');
				        Log::info('user : '.$user->appuser_id.' : fullname : '.$user->fullname.'<br/>');
				        Log::info('================================================================='.'<br/>');

    					$response['appUserID_'.$cloudCalendarTypeIndex.'_'.$refreshTokenIndex] = $user->appuser_id;		
		                
		                $syncDataResponse = $depMgmtObj->performSyncForLinkedCloudCalendarContentSetup($cloudCalendarTypeId);

    					$response['syncDataResponse_'.$cloudCalendarTypeIndex.'_'.$refreshTokenIndex] = $syncDataResponse;	
		                
				        Log::info('=================================================================');
				        Log::info('syncDataResponse : ');
				        Log::info($syncDataResponse);
				        Log::info('=================================================================');
					}			        
				}
	    	}	
		}
	    	

        // Log::info('============================ checkAndResyncAppuserCloudCalendarAutoSyncChanges END =====================================');

        return Response::json($response);
    }
}