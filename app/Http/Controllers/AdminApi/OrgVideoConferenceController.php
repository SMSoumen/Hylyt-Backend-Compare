<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Org\OrganizationAdministration;
use App\Models\Org\Api\OrgVideoConference;
use App\Models\Org\Api\OrgVideoConferenceParticipant;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\CmsModule;
use App\Models\Org\CmsRoleRight;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Redirect;
use Config;
use Crypt;
use Response;
use View;
use App\Libraries\OrganizationClass;
use App\Libraries\CommonFunctionClass;
use App\Http\Controllers\CommonFunctionController;
use DB;
use App\Libraries\ContentDependencyManagementClass;

class OrgVideoConferenceController extends Controller
{
    public $userId = NULL;
    public $roleId = 0;
    public $organizationId = 0;
    public $userDetails = NULL;

    public $modulePermissions = NULL;
    public $module = "";

    public $userToken = NULL;
    public $orgDbConName = NULL;
	
	public function __construct()
    {
        $this->module = Config::get('app_config_module.mod_org_video_conference');

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
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function loadVideoConferenceView()
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

            $js = array("/dist/datatables/jquery.dataTables.min.js", "/dist/bootbox/bootbox.min.js", "/dist/bootstrap-datepicker/dist/js/common_dt.js");
            $css = array("/dist/datatables/jquery.dataTables.min.css", "/dist/bootstrap-datepicker/dist/css/common_dt_modal.css");

            $orgEmployeeList = array();
            $orgEmployeeList[""] = "";
                    
            $modelObj = New OrgEmployee;
            $modelObj = $modelObj->setConnection($this->orgDbConName);
            $userUsers = $modelObj->ofDistinctEmployee(); // active()->
            $userUsers = $userUsers->orderBy('employee_name', 'ASC');
            $userUserArr = $userUsers->get(); 
            foreach ($userUserArr as $userUser) 
            {
                $id = sracEncryptNumberData($userUser->employee_id);
                $name = $userUser->employee_name;
                $email = $userUser->email;
                $isVerified = $userUser->is_verified;
                
                $orgEmployeeList[$id] = $name.' ['.$email.']';
            } 

            $participantRoleList = array();
            $participantRoleList[''] = ''; 
            $participantRoleList['1'] = 'Moderator';
            $participantRoleList['0'] = 'Not Moderator';     

            $participantAttendanceList = array();
            $participantAttendanceList[''] = ''; 
            $participantAttendanceList['1'] = 'Has Attended';
            $participantAttendanceList['0'] = 'Not Attended';     
            
            $data = array();
            $data['intJs'] = $js;
            $data['intCss'] = $css;
            $data['orgEmployeeList'] = $orgEmployeeList;
            $data['participantRoleList'] = $participantRoleList;
            $data['participantAttendanceList'] = $participantAttendanceList;
            $data['userDetails'] = $this->userDetails;
            $data['modulePermissions'] = $this->modulePermissions;
            $data['usrtoken'] = $this->userToken;

            $_viewToRender = View::make('orgVideoConference.index', $data);
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
    public function orgVideoConferenceDatatable()
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
            $dateTimeFormat = Config::get('app_config.sql_date_db_format');

            $participant = Input::get('participant');
            $participantRole = Input::get('participantRole');
            $participantAttendance = Input::get('participantAttendance');
            $schFromDate = Input::get('schFromDate');
            $schToDate = Input::get('schToDate');

            $participant = sracDecryptNumberData($participant);

            $modelObj = New OrgVideoConference;
            $modelObj->setConnection($this->orgDbConName);
            $tableName = $modelObj->table;

            $videoConferences = $modelObj->select([ $tableName.'.org_vc_id as org_vc_id', 'meeting_title', \DB::raw('IF(is_open_conference = 1, "Open", "Private") as is_open_conference'), 'scheduled_start_ts', 'scheduled_end_ts', 'employee_name', \DB::raw($tableName.".created_at as created_at")])->joinOrgVideoConferenceCreatorTable();

            if(isset($participant) && $participant != "" && $participant > 0)
            {
                $videoConferences->joinOrgVideoConferenceParticipantTable($participant);
                // $videoConferences->where('participant_id','=',$participant);
                if(isset($participantRole) && $participantRole != "")
                {
                    $participantRole = $participantRole*1;
                    if($participantRole == 0 || $participantRole == 1)
                    {
                        $videoConferences->where('is_moderator','=',$participantRole);
                    }
                }

                if(isset($participantAttendance) && $participantAttendance != "")
                {
                    $participantAttendance = $participantAttendance*1;
                    if($participantAttendance == 0 || $participantAttendance == 1)
                    {
                        $videoConferences->where('has_attended','=',$participantAttendance);
                    }
                }
            } 

            if(isset($schFromDate) && $schFromDate != "")
                $videoConferences->where('scheduled_start_ts','>=',$schFromDate);
                
            if(isset($schToDate) && $schToDate != "")
                $videoConferences->where('scheduled_start_ts','<=',$schToDate);

            return Datatables::of($videoConferences)
                    ->remove_column('org_vc_id')
                    ->remove_column('meeting_title')
                    ->add_column('dec_meeting_title', function($videoConference) {
                        return $this->getOrgVideoConferenceDatatableMeetingTitle($videoConference->meeting_title);
                    }, 0)
                    ->add_column('action', function($videoConference) {
                        return $this->getOrgVideoConferenceDatatableButton($videoConference->org_vc_id);
                    })
                    ->make();
        }
    }

    private function getOrgVideoConferenceDatatableMeetingTitle($meetingTitle)
    {
        $decMeetingTitle = '';

        try
        {
            $decMeetingTitle = Crypt::decrypt($meetingTitle);
        }
        catch (\Exception $e) 
        {
        }

        return $decMeetingTitle;
    }
    
    /**
     * Display buttons for datatables row
     *
     * @return string
     */
    private function getOrgVideoConferenceDatatableButton($id)
    {
        $id = sracEncryptNumberData($id);
        $buttonHtml = "";
        if($this->modulePermissions->module_view == 1)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadViewVideoConferenceDetailsModal(\''.$id.'\');" class="btn btn-xs btn-success"><i class="fa fa-eye"></i>&nbsp;&nbsp;Info</button>';
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
    public function loadInfoModal()
    {
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');

        if(!isset($this->userId) || $this->userId <= 0)
        {
            $msg = Config::get("app_config_notif.err_invalid_user");
            $response = array('status' => -1, 'msg' => "$msg" );
            return Response::json($response); 
        } 

        $status = 0;
        $msg = "";

        $response = array();

        if(!isset($this->modulePermissions) || ($this->modulePermissions->module_view == 0))
        {
            $status = -1;
            $msg = Config::get("app_config_notif.err_permission_denied");
        }
        else
        {
            $status = 1;

            $conferenceId = Input::get('conferenceId');
            
            $conferenceId = sracDecryptNumberData($conferenceId);

            $depMgmtObj = New ContentDependencyManagementClass;
            $depMgmtObj->withOrgId($this->organizationId);

            $videoConferenceResponse = $depMgmtObj->getUserVideoConferenceInformation($conferenceId, TRUE); 

            $response = $videoConferenceResponse;
            $status = $videoConferenceResponse['status'];
            $msg = $videoConferenceResponse['msg'];
                
            $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

            $status = $videoConferenceResponse['status'];
            $msg = $videoConferenceResponse['msg'];

            $pageDesc = 'Video Conference Details';

            $viewDetails = $videoConferenceResponse;
            $viewDetails['conferenceId'] = sracEncryptNumberData($conferenceId);
            $viewDetails['page_description'] = $pageDesc;
            $viewDetails['tzOfs'] = $offsetInMinutes * 1;
            $viewDetails['tzStr'] = $tzStr;
       
            $_viewToRender = View::make('videoConference.partialview._videoConferenceInformationModal', $viewDetails);
            $_viewToRender = $_viewToRender->render();

            $response['view'] = $_viewToRender;
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

            $id = Input::get('desigId');

            $id = sracDecryptNumberData($id);
            
            $pageName = 'Add'; 

            $videoConference = NULL;
            if($id > 0)
            {
                $modelObj = New OrgVideoConference;
                $modelObj->setConnection($this->orgDbConName);

                $videoConference = $modelObj->byId($id)->first();
                $pageName = 'Edit';     
            }
            
            $data = array();
            $data['videoConference'] = $videoConference;
            $data['page_description'] = $pageName.' '.'VideoConference';
            $data['usrtoken'] = $this->userToken;
           
            $_viewToRender = View::make('orgVideoConference.partialview._addEditModal', $data);
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
        $id = $request->input('desigId'); 
        $desigName = $request->input('videoConference_name');
        $desigName = CommonFunctionController::convertStringToCap($desigName); 

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
                $status = 1;
                $msg = 'VideoConference updated!'; 

                $modelObj = New OrgVideoConference;
                $modelObj->setConnection($this->orgDbConName);

                $videoConference = $modelObj->byId($id)->first();
                $videoConference->update($request->all());
                $videoConference->updated_by = $this->userId;
                $videoConference->videoConference_name = $desigName;
                $videoConference->save();     
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
                $msg = 'VideoConference added!';
                
                $modelObj = New OrgVideoConference;
                $tableName = $modelObj->table;                
                                
                $videoConference = array();
                $videoConference['videoConference_name'] = $desigName;
                $videoConference['created_by'] = $this->userId;
                $videoConference['created_at'] = CommonFunctionClass::getCurrentTimestamp();
                
                DB::connection($this->orgDbConName)->table($tableName)->insert($videoConference);
            }
        }
        
        $response['status'] = $status;
        $response['msg'] = $msg;

        return Response::json($response);
    }

    /**
     * Validate videoConference name for duplication
     *
     * @param  int  $id
     *
     * @return void
     */
    public function checkAvailForDelete()
    {
        $id = Input::get('desigId');

        $id = sracDecryptNumberData($id);

        $isAvailable = 1;
        $msg = "";

        $modelObj = New OrgEmployee;
        $modelObj->setConnection($this->orgDbConName);

        $employees = $modelObj->where('org_vc_id','=',$id)->first();
        
        if(isset($employees))
        {
            $isAvailable = 0;
            $msg = Config::get('app_config_notif.org_videoConference_unavailable');
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
        $id = Input::get('desigId');

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
            $msg = 'VideoConference deleted!';

            $modelObj = New OrgVideoConference;
            $modelObj->setConnection($this->orgDbConName);

            $videoConference = $modelObj->byId($id)->first();
            $videoConference->is_deleted = 1;
            $videoConference->deleted_by = $this->userId;
            $videoConference->updated_by = $this->userId;
            $videoConference->save();

            $videoConference->delete();
        }
        
        $response = array('status'=>$status, 'msg'=>$msg);

        return Response::json($response);
    }
}
