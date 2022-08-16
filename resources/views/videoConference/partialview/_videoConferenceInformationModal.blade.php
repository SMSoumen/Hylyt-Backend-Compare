<?php
$adminIcon = url(Config::get('app_config.icon_admin'));
$userIcon = url(Config::get('app_config.icon_user'));

// $conferenceId = 0;
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
$isUserModerator = false;
$showConferenceLogs = true; // false;

if(isset($videoConference))
{
	// $conferenceId = $videoConference["conferenceId"];
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
	$isUserModerator = $videoConference['isUserModerator'];
	
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

	if($isUserModerator)
	{
		$showConferenceLogs = true;
	}
}
?>

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

	.label-info-custom
	{
		color: #000 !important;
		font-size: 12px;
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
	var frmObj = '';
	$(document).ready(function(){


	});

	function saveLogsAsContent()
	{
		var urlStr = "{{ route('sysVideoConference.saveConferenceInformationAsUserContent') }}";

		var sessionParams = compileVideoConferenceSessionParams();
		@if(isset($orgKey))
			sessionParams = compileVideoConferenceSessionParams('{{ $orgKey }}');
		@endif

		var n = getActiveUTCOffset();	
		var dataToSend = sessionParams+"&ofs="+n+"&conferenceId="+'{{ $conferenceId }}';

		bootbox.dialog({
			message: "Do you wish to save meeting logs as content?",
			title: "Confirm save as content",
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

<div id="videoConferenceInformationModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
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
				<div class="row">
					<div class="col-md-12" id="divViewGroupName" style="display: block;">
						<div class="form-group">
							<b>{{ $conferenceSubject }}</b>
							<br/>
							{{ $scheduledStartTmStr }} - {{ $scheduledEndTmStr }}

							<!-- @if(isset($actualStartTmStr) && isset($actualEndTmStr))
							{{ $actualStartTmStr }} - {{ $actualEndTmStr }}
							@elseif(isset($scheduledStartTmStr) && isset($scheduledEndTmStr))
							{{ $scheduledStartTmStr }} - {{ $scheduledEndTmStr }}
							@endif -->

						</div>
					</div>
				</div>

				@if($conferenceIsUpcoming == 1)
					<div class="row">
						<div class="col-md-6" align="center">
							<b>Meeting ID:</b> 
							<br/>
							{{ $conferenceCode }}
						</div>
						<div class="col-md-6" align="center">
							<b>Meeting PIN:</b> 
							<br/>
							{{ $conferencePin }}
						</div>
					</div>
					<br/>
				@endif

				<div class="row">
					<div class="col-md-6" align="center">
						<b>Total Invites:</b> 
						<br/>
						{{ $totalInvites }}
					</div>
					@if(isset($actualStartTmStr))
						<div class="col-md-6" align="center">
							<b>Total Attendants:</b> 
							<br/>
							{{ $totalAttendants }}
						</div>
					@endif
				</div>
				<br/>

				<div class="row">
					<div class="col-md-9">
						<div class="form-group">
							{!! Form::label('group_member', 'Participant(s)', ['class' => 'control-label']) !!}
						</div>							
					</div>
					@if($conferenceIsUpcoming == 1 && $isUserModerator)
						<div class="col-md-3" style="cursor: pointer;">
							<div class="row">
								<div class="col-md-6" onclick="viewVideoConferenceShareModal('{{ $conferenceId }}', '{{ $orgKey }}')">
									Invite
								</div>	
								<div class="col-md-6" onclick="viewVideoConferenceShareWithHyLytUsersModal('{{ $conferenceId }}', '{{ $orgKey }}')">
									Share
								</div>		
							</div>					
						</div>
					@endif
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="table">
					        <table id="employees-table" class="table" width="100%">
					            <tbody>
					            	@if(isset($participants) && count($participants) > 0)
					            		@foreach($participants as $participant)
					            			@php
					            			$memName = $participant["participantName"];
					            			$memEmail = $participant["participantEmail"];
					            			$memIsAdmin = $participant["isModerator"];
					            			$memHasAttended = $participant["hasAttended"];
					            			$memEntryTs = $participant["conferenceEntryTs"];
					            			$memExitTs = $participant["conferenceExitTs"];
					            			@endphp
					            			<tr>
							                    <td style="vertical-align: middle !important;" width="10%">
							                    	<img src="{{ $userIcon }}" width="35px"/>
							                    </td>
							                    <td>
							                    	@if($memHasAttended === 0 || !$showConferenceLogs)
							                    		{{ $memName }}
							                    		<br/>
							                    	@endif
							                    	@if($showConferenceLogs)
								                    	@if($memHasAttended === 1)
								                    		<b>{{ $memName }}<i class="fa fa-check"></i></b>
								                    		<br/>
								                    		@if(isset($memEntryTs) && $memEntryTs != "")
								                    			{{ dbToDispDateTimeWithTZ($memEntryTs, $tzStr) }}
								                    		@endif
								                    		@if(isset($memExitTs) && $memExitTs != "")
								                    			{{ " - " . dbToDispDateTimeWithTZ($memExitTs, $tzStr) }}
								                    		@endif
								                    	@elseif($conferenceIsUpcoming == 0)
								                    		Did not Attend
								                    	@endif
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

			<div class="modal-footer content-detail-modal-footer" style="">
				<div class="col-md-8">
                	@if($isUserModerator)
						@if(isset($orgKey))
							{!! Form::button('<i class="fa fa-save"></i>&nbsp;Save Logs as Content', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'saveLogsAsContent()']) !!}
						@endif
					@endif
				</div>
				<div class="col-md-4">

				</div>
			</div>
		</div>
	</div>
</div>
<div id="divDependencies"></div>