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
                        <button onclick="editThoughtTip('{{ $thoughtTip->thought_tip_id }}');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>
                    @endif
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    {!! Form::label('for_date', 'For Date', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-6">
                        {!! $thoughtTip->for_date !!}
                    </div>
                </div>   
                <div class="row">
                    {!! Form::label('thought_tip_text', 'Thought/Tip Text', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-6">
                        {!! $thoughtTip->thought_tip_text !!}
                    </div>
                </div>                    
                {{ Form::open(array('url' => route('thoughtTip.edit'), 'id' => 'frmEditThoughtTip')) }}
                    {!! Form::hidden('thoughtTipId', 0, ['id' => 'editId']) !!}
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>
@endsection