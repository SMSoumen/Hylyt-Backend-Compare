@extends('admin_template')

@section('int_scripts')
<script>
	$(document).ready(function(){
	    $('#frmAddModule').formValidation({
                framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
                fields: {
                	//General Details                	
                  module_name: {
                        validators: {
							notEmpty: {
								message: 'Module Name is required'
							},
							remote: {
								message: 'Duplicate Module Name',
								url: "{!!  url('/validateModuleName') !!}",
								type: 'POST',
								data: function(validator, $field, value) 
								{			
									return {
										moduleId: 0,		
										moduleName: value			
									};
								}
							}
                        }
                    }
                }
            });
	});
</script>
@stop

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	{{ $page_description or null }}
		        </h3>
		    </div>
            <div class="box-body">
				    {!! Form::open(['url' => route('module.store'), 'class' => 'form-horizontal', 'id' => 'frmAddModule']) !!}
				            <div class="form-group {{ $errors->has('module_name') ? 'has-error' : ''}}">
				                {!! Form::label('module_name', 'Module Name', ['class' => 'col-sm-3 control-label']) !!}
				                <div class="col-sm-6">
				                    {!! Form::text('module_name', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'module_name']) !!}
				                    {!! $errors->first('module_name', '<p class="help-block">:message</p>') !!}
				                </div>
				            </div>


				    <div class="form-group">
				        <div class="col-sm-offset-3 col-sm-3">
				            {!! Form::submit('Save', ['class' => 'btn btn-primary form-control']) !!}
				        </div>
				    </div>
				    {!! Form::close() !!}
				</div>
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