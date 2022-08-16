@extends('admin_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Box -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ $page_description or null }}
                    &nbsp;
                    <button onclick="editModule('{{ $module->module_id }}');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>
                </h3>
            </div>
            <div class="box-body">
                    <div class="form-group">
                        {!! Form::label('module_name', 'Module Name', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {{ $module->module_name }}
                        </div>
                    </div>                    
                    {{ Form::open(array('url' => route('module.edit'), 'id' => 'frmEditModule')) }}
                        {!! Form::hidden('moduleId', 0, ['id' => 'editId']) !!}
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection