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

	#divSelectedParticipantList
	{
        max-height: 100px;
	    overflow-y: auto;
	    overflow-x: hidden;
	    margin: 5px;
	    padding-left: 20px;
	}
</style>
<style>

</style>

<script>
	var primarySelectedParticipantIdArr = [];
	var ongoingSelectedParticipantIdArr = [];
	var primaryParticipantIdArr = [];
	var primaryParticipantArr = [];
	var filteredParticipantArr = [];
	var frmObj = '#frmSaveVideoConference';

	var selectedOrgId = '{{ $selectedOrgId }}';
	var participantCtrlName = '{{ $participantCtrlName }}';
	var isViewInitialized = false;

	$(document).ready(function(){
				
		@if(!$isView)
			
			$('#fromDtTm').datetimepicker({
				date: new Date({{ $startTs }}),
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#startTimeStamp").val(dtTimestamp);
				
				// const calcToDtTm = moment(dtTimestamp).add(10, 'minutes');
				// const calcToDtTmTs = calcToDtTm.unix() * 1000;

				// $('#endTimeStamp').val(calcToDtTmTs);
				// $('#toDtTm').data("DateTimePicker").date(calcToDtTm.format('DD/MM/YYYY HH:mm'));
			});

			$('#startTimeStamp').val({{ $startTs }});
			
			
			$('#toDtTm').datetimepicker({				
				date: new Date({{ $endTs }}),
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#endTimeStamp").val(dtTimestamp);
			});

			$('#endTimeStamp').val({{ $endTs }});

			$('.cbParticipant').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		})
	  		.on('ifChecked', function(event){
			})
	  		.on('ifUnchecked', function(event){
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

	  		@if($isOpenConference == 1)
	  			$('.cbAnyoneCanJoin').iCheck('check');
	  		@endif

	  		$('.cbParticipantGenSel').iCheck({
	    		checkboxClass: 'icheckbox_flat-yellow',
	  		})
	  		.on('ifChecked', function(event){
	  			$('.cbParticipant').iCheck('check');
			})
	  		.on('ifUnchecked', function(event){
	  			$('.cbParticipant').iCheck('uncheck');
			});
		
			$('#sel_profile_id').css('width', '100%');
			$('#sel_profile_id').select2({
				placeholder: "Select Profile",
				allowClear: true,
			});
			$("#sel_profile_id").on("change",function(e)
			{	
				selectedOrgId = $(this).val();

				if(isViewInitialized == true)
				{
					loadParticipantListForSelectedProfile();
				}
			});
						
	  		$('#sel_profile_id').val('{{ $selectedOrgId }}').trigger('change');
	  		

           	primaryParticipantArr = <?php echo json_encode($applicableParticipantArr, JSON_PRETTY_PRINT) ?>;
           	filteredParticipantArr = primaryParticipantArr;

           	primaryParticipantIdArr = [];
	       	for(i = 0; i < primaryParticipantArr.length; i++)
	       	{
       			const participantObj = primaryParticipantArr[i];
        		primaryParticipantIdArr[i] = participantObj.id;
	       	}

           	primarySelectedParticipantIdArr = <?php echo json_encode($selectedParticipantIdArr, JSON_PRETTY_PRINT) ?>;
           	ongoingSelectedParticipantIdArr = <?php echo json_encode($selectedParticipantIdArr, JSON_PRETTY_PRINT) ?>;

           	reloadParticipantListView();

           	$("#searchStr").keyup(function(event){
				var searchStr = $("#searchStr").val();
				searchStr = searchStr.trim().toLowerCase();
				filterParticipantList(searchStr);
			});

			isViewInitialized = true;
			
		@endif

	});

	function loadParticipantListForSelectedProfile()
	{		
		var dataToSend = compileSessionParams(selectedOrgId);
		
		var urlStr = "{!! route('sysVideoConference.loadRelevantConferenceParticipants') !!}";
		
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
					
		           	primaryParticipantArr = JSON.parse(data.applicableParticipantArrJson);
		           	filteredParticipantArr = primaryParticipantArr;

		           	primarySelectedParticipantIdArr = [];
		           	ongoingSelectedParticipantIdArr = [];


		           	participantCtrlName = data.participantCtrlName;

		           	$("#searchStr").val('');

		           	reloadParticipantListView();
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}

	function filterParticipantList(searchStr)
	{
		filteredParticipantArr = primaryParticipantArr.filter(function (e) {
						return (((e.name).toLowerCase().indexOf(searchStr) >= 0) || ((e.email).toLowerCase().indexOf(searchStr) >= 0));
					});
		reloadParticipantListView();
	}

	function reloadParticipantListView()
	{
		$('#divParticipantList').html('');
       	for(i = 0; i < filteredParticipantArr.length; i++)
       	{
       		const participantObj = filteredParticipantArr[i];

        	var indParticipantId = participantObj.id;
        	var indParticipantName = participantObj.name;
        	var indParticipantEmail = participantObj.email;

        	let isSelectedText = "";
        	// if(primarySelectedParticipantIdArr.indexOf(indParticipantId) >= 0)
        	if(ongoingSelectedParticipantIdArr.indexOf(indParticipantId) >= 0)
        	{
        		isSelectedText = "checked='checked'";
        	}

        	// name="' + participantCtrlName + '[]"

        	var participantRowHtml = '';
        	participantRowHtml += '<div class="row">';
			participantRowHtml += '<div class="col-md-1">';
			participantRowHtml += '<input type="checkbox" class="cbParticipant" id="prtId' + indParticipantId + '" value="' + indParticipantId + '" ' + isSelectedText + '/>';
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
  		})
  		.on('ifChecked', function(event){
  			var indParticipantId = event.target.value;
  			if(ongoingSelectedParticipantIdArr.indexOf(indParticipantId) >= 0)
        	{
        	}
        	else
        	{
        		ongoingSelectedParticipantIdArr.push(indParticipantId);
        	}
        	reloadViewForSelectedParticipants();
		})
  		.on('ifUnchecked', function(event){
  			var indParticipantId = event.target.value;
  			if(ongoingSelectedParticipantIdArr.indexOf(indParticipantId) >= 0)
        	{
        		const consIndex = ongoingSelectedParticipantIdArr.indexOf(indParticipantId);
        		ongoingSelectedParticipantIdArr.splice(consIndex, 1);
        	}
        	reloadViewForSelectedParticipants();
		});
	}

	function reloadViewForSelectedParticipants()
	{
		$('#divSelectedParticipantList').html('');
       	for(i = 0; i < ongoingSelectedParticipantIdArr.length; i++)
       	{
       		const selParticipantId = ongoingSelectedParticipantIdArr[i];
       		const selParticipantIndex = primaryParticipantIdArr.indexOf(selParticipantId);

       		if(selParticipantIndex >= 0)
       		{
       			const participantObj = primaryParticipantArr[selParticipantIndex];

       			var indParticipantId = participantObj.id;
	        	var indParticipantName = participantObj.name;
	        	var indParticipantEmail = participantObj.email;

	        	var participantRowHtml = '';
	        	participantRowHtml += '<div class="row">';
				participantRowHtml += '<div class="col-md-11 participant-name">';
				participantRowHtml += '<label class="custom-control-label">';
				participantRowHtml += '<i class="fa fa-user"></i>&nbsp;';
				participantRowHtml += indParticipantName + ' [' + indParticipantEmail + ']';
				participantRowHtml += '</label>';
				participantRowHtml += '</div>';
				participantRowHtml += '<div class="col-md-1">';
				participantRowHtml += '<i class="fa fa-close" style="cursor:pointer;" onclick="removeSelectedListParticipant(' + i + ');">';
				participantRowHtml += '</i>';
				participantRowHtml += '</div>';
				participantRowHtml += '</div>';

				$('#divSelectedParticipantList').append(participantRowHtml);
       		}
       	}
	}

	function removeSelectedListParticipant(consIndex)
	{
		if(consIndex >= 0 && consIndex < ongoingSelectedParticipantIdArr.length)
		{
			ongoingSelectedParticipantIdArr.splice(consIndex, 1);
			reloadViewForSelectedParticipants();
			reloadParticipantListView();
		}
	}

	function checkIfParticipantsChecked() 
	{
	    var anyBoxesChecked = false;
	    // $(frmObj + ' input[type="checkbox"].cbParticipant').each(function() {
	    //     if ($(this).is(":checked")) {
	    //         anyBoxesChecked = true;
	    //     }
	    // });
	    if(ongoingSelectedParticipantIdArr.length > 0)
	    {
	    	anyBoxesChecked = true;
	    }
	    return anyBoxesChecked;
	}

	function submitConferenceSaveForm(btnCode)
	{
        var isScheduled = 0, inPopUp = 0;
        if(btnCode == 'SCHEDULE')
        {
        	isScheduled = 1;
        }
        else if(btnCode == 'STARTPOPUP')
        {
        	inPopUp = 1;
        }

		validateAndSubmitVideoConferenceForm(isScheduled, inPopUp);
	}

	function validateAndSubmitVideoConferenceForm(isScheduled, inPopUp)
	{
		var orgId = getCurrentOrganizationId();

		var currentDtTimestamp = moment().unix();
		currentDtTimestamp *= 1000;

		let isOpenConference = 0;
		$(frmObj + ' input[name="cbAnyoneCanJoin"]').each(function() {
	        if ($(this).is(":checked")) {
	            isOpenConference = 1;
	        }
	    });

	    $('#isOpenConference').val(isOpenConference);

		var meetingTitle = $('input[name="meetingTitle"]').val();
		var startTimeStamp = $('input[name="startTimeStamp"]').val();
		var endTimeStamp = $('input[name="endTimeStamp"]').val();
		var anyParticipantsSelected = checkIfParticipantsChecked();
		
		startTimeStamp = startTimeStamp*1;
		endTimeStamp = endTimeStamp*1;
		
		var isValid = true, errorMsg = '';
		if(!meetingTitle || meetingTitle.trim() == "")
		{
			isValid = false;
			errorMsg = 'Meeting title is required';
		}
		else if(startTimeStamp < currentDtTimestamp && isScheduled == 1)
		{
			isValid = false;
			errorMsg = 'Start time should  be greater than current time';
		}
		else if(endTimeStamp < startTimeStamp)
		{
			isValid = false;
			errorMsg = 'End time should  be greater than start time';
		}
		else if(anyParticipantsSelected == false && isOpenConference == 0)
		{
			isValid = false;
			errorMsg = 'Please select at-least one participant';
		}
		
		if(isValid)
		{			
			saveVideoConferenceDetails(isScheduled, inPopUp);
		}
		else
		{
			errorToast.push(errorMsg);
		}
	}

	function saveVideoConferenceDetails(isScheduled, inPopUp)
	{	
		var dataToSend = compileSessionParams(selectedOrgId);
		
		var urlStr = "{!! route('sysVideoConference.save') !!}";
		
		var formDataToSend = $(frmObj).serialize();
		dataToSend = formDataToSend+dataToSend;
		dataToSend += '&'+participantCtrlName+'='+JSON.stringify(ongoingSelectedParticipantIdArr);

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
					var conferenceId = data.syncId;
					var conferenceCode = data.syncConferenceCode;
					
					if(data.msg != "")
						successToast.push(data.msg);
					else
					{
						if(isScheduled == 1)
							successToast.push('Conference scheduled successfully');
						else
							successToast.push('Conference saved successfully');
					}
						
					
					if(isScheduled == 0)
					{
						conductVideoConferenceModal(selectedOrgId, conferenceId, conferenceCode, cbDisableVideoIsChecked, cbMuteAudioIsChecked, userDisplayName, inPopUp);
					}

					$("#addEditVideoConferenceModal").modal('hide');
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

<div id="addEditVideoConferenceModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveVideoConference']) !!}
				<div class="modal-header content-detail-modal-header">
					<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}	
					</h4>
				</div>
				<div class="modal-body content-detail-modal-body">

					@if($showProfileSelection == 1)
						<div class="row content-detail-time-row">						
							<div class="col-md-12">
								<div class="form-group detailsRow" id="divFromDateTime">
									<div class="col-md-12">
						                @if(!$isView)
											{!! Form::label('sel_profile_id', 'Profile', ['class' => 'control-label']) !!}
								            {{ Form::select('selProfileId', $userProfileArr, NULL, ['class' => 'form-control', 'id' => 'sel_profile_id']) }}
										@else

										@endif
									</div>
								</div>
							</div>
						</div>
					@endif

					<div class="row content-detail-time-row">						
						<div class="col-md-12">
							<div class="form-group detailsRow" id="divFromDateTime">
								<div class="col-md-12">
					                @if(!$isView)
				                        <span class="label label-black">Meeting Title</span>
										{!! Form::text('meetingTitle', $meetingTitle, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Meeting Title']) !!}
									@else

									@endif
								</div>
							</div>
						</div>
					</div>

					<div class="row content-detail-time-row">	
						@if($isScheduled == 1)					
							<div class="col-md-6">
								<div class="form-group detailsRow" id="divFromDateTime">
									<div class="col-md-12">
						                @if(!$isView)
				                        	<span class="label label-black">Start Time</span>
											<div class='input-group date' id='fromDtTm'>
							                    <input type='text' class="form-control" />
							                    <span class="input-group-addon">
							                        <span class="glyphicon glyphicon-calendar"></span>
							                    </span>
			               	 				</div>
										@else
											<span class="modalTextmodalText" id="spanFromDt"></span>
										@endif
									</div>
								</div>
							</div>
							<!-- <div class="col-md-2">
							</div> -->
						@endif
						{!! Form::hidden('startTimeStamp', null, ['id' => 'startTimeStamp']) !!}
						{!! Form::hidden('isOpenConference', 0, ['id' => 'isOpenConference']) !!}
						{!! Form::hidden('isScheduled', $isScheduled, ['id' => 'isScheduled']) !!}
						{!! Form::hidden('conferenceId', $editConferenceId, ['id' => 'conferenceId']) !!}
						<div class="col-md-6">
							<div class="form-group detailsRow" id="divToDateTime">
								<div class="col-md-12">
									@if(!$isView)
			                        	<span class="label label-black">End Time</span>
										<div class='input-group date' id='toDtTm'>
						                    <input type='text' class="form-control" />
						                    <span class="input-group-addon">
						                        <span class="glyphicon glyphicon-calendar"></span>
						                    </span>
		               	 				</div>
										{!! Form::hidden('endTimeStamp', null, ['id' => 'endTimeStamp']) !!}
									@else
										<span class="modalTextmodalText" id="spanToDt"></span>
									@endif
								</div>
							</div>
						</div>
					</div>

					<br/>	

					<div class="row content-detail-time-row form-group">
						<div class="col-md-6">
							<div class="col-md-2">
								<input type="checkbox" class="cbAnyoneCanJoin" name="cbAnyoneCanJoin" id="cbAnyoneCanJoin" value="1" />
							</div>
							<div class="col-md-10 conference-join-options">
								<label class="custom-control-label" for="cbAnyoneCanJoin">Anyone Can Join</label>
                    		</div>
						</div>
					</div>

					@if($isScheduled == 0)	
						<div class="row content-detail-time-row">						
							<div class="col-md-12">
								<div class="form-group detailsRow">
									<div class="col-md-12">
				                        <span class="label label-black">Display Name</span>
										{!! Form::text('userDisplayName', $userDisplayName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Display Name']) !!}
									</div>
									<input type="hidden" name="byAuthentication" value="1">
								</div>
							</div>
						</div>

						<div class="row content-detail-time-row">		
							<div class="col-md-12">
								<div class="form-group detailsRow">
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
					@endif

					<div class="row content-detail-time-row">		
						<div class="col-md-12">
							<div class="form-group detailsRow">
								<div class="col-md-12">
					                @if(!$isView)
				                        <span class="label label-black">
				                        	@if(isset($consGroupId) && $consGroupId > 0)
				                        		Select Group Participant(s)
				                        	@else
				                        		Select Participant(s)
				                        	@endif
				                        </span>
				                        <div id="divSelectedParticipantList"></div>
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
					                        <!-- @foreach($applicableParticipantArr as $applicableParticipantObj)
					                        	@php
					                        	$indParticipantId = $applicableParticipantObj['id'];
					                        	$indParticipantName = $applicableParticipantObj['name'];
					                        	$indParticipantEmail = $applicableParticipantObj['email'];
					                        	@endphp
						                        <div class="row">
						                        	<div class="col-md-1">
														<input type="checkbox" class="cbParticipant" name="{{ $participantCtrlName }}[]" id="prtId{{ $indParticipantId }}" value="{{ $indParticipantId }}"/>
						                        	</div>
						                        	<div class="col-md-11 participant-name">
  														<label class="custom-control-label" for="prtId{{ $indParticipantId }}">
  															{{ $indParticipantName.' ['.$indParticipantEmail.']' }}
  														</label>
						                        	</div>
						                        </div>
						                    @endforeach -->
						                </div>
									@else

									@endif
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
							@if(!$isView)
								<div class="col-md-12" align="right">
									@if($isScheduled == 1)
										{!! Form::button('Schedule', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'submitConferenceSaveForm("SCHEDULE")']) !!}
									@else
										{!! Form::button('Start in Pop Up', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'submitConferenceSaveForm("STARTPOPUP")']) !!}
										&nbsp;
										{!! Form::button('Start in New Window', ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'submitConferenceSaveForm("START")']) !!}
									@endif
								</div>
							@endif
						</div>
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
<div id="divDependencies"></div>