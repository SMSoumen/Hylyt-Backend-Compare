@extends('admin_template')

@section('int_scripts')
<script>
	var appuserListTable;
	var frmObj = $('#frmAppuserFilters');
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
	    appuserListTable = $('#deleted-users-table').DataTable({
	    	scrollX: true,
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('deletedAppuserServerDatatable') !!}",
	            method: 'GET',
	            "data": function ( data ) {

				}
	        },
	        columns: [
	            { data: '0', name: 'fullname' },
	            { data: '1', name: 'email' },
	            { data: '2', name: 'contact' },
	            { data: '3', name: 'gender' },
	            { data: '4', name: 'city' },
	            { data: '5', name: 'country' },
	            { data: '6', name: 'verification_status', searchable: false },
	            { data: '7', name: 'del_date', searchable: false },
	            { data: '8', name: 'data_size_mb', searchable: false },
	            { data: '9', name: 'attachment_size_mb', searchable: false },
	            { data: '10', name: 'total_r', searchable: false },
	            { data: '11', name: 'total_a', searchable: false },
	            { data: '12', name: 'total_c', searchable: false },
	            // { data: '12', name: 'action', sortable: false, searchable: false }
	        ],
	        order: [[ 7, "desc" ]],
	        dom: 'lBfrtip',
	        buttons: [
		        {
	                extend: 'excelHtml5',
	                title: 'Deleted_Appuser_List_'+getCurrentDateTimeStr(),
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
	            {
	                extend: 'pdfHtml5',
                	orientation: 'landscape',
	                title: 'Deleted_Appuser_List_'+getCurrentDateTimeStr(),
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
	            {
	                extend: 'csvHtml5'
	            },
	        ]
	    });
        
        function getCurrentDateTimeStr()
        {
			var dtStr = new Date().getDate()+'-'+((new Date().getMonth()*1)+1)+'-'+new Date().getFullYear()+'_'+new Date().getHours()+'-'+new Date().getMinutes();
			return dtStr;
		}
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
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="deleted-users-table" class="table table-bordered" width="100%">
			            <thead>
			                <tr>
			                    <th>Name</th>
			                    <th>Email</th>
			                    <th>Contact</th>
			                    <th>Gender</th>
			                    <th>City</th>
			                    <th>Country</th>
			                    <th>Verification Status</th>
			                    <th>Deleted On</th>
			                    <th>Data MB</th>
			                    <th>Attachment MB</th>
			                	<th>Reminder Count</th>
			                	<th>Archive Count</th>
			                	<th>Calendar Count</th>
			                    <!-- <th>Action</th> -->
			                </tr>
			            </thead>
			        </table>
			    </div>
			</div>
		</div>
	</div>
</div>
@endsection