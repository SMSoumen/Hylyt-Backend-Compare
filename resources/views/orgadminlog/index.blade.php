@extends('ws_template')

@section('int_scripts')
<script>
	var appuserLogListTable;
	$(document).ready(function(){
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
	    appuserLogListTable = $('#admin-logs-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('orgAdminLogDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrtoken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'created_at' }, 
	            { data: '1', name: 'type_name' },
	            { data: '2', name: 'fullname' },
	            { data: '3', name: 'log_message' },
	        ],
	        "order": [[ 0, "desc" ]],	        
	    });
	});
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	Admin Logs
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="admin-logs-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Log Time</th>
			                    <th>Action Type</th>
			                    <th>Admin Name</th>
			                    <th>Action Log</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
			</div>
		</div>
	</div>
</div>
@endsection