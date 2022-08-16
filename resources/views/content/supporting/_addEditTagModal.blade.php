<?php
$tagId = 0;
$tagName = "";
if(isset($tag))
{
	$tagId = $tag->tag_id;
	$tagName = $tag->tag_name;
}
?>
<script>
	$(document).ready(function(){
		$('#frmSaveTag').formValidation({
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
							message: 'Tag Name is required'
						},
						stringLength: {
	                        message: 'Tag Name must be less than 20 characters',
	                        max: 20,
	                    },
	                    regexp: {
	                        regexp: /^[a-zA-Z0-9\s]+$/i,
	                        message: 'Tag Name can consist of alphanumeric characters and spaces only'
	                    },
						remote:
						{
							message: 'Duplicate Tag Name',
							url: "{!! route('tag.validateTagName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									id: $("#tagId").val(),
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
            saveTagDetails($form);
        });
	});

	function saveTagDetails(frmObj)
	{	
		var dataToSend = compileSessionParams();
		var formDataToSend = $(frmObj).serialize();	
		dataToSend = formDataToSend+dataToSend;
		dataToSend += "&id="+$("#tagId").val();
		
		$.ajax({
			type: "POST",
			url: "{!! route('tag.save') !!}",
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				if(data.status*1 > 0)
				{
					if(data.msg != "")
						successToast.push(data.msg);
						
					$("#addEditTagModal").modal('hide');						
					reloadTagTable();			
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

<div id="addEditTagModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveTag']) !!}
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
						{!! Form::text('name', $tagName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder'=>'Tag Name']) !!}
						</div>
					</div>
					
					{!! Form::hidden('tagId', $tagId, ['id' => 'tagId']) !!}
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