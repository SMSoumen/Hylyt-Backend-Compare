<?php
$groupId = 0;
$groupName = "";
$groupDescription = "";
$groupQuotaMb = 0;
$groupIsTwoWay = NULL;
$groupHasAutoEnroll = NULL;
$isQuotaDisabled = "";
$minQuotaVal = 1;

if(isset($group))
{
	//$isQuotaDisabled = "disabled";
	$groupId = $id;//$group->group_id;
	$groupName = $group->name;
	$groupDescription = $group->description;
	$groupIsTwoWay = $group->is_two_way;
	$groupHasAutoEnroll = $group->auto_enroll_enabled;
	$groupQuotaKb = $group->allocated_space_kb;
	$minQuotaVal = $group->used_space_kb;
	
	$groupQuotaMb = ceil($groupQuotaKb/1024);
	
	if($groupQuotaMb <= 0)
		$minQuotaVal = $groupQuotaKb;
	
	$minQuotaVal = ceil($minQuotaVal/1024);
	
	if($minQuotaVal <= 0)
		$minQuotaVal = 1;
}    
$groupImageUrl = "";
if(isset($group->url))
	$groupImageUrl = $group->url;
?>
<style>
	.modal-lg {
	    width: 1200px;
	}
</style>
<script>
	$(document).ready(function(){	  
		$("#photo_file").fileinput({
			'showUpload':false,
			'showPreview':false,
			'previewFileType':'any',
			'allowedFileTypes': ["image"],
		});

		/* $('#isTwoWay').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		}); */

  		$('#isAutoEnroll').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});

  		/* @if(isset($groupIsTwoWay) && $groupIsTwoWay == 1)
  			console.log('groupIsTwoWay')
        	$('#isTwoWay').iCheck('check');
        @endif */

  		@if(isset($groupHasAutoEnroll) && $groupHasAutoEnroll == 1)
        	$('#isAutoEnroll').iCheck('check');
        @endif
		
		$(".removeImage").click(function() {
			$('#image_changed').val(1);
			$('#grpImgFileInput').show();
			$('#grpImgFileDisp').hide();
			registerAttachmentForValidation();
		});
			  
		$('#frmSaveGroup').formValidation({
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
				group_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Group Name is required'
						},
						remote:
						{
							message: 'Duplicate Group Name',
							url: "{!!  url('/validateOrgGroupName') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									groupId: "{{ $groupId }}",
									groupName: value,
									usrtoken: "{{ $usrtoken }}"
								};
							}
						}
					}
				},   
                group_quota_mb: {
                    validators: {
                        notEmpty: {
                            message: 'Allotted Space is required'
                        },
                        numeric: {
                            message: 'The value is not a number',
                            thousandsSeparator: '',
                            decimalSeparator: ''
                        } ,
                        greaterThan: {
                            value: {{ $minQuotaVal }}
                        }                     
                    }
                },
				photo_file:
				{
					validators:
					{
						file:
						{
							extension: 'jpeg,jpg,png',
							type: 'image/jpeg,image/png',
							maxSize: "<?php echo Config::get('app_config.org_grp_photo_image_filesize_limit'); ?>",
							message: 'The selected file is not valid'
						}
					}
				}
			}
		})
		.on('success.form.fv', function(e) {
            // Prevent form submission
            e.preventDefault();
            console.log("Herer");

            // Some instances you can use are
            var $form = $(e.target),        // The form instance
                fv    = $(e.target).data('formValidation'); // FormValidation instance
                
            $('#btnSubmit').prop('disabled', true);

            // Do whatever you want here ...
            saveGroupDetails($form);
        });
	});
	
	function registerAttachmentForValidation()
	{
		$("#photo_file").fileinput(
		{
			'showUpload':false,
			'showPreview':false,
			'previewFileType':'any'
		})
		.on('fileclear', function(event)
		{
			$('#frmSaveGroup').formValidation('revalidateField', 'photo_file');
		});
	}

	function saveGroupDetails(frmObj)
	{
		var elems = [];
		$('.empIsSelected').each(function(i, obj) {
		    if($(obj).prop("checked") == true)
		    {
		    	empId = $(obj).val();
				elems.push(empId);
			}
		});
		
		$('#groupMembers').val(JSON.stringify(elems));
		
    	var dataToBeSent = new FormData($(frmObj)[0]);
    	
		$.ajax({
			type: 'POST',
			url: siteurl+'/saveOrgGroupDetails',
			crossDomain: true,
			dataType: "json",
	        contentType: false,
	        processData: false,
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditGroupModal').modal('hide');	
			reloadGroupTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
	
	function submitSaveGroupForm(isFavorited)
	{
		$('#isFavorited').val(isFavorited);
		$('#frmSaveGroup').submit();
	}
	
</script>

<div id="addEditGroupModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}
					</h4>
				</div>
				<div class="modal-body">
					{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveGroup', 'enctype' => "multipart/form-data"]) !!}
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									{!! Form::label('group_name', 'Group Name *', ['class' => 'control-label']) !!}
									{!! Form::text('group_name', $groupName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								</div>
							</div>
					        <div class="col-md-6">
						        <div class="form-group">
									{!! Form::label('photo_file', 'Photo', ['class' => 'control-label']) !!}
									
									@php $inputVisibility = ""; @endphp
									@if($groupImageUrl != "" || $isView)
										@php $inputVisibility = "display:none;"; @endphp							
									@endif
									
									<span id="empImgFileInputMsg" style="color: red;{!! $inputVisibility !!}">
										
									</span>
									
									@if($inputVisibility != "" && $groupImageUrl != "")
										<div class="input-group" id="grpImgFileDisp" class="col-sm-12">
											<div class="col-sm-6">
												{{ HTML::image($groupImageUrl, '', array('height' => '50px')) }}
									            {!! Form::hidden('image_changed', 0, ['id' => 'image_changed']) !!}
										    </div>
										    @if(!$isView)
											    <div class="col-sm-6">
											         <button type="button" class="btn btn-xs btn-danger removeImage"><i class="fa fa-times"></i></button>
											    </div>
											@endif	
										</div>						
									@endif
									
									<div class="input-group" id="grpImgFileInput" style="{!! $inputVisibility !!}">
										<span class="input-group-addon">
											<i class="fa fa-image">
											</i>
										</span>
										{{ Form::file('photo_file', ['class' => 'form-control photo_file', 'autocomplete' => 'off', 'id' => 'photo_file', 'placeholder' => 'Select Photo']) }}
									</div>					               	
						        </div>
						    </div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									{!! Form::label('group_quota_mb', 'Quota', ['class' => 'control-label']) !!}
									 <div class="input-group">
										{!! Form::text('group_quota_mb', $groupQuotaMb, ['class' => 'form-control text-cap', 'autocomplete' => 'off', $isQuotaDisabled]) !!}
				                   	 	<span class="input-group-addon">MB</span>
				                   	</div>
								</div>
							</div>
							<!-- <div class="col-md-3">
								<label style="margin-top:30px;">
									{!! Form::checkbox('isTwoWay', 1, NULL, ['id' => 'isTwoWay']) !!}
									&nbsp;&nbsp;Is two way
								</label>
							</div> -->
							<div class="col-md-3">
								<label style="margin-top:30px;">
									{!! Form::checkbox('isAutoEnroll', 1, NULL, ['id' => 'isAutoEnroll']) !!}
									&nbsp;&nbsp;Is Auto Enroll
								</label>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									{!! Form::label('description', 'Description', ['class' => 'control-label']) !!}
									{!! Form::text('description', $groupDescription, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								</div>							
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									{!! Form::label('group_member', 'Group Member(s)', ['class' => 'control-label']) !!}
								</div>							
							</div>
						</div>
						
						{!! Form::hidden('isFavorited', 0, ['id' => 'isFavorited']) !!}	
						{!! Form::hidden('groupId', $groupId) !!}
						{!! Form::hidden('usrtoken', $usrtoken) !!}
						{!! Form::hidden('empIsSelected', NULL, ['id' => 'groupMembers']) !!}
					
					{!! Form::close() !!}
					
				    @include('orgemployee.partialview._advancedSearch')
				    @include('orgemployee.partialview._employeeList')
				</div>
				<div class="modal-footer">
					<div class="col-sm-offset-9 col-sm-3">
						@if($groupId == 0)
							{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save As Favorite', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'submitSaveGroupForm(1);', 'id' => 'btnSubmit']) !!}
						@endif
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'submitSaveGroupForm(0);', 'id' => 'btnSubmit']) !!}
					</div>
				</div>
		</div>
	</div>
</div>