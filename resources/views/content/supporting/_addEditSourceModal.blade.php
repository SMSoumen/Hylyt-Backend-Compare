<?php
$sourceId = 0;
$sourceName = "";
if(isset($source))
{
	$sourceId = $source->source_id;
	$sourceName = $source->source_name;
}
?>
<script>
	$(document).ready(function(){
		$('#frmSaveSource').formValidation({
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
				name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Source Name is required'
						},
						stringLength: {
	                        message: 'Source Name must be less than 20 characters',
	                        max: 20,
	                    },
	                    regexp: {
	                        regexp: /^[a-zA-Z1-9\s]+$/i,
	                        message: 'Source Name can consist of alphanumeric characters and spaces only'
	                    },
						remote:
						{
							message: 'Duplicate Source Name',
							url: "{!! route('source.validateSourceName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									id: $("#sourceId").val(),
									userId: getCurrentUserId(),
									orgId: getCurrentOrganizationId(),
									loginToken: getCurrentLoginToken(),
								};
							}
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
            saveSourceDetails($form);
        });
	});

	function saveSourceDetails(frmObj)
	{	
		var dataToSend = compileSessionParams();
		var formDataToSend = $(frmObj).serialize();	
		dataToSend = formDataToSend+dataToSend;
		dataToSend += "&id="+$("#sourceId").val();
		
		$.ajax({
			type: "POST",
			url: "{!! route('source.save') !!}",
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);
						
					$("#addEditSourceModal").modal('hide');						
					reloadSourceTable();			
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

<div id="addEditSourceModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveSource']) !!}
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
						<!-- {!! Form::label('name', 'Name', ['class' => 'col-sm-3 control-label']) !!} -->
						<div class="col-sm-12">
						{!! Form::text('name', $sourceName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder'=>'Source Name']) !!}
						</div>
					</div>
					
					{!! Form::hidden('sourceId', $sourceId, ['id' => 'sourceId']) !!}
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