@php
@endphp
<style>
	.infoRow
	{
		margin-top: 12px !important;
		margin-left: 12px !important;
	}
</style>
<script>
	var frmObj = '#frmStackAppOrgReferral';
	$(document).ready(function(){

			$.get("https://ipinfo.io", function(response) { 
				clientIPAddr = response.ip;
				$('#ipAddress').val(clientIPAddr);
			}, "json");
			
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
		             org_ref_code: {
	                    validators: {
							notEmpty: {
								message: 'Referral Code is required'
							},
							/* remote: {
								message: 'Invalid Referral Code',
								url: "{!!  url('/validateOrganizationReferralCode') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {
										usrToken: '',			
									};
								}
							} */
	                    }
		             }
				}
			})
			.on('success.form.fv', function(e) {
				
				console.log('is success');
				
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveAppOrgDetails($form);
	        });
	});
	function saveAppOrgDetails(frmObj) 
	{		
		var formDataToSend = $(frmObj).serialize();	
		var dataToSend = formDataToSend + "&usrtoken="+"{{ $usrtoken }}";
		
		$('#orgStackReferralModal').modal('hide');
		
		var url = "{{ route('saveOrgStackReferralCode') }}";
		
		console.log(url);
		
		$.ajax({
			type: "POST",
			url: url,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					
				}
				if(data.msg != "")
            		alert(data.msg);
			}
		});
	}

</script>
<div id="orgStackReferralModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Organization - Stack Referral Code
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmStackAppOrgReferral']) !!}
			<div class="modal-body infoBody">
				@if(isset($orgFieldsArr) && count($orgFieldsArr) > 0)
					<div class="row">
						<div class="col-md-12" align="center">
							@if(isset($orgLogoUrl) && $orgLogoUrl != "")
								<img src="{{ $orgLogoUrl }}" height="50px"/>
							@endif
							<hr/>
						</div>
					</div>
					<div class="row">
						@for($i=0; $i < count($orgFieldsArr); $i++)
							@php
							$orgField = $orgFieldsArr[$i];
							@endphp
							<div class="col-md-6 infoRow">
								{!! Form::label('appWebsite', $orgField['fldTitle'].':', ['class' => 'control-label']) !!}
								<br/>
								<b>{{ $orgField['fldValue'] }}</b>
							</div>
						@endfor
					</div>
				@endif
				<hr/>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group detailsRow">
							<i class="fa fa-envelope"></i>&nbsp;&nbsp;
							{!! Form::label('org_ref_code', 'Referral Code', ['class' => 'control-label']) !!}
			                {{ Form::text('org_ref_code', NULL, ['class' => 'form-control', 'id' => 'org_ref_code', 'autocomplete' => 'off']) }}
						</div>
					</div>
				</div>					
			</div>
			<br/>
			<div class="modal-footer">
				<div class="col-sm-offset-9 col-sm-3">
					<input type="hidden" name="ipAddress" id="ipAddress" value="">
					{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
				</div>
			</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>