@extends('admin_template')

@section('int_scripts')
<script>
	var frmObj = $('#frmChangePassword');
	$(document).ready(function(){
	    $(frmObj).formValidation({
            framework: 'bootstrap',
		    icon: {
		        valid: 'glyphicon glyphicon-ok',
		        invalid: 'glyphicon glyphicon-remove',
		        validating: 'glyphicon glyphicon-refresh'
		    },
            fields: {                	
                curr_pass: {
                    validators: {
						notEmpty: {
							message: 'Current Password is required'
						},
						remote: {
							message: 'Incorrect Password',
							url: "{!!  url('/validateCurrentPassword') !!}",
							type: 'POST',
							data: function(validator, $field, value) 
							{			
								return {		
									currPass: value			
								};
							}
						}
                    }
                },           	
                new_pass: {
                    validators: {
						notEmpty: {
							message: 'New Password is required'
						}
                    }
                },           	
                conf_pass: {
                    validators: {
						notEmpty: {
							message: 'Confirm Password is required'
						},
						identical: {
							field: 'new_pass',
							message: 'New password and its confirm are not the same'
						}
                    }
                }
            }
        })
        .on('success.form.fv', function(e) {
			// Prevent form submission
			e.preventDefault();
            var fv = $(e.target).data('formValidation');
			
			dialog_title = "Confirm Submit";
			dialog_msg = "Are You Sure You Want To Change The Password? <br/> <b>Note:</b> You will be required to login once the password is changed.";
			
			bootbox.dialog({
				message: dialog_msg,
				title: dialog_title,
				buttons: {
					yes: {
						label: "Ok",
						className: "btn-success",
						callback: function() {
							//Submit the form
            				fv.defaultSubmit(); 
						}
					},
					no: {
						label: "Cancel",
						className: "btn-danger",
						callback: function() {
							$(frmObj).data('formValidation').resetForm();							
						}
					}
				}
			});
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
		        	{{ $page_description or null }}
		        </h3>
		    </div>
            <div class="box-body">
			    {!! Form::open(['url' => route('user.updatePassword'), 'class' => 'form-horizontal', 'id' => 'frmChangePassword']) !!}
		            <div class="form-group {{ $errors->has('curr_pass') ? 'has-error' : ''}}">
		                {!! Form::label('curr_pass', 'Current Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('curr_pass', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'curr_pass']) !!}
		                    {!! $errors->first('curr_pass', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
		            <div class="form-group {{ $errors->has('new_pass') ? 'has-error' : ''}}">
		                {!! Form::label('new_pass', 'New Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('new_pass', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'new_pass']) !!}
		                    {!! $errors->first('new_pass', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
		            <div class="form-group {{ $errors->has('conf_pass') ? 'has-error' : ''}}">
		                {!! Form::label('conf_pass', 'Confirm Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('conf_pass', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'conf_pass']) !!}
		                    {!! $errors->first('conf_pass', '<p class="help-block">:message</p>') !!}
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