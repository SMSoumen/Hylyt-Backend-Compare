@extends('admin_template')

@section('int_scripts')
<script>
    var frmObj = $('#frmEditThoughtTip');
    $(document).ready(function(){
                
        $('#for_date').on('changeDate', function() { 
            $(frmObj).formValidation('revalidateField', 'for_date');
        });

        $(frmObj).formValidation('addField', 'for_date', {
             validators:
             {
                notEmpty:
                {
                    message: 'Date is required'
                },
                date: {
                    format: 'DD-MM-YYYY',
                    message: 'The value is not a valid date'
                },
                remote:
                {
                    url: "{!!  url('/validateThoughtTipDate') !!}",
                    type: 'POST',
                    delay: {!!  Config::get('app_config.validation_call_delay') !!},
                    data: function(validator, $field, value)
                    {
                        return{
                            thoughtTipId: {{ $thoughtTip->thought_tip_id }},        
                            forDate: value   
                        };
                    },
                    onError: function(e, data) {
                         $(frmObj).formValidation("updateMessage", "for_date", "remote", "Thought/Tip for date already exists");
                    },
                    onSuccess: function(e, data) {
                        console.log("Successss for_date")
                    }
                }
             }
        });
        
        $(frmObj).find('#thought_tip_text')
                .ckeditor({
                    customConfig : 'config.js',
                    toolbar : 'simple',
                    toolbarGroups: [
                        {"name":"basicstyles","groups":["basicstyles"]},
                    ],
                    removeButtons: 'Strike,Anchor,Styles,Specialchar,Superscript,Subscript'
                })
                .editor
                .on('change', function() { 
                    $(frmObj).formValidation('revalidateField', 'thought_tip_text'); 
                });

        $(frmObj).formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details                       
                thought_tip_text:
                {
                    validators:
                    {
                        notEmpty:
                        {
                            message: 'Thought/Tip Text is required'
                        },
                        callback: {
                            message: 'Thought/Tip Text must be less than 5 characters long',
                            callback: function(value, validator, $field) {
                                if (value === '') {
                                    return true;
                                }
                                // Get the plain text without HTML
                                var div  = $('<div/>').html(value).get(0),
                                    text = div.textContent || div.innerText;

                                return text.length <= 5;
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
                {!! Form::open(['url' => route('thoughtTip.update'), 'class' => 'form-horizontal', 'id' => 'frmEditThoughtTip']) !!}

                    <div class="form-group {{ $errors->has('for_date') ? 'has-error' : ''}}">
                        {!! Form::label('for_date', 'For Date', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6 sandbox-container">
                            {!! Form::text('for_date', $thoughtTip->for_date_disp, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'for_date']) !!}
                            {!! $errors->first('for_date', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>
                    <div class="form-group {{ $errors->has('thought_tip_text') ? 'has-error' : ''}}">
                        {!! Form::label('thought_tip_text', 'Thought/Tip Text', ['class' => 'col-sm-3 control-label']) !!}
                        <div class="col-sm-6">
                            {!! Form::textArea('thought_tip_text', $thoughtTip->thought_tip_text, ['class' => 'form-control', 'autocomplete' => 'off', 'rows' => '3']) !!}
                            {!! $errors->first('thought_tip_text', '<p class="help-block">:message</p>') !!}
                        </div>
                    </div>

                    {!! Form::hidden('thoughtTipId', $thoughtTip->thought_tip_id) !!}
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