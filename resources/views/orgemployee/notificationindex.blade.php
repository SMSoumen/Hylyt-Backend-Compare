@extends('admin_template')

@section('int_scripts')
<script>
	var appuserListTable;
	var frmObj = $('#frmAppuserFilters');
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
		
	    appuserListTable = $('#notifications-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('notificationDatatable') !!}",
	            method: 'GET',
	        },
	        columns: [
	            { data: '0', name: 'notif_text' },
	            { data: '1', name: 'notif_image' },
	            { data: '2', name: 'sent_on' },
	            { data: '3', name: 'sent_by' },
	            { data: '4', name: 'action', sortable: false, searchable: false }
	        ],
	        order: [[ 2, "desc" ]],
			columnDefs: [
	        	{
	                "targets":  1,
	                "render": function ( data, type, row )
	                {
	                	var imgHtml = '';
	                	if(data != "")
	                	{
	                		//imgHtml = '<img src="' + data + '" height="50"/>';		
	                		imgHtml = "Yes";					
						}
						else
						{
							imgHtml = "No";
						}
	                    return imgHtml;
	                },
	            },           
       		],	        
	    });
	});
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		@if(Session::has('flash_message'))
			<div class="row">
				<div class="col-md-12">
				    <div class="alert alert-info alert-dismissible">
                		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						<i class="icon fa fa-info-circle"></i>&nbsp;&nbsp;
				        <strong> {{ Session::get('flash_message') }} </strong> 
				    </div>
				</div>
			</div>
		@endif
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	{{ $page_description or null }}
		        	<!--&nbsp;&nbsp;
		        	<button class="btn btn-warning btn-sm" onclick="loadSendNotifModal();">
			    		<i class="fa fa-bullhorn"></i>&nbsp;&nbsp;
			    		Send Notification
			    	</button>-->
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
		        		<button class="btn btn-primary btn-sm" onclick="loadSendNotifModal();">
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
			                    <th>Image</th>
			                    <th>Sent On</th>
			                    <th>Sent By</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::open(array('url' => route('notification.show'), 'id' => 'frmViewNotification')) }}
					{!! Form::hidden('notif_id', 0, ['id' => 'viewId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('notification.filterAppuserListForSend'), 'id' => 'frmFilterAppuserList')) }}
					{!! Form::hidden('notif_id', 0, ['id' => 'filterId']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
<div id="divSendNotif"></div>
@endsection