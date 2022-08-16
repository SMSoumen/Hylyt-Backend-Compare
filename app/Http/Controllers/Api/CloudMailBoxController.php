<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\CloudMailBoxType;
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
use MailBox;
use View;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Log;
use App\Libraries\CloudMailBoxManagementClass;

class CloudMailBoxController extends Controller
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
    public function loadRelevantMailBoxList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $queryStr = Input::get('queryStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $fetchedMailBoxList = $cldMailBoxMgmtObj->getAllMailBoxs($queryStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['mailBoxResponse'] = $fetchedMailBoxList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudMailBox.partialview._mailBoxSubView', $viewDetails);
                            $mailBoxListView = $_viewToRender->render();

                            $response['mailBoxListView'] = $mailBoxListView;
                            $response['mailBoxResponse'] = $fetchedMailBoxList;
                        }
                        else
                        {
                            $response['mailBoxResponse'] = $fetchedMailBoxList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadRelevantMailBoxMessageList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $queryStr = Input::get('queryStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";
                        Log::info('accessToken : '.$accessToken);
                        Log::info('cloudMailBoxTypeCode : '.$cloudMailBoxTypeCode);

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $fetchedMessageList = $cldMailBoxMgmtObj->getAllMailBoxMessages($queryStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['messageResponse'] = $fetchedMessageList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = FALSE;

                            $_viewToRender = View::make('cloudMailBox.partialview._messageSubView', $viewDetails);
                            $messageView = $_viewToRender->render();

                            $response['messageView'] = $messageView;
                            $response['messageResponse'] = $fetchedMessageList;
                        }
                        else
                        {
                            $response['messageResponse'] = $fetchedMessageList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadRelevantMailBoxMessageContinuedList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $queryStr = Input::get('queryStr');
        $cursorStr = Input::get('cursorStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $cursorStr != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $fetchedMessageList = $cldMailBoxMgmtObj->getAllMailBoxMessages($queryStr, $cursorStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['messageResponse'] = $fetchedMessageList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudMailBox.partialview._messageSubView', $viewDetails);
                            $messageView = $_viewToRender->render();

                            $response['messageView'] = $messageView;
                            $response['messageResponse'] = $fetchedMessageList;
                        }
                        else
                        {
                            $response['messageResponse'] = $fetchedMessageList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadRelevantMailBoxMessageDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $messageId = Input::get('messageId');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');
        $tzStr = Input::get('tzStr');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $messageId != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $fetchedMessageDetails = $cldMailBoxMgmtObj->getMailBoxMessageDetails($messageId);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['messageDetails'] = $fetchedMessageDetails['messageDetails'];
                            $viewDetails['tzStr'] = $tzStr;
                            $viewDetails['orgKey'] = $encOrgId;

                            $_viewToRender = View::make('cloudMailBox.partialview._messageDetailsDialog', $viewDetails);
                            $messageView = $_viewToRender->render();

                            $response['messageView'] = $messageView;
                            $response['messageResponse'] = $fetchedMessageDetails;
                        }
                        else
                        {
                            $response['messageResponse'] = $fetchedMessageDetails;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadRelevantMailBoxMessageAttachmentDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $messageId = Input::get('messageId');
        $attachmentId = Input::get('attachmentId');
        $fileName = Input::get('fileName');
        $fileMimeType = Input::get('fileMimeType');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');
        $tzStr = Input::get('tzStr');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $messageId != "" && $attachmentId != "" && $fileName != "" && $fileMimeType != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $fetchedMessageAttachmentDetails = $cldMailBoxMgmtObj->getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['messageAttachmentDetails'] = $fetchedMessageAttachmentDetails;
                            $viewDetails['tzStr'] = $tzStr;
                                
                            $fileDisposition = "inline";
                            // if($isDownloadFlag == 1 || strpos($fileMimeType, 'audio') !== false || strpos($fileMimeType, 'video') !== false)
                            //     $fileDisposition = "attachment";

                            // Log::info('fileDisposition : '.$fileDisposition.' : fileName : '.$fileName);

                            return $fileContent = response()->make($fetchedMessageAttachmentDetails['fileContent'], 200, array(
                                'Content-Type' => $fileMimeType,
                                'Content-Disposition' => $fileDisposition.'; filename="' . $fileName . '"'
                            ));
                            
                            // $response['fileStr'] = utf8_encode($fileContent);

                            // $_viewToRender = View::make('cloudMailBox.partialview._messageAttachmentDetailsDialog', $viewDetails);
                            // $attachmentView = $_viewToRender->render();

                            // $response['attachmentView'] = $attachmentView;
                            // $response['attachmentResponse'] = $fetchedMessageAttachmentDetails;
                        }
                        else
                        {
                            $response['attachmentResponse'] = $fetchedMessageAttachmentDetails;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadMultiRelevantMailBoxMessageAttachmentDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $messageIdArr = Input::get('messageIdArr');
        $attachmentIdArr = Input::get('attachmentIdArr');
        $fileNameArr = Input::get('fileNameArr');
        $fileMimeTypeArr = Input::get('fileMimeTypeArr');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $appKey = Input::get('appKey');
        $tzStr = Input::get('tzStr');

        $messageIdArr = jsonDecodeArrStringIfRequired($messageIdArr);
        $attachmentIdArr = jsonDecodeArrStringIfRequired($attachmentIdArr);
        $fileNameArr = jsonDecodeArrStringIfRequired($fileNameArr);
        $fileMimeTypeArr = jsonDecodeArrStringIfRequired($fileMimeTypeArr);

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && count($messageIdArr) > 0 && count($attachmentIdArr) > 0 && count($fileNameArr) > 0 && count($fileMimeTypeArr) > 0)
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);

                        $compiledMessageAttachmentDetailArr = array();

                        foreach ($messageIdArr as $messageIndex => $messageId) 
                        {
                            $attachmentId = $attachmentIdArr[$messageIndex];
                            $fileName = $fileNameArr[$messageIndex];
                            $fileMimeType = $fileMimeTypeArr[$messageIndex];

                            $fetchedMessageAttachmentDetails = $cldMailBoxMgmtObj->getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType);

                            $compiledMessageAttachmentDetailArr[$messageIndex] = $fetchedMessageAttachmentDetails;
                        }

                        $response['messageAttachmentDetailArr'] = $compiledMessageAttachmentDetailArr;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function loadMultiRelevantMailBoxMessageCompleteDetails()
    {
        set_time_limit(0);

        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $messageIdArr = Input::get('messageIdArr');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $appKey = Input::get('appKey');
        $tzStr = Input::get('tzStr');

        $messageIdArr = jsonDecodeArrStringIfRequired($messageIdArr);

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && count($messageIdArr) > 0)
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);

                        $compiledMessageDetailArr = array();

                        foreach ($messageIdArr as $messageIndex => $messageId) 
                        {
                            $fetchedMessageDetailResponse = $cldMailBoxMgmtObj->getMailBoxMessageDetails($messageId);

                            if(isset($fetchedMessageDetailResponse) && isset($fetchedMessageDetailResponse['messageDetails']))
                            {
                                $fetchedMessageDetails = $fetchedMessageDetailResponse['messageDetails'];

                                if($fetchedMessageDetails['attachmentCount'] > 0)
                                {
                                    $compiledMessageAttachmentDetailArr = array();
                                    foreach ($fetchedMessageDetails['attachments'] as $attachmentIndex => $attachmentObj) 
                                    {
                                        $attachmentId = $attachmentObj['attachmentId'];
                                        $fileName = $attachmentObj['fileName'];
                                        $fileMimeType = $attachmentObj['fileMimeType'];

                                        $fetchedMessageAttachmentDetails = $cldMailBoxMgmtObj->getMailBoxMessageAttachmentDetails($messageId, $attachmentId, $fileName, $fileMimeType);
                                        
                                        $compiledMessageAttachmentDetailArr[$attachmentIndex] = $fetchedMessageAttachmentDetails;
                                    }
                                    $fetchedMessageDetails['attachments'] = $compiledMessageAttachmentDetailArr;
                                }

                                $compiledMessageDetailArr[$messageIndex] = $fetchedMessageDetails;
                            }

                        }

                        $response['messageDetailArr'] = $compiledMessageDetailArr;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function checkRelevantMessageCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $messageId = Input::get('messageId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $messageId != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $messageResponse = $cldMailBoxMgmtObj->checkMessageCanBeDeleted($messageId);
                            
                        $response['messageResponse'] = $messageResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function removeRelevantMessage()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $messageId = Input::get('messageId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $messageId != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $messageResponse = $cldMailBoxMgmtObj->performMessageDelete($messageId);
                            
                        $response['messageResponse'] = $messageResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $folderId = Input::get('folderId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $folderId != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $folderResponse = $cldMailBoxMgmtObj->checkFolderCanBeDeleted($folderId);
                            
                        $response['folderResponse'] = $folderResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function addNewMessage()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $appKey = Input::get('appKey');
        $snippet = Input::get('snippet');
        $sendToEmail = Input::get('sendToEmail');
        $sendCcEmail = Input::get('sendCcEmail');
        $sendBccEmail = Input::get('sendBccEmail');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $snippet != "" && ($sendToEmail != "" || $sendCcEmail != "" || $sendBccEmail != ""))
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $sanSendToEmailStr = "";

                        $sendToEmail = preg_replace('/\s+/', '', $sendToEmail);
                        $sendToEmailArr = explode(",", $sendToEmail);
                        foreach($sendToEmailArr as $email)
                        {
                            if(isValidEmail($email))
                            {
                                $sanSendToEmailStr .= $sanSendToEmailStr != "" ? "," : "";
                                $sanSendToEmailStr .= $email;
                            }
                        }

                        $sanSendCcEmailStr = "";

                        $sendCcEmail = preg_replace('/\s+/', '', $sendCcEmail);
                        $sendCcEmailArr = explode(",", $sendCcEmail);
                        foreach($sendCcEmailArr as $email)
                        {
                            if(isValidEmail($email))
                            {
                                $sanSendCcEmailStr .= $sanSendCcEmailStr != "" ? "," : "";
                                $sanSendCcEmailStr .= $email;
                            }
                        }

                        $sanSendBccEmailStr = "";

                        $sendBccEmail = preg_replace('/\s+/', '', $sendBccEmail);
                        $sendBccEmailArr = explode(",", $sendBccEmail);
                        foreach($sendBccEmailArr as $email)
                        {
                            if(isValidEmail($email))
                            {
                                $sanSendBccEmailStr .= $sanSendBccEmailStr != "" ? "," : "";
                                $sanSendBccEmailStr .= $email;
                            }
                        }

                        if($sanSendToEmailStr != "" || $sanSendCcEmailStr != "" || $sanSendBccEmailStr != "")
                        {
                            $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                            $cldMailBoxMgmtObj->withAppKey($appKey);
                            $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                            $messageResponse = $cldMailBoxMgmtObj->addNewMessage($subject, $snippet, $sanSendToEmailStr, $sanSendCcEmailStr, $sanSendBccEmailStr);
                                
                            $response['messageResponse'] = $messageResponse;
                        }
                        else
                        {
                            $status = -1;
                            $msg = "Invalid Recipient(s)"; 
                        }                            
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function updateExistingMessage()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $messageId = Input::get('messageId');
        $appKey = Input::get('appKey');
        $startTs = Input::get('startTs');
        $endTs = Input::get('endTs');
        $summary = Input::get('summary');
        $description = Input::get('description');
        $tzOfs = Input::get('tzOfs');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "" && $messageId != "" && $summary != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);
                    $mailBoxId = $depMgmtObj->getAppuserMailBoxIdForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $cldMailBoxMgmtObj = New CloudMailBoxManagementClass;
                        $cldMailBoxMgmtObj->withAppKey($appKey);
                        $cldMailBoxMgmtObj->withAccessTokenAndMailBoxTypeCode($accessToken, $mailBoxId, $cloudMailBoxTypeCode);
                        $messageResponse = $cldMailBoxMgmtObj->updateExistingMessage($tzOfs, $messageId, $startTs, $endTs, $summary, $description);
                            
                        $response['messageResponse'] = $messageResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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
    public function performCloudMailBoxMessageImportAsContent()
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
        $fileMailBoxUrlArr = Input::get('fileMailBoxUrlArr');
        $cloudFileThumbStrArr = Input::get('cloudFileThumbStrArr');
        $cloudMailBoxTypeIdArr = Input::get('cloudMailBoxTypeIdArr');
        $cloudMailBoxTypeCodeArr = Input::get('cloudMailBoxTypeCodeArr');
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

        if(!isset($fileMailBoxUrlArr) || !is_array($fileMailBoxUrlArr))
        {
            if(isset($fileMailBoxUrlArr))
            {
                $fileMailBoxUrlArr = json_decode($fileMailBoxUrlArr);
            }
            else
            {
                $fileMailBoxUrlArr = array();
            }
        }

        if(!isset($cloudMailBoxTypeIdArr) || !is_array($cloudMailBoxTypeIdArr))
        {
            if(isset($cloudMailBoxTypeIdArr))
            {
                $cloudMailBoxTypeIdArr = json_decode($cloudMailBoxTypeIdArr);
            }
            else
            {
                $cloudMailBoxTypeIdArr = array();
            }
        }

        if(!isset($cloudMailBoxTypeCodeArr) || !is_array($cloudMailBoxTypeCodeArr))
        {
            if(isset($cloudMailBoxTypeCodeArr))
            {
                $cloudMailBoxTypeCodeArr = json_decode($cloudMailBoxTypeCodeArr);
            }
            else
            {
                $cloudMailBoxTypeCodeArr = array();
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
                        $cloudMailBoxTypeId = $cloudMailBoxTypeIdArr[$fileIndex];
                        $cloudFileUrl = $fileMailBoxUrlArr[$fileIndex];

                        $cloudMailBoxType = $depMgmtObj->getCloudMailBoxTypeObjectById($cloudMailBoxTypeId);

                        if(isset($cloudMailBoxType))
                        {
                            $cloudMailBoxTypeName = $cloudMailBoxType->cloud_mail_box_type_name;

                            $cloudMailBoxTypeNameTagId = 0;

                            $tagWithCloudMailBoxTypeName = $depMgmtObj->getTagObjectByName($cloudMailBoxTypeName);
                            if(!isset($tagWithCloudMailBoxTypeName))
                            {
                                $tagResponse = $depMgmtObj->addEditTag(0, $cloudMailBoxTypeName);
                                $cloudMailBoxTypeNameTagId = $tagResponse['syncId'];
                            }
                            else
                            {
                                $cloudMailBoxTypeNameTagId = $orgId > 0 ? $tagWithCloudMailBoxTypeName->employee_tag_id : $tagWithCloudMailBoxTypeName->appuser_tag_id;
                            }
                            
                            $tagsArr = array();
                            if($cloudMailBoxTypeNameTagId > 0)
                            {
                                array_push($tagsArr, $cloudMailBoxTypeNameTagId);
                            }

                            $filePathStr = "";
                            if(isset($fileBaseFolder) && trim($fileBaseFolder) != "")
                            {
                                $fileBaseFolder = trim($fileBaseFolder);

                                $filePathStr = "<br>Folder: ".$fileBaseFolder;
                            }

                            $fileSourceStr = "";
                            if(isset($cloudMailBoxTypeName) && trim($cloudMailBoxTypeName) != "")
                            {
                                $fileSourceStr = "<br>Source: ".$cloudMailBoxTypeName;
                            }

                            $compContent = $fileName.$filePathStr.$fileSourceStr;

                            $compResponse['compContent_'.$fileIndex] = $compContent;
                            $compResponse['cloudMailBoxTypeName_'.$fileIndex] = $cloudMailBoxTypeName;
                            $compResponse['fileSize_'.$fileIndex] = $fileSize;
                            $compResponse['fileName_'.$fileIndex] = $fileName;
                            $compResponse['cloudFileId_'.$fileIndex] = $cloudFileId;
                            $compResponse['cloudMailBoxTypeNameTagId_'.$fileIndex] = $cloudMailBoxTypeNameTagId;

                            $contentResponse = $depMgmtObj->addEditContent(0, $compContent, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $removeAttachmentIdArr, NULL);

                            $newServerContentId = $contentResponse['syncId'];   
                            
                            $compResponse['newServerContentId_'.$fileIndex] = $newServerContentId;

                            if($newServerContentId > 0)
                            {
                                $serverFileName = '';
                                $attachmentResponse = $depMgmtObj->addEditContentAttachment(0, $newServerContentId, $fileName, $serverFileName, $fileSize, $cloudMailBoxTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $createTimeStamp);

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

    public function loadCloudMailBoxTypeAuthenticationDependencies()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudMailBoxTypeCode = Input::get('cloudMailBoxType');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudMailBoxTypeCode != "")
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

                $cloudMailBoxTypeId = $depMgmtObj->getCloudMailBoxTypeIdFromCode($cloudMailBoxTypeCode);
                if(isset($cloudMailBoxTypeId) && $cloudMailBoxTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForMailBoxType($cloudMailBoxTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = -1;
                        $msg = "Account already linked";
                    }
                    else
                    {
                        if($cloudMailBoxTypeCode == CloudMailBoxType::$DROPBOX_TYPE_CODE)
                        {
                            $status = 1;

                            $clientId = env('DROPBOX_APP_KEY');
                            $redirectUri = env('DROPBOX_REDIRECT_URI');

                            $authUrl = "https://www.dropbox.com/1/oauth2/authorize?response_type=code&client_id=".$clientId."&redirect_uri=".$redirectUri;

                            $response['authUrl'] = $authUrl;
                        }
                        // else if($cloudMailBoxTypeCode == CloudMailBoxType::$DROPBOX_TYPE_CODE)
                        // {
                        //     $status = 1;
                        //     $response['redirectUri'] = env('DROPBOX_REDIRECT_URI');
                        // }
                        // else if($cloudMailBoxTypeCode == CloudMailBoxType::$DROPBOX_TYPE_CODE)
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
                    $msg = Config::get('app_config_notif.err_invalid_cloud_mailBox_type'); 
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