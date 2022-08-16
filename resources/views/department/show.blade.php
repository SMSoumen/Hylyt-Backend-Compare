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
                        &nbsp;&nbsp;
                        <button onclick="editDepartment('{{ $department->department_id }}');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>
                    @endif
                </h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    {!! Form::label('department_name', 'Department Name', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-6">
                        {{ $department->department_name }}
                    </div>
                </div>                    
                {{ Form::open(array('url' => route('department.edit'), 'id' => 'frmEditDepartment')) }}
                    {!! Form::hidden('deptId', 0, ['id' => 'editId']) !!}
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>
@endsection