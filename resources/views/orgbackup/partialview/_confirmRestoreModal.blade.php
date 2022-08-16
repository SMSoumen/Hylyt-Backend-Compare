<!-- Modal -->
@php
	$backupId = 0;
   	$backupDesc = "";
   	if(isset($backupDetails))
   	{
		$backupId = $backupDetails->backup_id;
		$backupDesc = $backupDetails->backup_desc;
	}
@endphp
<div class="modal fade noprint" id="divConfirmRestoreModal" tabindex="-1" role="dialog" aria-labelledby="divConfirmRestoreModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="confirmRestoreTitle">
					Confirm Restore
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmConfirmRestore']) !!}
			{{ Form::hidden('backupId', $backupId) }}
			<div class="modal-body" id="divConfirmRestore">
				<div class="row"> 
					<div class="col-md-12">
		                <b>Note : </b>
		                <br/>
		                If you restore the data, all the current users will not have access to enterprise data.
		                <br/>
		                Also, you as admin, would be required to set the required users as active and send them re-enrollment details.
		                <br/>
		            </div>
				</div>
				<div class="row">
					<div class="col-md-12 form-group {{ $errors->has('backup_desc') ? 'has-error' : ''}}">
						{!! Form::label('backup_desc', 'Backup Description', ['class' => 'control-label']) !!}
						<div>
							{{ $backupDesc }}
						</div>
		                <br/>
					</div>
				</div>
				<div class="row"> 
					<div class="col-md-12 form-group {{ $errors->has('curr_pass') ? 'has-error' : ''}}">
		                {!! Form::label('curr_pass', 'Current Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('curr_pass', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'curr_pass']) !!}
		                    {!! $errors->first('curr_pass', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
				</div>
				<div class="row"> 
					<div class="col-md-12">
		                <b>Other Admin Credentials</b>
		            </div>
	                <br/>	
				</div>
				<div class="row"> 
					<div class="col-md-12 form-group {{ $errors->has('oth_admin_email') ? 'has-error' : ''}}">
		                {!! Form::label('oth_admin_email', 'Email', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::text('oth_admin_email', "", ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'oth_admin_email']) !!}
		                    {!! $errors->first('oth_admin_email', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
				</div>
				<div class="row"> 
					<div class="col-md-12 form-group {{ $errors->has('oth_admin_pass') ? 'has-error' : ''}}">
		                {!! Form::label('oth_admin_pass', 'Password', ['class' => 'col-sm-3 control-label']) !!}
		                <div class="col-sm-6">
		                    {!! Form::password('oth_admin_pass', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'oth_admin_pass']) !!}
		                    {!! $errors->first('oth_admin_pass', '<p class="help-block">:message</p>') !!}
		                </div>
		            </div>
				</div>

			</div>
			{!! Form::hidden('usrtoken', $usrtoken, ['id' => 'usrtoken']) !!}
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary btn-info">
					<i class="fa fa-save fa-lg">
					</i>&nbsp;&nbsp;Save
				</button>
			</div>
			{!! Form::close() !!}
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	$(document).ready(function()
	{		
		
		$('#frmConfirmRestore').formValidation(
			{
				framework: 'bootstrap',
				icon:
				{
					valid: 'glyphicon glyphicon-ok',
					invalid: 'glyphicon glyphicon-remove',
					validating: 'glyphicon glyphicon-refresh'
				},
				fields:
				{     	
	                curr_pass: {
	                    validators: {
							notEmpty: {
								message: 'Current Password is required'
							},
							remote: {
								message: 'Incorrect Password',
								url: "{!!  route('admValidateCurrPassword') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {		
										currPass: value,
										usrtoken: "{{ $usrtoken }}"
									};
								}
							}
	                    }
	                },		
	                oth_admin_email: {
	                    validators: {
							notEmpty: {
								message: 'Other Admin Email is required'
							},
	                        emailAddress: {
	                            message: 'The value is not a valid email address'
	                        },
							remote: {
								message: 'Incorrect Admin Email',
								url: "{!!  route('admValidateOtherAdminEmail') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {
										admEmail: value,
										usrtoken: "{{ $usrtoken }}"
									};
								}
							}
	                    }
	                },
	                oth_admin_pass: {
	                    validators: {
							notEmpty: {
								message: 'Other Admin Password is required'
							},
							remote: {
								message: 'Incorrect Admin Password',
								url: "{!!  route('admValidateOtherAdminPassword') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {		
										admEmail: $('#oth_admin_email').val(),
										admPass: value,
										usrtoken: "{{ $usrtoken }}"
									};
								}
							}
	                    }
	                }
				}
			});
		})
		.on('success.form.fv', function(e)
		{
			// Prevent form submission
			e.preventDefault();

			var $form = $(e.target);
			var $button = $form.data('formValidation').getSubmitButton();
			
			performRestore($form);
		});

		function performRestore(frmObj)
		{				
			bootbox.dialog({
				message: "Are you sure you wish to restore this backup?",
				title: "Confirm Restore",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() {
							var dataToBeSent = $(frmObj).serialize(); // +"&usrtoken="+"{{ $usrtoken }}";

							$('#divConfirmRestoreModal').on('hidden.bs.modal', function () {
								$("#divAddEditBackup").html('');
								showSystemResponse(1, 'Restore in progress');
							});
							
							$('#divConfirmRestoreModal').modal('hide');

							$.ajax({
								type: 'POST',
								crossDomain: true,
								url: "{!!  route('orgBackup.restore') !!}",
								dataType: "json",
								data: dataToBeSent,
							})
							.done(function(data){
								var status = data.status*1;
								var msg = data.msg;

								reloadBackupTable();
								showSystemResponse(status, msg);
							})
							.fail(function(xhr,ajaxOptions, thrownError) {
							})
							.always(function() {
							});	
						}
					},
					no: {
						label: "No",
						className: "btn-primary",
						callback: function() {
						}
					}
				}
			});
							
		}
</script>