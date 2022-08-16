<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\MlmNotification;
use App\Models\MlmNotificationAppuser;
use App\Models\RoleRight;
use App\Models\Module;
use Config;
use Response;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use View;
use App\Libraries\MailClass;
use App\Http\Traits\CloudMessagingTrait;
use Crypt;
use File;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use CloudMessagingTrait;
    		
    public $page_icon = "";
    public $page_title = "";
    public $breadcrumbArr = array();
    public $breadcrumbLinkArr = array();
    
    public $userData="";
    public $userId=0;
    public $role="";
    public $modulePermissions="";
    public $module="";
    
    public function __construct()
    {
        $userSession = Session::get('user');
        
        if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        }

        $this->page_icon = "<i class='fa fa-bullhorn'></i>";
        $this->page_title = "Notification";
        $this->page_title_link = "notification";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_notification');
        $modules = Module::where('module_name', '=', $this->module)->exists()->first();
        $rights = $modules->right()->where('role_id', '=', $this->role)->first();
        $this->modulePermissions = $rights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {    
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $pageName = $this->page_title.' List';

        $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/bootbox/bootbox.min.js", "/js/modules/common_notification.js", "/dist/bootstrap-fileinput/js/fileinput.min.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/bootstrap-fileinput/css/fileinput.min.css");      
        
        $data = array();
        $data['js'] = $js;
        $data['css'] = $css;
        $data['userdata'] = $this->userData;
        $data['pageName'] = $pageName;
        $data['page_description'] = $pageName;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('notification.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function notificationDatatable()
    {      
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }
        $orgId = 0;
	    $notifImageBaseUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($orgId, "");
	        	
        $dateFormat = Config::get('app_config.sql_date_db_format');
        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

        $notifications = MlmNotification::select(['mlm_notifications.mlm_notification_id', 'notification_text', \DB::raw("server_filename as notif_image"), \DB::raw("sent_at as sent_on"), "employee_name as sent_by", "is_draft"]);

        // \DB::raw("IF(server_filename <> '', CONCAT('$notifImageBaseUrl',server_filename), '') as notif_image")
         // \DB::raw("IF(sent_at <> NULL AND sent_at <> '', DATE_FORMAT(sent_at, '$dateTimeFormat'), '') as sent_on")
        
        $notifications->leftJoin('users', 'mlm_notifications.sent_by', '=', 'users.user_id');

        return Datatables::of($notifications)
                ->remove_column('mlm_notification_id')
                ->remove_column('is_draft')
                ->add_column('action', function($notification) {
                    return $this->getNotificationDatatableButton($notification->mlm_notification_id, $notification->is_draft);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getNotificationDatatableButton($id, $isDraft)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
        	$buttonHtml .= '&nbsp;<button onclick="viewNotificationDetails('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
            //$buttonHtml .= $id.'_'.$isDraft;
        }
        return $buttonHtml;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function show()
    {        
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('notif_id');

        if($id <= 0)
        {
            return redirect('notification');
        }

        $notification = MlmNotification::byId($id)->first();
        $notificationUsers = MlmNotificationAppuser::ofNotification($id)->get();

        $pageName = $this->page_title.' Details';

        $js = array("/dist/bootbox/bootbox.min.js", "/js/modules/common_notification.js");
        $css = array();       
        
        $data = array();
        $data['js'] = $js;
        $data['css'] = $css;
        $data['userdata'] = $this->userData;
        $data['pageName'] = $pageName;
        $data['page_description'] = $pageName;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['user'] = $user;
        $data['userConstant'] = $userConstant;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('notification.show', $data);
    }

    /**
     * Load Send Appuser Notification Modal.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function loadSendAppuserNotificationModal()
    {
        $notifId = Input::get('notifId');
        
        $notifDetails = NULL;
        if($notifId != "" && $notifId > 0)
        {
        	$notifDetails = MlmNotification::byId($notifId)->first();	
            if(isset($notifDetails))
            {
				$notifDetails->url = OrganizationClass::getOrgMlmNotificationAssetUrl(0, $notifDetails->server_filename);
			}
		}
        
    	$data = array();
    	$data["notifDetails"] = $notifDetails;
    	
    	$_viewToRender = View::make('notification.partialview._sendNotificationModal', $data);
        $_viewToRender->render();

        $response = array('status' => '1', 'view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Save Appuser Notification(s) as Draft.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function saveNotificationAsDraft()
    {
    	$status = 0;
    	$msg = "";
    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $notifId = Input::get('notification_id');
        $notifText = Input::get('notification_text');
        $notifImg = Input::file('notification_img');
        $imgChanged = Input::get('image_changed');
        $isSend = Input::get('is_send');
        $isTest = Input::get('is_test');
        $sendAsMail = Input::get('sendAsMail');

        if(!isset($sendAsMail) || is_nan($sendAsMail) || $sendAsMail * 1 != 1)
        {
            $sendAsMail = 0;
        }
        else
        {
            $sendAsMail = 1;
        }

        if($notifText != "")
        {    
        	$status = 1;
            $notifArr = $this->saveNotificationDetails($notifId, $notifText, $notifImg, $imgChanged, $sendAsMail);   
            
            $serverNotifId = $notifArr['id'];
            
            if(isset($isSend) && $isSend == 1)
            {
				$this->sendAppuserNotification($serverNotifId, $isTest, $sendAsMail);
			}         
        }
        else
        {
        	$status = -1;
			$msg = "Notification Text Is Required";
		}

        $response = array('status' => $status, 'msg' => $msg, 'notifId' => $serverNotifId);

        return Response::json($response);
    }

    /**
     * Send Appuser Notification(s).
     *
     * @param void
     *
     * @return JSONArray
     */
    public function sendAppuserNotification($notifId, $isTest, $sendAsMail)
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }   
        
        if($notifId != "" && $notifId > 0)
        {
        	$notifDetails = MlmNotification::byId($notifId)->first();	
        	
        	if(isset($notifDetails))
        	{		    	
			    $orgId = 0;
				$notifText = $notifDetails->notification_text;

	    		$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($orgId, $notifDetails->server_filename);
		        	
		        if($isTest == 1)
		        {
		        	$testEmails = Config::get('app_config.test_email_arr');
					$appUsers = Appuser::whereIn('email', $testEmails)->where('is_logged_in','=',1)->get();
				}
				else
				{
		    		$appUsers = Appuser::where('is_logged_in','=',1)->get();					
				}
				
		    	$this->sendNotification($appUsers, $isTest, $notifText, $notifImageUrl, $notifId, $sendAsMail);
			}  
		}   	
    }
    
    function sendNotification($appUsers, $isTest, $notifText, $notifImageUrl, $notifId, $sendAsMail)
    {
		foreach($appUsers as $appUser)
		{
			$userId = $appUser->appuser_id;
			
			//if($userId == 1 || $userId == 102 || $userId == 13 || $userId == 85)
			{
        		$userNotifData = array();
                $userNotifData['mlm_notification_id'] = $notifId;
                $userNotifData['appuser_id'] = $userId;	            
                $appuserNotif = MlmNotificationAppuser::create($userNotifData);
                
                if($sendAsMail == 1)
                {
                    $sendStatus = MailClass::sendNotificationMailToUser($userId, $notifText, $notifImageUrl);
                }
                else
                {
                    $sendStatus = $this->sendMessageToDevice($userId, $notifText, $notifImageUrl);
                }
        		
                $appuserNotif->status = $sendStatus;
                $appuserNotif->save();
			}
		}	
		if($isTest == 0)
        {
			$this->setNotificationSent($notifId, $sendAsMail);
		}	
	}
    
    function saveNotificationDetails($notifId, $notifText, $notifImg, $imgChanged, $sendAsMail)
    {
    	$notifArr = array();
    	$notifImgFileName = "";
        if (File::exists($notifImg) && $notifImg->isValid()) 
        {
            $notifImgFileName = FileUploadClass::uploadMlmNotifImage($notifImg);
            $imgChanged = 1;
        }
        
        if($notifId != "" && $notifId > 0)
        {
        	$notif = MlmNotification::byId($notifId)->first();	
        	if($imgChanged == 1)
        		$notif->server_filename = $notifImgFileName;		
		}   
		else
		{
			$notif = new MlmNotification();
	        $notif->created_by = $this->userId;	
        	$notif->server_filename = $notifImgFileName;		
		} 
        
        $notif->notification_text = $notifText;
        $notif->is_draft = 1;
    	$notif->is_sent = 0;
        $notif->sent_as_mail = $sendAsMail;
        $notif->save();
        
        $orgId = 0;
        $notifImageUrl = "";
        if($notifImgFileName != "")
	   		$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($orgId, $notifImgFileName);
        
        $notifArr['id'] = $notif->mlm_notification_id;
        $notifArr['url'] = $notifImageUrl;
        
        return $notifArr;
	}
	
	function setNotificationSent($notifId, $sendAsMail)
    {
        $currDt = date(Config::get('app_config.datetime_db_format'));
        
        $notif = MlmNotification::byId($notifId)->first();	
        $notif->is_draft = 0;
        $notif->is_sent = 1;
        $notif->sent_by = $this->userId;
        $notif->sent_at = $currDt;
        $notif->sent_as_mail = $sendAsMail;
        $notif->save();
    }

    /**
     * Filter Appuser list for send.
     *
     * @param void
     *
     * @return void
     */
    public function filterAppuserListForSend()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }     

        $notifId = Input::get('notif_id');
 
        if($notifId <= 0)
        {
            return redirect('notification');
        }
        
        $pageName = 'Filter Appuser List';
        
    	$js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/datatables/plugins/dataTables.buttons.min.js", "/dist/datatables/plugins/buttons.html5.min.js", "/dist/datatables/plugins/pdfmake.min.js", "/dist/datatables/plugins/vfs_fonts.js", "/dist/datatables/plugins/jszip.min.js", "/dist/bootbox/bootbox.min.js", "/dist/select2/dist/js/select2.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js", "/dist/icheck/icheck.min.js","/js/modules/common_notification.js","/js/modules/common_appuser_advanced_search.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/datatables/plugins/buttons.dataTables.min.css", "/dist/select2/dist/css/select2.min.css", "/dist/bootstrap-datepicker/dist/css/datepicker.css", "/dist/icheck/skins/all.css");   

        $regTypeList = array();     
        $regTypeList['Email'] = 'Email'; 
        $regTypeList['Facebook'] = 'Facebook'; 

        $verStatusList = array();   
        $verStatusList['1'] = 'Verified'; 
        $verStatusList['0'] = 'Pending'; 

        $genderList = array();
        $genderList['Male'] = 'Male'; 
        $genderList['Female'] = 'Female'; 

        $statusList = array();
        $statusList['1'] = 'Active'; 
        $statusList['0'] = 'Inactive';          
        
        $data = array();
        $data['js'] = $js;
        $data['css'] = $css;
        $data['userdata'] = $this->userData;
        $data['pageName'] = $pageName;
        $data['page_description'] = $pageName;
        $data['regTypeList'] = $regTypeList;
        $data['verStatusList'] = $verStatusList;
        $data['genderList'] = $genderList;
        $data['statusList'] = $statusList;
        $data['notifId'] = $notifId;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('notification.appuserList', $data);   	
    }

    /**
     * Add Selected Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function sendSelAppuserNotification()
    {    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }  
        
        $status = 0;
        $msg = "";        
        $response = array();
        
        $notifId = Input::get('notifId');
        $sendAsMail = Input::get('sendAsMail');

        if(!isset($sendAsMail) || is_nan($sendAsMail) || $sendAsMail * 1 != 1)
        {
            $sendAsMail = 0;
        }
        else
        {
            $sendAsMail = 1;
        }
        
        if($notifId != "" && $notifId > 0)
        {
        	$notifDetails = MlmNotification::byId($notifId)->first();	
        	
        	if(isset($notifDetails))
        	{
        		$status = 1;
				$notifText = $notifDetails->notification_text;
				$notifImgFileName = $notifDetails->server_filename;
			
				$orgId = 0;
			    $notifImageUrl = "";
			    if($notifImgFileName != "")
	   				$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($orgId, $notifImgFileName);
			    	
        		
        		$appuserResultset = $this->getAppuserResultSet();
        		$appUsers = $appuserResultset->get();
				
				$isTest = 0;
				$this->sendNotification($appUsers, $isTest, $notifText, $notifImageUrl, $notifId, $sendAsMail);	
        		//$response['appUsers'] = $appUsers;
        	}
        }
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function appuserDatatable()
    {      
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $appusers = $this->getAppuserResultSet();
        
        return Datatables::of($appusers)
                ->remove_column('appuser_id')
                ->remove_column('gender')
                ->remove_column('reg_type')
                ->remove_column('verification_status')
                ->remove_column('reg_token')
                ->remove_column('is_logged_in')
                ->make();
    }
    
    /**
     * Get Appuser Resultset for datatables
     *
     * @return string
     */
    private function getAppuserResultSet()
    {
        $dateFormat = Config::get('app_config.sql_date_db_format');
        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

        $regType = Input::get('regType');
        $verStatus = Input::get('verStatus');
        $gender = Input::get('gender');
        $status = Input::get('status');
        $regRangeStart = Input::get('regRangeStart');
        $regRangeEnd = Input::get('regRangeEnd');
        $syncRangeStart = Input::get('syncRangeStart');
        $syncRangeEnd = Input::get('syncRangeEnd');
        $refCode = Input::get('refCode');        

        $dtRegFrom = "";
        if($regRangeStart != "")
        {
            $dtRegFrom = date(Config::get('app_config.date_db_format'), strtotime($regRangeStart));
        }
        $dtRegTo = "";
        if($regRangeEnd != "")
        {
            $dtRegTo = date(Config::get('app_config.date_db_format'), strtotime($regRangeEnd));
        }

        $dtSyncFrom = "";
        if($syncRangeStart != "")
        {
            $dtSyncFrom = date(Config::get('app_config.date_db_format'), strtotime($syncRangeStart));
        }
        $dtSyncTo = "";
        if($syncRangeEnd != "")
        {
            $dtSyncTo = date(Config::get('app_config.date_db_format'), strtotime($syncRangeEnd));
        }

        $appusers = Appuser::select(['appusers.appuser_id', 'fullname', 'email', 'contact', 'gender', \DB::raw("IF(is_app_registered=1, 'Email', 'Facebook') as reg_type"), \DB::raw("IF(is_verified=1, 'Verified', 'Pending') as verification_status"), 'country', \DB::raw("DATE_FORMAT(appusers.created_at, '$dateFormat') as reg_date"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("(appusers.last_sync_ts) as last_synced_at"), \DB::raw("IF(ref_code='', '-', ref_code) as ref_code")]);
        
        $appusers->leftJoin('appuser_constants', 'appuser_constants.appuser_id', '=', 'appusers.appuser_id');
        $appusers->join('appuser_sessions', 'appuser_sessions.appuser_id', '=', 'appusers.appuser_id');
        $appusers->max('appusers.last_sync_ts');
           
        /*$appusers->leftjoin('appuser_sessions', function($join)
		{
			$join->on('appuser_sessions.appuser_id', '=', 'appusers.appuser_id')
			->max('last_sync_ts');
		});*/

        if(isset($regType) && $regType != "")
            $appusers->having('reg_type','=',$regType);

        if(isset($verStatus) && $verStatus != "")
            $appusers->where('is_verified','=',$verStatus);

        if(isset($status) && $status != "")
            $appusers->where('is_active','=',$status);

        if(isset($gender) && $gender != "")
            $appusers->where('gender','=',$gender);

        if(isset($refCode) && $refCode != "")
            $appusers->where('ref_code','LIKE', "%$refCode%");
            

        if(isset($dtRegFrom) && $dtRegFrom != "")
            $appusers->having('reg_date','>=',$dtRegFrom);
        if(isset($dtRegTo) && $dtRegTo != "")
            $appusers->having('reg_date','<=',$dtRegTo);

        if(isset($dtSyncFrom) && $dtSyncFrom != "")
            $appusers->having('last_synced_at','>=',$dtSyncFrom);
        if(isset($dtSyncTo) && $dtSyncTo != "")
            $appusers->having('last_synced_at','<=',$dtSyncTo);
         
         $appusers->groupBy('appusers.appuser_id');
         // $appusers->whereNotIn('appusers.last_sync_ts',['null', '', '0000-00-00 00:00:00']);
            
       // $appusers->orderBy('appuser_sessions.last_sync_ts', 'desc');
            
        //$appusers->having('last_synced_at','!=','');//->where('reg_token','<>','');
            
        return $appusers;
    }
    
    function sendTestNotification()
    {
		$userId = 654;
		$notifText = "Test OS Notif";
        $sendStatus = $this->sendOSMessageToDevice($userId, $notifText);        		
	}
}