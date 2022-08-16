<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\Org\Api\OrgBackup;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use Config;
use Response;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationUser;
use App\Models\Org\OrganizationServer;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgDepartment;
use App\Models\Org\Api\OrgDesignation;
use App\Models\Org\Api\OrgBadge;
use View;
use App\Libraries\MailClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\OrgCloudMessagingTrait;
use Crypt;
use File;
use App\Libraries\FileUploadClass;
use DB;
use Illuminate\Support\Facades\Log;

class OrgBackupManagementController extends Controller
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
    public $backupModel = NULL;
    public $backupTablename = NULL;

    public function __construct()
    {
        $this->module = Config::get('app_config_module.mod_org_backup');

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
                
                $this->backupModel = New OrgBackup;
                $this->backupModel->setConnection($this->orgDbConName);
                $this->backupTablename = $this->backupModel->table;

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
    public function loadBackupView()
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

            $js = array();
            $css = array();             
            
            $data = array();
            $data['js'] = $js;
            $data['css'] = $css;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;

            $_viewToRender = View::make('orgbackup.index', $data);
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
    public function backupDatatable()
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
	        $dateFormat = Config::get('app_config.sql_date_db_format');
	        $dateTimeFormat = Config::get('app_config.sql_datetime_db_format');

            $backups = $this->backupModel->select(['backup_id', $this->backupTablename.'.created_at', 'employee_name', 'backup_desc'])->joinEmployeeTable();

            return Datatables::of($backups)
                    ->remove_column('backup_id')
                    ->add_column('action', function($backup) {
                        return $this->getBackupDatatableButton($backup->backup_id);
                    })
                    ->make();
        }
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getBackupDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_edit == 1) {
            $buttonHtml .= '&nbsp;<button onclick="downloadBackupDetails(\''.$id.'\');" class="btn btn-xs btn-success"><i class="fa fa-download"></i>&nbsp;&nbsp;Download</button>';
        }
        if($this->modulePermissions->module_delete == 1) {
        	$buttonHtml .= '&nbsp;<button onclick="deleteBackupDetails(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;Delete</button>';
        }
        if($this->modulePermissions->module_edit == 1) {
            $buttonHtml .= '&nbsp;<button onclick="restoreUsingBackupDetails(\''.$id.'\');" class="btn btn-xs btn-warning"><i class="fa fa-window-restore"></i>&nbsp;&nbsp;Restore</button>';
        }
        return $buttonHtml;
    }

    /**
     * Load Add Appuser Content Modal.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function loadAddBackupModal()
    {    	
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $backupId = Input::get('backupId');
            
            $backupId = sracDecryptNumberData($backupId);
        
	        $backupDetails = NULL;
	        if($backupId != "" && $backupId > 0)
	        {
	            $backupDetails = $this->backupModel->byId($backupId)->first();
			}
	        
	    	$data = array();
            $data['id'] = sracEncryptNumberData($backupId);
	    	$data["backupDetails"] = $backupDetails;
	    	$data["usrtoken"] = $this->userToken;
	    	
	        $_viewToRender = View::make('orgbackup.partialview._addBackupModal', $data);
	        $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Validate group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    private function getAvailForBackup()
    {
        $isAvailable = 0;
        $msg = "";
        $isSent = 0;
        $response = array();

        $serverAvailableSpaceMBs = OrganizationClass::getTotalServerAvailableSpaceInMBs();

        $orgAssetDir = OrganizationClass::getOrgBaseAssetFolderDir($this->organizationId);
        $orgAssetDirSizeMBs = OrganizationClass::getTotalFolderSizeInMBs($orgAssetDir);

        if($serverAvailableSpaceMBs >= $orgAssetDirSizeMBs)
        {
            $isAvailable = 1;
        }
        else
        {
            $msg = 'Server space not available';
        }

        $response['serverAvailableSpaceMBs'] = $serverAvailableSpaceMBs;
        $response['orgAssetDirSizeMBs'] = $orgAssetDirSizeMBs;
        $response['status'] = $isAvailable;
        $response['msg'] = "$msg";

        return $response;
    }

    /**
     * Validate group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForBackup()
    {
        $response = $this->getAvailForBackup();
        $currOrgDbVersion = Config::get('app_config_sql.curr_org_db_version');
        $response['currOrgDbVersion'] = $currOrgDbVersion;
        return Response::json($response);
    }


    /**
     * Add Appuser Content.
     *
     * @param void
     *
     * @return void
     */
    public function addBackup()
    {
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $backupId = Input::get('backup_id');
	        $backupDesc = Input::get('backup_desc');
            $backupFilePath = '';

            $backupId = sracDecryptNumberData($backupId);

            $orgServer = OrganizationServer::ofOrganization($this->organizationId)->first();

            if(isset($orgServer))
            {
                $canBeBackupedResponse = $this->getAvailForBackup();
                $canPerformBackup = $canBeBackupedResponse['status'];

                if($canPerformBackup == 1)
                {
                    Log::info('Inside canPerformBackup '.$this->organizationId);
                    $orgBackupBasePath = OrganizationClass::createOrganizationDataBackup($this->organizationId);

                    if(isset($orgBackupBasePath) && $orgBackupBasePath != '')
                    {

                        $this->performDataBackup($orgBackupBasePath, $backupDesc);

                        $status = 1;
                        $msg = 'Backup generated!';

                        $availableSpace = disk_free_space('/'); // In Bytes

                        $response['msgLogs'] = $orgBackupBasePath; 
                        $response['availableSpace'] = $availableSpace; 
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Backup failed";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = $canBeBackupedResponse['msg'];
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

    private function performDataBackup($orgBackupBasePath, $backupDesc)
    {    
        $currOrgDbVersion = Config::get('app_config_sql.curr_org_db_version');

        $createdAt = CommonFunctionClass::getCurrentTimestamp();

        $backup = array();
        $backup['backup_desc'] = $backupDesc;
        $backup['backup_db_version'] = $currOrgDbVersion;
        $backup['backup_filepath'] = $orgBackupBasePath;
        $backup['created_by'] = $this->userId;
        $backup['created_at'] = $createdAt;
        
        $insBackupId = DB::connection($this->orgDbConName)->table($this->backupTablename)->insertGetId($backup);

        if(isset($insBackupId) && $insBackupId > 0)
        {
            $createdByName = $this->userDetails->fullname;
            MailClass::sendBackupGeneratedMail($this->organizationId, $backupDesc, $createdAt, $createdByName);
        }
    }

    /**
     * Validate group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('backupId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 0;
        $msg = "";
        $isSent = 0;
        
        $backupDetails = $this->backupModel->byId($id)->first();	
        
        if(isset($backupDetails))
        {
            $isAvailable = 1;
        }
        else
        {
            $msg = 'Invalid Backup';
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
        $id = Input::get('backupId');

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
            
        	$backup = $this->backupModel->byId($id)->first();

            if(isset($backup))
            {
                $orgBackupBasePath = $backup->backup_filepath;
                OrganizationClass::deleteOrganizationDataBackup($this->organizationId, $orgBackupBasePath);

                $deletedAt = CommonFunctionClass::getCurrentTimestamp();
                $deletedByName = $this->userDetails->fullname;

                $backupDesc = $backup->backup_desc;
                $createdAt = $backup->created_at;
                $createdByName = $backup->createdBy->fullname;

                $backup->is_deleted = 1;
                $backup->deleted_by = $this->userId;
                $backup->updated_by = $this->userId;
                $backup->save();
                
                $backup->delete();

                MailClass::sendBackupDeletedMail($this->organizationId, $backupDesc, $createdAt, $createdByName, $deletedAt, $deletedByName);

                $status = 1;
                $msg = 'Backup deleted!';
            }
            else
            {
                $status = -1;
                $msg = 'Invalid Backup!';
            }
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }

    /**
     * Load Add Appuser Content Modal.
     *
     * @param void
     *
     * @return JSONArray
     */
    public function loadConfirmRestoreModal()
    {       
        $status = 0;
        $msg = "";

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        }   

        if($this->modulePermissions->module_add == 0 && $this->modulePermissions->module_edit == 0)
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $backupId = Input::get('backupId');

            $backupId = sracDecryptNumberData($backupId);
        
            $backupDetails = NULL;
            if($backupId != "" && $backupId > 0)
            {
                $backupDetails = $this->backupModel->byId($backupId)->first();
            }
            
            $data = array();
            $data["backupDetails"] = $backupDetails;
            $data["usrtoken"] = $this->userToken;
            
            $_viewToRender = View::make('orgbackup.partialview._confirmRestoreModal', $data);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;          
        }

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    private function getAvailForRestore($id)
    {
        $isAvailable = 0;
        $msg = "";
        $isSent = 0;
        $response = array();
        
        $backupDetails = $this->backupModel->byId($id)->first();    
        
        if(isset($backupDetails))
        {
            $currOrgDbVersion = Config::get('app_config_sql.curr_org_db_version');
            $restoreOrgDbVersion = $backupDetails->backup_db_version;

            if($restoreOrgDbVersion == $currOrgDbVersion)
            {
                $orgBackupFolderName = $backupDetails->backup_filepath;
                $orgBackupBasePath = OrganizationClass::getOrgBaseBackupFolderDir($this->organizationId);
                $orgBackupBaseDir = $orgBackupBasePath . "/" . $orgBackupFolderName;

                $serverAvailableSpaceMBs = OrganizationClass::getTotalServerAvailableSpaceInMBs();
                $orgAllotedQuotaMBs = OrganizationClass::getOrganizationAllottedSpaceInMBs($this->organizationId);

                $orgAssetDir = OrganizationClass::getOrgBaseAssetFolderDir($this->organizationId);
                $orgCurrAssetDirSizeMBs = OrganizationClass::getTotalFolderSizeInMBs($orgAssetDir);
                $orgRestoreAssetDirSizeMBs = OrganizationClass::getTotalFolderSizeInMBs($orgBackupBaseDir);
                $orgRestoreAssetDirSizeMBs = $orgRestoreAssetDirSizeMBs * 2;

                if($orgAllotedQuotaMBs >= $orgRestoreAssetDirSizeMBs)
                {
                    if($serverAvailableSpaceMBs >= $orgRestoreAssetDirSizeMBs || (($serverAvailableSpaceMBs + $orgCurrAssetDirSizeMBs) >= $orgRestoreAssetDirSizeMBs))
                    {
                        $isAvailable = 1;
                    }
                    else
                    {
                        $msg = 'Server space not available';
                    }
                }
                else
                {
                    $msg = 'Organization space quota not sufficient';
                }

                $response['serverAvailableSpaceMBs'] = $serverAvailableSpaceMBs;
                $response['orgCurrAssetDirSizeMBs'] = $orgCurrAssetDirSizeMBs;
                $response['orgRestoreAssetDirSizeMBs'] = $orgRestoreAssetDirSizeMBs;
            }
            else
            {
                $msg = "Backup is no longer valid due to database upgrade.";
            }           
        }
        else
        {
            $isAvailable = 0;
            $msg = "No such backup";
        }

        $response['status'] = $isAvailable;
        $response['msg'] = "$msg";

        return $response;
    }

    /**
     * Validate group name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForRestore()
    {
        $id = Input::get('backupId');

        $id = sracDecryptNumberData($id);

        $response = $this->getAvailForRestore($id);

        return Response::json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function performRestore()
    {
        $id = Input::get('backupId');

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
            $orgServer = OrganizationServer::ofOrganization($this->organizationId)->first();

            if(isset($orgServer))
            {
                $canBeRestoredResponse = $this->getAvailForRestore($id);
                $canPerformRestore = $canBeRestoredResponse['status'];

                if($canPerformRestore == 1)
                {
                    $backup = $this->backupModel->byId($id)->first();

                    if(isset($backup))
                    {
                        $currDate = CommonFunctionClass::getCurrentTimestamp();

                        $orgPreRestoreBackupDesc = 'Auto Backup : Before restoring data on ' . $currDate;
                        $orgPreRestoreBackupBasePath = OrganizationClass::createOrganizationDataBackup($this->organizationId);
                        $this->performDataBackup($orgPreRestoreBackupBasePath, $orgPreRestoreBackupDesc);

                        $orgClassObj = New OrganizationClass;
                        $orgBackupBasePath = $backup->backup_filepath;
                        $orgBackupRestoreResult = $orgClassObj->restoreOrganizationDataBackup($this->organizationId, $orgBackupBasePath);

                        if(isset($orgBackupRestoreResult))
                        {
                            $status = $orgBackupRestoreResult->status;
                            $msg = $orgBackupRestoreResult->msg;

                            if($status > 0)
                            {
                                $restoredAt = CommonFunctionClass::getCurrentTimestamp();
                                $restoredByName = $this->userDetails->fullname;

                                $backupDesc = $backup->backup_desc;
                                $createdAt = $backup->created_at;
                                $createdByName = $backup->createdBy->fullname;

                                MailClass::sendBackupRestoredMail($this->organizationId, $backupDesc, $createdAt, $createdByName, $restoredAt, $restoredByName);
                            }
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = 'Invalid Backup!';
                    }
                }
                else
                {
                    $status = -1;
                    $msg = $canBeRestoredResponse['msg'];
                }                    
            }
            else
            {
                $status = -1;
                $msg = "Invalid data";
            }
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function performDownload()
    {
        $id = Input::get('backupId');

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
            $orgServer = OrganizationServer::ofOrganization($this->organizationId)->first();

            if(isset($orgServer))
            {
                $backup = $this->backupModel->byId($id)->first();

                if(isset($backup))
                {
                    $orgBackupFolderName = $backup->backup_filepath;
                    
                    $orgBackupBasePath = OrganizationClass::getOrgBaseBackupFolderDir($this->organizationId);
                    $orgBackupBaseDir = $orgBackupBasePath . "/" . $orgBackupFolderName;
                    $docName = $orgBackupFolderName . '.zip';
                    $docPath = $orgBackupBaseDir . "/" . $docName;

                    $file = new File($docPath);        
                    
                    if($file != null && File::exists($docPath))
                    { 
                        $downloadedAt = CommonFunctionClass::getCurrentTimestamp();
                        $downloadedByName = $this->userDetails->fullname;

                        $backupDesc = $backup->backup_desc;
                        $createdAt = $backup->created_at;
                        $createdByName = $backup->createdBy->fullname;

                        MailClass::sendBackupDownloadedMail($this->organizationId, $backupDesc, $createdAt, $createdByName, $downloadedAt, $downloadedByName);

                        $fileMimeType = File::mimeType($docPath);                   
                        $decryptedContents = File::get($docPath);

                        return $fileContent = response()->make($decryptedContents, 200, array(
                            'Content-Type' => $fileMimeType,
                            'Content-Disposition' => 'attachment; filename="' . $docName . '"'
                        ));
                    }    
                    else
                    {
                        echo "<h1>Document not found.</h1>";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = 'Invalid Backup!';
                }            
            }
            else
            {
                $status = -1;
                $msg = "Invalid data";
            }
        }
        if($status <= 0)
        {
            echo "<h1>" . $msg . "</h1>";
        }
    }
}