<div class="row attListRow">						
	<div class="col-md-offset-1 col-md-8">
		<div class="form-group">
			@if($attCloudStorageTypeId > 0)
				<i class="fa fa-cloud"></i>&nbsp;
			@endif
			<span class="modalTextmodalText">{{ $attachmentFilename }}</span>
		</div>
	</div>
	<div class="col-md-2" align="right">
		@if($isView)
			<button type="button" class="btn btn-xs btn-info" onclick="viewContentAttachmentInfo('{{ $id }}', '{{ $attachmentId }}', '{{ $isFolderFlag }}', '{{ $orgKey }}');">
				<i class="fa fa-info-circle"></i>
			</button>
			@if($isConversation && !$contentIsRemoved && $isContentPartRepliableByUser)
				<button type="button" class="btn btn-xs btn-success" onclick="setUpConversationAttachmentReply('{{ $attachmentId }}', '{{ $attachmentFilename }}');">
					<i class="fa fa-reply"></i>
				</button>
			@endif
		@endif
			
		@if($attCloudStorageTypeId > 0)
			<button type="button" class="btn btn-xs btn-purple" onclick="downloadCloudAttachment('{{ $attCloudFileUrl }}');">
				<i class="fa fa-arrows-alt"></i>
			</button>
		@else
			<button type="button" class="btn btn-xs btn-purple" onclick="downloadAttachment('{{ $attachmentId }}','{{ $isFolderFlag }}', 0, 0, 0, '{{ $orgIsSaveShareEnabled }}', '{{ $orgKey }}', '{{ $isLocked }}', '{{ $isShareEnabled }}');">
				<i class="fa fa-arrows-alt"></i>
			</button>
		@endif
		@if($orgIsSaveShareEnabled == 1)
			@if($attCloudStorageTypeId == 0)
				<button type="button" class="btn btn-xs btn-warning attachment_file_existing" onclick="downloadAttachment('{{ $attachmentId }}','{{ $isFolderFlag }}', 1, 0, 0, '{{ $orgIsSaveShareEnabled }}', '{{ $orgKey }}', '{{ $isLocked }}', '{{ $isShareEnabled }}');">
					<i class="fa fa-download"></i>
				</button>
			@endif
		@endif
		@if(!$isView)
			<button type="button" class="btn btn-xs btn-danger" onclick="removeUploadedFile(this, '{{ $attachmentId }}');"><i class="fa fa-trash"></i></button>
		@endif
	</div>
</div>