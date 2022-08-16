<div id="selRecipientModal" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="selRecipientAppuserHeader">
					{{ $page_description or null }}	
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('shrAppuserId', 'User', ['class' => 'control-label']) !!}
				            {{ Form::select('shrAppuserId[]', array(), NULL, ['class' => 'form-control', 'id' => 'shrAppuserId', 'multiple' => 'multiple']) }}
						</div>
					</div>
				</div>	
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('shrGroupId', 'Group', ['class' => 'control-label']) !!}
				            {{ Form::select('shrGroupId[]', array(), NULL, ['class' => 'form-control', 'id' => 'shrGroupId', 'multiple' => 'multiple']) }}
						</div>
					</div>
				</div>				
			</div>
			<div class="modal-footer">
				<div class="row">
					<div class="col-md-6" align="left">
						<button type="button" class="btn btn-default btn-sm" id="selRecipientCancelBtn">
							Cancel
						</button>						
					</div>
					<div class="col-md-6" align="right">
						<button type="button" class="btn btn-primary btn-sm" id="selRecipientSaveBtn">
							Send
						</button>						
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	var contentOrgKey = '{{ $currOrgKey }}';	
	var contentUserId = '{{ $currUserId }}';	
	var contentLoginToken = '{{ $currLoginToken }}';		
	$(document).ready(function()
	{
		$('#shrGroupId').css('width', '100%');
		$('#shrGroupId').select2({
			placeholder: "Select Group",
			allowClear: true,
			dataType: 'json',
			ajax: {
				url: "{{ route('group.getAppuserGroupList') }}",
				type: 'POST',
				quietMillis: 1000, 
				data: function (params) {
					return {
						searchStr: params.term,
						page: params.page || 1,
						userId: contentUserId,
						orgId: contentOrgKey,
						loginToken: contentLoginToken,
						forShare: 1,
					};
				},
				processResults: function (data, params) {
					return {
						results: data.results,
					};		
				},
			},
		});
		$('#shrGroupId').val(null).trigger('change');

		let empUserListUrl = "{{ route('contact.getAppuserContactList') }}";
		@if($currOrgId > 0)
			empUserListUrl = "{{ route('orgApp.getAppuserEmployeeList') }}";
		@endif
		
		$('#shrAppuserId').css('width', '100%');
		$('#shrAppuserId').select2({
			placeholder: "Select User",
			allowClear: true,
			dataType: 'json',
			ajax: {
				url: empUserListUrl,
				type: 'POST',
				quietMillis: 1000, 
				data: function (params) {
					return {
						searchStr: params.term,
						page: params.page || 1,
						userId: contentUserId,
						orgId: contentOrgKey,
						loginToken: contentLoginToken,
						onlySracUsers: 1,
						forShare: 1,
					};
				},
				processResults: function (data, params) {
					return {
						results: data.results,
					};		
				},
			},
		});
		$('#shrAppuserId').val(null).trigger('change');

		$("#selRecipientCancelBtn").click(function() {
			$("#shrAppuserId").val(null).trigger('change');
			$("#shrGroupId").val(null).trigger('change');
			$("#selRecipientModal").modal('hide');
			resetContentSelection();
		});

		$("#selRecipientSaveBtn").click(function() {
			var selAppuserIdArr = $("#shrAppuserId").val();
			var selGroupIdArr = $("#shrGroupId").val();
			
			// if(i == 0)
			{
				// i++;
				if((selAppuserIdArr != null && selAppuserIdArr.length > 0) || (selGroupIdArr != null && selGroupIdArr.length > 0))
				{
					bootbox.prompt({
						message: "Do you really want to perform share operation?",
						title: "Confirm SocioRAC Share",
						inputType: 'checkbox',
					    inputOptions: [
					    	{
						        text: 'Share as Locked Note',
						        value: '1',
					    	},
					    	{
						        text: 'Share as Restricted Content',
						        value: '2',
					    	}
					    ],
						callback: function (resultArr) {
							if(resultArr == null) {
								// Do nothing
								$("#selRecipientCancelBtn").trigger('click');
							}
							else {
								var isLocked = 0;
								if(resultArr.indexOf('1') >= 0) {
									isLocked = 1;
								}

								var isShareEnabled = 1;
								if(resultArr.indexOf('2') >= 0) {
									isShareEnabled = 0;
								}
                                $('#onelineChatText').val('')
								performShareContentToUser({{ $currIsFolder}}, selGroupIdArr, selAppuserIdArr, isLocked, isShareEnabled, '{{ $forContent }}', contentOrgKey, {{ $performContentRemove }}, 0, {{ $isOneLineQuickShare }}, '{{ $oneLineContentText }}');
								
								$("#selRecipientModal").modal('hide');
							}
						},
					});
				}
				else
				{
					errorToast.push("Select Group(s) and/or Appuser(s)");
					i = 0;
				}
			}					
		});
	});
</script>
