<?php
$badgeId = 0;
$badgeName = "";
if(isset($badge))
{
	$badgeId = $id;//badge->badge_id;
	$badgeName = $badge->badge_name;
}
?>

<script>
	$(document).ready(function(){
		$('#frmSaveBadge').formValidation({
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
				badge_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Badge Name is required'
						},
						remote:
						{
							message: 'Duplicate Badge Name',
							url: siteurl+'/validateOrgBadgeName',
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									badgeId: "{{ $badgeId }}",
									badgeName: value,
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
            saveBadgeDetails($form);
        });
	});

	function saveBadgeDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			url: siteurl+'/saveOrgBadgeDetails',
			dataType: "json",
			data: dataToBeSent,
			crossDomain: true,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditBadgeModal').modal('hide');	
			reloadBadgeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>

<div id="addEditBadgeModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveBadge']) !!}
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
						{!! Form::label('badge_name', 'Badge Name *', ['class' => 'col-sm-3 control-label']) !!}
						<div class="col-sm-6">
							{!! Form::text('badge_name', $badgeName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
						</div>
					</div>
					{!! Form::hidden('badgeId', $badgeId) !!}
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