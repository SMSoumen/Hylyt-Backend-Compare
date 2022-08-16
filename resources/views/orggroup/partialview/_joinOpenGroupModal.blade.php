<?php
?>
<script>
	$(document).ready(function(){
		$('#frmJoinOpenGroup').formValidation({
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
				openGroupRegCode:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Group Code is required'
						}
					}
				},
			}
		})
		.on('success.form.fv', function(e) {
            // Prevent form submission
            e.preventDefault();

            // Some instances you can use are
            var $form = $(e.target),        // The form instance
                fv    = $(e.target).data('formValidation'); // FormValidation instance

            // Do whatever you want here ...
            saveFolderDetails($form);
        });
	});

	function saveFolderDetails(frmObj)
	{	
		var openGroupRegCode = $('#openGroupRegCode').val();
		openGroupRegCode = sanitizeContentForPlainString(openGroupRegCode);	

		var dataToSend = compileSessionParams();
		dataToSend += "&openGroupRegCode="+openGroupRegCode;

		var submitUrl = "{!! route('group.joinOpenGroupAsMember') !!}";
		
		$.ajax({
			type: "POST",
			url: submitUrl,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);
						
					$("#joinOpenGroupModal").modal('hide');	

					refreshContentList();
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
</script>

<div id="joinOpenGroupModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmJoinOpenGroup']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						Join Group
					</h4>
				</div>
				<div class="modal-body">

					<div class="form-group">
						<!-- <div class="col-sm-12">
						{!! Form::label('name', 'Name', ['class' => 'col-sm-3 control-label']) !!}
						</div> -->
						<div class="col-sm-12">
						{!! Form::text('openGroupRegCode', NULL, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Group Code', 'id' => 'openGroupRegCode']) !!}
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<div class="col-sm-offset-9 col-sm-3">
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Join', ['type' => 'submit', 'class' => 'btn btn-primary form-control']) !!}
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>