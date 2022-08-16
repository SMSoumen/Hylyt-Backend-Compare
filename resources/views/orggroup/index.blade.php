@extends('ws_template')

@section('int_scripts')
<script>
	var moduleName = 'Group';
	var groupTableObj;
	$(document).ready(function(){
	    groupTableObj = $('#groups-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('orgGroupDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrtoken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'name' },
	            { data: '1', name: 'isAutoEnroll', sortable: false, searchable: false },
	            { data: '2', name: 'allotted_mb', sortable: false, searchable: false},
	            { data: '3', name: 'available_mb', sortable: false, searchable: false},
	            { data: '4', name: 'note_count', sortable: false, searchable: false},
	            { data: '5', name: 'status', searchable: false },
	            @if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
	            	{ data: '6', name: 'action', sortable: false, searchable: false }
	            @endif
	        ],
	        "order": [[ 0, "asc" ]],
    			"columnDefs": [
    	        	{
    	                "targets": 5,
    	                "render": function ( data, type, row )
    	                {
    	                	var dataArr = data.split("_");
    	                	var id = dataArr[0];
    	                	var isActive = dataArr[1];
    	                	
    	                	var isDisabled = ""; // "disabled='disabled'";
    	                	@if((isset($admusrtoken) && $admusrtoken != "") || (isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 0))
    							isDisabled = "disabled='disabled'";
    						@endif
    						
    						var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
    						var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
    						var changeStatusFnName = "changeGroupStatus";
    						
    						var event = 'changeStatus("'+moduleName+'","'+id+'",'+isActive+',"'+changeStatusFnName+'");';						
    						var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;
    
    	                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"' "+isDisabled+">"
    	                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
    	                },
    	            }          
           		],
	      });
	});
	
	function hideAllModals()
	{
		$('#groupInfoModal').modal('hide');
		$('#addEditGroupModal').modal('hide');
		$('#modifyGroupRightModal').modal('hide');
	}

	function loadGroupInfoModal(id)
	{
		hideAllModals();
		
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.loadOrgGroupDetailsModal') !!}",
			dataType: "json",
			data:"groupId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditGroup').html(data.view);
				$('#groupInfoModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function loadQuickAddEditGroupModal(id)
	{
		hideAllModals();
		
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.loadAddEditModal') !!}",
			dataType: "json",
			data:"groupId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditGroup').html(data.view);
				$('#addEditGroupModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function loadQuickModifyGroupRightModal(id)
	{
		hideAllModals();
		
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.loadModifyRightModal') !!}",
			dataType: "json",
			data:"groupId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditGroup').html(data.view);
				$('#modifyGroupRightModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function deleteGroup(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.checkAvailForDelete') !!}",
			dataType: "json",
			data:"groupId="+id+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				bootbox.dialog({
					message: "Do you really want to delete this group?",
					title: "Confirm Delete",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {

								$.ajax({
									type: 'POST',
									url: "{!!  route('orgGroup.delete') !!}",
									dataType: "json",
									data:"groupId="+id+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadGroupTable();
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
		.fail(function(xhr, ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
		
	function changeGroupStatus(id, statusActive)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.changeStatus') !!}",
			dataType: "json",
			data:"groupId="+id+"&statusActive="+statusActive+"&usrtoken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			var status = data.status;
			var msg = data.msg;

			showSystemResponse(status, msg);

			if(status*1 == 1)
			{
				reloadGroupTable();
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function reloadGroupTable()
	{
		groupTableObj.ajax.reload();
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
		        	Group List
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="loadQuickAddEditGroupModal(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add
				    	</a>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="groups-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Name</th>
			                    <th>Is Auto Enroll</th>
			                    <th>Allocated Space (In MB)</th>
			                    <th>Available Space (In MB)</th>
			                    <th>Notes Count</th>
	                    		<th>Status</th>
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
<div id="divAddEditGroup"></div>
@endsection