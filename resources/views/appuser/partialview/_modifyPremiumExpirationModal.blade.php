<?php
$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
    @for ($i = 0; $i < count($intJs); $i++)
        <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
    @endfor
@endif

<!-- Modal -->
<div class="modal fade noprint" id="divModifyExpirationModal" tabindex="-1" role="dialog" aria-labelledby="divModifyExpirationModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyExpirationTitle">Modify Appuser Expiration</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyExpiration']) !!}
        <div class="modal-body" id="divVoidInvoice">
          <div style="color: red;">
            Are you sure you want to modify space quota for <b>{{ $user->fullname }}</b>?
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12 form-group sandbox-container {{ $errors->has('premiumExpirationDate') ? 'has-error' : ''}}">  
                {!! Form::label('premiumExpirationDate', 'Premium Expiration Date', ['class' => 'control-label']) !!}
                {{ Form::text('premiumExpirationDate', $user->premiumExpirationDtDisp, ['class' => 'form-control', 'id' => 'premiumExpirationDate', 'autocomplete' => 'off']) }}
            </div> 
          </div>         
        </div>
        <div class="modal-footer">
          <button type="submit" name="btnModifyExpiration" id="btnModifyExpiration" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save Changes</button>
        </div>
        {{ Form::hidden('id', $user->appuser_id) }}
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    var frmObj = $('#frmModifyExpiration');
    $(document).ready(function(){

        $('#premiumExpirationDate').on('changeDate', function() { 
            $(frmObj).formValidation('revalidateField', 'premiumExpirationDate');  
        });

        $('#frmModifyExpiration').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details
                premiumExpirationDate:
                {
                    validators:
                    {
                        notEmpty:
                        {
                            message: 'Expiration Date is required'
                        },
                        date: {
                            format: 'DD-MM-YYYY',
                            message: 'Must be a valid date and higher than today',
                            min: new Date()
                        }
                    }
                },
            }
        });
    })
    .on('success.form.fv', function(e) {
        // Prevent form submission
        e.preventDefault();
        $('#frmModifyExpiration').data('formValidation').resetForm();
        saveUserPremiumExpirationDateDetails();
    });
</script>