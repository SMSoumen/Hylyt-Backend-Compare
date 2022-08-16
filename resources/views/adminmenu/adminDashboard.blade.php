<div class="row">
	<div class="col-md-12">
		<div class="row">
			<div class="col-md-4">
				<div class="box box-widget widget-user">
					<div class="widget-user-header bg-yellow">
						<h3 class="widget-user-username">Users</h3>
						<h5 class="widget-user-desc"></h5>
					</div>
					<div class="widget-user-image">
						<img class="img-circle" src="{{ url(Config::get('app_config.icon_dashboard_user')) }}" alt="User">
					</div>
					<div class="box-footer">
						<div class="row">
							<div class="col-sm-4 border-right">
								<div class="description-block">
									<h5 class="description-header">{{ $userCntAllotted }}</h5>
									<span class="description-text">Allotted</span>
								</div>
							</div>
							<!-- /.col -->
							<div class="col-sm-4 border-right">
								<div class="description-block">
									<h5 class="description-header">{{ $userCntUsed }}</h5>
									<span class="description-text">Utilized</span>
								</div>
							</div>
							<div class="col-sm-4">
								<div class="description-block">
									<h5 class="description-header">{{ $userCntAvailable }}</h5>
									<span class="description-text">Available</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col-md-3">
				<div class="info-box">
					<span class="info-box-icon bg-green"><i class="ion ion-person-stalker"></i></span>
					<div class="info-box-content">
						<span class="info-box-text">Groups</span>
						<span class="info-box-number">{{ $groupCount }}</span>
					</div>
				</div>
			</div>
			
			<div class="col-md-5">
				<div class="box box-widget widget-user">
					<div class="widget-user-header bg-aqua-active">
						<h3 class="widget-user-username">Quota</h3>
						<h5 class="widget-user-desc"></h5>
					</div>
					<div class="widget-user-image">
						<img class="img-circle" src="{{ url(Config::get('app_config.icon_dashboard_quota')) }}" alt="Quota">
					</div>
					<div class="box-footer">
						<div class="row">
							<div class="col-sm-4 border-right">
								<div class="description-block">
									<h5 class="description-header">{{ $gbAllotted }} GB</h5>
									<span class="description-text">Allotted</span>
								</div>
							</div>
							<!-- /.col -->
							<div class="col-sm-4 border-right">
								<div class="description-block">
									<h5 class="description-header">{{ $gbUsed }} MB</h5>
									<span class="description-text">Utilized</span>
								</div>
							</div>
							<div class="col-sm-4">
								<div class="description-block">
									<h5 class="description-header">{{ $gbAvailable }} MB</h5>
									<span class="description-text">Available</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

@if($isNew == 1)
	
	<div class="box" style="padding: 15px;">
		<div class="row">
			<div class="col-md-12">
				<ul class="nav nav-tabs">
					<li class="nav-item active">
						<a class="nav-link" data-toggle="tab" href="#users-tab">Users</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#groups-tab">Groups</a>
					</li>
				</ul>
				<div id="myTabContent" class="tab-content">
					<div class="tab-pane active" id="users-tab">

						<div style="padding: 15px;">
							<div class="row">
								<div class="col-md-12">
									<h2>Users</h2>
								</div>
							</div>

							<div class="row">
								<div class="col-md-12">
							        <table id="employees-table" class="table" width="100%">
							            <thead>
							            	<tr>
							            		<th>Employee Name</th>
							            		<th>Note Count</th>
							            		<th>Allotted Space (in MBs)</th>
							            		<th>Utilized Space (in MBs)</th>
							            		<th>Available Space (in MBs)</th>
							            		<th>Recent Note Created At</th>
							            		<!-- <th>Last Synced At</th> -->
							            		<th>Activation Status</th>
							            		<th>Verification Status</th>
							            	</tr>
							            </thead>
							            <tbody>
							            	@if(isset($employeeTableData) && count($employeeTableData) > 0)
							            		@foreach($employeeTableData as $empObj)
							            			@php
							            			$empName = $empObj["name"];
							            			$allottedSpaceMb = $empObj["allottedSpaceMb"];
							            			$usedSpaceMb = $empObj["usedSpaceMb"];
							            			$availableSpaceMb = $empObj["availableSpaceMb"];
							            			$empNoteCount = $empObj["noteCount"];
							            			$empIsActive = $empObj["isActive"];
							            			$empIsVerified = $empObj["isVerified"];
							            			$mostRecentNoteTs = $empObj["mostRecentNoteTs"];
							            			$lastSyncTs = $empObj["lastSyncTs"];
							            			@endphp
							            			<tr>
									                    <td>
									                    	{{ $empName }}
									                    </td>
									                    <td>
									                    	{{ $empNoteCount }}
									                    </td>
									                    <td>
									                    	{{ $allottedSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $usedSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $availableSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $mostRecentNoteTs > 0 ? dbToDispDateTimeWithTZOffset($mostRecentNoteTs, $tzOfs) : '' }}
									                    </td>
									                    <!-- <td>
									                    	{{ $lastSyncTs != "" && $lastSyncTs != 0 ? $lastSyncTs : '' }}
									                    </td> -->
									                    <td>
									                    	@if($empIsActive == 1)
									                    		Active
									                    	@else
									                    		Inactive
									                    	@endif
									                    </td>
									                    <td>
									                    	@if($empIsVerified == 1)
									                    		Verified
									                    	@else
									                    		Pending
									                    	@endif
									                    </td>
									                </tr>
							            		@endforeach
							            	@else
							            		<tr>
							            			<th colspan="5" style="text-align:center;">
							            				No User(s) found
							            			</th>
							            		</tr>
							            	@endif
							            </tbody>
							        </table>
								</div>
							</div>
					    </div>

					</div>
					<div class="tab-pane fade" id="groups-tab">

						<div style="padding: 15px;">
							<div class="row">
								<div class="col-md-12">
									<h2>Groups</h2>
								</div>
							</div>

							<div class="row">
								<div class="col-md-12">
							        <table id="groups-table" class="table" width="100%">
							            <thead>
							            	<tr>
							            		<th>Group Name</th>
							            		<th>Note Count</th>
							            		<th>Allotted Space (in MBs)</th>
							            		<th>Utilized Space (in MBs)</th>
							            		<th>Available Space (in MBs)</th>
							            		<th>Recent Note Created At</th>
							            	</tr>
							            </thead>
							            <tbody>
							            	@if(isset($groupTableData) && count($groupTableData) > 0)
							            		@foreach($groupTableData as $groupObj)
							            			@php
							            			$grpName = $groupObj["name"];
							            			$allottedSpaceMb = $groupObj["allottedSpaceMb"];
							            			$usedSpaceMb = $groupObj["usedSpaceMb"];
							            			$availableSpaceMb = $groupObj["availableSpaceMb"];
							            			$grpNoteCount = $groupObj["noteCount"];
							            			$mostRecentNoteTs = $groupObj["mostRecentNoteTs"];
							            			@endphp
							            			<tr>
									                    <td>
									                    	{{ $grpName }}
									                    </td>
									                    <td>
									                    	{{ $grpNoteCount }}
									                    </td>
									                    <td>
									                    	{{ $allottedSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $usedSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $availableSpaceMb }}
									                    </td>
									                    <td>
									                    	{{ $mostRecentNoteTs > 0 ? dbToDispDateTimeWithTZOffset($mostRecentNoteTs, $tzOfs) : '' }}
									                    </td>
									                </tr>
							            		@endforeach
							            	@else
							            		<tr>
							            			<th colspan="5" style="text-align:center;">
							            				No Group(s) found
							            			</th>
							            		</tr>
							            	@endif
							            </tbody>
							        </table>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>			
@endif

<script>
	$(document).ready(function(){

	    $('#employees-table').DataTable({
	        "order": [[ 0, "asc" ]],
        	"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
		    "iDisplayLength": -1
	    });

	    $('#groups-table').DataTable({
	        "order": [[ 0, "asc" ]],
        	"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
		    "iDisplayLength": -1
	    });

	});
</script>
