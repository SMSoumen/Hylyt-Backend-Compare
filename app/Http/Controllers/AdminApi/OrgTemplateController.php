<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgTemplate;
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

class OrgTemplateController extends Controller
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
        $this->module = Config::get('app_config_module.mod_org_template');

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
    public function loadTemplateView()
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

            $_viewToRender = View::make('orgtemplate.index', $data);
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
    public function templateDatatable()
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
            $modelObj = New OrgTemplate;
            $modelObj->setConnection($this->orgDbConName);

            $templates = $modelObj->select(['template_id', 'template_name', 'template_text']);

            return Datatables::of($templates)
                    ->remove_column('template_id')
                    ->add_column('action', function($template) {
                        return $this->getTemplateDatatableButton($template->template_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getTemplateDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        
        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditTemplateModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteTemplate(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

            $id = Input::get('tempId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $template = NULL;
            if($id > 0)
            {
                $modelObj = New OrgTemplate;
                $modelObj->setConnection($this->orgDbConName);

                $template = $modelObj->byId($id)->first();
                $pageName = 'Edit';     
            }
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['template'] = $template;
            $data['page_description'] = $pageName.' '.'Template';
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgtemplate.partialview._addEditModal', $data);
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
        $id = $request->input('tempId');  
        $templateName = $request->input('template_name');
        $templateText = $request->input('template_text');
        $templateText = nl2br($templateText);

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

                $modelObj = New OrgTemplate;
                $modelObj->setConnection($this->orgDbConName);

                $template = $modelObj->byId($id)->first();
                $template->template_text = $templateText;
                $template->template_name = $templateName;
                $template->save();

                $msg = 'Template updated!';  
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
                
                $modelObj = New OrgTemplate;
                $tableName = $modelObj->table;                
                                
                $template = array();
                $template['template_text'] = $templateText;
                $template['template_name'] = $templateName;
                $template['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                DB::connection($this->orgDbConName)->table($tableName)->insert($template);

                $msg = 'Template added!';
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
    public function checkAvailForDelete()
    {
        $id = Input::get('tempId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgTemplate;
        $modelObj->setConnection($this->orgDbConName);

        $employees = array();//OrgEm::where('department_id','=',$id)->first();
        
        if(count($employees)>0)
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.department_unavailable');
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
        $id = Input::get('tempId');

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
            $msg = 'Template deleted!';

            $modelObj = New OrgTemplate;
            $modelObj->setConnection($this->orgDbConName);

            $department = $modelObj->byId($id)->first();
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

    /**
     * Validate department name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateTemplateName()
    {        
        $id = Input::get('templateId');
        $name = Input::get('template_name');

        $id = sracDecryptNumberData($id);

        $modelObj = New OrgTemplate;
        $modelObj->setConnection($this->orgDbConName);

        $template = $modelObj->where('template_name','=',$name);

        if($id > 0)
        {   
            $template->where('template_id','!=',$id); 
        }   

        $templates = $template->first();          
        
        if(isset($templates))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }
}
