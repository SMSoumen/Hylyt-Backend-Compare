<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgBadge;
use App\Models\Org\Api\OrgEmployeeBadge;
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
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Http\Controllers\CommonFunctionController;
use DB;

class OrgBadgeController extends Controller
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
        $this->module = Config::get('app_config_module.mod_org_badge');

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
    public function loadBadgeView()
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

            $_viewToRender = View::make('orgbadge.index', $data);
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
    public function badgeDatatable()
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
            $modelObj = New OrgBadge;
            $modelObj->setConnection($this->orgDbConName);

            $badges = $modelObj->select(['badge_id', 'badge_name']);

            return Datatables::of($badges)
                    ->remove_column('badge_id')
                    ->add_column('action', function($badge) {
                        return $this->getBadgeDatatableButton($badge->badge_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getBadgeDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);

        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditBadgeModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteBadge(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

            $id = Input::get('badgeId');

            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $badge = NULL;
            if($id > 0)
            {
                $modelObj = New OrgBadge;
                $modelObj->setConnection($this->orgDbConName);

                $badge = $modelObj->byId($id)->first();
                $pageName = 'Edit';     
            }
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['badge'] = $badge;
            $data['page_description'] = $pageName.' '.'Badge';
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgbadge.partialview._addEditModal', $data);
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
        $id = $request->input('badgeId'); 
        $badgeName = $request->input('badge_name');
        $badgeName = CommonFunctionController::convertStringToCap($badgeName); 

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
                $msg = 'Badge updated!'; 

                $modelObj = New OrgBadge;
                $modelObj->setConnection($this->orgDbConName);

                $badge = $modelObj->byId($id)->first();
                $badge->update($request->all());
                $badge->badge_name = $badgeName;
                $badge->save();     
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
                $msg = 'Badge added!';
                
                $modelObj = New OrgBadge;
                $tableName = $modelObj->table;                
                                
                $badge = array();
                $badge['badge_name'] = $badgeName;
                $badge['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                DB::connection($this->orgDbConName)->table($tableName)->insert($badge);
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Validate designation name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateBadgeName()
    {        
        $id = Input::get('badgeId');
        $name = Input::get('badgeName');

        $id = sracDecryptNumberData($id);

        $modelObj = New OrgBadge;
        $modelObj->setConnection($this->orgDbConName);

        $badge = $modelObj->where('badge_name','=',$name);

        if($id > 0)
        {   
            $badge = $badge->where('badge_id','!=',$id); 
        }   

        $badge = $badge->first();          
        
        if(isset($badge))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate designation name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('badgeId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgEmployeeBadge;
        $modelObj->setConnection($this->orgDbConName);

        $employees = $modelObj->where('badge_id','=',$id)->first();
        
        if(isset($employees))
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.org_badge_unavailable');
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
        $id = Input::get('badgeId');

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
            $msg = 'Badge deleted!';

            $modelObj = New OrgBadge;
            $modelObj->setConnection($this->orgDbConName);

            $badge = $modelObj->byId($id)->first();
            $badge->is_deleted = 1;
            $badge->deleted_by = $this->userId;
            $badge->updated_by = $this->userId;
            $badge->save();

            $badge->delete();
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
        $id = Input::get('badgeId');        
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
            $msg = 'Badge status changed!';

            $modelObj = New OrgBadge;
            $modelObj->setConnection($this->orgDbConName);

            $badge = $modelObj->byId($id)->first();
            $badge->is_active = $statusActive;
            $badge->updated_by = $this->userId;
            $badge->save();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}
