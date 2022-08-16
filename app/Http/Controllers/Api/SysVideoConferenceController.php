<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\FolderType;
use App\Models\Api\SysVideoConference;
use App\Models\Api\SysVideoConferenceParticipant;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserContact;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserSource;
use App\Models\Api\AppuserTag;
use App\Models\Api\AppuserContentTag;
use App\Models\Api\AppuserContentImage;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Api\Group;
use App\Models\Api\GroupMember;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentTag;
use App\Models\Api\GroupContentAttachment;
use App\Models\Org\Api\OrgEmployee;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\Api\OrgEmployeeContentTag;
use App\Models\Org\Api\OrgEmployeeContentAttachment;
use App\Models\Org\Api\OrgGroup;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Org\Api\OrgGroupContent;
use App\Models\Org\Api\OrgGroupContentTag;
use App\Models\Org\Api\OrgGroupContentAttachment;
use App\Models\Org\OrganizationUser;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use DB;
use View;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\FolderFilterUtilClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class SysVideoConferenceController extends Controller
{  
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;
     
    public function __construct()
    {
        
    }
    
    /**
     * Add Content.
     *
     * @return json array
     */
    public function saveConferenceDetails()
    {
        $msg = "";
        $status = 0;

        $loginToken = Input::get('loginToken');
        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = Input::get('conferenceId');
        $meetingTitle = Input::get('meetingTitle');
        $startTimeStamp = Input::get('startTimeStamp');
        $endTimeStamp = Input::get('endTimeStamp');
        $isOpenConference = Input::get('isOpenConference');
        $contactIdArr = Input::get('contactIdArr');
        $contactIsModeratorArr = Input::get('contactIsModeratorArr');
        $orgEmpIdArr = Input::get('orgEmpIdArr');
        $orgEmpIsModeratorArr = Input::get('orgEmpIsModeratorArr');
        $isScheduled = Input::get('isScheduled');
        $selGroupId = Input::get('selGroupId');
        $selGroupMemberIdArr = Input::get('selGroupMemberIdArr');

        if(!isset($isScheduled) || $isScheduled != 1)
        {
            $isScheduled = 0;
        }

        if(!isset($selGroupId) || $selGroupId == "")
        {
            $selGroupId = "";
            $selGroupMemberIdArr = array();
        }

        if(!isset($selGroupMemberIdArr))
        {
            $selGroupMemberIdArr = array();
        }
        elseif(!is_array($selGroupMemberIdArr))
        {
            $selGroupMemberIdArr = json_decode($selGroupMemberIdArr);
        }

        if(!isset($contactIdArr))
        {
            $contactIdArr = array();
        }
        elseif(!is_array($contactIdArr))
        {
            $contactIdArr = json_decode($contactIdArr);
        }

        if(!isset($contactIsModeratorArr))
        {
            $contactIsModeratorArr = array();
        }
        elseif(!is_array($contactIsModeratorArr))
        {
            $contactIsModeratorArr = json_decode($contactIsModeratorArr);
        }

        if(!isset($orgEmpIdArr))
        {
            $orgEmpIdArr = array();
        }
        elseif(!is_array($orgEmpIdArr))
        {
            $orgEmpIdArr = json_decode($orgEmpIdArr);
        }

        if(!isset($orgEmpIsModeratorArr))
        {
            $orgEmpIsModeratorArr = array();
        }
        elseif(!is_array($orgEmpIsModeratorArr))
        {
            $orgEmpIsModeratorArr = json_decode($orgEmpIsModeratorArr);
        }
            
        $meetingTitle = urldecode($meetingTitle);
        
        $response = array();

        if($encUserId != "" && $meetingTitle != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $id = sracDecryptNumberData($id, $userSession);
                $selGroupId = sracDecryptNumberData($selGroupId, $userSession);
                $selGroupMemberIdArr = sracDecryptNumberArrayData($selGroupMemberIdArr, $userSession);
                $contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
                $orgEmpIdArr = sracDecryptNumberArrayData($orgEmpIdArr, $userSession);
                 
                $status = 1;
                $msg = "";
                $isFolder = TRUE; 
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $vidConference = $depMgmtObj->getVideoConferenceObject($id, TRUE);

                $participantIdArr = array();
                $participantIsModeratorArr = array();
                $partIndex = 0;

                $hasGroupSelection = FALSE;
                if($selGroupId > 0)
                {
                    $selGroup = $depMgmtObj->getGroupObject($selGroupId);
                    $selGroupMembers = $depMgmtObj->getGroupMembers($selGroupId);

                    if(isset($selGroup) && isset($selGroupMembers))
                    {
                        $grpHasUserAsMember = FALSE;
                        foreach ($selGroupMembers as $groupMember) 
                        {
                            $consUserIsModerator = 0;
                            if($orgId > 0)
                            {
                                $grpMemberUserId = $groupMember->employee_id;
                                if($grpMemberUserId == $orgEmpId && in_array($grpMemberUserId, $selGroupMemberIdArr))
                                {
                                    $grpHasUserAsMember = TRUE;
                                    $consUserIsModerator = 1;
                                }
                            }
                            else
                            {
                                $grpMemberUserId = $groupMember->member_appuser_id;
                                if($grpMemberUserId == $userId && in_array($grpMemberUserId, $selGroupMemberIdArr))
                                {
                                    $grpHasUserAsMember = TRUE;
                                    $consUserIsModerator = 1;
                                }
                            }

                            if(in_array($grpMemberUserId, $selGroupMemberIdArr))
                            {
                                $participantIdArr[$partIndex] = $grpMemberUserId;
                                $participantIsModeratorArr[$partIndex] = $consUserIsModerator;
                                $partIndex++;
                            }
                        }

                        if($grpHasUserAsMember)
                        {
                            $hasGroupSelection = TRUE;
                        }
                        else
                        {
                            $participantIdArr = array();
                            $participantIsModeratorArr = array();
                            $partIndex = 0;
                        }
                    }
                }

                if(!$hasGroupSelection)
                {
                    if($orgId > 0 && count($orgEmpIdArr) > 0)
                    {
                        $participantIdArr[$partIndex] = $orgEmpId;
                        $participantIsModeratorArr[$partIndex] = 1;
                        $partIndex++;

                        for($i=0; $i<count($orgEmpIdArr); $i++)
                        {
                            $consOrgEmpId = $orgEmpIdArr[$i];
                            $consOrgEmpIsModerator = isset($orgEmpIsModeratorArr[$i]) ? $orgEmpIsModeratorArr[$i] : 0;
                            
                            if($consOrgEmpId > 0 && !in_array($consOrgEmpId, $participantIdArr))
                            {
                                $participantIdArr[$partIndex] = $consOrgEmpId;
                                $participantIsModeratorArr[$partIndex] = $consOrgEmpIsModerator;
                                $partIndex++;
                            }
                        }
                    }
                    elseif($orgId == 0 && count($contactIdArr) > 0)
                    {
                        $participantIdArr[$partIndex] = $userId;
                        $participantIsModeratorArr[$partIndex] = 1;
                        $partIndex++;

                        for($i=0; $i<count($contactIdArr); $i++)
                        {
                            $contactId = $contactIdArr[$i];
                            $contactIsModerator = isset($contactIsModeratorArr[$i]) ? $contactIsModeratorArr[$i] : 0;
                            
                            if($contactId > 0)
                            {
                                $consUser = AppuserContact::ofUserContact($contactId)->first();
                            
                                if(isset($consUser) && $consUser->is_srac_regd == 1 && $consUser->regd_appuser_id > 0)
                                {
                                    $contUserEmail = $consUser->email;
                                    $contUserContactNo = $consUser->contact_no;
                                    $contUserId = $consUser->regd_appuser_id;
                                    $contAppuserDetails = $consUser->registeredAppuser;

                                    if(isset($contAppuserDetails) && !in_array($contUserId, $participantIdArr))
                                    {
                                        $participantIdArr[$partIndex] = $contUserId;
                                        $participantIsModeratorArr[$partIndex] = $contactIsModerator;
                                        $partIndex++;  
                                    }
                                }
                            }
                        }
                    }
                }

                if(isset($participantIdArr) && count($participantIdArr) == 0 && $isOpenConference == 1)
                {
                    if($orgId > 0)
                    {
                        $participantIdArr[$partIndex] = $orgEmpId;
                        $participantIsModeratorArr[$partIndex] = 1;
                        $partIndex++;
                    }
                    elseif($orgId == 0)
                    {
                        $participantIdArr[$partIndex] = $userId;
                        $participantIsModeratorArr[$partIndex] = 1;
                        $partIndex++;
                    }
                }
                
                if(isset($participantIdArr) && count($participantIdArr) > 0)
                {                  
                    $confResponse = $depMgmtObj->addEditVideoConference($id, $meetingTitle, $startTimeStamp, $endTimeStamp, $isOpenConference, $participantIdArr, $participantIsModeratorArr, $isScheduled);
                    $syncId = $confResponse["syncId"];
                    if($syncId > 0)
                    {
                        $status = 1;
                        $response['syncId'] = sracEncryptNumberData($syncId, $userSession);
                        $response['syncConferenceCode'] = $confResponse['syncConferenceCode'];                      
                    }   
                    else
                    {
                        $status = -1; 
                        $msg = 'Something went wrong. The meeting could not be saved';
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_data');
                }            
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function checkConferenceCanBeStarted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $byAuthentication = Input::get('byAuthentication');
        $conferenceCode = Input::get('conferenceCode');
        $conferencePassword = Input::get('conferencePassword');
        $conferenceId = Input::get('conferenceId');

        if(!isset($byAuthentication))
        {
            $byAuthentication = 1;
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                if($byAuthentication == 1)
                {
                    $performAuthentication = true;
                    $videoConferenceIdFetchResponse = $depMgmtObj->fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword); 
                    if(isset($videoConferenceIdFetchResponse))
                    {
                        $conferenceId = $videoConferenceIdFetchResponse['conferenceId'];

                        $depMgmtObj = $videoConferenceIdFetchResponse['confDepMgmtObj'];
                    }
                }

                $response = $depMgmtObj->canUserJoinVideoConference($conferenceId);  
                $status = $response['status'];
                $msg = $response['msg'];

                if($status > 0 || ($status < 0 && isset($response['allowWaitingRoomJoin']) && $response['allowWaitingRoomJoin'] == 1))
                {
                    $status = 1;

                    $response['conferenceId'] = sracEncryptNumberData($response['conferenceId'], $userSession);
                    unset($response['userConference']['sys_vc_id']);
                    unset($response['userConference']['creator_appuser_id']);
                }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function markConferenceJoinedByUser()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $byAuthentication = Input::get('byAuthentication');
        $conferenceCode = Input::get('conferenceCode');
        $conferencePassword = Input::get('conferencePassword');
        $conferenceId = Input::get('conferenceId');
        $sendWithButtons = Input::get('sendWithButtons');

        if(!isset($byAuthentication))
        {
            $byAuthentication = 1;
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);   

                if($byAuthentication == 1)
                {
                    $performAuthentication = true;
                    $videoConferenceId = $depMgmtObj->getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication, $conferencePassword); 
                    if(isset($videoConferenceId))
                    {
                        $conferenceId = $videoConferenceId;
                    }
                }
                else
                {
                    if(isset($conferenceCode) && $conferenceCode != "")
                    {
                        $performAuthentication = false;
                        $conferencePassword = "";
                        $videoConferenceIdFetchResponse = $depMgmtObj->fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword); 
                        if(isset($videoConferenceIdFetchResponse))
                        {
                            $conferenceId = $videoConferenceIdFetchResponse['conferenceId'];

                            $depMgmtObj = $videoConferenceIdFetchResponse['confDepMgmtObj'];
                        }
                    }
                }

                $tmpResponse = $depMgmtObj->canUserJoinVideoConference($conferenceId);  
                $status = $tmpResponse['status'];
                $msg = $tmpResponse['msg'];

                if($status > 0)
                {
                    $depMgmtObj->onVideoConferenceJoinedByUser($conferenceId, $sendWithButtons);  
                }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function markConferenceExitedByUser()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $byAuthentication = Input::get('byAuthentication');
        $conferenceCode = Input::get('conferenceCode');
        $conferencePassword = Input::get('conferencePassword');
        $conferenceId = Input::get('conferenceId');

        if(!isset($byAuthentication))
        {
            $byAuthentication = 1;
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);   

                if($byAuthentication == 1)
                {
                    $performAuthentication = true;
                    $videoConferenceId = $depMgmtObj->getVideoConferenceIdByConferenceCode($conferenceCode, $performAuthentication, $conferencePassword); 
                    if(isset($videoConferenceId))
                    {
                        $conferenceId = $videoConferenceId;
                    }
                }
                else
                {
                    if(isset($conferenceCode) && $conferenceCode != "")
                    {
                        $performAuthentication = false;
                        $conferencePassword = "";
                        $videoConferenceIdFetchResponse = $depMgmtObj->fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword); 
                        if(isset($videoConferenceIdFetchResponse))
                        {
                            $conferenceId = $videoConferenceIdFetchResponse['conferenceId'];

                            $depMgmtObj = $videoConferenceIdFetchResponse['confDepMgmtObj'];
                        }
                    }
                }

                $status = 1;

                $stopConference = FALSE;
                $videoConferenceObj = $depMgmtObj->getVideoConferenceObject($conferenceId, TRUE);
                if(isset($videoConferenceObj))
                {
                    // $stopConference = TRUE;
                }

                $depMgmtObj->onVideoConferenceExitedByUser($conferenceId, $stopConference); 
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function getUpcomingVideoConferences()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $renderView = Input::get('renderView');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');

        $response = array();

        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {  
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }  
                        
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $upcomingVideoConferences = $this->formulateVideoConferenceList($depMgmtObj, 'UPC');

                if(isset($renderView) && $renderView == 1)
                {
                    $viewDetails = array();
                    $viewDetails['videoConferences'] = $upcomingVideoConferences;
                    $viewDetails['tzStr'] = $tzStr;

                    $_viewToRender = View::make('videoConference.partialview._videoConferenceListView', $viewDetails);
                    $_viewToRender = $_viewToRender->render();

                    $response['view'] = $_viewToRender;
                }
                else
                {
                    $response['videoConferences'] = $upcomingVideoConferences;
                }
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function getPastVideoConferences()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $renderView = Input::get('renderView');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');

        $response = array();

        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {  
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }  
                        
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $upcomingVideoConferences = $this->formulateVideoConferenceList($depMgmtObj, 'PST');

                if(isset($renderView) && $renderView == 1)
                {
                    $viewDetails = array();
                    $viewDetails['videoConferences'] = $upcomingVideoConferences;
                    $viewDetails['tzStr'] = $tzStr;

                    $_viewToRender = View::make('videoConference.partialview._videoConferenceListView', $viewDetails);
                    $_viewToRender = $_viewToRender->render();

                    $response['view'] = $_viewToRender;
                }
                else
                {
                    $response['videoConferences'] = $upcomingVideoConferences;
                }
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function getAttendedVideoConferences()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $renderView = Input::get('renderView');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');

        $response = array();

        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {  
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }  
                        
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $upcomingVideoConferences = $this->formulateVideoConferenceList($depMgmtObj, 'ATT');

                if(isset($renderView) && $renderView == 1)
                {
                    $viewDetails = array();
                    $viewDetails['videoConferences'] = $upcomingVideoConferences;
                    $viewDetails['tzStr'] = $tzStr;

                    $_viewToRender = View::make('videoConference.partialview._videoConferenceListView', $viewDetails);
                    $_viewToRender = $_viewToRender->render();

                    $response['view'] = $_viewToRender;
                }
                else
                {
                    $response['videoConferences'] = $upcomingVideoConferences;
                }
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    public function formulateVideoConferenceList($depMgmtObj, $listCode)
    {
        $user = $depMgmtObj->getUserObject();
        $loginToken = $depMgmtObj->getCurrentLoginToken();

        $allOrgDepMgmtObjArr = array();
        // if($listCode == 'UPC')
        {
            $tmpPerDepMgmtObj = New ContentDependencyManagementClass;
            $tmpPerDepMgmtObj->withUserIdOrgIdAndEmpId($user, 0, 0);
            $tmpPerDepMgmtObj->setCurrentLoginToken($loginToken);
            array_push($allOrgDepMgmtObjArr, $tmpPerDepMgmtObj);

            $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();
            foreach ($userOrganizations as $userOrgIndex => $userOrg) 
            {
                $orgId = $userOrg->organization_id;                 
                $organization = $userOrg->organization;

                if(isset($organization)) 
                {
                    $empId = $userOrg->emp_id;

                    $tmpOrgDepMgmtObj = New ContentDependencyManagementClass;
                    $tmpOrgDepMgmtObj->withOrgIdAndEmpId($orgId, $empId);
                    $tmpOrgDepMgmtObj->setCurrentLoginToken($loginToken);

                    $orgEmployee = $tmpOrgDepMgmtObj->getPlainEmployeeObject();
                    if(isset($orgEmployee) && $orgEmployee->is_active == 1)
                    {
                        array_push($allOrgDepMgmtObjArr, $tmpOrgDepMgmtObj);
                    }
                }
            }
        }
        // else
        // {
        //     array_push($allOrgDepMgmtObjArr, $depMgmtObj);
        // }      

        $compiledList = array();
        foreach ($allOrgDepMgmtObjArr as $depMgmtIndex => $orgDepMgmtObj)
        {
            $videoConferences = $orgDepMgmtObj->getVideoConferenceListByListCode($listCode);
            $orgId = $orgDepMgmtObj->getOrganizationId();
    		$userOrEmpId = $orgDepMgmtObj->getEmployeeOrUserId(); 

            $userSession = $orgDepMgmtObj->getAppuserSession();

            $orgKey = "";
            $orgMapKey = "";
            $orgName = "";
            $orgEmpName = "";
            $orgIconUrl = "";
            if($orgId > 0)
            {
                $organization = $orgDepMgmtObj->getOrganizationObject();
                if(isset($organization))
                {
                    $orgKey = $orgDepMgmtObj->getOrgProfileKey();
                    $orgEmpName = $orgDepMgmtObj->getEmployeeOrUserName();
                    $orgEmpObject = $orgDepMgmtObj->getPlainEmployeeObject();

                    if(isset($orgEmpObject))
                    {
                        $orgMapKey = $orgEmpObject->org_emp_key;
                    }

                    $orgName = $organization->system_name;
                    $logoFilename = $organization->logo_filename;
                    if(isset($logoFilename) && $logoFilename != "")
                    {
                        $orgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                    }
                }
            }

            $orgRelatedDetails = array();
            $orgRelatedDetails['orgKey'] = $orgKey;
            $orgRelatedDetails['orgMapKey'] = $orgMapKey;
            $orgRelatedDetails['orgName'] = $orgName;
            $orgRelatedDetails['orgEmpName'] = $orgEmpName;
            $orgRelatedDetails['orgIconUrl'] = $orgIconUrl;
        	
        	if(isset($videoConferences))
        	{
                $currentTimestamp = CommonFunctionClass::getCreateTimestamp();

                $utcTz =  'UTC';
                $conMinStartDateTimeObj = Carbon::now($utcTz)->addHour();
                $conMinStartDateTimeObj->second = 0;
                $conMinStartTimeStamp = $conMinStartDateTimeObj->timestamp;       
                $conMinStartTimeStamp = $conMinStartTimeStamp * 1000; 

    	    	foreach ($videoConferences as $videoConference) {
    	    		$confObject = $this->formulateVideoConferenceObject($userSession, $orgRelatedDetails, $orgId, $userOrEmpId, $listCode, $videoConference, $currentTimestamp, $conMinStartTimeStamp);
    	    		array_push($compiledList, $confObject);
    	    	}    		
        	}
        }

        if($listCode == 'UPC')
        {
            array_multisort( array_column($compiledList, "scheduledStartTs"), SORT_ASC, $compiledList );
        }
        else
        {
            array_multisort( array_column($compiledList, "scheduledStartTs"), SORT_DESC, $compiledList );
        }

    	return $compiledList;
    }

    public function formulateVideoConferenceObject($userSession, $orgRelatedDetails, $orgId, $userOrEmpId, $listCode, $videoConference, $currentTimestamp, $conMinStartTimeStamp)
    {
    	$compiledVideoConference = array();

        $isUserConferenceCreator = $videoConference->creator_id == $userOrEmpId ? 1 : 0;
        $isUserConferenceRunning = $videoConference->is_running;

        $canJoinConference = $isUserConferenceRunning;
        $hasAttendedConference = $videoConference->has_attended;

        $canJoinWaitingRoom = 0;

        $showAttendedIcon = $listCode != 'ATT' ? 1 : 0;

        $canStartConference = 0;
        $canCancelConference = 0;
        if($isUserConferenceRunning == 0)
        {
            // if($isUserConferenceCreator == 1 && ($currentTimestamp >= $videoConference->scheduled_start_ts || $conMinStartTimeStamp >= $videoConference->scheduled_start_ts) && $currentTimestamp <= $videoConference->scheduled_end_ts)
            // {
            //     $canStartConference = 1;
            //     $canCancelConference = 1;
            // }

            if(($currentTimestamp >= $videoConference->scheduled_start_ts || $conMinStartTimeStamp >= $videoConference->scheduled_start_ts) && $currentTimestamp <= $videoConference->scheduled_end_ts)
            {
                if($isUserConferenceCreator == 1)
                {
                    $canStartConference = 1;
                    $canCancelConference = 1;                    
                }
                else
                {
                    $canJoinWaitingRoom = 1;
                }
            }

            if($isUserConferenceCreator == 1 && $currentTimestamp < $videoConference->scheduled_start_ts)
            {
                $canCancelConference = 1;
            }
            else if($isUserConferenceCreator == 1 && (!isset($videoConference->actual_start_ts) || $videoConference->actual_start_ts == 0))
            {
                $canCancelConference = 1;
            }
        }

        if($canJoinConference == 1)
        {
            if($listCode == 'PST')
            {
                $canJoinConference = 0;
            }
            else if($listCode == 'ATT')
            {
                if($currentTimestamp > $videoConference->scheduled_end_ts)
                {
                    $canJoinConference = 0;
                    $canCancelConference = 0;
                }
            }
        }

        $isUpcoming = $listCode == 'UPC' ? 1 : 0;

        if($canCancelConference == 1)
        {
            if($listCode == 'PST')
            {
                $canCancelConference = 0;
            }
        }

        $canViewConferenceInfo = 1;

    	$compiledVideoConference['conferenceId'] = sracEncryptNumberData($videoConference->cons_vc_id, $userSession);
    	$compiledVideoConference['meetingTitle'] = Crypt::decrypt($videoConference->meeting_title);
    	$compiledVideoConference['conferenceCode'] = $videoConference->conference_code;
    	$compiledVideoConference['isOpenConference'] = $videoConference->is_open_conference;
    	$compiledVideoConference['scheduledStartTs'] = $videoConference->scheduled_start_ts;
    	$compiledVideoConference['scheduledEndTs'] = $videoConference->scheduled_end_ts;
    	$compiledVideoConference['isRunning'] = $isUserConferenceRunning;
    	$compiledVideoConference['isModerator'] = $videoConference->is_moderator;
    	$compiledVideoConference['scheduledContentId'] = $videoConference->scheduled_content_id;
    	$compiledVideoConference['isCreator'] = $isUserConferenceCreator;
        $compiledVideoConference['creatorName'] = $videoConference->creator_name;
        $compiledVideoConference['creatorEmail'] = $videoConference->creator_email;
        $compiledVideoConference['canJoinConference'] = $canJoinConference;
        $compiledVideoConference['canJoinWaitingRoom'] = $canJoinWaitingRoom;
        $compiledVideoConference['canStartConference'] = $canStartConference;
        $compiledVideoConference['canViewConferenceInfo'] = $canViewConferenceInfo;
        $compiledVideoConference['canCancelConference'] = $canCancelConference;
        $compiledVideoConference['hasAttendedConference'] = $hasAttendedConference;
        $compiledVideoConference['showAttendedIcon'] = $showAttendedIcon;
        $compiledVideoConference['isUpcoming'] = $isUpcoming;
        $compiledVideoConference['orgKey'] = $orgRelatedDetails['orgKey'];
        $compiledVideoConference['orgMapKey'] = $orgRelatedDetails['orgMapKey'];
        $compiledVideoConference['orgName'] = $orgRelatedDetails['orgName'];
        $compiledVideoConference['orgEmpName'] = $orgRelatedDetails['orgEmpName'];
        $compiledVideoConference['orgIconUrl'] = $orgRelatedDetails['orgIconUrl'];

    	return $compiledVideoConference;
    }

    public function checkConferenceCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');

        $response = array();

        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {  
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $orgId = $depMgmtObj->getOrganizationId();
                $orgEmpId = $depMgmtObj->getOrganizationEmployeeId();

                $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, TRUE);

                if(isset($videoConference))
                {
                    if(!isset($videoConference->actual_start_ts) || $videoConference->actual_start_ts == 0)
                    {
                        if(($orgId == 0 && $videoConference->creator_appuser_id == $userId) || ($orgId > 0 && $videoConference->creator_employee_id == $orgEmpId))
                        {
                            $status = 1;
                        }
                        else
                        {
                            $status = -1;
                            $msg = "You do not have the permission to delete this conference";
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = "Cannot cancel an already started conference";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = "You do not have access to this conference";
                }
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function deleteConference()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');

        $response = array();

        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {  
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }  

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                        
                $status = 1;
                $msg = "Conference cancelled";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $depMgmtObj->deleteVideoConference($conferenceId);
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function conferenceListView()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $offset = Input::get('ofs');
        $searchStr = Input::get('searchStr');
        $sortBy = Input::get('sortBy');
        $sortOrder = Input::get('sortOrder');
        $hasFilters = Input::get('hasFilters');
        $loginToken = Input::get('loginToken');
        $isAttachmentView = Input::get('isAttachmentView');
        $isFavoritesTab = Input::get('isFavoritesTab');
        $tzOffset = Input::get('tzOfs');
        $tzStr = Input::get('tzStr');
        $listCode = Input::get('listCode');

        if(!isset($listCode))
        {
            $listCode = "";
        }

        if(!isset($isAttachmentView) && $isAttachmentView != 1)
        {
            $isAttachmentView = 0;
        }

        $overrideOffset = FALSE;// ; TRUE
        if($isAttachmentView == 1)
        {
            $offset = 0;
            $overrideOffset = TRUE;
        }

        $response = array(); 

        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $filAttachmentExtArr = array();
                
                $filFolderArr = null;
                $filGroupArr = null;
                $filSourceArr = null;
                $filTypeArr = null;
                $filAttachmentTypeArr = null;
                $filTagArr = null;
                $filFromDate = null;
                $filToDate = null;
                $isStarred = null; 
                $chkIsUntagged = null;
                $chkIsLocked = null;
                $chkIsConversation = null;
                $chkShowFolder = null;
                $chkShowGroup = null;
                $chkIsRestricted = null;
                $chkIsUnread = null;
                $filShowAttachment = null;
                $filSenderEmail = null;

                $contentListHasFilters = 0;
                
                if(isset($hasFilters) && $hasFilters == 1)
                {
                    $contentListHasFilters = 1;

                    $filFolderArr = Input::get('filFolderArr');
                    $filGroupArr = Input::get('filGroupArr');
                    $filSourceArr = Input::get('filSourceArr');
                    $filTypeArr = Input::get('filTypeArr');
                    $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
                    $filTagArr = Input::get('filTagArr');
                    $filFromDate = Input::get('fromTimeStamp');
                    $filToDate = Input::get('toTimeStamp');  
                    $isStarred = Input::get('chkIsStarred');  
                    $chkIsUntagged = Input::get('chkIsUntagged');
                    $chkIsLocked = Input::get('chkIsLocked');
                    $chkIsConversation = Input::get('chkIsConversation');
                    $chkShowFolder = Input::get('chkShowFolder');
                    $chkShowGroup = Input::get('chkShowGroup');
                    $chkIsUnread = Input::get('chkIsUnread');
                    $chkIsRestricted = Input::get('chkIsRestricted');
                    $filShowAttachment = Input::get('filShowAttachment');
                    $filSenderEmail = Input::get('filSenderEmail');

                    $filFolderArr = sracDecryptNumberArrayData($filFolderArr, $userSession);
                    $filGroupArr = sracDecryptNumberArrayData($filGroupArr, $userSession);
                    $filSourceArr = sracDecryptNumberArrayData($filSourceArr, $userSession);
                    $filTagArr = sracDecryptNumberArrayData($filTagArr, $userSession);


                    /*$filFolderArr = json_decode($filFolderArr);
                    $filSourceArr = json_decode($filSourceArr);
                    $filTypeArr = json_decode($filTypeArr);
                    $filTagArr = json_decode($filTagArr);*/
                    
                    if(isset($filAttachmentTypeArr) && count($filAttachmentTypeArr) > 0) {
                        $attachmentExtensionArr = Config::get('app_config.filter_attachment_type_extension_array');
                        $extensionStr = '';
                        foreach($filAttachmentTypeArr as $attachmentTypeId) {
                            if(isset($attachmentExtensionArr[$attachmentTypeId])) {
                                if($extensionStr != '')
                                    $extensionStr .= ' ';
                                    
                                $extensionStr .= $attachmentExtensionArr[$attachmentTypeId];
                            }
                        }
                        
                        if($extensionStr != '') {
                            $filAttachmentExtArr = explode(' ', $extensionStr);
                            /*print_r('$filAttachmentExtArr : ');
                            print_r($filAttachmentExtArr);*/
                        }           
                    }     
                }
                
                $filArr = array();
                $filArr['filFolderArr'] = $filFolderArr;
                $filArr['filGroupArr'] = $filGroupArr;
                $filArr['filSourceArr'] = $filSourceArr;
                $filArr['filTagArr'] = $filTagArr;   
                $filArr['filTypeArr'] = $filTypeArr;   
                $filArr['filAttachmentExtArr'] = $filAttachmentExtArr;   
                $filArr['filFromDate'] = $filFromDate;   
                $filArr['filToDate'] = $filToDate;   
                $filArr['chkIsStarred'] = $isStarred;   
                $filArr['chkIsUntagged'] = $chkIsUntagged;
                $filArr['chkIsLocked'] = $chkIsLocked;
                $filArr['chkIsConversation'] = $chkIsConversation;
                $filArr['chkShowFolder'] = $chkShowFolder;
                $filArr['chkShowGroup'] = $chkShowGroup;
                $filArr['chkIsTrashed'] = 0;
                $filArr['chkIsUnread'] = $chkIsUnread;
                $filArr['chkIsRestricted'] = $chkIsRestricted;
                $filArr['filShowAttachment'] = $filShowAttachment;
                $filArr['filSenderEmail'] = $filSenderEmail;


                $contentListCodeHasFolderGroupArr = Config::get('app_config.contentListCodeHasFolderGroupArr');
                $listHasFolderGroup = isset($contentListCodeHasFolderGroupArr[$listCode]) ? $contentListCodeHasFolderGroupArr[$listCode] : 0; 

                $listCodeFavNotes = Config::get('app_config.dashMetricFavNotesCode');
                $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');
                $listCodeAllNotes = Config::get('app_config.dashMetricAllNotesCode');
                $listCodeReminderNotes = Config::get('app_config.dashMetricReminderNotesCode');
                $listCodeCalendarNotes = Config::get('app_config.dashMetricCalendarNotesCode');
                $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
                $listCodeTrashNotes = Config::get('app_config.dashMetricTrashNotesCode');
                
                $typeR = Config::get("app_config.content_type_r");
                $typeA = Config::get("app_config.content_type_a");
                $typeC = Config::get("app_config.content_type_c");
                            
                $i = 0;
                $arrForSorting = array();
                $listSize = 20;
                $totalContentCount = 0;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $empOrUserId = $userId;
                if($orgId > 0)
                    $empOrUserId = $orgEmpId;
                
                
                $isAllNotes = FALSE;
                $isLocked = FALSE;
                
                $isFolder = FALSE;
                $folderOrGroupId = $groupId;
                $selFolderDetails = NULL;
                $selGroupDetails = NULL;
                if($isFolderFlag == 1)
                {
                    $isFolder = TRUE;
                    $folderOrGroupId = $folderId;
                    
                    if(isset($isLockedFlag) && $isLockedFlag == 1) 
                    {
                        $isLocked = TRUE;
                    }
                    
                    if($folderOrGroupId < 0) {
                        $isAllNotes = TRUE;
                        if($folderOrGroupId == -2)
                        {
                            $listCode = $listCodeTrashNotes;
                        }
                    }
                    else
                    {
                        $selFolderDetails = $depMgmtObj->getFolderObject($folderOrGroupId);
                    }
                }
                else
                {
                    $selGroupDetails = $depMgmtObj->getGroupObject($folderOrGroupId);
                }

                if($isAllNotes)
                {
                    if($listCode == $listCodeFavNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filArr['isStarred'] = 1;
                    }
                    else if($listCode == $listCodeReminderNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filTypeReminderArr = [ $typeR ];
                        $filArr['filTypeArr'] = $filTypeReminderArr;
                    }
                    else if($listCode == $listCodeCalendarNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filTypeCalendarArr = [ $typeC ];
                        $filArr['filTypeArr'] = $filTypeCalendarArr;
                    }
                    else if($listCode == $listCodeConversationNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 1;
                        $filArr['chkIsConversation'] = 1;
                    }
                    else if($listCode == $listCodeTrashNotes)
                    {
                        $hasFilters = 1;
                        $chkShowFolder = 1;
                        $chkShowGroup = 0;
                        $filArr['chkIsTrashed'] = 1;
                    }

                    $filArr['chkShowFolder'] = $chkShowFolder;
                    $filArr['chkShowGroup'] = $chkShowGroup;
                }

                $virtualFolderTypeId = FolderType::$TYPE_VIRTUAL_FOLDER_ID;
                $virtualFolderId = NULL;
                $virtualFolderFilterStr = NULL;
                    
                $groupPer = array();
                $folderTypeId = 0;
                $profileCnt = 0;

                $isVirtualFolder = FALSE;
                $isFavorited = 0;
                if($isFolder)
                {
                    $profileCnt = OrganizationUser::ofUserEmail($user->email)->verified()->count();
                    
                    $folderObj = $depMgmtObj->getFolderObject($folderOrGroupId);
                    if(isset($folderObj))
                    {
                        $isFavorited = $folderObj->is_favorited;
                        $folderTypeId = $folderObj->folder_type_id;

                        if($folderTypeId == $virtualFolderTypeId)
                        {
                            $isVirtualFolder = TRUE;
                            $virtualFolderId = $folderOrGroupId;

                            $isAllNotes = TRUE;
                            $folderOrGroupId = -1;

                            $virtualFolderFilterStr = $folderObj->applied_filters;

                            $folderFilterUtilObj = New FolderFilterUtilClass;
                            $folderFilterUtilObj->setFilterStr($virtualFolderFilterStr);

                            // Log::info('applied_filters : ');
                            // Log::info($folderObj->applied_filters);

                            if($hasFilters == 0)
                            {
                                $hasFilters = 1;

                                $filArr['chkIsConversation'] = $folderFilterUtilObj->getFilterValueIsConversation();
                                $filArr['chkIsUntagged'] = $folderFilterUtilObj->getFilterValueIsUntagged();
                                $filArr['chkIsLocked'] = $folderFilterUtilObj->getFilterValueIsLocked();
                                $filArr['chkIsStarred'] = $folderFilterUtilObj->getFilterValueIsStarred();
                                $filArr['chkIsLocked'] = $folderFilterUtilObj->getFilterValueIsRestricted();
                                $filArr['chkDownloadStatus'] = $folderFilterUtilObj->getFilterValueDownloadStatus();
                                $filArr['chkIsRestricted'] = $folderFilterUtilObj->getFilterValueIsRestricted();
                                $filArr['filFromDate'] = $folderFilterUtilObj->getFilterValueStartDateTs();
                                $filArr['filToDate'] = $folderFilterUtilObj->getFilterValueEndDateTs();
                                $filArr['filTypeArr'] = $folderFilterUtilObj->getFilterValueContentType();
                                $filArr['filFolderArr'] = $folderFilterUtilObj->getFilterValueFolder();
                                $filArr['filGroupArr'] = $folderFilterUtilObj->getFilterValueGroup();
                                $filArr['filSourceArr'] = $folderFilterUtilObj->getFilterValueSource();
                                $filArr['filTagArr'] = $folderFilterUtilObj->getFilterValueTag();
                                $filArr['filAttachmentTypeArr'] = $folderFilterUtilObj->getFilterValueAttachmentType();
                                $filArr['filShowAttachment'] = $folderFilterUtilObj->getFilterValueAttachmentStatus();
                                $filArr['filSenderEmail'] = $folderFilterUtilObj->getFilterValueSenderEmail();

                                $chkShowFolder = $folderFilterUtilObj->getFilterValueIsShowFolder();
                                $chkShowGroup = $folderFilterUtilObj->getFilterValueIsShowGroup();
                                
                                $filArr['chkShowFolder'] = $chkShowFolder;
                                $filArr['chkShowGroup'] = $chkShowGroup;
                            }
                        }
                    }
                }
                else
                {
                    $group = $depMgmtObj->getGroupObject($groupId);
                    
                    if(isset($group))
                    {
                        $groupMember = $depMgmtObj->getGroupMemberObject($groupId);
                        $isTwoWay = $group->is_two_way;
                        $allocSpace = $group->allocated_space_kb;
                        $usedSpace = $group->used_space_kb;
                        
                        $hasPostRight = 1;
                        $isAdmin = 0;
                        if(isset($groupMember))
                        {
                            $isAdmin = $groupMember->is_admin;
                            $isFavorited = $groupMember->is_favorited;
                            if($orgId > 0)
                                $hasPostRight = $groupMember->has_post_right;
                        }
                        $groupPer['isTwoWay'] = $isTwoWay;  
                        $groupPer['hasPostRight'] = $hasPostRight;  
                        $groupPer['isAdmin'] = $isAdmin;
                    }
                }

                // Log::info('Inside ContentList : isAllNotes : '.$isAllNotes.' : isFolder : '.$isFolder.' : folderOrGroupId : '.$folderOrGroupId.' : filArr : ');
                // Log::info($filArr);

                $showListHeader = FALSE;
                if($offset == 0)
                {
                    $showListHeader = TRUE;
                }
                
                $showFolderHeader = FALSE;
                $showGroupHeader = FALSE;

                // Log::info('isAllNotes : '.$isAllNotes.' : hasFilters : '.$hasFilters.' : chkShowFolder : '.$chkShowFolder.' : chkShowGroup : '.$chkShowGroup);
        
                
                $contentList = array();
                if((!$isAllNotes) || ($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowFolder) && $chkShowFolder == 1)))))
                {

                    $contentList = $this->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, $isFolder, $folderOrGroupId, $isAllNotes, $isLocked, $hasFilters, $filArr, $searchStr, $sortBy, $sortOrder);

                    if($offset == 0)
                    {
                        $showFolderHeader = TRUE;
                    }

                    if($listCode == $listCodeTrashNotes)
                    {
                        $showFolderHeader = FALSE;
                    }
                    //Log::info('Fetching Folder ContentList : $contentList : '.count($contentList));
                }
                
                /*print_r('<pre>after sortby<br/>');
                print_r($contentList);
                print_r('</pre>');*/
                
                $totalPrimaryContentCount = count($contentList);
                $totalSecondaryContentCount = 0;
                $totalContentCount = count($contentList);
        
                if(!$overrideOffset)
                {
                    $contentList = array_slice($contentList, $offset, $listSize, FALSE);
                }
                $resCnt = count($contentList);
                
                $secContentList = array();
                if($isAllNotes && (($hasFilters == 0) || ($hasFilters == 1 && (isset($chkShowGroup) && $chkShowGroup == 1)))) 
                {
                    if($offset == 0 || $resCnt < $listSize)
                    {
                        $usrGroupArr = $depMgmtObj->getAllGroupsFoUser();
                        $usrGroupIdArr = array();
                        foreach($usrGroupArr as $usrGroup) {
                            array_push($usrGroupIdArr, $usrGroup->group_id);
                        }
                        $filArr['filAllNotesGroupArr'] = $usrGroupIdArr;

                        $tempSecContentList = $this->formulateUserContentList($depMgmtObj, $orgId, $empOrUserId, FALSE, $folderOrGroupId, $isAllNotes, $isLocked, 1, $filArr, $searchStr, $sortBy, $sortOrder); 
                        if(count($tempSecContentList) > 0)
                        {
                            $totalContentCount = $totalContentCount + count($tempSecContentList);
                            $totalSecondaryContentCount = count($tempSecContentList);
                        }

                        if($resCnt < $listSize)
                        {
                            $secOffset = $offset - $totalPrimaryContentCount;
                            if($secOffset < 0)
                            {
                                $secOffset = 0;
                            }

                            $secListSize = $listSize - $resCnt;

                            $secContentList = $tempSecContentList; 
                            if(count($secContentList) > 0)
                            {
                                if(!$overrideOffset)
                                {
                                    $secContentList = array_slice($secContentList, $secOffset, $secListSize, FALSE);
                                }
                            }
                            if($secOffset == 0)
                            {
                                $showGroupHeader = TRUE;
                            }
                        }
                    }

                    //Log::info('Fetching Group ContentList : $secContentList : '.count($secContentList));
                }
                $secResCnt = count($secContentList);
                
                $profileShareRight = 0;
                if($orgId > 0)
                {
                    $orgConst = $depMgmtObj->getEmployeeOrUserConstantObject();
                    if(isset($orgConst))
                    {
                        $profileShareRight = $orgConst->is_srac_share_enabled;
                    }
                }
                else
                {
                    $profileShareRight = 1;
                }
                
                $isAllNotesFlag = $isAllNotes == TRUE ? 1 : 0;

                $status = 1;

                $dispAttachmentNameLength = 25;
                $primAttachmentArr = array();
                $secAttachmentArr = array();
                $attachmentView = "";
                if($isAttachmentView == 1)
                {
                    foreach ($contentList as $content) 
                    {
                        $contentId = $content['id'];
                        $contentIsFolder = $content['isFolder'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $contentIsFolder);
                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename);  
                            $attObj->url = $attachmentUrl;
                            $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);  
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;

                            $attachmentName = $attObj->filename;
                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($primAttachmentArr, $attObj);
                        }
                    }

                    foreach ($secContentList as $content) 
                    {
                        $contentId = $content['id'];
                        $contentIsFolder = $content['isFolder'];
                        $contentAttachments = $depMgmtObj->getContentAttachments($contentId, $contentIsFolder);
                        foreach($contentAttachments as $attObj)
                        {
                            $attServerFilename = $attObj->server_filename;
                            $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $attServerFilename);  
                            $attObj->url = $attachmentUrl;
                            $attachmentThumbUrl = OrganizationClass::getOrgContentAssetThumbUrl($orgId, $attServerFilename);  
                            $attObj->thumbUrl = $attachmentThumbUrl;
                            $attObj->contentId = $contentId;
                            $attObj->isFolder = $contentIsFolder;

                            $attachmentName = $attObj->filename;
                            $attachmentNameLength = strlen($attachmentName);

                            if($attachmentNameLength > $dispAttachmentNameLength)
                            {
                                $attachmentName = substr($attachmentName, 0, $dispAttachmentNameLength);
                                $attachmentName .= "..";
                            }
                            else
                            {
                                $attachmentName = substr($attachmentName, 0, $attachmentNameLength);                     
                            }

                            $attObj->stripped_filename = $attachmentName;

                            array_push($secAttachmentArr, $attObj);
                        }
                    }
                    
                    $viewDetails = array();
                    $viewDetails['isFavoritesTab'] = $isFavoritesTab;
                    $viewDetails['primAttachmentArr'] = $primAttachmentArr;
                    $viewDetails['secAttachmentArr'] = $secAttachmentArr;
                    $viewDetails['isAllNotes'] = $isAllNotesFlag;
                    $viewDetails['showFolderHeader'] = $showFolderHeader;
                    $viewDetails['showGroupHeader'] = $showGroupHeader;
               
                    $_viewToRender = View::make('content.partialview._attachmentView', $viewDetails);
                    $attachmentView = $_viewToRender->render();
                }
                
                $resCnt = count($contentList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_content_found');
                
                $showLoadMore = 1;
                if($resCnt + $secResCnt < $listSize)
                {
                    $showLoadMore = 0;
                }

                $updOffset = $resCnt + $secResCnt + $offset;
                
                /*if(isset($searchStr) && $searchStr != "") {
                    foreach($contentList as $contentObj) {
                        
                    }
                }*/

                $isVirtualFolderFlag = $isVirtualFolder == true ? 1 : 0;
                
                $userGroups = $depMgmtObj->getAllGroupsFoUser();
                $response['userGroups'] = $userGroups;

                $response['totalContentCount'] = $totalContentCount;
                $response['contentCnt'] = $resCnt;
                $response['contentArr'] = $contentList;
                $response['secContentCnt'] = $secResCnt;
                $response['secContentArr'] = $secContentList;
                $response['profileShareRight'] = $profileShareRight;
                $response['folderTypeId'] = $folderTypeId;
                $response['profileCnt'] = $profileCnt;
                $response['groupPer'] = $groupPer;
                $response['showLoadMore'] = $showLoadMore;
                $response['isFavorited'] = $isFavorited;
                $response['virtualFolderId'] = $virtualFolderId;
                $response['isVirtualFolder'] = $isVirtualFolder;
                $response['isVirtualFolderFlag'] = $isVirtualFolderFlag;
                $response['isAllNotes'] = $isAllNotesFlag;
                $response['showListHeader'] = $showListHeader;
                $response['showFolderHeader'] = $showFolderHeader;
                $response['showGroupHeader'] = $showGroupHeader;
                $response['filArr'] = $filArr;
                $response['isAttachmentView'] = $isAttachmentView;
                $response['attachmentView'] = $attachmentView;
                $response['primAttachmentArr'] = $primAttachmentArr;
                $response['selIsFolder'] = $isFolderFlag;
                $response['selFolderOrGroupId'] = $folderOrGroupId;
                $response['selFolderDetails'] = $selFolderDetails;
                $response['selGroupDetails'] = $selGroupDetails;
                $response['tzOffset'] = $tzOffset;
                $response['tzStr'] = $tzStr;
                $response['listCode'] = $listCode;
                $response['searchStr'] = $searchStr;
                $response['contentListHasFilters'] = $contentListHasFilters;


                // $orgKey = $depMgmtObj->getOrgEmpKey();
                $response['orgKey'] = $encOrgId;

                $viewDetails = $response;

                $_viewToRender = View::make('content.partialview._contentListView', $viewDetails);
                $contentListView = $_viewToRender->render();

                $response = array();
                $response['contentListView'] = utf8_encode($contentListView);
                $response['showLoadMore'] = $showLoadMore;
                $response['totalContentCount'] = $totalContentCount;
                $response['updOffset'] = $updOffset;
                $response['totalPrimaryContentCount'] = $totalPrimaryContentCount;
                $response['totalSecondaryContentCount'] = $totalSecondaryContentCount;
                // $response['contentList'] = $contentList;
                // $response['secContentList'] = $secContentList;

                if($isVirtualFolder && $offset == 0)
                {
                    $response['hasFilters'] = $hasFilters;
                    $response['filtersFrmData'] = $virtualFolderFilterStr;
                }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";
    
        return Response::json($response);
    }
    
    public function conferenceDetailsModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = Input::get('conferenceId');
        $isViewFlag = Input::get('isView');
        $isScheduled = Input::get('isScheduled');
        $reqIsFolder = Input::get('reqIsFolder');
        $reqFOrGId = Input::get('reqFOrGId');
        
        if(!isset($isScheduled))
        {
            $isScheduled = 0;
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $id = sracDecryptNumberData($id, $userSession);

                $isEdit = 0;
                $editConferenceId = "";
                
                $status = 1;
                
                $isView = FALSE;
                if($isViewFlag == 1)
                    $isView = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken); 

                $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();             
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
                
                $saveParticipantFieldName = 'participant_id';
                $participantCtrlName = 'contactIdArr';
                $empOrUserId = $userId;
                if($orgId > 0)
                {
                    $empOrUserId = $orgEmpId;
                    $saveParticipantFieldName = 'participant_id';
                    $participantCtrlName = 'orgEmpIdArr';
                }

                $pageDesc = "Start";
                $startTimeAddMinutes = 1;
                $endTimeAddMinutes = 10;
                if($isScheduled == 1)
                {
                    $pageDesc = "Schedule";
                    $startTimeAddMinutes = 5;
                    $endTimeAddMinutes = 15;
                }

                $showProfileSelection = 0;

                $consGroupId = 0;
                $consVirtualFolderId = 0;
                $consVirtualFolderSenderEmail = '';
                if(isset($reqFOrGId) && $reqFOrGId > 0)
                {
                	if(isset($reqIsFolder) && ($reqIsFolder == 0 || $reqIsFolder == 1))
                	{
                        if($reqIsFolder == 0)
                		{
                            $selGroupDetails = $depMgmtObj->getGroupObject($reqFOrGId);
                    		if(isset($selGroupDetails))
                    		{
                    			$consGroupId = $reqFOrGId;
                    		}
                        }
                        else
                        {
                            $virtualSenderFolderTypeId = FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID;

                            $selFolderDetails = $depMgmtObj->getFolderObject($reqFOrGId);
                            if(isset($selFolderDetails) && $selFolderDetails->folder_type_id == $virtualSenderFolderTypeId)
                            {
                                $consVirtualFolderId = $reqFOrGId;
                                $consVirtualFolderSenderEmail = $selFolderDetails->virtual_folder_sender_email;
                            }
                        }
                	}
                }

                $endTimeAddMinutes = $startTimeAddMinutes + $endTimeAddMinutes;

                $utcTz =  'UTC';
                $startDateObj = Carbon::now($utcTz)->addMinutes($startTimeAddMinutes);
                $startDateObj->second = 0;
                $startTimeStamp = $startDateObj->timestamp;       
                $startUtcTs = $startTimeStamp * 1000;  

                $endDateObj = Carbon::now($utcTz)->addMinutes($endTimeAddMinutes);
                $endDateObj->second = 0;
                $endTimeStamp = $endDateObj->timestamp;       
                $endUtcTs = $endTimeStamp * 1000;

                $meetingTitle = "";
                $isOpenConference = 0;
                $selectedParticipantIdArr = array();

                $videoConferenceId = 0;               
                $videoConferenceParticipants = NULL;     
                
                $videoConference = $depMgmtObj->getVideoConferenceObject($id, FALSE);
                if(isset($videoConference))
                {
                    $videoConferenceId = $id;
                    $pageDesc = "Edit";

                    $isEdit = 1;   
                    $isScheduled = 1;

                    $editConferenceId = sracEncryptNumberData($id, $userSession);

                    $meetingTitle = Crypt::decrypt($videoConference->meeting_title);
                    $isOpenConference = $videoConference->is_open_conference; 

                    $startUtcTs = $videoConference->scheduled_start_ts; 
                    $endUtcTs = $videoConference->scheduled_end_ts; 
                                    
                    $videoConferenceParticipants = $depMgmtObj->getVideoConferenceParticipants($videoConferenceId);

                    foreach ($videoConferenceParticipants as $videoConferenceParticipant) {
                        array_push($selectedParticipantIdArr, $videoConferenceParticipant->{$saveParticipantFieldName});
                    }
                }
                
                $isPremiumUser = $user->is_premium;
                
                $applicableParticipantArr = array();
                $selectedEncParticipantIdArr = array();
                $tempParticipantIdArr = array();
               
                if($isView) {
                    $pageDesc = "View";
                }
                else
                {
                	if($consGroupId > 0)
                	{
                		$groupMembers = $depMgmtObj->getGroupMembers($consGroupId);
                		if(isset($groupMembers) && count($groupMembers) > 0)
                		{
                			foreach ($groupMembers as $memberUser) 
                       		{
	                            if($orgId > 0)
	                            {
	                            	$participantEmpOrUserId = $memberUser->employee_id;                            	
	                            }
	                            else
	                            {
	                            	$participantEmpOrUserId = $memberUser->member_appuser_id;
	                            }    
	                            $name = $memberUser->name;
	                            $email = $memberUser->email;	                        
	                            
                                $encParticipantId = sracEncryptNumberData($participantEmpOrUserId, $userSession);

	                            $userDetails = array();
	                            $userDetails['id'] = $encParticipantId;
	                            $userDetails['name'] = $name;
	                            $userDetails['email'] = $email;
	                            
	                            array_push($applicableParticipantArr, $userDetails);

                                if(in_array($participantEmpOrUserId, $selectedParticipantIdArr))
                                {
                                    array_push($selectedEncParticipantIdArr, $encParticipantId);
                                }
                       		}
                		}
                	}
                	else if($orgId > 0)
                    {
                        $modelObj = New OrgEmployee;
                        $modelObj = $modelObj->setConnection($orgDbConName);
                        
                        $userUsers = $modelObj->verified()->exceptEmployee($orgEmpId)->ofDistinctEmployee();
                        $userUsers = $userUsers->orderBy('employee_name', 'ASC');
                        if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                        {
                            $userUsers = $userUsers->ofEmail($consVirtualFolderSenderEmail);
                        }
                        $userUserArr = $userUsers->get(); 

                        foreach ($userUserArr as $userUser) 
                        {
                            $participantEmpId = $userUser->employee_id;
                            $name = $userUser->employee_name;
                            $email = $userUser->email;
                            $isVerified = $userUser->is_verified;
                            
                            $encParticipantId = sracEncryptNumberData($participantEmpId, $userSession);
                            
                            $userDetails = array();
                            $userDetails['id'] = $encParticipantId;
                            $userDetails['name'] = $name;
                            $userDetails['email'] = $email;
                            
                            array_push($applicableParticipantArr, $userDetails);

                            array_push($tempParticipantIdArr, $participantEmpId);

                            if(in_array($participantEmpId, $selectedParticipantIdArr))
                            {
                                array_push($selectedEncParticipantIdArr, $encParticipantId);
                            }
                        }

                        $showProfileSelection = 1;
                    }
                    else
                    {
                        $userRegEmail = $user->email;
                        
                        $userCont = AppuserContact::ofUser($userId)->where('email','<>',$userRegEmail);
                        $isBlockedValArr = [ 0 ];
                        $userCont = $userCont->whereIn('is_blocked', $isBlockedValArr);
                        $userCont = $userCont->orderBy('name', 'asc');
                        $userCont = $userCont->where('is_srac_regd', '=', '1');
                        if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                        {
                            $userCont = $userCont->ofEmail($consVirtualFolderSenderEmail);
                        }
                        $userContArr = $userCont->get();

                        foreach ($userContArr as $usrCont) 
                        {
                            $contId = $usrCont->appuser_contact_id;
                            $name = $usrCont->name;
                            $email = $usrCont->email;

                            $isValidUser = FALSE;
                            if(trim($email) == "")
                            {
                                $appuserDetails = $usrCont->registeredAppuser;
                                if(isset($appuserDetails) && trim($appuserDetails->email) != '')
                                {
                                    $email = $appuserDetails->email;
                                    $isValidUser = TRUE;
                                }
                            } 
                            else
                            {
                                $isValidUser = TRUE;
                            }

                            if($isValidUser)
                            {                            
                                $encParticipantId = sracEncryptNumberData($contId, $userSession);

                                $contDetails = array();
                                $contDetails["id"] = $encParticipantId;
                                $contDetails["email"] = $email;
                                $contDetails["name"] = $name;
                                $contDetails["regdId"] = sracEncryptNumberData($usrCont->regd_appuser_id, $userSession); 
                                // $contDetails["regdObj"] = $usrCont->registeredAppuser;

                                array_push($applicableParticipantArr, $contDetails);

                                if(in_array($usrCont->regd_appuser_id, $selectedParticipantIdArr))
                                {
                                    array_push($selectedEncParticipantIdArr, $encParticipantId);
                                }
                            }                           
                        }

                        $showProfileSelection = 1;
                    }
                }

                $selectedOrgId = Crypt::encrypt("");

                $userProfileArr = array();
                    
                $userProfileArr[$selectedOrgId] = "Personal";
                // $depData['id'] = "";
                // $depData['text'] = "Personal";
                // array_push($userProfileArr, $depData);

                $hasApplicableEnterpriseProfiles = 0;
                    
                $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
                foreach($userOrganizations as $userOrg)
                {
                    if(isset($userOrg->organization) && $userOrg->organization->is_active == 1)
                    {
                        $tempOrgId = $userOrg->organization_id;
                        $tempEmpId = $userOrg->emp_id;

                        $depMgmtObj = New ContentDependencyManagementClass;
                        $depMgmtObj->withOrgIdAndEmpId($tempOrgId, $tempEmpId);
                        $isEmpActive = $depMgmtObj->getEmployeeIsActive();
                        $employeeConstant = $depMgmtObj->getEmployeeConstantObject();

                        if($isEmpActive == 1 && isset($employeeConstant) && isset($employeeConstant->is_srac_org_share_enabled) && $employeeConstant->is_srac_org_share_enabled == 1)
                        {
                            $hasApplicableEnterpriseProfiles = 1;

                            $orgEmployeeName = $depMgmtObj->getEmployeeOrUserName();

                            $tempEncOrgId = Crypt::encrypt($tempOrgId."_".$tempEmpId);
                            // $depData = array();
                            // $depData['id'] = $tempEncOrgId;
                            // $depData['text'] = $userOrg->organization->system_name." [".$orgEmployeeName."]";

                            // array_push($userProfileArr, $depData);

                            if($orgId == $tempOrgId && $orgEmpId == $tempEmpId)
                            {
                                $selectedOrgId = $tempEncOrgId;
                            }

                            $userProfileArr[$tempEncOrgId] = $userOrg->organization->system_name." [".$orgEmployeeName."]";
                        } 
                    }
                }

                if($showProfileSelection == 1 && $hasApplicableEnterpriseProfiles == 0)
                {
                    $showProfileSelection = 0;
                }

                // $showProfileSelection = 0;
                
                $applicableParticipantArrJson = json_encode($applicableParticipantArr);
                    
                $pageDesc = $pageDesc." "."Video Conference";
            
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $viewDetails = array();
                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['isView'] = $isView;
                $viewDetails['id'] = sracEncryptNumberData($videoConferenceId, $userSession);
                $viewDetails['isScheduled'] = $isScheduled;
                $viewDetails['videoConference'] = $videoConference;
                $viewDetails['videoConferenceParticipants'] = $videoConferenceParticipants;
                $viewDetails['applicableParticipantArr'] = $applicableParticipantArr;
                $viewDetails['startTs'] = $startUtcTs;
                $viewDetails['endTs'] = $endUtcTs;
                $viewDetails['meetingTitle'] = $meetingTitle;
                $viewDetails['isOpenConference'] = $isOpenConference;
                $viewDetails['participantCtrlName'] = $participantCtrlName;
                $viewDetails['consGroupId'] = sracEncryptNumberData($consGroupId, $userSession);
                $viewDetails['userDisplayName'] = $userOrEmpName;
                $viewDetails['applicableParticipantArrJson'] = $applicableParticipantArrJson;
                $viewDetails['showProfileSelection'] = $showProfileSelection;
                $viewDetails['userProfileArr'] = $userProfileArr;
                $viewDetails['selectedOrgId'] = $selectedOrgId;
                $viewDetails['selectedParticipantIdArr'] = $selectedEncParticipantIdArr;
                $viewDetails['isEdit'] = $isEdit;
                $viewDetails['editConferenceId'] = $editConferenceId;
           
                $_viewToRender = View::make('videoConference.partialview._videoConferenceAddEditModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();

                $response['view'] = $_viewToRender;   
                $response['applicableParticipantArr'] = $applicableParticipantArr;  
                $response['userProfileArr'] = $userProfileArr;  
                $response['selectedOrgId'] = $selectedOrgId;   

                $response['videoConferenceParticipants'] = $videoConferenceParticipants;
                // $response['selectedParticipantIdArr'] = $selectedEncParticipantIdArr;
                // $response['selectedParticipantIdArrPlain'] = $selectedParticipantIdArr;
                // $response['tempParticipantIdArr'] = $tempParticipantIdArr;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function loadRelevantConferenceParticipants()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $reqIsFolder = Input::get('reqIsFolder');
        $reqFOrGId = Input::get('reqFOrGId');
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken); 

                $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();             
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
                
                $participantCtrlName = 'contactIdArr';
                $empOrUserId = $userId;
                if($orgId > 0)
                {
                    $empOrUserId = $orgEmpId;
                    $participantCtrlName = 'orgEmpIdArr';
                }

                $consGroupId = 0;
                if(isset($reqFOrGId) && $reqFOrGId > 0)
                {
                    if(isset($reqIsFolder) && ($reqIsFolder == 0 || $reqIsFolder == 1))
                    {
                        if($reqIsFolder == 0)
                        {
                            $selGroupDetails = $depMgmtObj->getGroupObject($reqFOrGId);
                            if(isset($selGroupDetails))
                            {
                                $consGroupId = $reqFOrGId;
                            }
                        }
                    }
                }
                
                $isPremiumUser = $user->is_premium;
                
                $applicableParticipantArr = array();
               
                
                if($consGroupId > 0)
                {
                    $groupMembers = $depMgmtObj->getGroupMembers($consGroupId);
                    if(isset($groupMembers) && count($groupMembers) > 0)
                    {
                        foreach ($groupMembers as $memberUser) 
                        {
                            if($orgId > 0)
                            {
                                $id = $memberUser->employee_id;                             
                            }
                            else
                            {
                                $id = $memberUser->member_appuser_id;
                            }    
                            $name = $memberUser->name;
                            $email = $memberUser->email;                            
                            
                            $userDetails = array();
                            $userDetails['id'] = sracEncryptNumberData($id, $userSession);
                            $userDetails['name'] = $name;
                            $userDetails['email'] = $email;
                            
                            array_push($applicableParticipantArr, $userDetails);
                        }
                    }
                }
                else if($orgId > 0)
                {
                    $modelObj = New OrgEmployee;
                    $modelObj = $modelObj->setConnection($orgDbConName);
                    
                    $userUsers = $modelObj->verified()->exceptEmployee($orgEmpId)->ofDistinctEmployee();
                    $userUsers = $userUsers->orderBy('employee_name', 'ASC');
                    if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                    {
                        $userUsers = $userUsers->ofEmail($consVirtualFolderSenderEmail);
                    }
                    $userUserArr = $userUsers->get(); 

                    foreach ($userUserArr as $userUser) 
                    {
                        $id = $userUser->employee_id;
                        $name = $userUser->employee_name;
                        $email = $userUser->email;
                        $isVerified = $userUser->is_verified;
                        
                        
                        $userDetails = array();
                        $userDetails['id'] = sracEncryptNumberData($id, $userSession);
                        $userDetails['name'] = $name;
                        $userDetails['email'] = $email;
                        
                        array_push($applicableParticipantArr, $userDetails);
                    }
                }
                else
                {
                    $userRegEmail = $user->email;
                    
                    $userCont = AppuserContact::ofUser($userId)->where('email','<>',$userRegEmail);
                    $isBlockedValArr = [ 0 ];
                    $userCont = $userCont->whereIn('is_blocked', $isBlockedValArr);
                    $userCont = $userCont->orderBy('name', 'asc');
                    $userCont = $userCont->where('is_srac_regd', '=', '1');
                    if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                    {
                        $userCont = $userCont->ofEmail($consVirtualFolderSenderEmail);
                    }
                    $userContArr = $userCont->get();

                    foreach ($userContArr as $usrCont) 
                    {
                        $contId = $usrCont->appuser_contact_id;
                        $name = $usrCont->name;
                        $email = $usrCont->email;

                        $isValidUser = FALSE;
                        if(trim($email) == "")
                        {
                            $appuserDetails = $usrCont->registeredAppuser;
                            if(isset($appuserDetails) && trim($appuserDetails->email) != '')
                            {
                                $email = $appuserDetails->email;
                                $isValidUser = TRUE;
                            }
                        } 
                        else
                        {
                            $isValidUser = TRUE;
                        }

                        if($isValidUser)
                        {
                            $contDetails = array();
                            $contDetails["id"] = sracEncryptNumberData($contId, $userSession);
                            $contDetails["email"] = $email;
                            $contDetails["name"] = $name;
                            $contDetails["regdId"] = sracEncryptNumberData($usrCont->regd_appuser_id, $userSession); 
                            // $contDetails["regdObj"] = $usrCont->registeredAppuser;

                            array_push($applicableParticipantArr, $contDetails);
                        }                           
                    }
                }
                
                
                $applicableParticipantArrJson = json_encode($applicableParticipantArr);

                $response['applicableParticipantArr'] = $applicableParticipantArr;
                $response['applicableParticipantArrJson'] = $applicableParticipantArrJson;
                $response['participantCtrlName'] = $participantCtrlName;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function conferenceJoinModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceCode = Input::get('conferenceCode');
        $conferenceId = Input::get('conferenceId');
        $byAuthentication = Input::get('byAuthentication');
        $isStart = Input::get('isStart');

        if(!isset($byAuthentication))
        {
            $byAuthentication = 1;
        }
        
        
        if(!isset($isSchedule))
        {
            $isSchedule = 0;
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();

                if(!isset($isStart) && $isStart != 1)
                {
                    $isStart = 0;
                }

                if(!isset($conferenceCode))
                {
                    $conferenceCode = "";
                }      

                $conferenceDetails = null;
                if($byAuthentication == 0 && isset($conferenceId) && $conferenceId > 0)
                {
                    $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, FALSE); 
                    if(isset($videoConference))
                    {
                        $conferenceDetails = $videoConference;
                        $byAuthentication = 0;
                    }
                    elseif(isset($conferenceCode) && $conferenceCode != "")
                    {
                        $performAuthentication = false;
                        $conferencePassword = "";
                        $videoConferenceIdFetchResponse = $depMgmtObj->fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword); 
                        if(isset($videoConferenceIdFetchResponse))
                        {
                            $conferenceId = $videoConferenceIdFetchResponse['conferenceId'];

                            $depMgmtObj = $videoConferenceIdFetchResponse['confDepMgmtObj'];
                            $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, FALSE); 
                            if(isset($videoConference))
                            {
                                $conferenceDetails = $videoConference;
                                $byAuthentication = 0;
                            }
                        }
                    }
                }
                else
                {
                    $byAuthentication = 1;
                }          
                    
                $pageDesc = "Join"." "."Video Conference";
            
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $viewDetails = array();
                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['isStart'] = $isStart;
                $viewDetails['conferenceCode'] = $conferenceCode;
                $viewDetails['conferenceDetails'] = $conferenceDetails;
                $viewDetails['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
                $viewDetails['byAuthentication'] = $byAuthentication;
                $viewDetails['userDisplayName'] = $userOrEmpName;
                $viewDetails['consOrgKey'] = $encOrgId;
           
                $_viewToRender = View::make('videoConference.partialview._videoConferenceJoinModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();

                $response['view'] = $_viewToRender;   
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function conferenceConductModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');
        $cbDisableVideo = Input::get('cbDisableVideo');
        $cbMuteAudio = Input::get('cbMuteAudio');
        $userDisplayName = Input::get('userDisplayName');
        $conferenceCode = Input::get('conferenceCode');
        $forPopUp = Input::get('forPopUp');

        if(!isset($forPopUp) || $forPopUp != 1)
        {
            $forPopUp = 0;
        }

        if(!isset($cbDisableVideo) || $cbDisableVideo != 1)
        {
            $cbDisableVideo = 0;
        }

        if(!isset($cbMuteAudio) || $cbMuteAudio != 1)
        {
            $cbMuteAudio = 0;
        }        
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $videoConference = NULL;//$depMgmtObj->getVideoConferenceObject($conferenceId, FALSE);

                if(!isset($videoConference) && isset($conferenceCode) && $conferenceCode != "")
                {
                    $performAuthentication = false;
                    $conferencePassword = "";
                    $videoConferenceIdFetchResponse = $depMgmtObj->fetchVideoConferenceDetailsByAuthentication($conferenceCode, $performAuthentication, $conferencePassword); 
                    if(isset($videoConferenceIdFetchResponse))
                    {
                        $conferenceId = $videoConferenceIdFetchResponse['conferenceId'];

                        $depMgmtObj = $videoConferenceIdFetchResponse['confDepMgmtObj'];
                        $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, FALSE);
                    }
                }

                if(isset($videoConference))
                {
                    $canJoinResponse = $depMgmtObj->canUserJoinVideoConference($conferenceId);  
                    $status = $canJoinResponse['status'];
                    $msg = $canJoinResponse['msg'];

                    if($status == 1)
                    {
                        $pageDesc = "HyLyt - Video Conference";

                        $videoConferenceParticipants = $depMgmtObj->getVideoConferenceParticipants($conferenceId);

                        $consParticipantName = $canJoinResponse['participantName'];
                        if(isset($userDisplayName) && $userDisplayName != "")
                        {
                            $consParticipantName = $userDisplayName;
                        }
                
                        $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                        $viewDetails = array();
                        $viewDetails['page_description'] = $pageDesc;
                        $viewDetails['conferenceId'] = sracEncryptNumberData($canJoinResponse['conferenceId'], $userSession);
                        $viewDetails['meetingTitle'] = $canJoinResponse['conferenceSubject'];
                        $viewDetails['meetingId'] = $canJoinResponse['meetingId'];
                        $viewDetails['domainName'] = $canJoinResponse['domainName'];
                        $viewDetails['participantName'] = $consParticipantName;
                        $viewDetails['participantEmail'] = $canJoinResponse['participantEmail'];
                        $viewDetails['organizationName'] = $canJoinResponse['organizationName'];
                        $viewDetails['conferenceCode'] = $canJoinResponse['conferenceCode'];
                        $viewDetails['isVideoDisabled'] = $cbDisableVideo;
                        $viewDetails['isAudioMuted'] = $cbMuteAudio;
                        $viewDetails['currOrgId'] = $encOrgId;
                        $viewDetails['forPopUp'] = $forPopUp;
                        
                        if(isset($forPopUp) && $forPopUp == 1)
                        {
                            $_viewToRender = View::make('videoConference.partialview._videoConferenceConductSubView', $viewDetails);
                            $_viewToRender = $_viewToRender->render();
                        }
                        else
                        {
                            $_viewToRender = View::make('videoConference.partialview._videoConferenceConductModal', $viewDetails);
                            $_viewToRender = $_viewToRender->render();
                        }

                        $response['view'] = $_viewToRender; 
                        $response['isWaitingRoom'] = 0; 
                    }  
                    elseif(isset($canJoinResponse['allowWaitingRoomJoin']) && $canJoinResponse['allowWaitingRoomJoin'] == 1)
                    {
                        $pageDesc = "HyLyt - Video Conference Waiting Room";

                        $consParticipantName = $canJoinResponse['participantName'];
                        if(isset($userDisplayName) && $userDisplayName != "")
                        {
                            $consParticipantName = $userDisplayName;
                        }

                        $viewDetails = array();
                        $viewDetails['page_description'] = $pageDesc;
                        $viewDetails['conferenceId'] = sracEncryptNumberData($canJoinResponse['conferenceId'], $userSession);
                        $viewDetails['meetingTitle'] = $canJoinResponse['conferenceSubject'];
                        $viewDetails['meetingId'] = $canJoinResponse['meetingId'];
                        $viewDetails['domainName'] = $canJoinResponse['domainName'];
                        $viewDetails['participantName'] = $consParticipantName;
                        $viewDetails['participantEmail'] = $canJoinResponse['participantEmail'];
                        $viewDetails['organizationName'] = $canJoinResponse['organizationName'];
                        $viewDetails['conferenceCode'] = $canJoinResponse['conferenceCode'];
                        $viewDetails['isVideoDisabled'] = $cbDisableVideo;
                        $viewDetails['isAudioMuted'] = $cbMuteAudio;
                        $viewDetails['currOrgId'] = $encOrgId;
                        $viewDetails['forPopUp'] = $forPopUp;
                        
                        
                        $_viewToRender = View::make('videoConference.partialview._videoConferenceWaitingRoomModal', $viewDetails);
                        $_viewToRender = $_viewToRender->render();
                        
                        $status = 1;
                        $msg = "";

                        $response['view'] = $_viewToRender; 
                        $response['isWaitingRoom'] = 1; 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = "No such conference found";
                }

                // $response['vcFEtched'] = $videoConference;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function getConferenceInformation()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');      
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $videoConferenceResponse = $depMgmtObj->getUserVideoConferenceInformation($conferenceId, FALSE); 

                $response = $videoConferenceResponse;
                $status = $videoConferenceResponse['status'];
                $msg = $videoConferenceResponse['msg'];

                if($status > 0)
                {
                    $orgEmpObject = $depMgmtObj->getPlainEmployeeObject();

                    if(isset($orgEmpObject))
                    {
                        $orgMapKey = $orgEmpObject->org_emp_key;
                        $response['orgMapKey'] = $orgMapKey;
                    }
                }
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function getConferenceInformationModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');

        if(!isset($cbDisableVideo) || $cbDisableVideo != 1)
        {
            $cbDisableVideo = 0;
        }

        if(!isset($cbMuteAudio) || $cbMuteAudio != 1)
        {
            $cbMuteAudio = 0;
        }        
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $videoConferenceResponse = $depMgmtObj->getUserVideoConferenceInformation($conferenceId, FALSE); 
                
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $status = $videoConferenceResponse['status'];
                $msg = $videoConferenceResponse['msg'];

                $pageDesc = 'Video Conference Details';

                $viewDetails = $videoConferenceResponse;
                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['tzOfs'] = $offsetInMinutes * 1;
                $viewDetails['tzStr'] = $tzStr;
                $viewDetails['orgKey'] = $encOrgId;
                $viewDetails['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
           
                $_viewToRender = View::make('videoConference.partialview._videoConferenceInformationModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();

                $response['view'] = $_viewToRender; 
                $response['viewDetails'] = $viewDetails; 
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function saveConferenceInformationAsUserContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                // $depMgmtObj->setCurrentLoginToken($loginToken);

                $contentResponse = $depMgmtObj->saveUserVideoConferenceInformationAsContent($conferenceId, $offsetInMinutes);

                $status = $contentResponse['status'];
                $msg = $contentResponse['msg'];
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }   

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function getConferenceShareModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');     
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user))
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $videoConferenceResponse = $depMgmtObj->getUserVideoConferenceInformation($conferenceId, FALSE); 
                
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $status = $videoConferenceResponse['status'];
                $msg = $videoConferenceResponse['msg'];

                $pageDesc = 'Invite';

                $viewDetails = $videoConferenceResponse;
                $viewDetails['page_description'] = $pageDesc;
                $viewDetails['tzOfs'] = $offsetInMinutes * 1;
                $viewDetails['tzStr'] = $tzStr;
                $viewDetails['orgKey'] = $encOrgId;
                $viewDetails['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
           
                $_viewToRender = View::make('videoConference.partialview._videoConferenceShareModal', $viewDetails);
                $_viewToRender = $_viewToRender->render();

                $response['view'] = $_viewToRender; 
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function sendConferenceEmailInvitation()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');
        $recipientName = Input::get('recipientName');
        $recipientEmail = Input::get('recipientEmail');
        
        $response = array();
        if($encUserId != "" && $recipientName != "" && $recipientEmail != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user))
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $isEmailValid = CommonFunctionClass::validateEmailAddress($recipientEmail);
                if($isEmailValid)
                {
                    $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();
        
                    $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, FALSE); 
                    if(isset($videoConference))
                    {
                        $currentTimestamp = CommonFunctionClass::getCreateTimestamp();
                        if($currentTimestamp < $videoConference->scheduled_start_ts || $currentTimestamp < $videoConference->scheduled_end_ts)
                        {
                            $status = 1;
                            $depMgmtObj->onVideoConferenceParticipantInvited($conferenceId, $videoConference, $recipientName, $recipientEmail);
                        }
                        else
                        {
                            $status = -1;
                            $msg = 'Conference has already expired';   
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_data');     
                    }
                }
                else
                {
                    $status = -1;
                    $msg = 'Not a valid email';     
                }                       
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 

    public function getConferenceShareWithinHyLytModal()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $tzStr = Input::get('tzStr');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');     
        
        $response = array();
        if($encUserId != "" && $conferenceId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user))
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);

                $participantCtrlName = 'contactIdArr';
                $empOrUserId = $userId;
                if($orgId > 0)
                {
                    $empOrUserId = $orgEmpId;
                    $participantCtrlName = 'orgEmpIdArr';
                }

                $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

                $consGroupId = 0;

                $videoConferenceResponse = $depMgmtObj->getUserVideoConferenceInformation($conferenceId, FALSE); 
                
                $intJs = array("/dist/bootstrap-datepicker/dist/js/common_dt.js");

                $status = $videoConferenceResponse['status'];
                $msg = $videoConferenceResponse['msg'];

                if($status > 0)
                {
                    $conferenceDetailsObj = $videoConferenceResponse['videoConference'];
                    $conferenceParticipantArr = $videoConferenceResponse['participants'];

                    $conferenceParticipantIdArr = array();
                    if(isset($conferenceParticipantArr) && count($conferenceParticipantArr) > 0)
                    {
                        foreach ($conferenceParticipantArr as $key => $conferenceParticipantObj)
                        {
                            array_push($conferenceParticipantIdArr, sracDecryptNumberData($conferenceParticipantObj['participantId'], $userSession));
                        }
                    }

                    $applicableParticipantArr = array();
                   
                    if($consGroupId > 0)
                    {
                        $groupMembers = $depMgmtObj->getGroupMembers($consGroupId);
                        if(isset($groupMembers) && count($groupMembers) > 0)
                        {
                            foreach ($groupMembers as $memberUser) 
                            {
                                if($orgId > 0)
                                {
                                    $id = $memberUser->employee_id;                             
                                }
                                else
                                {
                                    $id = $memberUser->member_appuser_id;
                                }    
                                $name = $memberUser->name;
                                $email = $memberUser->email;     

                                if(!in_array($id, $conferenceParticipantIdArr))                       
                                {
                                    $userDetails = array();
                                    $userDetails['id'] = sracEncryptNumberData($id, $userSession);
                                    $userDetails['name'] = $name;
                                    $userDetails['email'] = $email;
                                    
                                    array_push($applicableParticipantArr, $userDetails);
                                }
                            }
                        }
                    }
                    else if($orgId > 0)
                    {
                        $modelObj = New OrgEmployee;
                        $modelObj = $modelObj->setConnection($orgDbConName);
                        
                        $userUsers = $modelObj->verified()->exceptEmployee($orgEmpId)->ofDistinctEmployee();
                        $userUsers = $userUsers->orderBy('employee_name', 'ASC');
                        if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                        {
                            $userUsers = $userUsers->ofEmail($consVirtualFolderSenderEmail);
                        }
                        $userUserArr = $userUsers->get(); 

                        foreach ($userUserArr as $userUser) 
                        {
                            $id = $userUser->employee_id;
                            $name = $userUser->employee_name;
                            $email = $userUser->email;
                            $isVerified = $userUser->is_verified;
                            
                            if(!in_array($id, $conferenceParticipantIdArr))                       
                            {
                                $userDetails = array();
                                $userDetails['id'] = sracEncryptNumberData($id, $userSession);
                                $userDetails['name'] = $name;
                                $userDetails['email'] = $email;
                                
                                array_push($applicableParticipantArr, $userDetails);
                            }
                        }
                    }
                    else
                    {
                        $userRegEmail = $user->email;
                        
                        $userCont = AppuserContact::ofUser($userId)->where('email','<>',$userRegEmail);
                        $isBlockedValArr = [ 0 ];
                        $userCont = $userCont->whereIn('is_blocked', $isBlockedValArr);
                        $userCont = $userCont->orderBy('name', 'asc');
                        $userCont = $userCont->where('is_srac_regd', '=', '1');
                        if(isset($consVirtualFolderSenderEmail) && $consVirtualFolderSenderEmail != "")
                        {
                            $userCont = $userCont->ofEmail($consVirtualFolderSenderEmail);
                        }
                        $userContArr = $userCont->get();

                        foreach ($userContArr as $usrCont) 
                        {
                            $contId = $usrCont->appuser_contact_id;
                            $name = $usrCont->name;
                            $email = $usrCont->email;

                            $isValidUser = FALSE;
                            if(trim($email) == "")
                            {
                                $appuserDetails = $usrCont->registeredAppuser;
                                if(isset($appuserDetails) && trim($appuserDetails->email) != '')
                                {
                                    $email = $appuserDetails->email;
                                    $isValidUser = TRUE;
                                }
                            } 
                            else
                            {
                                $isValidUser = TRUE;
                            }

                            if($isValidUser)
                            {
                                if(!in_array($usrCont->regd_appuser_id, $conferenceParticipantIdArr))                       
                                {
                                    $contDetails = array();
                                    $contDetails["id"] = sracEncryptNumberData($contId, $userSession);
                                    $contDetails["email"] = $email;
                                    $contDetails["name"] = $name;
                                    $contDetails["regdId"] = sracEncryptNumberData($usrCont->regd_appuser_id, $userSession); 
                                    // $contDetails["regdObj"] = $usrCont->registeredAppuser;

                                    array_push($applicableParticipantArr, $contDetails);
                                }
                            }                           
                        }
                    }
                    
                    $applicableParticipantArrJson = json_encode($applicableParticipantArr);

                    $pageDesc = 'Invite';

                    $viewDetails = $videoConferenceResponse;
                    $viewDetails['page_description'] = $pageDesc;
                    $viewDetails['tzOfs'] = $offsetInMinutes * 1;
                    $viewDetails['tzStr'] = $tzStr;
                    $viewDetails['orgKey'] = $encOrgId;
                    $viewDetails['applicableParticipantArr'] = $applicableParticipantArr;
                    $viewDetails['applicableParticipantArrJson'] = $applicableParticipantArrJson;
                    $viewDetails['participantCtrlName'] = $participantCtrlName;
                    $viewDetails['empOrUserId'] = $empOrUserId;
                    $viewDetails['conferenceId'] = sracEncryptNumberData($conferenceId, $userSession);
               
                    $_viewToRender = View::make('videoConference.partialview._videoConferenceShareWithinHyLytModal', $viewDetails);
                    $_viewToRender = $_viewToRender->render();

                    $response['view'] = $_viewToRender; 
                }                    
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
    
    public function sendConferenceInvitationWithinHyLyt()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $offsetInMinutes = Input::get('ofs');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $conferenceId = Input::get('conferenceId');
        $contactIdArr = Input::get('contactIdArr');
        $contactIsModeratorArr = Input::get('contactIsModeratorArr');
        $orgEmpIdArr = Input::get('orgEmpIdArr');
        $orgEmpIsModeratorArr = Input::get('orgEmpIsModeratorArr');
        $isScheduled = Input::get('isScheduled');
        $selGroupId = Input::get('selGroupId');
        $selGroupMemberIdArr = Input::get('selGroupMemberIdArr');

        if(!isset($isScheduled) || $isScheduled != 1)
        {
            $isScheduled = 0;
        }

        if(!isset($selGroupId) || $selGroupId == "")
        {
            $selGroupId = "";
            $selGroupMemberIdArr = array();
        }

        if(!isset($selGroupMemberIdArr))
        {
            $selGroupMemberIdArr = array();
        }
        elseif(!is_array($selGroupMemberIdArr))
        {
            $selGroupMemberIdArr = json_decode($selGroupMemberIdArr);
        }

        if(!isset($contactIdArr))
        {
            $contactIdArr = array();
        }
        elseif(!is_array($contactIdArr))
        {
            $contactIdArr = json_decode($contactIdArr);
        }

        if(!isset($contactIsModeratorArr))
        {
            $contactIsModeratorArr = array();
        }
        elseif(!is_array($contactIsModeratorArr))
        {
            $contactIsModeratorArr = json_decode($contactIsModeratorArr);
        }

        if(!isset($orgEmpIdArr))
        {
            $orgEmpIdArr = array();
        }
        elseif(!is_array($orgEmpIdArr))
        {
            $orgEmpIdArr = json_decode($orgEmpIdArr);
        }

        if(!isset($orgEmpIsModeratorArr))
        {
            $orgEmpIsModeratorArr = array();
        }
        elseif(!is_array($orgEmpIsModeratorArr))
        {
            $orgEmpIsModeratorArr = json_decode($orgEmpIsModeratorArr);
        }
        
        $response = array();
        if($encUserId != "")
        {
            if(!isset($loginToken) || $loginToken == "")
            {
                $response['status'] = -1;
                $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
                $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

                return Response::json($response);
            }
            
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user))
            {
                $userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
                if(!isset($userSession))
                {
                    $response['status'] = -1;
                    $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
                    $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

                    return Response::json($response);
                }

                $conferenceId = sracDecryptNumberData($conferenceId, $userSession);
                $selGroupId = sracDecryptNumberData($selGroupId, $userSession);

                $contactIdArr = sracDecryptNumberArrayData($contactIdArr, $userSession);
                $orgEmpIdArr = sracDecryptNumberArrayData($orgEmpIdArr, $userSession);
                $selGroupMemberIdArr = sracDecryptNumberArrayData($selGroupMemberIdArr, $userSession);
                
                $status = 1;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);

                $userOrEmpName = $depMgmtObj->getEmployeeOrUserName();
    
                $videoConference = $depMgmtObj->getVideoConferenceObject($conferenceId, FALSE); 
                if(isset($videoConference))
                {
                    $currentTimestamp = CommonFunctionClass::getCreateTimestamp();
                    if($currentTimestamp < $videoConference->scheduled_start_ts || $currentTimestamp < $videoConference->scheduled_end_ts)
                    {
                        $status = 1;

                        $participantIdArr = array();
                        $participantIsModeratorArr = array();
                        $partIndex = 0;

                        $hasGroupSelection = FALSE;
                        if($selGroupId > 0)
                        {
                            $selGroup = $depMgmtObj->getGroupObject($selGroupId);
                            $selGroupMembers = $depMgmtObj->getGroupMembers($selGroupId);

                            if(isset($selGroup) && isset($selGroupMembers))
                            {
                                $grpHasUserAsMember = FALSE;
                                foreach ($selGroupMembers as $groupMember) 
                                {
                                    $consUserIsModerator = 0;
                                    if($orgId > 0)
                                    {
                                        $grpMemberUserId = $groupMember->employee_id;
                                        if($grpMemberUserId == $orgEmpId && in_array($grpMemberUserId, $selGroupMemberIdArr))
                                        {
                                            $grpHasUserAsMember = TRUE;
                                            $consUserIsModerator = 1;
                                        }
                                    }
                                    else
                                    {
                                        $grpMemberUserId = $groupMember->member_appuser_id;
                                        if($grpMemberUserId == $userId && in_array($grpMemberUserId, $selGroupMemberIdArr))
                                        {
                                            $grpHasUserAsMember = TRUE;
                                            $consUserIsModerator = 1;
                                        }
                                    }

                                    if(in_array($grpMemberUserId, $selGroupMemberIdArr))
                                    {
                                        $participantIdArr[$partIndex] = $grpMemberUserId;
                                        $participantIsModeratorArr[$partIndex] = $consUserIsModerator;
                                        $partIndex++;
                                    }
                                }

                                if($grpHasUserAsMember)
                                {
                                    $hasGroupSelection = TRUE;
                                }
                                else
                                {
                                    $participantIdArr = array();
                                    $participantIsModeratorArr = array();
                                    $partIndex = 0;
                                }
                            }
                        }

                        if(!$hasGroupSelection)
                        {
                            if($orgId > 0 && count($orgEmpIdArr) > 0)
                            {
                                $participantIdArr[$partIndex] = $orgEmpId;
                                $participantIsModeratorArr[$partIndex] = 1;
                                $partIndex++;

                                for($i=0; $i<count($orgEmpIdArr); $i++)
                                {
                                    $consOrgEmpId = $orgEmpIdArr[$i];
                                    $consOrgEmpIsModerator = isset($orgEmpIsModeratorArr[$i]) ? $orgEmpIsModeratorArr[$i] : 0;
                                    
                                    if($consOrgEmpId > 0 && !in_array($consOrgEmpId, $participantIdArr))
                                    {
                                        $participantIdArr[$partIndex] = $consOrgEmpId;
                                        $participantIsModeratorArr[$partIndex] = $consOrgEmpIsModerator;
                                        $partIndex++;
                                    }
                                }
                            }
                            elseif($orgId == 0 && count($contactIdArr) > 0)
                            {
                                $participantIdArr[$partIndex] = $userId;
                                $participantIsModeratorArr[$partIndex] = 1;
                                $partIndex++;

                                for($i=0; $i<count($contactIdArr); $i++)
                                {
                                    $contactId = $contactIdArr[$i];
                                    $contactIsModerator = isset($contactIsModeratorArr[$i]) ? $contactIsModeratorArr[$i] : 0;
                                    
                                    if($contactId > 0)
                                    {
                                        $consUser = AppuserContact::ofUserContact($contactId)->first();
                                    
                                        if(isset($consUser) && $consUser->is_srac_regd == 1 && $consUser->regd_appuser_id > 0)
                                        {
                                            $contUserEmail = $consUser->email;
                                            $contUserContactNo = $consUser->contact_no;
                                            $contUserId = $consUser->regd_appuser_id;
                                            $contAppuserDetails = $consUser->registeredAppuser;

                                            if(isset($contAppuserDetails) && !in_array($contUserId, $participantIdArr))
                                            {
                                                $participantIdArr[$partIndex] = $contUserId;
                                                $participantIsModeratorArr[$partIndex] = $contactIsModerator;
                                                $partIndex++;  
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if(isset($participantIdArr) && count($participantIdArr) == 0 && $isOpenConference == 1)
                        {
                            if($orgId > 0)
                            {
                                $participantIdArr[$partIndex] = $orgEmpId;
                                $participantIsModeratorArr[$partIndex] = 1;
                                $partIndex++;
                            }
                            elseif($orgId == 0)
                            {
                                $participantIdArr[$partIndex] = $userId;
                                $participantIsModeratorArr[$partIndex] = 1;
                                $partIndex++;
                            }
                        }
                        
                        if(isset($participantIdArr) && count($participantIdArr) > 0)
                        {                  
                            foreach ($participantIdArr as $key => $participantId) {
                                $participantIsModerator = 0;
                                $participantAddResponse = $depMgmtObj->addVideoConferenceParticipant($conferenceId, $videoConference, $participantId, $participantIsModerator);
                            }
                        }
                        else
                        {
                            $status = -1;
                            $msg = Config::get('app_config_notif.err_invalid_data');
                        } 


                    }
                    else
                    {
                        $status = -1;
                        $msg = 'Conference has already expired';   
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_data');     
                }                
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    } 
}