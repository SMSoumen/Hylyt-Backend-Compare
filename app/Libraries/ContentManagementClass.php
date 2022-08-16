<?php 

namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use App\Models\Api\Appuser;
use App\Models\Api\AppuserConstant;
use App\Models\Api\AppuserContent;
use App\Libraries\MailClass;
use App\Http\Traits\CloudMessagingTrait;

class ContentManagementClass 
{
    use CloudMessagingTrait;
    
	public function addContentEntryForUser($userId, $contentText)
	{
		$response = array();
		
		$newContentId = 0;
		$sendStatus = 0;
		
		$userConstantDetails = AppuserConstant::ofUser($userId)->first();
        $appUser = Appuser::findOrFail($userId);
						
		if(isset($userConstantDetails))
    	{
    		$defFolderId = $userConstantDetails->def_folder_id;
    		$emailSourceId = $userConstantDetails->email_source_id;
    		$contentTypeAlmanac = 2;

			$utcTz =  'UTC';
    		$createDateObj = Carbon::now($utcTz);
    		$createTimeStamp = $createDateObj->timestamp;    		
    		$createTimeStamp = $createTimeStamp * 1000;

    		$userContent = New AppuserContent;
            $userContent->appuser_id = $userId;
            $userContent->content = Crypt::encrypt($contentText);
            $userContent->content_type_id = $contentTypeAlmanac;
            $userContent->folder_id = $defFolderId;
            $userContent->source_id = $emailSourceId;
            $userContent->is_marked = 0;
            $userContent->create_timestamp = $createTimeStamp;
            $userContent->save();
            
            $newContentId = $userContent->appuser_content_id;
            
            $sendStatus = 1;
        	if($appUser->is_logged_in == 1 && $appUser->reg_token != "")
           		$sendStatus = $this->sendEntryAddMessageToDevice($userId, $newContentId, "SocioRAC");
            
			MailClass::sendContentAddedMail($userId);
    	}
    	
    	$response['contId'] = $newContentId;
    	$response['sendStatus'] = $sendStatus;
    	
    	return $response;
	}
	
    /**
     * Attach a File.
     *
     * @return json array
     */
    public function makeAttachmentCopy($actAttachmentId)
    {
    	set_time_limit(0);
    	
        $msg = "";
        $status = 0;

        /*$fileExt = ".png";
        $encUserId = "eyJpdiI6Ik5rNVNRU1V6VXFTYUF4UHlqb1NGZnc9PSIsInZhbHVlIjoiNUhpbnBFcmFGM3dYR0RCWHBFcFh6Zz09IiwibWFjIjoiODIwNmVkZTg4NzU5ODBhZmEwZDc5ZTdjYTBkNWUxNTkxNzMyNjMyMGUzODI1ZmIxMzdhNTI3YTczNzBmOWE4NSJ9";
        $id = 18;
        $attachmentId = 9;
        $fileName = "14908_1479649473.png";
        $orgPath = public_path(Config::get('app_config.path_content_attachment'));
        $attachedFilePath = $orgPath.$fileName;
        $attachedFile = New File($attachedFilePath);*/

        $response = array();
        
        

        $encUserId = Input::get('userId');
        $id = Input::get('id');
        $attachmentId = Input::get('attachmentId');
        $fileName = Input::get('fileName');
        $fileExt = Input::get('fileExt');
        $fileSize = Input::get('fileSize');
        $attachedFile = Input::file('attachmentFile');
                
        $userContentId = $userContent->appuser_content_id;
        $serverFileDetails = FileUploadClass::uploadAttachment($attachedFile, $fileExt);

        $serverFileName = $serverFileDetails['name'];
        $serverFileSize = $serverFileDetails['size'];

        if($serverFileName != "" && $serverFileSize > 0)
        {
            $status = 1;

            $userContentAttachment = new AppuserContentAttachment();
            $userContentAttachment->attachment_id = $attachmentId;
            $userContentAttachment->filename = $fileName;
            $userContentAttachment->server_filename = $serverFileName;
            $userContentAttachment->appuser_content_id = $userContentId;
            $userContentAttachment->filesize = $fileSize;
            $userContentAttachment->save();

            $userContentAttachmentId = $userContentAttachment->content_attachment_id;
            
            $response['size'] = $serverFileSize;
            $response['name'] = $serverFileName;
            $baseUrl = Config::get('app_config.url_path_content_attachment');
            $attachmentUrl = url($baseUrl.$serverFileName);
            $response['attachmentUrl'] = $attachmentUrl;
            $response['syncId'] = $userContentAttachmentId;
            
    		CommonFunctionClass::setLastSyncTs($userId);
        }       
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_attachment_upload_failed'); 
        }

        return Response::json($response);
    }
	
	
}
