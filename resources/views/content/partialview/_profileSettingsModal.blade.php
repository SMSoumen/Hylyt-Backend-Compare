<style>
	.infoRow
	{
		margin-top: 12px !important;
		margin-left: 12px !important;
	}
	.infoBody
	{
		font-size: 16px !important;
		margin-bottom: 20px !important;
	}
	.settings-cloud-storage-icon
	{
		height: 30px;
	}
	.settings-cloud-storage-name
	{
		height: 30px;
		vertical-align: middle;
	}
	.settings-cloud-storage-actions
	{
		height: 30px;
		vertical-align: middle;
	}
</style>
<div id="profileSettingsModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Settings
				</h4>
			</div>
			<div class="modal-body infoBody">
				<div class="row infoRow">
					<div class="col-md-12">
						{!! $quotaStr !!}
					</div>
				</div>
				<!--<div class="row infoRow">
					<div class="col-md-12">
						Last Synced At: 
						<br/>
						{!! $lastSyncStr !!}
					</div>
				</div>-->
				<div class="row infoRow">
					<div class="col-md-12">
						Notes Count:
						<br/>
						<b>{{ $noteCount }} Note(s)</b>
					</div>
				</div>	
				<div class="row infoRow">
					<div class="col-md-12">
						Default Folder:
						&nbsp;&nbsp;
						<button type="button" class="btn btn-info btn-xs" onclick="performChangeDefaultFolder({{ $folderId }}, '{{ $folderName }}');">
							Change
						</button>
						<br/>
						<b>{{ $folderName }}</b>
						
					</div>
				</div>
				<!-- 
				@if($appHasIntegrationOptions == 1)
					<div class="row infoRow">
						<div class="col-md-12">
							<div class="row">
								<b>Integrations:</b>
								<br/>
							</div>
							@foreach($cloudStorageTypeList as $cloudStorageType)
								@php
								$cloudStorageTypeId = $cloudStorageType['id'];
								$cloudStorageTypeCode = $cloudStorageType['code'];
								$cloudStorageTypeName = $cloudStorageType['name'];
								$cloudStorageTypeIconUrl = $cloudStorageType['iconUrl'];
								$cloudStorageTypeIsLinked = $cloudStorageType['isLinked'];
								@endphp

								<div class="row">
									<div class="col-md-2">
										<img src="{{ $cloudStorageTypeIconUrl }}" class="settings-cloud-storage-icon" />
									</div> 
									<div class="col-md-6 settings-cloud-storage-name">
										{{ $cloudStorageTypeName }}
									</div>
									<div class="col-md-4 settings-cloud-storage-actions">
										@if($cloudStorageTypeIsLinked == 1)
											<button type="button" class="btn btn-info btn-xs" onclick="performUnLinkCloudStorage('{{ $cloudStorageTypeId }}', '{{ $cloudStorageTypeCode }}', '{{ $cloudStorageTypeName }}');">
												Unlink
											</button>
										@else
											<button type="button" class="btn btn-info btn-xs" onclick="performLinkCloudStorage('{{ $cloudStorageTypeId }}', '{{ $cloudStorageTypeCode }}');">
												Link
											</button>										
										@endif
									</div>
								</div>

							@endforeach
						</div>
					</div> 
				@endif-->
				<!-- 
				@if($appHasImportOptions == 1)
					<div class="row infoRow">
						<div class="col-md-12">
							<div class="row">
								<b>Imports:</b>
								<br/>
							</div>
							@foreach($cloudStorageTypeList as $cloudStorageType)
								@php
								$cloudStorageTypeId = $cloudStorageType['id'];
								$cloudStorageTypeCode = $cloudStorageType['code'];
								$cloudStorageTypeName = $cloudStorageType['name'];
								$cloudStorageTypeIconUrl = $cloudStorageType['iconUrl'];
								$cloudStorageTypeIsLinked = $cloudStorageType['isLinked'];
								@endphp

								<div class="row">
									<div class="col-md-2">
										<img src="{{ $cloudStorageTypeIconUrl }}" class="settings-cloud-storage-icon" />
									</div> 
									<div class="col-md-6 settings-cloud-storage-name">
										{{ $cloudStorageTypeName }}
									</div>
									<div class="col-md-4 settings-cloud-storage-actions">
										@if($cloudStorageTypeIsLinked == 1)
											<button type="button" class="btn btn-info btn-xs" onclick="performImportFromCloudStorage('{{ $cloudStorageTypeId }}', '{{ $cloudStorageTypeCode }}');">
												Import
											</button>
										@else
											<button type="button" class="btn btn-info btn-xs" onclick="performLinkAndImportFromCloudStorage('{{ $cloudStorageTypeId }}', '{{ $cloudStorageTypeCode }}');">
												Link & Import
											</button>										
										@endif
									</div>
								</div>

							@endforeach						
						</div>
					</div> 
				@endif-->
				<div class="row infoRow">
					<div class="col-md-12">
						Print Preferences:
						<br/>
						<b>{!! $printFieldStr !!}</b>
					</div>
				</div>		
			</div>
		</div>
	</div>
</div>
<div id="divForImportCloudAttachmentSelection"></div>