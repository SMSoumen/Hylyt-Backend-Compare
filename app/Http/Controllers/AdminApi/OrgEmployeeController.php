<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationUser;
use App\Models\Org\Api\OrgDepartment;
use App\Models\Org\Api\OrgDesignation;
use App\Models\Org\Api\OrgBadge;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeConstant;
use App\Models\Org\Api\OrgEmployeeBadge;
use App\Models\Org\Api\OrgEmployeeFieldValue;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use App\Models\Org\OrganizationFieldType;
use App\Models\Org\OrganizationUserField;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;
use App\Models\User;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserSession;
use Crypt;
use Response;
use View;
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\FileUploadClass;
use App\Libraries\MailClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Http\Controllers\CommonFunctionController;
use DB;
use Excel;
use Hash;
use File;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Log;

class OrgEmployeeController extends Controller
{
    use OrgCloudMessagingTrait;
    
    public $userId = NULL;
    public $roleId = 0;
    public $organizationId = 0;
    public $userDetails = NULL;

    public $modulePermissions = NULL;
    public $module = "";

    public $userToken = NULL;
    public $orgDbConName = NULL;
    
    public $admUserToken = NULL;
    public $admUserId = NULL;
    public $admUserDetails = NULL;
	
	public function __construct()
    {
        $this->module = Config::get('app_config_module.mod_org_employee');

        $encUserToken = Input::get('usrtoken'); 

        if(isset($encUserToken) && $encUserToken != "")
        {
            $this->userToken = $encUserToken;

            $adminUserId = Crypt::decrypt($encUserToken);

            $adminUser = OrganizationAdministration::active()->byId($adminUserId)->first();

            if(isset($adminUser))
            {
                $this->userDetails = $adminUser;
                $this->userId = $adminUserId;
                $this->roleId = $adminUser->role_id;
                $this->organizationId = $adminUser->organization_id;

                $this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->organizationId);

                $modules = CmsModule::where('module_name', '=', $this->module)->exists()->first();
                $rights = $modules->right()->where('role_id', '=', $this->roleId)->first();
                $this->modulePermissions = $rights;
            }
        }  
        $admUserToken = Input::get('admusrtoken'); 
        if(isset($admUserToken) && $admUserToken != "")
        {
            $this->admUserToken = $admUserToken;
            
            $this->userId = Crypt::decrypt($admUserToken);
            $user = User::active()->byId($this->userId)->first();

            if(isset($user))
            {
                $this->admUserDetails = $user;
                $this->roleId = Config::get("app_config.cms_role_id_for_admin");
        
        		$inpOrgId = Input::get('orgId');
        		$decOrgId = Crypt::decrypt($inpOrgId);
        		
        		if(isset($decOrgId) && $decOrgId > 0)
        		{
        			$this->organizationId = $decOrgId * 1;
                	$this->orgDbConName = OrganizationClass::configureConnectionForOrganization($this->organizationId);
				}

                $modules = CmsModule::where('module_name', '=', $this->module)->exists()->first();
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
    public function loadEmployeeView()
    {  
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
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

	        $verStatusList = array();   
	        $verStatusList[sracEncryptNumberData('1')] = Config::get('app_config.is_verified_text'); 
	        $verStatusList[sracEncryptNumberData('0')] = Config::get('app_config.verification_pending_text'); 
	        $verStatusList[sracEncryptNumberData('2')] = Config::get('app_config.is_self_verified_text');
            
            $deptModelObj = New OrgDepartment;
            $deptModelObj->setConnection($this->orgDbConName);                
            $departments = $deptModelObj->active()->get();
            $arrDepartment = array();
            foreach($departments as $department)
            {
				$arrDepartment[sracEncryptNumberData($department->department_id)] = $department->department_name;
			}
            
            $desigModelObj = New OrgDesignation;
            $desigModelObj->setConnection($this->orgDbConName);            
            $designations = $desigModelObj->active()->get();
            $arrDesignation = array();
            foreach($designations as $designation)
            {
				$arrDesignation[sracEncryptNumberData($designation->designation_id)] = $designation->designation_name;
			}
            
            $badgeModelObj = New OrgBadge;
            $badgeModelObj->setConnection($this->orgDbConName);            
            $badges = $badgeModelObj->active()->get();
            $arrBadge = array();
            foreach($badges as $badge)
            {
				$arrBadge[sracEncryptNumberData($badge->badge_id)] = $badge->badge_name;
			}

			$forDashboard = Input::get('forDashboard');
			if(!isset($forDashboard) || $forDashboard != 1)
			{
				$forDashboard = 0;
			}

			$onlyDeleted = Input::get('onlyDeleted');
			if(!isset($onlyDeleted) || $onlyDeleted != 1)
			{
				$onlyDeleted = 0;
			}
            
            $data = array();
            //$data['js'] = $js;
            //$data['css'] = $css;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;
            $data['admusrtoken'] = $this->admUserToken;
        	$data['verStatusList'] = $verStatusList;
        	$data['departmentList'] = $arrDepartment;
        	$data['designationList'] = $arrDesignation;
        	$data['badgeList'] = $arrBadge;
        	$data['forEmpList'] = 1;
        	$data['forDashboard'] = $forDashboard;
        	$data['onlyDeleted'] = $onlyDeleted;
        	$data['orgId'] = Crypt::encrypt($this->organizationId);

            $_viewToRender = View::make('orgemployee.index', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function loadUploadExcel()
    {  
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
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

            $js = array("/dist/datatables/jquery.dataTables.min.js","/dist/select2/dist/js/select2.min.js","/dist/bootbox/bootbox.min.js");
            $css = array("/dist/datatables/jquery.dataTables.min.css","/dist/select2/dist/css/select2.min.css");    
            
            $tempPath = Config::get('app_config_import_export.url_path_employee_template');     
            $tempFilename = Config::get('app_config_import_export.employee_template_name');     
            
            $empTemplateUri = url($tempPath.$tempFilename);
            
            $data = array();
            $data['js'] = $js;
            $data['css'] = $css;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;
            $data['empTemplateUri'] = $empTemplateUri;

            $_viewToRender = View::make('orgemployee.uploadExcel', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function downloadTemplate()
    {		
		$excelBaseUrl = Config::get('app_config_import_export.url_path_employee_template');
	    $destinationFileWithPath = url($excelBaseUrl).$fileName;
		
		if(file_exists($destinationFileWithPath))
		{
			$destinationFile = Config::get("app_config_import_export.employee_template_download").'/'.$fileName;
			$file_path = url($destinationFile);
			header('Content-Type: application/octet-stream');
			header("Content-Disposition: attachment; filename=$fileName");
			ob_clean();
			flush();
			readfile($file_path);
		}
		else
		{
			echo "File does not Exist.";
		}
	}

   	public function import(Request $request)
    {
        $status = 0;
        $msg = "";
        
        if($request->hasFile('import_file'))
        {
        	$status = 1;
            $data = array();
            
    		//set_time_limit(0);
            ini_set('memory_limit', '-1');
            
			$genderMale = Config::get("app_config.gender_male");
			$genderFemale = Config::get("app_config.gender_female");
			$genderOther = Config::get("app_config.gender_other");
        	
        	//PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);

            $path = $request->file('import_file')->getRealPath();
            Log::info('path : ');
            Log::info($path);
            $excelData = Excel::load($path, function($reader) {})->get();
            Log::info('excelData : ');
            Log::info($excelData);
            
            $orgUserFields = OrganizationUserField::ofOrganization($this->organizationId)->active()->get();
            $fldModelObj = New OrgEmployeeFieldValue;
            $fldModelObj->setConnection($this->orgDbConName);
        	$empFldTableName = $fldModelObj->table; 
        	
            $importResultArray = array();
            if(!empty($excelData) && $excelData->count())
            {            	
				$desigModelObj = New OrgDesignation;
	            $desigModelObj->setConnection($this->orgDbConName);
	            
				$deptModelObj = New OrgDepartment;
	            $deptModelObj->setConnection($this->orgDbConName);
	            
				$empModelObj = New OrgEmployee;
	            $empModelObj->setConnection($this->orgDbConName);
	            
	            $depMgmtObj = New ContentDependencyManagementClass;
        		$depMgmtObj->withOrgId($this->organizationId);
        		$defEmpQuotaKb = Config::get('app_config.default_allotted_attachment_kb');
        		$defEmpQuotaMb = round($defEmpQuotaKb/1024);
				          
				foreach ($excelData as $key => $value) 
				{	
		            Log::info('key : ');
		            Log::info($key);
		            Log::info('value : ');
		            Log::info($value);
					if(isset($value) && count($value) > 0)
					{
						foreach($value as $empRow)
						{
				            Log::info('empRow : ');
				            Log::info($empRow);
							$importStatus = -1;
							$importMsg = "";
							
							$isValidEmpData = TRUE;

							if(isset($empRow) && isset($empRow->sr_no))
							{
								$srNo = $empRow->sr_no;
								$empNo = $empRow->id;
								$name = $empRow->name;
								$departmentName = $empRow->department;
								$designationName = $empRow->designation;
								$contact = $empRow->contact_number;
								$email = $empRow->email;
								$dob = $empRow->date_of_birthdd_mm_yyyy;
								$startDt = $empRow->start_datedd_mm_yyyy;
								$emerContact = $empRow->emergency_contact;
								$gender = $empRow->gender;
								
								if($isValidEmpData && (!isset($srNo) || trim($srNo) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "Sr No is required";
								}
								else
								{
									$srNo = trim($srNo);
									
									if($srNo <= 0)
									{
										$isValidEmpData = FALSE;
										$importMsg = "Invalid Sr No";
									}						
								}

								if($isValidEmpData && (!isset($name) || trim($name) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "Name is required";
								}
								else
								{
									$name = trim($name);					
								}
								
								if($isValidEmpData && (!isset($empNo) || trim($empNo) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "ID is required";
								}
								else
								{
									$empNo = trim($empNo);
									
						            $employee = $empModelObj->byEmpNo($empNo)->first();
									if($isValidEmpData && isset($employee))
									{
										$isValidEmpData = FALSE;
										$importMsg = "User with ID exists";
									}						
								}

								if($isValidEmpData && (!isset($email) || trim($email) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "Email is required";
								}
								else
								{
									$email = trim($email);
									
									if($isValidEmpData && !isValidEmail($email))
									{
										$isValidEmpData = FALSE;
										$importMsg = "Invalid Email Format";									
									}
									/*else
									{
							            $employee = $empModelObj->ofEmail($email)->first();
										if($isValidEmpData && isset($employee))
										{
											$isValidEmpData = FALSE;
											$importMsg = "User with Email exists";
										}
									}*/						
								}

								$designationId = 0;
								if($isValidEmpData && (!isset($designationName) || trim($designationName) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "Designation is required";
								}
								else
								{
									$designationName = trim($designationName);
						            $designation = $desigModelObj->byName($designationName)->first();
						            if(isset($designation))
						            {
						            	$designationId = $designation->designation_id;
									}
									elseif($isValidEmpData)
									{
										$isValidEmpData = FALSE;
										$importMsg = "Designation does not exist";
									}
								}
								
								$departmentId = 0;
								if($isValidEmpData && (!isset($departmentName) || trim($departmentName) == ""))
								{
									$isValidEmpData = FALSE;
									$importMsg = "Department is required";
								}
								else
								{
									$departmentName = trim($departmentName);
						            $department = $deptModelObj->byName($departmentName)->first();
						            if(isset($department))
						            {
						            	$departmentId = $department->department_id;
									}
									elseif($isValidEmpData)
									{
										$isValidEmpData = FALSE;
										$importMsg = "Department does not exist";
									}
								}

								if(!isset($contact))
								{
									$contact = "";
								}
								$contact = trim($contact);
								
								$dbDob = "";
								if(isset($dob) && trim($dob) != "")
								{
									$dob = trim($dob);
									$dob = makeDate($dob);
									
									if($isValidEmpData && !isValidDMYDate($dob) && !isValidYMDDate($dob))
									{
										($dateArray = explode('/', $dob));
										$isValidEmpData = FALSE;
										$importMsg = "Invalid DOB Date Format";		
									}
									else
									{
	        							$dbDob = CommonFunctionController::convertDispToDbDate($dob);
									}
								}

								$dbStartDt = "";
								if(isset($startDt) && trim($startDt) != "")
								{
									$startDt = trim($startDt);
									$startDt = makeDate($startDt);
									
									if($isValidEmpData && !isValidDMYDate($startDt) && !isValidYMDDate($startDt))
									{
										$isValidEmpData = FALSE;
										$importMsg = "Invalid Start Date Format";									
									}
									else
									{
	        							$dbStartDt = CommonFunctionController::convertDispToDbDate($startDt);									
									}
								}	
								
								if(!isset($emerContact))
								{
									$emerContact = "";
								}
								$emerContact = trim($emerContact);
								
								if(!isset($gender))
								{
									$gender = "";
								}
								else
								{
									$gender = trim($gender);
									if($gender != $genderMale && $gender != $genderFemale && $gender != $genderOther)
									{
										$isValidEmpData = FALSE;
										$importMsg = "Invalid Gender";	
									}
								}

								$availUsrCnt = 0;
								$userCntParams = $depMgmtObj->getUserCountParams();
						        if(isset($userCntParams['avail']))
						        {
						        	$availUsrCnt = $userCntParams['avail'];
								}
								
								$availQuotaMb = 0;
						        $quotaParams = $depMgmtObj->getQuotaParams();
						        if(isset($quotaParams['avail']))
						        {
						        	$availQuotaMb = $quotaParams['avail'];
								}
								
								if($availUsrCnt <= 0)
								{
									$isValidEmpData = FALSE;
									$importMsg = "User count unavailable.";
								}
								
								if($availQuotaMb < $defEmpQuotaMb)
								{
									$isValidEmpData = FALSE;
									$importMsg = "User quota unavailable. ".$availQuotaMb;
								}	

								if($isValidEmpData)
								{
									$importStatus = 1;
									$importMsg = "-";
									
									$email = sanitizeEmailString($email);
									
									$employeeDetails = array();
							        $employeeDetails['employee_no'] = $empNo;
							        $employeeDetails['employee_name'] = $name;
							        $employeeDetails['contact'] = $contact;
							        $employeeDetails['dob'] = $dbDob;
							        $employeeDetails['department_id'] = $departmentId;
							        $employeeDetails['designation_id'] = $designationId;
					                $employeeDetails['email'] = $email;
					                $employeeDetails['created_by'] = $this->userId;
					                $employeeDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
							        $employeeDetails['start_date'] = $dbStartDt;
							        $employeeDetails['emergency_contact'] = $emerContact;
							        $employeeDetails['gender'] = $gender;
	                				
	                				$employeeId = $this->addNewEmployee($employeeDetails);
					
	        						$depMgmtObj->recalculateOrgSubscriptionParams();
	                				
	                				foreach($orgUserFields as $usrField)
						        	{
										$fieldId = $usrField->org_field_id;
										$fieldDispName = $usrField->field_display_name;
										$fieldRetName = $fieldDispName;
										$fieldRetName = strtolower($fieldRetName);
										$fieldRetName = str_replace(" ", '_', $fieldRetName);
										$fieldRetName = preg_replace('/[^A-Za-z0-9_\-]/', '', $fieldRetName);
										$fieldColVal = $empRow->$fieldRetName;
										
										if(isset($fieldColVal) && $fieldColVal != "")
										{										
											$empField = $fldModelObj->ofEmployee($employeeId)->ofField($fieldId)->first();				
											if(!isset($empField))
											{
												$fieldData = array();
												$fieldData['field_value'] = $fieldColVal;
												$fieldData['employee_id'] = $employeeId;
												$fieldData['org_field_id'] = $fieldId;
												$fieldData['created_at'] = CommonFunctionClass::getCurrentTimestamp();
												
												DB::connection($this->orgDbConName)->table($empFldTableName)->insert($fieldData);
											}
										}    
									
									}
								}							
													
								$importResult = array();
								$importResult['srno'] = $srNo;
								$importResult['importStatus'] = $importStatus;
								$importResult['importMsg'] = $importMsg;
								$importResult['id'] = $empNo;
								$importResult['name'] = $name;
								$importResult['department'] = $departmentName;
								$importResult['designation'] = $designationName;
								$importResult['email'] = $email;
								
								array_push($importResultArray, $importResult);
							}
							else
							{
							}						
						}		            						
					}
				}
			}
			
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;
            $data['importResultArray'] = $importResultArray;

            $_viewToRender = View::make('orgemployee.import', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }
        else
        {
			$status = -1;
			$msg = "Invalid File";
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
    public function employeeDatatable()
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
        	$forDashboard = Input::get('forDashboard');
        	$forEmpList = Input::get('forEmpList');
        	$forGroupList = Input::get('forGroupList');
        	$forSend = Input::get('forSend');
    		$onlyDeleted = Input::get('onlyDeleted');
    		$selEncIdArrStr = Input::get('selArr');

    		$selEmployeeIdArr = array();
    		if(isset($selEncIdArrStr) && $selEncIdArrStr != "")
    		{
				$selEncIdArr = json_decode($selEncIdArrStr);
				if(is_array($selEncIdArr))
				{
        			$selEmployeeIdArr = sracDecryptNumberArrayData($selEncIdArr);
				}
    		}
        	
            $employees = OrganizationClass::getEmployeeResultSet($this->organizationId);

            if($onlyDeleted == 1)
            {
            	$datatableObj =  Datatables::of($employees)->onlyTrashed();
			}
			else
			{
            	$datatableObj =  Datatables::of($employees);
			}

            $datatableObj->remove_column('is_verified')
	                    ->remove_column('is_emp_active')
	                    ->remove_column('employee_id')
	                    ->remove_column('has_web_access');
    
            if($forEmpList == 1)
            {
                $datatableObj->add_column('status', function($employee) {
                        return sracEncryptNumberData($employee->employee_id)."_".$employee->is_emp_active;
                    });
                
                $datatableObj->add_column('web_access_status', function($employee) {
                        return sracEncryptNumberData($employee->employee_id)."_".$employee->has_web_access;
                    });

            	$datatableObj->add_column('last_synced_at', function($employee) {
                        return $this->getEmployeeLastSyncedAt($employee->employee_id, $employee->is_verified, $employee->is_emp_active);
                    });
                    
                if(!isset($forDashboard) || $forDashboard != 1)
            	{
		            $datatableObj->remove_column('is_srac_share_enabled')
			                    ->remove_column('is_srac_org_share_enabled')
			                    ->remove_column('is_srac_retail_share_enabled')
			                    ->remove_column('is_copy_to_profile_enabled')
			                    ->remove_column('is_soc_share_enabled')
			                    ->remove_column('is_soc_facebook_enabled')
			                    ->remove_column('is_soc_twitter_enabled')
			                    ->remove_column('is_soc_linkedin_enabled')
			                    ->remove_column('is_soc_whatsapp_enabled')
			                    ->remove_column('is_soc_email_enabled')
			                    ->remove_column('is_soc_sms_enabled')
			                    ->remove_column('is_soc_other_enabled')
			                    ->remove_column('is_file_save_share_enabled')
			                    ->remove_column('is_screen_share_enabled')
			                    ->remove_column('has_web_access');

            		$datatableObj->add_column('selectEmp', function($employee) {
                        return $this->getEmployeeDatatableSelect($employee->employee_id, $employee->is_verified, $employee->is_emp_active);
                    }, 0);

            		$datatableObj->add_column('shareRights', function($employee) {
                        return $this->getEmployeeShareRightStr($employee, $employee->is_verified, $employee->is_emp_active);
                    });
                }
                else
                {
		            $datatableObj->remove_column('is_file_save_share_enabled')
			                    ->remove_column('is_screen_share_enabled');
                }

            	$orgScreenShareEnabled = OrganizationClass::orgHasScreenShareEnabled($this->organizationId);
            	$orgFileSaveShareEnabled = OrganizationClass::orgHasFileSaveShareEnabled($this->organizationId);

            	$datatableObj->add_column('action', function($employee) use($forDashboard, $orgFileSaveShareEnabled, $orgScreenShareEnabled) {
                    return $this->getEmployeeDatatableButton($forDashboard, $employee->employee_id, $employee->is_verified, $employee->is_emp_active, $orgFileSaveShareEnabled, $employee->is_file_save_share_enabled, $orgScreenShareEnabled, $employee->is_screen_share_enabled);
                });
			}
            
            if($forGroupList == 1)
            {
            	$datatableObj->add_column('selectEmp', function($employee) use ($selEmployeeIdArr) {
                        return $this->getGroupEmployeeDatatableButton($employee->employee_id, $employee->is_verified, $employee->is_emp_active, $selEmployeeIdArr);
                    }, 0);
			}
            
            if($forSend == 1)
            {
            	$datatableObj->add_column('selectEmp', function($employee) {
                        return $this->getEmployeeDatatableSelect($employee->employee_id, $employee->is_verified, $employee->is_emp_active);
                    }, 0);
			}
           
           	return $datatableObj->make();
        }
    }

    private function getEmployeeLastSyncedAt($empId, $isVerified, $isActive)
    {
    	$lastSyncTs = '';

    	if($isVerified == 1 && $isActive == 1)
    	{
    		$orgUser = OrganizationUser::ofOrganization($this->organizationId)->byEmpId($empId)->first();
    		if(isset($orgUser))
    		{
    			$orgUserEmail = $orgUser->appuser_email;

    			$appuser = Appuser::ofEmail($orgUserEmail)->first();

		    	if(isset($appuser))
		    	{
		    		$lastSyncTs = $appuser->last_sync_ts;
		    		$lastSyncTs = dbDateTimeToDispDateTimeWithDefaultTZ($lastSyncTs);
		    	}
    		}
    	}

    	return $lastSyncTs;
    }

    private function getEmployeeShareRightStr($employee, $isVerified, $isActive)
    {
    	/*
		$empConstantModelObj = New OrgEmployeeConstant;
        $empConstantModelObj->setConnection($this->orgDbConName);
        $employeeConstant = $empConstantModelObj->ofEmployee($empId)->first();
		*/

        $shareStr = '';
        if($isVerified == 1)
        {			                    
	        $sracShareEnabled = $employee->is_srac_share_enabled;
	        $sracOrgShareEnabled = $employee->is_srac_org_share_enabled;
	        $sracRetailShareEnabled = $employee->is_srac_retail_share_enabled;
	        $sracCopyToProfileEnabled = $employee->is_copy_to_profile_enabled;
	        $socShareEnabled = $employee->is_soc_share_enabled;
	        $socFacebookEnabled = $employee->is_soc_facebook_enabled;
	        $socTwitterEnabled = $employee->is_soc_twitter_enabled;
	        $socLinkedinEnabled = $employee->is_soc_linkedin_enabled;
	        $socWhatsappEnabled = $employee->is_soc_whatsapp_enabled;
	        $socEmailEnabled = $employee->is_soc_email_enabled;
	        $socSmsEnabled = $employee->is_soc_sms_enabled;
	        $socOtherEnabled = $employee->is_soc_other_enabled;

	        $sameStrSep = ', ';
	        $diffStrSep = '<br/>';

	        $sracShareStr = '';
	        $socialShareStr = '';

	        if($sracShareEnabled == 1)
	        {
	        	$sracShareStr .= $sracShareStr != '' ? $sameStrSep : '';
	        	$sracShareStr .= 'SRAC Share';
	        }

	        if($sracOrgShareEnabled == 1)
	        {
	        	$sracShareStr .= $sracShareStr != '' ? $sameStrSep : '';
	        	$sracShareStr .= 'Organization Users';
	        }

	        if($socShareEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'Social Share';
	        }

	        if($socFacebookEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'Facebook';
	        }

	        if($socTwitterEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep: '';
	        	$socialShareStr .= 'Twitter';
	        }

	        if($socLinkedinEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'LinkedIn';
	        }

	        if($socWhatsappEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'WhatsApp';
	        }

	        if($socEmailEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'Email';
	        }

	        if($socSmsEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'SMS';
	        }

	        if($socOtherEnabled == 1)
	        {
	        	$socialShareStr .= $socialShareStr != '' ? $sameStrSep : '';
	        	$socialShareStr .= 'Other';
	        }

	        if($sracShareStr != '')
	        {
	        	$shareStr .= $shareStr != '' ? $diffStrSep : '';
	        	$shareStr .= $sracShareStr;
	        }

	        if($socialShareStr != '')
	        {
	        	$shareStr .= $shareStr != '' ? $diffStrSep : '';
	        	$shareStr .= $socialShareStr;
	        }
        }

        return $shareStr;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getEmployeeDatatableButton($forDashboard, $id, $isVerified, $isActive, $orgFileSaveShareEnabled, $isFileSaveShareEnabled, $orgScreenShareEnabled, $isScreenShareEnabled)
    {
        $buttonHtml = "";
    	$id = sracEncryptNumberData($id);
        if($this->modulePermissions->module_edit == 1)
        {
        	if($isVerified == 1)
        	{
				$buttonHtml .= '&nbsp;<button onclick="loadModifyEmployeeQuotaModal(\''.$id.'\');" class="btn btn-xs btn-warning"><i class="fa fa-database"></i>&nbsp;&nbsp;Quota</button>';
            	$buttonHtml .= '&nbsp;<button onclick="loadModifyEmployeeShareRightModal(\''.$id.'\');" class="btn btn-xs btn-orange"><i class="fa fa-check-square-o"></i>&nbsp;&nbsp;Share Rights</button>';

                if(!isset($forDashboard) || $forDashboard != 1)
            	{
            		if($orgFileSaveShareEnabled)
            		{
            			if(!isset($isFileSaveShareEnabled) || $isFileSaveShareEnabled == 1)
		            	{
	            			$buttonHtml .= '&nbsp;<button onclick="modifyEmployeeFileSaveShare(\''.$id.'\', '.$isFileSaveShareEnabled.');" class="btn btn-xs btn-danger"><i class="fa fa-file-o"></i>&nbsp;&nbsp;Disable File Save</button>';
	            		}
	            		else
	            		{
	            			$buttonHtml .= '&nbsp;<button onclick="modifyEmployeeFileSaveShare(\''.$id.'\', '.$isFileSaveShareEnabled.');" class="btn btn-xs btn-olive"><i class="fa fa-file-o"></i>&nbsp;&nbsp;Enable File Save</button>';
	            		}
            		}
		              
            		if($orgScreenShareEnabled)
            		{  
		                if(isset($isScreenShareEnabled) && $isScreenShareEnabled == 1)
		            	{
	            			$buttonHtml .= '&nbsp;<button onclick="modifyEmployeeScreenShare(\''.$id.'\', '.$isScreenShareEnabled.');" class="btn btn-xs btn-danger"><i class="fa fa-file-o"></i>&nbsp;&nbsp;Disable Screen Share</button>';
	            		}
	            		else
	            		{
	            			$buttonHtml .= '&nbsp;<button onclick="modifyEmployeeScreenShare(\''.$id.'\', '.$isScreenShareEnabled.');" class="btn btn-xs btn-olive"><i class="fa fa-file-o"></i>&nbsp;&nbsp;Enable Screen Share</button>';
	            		}
	            	}
            	}
			
				if($isActive == 1)
				{
					$buttonHtml .= '&nbsp;<button onclick="loadRestoreEmployeeContentModal(\''.$id.'\');" class="btn btn-xs btn-olive"><i class="fa fa-at"></i>&nbsp;&nbsp;Restore Content</button>';

					$buttonHtml .= '&nbsp;<button onclick="detachSubscribedEmployee(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-unlink"></i>&nbsp;&nbsp;Detach</button>';
				}	
			}
			else
			{
				if($isActive == 1)
				{
					$buttonHtml .= '&nbsp;<button onclick="sendEmployeeCredentialMail(\''.$id.'\');" class="btn btn-xs btn-purple"><i class="fa fa-envelope"></i>&nbsp;&nbsp;Credentials</button>';

					$buttonHtml .= '&nbsp;<button onclick="viewEmployeeCredentials(\''.$id.'\');" class="btn btn-xs btn-purple"><i class="fa fa-envelope"></i>&nbsp;&nbsp;View Credentials</button>';
				}
			}
			
			if($isActive == 0)
				$buttonHtml .= '&nbsp;<button onclick="loadModifyEmployeeEmail(\''.$id.'\');" class="btn btn-xs btn-olive"><i class="fa fa-at"></i>&nbsp;&nbsp;Modify Subscriber</button>';

            $buttonHtml .= '&nbsp;<button onclick="loadQuickAddEditEmployeeModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        }
        if($this->modulePermissions->module_delete == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="deleteEmployee(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        }
        return $buttonHtml;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getEmployeeDatatableSelect($id, $isVerified, $isActive)
    {
    	$id = sracEncryptNumberData($id);

    	$buttonHtml = "";
    	// if($isVerified == 1 && $isActive == 1) 
    	{
			$buttonHtml = "<label><input type='checkbox' name='empIsSelected[]' class='empIsSelected' value='$id'></label>";
		}
        return $buttonHtml;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getGroupEmployeeDatatableButton($id, $isVerified, $isActive, $selEmployeeIdArr)
    {
    	$isSelected = '';
    	if(in_array($id, $selEmployeeIdArr))
    	{
    		$isSelected = 'checked';
    	}

    	$id = sracEncryptNumberData($id);

    	$buttonHtml = "<label><input type='checkbox' name='empIsSelected[]' class='empIsSelected' value='$id' $isSelected></label>";
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

            $id = Input::get('empId');
            $isViewFlag = Input::get('isView');
            
    		$id = sracDecryptNumberData($id);

            $isView = FALSE;
            if(isset($isViewFlag) && $isViewFlag == 1)
            	$isView = TRUE;
            
            $pageName = 'Add'; 
            
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgId($this->organizationId);
	        
	        $allocUsrCnt = 0;
	        $availUsrCnt = 0;
	        $userCntParams = $depMgmtObj->getUserCountParams();
	        if(isset($userCntParams['alloc']))
	        {
	        	$allocUsrCnt = $userCntParams['alloc'];
	        	$availUsrCnt = $userCntParams['avail'];
			}
	        
	        $allocOrgQuota = 0;
	        $availOrgQuota = 0;
	        $quotaParams = $depMgmtObj->getQuotaParams();
	        if(isset($quotaParams['alloc']))
	        {
	        	$allocOrgQuota = $quotaParams['alloc'];
	        	$availOrgQuota = $quotaParams['avail'];
			}

            $employee = NULL;
            $employeeDeptId = 0;
            $employeeDesigId = 0;
            $existingBadgeIdArr = array();
            $existingBadges = array();
            if($id > 0)
            {
                $empModelObj = New OrgEmployee;
                $empModelObj->setConnection($this->orgDbConName);

                $employee = $empModelObj->byId($id)->first();
                if(isset($employee))
                {
					$employee->dob_disp = CommonFunctionController::convertDbToDispDate($employee->dob);	
	            	$employee->photo_url = OrganizationClass::getOrgEmployeePhotoAssetUrl($this->organizationId, $employee->photo_filename);

				    $employeeDeptId = $employee->department_id;
				    $employeeDesigId = $employee->designation_id;   

				   	$employee->department_id = sracEncryptNumberData($employeeDeptId);
				   	$employee->designation_id = sracEncryptNumberData($employeeDesigId);
		        	
		            $badgeModelObj = New OrgEmployeeBadge;
		            $badgeModelObj->setConnection($this->orgDbConName);
		            $empBadges = $badgeModelObj->ofEmployee($id)->get();
		            
		            foreach($empBadges as $empBadge)
		            {
						array_push($existingBadges, sracEncryptNumberData($empBadge->badge_id));
						array_push($existingBadgeIdArr, ($empBadge->badge_id));
					}
				}
                $pageName = 'Edit';     
            }
            
            $orgUserFields = OrganizationUserField::ofOrganization($this->organizationId)->active()
            				->joinFieldTypeTable()->orderByPosition()->get();
            				
            $fldModelObj = New OrgEmployeeFieldValue;
            $fldModelObj->setConnection($this->orgDbConName);
            $empFieldValues = $fldModelObj->ofEmployee($id)->get();
            $arrEmpFieldValue = array();
            foreach($empFieldValues as $empFldVal)
            {
				$arrEmpFieldValue[$empFldVal->org_field_id] = $empFldVal->field_value;
			}
			
            
            $deptModelObj = New OrgDepartment;
            $deptModelObj->setConnection($this->orgDbConName);
                
            $departments = $deptModelObj->active()->get();
            $arrDepartment = array();
            foreach($departments as $department)
            {
            	$consDeptId = $department->department_id;
            	$consDeptName = $department->department_name;
            	$encConsDeptId = sracEncryptNumberData($consDeptId);

            	if($consDeptId == $employeeDeptId)
            	{
            		$arrDepartment[$employee->department_id] = $consDeptName;
            	}
            	else
            	{
            		$arrDepartment[$encConsDeptId] = $consDeptName;
            	}
			}
            
            $desigModelObj = New OrgDesignation;
            $desigModelObj->setConnection($this->orgDbConName);
            
            $designations = $desigModelObj->active()->get();
            $arrDesignation = array();
            foreach($designations as $designation)
            {
            	$consDesigId = $designation->designation_id;
            	$consDesigName = $designation->designation_name;
            	$encConsDesigId = sracEncryptNumberData($consDesigId);

            	if($consDesigId == $employeeDesigId)
            	{
            		$arrDesignation[$employee->designation_id] = $consDesigName;
            	}
            	else
            	{
            		$arrDesignation[$encConsDesigId] = $consDesigName;
            	}
			}
            
            $badgeModelObj = New OrgBadge;
            $badgeModelObj->setConnection($this->orgDbConName);
            
            $badges = $badgeModelObj->active()->get();
            $arrBadge = array();
            foreach($badges as $badge)
            {
            	$consBadgeId = $badge->badge_id;
            	$consBadgeName = $badge->badge_name;
            	$encConsBadgeId = sracEncryptNumberData($consBadgeId);

            	if(in_array($consBadgeId, $existingBadgeIdArr))
            	{
            		$badgeIndex = array_search($consBadgeId, $existingBadgeIdArr);
            		$badgeIdPreEnc = $existingBadges[$badgeIndex];
            		$arrBadge[$badgeIdPreEnc] = $consBadgeName;
            	}
            	else
            	{
            		$arrBadge[$encConsBadgeId] = $consBadgeName;
            	}
			}
			
			$genderMale = Config::get('app_config.gender_male'); 
			$genderFemale = Config::get('app_config.gender_female'); 
			$genderOther = Config::get('app_config.gender_other'); 
			$arrGender = array();
			$arrGender[$genderMale] = $genderMale;
			$arrGender[$genderFemale] = $genderFemale;
			$arrGender[$genderOther] = $genderOther;
			
        	$intJs = array("/dist/bootstrap-datepicker/dist/js/bootstrap-datepicker.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js");  
        	$intCss = array("/dist/bootstrap-datepicker/dist/css/datepicker.css","/dist/bootstrap-datepicker/dist/css/common_dt_modal.css");  
            
            $data = array();
            $data['id'] = sracEncryptNumberData($id);
            $data['employee'] = $employee;
            $data['departmentArr'] = $arrDepartment;
            $data['designationArr'] = $arrDesignation;
            $data['badgeArr'] = $arrBadge;
            $data['genderArr'] = $arrGender;
            $data['existingBadges'] = $existingBadges;
            $data['page_description'] = $pageName.' '.'Appuser';
            $data['usrtoken'] = $this->userToken;
        	$data['intJs'] = $intJs;
        	$data['intCss'] = $intCss;
        	$data['orgUserFields'] = $orgUserFields;
        	$data['empFieldValueArr'] = $arrEmpFieldValue;
        	$data['isView'] = $isView;
        	$data['availOrgQuota'] = $availOrgQuota;
        	$data['availUsrCnt'] = $availUsrCnt;
        	
        	if($availUsrCnt > 0 || isset($employee))
	        {
	            $_viewToRender = View::make('orgemployee.partialview._addEditModal', $data);				
			}
			else
			{
				$warningMsg = "Cannot add any more users. Run out of quota";
				$warningData = array('warningMsg' => $warningMsg);
	            $_viewToRender = View::make('orgemployee.partialview._warningModal', $warningData);	
			}
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
        $id = $request->input('empId');  
        $empNo = $request->input('emp_no');
        $empName = $request->input('emp_name');
        $empName = CommonFunctionController::convertStringToCap($empName); 
        $empEmail = $request->input('email');
        $empContact = $request->input('contact');
        $empDept = $request->input('dept_id');
        $empDesig = $request->input('desig_id');
        $empBadgeArr = $request->input('badge_id');
        $empDob = $request->input('dob');
        $empDob = CommonFunctionController::convertDispToDbDate($empDob);
        $empStartDt = $request->input('start_date');
        $empStartDt = CommonFunctionController::convertDispToDbDate($empStartDt);
        $empEmerContact = $request->input('emergency_contact');
        $empGender = $request->input('gender');
        
		$empImg = Input::file('photo_img');
		$imgChanged = Input::get('image_changed');
        
        $depMgmtObj = New ContentDependencyManagementClass;
        $depMgmtObj->withOrgId($this->organizationId);
        
        $userCntParams = $depMgmtObj->getUserCountParams();
        if(isset($userCntParams['alloc']))
        {
        	$allocUsrCnt = $userCntParams['alloc'];
        	$availUsrCnt = $userCntParams['avail'];
		}

		if(isset($empBadgeArr))
		{
			if(!is_array($empBadgeArr) && $empBadgeArr != "")
			{
				$empBadgeArr = json_decode($empBadgeArr);
			}

			if(!is_array($empBadgeArr))
			{
				$empBadgeArr = array();
			}
		}
		else
		{
			$empBadgeArr = array();
		}

    	$id = sracDecryptNumberData($id);
		$empDept = sracDecryptNumberData($empDept);
		$empDesig = sracDecryptNumberData($empDesig);
		$empBadgeArr = sracDecryptNumberArrayData($empBadgeArr);


        $response = array();
        $status = 0;
        $msg = "";
        
        $employeeDetails = array();
        $employeeDetails['employee_no'] = $empNo;
        $employeeDetails['employee_name'] = $empName;
        $employeeDetails['contact'] = $empContact;
        $employeeDetails['dob'] = $empDob;
        $employeeDetails['department_id'] = $empDept;
        $employeeDetails['designation_id'] = $empDesig;
        $employeeDetails['start_date'] = $empStartDt;
        $employeeDetails['emergency_contact'] = $empEmerContact;
        $employeeDetails['gender'] = $empGender;

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $status = 1;

                $modelObj = New OrgEmployee;
                $modelObj->setConnection($this->orgDbConName);

                $employee = $modelObj->byId($id)->first();
                $employeeDetails['updated_by'] = $this->userId;
                $employee->update($employeeDetails);
                
                $employeeId = $id;

                $msg = 'Employee updated!';        
            }
        }
        else
        {
            if($this->modulePermissions->module_add == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            elseif(isset($availUsrCnt) && $availUsrCnt >= 1)
            {
                $status = 1;
                $msg = 'Employee added!';     
				
				$empEmail = sanitizeEmailString($empEmail);          
                                
                $employeeDetails['email'] = $empEmail;
                $employeeDetails['created_by'] = $this->userId;
                $employeeDetails['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                $employeeId = $this->addNewEmployee($employeeDetails);
            }
            else
            {
                $status = -1;
                $msg = "User count quota finished";
			}
        }
        
        if(isset($employeeId) && $employeeId > 0)
        {			            
            $depMgmtObj = New ContentDependencyManagementClass;
    		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $employeeId);
    		$depMgmtObj->recalculateOrgSubscriptionParams();
    		$depMgmtObj->recalculateUserQuota(TRUE);
        	
	    	$empImgFileName = "";
	        if(isset($empImg) && File::exists($empImg) && $empImg->isValid()) 
	        {
	            $empImgFileName = FileUploadClass::uploadOrgEmployeeImage($empImg, $this->organizationId);
	        }
    	
	    	if($id == 0 || $imgChanged == 1 || $empImgFileName != "")
	        {		        
                $empModelObj = New OrgEmployee;
                $empModelObj->setConnection($this->orgDbConName);
                $employee = $empModelObj->byId($employeeId)->first();
                if(isset($employee))
                {
                	$employee->photo_filename = $empImgFileName;
                	$employee->save();
				}
			}
        
        	$existingBadges = array();
        	
            $modelObj = New OrgEmployeeBadge;
            $modelObj->setConnection($this->orgDbConName);
            $badgeTableName = $modelObj->table;
            $empBadges = $modelObj->ofEmployee($employeeId)->get();
            
            foreach($empBadges as $badgeInd => $empBadge)
            {
        		$empBadgeId = $empBadge->employee_badge_id;
				if(!isset($empBadgeArr) || !in_array($empBadgeId, $empBadgeArr))
				{
					$empBadge->delete();
				}
				else
				{
					array_push($existingBadges, $empBadgeId);
				}
			}
			
			$badgeDetails = array();
			$badgeDetails['employee_id'] = $employeeId;
			if(isset($empBadgeArr))
			{
				foreach($empBadgeArr as $badgeInd => $badgeId)
				{
					if($badgeId > 0 && !in_array($badgeId, $existingBadges))
					{
						$badgeDetails['badge_id'] = $badgeId;
						DB::connection($this->orgDbConName)->table($badgeTableName)->insert($badgeDetails);
					}
				}	
			}
            
			$fieldInputPrefix = Config::get("app_config_user_field.field_input_prefix");
            $orgUserFields = OrganizationUserField::ofOrganization($this->organizationId)->active()->get();
        
            $fldModelObj = New OrgEmployeeFieldValue;
            $fldModelObj->setConnection($this->orgDbConName);
        	$empFldTableName = $fldModelObj->table; 
            
        	foreach($orgUserFields as $usrField)
        	{
				$fieldId = $usrField->org_field_id;
				$fieldInputName = $fieldInputPrefix.$fieldId;
				$fieldValue = $request->$fieldInputName;
				
				if($fieldId > 0 && isset($fieldValue) && $fieldValue != "")
				{
					$fieldData = array();
					$fieldData['field_value'] = $fieldValue;
					
					$empField = $fldModelObj->ofEmployee($employeeId)->ofField($fieldId)->first();				
					if(!isset($empField))
					{
						//create
						$fieldData['employee_id'] = $employeeId;
						$fieldData['org_field_id'] = $fieldId;
						$fieldData['created_at'] = CommonFunctionClass::getCurrentTimestamp();
						
						$empFldId = DB::connection($this->orgDbConName)->table($empFldTableName)->insertGetId($fieldData);    
					}
					else
					{
						//update
	                	$empField->update($fieldData);
					}
				}					
			}
			
			$this->sendOrgEmployeeDetailsToDevice($employeeId);				
		}
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }
    
    private function addNewEmployee($employeeDetails)
    {
    	$empName = $employeeDetails['employee_name'];
    	$empEmail = $employeeDetails['email'];
                
        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);
        $empTableName = $modelObj->table; 
    	
		$employeeId = DB::connection($this->orgDbConName)->table($empTableName)->insertGetId($employeeDetails);                
        $orgUser = OrganizationUser::byEmpId($employeeId)->ofOrganization($this->organizationId)->first();
        
        $orgEmployee = $modelObj->byId($employeeId)->first();
        if(isset($orgEmployee))
        {
			$encOrgEmpId = Hash::make($this->organizationId."_".$employeeId);
	        $orgEmployee->org_emp_key = $encOrgEmpId;
	        $orgEmployee->save();
		}	    	
		
		if(!isset($orgUser))
		{
			$verificationCode = CommonFunctionClass::generateVerificationCode();
			$encVerificationCode = Crypt::encrypt($verificationCode);
			
			$organizationUser = New OrganizationUser;
			$organizationUser->organization_id = $this->organizationId;
			$organizationUser->emp_email = $empEmail;
			$organizationUser->emp_id = $employeeId;
			$organizationUser->appuser_email = "";
			$organizationUser->is_verified = 0;
			$organizationUser->verification_code = $encVerificationCode;
			$organizationUser->save();
			
			MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
		}
		
		return $employeeId;
	}
	
	public function sendCredentialMail(Request $request)
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
        
        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
       	 	$isMulti = Input::get('isMulti');        
	        if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			if($isMulti == 1) {
				$empIdArr = json_decode($request->input('empIdArr'));
			}
			else{
	        	$id = $request->input('empId');
				$empIdArr = array();
				array_push($empIdArr, $id);
			}

	        if(isset($empIdArr) && count($empIdArr) > 0)
	        {
    			$empIdArr = sracDecryptNumberArrayData($empIdArr);

	        	$actionPerformed = 0;
	        	$verifiedCount = 0;
	        	$inactiveCount = 0;
            	foreach($empIdArr as $empId) 
            	{
	        		$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        		$employee =  $depMgmtObj->getPlainEmployeeObject();
        			// $response['employee'.$empId] = $employee;
	        		
	        		if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 0)
	        		{	   
	        			$actionPerformed++;

	        			$empId = $employee->employee_id;
	        			$empName = $employee->employee_name;
	        			$empEmail = $employee->email;
	        			
	        			$orgUser = OrganizationUser::ofOrganization($this->organizationId)->byEmpId($empId)->first();
        				// $response['orgUser'.$empId] = $orgUser;
	        			if(isset($orgUser))
	        			{
	        				$encVerificationCode = $orgUser->verification_code;
							$verificationCode = Crypt::decrypt($encVerificationCode);
        					// $response['verificationCode'.$empId] = $verificationCode;

							MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
						}
						else
						{							
							$verificationCode = CommonFunctionClass::generateVerificationCode();
							$encVerificationCode = Crypt::encrypt($verificationCode);
							
							$organizationUser = New OrganizationUser;
							$organizationUser->organization_id = $this->organizationId;
							$organizationUser->emp_email = $empEmail;
							$organizationUser->emp_id = $empId;
							$organizationUser->appuser_email = "";
							$organizationUser->is_verified = 0;
							$organizationUser->verification_code = $encVerificationCode;
							$organizationUser->save();
							
							MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
						}
        				// $response['orgUser'] = $orgUser;
        				// $response['empId'] = $empId;
        				// $response['empName'] = $empName;
        				// $response['empEmail'] = $empEmail;
        				// $response['organizationId'] = $this->organizationId;
					}
					else
					{
						if($employee->is_verified == 1)
						{
							$verifiedCount++;
						}
						else if($employee->is_active == 0)
						{
							$inactiveCount++;
						}
					}
				}

				if($verifiedCount + $inactiveCount > 0)
				{
					if($verifiedCount > 0)
					{
						$msg = "some appuser account(s) were already verified";
					}


					if($inactiveCount > 0)
					{
						if($msg != "")
						{
							$msg .= " and ";
						}
						$msg = "some appuser account(s) were set to inactive";
					}

					$msg = "The operation wasn't performed entirely as " . $msg;
				}
				else
				{
					$msg = "The operation was performed successfully";
				}

				if($actionPerformed == 0)
				{
					$status = -1;
					$msg = "The operation was unsuccessfully";
				}
				else
				{
	        		$status = 1;
				}
			}
			else
			{
				$status = -1;
            	$msg = Config::get("app_config_notif.err_invalid_data");
			}
		}		

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}

	public function loadCredentialModal(Request $request)
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
        
        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
       	 	$isMulti = Input::get('isMulti');        
	        if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			if($isMulti == 1) {
				$empIdArr = json_decode($request->input('empIdArr'));
			}
			else{
	        	$id = $request->input('empId');
				$empIdArr = array();
				array_push($empIdArr, $id);
			}

	        if(isset($empIdArr) && count($empIdArr) > 0)
	        {
    			$empIdArr = sracDecryptNumberArrayData($empIdArr);

    			$compiledCredentialData = array();

	        	$actionPerformed = 0;
	        	$verifiedCount = 0;
	        	$inactiveCount = 0;
            	foreach($empIdArr as $empId) 
            	{
	        		$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        		$employee =  $depMgmtObj->getPlainEmployeeObject();
	        		
	        		if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 0)
	        		{	   
	        			$actionPerformed++;

	        			$empId = $employee->employee_id;
	        			$empName = $employee->employee_name;
	        			$empEmail = $employee->email;

	        			$verificationCode = "";
	        			
	        			$orgUser = OrganizationUser::ofOrganization($this->organizationId)->byEmpId($empId)->first();
	        			if(isset($orgUser))
	        			{
	        				$encVerificationCode = $orgUser->verification_code;
							$verificationCode = Crypt::decrypt($encVerificationCode);
						}
						else
						{							
							$verificationCode = CommonFunctionClass::generateVerificationCode();
							$encVerificationCode = Crypt::encrypt($verificationCode);
							
							$organizationUser = New OrganizationUser;
							$organizationUser->organization_id = $this->organizationId;
							$organizationUser->emp_email = $empEmail;
							$organizationUser->emp_id = $empId;
							$organizationUser->appuser_email = "";
							$organizationUser->is_verified = 0;
							$organizationUser->verification_code = $encVerificationCode;
							$organizationUser->save();
							
							MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
						}

						$compiledCredentialObj = array();
						$compiledCredentialObj['empName'] = $empName;
						$compiledCredentialObj['empEmail'] = $empEmail;
						$compiledCredentialObj['verificationCode'] = $verificationCode;

						array_push($compiledCredentialData, $compiledCredentialObj);
					}
					else
					{
						if($employee->is_verified == 1)
						{
							$verifiedCount++;
						}
						else if($employee->is_active == 0)
						{
							$inactiveCount++;
						}
					}
				}

	            $data = array();
	            $data['isMulti'] = $isMulti;
	            $data['usrtoken'] = $this->userToken;
	            $data['compiledCredentialData'] = $compiledCredentialData;
	           
	            $_viewToRender = View::make('orgemployee.partialview._employeeCredentialInfoModal', $data);
	            $_viewToRender = $_viewToRender->render();
	            
	            $response['view'] = $_viewToRender;

				if($verifiedCount + $inactiveCount > 0)
				{
					if($verifiedCount > 0)
					{
						$msg = "some appuser account(s) were already verified";
					}


					if($inactiveCount > 0)
					{
						if($msg != "")
						{
							$msg .= " and ";
						}
						$msg = "some appuser account(s) were set to inactive";
					}

					$msg = "The operation wasn't performed entirely as " . $msg;
				}
				else
				{
					$msg = "The operation was performed successfully";
				}

				if($actionPerformed == 0)
				{
					$status = -1;
					$msg = "The operation was unsuccessfully";
				}
				else
				{
	        		$status = 1;
				}
			}
			else
			{
				$status = -1;
            	$msg = Config::get("app_config_notif.err_invalid_data");
			}
		}		

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}

    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadModifyQuotaModal()
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

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('empId');
            $isMulti = Input::get('isMulti');
    		
    		$id = sracDecryptNumberData($id);

            if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			$multiIdArr = array();
			if($isMulti == 1) {
				$multiIdArr = json_decode(Input::get('empIdArr'));
    			
    			$multiIdArr = sracDecryptNumberArrayData($multiIdArr);
			}

            $employee = NULL;
            if($id > 0)
            {
                $empModelObj = New OrgEmployeeConstant;
                $empModelObj->setConnection($this->orgDbConName);
                $employee = $empModelObj->joinEmployeeTable()->ofEmployee($id)->first();                    
            }
            
            $data = array();
            $data['isMulti'] = $isMulti;
            $data['orgEmpIdArr'] = json_encode($multiIdArr);
            $data['employee'] = $employee;
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgemployee.partialview._modifyQuotaModal', $data);
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
    public function saveQuotaDetails(Request $request)
    {
        $empQuota = $request->input('empQuota');
		
        $isMulti = Input::get('isMulti');        
        if(!isset($isMulti)) {
			$isMulti = 0;
		}
		
		if($isMulti == 1) {
			$empIdArr = json_decode($request->input('empIdArr'));
		}
		else{
        	$id = $request->input('empId');
			$empIdArr = array();
			array_push($empIdArr, $id);
		}

    	$empIdArr = sracDecryptNumberArrayData($empIdArr);

        $status = 0;
        $msg = "";
        $response = array();

        if(isset($empIdArr) && count($empIdArr) > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
                $totalSelEmployeeCount = count($empIdArr);
                $totalEmployeeConstantNotFoundCount = 0;
                $totalEmployeeQuotaInsufficientCount = 0;
                $totalEmployeeQuotaModCount = 0;

            	foreach($empIdArr as $empId) 
            	{                
	                $empModelObj = New OrgEmployeeConstant;
	                $empModelObj->setConnection($this->orgDbConName);
	                $employee = $empModelObj->ofEmployee($empId)->first();
	                
	                if(isset($employee))
			        {
				        $updatedAllotMbs = $empQuota;
				        $updatedAllotKbs = $updatedAllotMbs*1024;
				        
			        	//modify quota accordingly
				        $currAllottedKb = $employee->attachment_kb_allotted;
				        $currAvailableKb = $employee->attachment_kb_available;

				        $diffAllotKbs = $updatedAllotKbs - $currAllottedKb;
				        $updatedAvailableKbs = $currAvailableKb + $diffAllotKbs;

				        if($updatedAllotKbs >= 0 && $updatedAvailableKbs >= 0)
				        {
				        	$totalEmployeeQuotaModCount++;

				            $employee->attachment_kb_allotted = $updatedAllotKbs;
				            $employee->attachment_kb_available = $updatedAvailableKbs;
				            $employee->save();
				            
				            $depMgmtObj = New ContentDependencyManagementClass;
	                		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	                		$depMgmtObj->recalculateOrgSubscriptionParams();
	                		$depMgmtObj->recalculateUserQuota(TRUE);
				            
				            //Quota changed mail
				            MailClass::sendOrgQuotaChangedMail($this->organizationId, $empId, $currAllottedKb);
							$this->sendOrgEmployeeQuotaToDevice($empId, $this->organizationId);
				        }
				        else
				        {
				        	$totalEmployeeQuotaInsufficientCount++;
				        }
					}
					else
					{
						$totalEmployeeConstantNotFoundCount++;
					}

					if($totalSelEmployeeCount == $totalEmployeeQuotaModCount)
					{
	                	$status = 1;
		                $msg = 'Employee Quota updated!';  
					}
   					else
   					{
   						$consErrorMsg = '';

   						if($totalEmployeeConstantNotFoundCount > 0)
   						{
   							$consErrorMsg .= $consErrorMsg != '' ? ', ' : '';
   							$consErrorMsg .= 'Some of the employee account(s) were not verified';
   						}

   						if($totalEmployeeQuotaInsufficientCount > 0)
   						{
   							$consErrorMsg .= $consErrorMsg != '' ? ', ' : '';
   							$consErrorMsg .= 'Some of the employee account(s) had insufficient space for modification';
   						}

	                	$status = -1;
		                $msg = $consErrorMsg;  
   					}
	            }   
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }
    
    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadModifyShareRightModal()
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

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('empId');
            $isMulti = Input::get('isMulti');
            
    		$id = sracDecryptNumberData($id);

            if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			$multiIdArr = array();
			if($isMulti == 1) {
				$multiIdArr = json_decode(Input::get('empIdArr'));
    			
    			$multiIdArr = sracDecryptNumberArrayData($multiIdArr);
			}

            $employee = NULL;
            if($id > 0)
            {
                $empModelObj = New OrgEmployeeConstant;
                $empModelObj->setConnection($this->orgDbConName);
                $employee = $empModelObj->joinEmployeeTable()->ofEmployee($id)->first();                    
            }
            
            $hasRetailShareRights = OrganizationClass::orgHasRetailShareEnabled($this->organizationId);
            
            $data = array();
            $data['isMulti'] = $isMulti;
            $data['orgEmpIdArr'] = json_encode($multiIdArr);
            $data['employee'] = $employee;
            $data['usrtoken'] = $this->userToken;
            $data['hasRetailShareRights'] = $hasRetailShareRights;
           
            $_viewToRender = View::make('orgemployee.partialview._modifyShareRightModal', $data);
            $_viewToRender = $_viewToRender->render();
            
            $response['view'] = $_viewToRender;
            // $response['idArr'] = $multiIdArr;
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
    public function saveShareRightDetails(Request $request)
    {        
    	$isSracEnabled = $request->input('is_srac_share_enabled');
    	$isSracOrgEnabled = $request->input('is_srac_org_share_enabled');
    	$isSracRetailShareEnabled = $request->input('is_srac_retail_share_enabled');
    	$isCopyToProfileEnabled = $request->input('is_copy_to_profile_enabled');
		$isFacebookEnabled = $request->input('is_soc_facebook_enabled');
		$isTwitterEnabled = $request->input('is_soc_twitter_enabled');
		$isLinkedinEnabled = $request->input('is_soc_linkedin_enabled');
		$isWhatsappEnabled = $request->input('is_soc_whatsapp_enabled');
		$isEmailEnabled = $request->input('is_soc_email_enabled');
		$isSmsEnabled = $request->input('is_soc_sms_enabled');
		$isOtherEnabled = $request->input('is_soc_other_enabled');
		
        $isMulti = Input::get('isMulti');        
        if(!isset($isMulti)) {
			$isMulti = 0;
		}
		
		if($isMulti == 1) {
			$empIdArr = json_decode($request->input('empIdArr'));
		}
		else{
        	$id = $request->input('empId');
			$empIdArr = array();
			array_push($empIdArr, $id);
		}

    	$empIdArr = sracDecryptNumberArrayData($empIdArr);

        $status = 0;
        $msg = "";
        $response = array();

        if(isset($empIdArr) && count($empIdArr) > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
            	$hasRetailShareRights = OrganizationClass::orgHasRetailShareEnabled($this->organizationId);
            	
	        	$actionPerformed = 0;
	        	$unverifiedCount = 0;
	        	$inactiveCount = 0;

            	foreach($empIdArr as $empId) 
            	{
	        		$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        		$employee =  $depMgmtObj->getPlainEmployeeObject();

					$empModelObj = New OrgEmployeeConstant;
	                $empModelObj->setConnection($this->orgDbConName);
	                $employeeConstant = $empModelObj->ofEmployee($empId)->first();
        
        			// $response['for_'+$empId] = 'Perform';
	                
	                if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 1)
			        {
			        	$actionPerformed++;
		                $status = 1;
		                $msg = 'Employee Share Rights updated!'; 
		                 
			        	//modify share rights accordingly
			        	if(!isset($isSracEnabled))
			        	{
			        		$isSracEnabled = 0;
						}
			        	if(!isset($isSracOrgEnabled))
			        	{
			        		$isSracOrgEnabled = 0;
						}
			        	if(!isset($isSracRetailShareEnabled))
			        	{
			        		$isSracRetailShareEnabled = 0;
						}
			        	if(!isset($isCopyToProfileEnabled))
			        	{
			        		$isCopyToProfileEnabled = 0;
						}
			        	if(!isset($isFacebookEnabled))
			        	{
				        	$isFacebookEnabled = 0;
				        }
			        	if(!isset($isTwitterEnabled))
			        	{
				        	$isTwitterEnabled = 0;
				        }
			        	if(!isset($isLinkedinEnabled))
			        	{
				        	$isLinkedinEnabled = 0;
				        }
			        	if(!isset($isWhatsappEnabled))
			        	{
				        	$isWhatsappEnabled = 0;
				        }
			        	if(!isset($isEmailEnabled))
			        	{
				        	$isEmailEnabled = 0;
				        }
			        	if(!isset($isSmsEnabled))
			        	{
				        	$isSmsEnabled = 0;
				        }
			        	if(!isset($isOtherEnabled))
			        	{
				        	$isOtherEnabled = 0;
				        }
				        $isSocEnabled = $isFacebookEnabled + $isTwitterEnabled + $isLinkedinEnabled + $isWhatsappEnabled + $isEmailEnabled + $isSmsEnabled + $isOtherEnabled;
				        if($isSocEnabled > 0)
				        	$isSocEnabled = 1;
				        	
				        if(!$hasRetailShareRights) {
							$isSracRetailShareEnabled = 0;
							// $isCopyToProfileEnabled = 0;
						}
				        				        
				        $employeeConstant->is_srac_share_enabled = $isSracEnabled;
				        $employeeConstant->is_srac_org_share_enabled = $isSracOrgEnabled;
				        $employeeConstant->is_srac_retail_share_enabled = $isSracRetailShareEnabled;
				        $employeeConstant->is_copy_to_profile_enabled = $isCopyToProfileEnabled;
				        $employeeConstant->is_soc_share_enabled = $isSocEnabled;
				        $employeeConstant->is_soc_facebook_enabled = $isFacebookEnabled;
				        $employeeConstant->is_soc_twitter_enabled = $isTwitterEnabled;
				        $employeeConstant->is_soc_linkedin_enabled = $isLinkedinEnabled;
				        $employeeConstant->is_soc_whatsapp_enabled = $isWhatsappEnabled;
				        $employeeConstant->is_soc_email_enabled = $isEmailEnabled;
				        $employeeConstant->is_soc_sms_enabled = $isSmsEnabled;
				        $employeeConstant->is_soc_other_enabled = $isOtherEnabled;
				        $employeeConstant->save();
				        
				        //SEND FCM
						$this->sendOrgEmployeeShareRightsToDevice($empId);
					}
					else
					{
						if($employee->is_verified == 0)
						{
							$unverifiedCount++;
						}
						else if($employee->is_active == 0)
						{
							$inactiveCount++;
						}
					}

				} 

				if($unverifiedCount + $inactiveCount > 0)
				{
					if($unverifiedCount > 0)
					{
						$msg = "some appuser account(s) were not verified yet";
					}


					if($inactiveCount > 0)
					{
						if($msg != "")
						{
							$msg .= " and ";
						}
						$msg = "some appuser account(s) were set to inactive";
					}

					$msg = "The operation wasn't performed entirely as " . $msg;
				}
				else
				{
					$msg = "The operation was performed successfully";
				}

				if($actionPerformed == 0)
				{
					$status = -1;
				}
				else
				{
	        		$status = 1;
				}
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function changeFileSaveShareEnabledStatus(Request $request)
    {        
    	$isFileSaveShareEnabled = $request->input('fileSaveShareEnabled');
		
        $isMulti = Input::get('isMulti');        
        if(!isset($isMulti)) {
			$isMulti = 0;
		}
		
		if($isMulti == 1) {
			$empIdArr = json_decode($request->input('empIdArr'));
		}
		else{
        	$id = $request->input('empId');
			$empIdArr = array();
			array_push($empIdArr, $id);
		}

        $status = 0;
        $msg = "";
        $response = array();

        if(isset($empIdArr) && count($empIdArr) > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
            	$orgFileSaveShareEnabled = OrganizationClass::orgHasFileSaveShareEnabled($this->organizationId);
            	
	        	$actionPerformed = 0;
	        	$unverifiedCount = 0;
	        	$inactiveCount = 0;

    			$empIdArr = sracDecryptNumberArrayData($empIdArr);

            	foreach($empIdArr as $empId) 
            	{
	        		$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        		$employee =  $depMgmtObj->getPlainEmployeeObject();

					$empModelObj = New OrgEmployeeConstant;
	                $empModelObj->setConnection($this->orgDbConName);
	                $employeeConstant = $empModelObj->ofEmployee($empId)->first();
        
        			// $response['for_'+$empId] = 'Perform';
	                
	                if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 1)
			        {
			        	$actionPerformed++;
		                $status = 1;
		                $msg = 'Employee Share Rights updated!'; 
		                 
			        	//modify share rights accordingly
			        	if(!isset($isFileSaveShareEnabled))
			        	{
			        		$isFileSaveShareEnabled = 0;
						}
				        	
				        if(!$orgFileSaveShareEnabled) {
							$isFileSaveShareEnabled = 0;
						}
				        				        
				        $employeeConstant->is_file_save_share_enabled = $isFileSaveShareEnabled;
				        $employeeConstant->save();
				        
				        //SEND FCM
						$this->sendOrgEmployeeDetailsToDevice($empId);
					}
					else
					{
						if($employee->is_verified == 0)
						{
							$unverifiedCount++;
						}
						else if($employee->is_active == 0)
						{
							$inactiveCount++;
						}
					}

				} 

				if($unverifiedCount + $inactiveCount > 0)
				{
					if($unverifiedCount > 0)
					{
						$msg = "some appuser account(s) were not verified yet";
					}


					if($inactiveCount > 0)
					{
						if($msg != "")
						{
							$msg .= " and ";
						}
						$msg = "some appuser account(s) were set to inactive";
					}

					$msg = "The operation wasn't performed entirely as " . $msg;
				}
				else
				{
					$msg = "The operation was performed successfully";
				}

				if($actionPerformed == 0)
				{
					$status = -1;
				}
				else
				{
	        		$status = 1;
				}
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function changeScreenShareEnabledStatus(Request $request)
    {        
    	$isScreenShareEnabled = $request->input('screenShareEnabled');
		
        $isMulti = Input::get('isMulti');        
        if(!isset($isMulti)) {
			$isMulti = 0;
		}
		
		if($isMulti == 1) {
			$empIdArr = json_decode($request->input('empIdArr'));
		}
		else{
        	$id = $request->input('empId');
			$empIdArr = array();
			array_push($empIdArr, $id);
		}

        $status = 0;
        $msg = "";
        $response = array();

        $response['empIdArr_Enc'] = $empIdArr;
        $response['isMulti'] = $isMulti;
        $response['isScreenShareEnabled'] = $isScreenShareEnabled;

        if(isset($empIdArr) && count($empIdArr) > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
            	$orgScreenShareEnabled = OrganizationClass::orgHasScreenShareEnabled($this->organizationId);
        		$response['orgScreenShareEnabled'] = $orgScreenShareEnabled;
            	
	        	$actionPerformed = 0;
	        	$unverifiedCount = 0;
	        	$inactiveCount = 0;

    			$empIdArr = sracDecryptNumberArrayData($empIdArr);

        		$response['empIdArr_Dec'] = $empIdArr;

            	foreach($empIdArr as $empId) 
            	{
	        		$depMgmtObj = New ContentDependencyManagementClass;
	        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $empId);
	        		$employee =  $depMgmtObj->getPlainEmployeeObject();

					$empModelObj = New OrgEmployeeConstant;
	                $empModelObj->setConnection($this->orgDbConName);
	                $employeeConstant = $empModelObj->ofEmployee($empId)->first();
        
        			// $response['for_'+$empId] = 'Perform';
	                
	                if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 1)
			        {
			        	$actionPerformed++;
		                $status = 1;
		                $msg = 'Employee Screen Share Rights updated!'; 
		                 
			        	//modify share rights accordingly
			        	if(!isset($isScreenShareEnabled))
			        	{
			        		$isScreenShareEnabled = 0;
						}
				        	
				        if(!$orgScreenShareEnabled) {
							$isScreenShareEnabled = 0;
						}
				        				        
				        $employeeConstant->is_screen_share_enabled = $isScreenShareEnabled;
				        $employeeConstant->save();

        				$response['empId_employeeConstant_'.$empId] = $employeeConstant;
				        
				        //SEND FCM
						$this->sendOrgEmployeeDetailsToDevice($empId);
					}
					else
					{
						if($employee->is_verified == 0)
						{
							$unverifiedCount++;
						}
						else if($employee->is_active == 0)
						{
							$inactiveCount++;
						}
					}

				} 

				if($unverifiedCount + $inactiveCount > 0)
				{
					if($unverifiedCount > 0)
					{
						$msg = "some appuser account(s) were not verified yet";
					}


					if($inactiveCount > 0)
					{
						if($msg != "")
						{
							$msg .= " and ";
						}
						$msg = "some appuser account(s) were set to inactive";
					}

					$msg = "The operation wasn't performed entirely as " . $msg;
				}
				else
				{
					$msg = "The operation was performed successfully";
				}

				if($actionPerformed == 0)
				{
					$status = -1;
				}
				else
				{
	        		$status = 1;
				}
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadRestoreContentModal()
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

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('empId');

    		$id = sracDecryptNumberData($id);

            $employee = NULL;
            if($id > 0)
            {	            
	            $depMgmtObj = New ContentDependencyManagementClass;
        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $id);
                $employee = $depMgmtObj->getEmployeeObject();

                if(isset($employee))
                {
                	$data = $depMgmtObj->getContentMetricsForRestore();
		            $data['employee'] = $employee;
		            $data['usrtoken'] = $this->userToken;
		           
		            $_viewToRender = View::make('orgemployee.partialview._restoreContentModal', $data);
		            $_viewToRender = $_viewToRender->render();
		            
		            $response['view'] = $_viewToRender; 
		            $response['data'] = $data; 
                }	                        
            }
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
    public function performContentRestore(Request $request)
    {        
        $id = $request->input('empId');

    	$id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";
        $response = array();

        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0){
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {
	            $depMgmtObj = New ContentDependencyManagementClass;
        		$depMgmtObj->withOrgIdAndEmpId($this->organizationId, $id);
                $employee = $depMgmtObj->getEmployeeObject();
                
                if(isset($employee))
		        {
                	$status = 1;
                	$msg = 'Employee Quota updated!';

                	$restoreDataMetrics = $depMgmtObj->getContentMetricsForRestore();

                	$isFolder = TRUE;
                	$trashedContents = $depMgmtObj->getAllContentsForRestore($isFolder);
                	if(isset($trashedContents) && count($trashedContents) > 0)
                	{
                		$restoreContentCount = count($trashedContents);
                		foreach ($trashedContents as $contKey => $trashedContent) 
                		{
                			$consContentId = $trashedContent->content_id;
                			$depMgmtObj->restoreDeletedContent($consContentId, $isFolder);
                		}

                		MailClass::sendOrgEmployeeContentRestoredMail($this->organizationId, $id, $restoreDataMetrics);
                	}
			        
				}
				else
				{
                	$status = -1;
				}      
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }
    
    /**
     * Validate employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateEmployeeNo()
    {        
        $id = Input::get('empId');
        $no = Input::get('empNo');

    	$id = sracDecryptNumberData($id);

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);

        $employee = $modelObj->where('employee_no','=',$no);

        if($id > 0)
        {   
            $employee = $employee->where('employee_id','!=',$id); 
        }   

        $employee = $employee->first();          
        
        if(isset($employee))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable));
    }
    
    /**
     * Validate employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateEmployeeEmail()
    {        
        $id = Input::get('empId');
        $email = Input::get('email');

    	$id = sracDecryptNumberData($id);

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);

        $employee = $modelObj->where('email','=',$email);

        if($id > 0)
        {   
            $employee = $employee->where('employee_id','!=',$id); 
        }   

        $employee = $employee->first();          
        
        if(isset($employee))
            $isAvailable = FALSE;
        else
            $isAvailable = TRUE;
        
        echo json_encode(array('valid' => $isAvailable));
    }
    
    /**
     * Validate employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function validateEmployeeQuota()
    {        
        $id = Input::get('empId');
        $quota = Input::get('quota');

    	$id = sracDecryptNumberData($id);

        $modelObj = New OrgEmployeeConstant;
        $modelObj->setConnection($this->orgDbConName);
        $employee = $modelObj->ofEmployee($id)->first();        
        
        if(isset($employee))
        {
        	$isAvailable = FALSE;
		}
        else
        {
        	$isAvailable = TRUE;
		}
        
        echo json_encode(array('valid' => $isAvailable));
    }

    /**
     * Validate employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('empId');

    	$id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);

        $employees = array();//OrgEm::where('department_id','=',$id)->first();
        
        if(count($employees)>0)
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function destroy()
    {
        $id = Input::get('empId');

    	$id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";

        if($this->modulePermissions->module_delete == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
            $status = 1;
            $msg = 'Employee deleted!';

            $modelObj = New OrgEmployee;
            $modelObj->setConnection($this->orgDbConName);
            
            //Send FCM
			$this->sendOrgEmployeeRemovedToDevice($id); 

            OrganizationClass::removeEmployeeDependencies($this->organizationId, $id, TRUE);
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }

    /**
     * Change the status of specified resource.
     *
     *
     * @return void
     */
    public function changeStatus(Request $request)
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

        if(!isset($this->modulePermissions) || $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
       	 	$isMulti = Input::get('isMulti');        
	        if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			if($isMulti == 1) {
				$empIdArr = json_decode($request->input('empIdArr'));
			}
			else{
	        	$id = $request->input('empId');
				$empIdArr = array();
				array_push($empIdArr, $id);
			}
	        $statusActive = Input::get('statusActive');

    		$empIdArr = sracDecryptNumberArrayData($empIdArr);

			if(isset($empIdArr) && count($empIdArr) > 0)
	        {
            	foreach($empIdArr as $empId) 
            	{
		            $status = 1;
			        $msg = 'Employee status changed!';     

		            $modelObj = New OrgEmployee;
		            $modelObj->setConnection($this->orgDbConName);

		            $employee = $modelObj->byId($empId)->first();

		            if(isset($employee) && $employee->is_active != $statusActive)
		            {
			            $employee->is_active = $statusActive;
			            $employee->updated_by = $this->userId;
			            $employee->save();
						
						// if($statusActive == 0)
						{
							$this->sendOrgEmployeeRemovedToDevice($empId);
						
							if($statusActive == 0)
							{
								MailClass::sendOrgEmployeeDeactivatedMail($this->organizationId, $empId);
							}
							else if($statusActive == 1)
							{
								MailClass::sendOrgEmployeeReactivatedMail($this->organizationId, $empId);

								if($employee->is_verified == 0)
								{
				        			$empName = $employee->employee_name;
				        			$empEmail = $employee->email;
				        			
				        			$orgUser = OrganizationUser::ofOrganization($this->organizationId)->byEmpId($empId)->first();
				        			if(isset($orgUser))
				        			{
				        				$encVerificationCode = $orgUser->verification_code;
										$verificationCode = Crypt::decrypt($encVerificationCode);
										MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
									}
								}
							}
						}
		            }
				}
			}
			else
			{
				$status = -1;
            	$msg = Config::get("app_config_notif.err_invalid_data");
			}
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }

    /**
     * Change the status of specified resource.
     *
     *
     * @return void
     */
    public function changeWebAccessStatus(Request $request)
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

        if(!isset($this->modulePermissions) || $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
       	 	$isMulti = Input::get('isMulti');        
	        if(!isset($isMulti)) {
				$isMulti = 0;
			}
			
			if($isMulti == 1) {
				$empIdArr = json_decode($request->input('empIdArr'));
			}
			else{
	        	$id = $request->input('empId');
				$empIdArr = array();
				array_push($empIdArr, $id);
			}
	        $statusActive = Input::get('statusActive');

			$empIdArr = sracDecryptNumberArrayData($empIdArr);

			if(isset($empIdArr) && count($empIdArr) > 0)
	        {
            	foreach($empIdArr as $empId) 
            	{
		            $status = 1;
			        $msg = 'Employee web access changed!';     

		            $modelObj = New OrgEmployee;
		            $modelObj->setConnection($this->orgDbConName);

		            $employee = $modelObj->byId($empId)->first();

		            if(isset($employee))
		            {
			            $employee->has_web_access = $statusActive;
			            $employee->updated_by = $this->userId;
			            $employee->save();
		            }
				}
			}
			else
			{
				$status = -1;
            	$msg = Config::get("app_config_notif.err_invalid_data");
			}
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }

    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadEditEmailModal()
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

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_add == 0 || $this->modulePermissions->module_edit == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $id = Input::get('empId');
            
    		$id = sracDecryptNumberData($id);            
        
	        $depMgmtObj = New ContentDependencyManagementClass;
	        $depMgmtObj->withOrgIdAndEmpId($this->organizationId, $id);
	        $employee = $depMgmtObj->getEmployeeObject();
            
            $data = array();
            $data['employeeId'] = sracEncryptNumberData($id);
            $data['empEmail'] = $employee->email;
            $data['empFullname'] = $employee->employee_name;
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgemployee.partialview._modifyEmailModal', $data);
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
    public function changeEmail(Request $request)
    {        
        $id = $request->input('empId');  
        $empEmail = $request->input('email');
        
    	$id = sracDecryptNumberData($id);

        $status = 0;
        $msg = "";
        $response = array();
        
        if($id > 0)
        {
            if($this->modulePermissions->module_edit == 0)
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_permission_denied');
            }
            else
            {        
		        $depMgmtObj = New ContentDependencyManagementClass;
		        $depMgmtObj->withOrgIdAndEmpId($this->organizationId, $id);

	            $modelObj = New OrgEmployee;
	            $modelObj->setConnection($this->orgDbConName);
	            $employee = $modelObj->byId($id)->first();
                
		        if(isset($employee))
		        {
					$empEmail = sanitizeEmailString($empEmail);

			        $employeeDetails = array();
			        $employeeDetails['email'] = $empEmail;
			        $employeeDetails['is_active'] = 1;
			        $employeeDetails['is_verified'] = 0;
			        $employeeDetails['is_self_registered'] = 0;
			        $employeeDetails['updated_by'] = $this->userId;
        
		        	$empName = $employee->employee_name;	
		        	$isVerified = $employee->is_verified;
		        	$oldEmail = $employee->email;
		        	if($empEmail != "" && $oldEmail != $empEmail)
		        	{
		        		$status = 1;
		                $msg = 'Employee email updated!';
						
						if($isVerified == 1)
			        	{
							$this->sendOrgEmployeeRemovedToDevice($id, TRUE);
							MailClass::sendOrgEmployeeLeftMail($this->organizationId, $id);							
						}
						
		                $employee->update($employeeDetails);
		                
						$verificationCode = CommonFunctionClass::generateVerificationCode();
						$encVerificationCode = Crypt::encrypt($verificationCode);
						$organizationUser = OrganizationUser::byEmpId($id)->ofOrganization($this->organizationId)->first();
						if(isset($organizationUser))
						{
							$organizationUser->emp_email = $empEmail;
							$organizationUser->appuser_email = "";
							$organizationUser->is_verified = 0;
							$organizationUser->verification_code = $encVerificationCode;
							$organizationUser->save();
						}
							
						MailClass::sendOrgEmployeeVerificationCodeMail($empName, $empEmail, $verificationCode, $this->organizationId);
					}                			
				}       
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Validate employee name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDetachment()
    {
        $id = Input::get('empId');

    	$id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);
        $employee = $modelObj->byId($id)->first();
        
        if(!isset($employee) || $employee->is_active == 0 || $employee->is_verified == 0)
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function detachEmployee()
    {
        $id = Input::get('empId');

    	$id = sracDecryptNumberData($id);
    	
        $status = 0;
        $msg = "";

        if($this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_permission_denied');
        }
        else
        {
	        $modelObj = New OrgEmployee;
	        $modelObj->setConnection($this->orgDbConName);
	        $employee = $modelObj->byId($id)->first();


	        if(isset($employee) && $employee->is_active == 1 && $employee->is_verified == 1)
	        {
		        $depMgmtObj = New ContentDependencyManagementClass;
		        $depMgmtObj->withOrgIdAndEmpId($this->organizationId, $id);
                $depMgmtObj->orgEmployeeLeave();

	            $status = 1;
	            $msg = 'Employee detached!';
	        }
	        else
	        {
	            $status = -1;
	            $msg = 'This Employee is already detached!';
	        }
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}
