<?php
$adminIcon = url(Config::get('app_config.icon_admin'));
$userIcon = url(Config::get('app_config.icon_user'));

// $groupId = 0;
$groupName = "";
$groupDescription = "-";

if(isset($group))
{
	// $groupId = $group->group_id;
	$groupName = $group->name;
	$groupDescription = $group->description;
}
?>
@if($isUserAdmin)
	<script>
		$(document).ready(function(){
			$('#frmRenameGroup').formValidation({
				framework: 'bootstrap',
				icon:
				{
					valid: "{!!  Config::get('app_config.validation_success_icon') !!}",
					invalid: "{!!  Config::get('app_config.validation_failure_icon') !!}",
					validating: "{!!  Config::get('app_config.validation_ongoing_icon') !!}"
				},
				fields:
				{
					//General Details
					grpName:
					{
						validators:
						{
							notEmpty:
							{
								message: 'Group Name is required'
							},
							remote: {
								message: 'Duplicate Group Name',
								url: "{!! route('orgGroup.validateGroupName') !!}",
								type: 'POST',
	                			delay: {!!  Config::get('app_config.validation_call_delay') !!} ,
								data: function(validator, $field, value) 
								{			
									return {
										groupId: '{{ $groupId }}',
										userId: getCurrentUserId(),
										orgId: getCurrentOrganizationId(),
										loginToken: getCurrentLoginToken(),		
									};
								}
							}						
						}
					}
				}
			})
			.on('success.form.fv', function(e) {
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveGroupName($form);
	        });
	        
			$('#frmModifyGroupQuota').formValidation({
				framework: 'bootstrap',
				icon:
				{
					valid: "{!!  Config::get('app_config.validation_success_icon') !!}",
					invalid: "{!!  Config::get('app_config.validation_failure_icon') !!}",
					validating: "{!!  Config::get('app_config.validation_ongoing_icon') !!}"
				},
				fields:
				{
					//General Details
					quotaMb:
					{
						validators:
						{
							notEmpty:
							{
								message: 'Group Quota is required'
							},
							between: {
	                            min: {{ $usedSpaceMb }},
	                            max: {{ $totalAvailableSpaceMb }},
	                            message: 'Group Quota must be between {{ $usedSpaceMb }} MB and {{ $totalAvailableSpaceMb }} MB'
	                        }						
						}
					}
				}
			})
			.on('success.form.fv', function(e) {
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveGroupQuota($form);
	        });
		});
	
		function loadQuickEditGroupName(isView)
		{
			$( "#divViewGroupName" ).slideToggle( "slow" );
			$( "#divEditGroupName" ).slideToggle( "slow" );
			
			if(isView == 1)
			{
				$("#grpName").val("{{ $groupName }}");
				$('#frmRenameGroup').data('formValidation').resetForm();
			}
		}
		
		function saveGroupName(frmObj)
		{
			var dataToSend = compileSessionParams();
			var formDataToSend = $(frmObj).serialize();	
			dataToSend = formDataToSend+dataToSend;
			
			$.ajax({
				type: "POST",
				url: "{!! route('group.rename') !!}",
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					if(data.status*1 > 0)
					{
						if(data.msg != "")
							successToast.push(data.msg);
							
						location.reload();
					}
					else
					{
						if(data.msg != "")
							errorToast.push(data.msg);
					}
				}
			});
		}
	
		function loadQuickEditGroupQuota(isView)
		{
			$( "#divViewGroupQuota" ).slideToggle( "slow" );
			$( "#divEditGroupQuota" ).slideToggle( "slow" );
			
			if(isView == 1)
			{
				$("#quotaMb").val("{{ $allottedSpaceMb }}");
				$('#frmModifyGroupQuota').data('formValidation').resetForm();
			}
		}
		
		function saveGroupQuota(frmObj)
		{
			var dataToSend = compileSessionParams();
			var formDataToSend = $(frmObj).serialize();	
			dataToSend = formDataToSend+dataToSend;
			
			$.ajax({
				type: "POST",
				url: "{!! route('group.modifyQuotaDetails') !!}",
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					if(data.status*1 > 0)
					{
						if(data.msg != "")
							successToast.push(data.msg);
							
						location.reload();
					}
					else
					{
						if(data.msg != "")
							errorToast.push(data.msg);
					}
				}
			});
		}

		function viewGroupMembershipShareModal()
		{
			$('#groupInfoModal').modal('hide');

			var urlStr = "{!! route('orgGroup.getMembershipShareModal') !!}";

			var n = getActiveUTCOffset();	
			var dataToSend = compileSessionParams('{{ $orgKey }}')+"&ofs="+n+"&grpId="+'{{ $groupId }}';

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{
					var contentDetailsStr = "";
					if(data.status*1 > 0)
					{
						if(data.msg != "")
							successToast.push(data.msg);
							
						$("#divForInviteMember").html(data.view);
						$("#groupMembershipShareInvitationModal").modal('show');
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
@endif
<div id="groupInfoModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Group Details
					@if($showSendInviteBtn)
						&nbsp;&nbsp;
				    	{!! Form::button('Invite', ['type' => 'button', 'class' => 'btn btn-default btn-xs', 'onclick' => "viewGroupMembershipShareModal();"]) !!}
					@endif
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-3">
						<img src="{{ $groupPhotoThumbUrl }}" style="border-radius: 50%;"/>
					</div>
					<div class="col-md-9" id="divViewGroupName" style="display: block;">
						<div class="form-group">
							{!! Form::label('group_name', 'Name', ['class' => 'control-label']) !!}
							@if($isUserAdmin)
								&nbsp;&nbsp;
						    	{!! Form::button('<i class="fa fa-edit"></i>', ['type' => 'button', 'class' => 'btn btn-default btn-xs', 'onclick' => "loadQuickEditGroupName(0);"]) !!}
							@endif
							<br/>
							<b>{{ $groupName }}</b>
							<br/>
							{{ $groupDescription }}
						</div>
					</div>
					<div class="col-md-6" id="divEditGroupName" style="display: none;">
						@if($isUserAdmin)
							{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmRenameGroup']) !!}
								<div class="form-group">
									{!! Form::label('grpName', 'Group Name', ['class' => 'control-label']) !!}
									{!! Form::text('grpName', $groupName, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'grpName']) !!} 
								</div>
								<div class="form-group">
									{!! Form::label('description', 'Description', ['class' => 'control-label']) !!}
									{!! Form::text('description', $groupDescription, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'description']) !!}
								</div>
								<br/>
								{!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-danger btn-xs', 'onclick' => "loadQuickEditGroupName(1);"]) !!}
								{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-success btn-xs']) !!}
								
								{!! Form::hidden('grpId', $groupId, ['id' => 'groupId']) !!}
							{!! Form::close() !!}
						@endif
					</div>
				</div>
				@if($isUserAdmin && $isOpenGroup == 1)
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								{!! Form::label('openGroupRegCode', 'Open Group Code:', ['class' => 'control-label']) !!}
								<br/>
								{{ $openGroupRegCode }}
							</div>
						</div>
					</div>
					<br/>
				@endif
				<div class="row">
					<div class="col-md-9" id="divViewGroupQuota" style="display: block;">
						<div class="form-group">
							{!! Form::label('group_quota', 'Quota', ['class' => 'control-label']) !!}
							@if($isUserAdmin)
								&nbsp;&nbsp;
					        	<a href="javascript:void(0)" class="btn btn-default btn-xs" onclick="loadQuickEditGroupQuota(0);">
						    		<i class="fa fa-edit"></i>
						    	</a>
							@endif
							<br/>
							{{ $groupQuotaStr }}
						</div>
					</div>
					<div class="col-md-6" id="divEditGroupQuota" style="display: none;">
						@if($isUserAdmin)
							{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyGroupQuota']) !!}
								<div class="form-group">
									{!! Form::label('group_quota', 'Allot Group Quota', ['class' => 'control-label']) !!}
									<div class="input-group">
										{!! Form::text('quotaMb', $allottedSpaceMb, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'quotaMb']) !!} 
										<span class="input-group-addon">MB(s)</span>
									</div>									
									<br/>
									{!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-danger btn-xs', 'onclick' => "loadQuickEditGroupQuota(1);"]) !!}
									{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-success btn-xs']) !!}
								</div>
								{!! Form::hidden('grpId', $groupId, ['id' => 'groupId']) !!}
							{!! Form::close() !!}
						@endif
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						Total Notes : {{ $totalNoteCount }}
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						Total Members : {{ $totalMemberCount }}
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						Active Members : {{ $activeMemberCount }}
					</div>
				</div>
							<br/>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('group_member', 'Group Member(s)', ['class' => 'control-label']) !!}
						</div>							
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="table">
					        <table id="employees-table" class="table" width="100%">
					            <tbody>
					            	@if(isset($members) && count($members) > 0)
					            		@foreach($members as $member)
					            			@php
					            			$memName = $member["name"];
					            			$memEmail = $member["email"];
					            			$memIsAdmin = $member["is_admin"];
					            			$memIsGhost = 0;
					            			if(isset($member["is_ghost"]))
					            				$memIsGhost = $member["is_ghost"];
					            			$memIsActive = $member["isActive"];
					            			$memNoteCount = $member["noteCount"];
					            			@endphp
					            			<tr>
							                    <td style="vertical-align: middle !important;" width="10%">
							                    	<img src="{{ $userIcon }}" width="35px"/>
							                    </td>
							                    <td>
							                    	{{ $memName }} ({{ $memNoteCount }} notes)
							                    	<br/>
							                    	{{ $memEmail }}
							                    	@if($memIsGhost === 1)
							                    		(ghost)
							                    	@endif
							                    </td>
							                    <td style="vertical-align: middle !important;" width="10%">
							                    	@if($memIsAdmin == 1)
							                    		<img src="{{ $adminIcon }}" width="35px"/>
							                    	@else
							                    		&nbsp;
							                    	@endif
							                    </td>
							                </tr>
					            		@endforeach
					            	@endif
					            </tbody>
					        </table>
					    </div>			
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="divForInviteMember"></div>