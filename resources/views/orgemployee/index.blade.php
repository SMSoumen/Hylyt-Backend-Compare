@extends('ws_template')

@section('int_scripts')
<script>
	var empTableObj;
	@if(isset($usrtoken) && $usrtoken != "")
		function loadQuickAddEditEmployeeModal(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadAddEditModal') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#addEditEmployeeModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}
		
		function loadModifyEmployeeEmail(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadEditEmailModal') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#editEmployeeEmailModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}
		
		function loadOrgEmployeeUploadExcel(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadUploadExcel') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divUploadExcel').html(data.view);
					$('#uploadExcel').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function loadRestoreEmployeeContentModal(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadRestoreContentModal') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#divRestoreContentModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function deleteEmployee(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.checkAvailForDelete') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					bootbox.dialog({
						message: "Do you really want to delete this employee?",
						title: "Confirm Delete",
							buttons: {
								yes: {
								label: "Yes",
								className: "btn-primary",
								callback: function() {

									$.ajax({
										type: 'POST',
										url: "{!!  route('orgEmployee.delete') !!}",
										dataType: "json",
										data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
									})
									.done(function(data){
										var status = data.status;
										var msg = data.msg;

										showSystemResponse(status, msg);

										if(status*1 == 1)
										{
											reloadEmployeeTable();
										}
									})
									.fail(function(xhr,ajaxOptions, thrownError) {
									})
									.always(function() {
									});	
								}
							},
							no: {
								label: "No",
								className: "btn-primary",
								callback: function() {
								}
							}
						}
					});
				}
				else
				{		
					bootbox.dialog({
						message: data.msg,
						title: "Warning!!",
							buttons: {
							no: {
								label: "OK",
								className: "btn-primary",
								callback: function() {
								}
							}
						}
					});			
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function detachSubscribedEmployee(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.checkAvailForDetachment') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					bootbox.dialog({
						message: "Do you really want to detach this employee?",
						title: "Confirm Detachment",
							buttons: {
								yes: {
								label: "Yes",
								className: "btn-primary",
								callback: function() {

									$.ajax({
										type: 'POST',
										url: "{!!  route('orgEmployee.detach') !!}",
										dataType: "json",
										data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
									})
									.done(function(data){
										var status = data.status;
										var msg = data.msg;

										showSystemResponse(status, msg);

										if(status*1 == 1)
										{
											reloadEmployeeTable();
										}
									})
									.fail(function(xhr,ajaxOptions, thrownError) {
									})
									.always(function() {
									});	
								}
							},
							no: {
								label: "No",
								className: "btn-primary",
								callback: function() {
								}
							}
						}
					});
				}
				else
				{		
					bootbox.dialog({
						message: data.msg,
						title: "Warning!!",
							buttons: {
							no: {
								label: "OK",
								className: "btn-primary",
								callback: function() {
								}
							}
						}
					});			
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}
		
		function changeEmployeeWebAccess(id, currStatus)
		{
			var statusMessage = "";
			var statusChangeTo = 0;
			
			if(currStatus*1 == 1)
			{
				statusMessage = "Do you wish to disable web access for selected employee?";
				statusActive = 0;
			}
			else
			{
				statusMessage = "Do you wish to enable web access for selected employee?";
				statusActive = 1;
			}

			bootbox.dialog({
				message: statusMessage,
				title: "Confirm Status Change",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() 
						{
							$.ajax({
								type: 'POST',
								url: "{!!  route('orgEmployee.changeWebAccessStatus') !!}",
								dataType: "json",
								data:"empId="+id+"&statusActive="+statusActive+"&usrtoken="+"{{ $usrtoken }}",
							})
							.done(function(data){
								var status = data.status;
								var msg = data.msg;

								showSystemResponse(status, msg);

								if(status*1 == 1)
								{
									reloadEmployeeTable();
								}
							})
							.fail(function(xhr,ajaxOptions, thrownError) {
							})
							.always(function() {
							});	
						}
					},
					no: {
						label: "No",
						className: "btn-primary",
						callback: function() {
						}
					}
				}
			});
		}
		
		function changeEmployeeStatus(id,statusActive)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.changeStatus') !!}",
				dataType: "json",
				data:"empId="+id+"&statusActive="+statusActive+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				var status = data.status;
				var msg = data.msg;

				showSystemResponse(status, msg);

				if(status*1 == 1)
				{
					reloadEmployeeTable();
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function changeSelectedEmployeeStatus(statusActive)
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();
				console.log( index + " : " + isChecked + ' : ' + empId);
				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				bootbox.dialog({
					message: "Do you want to send credentials to selected employee(s)?",
					title: "Confirm Send Mail",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() 
							{

								$.ajax({
									type: 'POST',
									url: "{!!  route('orgEmployee.changeStatus') !!}",
									dataType: "json",
									data:"empIdArr="+selIdArr+"&isMulti=1"+"&statusActive="+statusActive+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadEmployeeTable();
									}
								})
								.fail(function(xhr,ajaxOptions, thrownError) {
								})
								.always(function() {
								});
							}
						},
						no: {
							label: "No",
							className: "btn-primary",
							callback: function() {
							}
						}
					}
				});
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
				
		}

		function loadModifyEmployeeQuotaModal(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadModifyQuotaModal') !!}",
				dataType: "json",
				data:"empId="+id+"&isMulti=0"+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#divModifyQuotaModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function loadModifySelectedEmployeeQuotaModal()
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();
				
				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				$.ajax({
					type: 'POST',
					url: "{!!  route('orgEmployee.loadModifyQuotaModal') !!}",
					dataType: "json",
					data:"empIdArr="+selIdArr+"&isMulti=1"+"&usrtoken="+"{{ $usrtoken }}",
				})
				.done(function(data){
					if(data.status*1 == 1)
					{	
						$('#divAddEditEmployee').html(data.view);
						$('#divModifyQuotaModal').modal('show');
					}
				})
				.fail(function(xhr,ajaxOptions, thrownError) {
				})
				.always(function() {
				});	
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
		}

		function loadModifyEmployeeShareRightModal(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadModifyShareRightModal') !!}",
				dataType: "json",
				data:"empId="+id+"&isMulti=0"+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#divModifyShareRightModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});	
		}

		function loadModifySelectedEmployeeShareRightModal()
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();

				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				$.ajax({
					type: 'POST',
					url: "{!!  route('orgEmployee.loadModifyShareRightModal') !!}",
					dataType: "json",
					data:"empIdArr="+selIdArr+"&isMulti=1"+"&usrtoken="+"{{ $usrtoken }}",
				})
				.done(function(data){
					if(data.status*1 == 1)
					{	
						$('#divAddEditEmployee').html(data.view);
						$('#divModifyShareRightModal').modal('show');
					}
				})
				.fail(function(xhr,ajaxOptions, thrownError) {
				})
				.always(function() {
				});
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
		}

		function sendEmployeeCredentialMail(id)
		{
			bootbox.dialog({
				message: "Do you want to send credentials to selected employee?",
				title: "Confirm Send Mail",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() {
							
							$.ajax({
								type: 'POST',
								url: "{!!  route('orgEmployee.sendCredentialMail') !!}",
								dataType: "json",
								data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
							})
							.done(function(data){
								var status = data.status;
								var msg = data.msg;
								showSystemResponse(status, msg);
							})
							.fail(function(xhr,ajaxOptions, thrownError) {
							})
							.always(function() {
							});
							
						}
					},
					no: {
						label: "No",
						className: "btn-primary",
						callback: function() {
						}
					}
				}
			});
		}

		function viewEmployeeCredentials(id)
		{
			$.ajax({
				type: 'POST',
				url: "{!!  route('orgEmployee.loadCredentialModal') !!}",
				dataType: "json",
				data:"empId="+id+"&usrtoken="+"{{ $usrtoken }}",
			})
			.done(function(data){
				if(data.status*1 == 1)
				{	
					$('#divAddEditEmployee').html(data.view);
					$('#divViewEmployeeCredentialsModal').modal('show');
				}
			})
			.fail(function(xhr,ajaxOptions, thrownError) {
			})
			.always(function() {
			});
		}

		function sendSelectedEmployeeCredentialMail()
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();
				console.log( index + " : " + isChecked + ' : ' + empId);
				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				bootbox.dialog({
					message: "Do you want to send credentials to selected employee(s)?",
					title: "Confirm Send Mail",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() 
							{
								$.ajax({
									type: 'POST',
									url: "{!!  route('orgEmployee.sendCredentialMail') !!}",
									dataType: "json",
									data:"empIdArr="+selIdArr+"&isMulti=1"+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;
									showSystemResponse(status, msg);
								})
								.fail(function(xhr,ajaxOptions, thrownError) {
								})
								.always(function() {
								});
							}
						},
						no: {
							label: "No",
							className: "btn-primary",
							callback: function() {
							}
						}
					}
				});
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
		}
		
		function modifyEmployeeFileSaveShare(id, currStatus)
		{
			var statusMessage = "";
			var statusChangeTo = 0;
			
			if(currStatus*1 == 1)
			{
				statusMessage = "Do you wish to disable file save/share for selected employee?";
				statusChangeTo = 0;
			}
			else
			{
				statusMessage = "Do you wish to enable file save/share for selected employee?";
				statusChangeTo = 1;
			}

			let isMulti = 0;

			bootbox.dialog({
				message: statusMessage,
				title: "Confirm Status Change",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() 
						{
							$.ajax({
								type: 'POST',
								url: "{!!  route('orgEmployee.changeFileSaveShareEnabledStatus') !!}",
								dataType: "json",
								data:"empId="+id+"&isMulti="+isMulti+"&fileSaveShareEnabled="+statusChangeTo+"&usrtoken="+"{{ $usrtoken }}",
							})
							.done(function(data){
								var status = data.status;
								var msg = data.msg;

								showSystemResponse(status, msg);

								if(status*1 == 1)
								{
									reloadEmployeeTable();
								}
							})
							.fail(function(xhr,ajaxOptions, thrownError) {
							})
							.always(function() {
							});	
						}
					},
					no: {
						label: "No",
						className: "btn-primary",
						callback: function() {
						}
					}
				}
			});
		}

		function modifySelectedEmployeeEmployeeFileSaveShare(statusChangeTo)
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();
				console.log( index + " : " + isChecked + ' : ' + empId);
				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});
			
			var statusMessage = '';
			if(statusChangeTo*1 == 1)
			{
				statusMessage = "Do you wish to enable file save/share for selected employee(s)?";
			}
			else
			{
				statusMessage = "Do you wish to disable file save/share for selected employee(s)?";
			}

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				bootbox.dialog({
					message: statusMessage,
					title: "Confirm Status Change",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() 
							{

								$.ajax({
									type: 'POST',
									url: "{!!  route('orgEmployee.changeFileSaveShareEnabledStatus') !!}",
									dataType: "json",
									data:"empIdArr="+selIdArr+"&isMulti=1"+"&fileSaveShareEnabled="+statusChangeTo+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadEmployeeTable();
									}
								})
								.fail(function(xhr,ajaxOptions, thrownError) {
								})
								.always(function() {
								});
							}
						},
						no: {
							label: "No",
							className: "btn-primary",
							callback: function() {
							}
						}
					}
				});
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
				
		}
		
		function modifyEmployeeScreenShare(id, currStatus)
		{
			var statusMessage = "";
			var statusChangeTo = 0;
			
			if(currStatus*1 == 1)
			{
				statusMessage = "Do you wish to disable screen save/share for selected employee?";
				statusChangeTo = 0;
			}
			else
			{
				statusMessage = "Do you wish to enable screen save/share for selected employee?";
				statusChangeTo = 1;
			}

			let isMulti = 0;

			bootbox.dialog({
				message: statusMessage,
				title: "Confirm Status Change",
					buttons: {
						yes: {
						label: "Yes",
						className: "btn-primary",
						callback: function() 
						{
							$.ajax({
								type: 'POST',
								url: "{!!  route('orgEmployee.changeScreenShareEnabledStatus') !!}",
								dataType: "json",
								data:"empId="+id+"&isMulti="+isMulti+"&screenShareEnabled="+statusChangeTo+"&usrtoken="+"{{ $usrtoken }}",
							})
							.done(function(data){
								var status = data.status;
								var msg = data.msg;

								showSystemResponse(status, msg);

								if(status*1 == 1)
								{
									reloadEmployeeTable();
								}
							})
							.fail(function(xhr,ajaxOptions, thrownError) {
							})
							.always(function() {
							});	
						}
					},
					no: {
						label: "No",
						className: "btn-primary",
						callback: function() {
						}
					}
				}
			});
		}

		function modifySelectedEmployeeEmployeeScreenShare(statusChangeTo)
		{
			var selIdArr = [];
			$( "input.empIsSelected" ).each(function( index ) {
				const isChecked = $(this).iCheck('update')[0].checked;
				const empId = $(this).val();
				console.log( index + " : " + isChecked + ' : ' + empId);
				if(isChecked === true) {
					selIdArr.push(empId);
				}
			});
			
			var statusMessage = '';
			if(statusChangeTo*1 == 1)
			{
				statusMessage = "Do you wish to enable screen save/share for selected employee(s)?";
			}
			else
			{
				statusMessage = "Do you wish to disable screen save/share for selected employee(s)?";
			}

			if(selIdArr.length > 0)
			{
				selIdArr = JSON.stringify(selIdArr);

				bootbox.dialog({
					message: statusMessage,
					title: "Confirm Status Change",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() 
							{

								$.ajax({
									type: 'POST',
									url: "{!!  route('orgEmployee.changeScreenShareEnabledStatus') !!}",
									dataType: "json",
									data:"empIdArr="+selIdArr+"&isMulti=1"+"&screenShareEnabled="+statusChangeTo+"&usrtoken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadEmployeeTable();
									}
								})
								.fail(function(xhr,ajaxOptions, thrownError) {
								})
								.always(function() {
								});
							}
						},
						no: {
							label: "No",
							className: "btn-primary",
							callback: function() {
							}
						}
					}
				});
			}
			else
			{
				showSystemResponse(-1, 'Select atleast 1 appuser');
			}
				
		}

	@endif
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	@if(isset($onlyDeleted) && $onlyDeleted == 1)
		        		Deleted Appuser List
		        	@else
		        		Appuser List
		        	@endif
		        	
		        	@if(isset($usrtoken) && $usrtoken != "" && $modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="loadQuickAddEditEmployeeModal(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add
				    	</a>
			        	<a href="uploadAppuser" class="btn btn-success btn-sm">
				    		<i class="fa fa-upload"></i>&nbsp;&nbsp;
				    		Import
				    	</a>				    	
				    @endif
				</h3>  
		    </div>
		    @include('orgemployee.partialview._advancedSearch')
		    @include('orgemployee.partialview._employeeList')
		</div>
	</div>
</div>
<div id="divAddEditEmployee"></div>
@endsection