@extends('ws_template')

<?php
$couponId = 0;
$couponName = "";
$couponCodePrefix = "";
$couponName = "";
$couponCount = "";
$description = "";
$couponValidityStartDate = NULL;
$couponValidityEndDate = NULL;
$subscriptionValidityDays = 0;
$allottedSpaceInGb = "";
$allottedUserCount = "";
$isStackable = 0;
if(isset($coupon))
{
	$couponId = $id;//coupon->enterprise_coupon_id;
	$couponName = $coupon->coupon_name;
	$couponCodePrefix = $coupon->coupon_code_prefix;
	$couponCount = $coupon->coupon_count;
	$description = $coupon->description;
	$couponCodePrefix = $coupon->coupon_code_prefix;
	$couponValidityStartDate = $coupon->couponValidityStartDtDisp;
	$couponValidityEndDate = $coupon->couponValidityEndDtDisp;
	$subscriptionValidityDays = $coupon->subscription_validity_days;
	$allottedSpaceInGb = $coupon->allotted_space_in_gb;
	$allottedUserCount = $coupon->allotted_user_count;
	$isStackable = $coupon->is_stackable;
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif

@section('int_scripts')
<script>
	var moduleName = 'Enterprise Coupon';
	var couponCodeTableObj;
	var visibleColumns;
	var utilizationStatus = -1;
	$(document).ready(function(){

        visibleColumns = [ 0, 1, 2, 3 ];

	    couponCodeTableObj = $('#coupon-code-table').DataTable({
	        processing: true,
	        serverSide: true,
	         ajax: {
	            url: "{!!  route('enterpriseCouponCodeDatatable') !!}",
	            method: 'POST',
	            data: function ( d ) {
	                d.usrToken = "{{ $usrtoken }}";
	                d.couponId = "{{ $couponId }}";
	                d.utilizationStatus = utilizationStatus;
            	}
	        },
	        columns: [
	            { data: '0', name: 'coupon_code' },
	            { data: '1', name: 'utilization', searchable: false, sortable: false },
	            { data: '2', name: 'utilized_at' },
	            { data: '3', name: 'utilized_by_name', searchable: false, sortable: false },
	        ],
	        "order": [[ 2, "desc" ]],
        	dom: 'Bfrtip',
		    iDisplayLength: -1,
	        buttons: [
		        {
	                extend: 'excelHtml5',
	                title: 'Coupon Code List ' + '{{ $couponName }}',
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
	            // {
	            //     extend: 'pdfHtml5',
	            //     orientation: 'landscape',
	            //     pageSize: 'A4',
	            //     title: 'Coupon Code List ' + '{{ $couponName }}',
	            //     exportOptions: {
	            //         columns: visibleColumns
	            //     }
	            // }
	        ],
	    });
	});

	function loadQuickAddEditCouponCodeModal(id)
	{
		$.ajax({
			type: 'POST',
			url: "{!!  route('enterpriseCoupon.loadAddEditModal') !!}",
			dataType: "json",
			data:"couponCodeId="+id+"&usrToken="+"{{ $usrtoken }}",
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divAddEditCouponCode').html(data.view);
				$('#addEditCouponCodeModal').modal('show');
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}

	function reloadCouponTable()
	{
		couponCodeTableObj.ajax.reload();
	}

	function statusChanged(element, utilizedValue)
	{
		console.log('element : ', element)
		console.log('utilizedValue : ', utilizedValue)

		utilizationStatus = utilizedValue;

		reloadCouponTable();
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
		        	Enterprise Coupon Details
		        </h3>
		    </div>
            <div class="box-body">
            	<div class="row">
			        <div class="col-md-6">
			            <div class="form-group">
			                {!! Form::label('couponName', 'Coupon Name', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $couponName }}         
			            </div>
			        </div>
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('couponCodePrefix', 'Coupon Code Prefix', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $couponCodePrefix }}         
			            </div>
			        </div>
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('couponCount', 'Coupon Count', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $couponCount }}         
			            </div>
			        </div>
            	</div>
            	<div class="row">
			        <div class="col-md-6">
			            <div class="form-group">
			                {!! Form::label('couponValidityStartDate', 'Coupon Validity Start Date', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $couponValidityStartDate }}         
			            </div>
			        </div>
			        <div class="col-md-6">
			            <div class="form-group">
			                {!! Form::label('couponValidityEndDate', 'Coupon Validity End Date', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $couponValidityEndDate }}         
			            </div>
			        </div>
            	</div>
            	<div class="row">
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('allottedUserCount', 'Allotted User Count', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $allottedUserCount }}        
			            </div>
			        </div>
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('allottedSpaceInGb', 'Allotted Space (in GBs)', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $allottedSpaceInGb }}        
			            </div>
			        </div>
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('subscriptionValidityDays', 'Allotted Days', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $subscriptionValidityDays }}         
			            </div>
			        </div>
			        <div class="col-md-3">
			            <div class="form-group">
			                {!! Form::label('isStackable', 'Stackable', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $isStackable == 0 ? 'No' : 'Yes'  }}        
			            </div>
			        </div>
            	</div>
            	<div class="row">
			        <div class="col-md-12">
			            <div class="form-group">
			                {!! Form::label('description', 'Description', ['class' => 'control-label']) !!}
		                	<br/>
							{{ $description }}         
			            </div>
			        </div>
            	</div>
            	<div class="row">
            		<div class="col-md-12" align="center">
						<div class="btn-group btn-group-toggle" data-toggle="buttons">
							<label class="btn btn-primary active">
								<input type="radio" name="options" id="option1" autocomplete="off" onchange="statusChanged(this, -1)"> ALL
							</label>
							<label class="btn btn-primary">
								<input type="radio" name="options" id="option2" autocomplete="off" onchange="statusChanged(this, 0)"> PENDING
							</label>
							<label class="btn btn-primary">
								<input type="radio" name="options" id="option3" autocomplete="off" onchange="statusChanged(this, 1)"> UTILIZED
							</label>
						</div>
            		</div>
            	</div>
			    <div class="table">
			        <table id="coupon-code-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Coupon Code</th>
			                    <th>Status</th>	     	                    
			                    <th>Utilized On</th>	                    
			                    <th>Utilized By</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
			</div>
		</div>
	</div>
</div>
<div id="divAddEditCouponCode"></div>
@endsection