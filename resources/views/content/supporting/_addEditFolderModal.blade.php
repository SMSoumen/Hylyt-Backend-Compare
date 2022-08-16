<?php
$folderId = 0;
$folderName = "";
$folderIconCode = "";
$isFavorited = 0;
if(isset($folder))
{
	$folderId = $folder->folder_id;
	$folderName = $folder->folder_name;
	$folderIconCode = $folder->icon_code;
	$isFavorited = $folder->is_favorited;
}
else 
{
	$folderIconCode = Config::get('app_config.default_folder_icon_code');
}
?>
<script>
	var btnMarkFolder = $("#btnMarkFolder");
	var btnMarkFolderIcon = $("#btnMarkFolderIcon");
	$(document).ready(function(){
		
		// add/remove checked class
	    $(".image-radio").each(function(){
	        if($(this).find('input[type="radio"]').first().attr("checked")){
	            $(this).addClass('image-radio-checked');
	        }else{
	            $(this).removeClass('image-radio-checked');
	        }
	    });
	    
		@if($isFavorited == 1)
			btnMarkFolderIcon.addClass('text-yellow');
		@else
			btnMarkFolderIcon.removeClass('text-yellow');
		@endif

	    // sync the input state
	    $(".image-radio").on("click", function(e){
	        $(".image-radio").removeClass('image-radio-checked');
	        $(this).addClass('image-radio-checked');
	        var $radio = $(this).find('input[type="radio"]');
	        $radio.prop("checked",!$radio.prop("checked"));

	        e.preventDefault();
	    });
		
		$('#frmSaveFolder').formValidation({
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
							message: 'Folder Name is required'
						},
						stringLength: {
	                        message: 'Folder Name must be less than 20 characters',
	                        max: 20,
	                    },
	                    regexp: {
	                        regexp: /^[a-zA-Z1-9\s]+$/i,
	                        message: 'Folder Name can consist of alphanumeric characters and spaces only'
	                    },
						remote:
						{
							message: 'Duplicate Folder Name',
							url: "{!! route('folder.validateFolderName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									id: $("#folderId").val(),
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
            saveFolderDetails($form);
        });
	});

	function saveFolderDetails(frmObj)
	{	
		var dataToSend = compileSessionParams();
		var formDataToSend = $(frmObj).serialize();	
		dataToSend = formDataToSend+dataToSend;
		dataToSend += "&id="+$("#folderId").val();

		var submitUrl = '';
		@if(isset($hasFilters) && $hasFilters == 1)
			submitUrl = "{!! route('folder.saveVirtual') !!}";
			$("#filterContentModal").modal('hide');
		@else
			submitUrl = "{!! route('folder.save') !!}";
		@endif
		
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
						
					$("#addEditFolderModal").modal('hide');	

					@if(isset($hasFilters) && $hasFilters == 1)
						showFolderGroupList();
					@else					
						reloadFolderTable();
					@endif
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
	function toggleFolderStatus()
	{
		var currStatus = $("#isFavorited").val();
		
		var updateStatus = 1;
		
		if(currStatus*1 == 1)
			updateStatus = 0;
			
		if(updateStatus*1 == 1)
		{
			btnMarkFolderIcon.addClass('text-yellow');
		}
		else
		{
			btnMarkFolderIcon.removeClass('text-yellow');
		}
		
		$("#isFavorited").val(updateStatus);
	}
</script>
<style>
	.image-radio {
	    cursor: pointer;
	    box-sizing: border-box;
	    -moz-box-sizing: border-box;
	    -webkit-box-sizing: border-box;
	    border: 4px solid transparent;
	    margin-bottom: 0;
	    outline: 0;
	}
	.image-radio input[type="radio"] {
	    display: none;
	}
	.image-radio-checked {
	   /* border-color: #4783B0;*/
	}
	.image-radio .glyphicon {
	  	position: absolute;
    	color: #fff;
	    background-color: #8DC63F;
	    padding: 3px;
	    border-radius: 23px;
	    top: 36px;
	    right: 18px;
}
	.image-radio-checked .glyphicon {
	  display: block !important;
	}
</style>

<div id="addEditFolderModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSaveFolder']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}
						&nbsp;
						{!! Form::button('<i id="btnMarkFolderIcon" class="fa fa-star fa-2x"></i>', ['type' => 'button', 'class' => 'btn btn-link btn-xs', 'id' => 'btnMarkFolder', 'onclick' => 'toggleFolderStatus();', 'style' => 'margin-top: 0px;']) !!}
					</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<!-- <div class="col-sm-12">
						{!! Form::label('name', 'Name', ['class' => 'col-sm-3 control-label']) !!}
						</div> -->
						<div class="col-sm-12">
						{!! Form::text('name', $folderName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Folder Name']) !!}
						</div>
					</div>
					<div class="form-group">
					<!-- 	<div class="col-sm-12">
						{!! Form::label('iconCode', 'Icon', ['class' => 'col-sm-3 control-label']) !!}
						</div> -->
							@foreach($folderIcons as $folderIcon)
								@php
								$isSelected = '';
								if($folderIcon == $folderIconCode) {
									$isSelected = 'checked="checked"';
								}
								@endphp
								<div class="col-sm-2 text-center">
								    <label class="image-radio">
								        <img class="img-responsive" src="{{ $folderIconBasePath.'/'.$folderIcon.'.png' }}" />
								        <input type="radio" name="iconCode" value="{{ $folderIcon }}" {{ $isSelected }} />
								        <i class="glyphicon glyphicon-ok hidden"></i>
								    </label>
								</div>
							@endforeach
					</div>
					{!! Form::hidden('isFavorited', $isFavorited, ['id' => 'isFavorited']) !!}
					
					{!! Form::hidden('folderId', $folderId, ['id' => 'folderId']) !!}

					{!! Form::hidden('hasFilters', $hasFilters) !!}

					@if(isset($hasFilters) && $hasFilters == 1)
						{!! Form::hidden('fromTimeStamp', $fromTimeStamp) !!}
						{!! Form::hidden('toTimeStamp', $toTimeStamp) !!}

						@if(isset($filFolderArr) && is_array($filFolderArr) && count($filFolderArr) > 0)
							@foreach($filFolderArr as $filFolder)
								<input type="hidden" name="filFolderArr[]" value="{{ $filFolder }}">
							@endforeach
						@endif

						@if(isset($filGroupArr) && is_array($filGroupArr) && count($filGroupArr) > 0)
							@foreach($filGroupArr as $filGroup)
								<input type="hidden" name="filGroupArr[]" value="{{ $filGroup }}">
							@endforeach
						@endif

						@if(isset($filSourceArr) && is_array($filSourceArr) && count($filSourceArr) > 0)
							@foreach($filSourceArr as $filSource)
								<input type="hidden" name="filSourceArr[]" value="{{ $filSource }}">
							@endforeach
						@endif

						@if(isset($filTagArr) && is_array($filTagArr) && count($filTagArr) > 0)
							@foreach($filTagArr as $filTag)
								<input type="hidden" name="filTagArr[]" value="{{ $filTag }}">
							@endforeach
						@endif

						@if(isset($filTypeArr) && is_array($filTypeArr) && count($filTypeArr) > 0)
							@foreach($filTypeArr as $filType)
								<input type="hidden" name="filTypeArr[]" value="{{ $filType }}">
							@endforeach
						@endif

						@if(isset($filAttachmentTypeArr) && is_array($filAttachmentTypeArr) && count($filAttachmentTypeArr) > 0)
							@foreach($filAttachmentTypeArr as $filAttachmentType)
								<input type="hidden" name="filAttachmentTypeArr[]" value="{{ $filAttachmentType }}">
							@endforeach
						@endif

						{!! Form::hidden('chkIsUnread', $chkIsUnread) !!}
						{!! Form::hidden('chkIsStarred', $chkIsStarred) !!}
						{!! Form::hidden('chkIsUntagged', $chkIsUntagged) !!}
						{!! Form::hidden('chkIsLocked', $chkIsLocked) !!}
						{!! Form::hidden('chkIsConversation', $chkIsConversation) !!}
						{!! Form::hidden('chkIsRestricted', $chkIsRestricted) !!}
						{!! Form::hidden('chkShowFolder', $chkShowFolder) !!}
						{!! Form::hidden('chkShowGroup', $chkShowGroup) !!}
						{!! Form::hidden('chkDownloadStatus', $chkDownloadStatus) !!}
						{!! Form::hidden('filSenderEmail', $filSenderEmail) !!}
					@endif

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