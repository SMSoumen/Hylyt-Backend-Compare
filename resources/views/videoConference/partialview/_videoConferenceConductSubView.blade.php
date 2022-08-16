@php
$participantImgUrl = '';
@endphp

@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif

<style>
	.modal-xl
	{
		width: 1250px;
	}
</style>

<script>
	let currOrgKey = '{{ $currOrgId }}';
	let domainName = '{{ $domainName }}';
	let companyName = 'HyLyt';
	let companyLogoUrl = "https://web.sociorac.com/assets//images/company/hylyt-logo.png";
	let companyLinkUrl = "https://web.sociorac.com";
	let currMeetingObj;
	$(document).ready(function()
	{
		$('#btnLoadMeetingStats').hide();
		$('#btnEndMeeting').hide();

		startMeeting();

	});

	function onConferenceJoined()
	{
		var dataToSend = compileSessionParams(currOrgKey)+"&conferenceId={{ $conferenceId }}&byAuthentication=0&conferenceCode={{ $conferenceCode }}&sendWithButtons=1";
		var urlStr = "{!! route('sysVideoConference.markJoinedByUser') !!}";

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
					
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
								
			},
			complete: function() {
							
			}
		});	
	}

	function onConferenceExited()
	{
		var dataToSend = compileSessionParams(currOrgKey)+"&conferenceId={{ $conferenceId }}&byAuthentication=0&conferenceCode={{ $conferenceCode }}";
		var urlStr = "{!! route('sysVideoConference.markExitedByUser') !!}";

		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				// closeModal();

				if(data.status*1 > 0)
				{
					
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
								
			},
			complete: function() {
				closeModal();							
			}
		});	
	}

	function startMeeting()
	{
		const meetingPin = '1234';

		var startWithAudioMuted = false;
		@if($isAudioMuted == 1)
		startWithAudioMuted = true;
		@endif

		var startWithVideoMuted = false;
		@if($isVideoDisabled == 1)
		startWithVideoMuted = true;
		@endif

		let jitsiOptions = {
		    roomName: '{{ $meetingId }}',
		    height: '500px',
			configOverwrite: { 
                enableClosePage: true,
                startWithAudioMuted: startWithAudioMuted,
                startWithVideoMuted: startWithVideoMuted,
                requireDisplayName: true,
                enableWelcomePage: false,
                enableUserRolesBasedOnToken: true,
                lockRoomGuestEnabled: false,
                // prejoinPageEnabled: true,
                apiLogLevels: 'error',
                desktopSharingFrameRate: {
			        min: 60,
			        max: 60
			    }
			},
			interfaceConfigOverwrite: { 
				TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'profile', 'chat',
                    'etherpad', 'settings', 'raisehand', //'sharedvideo',
                    'videoquality', 'filmstrip', 'feedback', //'localrecording', //'livestreaming',
                    'tileview', 'download'
                ],
                SETTINGS_SECTIONS: [ 'devices', 'moderator' ],
				MOBILE_APP_PROMO: false,
                CONNECTION_INDICATOR_DISABLED: true,
                VIDEO_QUALITY_LABEL_DISABLED: true,
                RECENT_LIST_ENABLED: false,
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                SHOW_CHROME_EXTENSION_BANNER: false,
                SHOW_JITSI_WATERMARK: true,
                APP_NAME: companyName,
                NATIVE_APP_NAME: companyName,
                SHOW_WATERMARK_FOR_GUESTS: false,
                JITSI_WATERMARK_LINK: companyLinkUrl,
                CLOSE_PAGE_GUEST_HINT: true,
                SHOW_PROMOTIONAL_CLOSE_PAGE: true,
                PIP_ENABLED: true
			},
		 	userInfo: {
				email: '{{ $participantEmail }}',
				displayName: '{{ $participantName }}',
				avatarUrl: '{{ $participantImgUrl }}'
			},
			subject: '{{ $meetingTitle }}', 
		    parentNode: document.querySelector('#meet')
		};

		currMeetingObj = new JitsiMeetExternalAPI(domainName, jitsiOptions);
		// currMeetingObj.executeCommand('password', meetingPin);
		// currMeetingObj.executeCommand('toggleVideo');

		// JitsiMeetJS.setLogLevel(JitsiMeetJS.logLevels.ERROR);

        currMeetingObj.executeCommand("subject", "{{ $meetingTitle }}");
        // currMeetingObj.executeCommand("{{ $meetingId }}", "{{ $meetingTitle }}");
        currMeetingObj.executeCommand('avatarUrl', "{{ $participantImgUrl }}");
        currMeetingObj.on('readyToClose', () => {
            //window.location = 'https://profism.com/BU/call/myaccount/index.php/Other/thankYou/1592'
		});

		currMeetingObj.addEventListener("videoConferenceJoined", function(event) {
			// alert('videoConferenceJoined : ' + event.id);
			onConferenceJoined();
		});

		currMeetingObj.addEventListener("videoConferenceLeft", function(event) {
			// alert('videoConferenceLeft : ' + event.roomName);
			onConferenceExited();
		});

		currMeetingObj.addEventListener("participantJoined", function(event) {
			// alert('participantJoined : ' + event.formattedDisplayName);
        	currMeetingObj.executeCommand("subject", "{{ $meetingTitle }}");
		});

		$('#btnLoadMeeting').hide();
		$('#btnLoadMeetingStats').show();
		$('#btnEndMeeting').show();
	}

	function endMeeting()
	{
		if(currMeetingObj)
		{
			currMeetingObj.executeCommand('hangup');
			currMeetingObj.dispose();

			onConferenceExited();
		}

		$('#btnLoadMeeting').show();
		$('#btnLoadMeetingStats').hide();
		$('#btnEndMeeting').hide();
	}

	function exitAndCloseModal()
	{
		endMeeting();
		closeModal();
	}

	function closeModal()
	{
		@if($forPopUp == 1)
			if(window && window.open('','_self'))
			{
				window.open('','_self').close();
			}
		@else
			$('#conductVideoConferenceModal').modal('hide');
			refreshVideoConferenceDashboard();
		@endif
	}
	
</script>

<div class="row content-detail-time-row">			
	<div class="col-md-12">
		<div id="meet">
		</div>
	</div>
</div>
