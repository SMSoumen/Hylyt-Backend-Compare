@extends('ws_template')

@section('content')
<div id="employeeImport">
	<div class="row">
		<div class="col-md-12">
		<!-- Box -->
			<div class="box box-primary">
		    	<div class="box-header with-border">
		        	<h3 class="box-title">
		        		Data Import Status
		        	</h3>	
				 </div>
           		<div class="box-body">
           			@if(isset($importResultArray) && count($importResultArray) > 0)
           				<div class="table">
	           				<table class="table table-bordered" width="100%">
	           					<tr>
	           						<th>Sr No</th>
	           						<th>ID</th>
	           						<th>Name</th>
	           						<th>Email</th>
	           						<th>Department</th>
	           						<th>Designation</th>
	           						<th>Status</th>
	           						<th>Remarks</th>
	           					</tr>
	           					@foreach($importResultArray as $importResult)
	           						@php
	           							$impStatus = $importResult['importStatus'];	           							
	           							$statusText = "Failed";
	           							$statusLabelClass = "label-danger";
	           							if($impStatus > 0)
	           							{
	           								$statusText = "Success";
	           								$statusLabelClass = "label-success";
	           							}
	           						@endphp
	           						<tr>
	           							<td>{{ $importResult['srno'] }}</td>
	           							<td>{{ $importResult['id'] }}</td>
	           							<td>{{ $importResult['name'] }}</td>
	           							<td>{{ $importResult['email'] }}</td>
	           							<td>{{ $importResult['department'] }}</td>
	           							<td>{{ $importResult['designation'] }}</td>
	           							<td>
	           								<span class="label {{ $statusLabelClass }}">{{ $statusText }}</span>
	           							</td>
	           							<td>{{ $importResult['importMsg'] }}</td>
	           						</tr>
	           					@endforeach
	           				</table>
	           			</div>
           			@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection