<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgSystemTag;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;
use App\Models\User;
use Crypt;
use Response;
use View;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Http\Controllers\CommonFunctionController;
use App\Libraries\CommonFunctionClass;
use DB;
use Schema;

class OrgSystemTagController extends Controller
{
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
        $this->module = Config::get('app_config_module.mod_org_system_tag');

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
    public function loadSystemTagView()
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

            $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js");
            $css = array("/dist/datatables/jquery.dataTables.min.css");             
            
            $data = array();
            $data['js'] = $js;
            $data['css'] = $css; 
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;

            $_viewToRender = View::make('orgsystemtag.index', $data);
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
    public function systemTagDatatable()
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
            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);

            $systemTags = $modelObj->select(['system_tag_id', 'tag_name']);

            return Datatables::of($systemTags)
                    ->remove_column('system_tag_id')
                    ->add_column('action', function($systemTag) {
                        return $this->getSystemTagDatatableButton($systemTag->system_tag_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getSystemTagDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);

        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditSystemTagModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteSystemTag(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

            $id = Input::get('tagId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $systemTag = NULL;
            if($id > 0)
            {
                $modelObj = New OrgSystemTag;
                $modelObj->setConnection($this->orgDbConName);

                $systemTag = $modelObj->byId($id)->first();
                $pageName = 'Edit';     
            }
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['systemTag'] = $systemTag;
            $data['page_description'] = $pageName.' '.'System Tag';
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgsystemtag.partialview._addEditModal', $data);
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
        $id = $request->input('tagId');  
        $tagName = $request->input('tag_name');
        $tagName = CommonFunctionController::convertStringToCap($tagName);

        $status = 0;
        $msg = "";
        $response = array();

        $id = sracDecryptNumberData($id);

        $savedTagId = 0;
        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $modelObj = New OrgSystemTag;
                $modelObj->setConnection($this->orgDbConName);

                $systemTag = $modelObj->byId($id)->first();
                if(isset($systemTag))
                {
                    $status = 1;

                    $systemTag->updated_by = $this->userId;
                    $systemTag->tag_name = $tagName;
                    $systemTag->save();

                    $savedTagId = $id;

                    $msg = 'Tag updated!';  
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
                
                $modelObj = New OrgSystemTag;
                $tableName = $modelObj->table;                
                                
                $systemTag = array();
                $systemTag['tag_name'] = $tagName;
                $systemTag['created_by'] = $this->userId;
                $systemTag['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                $savedTagId = DB::connection($this->orgDbConName)->table($tableName)->insertGetId($systemTag);

                $msg = 'Tag added!';
            }
        }

        if($savedTagId > 0)
        {
            $tagSetupResponseArr = array();

            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);
            $savedTagDetails = $modelObj->byId($savedTagId)->first();

            if(isset($savedTagDetails))
            {
                $savedTagId = $savedTagDetails->system_tag_id;
                $savedTagName = $savedTagDetails->tag_name;

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgId($this->organizationId);
                $employees = $depMgmtObj->getAllEmployees();
                if(isset($employees) && count($employees) > 0)
                {  
                    foreach ($employees as $empIndex => $employee)
                    {
                        $employeeId = $employee->employee_id;
                        $employeeIsDeleted = $employee->is_deleted;

                        $tagSetupResponseObj = array();
                        $tagSetupResponseObj['empIndex'] = $empIndex;
                        $tagSetupResponseObj['employeeId'] = $employeeId;
                        if($employeeIsDeleted == 0)
                        {
                            $empDepMgmtObj = New ContentDependencyManagementClass;
                            $empDepMgmtObj->withOrgIdAndEmpId($this->organizationId, $employeeId);
                            $tagSetupDetails = $empDepMgmtObj->setupOrgEmployeeTagBasedOnOrgSystemTag($savedTagId, $savedTagName);
                            $tagSetupResponseObj['tagSetupDetails'] = $tagSetupDetails;
                        }
                        array_push($tagSetupResponseArr, $tagSetupResponseObj);
                    }                     
                }
            }        
            
            // $response['tagSetupResponseArr'] = $tagSetupResponseArr; 
            $response['id'] = sracEncryptNumberData($savedTagId);       
        }            
                
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function sendTagModifiedNotification(Request $request)
    {        
        $id = $request->input('tagId');

        $status = 0;
        $msg = "";
        $response = array();

        $id = sracDecryptNumberData($id);

        if($id > 0)
        {
            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);
            $systemTagDetails = $modelObj->byId($id)->first();

            if(isset($systemTagDetails))
            {
                $status = 1;

                $systemTagId = $systemTagDetails->system_tag_id;

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgId($this->organizationId);
                $employees = $depMgmtObj->getAllEmployees();
                if(isset($employees) && count($employees) > 0)
                {  
                    foreach ($employees as $empIndex => $employee)
                    {
                        if($employee->is_deleted == 0)
                        {
                            $employeeId = $employee->employee_id;
                            $empDepMgmtObj = New ContentDependencyManagementClass;
                            $empDepMgmtObj->withOrgIdAndEmpId($this->organizationId, $employeeId);
                            $empDepMgmtObj->sendOrgEmployeeTagBasedOnOrgSystemTagModifiedNotification($systemTagId);
                        }
                    }                     
                }
            }                
        }            
                
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Validate systemTag name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateSystemTagName()
    {        
        $id = Input::get('tagId');
        $name = Input::get('tagName');

        $id = sracDecryptNumberData($id);

        $modelObj = New OrgSystemTag;
        $modelObj->setConnection($this->orgDbConName);

        $systemTag = $modelObj->where('tag_name','=',$name);

        if($id > 0)
        {   
            $systemTag = $systemTag->where('system_tag_id','!=',$id); 
        }   

        $systemTag = $systemTag->first();          
        
        if(isset($systemTag))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate systemTag name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('tagId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $employees = NULL;// $modelObj->where('system_tag_id','=',$id)->first();
        
        if(isset($employees))
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.org_systemTag_unavailable');
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
        $id = Input::get('tagId');

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
            $msg = 'Tag deleted!';

            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);

            $systemTag = $modelObj->byId($id)->first();

            if(isset($systemTag))
            {
                $systemTag->is_deleted = 1;
                $systemTag->deleted_by = $this->userId;
                $systemTag->updated_by = $this->userId;
                $systemTag->save();

                $systemTag->delete();

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgId($this->organizationId);
                $employees = $depMgmtObj->getAllEmployees();
                if(isset($employees) && count($employees) > 0)
                {  
                    foreach ($employees as $empIndex => $employee)
                    {
                        if($employee->is_deleted == 0)
                        {
                            $employeeId = $employee->employee_id;
                            $empDepMgmtObj = New ContentDependencyManagementClass;
                            $empDepMgmtObj->withOrgIdAndEmpId($this->organizationId, $employeeId);
                            $empDepMgmtObj->resetOrgEmployeeTagOnOrgSystemTagRemoved($id);
                        }
                    }                     
                }
            }
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

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
        $id = Input::get('tagId');        
        $statusActive = Input::get('statusActive');

        $id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";


        if($this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
            $status = 1;
            $msg = 'Tag status changed!';

            $modelObj = New OrgSystemTag;
            $modelObj->setConnection($this->orgDbConName);

            $systemTag = $modelObj->byId($id)->first();
            $systemTag->is_active = $statusActive;
            $systemTag->updated_by = $this->userId;
            $systemTag->save();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}
