<?php
$adminIcon = url(Config::get('app_config.icon_admin'));
$userIcon = url(Config::get('app_config.icon_user'));

$conferenceId = "";
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

if(isset($videoConference) && count($videoConference) > 0)
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

<script>
	var primaryParticipantArr = [];
	var filteredParticipantArr = [];
	var frmObj = '#frmShareVideoConference';
	$(document).ready(function(){

			$('.cbParticipant').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		});

			$('.cbDisableVideo').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		});

			$('.cbMuteAudio').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		});

			$('.cbAnyoneCanJoin').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		});

	  		$('.cbParticipantGenSel').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		})
	  		.on('ifChecked', function(event){
	  			console.log('Select all')
	  			$('.cbParticipant').iCheck('check');
			})
	  		.on('ifUnchecked', function(event){
	  			console.log('UnSelect all')
	  			$('.cbParticipant').iCheck('uncheck');
			});

           	primaryParticipantArr = <?php echo json_encode($applicableParticipantArr, JSON_PRETTY_PRINT) ?>;
           	filteredParticipantArr = primaryParticipantArr;

           	reloadParticipantListView();

           	$("#searchStr").keyup(function(event){
				var searchStr = $("#searchStr").val();
				searchStr = searchStr.trim().toLowerCase();
				filterParticipantList(searchStr);
			});
			

	});

	function filterParticipantList(searchStr)
	{
		filteredParticipantArr = primaryParticipantArr.filter(function (e) {
						return (((e.name).toLowerCase().indexOf(searchStr) >= 0) || ((e.email).toLowerCase().indexOf(searchStr) >= 0));
					});
		reloadParticipantListView();
	}

	function reloadParticipantListView()
	{
		var participantCtrlName = '{{ $participantCtrlName }}';
		$('#divParticipantList').html('');
       	for(i = 0; i < filteredParticipantArr.length; i++)
       	{
       		const participantObj = filteredParticipantArr[i];


        	var indParticipantId = participantObj.id;
        	var indParticipantName = participantObj.name;
        	var indParticipantEmail = participantObj.email;

        	var participantRowHtml = '';
        	participantRowHtml += '<div class="row">';
			participantRowHtml += '<div class="col-md-1">';
			participantRowHtml += '<input type="checkbox" class="cbParticipant" name="' + participantCtrlName + '[]" id="prtId' + indParticipantId + '" value="' + indParticipantId + '"/>';
			participantRowHtml += '</div>';
			participantRowHtml += '<div class="col-md-11 participant-name">';
			participantRowHtml += '<label class="custom-control-label" for="prtId' + indParticipantId + '">';
			participantRowHtml += indParticipantName + ' [' + indParticipantEmail + ']';
			participantRowHtml += '</label>';
			participantRowHtml += '</div>';
			participantRowHtml += '</div>';

			$('#divParticipantList').append(participantRowHtml);
       	}

		$('.cbParticipant').iCheck({
    		checkboxClass: 'icheckbox_flat-yellow',
  		});
	}

	function checkIfParticipantsChecked() 
	{
	    var anyBoxesChecked = false;
	    $(frmObj + ' input[type="checkbox"].cbParticipant').each(function() {
	        if ($(this).is(":checked")) {
	            anyBoxesChecked = true;
	        }
	    });
	    return anyBoxesChecked;
	}

	function validateAndSubmitVideoConferenceShareForm()
	{
		var orgId = getCurrentOrganizationId();

		var anyParticipantsSelected = checkIfParticipantsChecked();
		
		var isValid = true, errorMsg = '';
		
		if(anyParticipantsSelected == false)
		{
			isValid = false;
			errorMsg = 'Please select at-least one participant';
		}
		
		if(isValid)
		{			
			shareVideoConferenceDetails();
		}
		else
		{
			errorToast.push(errorMsg);
		}
	}

	function shareVideoConferenceDetails()
	{	
		var dataToSend = compileSessionParams();
		var orgId = getCurrentOrganizationId();
		
		var urlStr = "{!! route('sysVideoConference.sendConferenceInvitationWithinHyLyt') !!}";
		
		var formDataToSend = $(frmObj).serialize();
		dataToSend = formDataToSend+dataToSend;
		
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

					$("#videoConferenceShareInvitationModal").modal('hide');
					refreshVideoConferenceDashboard();
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

				<div class="row content-detail-time-row">		
					<div class="col-md-12">
						<div class="form-group detailsRow">
							<form id="frmShareVideoConference" name="frmShareVideoConference">
								<input type="hidden" name="conferenceId" value="{{ $conferenceId }}">
								<div class="col-md-12">
			                        <span class="label label-black">
			                        	@if(isset($consGroupId) && $consGroupId > 0)
			                        		Select Group Participant(s)
			                        	@else
			                        		Select Participant(s)
			                        	@endif
			                        </span>
			                        <div id="divSearchParticipant">
										<div class="row">	
		                        			@if(isset($consGroupId) && $consGroupId > 0)			
												<div class="col-md-1">
													<input type="checkbox" class="cbParticipantGenSel" name="cbParticipantGenSel" id="cbParticipantGenSel" />
												</div>
		                        			@endif
											<div class="col-md-11">
												{!! Form::text('searchStr', NULL, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Search', 'id' => 'searchStr']) !!}
												<br/>
											</div>
										</div>
			                        </div>
			                        <div id="divParticipantList">
			                        	
					                </div>
								</div>
							</form>
						</div>
					</div>
				</div>

			</div>

			<div class="modal-footer content-detail-modal-footer" style="">
				<div class="col-md-12">
					@if(isset($orgKey))
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;Send Invite', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'validateAndSubmitVideoConferenceShareForm()']) !!}
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
<div id="divDependencies"></div>