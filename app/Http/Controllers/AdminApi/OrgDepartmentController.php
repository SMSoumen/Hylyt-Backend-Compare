<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgDepartment;
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
use App\Libraries\OrganizationClass;
use App\Http\Controllers\CommonFunctionController;
use App\Libraries\CommonFunctionClass;
use DB;
use Schema;

class OrgDepartmentController extends Controller
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
        $this->module = Config::get('app_config_module.mod_org_department');

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
    public function loadDepartmentView()
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

            $_viewToRender = View::make('orgdepartment.index', $data);
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
    public function departmentDatatable()
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
            $modelObj = New OrgDepartment;
            $modelObj->setConnection($this->orgDbConName);

            $departments = $modelObj->select(['department_id', 'department_name']);

            return Datatables::of($departments)
                    ->remove_column('department_id')
                    ->add_column('action', function($department) {
                        return $this->getDepartmentDatatableButton($department->department_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getDepartmentDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);

        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditDepartmentModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteDepartment(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

            $id = Input::get('deptId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $department = NULL;
            if($id > 0)
            {
                $modelObj = New OrgDepartment;
                $modelObj->setConnection($this->orgDbConName);

                $department = $modelObj->byId($id)->first();
                $pageName = 'Edit';     
            }
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['department'] = $department;
            $data['page_description'] = $pageName.' '.'Department';
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgdepartment.partialview._addEditModal', $data);
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
        $id = $request->input('deptId');  
        $deptName = $request->input('department_name');
        $deptName = CommonFunctionController::convertStringToCap($deptName);

        $status = 0;
        $msg = "";
        $response = array();

        $id = sracDecryptNumberData($id);

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;

                $modelObj = New OrgDepartment;
                $modelObj->setConnection($this->orgDbConName);

                $department = $modelObj->byId($id)->first();
                $department->updated_by = $this->userId;
                $department->department_name = $deptName;
                $department->save();

                $msg = 'Department updated!';  
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
                
                $modelObj = New OrgDepartment;
                $tableName = $modelObj->table;                
                                
                $department = array();
                $department['department_name'] = $deptName;
                $department['created_by'] = $this->userId;
                $department['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                DB::connection($this->orgDbConName)->table($tableName)->insert($department);

                $msg = 'Department added!';
            }
        }
                
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }

    /**
     * Validate department name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateDepartmentName()
    {        
        $id = Input::get('deptId');
        $name = Input::get('deptName');

        $id = sracDecryptNumberData($id);

        $modelObj = New OrgDepartment;
        $modelObj->setConnection($this->orgDbConName);

        $department = $modelObj->where('department_name','=',$name);

        if($id > 0)
        {   
            $department = $department->where('department_id','!=',$id); 
        }   

        $department = $department->first();          
        
        if(isset($department))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate department name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('deptId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);

        $employees = $modelObj->where('department_id','=',$id)->first();
        
        if(isset($employees))
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.org_department_unavailable');
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
        $id = Input::get('deptId');

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
            $msg = 'Department deleted!';

            $modelObj = New OrgDepartment;
            $modelObj->setConnection($this->orgDbConName);

            $department = $modelObj->byId($id)->first();
            $department->is_deleted = 1;
            $department->deleted_by = $this->userId;
            $department->updated_by = $this->userId;
            $department->save();

            $department->delete();
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
        $id = Input::get('deptId');        
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
            $msg = 'Department status changed!';

            $modelObj = New OrgDepartment;
            $modelObj->setConnection($this->orgDbConName);

            $department = $modelObj->byId($id)->first();
            $department->is_active = $statusActive;
            $department->updated_by = $this->userId;
            $department->save();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}
