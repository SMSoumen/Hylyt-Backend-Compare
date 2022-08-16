@php
	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$cloudFolderCreateIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFolderCreate');
	$cloudFileCreateIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFileCreate');
	$cloudFolderDeleteIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFolderDelete');
	$cloudFileDeleteIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFileDelete');
	$cloudFolderFileDeleteIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFolderFileDelete');
	$cloudFolderFileViewIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFileView');
	$cloudFileSelectIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageFileSelect');
	$noSearchStrIconUrl = $baseIconThemeUrl.(Config::get('app_config_asset.appIconNoSearchStringProvided'));
@endphp

@php
	$noAttachmentsFound = true;

	$folderCount = 0;
	$folderList = array();
	$hasAddFolder = 0;
	$hasAddFile = 0;
	$hasSelectFile = 0;
	$hasSearchFile = 0;

	$breadcrumbArr = array();

	if(isset($folderResponse) && isset($folderResponse['folderCount']))
	{
		$folderCount = $folderResponse['folderCount'];
		$folderList = $folderResponse['folderList'];
		$hasAddFolder = $folderResponse['hasAddFolder'];
		$hasAddFile = $folderResponse['hasAddFile'];
		$hasSelectFile = $folderResponse['hasSelectFile'];
		$hasSearchFile = $folderResponse['hasSearchFile'];

		$breadcrumbArr = isset($folderResponse['breadcrumbArr']) ? $folderResponse['breadcrumbArr'] : null;

	}
	
	$currentFolderName = 'Home';
	$rightMostPath = '';
	$rightMostTitle = '';
	$rightMostBaseFolderType = 0;
	if(isset($breadcrumbArr) && count($breadcrumbArr) > 0)
	{
		$lastIndex = count($breadcrumbArr) - 1;
		$currentFolderBreadcrumb = $breadcrumbArr[$lastIndex];
		$currentFolderName = $currentFolderBreadcrumb['title'];

		if(count($breadcrumbArr) > 1)
		{
			$secondLastIndex = count($breadcrumbArr) - 2;
			$rightMostBreadcrumb = $breadcrumbArr[$secondLastIndex];
			$rightMostPath = $rightMostBreadcrumb['path'];
			$rightMostTitle = $rightMostBreadcrumb['title'];
			$rightMostBaseFolderType = isset($rightMostBreadcrumb['baseFolderType']) ? $rightMostBreadcrumb['baseFolderType'] : 0;
		}
	}

	if(!isset($hideOperations) || $hideOperations != 1)
	{
		$hideOperations = 0;
	}

	if(!isset($showNoSearchStrMsg) || $showNoSearchStrMsg != 1)
	{
		$showNoSearchStrMsg = 0;
	}


@endphp

<style type="text/css">
	.noSearchStrFoundDiv {
		height: 150px;
		vertical-align: middle;
		text-align: center;
		font-size: 30px;
	}
</style>

@if($showNoSearchStrMsg == 0)
	@if($isPrimaryListLoad)
		<div class="row" style="margin-left: 0px; margin-right: 0px">
			<div class="col-md-8">
				<i class="fa fa-arrow-left" style="cursor: pointer;" onclick="loadAttachmentListSubView('{{ $rightMostPath }}', '', '{{ $rightMostBaseFolderType }}')"></i>
				<span class="ca-current-folder-name">{{ $currentFolderName }}</span>
			</div>
			<div class="col-md-4" align="right">
				<div class="row">
					@if(count($folderList) > 0 && $hasSelectFile == 1)
						<button type="button" class="btn btn-default btn-sm" onclick="submitContentAttachmentSelection();" data-toggle="tooltip" title="Select File(s)">
				    		<img src="{{ $cloudFileSelectIconPath }}" class="content-modal-button-icon"/>
				    	</button>
					@endif
					@if($hasAddFolder == 1)
						<button type="button" class="btn btn-default btn-sm" onclick="loadAddCloudFolderView();" data-toggle="tooltip" title="Create Folder">
				    		<img src="{{ $cloudFolderCreateIconPath }}" class="content-modal-button-icon"/>
				    	</button>
					@endif
					@if($hasAddFile == 1)
						<button type="button" class="btn btn-default btn-sm" onclick="loadAddCloudFileView();" data-toggle="tooltip" title="Upload File">
				    		<img src="{{ $cloudFileCreateIconPath }}" class="content-modal-button-icon"/>
				    	</button>
					@endif
			    </div>
			</div>
		</div>
		<div class="row" style="margin-left: 0px; margin-right: 0px; display: none; margin-bottom: 10px;" id="divAddCloudFolderView">
			<div class="col-md-9">
				<div class="input-group input-group-sm">
					<input type="text" id="txtNewCloudFolderName" class="form-control input-sm" placeholder="Folder Name" autocomplete="off" value="" >
					<span class="input-group-btn">
						<button class="btn btn-success btn-sm" type="button" onclick="performAddNewCloudFolder();"><i class="fa fa-save"></i></button>
						<button class="btn btn-danger btn-sm" type="button" onclick="cancelAddNewCloudFolder(false);"><i class="fa fa-times"></i></button>
					</span>
				</div>
			</div>
		</div>
		<div class="row" style="margin-left: 0px; margin-right: 0px; display: none; margin-bottom: 10px;" id="divAddCloudFileView">
			<div class="col-md-9">
				<div class="input-group input-group-sm">
					<input type="file" id="inpNewCloudFile" class="form-control input-sm" placeholder="Select File" autocomplete="off" value="" >
					<span class="input-group-btn">
						<button class="btn btn-success btn-sm" type="button" onclick="performAddNewCloudFile();"><i class="fa fa-save"></i></button>
						<button class="btn btn-danger btn-sm" type="button" onclick="cancelAddNewCloudFile(false);"><i class="fa fa-times"></i></button>
					</span>
				</div>
			</div>
		</div>
		<div class="row" style="margin-left: 0px; margin-right: 0px">
			@if($hasSearchFile == 1)
				<div class="col-md-6">
					<div class="input-group input-group-sm">
						<input type="text" id="txtSrchCldAtt" class="form-control input-sm" placeholder="Search Attachment(s)" autocomplete="off" value="{{ $queryStr }}" >
						<span class="input-group-btn">
							<button class="btn btn-success btn-sm" type="button" onclick="refreshCloudAttachmentListSubView();">Go!</button>
							<button class="btn btn-danger btn-sm" type="button" onclick="resetCloudAttachmentListSearchField();">Reset</button>
						</span>
					</div>
				</div>
			@endif
		</div>
	@endif

	@if(isset($folderCount) && ($folderCount) > 0)
		@php
			$noAttachmentsFound = false;
		@endphp
		<div class="row" style="margin-left: 0px; margin-right: 0px">
			<div class="col-md-12">
				@if(count($folderList) > 0)
					@foreach($folderList as $fileFolderIndex => $fileFolderObj)
						@php
							$isFile = $fileFolderObj['isFile'];
						@endphp
						@if($isFile == 1)
							@php
								$fileId = $fileFolderObj['fileId'];
								$fileName = $fileFolderObj['fileName'];
								$filePath = $fileFolderObj['filePath'];
								$fileDisplayPath = $fileFolderObj['fileDisplayPath'];
								$fileSize = $fileFolderObj['fileSize'];
								$fileSizeStr = $fileFolderObj['fileSizeStr'];
								$thumbUrl = $fileFolderObj['thumbUrl'];
								$filePreviewIsBase64 = $fileFolderObj['filePreviewIsBase64'];
								$hasFilePreviewStr = $fileFolderObj['hasFilePreviewStr'];
								$filePreviewStr = $fileFolderObj['filePreviewStr'];

								$consFilePreviewStr = $filePreviewIsBase64 == 1 ? 'data:image/png;base64, '.$filePreviewStr : $filePreviewStr;
							@endphp
							<div class="row ca-file-folder-row" title="{{ $fileName }}">
								<div class="col-md-1 ca-file-folder-chk" onclick="selAttachmentListItem('{{ $fileId }}')">
									<input type="checkbox" class="chkCAFile" id="chkCAFile{{ $fileId }}" value="{{ $fileId.'_'.$fileSize }}" />
								</div>
								<div class="col-md-2 ca-file-folder-img" onclick="selAttachmentListItem('{{ $fileId }}')">
									<img class="cloud-attachment-ind-icon" src="{{ $hasFilePreviewStr == 1 ? $consFilePreviewStr : $thumbUrl }}">
								</div>
								<div class="col-md-6 ca-file-details" onclick="selAttachmentListItem('{{ $fileId }}')">
									<span class="ca-file-name">{{ $fileName }}</span>
									<br/>
									<span class="ca-file-size">{{ $fileSizeStr }}</span>
								</div>
								<div class="col-md-3 ca-file-actions">
									@if($hideOperations == 0)
										<button type="button" class="btn btn-default btn-sm" onclick="performViewCloudFile('{{ $fileId }}', '{{ $fileSizeStr }}');" data-toggle="tooltip" title="View">
								    		<img src="{{ $cloudFolderFileViewIconPath }}" class="content-modal-button-icon"/>
								    	</button>
										<button type="button" class="btn btn-default btn-sm" onclick="performDeleteCloudFile('{{ $fileId }}');" data-toggle="tooltip" title="Delete File">
								    		<img src="{{ $cloudFolderFileDeleteIconPath }}" class="content-modal-button-icon"/>
								    	</button>
								    @endif
								</div>
							</div>
						@else
							@php
								$folderId = $fileFolderObj['folderId'];
								$folderName = $fileFolderObj['folderName'];
								$folderPath = $fileFolderObj['folderPath'];
								$folderDisplayPath = $fileFolderObj['folderDisplayPath'];
								$thumbUrl = $fileFolderObj['thumbUrl'];
								$hideDeleteBtn = isset($fileFolderObj['hideDeleteBtn']) && $fileFolderObj['hideDeleteBtn'] == 1 ? true : false;
								$folderBasePathType = isset($fileFolderObj['baseFolderType']) ? $fileFolderObj['baseFolderType'] : 0;
							@endphp
							<div class="row ca-file-folder-row" title="{{ $folderName }}">
								<!-- <div class="col-md-1 ca-file-folder-chk">

								</div> -->
								<div class="col-md-2 ca-file-folder-img" onclick="loadAttachmentListSubView('{{ $folderPath }}', '', '{{ $folderBasePathType }}')">
									<img class="cloud-attachment-ind-icon" src="{{ $thumbUrl }}">
								</div>
								<div class="col-md-8 ca-folder-details" onclick="loadAttachmentListSubView('{{ $folderPath }}', '', '{{ $folderBasePathType }}')">
									<span class="ca-folder-name">{{ $folderName }}</span>
								</div>
								<div class="col-md-2 ca-folder-actions">
									@if(!$hideDeleteBtn && $hideOperations == 0)
										<button type="button" class="btn btn-default btn-sm" onclick="performDeleteCloudFolder('{{ $folderId }}');" data-toggle="tooltip" title="Delete Folder">
								    		<img src="{{ $cloudFolderFileDeleteIconPath }}" class="content-modal-button-icon"/>
								    	</button>
								    @endif
								</div>
							</div>
						@endif
					@endforeach
				@elseif($isPrimaryListLoad)
					<span class="attachmentNotAvailable">No attachment(s) found</span>
				@endif
			</div>
		</div>
	@endif
@else
	<div class="noSearchStrFoundDiv">
		<img src="{{ $noSearchStrIconUrl }}" />
		<br/>
		It seems that you have not searched for a keyword
	</div>
@endif