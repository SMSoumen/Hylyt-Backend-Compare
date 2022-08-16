<?php 

namespace App\Libraries;

use Config;
use Image;
use File;
use App\Libraries\OrganizationClass;
use App\Models\Org\OrganizationServer;
use Crypt;
use Illuminate\Support\Facades\Log;

class FileUploadClass 
{
	public static function uploadOrgMlmNotifImage($file, $orgId)
	{        
        $fileName = "";

		if (File::exists($file) && $file->isValid()) 
        {
        	$extension = $file->getClientOriginalExtension();
        	$extension = strtolower($extension);
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.".".$extension;

			$orgServer = OrganizationServer::ofOrganization($orgId)->first();
			if(isset($orgServer))
			{
				$isAppFileServer = $orgServer->is_app_file_server;
				$hostname = $orgServer->file_host;
				
            	$orgAssetDirPath = OrganizationClass::getOrgMlmNotificationAssetDirPath($orgId);
            	
	            if($isAppFileServer == 1)
	            {
	            	$file->move($orgAssetDirPath, $fileName);				
				}
				else
				{						
					FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);
				}
			}
        }

        return $fileName;
	}
	
	public static function validateAndCreateRemoteOrgAssetFile($host, $file, $path, $filename)
	{          
		// data fields for POST request
		$fields = array("dirPath"=>$path, "filename"=>$filename, "isThumb"=>0);

		$files = array();
		$files[0]["content"] = file_get_contents($file->getPathName());
		$files[0]["paramname"] = "fileObj";
		$files[0]["filename"] = $file->getClientOriginalName();
		$files[0]["filetype"] = $file->getMimeType();

		//$url_data = http_build_query($data);

		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;

		$postData = FileUploadClass::build_data_files($boundary, $fields, $files);
		
		$createFileUrl = $host.Config::get('app_config.create_remote_org_file_url_suffix');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, TRUE);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_URL, $createFileUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		         "Content-Type: multipart/form-data; boundary=" . $delimiter,
		         "Content-Length: " . strlen($postData)   
			)                                                                
	    ); 
		curl_exec($ch);
		curl_close($ch);
	}
	
	public static function validateAndCreateRemoteOrgThumbAssetFile($host, $file, $path, $filename)
	{		
        $thumbsHeight = Config::get('app_config.thumb_photo_h');
        $thumbsWidth = Config::get('app_config.thumb_photo_w');
               
		// data fields for POST request
		$fields = array("dirPath"=>$path, "filename"=>$filename, "isThumb"=>1, "thumb_height"=>$thumbsHeight, "thumb_width"=>$thumbsWidth);

		$files = array();
		$files[0]["content"] = file_get_contents($file->getPathName());
		$files[0]["paramname"] = "fileObj";
		$files[0]["filename"] = $file->getClientOriginalName();
		$files[0]["filetype"] = $file->getMimeType();

		//$url_data = http_build_query($data);

		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;

		$postData = FileUploadClass::build_data_files($boundary, $fields, $files);
		
		$createFileUrl = $host.Config::get('app_config.create_remote_org_file_url_suffix');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, TRUE);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_URL, $createFileUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		         "Content-Type: multipart/form-data; boundary=" . $delimiter,
		         "Content-Length: " . strlen($postData)   
			)                                                                
	    ); 
		curl_exec($ch);
		curl_close($ch);
	}

	static function build_data_files($boundary, $fields, $files){
	    $data = '';
	    $eol = "\r\n";

	    $delimiter = '-------------' . $boundary;

	    foreach ($fields as $name => $content) {
	        $data .= "--" . $delimiter . $eol
	            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
	            . $content . $eol;
	    }


	    foreach ($files as $fileObj) {
	    	$postParamName = $fileObj['paramname'];
	    	$filename = $fileObj['filename'];
	    	$filetype = $fileObj['filetype'];
	    	$content = $fileObj['content'];
	    	
	        $data .= "--" . $delimiter . $eol
	            . 'Content-Disposition: form-data; name="' . $postParamName . '"; filename="' . $filename . '"; type="' . $filetype . '"' . $eol
	            //. 'Content-Type: image/png'.$eol
	            . 'Content-Transfer-Encoding: binary'.$eol
	            ;

	        $data .= $eol;
	        $data .= $content . $eol;
	    }
	    $data .= "--" . $delimiter . "--".$eol;


	    return $data;
	}

	public static function uploadOrgMlmContentAdditionFile($file, $orgId)
	{        
        $fileName = "";

		if (File::exists($file) && $file->isValid()) 
        {
            $isLocal = FALSE;
            
        	$extension = $file->getClientOriginalExtension();
        	$extension = strtolower($extension);
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.".".$extension;
					
	    	$orgAssetDirPath = OrganizationClass::getOrgMlmContentAdditionAssetDirPath($orgId);

			if($orgId > 0)
			{
				$orgServer = OrganizationServer::ofOrganization($orgId)->first();
				if(isset($orgServer))
				{
					$isAppFileServer = $orgServer->is_app_file_server;
					$hostname = $orgServer->file_host;
	            	
		            if($isAppFileServer == 1)
		            {
		            	$isLocal = TRUE;
					}
					else
					{	
						$isLocal = FALSE;										
						FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);										
					}
				}
			}
			else
			{
				$isLocal = TRUE;
			}
			
			if($isLocal) {
		            	
            	$newFilePath = $orgAssetDirPath."/".$fileName;
            	//$file->move($orgAssetDirPath, $fileName);		
            	$fileContent = File::get($file);	
            	$encFileContent = Crypt::encrypt($fileContent);
            	File::put($newFilePath, $encFileContent);
		            	
				$isTypeImage = checkIfFileTypeImageFromExtension($extension);
				
				if($isTypeImage) {
					$imagePath = $file;
					list($imageWidth, $imageHeight) = getimagesize($imagePath);

					/* Thumb Image Upload Begins */	
			        $thumbPath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name');//.'/';
			        $thumbsHeight = Config::get('app_config.thumb_photo_h');
			        $thumbsWidth = Config::get('app_config.thumb_photo_w');

			        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
			        $thumbHeight = $thumbDimensions['height'];
			        $thumbWidth = $thumbDimensions['width'];

			        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
			        $thumbImage = Image::make($imagePath)
			        					->resize($thumbWidth, $thumbHeight,  function ($c) {
										    $c->aspectRatio();
										    $c->upsize();
										});

					$thumbBackground->insert($thumbImage, 'center');
					
		    		$tempThumbFilePath = $thumbPath."/temp_".$fileName;					
					$thumbBackground->save($tempThumbFilePath);		    	
		    		$thumbFileContent = File::get($tempThumbFilePath);	
		        	File::delete($tempThumbFilePath);
		    		
		    		$newThumbFilePath = $thumbPath."/".$fileName;
		        	$encThumbFileContent = Crypt::encrypt($thumbFileContent);
		        	File::put($newThumbFilePath, $encThumbFileContent);
					/* Thumb Image Upload Ends */
				}
			}
        }

        return $fileName;
	}

    public static function copyContentAdditionFileToContent($addContFilePath, $orgId)
    {
    	$fileDetails = NULL;
    	
    	//if(File::exists($addContFilePath) && $addContFilePath->isValid()) 
        {
	        $extension = File::extension($addContFilePath);
	        $extension = strtolower($extension);
	           
	        $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);	

	        $newFilename = rand(11111,99999).'_'.time();
	        $newFilename = $newFilename.".".$extension;
	        $newFilePath = $orgAssetDirPath."/".$newFilename;
	        $newThumbFilePath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name')."/".$newFilename;

	        File::copy($addContFilePath, $newFilePath);
	        
			/* Thumb Image Upload Begins */	
			$isTypeImage = checkIfFileTypeImageFromExtension($extension);
			
			if($isTypeImage && isset($addContThumbFilePath)) {
	        	File::copy($addContThumbFilePath, $newThumbFilePath);
			}
			/* Thumb Image Upload Ends */	

	        $fileSize = File::size($newFilePath);
	        $fileSize = round($fileSize/1000);
	        
	        $fileDetails = array();
	        $fileDetails['name'] = $newFilename;
	        $fileDetails['size'] = $fileSize;
		}

        return $fileDetails;
    }
	
	public static function uploadMlmNotifImage($file)
	{
        $fileName = "";

		if (File::exists($file) && $file->isValid()) 
        {
        	$extension = $file->getClientOriginalExtension();
	        $extension = strtolower($extension);
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.".".$extension;

			$orgId = 0;
            $orgAssetDirPath = OrganizationClass::getOrgMlmNotificationAssetDirPath($orgId);
            $file->move($orgAssetDirPath, $fileName);
        }

        return $fileName;
	}
	
	public static function uploadAttachment($file, $ext, $orgId)
	{
		$fileName = "";
		$fileSize = 0;

		if(File::exists($file) && $file->isValid()) 
        {
	        $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
	        	
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.$ext;
            $isLocal = FALSE;
            
            if($orgId > 0)
            {
				$orgServer = OrganizationServer::ofOrganization($orgId)->first();
				if(isset($orgServer))
				{
					$isAppFileServer = $orgServer->is_app_file_server;
					$hostname = $orgServer->file_host;
						            	
		            if($isAppFileServer == 1)
		            {
		            	$isLocal = TRUE;			
					}
					else
					{	
						$isLocal = FALSE;					
						FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);										
					}
				}
			}
			else
			{
		        $isLocal = TRUE;				
			}
			
            if($isLocal) 
            {
                try
                {
                    $newFilePath = $orgAssetDirPath."/".$fileName;
                    //$file->move($orgAssetDirPath, $fileName);		
                    $fileContent = File::get($file);	
                    $encFileContent = Crypt::encrypt($fileContent);
                    File::put($newFilePath, $encFileContent);
	            }
				catch (Exception $e) 
				{
    				// Log::info('Inside Encryption Exception : ');
    				// Log::info($e);
				}
            	
				$isTypeImage = checkIfFileTypeImageFromExtension($ext);
				
				if($isTypeImage) {
					$imagePath = $file;
					list($imageWidth, $imageHeight) = getimagesize($imagePath);

					try
					{
                        /* Thumb Image Upload Begins */	
                        $thumbPath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name').'/';
                        $thumbsHeight = Config::get('app_config.thumb_photo_h');
                        $thumbsWidth = Config::get('app_config.thumb_photo_w');
                        
                        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
                        $thumbHeight = $thumbDimensions['height'];
                        $thumbWidth = $thumbDimensions['width'];
                        
                        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
                        $thumbImage = Image::make($imagePath)
                                            ->resize($thumbWidth, $thumbHeight,  function ($c) {
                                                $c->aspectRatio();
                                                $c->upsize();
                                            });
                        
                        $thumbBackground->insert($thumbImage, 'center');
                        
                        $tempThumbFilePath = $thumbPath."/temp_".$fileName;					
                        $thumbBackground->save($tempThumbFilePath);		    	
                        $thumbFileContent = File::get($tempThumbFilePath);	
                        File::delete($tempThumbFilePath);
                        
                        $newThumbFilePath = $thumbPath."/".$fileName;
                        $encThumbFileContent = Crypt::encrypt($thumbFileContent);
                        File::put($newThumbFilePath, $encThumbFileContent);
                        /* Thumb Image Upload Ends */
					}
					catch (Exception $e) 
					{
        				// Log::info('Inside Thumb Exception : ');
        				// Log::info($e);
					}
				}
			}

            /*$fileSize = File::size($orgPath.$fileName);
	        $fileSize = round($fileSize/1000);*/
        }

        $fileDetails['name'] = $fileName;
        $fileDetails['size'] = $fileSize;

        return $fileDetails;
	}
	
	public static function uploadAttachmentContent($inpFileName, $fileContent, $orgId)
	{	
		$fileName = "";
		$fileSize = 0;

		if(isset($fileContent) && $fileContent != '') 
        {
	        $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);

	        $orgAssetDirPath = '/var/www/html/public/'.$orgAssetDirPath;
	        
	        $extension = getExtensionFromFilename($inpFileName);
	        	
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.".".$extension;
            $isLocal = FALSE;
            
            if($orgId > 0)
            {
				$orgServer = OrganizationServer::ofOrganization($orgId)->first();
				if(isset($orgServer))
				{
					$isAppFileServer = $orgServer->is_app_file_server;
					$hostname = $orgServer->file_host;
						            	
		            if($isAppFileServer == 1)
		            {
		            	$isLocal = TRUE;			
					}
					else
					{	
						$isLocal = FALSE;					
						//FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);										
					}
				}
			}
			else
			{
		        $isLocal = TRUE;				
			}
			
			if($isLocal) {		            	
            	$newFilePath = $orgAssetDirPath."/".$fileName;
            	$encFileContent = Crypt::encrypt($fileContent);
            	File::put($newFilePath, $encFileContent);
            	
				$isTypeImage = checkIfFileTypeImageFromExtension($extension);
				
				if($isTypeImage) {
		    		$tempFilePath = $orgAssetDirPath."/temp_".$fileName;	
	        		File::put($tempFilePath, $fileContent);
					
					$imagePath = $tempFilePath;
					list($imageWidth, $imageHeight) = getimagesize($imagePath);

					/* Thumb Image Upload Begins */	
			        $thumbPath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name').'/';
			        $thumbsHeight = Config::get('app_config.thumb_photo_h');
			        $thumbsWidth = Config::get('app_config.thumb_photo_w');

			        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
			        $thumbHeight = $thumbDimensions['height'];
			        $thumbWidth = $thumbDimensions['width'];

			        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
			        $thumbImage = Image::make($imagePath)
			        					->resize($thumbWidth, $thumbHeight,  function ($c) {
										    $c->aspectRatio();
										    $c->upsize();
										});

					$thumbBackground->insert($thumbImage, 'center');
					
		    		$tempThumbFilePath = $thumbPath."/temp_".$fileName;					
					$thumbBackground->save($tempThumbFilePath);		    	
		    		$thumbFileContent = File::get($tempThumbFilePath);	
		        	File::delete($tempThumbFilePath);
		    		
		    		$newThumbFilePath = $thumbPath."/".$fileName;
		        	$encThumbFileContent = Crypt::encrypt($thumbFileContent);
		        	File::put($newThumbFilePath, $encThumbFileContent);
					/* Thumb Image Upload Ends */
					
			        File::delete($tempFilePath);
				}

	            $fileSize = File::size($newFilePath);
		        $fileSize = round($fileSize/1000);
			}
        }

        $fileDetails['name'] = $fileName;
        $fileDetails['size'] = $fileSize;

        return $fileDetails;
	}

    public static function makeAttachmentCopy($filename, $orgId)
    {	           
	    $newFilename = "";
	    $fileSize = 0;
	    $isLocal = FALSE;
	    
	    $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
        $orgFilePath = $orgAssetDirPath."/".$filename; 
        
        if(File::exists($orgFilePath)) 
        {       
	        $extension = File::extension($orgFilePath);
		    $extension = strtolower($extension);

	        $newFilename = rand(11111,99999).'_'.time();
	        $newFilename = $newFilename.".".$extension;
	        $newFilePath = $orgAssetDirPath."/".$newFilename;
	        
	        if($orgId > 0)
	        {
				$orgServer = OrganizationServer::ofOrganization($orgId)->first();
				if(isset($orgServer))
				{
					$isAppFileServer = $orgServer->is_app_file_server;
					$hostname = $orgServer->file_host;
						            	
		            if($isAppFileServer == 1)
		            {
		            	$isLocal = TRUE;		
					}
					else
					{	
						$isLocal = FALSE;	
						$file = new File($orgFilePath);				
						FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $newFilename);										
					}
				}
			}
			else
			{
				$isLocal = TRUE;			
			}
			
			if($isLocal) {
				File::copy($orgFilePath, $newFilePath);	
				
				/* Thumb Image Upload Begins */	
				$thumbPath = $orgAssetDirPath."/".Config::get('app_config.thumb_photo_folder_name').'/';
	        	$orgFileThumbPath = $thumbPath.$filename; 
	        	
				if(File::exists($orgFileThumbPath)) 
				{
			        $newThumbFilePath = $thumbPath."/".$newFilename;
			        File::copy($orgFileThumbPath, $newThumbFilePath);
				}
				/* Thumb Image Upload Ends */	
				
		        $fileSize = File::size($newFilePath);
			    $fileSize = round($fileSize/1000);
			}

		}
		

        $fileDetails['name'] = $newFilename;
        $fileDetails['size'] = $fileSize;
        return $fileDetails;
    }

	public static function removeAttachment($filename, $orgId)
	{
	    $orgAssetDirPath = OrganizationClass::getOrgContentAssetDirPath($orgId);
        $orgFile = $orgAssetDirPath."/".$filename;
		File::delete($orgFile);
		
		/* Thumb Image Remove Begins */
		$thumbPath = $orgAssetDirPath.Config::get('app_config.thumb_photo_folder_name').'/';
    	$orgThumbFile = $thumbPath."/".$filename; 
    	File::delete($orgThumbFile);
    	/* Thumb Image Remove Ends */
	}
	
	public static function uploadOrganizationLogoImage($file)
	{
        $fileName = rand(11111,99999).'_'.time();

		/* Original Image Upload Begins */	
        $extension = $file->getClientOriginalExtension();
	    $extension = strtolower($extension);
        $fileName = $fileName.'.'.$extension;
        
        $orgId = 0;
    	$orgAssetDirPath = OrganizationClass::getOrgPhotoDirPath($orgId);	            	
        $file->move($orgAssetDirPath, $fileName);
		/* Original Image Upload Ends */

		$imagePath = $orgAssetDirPath.'/'.$fileName;
		list($imageWidth, $imageHeight) = getimagesize($imagePath);

		/* Resized Image Upload Begins */	
        /*$resizedPath = $orgAssetDirPath.'/'.Config::get('app_config.resized_photo_folder_name').'/';
        $resizeHeight = Config::get('app_config.resized_photo_h');
        $resizeWidth = Config::get('app_config.resized_photo_w');

        $resizeDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $resizeHeight, $resizeWidth);
        $resizedHeight = $resizeDimensions['height'];
        $resizedWidth = $resizeDimensions['width'];

        $resizedBackground = Image::canvas($resizedWidth, $resizedHeight);
        $resizedImage = Image::make($imagePath)
        					->resize($resizedWidth, $resizedHeight,  function ($c) {
							    $c->aspectRatio();
							    $c->upsize();
							});
		$resizedBackground->insert($resizedImage, 'center');
		$resizedBackground->save($resizedPath.$fileName);*/
		/* Resized Image Upload Ends */	

		/* Thumb Image Upload Begins */	
        $thumbPath = public_path().'/'.$orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name').'/';
        $thumbsHeight = Config::get('app_config.thumb_photo_h');
        $thumbsWidth = Config::get('app_config.thumb_photo_w');

        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
        $thumbHeight = $thumbDimensions['height'];
        $thumbWidth = $thumbDimensions['width'];

        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
        $thumbImage = Image::make($imagePath)
        					->resize($thumbWidth, $thumbHeight,  function ($c) {
							    $c->aspectRatio();
							    $c->upsize();
							});

		$thumbBackground->insert($thumbImage, 'center');
		$thumbBackground->save($thumbPath.$fileName);
		/* Thumb Image Upload Ends */
		
        return $fileName;
	}
	
	public static function uploadOrganizationGroupPhotoImage($file, $orgId)
	{
        $fileName = rand(11111,99999).'_'.time();

		/* Original Image Upload Begins */	
        $extension = $file->getClientOriginalExtension();
	    $extension = strtolower($extension);
        $fileName = $fileName.'.'.$extension;
        
        $orgAssetDirPath = OrganizationClass::getOrgGroupPhotoDirPath($orgId);	  
        if($orgId > 0)
		{
			$orgServer = OrganizationServer::ofOrganization($orgId)->first();
			if(isset($orgServer))
			{
				$isAppFileServer = $orgServer->is_app_file_server;
				$hostname = $orgServer->file_host;
            	
	            if($isAppFileServer == 1)
	            {
	            	$isLocal = TRUE;
				}
				else
				{	
					$isLocal = FALSE;										
					FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);			
			        $thumbPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name');							
					FileUploadClass::validateAndCreateRemoteOrgThumbAssetFile($hostname, $file, $thumbPath, $fileName);											
				}
			}
		}
		else
		{
			$isLocal = TRUE;
		}
		
		if($isLocal) {
			$file->move($orgAssetDirPath, $fileName);

			$imagePath = $orgAssetDirPath.'/'.$fileName;
			list($imageWidth, $imageHeight) = getimagesize($imagePath);

			/* Thumb Image Upload Begins */	
	        $thumbPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name').'/';
	        $thumbsHeight = Config::get('app_config.thumb_photo_h');
	        $thumbsWidth = Config::get('app_config.thumb_photo_w');

	        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
	        $thumbHeight = $thumbDimensions['height'];
	        $thumbWidth = $thumbDimensions['width'];

	        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
	        $thumbImage = Image::make($imagePath)
	        					->resize($thumbWidth, $thumbHeight,  function ($c) {
								    $c->aspectRatio();
								    $c->upsize();
								});

			$thumbBackground->insert($thumbImage, 'center');
			$thumbBackground->save($thumbPath.$fileName);
		}
        
        return $fileName;
	}

	static function getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $resizeHeight, $resizeWidth)
	{
		if ($imageWidth > $imageHeight && $imageWidth != 0) 
		{
			$image_height = floor(($imageHeight/$imageWidth)*$resizeWidth);
			$image_width  = $resizeWidth;
		} 
		else if($imageHeight != 0)
		{
			$image_width  = floor(($imageWidth/$imageHeight)*$resizeHeight);
			$image_height = $resizeHeight;
		}
		else 
		{
			$image_width = $resizeWidth;
			$image_height = $resizeHeight;
		}

		$imageDimension = array('height' => $image_height, 'width' => $image_width);

		return $imageDimension;
	}
	
	public static function uploadOrgEmployeeImage($file, $orgId)
	{        
        $fileName = "";

		if(File::exists($file) && $file->isValid()) 
        {
        	$extension = $file->getClientOriginalExtension();
        	$extension = strtolower($extension);
            $fileName = rand(11111,99999).'_'.time();
            $fileName = $fileName.".".$extension;

			$orgServer = OrganizationServer::ofOrganization($orgId)->first();
			if(isset($orgServer))
			{
				$isAppFileServer = $orgServer->is_app_file_server;
				$hostname = $orgServer->file_host;
				
            	$orgAssetDirPath = OrganizationClass::getOrgEmployeePhotoAssetDirPath($orgId);
            	
	            if($isAppFileServer == 1)
	            {
	            	$file->move($orgAssetDirPath, $fileName);				
				}
				else
				{						
					FileUploadClass::validateAndCreateRemoteOrgAssetFile($hostname, $file, $orgAssetDirPath, $fileName);										
				}
			}
        }

        return $fileName;
	}
	
	public static function uploadUserProfileImage($file)
	{
        $fileName = rand(11111,99999).'_'.time();

		/* Original Image Upload Begins */	
        $extension = $file->getClientOriginalExtension();
	    $extension = strtolower($extension);
        $fileName = $fileName.'.'.$extension;
        
    	$orgAssetDirPath = OrganizationClass::getAppuserProfilePhotoDirPath();	            	
        $file->move($orgAssetDirPath, $fileName);
		/* Original Image Upload Ends */

		$imagePath = $orgAssetDirPath.'/'.$fileName;
		list($imageWidth, $imageHeight) = getimagesize($imagePath);

		/* Resized Image Upload Begins */	
        /*$resizedPath = $orgAssetDirPath.'/'.Config::get('app_config.resized_photo_folder_name').'/';
        $resizeHeight = Config::get('app_config.resized_photo_h');
        $resizeWidth = Config::get('app_config.resized_photo_w');

        $resizeDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $resizeHeight, $resizeWidth);
        $resizedHeight = $resizeDimensions['height'];
        $resizedWidth = $resizeDimensions['width'];

        $resizedBackground = Image::canvas($resizedWidth, $resizedHeight);
        $resizedImage = Image::make($imagePath)
        					->resize($resizedWidth, $resizedHeight,  function ($c) {
							    $c->aspectRatio();
							    $c->upsize();
							});
		$resizedBackground->insert($resizedImage, 'center');
		$resizedBackground->save($resizedPath.$fileName);*/
		/* Resized Image Upload Ends */	

		/* Thumb Image Upload Begins */	
        $thumbPath = $orgAssetDirPath.'/'.Config::get('app_config.thumb_photo_folder_name').'/';
        $thumbsHeight = Config::get('app_config.thumb_photo_h');
        $thumbsWidth = Config::get('app_config.thumb_photo_w');

        $thumbDimensions = FileUploadClass::getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
        $thumbHeight = $thumbDimensions['height'];
        $thumbWidth = $thumbDimensions['width'];

        $thumbBackground = Image::canvas($thumbWidth, $thumbHeight);
        $thumbImage = Image::make($imagePath)
        					->resize($thumbWidth, $thumbHeight,  function ($c) {
							    $c->aspectRatio();
							    $c->upsize();
							});

		$thumbBackground->insert($thumbImage, 'center');
		$thumbBackground->save($thumbPath.$fileName);
		/* Thumb Image Upload Ends */
		
        return $fileName;
	}
	
	public static function uploadTempCloudStorageAttachment($file, $extension)
	{
        $fileName = rand(11111,99999).'_'.time();

	    $extension = strtolower($extension);
        $fileName = $fileName.$extension;
        
    	$orgAssetDirPath = OrganizationClass::getTempCloudStorageAssetDirPath();	            	
        $file->move($orgAssetDirPath, $fileName);
		
        return $fileName;
	}

	public static function removeTempCloudStorageAttachment($filename)
	{
	    $orgAssetDirPath = OrganizationClass::getTempCloudStorageAssetDirPath();
        $orgFile = $orgAssetDirPath."/".$filename;
		File::delete($orgFile);
	}
}
