@extends('ws_template')

@section('int_scripts')
<script>
	var moduleName = 'Premium Coupon';
	var couponTableObj;
	$(document).ready(function(){
	    couponTableObj = $('#coupon-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('premiumCouponDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrToken = "{{ $usrtoken }}"
            	}
	        },
	        columns: [
	            { data: '0', name: 'coupon_name' },
	            { data: '1', name: 'coupon_count' },
	            { data: '2', name: 'coupon_validity_start_date' },
	            { data: '3', name: 'coupon_validity_end_date' },
	            { data: '4', name: 'subscription_validity_days' },
	            { data: '5', name: 'allotted_space_in_gb' },
	            { data: '6', name: 'coupon_multi_usage_count' },
	            { data: '7', name: 'status', sortable: false, searchable: false },
	            { data: '8', name: 'generation', sortable: false, searchable: false },
	            @if($modulePermissions->module_edit == 1 || $modulePermissions->module_delete == 1)
	            	{ data: '9', name: 'action', sortable: false, searchable: false }
	            @endif
	        ],
	        "order": [[ 3, "desc" ]],
			"columnDefs": [
	        	{
	                "targets": 7,
	                "render": function ( data, type, row )
	                {
	                	var dataArr = data.split("_");
	                	var id = dataArr[0];
	                	var isActive = dataArr[1];
                		var fnName = "changePremiumCouponStatus";

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

	function loadQuickAddEditCouponModal(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('premiumCoupon.loadAddEditModal') !!}",
			dataType: "json",
			data:"couponId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditCoupon').html(data.view);
				$('#addEditCouponModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function deleteCoupon(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('premiumCoupon.checkAvailForDelete') !!}",
			dataType: "json",
			data:"couponId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				bootbox.dialog({
					message: "Do you really want to delete this Coupon?",
					title: "Confirm Delete",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {

								$.ajax({
									type: 'POST',
									crossDomain: true,
									url: "{!!  route('premiumCoupon.delete') !!}",
									dataType: "json",
									data:"couponId="+id+"&usrToken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadCouponTable();
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

	function changePremiumCouponStatus(id, statusActive)
	{
		var dataToBeSent = "couponId="+id+"&statusActive="+statusActive+"&usrToken="+"{{ $usrtoken }}";	
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('premiumCoupon.changeStatus') !!}",
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
				
			showSystemResponse(status, msg);
			reloadCouponTable();
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function generateCouponCodes(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('premiumCoupon.checkAvailForGenerate') !!}",
			dataType: "json",
			data:"couponId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				bootbox.dialog({
					message: "Do you really want to generate this Coupon?",
					title: "Confirm Generate",
						buttons: {
							yes: {
							label: "Yes",
							className: "btn-primary",
							callback: function() {

								$.ajax({
									type: 'POST',
									crossDomain: true,
									url: "{!!  route('premiumCoupon.generate') !!}",
									dataType: "json",
									data:"couponId="+id+"&usrToken="+"{{ $usrtoken }}",
								})
								.done(function(data){
									var status = data.status;
									var msg = data.msg;

									showSystemResponse(status, msg);

									if(status*1 == 1)
									{
										reloadCouponTable();
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

	function viewCouponDetails(id)
	{
		$("#viewId").val(id);
		$("#frmViewPremiumCoupon").submit();
	}

	function reloadCouponTable()
	{
		couponTableObj.ajax.reload();
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
		        	Premium Coupon List
		        	@if($modulePermissions->module_add == 1)
			        	&nbsp;&nbsp;
			        	<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="loadQuickAddEditCouponModal(0);">
				    		<i class="fa fa-plus"></i>&nbsp;&nbsp;
				    		Add
				    	</a>
				    @endif
		        </h3>
		    </div>
            <div class="box-body">
			    <div class="table">
			        <table id="coupon-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Coupon Name</th>
			                    <th>Coupon Count</th>
			                    <th>Valid From</th>
			                    <th>Valid Upto</th>
			                    <th>Allotted Days</th>	
			                    <th>Allotted Space (in GBs)</th>	
			                    <th>Usage Count</th>	
			                    <th>Status</th>		
			                     <th>Generation</th>	     	                    
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
<div id="divAddEditCoupon"></div>
@endsection