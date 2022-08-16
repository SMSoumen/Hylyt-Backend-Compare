@php
	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';
	$headerIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$folderIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconFolderPath');
	$groupIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconGroupPath'));
	$tagIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTagPath'));
	$attachmentIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconAttachmentPath'));
	$isRestrictedIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsRestrictedPath'));
	$contentSenderIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconSenderPath'));
	$anyoneCanJoinIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconVideoConferenceAnyoneCanJoin'));

	$isRunningIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsMarkedPath'));
	$isNotRunningIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsUnMarkedPath'));
	$userIsCreatorIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconIsLockedPath'));

	$typeRIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeRPath'));
	$typeAIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeAPath'));
	$typeCIconPath = $baseIconThemeUrl.(Config::get('app_config_asset.appIconTypeCPath'));
                

    $consTheme = 'thm-blue';

    $noConferencesFound = TRUE;

@endphp

<section class="active-content-list">
	@if(isset($videoConferences) && $videoConferences > 0)

		@php
    	$noConferencesFound = FALSE;
    	@endphp

		@foreach($videoConferences as $conferenceObj)
			
			@php

			$conferenceId = $conferenceObj['conferenceId'];
			$meetingTitle = $conferenceObj['meetingTitle'];
			$conferenceCode = $conferenceObj['conferenceCode'];
			$isOpenConference = $conferenceObj['isOpenConference'];
			$scheduledStartTs = $conferenceObj['scheduledStartTs'];
			$conferenceIsUpcoming = isset($conferenceObj['isUpcoming']) ? $conferenceObj['isUpcoming'] : 0;
			$scheduledEndTs = $conferenceObj['scheduledEndTs'];
			$conferenceIsRunning = $conferenceObj['isRunning'];
			$userIsModerator = $conferenceObj['isModerator'];
			$userIsCreator = $conferenceObj['isCreator'];
			$creatorName = $conferenceObj['creatorName'];
			$creatorEmail = $conferenceObj['creatorEmail'];
			$canJoinConference = $conferenceObj['canJoinConference'];
			$canStartConference = $conferenceObj['canStartConference'];
			$canViewConferenceInfo = $conferenceObj['canViewConferenceInfo'];
			$canCancelConference = $conferenceObj['canCancelConference'];
			$hasAttendedConference = $conferenceObj['hasAttendedConference'];
			$showAttendedIcon = $conferenceObj['showAttendedIcon'];
			$canEditConference = $conferenceObj['canCancelConference'];
			$canJoinWaitingRoom = $conferenceObj['canJoinWaitingRoom'];
			
			$orgKey = $conferenceObj['orgKey'];
			$orgName = $conferenceObj['orgName'];
			$orgEmpName = $conferenceObj['orgEmpName'];
			$orgIconUrl = $conferenceObj['orgIconUrl'];
	
			$conferenceReminderDateStr = dbToDispDateTimeWithTZ($scheduledStartTs, $tzStr).' - '.dbToDispDateTimeWithTZ($scheduledEndTs, $tzStr);

			@endphp

			
			@include('videoConference.partialview._videoConferenceListRow')


		@endforeach

	@endif

	@if($noConferencesFound)
		<div class="noContentsDiv">No Conference(s)</div>
	@endif
</section>

<script>
	$(document).ready(function()
	{

	});
</script>