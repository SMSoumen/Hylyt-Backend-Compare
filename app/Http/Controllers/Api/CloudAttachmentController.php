<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\CloudStorageType;
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
use Storage;
use View;
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Log;
use App\Libraries\AttachmentCloudStorageManagementClass;

class CloudAttachmentController extends Controller
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
    public function loadRelevantFolderFileList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $queryStr = Input::get('queryStr');
        $baseFolderTypeId = Input::get('baseFolderType');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fetchedFolderList = $attCldStrgMgmtObj->getAllFoldersAndFiles($parentFolderName, $queryStr, $baseFolderTypeId);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['folderResponse'] = $fetchedFolderList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $attachmentView = $_viewToRender->render();

                            $response['folderView'] = $attachmentView;
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                        else
                        {
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function loadRelevantFolderFileContinuedList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $queryStr = Input::get('queryStr');
        $cursorStr = Input::get('cursorStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "" && $cursorStr != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fetchedFolderList = $attCldStrgMgmtObj->getContinuedFoldersAndFiles($parentFolderName, $queryStr, $cursorStr);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['folderResponse'] = $fetchedFolderList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = FALSE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $attachmentView = $_viewToRender->render();

                            $response['folderView'] = $attachmentView;
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                        else
                        {
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function loadRelevantFolderFileFilteredList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $queryStr = Input::get('queryStr');
        $baseFolderTypeId = Input::get('baseFolderType');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")// && $queryStr != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fetchedFolderList = $attCldStrgMgmtObj->getFilteredFoldersAndFiles($parentFolderName, $queryStr, $baseFolderTypeId, FALSE);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['folderResponse'] = $fetchedFolderList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = TRUE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $attachmentView = $_viewToRender->render();

                            $response['folderView'] = $attachmentView;
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                        else
                        {
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function loadRelevantFolderFileContinuedFilteredList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $queryStr = Input::get('queryStr');
        $cursorStr = Input::get('cursorStr');
        $renderView = Input::get('renderView');
        $appKey = Input::get('appKey');
        $isGeneralExhaustedFlag = Input::get('isGeneralExhausted');

        $isGeneralExhausted = FALSE;
        if($isGeneralExhaustedFlag == 1)
        {
            $isGeneralExhausted = TRUE;
        }

        if(!isset($renderView) || $renderView != 1)
        {
            $renderView = 0;
        }

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "" && $cursorStr != "")// && $queryStr != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fetchedFolderList = $attCldStrgMgmtObj->getContinuedFilteredFoldersAndFiles($parentFolderName, $queryStr, $cursorStr, NULL, $isGeneralExhausted);

                        if($renderView == 1)
                        {
                            $viewDetails = array();
                            $viewDetails['folderResponse'] = $fetchedFolderList;
                            $viewDetails['queryStr'] = $queryStr;
                            $viewDetails['isPrimaryListLoad'] = FALSE;

                            $_viewToRender = View::make('cloudAttachment.partialview._attachmentSubView', $viewDetails);
                            $attachmentView = $_viewToRender->render();

                            $response['folderView'] = $attachmentView;
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                        else
                        {
                            $response['folderResponse'] = $fetchedFolderList;
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function loadRelevantSelectedFileMappedDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $fileIdArr = Input::get('fileIdArr');
        $fileSizeArr = Input::get('fileSizeArr');
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

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fetchedFileList = $attCldStrgMgmtObj->getSelectedFileMappedDetailsArr($fileIdArr, $fileSizeArr);
                            
                        $response = $fetchedFileList;
                        // $response['fileIdArr'] = $fileIdArr;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function uploadRelevantFile(Request $request)
    {
    	set_time_limit(0);
    	
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $appKey = Input::get('appKey');

        Log::info('encUserId : '.$encUserId);
        Log::info('encOrgId : '.$encOrgId);
        Log::info('loginToken : '.$loginToken);
        Log::info('cloudStorageTypeCode : '.$cloudStorageTypeCode);
        Log::info('parentFolderName : '.$parentFolderName);
        Log::info('appKey : '.$appKey);

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        Log::info('accessToken validated : ');
                        if($request->hasFile('uplFile'))
                        {
                            Log::info('file validated : ');
                            if(isset($parentFolderName))
                            {
                                Log::info('parentFolderName validated : ');
                                $fileObj = $request->file('uplFile');
                                $fileMimeType = $fileObj->getMimeType();
                                $fileSize = $fileObj->getSize();
                                $fileName = $fileObj->getClientOriginalName();

                                $validFileMimeTypeArr = Config::get('app_config_cloud_storage.cloud_storage_file_valid_mimetype_arr');
                                $validFileSize = Config::get('app_config_cloud_storage.cloud_storage_file_valid_size');

                                if(in_array($fileMimeType, $validFileMimeTypeArr) && $fileSize <= $validFileSize) 
                                {
                                    Log::info('fileMimeType + fileSize validated : fileSize : '.$fileSize.' : fileMimeType : '.$fileMimeType);
                                    $status = 1;
                                    $msg = "";

                                    $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                                    $attCldStrgMgmtObj->withAppKey($appKey);
                                    $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                                    $uploadedFileDetails = $attCldStrgMgmtObj->uploadFile($parentFolderName, $fileObj);
                                        
                                    $response['fileResponse'] = $uploadedFileDetails;
                                }
                                else if(!in_array($fileMimeType, $validFileMimeTypeArr))
                                {
                                    $status = -1;
                                    $msg = 'Invalid file type';
                                }
                                else
                                {
                                    $status = -1;
                                    $msg = 'Max file size exceeded';
                                }
                            }
                            else
                            {
                                $status = -1;
                                $msg = Config::get('app_config_notif.err_invalid_data').'3';
                            }
                        }
                        else
                        {
                            $status = -1;
                            $msg = Config::get('app_config_notif.err_invalid_data').'2';
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    } 
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
            $msg = Config::get('app_config_notif.err_invalid_data').'1';
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
    public function checkRelevantFileCanBeDeleted()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $fileId = Input::get('fileId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fileResponse = $attCldStrgMgmtObj->checkFileCanBeDeleted($fileId);
                            
                        $response['fileResponse'] = $fileResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function removeRelevantFile()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $fileId = Input::get('fileId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $fileResponse = $attCldStrgMgmtObj->performFileDelete($fileId);
                            
                        $response['fileResponse'] = $fileResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $folderId = Input::get('folderId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "" && $folderId != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $folderResponse = $attCldStrgMgmtObj->checkFolderCanBeDeleted($folderId);
                            
                        $response['folderResponse'] = $folderResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function removeRelevantFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $folderId = Input::get('folderId');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "" && $folderId != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $folderResponse = $attCldStrgMgmtObj->performFolderDelete($folderId);
                            
                        $response['folderResponse'] = $folderResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function addNewRelevantFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $parentFolderName = Input::get('parentFolderName');
        $folderName = Input::get('folderName');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = 1;
                        $msg = "";

                        $attCldStrgMgmtObj = New AttachmentCloudStorageManagementClass;
                        $attCldStrgMgmtObj->withAppKey($appKey);
                        $attCldStrgMgmtObj->withAccessTokenAndStorageTypeCode($accessToken, $cloudStorageTypeCode);
                        $folderResponse = $attCldStrgMgmtObj->addNewFolder($parentFolderName, $folderName);
                            
                        $response['folderResponse'] = $folderResponse;
                    }
                    else
                    {
                        $status = -1;
                        $msg = Config::get('app_config_notif.err_invalid_cloud_storage_access_token'); 
                    }                        
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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
    public function performCloudStorageAttachmentImportAsContent()
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
        $fileStorageUrlArr = Input::get('fileStorageUrlArr');
        $cloudFileThumbStrArr = Input::get('cloudFileThumbStrArr');
        $cloudStorageTypeIdArr = Input::get('cloudStorageTypeIdArr');
        $cloudStorageTypeCodeArr = Input::get('cloudStorageTypeCodeArr');
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

        if(!isset($fileStorageUrlArr) || !is_array($fileStorageUrlArr))
        {
            if(isset($fileStorageUrlArr))
            {
                $fileStorageUrlArr = json_decode($fileStorageUrlArr);
            }
            else
            {
                $fileStorageUrlArr = array();
            }
        }

        if(!isset($cloudStorageTypeIdArr) || !is_array($cloudStorageTypeIdArr))
        {
            if(isset($cloudStorageTypeIdArr))
            {
                $cloudStorageTypeIdArr = json_decode($cloudStorageTypeIdArr);
            }
            else
            {
                $cloudStorageTypeIdArr = array();
            }
        }

        if(!isset($cloudStorageTypeCodeArr) || !is_array($cloudStorageTypeCodeArr))
        {
            if(isset($cloudStorageTypeCodeArr))
            {
                $cloudStorageTypeCodeArr = json_decode($cloudStorageTypeCodeArr);
            }
            else
            {
                $cloudStorageTypeCodeArr = array();
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
                    $isCompleted = Config::get('app_config.default_content_is_completed_status');
                    $isSnoozed = Config::get('app_config.default_content_is_snoozed_status');

                    foreach ($fileIdArr as $fileIndex => $cloudFileId) 
                    {
                        $fileBaseFolder = $fileBaseFolderArr[$fileIndex];
                        $fileName = $fileNameArr[$fileIndex];
                        $fileSize = $fileSizeArr[$fileIndex];
                        $cloudFileThumbStr = $cloudFileThumbStrArr[$fileIndex];
                        $cloudStorageTypeId = $cloudStorageTypeIdArr[$fileIndex];
                        $cloudFileUrl = $fileStorageUrlArr[$fileIndex];

                        $cloudStorageType = $depMgmtObj->getCloudStorageTypeObjectById($cloudStorageTypeId);

                        if(isset($cloudStorageType))
                        {
                            $cloudStorageTypeName = $cloudStorageType->cloud_storage_type_name;

                            $cloudStorageTypeNameTagId = 0;

                            $tagWithCloudStorageTypeName = $depMgmtObj->getTagObjectByName($cloudStorageTypeName);
                            if(!isset($tagWithCloudStorageTypeName))
                            {
                                $tagResponse = $depMgmtObj->addEditTag(0, $cloudStorageTypeName);
                                $cloudStorageTypeNameTagId = $tagResponse['syncId'];
                            }
                            else
                            {
                                $cloudStorageTypeNameTagId = $orgId > 0 ? $tagWithCloudStorageTypeName->employee_tag_id : $tagWithCloudStorageTypeName->appuser_tag_id;
                            }
                            
                            $tagsArr = array();
                            if($cloudStorageTypeNameTagId > 0)
                            {
                                array_push($tagsArr, $cloudStorageTypeNameTagId);
                            }

                            $filePathStr = "";
                            if(isset($fileBaseFolder) && trim($fileBaseFolder) != "")
                            {
                                $fileBaseFolder = trim($fileBaseFolder);

                                $filePathStr = "<br>Folder: ".$fileBaseFolder;
                            }

                            $fileSourceStr = "";
                            if(isset($cloudStorageTypeName) && trim($cloudStorageTypeName) != "")
                            {
                                $fileSourceStr = "<br>Source: ".$cloudStorageTypeName;
                            }

                            $compContent = $fileName.$filePathStr.$fileSourceStr;

                            $compResponse['compContent_'.$fileIndex] = $compContent;
                            $compResponse['cloudStorageTypeName_'.$fileIndex] = $cloudStorageTypeName;
                            $compResponse['fileSize_'.$fileIndex] = $fileSize;
                            $compResponse['fileName_'.$fileIndex] = $fileName;
                            $compResponse['cloudFileId_'.$fileIndex] = $cloudFileId;
                            $compResponse['cloudStorageTypeNameTagId_'.$fileIndex] = $cloudStorageTypeNameTagId;

                            $contentResponse = $depMgmtObj->addEditContent(0, $compContent, $contentType, $defFolderId, $sourceId, $tagsArr, $isMarked, $createTimeStamp, $updateTimeStamp, $fromTimeStamp, $toTimeStamp, $colorCode, $isLocked, $isShareEnabled, $remindBeforeMillis, $repeatDuration, $isCompleted, $isSnoozed, $removeAttachmentIdArr, NULL);

                            $newServerContentId = $contentResponse['syncId'];   
                            
                            $compResponse['newServerContentId_'.$fileIndex] = $newServerContentId;

                            if($newServerContentId > 0)
                            {
                                $serverFileName = '';
                                $attachmentResponse = $depMgmtObj->addEditContentAttachment(0, $newServerContentId, $fileName, $serverFileName, $fileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $createTimeStamp, $createTimeStamp);

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

    public function loadCloudStorageTypeAuthenticationDependencies()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $cloudStorageTypeCode = Input::get('cloudStorageType');
        $appKey = Input::get('appKey');

        $response = array();

        if($encUserId != "" && $cloudStorageTypeCode != "")
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

                $cloudStorageTypeId = $depMgmtObj->getCloudStorageTypeIdFromCode($cloudStorageTypeCode);
                if(isset($cloudStorageTypeId) && $cloudStorageTypeId > 0)
                {
                    $accessToken = $depMgmtObj->getAppuserAccessTokenForStorageType($cloudStorageTypeId);

                    if(isset($accessToken) && $accessToken != "")
                    {
                        $status = -1;
                        $msg = "Account already linked";
                    }
                    else
                    {
                        if($cloudStorageTypeCode == CloudStorageType::$DROPBOX_TYPE_CODE)
                        {
                            $status = 1;

                            $clientId = env('DROPBOX_APP_KEY');
                            $redirectUri = env('DROPBOX_REDIRECT_URI');

                            $authUrl = "https://www.dropbox.com/1/oauth2/authorize?response_type=code&client_id=".$clientId."&redirect_uri=".$redirectUri;

                            $response['authUrl'] = $authUrl;
                        }
                        // else if($cloudStorageTypeCode == CloudStorageType::$DROPBOX_TYPE_CODE)
                        // {
                        //     $status = 1;
                        //     $response['redirectUri'] = env('DROPBOX_REDIRECT_URI');
                        // }
                        // else if($cloudStorageTypeCode == CloudStorageType::$DROPBOX_TYPE_CODE)
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
                    $msg = Config::get('app_config_notif.err_invalid_cloud_storage_type'); 
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