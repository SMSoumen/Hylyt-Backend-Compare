@php
	$appEmail = $appOrgDetails['email'];
	$appPhone = $appOrgDetails['phone'];
	$appWebsite = $appOrgDetails['website'];
	$appDescription = $appOrgDetails['description'];
	$isAppPinEnforced = $appOrgDetails['isAppPinEnforced'];
	$isFileSaveShareEnabled = $appOrgDetails['isFileSaveShareEnabled'];
	$isScreenShareEnabled = $appOrgDetails['isScreenShareEnabled'];
	$appAttachmentRetainDays = $appOrgDetails['attachmentRetainDays'];
@endphp
<style>
	.infoRow
	{
		margin-top: 12px !important;
		margin-left: 12px !important;
	}
</style>
<script>
	var frmObj = '#frmSaveAppOrgDetails';
	$(document).ready(function(){
		
	  		$('#is_app_pin_enforced').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
		
	  		$('#is_file_save_share_enabled').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
		
	  		$('#is_screen_share_enabled').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});

	  		$('#retain_all_attachments').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		})
	  		.on('ifChecked', function (e) {
			    showHideRetainPeriodInput(false);
			})
	  		.on('ifUnchecked', function (e) {
			    showHideRetainPeriodInput(true);
			});
  		
	  		@if(isset($isAppPinEnforced) && $isAppPinEnforced == 1)
	        	$('#is_app_pin_enforced').iCheck('check');
	        @endif
  		
	  		@if(isset($isFileSaveShareEnabled) && $isFileSaveShareEnabled == 1)
	        	$('#is_file_save_share_enabled').iCheck('check');
	        @endif
  		
	  		@if(isset($isScreenShareEnabled) && $isScreenShareEnabled == 1)
	        	$('#is_screen_share_enabled').iCheck('check');
	        @endif
  		
	  		@if(isset($appAttachmentRetainDays) && $appAttachmentRetainDays > 0)
        		$('#retain_all_attachments').iCheck('uncheck');
	  		@else
	        	$('#retain_all_attachments').iCheck('check');
	        @endif

	        $('#organization_chat_redirection_id').css('width', '100%');
			$('#organization_chat_redirection_id').select2({
				placeholder: "Select Chat Redirection",
				allowClear: true
			});

	        $('#employee_inactivity_day_count').css('width', '100%');
			$('#employee_inactivity_day_count').select2({
				placeholder: "Select Employee Inactivity Period",
				allowClear: true
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
					appEmail:
					{
						validators:
						{
							notEmpty:
							{
								message: 'Email is required'
							},
							emailAddress:
							{
								message: 'Invalid Email'
							}
						}
					},
					appPhone: 
					{
	                    validators: {
							notEmpty:
							{
								message: 'Phone is required'
							}
	                    }
					},
					appWebsite: 
					{
	                    validators: {
							notEmpty:
							{
								message: 'Website is required'
							},
							uri: {
	                            message: 'Invalid website'
	                        }
	                    }
					},
					appDescription: 
					{
	                    validators: {
							notEmpty:
							{
								message: 'Description is required'
							}
	                    }
					},
					org_attachment_retain_days: 
					{
	                    validators: {
							notEmpty:
							{
								message: 'Attachment Retain Period is required'
							},
							integer: {
                            	message: 'The value is not a valid integer',
	                            thousandsSeparator: '',
	                            decimalSeparator: '.'
	                        },
                        	callback: {
								message: 'The value must be between -1 and ',
								callback: function(value, validator, $field) {
									if(value*1 < -1)
										return false;
									else
										return true;
								}
							}
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
		
		$('#orgInformationModal').modal('hide');
		
		var url = "{{ route('saveOrgAppInformation') }}";
		
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
					if(data.msg != "")
						successToast.push(data.msg);
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}

	function showHideRetainPeriodInput(isShown)
	{
		if(isShown === true)
		{
			$('#divRetainPeriodInput').show();
			$('#org_attachment_retain_days').val(1);
		}
		else
		{
			$('#divRetainPeriodInput').hide();
			$('#org_attachment_retain_days').val(-1);
		}
	}
</script>
<div id="orgInformationModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Organization Details
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveAppOrgDetails']) !!}
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
						<div class="col-md-6">
							<div class="form-group detailsRow">
								<i class="fa fa-envelope"></i>&nbsp;&nbsp;
								{!! Form::label('appEmail', 'Email', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $appEmail }}
								@else
				                	{{ Form::text('appEmail', $appEmail, ['class' => 'form-control', 'id' => 'appEmail', 'autocomplete' => 'off']) }}
								@endif
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group detailsRow">
								<i class="fa fa-phone"></i>&nbsp;&nbsp;
								{!! Form::label('appPhone', 'Phone', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $appPhone }}
								@else
				                	{{ Form::text('appPhone', $appPhone, ['class' => 'form-control', 'id' => 'appPhone', 'autocomplete' => 'off']) }}
								@endif
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group detailsRow">
								<i class="fa fa-chrome"></i>&nbsp;&nbsp;
								{!! Form::label('appWebsite', 'Website', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $appWebsite }}
								@else
				                	{{ Form::text('appWebsite', $appWebsite, ['class' => 'form-control', 'id' => 'appWebsite', 'autocomplete' => 'off']) }}
								@endif
							</div>
						</div>
						<div class="col-md-6">
							{!! Form::label('organization_chat_redirection_id', 'Chat Redirection', ['class' => 'control-label']) !!}
		                	{{ Form::select('organization_chat_redirection_id', $chatRedirectionArr, $chatRedirectionId, ['class' => 'form-control', 'id' => 'organization_chat_redirection_id']) }}
		                </div>
					</div>
					<div class="row">
						<div class="col-md-6">
							{!! Form::label('employee_inactivity_day_count', 'Employee Inactivity Period', ['class' => 'control-label']) !!}
		                	{{ Form::select('employee_inactivity_day_count', $employeeInactivityDayCountOptionArr, $appEmployeeInactivityDays, ['class' => 'form-control', 'id' => 'employee_inactivity_day_count']) }}
		                </div>
					</div>
					<div class="row">
				        <div class="col-md-6">
				            <div class="form-group" style="margin-top: 30px;">
				            	<label>
				                    {{ Form::checkbox('is_app_pin_enforced', 1, NULL, ['class' => 'form-control', 'id' => 'is_app_pin_enforced']) }}
				                    &nbsp;<i class="fa fa-lock"></i>&nbsp;Enforce App PIN
				          		</label>
				            </div>
				        </div>
				        <div class="col-md-6">
				        </div>
		            </div>
					<div class="row">
				        <div class="col-md-6">
				            <div class="form-group" style="margin-top: 30px;">
				            	<label>
				                    {{ Form::checkbox('is_file_save_share_enabled', 1, NULL, ['class' => 'form-control', 'id' => 'is_file_save_share_enabled']) }}
				                    &nbsp;<i class="fa fa-lock"></i>&nbsp;Enable File Save/Share On App
				          		</label>
				            </div>
				        </div>
				        <div class="col-md-6">
				            <div class="form-group" style="margin-top: 30px;">
				            	<label>
				                    {{ Form::checkbox('is_screen_share_enabled', 1, NULL, ['class' => 'form-control', 'id' => 'is_screen_share_enabled']) }}
				                    &nbsp;<i class="fa fa-lock"></i>&nbsp;Enable Screen Save/Share On App
				          		</label>
				            </div>
				        </div>
		            </div>
					<div class="row">
						<div class="col-md-6" id="divRetainPeriodInput">
							<div class="form-group detailsRow">
								<i class="fa fa-file"></i>&nbsp;&nbsp;
								{!! Form::label('org_attachment_retain_days', 'Attachment Retain Period', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $appAttachmentRetainDays > 0 ? $appAttachmentRetainDays.' Day(s)' : 'Retain All Attachment(s)' }} 
								@else
									<div class="input-group">
				                		{{ Form::text('org_attachment_retain_days', $appAttachmentRetainDays, ['class' => 'form-control', 'id' => 'org_attachment_retain_days', 'autocomplete' => 'off']) }}
				                		<div class="input-group-addon">
										    <span class="input-group-text">Day(s)</span>
									  	</div>
									</div>
								@endif
							</div>
						</div>
						@if(!$isView)
					        <div class="col-md-6">
					            <div class="form-group" style="margin-top: 30px;">
					            	<label>
					                    {{ Form::checkbox('retain_all_attachments', 1, NULL, ['class' => 'form-control', 'id' => 'retain_all_attachments']) }}
					                    &nbsp;Retain All Attachment(s)
					          		</label>
					            </div>
				        	</div>
				        @endif
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group detailsRow">
								<i class="fa fa-info"></i>&nbsp;&nbsp;
								{!! Form::label('appDescription', 'Description', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{!! $appDescription !!}
								@else
				                	{{ Form::textArea('appDescription', $appDescription, ['class' => 'form-control', 'id' => 'appDescription', 'autocomplete' => 'off', 'rows' => '3']) }}
								@endif
							</div>
						</div>
					</div>
				</div>
			<br/>
			@if(!$isView)
				<div class="modal-footer">
					<div class="col-sm-offset-9 col-sm-3">
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
					</div>
				</div>
			@endif
			{!! Form::close() !!}
		</div>
	</div>
</div>