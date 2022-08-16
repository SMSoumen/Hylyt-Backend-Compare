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
<div class="modal fade noprint" id="divAddBackupModal" tabindex="-1" role="dialog" aria-labelledby="divAddBackupModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="addBackupTitle">
					Request Backup
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmAddBackup']) !!}
			{{ Form::hidden('backup_id', $backupId) }}
			<div class="modal-body" id="divAddBackup">
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
					<div class="col-md-12 form-group {{ $errors->has('backup_desc') ? 'has-error' : ''}}">
						{!! Form::label('backup_desc', 'Backup Description', ['class' => 'control-label']) !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-file-o">
								</i>
							</span>
							{{ Form::text('backup_desc', $backupDesc, ['class' => 'form-control', 'id' => 'backup_desc', 'autocomplete' => 'off']) }}
							{!! $errors->first('backup_desc', '<p class="help-block">:message</p>') !!}
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
		
		$('#frmAddBackup').formValidation(
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
					backup_desc:
					{
						validators:
						{

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
			
			saveBackupDetails($form);
		});

		function saveBackupDetails(frmObj)
		{				
			bootbox.dialog({
				message: "Are you sure you wish to generate a backup?",
				title: "Confirm Backup",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() {
							var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";

							$('#divAddBackupModal').on('hidden.bs.modal', function () {
								$("#divAddEditBackup").html('');
								showSystemResponse(1, 'Backup in progress');
							});
							
							$('#divAddBackupModal').modal('hide');

							$.ajax({
								type: 'POST',
								crossDomain: true,
								url: siteurl+'/saveOrgBackupDetails',
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