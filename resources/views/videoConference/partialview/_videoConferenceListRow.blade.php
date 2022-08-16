<div class="row video-conference-list-row">

	<div class= "col-md-12 col-sm-12 video-conference-meeting-title">
		{!! html_entity_decode($meetingTitle) !!}
		@if($isOpenConference == 1 || ($hasAttendedConference == 1 && $showAttendedIcon == 1))
			<span style="position: absolute;right: 10px;">
				@if($isOpenConference == 1)
					<img src="{{ $anyoneCanJoinIconPath }}" class="video-conference-list-thm-icon" />
				@endif
				@if($hasAttendedConference == 1 && $showAttendedIcon == 1)
					<i class="fa fa-check"></i>
				@endif
			</span>
		@endif
	</div>

	<div class= "col-md-12 col-sm-12 video-conference-meeting-time">
		{{ $conferenceReminderDateStr }}
	</div>

	<div class= "col-md-12 col-sm-12 video-conference-creator-details">
		Created By - <span class= "video-conference-creator-name">{{ $creatorName }}</span>
	</div>

	@if($orgName != "")
		<div class= "col-md-12 col-sm-12 video-conference-creator-details">
			<span class= "video-conference-creator-name">{{ $orgName }}</span>
		</div>
		@if($orgEmpName != "")
			<div class= "col-md-12 col-sm-12 video-conference-creator-details">
				<span class= "video-conference-creator-name">{{ $orgEmpName }}</span>
			</div>
		@endif
	@endif

	<div class= "col-md-12 col-sm-12 video-conference-creator-details">
		
		@if($canViewConferenceInfo == 1)
			{!! Form::button('Info', ['type' => 'button', 'class' => 'btn btn-xs btn-primary', 'onclick' => "viewVideoConferenceInformation('".$conferenceId."', '".$orgKey."')"]) !!}
			@if($conferenceIsUpcoming == 1 && $userIsModerator == 1)
				@if($canEditConference == 1)
					{!! Form::button('Edit', ['type' => 'button', 'class' => 'btn btn-xs btn-purple', 'onclick' => "editVideoConferenceInformation('".$conferenceId."', '".$orgKey."')"]) !!}
				@endif
				{!! Form::button('Share', ['type' => 'button', 'class' => 'btn btn-xs btn-orange', 'onclick' => "viewVideoConferenceInformation('".$conferenceId."', '".$orgKey."')"]) !!}
			@endif
		@endif

		@if($canJoinConference == 1 || $canJoinWaitingRoom == 1)
			{!! Form::button('Join', ['type' => 'button', 'class' => 'btn btn-xs btn-success', 'onclick' => "joinVideoConference('".$orgKey."', '".$conferenceCode."', 0, '".$conferenceId."')"]) !!}
		@endif

		@if($canStartConference == 1)
			{!! Form::button('Start', ['type' => 'button', 'class' => 'btn btn-xs btn-success', 'onclick' => "joinVideoConference('".$orgKey."', '".$conferenceCode."', 1, '".$conferenceId."')"]) !!}
		@endif

		@if($canCancelConference == 1)
			{!! Form::button('Cancel', ['type' => 'button', 'class' => 'btn btn-xs btn-danger', 'onclick' => "checkAndCancelVideoConference('".$conferenceId."', '".$orgKey."')"]) !!}
		@endif
		
	</div>


</div>