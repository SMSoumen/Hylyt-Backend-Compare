<!-- Modal -->
<div class="modal fade noprint" id="divModifyQuotaModal" tabindex="-1" role="dialog" aria-labelledby="divModifyQuotaModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">Modify Appuser Quota</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyQuota']) !!}
        <div class="modal-body" id="divVoidInvoice">
          <div style="color: red;">
            Are you sure you want to modify space quota for <b>{{ $user->fullname }}</b>?
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12 form-group {{ $errors->has('appuserQuota') ? 'has-error' : ''}}">  
                {!! Form::label('appuserQuota', 'Allotted Space Quota', ['class' => 'control-label']) !!}
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-database"></i></span>
                    {{ Form::text('appuserQuota', ceil($userConstant->attachment_kb_allotted/1024), ['class' => 'form-control', 'id' => 'appuserQuota', 'autocomplete' => 'off']) }}
                    <span class="input-group-addon">MB</span>
                </div>
                {!! $errors->first('appuserQuota', '<p class="help-block">:message</p>') !!}
            </div> 
          </div>         
        </div>
        <div class="modal-footer">
          <button type="submit" name="btnModifyQuota" id="btnModifyQuota" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save Changes</button>
        </div>
        {{ Form::hidden('id', $userConstant->appuser_constant_id) }}
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){
        $('#frmModifyQuota').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details          
                appuserQuota: {
                    validators: {
                        notEmpty: {
                            message: 'Allotted Space is required'
                        },
                        numeric: {
                            message: 'The value is not a number',
                            thousandsSeparator: '',
                            decimalSeparator: ''
                        } ,
                        greaterThan: {
                            value: 1,
                            message: 'The value must be greater than 0'
                        }                     
                    }
                }
            }
        });
    })
    .on('success.form.fv', function(e) {
        // Prevent form submission
        e.preventDefault();
        $('#frmModifyQuota').data('formValidation').resetForm();
        saveUserQuotaDetails();
    });
</script>