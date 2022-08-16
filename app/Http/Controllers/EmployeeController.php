<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Role;
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
use Hash;


class EmployeeController extends Controller
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

        $this->page_icon = "<i class='fa fa-users'></i>";
        $this->page_title = "Employee";
        $this->page_title_link = "employee";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_employee');
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

        $pageName = 'Employee List';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_employee.js");
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

        return view('employee.index', $data);
    }

    public function uploadExcel()
    {
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $pageName = 'Upload Excel';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_employee.js");
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

        return view('employee.uploadExcel', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function employeeDatatable()
    {      

        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $employees = User::leftJoin('departments', 'departments.department_id', '=', 'users.department_id')
                         ->select(['user_id', 'employee_no', 'employee_name', 'department_name', \DB::raw('concat(user_id, "_", users.is_active) as status')]);

        return Datatables::of($employees)
                ->removeColumn('user_id')
                ->addColumn('action', function($employee) {
                    return $this->getEmployeeDatatableButton($employee->user_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getEmployeeDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewEmployee('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="editEmployee('.$id.');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';       
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteEmployee('.$id.');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';     
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

       /* Passing Role Id*/
        $roleArr = array();
        $roles = Role::active()
                              ->get();

        $roleArr[0] = "Select Role";
        foreach($roles as $role)
        {
            $roleArr[$role->role_id] = $role->role_name;
        }
        /* Passing Role Id*/

        /* Passing Department Id*/
        $departmentArr = array();
        $departments = Department::active()
                              ->get();

        $departmentArr[0] = "Select Department";
        foreach($departments as $department)
        {
            $departmentArr[$department->department_id] = $department->department_name;
        }
        /* Passing Dpartment Id*/

        $pageName = 'Add Employee';    
        $js = array("/js/modules/common_employee.js","/dist/select2/dist/js/select2.min.js");
    	$css = array("/dist/select2/dist/css/select2.min.css");  

        
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
        $data['departmentArr'] = $departmentArr;
        
        return view('employee.create', $data);
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
        
        $employee = User::create($request->all());
        $employee->created_by = $this->userId;
        $employee->password = Hash::make($request->password);
        $employee->save();

        Session::flash('flash_message', 'Employee added!');

        return redirect('employee');
    }

    /**
     * Validate Employee nunber for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateEmployeeNo()
    {        
        $id = Input::get('user_id');
        $number = Input::get('employeeNo');

        if($id > 0)
        {
            $employeeNoData = User::where('employee_no','=',$number)
                                        ->where('user_id','!=',$id)
                                        ->exists()
                                        ->get();    
        }
        else
        {
            $employeeNoData = User::where('employee_no','=',$number)
                                        ->exists()->get();    
        }
              
        
        if(count($employeeNoData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        echo json_encode(array('valid' => $isAvailable, 'number' => $number));
    }

    /**
     * Validate User name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */

    public function validateUserName()
    {        
        $id = Input::get('user_id');
        $name = Input::get('userName');
        

        if($id > 0)
        {
            $userData = User::where('username','=',$name)
                                        ->where('user_id','!=',$id)
                                        ->exists()
                                        ->get();    
        }
        else
        {
            $userData = User::where('username','=',$name)
                                         ->exists()
                                         ->get();    
        }
              
        
        if(count($userData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate Employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {        
        $id = Input::get('user_id');

        $isAvailable = 1;
        $msg = "";

        $employeeData = array();

        /*$employeeData = User::where('department_name','=',$name)
                                    ->where('department_id','!=',$id)
                                    ->exists()
                                    ->get(); */
        
        if(count($employeeData)>0)
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.employee_unavailable');
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
     
        $id = Input::get('user_id');

        if($id <= 0)
        {
            return redirect('employee');
        }

        $employee = User::findOrFail($id);
        $employeeRole = $employee->role;
        
        $roleName = "";
        if(isset($employee->role))
        	$roleName = $employee->role->role_name;
        $employee->role_name = $roleName;
        
        $deptName = "";
        if(isset($employee->department))
        	$deptName = $employee->department->department_name;
        $employee->department_name = $deptName;

        $pageName = 'Employee Details';    
        $js = array("/js/modules/common_employee.js");
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
        $data['employee'] = $employee;
        $data['employeeRole'] = $employeeRole;
      //  $data['role'] = $role;
     //  $data['department'] = $department;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('employee.show', $data);
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

    	$id = Input::get('user_id');

        if($id <= 0)
        {
            return redirect('employee');
        }

        /* For Selecting Department*/
            $departmentArr = array();
            $departments = Department::active()->where('department_id', '!=', $id)->get();

            $departmentArr[0] = "Select Department";
            foreach($departments as $department)
            {
                $departmentArr[$department->department_id] = $department->department_name;
            }
        /* For Selecting Department*/
        
       /* For Selecting Role*/
            $roleArr = array();
            $roles = Role::active()->where('role_id', '!=', $id)->get();

            $roleArr[0] = "Select Role";
            foreach($roles as $role)
            {
                $roleArr[$role->role_id] = $role->role_name;
            }
        /* For Selecting Role*/

        $employee = User::findOrFail($id);

        $pageName = 'Edit Employee';    
        $js = array("/js/modules/common_employee.js","/dist/select2/dist/js/select2.min.js");
        $css = array("/dist/select2/dist/css/select2.min.css");
                    
        
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
        $data['employee'] = $employee;
        $data['roleArr'] = $roleArr;
        $data['departmentArr'] = $departmentArr;

        return view('employee.edit', $data);
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

        $id = $request->input('user_id');
        $employee = User::findOrFail($id);
        $employee->update($request->all());
        $employee->updated_by = $this->userId;
        $employee->save();

        Session::flash('flash_message', 'Employee updated!');

        return redirect('employee');
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

    	$id = Input::get('user_id');
        
        $employee = User::findOrFail($id);
        $employee->is_deleted = 1;
        $employee->deleted_by = $this->userId;
        $employee->updated_by = $this->userId;
        $employee->save();

        User::destroy($id);

        Session::flash('flash_message', 'Employee deleted!');

        return redirect('employee');
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

        $id = Input::get('user_id');
        $statusActive = Input::get('statusActive');

        $employee = User::findOrFail($id);
        $employee->is_active = $statusActive;
        $employee->updated_by = $this->userId;
        $employee->save();

        Session::flash('flash_message', 'User status changed!');

        return redirect('employee');
    }

}
