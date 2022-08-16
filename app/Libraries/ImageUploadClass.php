<?php 

namespace App\Libraries;

use Config;
use Image;
use File;

class ImageUploadClass {

	function uploadContentImage($imageName, $imageStr)
	{
		$folderExt = Config::get('app_config.content_image_folder_ext');
		$fileName = $this->uploadImageString($folderExt, $imageName, $imageStr);
        return $fileName;
	}

	function uploadImageString($folderExt, $imageName, $imageStr)
	{	
		$fileName = $imageName;

		/* Original Image Upload Begins */
        $orgPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_org'));
		$imagePath = $orgPath.$fileName;

        $imageStr = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageStr));
		file_put_contents($imagePath, $imageStr);
		/* Original Image Upload Ends */

		$this->resizeImageFile($folderExt, $imagePath, $fileName);

        return $fileName;
	}

	function uploadImageFile($folderExt, $image)
	{	
		$fileName = $image->getClientOriginalName();

		/* Original Image Upload Begins */	
        $orgPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_org'));
        $extension = $image->getClientOriginalExtension();
        $fileName = $fileName.'.'.$extension;
        $image->move($orgPath, $fileName);
		/* Original Image Upload Ends */

		$imagePath = $orgPath.$fileName;
		$this->resizeImageFile($folderExt, $imagePath, $fileName);

        return $fileName;
	}

	function resizeImageFile($folderExt, $imagePath, $fileName)
	{
		list($imageWidth, $imageHeight) = getimagesize($imagePath);

		/* Resized Image Upload Begins */	
        $resizedPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_resized'));
        $resizeHeight = Config::get('app_config.resized_h');
        $resizeWidth = Config::get('app_config.resized_w');

        $resizeDimensions = $this->getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $resizeHeight, $resizeWidth);
        $resizedHeight = $resizeDimensions['height'];
        $resizedWidth = $resizeDimensions['width'];

        $resizedBackground = Image::canvas($resizedWidth, $resizedHeight);
        $resizedImage = Image::make($imagePath)
        					->resize($resizedWidth, $resizedHeight,  function ($c) {
							    $c->aspectRatio();
							    $c->upsize();
							});
		$resizedBackground->insert($resizedImage, 'center');
		$resizedBackground->save($resizedPath.$fileName);
		/* Resized Image Upload Ends */	

		/* Thumb Image Upload Begins */	
        $thumbPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_thumb'));
        $thumbsHeight = Config::get('app_config.thumb_h');
        $thumbsWidth = Config::get('app_config.thumb_w');

        $thumbDimensions = $this->getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $thumbsHeight, $thumbsWidth);
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
	}

	function getAspectMaintainedHeightWidth($imageWidth, $imageHeight, $resizeHeight, $resizeWidth)
	{
		if ($imageWidth > $imageHeight) 
		{
			$image_height = floor(($imageHeight/$imageWidth)*$resizeWidth);
			$image_width  = $resizeWidth;
		} 
		else 
		{
			$image_width  = floor(($imageWidth/$imageHeight)*$resizeHeight);
			$image_height = $resizeHeight;
		}

		$imageDimension = array('height' => $image_height, 'width' => $image_width);

		return $imageDimension;
	}

	function removeContentImage($filename)
	{
		$folderExt = Config::get('app_config.content_image_folder_ext');
		$this->removeImage($folderExt, $filename);
        return $fileName;
	}

	function removeImage($folderExt, $filename)
	{
        $orgPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_org'));
        $orgFile = $orgPath.$filename;
		File::delete($orgFile);

        $resizedPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_resized'));
        $resizedFile = $resizedPath.$filename;
		File::delete($resizedFile);

        $thumbPath = public_path(Config::get('app_config.path_'.$folderExt.'_img_thumb'));
        $thumbFile = $thumbPath.$filename;
		File::delete($thumbFile);
	}
}
