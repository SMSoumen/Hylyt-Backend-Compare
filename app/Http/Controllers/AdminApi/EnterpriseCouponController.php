<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\EnterpriseCoupon;
use App\Models\Org\EnterpriseCouponCode;
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
use App\Libraries\CommonFunctionClass;
use App\Libraries\FileUploadClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Controllers\CommonFunctionController;
use App\Libraries\MailClass;
use Crypt;

class EnterpriseCouponController extends Controller
{    
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

                $this->module = Config::get('app_config_module.mod_enterprise_coupon');
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
    public function loadCouponView()
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

            $_viewToRender = View::make('enterpriseCoupon.index', $data);
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
    public function enterpriseCouponDatatable()
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
            $coupons = EnterpriseCoupon::select([ 'enterprise_coupon_id', 'coupon_name', 'coupon_count', 'coupon_validity_start_date', 'coupon_validity_end_date', 'subscription_validity_days', 'allotted_space_in_gb', 'is_active', 'is_generated' ]);

            return Datatables::of($coupons)
                    ->remove_column('enterprise_coupon_id')
                    ->remove_column('is_active')
                    ->remove_column('is_generated')
                    ->add_column('status', function($coupon) {
                        return sracEncryptNumberData($coupon->enterprise_coupon_id)."_".$coupon->is_active;
                    })
                    ->add_column('generation', function($coupon) {
                        return $this->getCouponGenerationHtml($coupon->enterprise_coupon_id, $coupon);
                    })
                    ->add_column('action', function($coupon) {
                        return $this->getCouponDatatableButton($coupon->enterprise_coupon_id, $coupon);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getCouponGenerationHtml($id, $coupon)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($coupon->is_generated == 1)
        {
            $buttonHtml = "Generated";
        }
        else if($coupon->is_active == 1 && $this->modulePermissions->module_edit == 1)
        {
            $buttonHtml = '&nbsp;<button onclick="generateCouponCodes(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Generate</button>';
        }
        return $buttonHtml;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getCouponDatatableButton($id, $coupon)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1 && $coupon->is_generated == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewCouponDetails(\''.$id.'\');" class="btn btn-xs btn-info"><i class="fa fa-edit"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1 && $coupon->is_generated == 0)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditCouponModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1 && $coupon->is_generated == 0)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteCoupon(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        }
        return $buttonHtml;
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function loadCouponDetailView()
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

            $id = Input::get('couponId');
            
            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $coupon = NULL;
            if($id > 0)
            {
                $coupon = EnterpriseCoupon::byId($id)->first();
                if(isset($coupon))
                {
                    $status = 1;
                    $coupon->couponValidityStartDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($coupon->coupon_validity_start_date));
                    $coupon->couponValidityEndDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($coupon->coupon_validity_end_date));

                    $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/bootbox/bootbox.min.js");
                    $css = array("/dist/datatables/jquery.dataTables.min.css");             
                    
                    $data = array();
                    $data['id'] = sracEncryptNumberData($id);
                    $data['js'] = $js;
                    $data['css'] = $css;
                    $data['coupon'] = $coupon;
                    $data['userDetails'] = $this->userDetails;
                    $data['modulePermissions'] = $this->modulePermissions;
                    $data['usrtoken'] = $this->userToken;

                    $_viewToRender = View::make('enterpriseCoupon.details', $data);
                    $_viewToRender = $_viewToRender->render();

                    $response['view'] = $_viewToRender; 
                }
                else
                {
                    $status = -1;
                    $msg = "Invalid data";
                }    
            }
            else
            {
                $status = -1;
                $msg = "Invalid data";
            }         
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
    public function enterpriseCouponCodeDatatable()
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
            $id = Input::get('couponId');
            $utilizationStatus = Input::get('utilizationStatus');

            $id = sracDecryptNumberData($id);

        	$dateFormat = Config::get('app_config.sql_datetime_disp_format');

        	$baseTableName = 'enterprise_coupon_codes';

            $couponCodes = EnterpriseCouponCode::ofCoupon($id)->byUtilizationStatus($utilizationStatus)
        								->joinUtilizedByOrganizationTable()
                                        ->joinUtilizedByOrganizationAdminTable()
            							->select([ $baseTableName.'.enterprise_coupon_code_id', 'coupon_code', \DB::raw("IF(is_utilized = 1, 'Utilized', 'Pending') as utilization"), \DB::raw("utilized_at"), \DB::raw("CONCAT(regd_name, ' [', user_ip_address, ']<br/>', fullname, ' (', admin_email, ')') as utilized_by_organization") ]);

            return Datatables::of($couponCodes)
                    ->remove_column('enterprise_coupon_code_id')
                    // ->remove_column('utilized_at')
                    // ->remove_column('is_utilized')
                    // ->add_column('generation', function($couponCode) {
                    //     return $this->getCouponUtilizationHtml($couponCode);
                    // })
                    // ->add_column('action', function($couponCode) {
                    //     return $this->getCouponCodeDatatableButton($couponCode->enterprise_coupon_code_id);
                    // })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getCouponUtilizationHtml($couponCode)
    {
        $buttonHtml = "";
        if($couponCode->is_utilized == 1)
        {
            $buttonHtml = "Utilized at ".$couponCode->utilized_at;
        }
        else
        {
            $buttonHtml = "Pending";            
        }
        return $buttonHtml;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getCouponCodeDatatableButton($id)
    {
        $id = sracDecryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadViewCouponCodeDetails(\''.$id.'\');" class="btn btn-xs btn-info"><i class="fa fa-edit"></i>&nbsp;&nbsp;View</button>';
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

            $id = Input::get('couponId');

            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $coupon = NULL;
            if($id > 0)
            {
                $coupon = EnterpriseCoupon::byId($id)->first();
                if(isset($coupon))
                {
                    $coupon->couponValidityStartDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($coupon->coupon_validity_start_date));
                    $coupon->couponValidityEndDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($coupon->coupon_validity_end_date));
                }
                $pageName = 'Edit';     
            }

            $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['coupon'] = $coupon;
            $data['page_description'] = $pageName.' '.'Enterprise Coupon';
            $data['intJs'] = $intJs;
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('enterpriseCoupon.partialview._addEditModal', $data);
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
        $id = $request->input('couponId');  
        $couponName = $request->input('coupon_name');
        $couponCodePrefix = $request->input('coupon_code_prefix');

        $id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";
        $response = array();

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

                $coupon = EnterpriseCoupon::byId($id)->first();
                
                $coupon->update($request->all());
                $coupon->updated_by = $this->userId;

                $msg = 'Coupon updated!';  
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

                $coupon = EnterpriseCoupon::create($request->all());
                $coupon->created_by = $this->userId;
                $coupon->is_active = 1;
                $isAdd = TRUE;

                $msg = 'Coupon added!';
            }
        }

        if(isset($coupon))
        {
            $status = 1;
            $coupon->coupon_validity_start_date = date(Config::get('app_config.date_db_format'), strtotime($request->coupon_validity_start_date));
            $coupon->coupon_validity_end_date = date(Config::get('app_config.date_db_format'), strtotime($request->coupon_validity_end_date));
            $coupon->available_coupon_count = $coupon->coupon_count - $coupon->utilized_coupon_count;
            $coupon->save();
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

    public function validateCouponCodePrefix()
    {        
        $id = Input::get('couponId');
        $couponCodeText = Input::get('coupon_code_prefix');

        $id = sracDecryptNumberData($id);
        
        if($id > 0)
        {
            $couponData = EnterpriseCoupon::where('coupon_code_prefix','=',$couponCodeText)
                                        ->where('enterprise_coupon_id','!=',$id)
                                        ->get();    
        }
        else
        {
            $couponData = EnterpriseCoupon::where('coupon_code_prefix','=',$couponCodeText)->get();    
        }              
        
        if(count($couponData)>0)
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
        $id = Input::get('couponId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $coupon = EnterpriseCoupon::byId($id)->first();
        
        if(isset($coupon) && $coupon->is_active == 1)
        {
            $isAvailable = 0;
            $msg = "Cannot delete an active coupon";
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

        $id = Input::get('couponId');

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
                $coupon = EnterpriseCoupon::byId($id)->first();
            
                if(isset($coupon))
                {
                    $status = 1;
                    $msg = 'Coupon deleted!';
                    
                    $coupon->is_deleted = 1;
                    $coupon->deleted_by = $this->userId;
                    $coupon->updated_by = $this->userId;
                    $coupon->save();
                    
                    $coupon->delete();
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

        $id = Input::get('couponId');
        $statusActive = Input::get('statusActive');

        $id = sracDecryptNumberData($id);

        $coupon = EnterpriseCoupon::byId($id)->first();
        
        if(isset($coupon))
        {
            $coupon->is_active = $statusActive;
            $coupon->updated_by = $this->userId;
            $coupon->save();
            
            $msg = 'Coupon Code status changed!';            
        }

        echo json_encode(array('status' => $status, 'msg' => $msg));
    }

    /**
     * Validate Employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForGenerate()
    {        
        $id = Input::get('couponId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $coupon = EnterpriseCoupon::byId($id)->first();
        
        if(isset($coupon)>0)
        {
            if($coupon->is_generated == 1)
            {
                $isAvailable = 0;
                $msg = "Coupon already generated";
            }
            else if($coupon->is_active == 0)
            {
                $isAvailable = 0;
                $msg = "Cannot generate an inactive coupon";
            }
        }
        else
        {
            $isAvailable = 0;
            $msg = "No such coupon found";
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
    public function generate()
    {
        $status = 0;
        $msg = "";

        $id = Input::get('couponId');

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
                $coupon = EnterpriseCoupon::byId($id)->first();
            
                if(isset($coupon) && $coupon->is_active == 1 && $coupon->is_generated == 0)
                {
                    $status = 1;
                    $msg = 'Coupon codes generated!';

                    $couponCodePrefix = $coupon->coupon_code_prefix;
                    $couponCount = $coupon->coupon_count;
                    $couponCodeArr = array();

                    $genCouponCount = 0;
                    do
                    {
                        $genCouponCode = CommonFunctionClass::generateEnterpriseCouponCodeString($couponCodePrefix);
                        if(!in_array($genCouponCode, $couponCodeArr))
                        {
                            array_push($couponCodeArr, $genCouponCode);
                            $genCouponCount = count($couponCodeArr);
                        }
                    }
                    while($genCouponCount < $couponCount);

                    foreach ($couponCodeArr as $couponCode) {
                        $couponCodeIns = array();
                        $couponCodeIns['enterprise_coupon_id'] = $id;
                        $couponCodeIns['coupon_code'] = $couponCode;

                        $savedCouponCode = EnterpriseCouponCode::create($couponCodeIns);
                    }

                    $currTimestamp = CommonFunctionClass::getCurrentTimestamp();
                    
                    $coupon->is_generated = 1;
                    $coupon->generated_at = $currTimestamp;
                    $coupon->generated_by = $this->userId;
                    $coupon->updated_by = $this->userId;
                    $coupon->save();

                    $response['couponCodeArr'] = $couponCodeArr;
                    $response['couponCodePrefix'] = $couponCodePrefix;
                    $response['couponCount'] = $couponCount;
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
                
        $response['status'] = $status;
        $response['msg'] = $msg;
        
        return Response::json($response);
    }
}
