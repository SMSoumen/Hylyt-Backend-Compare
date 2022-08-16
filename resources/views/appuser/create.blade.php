@extends('admin_template')

@section('int_scripts')
<script>
	$(document).ready(function(){
		
		$("#department_id").select2({
			placeholder: "Select Department",
		});

		$("#role_id").select2({
			placeholder: "Select Role",
		});

	    $('#frmAddEmployee').formValidation({
                framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
                fields: {
                	//General Details 

                	employee_no: {         // Employee Number Validation
		                validators: {
		                	notEmpty: {
								message: 'Employee Number is required'
							},
							remote: {
								message: 'Duplicate Employee Number',
								url: "{!!  url('/validateEmployeeNo') !!}",
								type: 'POST',
								data: function(validator, $field, value) 
								{			
									return {
										user_id: 0,		
										employeeNo: value			
									};
								}
							}
						}
					},    

                  employee_name: {         // Name Validation
                        validators: {
							notEmpty: {
								message: 'Employee Name is required'
							}
                        }
                    },

                    department_id: { 
                            // Department Name Validation
                        validators: {
                        	callback: {
								message: 'Department Name is required',
								callback: function(value, validator, $field) {
									if(value*1 <= 0)
										return false;
									else
										return true;
								}
							}
                        }
                    },

                    role_id: {         // Role Validation
                        validators: {
							callback: {
								message: 'Role is required',
								callback: function(value, validator, $field) {
									if(value*1 <= 0)
										return false;
									else
										return true;
								}
							}
                        }
                    },

                    username: {         // UserName Validation
                        validators: {
							notEmpty: {
								message: 'User Name is required'
							},
							remote: {
								message: 'Duplicate User Name',
								url: "{!!  url('/validateUserName') !!}",
								type: 'POST',
								data: function(validator, $field, value) 
								{			
									return {
										user_id: 0,		
										userName: value			
									};
								}
							}
                        }
                    },

                    email: {              // Email Validation
		                validators: {
		                	notEmpty: {
								message: 'Email Address is required'
							},
		                    emailAddress: {
		                        message: 'The value is not a valid email address'
		                    }
		                }
            		},

            		contact_number: {     // Phone Number Validation
	                    validators: {
	                        numeric: {
	                            message: 'Enter the valid Number',
	                            
	                        }
	                    }
                	},

                	password: {         // password Validation
                        validators: {
							notEmpty: {
								message: 'Password is required'
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
			    {!! Form::open(['url' => route('employee.store'), 'class' => 'form-horizontal', 'id' => 'frmAddEmployee']) !!}
		    		<!-- Employee Number    -->
		            <div class="form-group {{ $errors->has('employee_no') ? 'has-error' : ''}}">
		                {!! Form::label('employee_no', 'Employee Number', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('employee_no', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'employee_no']) !!}		             
		                </div>
		            </div>

		            <!-- Employee Name    -->
		            <div class="form-group {{ $errors->has('employee_name') ? 'has-error' : ''}}">
		                {!! Form::label('employee_name', 'Employee Name', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('employee_name', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'employee_name']) !!}		             
		                </div>
		            </div>

		             <!-- User Name    -->
		            <div class="form-group {{ $errors->has('user_name') ? 'has-error' : ''}}">
		                {!! Form::label('username', 'User Name', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('username', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'username']) !!}		             
		                </div>
		            </div>

		             <!-- Password    -->
		            <div class="form-group {{ $errors->has('password') ? 'has-error' : ''}}">
		                {!! Form::label('password', 'Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('password', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'password']) !!}		             
		                </div>
		            </div>

		             <!-- Department_id   -->
		            <div class="form-group {{ $errors->has('department_id') ? 'has-error' : ''}}">
		                {!! Form::label('department_id', 'Department', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {{ Form::select('department_id', $departmentArr, null, ['class' => 'form-control select2', 'id' => 'department_id']) }}
		                    {!! $errors->first('department_id', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>

		             <!-- Role_id   -->
		            <div class="form-group {{ $errors->has('role_id') ? 'has-error' : ''}}">
		                {!! Form::label('role_id', 'Role', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {{ Form::select('role_id', $roleArr, null, ['class' => 'form-control', 'id' => 'role_id']) }}
		                    {!! $errors->first('role_id', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>

		             <!-- Contact Number    -->
		            <div class="form-group {{ $errors->has('contact_number') ? 'has-error' : ''}}">
		                {!! Form::label('contact_number', 'Contact Number', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('contact_number', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'contact_number']) !!}		             
		                </div>
		            </div>

		             <!-- email    -->
		            <div class="form-group {{ $errors->has('email') ? 'has-error' : ''}}">
		                {!! Form::label('email', 'Email', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('email', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'email']) !!}		             
		                </div>
		            </div>


		             <!-- Address    -->
		            <div class="form-group">
		                {!! Form::label('address', 'Address', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::textarea('address', null, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'address', 'rows' => '5']) !!}		             
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