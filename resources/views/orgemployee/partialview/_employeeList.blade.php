@php
if(!isset($forDashboard) || $forDashboard != 1)
{
	$forDashboard = 0;
}

$onlyDeleted = Input::get('onlyDeleted');
if(!isset($onlyDeleted) || $onlyDeleted != 1)
{
	$onlyDeleted = 0;
}
@endphp
<script>
	$(document).ready(function(){
		var moduleName = 'Employee';
		var tableCols = [];
		var tableColDefs = [];
		var tableUrl = "{!!  route('orgEmployeeDatatable') !!}";
		var orderCol = 0;
		var visibleColumns = [];
	    if(listForEmp == 1)
	    {
	    	var consBaseIndex = 0;
            @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
            consBaseIndex = 1;
            @endif

            visibleColumns = [ consBaseIndex + 0, consBaseIndex + 1, consBaseIndex + 2, consBaseIndex + 3, consBaseIndex + 4, consBaseIndex + 5, consBaseIndex + 6, consBaseIndex + 7, consBaseIndex + 8, consBaseIndex + 9, consBaseIndex + 10, consBaseIndex + 11, consBaseIndex + 12, consBaseIndex + 13, consBaseIndex + 14];
            
            @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
                visibleColumns.push(consBaseIndex + 15);
            @endif

			tableCols = [
	            @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
	            	{ data: '0', name: 'selectEmp', searchable: false, sortable: false },
	            @endif
	            { data: consBaseIndex + 0, name: 'employee_no' },
	            { data: consBaseIndex + 1, name: 'employee_name' },
	            { data: consBaseIndex + 2, name: 'org_departments.department_name' },
	            { data: consBaseIndex + 3, name: 'org_designations.designation_name' },
	            { data: consBaseIndex + 4, name: 'email' },
	            { data: consBaseIndex + 5, name: 'contact' },
	            { data: consBaseIndex + 6, name: 'dob' },
	            { data: consBaseIndex + 7, name: 'badges', searchable: false },
	            { data: consBaseIndex + 8, name: 'verification_status', searchable: false },
	            @if(isset($onlyDeleted) && $onlyDeleted == 0)
		            { data: consBaseIndex + 9, name: 'allotted_mb', searchable: false },
		            { data: consBaseIndex + 10, name: 'available_mb', searchable: false },
		            { data: consBaseIndex + 11, name: 'note_count', sortable: false, searchable: false},
		            { data: consBaseIndex + 12, name: 'status', searchable: false },
		            { data: consBaseIndex + 13, name: 'web_access_status', searchable: false },
		            { data: consBaseIndex + 14, name: 'last_synced_at', searchable: false, sortable: false },
		            @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
		            	{ data: consBaseIndex + 15, name: 'shareRights', sortable: false, searchable: false },
		            	{ data: consBaseIndex + 16, name: 'action', sortable: false, searchable: false },
		            	// { data: '14', name: 'selectEmp', searchable: false, sortable: false }
		            @endif
		        @endif
	        ];

            @if(isset($onlyDeleted) && $onlyDeleted == 0)
		        tableColDefs = [
		        	{
		                "targets":  consBaseIndex + 12,
		                "render": function ( data, type, row )
		                {
		                	if(data && data !== undefined && data !== "" && typeof data === 'string')
		                	{
		                		var dataArr = data.split("_");
			                	var id = dataArr[0];
			                	var isActive = dataArr[1];
			                	
			                	var isDisabled = "";
			                	@if((isset($admusrtoken) && $admusrtoken != "") || (isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 0))
									isDisabled = "disabled='disabled'";
								@endif
								
								var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
								var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
								var changeStatusFnName = "changeEmployeeStatus";
								
								var event = 'changeStatus("'+moduleName+'","'+id+'",'+isActive+',"'+changeStatusFnName+'");';						
								var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

			                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"' "+isDisabled+">"
			                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
		                	}
			                else
			                {
			                	return "";
			                }	
		                },
		            },
		        	{
		                "targets":  consBaseIndex + 13,
		                "render": function ( data, type, row )
		                {
		                	if(data && data !== undefined && data !== "" && typeof data === 'string')
		                	{
			                	var dataArr = data.split("_");
			                	var id = dataArr[0];
			                	var hasAccess = dataArr[1];
			                	
			                	var isDisabled = "";
			                	@if((isset($admusrtoken) && $admusrtoken != "") || (isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 0))
									isDisabled = "disabled='disabled'";
								@endif
								
								var btnClass = (hasAccess==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
								var iconClass = (hasAccess==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
								
								var event = 'changeEmployeeWebAccess("'+id+'",'+hasAccess+');';						
								var statusText =(hasAccess==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

			                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"' "+isDisabled+">"
			                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
			                }
			                else
			                {
			                	return "";
			                }
		                },
		            }         
	       		];
	        @endif
		}
		else if(listForSend == 1)
		{
			tableCols = [
            	{ data: '0', name: 'selectEmp', searchable: false, sortable: false },
	            { data: '1', name: 'employee_no' },
	            { data: '2', name: 'employee_name' },
	            { data: '3', name: 'org_departments.department_name' },
	            { data: '4', name: 'org_designations.designation_name' },
	            { data: '5', name: 'email' },
	            { data: '6', name: 'contact' },
	            { data: '7', name: 'dob' },
	            { data: '8', name: 'badges', searchable: false },
	            { data: '9', name: 'verification_status', searchable: false }
	        ];
		}
		else if(listForGroup == 1)
		{
			orderCol = 1;
			tableCols = [
	            { data: '0', name: 'selectEmp', searchable: false, sortable: false },
	            { data: '1', name: 'employee_no' },
	            { data: '2', name: 'employee_name' },
	            { data: '3', name: 'org_departments.department_name' },
	            { data: '4', name: 'org_designations.designation_name' },
	            { data: '5', name: 'email' },
	            { data: '6', name: 'badges', searchable: false },
	            { data: '7', name: 'verification_status', searchable: false }
	        ];
		}
		
	    empTableObj = $('#employees-table').DataTable({
	    	crossDomain: true,
	    	@if(isset($forEmpList) && $forEmpList == 1)
		    	scrollX: true,
			    deferRender: true,
		    @endif
	    	responsive: true,
	        processing: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	        serverSide: true,
	         ajax: {
	            url: tableUrl,
	            method: 'POST',
	            data: function ( d ) {
	            	d = getAppuserDataForTable(d);
	            	d.forDashboard = '{{ $forDashboard }}';
	            	d.onlyDeleted = '{{ $onlyDeleted }}';

	            	if(listForGroup == 1 || listForSend == 1)
					{	
						var jsonSelEmpIdArr = [];

						@if(isset($selectedEmpIdArr) && count($selectedEmpIdArr) > 0)
							@foreach($selectedEmpIdArr as $selEmpId)
								var selEmpId = "{{ $selEmpId }}";
								jsonSelEmpIdArr.push(selEmpId);
							@endforeach
						@endif

						var jsonSelEmpIdArrStr = JSON.stringify(jsonSelEmpIdArr);

	            		d.selArr = jsonSelEmpIdArrStr;
					}
            	}
	        },
	        columns: tableCols,
	        "order": [[ orderCol, "asc" ]],
	        @if(isset($forEmpList) && $forEmpList == 1 && $onlyDeleted == 0)
	        	dom: 'Bfrtip',
		        buttons: [
			        {
		                extend: 'excelHtml5',
		                title: 'Appuser_List_'+getCurrentDateTimeStr(),
		                exportOptions: {
		                    columns: visibleColumns
		                }
		            },
		            {
		                extend: 'pdfHtml5',
		                orientation: 'landscape',
		                pageSize: 'A4',
		                title: 'Appuser_List_'+getCurrentDateTimeStr(),
		                exportOptions: {
		                    columns: visibleColumns
		                }
		            },
		            {
		                extend: 'csvHtml5'
		            },
	       			@if($forDashboard == 0)
						{
							text: 'Share Rights',
							action: function ( e, dt, node, config ) {
								loadModifySelectedEmployeeShareRightModal();
							}
						},
						{
							text: 'Send Credentials',
							action: function ( e, dt, node, config ) {
								sendSelectedEmployeeCredentialMail();
							}
						},
						{
							text: 'Set Status Active',
							action: function ( e, dt, node, config ) {
								changeSelectedEmployeeStatus(1);
							}
						},
						{
							text: 'Set Status Inactive',
							action: function ( e, dt, node, config ) {
								changeSelectedEmployeeStatus(0);
							}
						},
						{
							text: 'Enable File Save/Share',
							action: function ( e, dt, node, config ) {
								modifySelectedEmployeeEmployeeFileSaveShare(1);
							}
						},
						{
							text: 'Disable File Save/Share',
							action: function ( e, dt, node, config ) {
								modifySelectedEmployeeEmployeeFileSaveShare(0);
							}
						},
						{
							text: 'Enable Screen Save/Share',
							action: function ( e, dt, node, config ) {
								modifySelectedEmployeeEmployeeScreenShare(1);
							}
						},
						{
							text: 'Disable Screen Save/Share',
							action: function ( e, dt, node, config ) {
								modifySelectedEmployeeEmployeeScreenShare(0);
							}
						},
						{
							text: 'Quota',
							action: function ( e, dt, node, config ) {
								loadModifySelectedEmployeeQuotaModal();
							}
						},
					@endif
		        ],
		    @else
		    	dom: 'rtip',
		    @endif
		    iDisplayLength: -1,	        
			"columnDefs": tableColDefs,
		    "drawCallback": function() {
		    	if(listForGroup == 1 || listForSend == 1)
				{
			        $('input.empIsSelected').iCheck({
						checkboxClass: 'icheckbox_square-blue',
					});
					
					@if(isset($selectedEmpIdArr) && count($selectedEmpIdArr) > 0)
						@foreach($selectedEmpIdArr as $selEmpId)
							// var selEmpId = "{{ $selEmpId }}";
							// var chkObj = $("input.empIsSelected[type=checkbox][value='"+selEmpId+"']");
							
							// $(chkObj).removeAttr('checked');
							// $(chkObj).prop('checked', true);
							// $(chkObj).iCheck('update');
						@endforeach
					@endif
					setupSelectAllEmployeeCheckBox();
				}
				else if(listForEmp == 1) {
	            	@if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
				        $('input.empIsSelected').iCheck({
							checkboxClass: 'icheckbox_square-blue',
						});
						setupSelectAllEmployeeCheckBox();
					@endif				
				}
		    },
		        
	    });		
	});

	function reloadEmployeeTable()
	{
		empTableObj.ajax.reload();
	}
        
    function getCurrentDateTimeStr()
    {
		var dtStr = new Date().getDate()+'-'+((new Date().getMonth()*1)+1)+'-'+new Date().getFullYear()+'_'+new Date().getHours()+'-'+new Date().getMinutes();
		return dtStr;
	}

	function setupSelectAllEmployeeCheckBox()
	{
        $('input.empSelectAll').iCheck({
			checkboxClass: 'icheckbox_square-blue',
		});
		$('input.empSelectAll').on('ifChecked', function(event){
			toggleAllEmployeeSelection(true);
		});
		$('input.empSelectAll').on('ifUnchecked', function(event){
			toggleAllEmployeeSelection(false);
		});		
	}

	function toggleAllEmployeeSelection(isChecked)
	{
		if(isChecked === true)
		{
			$('input.empIsSelected').iCheck('check');
		}
		else
		{
			$('input.empIsSelected').iCheck('uncheck');
		}
	}
</script>

<div class="box-body">
    <div class="table">
    	@if(isset($forGroupList) && $forGroupList == 1)
			{{ Form::open(array('id' => 'frmGroupMembers', 'class' => 'form-vertical')) }}
		@endif
	        <table id="employees-table" class="table table-bordered" width="100%">
	            <thead>
	                <tr>
	                    @if(isset($forGroupList) && $forGroupList == 1)
	                    	<th>
	                    		<label><input type='checkbox' id='empSelectAll' name='empSelectAll' class='empSelectAll'></label>
	                    	</th>
	                    @elseif(isset($forSend) && $forSend == 1)
	                    	<th>
	                    		<label><input type='checkbox' id='empSelectAll' name='empSelectAll' class='empSelectAll'></label>
	                    	</th>
	                    @elseif(isset($forEmpList) && $forEmpList == 1)
		                    @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
		                    	<th>
		                    		<label><input type='checkbox' id='empSelectAll' name='empSelectAll' class='empSelectAll'></label>
		                    	</th>
		                    @endif
		                @endif
	                    <th>ID</th>
	                    <th>Name</th>
	                    <th>Department</th>
	                    <th>Designation</th>
	                    <th>Email</th>
	                    @if(!isset($forGroupList) || $forGroupList == 0)
		                    <th>Contact</th>
		                    <th>DOB</th>
		                @endif
	                    <th>Badge(s)</th>
	                    <th>Verification Status</th>
	                    @if(isset($forEmpList) && $forEmpList == 1 && $onlyDeleted == 0)
		                    <th>Allotted MB</th>
		                    <th>Available MB</th>
                            <th>Notes Count</th>
                            <th>Status</th>
		                    <th>Web Access</th>
		                    <th>Last Synced At</th>
		                    @if(isset($usrtoken) && $usrtoken != "" && isset($modulePermissions) && $modulePermissions->module_edit == 1)
		                    	<th>Share Rights</th>
		                    	<th>Action</th>
		                    @endif
		                @endif
	                </tr>
	            </thead>
	        </table>
	    @if(isset($forGroupList) && $forGroupList == 1)
	    	{{ Form::close() }}
	    @endif
    </div>
</div>
