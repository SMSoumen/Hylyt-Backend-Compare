<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\Organization;
use App\Models\Org\OrgReferralCode;
use App\Models\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\User;
use App\Models\RoleRight;
use App\Models\Module;
use Config;
use Hash;
use View;
use Response;
use File;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Controllers\CommonFunctionController;
use App\Libraries\MailClass;
use Crypt;
use App\Http\Traits\OrgCloudMessagingTrait;

class OrgReferralCodeController extends Controller
{
    use OrgCloudMessagingTrait;
    
    public $page_icon = "";
	public $page_title = "";
	public $breadcrumbArr = array();
    public $breadcrumbLinkArr = array();
    
    public $userId=0;
	public $roleId=0;
	public $modulePermissions="";
	public $module="";
    public $userToken = NULL; 
    public $userDetails = NULL;
	
	public function __construct()
    {
        $encUserToken = Input::get('usrToken');
        
        if(isset($encUserToken) && $encUserToken != "")
        {
            $this->userToken = $encUserToken;
            $this->userId = Crypt::decrypt($this->userToken);
            $user = User::active()->byId($this->userId)->first();

            if(isset($user))
            {
                $this->userDetails = $user;
                $this->roleId = $user->role_id;

                $this->module = Config::get('app_config_module.mod_organization_referral_code');
		        $modules = Module::where('module_name', '=', $this->module)->exists()->first();
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
    public function loadReferralCodeView()
    {  
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg", 'userId' => $this->userId, 'userToken' => $this->userToken );
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

            $_viewToRender = View::make('orgreferralcode.index', $data);
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
    public function orgReferralCodeDatatable()
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
            $templates = OrgReferralCode::select([ 'referral_code_id', 'referral_code', 'expiration_date', 'allotted_days', 'user_count', 'allotted_quota_in_gb', 'is_active' ]);

            return Datatables::of($templates)
                    ->remove_column('referral_code_id')
                    ->remove_column('is_active')
                    ->add_column('status', function($referralCode) {
                        return sracEncryptNumberData($referralCode->referral_code_id)."_".$referralCode->is_active;
                    })
                    ->add_column('action', function($referralCode) {
                        return $this->getReferralCodeDatatableButton($referralCode->referral_code_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getReferralCodeDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        
        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditReferralCodeModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteReferralCode(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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

            $id = Input::get('codeId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $referralCode = NULL;
            if($id > 0)
            {
                $referralCode = OrgReferralCode::byId($id)->first();
                if(isset($referralCode))
                {
                    $referralCode->expDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($referralCode->expiration_date));
                }
                $pageName = 'Edit';     
            }

            $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['referralCode'] = $referralCode;
            $data['page_description'] = $pageName.' '.'Organization Referral Code';
            $data['intJs'] = $intJs;
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgreferralcode.partialview._addEditModal', $data);
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
        $id = $request->input('codeId');  
        $referralCodeText = $request->input('referral_code');
        $userCount = $request->input('user_count');
        $quotaAllotted = $request->input('allotted_quota_in_gb');

        $status = 0;
        $msg = "";
        $response = array();

        $id = sracDecryptNumberData($id);

        $isAdd = FALSE;

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;

                $referralCode = OrgReferralCode::byId($id)->first();
                
                $referralCode->update($request->all());
                $referralCode->updated_by = $this->userId;

                $msg = 'Referral Code updated!';  
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

                $referralCode = OrgReferralCode::create($request->all());
                $referralCode->created_by = $this->userId;
                $referralCode->is_active = 1;
                $isAdd = TRUE;

                $msg = 'Referral Code added!';
            }
        }

        if(isset($referralCode))
        {
            $status = 1;
            $referralCode->expiration_date = date(Config::get('app_config.date_db_format'), strtotime($request->expiration_date));
            $referralCode->save();
        }
                
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }
    
    /**
     * Validate User name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */

    public function validateOrgReferralCode()
    {        
        $id = Input::get('codeId');
        $referralCodeText = Input::get('referral_code');

        $id = sracDecryptNumberData($id);
        
        if($id > 0)
        {
            $refData = OrgReferralCode::where('referral_code','=',$referralCodeText)
                                        ->where('referral_code_id','!=',$id)
                                        ->get();    
        }
        else
        {
            $refData = OrgReferralCode::where('referral_code','=',$referralCodeText)->get();    
        }              
        
        if(count($refData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE; 
            
        echo json_encode(array('valid' => $isAvailable));
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
        $id = Input::get('codeId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $referralCode = OrgReferralCode::byId($id)->first();
        
        if(isset($referralCode) && $referralCode->is_active == 1)
        {
            $isAvailable = 0;
            $msg = "Cannot delete an active referral code";
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
        $status = 0;
        $msg = "";

    	$id = Input::get('codeId');

        $id = sracDecryptNumberData($id);
        
        if($id > 0)
        {
            if($this->modulePermissions->module_delete == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $referralCode = OrgReferralCode::byId($id)->first();
            
	            if(isset($referralCode))
		        {
		            $status = 1;
                	$msg = 'Referral Code deleted!';
					
			        $referralCode->is_deleted = 1;
			        $referralCode->deleted_by = $this->userId;
			        $referralCode->updated_by = $this->userId;
			        $referralCode->save();
			        
			        $referralCode->delete();
		        }
		        else
		        {
					$status = -1;
					$msg = "Invalid data";
				}
			}
        }
        else
        {
			$status = -1;
			$msg = "Invalid data";
		}

        echo json_encode(array('status' => $status, 'msg' => $msg));
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
        $status = 0;

        $id = Input::get('codeId');
        $statusActive = Input::get('statusActive');

        $id = sracDecryptNumberData($id);

        $referralCode = OrgReferralCode::byId($id)->first();
        
        if(isset($referralCode))
        {
            $referralCode->is_active = $statusActive;
            $referralCode->updated_by = $this->userId;
            $referralCode->save();
            
            $msg = 'Referral Code status changed!';            
        }

        echo json_encode(array('status' => $status, 'msg' => $msg));
    }
}
