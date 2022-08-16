<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\AppKeyMapping;
use App\Models\Org\OrgReferralCode;
use App\Models\Org\EnterpriseCoupon;
use App\Models\Org\EnterpriseCouponCode;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationUser;
use App\Models\Org\OrganizationSubscription;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationServer;
use App\Models\Org\OrganizationChatRedirection;
use App\Models\Org\CmsRole;
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
use App\Libraries\CommonFunctionClass;

class OrganizationController extends Controller
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

                $this->module = Config::get('app_config_module.mod_organization');
                $modules = Module::where('module_name', '=', $this->module)->exists()->first();
                $rights = $modules->right()->where('role_id', '=', $this->roleId)->first();
                $this->modulePermissions = $rights;
            }
        }        
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function organizationDatatable()
    {
        if($this->modulePermissions->module_view == 0){
            return redirect('permissionDenied');
        }

        $onlyDeleted = Input::get('onlyDeleted');
        if(!isset($onlyDeleted) || $onlyDeleted != 1)
        {
            $onlyDeleted = 0;
        }
        
        $modelObj = New Organization;
        $orgTableName = $modelObj->table;

        $orgSubscriptionModelObj = New OrganizationSubscription;
        $orgSubscriptionTableName = $orgSubscriptionModelObj->table;

        /* $organizations = Organization::select([$orgTableName.'.organization_id', 'org_code', 'regd_name', 'system_name', 'phone', \DB::raw('user_count as alloc_usr_cnt'), \DB::raw('(user_count-used_user_count) as avail_usr_cnt'), \DB::raw('allotted_quota_in_gb as alloc_quota_gb'), \DB::raw('(ROUND(((allotted_quota_in_gb*1024)-used_quota_in_mb)/1024)) as avail_quota_gb'), 'ref_code', \DB::raw('concat('.$orgTableName.'.organization_id, "_", organizations.is_active) as status')])
            ->joinSubscriptionTable(); */

         // $organizations = Organization::select([$orgTableName.'.organization_id', \DB::raw('concat(regd_name, "<br/>", phone) as regd_name_phone'), 'org_code', \DB::raw('concat(user_count, "<br/>", (user_count-used_user_count)) as usr_cnt_met'), \DB::raw('concat(allotted_quota_in_gb, "<br/>", ((ROUND(((allotted_quota_in_gb*1024)-used_quota_in_mb)/1024)))) as quota_met'), 'ref_code', \DB::raw('concat('.$orgTableName.'.organization_id, "_", organizations.is_active) as status')])
         //    ->joinSubscriptionTable();

         $organizations = Organization::select([$orgTableName.'.organization_id', 'regd_name', 'org_code', 'email', $orgSubscriptionTableName.'.activation_date as activation_date', $orgSubscriptionTableName.'.expiration_date as expiration_date', \DB::raw('concat(user_count, "<br/>", (user_count-used_user_count)) as usr_cnt_met'), \DB::raw('concat(allotted_quota_in_gb, "<br/>", ((ROUND(((allotted_quota_in_gb*1024)-used_quota_in_mb)/1024)))) as quota_met'), 'ref_code', 'phone', 'organizations.is_active'])
            ->joinSubscriptionTable();

        if($onlyDeleted == 1)
        {
            $organizations->onlyDeleted();

            return Datatables::of($organizations)
                    ->onlyTrashed()
                    ->make();
        }
        else
        {
            return Datatables::of($organizations)
                    // ->remove_column('organization_id')
                    ->remove_column('is_active')
                    ->add_column('status', function($organization) {
                        return sracEncryptNumberData($organization->organization_id)."_".$organization->is_active;
                    })
                    ->addColumn('action', function($organization) {
                        return $this->getOrganizationDatatableButton($organization->organization_id);
                    })
                    ->make();
        }

        /*
        return Datatables::of($organizations)
                ->addColumn('action', function($organization) {
                    return $this->getOrganizationDatatableButton($organization->organization_id);
                })
                ->make();
        */
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getOrganizationDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);

        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewOrganizationUsers(\''.$id.'\');" class="btn btn-xs btn-orange"><i class="fa fa-users"></i>&nbsp;&nbsp;Users</button>';
        }
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewDeletedOrganizationUsers(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-users"></i>&nbsp;&nbsp;Deleted Users</button>';
        }
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewOrganization(\''.$id.'\', 1);" class="btn btn-xs btn-success"><i class="fa fa-arrows-alt"></i>&nbsp;&nbsp;View</button>';
        }
        if($this->modulePermissions->module_edit == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="viewOrganization(\''.$id.'\', 0);" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';       
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteOrganization(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';     
        }
        return $buttonHtml;
    }

    /**
     * Load scheme basic details.
     *
     * @return void
     */
    public function loadRegDetailsView()
    { 
        $isViewFlag = Input::get('isView');
        $organizationId = Input::get('orgId');

        $organizationId = sracDecryptNumberData($organizationId);
        
        $organization = NULL;
        if(isset($organizationId) && $organizationId > 0)
        {
            $organization = Organization::ofOrganization($organizationId)->first();
            if(isset($organization))
            {
                $url = "";
                if(isset($organization->logo_filename) && $organization->logo_filename != "")
                    $url = OrganizationClass::getOrgPhotoUrl($organizationId, $organization->logo_filename);
                $organization->url = $url;
                
                $decSeVerCode = NULL;
                $seEnabled = $organization->org_self_enroll_enabled;
                if(isset($seEnabled) && $seEnabled == 1)
                {
                    $encSeVerCode = $organization->self_enroll_verification_code;
                    if(isset($encSeVerCode) && $encSeVerCode != "")
                        $decSeVerCode = Crypt::decrypt($encSeVerCode);
                }
                $organization->dec_se_verification_code = $decSeVerCode;
            }
        }

        $isView = FALSE;
        if($isViewFlag == 1)
            $isView = TRUE;

        $appKeyMappings = AppKeyMapping::active()->get();
        $appKeyMappingArr = array();
        $appKeyMappingArr[0] = Config::get('app_config.company_name');
        foreach ($appKeyMappings as $appKeyMapping) {
            $appKeyMappingArr[$appKeyMapping->app_key_mapping_id] = $appKeyMapping->app_name;
        }

        $data = array();       
        $data['organizationId'] = sracEncryptNumberData($organizationId);
        $data['isView'] = $isView;
        $data['organization'] = $organization;
        $data['modulePermissions'] = $this->modulePermissions;
        $data['userToken'] = $this->userToken;
        $data['appKeyMappingArr'] = $appKeyMappingArr;
        
        $_viewToRender = View::make('organization.partialview._orgRegistrationDetails', $data);
        $_viewToRender = $_viewToRender->render();

        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function saveRegDetails(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $id = $request->input('orgId');
        $isAdd = FALSE;
        $depDataChanged = FALSE;
        $chngFcm = array();

        $id = sracDecryptNumberData($id);
        
        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $organization = Organization::findOrFail($id);
                
                if(isset($organization))
                {
                    if(strcasecmp($organization->regd_name, $request->regd_name) != 0)
                    {
                        $depDataChanged = TRUE;
                    }
                    else if(strcasecmp($organization->system_name, $request->system_name) != 0)
                    {
                        $depDataChanged = TRUE;
                    }
                    else if(strcasecmp($organization->code, $request->code) != 0)
                    {
                        $depDataChanged = TRUE;
                    }
                }
                
                $organization->update($request->all());
                $organization->updated_by = $this->userId;              
            }
        }
        else
        {
            if($this->modulePermissions->module_add == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $organization = Organization::create($request->all());
                $organization->created_by = $this->userId;
                $organization->is_active = 0;
                $isAdd = TRUE;
            }
        }
        
        $orgId = 0;
        if(isset($organization))
        {
            $status = 1;
            $msg = 'Registration Details Saved!';
            
            $orgLogoFile = Input::file('logo_file');    
            $imgChanged = Input::get('image_changed');
            
            $fileName = "";
            if(isset($orgLogoFile) && File::exists($orgLogoFile) && $orgLogoFile->isValid()) 
            {
                $fileUpload = new FileUploadClass;
                $fileName = $fileUpload->uploadOrganizationLogoImage($orgLogoFile);
            }
                
            if($id == 0 || $imgChanged == 1 || $fileName != "")
            {
                $organization->logo_filename = $fileName;
                $depDataChanged = TRUE;
            }           

            $isSeEnabled = 0;
            $encSeVerCode = "";
            if(isset($request->org_self_enroll_enabled) && $request->org_self_enroll_enabled == 1)
            {
                $isSeEnabled = $request->org_self_enroll_enabled;
                $seVerCode = $request->self_enroll_verification_code;
                if($seVerCode != "")
                    $encSeVerCode = Crypt::encrypt($seVerCode);
            }
            
            $organization->regd_name = CommonFunctionController::convertStringToCap($request->regd_name);
            $organization->system_name = CommonFunctionController::convertStringToCap($request->system_name);
            $organization->email = CommonFunctionController::convertStringToLower($request->email);
            $organization->website = CommonFunctionController::convertStringToLower($request->website);
            $organization->org_self_enroll_enabled = $isSeEnabled;
            $organization->self_enroll_verification_code = $encSeVerCode;
            $organization->save();
                  
            $orgId = $organization->organization_id;    
            
            if($isAdd)        
            {
                $encOrgId = Hash::make($orgId);
                $organization->org_key = $encOrgId;
                $organization->app_email = $organization->email;
                $organization->app_phone = $organization->phone;
                $organization->app_website = $organization->website;
                $organization->save();
            }
            else if($depDataChanged) 
            {
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgId($id);
                $orgEmployees = $depMgmtObj->getAllEmployees();
        
                if($orgEmployees != NULL)
                {
                    foreach($orgEmployees as $emp)
                    {
                        $orgEmpId = $emp->employee_id;
                        $fcmStatus = $this->sendOrgEmployeeDetailsToDevice($orgEmpId, $id);
                        // array_push($chngFcm, array('e' => $orgEmpId, 'n' => $emp->employee_name, 's' => $fcmStatus));
                    }
                }
            }
        }  
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg, 'id'=>sracEncryptNumberData($orgId), 'depDataChanged'=>$depDataChanged, 'chngFcm'=>$chngFcm));
    }

    /**
     * Load scheme basic details.
     *
     * @return void
     */
    public function loadSubscriptionDetailsView()
    { 
        $isViewFlag = Input::get('isView');
        $organizationId = Input::get('orgId');

        $organizationId = sracDecryptNumberData($organizationId);
        
        $organization = NULL;
        $orgSubscription = NULL;
        if(isset($organizationId) && $organizationId > 0)
        {
            $organization = Organization::ofOrganization($organizationId)->first();
            $orgSubscription = OrganizationSubscription::ofOrganization($organizationId)->active()->first();
            
            if(isset($orgSubscription))
            {
                $orgSubscription->actDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($orgSubscription->activation_date));
                $orgSubscription->expDtDisp = date(Config::get('app_config.date_disp_format'), strtotime($orgSubscription->expiration_date));
            }
        }
        
        $isView = FALSE;
        if($isViewFlag == 1)
            $isView = TRUE; 
            
        $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

        $data = array();  
        $data['organizationId'] = sracEncryptNumberData($organizationId);     
        $data['isView'] = $isView;
        $data['organization'] = $organization;
        $data['orgSubscription'] = $orgSubscription;
        $data['modulePermissions'] = $this->modulePermissions;
        $data['intJs'] = $intJs;
        $data['userToken'] = $this->userToken;
        
        $_viewToRender = View::make('organization.partialview._orgSubscriptionDetails', $data);
        $_viewToRender = $_viewToRender->render();

        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function saveSubscriptionDetails(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $id = $request->input('orgId');

        $id = sracDecryptNumberData($id);
        
        if($id > 0)
        {
            if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $organization = Organization::ofOrganization($id)->first();

                $msg = 'Subscription Details Saved!';
            
                if(isset($organization))
                {
                    $status = 1;
                    
                    $orgSubscription = OrganizationSubscription::ofOrganization($id)->active()->first();
                    
                    if(!isset($orgSubscription))
                    {
                        $orgSubscription = New OrganizationSubscription;
                        $orgSubscription->organization_id = $id;
                        $orgSubscription->user_count = $request->user_count;
                        $orgSubscription->allotted_quota_in_gb = $request->allotted_quota_in_gb;
                    }
                    else
                    {
                        //Maybe some action will be made
                        $orgSubscription->user_count = $request->user_count;
                        $orgSubscription->allotted_quota_in_gb = $request->allotted_quota_in_gb;
                    }
                    
                    $orgSubscription->activation_date = date(Config::get('app_config.date_db_format'), strtotime($request->activation_date));
                    $orgSubscription->expiration_date = date(Config::get('app_config.date_db_format'), strtotime($request->expiration_date));
                    $isRemMailEnabled = 0;
                    if(isset($request->isRemMailEnabled) && $request->isRemMailEnabled == 1)
                    {
                        $isRemMailEnabled = $request->isRemMailEnabled;
                    }
                    $isBdayMailEnabled = 0;
                    if(isset($request->isBdayMailEnabled) && $request->isBdayMailEnabled == 1)
                    {
                        $isBdayMailEnabled = $request->isBdayMailEnabled;
                    }
                    $isRetailShareEnabled = 0;
                    if(isset($request->isRetailShareEnabled) && $request->isRetailShareEnabled == 1)
                    {
                        $isRetailShareEnabled = $request->isRetailShareEnabled;
                    }
                    $isContentAddedMailEnabled = 0;
                    if(isset($request->isContentAddedMailEnabled) && $request->isContentAddedMailEnabled == 1)
                    {
                        $isContentAddedMailEnabled = $request->isContentAddedMailEnabled;
                    }
                    $isContentDeliveredMailEnabled = 0;
                    if(isset($request->isContentDeliveredMailEnabled) && $request->isContentDeliveredMailEnabled == 1)
                    {
                        $isContentDeliveredMailEnabled = $request->isContentDeliveredMailEnabled;
                    }
                    
                    $orgSubscription->reminder_mail_enabled = $isRemMailEnabled;
                    $orgSubscription->birthday_mail_enabled = $isBdayMailEnabled;
                    $orgSubscription->retail_share_enabled = $isRetailShareEnabled;
                    $orgSubscription->content_added_mail_enabled = $isContentAddedMailEnabled;
                    $orgSubscription->content_delivered_mail_enabled = $isContentDeliveredMailEnabled;
                    
                    $orgSubscription->save();
                    
                    if($isRetailShareEnabled == 0 && $organization->is_active == 1) {
                        OrganizationClass::removeOrganizationEmployeeRetailShareRights($id);
                    }
                }
            }
        }
         
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg, 'id'=>sracEncryptNumberData($id)));
    }

    /**
     * Load scheme basic details.
     *
     * @return void
     */
    public function loadAdministrationDetailsView()
    { 
        $isViewFlag = Input::get('isView');
        $organizationId = Input::get('orgId');

        $organizationId = sracDecryptNumberData($organizationId);
        
        $organization = NULL;
        if(isset($organizationId) && $organizationId > 0)
        {
            $organization = Organization::ofOrganization($organizationId)->first();
        }

        $cmsRoles = CmsRole::active()->get();
        $roleArr = array();
        foreach ($cmsRoles as $role) {
            $roleArr[$role->role_id] = $role->role_name;
        }

        $isView = FALSE;
        if($isViewFlag == 1)
            $isView = TRUE;

        $fromIndOrganization = 0;
            
        $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

        $data = array();       
        $data['isView'] = $isView;
        $data['organization'] = $organization;
        $data['modulePermissions'] = $this->modulePermissions;
        $data['organizationId'] = sracEncryptNumberData($organizationId);
        $data['intJs'] = $intJs;
        $data['roleArr'] = $roleArr;
        $data['userToken'] = $this->userToken;
        $data['fromIndOrganization'] = $fromIndOrganization;
        
        $_viewToRender = View::make('organization.partialview._orgAdministrationDetails', $data);
        $_viewToRender = $_viewToRender->render();

        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }
    
    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function organizationAdminDatatable()
    {
        $fromIndOrganization = Input::get('fromIndOrganization');
        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }

        if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_view) && $this->modulePermissions->module_view == 0)
        {
            return redirect('permissionDenied');
        }

        $id = Input::get('orgId');

        $id = sracDecryptNumberData($id);

        $orgAdmins = OrganizationAdministration::select(['org_admin_id', 'fullname', 'admin_email', 'role_name', "organization_administrators.is_active as is_active"])
                    ->joinCmsRoleTable()
                    ->ofOrganization($id);

        return Datatables::of($orgAdmins)
                ->removeColumn('org_admin_id')
                ->removeColumn('is_active')
                ->add_column('status', function($orgAdmin) {
                    return sracEncryptNumberData($orgAdmin->org_admin_id)."_".$orgAdmin->is_active;
                })
                ->addColumn('action', function($orgAdmin) use($fromIndOrganization) {
                    return $this->getOrganizationAdminDatatableButton($orgAdmin->org_admin_id, $fromIndOrganization);
                })
                ->make();
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getOrganizationAdminDatatableButton($id, $fromIndOrganization)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if(($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_delete) && $this->modulePermissions->module_delete == 1) || $fromIndOrganization == 1)
        {
            $buttonHtml .= '&nbsp;<button type="button" onclick="deleteOrganizationAdmin(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        }
        return $buttonHtml;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function addAdministrator(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $id = $request->input('orgId');
        $name = $request->input('fullname');
        $email = $request->input('adm_email');
        $roleId = $request->input('role_id');

        $id = sracDecryptNumberData($id);

        $fromIndOrganization = $request->input('fromIndOrganization');
        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }
        
        if($id > 0)
        {
            // if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
            if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_edit) && $this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $organization = Organization::findOrFail($id);
                
                $msg = 'Administrator added!';
                
                if(isset($organization))
                {
                    $status = 1;
                    
                    $orgAdmin = OrganizationAdministration::ofOrganization($id)->withEmail($email)->first();
                    
                    if(!isset($orgAdmin))
                    {
                        $autoPassword = generateRandomPassword();
                        $autoPassword = Crypt::encrypt($autoPassword);
                        
                        $orgAdmin = New OrganizationAdministration;
                        $orgAdmin->organization_id = $id;
                        $orgAdmin->fullname = $name;
                        $orgAdmin->admin_email = $email;
                        $orgAdmin->role_id = $roleId;
                        $orgAdmin->password = $autoPassword;
                        $orgAdmin->save();
                        
                        //Send Credentials Mail
                        MailClass::sendOrgAdminCredentailMail($orgAdmin);
                    }
                }
            }
        }
         
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg, 'id'=>sracEncryptNumberData($id)));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function resendAdministratorCredentials(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $admId = $request->input('admId');

        $admId = sracDecryptNumberData($admId);

        $fromIndOrganization = $request->input('fromIndOrganization');
        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }
        
        if($admId > 0)
        {
            if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_edit) && $this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {   
                $orgAdmin = OrganizationAdministration::byId($admId)->first();
                
                if(isset($orgAdmin))
                {
                    $admIsActive = $orgAdmin->is_active;
                    $orgIsActive = 0;
                    if(isset($orgAdmin->organization))
                        $orgIsActive = $orgAdmin->organization->is_active;
                    if($admIsActive == 1)
                    {
                        if($orgIsActive == 1)
                        {
                            $status = 1;
                            $msg = "Credential mail sent";
                            
                            //Send Credentials Mail
                            MailClass::sendOrgAdminCredentailMail($orgAdmin);
                        }
                        else
                        {
                            $status = -1;
                            $msg = "Organization is inactive";
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Administrator account is inactive";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = "No Such Administrator";
                }
            }
        }
        else
        {
            $status = -1;
            $msg = "Invalid Data";
        }
         
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg));
    }

    public function loadAdministratorCredentialsModal(Request $request)
    {
        $status = 0;
        $msg = "";
        $response = array();
        
        $admId = $request->input('admId');

        $admId = sracDecryptNumberData($admId);

        $fromIndOrganization = $request->input('fromIndOrganization');
        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }
        
        if($admId > 0)
        {
            if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_edit) && $this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {   
                $orgAdmin = OrganizationAdministration::byId($admId)->first();
                
                if(isset($orgAdmin))
                {
                    $admIsActive = $orgAdmin->is_active;
                    $orgIsActive = 0;
                    if(isset($orgAdmin->organization))
                        $orgIsActive = $orgAdmin->organization->is_active;
                    if($admIsActive == 1)
                    {
                        if($orgIsActive == 1)
                        {
                            $status = 1;

                            $email = $orgAdmin->admin_email;
                            $name = $orgAdmin->fullname;
                            $encPassword = $orgAdmin->password;
                            $password = Crypt::decrypt($encPassword);

                            $data = array();
                            $data['name'] = $name;
                            $data['email'] = $email;
                            $data['password'] = $password;
                            
                            $_viewToRender = View::make('organization.partialview._orgAdministrationCredentialsModal', $data);
                            $_viewToRender = $_viewToRender->render();

                            $response['view'] = $_viewToRender;
                            $response['data'] = $data;
                        }
                        else
                        {
                            $status = -1;
                            $msg = "Organization is inactive";
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Administrator account is inactive";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = "No Such Administrator";
                }
            }
        }
        else
        {
            $status = -1;
            $msg = "Invalid Data";
        }


        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function deleteAdministrator()
    {
    	$id = Input::get('orgAdmId');
        $fromIndOrganization = Input::get('fromIndOrganization');
        $indOrgAdminId = Input::get('indOrgAdminId');

        $id = sracDecryptNumberData($id);
        $indOrgAdminId = sracDecryptNumberData($indOrgAdminId);

        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }
        
        if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_delete) && $this->modulePermissions->module_delete == 0)
        {
            return redirect('permissionDenied');
        }

        $status = 1;
        $msg = 'Administrator deleted!';

        if($fromIndOrganization == 1 && isset($indOrgAdminId) && $indOrgAdminId > 0 && $indOrgAdminId == $id)
        {
            $status = -1;
            $msg = 'Cannot delete the logged in account!';
        }
        else
        {
            $orgAdministrator = OrganizationAdministration::findOrFail($id);        
            $orgAdministrator->delete();
        }

        echo json_encode(array('status' => $status, 'msg' => $msg));
    }

    /**
     * Change the status of specified resource.
     *
     *
     * @return void
     */
    public function changeAdministratorStatus()
    {
    	$id = Input::get('orgAdmId');
        $statusActive = Input::get('statusActive');
        $fromIndOrganization = Input::get('fromIndOrganization');
        $indOrgAdminId = Input::get('indOrgAdminId');

        $id = sracDecryptNumberData($id);
        $indOrgAdminId = sracDecryptNumberData($indOrgAdminId);

        if(!isset($fromIndOrganization) || $fromIndOrganization != 1)
        {
            $fromIndOrganization = 0;
        }

        if($fromIndOrganization == 0 && isset($this->modulePermissions) && isset($this->modulePermissions->module_edit) && $this->modulePermissions->module_edit == 0)
        {
            return redirect('permissionDenied');
        }

        $status = 1;
        $msg = 'Administrator status changed!';

        if($fromIndOrganization == 1 && isset($indOrgAdminId) && $indOrgAdminId > 0 && $indOrgAdminId == $id)
        {
            $status = -1;
            $msg = 'Cannot change the status of logged in account!';
        }
        else
        {
            $orgAdministrator = OrganizationAdministration::findOrFail($id);
            $orgAdministrator->is_active = $statusActive;
            $orgAdministrator->save();

            if($statusActive == 1)
                MailClass::sendOrgAdminCredentailMail($orgAdministrator);
        }
        

        echo json_encode(array('status' => $status, 'msg' => $msg));
    }
    
    /**
     * Validate User name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */

    public function validateAdminEmailForOrg()
    {        
        $orgId = Input::get('orgId');
        $admEmail = Input::get('admEmail');     

        $orgId = sracDecryptNumberData($orgId); 

		$orgAdmin = OrganizationAdministration::ofOrganization($orgId)->withEmail($admEmail)->first();

        if(isset($orgAdmin))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
            
        echo json_encode(array('valid' => $isAvailable));
    }
    
    /**
     * Validate User name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */

    public function validateOrganizationCode()
    {        
        $id = Input::get('orgId');
        $orgCode = Input::get('org_code');

        $id = sracDecryptNumberData($id); 
        
        if($id > 0)
        {
            $orgData = Organization::where('org_code','=',$orgCode)
                                        ->where('organization_id','!=',$id)
                                        ->get();    
        }
        else
        {
            $orgData = Organization::where('org_code','=',$orgCode)->get();    
        }              
        
        if(count($orgData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE; 
            
        echo json_encode(array('valid' => $isAvailable));
    }
    
    /**
     * Validate User name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */

    public function validateOrganizationDatabaseName()
    {        
        $id = Input::get('orgId');
        $dbname = Input::get('dbname');

        $id = sracDecryptNumberData($id); 
        
        if($id > 0)
        {
            $orgData = OrganizationServer::where('dbname','=',$dbname)
                                        ->where('organization_id','!=',$id)
                                        ->get();    
        }
        else
        {
        	$dbname = Config::get('app_config.org_db_prefix').$dbname;
            $orgData = OrganizationServer::where('dbname','=',$dbname)->get();    
        }              
        
        if(count($orgData)>0)
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE; 
            
        echo json_encode(array('valid' => $isAvailable));
    }

    /**
     * Load scheme basic details.
     *
     * @return void
     */
    public function loadServerDetailsView()
    { 
        $isViewFlag = Input::get('isView');
        $organizationId = Input::get('orgId');

        $organizationId = sracDecryptNumberData($organizationId); 
        
        $organization = NULL;
        $orgServer = NULL;
        if(isset($organizationId) && $organizationId > 0)
        {
            $organization = Organization::ofOrganization($organizationId)->first();
            $orgServer = OrganizationServer::ofOrganization($organizationId)->first();

            if(isset($orgServer) && $orgServer->is_app_smtp_server == 0 && isset($orgServer->smtp_key) && $orgServer->smtp_key != '')
            {
                $orgServer->smtp_key = Crypt::decrypt($orgServer->smtp_key);
            }
        }
        
        $isView = FALSE;
        if($isViewFlag == 1)
            $isView = TRUE;

        $data = array();     
        $data['organizationId'] = sracEncryptNumberData($organizationId);   
        $data['isView'] = $isView;
        $data['organization'] = $organization;
        $data['orgServer'] = $orgServer;
        $data['modulePermissions'] = $this->modulePermissions;
        $data['userToken'] = $this->userToken;
        
        $_viewToRender = View::make('organization.partialview._orgServerDetails', $data);
        $_viewToRender->render();
    
        $response = array('view' => "$_viewToRender" );

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function saveServerDetails(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $id = $request->input('orgId');

        $id = sracDecryptNumberData($id); 
        
        if($id > 0)
        {
            if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $organization = Organization::ofOrganization($id)->first();

                $msg = 'Server Details Saved!';
            
                if(isset($organization))
                {
                    $status = 1;
                    
                    $orgServer = OrganizationServer::ofOrganization($id)->first();
                
                    if(!isset($orgServer))
                        {
                        $dbname = Config::get('app_config.org_db_prefix').$request->dbname;
                        
                        $orgServer = New OrganizationServer;
                        $orgServer->organization_id = $id;              
                        $orgServer->dbname = $dbname;
                    }
                    else
                    {
                        //Maybe some action will be made  
                        $dbname = $request->dbname;           
                        $orgServer->dbname = $dbname;
                    }
                    
                    $isAppDbServer = 0;
                    if(isset($request->is_app_db_server))
                        $isAppDbServer = $request->is_app_db_server;
                    
                    $isAppFileServer = 0;
                    if(isset($request->is_app_file_server))
                        $isAppFileServer = $request->is_app_file_server;
                    
                    $isAppSmtpServer = 1;
                    $encSmtpKey = '';
                    $smtpEmail = '';

                    if(isset($request->is_app_smtp_server) && $request->is_app_smtp_server == 1)
                        $isAppSmtpServer = $request->is_app_smtp_server;
                    else
                        $isAppSmtpServer = 0;

                    if($isAppSmtpServer == 0)
                    {
                        if(isset($request->smtp_key))
                        {
                            $encSmtpKey = Crypt::encrypt($request->smtp_key);
                            $smtpEmail = $request->smtp_email;
                        }
                    }
                    
                    $orgServer->is_app_db_server = $isAppDbServer;                  
                    $orgServer->is_app_file_server = $isAppFileServer;              
                    $orgServer->is_app_smtp_server = $isAppSmtpServer;    
                    $orgServer->host = $request->host;
                    $orgServer->file_host = $request->file_host;
                    $orgServer->username = $request->username;
                    $orgServer->password = $request->password;
                    $orgServer->smtp_email = $smtpEmail;
                    $orgServer->smtp_key = $encSmtpKey;
                    
                    $orgServer->save();
                }
            }
        }
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg, 'id'=>sracEncryptNumberData($id)));
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

        $id = sracDecryptNumberData($id); 
        

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
        $id = Input::get('orgId');

        $id = sracDecryptNumberData($id); 

        $isAvailable = 1;
        $msg = "";

        $organization = Organization::ofOrganization($id)->first();
        
        if(isset($organization) && $organization->is_active == 1)
        {
            $isAvailable = 0;
            $msg = "Cannot delete an active organization";
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

        $id = Input::get('orgId');
        $deleteDb = Input::get('deleteDb');

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
                $organization = Organization::ofOrganization($id)->first();
            
                if(isset($organization))
                {
                    $status = 1;
                    $msg = 'Organization deleted!';
                    
                    $organizationServer = OrganizationServer::ofOrganization($id)->first();
                    if(isset($organizationServer) && $organizationServer->dbname != "")
                    {
                        $orgClassObj = New OrganizationClass;
                        $orgClassObj->removeOrDeactivateOrganizationEmployees($id, TRUE, TRUE);
                    }
                    
                    $organization->is_deleted = 1;
                    $organization->deleted_by = $this->userId;
                    $organization->updated_by = $this->userId;
                    $organization->save();
                    
                    $organization->delete();
                    
                    if(isset($deleteDb) && $deleteDb == 1)
                    {
                        //Delete Database
                        //OrganizationClass::deleteOrganizationDependencies($id);
                    } 
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

        $id = Input::get('orgId');
        $statusActive = Input::get('statusActive');

        $id = sracDecryptNumberData($id); 

        $organization = Organization::ofOrganization($id)->first();
        $organizationServer = OrganizationServer::ofOrganization($id)->first();
        
        if(isset($organization))
        {
            if(isset($organizationServer) && $organizationServer->dbname != "")
            {
                $orgClassObj = New OrganizationClass;
                if($statusActive == 1)
                {
                    //Create Org Database
                    $orgClassObj->createOrganizationDependencies($id);
                }
                else
                {
                    $orgClassObj->removeOrDeactivateOrganizationEmployees($id, FALSE);
                }
                
                $organization->is_active = $statusActive;
                $organization->updated_by = $this->userId;
                $organization->save();
                
                $msg = 'Organization status changed!';
            }
            else
            {
                $status = -1;
                $msg = "Organization does not have server details";
            }
        }

        echo json_encode(array('status' => $status, 'msg' => $msg));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function registerOrganizationWithReferral(Request $request)
    {
        $status = 0;
        $msg = "";
        
        $referralCodeText = $request->org_ref_code;
        $orgCode = $request->org_code;

        $referralCodeText = preg_replace("/\s+/", "", $referralCodeText);
        $userIpAddress = $request->ipAddress;

        $isValidReferralCode = 0;
        $enterpriseActivationDate = NULL;
        $enterpriseExpirationDate = NULL;
        $enterpriseUserCount = 0;
        $enterpriseQuotaInGb = 0;
        $referralCodeId = 0;
        $hasReferral = 0;
        $enterpriseCouponCodeId = 0;
        $hasEnterpriseCoupon = 0;
        if(isset($referralCodeText) && $referralCodeText != "")
        {
            $referralCode = OrgReferralCode::active()->byCode($referralCodeText)->isValidForUsage()->first();
            $enterpriseCouponCode = EnterpriseCouponCode::isCouponCodeValidForUsage($referralCodeText)->first();
            if(isset($referralCode))
            {
                $isValidReferralCode = 1;
                $hasReferral = 1;
                $referralCodeId = $referralCode->referral_code_id;
                $enterpriseUserCount = $referralCode->user_count;
                $enterpriseQuotaInGb = $referralCode->allotted_quota_in_gb;

                $utcTz =  'UTC';

                $enterpriseActivationDate = Carbon::now($utcTz);
                $enterpriseActivationDate = $enterpriseActivationDate->toDateString();

                $enterpriseExpirationDate = Carbon::now($utcTz);
                $enterpriseExpirationDate = $enterpriseExpirationDate->addYear();
                $enterpriseExpirationDate = $enterpriseExpirationDate->toDateString();
            }
            elseif(isset($enterpriseCouponCode))
            {
                $isValidReferralCode = 1;
                $hasEnterpriseCoupon = 1;
                $enterpriseCouponCodeId = $enterpriseCouponCode->enterprise_coupon_code_id;

                $coupon = $enterpriseCouponCode->enterpriseCoupon;

                $enterpriseUserCount = $coupon->allotted_user_count;
                $enterpriseQuotaInGb = $coupon->allotted_space_in_gb;

                $utcTz =  'UTC';

                $enterpriseActivationDate = Carbon::now($utcTz);
                $enterpriseActivationDate = $enterpriseActivationDate->toDateString();

                $enterpriseExpirationDate = Carbon::now($utcTz);
                $enterpriseExpirationDate = $enterpriseExpirationDate->addDays($coupon->subscription_validity_days);
                $enterpriseExpirationDate = $enterpriseExpirationDate->toDateString();
            }
        }

        if(isset($isValidReferralCode) && $isValidReferralCode == 1)
        {
            $orgData = Organization::where('org_code','=',$orgCode)->first(); 

            if(!isset($orgData))
            {

                $isSeEnabled = 0;
                $encSeVerCode = "";
                if(isset($request->org_self_enroll_enabled) && $request->org_self_enroll_enabled == 1)
                {
                    $isSeEnabled = $request->org_self_enroll_enabled;
                    $seVerCode = $request->self_enroll_verification_code;
                    if($seVerCode != "")
                    {
                        $seVerCode = CommonFunctionController::convertStringToUpper($seVerCode);
                        $encSeVerCode = Crypt::encrypt($seVerCode);
                    }    
                }

                $orgLogoFile = Input::file('logo_file');
                
                $fileName = "";
                if(isset($orgLogoFile) && File::exists($orgLogoFile) && $orgLogoFile->isValid()) 
                {
                    $fileUpload = new FileUploadClass;
                    $fileName = $fileUpload->uploadOrganizationLogoImage($orgLogoFile);
                }
                
                $organization = Organization::create($request->all());
                $organization->created_by = $this->userId;
                $organization->is_active = 1;
                $organization->logo_filename = $fileName;
                $organization->org_code = CommonFunctionController::convertStringToUpper($request->org_code);            
                $organization->regd_name = CommonFunctionController::convertStringToUpper($request->regd_name);
                $organization->system_name = CommonFunctionController::convertStringToUpper($request->system_name);
                $organization->email = CommonFunctionController::convertStringToLower($request->email);
                $organization->website = CommonFunctionController::convertStringToLower($request->website);
                $organization->org_self_enroll_enabled = $isSeEnabled;
                $organization->self_enroll_verification_code = $encSeVerCode;
                $organization->ref_code = $referralCodeText;
                $organization->has_referral = $hasReferral;
                $organization->referral_code_id = $referralCodeId;
                $organization->has_coupon = $hasEnterpriseCoupon;
                $organization->enterprise_coupon_code_id = $enterpriseCouponCodeId;
                $organization->save();

                if(isset($organization))
                {
                    $orgId = $organization->organization_id;  
                    $encOrgId = Hash::make($orgId);
                    $organization->org_key = $encOrgId;
                    $organization->app_email = $organization->email;
                    $organization->app_phone = $organization->phone;
                    $organization->app_website = $organization->website;
                    $organization->save();
                                      
                    /* Subscription Details */
                    $isRemMailEnabled = 1;
                    $isBdayMailEnabled = 0;
                    $isRetailShareEnabled = 0;
                    $isContentAddedMailEnabled = 1;
                    $isContentDeliveredMailEnabled = 1;
                    
                    $orgSubscription = New OrganizationSubscription;
                    $orgSubscription->organization_id = $orgId;
                    $orgSubscription->user_count = $enterpriseUserCount;
                    $orgSubscription->allotted_quota_in_gb = $enterpriseQuotaInGb;
                    $orgSubscription->activation_date = $enterpriseActivationDate;
                    $orgSubscription->expiration_date = $enterpriseExpirationDate;  
                    $orgSubscription->reminder_mail_enabled = $isRemMailEnabled;
                    $orgSubscription->birthday_mail_enabled = $isBdayMailEnabled;
                    $orgSubscription->retail_share_enabled = $isRetailShareEnabled;
                    $orgSubscription->content_added_mail_enabled = $isContentAddedMailEnabled;
                    $orgSubscription->content_delivered_mail_enabled = $isContentDeliveredMailEnabled;                    
                    $orgSubscription->save();
                    /* Subscription Details */

                    /* Server Details */
                    $randomNum = 11;
                    $autoDbPostfix = strtolower($orgCode);
                    $autoDbPostfix = trim(preg_replace('/\s+/', ' ', $autoDbPostfix));
                    $autoDbPostfix = $autoDbPostfix."_".$randomNum;
                    $dbname = Config::get('app_config.org_db_prefix').$autoDbPostfix;
                     
                    $orgServer = New OrganizationServer;
                    $orgServer->organization_id = $orgId;              
                    $orgServer->dbname = $dbname;
                    $orgServer->is_app_db_server = 1;                  
                    $orgServer->is_app_file_server = 1;    
                    $orgServer->host = "";
                    $orgServer->file_host = "";
                    $orgServer->username = "";
                    $orgServer->password = "";                    
                    $orgServer->save();
                    /* Server Details */
                    
                    /* Admin Details */
                    $admRoleId = 1;
                    $autoPassword = generateRandomPassword();
                    $autoPassword = Crypt::encrypt($autoPassword);
                    
                    $orgAdmin = New OrganizationAdministration;
                    $orgAdmin->organization_id = $orgId;
                    $orgAdmin->fullname = $request->adm_fullname;
                    $orgAdmin->admin_email =$request->adm_email;
                    $orgAdmin->role_id = $admRoleId;
                    $orgAdmin->password = $autoPassword;
                    $orgAdmin->save();

                    $orgAdminId = $orgAdmin->org_admin_id;

                    if($hasEnterpriseCoupon == 1 && $enterpriseCouponCodeId > 0)
                    {
                        $currTimestamp = CommonFunctionClass::getCurrentTimestamp();

                        $enterpriseCouponCodeForUpd = EnterpriseCouponCode::byId($enterpriseCouponCodeId)->first();

                        $enterpriseCouponId = $enterpriseCouponCodeForUpd->enterprise_coupon_id;
                        
                        $enterpriseCouponForUpd = EnterpriseCoupon::byId($enterpriseCouponId)->first();

                        $enterpriseCouponCodeForUpd->is_utilized = 1;
                        $enterpriseCouponCodeForUpd->utilized_by_organization_admin = $orgAdminId;
                        $enterpriseCouponCodeForUpd->organization_id = $orgId;
                        $enterpriseCouponCodeForUpd->utilized_at = $currTimestamp;
                        $enterpriseCouponCodeForUpd->user_ip_address = $userIpAddress;
                        $enterpriseCouponCodeForUpd->allotted_space_in_gb = $enterpriseCouponForUpd->allotted_space_in_gb;
                        $enterpriseCouponCodeForUpd->allotted_user_count = $enterpriseCouponForUpd->allotted_user_count;
                        $enterpriseCouponCodeForUpd->subscription_start_date = $enterpriseActivationDate;
                        $enterpriseCouponCodeForUpd->subscription_end_date = $enterpriseExpirationDate;
                        $enterpriseCouponCodeForUpd->is_stacked = 0;
                        $enterpriseCouponCodeForUpd->save();

                        $utilizedEnterpriseCouponCodeCount = EnterpriseCouponCode::ofCoupon($enterpriseCouponId)->isUtilized()->count();

                        $availableEnterpriseCouponCodeCount = $enterpriseCouponForUpd->coupon_count - $utilizedEnterpriseCouponCodeCount;

                        $enterpriseCouponForUpd->available_coupon_count = $availableEnterpriseCouponCodeCount;
                        $enterpriseCouponForUpd->utilized_coupon_count = $utilizedEnterpriseCouponCodeCount;
                        $enterpriseCouponForUpd->save();
                    }
                    
                    MailClass::sendOrgAdminCredentailMail($orgAdmin); //Send Credentials Mail
                    /* Admin Details */

                    $admAdnlFullname1 = $request->adm_fullname_adnl_1;
                    $admAdnlEmail1 = $request->adm_email_adnl_1;
                    if(isset($admAdnlFullname1) && $admAdnlFullname1 != "" && isset($admAdnlEmail1) && $admAdnlEmail1 != "")
                    {
                        $adnl1AutoPassword = generateRandomPassword();
                        $adnl1AutoPassword = Crypt::encrypt($adnl1AutoPassword);

                        $orgAdminAdnl1 = New OrganizationAdministration;
                        $orgAdminAdnl1->organization_id = $orgId;
                        $orgAdminAdnl1->fullname = $admAdnlFullname1;
                        $orgAdminAdnl1->admin_email =$admAdnlEmail1;
                        $orgAdminAdnl1->role_id = $admRoleId;
                        $orgAdminAdnl1->password = $adnl1AutoPassword;
                        $orgAdminAdnl1->save();

                        MailClass::sendOrgAdminCredentailMail($orgAdminAdnl1); //Send Credentials Mail
                    }

                    $admAdnlFullname2 = $request->adm_fullname_adnl_2;
                    $admAdnlEmail2 = $request->adm_email_adnl_2;
                    if(isset($admAdnlFullname2) && $admAdnlFullname2 != "" && isset($admAdnlEmail2) && $admAdnlEmail2 != "")
                    {
                        $adnl2AutoPassword = generateRandomPassword();
                        $adnl2AutoPassword = Crypt::encrypt($adnl2AutoPassword);

                        $orgAdminAdnl2 = New OrganizationAdministration;
                        $orgAdminAdnl2->organization_id = $orgId;
                        $orgAdminAdnl2->fullname = $admAdnlFullname2;
                        $orgAdminAdnl2->admin_email =$admAdnlEmail2;
                        $orgAdminAdnl2->role_id = $admRoleId;
                        $orgAdminAdnl2->password = $adnl2AutoPassword;
                        $orgAdminAdnl2->save();

                        MailClass::sendOrgAdminCredentailMail($orgAdminAdnl2); //Send Credentials Mail
                    }

                    OrganizationClass::createOrganizationDependencies($orgId);
                    
                    MailClass::sendOrganizationReferralSignupMail($orgId, $referralCodeText); //Send Intimation Mail

                    $status = 1;
                    $msg = 'Registration Details Saved!';
                }
                else
                {
                    $status = -1;
                    $msg = 'Something went wrong';
                }
            }
            else
            {
                $status = -1;
                $msg = "Organization with the code already exists";
            }
        }
        else
        {
            $status = -1;
            $msg = "Invalid referral code";
        }
        
        echo json_encode(array('status'=>$status, 'msg'=>$msg));
    }
}
