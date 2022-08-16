@extends('ws_template')

<?php
$assetBasePath = Config::get('app_config.assetBasePath'); 
?>

@section('int_scripts')
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
@if (isset($intCss))
	@for ($i = 0; $i < count($intCss); $i++)
    	<link href="{{ asset($assetBasePath.$intCss[$i]) }}" rel="stylesheet" type="text/css" />
	@endfor
@endif
<script>
	var frmObj = $('#frmVideoConferenceFilters');
	var conferenceListTable;
	$(document).ready(function(){
		
		$('#selParticipant').css('width', '100%');
		$('#selParticipant').select2({
			placeholder: "Select Participant",
			allowClear: true,
		}).on("change", function () { 
	    	const selParticipantId = $(this).val();

	    	if(selParticipantId !== '')
	    	{
	    		$('#divParticipantRole').show();
	    		$('#divParticipantAttendance').show();
	    	}
	    	else
	    	{
				$('#selParticipantRole').val('').trigger('change');
				$('#selParticipantAttendance').val('').trigger('change');

	    		$('#divParticipantRole').hide();
	    		$('#divParticipantAttendance').hide();
	    	}
		});
		$('#selParticipant').val('').trigger('change');
		
		$('#selParticipantRole').css('width', '100%');
		$('#selParticipantRole').select2({
			placeholder: "Select Participant Role",
			allowClear: true,
		});
		$('#selParticipantRole').val('').trigger('change');
		
		$('#selParticipantAttendance').css('width', '100%');
		$('#selParticipantAttendance').select2({
			placeholder: "Select Participant Attendance",
			allowClear: true,
		});
		$('#selParticipantAttendance').val('').trigger('change');

	    conferenceListTable = $('#video-conferences-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('orgVideoConferenceDatatable') !!}",
	            method: 'POST',
	            // data: function ( d ) {
	            //     d.usrtoken = "{{ $usrtoken }}"
            	// },
	            data: function ( d ) {
	            	d = getVideoConferenceDataForTable(d);
            	}
	        },
	        columns: [
	            { data: '0', name: 'dec_meeting_title', sortable: false, searchable: false },
	            { data: '1', name: 'is_open_conference' },
	            { data: '2', name: 'scheduled_start_ts' },
	            { data: '3', name: 'scheduled_end_ts' },
	            { data: '4', name: 'employee_name' },
	            { data: '5', name: 'created_at' },
	            { data: '6', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 2, "desc" ]],	
	        "sDom": "lrtip",
	        "columnDefs": [
	        	{
	                "targets": 2,
	                "render": function ( data, type, row )
	                {
	                	var formattedDateTime = formatTimestampToDateTime(data);
	                    return formattedDateTime;
	                },
	            },
	        	{
	                "targets": 3,
	                "render": function ( data, type, row )
	                {
	                	var formattedDateTime = formatTimestampToDateTime(data);
	                    return formattedDateTime;
	                },
	            },
	        	{
	                "targets": 5,
	                "render": function ( data, type, row )
	                {
	                	var formattedDateTime = formatDBDateTimeToDispDateTime(data);
	                    return formattedDateTime;
	                },
	            },
	        ]        
	    });

		$('#divParticipantRole').hide();
		$('#divParticipantAttendance').hide();


	    $(frmObj).formValidation({
            framework: 'bootstrap',
		    icon: {
		        valid: 'glyphicon glyphicon-ok',
		        invalid: 'glyphicon glyphicon-remove',
		        validating: 'glyphicon glyphicon-refresh'
		    },
            fields: {                               	               	
             	txtSchFromDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    },
                        callback: {
                            message: 'The value must be less than or equal to Range End',
                            callback: function (value, validator, $field) {
                            	var max = $('#txtSchToDate').val();  

                            	if(max != "")
                            	{
                            		var maxDate = new Date(max.split("-").reverse().join("-"));
                            		var valDate = new Date(value.split("-").reverse().join("-"));
                            		return valDate <= maxDate;
                            	}
                            	return true;
                            }
                        }
                    }
                },           	               	
             	txtSchToDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    }
                    }
                }
            }
        })
        .on('success.form.fv', function(e) {
        	// Prevent form submission
            e.preventDefault();
			$(frmObj).data('formValidation').resetForm();

			reloadConferenceTable();
        });

	});

	function formatTimestampToDateTime(consTs)
	{
		var dateString = '';
		if(consTs != undefined)
		{
			dateString = moment(consTs).format("DD-MM-YYYY HH:mm");
		}
		return dateString;
	}

	function formatDBDateTimeToDispDateTime(consDateTimeStr)
	{
		var dateString = '';
		if(consDateTimeStr != undefined)
		{
			dateString = moment(consDateTimeStr).format("DD-MM-YYYY HH:mm");
		}
		return dateString;
	}

	function formatDateToTimestamp(dateStr)
	{
		var timestamp;
		if(dateStr != undefined)
		{
			timestamp = moment(dateStr, "DD-MM-YYYY").unix();
			timestamp = timestamp * 1000;
		}
		return timestamp;
	}

	function loadViewVideoConferenceDetailsModal(conferenceId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgVideoConference.loadInfoModal') !!}",
			dataType: "json",
			data: "conferenceId="+conferenceId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status > 0)
			{
				$("#divAddEditVideoConference").html(data.view);
				$('#videoConferenceInformationModal').modal();
			}
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});
	}
	
	function getVideoConferenceDataForTable(data)
	{	    
		data.usrtoken = "{{ $usrtoken }}";
		
		var participant = $('#selParticipant').val();
		if (participant != '')
		{
			data.participant = participant;
		}
		
		var participantRole = $('#selParticipantRole').val();
		if (participantRole != '')
		{
			data.participantRole = participantRole;
		}
		
		var participantAttendance = $('#selParticipantAttendance').val();
		if (participantAttendance != '')
		{
			data.participantAttendance = participantAttendance;
		}
		
		var schFromDate = $('#txtSchFromDate').val();
		if (schFromDate != '')
		{
			data.schFromDate = formatDateToTimestamp(schFromDate);
		}
		
		var schToDate = $('#txtSchToDate').val();
		if (schToDate != '')
		{
			data.schToDate = formatDateToTimestamp(schToDate);
		}
		
		return data;
	}
	
	function resetVideoConferenceFilters()
	{
	    dontReloadTable = 1;
		$('#selParticipant').val('').trigger('change');
		$('#selParticipantRole').val('').trigger('change');
		$('#selParticipantAttendance').val('').trigger('change');
		$('#txtSchFromDate').val('').trigger('change');
		$('#txtSchToDate').val('').trigger('change');
		reloadConferenceTable();
	    dontReloadTable = 0;
	}

	function reloadConferenceTable()
	{
		conferenceListTable.ajax.reload();
	}
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	Video Conference
		        </h3>
		    </div>
			{{ Form::open(array('id' => 'frmVideoConferenceFilters', 'class' => 'form-vertical')) }}
				<div class="box-body">
					<div class="row">
						<div class="col-sm-12">
							<section class="panel panel-default">
								<div class="panel-heading">
									Advanced Search Options			
									<button type="button" name="resetFilters" id="resetFilters" class="btn-link pull-right" onclick="resetVideoConferenceFilters();"><i class="fa fa-undo text-danger"></i>&nbsp;<b>Reset</b></button>		
									<button type="button" name="applyFilters" id="applyFilters" class="btn-link pull-right" onclick="reloadConferenceTable();"><i class="fa fa-check text-success"></i>&nbsp;<b>Apply</b></button>
								</div>
								<div class="panel-body">
									<div class="row">
										@php
											$colClass = "col-md-3";
										@endphp
										<div class="{{ $colClass }}">
										  	<label class="control-label" for="sandbox-container-reg">Scheduled Date</label>
											<div class="input-group input-group-sm input-daterange sandbox-container" id="sandbox-container-reg">
												<span class="input-group-addon">From</span>
					                    		{{ Form::text('txtSchFromDate', null, ['class' => 'form-control', 'id' => 'txtSchFromDate', 'autocomplete' => 'off']) }}
											    <span class="input-group-addon">To</span>
					                    		{{ Form::text('txtSchToDate', null, ['class' => 'form-control', 'id' => 'txtSchToDate', 'autocomplete' => 'off']) }}
										    </div>
										</div>	
										<div class="{{ $colClass }}">
							                {!! Form::label('selParticipant', 'Participant', ['class' => 'control-label']) !!}
						                    {{ Form::select('selParticipant', $orgEmployeeList, "", ['class' => 'form-control', 'id' => 'selParticipant']) }}
										</div>
										<div class="{{ $colClass }}" id="divParticipantRole">
							                {!! Form::label('selParticipantRole', 'Participant Role', ['class' => 'control-label']) !!}
						                    {{ Form::select('selParticipantRole', $participantRoleList, "", ['class' => 'form-control', 'id' => 'selParticipantRole']) }}
										</div>
										<div class="{{ $colClass }}" id="divParticipantAttendance">
							                {!! Form::label('selParticipantAttendance', 'Participant Attendance', ['class' => 'control-label']) !!}
						                    {{ Form::select('selParticipantAttendance', $participantAttendanceList, "", ['class' => 'form-control', 'id' => 'selParticipantAttendance']) }}
										</div>
									</div>
								</div>			
							</section>
						</div>
					</div>
				</div>
			{{ Form::close() }}
            <div class="box-body">
			    <div class="table">
			        <table id="video-conferences-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Meeting Title</th>
			                    <th>Conference Type</th>
			                    <th>Scheduled Start Time</th>
			                    <th>Scheduled End Time</th>
			                    <th>Created By</th>
			                    <th>Created On</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				
			</div>
		</div>
	</div>
</div>
<div id="divAddEditVideoConference"></div>
@endsection