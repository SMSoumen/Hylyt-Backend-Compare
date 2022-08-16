<style>
	.asRow
	{
		margin-top: 12px;
	}
</style>
{{ Form::open(array('id' => 'frmAppuserFilters')) }}
	<div class="row">
		<div class="col-sm-12">
			<section class="panel panel-default">
				<div class="panel-heading">
					Advanced Search Options
					<!--<button type="button" name="btnToggleAdvancedSearch" id="btnToggleAdvancedSearch" class="btn-link text-info"><i class="fa fa-chevron-down"></i></button>-->
					&nbsp;&nbsp;&nbsp;
					<button type="button" name="resetFilters" id="resetFilters" class="btn-link pull-right"><i class="fa fa-undo text-danger"></i>&nbsp;<b>Reset</b></button>
					<button type="submit" name="applyFilters" id="applyFilters" class="btn-link pull-right"><i class="fa fa-check-square-o text-success"></i>&nbsp;<b>Apply Filters</b></button>
				</div>
				<div class="panel-body">
					<div class="row">								
						<div class="col-md-4">
			                {!! Form::label('selRegType', 'Registration Type', ['class' => 'control-label']) !!}
		                    {{ Form::select('selRegType', $regTypeList, "", ['class' => 'form-control', 'id' => 'selRegType']) }}
						</div>
						<div class="col-md-4">
			                {!! Form::label('selVerStatus', 'Verification Status', ['class' => 'control-label']) !!}
		                    {{ Form::select('selVerStatus', $verStatusList, "", ['class' => 'form-control', 'id' => 'selVerStatus']) }}
						</div>									
						<div class="col-md-4">
			                {!! Form::label('selStatus', 'Status', ['class' => 'control-label']) !!}
		                    {{ Form::select('selStatus', $statusList, "", ['class' => 'form-control', 'id' => 'selStatus']) }}
						</div>			
					</div>
					<div  id="divAdvancedSearch" style="display: block;">
						<div class="row asRow">
							<div class="col-md-4">
							  	<label class="control-label" for="sandbox-container-reg">Registration Date</label>
								<div class="input-group input-group-sm input-daterange sandbox-container" id="sandbox-container-reg">
									<span class="input-group-addon">From</span>
		                    		{{ Form::text('txtRegFromDate', null, ['class' => 'form-control', 'id' => 'txtRegFromDate', 'autocomplete' => 'off']) }}
								    <span class="input-group-addon">To</span>
		                    		{{ Form::text('txtRegToDate', null, ['class' => 'form-control', 'id' => 'txtRegToDate', 'autocomplete' => 'off']) }}
							    </div>
							</div>						
							<div class="col-md-4">
				                {!! Form::label('selGender', 'Gender', ['class' => 'control-label']) !!}
			                    {{ Form::select('selGender', $genderList, "", ['class' => 'form-control', 'id' => 'selGender']) }}
							</div>	
							<div class="col-md-4">
							  	<label class="control-label" for="sandbox-container-sync">Last Sync Date</label>
								<div class="input-group input-group-sm input-daterange sandbox-container" id="sandbox-container-sync">
									<span class="input-group-addon">From</span>
		                    		{{ Form::text('txtSyncFromDate', null, ['class' => 'form-control', 'id' => 'txtSyncFromDate', 'autocomplete' => 'off']) }}
								    <span class="input-group-addon">To</span>
		                    		{{ Form::text('txtSyncToDate', null, ['class' => 'form-control', 'id' => 'txtSyncToDate', 'autocomplete' => 'off']) }}
							    </div>
							</div>	
						</div>
						<div class="row asRow">
							<div class="col-md-4">
				                {!! Form::label('txtRefCode', 'Referral Code', ['class' => 'control-label']) !!}
			                    {{ Form::text('txtRefCode', null, ['class' => 'form-control', 'id' => 'txtRefCode', 'autocomplete' => 'off']) }}
							</div>							
							<div class="col-md-4">
				                {!! Form::label('selPremStatus', 'Account Type', ['class' => 'control-label']) !!}
			                    {{ Form::select('selPremStatus', $premStatusList, "", ['class' => 'form-control', 'id' => 'selPremStatus']) }}
							</div>						
							<div class="col-md-4">
				                {!! Form::label('selEnterpStatus', 'Enterprise Account', ['class' => 'control-label']) !!}
			                    {{ Form::select('selEnterpStatus', $enterpStatusList, "", ['class' => 'form-control', 'id' => 'selEnterpStatus']) }}
							</div>		
						</div>
					</div>
				</div>			
			</section>
		</div>
	</div>
{{ Form::close() }}