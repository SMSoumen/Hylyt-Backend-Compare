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
use App\Models\Org\Api\OrgMlmContentAddition;
use App\Models\Org\Api\OrgMlmContentAdditionEmployee;
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
use App\Libraries\FileUploadClass;
use App\Libraries\MailClass;
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Traits\OrgCloudMessagingTrait;
use Crypt;
use DB;
use File;

class OrgContentAdditionController extends Controller
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
    
    public function __construct()
    {
        $this->module = Config::get('app_config_module.mod_org_content_addition');

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
    public function loadContentAdditionView()
    {
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg");
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

            $_viewToRender = View::make('orgcontentaddition.index', $data);
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
    public function contentAdditionDatatable()
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
        
            $modelObj = New OrgMlmContentAddition;
            $modelObj->setConnection($this->orgDbConName);

            $contAdditions = $modelObj->select(['mlm_content_addition_id', 'content_text', \DB::raw("DATE_FORMAT(sent_at, '$dateTimeFormat') as sent_on"), "sent_by"]);//->joinEmployeeTable();

            return Datatables::of($contAdditions)
                    ->remove_column('mlm_content_addition_id')
                    ->edit_column('sent_by', function($contAddition) {
                        return $this->getContentSentByData($contAddition->sent_by);
                    })
                    ->add_column('action', function($contAddition) {
                        return $this->getContentAdditionDatatableButton($contAddition->mlm_content_addition_id);
                    })
                    ->make();
        }
    }
    
    private function getContentSentByData($id) {
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
    private function getContentAdditionDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1) {
            $buttonHtml .= '&nbsp;<button onclick="viewAddContentDetails(\''.$id.'\');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_delete == 1) {
        	$buttonHtml .= '&nbsp;<button onclick="deleteAddContentDetails(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;Delete</button>';
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
    public function loadAddContentModal()
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

            $addContentId = Input::get('addContentId');
            
            $addContentId = sracDecryptNumberData($addContentId);
        
	        $addContentDetails = NULL;
	        if($addContentId != "" && $addContentId > 0)
	        {
	            $modelObj = New OrgMlmContentAddition;
	            $modelObj = $modelObj->setConnection($this->orgDbConName);

	            $addContentDetails = $modelObj->byId($addContentId)->first();
	            if(isset($addContentDetails))
	            {
					$addContentDetails->url = OrganizationClass::getOrgMlmContentAdditionAssetUrl($this->organizationId, $addContentDetails->server_filename);
				}
			}
	        
	    	$data = array();
            $data['id'] = sracEncryptNumberData($addContentId);
	    	$data["addContentDetails"] = $addContentDetails;
	    	$data["usrtoken"] = $this->userToken;
	    	
	        $_viewToRender = View::make('orgcontentaddition.partialview._addContentModal', $data);
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
    public function addAppuserContent()
    {
        $status = 0;
        $msg = "";
    	$serverAddContentId = 0;

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" , 'param' => $this->organizationId );
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

            $addContentId = Input::get('add_cont_id');
	        $contentText = Input::get('content_text');
        	$contentFile = Input::file('content_file');
        	$fileChanged = Input::get('image_changed');
	        $isSend = Input::get('is_send');
	        $isTest = Input::get('is_test');
	        $isFilterAppusers = Input::get('filter_appusers');

            $addContentId = sracDecryptNumberData($addContentId);

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
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";
        $response['contentId'] = sracEncryptNumberData($serverAddContentId); 

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
            
            $addContentId = Input::get('addContentId');

            $addContentId = sracDecryptNumberData($addContentId);
	        
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
            $data['addContentId'] = sracEncryptNumberData($addContentId);
	        $data['pageName'] = $pageName;
	        $data['page_description'] = $pageName;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;
        	$data['verStatusList'] = $verStatusList;
        	$data['departmentList'] = $arrDepartment;
        	$data['designationList'] = $arrDesignation;
        	$data['badgeList'] = $arrBadge;
        	$data['forSend'] = 1;
	    	
	        $_viewToRender = View::make('orgcontentaddition.appuserList', $data);
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
    public function addSelAppuserContent()
    {    	
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }  
        
        $status = 0;
        $msg = "";        
        $response = array();
        
        $addContentId = Input::get('contId');
        $empIdArr = json_decode(Input::get('empIdArr'));

        $addContentId = sracDecryptNumberData($addContentId);
        $empIdArr = sracDecryptNumberArrayData($empIdArr);
        
        if($addContentId != "" && $addContentId > 0 && isset($empIdArr) && count($empIdArr) > 0)
        {
			$modelObj = new OrgMlmContentAddition();
			$modelObj->setConnection($this->orgDbConName);
			
	        $addContDetails = $modelObj->byId($addContentId)->first();
	                	
        	if(isset($addContDetails))
        	{
        		$status = 1;
        		$contentText = $addContDetails->content_text;
				$contentFileName = $addContDetails->filename;
				$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl($this->organizationId, $addContDetails->server_filename);
        		
            	$employeeResultset = OrganizationClass::getEmployeeResultSet($this->organizationId, $empIdArr);
        		$employees = $employeeResultset->get();
				
				$isTest = 0;
				$this->addAndSendContent($employees, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId);	
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
            $modelObj = New OrgMlmContentAddition;
            $modelObj = $modelObj->setConnection($this->orgDbConName);

            $addContDetails = $modelObj->byId($addContentId)->first();
        	
        	if(isset($addContDetails))
        	{
        		$contentText = $addContDetails->content_text;
				$contentFileName = $addContDetails->filename;
				$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl($this->organizationId, $addContDetails->server_filename);
        		
            	$employeeResultset = OrganizationClass::getEmployeeResultSet($this->organizationId);
        		$employees = $employeeResultset->get();
				
				$isTest = 0;
				
				$this->addAndSendContent($employees, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId);	
        	}
        }
    }
    
    function addAndSendContent($employees, $isTest, $contentText, $contentFileName, $contentFileUrl, $addContentId)
    {        
		foreach($employees as $employee)
		{
			$empId = $employee->employee_id;			
			$userId = OrganizationClass::getUserIdFromOrgEmployee($this->organizationId, $empId);					
			$user = Appuser::byId($userId)->first();
			
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        
	        $empConst = $depMgmtObj->getEmployeeConstantObject();				
			if(isset($user) && isset($empConst))
	    	{    
	    		$defFolderId = $empConst->def_folder_id;
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

           		$empContentId = $response['syncId'];
           		
           		if(isset($contentFileUrl) && $contentFileUrl != "")
           		{
					$uploadResponse = FileUploadClass::copyContentAdditionFileToContent($contentFileUrl, $this->organizationId);
					
					if(isset($uploadResponse['name']))
					{
						$serverFileName = $uploadResponse['name'];
						$serverFileSize = $uploadResponse['size'];
						
						$filename = $contentFileName;
						
						$depMgmtObj->addEditContentAttachment(0, $empContentId, $filename, $serverFileName, $serverFileSize);
					}		
				}
	           		
        		$userContentAddData = array();
                $userContentAddData['mlm_content_addition_id'] = $addContentId;
                $userContentAddData['employee_id'] = $empId;            
                $userContentAddData['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                			
	        	$modelObj = New OrgMlmContentAdditionEmployee;
	            $modelObj = $modelObj->setConnection($this->orgDbConName);
	            $contentAdditionEmployeeTableName = $modelObj->table;
        
                DB::connection($this->orgDbConName)->table($contentAdditionEmployeeTableName)->insertGetId($userContentAddData);
                
                $this->sendOrgEntryAddMessageToDevice($empId, $this->organizationId, $empContentId, "");
                
				MailClass::sendOrgContentAddedMail($this->organizationId, $empId, $empContentId, "");
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
            $contentServerFileName = FileUploadClass::uploadOrgMlmContentAdditionFile($addContentFile, $this->organizationId);
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
        
        if($addContentId != "" && $addContentId > 0)
        {	
    		$addContentDetails['updated_by'] = $this->userId;	
    		
        	$modelObj = New OrgMlmContentAddition;
            $modelObj = $modelObj->setConnection($this->orgDbConName);
            $addContent = $modelObj->byId($addContentId)->first();
            $addContent->update($addContentDetails);
            
            $id = $addContentId;
		}   
		else
		{
    		$addContentDetails['created_by'] = $this->userId;	
    		$addContentDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp(); 
    			        	
			$modelObj = new OrgMlmContentAddition;
			$tableName = $modelObj->table;
	        
	        $id = DB::connection($this->orgDbConName)->table($tableName)->insertGetId($addContentDetails);	
		}
		$contentFileUrl = OrganizationClass::getOrgMlmContentAdditionAssetUrl($this->organizationId, $contentFileName);
        
        $contArr['id'] = $id;
        $contArr['url'] = $contentFileUrl;
        
        return $contArr;
	}
	
	function setAddContentSent($addContentId)
    {
        $currDt = date(Config::get('app_config.datetime_db_format'));
        
		$modelObj = new OrgMlmContentAddition;
		$modelObj->setConnection($this->orgDbConName);
		
        $addContent = $modelObj->byId($addContentId)->first();	
        $addContent->is_draft = 0;
        $addContent->is_sent = 1;
        $addContent->sent_by = $this->userId;
        $addContent->sent_at = $currDt;
        $addContent->save();
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
        $id = Input::get('contId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";
        $isSent = 0;
    		
    	$modelObj = New OrgMlmContentAddition;
        $modelObj = $modelObj->setConnection($this->orgDbConName);
        $contDetails = $modelObj->byId($id)->first();
        
        if(isset($contDetails))
        {
        	$isSent = $contDetails->is_sent;
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
        $id = Input::get('contId');

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
            $msg = 'Content Addition deleted!';
    		
	    	$modelObj = New OrgMlmContentAddition;
	        $modelObj = $modelObj->setConnection($this->orgDbConName);
	        
	        $contDetails = $modelObj->byId($id)->first();
            $contDetails->is_deleted = 1;
            $contDetails->deleted_by = $this->userId;
            $contDetails->updated_by = $this->userId;
            $contDetails->save();
        
            $contDetails->delete();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}