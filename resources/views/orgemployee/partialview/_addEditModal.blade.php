<?php
$fieldTypeText = Config::get("app_config_user_field.field_type_text");
$fieldTypeNumber = Config::get("app_config_user_field.field_type_number");
$fieldTypeDate = Config::get("app_config_user_field.field_type_date");
$fieldInputPrefix = Config::get("app_config_user_field.field_input_prefix");

$employeeId = 0;
$employeeNo = NULL;
$employeeName = NULL;
$email = NULL;
$contact = NULL;
$emerContact = NULL;
$dob = NULL;
$startDt = NULL;
$deptId = 0;
$desigId = 0;
$gender = "";
$isEmailDisabled = "";
$departmentName = "-";
$designationName = "-";
$genderName = "-";
$badgeString = "-";
if(isset($employee))
{
	$isEmailDisabled = "disabled";
	 
	$employeeId = $id;//employee->employee_id;
	$employeeNo = $employee->employee_no;
	$employeeName = $employee->employee_name;
	$email = $employee->email;
	$contact = $employee->contact;
	$dob = $employee->dob_disp;
	$deptId = $employee->department_id;
	$desigId = $employee->designation_id;
	$gender = $employee->gender;
	$startDt = dbToDispDate($employee->start_date);
	$emerContact = $employee->emergency_contact;
}
    
$empImageUrl = "";
if(isset($employee->photo_url))
	$empImageUrl = $employee->photo_url;
	
$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
@if (isset($intCss))
	@for ($i = 0; $i < count($intCss); $i++)
    	<link href="{{ asset($assetBasePath.$intCss[$i]) }}" rel="stylesheet" type="text/css" />
	@endfor
@endif

<div id="addEditEmployeeModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveEmployee', 'enctype' => "multipart/form-data"]) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}
					</h4>
				</div>
				<div class="modal-body">	
					<div class="row">	
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('emp_no', 'ID ', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $employeeNo }}
								@else
									{!! Form::text('emp_no', $employeeNo, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>
						<div class="col-md-8">
							<div class="form-group">
								{!! Form::label('emp_name', 'Name *', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $employeeName }}
								@else
									{!! Form::text('emp_name', $employeeName, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>	
					</div>	
					
					
					<div class="row">					
						<div class="col-md-12">
							<div class="form-group">
								{!! Form::label('photo_img', 'Photo Image', ['class' => 'control-label']) !!}
								
								@php $inputVisibility = ""; @endphp
								@if($empImageUrl != "" || $isView)
									@php $inputVisibility = "display:none;"; @endphp							
								@endif
								
								<span id="empImgFileInputMsg" style="color: red;{!! $inputVisibility !!}">
									
								</span>
								
								@if($inputVisibility != "" && $empImageUrl != "")
									<div class="input-group" id="empImgFileDisp" class="col-sm-12">
										<div class="col-sm-6">
											{{ HTML::image($empImageUrl, '', array('height' => '50px')) }}
								            {!! Form::hidden('image_changed', 0, ['id' => 'image_changed']) !!}
									    </div>
									    @if(!$isView)
										    <div class="col-sm-6">
										         <button type="button" class="btn btn-xs btn-danger removeImage"><i class="fa fa-times"></i></button>
										    </div>
										@endif	
									</div>						
								@endif
								
								<div class="input-group" id="empImgFileInput" style="{!! $inputVisibility !!}">
									<span class="input-group-addon">
										<i class="fa fa-image">
										</i>
									</span>
									{{ Form::file('photo_img', ['class' => 'form-control photo_img', 'id' => 'photo_img', 'placeholder' => 'Select Image']) }}
								</div>
								{!! $errors->first('photo_img', '<p class="help-block">:message</p>') !!}
							</div>
						</div>	
					</div>				
							
					<div class="row">			
						<div class="col-md-6">
							<div class="form-group">
				                {!! Form::label('dept_id', 'Department', ['class' => 'control-label']) !!}
				                @if($isView)
									<br/>
									{{ $departmentName }}
								@else
				               		{{ Form::select('dept_id', $departmentArr, NULL, ['class' => 'form-control', 'id' => 'dept_id']) }}
								@endif
				            </div>
				        </div>	
						<div class="col-md-6">
							<div class="form-group">
				                {!! Form::label('desig_id', 'Designation', ['class' => 'control-label']) !!}
				                @if($isView)
									<br/>
									{{ $designationName }}
								@else
				                	{{ Form::select('desig_id', $designationArr, NULL, ['class' => 'form-control', 'id' => 'desig_id']) }}
								@endif
				            </div>
				        </div>	
				    </div>	
					<div class="row">	
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('email', 'Email *', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $email }}
								@else
									{!! Form::email('email', $email, ['class' => 'form-control text-cap', 'autocomplete' => 'off', $isEmailDisabled]) !!}
								@endif
							</div>
						</div>		
						<div class="col-md-6">
							<div class="form-group">
				                {!! Form::label('gender', 'Gender', ['class' => 'control-label']) !!}
				                @if($isView)
									<br/>
									{{ $genderName }}
								@else
				                	{{ Form::select('gender', $genderArr, NULL, ['class' => 'form-control', 'id' => 'gender']) }}
								@endif
				            </div>
				        </div>
					</div>			    	
						    
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
				                {!! Form::label('badge_id', 'Badge(s)', ['class' => 'control-label']) !!}
				                @if($isView)
									<br/>
									{{ $badgeString }}
								@else
				                	{{ Form::select('badge_id[]', $badgeArr, NULL, ['class' => 'form-control', 'id' => 'badge_id', 'multiple' => 'multiple']) }}
								@endif
				            </div>
				        </div>	
				    </div>
				    
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('contact', 'Contact', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $contact }}
								@else
									{!! Form::text('contact', $contact, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group sandbox-container">
								{!! Form::label('dob', 'Date Of Birth', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $dob }}
								@else
									{!! Form::text('dob', $dob, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>
					</div>
				    
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('emergency_contact', 'Emergency Contact', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $emerContact }}
								@else
									{!! Form::text('emergency_contact', $emerContact, ['class' => 'form-control text-cap', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group sandbox-container">
								{!! Form::label('start_date', 'Start Date', ['class' => 'control-label']) !!}
								@if($isView)
									<br/>
									{{ $startDt }}
								@else
									{!! Form::text('start_date', $startDt, ['class' => 'form-control', 'autocomplete' => 'off']) !!}
								@endif
							</div>
						</div>
					</div>
	
					@if(isset($orgUserFields) && count($orgUserFields) > 0)
						<div class="row">
							@foreach($orgUserFields as $usrField)
								@php
								$fieldId = $usrField->org_field_id;
								$fieldDispName = $usrField->field_display_name;
								$fieldTypeId = $usrField->field_type_id;
								$fieldTypeName = $usrField->type_name;
								$fieldIsMandatory = $usrField->is_mandatory;
								
								if($fieldIsMandatory == 1)
									$fieldDispName .= "*";
								
								$fieldInpName = $fieldInputPrefix.$fieldId;
								$fieldValue = "";
								if(isset($empFieldValueArr[$fieldId]))
									$fieldValue = $empFieldValueArr[$fieldId];
								@endphp
								@include('orgemployee.partialview._userFieldRow')
							@endforeach
						</div>
					@endif		
				</div>
	            
	            {!! Form::hidden('empId', $employeeId) !!}
	            {!! Form::hidden('usrtoken', $usrtoken) !!}
				
				<div class="modal-footer">
					<div class="col-sm-offset-9 col-sm-3">
						{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary form-control']) !!}
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>

<script>
	$(document).ready(function(){
		
		@if($inputVisibility == "")
			registerAttachmentForValidation();
		@endif
		
		var js_array = [<?php echo '"'.implode('","', $existingBadges).'"' ?>];
		
		$('#dept_id').css('width', '100%');
		$('#dept_id').select2({
			placeholder: "Select Department",
			allowClear: true,
		});
		$('#dept_id').val('{{ $deptId }}').trigger('change');
		
		$(".removeImage").click(function() {
			$('#image_changed').val(1);
			$('#empImgFileInput').show();
			$('#empImgFileInputMsg').show();
			$('#empImgFileDisp').hide();
			registerAttachmentForValidation();
		});
		
		$('#gender').css('width', '100%');
		$('#gender').select2({
			placeholder: "Select Gender",
			allowClear: true,
		});
		$('#gender').val('{{ $gender }}').trigger('change');
		
		$('#desig_id').css('width', '100%');
		$('#desig_id').select2({
			placeholder: "Select Designation",
			allowClear: true,
		});
		$('#desig_id').val('{{ $desigId }}').trigger('change');
		
		$('#badge_id').css('width', '100%');
		$('#badge_id').select2({
			placeholder: "Select Badge",
			allowClear: true,
		});
		$('#badge_id').val(js_array).trigger('change');
		
		$('#frmSaveEmployee').formValidation({
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
				emp_name:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Name is required'
						}
					}
				},
				emp_no:
				{
					validators:
					{
						remote:
						{
							message: 'Duplicate ID',
							url: "{!!  url('/validateOrgEmployeeNo') !!}",
							type: 'POST',
							crossDomain: true,
							delay: {!!  Config::get('app_config.validation_call_delay') !!},
							data: function(validator, $field, value)
							{
								return{
									empId: "{{ $employeeId }}",
									empNo: value,
									usrtoken: "{{ $usrtoken }}"
								};
							}
						}
					}
				},
				email:
				{
					validators:
					{
						notEmpty:
						{
							message: 'Email is required'
						},
                        emailAddress: {
	                        message: 'Invalid email address'
	                    } 	
					}
				},
				photo_img:
				{
					validators:
					{
						file:
						{
							extension: 'jpeg,jpg,png',
							type: 'image/jpeg,image/png',
							maxSize: "<?php echo Config::get('app_config.org_emp_photo_image_filesize_limit'); ?>",
							message: 'The selected file is not valid'
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
            saveEmployeeDetails($form);
        });
	});
	
	function registerAttachmentForValidation()
	{
		$("#photo_img").fileinput(
		{
			'showUpload':false,
			'showPreview':false,
			'previewFileType':'any'
		})
		.on('fileclear', function(event)
		{
			$('#frmSaveEmployee').formValidation('revalidateField', 'photo_img');
		});
	}

	function saveEmployeeDetails(frmObj)
	{
   		var dataToBeSent = new FormData($(frmObj)[0]);
		$.ajax({
			type: 'POST',
			url: siteurl+'/saveOrgEmployeeDetails',
			dataType: "json",
			crossDomain: true,
			data: dataToBeSent,
	        contentType: false,
	        processData: false,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#addEditEmployeeModal').modal('hide');	
			reloadEmployeeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>