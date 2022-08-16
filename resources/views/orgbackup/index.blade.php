@extends('ws_template')

@section('int_scripts')
<script>
	var appuserListTable;
	$(document).ready(function(){
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
	    appuserListTable = $('#backups-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('orgBackupDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrtoken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'created_at' },
	            { data: '1', name: 'created_by' },
	            { data: '2', name: 'backup_desc' },
	            { data: '3', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 0, "desc" ]],	
	        "sDom": "lrtip"        
	    });
	});

	function viewAddBackupDetails(backupId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgBackup.checkAvailForBackup') !!}",
			dataType: "json",
			data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status*1 == 1)
			{
				$.ajax({
					type: "POST",
					url: "{!!  route('orgBackup.loadAddBackupModal') !!}",
					dataType: "json",
					data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
					crossDomain: true,
				})
				.done(function(data) {
					$("#divAddEditBackup").html(data.view);
					$('#divAddBackupModal').modal();
				})
				.fail(function(xhr, ajaxOptions, thrownError) {
				})
				.always(function() {
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

	function downloadBackupDetails(backupId)
	{
		$("#downloadBackupId").val(backupId);
		$("#frmDownloadBackup").submit();

				/*
		$.ajax({
			type: "POST",
			url: "{!!  route('orgBackup.download') !!}",
			dataType: "json",
			data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status*1 == 1)
			{
				window.location.href = data.downloadUrl;
				
				$('#hdnDwnAtt_userId').val(getCurrentUserId());    
				$('#hdnDwnAtt_orgId').val(getCurrentOrganizationId());    
				$('#hdnDwnAtt_loginToken').val(getCurrentLoginToken());    
				$('#hdnDwnAtt_isFolder').val(isFolder);    
				$('#hdnDwnAtt_attId').val(attId);
				$('#hdnDwnAtt_isDownload').val(isDownload);
				$('#hdnDwnAtt_isThumb').val(isThumb);
				$('#frmDownloadAttachment').submit();
				
			}

			showSystemResponse(data.status, data.msg);
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});*/
	}

	function deleteBackupDetails(backupId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgBackup.checkAvailForDelete') !!}",
			dataType: "json",
			data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status*1 == 1)
			{
				var alertMsg = "Do you really want to delete this backup?";
					
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
									url: "{!!  route('orgBackup.delete') !!}",
									dataType: "json",
									data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
									crossDomain: true,
								})
								.done(function(data) {
									if(data.status*1 == 1)
									{
	    								reloadBackupTable();
									}

									showSystemResponse(data.status, data.msg);
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


	function restoreUsingBackupDetails(backupId)
	{
		$.ajax({
			type: "POST",
			url: "{!!  route('orgBackup.checkAvailForRestore') !!}",
			dataType: "json",
			data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
			crossDomain: true,
		})
		.done(function(data) {
			if(data.status*1 == 1)
			{
				$.ajax({
					type: "POST",
					url: "{!!  route('orgBackup.loadConfirmRestoreModal') !!}",
					dataType: "json",
					data: "backupId="+backupId+"&usrtoken="+"{{ $usrtoken }}",
					crossDomain: true,
				})
				.done(function(data) {
					$("#divAddEditBackup").html(data.view);
					$('#divConfirmRestoreModal').modal();
				})
				.fail(function(xhr, ajaxOptions, thrownError) {
				})
				.always(function() {
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

	function reloadBackupTable()
	{
		appuserListTable.ajax.reload();
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
		        	Backup
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
		        		<button class="btn btn-primary btn-sm" onclick="viewAddBackupDetails(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add New
				    	</button>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="backups-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Created On</th>
			                    <th>Created By</th>
			                    <th>Description</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				
			</div>
		</div>
	</div>
</div>
<div id="divAddEditBackup"></div>
{{ Form::open(array('url' => route('orgBackup.download'), 'id' => 'frmDownloadBackup', 'target' => '_blank')) }}
	{!! Form::hidden('backupId', 0, ['id' => 'downloadBackupId']) !!}
	{!! Form::hidden('usrtoken', $usrtoken) !!}
{{ Form::close() }}
@endsection