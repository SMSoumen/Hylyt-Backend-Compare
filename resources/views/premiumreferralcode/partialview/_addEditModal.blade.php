<?php
$codeId = 0;
$referralCodeText = "";
$expDate = NULL;
$allottedDays = 0;
if(isset($referralCode))
{
	$codeId = $id;//referralCode->referral_code_id;
	$referralCodeText = $referralCode->referral_code;
	$expDate = $referralCode->expDtDisp;
	$allottedDays = $referralCode->allotted_days;
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif

<script>
	var frmObj = $('#frmSaveReferralCode');
	$(document).ready(function(){
		
		$(frmObj).formValidation({
			framework: 'bootstrap',
			icon:
			{
				valid: "{!!  Config::get('app_config.validation_success_icon') !!}",
				invalid: "{!!  Config::get('app_config.validation_failure_icon') !!}",
				validating: "{!!  Config::get('app_config.validation_ongoing_icon') !!}"
			},
			fields: 
			{
				//General Details
				referral_code:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Referral Code is required'
						},
						remote:
						{
							message: 'Duplicate Referral Code',
							url: "{{ route('premiumReferralCode.validateReferralCode') }}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									codeId: "{{ $codeId }}",
									usrToken: "{{ $usrtoken }}",
								};
							}
						}
					}
				},
				allotted_days:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Allotted Days is required'
						},
						numeric: 
						{
                            message: 'Allotted Days is not a number'
                        }
	             	}
				}
			}
		})
		.on('success.form.fv', function(e) {
            // Prevent form submission
            e.preventDefault();

            // Some instances you can use are
            var $form = $(e.target),        // The form instance
                fv    = $(e.target).data('formValidation'); // FormValidation instance

            // Do whatever you want here ...
            saveReferralCodeDetails($form);
        });
	});

	function saveReferralCodeDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrToken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/savePremiumReferralCodeDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditReferralCodeModal').modal('hide');	
			reloadReferralCodeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditReferralCodeModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveReferralCode']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}
					</h4>
				</div>
				
				<div class="modal-body">
					<div class="form-group">
						{!! Form::label('referral_code', 'Referral Code *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('referral_code', $referralCodeText, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					<div class="form-group sandbox-container">
            			{!! Form::label('expiration_date', 'Date of Expiration *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
                			{!! Form::text('expiration_date', $expDate, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'expiration_date']) !!}
						</div>
					</div>
					<div class="form-group">
           				{!! Form::label('allotted_days', 'Allotted Days *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('allotted_days', $allottedDays, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('codeId', $codeId) !!}
				</div>
				<div class="modal-footer">
					<div class="col-sm-offset-9 col-sm-3">
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary form-control']) !!}
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>