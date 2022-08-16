<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Module;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\RoleRight;
use Config;

class ModuleController extends Controller
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

        $this->page_icon = "<i class='fa fa-cubes'></i>";
        $this->page_title = "Module";
        $this->page_title_link = "module";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_module');
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

        $pageName = 'Module List';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_module.js");
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

        return view('module.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function moduleDatatable()
    {        
        $modules = Module::select(['module_id', 'module_name']);

        return Datatables::of($modules)
                ->add_column('action', function($module) {
                    return $this->getModuleDatatableButton($module->module_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getModuleDatatableButton($id)
    {
        $buttonHtml = "";
        $buttonHtml .= '&nbsp;<button onclick="viewModule('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        $buttonHtml .= '&nbsp;<button onclick="editModule('.$id.');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';       
        $buttonHtml .= '&nbsp;<button onclick="deleteModule('.$id.');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';     
        return $buttonHtml;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        $pageName = 'Add Module';    
        $js = array("/js/modules/common_module.js");
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
        
        return view('module.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function store(Request $request)
    {        
        $module = Module::create($request->all());
        $module->created_by = $this->userId;
        $module->save();
        $moduleId = $module->module_id;

        Session::flash('flash_message', 'Module added!');

        
        Session::flash('module_id', $moduleId);

        return redirect('modifyModuleRights');
    }

    /**
     * Validate module name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateModuleName()
    {        
        $id = Input::get('moduleId');
        $name = Input::get('moduleName');

        if($id > 0)
        {
            $moduleData = Module::where('module_name','=',$name)
                            ->where('module_id','!=',$id)
                            ->exists()
                            ->get();    
        }
        else
        {
            $moduleData = Module::where('module_name','=',$name)
                            ->exists()
                            ->get();    
        }
              
        
        if(count($moduleData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        echo json_encode(array('valid' => $isAvailable, 'name' => $name));
    }

    /**
     * Validate module name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {        
        $id = Input::get('moduleId');

        $isAvailable = 1;
        $msg = "";

        $moduleData = array();

        /*$moduleData = Module::where('module_name','=',$name)
                                    ->where('module_id','!=',$id)
                                    ->exists()
                                    ->get(); */
        
        if(count($moduleData)>0)
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.module_unavailable');
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
        $id = Input::get('moduleId');

        if($id <= 0)
        {
            return redirect('module');
        }

        $module = Module::findOrFail($id);

        $pageName = 'Module Details';    
        $js = array("/js/modules/common_module.js");
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
        $data['module'] = $module;

        return view('module.show', $data);
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
    	$id = Input::get('moduleId');

        if($id <= 0)
        {
            return redirect('module');
        }

        $module = Module::findOrFail($id);

        $pageName = 'Edit Module';    
        $js = array("/js/modules/common_module.js");
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
        $data['module'] = $module;

        return view('module.edit', $data);
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
        $id = $request->input('moduleId');
        $module = Module::findOrFail($id);
        $module->update($request->all());
        $module->updated_by = $this->userId;
        $module->save();

        Session::flash('flash_message', 'Module updated!');

        return redirect('module');
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
    	$id = Input::get('moduleId');
        
        $module = Module::findOrFail($id);
        $module->is_deleted = 1;
        $module->deleted_by = $this->userId;
        $module->updated_by = $this->userId;
        $module->save();

        Module::destroy($id);

        Session::flash('flash_message', 'Module deleted!');

        return redirect('module');
    }
}
