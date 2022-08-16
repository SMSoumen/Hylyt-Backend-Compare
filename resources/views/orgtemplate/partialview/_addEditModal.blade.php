<?php
$templateId = $id;
$templateName = "";
$templateText = "";
if(isset($template))
{
	// $templateId = $template->template_id;
	$templateName = $template->template_name;
	$templateText = $template->template_text;
	$templateText = br2nl($templateText);
}
?>

<script>
	var frmObj = $('#frmSaveTemplate');
	$(document).ready(function(){
		
		$(frmObj).find('#template_text')
		.ckeditor({
			customConfig : 'config.js',
			toolbar : 'simple',
			toolbarGroups: [
				{"name":"basicstyles","groups":["basicstyles"]},
			],
			removeButtons: 'Strike,Anchor,Styles,Specialchar,Superscript,Subscript'
		})
		.editor
		.on('change', function() { 
			$(frmObj).formValidation('revalidateField', 'template_text'); 
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
				template_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Template Name is required'
						},
						remote:
						{
							message: 'Duplicate Template Name',
							url: "{{ route('orgTemplate.validateTemplateName') }}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									templateId: "{{ $templateId }}",
									usrtoken: "{{ $usrtoken }}",
								};
							}
						}
					}
				},
				template_text:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Template Text is required'
						},
                        callback: {
                            message: 'Template Text must be less than 5 characters long',
                            callback: function(value, validator, $field) {
                                if (value === '') {
                                    return true;
                                }
                                // Get the plain text without HTML
                                var div  = $('<div/>').html(value).get(0),
                                    text = div.textContent || div.innerText;

                                return text.length <= 5;
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
            saveTemplateDetails($form);
        });
	});

	function saveTemplateDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: siteurl+'/saveOrgTemplateDetails',
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditTemplateModal').modal('hide');	
			reloadTemplateTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditTemplateModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveTemplate']) !!}
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
						{!! Form::label('template_name', 'Name *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('template_name', $templateName, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					<div class="form-group">
						{!! Form::label('template_text', 'Text *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::textArea('template_text', $templateText, ['class' => 'form-control', 'autocomplete' => 'off', 'rows' => '3']) !!}
						</div>
					</div>
					{!! Form::hidden('tempId', $templateId) !!}
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