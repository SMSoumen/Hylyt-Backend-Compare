@php	
	$startTagReplacement = '<span class="searchHighlighted">';
	$endTagReplacement = '</span>';
	
	$selGroupOrFolderId = 0;	
	$orgKey = $conOrgId;

	$selFolderId = NULL;
	$selFolderName = "-";
	$selSourceId = NULL;
	$selSourceName = "-";
	$contentTypeIdA = Config::get('app_config.content_type_a');
	$selTypeId = Config::get('app_config.content_type_a');
	$selTypeName = "-";
	$selTagIdStr = "";
	$tagNameStr = "";
	$contentText = NULL;
	$fromTs = NULL;
	$fromDtTm = NULL;
	$toTs = NULL;
	$toDtTm = NULL;
	$fromTimeStamp = NULL;
	$toTimeStamp = NULL;
	$createTimeStamp = NULL;
	$updateTimeStamp = NULL;
	$attachmentCnt = NULL;
	$folderArr = array();
	$sourceArr = array();
	$tagArr = array();
	$isMarked = 0;
	$isLocked = 0;
	$isShareEnabled = 1;
	$remindBeforeMillis = 0;
	$remindBeforeMillisStr = '';
	$repeatDuration = '';
	$repeatDurationStr = '';
	$contentColorCode = '';
	$tagIdArr = array();
	$hasSender = false;
	$contentFolderTypeId = 0;

	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
	$baseIconFolderPath = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$folderIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconContentDetailFolderPath');
	$groupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailGroupPath'));
	$tagIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTagPath'));
	$sourceIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailSourcePath'));
	$locationIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailLocationPath'));
	$remindBeforeIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailRemindBeforePath'));
	$repeatDurationIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailRepeatDurationPath'));
	$attachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailAttachmentPath'));
	$cloudAttachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailAttachmentPath'));
	$isMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsMarkedPath'));
	$isUnMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsUnMarkedPath'));
	$isLockedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsLockedPath'));
	$isRestrictedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsRestrictedPath'));
	$contentSenderIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailSenderPath'));
	$contentColorIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailColorPath'));
	$printIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailPrintPath'));
	$editIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailEditPath'));
	$shareIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailSharePath'));
	$replyIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailReplyPath'));
	$deleteIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailDeletePath'));
	$profileIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailProfilePath'));
	$templateIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTemplatePath'));
	$contentTypeIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTypePath'));
	$infoIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailInfoPath'));
	$isRemovedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsRemovedPath'));
	$restoreIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailRestorePath'));
	$refreshIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailRefreshPath'));

	$contentPartActionIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailReplyPath'));
	$contentPartActionWithAttachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailReplyPath'));

	$typeRIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTypeRPath'));
	$typeAIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTypeAPath'));
	$typeCIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailTypeCPath'));
                
    $contentTypeR = Config::get("app_config.content_type_r");
    $contentTypeA = Config::get("app_config.content_type_a");
    $contentTypeC = Config::get("app_config.content_type_c");

    $contentIsRemoved = FALSE;

	if(isset($content))
	{
		$selFolderId = $content->folder_id;
		$selFolderName = $content->folder_name;
		$selSourceId = $content->source_id;
		$selSourceName = $content->source_name;
		$selTypeId = $content->content_type_id;
		$selTypeName = $content->type_name;
		$contentText = $content->content_text;
		$isMarked = $content->is_marked;
		$isLocked = $content->is_locked;
		$isShareEnabled = $content->is_share_enabled;
		$createTimeStamp = $content->create_timestamp;
		$fromTs = $content->fromTs;
		$fromDtTm = $content->fromDtTm;
		$toTs = $content->toTs;
		$toDtTm = $content->toDtTm;
		$contentColorCode = $content->color_code;
		$remindBeforeMillis = $content->remind_before_millis;
		$repeatDuration = $content->repeat_duration;
		$contentFolderTypeId = isset($content->folder_type_id) ? $content->folder_type_id : 0;
		
		$remindBeforeMillisStr = $content->remind_before_millis_str;
		$repeatDurationStr = $content->repeat_duration_str;
		
		if(isset($content->shared_by_email) && $content->shared_by_email != "")
		{
			$hasSender = true;
		}
		
		if($content->is_removed != 0)
		{
    		$contentIsRemoved = TRUE;
		}
		
		$folderArr[$selFolderId] = $selFolderName;
		
		if($selSourceId > 0)
			$sourceArr[$selSourceId] = $selSourceName;
			
		if(isset($contentTags) && count($contentTags) > 0)
		{
			foreach($contentTags as $tag)
			{
				$tagId = $tag->tag_id;
				$tagName = $tag->tag_name;
				
				if($selTagIdStr != "")
					$selTagIdStr .= ",";				
				$selTagIdStr .= $tagId;
				
				if($tagNameStr != "")
					$tagNameStr .= ",";				
				$tagNameStr .= $tagName;
				
				$tagArr[$tagId] = $tagName;
				
				array_push($tagIdArr, $tagId);
			}
		}
	}
	else
	{
		$contentColorCode = Config::get('app_config.default_content_color_code');
		$selFolderId = $defaultFolder['id'];
		$selFolderName = $defaultFolder['text'];
		
		$selTypeId = $defaultContentType['id'];
		$selTypeName = $defaultContentType['text'];
	}

	if($isView)
	{
		if(!isset($tagNameStr) || $tagNameStr == "")
		{
			$tagNameStr = "No tag(s) added";
		}
		else 
		{
			
		}
		if(!isset($selSourceName) || $selSourceName == "")
		{
			$selSourceName = "No source added";
		}
		
		if($selTypeId == Config::get('app_config.content_type_a'))
		{
			$remindBeforeMillisStr = "";
		}
		
		if($selTypeId == Config::get('app_config.content_type_r') || $selTypeId == Config::get('app_config.content_type_a'))
		{
			$repeatDurationStr = "";
		}
	}

	$editContentText = $contentText;
	if(!$isView && $sendAsReply == 1)
	{
		$editContentText = "";
	}

	$isContentModifiableByUser = false;
	if($isLocked == 0 && !$isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) )))
	{
		$isContentModifiableByUser = true;
	}

	$isContentPartRepliableByUser = false;
	if($isLocked == 0 && $isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) )))
	{
		$isContentPartRepliableByUser = true;
	}

	$isContentPartDeletableByUser = false;
	if($isLocked == 0 && $isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupIsAdmin == 1) )))
	{
		$isContentPartDeletableByUser = true;
	}


	if($isFolder)
	{
		$iconFile = $folderIconPath;
		$selGroupOrFolderText = $selFolderName;
		$selGroupOrFolderId = $selFolderId;
	}
	else
	{
		$iconFile = $groupIconPath;
		$selGroupOrFolderText = $groupName;
		$selGroupOrFolderId = $groupId;
	}

	if($selTypeId == $contentTypeR)
	{
		$selContentTypeIconPath = $typeRIconPath;
	}
	elseif($selTypeId == $contentTypeA)
	{
		$selContentTypeIconPath = $typeAIconPath;
	}
	elseif($selTypeId == $contentTypeC)
	{
		$selContentTypeIconPath = $typeCIconPath;
	}
@endphp
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif

<div id="addEditContentModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" id="divModalContentForSubView">
			{!! $detailsubViewHtml !!}
		</div>
	</div>
</div>
<div id="divDependencies"></div>
<div id="divForCloudAttachmentSelection"></div>
