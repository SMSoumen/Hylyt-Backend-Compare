@extends('ws_template')

@section('int_scripts')
<script>
	var badgeTableObj;
	$(document).ready(function(){
	    badgeTableObj = $('#badges-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('orgBadgeDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrtoken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'badge_name' },
	            @if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
	            	{ data: '1', name: 'action', sortable: false, searchable: false }
	            @endif
	        ],
	        "order": [[ 0, "asc" ]]
	    });
	});

	function loadQuickAddEditBadgeModal(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgBadge.loadAddEditModal') !!}",
			dataType: "json",
			data:"badgeId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditBadge').html(data.view);
				$('#addEditBadgeModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function deleteBadge(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgBadge.checkAvailForDelete') !!}",
			dataType: "json",
			data:"badgeId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				bootbox.dialog({
					message: "Do you really want to delete this badge?",
					title: "Confirm Delete",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {

								$.ajax({
									type: 'POST',
									url: "{!!  route('orgBadge.delete') !!}",
									dataType: "json",
									data:"badgeId="+id+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadBadgeTable();
									}
								})
								.fail(function(xhr,ajaxOptions, thrownError) {
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
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function reloadBadgeTable()
	{
		badgeTableObj.ajax.reload();
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
		        	Badge List
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="loadQuickAddEditBadgeModal(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add
				    	</a>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="badges-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Name</th>
	            				@if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
			                    	<th>Action</th>
			                    @endif
			                </tr>
			            </thead>
			        </table>
			    </div>
			</div>
		</div>
	</div>
</div>
<div id="divAddEditBadge"></div>
@endsection