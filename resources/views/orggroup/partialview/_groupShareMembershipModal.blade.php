<?php
$adminIcon = url(Config::get('app_config.icon_admin'));
$userIcon = url(Config::get('app_config.icon_user'));

$groupName = "";

if(isset($group))
{
	$groupName = $group->name;
}
?>

@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
<style>

</style>

<script>
	var frmObj = '';
	$(document).ready(function(){


	});

	function validateInvitation()
	{
		var currentDtTimestamp = moment().unix();
		currentDtTimestamp *= 1000;

		var recipientName = $('input[name="recipientName"]').val();
		var recipientEmail = $('input[name="recipientEmail"]').val();
		
		var isValid = true, errorMsg = '';		
		if(!recipientName || recipientName.trim() == "")
		{
			isValid = false;
			errorMsg = 'Recipient Name is required';
		}
		else if(!recipientEmail || recipientEmail.trim() == "")
		{
			isValid = false;
			errorMsg = 'Recipient Email is required';
		}
		else if(!validateEmail(recipientEmail))
		{
			isValid = false;
			errorMsg = 'Recipient Email is invalid';
		}

		if(isValid)
		{			
			sendUserGroupMembershipInvitation();
		}
		else
		{
			errorToast.push(errorMsg);
		}

	}

	function validateEmail(email) {
	    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	    return re.test(String(email).toLowerCase());
	}

	function sendUserGroupMembershipInvitation()
	{
		var urlStr = "{{ route('orgGroup.sendGroupJoinInvitation') }}";

		var sessionParams = compileSessionParams();
		@if(isset($orgKey))
			sessionParams = compileSessionParams('{{ $orgKey }}');
		@endif

		var recipientName = $('input[name="recipientName"]').val();
		var recipientEmail = $('input[name="recipientEmail"]').val();

		var n = getActiveUTCOffset();	
		var dataToSend = sessionParams+"&ofs="+n+"&grpId="+'{{ $groupId }}'+"&recipientName="+recipientName+"&recipientEmail="+recipientEmail;

		bootbox.dialog({
			message: "Do you wish to send group invitation?",
			title: "Confirm share group",
				buttons: {
					yes: {
					label: "Yes",
					className: "btn-primary",
					callback: function() {
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
								}
								else
								{
									if(data.msg != "")
										errorToast.push(data.msg);
								}

								$('#groupMembershipShareInvitationModal').modal('hide');
							}
						});
					}
				},
				no: {
					label: "No",
					className: "btn-primary",
					callback: function() {
					}
				}
			}
		});			
	}

</script>

<div id="groupMembershipShareInvitationModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header content-detail-modal-header">
				<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="">
					&times;
				</button>
				<h4 class="modal-title">
					{{ $page_description or null }}	
				</h4>
			</div>

			<div class="modal-body">

				<div class="row content-detail-time-row">						
					<div class="col-md-12">
						<div class="form-group detailsRow">
							<div class="col-md-12">
		                        <span class="label label-black">Recipient Name</span>
								{!! Form::text('recipientName', NULL, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Recipient Name', 'id' => 'recipientName']) !!}
							</div>
						</div>
					</div>
				</div>	

				<div class="row content-detail-time-row">						
					<div class="col-md-12">
						<div class="form-group detailsRow">
							<div class="col-md-12">
		                        <span class="label label-black">Recipient Email</span>
								{!! Form::text('recipientEmail', NULL, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Recipient Email', 'id' => 'recipientEmail']) !!}
							</div>
						</div>
					</div>
				</div>	

			</div>

			<div class="modal-footer content-detail-modal-footer" style="">
				<div class="col-md-12">
					@if(isset($orgKey))
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;Send Invite', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'validateInvitation()']) !!}
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
<div id="divDependencies"></div>