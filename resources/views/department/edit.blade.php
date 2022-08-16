@extends('admin_template')

@section('int_scripts')
<script>
    $(document).ready(function(){
        $('#frmEditDepartment').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details                   
              department_name: {
                    validators: {
                        notEmpty: {
                            message: 'Department Name is required'
                        },
                        remote: {
                            message: 'Duplicate Department Name',
                            url: "{!!  url('/validateDepartmentName') !!}",
                            type: 'POST',
                            data: function(validator, $field, value) 
                            {           
                                return {
                                    deptId: {{ $department->department_id }},     
                                    deptName: value                              
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
                {!! Form::open(['url' => route('department.update'), 'class' => 'form-horizontal', 'id' => 'frmEditDepartment']) !!}
                    <div class="form-group {{ $errors->has('department_name') ? 'has-error' : ''}}">
                        {!! Form::label('department_name', 'Department Name', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::text('department_name', $department->department_name, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                            {!! $errors->first('department_name', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>

                    {!! Form::hidden('deptId', $department->department_id) !!}
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