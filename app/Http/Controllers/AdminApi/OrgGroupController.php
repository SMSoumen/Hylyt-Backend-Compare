<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgDepartment;
use App\Models\Org\Api\OrgDesignation;
use App\Models\Org\Api\OrgBadge;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;
use Crypt;
use Response;
use View;
use App\Libraries\MailClass;
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Http\Controllers\CommonFunctionController;
use App\Http\Traits\OrgCloudMessagingTrait;
use App\Libraries\FileUploadClass;
use DB;
use File;
use App\Libraries\ContentDependencyManagementClass;
use Illuminate\Support\Facades\Log;

class OrgGroupController extends Controller
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
        $this->module = Config::get('app_config_module.mod_org_group');

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
    public function loadGroupView()
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

            $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/dist/icheck/icheck.min.js","/dist/toggle-button/js/fa-multi-button.js");
            $css = array("/dist/datatables/jquery.dataTables.min.css","/dist/icheck/skins/square/square.css","/dist/icheck/skins/square/_all.css");             
            
            $data = array();
            $data['js'] = $js;
            $data['css'] = $css;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;

            $_viewToRender = View::make('orggroup.index', $data);
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
    public function groupDatatable()
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
            $modelObj = New OrgGroup;
            $modelObj->setConnection($this->orgDbConName);
            $grpTable = $modelObj->table;

            // $groups = $modelObj->select([ "$grpTable.group_id as group_id", 'name', \DB::raw("IF(is_two_way = 0, 'No', 'Yes') as twoWay"), \DB::raw("ROUND(allocated_space_kb/1024) as allotted_mb"), \DB::raw("ROUND((allocated_space_kb-used_space_kb)/1024) as available_mb"), \DB::raw("COUNT(group_content_id) as note_count"), \DB::raw('concat('.$grpTable.'.group_id, "_", is_group_active) as status') ]);

            $groups = $modelObj->select([ "$grpTable.group_id as group_id", 'name', \DB::raw("IF(auto_enroll_enabled = 0, 'No', 'Yes') as isAutoEnroll"), \DB::raw("ROUND(allocated_space_kb/1024) as allotted_mb"), \DB::raw("ROUND((allocated_space_kb-used_space_kb)/1024) as available_mb"), \DB::raw("COUNT(group_content_id) as note_count"), "is_group_active" ]);
            
            $groups = $groups->joinContents();

            return Datatables::of($groups)
                    ->remove_column('group_id')
                    ->remove_column('is_group_active')
                    ->add_column('status', function($group) {
                        return sracEncryptNumberData($group->group_id)."_".$group->is_group_active;
                    })
                    ->add_column('action', function($group) {
                        return $this->getGroupDatatableButton($group->group_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getGroupDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadGroupInfoModal(\''.$id.'\');" class="btn btn-xs btn-success"><i class="fa fa-info"></i>&nbsp;&nbsp;Info</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditGroupModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
            $buttonHtml .= '&nbsp;<button onclick="loadQuickModifyGroupRightModal(\''.$id.'\');" class="btn btn-xs btn-orange"><i class="fa fa-check-square-o"></i>&nbsp;&nbsp;Members</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteGroup(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        }
        return $buttonHtml;
    }

    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadAddEditModal()
    {
        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        } 

        $status = 0;
        $msg = "";

        $response = array();

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('groupId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $group = NULL;
            $selectedEmpIdArr = array();
            if($id > 0)
            {
                $modelObj = New OrgGroup;
                $modelObj->setConnection($this->orgDbConName);
                $group = $modelObj->byId($id)->first();
                
                $modelObj = New OrgGroupMember;
                $modelObj->setConnection($this->orgDbConName);
                $groupMembers = $modelObj->ofGroup($id)->get();
                
                foreach($groupMembers as $member)
                {
					array_push($selectedEmpIdArr, $member->employee_id);
				}
				
	            if(isset($group))
	            {
					$group->url = OrganizationClass::getOrgGroupPhotoUrl($this->organizationId, $group->img_server_filename);
				}
				
                $pageName = 'Edit';     
            }
            
            $modelObj = New OrgEmployee;
            $modelObj->setConnection($this->orgDbConName);
            
            $employees = $modelObj->joinDepartmentTable()->joinDesignationTable()->active()->get();

	        $verStatusList = array();   
	        $verStatusList['1'] = Config::get('app_config.is_verified_text'); 
	        $verStatusList['0'] = Config::get('app_config.verification_pending_text'); 
	        $verStatusList['2'] = Config::get('app_config.is_self_verified_text');
            
            $deptModelObj = New OrgDepartment;
            $deptModelObj->setConnection($this->orgDbConName);                
            $departments = $deptModelObj->active()->get();
            $arrDepartment = array();
            foreach($departments as $department)
            {
				$arrDepartment[$department->department_id] = $department->department_name;
			}
            
            $desigModelObj = New OrgDesignation;
            $desigModelObj->setConnection($this->orgDbConName);            
            $designations = $desigModelObj->active()->get();
            $arrDesignation = array();
            foreach($designations as $designation)
            {
				$arrDesignation[$designation->designation_id] = $designation->designation_name;
			}
            
            $badgeModelObj = New OrgBadge;
            $badgeModelObj->setConnection($this->orgDbConName);            
            $badges = $badgeModelObj->active()->get();
            $arrBadge = array();
            foreach($badges as $badge)
            {
				$arrBadge[$badge->badge_id] = $badge->badge_name;
			}      
			
			$isView = FALSE;    
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['group'] = $group;
            $data['employees'] = $employees;
            $data['page_description'] = $pageName.' '.'Group';
            $data['usrtoken'] = $this->userToken;
            $data['selectedEmpIdArr'] = sracEncryptNumberArrayData($selectedEmpIdArr);
        	$data['verStatusList'] = $verStatusList;
        	$data['departmentList'] = $arrDepartment;
        	$data['designationList'] = $arrDesignation;
        	$data['badgeList'] = $arrBadge;
        	$data['forGroupList'] = 1;
        	$data['isView'] = $isView;
           
            $_viewToRender = View::make('orggroup.partialview._addEditModal', $data);
            $_viewToRender = $_viewToRender->render();
            
            $response['view'] = $_viewToRender;
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function saveDetails(Request $request)
    {        
        $id = $request->input('groupId'); 
        $groupQuotaMb = $request->input('group_quota_mb');
        $groupName = $request->input('group_name');
        $groupName = CommonFunctionController::convertStringToCap($groupName); 
        // $groupIsTwoWay = $request->input('isTwoWay');  
        $groupIsAutoEnroll = $request->input('isAutoEnroll');  
        $selEmployeeIdArr = $request->input('empIsSelected');
        $isFavorited = $request->input('isFavorited');
        $description = $request->input('description');
        
        if(!isset($isFavorited)) {
			$isFavorited = 0;
		}
        
        /*$id = 11;
        $groupName = "New Group - SL";
        $selEmployeeIdArr = '["1","2"]';
        $groupIsTwoWay = 1;*/
        
        $isAdd = TRUE;
        $oldGroupName = "";
        
        $selEmployeeIdArr = json_decode($selEmployeeIdArr);
        
        $id = sracDecryptNumberData($id);
        $selEmployeeIdArr = sracDecryptNumberArrayData($selEmployeeIdArr);
              
        // if(!isset($groupIsTwoWay))
        // 	$groupIsTwoWay = 0;
        $groupIsTwoWay = 1;
        	
        if(!isset($groupIsAutoEnroll))
        	$groupIsAutoEnroll = 0;
                
        $groupQuotaKb = 0;
        if(isset($groupQuotaMb))
        	$groupQuotaKb = $groupQuotaMb*1024;

        $status = 0;
        $msg = "";
        $response = array();
        
        $groupDetails = array();
        $groupDetails['name'] = $groupName;
        $groupDetails['description'] = $description;
        $groupDetails['is_two_way'] = $groupIsTwoWay;
        $groupDetails['auto_enroll_enabled'] = $groupIsAutoEnroll;
        $groupDetails['allocated_space_kb'] = $groupQuotaKb;
        
        if(!isset($selEmployeeIdArr))
        	$selEmployeeIdArr = array();

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;
                $msg = 'Group updated!';  
                $groupDetails['updated_by'] = $this->userId;
                $groupDetails['updated_at'] = CommonFunctionClass::getCurrentTimestamp();

                $modelObj = New OrgGroup;
                $modelObj->setConnection($this->orgDbConName);
                $group = $modelObj->byId($id)->first();
                
                if(isset($group))
                {
	                $oldGroupName = $group->name;	                
	                $group->update($groupDetails);	                
	                $isAdd = FALSE;
	                
                	$groupId = $id;
				}
                
            }
        }
        else
        {
            if($this->modulePermissions->module_add == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;
                $msg = 'Group added!';
                                
                $groupDetails['created_by'] = $this->userId;
                $groupDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                $groupDetails['used_space_kb'] = 0;
                
                $modelObj = New OrgGroup;
                $tableName = $modelObj->table;                
                
                $groupId = DB::connection($this->orgDbConName)->table($tableName)->insertGetId($groupDetails);
                
	            $modelObj = New OrgGroup;
	            $modelObj->setConnection($this->orgDbConName);
            	$group = $modelObj->byId($groupId)->first();
            }
        }
        
        if(isset($groupId) && $groupId > 0)
        {
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgId($this->organizationId);
        	$depMgmtObj->recalculateOrgSubscriptionParams();
        	
        	$groupPhotoFile = Input::file('photo_file'); 
			$imgChanged = Input::get('image_changed');
			
			$fileName = "";
        	if(isset($groupPhotoFile) && File::exists($groupPhotoFile) && $groupPhotoFile->isValid()) 
            {
                $fileUpload = new FileUploadClass;
                $fileName = $fileUpload->uploadOrganizationGroupPhotoImage($groupPhotoFile, $this->organizationId);
            }
                
            if($id == 0 || $imgChanged == 1 || $fileName != "")
            {
                $grpModelObj = New OrgGroup;
                $grpModelObj->setConnection($this->orgDbConName);
                $group = $grpModelObj->byId($groupId)->first();
            	if(isset($group))
            	{
					$group->img_server_filename = $fileName;
					$group->save();
				}			
			}
			
			$isRename = 0;
			if(!$isAdd && $oldGroupName != $groupName)
				$isRename = 1;

            if($isAdd)
            {
                $isFolder = FALSE;
                $existingGroupContents = $depMgmtObj->getAllContents($isFolder, $groupId);
                foreach ($existingGroupContents as $existingGroupContent) {
                    $delContentId = $existingGroupContent->group_content_id;
                    $depMgmtObj->deleteContent($delContentId, $isFolder);
                }
            }
        	
            $modelObj = New OrgGroupMember;
            $modelObj->setConnection($this->orgDbConName);

            $groupEmployees = $modelObj->ofGroup($groupId)->get();
            
            $existingEmployeeIdArr = array();
            foreach($groupEmployees as $groupEmployee)
            {
            	$empId = $groupEmployee->employee_id;
            	
				if($empId > 0 && !in_array($empId, $selEmployeeIdArr))
				{
					$groupEmployee->delete();
					
					//FCM for removing user from group
					$this->sendOrgGroupDeletedMessageToDevice($empId, $groupId, $groupName);
					
					MailClass::sendOrgEmpRemovedFromGroupMail($this->organizationId, $empId, $group);
				}
				else
				{
					array_push($existingEmployeeIdArr, $empId);
					$isAddOp = 0;
					$this->sendOrgGroupAddedMessageToDevice($empId, $groupId, $isRename, $this->organizationId, $isAddOp, $oldGroupName);
				}	
			}
			
			foreach($selEmployeeIdArr as $empId)
			{
				if($empId > 0 && !in_array($empId, $existingEmployeeIdArr))
				{
	                $modelObj = New OrgGroupMember;
	                $tableName = $modelObj->table; 
	                     
					$empDetails = array();
					$empDetails['group_id'] = $groupId;
					$empDetails['employee_id'] = $empId;
					$empDetails['is_admin'] = 0;
					$empDetails['has_post_right'] = 0;
					$empDetails['is_ghost'] = 0;
                    $empDetails['is_locked'] = 0;
					$empDetails['is_favorited'] = $isFavorited;
					
                	DB::connection($this->orgDbConName)->table($tableName)->insert($empDetails);
					
					//FCM for adding user to group
					$isAddOp = 1;
					$this->sendOrgGroupAddedMessageToDevice($empId, $groupId, $isRename, $this->organizationId, $isAddOp, $oldGroupName);
					
					MailClass::sendOrgEmpAddedToGroupMail($this->organizationId, $empId, $group);
				}
			}         			
		}
        
        $response['status'] = $status;
        $response['msg'] = $msg;

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
        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        } 

        $status = 0;
        $msg = "";

        $response = array();

        $groupId = Input::get('groupId');
        $statusActive = Input::get('statusActive');

        $groupId = sracDecryptNumberData($groupId);

        if($groupId > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;

                $grpModelObj = New OrgGroup;
                $grpModelObj->setConnection($this->orgDbConName);
                $group = $grpModelObj->byId($groupId)->first();
                if(isset($group))
                {
                    $prevStatus = $group->is_group_active;
                    $groupName = $group->name;
                    $isRename = 0;

                    if($prevStatus != $statusActive)
                    {
                        $group->is_group_active = $statusActive;
                        $group->save();
                
                        $modelObj = New OrgGroupMember;
                        $modelObj->setConnection($this->orgDbConName);

                        $groupEmployees = $modelObj->ofGroup($groupId)->get();
                        foreach($groupEmployees as $groupEmployee)
                        {
                            $empId = $groupEmployee->employee_id;

                            Log::info('statusActive: '.$statusActive.' : empId : '.$empId);

                            $this->sendOrgGroupStatusChangeMessageToDevice($empId, $groupId, $this->organizationId, $statusActive);
                        }
                       
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
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadModifyRightModal()
    {
        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        } 

        $status = 0;
        $msg = "";

        $response = array();

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('groupId');

            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $rightArr = Config::get('app_config.group_permission_option_arr');

            $grpPermissionCodeRead = Config::get('app_config.group_permission_code_read');
            $grpPermissionCodeWrite = Config::get('app_config.group_permission_code_write');
            $grpPermissionCodeAdmin = Config::get('app_config.group_permission_code_admin');
            $grpPermissionCodeGhost = Config::get('app_config.group_permission_code_ghost');

            $group = NULL;
            $groupMembers = array();
            if($id > 0)
            {
                $modelObj = New OrgGroup;
                $modelObj->setConnection($this->orgDbConName);
                $group = $modelObj->byId($id)->first();
                
                $modelObj = New OrgGroupMember;
                $modelObj->setConnection($this->orgDbConName);
                $groupMembers = $modelObj->joinEmployeeTable()->joinDepartmentTable()->joinDesignationTable()->ofGroup($id)->get();
                
                $pageName = 'Edit';     
            }
            
            $intJs = array("/dist/datatables/plugins/dataTables.buttons.min.js", "/dist/datatables/plugins/buttons.html5.min.js", "/dist/datatables/plugins/pdfmake.min.js", "/dist/datatables/plugins/vfs_fonts.js", "/dist/datatables/plugins/jszip.min.js");  
            $intCss = array("/dist/datatables/plugins/buttons.dataTables.min.css");
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['group'] = $group;
            $data['employees'] = $groupMembers;
            $data['page_description'] = $pageName.' '.'Group';
            $data['usrtoken'] = $this->userToken;
            $data['rightArr'] = $rightArr;
            $data['grpPermissionCodeRead'] = $grpPermissionCodeRead;
            $data['grpPermissionCodeWrite'] = $grpPermissionCodeWrite;
            $data['grpPermissionCodeAdmin'] = $grpPermissionCodeAdmin;
            $data['grpPermissionCodeGhost'] = $grpPermissionCodeGhost;
            $data['intJs'] = $intJs;
            $data['intCss'] = $intCss;
           
            $_viewToRender = View::make('orggroup.partialview._modifyMemberRightModal', $data);
            $_viewToRender = $_viewToRender->render();
            
            $response['view'] = $_viewToRender;
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function saveRightDetails(Request $request)
    {        
        $id = $request->input('groupId'); 
        $empIsAdminArr = $request->input('empIsAdmin');
        $empHasPostRightArr = $request->input('empHasPostRight');
        $empIsGhostArr = $request->input('empIsGhost');

        $empPermissionIdArr = $request->input('empPermissionId');

        $id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";
        $response = array();

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;
                $msg = 'Group updated!';

                $grpPermissionCodeRead = Config::get('app_config.group_permission_code_read');
                $grpPermissionCodeWrite = Config::get('app_config.group_permission_code_write');
                $grpPermissionCodeAdmin = Config::get('app_config.group_permission_code_admin');
                $grpPermissionCodeGhost = Config::get('app_config.group_permission_code_ghost');

                $modelObj = New OrgGroup;
                $modelObj->setConnection($this->orgDbConName);
                $group = $modelObj->byId($id)->first();
                
                $modelObj = New OrgGroupMember;
                $modelObj->setConnection($this->orgDbConName);
                $groupMembers = $modelObj->ofGroup($id)->get();
                
                if(isset($group) && isset($groupMembers))
                {
					foreach($groupMembers as $groupMember)
					{
						$empId = $groupMember->employee_id;

                        $empPermissionIdInputName = 'empPermissionId_'.$empId;
                        $empPermissionId = $request->input($empPermissionIdInputName);

                        $hasPostRight = 0;
                        $isGhost = 0;
                        $isAdmin = 0;
                        if($empPermissionId == $grpPermissionCodeGhost)
                        {
                            $isGhost = 1;                            
                        }
                        else if($empPermissionId == $grpPermissionCodeAdmin)
                        {
                        $hasPostRight = 1;
                            $isAdmin = 1;
                        }
                        else if($empPermissionId == $grpPermissionCodeWrite)
                        {
                            $hasPostRight = 1;
                        }
                        else if($empPermissionId == $grpPermissionCodeRead)
                        {

                        }
						
						$groupMember->is_admin = $isAdmin;
						$groupMember->has_post_right = $hasPostRight;
						$groupMember->is_ghost = $isGhost;
						$groupMember->save();						
						
						$isRename = 0;
						$this->sendOrgGroupAddedMessageToDevice($empId, $id, $isRename);
					}
					$group->save();
				}
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Validate Group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateGroupName()
    {        
        $id = Input::get('groupId');
        $name = Input::get('groupName');

        $modelObj = New OrgGroup;
        $modelObj->setConnection($this->orgDbConName);

        $group = $modelObj->where('name','=',$name);

        $id = sracDecryptNumberData($id);

        if($id > 0)
        {   
            $group = $group->where('group_id','!=',$id); 
        }   

        $group = $group->first();          
        
        if(isset($group))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
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
        $id = Input::get('groupId');

        $isAvailable = 1;
        $msg = "";

        $id = sracDecryptNumberData($id);

        $modelObj = New OrgGroup;
        $modelObj->setConnection($this->orgDbConName);

        $employees = array();//OrgEmployee::where('designation_id','=',$id)->first();
        
        if(count($employees)>0)
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.group_unavailable');
        }
        else
        {
            $isAvailable = 1;
        }

        echo json_encode(array('status' => $isAvailable, 'msg' => $msg));
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
        $id = Input::get('groupId');

        $status = 0;
        $msg = "";

        $id = sracDecryptNumberData($id);

        if($this->modulePermissions->module_delete == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
            $status = 1;
            $msg = 'Group deleted!';

            $modelObj = New OrgGroup;
            $modelObj->setConnection($this->orgDbConName);

            $group = $modelObj->byId($id)->first();
            $groupName = $group->name;
        	
            $modelObj = New OrgGroupMember;
            $modelObj->setConnection($this->orgDbConName);
            $groupEmployees = $modelObj->ofGroup($id)->get();
            foreach($groupEmployees as $groupEmployee)
            {
            	$empId = $groupEmployee->employee_id;
				$this->sendOrgGroupDeletedMessageToDevice($empId, $id, $groupName);
				MailClass::sendOrgGroupDeletedMail($this->organizationId, $empId, $group);
				$groupEmployee->delete();										
			}
			
			//Remove all group content
            $group->delete();
            
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgId($this->organizationId);
        	$depMgmtObj->recalculateOrgSubscriptionParams();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
    
    /**
     * save app user broadcast details.
     *
     * @return json array
     */
    public function loadGroupDetailsModal()
    {
    	$msg = "";
        $status = 0;

        $grpId = Input::get('groupId');
        $defIconUrl = asset(Config::get('app_config.icon_default_app_group'));

        $grpId = sracDecryptNumberData($grpId);

        if($this->modulePermissions->module_view == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
            $status = 1;
            
        	$response = array();
        	
        	$depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgId($this->organizationId);
			
	        $group = $depMgmtObj->getGroupObject($grpId);
	        $members = $depMgmtObj->getGroupMembers($grpId);
	        $isUserAdmin = FALSE;
			$isFolder = FALSE;
			$totalNoteCount = $depMgmtObj->getAllContentModelObj($isFolder, $grpId)->count();
			
			$memberArr = array();
			$activeMemberCount = 0;
			if(isset($members) && count($members) > 0)
			{
				$adminMemberArr = array();
				$activeMemberArr = array();
				$inActiveMemberArr = array();
				foreach($members as $grpMember)
				{
					$memberEmpId = $grpMember->employee_id;
					$memberIsAdmin = $grpMember->is_admin;
					$memberIsGhost = $grpMember->is_ghost;
						
					$isActiveMember = 0;
					$memberNoteCount = $depMgmtObj->getGroupMemberContentCount($grpId, $grpMember->member_id);
					if($memberNoteCount > 0)
					{
						$activeMemberCount++;
						$isActiveMember = 1;
					}
						
					$grpMember->isActive = $isActiveMember;
        			$grpMember->noteCount = $memberNoteCount;
					
					if($memberIsAdmin == 1) {
						array_push($adminMemberArr, $grpMember);
					}
					else if($isActiveMember == 1) {
						array_push($activeMemberArr, $grpMember);									
					}
					else {
						array_push($inActiveMemberArr, $grpMember);									
					}
				}
					
				if(count($adminMemberArr) > 0) {
					$adminMemberArr = collect($adminMemberArr);
					$adminMemberArr = $adminMemberArr->sortBy('name');
					$adminMemberArr = $adminMemberArr->toArray();
					
					$memberArr = array_merge($memberArr, $adminMemberArr);
				}
				if(count($activeMemberArr) > 0) {
					$activeMemberArr = collect($activeMemberArr);
					$activeMemberArr = $activeMemberArr->sortByDesc('noteCount'); // ->sortBy('name');
					$activeMemberArr = $activeMemberArr->toArray();
					
					$memberArr = array_merge($memberArr, $activeMemberArr);
				}
				if(count($inActiveMemberArr) > 0) {
					$inActiveMemberArr = collect($inActiveMemberArr);
					$inActiveMemberArr = $inActiveMemberArr->sortBy('name');
					$inActiveMemberArr = $inActiveMemberArr->toArray();
					
					$memberArr = array_merge($memberArr, $inActiveMemberArr);
				}
			}	
		    $totalAvailableSpaceKb = $depMgmtObj->getAvailableUserQuota(TRUE);
		    
	        $allottedSpaceMb = 0;
	        $usedSpaceMb = 0;
	        $availableSpaceMb = 0;
	        if(isset($group))
			{
				$allottedKbs = $group->allocated_space_kb;
				$usedKbs = $group->used_space_kb;
				$availableKb = $allottedKbs - $usedKbs;
				
				$totalAvailableSpaceKb += $allottedKbs;
				
				$allottedSpaceMb = CommonFunctionClass::convertKbToMb($allottedKbs);
				$availableSpaceMb = CommonFunctionClass::convertKbToMb($availableKb);
				$usedSpaceMb = $allottedSpaceMb - $availableSpaceMb;
			}
	        
	        $totalAvailableSpaceMb = CommonFunctionClass::convertKbToMb($totalAvailableSpaceKb);
	        
	        $groupQuotaStr = "$availableSpaceMb MB remaining of your $allottedSpaceMb MB space limit";
	        
	        $groupPhotoUrl = "";
			$groupPhotoThumbUrl = "";
			$photoFilename = $group->img_server_filename;
			if(isset($photoFilename) && $photoFilename != "")
			{
           		$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($this->organizationId, $photoFilename);
           		$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($this->organizationId, $photoFilename);
			}
			else
			{
				$photoFilename = "";
				$groupPhotoThumbUrl = $defIconUrl;
			}
			$totalMemberCount = 0;
			if(isset($memberArr)) {
				$totalMemberCount = count($memberArr);
			}		    
		    
	        $viewDetails = array();
	        $viewDetails['group'] = $group;
	        $viewDetails['members'] = $memberArr;
	        $viewDetails['isUserAdmin'] = $isUserAdmin;
	        $viewDetails['groupQuotaStr'] = $groupQuotaStr;
	        $viewDetails['allottedSpaceMb'] = $allottedSpaceMb;
	        $viewDetails['usedSpaceMb'] = $usedSpaceMb;
	        $viewDetails['totalAvailableSpaceMb'] = $totalAvailableSpaceMb;
			$viewDetails["totalNoteCount"] = $totalNoteCount;
			$viewDetails["activeMemberCount"] = $activeMemberCount;
			$viewDetails["totalMemberCount"] = $totalMemberCount;
			$viewDetails["groupPhotoUrl"] = $groupPhotoUrl;
			$viewDetails["groupPhotoThumbUrl"] = $groupPhotoThumbUrl;
       
            $_viewToRender = View::make('orggroup.partialview._groupDetailsModal', $viewDetails);
            $_viewToRender = $_viewToRender->render();
            
            $response['view'] = $_viewToRender;
			
		}

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response); 
    }
    
}
