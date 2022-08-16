@php
	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$isMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsMarkedPath'));
	$isUnMarkedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsUnMarkedPath'));

	$isLockedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconFolderIsLockedPath'));

	$firstFolderOrGroupId = "";
	$firstIsFolderOrGroup = 0;

	if(isset($fOrGSearchStr) && $fOrGSearchStr != "")
	{
		if(isset($selFOrGId)  && isset($selIsFolder) && isset($selOrgId))
		{
			$firstFolderOrGroupId = $selFOrGId;
			$firstIsFolderOrGroup = $selIsFolder;
		}
	}
	else if($hasPreloadSelection == 1)
	{
		if(isset($preloadFOrGId)  && isset($preloadIsFolder))
		{
			$firstFolderOrGroupId = $preloadFOrGId;
			$firstIsFolderOrGroup = $preloadIsFolder;
		}
	}
@endphp
<div class="row" style="padding-bottom: 10px;">
	<div class="col-md-4 pull-right">
		<form name="frmSearchFolderOrGroup" onsubmit="refreshFolderOrGroupList()">
			<div class="input-group input-group-sm">
				<input type="text" id="txtSrchActFolderGroupList" class="form-control input-sm" placeholder="Search Folder/Group" autocomplete="off" value="{{ $fOrGSearchStr }}" >
				<span class="input-group-btn">
					<button class="btn btn-success btn-sm" type="submit">Go!</button>
					<button class="btn btn-danger btn-sm" type="button" onclick="resetFolderOrGroupSearchField();">Reset</button>
				</span>
			</div>
		</form>
	</div>
</div>
<div class="row">
	<div class="col-md-12 folder-group-list-scrollbar">
		<ul class="list-inline">

			@if(isset($folderArr) && count($folderArr) > 0)
				
				@php
				$isFolderFlag = 1;
				@endphp

				@foreach($folderArr as $folderObj)
					
					@php
					$folderId = $folderObj['id'];
					$folderName = $folderObj['name'];
					$folderIsLocked= $folderObj['isLocked'];
					$folderIsFavorited = $folderObj['isFavorited'];
					$folderOrgKey= $folderObj['orgKey'];
					$folderOrgIconUrl = $folderObj['orgIconUrl'];
					$folderIconCode = $folderObj['iconCode'];
					$folderIsVirtual = $folderObj['isVirtual'];
					$folderNameVirtualClass = $folderIsVirtual == 1 ? 'virtualFolder' : '';

					if($firstFolderOrGroupId == "")
					{
						$firstFolderOrGroupId = $folderId;
						$firstIsFolderOrGroup = $isFolderFlag;
					}

					@endphp
					<li class="list-inline-item folder-group-list-item">
						<div class="col-md-12" id="divFolderGroup_{{ $isFolderFlag }}_{{ $folderId }}" onclick="showSelectedContentList( '{{ $folderOrgKey }}', '{{ $folderId }}', '{{ $isFolderFlag }}', '{{ $folderIsLocked }}', '{{ $folderIsVirtual }}', '{{ $folderName }}');" align="center">
							@if($folderId > 0)
								@if($folderIsFavorited == 1)
									<img src="{{ $isMarkedIconPath }}" class="folder-group-list-mark-icon" />
								@else
									<!-- <img src="{{ $isUnMarkedIconPath }}" class="folder-group-list-mark-icon" /> -->
								@endif

								@if($folderIsLocked == 1)
									<span class="span-folder-group-list-lock-icon">
										<img src="{{ $isLockedIconPath }}" />
									</span>
								@endif
							@endif
							<div class="row folder-group-list-img">
								@if(isset($folderIconCode) && $folderIconCode != "")
									<img src="{{ $folderIconCode }}" class="folder-group-icon folder-group-icon-system"/>
								@else
									<img src="{{ $defIconCode }}" class="folder-group-icon folder-group-icon-system"/>
								@endif
							</div>
							<div class="row folder-group-name-div">
								<span class="folder-group-name {{ $folderNameVirtualClass }}">{{ $folderName }}</span>
							</div>

							@if($folderOrgIconUrl != "")
								<span class="span-folder-group-list-org-icon">
									<img src="{{ $folderOrgIconUrl }}" />
								</span>
							@endif
							<!-- <div class="col-md-3" align="right" style="padding: 13px;">
								<span>{{ Config::get('app_config_const.placeholder_folder_is_locked_icon') }}</span>
								<span onclick="loadFolderOrGroupContentInfo({{ $isFolderFlag }}, {{ $folderId }});">
									<i class="fa fa-info"></i>
								</span>
							</div> -->
						</div>
					</li>

				@endforeach

			@endif

			@if(isset($groupArr) && count($groupArr) > 0)
				
				@php
				$isFolderFlag = 0;
				@endphp

				@foreach($groupArr as $groupObj)
					
					@php
					$groupId = $groupObj['id'];
					$groupName = $groupObj['name'];
					$groupIsFavorited = $groupObj['isFavorited'];
					$groupPhotoUrl = $groupObj['photoThumbUrl'];
					$groupOrgKey= $groupObj['orgKey'];
					$groupOrgIconUrl = $groupObj['orgIconUrl'];

					if($firstFolderOrGroupId == "")
					{
						$firstFolderOrGroupId = $groupId;
						$firstIsFolderOrGroup = $isFolderFlag;
					}

					@endphp
					<li class="list-inline-item folder-group-list-item">
						<div class="col-md-12" id="divFolderGroup_{{ $isFolderFlag }}_{{ $groupId }}" onclick="showSelectedContentList( '{{ $groupOrgKey }}', '{{ $groupId }}', '{{ $isFolderFlag }}', 0, 0, '');" align="center">
							@if($groupIsFavorited == 1)
								<img src="{{ $isMarkedIconPath }}" class="folder-group-list-mark-icon" />
							@else
								<!-- <img src="{{ $isUnMarkedIconPath }}" class="folder-group-list-mark-icon" /> -->
							@endif
							<div class="row folder-group-list-img">
								<img src="{{ $groupPhotoUrl }}" class="folder-group-icon"/>
							</div>
							<div class="row folder-group-name-div">
								<span class="folder-group-name">{{ $groupName }}</span>
							</div>

							@if($groupOrgIconUrl != "")
								<span class="span-folder-group-list-org-icon">
									<img src="{{ $groupOrgIconUrl }}" />
								</span>
							@endif

							<!-- <div class="col-md-3" align="right" style="padding: 13px;">
								<span>{{ Config::get('app_config_const.placeholder_folder_is_locked_icon') }}</span>
								<span onclick="loadFolderOrGroupContentInfo({{ $isFolderFlag }}, {{ $groupId }});">
									<i class="fa fa-info"></i>
								</span>
							</div> -->
						</div>
					</li>

				@endforeach

			@endif

			@if(isset($sentObj))
				
				@php
				$isFolderFlag = 1;

				$folderId = $sentObj['id'];
				$folderName = $sentObj['name'];
				$folderIsLocked= $sentObj['isLocked'];
				$folderIsFavorited = $sentObj['isFavorited'];
				$folderOrgKey= $sentObj['orgKey'];
				$folderOrgIconUrl = $sentObj['orgIconUrl'];
				$folderIconCode = $sentObj['iconCode'];
				@endphp
				<li class="list-inline-item folder-group-list-item">
					<div class="col-md-12" id="divFolderGroup_{{ $isFolderFlag }}_{{ $folderId }}" onclick="showSelectedContentList( '{{ $folderOrgKey }}', '{{ $folderId }}', '{{ $isFolderFlag }}', '{{ $folderIsLocked }}' );" align="center">
						@if($folderId > 0)
							@if($folderIsFavorited == 1)
								<img src="{{ $isMarkedIconPath }}" class="folder-group-list-mark-icon" />
							@else
								<!-- <img src="{{ $isUnMarkedIconPath }}" class="folder-group-list-mark-icon" /> -->
							@endif

							@if($folderIsLocked == 1)
								<span class="span-folder-group-list-lock-icon">
									<img src="{{ $isLockedIconPath }}" />
								</span>
							@endif
						@endif
						<div class="row folder-group-list-img">
							@if(isset($folderIconCode) && $folderIconCode != "")
								<img src="{{ $folderIconCode }}" class="folder-group-icon folder-group-icon-system"/>
							@else
								<img src="{{ $defIconCode }}" class="folder-group-icon folder-group-icon-system"/>
							@endif
						</div>
						<div class="row folder-group-name-div">
							<span class="folder-group-name">{{ $folderName }}</span>
						</div>

						@if($folderOrgIconUrl != "")
							<span class="span-folder-group-list-org-icon">
								<img src="{{ $folderOrgIconUrl }}" />
							</span>
						@endif
						<!-- <div class="col-md-3" align="right" style="padding: 13px;">
							<span>{{ Config::get('app_config_const.placeholder_folder_is_locked_icon') }}</span>
							<span onclick="loadFolderOrGroupContentInfo({{ $isFolderFlag }}, {{ $folderId }});">
								<i class="fa fa-info"></i>
							</span>
						</div> -->
					</div>
				</li>

			@endif

			@if(isset($trashObj))
				
				@php
				$isFolderFlag = 1;

				$folderId = $trashObj['id'];
				$folderName = $trashObj['name'];
				$folderIsLocked= $trashObj['isLocked'];
				$folderIsFavorited = $trashObj['isFavorited'];
				$folderOrgKey= $trashObj['orgKey'];
				$folderOrgIconUrl = $trashObj['orgIconUrl'];
				$folderIconCode = $trashObj['iconCode'];
				@endphp
				<li class="list-inline-item folder-group-list-item">
					<div class="col-md-12" id="divFolderGroup_{{ $isFolderFlag }}_{{ $folderId }}" onclick="showSelectedContentList( '{{ $folderOrgKey }}', '{{ $folderId }}', '{{ $isFolderFlag }}', '{{ $folderIsLocked }}' );" align="center">
						@if($folderId > 0)
							@if($folderIsFavorited == 1)
								<img src="{{ $isMarkedIconPath }}" class="folder-group-list-mark-icon" />
							@else
								<!-- <img src="{{ $isUnMarkedIconPath }}" class="folder-group-list-mark-icon" /> -->
							@endif

							@if($folderIsLocked == 1)
								<span class="span-folder-group-list-lock-icon">
									<img src="{{ $isLockedIconPath }}" />
								</span>
							@endif
						@endif
						<div class="row folder-group-list-img">
							@if(isset($folderIconCode) && $folderIconCode != "")
								<img src="{{ $folderIconCode }}" class="folder-group-icon folder-group-icon-system"/>
							@else
								<img src="{{ $defIconCode }}" class="folder-group-icon folder-group-icon-system"/>
							@endif
						</div>
						<div class="row folder-group-name-div">
							<span class="folder-group-name">{{ $folderName }}</span>
						</div>

						@if($folderOrgIconUrl != "")
							<span class="span-folder-group-list-org-icon">
								<img src="{{ $folderOrgIconUrl }}" />
							</span>
						@endif
						<!-- <div class="col-md-3" align="right" style="padding: 13px;">
							<span>{{ Config::get('app_config_const.placeholder_folder_is_locked_icon') }}</span>
							<span onclick="loadFolderOrGroupContentInfo({{ $isFolderFlag }}, {{ $folderId }});">
								<i class="fa fa-info"></i>
							</span>
						</div> -->
					</div>
				</li>

			@endif

		</ul>
	</div>
</div>
<script>		
	$(document).ready(function()
	{
		$("#divFolderGroup_{{ $firstIsFolderOrGroup }}_{{ $firstFolderOrGroupId }}").trigger('click');
	});
</script>