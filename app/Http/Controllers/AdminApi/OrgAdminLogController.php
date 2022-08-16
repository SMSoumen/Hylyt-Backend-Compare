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
use App\Models\Org\OrganizationUser;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationAdministrationSession;
use App\Models\Org\OrganizationAdministrationLog;
use View;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\OrgCloudMessagingTrait;
use Crypt;
use File;
use App\Libraries\FileUploadClass;
use DB;

class OrgAdminLogController extends Controller
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
    public function loadAdminLogView()
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

            $_viewToRender = View::make('orgadminlog.index', $data);
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
    public function adminLogDatatable()
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

            $logTableModel = New OrganizationAdministrationLog;

            $adminLogs = $logTableModel->select([\DB::raw("organization_administration_logs.created_at as created_at"), "type_name", "fullname", "log_message"])
                                ->ofOrganization($this->organizationId)
                                ->joinOrgAdmin()
                                ->joinActionType();

            return Datatables::of($adminLogs)
                            ->make();
        }
    }
}