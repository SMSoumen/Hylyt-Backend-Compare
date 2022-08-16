@php
	$noAttachmentsFound = true;
@endphp

@if(isset($primAttachmentArr) && count($primAttachmentArr) > 0)
	@php
		$noAttachmentsFound = false;
	@endphp
	@if($isAllNotes == 1 && $showFolderHeader == 1)
		<!-- <div class="row">
			<div class="col-md-12">
				<div class="list-belongs-to-header">Folder</div>
			</div>
		</div> -->
		<div class="list-belongs-to-header-div">Folder</div>
	@endif
	<div class="row" style="margin-left: 0px; margin-right: 0px">
		@foreach($primAttachmentArr as $attIndex => $attObj)
			@php
				$attachmentId = $attObj->enc_content_attachment_id;
				$attachmentFilename = $attObj->stripped_filename;
				$attachmentUrl = $attObj->url;
				$attachmentThumbUrl = $attObj->thumbUrl;
				$attachmentContentId = $attObj->contentId;
				$attachmentContentIsFolder = $attObj->isFolder;
				$attCloudStorageTypeId = $attObj->att_cloud_storage_type_id;
				$attCloudFileUrl = $attObj->cloud_file_url;

				$contentIsLocked = $attObj->contentIsLocked;
				$contentIsShareEnabled = $attObj->contentIsShareEnabled;
			@endphp
			<div class="col-md-2 no-padding" style="margin-top: 15px;">
				<div class="att-card box-shadow">
					<div class="text-center">
						<img class="card-img-top" src="{{ $attachmentThumbUrl }}" style="height: 50px;">
					</div>
					<div class="card-body text-center">
						<p class="att-card-text">{{ $attachmentFilename }}</p>
						<div class="text-center">
							<div class="btn-group">
							<!-- 	<button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadAttachment('{{ $attachmentId }}', {{ $attachmentContentIsFolder }}, 0, 0);">View</button>
								<button type="button" class="btn btn-sm btn-outline-secondary"onclick="showContentDetails( '{{ $attachmentContentId }}', {{ $attachmentContentIsFolder }}, 1, {{ $isFavoritesTab }} );">Content</button> -->
								@if($attCloudStorageTypeId > 0)
									<button type="button" class="btn btn-sm" onclick="downloadCloudAttachment('{{ $attCloudFileUrl }}');">
										<i class="fa fa-expand"></i>
									</button>
								@else
									<button type="button" class="btn btn-sm" onclick="downloadAttachment('{{ $attachmentId }}', {{ $attachmentContentIsFolder }}, 0, 0, 0, '{{ $orgIsSaveShareEnabled }}', '{{ $orgKey }}', '{{ $contentIsLocked }}', '{{ $contentIsShareEnabled }}');"><i class="fa fa-expand"></i></button>
								@endif
								<button class="btn btn-sm" onclick="showContentDetails( '{{ $attachmentContentId }}', {{ $attachmentContentIsFolder }}, 1, {{ $isFavoritesTab }} );"><i class="fa fa-eye"></i></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		@endforeach
	</div>
@endif

@if(isset($secAttachmentArr) && count($secAttachmentArr) > 0)
	@php
		$noAttachmentsFound = false;
	@endphp
	@if($isAllNotes == 1 && $showGroupHeader == 1)
		<!-- <div class="row">
			<div class="col-md-12">
				<div class="list-belongs-to-header">Group</div>
			</div>
		</div> -->
		<div class="list-belongs-to-header-div">Group</div>
	@endif
	<div class="row" style="margin-left: 0px; margin-right: 0px">
		@foreach($secAttachmentArr as $attIndex => $attObj)
			@php
				$attachmentId = $attObj->enc_content_attachment_id;
				$attachmentFilename = $attObj->stripped_filename;
				$attachmentUrl = $attObj->url;
				$attachmentThumbUrl = $attObj->thumbUrl;
				$attachmentContentId = $attObj->contentId;
				$attachmentContentIsFolder = $attObj->isFolder;
				$attCloudStorageTypeId = $attObj->att_cloud_storage_type_id;
				$attCloudFileUrl = $attObj->cloud_file_url;

				$contentIsLocked = $attObj->contentIsLocked;
				$contentIsShareEnabled = $attObj->contentIsShareEnabled;
			@endphp
			<!-- <div class="col-md-2">
				<div class="card box-shadow">
					<img class="card-img-top" src="{{ $attachmentThumbUrl }}" style="width: 50px;">
					<div class="card-body">
						<p class="card-text">{{ $attachmentFilename }}</p>
						<div class="d-flex justify-content-between align-items-center">
							<div class="btn-group">
								<button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadAttachment('{{ $attachmentId }}', {{ $attachmentContentIsFolder }}, 0, 0);">View</button>
								<button type="button" class="btn btn-sm btn-outline-secondary"onclick="showContentDetails( '{{ $attachmentContentId }}', {{ $attachmentContentIsFolder }}, 1, {{ $isFavoritesTab }} );">Content</button>
							</div>
						</div>
					</div>
				</div>
			</div> -->
					<div class="col-md-2 no-padding">
				<div class="att-card box-shadow">
					<div class="text-center">
						<img class="card-img-top" src="{{ $attachmentThumbUrl }}" style="height: 50px;">
					</div>
					<div class="card-body text-center">
						<p class="att-card-text">{{ $attachmentFilename }}</p>
						<div class="text-center">
							<div class="btn-group">
							<!-- 	<button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadAttachment('{{ $attachmentId }}', {{ $attachmentContentIsFolder }}, 0, 0);">View</button>
								<button type="button" class="btn btn-sm btn-outline-secondary"onclick="showContentDetails( '{{ $attachmentContentId }}', {{ $attachmentContentIsFolder }}, 1, {{ $isFavoritesTab }} );">Content</button> -->

								@if($attCloudStorageTypeId > 0)
									<button type="button" class="btn btn-sm" onclick="downloadCloudAttachment('{{ $attCloudFileUrl }}');">
										<i class="fa fa-expand"></i>
									</button>
								@else
									<button type="button" class="btn btn-sm" onclick="downloadAttachment('{{ $attachmentId }}', {{ $attachmentContentIsFolder }}, 0, 0, 0, '{{ $orgIsSaveShareEnabled }}', '{{ $orgKey }}', '{{ $contentIsLocked }}', '{{ $contentIsShareEnabled }}');"><i class="fa fa-expand"></i></button>
								@endif
								<button class="btn btn-sm" onclick="showContentDetails( '{{ $attachmentContentId }}', {{ $attachmentContentIsFolder }}, 1, {{ $isFavoritesTab }} );"><i class="fa fa-eye"></i></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		@endforeach
	</div>
@endif

@if($noAttachmentsFound)
	<div class="noContentsDiv">No Attachment(s)</div>
@endif
