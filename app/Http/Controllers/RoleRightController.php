<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\Module;
use App\Models\RoleRight;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use View;
use Response;

class RoleRightController extends Controller
{
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

        $this->page_icon = "<i class='fa fa-check-square-o'></i>";
        $this->page_title = "Role";
        $this->page_title_link = "role";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];
    }
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {    
        return redirect('role');
    }

    /**
     * Show the form for modifying role rights.
     *
     * @return void
     */
    public function roleRights()
    {

        $pageName = 'Modify Role Rights';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/dist/select2/dist/js/select2.min.js","/js/modules/common_roleright.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css","/dist/select2/dist/css/select2.min.css");
        $roleArr = array();
        $roles = Role::active()
                        ->get();

        $roleArr[0] = "Select Role";
        foreach($roles as $role)
        {
            $roleArr[$role->role_id] = $role->role_name;
        }

        $roleId = Input::get('id');

        if(Session::has('role_id'))
        {
            $roleId = Session::get('role_id');
        }
        
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
        $data['roleArr'] = $roleArr;
        $data['roleId'] = $roleId;
        
        return view('roleright.addRoleRight', $data);
    }

    /**
     * Load the rights for selected role.
     *
     * @return void
     */
    public function loadRightsForRole()
    {        
        $id = Input::get('id');
        $modules = Module::active()->get();

        $roleRightsList = RoleRight::where('role_id', '=' , $id)->get();

        $roleRights = array();
        foreach ($roleRightsList as $roleRight) {
            $roleRights[$roleRight->module_id] = $roleRight;
        }
        
        $data = array();
        $data['modules'] = $modules;
        $data['roleRights'] = $roleRights;
       
        $_viewToRender = View::make('roleright.partialview._loadRoleRight', $data);
        $_viewToRender->render();

        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Load the rights for selected role.
     *
     * @return void
     */
    public function validateRoleRight()
    {
        $role_id = Input::get('role_id');
        $modules = Module::active()->get();

        $roleRightsList = RoleRight::where('role_id', '=' , $role_id)->get();

        $roleRights = array();
        foreach ($roleRightsList as $roleRight) {
            $roleRights[$roleRight->module_id] = $roleRight;
        }

        foreach ($modules as $module) 
        {
            $module_id = $module->module_id;
            $module_add = Input::get('chk_add_module_'.$module_id);
            $module_view = Input::get('chk_view_module_'.$module_id);
            $module_edit = Input::get('chk_edit_module_'.$module_id);
            $module_delete = Input::get('chk_delete_module_'.$module_id);
            $module_print = Input::get('chk_print_module_'.$module_id);
            $module_email = Input::get('chk_email_module_'.$module_id);
            $module_download = Input::get('chk_download_module_'.$module_id);
            $module_upload = Input::get('chk_upload_module_'.$module_id);
            $module_share = Input::get('chk_share_module_'.$module_id);
            
            $queryData = array();
            $queryData['module_add'] = 0;
            $queryData['module_view'] = 0;
            $queryData['module_edit'] = 0;
            $queryData['module_delete'] = 0;
            $queryData['module_print'] = 0;
            $queryData['module_email'] = 0;
            $queryData['module_download'] = 0;
            $queryData['module_upload'] = 0;
            $queryData['module_share'] = 0;

            if(isset($module_add) && $module_add == 1)
            {
                $queryData['module_add'] = 1;
            }
            if(isset($module_view) && $module_view == 1)
            {
                $queryData['module_view'] = 1;
            }
            if(isset($module_edit) && $module_edit == 1)
            {
                $queryData['module_edit'] = 1;
            }
            if(isset($module_delete) && $module_delete == 1)
            {
                $queryData['module_delete'] = 1;
            }
            if(isset($module_print) && $module_print == 1)
            {
                $queryData['module_print'] = 1;
            }
            if(isset($module_email) && $module_email == 1)
            {
                $queryData['module_email'] = 1;
            }
            if(isset($module_download) && $module_download == 1)
            {
                $queryData['module_download'] = 1;
            }
            if(isset($module_upload) && $module_upload == 1)
            {
                $queryData['module_upload'] = 1;
            }
            if(isset($module_share) && $module_share == 1)
            {
                $queryData['module_share'] = 1;
            }
            
            if(isset($roleRights[$module_id]) && count($roleRights[$module_id])>0)
            {
                $role_right_id = $roleRights[$module_id]->role_right_id;

                RoleRight::where('role_right_id', $role_right_id)
                         ->update($queryData);
            }
            else
            {
                $queryData['module_id'] = $module_id;
                $queryData['role_id'] = $role_id;

                $roleRight = RoleRight::create($queryData);
            }
        }

        Session::flash('flash_message', 'Role Rights modified!');
        return redirect('role');
    }

    /**
     * Show the form for modifying module rights.
     *
     * @return void
     */
    public function moduleRights()
    {

        $pageName = 'Modify Module Rights';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/dist/select2/dist/js/select2.min.js","/js/modules/common_roleright.js");
        $css = array("/dist/datatables/jquery.dataTables.min.css","/dist/select2/dist/css/select2.min.css");
        $moduleArr = array();
        $modules = Module::active()
                        ->get();

        $moduleArr[0] = "Select Module";
        foreach($modules as $module)
        {
            $moduleArr[$module->module_id] = $module->module_name;
        }

        $moduleId = Input::get('id');

        if(Session::has('module_id'))
        {
            $moduleId = Session::get('module_id');
        }
        
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
        $data['moduleArr'] = $moduleArr;
        $data['moduleId'] = $moduleId;
        
        return view('roleright.addModuleRight', $data);
    }

    /**
     * Load the rights for selected module.
     *
     * @return void
     */
    public function loadRightsForModule()
    {        
        $id = Input::get('id');
        $roles = Role::active()->get();

        $moduleRightsList = RoleRight::where('module_id', '=' , $id)->get();

        $moduleRights = array();
        foreach ($moduleRightsList as $moduleRights) {
            $moduleRights[$moduleRights->role_id] = $moduleRights;
        }
        
        $data = array();
        $data['roles'] = $roles;
        $data['moduleRights'] = $moduleRights;
       
        $_viewToRender = View::make('roleright.partialview._loadModuleRight', $data);
        $_viewToRender->render();

        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Load the rights for selected module.
     *
     * @return void
     */
    public function validateModuleRight()
    {
        $module_id = Input::get('module_id');
        $roles = Role::active()->get();

        $roleRightsList = RoleRight::where('module_id', '=' , $module_id)->get();

        $roleRights = array();
        foreach ($roleRightsList as $roleRight) {
            $roleRights[$roleRight->role_id] = $roleRight;
        }

        foreach ($roles as $role) 
        {
            $role_id = $role->role_id;
            $module_add = Input::get('chk_add_module_'.$role_id);
            $module_view = Input::get('chk_view_module_'.$role_id);
            $module_edit = Input::get('chk_edit_module_'.$role_id);
            $module_delete = Input::get('chk_delete_module_'.$role_id);
            $module_print = Input::get('chk_print_module_'.$role_id);
            $module_email = Input::get('chk_email_module_'.$role_id);
            $module_download = Input::get('chk_download_module_'.$role_id);
            $module_upload = Input::get('chk_upload_module_'.$role_id);
            $module_share = Input::get('chk_share_module_'.$role_id);
            
            $queryData = array();
            $queryData['module_add'] = 0;
            $queryData['module_view'] = 0;
            $queryData['module_edit'] = 0;
            $queryData['module_delete'] = 0;
            $queryData['module_print'] = 0;
            $queryData['module_email'] = 0;
            $queryData['module_download'] = 0;
            $queryData['module_upload'] = 0;
            $queryData['module_share'] = 0;

            if(isset($module_add) && $module_add == 1)
            {
                $queryData['module_add'] = 1;
            }
            if(isset($module_view) && $module_view == 1)
            {
                $queryData['module_view'] = 1;
            }
            if(isset($module_edit) && $module_edit == 1)
            {
                $queryData['module_edit'] = 1;
            }
            if(isset($module_delete) && $module_delete == 1)
            {
                $queryData['module_delete'] = 1;
            }
            if(isset($module_print) && $module_print == 1)
            {
                $queryData['module_print'] = 1;
            }
            if(isset($module_email) && $module_email == 1)
            {
                $queryData['module_email'] = 1;
            }
            if(isset($module_download) && $module_download == 1)
            {
                $queryData['module_download'] = 1;
            }
            if(isset($module_upload) && $module_upload == 1)
            {
                $queryData['module_upload'] = 1;
            }
            if(isset($module_share) && $module_share == 1)
            {
                $queryData['module_share'] = 1;
            }
            
            if(isset($roleRights[$role_id]) && count($roleRights[$role_id])>0)
            {
                $role_right_id = $roleRights[$role_id]->role_right_id;

                RoleRight::where('role_right_id', $role_right_id)
                         ->update($queryData);
            }
            else
            {
                $queryData['module_id'] = $module_id;
                $queryData['role_id'] = $role_id;

                $roleRight = RoleRight::create($queryData);
            }
        }

        Session::flash('flash_message', 'Role Rights modified!');

        return redirect('role');
    }
}
