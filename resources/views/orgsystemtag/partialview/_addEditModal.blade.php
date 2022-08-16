<?php
$systemTagId = 0;
$systemTagName = "";
if(isset($systemTag))
{
	$systemTagId = $id;//systemTag->system_tag_id;
	$systemTagName = $systemTag->tag_name;
}
?>

<script>
	$(document).ready(function(){
		$('#frmSaveSystemTag').formValidation({
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
				tag_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'SystemTag Name is required'
						},
						remote:
						{
							message: 'Duplicate SystemTag Name',
							url: "{!!  url('/validateOrgSystemTagName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									tagId: "{{ $systemTagId }}",
									tagName: value,
									usrtoken: "{{ $usrtoken }}"
								};
							}
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
            saveSystemTagDetails($form);
        });
	});

	function saveSystemTagDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/saveOrgSystemTagDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditSystemTagModal').modal('hide');	

			if(status > 0)
			{
				var savedTagId = data.id;
				sendSystemTagModifiedNotification(savedTagId);
			}

			reloadSystemTagTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function sendSystemTagModifiedNotification(savedTagId)
	{
		var dataToBeSent = "usrtoken="+"{{ $usrtoken }}" + "&tagId=" + savedTagId;
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/sendOrgSystemTagModifiedNotification',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditSystemTagModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveSystemTag']) !!}
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
						{!! Form::label('tag_name', 'Tag Name*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('tag_name', $systemTagName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('tagId', $systemTagId) !!}
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