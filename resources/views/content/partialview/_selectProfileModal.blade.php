<script>	
	$(document).ready(function(){
		
	});
</script>
<style>
	.liSelProfile
	{
		background-color: #c0c2c1 !important;
	}
	.optProfile
	{
		cursor: pointer;
	}
</style>
<div id="selectProfileModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Select Profile
					
					@if($isOrgSelected)
						<button type="button" class="btn btn-default btn-sm" onclick="loadOrgUserInformationView();" style="margin-left: 20px;">
							<i class="fa fa-info"></i>&nbsp;&nbsp;Profile Details
						</button>
					@endif
				</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<ul class="nav nav-pills nav-stacked profile-selection">
						@foreach($organizations as $org)
							@php
								$orgId = $org->id;
								$orgName = $org->name;
								$orgUrl = $org->url;
								$orgUsername = $org->user_name;
								$orgUserEmail = $org->user_email;								
								$orgStatus = $org->user_status;
								$orgIsSelected = $org->is_selected;
								$orgMetrics = $org->metrics_Str;
								
								$isSelectedClass = "";
								if($orgIsSelected == 1)
									$isSelectedClass = "liSelProfile";
								
								if($orgStatus == 0)
									$orgName .= "(Inactive)";
							@endphp
							@if($orgStatus == 1)
								@include('content.partialview._profileRow')
							@endif
						@endforeach
					</ul>
				</div>				
			</div>
		</div>
	</div>
</div>