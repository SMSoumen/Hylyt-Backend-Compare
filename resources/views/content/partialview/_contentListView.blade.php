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
	$isCompletedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsCompletedPath'));
	$isRepeatEnabledIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsRepeatEnabledPath'));
	$isConversationIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsConversationPath'));
                
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

@endphp

@php
	$titleText = '';
	$selGroupFolderIsFavorited = 0;
    $showSelection = TRUE;
    $grpIsAdmin = 0;
    $grpIsTwoWay = 0;
    $grpHasPostRight = 0;
    $hasFolderGroupList = TRUE;
    $isTrashNotes = FALSE;

    $isConversationList = FALSE;

    $consIsFolderFlagForMultiOps = 1;

    $showGrpAddContent = FALSE;
	if($isAllNotes == 1)
	{
    	$consIsFolderFlagForMultiOps = -1;

		$titleText = 'All Note(s)';
		$showSelection = FALSE;
    	$hasFolderGroupList = FALSE;

		if($isVirtualFolder)
		{
			$titleText = $selFolderDetails->folder_name;
			$selGroupFolderIsFavorited = $isFavorited;
    		$hasFolderGroupList = TRUE;
			$showSelection = TRUE;
		}
		else if($listCode == $listCodeFavNotes)
		{
			$titleText = 'Favorite Notes';
			$showSelection = TRUE;
		}
		else if($listCode == $listCodeReminderNotes)
		{
			$titleText = 'Reminders';
			$showSelection = TRUE;
		}
		else if($listCode == $listCodeCalendarNotes)
		{
			$titleText = 'Calendars';
			$showSelection = TRUE;
		}
		else if($listCode == $listCodeConversationNotes)
		{
			$titleText = 'Conversations';
    		$hasFolderGroupList = TRUE;
			$showSelection = TRUE;

			$isConversationList = TRUE;
		}
		else if($listCode == $listCodeAllNotes)
		{
    		$hasFolderGroupList = TRUE;
			$showSelection = TRUE;
		}
		else if($listCode == $listCodeTrashNotes)
		{
    		$isTrashNotes = TRUE;
			$titleText = 'Trash';
    		$hasFolderGroupList = TRUE;
			$showSelection = TRUE;
		}
	}
	elseif($selIsFolder == 1)
	{
    	$consIsFolderFlagForMultiOps = 1;

		$titleText = isset($selFolderDetails->folder_name) ? $selFolderDetails->folder_name : '';
		$selGroupFolderIsFavorited = $isFavorited;
	}
	else
	{
    	$consIsFolderFlagForMultiOps = 0;

		$titleText = isset($selGroupDetails) ? $selGroupDetails->name : '';
		$selGroupFolderIsFavorited = $isFavorited;

		if(isset($groupPer))
		{
			$grpIsTwoWay = isset($groupPer['isTwoWay']) ? $groupPer['isTwoWay'] : 0;
			$grpIsAdmin = isset($groupPer['isAdmin']) ? $groupPer['isAdmin'] : 0;
			$grpHasPostRight = isset($groupPer['hasPostRight']) ? $groupPer['hasPostRight'] : 0;
		}
	}

	$tagStrDelimiter = ',';

	if($forGlobalSearch == 1)
	{
		$showSelection = false;
	}
@endphp

@if($showListHeader)
	@php
		$contentListSearchFieldId = Config::get('app_config_const.div_search_fld_active_content_list');	
		$contentListAddButtonId = Config::get('app_config_const.div_add_btn_active_content_list');
		$contentListSortButtonId = Config::get('app_config_const.div_sort_btn_active_content_list');	
		$contentListFilterButtonId = Config::get('app_config_const.div_filter_btn_active_content_list');	
		$contentListRemoveFilterButtonId = Config::get('app_config_const.div_remove_filter_btn_active_content_list');	
		$contentMainPanelId = Config::get('app_config_const.div_group_content_main_panel');
		$noContentsDivId = Config::get('app_config_const.div_group_no_contents');
		$contentLoaderDivId = Config::get('app_config_const.div_active_content_list_loader');
		$contentListLoadButtonId = Config::get('app_config_const.div_load_btn_active_content_list');
		$markedContentListCount = Config::get('app_config_const.div_marked_active_content_list_count');
		
		$contentListRefreshButtonId = Config::get('app_config_const.div_refresh_btn_group_content_list');	
		$contentListPerformSearchButtonId = Config::get('app_config_const.div_perform_search_btn_group_content_list');	
		$contentListResetSearchButtonId = Config::get('app_config_const.div_reset_search_btn_group_content_list');	
		$contentListFavoriteButtonId = Config::get('app_config_const.div_favorite_btn_group_content_list');		
		$contentListOperationsId = Config::get('app_config_const.div_operations_group_content_list');
		$contentListContentCountId = Config::get('app_config_const.div_note_count_group_content_list');
		
		$contentListIsFavoritedIconId = Config::get('app_config_const.group_is_favorited_icon_id');	

		$filterHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconFilterPath'));
		$removeFilterHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconRemoveFilterPath'));
		$sortHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconSortPath'));
		$multiSelectHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconMultiSelectPath'));
		$refreshHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconRefreshPath'));
		$addHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconAddPath'));
		$addInPopUpHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appInPopUpIconAddPath'));
		$shareHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconSharePath'));
		$attachmentHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconWhiteAttachmentPath'));
		$isMarkedHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconFolderIsMarkedPath'));
		$isUnMarkedHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconFolderIsUnMarkedPath'));
		$isUnMarkedHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconFolderIsUnMarkedPath'));
		$videoConferenceHeaderIconPath = $headerIconThemeUrl.(Config::get('app_config_asset.appIconVideoConferencePath'));
		$selectAllHeaderIconPath = $headerIconThemeUrl.'Folder_SelectAll.png';
		$unselectAllHeaderIconPath = $headerIconThemeUrl.'Folder_UnSelectAll.png';
	@endphp
	
  	@php
  	$consFolderOrGroupIdForFilter = $selFolderOrGroupId;
  	if($isVirtualFolderFlag == 1)
  	{
  		$consFolderOrGroupIdForFilter = $virtualFolderId;
  	}
  	@endphp
	<section>
		<div class="row">
			<div class="col-xs-10">
				<h4 class="content-list-title">{{ $titleText }}</h4>
			</div>
			<div class="col-xs-2" align="right">
      				<img src="{{ $videoConferenceHeaderIconPath }}" class="content-list-header-icon" data-toggle="tooltip" title="Video Conference" onclick="goToVideoConferencingDashboard('{{ $orgKey }}', '{{ $selIsFolder }}', '{{ $consFolderOrGroupIdForFilter }}')"/>
			</div>
		</div> 


		<input type="hidden" id="afavIsFavorited" value="{{ $selGroupFolderIsFavorited }}"/>
		
		@if($isAttachmentView == 0)
			<div class="box-header with-border">
				<div class="row">
					<div class="col-md-4" align="left">
						<span id="{{ $contentListContentCountId }}" class="contentCountDiv">{{ $totalContentCount }} Note(s)</span>
					</div>
					<div class="col-md-4" align="right">
						<span id="divMarkedActiveContentListCount" class="contentCountDiv"></span>
					</div>
				</div>
				
				<div class="mailbox-controls" id="{{ $contentListOperationsId }}">
					<div class="col-md-10 contentListOperationsHeader">
						<div class="btn-group">
							@if($isAllNotes == 0 || $isVirtualFolder == true)
			                  	<button class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="confirmAndInvertFavoriteStatus();">
			                  		@if($selGroupFolderIsFavorited == 1)
			                  			<img src="{{ $isMarkedHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                						<span class="content-list-btn-title">Unmark</span>
			                  		@else
			                  			<img src="{{ $isUnMarkedHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                						<span class="content-list-btn-title">Mark</span>
			                  		@endif
			                  	</button>
			                @endif

			                @if($isAllNotes == 0)
	                			
	                			<button type="button" class="btn btn-default btn-sm btn-content-list-header" onclick="selectAllLoadedContents({{ $selIsFolder }});">
	                				<img src="{{ $selectAllHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                					<span class="content-list-btn-title">Select All</span>
	                			</button>
	                			
	                			<button type="button" class="btn btn-default btn-sm btn-content-list-header" onclick="resetContentSelection({{ $selIsFolder }});">
	                				<img src="{{ $unselectAllHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                					<span class="content-list-btn-title">Unselect All</span>
	                			</button>

			                @endif

		                	<button id="{{ $contentListRefreshButtonId }}" type="button" class="btn btn-default btn-sm btn-content-list-header" onclick="refreshContentList();">
		                		<img src="{{ $refreshHeaderIconPath }}" class="content-list-icon"/>
                				<span class="content-list-btn-title">Refresh</span>
		                	</button>

							@if(($selIsFolder == 1 && $folderTypeId == 0 && !$isTrashNotes) || ($selIsFolder == 0 && ($grpHasPostRight == 1 || $grpIsAdmin == 1)))
		                  		<button type="button" class="btn btn-default btn-sm btn-content-list-header" id="{{ $contentListAddButtonId }}" onclick="showContentDetails(0, '{{ $selIsFolder }}', 0, 0, '{{ $selFolderOrGroupEncId }}', '{{ $orgKey }}');">
		                  			<img src="{{ $addHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                					<span class="content-list-btn-title">Add</span>
		                  		</button>

		                  		<button type="button" class="btn btn-default btn-sm btn-content-list-header" id="{{ $contentListAddButtonId }}" onclick="loadAppuserContentDetailsInPopUp('{{ $orgKey }}', '{{ $selIsFolder }}', 0, 0, '{{ $listCode }}', '{{ $searchStr }}');">
		                  			<img src="{{ $addInPopUpHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title="" style="filter: invert(1);" />
                					<span class="content-list-btn-title">Add in Popup</span>
                				</button>
		                  	@endif

		                  	<button id="{{ $contentListSortButtonId }}" class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="showSortOptions('{{ $selIsFolder }}');">
		                  		<img src="{{ $sortHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
                					<span class="content-list-btn-title">Sort</span>
		                  	</button>

		                 	<button id="{{ $contentListFilterButtonId }}" class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="showFilterOptions('{{ $selIsFolder }}', '{{ $isVirtualFolderFlag }}', '{{ $consFolderOrGroupIdForFilter }}');">
		                 		<img src="{{ $filterHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
            					<span class="content-list-btn-title">Filter</span>
		                 	</button>

		                 	@if($isVirtualFolderFlag == 0 && $contentListHasFilters == 1)
		                 		<button id="{{ $contentListRemoveFilterButtonId }}" class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="resetContentListFilter('{{ $selIsFolder }}');">
		                 			<img src="{{ $removeFilterHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
            						<span class="content-list-btn-title">Reset Filter</span>
		                 		</button>
		                 	@endif

							@if($isAllNotes == 0 || $isAllNotes == 1)
		         		  		<button id="attachmentView" class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="showAttachmentView('{{ $selIsFolder }}');">
		         		  			<img src="{{ $attachmentHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
            						<span class="content-list-btn-title">Attachment View</span>
		         		  		</button>
		         		  	@endif

		         		  	@if($showSelection)

								@if($profileShareRight == 1)
			                 		<button id="multiShare" class="btn btn-default btn-sm btn-content-list-header" type="button" onclick="performShareContentToUserFromHeader('{{ $selIsFolder }}', '{{ $orgKey }}');">
			                 			<img src="{{ $shareHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
        								<span class="content-list-btn-title">Share</span>
			                 		</button>
			                 	@endif

			                 	<button type="button" class="btn btn-sm btn-content-list-header btn-default dropdown-toggle" data-toggle="dropdown">
									<img src="{{ $multiSelectHeaderIconPath }}" class="content-list-icon" data-toggle="tooltip" title=""/>
        								<span class="content-list-btn-title">Operations</span>
								</button>
								<ul class="dropdown-menu" style="top: 110%;right: 0;left: 50%;">
									@if($selIsFolder == 1 && !$isTrashNotes) <!-- && ($folderTypeId == 0 || $folderTypeId == 1) -->
										@if($appHasFolderSelection == 1)
											<li id="multiMoveToFolder" onclick="performMoveToFolder('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Move To Folder</a></li>
											<li id="multiCopyToFolder" onclick="performCopyToFolder('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Copy To Folder</a></li>
											@if($profileCnt > 0 && $profileShareRight == 1)
												<li id="multiCopyToProfile" onclick="performCopyToProfile('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Copy To Profile</a></li>
											@endif
										@endif
									@elseif($selIsFolder == 0)
										<li id="multiGroupInfo" onclick="loadGroupInfo();"><a href="javascript:void(0);">Group Info</a></li>
									@endif
									@if(($selIsFolder == 1 && !$isTrashNotes) || ($selIsFolder == 0 && $grpIsAdmin == 1)) <!-- && ($folderTypeId == 0 || $folderTypeId == 1) -->
										<li id="multiDelete{{ $selIsFolder }}" onclick="performDeleteContent('{{ $consIsFolderFlagForMultiOps }}', 0);"><a href="javascript:void(0);">Delete Content(s)</a></li>
									@endif
									@if($isTrashNotes)
										<li id="multiDelete{{ $selIsFolder }}" onclick="performDeleteContent('{{ $consIsFolderFlagForMultiOps }}', 1);"><a href="javascript:void(0);">Delete Content(s)</a></li>
										<li id="multiRestore{{ $selIsFolder }}" onclick="performRestoreContent('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Restore Content(s)</a></li>
									@endif
									@if(!$isTrashNotes && ($selIsFolder == 1 && $folderTypeId == 0) || ($selIsFolder == 0 && $grpIsTwoWay == 1 && ($grpHasPostRight == 1 || $grpIsAdmin == 1)))
										<li id="multiMerge{{ $selIsFolder }}" onclick="performMergeContent('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Merge Content(s)</a></li>
									@endif
									@if(!$isTrashNotes && ($selIsFolder == 1) || ($selIsFolder == 0 && $grpIsTwoWay == 1 && ($grpHasPostRight == 1 || $grpIsAdmin == 1))) <!-- && ($folderTypeId == 0) -->
										<li id="multiAddTag{{ $selIsFolder }}" onclick="performAddTag('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Add Tag(s)</a></li>
									@endif
									<li id="multiPrint" onclick="performPrintContent('{{ $consIsFolderFlagForMultiOps }}', 0, '{{ $orgKey }}');"><a href="javascript:void(0);">Print</a></li>
									@if(($selIsFolder == 1 && !$isTrashNotes) || ($selIsFolder == 0 && $grpIsAdmin == 1)) <!-- && ($folderTypeId == 0 || $folderTypeId == 1) -->
										<li id="multiMarkComplete{{ $selIsFolder }}" onclick="performMarkContentAsComplete('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Mark as Complete</a></li>
										<li id="multiMarkIncomplete{{ $selIsFolder }}" onclick="performMarkContentAsIncomplete('{{ $consIsFolderFlagForMultiOps }}');"><a href="javascript:void(0);">Mark as Incomplete</a></li>
									@endif
									@if($isConversationList && $orgKey == '')
										<li id="multiJoinGroup" onclick="loadJoinGroupModal('{{ $orgKey }}');"><a href="javascript:void(0);">Join Group</a></li>							
									@endif
								</ul>
								
							@endif
		                </div>
		            </div>
					<div class="col-md-2 pull-right" style="vertical-align: middle;">
						<form name="frmSearchContent" onsubmit="refreshContentList()">
							<div class="input-group input-group-sm">
								<input type="text" id="txtSrchActContList" class="form-control input-sm" placeholder="Search Content" autocomplete="off" value="{{ $searchStr }}" >
								<span class="input-group-btn">
									<button class="btn btn-success btn-sm" type="submit">Go!</button>
									<button class="btn btn-danger btn-sm" type="button" onclick="resetSearchField();">Reset</button>
								</span>
							</div>
						</form>
					</div>
				</div>
			</div>
		@endif

	</section>

@endif
@if($isAttachmentView == 0)
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
				$contentTitle = $contentObj['contentTitle'];
				$contentText = $contentObj['strippedContentAsIs']; // strippedContentText
				$contentCrtDate = $contentObj['createUtc'];
				$contentModDate = $contentObj['updateUtc'];
				$contentTypeId = $contentObj['contentType'];
				$contentIsMarked = $contentObj['isMarked'];
				$contentIsCompleted = $contentObj['isCompleted'];
				$contentHasRepeatEnabled = $contentObj['isRepeatEnabled'];
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
				$contentIsConversation = $contentObj['isConversation'];

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
				$contentIsCompleted = $contentObj['isCompleted'];
				$contentHasRepeatEnabled = $contentObj['isRepeatEnabled'];
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
				$contentIsConversation = $contentObj['isConversation'];

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
@elseif($attachmentView != "")
	{!! $attachmentView !!}
@endif

<script>	
	var isFolder = '{{ $selIsFolder }}';	
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
			setContentSelectionStatus(isFolder, contentId, true);
		})
  		.on('ifUnchecked', function(event){
  			var contentId = $(this).val();
			setContentSelectionStatus(isFolder, contentId, false);
		});
					
		@if($isAllNotes == 1)
			var isGroup = 0;
			$('.cbContent'+isGroup).iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		})
	  		.on('ifChecked', function(event){
	  			var contentId = $(this).val();
				setContentSelectionStatus(isGroup, contentId, true);
			})
	  		.on('ifUnchecked', function(event){
	  			var contentId = $(this).val();
				setContentSelectionStatus(isGroup, contentId, false);
			});

	  		@if(!$hasFolderGroupList)
				$('#divMainFolderGroupList').hide();
				$('.srac-header-section-content-list').addClass('srac-header-section-content-list-without-folder');
			@endif
		@endif
	});
</script>