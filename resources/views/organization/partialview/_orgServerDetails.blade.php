<?php
// $organizationId = 0;

// if(isset($organization))
// {
// 	$organizationId = $organization->organization_id;
// }

$isAppDbServer = 1;
$dbname = NULL;
$host = NULL;
$username = NULL;
$password = NULL;
$isAppFileServer = 1;
$fileHost = NULL;
$isAppSmtpServer = 1;
$smtpEmail = NULL;
$smtpKey = NULL;

if(isset($orgServer))
{
	$isAppDbServer = $orgServer->is_app_db_server;
	$dbname = $orgServer->dbname;
	$host = $orgServer->host;
	$username = $orgServer->username;
	$password = $orgServer->password;
	$isAppFileServer = $orgServer->is_app_file_server;
	$fileHost = $orgServer->file_host;
	$isAppSmtpServer = $orgServer->is_app_smtp_server;
	$smtpEmail = $orgServer->smtp_email;
	$smtpKey = $orgServer->smtp_key;
}

if($isView)
{
	if($username == "")
		$username = "-";
	
	if($password == "")
		$password = "-";
		
	if($host == "")
		$host = "-";
		
	if($fileHost == "")
		$fileHost = "-";
		
	if($smtpEmail == "")
		$smtpEmail = "-";
		
	if($smtpKey == "")
		$smtpKey = "-";
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmOrgServer']) !!}
  	<div class="row">
		<div class="col-md-12">
			<h4>
				Subscription Details
				@if($isView && ($modulePermissions->module_add == 1 || $modulePermissions->module_edit == 1))
					{!! Form::button('<i class="fa fa-pencil"></i>', ['type' => 'button', 'class' => 'btn btn-link', 'onclick' => "loadOrganizationServerDetailsView('$organizationId', 0);"]) !!}
				@endif
			</h4>
		</div>
	</div>
	<div class="row">
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('is_app_db_server', 1, NULL, ['class' => 'form-control', 'id' => 'is_app_db_server']) }}
                    &nbsp;Uses App DB Server
          		</label>
            </div>
        </div>
        <div class="col-md-8">
            <div class="form-group">
                {!! Form::label('dbname', 'Database Name', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $dbname }}
				@else
					@if($dbname == "")
						<div class="input-group">
								<span class="input-group-addon">{{ Config::get('app_config.org_db_prefix') }}</span>
					@endif
                		{!! Form::text('dbname', $dbname, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'dbname']) !!}
					@if($dbname == "")
						</div> 
					@endif
				@endif         
            </div>
        </div>
    </div>
	<div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('host', 'Host', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $host }}
				@else
                	{!! Form::text('host', $host, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'host']) !!}
				@endif         
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('username', 'User Name', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $username }}
				@else
                	{!! Form::text('username', $username, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'username']) !!}
				@endif         
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('password', 'Password', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $password }}
				@else
                	{!! Form::text('password', $password, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'password']) !!}
				@endif         
            </div>
        </div>
    </div>
       
	<div class="row">
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('is_app_file_server', 1, NULL, ['class' => 'form-control', 'id' => 'is_app_file_server']) }}
                    &nbsp;Uses App File Server
          		</label>
            </div>
        </div>
        <div class="col-md-8">
            <div class="form-group">
                {!! Form::label('file_host', 'File Server Host', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $fileHost }}
				@else
                	{!! Form::text('file_host', $fileHost, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'file_host']) !!}
				@endif         
            </div>
        </div>
    </div>
       
	<div class="row">
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('is_app_smtp_server', 1, NULL, ['class' => 'form-control', 'id' => 'is_app_smtp_server']) }}
                    &nbsp;Uses App SMTP Server
          		</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('smtp_email', 'SMTP Email', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $smtpEmail }}
				@else
                	{!! Form::text('smtp_email', $smtpEmail, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'smtp_email']) !!}
				@endif         
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('smtp_key', 'SMTP Key', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $smtpKey }}
				@else
                	{!! Form::text('smtp_key', $smtpKey, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'smtp_key']) !!}
				@endif         
            </div>
        </div>
    </div>
    
    @if(!$isView)
	    <br/>
	    {!! Form::hidden('orgId', $organizationId, ['class' => 'orgId']) !!}
	    <div class="row">
			<div class="col-md-12" align="right">
			    {!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-orange', 'onclick' => "loadOrganizationServerDetailsView('$organizationId', 1);"]) !!}
			    &nbsp;
			    {!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
			</div>
		</div>
	@endif
{!! Form::close() !!}
<script>	
	var frmOrgServer = $('#frmOrgServer');
	$(document).ready(function(){	

        @if($isView)
        	$('#is_app_db_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
	  		
	  		$('#is_app_file_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
	  		
	  		$('#is_app_smtp_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		});
	  		
            $('#is_app_db_server').prop('disabled', true);
            $('#is_app_file_server').prop('disabled', true);
            $('#is_app_smtp_server').prop('disabled', true);
        @else
        	$('#is_app_db_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		})
	  		.on('ifChanged', function(e) {
	            // Get the field name
	            var field = $(this).attr('name');
	            $(frmOrgServer).formValidation('revalidateField', field);
	            $(frmOrgServer).formValidation('revalidateField', "host");
	            $(frmOrgServer).formValidation('revalidateField', "username");
	            $(frmOrgServer).formValidation('revalidateField', "password");

	            var field = $(this).attr('name');
	            if($(this).prop('checked'))
	            {
	            	$('#host').val('').trigger('change');
	            	$('#host').prop('disabled', true);
	            	
	            	$('#username').val('').trigger('change');
	            	$('#username').prop('disabled', true);

	            	$('#password').val('').trigger('change');
	            	$('#password').prop('disabled', true);
	            }
	            else
	            {
	            	$('#host').prop('disabled', false);
	            	$('#username').prop('disabled', false);
	            	$('#password').prop('disabled', false);
	            }
	            
	        }).end();

	  		$('#is_app_file_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		})
	  		.on('ifChanged', function(e) {
	            // Get the field name
	            var field = $(this).attr('name');
	            $(frmOrgServer).formValidation('revalidateField', field);
	            $(frmOrgServer).formValidation('revalidateField', 'file_host');

	            var field = $(this).attr('name');
	            if($(this).prop('checked'))
	            {
	            	$('#file_host').val('').trigger('change');
	            	$('#file_host').prop('disabled', true);
	            }
	            else
	            {
	            	$('#file_host').prop('disabled', false);
	            }
	            
	        }).end();

	  		$('#is_app_smtp_server').iCheck({
	    		checkboxClass: 'icheckbox_square-blue',
	  		})
	  		.on('ifChanged', function(e) {
	            // Get the field name
	            var field = $(this).attr('name');
	            $(frmOrgServer).formValidation('revalidateField', field);
	            $(frmOrgServer).formValidation('revalidateField', 'smtp_email');
	            $(frmOrgServer).formValidation('revalidateField', 'smtp_key');

	            var field = $(this).attr('name');
	            if($(this).prop('checked'))
	            {
	            	$('#smtp_email').val('').trigger('change');
	            	$('#smtp_email').prop('disabled', true);

	            	$('#smtp_key').val('').trigger('change');
	            	$('#smtp_key').prop('disabled', true);
	            }
	            else
	            {
	            	$('#smtp_email').prop('disabled', false);

	            	$('#smtp_key').prop('disabled', false);
	            }
	            
	        }).end();
        @endif

		<?php
		if(!$isView)
		{?>			
			frmOrgServer.formValidation({
	            framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
	            fields: {           	
		             dbname: {
	                    validators: {
							notEmpty: {
								message: 'Database Name is required'
							},
							remote: {
								message: 'Duplicate Database Name',
								url: "{!!  url('/validateOrganizationDatabaseName') !!}",
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
		             host: {
	                    validators: {
							callback: {
	                            message: 'Host is required',
	                            callback: function (value, validator, $field) {
	                                var isAppDb = $("#is_app_db_server").prop('checked');
	                                if(!isAppDb)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             },         	
		             username: {
	                    validators: {
							callback: {
	                            message: 'Username is required',
	                            callback: function (value, validator, $field) {
	                                var isAppDb = $("#is_app_db_server").prop('checked');
	                                if(!isAppDb)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             },       	
		             password: {
	                    validators: {
							callback: {
	                            message: 'Password is required',
	                            callback: function (value, validator, $field) {
	                                var isAppDb = $("#is_app_db_server").prop('checked');
	                                if(!isAppDb)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             },    	
		             file_host: {
	                    validators: {
							callback: {
	                            message: 'File Host is required',
	                            callback: function (value, validator, $field) {
	                                var isAppDb = $("#is_app_file_server").prop('checked');
	                                if(!isAppDb)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             },  	
		             smtp_email: {
	                    validators: {
							callback: {
	                            message: 'SMTP Email is required',
	                            callback: function (value, validator, $field) {
	                                var isAppSmtp = $("#is_app_smtp_server").prop('checked');
	                                if(!isAppSmtp)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             }, 	
		             smtp_key: {
	                    validators: {
							callback: {
	                            message: 'SMTP Key is required',
	                            callback: function (value, validator, $field) {
	                                var isAppSmtp = $("#is_app_smtp_server").prop('checked');
	                                if(!isAppSmtp)
	                                	return value != "";
	                                else
	                                	return true;
	                            }
	                        }
	                    }
		             },
		        },
	        })
			.on('success.form.fv', function(e) {
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveOrganizationServerDetails($form);
	        });
	    <?php
	    }?>

  		

  		@if($isAppDbServer == 1)
        	$('#is_app_db_server').iCheck('check');
        @endif

  		@if($isAppFileServer == 1)
        	$('#is_app_file_server').iCheck('check');
        @endif

  		@if($isAppSmtpServer == 1)
        	$('#is_app_smtp_server').iCheck('check');
        @endif
	});
</script>