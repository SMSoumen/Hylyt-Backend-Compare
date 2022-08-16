@extends('admin_template')

@section('int_scripts')
<script>
	var appuserListTable;
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
	    appuserListTable = $('#notifications-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('contentAdditionDatatable') !!}",
	            method: 'GET',
	        },
	        columns: [
	            { data: '0', name: 'content_title' }, 
	            { data: '1', name: 'content_text' }, 
	            { data: '2', name: 'sent_on', sortable: false, searchable: false },
	            { data: '3', name: 'sent_by' },
	            { data: '4', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 1, "desc" ]],	        
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
		        		<button class="btn btn-primary btn-sm" onclick="loadAddContentModal();">
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
			                    <th>Title</th>
			                    <th>Text</th>
			                    <th>Sent On</th>
			                    <th>Sent By</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::open(array('url' => route('contentAddition.show'), 'id' => 'frmViewContentAddition')) }}
					{!! Form::hidden('cont_add_id', 0, ['id' => 'viewId']) !!}
				{{ Form::close() }}
				
				{{ Form::open(array('url' => route('contentAddition.filterAppuserListForSend'), 'id' => 'frmFilterAppuserList')) }}
					{!! Form::hidden('cont_add_id', 0, ['id' => 'filterId']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
<div id="divAddContent"></div>
@endsection