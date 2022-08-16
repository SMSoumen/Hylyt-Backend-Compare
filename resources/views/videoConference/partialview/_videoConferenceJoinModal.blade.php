@php	
	
@endphp
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
<style>
	.detailsRow
	{
		margin-top: 12px !important;
		background: white;
		border-radius: 5px;
		
	}

	.label-black
	{
		color: #000 !important;
	}

	.participant-name
	{
		padding: 5px;
		font-size: 12px;
	}

	.conference-join-options
	{
		margin-top: 5px;
		font-size: 12px;
		font-weight: 600;
	}

	label {
	    margin-top: 2px;
	}

	.icheckbox_flat-yellow 
	{
	    position: absolute;
	    top: 5px;
	    left: 20px;
	}

	#divParticipantList 
	{
	    max-height: 300px;
	    overflow-y: auto;
	    overflow-x: hidden;
	}
</style>
<style>

</style>

<script>
	var frmObj = '#frmJoinVideoConference';
	$(document).ready(function(){

		$('.cbDisableVideo').iCheck({
    		checkboxClass: 'icheckbox_flat-yellow',
  		});

		$('.cbMuteAudio').iCheck({
    		checkboxClass: 'icheckbox_flat-yellow',
  		});

	});

	function validateAndSubmitVideoConferenceJoinForm(forPopUp = 0)
	{
		var currentDtTimestamp = moment().unix();
		currentDtTimestamp *= 1000;

		var conferenceCode = $('input[name="conferenceCode"]').val();
		var conferencePassword = $('input[name="conferencePassword"]').val();
		var userDisplayName = $('input[name="userDisplayName"]').val();
		
		var isValid = true, errorMsg = '';
		@if($byAuthentication == 1)
			if(!conferenceCode || conferenceCode.trim() == "")
			{
				isValid = false;
				errorMsg = 'Meeting Id is required';
			}
			else if(!conferencePassword || conferencePassword.trim() == "")
			{
				isValid = false;
				errorMsg = 'Meeting Password is required';
			}
		@endif
		
		if(!userDisplayName || userDisplayName.trim() == "")
		{
			isValid = false;
			errorMsg = 'Display Name Id is required';
		}
		
		if(isValid)
		{			
			performJoinVideoConference(forPopUp);
		}
		else
		{
			errorToast.push(errorMsg);
		}
	}

	function performJoinVideoConference(forPopUp = 0)
	{	
		var dataToSend = compileVideoConferenceSessionParams('{{ $consOrgKey }}');		
		var urlStr = "{!! route('sysVideoConference.canBeStarted') !!}";
		
		var formDataToSend = $(frmObj).serialize();
		dataToSend = formDataToSend+dataToSend;

		let cbDisableVideoIsChecked = 0;
		$(frmObj + ' input[name="cbDisableVideo"]').each(function() {
	        if ($(this).is(":checked")) {
	            cbDisableVideoIsChecked = 1;
	        }
	    });

		let cbMuteAudioIsChecked = 0;
		$(frmObj + ' input[name="cbMuteAudio"]').each(function() {
	        if ($(this).is(":checked")) {
	            cbMuteAudioIsChecked = 1;
	        }
	    });

		var userDisplayName = $('input[name="userDisplayName"]').val();
		
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
					var conferenceId = data.conferenceId;
					var conferenceCode = data.conferenceCode;
					
					if(data.msg != "")
						successToast.push(data.msg);

					$("#joinVideoConferenceModal").modal('hide');
					refreshVideoConferenceDashboard();
					conductVideoConferenceModal('{{ $consOrgKey }}', conferenceId, conferenceCode, cbDisableVideoIsChecked, cbMuteAudioIsChecked, userDisplayName, forPopUp);
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

<div id="joinVideoConferenceModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmJoinVideoConference']) !!}
				<div class="modal-header content-detail-modal-header">
					<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}	
					</h4>
				</div>
				<div class="modal-body content-detail-modal-body">

					@if($byAuthentication == 1)
						<div class="row content-detail-time-row">						
							<div class="col-md-12">
								<div class="form-group detailsRow" id="divFromDateTime">
									<div class="col-md-12">
				                        <span class="label label-black">Meeting ID</span>
										{!! Form::text('conferenceCode', $conferenceCode, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Meeting ID']) !!}
									</div>
								</div>
							</div>
						</div>	

						<div class="row content-detail-time-row">						
							<div class="col-md-12">
								<div class="form-group detailsRow" id="divFromDateTime">
									<div class="col-md-12">
				                        <span class="label label-black">Meeting PIN</span>
										{!! Form::password('conferencePassword', ['class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => 'Meeting PIN']) !!}
									</div>
								</div>
							</div>
						</div>	
					@else
						<input type="hidden" name="conferenceId" value="{{ $conferenceId }}">
					@endif

					<div class="row content-detail-time-row">						
						<div class="col-md-12">
							<div class="form-group detailsRow" id="divFromDateTime">
								<div class="col-md-12">
			                        <span class="label label-black">Display Name</span>
									{!! Form::text('userDisplayName', $userDisplayName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Display Name']) !!}
								</div>
								<input type="hidden" name="byAuthentication" value="{{ $byAuthentication }}">
							</div>
						</div>
					</div>
				
					<div class="row content-detail-time-row">		
						<div class="col-md-12">
							<div class="form-group detailsRow" id="divFromDateTime">
								<div class="col-md-6">
									<div class="col-md-2">
										<input type="checkbox" class="cbDisableVideo" name="cbDisableVideo" id="cbDisableVideo" value="1" />
									</div>
									<div class="col-md-10 conference-join-options">
											<label class="custom-control-label" for="cbDisableVideo">Disable Video</label>
	                        			
	                        		</div>
								</div>
								<div class="col-md-6">
									<div class="col-md-2">
										<input type="checkbox" class="cbMuteAudio" name="cbMuteAudio" id="cbMuteAudio" value="1" />
									</div>
									<div class="col-md-10 conference-join-options">
											<label class="custom-control-label" for="cbMuteAudio">Mute Audio</label>
	                        		</div>
								</div>
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer content-detail-modal-footer">
					<div class="col-md-4">
						
					</div>
					<div class="col-md-8">
						<div class="row">
							<div class="col-md-12" align="right">

								@php
								$btnText = 'Join';
								if($isStart == 1)
								{
									$btnText = 'Start';
								}
								@endphp

								{!! Form::button($btnText.' in Pop Up', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'validateAndSubmitVideoConferenceJoinForm(1)']) !!}
								&nbsp;&nbsp;
								{!! Form::button($btnText.' in New Window', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'validateAndSubmitVideoConferenceJoinForm(0)']) !!}
							</div>
						</div>
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
<div id="divDependencies"></div>