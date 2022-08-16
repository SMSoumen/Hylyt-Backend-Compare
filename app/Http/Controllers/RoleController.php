<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\RoleRight;
use App\Models\Module;
use Config;
use App\Models\User;

class RoleController extends Controller
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

        $this->module = Config::get('app_config_module.mod_role');
        $modules = Module::where('module_name', '=', $this->module)->exists()->first();
        $rights = $modules->right()->where('role_id', '=', $this->role)->first();
        $this->modulePermissions = $rights;
    }
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {    
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $pageName = 'Role List';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_role.js");
    	$css = array("/dist/datatables/jquery.dataTables.min.css");             
        
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
        $data['modulePermissions'] = $this->modulePermissions;

        return view('role.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function roleDatatable()
    {  
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $roles = Role::select(['role_id', 'role_name']);

        return Datatables::of($roles)
                ->add_column('action', function($role) {
                    return $this->getRoleDatatableButton($role->role_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getRoleDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewRole('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="editRoleRight('.$id.');" class="btn btn-xs btn-warning"><i class="fa fa-check-square-o"></i>&nbsp;&nbsp;Rights</button>';
            $buttonHtml .= '&nbsp;<button onclick="editRole('.$id.');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteRole('.$id.');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>'; 
        }
        return $buttonHtml;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        if($this->modulePermissions->module_add == 0){
            return redirect('permissionDenied');
        }

        $pageName = 'Add Role';    
        $js = array("/js/modules/common_role.js");
    	$css = array();             
        
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
        
        return view('role.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function store(Request $request)
    { 
        if($this->modulePermissions->module_add == 0){
            return redirect('permissionDenied');
        }

        $role = Role::create($request->all());
        $role->created_by = $this->userId;
        $role->save();
        $roleId = $role->role_id;

        Session::flash('flash_message', 'Role added!');
        
        Session::flash('role_id', $roleId);

        return redirect('modifyRoleRights');
    }

    /**
     * Validate role name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateRoleName()
    {        
        $id = Input::get('roleId');
        $name = Input::get('roleName');

        if($id > 0)
        {
            $roleData = Role::where('role_name','=',$name)
                            ->where('role_id','!=',$id)
                            ->exists()
                            ->get();    
        }
        else
        {
            $roleData = Role::where('role_name','=',$name)
                            ->exists()
                            ->get();    
        }
              
        
        if(count($roleData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate role name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {        
        $id = Input::get('roleId');

        $isAvailable = 1;
        $msg = "";

        $roleData = array();
        $employees = User::where('role_id','=',$id)->first();
        
        if(isset($employees))
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.role_unavailable');
        }
        else
        {
            $isAvailable = 1;
        }

        echo json_encode(array('status' => $isAvailable, 'msg' => $msg));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function show()
    {   
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('roleId');

        if($id <= 0)
        {
            return redirect('role');
        }

        $role = Role::findOrFail($id);

        $pageName = 'Role Details';    
        $js = array("/js/modules/common_role.js");
        $css = array();             
        
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
        $data['modulePermissions'] = $this->modulePermissions;
        $data['role'] = $role;

        return view('role.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function edit()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

    	$id = Input::get('roleId');

        if($id <= 0)
        {
            return redirect('role');
        }

        $role = Role::findOrFail($id);

        $pageName = 'Edit Role';    
        $js = array("/js/modules/common_role.js");
        $css = array();             
        
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
        $data['role'] = $role;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('role.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function update(Request $request)
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = $request->input('roleId');
        $role = Role::findOrFail($id);
        $role->update($request->all());
        $role->updated_by = $this->userId;
        $role->save();

        Session::flash('flash_message', 'Role updated!');

        return redirect('role');
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
        if($this->modulePermissions->module_delete == 0){
            return redirect('permissionDenied');
        }

    	$id = Input::get('roleId');
        
        $role = Role::findOrFail($id);
        $role->is_deleted = 1;
        $role->deleted_by = $this->userId;
        $role->updated_by = $this->userId;
        $role->save();

        Role::destroy($id);

        Session::flash('flash_message', 'Role deleted!');

        return redirect('role');
    }
}
