@extends('admin_template')

@section('int_scripts')
<script>
	$(document).ready(function(){
	    $('#modules-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: '{!!  route('moduleDatatable') !!}',
	            method: 'GET'
	        },
	        columns: [
	            { data: '0', name: 'module_id' },
	            { data: '1', name: 'module_name' },
	            { data: '2', name: 'action', sortable: false, searchable: false }
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
			        	<a href="{{ route('module.create') }}" class="btn btn-primary btn-sm">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add New
				    	</a>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="modules-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>ID</th>
			                    <th>Name</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::open(array('url' => route('module.show'), 'id' => 'frmViewModule')) }}
					{!! Form::hidden('moduleId', 0, ['id' => 'viewId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('module.edit'), 'id' => 'frmEditModule')) }}
					{!! Form::hidden('moduleId', 0, ['id' => 'editId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('module.delete'), 'id' => 'frmDeleteModule')) }}
					{!! Form::hidden('moduleId', 0, ['id' => 'deleteId']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endsection