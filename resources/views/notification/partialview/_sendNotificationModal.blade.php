<!-- Modal -->
@php
	$notifId = 0;
   	$notifText = "";
   	$notifImgFileName = "";
   	$isDraft = 1;
   	if(isset($notifDetails))
   	{
		$notifId = $notifDetails->mlm_notification_id;
		$notifText = $notifDetails->notification_text;
		$notifImgFileName = $notifDetails->server_filename;
		$isDraft = $notifDetails->is_draft;
	}
	
	$disabledInput = "";
	if($isDraft == 0)
		$disabledInput = "readonly";
    
    $notifImageUrl = "";
    if(isset($notifDetails->url))
    	$notifImageUrl = $notifDetails->url;
@endphp
<div class="modal fade noprint" id="divSendNotificationModal" tabindex="-1" role="dialog" aria-labelledby="divSendNotificationModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="sendNotificationTitle">
					Appuser Notification
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSendNotification', 'enctype' => "multipart/form-data"]) !!}
			{{ Form::hidden('notification_id', $notifId) }}
			<div class="modal-body" id="divSendNotification">
				@if($isDraft == 1)
					<div >
						<span style="color: red;font-weight: bold;">
							NOTE:
						</span> This notification would be sent to all the logged in users.
					</div>
					<br/>
				@endif
				<div class="row">
					<div class="col-md-12 form-group {{ $errors->has('notification_text') ? 'has-error' : ''}}">
						{!! Form::label('notification_text', 'Notification Text', ['class' => 'control-label']) !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-bullhorn">
								</i>
							</span>
							{{ Form::textArea('notification_text', $notifText, ['class' => 'form-control', 'id' => 'notification_text', 'autocomplete' => 'off', 'rows'=>'2', $disabledInput]) }}
						</div>
						{!! $errors->first('notification_text', '<p class="help-block">:message</p>') !!}
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 form-group {{ $errors->has('notification_img') ? 'has-error' : ''}}">
						{!! Form::label('notification_img', 'Notification Image', ['class' => 'control-label']) !!}
						
						@php $inputVisibility = ""; @endphp
						@if($notifImageUrl != "" || $isDraft == 0)
							@php $inputVisibility = "display:none;"; @endphp							
						@endif
						
						<span id="notifImgFileInputMsg" style="color: red;{!! $inputVisibility !!}">
							<br/>DIMENSIONS: Min - 512x256, Balanced - 1024x512, Max - 2048x1024
						</span>
						
						@if($inputVisibility != "" && $notifImageUrl != "")
							<div class="input-group" id="notifImgFileDisp" class="col-sm-12">
								<div class="col-sm-6">
									{{ HTML::image($notifImageUrl, '', array('height' => '50px')) }}
						            {!! Form::hidden('image_changed', 0, ['id' => 'image_changed']) !!}
							    </div>
							    @if(isset($isDraft) && $isDraft == 1)
								    <div class="col-sm-6">
								         <button class="btn btn-xs btn-danger removeImage"><i class="fa fa-times"></i></button>
								    </div>
								@endif	
							</div>						
						@endif
						
						<div class="input-group" id="notifImgFileInput" style="{!! $inputVisibility !!}">
							<span class="input-group-addon">
								<i class="fa fa-image">
								</i>
							</span>
							{{ Form::file('notification_img', ['class' => 'form-control notification_img', 'id' => 'notification_img', 'placeholder' => 'Select Image']) }}
						</div>
						{!! $errors->first('notification_img', '<p class="help-block">:message</p>') !!}
					</div>
				</div>
			</div>
			@if(isset($isDraft) && $isDraft == 1)
				{{ Form::hidden('is_send', "0", ['id' => 'is_send']) }}
				{{ Form::hidden('is_test', "0", ['id' => 'is_test']) }}
				{{ Form::hidden('filter_appusers', "0", ['id' => 'filter_appusers']) }}
				<div class="modal-footer">
					<button type="submit" name="btnSaveDraft" id="btnSaveDraft" class="btn btn-primary btn-info">
						<i class="fa fa-save fa-lg">
						</i>&nbsp;&nbsp;Save As Draft
					</button>
					<button type="submit" name="btnNotifTestSend" id="btnNotifTestSend" class="btn btn-primary btn-warning">
						<i class="fa fa-flask fa-lg">
						</i>&nbsp;&nbsp;Test
					</button>
					<button type="submit" name="btnNotifSend" id="btnNotifSend" class="btn btn-primary btn-success">
						<i class="fa fa-send fa-lg">
						</i>&nbsp;&nbsp;Send To All
					</button>
					<button type="submit" name="btnFilterAppuser" id="btnFilterAppuser" class="btn btn-primary btn-purple">
						<i class="fa fa-filter fa-lg">
						</i>&nbsp;&nbsp;Selective Send
					</button>
				</div>
			@endif
			{!! Form::close() !!}
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
	$(document).ready(function()
	{
		$(".notification_img").fileinput(
		{
			'showUpload':false,
			'showPreview':true,
			'previewFileType':'any'
		}).on('fileclear', function(event)
		{
			$('#frmSendNotification').formValidation('revalidateField', 'notification_img');
		});
		
		$(".removeImage").click(function() {
			$('#image_changed').val(1);
			$('#notifImgFileInput').show();
			$('#notifImgFileInputMsg').show();
			$('#notifImgFileDisp').hide();
		});
		
		$('#frmSendNotification').formValidation(
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
				//General Details
				notification_text:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Notification Text is required'
						},
					}
				},
				notification_img:
				{
					validators:
					{
						file:
						{
							extension: 'jpeg,jpg,png',
							type: 'image/jpeg,image/png',
							maxSize: "<?php echo Config::get('app_config.mlm_notif_image_filesize_limit'); ?>",
							message: 'The selected file is not valid'
						}
					}
				}
			}
		})
		.on('success.form.fv', function(e)
		{
			// Prevent form submission
			e.preventDefault();

			var $form = $(e.target);
			var $button = $form.data('formValidation').getSubmitButton();

			$form.data('formValidation').resetForm();

			var btnId = $button.attr('id');

			var isSend = 0, isTest = 0, filterAppusers = 0;
			
			if(btnId == "btnFilterAppuser")
			{
				filterAppusers = 1;
			}
			else if(btnId == "btnNotifSend")
			{
				isSend = 1;
			}
			else if(btnId == "btnNotifTestSend")
			{
				isSend = 1;
				isTest = 1;
			}
			$('#is_send').val(isSend);
			$('#is_test').val(isTest);
			$('#filter_appusers').val(filterAppusers);
			
			saveNotificationDetails(isSend, isTest);
		});
	});
</script>