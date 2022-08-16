<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Department;
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


class DepartmentController extends Controller
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

        $this->page_icon = "<i class='fa fa-sitemap'></i>";
        $this->page_title = "Department";
        $this->page_title_link = "department";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_department');
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

        $pageName = 'Department List';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_department.js");
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

        return view('department.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function departmentDatatable()
    {
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $departments = Department::select(['department_id', 'department_name']);

        return Datatables::of($departments)
                ->remove_column('department_id')
                ->add_column('action', function($department) {
                    return $this->getDepartmentDatatableButton($department->department_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getDepartmentDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewDepartment('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="editDepartment('.$id.');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteDepartment('.$id.');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

        $pageName = 'Add Department';    
        $js = array("/js/modules/common_department.js");
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
        
        return view('department.create', $data);
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
        
        $department = Department::create($request->all());
        $department->created_by = $this->userId;
        $department->save();

        Session::flash('flash_message', 'Department added!');

        return redirect('department');
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

        if($id > 0)
        {
            $departmentData = Department::where('department_name','=',$name)
                                        ->where('department_id','!=',$id)
                                        ->exists()
                                        ->get();    
        }
        else
        {
            $departmentData = Department::where('department_name','=',$name)
                                        ->exists()
                                        ->get();    
        }              
        
        if(count($departmentData)>0)
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

        $isAvailable = 1;
        $msg = "";

        $employees = User::where('department_id','=',$id)->first();
        
        if(isset($employees))
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

        $id = Input::get('deptId');

        if($id <= 0)
        {
            return redirect('department');
        }

        $department = Department::findOrFail($id);

        $pageName = 'Department Details';    
        $js = array("/js/modules/common_department.js");
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
        $data['department'] = $department;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('department.show', $data);
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

    	$id = Input::get('deptId');

        if($id <= 0)
        {
            return redirect('department');
        }

        $department = Department::findOrFail($id);

        $pageName = 'Edit Department';    
        $js = array("/js/modules/common_department.js");
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
        $data['department'] = $department;

        return view('department.edit', $data);
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

        $id = $request->input('deptId');
        $department = Department::findOrFail($id);
        $department->update($request->all());
        $department->updated_by = $this->userId;
        $department->save();

        Session::flash('flash_message', 'Department updated!');

        return redirect('department');
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

    	$id = Input::get('deptId');
        
        $department = Department::findOrFail($id);
        $department->is_deleted = 1;
        $department->deleted_by = $this->userId;
        $department->updated_by = $this->userId;
        $department->save();

        Department::destroy($id);

        Session::flash('flash_message', 'Department deleted!');

        return redirect('department');
    }

    /**
     * Change the status of specified resource.
     *
     *
     * @return void
     */
    public function changeStatus()
    {
        if($this->modulePermissions->module_edit == 0){
            return redirect('permissionDenied');
        }

        $id = Input::get('deptId');
        $statusActive = Input::get('statusActive');

        $department = Department::findOrFail($id);
        $department->is_active = $statusActive;
        $department->updated_by = $this->userId;
        $department->save();

        Session::flash('flash_message', 'Department status changed!');

        return redirect('department');
    }
}
