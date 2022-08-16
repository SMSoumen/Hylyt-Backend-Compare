<!-- Modal -->
@php
	$addContId = 0;
   	$contText = "";
   	$isDraft = 1;
   	if(isset($addContentDetails))
   	{
		$addContId = $addContentDetails->mlm_content_addition_id;
		$contText = $addContentDetails->content_text;
		$isDraft = $addContentDetails->is_draft;
	}
	
	$disabledInput = "";
	if($isDraft == 0)
		$disabledInput = "readonly";
    
    $contImageUrl = "";
    if(isset($addContentDetails->url))
    	$contImageUrl = $addContentDetails->url;
@endphp
<div class="modal fade noprint" id="divAddContentModal" tabindex="-1" role="dialog" aria-labelledby="divAddContentModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="addContentTitle">
					Appuser Content
				</h4>
			</div>
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmAddContent', 'enctype' => "multipart/form-data"]) !!}
			{{ Form::hidden('add_cont_id', $addContId) }}
			<div class="modal-body" id="divAddContent">
				@if($isDraft == 1)
					<div >
						<span style="color: red;font-weight: bold;">
							NOTE:
						</span> This content would be added for all the verified users.
					</div>
					<br/>
				@endif
				<div class="row">
					<div class="col-md-12 form-group {{ $errors->has('content_text') ? 'has-error' : ''}}">
						{!! Form::label('content_text', 'Content Text', ['class' => 'control-label']) !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-bullhorn">
								</i>
							</span>
							{{ Form::textArea('content_text', $contText, ['class' => 'form-control', 'id' => 'content_text', 'autocomplete' => 'off', 'rows'=>'2', $disabledInput]) }}
						</div>
						{!! $errors->first('content_text', '
						<p class="help-block">
							:message
						</p>') !!}
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 form-group {{ $errors->has('content_file') ? 'has-error' : ''}}">
						{!! Form::label('content_file', 'Content Attachment', ['class' => 'control-label']) !!}
						
						@php $inputVisibility = ""; @endphp
						@if($contImageUrl != "" || $isDraft == 0)
							@php $inputVisibility = "display:none;"; @endphp							
						@endif
						
						<!--<span id="notifImgFileInputMsg" style="color: red;{!! $inputVisibility !!}">
							<br/>DIMENSIONS: Min - 512x256, Balanced - 1024x512, Max - 2048x1024
						</span>-->
						
						@if($inputVisibility != "" && $contImageUrl != "")
							<div class="input-group" id="notifImgFileDisp" class="col-sm-12">
								<div class="col-sm-6">
									{{ HTML::image($contImageUrl, '', array('height' => '50px')) }}
						            {!! Form::hidden('image_changed', 0, ['id' => 'image_changed']) !!}
							    </div>
							    @if(isset($isDraft) && $isDraft == 1)
								    <div class="col-sm-6">
								         <button class="btn btn-xs btn-danger removeImage"><i class="fa fa-times"></i></button>
								    </div>
								@endif	
							</div>						
						@endif
						
						<div class="input-group" id="contentFileInput" style="{!! $inputVisibility !!}">
							<span class="input-group-addon">
								<i class="fa fa-file">
								</i>
							</span>
							{{ Form::file('content_file', ['class' => 'form-control content_file', 'id' => 'content_file', 'placeholder' => 'Select File']) }}
						</div>
						{!! $errors->first('content_file', '<p class="help-block">:message</p>') !!}
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
					<button type="submit" name="btnTestAddContent" id="btnTestAddContent" class="btn btn-primary btn-warning">
						<i class="fa fa-flask fa-lg">
						</i>&nbsp;&nbsp;Test
					</button>
					<button type="submit" name="btnAddContent" id="btnAddContent" class="btn btn-primary btn-success">
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
		@if($inputVisibility == "")
			registerAttachmentForValidation();
		@endif
		
		$(".removeImage").click(function() {
			$('#image_changed').val(1);
			$('#contentFileInput').show();
			$('#notifImgFileDisp').hide();
			registerAttachmentForValidation();
		});
		$('#frmAddContent').formValidation(
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
				content_text:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Content Text is required'
						},
					}
				},
				content_file:
				{
					validators:
					{
						file:
						{
		                    type: '{{ Config::get("app_config.fv_attachment_type_normal") }}',
							maxSize: "<?php echo Config::get('app_config.org_mlm_add_content_file_filesize_limit'); ?>",
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
			else if(btnId == "btnAddContent")
			{
				isSend = 1;
			}
			else if(btnId == "btnTestAddContent")
			{
				isSend = 1;
				isTest = 1;
			}
			
			$('#is_send').val(isSend);
			$('#is_test').val(isTest);
			$('#filter_appusers').val(filterAppusers);

			addContentForAllUsers(isSend, isTest);		
		});
	});
	
	function registerAttachmentForValidation()
	{
		$(".content_file").fileinput(
		{
			'showUpload':false,
			'showPreview':true,
			'previewFileType':'any'
		}).on('fileclear', function(event)
		{
			$('#frmAddContent').formValidation('revalidateField', 'content_file');
		});		
	}
</script>