<?php
$departmentId = 0;
$departmentName = "";
if(isset($department))
{
	$departmentId = $id;//department->department_id;
	$departmentName = $department->department_name;
}
?>

<script>
	$(document).ready(function(){
		$('#frmSaveDepartment').formValidation({
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
				department_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Department Name is required'
						},
						remote:
						{
							message: 'Duplicate Department Name',
							url: "{!!  url('/validateOrgDeptName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									deptId: "{{ $departmentId }}",
									deptName: value,
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
            saveDepartmentDetails($form);
        });
	});

	function saveDepartmentDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/saveOrgDeptDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditDepartmentModal').modal('hide');	
			reloadDepartmentTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditDepartmentModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveDepartment']) !!}
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
						{!! Form::label('department_name', 'Department Name*', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('department_name', $departmentName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('deptId', $departmentId) !!}
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