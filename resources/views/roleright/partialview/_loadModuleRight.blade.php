<script>
	function checkAllRightCheckboxes()
	{
		var chkAllRights = $('#checkAllRights').is(":checked");
		if(chkAllRights)
		{
			$('.all-role-rights').prop('checked', true);
			$('.role-right').prop('checked', true);
		}
		else
		{
			$('.all-role-rights').prop('checked', false);
			$('.role-right').prop('checked', false);
		}
	}
	function checkAllRoleRightCheckboxes(role_id)
	{
		var chkAllModuleRights = $('#checkAllRoleRights_'+role_id).is(":checked");
		if(chkAllModuleRights)
		{
			$('.role-right-'+role_id).prop('checked', true);
		}
		else
		{
			$('.role-right-'+role_id).prop('checked', false);
		}
		
		var module_right_not_selected = $('.role-right').not(':checked').length;
		
		if(module_right_not_selected <= 0)
		{
			$('.all-rights').prop('checked', true);
		}
		else
		{
			$('.all-rights').prop('checked', false);
		}
	}
	function checkRoleRight(role_id)
	{
		var module_right_not_selected = $('.role-right-'+role_id).not(':checked').length;
		var module_not_selected = $('.role-right').not(':checked').length;
		
		if(module_right_not_selected <= 0)
		{
			$('.all-role-rights-'+role_id).prop('checked', true);
		}
		else
		{
			$('.all-role-rights-'+role_id).prop('checked', false);
		}
		
		if(module_not_selected <= 0)
		{
			$('.all-rights').prop('checked', true);
		}
		else
		{
			$('.all-rights').prop('checked', false);
		}
	}
</script>
<table width="100%" class="table table-striped">
	<thead>
		<tr>
			<th>
				<input type="checkbox" id="checkAllRights" value="1" onchange="checkAllRightCheckboxes();" class="all-rights"/>
			</th>												
			<th>Role</th>												
			<th>Add</th>												
			<th>View</th>												
			<th>Edit</th>												
			<th>Delete</th>												
			<th>Print</th>												
			<th>Email</th>												
			<th>Download</th>	
			<th>Upload</th>												
			<th>Share</th>												
		</tr>
	</thead>
	<tbody>
		@foreach($roles as $role)
			<?php 				
				$roleId = $role->role_id;
				
				$addChecked = "";
				$viewChecked = "";
				$editChecked = "";
				$deleteChecked = "";
				$printChecked = "";
				$emailChecked = "";
				$downloadChecked = "";
				$uploadChecked = "";
				$shareChecked = "";
			?>

			@if(isset($moduleRights[$roleId]))
				@if($moduleRights[$roleId]->module_add == 1)
					<?php $addChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_view == 1)
					<?php $viewChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_edit == 1)
					<?php $editChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_delete == 1)
					<?php $deleteChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_print == 1)
					<?php $printChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_email == 1)
					<?php $emailChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_download == 1)
					<?php $downloadChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_upload == 1)
					<?php $uploadChecked = "checked='checked'"; ?>
				@endif
				@if($moduleRights[$roleId]->module_share == 1)
					<?php $shareChecked = "checked='checked'"; ?>
				@endif
			@endif
			<tr>
				<td>
					<input type="checkbox" id="checkAllRoleRights_{{ $roleId }}" value="1" onchange="checkAllRoleRightCheckboxes({{ $roleId }});" class="all-role-rights all-role-rights-{{ $roleId }}"/>
				</td>
				<td>
					{{ $role->role_name }}
				</td>
				<td>
					<input type="checkbox" name="chk_add_module_{{ $roleId }}" id="chk_add_module_{{ $roleId }}" {{ $addChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_view_module_{{ $roleId }}" id="chk_view_module_{{ $roleId }}" {{ $viewChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_edit_module_{{ $roleId }}" id="chk_edit_module_{{ $roleId }}" {{ $editChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_delete_module_{{ $roleId }}" id="chk_delete_module_{{ $roleId }}" {{ $deleteChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_print_module_{{ $roleId }}" id="chk_print_module_{{ $roleId }}" {{ $printChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_email_module_{{ $roleId }}" id="chk_email_module_{{ $roleId }}" {{ $emailChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_download_module_{{ $roleId }}" id="chk_download_module_{{ $roleId }}" {{ $downloadChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_upload_module_{{ $roleId }}" id="chk_upload_module_{{ $roleId }}" {{ $uploadChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_share_module_{{ $roleId }}" id="chk_share_module_{{ $roleId }}" {{ $shareChecked }} value="1" class="role-right role-right-{{ $roleId }}" onchange="checkRoleRight({{ $roleId }});"/>
				</td>
			</tr>
		@endforeach																					
	</tbody>
</table>							