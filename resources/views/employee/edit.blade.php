@extends('admin_template')

@section('int_scripts')
<script>
    $(document).ready(function(){

        $("#role_id").select2({
            placeholder: "Select Role",
        });

        $("#department_id").select2({
            placeholder: "Select Department",
        });

        $('#frmEditEmployee').formValidation({
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
                                        user_id: {{ $employee->user_id }},     
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

                    /*username: {         // UserName Validation
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
                                        user_id: {{ $employee->user_id }},     
                                        userName: value         
                                    };
                                }
                            }
                        }
                    },*/

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

                    password: {         // Password Validation
                        validators: {
                            notEmpty: {
                                message: 'Password is required'
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
                {!! Form::open(['url' => route('employee.update'), 'class' => 'form-horizontal', 'id' => 'frmEditEmployee']) !!}
                    <!-- Employee Name   -->
                    <div class="form-group {{ $errors->has('employee_name') ? 'has-error' : ''}}">
                        {!! Form::label('employee_name', 'Employee Name', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::text('employee_name', $employee->employee_name, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                            {!! $errors->first('employee_name', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>

                    

                     <!-- Department    -->
                    <div class="form-group {{ $errors->has('department_name') ? 'has-error' : ''}}">
                            {!! Form::label('department_id', 'Department', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ Form::select('department_id', $departmentArr, $employee->department_id, ['class' => 'form-control', 'id' => 'department_id']) }}
                                {!! $errors->first('department_id', '<p class="help-block">:message</p>') !!}
                            </div>
                        </div>

                    <!-- Role    -->
                    <div class="form-group {{ $errors->has('role_name') ? 'has-error' : ''}}">
                            {!! Form::label('role_id', 'Role', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ Form::select('role_id', $roleArr, $employee->role_id, ['class' => 'form-control', 'id' => 'role_id']) }}
                                {!! $errors->first('role_id', '<p class="help-block">:message</p>') !!}
                            </div>
                        </div>

                    <!-- Contact Number    -->
                    <div class="form-group {{ $errors->has('contact_number') ? 'has-error' : ''}}">
                        {!! Form::label('contact_number', 'Contact Number', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::text('contact_number', $employee->contact_number, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                            {!! $errors->first('contact_number', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>


                    <!-- Email   -->
                    <div class="form-group {{ $errors->has('email') ? 'has-error' : ''}}">
                        {!! Form::label('email', 'Email', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::text('email', $employee->email, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                            {!! $errors->first('email', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>


                    <!-- Address    -->
                    <div class="form-group {{ $errors->has('address') ? 'has-error' : ''}}">
                        {!! Form::label('address', 'Address', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::textarea('address', $employee->address, ['class' => 'form-control', 'autocomplete' => 'off', 'rows' => '5']) !!}
                            {!! $errors->first('address', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>

                    
                    {!! Form::hidden('user_id', $employee->user_id) !!}
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