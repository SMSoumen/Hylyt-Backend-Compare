<style>
	.asRow
	{
		margin-top: 12px;
	}
</style>
<script>	
	var dontReloadTable = 1;
	
	var listForSend = 0;
	@if(isset($forSend))
		listForSend = {{ $forSend }};
	@endif
	
	var listForEmp = 0;
	@if(isset($forEmpList))
		listForEmp = {{ $forEmpList }};
	@endif
	
	var listForGroup = 0;
	@if(isset($forGroupList))
		listForGroup = {{ $forGroupList }};
	@endif
	
	var onlyDeleted = 0;
	@if(isset($onlyDeleted))
		onlyDeleted = {{ $onlyDeleted }};
	@endif
	
	$(document).ready(function(){	
		if(listForEmp == 1 || listForGroup == 1)	
	    {	
			$('#selVerStatus').css('width', '100%');
			$('#selVerStatus').select2({
				placeholder: "Select Verification Status",
				allowClear: true,
			}).on("change", function () { 
				if(dontReloadTable == 0)
					reloadEmpTable();
			});
			$('#selVerStatus').val('').trigger('change');
		}
		
		$('#selDesignation').css('width', '100%');
		$('#selDesignation').select2({
			placeholder: "Select Designation",
			allowClear: true,
		}).on("change", function () { 
			if(dontReloadTable == 0)
				reloadEmpTable();
		});
		$('#selDesignation').val('').trigger('change');
		
		$('#selDepartment').css('width', '100%');
		$('#selDepartment').select2({
			placeholder: "Select Department",
			allowClear: true,
		}).on("change", function () { 
			if(dontReloadTable == 0)
				reloadEmpTable();
		});
		
		$('#selDepartment').val('').trigger('change');
		
		$('#selBadge').css('width', '100%');
		$('#selBadge').select2({
			placeholder: "Select Badge",
			allowClear: true,
		}).on("change", function () { 
			if(dontReloadTable == 0)
				reloadEmpTable();
		});
		$('#selBadge').val('').trigger('change');
		
		dontReloadTable = 0;
	});
	
	function getAppuserDataForTable(data)
	{	    
	    @if(isset($admusrtoken) && $admusrtoken != "")	
		    data.admusrtoken = "{{ $admusrtoken }}";		
		    data.orgId = "{{ $orgId }}";
		@elseif(isset($usrtoken) && $usrtoken != "")
			data.usrtoken = "{{ $usrtoken }}";	
		@endif		
		data.forSend = listForSend;
		data.forEmpList = listForEmp;
		data.forGroupList = listForGroup;
	    
		if(listForEmp == 1 || listForGroup == 1)	
	    {
	    	var verStatus = $('#selVerStatus').val();
			if (verStatus != '')
			{
				data.verStatus = verStatus;
			}
		}
		
		var designation = $('#selDesignation').val();
		if (designation != '')
		{
			data.designation = designation;
		}
		
		var department = $('#selDepartment').val();
		if (department != '')
		{
			data.department = department;
		}
		
		var badge = $('#selBadge').val();
		if (badge != '')
		{
			data.badge = badge;
		}
		
		return data;
	}

	function getAppuserDataForTableForForm()
	{
		var formData = "";
	    formData += "&usrtoken="+"{{ $usrtoken }}";		
		formData += "&forSend="+listForSend;
		formData += "&forEmpList="+listForEmp;
		formData += "&forGroupList="+listForGroup;
	    
		if(listForEmp == 1 || listForGroup == 1)	
	    {
	    	var verStatus = $('#selVerStatus').val();
			if (verStatus != '')
			{
				formData += "&verStatus="+verStatus;
			}
		}
		
		var designation = $('#selDesignation').val();
		if (designation != '')
		{
			formData += "&designation="+designation;
		}
		
		var department = $('#selDepartment').val();
		if (department != '')
		{
			formData += "&department="+department;
		}
		
		var badge = $('#selBadge').val();
		if (badge != '')
		{
			formData += "&badge="+badge;
		}
		
		return formData;
		
	}
	
	function resetAppuserFilters()
	{
	    dontReloadTable = 1;
		if(listForEmp == 1 || listForGroup == 1)	
	    {
			$('#selVerStatus').val('').trigger('change');
		}
		$('#selDesignation').val('').trigger('change');
		$('#selDepartment').val('').trigger('change');
		$('#selBadge').val('').trigger('change');
		reloadEmpTable();
	    dontReloadTable = 0;
	}
	
	function reloadEmpTable()
	{
		reloadEmployeeTable();
	}
</script>
{{ Form::open(array('id' => 'frmAppuserFilters', 'class' => 'form-vertical')) }}
	<div class="box-body">
		<div class="row">
			<div class="col-sm-12">
				<section class="panel panel-default">
					<div class="panel-heading">
						Advanced Search Options			
						<button type="button" name="resetFilters" id="resetFilters" class="btn-link pull-right" onclick="resetAppuserFilters();"><i class="fa fa-undo text-danger"></i>&nbsp;<b>Reset</b></button>
					</div>
					<div class="panel-body">
						<div class="row">
							@php
								$colClass = "col-md-4";
							@endphp
							@if((isset($forEmpList) && $forEmpList == 1) || (isset($forGroupList) && $forGroupList == 1))
								@php
									$colClass = "col-md-3";
								@endphp
								<div class="{{ $colClass }}">
					                {!! Form::label('selVerStatus', 'Verification Status', ['class' => 'control-label']) !!}
				                    {{ Form::select('verStatus', $verStatusList, "", ['class' => 'form-control', 'id' => 'selVerStatus']) }}
								</div>
							@endif
							<div class="{{ $colClass }}">
				                {!! Form::label('selDesignation', 'Designation', ['class' => 'control-label']) !!}
			                    {{ Form::select('selDesignation', $designationList, "", ['class' => 'form-control', 'id' => 'selDesignation']) }}
							</div>
							<div class="{{ $colClass }}">
				                {!! Form::label('selDepartment', 'Department', ['class' => 'control-label']) !!}
			                    {{ Form::select('selDepartment', $departmentList, "", ['class' => 'form-control', 'id' => 'selDepartment']) }}
							</div>
							<div class="{{ $colClass }}">
				                {!! Form::label('selBadge', 'Badge', ['class' => 'control-label']) !!}
			                    {{ Form::select('selBadge', $badgeList, "", ['class' => 'form-control', 'id' => 'selBadge']) }}
							</div>
						</div>
					</div>			
				</section>
			</div>
		</div>
	</div>
{{ Form::close() }}