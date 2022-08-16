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
	$contentIsCompleted = 0;

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
	$cloudAttachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailCloudAttachmentPath'));
	$isMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsMarkedPath'));
	$isUnMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsUnMarkedPath'));
	$isLockedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIsLockedPath'));
	$isRestrictedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsRestrictedPath'));
	$contentSenderIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailSenderPath'));
	$contentColorIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailColorPath'));
	$printIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailPrintPath'));
	$completeIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailCompletePath'));
	$incompleteIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailIncompletePath'));
	$downloadIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailDownloadPath'));
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
	$filterIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailVirtualFolderPath'));
	$viewInPopUpIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailOpenInPopUpPath'));
	$saveAsNoteIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconContentDetailSaveAsNote'));

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
		$selFolderId = $content->enc_folder_id;
		$selFolderName = $content->folder_name;
		$selSourceId = $content->enc_source_id;
		$selSourceName = $content->source_name;
		$selTypeId = $content->content_type_id;
		$selTypeName = $content->type_name;
		$contentText = $content->content_text;
		$isMarked = $content->is_marked;
		$isLocked = $content->is_locked;
		$contentIsCompleted = $content->is_completed;
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
				$tagId = $tag->enc_tag_id;
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
		if(isset($defaultFolder['id']))
		{
			$selFolderId = $defaultFolder['id'];
			$selFolderName = $defaultFolder['text'];
		}
		
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

	$isContentEditButtonVisible = false;
	if($isLocked == 0 && !$isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) )))
	{
		$isContentEditButtonVisible = true;
	}

	$isContentModifiableByUser = false;
	if($isLocked == 0 && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) ))) //  && !$isConversation
	{
		$isContentModifiableByUser = true;
	}

	$isContentPartRepliableByUser = false;
	if($isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) ))) // $isLocked == 0 && 
	{
		$isContentPartRepliableByUser = true;
	}

	$isContentPartDeletableByUser = false;
	if($isLocked == 0 && $isShareEnabled == 1 && $isConversation && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupIsAdmin == 1) )))
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

	function formatContentWithUrl($basicContentText)
	{
		$url_filter_protocol = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		$url_filter_www = "/(www)\.[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

		if (preg_match($url_filter_protocol, $basicContentText, $url)) 
		{
			return preg_replace($url_filter_protocol, "<a href='$url[0]' target='_blank'>$url[0]</a> ", $basicContentText);
		} 
		elseif (preg_match($url_filter_www, $basicContentText, $url)) 
		{
			return preg_replace($url_filter_www, "<a href='https://$url[0]' target='_blank'>$url[0]</a> ", $basicContentText);
		} 
		else 
		{
			return $basicContentText;
		}
	}

	$totalConversationCount = isset($contentConversationDetailsReversed) ? count($contentConversationDetailsReversed) : 0;

@endphp


@if($isView)
	<style>
		.detailsRow
		{
			margin-top: 12px !important;
			background: white;
			border-radius: 5px;
			
		}
	</style>
@endif
<style>
	.noselect {
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}

	.chat-operations 
	{
	    /*position: absolute;
	    z-index: 1000;*/
	    padding-top: 8px;
	    padding-right: 40px;
	    padding-bottom: 5px;
	    /*right: 3%;*/
	}

	.chat-operation-btn 
	{
        padding-right: 5px;
    	font-size: 16px;
    	color: #56ab2f;
	}

	a.chat-operation-btn:hover, a.chat-operation-btn:active, a.chat-operation-btn:focus 
	{
    	color: #56ab2f;
	}

	img.addEditActionBtnImg
	{
		width: 25px;
	}
	
	.grpNameRow
	{
		margin-top: 8px !important;
	}
	@media (min-width: 768px) {
		.modal-xl {
			width: 60%;
			max-width: 1600px;
		}
	}
	.image-radio {
	    cursor: pointer;
	    box-sizing: border-box;
	    -moz-box-sizing: border-box;
	    -webkit-box-sizing: border-box;
	    border: 4px solid transparent;
	    margin-bottom: 0;
	    outline: 0;
	}
	.image-radio input[type="radio"] {
	    display: none;
	}
	.image-radio-checked {
	}
	.image-radio .glyphicon {
	  	position: absolute;
		background-color: #8DC63F;
		padding: 3px;
		border-radius: 23px;
		top: 20px;
		right: 13px;
	}
	.image-radio-checked .glyphicon {
	  display: block !important;
	}
	.bsTitle {
		display: block;
	}
	.modalTextmodalText {

	}
	.selIcon {
		margin-top: -10px;
	}

	.bsDisabled
	{

	}

	.bsSelected
	{
		filter: sepia(79%) saturate(322%) brightness(55%) hue-rotate(180deg);
	}

	.content-modal-icon
	{
		height: 26px;
	}

	.modal {
	  overflow-y:auto;
	}
	
	.conversation-sender {
		padding-top: 10px;
		padding-bottom: 2px;
		font-size: 12px;

	  white-space: nowrap; 
	  overflow: hidden;
	  text-overflow: ellipsis;
	}
	
	.conversation-timestamp {
		padding-top: 2px;
		padding-bottom: 3px;
		font-size: 12px;
	}
	
	.conversation-text {
		border-radius: 10px;
    	padding: 5px;
    	border: 1px solid #efefef;
	}

	.modal-header {
		color: white;
		border-top-left-radius: 10px !important;
		border-top-right-radius: 10px !important;
	}

	.content-detail-modal-header {
		background-color: #56ab2f;

	}

	.content-detail-modal-body {
		background-color: #FFFFFF;	
	}

	.content-detail-modal-footer {
		background-color: #FFFFFF;		
	}

	.color-dot {
		height: 25px;
		width: 25px;
		border-radius: 50%;
		display: inline-block;
		border: 2px solid #BEC2CE;
	}

	.color-dot-disabled {
		margin-top: 5px;
	}

	.modal-content-close {
		color: #BEC2CE;
	}

	#spanGroupOrFolderText {
   		color: #858997;
	}

	.content-modal-button-icon {
		width: 15px;
	}

	.content-detail-time-row {
		margin-top: 10px;
		margin-bottom: 10px;
	}

	.content-text-div {
    	padding: 5px;
	    border-radius: 5px;
	    background-color: #FFFFFF;
	    box-shadow: 0px 0px 4px #00000020;
	    margin-bottom: 10px;

	}

	.conversation-reply-div {
    	margin-top: 7px;
    	padding: 5px;
	    border-radius: 5px;
	    background-color: #FFFFFF;
	    box-shadow: 0px 0px 4px #00000020;
	    margin-bottom: 5px;
    	border-left: #56ab2f 5px solid;
	}
	
	.conversation-reply-sender {
		color: #56ab2f;
		font-size: 12px;
		font-weight: 500;
		white-space: nowrap; 
		overflow: hidden;
		text-overflow: ellipsis;
	}
	
	.conversation-reply-text {
	}

	.conversation-reply-close-btn {
		position: absolute;
	    right: 50px;
	    top: 15px;
	    color: #858997;
	}

	.conversation-edt-dlt-details {
		font-size: 10px;
		font-style: italic;
	}

	.divConversationThread {
	}

	.btn-forPopUp {
	    position: absolute;
	    margin-top: -6px;
	    right: 25px;
	}

	.content-modal-button-link-icon {
		height: 25px;
    	filter: invert(1);
	}
</style>

@include('content.partialview._contentAddEditScripts')

<script type="text/javascript">
	
	$(document).ready(function(){
		setupBasicContentViewSUB();
	});

	var ctrlDown = false,
	ctrlKey = 17,
	cmdKey = 91,
	vKey = 86,
	cKey = 67;

	function setupBasicContentViewSUB()
	{
		renderTimeLayout({{ $selTypeId }});

		var jsTagIdArr = [];
		@foreach($tagIdArr as $tagId)
			jsTagIdArr.push('{{ $tagId }}');
		@endforeach
		
		$('[name="tagList"]').val( JSON.stringify( jsTagIdArr ) );
		$('[name="sourceId"]').val('{{ $selSourceId }}');
		$('[name="remindBeforeMillis"]').val({{ $remindBeforeMillis }});
		$('[name="repeatDuration"]').val("{{ $repeatDuration }}");
		$('[name="folderId"]').val('{{ $selFolderId }}');		
		$('[name="groupId"]').val('{{ $groupId }}');
		
		@if($isLocked == 0)
			$('#color-popup-over').popover({
				title: "Select Color",
				trigger: 'manual',
				html: "true",
				placement: "auto bottom",
				content: function() {
					var htmlStr = '<div class="row">';
					@foreach($colorCodes as $colorCode)
						var isSelected = '';
						var colorCodeForImg = "{{ $colorCode }}";
						var colorCode = '#'+colorCodeForImg;
						if(colorCode == contentColorCode) {
							isSelected = 'checked="checked"';
						}
						htmlStr += '<div class="col-sm-3 text-center">';
						htmlStr += '<label class="image-radio">';
						htmlStr += '<img class="img-responsive" src="'+colorCodeIconBasePath+'/'+colorCodeForImg+'.png" />';
						htmlStr += '<input type="radio" name="selColorCode" value="'+colorCode+'" ' + isSelected +'/>';
						htmlStr += '<i class="glyphicon glyphicon-ok hidden"></i>';
						htmlStr += '</label>';
						htmlStr += '</div>';
					@endforeach
					htmlStr += '</div>';
					return htmlStr;
				},
			})
			.on('shown.bs.popover', function() {
			    $(".image-radio").each(function(){
			        if($(this).find('input[type="radio"]').first().attr("checked")){
			            $(this).addClass('image-radio-checked');
			        }else{
			            $(this).removeClass('image-radio-checked');
			        }
			    });

			    $(".image-radio").on("click", function(e){
			        $(".image-radio").removeClass('image-radio-checked');
			        $(this).addClass('image-radio-checked');
			        var $radio = $(this).find('input[type="radio"]');
			        $radio.prop("checked",!$radio.prop("checked"));
					colorSelectionChanged();
			        e.preventDefault();
			    });
			})
			.click(function(e) {
				if(!isColorPopoverOpen) {
					$(this).popover('show');
					isColorPopoverOpen = true;
				}
			});
		@endif
			
		$('#addEditContentModal').on('hidden.bs.modal', function () {
			var contentDetDivName = "divAddEditContent";
		    $("#"+contentDetDivName).html("");
		});

		@if($isMarked == 1)
			btnMarkContentIcon.addClass('text-yellow');
		@else
			btnMarkContentIcon.removeClass('text-yellow');
		@endif
		
		@if($isLocked == 1 || $contentIsRemoved)
			$('#btnMarkContent').attr('disabled',true);
		@endif
		
		@if($isView)
			var fromTs = getDispDateTimeFromTimestamp({{ $fromTs }});
			$('#spanFromDt').html(fromTs);
			var toTs = getDispDateTimeFromTimestamp({{ $toTs }});
			$('#spanToDt').html(toTs);

			@if($isConversation && isset($contentConversationDetailsReversed))
				@foreach($contentConversationDetailsReversed as $i => $conversationObj)
					var sentAtTs = getDispDateTimeFromTimestamp({{ $conversationObj['sentAt'] }});
					$("#convoTs_{{ $i }}").html(sentAtTs);
					@if($conversationObj['isDeleted'] || $conversationObj['isEdited'])
					var editedOrDeletedAtTs = getDispDateTimeFromTimestamp({{ $conversationObj['editedOrDeletedAt'] }});
					$("#convoEdtDltTs_{{ $i }}").html(editedOrDeletedAtTs);
					@endif
					contentConversationJSArr.push({
						content: "{{ rawurlencode($conversationObj['content']) }}"
					});
				@endforeach
			@endif
		@endif
				
		@if(!$isView)
			
			$('#fromDtTm').datetimepicker({
				date: new Date({{ $fromTs }}),
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#fromTimeStamp").val(dtTimestamp);

				var selContentTypeId = $('#type_id').val();
				if(selContentTypeId == typeC)
				{
					const calcToDtTm = moment(dtTimestamp).add(1, 'hour');
					const calcToDtTmTs = calcToDtTm.unix() * 1000;

					$('#toTimeStamp').val(calcToDtTmTs);
					$('#toDtTm').data("DateTimePicker").date(calcToDtTm.format('DD/MM/YYYY HH:mm'));
				}
			});
			
			$('#fromTimeStamp').val(new Date({{ $fromTs }}).getTime());

			var consToDt;
			@if(isset($toTs))
				consToDt = new Date({{ $toTs }})
			@else
				consToDt = new Date().addHours(1);
			@endif
			
			$('#toDtTm').datetimepicker({				
				date: consToDt,
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#toTimeStamp").val(dtTimestamp);
			});
			
			$('#toTimeStamp').val(consToDt.getTime());

			// if (CKEDITOR.instances['content']) {
		 	//        CKEDITOR.remove(CKEDITOR.instances['content']);
			// }
			// CKEDITOR.replace('content');
			
          	ckEditObj = $(frmObj).find('#content').ckeditor({
				customConfig: 'config.js',
				uiColor: '{{ $contentColorCode }}',
				toolbar: 'simple',
				toolbarGroups: [
					{"name":"basicstyles","groups":["basicstyles"]}
				],
				removeButtons: 'Strike,Anchor,Styles,Specialchar,Superscript,Subscript,Copy',
				removePlugins: 'elementspath,copy,paste',
				keystrokes: [
					[ CKEDITOR.CTRL + cKey, false ]
				]
			});

			CKEDITOR.instances.content.on('paste copy', function(evt) {
				evt.cancel();
			});

		    $(document).bind('cut copy paste', function (e) {
		        e.preventDefault();
		    });
			
			$('#templateId').val(null).trigger('change');
			
			@if($currOrgId == "")
				$('#divTemplate').css("display", "none");
			@endif
			
			$(frmObj).submit(function(e){
	            e.preventDefault();

	            var performShare = 0;
	            
			});

			@if(isset($mappedCloudAttachmentDetailsArr) && count($mappedCloudAttachmentDetailsArr) > 0)
				@foreach($mappedCloudAttachmentDetailsArr as $mappedCloudAttachmentDetails)

					var attachmentId = "{{ isset($mappedCloudAttachmentDetails->id) ? $mappedCloudAttachmentDetails->id : 0 }}";
					var attachmentFileSize = "{{ $mappedCloudAttachmentDetails->fileSizeKB }}";
					var attachmentFileName = "{{ $mappedCloudAttachmentDetails->fileName }}";
					var cloudStorageTypeId = "{{ $mappedCloudAttachmentDetails->cloudStorageTypeId }}";
					var cloudFileUrl = "{{ $mappedCloudAttachmentDetails->fileStorageUrl }}";
					var cloudFileId = "{{ $mappedCloudAttachmentDetails->fileId }}";

					var mappedFileDetailsObj = {
						id: attachmentId * 1,
						fileSizeKB: attachmentFileSize * 1,
						fileName: attachmentFileName,
						cloudStorageTypeId: cloudStorageTypeId * 1,
						fileStorageUrl: cloudFileUrl,
						fileId: cloudFileId
					};

					// console.log('mappedFileDetailsObj : ', mappedFileDetailsObj)

					addCloudFileRow(mappedFileDetailsObj);
				@endforeach
			@endif

	    @endif

	    toggleSourceHasSelection();
	    toggleTagHasSelection();
	    toggleLocationHasSelection();
	    toggleRemindBeforeHasSelection();
	    toggleRepeatHasSelection();
	}
	
</script>
{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveContent']) !!}
	<div class="modal-header content-detail-modal-header">
		@if($forPopUp == 0)
			@if($isView)
        		<button type="button" class="btn btn-link btn-forPopUp" onclick="loadAppuserContentDetailsInPopUp('{{ $orgKey }}', '{{ $isFolderFlag }}', '{{ $id }}', '1', '{{ $listCode }}', '{{ $searchStr }}');" data-toggle="tooltip" title="View Content in Pop Up">
		    		<img src="{{ $viewInPopUpIconPath }}" class="content-modal-button-link-icon"/>
		    	</button>
				&nbsp;&nbsp;
	   		@endif
			<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="toggleAddEditContentButton(1);">
				&times;
			</button>
		@endif
		<h4 class="modal-title">
			<!-- {{ $page_description or null }} -->
	    	&nbsp;
	    	<button type="button" id="btnMarkContent" class="btn btn-default btn-sm" onclick="toggleContentStatus();">
	    		@if($isMarked == 1)
					<img id="contentIsMarkedIcon" class="content-modal-button-icon" src="{{ $isMarkedIconPath }}" data-toggle="tooltip" title="Unmark Content"/>
				@else
					<img id="contentIsMarkedIcon" class="content-modal-button-icon" src="{{ $isUnMarkedIconPath }}" data-toggle="tooltip" title="Mark Content"/>
				@endif
			</button>

			@if($isView)
				&nbsp;&nbsp;
        		<button type="button" class="btn btn-default btn-sm" onclick="reloadContentDetailsView();" data-toggle="tooltip" title="Refresh Content">
		    		<img src="{{ $refreshIconPath }}" class="content-modal-button-icon"/>
		    	</button>
				@if($forPopUp == 0)
					<!-- &nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="loadAppuserContentDetailsInPopUp('{{ $orgKey }}', '{{ $isFolderFlag }}', '{{ $id }}', '1', '{{ $listCode }}', '{{ $searchStr }}');" data-toggle="tooltip" title="View Content in Pop Up">
			    		<img src="{{ $viewInPopUpIconPath }}" class="content-modal-button-icon"/>
			    	</button> -->
		   		@endif
		    @endif
			
			@if($isView && !$contentIsRemoved)
				&nbsp;&nbsp;
        		<button type="button" class="btn btn-default btn-sm" onclick="performPrintContent('{{ $isFolderFlag }}', '{{ $id }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Print Content">
		    		<img src="{{ $printIconPath }}" class="content-modal-button-icon"/>
		    	</button>
		    	@if($contentIsCompleted == 1)
					&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="performMarkContentAsIncomplete('{{ $isFolderFlag }}', '{{ $id }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Mark as Incomplete">
			    		<img src="{{ $incompleteIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @else
					&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="performMarkContentAsComplete('{{ $isFolderFlag }}', '{{ $id }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Mark as Complete">
			    		<img src="{{ $completeIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @endif
				@if($selTypeId == $contentTypeC)
					&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="dowloadCalendarContentAsIcsFile();" data-toggle="tooltip" title="Download Calendar Entry">
			    		<img src="{{ $downloadIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @endif
				@if($isContentModifiableByUser)
					@if($isContentEditButtonVisible)
		        		&nbsp;&nbsp;
						@if($forPopUp == 0)
			        		<button id="btnEditContent" type="button" class="btn btn-default btn-sm" onclick="showContentDetails('{{ $id }}', '{{ $isFolderFlag }}', 0, 0, '{{ $selGroupOrFolderId }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Edit Content">
					    		<img src="{{ $editIconPath }}" class="content-modal-button-icon"/>
					    	</button>
					    @else
			        		<button id="btnEditContent" type="button" class="btn btn-default btn-sm" onclick="loadAppuserContentDetailsInPopUp('{{ $orgKey }}', '{{ $isFolderFlag }}', '{{ $id }}', '0', '{{ $listCode }}', '{{ $searchStr }}');" data-toggle="tooltip" title="Edit Content">
					    		<img src="{{ $editIconPath }}" class="content-modal-button-icon"/>
					    	</button>
				   		@endif
				    @endif
				    @if($hasSender && $selTypeId == $contentTypeIdA)
						&nbsp;&nbsp;
		        		<button type="button" class="btn btn-default btn-sm" onclick="showContentDetails('{{ $id }}', '{{ $isFolderFlag }}', 0, 1, '{{ $selGroupOrFolderId }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Reply To Content">
			    			<img src="{{ $replyIconPath }}" class="content-modal-button-icon"/>
				    	</button>
				    @endif
		    	@endif
				@if($isView && $isShareEnabled == 0)
			    	&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" title="Restricted Content">
			    		<img src="{{ $isRestrictedIconPath }}" class="content-modal-button-icon thm-gray"/>
			    	</button>
				@endif
				@if($isView && $isLocked == 1)
			    	&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" title="Locked Content">
			    		<img src="{{ $isLockedIconPath }}" class="content-modal-button-icon"/>
			    	</button>
				@endif	
				&nbsp;&nbsp;
        		<button type="button" class="btn btn-default btn-sm" onclick="loadContentInfo('{{ $isFolderFlag }}', '{{ $id }}', '{{ $isFavoritesTab }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Content Info">
		    		<img src="{{ $infoIconPath }}" class="content-modal-button-icon"/>
		    	</button>
			    @if($isShareEnabled == 1) 
			    	&nbsp;&nbsp;
			    	<button class="btn btn-default btn-sm" type="button" onclick="shareContent();"><img src="{{ $shareIconPath }}" class="content-modal-button-icon" data-toggle="tooltip" title="Share Content"/></button>
			    @endif
				@if($isFolder || (!$isFolder && ($groupIsAdmin == 1)))
					&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="confirmAndDeleteContent('{{ $id }}', '{{ $isFolderFlag }}', '{{ $isFavoritesTab }}', 0, '{{ $orgKey }}', '{{ $selGroupOrFolderId }}');" data-toggle="tooltip" title="Delete Content">
			    		<img src="{{ $deleteIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @endif

		    	<!-- @if($isConversation && $hasSender)
			    	&nbsp;&nbsp;
	             	<button type="button" class="btn btn-default btn-sm" onclick="createVirtualFolderFromContent('{{ $orgKey }}');" data-toggle="tooltip" title="Create Virtual Folder">
	             		<img src="{{ $filterIconPath }}" class="content-modal-button-icon"/>
	             	</button>
	            @endif -->

	            @if($isConversation)
			    	&nbsp;&nbsp;
			    	<button id="divTemplate" type="button" class="btn btn-default btn-sm" onclick="openTemplateSelectionModal();" data-toggle="tooltip" title="Template">
			    		<img src="{{ $templateIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @endif

				@if(!$isFolder)
					&nbsp;&nbsp;
	        		<button type="button" class="btn btn-default btn-sm" onclick="saveGroupContentAsFolderNote('{{ $orgKey }}', '{{ $isFolderFlag }}', '{{ $selGroupOrFolderId }}');" data-toggle="tooltip" title="Save as Note">
			    		<img src="{{ $saveAsNoteIconPath }}" class="content-modal-button-icon"/>
			    	</button>
			    @endif
		    @elseif($contentIsRemoved)
				&nbsp;&nbsp;
        		<button type="button" class="btn btn-default btn-sm" onclick="confirmAndDeleteContent('{{ $id }}', '{{ $isFolderFlag }}', '{{ $isFavoritesTab }}', 1, '{{ $orgKey }}');" data-toggle="tooltip" title="Delete Content Permanently">
		    		<img src="{{ $deleteIconPath }}" class="content-modal-button-icon"/>
		    	</button>
				&nbsp;&nbsp;
        		<button type="button" class="btn btn-default btn-sm" onclick="confirmAndRestoreDeletedContent('{{ $id }}', '{{ $isFolderFlag }}', '{{ $isFavoritesTab }}', '{{ $orgKey }}');" data-toggle="tooltip" title="Restore Content">
		    		<img src="{{ $restoreIconPath }}" class="content-modal-button-icon"/>
		    	</button>
		    @else
		    	&nbsp;&nbsp;
		    	<button type="button" class="btn btn-default btn-sm" onclick="openContentTypeSelectionModal();" data-toggle="tooltip" title="Content Type">
		    		<img id="contentTypeIcon" src="{{ $selContentTypeIconPath }}" class="content-modal-button-icon"/>
		    	</button>
				@if($id == "")
			    	&nbsp;&nbsp;
			    	<button type="button" class="btn btn-default btn-sm" onclick="openProfileSelectionModal();" data-toggle="tooltip" title="Profile">
			    		<img src="{{ $profileIconPath }}" class="content-modal-button-icon"/>
			    	</button>
				@endif		
		    	&nbsp;&nbsp;
		    	<button type="button" class="btn btn-default btn-sm" onclick="openGroupOrFolderSelectionModal();" data-toggle="tooltip" title="Content Belongs To">
					<img id="groupOrFolderIcon" class="content-modal-button-icon" src="{{ $iconFile }}"/>
					<span class="modalTextmodalText" id="spanGroupOrFolderText">{!! $selGroupOrFolderText !!}</span>
				</button>
		    	&nbsp;&nbsp;
		    	<button id="divTemplate" type="button" class="btn btn-default btn-sm" onclick="openTemplateSelectionModal();" data-toggle="tooltip" title="Template">
		    		<img src="{{ $templateIconPath }}" class="content-modal-button-icon"/>
		    	</button>
			    	
		    	<!--<div class="col-md-offset-6 col-md-6" id="divTemplate">
					&nbsp;&nbsp;
					<a class="btn btn-link" onclick="openTemplateSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_template.png' }}"/></a>		
		    	</div>	-->		    
			@endif		
		</h4>
	</div>
	{{-- _CHANGES --}}
	<div class="modal-body content-detail-modal-body">
		<label>Title Note</label>
		<input class="form-group" type="text" value="" name='title_note'>
	</div>
	{{-- _CHANGES --}}
	<div class="modal-body content-detail-modal-body">	
    	{!! Form::hidden('templateId', null, ['id' => 'templateId']) !!}
		{!! Form::hidden('isMarked', $isMarked, ['id' => 'isMarked']) !!}			
		{!! Form::hidden('colorCode', $contentColorCode, ['id' => 'colorCode']) !!}		
		{!! Form::hidden('isLocked', $isLocked, ['id' => 'isLocked']) !!}		
		{!! Form::hidden('isShareEnabled', $isShareEnabled, ['id' => 'isShareEnabled']) !!}	
		{!! Form::hidden('syncWithCloudCalendarGoogle', 0, ['id' => 'syncWithCloudCalendarGoogle']) !!}	
		{!! Form::hidden('syncWithCloudCalendarOnedrive', 0, ['id' => 'syncWithCloudCalendarOnedrive']) !!}

		@if(!$isView)
			{!! Form::hidden('folderId', $selFolderId, ['id' => 'folderId']) !!}
			{!! Form::hidden('groupId', $groupId, ['id' => 'groupId']) !!}	
			{!! Form::hidden('contentType', $selTypeId, [ 'id' => 'type_id' ]) !!}
		@else
			{!! Form::hidden('groupContentSaveToFolderId', 0, ['id' => 'groupContentSaveToFolderId']) !!}
		@endif
		<div class="row content-detail-time-row"  onclick="openContentDateTimeModificationModal();">						
			<div class="col-md-6">
				<div class="form-group detailsRow" id="divFromDateTime">
					<div class="col-md-offset-1 col-md-11">
		                @if(!$isView)
							<div class='input-group date' id='fromDtTm'>
			                    <input type='text' class="form-control" />
			                    <span class="input-group-addon">
			                        <span class="glyphicon glyphicon-calendar"></span>
			                    </span>
           	 				</div>
							{!! Form::hidden('fromTimeStamp', $fromTs, ['id' => 'fromTimeStamp']) !!}
						@else
							<span class="modalTextmodalText" id="spanFromDt" style="cursor: pointer;"></span>
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group detailsRow" id="divToDateTime">
					<div class="col-md-offset-1 col-md-11">
						@if(!$isView)
							<div class='input-group date' id='toDtTm'>
			                    <input type='text' class="form-control" />
			                    <span class="input-group-addon">
			                        <span class="glyphicon glyphicon-calendar"></span>
			                    </span>
           	 				</div>
							{!! Form::hidden('toTimeStamp', $toTs, ['id' => 'toTimeStamp']) !!}
						@else
							<span class="modalTextmodalText" id="spanToDt" style="cursor: pointer;"></span>
						@endif
					</div>
				</div>
			</div>
		</div>				
		@if($isView)
			@if($isConversation)
				<div class="divConversationThread">
					@foreach($contentConversationDetailsReversed as $i => $conversationObj)
						@php
						$senderStr = $conversationObj['sender'];
						$baseConvContentStr = $conversationObj['content'];
						$baseConvContentStrStripped = $conversationObj['contentStripped'];
						$decodedConvContentStr = ($baseConvContentStrStripped);
						$isForwarded = $conversationObj['isForwarded'];
						$isEdited = $conversationObj['isEdited'];
						$isDeleted = $conversationObj['isDeleted'];
						$hasReply = $conversationObj['hasReply'];
						$isUserMsgSender = $conversationObj['isUserMsgSender'];
						$alignmentForMsg = $isUserMsgSender == 1 ? 'right' : 'left';
						$consConvIndex = ($totalConversationCount - 1) - $i;
						@endphp
						<div class="row">	
							<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
								<div class="conversation-sender" align="{{ $alignmentForMsg }}">
									@if(!$isForwarded)
										<img style="height: 20px;" src="{{ $contentSenderIconPath }}" />
										{!! $senderStr !!}
									@endif
								</div>
								@if($hasReply)
									@php
									$replySenderStr = $conversationObj['replySender'];
									$replyConvContentStr = $conversationObj['replyContent'];
									@endphp
									<div class="conversation-reply-div" align="{{ $alignmentForMsg }}">
										<div class="conversation-reply-sender">
											{!! $replySenderStr !!}
										</div>
										<div class="conversation-reply-text">
											{!! formatContentWithUrl($replyConvContentStr) !!}
										</div>
									</div>
								@endif
								<div class="conversation-text divContentText noselect" style="background-color: {{ $contentColorCode }}" align="{{ $alignmentForMsg }}">
									{!! formatContentWithUrl($baseConvContentStr) !!}
								</div>
								@if($isDeleted || $isEdited)
									@php
									$editedOrDeletedByStr = $conversationObj['editedOrDeletedBy'];
									$editedOrDeletedConvContentStr = $conversationObj['editOrDeleteStr'];
									if($isDeleted)
									{
										$editedOrDeletedActionStr = 'Deleted';												
									}
									else
									{
										$editedOrDeletedActionStr = 'Edited';
									}
									@endphp
									@if($isDeleted)
										<div class="conversation-text" style="background-color: {{ $contentColorCode }}">
											{!! formatContentWithUrl($editedOrDeletedConvContentStr) !!}
										</div>
									@endif
									<div class="conversation-edt-dlt-details" align="{{ $alignmentForMsg }}">
										{{ $editedOrDeletedActionStr }} on <span id="convoEdtDltTs_{{ $i }}"></span> by {!! $editedOrDeletedByStr !!}
									</div>
								@endif
								<div class="conversation-timestamp" align="right" id="convoTs_{{ $i }}"></div>
							</div>
							@if(!$isDeleted && $isContentPartRepliableByUser && !$isForwarded && !$contentIsRemoved && $contentFolderTypeId == 0)
								<div class="chat-operations" align="right"> <!-- class = "" -->
									@if($isContentPartRepliableByUser)
										<a href="javascript:void(0)" onclick="setUpConversationPartReply('{{ $i }}', '{{ $consConvIndex }}', '{{ $decodedConvContentStr }}', '{{ $senderStr }}')" class="chat-operation-btn" data-toggle="tooltip" title="Reply">
											<i class="fa fa-reply"></i>
										</a>
										@if($isUserMsgSender == 1 && $isShareEnabled == 1)
											<a href="javascript:void(0)" onclick="setUpConversationPartEdit('{{ $i }}', '{{ $consConvIndex }}', '{{ $decodedConvContentStr }}')" class="chat-operation-btn" data-toggle="tooltip" title="Edit">
												<i class="fa fa-edit"></i>
											</a>
										@endif
									@endif
									@if($isContentPartDeletableByUser && ($isUserMsgSender == 1 || (!$isFolder && ($groupIsAdmin == 1))))
										<a href="javascript:void(0)" onclick="confirmAndPerformConversationPartDelete('{{ $i }}', '{{ $consConvIndex }}', '{{ $baseConvContentStrStripped }}')" class="chat-operation-btn" data-toggle="tooltip" title="Delete">
											<i class="fa fa-trash"></i>
										</a>
									@endif
								    @if($isShareEnabled == 1) 
										<a href="javascript:void(0)" onclick="confirmAndPerformConversationPartForward('{{ $i }}', '{{ $consConvIndex }}', '{{ $baseConvContentStrStripped }}')" class="chat-operation-btn" data-toggle="tooltip" title="Forward">
											<i class="fa fa-share"></i>
										</a>
										<a href="javascript:void(0)" onclick="confirmAndPerformConversationPartCopy('{{ $i }}', '{{ $consConvIndex }}', '{{ $baseConvContentStrStripped }}')" class="chat-operation-btn" data-toggle="tooltip" title="Copy">
											<i class="fa fa-copy"></i>
										</a>
								    @endif
								</div>
							@endif
						</div>
					@endforeach
					@if(!$contentIsRemoved && $isContentPartRepliableByUser && $contentFolderTypeId == 0)
						<div class="row">	
							<div class="col-md-12" id="newConversationReplyDiv" style="padding-left: 40px; padding-right: 40px; margin-bottom: 10px; display: none;">
								<div class="conversation-reply-div">
									<a href="javascript:void(0)" onclick="resetConversationPartOperationDependencies()" class="conversation-reply-close-btn">
										<i class="fa fa-times"></i>
									</a>
									<div class="conversation-reply-sender" id="newConversationReplySender">
									</div>
									<div class="conversation-reply-text" id="newConversationReplyContent">
									</div>
								</div>
							</div>
							<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
								<div class="input-group input-group-sm">
									<!-- <input type="text" id="contentPartText" class="form-control input-sm" placeholder="Type a message" autocomplete="off" value=""> -->
									<textarea id="contentPartText" class="form-control input-sm" placeholder="Type a message" autocomplete="off">{{ $preloadConversationReplyText }}</textarea>
									<input type="hidden" id="contentPartIndex" value="-1">
									<input type="hidden" id="isEditOp" value="0">
									<input type="hidden" id="isReplyOp" value="0">
									<span class="input-group-btn">
										<button id="" class="btn btn-success btn-sm" type="button" onclick="confirmAndPerformConversationPartEditOrReplyOperation();"data-toggle="tooltip" title="Reply">
											<i class="fa fa-reply"></i>
										</button>
										<button id="" class="btn btn-warning btn-sm" type="button" onclick="confirmAndPerformConversationPartReplyWithAttachmentOperation();" data-toggle="tooltip" title="Edit">
											<i class="fa fa-share"></i>
										</button>
									</span>
								</div>
							</div>
						</div>
					@endif
				</div>
			@else
				<div class="row">	
					<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
						<div class="content-text-div divContentText noselect" style="background-color: {{ $contentColorCode }}">
							<div class="">
								{!! formatContentWithUrl($contentText) !!}
							</div>
						</div>
					</div>
				</div>
			@endif
		@else
			<div class="row">	
				<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
					<div class="form-group detailsRow divContentText" id="divContentText" style="background-color: {{ $contentColorCode }}">
	                	{{ Form::textarea('content', $editContentText, ['class' => 'form-control ckeditor', 'id' => 'content']) }}
					</div>
				</div>
			</div>
		@endif
		
		@if((isset($contentAttachments) && count($contentAttachments) > 0) || !$isView)
			@include('content.partialview._attachmentListHeader')
		@endif
		<div id="divAttachments"></div>
		@if($isView || $sendAsReply == 0)
			@if(isset($contentAttachments) && count($contentAttachments) > 0)
				@foreach($contentAttachments as $attObj)
					@php
						$attachmentId = $attObj->enc_content_attachment_id;
						$attachmentFilename = $attObj->filename;
						$attachmentUrl = $attObj->url;
						$attCloudStorageTypeId = $attObj->att_cloud_storage_type_id;
						$attCloudFileUrl = $attObj->cloud_file_url;
					@endphp
					@include('content.partialview._attachmentListRow')
				@endforeach
			@endif
		@endif
				
		{!! Form::hidden('id', $id) !!}
		{!! Form::hidden('isFolderFlag', $isFolderFlag, ['id' => 'isFolderFlag']) !!}
		{!! Form::hidden('sendAsReply', $sendAsReply, ['id' => 'sendAsReply']) !!}
		{!! Form::hidden('conOrgId', $conOrgId, ['id' => 'conOrgId']) !!}
		{!! Form::hidden('createTimeStamp', $createTimeStamp, ['id' => 'createTimeStamp']) !!}
		{!! Form::hidden('updateTimeStamp', $updateTimeStamp, ['id' => 'updateTimeStamp']) !!}
		{!! Form::hidden('attachmentCnt', $attachmentCnt, ['id' => 'attachmentCnt']) !!}
		{!! Form::hidden('removeAttachmentIdArr[]', NULL, ['id' => 'removeAttachmentIds']) !!}
	</div>
	<div class="modal-footer content-detail-modal-footer">
		<div class="col-md-8">
			<div class="row">	
				<div class="col-md-12" align="left">
					<a class="btn btn-link" onclick="openTagSelectionModal();"><img class="addEditActionBtnImg" id="imgBsTag" src="{{ $tagIconPath }}" data-toggle="tooltip" title="Tag(s)"/></a>
					{!! Form::hidden('tagList', NULL, ['id' => 'tagList']) !!}

					<a class="btn btn-link" onclick="openSourceSelectionModal();"><img class="addEditActionBtnImg" id="imgBsSource" src="{{ $sourceIconPath }}" data-toggle="tooltip" title="Source"/></a>
					{!! Form::hidden('sourceId', NULL, ['id' => 'sourceId']) !!}

					<a class="btn btn-link" onclick="openLocationSelectionModal();"><img class="addEditActionBtnImg" id="imgBsLocation" src="{{ $locationIconPath }}" data-toggle="tooltip" title="Location"/></a>
					{!! Form::hidden('locationId', NULL) !!}

					<a class="btn btn-link" onclick="openRemindBeforeSelectionModal();"><img class="addEditActionBtnImg" id="imgBsRemindBefore" src="{{ $remindBeforeIconPath }}" data-toggle="tooltip" title="Remind Before"/></a>
					{!! Form::hidden('remindBeforeMillis', NULL) !!}

					<a class="btn btn-link" onclick="openRepeatDurationSelectionModal();"><img class="addEditActionBtnImg" id="imgBsRepeat" src="{{ $repeatDurationIconPath }}" data-toggle="tooltip" title="Repeat Duration"/></a>
					{!! Form::hidden('repeatDuration', NULL) !!}

					@if($isLocked == 0 && !$contentIsRemoved)
						<a class="btn btn-link" href="javascript:void(0);" id="color-popup-over" data-toggle="popover">
							<span class="color-dot" id="faColorCodeIcon" style="background-color: {{ $contentColorCode }};" data-toggle="tooltip" title="Color"></span>
						</a>
					@else
						<a class="btn btn-link" href="javascript:void(0);">
							<span class="color-dot color-dot-disabled" style="background-color: {{ $contentColorCode }};" data-toggle="tooltip" title="Color"></span>
						</a>
					@endif

					<a class="btn btn-link" onclick="openAttachmentSelectionModal();" data-toggle="tooltip" title="Add Attachment"><img class="addEditActionBtnImg" src="{{ $attachmentIconPath }}"/></a>
					@if($appHasCloudStorage == 1)
						@if(isset($cloudStorageTypeList) && count($cloudStorageTypeList) > 0 && !$isView)
							@foreach($cloudStorageTypeList as $cloudStorageType)
								@php
								$cloudStorageTypeCode = $cloudStorageType['code'];
								$cloudStorageTypeName = $cloudStorageType['name'];
								$cloudStorageTypeIconUrl = $cloudStorageType['iconUrl'];
								$cloudStorageTypeIsLinked = $cloudStorageType['isLinked'];
								@endphp
								<a class="btn btn-link" onclick="openCloudAttachmentSelectionModal('{{ $cloudStorageTypeIsLinked }}', '{{ $cloudStorageTypeCode }}', '{{ $cloudStorageTypeName }}');" data-toggle="tooltip" title="Select file from {{ $cloudStorageTypeName }}">
									<img class="addEditActionBtnImg" src="{{ $cloudStorageTypeIconUrl }}"/>
								</a>
							@endforeach
						@endif
					@endif
				</div>
			</div>	
		</div>
		<div class="col-md-4">
			<div class="row">
				@if(!$isView)
					<div class="col-md-12" align="right">
						{!! Form::button('Save', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'submitContentSaveForm("SAVE")']) !!}

						{!! Form::button('Forward', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'submitContentSaveForm("FORWARD")']) !!}
					</div>
				@endif
			</div>
		</div>
	</div>
{!! Form::close() !!}