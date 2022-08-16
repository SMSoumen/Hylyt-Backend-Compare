<?php
$designationId = 0;
$designationName = "";
if(isset($designation))
{
	$designationId = $id;//designation->designation_id;
	$designationName = $designation->designation_name;
}
?>

<script>
	$(document).ready(function(){
		$('#frmSaveDesignation').formValidation({
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
				designation_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Designation Name is required'
						},
						remote:
						{
							message: 'Duplicate Designation Name',
							url: "{!!  url('/validateOrgDesigName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									desigId: "{{ $designationId }}",
									desigName: value,
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
            saveDesignationDetails($form);
        });
	});

	function saveDesignationDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/saveOrgDesigDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditDesignationModal').modal('hide');	
			reloadDesignationTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditDesignationModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveDesignation']) !!}
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
						{!! Form::label('designation_name', 'Designation Name*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('designation_name', $designationName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('desigId', $designationId) !!}
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