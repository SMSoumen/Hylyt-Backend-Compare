@extends('admin_template')

@section('int_scripts')
<script>
	@if(isset($viewDashboardMetrics) && $viewDashboardMetrics == 1)
		var mbPostfix = '&nbsp;<sup style="font-size: 20px">MB</sup>';
		$(document).ready(function()
		{
			//Load Counts
			$.ajax({
				type: "POST",
				url: siteurl+'/loadDashboardStats',
				dataType: "json",
				data: "",
			})
			.done(function(data) {			
				$('#divUserCnt').html(data.userCnt);
				$('#divDelUserCnt').html(data.delUserCnt);
				$('#divMbAllotted').html(data.mbAllotted + mbPostfix);
				$('#divMbUsed').html(data.mbUsed + mbPostfix);
			})
			.fail(function(xhr, ajaxOptions, thrownError) {
			})
			.always(function() {
			});
		});
	@endif
</script>
@stop

@section('content')
	@if(isset($viewDashboardMetrics) && $viewDashboardMetrics == 1)
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="col-lg-3 col-xs-6">
						<!-- small box -->
						<div class="small-box bg-aqua">
							<div class="inner">
								<h3 id="divUserCnt">
									0
								</h3>
								<p>
									Total Users
								</p>
							</div>
							<div class="icon">
								<i class="ion ion-person">
								</i>
							</div>
						</div>
					</div>
					<div class="col-lg-3 col-xs-6">
						<!-- small box -->
						<div class="small-box bg-red">
							<div class="inner">
								<h3 id="divDelUserCnt">
									0
								</h3>

								<p>
									Deleted Users
								</p>
							</div>
							<div class="icon">
								<i class="ion ion-trash-a">
								</i>
							</div>
						</div>
					</div>
					<div class="col-lg-3 col-xs-6">
						<!-- small box -->
						<div class="small-box bg-green">
							<div class="inner">
								<h3 id="divMbAllotted">
									0
								</h3>

								<p>
									Quota Allotted
								</p>
							</div>
							<div class="icon">
								<i class="ion ion-pie-graph">
								</i>
							</div>
						</div>
					</div>
					<!-- ./col -->
					<div class="col-lg-3 col-xs-6">
						<!-- small box -->
						<div class="small-box bg-yellow">
							<div class="inner">
								<h3 id="divMbUsed">
									0
								</h3>

								<p>
									Quota Utilized
								</p>
							</div>
							<div class="icon">
								<i class="ion ion-pie-graph">
								</i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	@endif
@endsection