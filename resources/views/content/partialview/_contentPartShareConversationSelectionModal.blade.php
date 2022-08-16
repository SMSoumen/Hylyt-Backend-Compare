@php
	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
	$headerIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$folderIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconFolderPath');
	$groupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconGroupPath'));
	$tagIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTagPath'));
	$attachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconAttachmentPath'));
	$isMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsMarkedPath'));
	$isUnMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsUnMarkedPath'));
	$isLockedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsLockedPath'));
	$isRestrictedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsRestrictedPath'));
	$contentSenderIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconSenderPath'));
	$typeRIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeRPath'));
	$typeAIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeAPath'));
	$typeCIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeCPath'));
                
    $contentTypeR = Config::get("app_config.content_type_r");
    $contentTypeA = Config::get("app_config.content_type_a");
    $contentTypeC = Config::get("app_config.content_type_c");

    $listCodeFavNotes = Config::get('app_config.dashMetricFavNotesCode');
    $listCodeFavFolders = Config::get('app_config.dashMetricFavFoldersCode');
    $listCodeAllNotes = Config::get('app_config.dashMetricAllNotesCode');
    $listCodeReminderNotes = Config::get('app_config.dashMetricReminderNotesCode');
    $listCodeCalendarNotes = Config::get('app_config.dashMetricCalendarNotesCode');
    $listCodeConversationNotes = Config::get('app_config.dashMetricConversationNotesCode');
    $listCodeTrashNotes = Config::get('app_config.dashMetricTrashNotesCode');

    $consTheme = 'thm-blue';

    $noContentsFound = TRUE;

    $contentSrNo = 0;

	$tagStrDelimiter = ',';

    $hasFolderGroupList = TRUE;
@endphp

<div id="selPartContentConversationModal" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<div class="row">
					<div class="col-md-3" align="left">
						<h4 class="modal-title" id="selRecipientAppuserHeader">
							Share Conversation
						</h4>
					</div>
					<div class="col-md-9" align="right">
						<button type="button" class="btn btn-default btn-sm" id="selConversationForPartShareAddNewBtn">
							Add New
						</button>	
						&nbsp; &nbsp;
						<button type="button" class="btn btn-default btn-sm" id="selConversationForPartShareSendBtn">
							Send
						</button>	
						&nbsp; &nbsp;
						<button type="button" class="btn btn-danger btn-sm" id="selConversationForPartShareCancelBtn">
							Cancel
						</button>					
					</div>
				</div>
			</div>
			<div class="modal-body">
				<section class="active-content-list">

					@if(isset($contentArr) && $contentCnt > 0)

						@php
				    	$noContentsFound = FALSE;
				    	@endphp

						@if($isAllNotes == 1 && $showFolderHeader)
							<div class="list-belongs-to-header-div">Folder<!--  ({{ $contentCnt }} Note(s)) --></div>
						@endif

						@foreach($contentArr as $contentObj)
							
							@php

							$contentSrNo++;

							$contentId = $contentObj['id'];
							$contentIsFolder = $contentObj['isFolder'];
							$contentText = $contentObj['strippedContentAsIs']; // strippedContentText
							$contentCrtDate = $contentObj['createUtc'];
							$contentModDate = $contentObj['updateUtc'];
							$contentTypeId = $contentObj['contentType'];
							$contentIsMarked = $contentObj['isMarked'];
							$contentSenderStr = $contentObj['senderStr'];
							$contentTagStr = $contentObj['tagStr'];
							$contentHasAttachment = $contentObj['hasAttachment'];
							$contentColorCode = $contentObj['colorCode'];
							$contentIsLocked = $contentObj['isLocked'];
							$contentIsShareEnabled = $contentObj['isShareEnabled'];
							$contentFromDate = $contentObj['startUtc'];
							$contentToDate = $contentObj['endUtc'];
							$contentTagStr = $contentObj['tagStr'];
				            $contentTagArr = isset($contentTagStr) && $contentTagStr != "" && $contentTagStr != "-" ? explode($tagStrDelimiter, $contentTagStr) : array();

							$contentBelongsToName = "";
							$contentBelongsToIconPath = "";
							if($contentIsFolder == 1)
							{
								$contentBelongsToName = $contentObj['folderName'];
								$contentBelongsToIconPath = $folderIconPath;
							}
							else
							{
								$contentBelongsToName = $contentObj['groupName'];
								$contentBelongsToIconPath = $groupIconPath;
							}

							$contentReminderDateStr = "";
							$contentTypeIconPath = "";
							switch($contentTypeId)
							{
								case $contentTypeR:
									{
										$contentTypeIconPath = $typeRIconPath;
										$contentReminderDateStr = dbToDispDateTimeWithTZ($contentFromDate, $tzStr);
										break;
									}
								case $contentTypeA:
									{
										$contentTypeIconPath = $typeAIconPath;
										break;
									}
								case $contentTypeC:
									{
										$contentTypeIconPath = $typeCIconPath;
										$contentReminderDateStr = dbToDispDateTimeWithTZ($contentFromDate, $tzStr).' - '.dbToDispDateTimeWithTZ($contentToDate, $tzStr);;
										break;
									}
								default: 
									{
										break;
									}
							};

							@endphp

							
							@include('content.partialview._contentListRow')


						@endforeach

					@endif


					@if(isset($secContentArr) && $secContentCnt > 0)

						@php
				    	$noContentsFound = FALSE;
				    	@endphp

						@if($isAllNotes == 1 && $showGroupHeader)
							<div class="list-belongs-to-header-div">Group<!--  ({{ $secContentCnt }} Note(s)) --></div>
						@endif

						@foreach($secContentArr as $contentObj)
							
							@php

							$contentSrNo++;

							$contentId = $contentObj['id'];
							$contentIsFolder = $contentObj['isFolder'];
							$contentText = $contentObj['strippedContentText'];
							$contentCrtDate = $contentObj['createUtc'];
							$contentModDate = $contentObj['updateUtc'];
							$contentTypeId = $contentObj['contentType'];
							$contentIsMarked = $contentObj['isMarked'];
							$contentSenderStr = $contentObj['senderStr'];
							$contentTagStr = $contentObj['tagStr'];
							$contentHasAttachment = $contentObj['hasAttachment'];
							$contentColorCode = $contentObj['colorCode'];
							$contentIsLocked = $contentObj['isLocked'];
							$contentIsShareEnabled = $contentObj['isShareEnabled'];
							$contentFromDate = $contentObj['startUtc'];
							$contentToDate = $contentObj['endUtc'];
							$contentTagStr = $contentObj['tagStr'];
				            $contentTagArr = isset($contentTagStr) && $contentTagStr != "" && $contentTagStr != "-" ? explode($tagStrDelimiter, $contentTagStr) : array();

							$contentBelongsToName = "";
							$contentBelongsToIconPath = "";
							if($contentIsFolder == 1)
							{
								$contentBelongsToName = $contentObj['folderName'];
								$contentBelongsToIconPath = $folderIconPath;
							}
							else
							{
								$contentBelongsToName = $contentObj['groupName'];
								$contentBelongsToIconPath = $groupIconPath;
							}

							$contentReminderDateStr = "";
							$contentTypeIconPath = "";
							switch($contentTypeId)
							{
								case $contentTypeR:
									{
										$contentTypeIconPath = $typeRIconPath;
										$contentReminderDateStr = dbToDispDateTimeWithTZ($contentFromDate, $tzStr);
										break;
									}
								case $contentTypeA:
									{
										$contentTypeIconPath = $typeAIconPath;
										break;
									}
								case $contentTypeC:
									{
										$contentTypeIconPath = $typeCIconPath;
										$contentReminderDateStr = dbToDispDateTimeWithTZ($contentFromDate, $tzStr).' - '.dbToDispDateTimeWithTZ($contentToDate, $tzStr);;
										break;
									}
								default: 
									{
										break;
									}
							};

							@endphp

							
							@include('content.partialview._contentListRow')


						@endforeach

					@endif


					@if($noContentsFound)
						<div class="noContentsDiv">No Content(s)</div>
					@endif

				</section>
			</div>
			<div class="modal-footer">
				<!-- <div class="row">
					<div class="col-md-6" align="left">
						<button type="button" class="btn btn-default btn-sm" id="selConversationForPartShareCancelBtn">
							Cancel
						</button>						
					</div>
					<div class="col-md-6" align="right">
						<button type="button" class="btn btn-primary btn-sm" id="selConversationForPartShareSendBtn">
							Send
						</button>						
					</div>
				</div> -->
			</div>
		</div>
	</div>
</div>

<script>
	var contentOpSelFolderContentIdArr = new Array();
	var contentOpSelGroupContentIdArr = new Array();
	var isFolder = '{{ $selIsFolder }}';
	var contentOrgKey = '{{ $currOrgKey }}';	
	var contentUserId = '{{ $currUserId }}';	
	var contentLoginToken = '{{ $currLoginToken }}';		
	$(document).ready(function()
	{
		$('[data-toggle="tooltip"]').tooltip({
			trigger : 'hover'
		});

		//Disable cut copy paste
	    $(document).bind('cut copy paste', function (e) {
	        e.preventDefault();
	    });
	     
	    //Disable mouse right click
	    $(document).on("contextmenu",function(e){
	        return false;
	    });

		$('.cbContent'+isFolder).iCheck({
    		checkboxClass: 'icheckbox_flat-yellow',
  		})
  		.on('ifChecked', function(event){
  			var contentId = $(this).val();
			setContentOpSelectionStatus(isFolder, contentId, contentOrgKey, true);
		})
  		.on('ifUnchecked', function(event){
  			var contentId = $(this).val();
			setContentOpSelectionStatus(isFolder, contentId, contentOrgKey, false);
		});
					
		@if($isAllNotes == 1)
			var isGroup = 0;
			$('.cbContent'+isGroup).iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		})
	  		.on('ifChecked', function(event){
	  			var contentId = $(this).val();
				setContentOpSelectionStatus(isGroup, contentId, contentOrgKey, true);
			})
	  		.on('ifUnchecked', function(event){
	  			var contentId = $(this).val();
				setContentOpSelectionStatus(isGroup, contentId, contentOrgKey, false);
			});

	  		@if(!$hasFolderGroupList)
				$('#divMainFolderGroupList').hide();
				$('.srac-header-section-content-list').addClass('srac-header-section-content-list-without-folder');
			@endif
		@endif

		$("#selConversationForPartShareCancelBtn").click(function() {
			$("#selPartContentConversationModal").modal('hide');
		});

		$("#selConversationForPartShareSendBtn").click(function()
		{
			var isAddNew = false;

			var dataToSend = compileSessionParams();
			dataToSend += "&convText="+"{{ rawurlencode($convText) }}";
			dataToSend += "&selAppuserContactId="+"{{ $selAppuserContactId }}";
			dataToSend += "&selGroupId="+"{{ $selGroupId }}";

			performConversationPartForwardOperationPostSelection(dataToSend, isAddNew);
		});

		$('#selConversationForPartShareAddNewBtn').click(function()
		{
			var isAddNew = true;

			var dataToSend = compileSessionParams();
			dataToSend += "&contentText="+"{{ rawurlencode($convText) }}";
			dataToSend += "&withShare="+"0";
			dataToSend += "&conOrgId="+contentOrgKey;

			@if(isset($selAppuserContactId) && $selAppuserContactId > 0)
				dataToSend += "&isFolder="+"1";
				dataToSend += "&sendToEmail="+"{{ $sendToEmail }}";
			@elseif(isset($selGroupId) && $selGroupId > 0)
				dataToSend += "&isFolder="+"0";
				dataToSend += "&folderOrGroupId="+"{{ $selGroupId }}";
			@endif


			performConversationPartForwardOperationPostSelection(dataToSend, isAddNew);
		});
	});

	function setContentOpSelectionStatus(isFolder, contentId, orgId, isSelected)
	{
		if(isFolder == 1)
		{
			const contentIndex = contentOpSelFolderContentIdArr.indexOf(contentId);
			if(isSelected)
			{
			    if(contentIndex === -1) 
			    {
					contentOpSelFolderContentIdArr.push(contentId);
				}
			}
			else
			{    
			    if(contentIndex !== -1) 
			    {
			        contentOpSelFolderContentIdArr.splice(contentIndex, 1);
			    }
			}	
		}
		else
		{
			const contentIndex = contentOpSelGroupContentIdArr.indexOf(contentId);
			if(isSelected)
			{
			    if(contentIndex === -1) 
			    {
					contentOpSelGroupContentIdArr.push(contentId);
				}
			}
			else
			{    
			    if(contentIndex !== -1) 
			    {
			        contentOpSelGroupContentIdArr.splice(contentIndex, 1);
			    }
			}	
		}
	}

	function compileSelConversationListForShareOperation(dataToSend)
	{
		var noErrorFound = true;
		var compContentArrStr = "";
		
		let selFolderCnt = contentOpSelFolderContentIdArr.length;
		let selGroupCnt = contentOpSelGroupContentIdArr.length;

		if(selFolderCnt > 0 && selGroupCnt > 0)
		{
			noErrorFound = false;
    		errorToast.push("Select either folder or group content(s) only");
		}
		else if(selFolderCnt > 0)
		{
			contentArrStr = contentOpSelFolderContentIdArr.join(",");
			isFolder = 1;			
		}
		else if(selGroupCnt > 0)
		{
			contentArrStr = contentOpSelGroupContentIdArr.join(",");
			isFolder = 0;
		}		

	    if(noErrorFound == true)
	    {
		    if(contentArrStr == undefined || contentArrStr == "")
		    {
		    	errorToast.push("Select Content");
		    }
		    else
			{
				compContentArrStr = 'selContentIdArr='+ '['+contentArrStr+']' + '&isFolder=' + isFolder;
			}    	
	    }
		return compContentArrStr; 
	}

	function performConversationPartForwardOperationPostSelection(dataToSend, isAddNew)
	{
		var compDataToSend = '', urlStr = '';
		if(isAddNew === false)
		{
			urlStr = "{!! route('content.performAppuserPartContentShareToConversation') !!}";
			compContentArrStr = compileSelConversationListForShareOperation(dataToSend);
			compDataToSend += dataToSend + '&' + compContentArrStr;
		}
		else
		{
			urlStr = "{!! route('content.saveOneLineChatContent') !!}";
			compDataToSend = dataToSend;
		}
	
		if(compDataToSend != "")
		{
			$('#selPartContentConversationModal').modal('hide');

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: compDataToSend,
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
	}
</script>
