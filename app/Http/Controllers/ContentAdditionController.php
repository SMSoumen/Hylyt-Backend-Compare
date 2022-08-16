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
use App\Models\MlmContentAddition;
use App\Models\MlmContentAdditionAppuser;
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
use DB;
use File;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;

class ContentAdditionController extends Controller
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

        $this->page_icon = "<i class='fa fa-calendar-plus-o'></i>";
        $this->page_title = "Content Addition";
        $this->page_title_link = "contentAddition";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_content_addition');
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

        $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/bootbox/bootbox.min.js", "/js/modules/common_content_addition.js", "/dist/bootstrap-fileinput/js/fileinput.min.js");
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

        return view('contentaddition.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function contentAdditionDatatable()
    {      
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }
        $dateFormat = Config::get('app_config.sql_date_db_format');
        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

        $contAdditions = MlmContentAddition::select(['mlm_content_addition_id', 'content_text', \DB::raw("DATE_FORMAT(sent_at, '$dateTimeFormat') as sent_on"), "employee_name as sent_by"]); 
        
        $contAdditions->leftJoin('users', 'sent_by', '=', 'user_id');

        return Datatables::of($contAdditions)
                ->remove_column('mlm_content_addition_id')
                ->add_column('action', function($contAddition) {
                    return $this->getContentAdditionDatatableButton($contAddition->mlm_content_addition_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getContentAdditionDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
            $buttonHtml .= '&nbsp;<button onclick="viewAddContentDetails('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
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

        $user = MlmContentAddition::byId($id)->first();
        $userConstant = MlmContentAdditionAppuser::ofUser($id)->first();

        $pageName = $this->page_title.' Details';    
        $js = array("/js/modules/common_appuser.js", "/dist/bootbox/bootbox.min.js");
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

        return view('contentaddition.show', $data);
    }

    /**
     * Load Add Appuser Content Modal.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function loadAddAppuserContentModal()
    {
        $addContentId = Input::get('addContentId');
        
        $addContentDetails = NULL;
        if($addContentId != "" && $addContentId > 0)
        {
        	$addContentDetails = MlmContentAddition::byId($addContentId)->first();	
            if(isset($addContentDetails))
            {
				$addContentDetails->url = OrganizationClass::getOrgMlmContentAdditionAssetUrl(0, $addContentDetails->server_filename);
			}
		}
        
    	$data = array();
    	$data["addContentDetails"] = $addContentDetails;
    	
        $_viewToRender = View::make('contentaddition.partialview._addContentModal', $data);
        $_viewToRender->render();

        $response = array('status' => '1', 'view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Add Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function addAppuserContent()
    {
    	$status = 0;
    	$msg = "";
    	$serverAddContentId = 0;
    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $addContentId = Input::get('add_cont_id');
        $contentText = Input::get('content_text');
    	$contentFile = Input::file('content_file');
    	$fileChanged = Input::get('image_changed');
        $isSend = Input::get('is_send');
        $isTest = Input::get('is_test');
        $isFilterAppusers = Input::get('filter_appusers');

        if($contentText != "")
        {    
        	$status = 1;
            $conAddResp = $this->saveAddContentDetails($addContentId, $contentText, $contentFile, $fileChanged);   
            
            if(isset($conAddResp['id']))
	        {
            	$serverAddContentId = $conAddResp['id'];
	            $addContentFileUrl = $conAddResp['url'];
			}
            
            if(isset($isSend) && $isSend == 1)
            {
				$this->sendAddAppuserContentNotif($serverAddContentId, $isTest);
			}         
        }
        else
        {
        	$status = -1;
			$msg = "Content Text Is Required";
		}

        $response = array('status' => $status, 'msg' => $msg, 'contentId' => $serverAddContentId);

        return Response::json($response);    	
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

        $addContentId = Input::get('cont_add_id');
        
        if($addContentId <= 0)
        {
            return redirect('contentaddition');
        }
        
        $pageName = 'Filter Appuser List';
        
    	$js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/datatables/plugins/dataTables.buttons.min.js", "/dist/datatables/plugins/buttons.html5.min.js", "/dist/datatables/plugins/pdfmake.min.js", "/dist/datatables/plugins/vfs_fonts.js", "/dist/datatables/plugins/jszip.min.js", "/dist/bootbox/bootbox.min.js", "/dist/select2/dist/js/select2.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js", "/dist/icheck/icheck.min.js","/js/modules/common_content_addition.js","/js/modules/common_appuser_advanced_search.js");
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
        $data['addContentId'] = $addContentId;
        $data['page_icon'] = $this->page_icon;
        $data['page_title'] = $this->page_title;        
        $data['breadcrumbArr'] = $this->breadcrumbArr;
        $data['breadcrumbLinkArr'] = $this->breadcrumbLinkArr;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('contentaddition.appuserList', $data);   	
    }

    /**
     * Add Selected Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function addSelAppuserContent()
    {    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }  
        
        $status = 0;
        $msg = "";        
        $response = array();
        
        $addContentId = Input::get('contId');
        
        if($addContentId != "" && $addContentId > 0)
        {
        	$addContDetails = MlmContentAddition::byId($addContentId)->first();	
        	
        	if(isset($addContDetails))
        	{
        		$status = 1;
        		$contentText = $addContDetails->content_text;
				$contentFileName = $addContDetails->filename;
				$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl(0, $addContDetails->server_filename);
        		
        		$appuserResultset = $this->getAppuserResultSet();
        		$appUsers = $appuserResultset->get();
				
				$isTest = 0;
				$this->addAndSendContent($appUsers, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId);	
				
				//$response['appUsers'] = $appUsers;
        	}
        }
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Add Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function sendAddAppuserContentNotif($addContentId, $isTest)
    {    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }  
        
        if($addContentId != "" && $addContentId > 0)
        {
        	$addContDetails = MlmContentAddition::byId($addContentId)->first();	
        	
        	if(isset($addContDetails))
        	{
        		$contentText = $addContDetails->content_text;
				$contentFileName = $addContDetails->filename;
				$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl(0, $addContDetails->server_filename);
        		
        		if($isTest == 1)
		        {
		        	$testEmails = Config::get('app_config.test_email_arr');
					$appUsers = Appuser::whereIn('email', $testEmails)->where('is_verified','=',1)->get();
				}
				else
				{
		    		$appUsers = Appuser::where('is_verified','=',1)->get();					
				}
				
				$this->addAndSendContent($appUsers, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId);	
        	}
        }
    }
    
    function addAndSendContent($appUsers, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId)
    {
		foreach($appUsers as $appUser)
		{
			$userId = $appUser->appuser_id;
			
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgKey($appUser, "");
			
	        $userConst = $depMgmtObj->getUserConstantObject();
			//if($userId == 1 || $userId == 102 || $userId == 13 || $userId == 85)
			if(isset($userConst))
			{
				$defFolderId = $userConst->def_folder_id;
                $colorCode = Config::get('app_config.default_content_color_code');
                $isLocked = Config::get('app_config.default_content_lock_status');
                $isShareEnabled = Config::get('app_config.default_content_share_status');
                $contentType = Config::get('app_config.content_type_a');
                $sourceId = NULL;
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

				$utcTz =  'UTC';
	    		$createDateObj = Carbon::now($utcTz);
	    		$createTimeStamp = $createDateObj->timestamp;		    		
	    		$createTimeStamp = $createTimeStamp * 1000;
	    		$updateTimeStamp = $createTimeStamp;
            
                $response = $depMgmtObj->addEditContent(0, $contentText, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $reminderTimestamp, $removeAttachmentIdArr); 

           		$newContentId = $response['syncId'];
           		
           		if(isset($contentFileUrl) && $contentFileUrl != "")
           		{
					$uploadResponse = FileUploadClass::copyContentAdditionFileToContent($contentFileUrl, 0);
					
					if(isset($uploadResponse['name']))
					{
						$serverFileName = $uploadResponse['name'];
						$serverFileSize = $uploadResponse['size'];
						
						$filename = $contentFileName;
						
						$depMgmtObj->addEditContentAttachment(0, $newContentId, $filename, $serverFileName, $serverFileSize);
					}		
				}
		            
        		$userContentAddData = array();
                $userContentAddData['mlm_content_addition_id'] = $addContentId;
                $userContentAddData['appuser_id'] = $userId;	            
                $appuserContentAdd = MlmContentAdditionAppuser::create($userContentAddData);
	            
	            $this->sendEntryAddMessageToDevice($userId, $newContentId, "SocioRAC");
				MailClass::sendContentAddedMail($userId, $newContentId);
			}
		}
		if($isTest == 0)
        {
			$this->setAddContentSent($addContentId);
		}
	}
    
    function saveAddContentDetails($addContentId, $addContentText, $addContentFile, $fileChanged)
    {
    	$contArr = array();
    	$contentFileName = "";
    	$contentServerFileName = "";
        if(isset($addContentFile) && File::exists($addContentFile) && $addContentFile->isValid()) 
        {
            $contentServerFileName = FileUploadClass::uploadOrgMlmContentAdditionFile($addContentFile, 0);
            $contentFileName = $addContentFile->getClientOriginalName();
        }
        
        $id = 0;
    	
    	$addContentDetails = array();
    	$addContentDetails['content_text'] = $addContentText;
    	$addContentDetails['is_draft'] = 1;
    	$addContentDetails['is_sent'] = 0;
    	
    	if($addContentId == 0 || $fileChanged == 1 || $contentServerFileName != "")
        {
	        $addContentDetails['filename'] = $contentFileName;
	        $addContentDetails['server_filename'] = $contentServerFileName;
		}
        
        $addContent = MlmContentAddition::byId($addContentId)->first();
        
        if(isset($addContent))
        {		
        	$addContentDetails['updated_by'] = $this->userId;
            $addContent->update($addContentDetails);
            $id = $addContentId;
		}   
		else
		{
	        $addContentDetails['created_by'] = $this->userId;
    			        	
			$modelObj = new MlmContentAddition;
			$tableName = $modelObj->table;	        
	        $id = DB::table($tableName)->insertGetId($addContentDetails);	
		}
        
		$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl(0, $contentFileName);
        
        $contArr['id'] = $id;
        $contArr['url'] = $contentFileUrl;
        
        return $contArr;
	}
	
	function setAddContentSent($addContentId)
    {
        $currDt = date(Config::get('app_config.datetime_db_format'));
        
        $addContent = MlmContentAddition::byId($addContentId)->first();	
        $addContent->is_draft = 0;
        $addContent->is_sent = 1;
        $addContent->sent_by = $this->userId;
        $addContent->sent_at = $currDt;
        $addContent->save();
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

        $appusers = Appuser::select(['appusers.appuser_id', 'fullname', 'email', 'contact', 'gender', \DB::raw("IF(is_app_registered=1, 'Email', 'Facebook') as reg_type"), \DB::raw("IF(is_verified=1, 'Verified', 'Pending') as verification_status"), 'country', \DB::raw("DATE_FORMAT(appusers.created_at, '$dateFormat') as reg_date"), \DB::raw("ROUND(attachment_kb_allotted/1024) as allotted_mb"), \DB::raw("ROUND(attachment_kb_available/1024) as available_mb"), \DB::raw("(last_sync_ts) as last_synced_at"), \DB::raw("IF(ref_code='', '-', ref_code) as ref_code")]);
        
        $appusers->leftJoin('appuser_constants', 'appuser_constants.appuser_id', '=', 'appusers.appuser_id');
        $appusers->leftJoin('appuser_sessions', 'appuser_sessions.appuser_id', '=', 'appusers.appuser_id');
        $appusers->max('last_sync_ts');

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
        $appusers->whereNotIn('last_sync_ts',['null', '', '0000-00-00 00:00:00']);
            
        $appusers->where('is_verified','=', 1);
            
        return $appusers;
    }
}