<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\FolderType;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserFolder;
use App\Models\Api\AppuserContent;
use App\Models\Org\Api\OrgEmployeeFolder;
use App\Models\Org\Api\OrgEmployeeContent;
use App\Models\Org\OrganizationUser;
use App\Models\Api\AppuserConstant;
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
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\FolderFilterUtilClass;
use DB;
use View;
use Illuminate\Support\Facades\Log;

class FolderController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    /**
     * Add Folder.
     *
     * @return json array
     */
    public function saveFolderDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = Input::get('id');
        $name = Input::get('name');
        $iconCode = Input::get('iconCode');
        $folderTypeId = Input::get('folderType');
        $appliedFilters = Input::get('appliedFilters');
        $isFavorited = Input::get('isFavorited');
        $loginToken = Input::get('loginToken');

        if(!isset($folderTypeId))
        {
            $folderTypeId = 0;
        }

        $response = array();

        if($encUserId != "" && $name != "")
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
				           
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $response = $depMgmtObj->addEditFolder($id, $name, $iconCode, $isFavorited, $folderTypeId, $appliedFilters);
                $response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
                	
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
     * Add Folder.
     *
     * @return json array
     */
    public function saveVirtualFolderDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = Input::get('id');
        $name = Input::get('name');
        $iconCode = Input::get('iconCode');
        $isFavorited = Input::get('isFavorited');

        $filFolderArr = Input::get('filFolderArr');
        $filGroupArr = Input::get('filGroupArr');
        $filSourceArr = Input::get('filSourceArr');
        $filTypeArr = Input::get('filTypeArr');
        $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
        $filTagArr = Input::get('filTagArr');
        $filFromDate = Input::get('fromTimeStamp');
        $filToDate = Input::get('toTimeStamp');  
        $chkIsUnread = Input::get('chkIsUnread');  
        $chkIsStarred = Input::get('chkIsStarred');  
        $chkIsUntagged = Input::get('chkIsUntagged');
        $chkIsLocked = Input::get('chkIsLocked');
        $chkIsConversation = Input::get('chkIsConversation');
        $chkIsRestricted = Input::get('chkIsRestricted');
        $chkShowFolder = Input::get('chkShowFolder');
        $chkShowGroup = Input::get('chkShowGroup');
        //$chkDownloadStatus = Input::get('chkDownloadStatus');
        $filShowAttachment = Input::get('filShowAttachment');
        $filSenderEmail = Input::get('filSenderEmail');
        $filDateDayCount = Input::get('filDateDayCount'); 
        $filDateFilterType = Input::get('filDateFilterType');  

        // Log::info('filSenderEmail : '.$filSenderEmail);

        $response = array();

        if($encUserId != "" && $name != "")
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
                $filFolderArr = sracDecryptNumberArrayData($filFolderArr, $userSession);
                $filGroupArr = sracDecryptNumberArrayData($filGroupArr, $userSession);
                $filSourceArr = sracDecryptNumberArrayData($filSourceArr, $userSession);
                $filTagArr = sracDecryptNumberArrayData($filTagArr, $userSession);
				           
                $status = 1;
                $msg = "";
                
                $dateFilterTypeIdNone = Config::get('app_config.filter_date_type_id_none'); 
                $dateFilterTypeIdDateRange = Config::get('app_config.filter_date_type_id_date_range'); 
                $dateFilterTypeIdDayCount = Config::get('app_config.filter_date_type_id_day_count'); 

                if($filDateFilterType == $dateFilterTypeIdNone)
                {
                    $filFromDate = '';
                    $filToDate = '';
                    $filDateDayCount = '';
                }
                else if($filDateFilterType == $dateFilterTypeIdDateRange)
                {
                    $filDateDayCount = '';
                }
                else if($filDateFilterType == $dateFilterTypeIdDayCount)
                {
                    $filFromDate = '';
                    $filToDate = '';
                }

                $consEncFilterStr = "";

                $folderFilterUtilObj = New FolderFilterUtilClass;
                $folderFilterUtilObj->setFilterStr($consEncFilterStr);

                $folderFilterUtilObj->setFilterValueIsConversation($chkIsConversation);
                $folderFilterUtilObj->setFilterValueIsUntagged($chkIsUntagged);
                $folderFilterUtilObj->setFilterValueIsStarred($chkIsStarred);
                $folderFilterUtilObj->setFilterValueIsUnread($chkIsUnread);
                $folderFilterUtilObj->setFilterValueIsLocked($chkIsLocked);
                $folderFilterUtilObj->setFilterValueIsRestricted($chkIsRestricted);
                $folderFilterUtilObj->setFilterValueIsShowFolder($chkShowFolder);
                $folderFilterUtilObj->setFilterValueIsShowGroup($chkShowGroup);
                $folderFilterUtilObj->setFilterValueAttachmentStatus($filShowAttachment);
                $folderFilterUtilObj->setFilterValueSenderEmail($filSenderEmail);

                $filDownloadStatus = -1;
                $folderFilterUtilObj->setFilterValueDownloadStatus($filDownloadStatus);

                // $filDateFilterType = -1;
                // if(isset($filFromDate) && $filFromDate != '' && is_numeric($filFromDate))
                // {
                //     $filDateFilterType = 0;
                // }
                // else if(isset($filToDate) && $filToDate != '' && is_numeric($filToDate))
                // {
                //     $filDateFilterType = 0;
                // }

                $folderFilterUtilObj->setFilterValueDateFilterType($filDateFilterType);
                $folderFilterUtilObj->setFilterValueStartDateTs($filFromDate);
                $folderFilterUtilObj->setFilterValueEndDateTs($filToDate);
                $folderFilterUtilObj->setFilterValueDateFilterTypeDayCount($filDateDayCount);

                $folderFilterUtilObj->setFilterValueContentType($filTypeArr);
                $folderFilterUtilObj->setFilterValueFolder($filFolderArr);
                $folderFilterUtilObj->setFilterValueGroup($filGroupArr);
                $folderFilterUtilObj->setFilterValueSource($filSourceArr);
                $folderFilterUtilObj->setFilterValueTag($filTagArr);
                $folderFilterUtilObj->setFilterValueAttachmentType($filAttachmentTypeArr);

                $updEncFilterStr = $folderFilterUtilObj->compileFilterStr();

                // Log::info('updEncFilterStr : ' . $updEncFilterStr);

                $virtualFolderTypeId = FolderType::$TYPE_VIRTUAL_FOLDER_ID;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $response = $depMgmtObj->addEditFolder($id, $name, $iconCode, $isFavorited, $virtualFolderTypeId, $updEncFilterStr);
                $response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
                	
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
     * Folder List.
     *
     * @return json array
     */
    public function folderGroupListView()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isLockedFlag = Input::get('isLocked');
        $listCode = Input::get('listCode');
        $searchStr = Input::get('searchStr');
        $selIsFolder = Input::get('selIsFolder');
        $selFOrGId = Input::get('selFOrGId');
        $selOrgId = Input::get('selOrgId');

        $hasPreloadSelection = Input::get('hasPreloadSelection');
        $preloadIsFolder = Input::get('preloadIsFolder');
        $preloadFOrGId = Input::get('preloadFOrGId');
        
        if(isset($searchStr) && trim($searchStr) != "") {
            $searchStr = strtolower(trim($searchStr));
        }
        else {
            $searchStr = '';
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

                $validContentListCodeArr = Config::get('app_config.validContentListCodeArr');
                $contentListCodeHasFolderGroupArr = Config::get('app_config.contentListCodeHasFolderGroupArr');
                $listHasFolderGroup = isset($contentListCodeHasFolderGroupArr[$listCode]) ? $contentListCodeHasFolderGroupArr[$listCode] : 0;    

                if(isset($listCode) && in_array($listCode, $validContentListCodeArr) && $listHasFolderGroup == 1)
                {
                    $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');
                    $listCodeAllNotes = Config::get('app_config.dashMetricAllNotesCode');
                    $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
                    $listCodeTrashFolder = Config::get('app_config.dashMetricTrashFolderCode');
                    $listCodeSentFolder = Config::get('app_config.dashMetricSentFolderCode');

                    $depMgmtObj = New ContentDependencyManagementClass;
                    $depMgmtObj->withOrgKey($user, $encOrgId);
                    $depMgmtObj->setCurrentLoginToken($loginToken);

                    $userOrganizations = array();
                    if($listCode == $listCodeAllNotes)
                    {
                        $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();
                    }
                    $lastUserOrgIndex = count($userOrganizations) > 0 ? count($userOrganizations) - 1 : 0;

                    $folderList = array();
                    if($listCode == $listCodeFavFolders || $listCode == $listCodeConversationNotes || $listCode == $listCodeAllNotes)
                    {
                        $folderList = $this->getRelevantFolderList($depMgmtObj, $isLockedFlag, $listCode);
                    }

                    $groupList = array();
                    if($listCode == $listCodeConversationNotes || $listCode == $listCodeFavFolders || $listCode == $listCodeAllNotes) // 
                    {
                        $groupList = $this->getRelevantGroupList($depMgmtObj, $listCode);
                    }

                    $status = 1;

                    $resCnt = count($folderList); 
                    if($resCnt == 0)
                        $msg = Config::get('app_config_notif.inf_no_folder_found');
                
                    $folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));
                    
                    $defFolderIconCode = Config::get('app_config.default_folder_icon_code');
                    $defIconCode = $folderIconBasePath.'/'.$defFolderIconCode.'.png';

                    $webIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.web_icon_base_path'));

                    $activeFolderIconUrl = $webIconBasePath.'/'.Config::get('app_config.web_active_folder_icon_path');
                    $inactiveFolderIconUrl = $webIconBasePath.'/'.Config::get('app_config.web_inactive_folder_icon_path');

                    $trashObj = null;
                    $sentObj = null;
                    if($listCode == $listCodeAllNotes || $listCode == $listCodeConversationNotes)
                    {
                        $trashFolderList = $this->getRelevantFolderList($depMgmtObj, $isLockedFlag, $listCodeTrashFolder);
                        if(isset($trashFolderList) && count($trashFolderList) > 0)
                        {
                            $trashObj = $trashFolderList[0]; 
                        }

                        if($listCode == $listCodeConversationNotes)
                        {
                            $sentFolderList = $this->getRelevantFolderList($depMgmtObj, $isLockedFlag, $listCodeSentFolder);
                            if(isset($sentFolderList) && count($sentFolderList) > 0)
                            {
                                $sentObj = $sentFolderList[0]; 
                            }
                        }
                    }

                    if(isset($searchStr) && $searchStr != "")
                    {
                        $filteredFolderList = array();
                        foreach ($folderList as $folderObj) 
                        {       
                            $consFolderId = $folderObj['id'];
                            $consFolderName = $folderObj['name'];
                            $consFolderOrgKey = $folderObj['orgKey'];

                            $addFolder = FALSE;   
                            // if($selFOrGId > 0)
                            {
                                if($selIsFolder == 1 && $selFOrGId == $consFolderId && $selOrgId == $consFolderOrgKey)
                                {
                                    $addFolder = TRUE;                                    
                                }
                            }       

                            if (!$addFolder && strpos(strtolower($consFolderName), $searchStr) !== false)
                            {
                                $addFolder = TRUE;
                            }

                            if($addFolder)
                            {
                                array_push($filteredFolderList, $folderObj);
                            }
                        }

                        $folderList = $filteredFolderList;

                        $filteredGroupList = array();
                        foreach ($groupList as $groupObj) 
                        {       
                            $consGroupId = $groupObj['id'];
                            $consGroupName = $groupObj['name'];
                            $consGroupOrgKey = $groupObj['orgKey'];

                            $addGroup = FALSE;   
                            // if($selFOrGId > 0)
                            {
                                if($selIsFolder == 0 && $selFOrGId == $consGroupId && $selOrgId == $consGroupOrgKey)
                                {
                                    $addGroup = TRUE;                                    
                                }
                            }       

                            if (!$addGroup && strpos(strtolower($consGroupName), $searchStr) !== false)
                            {
                                $addGroup = TRUE;
                            }

                            if($addGroup)
                            {
                                array_push($filteredGroupList, $groupObj);
                            }
                        }

                        $groupList = $filteredGroupList;

                        if(isset($sentObj))
                        {       
                            $consFolderId = $sentObj['id'];
                            $consFolderName = $sentObj['name'];
                            $consFolderOrgKey = $sentObj['orgKey'];

                            $addFolder = FALSE;   
                            // if($selFOrGId > 0)
                            {
                                if($selIsFolder == 1 && $selFOrGId == $consFolderId && $selOrgId == $consFolderOrgKey)
                                {
                                    $addFolder = TRUE;                                    
                                }
                            }       

                            if (!$addFolder && strpos(strtolower($consFolderName), $searchStr) !== false)
                            {
                                $addFolder = TRUE;
                            }

                            if(!$addFolder)
                            {
                                $sentObj = NULL;
                            }
                        }

                        if(isset($trashObj))
                        {       
                            $consFolderId = $trashObj['id'];
                            $consFolderName = $trashObj['name'];
                            $consFolderOrgKey = $trashObj['orgKey'];

                            $addFolder = FALSE;   
                            // if($selFOrGId > 0)
                            {
                                if($selIsFolder == 1 && $selFOrGId == $consFolderId && $selOrgId == $consFolderOrgKey)
                                {
                                    $addFolder = TRUE;                                    
                                }
                            }       

                            if (!$addFolder && strpos(strtolower($consFolderName), $searchStr) !== false)
                            {
                                $addFolder = TRUE;
                            }

                            if(!$addFolder)
                            {
                                $trashObj = NULL;
                            }
                        }
                    }

                    $response['folderCnt'] = $resCnt;
                    $response['folderArr'] = $folderList;
                    $response['defIconCode'] = $defIconCode;
                    $response['activeFolderIconUrl'] = $activeFolderIconUrl;
                    $response['inactiveFolderIconUrl'] = $inactiveFolderIconUrl;
                    $response['groupArr'] = $groupList;
                    $response['trashObj'] = $trashObj;
                    $response['sentObj'] = $sentObj;
                    $response['fOrGSearchStr'] = $searchStr;
                    $response['selFOrGId'] = $selFOrGId;
                    $response['selOrgId'] = $selOrgId;
                    $response['selIsFolder'] = $selIsFolder;
                    $response['hasPreloadSelection'] = $hasPreloadSelection;
                    $response['preloadIsFolder'] = $preloadIsFolder;
                    $response['preloadFOrGId'] = $preloadFOrGId;

                    $viewDetails = $response;

                    $_viewToRender = View::make('content.partialview._folderGroupListView', $viewDetails);
                    $folderGroupListView = $_viewToRender->render();

                    $response['folderGroupListView'] = $folderGroupListView;

                    CommonFunctionClass::setLastSyncTs($userId, $loginToken);
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
    
    /**
     * Folder List.
     *
     * @return json array
     */
    public function allNotesFolderGroupId()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

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

                $allNotesFolderId = -1;
                $allNotesEncFOrGId = sracEncryptNumberDataWithSanitization($allNotesFolderId, $userSession);
                
                $status = 1;
                $response['id'] = $allNotesEncFOrGId;
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
     * Folder List.
     *
     * @return json array
     */
    public function folderList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isLockedFlag = Input::get('isLocked');

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

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $listCode = "";
                $folderList = $this->getRelevantFolderList($depMgmtObj, $isLockedFlag, $listCode);

                $status = 1;

                $resCnt = count($folderList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_folder_found');
            
                $folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));
                
                $defFolderIconCode = Config::get('app_config.default_folder_icon_code');
                $defIconCode = $folderIconBasePath.'/'.$defFolderIconCode.'.png';

                $response['folderCnt'] = $resCnt;
                $response['folderArr'] = $folderList;
                $response['defIconCode'] = $defIconCode;
                	
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

    public function getRelevantFolderList($depMgmtObj, $isLockedFlag, $listCode)
    {
        $isFolder = TRUE;

        $userSession = $depMgmtObj->getAppuserSession();
                     
        $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');
        $listCodeAllNotes = Config::get('app_config.dashMetricAllNotesCode');
        $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
        $listCodeTrashFolder = Config::get('app_config.dashMetricTrashFolderCode');
        $listCodeSentFolder = Config::get('app_config.dashMetricSentFolderCode');

        $primOrgKey = "";
        $primOrgIconUrl = "";
        $primOrgId = $depMgmtObj->getOrganizationId();
        if($primOrgId > 0)
        {
            $organization = $depMgmtObj->getOrganizationObject();
            if(isset($organization))
            {
                $primOrgKey = $depMgmtObj->getOrgProfileKey();

                $logoFilename = $organization->logo_filename;
                if(isset($logoFilename) && $logoFilename != "")
                {
                    $primOrgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($primOrgId, $logoFilename);
                }

            }
        }

        $i = 0;
        $folderList = array();
        $folderIdArr = array();
        $arrForSorting = array();

        $allOrgDepMgmtObjArr = array();
        if($listCode == $listCodeFavFolders)
        {
            $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();
            $lastUserOrgIndex = count($userOrganizations) > 0 ? count($userOrganizations) - 1 : 0;

            $user = $depMgmtObj->getUserObject();

            $tmpPerDepMgmtObj = New ContentDependencyManagementClass;
            $tmpPerDepMgmtObj->withUserIdOrgIdAndEmpId($user, 0, 0);
            array_push($allOrgDepMgmtObjArr, $tmpPerDepMgmtObj);

            foreach ($userOrganizations as $userOrgIndex => $userOrg) 
            {
                $orgId = $userOrg->organization_id;                 
                $organization = $userOrg->organization;
                if(isset($organization)) 
                {
                    $empId = $userOrg->emp_id;

                    $tmpOrgDepMgmtObj = New ContentDependencyManagementClass;
                    $tmpOrgDepMgmtObj->withOrgIdAndEmpId($orgId, $empId);

                    $orgEmployee = $tmpOrgDepMgmtObj->getPlainEmployeeObject();
                    if(isset($orgEmployee) && $orgEmployee->is_active == 1)
                    {
                        array_push($allOrgDepMgmtObjArr, $tmpOrgDepMgmtObj);
                    }
                }
            }
        }
        else
        {
            array_push($allOrgDepMgmtObjArr, $depMgmtObj);
        }

        $hasSentFolder = FALSE;
        $sentFolderObj = NULL;

        if($listCode != $listCodeTrashFolder)
        {
            foreach ($allOrgDepMgmtObjArr as $depMgmtIndex => $orgDepMgmtObj)
            {
                $orgId = $orgDepMgmtObj->getOrganizationId();

                $lockedFolderArr = $orgDepMgmtObj->getLockedFolderIdArr();
                // $userSession = $orgDepMgmtObj->getAppuserSession();

                $orgKey = "";
                $orgIconUrl = "";
                if($orgId > 0)
                {
                    $organization = $orgDepMgmtObj->getOrganizationObject();
                    if(isset($organization))
                    {
                        $orgKey = $orgDepMgmtObj->getOrgProfileKey();

                        $logoFilename = $organization->logo_filename;
                        if(isset($logoFilename) && $logoFilename != "")
                        {
                            // $orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
                            $orgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                        }

                    }
                }

                $folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));
                
                $userFoldersModelObj = $orgDepMgmtObj->getAllFoldersModelObj();

                if(isset($userFoldersModelObj))
                {
                    if($listCode == $listCodeFavFolders)
                    {
                        $userFoldersModelObj = $userFoldersModelObj->isFavorited();
                    }
                    else if($listCode == $listCodeAllNotes)
                    {
                        $userFoldersModelObj = $userFoldersModelObj->isNotSentOrVirtualSenderFolder();
                    }
                    else if($listCode == $listCodeConversationNotes)
                    {
                        $userFoldersModelObj = $userFoldersModelObj->isSentOrVirtualSenderFolder()->orderBy('folder_type_id');
                    }
                    $userFolders = $userFoldersModelObj->get();
                }

                foreach ($userFolders as $folder) 
                {
                    if($orgId > 0)                  
                    {
                        $folderId = $folder->employee_folder_id;
                        $idColName = 'employee_contents'.'.employee_content_id';
                        
                    }
                    else
                    {
                        $folderId = $folder->appuser_folder_id;
                        $idColName = 'appuser_contents'.'.appuser_content_id';
                    }   
                    
                    // $folderContents = $orgDepMgmtObj->getAllContentModelObj($isFolder, $folderId);
                    // $folderContents = $folderContents->select(["$idColName as content_id"]);
                    // $folderContents = $folderContents->get();
                        
                    $folderName = $folder->folder_name;
                    $folderIconCode = $folder->icon_code;
                    $folderIsFavorited = $folder->is_favorited;
                    $folderTypeId = $folder->folder_type_id;
                    $folderContentCnt = 0; //count($folderContents);    
                    $folderContentModifiedAt = $folder->content_modified_at;             
                    
                    if(!in_array($folderId, $folderIdArr))
                    {
                        array_push($folderIdArr, $folderId);
                        
                        $folderIsLocked = 0;
                        if(in_array($folderId, $lockedFolderArr))
                        {
                            $folderIsLocked = 1;
                        }

                        $folderIsVirtual = ($folderTypeId == FolderType::$TYPE_VIRTUAL_FOLDER_ID || $folderTypeId == FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID) ? 1 : 0;
                        
                        if($isLockedFlag == 0 || ($isLockedFlag == 1 && $folderIsLocked == 0)) 
                        {
                            $compFolderObj = array();
                            $compFolderObj['id'] = sracEncryptNumberDataWithSanitization($folderId, $userSession);
                            $compFolderObj['name'] = $folderName;
                            $compFolderObj['iconCode'] = $folderIconBasePath.'/'.$folderIconCode.'.png';
                            $compFolderObj['contentCnt'] = $folderContentCnt;
                            $compFolderObj['isLocked'] = $folderIsLocked;
                            $compFolderObj['isFavorited'] = $folderIsFavorited;
                            $compFolderObj['folderType'] = $folderTypeId;
                            $compFolderObj['orgKey'] = $orgKey;
                            $compFolderObj['orgIconUrl'] = $orgIconUrl;
                            $compFolderObj['isVirtual'] = $folderIsVirtual;

                            if($folderTypeId != FolderType::$TYPE_SENT_FOLDER_ID)
                            {
                                $folderList[$i] = $compFolderObj;

                                $arrForSorting[$i] = $folderContentModifiedAt;//strtolower($folderName);

                                $i++;
                            }
                            else
                            {
                                $hasSentFolder = TRUE;
                                $sentFolderObj = $compFolderObj;
                            }
                        }
                    }
                }

                array_multisort($arrForSorting, $folderList); 

                $folderList = array_reverse($folderList);

                if($hasSentFolder)
                {
                    // array_unshift($folderList, $sentFolderObj);
                }
            }
        }

        if($listCode == $listCodeAllNotes)
        {
            $allNotesFolderId = -1;
            $allNotesFolderName = 'All Note(s)';
            $allNotesFolderContentCnt = 0;
            $allNotesFolderIsLocked = 0;
            $allNotesFolderIsFavorited = 0;
            $allNotesFolderTypeId = 0;

            $allNotesFolderObj = array();
            $allNotesFolderObj['id'] = sracEncryptNumberDataWithSanitization($allNotesFolderId, $userSession);
            $allNotesFolderObj['name'] = $allNotesFolderName;
            $allNotesFolderObj['iconCode'] = ""; // $folderIconBasePath.'/'.$folderIconCode.'.png';
            $allNotesFolderObj['contentCnt'] = $allNotesFolderContentCnt;
            $allNotesFolderObj['isLocked'] = $allNotesFolderIsLocked;
            $allNotesFolderObj['isFavorited'] = $allNotesFolderIsFavorited;
            $allNotesFolderObj['folderType'] = $allNotesFolderTypeId;
            $allNotesFolderObj['orgKey'] = $primOrgKey;
            $allNotesFolderObj['orgIconUrl'] = $primOrgIconUrl;
            $allNotesFolderObj['isVirtual'] = 0;

            array_unshift($folderList, $allNotesFolderObj);
        }


        if($listCode == $listCodeSentFolder)
        {
            $folderList = array();
            if($hasSentFolder)
            {
                array_push($folderList, $sentFolderObj);
            }
        }


        if($listCode == $listCodeTrashFolder)
        {
            $trashNotesFolderId = -2;
            $trashNotesFolderName = 'Trash';
            $trashNotesFolderContentCnt = 0;

            $assetBasePath = Config::get('app_config.assetBasePath');
            $baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
            $trashNotesIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconTrashPath');

            $trashNotesFolderObj = array();
            $trashNotesFolderObj['id'] = sracEncryptNumberDataWithSanitization($trashNotesFolderId, $userSession);
            $trashNotesFolderObj['name'] = $trashNotesFolderName;
            $trashNotesFolderObj['iconCode'] = $trashNotesIconPath;
            $trashNotesFolderObj['contentCnt'] = $trashNotesFolderContentCnt;
            $trashNotesFolderObj['isLocked'] = 0;
            $trashNotesFolderObj['isFavorited'] = 0;
            $trashNotesFolderObj['folderType'] = 0;
            $trashNotesFolderObj['orgKey'] = $primOrgKey;
            $trashNotesFolderObj['orgIconUrl'] = $primOrgIconUrl;
            $trashNotesFolderObj['isVirtual'] = 0;

            array_unshift($folderList, $trashNotesFolderObj);
        }
        
        if($listCode == $listCodeConversationNotes)
        {
            $assetBasePath = Config::get('app_config.assetBasePath');
            $baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
            $defGroupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconWhiteGroupPath'));

            $allConversationsFolderId = -1;
            $allConversationsFolderName = 'Conversations';
            $allConversationsFolderContentCnt = 0;
            $allConversationsFolderIsLocked = 0;
            $allConversationsFolderIsFavorited = 0;
            $allConversationsFolderTypeId = 0;

            $allConversationsFolderObj = array();
            $allConversationsFolderObj['id'] = sracEncryptNumberDataWithSanitization($allConversationsFolderId, $userSession);
            $allConversationsFolderObj['name'] = $allConversationsFolderName;
            $allConversationsFolderObj['iconCode'] = $defGroupIconPath;
            $allConversationsFolderObj['contentCnt'] = $allConversationsFolderContentCnt;
            $allConversationsFolderObj['isLocked'] = $allConversationsFolderIsLocked;
            $allConversationsFolderObj['isFavorited'] = $allConversationsFolderIsFavorited;
            $allConversationsFolderObj["folderType"] = $allConversationsFolderTypeId;
            $allConversationsFolderObj['orgKey'] = $primOrgKey;
            $allConversationsFolderObj['orgIconUrl'] = $primOrgIconUrl;
            $allConversationsFolderObj['isVirtual'] = 0;

            array_unshift($folderList, $allConversationsFolderObj);
        }

        return $folderList;
    }

    public function getRelevantGroupList($depMgmtObj, $listCode)
    {
        $isFolder = FALSE;

        $primOrgKey = "";
        $primOrgIconUrl = "";
        $primOrgId = $depMgmtObj->getOrganizationId();
        $userSession = $depMgmtObj->getAppuserSession();

        if($primOrgId > 0)
        {
            $organization = $depMgmtObj->getOrganizationObject();
            if(isset($organization))
            {
                $primOrgKey = $depMgmtObj->getOrgProfileKey();

                $logoFilename = $organization->logo_filename;
                if(isset($logoFilename) && $logoFilename != "")
                {
                    $primOrgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($primOrgId, $logoFilename);
                }

            }
        }
                     
        $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
        $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');

        $i = 0;
        $groupList = array(); 
        $groupIdArr = array();
        $arrForSorting = array();

        $allOrgDepMgmtObjArr = array();
        if($listCode == $listCodeFavFolders)
        {
            $userOrganizations = $depMgmtObj->getAllUserOrganizationProfiles();
            $lastUserOrgIndex = count($userOrganizations) > 0 ? count($userOrganizations) - 1 : 0;

            $user = $depMgmtObj->getUserObject();

            $tmpPerDepMgmtObj = New ContentDependencyManagementClass;
            $tmpPerDepMgmtObj->withUserIdOrgIdAndEmpId($user, 0, 0);
            array_push($allOrgDepMgmtObjArr, $tmpPerDepMgmtObj);

            foreach ($userOrganizations as $userOrgIndex => $userOrg) 
            {
                $orgId = $userOrg->organization_id;                 
                $organization = $userOrg->organization;
                if(isset($organization)) 
                {
                    $empId = $userOrg->emp_id;

                    $tmpOrgDepMgmtObj = New ContentDependencyManagementClass;
                    $tmpOrgDepMgmtObj->withOrgIdAndEmpId($orgId, $empId);

                    array_push($allOrgDepMgmtObjArr, $tmpOrgDepMgmtObj);
                }
            }
        }
        else
        {
            array_push($allOrgDepMgmtObjArr, $depMgmtObj);
        }

        foreach ($allOrgDepMgmtObjArr as $depMgmtIndex => $orgDepMgmtObj)
        {
            $orgId = $orgDepMgmtObj->getOrganizationId();

            $lockedFolderArr = $orgDepMgmtObj->getLockedFolderIdArr();

            $orgKey = "";
            $orgIconUrl = "";
            if($orgId > 0)
            {
                $organization = $orgDepMgmtObj->getOrganizationObject();
                if(isset($organization))
                {
                    $orgKey = $orgDepMgmtObj->getOrgProfileKey();

                    $logoFilename = $organization->logo_filename;
                    if(isset($logoFilename) && $logoFilename != "")
                    {
                        // $orgLogoUrl = OrganizationClass::getOrgPhotoUrl($orgId, $logoFilename);
                        $orgIconUrl = OrganizationClass::getOrgPhotoThumbUrl($orgId, $logoFilename);
                    }

                }
            }
            
            $assetBasePath = Config::get('app_config.assetBasePath');
            $baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
            $defGroupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconWhiteGroupPath'));
            
            $userGroupsModelObj = $orgDepMgmtObj->getAllGroupsForUserModelObj();
            if($listCode == $listCodeFavFolders)
            {
                $userGroupsModelObj = $userGroupsModelObj->isFavorited();
            }
            $userGroups = $userGroupsModelObj->get();
            foreach ($userGroups as $userGroup) 
            {
                $grpId = $userGroup->group_id;
                $name = $userGroup->name;
                $isTwoWay = $userGroup->is_two_way;
                $isFavorited = $userGroup->is_favorited;
                $description = $userGroup->description;
                $isAdmin = $userGroup->is_admin;
                $hasPostRight = $userGroup->has_post_right;
                $groupContentModifiedAt = $userGroup->content_modified_at;      
                
                // if($orgId > 0)
                // {
                //     $idColName = 'org_group_contents'.'.group_content_id';
                // }
                // else
                // {
                //     $idColName = 'group_contents'.'.group_content_id';
                // }
                
                // $groupContents = $orgDepMgmtObj->getAllContentModelObj($isFolder, $grpId);
                // $groupContents = $groupContents->select(["$idColName as content_id"]);
                // $groupContents = $groupContents->get();
                
                $groupContentCnt = 0; // count($groupContents);
                
                $photoFilename = $userGroup->img_server_filename;
                $groupPhotoUrl = $defGroupIconPath;
                $groupPhotoThumbUrl = $defGroupIconPath;
                if(isset($photoFilename) && $photoFilename != "")
                {
                    $groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
                    $groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);                          
                }
                    
                $groupList[$i]["id"] = sracEncryptNumberDataWithSanitization($grpId, $userSession);
                $groupList[$i]["name"] = $name;
                $groupList[$i]["description"] = $description;
                $groupList[$i]["contentCnt"] = $groupContentCnt;
                $groupList[$i]["isUserAdmin"] = $isAdmin;
                $groupList[$i]["hasPostRight"] = $hasPostRight;
                $groupList[$i]["isTwoWay"] = $isTwoWay;
                $groupList[$i]["isFavorited"] = $isFavorited;
                $groupList[$i]["photoUrl"] = $groupPhotoUrl;
                $groupList[$i]["photoThumbUrl"] = $groupPhotoThumbUrl;
                $groupList[$i]["photoFilename"] = $photoFilename;
                $groupList[$i]['orgKey'] = $orgKey;
                $groupList[$i]['orgIconUrl'] = $orgIconUrl;
                $arrForSorting[$i] = $groupContentModifiedAt;//strtolower($name);
                $i++;
            } 
        }

        array_multisort($arrForSorting, $groupList);

        $groupList = array_reverse($groupList);

        return $groupList;
    }
    
    /**
     * Folder List.
     *
     * @return json array
     */
    public function loadSelectFolderList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $selOrgId = Input::get('selOrgId');
        $loginToken = Input::get('loginToken');
        $searchStr = Input::get('searchStr');

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
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);	
                
                $folderArr = array();
                $arrForSorting = array();                
                $userFolders = $depMgmtObj->getAllFoldersModelObj();
                    
                if(isset($searchStr) && $searchStr != "")
                {       
                    $userFolders = $userFolders->where(function($query) use ($searchStr)
                                    {
                                        $query->where('folder_name','like',"%$searchStr%");
                                    });     
                }

                $userFolderArr = $userFolders->get(); 

                foreach ($userFolderArr as $folder) 
                {
                	if($folder->folder_type_id == 0)
                	{
						if($orgId > 0)					
							$folderId = $folder->employee_folder_id;
						else
							$folderId = $folder->appuser_folder_id;
							
						$folderName = $folder->folder_name;
						$folderObj = array();
						$folderObj["id"] = sracEncryptNumberData($folderId, $userSession);
						$folderObj["text"] = $folderName;
						array_push($folderArr, $folderObj);
						array_push($arrForSorting, $folderName);
					}
                }
                array_multisort($arrForSorting, $folderArr);   

				$response = array('results' => $folderArr );
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
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function appuserFolderListDatatable()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
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
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $modelObj = $depMgmtObj->getAllFoldersModelObj();

                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);                
            	if($orgId > 0)					
					$idStr = 'employee_folder_id';
				else
					$idStr = 'appuser_folder_id';
						
	            $folders = $modelObj->select([$idStr.' as folder_id', 'folder_name', 'folder_type_id']);

	            return Datatables::of($folders)
	                    ->remove_column('folder_id')
	                    ->remove_column('folder_type_id')
	                    ->add_column('action', function($folder) use ($userSession) {
	                        return $this->getFolderDatatableButton($folder->folder_id, $folder->folder_type_id, $userSession);
	                    })
	                    ->make();
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
    
    private function getFolderDatatableButton($id, $typeId, $userSession)
    {
        $id = sracEncryptNumberData($id, $userSession);

		$buttonHtml = "";        
		if($typeId == 0)
		{
	        $buttonHtml .= '&nbsp;<button onclick="loadAddEditFolderModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
		}     
        if($typeId != FolderType::$TYPE_SENT_FOLDER_ID)
        {
            $buttonHtml .= '&nbsp;<button onclick="checkAndDeleteFolder(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
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
        $encUserId = Input::get('userId');
        $id = Input::get('id');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        $hasFilters = Input::get('hasFilters');
        $filFolderArr = Input::get('filFolderArr');
        $filGroupArr = Input::get('filGroupArr');
        $filSourceArr = Input::get('filSourceArr');
        $filTypeArr = Input::get('filTypeArr');
        $filAttachmentTypeArr = Input::get('filAttachmentTypeArr');
        $filTagArr = Input::get('filTagArr');
        $filFromTimeStamp = Input::get('fromTimeStamp');
        $filToTimeStamp = Input::get('toTimeStamp');  
        $chkIsUnread = Input::get('chkIsUnread');  
        $chkIsStarred = Input::get('chkIsStarred');  
        $chkIsUntagged = Input::get('chkIsUntagged');
        $chkIsLocked = Input::get('chkIsLocked');
        $chkIsConversation = Input::get('chkIsConversation');
        $chkIsRestricted = Input::get('chkIsRestricted');
        $chkShowFolder = Input::get('chkShowFolder');
        $chkShowGroup = Input::get('chkShowGroup');
        $chkDownloadStatus = Input::get('chkDownloadStatus');
        $filShowAttachment = Input::get('filShowAttachment');
        $filSenderEmail = Input::get('filSenderEmail');

        $status = 0;
        $msg = "";

        if($encUserId != "" && $id != "")
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
				             
                $status = 1;	            
	            $pageName = 'Add'; 
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $folder = $depMgmtObj->getFolderObject($id);

	            if(isset($folder))
	            {
	            	$pageName = "Edit";
	            	$folder->folder_id = sracEncryptNumberData($id, $userSession);
				}
				
				$folderIcons = Config::get('app_config.folder_icon_path_arr');
				$folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));
	            
	            $data = array();
	            $data['folder'] = $folder;
	            $data['folderIcons'] = $folderIcons;
	            $data['folderIconBasePath'] = $folderIconBasePath;
	            $data['page_description'] = $pageName.' '.'Folder';

                $data['hasFilters'] = $hasFilters;
                $data['fromTimeStamp'] = $filFromTimeStamp;
                $data['toTimeStamp'] = $filToTimeStamp;
                $data['filFolderArr'] = $filFolderArr;
                $data['filGroupArr'] = $filGroupArr;
                $data['filSourceArr'] = $filSourceArr;
                $data['filTypeArr'] = $filTypeArr;
                $data['filAttachmentTypeArr'] = $filAttachmentTypeArr;
                $data['filTagArr'] = $filTagArr;
                $data['chkIsUnread'] = $chkIsUnread;
                $data['chkIsStarred'] = $chkIsStarred;
                $data['chkIsUntagged'] = $chkIsUntagged;
                $data['chkIsLocked'] = $chkIsLocked;
                $data['chkIsConversation'] = $chkIsConversation;
                $data['chkIsRestricted'] = $chkIsRestricted;
                $data['chkShowFolder'] = $chkShowFolder;
                $data['chkShowGroup'] = $chkShowGroup;
                $data['chkDownloadStatus'] = $chkDownloadStatus;
                $data['filShowAttachment'] = $filShowAttachment;
                $data['filSenderEmail'] = $filSenderEmail;
	           
	            $_viewToRender = View::make('content.supporting._addEditFolderModal', $data);
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
    
    /**
     * Check Folder Can Be Deleted.
     *
     * @return json array
     */
    public function checkFolderCanBeDeleted()
    {
        $msg = "";
        $status = 0;
        $isDeletable = 1;

        $encUserId = Input::get('userId');
        $id = Input::get('id');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $response = array();
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
                
                $isFolder = TRUE;
				
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
				$sentFolderId = $depMgmtObj->getSentFolderId();
				if($sentFolderId != $id)
				{
					$usedFolders = $depMgmtObj->getAllContents($isFolder, $id);
					
					if(count($usedFolders) == 0)                
	                	$status = 1;
	                else
	                {
						$status = -1;
						$msg = "Folder in use. Cannot be deleted.";
					}      
				}
	                else
                {
					$status = -1;
					$msg = "Sent Folder cannot be deleted.";
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
    
    /**
     * Delete Folder.
     *
     * @return json array
     */
    public function deleteFolder()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = Input::get('id');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $id != "")
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
				              
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $folderDeleteResponse = $depMgmtObj->deleteFolder($id);

                $response['folderDeleteResponse'] = $folderDeleteResponse;
                    
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
     * Folder List.
     *
     * @return json array
     */
    public function validateFolderPin()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $pin = Input::get('pin');
        $loginToken = Input::get('loginToken');

        /*$encUserId = "eyJpdiI6InVVMHF5dEc2dTBYQm1CajZhY05FT1E9PSIsInZhbHVlIjoib0xuT0xXcitYc2FyVjE4NjUzNTc4UT09IiwibWFjIjoiOWE3MjEwMjBlNjhkZDE0ZWNhNjBiODAzN2I4MDcyYjAzZjU4M2NjZjI5Njk3YjZlMzBmMzU4OTg2Mzk4ODgyZiJ9";*/

        $response = array();

        if($encUserId != "" && $pin != "")
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
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userConstants = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstants))
                {
                    $hasFolderPasscode = $userConstants->folder_passcode_enabled;
                    $folderPasscode = $userConstants->folder_passcode;
                    
                    if($hasFolderPasscode == 1) 
                    {
                   		$decFolderPasscode = Crypt::decrypt($folderPasscode);
                   		if($pin == $decFolderPasscode) {
                			$status = 1;
						}
						else {
			                $status = -1;
			                $msg = "Invalid PIN";  
						}
					}
					else
		            {
		                $status = -1;
		                $msg = "Invalid PIN";       
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
    
    /**
     * Folder List.
     *
     * @return json array
     */
    public function checkFolderPinEnabled()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');

        /*$encUserId = "eyJpdiI6InVVMHF5dEc2dTBYQm1CajZhY05FT1E9PSIsInZhbHVlIjoib0xuT0xXcitYc2FyVjE4NjUzNTc4UT09IiwibWFjIjoiOWE3MjEwMjBlNjhkZDE0ZWNhNjBiODAzN2I4MDcyYjAzZjU4M2NjZjI5Njk3YjZlMzBmMzU4OTg2Mzk4ODgyZiJ9";*/

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
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $userConstants = $depMgmtObj->getEmployeeOrUserConstantObject();
                if(isset($userConstants))
                {
                    $hasFolderPasscode = $userConstants->folder_passcode_enabled;
                    if($userConstants->folder_passcode == '') {
						$hasFolderPasscode = 0;
					}
                    
                    $status = 1;  
        			$response['enabled'] = $hasFolderPasscode;        
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
    
    public function validateFolderName()
    {
        $msg = "";
        $status = 0;
        $isAvailable = FALSE;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = Input::get('id');
        $name = Input::get('name');

        $response = array();

        if($encUserId != "" && $id != "")
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
				             
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $modelObj = $depMgmtObj->getAllFoldersModelObj();            
                
               	$modelObj = $modelObj->where('folder_name','=',$name);
               	if($id > 0) 
            	{
            		if($encOrgId == "")
            			$fieldname = "appuser";
            		else
            			$fieldname = "employee";
            			
            		$modelObj = $modelObj->where($fieldname."_folder_id", "<>", "$id");
				}
            		
            	$folder = $modelObj->first();
            	
            	if(!isset($folder))
		            $isAvailable = TRUE;
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

        $response['valid'] = $isAvailable;
        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}
    
    /**
     * Folder List.
     *
     * @return json array
     */
    public function favoriteList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isLockedFlag = Input::get('isLocked');

        /*$encUserId = "eyJpdiI6Ik1IVko5VzZxRktNMFZSdkpMcHZobHc9PSIsInZhbHVlIjoiaXFOK3JzRFwvdHRkdGJRdmtcL29zN0FBPT0iLCJtYWMiOiJmYmNmODM2NDI0MGIyMjBkMzk1MDIwNjZjMjI0ODNjYmZiMDk3OTc3ZjY2M2VkMWQ3MGNkNTBiNmExYjVkM2NlIn0=";
        $loginToken = '2017-11-06 10:18:59_4304';*/

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
				
				$organizationKeyArr = array();
				$orgDetails = array();
				$orgDetails['key'] = '';
				$orgDetails['id'] = 0;
				$orgDetails['thumbUrl'] = '';
				array_push($organizationKeyArr, $orgDetails);
	            $userOrganizations = OrganizationUser::ofUserEmail($user->email)->verified()->get();
	            foreach ($userOrganizations as $userOrg) 
                {
                	$organization = $userOrg->organization;
                	if(isset($organization)) {
						$userOrgId = $userOrg->organization_id;
	                	$userOrgEmpId = $userOrg->emp_id;
						$userOrgKey = Crypt::encrypt($userOrgId."_".$userOrgEmpId);
						$userOrgLogoThumbUrl = "";
						$userOrgLogoFilename = $organization->logo_filename;
						if(isset($userOrgLogoFilename) && $userOrgLogoFilename != "")
						{
							$userOrgLogoThumbUrl = OrganizationClass::getOrgPhotoThumbUrl($userOrgId, $userOrgLogoFilename);
						}
						$orgDetails = array();
						$orgDetails['key'] = $userOrgKey;
						$orgDetails['id'] = $userOrgId;
						$orgDetails['thumbUrl'] = $userOrgLogoThumbUrl;
	                	array_push($organizationKeyArr, $orgDetails);
					}
				}
				
                $i = 0;
                $folderGroupList = array();
                $folderIdArr = array();
                $arrForSorting = array();
                    
				$folderIconBasePath = asset(Config::get('app_config.assetBasePath').Config::get('app_config.folder_icon_base_path'));
				
				foreach($organizationKeyArr as $organizationObj)
				{
	                $orgKey = $organizationObj['key'];
	                $orgId = $organizationObj['id'];
	                $orgThumbUrl = $organizationObj['thumbUrl'];
	                
	                $depMgmtObj = New ContentDependencyManagementClass;
	                $depMgmtObj->withOrgKey($user, $orgKey);
	                $depMgmtObj->setCurrentLoginToken($loginToken);
	                $userConstants = $depMgmtObj->getEmployeeOrUserConstantObject();
	                
	                $passcodeFolderIdDelimiter = Config::get('app_config.folder_passcode_id_delimiter');
					$lockedFolderArr = array();
	                if(isset($userConstants))
	                {
	                    $hasFolderPasscode = $userConstants->folder_passcode_enabled;
	                    $folderIdStr = $userConstants->folder_id_str;
	                    if($hasFolderPasscode == 1 && $folderIdStr != null ) 
	                    {
	                        $lockedFolderArr = explode($passcodeFolderIdDelimiter, $folderIdStr);
						}
	                }
	                $isFolder = TRUE;
                
	                $userFolders = $depMgmtObj->getAllFolders();
	                foreach ($userFolders as $folder) 
	                {
	                	if($orgId > 0)					
						{
							$folderId = $folder->employee_folder_id;
							$idColName = 'employee_contents'.'.employee_content_id';
							
						}
						else
						{
							$folderId = $folder->appuser_folder_id;
							$idColName = 'appuser_contents'.'.appuser_content_id';
						}	
						
						$folderContents = $depMgmtObj->getAllContentModelObj($isFolder, $folderId);
	    				$folderContents = $folderContents->select(["$idColName as content_id"]);
	    				$folderContents = $folderContents->get();
							
						$folderName = $folder->folder_name;
						$folderIconCode = $folder->icon_code;
						$folderIsFavorited = $folder->is_favorited;
						$folderContentCnt = count($folderContents);					
						
						if($folderIsFavorited == 1 && !in_array($folderId, $folderIdArr))
						{
							array_push($folderIdArr, $folderId);
							
		                	$folderIsLocked = 0;
		                	if(in_array($folderId, $lockedFolderArr))
		                	{
								$folderIsLocked = 1;
							}
							
							if($isLockedFlag == 0 || ($isLockedFlag == 1 && $folderIsLocked == 0)) {
			                    $folderGroupList[$i]['id'] = $folderId;
			                    $folderGroupList[$i]['name'] = $folderName;
			                    $folderGroupList[$i]['isFolder'] = 1;
				                $folderGroupList[$i]['orgKey'] = $orgKey;
								$folderGroupList[$i]["orgThumbUrl"] = $orgThumbUrl;
			                    $folderGroupList[$i]['iconCode'] = $folderIconBasePath.'/'.$folderIconCode.'.png';
			                    $folderGroupList[$i]['contentCnt'] = $folderContentCnt;
			                    $folderGroupList[$i]['isLocked'] = $folderIsLocked;
			                    $folderGroupList[$i]['isFavorited'] = $folderIsFavorited;
			                    $arrForSorting[$i] = strtolower($folderName);

			                    $i++;
							}
						}
	                }
	                
	                $isFolder = FALSE;
	                $userGroups = $depMgmtObj->getAllGroupsFoUser("");
	                foreach ($userGroups as $userGroup) 
	                {
						$grpId = $userGroup->group_id;
						$name = $userGroup->name;
						$isTwoWay = $userGroup->is_two_way;
						$isFavorited = $userGroup->is_favorited;
						$description = $userGroup->description;
						
						if($isFavorited == 1)
						{
							$isAdmin = 0;					
							$isUserGroupAdmin = $depMgmtObj->getUserIsGroupAdmin($grpId);
			    			if(isset($isUserGroupAdmin))  
			    			{
								$isAdmin = 1;
							}
							
							if($orgId > 0)
		                    {
		                    	$idColName = 'org_group_contents'.'.group_content_id';
							}
							else
		                    {
		                    	$idColName = 'group_contents'.'.group_content_id';
							}
							
							$groupContents = $depMgmtObj->getAllContentModelObj($isFolder, $grpId);
		    				$groupContents = $groupContents->select(["$idColName as content_id"]);
		    				$groupContents = $groupContents->get();
		    				
							$groupContentCnt = count($groupContents);	  

							$hasPostRight = $depMgmtObj->getUserHasGroupPostRight($grpId);   
							
							$photoFilename = $userGroup->img_server_filename;
							$groupPhotoUrl = "";
							$groupPhotoThumbUrl = "";
							if(isset($photoFilename) && $photoFilename != "")
							{
								$groupPhotoUrl = OrganizationClass::getOrgGroupPhotoUrl($orgId, $photoFilename);
								$groupPhotoThumbUrl = OrganizationClass::getOrgGroupPhotoThumbUrl($orgId, $photoFilename);							
							}       	
												
							$folderGroupList[$i]["id"] = $grpId;
							$folderGroupList[$i]["name"] = $name;
			                $folderGroupList[$i]['isFolder'] = 0;
			                $folderGroupList[$i]['orgKey'] = $orgKey;
							$folderGroupList[$i]["orgThumbUrl"] = $orgThumbUrl;
							$folderGroupList[$i]["description"] = $description;
			                $folderGroupList[$i]['contentCnt'] = $groupContentCnt;
							$folderGroupList[$i]["isUserAdmin"] = $isAdmin;
							$folderGroupList[$i]["hasPostRight"] = $hasPostRight;
							$folderGroupList[$i]["isTwoWay"] = $isTwoWay;
							$folderGroupList[$i]["isFavorited"] = $isFavorited;
							$folderGroupList[$i]["photoUrl"] = $groupPhotoUrl;
							$folderGroupList[$i]["photoThumbUrl"] = $groupPhotoThumbUrl;
			                $arrForSorting[$i] = strtolower($name);
							
							$i++;	
						}
						 
								
					} 
				}
                
                array_multisort($arrForSorting, $folderGroupList);   

                $status = 1;

                $resCnt = count($folderGroupList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_folder_found');
                
                $defFolderIconCode = Config::get('app_config.default_folder_icon_code');
                $defIconCode = $folderIconBasePath.'/'.$defFolderIconCode.'.png';

                $response['favoriteCnt'] = $resCnt;
                $response['favoriteArr'] = $folderGroupList;
                $response['defIconCode'] = $defIconCode;
                // $response['organizationKeyArr'] = $organizationKeyArr;
                // $response['userOrganizations'] = $userOrganizations;
                	
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
     * save app user broadcast details.
     *
     * @return json array
     */
    public function invertFavoritedStatus()
    {
    	$msg = "";
        $status = 0;

        $encUserId = Input::get('userId');        
        $folderId = Input::get('folderId');
        $isFavorited = Input::get('isFavorited');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');

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

                $folderId = sracDecryptNumberData($folderId, $userSession);
				
				$status = 1;
				
				$isFavorited = $isFavorited*1;
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);		  
                $isFavorited = $depMgmtObj->setFolderFavoritedStatus($folderId, $isFavorited);	
                
       			$response['isFavorited'] = $isFavorited;	
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
