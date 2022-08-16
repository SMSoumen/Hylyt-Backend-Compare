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


	var totalConversationCount = {{ isset($contentConversationDetailsReversed) ? count($contentConversationDetailsReversed) : 0 }};
	var maxConversationCount = 100;
	var maxConversationMessage = 'A conversation can have a maximum of ' + maxConversationCount + ' messages';

	var associatedCloudAttachments = [];

	var contentOrgKey = '{{ $orgKey }}';
	var contentConversationJSArr = [];
	var hasLinkedCloudCalendarOptions = false;
	var hasLinkedCloudCalendarTypeGoogle = false;
	var hasLinkedCloudCalendarTypeOneDrive = false;

	var hasAppKeyMaping = '{{ $hasAppKeyMaping }}' * 1;

	$(document).ready(function(){

		Date.prototype.addHours = function(h) {
			this.setTime(this.getTime() + (h*60*60*1000));
			return this;
		}

	    $(document).bind('cut copy paste', function (e) {
	        e.preventDefault();
	    });
	     
	    $(document).on("contextmenu",function(e){
	        return false;
	    });

	    $(frmObj).on("keypress", function (event) { 
            var keyPressed = event.keyCode || event.which;

            if (keyPressed === 13 && ($(event.target)[0]!=$("textarea")[0]))
            {
                event.preventDefault(); 
                return false; 
            }
        });

		@if(isset($hasLinkedCloudCalendarTypeGoogle) && $hasLinkedCloudCalendarTypeGoogle == 1)
			hasLinkedCloudCalendarOptions = true;
			hasLinkedCloudCalendarTypeGoogle = true;
		@endif

		@if(isset($hasLinkedCloudCalendarTypeOneDrive) && $hasLinkedCloudCalendarTypeOneDrive == 1)
			hasLinkedCloudCalendarOptions = true;
			hasLinkedCloudCalendarTypeOneDrive = true;
		@endif

		scheduleRefreshContentDetailsCalls();

		$('#addEditContentModal').on('hidden.bs.modal', function (e) {
			clearContentRefresher();
		});
	});

	var contentRefresher;
	function scheduleRefreshContentDetailsCalls()
	{	
		@if(!$contentIsRemoved && $isView && $isConversation)
			clearContentRefresher();
			var content_detail_reload_interval_ms = 60000 * 0.5;
			contentRefresher = window.setInterval("checkAndRefreshContentDetails();", content_detail_reload_interval_ms);
		@endif
	}

	function clearContentRefresher() {
		if(contentRefresher)
		{
			clearInterval(contentRefresher);
		}
	}

	function checkAndRefreshContentDetails()
	{
		var isAnyOtherModalOpen = $('div.modal:not(#addEditContentModal)').hasClass('in');
		var isDetailModalOpen = $('div.modal#addEditContentModal').hasClass('in');
		
		if(isDetailModalOpen && !isAnyOtherModalOpen)
		{
			let isAutoRefresh = 1;
			clearContentRefresher();
			reloadContentDetailsView(isAutoRefresh);
		}
		else 
		{
		}
	}

	function reloadContentDetailsView(isAutoRefresh = 0)
	{
		var isViewFlag = 0;
		@if($isView)
		isViewFlag = 1;
		@endif

		var isFolderFlag = {{ $isFolderFlag }};
		var contentId = '{{ $id }}';
		var listCode = '{{ $listCode }}';
		var searchStr = '{{ $searchStr }}';

		var preloadConversationReplyText = "";
		if(isAutoRefresh && isAutoRefresh !== undefined && isAutoRefresh === 1)
		{
			var editOrReplyText = $('#contentPartText').val();
			editOrReplyText = editOrReplyText.trim();

			editOrReplyText = nl2br(editOrReplyText);

			if(editOrReplyText !== '')
			{
				preloadConversationReplyText = editOrReplyText;
			}
		}

		var dataToSend = "orgId="+$('#conOrgId').val()+"&userId="+getCurrentUserId()+"&loginToken="+getCurrentLoginToken()+"&isFolder="+isFolderFlag;
		dataToSend += "&id="+contentId+"&searchStr="+searchStr+"&listCode="+listCode+"&isView="+isViewFlag+"&preloadConversationReplyText="+preloadConversationReplyText;
		
		var urlStr = "{!! route('content.loadContentDetailsSubView') !!}";
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
					var viewHtml = data.view;
					$('#divModalContentForSubView').html(viewHtml);
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
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
		$( ".cldAtt_storage_type_id" ).each(function( e ) {
			var cldAttStorageTypeId = $(this).val();
			if(cldAttStorageTypeId > 0)
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
	
	function getOnlyAddedAttachmentCount()
	{		
		var addedAttCount = 0;
		$( ".attachment_file" ).each(function( index ) {
			var filename = $(this).val();
			if(filename != "")
			{
				addedAttCount++;
			}
		});
		$( ".cldAtt_storage_type_id" ).each(function( e ) {
			var cldAttStorageTypeId = $(this).val();
			if(cldAttStorageTypeId > 0)
			{
				addedAttCount++;
			}
		});
		return addedAttCount;
	}

	function submitContentSaveForm(btnCode)
	{
        var performShare = 0;
        if(btnCode == 'FORWARD')
        {
        	performShare = 1;
        }

		validateAndSubmitContentForm(performShare);
	}

	function validateAndSubmitContentForm(performShare)
	{
		var contentType = $('input[name="contentType"]').val();
		var folderId = $('input[name="folderId"]').val();
		var groupId = $('input[name="groupId"]').val();
		var content = CKEDITOR.instances.content.getData();
		var content_title=$('input[name="content_title"]').val();//_CHANGES
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
		else if((isFolderFlag == 1 && folderId == "") || (isFolderFlag == 0 && groupId == ""))
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

			});
		}
		
		if(isValid)
		{
			var contentTypeIsCalendar = false;
			if(contentType == typeC)
			{
				contentTypeIsCalendar = true;
			}

			var currentDtTimestamp = moment().unix();
			currentDtTimestamp *= 1000;
			@if(!isset($createTimeStamp) || $createTimeStamp == '')
				$("#createTimeStamp").val(currentDtTimestamp);
			@endif
			$("#updateTimeStamp").val(currentDtTimestamp);
			$("#attachmentCnt").val(0);

			let confirmationDialogTitle = "Are you sure you wish to save this content?";
			let renderConfirmationDialog = false;

			let generalSharingInputOptions = [];
			if((isFolderFlag == 0) || (isFolderFlag == 1 && performShare == 1))
			{
				confirmationDialogTitle = "Are you sure you wish to share this content?";

				generalSharingInputOptions.push({
					text: 'Share as Locked Note',
					value: 'LCK',
				});
			
				generalSharingInputOptions.push({
					text: 'Share as Restricted Content',
					value: 'RST',
				});
			}

			let cloudSharingInputOptions = [];
			if(contentTypeIsCalendar == true)
			{
				if(hasLinkedCloudCalendarOptions == true)
				{
					if(hasLinkedCloudCalendarTypeGoogle == true)
					{
						cloudSharingInputOptions.push({
							text: 'Sync with Google Calendar',
							value: 'GGL-CAL'
						});
					}
					
					if(hasLinkedCloudCalendarTypeOneDrive == true)
					{
						cloudSharingInputOptions.push({
							text: 'Sync with Microsoft Calendar',
							value: 'MS-CAL'
						});
					}
				}
			}

			let confirmationDialogInputOptions = [];
			if(generalSharingInputOptions.length > 0)
			{
				// renderConfirmationDialog = true;
				// confirmationDialogInputOptions = confirmationDialogInputOptions.concat(generalSharingInputOptions);
			}
			if(cloudSharingInputOptions.length > 0)
			{
				renderConfirmationDialog = true;
				confirmationDialogInputOptions = confirmationDialogInputOptions.concat(cloudSharingInputOptions);
			}


			if(contentTypeIsCalendar == true)
			{
				var dataToSend = '&loginToken=' + getCurrentLoginToken() + '&userId=' + getCurrentUserId() + '&orgId=' + $('#conOrgId').val();
				var formDataToSend = $(frmObj).serialize();
				formDataToSend = formDataToSend + dataToSend;
				
				var urlStr = "{!! route('content.checkCalendarContentTimingForOverlapping') !!}";
				$.ajax({
					type: "POST",
					url: urlStr,
					crossDomain : true,
					dataType: 'json',
					data: formDataToSend,
					success: function(data)
					{
						if(data.status*1 > 0)
						{
							var isOverLapping = data.isOverLapping;

							if(isOverLapping*1 == 1)
							{
								renderConfirmationDialog = true;
								confirmationDialogTitle = "We have found conflict with one of the calendar entries. Would you still like to save?";
							}

							renderConfirmationDialogIfRequiredAndPerformSave(frmObj, performShare, renderConfirmationDialog, confirmationDialogTitle, confirmationDialogInputOptions);
						}
						else
						{
							if(data.msg != "")
								errorToast.push(data.msg);
						}
					}
				});
			}
			else
			{
				renderConfirmationDialogIfRequiredAndPerformSave(frmObj, performShare, renderConfirmationDialog, confirmationDialogTitle, confirmationDialogInputOptions);
			}
		}
		else
		{
			errorToast.push(errorMsg);
		}
	}

	function renderConfirmationDialogIfRequiredAndPerformSave(frmObj, performShare, renderConfirmationDialog, confirmationDialogTitle, confirmationDialogInputOptions)
	{
		if(renderConfirmationDialog == true)
		{
			bootbox.prompt({
				title: confirmationDialogTitle,
				value: [],
				inputType: 'checkbox',
				inputOptions: confirmationDialogInputOptions,
				callback: function (resultArr) {
			    	var isLocked = 0;
			    	var isRestricted = 0;
			    	var syncWithCloudCalendarGoogle = 0;
			    	var syncWithCloudCalendarOnedrive = 0;

					if(resultArr && resultArr.length > 0)
					{
						if(resultArr.includes('LCK'))
						{
							isLocked = 1;
						}

						if(resultArr.includes('RST'))
						{
							isRestricted = 1;
						}

						if(resultArr.includes('GGL-CAL'))
						{
							syncWithCloudCalendarGoogle = 1;
						}

						if(resultArr.includes('MS-CAL'))
						{
							syncWithCloudCalendarOnedrive = 1;
						}
					}

					let isShareEnabled = isRestricted == 1 ? 0 : 1;

					$('#isLocked').val(isLocked);
					$('#isShareEnabled').val(isShareEnabled);
					$('#syncWithCloudCalendarGoogle').val(syncWithCloudCalendarGoogle);
					$('#syncWithCloudCalendarOnedrive').val(syncWithCloudCalendarOnedrive);

					saveContentDetails(frmObj, performShare);
				}
			});
		}
		else
		{
			saveContentDetails(frmObj, performShare);
		}
	}

	var contentSavedToOrgKey;

	function saveContentDetails(frmObj, performShare)
	{	
		var dataToSend = compileSessionParams(contentOrgKey);
		var orgId = contentOrgKey;
		@if($isFavoritesTab)
			dataToSend = compileSessionParamsForFavorites();
			orgId = getFavoriteOrgId();
		@endif
		
		var attCnt = getAttachmentCount();
		var addedAttCnt = getOnlyAddedAttachmentCount();
		
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
			var baseContentText = "{{ $encodedContentText }}";
			var appendedContent = $('#content').val() + baseContentText;
			$('#content').val(appendedContent);
		@endif
		
		var formDataToSend = $(frmObj).serialize();
		dataToSend = formDataToSend+dataToSend;
		
		var hasAttachmentSizeExceededError = false;
		var maxFileSizeKb = ('{{ $individualAttachmentSize }}' * 1) * 1024; // 5 MB

		var hasAttachmentAddedCountExceededError = false;
		var hasAttachmentTotalCountExceededError = false;
		var maxAddedFileCount = 5;
		var maxTotalFileCount = 50;
		if(hasAppKeyMaping > 0)
		{
			maxAddedFileCount = 5;
		}
		else
		{
			maxAddedFileCount = 50;
		}

		var attachmentFormDataArr = [];
		if(attCnt > 0)
		{
    		var currentDtTimestamp = moment().unix();
    		currentDtTimestamp *= 1000;

    		if(addedAttCnt > maxAddedFileCount)
    		{
    			hasAttachmentAddedCountExceededError = true;
    		}

    		if(attCnt > maxTotalFileCount)
    		{
    			hasAttachmentTotalCountExceededError = true;
    		}

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
			  
					var attFormData = new FormData();
				    attFormData.append('fileName', fileName);
				    attFormData.append('fileExt', fileExt);
				    attFormData.append('fileSize', fileSizeKb);
				    attFormData.append('cloudStorageTypeId', 0);
				    attFormData.append('attachmentFile', $(this)[0].files[0]);
            	    attFormData.append('attCreateTs', currentDtTimestamp);
            	    attFormData.append('attUpdateTs', currentDtTimestamp);

            	    if(fileSizeKb > maxFileSizeKb)
            	    {
            	    	hasAttachmentSizeExceededError = true;
            	    }

				    attachmentFormDataArr.push(attFormData);
				}	
			});

    		$( ".cldAtt_storage_type_id" ).each(function( e ) {
    			var cldAttStorageTypeId = $(this).val();
    			
    			if(cldAttStorageTypeId !== undefined && cldAttStorageTypeId != "")
    			{
    				var attListRowItem = $(this).closest('.attListRow');
    				var fileName = attListRowItem.find('.cldAtt_file_name').val();
    
    				if(fileName !== undefined && fileName !== '')
    				{
    					var fileSizeKb = attListRowItem.find('.cldAtt_file_size').val();
    					var cloudFileUrl = attListRowItem.find('.cldAtt_cloud_file_url').val();
    					var cloudFileId = attListRowItem.find('.cldAtt_cloud_file_id').val();
    
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
    
    					var attFormData = new FormData();
    				    attFormData.append('fileName', fileName);
    				    attFormData.append('fileExt', fileExt);
    				    attFormData.append('fileSize', fileSizeKb);
    				    attFormData.append('cloudStorageTypeId', cldAttStorageTypeId);
    				    attFormData.append('cloudFileUrl', cloudFileUrl);
    				    attFormData.append('cloudFileId', cloudFileId);
                	    attFormData.append('attCreateTs', currentDtTimestamp);
                	    attFormData.append('attUpdateTs', currentDtTimestamp);

				        attachmentFormDataArr.push(attFormData);    					
    				}
    			}
    		});
		}

		if(hasAttachmentTotalCountExceededError === true)
		{
			errorToast.push("The content has a limit of " + maxTotalFileCount + " attachments. Remove some and try again.");
			return;
		}

		if(hasAttachmentAddedCountExceededError === true)
		{
			errorToast.push("The content save transaction has a limit of " + maxAddedFileCount + " attachments. Remove some and try again.");
			return;
		}

		if(hasAttachmentSizeExceededError === true)
		{
			errorToast.push("The content attachments can be of max " + '{{ $individualAttachmentSize }}' + " MBs. Remove these and try again.");
			return;
		}
		
		$("#addEditContentModal").modal('hide');

		contentSavedToOrgKey = $('#conOrgId').val();
		
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
						
					if(attCnt > 0 && attachmentFormDataArr.length > 0)
					{
						uploadContentAttachments(isFolderFlag, attCnt, data.syncId, performShare, attachmentFormDataArr);
					}
					else
					{
						if(performShare == 1)
						{
							performQuickShareContentPostSave(isFolderFlag, data.syncId);
						}
						else
						{
							reloadPageIfDetailsLoadedInPopUp(isFolderFlag, data.syncId);	
						}

						toggleAddEditContentButton(1);	
						refreshContentList(contentOrgKey);
						performRefreshKanbanBoard();
						refreshContentCalendarEntries();
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

	function reloadPageIfDetailsLoadedInPopUp(isFolderFlag, savedContentId)
	{
		@if($forPopUp == 1)
		loadAppuserContentDetailsInPopUp(contentOrgKey, isFolderFlag, savedContentId, 1, '{{ $listCode }}', '{{ $searchStr }}');
		@endif
	}

	var filesForUpload = 0, filesUploaded = 0;
	
	function uploadContentAttachments(isFolder, attCnt, contentId, performShare, attachmentFormDataArr)
	{
		var urlStr;
		if(isFolder == 1)
			urlStr = "{!! route('attachment.upload') !!}";
		else
			urlStr = "{!! route('attachment.uploadToGroup') !!}";
		
		var isAdd = 1;
		@if($id != "")
			isAdd = 0;
		@endif
		
		var primFormData = new FormData();
	    primFormData.append('attachmentCnt', attCnt);
	    primFormData.append('isAdd', isAdd);
	    primFormData.append('id', contentId);
	    primFormData.append('orgId', contentSavedToOrgKey);
	    primFormData.append('userId', getCurrentUserId());
	    primFormData.append('loginToken', getCurrentLoginToken());
	    primFormData.append('sendAsReply', $('#sendAsReply').val());

	    filesForUpload = attCnt;

	    var consUserId = getCurrentUserId();
	    var consLoginToken = getCurrentLoginToken();
	    var consSendAsReply = $('#sendAsReply').val();


		(attachmentFormDataArr).forEach((attachmentFormData) => 
		{
			var attachmentFormDataForUpload = attachmentFormData;
		    attachmentFormDataForUpload.append('attachmentCnt', attCnt);
		    attachmentFormDataForUpload.append('isAdd', isAdd);
		    attachmentFormDataForUpload.append('id', contentId);
		    attachmentFormDataForUpload.append('orgId', contentSavedToOrgKey);
		    attachmentFormDataForUpload.append('userId', consUserId);
		    attachmentFormDataForUpload.append('loginToken', consLoginToken);
		    attachmentFormDataForUpload.append('sendAsReply', consSendAsReply);

			uploadContentAttachment(urlStr, attachmentFormDataForUpload, performShare, isFolder, contentId);
		});

		$("#addEditContentModal").modal('hide');						
		refreshContentList(contentOrgKey);
		performRefreshKanbanBoard();
		refreshContentCalendarEntries();

		toggleAddEditContentButton(1);
	}
	
	function uploadContentAttachment(urlStr, dataToSend, performShare, isFolder, contentId)
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
				filesUploaded++;

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

				if(filesForUpload === filesUploaded)
				{	
					if(performShare == 1)
					{
						performQuickShareContentPostSave(isFolder, contentId)
					}
					else
					{
						reloadPageIfDetailsLoadedInPopUp(isFolder, contentId);
					}
				}
			}
		});
	}

	function performQuickShareContentPostSave(isFolder, contentId)
	{
		var groupIdArr;
		var userIdArr;
		var isLocked;
		var isShareEnabled;
		var performContentRemove = 0;
		@if($id == "")
			performContentRemove = 1;
		@endif
		$('#addEditContentModal').modal("hide");
		performShareContentToUser(isFolder, groupIdArr, userIdArr, isLocked, isShareEnabled, contentId, contentSavedToOrgKey, performContentRemove, '{{ $forPopUp }}');
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
			iconFile = '{{ $typeAIconPath }}';
		}
		else if(selTypeId == typeR*1)			
		{
			toggleFromPanel(true);
			toggleToPanel(false);
			toggleRemindBefore(true);
			toggleRepeat(false);
			iconFile = '{{ $typeRIconPath }}';
		}
		else if(selTypeId == typeC*1)
		{
			toggleFromPanel(true);
			toggleToPanel(true);
			toggleRemindBefore(true);
			toggleRepeat(true);
			iconFile = '{{ $typeCIconPath }}';
		}
		var iconSrc = iconFile;
		
		$("#contentTypeIcon").attr('src', iconSrc);
	}

	function groupOrFolderChanged(groupOrFolderId)
	{
		var isFolderFlag = $("#isFolderFlag").val();
		if(groupOrFolderId == "")
		{
			var newIsFolderFlag, iconFile, defId, defText, otherControlId, selControlId;
			if(isFolderFlag == 1)
			{
				newIsFolderFlag = 0;
				iconFile = '{{ $groupIconPath }}';
				selControlId = 'groupId';
				otherControlId = 'folderId';
				@if(isset($defaultGroup) && isset($defaultGroup['id']))
					defId = "{{ $defaultGroup['id'] }}";
					defText = "{{ $defaultGroup['text'] }}";
				@endif
			}
			else
			{
				newIsFolderFlag = 1;
				iconFile = '{{ $folderIconPath }}';
				selControlId = 'folderId';
				otherControlId = 'groupId';
				@if(isset($defaultFolder) && isset($defaultFolder['id']))
					defId = "{{ $defaultFolder['id'] }}";
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
			
			var iconSrc = iconFile;
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
		@if($isLocked == 0 && !$contentIsRemoved)
			var currStatus = $("#isMarked").val();
			var updateStatus = 1;
			
			if(currStatus == 1)
				updateStatus = 0;
				
			var iconFile = '';
			if(updateStatus == 1)
			{
				iconFile = '{{ $isMarkedIconPath }}';
			}
			else
			{
				iconFile = '{{ $isUnMarkedIconPath }}';
			}

			$("#contentIsMarkedIcon").attr('src', iconFile);
			
			$("#isMarked").val(updateStatus);
			
			@if($isView)
				saveContentIsMarkToggled();
			@endif
		@endif		
	}
	
	function toggleTagHasSelection()
	{
		var tagListStr = $('#tagList').val();
		var tagArr = JSON.parse(tagListStr);
		if(tagArr !== undefined && tagArr.length > 0)
		{
			$("#imgBsTag").addClass('bsSelected');
		}
		else
		{
			$("#imgBsTag").removeClass('bsSelected');
		}
	}
	
	function toggleSourceHasSelection()
	{
		var sourceId = $('#sourceId').val();
		if(sourceId !== undefined && sourceId != "")
		{
			$("#imgBsSource").addClass('bsSelected');
		}
		else
		{
			$("#imgBsSource").removeClass('bsSelected');
		}
	}
	
	function toggleLocationHasSelection()
	{
		var locationId = $('#locationId').val();
		if(locationId !== undefined && locationId*1 > 0)
		{
			$("#imgBsLocation").addClass('bsSelected');
		}
		else
		{
			$("#imgBsLocation").removeClass('bsSelected');
		}
	}
	
	function toggleRemindBeforeHasSelection()
	{
		var remindBeforeMillis = $('[name="remindBeforeMillis"]').val();
		if(remindBeforeMillis !== undefined && remindBeforeMillis*1 > 0)
		{
			$("#imgBsRemindBefore").addClass('bsSelected');
		}
		else
		{
			$("#imgBsRemindBefore").removeClass('bsSelected');
		}
	}
	
	function toggleRepeatHasSelection()
	{
		var repeatDuration = $('[name="repeatDuration"]').val();
		if(repeatDuration !== undefined && repeatDuration != "")
		{
			$("#imgBsRepeat").addClass('bsSelected');
		}
		else
		{
			$("#imgBsRepeat").removeClass('bsSelected');
		}
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

	function addCloudFileRow(mappedFileDetailsObj)
	{
		associatedCloudAttachments.push(mappedFileDetailsObj);

		var attachmentId = mappedFileDetailsObj.id || 0;
		var attachmentFileSize = mappedFileDetailsObj.fileSizeKB;
		var attachmentFileName = mappedFileDetailsObj.fileName;
		var cloudStorageTypeId = mappedFileDetailsObj.cloudStorageTypeId;
		var cloudFileUrl = mappedFileDetailsObj.fileStorageUrl;
		var cloudFileId = mappedFileDetailsObj.fileId;

		var cldAttachmentRowHtml = '';

		cldAttachmentRowHtml += '<div class="row attListRow">';						
		cldAttachmentRowHtml += '<div class="col-md-offset-1 col-md-8">';
		cldAttachmentRowHtml += '<div class="form-group">';
		cldAttachmentRowHtml += '<span class="modalTextmodalText">' + attachmentFileName + '</span>';
		cldAttachmentRowHtml += '</div>';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_attachment_id" value="' + attachmentId + '" />';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_storage_type_id" value="' + cloudStorageTypeId + '" />';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_cloud_file_url" value="' + cloudFileUrl + '" />';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_cloud_file_id" value="' + cloudFileId + '" />';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_file_size" value="' + attachmentFileSize + '" />';
		cldAttachmentRowHtml += '<input type="hidden" class="cldAtt_file_name" value="' + attachmentFileName + '" />';
		cldAttachmentRowHtml += '</div>';
		cldAttachmentRowHtml += '<div class="col-md-2" align="right">';
		cldAttachmentRowHtml += '<button type="button" class="btn btn-xs btn-danger" onclick="removeUploadedCloudFile(this, ' + attachmentId + ');">';
		cldAttachmentRowHtml += '<i class="fa fa-trash"></i>';
		cldAttachmentRowHtml += '</button>';
		cldAttachmentRowHtml += '</div>';
		cldAttachmentRowHtml += '</div>';

		$('#divAttachments').append(cldAttachmentRowHtml);
	}
	
	function removeUploadedCloudFile(btnObj, attId)
	{
		var isCloud = 1;
		removeUploadedRelevantFile(isCloud, btnObj, attId);
	}
	
	function removeUploadedFile(btnObj, attId)
	{
		var isCloud = 0;
		removeUploadedRelevantFile(isCloud, btnObj, attId);
	}
	
	function removeUploadedRelevantFile(isCloud, btnObj, attId)
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

	function selectAndLoadTemplate(isConversation = 0)
	{
		var tempId = $("#templateId").val();
		if(tempId != "")
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
						if(isConversation === 1)
						{
							const tempText = stripHtmlFromContentText(tempHtml);
							$('#contentPartText').val(tempText);
						}
						else
						{
							CKEDITOR.instances.content.setData(tempHtml);
						}
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
        	$('#faColorCodeIcon').css('background-color', contentColorCode);
        	$('.divContentText').css('background-color', contentColorCode);

			// CKEDITOR.replace( '#content', {
			// 	uiColor: contentColorCode
			// });

			// $(".ckeditor").each(function() {
			// 	console.log('$(this) : ', $(this));
			// 	console.log('$(this).attr("id") : ', $(this).attr("id"));
			// 	CKEDITOR.replace($(this).attr("id"), {
			// 		uiColor: contentColorCode
			// 	});
			// });
        	
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

		toggleSourceHasSelection();
	}
	
	function tagsChanged()
	{
		@if($isView)
        	savePartialContentUpdate('TAG');
		@endif

		toggleTagHasSelection();
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
	
		var urlStr = "{{ route('content.profileDefaultFolderGroupDetails') }}";
		var dataToSend = compileSessionParams(orgId);

		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(cnfData)
			{
				if(cnfData.status*1 > 0)
				{
					if(cnfData.msg != "")
						successToast.push(cnfData.msg);

					$("#isFolderFlag").val(1);
					$('#folderId').val(cnfData.defaultFolderId);
					$('#spanGroupOrFolderText').text(cnfData.defaultFolderName);
				}
				else
				{
					if(cnfData.msg != "")
						errorToast.push(cnfData.msg);
				}
			}
		});
	}

	function templateChanged()
	{
		@if(!$isView)
			selectAndLoadTemplate(0);
		@elseif($isConversation)
			selectAndLoadTemplate(1);
		@endif
	}
	
	function saveContentIsMarkToggled() {
		@if($isLocked == 0)
			var contentId = '{{ $id }}';
			var isFolderFlag = $("#isFolderFlag").val();
			var dataToSend = compileSessionParams(contentOrgKey);
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
						refreshContentList(contentOrgKey);
						performRefreshKanbanBoard();
						refreshContentCalendarEntries();	

						performPartOperationPush();		
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
			var isSilent = 0;
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
				if(folderId != "")
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
				isSilent = 1;
			}
			
			if(performSave == true)
			{
				var dataToSend = compileSessionParams(contentOrgKey);
				@if($isFavoritesTab)
					dataToSend = compileSessionParamsForFavorites();
				@endif
				
				var contentId = '{{ $id }}';
				var isFolderFlag = $("#isFolderFlag").val();
				dataToSend += '&id='+contentId+'&isFolder='+isFolderFlag+'&isMetaUpdate='+'1'+partialData;
				if(isFolderFlag*1 == 1)
				{
					isSilent = 0; 
				}
				
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
							refreshContentList(contentOrgKey);
							performRefreshKanbanBoard();
							refreshContentCalendarEntries();

							performPartOperationPush(isSilent, data.notifOpCode);	
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
		@if($appHasTypeReminder == 1 || $appHasTypeCalendar == 1)
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
				var isIntVal = 0;
				var displayFieldId = 'spanGroupOrFolderText';
				var callbackName = 'groupOrFolderChanged';
				
				var enableDataToggle = 1;
				@if($id != "")
					enableDataToggle = 0;
				@endif
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			@endif
		}
		else
		{
			@if(!$isView && $id == "")
				var depCode = 'GROUP';
				var fieldId = 'groupId';
				var hasDone = 0;
				var isMultiSelect = 0;
			    var hasCancel = 1;
				var isMandatory = 1;
				var isIntVal = 0;
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
			@if(($isLocked == 1 || $contentIsRemoved) && isset($selSourceName) && $selSourceName != "")
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
				var isIntVal = 0;
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

	function openCloudAttachmentSelectionModal(isLinked, cloudStorageType, cloudStorageTypeName) {
		@if(!$isView)
			if(isLinked == 1)
			{
				var urlStr = "{!! route('content.loadContentCloudAttachmentSelectionModal') !!}";

				var dataToSend = "orgId=" + $('#conOrgId').val()+"&userId="+getCurrentUserId()+"&loginToken="+getCurrentLoginToken();
				dataToSend += "&cloudStorageType="+cloudStorageType+"&forContent=1";

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
							$('#divForCloudAttachmentSelection').html(data.view);
							$('#cloudAttachmentSelectionModal').modal("show");

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
			else
			{
				errorToast.push('Please link your ' + cloudStorageTypeName + ' first');
			}
		@endif		
	}

	function onCloudAttachmentsPickedForContent(mappedFileDetailsArr)
	{
		for(let i = 0; i < mappedFileDetailsArr.length; i++)
		{
			var mappedFileDetailsObj = mappedFileDetailsArr[i];
			addCloudFileRow(mappedFileDetailsObj);
		}
	}
	
	function openTagSelectionModal() {
		@if(($isLocked == 1 || $contentIsRemoved) && isset($tagNameStr) && $tagNameStr != "")
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
			var isIntVal = 0;
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
				var displayFieldId = '';
				var callbackName = 'toggleRemindBeforeHasSelection';
				var enableDataToggle = 0;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			}
			else
			{
				var dispToast = new ax5.ui.toast({
					icon: '<i class="fa fa-clock-o"></i>',
					containerPosition: "top-right",
					closeIcon: '<i class="fa fa-times"></i>',
					theme: 'warning'
				});
				dispToast.push("Remind before can be used with content of type reminder or calendar");
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
				var displayFieldId = '';
				var callbackName = 'toggleRepeatHasSelection';
				var enableDataToggle = 0;
			
				openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
			}
			else
			{
				var dispToast = new ax5.ui.toast({
					icon: '<i class="fa fa-clock-o"></i>',
					containerPosition: "top-right",
					closeIcon: '<i class="fa fa-times"></i>',
					theme: 'warning'
				});
				dispToast.push("Repeat can be used with content of type calendar");
			}
		@endif
	}
	
	function openProfileSelectionModal()
	{
		@if(!$isView && $id == "")	
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
		@if(!$isView || ($isView && $isConversation))
			var depCode = 'TEMPLATE';
			var fieldId = 'templateId';
			var hasDone = 0;
			var isMultiSelect = 0;
		    var hasCancel = 1;
			var isMandatory = 0;
			var isIntVal = 0;
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

	function openContentDateTimeModificationModal() {
		@if($isView && $isLocked == 0 && !$contentIsRemoved && isset($content))
			$("#contentDependencyModal").modal("hide");	
			$("#contentModifyDateTimeModal").modal("hide");	

			var dataToSend = "orgId=" + $('#conOrgId').val() + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken();
			dataToSend += "&contentTypeId=" + {{ $content->content_type_id }} + "&fromTs=" + '{{ $content->fromTs }}' + "&toTs=" + '{{ $content->toTs }}';
			dataToSend += "&contentId=" + '{{ $id }}' + "&isFolder=" + '{{ $isFolder }}' + '&isConversation=' + '{{ $isConversation ? 1 : 0 }}';
			
			var urlStr = "{!! route('content.appuserContentModifyDateTimeModal') !!}";
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
						$("#contentModifyDateTimeModal").modal("show");				
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
	
	function shareContent() 
	{
		var isFolder = {{ $isFolderFlag }};
		var forContent = '{{ $id }}';
		var groupIdArr;
		var userIdArr;
		var isLocked;
		var isShareEnabled;
		$('#addEditContentModal').modal("hide");
		performShareContentToUser(isFolder, groupIdArr, userIdArr, isLocked, isShareEnabled, forContent, contentOrgKey);
	}

	function setUpConversationAttachmentReply(attachmentId, attachmentFilename)
	{
		resetConversationPartOperationDependencies();

		let convIndex = -1;
		let convSender = '';

		$('#isEditOp').val(0);
		$('#isReplyOp').val(1);
		$('#contentPartText').val(attachmentFilename + '\n');
		$('#contentPartIndex').val(convIndex);
		$('#contentPartText').focus();

		$('#newConversationReplySender').html(convSender);
		$('#newConversationReplyContent').html(attachmentFilename);
		$('#newConversationReplyDiv').show();

		successToast.push('Type message in text field for replying to the thread');
	}

	function setUpConversationPartReply(convIndex, consConvIndex, convText, convSender)
	{
		resetConversationPartOperationDependencies();

		$('#isEditOp').val(0);
		$('#isReplyOp').val(1);
		$('#contentPartText').val('');
		$('#contentPartIndex').val(consConvIndex);

		let fetchedConvText = contentConversationJSArr[convIndex].content;
		fetchedConvText = decodeURIComponent(fetchedConvText);
		fetchedConvText = br2nl(fetchedConvText);
		fetchedConvText = $("<p/>").html(fetchedConvText).text();

		$('#newConversationReplySender').html(convSender);
		$('#newConversationReplyContent').html(fetchedConvText);
		$('#newConversationReplyDiv').show();

		successToast.push('Type message in text field for replying to the thread');
	}

	function setUpConversationPartEdit(convIndex, consConvIndex, convText)
	{
		resetConversationPartOperationDependencies();

		let fetchedConvText = contentConversationJSArr[convIndex].content;
		fetchedConvText = decodeURIComponent(fetchedConvText);
		fetchedConvText = br2nl(fetchedConvText);
		fetchedConvText = $("<p/>").html(fetchedConvText).text();

		$('#isEditOp').val(1);
		$('#isReplyOp').val(0);
		$('#contentPartText').val(fetchedConvText);
		$('#contentPartIndex').val(consConvIndex);

		successToast.push('Type message in text field for editing the thread');
	}

	function confirmAndPerformConversationPartDelete(convIndex, consConvIndex, convText)
	{
		resetConversationPartOperationDependencies();

		$('#contentPartIndex').val(consConvIndex);

		bootbox.dialog({
			message: "Do you really want to delete this chat?<br><b>" + convText + "</b>",
			title: "Confirm Delete",
				buttons: {
					yes: {
					label: "Yes",
					className: "btn-primary",
					callback: function() {
						performConversationPartOperation('DLT');
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

	function confirmAndPerformConversationPartForward(convIndex, consConvIndex, convText)
	{
		resetConversationPartOperationDependencies();

		let fetchedConvText = contentConversationJSArr[convIndex].content;
		fetchedConvText = decodeURIComponent(fetchedConvText);
		fetchedConvText = br2nl(fetchedConvText);
		fetchedConvText = $("<p/>").html(fetchedConvText).text();

		var dataToSend = compileSessionParams();
		dataToSend += "&convText="+encodeURIComponent(fetchedConvText);

		var urlStr = "{!! route('content.sharePartContentRecipientSelectionModal') !!}";

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
						
					$('#addEditContentModal').modal('hide');

					$("#divShareContentOptions").html(data.view);
					$("#selPartContentRecipientModal").modal('show');
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}

	function confirmAndPerformConversationPartCopy(convIndex, consConvIndex, convText)
	{
		resetConversationPartOperationDependencies();

		let fetchedConvText = contentConversationJSArr[convIndex].content;
		fetchedConvText = decodeURIComponent(fetchedConvText);
		fetchedConvText = br2nl(fetchedConvText);
		fetchedConvText = $("<p/>").html(fetchedConvText).text();

		copyTextToClipboard(fetchedConvText);
	}

	function confirmAndPerformConversationPartEditOrReplyOperation()
	{
		if(totalConversationCount >= maxConversationCount)
		{
			var dispToast = new ax5.ui.toast({
			    icon: '<i class="fa fa-info"></i>',
			    closeIcon: '<i class="fa fa-times"></i>',
				containerPosition: "top-right",
				theme: 'warning'
			});
			dispToast.push(maxConversationMessage);
			return;
		}
		else
		{
			var isDataValid = true;
			var validationMsg = '';

			var editOrReplyText = $('#contentPartText').val();
			editOrReplyText = editOrReplyText.trim();

			editOrReplyText = nl2br(editOrReplyText);

			if(editOrReplyText === '')
			{
				isDataValid = false;
				validationMsg = 'Message cannot be empty';
			}

			var convIndex = $('#contentPartIndex').val();
			var isEditOp = $('#isEditOp').val();
			var isReplyOp = $('#isReplyOp').val();

			var opCode = '';
			if(isEditOp*1 == 1)
			{
				opCode = 'EDT';
				if(convIndex == '' || convIndex*1 < -1)
				{
					isDataValid = false;
					validationMsg = 'Incorrect conversation part';
				}
			}
			else if(isReplyOp*1 == 1)
			{
				opCode = 'RPL';
			}

			if(opCode == '')
			{
				opCode = 'RPL';
				$('#contentPartIndex').val(-1);
			}

			performConversationPartOperation(opCode);
		}			
	}

	function performConversationPartOperation(opCode)
	{
		var contentId = '{{ $id }}';
		var contentIsFolder = '{{ $isFolderFlag }}';

		var currDate = new Date();
		var updateTimeStamp = currDate.getTime();

		var convIndex = $('#contentPartIndex').val();

		var editOrReplyText = $('#contentPartText').val();
		editOrReplyText = editOrReplyText.trim();
		editOrReplyText = nl2br(editOrReplyText);

		var editOrReplyTextEmpty = true, isDataValid = false;
		if(editOrReplyText != '' && editOrReplyText.length > 0)
		{
			editOrReplyTextEmpty = false;
			isDataValid = true;
		}

		var dataToSend = "orgId=" + $('#conOrgId').val()+"&userId="+getCurrentUserId()+"&loginToken="+getCurrentLoginToken();
		dataToSend += "&contentId="+contentId+"&isFolder="+contentIsFolder+"&convIndex="+convIndex+"&updateTimeStamp="+updateTimeStamp;
		
		var isDeleteOp = 0, isEditOp = 0, isReplyOp = 0;
		if(opCode == 'DLT')
		{
			isDataValid = true;
			isDeleteOp = 1;
			dataToSend += "&isDeleteOp="+isDeleteOp;
		}
		else if(opCode == 'EDT')
		{
			isEditOp = 1;
			dataToSend += "&isEditOp="+isEditOp+"&editText="+editOrReplyText;
		}
		else if(opCode == 'RPL')
		{
			isReplyOp = 1;
			dataToSend += "&isReplyOp="+isReplyOp+"&replyText="+editOrReplyText;
		}

		if(isDataValid == true)
		{
			var urlStr = "{!! route('content.performContentConversationPartOperation') !!}";

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					resetConversationPartOperationDependencies();
					if(data.status*1 > 0)
					{	
						// performPartOperationPush();
						reloadContentDetailsModal();

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
		else
		{
			errorToast.push('Message cannot be empty');
		}
	}

	function performPartOperationPush(isSilent = 0, notifOpCode = '')
	{
		var contentId = '{{ $id }}';
		var contentIsFolder = '{{ $isFolderFlag }}';
		var tempConOrgId = $('#conOrgId').val();

		performContentModificationPush(contentId, contentIsFolder, tempConOrgId, isSilent, notifOpCode);
	}

	function confirmAndPerformConversationPartReplyWithAttachmentOperation()
	{
		if(totalConversationCount >= maxConversationCount)
		{
			var dispToast = new ax5.ui.toast({
			    icon: '<i class="fa fa-info"></i>',
			    closeIcon: '<i class="fa fa-times"></i>',
				containerPosition: "top-right",
				theme: 'warning'
			});
			dispToast.push(maxConversationMessage);
			return;
		}
		else
		{
			$('#addEditContentModal').modal("hide");
			showContentDetails('{{ $id }}', '{{ $isFolderFlag }}', 0, 1, '{{ $selGroupOrFolderId }}', '{{ $orgKey }}');
		}
	}

	function createVirtualFolderFromContent(contOrgKey)
	{
		var contentIsFolderForFilter = -1;
		var contentSenderEmailForFilter = "{{ $content ? $content->shared_by_email : '' }}";

		var locIsVirtualFolderFlag, locVirtualFolderId;
		var additionalParamsForFilter = {
			senderEmail: contentSenderEmailForFilter,
			filtersNonModifiable: 1
		};

		if(contentIsFolderForFilter*1 == 1)
		{
			additionalParamsForFilter.chkShowFolder = 1;
			additionalParamsForFilter.contentFolderId = "{{ $selGroupOrFolderId }}";
		}
		else if(contentIsFolderForFilter*1 == 0)
		{
			additionalParamsForFilter.chkShowGroup = 1;
			additionalParamsForFilter.contentGroupId = "{{ $selGroupOrFolderId }}";
		}
		else
		{
			additionalParamsForFilter.chkShowFolder = 1;
			additionalParamsForFilter.chkShowGroup = 1;
		}

		$('#addEditContentModal').modal("hide");

		showFilterOptions(contentIsFolderForFilter, locIsVirtualFolderFlag, locVirtualFolderId, additionalParamsForFilter, contOrgKey);
	}

	function dowloadCalendarContentAsIcsFile()
	{
		var fromTimeStr = getCalendarDateTimeFromTimestamp('{{ $fromTs }}');
		var toTimeStr = getCalendarDateTimeFromTimestamp('{{ $toTs }}');

		var strippedContentText = '';

		@if($isConversation && count($contentConversationDetails) > 0)
			strippedContentText = "{{ $encodedContentConversationText }}";
		@else
			strippedContentText = '{{ $encodedStrippedContentText }}';
		@endif

		performDowloadCalendarContentAsIcsFile(strippedContentText, fromTimeStr, toTimeStr);
	}

	function saveGroupContentAsFolderNote()
	{
		var depCode = 'FOLDER';
		var fieldId = 'groupContentSaveToFolderId';
		var hasDone = 0;
		var isMultiSelect = 0;
	    var hasCancel = 1;
		var isMandatory = 1;
		var isIntVal = 0;
		var displayFieldId = '';
		var callbackName = 'saveGroupContentAsFolderNoteFolderSelected';
		
		var enableDataToggle = 1;
		@if($id != "")
			enableDataToggle = 0;
		@endif
	
		openDependencyModal(depCode, fieldId, isMultiSelect, hasDone, hasCancel, isMandatory, isIntVal, displayFieldId, callbackName, enableDataToggle);
	}

	function saveGroupContentAsFolderNoteFolderSelected()
	{
		var groupContentSaveToFolderId = $("#groupContentSaveToFolderId").val();
		if(groupContentSaveToFolderId != "")
		{
			var contentId = '{{ $id }}';
			var contentGroupId = "{{ $selGroupOrFolderId }}";
			var contentOrgKey = $('#conOrgId').val();

			var dataToSend = "orgId=" + contentOrgKey + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken();
			dataToSend += "&contentId=" + contentId + "&groupId=" + contentGroupId + "&copyToFolderId=" + groupContentSaveToFolderId;

			var urlStr = "{!! route('content.copyGroupContentToFolder') !!}";

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					resetConversationPartOperationDependencies();
					if(data.status*1 > 0)
					{	
						$('#addEditContentModal').modal("hide");
						refreshContentList(contentOrgKey);
						performRefreshKanbanBoard();
						refreshContentCalendarEntries();

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
	}

	function resetConversationPartOperationDependencies()
	{
		$('#isEditOp').val(0);
		$('#isReplyOp').val(0);
		$('#contentPartText').val('');
		$('#contentPartIndex').val(-1);

		$('#newConversationReplySender').html('');
		$('#newConversationReplyContent').html('');
		$('#newConversationReplyDiv').hide();
	}

	function reloadContentDetailsModal()
	{
		refreshContentList(contentOrgKey);
		performRefreshKanbanBoard();
		refreshContentCalendarEntries();

		reloadContentDetailsView();
	}

	function nl2br(str) 
	{
		if (typeof str === 'undefined' || str === null) {
			return '';
		}

		var breakTag = '<br>';
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}

	function br2nl(str)
	{		
		var replaceStr = "\n";
		return str.replace(/<\s*\/?br\s*[\/]?>/gi, replaceStr);
	}

	function fallbackCopyTextToClipboard(text)
	{
		var textArea = document.createElement("textarea");
		textArea.value = text;

		// Avoid scrolling to bottom
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.position = "fixed";

		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			var successful = document.execCommand('copy');
			var msg = successful ? 'successful' : 'unsuccessful';
			// console.log('Fallback: Copying text command was ' + msg);
		} catch (err) {
			// console.error('Fallback: Oops, unable to copy', err);
		}

		document.body.removeChild(textArea);
	}

	function copyTextToClipboard(text)
	{
		if (!navigator.clipboard) {
			fallbackCopyTextToClipboard(text);
			return;
		}
		navigator.clipboard.writeText(text).then(function() {
			// console.log('Async: Copying to clipboard was successful!');
		}, function(err) {
			// console.error('Async: Could not copy text: ', err);
		});
	}

	function stripHtmlFromContentText(html)
	{
		let tmp = document.createElement("DIV");
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || "";
	}
	
	function chatInputWordCounter() {
	   let count = $("#contentPartText").val().length;
    	$("#chatWordCount").text(count);
    }

</script>
