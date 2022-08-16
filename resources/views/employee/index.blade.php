@extends('admin_template')

@section('int_scripts')
<script>
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
	    $('#users-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: '{!!  route('employeeDatatable') !!}',
	            method: 'GET'
	        },
	        columns: [
	            { data: '0', name: 'employee_no' },
	            { data: '1', name: 'employee_name' },
	            { data: '2', name: 'department_name' },
	            { data: '3', name: 'status', sortable: false, searchable: false },
	            { data: '4', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 0, "asc" ]],
			"columnDefs": [
	        	{
	                "targets":  3,
	                "render": function ( data, type, row )
	                {
	                	var dataArr = data.split("_");
	                	var id = dataArr[0];
	                	var isActive = dataArr[1];

						var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
						var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
						var event = 'changeStatus("'+moduleName+'",'+id+','+isActive+');';						
						var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

	                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"'>"
	                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
	                },
	            }           
       		]   
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
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="{{ route('employee.create') }}" class="btn btn-primary btn-sm">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add New
				    	</a>
			    	@endif
			    	
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="users-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Emp ID</th>
			                    <th>Name</th>
			                    <th>Department</th>
			                    <th>Status</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::open(array('url' => route('employee.show'), 'id' => 'frmViewEmployee')) }}
					{!! Form::hidden('user_id', 0, ['id' => 'viewId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('employee.edit'), 'id' => 'frmEditEmployee')) }}
					{!! Form::hidden('user_id', 0, ['id' => 'editId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('employee.delete'), 'id' => 'frmDeleteEmployee')) }}
					{!! Form::hidden('user_id', 0, ['id' => 'deleteId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('employee.changeStatus'), 'id' => 'frmStatusChange')) }}
					{!! Form::hidden('user_id', 0, ['id' => 'statusId']) !!}
					{!! Form::hidden('statusActive', 0, ['id' => 'statusActive']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endsection