@extends('ws_template')

@section('int_scripts')
<script>
	var appuserListTable;
	$(document).ready(function(){
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
	    appuserListTable = $('#notifications-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('orgNotificationDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrtoken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'notif_text' }, 
	            { data: '1', name: 'sent_on' },
	            { data: '2', name: 'sent_by' },
	            { data: '3', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 1, "desc" ]],	        
	    });
	});
	function viewAddNotificationDetails(notifId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgNotification.loadAddNotificationModal') !!}",
			dataType: "json",
			data: "notifId="+notifId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			$("#divAddEditNotification").html(data.view);
			$('#divAddNotificationModal').modal();
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});
	}
	function addContentForAllUsers(isSend, isTest)
	{
		if(isSend == 1 && isTest == 0)
		{
			//Bootbox
			bootbox.dialog({
				message: "Do you really want to add this Notification All Relevant Users?",
				title: "Confirm Add Notification",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() {
							saveAddNotificationForAllUsers();
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
		else
		{
			saveAddNotificationForAllUsers();
		}
	}
	function saveAddNotificationForAllUsers()
	{
   		var formData = new FormData($('#frmAddNotification')[0]);
		$('#divAddNotificationModal').modal('hide');
		$.ajax({
			type: "POST",
			url: "{!!  route('orgNotification.addAppuserNotification') !!}",
			dataType: "json",
			data: formData,
			crossDomain: true,
	        contentType: false,
	        processData: false,
		})
		.done(function(data) {
			if(data.msg != "")
			{
				ShowBootboxNotification("Alert", data.msg);
				return;
			}
			if($('#filter_appusers').val() == 1)
			{
				//Go to Filter Appusers
				var id = data.notifId;
				loadFilterAppuserList(id);
			}
			else
			{
	    		appuserListTable.ajax.reload();
			}
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});
	}
	function loadFilterAppuserList(id)
	{		
		$('#filterId').val(id);
		$('#frmFilterAppuserList').attr('action', 'filterListForNotification');
		$('#frmFilterAppuserList').submit();
	}
	function deleteNotificationDetails(notifId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgNotification.checkAvailForDelete') !!}",
			dataType: "json",
			data: "notifId="+notifId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status*1 == 1)
			{
				var isSent = data.isSent;
				var alertMsg = "";
				if(isSent*1 == 1)
					alertMsg = "This notification has already been sent. Do you still want to delete it?";
				else
					alertMsg = "Do you really want to delete this notification?";
					
				bootbox.dialog({
					message: alertMsg,
					title: "Confirm Delete",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {
								$.ajax({
									type: "POST",
									url: "{!!  route('orgNotification.delete') !!}",
									dataType: "json",
									data: "notifId="+notifId+"&usrtoken="+"{{ $usrtoken }}",
									crossDomain: true,
								})
								.done(function(data) {
									if(data.status*1 == 1)
									{
	    								appuserListTable.ajax.reload();
									}
									
									if(data.msg != "")
									{
										ShowBootboxNotification("Alert", data.msg);
									}
								})
								.fail(function(xhr, ajaxOptions, thrownError) {
								})
								.always(function() {
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
			else
			{		
				bootbox.dialog({
					message: data.msg,
					title: "Warning!!",
						buttons: {
						no: {
							label: "OK",
							className: "btn-primary",
							callback: function() {
							}
						}
					}
				});			
			}
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});
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
		        	Notification
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
		        		<button class="btn btn-primary btn-sm" onclick="viewAddNotificationDetails(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add New
				    	</button>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="notifications-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Text</th>
			                    <th>Sent On</th>
			                    <th>Sent By</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				
				{{ Form::open(array('url' => '', 'method' => 'post', 'id' => 'frmFilterAppuserList')) }}
					{!! Form::hidden('notif_id', 0, ['id' => 'filterId']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
<div id="divAddEditNotification"></div>
@endsection