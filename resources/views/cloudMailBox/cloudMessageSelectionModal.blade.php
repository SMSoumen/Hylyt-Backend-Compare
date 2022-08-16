<div id="cloudAttachmentSelectionModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					{{ $modalTitle or null }}		
				</h4>
			</div>
			<div class="modal-body">
				<div id="attachmentListBody"></div>
				<div class="row" id="divForLoadMore" style="display: none;">
					<div class="col-md-12" align="center">
						<button type="button" class="btn btn-default btn-sm" onclick="loadAttachmentListContinuedSubView();">
				    		Load More
				    	</button>
				    </div>
				</div>
				<div class="divForLoader" align="center">
					<div class="loader" id="attachmentListLoader" style="display: none;">
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<style type="text/css">

	.ca-message-row
	{
		height: 55px;
		cursor: pointer;
	    background-color: #f5f5f5;
	    margin: 15px;
	    padding-top: 5px;
	    padding-bottom: 5px;
	    border-radius: 10px;
	}

	.ca-message-time
	{
	    padding-top: 5px;
	    height: 100%;
	    padding-left: 20px;
	    overflow: hidden;
    	text-overflow: ellipsis;
    	white-space: nowrap;
	}

	.ca-message-actions 
	{
	    padding-top: 5px;
	    height: 100%;
	    padding-right: 10px;
	}

	.ca-message-details 
	{
		
	}

	.ca-message-snippet 
	{
	    font-weight: 600;
	}


</style>
<script>
	var consAttachmentFolder = '';
	var consQueryStr = '';
	var consLoadMoreCursor = '';
	var forImport = '{{ $forImport }}';
	var forContent = '{{ $forContent }}';

	$(document).ready(function() 
	{
		loadAttachmentListSubView('');
	});

	function loadAttachmentListSubView(parentFolderName, queryStr = '', baseFolderType = 0) 
	{
		consAttachmentFolder = parentFolderName;
		consQueryStr = queryStr;

		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}

		var dataToSend = "orgId=" + consOrgId + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken() + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&parentFolderName="+consAttachmentFolder+"&renderView=1"+"&queryStr="+queryStr+"&baseFolderType="+baseFolderType;
		
		var urlStr = "{!! route('cloudAttachment.loadRelevantFolderFileList') !!}";

		$('#attachmentListLoader').show();
		$("#attachmentListBody").html('');	
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				$('#attachmentListLoader').hide();
				if(data.status*1 > 0)
				{	
					$("#attachmentListBody").html(data.folderView);	

					var folderResponse = data.folderResponse;
					consLoadMoreCursor = '';
					if(folderResponse.hasLoadMore == 1)
					{
						consLoadMoreCursor = folderResponse.loadMoreCursor;
						$("#divForLoadMore").show();
					}
					else
					{
						$("#divForLoadMore").hide();
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

	function loadAttachmentListContinuedSubView() 
	{
		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}

		var dataToSend = "orgId=" + consOrgId + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken() + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&parentFolderName="+consAttachmentFolder+"&renderView=1"+"&queryStr="+consQueryStr+"&cursorStr="+consLoadMoreCursor;
		
		var urlStr = "{!! route('cloudAttachment.loadRelevantFolderFileContinuedList') !!}";

		$('#attachmentListLoader').show();
		$("#divForLoadMore").hide();
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				$('#attachmentListLoader').hide();
				if(data.status*1 > 0)
				{	
					$("#attachmentListBody").append(data.folderView);	

					var folderResponse = data.folderResponse;
					consLoadMoreCursor = '';
					if(folderResponse.hasLoadMore == 1)
					{
						consLoadMoreCursor = folderResponse.loadMoreCursor;
						$("#divForLoadMore").show();
					}
					else
					{
						$("#divForLoadMore").hide();
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

	function selAttachmentListItem(selFileId)
	{
		var chkBxId = '#chkCAFile'+selFileId;

		var isChecked = $(chkBxId).is(':checked'); 

		if(isChecked)	
		{
			$(chkBxId).prop('checked', false);
		}
		else
		{
			$(chkBxId).prop('checked', true);
		}
	}

	function submitContentAttachmentSelection()
	{
		var compiledFileIdArr = [];
		var compiledFileSizeArr = [];
		var compiledFileIdStr = '';
		var compiledFileSizeStr = '';
		$('.chkCAFile').each(function () {
			var sThisVal = this.checked;

			if(sThisVal)
			{
				var fileInfoStr = $(this).val();

				if(fileInfoStr != '' && fileInfoStr.includes('_'))
				{
					var fileInfoArr = fileInfoStr.split('_');

					if(fileInfoArr.length >= 2)
					{
						var fileId, fileSize;
						if(fileInfoArr.length > 2)
						{
							fileSize = fileInfoArr.splice(-1, 1);
							fileId = fileInfoArr.join("_");
						}
						else
						{
							fileId = fileInfoArr[0];
							fileSize = fileInfoArr[1];							
						}

						if(compiledFileIdStr != '')
						{
							compiledFileIdStr += ',';
						}

						compiledFileIdStr += '"'+fileId+'"';

						if(compiledFileSizeStr != '')
						{
							compiledFileSizeStr += ',';
						}

						compiledFileSizeStr += fileSize;

						compiledFileIdArr.push(fileId);
						compiledFileSizeArr.push(fileSize);
					}
				}
			}
		});

		if(compiledFileIdArr.length > 0)
		{
			compiledFileIdStr = '[' + compiledFileIdStr + ']';
			compiledFileSizeStr = '[' + compiledFileSizeStr + ']';

			let consOrgId = $('#conOrgId').val();
			if(consOrgId === undefined)
			{
				consOrgId = getCurrentOrganizationId();
			}
			
			var dataToSend = "orgId=" + consOrgId + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken() + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
			dataToSend += "&fileIdArr="+compiledFileIdStr+"&fileSizeArr="+compiledFileSizeStr;
			
			var urlStr = "{!! route('cloudAttachment.loadRelevantSelectedFileMappedDetails') !!}";

			$('#attachmentListLoader').show();
			$("#attachmentListBody").html('');	

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					$('#attachmentListLoader').hide();
					$('#cloudAttachmentSelectionModal').modal('hide');

					if(data.status*1 > 0)
					{	
						var mappedDetailsArr = data.mappedDetailsArr;	
						var allFileDetailsFetched = data.allFileDetailsFetched;	
						var fileNotFetchedErrorMsg = data.fileNotFetchedErrorMsg;	

						if(allFileDetailsFetched*1 == 0 && fileNotFetchedErrorMsg != "")
						{
							errorToast.push(fileNotFetchedErrorMsg);
						}

						if(forContent*1 == 1)
						{
							onCloudAttachmentsPickedForContent(mappedDetailsArr);
						}
						else if(forImport*1 == 1)
						{
							onCloudAttachmentsPickedForImport(mappedDetailsArr);
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
	}

	function resetCloudAttachmentListSearchField()
	{
		$('#txtSrchCldAtt').val('');
		refreshCloudAttachmentListSubView();
	}

	function refreshCloudAttachmentListSubView()
	{
		var searchStr = $('#txtSrchCldAtt').val();

		loadAttachmentListSubView(consAttachmentFolder, searchStr);
	}

	function loadAddCloudFolderView()
	{
		cancelAddNewCloudFolder(false);
		cancelAddNewCloudFile(false);
		$('#divAddCloudFolderView').show();
	}

	function performAddNewCloudFolder()
	{
		var consFolderName = $('#txtNewCloudFolderName').val();

		if(consFolderName.trim() == '')
		{
			errorToast.push('Folder name is required');
			return;
		}

		let isValidData = true;

		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}
			
		var dataToSend = compileSessionParams(consOrgId) + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&parentFolderName="+consAttachmentFolder+"&folderName="+consFolderName;

		var urlStr = "{!! route('cloudAttachment.addNewRelevantFolder') !!}";

		if(isValidData)
		{
			successToast.push('Processing folder add');
			cancelAddNewCloudFolder(false);

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					resetCloudAttachmentListSearchField();

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
	}

	function cancelAddNewCloudFolder(reloadData = false)
	{
		$('#txtNewCloudFolderName').val('');
		$('#divAddCloudFolderView').hide();

		if(reloadData !== undefined && reloadData === true)
		{
			resetCloudAttachmentListSearchField();
		}
	}

	function performDeleteCloudFolder(folderId)
	{
		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}
			
		var dataToSend = compileSessionParams(consOrgId) + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&folderId="+folderId;

		var urlDeleteStr = "{!! route('cloudAttachment.removeRelevantFolder') !!}";
		var urlCheckDeleteStr = "{!! route('cloudAttachment.checkRelevantFolderCanBeDeleted') !!}";

		$.ajax({
			type: "POST",
			url: urlCheckDeleteStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(chkData)
			{
				if(chkData.status*1 > 0 && chkData.folderResponse)
				{	
					if(chkData.folderResponse.canBeDeleted * 1 === 1)
					{
						bootbox.dialog({
							message: "Do you really want to permanently delete this folder?",
							title: "Confirm Delete Folder",
								buttons: {
									yes: {
									label: "Yes",
									className: "btn-primary",
									callback: function() 
									{
										$.ajax({
											type: "POST",
											url: urlDeleteStr,
											crossDomain : true,
											dataType: 'json',
											data: dataToSend,
											success: function(data)
											{
												resetCloudAttachmentListSearchField();

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
								},
								no: {
									label: "No",
									className: "btn-primary",
									callback: function()
									{

									}
								}
							}
						});
					}
					else
					{
						if(chkData.folderResponse.validationMsg != "")
							errorToast.push(chkData.folderResponse.validationMsg);
					}
						
				}
				else
				{
					if(chkData.msg != "")
						errorToast.push(chkData.msg);
				}
			}
		});
	}

	function loadAddCloudFileView()
	{
		cancelAddNewCloudFolder(false);
		cancelAddNewCloudFile(false);
		$('#divAddCloudFileView').show();
	}

	function performAddNewCloudFile()
	{
		let isValidData = true;

		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}

        var dataToSend = new FormData();
        dataToSend.append('orgId', consOrgId);
        dataToSend.append('userId', getCurrentUserId());
        dataToSend.append('loginToken', getCurrentLoginToken());
        dataToSend.append('cloudStorageType', "{{ $cloudStorageTypeCode }}");
        dataToSend.append('parentFolderName', consAttachmentFolder);

        var files = $('#inpNewCloudFile')[0].files;

		if(files.length <= 0)
		{
			errorToast.push('File is required');
			return;
		}

		var urlStr = "{!! route('cloudAttachment.uploadRelevantFile') !!}";

		if(isValidData)
		{
           	dataToSend.append('uplFile', files[0]);
           	
			successToast.push('Processing file upload');
			cancelAddNewCloudFile(false);

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				contentType: false,
				processData: false,
				success: function(data)
				{
					resetCloudAttachmentListSearchField();

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
	}

	function cancelAddNewCloudFile(reloadData = false)
	{
		$('#txtNewCloudFile').val('');
		$('#divAddCloudFileView').hide();

		if(reloadData !== undefined && reloadData === true)
		{
			resetCloudAttachmentListSearchField();
		}
	}

	function performViewCloudFile(fileId, fileSizeStr)
	{

		const compiledFileIdStr = '["' + fileId + '"]';
		const compiledFileSizeStr = '["' + fileSizeStr + '"]';

		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}
		
		var dataToSend = "orgId=" + consOrgId + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken() + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&fileIdArr="+compiledFileIdStr+"&fileSizeArr="+compiledFileSizeStr;
		
		var urlStr = "{!! route('cloudAttachment.loadRelevantSelectedFileMappedDetails') !!}";

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
					var mappedDetailsArr = data.mappedDetailsArr;	
					var allFileDetailsFetched = data.allFileDetailsFetched;	
					var fileNotFetchedErrorMsg = data.fileNotFetchedErrorMsg;	

					if(allFileDetailsFetched*1 == 0 && fileNotFetchedErrorMsg != "")
					{
						errorToast.push(fileNotFetchedErrorMsg);
					}
					else
					{
						const fileStorageUrl = mappedDetailsArr && mappedDetailsArr[0] ? mappedDetailsArr[0].fileStorageUrl : "";

						if(fileStorageUrl && fileStorageUrl !== undefined && fileStorageUrl !== "")
						{
							window.open(fileStorageUrl, '_blank').focus();
						}
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

	function performDeleteCloudFile(fileId)
	{
		let consOrgId = $('#conOrgId').val();
		if(consOrgId === undefined)
		{
			consOrgId = getCurrentOrganizationId();
		}
			
		var dataToSend = compileSessionParams(consOrgId) + "&cloudStorageType=" + "{{ $cloudStorageTypeCode }}";
		dataToSend += "&fileId="+fileId;

		var urlDeleteStr = "{!! route('cloudAttachment.removeRelevantFile') !!}";
		var urlCheckDeleteStr = "{!! route('cloudAttachment.checkRelevantFileCanBeDeleted') !!}";

		$.ajax({
			type: "POST",
			url: urlCheckDeleteStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(chkData)
			{
				if(chkData.status*1 > 0 && chkData.fileResponse)
				{	
					if(chkData.fileResponse.canBeDeleted * 1 === 1)
					{
						bootbox.dialog({
							message: "Do you really want to permanently delete this file?",
							title: "Confirm Delete File",
								buttons: {
									yes: {
									label: "Yes",
									className: "btn-primary",
									callback: function() 
									{
										$.ajax({
											type: "POST",
											url: urlDeleteStr,
											crossDomain : true,
											dataType: 'json',
											data: dataToSend,
											success: function(data)
											{
												resetCloudAttachmentListSearchField();

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
								},
								no: {
									label: "No",
									className: "btn-primary",
									callback: function()
									{

									}
								}
							}
						});
					}
					else
					{
						if(chkData.fileResponse.validationMsg != "")
							errorToast.push(chkData.fileResponse.validationMsg);
					}
				}
				else
				{
					if(chkData.msg != "")
						errorToast.push(chkData.msg);
				}
			}
		});
	}
</script>