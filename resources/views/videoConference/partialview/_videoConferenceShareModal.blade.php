<?php
$adminIcon = url(Config::get('app_config.icon_admin'));
$userIcon = url(Config::get('app_config.icon_user'));

$conferenceId = 0;
$conferenceSubject = "";
$conferenceIsUpcoming = 0;
$scheduledStartTs = null;
$scheduledEndTs = null;
$scheduledStartTmStr = null;
$scheduledEndTmStr = null;
$actualStartTs = null;
$actualEndTs = null;
$actualStartTmStr = null;
$actualEndTmStr = null;
$conferenceCode = "";
$conferencePin = "";
$totalInvites = 0;
$totalAttendants = 0;

if(isset($videoConference))
{
	$conferenceId = $videoConference["conferenceId"];
	$conferenceSubject = $videoConference['conferenceSubject'];
	$conferenceIsUpcoming = $videoConference['isUpcoming'];
	$scheduledStartTs = $videoConference['scheduledStartTs'];
	$scheduledEndTs = $videoConference['scheduledEndTs'];
	$actualStartTs = $videoConference['actualStartTs'];
	$actualEndTs = $videoConference['actualEndTs'];
	$conferenceCode = $videoConference['conferenceCode'];
	$conferencePin = $videoConference['conferencePin'];
	$totalInvites = $videoConference['totalInvites'];
	$totalAttendants = $videoConference['totalAttendants'];
	
	if(isset($scheduledStartTs) && $scheduledStartTs != "")
	{
		$scheduledStartTmStr = dbToDispDateTimeWithTZ($scheduledStartTs, $tzStr);
	}
	
	if(isset($scheduledEndTs) && $scheduledEndTs != "")
	{
		$scheduledEndTmStr = dbToDispDateTimeWithTZ($scheduledEndTs, $tzStr);
	}
	
	if(isset($actualStartTs) && $actualStartTs != "")
	{
		$actualStartTmStr = dbToDispDateTimeWithTZ($actualStartTs, $tzStr);
	}
	
	if(isset($actualEndTs) && $actualEndTs != "")
	{
		$actualEndTmStr = dbToDispDateTimeWithTZ($actualEndTs, $tzStr);
	}
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
			sendUserVideoConferenceInvitation();
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

	function sendUserVideoConferenceInvitation()
	{
		var urlStr = "{{ route('sysVideoConference.sendEmailInvitation') }}";

		var sessionParams = compileVideoConferenceSessionParams();
		@if(isset($orgKey))
			sessionParams = compileVideoConferenceSessionParams('{{ $orgKey }}');
		@endif

		var recipientName = $('input[name="recipientName"]').val();
		var recipientEmail = $('input[name="recipientEmail"]').val();

		var n = getActiveUTCOffset();	
		var dataToSend = sessionParams+"&ofs="+n+"&conferenceId="+'{{ $conferenceId }}'+"&recipientName="+recipientName+"&recipientEmail="+recipientEmail;

		bootbox.dialog({
			message: "Do you wish to send meeting invitation?",
			title: "Confirm share meeting",
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

								$('#videoConferenceShareInvitationModal').modal('hide');
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

<div id="videoConferenceShareInvitationModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
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
				<!-- <div class="row">
					<div class="col-md-12" id="divViewGroupName" style="display: block;">
						<div class="form-group">
							<b>{{ $conferenceSubject }}</b>
							<br/>
							@if(isset($actualStartTmStr) && isset($actualEndTmStr))
							{{ $actualStartTmStr }} - {{ $actualEndTmStr }}
							@elseif(isset($scheduledStartTmStr) && isset($scheduledEndTmStr))
							{{ $scheduledStartTmStr }} - {{ $scheduledEndTmStr }}
							@endif
						</div>
					</div>
				</div> -->

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