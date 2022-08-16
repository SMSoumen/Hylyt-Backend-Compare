<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\CloudCalendarType;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserContent;
use App\Models\Api\AppuserContentAttachment;
use App\Models\Org\Api\OrgGroupMember;
use App\Models\Api\Group;
use App\Models\Api\GroupContent;
use App\Models\Api\GroupContentAttachment;
use App\Models\Api\GroupMember;
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
use File;
use Calendar;
use View;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Log;
use App\Libraries\CloudCalendarManagementClass;

class CloudCalendarController extends Controller
{   
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;
    
    public function __construct()
    {
        
    }
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function loadRelevantCalendarList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $queryStr = Input::get('queryStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $fetchedCalendarList = $cldCalendarMgmtObj->getAllCalendars($queryStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['calendarResponse'] = $fetchedCalendarList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudCalendar.partialview._calendarSubView', $viewDetails);
                            $calendarListView = $_viewToRender->render();

                            $response['calendarListView'] = $calendarListView;
                            $response['calendarResponse'] = $fetchedCalendarList;
                        }
                        else
                        {
                            $response['calendarResponse'] = $fetchedCalendarList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function performLinkedCalendarPrimarySync()
    {
        set_time_limit(0);
        
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $primarySyncResponse = $depMgmtObj->performSyncForLinkedCloudCalendarContentSetup($cloudCalendarTypeId);   

                        $response['primarySyncResponse'] = $primarySyncResponse;                    
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function loadRelevantCalendarEventList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $calendarId = Input::get('calendarId');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $queryStr = Input::get('queryStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $fetchedEventList = $cldCalendarMgmtObj->getAllCalendarEvents($calendarId, $queryStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['eventResponse'] = $fetchedEventList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = FALSE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $eventView = $_viewToRender->render();

                            $response['eventView'] = $eventView;
                            $response['eventResponse'] = $fetchedEventList;
                        }
                        else
                        {
                            $response['eventResponse'] = $fetchedEventList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function loadRelevantCalendarEventContinuedList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $calendarId = Input::get('calendarId');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $queryStr = Input::get('queryStr');
        $cursorStr = Input::get('cursorStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $cursorStr != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $fetchedEventList = $cldCalendarMgmtObj->getAllCalendarEvents($calendarId, $queryStr, $cursorStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['eventResponse'] = $fetchedEventList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $eventView = $_viewToRender->render();

                            $response['eventView'] = $eventView;
                            $response['eventResponse'] = $fetchedEventList;
                        }
                        else
                        {
                            $response['eventResponse'] = $fetchedEventList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function loadRelevantCalendarEventDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $calendarId = Input::get('calendarId');
        $eventId = Input::get('eventId');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $eventId != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $fetchedEventDetails = $cldCalendarMgmtObj->getCalendarEventDetails($calendarId, $eventId);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['eventResponse'] = $fetchedEventDetails;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $eventView = $_viewToRender->render();

                            $response['eventView'] = $eventView;
                            $response['eventResponse'] = $fetchedEventDetails;
                        }
                        else
                        {
                            $response['eventResponse'] = $fetchedEventDetails;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function checkRelevantEventCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $calendarId = Input::get('calendarId');
        $eventId = Input::get('eventId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $eventId != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $eventResponse = $cldCalendarMgmtObj->checkEventCanBeDeleted($calendarId, $eventId);
                            
                        $response['eventResponse'] = $eventResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function removeRelevantEvent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $calendarId = Input::get('calendarId');
        $eventId = Input::get('eventId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $eventId != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $eventResponse = $cldCalendarMgmtObj->performEventDelete($calendarId, $eventId);
                            
                        $response['eventResponse'] = $eventResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function checkRelevantFolderCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $folderId = Input::get('folderId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $folderId != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $folderResponse = $cldCalendarMgmtObj->checkFolderCanBeDeleted($folderId);
                            
                        $response['folderResponse'] = $folderResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function addNewEvent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $calendarId = Input::get('calendarId');
        $appKey = Input::get('appKey');
        $startTs = Input::get('startTs');
        $endTs = Input::get('endTs');
        $summary = Input::get('summary');
        $description = Input::get('description');
        $tzOfs = Input::get('tzOfs');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $summary != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $genEventId = CommonFunctionClass::generateRetailCloudCalendarEventIdString();

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $eventResponse = $cldCalendarMgmtObj->addNewEvent($tzOfs, $calendarId, $genEventId, $startTs, $endTs, $summary, $description);
                            
                        $response['eventResponse'] = $eventResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */
    public function updateExistingEvent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $calendarId = Input::get('calendarId');
        $eventId = Input::get('eventId');
        $appKey = Input::get('appKey');
        $startTs = Input::get('startTs');
        $endTs = Input::get('endTs');
        $summary = Input::get('summary');
        $description = Input::get('description');
        $tzOfs = Input::get('tzOfs');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "" && $calendarId != "" && $eventId != "" && $summary != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldCalendarMgmtObj = New CloudCalendarManagementClass;
                        $cldCalendarMgmtObj->withAppKey($appKey);
                        $cldCalendarMgmtObj->withAccessTokenAndCalendarTypeCode($accessToken, $cloudCalendarTypeCode);
                        $eventResponse = $cldCalendarMgmtObj->updateExistingEvent($tzOfs, $calendarId, $eventId, $startTs, $endTs, $summary, $description);
                            
                        $response['eventResponse'] = $eventResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
    
    /**
     * Add Content.
     *
     * @return json array
     */
    public function performCloudCalendarEventImportAsContent()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $fileBaseFolderArr = Input::get('fileBaseFolderArr');
        $fileNameArr = Input::get('fileNameArr');
        $fileIdArr = Input::get('fileIdArr');
        $fileSizeArr = Input::get('fileSizeArr');
        $fileCalendarUrlArr = Input::get('fileCalendarUrlArr');
        $cloudFileThumbStrArr = Input::get('cloudFileThumbStrArr');
        $cloudCalendarTypeIdArr = Input::get('cloudCalendarTypeIdArr');
        $cloudCalendarTypeCodeArr = Input::get('cloudCalendarTypeCodeArr');
        $appKey = Input::get('appKey');

        if(!isset($fileIdArr) || !is_array($fileIdArr))
        {
            if(isset($fileIdArr))
            {
                $fileIdArr = json_decode($fileIdArr);
            }
            else
            {
                $fileIdArr = array();
            }
        }

        if(!isset($fileSizeArr) || !is_array($fileSizeArr))
        {
            if(isset($fileSizeArr))
            {
                $fileSizeArr = json_decode($fileSizeArr);
            }
            else
            {
                $fileSizeArr = array();
            }
        }

        if(!isset($cloudFileThumbStrArr) || !is_array($cloudFileThumbStrArr))
        {
            if(isset($cloudFileThumbStrArr))
            {
                $cloudFileThumbStrArr = json_decode($cloudFileThumbStrArr);
            }
            else
            {
                $cloudFileThumbStrArr = array();
            }
        }

        if(!isset($fileBaseFolderArr) || !is_array($fileBaseFolderArr))
        {
            if(isset($fileBaseFolderArr))
            {
                $fileBaseFolderArr = json_decode($fileBaseFolderArr);
            }
            else
            {
                $fileBaseFolderArr = array();
            }
        }

        if(!isset($fileNameArr) || !is_array($fileNameArr))
        {
            if(isset($fileNameArr))
            {
                $fileNameArr = json_decode($fileNameArr);
            }
            else
            {
                $fileNameArr = array();
            }
        }

        if(!isset($fileCalendarUrlArr) || !is_array($fileCalendarUrlArr))
        {
            if(isset($fileCalendarUrlArr))
            {
                $fileCalendarUrlArr = json_decode($fileCalendarUrlArr);
            }
            else
            {
                $fileCalendarUrlArr = array();
            }
        }

        if(!isset($cloudCalendarTypeIdArr) || !is_array($cloudCalendarTypeIdArr))
        {
            if(isset($cloudCalendarTypeIdArr))
            {
                $cloudCalendarTypeIdArr = json_decode($cloudCalendarTypeIdArr);
            }
            else
            {
                $cloudCalendarTypeIdArr = array();
            }
        }

        if(!isset($cloudCalendarTypeCodeArr) || !is_array($cloudCalendarTypeCodeArr))
        {
            if(isset($cloudCalendarTypeCodeArr))
            {
                $cloudCalendarTypeCodeArr = json_decode($cloudCalendarTypeCodeArr);
            }
            else
            {
                $cloudCalendarTypeCodeArr = array();
            }
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

                 
                $msg = "";
                $isFolder = TRUE; 
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $defFolderId = $depMgmtObj->getDefaultFolderId();

                $newServerContentId = 0;
                if($defFolderId > 0)
                {
                    $status = 1;

                    $compResponse = array();

                    $utcTz =  'UTC';
                    $createDateObj = Carbon::now($utcTz);
                    $createTimeStamp = $createDateObj->timestamp;                   
                    $createTimeStamp = $createTimeStamp * 1000;
                    $updateTimeStamp = $createTimeStamp;

                    $colorCode = Config::get('app_config.default_content_color_code');
                    $isLocked = Config::get('app_config.default_content_lock_status');
                    $isShareEnabled = Config::get('app_config.default_content_share_status');
                    $contentType = Config::get('app_config.content_type_a');
                    $sourceId = 0;
                    $removeAttachmentIdArr = NULL;
                    $fromTimeStamp = "";
                    $toTimeStamp = "";
                    $isMarked = 0;
                    $remindBeforeMillis = 0;
                    $repeatDuration = 0;

                    foreach ($fileIdArr as $fileIndex => $cloudFileId) 
                    {
                        $fileBaseFolder = $fileBaseFolderArr[$fileIndex];
                        $fileName = $fileNameArr[$fileIndex];
                        $fileSize = $fileSizeArr[$fileIndex];
                        $cloudFileThumbStr = $cloudFileThumbStrArr[$fileIndex];
                        $cloudCalendarTypeId = $cloudCalendarTypeIdArr[$fileIndex];
                        $cloudFileUrl = $fileCalendarUrlArr[$fileIndex];

                        $cloudCalendarType = $depMgmtObj->getCloudCalendarTypeObjectById($cloudCalendarTypeId);

                        if(isset($cloudCalendarType))
                        {
                            $cloudCalendarTypeName = $cloudCalendarType->cloud_calendar_type_name;

                            $cloudCalendarTypeNameTagId = 0;

                            $tagWithCloudCalendarTypeName = $depMgmtObj->getTagObjectByName($cloudCalendarTypeName);
                            if(!isset($tagWithCloudCalendarTypeName))
                            {
                                $tagResponse = $depMgmtObj->addEditTag(0, $cloudCalendarTypeName);
                                $cloudCalendarTypeNameTagId = $tagResponse['syncId'];
                            }
                            else
                            {
                                $cloudCalendarTypeNameTagId = $orgId > 0 ? $tagWithCloudCalendarTypeName->employee_tag_id : $tagWithCloudCalendarTypeName->appuser_tag_id;
                            }
                            
                            $tagsArr = array();
                            if($cloudCalendarTypeNameTagId > 0)
                            {
                                array_push($tagsArr, $cloudCalendarTypeNameTagId);
                            }

                            $filePathStr = "";
                            if(isset($fileBaseFolder) && trim($fileBaseFolder) != "")
                            {
                                $fileBaseFolder = trim($fileBaseFolder);

                                $filePathStr = "<br>Folder: ".$fileBaseFolder;
                            }

                            $fileSourceStr = "";
                            if(isset($cloudCalendarTypeName) && trim($cloudCalendarTypeName) != "")
                            {
                                $fileSourceStr = "<br>Source: ".$cloudCalendarTypeName;
                            }

                            $compContent = $fileName.$filePathStr.$fileSourceStr;
                            $contentTitle = $fileName;

                            $compResponse['compContent_'.$fileIndex] = $compContent;
                            $compResponse['cloudCalendarTypeName_'.$fileIndex] = $cloudCalendarTypeName;
                            $compResponse['fileSize_'.$fileIndex] = $fileSize;
                            $compResponse['fileName_'.$fileIndex] = $fileName;
                            $compResponse['cloudFileId_'.$fileIndex] = $cloudFileId;
                            $compResponse['cloudCalendarTypeNameTagId_'.$fileIndex] = $cloudCalendarTypeNameTagId;

                            $isCompleted = 0;
                            $isSnoozed = 0;

                            $contentResponse = $depMgmtObj->addEditContent(0, $compContent, $contentTitle, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed,$removeAttachmentIdArr, NULL);

                            $newServerContentId = $contentResponse['syncId'];   
                            
                            $compResponse['newServerContentId_'.$fileIndex] = $newServerContentId;

                            if($newServerContentId > 0)
                            {
                                $serverFileName = '';
                                $attachmentResponse = $depMgmtObj->addEditContentAttachment(0, $newServerContentId, $fileName, $serverFileName, $fileSize, $cloudCalendarTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $createTimeStamp);

                                $newServerContentAttachmentId = $attachmentResponse['syncId'];   

                                $compResponse['newServerContentAttachmentId_'.$fileIndex] = $newServerContentAttachmentId;

                                $isAdd = TRUE;
                                $depMgmtObj->sendRespectiveContentModificationPush($isFolder, $newServerContentId, $isAdd, NULL);
                            }
                        }
                    } 

                    // $response['compResponse'] = $compResponse;       
                }
                else
                {
                    $status = -1;
                    $msg = "Unable to save content";
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

    public function loadCloudCalendarTypeAuthenticationDependencies()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudCalendarTypeCode = Input::get('cloudCalendarType');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudCalendarTypeCode != "")
        {
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
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $cloudCalendarTypeId = $depMgmtObj->getCloudCalendarTypeIdFromCode($cloudCalendarTypeCode);
                if(isset($cloudCalendarTypeId) && $cloudCalendarTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForCalendarType($cloudCalendarTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = -1;
                        $msg = "Account already linked";
                    }
                    else
                    {
                        if($cloudCalendarTypeCode == CloudCalendarType::$DROPBOX_TYPE_CODE)
                        {
                            $status = 1;

                            $clientId = env('DROPBOX_APP_KEY');
                            $redirectUri = env('DROPBOX_REDIRECT_URI');

                            $authUrl = "https://www.dropbox.com/1/oauth2/authorize?response_type=code&client_id=".$clientId."&redirect_uri=".$redirectUri;

                            $response['authUrl'] = $authUrl;
                        }
                        // else if($cloudCalendarTypeCode == CloudCalendarType::$DROPBOX_TYPE_CODE)
                        // {
                        //     $status = 1;
                        //     $response['redirectUri'] = env('DROPBOX_REDIRECT_URI');
                        // }
                        // else if($cloudCalendarTypeCode == CloudCalendarType::$DROPBOX_TYPE_CODE)
                        // {
                        //     $status = 1;
                        //     $response['redirectUri'] = env('DROPBOX_REDIRECT_URI');
                        // }
                        else
                        {
                            $status = -1;
                            $msg = Config::get('app_config_notif.err_invalid_data');
                        }
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_calendar_type'); 
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
}