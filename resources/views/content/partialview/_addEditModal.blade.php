@php	
$startTagReplacement = '<span class="searchHighlighted">';
$endTagReplacement = '</span>';
				
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
$isShareEnabled = 0;
$remindBeforeMillis = 0;
$remindBeforeMillisStr = '';
$repeatDuration = '';
$repeatDurationStr = '';
$contentColorCode = '';
$tagIdArr = array();
$hasSender = false;

$assetBasePath = Config::get('app_config.assetBasePath');
$baseIconFolderPath = asset($assetBasePath.Config::get('app_config.appIconBaseFolderPath'))."/";

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
	
	$remindBeforeMillisStr = $content->remind_before_millis_str;
	$repeatDurationStr = $content->repeat_duration_str;
	
	if(isset($content->shared_by_email) && $content->shared_by_email != "")
	{
		$hasSender = true;
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

$assetBasePath = Config::get('app_config.assetBasePath'); 
@endphp
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
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
	img.addEditActionBtnImg
	{
		width: 45%;
	}
	
	.grpNameRow
	{
		margin-top: 8px !important;
	}
	@media (min-width: 768px) {
	  .modal-xl {
	    width: 90%;
	   max-width:1600px;
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
	   /* border-color: #4783B0;*/
	}
	.image-radio .glyphicon {
	  	position: absolute;
		color: #fff;
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
		color: #fff;
		display: block;
	}
	.modalTextmodalText {
		color: #fff;
	}
	.selIcon {
		margin-top: -10px;
	}
	.modalBackdrop
	{
		background-color: #333;
	}
	.bsDisabled
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
		color: #fff;
		font-size: 12px;
	}
	
	.conversation-timestamp {
		padding-top: 2px;
		padding-bottom: 3px;
		color: #fff;
		font-size: 12px;
	}
	
	.conversation-text {
		border-radius: 10px;
    	padding-left: 5px;
	}
</style>

<script>
	var frmObj = '#frmSaveContent';
	var typeR = "{{ Config::get('app_config.content_type_r') }}";
	var typeA = "{{ Config::get('app_config.content_type_a') }}";
	var typeC = "{{ Config::get('app_config.content_type_c') }}";
	var btnMarkContent = $("#btnMarkContent");
	var btnMarkContentIcon = $("#btnMarkContentIcon");
	var remFileElems = [];
	var addAttHtml = "{{ $addAttachmentViewHtml }}";
	var ckEditObj;
	var contentColorCode = "{{ $contentColorCode }}";
	var colorCodeIconBasePath = "{{ $colorCodeIconBasePath }}";
	var isColorPopoverOpen = false;
	$(document).ready(function(){
		renderTimeLayout({{ $selTypeId }});
		
		var jsTagIdArr = [];
		@foreach($tagIdArr as $tagId)
			jsTagIdArr.push({{ $tagId }});
		@endforeach
		
		$('[name="tagList"]').val( JSON.stringify( jsTagIdArr ) );
		$('[name="sourceId"]').val({{ $selSourceId }});
		$('[name="remindBeforeMillis"]').val({{ $remindBeforeMillis }});
		$('[name="repeatDuration"]').val("{{ $repeatDuration }}");
		
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
				// add/remove checked class
			    $(".image-radio").each(function(){
			        if($(this).find('input[type="radio"]').first().attr("checked")){
			            $(this).addClass('image-radio-checked');
			        }else{
			            $(this).removeClass('image-radio-checked');
			        }
			    });

			    // sync the input state
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
		
		@if($isLocked == 1)
			$('#btnMarkContent').attr('disabled',true);
		@endif
		
		@if($isView)
			var fromTs = getDispDateTimeFromTimestamp({{ $fromTs }});
			$('#spanFromDt').html(fromTs);
			var toTs = getDispDateTimeFromTimestamp({{ $toTs }});
			$('#spanToDt').html(toTs);

			@if($isConversation && isset($contentConversationDetails))
				@foreach($contentConversationDetails as $i => $conversationObj)
					var sentAtTs = getDispDateTimeFromTimestamp({{ $conversationObj['sentAt'] }});
					$("#convoTs_{{ $i }}").html(sentAtTs);
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
			});
			
			$('#fromTimeStamp').val(new Date({{ $fromTs }}).getTime());
			
			$('#toDtTm').datetimepicker({				
				date: new Date({{ $toTs }}),
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#toTimeStamp").val(dtTimestamp);
			});
			
			$('#toTimeStamp').val(new Date({{ $toTs }}).getTime());
			
          	ckEditObj = $(frmObj).find('#content').ckeditor({
				customConfig : 'config.js',
				toolbar : 'simple',
				toolbarGroups: [
					{"name":"basicstyles","groups":["basicstyles"]}
				],
				removeButtons: 'Strike,Anchor,Styles,Specialchar,Superscript,Subscript',
				removePlugins : 'elementspath'
			});
			
			/*$('#template_id').css('width', '100%');
			$('#template_id').select2({
				placeholder: "Select Template",
				allowClear: true,
				dataType: 'json',
				ajax: {
					url: "{!!  url('/appuserOrganizationTemplateList') !!}",
					type: 'POST',
					quietMillis: 1000, 
					data: function (params) {
						return {
							searchStr: params.term,
							page: params.page || 1,
							userId: getCurrentUserId(),
							orgId: getCurrentOrganizationId(),
							loginToken: getCurrentLoginToken(),
							selOrgId: $("#org_id").val(),
						};
					},
					processResults: function (data, params) {
						return {
							results: data.results,
						};		
					},
				},
			}).on("select2:select", function(e) { 
				tempId = $(this).val();
				selectAndLoadTemplate(tempId);
			});*/
			$('#templateId').val(null).trigger('change');
			
			@if($currOrgId == "")
				$('#divTemplate').css("display", "none");
			@endif
			
			$(frmObj).submit(function(e){
	            e.preventDefault();
	            
				validateAndSubmitContentForm();
			});
	    @endif
	});
	
	function getAttachmentCount()
	{
		$("#removeAttachmentIds").val(remFileElems);
		
		var attCount = 0;
		$( ".attachment_file" ).each(function( index ) {
			var filename = $(this).val();
			if(filename != "")
			{
				attCount++;
			}
		});
		$( ".attachment_file_existing" ).each(function( index ) {
			attCount++;
		});
		$("#attachmentCnt").val(attCount);
		return attCount;
	}

	function validateAndSubmitContentForm()
	{
		var contentType = $('input[name="contentType"]').val();
		var folderId = $('input[name="folderId"]').val();
		var groupId = $('input[name="groupId"]').val();
		var content = CKEDITOR.instances.content.getData();
		var isFolderFlag = $('input[name="isFolderFlag"]').val();
		var fromTimeStamp = $('input[name="fromTimeStamp"]').val();
		var toTimeStamp = $('input[name="toTimeStamp"]').val();
		
		fromTimeStamp = fromTimeStamp*1;
		toTimeStamp = toTimeStamp*1;
		
		var isValid = true, errorMsg = '';
		if(contentType*1 <= 0)
		{
			isValid = false;
			errorMsg = 'Content Type is required';
		}
		else if((isFolderFlag == 1 && folderId <= 0) || (isFolderFlag == 0 && groupId <= 0))
		{
			isValid = false;
			if(isFolderFlag == 1)
			{
				errorMsg = 'Folder is required';
			}
			else
			{
				errorMsg = 'Group is required';
			}
		}
		else if(!content || content.trim() == "")
		{
			isValid = false;
			errorMsg = 'Content is required';
		}
		else if((contentType == typeR && fromTimeStamp <= 0) || (contentType == typeC && ((fromTimeStamp <= 0) || (toTimeStamp <= 0) || (toTimeStamp < fromTimeStamp))))
		{
			isValid = false;
			errorMsg = 'Time is invalid';
		}
		else
		{
			$( ".attachment_file" ).each(function( index ) {
					/*'attachment_file[]': {
	                    validators: {
	                    	selector: '.attachment_file',
	                    	file: {
			                    type: '{{ $attachmentType }}',
			                    maxSize: {{ $individualAttachmentSize*1000000 }},
			                    maxTotalSize: {{ $availSpace*1000 }},
			                    maxFiles: {{ $maxAttachmentCount }},
			                    message: 'The selected file is not valid'
			                },
			            }
			        }*/
			});
		}
		
		if(isValid)
		{
			var currentDtTimestamp = moment().unix();
			currentDtTimestamp *= 1000;
			@if(!isset($createTimeStamp) || $createTimeStamp == '')
				$("#createTimeStamp").val(currentDtTimestamp);
			@endif
			$("#updateTimeStamp").val(currentDtTimestamp);
			$("#attachmentCnt").val(0);
			
			if(isFolderFlag == 1)
			{
				saveContentDetails(frmObj);
			}
			else
			{
				bootbox.confirm({
				    message: "Do you wish to share as locked (non-editable) note?",
				    buttons: {
				        confirm: {
				            label: 'Yes',
				            className: 'btn-success'
				        },
				        cancel: {
				            label: 'No',
				            className: 'btn-danger'
				        }
				    },
				    callback: function (result) {
				    	var isLocked = 0;
				    	if(result === true)
				    	{
							isLocked = 1;
						}
						$('#isLocked').val(isLocked);
						saveContentDetails(frmObj);
				    }
				});
			}	
		}
		else
		{
			errorToast.push(errorMsg);
		}
	}

	function saveContentDetails(frmObj)
	{	
		var dataToSend = compileSessionParams();
		var orgId = getCurrentOrganizationId();
		@if($isFavoritesTab)
			dataToSend = compileSessionParamsForFavorites();
			orgId = getFavoriteOrgId();
		@endif
		
		var attCnt = getAttachmentCount();
		
		var urlStr = "";
		var isFolderFlag = $("#isFolderFlag").val();
		
		if(isFolderFlag == 1)
			urlStr = "{!! route('content.save') !!}";
		else
		{
			if(orgId != "")
				urlStr = "{!! route('orgGroup.saveContentDetails') !!}";
			else
				urlStr = "{!! route('group.saveContentDetails') !!}";
		}

		@if(!$isView && $sendAsReply == 1)
			var appendedContent = $('#content').val() + "{{ $contentText }}";
			$('#content').val(appendedContent);
		@endif
		
		var formDataToSend = $(frmObj).serialize();
		dataToSend = formDataToSend+dataToSend;
		
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);
					else
						successToast.push('Content saved successfully');
						
					if(attCnt > 0)
					{
						uploadContentAttachments(isFolderFlag, attCnt, data.syncId);
					}
					else
					{
						$("#addEditContentModal").modal('hide');
						toggleAddEditContentButton(1);						
						refreshContentList(isFolderFlag);
						refreshKanbanBoard();
					}					
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
	function uploadContentAttachments(isFolder, attCnt, contentId)
	{
		var urlStr;
		if(isFolder == 1)
			urlStr = "{!! route('attachment.upload') !!}";
		else
			urlStr = "{!! route('attachment.uploadToGroup') !!}";
		
		var isAdd = 1;
		@if($id > 0)
			isAdd = 0;
		@endif
		
		var primFormData = new FormData();
	    primFormData.append('attachmentCnt', attCnt);
	    primFormData.append('isAdd', isAdd);
	    primFormData.append('id', contentId);
	    primFormData.append('orgId', getCurrentOrganizationId());
	    primFormData.append('userId', getCurrentUserId());
	    primFormData.append('loginToken', getCurrentLoginToken());
	    primFormData.append('sendAsReply', $('#sendAsReply').val());
		
		$( ".attachment_file" ).each(function( e ) {
			var fileName = $(this).val().split('\\').pop();
			
			if(fileName != "")
			{
				var fileExt = fileName.replace(/^.*\./, '');
				fileExt = "."+fileExt;
				
				if(fileExt == fileName) 
				{
				    fileExt = '';
				} 
				else 
				{
					fileExt = fileExt.toLowerCase();
				}
				var fileSize = $(this)[0].files[0].size;
				var fileSizeKb = Math.ceil(fileSize/1000);
		  
				var formData = primFormData;
			    formData.append('fileName', fileName);
			    formData.append('fileExt', fileExt);
			    formData.append('fileSize', fileSizeKb);
			    formData.append('attachmentFile', $(this)[0].files[0]);
				
				uploadContentAttachment(urlStr, formData);
			}	
		});
		
		$("#addEditContentModal").modal('hide');						
		refreshContentList(isFolder);
		toggleAddEditContentButton(1);
	}
	
	function uploadContentAttachment(urlStr, dataToSend)
	{
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			cache: false,
	        contentType: false,
	        processData: false,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
	function renderTimeLayout(selTypeId)
	{
		var iconFile = '';
		if(selTypeId == null || selTypeId <= 0 || selTypeId == typeA*1)
		{
			toggleFromPanel(false);
			toggleToPanel(false);
			toggleRemindBefore(false);
			toggleRepeat(false);
			iconFile = 'ic_archive.png';
		}
		else if(selTypeId == typeR*1)			
		{
			toggleFromPanel(true);
			toggleToPanel(false);
			toggleRemindBefore(true);
			toggleRepeat(false);
			iconFile = 'ic_reminder.png';
		}
		else if(selTypeId == typeC*1)
		{
			toggleFromPanel(true);
			toggleToPanel(true);
			toggleRemindBefore(true);
			toggleRepeat(true);
			iconFile = 'ic_calendar.png';
		}
		var iconSrc = '{{ $baseIconFolderPath }}' + iconFile;
		
		$("#contentTypeIcon").attr('src', iconSrc);
	}

	function groupOrFolderChanged(groupOrFolderId)
	{
		var isFolderFlag = $("#isFolderFlag").val();
		if(groupOrFolderId*1 <= 0)
		{
			var newIsFolderFlag, iconFile, defId, defText, otherControlId, selControlId;
			if(isFolderFlag == 1)
			{
				newIsFolderFlag = 0;
				iconFile = 'ic_group.png';
				selControlId = 'groupId';
				otherControlId = 'folderId';
				@if(isset($defaultGroup) && isset($defaultGroup['id']))
					defId = {{ $defaultGroup['id'] }};
					defText = "{{ $defaultGroup['text'] }}";
				@endif
			}
			else
			{
				newIsFolderFlag = 1;
				iconFile = 'ic_folder.png';
				selControlId = 'folderId';
				otherControlId = 'groupId';
				@if(isset($defaultFolder) && isset($defaultFolder['id']))
					defId = {{ $defaultFolder['id'] }};
					defText = "{{ $defaultFolder['text'] }}";
				@endif
			}
			
			if(!defText)
			{
				defText = '';
			}
			
			if(!defId)
			{
				defId = 0;
			}
			
			$("#isFolderFlag").val(newIsFolderFlag);
			$("#spanGroupOrFolderText").text(defText);
			$("input[name='" + selControlId + "']").val(defId);
			$("input[name='" + otherControlId + "']").val(0);
			
			var iconSrc = '{{ $baseIconFolderPath }}' + iconFile;
			$("#groupOrFolderIcon").attr('src', iconSrc);
		}
		else
		{
			@if($isView)
				if(isFolderFlag == 1)
				{
		        	savePartialContentUpdate('FOL');
				}
			@endif
		}
	}
	
	function toggleRemindBefore(visibilityStatus)
	{
		if(visibilityStatus)
			$("#imgBsRemindBefore").removeClass('bsDisabled');
		else
			$("#imgBsRemindBefore").addClass('bsDisabled');
	}
	
	function toggleRepeat(visibilityStatus)
	{
		if(visibilityStatus)
			$("#imgBsRepeat").removeClass('bsDisabled');
		else
			$("#imgBsRepeat").addClass('bsDisabled');
	}
	
	function toggleFromPanel(visibilityStatus)
	{
		if(visibilityStatus)
			$("#divFromDateTime").show();
		else
			$("#divFromDateTime").hide();
	}
	
	function toggleToPanel(visibilityStatus)
	{
		if(visibilityStatus)
			$("#divToDateTime").show();
		else
			$("#divToDateTime").hide();
	}

	function toggleContentStatus()
	{
		@if($isLocked == 0)
			var currStatus = $("#isMarked").val();
			var updateStatus = 1;
			
			if(currStatus == 1)
				updateStatus = 0;
				
			if(updateStatus == 1)
			{
				btnMarkContentIcon.addClass('text-yellow');
			}
			else
			{
				btnMarkContentIcon.removeClass('text-yellow');
			}
			
			$("#isMarked").val(updateStatus);
			
			@if($isView)
				saveContentIsMarkToggled();
			@endif
		@endif
	}
	
	function addFileRow()
	{		
		$('#divAttachments').append(
			$('<div/>', {
				html: addAttHtml 
			}).text()
		);
		
		$(".attachment_file").fileinput({
			'showUpload':false,
			'showPreview':false,
			'previewFileType':'any'
		}).on('fileclear', function(event) {
            $(frmObj).formValidation('revalidateField', 'attachment_file[]');
        });
	}
	
	function removeUploadedFile(btnObj, attId)
	{
		bootbox.dialog({
			message: "Do you really want to remove this file?",
			title: "Confirm Delete",
				buttons: {
					yes: {
					label: "Yes",
					className: "btn-primary",
					callback: function() {
						btnObj.closest( ".attListRow" ).remove();
						remFileElems.push(attId);
					}
				},
				no: {
					label: "No",
					className: "btn-primary",
					callback: function() {
					}
				}
			}
		});
	}
	
	function removeInputFile(btnObj)
	{
		btnObj.closest( ".attAddRow" ).remove();		
	}
	
	/*function viewInputFile(btnObj)
	{
		var rowDiv = $(btnObj).closest(".attAddRow");
		var attFile = $(rowDiv).find(".attachment_file");
		var attFileId = $(attFile).attr('id');
		console.log('attFileId: ' + attFileId);
		var attFilename = $(attFile).val();
		
		console.log('attFilename: ' + attFilename);
	}*/

	function selectAndLoadTemplate()
	{
		var tempId = $("#templateId").val();
		if(tempId*1 > 0)
		{
			var dataToSend = "orgId="+$('#conOrgId').val()+"&userId="+getCurrentUserId()+"&loginToken="+getCurrentLoginToken()+"&tempId="+tempId;
		
			var urlStr = "{!! route('orgApp.getTemplateDetails') !!}";
			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					if(data.status*1 > 0)
					{
						var tempHtml = data.text;
						CKEDITOR.instances.content.setData(tempHtml);
						$("#templateId").val(null).trigger('change');				
					}
					else
					{
						if(data.msg != "")
							errorToast.push(data.msg);
					}
				}
			});
		}
	}

	function colorSelectionChanged() {
		var radioValue = $("input[name='selColorCode']:checked").val();
		
        if(radioValue && radioValue != ''){
        	contentColorCode = radioValue;
        	
			$("#colorCode").val(contentColorCode);
        	$('#faColorCodeIcon').css('color', contentColorCode);
        	$('#divContentText').css('background-color', contentColorCode);
        	// ckEditObj.uiColor = contentColorCode;
        	
        	$('#color-popup-over').popover('hide');
			isColorPopoverOpen = false;
        }
        
        @if($isView)
        	savePartialContentUpdate('CLR');
        @endif
	}
	
	function sourceChanged()
	{
		@if($isView)
        	savePartialContentUpdate('SRC');
		@endif
	}
	
	function tagsChanged()
	{
		@if($isView)
        	savePartialContentUpdate('TAG');
		@endif
	}
	
	function profileChanged()
	{
		var orgId = $('#conOrgId').val();
		if(orgId != "")
		{
			$('#divTemplate').css("display", "inline");
		}
		else
		{
			$('#divTemplate').css("display", "none");
		}
		$('#templateId').val(null);
		$('#groupId').val(null);
		$('#folderId').val(null);
		$('#spanGroupOrFolderText').text("");
		$('#sourceId').val(null);
		$('#tagList').val(null);
	}

	function templateChanged()
	{
		@if(!$isView)
			selectAndLoadTemplate();
		@endif
	}
	
	function saveContentIsMarkToggled() {
		@if($isLocked == 0)
			var contentId = {{ $id }};
			var isFolderFlag = $("#isFolderFlag").val();
			var dataToSend = compileSessionParams();
			@if($isFavoritesTab)
				dataToSend = compileSessionParamsForFavorites();
			@endif
			dataToSend += '&id='+contentId+'&isFolder='+isFolderFlag;
			
			var urlStr = "{!! route('content.toggleMark') !!}";
			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					if(data.status*1 > 0)
					{	
						refreshContentList(isFolderFlag);
						refreshKanbanBoard();				
					}
					else
					{
						if(data.msg != "")
							errorToast.push(data.msg);
					}
				}
			});
		@endif
	}
	
	function savePartialContentUpdate(updCode) {
		@if($isLocked == 0)
			var performSave = false;
			var partialData = '';
			if(updCode == 'CLR')
			{
				colorCode = $('#colorCode').val();
				if(colorCode != '')
				{
					partialData = '&colorCode='+colorCode;
					performSave = true;
				}
			}
			else if(updCode == 'FOL')
			{
				folderId = $('#folderId').val();
				if(folderId > 0)
				{
					partialData = '&folderId='+folderId;
					performSave = true;
				}
			}
			else if(updCode == 'SRC')
			{
				sourceId = $('#sourceId').val();
				partialData = '&sourceId='+sourceId;
				performSave = true;
			}
			else if(updCode == 'TAG')
			{
				tagList = $('#tagList').val();
				partialData = '&tagList='+tagList;
				performSave = true;
			}
			
			if(performSave == true)
			{
				var dataToSend = compileSessionParams();
				@if($isFavoritesTab)
					dataToSend = compileSessionParamsForFavorites();
				@endif
				
				var contentId = {{ $id }};
				var isFolderFlag = $("#isFolderFlag").val();
				dataToSend += '&id='+contentId+'&isFolder='+isFolderFlag+partialData;
				
				var urlStr = "{!! route('content.modifyContentDetails') !!}";
				$.ajax({
					type: "POST",
					url: urlStr,
					crossDomain : true,
					dataType: 'json',
					data: dataToSend,
					success: function(data)
					{
						if(data.status*1 > 0)
						{						
							refreshContentList(isFolderFlag);
							refreshKanbanBoard();			
						}
						else
						{
							if(data.msg != "")
								errorToast.push(data.msg);
						}
					}
				});
			}
			
		@endif	
	}
	
	function openContentTypeSelectionModal() {
		@if(!$isView)
			var depCode = 'TYPE';
			var fieldId = 'contentType';
			var hasDone = 0;
			var isMultiSelect = 0;
		    var hasCancel = 1;
			var isMandatory = 1;
			var isIntVal = 1;
			var displayFieldId = 'spanContentTypeText';
			var callbackName = 'renderTimeLayout';
		
			openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName);
		@endif
	}
	
	function openGroupOrFolderSelectionModal() {
		var isFolderFlag = $("#isFolderFlag").val();
		if(isFolderFlag == 1)
		{
			@if($isLocked == 0)
				var depCode = 'FOLDER';
				var fieldId = 'folderId';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 1;
				var isIntVal = 1;
				var displayFieldId = 'spanGroupOrFolderText';
				var callbackName = 'groupOrFolderChanged';
				
				var enableDataToggle = 1;
				@if($id > 0)
					enableDataToggle = 0;
				@endif
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			@endif
		}
		else
		{
			@if(!$isView && $id == 0)
				var depCode = 'GROUP';
				var fieldId = 'groupId';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 1;
				var isIntVal = 1;
				var displayFieldId = 'spanGroupOrFolderText';
				var callbackName = 'groupOrFolderChanged';
				var enableDataToggle = 1;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			@endif
		}
	}
	
	function openSourceSelectionModal() {
		var isFolderFlag = $("#isFolderFlag").val();
	
		if(isFolderFlag == 1)
		{
			@if($isLocked == 1 && isset($selSourceName) && $selSourceName != "")
				var dispToast = new ax5.ui.toast({
					icon: '<i class="fa fa-clipboard"></i>',
					containerPosition: "top-right",
					closeIcon: '<i class="fa fa-times"></i>',
					theme: 'success'
				});
				dispToast.push("{{ $selSourceName }}");
			@else
				var depCode = 'SOURCE';
				var fieldId = 'sourceId';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 0;
				var isIntVal = 1;
				var displayFieldId = '';
				var callbackName = 'sourceChanged';
				var enableDataToggle = 0;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			@endif
		}
	}
	
	function openAttachmentSelectionModal() {
		@if(!$isView)
			addFileRow();
			var dispToast = new ax5.ui.toast({
				icon: '<i class="fa fa-paperclip"></i>',
				containerPosition: "top-right",
				closeIcon: '<i class="fa fa-times"></i>',
				theme: 'success'
			});
			dispToast.push("Attachment row added. Please browse and select the attachment for content.");
		@endif
	}
	
	function openTagSelectionModal() {
		@if($isLocked == 1 && isset($tagNameStr) && $tagNameStr != "")
			var dispToast = new ax5.ui.toast({
				icon: '<i class="fa fa-tags"></i>',
				containerPosition: "top-right",
				closeIcon: '<i class="fa fa-times"></i>',
				theme: 'success'
			});
			dispToast.push("{{ $tagNameStr }}");
		@else
			var depCode = 'TAG';
			var fieldId = 'tagList';
			var hasDone = 1;
			var isMultiSelect = 1;
		    var hasCancel = 1;
			var isMandatory = 0;
			var isIntVal = 1;
			var displayFieldId = '';
			var callbackName = 'tagsChanged';
			var enableDataToggle = 0;
		
			openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
		@endif
	}
	
	function openLocationSelectionModal()
	{
		var dispToast = new ax5.ui.toast({
			icon: '<i class="fa fa-map-marker"></i>',
			containerPosition: "top-right",
			closeIcon: '<i class="fa fa-times"></i>',
			theme: 'success'
		});
		dispToast.push("Coming Soon");
	}
	
	function openRemindBeforeSelectionModal()
	{
		@if($isView && isset($remindBeforeMillisStr) && $remindBeforeMillisStr != "")
			var dispToast = new ax5.ui.toast({
				icon: '<i class="fa fa-clock-o"></i>',
				containerPosition: "top-right",
				closeIcon: '<i class="fa fa-times"></i>',
				theme: 'success'
			});
			dispToast.push("{{ $remindBeforeMillisStr }}");
		@elseif(!$isView)	
			var selTypeId = $("#type_id").val();		
			if(selTypeId*1 == typeC || selTypeId*1 == typeR)
			{
				var depCode = 'REMIND';
				var fieldId = 'remindBeforeMillis';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 0;
				var isIntVal = 1;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal);
			}
		@endif
	}
	
	function openRepeatDurationSelectionModal()
	{
		@if($isView && isset($repeatDurationStr) && $repeatDurationStr != "")
			var dispToast = new ax5.ui.toast({
				icon: '<i class="fa fa-refresh"></i>',
				containerPosition: "top-right",
				closeIcon: '<i class="fa fa-times"></i>',
				theme: 'success'
			});
			dispToast.push("{{ $repeatDurationStr }}");
		@elseif(!$isView)	
			var selTypeId = $("#type_id").val();
			
			if(selTypeId*1 == typeC)
			{
				var depCode = 'REPEAT';
				var fieldId = 'repeatDuration';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 0;
				var isIntVal = 0;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal);
			}
		@endif
	}
	
	function openProfileSelectionModal()
	{
		@if(!$isView && $id == 0)	
			var depCode = 'PROFILE';
			var fieldId = 'conOrgId';
			var hasDone = 0;
			var isMultiSelect = 0;
		    var hasCancel = 1;
			var isMandatory = 0;
			var isIntVal = 0;
			var displayFieldId = '';
			var callbackName = 'profileChanged';
			var enableDataToggle = 0;
		
			openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
		@endif
	}
	
	function openTemplateSelectionModal() {
		@if(!$isView)	
			var depCode = 'TEMPLATE';
			var fieldId = 'templateId';
			var hasDone = 0;
			var isMultiSelect = 0;
		    var hasCancel = 1;
			var isMandatory = 0;
			var isIntVal = 1;
			var displayFieldId = '';
			var callbackName = 'templateChanged';
			var enableDataToggle = 0;
		
			openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
		@endif
	}
	
	function openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle) {
	
		var selectedId = $('input[name="' + fieldId + '"]').val();
		
		if(!displayFieldId || displayFieldId == undefined)
		{
			displayFieldId = "";
		}
		
		if(!callbackName || callbackName == undefined)
		{
			callbackName = "";
		}
		
		if(!enableDataToggle || enableDataToggle == undefined)
		{
			enableDataToggle = 0;
		}
		
		$("#contentDependencyModal").modal("hide");	
		var dataToSend = "orgId=" + $('#conOrgId').val()+"&userId="+getCurrentUserId()+"&loginToken="+getCurrentLoginToken();
		dataToSend += "&depCode="+depCode+"&selectedId="+selectedId+"&isMultiSelect="+isMultiSelect+"&hasDone="+hasDone+"&hasCancel="+hasCancel;
		dataToSend += "&fieldId="+fieldId+"&isMandatory="+isMandatory+"&isIntVal="+isIntVal+"&displayFieldId="+displayFieldId+"&callbackName="+callbackName+"&enableDataToggle="+enableDataToggle;
		
		var urlStr = "{!! route('content.appuserContentDependencyModal') !!}";
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{	
					$("#divDependencies").html(data.view);
					$("#contentDependencyModal").modal("show");				
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
	function shareContent() 
	{
		var isFolder = {{ $isFolderFlag }};
		var forContent = {{ $id }};
		var groupIdArr;
		var userIdArr;
		var isLocked;
		var isShareEnabled;
		performShareContentToUser(isFolder, groupIdArr, userIdArr, isLocked, isShareEnabled, forContent);
	}
</script>

<div id="addEditContentModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveContent']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" onclick="toggleAddEditContentButton(1);">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}
						&nbsp;
						{!! Form::button('<i id="btnMarkContentIcon" class="fa fa-star fa-2x text-white"></i>', ['type' => 'button', 'class' => 'btn btn-link btn-xs', 'id' => 'btnMarkContent', 'onclick' => 'toggleContentStatus();', 'style' => '']) !!}
						@if($isLocked == 0)
							&nbsp;
							<a href="javascript:void(0);" id="color-popup-over" data-toggle="popover"><i class="fa fa-circle" style="color: {{ $contentColorCode }}; font-size: 26px; padding-left: 10px; vertical-align: middle;" id="faColorCodeIcon"></i></a>
						@else
							<i class="fa fa-circle fa-2x" style="color: {{ $contentColorCode }}; font-size: 26px; padding-left: 10px; vertical-align: middle;"></i>
						@endif
						@if($isView)
							&nbsp;&nbsp;
			        		<button type="button" class="btn btn-link btn-sm" onclick="performPrintContent({{ $isFolderFlag }}, {{ $id }});">
					    		<img src="{{ $baseIconFolderPath.'ic_print.png' }}" class="content-modal-icon"/>
					    	</button>
							@if($isLocked == 0 && (($isFolder) || (!$isFolder && $groupIsTwoWay == 1 && ($groupHasPostRight == 1 || $groupIsAdmin == 1) )))
				        		<button id="btnEditContent" type="button" class="btn btn-link btn-sm" onclick="showContentDetails({{ $id }}, {{ $isFolderFlag }}, 0, {{ $isFavoritesTab }});">
						    		<img src="{{ $baseIconFolderPath.'ic_edit.png' }}" class="content-modal-icon"/>
						    	</button>
							    @if($hasSender && $selTypeId == $contentTypeIdA)
									&nbsp;&nbsp;
					        		<button type="button" class="btn btn-link btn-sm" onclick="showContentDetails({{ $id }}, {{ $isFolderFlag }}, 0, {{ $isFavoritesTab }}, 1);">
							    		<i class="fa fa-reply fa-2x text-white"></i>
							    	</button>
							    @endif
					    	@endif
							@if($isView && $isShareEnabled == 0)
						    	&nbsp;&nbsp;
						    	<img src="{{ $baseIconFolderPath.'ic_restricted.png' }}" class="content-modal-icon"/>
							@endif
							@if($isView && $isLocked == 1)
						    	&nbsp;&nbsp;
						    	<img src="{{ $baseIconFolderPath.'ic_locked.png' }}" class="content-modal-icon"/>
							@endif	
							&nbsp;&nbsp;
			        		<button type="button" class="btn btn-link btn-sm" onclick="loadContentInfo({{ $isFolderFlag }}, {{ $id }}, {{ $isFavoritesTab }});">
					    		<img src="{{ $baseIconFolderPath.'ic_info.png' }}" class="content-modal-icon"/>
					    	</button>
						    @if($isView && $isShareEnabled == 1) 
						    	&nbsp;&nbsp;
						    	<button class="btn btn-link btn-sm" type="button" onclick="shareContent();"><img src="{{ $baseIconFolderPath.'ic_share.png' }}" class="content-modal-icon"/></button>
						    @endif
							@if($isFolder || (!$isFolder && $groupIsAdmin == 1))
								&nbsp;&nbsp;
				        		<button type="button" class="btn btn-link btn-sm" onclick="confirmAndDeleteContent({{ $id }}, {{ $isFolderFlag }}, {{ $isFavoritesTab }});">
						    		<img src="{{ $baseIconFolderPath.'ic_delete.png' }}" class="content-modal-icon"/>
						    	</button>
						    @endif
					    @else
							@if($id == 0)
						    	&nbsp;&nbsp;
						    	<button type="button" class="btn btn-link btn-sm" onclick="openProfileSelectionModal();">
						    		<img src="{{ $baseIconFolderPath.'ic_profile.png' }}" class="content-modal-icon"/>
						    	</button>
							@endif		
					    	&nbsp;&nbsp;
					    	<button id="divTemplate" type="button" class="btn btn-link btn-sm" onclick="openTemplateSelectionModal();">
					    		<img src="{{ $baseIconFolderPath.'ic_template.png' }}" class="content-modal-icon"/>
					    	</button>
					    	{!! Form::hidden('templateId', null, ['id' => 'templateId']) !!}
						    	
					    	<!--<div class="col-md-offset-6 col-md-6" id="divTemplate">
								&nbsp;&nbsp;
								<a class="btn btn-link" onclick="openTemplateSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_template.png' }}"/></a>		
					    	</div>	-->		    
						@endif		
					</h4>
				</div>
				<div class="modal-body modalBackdrop">	
					{!! Form::hidden('isMarked', $isMarked, ['id' => 'isMarked']) !!}			
					{!! Form::hidden('colorCode', $contentColorCode, ['id' => 'colorCode']) !!}		
					{!! Form::hidden('isLocked', $isLocked, ['id' => 'isLocked']) !!}		
					<div class="row">
						<div class="col-md-6" style="cursor: pointer;">
							<div class="form-group detailsRow" onclick="openContentTypeSelectionModal();">
								<div class="col-md-1 selIcon">
									<img id="contentTypeIcon" src="{{ $baseIconFolderPath.'ic_archive.png' }}" />
								</div>
								<div class="col-md-11">
									<span class="modalTextmodalText" id="spanContentTypeText">{{ $selTypeName }}</span>
									@if(!$isView)
										{!! Form::hidden('contentType', $selTypeId, [ 'id' => 'type_id' ]) !!}
									@endif
								</div>
							</div>							
						</div>
						<div class="col-md-6" style="cursor: pointer;">
							<div class="form-group detailsRow" onclick="openGroupOrFolderSelectionModal();">
								
								@if($isFolder)
									@php
										$iconFile = 'ic_folder.png';
										$selGroupOrFolderText = $selFolderName;
									@endphp
								@else
									@php
										$iconFile = 'ic_group.png';
										$selGroupOrFolderText = $groupName;
									@endphp
								@endif
								<div class="col-md-1 selIcon">
									<img id="groupOrFolderIcon" src="{{ $baseIconFolderPath.$iconFile }}" />
								</div>
								<div class="col-md-11">
									<span class="modalTextmodalText" id="spanGroupOrFolderText">{!! $selGroupOrFolderText !!}</span>
									{!! Form::hidden('folderId', $selFolderId, ['id' => 'folderId']) !!}
									{!! Form::hidden('groupId', $groupId, ['id' => 'groupId']) !!}
								</div>
							</div>							
						</div>
						<!--<div class="col-md-6">
							<div class="form-group detailsRow">
								@if($isFolder)
									<div class="col-md-1 selIcon">
										<img src="{{ $baseIconFolderPath.'ic_folder.png' }}"/>
									</div>
									<div class="col-md-11">
						                @if($isView)
											<span class="modalTextmodalText">{{ $selFolderName }}</span>
										@else
						                	{{ Form::select('folderId', $folderArr, NULL, ['class' => 'form-control', 'id' => 'folder_id']) }}
										@endif
									</div>
								@else
									{!! Form::hidden('groupId', $groupId) !!}
									<div class="col-md-1 selIcon">
										<img src="{{ $baseIconFolderPath.'ic_group.png' }}"/>
									</div>
									<div class="col-md-11">
										<span class="modalTextmodalText">{{ $groupName }}</span>
									</div>
								@endif
							</div>
						</div>-->
					</div>
					<div class="row">						
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
										<span class="modalTextmodalText" id="spanFromDt"></span>
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
										<span class="modalTextmodalText" id="spanToDt"></span>
									@endif
								</div>
							</div>
						</div>
					</div>
					<div class="row">	
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openAttachmentSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_attachment.png' }}"/></a>
							<span class="bsTitle">Attachments</span>
						</div>
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openSourceSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_source.png' }}"/></a>
							<span class="bsTitle">Source</span>
							{!! Form::hidden('sourceId', NULL, ['id' => 'sourceId']) !!}
						</div>
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openTagSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_tags.png' }}"/></a>
							<span class="bsTitle">Tags</span>
							{!! Form::hidden('tagList', NULL, ['id' => 'tagList']) !!}
						</div>
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openLocationSelectionModal();"><img class="addEditActionBtnImg" src="{{ $baseIconFolderPath.'ic_location.png' }}"/></a>
							<span class="bsTitle">Location</span>
							@if(!$isView)
								{!! Form::hidden('locationId', NULL) !!}
							@endif
						</div>
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openRemindBeforeSelectionModal();"><img class="addEditActionBtnImg" id="imgBsRemindBefore" src="{{ $baseIconFolderPath.'ic_remind_before.png' }}"/></a>
							<span class="bsTitle">Remind Before</span>
							@if(!$isView)
								{!! Form::hidden('remindBeforeMillis', NULL) !!}
							@endif
						</div>
						<div class="col-md-2" align="center">
							<a class="btn btn-link" onclick="openRepeatDurationSelectionModal();"><img class="addEditActionBtnImg" id="imgBsRepeat" src="{{ $baseIconFolderPath.'ic_repeat.png' }}"/></a>
							<span class="bsTitle">Repeat</span>
							@if(!$isView)
								{!! Form::hidden('repeatDuration', NULL) !!}
							@endif
						</div>
					</div>					
					@if($isView)
						@if($isConversation)
							@foreach($contentConversationDetails as $i => $conversationObj)
								@php
								$senderStr = $conversationObj['sender'];
								@endphp
								<div class="row">	
									<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
										<div class="conversation-sender">
											<img style="height: 20px;" src="{{ $baseIconFolderPath.'ic_user.png' }}" />
											{!! $senderStr !!}
										</div>
										<div class="conversation-text" style="background-color: {{ $contentColorCode }}">
											{!! $conversationObj['content'] !!}
										</div>
										<div class="conversation-timestamp" align="right" id="convoTs_{{ $i }}"></div>
									</div>
								</div>
							@endforeach
						@else
							<div class="row">	
								<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
									<div class="" style="background-color: {{ $contentColorCode }}">
										<div class="">
											{!! $contentText !!}
										</div>
									</div>
								</div>
							</div>
						@endif
					@else
						<div class="row">	
							<div class="col-md-12" style="padding-left: 40px; padding-right: 40px;">
								<div class="form-group detailsRow" id="divContentText" style="background-color: {{ $contentColorCode }}">
				                	{{ Form::textarea('content', $editContentText, ['class' => 'form-control', 'id' => 'content']) }}
								</div>
							</div>
						</div>
					@endif
					
					@if((isset($contentAttachments) && count($contentAttachments) > 0) || !$isView)
						@include('content.partialview._attachmentListHeader')
					@endif
					<div id="divAttachments"></div>
					@if(isset($contentAttachments) && count($contentAttachments) > 0)
						@foreach($contentAttachments as $attObj)
							@php
								$attachmentId = $attObj->content_attachment_id;
								$attachmentFilename = $attObj->filename;
								$attachmentUrl = $attObj->url;
							@endphp
							@include('content.partialview._attachmentListRow')
						@endforeach
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
				@if(!$isView)
					<div class="modal-footer modalBackdrop">
						<div class="col-sm-offset-9 col-sm-3">
							{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
						</div>
					</div>
				@endif
			{!! Form::close() !!}
		</div>
	</div>
</div>
<div id="divDependencies"></div>