<?php
$couponId = 0;
$couponName = "";
$couponCodePrefix = "";
$couponName = "";
$couponCount = "";
$description = "";
$couponValidityStartDate = NULL;
$couponValidityEndDate = NULL;
$subscriptionValidityDays = 0;
$allottedSpaceInGb = 0;
$couponMultiUsageCount = 1;
if(isset($coupon))
{
	$couponId = $id;//coupon->premium_coupon_id;
	$couponName = $coupon->coupon_name;
	$couponCodePrefix = $coupon->coupon_code_prefix;
	$couponCount = $coupon->coupon_count;
	$description = $coupon->description;
	$couponCodePrefix = $coupon->coupon_code_prefix;
	$couponValidityStartDate = $coupon->couponValidityStartDtDisp;
	$couponValidityEndDate = $coupon->couponValidityEndDtDisp;
	$subscriptionValidityDays = $coupon->subscription_validity_days;
	$allottedSpaceInGb = $coupon->allotted_space_in_gb;
	$couponMultiUsageCount = $coupon->coupon_multi_usage_count;
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif

<script>
	var frmObj = $('#frmSaveCoupon');
	$(document).ready(function(){

		$('#coupon_validity_start_date').on('changeDate', function() { 
			$(frmObj).formValidation('revalidateField', 'coupon_validity_start_date');  
		});
		
		$('#coupon_validity_end_date').on('changeDate', function() { 
			$(frmObj).formValidation('revalidateField', 'coupon_validity_end_date');  
		});

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
				coupon_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Coupon Name is required'
						},
						stringLength: {
                            min: 3,
                            max: 50,
                            message: 'Coupon Name must be 3-50 characters long'
                        },
                        regexp: {
                            regexp: /^[a-z0-9A-Zs]+$/i,
                            message: 'Coupon Name can consist of alphabetical & numeric characters and spaces only'
                        },
					}
				},
				coupon_code_prefix:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Coupon Code Prefix is required'
						},
						stringLength: {
                            min: 2,
                            max: 2,
                            message: 'Coupon Code Prefix must be 2 characters'
                        },
                        regexp: {
                            regexp: /^[a-z0-9A-Z]+$/i,
                            message: 'The full name can consist of alphabetical & numeric characters only'
                        },
						remote:
						{
							message: 'Duplicate Coupon Code Prefix',
							url: "{{ route('premiumCoupon.validateCouponCodePrefix') }}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									couponId: "{{ $couponId }}",
									usrToken: "{{ $usrtoken }}",
								};
							}
						}
					}
				},
				coupon_validity_start_date:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Start Date is required'
						},
						date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'Invalid date'
	                    }
	             	}
				},
				coupon_validity_end_date:
				{
					validators:
					{
						notEmpty:
						{
							message: 'End Date is required'
						},
						date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'Invalid date'
	                    }
	             	}
				},
				subscription_validity_days:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Allotted Days is required'
						},
                        integer: {
                            message: 'Allotted Days is not an integer',
                            // The default separators
                            thousandsSeparator: '',
                            decimalSeparator: '.'
                        },
                        between: {
                            min: 1,
                            max: 1000,
                            message: 'Allotted Days must be between 1 and 1000'
                        }
	             	}
				},
				allotted_space_in_gb:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Allotted Space is required'
						},
                        numeric: {
                            message: 'Allotted Space is not a number',
                            // The default separators
                            thousandsSeparator: '',
                            decimalSeparator: '.'
                        },
                        between: {
                            min: 0.5,
                            max: 100,
                            message: 'Allotted Space must be between 0.5 and 100'
                        }
	             	}
				},
				coupon_count:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Coupon Count is required'
						},
                        integer: {
                            message: 'Coupon Count is not an integer',
                            // The default separators
                            thousandsSeparator: '',
                            decimalSeparator: '.'
                        },
                        between: {
                            min: 1,
                            max: 100000,
                            message: 'Coupon Count must be between 1 and 100000'
                        }
	             	}
				},
				coupon_multi_usage_count:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Usage Count is required'
						},
                        integer: {
                            message: 'Usage Count is not an integer',
                            // The default separators
                            thousandsSeparator: '',
                            decimalSeparator: '.'
                        },
                        between: {
                            min: 1,
                            max: 10,
                            message: 'Usage Count must be between 1 and 10'
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
            saveCouponDetails($form);
        });
	});

	function saveCouponDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrToken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/savePremiumCouponDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditCouponModal').modal('hide');	
			reloadCouponTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditCouponModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveCoupon']) !!}
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
						{!! Form::label('coupon_name', 'Coupon Name*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('coupon_name', $couponName, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					<div class="form-group">
						{!! Form::label('coupon_code_prefix', 'Coupon Code Prefix*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('coupon_code_prefix', $couponCodePrefix, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					<div class="form-group">
						{!! Form::label('coupon_count', 'Coupon Count*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('coupon_count', $couponCount, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>

					<div class="form-group sandbox-container">
            			{!! Form::label('coupon_validity_start_date', 'Coupon Validity Start Date*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
                			{!! Form::text('coupon_validity_start_date', $couponValidityStartDate, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'coupon_validity_start_date']) !!}
						</div>
					</div>

					<div class="form-group sandbox-container">
            			{!! Form::label('coupon_validity_end_date', 'Coupon Validity End Date*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
                			{!! Form::text('coupon_validity_end_date', $couponValidityEndDate, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'coupon_validity_end_date']) !!}
						</div>
					</div>

					<div class="form-group">
           				{!! Form::label('subscription_validity_days', 'Allotted Days *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('subscription_validity_days', $subscriptionValidityDays, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>

					<div class="form-group">
           				{!! Form::label('allotted_space_in_gb', 'Allotted Space (in GBs)*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('allotted_space_in_gb', $allottedSpaceInGb, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>

					<div class="form-group">
						{!! Form::label('coupon_multi_usage_count', 'Usage Count*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('coupon_multi_usage_count', $couponMultiUsageCount, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>

					<div class="form-group">
						{!! Form::label('description', 'Description', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('description', $description, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('couponId', $couponId) !!}
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