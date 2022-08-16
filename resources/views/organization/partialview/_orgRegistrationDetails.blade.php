<?php
// $organizationId = 0;
$orgCode = NULL;
$orgSystemName = NULL;
$orgRegistrationName = NULL;
$phone = NULL;
$email = NULL;
$address = NULL;
$website = NULL;
$notes = NULL;
$logoImageHtml = "-";
$logoFilename = "";
$isSelfEnrollEnabled = 0;
$seVerCode = NULL;
$mappedAppKeyId = 0;
$mappedAppKeyName = Config::get('app_config.company_name');

if(isset($organization))
{
	// $organizationId = $organization->organization_id;
	$orgCode = $organization->org_code;
	$orgSystemName = $organization->system_name;
	$orgRegistrationName = $organization->regd_name;
	$phone = $organization->phone;
	$email = $organization->email;
	$address = $organization->address;
	$website = $organization->website;
	$notes = $organization->org_notes;
	$logoFilename = $organization->logo_filename;
	$isSelfEnrollEnabled = $organization->org_self_enroll_enabled;
	$seVerCode = $organization->dec_se_verification_code;
	$mappedAppKeyId = $organization->mapped_app_key_id;

	if(isset($organization->appKeyMapping))
	{
		$mappedAppKeyName = $organization->appKeyMapping->app_name;
	}
}
	
if($isView)
{
	if($orgSystemName == "")
	{
		$orgSystemName = "-";
	}
	if($orgRegistrationName == "")
	{
		$orgRegistrationName = "-";
	}
	if($phone == "")
	{
		$phone = "-";
	}
	if($email == "")
	{
		$email = "-";
	}
	if($address == "")
	{
		$address = "-";
	}
	if($website == "")
	{
		$website = "-";
	}
	if($notes == "")
	{
		$notes = "-";
	}	
	if($seVerCode == "")
	{
		$seVerCode = "-";
	}								
}
	
$orgLogoUrl = "";
if(isset($organization->url))
{
	$orgLogoUrl = $organization->url;
	if($orgLogoUrl != "")
	{
		$logoImageHtml = "<img src='$orgLogoUrl' / height='35px'>";
	}
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmOrgRegistration', 'enctype' => "multipart/form-data"]) !!}
  	<div class="row">
		<div class="col-md-12">
			<h4>
				Registration Details
				@if($isView && ($modulePermissions->module_add == 1 || $modulePermissions->module_edit == 1))
					{!! Form::button('<i class="fa fa-pencil"></i>', ['type' => 'button', 'class' => 'btn btn-link', 'onclick' => "loadOrganizationRegistrationDetailsView('$organizationId', 0);"]) !!}
				@endif
			</h4>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-6">
            <div class="form-group">
                {!! Form::label('regd_name', 'Registered Name *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $orgRegistrationName }}
                @else
                	{!! Form::text('regd_name', $orgRegistrationName, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'regd_name']) !!}
                @endif                
            </div>
        </div>
		<div class="col-md-6">
            <div class="form-group">
                {!! Form::label('system_name', 'System Name *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $orgSystemName }}
                @else
                	{!! Form::text('system_name', $orgSystemName, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'system_name']) !!}
                @endif                
            </div>
        </div>
    </div>
    
    <div class="row">  	
        <div class="col-md-6">
	        <div class="form-group">	            
				{!! Form::label('logo_file', 'Logo', ['class' => 'control-label']) !!}
				
				@php $inputVisibility = ""; @endphp
				@if($orgLogoUrl != "" || $isView)
					@php $inputVisibility = "display:none;"; @endphp							
				@endif
				
				@if($inputVisibility != "" && $orgLogoUrl != "")
					<div class="input-group" id="orgImgFileDisp" class="col-sm-12">
						<div class="col-sm-6">
							{{ HTML::image($orgLogoUrl, '', array('height' => '50px')) }}
				            {!! Form::hidden('image_changed', 0, ['id' => 'image_changed']) !!}
					    </div>
					    @if(!$isView)
						    <div class="col-sm-6">
						         <button type="button" class="btn btn-xs btn-danger removeImage"><i class="fa fa-times"></i></button>
						    </div>
						@endif	
					</div>						
				@endif
				
				<div class="input-group" id="orgImgFileInput" style="{!! $inputVisibility !!}">
					<span class="input-group-addon">
						<i class="fa fa-image">
						</i>
					</span>
					{{ Form::file('logo_file', ['class' => 'form-control logo_file', 'autocomplete' => 'off', 'id' => 'logo_file', 'placeholder' => 'Select Logo']) }}
				</div>
	            
	        </div>
	    </div>
		<div class="col-md-6">
            <div class="form-group">
                {!! Form::label('org_code', 'Code *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $orgCode }}
                @else
                	{!! Form::text('org_code', $orgCode, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'org_code']) !!}
                @endif                
            </div>
        </div>  
    </div>
    
    <div class="row">
		<div class="col-md-4">
            <div class="form-group">
                {!! Form::label('phone', 'Phone *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $phone }}
                @else
                	{!! Form::text('phone', $phone, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'phone']) !!}
                @endif                
            </div>
        </div>
		<div class="col-md-4">
            <div class="form-group">
                {!! Form::label('email', 'Email *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $email }}
                @else
                	{!! Form::text('email', $email, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'email']) !!}
                @endif                
            </div>
        </div>
		<div class="col-md-4">
            <div class="form-group">
                {!! Form::label('website', 'Website', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{{ $website }}
                @else
                	{!! Form::text('website', $website, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'website']) !!}
                @endif                
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('address', 'Address', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{!! nl2br($address) !!}
                @else
                	{!! Form::textarea('address', $address, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'address', 'rows' => '3']) !!}
                @endif               
            </div>
        </div> 
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('org_notes', 'Notes', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
                	{!! nl2br($notes) !!}
                @else
                	{!! Form::textarea('org_notes', $notes, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'org_notes', 'rows' => '3']) !!}
                @endif               
            </div>
        </div> 
    </div>  
	<div class="row">		
        <div class="col-md-3">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('org_self_enroll_enabled', 1, NULL, ['class' => 'form-control', 'id' => 'org_self_enroll_enabled']) }}
                    &nbsp;Enable Self Enrollment
          		</label>
            </div>
        </div>	
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('self_enroll_verification_code', 'Verification Code', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $seVerCode }}
				@else
                	{!! Form::text('self_enroll_verification_code', $seVerCode, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'self_enroll_verification_code']) !!}
				@endif         
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('mapped_app_key_id', 'Application', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $mappedAppKeyName }}
				@else
                	{!! Form::select('mapped_app_key_id', $appKeyMappingArr, $mappedAppKeyId, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'mapped_app_key_id']) !!}
				@endif         
            </div>
        </div>
	</div>  
    @if(!$isView)
	    <br/>
	    {!! Form::hidden('orgId', $organizationId, ['class' => 'orgId', 'id' => 'regOrgId']) !!}
	    {!! Form::hidden('usrToken', $userToken) !!}
	    <div class="row">
			<div class="col-md-12" align="right">
			    {!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-orange', 'onclick' => "loadOrganizationRegistrationDetailsView('$organizationId', 1);"]) !!}
			    &nbsp;
			    {!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
			</div>
		</div>
	@endif
{!! Form::close() !!}
<script>	
	var frmRegistrationObj = $('#frmOrgRegistration');
	$(document).ready(function(){
		
		$(".removeImage").click(function() {
			$('#image_changed').val(1);
			$('#orgImgFileInput').show();
			$('#orgImgFileDisp').hide();
			registerAttachmentForValidation();
		});
        
		<?php
		if(!$isView)
		{?>		
			$("#mapped_app_key_id").css('width', '100%');		
			$("#mapped_app_key_id").select2({
				placeholder: "Select Application",
				allowClear: false
			});
			$("#mapped_app_key_id").val({{ $mappedAppKeyId }}).trigger('change');

			frmRegistrationObj.formValidation({
	            framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
	            fields: {           	
		             regd_name: {
	                    validators: {
							notEmpty: {
								message: 'Registered Name is required'
							}
	                    }
		             },      	
		             system_name: {
	                    validators: {
							notEmpty: {
								message: 'System Name is required'
							}
	                    }
		             },      	
		             phone: {
	                    validators: {
							notEmpty: {
								message: 'Phone is required'
							}
	                    }
		             },      	
		             email: {
	                    validators: {
							notEmpty: {
								message: 'Email is required'
							},
							emailAddress: {
		                        message: 'This is not a valid email address'
		                    }
	                    }
		             },    	
		             website: {
	                    validators: {
	                        uri: {
	                            message: 'The website URL is not valid'
	                        },
	                    }
		             },      	
		             org_code: {
	                    validators: {
							notEmpty: {
								message: 'Code is required'
							},
							remote: {
								message: 'Duplicate Organization Code',
								url: "{!!  url('/validateOrganizationCode') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {
										orgId: $('#regOrgId').val(),
										usrToken: '{{ $userToken }}',			
									};
								}
							}
	                    }
		             },      	
		             self_enroll_verification_code: {
	                    validators: {
							callback: {
	                            message: 'Verification Code is required',
	                            callback: function (value, validator, $field) {
	                                var isSelfEnroll = $("#org_self_enroll_enabled").prop('checked');
	                                if(isSelfEnroll)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        },
							stringLength: {
		                        max: 9,
		                        message: 'The Verification must be less than 8 characters'
		                    }
	                    }
		             },
					 logo_file:
					 {
						validators:
						{
							file:
							{
								extension: 'jpeg,jpg,png',
								type: 'image/jpeg,image/png',
								maxSize: "<?php echo Config::get('app_config.org_logo_image_filesize_limit'); ?>",
								message: 'The selected file is not valid'
							}
						}
					 }
		        },
	        })
			.on('success.form.fv', function(e) {
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveOrganizationRegistrationDetails($form);
	        });
	        
	        
			$("#logo_file").fileinput({
				'showUpload':false,
				'showPreview':false,
				'previewFileType':'any',
				'allowedFileTypes': ["image"],
			});
  		
	  		$('#org_self_enroll_enabled').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		})
	  		.on('ifChanged', function(e) {
	            // Get the field name
	            var field = $(this).attr('name');
	            $(frmRegistrationObj).formValidation('revalidateField', field);
	            $(frmRegistrationObj).formValidation('revalidateField', 'self_enroll_verification_code');

	            var field = $(this).attr('name');
	            if($(this).prop('checked'))
	            {
	            	$('#self_enroll_verification_code').prop('disabled', false);
	            }
	            else
	            {
	            	$('#self_enroll_verification_code').val('').trigger('change');
	            	$('#self_enroll_verification_code').prop('disabled', true);
	            }
	            
	        }).end();
			
	    <?php
	    }?>	  

        @if($isView)
        	$('#org_self_enroll_enabled').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
            $('#org_self_enroll_enabled').prop('disabled', true);
        @endif  

  		@if(isset($isSelfEnrollEnabled) && $isSelfEnrollEnabled == 1)
        	$('#org_self_enroll_enabled').iCheck('check');
        @else
            $('#self_enroll_verification_code').prop('disabled', true);
        @endif
	});
	
	function registerAttachmentForValidation()
	{
		$("#logo_file").fileinput(
		{
			'showUpload':false,
			'showPreview':false,
			'previewFileType':'any'
		})
		.on('fileclear', function(event)
		{
			frmRegistrationObj.formValidation('revalidateField', 'logo_file');
		});
	}
</script>