<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\ThoughtTip;
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


class ThoughtTipController extends Controller
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

        $this->page_icon = "<i class='fa fa-lightbulb-o'></i>";
        $this->page_title = "Thought/Tip";
        $this->page_title_link = "thoughtTip";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->module = Config::get('app_config_module.mod_thought_tip');
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

        $pageName = 'Thought/Tip List';    
        $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js","/js/modules/common_thoughtTip.js");
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

        return view('thoughtTip.index', $data);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function thoughtTipDatatable()
    {
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }
        $dateFormat = Config::get('app_config.sql_date_db_format');

        $thoughtTips = ThoughtTip::select(['thought_tip_id', \DB::raw("IF(for_date <> '', DATE_FORMAT(for_date, '$dateFormat'), '') as for_date"), 'thought_tip_text', \DB::raw('concat(thought_tip_id, "_", is_active) as status') ]);

        return Datatables::of($thoughtTips)
                ->remove_column('thought_tip_id')
                ->add_column('action', function($thoughtTip) {
                    return $this->getThoughtTipDatatableButton($thoughtTip->thought_tip_id);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getThoughtTipDatatableButton($id)
    {
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewThoughtTip('.$id.');" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="editThoughtTip('.$id.');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteThoughtTip('.$id.');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

        $pageName = 'Add Thought/Tip';    
        $js = array("/js/modules/common_thoughtTip.js","/dist/ckeditor/ckeditor.js","/dist/ckeditor/adapters/jquery.js","/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js","/dist/bootstrap-datepicker/dist/js/common_dt.js");
    	$css = array("/dist/bootstrap-datepicker/dist/css/datepicker.css");             
        
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
        
        return view('thoughtTip.create', $data);
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
        
        $thoughtTip = ThoughtTip::create($request->all());
        $thoughtTip->for_date = date(Config::get('app_config.date_db_format'), strtotime($request->for_date));
        $thoughtTip->created_by = $this->userId;
        $thoughtTip->save();

        Session::flash('flash_message', 'ThoughtTip added!');

        return redirect('thoughtTip');
    }

    /**
     * Validate thoughtTip name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateThoughtTipDate()
    {        
        $id = Input::get('thoughtTipId');
        $forDate = Input::get('forDate');

        $forDateDb = date(Config::get('app_config.date_db_format'), strtotime($forDate));

        if($id > 0)
        {
            $thoughtTipData = ThoughtTip::whereDate('for_date','=',$forDateDb)
                                        ->where('thought_tip_id','!=',$id)
                                        ->exists()
                                        ->get();    
        }
        else
        {
            $thoughtTipData = ThoughtTip::whereDate('for_date','=',$forDateDb)
                                        ->exists()
                                        ->get();    
        }              
        
        if(count($thoughtTipData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable, 'forDate' => $forDate));
    }

    /**
     * Validate thoughtTip name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('thoughtTipId');

        $isAvailable = 1;
        $msg = "";

        $employees = array();
        if(count($employees)>0)
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.thought_tip_unavailable');
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

        $id = Input::get('thoughtTipId');

        if($id <= 0)
        {
            return redirect('thoughtTip');
        }

        $thoughtTip = ThoughtTip::findOrFail($id);

        $pageName = 'Thought/Tip Details';    
        $js = array("/js/modules/common_thoughtTip.js");
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
        $data['thoughtTip'] = $thoughtTip;
        $data['modulePermissions'] = $this->modulePermissions;

        return view('thoughtTip.show', $data);
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

    	$id = Input::get('thoughtTipId');

        if($id <= 0)
        {
            return redirect('thoughtTip');
        }

        $thoughtTip = ThoughtTip::findOrFail($id);
        if(isset($thoughtTip))
        {
            $thoughtTip->for_date_disp = date(Config::get('app_config.date_disp_format'), strtotime($thoughtTip->for_date));
        }    

        $pageName = 'Edit Thought/Tip';    
        $js = array("/js/modules/common_thoughtTip.js","/dist/ckeditor/ckeditor.js","/dist/ckeditor/adapters/jquery.js","/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js","/dist/bootstrap-datepicker/dist/js/common_dt.js");
        $css = array("/dist/bootstrap-datepicker/dist/css/datepicker.css");          
        
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
        $data['thoughtTip'] = $thoughtTip;

        return view('thoughtTip.edit', $data);
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

        $id = $request->input('thoughtTipId');
        $thoughtTip = ThoughtTip::findOrFail($id);
        $thoughtTip->update($request->all());
        $thoughtTip->for_date = date(Config::get('app_config.date_db_format'), strtotime($request->for_date));
        $thoughtTip->updated_by = $this->userId;
        $thoughtTip->save();

        Session::flash('flash_message', 'ThoughtTip updated!');

        return redirect('thoughtTip');
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

    	$id = Input::get('thoughtTipId');
        
        $thoughtTip = ThoughtTip::findOrFail($id);
        $thoughtTip->is_deleted = 1;
        $thoughtTip->deleted_by = $this->userId;
        $thoughtTip->updated_by = $this->userId;
        $thoughtTip->save();

        ThoughtTip::destroy($id);

        Session::flash('flash_message', 'ThoughtTip deleted!');

        return redirect('thoughtTip');
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

        $id = Input::get('thoughtTipId');
        $statusActive = Input::get('statusActive');

        $thoughtTip = ThoughtTip::findOrFail($id);
        $thoughtTip->is_active = $statusActive;
        $thoughtTip->updated_by = $this->userId;
        $thoughtTip->save();

        Session::flash('flash_message', 'ThoughtTip status changed!');

        return redirect('thoughtTip');
    }
}
