<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

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
use App\Libraries\ImageUploadClass;
use App\Libraries\FileUploadClass;
use App\Libraries\CommonFunctionClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Libraries\OrganizationClass;
use App\Http\Traits\CloudMessagingTrait;
use App\Http\Traits\OrgCloudMessagingTrait;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{   
    use CloudMessagingTrait;
    use OrgCloudMessagingTrait;
    
    public function __construct()
    {
        
    }
    
    /**
     * Attach a File.
     *
     * @return json array
     */   

    public function uploadAttachment()
    {
    	set_time_limit(0);
    	
        Log::info('Inside uploadAttachment');

        $msg = "";
        $status = 0;
        $logsArr = array();

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = Input::get('id');
        $attId = Input::get('attId');
        $fileName = Input::get('fileName');
        $fileExt = Input::get('fileExt');
        $fileSize = Input::get('fileSize');
        $attachedFile = Input::file('attachmentFile');
        $isAdd = Input::get('isAdd');
        $loginToken = Input::get('loginToken');
        $attachmentCnt = Input::get('attachmentCnt');
        $sendAsReply = Input::get('sendAsReply');
        $inpCloudStorageTypeId = Input::get('cloudStorageTypeId');
        $cloudFileUrl = Input::get('cloudFileUrl');
        $cloudFileId = Input::get('cloudFileId');
        $cloudFileThumbStr = Input::get('cloudFileThumbStr');
        $attCreateTs = Input::get('attCreateTs');
        $attUpdateTs = Input::get('attUpdateTs');

        // Log::info('id : '.$id.' : fileName : '.$fileName.' : fileSize : '.$fileSize.' : cloudFileUrl : '.$cloudFileUrl.' : cloudFileId : '.$cloudFileId.' : inpCloudStorageTypeId : '.$inpCloudStorageTypeId);
        // Log::info('attId : '.$attId.' : attCreateTs : '.$attCreateTs.' : attUpdateTs : '.$attUpdateTs.' : fileExt : '.$fileExt.' : cloudFileThumbStr : '.$cloudFileThumbStr.' : attachmentCnt : '.$attachmentCnt);

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
                $attId = sracDecryptNumberData($attId, $userSession);
				
    			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
            	$isFolder = TRUE;
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $availableKbs = $depMgmtObj->getAvailableUserQuota($isFolder);

                $cloudStorageTypeId = 0;
                $cloudStorageType = $depMgmtObj->getCloudStorageTypeObjectById($inpCloudStorageTypeId);
                if(isset($cloudStorageType))
                {
                    $cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;
                }
                
                if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $fileSize))
                {
					$userContent = $depMgmtObj->getContentObject($id, $isFolder);                
	                if(isset($userContent))
	                {
                        if($cloudStorageTypeId > 0)
                        {
                            $serverFileName = '';
                            $serverFileSize = $fileSize;
                        }
                        else
                        {
                            $serverFileDetails = FileUploadClass::uploadAttachment($attachedFile, $fileExt, $orgId);
                            $serverFileName = $serverFileDetails['name'];
                            $serverFileSize = $fileSize;//$serverFileDetails['size'];
                            $cloudFileUrl = '';
                            $cloudFileId = '';
                            $cloudFileThumbStr = '';
                        }

	                    if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
	                    {
	                        $status = 1;
	           				$response = $depMgmtObj->addEditContentAttachment($attId, $id, $fileName, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
	                        $attachmentId = $response['syncId'];

                            if($cloudStorageTypeId == 0)
		                    {
                                $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $serverFileName); 
                            }
                            else
                            {
                                $attachmentUrl = $cloudFileUrl; 
                            }
	                        
	                        $response['size'] = $serverFileSize;
	                        $response['name'] = $serverFileName;  
	                        $response['attachmentUrl'] = $attachmentUrl;
                            $response['syncId'] = sracEncryptNumberData($attachmentId, $userSession);
	                        
	                		CommonFunctionClass::setLastSyncTs($userId, $loginToken);
	                		
	            			$currAttachmentCnt = $depMgmtObj->getContentAttachmentCount($id, $isFolder);
	    					$modAttachmentCnt = $depMgmtObj->getModifiedContentAttachmentCnt($id, $isFolder);
	    					
	    					//$depMgmtObj->recalculateUserQuota($isFolder);
                			$updatedAllocatedKbs = $depMgmtObj->getAllocatedUserQuota($isFolder);
                			$updatedAvailableKbs = $depMgmtObj->getAvailableUserQuota($isFolder);
                			$updatedUsedKbs = $depMgmtObj->getUsedUserQuota($isFolder);
							
			                $response["allocKb"] = $updatedAllocatedKbs;
			                $response["usedKb"] = $updatedUsedKbs;
			                
			                array_push($logsArr, 'b4 send');
	    					
	                		if($attachmentCnt == $currAttachmentCnt && $modAttachmentCnt == 0)
	                		{
                				$depMgmtObj->setContentUpdated($id, $isFolder);          			        				
								//Send FCM to all
								if($orgId > 0)
								{								
	           						$this->sendOrgContentAddMessageToDevice($orgEmpId, $orgId, $loginToken, $isFolder, $id);
	           						$this->sendOrgEmployeeQuotaToDevice($orgEmpId, $orgId);	
								}
								else
								{
					           		$this->sendContentAddMessageToDevice($userId, $loginToken, $isFolder, $id);
					           		$this->sendUserQuotaToDevice($userId, $loginToken);
								}	
								
			                	array_push($logsArr, 'b4 sendAsReply');					
						
								if(isset($sendAsReply) && $sendAsReply == 1)
								{
                                    $encContentText = $userContent->content;
                                    $contentText = Crypt::decrypt($encContentText);
                                    array_push($logsArr, 'in sendAsReply');
                                    // $depMgmtObj->sendContentAsReply($encUserId, $encOrgId, $loginToken, $id, $isFolder);
                                    $depMgmtObj->sendFolderContentAsReply($id, $contentText);
								}
							}
	                    }       
	                    else
	                    {
	                        $status = -1;
	                        $msg = Config::get('app_config_notif.err_attachment_upload_failed'); 
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
           	 		$msg = Config::get('app_config_notif.err_insufficient_space');
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
		// $response['l'] = $logsArr;

        Log::info('uploadAttachment : ');
        Log::info($response);

        return Response::json($response);
    }
    
    /**
     * Remove File(s) from content.
     *
     * @return json array
     */   

    public function removeAttachment()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $id = Input::get('id');
        $attachmentIdArr = Input::get('attachmentIdArr');
        $attIdArr = json_decode($attachmentIdArr);

        $response = array();

        if($encUserId != "" && $id != "")
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

                $id = sracDecryptNumberData($id, $userSession);
                $attachmentIdArr = sracDecryptNumberArrayData($attachmentIdArr, $userSession);

                $userContent = AppuserContent::ofUserAndContent($userId, $id)->first();
                if(isset($userContent))
                {
                    $userContentId = $userContent->appuser_content_id;
                    for($i=0; $i<count($attIdArr); $i++)
                    {
                        $status = 1;
                        $attachmentId = $attIdArr[$i];

                        if($attachmentId > 0)
                        {
                            $userAttachment = AppuserContentAttachment::ofUserContentAndId($userContentId, $attachmentId)->first();

                            if(isset($userAttachment))
                            {
                                if($userAttachment->att_cloud_storage_type_id == 0)
                                {
                                    $filename = $userAttachment->server_filename;
                                    FileUploadClass::removeAttachment($filename);
                                }
                                $userAttachment->delete();
                            }
                        }                            
                    }
                    CommonFunctionClass::setLastSyncTs($userId);                   
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
     * Download File(s) from content.
     *
     * @return json array
     */   

    public function downloadAttachment()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $id = Input::get('attachmentId');
        $isDownloadFlag = Input::get('isDownload');
        $isThumbFlag = Input::get('isThumb');

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

				$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
				
				$isFolder = FALSE;
				if($isFolderFlag == 1)
					$isFolder = TRUE;
					
				if(!isset($isDownloadFlag))
					$isDownloadFlag = 0;

				$depMgmtObj = New ContentDependencyManagementClass;
				$depMgmtObj->withOrgKey($user, $encOrgId);
				
				$contentAttachment = $depMgmtObj->getContentAttachment($id, $isFolder);

				
				if(isset($contentAttachment))
				{ 
					$status = 1;
                    $cloudStorageTypeId = $contentAttachment->att_cloud_storage_type_id;
                    $serverFilename = $contentAttachment->server_filename;
                    $fileName = $contentAttachment->filename;

                    if($cloudStorageTypeId == 0 && $serverFilename != "")
                    {                        
                        $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
                        
                        if($isThumbFlag == 1) {
                            $filePath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name')."/".$serverFilename;
                        }
                        else {
                            $filePath = $orgAssetDirPath."/".$serverFilename;
                        }
                        
                        if(File::exists($filePath)) {
                            try
                            {
                                //print_r($filePath .' exists');
                                // Log::info('filePath : '.$filePath);
                             
                                // $fileMimeType = File::mimeType($filePath);   
                                $fileMimeType = getMimeTypeFromFilename($serverFilename);  
                               //print_r('$fileMimeType: '.$fileMimeType);
                                // Log::info('fileMimeType : '.$fileMimeType);
                                
                                $encryptedContents = File::get($filePath);
                                $decryptedContents = Crypt::decrypt($encryptedContents);
                            
                               //print_r('$encryptedContents: '.$encryptedContents);
                            
                                
                                $fileDisposition = "inline";
                                if($isDownloadFlag == 1 || strpos($fileMimeType, 'audio') !== false || strpos($fileMimeType, 'video') !== false)
                                    $fileDisposition = "attachment";

                                // Log::info('fileDisposition : '.$fileDisposition.' : fileName : '.$fileName);

                                return $fileContent = response()->make($decryptedContents, 200, array(
                                    'Content-Type' => $fileMimeType,
                                    'Content-Disposition' => $fileDisposition.'; filename="' . $fileName . '"'
                                ));
                                
                                $response['fileStr'] = utf8_encode($fileContent);
                            }
                            catch (Exception $e) 
                            {
                                $response['fileStr'] = "";
                                
                                $status = -1;
                                $msg = Config::get('app_config_notif.err_invalid_data');
                            }
                        }
                        else {
                            $response['fileStr'] = "";
                        }
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
    
    /**
     * Attach a File.
     *
     * @return json array
     */
    public function uploadGroupAttachment()
    {
    	set_time_limit(0);

        //Log::info('Inside uploadGroupAttachment');
    	
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $attId = Input::get('attId');
        $id = Input::get('id');
        $fileName = Input::get('fileName');
        $fileExt = Input::get('fileExt');
        $fileSize = Input::get('fileSize');
        $attachedFile = Input::file('attachmentFile');
        $attachmentCnt = Input::get('attachmentCnt');
        $isAdd = Input::get('isAdd');
        $loginToken = Input::get('loginToken');
        $sendAsReply = Input::get('sendAsReply');
        $forRestore = Input::get('forRestore');
        $inpCloudStorageTypeId = Input::get('cloudStorageTypeId');
        $cloudFileUrl = Input::get('cloudFileUrl');
        $cloudFileId = Input::get('cloudFileId');
        $cloudFileThumbStr = Input::get('cloudFileThumbStr');
        $attCreateTs = Input::get('attCreateTs');
        $attUpdateTs = Input::get('attUpdateTs');

        if(!isset($forRestore) || $forRestore == "" || $forRestore != 1)
        {
            $forRestore = 0;
        }

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
                $attId = sracDecryptNumberData($attId, $userSession);
				
				$isFolder = FALSE;			
            	$sharedByUserEmail = $user->email;
            	                   
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $groupContent = $depMgmtObj->getContentObject($id, $isFolder);
                if(isset($groupContent))
                {
                    $groupContentId = $groupContent->group_content_id;
                    $serverGroupId = $groupContent->group_id;
                    $group = $depMgmtObj->getGroupObject($serverGroupId);
                	
                	if(isset($group))
                	{
            			$orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
        				$orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
				        if($orgId > 0)
				        {
							$orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);
						}

                        $cloudStorageTypeId = 0;
                        $cloudStorageType = $depMgmtObj->getCloudStorageTypeObjectById($inpCloudStorageTypeId);
                        if(isset($cloudStorageType))
                        {
                            $cloudStorageTypeId = $cloudStorageType->cloud_storage_type_id;
                        }
            			
            			$hasSpaceAvailable = FALSE;
                		$availableKbs = $depMgmtObj->getAvailableUserQuota($isFolder, $serverGroupId);
                        if(($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $availableKbs >= $fileSize))
                        {
							$hasSpaceAvailable = TRUE;
						}
						
						if($hasSpaceAvailable)
            			{      
                            if($cloudStorageTypeId > 0)
                            {
                                $serverFileName = '';
                                $serverFileSize = $fileSize;
                            }
                            else
                            {
                                $serverFileDetails = FileUploadClass::uploadAttachment($attachedFile, $fileExt, $orgId);
                                $serverFileName = $serverFileDetails['name'];
                                $serverFileSize = $fileSize;//$serverFileDetails['size'];
                                $cloudFileUrl = '';
                                $cloudFileId = '';
                                $cloudFileThumbStr = '';
                            }

                            if((($cloudStorageTypeId > 0) || ($cloudStorageTypeId == 0 && $serverFileName != "")) && $serverFileSize > 0)
                            {
		                        $status = 1;
		           				$response = $depMgmtObj->addEditGroupContentAttachment($attId, $id, $fileName, $serverFileName, $serverFileSize, $cloudStorageTypeId, $cloudFileUrl, $cloudFileId, $cloudFileThumbStr, $attCreateTs, $attUpdateTs);
		                        $attachmentId = $response['syncId'];
		                        
		                        if($orgId > 0 && $cloudStorageTypeId == 0)
		            			{
									$allocatedSpaceKb = $group->allocated_space_kb;
									$usedSpaceKb = $group->used_space_kb;
							
			                        $newUsedSpace = $usedSpaceKb + $fileSize;
			                        
			                        if($newUsedSpace > $allocatedSpaceKb)
			                        	$newUsedSpace = $allocatedSpaceKb;
			                        
			                        $group->used_space_kb = $newUsedSpace;
			                        $group->save();
								}

                                if($cloudStorageTypeId == 0)
                                {
                                    $attachmentUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $serverFileName); 
                                }
                                else
                                {
                                    $attachmentUrl = $cloudFileUrl; 
                                }
		                        
		                        $response['size'] = $serverFileSize;
		                        $response['name'] = $serverFileName;
		                        $response['attachmentUrl'] = $attachmentUrl;
                                $response['syncId'] = sracEncryptNumberData($attachmentId, $userSession);
		                        
		                		CommonFunctionClass::setLastSyncTs($userId, $loginToken);
		                		
		                		$currAttachmentCnt = $depMgmtObj->getContentAttachmentCount($id, $isFolder);
    							$modAttachmentCnt = $depMgmtObj->getModifiedContentAttachmentCnt($id, $isFolder);
    					
    							//$depMgmtObj->recalculateUserQuota($isFolder, $serverGroupId);
                				$updatedAllocatedKbs = $depMgmtObj->getAllocatedUserQuota($isFolder, $serverGroupId);
	                			$updatedAvailableKbs = $depMgmtObj->getAvailableUserQuota($isFolder, $serverGroupId);
	                			$updatedUsedKbs = $depMgmtObj->getUsedUserQuota($isFolder, $serverGroupId);
								
				                $response["allocKb"] = $updatedAllocatedKbs;
				                $response["usedKb"] = $updatedUsedKbs;
				                
				                $isRename = 0;			                
		                		if($attachmentCnt == $currAttachmentCnt && $modAttachmentCnt == 0)
		                		{
                					$depMgmtObj->setContentUpdated($id, $isFolder);
                					
									if($orgId > 0)
									{
										$grpMemModelObj = New OrgGroupMember;
						            	$grpMemModelObj->setConnection($orgDbConName);     	
						            	$groupMembers = $grpMemModelObj->ofGroup($serverGroupId)->get();
										
										foreach($groupMembers as $groupMember)
										{
											$memberEmpId = $groupMember->employee_id;
											
						        			if($memberEmpId != $orgEmpId)
						        			{
								        		$empDepMgmtObj = New ContentDependencyManagementClass;								
										       	$empDepMgmtObj->withOrgIdAndEmpId($orgId, $memberEmpId);   
										        $orgEmployee = $empDepMgmtObj->getPlainEmployeeObject();
								        
								        		if(isset($orgEmployee) && $orgEmployee->is_active == 1)
		           									$this->sendOrgGroupEntryAddMessageToDevice($orgId, $memberEmpId, $id, $isAdd, $sharedByUserEmail);
											}
											else
											{
												$this->sendOrgContentAddMessageToDevice($memberEmpId, $orgId, $loginToken, $isFolder, $groupContentId);
											}	
											$this->sendOrgGroupAddedMessageToDevice($memberEmpId, $serverGroupId, $isRename, $orgId);										
										}	
									}
									else
									{
										$groupMembers = GroupMember::ofGroup($serverGroupId)->get();
										if(isset($groupMembers) && count($groupMembers) > 0)
										{
											foreach($groupMembers as $groupMember)
											{
												$memberUserId = $groupMember->member_appuser_id;
												                        	
                                                if($memberUserId != $userId)
                                                {
                                                    $this->sendGroupEntryAddMessageToDevice($memberUserId, $groupContentId, $isAdd, $sharedByUserEmail, $forRestore);
                                                    $this->sendGroupAddedMessageToDevice($memberUserId, $serverGroupId, $isRename, $sharedByUserEmail);
                                                }
                                                else
                                                {						
                                                    $this->sendContentAddMessageToDevice($memberUserId, $loginToken, $isFolder, $groupContentId);
                                                    $this->sendGroupAddedMessageToDevice($memberUserId, $serverGroupId, $isRename, $sharedByUserEmail, $loginToken);
                                                }									
											}
										}
									}						
					
									if(isset($sendAsReply) && $sendAsReply == 1)
									{
										$depMgmtObj->sendContentAsReply($encUserId, $encOrgId, $loginToken, $id, $isFolder);										
									}   
								}
		                    }       
		                    else
		                    {
		                        $status = -1;
		                        $msg = Config::get('app_config_notif.err_attachment_upload_failed'); 
		                    } 
		                }       
	                    else
	                    {
	                        $status = -1;
	                        $msg = "Group space has been exhausted";
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
     * Remove File(s) from content.
     *
     * @return json array
     */   

    public function removeContentAttachment()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');
        $encOrgId = Input::get('orgId');

        $contentId = Input::get('contentId');
        $isFolderFlag = Input::get('isFolder');
        $attachmentId = Input::get('attachmentId');

        $response = array();

        if($encUserId != "" && $contentId != "")// && $attachmentId != "")
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

                $contentId = sracDecryptNumberData($contentId, $userSession);
                $attachmentId = sracDecryptNumberData($attachmentId, $userSession);

                $isFolder = FALSE;
                if(isset($isFolderFlag) && $isFolderFlag == 1)
                {
                    $isFolder = TRUE;                    
                }
                    
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);

                $content = $depMgmtObj->getContentObject($contentId, $isFolder);  

                if(isset($content))
                {
                    if($content->is_locked == 0)
                    {
                        $orgId = $depMgmtObj->getOrganizationId();  
                        $orgEmpId = $depMgmtObj->getOrgEmployeeId();

                        $sharedByName = "";  
                        $sharedByEmail = "";  
                        
                        $sharedByUserName = $user->fullname;  
                        $sharedByUserEmail = $user->email; 
                        $sharedByEmpEmail = "";             
                        $sharedByEmpName = "";

                        $orgEmployee = $depMgmtObj->getPlainEmployeeObject();
                        if(isset($orgEmployee))
                        {   
                            $sharedByEmpName = $orgEmployee->employee_name;
                            $sharedByEmpEmail = $orgEmployee->email;          
                        }

                        if($orgId > 0)
                        {
                            $sharedByName = $sharedByEmpName;  
                            $sharedByEmail = $sharedByEmpEmail;
                        }
                        else
                        {
                            $sharedByName = $sharedByUserName;  
                            $sharedByEmail = $sharedByUserEmail;
                        }

                        $hasModifyRights = $depMgmtObj->userHasContentModificationRight($isFolder, $content);

                        if($hasModifyRights)
                        {
                            $status = 1;
                            $depMgmtObj->deleteFolderOrGroupContentAttachment($attachmentId, $isFolder);
                           
                            $isAdd = 0;
                            $depMgmtObj->sendRespectiveContentModificationPush($isFolder, $contentId, $isAdd, $sharedByEmail);
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
                        $msg = 'Locked content cannot be modified';
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

    public function decryptAttachmentForTempUrl()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $isFolderFlag = Input::get('isFolder');
        $id = Input::get('attachmentId');

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
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgEmpId = OrganizationClass::getOrgEmployeeIdFromOrgKey($encOrgId);
                
                $isFolder = FALSE;
                if($isFolderFlag == 1)
                    $isFolder = TRUE;
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                
                $contentAttachment = $depMgmtObj->getContentAttachment($id, $isFolder);
                
                if(isset($contentAttachment))
                { 
                    $status = 1;
                    $serverFilename = $contentAttachment->server_filename;
                    $fileName = $contentAttachment->filename;
                    $fileSize = $contentAttachment->filesize;
                    
                    $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
                    $filePath = $orgAssetDirPath."/".$serverFilename;
                    
                    if(File::exists($filePath)) 
                    {
                        $tempFileUrl = '';
                        $encryptedContents = File::get($filePath);
                        try
                        {
                            $decryptedContents = Crypt::decrypt($encryptedContents);

                            $tempFilename = 'tmp_dec_'.$serverFilename;
                            $tempFilePath = $orgAssetDirPath."/".$tempFilename;
                            File::put($tempFilePath, $decryptedContents);
                            $tempFileUrl = OrganizationClass::getOrgContentAssetUrl($orgId, $tempFilename); 

                        }
                        catch(Exception $e)
                        {
                            //Log::info('$e : '.$e);
                        }

                        $isTypeImage = checkIfFileTypeImageFromFileName($serverFilename);
                        $isTypeImageFlag = $isTypeImage ? 1 : 0;

                        $isTypeVideo = checkIfFileTypeVideoFromFileName($serverFilename);
                        $isTypeVideoFlag = $isTypeVideo ? 1 : 0;

                        $nonRenderableExtensionArr = array();
                        array_push($nonRenderableExtensionArr, 'ics');
                        array_push($nonRenderableExtensionArr, 'vcf');

                        $canRenderViewFlag = 1;
                        $serverFileExt = getExtensionStringFromFilename($serverFilename);
                        if(in_array($serverFileExt, $nonRenderableExtensionArr))
                        {
                            $canRenderViewFlag = 0;
                        }
                        
                        $response['fileUrl'] = $tempFileUrl;
                        $response['isTypeImage'] = $isTypeImageFlag;
                        $response['isTypeVideo'] = $isTypeVideoFlag;
                        $response['canRenderView'] = $canRenderViewFlag;
                    }
                    else 
                    {
                        $response['fileUrl'] = "";
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