@php
$assetBasePath = Config::get('app_config.assetBasePath'); 
@endphp
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
<style>
	.select2-container--default .select2-search--inline .select2-search__field {
	    width: 200px !important;
	}
	.select2-container .select2-selection--single {
	    height: 35px;
	}
	.select2-container--default .select2-selection--single .select2-selection__rendered {
	    line-height: 25px;
	}
</style>
<script>
	var frmDtTmPicker;
	var toDtTmPicker;
	
	$(document).ready(function(){		
		$('#type_id').css('width', '100%');
		$('#type_id').select2({
			placeholder: "Select Type",
			allowClear: true,
		});
					
  		@if(is_array($selTypeIdArr) && count($selTypeIdArr) > 0)
  			var selTypeIdArr = [];
  			@foreach($selTypeIdArr as $selTypeId)
  				selTypeIdArr.push({{ $selTypeId }});
  			@endforeach
  			$('#type_id').val(selTypeIdArr).trigger('change');
  		@else
			$('#type_id').val(null).trigger('change');
		@endif
		
		$('#show_attachment_id').css('width', '100%');
		$('#show_attachment_id').select2({
			placeholder: "Select Attachment Type",
			allowClear: true,
		});
					
  		@if(isset($selShowAttachmentId))
  			$('#show_attachment_id').val({{ $selShowAttachmentId }}).trigger('change');
  		@else
			$('#show_attachment_id').val(null).trigger('change');
		@endif
		
		$('#repeat_status_id').css('width', '100%');
		$('#repeat_status_id').select2({
			placeholder: "Select Repeat Status",
			allowClear: true,
		});
					
  		@if(isset($selRepeatStatusId))
  			$('#repeat_status_id').val({{ $selRepeatStatusId }}).trigger('change');
  		@else
			$('#repeat_status_id').val(null).trigger('change');
		@endif
		
		$('#completed_status_id').css('width', '100%');
		$('#completed_status_id').select2({
			placeholder: "Select Complete Status",
			allowClear: true,
		});
					
  		@if(isset($selCompletedStatusId))
  			$('#completed_status_id').val({{ $selCompletedStatusId }}).trigger('change');
  		@else
			$('#completed_status_id').val(null).trigger('change');
		@endif

		
		$('#attachment_type_id').css('width', '100%');
		$('#attachment_type_id').select2({
			placeholder: "Select Attachment Type",
			allowClear: true,
		});
					
  		@if(isset($selAttachmentTypeIdArr) && is_array($selAttachmentTypeIdArr) && count($selAttachmentTypeIdArr) > 0)
  			var selAttachmentTypeIdArr = [];
  			@foreach($selAttachmentTypeIdArr as $selAttachmentTypeId)
  				selAttachmentTypeIdArr.push({{ $selAttachmentTypeId }});
  			@endforeach
  			$('#attachment_type_id').val(selAttachmentTypeIdArr).trigger('change');
  		@else
			$('#attachment_type_id').val(null).trigger('change');
		@endif
		
		$('#date_filter_type_id').css('width', '100%');
		$('#date_filter_type_id').select2({
			placeholder: "Select Date Filter",
			allowClear: true,
		});
		$("#date_filter_type_id").on("change",function(e)
		{	
			var filterTypeId = $(this).val();
			if(filterTypeId*1 == -1)
			{
				$('#filDateRangeDiv').hide();
				$('#filDateDayCountDiv').hide();

				$('#filDateDayCount').val('');

				$('#fromTimeStamp').val('');
				$('#toTimeStamp').val('');
			}
			else if(filterTypeId*1 == 0)
			{
				$('#filDateRangeDiv').show();
				$('#filDateDayCountDiv').hide();

				$('#filDateDayCount').val('');
			}	
			else if(filterTypeId*1 == 1)
			{
				$('#filDateRangeDiv').hide();
				$('#filDateDayCountDiv').show();

				$('#fromTimeStamp').val('');
				$('#toTimeStamp').val('');
			}		   				
		});
					
  		@if(isset($filDateFilterType))
  			$('#date_filter_type_id').val({{ $filDateFilterType }}).trigger('change');
  		@else
			$('#date_filter_type_id').val(null).trigger('change');
		@endif

		@if($isFolder)
			$('#folder_id').css('width', '100%');
			$('#folder_id').select2({
				placeholder: "Select Folder",
				allowClear: true,
				dataType: 'json',
				ajax: {
					url: "{!!  url('/loadSelectFolderList') !!}",
					type: 'POST',
					quietMillis: 1000, 
					data: function (params) {
						return {
							searchStr: params.term,
							page: params.page || 1,
							userId: getCurrentUserId(),
							orgId: getCurrentOrganizationId(),
							loginToken: getCurrentLoginToken(),
						};
					},
					processResults: function (data, params) {
						return {
							results: data.results,
						};		
					},
				},
			});
					
	  		@if(count($selFolderIdArr) > 0)
	  			var selFolderIdArr = [];
	  			@foreach($selFolderIdArr as $selFolderId)
	  				selFolderIdArr.push({{ $selFolderId }});
	  			@endforeach
	  			$('#folder_id').val(selFolderIdArr).trigger('change');
	  		@else
				$('#folder_id').val(null).trigger('change');
			@endif
			
			
			$('#source_id').css('width', '100%');
			$('#source_id').select2({
				placeholder: "Select Source",
				allowClear: true,
				dataType: 'json',
				ajax: {
					url: "{!!  url('/loadSelectSourceList') !!}",
					type: 'POST',
					quietMillis: 1000, 
					data: function (params) {
						return {
							searchStr: params.term,
							page: params.page || 1,
							userId: getCurrentUserId(),
							orgId: getCurrentOrganizationId(),
							loginToken: getCurrentLoginToken(),
						};
					},
					processResults: function (data, params) {
						return {
							results: data.results,
						};		
					},
				},
			});
					
	  		@if(is_array($selSourceIdArr) && count($selSourceIdArr) > 0)
	  			var selSourceIdArr = [];
	  			@foreach($selSourceIdArr as $selSourceId)
	  				selSourceIdArr.push({{ $selSourceId }});
	  			@endforeach
	  			$('#source_id').val(selSourceIdArr).trigger('change');
	  		@else
				$('#source_id').val(null).trigger('change');
			@endif
	    @endif
	    
	    @if(!$isFolder || $isAllNotes || $isVirtualFolder == 1)
			$('#group_id').css('width', '100%');
			$('#group_id').select2({
				placeholder: "Select Group",
				allowClear: true,
				dataType: 'json',
				ajax: {
					url: "{!!  url('/loadSelectGroupList') !!}",
					type: 'POST',
					quietMillis: 1000, 
					data: function (params) {
						return {
							searchStr: params.term,
							page: params.page || 1,
							userId: getCurrentUserId(),
							orgId: getCurrentOrganizationId(),
							loginToken: getCurrentLoginToken(),
						};
					},
					processResults: function (data, params) {
						return {
							results: data.results,
						};		
					},
				},
			});
					
	  		@if(is_array($selGroupIdArr) && count($selGroupIdArr) > 0)
	  			var selGroupIdArr = [];
	  			@foreach($selGroupIdArr as $selGroupId)
	  				selGroupIdArr.push({{ $selGroupId }});
	  			@endforeach
	  			$('#group_id').val(selGroupIdArr).trigger('change');
	  		@else
				$('#group_id').val(null).trigger('change');
			@endif
			
		@endif
			
		$('#tag_id').css('width', '100%');
		$('#tag_id').select2({
			placeholder: "Select Tag",
			allowClear: true,
			dataType: 'json',
			ajax: {
				url: "{!!  url('/loadSelectTagList') !!}",
				type: 'POST',
				quietMillis: 1000, 
				data: function (params) {
					return {
						searchStr: params.term,
						page: params.page || 1,
						userId: getCurrentUserId(),
						orgId: getCurrentOrganizationId(),
						loginToken: getCurrentLoginToken(),
					};
				},
				processResults: function (data, params) {
					return {
						results: data.results,
					};		
				},
			},
		});
		
  		@if(is_array($selTagIdArr) && count($selTagIdArr) > 0)
  			var selTagIdArr = [];
  			@foreach($selTagIdArr as $selTagId)
  				selTagIdArr.push({{ $selTagId }});
  			@endforeach
  			$('#tag_id').val(selTagIdArr).trigger('change');
  		@else
			$('#tag_id').val(null).trigger('change');
		@endif

		$('#senderEmail').css('width', '100%');
		$('#senderEmail').select2({
			placeholder: "Select sender",
			allowClear: true,
			dataType: 'json',
			ajax: {
				url: "{!!  url('/appuserOrgSenderEmailMappedList') !!}",
				type: 'POST',
				quietMillis: 1000, 
				data: function (params) {
					return {
						searchStr: params.term,
						page: params.page || 1,
						userId: getCurrentUserId(),
						orgId: getCurrentOrganizationId(),
						loginToken: getCurrentLoginToken(),
						isSenderVirtualFolder: {{ $isSenderVirtualFolder }},
						senderVirtualFolderEmail: '{{ $senderVirtualFolderEmail }}'
					};
				},
				processResults: function (data, params) {
					return {
						results: data.results,
					};		
				},
			},
		});
					
  		@if(is_array($selSenderEmailIdArr) && count($selSenderEmailIdArr) > 0)
  			var selSenderEmailIdArr = [];
  			@foreach($selSenderEmailIdArr as $selSenderEmailId)
  				selSenderEmailIdArr.push('{{ $selSenderEmailId }}');
  			@endforeach
  			$('#senderEmail').val(selSenderEmailIdArr).trigger('change');
  		@else
			$('#senderEmail').val(null).trigger('change');
		@endif
		
		var fromDt = '';
		@if($filFromDate != '' && $filFromDate > 0)
			fromDt = new Date({{ $filFromDate }});
		@endif
			
		frmDtTmPicker = $('#fromDtTm').datetimepicker({
			date: fromDt,
			format:'DD/MM/YYYY HH:mm'
		}).on('dp.change', function (e) {
			dtTimestamp = e.date.unix();
			dtTimestamp *= 1000;
			$("#fromTimeStamp").val(dtTimestamp);
			//$(frmObj).formValidation('revalidateField', 'fromTimeStamp');
			//$(frmObj).formValidation('revalidateField', 'toTimeStamp');
		});
		
		var toDt = '';
		@if($filToDate != '' && $filToDate > 0)
			toDt = new Date({{ $filToDate }});
		@endif
		
		toDtTmPicker = $('#toDtTm').datetimepicker({				
			date: toDt,
			format:'DD/MM/YYYY HH:mm'
		}).on('dp.change', function (e) {
			dtTimestamp = e.date.unix();
			dtTimestamp *= 1000;
			$("#toTimeStamp").val(dtTimestamp);
			//$(frmObj).formValidation('revalidateField', 'fromTimeStamp');
			//$(frmObj).formValidation('revalidateField', 'toTimeStamp');
		});
				
		$('#chkIsStarred').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsStarred == 1)
  			$('#chkIsStarred').iCheck('check');
  		@endif
				
		$('#chkIsUntagged').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsUntagged == 1)
  			$('#chkIsUntagged').iCheck('check');
  		@endif
				
		$('#chkIsLocked').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsLocked == 1)
  			$('#chkIsLocked').iCheck('check');
  		@endif
				
		$('#chkIsConversation').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsConversation == 1)
  			$('#chkIsConversation').iCheck('check');
  		@endif
				
		$('#chkIsUnread').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsUnread == 1)
  			$('#chkIsUnread').iCheck('check');
  		@endif
				
		$('#chkIsRestricted').iCheck({
    		checkboxClass: 'icheckbox_flat-blue',
  		});
  		
  		@if($chkIsRestricted == 1)
  			$('#chkIsRestricted').iCheck('check');
  		@endif
			
		@if($isAllNotes || $isVirtualFolder == 1)	
			$('#chkShowFolder').iCheck({
	    		checkboxClass: 'icheckbox_flat-blue',
	  		});
	  		
	  		@if($chkShowFolder == 1)
	  			$('#chkShowFolder').iCheck('check');
	  		@endif
					
			$('#chkShowGroup').iCheck({
	    		checkboxClass: 'icheckbox_flat-blue',
	  		});
	  		
	  		@if($chkShowGroup == 1)
	  			$('#chkShowGroup').iCheck('check');
	  		@endif
	  	@endif

	  	@if($filtersNonModifiable == 1)
	  		loadCreateVirtualFolderModal(0);
	  	@endif
	});
</script>
<div id="filterContentModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmFilterContent']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}	
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('type_id', 'Content Type', ['class' => 'control-label']) !!}
					            {{ Form::select('filTypeArr[]', $typeArr, NULL, ['class' => 'form-control', 'id' => 'type_id', 'multiple' => 'multiple']) }}
							</div>
						</div>
						@if($isFolder)
							<div class="col-md-6">
								<div class="form-group">
									{!! Form::label('source_id', 'Source', ['class' => 'control-label']) !!}
					                {{ Form::select('filSourceArr[]', $selSourceArr, NULL, ['class' => 'form-control', 'id' => 'source_id', 'multiple' => 'multiple']) }}
								</div>
							</div>
					    @endif
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('tagList', 'Tag', ['class' => 'control-label']) !!}
				                {{ Form::select('filTagArr[]', $selTagArr, NULL, ['class' => 'form-control', 'id' => 'tag_id', 'multiple' => 'multiple']) }}
							</div>
						</div>
					</div>
					<div class="row">
						@if($isFolder)
							<div class="col-md-6">
								@if($isAllNotes || $isVirtualFolder == 1)
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<input type="checkbox" class="" value="1" id="chkShowFolder" name="chkShowFolder">
												{!! Form::label('chkShowFolder', '&nbsp;&nbsp;Show Folders', ['class' => 'control-label']) !!}
											</div>
										</div>
									</div>
								@endif
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											{!! Form::label('folder_id', 'Folder', ['class' => 'control-label']) !!}
							                {{ Form::select('filFolderArr[]', $selFolderArr, NULL, ['class' => 'form-control', 'id' => 'folder_id', 'multiple' => 'multiple']) }}
										</div>
									</div>
								</div>
							</div>
					    @endif
					    @if(!$isFolder || $isAllNotes || $isVirtualFolder == 1)
							<div class="col-md-6">
								@if($isAllNotes || $isVirtualFolder == 1)
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<input type="checkbox" class="" value="1" id="chkShowGroup" name="chkShowGroup">
												{!! Form::label('chkShowGroup', '&nbsp;&nbsp;Show Groups', ['class' => 'control-label']) !!}
											</div>
										</div>
									</div>
								@endif
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											{!! Form::label('groupId', 'Group', ['class' => 'control-label']) !!}
					                		{{ Form::select('filGroupArr[]', $selGroupArr, NULL, ['class' => 'form-control', 'id' => 'group_id', 'multiple' => 'multiple']) }}
										</div>
									</div>
								</div>
							</div>
					    @endif
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								{!! Form::label('senderEmail', 'Sender', ['class' => 'control-label']) !!}
		                		{{ Form::select('filSenderEmail', $selSenderEmailArr, NULL, ['class' => 'form-control', 'id' => 'senderEmail']) }}
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('repeat_status_id', 'Repeat', ['class' => 'control-label']) !!}
					            {{ Form::select('filRepeatStatus', $repeatStatusArr, NULL, ['class' => 'form-control', 'id' => 'repeat_status_id']) }}
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('completed_status_id', 'Completed', ['class' => 'control-label']) !!}
					            {{ Form::select('filCompletedStatus', $completedStatusArr, NULL, ['class' => 'form-control', 'id' => 'completed_status_id']) }}
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('show_attachment_id', 'Attachment', ['class' => 'control-label']) !!}
					            {{ Form::select('filShowAttachment', $showAttachmentArr, NULL, ['class' => 'form-control', 'id' => 'show_attachment_id']) }}
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('attachment_type_id', 'Attachment Type', ['class' => 'control-label']) !!}
					            {{ Form::select('filAttachmentTypeArr[]', $attachmentTypeArr, NULL, ['class' => 'form-control', 'id' => 'attachment_type_id', 'multiple' => 'multiple']) }}
							</div>
						</div>
					</div>
					<br/>
					@if($appHasTypeReminder == 1 || $appHasTypeCalendar == 1)
						<div class="row">
							<div class="col-md-6">
								{!! Form::label('date_filter_type_id', 'Filter Date', ['class' => 'control-label']) !!}
					            {{ Form::select('filDateFilterType', $dateFilterTypeArr, NULL, ['class' => 'form-control', 'id' => 'date_filter_type_id']) }}
							</div>
							<div class="col-md-6" id="filDateDayCountDiv">
								{!! Form::label('filDateDayCount', 'Day Count', ['class' => 'control-label']) !!}
								{!! Form::text('filDateDayCount', $filDateDayCount, ['class' => 'form-control', 'id' => 'filDateDayCount']) !!}
							</div>
						</div>
						<br/>
						<div class="row" id="filDateRangeDiv">
							<div class="col-md-6">
								{!! Form::label('fromDtTm', 'Start Date', ['class' => 'control-label']) !!}
								<div class="form-group detailsRow" id="spanFromDt">
									<div class='input-group date' id='fromDtTm'>
					                    <input type='text' class="form-control" />
					                    <span class="input-group-addon">
					                        <span class="glyphicon glyphicon-calendar"></span>
					                    </span>
		           	 				</div>
									{!! Form::hidden('fromTimeStamp', $filFromDate, ['id' => 'fromTimeStamp']) !!}
								</div>
							</div>
							<div class="col-md-6">
								{!! Form::label('toDtTm', 'End Date', ['class' => 'control-label']) !!}
								<div class="form-group detailsRow" id="spanToDt">
									<div class='input-group date' id='toDtTm'>
					                    <input type='text' class="form-control" />
					                    <span class="input-group-addon">
					                        <span class="glyphicon glyphicon-calendar"></span>
					                    </span>
		           	 				</div>
									{!! Form::hidden('toTimeStamp', $filToDate, ['id' => 'toTimeStamp']) !!}
								</div>
							</div>
						</div>
						<br/>
					@endif
					<div class="row">
						<div class="col-md-6">						
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsStarred" name="chkIsStarred">
								{!! Form::label('chkIsStarred', '&nbsp;&nbsp;Starred', ['class' => 'control-label']) !!}
							</div>
						</div>
						<div class="col-md-6">	
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsUntagged" name="chkIsUntagged">
								{!! Form::label('chkIsUntagged', '&nbsp;&nbsp;Not Tagged', ['class' => 'control-label']) !!}
							</div>
						</div>
					</div>
					<br/>
					<div class="row">
						<div class="col-md-6">						
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsLocked" name="chkIsLocked">
								{!! Form::label('chkIsLocked', '&nbsp;&nbsp;Locked', ['class' => 'control-label']) !!}
							</div>
						</div>
						<div class="col-md-6">	
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsConversation" name="chkIsConversation">
								{!! Form::label('chkIsConversation', '&nbsp;&nbsp;Conversations', ['class' => 'control-label']) !!}
							</div>
						</div>
					</div>
					<br/>
					<div class="row">
						<div class="col-md-6">						
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsRestricted" name="chkIsRestricted">
								{!! Form::label('chkIsRestricted', '&nbsp;&nbsp;Restricted', ['class' => 'control-label']) !!}
							</div>
						</div>
						<div class="col-md-6">	
							<div class="form-group">
								<input type="checkbox" class="" value="1" id="chkIsUnread" name="chkIsUnread">
								{!! Form::label('chkIsUnread', '&nbsp;&nbsp;Unread', ['class' => 'control-label']) !!}
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="col-md-6" align="left">
						@php
						$btnName = "Create Smart Folder";
						if(isset($virtualFolderId) && $virtualFolderId > 0)
						{
							$btnName = 'Update Smart Folder';
						}
						else
						{
							$virtualFolderId = 0;
						}
						@endphp
						
						{!! Form::button('<i class="fa fa-folder"></i>&nbsp;&nbsp;'.$btnName, ['type' => 'button', 'class' => 'btn btn-warning', 'onclick' => 'loadCreateVirtualFolderModal('.$virtualFolderId.');', 'id' => 'btnCreateVirtualFolder']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::button('<i class="fa fa-refresh"></i>&nbsp;&nbsp;Reset', ['type' => 'button', 'class' => 'btn btn-danger', 'onclick' => 'resetContentListFilter('.$isFolderFlag.', '.$isFavoritesTab.');', 'id' => 'btnResetFilter']) !!}
						&nbsp;
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Apply', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'filtetContentList('.$isFolderFlag.', '.$isFavoritesTab.');', 'id' => 'btnFilter']) !!}
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>