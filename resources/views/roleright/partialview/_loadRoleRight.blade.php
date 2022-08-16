<script>
	function checkAllRightCheckboxes()
	{
		var chkAllRights = $('#checkAllRights').is(":checked");
		if(chkAllRights)
		{
			$('.all-module-rights').prop('checked', true);
			$('.module-right').prop('checked', true);
		}
		else
		{
			$('.all-module-rights').prop('checked', false);
			$('.module-right').prop('checked', false);
		}
	}
	function checkAllModuleRightCheckboxes(role_id)
	{
		var chkAllModuleRights = $('#checkAllModuleRights_'+role_id).is(":checked");
		if(chkAllModuleRights)
		{
			$('.module-right-'+role_id).prop('checked', true);
		}
		else
		{
			$('.module-right-'+role_id).prop('checked', false);
		}
		
		var module_right_not_selected = $('.module-right').not(':checked').length;
		
		if(module_right_not_selected <= 0)
		{
			$('.all-rights').prop('checked', true);
		}
		else
		{
			$('.all-rights').prop('checked', false);
		}
	}
	function checkModuleRight(module_id)
	{
		var module_right_not_selected = $('.module-right-'+module_id).not(':checked').length;
		var module_not_selected = $('.module-right').not(':checked').length;
		
		if(module_right_not_selected <= 0)
		{
			$('.all-module-rights-'+module_id).prop('checked', true);
		}
		else
		{
			$('.all-module-rights-'+module_id).prop('checked', false);
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
<table width="100%" class="table table-striped table-fixed">
	<thead>
		<tr>
			<th>
				<input type="checkbox" id="checkAllRights" value="1" onchange="checkAllRightCheckboxes();" class="all-rights"/>
			</th>	
			<th>Module</th>												
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
		@foreach($modules as $module)
			<?php 				
				$moduleId = $module->module_id;
				
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

			@if(isset($roleRights[$moduleId]))
				@if($roleRights[$moduleId]->module_add == 1)
					<?php $addChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_view == 1)
					<?php $viewChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_edit == 1)
					<?php $editChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_delete == 1)
					<?php $deleteChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_print == 1)
					<?php $printChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_email == 1)
					<?php $emailChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_download == 1)
					<?php $downloadChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_upload == 1)
					<?php $uploadChecked = "checked='checked'"; ?>
				@endif
				@if($roleRights[$moduleId]->module_share == 1)
					<?php $shareChecked = "checked='checked'"; ?>
				@endif
			@endif
			<tr>
				<td>
					<input type="checkbox" id="checkAllModuleRights_{{ $moduleId }}" value="1" onchange="checkAllModuleRightCheckboxes({{ $moduleId }});" class="all-module-rights all-module-rights-{{ $moduleId }} minimal"/>
				</td>
				<td>
					{{ $module->module_name }}
				</td>
				<td>
					<input type="checkbox" name="chk_add_module_{{ $moduleId }}" id="chk_add_module_{{ $moduleId }}" {{ $addChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_view_module_{{ $moduleId }}" id="chk_view_module_{{ $moduleId }}" {{ $viewChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_edit_module_{{ $moduleId }}" id="chk_edit_module_{{ $moduleId }}" {{ $editChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_delete_module_{{ $moduleId }}" id="chk_delete_module_{{ $moduleId }}" {{ $deleteChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_print_module_{{ $moduleId }}" id="chk_print_module_{{ $moduleId }}" {{ $printChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_email_module_{{ $moduleId }}" id="chk_email_module_{{ $moduleId }}" {{ $emailChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_download_module_{{ $moduleId }}" id="chk_download_module_{{ $moduleId }}" {{ $downloadChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_upload_module_{{ $moduleId }}" id="chk_upload_module_{{ $moduleId }}" {{ $uploadChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
				<td>
					<input type="checkbox" name="chk_share_module_{{ $moduleId }}" id="chk_share_module_{{ $moduleId }}" {{  $shareChecked }} value="1" class="module-right module-right-{{ $moduleId }} minimal" onchange="checkModuleRight({{ $moduleId }});"/>
				</td>
			</tr>	
		@endforeach																					
	</tbody>
</table>							