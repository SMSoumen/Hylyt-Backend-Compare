@extends('admin_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Box -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ $page_description or null }}
                    @if($modulePermissions->module_edit == 1)
                        &nbsp;
                        <button onclick="editEmployee('{{ $employee->user_id }}');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>
                    @endif
                </h3>
            </div>
            <div class="box-body">
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('employee_no', 'Employee Number', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->employee_no != "" ? $employee->employee_no : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                        {!! Form::label('employee_name', 'Employee Name', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {{ $employee->employee_name != "" ? $employee->employee_name : "-" }}
                        </div>
                    </div>  <br>  

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('username', 'User Name', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->username != "" ? $employee->username : "-" }}
                            </div>
                        </div>
                     </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('department_name', 'Department', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->department_name != "" ? $employee->department_name : "-"}}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('role_name', 'Role', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->role_name != "" ? $employee->role_name : "-"}}
                            </div>
                        </div> 
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('email', 'Email', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->email != "" ? $employee->email : "-"}}
                            </div>
                        </div> 
                    </div>

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('contact_number', 'Contact Number', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->contact_number != "" ? $employee->contact_number : "-" }}
                            </div>
                        </div> 
                    </div>

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('address', 'Address', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $employee->address != "" ? $employee->address : "-"}}
                            </div>
                        </div>
                    </div>  


                    {{ Form::open(array('url' => route('employee.edit'), 'id' => 'frmEditEmployee')) }}
                        {!! Form::hidden('user_id', 0, ['id' => 'editId']) !!}
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection