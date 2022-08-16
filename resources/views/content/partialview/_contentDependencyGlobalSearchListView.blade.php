@php

	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$isMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsMarkedPath'));
	$isUnMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsUnMarkedPath'));

@endphp

<style>
	.divOptionList
	{
		max-height: 400px;
    	overflow: auto;
	}

	.dep-list-row {
		/*padding: 10px;*/
		background: white;
	    border-radius: 10px;	
	    margin: 5px;	
	}

	.dep-folder-group-icon {
		height: 50px;
	}

	.dep-folder-group-name {
		vertical-align: middle; 
		padding-top: 16px;
		font-size: 16px;
	}

	.dep-folder-group-mark-icon {
		vertical-align: middle; 
		padding-top: 16px;
	}
</style>

<div class="row">
	<div class="col-md-12 divOptionList" id="divOptionList">
		@if(isset($depFolderOrGroupArr) && count($depFolderOrGroupArr) > 0)
			@foreach($depFolderOrGroupArr as $depData)
				@php
				$id = $depData['id'];
				$isFolder = $depData['isFolder'];
				$folderTypeId = $depData['folderTypeId'];
				$text = $depData['text'];
				$iconUrl = $depData['iconUrl'];
				$isFavorited = $depData['isFavorited'];
				@endphp
				<div class="row depSelectRow dep-list-row" onclick="loadRelevantFolderOrGroupContentListFromGS('{{ $isFolder }}', '{{ $id }}', '{{ $folderTypeId }}');">
					<div class="col-md-1">	
						<img src="{{ $iconUrl }}" class="dep-folder-group-icon" />
					</div>
					<div class="col-md-10 dep-folder-group-name">	
						{{ $text }}				
					</div>
					<div class="col-md-1" align="right">
				    	@if($isFavorited == 1)
							<img src="{{ $isMarkedIconPath }}" class="dep-folder-group-mark-icon" />
						@else
							<img src="{{ $isUnMarkedIconPath }}" class="dep-folder-group-mark-icon" />
						@endif
					</div>	
				</div>	
			@endforeach	
		@endif

		@if(isset($depMediaAttachmentArr) && count($depMediaAttachmentArr) > 0)
			<div class="row" style="padding: 10px;">
				@foreach($depMediaAttachmentArr as $depData)
					@php
					$attachmentId = $depData['content_attachment_id'];
					$attachmentFilename = $depData['stripped_filename'];
					$attachmentUrl = $depData['url'];
					$attachmentThumbUrl = $depData['thumbUrl'];
					$attachmentContentId = $depData['contentId'];
					$attachmentContentIsFolder = $depData['isFolder'];
					@endphp
					<div class="col-md-3" style="padding-top: 5px;" onclick="showContentDetails('{{ $attachmentContentId }}', '{{ $attachmentContentIsFolder }}', 1, 0, 0);">
						<div class="att-card box-shadow">
							<div class="text-center">
								<img class="card-img-top" src="{{ $attachmentThumbUrl }}" style="height: 50px;">
							</div>
							<div class="card-body" align="center">
								<p class="att-card-text">{{ $attachmentFilename }}</p>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		@endif

	</div>
</div>

<script>
	var conversationNotesListCode = "{{ Config::get('app_config.dashMetricConversationNotesCode') }}";
	var allNotesListCode = "{{ Config::get('app_config.dashMetricAllNotesCode') }}";
	function loadRelevantFolderOrGroupContentListFromGS(isFolder, folderOrGroupId, folderTypeId)
	{
		let consListCode = '';

		if(isFolder*1 == 1)
		{
			if(folderTypeId*1 == 0)
			{
				consListCode = allNotesListCode;
			}
			else if(folderTypeId*1 == 2)
			{
				consListCode = allNotesListCode;
			}
			else if(folderTypeId*1 == 3)
			{
				consListCode = conversationNotesListCode;
			}
		}
		else
		{
			consListCode = conversationNotesListCode;
		}

		var form = document.createElement("form");
	    var element1 = document.createElement("input"); 
	    element1.setAttribute("type", "hidden");
	    var element2 = document.createElement("input");  
	    element2.setAttribute("type", "hidden");

	    form.method = "post";
	    form.action =  "/content/" + consListCode;  

	    element1.value=folderOrGroupId;
	    element1.name="selFolderOrGroupId";
	    form.appendChild(element1);  

	    element2.value=isFolder;
	    element2.name="selIsFolder";
	    form.appendChild(element2);

	    document.body.appendChild(form);

	    form.submit();
	}
</script>