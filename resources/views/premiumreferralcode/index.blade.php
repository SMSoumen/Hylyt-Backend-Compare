@extends('ws_template')

@section('int_scripts')
<script>
	var moduleName = 'Premium Referral Code';
	var codeTableObj;
	$(document).ready(function(){
	    codeTableObj = $('#referral-code-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('premiumReferralCodeDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrToken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'referral_code' },
	            { data: '1', name: 'expiration_date' },
	            { data: '2', name: 'allotted_days' },
	            { data: '3', name: 'status', sortable: false, searchable: false },
	            @if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
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
                		var fnName = "changePremiumReferralCodeStatus";

						var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
						var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
						var event = 'changeStatus("'+moduleName+'","'+id+'",'+isActive+','+fnName+');';						
						var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

	                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"'>"
	                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
	                },
	            }           
       		]
	    });
	});

	function loadQuickAddEditReferralCodeModal(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('premiumReferralCode.loadAddEditModal') !!}",
			dataType: "json",
			data:"codeId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditReferralCode').html(data.view);
				$('#addEditReferralCodeModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function deleteReferralCode(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('premiumReferralCode.checkAvailForDelete') !!}",
			dataType: "json",
			data:"codeId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				bootbox.dialog({
					message: "Do you really want to delete this Referral Code?",
					title: "Confirm Delete",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {

								$.ajax({
									type: 'POST',
									crossDomain: true,
									url: "{!!  route('premiumReferralCode.delete') !!}",
									dataType: "json",
									data:"codeId="+id+"&usrToken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadReferralCodeTable();
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

	function changePremiumReferralCodeStatus(id, statusActive)
	{
		var dataToBeSent = "codeId="+id+"&statusActive="+statusActive+"&usrToken="+"{{ $usrtoken }}";	
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('premiumReferralCode.changeStatus') !!}",
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
				
			showSystemResponse(status, msg);
			reloadReferralCodeTable();
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function reloadReferralCodeTable()
	{
		codeTableObj.ajax.reload();
	}
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	Premium Referral Code List
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="loadQuickAddEditReferralCodeModal(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add
				    	</a>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="referral-code-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Referral Code</th>
			                    <th>Date of Expiration</th>
			                    <th>Allotted Days</th>	
			                    <th>Status</th>		     	                    
	            				@if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
			                    	<th>Action</th>
			                    @endif
			                </tr>
			            </thead>
			        </table>
			    </div>
			</div>
		</div>
	</div>
</div>
<div id="divAddEditReferralCode"></div>
@endsection