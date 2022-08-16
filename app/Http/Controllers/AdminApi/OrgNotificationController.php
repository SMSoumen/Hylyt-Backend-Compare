<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\Org\Api\OrgMlmNotification;
use App\Models\Org\Api\OrgMlmNotificationEmployee;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use Config;
use Response;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationUser;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgDepartment;
use App\Models\Org\Api\OrgDesignation;
use App\Models\Org\Api\OrgBadge;
use View;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\OrgCloudMessagingTrait;
use Crypt;
use File;
use App\Libraries\FileUploadClass;
use DB;

class OrgNotificationController extends Controller
{
    use OrgCloudMessagingTrait;
    
    public $userId = NULL;
    public $roleId = 0;
    public $organizationId = 0;
    public $userDetails = NULL;

    public $modulePermissions = NULL;
    public $module = "";

    public $userToken = NULL;
    public $orgDbConName = NULL;
    public $notifModel = NULL;
    public $notifTablename = NULL;
    public $notifEmployeeModel = NULL;
    public $notifEmployeeTablename = NULL;
     
    public function __construct()
    {
        $this->module = Config::get('app_config_module.mod_org_notification');

        $encUserToken = Input::get('usrtoken'); 

        if(isset($encUserToken) && $encUserToken != "")
        {
            $this->userToken = $encUserToken;

            $adminUserId = Crypt::decrypt($encUserToken);

            $adminUser = OrganizationAdministration::active()->byId($adminUserId)->first();

            if(isset($adminUser))
            {
                $this->userDetails = $adminUser;
                $this->userId = $adminUserId;
                $this->roleId = $adminUser->role_id;
                $this->organizationId = $adminUser->organization_id;

                $this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->organizationId);
                
                $this->notifModel = New OrgMlmNotification;
                $this->notifModel->setConnection($this->orgDbConName);
                $this->notifTablename = $this->notifModel->table;
                
                $this->notifEmployeeModel = New OrgMlmNotificationEmployee;
                $this->notifEmployeeModel->setConnection($this->orgDbConName);
                $this->notifEmployeeTablename = $this->notifEmployeeModel->table;

                $modules = CmsModule::where('module_name', '=', $this->module)->exists()->first();
                $rights = $modules->right()->where('role_id', '=', $this->roleId)->first();
                $this->modulePermissions = $rights;
            }
        }    
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function loadNotificationView()
    {
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_view == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $js = array();
            $css = array();             
            
            $data = array();
            $data['js'] = $js;
            $data['css'] = $css;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;

            $_viewToRender = View::make('orgnotification.index', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function notificationDatatable()
    {                
        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        } 

        if(!isset($this->modulePermissions) || $this->modulePermissions->module_view == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
            return NULL;
        }
        else
        {
	        $dateFormat = Config::get('app_config.sql_date_db_format');
	        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

            $notifications = $this->notifModel->select(['mlm_notification_id', 'notification_text as notif_text', \DB::raw("DATE_FORMAT(sent_at, '$dateTimeFormat') as sent_on"), "sent_by"]);//->joinEmployeeTable();

            return Datatables::of($notifications)
                    ->remove_column('mlm_notification_id')
                    ->edit_column('sent_by', function($notification) {
                        return $this->getNotificationSentByData($notification->sent_by);
                    })
                    ->add_column('action', function($notification) {
                        return $this->getNotificationDatatableButton($notification->mlm_notification_id);
                    })
                    ->make();
        }
    }
    
    private function getNotificationSentByData($id) {
		$name = "";		
		$orgAdmin = OrganizationAdministration::ofOrganization($this->organizationId)->byId($id)->first();
		if(isset($orgAdmin)) {
			$name = $orgAdmin->fullname;
		}		
		return $name;
	}
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getNotificationDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
        	$buttonHtml .= '&nbsp;<button onclick="viewAddNotificationDetails(\''.$id.'\');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_delete == 1) {
        	$buttonHtml .= '&nbsp;<button onclick="deleteNotificationDetails(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;Delete</button>';
        }
        return $buttonHtml;
    }

    /**
     * Load Add Appuser Content Modal.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function loadAddNotifModal()
    {    	
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $notifId = Input::get('notifId');
            
            $notifId = sracDecryptNumberData($notifId);
        
	        $notifDetails = NULL;
	        if($notifId != "" && $notifId > 0)
	        {
	            $notifDetails = $this->notifModel->byId($notifId)->first();
	            if(isset($notifDetails))
	            {
					$notifDetails->url = OrganizationClass::getOrgMlmNotificationAssetUrl($this->organizationId, $notifDetails->server_filename);
				}
			}
	        
	    	$data = array();
            $data['id'] = sracEncryptNumberData($notifId);
	    	$data["notificationDetails"] = $notifDetails;
	    	$data["usrtoken"] = $this->userToken;
	    	
	        $_viewToRender = View::make('orgnotification.partialview._addContentModal', $data);
	        $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Add Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function addAppuserNotification()
    {
        $status = 0;
        $msg = "";
    	$serverNotifId = 0;

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $notifId = Input::get('notif_id');
	        $notifText = Input::get('notif_text');
        	$notifImg = Input::file('notification_img');
        	$imgChanged = Input::get('image_changed');
	        $isSend = Input::get('is_send');
	        $isTest = Input::get('is_test');
	        $isFilterAppusers = Input::get('filter_appusers');

            $notifId = sracDecryptNumberData($notifId);

	        if($notifText != "")
	        {    
	        	$status = 1;
	            $notifResp = $this->saveNotificationDetails($notifId, $notifText, $notifImg, $imgChanged);
	            
	            if(isset($notifResp['id']))
	            {
	            	$serverNotifId = $notifResp['id'];
	            	$notifImgUrl = $notifResp['url'];
				}
	            
	            if(isset($isSend) && $isSend == 1)
	            {
					$this->sendAppuserNotification($serverNotifId, $isTest);
				}         
	        }
	        else
	        {
	        	$status = -1;
				$msg = "Notification Is Required";
			}         
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";
        $response['notifId'] = $serverNotifId > 0 ? sracEncryptNumberData($serverNotifId) : ''; 

        return Response::json($response);  	
    }

    /**
     * Send Appuser Notification(s).
     *
     * @param void
     *
     * @return JSONArray
     */
    public function sendAppuserNotification($notifId, $isTest)
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }   
        
        if($notifId != "" && $notifId > 0)
        {
        	$notifDetails = $this->notifModel->byId($notifId)->first();	
        	
        	if(isset($notifDetails))
        	{
				$notifText = $notifDetails->notification_text;			    	
				$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($this->organizationId, $notifDetails->server_filename);
				
            	$employeeResultset = OrganizationClass::getEmployeeResultSet($this->organizationId);
        		$orgEmployees = $employeeResultset->get();
				
		    	$this->sendNotification($orgEmployees, $isTest, $notifText, $notifImageUrl, $notifId);
			}  
		}   	
    }
    
    function sendNotification($orgEmployees, $isTest, $notifText, $notifImageUrl, $notifId)
    {
		foreach($orgEmployees as $emp)
		{
			$empId = $emp->employee_id;		
    		$userNotifData = array();
            $userNotifData['mlm_notification_id'] = $notifId;
            $userNotifData['employee_id'] = $empId;
            
            $notifEmpId = DB::connection($this->orgDbConName)->table($this->notifEmployeeTablename)->insertGetId($userNotifData);            
    		$this->sendOrgMessageToDevice($empId, $notifText, $notifImageUrl);
		}	
		if($isTest == 0)
        {
			$this->setNotificationSent($notifId);
		}	
	}
    
    function saveNotificationDetails($notifId, $notifText, $notifImg, $imgChanged)
    {
    	$notifArr = array();
        
        
    	$id = 0;
    	
    	$addNotifDetails = array();
    	$addNotifDetails['notification_text'] = $notifText;
    	$addNotifDetails['is_draft'] = 1;
    	$addNotifDetails['is_sent'] = 0;
    	
    	$notifImgFileName = "";
    	if($notifId == 0 || $imgChanged == 1)
        {
        	if(isset($notifImg) && File::exists($notifImg) && $notifImg->isValid()) 
	        {
	            $notifImgFileName = FileUploadClass::uploadOrgMlmNotifImage($notifImg, $this->organizationId);
	            if($notifImgFileName != "")
        			$addNotifDetails['server_filename'] = $notifImgFileName;
	        }
		}
        
        if($notifId != "" && $notifId > 0)
        {
    		$addNotifDetails['updated_by'] = $this->userId;	
    		
            $notif = $this->notifModel->byId($notifId)->first();
            $notif->update($addNotifDetails);
            
            $id = $notifId;
		}   
		else
		{	
    		$addNotifDetails['created_by'] = $this->userId; 
    		$addNotifDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp(); 
	        
	        $id = DB::connection($this->orgDbConName)->table($this->notifTablename)->insertGetId($addNotifDetails);	
		}
        
		$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($this->organizationId, $notifImgFileName);
        
        $notifArr['id'] = $id;
        $notifArr['url'] = $notifImageUrl;
        
        return $notifArr;
	}

	function setNotificationSent($notifId)
    {
        $currDt = date(Config::get('app_config.datetime_db_format'));
		
        $notif = $this->notifModel->byId($notifId)->first();
        $notif->is_draft = 0;
        $notif->is_sent = 1;
        $notif->sent_by = $this->userId;
        $notif->sent_at = $currDt;
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
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;
            
            $notifId = Input::get('notif_id');

            $notifId = sracDecryptNumberData($notifId);
	        
	        $pageName = 'Filter Appuser List';

	        $verStatusList = array();   
	        $verStatusList['1'] = 'Verified'; 
	        $verStatusList['0'] = 'Pending'; 
            
            $deptModelObj = New OrgDepartment;
            $deptModelObj->setConnection($this->orgDbConName);                
            $departments = $deptModelObj->active()->get();
            $arrDepartment = array();
            foreach($departments as $department)
            {
				$arrDepartment[sracEncryptNumberData($department->department_id)] = $department->department_name;
			}
            
            $desigModelObj = New OrgDesignation;
            $desigModelObj->setConnection($this->orgDbConName);            
            $designations = $desigModelObj->active()->get();
            $arrDesignation = array();
            foreach($designations as $designation)
            {
				$arrDesignation[sracEncryptNumberData($designation->designation_id)] = $designation->designation_name;
			}
            
            $badgeModelObj = New OrgBadge;
            $badgeModelObj->setConnection($this->orgDbConName);            
            $badges = $badgeModelObj->active()->get();
            $arrBadge = array();
            foreach($badges as $badge)
            {
				$arrBadge[sracEncryptNumberData($badge->badge_id)] = $badge->badge_name;
			}          
	        
	        $data = array();
            $data['id'] = sracEncryptNumberData($notifId);
	        $data['pageName'] = $pageName;
	        $data['page_description'] = $pageName;
	        $data['notifId'] = sracEncryptNumberData($notifId);
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;
        	$data['verStatusList'] = $verStatusList;
        	$data['departmentList'] = $arrDepartment;
        	$data['designationList'] = $arrDesignation;
        	$data['badgeList'] = $arrBadge;
        	$data['forSend'] = 1;
	    	
	        $_viewToRender = View::make('orgnotification.appuserList', $data);
	        $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;              
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Add Selected Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function addSelAppuserNotification()
    {    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }  
        
        $status = 0;
        $msg = "";        
        $response = array();
        
        $notifId = Input::get('notifId');
        $empIdArr = json_decode(Input::get('empIdArr'));

        if($notifId != "" && isset($empIdArr) && count($empIdArr) > 0)
        {
            $notifId = sracDecryptNumberData($notifId);
            $empIdArr = sracDecryptNumberArrayData($empIdArr);

        	$notifDetails = $this->notifModel->byId($notifId)->first();	
        	
        	if(isset($notifDetails))
        	{
        		$status = 1;
				$notifText = $notifDetails->notification_text;
				$notifImageUrl = OrganizationClass::getOrgMlmNotificationAssetUrl($this->organizationId, $notifDetails->server_filename);
        		
            	$employeeResultset = OrganizationClass::getEmployeeResultSet($this->organizationId, $empIdArr);
        		$orgEmployees = $employeeResultset->get();

				$isTest = 0;
				$this->sendNotification($orgEmployees, $isTest, $notifText, $notifImageUrl, $notifId);	
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

        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Validate group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('notifId');

        $isAvailable = 1;
        $msg = "";
        $isSent = 0;
        
        $id = sracDecryptNumberData($id);

        $notifDetails = $this->notifModel->byId($id)->first();	
        
        if(isset($notifDetails))
        {
        	$isSent = $notifDetails->is_sent;
            //$isAvailable = 0;
            //$msg = Config::get('app_config_notif.group_unavailable');
        }

        echo json_encode(array('status' => $isAvailable, 'msg' => $msg, 'isSent' => $isSent));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function destroy()
    {
        $id = Input::get('notifId');
        
        $id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";

        if($this->modulePermissions->module_delete == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
            $status = 1;
            $msg = 'Notification deleted!';
            
        	$notif = $this->notifModel->byId($id)->first();
            $notif->is_deleted = 1;
            $notif->deleted_by = $this->userId;
            $notif->updated_by = $this->userId;
            $notif->save();
            
            $notif->delete();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}