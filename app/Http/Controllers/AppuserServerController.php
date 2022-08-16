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
use App\Models\RoleRight;
use App\Models\Module;
use Config;
use Response;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Api\DeletedAppuser;
use View;
use App\Libraries\MailClass;
use App\Http\Traits\CloudMessagingTrait;
use Crypt;
use App\Libraries\CommonFunctionClass;

class AppuserServerController extends Controller
{    		
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

        $this->page_icon = "<i class='fa fa-user'></i>";
        $this->page_title = "App User";
        $this->page_title_link = "appuser";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_appuser');
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

        $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/datatables/plugins/dataTables.buttons.min.js", "/dist/datatables/plugins/buttons.html5.min.js", "/dist/datatables/plugins/pdfmake.min.js", "/dist/datatables/plugins/vfs_fonts.js", "/dist/datatables/plugins/jszip.min.js", "/dist/bootbox/bootbox.min.js", "/dist/select2/dist/js/select2.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js", "/dist/icheck/icheck.min.js","/js/modules/common_appuser.js","/js/modules/common_appuser_advanced_search.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/datatables/plugins/buttons.dataTables.min.css", "/dist/select2/dist/css/select2.min.css", "/dist/bootstrap-datepicker/dist/css/datepicker.css", "/dist/icheck/skins/all.css"); 

        $regTypeList = array();     
        $regTypeList[Appuser::$_IS_EMAIL_REGISTERED] = 'Email'; 
        $regTypeList[Appuser::$_IS_FACEBOOK_REGISTERED] = 'Facebook'; 
        $regTypeList[Appuser::$_IS_GOOGLE_REGISTERED] = 'Google'; 
        $regTypeList[Appuser::$_IS_LINKEDIN_REGISTERED] = 'LinkedIn'; 

        $verStatusList = array();   
        $verStatusList['1'] = 'Verified'; 
        $verStatusList['0'] = 'Pending';
        
        $premiumText = Config::get('app_config.premium_active_btn_text');
        $regularText = Config::get('app_config.premium_inactive_btn_text'); 
        
        $enterpPremiumText = Config::get('app_config.enterprise_active_btn_text');
        $enterpRegularText = Config::get('app_config.enterprise_inactive_btn_text');  

        $premStatusList = array();   
        $premStatusList['0'] = $regularText; 
        $premStatusList['1'] = $premiumText; 

        $enterpStatusList = array();   
        $enterpStatusList[$enterpRegularText] = $enterpRegularText; 
        $enterpStatusList[$enterpPremiumText] = $enterpPremiumText; 

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
        $data['premStatusList'] = $premStatusList;
        $data['enterpStatusList'] = $enterpStatusList;
        $data['genderList'] = $genderList;
        $data['statusList'] = $statusList;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('appuser.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function appuserDatatable()
    {
        $appusers = $this->getAppuserResultSet();
        
        //print_r($appusers->toSql());
        
        return Datatables::of($appusers)
                ->remove_column('appuser_id')
                ->remove_column('gender')
                ->remove_column('reg_type')
                ->remove_column('verification_status')
                // ->remove_column('premium_status')
                ->remove_column('is_active')
                ->add_column('status', function($appuser) {
                    return $appuser->appuser_id."_".$appuser->is_active;
                })
                ->add_column('action', function($appuser) {
                    return $this->getAppuserDatatableButton($appuser->appuser_id);
                })
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
        $accType = Input::get('accType');
        $enterpType = Input::get('enterpType');
        $gender = Input::get('gender');
        $status = Input::get('status');
        $regRangeStart = Input::get('regRangeStart');
        $regRangeEnd = Input::get('regRangeEnd');
        $syncRangeStart = Input::get('syncRangeStart');
        $syncRangeEnd = Input::get('syncRangeEnd');
        $refCode = Input::get('refCode');
        
        $enterpPremiumText = Config::get('app_config.enterprise_active_btn_text');
        $enterpRegularText = Config::get('app_config.enterprise_inactive_btn_text');  
        
        $premiumText = Config::get('app_config.premium_active_btn_text');
        $regularText = Config::get('app_config.premium_inactive_btn_text'); 

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
        
        $premiumText = Config::get('app_config.premium_active_btn_text');
        $regularText = Config::get('app_config.premium_inactive_btn_text');

        /*$appusers = Appuser::select(['appusers.appuser_id', 'fullname', 'email', 'contact', 'gender', \DB::raw("IF(is_app_registered=1, 'Email', 'Facebook') as reg_type"), \DB::raw("IF(is_verified=1, 'Verified', 'Pending') as verification_status"), 'country', \DB::raw("DATE_FORMAT(appusers.created_at, '$dateFormat') as reg_date"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("DATE_FORMAT(appusers.last_sync_ts, '$dateFormat') as last_synced_at"), \DB::raw("IF(ref_code='', '-', ref_code) as ref_code")]);*/
        //$appusers = Appuser::select(['appusers.appuser_id', 'fullname', 'email', 'contact', 'gender', \DB::raw("IF(is_app_registered=1, 'Email', 'Facebook') as reg_type"), \DB::raw("IF(is_verified=1, 'Verified', 'Pending') as verification_status"), \DB::raw("IF(is_premium=1, '$premiumText', '$regularText') as premium_status"), 'country', \DB::raw("DATE_FORMAT(appusers.created_at, '$dateFormat') as reg_date"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("COUNT(appuser_content_id) as note_count"), \DB::raw("IF(ref_code='', '-', ref_code) as ref_code"), \DB::raw("max(last_sync_ts) as last_synced_at")]);
        $appusers = Appuser::select(['appusers.appuser_id', 'fullname', 'appusers.email', 'contact', 'gender', \DB::raw("IF(appusers.is_verified=1, 'Verified', 'Pending') as verification_status"), 'country', \DB::raw("DATE_FORMAT(appusers.created_at, '$dateFormat') as reg_date"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("IF(appusers.ref_code='', '-', appusers.ref_code) as ref_code"), \DB::raw("IF(is_premium=1, '$premiumText', '$regularText') as premium_str"), \DB::raw("IF(count(organizations.organization_id) > 0, '$enterpPremiumText', '$enterpRegularText') as enterp_str"), \DB::raw("last_sync_ts as last_synced_at"), 'appusers.is_active as is_active']);
        
        $appusers->leftJoin('appuser_constants', 'appuser_constants.appuser_id', '=', 'appusers.appuser_id');
        //$appusers->leftJoin('appuser_sessions', 'appuser_sessions.appuser_id', '=', 'appusers.appuser_id');
        
        $appusers->leftJoin('organization_users', 'appusers.email', '=', 'organization_users.appuser_email');
        $appusers->leftJoin('organizations', 'organization_users.organization_id', '=', 'organizations.organization_id');
        
        //$appusers->leftJoin('appuser_contents', 'appuser_contents.appuser_id', '=', 'appusers.appuser_id');
        /*$appusers->leftJoin('appuser_sessions', function($join){
						    $join->on('appuser_sessions.appuser_id', '=', 'appusers.appuser_id')
						         ->where('max(last_sync_ts)');
						        // ->groupBy('appuser_sessions.appuser_id');
						});*/
        
        $appusers->groupBy('appusers.appuser_id');

        if(isset($regType) && $regType != "")
            $appusers->where('is_app_registered','=',$regType);

        if(isset($verStatus) && $verStatus != "")
            $appusers->where('appusers.is_verified','=',$verStatus);

        if(isset($accType) && $accType != "")
            $appusers->where('is_premium','=',$accType);

        if(isset($enterpType) && $enterpType != "") {
            $appusers->having('enterp_str','=',$enterpType);
		}

        if(isset($status) && $status != "")
            $appusers->where('appusers.is_active','=',$status);

        if(isset($gender) && $gender != "")
            $appusers->where('gender','=',$gender);

        if(isset($refCode) && $refCode != "")
            $appusers->where('ref_code','LIKE', "%$refCode%");

        if(isset($dtRegFrom) && $dtRegFrom != "")
            $appusers->having('reg_date','>=',$dtRegFrom);
            
        if(isset($dtRegTo) && $dtRegTo != "")
            $appusers->having('reg_date','<=',$dtRegTo);

        /*if(isset($dtSyncFrom) && $dtSyncFrom != "")
            $appusers->having('last_synced_at','>=',$dtSyncFrom);
        if(isset($dtSyncTo) && $dtSyncTo != "")
            $appusers->having('last_synced_at','<=',$dtSyncTo);*/
            
        return $appusers;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getAppuserDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
            $buttonHtml .= '&nbsp;<button onclick="viewAppuser('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
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

        $id = Input::get('appuser_id');

        if($id <= 0)
        {
            return redirect('appuser');
        }

        $user = Appuser::findOrFail($id);
        $userConstant = AppuserConstant::ofUser($id)->first();

        $pageName = $this->page_title.' Details';    
        $js = array("/js/modules/common_appuser.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js");
        $css = array("/dist/bootstrap-datepicker/dist/css/datepicker.css", "/dist/bootstrap-datepicker/dist/css/common_dt_modal.css");      
               
        
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

        return view('appuser.show', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadModifyAppuserQuotaModal()
    { 
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('id');

        if($id <= 0)
        {
            return redirect('appuser');
        }

        $userConstant = AppuserConstant::findOrFail($id);
        $user = Appuser::findOrFail($userConstant->appuser_id);
        
        $data = array();
        $data['userConstant'] = $userConstant;
        $data['user'] = $user;

        $_viewToRender = View::make('appuser.partialview._modifyQuotaModal', $data);
        $_viewToRender->render();

        $response = array('status' => '1', 'view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function saveAppuserQuotaDetails()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('id');
        $updatedAllotMbs = Input::get('appuserQuota');
        $updatedAllotKbs = $updatedAllotMbs*1024;

        if($id <= 0)
        {
            return redirect('appuser');
        }

        $userConstant = AppuserConstant::findOrFail($id);
        $userId = $userConstant->appuser_id;
        $user = Appuser::findOrFail($userId);

        $currAllottedKb = $userConstant->attachment_kb_allotted;
        $currAvailableKb = $userConstant->attachment_kb_available;

        $diffAllotKbs = $updatedAllotKbs - $currAllottedKb;
        $updatedAvailableKbs = $currAvailableKb + $diffAllotKbs;

        if($updatedAllotKbs >= 0 && $updatedAvailableKbs >= 0)
        {
            $msg = "";
            $userConstant->attachment_kb_allotted = $updatedAllotKbs;
            $userConstant->attachment_kb_available = $updatedAvailableKbs;
            $userConstant->save();
            
            //Quota changed mail
            MailClass::sendQuotaChangedMail($userId, $currAllottedKb);
        }
        else
        {
            $msg = Config::get('app_config_notif.err_user_quota_not_valid');
        }

        $avlMb = ceil($userConstant->attachment_kb_available/1024)." MB";
        $allotMb = ceil($userConstant->attachment_kb_allotted/1024)." MB";

        $response = array('status' => '1', 'msg' => $msg, 'avlMb' => $avlMb, 'allotMb' => $allotMb);

        return Response::json($response);        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadModifyAppuserPremiumExpirationDateModal()
    { 
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('id');

        if($id <= 0)
        {
            return redirect('appuser');
        }

        $user = Appuser::active()->byId($id)->first();

        if(isset($user))
        {
            $user->premiumExpirationDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($user->premium_expiration_date));

            $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");           
            
            $data = array();
            $data['user'] = $user;
            $data['intJs'] = $intJs;

            $_viewToRender = View::make('appuser.partialview._modifyPremiumExpirationModal', $data);
            $_viewToRender->render();

            $response = array('status' => '1', 'view' => "$_viewToRender" );
        }
        else
        {
            $response = array('status' => '-1', 'msg' => "Invalid User" );
        }            

        return Response::json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function saveAppuserPremiumExpirationDateDetails()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $userId = Input::get('id');
        $premiumExpirationDate = Input::get('premiumExpirationDate');

        if($userId <= 0)
        {
            return redirect('appuser');
        }

        $user = Appuser::findOrFail($userId);

        if($premiumExpirationDate != "")
        {
            $msg = "";
            $user->premium_expiration_date = date(Config::get('app_config.date_db_format'), strtotime($premiumExpirationDate));
            $user->save();
            
            //Quota changed mail
            // MailClass::sendQuotaChangedMail($userId, $currAllottedKb);
        }
        else
        {
            $msg = 'Invalid Date';
        }

        $response = array('status' => '1', 'msg' => $msg, 'premiumExpirationDate' => $premiumExpirationDate);

        return Response::json($response);        
    }


    /**
     * Change the status of specified resource.
     *
     *
     * @return void
     */
    public function changeStatus()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('userId');
        $statusActive = Input::get('statusActive');

        $currTs = CommonFunctionClass::getCurrentTimestamp();  

        $user = Appuser::byId($id)->first();
        $user->is_active = $statusActive;
        $user->deactivated_at = $statusActive == 0 ? $currTs : NULL;
        $user->save();

        $msg = 'App User status changed!';

        $response = array('status' => 1, 'msg' => $msg);

        return Response::json($response);
    }

    /**
     * Change the premium status of specified resource.
     *
     *
     * @return void
     */
    public function changePremiumStatus()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $userId = Input::get('userId');
        $statusActive = Input::get('premStatusActive');

        $premiumActivationDate = NULL;
        $premiumExpirationDate = NULL;
        if($statusActive == 1)
        {
            $utcTz =  'UTC';

            $premiumActivationDate = Carbon::now($utcTz);
            $premiumActivationDate = $premiumActivationDate->toDateString();

            $premiumExpirationDate = Carbon::now($utcTz);
            $premiumExpirationDate = $premiumExpirationDate->addYear();
            $premiumExpirationDate = $premiumExpirationDate->toDateString();
        }

        $user = Appuser::byId($userId)->first();
        $user->is_premium = $statusActive;
        $user->premium_activation_date = $premiumActivationDate;
        $user->premium_expiration_date = $premiumExpirationDate;
        $user->save();
                
        if($statusActive == 1 && isset($user->userConstants))
        {
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

        Session::flash('flash_message', 'App User premium status changed!');

        return redirect('appuser');
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function deletedAppuserList()
    {    
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $pageName = 'Deleted '.$this->page_title.' List';

        $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/datatables/plugins/dataTables.buttons.min.js", "/dist/datatables/plugins/buttons.html5.min.js", "/dist/datatables/plugins/pdfmake.min.js", "/dist/datatables/plugins/vfs_fonts.js", "/dist/datatables/plugins/jszip.min.js", "/dist/bootbox/bootbox.min.js", "/dist/select2/dist/js/select2.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js", "/dist/icheck/icheck.min.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/datatables/plugins/buttons.dataTables.min.css", "/dist/select2/dist/css/select2.min.css", "/dist/bootstrap-datepicker/dist/css/datepicker.css", "/dist/icheck/skins/all.css"); 

        $regTypeList = array();     
        $regTypeList[Appuser::$_IS_EMAIL_REGISTERED] = 'Email'; 
        $regTypeList[Appuser::$_IS_FACEBOOK_REGISTERED] = 'Facebook'; 
        $regTypeList[Appuser::$_IS_GOOGLE_REGISTERED] = 'Google'; 
        $regTypeList[Appuser::$_IS_LINKEDIN_REGISTERED] = 'LinkedIn'; 

        $verStatusList = array();   
        $verStatusList['1'] = 'Verified'; 
        $verStatusList['0'] = 'Pending';
        
        $premiumText = Config::get('app_config.premium_active_btn_text');
        $regularText = Config::get('app_config.premium_inactive_btn_text'); 
        
        $enterpPremiumText = Config::get('app_config.enterprise_active_btn_text');
        $enterpRegularText = Config::get('app_config.enterprise_inactive_btn_text');  

        $premStatusList = array();   
        $premStatusList['0'] = $regularText; 
        $premStatusList['1'] = $premiumText; 

        $enterpStatusList = array();   
        $enterpStatusList[$enterpRegularText] = $enterpRegularText; 
        $enterpStatusList[$enterpPremiumText] = $enterpPremiumText; 

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
        $data['premStatusList'] = $premStatusList;
        $data['enterpStatusList'] = $enterpStatusList;
        $data['genderList'] = $genderList;
        $data['statusList'] = $statusList;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('appuser.deletedList', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function deletedAppuserDatatable()
    {
        $dateFormat = Config::get('app_config.sql_date_db_format');
        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

        $premiumText = Config::get('app_config.premium_active_btn_text');
        $regularText = Config::get('app_config.premium_inactive_btn_text');

        $appusers = DeletedAppuser::select(['appuser_id', 'fullname', 'email', 'contact', 'gender', 'city', 'country', \DB::raw("IF(is_verified=1, 'Verified', 'Pending') as verification_status"), \DB::raw("DATE_FORMAT(created_at, '$dateFormat') as del_date"), \DB::raw("ROUND(data_size_kb/1024) as data_size_mb"), \DB::raw("ROUND(attachment_size_kb/1024) as attachment_size_mb"), 'total_r', 'total_a', 'total_c']);
        
        return Datatables::of($appusers)
                ->remove_column('appuser_id')
                // ->add_column('action', function($appuser) {
                //     return $this->getDeletedAppuserDatatableButton($appuser->appuser_id);
                // })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getDeletedAppuserDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
            $buttonHtml .= '&nbsp;<button onclick="viewAppuser('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        return $buttonHtml;
    }
}