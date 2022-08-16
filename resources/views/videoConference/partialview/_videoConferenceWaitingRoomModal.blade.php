@if($forPopUp == 1)
	<h4>
		Please wait, the host has not started the meeting. We will automatically let you in once meeting has started.
	</h4>
@else
	<div id="videoConferenceWaitingRoomModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header content-detail-modal-header">
					<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="exitAndCloseModal()">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}	
					</h4>
				</div>
				<div class="modal-body content-detail-modal-body">

					<h4>
						Please wait, the host has not started the meeting. We will automatically let you in once meeting has started.
					</h4>

				</div>
			</div>
		</div>
	</div>
@endif

<script type="text/javascript">

	var orgKey = "{{ $currOrgId }}"; 
	var conferenceId = "{{ $conferenceId }}";
	var conferenceCode = "{{ $conferenceCode }}";
	var cbDisableVideo = "{{ $isVideoDisabled }}";
	var cbMuteAudio = "{{ $isAudioMuted }}";
	var userDisplayName = "{{ $participantName }}";
	var forPopUp = "{{ $forPopUp }}";	

	var joinConferenceLoaded = 0;

	var intervalId = setInterval(function() {
		// alert("Interval reached every 5s");
		checkAndLoadConductVideoConferenceModal();
	}, 5000);

	function checkAndLoadConductVideoConferenceModal()
	{
		if(joinConferenceLoaded === 0)
		{
			var urlStr = "{!! route('sysVideoConference.canBeStarted') !!}";

			var n = getActiveUTCOffset();	
			var dataToSend = compileVideoConferenceSessionParams(orgKey)+"&ofs="+n+"&conferenceId="+conferenceId+"&conferenceCode="+conferenceCode+"&cbDisableVideo="+cbDisableVideo+"&cbMuteAudio="+cbMuteAudio+"&userDisplayName="+userDisplayName;

			$.ajax({
				type: "POST",
				url: urlStr,
				crossDomain : true,
				dataType: 'json',
				data: dataToSend,
				success: function(data)
				{``
					if(data.status*1 > 0)
					{
						if(data.allowWaitingRoomJoin == 1)
						{

						}
						else
						{
							$("#videoConferenceWaitingRoomModal").modal('hide');
							clearInterval(intervalId);
							joinConferenceLoaded = 1;

							if(forPopUp == 1)
							{
								loadViewForConductVideoConferenceModal(orgKey, conferenceId, conferenceCode, cbDisableVideo, cbMuteAudio, userDisplayName);
							}
							else
							{
								conductVideoConferenceModal(orgKey, conferenceId, conferenceCode, cbDisableVideo, cbMuteAudio, userDisplayName, forPopUp);
							}
						}							
					}
				}
			});		
		}
		
	}

</script>