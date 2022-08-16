<?php
// $organizationId = 0;
$organizationAdminId = 0;
$adminEmail = NULL;
$adminName = NULL;

if(isset($organization))
{
	// $organizationId = $organization->organization_id;
}
if(isset($organizationAdmin))
{
	$organizationAdminId = $organizationAdmin->org_admin_id;
	$adminEmail = $organizationAdmin->admin_email;
	$adminName = $organizationAdmin->fullname;
}
?>
{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmOrgAdministration']) !!}
  	<div class="row">
		<div class="col-md-12">
			<h4>
				Administration Details
				@if($isView && ($modulePermissions->module_add == 1 || $modulePermissions->module_edit == 1))
					{!! Form::button('<i class="fa fa-pencil"></i>', ['type' => 'button', 'class' => 'btn btn-link', 'onclick' => "loadOrganizationAdministrationDetailsView('$organizationId', 0);"]) !!}
				@endif
			</h4>
		</div>
	</div>
    @if(!$isView)
		<div class="row">
	        <div class="col-md-3">
	            <div class="form-group">
	                {!! Form::label('fullname', 'Name', ['class' => 'control-label']) !!}
	                {!! Form::text('fullname', $adminName, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'fullname']) !!}         
	            </div>
	        </div>
	        <div class="col-md-3">
	            <div class="form-group">
	                {!! Form::label('adm_email', 'Email *', ['class' => 'control-label']) !!}
	                {!! Form::text('adm_email', $adminEmail, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'adm_email']) !!}         
	            </div>
	        </div>
	        <div class="col-md-3">
	            <div class="form-group">
	                {!! Form::label('role_id', 'Role', ['class' => 'control-label']) !!}
	                {!! Form::select('role_id', $roleArr, NULL, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'role_id']) !!}         
	            </div>
	        </div>
	        <div class="col-md-3">
	        	<br/>
			    {!! Form::button('<i class="fa fa-plus"></i>&nbsp;&nbsp;Add', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
			    &nbsp;
			    {!! Form::button('<i class="fa fa-refresh"></i>&nbsp;&nbsp;Reset', ['type' => 'button', 'class' => 'btn btn-danger', 'onclick' => 'resetOrgAdminForm();']) !!}
			</div>
	    </div>
	@endif
	
	<div class="row">
        <div class="col-md-12">
		    <div class="table">
		        <table id="organization-admins-table" class="table table-bordered" width="100%">
		            <thead>
		                <tr>
		                    <th>Name</th>
		                    <th>Email</th>
		                    <th>Role</th>
		                    <th>Status</th>
		                     @if(!$isView)
		                    	<th>Action</th>
		                    @endif
		                </tr>
		            </thead>
		        </table>
		    </div>
		</div>
	</div>
    {!! Form::hidden('orgId', $organizationId, ['class' => 'orgId', 'id' => 'admOrgId']) !!}
    {!! Form::hidden('fromIndOrganization', $fromIndOrganization) !!}
    @if(!$isView)
	    <br/>
	    {!! Form::hidden('orgAdmId', $organizationAdminId, ['class' => 'orgId', 'id' => 'orgAdmId']) !!}
	    @if($fromIndOrganization == 0)
		    <div class="row">
		        <div class="col-md-12" align="right">
				    {!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-orange', 'onclick' => "loadOrganizationAdministrationDetailsView('$organizationId', 1);"]) !!}
				</div>			
			</div>
		@endif
	@endif
{!! Form::close() !!}
<script>	
	var orgAdminTableObj;
	var frmAdministrationObj = $('#frmOrgAdministration');
	$(document).ready(function(){	
		var moduleName = 'Administrator';
	    orgAdminTableObj = $('#organization-admins-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('organizationAdminDatatable') !!}",
	            method: 'POST',
	            crossDomain: true,
	            data: function ( d ) {
	                d.usrToken = "{{ $userToken }}",
	                d.fromIndOrganization = "{{ $fromIndOrganization }}",
					d.orgId = $('#admOrgId').val()
            	}
	            
	        },
	        columns: [
	            { data: '0', name: 'fullname' },
	            { data: '1', name: 'admin_email' },
	            { data: '2', name: 'cms_roles.role_name' },
	            { data: '3', name: 'status', sortable: false, searchable: false },
	            @if(!$isView || $fromIndOrganization == 1)
	            	{ data: '4', name: 'action', sortable: false, searchable: false }
	            @endif
	        ],
	        "order": [[ 0, "asc" ]],
			"columnDefs": [
	        	{
	                "targets":  3,
	                "render": function ( data, type, row )
	                {
	                	var dataArr = data.split("_");
	                	var id = dataArr[0];
	                	var isActive = dataArr[1];
                		var fnName = "changeOrgAdministratorStatus";

						var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
						var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
						
						var event = "";	
						var disabledBtn = "disabled='disabled'";
						@if(!$isView || $fromIndOrganization == 1)
							event = 'changeStatus("'+moduleName+'","'+id+'",'+isActive+','+fnName+');';
							disabledBtn = "";
						@endif
											
						var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

	                    var statusButtonHtml =  "<button type='button' class='btn btn-xs "+btnClass+"' onclick ='"+event+"' "+disabledBtn+">"
	                    						+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
						
						var credButtonHtml = ''; 
						@if(!$isView)
							credButtonHtml = '&nbsp;&nbsp;<button type="button" onclick="resendOrganizationAdminCred(\''+id+'\');" class="btn btn-xs btn-purple"><i class="fa fa-envelope"></i>&nbsp;&nbsp;Credentials</button>';
						@endif

						var credViewButtonHtml = '';
						@if(!$isView && $fromIndOrganization == 0)
							credViewButtonHtml = '&nbsp;&nbsp;<button type="button" onclick="loadOrganizationAdminCredDetails(\''+id+'\');" class="btn btn-xs btn-purple"><i class="fa fa-envelope"></i>&nbsp;&nbsp;View Credentials</button>';
						@endif
						
						return statusButtonHtml+credButtonHtml+credViewButtonHtml;
						
	                },
	            }           
       		]  
	    });
		<?php
		if(!$isView)
		{?>
			$("#role_id").css('width', '100%');		
			$("#role_id").select2({
				placeholder: "Select Role",
				allowClear: true,
			});
			$("#role_id").val('').trigger('change');

			frmAdministrationObj.formValidation({
	            framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
	            fields: {           	
		             adm_email: {
	                    validators: {
							notEmpty: {
								message: 'Email is required'
							},
							emailAddress: {
		                        message: 'The value is not a valid email address'
		                    },
							remote: {
								message: 'Duplicate Administration Email',
								url: "{!!  url('/validateAdminEmailForOrg') !!}",
								type: 'POST',
								crossDomain: true,
								delay: {!!  Config::get('app_config.validation_call_delay') !!},
								data: function(validator, $field, value) 
								{			
									return {
										orgId: $('#admOrgId').val(),
										usrToken: '{{ $userToken }}',
										admEmail: value			
									};
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
	            addOrganizationAdministrator($form);
	        });
	    <?php
	    }?>
	});
	
	function redrawOrganizationAdminTable()
	{
		orgAdminTableObj.ajax.reload();
	}
	
	function resetOrgAdminForm()
	{
		$('#adm_email').val('');
		$('#fullname').val('');
		$('#role_id').val('').trigger('change');		
		$(frmAdministrationObj).data('formValidation').resetForm();
	}
</script>