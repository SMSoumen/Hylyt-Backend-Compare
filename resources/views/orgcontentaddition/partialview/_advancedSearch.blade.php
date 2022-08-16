<style>
	.asRow
	{
		margin-top: 12px;
	}
</style>
<script>	
	function getAppuserDataForTable(data)
	{
		
		
	    var verStatus = $('#selVerStatus').val();
		if (verStatus != '')
		{
			data.verStatus = verStatus;
		} 
		
		var gender = $('#selGender').val();
		if (gender != '')
		{
			data.gender = gender;
		}
		
		var status = $('#selStatus').val();
		if (status != '')
		{
			data.status = status;
		}
		
		return data;
	}
</script>
{{ Form::open(array('id' => 'frmAppuserFilters')) }}
	<div class="row">
		<div class="col-sm-12">
			<section class="panel panel-default">
				<div class="panel-heading">
					Advanced Search Options
					<!--<button type="button" name="btnToggleAdvancedSearch" id="btnToggleAdvancedSearch" class="btn-link text-info"><i class="fa fa-chevron-down"></i></button>-->
					&nbsp;&nbsp;&nbsp;
					<button type="button" name="btnSend" id="btnSend" class="btn btn-primary btn-success" style="margin-left: 45px;" onclick="addContentForSelUsers();"><i class="fa fa-send fa-lg"></i>&nbsp;<b>Send</b></button>
					<button type="button" name="resetFilters" id="resetFilters" class="btn-link pull-right"><i class="fa fa-undo text-danger"></i>&nbsp;<b>Reset</b></button>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-4">
			                {!! Form::label('selVerStatus', 'Verification Status', ['class' => 'control-label']) !!}
		                    {{ Form::select('verStatus', $verStatusList, "", ['class' => 'form-control', 'id' => 'selVerStatus']) }}
						</div>									
						<div class="col-md-4">
			                {!! Form::label('selStatus', 'Status', ['class' => 'control-label']) !!}
		                    {{ Form::select('status', $statusList, "", ['class' => 'form-control', 'id' => 'selStatus']) }}
						</div>				
						<div class="col-md-4">
			                {!! Form::label('selGender', 'Gender', ['class' => 'control-label']) !!}
		                    {{ Form::select('gender', $genderList, "", ['class' => 'form-control', 'id' => 'selGender']) }}
						</div>
					</div>
					<div  id="divAdvancedSearch" style="display: block;">
						<div class="row asRow">	
						</div>						
					</div>
				</div>			
			</section>
		</div>
	</div>
{{ Form::close() }}