@extends('admin_template')

@section('int_scripts')
<script>
	$(document).ready(function(){

		$("#role_id").select2({
			placeholder: "Select Role",
		});

		var selected_role_id = '<?php echo $roleId; ?>';
		if(selected_role_id*1 > 0)
		{
			loadViewForRoleRights();
		}
	});
</script>
@stop

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
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
			    {!! Form::open(['url' => route('roleright.validateRoleRight'), 'class' => 'form-horizontal', 'id' => 'frmAddRoleRight']) !!}
		            <div class="form-group {{ $errors->has('role_name') ? 'has-error' : ''}}">
		                {!! Form::label('role_id', 'Role Name', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {{ Form::select('role_id', $roleArr, $roleId, ['class' => 'form-control', 'id' => 'role_id', 'onchange' => 'loadViewForRoleRights();']) }}
		                    {!! $errors->first('role_name', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
				    <div class="form-group">
				        <div class="col-sm-offset-3 col-sm-3">
				            {!! Form::submit('Save', ['class' => 'btn btn-primary form-control']) !!}
				        </div>
				    </div>
				    <div id="rightsDiv"></div>
			    {!! Form::close() !!}
			</div>
		</div>
	    @if ($errors->any())
	        <ul class="alert alert-danger">
	            @foreach ($errors->all() as $error)
	                <li>{{ $error }}</li>
	            @endforeach
	        </ul>
	    @endif
	</div>
</div>
@endsection