<div id="selPartContentRecipientModal" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
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
				            {{ Form::select('shrAppuserId[]', array(), NULL, ['class' => 'form-control', 'id' => 'shrAppuserId']) }}
						</div>
					</div>
				</div>	
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('shrGroupId', 'Group', ['class' => 'control-label']) !!}
				            {{ Form::select('shrGroupId[]', array(), NULL, ['class' => 'form-control', 'id' => 'shrGroupId']) }}
						</div>
					</div>
				</div>				
			</div>
			<div class="modal-footer">
				<div class="row">
					<div class="col-md-6" align="left">
						<button type="button" class="btn btn-default btn-sm" id="selRecipientForPartShareCancelBtn">
							Cancel
						</button>						
					</div>
					<div class="col-md-6" align="right">
						<button type="button" class="btn btn-primary btn-sm" id="selRecipientForPartShareNextBtn">
							Next
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

		$("#selRecipientForPartShareCancelBtn").click(function() {
			$("#shrAppuserId").val(null).trigger('change');
			$("#shrGroupId").val(null).trigger('change');
			$("#selPartContentRecipientModal").modal('hide');
		});

		$("#selRecipientForPartShareNextBtn").click(function()
		{
			var dataToSend = compileSessionParams();
			dataToSend += "&convText="+"{{ rawurlencode($convText) }}";

			var selAppuserContactId = $("#shrAppuserId").val();
			var selGroupId = $("#shrGroupId").val();

			var isAppuserSelected = false;
			if(selAppuserContactId != null && selAppuserContactId > 0)
			{
				isAppuserSelected = true;
			}

			var isGroupSelected = false;
			if(selGroupId != null && selGroupId > 0)
			{
				isGroupSelected = true;
			}

			if(isGroupSelected === true || isAppuserSelected === true)
			{
				if(isGroupSelected === true && isAppuserSelected === true)
				{
					errorToast.push("Select either Group or Appuser. But not both.");
				}
				else if(isAppuserSelected === true)
				{
					dataToSend += "&selAppuserContactId="+selAppuserContactId;
					performConversationSelection(dataToSend);
				}
				else if(isGroupSelected === true)
				{
					dataToSend += "&selGroupId="+selGroupId;
					performConversationSelection(dataToSend);					
				}
			}
			else
			{
				errorToast.push("Select a Group or Appuser");
			}					
		});
	});



	function performConversationSelection(dataToSend)
	{
		var urlStr = "{!! route('content.sharePartContentConversationSelectionModal') !!}";

		$('#selPartContentRecipientModal').modal('hide');

		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);			
						
					$("#divShareContentOptions").html(data.view);
					$("#selPartContentConversationModal").modal('show');
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
</script>
