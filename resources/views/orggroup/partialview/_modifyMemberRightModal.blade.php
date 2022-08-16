<?php
$groupId = 0;
$groupName = "";
$isGroupTwoWay = NULL;
if(isset($group))
{
	$groupId = $id;//$group->group_id;
	$groupName = $group->name;
	$groupIsTwoWay = $group->is_two_way;
	
	if($groupIsTwoWay == 1)
		$isGroupTwoWay = TRUE;
}

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
<style>
	div.dt-buttons {
	    z-index: 10000 !important;
	}
</style>
<script>
	var empTableObj;
	var visibleColumns = [ 0, 1, 2, 3 ];
	var grpName = "{{ $groupName }}";
	var exportTitlePrefix = grpName + '_Member_List_';
	$(document).ready(function(){
		
		$('input').iCheck({
			checkboxClass: 'icheckbox_square-blue',
		});

		$('.right_id').css('width', '100%');
		$('.right_id').select2({
			placeholder: "Select Permission",
			allowClear: true,
		});
		
	    empTableObj = $('#employees-table').DataTable({
	    	dom: 'fBrtip',
	    	iDisplayLength: -1,
	        buttons: [
	            {
	                extend:    'excelHtml5',
	                titleAttr: 'Excel',
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
		        {
	                extend: 'csvHtml5',
	                title: exportTitlePrefix+getCurrentDateTimeStr(),
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            }
	        ],
		});
	    
		$('#frmSaveGroupRight').formValidation({
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
				
			}
		})
		.on('success.form.fv', function(e) {
            // Prevent form submission
            e.preventDefault();

            // Some instances you can use are
            var $form = $(e.target),        // The form instance
                fv    = $(e.target).data('formValidation'); // FormValidation instance

            // Do whatever you want here ...
            saveGroupRightDetails($form);
        });
	});

	function saveGroupRightDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			url: "{!!  route('orgGroup.saveRightDetails') !!}",
			crossDomain: true,
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#modifyGroupRightModal').modal('hide');
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
        
    function getCurrentDateTimeStr()
    {
  		var dtStr = new Date().getDate()+'-'+((new Date().getMonth()*1)+1)+'-'+new Date().getFullYear()+'_'+new Date().getHours()+'-'+new Date().getMinutes();
  		return dtStr;
  	}
</script>

<div id="modifyGroupRightModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmSaveGroupRight']) !!}
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
						<div class="col-md-9">
							<div class="form-group">
								{!! Form::label('group_name', 'Group Name', ['class' => 'control-label']) !!}
								{!! Form::text('group_name', $groupName, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'disabled' => 'disabled']) !!}
							</div>
						</div>
						<div class="col-md-3">
							<label style="margin-top:30px;">
								{!! Form::checkbox('isTwoWay', 1, $isGroupTwoWay, ['id' => 'isTwoWay', 'disabled' => 'disabled']) !!}
								&nbsp;&nbsp;Is two way
							</label>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								{!! Form::label('group_member', 'Group Member(s)', ['class' => 'control-label']) !!}
							</div>							
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="table">
						        <table id="employees-table" class="table table-bordered" width="100%">
						            <thead>
						                <tr>
						                    <!-- <th>Is Admin</th> -->
						                    <th>Employee No</th>
						                    <th>Name</th>
						                    <th>Department</th>
						                    <th>Designation</th>
						                    <!-- <th>Has Post Right</th>
						                    <th>Is Ghost</th> -->
						                    <th>Permissions</th>
						                </tr>
						            </thead>
						            <tbody>
						            	@if(isset($employees) && count($employees) > 0)
						            		@foreach($employees as $employee)
						            			@php
						            			$empId = $employee->employee_id;
						            			$empNo = $employee->employee_no;
						            			$empName = $employee->employee_name;
						            			$empDept = $employee->department_name;
						            			$empDesig = $employee->designation_name;
						            			$empIsAdmin = $employee->is_admin;
						            			$empIsGhost = $employee->is_ghost;
						            			$empHasPostRight = $employee->has_post_right;
						            			
						            			$isEmpAdminSelected = NULL;
						            			if($empIsAdmin == 1)
						            			{
													$isEmpAdminSelected = TRUE;
												}
												
						            			$isHasPostRightSelected = NULL;
						            			if($empHasPostRight == 1)
						            			{
													$isHasPostRightSelected = TRUE;
												}
												
						            			$isGhostSelected = NULL;
						            			if($empIsGhost == 1)
						            			{
													$isGhostSelected = TRUE;
												}

												$empRightId = "";
												if($empIsAdmin == 1)
												{
													$empRightId = $grpPermissionCodeAdmin;
												} 
												else if($empIsGhost == 1)
												{
													$empRightId = $grpPermissionCodeGhost;
												}
												else if($empHasPostRight == 1)
												{
													$empRightId = $grpPermissionCodeWrite;
												}
												else
												{
													$empRightId = $grpPermissionCodeRead;
												}

						            			@endphp
						            			<tr>
						            				<!-- <td align="center">
						            					{!! Form::checkbox('empIsAdmin[]', $empId, $isEmpAdminSelected, ['class' => 'empIsSelected']) !!}
						            				</td> -->
						            				<td>{{ $empNo }}</td>
						            				<td>{{ $empName }}</td>
						            				<td>{{ $empDept }}</td>
						            				<td>{{ $empDesig }}</td>
						            				<td> 
						            					{{ Form::select('empPermissionId_'.$empId, $rightArr, $empRightId, ['class' => 'form-control right_id']) }}
						            				</td>
						            				<!-- <td align="center">
						            					{!! Form::checkbox('empHasPostRight[]', $empId, $isHasPostRightSelected, ['class' => 'empIsSelected']) !!}
						            				</td>
						            				<td align="center">
						            					{!! Form::checkbox('empIsGhost[]', $empId, $isGhostSelected, ['class' => 'empIsSelected']) !!}
						            				</td> -->
						            			</tr>
						            		@endforeach
						            	@endif
						            </tbody>
						        </table>
						    </div>			
						</div>
					</div>
					{!! Form::hidden('groupId', $groupId) !!}
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