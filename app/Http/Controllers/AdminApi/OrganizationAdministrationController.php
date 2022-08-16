<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use App\Models\Org\Organization;
use App\Models\Org\CmsRole;
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
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Http\Controllers\CommonFunctionController;
use DB;
use App\Libraries\ContentDependencyManagementClass;

class OrganizationAdministrationController extends Controller
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
        $this->module = Config::get('app_config_module.mod_organization_administration');

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
    public function loadOrganizationAdministrationView()
    {  
        $status = 0;
        $msg = "";

        $isViewFlag = 0;

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

            $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js");
            $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/bootstrap-datepicker/dist/css/common_dt_modal.css");
 
            $organization = Organization::ofOrganization($this->organizationId)->first();

            $cmsRoles = CmsRole::active()->get();
            $roleArr = array();
            foreach ($cmsRoles as $role) {
                $roleArr[$role->role_id] = $role->role_name;
            }

            $isView = FALSE;
            if($isViewFlag == 1)
                $isView = TRUE;

            $fromIndOrganization = 1;
                
            $data = array();  
            $data['intJs'] = $js;
            $data['intCss'] = $css;
            $data['adminUserId'] = $this->userId;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['userToken'] = $this->userToken;
            $data['isView'] = $isView;
            $data['organizationId'] = sracEncryptNumberData($this->organizationId);
            $data['organization'] = $organization;
            $data['roleArr'] = $roleArr;
            $data['fromIndOrganization'] = $fromIndOrganization;
            
            $_viewToRender = View::make('organization.partialview._orgAdministrationDetails', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;  

            $response['adminUserId'] = $this->userId;  
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
}
