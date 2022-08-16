<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;
use App\Models\Api\DeletedAppuser;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserOrder;
use App\Models\Api\AppuserAddressSuggestion;
use App\Models\Seller;
use App\Models\Module;
use DB;
use Response;
use App\Models\Api\AppuserConstant;

class DashboardController extends Controller
{
    public $page_icon = "";
	public $page_title = "";
	public $breadcrumbArr = array();
    public $breadcrumbLinkArr = array();
    
    public $userData="";
    public $userId;
	public $role="";
	public $moduleDashboardMetricsPermissions = "";
	public $moduleDashboardMetrics = "";
	
	public function __construct()
    {
        $userSession = Session::get('user');
        
        if (!Session::has('user') || !isset($userSession) || count($userSession) == 0 || $userSession[0]['id'] <= 0) 
        {
            Redirect::to('login')->send();
        }

        $this->page_icon = "<i class='fa fa-dashboard'></i>";
    	$this->page_title = "Dashboard";
    	$this->page_title_link = "dashboard";
        
        array_push($this->breadcrumbArr, $this->page_title);
        array_push($this->breadcrumbLinkArr, $this->page_title_link);

        $this->userData = $userSession[0];
        $this->userId = $this->userData['id'];
        $this->role = $this->userData['role'];

        $this->moduleDashboardMetrics = Config::get('app_config_module.mod_dashboard_metrics');
        $modules = Module::where('module_name', '=', $this->moduleDashboardMetrics)->exists()->first();
        $rights = $modules->right()->where('role_id', '=', $this->role)->first();
        $this->moduleDashboardMetricsPermissions = $rights;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {    
        $pageName = 'Report';    
        $js = array();
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
        $data['viewDashboardMetrics'] = $this->moduleDashboardMetricsPermissions->module_view;
        

        return view('dashboard.index', $data);
    }   
    
    /**
     * Get Stats for Dashboard.
     *
     * @return void
     */
    public function loadDashboardStats()
    {    
    	$response = array();
    	$userCnt = 0;
    	$delUserCnt = 0;
    	$mbAllotted = 0;
    	$mbUsed = 0;
    			
		$verifiedUsers = Appuser::where('is_verified','=', 1)->get();	
				
		$regUsers = Appuser::get();	
		$userCnt = count($regUsers);  
		
		$delUsers = DeletedAppuser::get();  	
		$delUserCnt = count($delUsers);    	
		
		$kbAllotted = AppuserConstant::sum('attachment_kb_allotted');	
		$kbAvailable = AppuserConstant::sum('attachment_kb_available');	
		$kbUsed = $kbAllotted - $kbAvailable;	
		
		$mbAllotted = round($kbAllotted/1024);
		$mbUsed = round($kbUsed/1024);
    	
    	$response['userCnt'] = $userCnt;
    	$response['delUserCnt'] = $delUserCnt;
    	$response['mbAllotted'] = $mbAllotted;
    	$response['mbUsed'] = $mbUsed;
    
        return Response::json($response);
    }   
}